<?php
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AdminActivityLogController;
use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminCourtController;
use App\Http\Controllers\AdminCustomizationController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminEventController;
use App\Http\Controllers\AdminFinanceController;
use App\Http\Controllers\AdminOpenPlayController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingReceiptController;
use App\Http\Controllers\OpenPlayController;
use App\Http\Controllers\OpenPlayHostController;
use App\Http\Controllers\PayMongoGatewayController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffTodayController;
use App\Http\Controllers\StaffWalkInController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    /** @var \App\Models\User|null $user */
    $user = Auth::user();

    if ($user?->isAdmin() || $user?->hasRole('manager')) {
        return redirect()->route('admin.dashboard');
    }

    if ($user?->hasRole('staff')) {
        return redirect()->route('staff.today');
    }

    $courts = \App\Models\Court::where('court_status', 'available')->orderBy('id')->get();
    $events = \App\Models\Event::query()
        ->where('start_date', '<=', now()->addDays(30)->toDateString())
        ->where('end_date', '>=', now()->toDateString())
        ->orderBy('start_date')
        ->get();
    $openPlaySessions = \App\Models\OpenPlaySession::with('courts')
        ->where('date', '>=', now()->toDateString())
        ->whereIn('session_status', ['scheduled', 'ongoing'])
        ->orderBy('date')
        ->orderBy('start_time')
        ->take(3)
        ->get();
    $siteSettings = \App\Models\SiteSettings::first();

    return view('welcome', compact('courts', 'events', 'openPlaySessions', 'siteSettings'));
})->name('welcome');

Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');

Route::get('/open-play', [OpenPlayController::class, 'index'])->name('open-play.index');
Route::get('/open-play/{session}', [OpenPlayController::class, 'show'])->whereNumber('session')->name('open-play.show');

