<x-admin-layout>
    <x-slot name="heading">Operations Overview</x-slot>

    <div class="gz-panel gz-panel-body mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-block w-2 h-2 rounded-full" style="background: var(--gz-pop);"></span>
                <span class="gz-eyebrow" style="color: var(--gz-pop-dark);">System online · HQ pulse</span>
            </div>
            <h1 class="gz-font-display font-bold text-xl">
                Welcome, {{ Str::before(Auth::user()->name, ' ') }}.
            </h1>
            <p class="text-sm mt-1" style="color: var(--gz-muted);">
                Operational schedule and activity report for {{ $today->format('l, F j, Y') }}.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if(Auth::user()->hasPermission('manage_courts'))
                <a href="{{ route('admin.courts.create') }}" class="gz-btn-primary gz-btn-sm">
                    + Add court
                </a>
            @endif
            @if(Auth::user()->hasPermission('view_finances'))
                <a href="{{ route('admin.finance') }}" class="gz-btn-outline gz-btn-sm">
                    Financial report
                </a>
            @endif
            <button
                type="button"
                @click="$dispatch('open-export-modal', { type: 'overall', period: 'this_month' })"
                class="gz-btn-outline gz-btn-sm flex items-center gap-1.5"
                title="Export operational and financial reports in CSV format"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Export reports</span>
            </button>
        </div>
    </div>

    {{-- 4 KPI Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Metric 1: Month Revenue --}}
        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: rgba(229, 168, 35, 0.15); color: var(--gz-pop-dark);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="gz-badge gz-badge-success">AUDITED</span>
            </div>
            <div class="gz-eyebrow mb-1">Month revenue</div>
            <div class="gz-kpi-value text-right" style="color: var(--gz-pop-dark); font-size: 24px;">
                ₱{{ number_format($monthlyRevenue, 2) }}
            </div>
            <p class="text-xs mt-2 font-semibold flex items-center gap-1" style="color: {{ $revenueChange !== null ? ($revenueChange >= 0 ? 'var(--gz-pop-dark)' : 'var(--gz-danger)') : 'var(--gz-muted)' }};">
                @if($revenueChange !== null)
                    <span>{{ $revenueChange >= 0 ? '▲ +' : '▼ ' }}{{ $revenueChange }}%</span>
                    <span style="color: var(--gz-muted); font-weight: normal;">vs last mo</span>
                @else
                    Base month benchmark
                @endif
            </p>
        </div>

        {{-- Metric 2: Court Fleet --}}
        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: rgba(18, 21, 15, 0.06); color: var(--gz-ink);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM4 12h16M12 4v16" />
                    </svg>
                </div>
                <span class="gz-badge gz-badge-success">Live Fleet</span>
            </div>
            <div class="gz-eyebrow mb-1">Court fleet</div>
            <div class="gz-kpi-value text-right" style="font-size: 24px;">
                {{ $courts->where('court_status', 'available')->count() }}
                <span class="text-sm font-normal" style="color: var(--gz-muted);">/ {{ $courts->count() }}</span>
            </div>
            <p class="text-xs mt-2 font-semibold" style="color: var(--gz-pop-dark);">Ready for reservation</p>
        </div>

        {{-- Metric 3: Today Matches --}}
        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: rgba(18, 21, 15, 0.06); color: var(--gz-ink);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="gz-badge gz-badge-neutral">{{ $today->format('M j') }}</span>
            </div>
            <div class="gz-eyebrow mb-1">Today matches</div>
            <div class="gz-kpi-value text-right" style="font-size: 24px;">
                {{ $todayBookings->count() }}
            </div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">
                <span class="font-bold text-[color:var(--gz-ink)]">{{ $todayOnlineCount }} Online</span> · 
                <span class="font-bold text-[color:var(--red)]">{{ $todayWalkInCount }} Walk-in</span>
            </p>
        </div>

        {{-- Metric 4: Fleet Court Utilization --}}
        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: rgba(229, 168, 35, 0.15); color: var(--gz-pop-dark);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <span class="gz-badge gz-badge-success">MTD LOAD</span>
            </div>
            <div class="gz-eyebrow mb-1">Court utilization</div>
            <div class="gz-kpi-value text-right" style="color: var(--gz-pop-dark); font-size: 24px;">
                {{ number_format($utilization['month_utilization_rate'], 1) }}%
            </div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">
                <span class="font-bold text-[color:var(--gz-ink)]">{{ number_format($utilization['today_utilization_rate'], 1) }}% today</span> · 
                <span>{{ $utilization['month_booked_hours'] }}h booked</span>
            </p>
        </div>
    </div>

    {{-- Channel Breakdown Summary: Online vs Walk-In --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        {{-- Online Channel --}}
        <div class="gz-panel gz-panel-body" style="border-left: 4px solid var(--gz-border);">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="gz-badge gz-badge-neutral text-xs">Web Portal</span>
                        <span class="text-xs" style="color: var(--gz-muted);">Self-Service</span>
                    </div>
                    <h3 class="gz-font-display font-bold text-base">Online Reservations</h3>
                </div>
                <div class="text-right">
                    <div class="font-mono font-bold text-2xl text-[color:var(--gz-ink)]">
                        {{ number_format($totalOnlineCount) }}
                    </div>
                    <div class="text-[11px]" style="color: var(--gz-muted);">Total reservations</div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t flex items-center justify-between text-xs" style="border-color: var(--gz-border);">
                <div>
                    <span style="color: var(--gz-muted);">Today:</span>
                    <span class="font-bold font-mono ml-1">{{ $todayOnlineCount }} sessions</span>
                </div>
                <div>
                    <span style="color: var(--gz-muted);">This Month:</span>
                    <span class="font-bold font-mono ml-1">{{ $monthOnlineCount }}</span>
                </div>
                <div>
                    <span style="color: var(--gz-muted);">Collections:</span>
                    <span class="font-bold font-mono ml-1" style="color: var(--gz-pop-dark);">₱{{ number_format($onlineRevenue, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Walk-In Channel --}}
        <div class="gz-panel gz-panel-body" style="border-left: 4px solid var(--red);">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="gz-badge text-xs" style="background: rgba(179,38,30,0.12); color: var(--red); border: 1px solid var(--red);">Front Desk</span>
                        <span class="text-xs" style="color: var(--gz-muted);">On-the-spot</span>
                    </div>
                    <h3 class="gz-font-display font-bold text-base">Walk-In Reservations</h3>
                </div>
                <div class="text-right">
                    <div class="font-mono font-bold text-2xl" style="color: var(--red);">
                        {{ number_format($totalWalkInCount) }}
                    </div>
                    <div class="text-[11px]" style="color: var(--gz-muted);">Total reservations</div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t flex items-center justify-between text-xs" style="border-color: var(--gz-border);">
                <div>
                    <span style="color: var(--gz-muted);">Today:</span>
                    <span class="font-bold font-mono ml-1">{{ $todayWalkInCount }} sessions</span>
                </div>
                <div>
                    <span style="color: var(--gz-muted);">This Month:</span>
                    <span class="font-bold font-mono ml-1">{{ $monthWalkInCount }}</span>
                </div>
                <div>
                    <span style="color: var(--gz-muted);">Collections:</span>
                    <span class="font-bold font-mono ml-1" style="color: var(--red);">₱{{ number_format($walkInRevenue, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    @if(isset($eventsCount))
        <div class="mb-8">
            <div class="gz-kpi-card flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base" style="background: rgba(229, 168, 35, 0.15); color: var(--gz-pop-dark);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <div>
                        <div class="gz-eyebrow">Promos & Discounts</div>
                        <div class="text-sm font-bold">{{ $eventsCount }} Active Promos & Special Offers</div>
                    </div>
                </div>
                <a href="{{ route('admin.events.index') }}" class="gz-btn-outline gz-btn-sm">
                    Manage Promos →
                </a>
            </div>
        </div>
    @endif

    {{-- Court Utilization & Capacity Analytics Report --}}
    <div class="gz-panel mb-8">
        <div class="gz-panel-header flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="gz-eyebrow">Fleet load & capacity analytics</span>
                    <span class="gz-badge gz-badge-success">Live metrics</span>
                </div>
                <h2 class="gz-font-display font-bold text-base">Court Utilization Metrics</h2>
                <p class="text-xs" style="color: var(--gz-muted);">
                    Based on standard {{ $utilization['operating_hours_per_day'] }}-hour operating schedule (6:00 AM – 10:00 PM) across {{ $utilization['total_courts_count'] }} courts
                </p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="gz-badge gz-badge-neutral font-mono">
                    Facility load: {{ number_format($utilization['month_utilization_rate'], 1) }}%
                </span>
                @if(Auth::user()->hasPermission('view_finances'))
                    <a href="{{ route('admin.finance') }}" class="gz-link text-xs">View revenue yield →</a>
                @endif
            </div>
        </div>

        {{-- Facility Utilization Highlights Bar --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4 border-b text-xs" style="border-color: var(--gz-border); background: var(--gz-surface);">
            <div>
                <span class="text-[11px] block" style="color: var(--gz-muted);">Today's Utilization</span>
                <span class="text-base font-bold text-[color:var(--gz-ink)]">
                    {{ number_format($utilization['today_utilization_rate'], 1) }}%
                </span>
                <span class="block text-[10px]" style="color: var(--gz-muted);">{{ $utilization['today_booked_hours'] }}h / {{ $utilization['today_capacity_hours'] }}h capacity</span>
            </div>
            <div>
                <span class="text-[11px] block" style="color: var(--gz-muted);">Month Booked Hours</span>
                <span class="text-base font-bold" style="color: var(--gz-pop-dark);">
                    {{ number_format($utilization['month_booked_hours'], 1) }} hrs
                </span>
                <span class="block text-[10px]" style="color: var(--gz-muted);">of {{ number_format($utilization['month_capacity_hours']) }}h total capacity</span>
            </div>
            <div>
                <span class="text-[11px] block" style="color: var(--gz-muted);">Busiest Court</span>
                <span class="text-base font-bold text-[color:var(--gz-ink)]">
                    {{ $utilization['busiest_court_name'] }}
                </span>
                <span class="block text-[10px]" style="color: var(--gz-pop-dark);">{{ number_format($utilization['busiest_court_rate'], 1) }}% load</span>
            </div>
            <div>
                <span class="text-[11px] block" style="color: var(--gz-muted);">Peak Booking Hour</span>
                <span class="text-base font-bold text-[color:var(--gz-ink)]">
                    {{ $utilization['peak_time_slot'] }}
                </span>
                <span class="block text-[10px]" style="color: var(--gz-muted);">Most requested slot</span>
            </div>
        </div>

        {{-- Court Fleet Utilization Cards --}}
        <div class="gz-panel-body">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($courtUtilization as $courtItem)
                    <div class="gz-kpi-card flex flex-col justify-between" style="border-left: 3px solid {{ $courtItem['is_available'] ? 'var(--gz-pop)' : 'var(--gz-border)' }};">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-bold text-sm text-[color:var(--gz-ink)]">
                                    {{ $courtItem['court_name'] }}
                                </h3>
                                @if($courtItem['court_status'] === 'available')
                                    <span class="gz-badge gz-badge-success text-[10px]">Available</span>
                                @elseif($courtItem['court_status'] === 'maintenance')
                                    <span class="gz-badge gz-badge-warning text-[10px]">Maintenance</span>
                                @else
                                    <span class="gz-badge gz-badge-danger text-[10px]">Closed</span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between text-xs mb-3" style="color: var(--gz-muted);">
                                <span>{{ $courtItem['size'] }}</span>
                                <span class="font-semibold text-[color:var(--gz-ink)]">₱{{ number_format($courtItem['price_per_hour'], 2) }}/hr</span>
                            </div>

                            {{-- Utilization Meter --}}
                            <div class="mb-3">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="font-medium" style="color: var(--gz-muted);">Monthly Utilization</span>
                                    <span class="font-bold" style="color: var(--gz-pop-dark);">
                                        {{ number_format($courtItem['month_utilization_rate'], 1) }}%
                                    </span>
                                </div>
                                <div class="gz-meter-track">
                                    <div class="gz-meter-fill" style="width: {{ min(max($courtItem['month_utilization_rate'], 2), 100) }}%;"></div>
                                </div>
                            </div>

                            {{-- Detailed Metrics List --}}
                            <div class="space-y-1.5 pt-2 border-t text-xs" style="border-color: var(--gz-border);">
                                <div class="flex items-center justify-between">
                                    <span style="color: var(--gz-muted);">Today's Load:</span>
                                    <span class="font-semibold text-[color:var(--gz-ink)]">
                                        {{ number_format($courtItem['today_utilization_rate'], 1) }}% ({{ $courtItem['today_booked_hours'] }}h / 16h)
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span style="color: var(--gz-muted);">Month Reserved:</span>
                                    <span class="font-semibold text-[color:var(--gz-ink)]">
                                        {{ $courtItem['month_booked_hours'] }}h / {{ $courtItem['month_capacity_hours'] }}h
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span style="color: var(--gz-muted);">Sessions:</span>
                                    <span class="font-semibold text-[color:var(--gz-ink)]">
                                        {{ $courtItem['month_bookings_count'] }} this mo · {{ $courtItem['total_bookings_count'] }} total
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span style="color: var(--gz-muted);">Peak Slot:</span>
                                    <span class="font-semibold text-[color:var(--gz-ink)]">
                                        {{ $courtItem['peak_time_slot'] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t flex items-center justify-between text-xs" style="border-color: var(--gz-border);">
                            <span style="color: var(--gz-muted);">Revenue Generated:</span>
                            <span class="font-bold font-mono" style="color: var(--gz-pop-dark);">
                                ₱{{ number_format($courtItem['revenue_generated'], 2) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-center py-6" style="color: var(--gz-muted);">No courts available to compute utilization.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 gz-panel">
            <div class="gz-panel-header">
                <div>
                    <h2 class="gz-font-display font-bold text-base">Today's Court Run-Sheet</h2>
                    <p class="text-xs" style="color: var(--gz-muted);">
                        Schedule and active court roster for {{ $today->format('D, M d, Y') }}
                    </p>
                </div>
                <a href="{{ route('admin.courts.index') }}" class="gz-link text-xs">Manage courts →</a>
            </div>

            <div class="gz-panel-body overflow-x-auto">
                @if($todayBookings->isEmpty())
                    <div class="p-8 text-center border border-dashed" style="border-color: var(--gz-border); background: var(--gz-surface);">
                        <p class="font-semibold">No matches slated today</p>
                        <p class="text-sm mt-1" style="color: var(--gz-muted);">All courts are clear or open for reservation.</p>
                    </div>
                @else
                    <table class="gz-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Channel</th>
                                <th>Court</th>
                                <th>Player</th>
                                <th>Status</th>
                                <th>Staff Audit</th>
                                <th class="text-right">Fee</th>
                                <th class="text-right">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todayBookings as $booking)
                                <tr>
                                    <td class="text-sm whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }}
                                        –
                                        {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
                                    </td>
                                    <td>
                                        @if($booking->isWalkIn())
                                            <span class="gz-badge text-[10px]" style="background: rgba(179,38,30,0.12); color: var(--red); border: 1px solid var(--red);">Walk-In</span>
                                        @else
                                            <span class="gz-badge gz-badge-neutral text-[10px]">Online</span>
                                        @endif
                                    </td>
                                    <td class="font-semibold text-sm">
                                        {{ $booking->court?->court_name ?? 'Court Removed' }}
                                    </td>
                                    <td>
                                        <div class="font-semibold text-sm">{{ $booking->user?->name ?? 'Guest / Player' }}</div>
                                        <div class="text-xs" style="color: var(--gz-muted);">{{ $booking->user?->email }}</div>
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match($booking->booking_status) {
                                                'confirmed' => 'gz-badge-success',
                                                'show' => 'gz-badge-success',
                                                'pending' => 'gz-badge-warning',
                                                'cancelled' => 'gz-badge-danger',
                                                default => 'gz-badge-neutral',
                                            };
                                        @endphp
                                        <span class="gz-badge {{ $badgeClass }}">{{ ucfirst($booking->booking_status) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $dashPayment = $booking->payments->first();
                                            $dashCashier = $dashPayment?->processor?->name 
                                                ?? ($booking->isWalkIn() ? $booking->statusUpdatedBy?->name : null);
                                        @endphp
                                        @if($dashCashier)
                                            <div class="text-xs font-semibold text-gray-800 flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                                <span>Cashier: {{ $dashCashier }}</span>
                                            </div>
                                        @elseif($booking->isWalkIn())
                                            <div class="text-[11px] text-gray-500">
                                                <span class="font-medium text-gray-700">Cashier:</span> Front Desk Staff
                                            </div>
                                        @endif

                                        @if($booking->statusUpdatedBy && $booking->statusUpdatedBy->name !== $dashCashier)
                                            <div class="text-xs font-semibold text-emerald-800 mt-1 flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0"></span>
                                                <span>Marked: {{ $booking->statusUpdatedBy->name }}</span>
                                            </div>
                                            @if($booking->status_updated_at)
                                                <div class="text-[10px] text-gray-500 pl-2.5">
                                                    {{ $booking->status_updated_at->format('g:i A') }}
                                                </div>
                                            @endif
                                        @elseif(!$booking->statusUpdatedBy)
                                            <div class="text-[11px] text-gray-400 mt-0.5">
                                                Attendance: <span class="italic">Pending</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-right text-sm font-semibold">
                                        ₱{{ number_format($booking->court?->price_per_hour ?? 0, 2) }}
                                    </td>
                                    <td class="text-right">
                                        <button
                                            type="button"
                                            @click="$dispatch('open-booking-receipt', {{ $booking->id }})"
                                            class="gz-btn-outline gz-btn-sm inline-flex items-center gap-1 text-[11px] py-1 px-2"
                                            title="View Official Receipt"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span>Receipt</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="gz-panel flex flex-col justify-between">
            <div>
                <div class="gz-panel-header">
                    <div>
                        <h2 class="gz-font-display font-bold text-base">Court Health</h2>
                        <p class="text-xs" style="color: var(--gz-muted);">Arena fleet status</p>
                    </div>
                    <span class="gz-badge gz-badge-success">● Active</span>
                </div>

                <div class="gz-panel-body space-y-4">
                    @forelse($courtUtilization as $courtItem)
                        <div class="gz-kpi-card">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-semibold">{{ $courtItem['court_name'] }}</span>
                                @if($courtItem['court_status'] === 'available')
                                    <span class="gz-badge gz-badge-success">Online</span>
                                @elseif($courtItem['court_status'] === 'maintenance')
                                    <span class="gz-badge gz-badge-warning">Maint</span>
                                @else
                                    <span class="gz-badge gz-badge-danger">Closed</span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between text-xs mb-2" style="color: var(--gz-muted);">
                                <span>{{ $courtItem['size'] }}</span>
                                <span class="font-bold" style="color: var(--gz-pop-dark);">{{ number_format($courtItem['month_utilization_rate'], 1) }}% load ({{ $courtItem['month_booked_hours'] }}h)</span>
                            </div>

                            <div class="gz-meter-track">
                                <div class="gz-meter-fill" style="width: {{ min(max($courtItem['month_utilization_rate'], $courtItem['is_available'] ? 4 : 0), 100) }}%; {{ !$courtItem['is_available'] && $courtItem['court_status'] !== 'maintenance' ? 'background: var(--gz-danger);' : '' }} {{ $courtItem['court_status'] === 'maintenance' ? 'background: var(--gz-warning);' : '' }}"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-center py-6" style="color: var(--gz-muted);">No courts registered in fleet.</p>
                    @endforelse
                </div>
            </div>

            <div class="p-4 border-t" style="border-color: var(--gz-border);">
                <a href="{{ route('admin.courts.index') }}" class="gz-btn-outline w-full justify-center text-sm">
                    Configure arena inventory →
                </a>
            </div>
        </div>
    </div>

    <div class="gz-panel">
        <div class="gz-panel-header">
            <div>
                <h2 class="gz-font-display font-bold text-base">Recent Payment Audit Ledger</h2>
                <p class="text-xs" style="color: var(--gz-muted);">Latest transactions processed across all court bookings</p>
            </div>
            @if(Auth::user()->hasPermission('view_finances'))
                <a href="{{ route('admin.finance') }}" class="gz-link text-xs">Expand to full financials →</a>
            @endif
        </div>

        <div class="gz-panel-body overflow-x-auto">
            @if($recentPayments->isEmpty())
                <div class="p-8 text-center border border-dashed" style="border-color: var(--gz-border); background: var(--gz-surface);">
                    <p class="font-semibold">No recent payments logged</p>
                    <p class="text-sm mt-1" style="color: var(--gz-muted);">New completed customer payments will appear here in real time.</p>
                </div>
            @else
                <table class="gz-table">
                    <thead>
                        <tr>
                            <th>Ref No.</th>
                            <th>Player</th>
                            <th>Channel</th>
                            <th>Staff / Cashier</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentPayments as $payment)
                            <tr>
                                <td class="text-sm">{{ $payment->ref_num ?: 'PAY-#' . $payment->id }}</td>
                                <td class="text-sm font-semibold">{{ $payment->booking?->user?->name ?? 'Guest User' }}</td>
                                <td>
                                    @if($payment->booking?->isWalkIn())
                                        <span class="gz-badge text-[10px]" style="background: rgba(179,38,30,0.12); color: var(--red); border: 1px solid var(--red);">Walk-In</span>
                                    @else
                                        <span class="gz-badge gz-badge-neutral text-[10px]">Online</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $handler = $payment->processor?->name 
                                            ?? ($payment->booking?->isWalkIn() ? $payment->booking->statusUpdatedBy?->name : null);
                                    @endphp
                                    @if($handler)
                                        <div class="font-semibold text-xs text-gray-900">{{ $handler }}</div>
                                        <span class="text-[10px] text-emerald-700 font-medium">Cashier</span>
                                    @elseif($payment->booking?->isWalkIn())
                                        <span class="text-xs text-gray-500">Front Desk Staff</span>
                                    @else
                                        <span class="text-xs text-gray-400">Online Gateway</span>
                                    @endif
                                    @if($payment->booking?->statusUpdatedBy && $payment->booking->statusUpdatedBy->name !== $handler)
                                        <div class="text-[10px] text-gray-500 mt-0.5">
                                            Marked: {{ $payment->booking->statusUpdatedBy->name }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-sm" style="color: var(--gz-muted);">
                                    {{ \Carbon\Carbon::parse($payment->date)->format('M d, Y') }}
                                </td>
                                <td>
                                    <span class="gz-badge {{ $payment->payment_status === 'paid' ? 'gz-badge-success' : 'gz-badge-warning' }}">
                                        {{ ucfirst($payment->payment_status) }}
                                    </span>
                                </td>
                                <td class="text-right font-semibold" style="color: var(--gz-pop-dark);">
                                    ₱{{ number_format($payment->amount, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <script>
        if (typeof window.renderPixelGrid === 'undefined') {
            window.renderPixelGrid = function(id, rows, colorMap) {
                const el = document.getElementById(id);
                if (!el) return;
                el.innerHTML = '';
                rows.forEach(row => {
                    [...row].forEach(ch => {
                        const cell = document.createElement('div');
                        cell.style.background = colorMap[ch] || 'transparent';
                        cell.style.width = '100%';
                        cell.style.height = '100%';
                        el.appendChild(cell);
                    });
                });
            };
        }
        window.renderPixelGrid('icon-revenue', [
            "........", ".1111...", ".1....1.", ".1.11.1.", ".1.11.1.", ".1....1.", ".1111...", "........"
        ], { '.': 'transparent', '1': '#FCFBF7' });
        window.renderPixelGrid('icon-fleet', [
            "........", "..11....", ".1..1...", ".1..1...", ".11111..", ".1..1...", ".1..1...", "........"
        ], { '.': 'transparent', '1': '#FCFBF7' });
        window.renderPixelGrid('icon-matches', [
            "........", "..11....", ".1111...", "..11....", "..11....", "..11....", ".1111...", "........"
        ], { '.': 'transparent', '1': '#FCFBF7' });
        window.renderPixelGrid('icon-audits', [
            "........", ".1111...", ".1..1...", ".1..1...", ".1..1...", ".1..1...", ".1111...", "........"
        ], { '.': 'transparent', '1': '#FCFBF7' });
        window.renderPixelGrid('icon-events', [
            "........", "..1.....", ".111....", "..1.....", "..1.....", ".11111..", "........", "........"
        ], { '.': 'transparent', '1': '#FCFBF7' });
    </script>
</x-admin-layout>