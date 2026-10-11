<div
    x-data="{
        isOpen: false,
        loading: false,
        error: null,
        receipt: null,

        openReceipt(detail) {
            const bookingId = (typeof detail === 'object' && detail !== null) ? (detail.bookingId || detail.id) : detail;
            if (!bookingId) return;

            this.isOpen = true;
            this.loading = true;
            this.error = null;
            this.receipt = null;

            fetch(`/bookings/${bookingId}/receipt`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Receipt could not be loaded (' + res.status + ')');
                }
                return res.json();
            })
            .then(data => {
                this.receipt = data;
                this.loading = false;
            })
            .catch(err => {
                console.error(err);
                this.error = err.message || 'Unable to retrieve receipt data.';
                this.loading = false;
            });
        },

        close() {
            this.isOpen = false;
        },

        formatMoney(amount) {
            return Number(amount || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }"
    @open-booking-receipt.window="openReceipt($event.detail)"
    @view-receipt.window="openReceipt($event.detail)"
    @keydown.escape.window="close()"
    x-cloak
>
    <!-- Modal Backdrop & Container -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-start sm:items-center justify-center p-4 sm:p-6"
        style="z-index: 9999;"
        role="dialog"
        aria-modal="true"
        aria-labelledby="receipt-modal-title"
    >
        <div
            @click.outside="close()"
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-[#FCFBF7] rounded-2xl border-2 border-[#12150F] shadow-[8px_8px_0_#12150F] my-4"
        >
            <!-- Loading State -->
            <div x-show="loading" class="p-12 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-solid border-[#12150F] border-r-transparent mb-4"></div>
                <p class="font-bold text-sm text-[#12150F]">Loading official receipt...</p>
                <p class="text-xs text-[#565A4E] mt-1">Fetching transaction and court settlement details</p>
            </div>

            <!-- Error State -->
            <div x-show="!loading && error" class="p-8 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-red-100 border border-red-300 text-red-700 flex items-center justify-center font-bold">
                    !
                </div>
                <h3 class="font-bold text-base text-[#12150F]">Receipt Unavailable</h3>
                <p class="text-xs text-red-600 mt-1" x-text="error"></p>
                <button
                    type="button"
                    @click="close()"
                    class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-[#12150F] text-white hover:bg-black transition"
                >
                    Close Window
                </button>
            </div>

            <!-- Loaded Content -->
            <div x-show="!loading && receipt && !error" class="max-h-[85vh] overflow-y-auto">
                <!-- Modal Top Controls -->
                <div class="sticky top-0 z-10 bg-[#FCFBF7]/95 backdrop-blur-xs border-b border-[#12150F]/15 px-6 py-3.5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-block px-2.5 py-1 bg-[#12150F] text-[#E5A823] text-[10px] font-mono font-black uppercase tracking-wider rounded">
                            OFFICIAL RECEIPT
                        </span>
                        <span class="font-mono font-bold text-xs text-[#12150F]" x-text="receipt ? ('#' + receipt.ref_num) : ''"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            :href="receipt ? `/bookings/${receipt.booking_id}/receipt?autoprint=1` : '#'"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-[#E5A823] text-[#12150F] border border-[#12150F] shadow-[2px_2px_0_#12150F] hover:bg-[#D49B1F] transition"
                            title="Open standalone printable receipt"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            <span>Print / View Full</span>
                        </a>

                        <button
                            type="button"
                            @click="close()"
                            class="p-1.5 rounded-lg border border-black/20 hover:bg-black/5 text-[#12150F] transition"
                            aria-label="Close"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Receipt Body Sheet -->
                <div class="p-6 sm:p-8 space-y-6">
                    <!-- Brand & Reference Header -->
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 border-b-2 border-dashed border-[#12150F]/20 pb-5">
                        <div class="flex items-center gap-3">
                            <template x-if="receipt && receipt.logo_url">
                                <img
                                    :src="receipt.logo_url"
                                    :alt="receipt.system_name"
                                    class="h-10 w-10 rounded-xl object-contain border border-black/10 shrink-0"
                                >
                            </template>
                            <div>
                                <h2 class="font-display font-extrabold text-lg tracking-tight text-[#12150F]" x-text="receipt?.system_name"></h2>
                                <p class="text-xs text-[#565A4E]" x-text="receipt?.business_name"></p>
                                <p class="text-[11px] text-[#7A7E73]" x-text="(receipt?.business_address || '') + ' · ' + (receipt?.contact_number || '')"></p>
                            </div>
                        </div>

                        <div class="text-left sm:text-right">
                            <div class="font-mono font-bold text-sm text-[#12150F]" x-text="'REF: ' + (receipt?.ref_num || '')"></div>
                            <template x-if="receipt && receipt.paymongo_payment_id">
                                <div class="font-mono text-[11px] font-semibold text-emerald-800 mt-0.5" x-text="'PayMongo ID: ' + receipt.paymongo_payment_id"></div>
                            </template>
                            <div class="text-xs text-[#565A4E] mt-0.5" x-text="'Issued: ' + (receipt?.issued_at || '')"></div>
                        </div>
                    </div>

                    <!-- Customer & Transaction Metadata -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-[#12150F]/[0.03] p-4 rounded-xl border border-[#12150F]/10 text-xs">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[#7A7E73] tracking-wider block">Customer Details</span>
                            <div class="font-bold text-[#12150F] text-sm mt-0.5" x-text="receipt?.customer_name"></div>
                            <div class="text-[#565A4E]" x-text="receipt?.customer_email"></div>
                        </div>
                        <div class="sm:text-right">
                            <span class="text-[10px] uppercase font-bold text-[#7A7E73] tracking-wider block">Reservation Channel</span>
                            <div class="font-semibold text-[#12150F] mt-0.5" x-text="receipt?.booking_type"></div>
                            <div class="text-[#565A4E] mt-0.5">
                                Method: <span class="font-medium text-[#12150F]" x-text="receipt?.payment_method"></span>
                            </div>
                            <template x-if="receipt && receipt.paymongo_payment_id">
                                <div class="font-mono text-[11px] text-[#565A4E] mt-0.5">
                                    PayMongo ID: <span class="font-bold text-[#12150F]" x-text="receipt.paymongo_payment_id"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Itemized Court Sessions -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-[#7A7E73] mb-3">Reserved Court Sessions</h4>
                        <div class="border border-[#12150F]/20 rounded-xl overflow-hidden divide-y divide-[#12150F]/10">
                            <template x-for="(item, idx) in (receipt?.items || [])" :key="item.id || idx">
                                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white">
                                    <div class="flex items-start gap-3">
                                        <div class="w-6 h-6 rounded-md bg-[#12150F]/5 text-[#12150F] flex items-center justify-center font-bold text-xs shrink-0 mt-0.5" x-text="idx + 1"></div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-bold text-sm text-[#12150F]" x-text="item.court_name"></span>
                                                <span class="px-1.5 py-0.5 bg-[#12150F]/5 border border-black/10 rounded text-[10px] font-medium text-[#565A4E]" x-text="item.court_size"></span>
                                                <template x-if="item.event_title">
                                                    <span class="px-1.5 py-0.5 bg-[#E5A823]/20 text-[#12150F] rounded text-[10px] font-bold" x-text="item.event_title + (item.event_discount > 0 ? (' · ' + item.event_discount + '% OFF') : '')"></span>
                                                </template>
                                            </div>
                                            <div class="text-xs text-[#565A4E] mt-0.5">
                                                <span class="font-semibold text-[#12150F]" x-text="item.date"></span> ·
                                                <span x-text="item.time_window"></span>
                                                <span class="opacity-75" x-text="'(' + item.hours + ' hr @ ₱' + formatMoney(item.rate) + '/hr)'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-left sm:text-right pl-9 sm:pl-0">
                                        <div class="font-mono font-bold text-sm text-[#12150F]" x-text="'₱' + formatMoney(item.amount)"></div>
                                        <span
                                            class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider mt-0.5"
                                            :class="item.status === 'confirmed' ? 'bg-emerald-100 text-emerald-800' : (item.status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700')"
                                            x-text="item.status"
                                        ></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Totals Breakdown -->
                    <div class="border-t-2 border-[#12150F]/15 pt-4 space-y-2 text-xs">
                        <div class="flex items-center justify-between text-[#565A4E]">
                            <span>Subtotal</span>
                            <span class="font-mono font-semibold text-[#12150F]" x-text="'₱' + formatMoney(receipt?.subtotal)"></span>
                        </div>

                        <template x-if="receipt && receipt.discount_amount > 0">
                            <div class="flex items-center justify-between text-emerald-700 font-semibold">
                                <span x-text="'Promo Discount (' + receipt.discount_percent + '%)'"></span>
                                <span class="font-mono" x-text="'-₱' + formatMoney(receipt.discount_amount)"></span>
                            </div>
                        </template>

                        <div class="flex items-center justify-between text-base font-bold text-[#12150F] pt-2 border-t border-[#12150F]/15">
                            <span>Total Settlement Paid</span>
                            <span class="font-mono text-lg text-[#12150F]" x-text="'₱' + formatMoney(receipt?.total_amount)"></span>
                        </div>

                        <template x-if="receipt && receipt.cash_tendered !== null">
                            <div class="pt-2 border-t border-dashed border-[#12150F]/20 space-y-1">
                                <div class="flex items-center justify-between text-[#565A4E]">
                                    <span>Cash Tendered</span>
                                    <span class="font-mono font-bold text-[#12150F]" x-text="'₱' + formatMoney(receipt.cash_tendered)"></span>
                                </div>
                                <div class="flex items-center justify-between font-bold text-emerald-900 bg-emerald-50 px-2 py-1 rounded border border-emerald-200">
                                    <span>Change Returned</span>
                                    <span class="font-mono font-extrabold text-sm" x-text="'₱' + formatMoney(receipt.change_amount || 0)"></span>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center justify-between text-[11px] text-[#7A7E73] pt-1">
                            <span>Payment Status</span>
                            <span class="font-bold uppercase tracking-wider" :class="receipt?.payment_status === 'paid' ? 'text-emerald-700' : 'text-amber-700'" x-text="receipt?.payment_status"></span>
                        </div>
                    </div>

                    <!-- Non-Refundable Notice -->
                    <div class="p-2.5 rounded-lg border border-dashed border-[#12150F]/20 bg-[#12150F]/[0.02] text-center text-[10px]">
                        <span class="font-bold text-[#12150F] block">NON-REFUNDABLE TRANSACTION</span>
                        <span class="text-[#7A7E73]">Court reservations are final. No refunds on cancellations or no-shows.</span>
                    </div>

                    <!-- Footer Note -->
                    <div class="pt-4 border-t border-dashed border-[#12150F]/20 text-center text-[11px] text-[#7A7E73]">
                        <p class="font-medium text-[#565A4E]" x-text="'Thank you for playing at ' + (receipt?.business_name || 'Gaoshou Pickleball') + '!'">Thank you for playing at Gaoshou Pickleball!</p>
                        <p class="mt-0.5">Please present this receipt reference or digital copy at the front desk if requested.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
