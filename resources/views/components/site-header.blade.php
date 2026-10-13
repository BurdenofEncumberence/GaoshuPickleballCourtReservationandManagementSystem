<header
    class="site-header relative"
    x-data="{ mobileNavOpen: false, scrolled: false }"
    @scroll.window="scrolled = window.scrollY > 8"
    :class="{ 'is-scrolled': scrolled }"
>
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <div class="pixel-mark" id="site-header-mark" aria-hidden="true"></div>
            <span class="gz-font-display font-bold text-lg">{{ \App\Models\SiteSettings::first()?->system_name ?? 'Gaoshou Pickleball' }}</span>
        </a>

        <nav class="hidden sm:flex items-center gap-8 text-sm font-semibold" style="color: var(--gz-muted);" aria-label="Primary">
            <a href="{{ url('/') }}" class="nav-link" @if(request()->is('/')) aria-current="page" @endif>Home</a>
            <a href="{{ route('booking') }}" class="nav-link" @if(request()->routeIs('booking')) aria-current="page" @endif>Courts</a>
            <a href="{{ url('/#promos') }}" class="nav-link">Promos</a>
        </nav>

        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}" class="gz-btn-outline gz-btn-sm hidden sm:inline-flex">Login</a>
            <a href="{{ route('register') }}" class="gz-btn-primary gz-btn-sm hidden sm:inline-flex">Register</a>

            <button
                type="button"
                class="sm:hidden gz-btn-outline gz-btn-sm"
                @click="mobileNavOpen = !mobileNavOpen"
                :aria-expanded="mobileNavOpen.toString()"
                aria-controls="site-header-mobile-nav"
            >
                <span x-show="!mobileNavOpen">Menu</span>
                <span x-show="mobileNavOpen" x-cloak>Close</span>
            </button>
        </div>
    </div>

    <nav
        id="site-header-mobile-nav"
        x-show="mobileNavOpen"
        x-cloak
        class="sm:hidden px-6 pb-5 flex flex-col gap-4 text-sm font-semibold border-t"
        style="border-color: var(--gz-border); color: var(--gz-muted);"
        aria-label="Primary, mobile"
    >
        <a href="{{ url('/') }}" class="nav-link" @if(request()->is('/')) aria-current="page" @endif>Home</a>
        <a href="{{ route('booking') }}" class="nav-link" @if(request()->routeIs('booking')) aria-current="page" @endif>Courts</a>
        <a href="{{ url('/#promos') }}" class="nav-link">Promos</a>
        <a href="{{ route('login') }}" class="gz-btn-outline gz-btn-sm text-center">Login</a>
        <a href="{{ route('register') }}" class="gz-btn-primary gz-btn-sm justify-center">Register</a>
    </nav>
</header>

<script>
    if (typeof window.renderPixelGrid === 'undefined') {
        window.renderPixelGrid = function(id, rows, colorMap) {
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
    window.renderPixelGrid('site-header-mark', [
        "........", ".W....W.", "..WWWW..", ".W.WW.W.",
        ".W.WW.W.", "..WWWW..", ".W....W.", "........"
    ], { '.': 'transparent', 'W': '#FCFBF7' });
</script>