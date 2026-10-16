<?php

use App\Models\Court;
use App\Models\OpenPlaySession;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->court1 = Court::create([
        'court_name' => 'Court Alpha',
        'price_per_hour' => 300.00,
        'court_status' => 'available',
        'court_type' => 'regular',
    ]);

    $this->court2 = Court::create([
        'court_name' => 'Court Beta',
        'price_per_hour' => 200.00,
        'court_status' => 'available',
        'court_type' => 'junior',
    ]);
});

test('player can view open play host form', function () {
    $player = User::factory()->create(['role' => 'player']);

    $response = $this->actingAs($player)->get(route('open-play.host.create'));

    $response->assertOk();
    $response->assertSee('Host an Open Play Session');
});

test('player can submit open play host request which starts in pending_approval status', function () {
    $player = User::factory()->create(['role' => 'player']);

    $sessionDate = today()->addDays(2)->toDateString();

    $response = $this->actingAs($player)->post(route('open-play.host.store'), [
        'title' => 'Sunday Social Open Play',
        'session_type' => 'open_play',
        'date' => $sessionDate,
        'start_time' => '17:00',
        'end_time' => '19:00',
        'allocated_courts' => [$this->court1->id, $this->court2->id],
        'max_capacity' => 12,
        'skill_level' => 'All Levels',
        'price_per_slot' => 120.00,
        'details' => 'Paddle stacking rotation pool across Alpha and Beta.',
    ]);

    $response->assertRedirect(route('open-play.host.index'));
    $response->assertSessionHas('host_request_submitted');
    $response->assertSessionHas('status');

    $session = OpenPlaySession::where('title', 'Sunday Social Open Play')->first();
    expect($session)->not->toBeNull();
    expect($session->session_status)->toBe('pending_approval');
    expect($session->host_payment_status)->toBe('unpaid');
    expect($session->created_by)->toBe($player->id);
    expect($session->courts)->toHaveCount(2);
    // Court Alpha is 300/hr, Beta is 200/hr = 500/hr * 2 hrs = 1000.00 court_fee
    expect((float) $session->court_fee)->toBe(1000.00);

    // Follow redirect to ensure confirmation message and modal are rendered
    $followResponse = $this->actingAs($player)->get(route('open-play.host.index'));
    $followResponse->assertOk();
    $followResponse->assertSee('Hosting Request Submitted!');
    $followResponse->assertSee('Sunday Social Open Play');
    $followResponse->assertSee('Pending Manager Approval');
    $followResponse->assertSee('Pay Court Fee to Secure Reservation');
});

test('player strictly cannot host a tournament and validation fails', function () {
    $player = User::factory()->create(['role' => 'player']);

    $sessionDate = today()->addDays(2)->toDateString();

    $response = $this->actingAs($player)->post(route('open-play.host.store'), [
        'title' => 'Illegal Player Tournament',
        'session_type' => 'tournament',
        'date' => $sessionDate,
        'start_time' => '17:00',
        'end_time' => '19:00',
        'allocated_courts' => [$this->court1->id],
        'max_capacity' => 16,
        'skill_level' => 'Intermediate (3.0 - 3.5)',
        'price_per_slot' => 200.00,
    ]);

    $response->assertSessionHasErrors(['session_type']);
    expect(OpenPlaySession::where('title', 'Illegal Player Tournament')->exists())->toBeFalse();
});

test('manager can accept host request which moves status to approved_pending_payment', function () {
    $player = User::factory()->create(['role' => 'player']);
    $manager = User::factory()->create(['role' => 'manager']);

    $session = OpenPlaySession::create([
        'title' => 'Community Gathering',
        'session_type' => 'open_play',
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '20:00',
        'allocated_courts' => [$this->court1->id],
        'max_capacity' => 10,
        'skill_level' => 'All Levels',
        'price_per_slot' => 150.00,
        'court_fee' => 600.00,
        'session_status' => 'pending_approval',
        'host_payment_status' => 'unpaid',
        'created_by' => $player->id,
    ]);
    $session->courts()->sync([$this->court1->id]);

    $response = $this->actingAs($manager)->post(route('admin.open-play.accept', $session));

    $response->assertRedirect();
    $session->refresh();
    expect($session->session_status)->toBe('approved_pending_payment');
});

