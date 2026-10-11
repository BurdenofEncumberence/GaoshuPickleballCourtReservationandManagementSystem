<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminBookingController extends Controller
{
    /**
     * Display a listing of all bookings across the system with filtering and search.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $courtId = $request->query('court_id', 'all');
        $channel = $request->query('channel', 'all');
        $dateFilter = $request->query('date_filter', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = Booking::query()
            ->with(['court', 'user', 'payments', 'event']);

        // Search filter (booking ID, reference number, user name, user email, court name)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('payments', function ($pq) use ($search) {
                        $pq->where('ref_num', 'like', "%{$search}%");
                    })
                    ->orWhereHas('court', function ($cq) use ($search) {
                        $cq->where('court_name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($status !== 'all' && in_array($status, ['confirmed', 'pending', 'cancelled', 'completed'])) {
            if ($status === 'completed') {
                $query->where('booking_status', 'confirmed')
                    ->where(function ($q) {
                        $q->whereDate('date', '<', today())
                            ->orWhere(function ($sub) {
                                $sub->whereDate('date', today())
                                    ->where('end_time', '<=', now()->format('H:i:s'));
                            });
                    });
            } else {
                $query->where('booking_status', $status);
            }
        }

        // Court filter
        if ($courtId !== 'all' && is_numeric($courtId)) {
            $query->where('court_id', (int) $courtId);
        }

        // Channel filter (online vs walk_in)
        if ($channel !== 'all' && in_array($channel, ['online', 'walk_in'])) {
            $query->where('booking_type', $channel);
        }

        // Date filter
        match ($dateFilter) {
            'today' => $query->whereDate('date', today()),
            'upcoming' => $query->whereDate('date', '>=', today()),
            'past' => $query->whereDate('date', '<', today()),
            'this_week' => $query->whereBetween('date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()]),
            'this_month' => $query->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]),
            default => null,
        };

        // Order by latest date and start time
        $bookings = $query->orderByDesc('date')
            ->orderByDesc('start_time')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Calculate KPIs
        $totalCount = Booking::count();
        $confirmedCount = Booking::where('booking_status', 'confirmed')->count();
        $pendingCount = Booking::where('booking_status', 'pending')->count();
        $todayCount = Booking::whereDate('date', today())->count();
        $totalRevenue = Payment::where('payment_status', 'paid')->sum('amount');

        $courts = Court::orderBy('id')->get();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'courts' => $courts,
            'status' => $status,
            'courtId' => $courtId,
            'channel' => $channel,
            'dateFilter' => $dateFilter,
            'search' => $search,
            'kpis' => [
                'total' => $totalCount,
                'confirmed' => $confirmedCount,
                'pending' => $pendingCount,
                'today' => $todayCount,
                'revenue' => (float) $totalRevenue,
            ],
        ]);
    }
}
