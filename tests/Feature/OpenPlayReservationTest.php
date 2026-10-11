<?php

use App\Models\Court;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // Fresh courts for testing
    $this->court1 = Court::create([
        'court_name' => 'Court 1',
        'price_per_hour' => 500.00,
        'court_status' => 'available',
    ]);

    $this->court2 = Court::create([
        'court_name' => 'Court 2',
        'price_per_hour' => 500.00,
        'court_status' => 'available',
    ]);

    $this->court3 = Court::create([
        'court_name' => 'Court 3',
        'price_per_hour' => 500.00,
        'court_status' => 'available',
    ]);
});

test('manager can create open play session with allocated courts, capacity, and participation fee', function () {
    $manager = User::factory()->create(['role' => 'manager']);

    $sessionDate = today()->addDays(3)->toDateString();

    $response = $this->actingAs($manager)->post(route('admin.open-play.store'), [
        'title' => 'Friday Night Open Play (Intermediate 3.5+)',
        'session_type' => 'open_play',
        'date' => $sessionDate,
        'start_time' => '18:00',
        'end_time' => '21:00',
        'allocated_courts' => [$this->court1->id, $this->court2->id, $this->court3->id],
        'max_capacity' => 12,
        'skill_level' => 'Intermediate 3.5+',
        'price_per_slot' => 150.00,
        'details' => 'Communal rotation pool across Courts 1, 2, and 3. Paddle stacking rules apply.',
    ]);

    $response->assertRedirect(route('admin.open-play.index'));

    $session = OpenPlaySession::where('title', 'Friday Night Open Play (Intermediate 3.5+)')->first();
    expect($session)->not->toBeNull();
    expect($session->max_capacity)->toBe(12);
    expect((float) $session->price_per_slot)->toBe(150.00);
    expect($session->courts)->toHaveCount(3);
    expect($session->remaining_slots)->toBe(12);
    expect($session->is_full)->toBeFalse();
});

test('admin cannot create open play session and is forbidden', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $sessionDate = today()->addDays(3)->toDateString();

    $this->actingAs($admin)->get(route('admin.open-play.create'))
        ->assertForbidden();

    $response = $this->actingAs($admin)->post(route('admin.open-play.store'), [
        'title' => 'Admin Attempt Session',
        'session_type' => 'open_play',
        'date' => $sessionDate,
        'start_time' => '18:00',
        'end_time' => '21:00',
        'allocated_courts' => [$this->court1->id],
        'max_capacity' => 8,
        'skill_level' => 'All Levels',
        'price_per_slot' => 100.00,
    ]);

    $response->assertForbidden();
});

test('manager cannot create open play session without allocated courts or max capacity', function () {
    $manager = User::factory()->create(['role' => 'manager']);

    $response = $this->actingAs($manager)->post(route('admin.open-play.store'), [
        'title' => 'Incomplete Session',
        'session_type' => 'open_play',
        'date' => today()->addDays(2)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '21:00',
        'allocated_courts' => [],
        'max_capacity' => null,
        'skill_level' => 'All Levels',
        'price_per_slot' => 100.00,
    ]);

    $response->assertSessionHasErrors(['allocated_courts', 'max_capacity']);
});

test('admin can view session roster and update player attendance', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $player = User::factory()->create(['role' => 'player']);

    $session = OpenPlaySession::create([
        'title' => 'Saturday Morning Tournament',
        'session_type' => 'tournament',
        'date' => today()->addDays(4)->toDateString(),
        'start_time' => '08:00',
        'end_time' => '12:00',
        'max_capacity' => 16,
        'skill_level' => 'Advanced 4.0+',
        'price_per_slot' => 250.00,
        'session_status' => 'scheduled',
        'created_by' => $admin->id,
    ]);
    $session->courts()->attach([$this->court1->id, $this->court2->id]);

    $registration = $session->registrations()->create([
        'user_id' => $player->id,
        'slots_count' => 1,
        'total_fee' => 250.00,
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'ref_num' => 'OP-TEST1234',
        'attendance_status' => 'registered',
    ]);

    // View roster
    $rosterResponse = $this->actingAs($admin)->get(route('admin.open-play.show', $session));
    $rosterResponse->assertOk();
    $rosterResponse->assertSee($player->name);
    $rosterResponse->assertSee('OP-TEST1234');

    // Update attendance to show
    $patchResponse = $this->actingAs($admin)->patch(route('admin.open-play.attendance', $registration), [
        'attendance_status' => 'show',
    ]);
    $patchResponse->assertRedirect();
    expect($registration->fresh()->attendance_status)->toBe('show');

    // Update attendance to no_show
    $this->actingAs($admin)->patch(route('admin.open-play.attendance', $registration), [
        'attendance_status' => 'no_show',
    ]);
    expect($registration->fresh()->attendance_status)->toBe('no_show');
});

