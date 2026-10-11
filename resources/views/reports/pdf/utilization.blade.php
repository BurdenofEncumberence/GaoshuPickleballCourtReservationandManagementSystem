<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $siteName }} - Court Capacity & Utilization Report</title>
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
                <div class="report-subtitle">Court Capacity & Utilization Report</div>
            </td>
            <td class="meta-box">
                <span class="period-badge">{{ $range['label'] }}</span>
                <div>Generated: <strong>{{ now()->format('M d, Y h:i A') }}</strong></div>
                <div>Audited by: <strong>{{ $user->name }} ({{ strtoupper($user->role) }})</strong></div>
            </td>
        </tr>
    </table>

    <div class="section-title">Facility Capacity Summary</div>
    <table class="kpi-grid">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Utilization Rate</div>
                <div class="kpi-value" style="color: #0b7e45;">{{ $facilityUtilizationRate }}%</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Hours Utilized</div>
                <div class="kpi-value">{{ number_format($totalFacilityBookedHours, 1) }}h</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Operating Capacity</div>
                <div class="kpi-value">{{ number_format($totalFacilityCapacity) }}h</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Fleet Yield (RevPACH)</div>
                <div class="kpi-value" style="font-size: 13px;">
                    PHP {{ $totalFacilityCapacity > 0 ? number_format($totalFacilityRevenue / $totalFacilityCapacity, 2) : '0.00' }}/h
                </div>
            </td>
        </tr>
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Courts Inventory</div>
                <div class="kpi-value" style="font-size: 13px;">{{ $availableCourts }} Available / {{ $totalCourts }} Total</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Daily Schedule</div>
                <div class="kpi-value" style="font-size: 13px;">16 Hours (6 AM - 10 PM)</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Days in Period</div>
                <div class="kpi-value" style="font-size: 13px;">{{ $days }} Days</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Total Fleet Revenue</div>
                <div class="kpi-value" style="font-size: 13px; color: #0b7e45;">PHP {{ number_format($totalFacilityRevenue, 2) }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Per-Court Utilization Breakdown</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Court Name</th>
                <th class="text-center">Status</th>
                <th class="text-right">Rate/Hour</th>
                <th class="text-right">Capacity (h)</th>
                <th class="text-right">Booked (h)</th>
                <th class="text-right">Load (%)</th>
                <th class="text-center">Sessions</th>
                <th class="text-right">Revenue (PHP)</th>
                <th class="text-right">RevPACH</th>
                <th>Peak Slot</th>
            </tr>
        </thead>
        <tbody>
            @foreach($courtStats as $stat)
                @php $court = $stat['court']; @endphp
                <tr>
                    <td style="font-weight: 700;">{{ $court->court_name }}</td>
                    <td class="text-center">
                        <span class="badge {{ $court->court_status === 'available' ? 'badge-success' : 'badge-danger' }}">
                            {{ ucfirst($court->court_status) }}
                        </span>
                    </td>
                    <td class="text-right">PHP {{ number_format((float) $court->price_per_hour, 2) }}</td>
                    <td class="text-right font-mono">{{ $stat['capacity_hours'] }}h</td>
                    <td class="text-right font-mono" style="font-weight: 700;">{{ number_format($stat['booked_hours'], 1) }}h</td>
                    <td class="text-right font-mono" style="font-weight: 700; color: {{ $stat['utilization_rate'] >= 50 ? '#0b7e45' : '#12150f' }};">
                        {{ $stat['utilization_rate'] }}%
                    </td>
                    <td class="text-center">{{ $stat['sessions_count'] }}</td>
                    <td class="text-right" style="font-weight: 700;">PHP {{ number_format($stat['revenue'], 2) }}</td>
                    <td class="text-right font-mono">PHP {{ number_format($stat['rev_pach'], 2) }}</td>
                    <td style="font-size: 8.5px;">{{ $stat['peak_slot'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        {{ $siteName }} Operational Analytics &middot; Court Capacity Management &middot; Page 1
    </div>
</body>
</html>
