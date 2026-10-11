<?php

use App\Mail\BookingReceiptMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

test('staff can view the walk-in booking creation form', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $court = Court::create([
        'court_name' => 'Center Court',
        'price_per_hour' => 500,
        'court_status' => 'available',
    ]);

    $response = $this->actingAs($staff)->get(route('staff.walkin.create'));

    $response->assertOk();
    $response->assertSee('Walk-in Court Booking');
    $response->assertSee('Center Court');
    $response->assertSee('8:00 AM - 9:00 AM');
});

test('players and guests are blocked from accessing the staff walk-in portal', function () {
    $player = User::factory()->create(['role' => 'player']);

    // Guest redirected to login
    $this->get(route('staff.walkin.create'))->assertRedirect(route('login'));

    // Player blocked with 403 Forbidden
    $this->actingAs($player)
        ->get(route('staff.walkin.create'))
        ->assertForbidden();

    $this->actingAs($player)
        ->post(route('staff.walkin.store'), [])
        ->assertForbidden();
});

test('staff can create a walk-in booking for a guest customer with cash payment', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $court = Court::create([
        'court_name' => 'Court 1',
        'price_per_hour' => 450,
        'court_status' => 'available',
    ]);

    $response = $this->actingAs($staff)->post(route('staff.walkin.store'), [
        'first_name' => 'juan',
        'middle_name' => 'carlos',
        'last_name' => 'delos reyes',
        'date' => today()->toDateString(),
        'court_id' => $court->id,
        'time_slot' => '10:00 AM - 11:00 AM',
        'payment_method' => 'cash',
        'attendance_status' => 'show',
    ]);

    $response->assertRedirect(route('staff.today'));
    $response->assertSessionHas('status');

    // Auto-created player user has title-cased name
    $user = User::where('last_name', 'Delos Reyes')->first();
    expect($user)->not->toBeNull();
    expect($user->first_name)->toBe('Juan');
    expect($user->middle_name)->toBe('Carlos');
    expect($user->name)->toBe('Juan Carlos Delos Reyes');

    // Booking created with 'show' status
    $booking = Booking::where('user_id', $user->id)->first();
    expect($booking)->not->toBeNull();
    expect($booking->court_id)->toBe($court->id);
    expect($booking->booking_status)->toBe('show');
    expect($booking->start_time)->toBe('10:00:00');
    expect($booking->end_time)->toBe('11:00:00');

    // Paid payment created with Cash method
    $payment = Payment::where('booking_id', $booking->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->payment_method)->toBe('cash');
    expect($payment->payment_status)->toBe('paid');
    expect((float) $payment->amount)->toBe(450.00);

    // Verify it appears on today's run-sheet
    $todayResponse = $this->actingAs($staff)->get(route('staff.today'));
    $todayResponse->assertSee('Juan Carlos Delos Reyes');
    $todayResponse->assertSee('Court 1');
});

test('staff can book a walk-in for an existing registered player by email', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $player = User::factory()->create([
        'role' => 'player',
        'email' => 'regular.player@example.com',
        'first_name' => 'Regular',
        'last_name' => 'Player',
        'name' => 'Regular Player',
    ]);
    $court = Court::create([
        'court_name' => 'Court 2',
        'price_per_hour' => 600,
        'court_status' => 'available',
    ]);

    $response = $this->actingAs($staff)->post(route('staff.walkin.store'), [
        'first_name' => 'Regular',
        'last_name' => 'Player',
        'email' => 'regular.player@example.com',
        'date' => today()->toDateString(),
        'court_id' => $court->id,
        'time_slot' => '2:00 PM - 3:00 PM',
        'payment_method' => 'gcash',
        'attendance_status' => 'confirmed',
        'ref_num' => 'GCASH-987654321',
    ]);

    $response->assertRedirect(route('staff.today'));

    // Reused existing user
    $booking = Booking::where('user_id', $player->id)->first();
    expect($booking)->not->toBeNull();
    expect($booking->booking_status)->toBe('confirmed');

    $payment = Payment::where('booking_id', $booking->id)->first();
    expect($payment->payment_method)->toBe('gcash');
    expect($payment->ref_num)->toBe('GCASH-987654321');
    expect((float) $payment->amount)->toBe(600.00);
});

test('staff cannot book a slot that is already booked', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $existingPlayer = User::factory()->create(['role' => 'player']);
    $court = Court::create([
        'court_name' => 'Court 3',
        'price_per_hour' => 500,
        'court_status' => 'available',
    ]);

    Booking::create([
        'user_id' => $existingPlayer->id,
        'court_id' => $court->id,
        'date' => today()->toDateString(),
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
        'booking_status' => 'confirmed',
    ]);

    $response = $this->actingAs($staff)
        ->from(route('staff.walkin.create'))
        ->post(route('staff.walkin.store'), [
            'first_name' => 'Walkin',
            'last_name' => 'Customer',
            'date' => today()->toDateString(),
            'court_id' => $court->id,
            'time_slot' => '8:00 AM - 9:00 AM',
            'payment_method' => 'cash',
            'attendance_status' => 'show',
        ]);

    $response->assertRedirect(route('staff.walkin.create'));
    $response->assertSessionHasErrors(['time_slot']);
});

