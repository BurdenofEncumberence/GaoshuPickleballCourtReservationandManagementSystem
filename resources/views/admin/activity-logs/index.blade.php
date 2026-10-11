<x-admin-layout>
    <x-slot name="heading">Activity Logs</x-slot>

    <!-- Header Panel -->
    <div class="gz-panel gz-panel-body mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="gz-eyebrow">System-wide audit trail</span>
            <h1 class="gz-font-display font-bold text-xl mt-1">User Activity Logs</h1>
            <p class="text-sm mt-1" style="color: var(--gz-muted);">
                Complete chronological audit of actions performed across all user types (Admin, Manager, Staff, and Player) with their full name and role.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="gz-badge-outline font-mono text-xs">
                TOTAL LOGS: {{ number_format($kpis['total']) }}
            </span>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="gz-kpi-card">
            <div class="gz-eyebrow mb-1">Total Activities</div>
            <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-right" style="color: var(--gz-ink);">
                {{ number_format($kpis['total']) }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">All recorded events</p>
        </div>

        <div class="gz-kpi-card">
            <div class="gz-eyebrow mb-1">Admin &amp; Manager</div>
            <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-right" style="color: var(--gz-pop-dark);">
                {{ number_format($kpis['admin_manager']) }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Management operations</p>
        </div>

        <div class="gz-kpi-card">
            <div class="gz-eyebrow mb-1">Staff Actions</div>
            <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-amber-600 text-right">
                {{ number_format($kpis['staff']) }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Walk-ins &amp; schedules</p>
        </div>

        <div class="gz-kpi-card">
            <div class="gz-eyebrow mb-1">Player Actions</div>
            <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-emerald-600 text-right">
                {{ number_format($kpis['player']) }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Bookings &amp; registrations</p>
        </div>

        <div class="gz-kpi-card col-span-2 lg:col-span-1">
            <div class="gz-eyebrow mb-1">Today's Activity</div>
            <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-right" style="color: var(--gz-ink);">
                {{ number_format($kpis['today']) }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Logged today</p>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="gz-panel p-4 mb-6" style="background: var(--gz-surface);">
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <!-- Search bar -->
            <div class="sm:col-span-4 relative">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search by User Name, Email, Role, or Activity..."
                    class="gz-input text-xs w-full py-2 pr-8"
                >
                @if($search !== '')
                    <a
                        href="{{ route('admin.activity-logs.index', array_merge(request()->except(['search', 'page']))) }}"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs font-bold opacity-60 hover:opacity-100"
                        title="Clear search"
                    >×</a>
                @endif
            </div>

            <!-- User Role filter -->
            <div class="sm:col-span-3">
                <select name="role" onchange="this.form.submit()" class="gz-input text-xs w-full py-2">
                    <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All User Types</option>
                    <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="manager" {{ $role === 'manager' ? 'selected' : '' }}>Manager</option>
                    <option value="staff" {{ $role === 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="player" {{ $role === 'player' ? 'selected' : '' }}>Player</option>
                </select>
            </div>

            <!-- Action filter -->
            <div class="sm:col-span-3">
                <select name="action" onchange="this.form.submit()" class="gz-input text-xs w-full py-2">
                    <option value="all" {{ $actionFilter === 'all' ? 'selected' : '' }}>All Activity Types</option>
                    @foreach($availableActions as $act)
                        <option value="{{ $act }}" {{ $actionFilter === $act ? 'selected' : '' }}>
                            {{ $act }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date filter -->
            <div class="sm:col-span-2 flex gap-2">
                <select name="date" onchange="this.form.submit()" class="gz-input text-xs w-full py-2">
                    <option value="all" {{ $dateFilter === 'all' ? 'selected' : '' }}>All Dates</option>
                    <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="week" {{ $dateFilter === 'week' ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ $dateFilter === 'month' ? 'selected' : '' }}>This Month</option>
                </select>

                @if($role !== 'all' || $actionFilter !== 'all' || $dateFilter !== 'all' || $search !== '')
                    <a href="{{ route('admin.activity-logs.index') }}" class="gz-btn-outline gz-btn-sm shrink-0 flex items-center" title="Reset filters">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Activity Logs Table -->
    <div class="gz-panel overflow-hidden">
        @if($logs->isEmpty())
            <div class="p-10 text-center text-sm" style="color: var(--gz-muted);">
                No activity logs matched your current filters.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="gz-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User Name</th>
                            <th>User Type</th>
                            <th>Activity</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            @php
                                $roleLower = strtolower($log->user_role ?? 'player');
                                $roleBadgeStyle = match($roleLower) {
                                    'admin' => 'background: rgba(239, 68, 68, 0.14); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.35);',
                                    'manager' => 'background: rgba(229, 168, 35, 0.16); color: var(--gz-pop-dark); border: 1px solid rgba(229, 168, 35, 0.4);',
                                    'staff' => 'background: rgba(59, 130, 246, 0.14); color: #2563eb; border: 1px solid rgba(59, 130, 246, 0.35);',
                                    default => 'background: rgba(16, 185, 129, 0.14); color: #059669; border: 1px solid rgba(16, 185, 129, 0.35);',
                                };
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap">
                                    <div class="font-semibold text-xs" style="color: var(--gz-ink);">
                                        {{ $log->created_at?->format('M d, Y · h:i A') }}
                                    </div>
                                    <div class="text-[11px] font-mono mt-0.5" style="color: var(--gz-muted);">
                                        {{ $log->created_at?->diffForHumans() }}
                                    </div>
                                </td>
                                <td>
                                    <div class="font-bold text-sm" style="color: var(--gz-ink);">
                                        {{ $log->user_name }}
                                    </div>
                                    @if($log->user_email)
                                        <div class="text-xs font-mono" style="color: var(--gz-muted);">
                                            {{ $log->user_email }}
                                        </div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    <span class="inline-block text-[10px] font-mono font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full" style="{{ $roleBadgeStyle }}">
                                        {{ strtoupper($roleLower) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap">
                                    <span class="font-semibold text-xs" style="color: var(--gz-ink);">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="text-xs max-w-md" style="color: var(--gz-muted);">
                                    {{ $log->description }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-4 border-t" style="border-color: var(--gz-border);">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>
</x-admin-layout>
