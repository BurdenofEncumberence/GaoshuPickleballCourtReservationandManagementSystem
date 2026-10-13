<x-admin-layout>
    <x-slot name="heading">Promos Management</x-slot>

    {{-- Top Action Bar --}}
    <div class="gz-panel gz-panel-body mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-block w-2 h-2 rounded-full" style="background: var(--gz-pop);"></span>
                <span class="gz-eyebrow" style="color: var(--gz-pop-dark);">Special promos & discounts</span>
            </div>
            <h1 class="gz-font-display font-bold text-xl">Promos Overview</h1>
            <p class="text-sm mt-1" style="color: var(--gz-muted);">
                Manage promotional campaigns, seasonal discounts, and specials.
            </p>
        </div>
        <a href="{{ route('admin.events.create') }}" class="gz-btn-primary gz-btn-sm">
            + Add promo
        </a>
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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="gz-eyebrow">Total promos</span>
                <span class="gz-badge gz-badge-neutral">All</span>
            </div>
            <div class="gz-kpi-value text-right">{{ $events->count() }}</div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">Registered promos</p>
        </div>

        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="gz-eyebrow">Active</span>
                <span class="gz-badge gz-badge-success">Live</span>
            </div>
            <div class="gz-kpi-value text-right" style="color: var(--gz-pop-dark);">
                {{ $events->where('start_date', '<=', now())->where('end_date', '>=', now())->count() }}
            </div>
            <p class="text-xs mt-2" style="color: var(--gz-pop-dark);">Currently running</p>
        </div>

        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="gz-eyebrow">Total bookings</span>
                <span class="gz-badge gz-badge-neutral">Bookings</span>
            </div>
            <div class="gz-kpi-value text-right">{{ $events->sum('bookings_count') }}</div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">Promo reservations</p>
        </div>
    </div>

    {{-- Promos Table --}}
    <div class="gz-panel">
        <div class="gz-panel-header">
            <div>
                <h2 class="gz-font-display font-bold text-base">Promos Matrix</h2>
                <p class="text-xs" style="color: var(--gz-muted);">All configured promos and discounts</p>
            </div>
            <span class="gz-badge gz-badge-neutral">{{ $events->count() }} promos</span>
        </div>

        <div class="gz-panel-body overflow-x-auto">
            @if($events->isEmpty())
                <div class="p-8 text-center border border-dashed" style="border-color: var(--gz-border); background: var(--gz-surface);">
                    <p class="font-semibold">No promos found</p>
                    <p class="text-sm mt-1" style="color: var(--gz-muted);">Create your first promo to offer special discounts and rates.</p>
                </div>
            @else
                <table class="gz-table">
                    <thead>
                        <tr>
                            <th>Promo</th>
                            <th>Date Range</th>
                            <th>Discount</th>
                            <th>Bookings</th>
                            <th>Image</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                            <tr class="cursor-pointer hover:bg-opacity-50" onclick="window.location='{{ route('admin.events.show', $event) }}'">
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        @if($event->image)
                                            <img src="{{ asset('storage/' . $event->image) }}" alt="{{ $event->event_title }}" class="w-10 h-10 object-cover rounded">
                                        @else
                                            <div class="flex items-center justify-center font-bold text-sm" style="width: 40px; height: 40px; background: var(--gz-bg); border: 1.5px solid var(--gz-border); color: var(--gz-ink);">
                                                {{ strtoupper(substr($event->event_title, 0, 1)) }}
                                            </div>
                                        @endif
                                        <span class="font-semibold text-sm">{{ $event->event_title }}</span>
                                    </div>
                                </td>
                                <td class="text-sm" style="color: var(--gz-muted);">
                                    {{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}
                                </td>
                                <td class="text-sm font-semibold" style="color: var(--gz-pop-dark);">
                                    @if($event->discount)
                                        {{ $event->discount }}%
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-sm">
                                    {{ $event->bookings_count }}
                                </td>
                                <td>
                                    @if($event->image)
                                        <span class="gz-badge gz-badge-success">Yes</span>
                                    @else
                                        <span class="gz-badge gz-badge-neutral">No</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap" onclick="event.stopPropagation()">
                                    <a href="{{ route('admin.events.edit', $event) }}" class="gz-btn-outline gz-btn-sm mr-2">
                                        Edit
                                    </a>
                                    @if($event->bookings_count === 0)
                                        <form class="inline" method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Delete this promo?')">
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
