<x-staff-layout>
    <x-slot name="heading">Today's Customer Attendance</x-slot>

    {{-- Header & Operational Banner --}}
    <div class="gz-panel gz-panel-body mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-block w-2 h-2" style="background: var(--gz-pop);"></span>
                <span class="gz-eyebrow" style="color: var(--gz-pop-dark);">Front desk · Attendance tracker</span>
            </div>
            <h1 class="gz-font-display font-bold text-xl">
                {{ $selectedDate->isToday() ? 'Customers scheduled for today' : 'Customers scheduled for ' . $selectedDate->format('M j, Y') }}
            </h1>
            <p class="text-sm mt-1" style="color: var(--gz-muted);">
                Track player arrival and attendance: Show or No-Show for {{ $selectedDate->format('l, F j, Y') }}.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 print:hidden">
            <a href="{{ route('staff.walkin.create') }}" class="gz-btn-primary gz-btn-sm">
                + New Walk-In
            </a>
            <button onclick="window.print()" class="gz-btn-outline gz-btn-sm">
                Print run-sheet
            </button>
            <a href="{{ route('staff.today') }}" class="gz-btn-outline gz-btn-sm">
                ↺ Refresh
            </a>
        </div>
    </div>

    {{-- Feedback Alerts --}}
    @if(session('status'))
        <div class="gz-status mb-6" role="status" aria-live="polite">
            {{ session('status') }}
        </div>
    @endif

    {{-- Recent Transaction / Receipt Prompt Card --}}
    @if(session('print_receipt_booking_id'))
        <div class="gz-panel mb-6 border-2 border-[#12150F] shadow-[4px_4px_0_#12150F] print:hidden" style="background: var(--gz-surface);">
            <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-block px-2.5 py-0.5 bg-[#12150F] text-[#E5A823] text-[10px] font-mono font-black uppercase tracking-wider rounded">
                            Transaction Completed
                        </span>
                        <span class="font-mono font-bold text-xs" style="color: var(--gz-ink);">
                            REF: #{{ session('print_receipt_ref') }}
                        </span>
                    </div>
                    <h2 class="gz-font-display font-bold text-lg" style="color: var(--gz-ink);">
                        Walk-in Confirmed: {{ session('receipt_customer_name') }}
                    </h2>
                    <div class="flex items-center gap-3 text-xs flex-wrap" style="color: var(--gz-muted);">
                        @if(session('receipt_court_name'))
                            <span>Court: <strong style="color: var(--gz-ink);">{{ session('receipt_court_name') }}</strong></span>
                        @endif
                        @if(session('receipt_time_slot'))
                            <span>Slot: <strong style="color: var(--gz-ink);">{{ session('receipt_time_slot') }}</strong></span>
                        @endif
                        @if(session('receipt_amount') !== null)
                            <span>Total: <strong class="font-mono" style="color: var(--gz-pop-dark);">₱{{ number_format(session('receipt_amount'), 2) }}</strong></span>
                        @endif
                        @if(session('receipt_staff_name'))
                            <span>Handled By: <strong style="color: var(--gz-ink);">{{ session('receipt_staff_name') }}</strong></span>
                        @endif
                        @if(session('receipt_cash_tendered') !== null)
                            <span>Tendered: <strong class="font-mono" style="color: var(--gz-ink);">₱{{ number_format(session('receipt_cash_tendered'), 2) }}</strong></span>
                            <span class="px-2 py-0.5 rounded font-bold bg-emerald-100 text-emerald-800">
                                Change: ₱{{ number_format(session('receipt_change_amount') ?? 0, 2) }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                    <a
                        href="{{ route('bookings.receipt', ['booking' => session('print_receipt_booking_id'), 'autoprint' => 1]) }}"
                        target="_blank"
                        class="gz-btn-primary gz-btn-sm inline-flex items-center gap-2 font-bold py-2 px-3 text-xs"
                        title="Open receipt in printable window"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        <span>Print Official Receipt</span>
                    </a>

                    <button
                        type="button"
                        @click="$dispatch('open-booking-receipt', {{ session('print_receipt_booking_id') }})"
                        class="gz-btn-outline gz-btn-sm inline-flex items-center gap-1.5 py-2 px-3 text-xs"
                    >
                        <span>View Receipt Modal</span>
                    </button>

                    <a href="{{ route('staff.walkin.create') }}" class="gz-btn-outline gz-btn-sm py-2 px-3 text-xs">
                        + Next Walk-In
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- 4 Attendance KPI Counter Ledgers --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 print:hidden">
        {{-- Total Scheduled --}}
        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <div class="icon-badge" style="width:32px; height:32px; padding:6px;" id="icon-scheduled" aria-hidden="true"></div>
                <span class="gz-badge gz-badge-neutral">Today</span>
            </div>
            <div class="gz-eyebrow mb-1">Scheduled</div>
            <div class="gz-kpi-value text-right" style="font-size: 22px;">
                {{ $totalBookingsToday }}
            </div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">Confirmed match slots</p>
        </div>

        {{-- Show Count --}}
        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <div class="icon-badge" style="width:32px; height:32px; padding:6px;" id="icon-show" aria-hidden="true"></div>
                <span class="gz-badge gz-badge-success">Present</span>
            </div>
            <div class="gz-eyebrow mb-1">Show</div>
            <div class="gz-kpi-value text-right" style="font-size: 22px; color: var(--gz-pop-dark);">
                {{ $showCount }}
            </div>
            <p class="text-xs mt-2 font-semibold" style="color: var(--gz-pop-dark);">Arrived &amp; played</p>
        </div>

        {{-- No-Show Count --}}
        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <div class="icon-badge" style="width:32px; height:32px; padding:6px;" id="icon-noshow" aria-hidden="true"></div>
                <span class="gz-badge {{ $noShowCount > 0 ? 'gz-badge-danger' : 'gz-badge-neutral' }}">Absent</span>
            </div>
            <div class="gz-eyebrow mb-1">No-show</div>
            <div class="gz-kpi-value text-right" style="font-size: 22px; color: {{ $noShowCount > 0 ? 'var(--gz-danger)' : 'var(--gz-ink)' }};">
                {{ $noShowCount }}
            </div>
            <p class="text-xs mt-2" style="color: {{ $noShowCount > 0 ? 'var(--gz-danger)' : 'var(--gz-muted)' }};">
                {{ $noShowCount > 0 ? 'Missed reservation' : 'No missed matches' }}
            </p>
        </div>

        {{-- Awaiting Arrival --}}
        <div class="gz-kpi-card">
            <div class="flex items-center justify-between mb-3">
                <div class="icon-badge" style="width:32px; height:32px; padding:6px;" id="icon-awaiting" aria-hidden="true"></div>
                <span class="gz-badge gz-badge-warning">Arrival</span>
            </div>
            <div class="gz-eyebrow mb-1">Awaiting</div>
            <div class="gz-kpi-value text-right" style="font-size: 22px;">
                {{ $awaitingCount }}
            </div>
            <p class="text-xs mt-2" style="color: var(--gz-muted);">Scheduled for today</p>
        </div>
    </div>

    {{-- Search and Filter Controls --}}
    <div class="gz-panel gz-panel-body mb-6 print:hidden">
        <form method="GET" action="{{ route('staff.today') }}" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            {{-- Search Input --}}
            <div class="flex-1 flex gap-2">
                <input type="text"
                       name="search"
                       value="{{ $searchTerm }}"
                       placeholder="Search customer name, email, or court..."
                       class="gz-input">
                <button type="submit" class="gz-btn-primary gz-btn-sm whitespace-nowrap">
                    Search
                </button>
                @if($searchTerm || $selectedCourt || $selectedStatus)
                    <a href="{{ route('staff.today') }}" class="gz-btn-outline gz-btn-sm whitespace-nowrap">
                        Clear
                    </a>
                @endif
            </div>

            {{-- Court, Date & Attendance Quick Filters --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Date Filter --}}
                <div class="flex items-center gap-1 bg-[color:var(--gz-bg)] px-2 py-1 border border-[color:var(--gz-border)]">
                    <span class="text-xs font-semibold" style="color: var(--gz-muted);">Date:</span>
                    <input type="date" name="date" value="{{ $selectedDate->toDateString() }}" onchange="this.form.submit()" class="text-xs bg-transparent border-0 p-0 focus:ring-0" style="color: var(--gz-ink);">
                </div>

                {{-- Court Filter --}}
                <select name="court_id" onchange="this.form.submit()" class="gz-input w-auto">
                    <option value="">All courts</option>
                    @foreach($courts as $court)
                        <option value="{{ $court->id }}" @selected($selectedCourt == $court->id)>
                            {{ $court->court_name }}
                        </option>
                    @endforeach
                </select>

                {{-- Attendance Status Filter --}}
                <select name="status" onchange="this.form.submit()" class="gz-input w-auto">
                    <option value="">All attendance</option>
                    <option value="show" @selected($selectedStatus === 'show')>Show (Present)</option>
                    <option value="no_show" @selected($selectedStatus === 'no_show')>No-Show (Absent)</option>
                    <option value="scheduled" @selected($selectedStatus === 'scheduled')>Awaiting Arrival</option>
                </select>
            </div>
        </form>
    </div>

    {{-- Interactive Court Schedule Matrix & Click-to-Inspect Modal --}}
    <div
        x-data="{
            bookedSlotDetails: @js($bookedSlotDetails ?? []),
            specialSlotDetails: @js($specialSlotDetails ?? []),
            activeBooking: null,
            activeSpecial: null,
            openBookingInspector(courtId, slot) {
                const detail = (this.bookedSlotDetails[courtId] || {})[slot] || null;
                if (detail) {
                    this.activeSpecial = null;
                    this.activeBooking = detail;
                }
            },
            openSpecialInspector(courtId, slot) {
                const special = (this.specialSlotDetails[courtId] || {})[slot] || null;
                if (special) {
                    this.activeBooking = null;
                    this.activeSpecial = special;
                }
            },
            closeInspector() {
                this.activeBooking = null;
                this.activeSpecial = null;
            }
        }"
        @keydown.escape.window="closeInspector()"
        class="gz-panel mb-6 print:hidden overflow-hidden"
    >
        <div class="gz-panel-header">
            <div>
                <h2 class="gz-font-display font-bold text-base">
                    Court Schedule Matrix — {{ $selectedDate->format('F j, Y') }}
                </h2>
                <p class="text-xs mt-0.5" style="color: var(--gz-muted);">
                    Booked slots display as <strong>Booked</strong>. Click any <strong>Booked</strong> slot to view who booked that court time, how much they paid, payment method, and attendance.
                </p>
            </div>
            <span class="gz-badge gz-badge-neutral">
                {{ $courts->count() }} Courts · {{ count($timeSlots ?? []) }} Time Slots
            </span>
        </div>

        <div style="max-height: 390px; overflow-y: auto; overflow-x: auto;">
            <table class="gz-table" style="font-size: 12px;">
                <thead style="position: sticky; top: 0; z-index: 2; background: var(--gz-surface);">
                    <tr>
                        <th class="text-left" style="padding: 10px 14px; min-width: 145px;">TIME</th>
                        @foreach($courts as $court)
                            <th class="text-left" style="padding: 10px 12px; min-width: 165px;">
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
                    @foreach($timeSlots ?? [] as $slot)
                        <tr>
                            <th scope="row" class="text-left font-bold whitespace-nowrap" style="padding: 7px 14px; color: var(--gz-muted);">
                                {{ $slot }}
                            </th>
                            @foreach($courts as $court)
                                @php
                                    $slotBooking = $bookedSlotDetails[$court->id][$slot] ?? null;
                                    $slotSpecial = $specialSlotDetails[$court->id][$slot] ?? null;
                                @endphp
                                <td style="padding: 5px 8px;">
                                    @if($slotBooking)
                                        <button
                                            type="button"
                                            @click="openBookingInspector({{ $court->id }}, @js($slot))"
                                            class="w-full text-xs font-bold rounded-lg transition flex items-center justify-center gap-1.5 border cursor-pointer hover:opacity-90"
                                            style="height: 34px; background: var(--gz-pop); color: #12150F; border-color: var(--gz-pop-dark); box-shadow: 0 1px 2px rgba(18,21,15,0.12);"
                                            title="Click to inspect who booked {{ $court->court_name }} ({{ $slot }}) and payment details"
                                        >
                                            <span>Booked</span>
                                        </button>
                                    @elseif($slotSpecial)
                                        <button
                                            type="button"
                                            @click="openSpecialInspector({{ $court->id }}, @js($slot))"
                                            class="w-full text-xs font-bold rounded-lg transition flex items-center justify-center gap-1 border cursor-pointer hover:opacity-90"
                                            style="height: 34px; {{ $slotSpecial['type'] === 'tournament' ? 'background: rgba(245, 158, 11, 0.22); border-color: rgba(245, 158, 11, 0.65); color: #B45309;' : 'background: rgba(229, 168, 35, 0.2); border-color: rgba(229, 168, 35, 0.6); color: #A67512;' }}"
                                            title="Click to inspect {{ $slotSpecial['type_label'] }} session details"
                                        >
                                            <span>{{ $slotSpecial['type_label'] }}</span>
                                        </button>
                                    @else
                                        <a
                                            href="{{ route('staff.walkin.create', ['date' => $selectedDate->toDateString(), 'court_id' => $court->id, 'time_slot' => $slot]) }}"
                                            class="w-full text-xs font-semibold rounded-lg transition flex items-center justify-center border hover:border-[#12150F]"
                                            style="height: 34px; background: var(--gz-surface); color: var(--gz-ink); border-color: var(--gz-border);"
                                            title="Open slot — click to book a walk-in for {{ $court->court_name }} at {{ $slot }}"
                                        >
                                            Open
                                        </a>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Schedule Matrix Legend --}}
        <div class="p-3 border-t flex flex-wrap items-center justify-between gap-3 text-[11px]" style="border-color: var(--gz-border); background: var(--gz-bg);">
            <div class="flex items-center gap-4 flex-wrap">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded border" style="background: var(--gz-surface); border-color: var(--gz-border);"></span>
                    <span style="color: var(--gz-muted);">Open (Click to book walk-in)</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded border" style="background: var(--gz-pop); border-color: var(--gz-pop-dark);"></span>
                    <span class="font-semibold" style="color: var(--gz-ink);">Booked (Click to view customer &amp; payment details)</span>
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
            <span class="text-[11px]" style="color: var(--gz-muted);">
                Click any <strong>Booked</strong> cell to inspect customer &amp; payment details
            </span>
        </div>

        {{-- Modal: Booked Slot Customer & Payment Details Inspector --}}
        <div
            x-show="activeBooking !== null"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(18, 21, 15, 0.65); backdrop-filter: blur(2px);"
            @click.self="closeInspector()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="slot-inspector-title"
        >
            <div class="gz-panel w-full max-w-lg rounded-2xl border-2 overflow-hidden shadow-2xl" style="background: var(--gz-surface); border-color: var(--gz-ink);">
                {{-- Modal Header --}}
                <div class="p-4 sm:p-5 border-b flex items-center justify-between gap-3" style="background: var(--gz-bg); border-color: var(--gz-border);">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 text-[10px] font-mono font-bold uppercase rounded bg-[#E5A823] text-[#12150F]">
                                Booked Reservation
                            </span>
                            <span class="font-mono text-xs font-bold" style="color: var(--gz-muted);" x-text="'#' + (activeBooking?.booking_ref || '')"></span>
                        </div>
                        <h3 id="slot-inspector-title" class="gz-font-display font-bold text-lg mt-1" style="color: var(--gz-ink);" x-text="(activeBooking?.court_name || '') + ' · ' + (activeBooking?.time_slot || '')"></h3>
                        <p class="text-xs" style="color: var(--gz-muted);" x-text="(activeBooking?.date_formatted || '') + ' · ' + (activeBooking?.court_size || '')"></p>
                    </div>
                    <button
                        type="button"
                        @click="closeInspector()"
                        class="gz-btn-outline gz-btn-sm px-2.5 py-1 text-xs font-bold"
                        aria-label="Close booking details modal"
                    >
                        ✕
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 sm:p-5 space-y-4 text-xs">
                    {{-- Who Booked Section --}}
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
                                <template x-if="activeBooking?.status_updated_by_name">
                                    <span class="block text-[10px] mt-0.5 font-semibold text-emerald-800" x-text="'Marked by: ' + activeBooking?.status_updated_by_name + (activeBooking?.status_updated_at ? (' (' + activeBooking?.status_updated_at + ')') : '')"></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Payment & Financial Breakdown Section --}}
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
                            <template x-if="activeBooking?.processed_by_name">
                                <div class="flex items-center justify-between">
                                    <span style="color: var(--gz-muted);">Payment Processed By:</span>
                                    <span class="font-semibold" style="color: var(--gz-ink);" x-text="activeBooking?.processed_by_name"></span>
                                </div>
                            </template>
                            <div class="flex items-center justify-between">
                                <span style="color: var(--gz-muted);">Payment Reference:</span>
                                <span class="font-mono font-semibold" style="color: var(--gz-ink);" x-text="activeBooking?.payment_ref"></span>
                            </div>
                            <template x-if="activeBooking?.paymongo_payment_id">
                                <div class="flex items-center justify-between">
                                    <span style="color: var(--gz-muted);">PayMongo Payment ID:</span>
                                    <span class="font-mono font-semibold" style="color: var(--gz-ink);" x-text="activeBooking?.paymongo_payment_id"></span>
                                </div>
                            </template>

                            <template x-if="activeBooking?.cash_tendered !== null && activeBooking?.cash_tendered !== undefined">
                                <div class="pt-2 mt-2 border-t space-y-1" style="border-color: var(--gz-border);">
                                    <div class="flex items-center justify-between">
                                        <span style="color: var(--gz-muted);">Cash Tendered by Customer:</span>
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

                {{-- Modal Footer Actions --}}
                <div class="p-4 border-t flex flex-wrap items-center justify-between gap-2" style="background: var(--gz-bg); border-color: var(--gz-border);">
                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            type="button"
                            @click="const id = activeBooking?.booking_id; closeInspector(); if (id) { $dispatch('open-booking-receipt', id); }"
                            class="gz-btn-outline gz-btn-sm text-xs py-1.5 px-3"
                        >
                            View Receipt
                        </button>
                        <a
                            :href="activeBooking?.receipt_print_url || '#'"
                            target="_blank"
                            class="gz-btn-primary gz-btn-sm text-xs py-1.5 px-3 inline-flex items-center gap-1.5"
                        >
                            <span>Print Receipt</span>
                        </a>
                    </div>

                    <template x-if="activeBooking?.is_today">
                        <div class="flex items-center gap-1.5">
                            <form method="POST" :action="activeBooking?.status_update_url || '#'" class="inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="attendance_status" value="show">
                                <button type="submit" class="gz-btn-success gz-btn-sm text-xs py-1.5 px-2.5">
                                    Mark Show
                                </button>
                            </form>
                            <form method="POST" :action="activeBooking?.status_update_url || '#'" class="inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="attendance_status" value="no_show">
                                <button type="submit" class="gz-btn-danger gz-btn-sm text-xs py-1.5 px-2.5">
                                    Mark No-Show
                                </button>
                            </form>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Modal: Open Play / Tournament Slot Details Inspector --}}
        <div
            x-show="activeSpecial !== null"
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
                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold uppercase rounded bg-[#E5A823] text-[#12150F]" x-text="activeSpecial?.type_label"></span>
                        <h3 class="gz-font-display font-bold text-lg mt-1" style="color: var(--gz-ink);" x-text="activeSpecial?.title"></h3>
                        <p class="text-xs" style="color: var(--gz-muted);" x-text="(activeSpecial?.court_name || '') + ' · ' + (activeSpecial?.time_window || '') + ' · ' + (activeSpecial?.skill_level || '')"></p>
                    </div>
                    <button type="button" @click="closeInspector()" class="gz-btn-outline gz-btn-sm px-2.5 py-1 text-xs font-bold">✕</button>
                </div>
                <div class="p-4 sm:p-5 space-y-4 text-xs">
                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="p-2.5 rounded-lg border" style="background: var(--gz-bg); border-color: var(--gz-border);">
                            <span class="block text-[10px] uppercase" style="color: var(--gz-muted);">Slots Booked</span>
                            <span class="font-bold text-sm" x-text="(activeSpecial?.registered_slots || 0) + ' / ' + (activeSpecial?.max_capacity || 0)"></span>
                        </div>
                        <div class="p-2.5 rounded-lg border" style="background: var(--gz-bg); border-color: var(--gz-border);">
                            <span class="block text-[10px] uppercase" style="color: var(--gz-muted);">Fee / Slot</span>
                            <span class="font-mono font-bold text-sm" x-text="'₱' + Number(activeSpecial?.price_per_slot || 0).toFixed(2)"></span>
                        </div>
                        <div class="p-2.5 rounded-lg border" style="background: var(--gz-bg); border-color: var(--gz-border);">
                            <span class="block text-[10px] uppercase" style="color: var(--gz-muted);">Total Fees</span>
                            <span class="font-mono font-bold text-sm" x-text="'₱' + Number(activeSpecial?.total_revenue || 0).toFixed(2)"></span>
                        </div>
                    </div>

                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider mb-1.5" style="color: var(--gz-pop-dark);">
                            Registered Players (<span x-text="(activeSpecial?.players || []).length"></span>)
                        </div>
                        <div class="max-h-44 overflow-y-auto divide-y border rounded-xl" style="border-color: var(--gz-border); background: var(--gz-bg);">
                            <template x-for="(p, idx) in (activeSpecial?.players || [])" :key="idx">
                                <div class="p-2.5 flex items-center justify-between gap-2">
                                    <div>
                                        <div class="font-bold" x-text="p.name"></div>
                                        <div class="text-[11px]" style="color: var(--gz-muted);" x-text="p.email + ' · ' + p.slots_count + ' slot(s)'"></div>
                                        <div class="font-mono text-[10px] mt-0.5" style="color: var(--gz-muted);">
                                            <span x-text="'Ref: ' + (p.ref_num || 'N/A')"></span>
                                            <template x-if="p.paymongo_payment_id">
                                                <span x-text="' · PayMongo: ' + p.paymongo_payment_id"></span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-mono font-bold" x-text="'₱' + Number(p.total_fee || 0).toFixed(2)"></div>
                                        <div class="text-[10px]" style="color: var(--gz-muted);" x-text="p.payment_status + ' (' + p.payment_method + ')'"></div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!(activeSpecial?.players || []).length">
                                <div class="p-3 text-center italic" style="color: var(--gz-muted);">No players registered yet.</div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Customers Table --}}
    <div class="gz-panel">
        <div class="gz-panel-header">
            <div>
                <h2 class="gz-font-display font-bold text-base">{{ $selectedDate->isToday() ? "Today's customer run-sheet" : "Customer run-sheet for " . $selectedDate->format('M j, Y') }}</h2>
                <p class="text-xs" style="color: var(--gz-muted);">
                    Showing {{ $bookings->count() }} customer reservation{{ $bookings->count() === 1 ? '' : 's' }} for {{ $selectedDate->format('D, M d, Y') }}
                </p>
            </div>
            <span class="gz-badge gz-badge-neutral">
                {{ $bookings->count() }} match{{ $bookings->count() === 1 ? '' : 'es' }}
            </span>
        </div>

        <div class="gz-panel-body overflow-x-auto">
            @if($bookings->isEmpty())
                <div class="p-8 text-center border border-dashed" style="border-color: var(--gz-border); background: var(--gz-surface);">
                    <p class="font-semibold">No customers found for today</p>
                    <p class="text-sm mt-1" style="color: var(--gz-muted);">
                        @if($searchTerm || $selectedCourt || $selectedStatus)
                            No scheduled customers match your filter parameters. Try clearing the filter.
                        @else
                            No player bookings have been scheduled for today.
                        @endif
                    </p>
                    @if($searchTerm || $selectedCourt || $selectedStatus)
                        <div class="mt-4">
                            <a href="{{ route('staff.today') }}" class="gz-btn-outline gz-btn-sm">
                                Reset filters
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <table class="gz-table">
                    <thead>
                        <tr>
                            <th>Time / Timing</th>
                            <th>Customer / Player</th>
                            <th>Court Assigned</th>
                            <th>Attendance</th>
                            <th class="text-right print:hidden">Staff Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                            @php
                                $startTime = \Carbon\Carbon::parse($booking->date . ' ' . $booking->start_time);
                                $endTime = \Carbon\Carbon::parse($booking->date . ' ' . $booking->end_time);
                                $isLiveNow = now()->between($startTime, $endTime);
                                $isPast = now()->gt($endTime);
                                $isNoShow = in_array($booking->booking_status, ['no_show', 'no-show']);
                                $isShow = $booking->booking_status === 'show';
                            @endphp
                            <tr>
                                {{-- Time and Live Indicator --}}
                                <td class="whitespace-nowrap">
                                    <div class="text-sm font-semibold">
                                        {{ $startTime->format('g:i A') }} - {{ $endTime->format('g:i A') }}
                                    </div>
                                    <div class="mt-1">
                                        @if($isLiveNow)
                                            <span class="gz-badge gz-badge-success">Live on court</span>
                                        @elseif($isPast)
                                            <span class="gz-badge gz-badge-neutral">Finished</span>
                                        @else
                                            <span class="gz-badge gz-badge-warning">Upcoming</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Customer Details --}}
                                <td>
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-8 h-8 flex items-center justify-center text-sm font-bold shrink-0" style="background: var(--gz-pop); color: var(--gz-ink);">
                                            {{ strtoupper(substr($booking->user?->first_name ?: $booking->user?->name ?: 'G', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-sm">
                                                {{ $booking->user?->name ?? 'Guest Customer' }}
                                            </div>
                                            <div class="text-xs" style="color: var(--gz-muted);">
                                                {{ $booking->user?->email ?? 'No email on file' }}
                                            </div>
                                            @if($booking->user?->role)
                                                <span class="gz-eyebrow inline-block mt-0.5">
                                                    {{ $booking->user->role }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Court --}}
                                <td>
                                    <div class="font-semibold text-sm">
                                        {{ $booking->court?->court_name ?? 'Court Unassigned' }}
                                    </div>
                                    <div class="text-xs" style="color: var(--gz-muted);">
                                        {{ $booking->court?->size ?: 'Regular (13.41m x 6.10m)' }}
                                    </div>
                                </td>

                                {{-- Attendance Status Badge --}}
                                <td>
                                    @if($isShow)
                                        <span class="gz-badge gz-badge-success">Show (Present)</span>
                                    @elseif($isNoShow)
                                        <span class="gz-badge gz-badge-danger">No-show (Absent)</span>
                                    @else
                                        <span class="gz-badge gz-badge-warning">Awaiting arrival</span>
                                    @endif

                                    @if($booking->statusUpdatedBy)
                                        <div class="text-[11px] mt-1 font-medium" style="color: var(--gz-muted);">
                                            Marked by: <span class="font-bold" style="color: var(--gz-ink);">{{ $booking->statusUpdatedBy->name }}</span>
                                            @if($booking->status_updated_at)
                                                <span class="text-[10px]">({{ $booking->status_updated_at->format('g:i A') }})</span>
                                            @endif
                                        </div>
                                    @endif

                                    @php
                                        $paymentStaff = $booking->payments->first(fn ($p) => $p->processor !== null)?->processor?->name
                                            ?? ($booking->isWalkIn() ? $booking->statusUpdatedBy?->name : null);
                                    @endphp
                                    @if($paymentStaff)
                                        <div class="text-[10px] mt-0.5 text-gray-700 font-medium">
                                            Handled by: <span class="font-bold text-gray-900">{{ $paymentStaff }}</span>
                                        </div>
                                    @endif
                                </td>

                                {{-- Staff Attendance Actions (Show / No Show & Receipt) --}}
                                <td class="text-right print:hidden">
                                    @php
                                        $isBookingToday = \Carbon\Carbon::parse($booking->date)->isSameDay(now());
                                    @endphp
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <button
                                            type="button"
                                            @click="$dispatch('open-booking-receipt', {{ $booking->id }})"
                                            class="gz-btn-outline gz-btn-sm inline-flex items-center gap-1 text-[11px] py-1 px-2"
                                            title="View Official Receipt Modal"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span>Receipt</span>
                                        </button>

                                        <a
                                            href="{{ route('bookings.receipt', ['booking' => $booking->id, 'autoprint' => 1]) }}"
                                            target="_blank"
                                            class="gz-btn-outline gz-btn-sm inline-flex items-center gap-1 text-[11px] py-1 px-2"
                                            title="Print Official Receipt"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                            </svg>
                                            <span>Print</span>
                                        </a>

                                        @if($isBookingToday && !$isShow)
                                            {{-- Mark as SHOW --}}
                                            <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="attendance_status" value="show">
                                                <button type="submit" class="gz-btn-success gz-btn-sm" title="Mark Customer as Present">
                                                    Show
                                                </button>
                                            </form>

                                            {{-- Mark as NO-SHOW --}}
                                            @if(!$isNoShow)
                                                <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="attendance_status" value="no_show">
                                                    <button type="submit" class="gz-btn-danger gz-btn-sm" title="Mark Customer as No-Show">
                                                        No-show
                                                    </button>
                                                </form>
                                            @endif
                                        @elseif(!$isBookingToday)
                                            <span class="text-xs italic" style="color: var(--gz-muted);" title="Attendance check-in is performed on match day">Check-in on match day</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Footer Run-sheet summary --}}
        <div class="p-4 border-t flex flex-col sm:flex-row items-center justify-between text-xs" style="border-color: var(--gz-border); color: var(--gz-muted);">
            <span>{{ $siteSettings->system_name ?? 'Gaoshou Pickleball' }} Front Desk</span>
            <span>Date: {{ $today->format('Y-m-d') }} · Attendance engine</span>
        </div>
    </div>

    {{-- Open Play & Tournament Sessions Section --}}
    @if(isset($openPlaySessions) && $openPlaySessions->isNotEmpty())
        <div class="gz-panel mt-6">
            <div class="gz-panel-header">
                <div>
                    <h2 class="gz-font-display font-bold text-base">
                        Open Play &amp; Tournament Attendees for {{ $selectedDate->format('M j, Y') }}
                    </h2>
                    <p class="text-xs" style="color: var(--gz-muted);">
                        Community sessions and player rosters scheduled on facility courts.
                    </p>
                </div>
                <span class="gz-badge gz-badge-warning">
                    {{ $openPlaySessions->count() }} Session{{ $openPlaySessions->count() === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="gz-panel-body space-y-6">
                @foreach($openPlaySessions as $session)
                    @php
                        $sessionStart = \Carbon\Carbon::parse($session->date->format('Y-m-d') . ' ' . $session->start_time);
                        $sessionEnd = \Carbon\Carbon::parse($session->date->format('Y-m-d') . ' ' . $session->end_time);
                        $isSessionLive = now()->between($sessionStart, $sessionEnd);
                        $registeredPlayers = $session->registrations->whereIn('payment_status', ['paid', 'pending']);
                        $totalSlotsFilled = $registeredPlayers->sum('slots_count');
                    @endphp
                    <div class="p-4 rounded-xl border" style="border-color: var(--gz-border); background: var(--gz-surface);">
                        {{-- Session Header --}}
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b" style="border-color: var(--gz-border);">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="gz-font-display font-bold text-base" style="color: var(--gz-ink);">
                                        {{ $session->title }}
                                    </span>
                                    <span class="gz-badge {{ $session->session_type === 'tournament' ? 'gz-badge-warning' : 'gz-badge-neutral' }}">
                                        {{ ucfirst(str_replace('_', ' ', $session->session_type)) }}
                                    </span>
                                    @if($isSessionLive)
                                        <span class="gz-badge gz-badge-success">Live on Court</span>
                                    @endif
                                </div>
                                <div class="text-xs mt-1 flex items-center gap-3 flex-wrap" style="color: var(--gz-muted);">
                                    <span><strong>Time:</strong> {{ $sessionStart->format('g:i A') }} – {{ $sessionEnd->format('g:i A') }}</span>
                                    <span><strong>Courts:</strong> {{ $session->courts->pluck('court_name')->join(', ') ?: 'No courts assigned' }}</span>
                                    <span><strong>Capacity:</strong> {{ $totalSlotsFilled }} / {{ $session->max_capacity }} slots</span>
                                    <span><strong>Fee:</strong> ₱{{ number_format($session->price_per_slot, 2) }}/slot</span>
                                </div>
                            </div>
                        </div>

                        {{-- Roster Table --}}
                        <div class="mt-3 overflow-x-auto">
                            @if($registeredPlayers->isEmpty())
                                <p class="text-xs italic py-2" style="color: var(--gz-muted);">
                                    No players registered yet for this session.
                                </p>
                            @else
                                <table class="gz-table text-xs">
                                    <thead>
                                        <tr>
                                            <th>Player Name</th>
                                            <th>Contact / Email</th>
                                            <th>Slots</th>
                                            <th>Payment</th>
                                            <th>Attendance</th>
                                            <th class="text-right print:hidden">Staff Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($registeredPlayers as $reg)
                                            @php
                                                $regShow = $reg->attendance_status === 'show';
                                                $regNoShow = $reg->attendance_status === 'no_show';
                                                $isDateToday = \Carbon\Carbon::parse($session->date)->isSameDay(now());
                                            @endphp
                                            <tr>
                                                <td class="font-semibold">{{ $reg->player_name }}</td>
                                                <td style="color: var(--gz-muted);">
                                                    {{ $reg->player_email }}
                                                    @if($reg->player_phone)
                                                        · {{ $reg->player_phone }}
                                                    @endif
                                                </td>
                                                <td>{{ $reg->slots_count }} slot{{ $reg->slots_count === 1 ? '' : 's' }}</td>
                                                <td>
                                                    <span class="gz-badge {{ $reg->payment_status === 'paid' ? 'gz-badge-success' : 'gz-badge-warning' }}">
                                                        {{ strtoupper($reg->payment_status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($regShow)
                                                        <span class="gz-badge gz-badge-success">Show (Present)</span>
                                                    @elseif($regNoShow)
                                                        <span class="gz-badge gz-badge-danger">No-Show</span>
                                                    @else
                                                        <span class="gz-badge gz-badge-warning">Registered</span>
                                                    @endif
                                                </td>
                                                <td class="text-right print:hidden">
                                                    @if($isDateToday)
                                                        <div class="flex items-center justify-end gap-1.5">
                                                            @if(!$regShow)
                                                                <form method="POST" action="{{ route('staff.open-play.attendance', $reg) }}" class="inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="attendance_status" value="show">
                                                                    <button type="submit" class="gz-btn-success gz-btn-sm py-0.5 px-2 text-[10px]" title="Mark Present">
                                                                        Show
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            @if(!$regNoShow)
                                                                <form method="POST" action="{{ route('staff.open-play.attendance', $reg) }}" class="inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="attendance_status" value="no_show">
                                                                    <button type="submit" class="gz-btn-danger gz-btn-sm py-0.5 px-2 text-[10px]" title="Mark Absent">
                                                                        No-show
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-[11px] italic" style="color: var(--gz-muted);">Match day check-in</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-staff-layout>