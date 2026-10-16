<x-app-layout title="{{ $session->title }} — Gaoshou Pickleball">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6" x-data="openPlayReservationComponent({{ (float) $session->price_per_slot }}, {{ (int) $session->remaining_slots }})">
        {{-- Breadcrumbs --}}
        <div class="flex items-center gap-2 text-xs mb-4" style="color: var(--gz-muted);">
            <a href="{{ route('open-play.index') }}" class="hover:underline">Open Play & Tournaments</a>
            <span>/</span>
            <span class="truncate">{{ $session->title }}</span>
        </div>

        {{-- Flash / Error Alerts --}}
        @if (session('status'))
            <div class="mb-6 p-4 rounded-xl text-sm border" style="background: rgba(229, 168, 35, 0.08); border-color: rgba(229, 168, 35, 0.3); color: var(--gz-ink);">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" style="color: var(--gz-pop-dark);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl text-sm" style="background: var(--gz-danger-bg); color: var(--gz-danger);" role="alert">
                <p class="font-bold mb-1">Could not complete reservation:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            {{-- Left Column: Session Details --}}
            <div class="lg:col-span-7 space-y-6">
                <div class="gz-panel p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="gz-badge {{ $session->session_type === 'tournament' ? 'gz-badge-primary' : 'gz-badge-outline' }} text-[10px] uppercase font-bold tracking-wider">
                            {{ str_replace('_', ' ', $session->session_type) }}
                        </span>
                        <span class="gz-badge-outline text-[10px] font-semibold px-2 py-0.5">
                            {{ $session->skill_level }}
                        </span>
                        <span class="gz-badge text-[10px] uppercase font-bold ml-auto
                            {{ $session->session_status === 'scheduled' ? 'gz-badge-success' : 'gz-badge-outline' }}">
                            {{ $session->session_status }}
                        </span>
                    </div>

                    <h1 class="gz-font-display font-extrabold text-2xl sm:text-3xl mb-4" style="color: var(--gz-ink);">
                        {{ $session->title }}
                    </h1>

                    {{-- Schedule Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl mb-6" style="background: var(--gz-bg); border: 1px solid var(--gz-border);">
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider" style="color: var(--gz-muted);">Date</span>
                            <div class="font-bold text-sm mt-0.5" style="color: var(--gz-ink);">
                                {{ $session->date->format('l, F j, Y') }}
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider" style="color: var(--gz-muted);">Time Window</span>
                            <div class="font-bold text-sm mt-0.5" style="color: var(--gz-ink);">
                                {{ $session->time_window }}
                                <span class="text-xs font-normal" style="color: var(--gz-muted);">({{ $session->duration_hours }} hrs)</span>
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider" style="color: var(--gz-muted);">Allocated Courts</span>
                            <div class="font-bold text-sm mt-0.5" style="color: var(--gz-ink);">
                                {{ $session->allocated_courts_label ?: 'Dedicated Venue Courts' }}
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider" style="color: var(--gz-muted);">Participation Fee</span>
                            <div class="font-mono font-extrabold text-base mt-0.5" style="color: var(--gz-ink);">
                                ₱{{ number_format($session->price_per_slot, 2) }}
                                <span class="text-xs font-normal font-sans" style="color: var(--gz-muted);">/ player slot</span>
                            </div>
                        </div>
                    </div>

                    {{-- Real-Time Capacity Status Bar --}}
                    <div class="p-4 rounded-xl mb-6" style="background: var(--gz-surface); border: 1px solid var(--gz-border);">
                        <div class="flex items-center justify-between text-xs mb-2">
                            <span class="font-bold" style="color: var(--gz-ink);">Live Slot Availability:</span>
                            @if($session->is_full)
                                <span class="font-bold text-red-600 dark:text-red-400">Completely Sold Out</span>
                            @else
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ $session->remaining_slots }} of {{ $session->max_capacity }} player slots remaining
                                </span>
                            @endif
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 h-2.5 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300 {{ $session->is_full ? 'bg-red-500' : ($session->remaining_slots <= 3 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                 style="width: {{ $session->capacity_percent }}%;"></div>
                        </div>
                        <p class="text-[11px] mt-2" style="color: var(--gz-muted);">
                            Maximum {{ $session->max_capacity }} players join the communal rotation pool across {{ $session->courts->count() ?: 1 }} allocated court(s).
                        </p>
                    </div>

                    {{-- Details & Format --}}
                    @if($session->details)
                        <div class="mb-6">
                            <h2 class="gz-font-display font-bold text-xs uppercase tracking-wider mb-2" style="color: var(--gz-muted);">
                                Event Format & Rotation Rules
                            </h2>
                            <p class="text-xs leading-relaxed" style="color: var(--gz-ink);">
                                {{ $session->details }}
                            </p>
                        </div>
                    @endif

                    {{-- How it Works Notice --}}
                    <div class="p-4 rounded-xl border border-dashed text-xs" style="border-color: var(--gz-border); background: var(--gz-bg);">
                        <div class="font-bold mb-1" style="color: var(--gz-ink);">How Open Play Works</div>
                        <p class="leading-relaxed" style="color: var(--gz-muted);">
                            Instead of booking a full private court for yourself, you purchase an individual participation ticket to join this session's rotation pool. Players rotate games with paddle-stacking rules or organized brackets throughout the full session timeframe.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Right Column: Reservation / Ticket Form --}}
            <div class="lg:col-span-5">
                <div class="gz-panel p-6 sticky top-6">
                    <h2 class="gz-font-display font-bold text-lg mb-1" style="color: var(--gz-ink);">
                        Reserve Your Slot
                    </h2>
                    <p class="text-xs mb-4" style="color: var(--gz-muted);">
                        Secure your spot in the communal pool via online payment.
                    </p>

                    @guest
                        <div class="p-6 rounded-xl text-center space-y-4" style="background: var(--gz-bg); border: 1px solid var(--gz-border);">
                            <svg class="w-10 h-10 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-bold" style="color: var(--gz-ink);">Please sign in to reserve</p>
                                <p class="text-xs mt-1" style="color: var(--gz-muted);">You need an active player account to join this Open Play session.</p>
                            </div>
                            <div class="flex items-center justify-center gap-3">
                                <a href="{{ route('login') }}" class="gz-btn-primary gz-btn-sm text-xs">
                                    Log In
                                </a>
                                <a href="{{ route('register') }}" class="gz-btn-outline gz-btn-sm text-xs">
                                    Register
                                </a>
                            </div>
                        </div>
                    @else
                        @if($session->is_full)
                            <div class="p-6 rounded-xl text-center space-y-3" style="background: var(--gz-bg); border: 1px solid var(--gz-border);">
                                <span class="gz-badge text-xs px-3 py-1 bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400 font-bold uppercase tracking-wider">
                                    Sold Out
                                </span>
                                <p class="text-sm font-bold" style="color: var(--gz-ink);">All player slots are filled</p>
                                <p class="text-xs" style="color: var(--gz-muted);">
                                    This Open Play session has reached its maximum capacity of {{ $session->max_capacity }} players.
                                </p>
                                <a href="{{ route('open-play.index') }}" class="gz-btn-outline gz-btn-sm text-xs inline-block">
                                    View Other Sessions
                                </a>
                            </div>
                        @else
                            <form method="POST" action="{{ route('open-play.reserve', $session) }}">
                                @csrf

                                <div class="space-y-4">
                                    {{-- Slot Quantity Selector --}}
                                    <div>
                                        <label for="slots_count" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                                            Number of Player Tickets
                                        </label>
                                        <div class="flex items-center gap-3">
                                            <button
                                                type="button"
                                                @click="decrementSlots()"
                                                :disabled="slotsCount <= 1"
                                                class="gz-btn-outline h-9 w-9 flex items-center justify-center text-base font-bold disabled:opacity-40"
                                            >-</button>

                                            <input
                                                type="number"
                                                id="slots_count"
                                                name="slots_count"
                                                x-model.number="slotsCount"
                                                min="1"
                                                :max="maxSelectable"
                                                class="gz-input text-center font-bold text-sm h-9 w-20"
                                                readonly
                                            >

                                            <button
                                                type="button"
                                                @click="incrementSlots()"
                                                :disabled="slotsCount >= maxSelectable"
                                                class="gz-btn-outline h-9 w-9 flex items-center justify-center text-base font-bold disabled:opacity-40"
                                            >+</button>

                                            <span class="text-xs" style="color: var(--gz-muted);" x-text="'(max ' + maxSelectable + ' available)'"></span>
                                        </div>
                                        <p class="text-[11px] mt-1" style="color: var(--gz-muted);">
                                            Reserving for friends? You can book multiple tickets in one checkout.
                                        </p>
                                    </div>

                                    {{-- Player Account Summary (Inherited via user_id) --}}
                                    <div class="p-3 rounded-xl border text-xs" style="border-color: var(--gz-border); background: var(--gz-bg);">
                                        <span class="block text-[10px] font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                                            Registered Player Account
                                        </span>
                                        <div class="font-bold" style="color: var(--gz-ink);">{{ Auth::user()->name }}</div>
                                        <div style="color: var(--gz-muted);">
                                            {{ Auth::user()->email }}
                                            @if(Auth::user()->phone)
                                                • {{ Auth::user()->phone }}
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Notes (Optional) --}}
                                    <div>
                                        <label for="notes" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                                            Additional Notes / Guest Names (Optional)
                                        </label>
                                        <input
                                            type="text"
                                            id="notes"
                                            name="notes"
                                            value="{{ old('notes') }}"
                                            placeholder="e.g. Booking for Geoff and Mark"
                                            class="gz-input w-full text-xs"
                                        >
                                    </div>

                                    {{-- Payment Method Selection --}}
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider mb-2" style="color: var(--gz-muted);">
                                            Payment Method <span class="text-red-500">*</span>
                                        </label>

                                        <input type="hidden" name="payment_method" value="paymongo">

                                        <div class="space-y-2">
                                            <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                                                   style="border-color: var(--gz-pop-dark); background: rgba(229, 168, 35, 0.05);">
                                                <input
                                                    type="radio"
                                                    checked
                                                    class="mt-0.5 text-[#E5A823] focus:ring-[#E5A823]"
                                                >
                                                <div>
                                                    <span class="block text-xs font-bold" style="color: var(--gz-pop-dark);">
                                                        GCash QR Scan (PayMongo)
                                                    </span>
                                                    <span class="block text-[11px]" style="color: var(--gz-muted);">
                                                        Scan to pay via GCash QR Code
                                                    </span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    {{-- Subtotal Summary --}}
                                    <div class="p-4 rounded-xl border mt-4" style="border-color: var(--gz-border); background: var(--gz-bg);">
                                        <div class="flex items-center justify-between text-xs mb-1" style="color: var(--gz-muted);">
                                            <span>Participation Fee (<span x-text="slotsCount + ' slot(s)'"></span>):</span>
                                            <span class="font-mono font-bold" x-text="'₱' + (slotsCount * pricePerSlot).toFixed(2)"></span>
                                        </div>
                                        <div class="border-t my-2" style="border-color: var(--gz-border);"></div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--gz-ink);">Total Due:</span>
                                            <span class="font-mono font-extrabold text-lg" style="color: var(--gz-pop-dark);" x-text="'₱' + (slotsCount * pricePerSlot).toFixed(2)"></span>
                                        </div>
                                    </div>

                                    {{-- Non-Refundable Payment Notice --}}
                                    <div class="p-3.5 rounded-xl flex items-start gap-3 text-xs bg-amber-500/10 border border-amber-500/30 mt-3">
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        <div>
                                            <strong class="block font-bold text-amber-700 dark:text-amber-400 mb-0.5 uppercase tracking-wide text-[11px]">Non-Refundable Payment Policy</strong>
                                            <p style="color: var(--gz-ink); line-height: 1.45;">
                                                Please review your slot count before proceeding. All confirmed event registrations and payments are final and <span class="font-bold underline">strictly non-refundable</span>.
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Submit Button --}}
                                    <button
                                        type="submit"
                                        class="gz-btn-primary w-full justify-center py-3 text-sm font-bold mt-2"
                                    >
                                        <span>
                                            Pay Online (<span x-text="'₱' + (slotsCount * pricePerSlot).toFixed(2)"></span>) →
                                        </span>
                                    </button>
                                </div>
                            </form>
                        @endif
                    @endguest
                </div>
            </div>
        </div>
    </div>

    <script>
        function openPlayReservationComponent(pricePerSlot, remainingSlots) {
            return {
                pricePerSlot: pricePerSlot,
                remainingSlots: remainingSlots,
                slotsCount: 1,
                paymentMethod: 'paymongo',
                get maxSelectable() {
                    return Math.min(this.remainingSlots, 6);
                },
                incrementSlots() {
                    if (this.slotsCount < this.maxSelectable) {
                        this.slotsCount++;
                    }
                },
                decrementSlots() {
                    if (this.slotsCount > 1) {
                        this.slotsCount--;
                    }
                }
            };
        }
    </script>
</x-app-layout>
