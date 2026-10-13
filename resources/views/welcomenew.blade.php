<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Models\SiteSettings::first()?->system_name ?? 'Gaoshou Pickleball' }} - Book Your Court, Rally with Ease</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --bg: #F4F1E9;
            --surface: #FCFBF7;
            --ink: #12150F;
            --muted: #565A4E;
            --border: #E4E0D4;
            --pop: #E5A823;
            --pop-dark: #A67512;
        }

        body {
            background: var(--bg);
            color: var(--ink);
            font-family: 'Inter', sans-serif;
        }
        .font-display {
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.02em;
        }

        .grain {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: 0.035;
            z-index: 0;
            background-image: radial-gradient(circle, var(--ink) 1px, transparent 1px);
            background-size: 3px 3px;
        }

        .skip-link {
            position: absolute;
            left: -9999px;
            top: 0;
            background: var(--ink);
            color: var(--surface);
            padding: 12px 20px;
            border-radius: 0 0 12px 0;
            font-weight: 700;
            font-size: 14px;
            z-index: 100;
        }
        .skip-link:focus {
            left: 0;
        }

        a:focus-visible,
        button:focus-visible {
            outline: 3px solid var(--pop-dark);
            outline-offset: 3px;
            border-radius: 8px;
        }

        @media (prefers-reduced-motion: reduce) {
            .btn-primary,
            .btn-outline,
            .surface-card,
            .site-header {
                transition: none !important;
            }
            .btn-primary:hover,
            .btn-primary:active,
            .surface-card:hover {
                transform: none !important;
            }
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--bg);
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .site-header.is-scrolled {
            border-color: var(--border);
            box-shadow: 0 8px 24px -16px rgba(18, 21, 15, 0.25);
        }

        .btn-primary {
            background: var(--pop);
            color: var(--ink);
            border-radius: 999px;
            padding: 16px 30px;
            font-weight: 700;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 4px 0 var(--pop-dark);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 0 var(--pop-dark);
        }
        .btn-primary:active {
            transform: translateY(2px);
            box-shadow: 0 1px 0 var(--pop-dark);
        }
        .btn-outline {
            border: 2px solid var(--ink);
            color: var(--ink);
            border-radius: 999px;
            padding: 14px 26px;
            font-weight: 700;
            font-size: 15px;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .btn-outline:hover {
            background: var(--ink);
            color: var(--surface);
        }

        .surface-card {
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: 28px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .surface-card:hover {
            transform: translateY(-4px) rotate(-0.5deg);
            border-color: var(--pop);
            box-shadow: 0 16px 32px -12px rgba(18, 21, 15, 0.15);
        }
        .surface-card:nth-child(2):hover {
            transform: translateY(-4px) rotate(0.5deg);
        }

        .icon-badge {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: rgba(229, 168, 35, 0.12);
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            grid-template-rows: repeat(8, 1fr);
            padding: 11px;
            flex-shrink: 0;
        }
        .icon-badge div { width: 100%; height: 100%; }

        .pixel-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: var(--ink);
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            grid-template-rows: repeat(8, 1fr);
            padding: 7px;
            flex-shrink: 0;
        }
        .pixel-mark div { width: 100%; height: 100%; }

        .court-frame {
            aspect-ratio: 4 / 3;
            background: var(--ink);
            border-radius: 32px;
            position: relative;
            overflow: hidden;
        }
        .court-line { position: absolute; background: rgba(244, 241, 233, 0.9); border-radius: 3px; }
        .court-net {
            position: absolute; left: 50%; top: 0; bottom: 0; width: 3px;
            background: repeating-linear-gradient(0deg, rgba(244,241,233,0.55), rgba(244,241,233,0.55) 6px, transparent 6px, transparent 12px);
            transform: translateX(-50%);
        }
        .court-dot { position: absolute; width: 14px; height: 14px; border-radius: 50%; }

        .footer-link {
            color: rgba(252, 251, 247, 0.65);
            transition: color 0.15s ease;
            text-decoration: none;
        }
        .footer-link:hover { color: var(--pop); text-decoration: underline; }

        .nav-link {
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .nav-link:hover { color: var(--ink); }
        .nav-link[aria-current="page"] {
            color: var(--ink);
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased relative">
    <div class="grain"></div>

    <a href="#main-content" class="skip-link">Skip to main content</a>

    <header
        class="site-header relative"
        x-data="{ mobileNavOpen: false, scrolled: false }"
        @scroll.window="scrolled = window.scrollY > 8"
        :class="{ 'is-scrolled': scrolled }"
    >
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="pixel-mark" id="brand-mark" aria-hidden="true"></div>
                <span class="font-display font-bold text-lg">{{ \App\Models\SiteSettings::first()?->system_name ?? 'Gaoshou Pickleball' }}</span>
            </div>

            <nav class="hidden sm:flex items-center gap-8 text-sm font-semibold" style="color: var(--muted);" aria-label="Primary">
                <a href="#" class="nav-link" aria-current="page">Home</a>
                <a href="#" class="nav-link">Courts</a>
                <a href="{{ url('/#promos') }}" class="nav-link">Promos</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="btn-outline text-sm hidden sm:inline-flex">Login</a>
                <a href="{{ route('register') }}" class="btn-primary text-sm hidden sm:inline-flex">Register</a>

                <button
                    type="button"
                    class="sm:hidden btn-outline text-sm"
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
            style="border-color: var(--border); color: var(--muted);"
            aria-label="Primary, mobile"
        >
            <a href="#" class="nav-link" aria-current="page">Home</a>
            <a href="#" class="nav-link">Courts</a>
            <a href="{{ url('/#promos') }}" class="nav-link">Promos</a>
            <a href="{{ route('login') }}" class="btn-outline text-sm text-center">Login</a>
            <a href="{{ route('register') }}" class="btn-primary text-sm justify-center">Register</a>
        </nav>
    </header>

    <main id="main-content">

    <section class="relative z-10 max-w-6xl mx-auto px-6 pt-16 pb-24" aria-label="Introduction">
        <div class="grid md:grid-cols-2 gap-16 items-center">
            <div>
                <h1 class="font-display text-5xl md:text-6xl font-bold leading-[1.05]">
                    Your court era
                    <br>
                    starts <span style="color: var(--pop-dark);">now.</span>
                </h1>
                <p class="mt-6 text-lg max-w-md leading-relaxed" style="color: var(--muted);">
                    No more group chats going back and forth for 45 minutes.
                    See what's open, lock in a time, and go play. That's it.
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-4">
                    <a href="{{ route('register') }}" class="btn-primary">
                        Lock In a Court
                    </a>
                    <a href="#features" class="text-sm font-semibold underline underline-offset-4" style="color: var(--ink);">
                        See how it works
                    </a>
                </div>
            </div>

            <div class="court-frame" role="img" aria-hidden="true">
                <div class="court-line" style="top:8%; left:8%; right:8%; height:4px;"></div>
                <div class="court-line" style="bottom:8%; left:8%; right:8%; height:4px;"></div>
                <div class="court-line" style="top:8%; bottom:8%; left:8%; width:4px;"></div>
                <div class="court-line" style="top:8%; bottom:8%; right:8%; width:4px;"></div>
                <div class="court-net"></div>
                <div class="court-dot" style="background: var(--pop); left: 30%; top: 40%;"></div>
                <div class="court-dot" style="background: var(--surface); left: 65%; top: 60%;"></div>
            </div>
        </div>
    </section>

    <section id="features" class="relative z-10 max-w-5xl mx-auto px-6 pb-24" aria-labelledby="features-heading">
        <h2 id="features-heading" class="font-display text-3xl md:text-4xl font-bold mb-2">Built different.</h2>
        <p class="text-base mb-10" style="color: var(--muted);">Everything you need, none of the hassle.</p>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="surface-card p-7">
                <div class="icon-badge mb-6" id="icon-availability" aria-hidden="true"></div>
                <h3 class="font-display font-bold text-lg mb-2">See what's open, live</h3>
                <p class="text-base leading-relaxed" style="color: var(--muted);">
                    Real-time court schedules. No calling, no guessing, no "is it free right now?" texts.
                </p>
            </div>
            <div class="surface-card p-7">
                <div class="icon-badge mb-6" id="icon-booking" aria-hidden="true"></div>
                <h3 class="font-display font-bold text-lg mb-2">Book in one tap</h3>
                <p class="text-base leading-relaxed" style="color: var(--muted);">
                    Pick a slot, see the total, pay. Under a minute, start to finish.
                </p>
            </div>
            <div class="surface-card p-7">
                <div class="icon-badge mb-6" id="icon-preferences" aria-hidden="true"></div>
                <h3 class="font-display font-bold text-lg mb-2">Pick your vibe</h3>
                <p class="text-base leading-relaxed" style="color: var(--muted);">
                    Indoor, outdoor, covered, or under the lights — filter to what you actually want.
                </p>
            </div>
        </div>
    </section>

    </main>

    <footer class="relative z-10" style="background: var(--ink); color: rgba(255,255,255,0.7);">
        <div class="max-w-6xl mx-auto px-6 py-14 grid md:grid-cols-4 gap-10">
            <div>
                <span class="font-display font-bold text-lg" style="color: var(--surface);">{{ \App\Models\SiteSettings::first()?->business_name ?? 'Gaoshou Pickleball' }}</span>
                <p class="mt-4 text-sm max-w-xs leading-relaxed">
                    Court booking for the Davao pickleball community. Built by players, for players.
                </p>
            </div>
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wide mb-4" style="color: var(--surface);">Explore</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('booking') }}" class="footer-link">Find courts</a></li>
                    <li><a href="#" class="footer-link">Host a tournament</a></li>
                    <li><a href="#" class="footer-link">Find partners</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wide mb-4" style="color: var(--surface);">For users</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="footer-link">Support</a></li>
                    <li><a href="#" class="footer-link">Pricing</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wide mb-4" style="color: var(--surface);">Company</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="footer-link">About us</a></li>
                    <li><a href="#" class="footer-link">Contact</a></li>
                </ul>
            </div>
        </div>
        <div class="max-w-6xl mx-auto px-6 pb-8 text-sm" style="color: rgba(255,255,255,0.4);">
            © {{ date('Y') }} {{ \App\Models\SiteSettings::first()?->business_name ?? 'Gaoshou Pickleball' }}. All rights reserved.
        </div>
    </footer>

    <script>
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

        const glyphs = {
            'brand-mark': [
                "........",".W....W.","..WWWW..",".W.WW.W.",
                ".W.WW.W.","..WWWW..",".W....W.","........"
            ],
            'icon-availability': [
                "........",".G....G.","..GGGG..",".G.GG.G.",
                ".G.GG.G.","..GGGG..",".G....G.","........"
            ],
            'icon-booking': [
                "...G....","..GG....",".GGG....","GGGGGGG.",
                "....GGG.","....GG..","....G...","........"
            ],
            'icon-preferences': [
                "........",".GGGG...","...G....",".GGGGGG.",
                "...G....",".GG.....","...G....","........"
            ]
        };
        renderPixelGrid('brand-mark', glyphs['brand-mark'], { '.': 'transparent', 'W': '#FCFBF7' });
        renderPixelGrid('icon-availability', glyphs['icon-availability'], { '.': 'transparent', 'G': '#E5A823' });
        renderPixelGrid('icon-booking', glyphs['icon-booking'], { '.': 'transparent', 'G': '#E5A823' });
        renderPixelGrid('icon-preferences', glyphs['icon-preferences'], { '.': 'transparent', 'G': '#E5A823' });
    </script>

</body>
</html>