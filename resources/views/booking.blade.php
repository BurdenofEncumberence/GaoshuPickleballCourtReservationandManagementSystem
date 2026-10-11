<x-app-layout title="Reserve Courts — Gaoshou Pickleball">
    <div x-data="courtBookingComponent()">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
            <div>
                <h1 class="gz-font-display font-bold text-xl">Reserve Courts</h1>
                <p class="text-sm" style="color: var(--gz-muted);">
                    Select multiple courts or time slots across your preferred dates and book them all in one single checkout.
                </p>
            </div>
            <div x-show="selectedSlots.length > 0" class="flex items-center gap-3">
                <span class="gz-badge gz-badge-success" x-text="selectedSlots.length + (selectedSlots.length === 1 ? ' session selected' : ' sessions selected')"></span>
                <button type="button" @click="clearSlots()" class="text-xs font-semibold underline" style="color: var(--gz-danger);">
                    Clear Selection
                </button>
            </div>
        </div>

        {{-- Validation Error Alerts --}}
        @if ($errors->any())
            <div class="mb-4 p-4 rounded-xl text-sm" style="background: var(--gz-danger-bg); color: var(--gz-danger);" role="alert">
                <p class="font-bold mb-1">Please check your reservation selection:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- STEP 1: Date, Court & Time Slots Grid --}}
        <div x-show="step === 1">
            <section class="gz-panel mb-4" style="padding: 16px;">
                <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="gz-font-display font-bold text-sm" tabindex="-1" x-ref="step1Heading">
                            Pick Courts & Time Slots
                        </h2>
                        <p class="text-xs mt-0.5" style="color: var(--gz-muted);">
                            Click any open slot to add it. You can select multiple courts and times.
                        </p>
                    </div>
                    <div class="flex items-center gap-4 text-[11px]" style="color: var(--gz-muted);">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 inline-block rounded" style="background: var(--gz-surface); border: 1px solid var(--gz-border);" aria-hidden="true"></span> Open
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 inline-block rounded" style="background: var(--gz-pop);" aria-hidden="true"></span> Selected
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 inline-block rounded" style="background: var(--gz-bg); border: 1px solid var(--gz-border);" aria-hidden="true"></span> Booked
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                    {{-- Calendar on Left Column --}}
                    <div class="lg:col-span-4 self-start">
                        <div class="gz-kpi-card" style="padding: 12px;">
                            <div class="flex items-center justify-between mb-2">
                                <button type="button" @click="prevMonth()" class="gz-btn-outline gz-btn-sm" style="padding: 5px 10px;" aria-label="Previous month">‹</button>
                                <p class="gz-font-display font-bold text-xs" x-text="monthLabel" aria-live="polite"></p>
                                <button type="button" @click="nextMonth()" class="gz-btn-outline gz-btn-sm" style="padding: 5px 10px;" aria-label="Next month">›</button>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-[10px] text-center mb-1" style="color: var(--gz-muted);" aria-hidden="true">
                                <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-xs" role="grid" aria-label="Choose a date">
                                <template x-for="(day, idx) in calendarDays" :key="idx">
                                    <button
                                        type="button"
                                        x-show="day !== null"
                                        @click="pickDay(day)"
                                        :disabled="day === null || isPast(day)"
                                        :aria-current="day !== null && isToday(day) ? 'date' : null"
                                        :aria-pressed="day !== null && selectedDate === dateStringFor(day) ? 'true' : 'false'"
                                        :aria-label="day !== null ? (isToday(day) ? day + ', today' : day) : null"
                                        class="h-7 flex items-center justify-center rounded-lg"
                                        :style="
                                            day !== null && isPast(day)
                                                ? 'background: transparent; color: var(--gz-border); cursor: not-allowed;'
                                                : (day !== null && selectedDate === dateStringFor(day)
                                                    ? 'background: var(--gz-pop); color: var(--gz-ink); font-weight: 700;'
                                                    : (day !== null && isToday(day)
                                                        ? 'background: var(--gz-bg); border: 1.5px solid var(--gz-pop-dark); font-weight: 700;'
                                                        : 'background: transparent; border: 1px solid transparent;'))
                                        "
                                        x-text="day"
                                    ></button>
                                </template>
                            </div>
                            <div class="mt-3 pt-2 border-t flex items-center justify-between text-[11px]" style="border-color: var(--gz-border);">
                                <span style="color: var(--gz-muted);">Viewing Schedule for:</span>
                                <span class="font-bold" x-text="selectedDate" style="color: var(--gz-ink);"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Court Availability Table --}}
                    <div class="lg:col-span-8">
                        <div class="gz-panel overflow-hidden">
                            <div style="max-height: 290px; overflow-y: auto; overflow-x: auto;">
                                <table class="gz-table" style="font-size: 12px;">
                                    <caption class="sr-only">Court availability by time slot. Click to select multiple.</caption>
                                    <thead style="position: sticky; top: 0; z-index: 2; background: var(--gz-surface);">
                                        <tr>
                                            <th style="padding: 7px 10px;">Time</th>
                                            <template x-for="court in courts" :key="'head'+court.id">
                                                <th class="text-center" style="padding: 7px 8px;">
                                                    <span class="font-semibold block" x-text="court.name"></span>
                                                    <span x-show="court.size" class="inline-block text-[10px] font-medium px-1.5 py-0.5 rounded mt-0.5" style="background: rgba(46, 125, 50, 0.12); color: #1b5e20;" x-text="court.size"></span>
                                                    <span class="block text-[10px] font-normal mt-0.5" style="color: var(--gz-muted);" x-text="'₱' + Number(court.rate).toFixed(2) + '/hr'"></span>
                                                </th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="time in timeSlots" :key="time">
                                            <tr>
                                                <th scope="row" class="text-left font-semibold whitespace-nowrap" style="padding: 5px 10px;" x-text="time"></th>
                                                <template x-for="court in courts" :key="time + '-' + court.id">
                                                    <td style="padding: 4px;">
                                                        <button
                                                            type="button"
                                                            @click="toggleSlot(time, court.id)"
                                                            :disabled="isBooked(time, court.id)"
                                                            :aria-pressed="isSelected(time, court.id) ? 'true' : 'false'"
                                                            :title="getSlotTitle(time, court.id)"
                                                            class="w-full text-[11px] font-semibold rounded-lg transition"
                                                            style="height: 28px;"
                                                            :style="getSlotStyle(time, court.id)"
                                                            x-text="getSlotLabel(time, court.id)"
                                                        ></button>
                                                    </td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Schedule Legend & Info --}}
                            <div class="p-3 border-t flex flex-wrap items-center justify-between gap-3 text-[11px]" style="border-color: var(--gz-border); background: var(--gz-bg);">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded border" style="background: var(--gz-surface); border-color: var(--gz-border);"></span>
                                        <span style="color: var(--gz-muted);">Open</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded" style="background: var(--gz-pop);"></span>
                                        <span style="color: var(--gz-muted);">Selected</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded border" style="background: var(--gz-bg); border-color: var(--gz-border);"></span>
                                        <span style="color: var(--gz-muted);">Booked</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded border" style="background: rgba(229, 168, 35, 0.2); border-color: rgba(229, 168, 35, 0.6);"></span>
                                        <span class="font-semibold" style="color: #A67512;">Open Play</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded border" style="background: rgba(245, 158, 11, 0.2); border-color: rgba(245, 158, 11, 0.6);"></span>
                                        <span class="font-semibold" style="color: #B45309;">Tournament</span>
                                    </span>
                                </div>
                                <a href="{{ route('open-play.index') }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#A67512] hover:underline">
                                    Join Open Play & Tournaments →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

            {{-- Fixed Booking Cart Summary Bar --}}
            <section class="gz-panel flex flex-col sm:flex-row items-center justify-between gap-3" style="padding: 14px 16px;">
                <div>
                    <p class="gz-font-display font-bold text-sm mb-0.5">Booking Cart Summary</p>
                    <p class="text-xs" style="color: var(--gz-muted);" x-show="canPay">
                        <span class="font-semibold" style="color: var(--gz-ink);" x-text="selectedSlots.length + (selectedSlots.length === 1 ? ' session' : ' sessions')"></span> selected across your chosen court(s).
                        Subtotal: <span class="font-semibold" style="color: var(--gz-ink);" x-text="'₱' + courtsSubtotal.toFixed(2)"></span> ·
                        Est. Total (incl. service fee): <span class="font-bold" style="color: var(--gz-pop-dark);" x-text="'₱' + totalDue.toFixed(2)"></span>
                    </p>
                    <p class="text-xs" style="color: var(--gz-muted);" x-show="!canPay">
                        Select one or more open court time slots above to continue.
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <button type="button" x-show="selectedSlots.length > 0" x-cloak @click="clearSlots()" class="text-xs font-semibold underline" style="color: var(--gz-danger);">
                        Clear Selection
                    </button>
                    <button type="button" @click="goToReview()" :disabled="!canPay" class="gz-btn-primary gz-btn-sm whitespace-nowrap">
                        Review & Pay (<span x-text="selectedSlots.length"></span>) →
                    </button>
                </div>
            </section>
        </div>

        {{-- STEP 2: Review All Bookings & Process Single Transaction Payment --}}
        <div x-show="step === 2" x-cloak>
            <div class="flex items-center justify-between mb-4">
                <button type="button" @click="goBack()" class="gz-btn-outline gz-btn-sm">
                    ‹ Back to Court Selection
                </button>
                <span class="text-xs" style="color: var(--gz-muted);">
                    Step 2 of 2: Review & Single Checkout
                </span>
            </div>

            <h2 class="sr-only" tabindex="-1" x-ref="step2Heading">Review and pay for your bookings</h2>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- Left: Itemized Reservation Summary --}}
                <div class="lg:col-span-5">
                    <div class="gz-panel gz-panel-body">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="gz-font-display font-bold text-base">Reservation Summary</h3>
                            <span class="gz-badge gz-badge-neutral" x-text="selectedSlots.length + (selectedSlots.length === 1 ? ' item' : ' items')"></span>
                        </div>

                        {{-- Itemized Sessions List --}}
                        <div class="max-h-60 overflow-y-auto mb-4 divide-y" style="border-color: var(--gz-border);">
                            <template x-for="(slot, idx) in selectedSlots" :key="'sum-'+slot.courtId+'-'+slot.date+'-'+slot.time">
                                <div class="py-2.5 flex items-start justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <p class="font-semibold text-sm" x-text="slot.courtName"></p>
                                            <span x-show="slot.courtSize" class="text-[10px] font-medium px-1.5 py-0.2 rounded" style="background: rgba(46, 125, 50, 0.1); color: #1b5e20;" x-text="slot.courtSize"></span>
                                        </div>
                                        <p class="text-xs" style="color: var(--gz-muted);" x-text="slot.date + ' · ' + slot.time"></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="font-semibold text-sm" x-text="'₱' + Number(slot.rate).toFixed(2)"></p>
                                        <button type="button" @click="removeSlot(idx)" class="text-[11px] underline opacity-70 hover:opacity-100 hover:text-red-600">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Event Discounts --}}
                        <template x-if="events && events.length > 0">
                            <div class="pt-3 border-t mb-3" style="border-color: var(--gz-border);">
                                <label class="text-xs font-bold uppercase tracking-wider block mb-2" style="color: var(--gz-pop-dark);">Special Event Promotion</label>
                                <select x-model="selectedEvent" class="gz-input text-xs w-full py-1.5 mb-2">
                                    <option :value="null">No promotional discount</option>
                                    <template x-for="event in events" :key="event.id">
                                        <option :value="event.id" x-text="event.title + ' (' + event.discount + '% OFF)'"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        {{-- Financial Totals --}}
                        <div class="pt-3 border-t space-y-2 text-sm" style="border-color: var(--gz-border);">
                            <div class="flex justify-between">
                                <span style="color: var(--gz-muted);">Court Subtotal</span>
                                <span class="font-semibold" x-text="'₱' + courtsSubtotal.toFixed(2)"></span>
                            </div>
                            <template x-if="discountAmount > 0">
                                <div class="flex justify-between" style="color: var(--gz-pop-dark);">
                                    <span>Event Discount (<span x-text="eventDiscountPercent + '%'"></span>)</span>
                                    <span class="font-semibold" x-text="'-₱' + discountAmount.toFixed(2)"></span>
                                </div>
                            </template>
                            <template x-if="serviceFee > 0">
                                <div class="flex justify-between">
                                    <span style="color: var(--gz-muted);">Transaction Service Fee</span>
                                    <span class="font-semibold" x-text="'₱' + serviceFee.toFixed(2)"></span>
                                </div>
                            </template>
                        </div>

                        <div class="flex justify-between items-center pt-4 mt-4 border-t" style="border-color: var(--gz-border);">
                            <div>
                                <span class="gz-font-display font-bold text-sm block">Total Due</span>
                                <span class="text-[11px]" style="color: var(--gz-muted);">Single transaction</span>
                            </div>
                            <span class="gz-font-display font-bold text-xl" style="color: var(--gz-pop-dark);" x-text="'₱' + totalDue.toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                {{-- Right: Payment Method & Submission Form --}}
                <div class="lg:col-span-7">
                    <div class="gz-panel gz-panel-body">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="gz-font-display font-bold text-base">Payment Method</h3>
                                <p class="text-xs" style="color: var(--gz-muted);">Choose how you would like to settle your court reservation.</p>
                            </div>
                            <span class="gz-badge gz-badge-success text-[10px]">Instant Lock</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6" role="radiogroup" aria-label="Payment method">
                            {{-- Option 1: PayMongo Online --}}
                            <button type="button" @click="paymentMethod = 'paymongo'"
                                    role="radio" :aria-checked="paymentMethod === 'paymongo' ? 'true' : 'false'"
                                    class="gz-kpi-card text-left p-3.5 cursor-pointer transition relative"
                                    :style="paymentMethod === 'paymongo' ? 'border-color: var(--gz-pop); background: rgba(229,168,35,0.08);' : ''">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs shrink-0" style="background: rgba(229,168,35,0.2); color: var(--gz-pop-dark);">
                                            PAY
                                        </div>
                                        <div>
                                            <span class="text-sm font-bold block leading-tight">Pay Online</span>
                                            <span class="text-[11px]" style="color: var(--gz-muted);">Instant Confirmation</span>
                                        </div>
                                    </div>
                                    <span class="gz-badge gz-badge-success text-[9px]">Online Checkout</span>
                                </div>
                                <div class="flex items-center gap-1 flex-wrap mt-2 pt-2 border-t text-[10px]" style="border-color: var(--gz-border);">
                                    <span class="gz-badge gz-badge-pop font-semibold text-[9px]">QR Ph</span>
                                    <span class="gz-badge gz-badge-neutral text-[9px]">Bank</span>
                                    <span class="gz-badge gz-badge-neutral text-[9px]">GCash</span>
                                    <span class="gz-badge gz-badge-neutral text-[9px]">Maya</span>
                                    <span class="gz-badge gz-badge-neutral text-[9px]">Cards</span>
                                </div>
                            </button>

                            {{-- Option 2: Cash at Counter --}}
                            <button type="button" @click="paymentMethod = 'cash'"
                                    role="radio" :aria-checked="paymentMethod === 'cash' ? 'true' : 'false'"
                                    class="gz-kpi-card text-left p-3.5 cursor-pointer transition relative"
                                    :style="paymentMethod === 'cash' ? 'border-color: var(--gz-pop); background: rgba(229,168,35,0.08);' : ''">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs shrink-0" style="background: rgba(18,21,15,0.08); color: var(--gz-ink);">
                                            DESK
                                        </div>
                                        <div>
                                            <span class="text-sm font-bold block leading-tight">Cash at Counter</span>
                                            <span class="text-[11px]" style="color: var(--gz-muted);">Over-the-Counter</span>
                                        </div>
                                    </div>
                                    <span class="gz-badge gz-badge-neutral text-[9px]">Front Desk</span>
                                </div>
                                <p class="text-[10px] mt-2 pt-2 border-t leading-snug" style="border-color: var(--gz-border); color: var(--gz-muted);">
                                    Pay in person at the front desk cashier before your scheduled session starts.
                                </p>
                            </button>
                        </div>

                        {{-- Cash Info Panel --}}
                        <div x-show="paymentMethod === 'cash'" x-cloak class="mb-6 p-4 rounded-xl text-xs" style="background: var(--gz-surface); border: 1px solid var(--gz-border);">
                            <strong class="block font-bold text-sm mb-1">Over-the-Counter Payment</strong>
                            <p style="color: var(--gz-muted); line-height: 1.4;">
                                Your slots are reserved immediately. Please present your booking reference to the cashier at the Gaoshou Pickleball front desk before stepping onto the court.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('bookings.store') }}">
                            @csrf
                            {{-- JSON payload for multiple slots --}}
                            <input type="hidden" name="slots" :value="JSON.stringify(selectedSlots.map(s => ({ court_id: s.courtId, date: s.date, time_slot: s.time })))">

                            {{-- Legacy inputs for backward compatibility if 1 slot --}}
                            <input type="hidden" name="court_id" :value="selectedCourt">
                            <input type="hidden" name="date" :value="selectedSlots.length > 0 ? selectedSlots[0].date : selectedDate">
                            <input type="hidden" name="time_slot" :value="selectedTimeSlot">

                            <input type="hidden" name="payment_method" :value="paymentMethod">
                            <input type="hidden" name="event_id" :value="selectedEvent">

                            <button type="submit" :disabled="!canConfirm" class="gz-btn-primary w-full justify-center py-3 text-sm font-bold">
                                <template x-if="paymentMethod === 'paymongo'">
                                    <span>Pay Online (<span x-text="'₱' + totalDue.toFixed(2)"></span>) →</span>
                                </template>
                                <template x-if="paymentMethod === 'cash'">
                                    <span>Confirm Reservation & Pay at Counter (<span x-text="'₱' + totalDue.toFixed(2)"></span>)</span>
                                </template>
                                <template x-if="paymentMethod !== 'paymongo' && paymentMethod !== 'cash'">
                                    <span>Confirm & Pay (<span x-text="'₱' + totalDue.toFixed(2)"></span>)</span>
                                </template>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function courtBookingComponent() {
            return {
                selectedDate: @json(now()->toDateString()),
                currentMonth: {{ now()->month - 1 }},
                currentYear: {{ now()->year }},
                todayStr: @json(now()->toDateString()),

                courts: @json($courts),
                timeSlots: [
                    "6:00 AM - 7:00 AM", "7:00 AM - 8:00 AM", "8:00 AM - 9:00 AM", "9:00 AM - 10:00 AM",
                    "10:00 AM - 11:00 AM", "11:00 AM - 12:00 PM", "12:00 PM - 1:00 PM", "1:00 PM - 2:00 PM",
                    "2:00 PM - 3:00 PM", "3:00 PM - 4:00 PM", "4:00 PM - 5:00 PM", "5:00 PM - 6:00 PM",
                    "6:00 PM - 7:00 PM", "7:00 PM - 8:00 PM", "8:00 PM - 9:00 PM", "9:00 PM - 10:00 PM",
                ],

                bookedSlots: @json($bookedSlots ?? []),
                specialSlots: @json($specialSlots ?? []),
                selectedSlots: [],

                getSpecialSlot(time, courtId, date = null) {
                    const d = date || this.selectedDate;
                    const daySpecial = this.specialSlots[d] || {};
                    const courtSpecial = daySpecial[courtId] || {};
                    return courtSpecial[time] || null;
                },

                isBooked(time, courtId, date = null) {
                    const d = date || this.selectedDate;
                    const dayBookings = this.bookedSlots[d] || {};
                    const courtBookings = dayBookings[courtId] || [];
                    return courtBookings.includes(time);
                },

                isSelected(time, courtId, date = null) {
                    const d = date || this.selectedDate;
                    return this.selectedSlots.some(s => s.courtId === courtId && s.time === time && s.date === d);
                },

                getSlotStatus(time, courtId, date = null) {
                    const special = this.getSpecialSlot(time, courtId, date);
                    if (special) {
                        return special.type === 'tournament' ? 'tournament' : 'open_play';
                    }
                    if (this.isBooked(time, courtId, date)) {
                        return 'booked';
                    }
                    if (this.isSelected(time, courtId, date)) {
                        return 'selected';
                    }
                    return 'open';
                },

                getSlotLabel(time, courtId, date = null) {
                    const special = this.getSpecialSlot(time, courtId, date);
                    if (special) {
                        return special.type === 'tournament' ? 'Tournament' : 'Open Play';
                    }
                    if (this.isBooked(time, courtId, date)) {
                        return 'Booked';
                    }
                    if (this.isSelected(time, courtId, date)) {
                        return 'Selected';
                    }
                    return 'Open';
                },

                getSlotTitle(time, courtId, date = null) {
                    const special = this.getSpecialSlot(time, courtId, date);
                    if (special) {
                        const typeLabel = special.type === 'tournament' ? 'Tournament' : 'Open Play';
                        return typeLabel + ': ' + (special.title || 'Reserved Session') + ' (Occupied)';
                    }
                    if (this.isBooked(time, courtId, date)) {
                        return 'Already booked for private reservation';
                    }
                    return 'Available for booking - click to select';
                },

                getSlotStyle(time, courtId, date = null) {
                    const status = this.getSlotStatus(time, courtId, date);
                    if (status === 'tournament') {
                        return 'height: 28px; background: rgba(245, 158, 11, 0.16); color: #B45309; border: 1.5px solid rgba(245, 158, 11, 0.55); font-weight: 700; cursor: not-allowed;';
                    }
                    if (status === 'open_play') {
                        return 'height: 28px; background: rgba(229, 168, 35, 0.16); color: #A67512; border: 1.5px solid rgba(229, 168, 35, 0.55); font-weight: 700; cursor: not-allowed;';
                    }
                    if (status === 'booked') {
                        return 'height: 28px; background: var(--gz-bg); color: var(--gz-muted); cursor: not-allowed; border: 1px solid var(--gz-border); opacity: 0.65;';
                    }
                    if (status === 'selected') {
                        return 'height: 28px; background: var(--gz-pop); color: var(--gz-ink); font-weight: 700; cursor: pointer;';
                    }
                    return 'height: 28px; background: var(--gz-surface); border: 1px solid var(--gz-border); cursor: pointer; color: var(--gz-ink);';
                },

                toggleSlot(time, courtId) {
                    if (this.isBooked(time, courtId)) return;
                    const d = this.selectedDate;
                    const idx = this.selectedSlots.findIndex(s => s.courtId === courtId && s.time === time && s.date === d);
                    if (idx > -1) {
                        this.selectedSlots.splice(idx, 1);
                    } else {
                        const court = this.courts.find(c => c.id === courtId);
                        this.selectedSlots.push({
                            courtId: courtId,
                            courtName: court ? court.name : ("Court " + courtId),
                            courtSize: court ? court.size : "",
                            rate: court ? Number(court.rate) : 0,
                            date: d,
                            time: time
                        });
                    }
                },

                removeSlot(index) {
                    this.selectedSlots.splice(index, 1);
                    if (this.selectedSlots.length === 0 && this.step === 2) {
                        this.step = 1;
                    }
                },

                clearSlots() {
                    this.selectedSlots = [];
                },

                events: @json($events ?? []),
                selectedEvent: null,
                get selectedEventObj() {
                    return this.events.find(e => e.id === this.selectedEvent);
                },
                get eventDiscountPercent() {
                    return this.selectedEventObj ? (Number(this.selectedEventObj.discount) || 0) : 0;
                },
                get discountAmount() {
                    return this.eventDiscountPercent > 0 ? (this.courtsSubtotal * (this.eventDiscountPercent / 100)) : 0;
                },

                get selectedCourt() {
                    return this.selectedSlots.length > 0 ? this.selectedSlots[0].courtId : null;
                },
                get selectedTimeSlot() {
                    return this.selectedSlots.length > 0 ? this.selectedSlots[0].time : null;
                },
                get selectedCourtName() {
                    return this.selectedSlots.length > 0 ? this.selectedSlots[0].courtName : null;
                },

                get courtsSubtotal() {
                    return this.selectedSlots.reduce((acc, s) => acc + (Number(s.rate) || 0), 0);
                },

                serviceFee: 0,

                get totalDue() {
                    if (this.selectedSlots.length === 0) return 0;
                    const sub = this.courtsSubtotal - this.discountAmount;
                    return Math.max(0, sub + this.serviceFee);
                },

                get canPay() {
                    return this.selectedSlots.length > 0;
                },

                step: 1,
                paymentMethod: 'paymongo',

                get canConfirm() {
                    return this.canPay && this.paymentMethod !== null;
                },

                pickDate(date) {
                    this.selectedDate = date;
                },

                get calendarDays() {
                    const firstDay = new Date(this.currentYear, this.currentMonth, 1).getDay();
                    const daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
                    const days = [];
                    for (let i = 0; i < firstDay; i++) days.push(null);
                    for (let d = 1; d <= daysInMonth; d++) days.push(d);
                    return days;
                },

                dateStringFor(day) {
                    const mm = String(this.currentMonth + 1).padStart(2, "0");
                    const dd = String(day).padStart(2, "0");
                    return `${this.currentYear}-${mm}-${dd}`;
                },

                isPast(day) {
                    return this.dateStringFor(day) < this.todayStr;
                },

                isToday(day) {
                    return this.dateStringFor(day) === this.todayStr;
                },

                pickDay(day) {
                    if (this.isPast(day)) return;
                    this.pickDate(this.dateStringFor(day));
                },

                prevMonth() {
                    if (this.currentMonth === 0) {
                        this.currentMonth = 11;
                        this.currentYear--;
                    } else {
                        this.currentMonth--;
                    }
                },

                nextMonth() {
                    if (this.currentMonth === 11) {
                        this.currentMonth = 0;
                        this.currentYear++;
                    } else {
                        this.currentMonth++;
                    }
                },

                get monthLabel() {
                    const names = ["January","February","March","April","May","June","July","August","September","October","November","December"];
                    return names[this.currentMonth] + " " + this.currentYear;
                },

                goToReview() {
                    if (this.canPay) {
                        this.step = 2;
                        this.$nextTick(() => this.$refs.step2Heading?.focus());
                    }
                },

                goBack() {
                    this.step = 1;
                    this.$nextTick(() => this.$refs.step1Heading?.focus());
                },
            };
        }
    </script>
</x-app-layout>