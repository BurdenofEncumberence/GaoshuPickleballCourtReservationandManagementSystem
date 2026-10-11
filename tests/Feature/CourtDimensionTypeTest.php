<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;

test('court form renders fixed dropdown options for Regular (13.41m x 6.10m) and Junior (10m x 4.5m)', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.courts.create'));

    $response->assertStatus(200);
    $response->assertSee('id="size"', false);
    $response->assertSee('name="size"', false);
    $response->assertSee('Regular (13.41m x 6.10m)');
    $response->assertSee('Junior (10m x 4.5m)');
});

test('admin can create a court with Regular (13.41m x 6.10m) or Junior (10m x 4.5m) dimensions', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $responseRegular = $this->actingAs($admin)->post(route('admin.courts.store'), [
        'court_name' => 'Center Court Alpha',
        'size' => 'Regular (13.41m x 6.10m)',
        'price_per_hour' => 550.00,
        'court_status' => 'available',
    ]);

    $responseRegular->assertRedirect(route('admin.courts.index'));
    $this->assertDatabaseHas('courts', [
        'court_name' => 'Center Court Alpha',
        'size' => 'Regular (13.41m x 6.10m)',
    ]);

    $responseJunior = $this->actingAs($admin)->post(route('admin.courts.store'), [
        'court_name' => 'Junior Training Arena',
        'size' => 'Junior (10m x 4.5m)',
        'price_per_hour' => 400.00,
        'court_status' => 'available',
    ]);

    $responseJunior->assertRedirect(route('admin.courts.index'));
    $this->assertDatabaseHas('courts', [
        'court_name' => 'Junior Training Arena',
        'size' => 'Junior (10m x 4.5m)',
    ]);
});

test('court creation fails validation when size is omitted', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('admin.courts.store'), [
        'court_name' => 'No Dimension Court',
        'size' => '',
        'price_per_hour' => 500.00,
        'court_status' => 'available',
    ]);

    $response->assertSessionHasErrors('size');
});

test('players see court dimensions on booking schedule and summary', function () {
    $player = User::factory()->create(['role' => 'player']);

    Court::create([
        'court_name' => 'Main Stadium Court',
        'size' => 'Regular (13.41m x 6.10m)',
        'price_per_hour' => 600.00,
        'court_status' => 'available',
    ]);

    Court::create([
        'court_name' => 'Academy Junior Court',
        'size' => 'Junior (10m x 4.5m)',
        'price_per_hour' => 350.00,
        'court_status' => 'available',
    ]);

    $response = $this->actingAs($player)->get(route('booking'));

    $response->assertStatus(200);
    $response->assertSee('Main Stadium Court');
    $response->assertSee('Academy Junior Court');
    $response->assertSee('Regular (13.41m x 6.10m)');
    $response->assertSee('Junior (10m x 4.5m)');
});

test('players see court dimensions on public welcome page and booking history', function () {
    $court = Court::create([
        'court_name' => 'Pro Championship Court',
        'size' => 'Regular (13.41m x 6.10m)',
        'price_per_hour' => 700.00,
        'court_status' => 'available',
    ]);

    // Public welcome page
    $publicResponse = $this->get('/');
    $publicResponse->assertStatus(200);
    $publicResponse->assertSee('Pro Championship Court');
    $publicResponse->assertSee('Regular (13.41m x 6.10m)');

    // Player booking history
    $player = User::factory()->create(['role' => 'player']);
    Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'reference_number' => 'REF-DIM-123',
        'date' => today()->addDay()->toDateString(),
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'booking_status' => 'confirmed',
    ]);

    $historyResponse = $this->actingAs($player)->get(route('bookings.index'));
    $historyResponse->assertStatus(200);
    $historyResponse->assertSee('Pro Championship Court');
    $historyResponse->assertSee('Regular (13.41m x 6.10m)');
});

test('newly added court by manager immediately appears across all appointment booking pages and can be booked', function () {
    $manager = User::factory()->create(['role' => 'manager']);
    $staff = User::factory()->create(['role' => 'staff']);
    $player = User::factory()->create(['role' => 'player']);

    // Seed initial 4 courts
    foreach (range(1, 4) as $num) {
        Court::create([
            'court_name' => "Court {$num}",
            'size' => 'Regular (13.41m x 6.10m)',
            'price_per_hour' => 500.00,
            'court_status' => 'available',
        ]);
    }

    // Manager adds a 5th court via AdminCourtController@store
    $storeResponse = $this->actingAs($manager)->post(route('admin.courts.store'), [
        'court_name' => 'Court 5',
        'size' => 'Regular (13.41m x 6.10m)',
        'price_per_hour' => 550.00,
        'court_status' => 'available',
    ]);

    $storeResponse->assertRedirect(route('admin.courts.index'));

    $newCourt = Court::where('court_name', 'Court 5')->first();
    expect($newCourt)->not->toBeNull();
    expect($newCourt->court_status)->toBe('available');

    // 1. Player appointment booking page (/booking) shows Court 5 in the schedule matrix
    $playerBookingPage = $this->actingAs($player)->get(route('booking'));
    $playerBookingPage->assertStatus(200);
    $playerBookingPage->assertSee('Court 5');
    $playerBookingPage->assertSee('550.00');

    // 2. Staff walk-in appointment booking page (/staff/walk-in) shows Court 5
    $staffWalkInPage = $this->actingAs($staff)->get(route('staff.walkin.create'));
    $staffWalkInPage->assertStatus(200);
    $staffWalkInPage->assertSee('Court 5');

    // 3. Staff today schedule matrix (/staff/today) shows Court 5
    $staffTodayPage = $this->actingAs($staff)->get(route('staff.today'));
    $staffTodayPage->assertStatus(200);
    $staffTodayPage->assertSee('Court 5');

    // 4. Player Host Open Play court appointment page (/open-play/host) shows Court 5
    $hostOpenPlayPage = $this->actingAs($player)->get(route('open-play.host.create'));
    $hostOpenPlayPage->assertStatus(200);
    $hostOpenPlayPage->assertSee('Court 5');

    // 5. Manager Open Play creation page (/admin/open-play/create) shows Court 5
    $adminOpenPlayPage = $this->actingAs($manager)->get(route('admin.open-play.create'));
    $adminOpenPlayPage->assertStatus(200);
    $adminOpenPlayPage->assertSee('Court 5');

    // 6. Player can book an appointment on the newly added Court 5
    $targetDate = today()->addDay()->toDateString();
    $bookResponse = $this->actingAs($player)->post(route('bookings.store'), [
        'slots' => [
            [
                'court_id' => $newCourt->id,
                'date' => $targetDate,
                'time_slot' => '9:00 AM - 10:00 AM',
            ],
        ],
        'payment_method' => 'gcash',
    ]);

    $bookResponse->assertRedirect(route('bookings.index'));
    $this->assertDatabaseHas('bookings', [
        'user_id' => $player->id,
        'court_id' => $newCourt->id,
        'date' => $targetDate,
        'booking_status' => 'confirmed',
    ]);
});