test('admin cannot accept or reject host request and is forbidden', function () {
    $player = User::factory()->create(['role' => 'player']);
    $admin = User::factory()->create(['role' => 'admin']);

    $session = OpenPlaySession::create([
        'title' => 'Pending Request',
        'session_type' => 'open_play',
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '20:00',
        'max_capacity' => 10,
        'skill_level' => 'All Levels',
        'price_per_slot' => 150.00,
        'court_fee' => 600.00,
        'session_status' => 'pending_approval',
        'host_payment_status' => 'unpaid',
        'created_by' => $player->id,
    ]);
    $session->courts()->sync([$this->court1->id]);

    $this->actingAs($admin)->post(route('admin.open-play.accept', $session))->assertForbidden();
    $this->actingAs($admin)->post(route('admin.open-play.reject', $session))->assertForbidden();

    $session->refresh();
    expect($session->session_status)->toBe('pending_approval');
});

test('manager can reject host request with a note', function () {
    $player = User::factory()->create(['role' => 'player']);
    $manager = User::factory()->create(['role' => 'manager']);

    $session = OpenPlaySession::create([
        'title' => 'Request To Reject',
        'session_type' => 'open_play',
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '20:00',
        'max_capacity' => 10,
        'skill_level' => 'All Levels',
        'price_per_slot' => 150.00,
        'court_fee' => 600.00,
        'session_status' => 'pending_approval',
        'host_payment_status' => 'unpaid',
        'created_by' => $player->id,
    ]);
    $session->courts()->sync([$this->court1->id]);

    $response = $this->actingAs($manager)->post(route('admin.open-play.reject', $session), [
        'manager_note' => 'Courts are under scheduled lighting maintenance.',
    ]);

    $response->assertRedirect();
    $session->refresh();
    expect($session->session_status)->toBe('rejected');
    expect($session->manager_note)->toBe('Courts are under scheduled lighting maintenance.');
});

test('host player pays via paymongo to secure the reservation and schedule courts', function () {
    $player = User::factory()->create(['role' => 'player']);

    $session = OpenPlaySession::create([
        'title' => 'Approved Session Awaiting Payment',
        'session_type' => 'open_play',
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '20:00',
        'max_capacity' => 10,
        'skill_level' => 'All Levels',
        'price_per_slot' => 150.00,
        'court_fee' => 600.00,
        'session_status' => 'approved_pending_payment',
        'host_payment_status' => 'unpaid',
        'created_by' => $player->id,
    ]);
    $session->courts()->sync([$this->court1->id]);

    // Player visits payment view
    $this->actingAs($player)->get(route('open-play.host.pay.show', $session))
        ->assertOk()
        ->assertSee('Secure Court Reservation');

    Http::fake([
        'https://api.paymongo.com/v1/checkout_sessions' => Http::response([
            'data' => [
                'id' => 'cs_test_host_session_123',
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/cs_test_host_session_123',
                    'status' => 'active',
                ],
            ],
        ], 200),
    ]);

    // Player submits online payment
    $response = $this->actingAs($player)->post(route('open-play.host.pay.process', $session), [
        'payment_method' => 'paymongo',
    ]);

    $response->assertRedirect(route('paymongo.gateway.show', ['session_id' => 'cs_test_host_session_123']));

    // PayMongo success callback marks session as scheduled and paid
    $successResponse = $this->actingAs($player)->get(route('open-play.host.paymongo.success', [
        'open_play_id' => $session->id,
    ]));

    $successResponse->assertRedirect(route('open-play.host.index'));

    $session->refresh();
    expect($session->session_status)->toBe('scheduled');
    expect($session->host_payment_status)->toBe('paid');
    expect($session->host_payment_method)->toBe('paymongo');
    expect($session->host_paid_at)->not->toBeNull();

    // Now it appears in public open play directory
    $publicResponse = $this->get(route('open-play.index'));
    $publicResponse->assertSee('Approved Session Awaiting Payment');
});

