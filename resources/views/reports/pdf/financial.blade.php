<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $siteName }} - Financial & Revenue Audit Report</title>
    <style>
        @page {
            margin: 28px 32px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #12150f;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 2px solid #12150f;
            padding-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #12150f;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #636b59;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .meta-box {
            text-align: right;
            font-size: 10px;
            color: #636b59;
        }
        .meta-box strong {
            color: #12150f;
        }
        .period-badge {
            display: inline-block;
            background: #E5A823;
            color: #12150F;
            font-weight: 700;
            font-size: 9px;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #12150f;
            margin-top: 18px;
            margin-bottom: 8px;
            border-left: 3px solid #E5A823;
            padding-left: 8px;
        }
        .kpi-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-bottom: 16px;
        }
        .kpi-cell {
            background: #fcfbf7;
            border: 1px solid #e5e3dc;
            border-radius: 6px;
            padding: 10px 12px;
            vertical-align: top;
            width: 25%;
        }
        .kpi-label {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            color: #636b59;
            margin-bottom: 4px;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: 800;
            color: #12150f;
            text-align: right;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            margin-top: 6px;
        }
        .data-table th {
            background: #f3f1e9;
            color: #12150f;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8.5px;
            letter-spacing: 0.5px;
            padding: 7px 6px;
            border-top: 1px solid #d4d0c5;
            border-bottom: 1px solid #d4d0c5;
            text-align: left;
        }
        .data-table td {
            padding: 6px 6px;
            border-bottom: 1px solid #eeebe2;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) td {
            background: #faf9f5;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e5e3dc;
            font-size: 9px;
            color: #8c9480;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div class="logo-title">{{ $siteName }}</div>
                <div class="report-subtitle">Financial & Revenue Audit Report</div>
            </td>
            <td class="meta-box">
                <span class="period-badge">{{ $range['label'] }}</span>
                <div>Generated: <strong>{{ now()->format('M d, Y h:i A') }}</strong></div>
                <div>Audited by: <strong>{{ $user->name }} ({{ strtoupper($user->role) }})</strong></div>
            </td>
        </tr>
    </table>

    <div class="section-title">Revenue & Cashflow Highlights</div>
    <table class="kpi-grid">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Paid Collections</div>
                <div class="kpi-value" style="color: #0b7e45;">PHP {{ number_format($totalPaidAmount, 2) }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Online Collections</div>
                <div class="kpi-value">PHP {{ number_format((float) $onlinePaid->sum('amount'), 2) }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Counter Cash</div>
                <div class="kpi-value">PHP {{ number_format((float) $walkInPaid->sum('amount'), 2) }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Pending Unsettled</div>
                <div class="kpi-value" style="color: #b45309;">PHP {{ number_format($pendingAmount, 2) }}</div>
            </td>
        </tr>
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Paid Transactions</div>
                <div class="kpi-value">{{ number_format($paidCount) }} txns</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Online Volume</div>
                <div class="kpi-value" style="font-size: 13px;">{{ $onlinePaid->count() }} transactions</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Counter Volume</div>
                <div class="kpi-value" style="font-size: 13px;">{{ $walkInPaid->count() }} transactions</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Avg Transaction</div>
                <div class="kpi-value" style="font-size: 13px;">PHP {{ $paidCount > 0 ? number_format($totalPaidAmount / $paidCount, 2) : '0.00' }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Payment Method Breakdown</div>
    <table class="data-table" style="margin-bottom: 16px;">
        <thead>
            <tr>
                <th>Payment Gateway / Channel</th>
                <th class="text-right">Total Collections (PHP)</th>
                <th class="text-center">Transaction Count</th>
                <th class="text-right">Share of Revenue (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($methodBreakdown as $method => $data)
                <tr>
                    <td style="font-weight: 700;">{{ ucfirst((string) $method) }}</td>
                    <td class="text-right" style="font-weight: 700;">PHP {{ number_format($data['total'], 2) }}</td>
                    <td class="text-center">{{ $data['count'] }}</td>
                    <td class="text-right font-mono">{{ $data['percent'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="color: #8c9480;">No payments settled in this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Itemized Transactions Ledger ({{ $payments->count() }} Records)</div>
    @if($payments->isEmpty())
        <p style="text-align: center; color: #8c9480; padding: 20px 0;">No payment records logged for this timeframe.</p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Payment Ref</th>
                    <th>Booking Ref</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Court</th>
                    <th class="text-center">Method</th>
                    <th>Staff / Cashier</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Amount (PHP)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    @php $booking = $payment->booking; @endphp
                    <tr>
                        <td style="font-family: monospace; font-weight: 700;">{{ $payment->ref_num ?? $payment->checkout_session_id ?? ('PAY-' . $payment->id) }}</td>
                        <td style="font-family: monospace;">{{ $booking ? ($booking->reference_number ?? ('BK-' . $booking->id)) : 'N/A' }}</td>
                        <td>{{ $payment->date }}</td>
                        <td>{{ $booking->user->name ?? $booking->customer_name ?? 'Guest Customer' }}</td>
                        <td>{{ $booking->court->court_name ?? 'N/A' }}</td>
                        <td class="text-center">{{ ucfirst($payment->payment_method) }}</td>
                        <td style="font-size: 8px;">
                            @php
                                $staffHandler = $payment->processor?->name 
                                    ?? ($booking?->isWalkIn() ? $booking->statusUpdatedBy?->name : null);
                            @endphp
                            @if($staffHandler)
                                <strong>{{ $staffHandler }}</strong>
                            @elseif($booking?->isWalkIn())
                                <span style="color: #636b59;">Front Desk Staff</span>
                            @else
                                <span style="color: #8c9480;">Online Gateway</span>
                            @endif
                            @if($booking?->statusUpdatedBy && $booking->statusUpdatedBy->name !== $staffHandler)
                                <div style="color: #15803d; font-size: 7.5px;">Marked: {{ $booking->statusUpdatedBy->name }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $payment->payment_status === 'paid' ? 'badge-success' : ($payment->payment_status === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                                {{ ucfirst($payment->payment_status) }}
                            </span>
                        </td>
                        <td class="text-right" style="font-weight: 700;">
                            {{ number_format((float) $payment->amount, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ $siteName }} Financial Audit &middot; Verified Cashflow Ledger &middot; Page 1
    </div>
</body>
</html>
