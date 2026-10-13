@php
    $siteSettings = $siteSettings ?? \App\Models\SiteSettings::current();
    $logoUrl = null;
    if ($siteSettings && $siteSettings->logo && file_exists(public_path('storage/' . $siteSettings->logo))) {
        $logoUrl = asset('storage/' . $siteSettings->logo);
    } elseif (file_exists(public_path('images/logo.png'))) {
        $logoUrl = asset('images/logo.png');
    } elseif (file_exists(public_path('images/kymnet-logo.png'))) {
        $logoUrl = asset('images/kymnet-logo.png');
    }
@endphp

@if($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $siteSettings->system_name ?? 'Gaoshou Pickleball' }}" {{ $attributes->merge(['class' => 'h-16 sm:h-20 w-auto max-h-20 max-w-[220px] object-contain rounded-xl shadow-sm']) }}>
@else
    <div {{ $attributes->merge(['class' => 'h-14 w-14 rounded-2xl bg-[#12150F] flex items-center justify-center font-display font-extrabold text-amber-400 text-2xl border border-amber-500/30 shadow-md']) }}>
        G
    </div>
@endif
