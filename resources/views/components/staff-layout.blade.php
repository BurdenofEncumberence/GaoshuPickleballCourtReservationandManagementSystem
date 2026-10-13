@props(['title' => null, 'heading' => "Today's Schedule"])

@php
    $siteSettings = $siteSettings ?? \App\Models\SiteSettings::current();
    $currentUser = Auth::user();
    $shortName = $currentUser->first_name
        ?: (\Illuminate\Support\Str::of($currentUser->name ?? 'Staff')->before(' ')->value() ?: 'Staff');
    $logoPath = null;
    if ($siteSettings && $siteSettings->logo && file_exists(public_path('storage/' . $siteSettings->logo))) {
        $logoPath = asset('storage/' . $siteSettings->logo);
    } elseif (file_exists(public_path('images/logo.png'))) {
        $logoPath = asset('images/logo.png');
    } elseif (file_exists(public_path('images/kymnet-logo.png'))) {
        $logoPath = asset('images/kymnet-logo.png');
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Staff Desk · ' . ($siteSettings->system_name ?? 'Gaoshou Pickleball') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased gz-app-shell">
    <div class="grain"></div>
    <x-loading-screen />

    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex flex-col lg:flex-row" style="background: var(--gz-bg);">

        {{-- Mobile Top Bar --}}
        <header class="lg:hidden sticky top-0 z-40 border-b flex items-center justify-between px-4 py-3 shrink-0"
                style="background: var(--gz-surface); border-color: var(--gz-border);">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="sidebarOpen = true"
                    class="gz-btn-outline gz-btn-sm p-2 flex items-center justify-center rounded-lg"
                    aria-label="Open sidebar navigation"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <div class="flex items-center gap-2">
                    @if($logoPath)
                        <img src="{{ $logoPath }}" alt="{{ $siteSettings->system_name ?? 'Logo' }}" class="h-8 sm:h-9 w-auto max-h-9 max-w-[120px] object-contain rounded-lg">
                    @else
                        <div class="w-6 h-6 rounded flex items-center justify-center font-bold text-xs" style="background: var(--gz-pop); color: var(--gz-ink);">
                            G
                        </div>
                    @endif
                    <span class="gz-font-display font-bold text-sm tracking-tight" style="color: var(--gz-ink);">
                        {{ $siteSettings->system_name ?? 'Gaoshou Pickleball' }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="gz-badge-outline text-[10px] uppercase font-mono font-bold px-2 py-0.5">STAFF</span>
                <span class="text-xs font-bold" style="color: var(--gz-ink);">{{ $shortName }}</span>
            </div>
        </header>

        {{-- Mobile Sidebar Backdrop --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            aria-hidden="true"
        ></div>

        {{-- Left Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 xl:w-72 flex flex-col justify-between border-r transition-transform duration-200 ease-in-out lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            style="background: var(--gz-surface); border-color: var(--gz-border);"
        >
            {{-- Top Branding Header & Nav --}}
            <div class="flex-1 flex flex-col min-h-0">
                <div class="h-16 px-6 border-b flex items-center justify-between shrink-0" style="border-color: var(--gz-border);">
                    <a href="{{ route('staff.today') }}" class="flex items-center gap-2.5">
                        @if($logoPath)
                            <img src="{{ $logoPath }}" alt="{{ $siteSettings->system_name ?? 'Logo' }}" class="h-9 sm:h-10 w-auto max-h-10 max-w-[130px] object-contain rounded-lg shrink-0">
                        @else
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm" style="background: var(--gz-pop); color: var(--gz-ink);">
                                G
                            </div>
                        @endif
                        <div>
                            <span class="gz-font-display font-extrabold text-base tracking-tight block leading-tight" style="color: var(--gz-ink);">
                                {{ $siteSettings->system_name ?? 'Gaoshou Pickleball' }}
                            </span>
                            <span class="text-[10px] uppercase font-bold tracking-widest block" style="color: var(--gz-muted);">
                                Staff Desk
                            </span>
                        </div>
                    </a>

                    <button
                        type="button"
                        @click="sidebarOpen = false"
                        class="lg:hidden p-1.5 rounded-lg text-gray-500 hover:text-black dark:hover:text-white"
                        aria-label="Close sidebar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- Sidebar Navigation Links --}}
                <div class="p-4 overflow-y-auto flex-1 min-h-0">
                    <div class="mb-6">
                        <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider" style="color: var(--gz-muted);">
                            Operations
                        </div>

                        <nav class="space-y-1">
                            {{-- Today's Schedule --}}
                            <a
                                href="{{ route('staff.today') }}"
                                class="gz-sidebar-nav-link {{ request()->routeIs('staff.today') ? 'is-active' : '' }}"
                                @if(request()->routeIs('staff.today')) aria-current="page" @endif
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span>Today's Schedule</span>
                            </a>

                            {{-- Walk-In Booking --}}
                            <a
                                href="{{ route('staff.walkin.create') }}"
                                class="gz-sidebar-nav-link {{ request()->routeIs('staff.walkin.*') ? 'is-active' : '' }}"
                                @if(request()->routeIs('staff.walkin.*')) aria-current="page" @endif
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                <span>Walk-In Booking</span>
                            </a>
                        </nav>
                    </div>

                    {{-- Account Section --}}
                    <div class="mb-2">
                        <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider" style="color: var(--gz-muted);">
                            Account
                        </div>
                        <nav class="space-y-1">
                            <a
                                href="{{ route('profile.edit') }}"
                                class="gz-sidebar-nav-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}"
                                @if(request()->routeIs('profile.*')) aria-current="page" @endif
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Profile Settings</span>
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            {{-- Sidebar User Profile & Logout Footer --}}
            <div class="p-4 border-t shrink-0" style="border-color: var(--gz-border); background: var(--gz-bg);">
                <div class="flex items-center gap-2.5 mb-3 min-w-0">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0" style="background: var(--gz-ink); color: var(--gz-surface);">
                        {{ strtoupper(substr($currentUser->name ?? 'S', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold truncate leading-tight" style="color: var(--gz-ink);" title="{{ $currentUser->name }}">
                            {{ $currentUser->name }}
                        </p>
                        <span class="gz-badge-outline text-[9px] uppercase font-mono font-bold px-1.5 mt-0.5 inline-block">STAFF</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-semibold transition border border-red-200 text-red-600 hover:bg-red-600 hover:text-white hover:border-red-600 active:scale-98 dark:border-red-900/40 dark:hover:bg-red-700"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Workspace Content --}}
        <div class="flex-1 min-w-0 flex flex-col min-h-screen lg:ml-64 xl:ml-72">
            {{-- Optional Top Page Header --}}
            @isset($header)
                <header class="sticky top-0 z-20 border-b px-4 sm:px-6 lg:px-8 py-4 shrink-0" style="background: var(--gz-surface); border-color: var(--gz-border);">
                    <div class="max-w-7xl mx-auto">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            {{-- Main Content Slot --}}
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                {{ $slot }}
            </main>

            {{-- Staff Footer --}}
            <footer class="border-t px-4 sm:px-6 lg:px-8 py-4 shrink-0 text-xs" style="border-color: var(--gz-border); color: var(--gz-muted);">
                <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                    <span>{{ $siteSettings->system_name ?? 'Gaoshou Pickleball' }} &copy; {{ date('Y') }} &middot; All Rights Reserved</span>
                    <span class="inline-flex items-center gap-1.5 font-mono text-[11px]">
                        <span class="w-2 h-2 rounded-full inline-block" style="background: var(--gz-pop);"></span>
                        Staff Desk Online
                    </span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Global Booking Receipt Modal -->
    <x-booking-receipt-modal />
</body>
</html>