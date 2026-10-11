<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CourtUtilizationService
{
    /**
     * Standard operational capacity: 16 hours per day (6:00 AM to 10:00 PM).
     */
    public const DAILY_OPERATING_HOURS = 16;

    /**
     * Get facility-level summary utilization metrics.
     *
     * @param Carbon|null $date
     * @return array
     */
    public function getFacilityMetrics(?Carbon $date = null): array
    {
        $targetDate = $date ? $date->copy() : Carbon::today();
        $monthStart = $targetDate->copy()->startOfMonth();
        $monthEnd = $targetDate->copy()->endOfMonth();
        $daysInMonth = $targetDate->daysInMonth;

        $courts = Court::all();
        $totalCourtsCount = $courts->count();
        $availableCourtsCount = $courts->where('court_status', 'available')->count();
        $capacityCourtsCount = max($availableCourtsCount, 1);

        // Daily capacity in hours
        $dailyCapacityHours = $capacityCourtsCount * self::DAILY_OPERATING_HOURS;
        // Monthly capacity in hours
        $monthlyCapacityHours = $capacityCourtsCount * $daysInMonth * self::DAILY_OPERATING_HOURS;

        // Query active bookings (non-cancelled)
        $activeBookingsQuery = Booking::where('booking_status', '!=', 'cancelled');

        $todayBookings = (clone $activeBookingsQuery)
            ->whereDate('date', $targetDate->toDateString())
            ->get();

        $monthBookings = (clone $activeBookingsQuery)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();

        $allBookings = (clone $activeBookingsQuery)->get();

        $todayBookedHours = (float) $todayBookings->sum(fn ($b) => $this->calculateBookingHours($b));
        $monthBookedHours = (float) $monthBookings->sum(fn ($b) => $this->calculateBookingHours($b));
        $totalBookedHours = (float) $allBookings->sum(fn ($b) => $this->calculateBookingHours($b));

        $todayUtilizationRate = $dailyCapacityHours > 0
            ? round(($todayBookedHours / $dailyCapacityHours) * 100, 1)
            : 0.0;

        $monthUtilizationRate = $monthlyCapacityHours > 0
            ? round(($monthBookedHours / $monthlyCapacityHours) * 100, 1)
            : 0.0;

        // Court breakdown
        $courtBreakdown = $this->getCourtBreakdown($targetDate);

        // Peak booking time slot facility-wide
        $peakTimeSlot = $this->determinePeakSlot($monthBookings->isNotEmpty() ? $monthBookings : $allBookings);

        // Determine busiest court
        $busiestCourt = $courtBreakdown->sortByDesc('month_utilization_rate')->first();

        return [
            'operating_hours_per_day' => self::DAILY_OPERATING_HOURS,
            'total_courts_count' => $totalCourtsCount,
            'available_courts_count' => $availableCourtsCount,
            'today_booked_hours' => $todayBookedHours,
            'today_capacity_hours' => $dailyCapacityHours,
            'today_utilization_rate' => min($todayUtilizationRate, 100.0),
            'month_booked_hours' => $monthBookedHours,
            'month_capacity_hours' => $monthlyCapacityHours,
            'month_utilization_rate' => min($monthUtilizationRate, 100.0),
            'total_booked_hours' => $totalBookedHours,
            'total_sessions_count' => $allBookings->count(),
            'month_sessions_count' => $monthBookings->count(),
            'today_sessions_count' => $todayBookings->count(),
            'peak_time_slot' => $peakTimeSlot,
            'busiest_court_name' => $busiestCourt ? $busiestCourt['court_name'] : 'N/A',
            'busiest_court_rate' => $busiestCourt ? $busiestCourt['month_utilization_rate'] : 0.0,
            'busiest_court_hours' => $busiestCourt ? $busiestCourt['month_booked_hours'] : 0.0,
            'court_breakdown' => $courtBreakdown,
        ];
    }

    /**
     * Get per-court utilization metrics breakdown.
     *
     * @param Carbon|null $date
     * @return Collection
     */
    public function getCourtBreakdown(?Carbon $date = null): Collection
    {
        $targetDate = $date ? $date->copy() : Carbon::today();
        $monthStart = $targetDate->copy()->startOfMonth();
        $monthEnd = $targetDate->copy()->endOfMonth();
        $daysInMonth = $targetDate->daysInMonth;

        $courtDailyCapacity = self::DAILY_OPERATING_HOURS;
        $courtMonthlyCapacity = $daysInMonth * self::DAILY_OPERATING_HOURS;

        $courts = Court::with(['bookings' => function ($query) {
            $query->where('booking_status', '!=', 'cancelled')->with('payments');
        }])->orderBy('id')->get();

        return $courts->map(function (Court $court) use ($targetDate, $monthStart, $monthEnd, $courtDailyCapacity, $courtMonthlyCapacity) {
            $allBookings = $court->bookings;
            $todayBookings = $allBookings->where('date', $targetDate->toDateString());
            $monthBookings = $allBookings->filter(function ($b) use ($monthStart, $monthEnd) {
                return $b->date >= $monthStart->toDateString() && $b->date <= $monthEnd->toDateString();
            });

            $todayBookedHours = (float) $todayBookings->sum(fn ($b) => $this->calculateBookingHours($b));
            $monthBookedHours = (float) $monthBookings->sum(fn ($b) => $this->calculateBookingHours($b));
            $totalBookedHours = (float) $allBookings->sum(fn ($b) => $this->calculateBookingHours($b));

            $todayUtilization = $courtDailyCapacity > 0
                ? round(($todayBookedHours / $courtDailyCapacity) * 100, 1)
                : 0.0;

            $monthUtilization = $courtMonthlyCapacity > 0
                ? round(($monthBookedHours / $courtMonthlyCapacity) * 100, 1)
                : 0.0;

            // Total revenue generated by court
            $revenueGenerated = (float) $allBookings->flatMap->payments
                ->where('payment_status', 'paid')
                ->sum('amount');

            // Month revenue generated
            $monthRevenue = (float) $monthBookings->flatMap->payments
                ->where('payment_status', 'paid')
                ->sum('amount');

            // Revenue per available court hour (RevPACH) for this month
            $revPach = $courtMonthlyCapacity > 0
                ? round($monthRevenue / $courtMonthlyCapacity, 2)
                : 0.0;

            // Peak slot on this court
            $peakSlot = $this->determinePeakSlot($monthBookings->isNotEmpty() ? $monthBookings : $allBookings);

            return [
                'id' => $court->id,
                'court_name' => $court->court_name,
                'court_status' => $court->court_status,
                'size' => $court->size ?: 'Regular (13.41m x 6.10m)',
                'price_per_hour' => (float) $court->price_per_hour,
                'today_booked_hours' => $todayBookedHours,
                'today_capacity_hours' => $courtDailyCapacity,
                'today_utilization_rate' => min($todayUtilization, 100.0),
                'month_booked_hours' => $monthBookedHours,
                'month_capacity_hours' => $courtMonthlyCapacity,
                'month_utilization_rate' => min($monthUtilization, 100.0),
                'month_bookings_count' => $monthBookings->count(),
                'total_booked_hours' => $totalBookedHours,
                'total_bookings_count' => $allBookings->count(),
                'month_revenue' => $monthRevenue,
                'revenue_generated' => $revenueGenerated,
                'rev_pach' => $revPach,
                'peak_time_slot' => $peakSlot,
                'is_available' => $court->court_status === 'available',
            ];
        });
    }

    /**
     * Calculate duration of a booking in fractional hours.
     *
     * @param Booking $booking
     * @return float
     */
    public function calculateBookingHours(Booking $booking): float
    {
        if (empty($booking->start_time) || empty($booking->end_time)) {
            return 1.0;
        }

        try {
            $start = Carbon::parse($booking->start_time);
            $end = Carbon::parse($booking->end_time);
            $diffMins = abs($start->diffInMinutes($end));
            return $diffMins > 0 ? round($diffMins / 60, 2) : 1.0;
        } catch (\Throwable $e) {
            return 1.0;
        }
    }

    /**
     * Determine the most frequently booked time slot.
     *
     * @param Collection $bookings
     * @return string
     */
    protected function determinePeakSlot(Collection $bookings): string
    {
        if ($bookings->isEmpty()) {
            return 'N/A';
        }

        $frequencies = [];
        foreach ($bookings as $b) {
            if (empty($b->start_time) || empty($b->end_time)) {
                continue;
            }
            try {
                $start = Carbon::parse($b->start_time)->format('g:i A');
                $end = Carbon::parse($b->end_time)->format('g:i A');
                $slotKey = "{$start} - {$end}";
                $frequencies[$slotKey] = ($frequencies[$slotKey] ?? 0) + 1;
            } catch (\Throwable $e) {
                continue;
            }
        }

        if (empty($frequencies)) {
            return 'N/A';
        }

        arsort($frequencies);
        return array_key_first($frequencies);
    }
}
