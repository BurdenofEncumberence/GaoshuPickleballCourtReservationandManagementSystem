<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteSettings->system_name ?? 'Gaoshou Pickleball' }} - Book Your Court, Rally with Ease</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        try {
            if (localStorage.getItem('gaoshou_theme') === 'night' || localStorage.getItem('kymnet_theme') === 'night') {
                document.documentElement.classList.add('night-mode');
            }
        } catch (e) {}
    </script>
    <style>
        :root {
            --bg: #F4F1E9;
            --surface: #FCFBF7;
            --ink: #12150F;
            --muted: #565A4E;
            --border: #E4E0D4;
            --pop: #E5A823;
            --pop-dark: #A67512;

            /* Text color on gold buttons: never flips */
            --on-pop: #12150F;
            /* Panels that stay dark in both themes */
            --dark-bg: #12150F;
            --dark-fg: #FCFBF7;

            /* Map the nav partial's --gz-* variables onto this palette so the nav follows the theme */
            --gz-bg: var(--bg);
            --gz-surface: var(--surface);
            --gz-ink: var(--ink);
            --gz-muted: var(--muted);
            --gz-border: var(--border);
        }

        html.night-mode, body.night-mode {
            --bg: #10140E !important;
            --surface: #181E15 !important;
            --ink: #F4F1E9 !important;
            --muted: #8F9685 !important;
            --border: #283324 !important;
            --pop: #E5A823 !important;
            --pop-dark: #A67512 !important;
        }

        body {
            background: var(--bg);
            color: var(--ink);
            font-family: 'Inter', sans-serif;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        body.night-mode {
            background-color: var(--bg) !important;
            color: var(--ink) !important;
        }

        body.night-mode .grain {
            opacity: 0.05;
            background-image: radial-gradient(circle, var(--pop) 1px, transparent 1px);
        }

        body.night-mode #hero-arena {
            box-shadow: 0 0 35px -5px rgba(229, 168, 35, 0.2), 0 20px 40px -15px rgba(0, 0, 0, 0.8);
        }

        /* Dark panels (banner, CTA, footer) stay dark in both themes */
        .dark-panel { background: var(--dark-bg); color: var(--dark-fg); }
        html.night-mode .dark-panel { border: 1px solid var(--border); }

        /* Nav partial components follow the theme */
        .gz-btn-primary { color: var(--on-pop) !important; }
        .gz-btn-outline { color: var(--ink); border-color: var(--ink); }
        .gz-dropdown, .gz-panel {
            background: var(--surface);
            color: var(--ink);
            border-color: var(--border);
        }
        .gz-dropdown-item { color: var(--ink); }
        .gz-dropdown-item:hover { background: var(--bg); }
        .gz-badge-outline { border-color: var(--border); color: var(--ink); }

        /* Hardcoded Tailwind tints become the gold palette at night */
        html.night-mode .bg-emerald-100,
        html.night-mode .bg-green-100,
        html.night-mode .bg-purple-100,
        html.night-mode .bg-amber-100 { background: rgba(229, 168, 35, 0.15) !important; }
        html.night-mode .text-emerald-800,
        html.night-mode .text-green-800,
        html.night-mode .text-purple-800,
        html.night-mode .text-amber-800,
        html.night-mode .text-emerald-600 { color: var(--pop) !important; }

        /* Chinese Fretwork / Huiwen Corner Brackets */
        .huiwen-corner {
            position: absolute;
            width: 18px;
            height: 18px;
            stroke: var(--ink);
            opacity: 0.22;
            fill: none;
            stroke-width: 1.5;
            pointer-events: none;
            transition: stroke 0.2s ease, opacity 0.2s ease;
        }
        .huiwen-tl { top: 8px; left: 8px; }
        .huiwen-tr { top: 8px; right: 8px; transform: scaleX(-1); }
        .huiwen-bl { bottom: 8px; left: 8px; transform: scaleY(-1); }
        .huiwen-br { bottom: 8px; right: 8px; transform: scale(-1); }

        /* Gold Seal Stamp */
        .seal-jade {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid var(--pop-dark);
            color: var(--pop-dark);
            border-radius: 4px;
            padding: 1px 5px;
            font-size: 10px;
            font-weight: 800;
            line-height: 1;
            background: rgba(229, 168, 35, 0.12);
            transform: rotate(-2deg);
            user-select: none;
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
            color: var(--on-pop);
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
        /* Outline button for use on always-dark panels */
        .btn-outline-light {
            border: 2px solid var(--dark-fg);
            color: var(--dark-fg);
            border-radius: 999px;
            font-weight: 700;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .btn-outline-light:hover {
            background: var(--dark-fg);
            color: var(--dark-bg);
        }

        .surface-card {
            position: relative;
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

    <x-loading-screen />

    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div
        x-data="{ show: !localStorage.getItem('kymnet_event_banner_dismissed') }"
        x-show="show"
        x-cloak
        class="relative z-40 dark-panel"
    >
        <div class="max-w-6xl mx-auto px-6 py-2.5 flex items-center justify-between gap-4 text-sm">
            <p class="font-semibold">
                <span style="color: var(--pop);">●</span>
                Grand Opening Tournament — registration opens soon.
                <a href="#promos" class="underline underline-offset-2 ml-1">Learn more →</a>
            </p>
            <button
                type="button"
                @click="show = false; localStorage.setItem('kymnet_event_banner_dismissed', '1')"
                aria-label="Dismiss announcement"
                class="shrink-0 opacity-70 hover:opacity-100"
            >
                ✕
            </button>
        </div>
    </div>

    <div style="background: var(--bg); position: sticky; top: 0; z-index: 50;">
        @include('layouts.navigation')
    </div>

    <main id="main-content">

    <section class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 pt-10 sm:pt-16 pb-16 sm:pb-24" aria-label="Introduction">
        <div class="grid md:grid-cols-2 gap-10 md:gap-16 items-center">
            <div>
                <h1 class="font-display text-4xl sm:text-5xl md:text-6xl font-bold leading-[1.08] sm:leading-[1.05]">
                    Your court era
                    <br>
                    starts <span style="color: var(--pop-dark);">now.</span>
                </h1>
                <p class="mt-4 sm:mt-6 text-base sm:text-lg max-w-md leading-relaxed" style="color: var(--muted);">
                    No more group chats going back and forth for 45 minutes.
                    See what's open, lock in a time, and go play. That's it.
                </p>
                <div class="mt-6 sm:mt-9 flex flex-wrap items-center gap-3 sm:gap-4">
                    @php
                        $reserveUrl = match(true) {
                            Auth::check() && (Auth::user()->isAdmin() || Auth::user()->hasRole('manager')) => route('admin.dashboard'),
                            Auth::check() && Auth::user()->hasRole('staff') => route('staff.today'),
                            default => route('booking'),
                        };
                    @endphp
                    <a href="{{ $reserveUrl }}" class="btn-primary">
                        Reserve a Court
                    </a>
                    <a href="#courts" class="text-sm font-semibold underline underline-offset-4" style="color: var(--ink);">
                        Explore Courts & Rates ↓
                    </a>
                </div>
            </div>

            <!-- Tournament Arena & Regulation Court Showcase -->
            <div class="relative w-full">
                <div id="hero-arena" class="relative rounded-3xl p-5 sm:p-7 border shadow-xl overflow-hidden transition-colors duration-250" style="background: var(--surface); color: var(--ink); border-color: var(--border);">
                    <!-- Subtle Court Glow & Ambient Lighting -->
                    <div class="absolute -top-24 -right-24 w-64 h-64 bg-[#E5A823]/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-[#E5A823]/10 rounded-full blur-3xl pointer-events-none"></div>

                    <!-- Card Header -->
                    <div class="relative z-10 flex items-center justify-between pb-4 border-b gap-2" style="border-color: var(--border);">
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#E5A823] opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#E5A823]"></span>
                            </span>
                            <span class="text-[11px] sm:text-xs font-mono font-bold tracking-wider uppercase" style="color: var(--pop-dark);">Live Court Hub</span>
                        </div>
                        <span class="text-[10px] sm:text-xs font-mono" style="color: var(--muted);">Open Daily: 8 AM – 10 PM</span>
                    </div>

                    <!-- Players Playing Showcase -->
                    <div class="relative z-10 my-5">
                        <div class="relative rounded-2xl p-2 sm:p-3 border shadow-inner transition-colors duration-250" style="background: var(--bg); border-color: var(--border);">
                            <div class="relative w-full aspect-[16/9] rounded-xl overflow-hidden border border-[#E5A823]/40 shadow-md group" style="background: var(--surface);">
                                <img
                                    src="{{ asset('images/players-playing.jpg') }}"
                                    alt="Gaoshou Pickleball players rallying on court"
                                    class="w-full h-full object-cover object-center transition-transform duration-500 group-hover:scale-[1.03]"
                                >
                                <div class="absolute inset-0 bg-gradient-to-t from-black/45 via-transparent to-transparent pointer-events-none"></div>

                                <!-- Official Dimensions Overlay -->
                                <div class="absolute bottom-2 left-1/2 -translate-x-1/2 whitespace-nowrap text-[9px] sm:text-[10px] font-mono bg-white/90 text-[#12150F] border border-[#E5A823]/50 px-3 py-1 rounded-full pointer-events-none shadow-sm font-semibold">
                                    USA Pickleball Official Spec · 13.41m × 6.10m (44' × 20')
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Court Specs & Features Grid -->
                    <div class="relative z-10 grid grid-cols-2 gap-2.5 sm:gap-3 text-xs">
                        <div class="rounded-xl p-3 border transition" style="background: var(--bg); border-color: var(--border);">
                            <div class="text-[10px] uppercase font-bold tracking-wider" style="color: var(--pop-dark);">Regular Courts</div>
                            <div class="font-display font-bold text-sm sm:text-base mt-0.5" style="color: var(--ink);">13.41m × 6.10m</div>
                            <div class="text-[11px] mt-0.5" style="color: var(--muted);">Official regulation format</div>
                        </div>
                        <div class="rounded-xl p-3 border transition" style="background: var(--bg); border-color: var(--border);">
                            <div class="text-[10px] uppercase font-bold tracking-wider" style="color: var(--pop-dark);">Junior Courts</div>
                            <div class="font-display font-bold text-sm sm:text-base mt-0.5" style="color: var(--ink);">10.00m × 4.50m</div>
                            <div class="text-[11px] mt-0.5" style="color: var(--muted);">Youth & training format</div>
                        </div>
                        <div class="rounded-xl p-3 border transition" style="background: var(--bg); border-color: var(--border);">
                            <div class="text-[10px] uppercase font-bold tracking-wider" style="color: var(--muted);">Playing Surface</div>
                            <div class="font-semibold mt-0.5 text-[11px] sm:text-xs" style="color: var(--ink);">Pro Cushion-Flex™ Acrylic</div>
                            <div class="text-[10px]" style="color: var(--muted);">Shock-absorbing joint protection</div>
                        </div>
                        <div class="rounded-xl p-3 border transition" style="background: var(--bg); border-color: var(--border);">
                            <div class="text-[10px] uppercase font-bold tracking-wider" style="color: var(--muted);">Night Lighting</div>
                            <div class="font-semibold mt-0.5 text-[11px] sm:text-xs" style="color: var(--ink);">1,000-Lux Anti-Glare LEDs</div>
                            <div class="text-[10px]" style="color: var(--muted);">Even ceiling diffusion</div>
                        </div>
                    </div>

                    <!-- Live Fleet Bar & Direct Booking Action -->
                    <div class="relative z-10 mt-4 pt-4 border-t flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="border-color: var(--border);">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-[#E5A823]"></div>
                            <span class="text-xs font-medium" style="color: var(--ink);">
                                {{ $courts->count() > 0 ? $courts->count() . ' Courts Ready for Booking' : 'Courts Open for Booking' }}
                            </span>
                        </div>
                        <a href="{{ $reserveUrl }}" class="inline-flex items-center justify-center gap-2 bg-[#E5A823] text-[#12150F] text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-[#D49B1F] transition shadow-md">
                            <span>Check Live Availability</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Facility Highlights -->
    <section class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 -mt-8 mb-20 sm:mb-24" aria-label="Facility Highlights">
        <div class="relative bg-[color:var(--surface)] border border-[color:var(--border)] rounded-2xl p-4 sm:p-6 shadow-sm grid grid-cols-2 md:grid-cols-4 gap-3.5 sm:gap-6">
            <svg class="huiwen-corner huiwen-tl" viewBox="0 0 20 20"><path d="M1 19V1H19M5 19V5H19"/></svg>
            <svg class="huiwen-corner huiwen-tr" viewBox="0 0 20 20"><path d="M1 19V1H19M5 19V5H19"/></svg>
            <svg class="huiwen-corner huiwen-bl" viewBox="0 0 20 20"><path d="M1 19V1H19M5 19V5H19"/></svg>
            <svg class="huiwen-corner huiwen-br" viewBox="0 0 20 20"><path d="M1 19V1H19M5 19V5H19"/></svg>
            <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(229, 168, 35, 0.15); color: var(--pop-dark);">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                </div>
                <div>
                    <div class="font-display font-bold text-base sm:text-lg md:text-xl leading-none">{{ isset($courts) ? $courts->count() : 4 }} {{ \Illuminate\Support\Str::plural('Court', isset($courts) ? $courts->count() : 4) }}</div>
                    <p class="text-[11px] sm:text-xs mt-1" style="color: var(--muted);">Championship Fleet</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(229, 168, 35, 0.15); color: var(--pop-dark);">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                </div>
                <div>
                    <div class="font-display font-bold text-base sm:text-lg md:text-xl leading-none">1,000 Lux</div>
                    <p class="text-[11px] sm:text-xs mt-1" style="color: var(--muted);">Anti-Glare Night LEDs</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(229, 168, 35, 0.15); color: var(--pop-dark);">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </div>
                <div>
                    <div class="font-display font-bold text-base sm:text-lg md:text-xl leading-none">Cushion-Flex™</div>
                    <p class="text-[11px] sm:text-xs mt-1" style="color: var(--muted);">Joint Safety</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(229, 168, 35, 0.15); color: var(--pop-dark);">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <div class="font-display font-bold text-base sm:text-lg md:text-xl leading-none">Under 60s</div>
                    <p class="text-[11px] sm:text-xs mt-1" style="color: var(--muted);">Instant Booking</p>
                </div>
            </div>
        </div>
    </section>

    @if(isset($events) && $events->isNotEmpty())
    <!-- Active Promos & Special Offers Section -->
    <section id="promos" class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 pb-16 sm:pb-20" aria-labelledby="promos-heading">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full border border-[color:var(--border)] bg-[color:var(--surface)] text-[color:var(--pop-dark)]">
                    Promos & Specials
                </span>
                <h2 id="promos-heading" class="font-display text-2xl md:text-3xl font-bold mt-2">Active Promos & Special Offers</h2>
                <p class="text-sm mt-1" style="color: var(--muted);">Claim exclusive promotional discounts and limited-time savings on court bookings.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($events as $event)
                <div class="surface-card p-5">
                    @if($event->image)
                        <img src="{{ asset('storage/' . $event->image) }}" alt="{{ $event->event_title }}" class="w-full h-40 object-cover rounded-lg mb-4">
                    @else
                        <div class="w-full h-40 rounded-lg mb-4 flex items-center justify-center" style="background: rgba(229,168,35,0.12); color: var(--gz-pop-dark);">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                    @endif

                    <h3 class="font-display font-bold text-lg mb-2">{{ $event->event_title }}</h3>

                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-sm" style="color: var(--muted);">
                            {{ $event->start_date ? \Carbon\Carbon::parse($event->start_date)->format('M d, Y') : '' }} - {{ $event->end_date ? \Carbon\Carbon::parse($event->end_date)->format('M d, Y') : '' }}
                        </span>
                    </div>

                    @if($event->discount)
                        <div class="inline-block px-3 py-1 rounded-full text-sm font-bold mb-3" style="background: rgba(229,168,35,0.18); color: var(--gz-pop-dark);">
                            {{ $event->discount }}% OFF
                        </div>
                    @endif

                    @if($event->details)
                        <p class="text-sm mb-4 line-clamp-2" style="color: var(--muted);">
                            {{ $event->details }}
                        </p>
                    @endif

                    <a href="{{ route('booking') }}" class="btn-primary text-sm w-full justify-center">
                        Book a Court
                    </a>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    @if(isset($openPlaySessions) && $openPlaySessions->isNotEmpty())
    <!-- Open Play & Pickleball Tournaments Showcase -->
    <section id="open-play" class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 pb-16 sm:pb-20" aria-labelledby="openplay-heading">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full border border-[color:var(--border)] bg-[color:var(--surface)] text-[color:var(--pop-dark)]">
                    Communal Pickleball
                </span>
                <h2 id="openplay-heading" class="font-display text-2xl md:text-3xl font-bold mt-2">Open Play & Tournaments</h2>
                <p class="text-sm mt-1" style="color: var(--muted);">
                    Join communal rotation pools and tournaments! Pay only the per-player participation fee—no need to rent a full court.
                </p>
            </div>
            <a href="{{ route('open-play.index') }}" class="text-sm font-bold flex items-center gap-1 hover:underline" style="color: var(--pop-dark);">
                <span>Browse All Open Plays</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($openPlaySessions as $session)
                <div class="surface-card p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border border-[color:var(--border)] bg-[color:var(--surface)]">
                                {{ str_replace('_', ' ', $session->session_type) }}
                            </span>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-950 text-green-800 dark:text-green-300">
                                {{ $session->skill_level }}
                            </span>
                        </div>
                        <h3 class="font-display font-bold text-lg mb-2">{{ $session->title }}</h3>
                        <div class="text-xs space-y-1 mb-4" style="color: var(--muted);">
                            <div class="font-semibold text-black dark:text-white">{{ $session->date->format('l, M j, Y') }}</div>
                            <div>{{ $session->time_window }} ({{ $session->duration_hours }} hrs)</div>
                            <div>Courts: {{ $session->allocated_courts_label ?: 'Dedicated Venue Courts' }}</div>
                        </div>

                        <div class="p-3 rounded-lg mb-4 bg-gray-50 dark:bg-gray-800/50 border border-[color:var(--border)]">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span style="color: var(--muted);">Availability:</span>
                                @if($session->is_full)
                                    <span class="font-bold text-red-600">Sold Out</span>
                                @else
                                    <span class="font-bold text-emerald-600">{{ $session->remaining_slots }} of {{ $session->max_capacity }} slots left</span>
                                @endif
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $session->is_full ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $session->capacity_percent }}%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[color:var(--border)] flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider block" style="color: var(--muted);">Participation Fee</span>
                            <span class="font-mono font-bold text-base">₱{{ number_format($session->price_per_slot, 2) }}</span>
                            <span class="text-[10px]" style="color: var(--muted);">/ slot</span>
                        </div>
                        @if($session->is_full)
                            <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-200 dark:bg-gray-800 text-gray-500">
                                Sold Out
                            </span>
                        @else
                            <a href="{{ route('open-play.show', $session) }}" class="btn-primary text-xs py-2 px-4">
                                Reserve Slot
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Championship Court Fleet Showcase -->
    <section id="courts" class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 pb-20 sm:pb-24" aria-labelledby="courts-heading">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full border border-[color:var(--border)] bg-[color:var(--surface)] text-[color:var(--pop-dark)]">
                    Arena Fleet Showcase
                </span>
                <h2 id="courts-heading" class="font-display text-3xl md:text-4xl font-bold mt-3">
                    Tournament-Grade Courts Built for Rallies.
                </h2>
                <p class="text-base mt-2 max-w-xl" style="color: var(--muted);">
                    Every court is engineered with competition-grade dimensions, true-bounce surfaces, and optimal lighting so your games stay sharp from first serve to match point.
                </p>
            </div>
            <div>
                <a href="{{ $reserveUrl }}" class="btn-primary shrink-0 text-sm py-3 px-6">
                    Check Today's Schedule →
                </a>
            </div>
        </div>

        @php
            $displayCourts = isset($courts) && $courts->isNotEmpty() ? $courts : \App\Models\Court::where('court_status', 'available')->orderBy('id')->get();
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($displayCourts as $court)
                <div class="surface-card p-6 flex flex-col justify-between">
                    <svg class="huiwen-corner huiwen-tl" viewBox="0 0 20 20"><path d="M1 19V1H19M5 19V5H19"/></svg>
                    <svg class="huiwen-corner huiwen-tr" viewBox="0 0 20 20"><path d="M1 19V1H19M5 19V5H19"/></svg>
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-bold uppercase px-2 py-0.5 rounded-full" style="background: rgba(229, 168, 35, 0.15); color: var(--pop-dark);">
                                {{ $court->size ?: 'Regular (13.41m x 6.10m)' }}
                            </span>
                            <span class="text-xs font-semibold flex items-center gap-1.5" style="color: var(--pop-dark);">
                                <span class="w-2 h-2 rounded-full bg-[#E5A823] animate-pulse"></span>
                                Available Daily
                            </span>
                        </div>

                        <div class="h-28 rounded-2xl relative overflow-hidden mb-5 flex items-center justify-center" style="background: #12150F;">
                            <div class="absolute inset-2 border border-dashed border-stone-600 rounded-lg flex items-center justify-center">
                                <div class="w-full h-[2px] bg-[#E5A823]/80"></div>
                                <div class="absolute w-[2px] h-full bg-stone-500"></div>
                            </div>
                            <span class="relative z-10 font-display font-extrabold text-white text-lg tracking-wide uppercase">
                                {{ $court->court_name }}
                            </span>
                        </div>

                        <h3 class="font-display font-bold text-xl mb-1">{{ $court->court_name }}</h3>
                        <p class="text-xs mb-4" style="color: var(--muted);">
                            @if(str_contains(strtolower($court->size ?? ''), 'junior'))
                                Junior training dimensions (10m × 4.5m) layout with high-durability acrylic cushioning.
                            @else
                                USA Pickleball regulation dimensions (13.41m × 6.10m) layout with high-durability acrylic cushioning.
                            @endif
                        </p>

                        <ul class="space-y-2 text-xs mb-6" style="color: var(--ink);">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#E5A823] shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span>Anti-slip acrylic cushion surface</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#E5A823] shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span>1,000-Lux stadium night lighting</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#E5A823] shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span>Heavy-duty tournament mesh net</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#E5A823] shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span>Covered roof & shaded player zone</span>
                            </li>
                        </ul>
                    </div>

                    <div class="pt-4 border-t border-[color:var(--border)]">
                        <div class="flex items-baseline justify-between mb-3">
                            <span class="text-xs" style="color: var(--muted);">Hourly Rate</span>
                            <div>
                                <span class="font-display font-bold text-xl text-[color:var(--ink)]">₱{{ number_format($court->price_per_hour, 2) }}</span>
                                <span class="text-[11px]" style="color: var(--muted);">/ hr</span>
                            </div>
                        </div>
                        <a href="{{ $reserveUrl }}" class="btn-outline w-full text-center text-xs py-2.5 block">
                            Book {{ $court->court_name }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full p-8 text-center surface-card">
                    <p class="font-bold">Courts are being prepared for play.</p>
                    <a href="{{ $reserveUrl }}" class="btn-primary mt-4 inline-block">View Booking Schedule</a>
                </div>
            @endforelse
        </div>

        <div class="mt-8 p-5 rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)] flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg shrink-0" style="background: rgba(229, 168, 35, 0.15); color: var(--pop-dark);">
                    <svg class="w-5 h-5 text-[#A67512]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                </div>
                <div>
                    <h4 class="font-bold text-sm">Organizing a mini tournament or squad friendly?</h4>
                    <p class="text-xs" style="color: var(--muted);">Our booking engine supports selecting multiple courts & multiple consecutive hours in one unified checkout!</p>
                </div>
            </div>
            <a href="{{ $reserveUrl }}" class="font-semibold text-xs underline underline-offset-4 shrink-0" style="color: var(--ink);">
                Reserve multiple courts →
            </a>
        </div>
    </section>

    <!-- Why Play at Gaoshou Pickleball / Amenities -->
    <section id="perks" class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 pb-20 sm:pb-24" aria-labelledby="perks-heading">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full border border-[color:var(--border)] bg-[color:var(--surface)] text-[color:var(--pop-dark)]">
                The Gaoshou Pickleball Experience
            </span>
            <h2 id="perks-heading" class="font-display text-3xl md:text-4xl font-bold mt-3">
                Why Davao Picklers Choose Our Courts
            </h2>
            <p class="text-base mt-2" style="color: var(--muted);">
                From the moment you step foot on the acrylic surface to the final match point, every detail is tailored for maximum performance, comfort, and fun.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="surface-card p-7">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5" style="background: rgba(229, 168, 35, 0.15); color: var(--pop-dark);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </div>
                <h3 class="font-display font-bold text-lg mb-2">Joint-Cushion Surface</h3>
                <p class="text-sm leading-relaxed" style="color: var(--muted);">
                    Multi-layer shock absorbing acrylic cushion protects your knees and ankles during quick dinking lateral movements, reducing fatigue and injury risk.
                </p>
            </div>

            <div class="surface-card p-7">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5" style="background: rgba(229, 168, 35, 0.15); color: var(--pop-dark);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
                <h3 class="font-display font-bold text-lg mb-2">Anti-Glare Night Lighting</h3>
                <p class="text-sm leading-relaxed" style="color: var(--muted);">
                    Stadium-grade 1,000-Lux overhead lighting angled perfectly above the court so you can track high lobs and overhead smashes without blinding glare.
                </p>
            </div>

            <div class="surface-card p-7">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5" style="background: rgba(229, 168, 35, 0.15); color: var(--pop-dark);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" /></svg>
                </div>
                <h3 class="font-display font-bold text-lg mb-2">Covered & Weather-Resistant</h3>
                <p class="text-sm leading-relaxed" style="color: var(--muted);">
                    Rain or intense tropical sun will never cancel your match. Our covered arena canopy keeps courts 100% dry and breezy with natural cross-ventilation.
                </p>
            </div>

        </div>
    </section>

    <!-- 3-Step Booking Process -->
    <section id="how-it-works" class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 pb-20 sm:pb-24" aria-labelledby="steps-heading">
        <div class="bg-[color:var(--surface)] border border-[color:var(--border)] rounded-3xl p-6 sm:p-8 md:p-12">
            <div class="max-w-2xl mb-12">
                <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full border border-[color:var(--border)] bg-[color:var(--bg)] text-[color:var(--pop-dark)]">
                    Frictionless Experience
                </span>
                <h2 id="steps-heading" class="font-display text-3xl md:text-4xl font-bold mt-3">
                    Book Your Court in Under 60 Seconds
                </h2>
                <p class="text-base mt-2" style="color: var(--muted);">
                    No more waiting for message replies or showing up to full courts. Instant confirmation is guaranteed.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <div class="flex flex-col">
                    <div class="w-12 h-12 rounded-2xl font-display font-bold text-xl flex items-center justify-center mb-4 shadow-sm" style="background: var(--pop); color: var(--on-pop);">
                    01
                  </div>
                    <h3 class="font-display font-bold text-lg mb-2">Pick Your Date & Time</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--muted);">
                        Select which court you prefer and see real-time color-coded slot availability. What you see is guaranteed open.
                    </p>
                </div>

                <div class="flex flex-col">
                    <div class="w-12 h-12 rounded-2xl text-white font-display font-bold text-xl flex items-center justify-center mb-4" style="background: var(--dark-bg); border: 1px solid var(--border);">
                        02
                    </div>
                    <h3 class="font-display font-bold text-lg mb-2">Single or Multi-Slot Cart</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--muted);">
                        Book a 1-hour fast match or add multiple consecutive hours and multiple courts for group play in a single checkout.
                    </p>
                </div>

                <div class="flex flex-col">
                    <div class="w-12 h-12 rounded-2xl text-white font-display font-bold text-xl flex items-center justify-center mb-4" style="background: var(--dark-bg); border: 1px solid var(--border);">
                        03
                    </div>
                    <h3 class="font-display font-bold text-lg mb-2">Instant Confirmation & Play</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--muted);">
                        Pay via GCash online or on-site Cash at the front desk with instant OTP verification. Show up and claim your court!
                    </p>
                </div>
            </div>

            <div class="mt-10 pt-8 border-t border-[color:var(--border)] flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 text-xs" style="color: var(--muted);">
                    <span class="flex items-center gap-1.5 font-semibold text-[color:var(--ink)]">
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Zero Booking Surcharge
                    </span>
                    <span>·</span>
                    <span class="flex items-center gap-1.5 font-semibold text-[color:var(--ink)]">
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Instant Verified Digital Receipt
                    </span>
                </div>
                <a href="{{ $reserveUrl }}" class="btn-primary py-3 px-8 text-sm">
                    Start Reservation →
                </a>
            </div>
        </div>
    </section>

    <!-- Player Testimonials -->
    <section class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 pb-20 sm:pb-24" aria-labelledby="testimonials-heading">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full border border-[color:var(--border)] bg-[color:var(--surface)] text-[color:var(--pop-dark)]">
                Player Community
            </span>
            <h2 id="testimonials-heading" class="font-display text-3xl md:text-4xl font-bold mt-3">
                Loved by Passionate Davao Picklers
            </h2>
            <p class="text-base mt-2" style="color: var(--muted);">
                Read why local players, tournament contenders, and weekend squads make Gaoshou Pickleball their home court.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                      <div class="surface-card p-7 flex flex-col justify-between">
                <div>
                    <div class="flex text-amber-500 mb-3 text-sm tracking-widest" aria-label="5 stars">
                        ★★★★★
                    </div>
                    <p class="text-sm leading-relaxed mb-6" style="color: var(--ink);">
                        "Great courts, great lighting, and a very welcoming team. We booked 2 courts for our company round-robin and everything was ready right on the dot."
                    </p>
                </div>
                <div class="flex items-center gap-3 pt-4 border-t border-[color:var(--border)]">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-amber-100 text-amber-800">
                        PR
                    </div>
                    <div>
                        <div class="font-bold text-sm">Paolo Rodriguez</div>
                        <div class="text-xs" style="color: var(--muted);">Company League Captain</div>
                    </div>
                </div>
            </div>

            <div class="surface-card p-7 flex flex-col justify-between">
                <div>
                    <div class="flex text-amber-500 mb-3 text-sm tracking-widest" aria-label="5 stars">
                        ★★★★★
                    </div>
                    <p class="text-sm leading-relaxed mb-6" style="color: var(--ink);">
                        "We used to waste 40 minutes in Messenger groups trying to coordinate court slots. Gaoshou Pickleball's live schedule makes booking effortless. The stadium lights at night are super crisp and anti-glare!"
                    </p>
                </div>
                <div class="flex items-center gap-3 pt-4 border-t border-[color:var(--border)]">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-purple-100 text-purple-800">
                        RG
                    </div>
                    <div>
                        <div class="font-bold text-sm">Rachel Garcia</div>
                        <div class="text-xs" style="color: var(--muted);">Weekend Warrior Squad</div>
                    </div>
                </div>
            </div>

            <div class="surface-card p-7 flex flex-col justify-between">
                <div>
                    <div class="flex text-amber-500 mb-3 text-sm tracking-widest" aria-label="5 stars">
                        ★★★★★
                    </div>
                    <p class="text-sm leading-relaxed mb-6" style="color: var(--ink);">
                        "Top-notch facilities, clean showers, cold hydration water, and very welcoming staff. We booked 2 courts for our company round-robin and everything was ready right on the dot."
                    </p>
                </div>
                <div class="flex items-center gap-3 pt-4 border-t border-[color:var(--border)]">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-amber-100 text-amber-800">
                        PR
                    </div>
                    <div>
                        <div class="font-bold text-sm">Paolo Rodriguez</div>
                        <div class="text-xs" style="color: var(--muted);">Company League Captain</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 pb-20 sm:pb-24" aria-labelledby="faq-heading" x-data="{ activeFaq: null }">
        <div class="text-center mb-12">
            <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full border border-[color:var(--border)] bg-[color:var(--surface)] text-[color:var(--pop-dark)]">
                Got Questions?
            </span>
            <h2 id="faq-heading" class="font-display text-3xl md:text-4xl font-bold mt-3">
                Frequently Asked Questions
            </h2>
            <p class="text-base mt-2" style="color: var(--muted);">
                Everything you need to know before booking your court reservation.
            </p>
        </div>

        <div class="space-y-3">
            <div class="surface-card overflow-hidden">
                <button type="button" @click="activeFaq = activeFaq === 1 ? null : 1" class="w-full p-5 text-left flex items-center justify-between font-bold text-base">
                    <span>Do I need to bring my own paddles and balls?</span>
                    <span class="text-xl transition-transform" :class="{'rotate-45': activeFaq === 1}">+</span>
                </button>
                <div x-show="activeFaq === 1" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    You are welcome to bring your own gear! If you don't have paddles yet, we offer tournament-grade carbon fiber paddle rentals and USAPA approved balls at our reception counter for a nominal fee.
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <button type="button" @click="activeFaq = activeFaq === 2 ? null : 2" class="w-full p-5 text-left flex items-center justify-between font-bold text-base">
                    <span>How many players can play on one reserved court?</span>
                    <span class="text-xl transition-transform" :class="{'rotate-45': activeFaq === 2}">+</span>
                </button>
                <div x-show="activeFaq === 2" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Each reservation covers the entire court! You can play singles (2 players), standard doubles (4 players), or bring up to 6 players to rotate and play king-of-the-court rallies at no extra charge.
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <button type="button" @click="activeFaq = activeFaq === 3 ? null : 3" class="w-full p-5 text-left flex items-center justify-between font-bold text-base">
                    <span>Can I book multiple courts or multiple hours at once?</span>
                    <span class="text-xl transition-transform" :class="{'rotate-45': activeFaq === 3}">+</span>
                </button>
                <div x-show="activeFaq === 3" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Yes! Our interactive booking system allows you to select consecutive time slots across the day or reserve multiple courts simultaneously for clubs and mini-tournaments in one single checkout.
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <button type="button" @click="activeFaq = activeFaq === 4 ? null : 4" class="w-full p-5 text-left flex items-center justify-between font-bold text-base">
                    <span>What payment methods are supported?</span>
                    <span class="text-xl transition-transform" :class="{'rotate-45': activeFaq === 4}">+</span>
                </button>
                <div x-show="activeFaq === 4" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    We support instant digital payments via GCash when reserving online, as well as on-site cash settlements handled directly by our front desk staff.
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <button type="button" @click="activeFaq = activeFaq === 5 ? null : 5" class="w-full p-5 text-left flex items-center justify-between font-bold text-base">
                    <span>What are your operating hours?</span>
                    <span class="text-xl transition-transform" :class="{'rotate-45': activeFaq === 5}">+</span>
                </button>
                <div x-show="activeFaq === 5" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="px-5 pb-5 text-sm leading-relaxed" style="color: var(--muted);">
                    Gaoshou Pickleball is open 7 days a week from 8:00 AM to 10:00 PM, including holidays. Night play is fully lit by our 1,000-Lux stadium lighting system.
                </div>
            </div>
        </div>
    </section>

    <!-- Final Call To Action Banner -->
    <section class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 pb-20 sm:pb-24" aria-label="Reserve Call To Action">
        <div class="rounded-3xl p-6 sm:p-10 md:p-16 text-center relative overflow-hidden dark-panel">
            <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full blur-3xl opacity-20 pointer-events-none" style="background: var(--pop);"></div>
            <div class="absolute -left-20 -bottom-20 w-80 h-80 rounded-full blur-3xl opacity-20 pointer-events-none" style="background: var(--pop);"></div>

            <div class="relative z-10 max-w-2xl mx-auto">
                <span class="text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full" style="background: rgba(229, 168, 35, 0.2); color: var(--pop);">
                    ● Live Availability Ready
                </span>
                <h2 class="font-display text-4xl md:text-5xl font-extrabold mt-5 leading-tight">
                    Your next great rally starts on our courts.
                </h2>
                <p class="mt-4 text-base md:text-lg leading-relaxed text-stone-300">
                    Peak evening and weekend slots fill up fast. Reserve your preferred court today and experience the highest standard of pickleball in Davao.
                </p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    <a href="{{ $reserveUrl }}" class="btn-primary text-base py-4 px-8">
                        Reserve a Court Now →
                    </a>
                    <a href="#courts" class="btn-outline-light text-base py-3.5 px-6">
                        Explore Courts & Rates
                    </a>
                </div>
                <p class="text-xs mt-6 text-stone-400">
                    Instant confirmation · Flexible cancellation · Multi-court options
                </p>
            </div>
        </div>
    </section>

    </main>

   <x-site-footer />

    <script>
        try {
            if (localStorage.getItem('gaoshou_theme') === 'night' || localStorage.getItem('kymnet_theme') === 'night') {
                document.body.classList.add('night-mode');
            }
        } catch (e) {}
    </script>


</body>
</html>