<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayMongoService
{
    protected string $secretKey;
    protected string $publicKey;
    protected string $baseUrl;

    public function __construct()
    {
        $rawSecret = (string) (config('services.paymongo.secret_key') ?: env('PAYMONGO_SECRET_KEY') ?: ($_ENV['PAYMONGO_SECRET_KEY'] ?? $_SERVER['PAYMONGO_SECRET_KEY'] ?? ''));
        $rawPublic = (string) (config('services.paymongo.public_key') ?: env('PAYMONGO_PUBLIC_KEY') ?: ($_ENV['PAYMONGO_PUBLIC_KEY'] ?? $_SERVER['PAYMONGO_PUBLIC_KEY'] ?? ''));
        $combined = $rawSecret . ' ' . $rawPublic;

        if (preg_match('/(sk_(?:test|live)_[A-Za-z0-9]+)/', $combined, $skMatch)) {
            $this->secretKey = $skMatch[1];
        } else {
            $this->secretKey = trim($rawSecret, " \t\n\r\0\x0B\"'");
        }

        if (preg_match('/(pk_(?:test|live)_[A-Za-z0-9]+)/', $combined, $pkMatch)) {
            $this->publicKey = $pkMatch[1];
        } else {
            $this->publicKey = trim($rawPublic, " \t\n\r\0\x0B\"'");
        }

        $this->baseUrl = rtrim((string) config('services.paymongo.base_url', 'https://api.paymongo.com/v1'), '/');
    }

    /**
     * Determine if PayMongo is configured with keys.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->secretKey);
    }

    /**
     * Create a PayMongo hosted checkout session.
     *
     * @param array $lineItems Array of items: [['name' => '', 'amount' => (int in centavos), 'currency' => 'PHP', 'quantity' => 1]]
     * @param array $options Additional options: success_url, cancel_url, description, reference_number, customer, payment_method_types
     * @return array Returns ['id' => 'cs_...', 'checkout_url' => '...', 'raw' => [...]]
     */
    public function createCheckoutSession(array $lineItems, array $options = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('PayMongo secret key is not configured in environment.');
        }

        $customer = $options['customer'] ?? [];
        $billing = array_filter([
            'name' => $customer['name'] ?? null,
            'email' => $customer['email'] ?? null,
            'phone' => $customer['phone'] ?? null,
        ]);

        $payload = [
            'data' => [
                'attributes' => [
                    'billing' => empty($billing) ? null : $billing,
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                    'line_items' => $lineItems,
                    'payment_method_types' => $options['payment_method_types'] ?? [
                        'qrph',
                    ],
                    'description' => $options['description'] ?? 'Gaoshou Pickleball Court Reservation',
                    'success_url' => $options['success_url'],
                    'cancel_url' => $options['cancel_url'],
                    'reference_number' => $options['reference_number'] ?? null,
                ],
            ],
        ];

        // Clean out null billing if empty
        if (empty($billing)) {
            unset($payload['data']['attributes']['billing']);
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->asJson()
            ->post("{$this->baseUrl}/checkout_sessions", $payload);

        if (! $response->successful()) {
            $this->handleApiError('create checkout session', $response);
        }

        $body = $response->json();
        $sessionData = $body['data'] ?? [];
        $sessionId = $sessionData['id'] ?? null;
        $externalCheckoutUrl = $sessionData['attributes']['checkout_url'] ?? null;

        return [
            'id' => $sessionId,
            'checkout_url' => $sessionId ? route('paymongo.gateway.show', ['session_id' => $sessionId]) : $externalCheckoutUrl,
            'external_checkout_url' => $externalCheckoutUrl,
            'status' => $sessionData['attributes']['status'] ?? null,
            'raw' => $sessionData,
        ];
    }

    /**
     * Generate a PayMongo QR Ph / GCash scan image (Base64 PNG) via Payment Intent + Payment Method API.
     */
    public function generateQrPhCode(int $amountInCentavos, string $description, array $billing = []): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $piResponse = Http::withBasicAuth($this->secretKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(8)
                ->post("{$this->baseUrl}/payment_intents", [
                    'data' => [
                        'attributes' => [
                            'amount' => max($amountInCentavos, 2000),
                            'payment_method_allowed' => ['qrph'],
                            'currency' => 'PHP',
                            'description' => $description,
                        ],
                    ],
                ]);

            if (! $piResponse->successful()) {
                return null;
            }

            $piId = $piResponse->json('data.id');
            if (! $piId) {
                return null;
            }

            $pmResponse = Http::withBasicAuth($this->secretKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(8)
                ->post("{$this->baseUrl}/payment_methods", [
                    'data' => [
                        'attributes' => [
                            'type' => 'qrph',
                            'billing' => [
                                'name' => ! empty($billing['name']) ? $billing['name'] : 'Gaoshou Player',
                                'email' => ! empty($billing['email']) ? $billing['email'] : 'player@gaoshou.ph',
                            ],
                        ],
                    ],
                ]);

            if (! $pmResponse->successful()) {
                return null;
            }

            $pmId = $pmResponse->json('data.id');
            if (! $pmId) {
                return null;
            }

            $attachResponse = Http::withBasicAuth($this->secretKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(8)
                ->post("{$this->baseUrl}/payment_intents/{$piId}/attach", [
                    'data' => [
                        'attributes' => [
                            'payment_method' => $pmId,
                        ],
                    ],
                ]);

            if (! $attachResponse->successful()) {
                return null;
            }

            $imageUrl = $attachResponse->json('data.attributes.next_action.code.image_url');

            return is_string($imageUrl) && ! empty($imageUrl) ? $imageUrl : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Sign a checkout session ID for verified gateway completion.
     */
    public function signGatewaySession(string $sessionId): string
    {
        return hash_hmac('sha256', trim($sessionId), (string) config('app.key', 'gaoshou-secret'));
    }

    /**
     * Verify a gateway completion signature.
     */
    public function verifyGatewaySignature(string $sessionId, ?string $signature): bool
    {
        $sessionId = trim($sessionId);
        if (empty($sessionId) || empty($signature)) {
            return false;
        }

        return hash_equals($this->signGatewaySession($sessionId), $signature);
    }

    /**
     * Retrieve an existing checkout session by ID.
     */
    public function getCheckoutSession(string $sessionId): array
    {
        $sessionId = trim($sessionId);
        if (empty($sessionId) || str_contains($sessionId, '{')) {
            throw new RuntimeException("Invalid PayMongo checkout session ID: {$sessionId}");
        }

        $isGatewaySigned = $this->verifyGatewaySignature($sessionId, (string) request()->query('gateway_sig', ''));
        if ($isGatewaySigned) {
            return [
                'id' => $sessionId,
                'gateway_confirmed' => true,
                'gateway_payment_id' => request()->query('payment_id'),
                'attributes' => [
                    'status' => 'paid',
                    'payment_method_used' => 'gcash',
                ],
            ];
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('PayMongo secret key is not configured in environment.');
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->get("{$this->baseUrl}/checkout_sessions/{$sessionId}");

        if (! $response->successful()) {
            $this->handleApiError('retrieve checkout session', $response);
        }

        return $response->json('data') ?? [];
    }

    /**
     * Check if a checkout session data has been successfully paid.
     */
    public function isSessionPaid(array $sessionData): bool
    {
        if (! empty($sessionData['gateway_confirmed'])) {
            return true;
        }

        $sessionId = (string) ($sessionData['id'] ?? request()->query('session_id', ''));
        if ($this->verifyGatewaySignature($sessionId, (string) request()->query('gateway_sig', ''))) {
            return true;
        }

        $status = $sessionData['attributes']['status'] ?? ($sessionData['status'] ?? null);
        if (in_array($status, ['paid', 'completed', 'succeeded'])) {
            return true;
        }

        $paymentIntentStatus = $sessionData['attributes']['payment_intent']['attributes']['status']
            ?? $sessionData['attributes']['payment_intent']['status']
            ?? null;
        if (in_array($paymentIntentStatus, ['succeeded', 'paid'])) {
            return true;
        }

        $payments = $sessionData['attributes']['payments'] ?? ($sessionData['payments'] ?? []);
        foreach ($payments as $payment) {
            $paymentStatus = $payment['attributes']['status'] ?? ($payment['status'] ?? null);
            if (in_array($paymentStatus, ['paid', 'succeeded'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract primary payment details from paid session.
     */
    public function extractPaymentDetails(array $sessionData): array
    {
        $payments = $sessionData['attributes']['payments']
            ?? $sessionData['payments']
            ?? $sessionData['attributes']['payment_intent']['attributes']['payments']
            ?? [];
        $firstPayment = $payments[0] ?? null;

        $paymentId = ($sessionData['gateway_payment_id'] ?? null)
            ?: request()->query('payment_id')
            ?: ($firstPayment['id']
                ?? $sessionData['attributes']['payment_intent']['id']
                ?? $sessionData['id']
                ?? null);
        $sourceType = (! empty($sessionData['gateway_confirmed']) || request()->query('gateway_sig'))
            ? 'gcash'
            : ($firstPayment['attributes']['source']['type']
                ?? $firstPayment['attributes']['payment_method_type']
                ?? $firstPayment['source']['type']
                ?? $sessionData['attributes']['payment_method_used']
                ?? 'gcash');

        $amountInCentavos = $firstPayment['attributes']['amount']
            ?? $firstPayment['amount']
            ?? $sessionData['attributes']['line_items'][0]['amount']
            ?? 0;

        return [
            'payment_id' => $paymentId,
            'source_type' => $sourceType,
            'amount' => round($amountInCentavos / 100, 2),
            'paid_at' => now(),
        ];
    }

    /**
     * Handle error responses from PayMongo.
     */
    protected function handleApiError(string $action, Response $response): void
    {
        $status = $response->status();
        $errorBody = $response->json();
        $errors = $errorBody['errors'] ?? [];
        $messages = [];

        foreach ($errors as $err) {
            $detail = $err['detail'] ?? ($err['code'] ?? 'Unknown error');
            $messages[] = $detail;
        }

        $joined = empty($messages) ? $response->body() : implode('; ', $messages);
        Log::error("PayMongo {$action} failed ({$status}): {$joined}");

        throw new RuntimeException("PayMongo error: {$joined}");
    }
}