test('players can browse upcoming open play sessions and view real-time remaining slots', function () {
    $session = OpenPlaySession::create([
        'title' => 'Sunday Social Mixer',
        'session_type' => 'open_play',
        'date' => today()->addDays(5)->toDateString(),
        'start_time' => '17:00',
        'end_time' => '20:00',
        'max_capacity' => 10,
        'skill_level' => 'All Levels',
        'price_per_slot' => 120.00,
        'session_status' => 'scheduled',
    ]);
    $session->courts()->attach([$this->court1->id]);

    $existingPlayer = User::factory()->create(['role' => 'player']);

    // Pre-register 3 slots
    $session->registrations()->create([
        'user_id' => $existingPlayer->id,
        'slots_count' => 3,
        'total_fee' => 360.00,
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'ref_num' => 'OP-PRE001',
    ]);

    $response = $this->get(route('open-play.index'));
    $response->assertOk();
    $response->assertSee('Sunday Social Mixer');
    $response->assertSee('7 of 10 slots left');
    $response->assertSee('₱120.00');

    $detailResponse = $this->get(route('open-play.show', $session));
    $detailResponse->assertOk();
    $detailResponse->assertSee('Sunday Social Mixer');
    $detailResponse->assertSee('7 of 10 player slots remaining');
});

test('player can reserve a slot paying only participation fee instead of full court rate', function () {
    $player = User::factory()->create(['role' => 'player']);

    $session = OpenPlaySession::create([
        'title' => 'Tuesday Drill & Play',
        'session_type' => 'open_play',
        'date' => today()->addDays(2)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '21:00',
        'max_capacity' => 8,
        'skill_level' => 'Beginner 2.0-3.0',
        'price_per_slot' => 150.00,
        'session_status' => 'scheduled',
    ]);
    $session->courts()->attach([$this->court1->id, $this->court2->id]);

    $payload = [
        'slots_count' => 1,
        'payment_method' => 'cash',
        'notes' => 'Looking forward to play',
    ];

    $response = $this->actingAs($player)->post(route('open-play.reserve', $session), $payload);
    $response->assertRedirect(route('bookings.index'));

    $registration = OpenPlayRegistration::where('user_id', $player->id)->first();
    expect($registration)->not->toBeNull();
    expect($registration->slots_count)->toBe(1);
    // Participation fee is ₱150, NOT the 3-hour court rate of ₱3,000 (2 courts * 3 hrs * ₱500)
    expect((float) $registration->total_fee)->toBe(150.00);
    expect($registration->payment_method)->toBe('cash');
    expect($registration->payment_status)->toBe('pending');
    expect($session->fresh()->remaining_slots)->toBe(7);
});

test('player can reserve multiple slots for friends and participation fee is accurately calculated', function () {
    $player = User::factory()->create(['role' => 'player']);

    $session = OpenPlaySession::create([
        'title' => 'Weekend Pickleball Bash',
        'session_type' => 'open_play',
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '16:00',
        'end_time' => '19:00',
        'max_capacity' => 12,
        'skill_level' => 'Intermediate 3.5+',
        'price_per_slot' => 180.00,
        'session_status' => 'scheduled',
    ]);
    $session->courts()->attach([$this->court1->id, $this->court2->id]);

    $payload = [
        'slots_count' => 3,
        'payment_method' => 'cash',
        'notes' => 'Reserving for Alice, Bob, and Charlie',
    ];

    $this->actingAs($player)->post(route('open-play.reserve', $session), $payload);

    $registration = OpenPlayRegistration::where('user_id', $player->id)->first();
    expect($registration->slots_count)->toBe(3);
    // 3 slots * ₱180 = ₱540
    expect((float) $registration->total_fee)->toBe(540.00);
    expect($session->fresh()->remaining_slots)->toBe(9);
});

