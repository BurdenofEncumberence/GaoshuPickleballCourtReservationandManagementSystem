@props(['title' => null, 'heading' => 'Overview'])

@php
    $siteSettings = \App\Models\SiteSettings::first();
    $currentUser = Auth::user();
    $shortName = $currentUser->first_name 
        ?: (\Illuminate\Support\Str::of($currentUser->name ?? 'Admin')->before(' ')->value() ?: 'Admin');
    $logoPath = null;
    if ($siteSettings && $siteSettings->logo && file_exists(public_path('storage/' . $siteSettings->logo))) {
        $logoPath = asset('storage/' . $siteSettings->logo);
    } elseif (file_exists(public_path('images/logo.png'))) {
        $logoPath = asset('images/logo.png');
    } elseif (file_exists(public_path('images/kymnet-logo.png'))) {
        $logoPath = asset('images/kymnet-logo.png');
    }
    $pendingHostRequestsCount = ($currentUser && $currentUser->isManager())
        ? \App\Models\OpenPlaySession::where('session_status', 'pending_approval')->count()
        : 0;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin Operations · ' . ($siteSettings->system_name ?? 'Gaoshou Pickleball') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased gz-app-shell">
    <div class="grain"></div>
    <x-loading-screen />

    <div
        x-data="{ sidebarOpen: false, exportModalOpen: false, exportType: 'overall', exportPeriod: 'this_month', exportFormat: 'csv' }"
        @open-export-modal.window="exportModalOpen = true; if ($event.detail?.type) exportType = $event.detail.type; if ($event.detail?.period) exportPeriod = $event.detail.period; if ($event.detail?.format) exportFormat = $event.detail.format;"
        class="min-h-screen flex flex-col lg:flex-row"
        style="background: var(--gz-bg);"
    >
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
                        <img src="{{ $logoPath }}" alt="{{ $siteSettings->system_name ?? 'Logo' }}" class="h-7 w-auto max-h-7 max-w-[100px] object-contain rounded-lg">
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
                <span class="gz-badge-outline text-[10px] uppercase font-mono font-bold px-2 py-0.5">
                    {{ $currentUser->isAdmin() ? 'ADMIN' : 'MANAGER' }}
                </span>
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
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                        @if($logoPath)
                            <img src="{{ $logoPath }}" alt="{{ $siteSettings->system_name ?? 'Logo' }}" class="h-8 w-auto max-h-8 max-w-[110px] object-contain rounded-lg shrink-0">
                        @else
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm" style="background: var(--gz-pop); color: var(--gz-ink);">
                                G
                            </div>
                        @endif
                        <div>
                            <span class="gz-font-display font-extrabold text-base tracking-tight block leading-tight" style="color: var(--gz-ink);">
                                {{ $siteSettings->system_name ?? 'Gaoshou Pickleball' }}
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
                    {{-- Section: Operations --}}
                    <div class="mb-6">
                        <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider" style="color: var(--gz-muted);">
                            Operations
                        </div>

                        <nav class="space-y-1">
                            {{-- Overview --}}
                            <a
                                href="{{ route('admin.dashboard') }}"
                                class="gz-sidebar-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                                @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                </svg>
                                <span>Overview</span>
                            </a>

                            {{-- Bookings --}}
                            <a
                                href="{{ route('admin.bookings.index') }}"
                                class="gz-sidebar-nav-link {{ request()->routeIs('admin.bookings.*') ? 'is-active' : '' }}"
                                @if(request()->routeIs('admin.bookings.*')) aria-current="page" @endif
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                <span>Bookings</span>
                            </a>

                            {{-- Courts --}}
                            <a
                                href="{{ route('admin.courts.index') }}"
                                class="gz-sidebar-nav-link {{ request()->routeIs('admin.courts.*') ? 'is-active' : '' }}"
                                @if(request()->routeIs('admin.courts.*')) aria-current="page" @endif
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <span>Courts</span>
                            </a>

                            {{-- Events --}}
                            <a
                                href="{{ route('admin.events.index') }}"
                                class="gz-sidebar-nav-link {{ request()->routeIs('admin.events.*') ? 'is-active' : '' }}"
                                @if(request()->routeIs('admin.events.*')) aria-current="page" @endif
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span>Events</span>
                            </a>

                            {{-- Open Play & Tournaments --}}
                            <a
                                href="{{ route('admin.open-play.index') }}"
                                class="gz-sidebar-nav-link {{ request()->routeIs('admin.open-play.*') ? 'is-active' : '' }}"
                                @if(request()->routeIs('admin.open-play.*')) aria-current="page" @endif
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <span class="flex-1">Open Play & Tournaments</span>
                                @if(($pendingHostRequestsCount ?? 0) > 0)
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-mono font-bold rounded-full bg-amber-500 text-stone-950 shrink-0 shadow-xs" title="{{ $pendingHostRequestsCount }} requests pending review">
                                        {{ $pendingHostRequestsCount }}
                                    </span>
                                @endif
                            </a>

                            {{-- Financials --}}
                            <a
                                href="{{ route('admin.finance') }}"
                                class="gz-sidebar-nav-link {{ request()->routeIs('admin.finance') ? 'is-active' : '' }}"
                                @if(request()->routeIs('admin.finance')) aria-current="page" @endif
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <span>Financials</span>
                            </a>

                            @if($currentUser && $currentUser->isAdmin())
                                {{-- User Management --}}
                                <a
                                    href="{{ route('admin.users.index') }}"
                                    class="gz-sidebar-nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}"
                                    @if(request()->routeIs('admin.users.*')) aria-current="page" @endif
                                >
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    <span>User Management</span>
                                </a>

                                {{-- Activity Logs --}}
                                <a
                                    href="{{ route('admin.activity-logs.index') }}"
                                    class="gz-sidebar-nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'is-active' : '' }}"
                                    @if(request()->routeIs('admin.activity-logs.*')) aria-current="page" @endif
                                >
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span>Activity Logs</span>
                                </a>
                            @endif

                            {{-- Customization --}}
                            <a
                                href="{{ route('admin.customization.index') }}"
                                class="gz-sidebar-nav-link {{ request()->routeIs('admin.customization.*') ? 'is-active' : '' }}"
                                @if(request()->routeIs('admin.customization.*')) aria-current="page" @endif
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                                <span>Customization</span>
                            </a>

                            {{-- Export Reports --}}
                            <button
                                type="button"
                                @click="exportModalOpen = true"
                                class="gz-sidebar-nav-link"
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Export Reports</span>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>

            {{-- Sidebar User Profile & Logout Footer --}}
            <div class="p-4 border-t shrink-0" style="border-color: var(--gz-border); background: var(--gz-bg);">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0" style="background: var(--gz-ink); color: var(--gz-surface);">
                            {{ strtoupper(substr($currentUser->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold truncate leading-tight" style="color: var(--gz-ink);" title="{{ $currentUser->name }}">
                                {{ $currentUser->name }}
                            </p>
                            <span class="gz-badge-outline text-[9px] uppercase font-mono font-bold px-1.5 py-0.2 mt-0.5 inline-block">
                                {{ $currentUser->isAdmin() ? 'ADMIN' : 'MANAGER' }}
                            </span>
                        </div>
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

            {{-- Admin Footer --}}
            <footer class="border-t px-4 sm:px-6 lg:px-8 py-4 shrink-0 text-xs" style="border-color: var(--gz-border); color: var(--gz-muted);">
                <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                    <span>{{ $siteSettings->system_name ?? 'Gaoshou Pickleball' }} &copy; {{ date('Y') }} &middot; All Rights Reserved</span>
                    <span class="inline-flex items-center gap-1.5 font-mono text-[11px]">
                        <span class="w-2 h-2 rounded-full inline-block" style="background: var(--gz-pop);"></span>
                        HQ Operations Online
                    </span>
                </div>
            </footer>
        </div>

        {{-- Reports Export Modal --}}
        <div
            x-show="exportModalOpen"
            x-cloak
            class="fixed inset-0 z-[60] overflow-y-auto"
            aria-labelledby="export-modal-title"
            role="dialog"
            aria-modal="true"
        >
            {{-- Backdrop --}}
            <div
                x-show="exportModalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/60 transition-opacity"
                @click="exportModalOpen = false"
                aria-hidden="true"
            ></div>

            {{-- Modal Panel --}}
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div
                    x-show="exportModalOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    @keydown.escape.window="exportModalOpen = false"
                    class="relative transform overflow-hidden rounded-2xl border text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg p-6"
                    style="background: var(--gz-surface); border-color: var(--gz-border); color: var(--gz-ink);"
                >
                    <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--gz-border);">
                        <div>
                            <span class="gz-eyebrow" style="color: var(--gz-pop-dark);">Data Export & Audit</span>
                            <h3 id="export-modal-title" class="gz-font-display font-bold text-lg mt-0.5">
                                Export System Reports
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="exportModalOpen = false"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-black dark:hover:text-white"
                            aria-label="Close export modal"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form method="GET" action="{{ route('admin.reports.export') }}" class="mt-5 space-y-5">
                        {{-- Report Type Selection --}}
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-2" style="color: var(--gz-muted);">
                                Report Category
                            </label>
                            <div class="grid grid-cols-1 gap-2.5">
                                <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                                       :class="exportType === 'overall' ? 'border-[color:var(--gz-pop-dark)] bg-[color:var(--gz-pop)]/10' : 'border-[color:var(--gz-border)] hover:bg-black/5 dark:hover:bg-white/5'">
                                    <input type="radio" name="type" value="overall" x-model="exportType" class="mt-0.5">
                                    <div class="text-xs">
                                        <p class="font-bold">Overall Operations Report</p>
                                        <p class="text-[11px] mt-0.5" style="color: var(--gz-muted);">Facility bookings, revenue totals, utilization rates, and activity logs.</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                                       :class="exportType === 'financial' ? 'border-[color:var(--gz-pop-dark)] bg-[color:var(--gz-pop)]/10' : 'border-[color:var(--gz-border)] hover:bg-black/5 dark:hover:bg-white/5'">
                                    <input type="radio" name="type" value="financial" x-model="exportType" class="mt-0.5">
                                    <div class="text-xs">
                                        <p class="font-bold">Financial Audit Report</p>
                                        <p class="text-[11px] mt-0.5" style="color: var(--gz-muted);">Collections breakdown, online vs walk-in splits, payment methods, and ledger.</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                                       :class="exportType === 'utilization' ? 'border-[color:var(--gz-pop-dark)] bg-[color:var(--gz-pop)]/10' : 'border-[color:var(--gz-border)] hover:bg-black/5 dark:hover:bg-white/5'">
                                    <input type="radio" name="type" value="utilization" x-model="exportType" class="mt-0.5">
                                    <div class="text-xs">
                                        <p class="font-bold">Court Utilization Report</p>
                                        <p class="text-[11px] mt-0.5" style="color: var(--gz-muted);">Court capacities, actual hours played, utilization load rates, and RevPACH yields.</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Timeframe Selection --}}
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-2" style="color: var(--gz-muted);">
                                Reporting Timeframe
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                                       :class="exportPeriod === 'this_day' ? 'border-[color:var(--gz-pop-dark)] bg-[color:var(--gz-pop)]/10' : 'border-[color:var(--gz-border)] hover:bg-black/5 dark:hover:bg-white/5'">
                                    <input type="radio" name="period" value="this_day" x-model="exportPeriod">
                                    <span>This Day (Today)</span>
                                </label>

                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                                       :class="exportPeriod === 'this_month' ? 'border-[color:var(--gz-pop-dark)] bg-[color:var(--gz-pop)]/10' : 'border-[color:var(--gz-border)] hover:bg-black/5 dark:hover:bg-white/5'">
                                    <input type="radio" name="period" value="this_month" x-model="exportPeriod">
                                    <span>This Month</span>
                                </label>

                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                                       :class="exportPeriod === 'last_month' ? 'border-[color:var(--gz-pop-dark)] bg-[color:var(--gz-pop)]/10' : 'border-[color:var(--gz-border)] hover:bg-black/5 dark:hover:bg-white/5'">
                                    <input type="radio" name="period" value="last_month" x-model="exportPeriod">
                                    <span>Last Month</span>
                                </label>

                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                                       :class="exportPeriod === 'this_year' ? 'border-[color:var(--gz-pop-dark)] bg-[color:var(--gz-pop)]/10' : 'border-[color:var(--gz-border)] hover:bg-black/5 dark:hover:bg-white/5'">
                                    <input type="radio" name="period" value="this_year" x-model="exportPeriod">
                                    <span>This Year</span>
                                </label>
                            </div>
                        </div>

                        {{-- Format Selection (CSV or PDF) --}}
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-2" style="color: var(--gz-muted);">
                                File Format
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                                       :class="exportFormat === 'csv' ? 'border-[color:var(--gz-pop-dark)] bg-[color:var(--gz-pop)]/10' : 'border-[color:var(--gz-border)] hover:bg-black/5 dark:hover:bg-white/5'">
                                    <input type="radio" name="format" value="csv" x-model="exportFormat">
                                    <span>CSV Spreadsheet (.csv)</span>
                                </label>

                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                                       :class="exportFormat === 'pdf' ? 'border-[color:var(--gz-pop-dark)] bg-[color:var(--gz-pop)]/10' : 'border-[color:var(--gz-border)] hover:bg-black/5 dark:hover:bg-white/5'">
                                    <input type="radio" name="format" value="pdf" x-model="exportFormat">
                                    <span>PDF Document (.pdf)</span>
                                </label>
                            </div>
                        </div>

                        {{-- Footer Notes & Actions --}}
                        <div class="pt-4 border-t flex items-center justify-between gap-3" style="border-color: var(--gz-border);">
                            <span class="text-[11px] font-mono" style="color: var(--gz-muted);" x-text="exportFormat === 'pdf' ? 'Format: Portable Document Format (.pdf)' : 'Format: CSV (Excel Compatible)'"></span>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="exportModalOpen = false"
                                    class="gz-btn-outline gz-btn-sm"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    @click="setTimeout(() => { exportModalOpen = false; }, 800)"
                                    class="gz-btn-primary gz-btn-sm flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    <span x-text="exportFormat === 'pdf' ? 'Download PDF' : 'Download CSV'"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Booking Receipt Modal -->
    <x-booking-receipt-modal />
</body>
</html>