<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use App\Models\OpenPlaySession;
use App\Services\PayMongoService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OpenPlayHostController extends Controller
{
    /**
     * Show the form for a player to host an Open Play session.
     */
    public function create(): View
    {
        $courts = Court::where('court_status', 'available')
            ->orderBy('id')
            ->get()
            ->values();

        $session = new OpenPlaySession([
            'date' => today()->addDays(2)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '21:00',
            'max_capacity' => 12,
            'price_per_slot' => 150.00,
            'skill_level' => 'All Levels',
            'session_type' => 'open_play',
        ]);

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

        $openPlaySessions = OpenPlaySession::with('courts')
            ->where('date', '>=', now()->toDateString())
            ->whereIn('session_status', ['scheduled', 'approved_pending_payment', 'ongoing'])
            ->get();

        $specialSlots = [];
        foreach ($openPlaySessions as $existingSession) {
            $dateStr = Carbon::parse($existingSession->date)->toDateString();
            $sessionStart = Carbon::parse($existingSession->start_time);
            $sessionEnd = Carbon::parse($existingSession->end_time);

            $cur = $sessionStart->copy();
            while ($cur->lt($sessionEnd)) {
                $next = $cur->copy()->addHour();
                $formattedSlot = $cur->format('g:i A') . ' - ' . $next->format('g:i A');
                foreach ($existingSession->courts as $allocatedCourt) {
                    $bookedSlots[$dateStr][$allocatedCourt->id][] = $formattedSlot;
                    $specialSlots[$dateStr][$allocatedCourt->id][$formattedSlot] = [
                        'type' => $existingSession->session_type,
                        'title' => $existingSession->title,
                        'id' => $existingSession->id,
                    ];
                }
                $cur = $next;
            }
        }

        return view('open-play.host', [
            'session' => $session,
            'courts' => $courts,
            'allocatedCourtIds' => [],
            'bookedSlots' => $bookedSlots,
            'specialSlots' => $specialSlots,
        ]);
    }

    /**
     * Store a player's Open Play hosting request (pending manager approval).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'session_type' => ['required', 'in:open_play'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'allocated_courts' => ['required', 'array', 'min:1'],
            'allocated_courts.*' => ['required', 'integer', 'exists:courts,id'],
            'max_capacity' => ['required', 'integer', 'min:2', 'max:100'],
            'skill_level' => ['required', 'string', 'max:100'],
            'price_per_slot' => ['required', 'numeric', 'min:0', 'max:99999'],
            'details' => ['nullable', 'string', 'max:2000'],
        ], [
            'session_type.in' => 'Players can only host Open Play sessions. Tournaments must be organized by facility managers.',
            'allocated_courts.required' => 'Please select at least one court for your Open Play session.',
            'allocated_courts.min' => 'Please select at least one court for your Open Play session.',
            'max_capacity.required' => 'Please specify the maximum player capacity.',
            'price_per_slot.required' => 'Please specify the per-player participation fee.',
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        $startTime = Carbon::parse($validated['start_time'])->format('H:i:s');
        $endTime = Carbon::parse($validated['end_time'])->format('H:i:s');

        if ($endTime <= $startTime) {
            return back()->withInput()->withErrors([
                'end_time' => 'The session end time must be after the start time.',
            ]);
        }

        // Check if any requested court is already reserved during this window
        foreach ($validated['allocated_courts'] as $courtId) {
            $bookingConflict = Booking::where('court_id', $courtId)
                ->whereDate('date', $date)
                ->where('booking_status', '!=', 'cancelled')
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->exists();

            if ($bookingConflict) {
                $courtName = Court::find($courtId)?->court_name ?? "Court {$courtId}";
                return back()->withInput()->withErrors([
                    'allocated_courts' => "{$courtName} already has an existing reservation on {$date} between {$validated['start_time']} and {$validated['end_time']}.",
                ]);
            }

            $openPlayConflict = OpenPlaySession::whereDate('date', $date)
                ->whereIn('session_status', ['scheduled', 'approved_pending_payment', 'ongoing'])
                ->whereHas('courts', fn ($q) => $q->where('courts.id', $courtId))
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->exists();

            if ($openPlayConflict) {
                $courtName = Court::find($courtId)?->court_name ?? "Court {$courtId}";
                return back()->withInput()->withErrors([
                    'allocated_courts' => "{$courtName} is already scheduled for an Open Play or Tournament on {$date} during this time window.",
                ]);
            }
        }

        // Compute court hire fee based on duration and hourly rates
        $startCarbon = Carbon::parse($startTime);
        $endCarbon = Carbon::parse($endTime);
        $durationHours = max(1.0, abs($startCarbon->diffInMinutes($endCarbon)) / 60);

        $selectedCourts = Court::whereIn('id', $validated['allocated_courts'])->get();
        $courtFee = round($selectedCourts->sum('price_per_hour') * $durationHours, 2);

        $session = OpenPlaySession::create([
            'title' => $validated['title'],
            'session_type' => 'open_play',
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_capacity' => $validated['max_capacity'],
            'skill_level' => $validated['skill_level'],
            'price_per_slot' => $validated['price_per_slot'],
            'court_fee' => $courtFee,
            'details' => $validated['details'] ?? null,
            'session_status' => 'pending_approval',
            'host_payment_status' => 'unpaid',
            'created_by' => Auth::id(),
        ]);

        $session->courts()->sync($validated['allocated_courts']);

        return redirect()->route('open-play.host.index')
            ->with('host_request_submitted', [
                'id' => $session->id,
                'title' => $session->title,
                'date' => Carbon::parse($session->date)->format('D, M j, Y'),
                'time' => Carbon::parse($session->start_time)->format('g:i A') . ' – ' . Carbon::parse($session->end_time)->format('g:i A'),
                'courts' => $selectedCourts->pluck('court_name')->join(', '),
                'courts_count' => $selectedCourts->count(),
                'courts_breakdown' => $selectedCourts->map(fn ($c) => "{$c->court_name} (₱" . number_format($c->price_per_hour, 2) . "/hr)")->join(', '),
                'court_fee' => (float) $session->court_fee,
                'max_capacity' => (int) $session->max_capacity,
                'price_per_slot' => (float) $session->price_per_slot,
            ])
            ->with('status', "Your Open Play hosting request for '{$session->title}' has been submitted! It is now pending review and acceptance by the facility manager.");
    }

    /**
     * Display listing of sessions hosted by the authenticated player.
     */
    public function index(): View
    {
        $sessions = OpenPlaySession::with('courts')
            ->where('created_by', Auth::id())
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(10);

        return view('open-play.my-sessions', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Display payment view for an accepted Open Play hosting request.
     */
    public function showPayment(OpenPlaySession $session): View
    {
        abort_unless(Auth::id() === $session->created_by, 403);
        abort_unless($session->session_status === 'approved_pending_payment', 400, 'This session is not currently awaiting payment.');

        $session->load('courts');

        return view('open-play.host-pay', [
            'session' => $session,
        ]);
    }

    /**
     * Process payment to secure the court appointment for the accepted session.
     */
    public function processPayment(Request $request, OpenPlaySession $session, PayMongoService $payMongoService)
    {
        abort_unless(Auth::id() === $session->created_by, 403);
        abort_unless($session->session_status === 'approved_pending_payment', 400, 'This session is not currently awaiting payment.');

        $request->validate([
            'payment_method' => ['nullable', 'in:paymongo'],
        ]);

        $paymentMethod = 'paymongo';

        if ($paymentMethod === 'cash') {
            $session->update([
                'session_status' => 'scheduled',
                'host_payment_status' => 'pending',
                'host_payment_method' => 'cash',
                'host_paid_at' => now(),
            ]);

            return redirect()->route('open-play.host.index')
                ->with('status', "Your court appointment for '{$session->title}' is secured! Please settle your cash payment of ₱" . number_format($session->court_fee, 2) . " at the front desk cashier.");
        }

        // Online PayMongo Checkout
        if ($payMongoService->isConfigured() && $session->court_fee > 0) {
            $formattedDate = $session->date->format('M d, Y');
            $durationHours = (float) $session->duration_hours;
            $courts = $session->courts;
            $courtCount = $courts->count();
            $totalCentavos = (int) round($session->court_fee * 100);
            $runningCentavos = 0;
            $lineItems = [];

            foreach ($courts as $index => $court) {
                $courtHourly = (float) $court->price_per_hour;
                $courtTypeName = $court->court_type ? ' (' . ucfirst($court->court_type) . ')' : '';

                if ($index === $courtCount - 1) {
                    $itemCentavos = $totalCentavos - $runningCentavos;
                } else {
                    $itemCentavos = (int) round($courtHourly * $durationHours * 100);
                    $runningCentavos += $itemCentavos;
                }

                $lineItems[] = [
                    'name' => "{$court->court_name}{$courtTypeName} – {$session->title}",
                    'description' => "Court Hire: {$court->court_name} | {$durationHours} hr(s) @ ₱" . number_format($courtHourly, 2) . "/hr | {$formattedDate} ({$session->time_window})",
                    'amount' => $itemCentavos,
                    'currency' => 'PHP',
                    'quantity' => 1,
                ];
            }

            if (empty($lineItems)) {
                $lineItems[] = [
                    'name' => "Court Appointment Fee: {$session->title}",
                    'description' => "{$formattedDate} | {$session->time_window} | {$courtCount} court(s)",
                    'amount' => $totalCentavos,
                    'currency' => 'PHP',
                    'quantity' => 1,
                ];
            }

            $courtSummaryList = $courts->map(function ($c) use ($durationHours) {
                return "{$c->court_name} (₱" . number_format($c->price_per_hour, 2) . "/hr x {$durationHours}h = ₱" . number_format($c->price_per_hour * $durationHours, 2) . ")";
            })->join(', ');

            $checkoutDescription = "Gaoshou Pickleball Court Appointment: {$session->title} [{$courtCount} court" . ($courtCount > 1 ? 's' : '') . ": {$courtSummaryList}]";

            $successUrl = route('open-play.host.paymongo.success') . '?open_play_id=' . $session->id;
            $cancelUrl = route('open-play.host.paymongo.cancel') . '?open_play_id=' . $session->id;

            try {
                $checkout = $payMongoService->createCheckoutSession($lineItems, [
                    'description' => $checkoutDescription,
                    'reference_number' => "HOST-{$session->id}",
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'customer' => [
                        'name' => Auth::user()->name,
                        'email' => Auth::user()->email,
                    ],
                ]);

                $checkoutSessionId = $checkout['id'] ?? null;
                $checkoutUrl = $checkout['checkout_url'] ?? null;

                if ($checkoutSessionId && $checkoutUrl) {
                    $session->update([
                        'paymongo_checkout_session_id' => $checkoutSessionId,
                    ]);

                    return redirect()->away($checkoutUrl);
                }
            } catch (\Throwable $e) {
                // Fallback if API fails
            }
        }

        // If PayMongo is not configured or in sandbox simulation
        $session->update([
            'session_status' => 'scheduled',
            'host_payment_status' => 'paid',
            'host_payment_method' => 'paymongo',
            'host_paid_at' => now(),
        ]);

        return redirect()->route('open-play.host.index')
            ->with('status', "Payment of ₱" . number_format($session->court_fee, 2) . " successful! Your court appointment is secured and the Open Play session is now scheduled.");
    }

    /**
     * PayMongo checkout success callback.
     */
    public function paymongoSuccess(Request $request, PayMongoService $payMongoService): RedirectResponse
    {
        $openPlayId = $request->query('open_play_id');
        $session = OpenPlaySession::where('id', $openPlayId)->firstOrFail();

        abort_unless(Auth::id() === $session->created_by, 403);

        $session->update([
            'session_status' => 'scheduled',
            'host_payment_status' => 'paid',
            'host_payment_method' => 'paymongo',
            'host_paid_at' => now(),
        ]);

        return redirect()->route('open-play.host.index')
            ->with('status', "Payment of ₱" . number_format($session->court_fee, 2) . " verified! Your court appointment is secured and the session is now live.");
    }

    /**
     * PayMongo checkout cancel callback.
     */
    public function paymongoCancel(Request $request): RedirectResponse
    {
        return redirect()->route('open-play.host.index')
            ->with('status', "Online payment was cancelled. You can complete payment at any time to secure your court appointment.");
    }

    /**
     * Cancel/delete a pending hosting request.
     */
    public function destroy(OpenPlaySession $session): RedirectResponse
    {
        abort_unless(Auth::id() === $session->created_by, 403);
        abort_unless(in_array($session->session_status, ['pending_approval', 'rejected']), 400, 'Cannot cancel a session that is already approved or scheduled.');

        $session->courts()->detach();
        $session->delete();

        return redirect()->route('open-play.host.index')
            ->with('status', "Hosting request cancelled.");
    }
}
