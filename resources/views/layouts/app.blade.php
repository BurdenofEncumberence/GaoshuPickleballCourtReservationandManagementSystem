@props(['title' => null, 'heading' => 'Overview'])

@php
    $siteSettings = $siteSettings ?? \App\Models\SiteSettings::current();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ($siteSettings->system_name ?? 'Gaoshou Pickleball') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        try {
            if (localStorage.getItem('gaoshou_theme') === 'night' || localStorage.getItem('kymnet_theme') === 'night') {
                document.documentElement.classList.add('night-mode', 'dark');
            }
        } catch (e) {}
    </script>
</head>
<body class="antialiased gz-app-shell transition-colors duration-200">
    <div class="grain"></div>
    <x-loading-screen />

    <div class="min-h-screen flex flex-col">
        <div style="background: var(--gz-bg); position: sticky; top: 0; z-index: 50;">
            @include('layouts.navigation', ['siteSettings' => $siteSettings])
        </div>

        @isset($header)
            <div class="gz-container relative" style="padding-bottom: 0;">
                <div style="padding-top: 24px; padding-bottom: 20px; border-bottom: 1.5px solid var(--gz-border);">
                    {{ $header }}
                </div>
            </div>
        @endisset

        <div
            class="gz-container relative flex-1"
            style="padding-top: 24px;"
        >
            <main>
                {{ $slot }}
            </main>
        </div>

        <x-site-footer />
    </div>
</body>
</html>