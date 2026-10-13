<x-admin-layout>
    <x-slot name="heading">{{ $event->exists ? 'Edit Promo' : 'Add New Promo' }}</x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="mb-4">
            <a href="{{ route('admin.events.index') }}" class="gz-link text-sm">
                ← Back to promos
            </a>
        </div>

        <div class="gz-panel">
            <div class="gz-panel-header">
                <div>
                    <span class="gz-eyebrow" style="color: var(--gz-pop-dark);">Promo configuration</span>
                    <h2 class="gz-font-display font-bold text-lg mt-1">
                        {{ $event->exists ? 'Update promo: ' . $event->event_title : 'Create new promo' }}
                    </h2>
                    <p class="text-sm mt-1" style="color: var(--gz-muted);">
                        Set promo details, date range, discounts, and promotional imagery.
                    </p>
                </div>
            </div>

            <div class="gz-panel-body">
                <form method="POST" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @if($event->exists)
                        @method('PUT')
                    @endif

                    {{-- Event Title --}}
                    <div>
                        <label for="event_title" class="gz-label">Promo title *</label>
                        <input id="event_title"
                               name="event_title"
                               type="text"
                               value="{{ old('event_title', $event->event_title) }}"
                               placeholder="e.g. Summer Special, Holiday Promo"
                               required
                               class="gz-input">
                        <x-input-error :messages="$errors->get('event_title')" class="gz-error" />
                    </div>

                    {{-- Date Range --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="start_date" class="gz-label">Start date *</label>
                            <input id="start_date"
                                   name="start_date"
                                   type="date"
                                   value="{{ old('start_date', $event->start_date?->format('Y-m-d')) }}"
                                   required
                                   class="gz-input">
                            <x-input-error :messages="$errors->get('start_date')" class="gz-error" />
                        </div>

                        <div>
                            <label for="end_date" class="gz-label">End date *</label>
                            <input id="end_date"
                                   name="end_date"
                                   type="date"
                                   value="{{ old('end_date', $event->end_date?->format('Y-m-d')) }}"
                                   required
                                   class="gz-input">
                            <x-input-error :messages="$errors->get('end_date')" class="gz-error" />
                        </div>
                    </div>

                    {{-- Discount --}}
                    <div>
                        <label for="discount" class="gz-label">Discount percentage (optional)</label>
                        <input id="discount"
                               name="discount"
                               type="number"
                               min="0"
                               max="100"
                               step="0.01"
                               value="{{ old('discount', $event->discount) }}"
                               placeholder="e.g. 10.00"
                               class="gz-input">
                        <x-input-error :messages="$errors->get('discount')" class="gz-error" />
                        <p class="text-xs mt-1" style="color: var(--gz-muted);">Leave empty if no discount applies</p>
                    </div>

                    {{-- Details --}}
                    <div>
                        <label for="details" class="gz-label">Promo details (optional)</label>
                        <textarea id="details"
                                  name="details"
                                  rows="4"
                                  placeholder="Describe the promo, rules, or any special information..."
                                  class="gz-input">{{ old('details', $event->details) }}</textarea>
                        <x-input-error :messages="$errors->get('details')" class="gz-error" />
                    </div>

                    {{-- Image Upload --}}
                    <div>
                        <label for="image" class="gz-label">Promo image (optional)</label>
                        <input id="image"
                               name="image"
                               type="file"
                               accept="image/*"
                               class="gz-input">
                        <x-input-error :messages="$errors->get('image')" class="gz-error" />
                        <p class="text-xs mt-1" style="color: var(--gz-muted);">Max file size: 5MB. Recommended: 1200x630px</p>
                        @if($event->image)
                            <div class="mt-3">
                                <p class="text-sm mb-2" style="color: var(--gz-muted);">Current image:</p>
                                <img src="{{ asset('storage/' . $event->image) }}" alt="Current promo image" class="w-48 h-auto rounded border" style="border-color: var(--gz-border);">
                            </div>
                        @endif
                    </div>

                    {{-- Form Actions --}}
                    <div class="pt-6 border-t flex flex-col-reverse sm:flex-row sm:justify-end gap-3" style="border-color: var(--gz-border);">
                        <a href="{{ route('admin.events.index') }}" class="gz-btn-outline text-center">
                            Cancel
                        </a>
                        <button type="submit" class="gz-btn-primary">
                            {{ $event->exists ? 'Save changes' : 'Create promo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
