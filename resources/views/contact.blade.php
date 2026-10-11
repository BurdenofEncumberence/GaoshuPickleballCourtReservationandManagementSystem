<x-guest-layout :wide="true" title="Contact Desk | Gaoshou Pickleball" label="Contact Desk">
    <div class="w-full">
        <div class="max-w-2xl mb-10">
            <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full border"
                  style="border-color: var(--gz-border); background: var(--gz-surface); color: var(--pop-dark, #A67512);">
                Contact Desk
            </span>
            <h1 class="gz-font-display text-4xl md:text-5xl font-bold mt-3 leading-tight" style="color: var(--gz-ink);">
                Questions? Let's <span style="color: var(--pop-dark, #A67512);">talk.</span>
            </h1>
            <p class="mt-3 text-base" style="color: var(--gz-muted);">
                Court bookings, open play, tournaments, or group events. Send us a message and the front desk will reply as soon as possible.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

            {{-- Info cards --}}
            <div class="lg:col-span-2 space-y-4 min-w-0">
                @php
                    $email = $siteSettings->email_address ?? 'support@gaoshou.ph';
                    $items = [
                        ['Email', $email, 'mailto:' . $email],
                        ['Phone', $siteSettings->contact_number ?? null, null],
                        ['Hours', 'Open Daily: 8:00 AM – 10:00 PM', null],
                        ['Location', 'Davao City, Philippines', null],
                    ];
                @endphp

                @foreach($items as [$label, $value, $href])
                    @if($value)
                        <div class="rounded-2xl border p-5 min-w-0" style="background: var(--gz-surface); border-color: var(--gz-border);">
                            <div class="text-[11px] font-bold uppercase tracking-wider" style="color: var(--pop-dark, #A67512);">{{ $label }}</div>
                            @if($href)
                                <a href="{{ $href }}" class="font-semibold mt-1 block underline underline-offset-4 break-words" style="color: var(--gz-ink);">{{ $value }}</a>
                            @else
                                <div class="font-semibold mt-1" style="color: var(--gz-ink);">{{ $value }}</div>
                            @endif
                        </div>
                    @endif
                @endforeach

                <div class="rounded-2xl border p-5" style="background: var(--gz-surface); border-color: var(--gz-border);">
                    <div class="font-bold text-sm" style="color: var(--gz-ink);">Looking for quick answers?</div>
                    <p class="text-xs mt-1" style="color: var(--gz-muted);">Check our FAQ for payments, rentals, and booking tips.</p>
                    <a href="{{ url('/') }}#faq" class="text-xs font-semibold underline underline-offset-4 mt-2 inline-block" style="color: var(--gz-ink);">
                        Read the FAQ →
                    </a>
                </div>
            </div>

            {{-- Form --}}
            <div class="lg:col-span-3 min-w-0 rounded-3xl border p-6 sm:p-8" style="background: var(--gz-surface); border-color: var(--gz-border);">
                @if(session('status'))
                    <div class="mb-5 rounded-xl px-4 py-3 text-sm font-semibold"
                         style="background: rgba(229,168,35,0.15); color: var(--pop-dark, #A67512);" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.send') }}" class="space-y-5">
                    @csrf

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div class="min-w-0">
                            <label for="name" class="block text-sm font-semibold mb-1.5" style="color: var(--gz-ink);">Your name</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                                   class="w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#E5A823]"
                                   style="background: var(--gz-bg); border-color: var(--gz-border); color: var(--gz-ink);">
                            @error('name')<p class="text-xs mt-1 text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="min-w-0">
                            <label for="email" class="block text-sm font-semibold mb-1.5" style="color: var(--gz-ink);">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                   class="w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#E5A823]"
                                   style="background: var(--gz-bg); border-color: var(--gz-border); color: var(--gz-ink);">
                            @error('email')<p class="text-xs mt-1 text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-semibold mb-1.5" style="color: var(--gz-ink);">Subject</label>
                        <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required
                               class="w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#E5A823]"
                               style="background: var(--gz-bg); border-color: var(--gz-border); color: var(--gz-ink);">
                        @error('subject')<p class="text-xs mt-1 text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-semibold mb-1.5" style="color: var(--gz-ink);">Message</label>
                        <textarea id="message" name="message" rows="6" required
                                  class="w-full rounded-xl border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#E5A823]"
                                  style="background: var(--gz-bg); border-color: var(--gz-border); color: var(--gz-ink);">{{ old('message') }}</textarea>
                        @error('message')<p class="text-xs mt-1 text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="gz-btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>