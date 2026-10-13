<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-2xl" style="color: var(--gz-ink);">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Promos Section --}}
            <div class="mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-display font-bold text-xl" style="color: var(--gz-ink);">All Promos & Special Offers</h3>
                    <a href="{{ route('booking') }}" class="text-sm font-semibold hover:underline" style="color: var(--gz-pop-dark);">
                        Book a Court →
                    </a>
                </div>
                
                @if($events->isEmpty())
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-8 text-center border border-gray-200 dark:border-gray-700">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: rgba(229,168,35,0.12); color: var(--gz-pop-dark);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">No active promos at the moment.</p>
                        <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Check back later for special offers and discounts!</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($events as $event)
                            <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                                @if($event->image)
                                    <img src="{{ asset('storage/' . $event->image) }}" alt="{{ $event->event_title }}" class="w-full h-48 object-cover">
                                @else
                                    <div class="w-full h-48 flex items-center justify-center" style="background: rgba(229,168,35,0.12); color: var(--gz-pop-dark);">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    </div>
                                @endif
                                
                                <div class="p-5">
                                    <h4 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-2">{{ $event->event_title }}</h4>
                                    
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}
                                        </span>
                                    </div>
                                    
                                    @if($event->discount)
                                        <div class="inline-block px-3 py-1 rounded-full text-sm font-bold mb-3" style="background: rgba(229,168,35,0.18); color: var(--gz-pop-dark);">
                                            {{ $event->discount }}% OFF
                                        </div>
                                    @else
                                        <div class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-3 py-1 rounded-full text-sm mb-3">
                                            No discount
                                        </div>
                                    @endif
                                    
                                    @if($event->details)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">
                                            {{ $event->details }}
                                        </p>
                                    @endif
                                    
                                    <a href="{{ route('booking') }}" class="inline-block text-sm font-semibold hover:underline" style="color: var(--gz-pop-dark);">
                                        Book a Court →
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Quick Actions --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="{{ route('booking') }}" class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow block">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Book a Court</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Reserve your slot</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('bookings.index') }}" class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow block">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">My Bookings</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">View your reservations</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('profile.edit') }}" class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow block">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Profile</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Manage your account</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
