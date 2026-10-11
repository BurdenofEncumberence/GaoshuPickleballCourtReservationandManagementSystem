<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class StaffTodayController extends Controller
{
    protected array $standardTimeSlots = [
        '6:00 AM - 7:00 AM',
        '7:00 AM - 8:00 AM',
        '8:00 AM - 9:00 AM',
        '9:00 AM - 10:00 AM',
        '10:00 AM - 11:00 AM',
        '11:00 AM - 12:00 PM',
        '12:00 PM - 1:00 PM',
        '1:00 PM - 2:00 PM',
        '2:00 PM - 3:00 PM',
        '3:00 PM - 4:00 PM',
        '4:00 PM - 5:00 PM',
        '5:00 PM - 6:00 PM',
        '6:00 PM - 7:00 PM',
        '7:00 PM - 8:00 PM',
        '8:00 PM - 9:00 PM',
        '9:00 PM - 10:00 PM',
    ];

    public function index(Request $request): View
    {
        $today = Carbon::today();
        $dateParam = $request->input('date', $today->toDateString());
        $selectedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $dateParam) ? Carbon::parse($dateParam) : $today;

        $query = Booking::with(['court', 'user', 'payments'])
            ->whereDate('date', $selectedDate)
            ->where('booking_status', '!=', 'cancelled')
            ->orderBy('start_time');

        // Filter by court if specified
        if ($request->filled('court_id')) {
            $query->where('court_id', $request->input('court_id'));
        }

        // Filter by attendance status (show, no_show, scheduled)
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'show') {
                $query->where('booking_status', 'show');
            } elseif ($status === 'no_show') {
                $query->whereIn('booking_status', ['no_show', 'no-show']);
            } elseif ($status === 'scheduled') {
                $query->whereNotIn('booking_status', ['show', 'no_show', 'no-show']);
            }
        }

        // Search by customer name, email, or court name
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('court', function ($cq) use ($search) {
                    $cq->where('court_name', 'like', "%{$search}%");
                });
            });
        }

        $bookings = $query->get();

        // Query Open Play / Tournament sessions and registrations for selected date
        $openPlaySessions = OpenPlaySession::with(['courts', 'registrations.user'])
            ->whereDate('date', $selectedDate)
            ->whereIn('session_status', ['scheduled', 'ongoing', 'completed', 'approved_pending_payment'])
            ->orderBy('start_time')
            ->get();

        // Baseline attendance statistics & full slot matrix for the selected date
        $selectedDateAll = Booking::with(['court', 'user', 'payments'])
            ->whereDate('date', $selectedDate)
            ->where('booking_status', '!=', 'cancelled')
            ->get();

        $totalBookingsToday = $selectedDateAll->count();
        $showCount = $selectedDateAll->where('booking_status', 'show')->count();
        $noShowCount = $selectedDateAll->filter(fn ($b) => in_array($b->booking_status, ['no_show', 'no-show']))->count();
        $awaitingCount = max(0, $totalBookingsToday - $showCount - $noShowCount);
        $uniqueCustomersCount = $selectedDateAll->pluck('user_id')->unique()->count();

        // Build Booked Slot Details map indexed by [court_id][slot_label]
        $bookedSlotDetails = [];
        foreach ($selectedDateAll as $b) {
            $slotLabel = Carbon::parse($b->start_time)->format('g:i A') . ' - ' . Carbon::parse($b->end_time)->format('g:i A');
            $latestPayment = $b->payments->sortByDesc('id')->first();
            $courtRate = (float) ($b->court?->price_per_hour ?? 0);
            $paidAmount = $latestPayment ? (float) $latestPayment->amount : $courtRate;

            $bookedSlotDetails[$b->court_id][$slotLabel] = [
                'booking_id' => $b->id,
                'booking_ref' => 'BK-' . str_pad((string) $b->id, 6, '0', STR_PAD_LEFT),
                'customer_name' => $b->user?->name ?? 'Guest Player',
                'customer_email' => $b->user?->email ?? 'No email on file',
                'booking_type' => $b->isWalkIn() ? 'Walk-In Counter' : 'Online Reservation',
                'booking_status' => $b->booking_status,
                'attendance_label' => match (true) {
                    $b->booking_status === 'show' => 'Show (Present)',
                    in_array($b->booking_status, ['no_show', 'no-show']) => 'No-Show (Absent)',
                    default => 'Scheduled (Awaiting)',
                },
                'court_id' => $b->court_id,
                'court_name' => $b->court?->court_name ?? 'Court',
                'court_size' => $b->court?->size ?: 'Regular (13.41m x 6.10m)',
                'court_rate' => $courtRate,
                'date_formatted' => $selectedDate->format('F j, Y'),
                'date_iso' => $selectedDate->toDateString(),
                'is_today' => $selectedDate->isToday(),
                'time_slot' => $slotLabel,
                'payment_amount' => $paidAmount,
                'payment_status' => strtoupper($latestPayment?->payment_status ?? 'PAID'),
                'payment_method' => ucwords(str_replace('_', ' ', $latestPayment?->payment_method ?? 'cash')),
                'payment_ref' => $latestPayment?->ref_num ?: 'N/A',
                'paymongo_payment_id' => $latestPayment?->paymongo_payment_id ?: $latestPayment?->checkout_session_id,
                'cash_tendered' => $latestPayment?->cash_tendered !== null ? (float) $latestPayment->cash_tendered : null,
                'change_amount' => $latestPayment?->change_amount !== null ? (float) $latestPayment->change_amount : null,
                'status_update_url' => route('staff.bookings.status', $b),
                'receipt_print_url' => route('bookings.receipt', ['booking' => $b->id, 'autoprint' => 1]),
            ];
        }

        // Open Play attendee counts and special slot matrix details
        $openPlayAttendeesCount = 0;
        $specialSlotDetails = [];
        foreach ($openPlaySessions as $session) {
            $activeRegs = $session->registrations->whereIn('payment_status', ['paid', 'pending']);
            $sessionSlotsBooked = (int) $activeRegs->sum('slots_count');
            $openPlayAttendeesCount += $sessionSlotsBooked;
            $sessionRevenue = (float) $activeRegs->sum('total_fee');

            $playersList = $activeRegs->map(fn ($r) => [
                'name' => $r->player_name,
                'email' => $r->player_email,
                'slots_count' => $r->slots_count,
                'total_fee' => (float) $r->total_fee,
                'payment_status' => strtoupper($r->payment_status),
                'payment_method' => ucwords(str_replace('_', ' ', $r->payment_method)),
                'ref_num' => $r->ref_num,
                'paymongo_payment_id' => $r->paymongo_payment_id ?: $r->paymongo_checkout_session_id,
                'attendance_status' => $r->attendance_status,
            ])->values()->all();

            $sessionStart = Carbon::parse($session->start_time);
            $sessionEnd = Carbon::parse($session->end_time);
            $cur = $sessionStart->copy();
            while ($cur->lt($sessionEnd)) {
                $next = $cur->copy()->addHour();
                $slotLabel = $cur->format('g:i A') . ' - ' . $next->format('g:i A');
                foreach ($session->courts as $allocatedCourt) {
                    $specialSlotDetails[$allocatedCourt->id][$slotLabel] = [
                        'session_id' => $session->id,
                        'type' => $session->session_type,
                        'type_label' => $session->session_type === 'tournament' ? 'Tournament' : 'Open Play',
                        'title' => $session->title,
                        'skill_level' => $session->skill_level,
                        'time_window' => $session->time_window,
                        'court_name' => $allocatedCourt->court_name,
                        'price_per_slot' => (float) $session->price_per_slot,
                        'registered_slots' => $sessionSlotsBooked,
                        'max_capacity' => (int) $session->max_capacity,
                        'total_revenue' => $sessionRevenue,
                        'players' => $playersList,
                    ];
                }
                $cur = $next;
            }
        }

        $courts = Court::where('court_status', 'available')
            ->orWhereHas('bookings', fn ($q) => $q->whereDate('date', $selectedDate)->where('booking_status', '!=', 'cancelled'))
            ->orderBy('id')
            ->get()
            ->values();

        return view('staff.today', [
            'today' => $today,
            'selectedDate' => $selectedDate,
            'bookings' => $bookings,
            'openPlaySessions' => $openPlaySessions,
            'totalBookingsToday' => $totalBookingsToday,
            'showCount' => $showCount,
            'noShowCount' => $noShowCount,
            'awaitingCount' => $awaitingCount,
            'uniqueCustomersCount' => $uniqueCustomersCount,
            'openPlayAttendeesCount' => $openPlayAttendeesCount,
            'courts' => $courts,
            'timeSlots' => $this->standardTimeSlots,
            'bookedSlotDetails' => $bookedSlotDetails,
            'specialSlotDetails' => $specialSlotDetails,
            'selectedCourt' => $request->input('court_id'),
            'selectedStatus' => $request->input('status'),
            'searchTerm' => $request->input('search'),
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_status' => ['required', 'in:show,no_show,scheduled'],
        ]);

        if ($booking->booking_status === 'cancelled') {
            return back()->with('error', 'Cannot update attendance on a cancelled booking.');
        }

        if (! Carbon::parse($booking->date)->isSameDay(Carbon::today())) {
            return back()->with('error', 'Attendance status can only be updated for today\'s scheduled bookings.');
        }

        $statusValue = match ($validated['attendance_status']) {
            'show' => 'show',
            'no_show' => 'no_show',
            'scheduled' => 'confirmed',
        };

        $booking->update([
            'booking_status' => $statusValue,
        ]);

        $customerName = $booking->user?->name ?? 'Customer';
        $statusLabel = match ($validated['attendance_status']) {
            'show' => 'SHOW (Present)',
            'no_show' => 'NO-SHOW',
            'scheduled' => 'SCHEDULED (Awaiting)',
        };

        return back()->with('status', "Attendance for {$customerName} marked as {$statusLabel}.");
    }

    public function updateOpenPlayAttendance(Request $request, OpenPlayRegistration $registration): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_status' => ['required', 'in:registered,show,no_show'],
        ]);

        $registration->update($validated);

        $statusLabel = match ($validated['attendance_status']) {
            'show' => 'SHOW (Present)',
            'no_show' => 'NO-SHOW',
            'registered' => 'REGISTERED (Awaiting)',
        };

        return back()->with('status', "Open Play attendance for {$registration->player_name} marked as {$statusLabel}.");
    }
}
