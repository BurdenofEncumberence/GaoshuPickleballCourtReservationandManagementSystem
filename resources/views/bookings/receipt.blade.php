<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Official Receipt · {{ $receipt['ref_num'] }} · {{ $receipt['system_name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css'])

    <style>
        @media print {
            body {
                background: #FFFFFF !important;
                color: #000000 !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .receipt-sheet {
                box-shadow: none !important;
                border: 1px solid #12150F !important;
                max-width: 100% !important;
                margin: 0 !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-[#F3E5C8]/40 text-[#1A1611] font-sans antialiased min-h-screen py-8 px-4 sm:px-6">

    <!-- Top Action Bar (hidden on print) -->
    <div class="max-w-2xl mx-auto mb-6 flex items-center justify-between no-print gap-3">
        <button
            type="button"
            onclick="window.history.back()"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-black/20 hover:border-black/40 text-xs font-semibold bg-white shadow-sm transition"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Back</span>
        </button>

        <div class="flex items-center gap-2">
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg font-bold text-xs bg-[#E5A823] text-[#12150F] shadow-sm hover:bg-[#D49B1F] transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                <span>Print / Save as PDF</span>
            </button>
        </div>
    </div>

    <!-- Official Receipt Document Sheet -->
    <main class="max-w-2xl mx-auto bg-[#FCFBF7] border-2 border-[#12150F] rounded-2xl p-6 sm:p-8 shadow-[6px_8px_0_#12150F] receipt-sheet">

        <!-- Receipt Header -->
        <header class="border-b-2 border-dashed border-[#12150F]/20 pb-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img
                        src="{{ $receipt['logo_url'] }}"
                        alt="{{ $receipt['system_name'] }}"
                        class="h-14 sm:h-16 w-auto max-h-16 max-w-[180px] object-contain rounded-xl shrink-0"
                    >
                    <div>
                        <h1 class="font-display font-extrabold text-xl sm:text-2xl tracking-tight leading-none text-[#12150F]">
                            {{ $receipt['system_name'] }}
                        </h1>
                        <p class="text-xs text-[#565A4E] mt-1">
                            {{ $receipt['business_name'] }}
                        </p>
                        <p class="text-[11px] text-[#7A7E73]">
                            {{ $receipt['business_address'] }} · {{ $receipt['contact_number'] }}
                        </p>
                    </div>
                </div>

                <div class="text-left sm:text-right">
                    <span class="inline-block px-3 py-1 bg-[#12150F] text-[#E5A823] text-xs font-mono font-black uppercase tracking-wider rounded-md">
                        OFFICIAL RECEIPT
                    </span>
                    <div class="font-mono font-bold text-sm text-[#12150F] mt-2">
                        REF: {{ $receipt['ref_num'] }}
                    </div>
                    @if(!empty($receipt['paymongo_payment_id']))
                        <div class="font-mono text-[11px] font-bold text-[#B87D09] dark:text-[#E5A823] mt-0.5">
                            PayMongo ID: {{ $receipt['paymongo_payment_id'] }}
                        </div>
                    @endif
                    <div class="text-[11px] text-[#565A4E] mt-0.5">
                        Issued: {{ $receipt['issued_at'] }}
                    </div>
                    @if(!empty($receipt['processed_by_name']))
                        <div class="text-[11px] font-semibold text-[#12150F] mt-0.5">
                            Handled By: <span class="font-bold">{{ $receipt['processed_by_name'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </header>

        <!-- Customer & Settlement Meta -->
        <section class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-6 mb-6 border-b border-[#12150F]/10 text-xs">
            <div class="space-y-1">
                <span class="text-[10px] uppercase font-bold tracking-wider text-[#7A7E73] block">Billed To</span>
                <div class="font-bold text-sm text-[#12150F]">{{ $receipt['customer_name'] }}</div>
                <div class="text-[#565A4E]">{{ $receipt['customer_email'] }}</div>
                <div class="inline-block mt-1 px-2 py-0.5 bg-black/5 text-[#12150F] font-semibold text-[10px] rounded">
                    Channel: {{ $receipt['booking_type'] }}
                </div>
            </div>

            <div class="space-y-1 sm:text-right">
                <span class="text-[10px] uppercase font-bold tracking-wider text-[#7A7E73] block">Payment Information</span>
                <div class="font-semibold text-sm text-[#12150F]">
                    Method: {{ $receipt['payment_method'] }}
                </div>
                @if(!empty($receipt['processed_by_name']))
                    <div class="text-[11px] text-[#565A4E]">
                        Handled By Staff: <span class="font-bold text-[#12150F]">{{ $receipt['processed_by_name'] }}</span>
                    </div>
                @endif
                @if(!empty($receipt['paymongo_payment_id']))
                    <div class="font-mono text-[11px] text-[#12150F]">
                        PayMongo Payment ID: <span class="font-bold">{{ $receipt['paymongo_payment_id'] }}</span>
                    </div>
                @endif
                <div class="flex items-center sm:justify-end gap-2 pt-1">
                    <span class="text-[11px] text-[#565A4E]">Payment Status:</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $receipt['payment_status'] === 'paid' ? 'bg-[#E5A823] text-[#12150F]' : 'bg-amber-200 text-amber-900' }}">
                        {{ strtoupper($receipt['payment_status']) }}
                    </span>
                </div>
                <div class="flex items-center sm:justify-end gap-2">
                    <span class="text-[11px] text-[#565A4E]">Booking Status:</span>
                    <span class="font-bold uppercase text-[11px] text-[#12150F]">
                        {{ ucfirst($receipt['overall_status']) }}
                    </span>
                </div>
                @if(!empty($receipt['status_updated_by_name']))
                    <div class="text-[11px] text-[#565A4E]">
                        Attendance Handled By: <span class="font-semibold text-[#12150F]">{{ $receipt['status_updated_by_name'] }}</span>
                    </div>
                @endif
            </div>
        </section>

        <!-- Itemized Reservations Table -->
        <section class="mb-6">
            <h2 class="text-xs font-bold uppercase tracking-wider text-[#7A7E73] mb-3">
                Itemized Court Reservations
            </h2>

            <div class="border border-[#12150F]/15 rounded-xl overflow-hidden">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-[#12150F]/5 border-b border-[#12150F]/15 text-[#565A4E] font-semibold uppercase text-[10px] tracking-wider">
                        <tr>
                            <th class="p-3">Court & Specification</th>
                            <th class="p-3">Date & Time</th>
                            <th class="p-3 text-right">Hours</th>
                            <th class="p-3 text-right">Rate / hr</th>
                            <th class="p-3 text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#12150F]/10">
                        @foreach($receipt['items'] as $item)
                            <tr>
                                <td class="p-3">
                                    <div class="font-bold text-sm text-[#12150F]">{{ $item['court_name'] }}</div>
                                    <div class="text-[10px] text-[#565A4E] mt-0.5">{{ $item['court_size'] }}</div>
                                    @if($item['event_title'])
                                        <div class="text-[10px] text-emerald-700 font-semibold mt-0.5">
                                            Event: {{ $item['event_title'] }} ({{ $item['event_discount'] }}% discount)
                                        </div>
                                    @endif
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    <div class="font-semibold text-[#12150F]">{{ $item['date'] }}</div>
                                    <div class="text-[#565A4E]">{{ $item['time_window'] }}</div>
                                </td>
                                <td class="p-3 text-right font-mono">{{ number_format($item['hours'], 1) }}h</td>
                                <td class="p-3 text-right font-mono">₱{{ number_format($item['rate'], 2) }}</td>
                                <td class="p-3 text-right font-mono font-bold text-[#12150F]">
                                    ₱{{ number_format($item['amount'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Financial Summary Totals -->
        <section class="bg-[#12150F]/5 rounded-xl p-4 mb-6 border border-[#12150F]/10 text-xs">
            <div class="space-y-2">
                <div class="flex items-center justify-between text-[#565A4E]">
                    <span>Subtotal</span>
                    <span class="font-mono">₱{{ number_format($receipt['subtotal'], 2) }}</span>
                </div>

                @if($receipt['discount_amount'] > 0)
                    <div class="flex items-center justify-between text-[#A67512] font-semibold">
                        <span>Event Promotion Discount ({{ $receipt['discount_percent'] }}%)</span>
                        <span class="font-mono">-₱{{ number_format($receipt['discount_amount'], 2) }}</span>
                    </div>
                @endif

                <div class="border-t-2 border-[#12150F] pt-2 flex items-center justify-between text-base font-extrabold text-[#12150F]">
                    <span>Total Amount Paid</span>
                    <span class="font-mono text-lg text-[#12150F]">₱{{ number_format($receipt['total_amount'], 2) }}</span>
                </div>

                @if($receipt['cash_tendered'] !== null)
                    <div class="pt-2 border-t border-dashed border-[#12150F]/20 space-y-1.5">
                        <div class="flex items-center justify-between text-[#565A4E]">
                            <span>Cash Tendered</span>
                            <span class="font-mono font-bold text-[#12150F]">₱{{ number_format($receipt['cash_tendered'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between font-bold text-emerald-900 bg-emerald-50 px-2 py-1.5 rounded border border-emerald-200">
                            <span>Change Given</span>
                            <span class="font-mono font-extrabold text-sm">₱{{ number_format($receipt['change_amount'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <!-- Non-Refundable Notice Banner -->
        <div class="mb-6 p-3 rounded-xl border border-dashed border-[#12150F]/30 bg-[#12150F]/[0.02] text-center text-[11px]">
            <span class="font-black tracking-wider uppercase text-[#12150F] block">
                Non-Refundable Transaction · All Court Bookings Are Final
            </span>
            <span class="text-[#565A4E] text-[10px]">
                Please review court schedule and times carefully. Cancellations and no-shows are not eligible for cash or digital refunds.
            </span>
        </div>

        <!-- Terms & Verification Badge -->
        <footer class="pt-4 border-t border-[#12150F]/15 flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] text-[#7A7E73]">
            <div class="space-y-1 text-center sm:text-left">
                <p class="font-bold text-[#12150F]">Check-In Guidelines:</p>
                <p>Present this reference code at reception 10 minutes prior to scheduled match time.</p>
                <p>Non-marking court shoes are required on all competition acrylic surfaces.</p>
            </div>

            <div class="text-center sm:text-right shrink-0">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#E5A823]/15 text-[#12150F] border border-[#E5A823]/40 rounded-full font-bold text-[10px]">
                    <svg class="w-3.5 h-3.5 text-[#A67512]" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span>VERIFIED OFFICIAL RECEIPT</span>
                </div>
                @if(!empty($receipt['processed_by_name']))
                    <div class="text-[10px] text-[#12150F] font-semibold mt-1">
                        Handled by Staff: <span class="font-bold">{{ $receipt['processed_by_name'] }}</span>
                    </div>
                @endif
                <div class="text-[9px] text-[#7A7E73] mt-0.5 font-mono">
                    {{ $receipt['system_name'] }} · SECURE TRANSACTION
                </div>
            </div>
        </footer>

    </main>

    @if(request()->boolean('autoprint') || request()->boolean('print'))
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    window.print();
                }, 300);
            });
        </script>
    @endif

</body>
</html>
