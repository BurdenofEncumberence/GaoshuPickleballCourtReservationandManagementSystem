<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;

test('online booking store assigns booking_type online', function () {
    $player = User::factory()->create(['role' => 'player']);
    $court = Court::create([
        'court_name' => 'Court North',
        'price_per_hour' => 600,
        'court_status' => 'available',
    ]);

    $this->actingAs($player)->post(route('bookings.store'), [
        'court_id' => $court->id,
        'date' => today()->addDay()->toDateString(),
        'time_slot' => '10:00 AM - 11:00 AM',
    ])->assertRedirect(route('bookings.index'));

    $booking = Booking::where('user_id', $player->id)->latest()->first();
    expect($booking)->not->toBeNull();
    expect($booking->booking_type)->toBe('online');
    expect($booking->isOnline())->toBeTrue();
    expect($booking->isWalkIn())->toBeFalse();
});

test('staff walk-in booking store assigns booking_type walk_in', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $court = Court::create([
        'court_name' => 'Court South',
        'price_per_hour' => 600,
        'court_status' => 'available',
    ]);

    $this->actingAs($staff)->post(route('staff.walkin.store'), [
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'date' => today()->toDateString(),
        'court_id' => $court->id,
        'time_slot' => '11:00 AM - 12:00 PM',
        'payment_method' => 'cash',
        'attendance_status' => 'show',
    ])->assertRedirect(route('staff.today'));

    $booking = Booking::where('booking_type', 'walk_in')->latest()->first();
    expect($booking)->not->toBeNull();
    expect($booking->booking_type)->toBe('walk_in');
    expect($booking->isWalkIn())->toBeTrue();
    expect($booking->isOnline())->toBeFalse();
});

test('admin dashboard accurately displays online and walk-in reservation counts and revenues', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $court = Court::create([
        'court_name' => 'Grand Arena',
        'price_per_hour' => 500,
        'court_status' => 'available',
    ]);

    $player1 = User::factory()->create(['role' => 'player', 'name' => 'Online Player']);
    $player2 = User::factory()->create(['role' => 'player', 'name' => 'Walkin Player']);

    // 1 online booking today
    $onlineBooking = Booking::create([
        'user_id' => $player1->id,
        'court_id' => $court->id,
        'date' => today()->toDateString(),
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
        'booking_status' => 'confirmed',
        'attendance_status' => 'upcoming',
        'booking_type' => 'online',
    ]);
    Payment::create([
        'booking_id' => $onlineBooking->id,
        'amount' => 500,
        'payment_status' => 'paid',
        'payment_method' => 'gcash',
        'date' => today()->toDateString(),
        'time' => '10:00:00',
        'ref_num' => 'PAY-ONL-1',
    ]);

    // 1 walk-in booking today
    $walkInBooking = Booking::create([
        'user_id' => $player2->id,
        'court_id' => $court->id,
        'date' => today()->toDateString(),
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'booking_status' => 'confirmed',
        'attendance_status' => 'show',
        'booking_type' => 'walk_in',
    ]);
    Payment::create([
        'booking_id' => $walkInBooking->id,
        'amount' => 500,
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'date' => today()->toDateString(),
        'time' => '10:00:00',
        'ref_num' => 'WALK-001',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));
    $response->assertOk();

    // Assert counts passed to view
    $response->assertViewHas('todayOnlineCount', 1);
    $response->assertViewHas('todayWalkInCount', 1);
    $response->assertViewHas('totalOnlineCount', 1);
    $response->assertViewHas('totalWalkInCount', 1);
    $response->assertViewHas('onlineRevenue', 500.0);
    $response->assertViewHas('walkInRevenue', 500.0);

    // Assert page content
    $response->assertSee('Online Reservations');
    $response->assertSee('Walk-In Reservations');
    $response->assertSee('Online');
    $response->assertSee('Walk-In');
});

test('admin financial reports include online and walk-in reservation counts and collections', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $court = Court::create([
        'court_name' => 'Grand Arena',
        'price_per_hour' => 500,
        'court_status' => 'available',
    ]);

    $player = User::factory()->create(['role' => 'player']);

    // 2 online bookings this month
    for ($i = 0; $i < 2; $i++) {
        $b = Booking::create([
            'user_id' => $player->id,
            'court_id' => $court->id,
            'date' => today()->toDateString(),
            'start_time' => sprintf('%02d:00:00', 8 + $i),
            'end_time' => sprintf('%02d:00:00', 9 + $i),
            'booking_status' => 'confirmed',
            'attendance_status' => 'upcoming',
            'booking_type' => 'online',
        ]);
        Payment::create([
            'booking_id' => $b->id,
            'amount' => 500,
            'payment_status' => 'paid',
            'payment_method' => 'gcash',
            'date' => today()->toDateString(),
            'time' => '10:00:00',
            'ref_num' => 'PAY-ONL-' . ($i + 1),
        ]);
    }

    // 1 walk-in booking this month
    $w = Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => today()->toDateString(),
        'start_time' => '14:00:00',
        'end_time' => '15:00:00',
        'booking_status' => 'confirmed',
        'attendance_status' => 'show',
        'booking_type' => 'walk_in',
    ]);
    Payment::create([
        'booking_id' => $w->id,
        'amount' => 500,
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'date' => today()->toDateString(),
        'time' => '14:00:00',
        'ref_num' => 'WALK-002',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.finance'));
    $response->assertOk();

    // Assert counts passed to view
    $response->assertViewHas('totalOnlineCount', 2);
    $response->assertViewHas('totalWalkInCount', 1);
    $response->assertViewHas('monthOnlineCount', 2);
    $response->assertViewHas('monthWalkInCount', 1);
    $response->assertViewHas('onlineRevenue', 1000.0);
    $response->assertViewHas('walkInRevenue', 500.0);
    $response->assertViewHas('onlinePaidTransactions', 2);
    $response->assertViewHas('walkInPaidTransactions', 1);

    // Assert content rendered
    $response->assertSee('Online Reservations');
    $response->assertSee('Walk-In Reservations');
    $response->assertSee('1,000.00');
    $response->assertSee('500.00');
    $response->assertSee('Online');
    $response->assertSee('Walk-In');
});

