<x-admin-layout>
    <x-slot name="heading">Promo Details</x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="mb-4">
            <a href="{{ route('admin.events.index') }}" class="gz-link text-sm">
                ← Back to promos
            </a>
        </div>

        <div class="gz-panel">
            <div class="gz-panel-header">
                <div>
                    <span class="gz-eyebrow" style="color: var(--gz-pop-dark);">Promo information</span>
                    <h2 class="gz-font-display font-bold text-lg mt-1">{{ $event->event_title }}</h2>
                    <p class="text-sm mt-1" style="color: var(--gz-muted);">
                        View complete promo details and booking statistics.
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.events.edit', $event) }}" class="gz-btn-outline gz-btn-sm">
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
                </div>
            </div>

            <div class="gz-panel-body">
                {{-- Event Image --}}
                @if($event->image)
                    <div class="mb-6">
                        <img src="{{ asset('storage/' . $event->image) }}" alt="{{ $event->event_title }}" class="w-full h-auto rounded-lg" style="max-height: 400px; object-fit: cover;">
                    </div>
                @endif

                {{-- Event Details Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <p class="gz-eyebrow mb-1" style="color: var(--gz-muted);">Promo title</p>
                        <p class="font-semibold">{{ $event->event_title }}</p>
                    </div>

                    <div>
                        <p class="gz-eyebrow mb-1" style="color: var(--gz-muted);">Date range</p>
                        <p class="font-semibold">
                            {{ $event->start_date->format('F d, Y') }} - {{ $event->end_date->format('F d, Y') }}
                        </p>
                    </div>

                    <div>
                        <p class="gz-eyebrow mb-1" style="color: var(--gz-muted);">Discount</p>
                        <p class="font-semibold" style="color: var(--gz-pop-dark);">
                            @if($event->discount)
                                {{ $event->discount }}% off
                            @else
                                No discount
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="gz-eyebrow mb-1" style="color: var(--gz-muted);">Total bookings</p>
                        <p class="font-semibold">{{ $event->bookings_count }}</p>
                    </div>
                </div>

                {{-- Promo Description --}}
                @if($event->details)
                    <div class="mb-6">
                        <p class="gz-eyebrow mb-2" style="color: var(--gz-muted);">Promo details</p>
                        <div class="p-4 rounded" style="background: var(--gz-surface); border: 1px solid var(--gz-border);">
                            <p class="text-sm whitespace-pre-wrap">{{ $event->details }}</p>
                        </div>
                    </div>
                @endif

                {{-- Timestamps --}}
                <div class="pt-4 border-t" style="border-color: var(--gz-border);">
                    <p class="text-xs" style="color: var(--gz-muted);">
                        Created: {{ $event->created_at->format('F d, Y g:i A') }}
                        @if($event->updated_at != $event->created_at)
                            · Updated: {{ $event->updated_at->format('F d, Y g:i A') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Related Bookings --}}
        @if($event->bookings_count > 0)
            <div class="gz-panel mt-6">
                <div class="gz-panel-header">
                    <div>
                        <h2 class="gz-font-display font-bold text-base">Promo Bookings</h2>
                        <p class="text-xs" style="color: var(--gz-muted);">Reservations associated with this promo</p>
                    </div>
                    <span class="gz-badge gz-badge-neutral">{{ $event->bookings_count }} bookings</span>
                </div>

                <div class="gz-panel-body overflow-x-auto">
                    <table class="gz-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Court</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($event->bookings as $booking)
                                <tr>
                                    <td>
                                        <span class="font-semibold text-sm">{{ $booking->user?->name ?? 'N/A' }}</span>
                                    </td>
                                    <td class="text-sm">{{ $booking->court?->court_name ?? 'N/A' }}</td>
                                    <td class="text-sm">{{ $booking->date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td class="text-sm">{{ $booking->start_time }} - {{ $booking->end_time }}</td>
                                    <td>
                                        @if($booking->booking_status === 'confirmed')
                                            <span class="gz-badge gz-badge-success">Confirmed</span>
                                        @elseif($booking->booking_status === 'pending')
                                            <span class="gz-badge gz-badge-warning">Pending</span>
                                        @else
                                            <span class="gz-badge gz-badge-neutral">{{ ucfirst($booking->booking_status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
