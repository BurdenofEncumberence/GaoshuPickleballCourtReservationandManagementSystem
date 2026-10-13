<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'court_id',
        'event_id',
        'date',
        'start_time',
        'end_time',
        'booking_status',
        'booking_type',
        'status_updated_by',
        'status_updated_at',
    ];

    protected $casts = [
        'status_updated_at' => 'datetime',
    ];

    public function isWalkIn(): bool
    {
        return $this->booking_type === 'walk_in';
    }

    public function isOnline(): bool
    {
        return $this->booking_type === 'online';
    }

    public function getBookingTypeLabelAttribute(): string
    {
        return $this->isWalkIn() ? 'Walk-In' : 'Online';
    }

    public function scopeWalkIn($query)
    {
        return $query->where('booking_type', 'walk_in');
    }

    public function scopeOnline($query)
    {
        return $query->where('booking_type', 'online');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function statusUpdatedBy()
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }
}