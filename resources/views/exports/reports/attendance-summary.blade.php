<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Davomat hisoboti</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 18pt;
            margin-bottom: 5px;
            color: #1a1a1a;
        }

        .header .subtitle {
            font-size: 11pt;
            color: #666;
            margin-bottom: 3px;
        }

        .period-info {
            background: #e9ecef;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            font-size: 10pt;
        }

        .period-info strong {
            color: #007bff;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: white;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            border-top: 3px solid #007bff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .summary-card .number {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .summary-card .label {
            font-size: 7pt;
            color: #6c757d;
            text-transform: uppercase;
        }

        .summary-card.present {
            border-top-color: #28a745;
        }

        .summary-card.present .number {
            color: #28a745;
        }

        .summary-card.absent {
            border-top-color: #dc3545;
        }

        .summary-card.absent .number {
            color: #dc3545;
        }

        .summary-card.late {
            border-top-color: #ffc107;
        }

        .summary-card.late .number {
            color: #ffc107;
        }

        .summary-card.rate {
            border-top-color: #17a2b8;
        }

        .summary-card.rate .number {
            color: #17a2b8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table thead {
            background: #2c3e50;
            color: white;
        }

        table thead th {
            padding: 8px 6px;
            text-align: left;
            font-weight: 600;
            font-size: 9pt;
            border: 1px solid #1a252f;
        }

        table tbody td {
            padding: 6px;
            border: 1px solid #ddd;
            font-size: 9pt;
        }

        table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .text-center {
            text-align: center;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            transition: width 0.3s;
        }

        .progress-excellent {
            background: linear-gradient(90deg, #28a745, #20c997);
        }

        .progress-good {
            background: linear-gradient(90deg, #17a2b8, #20c997);
        }

        .progress-average {
            background: linear-gradient(90deg, #ffc107, #fd7e14);
        }

        .progress-poor {
            background: linear-gradient(90deg, #dc3545, #c82333);
        }

        .rate-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: 600;
        }

        .rate-excellent {
            background: #d4edda;
            color: #155724;
        }

        .rate-good {
            background: #d1ecf1;
            color: #0c5460;
        }

        .rate-average {
            background: #fff3cd;
            color: #856404;
        }

        .rate-poor {
            background: #f8d7da;
            color: #721c24;
        }

        .chart-section {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .chart-section h3 {
            font-size: 12pt;
            margin-bottom: 15px;
            color: #1a1a1a;
        }

        .chart-row {
            margin-bottom: 12px;
        }

        .chart-label {
            font-size: 9pt;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>DAVOMAT HISOBOTI</h1>
        <div class="subtitle">Umumiy ko'rsatkichlar bo'yicha</div>
        <div class="subtitle" style="font-size: 9pt; margin-top: 5px;">{{ date('d.m.Y H:i') }}</div>
    </div>

    <!-- Period Info -->
    @if(isset($period))
    <div class="period-info">
        <strong>Hisobot davri:</strong>
        {{ $period['from'] ?? 'Boshi' }} dan {{ $period['to'] ?? 'Bugunga qadar' }} gacha
        @if(isset($filters['group_id']))
            | <strong>Guruh:</strong> {{ $filters['group_name'] ?? 'N/A' }}
        @endif
    </div>
    @endif

    <!-- Summary Cards -->
    @if(isset($summary))
    <div class="summary-cards">
        <div class="summary-card">
            <div class="number">{{ $summary['total_records'] ?? 0 }}</div>
            <div class="label">Jami Yozuvlar</div>
        </div>
        <div class="summary-card present">
            <div class="number">{{ $summary['present'] ?? 0 }}</div>
            <div class="label">Qatnashganlar</div>
        </div>
        <div class="summary-card absent">
            <div class="number">{{ $summary['absent'] ?? 0 }}</div>
            <div class="label">Qatnashmaganlar</div>
        </div>
        <div class="summary-card late">
            <div class="number">{{ $summary['late'] ?? 0 }}</div>
            <div class="label">Kechikkanlar</div>
        </div>
        <div class="summary-card rate">
            <div class="number">{{ number_format($summary['attendance_rate'] ?? 0, 1) }}%</div>
            <div class="label">Davomat Foizi</div>
        </div>
    </div>
    @endif

    <!-- Attendance by Group/Student -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                @if(isset($by_group) && $by_group)
                    <th style="width: 20%;">Guruh</th>
                @else
                    <th style="width: 25%;">Talaba</th>
                @endif
                <th style="width: 10%;">Jami</th>
                <th style="width: 10%;">Qatnashdi</th>
                <th style="width: 10%;">Qatnashmadi</th>
                <th style="width: 10%;">Kechikdi</th>
                <th style="width: 15%;">Foiz</th>
                <th style="width: 20%;">Ko'rsatkich</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
            @php
                $attendanceRate = $item['total'] > 0 ? ($item['present'] / $item['total'] * 100) : 0;
                $rateClass = $attendanceRate >= 90 ? 'rate-excellent' : ($attendanceRate >= 75 ? 'rate-good' : ($attendanceRate >= 60 ? 'rate-average' : 'rate-poor'));
                $rateLabel = $attendanceRate >= 90 ? 'A\'lo' : ($attendanceRate >= 75 ? 'Yaxshi' : ($attendanceRate >= 60 ? 'Qoniqarli' : 'Yomon'));
                $progressClass = $attendanceRate >= 90 ? 'progress-excellent' : ($attendanceRate >= 75 ? 'progress-good' : ($attendanceRate >= 60 ? 'progress-average' : 'progress-poor'));
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    @if(isset($by_group) && $by_group)
                        <strong>{{ $item['group_name'] ?? 'N/A' }}</strong>
                    @else
                        <strong>{{ $item['student_name'] ?? 'N/A' }}</strong>
                        @if(isset($item['group_name']))
                            <br><small style="color: #666;">{{ $item['group_name'] }}</small>
                        @endif
                    @endif
                </td>
                <td class="text-center">{{ $item['total'] ?? 0 }}</td>
                <td class="text-center" style="color: #28a745;">{{ $item['present'] ?? 0 }}</td>
                <td class="text-center" style="color: #dc3545;">{{ $item['absent'] ?? 0 }}</td>
                <td class="text-center" style="color: #ffc107;">{{ $item['late'] ?? 0 }}</td>
                <td class="text-center">
                    <strong>{{ number_format($attendanceRate, 1) }}%</strong>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div class="progress-bar" style="flex: 1;">
                            <div class="progress-fill {{ $progressClass }}" style="width: {{ $attendanceRate }}%;"></div>
                        </div>
                        <span class="rate-badge {{ $rateClass }}">{{ $rateLabel }}</span>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px; color: #999;">
                    Ma'lumotlar topilmadi
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Charts Section -->
    @if(isset($charts))
    <div class="chart-section">
        <h3>Vizual ko'rsatkichlar</h3>

        <div class="chart-row">
            <div class="chart-label">
                <span><strong>Qatnashganlar:</strong></span>
                <span>{{ $summary['present'] ?? 0 }} ({{ number_format(($summary['present'] ?? 0) / max($summary['total_records'] ?? 1, 1) * 100, 1) }}%)</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill progress-excellent" style="width: {{ ($summary['present'] ?? 0) / max($summary['total_records'] ?? 1, 1) * 100 }}%;"></div>
            </div>
        </div>

        <div class="chart-row">
            <div class="chart-label">
                <span><strong>Qatnashmaganlar:</strong></span>
                <span>{{ $summary['absent'] ?? 0 }} ({{ number_format(($summary['absent'] ?? 0) / max($summary['total_records'] ?? 1, 1) * 100, 1) }}%)</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill progress-poor" style="width: {{ ($summary['absent'] ?? 0) / max($summary['total_records'] ?? 1, 1) * 100 }}%;"></div>
            </div>
        </div>

        <div class="chart-row">
            <div class="chart-label">
                <span><strong>Kechikkanlar:</strong></span>
                <span>{{ $summary['late'] ?? 0 }} ({{ number_format(($summary['late'] ?? 0) / max($summary['total_records'] ?? 1, 1) * 100, 1) }}%)</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill progress-average" style="width: {{ ($summary['late'] ?? 0) / max($summary['total_records'] ?? 1, 1) * 100 }}%;"></div>
            </div>
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div style="margin-bottom: 15px;">
            <div style="float: left; width: 48%;">
                <strong>Dekan:</strong><br><br>
                _________________________ <small>(F.I.O va imzo)</small>
            </div>
            <div style="float: right; width: 48%;">
                <strong>O'quv ishlari bo'yicha dekan o'rinbosari:</strong><br><br>
                _________________________ <small>(F.I.O va imzo)</small>
            </div>
            <div style="clear: both;"></div>
        </div>
        <div style="text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
            <div style="font-size: 7pt; color: #999;">
                Hisobot avtomatik ravishda tizim tomonidan yaratilgan | {{ now()->format('d.m.Y H:i:s') }}
            </div>
        </div>
    </div>
</body>
</html>
