<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs mb-1" style="color: var(--gz-muted);">
                    <a href="{{ route('admin.open-play.index') }}" class="hover:underline">Open Play & Tournaments</a>
                    <span>/</span>
                    <span>{{ $session->exists ? 'Edit Session' : 'Create Session' }}</span>
                </div>
                <h1 class="gz-font-display font-bold text-xl sm:text-2xl">
                    {{ $session->exists ? 'Edit Session: ' . $session->title : 'Create Open Play / Tournament Session' }}
                </h1>
                <p class="text-xs mt-1" style="color: var(--gz-muted);">
                    Configure communal player pool events, allocate courts, set max slot capacities and per-player participation fees.
                </p>
            </div>
            <a href="{{ route('admin.open-play.index') }}" class="gz-btn-outline gz-btn-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back to Sessions</span>
            </a>
        </div>
    </x-slot>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl text-sm" style="background: var(--gz-danger-bg); color: var(--gz-danger);" role="alert">
            <p class="font-bold mb-1">Please correct the following errors:</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="gz-panel p-6 max-w-4xl mx-auto">
        <form method="POST" action="{{ $session->exists ? route('admin.open-play.update', $session) : route('admin.open-play.store') }}">
            @csrf
            @if($session->exists)
                @method('PUT')
            @endif

            <div class="space-y-6">
                {{-- Session Title & Type --}}
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
                            placeholder="e.g. Friday Night Open Play (Intermediate 3.5+)"
                            required
                            class="gz-input w-full text-sm"
                        >
                    </div>

                    <div>
                        <label for="session_type" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                            Event Type <span class="text-red-500">*</span>
                        </label>
                        <select id="session_type" name="session_type" required class="gz-select w-full text-sm">
                            <option value="open_play" {{ old('session_type', $session->session_type) === 'open_play' ? 'selected' : '' }}>
                                Open Play (Communal Pool)
                            </option>
                            <option value="tournament" {{ old('session_type', $session->session_type) === 'tournament' ? 'selected' : '' }}>
                                Tournament / Bracket
                            </option>
                        </select>
                    </div>
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
                            value="{{ old('date', $session->date ? $session->date->format('Y-m-d') : '') }}"
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
                                Verify court vacancy across existing private bookings and other communal sessions on your selected date.
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
                            <tbody id="admin-slot-matrix-body">
                                {{-- Dynamically populated via JS based on selected date --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Live Conflict Warning Box --}}
                    <div id="admin-conflict-warning" class="hidden mt-3 p-3 rounded-lg text-xs" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #DC2626;">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <span class="font-bold">Time Slot Conflict Detected:</span>
                                <span id="admin-conflict-message">One or more selected courts already have an active booking or session in this window.</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Court Allocation --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                        Allocated Courts <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs mb-3" style="color: var(--gz-muted);">
                        Select which courts are reserved for this communal event. These courts will be blocked from private group booking during this time window.
                    </p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($courts as $court)
                            @php
                                $checked = in_array($court->id, old('allocated_courts', $allocatedCourtIds ?? []));
                            @endphp
                            <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer hover:bg-black/5 dark:hover:bg-white/5 transition"
                                   id="admin-court-card-{{ $court->id }}"
                                   style="border-color: var(--gz-border); background: var(--gz-surface);">
                                <input
                                    type="checkbox"
                                    name="allocated_courts[]"
                                    value="{{ $court->id }}"
                                    data-court-id="{{ $court->id }}"
                                    data-court-name="{{ $court->court_name }}"
                                    {{ $checked ? 'checked' : '' }}
                                    class="admin-court-checkbox rounded text-emerald-600 focus:ring-emerald-500"
                                >
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs font-bold block" style="color: var(--gz-ink);">{{ $court->court_name }}</span>
                                    <span class="text-[10px] block" style="color: var(--gz-muted);">
                                        ₱{{ number_format($court->price_per_hour, 2) }}/hr
                                        @if($court->size)
                                            • {{ $court->size }}
                                        @endif
                                    </span>
                                    <span class="admin-court-conflict-badge hidden text-[10px] font-bold text-red-600 dark:text-red-400 block mt-0.5">Occupied</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('allocated_courts')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Max Capacity, Skill Level & Price Per Slot --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="max_capacity" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                            Max Capacity (Player Slots) <span class="text-red-500">*</span>
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
                        <p class="text-[11px] mt-1" style="color: var(--gz-muted);">
                            Typically 4 to 6 player slots per allocated court.
                        </p>
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
                        <p class="text-[11px] mt-1" style="color: var(--gz-muted);">
                            Expected player skill range for this session.
                        </p>
                    </div>

                    <div>
                        <label for="price_per_slot" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                            Price Per Player Slot (₱) <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            id="price_per_slot"
                            name="price_per_slot"
                            value="{{ old('price_per_slot', $session->price_per_slot ?? 150.00) }}"
                            required
                            class="gz-input w-full text-sm font-mono font-bold"
                        >
                        <p class="text-[11px] mt-1" style="color: var(--gz-muted);">
                            Individual player participation fee (not full court fee).
                        </p>
                    </div>
                </div>

                {{-- If Editing: Status --}}
                @if($session->exists)
                    <div>
                        <label for="session_status" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                            Session Status
                        </label>
                        <select id="session_status" name="session_status" class="gz-select w-full sm:w-64 text-sm">
                            <option value="scheduled" {{ old('session_status', $session->session_status) === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="ongoing" {{ old('session_status', $session->session_status) === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                            <option value="completed" {{ old('session_status', $session->session_status) === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('session_status', $session->session_status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                @endif

                {{-- Rules / Details --}}
                <div>
                    <label for="details" class="block text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--gz-muted);">
                        Event Details & Rotation Format (Optional)
                    </label>
                    <textarea
                        id="details"
                        name="details"
                        rows="3"
                        placeholder="e.g. Round-robin rotation format with 11-point games. Paddles and balls provided. Warm up starts 15 mins prior."
                        class="gz-input w-full text-sm"
                    >{{ old('details', $session->details) }}</textarea>
                </div>

                {{-- Actions --}}
                <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--gz-border);">
                    <a href="{{ route('admin.open-play.index') }}" class="gz-btn-outline text-xs">
                        Cancel
                    </a>
                    <button type="submit" class="gz-btn-primary text-xs">
                        {{ $session->exists ? 'Save Changes' : 'Publish Open Play Session' }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Live Schedule Matrix & Conflict Checker Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dateInput = document.getElementById('date');
            const startTimeInput = document.getElementById('start_time');
            const endTimeInput = document.getElementById('end_time');
            const courtCheckboxes = document.querySelectorAll('.admin-court-checkbox');
            const slotMatrixBody = document.getElementById('admin-slot-matrix-body');
            const conflictWarning = document.getElementById('admin-conflict-warning');
            const conflictMessage = document.getElementById('admin-conflict-message');

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
                            span.title = 'Reserved by private booking';
                        } else {
                            span.textContent = 'Open';
                            span.style.background = 'rgba(16, 185, 129, 0.1)';
                            span.style.color = '#059669';
                            span.title = 'Court is free';
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
                    if (conflictWarning) conflictWarning.classList.add('hidden');
                    return;
                }

                const dateBookings = bookedSlots[date] || {};
                const dateSpecials = specialSlots[date] || {};
                const conflictingCourts = [];

                courtCheckboxes.forEach(cb => {
                    const courtId = cb.dataset.courtId;
                    const courtCard = document.getElementById('admin-court-card-' + courtId);
                    const conflictBadge = courtCard ? courtCard.querySelector('.admin-court-conflict-badge') : null;

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

                if (conflictingCourts.length > 0 && conflictWarning && conflictMessage) {
                    conflictWarning.classList.remove('hidden');
                    conflictMessage.textContent = 'Conflict detected on ' + conflictingCourts.join(', ') + ' between ' + startTime + ' and ' + endTime + '. These court slots are already occupied.';
                } else if (conflictWarning) {
                    conflictWarning.classList.add('hidden');
                }
            }

            dateInput.addEventListener('change', renderScheduleMatrix);
            startTimeInput.addEventListener('change', checkConflicts);
            endTimeInput.addEventListener('change', checkConflicts);
            courtCheckboxes.forEach(cb => cb.addEventListener('change', checkConflicts));

            renderScheduleMatrix();
            checkConflicts();
        });
    </script>
</x-admin-layout>
