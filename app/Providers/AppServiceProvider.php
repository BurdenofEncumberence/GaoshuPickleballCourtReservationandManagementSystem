<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Event as FacilityEvent;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\SiteSettings;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $isVercel = (bool) (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('VERCEL'));

        if ($this->app->environment('production') || $isVercel) {
            URL::forceScheme('https');
        }

        $this->registerActivityLogListeners();
    }

    /**
     * Register automatic activity log listeners across all user types and models.
     */
    protected function registerActivityLogListeners(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            /** @var User|null $user */
            $user = $event->user instanceof User ? $event->user : null;
            if ($user) {
                $roleLabel = ucfirst($user->role ?: 'player');
                ActivityLog::record(
                    'Logged In',
                    "{$user->name} signed in to the {$roleLabel} workspace.",
                    $user
                );
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            /** @var User|null $user */
            $user = $event->user instanceof User ? $event->user : null;
            if ($user) {
                ActivityLog::record(
                    'Logged Out',
                    "{$user->name} signed out of the system.",
                    $user
                );
            }
        });

        Event::listen(Registered::class, function (Registered $event): void {
            /** @var User|null $user */
            $user = $event->user instanceof User ? $event->user : null;
            if ($user) {
                $roleLabel = ucfirst($user->role ?: 'player');
                ActivityLog::record(
                    'Registered Account',
                    "{$user->name} ({$user->email}) registered a new {$roleLabel} account.",
                    $user
                );
            }
        });

        Booking::created(function (Booking $booking): void {
            if ($this->app->runningInConsole() && ! $this->app->runningUnitTests() && ! Auth::check()) {
                return;
            }

            /** @var User|null $authUser */
            $authUser = Auth::user();
            $bookingUser = $booking->user ?? User::find($booking->user_id);
            $court = $booking->court ?? Court::find($booking->court_id);
            $courtName = $court?->court_name ?? "Court #{$booking->court_id}";
            $isWalkIn = ($booking->booking_type ?? 'online') === 'walk_in'
                || ($authUser && $authUser->hasRole(['staff', 'admin', 'manager']));

            $actor = $isWalkIn && $authUser ? $authUser : ($bookingUser ?? $authUser);
            if (! $actor) {
                return;
            }

            $ref = $booking->reference_number ?: ('#' . $booking->id);
            if ($isWalkIn) {
                $playerName = $bookingUser?->name ?? 'Walk-In Player';
                ActivityLog::record(
                    'Created Walk-In Booking',
                    "Booked {$courtName} for {$playerName} on {$booking->date} ({$booking->start_time} - {$booking->end_time}) — Ref: {$ref}.",
                    $actor
                );
            } else {
                ActivityLog::record(
                    'Booked Court Appointment',
                    "Reserved {$courtName} on {$booking->date} ({$booking->start_time} - {$booking->end_time}) — Ref: {$ref}.",
                    $actor
                );
            }
        });

        Booking::updated(function (Booking $booking): void {
            if (! $booking->wasChanged('booking_status')) {
                return;
            }

            /** @var User|null $actor */
            $actor = Auth::user() ?? $booking->user ?? User::find($booking->user_id);
            if (! $actor) {
                return;
            }

            $court = $booking->court ?? Court::find($booking->court_id);
            $courtName = $court?->court_name ?? "Court #{$booking->court_id}";
            $ref = $booking->reference_number ?: ('#' . $booking->id);

            ActivityLog::record(
                'Updated Booking Status',
                "Changed booking {$ref} ({$courtName} on {$booking->date}) status to " . ucfirst((string) $booking->booking_status) . '.',
                $actor
            );
        });

        Court::created(function (Court $court): void {
            if (! Auth::check()) {
                return;
            }

            ActivityLog::record(
                'Added Court to Fleet',
                "Added {$court->court_name} ({$court->size}) at ₱" . number_format((float) $court->price_per_hour, 2) . "/hr [{$court->court_status}]."
            );
        });

        Court::updated(function (Court $court): void {
            if (! Auth::check()) {
                return;
            }

            ActivityLog::record(
                'Updated Court Details',
                "Updated {$court->court_name} ({$court->size}) — Rate: ₱" . number_format((float) $court->price_per_hour, 2) . "/hr, Status: {$court->court_status}."
            );
        });

        Court::deleted(function (Court $court): void {
            if (! Auth::check()) {
                return;
            }

            ActivityLog::record(
                'Removed Court',
                "Removed {$court->court_name} from court inventory."
            );
        });

        OpenPlaySession::created(function (OpenPlaySession $session): void {
            $actor = Auth::user() ?? ($session->host_user_id ? User::find($session->host_user_id) : null);
            if (! $actor) {
                return;
            }

            $action = $session->host_user_id && $actor->isPlayer()
                ? 'Requested Hosted Open Play'
                : 'Created Open Play Session';

            ActivityLog::record(
                $action,
                "Scheduled \"{$session->title}\" on {$session->date} ({$session->start_time} - {$session->end_time}).",
                $actor
            );
        });

        OpenPlaySession::updated(function (OpenPlaySession $session): void {
            if (! Auth::check()) {
                return;
            }

            ActivityLog::record(
                'Updated Open Play Session',
                "Updated Open Play session \"{$session->title}\" ({$session->date}) — Status: " . ucfirst((string) $session->session_status) . '.'
            );
        });

        OpenPlayRegistration::created(function (OpenPlayRegistration $reg): void {
            $actor = Auth::user() ?? ($reg->user_id ? User::find($reg->user_id) : null);
            if (! $actor) {
                return;
            }

            $session = $reg->session ?? OpenPlaySession::find($reg->open_play_session_id);
            $title = $session?->title ?? "Session #{$reg->open_play_session_id}";

            ActivityLog::record(
                'Joined Open Play Session',
                "Reserved {$reg->slots_reserved} slot(s) for \"{$title}\" (Ref: {$reg->reference_number}).",
                $actor
            );
        });

        OpenPlayRegistration::updated(function (OpenPlayRegistration $reg): void {
            if (! Auth::check() || ! $reg->wasChanged('attendance_status')) {
                return;
            }

            ActivityLog::record(
                'Updated Player Attendance',
                "Updated Open Play registration {$reg->reference_number} attendance to " . strtoupper((string) $reg->attendance_status) . '.'
            );
        });

        FacilityEvent::created(function (FacilityEvent $event): void {
            if (! Auth::check()) {
                return;
            }

            ActivityLog::record(
                'Created Facility Event',
                "Created event \"{$event->event_name}\" ({$event->start_date} to {$event->end_date})."
            );
        });

        FacilityEvent::updated(function (FacilityEvent $event): void {
            if (! Auth::check()) {
                return;
            }

            ActivityLog::record(
                'Updated Facility Event',
                "Updated event \"{$event->event_name}\" ({$event->start_date} to {$event->end_date})."
            );
        });

        FacilityEvent::deleted(function (FacilityEvent $event): void {
            if (! Auth::check()) {
                return;
            }

            ActivityLog::record(
                'Deleted Facility Event',
                "Deleted event \"{$event->event_name}\"."
            );
        });

        SiteSettings::updated(function (SiteSettings $settings): void {
            if (! Auth::check()) {
                return;
            }

            ActivityLog::record(
                'Updated Site Customization',
                "Updated branding and customization settings for {$settings->system_name}."
            );
        });
    }
}