test('capacity control rejects the 13th registration when session has 12 slots maximum', function () {
    $player1 = User::factory()->create(['role' => 'player']);
    $player2 = User::factory()->create(['role' => 'player']);

    $session = OpenPlaySession::create([
        'title' => 'Strict 12 Player Cap Open Play',
        'session_type' => 'open_play',
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '21:00',
        'max_capacity' => 12,
        'skill_level' => 'All Levels',
        'price_per_slot' => 150.00,
        'session_status' => 'scheduled',
    ]);
    $session->courts()->attach([$this->court1->id, $this->court2->id, $this->court3->id]);

    // Fill up all 12 slots
    $session->registrations()->create([
        'user_id' => $player1->id,
        'slots_count' => 12,
        'total_fee' => 1800.00,
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'ref_num' => 'OP-FULL12',
    ]);

    expect($session->fresh()->remaining_slots)->toBe(0);
    expect($session->fresh()->is_full)->toBeTrue();

    // Now attempt to register the 13th slot
    $response = $this->actingAs($player2)->from(route('open-play.show', $session))
        ->post(route('open-play.reserve', $session), [
            'slots_count' => 1,
            'payment_method' => 'cash',
        ]);

    $response->assertRedirect(route('open-play.show', $session));
    $response->assertSessionHasErrors(['slots_count']);

    // Ensure no registration was created
    expect(OpenPlayRegistration::where('user_id', $player2->id)->exists())->toBeFalse();
    expect($session->fresh()->registered_slots_count)->toBe(12);
});

test('capacity control rejects booking if requested quantity exceeds remaining available slots', function () {
    $earlyPlayer = User::factory()->create(['role' => 'player']);
    $player = User::factory()->create(['role' => 'player']);

    $session = OpenPlaySession::create([
        'title' => 'Limited Capacity Session',
        'session_type' => 'open_play',
        'date' => today()->addDays(2)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '21:00',
        'max_capacity' => 10,
        'skill_level' => 'All Levels',
        'price_per_slot' => 150.00,
        'session_status' => 'scheduled',
    ]);
    $session->courts()->attach([$this->court1->id]);

    // Already 8 slots registered (2 remaining)
    $session->registrations()->create([
        'user_id' => $earlyPlayer->id,
        'slots_count' => 8,
        'total_fee' => 1200.00,
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'ref_num' => 'OP-EARLY8',
    ]);

    expect($session->fresh()->remaining_slots)->toBe(2);

    // Player requests 3 slots when only 2 remain
    $response = $this->actingAs($player)->from(route('open-play.show', $session))
        ->post(route('open-play.reserve', $session), [
            'slots_count' => 3,
            'payment_method' => 'cash',
        ]);

    $response->assertRedirect(route('open-play.show', $session));
    $response->assertSessionHasErrors(['slots_count']);
    expect(OpenPlayRegistration::where('user_id', $player->id)->exists())->toBeFalse();
});

test('private court booking is blocked on courts allocated to an active open play session during session hours', function () {
    $player = User::factory()->create(['role' => 'player']);
    $targetDate = today()->addDays(2)->toDateString();

    // Open play session allocated on Court 1 from 6:00 PM (18:00) to 9:00 PM (21:00)
    $session = OpenPlaySession::create([
        'title' => 'Friday Night Communal Open Play',
        'session_type' => 'open_play',
        'date' => $targetDate,
        'start_time' => '18:00',
        'end_time' => '21:00',
        'max_capacity' => 12,
        'skill_level' => 'All Levels',
        'price_per_slot' => 150.00,
        'session_status' => 'scheduled',
    ]);
    $session->courts()->attach([$this->court1->id]);

    // Player tries to make a private reservation on Court 1 at 6:00 PM - 7:00 PM
    $payload = [
        'slots' => [
            [
                'court_id' => $this->court1->id,
                'date' => $targetDate,
                'time_slot' => '6:00 PM - 7:00 PM',
            ],
        ],
        'payment_method' => 'cash',
    ];

    $response = $this->actingAs($player)->post(route('bookings.store'), $payload);
    $response->assertSessionHasErrors('slots.0.time_slot');

    // But private booking on Court 2 (not allocated to Open Play) is permitted!
    $payloadCourt2 = [
        'slots' => [
            [
                'court_id' => $this->court2->id,
                'date' => $targetDate,
                'time_slot' => '6:00 PM - 7:00 PM',
            ],
        ],
        'payment_method' => 'cash',
    ];

    $responseCourt2 = $this->actingAs($player)->post(route('bookings.store'), $payloadCourt2);
    $responseCourt2->assertRedirect(route('bookings.index'));
});

