<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CourtSeeder::class);

        // Seed / Update Site Settings to Gaoshou Pickleball
        \App\Models\SiteSettings::updateOrCreate(
            ['id' => 1],
            [
                'business_name' => 'Gaoshou Pickleball',
                'system_name' => 'Gaoshou Pickleball',
                'tagline' => 'Book Your Court, Rally with Ease',
                'email_address' => 'support@gaoshou.ph',
            ]
        );

        // Seed Admin account
        User::updateOrCreate(
            ['email' => 'admin@kymnet.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Seed Manager account
        User::updateOrCreate(
            ['email' => 'manager@kymnet.com'],
            [
                'first_name' => 'Manager',
                'last_name' => 'User',
                'name' => 'Manager User',
                'password' => Hash::make('manager123'),
                'role' => 'manager',
                'email_verified_at' => now(),
            ]
        );

        // Seed Staff account
        User::updateOrCreate(
            ['email' => 'staff@kymnet.com'],
            [
                'first_name' => 'Staff',
                'last_name' => 'Member',
                'name' => 'Staff Member',
                'password' => Hash::make('staff123'),
                'role' => 'staff',
                'email_verified_at' => now(),
            ]
        );

        // Seed Gaoshou accounts
        User::updateOrCreate(
            ['email' => 'admin@gaoshou.ph'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        User::updateOrCreate(
            ['email' => 'manager@gaoshou.ph'],
            [
                'first_name' => 'Manager',
                'last_name' => 'User',
                'name' => 'Manager User',
                'password' => Hash::make('manager123'),
                'role' => 'manager',
                'email_verified_at' => now(),
            ]
        );
        User::updateOrCreate(
            ['email' => 'staff@gaoshou.ph'],
            [
                'first_name' => 'Staff',
                'last_name' => 'Member',
                'name' => 'Staff Member',
                'password' => Hash::make('staff123'),
                'role' => 'staff',
                'email_verified_at' => now(),
            ]
        );
        User::updateOrCreate(
            ['email' => 'player@gaoshou.ph'],
            [
                'first_name' => 'Player',
                'last_name' => 'Demo',
                'name' => 'Player Demo',
                'password' => Hash::make('player123'),
                'role' => 'player',
                'email_verified_at' => now(),
            ]
        );

        // Seed Player test account
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'role' => 'player',
                'email_verified_at' => now(),
            ]
        );

        // Seed a sample booking for today's run-sheet
        $testPlayer = User::where('email', 'test@example.com')->first();
        $firstCourt = \App\Models\Court::first();
        if ($testPlayer && $firstCourt) {
            \App\Models\Booking::firstOrCreate(
                [
                    'user_id' => $testPlayer->id,
                    'court_id' => $firstCourt->id,
                    'date' => today()->toDateString(),
                    'start_time' => '10:00:00',
                ],
                [
                    'end_time' => '11:00:00',
                    'booking_status' => 'confirmed',
                    'booking_type' => 'online',
                ]
            );
        }

        // Backfill activity logs across all user types
        \App\Models\ActivityLog::backfillIfEmpty();
    }
}
