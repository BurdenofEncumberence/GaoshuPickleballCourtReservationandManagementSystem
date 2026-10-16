<?php

namespace App\Http\Controllers;

use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\Payment;
use App\Services\PayMongoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayMongoGatewayController extends Controller
{
    /**
     * Render the Gold-themed PayMongo GCash QR Scanning Payment Gateway page.
     */
    public function show(Request $request, string $sessionId, PayMongoService $payMongoService): View
    {
        $context = $this->resolveCheckoutContext($sessionId, $payMongoService);

        $qrImageBase64 = null;
        if ($context['amount'] > 0) {
            $qrImageBase64 = $payMongoService->generateQrPhCode(
                (int) round($context['amount'] * 100),
                $context['description'],
                [
                    'name' => $context['customer_name'],
                    'email' => $context['customer_email'],
                ]
            );
        }

        return view('paymongo.checkout', array_merge($context, [
            'sessionId' => $sessionId,
            'qrImageBase64' => $qrImageBase64,
        ]));
    }

    /**
     * Confirm and complete the GCash QR payment on the Gold Gateway page
     * and redirect to the appropriate flow's success callback with a cryptographic signature.
     */
    public function complete(Request $request, string $sessionId, PayMongoService $payMongoService): RedirectResponse
    {
        $context = $this->resolveCheckoutContext($sessionId, $payMongoService);

        $signature = $payMongoService->signGatewaySession($sessionId);
        $paymentId = 'pay_' . substr(md5($sessionId . ':gcash'), 0, 20);

        $separator = str_contains($context['success_url'], '?') ? '&' : '?';
        $redirectUrl = $context['success_url'] . $separator . http_build_query([
            'session_id' => $sessionId,
            'ref' => $context['reference_number'],
            'gateway_sig' => $signature,
            'payment_id' => $paymentId,
        ]);

        return redirect()->to($redirectUrl);
    }

    /**
     * Resolve transaction context from database records (Player Booking, Staff Walk-In,
     * Open Play Registration, or Open Play Host Payment) with PayMongo API fallback.
     *
     * @return array{
     *     amount: float,
     *     reference_number: string,
     *     description: string,
     *     customer_name: string,
     *     customer_email: string,
     *     line_items: array<int, array{name: string, description: string, amount: float, quantity: int}>,
     *     success_url: string,
     *     cancel_url: string
     * }
     */
    private function resolveCheckoutContext(string $sessionId, PayMongoService $payMongoService): array
    {
        $user = Auth::user();
        $customerName = $user?->name ?? 'Gaoshou Player';
        $customerEmail = $user?->email ?? 'player@gaoshou.ph';

        // 1. Check Court Booking / Staff Walk-In Payments
        $payments = Payment::with(['booking.court', 'booking.user'])
            ->where('checkout_session_id', $sessionId)
            ->orderBy('booking_id')
            ->get();

        if ($payments->isNotEmpty()) {
            $bookings = $payments->map(fn ($p) => $p->booking)->filter()->values();
            $bookingIds = $bookings->pluck('id')->all();
            $referenceNumber = count($bookingIds) === 1
                ? "BOOKING-{$bookingIds[0]}"
                : 'BOOKINGS-' . implode('-', $bookingIds);

            $isWalkIn = $bookings->contains(fn ($b) => (bool) $b->is_walk_in) || ($user && $user->hasRole('staff'));
            $firstBooking = $bookings->first();
            if ($firstBooking) {
                $customerName = $firstBooking->walk_in_customer_name ?: ($firstBooking->user?->name ?? $customerName);
                $customerEmail = $firstBooking->user?->email ?? $customerEmail;
            }

            $lineItems = [];
            $totalAmount = 0.0;

            foreach ($payments as $payment) {
                $booking = $payment->booking;
                $courtName = $booking?->court?->court_name ?? 'Pickleball Court';
                $dateFormatted = $booking?->date ? Carbon::parse($booking->date)->format('D, M j, Y') : '';
                $timeFormatted = $booking?->start_time && $booking?->end_time
                    ? Carbon::parse($booking->start_time)->format('g:i A') . ' - ' . Carbon::parse($booking->end_time)->format('g:i A')
                    : '';

                $amt = (float) $payment->amount;
                $totalAmount += $amt;

                $lineItems[] = [
                    'name' => "{$courtName} Reservation",
                    'description' => trim("{$dateFormatted} · {$timeFormatted}", ' ·'),
                    'amount' => $amt,
                    'quantity' => 1,
                ];
            }

            $successRoute = $isWalkIn ? 'staff.walkin.paymongo.success' : 'booking.paymongo.success';
            $cancelRoute = $isWalkIn ? 'staff.walkin.paymongo.cancel' : 'booking.paymongo.cancel';

            return [
                'amount' => round($totalAmount, 2),
                'reference_number' => $referenceNumber,
                'description' => "Gaoshou Pickleball Court Reservation ({$referenceNumber})",
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'line_items' => $lineItems,
                'success_url' => route($successRoute, ['session_id' => $sessionId, 'ref' => $referenceNumber]),
                'cancel_url' => route($cancelRoute, ['session_id' => $sessionId, 'ref' => $referenceNumber]),
            ];
        }

        // 2. Check Open Play Player Registration
        $registration = OpenPlayRegistration::with(['session', 'user'])
            ->where('paymongo_checkout_session_id', $sessionId)
            ->first();

        if ($registration) {
            $opSession = $registration->session;
            $referenceNumber = "OP-{$registration->id}";
            $amount = (float) $registration->total_amount;
            $slotsCount = (int) $registration->slots_count;
            $sessionTitle = $opSession?->title ?? 'Open Play Session';
            $dateStr = $opSession?->date ? Carbon::parse($opSession->date)->format('D, M j, Y') : '';

            return [
                'amount' => round($amount, 2),
                'reference_number' => $referenceNumber,
                'description' => "Gaoshou Open Play Registration: {$sessionTitle} ({$referenceNumber})",
                'customer_name' => $registration->user?->name ?? $customerName,
                'customer_email' => $registration->user?->email ?? $customerEmail,
                'line_items' => [
                    [
                        'name' => "Open Play: {$sessionTitle}",
                        'description' => trim("{$dateStr} · {$slotsCount} slot(s)", ' ·'),
                        'amount' => round($amount, 2),
                        'quantity' => 1,
                    ],
                ],
                'success_url' => route('open-play.paymongo.success', [
                    'session_id' => $sessionId,
                    'ref' => $referenceNumber,
                    'reg_id' => $registration->id,
                ]),
                'cancel_url' => route('open-play.paymongo.cancel', [
                    'session_id' => $sessionId,
                    'ref' => $referenceNumber,
                    'reg_id' => $registration->id,
                ]),
            ];
        }

        // 3. Check Open Play Host Court Hire Payment
        $hostSession = OpenPlaySession::with(['courts', 'creator'])
            ->where('paymongo_checkout_session_id', $sessionId)
            ->first();

        if ($hostSession) {
            $referenceNumber = "HOST-{$hostSession->id}";
            $amount = (float) $hostSession->court_fee;
            $courtNames = $hostSession->courts->pluck('court_name')->join(', ') ?: 'Assigned Courts';
            $dateStr = $hostSession->date ? Carbon::parse($hostSession->date)->format('D, M j, Y') : '';

            return [
                'amount' => round($amount, 2),
                'reference_number' => $referenceNumber,
                'description' => "Gaoshou Open Play Host Court Fee: {$hostSession->title} ({$referenceNumber})",
                'customer_name' => $hostSession->creator?->name ?? $customerName,
                'customer_email' => $hostSession->creator?->email ?? $customerEmail,
                'line_items' => [
                    [
                        'name' => "Host Court Fee: {$hostSession->title}",
                        'description' => trim("{$courtNames} · {$dateStr}", ' ·'),
                        'amount' => round($amount, 2),
                        'quantity' => 1,
                    ],
                ],
                'success_url' => route('open-play.host.paymongo.success', [
                    'open_play_id' => $hostSession->id,
                    'session_id' => $sessionId,
                    'ref' => $referenceNumber,
                ]),
                'cancel_url' => route('open-play.host.paymongo.cancel', [
                    'open_play_id' => $hostSession->id,
                    'session_id' => $sessionId,
                    'ref' => $referenceNumber,
                ]),
            ];
        }

        // 4. Fallback: Query PayMongo Checkout Session directly
        $remoteSession = $payMongoService->getCheckoutSession($sessionId) ?? [];
        $attributes = $remoteSession['attributes'] ?? [];
        $referenceNumber = (string) ($attributes['reference_number'] ?? $requestReference = request()->query('ref', 'GS-' . strtoupper(substr($sessionId, -6))));
        $remoteItems = $attributes['line_items'] ?? [];
        $lineItems = [];
        $totalAmount = 0.0;

        foreach ($remoteItems as $item) {
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $amt = ((float) ($item['amount'] ?? 0) / 100) * $qty;
            $totalAmount += $amt;
            $lineItems[] = [
                'name' => (string) ($item['name'] ?? 'Court Reservation'),
                'description' => (string) ($item['description'] ?? 'Gaoshou Pickleball'),
                'amount' => round($amt, 2),
                'quantity' => $qty,
            ];
        }

        if (empty($lineItems)) {
            $totalAmount = 300.00;
            $lineItems[] = [
                'name' => 'Gaoshou Pickleball Reservation',
                'description' => "Reference: {$referenceNumber}",
                'amount' => $totalAmount,
                'quantity' => 1,
            ];
        }

        return [
            'amount' => round($totalAmount, 2),
            'reference_number' => $referenceNumber,
            'description' => (string) ($attributes['description'] ?? "Gaoshou Pickleball ({$referenceNumber})"),
            'customer_name' => (string) ($attributes['billing']['name'] ?? $customerName),
            'customer_email' => (string) ($attributes['billing']['email'] ?? $customerEmail),
            'line_items' => $lineItems,
            'success_url' => (string) ($attributes['success_url'] ?? route('booking.paymongo.success', ['session_id' => $sessionId, 'ref' => $referenceNumber])),
            'cancel_url' => (string) ($attributes['cancel_url'] ?? route('booking.paymongo.cancel', ['session_id' => $sessionId, 'ref' => $referenceNumber])),
        ];
    }
}