test('another player cannot process payment for a session they did not host', function () {
    $hostPlayer = User::factory()->create(['role' => 'player']);
    $otherPlayer = User::factory()->create(['role' => 'player']);

    $session = OpenPlaySession::create([
        'title' => 'Host Player Session',
        'session_type' => 'open_play',
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '20:00',
        'max_capacity' => 10,
        'skill_level' => 'All Levels',
        'price_per_slot' => 150.00,
        'court_fee' => 600.00,
        'session_status' => 'approved_pending_payment',
        'host_payment_status' => 'unpaid',
        'created_by' => $hostPlayer->id,
    ]);
    $session->courts()->sync([$this->court1->id]);

    $this->actingAs($otherPlayer)->get(route('open-play.host.pay.show', $session))
        ->assertForbidden();

    $this->actingAs($otherPlayer)->post(route('open-play.host.pay.process', $session), [
        'payment_method' => 'cash',
    ])->assertForbidden();
});

test('host player payment breakdown shows individual courts and creates detailed paymongo checkout line items', function () {
    Config::set('services.paymongo.secret_key', 'sk_test_fake_secret_key');
    Config::set('services.paymongo.public_key', 'pk_test_fake_public_key');

    $player = User::factory()->create(['role' => 'player']);

    $session = OpenPlaySession::create([
        'title' => 'Alpha and Beta Social Play',
        'session_type' => 'open_play',
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '20:00',
        'max_capacity' => 12,
        'skill_level' => 'All Levels',
        'price_per_slot' => 150.00,
        // Alpha (300/hr) + Beta (200/hr) = 500/hr * 2 hrs = 1000.00
        'court_fee' => 1000.00,
        'session_status' => 'approved_pending_payment',
        'host_payment_status' => 'unpaid',
        'created_by' => $player->id,
    ]);
    $session->courts()->sync([$this->court1->id, $this->court2->id]);

    // Check payment page displays detailed court breakdown and prices
    $viewResponse = $this->actingAs($player)->get(route('open-play.host.pay.show', $session));
    $viewResponse->assertOk();
    $viewResponse->assertSee('Allocated Courts (2 Courts)');
    $viewResponse->assertSee('Court Alpha');
    $viewResponse->assertSee('₱300.00/hr × 2 hr(s)');
    $viewResponse->assertSee('₱600.00');
    $viewResponse->assertSee('Court Beta');
    $viewResponse->assertSee('₱200.00/hr × 2 hr(s)');
    $viewResponse->assertSee('₱400.00');
    $viewResponse->assertSee('₱1,000.00');

    Http::fake([
        'https://api.paymongo.com/v1/checkout_sessions' => Http::response([
            'data' => [
                'id' => 'cs_test_host_session_xyz789',
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/cs_test_host_session_xyz789',
                    'status' => 'active',
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($player)->post(route('open-play.host.pay.process', $session), [
        'payment_method' => 'paymongo',
    ]);

    $response->assertRedirect(route('paymongo.gateway.show', ['session_id' => 'cs_test_host_session_xyz789']));

    Http::assertSent(function ($request) {
        $payload = $request->data();
        $lineItems = $payload['data']['attributes']['line_items'] ?? [];
        $description = $payload['data']['attributes']['description'] ?? '';

        expect($lineItems)->toHaveCount(2);
        expect($lineItems[0]['name'])->toContain('Court Alpha');
        expect($lineItems[0]['amount'])->toBe(60000); // ₱600.00
        expect($lineItems[1]['name'])->toContain('Court Beta');
        expect($lineItems[1]['amount'])->toBe(40000); // ₱400.00
        expect($description)->toContain('2 courts');
        expect($description)->toContain('Court Alpha');
        expect($description)->toContain('Court Beta');

        return true;
    });
});
