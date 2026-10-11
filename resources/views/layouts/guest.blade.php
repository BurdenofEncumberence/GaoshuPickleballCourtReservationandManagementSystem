@props([
    'title' => 'Gaoshou Pickleball',
    'label' => 'Account',
    'cardClass' => '',
    'wide' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
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
<body class="antialiased relative gz-app-shell min-h-screen flex flex-col justify-between transition-colors duration-200">
    <div class="grain"></div>
    <x-loading-screen />

    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div style="background: var(--gz-bg); position: sticky; top: 0; z-index: 50; transition: background-color 0.2s ease;">
        @include('layouts.navigation')
    </div>

    <main id="main-content" class="relative z-10 flex-1 flex items-center justify-center py-12 sm:py-16">
        <section class="max-w-6xl w-full mx-auto px-4 sm:px-6 flex justify-center" aria-label="{{ $label }}">
            @if($wide)
                {{-- Wide pages (e.g. Contact): full width, no login-style card --}}
                <div class="w-full" style="max-width: 72rem;">
                    {{ $slot }}
                </div>
            @else
                {{-- Default narrow card (login, register, etc.) --}}
                <div class="gz-card w-full rounded-3xl {{ $cardClass }}">
                    {{ $slot }}
                </div>
            @endif
        </section>
    </main>

    <x-site-footer />
</body>
</html>