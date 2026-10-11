<?php

namespace App\Http\Controllers;

use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Services\PayMongoService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OpenPlayController extends Controller
{
    /**
     * Display a listing of upcoming Open Play & Tournament sessions.
     */
    public function index(Request $request): View
    {
        $typeFilter = $request->query('type', 'all');
        $skillFilter = $request->query('skill_level', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = OpenPlaySession::with(['courts', 'registrations'])
            ->where('date', '>=', today())
            ->whereIn('session_status', ['scheduled', 'ongoing'])
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($typeFilter !== 'all') {
            $query->where('session_type', $typeFilter);
        }

        if ($skillFilter !== 'all') {
            $query->where('skill_level', $skillFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%");
            });
        }

        $sessions = $query->paginate(9)->withQueryString();

        // Skill level options for filtering
        $dbSkills = OpenPlaySession::where('date', '>=', today())
            ->whereIn('session_status', ['scheduled', 'ongoing'])
            ->pluck('skill_level')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $availableSkills = collect(array_unique(array_merge(OpenPlaySession::SKILL_LEVELS, $dbSkills)))->values();

        return view('open-play.index', [
            'sessions' => $sessions,
            'typeFilter' => $typeFilter,
            'skillFilter' => $skillFilter,
            'search' => $search,
            'availableSkills' => $availableSkills,
        ]);
    }

    /**
     * Display the specified Open Play / Tournament session details and reservation form.
     */
    public function show(OpenPlaySession $session): View
    {
        if (! in_array($session->session_status, ['scheduled', 'ongoing', 'completed'], true)) {
            $user = Auth::user();
            abort_unless($user && ($user->id === $session->created_by || $user->isManager() || $user->isAdmin()), 404);
        }

        $session->load(['courts', 'registrations']);

        return view('open-play.show', [
            'session' => $session,
        ]);
    }

    /**
     * Store a player slot registration with strict concurrency locking and overbooking prevention.
     */
    public function store(Request $request, OpenPlaySession $session, PayMongoService $payMongoService)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'slots_count' => ['required', 'integer', 'min:1', 'max:10'],
            'payment_method' => ['required', 'string', 'in:paymongo,cash'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'slots_count.required' => 'Please select the number of player slots.',
            'slots_count.min' => 'At least 1 slot must be selected.',
            'slots_count.max' => 'You can reserve up to 10 slots per transaction.',
            'payment_method.required' => 'Please select your preferred payment method.',
        ]);

        $requestedSlots = (int) $validated['slots_count'];
        $paymentMethod = $validated['payment_method'];

        try {
            /** @var OpenPlayRegistration $registration */
            $registration = DB::transaction(function () use ($session, $validated, $requestedSlots, $user, $paymentMethod) {
                // Acquire pessimistic row lock on the session to serialize capacity verification
                $lockedSession = OpenPlaySession::where('id', $session->id)->lockForUpdate()->firstOrFail();

                if (! in_array($lockedSession->session_status, ['scheduled', 'ongoing'], true)) {
                    throw new DomainException('This session is not currently open for registrations.');
                }

                if ($lockedSession->date < today()) {
                    throw new DomainException('This session has already ended.');
                }

                // Sum all currently reserved slots (paid + pending) under lock
                $activeSlots = (int) $lockedSession->registrations()
                    ->whereIn('payment_status', ['paid', 'pending'])
                    ->lockForUpdate()
                    ->sum('slots_count');

                $availableSlots = $lockedSession->max_capacity - $activeSlots;

                if ($requestedSlots > $availableSlots) {
                    if ($availableSlots <= 0) {
                        throw new DomainException('Sorry, this session is now completely sold out.');
                    }
                    throw new DomainException("Only {$availableSlots} slot(s) remain available for this session. You requested {$requestedSlots} slot(s).");
                }

                $totalFee = round($requestedSlots * (float) $lockedSession->price_per_slot, 2);
                $refNum = 'OP-' . strtoupper(Str::random(8));

                return $lockedSession->registrations()->create([
                    'user_id' => $user?->id,
                    'slots_count' => $requestedSlots,
                    'total_fee' => $totalFee,
                    'payment_status' => 'pending',
                    'payment_method' => $paymentMethod,
                    'ref_num' => $refNum,
                    'attendance_status' => 'registered',
                    'notes' => $validated['notes'] ?? null,
                ]);
            });
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['slots_count' => $e->getMessage()]);
        }

        // Handle PayMongo Hosted Checkout if selected and configured
        $isPayMongo = ($paymentMethod === 'paymongo') && $payMongoService->isConfigured();

        if ($isPayMongo && $registration->total_fee > 0) {
            $formattedDate = $session->date->format('M d, Y');
            $sessionTypeLabel = ucfirst(str_replace('_', ' ', $session->session_type));

            $lineItems = [
                [
                    'name' => "{$session->title} Ticket ({$sessionTypeLabel})",
                    'description' => "{$formattedDate} | {$session->time_window} | {$registration->slots_count} slot(s) @ ₱{$session->price_per_slot}/slot",
                    'amount' => (int) round($registration->total_fee * 100), // in centavos
                    'currency' => 'PHP',
                    'quantity' => 1,
                ]
            ];

            $successUrl = route('open-play.paymongo.success') . '?ref=' . urlencode($registration->ref_num);
            $cancelUrl = route('open-play.paymongo.cancel') . '?ref=' . urlencode($registration->ref_num);

            try {
                $checkout = $payMongoService->createCheckoutSession($lineItems, [
                    'description' => "Gaoshou Pickleball {$sessionTypeLabel} Slot ({$registration->ref_num})",
                    'reference_number' => $registration->ref_num,
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'customer' => [
                        'name' => $registration->player_name,
                        'email' => $registration->player_email,
                        'phone' => $registration->player_phone,
                    ],
                    'payment_method_types' => [
                        'qrph',
                        'dob',
                        'paymaya',
                        'gcash',
                        'card',
                    ],
                ]);

                $checkoutSessionId = $checkout['id'] ?? null;
                $checkoutUrl = $checkout['checkout_url'] ?? null;

                if ($checkoutSessionId && $checkoutUrl) {
                    $registration->update([
                        'paymongo_checkout_session_id' => $checkoutSessionId,
                    ]);

                    return redirect()->away($checkoutUrl);
                }
            } catch (\Throwable $e) {
                report($e);
                // Mark registration as cancelled so slot lock is released if gateway fails
                $registration->update(['payment_status' => 'cancelled']);
                return back()
                    ->withInput()
                    ->withErrors(['payment_method' => 'Unable to connect to PayMongo payment gateway: ' . $e->getMessage()]);
            }
        }

        // If Cash at Counter or zero fee
        if ($registration->total_fee <= 0) {
            $registration->update(['payment_status' => 'paid']);
            return redirect()->route('bookings.index')
                ->with('status', "Registration confirmed for {$session->title}! Your reference number is {$registration->ref_num}.");
        }

        return redirect()->route('bookings.index')
            ->with('status', "Reserved {$registration->slots_count} slot(s) for {$session->title}! Please pay ₱" . number_format($registration->total_fee, 2) . " cash at the counter upon arrival (Ref: {$registration->ref_num}).");
    }

    /**
     * Handle return from PayMongo hosted checkout on successful payment.
     */
    public function paymongoSuccess(Request $request, PayMongoService $payMongoService): RedirectResponse
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

        $registration = OpenPlayRegistration::where(function ($q) use ($sessionId, $refNum) {
            if ($sessionId) {
                $q->where('paymongo_checkout_session_id', $sessionId);
            }
            if ($refNum) {
                $q->orWhere('ref_num', $refNum);
            }
        })->with('session')->first();

        if (! $registration) {
            return redirect()->route('bookings.index')
                ->with('status', 'Payment received, but no matching registration record was found.');
        }

        // Authorization check: ensure user owns this registration
        if (Auth::check() && $registration->user_id && $registration->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this registration.');
        }

        $actualSessionId = $sessionId ?: $registration->paymongo_checkout_session_id;

        // Verify status with PayMongo
        if ($actualSessionId && $payMongoService->isConfigured()) {
            try {
                $sessionData = $payMongoService->getCheckoutSession($actualSessionId);
                $isPaid = $payMongoService->isSessionPaid($sessionData);

                if ($isPaid) {
                    $details = $payMongoService->extractPaymentDetails($sessionData);
                    $registration->update([
                        'payment_status' => 'paid',
                        'paymongo_checkout_session_id' => $actualSessionId,
                        'paymongo_payment_id' => $details['payment_id'] ?? null,
                        'payment_method' => 'paymongo_' . ($details['source_type'] ?? 'online'),
                    ]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        } else {
            // In testing/simulation without gateway connection, mark paid
            $registration->update(['payment_status' => 'paid']);
        }

        return redirect()->route('bookings.index')
            ->with('status', "Payment successful! Your {$registration->slots_count} slot(s) for '{$registration->session->title}' are confirmed (Ref: {$registration->ref_num}).");
    }

    /**
     * Handle return from PayMongo hosted checkout when customer cancels.
     */
    public function paymongoCancel(Request $request): RedirectResponse
    {
        $sessionId = trim((string) $request->query('session_id', ''));
        if (str_contains($sessionId, '{')) {
            $sessionId = '';
        }
        $refNum = trim((string) $request->query('ref', ''));

        $registration = OpenPlayRegistration::where(function ($q) use ($sessionId, $refNum) {
            if ($sessionId) {
                $q->where('paymongo_checkout_session_id', $sessionId);
            }
            if ($refNum) {
                $q->orWhere('ref_num', $refNum);
            }
        })->first();

        if ($registration && $registration->payment_status === 'pending') {
            // Cancel registration to immediately release reserved slots
            $registration->update(['payment_status' => 'cancelled']);
        }

        $sessionIdTarget = $registration?->open_play_session_id;

        if ($sessionIdTarget) {
            return redirect()->route('open-play.show', $sessionIdTarget)
                ->with('status', 'Payment checkout was cancelled. Your slot reservation has been released.');
        }

        return redirect()->route('open-play.index')
            ->with('status', 'Payment checkout was cancelled. Your slot reservation has been released.');
    }
}
