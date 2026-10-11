@php
    $siteSettings = $siteSettings ?? \App\Models\SiteSettings::first();
    $homeUrl = match(true) {
        Auth::check() && (Auth::user()->isAdmin() || Auth::user()->hasRole('manager')) => route('admin.dashboard'),
        Auth::check() && Auth::user()->hasRole('staff') => route('staff.today'),
        default => url('/'),
    };
@endphp

<style>
    /* Theme toggle icon swap (works on every page that includes this nav) */
    .theme-icon-sun { display: none; }
    html.night-mode .theme-icon-sun,
    html.dark .theme-icon-sun { display: block; }
    html.night-mode .theme-icon-moon,
    html.dark .theme-icon-moon { display: none; }

    .theme-toggle-btn {
        width: 36px;
        height: 36px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        border: 1.5px solid var(--gz-border);
        background: var(--gz-surface, transparent);
        color: var(--gz-ink);
        cursor: pointer;
        transition: border-color 0.15s ease, transform 0.15s ease;
        flex-shrink: 0;
    }
    .theme-toggle-btn:hover { border-color: var(--pop, #E5A823); transform: rotate(-12deg); }
</style>

<nav x-data="{ mobileOpen: false, profileOpen: false }" class="border-b relative" style="border-color: var(--gz-border);">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex justify-between h-16 items-center gap-2">
            {{-- Left: logo --}}
            <a href="{{ $homeUrl }}" class="flex items-center shrink-0">
                @php
                    $logoPath = null;
                    if ($siteSettings && $siteSettings->logo && file_exists(public_path('storage/' . $siteSettings->logo))) {
                        $logoPath = asset('storage/' . $siteSettings->logo);
                    } elseif (file_exists(public_path('images/logo.png'))) {
                        $logoPath = asset('images/logo.png');
                    } elseif (file_exists(public_path('images/kymnet-logo.png'))) {
                        $logoPath = asset('images/kymnet-logo.png');
                    }
                @endphp
                @if($logoPath)
                    <img src="{{ $logoPath }}" alt="{{ $siteSettings->system_name ?? 'Gaoshou Pickleball' }}" class="h-10 sm:h-12 w-auto max-h-12 max-w-[200px] sm:max-w-[240px] object-contain shrink-0">
                @else
                    <div class="flex items-center gap-2.5">
                        <div class="pixel-mark shrink-0" aria-hidden="true" style="width:34px; height:34px; background:#12150F; border:1px solid var(--gz-border); border-radius:10px; display:grid; grid-template-columns:repeat(8,1fr); grid-template-rows:repeat(8,1fr); padding:6px;" id="nav-seal"></div>
                        <div class="flex flex-col">
                            <span class="gz-font-display font-bold text-base tracking-tight leading-none" style="color: var(--gz-ink);">{{ $siteSettings->system_name ?? 'Gaoshou Pickleball' }}</span>
                            <span class="text-[9px] uppercase tracking-wider font-semibold opacity-70 mt-0.5 leading-none hidden sm:block" style="color: var(--gz-muted);">Pickleball Arena</span>
                        </div>
                    </div>
                @endif
            </a>

            {{-- Center: navigation links (desktop) --}}
            <div class="hidden sm:flex items-center gap-8 flex-1 justify-center">
                <div class="flex gap-6 text-sm font-semibold" style="color: var(--gz-muted);">
                    @auth
                        @if(Auth::user()->isAdmin() || Auth::user()->hasRole('manager'))
                            <a href="{{ route('admin.dashboard') }}" class="nav-link" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                                Overview
                            </a>
                            <a href="{{ route('admin.courts.index') }}" class="nav-link" @if(request()->routeIs('admin.courts.*')) aria-current="page" @endif>
                                Courts
                            </a>
                            <a href="{{ route('admin.events.index') }}" class="nav-link" @if(request()->routeIs('admin.events.*')) aria-current="page" @endif>
                                Events
                            </a>
                            <a href="{{ route('admin.finance') }}" class="nav-link" @if(request()->routeIs('admin.finance')) aria-current="page" @endif>
                                Financials
                            </a>
                            <a href="{{ route('admin.customization.index') }}" class="nav-link" @if(request()->routeIs('admin.customization.*')) aria-current="page" @endif>
                                Customization
                            </a>
                            <a href="{{ route('admin.activity-logs.index') }}" class="nav-link" @if(request()->routeIs('admin.activity-logs.*')) aria-current="page" @endif>
                                Activity Logs
                            </a>
                        @elseif(Auth::user()->hasRole('staff'))
                            <a href="{{ route('staff.today') }}" class="nav-link" @if(request()->routeIs('staff.today')) aria-current="page" @endif>
                                Today's Schedule
                            </a>
                            <a href="{{ route('staff.walkin.create') }}" class="nav-link" @if(request()->routeIs('staff.walkin.*')) aria-current="page" @endif>
                                Walk-In Booking
                            </a>
                        @else
                            <a href="{{ url('/') }}" class="nav-link" @if(request()->is('/')) aria-current="page" @endif>
                                Home
                            </a>
                            <a href="{{ route('booking') }}" class="nav-link" @if(request()->routeIs('booking')) aria-current="page" @endif>
                                Book Courts
                            </a>
                            <a href="{{ route('open-play.index') }}" class="nav-link" @if(request()->routeIs('open-play.*')) aria-current="page" @endif>
                                Open Play
                            </a>
                            <a href="{{ route('bookings.index') }}" class="nav-link" @if(request()->routeIs('bookings.index')) aria-current="page" @endif>
                                Booking History
                            </a>
                        @endif
                    @else
                        <a href="{{ url('/') }}" class="nav-link" @if(request()->is('/')) aria-current="page" @endif>
                            Home
                        </a>
                        <a href="{{ route('booking') }}" class="nav-link" @if(request()->routeIs('booking')) aria-current="page" @endif>
                            Book Courts
                        </a>
                        <a href="{{ route('open-play.index') }}" class="nav-link" @if(request()->routeIs('open-play.*')) aria-current="page" @endif>
                            Open Play
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Right: theme toggle + account/auth buttons (desktop) --}}
            <div class="hidden sm:flex items-center gap-3 relative">
                <button type="button" onclick="toggleTheme()" class="theme-toggle-btn" aria-label="Toggle night mode" title="Toggle night mode">
                    <svg class="theme-icon-moon w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                    <svg class="theme-icon-sun w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/></svg>
                </button>

                @auth
                    @php
                        $userShortName = Auth::user()->first_name
                            ?: (\Illuminate\Support\Str::of(Auth::user()->name ?? 'Account')->before(' ')->value() ?: 'Account');
                    @endphp
                    <button @click="profileOpen = !profileOpen" @click.outside="profileOpen = false"
                            :aria-expanded="profileOpen.toString()"
                            class="gz-btn-outline gz-btn-sm flex items-center gap-2"
                            title="{{ Auth::user()->name }}">
                        <span class="max-w-[110px] truncate">{{ $userShortName }}</span>
                        <svg class="w-4 h-4 transition-transform duration-150 shrink-0" :class="{'rotate-180': profileOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="profileOpen" x-cloak
                         class="absolute right-0 top-full mt-2 w-56 gz-dropdown z-50 overflow-hidden"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1">

                        <div class="px-4 py-3 border-b border-[color:var(--gz-border)] bg-[color:var(--gz-bg)]/40">
                            <p class="text-[10px] uppercase font-bold tracking-wider text-[color:var(--gz-muted)]">Signed in as</p>
                            <p class="text-sm font-bold truncate text-[color:var(--gz-ink)]" title="{{ Auth::user()->name }}">{{ $userShortName }}</p>
                            <p class="text-xs text-[color:var(--gz-muted)] truncate" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</p>
                            <div class="mt-1.5">
                                <span class="gz-badge-outline text-[10px] px-2 py-0.5 uppercase tracking-wider font-mono font-bold">
                                    {{ Auth::user()->isAdmin() ? 'ADMIN' : (Auth::user()->hasRole('manager') ? 'MANAGER' : (Auth::user()->hasRole('staff') ? 'STAFF' : 'PLAYER')) }}
                                </span>
                            </div>
                        </div>

                        <div class="py-1">
                            @if(Auth::user()->isAdmin() || Auth::user()->hasRole('manager'))
                                <a href="{{ route('admin.dashboard') }}" class="gz-dropdown-item">Admin Overview</a>
                                <a href="{{ route('admin.courts.index') }}" class="gz-dropdown-item">Courts Inventory</a>
                                <a href="{{ route('admin.events.index') }}" class="gz-dropdown-item">Events Management</a>
                                <a href="{{ route('admin.finance') }}" class="gz-dropdown-item">Financial Reports</a>
                                <a href="{{ route('admin.customization.index') }}" class="gz-dropdown-item">Customization</a>
                                <a href="{{ route('admin.activity-logs.index') }}" class="gz-dropdown-item">Activity Logs</a>
                            @elseif(Auth::user()->hasRole('staff'))
                                <a href="{{ route('staff.today') }}" class="gz-dropdown-item">Today's Schedule</a>
                                <a href="{{ route('staff.walkin.create') }}" class="gz-dropdown-item">Walk-In Booking</a>
                            @else
                                <a href="{{ route('booking') }}" class="gz-dropdown-item">Book Courts</a>
                                <a href="{{ route('bookings.index') }}" class="gz-dropdown-item">Courts Booked</a>
                                <a href="{{ route('open-play.host.index') }}" class="gz-dropdown-item">My Hosted Open Play</a>
                            @endif

                            <div class="my-1 border-t border-[color:var(--gz-border)]"></div>

                            <a href="{{ route('profile.edit') }}" class="gz-dropdown-item">
                                Profile Settings
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left gz-dropdown-item font-semibold" style="color: var(--gz-danger);">
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="gz-btn-outline gz-btn-sm" @if(request()->routeIs('login')) aria-current="page" @endif>
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="gz-btn-primary gz-btn-sm" @if(request()->routeIs('register')) aria-current="page" @endif>
                        Register
                    </a>
                @endauth
            </div>

            {{-- Mobile: theme toggle + menu button --}}
            <div class="sm:hidden flex items-center gap-1.5 shrink-0">
                <button type="button" onclick="toggleTheme()" class="theme-toggle-btn !w-9 !h-9" aria-label="Toggle night mode" title="Toggle theme">
                    <svg class="theme-icon-moon w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                    <svg class="theme-icon-sun w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/></svg>
                </button>
                <button @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()" aria-controls="mobile-nav-genz"
                        class="w-9 h-9 rounded-full border border-[color:var(--gz-border)] hover:bg-black/5 dark:hover:bg-white/5 transition flex items-center justify-center shrink-0"
                        style="color: var(--gz-ink); background: var(--gz-surface);" aria-label="Toggle navigation menu">
                    <svg x-show="!mobileOpen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg x-show="mobileOpen" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-nav-genz" x-show="mobileOpen" x-cloak
         class="sm:hidden gz-panel mx-4 mb-4 overflow-hidden rounded-2xl shadow-xl border border-[color:var(--gz-border)]"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2">
        @auth
            <div class="px-4 py-3 border-b border-[color:var(--gz-border)] bg-[color:var(--gz-bg)]/40">
                <p class="text-[10px] uppercase font-bold tracking-wider text-[color:var(--gz-muted)]">Signed in as</p>
                <p class="text-sm font-bold truncate text-[color:var(--gz-ink)]" title="{{ Auth::user()->name }}">
                    {{ Auth::user()->first_name ?: (\Illuminate\Support\Str::of(Auth::user()->name ?? 'Account')->before(' ')->value() ?: 'Account') }}
                </p>
                <span class="gz-badge-outline text-[10px] px-2 py-0.5 uppercase tracking-wider font-mono font-bold mt-1 inline-block">
                    {{ Auth::user()->isAdmin() ? 'ADMIN' : (Auth::user()->hasRole('manager') ? 'MANAGER' : (Auth::user()->hasRole('staff') ? 'STAFF' : 'PLAYER')) }}
                </span>
            </div>
            @if(Auth::user()->isAdmin() || Auth::user()->hasRole('manager'))
                <a href="{{ route('admin.dashboard') }}" class="gz-dropdown-item font-bold">Admin Dashboard</a>
                <a href="{{ route('admin.courts.index') }}" class="gz-dropdown-item">Courts Inventory</a>
                <a href="{{ route('admin.events.index') }}" class="gz-dropdown-item">Events Management</a>
                <a href="{{ route('admin.finance') }}" class="gz-dropdown-item">Financial Reports</a>
                <a href="{{ route('admin.customization.index') }}" class="gz-dropdown-item">Customization</a>
                <a href="{{ route('admin.activity-logs.index') }}" class="gz-dropdown-item">Activity Logs</a>
            @elseif(Auth::user()->hasRole('staff'))
                <a href="{{ route('staff.today') }}" class="gz-dropdown-item">Today's Schedule</a>
                <a href="{{ route('staff.walkin.create') }}" class="gz-dropdown-item">Walk-In Booking</a>
            @else
                <a href="{{ url('/') }}" class="gz-dropdown-item">Home</a>
                <a href="{{ route('booking') }}" class="gz-dropdown-item">Book Courts</a>
                <a href="{{ route('open-play.index') }}" class="gz-dropdown-item">Open Play & Tournaments</a>
                <a href="{{ route('open-play.host.index') }}" class="gz-dropdown-item">My Hosted Open Play</a>
                <a href="{{ route('bookings.index') }}" class="gz-dropdown-item">Booking History</a>
            @endif
            <div class="my-1 border-t border-[color:var(--gz-border)]"></div>
            <a href="{{ route('profile.edit') }}" class="gz-dropdown-item">Profile Settings</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left gz-dropdown-item font-semibold" style="color: var(--gz-danger);">Log Out</button>
            </form>
        @else
            <a href="{{ url('/') }}" class="gz-dropdown-item">Home</a>
            <a href="{{ route('booking') }}" class="gz-dropdown-item">Book Courts</a>
            <a href="{{ route('open-play.index') }}" class="gz-dropdown-item">Open Play & Tournaments</a>
            <div class="p-3 border-t border-[color:var(--gz-border)] flex items-center gap-2">
                <a href="{{ route('login') }}" class="gz-btn-outline gz-btn-sm flex-1 text-center justify-center">Login</a>
                <a href="{{ route('register') }}" class="gz-btn-primary gz-btn-sm flex-1 text-center justify-center">Register</a>
            </div>
        @endauth
    </div>
