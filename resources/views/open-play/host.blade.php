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
                    {{-- Hidden inputs populated by interactive schedule matrix --}}
                    <input type="hidden" id="start_time" name="start_time" value="{{ old('start_time', '') }}">
                    <input type="hidden" id="end_time" name="end_time" value="{{ old('end_time', '') }}">
                    <div id="host-allocated-courts-inputs"></div>

                    {{-- Title & Date --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
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
                    </div>

                    {{-- Interactive Court Schedule & Time Slot Availability Matrix --}}
                    <div class="p-4 rounded-xl border" style="border-color: var(--gz-border); background: var(--gz-surface);">
                        <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider" style="color: var(--gz-ink);">
                                    Select Time Blocks &amp; Allocated Courts <span class="text-red-500">*</span>
                                </h3>
                                <p class="text-[11px] mt-0.5" style="color: var(--gz-muted);">
                                    Click one or more <strong>Open</strong> blocks below to select your session time window and courts.
                                </p>
                            </div>
                            <div class="flex items-center gap-3 text-[10px] flex-wrap" style="color: var(--gz-muted);">
                                <span class="flex items-center gap-1">
                                    <span class="w-2.5 h-2.5 rounded border" style="background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.5);"></span> Open (Click)
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="w-2.5 h-2.5 rounded border" style="background: var(--gz-pop, #E5A823); border-color: #14221A;"></span> Selected
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

                        {{-- Selected Time Blocks & Courts Summary Bar --}}
                        <div id="host-selection-bar" class="mb-3 p-3 rounded-lg border text-xs" style="background: var(--gz-bg); border-color: var(--gz-border);">
                            <div id="host-selection-empty" style="color: var(--gz-muted);">
                                No time blocks selected yet. Click any <strong>Open</strong> slot in the schedule below to set your session time and courts.
                            </div>
                            <div id="host-selection-details" class="hidden space-y-2">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-4 flex-wrap">
                                        <span>
                                            <strong style="color: var(--gz-muted);">Time Window:</strong>
                                            <span id="host-summary-time" class="font-bold" style="color: var(--gz-ink);"></span>
                                        </span>
                                        <span>
                                            <strong style="color: var(--gz-muted);">Allocated Courts:</strong>
                                            <span id="host-summary-courts" class="font-bold" style="color: var(--gz-ink);"></span>
                                        </span>
                                    </div>
                                    <button type="button" id="host-clear-slots-btn" class="text-xs font-bold underline cursor-pointer" style="color: #DC2626;">
                                        Clear Selection
                                    </button>
                                </div>
                                <div id="host-selected-chips" class="flex items-center gap-1.5 flex-wrap"></div>
                            </div>
                        </div>

                        {{-- Live Slot Matrix --}}
                        <div class="overflow-x-auto max-h-72 overflow-y-auto mt-2 border rounded-lg" style="border-color: var(--gz-border);">
                            <table class="gz-table text-xs w-full">
                                <thead style="position: sticky; top: 0; background: var(--gz-surface); z-index: 2;">
                                    <tr>
                                        <th class="py-2 px-3 text-left" style="min-width: 145px;">Time Slot</th>
                                        @foreach($courts as $court)
                                            <th class="py-2 px-2 text-center whitespace-nowrap" style="min-width: 135px;">
                                                {{ $court->court_name }}
                                                @if($court->size)
                                                    <span class="block text-[10px] font-medium" style="color: var(--gz-muted);">{{ $court->size }}</span>
                                                @endif
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

                        @error('allocated_courts')
                            <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                        @enderror

                        {{-- Live Conflict Warning Box --}}
                        <div id="conflict-warning" class="hidden mt-3 p-3 rounded-lg text-xs" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #DC2626;">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div>
                                    <span class="font-bold">Notice:</span>
                                    <span id="conflict-message">One or more selected courts are already booked during this time window. Please adjust your time or choose other courts.</span>
                                </div>
                            </div>
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

    {{-- Live Interactive Schedule Matrix & Fee Estimation Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('hostForm');
            const dateInput = document.getElementById('date');
            const startTimeInput = document.getElementById('start_time');
            const endTimeInput = document.getElementById('end_time');
            const courtsContainer = document.getElementById('host-allocated-courts-inputs');
            const summaryDuration = document.getElementById('summary-duration');
            const summaryCourtsCount = document.getElementById('summary-courts-count');
            const estimatedTotal = document.getElementById('estimated-total');
            const slotMatrixBody = document.getElementById('slot-matrix-body');
            const selectionEmpty = document.getElementById('host-selection-empty');
            const selectionDetails = document.getElementById('host-selection-details');
            const summaryTime = document.getElementById('host-summary-time');
            const summaryCourts = document.getElementById('host-summary-courts');
            const selectedChips = document.getElementById('host-selected-chips');
            const clearSlotsBtn = document.getElementById('host-clear-slots-btn');
            const conflictWarning = document.getElementById('conflict-warning');
            const conflictMessage = document.getElementById('conflict-message');

            if (dateInput) {
                const now = new Date();
                const localToday = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
                if (!dateInput.min || localToday > dateInput.min) {
                    dateInput.min = localToday;
                }
                if (dateInput.value && dateInput.value < dateInput.min) {
                    dateInput.value = dateInput.min;
                }
            }

            const bookedSlots = @json($bookedSlots ?? []);
            const specialSlots = @json($specialSlots ?? []);
            const courts = @json($courts ?? []);
            const initialCourtIds = @json(array_map('intval', old('allocated_courts', [])));

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

            let selectedBlocks = [];

            function parseSlotRange(timeSlotStr) {
                const parts = timeSlotStr.split(' - ');
                return {
                    start: parseTimeString(parts[0], false),
                    end: parseTimeString(parts[1], true)
                };
            }

            function parseTimeString(tStr, isEnd) {
                tStr = tStr.trim();
                const [time, modifier] = tStr.split(' ');
                let [hours, minutes] = time.split(':').map(Number);
                if (modifier === 'PM' && hours < 12) hours += 12;
                if (modifier === 'AM' && hours === 12) {
                    hours = isEnd ? 24 : 0;
                }
                return hours * 60 + (minutes || 0);
            }

            function formatMinutes24(mins) {
                if (mins >= 1440) return '23:59';
                const h = Math.floor(mins / 60);
                const m = mins % 60;
                return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
            }

            function formatMinutes12(mins) {
                if (mins >= 1440) return '12:00 AM';
                let h = Math.floor(mins / 60);
                const m = mins % 60;
                const ampm = h >= 12 ? 'PM' : 'AM';
                h = h % 12;
                if (h === 0) h = 12;
                return h + ':' + String(m).padStart(2, '0') + ' ' + ampm;
            }

            // Restore old input if validation failed
            if (initialCourtIds.length > 0 && startTimeInput.value && endTimeInput.value) {
                const [sH, sM] = startTimeInput.value.split(':').map(Number);
                const [eH, eM] = endTimeInput.value.split(':').map(Number);
                const initStart = sH * 60 + (sM || 0);
                const initEnd = (eH === 23 && eM === 59) ? 1440 : (eH * 60 + (eM || 0));

                allTimeSlots.forEach(slot => {
                    const { start, end } = parseSlotRange(slot);
                    if (start >= initStart && end <= initEnd) {
                        initialCourtIds.forEach(cId => {
                            selectedBlocks.push({ courtId: Number(cId), slot: slot });
                        });
                    }
                });
            }

            function isBlockSelected(courtId, slot) {
                return selectedBlocks.some(b => Number(b.courtId) === Number(courtId) && b.slot === slot);
            }

            function toggleBlock(courtId, slot) {
                const idx = selectedBlocks.findIndex(b => Number(b.courtId) === Number(courtId) && b.slot === slot);
                if (idx >= 0) {
                    selectedBlocks.splice(idx, 1);
                } else {
                    selectedBlocks.push({ courtId: Number(courtId), slot: slot });
                }
                syncSelectionState();
                renderScheduleMatrix();
            }

            function syncSelectionState() {
                courtsContainer.innerHTML = '';
                selectedChips.innerHTML = '';

                if (selectedBlocks.length === 0) {
                    startTimeInput.value = '';
                    endTimeInput.value = '';
                    selectionEmpty.classList.remove('hidden');
                    selectionDetails.classList.add('hidden');
                    summaryDuration.textContent = '0.0';
                    summaryCourtsCount.textContent = '0';
                    estimatedTotal.textContent = '₱0.00';
                    if (conflictWarning) conflictWarning.classList.add('hidden');
                    return;
                }

                selectionEmpty.classList.add('hidden');
                selectionDetails.classList.remove('hidden');

                let minStart = Infinity;
                let maxEnd = -Infinity;
                const uniqueCourtIds = [];

                selectedBlocks.forEach((b, idx) => {
                    const { start, end } = parseSlotRange(b.slot);
                    if (start < minStart) minStart = start;
                    if (end > maxEnd) maxEnd = end;
                    if (!uniqueCourtIds.includes(Number(b.courtId))) {
                        uniqueCourtIds.push(Number(b.courtId));
                    }

                    const courtObj = courts.find(c => Number(c.id) === Number(b.courtId));
                    const courtName = courtObj ? courtObj.court_name : ('Court ' + b.courtId);

                    const chip = document.createElement('span');
                    chip.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold';
                    chip.style.background = 'rgba(229, 168, 35, 0.2)';
                    chip.style.color = 'var(--gz-ink)';
                    chip.style.border = '1px solid rgba(229, 168, 35, 0.5)';
                    chip.innerHTML = `<span>${courtName} · ${b.slot}</span>`;

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'font-extrabold hover:opacity-75 cursor-pointer ml-1';
                    removeBtn.textContent = '×';
                    removeBtn.title = 'Remove slot';
                    removeBtn.addEventListener('click', function () {
                        selectedBlocks.splice(idx, 1);
                        syncSelectionState();
                        renderScheduleMatrix();
                    });

                    chip.appendChild(removeBtn);
                    selectedChips.appendChild(chip);
                });

                startTimeInput.value = formatMinutes24(minStart);
                endTimeInput.value = formatMinutes24(maxEnd);

                let totalHourlyRate = 0;
                uniqueCourtIds.sort((a, b) => a - b).forEach(cId => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'allocated_courts[]';
                    input.value = String(cId);
                    courtsContainer.appendChild(input);

                    const c = courts.find(ct => Number(ct.id) === Number(cId));
                    if (c) {
                        totalHourlyRate += parseFloat(c.price_per_hour || 0);
                    }
                });

                const durationHours = Math.max(1.0, (maxEnd - minStart) / 60);
                const durationLabel = durationHours.toFixed(1).replace(/\.0$/, '');
                summaryTime.textContent = `${formatMinutes12(minStart)} – ${formatMinutes12(maxEnd)} (${durationLabel} hr${durationLabel == 1 ? '' : 's'})`;
                summaryCourts.textContent = uniqueCourtIds.map(id => {
                    const c = courts.find(ct => Number(ct.id) === Number(id));
                    return c ? c.court_name : ('Court ' + id);
                }).join(', ');

                summaryDuration.textContent = durationHours.toFixed(1);
                summaryCourtsCount.textContent = String(uniqueCourtIds.length);
                const fee = durationHours * totalHourlyRate;
                estimatedTotal.textContent = '₱' + fee.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                checkConflicts(minStart, maxEnd, uniqueCourtIds);
            }

            function checkConflicts(minStart, maxEnd, uniqueCourtIds) {
                if (!conflictWarning || !conflictMessage) return;
                const date = dateInput.value;
                const dateBookings = bookedSlots[date] || {};
                const dateSpecials = specialSlots[date] || {};
                const conflictingCourts = [];

                uniqueCourtIds.forEach(courtId => {
                    let hasConflict = false;
                    allTimeSlots.forEach(slot => {
                        const { start, end } = parseSlotRange(slot);
                        if (start < maxEnd && end > minStart) {
                            const courtBooked = (dateBookings[courtId] || []).includes(slot);
                            const special = dateSpecials[courtId] ? dateSpecials[courtId][slot] : null;
                            if (courtBooked || special) {
                                hasConflict = true;
                            }
                        }
                    });
                    if (hasConflict) {
                        const c = courts.find(ct => Number(ct.id) === Number(courtId));
                        conflictingCourts.push(c ? c.court_name : ('Court ' + courtId));
                    }
                });

                if (conflictingCourts.length > 0) {
                    conflictWarning.classList.remove('hidden');
                    conflictMessage.textContent = 'Occupied slot detected within the selected time span on ' + conflictingCourts.join(', ') + '. Please choose contiguous open slots.';
                } else {
                    conflictWarning.classList.add('hidden');
                }
            }

            function renderScheduleMatrix() {
                if (dateInput && dateInput.min && dateInput.value && dateInput.value < dateInput.min) {
                    dateInput.value = dateInput.min;
                }
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
                        const selected = isBlockSelected(court.id, slot);

                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'w-full text-[11px] font-bold px-2.5 py-1 rounded-lg transition';

                        if (special) {
                            btn.disabled = true;
                            btn.style.cursor = 'not-allowed';
                            if (special.type === 'tournament') {
                                btn.textContent = 'Tournament';
                                btn.style.background = 'rgba(245, 158, 11, 0.15)';
                                btn.style.color = '#B45309';
                                btn.title = 'Tournament: ' + (special.title || '');
                            } else {
                                btn.textContent = 'Open Play';
                                btn.style.background = 'rgba(229, 168, 35, 0.15)';
                                btn.style.color = '#A67512';
                                btn.title = 'Open Play: ' + (special.title || '');
                            }
                        } else if (courtBooked) {
                            btn.disabled = true;
                            btn.style.cursor = 'not-allowed';
                            btn.textContent = 'Booked';
                            btn.style.background = 'rgba(239, 68, 68, 0.12)';
                            btn.style.color = '#DC2626';
                            btn.title = 'Reserved by private booking';
                        } else if (selected) {
                            btn.textContent = 'Selected';
                            btn.style.background = 'var(--gz-pop, #E5A823)';
                            btn.style.color = '#14221A';
                            btn.style.border = '1.5px solid #14221A';
                            btn.style.cursor = 'pointer';
                            btn.title = 'Click to deselect ' + court.court_name + ' (' + slot + ')';
                            btn.addEventListener('click', function () {
                                toggleBlock(court.id, slot);
                            });
                        } else {
                            btn.textContent = 'Open';
                            btn.style.background = 'rgba(16, 185, 129, 0.12)';
                            btn.style.color = '#059669';
                            btn.style.border = '1px solid rgba(16, 185, 129, 0.3)';
                            btn.style.cursor = 'pointer';
                            btn.title = 'Click to select ' + court.court_name + ' (' + slot + ')';
                            btn.addEventListener('click', function () {
                                toggleBlock(court.id, slot);
                            });
                        }

                        td.appendChild(btn);
                        tr.appendChild(td);
                    });

                    slotMatrixBody.appendChild(tr);
                });
            }

            if (clearSlotsBtn) {
                clearSlotsBtn.addEventListener('click', function () {
                    selectedBlocks = [];
                    syncSelectionState();
                    renderScheduleMatrix();
                });
            }

            dateInput.addEventListener('change', function () {
                const date = dateInput.value;
                const dateBookings = bookedSlots[date] || {};
                const dateSpecials = specialSlots[date] || {};
                selectedBlocks = selectedBlocks.filter(b => {
                    const courtBooked = (dateBookings[b.courtId] || []).includes(b.slot);
                    const special = dateSpecials[b.courtId] ? dateSpecials[b.courtId][b.slot] : null;
                    return !courtBooked && !special;
                });
                syncSelectionState();
                renderScheduleMatrix();
            });

            if (form) {
                form.addEventListener('submit', function (e) {
                    if (selectedBlocks.length === 0) {
                        e.preventDefault();
                        if (conflictWarning && conflictMessage) {
                            conflictWarning.classList.remove('hidden');
                            conflictMessage.textContent = 'Please click at least one Open time block in the schedule matrix above to set your session time and courts.';
                        }
                        slotMatrixBody.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            }

            syncSelectionState();
            renderScheduleMatrix();
        });
    </script>
</x-app-layout>
