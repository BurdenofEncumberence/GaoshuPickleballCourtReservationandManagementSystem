{{--
    resources/views/components/site-footer.blade.php

    Shared footer used by every page. Purely static — no per-page
    variation, so no props needed.
--}}
@php
    $siteSettings = \App\Models\SiteSettings::first();
    $hasCustomSocial = $siteSettings && ($siteSettings->facebook_link || $siteSettings->twitter_link || $siteSettings->instagram_link || $siteSettings->linkedin_link);
@endphp
<footer class="border-t mt-12 print:hidden" style="background: #12150F; border-color: rgba(255,255,255,0.08); color: rgba(255,255,255,0.7);">
    <div class="max-w-6xl mx-auto px-6 py-12 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
        <div>
            <span class="gz-font-display font-bold text-lg" style="color: #FCFBF7;">{{ $siteSettings->business_name ?? ($siteSettings->system_name ?? 'Gaoshou Pickleball') }}</span>
            <p class="mt-3 text-sm max-w-xs leading-relaxed">
                {{ $siteSettings->tagline ?? 'Book Your Court, Rally with Ease' }}
            </p>
            <p class="mt-2 text-sm">
                <a href="mailto:{{ $siteSettings->email_address ?? 'support@gaoshou.ph' }}" class="hover:text-[#E5A823] transition">
                    {{ $siteSettings->email_address ?? 'support@gaoshou.ph' }}
                </a>
            </p>
            @if($siteSettings && $siteSettings->contact_number)
                <p class="mt-1 text-sm">{{ $siteSettings->contact_number }}</p>
            @endif
        </div>
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-wide mb-4" style="color: #FCFBF7;">Explore</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('booking') }}" class="hover:text-[#E5A823] transition">Reserve Courts</a></li>
                <li><a href="{{ route('open-play.index') }}" class="hover:text-[#E5A823] transition">Open Play &amp; Tournaments</a></li>
                @auth
                    <li><a href="{{ route('bookings.index') }}" class="hover:text-[#E5A823] transition">My Bookings</a></li>
                @else
                    <li><a href="{{ route('login') }}" class="hover:text-[#E5A823] transition">Player Sign In</a></li>
                @endauth
            </ul>
        </div>
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-wide mb-4" style="color: #FCFBF7;">Legal &amp; Support</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('terms') }}" class="hover:text-[#E5A823] transition">Terms &amp; Conditions</a></li>
                <li><a href="{{ route('privacy') }}" class="hover:text-[#E5A823] transition">Privacy Policy</a></li>
            </ul>
        </div>
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-wide mb-4" style="color: #FCFBF7;">Connect</h4>
            <ul class="space-y-2 text-sm">
                @if($hasCustomSocial)
                    @if($siteSettings->facebook_link)
                        <li><a href="{{ $siteSettings->facebook_link }}" target="_blank" rel="noopener noreferrer" class="hover:text-[#E5A823] transition">Facebook</a></li>
                    @endif
                    @if($siteSettings->instagram_link)
                        <li><a href="{{ $siteSettings->instagram_link }}" target="_blank" rel="noopener noreferrer" class="hover:text-[#E5A823] transition">Instagram</a></li>
                    @endif
                    @if($siteSettings->twitter_link)
                        <li><a href="{{ $siteSettings->twitter_link }}" target="_blank" rel="noopener noreferrer" class="hover:text-[#E5A823] transition">X (Twitter)</a></li>
                    @endif
                    @if($siteSettings->linkedin_link)
                        <li><a href="{{ $siteSettings->linkedin_link }}" target="_blank" rel="noopener noreferrer" class="hover:text-[#E5A823] transition">LinkedIn</a></li>
                    @endif
                @else
                    <li><a href="mailto:{{ $siteSettings->email_address ?? 'support@gaoshou.ph' }}" class="hover:text-[#E5A823] transition">Email Front Desk</a></li>
                    <li><a href="{{ route('open-play.index') }}" class="hover:text-[#E5A823] transition">Community Open Play</a></li>
                    <li><span style="color: rgba(255,255,255,0.5);">Open Daily: 6:00 AM – 10:00 PM</span></li>
                @endif
            </ul>
        </div>
    </div>
    <div class="max-w-6xl mx-auto px-6 pb-8 text-sm border-t pt-6" style="border-color: rgba(255,255,255,0.06); color: rgba(255,255,255,0.4);">
        © {{ date('Y') }} {{ $siteSettings->business_name ?? ($siteSettings->system_name ?? 'Gaoshou Pickleball') }}. All rights reserved.
    </div>
</footer>