test('admin and manager views display staff cashier and attendance audit columns', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $manager = User::factory()->create(['role' => 'manager']);
    $staff = User::factory()->create(['role' => 'staff', 'name' => 'Staff Alex']);
    $court = Court::create([
        'court_name' => 'Court Center',
        'price_per_hour' => 500,
        'court_status' => 'available',
    ]);
    $player = User::factory()->create(['role' => 'player', 'name' => 'Test Player']);

    $booking = Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => today()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'booking_status' => 'show',
        'booking_type' => 'walk_in',
        'status_updated_by' => $staff->id,
        'status_updated_at' => now(),
    ]);

    Payment::create([
        'booking_id' => $booking->id,
        'amount' => 500,
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'date' => today()->toDateString(),
        'time' => '10:00:00',
        'ref_num' => 'WALK-AUDIT-1',
        'processed_by' => $staff->id,
    ]);

    // 1. Admin bookings list page shows staff audit
    $bookingsRes = $this->actingAs($manager)->get(route('admin.bookings.index'));
    $bookingsRes->assertOk();
    $bookingsRes->assertSee('Staff Audit');
    $bookingsRes->assertSee('Cashier: Staff Alex');
    $bookingsRes->assertSee('Marked: Staff Alex');

    // 2. Admin dashboard page shows staff audit in run-sheet and recent payments
    $dashRes = $this->actingAs($admin)->get(route('admin.dashboard'));
    $dashRes->assertOk();
    $dashRes->assertSee('Staff Audit');
    $dashRes->assertSee('Staff / Cashier');
    $dashRes->assertSee('Staff Alex');

    // 3. Admin finance page shows staff cashier in master payment ledger
    $financeRes = $this->actingAs($admin)->get(route('admin.finance'));
    $financeRes->assertOk();
    $financeRes->assertSee('Staff / Cashier');
    $financeRes->assertSee('Staff Alex');

    // 4. Booking receipt page and JSON endpoint display handled by staff
    $receiptRes = $this->actingAs($admin)->get(route('bookings.receipt', $booking));
    $receiptRes->assertOk();
    $receiptRes->assertSee('Handled By:');
    $receiptRes->assertSee('Staff Alex');

    $receiptJsonRes = $this->actingAs($admin)->getJson(route('bookings.receipt', $booking));
    $receiptJsonRes->assertOk();
    $receiptJsonRes->assertJsonPath('processed_by_name', 'Staff Alex');

    // 5. Overall Operations CSV report includes staff cashier and attendance marker
    $overallCsvRes = $this->actingAs($admin)->get(route('admin.reports.export', [
        'type' => 'overall',
        'period' => 'this_month',
        'format' => 'csv',
    ]));
    $overallCsvRes->assertOk();
    $overallCsvStream = $overallCsvRes->streamedContent();
    expect($overallCsvStream)->toContain('Cashier / Processed By');
    expect($overallCsvStream)->toContain('Staff Alex');

    // 6. Financial Audit CSV report includes staff cashier
    $finCsvRes = $this->actingAs($admin)->get(route('admin.reports.export', [
        'type' => 'financial',
        'period' => 'this_month',
        'format' => 'csv',
    ]));
    $finCsvRes->assertOk();
    $finCsvStream = $finCsvRes->streamedContent();
    expect($finCsvStream)->toContain('Staff / Cashier');
    expect($finCsvStream)->toContain('Staff Alex');

    // 7. Court Utilization CSV report itemized court sessions include staff handler
    $utilCsvRes = $this->actingAs($admin)->get(route('admin.reports.export', [
        'type' => 'utilization',
        'period' => 'this_month',
        'format' => 'csv',
    ]));
    $utilCsvRes->assertOk();
    $utilCsvStream = $utilCsvRes->streamedContent();
    expect($utilCsvStream)->toContain('Staff / Cashier');
    expect($utilCsvStream)->toContain('Staff Alex');
});
