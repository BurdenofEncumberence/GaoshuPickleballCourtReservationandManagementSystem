<x-staff-layout title="Front Desk · Walk-In Booking · Gaoshou Pickleball" heading="New Walk-In Booking">
    {{-- Header Banner --}}
    <div class="gz-panel gz-panel-body mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-block w-2 h-2" style="background: var(--gz-pop);"></span>
                <span class="gz-eyebrow" style="color: var(--gz-pop-dark);">Front desk · On-the-spot reservation</span>
            </div>
            <h1 class="gz-font-display font-bold text-xl">
                Walk-in Court Booking
            </h1>
            <p class="text-sm mt-1" style="color: var(--gz-muted);">
                Reserve a court immediately for counter walk-ins and collect payment on arrival.
            </p>
        </div>
        <div class="flex items-center gap-2 print:hidden">
            <a href="{{ route('staff.today') }}" class="gz-btn-outline gz-btn-sm">
                ← Back to Today's Run-Sheet
            </a>
        </div>
    </div>

    {{-- Error / Status Alerts --}}
    @if ($errors->any())
        <div class="mb-6 p-4 border" style="background: rgba(179,38,30,0.08); border-color: var(--red);" role="alert">
            <div class="flex items-center gap-2 mb-2 font-bold text-sm" style="color: var(--red);">
                <span>Please fix the following errors:</span>
            </div>
            <ul class="text-xs list-disc list-inside space-y-1" style="color: var(--gz-ink);">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <script>
        function walkInBookingData() {
            return {
                selectedDate: @json(old('date', $selectedDate)),
                selectedCourtId: @json((string) old('court_id', $preselectedCourtId ?? $courts->first()->id ?? '')),
                selectedSlot: @json(old('time_slot', $preselectedSlot ?? '')),
                selectedPaymentMethod: @json(old('payment_method', 'cash')),
                attendanceStatus: @json(old('attendance_status', 'show')),
                firstName: @json(old('first_name', '')),
                middleName: @json(old('middle_name', '')),
                lastName: @json(old('last_name', '')),
                email: @json(old('email', '')),
                cashTendered: @json(old('cash_tendered', '')),

                courts: @json($courts),
                bookedSlots: @json($bookedSlots),
                bookedSlotDetails: @json($bookedSlotDetails ?? []),
                specialSlots: @json($specialSlots ?? []),
                recentPlayers: @json($recentPlayers),
                activeBooking: null,

                get currentCourt() {
                    return this.courts.find(c => c.id == this.selectedCourtId) || null;
                },

                get courtRate() {
                    return this.currentCourt ? Number(this.currentCourt.price_per_hour) : 0;
                },

                get cashTenderedNum() {
                    const parsed = parseFloat(this.cashTendered);
                    return isNaN(parsed) ? 0 : parsed;
                },

                get changeDue() {
                    if (this.selectedPaymentMethod !== 'cash') return 0;
                    return Math.max(0, this.cashTenderedNum - this.courtRate);
                },

                get isCashValid() {
                    if (this.selectedPaymentMethod !== 'cash') return true;
                    if (this.cashTendered === '' || this.cashTendered === null) return false;
                    return this.cashTenderedNum >= this.courtRate;
                },

                get cashShortage() {
                    if (this.selectedPaymentMethod !== 'cash') return 0;
                    return Math.max(0, this.courtRate - this.cashTenderedNum);
                },

                setExactCash() {
                    this.cashTendered = this.courtRate > 0 ? this.courtRate.toFixed(2) : '0.00';
                },

                setCashAmount(val) {
                    this.cashTendered = Number(val).toFixed(2);
                },

                getSpecialSlotForCourt(courtId, slot) {
                    const courtSpecial = this.specialSlots[courtId] || {};
                    return courtSpecial[slot] || null;
                },

                isSlotBookedForCourt(courtId, slot) {
                    const courtBookings = this.bookedSlots[courtId] || [];
                    return courtBookings.includes(slot);
                },

                isSlotSelectedForCourt(courtId, slot) {
                    return String(this.selectedCourtId) === String(courtId) && this.selectedSlot === slot;
                },

                getSlotLabelForCourt(courtId, slot) {
                    const special = this.getSpecialSlotForCourt(courtId, slot);
                    if (special) {
                        return special.type === 'tournament' ? 'Tournament' : 'Open Play';
                    }
                    if (this.isSlotBookedForCourt(courtId, slot)) {
                        return 'Booked';
                    }
                    if (this.isSlotSelectedForCourt(courtId, slot)) {
                        return 'Selected';
                    }
                    return 'Open';
                },

                handleCellClick(courtId, slot) {
                    const bookedDetail = (this.bookedSlotDetails[courtId] || {})[slot] || null;
                    if (bookedDetail) {
                        this.activeBooking = bookedDetail;
                        return;
                    }
                    if (this.isSlotBookedForCourt(courtId, slot)) {
                        return;
                    }
                    this.selectedCourtId = String(courtId);
                    this.selectedSlot = slot;
                },

                closeInspector() {
                    this.activeBooking = null;
                },

                selectPlayer(event) {
                    const playerId = event.target.value;
                    if (!playerId) return;
                    const player = this.recentPlayers.find(p => p.id == playerId);
                    if (player) {
                        this.firstName = player.first_name || '';
                        this.lastName = player.last_name || '';
                        this.email = player.email || '';
                    }
                },

                onDateChange() {
                    window.location.href = @json(route('staff.walkin.create')) + '?date=' + this.selectedDate;
                }
            };
        }
    </script>

    <div x-data="walkInBookingData()" @keydown.escape.window="closeInspector()">
        <form method="POST" action="{{ route('staff.walkin.store') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @csrf

            {{-- LEFT 2 COLUMNS: Booking Options & Slot Selection --}}
            <div class="lg:col-span-2 min-w-0 space-y-6">
                {{-- Date & Court Chooser --}}
                <div class="gz-panel gz-panel-body">
                    <h2 class="gz-font-display font-bold text-base mb-4 flex items-center justify-between">
                        <span>1. Date &amp; Court Selection</span>
                        <span class="text-xs font-normal" style="color: var(--gz-muted);">Matches 1-hour blocks</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Date picker --}}
                        <div>
                            <label for="date" class="gz-label">Reservation Date</label>
                            <input
                                id="date"
                                type="date"
                                name="date"
                                min="{{ today()->toDateString() }}"
                                x-model="selectedDate"
                                @change="onDateChange()"
                                class="gz-input"
                                required
                            >
                            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Changing date refreshes live court availability.</p>
                        </div>

                        {{-- Court picker --}}
                        <div>
                            <label for="court_id" class="gz-label">Court</label>
                            <select
                                id="court_id"
                                name="court_id"
                                x-model="selectedCourtId"
                                class="gz-input"
                                required
                            >
                                @foreach($courts as $court)
                                    <option value="{{ $court->id }}">
                                        {{ $court->court_name }} ({{ $court->size ?: 'Regular (13.41m x 6.10m)' }}) · ₱{{ number_format($court->price_per_hour, 2) }}/hr
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Or click directly on any court column in the schedule matrix below.</p>
                        </div>
                    </div>
                </div>

                {{-- Multi-Court Time Slot Schedule Matrix --}}
                <div class="gz-panel overflow-hidden">
                    <div class="p-4 border-b flex items-center justify-between flex-wrap gap-2" style="border-color: var(--gz-border);">
                        <div>
                            <h2 class="gz-font-display font-bold text-base">
                                2. Choose Available Time Slot
                            </h2>
                            <p class="text-xs mt-0.5" style="color: var(--gz-muted);">
                                Click an <strong>Open</strong> slot to select it, or click any <strong>Booked</strong> slot to view who booked that court time and how much they paid.
                            </p>
                        </div>
                        <template x-if="selectedSlot">
                            <span class="gz-badge gz-badge-success" x-text="(currentCourt ? currentCourt.court_name + ' · ' : '') + selectedSlot"></span>
                        </template>
                    </div>

                    <input type="hidden" name="time_slot" :value="selectedSlot" required>

                    <div style="max-height: 360px; overflow-y: auto; overflow-x: auto;">
                        <table class="gz-table" style="font-size: 12px;">
                            <thead style="position: sticky; top: 0; z-index: 2; background: var(--gz-surface);">
                                <tr>
                                    <th class="text-left" style="padding: 10px 14px; min-width: 145px;">TIME</th>
                                    @foreach($courts as $court)
                                        <th class="text-left" style="padding: 10px 12px; min-width: 160px;">
                                            <span class="font-bold uppercase tracking-wide block text-xs" style="color: var(--gz-ink);">
                                                {{ $court->court_name }}
                                            </span>
                                            @if($court->size)
                                                <span class="inline-block text-[10px] font-semibold uppercase px-2 py-0.5 rounded mt-1" style="background: rgba(46, 125, 50, 0.12); color: #1b5e20;">
                                                    {{ $court->size }}
                                                </span>
                                            @endif
                                            <span class="block text-[11px] font-normal mt-1" style="color: var(--gz-muted);">
                                                ₱{{ number_format($court->price_per_hour, 2) }}/HR
                                            </span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timeSlots as $slot)
                                    <tr>
                                        <th scope="row" class="text-left font-bold whitespace-nowrap" style="padding: 6px 14px; color: var(--gz-muted);">
                                            {{ $slot }}
                                        </th>
                                        @foreach($courts as $court)
                                            <td style="padding: 4px 8px;">
                                                <button
                                                    type="button"
                                                    @click="handleCellClick({{ $court->id }}, @js($slot))"
                                                    :disabled="getSpecialSlotForCourt({{ $court->id }}, @js($slot)) !== null"
                                                    class="w-full text-xs font-semibold rounded-lg transition flex items-center justify-center border"
                                                    style="height: 32px;"
                                                    :style="
                                                        getSpecialSlotForCourt({{ $court->id }}, @js($slot))
                                                            ? (getSpecialSlotForCourt({{ $court->id }}, @js($slot)).type === 'tournament'
                                                                ? 'background: rgba(245, 158, 11, 0.22); border-color: rgba(245, 158, 11, 0.65); color: #B45309; cursor: not-allowed;'
                                                                : 'background: rgba(229, 168, 35, 0.2); border-color: rgba(229, 168, 35, 0.6); color: #A67512; cursor: not-allowed;')
                                                            : (isSlotBookedForCourt({{ $court->id }}, @js($slot))
                                                                ? 'background: var(--gz-bg); border-color: var(--gz-border); color: var(--gz-muted); font-weight: 700; cursor: pointer;'
                                                                : (isSlotSelectedForCourt({{ $court->id }}, @js($slot))
                                                                    ? 'background: var(--gz-pop); border-color: var(--gz-pop-dark); color: #12150F; font-weight: 800;'
                                                                    : 'background: var(--gz-surface); border-color: var(--gz-border); color: var(--gz-ink); cursor: pointer;'))
                                                    "
                                                    x-text="getSlotLabelForCourt({{ $court->id }}, @js($slot))"
                                                ></button>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

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
                                <span style="color: var(--gz-muted);">Booked (Click to inspect customer &amp; payment)</span>
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
                    </div>

                    @error('time_slot')
                        <div class="p-3 border-t" style="border-color: var(--gz-border);">
                            <p class="gz-error" role="alert">{{ $message }}</p>
                        </div>
                    @enderror
                </div>

                {{-- Customer Details --}}
                <div class="gz-panel gz-panel-body">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                        <h2 class="gz-font-display font-bold text-base">
                            3. Customer Information
                        </h2>

                        {{-- Quick Autofill dropdown --}}
                        @if($recentPlayers->isNotEmpty())
                            <div class="flex items-center gap-2">
                                <label for="quick_player" class="text-xs whitespace-nowrap" style="color: var(--gz-muted);">Autofill Player:</label>
                                <select id="quick_player" @change="selectPlayer($event)" class="text-xs p-1 border bg-white text-[#12150F]" style="border-color: var(--gz-border);">
                                    <option value="">-- Choose member --</option>
                                    @foreach($recentPlayers as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="gz-label">First Name <span class="text-red-600">*</span></label>
                            <input
                                id="first_name"
                                type="text"
                                name="first_name"
                                x-model="firstName"
                                class="gz-input capitalize"
                                placeholder="e.g. Juan"
                                required
                            >
                            @error('first_name')
                                <p class="gz-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="last_name" class="gz-label">Last Name <span class="text-red-600">*</span></label>
                            <input
                                id="last_name"
                                type="text"
                                name="last_name"
                                x-model="lastName"
                                class="gz-input capitalize"
                                placeholder="e.g. Dela Cruz"
                                required
                            >
                            @error('last_name')
                                <p class="gz-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="middle_name" class="gz-label">Middle Name <span class="text-xs font-normal opacity-70">(optional)</span></label>
                            <input
                                id="middle_name"
                                type="text"
                                name="middle_name"
                                x-model="middleName"
                                class="gz-input capitalize"
                                placeholder="e.g. Santos"
                            >
                        </div>

                        <div>
                            <label for="phone" class="gz-label">Phone / Mobile <span class="text-xs font-normal opacity-70">(optional)</span></label>
                            <input
                                id="phone"
                                type="text"
                                name="phone"
                                value="{{ old('phone') }}"
                                class="gz-input"
                                placeholder="e.g. 0917-123-4567"
                            >
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="email" class="gz-label">Email Address <span class="text-xs font-normal opacity-70">(optional)</span></label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            x-model="email"
                            class="gz-input"
                            placeholder="e.g. customer@example.com"
                        >
                        <p class="text-[11px] mt-1" style="color: var(--gz-muted);">
                            If provided, existing player account is linked. If omitted, a guest walk-in profile is automatically generated.
                        </p>
                        @error('email')
                            <p class="gz-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: Payment, Attendance & Confirmation --}}
            <div class="space-y-6">
                {{-- Payment & Checkout Card --}}
                <div class="gz-panel gz-panel-body">
                    <h2 class="gz-font-display font-bold text-base mb-4">
                        4. Counter Payment
                    </h2>

                    {{-- Rate Calculation --}}
                    <div class="p-3 border mb-4" style="background: var(--gz-bg); border-color: var(--gz-border);">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span style="color: var(--gz-muted);">Rate per hour:</span>
                            <span class="font-mono font-bold" x-text="'₱' + courtRate.toFixed(2)"></span>
                        </div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span style="color: var(--gz-muted);">Duration:</span>
                            <span class="font-mono">1 Hour</span>
                        </div>
                        <div class="border-t pt-2 mt-2 flex items-center justify-between" style="border-color: var(--gz-border);">
                            <span class="font-bold text-sm">Total Due:</span>
                            <span class="font-mono font-bold text-lg" style="color: var(--gz-pop-dark);" x-text="'₱' + courtRate.toFixed(2)"></span>
                        </div>
                    </div>

                    {{-- Payment Method Selection --}}
                    <div class="mb-4">
                        <label class="gz-label">Payment Method Collected</label>
                        <div class="space-y-2 mt-1">
                            {{-- Option 1: PayMongo Online Gateway --}}
                            <label class="flex items-start gap-2.5 p-2.5 border cursor-pointer text-xs rounded transition"
                                   :style="selectedPaymentMethod === 'paymongo' ? 'border-color: var(--gz-pop-dark); background: rgba(229,168,35,0.08);' : 'border-color: var(--gz-border);'">
                                <input type="radio" name="payment_method" value="paymongo" x-model="selectedPaymentMethod" class="mt-0.5">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold" style="color: var(--gz-pop-dark);">Pay Online (PayMongo)</span>
                                        <span class="gz-badge gz-badge-success text-[9px]">Online Gateway</span>
                                    </div>
                                    <span class="text-[10px] block mt-0.5" style="color: var(--gz-muted);">QR Ph · GCash · Maya · Debit/Credit Card · Online Banking</span>
                                </div>
                            </label>

                            {{-- Option 2: Cash --}}
                            <label class="flex items-start gap-2.5 p-2.5 border cursor-pointer text-xs rounded transition"
                                   :style="selectedPaymentMethod === 'cash' ? 'border-color: var(--gz-pop-dark); background: rgba(229,168,35,0.08);' : 'border-color: var(--gz-border);'">
                                <input type="radio" name="payment_method" value="cash" x-model="selectedPaymentMethod" class="mt-0.5">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-[color:var(--gz-ink)]">Cash</span>
                                        <span class="gz-badge gz-badge-neutral text-[9px]">Front Desk</span>
                                    </div>
                                    <span class="text-[10px] block mt-0.5" style="color: var(--gz-muted);">Physical cash collected and verified at the counter</span>
                                </div>
                            </label>

                            {{-- Cash Tendered & Change Calculator (Active when Cash is selected) --}}
                            <div x-show="selectedPaymentMethod === 'cash'" x-transition class="p-3.5 border rounded-lg space-y-3" style="background: rgba(229,168,35,0.05); border-color: var(--gz-pop-dark);">
                                <div class="flex items-center justify-between">
                                    <label for="cash_tendered" class="gz-label text-xs font-bold mb-0">
                                        Amount Paid by Customer <span class="text-red-600">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="setExactCash()"
                                        class="text-[11px] font-bold underline cursor-pointer"
                                        style="color: var(--gz-pop-dark);"
                                    >
                                        Exact Amount
                                    </button>
                                </div>

                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none font-bold text-sm text-[color:var(--gz-muted)]">₱</span>
                                    <input
                                        id="cash_tendered"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="cash_tendered"
                                        x-model="cashTendered"
                                        :required="selectedPaymentMethod === 'cash'"
                                        class="gz-input pl-8 font-mono font-bold text-base"
                                        placeholder="0.00"
                                        autocomplete="off"
                                    >
                                </div>

                                {{-- Denomination shortcut buttons --}}
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[10px] uppercase font-bold" style="color: var(--gz-muted);">Presets:</span>
                                    <button type="button" @click="setExactCash()" class="px-2 py-0.5 text-xs font-semibold rounded border bg-white hover:bg-black/5" style="border-color: var(--gz-border);">
                                        Exact (₱<span x-text="courtRate.toFixed(2)"></span>)
                                    </button>
                                    <button type="button" @click="setCashAmount(500)" class="px-2 py-0.5 text-xs font-semibold rounded border bg-white hover:bg-black/5" style="border-color: var(--gz-border);">
                                        ₱500
                                    </button>
                                    <button type="button" @click="setCashAmount(1000)" class="px-2 py-0.5 text-xs font-semibold rounded border bg-white hover:bg-black/5" style="border-color: var(--gz-border);">
                                        ₱1,000
                                    </button>
                                    <button type="button" @click="setCashAmount(1500)" class="px-2 py-0.5 text-xs font-semibold rounded border bg-white hover:bg-black/5" style="border-color: var(--gz-border);">
                                        ₱1,500
                                    </button>
                                    <button type="button" @click="setCashAmount(2000)" class="px-2 py-0.5 text-xs font-semibold rounded border bg-white hover:bg-black/5" style="border-color: var(--gz-border);">
                                        ₱2,000
                                    </button>
                                </div>

                                {{-- Real-time Change / Shortage Output --}}
                                <div class="p-2.5 rounded border text-xs"
                                     :class="{
                                        'bg-emerald-50 border-emerald-300 text-emerald-900': isCashValid && cashTendered !== '',
                                        'bg-red-50 border-red-300 text-red-800': !isCashValid && cashTendered !== '',
                                        'bg-white border-dashed text-[color:var(--gz-muted)]': cashTendered === ''
                                     }"
                                     style="border-color: var(--gz-border);">
                                    <template x-if="cashTendered === ''">
                                        <div class="flex items-center justify-between">
                                            <span>Change to return:</span>
                                            <span class="font-mono font-bold">₱0.00</span>
                                        </div>
                                    </template>
                                    <template x-if="isCashValid && cashTendered !== ''">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold flex items-center gap-1.5">
                                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-600"></span>
                                                Customer Change:
                                            </span>
                                            <span class="font-mono font-extrabold text-base text-emerald-800" x-text="'₱' + changeDue.toFixed(2)"></span>
                                        </div>
                                    </template>
                                    <template x-if="!isCashValid && cashTendered !== ''">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold flex items-center gap-1.5">
                                                <span class="inline-block w-2 h-2 rounded-full bg-red-600"></span>
                                                Insufficient cash:
                                            </span>
                                            <span class="font-mono font-bold text-red-700" x-text="'Short ₱' + cashShortage.toFixed(2)"></span>
                                        </div>
                                    </template>
                                </div>

                                @error('cash_tendered')
                                    <p class="gz-error text-xs" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Arrival / Attendance Status --}}
                    <div class="mb-6">
                        <label class="gz-label">Arrival Attendance Status</label>
                        <div class="space-y-2 mt-1">
                            <label class="flex items-start gap-2 p-2 border cursor-pointer text-xs" style="border-color: var(--gz-border);">
                                <input type="radio" name="attendance_status" value="show" x-model="attendanceStatus" class="mt-0.5">
                                <div>
                                    <span class="font-bold" style="color: var(--gz-pop-dark);">SHOW (Present Now)</span>
                                    <p class="text-[10px]" style="color: var(--gz-muted);">Customer is here at the counter, ready to play immediately.</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 p-2 border cursor-pointer text-xs" style="border-color: var(--gz-border);">
                                <input type="radio" name="attendance_status" value="confirmed" x-model="attendanceStatus" class="mt-0.5">
                                <div>
                                    <span class="font-bold text-[color:var(--gz-ink)]">SCHEDULED (Later)</span>
                                    <p class="text-[10px]" style="color: var(--gz-muted);">Reserved ahead of time; awaiting arrival later today.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <button
                        type="submit"
                        :disabled="!selectedSlot || (selectedPaymentMethod === 'cash' && !isCashValid)"
                        class="gz-btn-primary w-full py-3 text-sm justify-center flex items-center gap-2"
                        :class="{'opacity-50 cursor-not-allowed': !selectedSlot || (selectedPaymentMethod === 'cash' && !isCashValid)}"
                    >
                        <template x-if="selectedPaymentMethod === 'paymongo'">
                            <span>Pay Online via PayMongo (<span x-text="'₱' + courtRate.toFixed(2)"></span>) →</span>
                        </template>
                        <template x-if="selectedPaymentMethod !== 'paymongo'">
                            <span>Confirm Walk-In Reservation (<span x-text="'₱' + courtRate.toFixed(2)"></span>)</span>
                        </template>
                    </button>
                    <p x-show="!selectedSlot" class="text-xs text-center mt-2 text-red-600 font-semibold">
                        Please choose an open time slot above.
                    </p>
                    <p x-show="selectedSlot && selectedPaymentMethod === 'cash' && !isCashValid" class="text-xs text-center mt-2 text-red-600 font-semibold">
                        Please input customer cash tendered (minimum ₱<span x-text="courtRate.toFixed(2)"></span>).
                    </p>
                </div>

                {{-- Helpful Counter Instructions Card --}}
                <div class="gz-panel gz-panel-body text-xs space-y-2" style="background: var(--gz-bg); color: var(--gz-muted);">
                    <div class="font-bold text-xs flex items-center gap-1.5" style="color: var(--gz-ink);">
                        Front Desk Instructions
                    </div>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Walk-ins are booked as 1-hour sessions at standard court pricing.</li>
                        <li>For <strong>Pay Online (PayMongo)</strong>, completing checkout automatically confirms and marks payment as paid.</li>
                        <li>Cash and manual counter payments are recorded as <strong>PAID</strong> immediately upon submission.</li>
                        <li>The reservation will appear instantly on the daily run-sheet under today's schedule.</li>
                    </ul>
                </div>
            </div>
        </form>

        {{-- Modal: Booked Slot Customer & Payment Details Inspector --}}
        <div
            x-show="activeBooking !== null"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(18, 21, 15, 0.65); backdrop-filter: blur(2px);"
            @click.self="closeInspector()"
            role="dialog"
            aria-modal="true"
        >
            <div class="gz-panel w-full max-w-lg rounded-2xl border-2 overflow-hidden shadow-2xl" style="background: var(--gz-surface); border-color: var(--gz-ink);">
                <div class="p-4 sm:p-5 border-b flex items-center justify-between gap-3" style="background: var(--gz-bg); border-color: var(--gz-border);">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 text-[10px] font-mono font-bold uppercase rounded bg-[#E5A823] text-[#12150F]">
                                Booked Appointment
                            </span>
                            <span class="font-mono text-xs font-bold" style="color: var(--gz-muted);" x-text="'#' + (activeBooking?.booking_ref || '')"></span>
                        </div>
                        <h3 class="gz-font-display font-bold text-lg mt-1" style="color: var(--gz-ink);" x-text="(activeBooking?.court_name || '') + ' · ' + (activeBooking?.time_slot || '')"></h3>
                        <p class="text-xs" style="color: var(--gz-muted);" x-text="(activeBooking?.date_formatted || '') + ' · ' + (activeBooking?.court_size || '')"></p>
                    </div>
                    <button type="button" @click="closeInspector()" class="gz-btn-outline gz-btn-sm px-2.5 py-1 text-xs font-bold">✕</button>
                </div>

                <div class="p-4 sm:p-5 space-y-4 text-xs">
                    <div class="p-3.5 rounded-xl border" style="background: var(--gz-bg); border-color: var(--gz-border);">
                        <div class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color: var(--gz-pop-dark);">
                            Booked By (Customer / Player)
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <span class="block text-[10px] uppercase" style="color: var(--gz-muted);">Player Name</span>
                                <span class="font-bold text-sm block mt-0.5" style="color: var(--gz-ink);" x-text="activeBooking?.customer_name"></span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase" style="color: var(--gz-muted);">Email Address</span>
                                <span class="font-semibold block mt-0.5 break-all" style="color: var(--gz-ink);" x-text="activeBooking?.customer_email"></span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase" style="color: var(--gz-muted);">Reservation Channel</span>
                                <span class="font-semibold block mt-0.5" style="color: var(--gz-ink);" x-text="activeBooking?.booking_type"></span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase" style="color: var(--gz-muted);">Attendance Status</span>
                                <span class="font-bold block mt-0.5" style="color: var(--gz-ink);" x-text="activeBooking?.attendance_label"></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl border" style="background: var(--gz-bg); border-color: var(--gz-border);">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--gz-pop-dark);">
                                Payment &amp; Transaction Summary
                            </span>
                            <span
                                class="gz-badge text-[10px] font-bold uppercase"
                                :class="activeBooking?.payment_status === 'PAID' ? 'gz-badge-success' : 'gz-badge-warning'"
                                x-text="activeBooking?.payment_status"
                            ></span>
                        </div>

                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between">
                                <span style="color: var(--gz-muted);">Amount Paid / Total Due:</span>
                                <span class="font-mono font-extrabold text-base" style="color: var(--gz-ink);" x-text="'₱' + Number(activeBooking?.payment_amount || 0).toFixed(2)"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span style="color: var(--gz-muted);">Payment Method:</span>
                                <span class="font-semibold" style="color: var(--gz-ink);" x-text="activeBooking?.payment_method"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span style="color: var(--gz-muted);">Payment Reference:</span>
                                <span class="font-mono font-semibold" style="color: var(--gz-ink);" x-text="activeBooking?.payment_ref"></span>
                            </div>

                            <template x-if="activeBooking?.cash_tendered !== null && activeBooking?.cash_tendered !== undefined">
                                <div class="pt-2 mt-2 border-t space-y-1" style="border-color: var(--gz-border);">
                                    <div class="flex items-center justify-between">
                                        <span style="color: var(--gz-muted);">Cash Tendered:</span>
                                        <span class="font-mono font-bold" style="color: var(--gz-ink);" x-text="'₱' + Number(activeBooking?.cash_tendered || 0).toFixed(2)"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span style="color: var(--gz-muted);">Change Returned:</span>
                                        <span class="font-mono font-bold text-emerald-700" x-text="'₱' + Number(activeBooking?.change_amount || 0).toFixed(2)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t flex items-center justify-between gap-2" style="background: var(--gz-bg); border-color: var(--gz-border);">
                    <button type="button" @click="closeInspector()" class="gz-btn-outline gz-btn-sm text-xs py-1.5 px-3">
                        Close
                    </button>
                    <a
                        :href="activeBooking?.receipt_print_url || '#'"
                        target="_blank"
                        class="gz-btn-primary gz-btn-sm text-xs py-1.5 px-3 inline-flex items-center gap-1.5"
                    >
                        <span>Print Receipt</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-staff-layout>