test('cancelled registrations release capacity immediately for other players', function () {
    $player = User::factory()->create(['role' => 'player']);

    $session = OpenPlaySession::create([
        'title' => 'Slot Release Session',
        'session_type' => 'open_play',
        'date' => today()->addDays(3)->toDateString(),
        'start_time' => '10:00',
        'end_time' => '13:00',
        'max_capacity' => 4,
        'skill_level' => 'All Levels',
        'price_per_slot' => 100.00,
        'session_status' => 'scheduled',
    ]);
    $session->courts()->attach([$this->court1->id]);

    $registration = $session->registrations()->create([
        'user_id' => $player->id,
        'slots_count' => 4,
        'total_fee' => 400.00,
        'payment_status' => 'pending',
        'payment_method' => 'paymongo',
        'ref_num' => 'OP-RELEASE1',
        'paymongo_checkout_session_id' => 'cs_test_release_123',
    ]);

    expect($session->fresh()->remaining_slots)->toBe(0);

    // Cancel callback triggered
    $this->actingAs($player)->get(route('open-play.paymongo.cancel', [
        'session_id' => 'cs_test_release_123',
        'ref' => 'OP-RELEASE1',
    ]));

    expect($registration->fresh()->payment_status)->toBe('cancelled');
    // All 4 slots are released back into the pool
    expect($session->fresh()->remaining_slots)->toBe(4);
});

test('court booking schedule marks open play and tournament slots distinctively and passes specialSlots', function () {
    $player = User::factory()->create(['role' => 'player', 'email_verified_at' => now()]);

    $date = today()->addDays(2)->toDateString();

    $openPlay = OpenPlaySession::create([
        'title' => 'Friday Night Social',
        'session_type' => 'open_play',
        'date' => $date,
        'start_time' => '18:00',
        'end_time' => '21:00',
        'max_capacity' => 12,
        'skill_level' => 'Intermediate 3.5+',
        'price_per_slot' => 180.00,
        'session_status' => 'scheduled',
    ]);
    $openPlay->courts()->attach([$this->court1->id]);

    $tournament = OpenPlaySession::create([
        'title' => 'Championship Cup',
        'session_type' => 'tournament',
        'date' => $date,
        'start_time' => '08:00',
        'end_time' => '11:00',
        'max_capacity' => 16,
        'skill_level' => 'Advanced 4.0+',
        'price_per_slot' => 350.00,
        'session_status' => 'scheduled',
    ]);
    $tournament->courts()->attach([$this->court2->id]);

    $response = $this->actingAs($player)->get(route('booking'));

    $response->assertOk();
    $response->assertViewHas('specialSlots');
    $specialSlots = $response->viewData('specialSlots');

    // Court 1 has open play slots
    expect(isset($specialSlots[$date][$this->court1->id]['6:00 PM - 7:00 PM']))->toBeTrue();
    expect($specialSlots[$date][$this->court1->id]['6:00 PM - 7:00 PM']['type'])->toBe('open_play');

    // Court 2 has tournament slots
    expect(isset($specialSlots[$date][$this->court2->id]['8:00 AM - 9:00 AM']))->toBeTrue();
    expect($specialSlots[$date][$this->court2->id]['8:00 AM - 9:00 AM']['type'])->toBe('tournament');

    // View includes legend and text
    $response->assertSee('Open Play');
    $response->assertSee('Tournament');
});
