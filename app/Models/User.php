<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'email_verified_at',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Capitalize the first letter of each word in a name string.
     */
    public static function titleCaseName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $trimmed = trim(preg_replace('/\s+/', ' ', (string) $name));
        if ($trimmed === '') {
            return null;
        }

        return mb_convert_case($trimmed, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->first_name !== null) {
                $user->first_name = static::titleCaseName($user->first_name);
            }
            if ($user->middle_name !== null) {
                $user->middle_name = static::titleCaseName($user->middle_name);
            }
            if ($user->last_name !== null) {
                $user->last_name = static::titleCaseName($user->last_name);
            }

            $computedName = trim(implode(' ', array_filter([$user->first_name, $user->middle_name, $user->last_name])));

            if ($user->name && $user->name !== $computedName && ($user->isDirty('name') || empty($computedName))) {
                $nameParts = preg_split('/\s+/', trim((string) $user->name));
                $user->first_name = static::titleCaseName(array_shift($nameParts) ?: null);
                $user->last_name = ! empty($nameParts) ? static::titleCaseName(array_pop($nameParts)) : null;
                $user->middle_name = ! empty($nameParts) ? static::titleCaseName(implode(' ', $nameParts)) : null;
            } elseif ($user->first_name || $user->last_name) {
                $parts = array_filter(
                    [$user->first_name, $user->middle_name, $user->last_name],
                    fn ($part) => ! empty(trim((string) $part))
                );
                $user->name = implode(' ', $parts);
            }

            if ($user->name !== null) {
                $user->name = static::titleCaseName($user->name);
            }
        });
    }

    public function setFirstNameAttribute(?string $value): void
    {
        $this->attributes['first_name'] = static::titleCaseName($value);
    }

    public function setMiddleNameAttribute(?string $value): void
    {
        $this->attributes['middle_name'] = static::titleCaseName($value);
    }

    public function setLastNameAttribute(?string $value): void
    {
        $this->attributes['last_name'] = static::titleCaseName($value);
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = static::titleCaseName($value);
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(?string $value): ?string
    {
        if (! empty($value)) {
            return static::titleCaseName($value);
        }

        $parts = array_filter(
            [$this->attributes['first_name'] ?? null, $this->attributes['middle_name'] ?? null, $this->attributes['last_name'] ?? null],
            fn ($part) => ! empty(trim((string) $part))
        );
        $fullName = implode(' ', $parts);

        return $fullName !== '' ? static::titleCaseName($fullName) : null;
    }

    /**
     * Get the user's first name with fallback to split name.
     */
    public function getFirstNameAttribute(?string $value): ?string
    {
        if (! empty($value)) {
            return static::titleCaseName($value);
        }

        if (! empty($this->attributes['name'] ?? null)) {
            $parts = explode(' ', trim((string) $this->attributes['name']), 2);
            return static::titleCaseName($parts[0] ?? null);
        }

        return null;
    }

    /**
     * Get the user's middle name.
     */
    public function getMiddleNameAttribute(?string $value): ?string
    {
        if (! empty($value)) {
            return static::titleCaseName($value);
        }

        return null;
    }

    /**
     * Get the user's last name with fallback to split name.
     */
    public function getLastNameAttribute(?string $value): ?string
    {
        if (! empty($value)) {
            return static::titleCaseName($value);
        }

        if (! empty($this->attributes['name'] ?? null)) {
            $parts = explode(' ', trim((string) $this->attributes['name']), 2);
            return static::titleCaseName($parts[1] ?? null);
        }

        return null;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    public function isPlayer(): bool
    {
        return $this->hasRole('player') || (! $this->hasRole(['admin', 'manager', 'staff']));
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = [
            'admin' => ['view_admin_dashboard', 'manage_courts', 'view_finances', 'manage_walk_in'],
            'manager' => ['view_admin_dashboard', 'manage_courts', 'view_finances', 'manage_walk_in', 'manage_open_play', 'create_open_play'],
            'staff' => ['view_today_bookings', 'manage_booking_status', 'manage_walk_in'],
            'player' => [],
        ];

        return in_array('*', $permissions[$this->role] ?? [], true)
            || in_array($permission, $permissions[$this->role] ?? [], true);
    }

    public function openPlayRegistrations()
    {
        return $this->hasMany(OpenPlayRegistration::class);
    }
}