</nav>

<script>
    // Theme toggle lives here so it works on every page that includes the nav.
    window.toggleTheme = function () {
        const isNight = document.documentElement.classList.toggle('night-mode');
        document.documentElement.classList.toggle('dark', isNight);
        if (document.body) {
            document.body.classList.toggle('night-mode', isNight);
            document.body.classList.toggle('dark', isNight);
        }
        try {
            localStorage.setItem('gaoshou_theme', isNight ? 'night' : 'day');
            localStorage.setItem('kymnet_theme', isNight ? 'night' : 'day');
        } catch (e) {}
    };

    try {
        if (localStorage.getItem('gaoshou_theme') === 'night' || localStorage.getItem('kymnet_theme') === 'night') {
            document.documentElement.classList.add('night-mode', 'dark');
            document.body?.classList.add('night-mode', 'dark');
        }
    } catch (e) {}

    if (typeof window.renderPixelGrid === 'undefined') {
        window.renderPixelGrid = function (id, rows, colorMap) {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerHTML = '';
            rows.forEach(row => {
                [...row].forEach(ch => {
                    const cell = document.createElement('div');
                    cell.style.background = colorMap[ch] || 'transparent';
                    cell.style.width = '100%';
                    cell.style.height = '100%';
                    el.appendChild(cell);
                });
            });
        };
    }
    window.renderPixelGrid('nav-seal', [
        "........", ".W....W.", "..WWWW..", ".W.WW.W.",
        ".W.WW.W.", "..WWWW..", ".W....W.", "........"
    ], { '.': 'transparent', 'W': '#FCFBF7' });
</script>