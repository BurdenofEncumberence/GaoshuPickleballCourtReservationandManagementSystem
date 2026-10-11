<x-admin-layout>
    <x-slot name="heading">Court Inventory</x-slot>

    {{-- Top Action Bar --}}
    <div class="gz-panel gz-panel-body mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-block w-2 h-2 rounded-full" style="background: var(--gz-pop);"></span>
                <span class="gz-eyebrow" style="color: var(--gz-pop-dark);">Arena assets & configuration</span>
            </div>
            <h1 class="gz-font-display font-bold text-xl">Court Roster & Hourly Rates</h1>
            <p class="text-sm mt-1" style="color: var(--gz-muted);">
                Manage court availability, operational status, and pricing rules.
            </p>
        </div>
        @if(Auth::user()->hasPermission('manage_courts'))
            <a href="{{ route('admin.courts.create') }}" class="gz-btn-primary gz-btn-sm">
                + Add court
            </a>
        @endif
    </div>

    {{-- Session Feedback Messages --}}
    @if(session('status'))
        <div class="gz-status mb-6">
            {{ session('status') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 text-sm font-semibold p-3" style="background: var(--gz-danger-bg); border: 1px solid rgba(196, 69, 58, 0.35); color: var(--gz-danger);">
            {{ session('error') }}
        </div>
    @endif

    {{-- KPI Metric Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="gz-eyebrow">Total courts</span>
                <span class="gz-badge gz-badge-neutral">All</span>
            </div>
            <div class="gz-kpi-value text-right">{{ $courts->count() }}</div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">Registered arenas</p>
        </div>

        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="gz-eyebrow">Available</span>
                <span class="gz-badge gz-badge-success">Active</span>
            </div>
            <div class="gz-kpi-value text-right" style="color: var(--gz-pop-dark);">
                {{ $courts->where('court_status', 'available')->count() }}
            </div>
            <p class="text-xs mt-2" style="color: var(--gz-pop-dark);">Open for bookings</p>
        </div>

        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="gz-eyebrow">Maintenance</span>
                <span class="gz-badge {{ $courts->where('court_status', '!=', 'available')->count() > 0 ? 'gz-badge-warning' : 'gz-badge-neutral' }}">
                    {{ $courts->where('court_status', '!=', 'available')->count() > 0 ? 'Hold' : 'Clear' }}
                </span>
            </div>
            <div class="gz-kpi-value text-right" style="color: {{ $courts->where('court_status', '!=', 'available')->count() > 0 ? 'var(--gz-warning)' : 'inherit' }};">
                {{ $courts->where('court_status', '!=', 'available')->count() }}
            </div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">Offline / repair</p>
        </div>

        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="gz-eyebrow">Fleet Utilization</span>
                <span class="gz-badge gz-badge-success">MTD Load</span>
            </div>
            <div class="gz-kpi-value text-right" style="color: var(--gz-pop-dark);">
                {{ number_format($utilization['month_utilization_rate'] ?? 0, 1) }}%
            </div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">{{ number_format($utilization['month_booked_hours'] ?? 0, 1) }}h booked this month</p>
        </div>
    </div>

    {{-- Courts Inventory Table --}}
    <div class="gz-panel">
        <div class="gz-panel-header">
            <div>
                <h2 class="gz-font-display font-bold text-base">Court Inventory Matrix</h2>
                <p class="text-xs" style="color: var(--gz-muted);">Active configuration for the public booking engine</p>
            </div>
            <span class="gz-badge gz-badge-neutral">{{ $courts->count() }} courts</span>
        </div>

        <div class="gz-panel-body overflow-x-auto">
            @if($courts->isEmpty())
                <div class="p-8 text-center border border-dashed" style="border-color: var(--gz-border); background: var(--gz-surface);">
                    <p class="font-semibold">No courts found</p>
                    <p class="text-sm mt-1" style="color: var(--gz-muted);">Add your first court to open public appointments.</p>
                </div>
            @else
                <table class="gz-table">
                    <thead>
                        <tr>
                            <th>Court</th>
                            <th>Dimensions</th>
                            <th>Rate / hour</th>
                            <th>Total bookings</th>
                            <th>Utilization (MTD)</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courts as $court)
                            @php
                                $cUtil = collect($courtUtilization ?? [])->firstWhere('id', $court->id);
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex items-center justify-center font-bold text-sm" style="width: 28px; height: 28px; background: var(--gz-bg); border: 1.5px solid var(--gz-border); color: var(--gz-ink);">
                                            {{ strtoupper(substr($court->court_name, 0, 1)) }}
                                        </div>
                                        <span class="font-semibold text-sm">{{ $court->court_name }}</span>
                                    </div>
                                </td>
                                <td class="text-sm" style="color: var(--gz-muted);">
                                    {{ $court->size ?: 'Standard' }}
                                </td>
                                <td class="text-sm font-semibold" style="color: var(--gz-pop-dark);">
                                    ₱{{ number_format($court->price_per_hour, 2) }}
                                </td>
                                <td class="text-sm">
                                    {{ $court->bookings_count }}
                                </td>
                                <td style="min-width: 140px;">
                                    @if($cUtil)
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-bold" style="color: var(--gz-pop-dark);">{{ number_format($cUtil['month_utilization_rate'], 1) }}%</span>
                                            <span class="text-[10px]" style="color: var(--gz-muted);">{{ number_format($cUtil['month_booked_hours'], 1) }}h</span>
                                        </div>
                                        <div class="gz-meter-track">
                                            <div class="gz-meter-fill" style="width: {{ min(max($cUtil['month_utilization_rate'], 2), 100) }}%;"></div>
                                        </div>
                                    @else
                                        <span class="text-xs" style="color: var(--gz-muted);">0.0%</span>
                                    @endif
                                </td>
                                <td>
                                    @if($court->court_status === 'available')
                                        <span class="gz-badge gz-badge-success">Available</span>
                                    @elseif($court->court_status === 'maintenance')
                                        <span class="gz-badge gz-badge-warning">Maintenance</span>
                                    @else
                                        <span class="gz-badge gz-badge-danger">Closed</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('admin.courts.edit', $court) }}" class="gz-btn-outline gz-btn-sm mr-2">
                                        Edit
                                    </a>
                                    @if($court->bookings_count === 0)
                                        <form class="inline" method="POST" action="{{ route('admin.courts.destroy', $court) }}" onsubmit="return confirm('Remove this court from inventory?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="gz-btn-sm font-bold" style="background: var(--gz-danger-bg); color: var(--gz-danger); border: 1.5px solid rgba(196, 69, 58, 0.35);">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-admin-layout>