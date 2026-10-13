<x-admin-layout>
    <x-slot name="heading">{{ $court->exists ? 'Edit Court' : 'Add New Court' }}</x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="mb-4">
            <a href="{{ route('admin.courts.index') }}" class="gz-link text-sm">
                ← Back to court inventory
            </a>
        </div>

        <div class="gz-panel">
            <div class="gz-panel-header">
                <div>
                    <span class="gz-eyebrow" style="color: var(--gz-pop-dark);">Arena setup</span>
                    <h2 class="gz-font-display font-bold text-lg mt-1">
                        {{ $court->exists ? 'Update court: ' . $court->court_name : 'Register new court' }}
                    </h2>
                    <p class="text-sm mt-1" style="color: var(--gz-muted);">
                        Set operational details and pricing displayed in the court reservation system.
                    </p>
                </div>
            </div>

            <div class="gz-panel-body">
                <form method="POST" action="{{ $court->exists ? route('admin.courts.update', $court) : route('admin.courts.store') }}" class="space-y-5">
                    @csrf
                    @if($court->exists)
                        @method('PUT')
                    @endif

                    {{-- Court Name --}}
                    <div>
                        <label for="court_name" class="gz-label">Court name *</label>
                        <input id="court_name"
                               name="court_name"
                               type="text"
                               value="{{ old('court_name', $court->court_name) }}"
                               placeholder="e.g. Center Court, Court 5"
                               required
                               class="gz-input">
                        <x-input-error :messages="$errors->get('court_name')" class="gz-error" />
                    </div>

                    {{-- Court Size and Price Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="size" class="gz-label">Court dimensions / type *</label>
                            <select id="size"
                                    name="size"
                                    required
                                    class="gz-input cursor-pointer">
                                <option value="" disabled {{ old('size', $court->size) ? '' : 'selected' }}>Select court dimensions / type</option>
                                <option value="Regular (13.41m x 6.10m)" @selected(str_contains(strtolower(old('size', $court->size ?? '')), 'regular') || str_contains(old('size', $court->size ?? ''), '13.41'))>Regular (13.41m x 6.10m)</option>
                                <option value="Junior (10m x 4.5m)" @selected(str_contains(strtolower(old('size', $court->size ?? '')), 'junior') || str_contains(old('size', $court->size ?? ''), '10m'))>Junior (10m x 4.5m)</option>
                                @if($court->size && !str_contains(strtolower($court->size), 'regular') && !str_contains(strtolower($court->size), 'junior'))
                                    <option value="{{ $court->size }}" selected>{{ $court->size }} (Existing)</option>
                                @endif
                            </select>
                            <x-input-error :messages="$errors->get('size')" class="gz-error" />
                        </div>

                        <div>
                            <label for="price_per_hour" class="gz-label">Rate per hour (PHP) *</label>
                            <input id="price_per_hour"
                                   name="price_per_hour"
                                   type="number"
                                   min="0"
                                   step="0.01"
                                   value="{{ old('price_per_hour', $court->price_per_hour) }}"
                                   placeholder="e.g. 350.00"
                                   required
                                   class="gz-input">
                            <x-input-error :messages="$errors->get('price_per_hour')" class="gz-error" />
                        </div>
                    </div>

                    {{-- Court Status --}}
                    <div>
                        <label for="court_status" class="gz-label">Operational status *</label>
                        <select id="court_status" name="court_status" class="gz-input cursor-pointer">
                            <option value="available" @selected(old('court_status', $court->court_status ?: 'available') === 'available')>
                                Available (open for public reservations)
                            </option>
                            <option value="maintenance" @selected(old('court_status', $court->court_status) === 'maintenance')>
                                Maintenance (temporarily unavailable)
                            </option>
                            <option value="closed" @selected(old('court_status', $court->court_status) === 'closed')>
                                Closed (offline / archived)
                            </option>
                        </select>
                        <x-input-error :messages="$errors->get('court_status')" class="gz-error" />
                    </div>

                    {{-- Form Actions --}}
                    <div class="pt-6 border-t flex flex-col-reverse sm:flex-row sm:justify-end gap-3" style="border-color: var(--gz-border);">
                        <a href="{{ route('admin.courts.index') }}" class="gz-btn-outline text-center">
                            Cancel
                        </a>
                        <button type="submit" class="gz-btn-primary">
                            {{ $court->exists ? 'Save changes' : 'Create court' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>