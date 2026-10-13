<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_role',
        'action',
        'description',
        'ip_address',
        'created_at',
        'updated_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an activity log entry safely without interrupting application flow.
     */
    public static function record(string $action, string $description, ?User $user = null, ?string $createdAt = null): ?self
    {
        try {
            if (! Schema::hasTable('activity_logs')) {
                return null;
            }

            /** @var User|null $actor */
            $actor = $user ?? Auth::user();

            $userName = $actor?->name ?: ($actor?->email ?: 'System');
            $userEmail = $actor?->email;
            $userRole = $actor?->role ?: 'player';
            $ipAddress = request()?->ip();

            $payload = [
                'user_id' => $actor?->id,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'user_role' => strtolower((string) $userRole),
                'action' => $action,
                'description' => $description,
                'ip_address' => $ipAddress,
            ];

            if ($createdAt) {
                $payload['created_at'] = $createdAt;
                $payload['updated_at'] = $createdAt;
            }

            return static::create($payload);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Backfill activity logs from existing users, courts, bookings, and open play records if the table is empty.
     */
    public static function backfillIfEmpty(): void
    {
        try {
            if (! Schema::hasTable('activity_logs') || static::query()->exists()) {
                return;
            }

            $admin = User::where('role', 'admin')->first();
            $manager = User::where('role', 'manager')->first() ?? $admin;
            $staff = User::where('role', 'staff')->first() ?? $manager;

            // 1. Existing Users (Account Setup / Registration)
            foreach (User::orderBy('id')->get() as $u) {
                $roleLabel = ucfirst($u->role ?: 'player');
                $action = in_array($u->role, ['admin', 'manager', 'staff'], true)
                    ? "Provisioned {$roleLabel} Account"
                    : 'Registered Player Account';

                static::create([
                    'user_id' => $u->id,
                    'user_name' => $u->name ?: $u->email,
                    'user_email' => $u->email,
                    'user_role' => $u->role ?: 'player',
                    'action' => $action,
                    'description' => "{$u->name} ({$u->email}) account active with role: {$roleLabel}.",
                    'ip_address' => '127.0.0.1',
                    'created_at' => $u->created_at ?? now()->subDays(5),
                    'updated_at' => $u->updated_at ?? now()->subDays(5),
                ]);
            }

            // 2. Existing Courts (Managed by Manager / Admin)
            foreach (Court::orderBy('id')->get() as $court) {
                $actor = $manager ?? $admin;
                static::create([
                    'user_id' => $actor?->id,
                    'user_name' => $actor?->name ?: 'Manager User',
                    'user_email' => $actor?->email,
                    'user_role' => $actor?->role ?: 'manager',
                    'action' => 'Added Court to Fleet',
                    'description' => "Configured {$court->court_name} ({$court->size}) at ₱" . number_format((float) $court->price_per_hour, 2) . "/hr [{$court->court_status}].",
                    'ip_address' => '127.0.0.1',
                    'created_at' => $court->created_at ?? now()->subDays(4),
                    'updated_at' => $court->updated_at ?? now()->subDays(4),
                ]);
            }

            // 3. Existing Bookings (Player Online Bookings & Staff Walk-In Bookings)
            foreach (Booking::with(['user', 'court', 'payments'])->orderBy('id')->get() as $booking) {
                $isWalkIn = ($booking->booking_type ?? 'online') === 'walk_in';
                $actor = $isWalkIn ? ($staff ?? $booking->user) : $booking->user;
                $courtName = $booking->court?->court_name ?? "Court #{$booking->court_id}";
                $playerName = $booking->user?->name ?? 'Guest Player';
                $amountPaid = (float) $booking->payments->where('payment_status', 'paid')->sum('amount');
                $ref = $booking->reference_number ?: ('#' . $booking->id);

                if ($isWalkIn) {
                    static::create([
                        'user_id' => $actor?->id,
                        'user_name' => $actor?->name ?: 'Staff Member',
                        'user_email' => $actor?->email,
                        'user_role' => $actor?->role ?: 'staff',
                        'action' => 'Created Walk-In Booking',
                        'description' => "Booked {$courtName} for walk-in player {$playerName} on {$booking->date} ({$booking->start_time} - {$booking->end_time}) — Paid ₱" . number_format($amountPaid, 2) . " ({$ref}).",
                        'ip_address' => '127.0.0.1',
                        'created_at' => $booking->created_at ?? now()->subDay(),
                        'updated_at' => $booking->updated_at ?? now()->subDay(),
                    ]);
                } else {
                    static::create([
                        'user_id' => $actor?->id,
                        'user_name' => $actor?->name ?: $playerName,
                        'user_email' => $actor?->email,
                        'user_role' => $actor?->role ?: 'player',
                        'action' => 'Booked Court Reservation',
                        'description' => "Reserved {$courtName} on {$booking->date} ({$booking->start_time} - {$booking->end_time}) — Status: " . ucfirst((string) $booking->booking_status) . ", Paid ₱" . number_format($amountPaid, 2) . " ({$ref}).",
                        'ip_address' => '127.0.0.1',
                        'created_at' => $booking->created_at ?? now()->subDay(),
                        'updated_at' => $booking->updated_at ?? now()->subDay(),
                    ]);
                }
            }

            // 4. Existing Open Play Sessions & Registrations
            if (Schema::hasTable('open_play_sessions')) {
                foreach (OpenPlaySession::with('host')->orderBy('id')->get() as $session) {
                    $actor = $session->host ?? $manager ?? $admin;
                    $role = $actor?->role ?: 'manager';
                    static::create([
                        'user_id' => $actor?->id,
                        'user_name' => $actor?->name ?: 'Manager User',
                        'user_email' => $actor?->email,
                        'user_role' => $role,
                        'action' => $session->host_user_id ? 'Requested Hosted Open Play' : 'Published Open Play Session',
                        'description' => "Scheduled \"{$session->title}\" on {$session->date} ({$session->start_time} - {$session->end_time}) — Status: " . ucfirst((string) $session->session_status) . ".",
                        'ip_address' => '127.0.0.1',
                        'created_at' => $session->created_at ?? now()->subHours(12),
                        'updated_at' => $session->updated_at ?? now()->subHours(12),
                    ]);
                }
            }

            if (Schema::hasTable('open_play_registrations')) {
                foreach (OpenPlayRegistration::with(['user', 'session'])->orderBy('id')->get() as $reg) {
                    $actor = $reg->user;
                    $sessionTitle = $reg->session?->title ?? "Session #{$reg->open_play_session_id}";
                    static::create([
                        'user_id' => $actor?->id,
                        'user_name' => $actor?->name ?: 'Player',
                        'user_email' => $actor?->email,
                        'user_role' => $actor?->role ?: 'player',
                        'action' => 'Joined Open Play Session',
                        'description' => "Registered {$reg->slots_reserved} slot(s) for \"{$sessionTitle}\" (Ref: {$reg->reference_number}) — Amount: ₱" . number_format((float) $reg->total_amount, 2) . ".",
                        'ip_address' => '127.0.0.1',
                        'created_at' => $reg->created_at ?? now()->subHours(6),
                        'updated_at' => $reg->updated_at ?? now()->subHours(6),
                    ]);
                }
            }
        } catch (Throwable) {
            // Ignore backfill errors
        }
    }
}
