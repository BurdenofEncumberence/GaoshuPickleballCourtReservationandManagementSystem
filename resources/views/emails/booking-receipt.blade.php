<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation & Receipt</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #F4F1E9;
            color: #12150F;
            margin: 0;
            padding: 30px 15px;
            -webkit-text-size-adjust: none;
        }
        .container {
            max-width: 620px;
            margin: 0 auto;
            background: #FCFBF7;
            border: 2px solid #12150F;
            border-radius: 16px;
            box-shadow: 4px 6px 0px #12150F;
            padding: 36px 32px;
        }
        .header {
            border-bottom: 2px dashed #E4E0D4;
            padding-bottom: 24px;
            margin-bottom: 24px;
        }
        .brand-badge {
            background: #12150F;
            color: #E5A823;
            font-weight: 800;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 15px;
            letter-spacing: 1px;
            display: inline-block;
        }
        .receipt-title {
            font-size: 24px;
            font-weight: 800;
            margin: 16px 0 6px 0;
            color: #12150F;
            letter-spacing: -0.02em;
        }
        .subtitle {
            font-size: 14px;
            color: #565A4E;
            margin: 0;
        }
        .meta-grid {
            width: 100%;
            margin-bottom: 24px;
            border-collapse: collapse;
        }
        .meta-grid td {
            padding: 6px 0;
            font-size: 14px;
            vertical-align: top;
        }
        .meta-label {
            color: #565A4E;
            font-weight: 600;
            width: 35%;
        }
        .meta-val {
            color: #12150F;
            font-weight: 700;
        }
        .badge-ref {
            display: inline-block;
            background: #F4F1E9;
            border: 1px solid #12150F;
            padding: 3px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 13px;
            font-weight: 700;
        }
        .badge-status {
            display: inline-block;
            background: #E5A823;
            color: #12150F;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 24px;
        }
        .items-table th {
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #565A4E;
            border-bottom: 2px solid #12150F;
            padding: 8px 6px;
        }
        .items-table td {
            font-size: 14px;
            border-bottom: 1px solid #E4E0D4;
            padding: 12px 6px;
            color: #12150F;
        }
        .text-right {
            text-align: right;
        }
        .total-box {
            background: #F4F1E9;
            border: 1px solid #E4E0D4;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 24px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 8px;
            color: #565A4E;
        }
        .total-row.grand {
            font-size: 18px;
            font-weight: 800;
            color: #12150F;
            border-top: 2px solid #12150F;
            padding-top: 10px;
            margin-top: 10px;
            margin-bottom: 0;
        }
        .instructions {
            background: #FCFBF7;
            border: 1px solid #E4E0D4;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .instructions h4 {
            margin: 0 0 8px 0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #12150F;
        }
        .instructions ul {
            margin: 0;
            padding-left: 18px;
            font-size: 13px;
            color: #565A4E;
            line-height: 1.6;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #7A7E73;
            border-top: 1px solid #E4E0D4;
            padding-top: 18px;
        }
        .btn-view {
            display: inline-block;
            background: #E5A823;
            color: #12150F !important;
            text-decoration: none !important;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 24px;
            border-radius: 999px;
            border: 2px solid #12150F;
            box-shadow: 2px 3px 0px #12150F;
            margin: 10px 0 20px 0;
        }
        .btn-view,
        .btn-view:link,
        .btn-view:visited,
        .btn-view:hover,
        .btn-view:active {
            color: #12150F !important;
            text-decoration: none !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Brand Header -->
        <div class="header">
            <span class="brand-badge">{{ $siteSettings?->system_name ?? 'Gaoshou Pickleball' }}</span>
            <h1 class="receipt-title">Booking Confirmation & Receipt</h1>
            <p class="subtitle">Thank you for your reservation! Your court booking has been confirmed.</p>
        </div>

        <!-- Meta Info -->
        <table class="meta-grid">
            <tr>
                <td class="meta-label">Customer Name:</td>
                <td class="meta-val">{{ $user->name }}</td>
            </tr>
            <tr>
                <td class="meta-label">Customer Email:</td>
                <td class="meta-val">{{ $user->email }}</td>
            </tr>
            <tr>
                <td class="meta-label">Transaction Reference:</td>
                <td class="meta-val"><span class="badge-ref">{{ $refNum }}</span></td>
            </tr>
            <tr>
                <td class="meta-label">Payment Method:</td>
                <td class="meta-val">{{ strtoupper($paymentMethod) }}</td>
            </tr>
            <tr>
                <td class="meta-label">Booking Status:</td>
                <td class="meta-val"><span class="badge-status">CONFIRMED</span></td>
            </tr>
            <tr>
                <td class="meta-label">Date Issued:</td>
                <td class="meta-val">{{ now()->format('F j, Y \a\t g:i A') }}</td>
            </tr>
            @php
                $firstB = collect($bookings)->first();
                $staffHandler = $firstB?->payments?->first(fn ($p) => $p->processor !== null)?->processor?->name
                    ?? $firstB?->statusUpdatedBy?->name
                    ?? ($firstB?->isWalkIn() ? 'Front Desk Staff' : null);
            @endphp
            @if($staffHandler)
                <tr>
                    <td class="meta-label">Handled By Staff:</td>
                    <td class="meta-val">{{ $staffHandler }}</td>
                </tr>
            @endif
        </table>

        <!-- Booked Courts Table -->
        <h3 style="font-size: 16px; margin: 0 0 10px 0;">Reserved Court Details</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Court</th>
                    <th>Date</th>
                    <th>Time Slot</th>
                    <th class="text-right">Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                    @php
                        $start = \Carbon\Carbon::parse($booking->start_time);
                        $end = \Carbon\Carbon::parse($booking->end_time);
                        $hours = max(1, $start->diffInMinutes($end) / 60);
                        $courtName = $booking->court?->court_name ?? 'Court';
                        $price = $booking->court ? ($booking->court->price_per_hour * $hours) : 0;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $courtName }}</strong>
                            @if($booking->court?->size)
                                <br><span style="font-size: 11px; color: #565A4E;">{{ $booking->court->size }}</span>
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($booking->date)->format('M d, Y') }}</td>
                        <td>{{ $start->format('g:i A') }} – {{ $end->format('g:i A') }} ({{ $hours }} hr{{ $hours > 1 ? 's' : '' }})</td>
                        <td class="text-right">₱{{ number_format($price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Financial Totals -->
        <div class="total-box">
            <div class="total-row">
                <span>Subtotal</span>
                <span>₱{{ number_format($subtotal, 2) }}</span>
            </div>
            @if($discountPercent > 0)
                <div class="total-row" style="color: #A67512; font-weight: 600;">
                    <span>Promotion Discount ({{ $discountPercent }}% OFF{{ $event ? ' - ' . $event->event_title : '' }})</span>
                    <span>- ₱{{ number_format($discountAmount, 2) }}</span>
                </div>
            @endif
            <div class="total-row grand">
                <span>Total Amount Paid</span>
                <span>₱{{ number_format($totalAmount, 2) }}</span>
            </div>
        </div>

        <!-- Check-in Guidelines -->
        <div class="instructions">
            <h4>Check-in & Match Day Guidelines</h4>
            <ul>
                <li>Please arrive at the facility <strong>10–15 minutes</strong> before your scheduled court time.</li>
                <li>Present this email receipt or your reference code <strong>{{ $refNum }}</strong> at the front desk.</li>
                <li>Non-marking sports or court shoes are required on all championship courts.</li>
                <li>Paddles and balls are available at the front desk if needed.</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="{{ route('bookings.index') }}" class="btn-view" style="display: inline-block; background-color: #E5A823; color: #12150F !important; text-decoration: none !important; font-weight: 700; font-size: 14px; padding: 12px 24px; border-radius: 999px; border: 2px solid #12150F; box-shadow: 2px 3px 0px #12150F; margin: 10px 0 20px 0;"><span style="color: #12150F !important; text-decoration: none !important;">View My Bookings Online &rarr;</span></a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 6px 0;"><strong>{{ $siteSettings?->business_name ?? 'Gaoshou Pickleball Complex' }}</strong></p>
            <p style="margin: 0 0 6px 0;">{{ $siteSettings?->business_address ?? 'Davao City, Philippines' }}</p>
            <p style="margin: 0;">Need to reschedule or have questions? Contact support at {{ $siteSettings?->email_address ?? 'support@gaoshou.ph' }}</p>
        </div>
    </div>
</body>
</html>
