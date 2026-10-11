<x-admin-layout>
    <x-slot name="heading">Financial Ledger</x-slot>

    <div class="gz-panel gz-panel-body mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="gz-eyebrow">Financial audit & reporting</span>
            <h1 class="gz-font-display font-bold text-xl mt-1">Settlements & Cashflow Archive</h1>
            <p class="text-sm mt-1" style="color: var(--gz-muted);">
                Historical records, monthly revenue performance, and payment breakdown.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="gz-badge-outline font-mono text-xs">
                YEAR {{ now()->format('Y') }}
            </span>
            <button
                type="button"
                @click="$dispatch('open-export-modal', { type: 'financial', period: 'this_month' })"
                class="gz-btn-primary gz-btn-sm flex items-center gap-1.5"
                title="Export financial audit and transaction reports in CSV format"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Export report</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: rgba(229, 168, 35, 0.15); color: var(--gz-pop-dark);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="gz-badge gz-badge-success">Paid</span>
            </div>
            <div class="gz-eyebrow mb-1">This month</div>
            <div class="gz-kpi-value text-right" style="color: var(--gz-pop-dark); font-size: 24px;">
                ₱{{ number_format($monthlyRevenue, 2) }}
            </div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">{{ now()->format('F Y') }} audited</p>
        </div>

        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: rgba(18, 21, 15, 0.06); color: var(--gz-ink);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <span class="gz-badge gz-badge-neutral">Cumulative</span>
            </div>
            <div class="gz-eyebrow mb-1">Year to date</div>
            <div class="gz-kpi-value text-right" style="font-size: 24px;">
                ₱{{ number_format($yearRevenue, 2) }}
            </div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">All settled {{ now()->format('Y') }}</p>
        </div>

        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: rgba(18, 21, 15, 0.06); color: var(--gz-ink);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span class="gz-badge gz-badge-neutral">Count</span>
            </div>
            <div class="gz-eyebrow mb-1">Transactions</div>
            <div class="gz-kpi-value text-right" style="font-size: 24px;">
                {{ number_format($transactionCount) }}
            </div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">Confirmed paid receipts</p>
        </div>
    </div>

    <!-- Channel Breakdown: Online vs Walk-In -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="gz-kpi-card" style="border-left: 4px solid var(--gz-pop-dark);">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-base" style="background: rgba(229, 168, 35, 0.15); color: var(--gz-pop-dark);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    </div>
                    <div>
                        <div class="gz-eyebrow">Online Channel</div>
                        <div class="text-sm font-bold">Online Reservations</div>
                    </div>
                </div>
                <span class="gz-badge gz-badge-neutral">{{ $monthOnlineCount }} this month</span>
            </div>
            <div class="grid grid-cols-2 gap-4 pt-2 border-t" style="border-color: var(--gz-border);">
                <div>
                    <div class="text-xs" style="color: var(--gz-muted);">Total Bookings</div>
                    <div class="text-xl font-bold mt-0.5 text-right">{{ number_format($totalOnlineCount) }}</div>
                </div>
                <div>
                    <div class="text-xs" style="color: var(--gz-muted);">Audited Collections</div>
                    <div class="text-xl font-bold mt-0.5 text-right" style="color: var(--gz-pop-dark);">₱{{ number_format($onlineRevenue, 2) }}</div>
                    <div class="text-[10px] text-right" style="color: var(--gz-muted);">{{ number_format($onlinePaidTransactions) }} receipts</div>
                </div>
            </div>
        </div>

        <div class="gz-kpi-card" style="border-left: 4px solid var(--red);">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-base" style="background: rgba(179,38,30,0.12); color: var(--red);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div>
                        <div class="gz-eyebrow" style="color: var(--red);">On-Site Desk</div>
                        <div class="text-sm font-bold">Walk-In Reservations</div>
                    </div>
                </div>
                <span class="gz-badge" style="background: rgba(179,38,30,0.12); color: var(--red); border: 1px solid var(--red);">{{ $monthWalkInCount }} this month</span>
            </div>
            <div class="grid grid-cols-2 gap-4 pt-2 border-t" style="border-color: var(--gz-border);">
                <div>
                    <div class="text-xs" style="color: var(--gz-muted);">Total Bookings</div>
                    <div class="text-xl font-bold mt-0.5 text-right">{{ number_format($totalWalkInCount) }}</div>
                </div>
                <div>
                    <div class="text-xs" style="color: var(--gz-muted);">Audited Collections</div>
                    <div class="text-xl font-bold mt-0.5 text-right" style="color: var(--red);">₱{{ number_format($walkInRevenue, 2) }}</div>
                    <div class="text-[10px] text-right" style="color: var(--gz-muted);">{{ number_format($walkInPaidTransactions) }} receipts</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 gz-panel">
            <div class="gz-panel-header">
                <div>
                    <h2 class="gz-font-display font-bold text-base">Revenue Trend</h2>
                    <p class="text-xs" style="color: var(--gz-muted);">Last 6 months of historical collections</p>
                </div>
                <span class="gz-badge gz-badge-success">PHP</span>
            </div>

            <div class="gz-panel-body">
                @php
                    $maxRevenue = max($monthlyTotals->max('amount'), 1);
                @endphp
                <div class="flex items-end justify-between gap-3 sm:gap-6 h-56 pt-6 pb-2 border-b" style="border-color: var(--gz-border);">
                    @foreach($monthlyTotals as $month)
                        @php
                            $heightPercent = max(($month['amount'] / $maxRevenue) * 100, $month['amount'] > 0 ? 10 : 3);
                        @endphp
                        <div class="flex-1 flex flex-col items-center h-full justify-end">
                            <div class="text-xs mb-1 text-center whitespace-nowrap" style="color: var(--gz-muted);">
                                ₱{{ number_format($month['amount'], 0) }}
                            </div>

                            <div class="gz-bar-track"
                                 style="height: {{ $heightPercent }}%;"
                                 title="{{ $month['label'] }}: ₱{{ number_format($month['amount'], 2) }}">
                                <div class="gz-bar-fill w-full h-full"></div>
                            </div>

                            <div class="mt-2 text-xs font-semibold text-center">
                                {{ $month['label'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex items-center justify-between text-xs" style="color: var(--gz-muted);">
                    <span>Excludes unconfirmed / cancelled slots</span>
                    <span style="color: var(--gz-pop-dark);">● Paid revenue</span>
                </div>
            </div>
        </div>

        <div class="gz-panel flex flex-col justify-between">
            <div>
                <div class="gz-panel-header">
                    <div>
                        <h2 class="gz-font-display font-bold text-base">Payment Mix</h2>
                        <p class="text-xs" style="color: var(--gz-muted);">By payment method</p>
                    </div>
                    <span class="gz-badge gz-badge-neutral">Share</span>
                </div>

                <div class="gz-panel-body space-y-4">
                    @forelse($methodTotals as $method)
                        @php
                            $maxTotal = max($methodTotals->max('total'), 1);
                            $percentage = round(($method->total / $maxTotal) * 100);
                        @endphp
                        <div class="gz-kpi-card">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-semibold">{{ $method->payment_method ?: 'Direct' }}</span>
                                <span class="text-sm font-bold" style="color: var(--gz-pop-dark);">
                                    ₱{{ number_format($method->total, 2) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-xs mb-2" style="color: var(--gz-muted);">
                                <span>{{ $method->transactions }} transactions</span>
                                <span>{{ $percentage }}% of max</span>
                            </div>

                            <div class="gz-meter-track">
                                <div class="gz-meter-fill" style="width: {{ $percentage }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center">
                            <p class="text-sm" style="color: var(--gz-muted);">No payment records to compile mix.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="p-4 text-center border-t" style="border-color: var(--gz-border);">
                <p class="text-xs" style="color: var(--gz-muted);">Gaoshou Pickleball financial verification system</p>
            </div>
        </div>
    </div>

    {{-- Court Utilization & Revenue Yield Analysis --}}
    <div class="gz-panel mb-8">
        <div class="gz-panel-header flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="gz-eyebrow">Asset performance & revenue yield</span>
                    <span class="gz-badge gz-badge-success">Operational audit</span>
                </div>
                <h2 class="gz-font-display font-bold text-base">Court Utilization & Revenue Yield Analysis</h2>
                <p class="text-xs" style="color: var(--gz-muted);">
                    Evaluation of booked court capacity against operational revenue yield (RevPACH - Revenue per Available Court Hour)
                </p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="gz-badge gz-badge-neutral font-mono">
                    Facility Load: {{ number_format($utilization['month_utilization_rate'], 1) }}%
                </span>
            </div>
        </div>

        {{-- Facility KPI Summary --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4 border-b text-xs" style="border-color: var(--gz-border); background: var(--gz-surface);">
            <div>
                <span class="text-[11px] block" style="color: var(--gz-muted);">Facility MTD Utilization</span>
                <span class="text-base font-bold text-[color:var(--gz-ink)]">
                    {{ number_format($utilization['month_utilization_rate'], 1) }}%
                </span>
                <span class="block text-[10px]" style="color: var(--gz-muted);">{{ number_format($utilization['month_booked_hours'], 1) }}h booked this month</span>
            </div>
            <div>
                <span class="text-[11px] block" style="color: var(--gz-muted);">Monthly Operating Capacity</span>
                <span class="text-base font-bold text-[color:var(--gz-ink)]">
                    {{ number_format($utilization['month_capacity_hours']) }} hrs
                </span>
                <span class="block text-[10px]" style="color: var(--gz-muted);">{{ $utilization['available_courts_count'] }} courts × 16h/day</span>
            </div>
            <div>
                <span class="text-[11px] block" style="color: var(--gz-muted);">Most Productive Court</span>
                <span class="text-base font-bold" style="color: var(--gz-pop-dark);">
                    {{ $utilization['busiest_court_name'] }}
                </span>
                <span class="block text-[10px]" style="color: var(--gz-muted);">{{ number_format($utilization['busiest_court_rate'], 1) }}% load ({{ $utilization['busiest_court_hours'] }}h)</span>
            </div>
            <div>
                <span class="text-[11px] block" style="color: var(--gz-muted);">Peak Booking Window</span>
                <span class="text-base font-bold text-[color:var(--gz-ink)]">
                    {{ $utilization['peak_time_slot'] }}
                </span>
                <span class="block text-[10px]" style="color: var(--gz-muted);">Highest occupancy rate</span>
            </div>
        </div>

        <div class="gz-panel-body overflow-x-auto">
            <table class="gz-table">
                <thead>
                    <tr>
                        <th>Court</th>
                        <th>Status</th>
                        <th>Rate / hr</th>
                        <th>Booked Hours (MTD)</th>
                        <th>Utilization Load</th>
                        <th>Month Collections</th>
                        <th>RevPACH (Yield/hr)</th>
                        <th class="text-right">Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courtUtilization as $cItem)
                        <tr>
                            <td>
                                <div class="font-semibold text-sm">{{ $cItem['court_name'] }}</div>
                                <div class="text-xs" style="color: var(--gz-muted);">{{ $cItem['size'] }}</div>
                            </td>
                            <td>
                                @if($cItem['court_status'] === 'available')
                                    <span class="gz-badge gz-badge-success text-[10px]">Available</span>
                                @elseif($cItem['court_status'] === 'maintenance')
                                    <span class="gz-badge gz-badge-warning text-[10px]">Maintenance</span>
                                @else
                                    <span class="gz-badge gz-badge-danger text-[10px]">Closed</span>
                                @endif
                            </td>
                            <td class="font-mono text-sm">
                                ₱{{ number_format($cItem['price_per_hour'], 2) }}
                            </td>
                            <td>
                                <span class="font-bold text-sm text-[color:var(--gz-ink)]">{{ number_format($cItem['month_booked_hours'], 1) }}h</span>
                                <span class="text-xs opacity-70 block" style="color: var(--gz-muted);">of {{ $cItem['month_capacity_hours'] }}h capacity</span>
                            </td>
                            <td style="min-width: 140px;">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-bold" style="color: var(--gz-pop-dark);">{{ number_format($cItem['month_utilization_rate'], 1) }}%</span>
                                    <span class="text-[10px]" style="color: var(--gz-muted);">{{ $cItem['month_bookings_count'] }} bookings</span>
                                </div>
                                <div class="gz-meter-track">
                                    <div class="gz-meter-fill" style="width: {{ min(max($cItem['month_utilization_rate'], 2), 100) }}%;"></div>
                                </div>
                            </td>
                            <td class="font-mono text-sm font-semibold" style="color: var(--gz-pop-dark);">
                                ₱{{ number_format($cItem['month_revenue'], 2) }}
                            </td>
                            <td>
                                <div class="font-mono text-xs font-bold text-[color:var(--gz-ink)]">
                                    ₱{{ number_format($cItem['rev_pach'], 2) }}/h
                                </div>
                                <span class="text-[10px]" style="color: var(--gz-muted);">capacity yield</span>
                            </td>
                            <td class="text-right font-mono text-sm font-bold text-[color:var(--gz-ink)]">
                                ₱{{ number_format($cItem['revenue_generated'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-6 text-sm" style="color: var(--gz-muted);">
                                No court utilization records available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="gz-panel">
        <div class="gz-panel-header">
            <div>
                <h2 class="gz-font-display font-bold text-base">Master Payment Ledger</h2>
                <p class="text-xs" style="color: var(--gz-muted);">Comprehensive audit trail of all recorded player transactions</p>
            </div>
            <span class="gz-badge gz-badge-neutral">{{ $payments->total() }} total entries</span>
        </div>

        <div class="gz-panel-body overflow-x-auto">
            @if($payments->isEmpty())
                <div class="p-8 text-center border border-dashed" style="border-color: var(--gz-border); background: var(--gz-surface);">
                    <p class="font-semibold">No transactions in ledger</p>
                    <p class="text-sm mt-1" style="color: var(--gz-muted);">Paid bookings will automatically append to this ledger.</p>
                </div>
            @else
                <table class="gz-table">
                    <thead>
                        <tr>
                            <th>Ref No.</th>
                            <th>Player</th>
                            <th>Channel</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td class="text-sm">{{ $payment->ref_num ?: 'PAY-#' . $payment->id }}</td>
                                <td>
                                    <div class="font-semibold text-sm">{{ $payment->booking?->user?->name ?? 'Guest User' }}</div>
                                    <div class="text-xs" style="color: var(--gz-muted);">{{ $payment->booking?->user?->email }}</div>
                                </td>
                                <td>
                                    @if($payment->booking?->isWalkIn())
                                        <span class="gz-badge text-[10px]" style="background: rgba(179,38,30,0.12); color: var(--red); border: 1px solid var(--red);">Walk-In</span>
                                    @else
                                        <span class="gz-badge gz-badge-neutral text-[10px]">Online</span>
                                    @endif
                                </td>
                                <td class="text-sm font-medium capitalize">{{ $payment->payment_method ?: 'Standard' }}</td>
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
                                <td class="text-right">
                                    @if($payment->booking_id)
                                        <button
                                            type="button"
                                            @click="$dispatch('open-booking-receipt', {{ $payment->booking_id }})"
                                            class="gz-btn-outline gz-btn-sm inline-flex items-center gap-1 text-[11px] py-1 px-2"
                                            title="View Official Receipt"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span>Receipt</span>
                                        </button>
                                    @else
                                        <span class="text-xs" style="color: var(--gz-muted);">--</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if($payments->hasPages())
            <div class="p-4 border-t flex justify-between items-center" style="border-color: var(--gz-border);">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>