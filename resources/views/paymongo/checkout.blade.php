<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GCash QR Checkout — {{ config('app.name', 'Gaoshou Pickleball') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:600,700,800|plus-jakarta-sans:400,500,600,700|jetbrains-mono:500,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --pm-gold: #E5A823;
            --pm-gold-dark: #A67512;
            --pm-gold-soft: rgba(229, 168, 35, 0.14);
            --pm-ink: #12150F;
            --pm-bg: #FCFBF7;
            --pm-surface: #FFFFFF;
            --pm-border: #E5E2D9;
            --pm-muted: #5C6255;
        }
        body {
            background: radial-gradient(circle at top right, rgba(229, 168, 35, 0.16), transparent 45%),
                        radial-gradient(circle at bottom left, rgba(166, 117, 18, 0.10), transparent 45%),
                        var(--pm-bg);
            color: var(--pm-ink);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }
        .pm-gold-header {
            background: linear-gradient(135deg, #12150F 0%, #1F1A0E 60%, #2C220E 100%);
            border-bottom: 2px solid var(--pm-gold);
        }
        .pm-gold-btn {
            background: linear-gradient(135deg, #E5A823 0%, #C68B12 100%);
            color: #12150F;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            letter-spacing: 0.02em;
            box-shadow: 0 8px 20px -4px rgba(229, 168, 35, 0.45);
            transition: all 0.18s ease;
        }
        .pm-gold-btn:hover {
            background: linear-gradient(135deg, #F0B429 0%, #D49617 100%);
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -4px rgba(229, 168, 35, 0.55);
        }
        .pm-qr-frame {
            background: #FFFFFF;
            border: 3px solid var(--pm-gold);
            box-shadow: 0 12px 32px -8px rgba(166, 117, 18, 0.22);
        }
    </style>
</head>
<body class="antialiased flex flex-col min-h-screen">

    {{-- Top Gold-Accented PayMongo × Gaoshou Gateway Bar --}}
    <header class="pm-gold-header text-white py-3.5 px-4 sm:px-8">
        <div class="max-w-5xl mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="Gaoshou Pickleball" class="h-10 w-auto object-contain rounded-lg bg-white/10 p-1 border border-[#E5A823]/40">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-sm sm:text-base tracking-tight text-white" style="font-family: 'Outfit', sans-serif;">
                            GAOSHOU PICKLEBALL
                        </span>
                        <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full"
                              style="background: rgba(229, 168, 35, 0.22); color: #E5A823; border: 1px solid rgba(229, 168, 35, 0.45);">
                            PayMongo Gateway
                        </span>
                    </div>
                    <p class="text-[11px] text-[#D7D3C8]">Official Gold Secure Checkout · GCash QR Scan Only</p>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs font-semibold px-3 py-1.5 rounded-lg"
                 style="background: rgba(229, 168, 35, 0.14); color: #E5A823; border: 1px solid rgba(229, 168, 35, 0.35);">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>256-Bit Encrypted</span>
            </div>
        </div>
    </header>

    {{-- Main Checkout Content --}}
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- Left Column: Order Summary & Customer Details --}}
            <div class="lg:col-span-5 space-y-5">
                <div class="rounded-2xl p-6 border bg-white shadow-sm" style="border-color: var(--pm-border);">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b" style="border-color: var(--pm-border);">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-widest block" style="color: var(--pm-gold-dark);">
                                Reservation Summary
                            </span>
                            <h1 class="text-lg font-extrabold mt-0.5" style="font-family: 'Outfit', sans-serif; color: var(--pm-ink);">
                                Reference #{{ $reference_number }}
                            </h1>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full"
                              style="background: var(--pm-gold-soft); color: var(--pm-gold-dark); border: 1px solid rgba(229, 168, 35, 0.4);">
                            Awaiting Scan
                        </span>
                    </div>

                    {{-- Amount Highlight Banner --}}
                    <div class="rounded-xl p-4 mb-5 border"
                         style="background: linear-gradient(135deg, rgba(229, 168, 35, 0.14) 0%, rgba(229, 168, 35, 0.05) 100%); border-color: rgba(229, 168, 35, 0.45);">
                        <div class="text-xs font-semibold uppercase tracking-wider" style="color: var(--pm-gold-dark);">
                            Total Amount Due
                        </div>
                        <div class="text-3xl font-extrabold mt-1 font-mono" style="color: var(--pm-ink);">
                            ₱{{ number_format($amount, 2) }}
                        </div>
                        <div class="text-[11px] mt-1" style="color: var(--pm-muted);">
                            {{ $description }}
                        </div>
                    </div>

                    {{-- Line Items --}}
                    <div class="space-y-3 mb-5">
                        <div class="text-[11px] font-bold uppercase tracking-wider" style="color: var(--pm-muted);">
                            Breakdown
                        </div>
                        @foreach ($line_items as $item)
                            <div class="flex items-start justify-between gap-3 py-2.5 px-3 rounded-xl border text-xs"
                                 style="border-color: var(--pm-border); background: #FCFBF7;">
                                <div>
                                    <div class="font-bold text-sm" style="color: var(--pm-ink);">{{ $item['name'] }}</div>
                                    @if (!empty($item['description']))
                                        <div class="text-[11px] mt-0.5" style="color: var(--pm-muted);">{{ $item['description'] }}</div>
                                    @endif
                                </div>
                                <div class="font-mono font-bold text-sm shrink-0" style="color: var(--pm-gold-dark);">
                                    ₱{{ number_format($item['amount'], 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Customer Details --}}
                    <div class="pt-4 border-t space-y-2 text-xs" style="border-color: var(--pm-border);">
                        <div class="flex justify-between">
                            <span style="color: var(--pm-muted);">Billed To:</span>
                            <span class="font-bold" style="color: var(--pm-ink);">{{ $customer_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color: var(--pm-muted);">Email:</span>
                            <span class="font-medium" style="color: var(--pm-ink);">{{ $customer_email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color: var(--pm-muted);">Payment Method:</span>
                            <span class="font-bold" style="color: var(--pm-gold-dark);">GCash QR Scan</span>
                        </div>
                    </div>
                </div>

                {{-- Cancel / Back Link --}}
                <div class="text-center sm:text-left">
                    <a href="{{ $cancel_url }}"
                       class="inline-flex items-center gap-2 text-xs font-bold px-4 py-2.5 rounded-xl border transition hover:bg-amber-50/60"
                       style="border-color: var(--pm-border); color: var(--pm-muted);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Cancel & Return to Gaoshou</span>
                    </a>
                </div>
            </div>

            {{-- Right Column: Gold GCash QR Code Scanner Card --}}
            <div class="lg:col-span-7">
                <div class="rounded-2xl p-6 sm:p-8 border bg-white shadow-sm relative overflow-hidden"
                     style="border-color: var(--pm-gold); box-shadow: 0 16px 40px -12px rgba(166, 117, 18, 0.18);">

                    {{-- Top Gold Ribbon --}}
                    <div class="absolute top-0 inset-x-0 h-1.5" style="background: linear-gradient(90deg, #A67512, #E5A823, #F3C65F, #E5A823, #A67512);"></div>

                    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center font-extrabold text-sm shadow-sm"
                                 style="background: linear-gradient(135deg, #E5A823 0%, #A67512 100%); color: #12150F; font-family: 'Outfit', sans-serif;">
                                QR
                            </div>
                            <div>
                                <h2 class="text-xl font-extrabold tracking-tight" style="font-family: 'Outfit', sans-serif; color: var(--pm-ink);">
                                    Scan to Pay with GCash
                                </h2>
                                <p class="text-xs" style="color: var(--pm-muted);">
                                    Scan the official PayMongo QR code below using your GCash mobile app.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider px-3 py-1 rounded-full"
                                  style="background: var(--pm-gold-soft); color: var(--pm-gold-dark); border: 1px solid rgba(229, 168, 35, 0.5);">
                                GCash QR Only
                            </span>
                        </div>
                    </div>

                    {{-- QR Code Container --}}
                    <div class="flex flex-col items-center justify-center my-4">
                        <div class="pm-qr-frame rounded-2xl p-5 sm:p-6 flex flex-col items-center max-w-xs w-full">
                            {{-- Gold Header inside QR card --}}
                            <div class="w-full flex items-center justify-between pb-3 mb-3 border-b text-xs font-bold"
                                 style="border-color: var(--pm-border); color: var(--pm-gold-dark);">
                                <span>GAOSHOU PICKLEBALL</span>
                                <span class="font-mono">GCASH QR</span>
                            </div>

                            @if (!empty($qrImageBase64))
                                <img src="{{ $qrImageBase64 }}"
                                     alt="GCash QR Code for {{ $reference_number }}"
                                     class="w-56 h-56 sm:w-64 sm:h-64 object-contain rounded-lg" />
                            @else
                                {{-- High-contrast Deterministic SVG QR Code Fallback for Offline/Test Environments --}}
                                <div class="w-56 h-56 sm:w-64 sm:h-64 p-3 bg-white rounded-lg flex items-center justify-center border"
                                     style="border-color: var(--pm-border);">
                                    <svg viewBox="0 0 210 210" class="w-full h-full" role="img" aria-label="GCash QR Code">
                                        <rect width="210" height="210" fill="#FFFFFF"/>
                                        {{-- Finder Pattern Top-Left --}}
                                        <rect x="10" y="10" width="56" height="56" rx="4" fill="#12150F"/>
                                        <rect x="18" y="18" width="40" height="40" rx="2" fill="#FFFFFF"/>
                                        <rect x="26" y="26" width="24" height="24" rx="2" fill="#E5A823"/>
                                        {{-- Finder Pattern Top-Right --}}
                                        <rect x="144" y="10" width="56" height="56" rx="4" fill="#12150F"/>
                                        <rect x="152" y="18" width="40" height="40" rx="2" fill="#FFFFFF"/>
                                        <rect x="160" y="26" width="24" height="24" rx="2" fill="#E5A823"/>
                                        {{-- Finder Pattern Bottom-Left --}}
                                        <rect x="10" y="144" width="56" height="56" rx="4" fill="#12150F"/>
                                        <rect x="18" y="152" width="40" height="40" rx="2" fill="#FFFFFF"/>
                                        <rect x="26" y="160" width="24" height="24" rx="2" fill="#E5A823"/>
                                        {{-- QR Matrix Modules --}}
                                        <g fill="#12150F">
                                            <rect x="78" y="14" width="10" height="10"/><rect x="98" y="14" width="10" height="10"/><rect x="118" y="14" width="10" height="10"/>
                                            <rect x="78" y="34" width="10" height="10"/><rect x="108" y="34" width="10" height="10"/><rect x="128" y="34" width="10" height="10"/>
                                            <rect x="88" y="54" width="10" height="10"/><rect x="118" y="54" width="10" height="10"/>
                                            <rect x="14" y="78" width="10" height="10"/><rect x="34" y="78" width="10" height="10"/><rect x="54" y="78" width="10" height="10"/>
                                            <rect x="24" y="98" width="10" height="10"/><rect x="44" y="98" width="10" height="10"/>
                                            <rect x="14" y="118" width="10" height="10"/><rect x="44" y="118" width="10" height="10"/><rect x="54" y="118" width="10" height="10"/>
                                            <rect x="144" y="78" width="10" height="10"/><rect x="164" y="78" width="10" height="10"/><rect x="184" y="78" width="10" height="10"/>
                                            <rect x="154" y="98" width="10" height="10"/><rect x="174" y="98" width="10" height="10"/><rect x="190" y="98" width="10" height="10"/>
                                            <rect x="144" y="118" width="10" height="10"/><rect x="174" y="118" width="10" height="10"/>
                                            <rect x="78" y="144" width="10" height="10"/><rect x="98" y="144" width="10" height="10"/><rect x="128" y="144" width="10" height="10"/>
                                            <rect x="88" y="164" width="10" height="10"/><rect x="118" y="164" width="10" height="10"/><rect x="144" y="164" width="10" height="10"/><rect x="174" y="164" width="10" height="10"/>
                                            <rect x="78" y="184" width="10" height="10"/><rect x="108" y="184" width="10" height="10"/><rect x="134" y="184" width="10" height="10"/><rect x="164" y="184" width="10" height="10"/><rect x="188" y="184" width="10" height="10"/>
                                        </g>
                                        {{-- Center GCash Gold Badge --}}
                                        <rect x="76" y="76" width="58" height="58" rx="10" fill="#12150F" stroke="#E5A823" stroke-width="3"/>
                                        <text x="105" y="103" text-anchor="middle" fill="#E5A823" font-family="Outfit, sans-serif" font-weight="800" font-size="13">GCash</text>
                                        <text x="105" y="119" text-anchor="middle" fill="#FFFFFF" font-family="JetBrains Mono, monospace" font-weight="700" font-size="10">QR SCAN</text>
                                    </svg>
                                </div>
                            @endif

                            <div class="w-full pt-3 mt-3 border-t flex items-center justify-between text-xs"
                                 style="border-color: var(--pm-border);">
                                <span class="font-semibold" style="color: var(--pm-muted);">Amount to Scan:</span>
                                <span class="font-mono font-extrabold text-sm" style="color: var(--pm-gold-dark);">
                                    ₱{{ number_format($amount, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- 3-Step GCash Instructions --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 my-6">
                        <div class="p-3 rounded-xl border text-xs" style="border-color: var(--pm-border); background: #FCFBF7;">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[11px] font-extrabold mb-1"
                                  style="background: var(--pm-gold); color: #12150F;">1</span>
                            <div class="font-bold" style="color: var(--pm-ink);">Open GCash App</div>
                            <p class="text-[11px] mt-0.5" style="color: var(--pm-muted);">Launch the GCash app on your phone and tap <strong>QR</strong>.</p>
                        </div>
                        <div class="p-3 rounded-xl border text-xs" style="border-color: var(--pm-border); background: #FCFBF7;">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[11px] font-extrabold mb-1"
                                  style="background: var(--pm-gold); color: #12150F;">2</span>
                            <div class="font-bold" style="color: var(--pm-ink);">Scan QR Code</div>
                            <p class="text-[11px] mt-0.5" style="color: var(--pm-muted);">Point your camera at the QR code above and authorize ₱{{ number_format($amount, 2) }}.</p>
                        </div>
                        <div class="p-3 rounded-xl border text-xs" style="border-color: var(--pm-border); background: #FCFBF7;">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[11px] font-extrabold mb-1"
                                  style="background: var(--pm-gold); color: #12150F;">3</span>
                            <div class="font-bold" style="color: var(--pm-ink);">Confirm Below</div>
                            <p class="text-[11px] mt-0.5" style="color: var(--pm-muted);">Click the gold confirmation button below to lock in your booking.</p>
                        </div>
                    </div>

                    {{-- Complete Payment Form --}}
                    <form method="POST" action="{{ route('paymongo.gateway.complete', ['session_id' => $sessionId]) }}">
                        @csrf
                        <button type="submit"
                                class="pm-gold-btn w-full py-3.5 px-6 rounded-xl text-sm sm:text-base flex items-center justify-center gap-2.5 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Confirm & Complete GCash Payment (₱{{ number_format($amount, 2) }})</span>
                        </button>
                    </form>

                    <p class="text-center text-[11px] mt-3" style="color: var(--pm-muted);">
                        Session ID: <span class="font-mono">{{ $sessionId }}</span> · Powered by PayMongo QR Ph for Gaoshou Pickleball
                    </p>
                </div>
            </div>

        </div>
    </main>

</body>
</html>
