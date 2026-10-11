<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenPlayRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'open_play_session_id',
        'user_id',
        'slots_count',
        'total_fee',
        'payment_status',
        'payment_method',
        'ref_num',
        'attendance_status',
        'paymongo_checkout_session_id',
        'paymongo_payment_id',
        'notes',
    ];

    protected $casts = [
        'slots_count' => 'integer',
        'total_fee' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(OpenPlaySession::class, 'open_play_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPlayerNameAttribute(): string
    {
        return $this->user?->name ?? 'Registered Player';
    }

    public function getPlayerEmailAttribute(): string
    {
        return $this->user?->email ?? '';
    }

    public function getPlayerPhoneAttribute(): ?string
    {
        return $this->user?->phone;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isCancelled(): bool
    {
        return $this->payment_status === 'cancelled';
    }
}
