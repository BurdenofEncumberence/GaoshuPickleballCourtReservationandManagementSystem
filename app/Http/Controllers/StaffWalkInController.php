<?php

namespace App\Http\Controllers;

use App\Mail\BookingReceiptMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayMongoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffWalkInController extends Controller
{
    /**
     * Standard 1-hour club match time slots.
     */
    protected array $standardTimeSlots = [
        '6:00 AM - 7:00 AM',
        '7:00 AM - 8:00 AM',
        '8:00 AM - 9:00 AM',
        '9:00 AM - 10:00 AM',
        '10:00 AM - 11:00 AM',
        '11:00 AM - 12:00 PM',
        '12:00 PM - 1:00 PM',
        '1:00 PM - 2:00 PM',
        '2:00 PM - 3:00 PM',
        '3:00 PM - 4:00 PM',
        '4:00 PM - 5:00 PM',
        '5:00 PM - 6:00 PM',
        '6:00 PM - 7:00 PM',
        '7:00 PM - 8:00 PM',
        '8:00 PM - 9:00 PM',
        '9:00 PM - 10:00 PM',
    ];

    /**
     * Display the walk-in booking creation form.
     */
    public function create(Request $request): View
    {
        $selectedDate = $request->input('date', today()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = today()->toDateString();
        }

        $courts = Court::where('court_status', 'available')
            ->orderBy('id')
            ->get()
            ->values();

        $existingBookings = Booking::with(['user', 'court', 'payments'])
            ->where('date', $selectedDate)
            ->where('booking_status', '!=', 'cancelled')
            ->get();

        // Map booked slots per court and build detailed inspector payload
        $bookedSlots = [];
        $bookedSlotDetails = [];
        foreach ($existingBookings as $b) {
            $slotStr = Carbon::parse($b->start_time)->format('g:i A') . ' - ' . Carbon::parse($b->end_time)->format('g:i A');
            $bookedSlots[$b->court_id][] = $slotStr;

            $latestPayment = $b->payments->sortByDesc('id')->first();
            $courtRate = (float) ($b->court?->price_per_hour ?? 0);
            $paidAmount = $latestPayment ? (float) $latestPayment->amount : $courtRate;

            $bookedSlotDetails[$b->court_id][$slotStr] = [
                'booking_id' => $b->id,
                'booking_ref' => 'BK-' . str_pad((string) $b->id, 6, '0', STR_PAD_LEFT),
                'customer_name' => $b->user?->name ?? 'Guest Player',
                'customer_email' => $b->user?->email ?? 'No email on file',
                'booking_type' => $b->isWalkIn() ? 'Walk-In Counter' : 'Online Reservation',
                'attendance_label' => match (true) {
                    $b->booking_status === 'show' => 'Show (Present)',
                    in_array($b->booking_status, ['no_show', 'no-show']) => 'No-Show (Absent)',
                    default => 'Scheduled (Awaiting)',
                },
                'court_name' => $b->court?->court_name ?? 'Court',
                'court_size' => $b->court?->size ?: 'Regular (13.41m x 6.10m)',
                'date_formatted' => Carbon::parse($selectedDate)->format('F j, Y'),
                'time_slot' => $slotStr,
                'payment_amount' => $paidAmount,
                'payment_status' => strtoupper($latestPayment?->payment_status ?? 'PAID'),
                'payment_method' => ucwords(str_replace('_', ' ', $latestPayment?->payment_method ?? 'cash')),
                'payment_ref' => $latestPayment?->ref_num ?: 'N/A',
                'cash_tendered' => $latestPayment?->cash_tendered !== null ? (float) $latestPayment->cash_tendered : null,
                'change_amount' => $latestPayment?->change_amount !== null ? (float) $latestPayment->change_amount : null,
                'receipt_print_url' => route('bookings.receipt', ['booking' => $b->id, 'autoprint' => 1]),
            ];
        }

        // Include courts allocated to active Open Play / Tournament sessions
        $openPlaySessions = \App\Models\OpenPlaySession::with(['courts', 'registrations.user'])
            ->whereDate('date', $selectedDate)
            ->whereIn('session_status', ['scheduled', 'approved_pending_payment', 'ongoing'])
            ->get();

        $specialSlots = [];
        foreach ($openPlaySessions as $session) {
            $sessionStart = Carbon::parse($session->start_time);
            $sessionEnd = Carbon::parse($session->end_time);
            $activeRegs = $session->registrations->whereIn('payment_status', ['paid', 'pending']);
            $sessionSlotsBooked = (int) $activeRegs->sum('slots_count');
            $sessionRevenue = (float) $activeRegs->sum('total_fee');

            $cur = $sessionStart->copy();
            while ($cur->lt($sessionEnd)) {
                $next = $cur->copy()->addHour();
                $slotStr = $cur->format('g:i A') . ' - ' . $next->format('g:i A');
                foreach ($session->courts as $allocatedCourt) {
                    $bookedSlots[$allocatedCourt->id][] = $slotStr;
                    $specialSlots[$allocatedCourt->id][$slotStr] = [
                        'type' => $session->session_type,
                        'type_label' => $session->session_type === 'tournament' ? 'Tournament' : 'Open Play',
                        'title' => $session->title,
                        'skill_level' => $session->skill_level,
                        'time_window' => $session->time_window,
                        'court_name' => $allocatedCourt->court_name,
                        'price_per_slot' => (float) $session->price_per_slot,
                        'registered_slots' => $sessionSlotsBooked,
                        'max_capacity' => (int) $session->max_capacity,
                        'total_revenue' => $sessionRevenue,
                    ];
                }
                $cur = $next;
            }
        }

        // Recent registered players for quick selection
        $recentPlayers = User::where('role', 'player')
            ->latest()
            ->take(20)
            ->get(['id', 'name', 'first_name', 'last_name', 'email']);

        return view('staff.walk-in', [
            'courts' => $courts,
            'timeSlots' => $this->standardTimeSlots,
            'selectedDate' => $selectedDate,
            'preselectedCourtId' => $request->query('court_id'),
            'preselectedSlot' => $request->query('time_slot'),
            'bookedSlots' => $bookedSlots,
            'bookedSlotDetails' => $bookedSlotDetails,
            'specialSlots' => $specialSlots,
            'recentPlayers' => $recentPlayers,
        ]);
    }

    /**
     * Process and store a walk-in customer booking.
     */
    public function store(Request $request, PayMongoService $payMongoService): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'time_slot' => ['required', 'string'],
            'payment_method' => ['required', 'in:cash,paymongo,gcash,maya,card,counter'],
            'cash_tendered' => ['nullable', 'numeric', 'min:0'],
            'attendance_status' => ['required', 'in:show,confirmed'],
            'ref_num' => ['nullable', 'string', 'max:100'],
        ]);

        $times = $this->parseSlotTimes($validated['time_slot']);
        if ($times === null) {
            return back()->withInput()->withErrors([
                'time_slot' => "Invalid time slot format '{$validated['time_slot']}'.",
            ]);
        }

        [$startTime, $endTime] = $times;
        $court = Court::findOrFail($validated['court_id']);

        // Check for booking conflict
        $conflict = Booking::where('court_id', $court->id)
            ->where('date', $validated['date'])
            ->where('booking_status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($conflict) {
            return back()->withInput()->withErrors([
                'time_slot' => "{$court->court_name} is already booked on {$validated['date']} for {$validated['time_slot']}.",
            ]);
        }

        // Resolve or create User
        $firstName = User::titleCaseName($validated['first_name']);
        $middleName = User::titleCaseName($validated['middle_name'] ?? null);
        $lastName = User::titleCaseName($validated['last_name']);
        $fullName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName])));

        $email = ! empty($validated['email']) ? strtolower(trim($validated['email'])) : null;
        $user = null;

        if ($email) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            if (! $email) {
                $slug = Str::slug($fullName) ?: 'walkin';
                $email = "walkin_{$slug}_" . time() . '_' . random_int(100, 999) . '@gaoshou.local';
            }

            $user = User::create([
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'name' => $fullName,
                'email' => $email,
                'password' => Hash::make(Str::random(16)),
                'role' => 'player',
                'email_verified_at' => now(),
            ]);
        }

        $paymentRef = ! empty($validated['ref_num'])
            ? trim($validated['ref_num'])
            : 'WALK-' . strtoupper(Str::random(8));

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $hours = max(1, $start->diffInMinutes($end) / 60);
        $amount = round($court->price_per_hour * $hours, 2);

        $cashTendered = null;
        $changeAmount = null;

        if ($validated['payment_method'] === 'cash') {
            if (isset($validated['cash_tendered']) && $validated['cash_tendered'] !== '' && $validated['cash_tendered'] !== null) {
                $cashTendered = round((float) $validated['cash_tendered'], 2);
                if ($cashTendered < $amount) {
                    return back()->withInput()->withErrors([
                        'cash_tendered' => 'Cash tendered (₱' . number_format($cashTendered, 2) . ') must be at least the total amount due (₱' . number_format($amount, 2) . ').',
                    ]);
                }
            } else {
                $cashTendered = $amount;
            }
            $changeAmount = round(max(0, $cashTendered - $amount), 2);
        }

        // PayMongo Online Gateway handling
        if ($validated['payment_method'] === 'paymongo') {
            if (! $payMongoService->isConfigured()) {
                return back()->withInput()->withErrors([
                    'payment_method' => 'PayMongo gateway secret key is not configured in the system environment.',
                ]);
            }

            $lineItems = [
                [
                    'name' => "{$court->court_name} Walk-In Booking",
                    'description' => "{$validated['date']} ({$validated['time_slot']})",
                    'amount' => (int) round($amount * 100),
                    'currency' => 'PHP',
                    'quantity' => 1,
                ],
            ];

            $successUrl = route('staff.walkin.paymongo.success') . '?ref=' . urlencode($paymentRef);
            $cancelUrl = route('staff.walkin.paymongo.cancel') . '?ref=' . urlencode($paymentRef);

            try {
                $checkout = $payMongoService->createCheckoutSession($lineItems, [
                    'description' => "Gaoshou Pickleball Walk-In ({$paymentRef})",
                    'reference_number' => $paymentRef,
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'customer' => [
                        'name' => $fullName,
                        'email' => ($user && ! str_ends_with($user->email, '@gaoshou.local') && ! str_ends_with($user->email, '@kymnet.local')) ? $user->email : null,
                        'phone' => $validated['phone'] ?? null,
                    ],
                    'payment_method_types' => [
                        'qrph',
                        'dob',
                        'paymaya',
                        'gcash',
                        'card',
                    ],
                ]);
            } catch (\Throwable $e) {
                report($e);
                return back()->withInput()->withErrors([
                    'payment_method' => 'Unable to connect to PayMongo checkout: ' . $e->getMessage(),
                ]);
            }

            $sessionId = $checkout['id'] ?? null;
            $checkoutUrl = $checkout['checkout_url'] ?? null;

            if (! $sessionId || ! $checkoutUrl) {
                return back()->withInput()->withErrors([
                    'payment_method' => 'Invalid response from PayMongo checkout portal.',
                ]);
            }

            DB::transaction(function () use (
                $user,
                $court,
                $validated,
                $startTime,
                $endTime,
                $amount,
                $paymentRef,
                $sessionId
            ) {
                $booking = Booking::create([
                    'user_id' => $user->id,
                    'court_id' => $court->id,
                    'event_id' => null,
                    'date' => $validated['date'],
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'booking_status' => 'pending',
                    'booking_type' => 'walk_in',
                ]);

                Payment::create([
                    'booking_id' => $booking->id,
                    'payment_method' => 'paymongo',
                    'payment_status' => 'pending',
                    'amount' => $amount,
                    'ref_num' => $paymentRef,
                    'checkout_session_id' => $sessionId,
                    'date' => now()->toDateString(),
                    'time' => now()->format('H:i:s'),
                ]);
            });

            session(['walkin_attendance_' . $paymentRef => $validated['attendance_status']]);

            return redirect()->away($checkoutUrl);
        }

        // Standard counter payments (cash, manual gcash/maya/card)
        $createdBooking = null;

        DB::transaction(function () use (
            $user,
            $court,
            $validated,
            $startTime,
            $endTime,
            $amount,
            $cashTendered,
            $changeAmount,
            $paymentRef,
            &$createdBooking
        ) {
            $createdBooking = Booking::create([
                'user_id' => $user->id,
                'court_id' => $court->id,
                'event_id' => null,
                'date' => $validated['date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'booking_status' => $validated['attendance_status'],
                'booking_type' => 'walk_in',
            ]);

            Payment::create([
                'booking_id' => $createdBooking->id,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'paid',
                'amount' => $amount,
                'cash_tendered' => $cashTendered,
                'change_amount' => $changeAmount,
                'ref_num' => $paymentRef,
                'date' => now()->toDateString(),
                'time' => now()->format('H:i:s'),
            ]);
        });

        // Dispatch receipt email if user has a valid real email
        if ($user && $user->email && ! str_ends_with($user->email, '@gaoshou.local') && ! str_ends_with($user->email, '@kymnet.local')) {
            try {
                $createdBooking->setRelation('court', $court);
                Mail::to($user->email)->send(new BookingReceiptMail(
                    user: $user,
                    bookings: [$createdBooking],
                    refNum: $paymentRef,
                    paymentMethod: $validated['payment_method'],
                    discountPercent: 0,
                    event: null,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $methodLabel = strtoupper($validated['payment_method']);
        $statusText = $validated['attendance_status'] === 'show' ? 'SHOW (Present)' : 'SCHEDULED';
        $statusMessage = "Walk-in booking confirmed for {$fullName} at {$court->court_name} ({$validated['time_slot']}). Attendance: {$statusText}. Payment: ₱" . number_format($amount, 2) . " via {$methodLabel} [{$paymentRef}].";

        if ($validated['payment_method'] === 'cash') {
            $statusMessage .= " Tendered: ₱" . number_format($cashTendered, 2) . " · Change: ₱" . number_format($changeAmount, 2) . ".";
        }

        return redirect()->route('staff.today')->with([
            'status' => $statusMessage,
            'print_receipt_booking_id' => $createdBooking->id,
            'print_receipt_ref' => $paymentRef,
            'receipt_customer_name' => $fullName,
            'receipt_court_name' => $court->court_name,
            'receipt_time_slot' => $validated['time_slot'],
            'receipt_amount' => $amount,
            'receipt_payment_method' => $validated['payment_method'],
            'receipt_cash_tendered' => $cashTendered,
            'receipt_change_amount' => $changeAmount,
        ]);
    }

    /**
     * Handle return from PayMongo hosted checkout on successful walk-in payment.
     */
    public function paymongoSuccess(Request $request, PayMongoService $payMongoService): RedirectResponse
    {
        $sessionId = trim((string) $request->query('session_id', ''));
        if (str_contains($sessionId, '{')) {
            $sessionId = '';
        }
        $refNum = trim((string) $request->query('ref', ''));

        if (! $sessionId && ! $refNum) {
            return redirect()->route('staff.today')
                ->with('status', 'No payment confirmation details provided.');
        }

        $payment = Payment::where(function ($q) use ($sessionId, $refNum) {
            if ($sessionId) {
                $q->where('checkout_session_id', $sessionId);
            }
            if ($refNum) {
                $q->orWhere('ref_num', $refNum);
            }
        })->first();

        if (! $payment) {
            return redirect()->route('staff.today')
                ->with('status', 'Payment received, but no matching walk-in booking record was found.');
        }

        $actualSessionId = $sessionId ?: $payment->checkout_session_id;
        $sessionData = [];
        $isPaid = false;

        if ($actualSessionId && $payMongoService->isConfigured()) {
            try {
                $sessionData = $payMongoService->getCheckoutSession($actualSessionId);
                $isPaid = $payMongoService->isSessionPaid($sessionData);
            } catch (\Throwable $e) {
                report($e);
            }
        } else {
            $isPaid = true;
        }

        $booking = $payment->booking;

        if ($isPaid) {
            $details = $sessionData ? $payMongoService->extractPaymentDetails($sessionData) : [];
            $paymongoPaymentId = $details['payment_id'] ?? null;
            $sourceType = $details['source_type'] ?? 'online';

            $attendanceStatus = session()->pull('walkin_attendance_' . ($refNum ?: $payment->ref_num), 'show');

            DB::transaction(function () use ($payment, $booking, $actualSessionId, $paymongoPaymentId, $sourceType, $attendanceStatus) {
                $payment->update([
                    'payment_status' => 'paid',
                    'checkout_session_id' => $actualSessionId ?: $payment->checkout_session_id,
                    'paymongo_payment_id' => $paymongoPaymentId ?: $payment->paymongo_payment_id,
                    'payment_method' => 'paymongo_' . $sourceType,
                ]);

                if ($booking) {
                    $booking->update([
                        'booking_status' => $attendanceStatus,
                    ]);
                }
            });

            // Dispatch receipt email if user has a valid real email
            if ($booking && $booking->user && $booking->user->email && ! str_ends_with($booking->user->email, '@gaoshou.local') && ! str_ends_with($booking->user->email, '@kymnet.local')) {
                try {
                    $booking->load('court');
                    $methodLabel = 'PayMongo (' . strtoupper(str_replace('_', ' ', $sourceType)) . ')';
                    Mail::to($booking->user->email)->send(new BookingReceiptMail(
                        user: $booking->user,
                        bookings: [$booking],
                        refNum: $payment->ref_num,
                        paymentMethod: $methodLabel,
                        discountPercent: 0,
                        event: null,
                    ));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $methodLabel = strtoupper(str_replace('_', ' ', $sourceType));
            $statusText = $booking && $booking->booking_status === 'show' ? 'SHOW (Present)' : 'SCHEDULED';

            return redirect()->route('staff.today')->with([
                'status' => "Walk-in booking confirmed via PayMongo ({$methodLabel}) for {$booking?->user?->name}! Attendance: {$statusText}. Ref: {$payment->ref_num}.",
                'print_receipt_booking_id' => $booking?->id,
                'print_receipt_ref' => $payment->ref_num,
                'receipt_customer_name' => $booking?->user?->name,
                'receipt_court_name' => $booking?->court?->court_name,
                'receipt_time_slot' => $booking ? (Carbon::parse($booking->start_time)->format('g:i A') . ' - ' . Carbon::parse($booking->end_time)->format('g:i A')) : null,
                'receipt_amount' => $payment->amount,
                'receipt_payment_method' => 'paymongo',
            ]);
        }

        return redirect()->route('staff.today')->with(
            'status',
            "Payment for walk-in booking [{$payment->ref_num}] is pending confirmation."
        );
    }

    /**
     * Handle return when staff or customer cancels PayMongo checkout.
     */
    public function paymongoCancel(Request $request): RedirectResponse
    {
        $sessionId = trim((string) $request->query('session_id', ''));
        if (str_contains($sessionId, '{')) {
            $sessionId = '';
        }
        $refNum = trim((string) $request->query('ref', ''));

        $payment = Payment::query()
            ->when($sessionId, fn ($q) => $q->where('checkout_session_id', $sessionId))
            ->when($refNum && ! $sessionId, fn ($q) => $q->where('ref_num', $refNum))
            ->first();

        if ($payment && $payment->payment_status === 'pending') {
            $booking = $payment->booking;
            DB::transaction(function () use ($payment, $booking) {
                $payment->update(['payment_status' => 'failed']);
                if ($booking && $booking->booking_status === 'pending') {
                    $booking->update(['booking_status' => 'cancelled']);
                }
            });
        }

        return redirect()->route('staff.walkin.create')->with(
            'status',
            'PayMongo checkout session was cancelled. The reserved slot has been released.'
        );
    }

    /**
     * Parse "8:00 AM - 9:00 AM" into ['08:00:00', '09:00:00'].
     */
    protected function parseSlotTimes(?string $slot): ?array
    {
        if (empty($slot)) {
            return null;
        }

        $parts = array_map('trim', explode('-', $slot));
        if (count($parts) !== 2) {
            return null;
        }

        try {
            $start = Carbon::parse($parts[0])->format('H:i:s');
            $end = Carbon::parse($parts[1])->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }

        if ($start >= $end) {
            return null;
        }

        return [$start, $end];
    }
}
