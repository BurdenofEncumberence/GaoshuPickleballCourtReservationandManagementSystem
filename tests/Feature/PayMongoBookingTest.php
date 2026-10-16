<?php

use App\Mail\BookingReceiptMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Config::set('services.paymongo.secret_key', 'sk_test_fake_secret_key');
    Config::set('services.paymongo.public_key', 'pk_test_fake_public_key');
});

test('submitting booking with paymongo payment method creates checkout session and redirects to checkout url', function () {
    $player = User::factory()->create([
        'role' => 'player',
        'email' => 'player.paymongo@example.com',
        'name' => 'Geoff Test Player',
    ]);

    $court = Court::create([
        'court_name' => 'Arena Center',
        'price_per_hour' => 500.00,
        'court_status' => 'available',
    ]);

    $targetDate = today()->addDays(2)->toDateString();

    Http::fake([
        'https://api.paymongo.com/v1/checkout_sessions' => Http::response([
            'data' => [
                'id' => 'cs_test_session_abc123',
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/cs_test_session_abc123',
                    'status' => 'active',
                    'line_items' => [
                        [
                            'name' => 'Arena Center Reservation',
                            'amount' => 50000,
                            'currency' => 'PHP',
                            'quantity' => 1,
                        ],
                    ],
                    'payment_method_types' => ['qrph'],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($player)->post(route('bookings.store'), [
        'slots' => [
            [
                'court_id' => $court->id,
                'date' => $targetDate,
                'time_slot' => '9:00 AM - 10:00 AM',
            ],
        ],
        'payment_method' => 'paymongo',
    ]);

    $response->assertRedirect(route('paymongo.gateway.show', ['session_id' => 'cs_test_session_abc123']));

    // Verify booking is reserved as pending
    $booking = Booking::where('user_id', $player->id)->first();
    expect($booking)->not->toBeNull();
    expect($booking->booking_status)->toBe('pending');
    expect($booking->booking_type)->toBe('online');

    // Verify payment record is created with checkout_session_id
    $payment = Payment::where('booking_id', $booking->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->payment_status)->toBe('pending');
    expect($payment->checkout_session_id)->toBe('cs_test_session_abc123');
    expect((float) $payment->amount)->toBe(500.00);
});

test('paymongo gold gcash qr gateway page displays qr scan only and completing payment confirms booking', function () {
    Mail::fake();

    $player = User::factory()->create([
        'role' => 'player',
        'email' => 'gold.gateway@example.com',
        'name' => 'Gold Gateway Player',
    ]);

    $court = Court::create([
        'court_name' => 'Court Gold QR',
        'price_per_hour' => 450.00,
        'court_status' => 'available',
    ]);

    $booking = Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => today()->addDays(2)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'booking_status' => 'pending',
        'booking_type' => 'online',
    ]);

    $sessionId = 'cs_test_gold_gateway_123';
    $payment = Payment::create([
        'booking_id' => $booking->id,
        'payment_method' => 'paymongo',
        'payment_status' => 'pending',
        'amount' => 450.00,
        'ref_num' => 'PAY-GOLDQR1',
        'checkout_session_id' => $sessionId,
        'date' => today()->toDateString(),
        'time' => '10:00:00',
    ]);

    Http::fake([
        'https://api.paymongo.com/v1/payment_intents' => Http::response([
            'data' => ['id' => 'pi_test_gold_1'],
        ], 200),
        'https://api.paymongo.com/v1/payment_methods' => Http::response([
            'data' => ['id' => 'pm_test_gold_1'],
        ], 200),
        'https://api.paymongo.com/v1/payment_intents/pi_test_gold_1/attach' => Http::response([
            'data' => [
                'attributes' => [
                    'next_action' => [
                        'code' => [
                            'image_url' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $gatewayPage = $this->actingAs($player)->get(route('paymongo.gateway.show', ['session_id' => $sessionId]));
    $gatewayPage->assertOk();
    $gatewayPage->assertSee('Scan to Pay with GCash');
    $gatewayPage->assertSee('GCash QR Only');
    $gatewayPage->assertSee('₱450.00');
    $gatewayPage->assertDontSee('Online Banking');
    $gatewayPage->assertDontSee('Debit/Credit Card');

    $completeResponse = $this->actingAs($player)->post(route('paymongo.gateway.complete', ['session_id' => $sessionId]));
    $completeResponse->assertRedirect();

    $followRedirect = $this->actingAs($player)->get($completeResponse->headers->get('Location'));
    $followRedirect->assertRedirect(route('bookings.index'));

    $booking->refresh();
    $payment->refresh();

    expect($booking->booking_status)->toBe('confirmed');
    expect($payment->payment_status)->toBe('paid');
    expect($payment->payment_method)->toBe('paymongo_gcash');
});

test('paymongo success return verifies payment and marks booking confirmed and sends receipt email', function () {
    Mail::fake();

    $player = User::factory()->create([
        'role' => 'player',
        'email' => 'player.paymongo.paid@example.com',
        'name' => 'Paid Player',
    ]);

    $court = Court::create([
        'court_name' => 'Court PayMongo 1',
        'price_per_hour' => 450.00,
        'court_status' => 'available',
    ]);

    $booking = Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'booking_status' => 'pending',
        'booking_type' => 'online',
    ]);

    $refNum = 'PAY-TESTPAYM1';
    $sessionId = 'cs_test_session_verified999';

    $payment = Payment::create([
        'booking_id' => $booking->id,
        'payment_method' => 'paymongo',
        'payment_status' => 'pending',
        'amount' => 450.00,
        'ref_num' => $refNum,
        'checkout_session_id' => $sessionId,
        'date' => today()->toDateString(),
        'time' => '10:00:00',
    ]);

    Http::fake([
        "https://api.paymongo.com/v1/checkout_sessions/{$sessionId}" => Http::response([
            'data' => [
                'id' => $sessionId,
                'type' => 'checkout_session',
                'attributes' => [
                    'status' => 'paid',
                    'payments' => [
                        [
                            'id' => 'pay_test_pm_987654',
                            'type' => 'payment',
                            'attributes' => [
                                'status' => 'paid',
                                'amount' => 45000,
                                'source' => [
                                    'type' => 'qrph',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($player)->get(route('booking.paymongo.success', [
        'session_id' => $sessionId,
        'ref' => $refNum,
    ]));

    $response->assertRedirect(route('bookings.index'));

    $booking->refresh();
    $payment->refresh();

    expect($booking->booking_status)->toBe('confirmed');
    expect($payment->payment_status)->toBe('paid');
    expect($payment->paymongo_payment_id)->toBe('pay_test_pm_987654');
    expect($payment->payment_method)->toBe('paymongo_qrph');

    Mail::assertSent(BookingReceiptMail::class, function ($mail) use ($player) {
        return $mail->hasTo($player->email)
            && str_contains($mail->paymentMethod, 'QRPH');
    });
});

test('paymongo success return works when paymongo redirects with literal placeholder or ref only', function () {
    Mail::fake();

    $player = User::factory()->create([
        'role' => 'player',
        'email' => 'player.paymongo.placeholder@example.com',
        'name' => 'Placeholder Player',
    ]);

    $court = Court::create([
        'court_name' => 'Court PayMongo 2',
        'price_per_hour' => 450.00,
        'court_status' => 'available',
    ]);

    $booking = Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'booking_status' => 'pending',
        'booking_type' => 'online',
    ]);

    $refNum = 'PAY-PLACEHOLDER1';
    $realSessionId = 'cs_test_session_real_from_db';

    $payment = Payment::create([
        'booking_id' => $booking->id,
        'payment_method' => 'paymongo',
        'payment_status' => 'pending',
        'amount' => 450.00,
        'ref_num' => $refNum,
        'checkout_session_id' => $realSessionId,
        'date' => today()->toDateString(),
        'time' => '10:00:00',
    ]);

    Http::fake([
        "https://api.paymongo.com/v1/checkout_sessions/{$realSessionId}" => Http::response([
            'data' => [
                'id' => $realSessionId,
                'type' => 'checkout_session',
                'attributes' => [
                    'status' => 'paid',
                    'payments' => [
                        [
                            'id' => 'pay_test_pm_real123',
                            'type' => 'payment',
                            'attributes' => [
                                'status' => 'paid',
                                'amount' => 45000,
                                'source' => [
                                    'type' => 'gcash',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($player)->get(route('booking.paymongo.success', [
        'session_id' => '{CHECKOUT_SESSION_ID}',
        'ref' => $refNum,
    ]));

    $response->assertRedirect(route('bookings.index'));

    $booking->refresh();
    $payment->refresh();

    expect($booking->booking_status)->toBe('confirmed');
    expect($payment->payment_status)->toBe('paid');
    expect($payment->checkout_session_id)->toBe($realSessionId);
    expect($payment->paymongo_payment_id)->toBe('pay_test_pm_real123');
    expect($payment->payment_method)->toBe('paymongo_gcash');
});

test('paymongo cancel releases pending reservation slots', function () {
    $player = User::factory()->create(['role' => 'player']);
    $court = Court::create([
        'court_name' => 'Court PayMongo Cancel',
        'price_per_hour' => 500.00,
        'court_status' => 'available',
    ]);

    $booking = Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => today()->addDays(2)->toDateString(),
        'start_time' => '14:00:00',
        'end_time' => '15:00:00',
        'booking_status' => 'pending',
        'booking_type' => 'online',
    ]);

    $sessionId = 'cs_test_session_cancelled123';
    $payment = Payment::create([
        'booking_id' => $booking->id,
        'payment_method' => 'paymongo',
        'payment_status' => 'pending',
        'amount' => 500.00,
        'ref_num' => 'PAY-CANCEL1',
        'checkout_session_id' => $sessionId,
        'date' => today()->toDateString(),
        'time' => '14:00:00',
    ]);

    $response = $this->actingAs($player)->get(route('booking.paymongo.cancel', [
        'session_id' => $sessionId,
    ]));

    $response->assertRedirect(route('booking'));

    $booking->refresh();
    $payment->refresh();

    expect($booking->booking_status)->toBe('cancelled');
    expect($payment->payment_status)->toBe('failed');
});

test('cash at counter selection confirms booking with pending cash payment without contacting paymongo', function () {
    Http::preventStrayRequests();

    $player = User::factory()->create(['role' => 'player']);
    $court = Court::create([
        'court_name' => 'Court Cash Only',
        'price_per_hour' => 300.00,
        'court_status' => 'available',
    ]);

    $targetDate = today()->addDay()->toDateString();

    $response = $this->actingAs($player)->post(route('bookings.store'), [
        'slots' => [
            [
                'court_id' => $court->id,
                'date' => $targetDate,
                'time_slot' => '1:00 PM - 2:00 PM',
            ],
        ],
        'payment_method' => 'cash',
    ]);

    $response->assertRedirect(route('bookings.index'));

    $booking = Booking::where('user_id', $player->id)->first();
    expect($booking->booking_status)->toBe('confirmed');

    $payment = Payment::where('booking_id', $booking->id)->first();
    expect($payment->payment_status)->toBe('pending');
    expect($payment->payment_method)->toBe('cash');
});
