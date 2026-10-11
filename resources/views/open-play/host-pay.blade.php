<x-app-layout title="Secure Court Appointment — Gaoshou Pickleball">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6" x-data="{ paymentMethod: 'paymongo' }">
        {{-- Breadcrumbs & Header --}}
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs mb-2" style="color: var(--gz-muted);">
                <a href="{{ route('open-play.index') }}" class="hover:underline">Open Play</a>
                <span>/</span>
                <a href="{{ route('open-play.host.index') }}" class="hover:underline">My Hosted Sessions</a>
                <span>/</span>
                <span class="font-semibold" style="color: var(--gz-ink);">Court Payment</span>
            </div>
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="gz-font-display font-extrabold text-2xl sm:text-3xl tracking-tight" style="color: var(--gz-ink);">
                        Secure Court Appointment
                    </h1>
                    <p class="text-sm mt-1" style="color: var(--gz-muted);">
                        Your Open Play session has been accepted by the facility manager! Complete payment to finalize your court reservation.
                    </p>
                </div>
                <a href="{{ route('open-play.host.index') }}" class="gz-btn-outline gz-btn-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back to Hosted Sessions</span>
                </a>
            </div>
        </div>

        {{-- Accepted Request Banner --}}
        <div class="gz-panel p-4 mb-6" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25);">
            <div class="flex items-start gap-3">
                <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-xs space-y-1" style="color: var(--gz-ink);">
                    <p class="font-bold text-sm text-emerald-700 dark:text-emerald-400">Request Accepted by Facility Manager</p>
                    <p style="color: var(--gz-muted);">
                        The manager has approved your date and court allocations. Pay the court appointment fee below to officially lock in the courts and open registrations for other players.
                    </p>
                    @if ($session->manager_note)
                        <div class="mt-2 p-2.5 rounded bg-white/60 dark:bg-black/20 border border-emerald-500/20 text-xs">
                            <span class="font-bold uppercase tracking-wider text-[10px]" style="color: var(--gz-muted);">Manager Note:</span>
                            <p class="mt-0.5" style="color: var(--gz-ink);">{{ $session->manager_note }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Session Overview & Court Breakdown --}}
        <div class="gz-panel p-6 mb-6">
            <h2 class="gz-font-display font-bold text-base mb-4" style="color: var(--gz-ink);">
                Session & Court Allocation Summary
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs mb-4">
                <div class="p-3.5 rounded-xl border" style="background: var(--gz-surface); border-color: var(--gz-border);">
                    <span class="text-[11px] font-bold uppercase tracking-wider block mb-1" style="color: var(--gz-muted);">Session Title</span>
                    <span class="text-sm font-bold" style="color: var(--gz-ink);">{{ $session->title }}</span>
                </div>

                <div class="p-3.5 rounded-xl border" style="background: var(--gz-surface); border-color: var(--gz-border);">
                    <span class="text-[11px] font-bold uppercase tracking-wider block mb-1" style="color: var(--gz-muted);">Schedule</span>
                    <span class="text-sm font-semibold" style="color: var(--gz-ink);">
                        {{ $session->date->format('l, F j, Y') }}
                    </span>
                    <span class="block text-xs mt-0.5" style="color: var(--gz-muted);">{{ $session->time_window }}</span>
                </div>
            </div>

            <div class="border rounded-xl p-4 mb-4" style="border-color: var(--gz-border); background: var(--gz-surface);">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--gz-muted);">
                        Allocated Courts ({{ $session->courts->count() }} {{ \Illuminate\Support\Str::plural('Court', $session->courts->count()) }})
                    </span>
                    <span class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--gz-muted);">
                        Fee Breakdown
                    </span>
                </div>
                <div class="divide-y text-xs" style="border-color: var(--gz-border);">
                    @foreach($session->courts as $court)
                        @php
                            $courtSubtotal = round($court->price_per_hour * $session->duration_hours, 2);
                        @endphp
                        <div class="py-2.5 flex items-center justify-between">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-sm" style="color: var(--gz-ink);">{{ $court->court_name }}</span>
                                    @if($court->court_type)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold uppercase tracking-wider bg-black/5 dark:bg-white/10" style="color: var(--gz-muted);">
                                            {{ ucfirst($court->court_type) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px]" style="color: var(--gz-muted);">
                                    ₱{{ number_format($court->price_per_hour, 2) }}/hr × {{ $session->duration_hours }} hr(s)
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-bold text-sm" style="color: var(--gz-ink);">
                                    ₱{{ number_format($courtSubtotal, 2) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Fee Computation --}}
            <div class="p-4 rounded-xl border text-xs space-y-1.5" style="background: var(--gz-bg); border-color: var(--gz-border);">
                <div class="flex items-center justify-between" style="color: var(--gz-muted);">
                    <span>Number of Reserved Courts:</span>
                    <span class="font-semibold">{{ $session->courts->count() }} court(s)</span>
                </div>
                <div class="flex items-center justify-between" style="color: var(--gz-muted);">
                    <span>Combined Hourly Rate:</span>
                    <span class="font-mono font-semibold">₱{{ number_format($session->courts->sum('price_per_hour'), 2) }}/hr</span>
                </div>
                <div class="flex items-center justify-between" style="color: var(--gz-muted);">
                    <span>Session Duration:</span>
                    <span class="font-semibold">{{ $session->duration_hours }} hour(s)</span>
                </div>
                <div class="border-t my-2" style="border-color: var(--gz-border);"></div>
                <div class="flex items-center justify-between pt-1">
                    <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--gz-ink);">Total Court Appointment Fee:</span>
                    <span class="gz-font-display text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">
                        ₱{{ number_format($session->court_fee, 2) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Payment Selection Form --}}
        <div class="gz-panel p-6">
            <h2 class="gz-font-display font-bold text-base mb-1" style="color: var(--gz-ink);">
                Select Payment Method
            </h2>
            <p class="text-xs mb-4" style="color: var(--gz-muted);">
                Choose how you would like to settle the court hire fee to secure your appointment.
            </p>

            <form method="POST" action="{{ route('open-play.host.pay.process', $session) }}">
                @csrf

                <div class="space-y-3 mb-6">
                    <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer hover:bg-black/5 dark:hover:bg-white/5 transition"
                           :style="paymentMethod === 'paymongo' ? 'border-color: #10b981; background: rgba(16, 185, 129, 0.05);' : 'border-color: var(--gz-border);'">
                        <input
                            type="radio"
                            name="payment_method"
                            value="paymongo"
                            x-model="paymentMethod"
                            class="mt-1 text-emerald-600 focus:ring-emerald-500"
                        >
                        <div class="flex-1">
                            <span class="block text-sm font-bold" style="color: var(--gz-ink);">
                                Pay Online (PayMongo)
                            </span>
                            <span class="block text-xs mt-0.5" style="color: var(--gz-muted);">
                                Instant verification via GCash, Maya, Debit/Credit Card, or Online Banking. Your courts are secured immediately.
                            </span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer hover:bg-black/5 dark:hover:bg-white/5 transition"
                           :style="paymentMethod === 'cash' ? 'border-color: #10b981; background: rgba(16, 185, 129, 0.05);' : 'border-color: var(--gz-border);'">
                        <input
                            type="radio"
                            name="payment_method"
                            value="cash"
                            x-model="paymentMethod"
                            class="mt-1 text-emerald-600 focus:ring-emerald-500"
                        >
                        <div class="flex-1">
                            <span class="block text-sm font-bold" style="color: var(--gz-ink);">
                                Cash at Front Desk
                            </span>
                            <span class="block text-xs mt-0.5" style="color: var(--gz-muted);">
                                Reserve and secure courts now. Settle ₱{{ number_format($session->court_fee, 2) }} in cash with the front desk cashier prior to the session start.
                            </span>
                        </div>
                    </label>
                </div>

                {{-- Non-Refundable Payment Notice --}}
                <div class="p-3.5 rounded-xl flex items-start gap-3 text-xs bg-amber-500/10 border border-amber-500/30">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <strong class="block font-bold text-amber-700 dark:text-amber-400 mb-0.5 uppercase tracking-wide text-[11px]">Non-Refundable Payment Policy</strong>
                        <p style="color: var(--gz-ink); line-height: 1.45;">
                            Please review your session details before paying. All host court fee payments are final and <span class="font-bold underline">strictly non-refundable</span> once confirmed.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t" style="border-color: var(--gz-border);">
                    <a href="{{ route('open-play.host.index') }}" class="gz-btn-outline text-sm">
                        Cancel
                    </a>

                    <button type="submit" class="gz-btn-primary text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span x-text="paymentMethod === 'paymongo' ? 'Proceed to Online Payment (₱' + '{{ number_format($session->court_fee, 2) }}' + ')' : 'Confirm Cash Payment & Secure Courts'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
