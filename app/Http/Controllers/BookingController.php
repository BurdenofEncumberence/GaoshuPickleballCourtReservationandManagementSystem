<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Mail\BookingReceiptMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Event;
use App\Models\OpenPlaySession;
use App\Models\Payment;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function create()
    {
        $user = Auth::user();

        if ($user) {
            if ($user->isAdmin() || $user->hasRole('manager')) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->hasRole('staff')) {
                return redirect()->route('staff.today');
            }
        }

        if (Court::count() === 0) {
            (new \Database\Seeders\CourtSeeder())->run();
        }

        $courts = Court::query()
            ->where('court_status', 'available')
            ->orderBy('id')
            ->get()
            ->map(fn (Court $court) => [
                'id' => $court->id,
                'name' => $court->court_name,
                'size' => $court->size ?: 'Regular (13.41m x 6.10m)',
                'rate' => (float) $court->price_per_hour,
            ])
            ->values();

        $bookings = Booking::query()
            ->where('date', '>=', now()->toDateString())
            ->where('booking_status', '!=', 'cancelled')
            ->get();

        $bookedSlots = [];
        foreach ($bookings as $booking) {
            $formattedSlot = Carbon::parse($booking->start_time)->format('g:i A') . ' - ' . Carbon::parse($booking->end_time)->format('g:i A');
            $dateStr = Carbon::parse($booking->date)->toDateString();
            $bookedSlots[$dateStr][$booking->court_id][] = $formattedSlot;
        }

        // Include courts allocated to active Open Play / Tournament sessions
        $openPlaySessions = OpenPlaySession::with('courts')
            ->where('date', '>=', now()->toDateString())
            ->whereIn('session_status', ['scheduled', 'approved_pending_payment', 'ongoing'])
            ->get();

        $specialSlots = [];
        foreach ($openPlaySessions as $session) {
            $dateStr = Carbon::parse($session->date)->toDateString();
            $sessionStart = Carbon::parse($session->start_time);
            $sessionEnd = Carbon::parse($session->end_time);

            $cur = $sessionStart->copy();
            while ($cur->lt($sessionEnd)) {
                $next = $cur->copy()->addHour();
                $formattedSlot = $cur->format('g:i A') . ' - ' . $next->format('g:i A');
                foreach ($session->courts as $allocatedCourt) {
                    $bookedSlots[$dateStr][$allocatedCourt->id][] = $formattedSlot;
                    $specialSlots[$dateStr][$allocatedCourt->id][$formattedSlot] = [
                        'type' => $session->session_type,
                        'title' => $session->title,
                        'id' => $session->id,
                    ];
                }
                $cur = $next;
            }
        }

        $events = Event::query()
            ->where('start_date', '<=', now()->addDays(30)->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->event_title,
                'discount' => (float) $event->discount,
                'startDate' => $event->start_date->toDateString(),
                'endDate' => $event->end_date->toDateString(),
            ]);

        return view('booking', [
            'courts' => $courts,
            'bookedSlots' => $bookedSlots,
            'specialSlots' => $specialSlots,
            'events' => $events,
        ]);
    }

    public function store(StoreBookingRequest $request, PayMongoService $payMongoService)
    {
        $user = Auth::user();

        if ($user && ($user->isAdmin() || $user->hasRole('manager'))) {
            return redirect()->route('admin.dashboard');
        }

        if ($user && $user->hasRole('staff')) {
            return redirect()->route('staff.today');
        }

        $slots = $request->parsedSlots();
        $refNum = 'PAY-' . strtoupper(Str::random(8));
        $paymentMethod = $request->input('payment_method', 'paymongo') ?: 'paymongo';
        if ($paymentMethod === 'cash') {
            $paymentMethod = 'paymongo';
        }
        $eventId = $request->input('event_id');

        // Calculate potential event discount
        $discountPercent = 0;
        $event = null;
        if ($eventId) {
            $event = Event::find($eventId);
            if ($event && $event->discount) {
                $discountPercent = (float) $event->discount;
            }
        }

        // Build itemized slot records and PayMongo line items
        $slotData = [];
        $lineItems = [];

        foreach ($slots as $item) {
            $court = Court::findOrFail($item['court_id']);
            $start = Carbon::parse($item['start_time']);
            $end = Carbon::parse($item['end_time']);
            $hours = max(1, $start->diffInMinutes($end) / 60);
            $rawAmount = round($court->price_per_hour * $hours, 2);
            $amount = $discountPercent > 0
                ? round($rawAmount * (1 - ($discountPercent / 100)), 2)
                : $rawAmount;

            $slotData[] = [
                'court' => $court,
                'item' => $item,
                'amount' => $amount,
            ];

            $lineItems[] = [
                'name' => "{$court->court_name} Reservation",
                'description' => "{$item['date']} (" . $start->format('g:i A') . ' - ' . $end->format('g:i A') . ')',
                'amount' => (int) round($amount * 100), // PayMongo accepts amounts in centavos
                'currency' => 'PHP',
                'quantity' => 1,
            ];
        }

        // Check if PayMongo checkout session should be created
        $isPayMongo = ($paymentMethod === 'paymongo') && $payMongoService->isConfigured();

        if ($isPayMongo) {
            $successUrl = route('booking.paymongo.success') . '?ref=' . urlencode($refNum);
            $cancelUrl = route('booking.paymongo.cancel') . '?ref=' . urlencode($refNum);

            try {
                $checkout = $payMongoService->createCheckoutSession($lineItems, [
                    'description' => "Gaoshou Pickleball Court Reservation ({$refNum})",
                    'reference_number' => $refNum,
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'customer' => [
                        'name' => $user?->name,
                        'email' => $user?->email,
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
                return back()
                    ->withInput()
                    ->withErrors(['payment_method' => 'Unable to connect to PayMongo checkout: ' . $e->getMessage()]);
            }

            $sessionId = $checkout['id'] ?? null;
            $checkoutUrl = $checkout['checkout_url'] ?? null;

            if (! $sessionId || ! $checkoutUrl) {
                return back()
                    ->withInput()
                    ->withErrors(['payment_method' => 'Invalid response from PayMongo checkout portal.']);
            }

            // Reserve slots with pending status awaiting successful payment redirect
            DB::transaction(function () use ($slotData, $eventId, $paymentMethod, $refNum, $sessionId) {
                foreach ($slotData as $data) {
                    $booking = Booking::create([
                        'user_id' => Auth::id(),
                        'court_id' => $data['court']->id,
                        'event_id' => $eventId,
                        'date' => $data['item']['date'],
                        'start_time' => $data['item']['start_time'],
                        'end_time' => $data['item']['end_time'],
                        'booking_status' => 'pending',
                        'booking_type' => 'online',
                    ]);

                    Payment::create([
                        'booking_id' => $booking->id,
                        'payment_method' => $paymentMethod,
                        'payment_status' => 'pending',
                        'amount' => $data['amount'],
                        'ref_num' => $refNum,
                        'checkout_session_id' => $sessionId,
                        'date' => now()->toDateString(),
                        'time' => now()->format('H:i:s'),
                    ]);
                }
            });

            return redirect()->away($checkoutUrl);
        }

        // Direct confirmation path: Cash at counter or direct non-gateway online simulation
        $createdBookings = [];
        $isCash = ($paymentMethod === 'cash');

        DB::transaction(function () use ($slotData, $eventId, $paymentMethod, $isCash, $refNum, &$createdBookings) {
            foreach ($slotData as $data) {
                $booking = Booking::create([
                    'user_id' => Auth::id(),
                    'court_id' => $data['court']->id,
                    'event_id' => $eventId,
                    'date' => $data['item']['date'],
                    'start_time' => $data['item']['start_time'],
                    'end_time' => $data['item']['end_time'],
                    'booking_status' => 'confirmed',
                    'booking_type' => 'online',
                ]);

                Payment::create([
                    'booking_id' => $booking->id,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $isCash ? 'pending' : 'paid',
                    'amount' => $data['amount'],
                    'ref_num' => $refNum,
                    'date' => now()->toDateString(),
                    'time' => now()->format('H:i:s'),
                ]);

                $createdBookings[] = $booking;
            }
        });

        // Dispatch official booking receipt email to customer
        if ($user && $user->email) {
            try {
                foreach ($createdBookings as $b) {
                    $b->loadMissing('court');
                }

                Mail::to($user->email)->send(new BookingReceiptMail(
                    user: $user,
                    bookings: $createdBookings,
                    refNum: $refNum,
                    paymentMethod: $paymentMethod,
                    discountPercent: $discountPercent,
                    event: $event,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $count = count($createdBookings);
        $message = $count > 1
            ? "{$count} court reservations confirmed under transaction {$refNum}."
            : 'Booking confirmed for ' . ($slots[0]['date'] ?? today()->toDateString()) . '.';

        return redirect()
            ->route('bookings.index')
            ->with('status', $message);
    }

    /**
     * Handle return redirect after player completes payment on PayMongo checkout page.
     */
    public function paymongoSuccess(Request $request, PayMongoService $payMongoService)
    {
        $sessionId = trim((string) $request->query('session_id', ''));
        if (str_contains($sessionId, '{')) {
            $sessionId = '';
        }
        $refNum = trim((string) $request->query('ref', ''));

        if (! $sessionId && ! $refNum) {
            return redirect()->route('bookings.index')
                ->with('status', 'No payment confirmation details provided.');
        }

        // Find matching payments by ref or session_id
        $payments = Payment::where(function ($q) use ($sessionId, $refNum) {
            if ($sessionId) {
                $q->where('checkout_session_id', $sessionId);
            }
            if ($refNum) {
                $q->orWhere('ref_num', $refNum);
            }
        })->get();

        if ($payments->isEmpty()) {
            return redirect()->route('bookings.index')
                ->with('status', 'Payment received, but no reservations matched this transaction.');
        }

        // Determine actual PayMongo session ID from request or database record
        $actualSessionId = $sessionId ?: $payments->first()?->checkout_session_id;

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
            // Local fallback simulation if API not configured
            $isPaid = true;
        }

        $bookingIds = $payments->pluck('booking_id')->filter()->unique();
        $bookings = Booking::whereIn('id', $bookingIds)->with(['court', 'event'])->get();

        // Ownership authorization check
        $currentUserId = Auth::id();
        $firstBooking = $bookings->first();
        if ($firstBooking && $firstBooking->user_id !== $currentUserId && ! Auth::user()?->isAdmin()) {
            abort(403, 'Unauthorized access to this booking transaction.');
        }

        if ($isPaid) {
            $details = $sessionData ? $payMongoService->extractPaymentDetails($sessionData) : [];
            $paymongoPaymentId = $details['payment_id'] ?? null;
            $sourceType = $details['source_type'] ?? 'online';

            $alreadyPaid = $payments->every(fn ($p) => $p->payment_status === 'paid');

            if (! $alreadyPaid) {
                DB::transaction(function () use ($payments, $bookings, $actualSessionId, $paymongoPaymentId, $sourceType) {
                    foreach ($payments as $payment) {
                        $payment->update([
                            'payment_status' => 'paid',
                            'checkout_session_id' => $actualSessionId ?: $payment->checkout_session_id,
                            'paymongo_payment_id' => $paymongoPaymentId ?: $payment->paymongo_payment_id,
                            'payment_method' => 'paymongo_' . $sourceType,
                        ]);
                    }

                    foreach ($bookings as $booking) {
                        $booking->update([
                            'booking_status' => 'confirmed',
                        ]);
                    }
                });

                // Dispatch receipt email
                $user = Auth::user();
                if ($user && $user->email) {
                    try {
                        $methodLabel = 'PayMongo (' . strtoupper(str_replace('_', ' ', $sourceType)) . ')';
                        Mail::to($user->email)->send(new BookingReceiptMail(
                            user: $user,
                            bookings: $bookings,
                            refNum: $payments->first()?->ref_num ?? 'N/A',
                            paymentMethod: $methodLabel,
                            discountPercent: (float) ($bookings->first()?->event?->discount ?? 0),
                            event: $bookings->first()?->event,
                        ));
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }

            return redirect()->route('bookings.index')
                ->with('status', 'Payment successful via PayMongo (' . strtoupper(str_replace('_', ' ', $sourceType)) . ')! Your reservation is confirmed.');
        }

        return redirect()->route('bookings.index')
            ->with('status', 'Your PayMongo checkout session is pending confirmation.');
    }

    /**
     * Handle return redirect when player cancels checkout on PayMongo page.
     */
    public function paymongoCancel(Request $request)
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
            $bookingIds = $payments->pluck('booking_id')->unique();
            $bookings = Booking::whereIn('id', $bookingIds)
                ->where('user_id', Auth::id())
                ->where('booking_status', 'pending')
                ->get();

            DB::transaction(function () use ($payments, $bookings) {
                foreach ($bookings as $booking) {
                    $booking->update(['booking_status' => 'cancelled']);
                }
                foreach ($payments as $payment) {
                    if ($payment->payment_status === 'pending') {
                        $payment->update(['payment_status' => 'failed']);
                    }
                }
            });
        }

        return redirect()->route('booking')
            ->with('status', 'Payment was cancelled. Your pending court slots have been released.');
    }

    public function index()
    {
        $bookings = Booking::with(['court', 'payments', 'event'])
            ->where('user_id', Auth::id())
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get();

        $openPlayRegistrations = Auth::user()
            ? Auth::user()->openPlayRegistrations()
                ->with(['session.courts'])
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('bookings-index', [
            'bookings' => $bookings,
            'openPlayRegistrations' => $openPlayRegistrations,
        ]);
    }
}