/*
|--------------------------------------------------------------------------
| Central Authenticated Dashboard Router
|--------------------------------------------------------------------------
| Routes authenticated users strictly according to their assigned role.
*/
Route::get('/dashboard', function () {
    /** @var \App\Models\User|null $user */
    $user = Auth::user();

    return match (true) {
        $user?->isAdmin() || $user?->hasRole('manager') => redirect()->route('admin.dashboard'),
        $user?->hasRole('staff') => redirect()->route('staff.today'),
        default => redirect()->route('welcome'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('welcome');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bookings/{booking}/receipt', [BookingReceiptController::class, 'show'])
        ->whereNumber('booking')
        ->name('bookings.receipt');

    Route::get('/paymongo/checkout/{session_id}', [PayMongoGatewayController::class, 'show'])
        ->name('paymongo.gateway.show');
    Route::post('/paymongo/checkout/{session_id}/complete', [PayMongoGatewayController::class, 'complete'])
        ->name('paymongo.gateway.complete');
});

/*
|--------------------------------------------------------------------------
| Admin & Manager Workspace
|--------------------------------------------------------------------------
| Restricted to admin and manager roles with verified accounts.
*/
Route::middleware(['auth', 'verified', 'role:admin,manager'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('/finance', [AdminFinanceController::class, 'index'])->name('finance');
        Route::resource('courts', AdminCourtController::class)
            ->except(['show'])
            ->whereNumber('court');
        Route::resource('events', AdminEventController::class);
        Route::resource('promos', AdminEventController::class)->names('promos');
        Route::resource('open-play', AdminOpenPlayController::class);
        Route::post('/open-play/{open_play}/accept', [AdminOpenPlayController::class, 'acceptHostRequest'])
            ->name('open-play.accept');
        Route::post('/open-play/{open_play}/reject', [AdminOpenPlayController::class, 'rejectHostRequest'])
            ->name('open-play.reject');
        Route::patch('/open-play/registrations/{registration}/attendance', [AdminOpenPlayController::class, 'updateAttendance'])
            ->name('open-play.attendance');
        Route::get('/reports/export', [AdminReportController::class, 'export'])->name('reports.export');
        Route::get('/customization', [AdminCustomizationController::class, 'index'])->name('customization.index');
        Route::patch('/customization', [AdminCustomizationController::class, 'update'])->name('customization.update');

        Route::middleware('role:admin')->group(function () {
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
            Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->whereNumber('user')->name('users.role');
            Route::patch('/users/{user}/status', [AdminUserController::class, 'toggleStatus'])->whereNumber('user')->name('users.status');
            Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');
        });
    });

/*
|--------------------------------------------------------------------------
| Staff Operations Workspace
|--------------------------------------------------------------------------
| Restricted to staff roles; includes parameter validation and rate limiting.
*/
Route::middleware(['auth', 'verified', 'role:staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/today', [StaffTodayController::class, 'index'])->name('today');
        Route::patch('/bookings/{booking}/status', [StaffTodayController::class, 'updateStatus'])
            ->whereNumber('booking')
            ->middleware('throttle:60,1')
            ->name('bookings.status');
        Route::patch('/open-play/registrations/{registration}/attendance', [StaffTodayController::class, 'updateOpenPlayAttendance'])
            ->whereNumber('registration')
            ->middleware('throttle:60,1')
            ->name('open-play.attendance');
        Route::get('/walk-in', [StaffWalkInController::class, 'create'])->name('walkin.create');
        Route::post('/walk-in', [StaffWalkInController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('walkin.store');
        Route::get('/walk-in/paymongo/success', [StaffWalkInController::class, 'paymongoSuccess'])
            ->name('walkin.paymongo.success');
        Route::get('/walk-in/paymongo/cancel', [StaffWalkInController::class, 'paymongoCancel'])
            ->name('walkin.paymongo.cancel');
    });

/*
|--------------------------------------------------------------------------
| Player Booking Portal
|--------------------------------------------------------------------------
| Restricted to players; prevents staff and admin from booking slots.
| Throttled to prevent reservation flood attacks.
*/
Route::middleware(['auth', 'verified', 'prevent.staff_admin_booking'])
    ->group(function () {
        Route::get('/booking', [BookingController::class, 'create'])->name('booking');
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::post('/bookings', [BookingController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('bookings.store');
        Route::get('/booking/paymongo/success', [BookingController::class, 'paymongoSuccess'])
            ->name('booking.paymongo.success');
        Route::get('/booking/paymongo/cancel', [BookingController::class, 'paymongoCancel'])
            ->name('booking.paymongo.cancel');

        Route::get('/open-play/host', [OpenPlayHostController::class, 'create'])->name('open-play.host.create');
        Route::post('/open-play/host', [OpenPlayHostController::class, 'store'])
            ->middleware('throttle:15,1')
            ->name('open-play.host.store');
        Route::get('/open-play/my-sessions', [OpenPlayHostController::class, 'index'])->name('open-play.host.index');
        Route::get('/open-play/{session}/host-pay', [OpenPlayHostController::class, 'showPayment'])->name('open-play.host.pay.show');
        Route::post('/open-play/{session}/host-pay', [OpenPlayHostController::class, 'processPayment'])->name('open-play.host.pay.process');
        Route::get('/open-play/host-pay/success', [OpenPlayHostController::class, 'paymongoSuccess'])->name('open-play.host.paymongo.success');
        Route::get('/open-play/host-pay/cancel', [OpenPlayHostController::class, 'paymongoCancel'])->name('open-play.host.paymongo.cancel');
        Route::delete('/open-play/my-sessions/{session}', [OpenPlayHostController::class, 'destroy'])->name('open-play.host.destroy');

        Route::post('/open-play/{session}/reserve', [OpenPlayController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('open-play.reserve');
        Route::get('/open-play/paymongo/success', [OpenPlayController::class, 'paymongoSuccess'])
            ->name('open-play.paymongo.success');
        Route::get('/open-play/paymongo/cancel', [OpenPlayController::class, 'paymongoCancel'])
            ->name('open-play.paymongo.cancel');
    });

/*
|--------------------------------------------------------------------------
| User Account & Profile Management
|--------------------------------------------------------------------------
| Requires authenticated session; rate limited against brute force/tampering.
*/
Route::middleware('auth')
    ->prefix('profile')
    ->name('profile.')
    ->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])
            ->middleware('throttle:15,1')
            ->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])
            ->middleware('throttle:5,1')
            ->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| Authentication Routes (Breeze / OTP / Password Management)
|--------------------------------------------------------------------------
*/



Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])
    ->middleware('throttle:5,1')
    ->name('contact.send');
require __DIR__.'/auth.php';