test('staff can create a walk-in booking with paymongo payment method and is redirected to checkout url', function () {
    Config::set('services.paymongo.secret_key', 'sk_test_fake_secret_key');
    Config::set('services.paymongo.public_key', 'pk_test_fake_public_key');

    $staff = User::factory()->create(['role' => 'staff']);
    $court = Court::create([
        'court_name' => 'Court PayMongo WalkIn',
        'price_per_hour' => 500,
        'court_status' => 'available',
    ]);

    Http::fake([
        'https://api.paymongo.com/v1/checkout_sessions' => Http::response([
            'data' => [
                'id' => 'cs_test_walkin_session_123',
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/cs_test_walkin_session_123',
                    'status' => 'active',
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($staff)->post(route('staff.walkin.store'), [
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'email' => 'mario.rossi@example.com',
        'phone' => '09171112233',
        'date' => today()->toDateString(),
        'court_id' => $court->id,
        'time_slot' => '10:00 AM - 11:00 AM',
        'payment_method' => 'paymongo',
        'attendance_status' => 'show',
    ]);

    $response->assertRedirect('https://checkout.paymongo.com/cs_test_walkin_session_123');

    $booking = Booking::where('court_id', $court->id)->first();
    expect($booking)->not->toBeNull();
    expect($booking->booking_status)->toBe('pending');
    expect($booking->isWalkIn())->toBeTrue();

    $payment = Payment::where('booking_id', $booking->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->payment_method)->toBe('paymongo');
    expect($payment->payment_status)->toBe('pending');
    expect($payment->checkout_session_id)->toBe('cs_test_walkin_session_123');
    expect((float) $payment->amount)->toBe(500.00);
});

test('staff walk-in paymongo success callback confirms booking and payment and sends receipt email', function () {
    Config::set('services.paymongo.secret_key', 'sk_test_fake_secret_key');
    Config::set('services.paymongo.public_key', 'pk_test_fake_public_key');
    Mail::fake();

    $staff = User::factory()->create(['role' => 'staff']);
    $player = User::factory()->create([
        'role' => 'player',
        'email' => 'walkin.player@example.com',
        'name' => 'Walkin Customer',
    ]);

    $court = Court::create([
        'court_name' => 'Court 4',
        'price_per_hour' => 500,
        'court_status' => 'available',
    ]);

    $booking = Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => today()->toDateString(),
        'start_time' => '14:00:00',
        'end_time' => '15:00:00',
        'booking_status' => 'pending',
        'booking_type' => 'walk_in',
    ]);

    $payment = Payment::create([
        'booking_id' => $booking->id,
        'payment_method' => 'paymongo',
        'payment_status' => 'pending',
        'amount' => 500.00,
        'ref_num' => 'WALK-TEST1234',
        'checkout_session_id' => 'cs_test_success_789',
        'date' => today()->toDateString(),
        'time' => '14:00:00',
    ]);

    Http::fake([
        'https://api.paymongo.com/v1/checkout_sessions/cs_test_success_789' => Http::response([
            'data' => [
                'id' => 'cs_test_success_789',
                'type' => 'checkout_session',
                'attributes' => [
                    'status' => 'paid',
                    'payments' => [
                        [
                            'id' => 'pay_walkin_pm_987654',
                            'attributes' => [
                                'status' => 'paid',
                                'amount' => 50000,
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

    $response = $this->actingAs($staff)->get(route('staff.walkin.paymongo.success', [
        'session_id' => 'cs_test_success_789',
        'ref' => 'WALK-TEST1234',
    ]));

    $response->assertRedirect(route('staff.today'));
    $response->assertSessionHas('status');

    $booking->refresh();
    expect($booking->booking_status)->toBe('show');

    $payment->refresh();
    expect($payment->payment_status)->toBe('paid');
    expect($payment->paymongo_payment_id)->toBe('pay_walkin_pm_987654');
    expect($payment->payment_method)->toBe('paymongo_qrph');

    Mail::assertSent(BookingReceiptMail::class, function ($mail) {
        return $mail->hasTo('walkin.player@example.com');
    });
});

test('staff walk-in paymongo cancel callback releases pending booking slot', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $player = User::factory()->create(['role' => 'player']);
    $court = Court::create([
        'court_name' => 'Court 5',
        'price_per_hour' => 500,
        'court_status' => 'available',
    ]);

    $booking = Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => today()->toDateString(),
        'start_time' => '16:00:00',
        'end_time' => '17:00:00',
        'booking_status' => 'pending',
        'booking_type' => 'walk_in',
    ]);

    $payment = Payment::create([
        'booking_id' => $booking->id,
        'payment_method' => 'paymongo',
        'payment_status' => 'pending',
        'amount' => 500.00,
        'ref_num' => 'WALK-CANCEL123',
        'checkout_session_id' => 'cs_test_cancel_999',
        'date' => today()->toDateString(),
        'time' => '16:00:00',
    ]);

    $response = $this->actingAs($staff)->get(route('staff.walkin.paymongo.cancel', [
        'session_id' => 'cs_test_cancel_999',
        'ref' => 'WALK-CANCEL123',
    ]));

    $response->assertRedirect(route('staff.walkin.create'));
    $response->assertSessionHas('status');

    $booking->refresh();
    $payment->refresh();

    expect($booking->booking_status)->toBe('cancelled');
    expect($payment->payment_status)->toBe('failed');
});

test('staff walk-in with cash tenders more than amount due and records change correctly with receipt print prompt', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $court = Court::create([
        'court_name' => 'Court A',
        'price_per_hour' => 400,
        'court_status' => 'available',
    ]);

    $response = $this->actingAs($staff)->post(route('staff.walkin.store'), [
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'date' => today()->toDateString(),
        'court_id' => $court->id,
        'time_slot' => '9:00 AM - 10:00 AM',
        'payment_method' => 'cash',
        'cash_tendered' => '1000.00',
        'attendance_status' => 'show',
    ]);

    $response->assertRedirect(route('staff.today'));
    $response->assertSessionHas('print_receipt_booking_id');
    $response->assertSessionHas('receipt_cash_tendered', 1000.00);
    $response->assertSessionHas('receipt_change_amount', 600.00);

    $booking = Booking::where('court_id', $court->id)->latest()->first();
    $payment = Payment::where('booking_id', $booking->id)->first();

    expect((float) $payment->amount)->toBe(400.00);
    expect((float) $payment->cash_tendered)->toBe(1000.00);
    expect((float) $payment->change_amount)->toBe(600.00);

    // Verify receipt page shows cash breakdown and non-refundable badge
    $receiptResponse = $this->actingAs($staff)->get(route('bookings.receipt', ['booking' => $booking->id]));
    $receiptResponse->assertOk();
    $receiptResponse->assertSee('1,000.00');
    $receiptResponse->assertSee('600.00');
    $receiptResponse->assertSee('Non-Refundable Transaction');

    // Verify receipt JSON endpoint returns cash tendered and change
    $jsonResponse = $this->actingAs($staff)->getJson(route('bookings.receipt', ['booking' => $booking->id]));
    $jsonResponse->assertOk();
    $jsonResponse->assertJsonPath('cash_tendered', 1000);
    $jsonResponse->assertJsonPath('change_amount', 600);
});

test('staff walk-in with cash tenders less than amount due fails validation', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $court = Court::create([
        'court_name' => 'Court B',
        'price_per_hour' => 500,
        'court_status' => 'available',
    ]);

    $response = $this->actingAs($staff)
        ->from(route('staff.walkin.create'))
        ->post(route('staff.walkin.store'), [
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
            'date' => today()->toDateString(),
            'court_id' => $court->id,
            'time_slot' => '11:00 AM - 12:00 PM',
            'payment_method' => 'cash',
            'cash_tendered' => '300.00',
            'attendance_status' => 'show',
        ]);

    $response->assertRedirect(route('staff.walkin.create'));
    $response->assertSessionHasErrors(['cash_tendered']);

    // Ensure no booking or payment was created
    expect(Booking::where('court_id', $court->id)->count())->toBe(0);
});

test('staff schedule matrix displays booked slots as Booked and provides customer and payment details on click', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $player = User::factory()->create([
        'role' => 'player',
        'name' => 'Carlos Alcaraz',
        'email' => 'carlos@example.com',
    ]);

    $court = Court::create([
        'court_name' => 'Court 1',
        'size' => 'Regular (13.41m x 6.10m)',
        'price_per_hour' => 500,
        'court_status' => 'available',
    ]);

    $booking = Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => today()->toDateString(),
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
        'booking_status' => 'confirmed',
        'booking_type' => 'online',
    ]);

    Payment::create([
        'booking_id' => $booking->id,
        'payment_method' => 'paymongo_gcash',
        'payment_status' => 'paid',
        'amount' => 525.00,
        'ref_num' => 'PM-GCASH-9988',
        'date' => today()->toDateString(),
        'time' => '08:00:00',
    ]);

    $response = $this->actingAs($staff)->get(route('staff.today'));
    $response->assertOk();
    $response->assertSee('Court Schedule Matrix');
    $response->assertSee('8:00 AM - 9:00 AM');
    $response->assertSee('Booked');

    $bookedSlotDetails = $response->viewData('bookedSlotDetails');
    expect($bookedSlotDetails)->toHaveKey($court->id);
    expect($bookedSlotDetails[$court->id])->toHaveKey('8:00 AM - 9:00 AM');

    $slotDetail = $bookedSlotDetails[$court->id]['8:00 AM - 9:00 AM'];
    expect($slotDetail['customer_name'])->toBe('Carlos Alcaraz');
    expect($slotDetail['customer_email'])->toBe('carlos@example.com');
    expect($slotDetail['payment_amount'])->toBe(525.00);
    expect($slotDetail['payment_status'])->toBe('PAID');
    expect($slotDetail['payment_ref'])->toBe('PM-GCASH-9988');
});


