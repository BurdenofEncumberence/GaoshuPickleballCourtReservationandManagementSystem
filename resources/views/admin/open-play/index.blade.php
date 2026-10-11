<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="gz-font-display font-bold text-xl sm:text-2xl">Open Play & Tournaments</h1>
                <p class="text-xs mt-1" style="color: var(--gz-muted);">
                    Manage communal open play sessions and tournament brackets with per-slot player capacity and court allocations.
                </p>
            </div>
            @if(Auth::user()->isManager())
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.open-play.create') }}" class="gz-btn-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Create Session</span>
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    {{-- Status Flash Alert --}}
    @if (session('status'))
        <div class="mb-4 p-4 rounded-xl text-sm border" style="background: rgba(229, 168, 35, 0.08); border-color: rgba(229, 168, 35, 0.3); color: var(--gz-ink);">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" style="color: var(--gz-pop-dark);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    {{-- Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="gz-kpi-card">
            <span class="gz-kpi-label">Upcoming Sessions</span>
            <div class="gz-kpi-value mt-1 text-right">{{ number_format($upcomingCount) }}</div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Active sessions accepting player registrations</p>
        </div>

        <a href="{{ route('admin.open-play.index', ['status' => 'pending_approval']) }}" class="gz-kpi-card block hover:border-black/40 dark:hover:border-white/40 transition">
            <div class="flex items-center justify-between">
                <span class="gz-kpi-label">Player Host Requests</span>
                @if(($pendingRequestsCount ?? 0) > 0)
                    <span class="gz-badge text-[10px] font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300">Action Required</span>
                @endif
            </div>
            <div class="gz-kpi-value mt-1 flex items-baseline justify-end gap-2 text-right">
                <span class="{{ ($pendingRequestsCount ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400 font-extrabold' : '' }}">{{ number_format($pendingRequestsCount ?? 0) }}</span>
                <span class="text-xs font-normal" style="color: var(--gz-muted);">pending</span>
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Proposals submitted by players awaiting review</p>
        </a>

        <div class="gz-kpi-card">
            <span class="gz-kpi-label">Total Player Registrations</span>
            <div class="gz-kpi-value mt-1 text-right">{{ number_format($totalRegistrations) }}</div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Total player slot tickets issued</p>
        </div>

        <div class="gz-kpi-card">
            <span class="gz-kpi-label">Open Play Revenue</span>
            <div class="gz-kpi-value mt-1 text-right">₱{{ number_format($totalOpenPlayRevenue, 2) }}</div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Total collected participation fees</p>
        </div>
    </div>

    {{-- Pending Host Requests Alert for Managers --}}
    @if(($pendingRequestsCount ?? 0) > 0 && Auth::user()->isManager())
        <div class="mb-6 p-4 rounded-xl flex items-center justify-between flex-wrap gap-3" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3);">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-amber-500/20 text-amber-600 dark:text-amber-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-sm text-amber-800 dark:text-amber-300">
                        {{ $pendingRequestsCount }} Player Host Request{{ $pendingRequestsCount > 1 ? 's' : '' }} Pending Review
                    </p>
                    <p class="text-xs" style="color: var(--gz-muted);">
                        Players have requested to host communal Open Play sessions. Review court allocations and accept or decline.
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.open-play.index', ['status' => 'pending_approval']) }}" class="gz-btn-sm gz-btn-outline font-bold text-amber-700 hover:bg-amber-500/10">
                View Pending Requests
            </a>
        </div>
    @endif

    {{-- Quick Status Tabs --}}
    <div class="flex items-center gap-2 mb-4 overflow-x-auto pb-1">
        <a
            href="{{ route('admin.open-play.index', array_filter(['type' => $typeFilter !== 'all' ? $typeFilter : null])) }}"
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap {{ $statusFilter === 'all' ? 'bg-[#12150F] text-white dark:bg-white dark:text-black shadow-xs' : 'border hover:bg-black/5 dark:hover:bg-white/5' }}"
            style="{{ $statusFilter !== 'all' ? 'background: var(--gz-surface); border-color: var(--gz-border); color: var(--gz-ink);' : '' }}"
        >
            <span>All Sessions</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'all' ? 'bg-white/20 text-white dark:bg-black/20 dark:text-black' : 'bg-black/10 dark:bg-white/10' }}">
                {{ $totalSessionsCount ?? 0 }}
            </span>
        </a>

        <a
            href="{{ route('admin.open-play.index', array_filter(['status' => 'pending_approval', 'type' => $typeFilter !== 'all' ? $typeFilter : null])) }}"
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap {{ $statusFilter === 'pending_approval' ? 'bg-amber-500 text-stone-950 font-black shadow-sm' : 'border hover:bg-black/5 dark:hover:bg-white/5' }}"
            style="{{ $statusFilter !== 'pending_approval' ? 'background: var(--gz-surface); border-color: var(--gz-border); color: var(--gz-ink);' : '' }}"
        >
            <span>Host Requests (Pending)</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ ($pendingRequestsCount ?? 0) > 0 ? 'bg-amber-700 text-white font-extrabold' : 'bg-black/10 dark:bg-white/10' }}">
                {{ $pendingRequestsCount ?? 0 }}
            </span>
        </a>

        <a
            href="{{ route('admin.open-play.index', array_filter(['status' => 'approved_pending_payment', 'type' => $typeFilter !== 'all' ? $typeFilter : null])) }}"
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap {{ $statusFilter === 'approved_pending_payment' ? 'bg-[#12150F] text-white dark:bg-white dark:text-black shadow-xs' : 'border hover:bg-black/5 dark:hover:bg-white/5' }}"
            style="{{ $statusFilter !== 'approved_pending_payment' ? 'background: var(--gz-surface); border-color: var(--gz-border); color: var(--gz-ink);' : '' }}"
        >
            <span>Approved (Awaiting Payment)</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'approved_pending_payment' ? 'bg-white/20 text-white dark:bg-black/20 dark:text-black' : 'bg-black/10 dark:bg-white/10' }}">
                {{ $approvedPendingPaymentCount ?? 0 }}
            </span>
        </a>

        <a
            href="{{ route('admin.open-play.index', array_filter(['status' => 'scheduled', 'type' => $typeFilter !== 'all' ? $typeFilter : null])) }}"
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap {{ $statusFilter === 'scheduled' ? 'bg-[#12150F] text-white dark:bg-white dark:text-black shadow-xs' : 'border hover:bg-black/5 dark:hover:bg-white/5' }}"
            style="{{ $statusFilter !== 'scheduled' ? 'background: var(--gz-surface); border-color: var(--gz-border); color: var(--gz-ink);' : '' }}"
        >
            <span>Scheduled</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'scheduled' ? 'bg-white/20 text-white dark:bg-black/20 dark:text-black' : 'bg-black/10 dark:bg-white/10' }}">
                {{ $scheduledCount ?? 0 }}
            </span>
        </a>
    </div>

    {{-- Filter Toolbar --}}
    <div class="gz-panel p-4 mb-6">
        <form method="GET" action="{{ route('admin.open-play.index') }}" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label for="filter-status" class="text-xs font-semibold" style="color: var(--gz-muted);">Status:</label>
                <select id="filter-status" name="status" onchange="this.form.submit()" class="gz-select text-xs py-1.5 px-3">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="pending_approval" {{ $statusFilter === 'pending_approval' ? 'selected' : '' }}>Pending Host Approval</option>
                    <option value="approved_pending_payment" {{ $statusFilter === 'approved_pending_payment' ? 'selected' : '' }}>Approved (Pending Payment)</option>
                    <option value="scheduled" {{ $statusFilter === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="ongoing" {{ $statusFilter === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                    <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label for="filter-type" class="text-xs font-semibold" style="color: var(--gz-muted);">Event Type:</label>
                <select id="filter-type" name="type" onchange="this.form.submit()" class="gz-select text-xs py-1.5 px-3">
                    <option value="all" {{ $typeFilter === 'all' ? 'selected' : '' }}>All Types</option>
                    <option value="open_play" {{ $typeFilter === 'open_play' ? 'selected' : '' }}>Open Play</option>
                    <option value="tournament" {{ $typeFilter === 'tournament' ? 'selected' : '' }}>Tournament</option>
                </select>
            </div>

            @if($statusFilter !== 'all' || $typeFilter !== 'all')
                <a href="{{ route('admin.open-play.index') }}" class="text-xs font-semibold underline ml-auto" style="color: var(--gz-danger);">
                    Reset Filters
                </a>
            @endif
        </form>
    </div>

    {{-- Sessions Table / Card Grid --}}
    <div class="gz-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="gz-table w-full text-xs">
                <thead>
                    <tr>
                        <th class="text-left py-3 px-4">Session Info</th>
                        <th class="text-left py-3 px-4">Date & Time</th>
                        <th class="text-left py-3 px-4">Allocated Courts</th>
                        <th class="text-left py-3 px-4">Skill Level</th>
                        <th class="text-center py-3 px-4">Capacity & Roster</th>
                        <th class="text-right py-3 px-4">Fee / Slot</th>
                        <th class="text-center py-3 px-4">Status</th>
                        <th class="text-right py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--gz-border);">
                    @forelse($sessions as $session)
                        @php
                            $registeredCount = $session->registered_slots_count;
                            $percent = $session->capacity_percent;
                            $isFull = $session->is_full;
                        @endphp
                        <tr class="hover:bg-black/5 dark:hover:bg-white/5 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-sm" style="color: var(--gz-ink);">
                                    <a href="{{ route('admin.open-play.show', $session) }}" class="hover:underline">
                                        {{ $session->title }}
                                    </a>
                                </div>
                                <div class="text-[11px] mt-0.5 flex items-center gap-1.5 flex-wrap" style="color: var(--gz-muted);">
                                    <span class="font-mono uppercase font-bold">{{ str_replace('_', ' ', $session->session_type) }}</span>
                                    @if($session->isHostPlayer())
                                        <span>•</span>
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">Host: {{ $session->creator?->name ?? 'Player' }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-semibold">{{ $session->date->format('M d, Y (D)') }}</div>
                                <div class="text-[11px]" style="color: var(--gz-muted);">{{ $session->time_window }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap gap-1 max-w-[200px]">
                                    @forelse($session->courts as $court)
                                        <span class="gz-badge-outline text-[10px] px-2 py-0.5">
                                            {{ $court->court_name }}
                                        </span>
                                    @empty
                                        <span class="text-[11px] italic" style="color: var(--gz-muted);">No courts allocated</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="gz-badge text-[10px] px-2 py-0.5">
                                    {{ $session->skill_level }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="font-bold {{ $isFull ? 'text-amber-600' : '' }}">
                                        {{ $registeredCount }} / {{ $session->max_capacity }}
                                    </span>
                                    <span class="text-[10px]" style="color: var(--gz-muted);">slots</span>
                                </div>
                                <div class="w-24 mx-auto bg-gray-200 dark:bg-gray-700 h-1.5 rounded-full mt-1.5 overflow-hidden">
                                    <div class="h-full rounded-full {{ $isFull ? 'bg-amber-500' : 'bg-emerald-500' }}" style="width: {{ $percent }}%;"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-bold">
                                ₱{{ number_format($session->price_per_slot, 2) }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="gz-badge text-[10px] uppercase font-bold
                                    {{ $session->session_status === 'scheduled' ? 'gz-badge-success' : '' }}
                                    {{ $session->session_status === 'ongoing' ? 'gz-badge-primary' : '' }}
                                    {{ $session->session_status === 'completed' ? 'gz-badge-outline' : '' }}
                                    {{ $session->session_status === 'pending_approval' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                                    {{ $session->session_status === 'approved_pending_payment' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' : '' }}
                                    {{ $session->session_status === 'rejected' ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400' : '' }}
                                    {{ $session->session_status === 'cancelled' ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' : '' }}
                                ">
                                    {{ str_replace('_', ' ', $session->session_status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    @if($session->session_status === 'pending_approval' && Auth::user()->isManager())
                                        <form method="POST" action="{{ route('admin.open-play.accept', $session) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="gz-btn-primary gz-btn-sm text-[11px] py-1 px-2.5" title="Accept Host Request">
                                                Accept
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.open-play.reject', $session) }}" class="inline" onsubmit="return confirm('Reject this hosting request?');">
                                            @csrf
                                            <button type="submit" class="gz-btn-outline gz-btn-sm text-[11px] py-1 px-2.5 text-red-600 hover:text-red-700" title="Reject Host Request">
                                                Reject
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.open-play.show', $session) }}" class="gz-btn-outline gz-btn-sm text-[11px] py-1 px-2.5" title="View Details">
                                        View
                                    </a>
                                    @if(Auth::user()->isManager())
                                        <a href="{{ route('admin.open-play.edit', $session) }}" class="gz-btn-outline gz-btn-sm text-[11px] py-1 px-2.5" title="Edit Session">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.open-play.destroy', $session) }}" onsubmit="return confirm('Are you sure you want to delete or cancel this session?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="gz-btn-outline gz-btn-sm text-[11px] py-1 px-2.5 text-red-600 hover:text-red-700" title="Delete or Cancel">
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 px-4" style="color: var(--gz-muted);">
                                @if($statusFilter === 'pending_approval')
                                    <div class="max-w-md mx-auto">
                                        <div class="w-12 h-12 mx-auto rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                            </svg>
                                        </div>
                                        <h3 class="gz-font-display font-bold text-base" style="color: var(--gz-ink);">No Pending Player Host Requests</h3>
                                        <p class="text-xs mt-1" style="color: var(--gz-muted);">
                                            When registered players submit a proposal to host an Open Play session from their player portal (<code class="px-1 py-0.5 rounded bg-black/5 dark:bg-white/10 font-mono">/open-play/host</code>), their requests will appear here for manager review, court scheduling confirmation, and approval.
                                        </p>
                                    </div>
                                @else
                                    <p class="text-sm font-semibold">No Open Play or Tournament sessions found.</p>
                                    <p class="text-xs mt-1">
                                        @if(Auth::user()->isManager())
                                            Click "Create Session" above to configure your first communal pickleball event.
                                        @else
                                            Waiting for a manager to configure communal sessions.
                                        @endif
                                    </p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sessions->hasPages())
            <div class="p-4 border-t" style="border-color: var(--gz-border);">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
