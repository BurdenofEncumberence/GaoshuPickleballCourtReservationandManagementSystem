<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;

it('records full payment upon successful customer booking and reflects in admin financials', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $player = User::factory()->create(['role' => 'player']);

    $court = Court::create([
        'court_name' => 'Court Center',
        'price_per_hour' => 750.00,
        'court_status' => 'available',
    ]);

    // Customer books a reservation
    $response = $this->actingAs($player)->post(route('bookings.store'), [
        'court_id' => $court->id,
        'date' => today()->addDay()->toDateString(),
        'time_slot' => '9:00 AM - 10:00 AM',
    ]);

    $response->assertRedirect(route('bookings.index'));

    // Verify booking was created and confirmed
    $booking = Booking::where('user_id', $player->id)->latest()->first();
    expect($booking)->not->toBeNull();
    expect($booking->booking_status)->toBe('confirmed');

    // Verify paid payment was generated
    $payment = Payment::where('booking_id', $booking->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->payment_status)->toBe('paid');
    expect((float) $payment->amount)->toBe(750.00);

    // Verify admin finance tab reflects the payment
    $adminFinanceResponse = $this->actingAs($admin)->get(route('admin.finance'));
    $adminFinanceResponse->assertOk();
    $adminFinanceResponse->assertSee('750.00');
    $adminFinanceResponse->assertSee($payment->ref_num);

    // Verify admin overview dashboard reflects the revenue
    $adminDashboardResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
    $adminDashboardResponse->assertOk();
    $adminDashboardResponse->assertSee('750.00');
});
