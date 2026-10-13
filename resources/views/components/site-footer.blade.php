{{--
    resources/views/components/site-footer.blade.php
    Universal footer used by every page, including the home page.
--}}
@php
    $siteSettings = $siteSettings ?? \App\Models\SiteSettings::current();
    $businessName = $siteSettings->business_name ?? ($siteSettings->system_name ?? 'Gaoshou Pickleball');
    $email = $siteSettings->email_address ?? 'support@gaoshou.ph';
    $tagline = $siteSettings->tagline ?? 'Court booking for the Davao pickleball community. Built by players, for players.';
    $socials = array_filter([
        'Facebook'  => $siteSettings->facebook_link ?? null,
        'Instagram' => $siteSettings->instagram_link ?? null,
        'X (Twitter)' => $siteSettings->twitter_link ?? null,
        'LinkedIn'  => $siteSettings->linkedin_link ?? null,
    ]);
@endphp

<footer class="relative z-10 print:hidden" style="background: #12150F; color: rgba(255,255,255,0.7); border-top: 1px solid rgba(255,255,255,0.08);">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-14 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8 sm:gap-10">

        {{-- Brand --}}
        <div>
            <span class="font-display gz-font-display font-bold text-lg" style="color: #FCFBF7;">{{ $businessName }}</span>
            <p class="mt-4 text-sm max-w-xs leading-relaxed">{{ $tagline }}</p>
            <p class="mt-3 text-sm">
                <a href="mailto:{{ $email }}" class="hover:text-[#E5A823] transition">{{ $email }}</a>
            </p>
            @if($siteSettings && $siteSettings->contact_number)
                <p class="mt-1 text-sm">{{ $siteSettings->contact_number }}</p>
            @endif
        </div>

        {{-- Explore --}}
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-wide mb-4" style="color: #FCFBF7;">Explore</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('booking') }}" class="hover:text-[#E5A823] transition">Reserve a Court</a></li>
                <li><a href="{{ route('open-play.index') }}" class="hover:text-[#E5A823] transition">Open Play &amp; Tournaments</a></li>
                <li><a href="{{ url('/') }}#courts" class="hover:text-[#E5A823] transition">Court Fleet &amp; Rates</a></li>
                <li><a href="{{ url('/') }}#perks" class="hover:text-[#E5A823] transition">Facility Amenities</a></li>
                <li><a href="{{ url('/') }}#how-it-works" class="hover:text-[#E5A823] transition">How It Works</a></li>
                @auth
                    <li><a href="{{ route('bookings.index') }}" class="hover:text-[#E5A823] transition">My Bookings</a></li>
                @else
                    <li><a href="{{ route('login') }}" class="hover:text-[#E5A823] transition">Player Sign In</a></li>
                @endauth
            </ul>
        </div>

        {{-- Support & Legal --}}
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-wide mb-4" style="color: #FCFBF7;">Support &amp; Legal</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ url('/') }}#faq" class="hover:text-[#E5A823] transition">Frequently Asked Questions</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-[#E5A823] transition">Contact Desk</a></li>
                <li><a href="{{ route('terms') }}" class="hover:text-[#E5A823] transition">Terms &amp; Conditions</a></li>
                <li><a href="{{ route('privacy') }}" class="hover:text-[#E5A823] transition">Privacy Policy</a></li>
            </ul>
        </div>

        {{-- Location & Hours (+ socials if set in SiteSettings) --}}
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-wide mb-4" style="color: #FCFBF7;">Location &amp; Hours</h4>
            <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.65);">
                Davao City, Philippines<br>
                Open Daily: 8:00 AM – 10:00 PM<br>
                Covered &amp; Stadium-Lit Courts
            </p>

            @if(count($socials))
                <ul class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                    @foreach($socials as $label => $link)
                        <li>
                            <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" class="hover:text-[#E5A823] transition">{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 pb-8 pt-6 text-sm border-t" style="border-color: rgba(255,255,255,0.06); color: rgba(255,255,255,0.4);">
        © {{ date('Y') }} {{ $businessName }}. All rights reserved.
    </div>
</footer>