<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs mb-1" style="color: var(--gz-muted);">
                    <a href="{{ route('admin.open-play.index') }}" class="hover:underline">Open Play & Tournaments</a>
                    <span>/</span>
                    <span>Session Roster</span>
                </div>
                <h1 class="gz-font-display font-bold text-xl sm:text-2xl">{{ $session->title }}</h1>
                <p class="text-xs mt-1" style="color: var(--gz-muted);">
                    {{ $session->date->format('l, F j, Y') }} • {{ $session->time_window }} ({{ $session->duration_hours }} hrs)
                </p>
            </div>
            <div class="flex items-center gap-3">
                @if(Auth::user()->isManager())
                    <a href="{{ route('admin.open-play.edit', $session) }}" class="gz-btn-outline gz-btn-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <span>Edit Session</span>
                    </a>
                @endif
                <a href="{{ route('admin.open-play.index') }}" class="gz-btn-outline gz-btn-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>All Sessions</span>
                </a>
            </div>
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

    @if (session('error'))
        <div class="mb-4 p-4 rounded-xl text-sm" style="background: var(--gz-danger-bg); color: var(--gz-danger);" role="alert">
            <p class="font-bold">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Host Request Review Card (if created by a player) --}}
    @if ($session->isHostPlayer())
        <div class="gz-panel p-5 mb-6 border" style="background: var(--gz-surface); border-color: var(--gz-border);">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--gz-muted);">Player-Hosted Session</span>
                        <span class="gz-badge text-[10px] uppercase font-bold
                            {{ $session->session_status === 'pending_approval' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                            {{ $session->session_status === 'approved_pending_payment' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' : '' }}
                            {{ $session->session_status === 'scheduled' ? 'gz-badge-success' : '' }}
                            {{ $session->session_status === 'rejected' ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400' : '' }}
                        ">
                            {{ str_replace('_', ' ', $session->session_status) }}
                        </span>
                    </div>
                    <div class="text-sm font-bold" style="color: var(--gz-ink);">
                        Host: {{ $session->creator?->name }} ({{ $session->creator?->email }})
                    </div>
                    <div class="text-xs flex items-center gap-4 flex-wrap" style="color: var(--gz-muted);">
                        <span>Court Hire Fee: <strong class="font-mono text-emerald-600 dark:text-emerald-400">₱{{ number_format($session->court_fee, 2) }}</strong></span>
                        <span>Payment Status: <strong class="capitalize">{{ $session->host_payment_status }}</strong> @if($session->host_payment_method)({{ ucfirst($session->host_payment_method) }})@endif</span>
                        @if($session->host_paid_at)
                            <span>Paid At: {{ $session->host_paid_at->format('M d, Y h:i A') }}</span>
                        @endif
                    </div>
                    @if($session->manager_note)
                        <div class="text-xs mt-2 p-2.5 rounded border" style="background: var(--gz-bg); border-color: var(--gz-border);">
                            <span class="font-bold text-[10px] uppercase tracking-wider block" style="color: var(--gz-muted);">Manager Note:</span>
                            <span style="color: var(--gz-ink);">{{ $session->manager_note }}</span>
                        </div>
                    @endif
                </div>

                @if($session->session_status === 'pending_approval' && Auth::user()->isManager())
                    <div class="flex items-center gap-2 pt-3 md:pt-0 border-t md:border-t-0" style="border-color: var(--gz-border);">
                        <form method="POST" action="{{ route('admin.open-play.accept', $session) }}" class="inline">
                            @csrf
                            <button type="submit" class="gz-btn-primary gz-btn-sm flex items-center gap-1.5 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Accept Request</span>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.open-play.reject', $session) }}" class="inline" onsubmit="return confirm('Reject this player hosting request?');">
                            @csrf
                            <button type="submit" class="gz-btn-outline gz-btn-sm text-red-600 hover:text-red-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Reject</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Overview KPI Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="gz-kpi-card">
            <span class="gz-kpi-label">Slots Occupied</span>
            <div class="gz-kpi-value mt-1 text-right">
                {{ $session->registered_slots_count }} <span class="text-sm font-normal text-muted">/ {{ $session->max_capacity }}</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 h-1.5 rounded-full mt-2 overflow-hidden">
                <div class="h-full rounded-full {{ $session->is_full ? 'bg-amber-500' : 'bg-emerald-500' }}" style="width: {{ $session->capacity_percent }}%;"></div>
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">{{ $session->remaining_slots }} slot(s) available</p>
        </div>

        <div class="gz-kpi-card">
            <span class="gz-kpi-label">Allocated Courts</span>
            <div class="gz-kpi-value mt-1 text-base font-bold text-right">
                {{ $session->allocated_courts_label ?: 'None' }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Reserved exclusively for this event</p>
        </div>

        <div class="gz-kpi-card">
            <span class="gz-kpi-label">Ticket Fee / Slot</span>
            <div class="gz-kpi-value mt-1 font-mono text-right">₱{{ number_format($session->price_per_slot, 2) }}</div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Target Skill: {{ $session->skill_level }}</p>
        </div>

        <div class="gz-kpi-card">
            <span class="gz-kpi-label">Revenue Collected</span>
            <div class="gz-kpi-value mt-1 font-mono text-right">
                ₱{{ number_format($session->registrations->where('payment_status', 'paid')->sum('total_fee'), 2) }}
            </div>
            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">
                Pending: ₱{{ number_format($session->registrations->where('payment_status', 'pending')->sum('total_fee'), 2) }}
            </p>
        </div>
    </div>

    {{-- Details Card --}}
    @if($session->details)
        <div class="gz-panel p-4 mb-6">
            <h3 class="gz-font-display font-bold text-xs uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                Format & Notes
            </h3>
            <p class="text-xs" style="color: var(--gz-ink);">{{ $session->details }}</p>
        </div>
    @endif

    {{-- Player Roster Table --}}
    <div class="gz-panel overflow-hidden">
        <div class="p-4 border-b flex items-center justify-between" style="border-color: var(--gz-border);">
            <div>
                <h2 class="gz-font-display font-bold text-sm">Player Roster & Attendance</h2>
                <p class="text-xs mt-0.5" style="color: var(--gz-muted);">
                    List of registered players for this session. Mark attendance (Show / No Show) as players check in.
                </p>
            </div>
            <span class="gz-badge gz-badge-outline text-xs">
                {{ $session->registrations->count() }} Transaction(s)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="gz-table w-full text-xs">
                <thead>
                    <tr>
                        <th class="text-left py-3 px-4">Player Details</th>
                        <th class="text-center py-3 px-4">Slots</th>
                        <th class="text-right py-3 px-4">Total Fee</th>
                        <th class="text-center py-3 px-4">Payment</th>
                        <th class="text-left py-3 px-4">Reference</th>
                        <th class="text-center py-3 px-4">Attendance</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--gz-border);">
                    @forelse($session->registrations as $reg)
                        <tr class="hover:bg-black/5 dark:hover:bg-white/5 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-sm" style="color: var(--gz-ink);">
                                    {{ $reg->player_name }}
                                </div>
                                <div class="text-[11px]" style="color: var(--gz-muted);">
                                    {{ $reg->player_email }}
                                    @if($reg->player_phone)
                                        • {{ $reg->player_phone }}
                                    @endif
                                </div>
                                @if($reg->notes)
                                    <div class="text-[10px] italic mt-0.5" style="color: var(--gz-muted);">
                                        Note: {{ $reg->notes }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="font-bold text-sm">{{ $reg->slots_count }}</span>
                                <span class="text-[11px]" style="color: var(--gz-muted);">slot(s)</span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-bold">
                                ₱{{ number_format($reg->total_fee, 2) }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="gz-badge text-[10px] uppercase font-bold
                                    {{ $reg->payment_status === 'paid' ? 'gz-badge-success' : '' }}
                                    {{ $reg->payment_status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400' : '' }}
                                    {{ $reg->payment_status === 'cancelled' ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400' : '' }}
                                ">
                                    {{ $reg->payment_status }}
                                </span>
                                <div class="text-[10px] mt-0.5" style="color: var(--gz-muted);">
                                    {{ ucfirst(str_replace('_', ' ', $reg->payment_method)) }}
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap font-mono text-[11px]">
                                <div>{{ $reg->ref_num }}</div>
                                @if($reg->paymongo_payment_id || $reg->paymongo_checkout_session_id)
                                    <div class="text-[10px] mt-0.5" style="color: var(--gz-muted);">
                                        PayMongo: {{ $reg->paymongo_payment_id ?: $reg->paymongo_checkout_session_id }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <form method="POST" action="{{ route('admin.open-play.attendance', $reg) }}" class="inline-flex items-center gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select
                                        name="attendance_status"
                                        onchange="this.form.submit()"
                                        class="gz-select text-[11px] py-1 px-2 font-semibold
                                            {{ $reg->attendance_status === 'show' ? 'border-emerald-500 text-emerald-600' : '' }}
                                            {{ $reg->attendance_status === 'no_show' ? 'border-red-500 text-red-600' : '' }}
                                        "
                                    >
                                        <option value="registered" {{ $reg->attendance_status === 'registered' ? 'selected' : '' }}>Registered</option>
                                        <option value="show" {{ $reg->attendance_status === 'show' ? 'selected' : '' }}>Show</option>
                                        <option value="no_show" {{ $reg->attendance_status === 'no_show' ? 'selected' : '' }}>No Show</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8" style="color: var(--gz-muted);">
                                No players have registered for this session yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
