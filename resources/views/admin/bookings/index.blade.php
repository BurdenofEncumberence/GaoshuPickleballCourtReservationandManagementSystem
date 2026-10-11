<x-admin-layout>
    <x-slot name="heading">Booking History</x-slot>

    <!-- Header Panel -->
    <div class="gz-panel gz-panel-body mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="gz-eyebrow">Reservation archives & receipts</span>
            <h1 class="gz-font-display font-bold text-xl mt-1">Court Booking History</h1>
            <p class="text-sm mt-1" style="color: var(--gz-muted);">
                Audit, inspect, and trace all court bookings across online and walk-in channels with viewable official receipts.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="gz-badge-outline font-mono text-xs">
                TOTAL: {{ number_format($kpis['total']) }}
            </span>
            <button
                type="button"
                @click="$dispatch('open-export-modal', { type: 'bookings', period: 'all' })"
                class="gz-btn-outline gz-btn-sm flex items-center gap-1.5"
                title="Export booking report in CSV format"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Export</span>
            </button>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="gz-kpi-card">
            <div class="gz-eyebrow mb-1">Total Reservations</div>
            <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-right" style="color: var(--gz-ink);">
                {{ number_format($kpis['total']) }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">All-time reservations</p>
        </div>

        <div class="gz-kpi-card">
            <div class="gz-eyebrow mb-1">Confirmed</div>
            <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-right" style="color: var(--gz-pop-dark);">
                {{ number_format($kpis['confirmed']) }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Settled and active</p>
        </div>

        <div class="gz-kpi-card">
            <div class="gz-eyebrow mb-1">Pending</div>
            <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-amber-600 text-right">
                {{ number_format($kpis['pending']) }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Awaiting payment</p>
        </div>

        <div class="gz-kpi-card">
            <div class="gz-eyebrow mb-1">Today</div>
            <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-right" style="color: var(--gz-ink);">
                {{ number_format($kpis['today']) }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Scheduled today</p>
        </div>

        <div class="gz-kpi-card col-span-2 lg:col-span-1">
            <div class="gz-eyebrow mb-1">Total Collections</div>
            <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-right" style="color: var(--gz-pop-dark);">
                ₱{{ number_format($kpis['revenue'], 2) }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Audited payments</p>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="gz-panel p-4 mb-6" style="background: var(--gz-surface);">
        <form method="GET" action="{{ route('admin.bookings.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <!-- Search bar -->
            <div class="sm:col-span-4 relative">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search by ID, Ref, Player, Email, Court..."
                    class="gz-input text-xs w-full py-2 pr-8"
                >
                @if($search !== '')
                    <a
                        href="{{ route('admin.bookings.index', array_merge(request()->except(['search', 'page']))) }}"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs font-bold opacity-60 hover:opacity-100"
                        title="Clear search"
                    >×</a>
                @endif
            </div>

            <!-- Status filter -->
            <div class="sm:col-span-2">
                <select name="status" onchange="this.form.submit()" class="gz-input text-xs w-full py-2">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="confirmed" {{ $status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Court filter -->
            <div class="sm:col-span-2">
                <select name="court_id" onchange="this.form.submit()" class="gz-input text-xs w-full py-2">
                    <option value="all" {{ $courtId === 'all' ? 'selected' : '' }}>All Courts</option>
                    @foreach($courts as $court)
                        <option value="{{ $court->id }}" {{ (string)$courtId === (string)$court->id ? 'selected' : '' }}>
                            {{ $court->court_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Channel filter -->
            <div class="sm:col-span-2">
                <select name="channel" onchange="this.form.submit()" class="gz-input text-xs w-full py-2">
                    <option value="all" {{ $channel === 'all' ? 'selected' : '' }}>All Channels</option>
                    <option value="online" {{ $channel === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="walk_in" {{ $channel === 'walk_in' ? 'selected' : '' }}>Walk-In</option>
                </select>
            </div>

            <!-- Date filter -->
            <div class="sm:col-span-2">
                <select name="date_filter" onchange="this.form.submit()" class="gz-input text-xs w-full py-2">
                    <option value="all" {{ $dateFilter === 'all' ? 'selected' : '' }}>All Dates</option>
                    <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="upcoming" {{ $dateFilter === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    <option value="past" {{ $dateFilter === 'past' ? 'selected' : '' }}>Past</option>
                    <option value="this_week" {{ $dateFilter === 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ $dateFilter === 'this_month' ? 'selected' : '' }}>This Month</option>
                </select>
            </div>
        </form>

        @if($search !== '' || $status !== 'all' || $courtId !== 'all' || $channel !== 'all' || $dateFilter !== 'all')
            <div class="mt-3 pt-3 border-t flex items-center justify-between text-xs" style="border-color: var(--gz-border); color: var(--gz-muted);">
                <div class="flex items-center gap-2 flex-wrap">
                    <span>Active Filters:</span>
                    @if($search !== '')
                        <span class="gz-badge gz-badge-neutral text-[10px]">Keyword: {{ $search }}</span>
                    @endif
                    @if($status !== 'all')
                        <span class="gz-badge gz-badge-neutral text-[10px] capitalize">Status: {{ $status }}</span>
                    @endif
                    @if($courtId !== 'all')
                        <span class="gz-badge gz-badge-neutral text-[10px]">Court #{{ $courtId }}</span>
                    @endif
                    @if($channel !== 'all')
                        <span class="gz-badge gz-badge-neutral text-[10px] capitalize">Channel: {{ $channel }}</span>
                    @endif
                    @if($dateFilter !== 'all')
                        <span class="gz-badge gz-badge-neutral text-[10px] capitalize">Date: {{ str_replace('_', ' ', $dateFilter) }}</span>
                    @endif
                </div>
                <a href="{{ route('admin.bookings.index') }}" class="font-semibold underline shrink-0 text-red-600 hover:text-red-700">
                    Reset All Filters
                </a>
            </div>
        @endif
    </div>

    <!-- Bookings Table Panel -->
    <div class="gz-panel">
        <div class="gz-panel-header">
            <div>
                <h2 class="gz-font-display font-bold text-base">Booking Records</h2>
                <p class="text-xs" style="color: var(--gz-muted);">Itemized historical reservations with instant receipt inspection</p>
            </div>
            <span class="gz-badge gz-badge-neutral">{{ $bookings->total() }} total records</span>
        </div>

        <div class="gz-panel-body overflow-x-auto">
            @if($bookings->isEmpty())
                <div class="p-12 text-center border border-dashed rounded-xl" style="border-color: var(--gz-border); background: var(--gz-surface);">
                    <p class="font-semibold text-base" style="color: var(--gz-ink);">No booking records found</p>
                    <p class="text-xs mt-1" style="color: var(--gz-muted);">
                        Try adjusting your search query or filter options above.
                    </p>
                    @if($search !== '' || $status !== 'all' || $courtId !== 'all' || $channel !== 'all' || $dateFilter !== 'all')
                        <a href="{{ route('admin.bookings.index') }}" class="gz-btn-outline gz-btn-sm mt-3 inline-block">
                            Clear Filters
                        </a>
                    @endif
                </div>
            @else
                <table class="gz-table">
                    <thead>
                        <tr>
                            <th>Ref No.</th>
                            <th>Date & Time</th>
                            <th>Court</th>
                            <th>Player</th>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th class="text-right">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                            @php
                                $payment = $booking->payments->first();
                                $ref = $payment?->ref_num ?: ('BK-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT));
                                $courtName = $booking->court?->court_name ?? 'Court Removed';
                                $courtSize = $booking->court?->size ?: 'Regular (13.41m x 6.10m)';
                                $fee = $payment?->amount ?? ($booking->court?->price_per_hour ?? 0);

                                $statusBadgeClass = match($booking->booking_status) {
                                    'confirmed' => 'gz-badge-success',
                                    'pending'   => 'gz-badge-warning',
                                    'cancelled' => 'gz-badge-danger',
                                    default     => 'gz-badge-neutral',
                                };
                            @endphp
                            <tr>
                                <!-- Ref No. -->
                                <td>
                                    <div class="font-mono font-bold text-xs" style="color: var(--gz-ink);">
                                        {{ $ref }}
                                    </div>
                                    <div class="text-[10px]" style="color: var(--gz-muted);">
                                        ID #{{ $booking->id }}
                                    </div>
                                    @if($payment && ($payment->paymongo_payment_id || $payment->checkout_session_id))
                                        <div class="font-mono text-[10px] mt-0.5" style="color: var(--gz-muted);">
                                            PayMongo: {{ $payment->paymongo_payment_id ?: $payment->checkout_session_id }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Date & Time -->
                                <td>
                                    <div class="font-semibold text-xs whitespace-nowrap" style="color: var(--gz-ink);">
                                        {{ \Carbon\Carbon::parse($booking->date)->format('M d, Y') }}
                                    </div>
                                    <div class="text-[11px] whitespace-nowrap" style="color: var(--gz-muted);">
                                        {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
                                    </div>
                                </td>

                                <!-- Court -->
                                <td>
                                    <div class="font-semibold text-xs" style="color: var(--gz-ink);">
                                        {{ $courtName }}
                                    </div>
                                    <span class="gz-badge gz-badge-neutral text-[9px] mt-0.5">
                                        {{ $courtSize }}
                                    </span>
                                    @if($booking->event)
                                        <div class="text-[10px] text-emerald-700 font-medium mt-0.5">
                                            {{ $booking->event->event_title }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Player -->
                                <td>
                                    <div class="font-semibold text-xs" style="color: var(--gz-ink);">
                                        {{ $booking->user?->name ?? 'Guest User' }}
                                    </div>
                                    <div class="text-[11px]" style="color: var(--gz-muted);">
                                        {{ $booking->user?->email ?? 'N/A' }}
                                    </div>
                                </td>

                                <!-- Channel -->
                                <td>
                                    @if($booking->isWalkIn())
                                        <span class="gz-badge text-[10px]" style="background: rgba(179,38,30,0.12); color: var(--red); border: 1px solid var(--red);">
                                            Walk-In
                                        </span>
                                    @else
                                        <span class="gz-badge gz-badge-neutral text-[10px]">
                                            Online
                                        </span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td>
                                    <span class="gz-badge {{ $statusBadgeClass }} text-[10px] capitalize">
                                        {{ $booking->booking_status }}
                                    </span>
                                </td>

                                <!-- Amount -->
                                <td>
                                    <div class="font-mono font-bold text-xs" style="color: var(--gz-ink);">
                                        ₱{{ number_format($fee, 2) }}
                                    </div>
                                    <div class="text-[10px] capitalize" style="color: var(--gz-muted);">
                                        {{ $payment?->payment_status ?? ($booking->booking_status === 'confirmed' ? 'paid' : 'pending') }}
                                    </div>
                                </td>

                                <!-- Actions: View Receipt -->
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        <button
                                            type="button"
                                            @click="$dispatch('open-booking-receipt', {{ $booking->id }})"
                                            class="gz-btn-outline gz-btn-sm inline-flex items-center gap-1.5 text-xs py-1 px-2.5"
                                            title="View Official Receipt"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span>Receipt</span>
                                        </button>

                                        <a
                                            href="{{ route('bookings.receipt', $booking) }}"
                                            target="_blank"
                                            class="p-1 rounded border border-black/15 text-gray-500 hover:text-black hover:border-black/30 transition"
                                            title="Open Printable Receipt in New Tab"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if($bookings->hasPages())
            <div class="p-4 border-t flex justify-between items-center" style="border-color: var(--gz-border);">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
