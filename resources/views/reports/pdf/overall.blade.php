<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $siteName }} - Overall Operations Report</title>
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
        .badge-neutral { background: #f3f4f6; color: #374151; }
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
                <div class="report-subtitle">Overall Operational & Performance Report</div>
            </td>
            <td class="meta-box">
                <span class="period-badge">{{ $range['label'] }}</span>
                <div>Generated: <strong>{{ now()->format('M d, Y h:i A') }}</strong></div>
                <div>Audited by: <strong>{{ $user->name }} ({{ strtoupper($user->role) }})</strong></div>
            </td>
        </tr>
    </table>

    <div class="section-title">Executive Summary Metrics</div>
    <table class="kpi-grid">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Total Bookings</div>
                <div class="kpi-value">{{ number_format($bookings->count()) }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-value" style="color: #0b7e45;">PHP {{ number_format($totalRevenue, 2) }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Hours Utilized</div>
                <div class="kpi-value">{{ number_format($bookedHours, 1) }}h</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Utilization Rate</div>
                <div class="kpi-value">{{ $utilizationRate }}%</div>
            </td>
        </tr>
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Online vs Walk-In</div>
                <div class="kpi-value" style="font-size: 13px;">{{ $bookings->where('booking_type', 'online')->count() }} / {{ $bookings->where('booking_type', 'walk_in')->count() }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Online Revenue</div>
                <div class="kpi-value" style="font-size: 13px;">PHP {{ number_format($onlineRevenue, 2) }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Counter / Cash</div>
                <div class="kpi-value" style="font-size: 13px;">PHP {{ number_format($cashRevenue, 2) }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Capacity Hours</div>
                <div class="kpi-value" style="font-size: 13px;">{{ number_format($capacityHours) }}h (Fleet)</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Itemized Bookings Log ({{ $bookings->count() }} Records)</div>
    @if($bookings->isEmpty())
        <p style="text-align: center; color: #8c9480; padding: 20px 0;">No bookings recorded for this timeframe.</p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Ref #</th>
                    <th>Customer</th>
                    <th>Court</th>
                    <th>Date</th>
                    <th>Time Window</th>
                    <th class="text-center">Hours</th>
                    <th class="text-center">Type</th>
                    <th class="text-center">Status</th>
                    <th>Staff Audit</th>
                    <th class="text-right">Fee (PHP)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                    @php
                        $payment = $booking->payments->first();
                        $hours = (new \App\Services\ReportExportService())->calculateBookingHours($booking);
                    @endphp
                    <tr>
                        <td style="font-family: monospace; font-weight: 700;">{{ $booking->reference_number ?? ('BK-' . $booking->id) }}</td>
                        <td>{{ $booking->user->name ?? $booking->customer_name ?? 'Guest Customer' }}</td>
                        <td>{{ $booking->court->court_name ?? 'Court #' . $booking->court_id }}</td>
                        <td>{{ $booking->date }}</td>
                        <td>{{ $booking->start_time }} - {{ $booking->end_time }}</td>
                        <td class="text-center">{{ number_format($hours, 1) }}</td>
                        <td class="text-center">
                            <span class="badge {{ $booking->booking_type === 'walk_in' ? 'badge-warning' : 'badge-neutral' }}">
                                {{ $booking->booking_type === 'walk_in' ? 'Walk-in' : 'Online' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ in_array($booking->booking_status, ['confirmed', 'show']) ? 'badge-success' : ($booking->booking_status === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                                {{ ucfirst($booking->booking_status) }}
                            </span>
                        </td>
                        <td style="font-size: 8px;">
                            @if($payment?->processor)
                                <div><strong>Cashier:</strong> {{ $payment->processor->name }}</div>
                            @elseif($booking->booking_type === 'walk_in')
                                <div style="color: #636b59;">Desk Walk-In</div>
                            @endif
                            @if($booking->statusUpdatedBy)
                                <div style="color: #15803d;"><strong>Marked:</strong> {{ $booking->statusUpdatedBy->name }}</div>
                            @endif
                            @if(!$payment?->processor && !$booking->statusUpdatedBy && $booking->booking_type !== 'walk_in')
                                <span style="color: #8c9480;">Online Gateway</span>
                            @endif
                        </td>
                        <td class="text-right" style="font-weight: 700;">
                            {{ $payment ? number_format((float) $payment->amount, 2) : '0.00' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ $siteName }} Confidential Management Report &middot; Generated via Operations HQ Audit &middot; Page 1
    </div>
</body>
</html>
