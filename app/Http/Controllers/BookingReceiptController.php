<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BookingReceiptController extends Controller
{
    /**
     * Display or return data for an official booking receipt.
     */
    public function show(Request $request, Booking $booking)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Security authorization check:
        // Admin, Manager, and Staff can view any booking receipt.
        // A player can strictly only view their own receipt.
        $canAccess = $user->isAdmin()
            || $user->hasRole('manager')
            || $user->hasRole('staff')
            || $booking->user_id === $user->id;

        if (! $canAccess) {
            abort(403, 'Unauthorized access to this booking receipt.');
        }

        $booking->load(['court', 'user', 'payments', 'event']);

        $firstPayment = $booking->payments->first();
        $refNum = $firstPayment?->ref_num ?: ('BK-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT));

        // Group sibling bookings if they were booked in the same transaction with the same payment reference
        $transactionBookings = $firstPayment?->ref_num
            ? Booking::whereHas('payments', fn ($q) => $q->where('ref_num', $firstPayment->ref_num))
                ->with(['court', 'payments', 'event'])
                ->orderBy('date')
                ->orderBy('start_time')
                ->get()
            : collect([$booking]);

        if ($transactionBookings->isEmpty()) {
            $transactionBookings = collect([$booking]);
        }

        // Financial calculations
        $subtotal = 0.0;
        $items = [];

        foreach ($transactionBookings as $tb) {
            $start = Carbon::parse($tb->start_time);
            $end = Carbon::parse($tb->end_time);
            $hours = max(1, abs($start->diffInMinutes($end)) / 60);
            $rate = (float) ($tb->court?->price_per_hour ?? 0);
            $lineBase = $rate * $hours;
            $subtotal += $lineBase;

            $items[] = [
                'id' => $tb->id,
                'court_name' => $tb->court?->court_name ?? 'Court',
                'court_size' => $tb->court?->size ?: 'Regular (13.41m x 6.10m)',
                'date' => Carbon::parse($tb->date)->format('D, M j, Y'),
                'time_window' => $start->format('g:i A') . ' – ' . $end->format('g:i A'),
                'hours' => $hours,
                'rate' => $rate,
                'amount' => (float) ($tb->payments->first()?->amount ?? $lineBase),
                'status' => $tb->booking_status,
                'event_title' => $tb->event?->event_title,
                'event_discount' => $tb->event?->discount ? (float) $tb->event->discount : 0.0,
            ];
        }

        // Check for discount from event
        $discountPercent = (float) ($booking->event?->discount ?? 0);
        $discountAmount = 0.0;
        if ($discountPercent > 0) {
            $discountAmount = round($subtotal * ($discountPercent / 100), 2);
        }

        // Check total from payments table if available
        $totalPaid = (float) $transactionBookings->flatMap->payments->sum('amount');
        if ($totalPaid <= 0) {
            $totalPaid = max(0, $subtotal - $discountAmount);
        }

        $rawMethod = $firstPayment?->payment_method ?? 'online';
        $methodLabel = match (true) {
            str_starts_with($rawMethod, 'paymongo_') => 'Pay Online (' . strtoupper(str_replace('paymongo_', '', $rawMethod)) . ')',
            $rawMethod === 'paymongo'                => 'Pay Online (PayMongo)',
            $rawMethod === 'cash'                    => 'Cash at Counter',
            $rawMethod === 'gcash'                   => 'GCash',
            $rawMethod === 'card'                    => 'Debit/Credit Card',
            default                                  => ucfirst(str_replace('_', ' ', $rawMethod)),
        };

        $paymentStatus = $firstPayment?->payment_status ?? ($booking->booking_status === 'confirmed' ? 'paid' : 'pending');

        // Backfill paymongo_payment_id on the fly if checkout_session_id exists but payment_id was not yet stored
        $paymongoPaymentId = $firstPayment?->paymongo_payment_id;
        if (! $paymongoPaymentId && $firstPayment?->checkout_session_id) {
            try {
                $payMongoService = app(\App\Services\PayMongoService::class);
                if ($payMongoService->isConfigured()) {
                    $sessionData = $payMongoService->getCheckoutSession($firstPayment->checkout_session_id);
                    $details = $payMongoService->extractPaymentDetails($sessionData);
                    if (! empty($details['payment_id'])) {
                        $paymongoPaymentId = $details['payment_id'];
                        $firstPayment->update(['paymongo_payment_id' => $paymongoPaymentId]);
                    }
                }
            } catch (\Throwable $e) {
                // Ignore if offline or test session unavailable
            }
        }

        $siteSettings = SiteSettings::first();

        $receiptData = [
            'ref_num' => $refNum,
            'paymongo_payment_id' => $paymongoPaymentId ?: $firstPayment?->checkout_session_id,
            'booking_id' => $booking->id,
            'issued_at' => $booking->created_at->format('M d, Y · g:i A'),
            'customer_name' => $booking->user?->name ?? 'Guest Customer',
            'customer_email' => $booking->user?->email ?? 'N/A',
            'booking_type' => $booking->isWalkIn() ? 'Walk-In Front Desk' : 'Online Booking',
            'overall_status' => $booking->booking_status,
            'payment_method' => $methodLabel,
            'payment_status' => $paymentStatus,
            'cash_tendered' => $firstPayment?->cash_tendered !== null ? (float) $firstPayment->cash_tendered : null,
            'change_amount' => $firstPayment?->change_amount !== null ? (float) $firstPayment->change_amount : null,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalPaid,
            'event_title' => $booking->event?->event_title,
            'system_name' => $siteSettings?->system_name ?? 'Gaoshou Pickleball',
            'business_name' => $siteSettings?->business_name ?? 'Gaoshou Pickleball Complex',
            'tagline' => $siteSettings?->tagline ?? 'Book Your Court, Rally with Ease',
            'business_address' => $siteSettings?->business_address ?? 'Davao City, Philippines',
            'contact_number' => $siteSettings?->contact_number ?? '+63 (082) 000-0000',
            'email_address' => $siteSettings?->email_address ?? 'support@gaoshou.ph',
            'logo_url' => $siteSettings?->logo ? asset('storage/' . $siteSettings->logo) : asset('images/logo.png'),
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($receiptData);
        }

        return view('bookings.receipt', [
            'receipt' => $receiptData,
            'booking' => $booking,
            'siteSettings' => $siteSettings,
        ]);
    }
}
