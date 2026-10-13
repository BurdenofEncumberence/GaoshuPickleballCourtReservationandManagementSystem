<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\CourtUtilizationService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminFinanceController extends Controller
{
    public function index(CourtUtilizationService $utilizationService): View
    {
        $paidPayments = Payment::where('payment_status', 'paid');
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthlyRevenue = (clone $paidPayments)->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])->sum('amount');
        $yearRevenue = (clone $paidPayments)->whereYear('date', now()->year)->sum('amount');

        $monthlyTotals = collect(range(5, 0))->map(function (int $monthsAgo) use ($paidPayments): array {
            $month = now()->subMonths($monthsAgo);

            return [
                'label' => $month->format('M'),
                'amount' => (float) (clone $paidPayments)->whereYear('date', $month->year)->whereMonth('date', $month->month)->sum('amount'),
            ];
        });

        $methodTotals = (clone $paidPayments)
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as transactions')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $totalOnlineCount = Booking::where('booking_type', 'online')->count();
        $totalWalkInCount = Booking::where('booking_type', 'walk_in')->count();
        $monthOnlineCount = Booking::whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])->where('booking_type', 'online')->count();
        $monthWalkInCount = Booking::whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])->where('booking_type', 'walk_in')->count();

        $onlineRevenue = (clone $paidPayments)->whereHas('booking', fn ($q) => $q->where('booking_type', 'online'))->sum('amount');
        $walkInRevenue = (clone $paidPayments)->whereHas('booking', fn ($q) => $q->where('booking_type', 'walk_in'))->sum('amount');
        $onlinePaidTransactions = (clone $paidPayments)->whereHas('booking', fn ($q) => $q->where('booking_type', 'online'))->count();
        $walkInPaidTransactions = (clone $paidPayments)->whereHas('booking', fn ($q) => $q->where('booking_type', 'walk_in'))->count();

        return view('admin.finance', [
            'monthlyRevenue' => $monthlyRevenue,
            'yearRevenue' => $yearRevenue,
            'transactionCount' => (clone $paidPayments)->count(),
            'monthlyTotals' => $monthlyTotals,
            'methodTotals' => $methodTotals,
            'totalOnlineCount' => $totalOnlineCount,
            'totalWalkInCount' => $totalWalkInCount,
            'monthOnlineCount' => $monthOnlineCount,
            'monthWalkInCount' => $monthWalkInCount,
            'onlineRevenue' => $onlineRevenue,
            'walkInRevenue' => $walkInRevenue,
            'onlinePaidTransactions' => $onlinePaidTransactions,
            'walkInPaidTransactions' => $walkInPaidTransactions,
            'payments' => Payment::with(['booking.user', 'processor', 'booking.statusUpdatedBy'])->latest('date')->latest('created_at')->paginate(10),
            'today' => Carbon::today(),
            'utilization' => $utilizationService->getFacilityMetrics(),
            'courtUtilization' => $utilizationService->getCourtBreakdown(),
        ]);
    }
}
