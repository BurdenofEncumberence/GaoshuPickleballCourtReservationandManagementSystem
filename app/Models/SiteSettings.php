<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSettings extends Model
{
    protected $fillable = [
        'business_name',
        'system_name',
        'tagline',
        'email_address',
        'contact_number',
        'business_address',
        'facebook_link',
        'twitter_link',
        'instagram_link',
        'linkedin_link',
        'logo',
    ];

    public static function current(): ?self
    {
        return \Illuminate\Support\Facades\Cache::remember('site_settings_current', 3600, function () {
            return static::first();
        });
    }

    public static function clearCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('site_settings_current');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }
}
