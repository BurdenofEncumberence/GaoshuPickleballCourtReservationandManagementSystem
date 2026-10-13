<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Event;
use App\Models\Payment;
use App\Services\CourtUtilizationService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(CourtUtilizationService $utilizationService): View
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $courts = Court::withCount([
            'bookings as active_bookings_count' => fn ($query) => $query
                ->whereIn('booking_status', ['pending', 'confirmed'])
                ->whereDate('date', '>=', today()),
        ])->orderBy('id')->get();

        $todayBookings = Booking::with(['court', 'user', 'statusUpdatedBy', 'payments.processor'])
            ->whereDate('date', today())
            ->orderBy('start_time')
            ->get();

        $paidPayments = Payment::where('payment_status', 'paid');

        $monthlyRevenue = (clone $paidPayments)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        $lastMonthRevenue = (clone $paidPayments)
            ->whereBetween('date', [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()])
            ->sum('amount');

        $yearRevenue = (clone $paidPayments)
            ->whereYear('date', now()->year)
            ->sum('amount');

        $revenueChange = $lastMonthRevenue > 0
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100)
            : null;

        $recentPayments = Payment::with(['booking.user', 'processor', 'booking.statusUpdatedBy'])
            ->latest('date')
            ->latest('created_at')
            ->limit(6)
            ->get();

        $todayOnlineCount = $todayBookings->filter(fn ($b) => $b->booking_type === 'online')->count();
        $todayWalkInCount = $todayBookings->filter(fn ($b) => $b->booking_type === 'walk_in')->count();

        $totalOnlineCount = Booking::where('booking_type', 'online')->count();
        $totalWalkInCount = Booking::where('booking_type', 'walk_in')->count();

        $monthBookings = Booking::whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
        $monthOnlineCount = (clone $monthBookings)->where('booking_type', 'online')->count();
        $monthWalkInCount = (clone $monthBookings)->where('booking_type', 'walk_in')->count();

        $onlineRevenue = (clone $paidPayments)->whereHas('booking', fn ($q) => $q->where('booking_type', 'online'))->sum('amount');
        $walkInRevenue = (clone $paidPayments)->whereHas('booking', fn ($q) => $q->where('booking_type', 'walk_in'))->sum('amount');

        return view('admin.dashboard', [
            'courts' => $courts,
            'todayBookings' => $todayBookings,
            'todayOnlineCount' => $todayOnlineCount,
            'todayWalkInCount' => $todayWalkInCount,
            'totalOnlineCount' => $totalOnlineCount,
            'totalWalkInCount' => $totalWalkInCount,
            'monthOnlineCount' => $monthOnlineCount,
            'monthWalkInCount' => $monthWalkInCount,
            'onlineRevenue' => $onlineRevenue,
            'walkInRevenue' => $walkInRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'yearRevenue' => $yearRevenue,
            'revenueChange' => $revenueChange,
            'pendingPayments' => Payment::where('payment_status', 'pending')->count(),
            'totalBookings' => Booking::count(),
            'confirmedBookingsCount' => Booking::where('booking_status', 'confirmed')->count(),
            'eventsCount' => Event::count(),
            'recentPayments' => $recentPayments,
            'today' => Carbon::today(),
            'utilization' => $utilizationService->getFacilityMetrics(),
            'courtUtilization' => $utilizationService->getCourtBreakdown(),
        ]);
    }
}
