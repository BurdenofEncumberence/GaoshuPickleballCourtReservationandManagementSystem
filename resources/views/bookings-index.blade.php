<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="gz-font-display font-bold text-xl sm:text-2xl">Booking History</h1>
                <p class="text-xs mt-1" style="color: var(--gz-muted);">
                    View and manage your court reservations, grouped by booking transaction with settlement details.
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $bookUrl = route('booking');

        // Group player bookings by transaction reference (ref_num)
        $transactionGroups = $bookings->groupBy(function ($b) {
            return $b->payments->first()?->ref_num ?: ('RES-' . $b->id);
        })->map(function ($items, $refNum) {
            $firstPayment = $items->flatMap->payments->first();

            $slots = $items->map(function ($b) {
                $date    = \Illuminate\Support\Carbon::parse($b->date);
                $start   = \Illuminate\Support\Carbon::parse($b->start_time);
                $end     = \Illuminate\Support\Carbon::parse($b->end_time);
                $startDt = $date->copy()->setTime($start->hour, $start->minute);
                $endDt   = $date->copy()->setTime($end->hour, $end->minute);

                $mins = abs($startDt->diffInMinutes($endDt));
                $duration = $mins % 60 === 0
                    ? ($mins / 60) . ' hour' . ($mins === 60 ? '' : 's')
                    : $mins . ' min';

                $slotStatus = ($b->booking_status === 'confirmed' && $endDt->isPast())
                    ? 'completed'
                    : $b->booking_status;

                $payment = $b->payments->first();
                $amount = (float) ($payment?->amount ?? $b->court?->price_per_hour ?? 0);

                return [
                    'b'             => $b,
                    'startDt'       => $startDt,
                    'endDt'         => $endDt,
                    'courtName'     => $b->court->court_name ?? 'Court',
                    'courtSize'     => $b->court?->size ?: 'Regular (13.41m x 6.10m)',
                    'courtId'       => $b->court_id,
                    'dateLabel'     => $date->format('D, M j, Y'),
                    'timeLabel'     => $start->format('g:i A') . ' – ' . $end->format('g:i A'),
                    'durationLabel' => $duration,
                    'amount'        => $amount,
                    'status'        => $slotStatus,
                    'event'         => $b->event,
                    'isWalkIn'      => $b->isWalkIn(),
                ];
            })->sortBy('startDt')->values();

            $totalAmount = (float) $items->flatMap->payments->sum('amount');
            if ($totalAmount <= 0) {
                $totalAmount = (float) $slots->sum('amount');
            }

            // Determine aggregate status for this transaction
            $allCancelled = $slots->every(fn ($s) => $s['status'] === 'cancelled');
            $allCompleted = $slots->every(fn ($s) => $s['status'] === 'completed');
            $anyPending   = $slots->contains(fn ($s) => $s['status'] === 'pending');
            $anyConfirmed = $slots->contains(fn ($s) => $s['status'] === 'confirmed');

            $overallStatus = match (true) {
                $allCancelled => 'cancelled',
                $anyPending   => 'pending',
                $allCompleted => 'completed',
                $anyConfirmed => 'confirmed',
                default       => 'confirmed',
            };

            // Payment method and status labeling
            $rawMethod = $firstPayment?->payment_method ?? 'online';
            $methodLabel = match (true) {
                str_starts_with($rawMethod, 'paymongo_') => 'Pay Online (' . strtoupper(str_replace('paymongo_', '', $rawMethod)) . ')',
                $rawMethod === 'paymongo'                => 'Pay Online',
                $rawMethod === 'cash'                    => 'Cash at Counter',
                $rawMethod === 'gcash'                   => 'GCash',
                $rawMethod === 'card'                    => 'Card',
                default                                  => ucfirst($rawMethod),
            };

            $paymentStatus = $firstPayment?->payment_status ?? ($anyPending ? 'pending' : 'paid');
            $paymongoPaymentId = $firstPayment?->paymongo_payment_id ?: $firstPayment?->checkout_session_id;

            // Upcoming check: transaction has at least one active (non-cancelled) slot in the future
            $isUpcoming = $slots->contains(function ($s) {
                return $s['status'] !== 'cancelled' && $s['endDt']->isFuture();
            });

            // Date summary
            $uniqueDates = $slots->pluck('dateLabel')->unique()->values();
            $dateSummary = $uniqueDates->count() === 1
                ? $uniqueDates->first()
                : $uniqueDates->first() . ' (' . $uniqueDates->count() . ' dates)';

            $earliestStart = $slots->min('startDt');
            $latestEnd     = $slots->max('endDt');

            $isWalkIn = $slots->first()['isWalkIn'] ?? false;

            // Search token blob for client-side instant filtering
            $searchBlob = strtolower(implode(' ', array_filter([
                $refNum,
                $paymongoPaymentId,
                $methodLabel,
                $overallStatus,
                $paymentStatus,
                $slots->pluck('courtName')->join(' '),
                $slots->pluck('dateLabel')->join(' '),
                $slots->pluck('timeLabel')->join(' '),
            ])));

            return [
                'refNum'            => $refNum,
                'paymongoPaymentId' => $paymongoPaymentId,
                'slots'             => $slots,
                'courtsCount'       => $slots->count(),
                'totalAmount'       => $totalAmount,
                'overallStatus'     => $overallStatus,
                'paymentMethod'     => $methodLabel,
                'paymentStatus'     => $paymentStatus,
                'isUpcoming'        => $isUpcoming,
                'dateSummary'       => $dateSummary,
                'earliestStart'     => $earliestStart,
                'latestEnd'         => $latestEnd,
                'isWalkIn'          => $isWalkIn,
                'courtNames'        => $slots->pluck('courtName')->unique()->values()->all(),
                'searchBlob'        => $searchBlob,
            ];
        })->values();

        $upcomingGroups = $transactionGroups->filter(fn ($g) => $g['isUpcoming'])->sortBy('earliestStart')->values();
        $pastGroups     = $transactionGroups->filter(fn ($g) => ! $g['isUpcoming'])->sortByDesc('latestEnd')->values();
        $allGroups      = $transactionGroups->sortByDesc('latestEnd')->values();

        $groups = [
            'upcoming' => $upcomingGroups,
            'past'     => $pastGroups,
            'all'      => $allGroups,
        ];

        $defaultTab = $upcomingGroups->isNotEmpty() ? 'upcoming' : ($pastGroups->isNotEmpty() ? 'past' : 'all');

        $allCourtNames = $bookings->pluck('court.court_name')->filter()->unique()->sort()->values();
    @endphp

    <div class="py-6 sm:py-8"
         x-data="{
             tab: '{{ $defaultTab }}',
             search: '',
             statusFilter: 'all',
             courtFilter: 'all',

             matches(item) {
                 if (this.statusFilter !== 'all' && item.status !== this.statusFilter) {
                     return false;
                 }
                 if (this.courtFilter !== 'all' && !item.courts.includes(this.courtFilter)) {
                     return false;
                 }
                 if (this.search.trim() !== '') {
                     const q = this.search.toLowerCase().trim();
                     if (!item.blob.includes(q)) {
                         return false;
                     }
                 }
                 return true;
             },

             resetFilters() {
                 this.search = '';
                 this.statusFilter = 'all';
                 this.courtFilter = 'all';
             }
         }"
    >
        @if (session('status'))
            <div class="gz-status mb-4" role="status" aria-live="polite">
                {{ session('status') }}
            </div>
        @endif

        {{-- Top Navigation: Tabs + Book Court Button --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-2" role="tablist" aria-label="Booking filter">
                @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All History'] as $key => $label)
                    <button type="button"
                            role="tab"
                            id="tab-{{ $key }}"
                            aria-controls="panel-{{ $key }}"
                            :aria-selected="tab === '{{ $key }}' ? 'true' : 'false'"
                            @click="tab = '{{ $key }}'"
                            class="text-sm font-semibold rounded-xl transition"
                            :style="tab === '{{ $key }}'
                                ? 'padding: 7px 14px; background: var(--gz-pop); color: var(--gz-ink); border: 1px solid var(--gz-pop);'
                                : 'padding: 7px 14px; background: var(--gz-surface); color: var(--gz-muted); border: 1px solid var(--gz-border);'">
                        {{ $label }} ({{ $groups[$key]->count() }})
                    </button>
                @endforeach
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('open-play.index') }}" class="gz-btn-outline gz-btn-sm whitespace-nowrap">
                    Open Play Sessions
                </a>
                <a href="{{ $bookUrl }}" class="gz-btn-primary gz-btn-sm whitespace-nowrap">
                    + Book a Court
                </a>
            </div>
        </div>

        {{-- Open Play & Tournament Tickets Section --}}
        @if(isset($openPlayRegistrations) && $openPlayRegistrations->isNotEmpty())
            <div class="gz-panel mb-6 overflow-hidden">
                <div class="p-4 border-b flex items-center justify-between" style="border-color: var(--gz-border); background: var(--gz-bg);">
                    <div class="flex items-center gap-2">
                        <span class="gz-badge gz-badge-pop text-[10px] uppercase font-bold tracking-wider">Tickets</span>
                        <h2 class="gz-font-display font-bold text-sm">Open Play & Tournament Registrations</h2>
                    </div>
                    <a href="{{ route('open-play.index') }}" class="text-xs font-semibold hover:underline" style="color: var(--gz-pop-dark);">
                        Find More Sessions →
                    </a>
                </div>

                <div class="divide-y" style="border-color: var(--gz-border);">
                    @foreach($openPlayRegistrations as $reg)
                        @php
                            $sess = $reg->session;
                            $sessDate = $sess ? \Illuminate\Support\Carbon::parse($sess->date) : null;
                        @endphp
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-black/5 dark:hover:bg-white/5 transition">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-sm" style="color: var(--gz-ink);">
                                        {{ $sess?->title ?? 'Open Play Session' }}
                                    </span>
                                    <span class="gz-badge text-[10px] uppercase font-bold">
                                        {{ $sess ? str_replace('_', ' ', $sess->session_type) : 'Open Play' }}
                                    </span>
                                    <span class="gz-badge-outline text-[10px]">
                                        {{ $reg->slots_count }} slot(s)
                                    </span>
                                </div>

                                <div class="text-xs flex items-center gap-2 flex-wrap" style="color: var(--gz-muted);">
                                    @if($sessDate)
                                        <span>{{ $sessDate->format('D, M j, Y') }}</span>
                                        <span>•</span>
                                        <span>{{ $sess->time_window }}</span>
                                        <span>•</span>
                                    @endif
                                    <span>Courts: {{ $sess?->allocated_courts_label ?: 'Dedicated Courts' }}</span>
                                    <span>•</span>
                                    <span class="font-mono">Ref: {{ $reg->ref_num }}</span>
                                    @if($reg->paymongo_payment_id || $reg->paymongo_checkout_session_id)
                                        <span>•</span>
                                        <span class="font-mono">PayMongo ID: {{ $reg->paymongo_payment_id ?: $reg->paymongo_checkout_session_id }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-4 justify-between sm:justify-end">
                                <div class="text-right">
                                    <div class="font-mono font-bold text-sm" style="color: var(--gz-ink);">
                                        ₱{{ number_format($reg->total_fee, 2) }}
                                    </div>
                                    <div class="text-[10px]" style="color: var(--gz-muted);">
                                        {{ $reg->payment_method === 'paymongo' ? 'Pay Online' : ($reg->payment_method === 'cash' ? 'Cash at Counter' : ucfirst(str_replace('_', ' ', $reg->payment_method))) }}
                                    </div>
                                </div>

                                <span class="gz-badge text-[10px] uppercase font-bold
                                    {{ $reg->payment_status === 'paid' ? 'gz-badge-success' : '' }}
                                    {{ $reg->payment_status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400' : '' }}
                                    {{ $reg->payment_status === 'cancelled' ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400' : '' }}
                                ">
                                    {{ $reg->payment_status }}
                                </span>

                                @if($sess)
                                    <a href="{{ route('open-play.show', $sess) }}" class="gz-btn-outline gz-btn-sm text-xs">
                                        View
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Search & Filter Toolbar --}}
        <div class="gz-panel mb-6" style="padding: 14px 16px;">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                {{-- Search Input --}}
                <div class="sm:col-span-6 relative">
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Search by transaction reference, court name, or date..."
                        class="gz-input text-xs w-full py-2 pr-7"
                    >
                    <button
                        type="button"
                        x-show="search.length > 0"
                        @click="search = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs font-bold opacity-50 hover:opacity-100"
                        title="Clear search"
                    >Clear</button>
                </div>

                {{-- Status Filter --}}
                <div class="sm:col-span-3">
                    <select x-model="statusFilter" class="gz-input text-xs w-full py-2">
                        <option value="all">All Statuses</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="pending">Pending Payment</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                {{-- Court Filter --}}
                <div class="sm:col-span-3">
                    <select x-model="courtFilter" class="gz-input text-xs w-full py-2">
                        <option value="all">All Courts</option>
                        @foreach ($allCourtNames as $cName)
                            <option value="{{ $cName }}">{{ $cName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Active Filter Pills / Reset button --}}
            <div x-show="search.trim() !== '' || statusFilter !== 'all' || courtFilter !== 'all'" x-cloak class="mt-3 pt-3 border-t flex items-center justify-between gap-2 text-xs" style="border-color: var(--gz-border);">
                <div class="flex items-center gap-2 flex-wrap" style="color: var(--gz-muted);">
                    <span>Filtered by:</span>
                    <template x-if="search.trim() !== ''">
                        <span class="gz-badge gz-badge-neutral text-[10px]" x-text="'Keyword: ' + search"></span>
                    </template>
                    <template x-if="statusFilter !== 'all'">
                        <span class="gz-badge gz-badge-neutral text-[10px] capitalize" x-text="'Status: ' + statusFilter"></span>
                    </template>
                    <template x-if="courtFilter !== 'all'">
                        <span class="gz-badge gz-badge-neutral text-[10px]" x-text="'Court: ' + courtFilter"></span>
                    </template>
                </div>
                <button type="button" @click="resetFilters()" class="font-semibold underline shrink-0 text-xs" style="color: var(--gz-danger);">
                    Reset Filters
                </button>
            </div>
        </div>

        {{-- Transaction Groups List --}}
        @foreach (['upcoming', 'past', 'all'] as $key)
            @php $currentGroupList = $groups[$key]; @endphp

            <section id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}"
                     x-show="tab === '{{ $key }}'" @if ($key !== $defaultTab) x-cloak @endif>

                @if ($currentGroupList->isEmpty())
                    <div class="gz-panel text-center" style="padding: 40px 24px;">
                        <p class="gz-font-display font-bold text-base mb-1">
                            {{ $key === 'upcoming' ? 'No upcoming reservations' : ($key === 'past' ? 'No past reservations' : 'No reservations found') }}
                        </p>
                        <p class="text-sm mb-4" style="color: var(--gz-muted);">
                            {{ $key === 'upcoming'
                                ? 'Your confirmed court bookings will be grouped and listed here.'
                                : 'Finished and cancelled transactions will appear here.' }}
                        </p>
                        <a href="{{ $bookUrl }}" class="gz-btn-primary gz-btn-sm">Book a court</a>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($currentGroupList as $group)
                            @php
                                $badgeClass = match ($group['overallStatus']) {
                                    'confirmed' => 'gz-badge-success',
                                    'completed' => 'gz-badge-neutral',
                                    'pending'   => 'gz-badge-warning',
                                    'cancelled' => 'gz-badge-danger',
                                    default     => 'gz-badge-neutral',
                                };

                                $itemSearchData = [
                                    'status' => $group['overallStatus'],
                                    'courts' => $group['courtNames'],
                                    'blob'   => $group['searchBlob'],
                                ];
                            @endphp

                            <article
                                class="gz-panel overflow-hidden"
                                x-show="matches({{ json_encode($itemSearchData) }})"
                                style="{{ $group['isUpcoming'] ? 'border-left: 4px solid var(--gz-pop);' : 'opacity: .95;' }}"
                            >
                                {{-- Transaction Header Card --}}
                                <div class="p-4 sm:p-5 border-b" style="border-color: var(--gz-border); background: var(--gz-surface);">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap mb-1.5">
                                                <span class="font-mono font-bold text-sm tracking-wide" style="color: var(--gz-ink);">
                                                    {{ $group['refNum'] }}
                                                </span>
                                                <span class="gz-badge gz-badge-neutral text-[10px]">
                                                    {{ $group['courtsCount'] }} {{ $group['courtsCount'] === 1 ? 'Court Session' : 'Courts Reserved' }}
                                                </span>
                                                @if ($group['isWalkIn'])
                                                    <span class="gz-badge text-[10px]" style="background: rgba(179,38,30,0.12); color: var(--red); border: 1px solid var(--red);">
                                                        Walk-In
                                                    </span>
                                                @else
                                                    <span class="gz-badge gz-badge-neutral text-[10px]">
                                                        Online
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="text-xs space-y-0.5" style="color: var(--gz-muted);">
                                                <p>
                                                    <span class="font-semibold" style="color: var(--gz-ink);">{{ $group['dateSummary'] }}</span>
                                                    · Method: <span class="font-medium">{{ $group['paymentMethod'] }}</span>
                                                    · Payment: <span class="font-semibold capitalize {{ $group['paymentStatus'] === 'paid' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $group['paymentStatus'] }}</span>
                                                    @if(!empty($group['paymongoPaymentId']))
                                                        · PayMongo ID: <span class="font-mono font-semibold" style="color: var(--gz-ink);">{{ $group['paymongoPaymentId'] }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3 sm:justify-end flex-wrap">
                                            <div class="text-left sm:text-right">
                                                <span class="text-[10px] uppercase font-bold tracking-wider block" style="color: var(--gz-muted);">
                                                    Transaction Total
                                                </span>
                                                <span class="gz-font-display font-bold text-lg" style="color: var(--gz-pop-dark);">
                                                    ₱{{ number_format($group['totalAmount'], 2) }}
                                                </span>
                                            </div>
                                            <span class="gz-badge {{ $badgeClass }} text-xs capitalize">
                                                {{ ucfirst($group['overallStatus']) }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="$dispatch('open-booking-receipt', {{ $group['slots']->first()['b']->id }})"
                                                class="gz-btn-outline gz-btn-sm inline-flex items-center gap-1.5 text-xs py-1.5 px-3"
                                                title="View Official Receipt for this transaction"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <span>View Receipt</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Itemized Court Slots List Inside This Transaction --}}
                                <div class="divide-y" style="border-color: var(--gz-border);">
                                    @foreach ($group['slots'] as $sIdx => $slot)
                                        @php
                                            $slotBadgeClass = match ($slot['status']) {
                                                'confirmed' => 'gz-badge-success',
                                                'completed' => 'gz-badge-neutral',
                                                'pending'   => 'gz-badge-warning',
                                                'cancelled' => 'gz-badge-danger',
                                                default     => 'gz-badge-neutral',
                                            };
                                        @endphp

                                        <div class="p-3.5 sm:px-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div class="flex items-start gap-3">
                                                <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 mt-0.5"
                                                     style="background: rgba(18, 21, 15, 0.06); color: var(--gz-ink);">
                                                    {{ $sIdx + 1 }}
                                                </div>

                                                <div>
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <h4 class="font-bold text-sm" style="color: var(--gz-ink);">
                                                             {{ $slot['courtName'] }}
                                                        </h4>
                                                        @if (!empty($slot['courtSize']))
                                                            <span class="gz-badge gz-badge-neutral text-[10px]">
                                                                {{ $slot['courtSize'] }}
                                                            </span>
                                                        @endif
                                                        @if ($slot['event'])
                                                            <span class="gz-badge gz-badge-pop text-[10px]">
                                                                {{ $slot['event']->event_title }}
                                                                @if ($slot['event']->discount)
                                                                    · {{ $slot['event']->discount }}% OFF
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <p class="text-xs mt-0.5" style="color: var(--gz-muted);">
                                                        <span class="font-medium" style="color: var(--gz-ink);">{{ $slot['dateLabel'] }}</span>
                                                        · {{ $slot['timeLabel'] }}
                                                        <span class="opacity-75">({{ $slot['durationLabel'] }})</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2.5 justify-between sm:justify-end pl-10 sm:pl-0">
                                                <span class="font-mono font-semibold text-sm" style="color: var(--gz-ink);">
                                                    ₱{{ number_format($slot['amount'], 2) }}
                                                </span>
                                                <span class="gz-badge {{ $slotBadgeClass }} text-[11px] capitalize">
                                                    {{ ucfirst($slot['status']) }}
                                                </span>
                                                <button
                                                    type="button"
                                                    @click="$dispatch('open-booking-receipt', {{ $slot['b']->id }})"
                                                    class="gz-btn-outline gz-btn-sm inline-flex items-center gap-1 text-[11px] py-1 px-2"
                                                    title="View Official Receipt for this slot"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    <span>Receipt</span>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>

    <!-- Booking Receipt Modal -->
    <x-booking-receipt-modal />
</x-app-layout>