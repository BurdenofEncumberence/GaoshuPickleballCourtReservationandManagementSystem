<x-app-layout title="Host an Open Play Session — Gaoshou Pickleball">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6">
        {{-- Breadcrumb & Title --}}
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs mb-2" style="color: var(--gz-muted);">
                <a href="{{ route('open-play.index') }}" class="hover:underline">Open Play</a>
                <span>/</span>
                <span class="font-semibold" style="color: var(--gz-ink);">Host an Open Play</span>
            </div>
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="gz-font-display font-extrabold text-2xl sm:text-3xl tracking-tight" style="color: var(--gz-ink);">
                        Host an Open Play Session
                    </h1>
                    <p class="text-sm mt-1" style="color: var(--gz-muted);">
                        Submit your session details for manager approval. Once approved, you can pay the court fee to secure your appointment.
                    </p>
                </div>
                <a href="{{ route('open-play.index') }}" class="gz-btn-outline gz-btn-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back to Sessions</span>
                </a>
            </div>
        </div>

        {{-- How It Works Card --}}
        <div class="gz-panel p-4 mb-6" style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2);">
            <div class="flex items-start gap-3">
                <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-xs space-y-1" style="color: var(--gz-ink);">
                    <p class="font-bold text-sm">How Hosting Works</p>
                    <p style="color: var(--gz-muted);">
                        1. <strong>Submit Request:</strong> Select date, time, and courts. Only Open Play sessions may be hosted by players.
                    </p>
                    <p style="color: var(--gz-muted);">
                        2. <strong>Manager Review:</strong> A facility manager inspects court schedule and accepts or declines your request.
                    </p>
                    <p style="color: var(--gz-muted);">
                        3. <strong>Court Payment:</strong> Once accepted, you pay the court hire fee (online via PayMongo or cash at desk) to secure the appointment and publish it for players to join.
                    </p>
                </div>
            </div>
        </div>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl text-sm" style="background: var(--gz-danger-bg); color: var(--gz-danger);" role="alert">
                <p class="font-bold mb-1">Please correct the following errors:</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Hosting Form --}}
        <div class="gz-panel p-6">
            <form method="POST" action="{{ route('open-play.host.store') }}" id="hostForm">
                @csrf
                <input type="hidden" name="session_type" value="open_play">

                <div class="space-y-6">
                    {{-- Title --}}
                    <div>
                        <label for="title" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                            Session Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            value="{{ old('title', $session->title) }}"
                            placeholder="e.g. Saturday Morning Open Play (All Levels)"
                            required
                            class="gz-input w-full text-sm"
                        >
                        <p class="text-[11px] mt-1" style="color: var(--gz-muted);">
                            Give your communal play session a descriptive title.
                        </p>
                    </div>

                    {{-- Date, Start Time, End Time --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="date" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                                Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                id="date"
                                name="date"
                                min="{{ today()->toDateString() }}"
                                value="{{ old('date', $session->date ? $session->date->format('Y-m-d') : today()->addDay()->toDateString()) }}"
                                required
                                class="gz-input w-full text-sm"
                            >
                        </div>

                        <div>
                            <label for="start_time" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                                Start Time <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="time"
                                id="start_time"
                                name="start_time"
                                value="{{ old('start_time', $session->start_time ? substr($session->start_time, 0, 5) : '18:00') }}"
                                required
                                class="gz-input w-full text-sm"
                            >
                        </div>

                        <div>
                            <label for="end_time" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                                End Time <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="time"
                                id="end_time"
                                name="end_time"
                                value="{{ old('end_time', $session->end_time ? substr($session->end_time, 0, 5) : '21:00') }}"
                                required
                                class="gz-input w-full text-sm"
                            >
                        </div>
                    </div>

                    {{-- Live Court Schedule & Time Slot Availability Matrix --}}
                    <div class="p-4 rounded-xl border" style="border-color: var(--gz-border); background: var(--gz-surface);">
                        <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider" style="color: var(--gz-ink);">
                                    Court Schedule &amp; Time Slot Availability
                                </h3>
                                <p class="text-[11px] mt-0.5" style="color: var(--gz-muted);">
                                    Inspect existing private bookings, tournaments, and open play blocks on your chosen date to avoid conflicts.
                                </p>
                            </div>
                            <div class="flex items-center gap-3 text-[10px]" style="color: var(--gz-muted);">
                                <span class="flex items-center gap-1">
                                    <span class="w-2.5 h-2.5 rounded border" style="background: var(--gz-surface); border-color: var(--gz-border);"></span> Available
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="w-2.5 h-2.5 rounded border" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.5);"></span> Private Booking
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="w-2.5 h-2.5 rounded border" style="background: rgba(229, 168, 35, 0.2); border-color: rgba(229, 168, 35, 0.6);"></span> Open Play
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="w-2.5 h-2.5 rounded border" style="background: rgba(245, 158, 11, 0.2); border-color: rgba(245, 158, 11, 0.6);"></span> Tournament
                                </span>
                            </div>
                        </div>

                        {{-- Live Slot Matrix --}}
                        <div class="overflow-x-auto max-h-56 overflow-y-auto mt-2 border rounded-lg" style="border-color: var(--gz-border);">
                            <table class="gz-table text-xs w-full">
                                <thead style="position: sticky; top: 0; background: var(--gz-surface); z-index: 2;">
                                    <tr>
                                        <th class="py-2 px-3 text-left" style="min-width: 145px;">Time Slot</th>
                                        @foreach($courts as $court)
                                            <th class="py-2 px-2 text-center whitespace-nowrap" style="min-width: 130px;">
                                                {{ $court->court_name }}
                                                <span class="block text-[10px] font-normal" style="color: var(--gz-muted);">
                                                    ₱{{ number_format($court->price_per_hour, 2) }}/hr
                                                </span>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody id="slot-matrix-body">
                                    {{-- Dynamically populated via JS based on selected date --}}
                                </tbody>
                            </table>
                        </div>

                        {{-- Live Conflict Warning Box --}}
                        <div id="conflict-warning" class="hidden mt-3 p-3 rounded-lg text-xs" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #DC2626;">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div>
                                    <span class="font-bold">Time Slot Conflict Detected:</span>
                                    <span id="conflict-message">One or more selected courts are already booked during this time window. Please adjust your time or choose other courts.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Court Selection --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold uppercase tracking-wider" style="color: var(--gz-muted);">
                                Select Courts to Reserve <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[11px]" style="color: var(--gz-muted);">Hourly rates apply for appointment fee</span>
                        </div>
                        <p class="text-xs mb-3" style="color: var(--gz-muted);">
                            Choose one or more courts for your Open Play session. Court hire fees will be calculated based on duration and hourly rates.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($courts as $court)
                                @php
                                    $checked = in_array($court->id, old('allocated_courts', []));
                                @endphp
                                <label class="court-choice flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer hover:bg-black/5 dark:hover:bg-white/5 transition"
                                       id="court-card-{{ $court->id }}"
                                       style="border-color: var(--gz-border); background: var(--gz-surface);">
                                    <input
                                        type="checkbox"
                                        name="allocated_courts[]"
                                        value="{{ $court->id }}"
                                        data-court-id="{{ $court->id }}"
                                        data-court-name="{{ $court->court_name }}"
                                        data-rate="{{ $court->price_per_hour }}"
                                        {{ $checked ? 'checked' : '' }}
                                        class="court-checkbox mt-0.5 rounded text-emerald-600 focus:ring-emerald-500"
                                    >
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-bold" style="color: var(--gz-ink);">{{ $court->court_name }}</div>
                                        <div class="text-[11px] mt-0.5" style="color: var(--gz-muted);">
                                            ₱{{ number_format($court->price_per_hour, 2) }}/hr
                                            @if($court->size)
                                                • {{ $court->size }}
                                            @endif
                                        </div>
                                        <div class="court-conflict-badge hidden text-[10px] font-bold text-red-600 dark:text-red-400 mt-1">
                                            Occupied in this window
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Capacity, Ticket Price, Skill Level --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="max_capacity" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                                Max Player Capacity <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                id="max_capacity"
                                name="max_capacity"
                                min="2"
                                max="100"
                                value="{{ old('max_capacity', $session->max_capacity ?? 12) }}"
                                required
                                class="gz-input w-full text-sm"
                            >
                            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Total players allowed to sign up.</p>
                        </div>

                        <div>
                            <label for="price_per_slot" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                                Fee Per Player (₱) <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                id="price_per_slot"
                                name="price_per_slot"
                                min="0"
                                step="0.01"
                                value="{{ old('price_per_slot', $session->price_per_slot ?? 150.00) }}"
                                required
                                class="gz-input w-full text-sm"
                            >
                            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Ticket cost per participating player.</p>
                        </div>

                        <div>
                            <label for="skill_level" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                                Target Skill Level <span class="text-red-500">*</span>
                            </label>
                            <select id="skill_level" name="skill_level" required class="gz-select w-full text-sm">
                                @php
                                    $currentSkill = old('skill_level', $session->skill_level ?? 'All Levels');
                                    $skillsList = \App\Models\OpenPlaySession::SKILL_LEVELS;
                                @endphp
                                @foreach($skillsList as $skill)
                                    <option value="{{ $skill }}" {{ $currentSkill === $skill ? 'selected' : '' }}>
                                        {{ $skill }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Expected player level range.</p>
                        </div>
                    </div>

                    {{-- Details / Description --}}
                    <div>
                        <label for="details" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                            Session Notes & Guidelines (Optional)
                        </label>
                        <textarea
                            id="details"
                            name="details"
                            rows="3"
                            placeholder="Provide rotational format (e.g. King of the Court, round-robin), paddle rules, or what to bring..."
                            class="gz-input w-full text-sm"
                        >{{ old('details', $session->details) }}</textarea>
                    </div>

                    {{-- Live Fee Estimation Card --}}
                    <div class="p-4 rounded-xl border" style="background: var(--gz-surface); border-color: var(--gz-border);">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--gz-muted);">Estimated Court Appointment Fee</span>
                                <p class="text-xs mt-0.5" style="color: var(--gz-muted);">
                                    Calculated as <span id="summary-duration">3.0</span> hrs × <span id="summary-courts-count">0</span> court(s). Due upon manager approval.
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="gz-font-display text-2xl font-extrabold text-emerald-600 dark:text-emerald-400" id="estimated-total">
                                    ₱0.00
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Form Submission --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t" style="border-color: var(--gz-border);">
                        <a href="{{ route('open-play.index') }}" class="gz-btn-outline text-sm">
                            Cancel
                        </a>
                        <button type="submit" class="gz-btn-primary text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Submit Request for Manager Approval</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Live Fee & Time Slot Matrix Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dateInput = document.getElementById('date');
            const startTimeInput = document.getElementById('start_time');
            const endTimeInput = document.getElementById('end_time');
            const courtCheckboxes = document.querySelectorAll('.court-checkbox');
            const summaryDuration = document.getElementById('summary-duration');
            const summaryCourtsCount = document.getElementById('summary-courts-count');
            const estimatedTotal = document.getElementById('estimated-total');
            const slotMatrixBody = document.getElementById('slot-matrix-body');
            const conflictWarning = document.getElementById('conflict-warning');
            const conflictMessage = document.getElementById('conflict-message');

            const bookedSlots = @json($bookedSlots ?? []);
            const specialSlots = @json($specialSlots ?? []);
            const courts = @json($courts ?? []);

            const allTimeSlots = [
                "6:00 AM - 7:00 AM",
                "7:00 AM - 8:00 AM",
                "8:00 AM - 9:00 AM",
                "9:00 AM - 10:00 AM",
                "10:00 AM - 11:00 AM",
                "11:00 AM - 12:00 PM",
                "12:00 PM - 1:00 PM",
                "1:00 PM - 2:00 PM",
                "2:00 PM - 3:00 PM",
                "3:00 PM - 4:00 PM",
                "4:00 PM - 5:00 PM",
                "5:00 PM - 6:00 PM",
                "6:00 PM - 7:00 PM",
                "7:00 PM - 8:00 PM",
                "8:00 PM - 9:00 PM",
                "9:00 PM - 10:00 PM",
                "10:00 PM - 11:00 PM",
                "11:00 PM - 12:00 AM"
            ];

            function parseSlotRange(timeSlotStr) {
                // e.g. "6:00 AM - 7:00 AM"
                const parts = timeSlotStr.split(' - ');
                return {
                    start: parseTimeString(parts[0]),
                    end: parseTimeString(parts[1])
                };
            }

            function parseTimeString(tStr) {
                tStr = tStr.trim();
                const [time, modifier] = tStr.split(' ');
                let [hours, minutes] = time.split(':').map(Number);
                if (modifier === 'PM' && hours < 12) hours += 12;
                if (modifier === 'AM' && hours === 12) hours = 0;
                return hours * 60 + (minutes || 0);
            }

            function renderScheduleMatrix() {
                const date = dateInput.value;
                if (!slotMatrixBody) return;

                slotMatrixBody.innerHTML = '';
                const dateBookings = bookedSlots[date] || {};
                const dateSpecials = specialSlots[date] || {};

                allTimeSlots.forEach(slot => {
                    const tr = document.createElement('tr');
                    const th = document.createElement('th');
                    th.className = 'py-1.5 px-3 text-left font-semibold whitespace-nowrap';
                    th.textContent = slot;
                    tr.appendChild(th);

                    courts.forEach(court => {
                        const td = document.createElement('td');
                        td.className = 'py-1.5 px-2 text-center';

                        const courtBooked = (dateBookings[court.id] || []).includes(slot);
                        const special = dateSpecials[court.id] ? dateSpecials[court.id][slot] : null;

                        const span = document.createElement('span');
                        span.className = 'inline-block text-[11px] font-bold px-2 py-0.5 rounded';

                        if (special) {
                            if (special.type === 'tournament') {
                                span.textContent = 'Tournament';
                                span.style.background = 'rgba(245, 158, 11, 0.15)';
                                span.style.color = '#B45309';
                                span.title = 'Tournament: ' + (special.title || '');
                            } else {
                                span.textContent = 'Open Play';
                                span.style.background = 'rgba(229, 168, 35, 0.15)';
                                span.style.color = '#A67512';
                                span.title = 'Open Play: ' + (special.title || '');
                            }
                        } else if (courtBooked) {
                            span.textContent = 'Booked';
                            span.style.background = 'rgba(239, 68, 68, 0.12)';
                            span.style.color = '#DC2626';
                            span.title = 'Reserved by private player';
                        } else {
                            span.textContent = 'Open';
                            span.style.background = 'rgba(16, 185, 129, 0.1)';
                            span.style.color = '#059669';
                            span.title = 'Available for reservation';
                        }

                        td.appendChild(span);
                        tr.appendChild(td);
                    });

                    slotMatrixBody.appendChild(tr);
                });

                checkConflicts();
            }

            function checkConflicts() {
                const date = dateInput.value;
                const startTime = startTimeInput.value;
                const endTime = endTimeInput.value;

                if (!date || !startTime || !endTime) return;

                const [sH, sM] = startTime.split(':').map(Number);
                const [eH, eM] = endTime.split(':').map(Number);
                const requestedStart = sH * 60 + sM;
                const requestedEnd = eH * 60 + eM;

                if (requestedEnd <= requestedStart) {
                    conflictWarning.classList.add('hidden');
                    return;
                }

                const dateBookings = bookedSlots[date] || {};
                const dateSpecials = specialSlots[date] || {};
                const conflictingCourts = [];

                courtCheckboxes.forEach(cb => {
                    const courtId = cb.dataset.courtId;
                    const courtCard = document.getElementById('court-card-' + courtId);
                    const conflictBadge = courtCard ? courtCard.querySelector('.court-conflict-badge') : null;

                    let hasConflict = false;

                    allTimeSlots.forEach(slot => {
                        const { start: slotStart, end: slotEnd } = parseSlotRange(slot);
                        const isOverlapping = (slotStart < requestedEnd && slotEnd > requestedStart);

                        if (isOverlapping) {
                            const courtBooked = (dateBookings[courtId] || []).includes(slot);
                            const special = dateSpecials[courtId] ? dateSpecials[courtId][slot] : null;

                            if (courtBooked || special) {
                                hasConflict = true;
                            }
                        }
                    });

                    if (conflictBadge) {
                        if (hasConflict) {
                            conflictBadge.classList.remove('hidden');
                            courtCard.style.borderColor = 'rgba(239, 68, 68, 0.5)';
                        } else {
                            conflictBadge.classList.add('hidden');
                            courtCard.style.borderColor = 'var(--gz-border)';
                        }
                    }

                    if (cb.checked && hasConflict) {
                        conflictingCourts.push(cb.dataset.courtName || ('Court ' + courtId));
                    }
                });

                if (conflictingCourts.length > 0) {
                    conflictWarning.classList.remove('hidden');
                    conflictMessage.textContent = 'Conflict detected on ' + conflictingCourts.join(', ') + ' between ' + startTime + ' and ' + endTime + '. These court slots are already occupied.';
                } else {
                    conflictWarning.classList.add('hidden');
                }
            }

            function calculateFee() {
                const startTime = startTimeInput.value;
                const endTime = endTimeInput.value;

                let durationHours = 0;
                if (startTime && endTime) {
                    const [sH, sM] = startTime.split(':').map(Number);
                    const [eH, eM] = endTime.split(':').map(Number);
                    const startMin = sH * 60 + sM;
                    const endMin = eH * 60 + eM;
                    if (endMin > startMin) {
                        durationHours = (endMin - startMin) / 60;
                    }
                }

                let totalHourlyRate = 0;
                let courtsCount = 0;
                courtCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        courtsCount++;
                        totalHourlyRate += parseFloat(cb.dataset.rate || 0);
                    }
                });

                summaryDuration.textContent = durationHours.toFixed(1);
                summaryCourtsCount.textContent = courtsCount;

                const fee = durationHours * totalHourlyRate;
                estimatedTotal.textContent = '₱' + fee.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                checkConflicts();
            }

            dateInput.addEventListener('change', renderScheduleMatrix);
            startTimeInput.addEventListener('change', calculateFee);
            endTimeInput.addEventListener('change', calculateFee);
            courtCheckboxes.forEach(cb => cb.addEventListener('change', calculateFee));

            renderScheduleMatrix();
            calculateFee();
        });
    </script>
</x-app-layout>
