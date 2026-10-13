{{--
    resources/views/components/site-header.blade.php

    Shared header used by every page (welcome, login, register, ...).
    Active nav state is derived from the current route/path instead of
    being hardcoded per page, so no page has to remember to set
    aria-current itself.
--}}
<header
    class="site-header relative"
    x-data="{ mobileNavOpen: false, scrolled: false }"
    @scroll.window="scrolled = window.scrollY > 8"
    :class="{ 'is-scrolled': scrolled }"
>
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="pixel-mark" id="brand-mark" aria-hidden="true"></div>
            <span class="gz-font-display font-bold text-lg">{{ \App\Models\SiteSettings::first()?->system_name ?? 'Gaoshou Pickleball' }}</span>
        </div>

        <nav class="hidden sm:flex items-center gap-8 text-sm font-semibold" style="color: var(--gz-muted);" aria-label="Primary">
            <a href="{{ url('/') }}" class="nav-link" @if(request()->is('/')) aria-current="page" @endif>Home</a>
            <a href="#" class="nav-link">Courts</a>
            <a href="{{ url('/#promos') }}" class="nav-link">Promos</a>
        </nav>

        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}" class="gz-btn-outline text-sm hidden sm:inline-flex" @if(request()->routeIs('login')) aria-current="page" @endif>Login</a>
            <a href="{{ route('register') }}" class="gz-btn-primary text-sm hidden sm:inline-flex" @if(request()->routeIs('register')) aria-current="page" @endif>Register</a>

            <button
                type="button"
                class="sm:hidden gz-btn-outline text-sm"
                @click="mobileNavOpen = !mobileNavOpen"
                :aria-expanded="mobileNavOpen.toString()"
                aria-controls="mobile-nav"
            >
                <span x-show="!mobileNavOpen">Menu</span>
                <span x-show="mobileNavOpen" x-cloak>Close</span>
            </button>
        </div>
    </div>

    <nav
        id="mobile-nav"
        x-show="mobileNavOpen"
        x-cloak
        class="sm:hidden px-6 pb-5 flex flex-col gap-4 text-sm font-semibold border-t"
        style="border-color: var(--gz-border); color: var(--gz-muted);"
        aria-label="Primary, mobile"
    >
        <a href="{{ url('/') }}" class="nav-link" @if(request()->is('/')) aria-current="page" @endif>Home</a>
        <a href="#" class="nav-link">Courts</a>
        <a href="{{ url('/#promos') }}" class="nav-link">Promos</a>
        <a href="{{ route('login') }}" class="gz-btn-outline text-sm text-center" @if(request()->routeIs('login')) aria-current="page" @endif>Login</a>
        <a href="{{ route('register') }}" class="gz-btn-primary text-sm justify-center" @if(request()->routeIs('register')) aria-current="page" @endif>Register</a>
    </nav>
</header>

<script>
    // Owned by the header itself: renders the 8x8 pixel-grid brand mark.
    // Declared as a plain global function (not a module), so any page that
    // includes this component before its own inline <script> can reuse
    // window.renderPixelGrid for its own icons without redefining it.
    if (typeof renderPixelGrid !== 'function') {
        function renderPixelGrid(id, rows, colorMap) {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerHTML = '';
            rows.forEach(row => {
                [...row].forEach(ch => {
                    const cell = document.createElement('div');
                    cell.style.background = colorMap[ch] || 'transparent';
                    el.appendChild(cell);
                });
            });
        }
    }

    renderPixelGrid('brand-mark', [
        "........",".W....W.","..WWWW..",".W.WW.W.",
        ".W.WW.W.","..WWWW..",".W....W.","........"
    ], { '.': 'transparent', 'W': '#FCFBF7' });
</script>