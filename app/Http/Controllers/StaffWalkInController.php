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
use Illuminate\Support\Facades\Auth;
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

        $existingBookings = Booking::with(['user', 'court', 'payments.processor', 'statusUpdatedBy'])
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
                'status_updated_by_name' => $b->statusUpdatedBy?->name,
                'status_updated_at' => $b->status_updated_at?->format('g:i A'),
                'processed_by_name' => $latestPayment?->processor?->name,
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
     * Process and store a walk-in customer booking (single or multiple slots).
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
            'court_id' => ['nullable', 'integer', 'exists:courts,id'],
            'time_slot' => ['nullable', 'string'],
            'slots' => ['nullable'],
            'payment_method' => ['required', 'in:cash,paymongo,gcash,maya,card,counter'],
            'cash_tendered' => ['nullable', 'numeric', 'min:0'],
            'attendance_status' => ['required', 'in:show,confirmed'],
            'ref_num' => ['nullable', 'string', 'max:100'],
        ]);

        // Parse multi-slot payload or fallback to single court_id + time_slot
        $rawSlots = [];
        $slotsInput = $request->input('slots');
        if (is_string($slotsInput) && $slotsInput !== '') {
            $decoded = json_decode($slotsInput, true);
            if (is_array($decoded)) {
                $rawSlots = $decoded;
            }
        } elseif (is_array($slotsInput)) {
            $rawSlots = $slotsInput;
        }

        if (empty($rawSlots) && ! empty($validated['court_id']) && ! empty($validated['time_slot'])) {
            $rawSlots = [
                [
                    'courtId' => $validated['court_id'],
                    'timeSlot' => $validated['time_slot'],
                ],
            ];
        }

        if (empty($rawSlots)) {
            return back()->withInput()->withErrors([
                'time_slot' => 'Please select at least one available court and time slot.',
            ]);
        }

        $parsedItems = [];
        $seenKeys = [];
        $totalAmount = 0.0;

        foreach ($rawSlots as $slotEntry) {
            $cId = (int) ($slotEntry['courtId'] ?? $slotEntry['court_id'] ?? 0);
            $tSlot = trim((string) ($slotEntry['timeSlot'] ?? $slotEntry['time_slot'] ?? ''));

            if (! $cId || $tSlot === '') {
                continue;
            }

            $dedupKey = "{$cId}|{$tSlot}";
            if (isset($seenKeys[$dedupKey])) {
                continue;
            }
            $seenKeys[$dedupKey] = true;

            $times = $this->parseSlotTimes($tSlot);
            if ($times === null) {
                return back()->withInput()->withErrors([
                    'time_slot' => "Invalid time slot format '{$tSlot}'.",
                ]);
            }

            [$startTime, $endTime] = $times;
            $court = Court::find($cId);
            if (! $court) {
                return back()->withInput()->withErrors([
                    'time_slot' => 'Selected court does not exist.',
                ]);
            }

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
                    'time_slot' => "{$court->court_name} is already booked on {$validated['date']} for {$tSlot}.",
                ]);
            }

            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);
            $hours = max(1, $start->diffInMinutes($end) / 60);
            $slotAmount = round($court->price_per_hour * $hours, 2);
            $totalAmount = round($totalAmount + $slotAmount, 2);

            $parsedItems[] = [
                'court' => $court,
                'time_slot' => $tSlot,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'amount' => $slotAmount,
            ];
        }

        if (empty($parsedItems)) {
            return back()->withInput()->withErrors([
                'time_slot' => 'Please select at least one valid court and time slot.',
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

        $cashTendered = null;
        $changeAmount = null;

        if ($validated['payment_method'] === 'cash') {
            if (isset($validated['cash_tendered']) && $validated['cash_tendered'] !== '' && $validated['cash_tendered'] !== null) {
                $cashTendered = round((float) $validated['cash_tendered'], 2);
                if ($cashTendered < $totalAmount) {
                    return back()->withInput()->withErrors([
                        'cash_tendered' => 'Cash tendered (₱' . number_format($cashTendered, 2) . ') must be at least the total amount due (₱' . number_format($totalAmount, 2) . ').',
                    ]);
                }
            } else {
                $cashTendered = $totalAmount;
            }
            $changeAmount = round(max(0, $cashTendered - $totalAmount), 2);
        }

        // PayMongo Online Gateway handling
        if ($validated['payment_method'] === 'paymongo') {
            if (! $payMongoService->isConfigured()) {
                return back()->withInput()->withErrors([
                    'payment_method' => 'PayMongo gateway secret key is not configured in the system environment.',
                ]);
            }

            $lineItems = [];
            foreach ($parsedItems as $item) {
                $lineItems[] = [
                    'name' => "{$item['court']->court_name} Walk-In Booking",
                    'description' => "{$validated['date']} ({$item['time_slot']})",
                    'amount' => (int) round($item['amount'] * 100),
                    'currency' => 'PHP',
                    'quantity' => 1,
                ];
            }

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

            $staffId = Auth::id();

            DB::transaction(function () use (
                $user,
                $parsedItems,
                $validated,
                $paymentRef,
                $sessionId,
                $staffId
            ) {
                foreach ($parsedItems as $item) {
                    $booking = Booking::create([
                        'user_id' => $user->id,
                        'court_id' => $item['court']->id,
                        'event_id' => null,
                        'date' => $validated['date'],
                        'start_time' => $item['start_time'],
                        'end_time' => $item['end_time'],
                        'booking_status' => 'pending',
                        'booking_type' => 'walk_in',
                        'status_updated_by' => $staffId,
                        'status_updated_at' => now(),
                    ]);

                    Payment::create([
                        'booking_id' => $booking->id,
                        'payment_method' => 'paymongo',
                        'payment_status' => 'pending',
                        'amount' => $item['amount'],
                        'processed_by' => $staffId,
                        'ref_num' => $paymentRef,
                        'checkout_session_id' => $sessionId,
                        'date' => now()->toDateString(),
                        'time' => now()->format('H:i:s'),
                    ]);
                }
            });

            session([
                'walkin_attendance_' . $paymentRef => $validated['attendance_status'],
                'walkin_staff_' . $paymentRef => $staffId,
            ]);

            return redirect()->away($checkoutUrl);
        }

        // Standard counter payments (cash, manual gcash/maya/card)
        $createdBookings = [];
        $staffId = Auth::id();

        DB::transaction(function () use (
            $user,
            $parsedItems,
            $validated,
            $cashTendered,
            $changeAmount,
            $paymentRef,
            $staffId,
            &$createdBookings
        ) {
            foreach ($parsedItems as $item) {
                $booking = Booking::create([
                    'user_id' => $user->id,
                    'court_id' => $item['court']->id,
                    'event_id' => null,
                    'date' => $validated['date'],
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                    'booking_status' => $validated['attendance_status'],
                    'booking_type' => 'walk_in',
                    'status_updated_by' => $staffId,
                    'status_updated_at' => now(),
                ]);

                $booking->setRelation('court', $item['court']);

                Payment::create([
                    'booking_id' => $booking->id,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'paid',
                    'amount' => $item['amount'],
                    'cash_tendered' => $cashTendered,
                    'change_amount' => $changeAmount,
                    'processed_by' => $staffId,
                    'ref_num' => $paymentRef,
                    'date' => now()->toDateString(),
                    'time' => now()->format('H:i:s'),
                ]);

                $createdBookings[] = $booking;
            }
        });

        // Dispatch receipt email if user has a valid real email
        if ($user && $user->email && ! str_ends_with($user->email, '@gaoshou.local') && ! str_ends_with($user->email, '@kymnet.local')) {
            try {
                Mail::to($user->email)->send(new BookingReceiptMail(
                    user: $user,
                    bookings: $createdBookings,
                    refNum: $paymentRef,
                    paymentMethod: $validated['payment_method'],
                    discountPercent: 0,
                    event: null,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $firstBooking = $createdBookings[0];
        $slotSummary = collect($parsedItems)->map(fn ($i) => "{$i['court']->court_name} ({$i['time_slot']})")->join(', ');
        $courtSummary = collect($parsedItems)->pluck('court.court_name')->unique()->join(', ');
        $timeSummary = collect($parsedItems)->pluck('time_slot')->join(', ');

        $methodLabel = strtoupper($validated['payment_method']);
        $statusText = $validated['attendance_status'] === 'show' ? 'SHOW (Present)' : 'SCHEDULED';
        $statusMessage = "Walk-in booking confirmed for {$fullName}: {$slotSummary}. Attendance: {$statusText}. Payment: ₱" . number_format($totalAmount, 2) . " via {$methodLabel} [{$paymentRef}].";

        if ($validated['payment_method'] === 'cash') {
            $statusMessage .= " Tendered: ₱" . number_format($cashTendered, 2) . " · Change: ₱" . number_format($changeAmount, 2) . ".";
        }

        return redirect()->route('staff.today')->with([
            'status' => $statusMessage,
            'print_receipt_booking_id' => $firstBooking->id,
            'print_receipt_ref' => $paymentRef,
            'receipt_customer_name' => $fullName,
            'receipt_court_name' => $courtSummary,
            'receipt_time_slot' => $timeSummary,
            'receipt_amount' => $totalAmount,
            'receipt_payment_method' => $validated['payment_method'],
            'receipt_cash_tendered' => $cashTendered,
            'receipt_change_amount' => $changeAmount,
            'receipt_staff_name' => Auth::user()?->name,
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

        $payments = Payment::where(function ($q) use ($sessionId, $refNum) {
            if ($sessionId) {
                $q->where('checkout_session_id', $sessionId);
            }
            if ($refNum) {
                $q->orWhere('ref_num', $refNum);
            }
        })->get();

        if ($payments->isEmpty()) {
            return redirect()->route('staff.today')
                ->with('status', 'Payment received, but no matching walk-in booking record was found.');
        }

        $firstPayment = $payments->first();
        $actualSessionId = $sessionId ?: $firstPayment->checkout_session_id;
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

        $bookingIds = $payments->pluck('booking_id')->filter()->unique();
        $bookings = Booking::whereIn('id', $bookingIds)->with(['user', 'court'])->get();
        $firstBooking = $bookings->first();

        if ($isPaid) {
            $details = $sessionData ? $payMongoService->extractPaymentDetails($sessionData) : [];
            $paymongoPaymentId = $details['payment_id'] ?? null;
            $sourceType = $details['source_type'] ?? 'online';

            $refKey = $refNum ?: $firstPayment->ref_num;
            $attendanceStatus = session()->pull('walkin_attendance_' . $refKey, 'show');
            $staffId = Auth::id() ?? session()->pull('walkin_staff_' . $refKey);

            DB::transaction(function () use ($payments, $bookings, $actualSessionId, $paymongoPaymentId, $sourceType, $attendanceStatus, $staffId) {
                foreach ($payments as $payment) {
                    $payment->update([
                        'payment_status' => 'paid',
                        'checkout_session_id' => $actualSessionId ?: $payment->checkout_session_id,
                        'paymongo_payment_id' => $paymongoPaymentId ?: $payment->paymongo_payment_id,
                        'payment_method' => 'paymongo_' . $sourceType,
                        'processed_by' => $payment->processed_by ?: $staffId,
                    ]);
                }

                foreach ($bookings as $booking) {
                    $booking->update([
                        'booking_status' => $attendanceStatus,
                        'status_updated_by' => $staffId ?: $booking->status_updated_by,
                        'status_updated_at' => now(),
                    ]);
                }
            });

            // Dispatch receipt email if user has a valid real email
            if ($firstBooking && $firstBooking->user && $firstBooking->user->email && ! str_ends_with($firstBooking->user->email, '@gaoshou.local') && ! str_ends_with($firstBooking->user->email, '@kymnet.local')) {
                try {
                    $methodLabel = 'PayMongo (' . strtoupper(str_replace('_', ' ', $sourceType)) . ')';
                    Mail::to($firstBooking->user->email)->send(new BookingReceiptMail(
                        user: $firstBooking->user,
                        bookings: $bookings->all(),
                        refNum: $firstPayment->ref_num,
                        paymentMethod: $methodLabel,
                        discountPercent: 0,
                        event: null,
                    ));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $methodLabel = strtoupper(str_replace('_', ' ', $sourceType));
            $statusText = $firstBooking && $firstBooking->booking_status === 'show' ? 'SHOW (Present)' : 'SCHEDULED';
            $totalPaid = (float) $payments->sum('amount');

            return redirect()->route('staff.today')->with([
                'status' => "Walk-in booking confirmed via PayMongo ({$methodLabel}) for {$firstBooking?->user?->name}! Attendance: {$statusText}. Ref: {$firstPayment->ref_num}.",
                'print_receipt_booking_id' => $firstBooking?->id,
                'print_receipt_ref' => $firstPayment->ref_num,
                'receipt_customer_name' => $firstBooking?->user?->name,
                'receipt_court_name' => $bookings->pluck('court.court_name')->filter()->unique()->join(', '),
                'receipt_time_slot' => $bookings->map(fn ($b) => Carbon::parse($b->start_time)->format('g:i A') . ' - ' . Carbon::parse($b->end_time)->format('g:i A'))->join(', '),
                'receipt_amount' => $totalPaid,
                'receipt_payment_method' => 'paymongo',
                'receipt_staff_name' => Auth::user()?->name ?? $firstBooking?->statusUpdatedBy?->name,
            ]);
        }

        return redirect()->route('staff.today')->with(
            'status',
            "Payment for walk-in booking [{$firstPayment->ref_num}] is pending confirmation."
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

        $payments = Payment::query()
            ->when($sessionId, fn ($q) => $q->where('checkout_session_id', $sessionId))
            ->when($refNum && ! $sessionId, fn ($q) => $q->where('ref_num', $refNum))
            ->get();

        if ($payments->isNotEmpty()) {
            DB::transaction(function () use ($payments) {
                foreach ($payments as $payment) {
                    if ($payment->payment_status === 'pending') {
                        $payment->update(['payment_status' => 'failed']);
                        $booking = $payment->booking;
                        if ($booking && $booking->booking_status === 'pending') {
                            $booking->update(['booking_status' => 'cancelled']);
                        }
                    }
                }
            });
        }

        return redirect()->route('staff.walkin.create')->with(
            'status',
            'PayMongo checkout session was cancelled. The reserved slot(s) have been released.'
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
