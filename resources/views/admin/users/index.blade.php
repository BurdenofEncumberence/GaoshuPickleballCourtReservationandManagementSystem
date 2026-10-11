<x-admin-layout>
    <x-slot name="heading">User &amp; Employee Management</x-slot>

    <div x-data="{ showAddModal: {{ $errors->any() ? 'true' : 'false' }} }">
        <!-- Header Panel -->
        <div class="gz-panel gz-panel-body mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="gz-eyebrow">Staff, Manager &amp; Player Directory</span>
                <h1 class="gz-font-display font-bold text-xl mt-1">Employee &amp; User Management</h1>
                <p class="text-sm mt-1" style="color: var(--gz-muted);">
                    Provision new Staff or Manager accounts, assign role permissions, and activate or deactivate user access.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="showAddModal = true"
                    class="gz-btn-primary gz-btn-sm flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Add Staff / Manager</span>
                </button>
            </div>
        </div>

        @if(session('status'))
            <div class="gz-status mb-6">
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-3.5 rounded-xl border text-xs font-semibold" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.35); color: #dc2626;">
                {{ session('error') }}
            </div>
        @endif

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="gz-kpi-card">
                <div class="gz-eyebrow mb-1">Total Accounts</div>
                <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-right" style="color: var(--gz-ink);">
                    {{ number_format($kpis['total']) }}
                </div>
                <p class="text-[11px] mt-1" style="color: var(--gz-muted);">All registered users</p>
            </div>

            <div class="gz-kpi-card">
                <div class="gz-eyebrow mb-1">Employees</div>
                <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-right" style="color: var(--gz-pop-dark);">
                    {{ number_format($kpis['employees']) }}
                </div>
                <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Admin, Manager &amp; Staff</p>
            </div>

            <div class="gz-kpi-card">
                <div class="gz-eyebrow mb-1">Active Accounts</div>
                <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-emerald-600 text-right">
                    {{ number_format($kpis['active']) }}
                </div>
                <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Permitted to sign in</p>
            </div>

            <div class="gz-kpi-card">
                <div class="gz-eyebrow mb-1">Deactivated</div>
                <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-red-600 text-right">
                    {{ number_format($kpis['deactivated']) }}
                </div>
                <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Suspended access</p>
            </div>

            <div class="gz-kpi-card col-span-2 lg:col-span-1">
                <div class="gz-eyebrow mb-1">Player Accounts</div>
                <div class="gz-kpi-value text-xl sm:text-2xl font-bold text-right" style="color: var(--gz-ink);">
                    {{ number_format($kpis['players']) }}
                </div>
                <p class="text-[11px] mt-1" style="color: var(--gz-muted);">Registered players</p>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="gz-panel p-4 mb-6" style="background: var(--gz-surface);">
            <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <!-- Search bar -->
                <div class="sm:col-span-5 relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by Name, Email, or Role..."
                        class="gz-input text-xs w-full py-2 pr-8"
                    >
                    @if($search !== '')
                        <a
                            href="{{ route('admin.users.index', array_merge(request()->except(['search', 'page']))) }}"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs font-bold opacity-60 hover:opacity-100"
                            title="Clear search"
                        >×</a>
                    @endif
                </div>

                <!-- Role filter -->
                <div class="sm:col-span-4">
                    <select name="role" onchange="this.form.submit()" class="gz-input text-xs w-full py-2">
                        <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All Roles</option>
                        <option value="employees" {{ $role === 'employees' ? 'selected' : '' }}>Employees Only (Admin / Manager / Staff)</option>
                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="manager" {{ $role === 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="staff" {{ $role === 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="player" {{ $role === 'player' ? 'selected' : '' }}>Player</option>
                    </select>
                </div>

                <!-- Status filter -->
                <div class="sm:col-span-3 flex gap-2">
                    <select name="status" onchange="this.form.submit()" class="gz-input text-xs w-full py-2">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active Only</option>
                        <option value="deactivated" {{ $status === 'deactivated' ? 'selected' : '' }}>Deactivated Only</option>
                    </select>

                    @if($role !== 'all' || $status !== 'all' || $search !== '')
                        <a href="{{ route('admin.users.index') }}" class="gz-btn-outline gz-btn-sm shrink-0 flex items-center" title="Reset filters">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="gz-panel overflow-hidden">
            @if($users->isEmpty())
                <div class="p-10 text-center text-sm" style="color: var(--gz-muted);">
                    No user accounts matched your current filters.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="gz-table">
                        <thead>
                            <tr>
                                <th>Name &amp; Email</th>
                                <th>Role</th>
                                <th>Account Status</th>
                                <th>Joined Date</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                                @php
                                    $isSelf = $u->id === Auth::id();
                                    $isActive = (bool) ($u->is_active ?? true);
                                    $roleLower = strtolower($u->role ?: 'player');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="font-bold text-sm flex items-center gap-2" style="color: var(--gz-ink);">
                                            <span>{{ $u->name ?: $u->email }}</span>
                                            @if($isSelf)
                                                <span class="gz-badge-outline text-[10px] px-2 py-0.5 font-mono">YOU</span>
                                            @endif
                                        </div>
                                        <div class="text-xs font-mono" style="color: var(--gz-muted);">
                                            {{ $u->email }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($isSelf)
                                            <span class="inline-block text-[10px] font-mono font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full" style="background: rgba(239, 68, 68, 0.14); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.35);">
                                                {{ strtoupper($roleLower) }}
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.role', $u) }}" class="inline-flex items-center">
                                                @csrf
                                                @method('PATCH')
                                                <select
                                                    name="role"
                                                    onchange="this.form.submit()"
                                                    class="gz-input text-xs py-1 px-2.5 font-semibold"
                                                    style="min-width: 115px;"
                                                >
                                                    <option value="admin" {{ $roleLower === 'admin' ? 'selected' : '' }}>Admin</option>
                                                    <option value="manager" {{ $roleLower === 'manager' ? 'selected' : '' }}>Manager</option>
                                                    <option value="staff" {{ $roleLower === 'staff' ? 'selected' : '' }}>Staff</option>
                                                    <option value="player" {{ $roleLower === 'player' ? 'selected' : '' }}>Player</option>
                                                </select>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">
                                        @if($isActive)
                                            <span class="gz-badge gz-badge-success text-[10px] uppercase font-mono font-bold">
                                                Active
                                            </span>
                                        @else
                                            <span class="gz-badge gz-badge-danger text-[10px] uppercase font-mono font-bold">
                                                Deactivated
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-xs" style="color: var(--gz-muted);">
                                        {{ $u->created_at?->format('M d, Y') ?? 'N/A' }}
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        @if($isSelf)
                                            <span class="text-xs italic" style="color: var(--gz-muted);">Current Session</span>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.status', $u) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                @if($isActive)
                                                    <button
                                                        type="submit"
                                                        class="gz-btn-outline gz-btn-sm text-xs font-semibold"
                                                        style="color: var(--gz-danger); border-color: rgba(239, 68, 68, 0.4);"
                                                        onclick="return confirm('Deactivate account for {{ addslashes($u->name ?: $u->email) }}? They will not be able to sign in until reactivated.')"
                                                    >
                                                        Deactivate
                                                    </button>
                                                @else
                                                    <button
                                                        type="submit"
                                                        class="gz-btn-primary gz-btn-sm text-xs font-semibold"
                                                    >
                                                        Activate
                                                    </button>
                                                @endif
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                    <div class="p-4 border-t" style="border-color: var(--gz-border);">
                        {{ $users->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- Add Staff / Manager Modal -->
        <div
            x-show="showAddModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px);"
            @keydown.escape.window="showAddModal = false"
        >
            <div
                @click.outside="showAddModal = false"
                class="gz-panel w-full max-w-lg overflow-hidden shadow-2xl border"
                style="background: var(--gz-surface); border-color: var(--gz-border);"
            >
                <div class="p-5 border-b flex items-center justify-between" style="border-color: var(--gz-border);">
                    <div>
                        <span class="gz-eyebrow">New Employee / Account</span>
                        <h2 class="gz-font-display font-bold text-lg mt-0.5">Add Staff or Manager Account</h2>
                    </div>
                    <button
                        type="button"
                        @click="showAddModal = false"
                        class="w-8 h-8 rounded-full flex items-center justify-center border hover:opacity-80"
                        style="border-color: var(--gz-border); color: var(--gz-ink);"
                    >
                        ×
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.users.store') }}" class="p-5 space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="first_name" class="gz-label">First Name *</label>
                            <input
                                id="first_name"
                                type="text"
                                name="first_name"
                                value="{{ old('first_name') }}"
                                required
                                class="gz-input text-xs w-full"
                                placeholder="e.g. Juan"
                            >
                            @error('first_name')
                                <p class="gz-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="middle_name" class="gz-label">Middle Name</label>
                            <input
                                id="middle_name"
                                type="text"
                                name="middle_name"
                                value="{{ old('middle_name') }}"
                                class="gz-input text-xs w-full"
                                placeholder="Optional"
                            >
                        </div>

                        <div>
                            <label for="last_name" class="gz-label">Last Name *</label>
                            <input
                                id="last_name"
                                type="text"
                                name="last_name"
                                value="{{ old('last_name') }}"
                                required
                                class="gz-input text-xs w-full"
                                placeholder="e.g. Dela Cruz"
                            >
                            @error('last_name')
                                <p class="gz-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="email" class="gz-label">Email Address *</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                class="gz-input text-xs w-full"
                                placeholder="staff2@gaoshou.ph"
                            >
                            @error('email')
                                <p class="gz-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="role" class="gz-label">Account Role *</label>
                            <select id="role" name="role" required class="gz-input text-xs w-full">
                                <option value="staff" {{ old('role', 'staff') === 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }}>Manager</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="player" {{ old('role') === 'player' ? 'selected' : '' }}>Player</option>
                            </select>
                            @error('role')
                                <p class="gz-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="password" class="gz-label">Temporary Password *</label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                minlength="6"
                                class="gz-input text-xs w-full"
                                placeholder="Min. 6 characters"
                            >
                            @error('password')
                                <p class="gz-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="gz-label">Confirm Password *</label>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                minlength="6"
                                class="gz-input text-xs w-full"
                                placeholder="Re-enter password"
                            >
                        </div>
                    </div>

                    <div class="pt-3 border-t flex items-center justify-end gap-2" style="border-color: var(--gz-border);">
                        <button type="button" @click="showAddModal = false" class="gz-btn-outline gz-btn-sm text-xs">
                            Cancel
                        </button>
                        <button type="submit" class="gz-btn-primary gz-btn-sm text-xs">
                            Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
