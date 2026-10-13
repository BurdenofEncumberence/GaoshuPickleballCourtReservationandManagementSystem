<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;

it('allows staff to view customers booked for today with show/no-show attendance', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $player = User::factory()->create(['role' => 'player', 'name' => 'John Customer']);
    $court = Court::create([
        'court_name' => 'Court Alpha',
        'price_per_hour' => 500,
        'court_status' => 'available',
    ]);

    Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => today()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'booking_status' => 'confirmed',
    ]);

    $response = $this->actingAs($staff)->get(route('staff.today'));

    $response->assertOk();
    $response->assertSee('John Customer');
    $response->assertSee('Court Alpha');
    $response->assertSee('Awaiting arrival', false);
});

it('blocks players from viewing staff today schedule', function () {
    $player = User::factory()->create(['role' => 'player']);

    $this->actingAs($player)
        ->get(route('staff.today'))
        ->assertForbidden();
});

it('blocks staff from viewing admin finance', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff)
        ->get(route('admin.finance'))
        ->assertForbidden();
});

it('blocks admins from viewing staff today schedule because admin only sees admin panel', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('staff.today'))
        ->assertForbidden();
});

it('redirects staff to staff today after login', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'password' => 'password',
    ]);

    $this->post(route('login'), [
        'email' => $staff->email,
        'password' => 'password',
    ])->assertRedirect(route('staff.today', absolute: false));
});

it('allows staff to mark customer as show, no-show, and reset to scheduled', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $player = User::factory()->create(['role' => 'player']);
    $court = Court::create([
        'court_name' => 'Court Beta',
        'price_per_hour' => 400,
        'court_status' => 'available',
    ]);

    $booking = Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => today()->toDateString(),
        'start_time' => '14:00:00',
        'end_time' => '15:00:00',
        'booking_status' => 'confirmed',
    ]);

    // Mark as SHOW
    $response = $this->actingAs($staff)
        ->patch(route('staff.bookings.status', $booking), [
            'attendance_status' => 'show',
        ]);

    $response->assertRedirect();
    $this->assertSame('show', $booking->fresh()->booking_status);

    // Mark as NO-SHOW
    $this->actingAs($staff)
        ->patch(route('staff.bookings.status', $booking), [
            'attendance_status' => 'no_show',
        ])
        ->assertRedirect();

    $this->assertSame('no_show', $booking->fresh()->booking_status);

    // Reset to SCHEDULED
    $this->actingAs($staff)
        ->patch(route('staff.bookings.status', $booking), [
            'attendance_status' => 'scheduled',
        ])
        ->assertRedirect();

    $this->assertSame('confirmed', $booking->fresh()->booking_status);
});

it('allows staff to filter schedule by custom date', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $player = User::factory()->create(['role' => 'player', 'name' => 'Tomorrow Player']);
    $court = Court::create([
        'court_name' => 'Court Gamma',
        'price_per_hour' => 450,
        'court_status' => 'available',
    ]);

    $tomorrow = today()->addDay()->toDateString();
    Booking::create([
        'user_id' => $player->id,
        'court_id' => $court->id,
        'date' => $tomorrow,
        'start_time' => '11:00:00',
        'end_time' => '12:00:00',
        'booking_status' => 'confirmed',
    ]);

    $response = $this->actingAs($staff)->get(route('staff.today', ['date' => $tomorrow]));
    $response->assertOk();
    $response->assertSee('Tomorrow Player');
    $response->assertSee('Court Gamma');
});

