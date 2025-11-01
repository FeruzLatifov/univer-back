<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Oylik statistika</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 18pt; margin-bottom: 5px; }
        .period-badge { display: inline-block; background: #007bff; color: white; padding: 8px 16px; border-radius: 4px; font-weight: bold; margin-bottom: 15px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .stat-box { background: white; padding: 12px; border-radius: 4px; text-align: center; border-top: 3px solid #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-box .number { font-size: 20pt; font-weight: bold; margin-bottom: 4px; color: #007bff; }
        .stat-box .label { font-size: 7pt; color: #6c757d; text-transform: uppercase; }
        .section { margin-bottom: 20px; }
        .section h3 { font-size: 12pt; margin-bottom: 10px; padding-left: 10px; border-left: 4px solid #007bff; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table thead { background: #2c3e50; color: white; }
        table thead th { padding: 8px 6px; text-align: left; font-weight: 600; font-size: 9pt; border: 1px solid #1a252f; }
        table tbody td { padding: 6px; border: 1px solid #ddd; font-size: 9pt; }
        table tbody tr:nth-child(even) { background: #f9f9f9; }
        .text-center { text-align: center; }
        .chart-container { background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .chart-row { margin-bottom: 12px; }
        .chart-label { font-size: 9pt; margin-bottom: 4px; display: flex; justify-content: space-between; }
        .progress-bar { height: 10px; background: #e9ecef; border-radius: 5px; overflow: hidden; }
        .progress-fill { height: 100%; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 8pt; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>OYLIK STATISTIKA HISOBOTI</h1>
        <div style="font-size: 11pt; color: #666;">{{ date('Y') }} yil</div>
    </div>

    @if(isset($month) && isset($year))
    <div style="text-align: center; margin-bottom: 20px;">
        <span class="period-badge">
            {{ $month }}-oy, {{ $year }} yil
        </span>
    </div>
    @endif

    @if(isset($overview))
    <div class="stats-grid">
        <div class="stat-box">
            <div class="number">{{ $overview['total_students'] ?? 0 }}</div>
            <div class="label">Jami Talabalar</div>
        </div>
        <div class="stat-box" style="border-top-color: #28a745;">
            <div class="number" style="color: #28a745;">{{ $overview['active_students'] ?? 0 }}</div>
            <div class="label">Faol Talabalar</div>
        </div>
        <div class="stat-box" style="border-top-color: #17a2b8;">
            <div class="number" style="color: #17a2b8;">{{ $overview['total_classes'] ?? 0 }}</div>
            <div class="label">Darslar soni</div>
        </div>
        <div class="stat-box" style="border-top-color: #ffc107;">
            <div class="number" style="color: #ffc107;">{{ number_format($overview['attendance_rate'] ?? 0, 1) }}%</div>
            <div class="label">Davomat foizi</div>
        </div>
    </div>
    @endif

    <!-- Attendance Stats -->
    @if(isset($attendance))
    <div class="section">
        <h3>Davomat statistikasi</h3>
        <div class="chart-container">
            <div class="chart-row">
                <div class="chart-label">
                    <span><strong>Qatnashganlar:</strong></span>
                    <span>{{ $attendance['present'] ?? 0 }} ({{ number_format(($attendance['present'] ?? 0) / max($attendance['total'] ?? 1, 1) * 100, 1) }}%)</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ ($attendance['present'] ?? 0) / max($attendance['total'] ?? 1, 1) * 100 }}%; background: linear-gradient(90deg, #28a745, #20c997);"></div>
                </div>
            </div>
            <div class="chart-row">
                <div class="chart-label">
                    <span><strong>Qatnashmaganlar:</strong></span>
                    <span>{{ $attendance['absent'] ?? 0 }} ({{ number_format(($attendance['absent'] ?? 0) / max($attendance['total'] ?? 1, 1) * 100, 1) }}%)</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ ($attendance['absent'] ?? 0) / max($attendance['total'] ?? 1, 1) * 100 }}%; background: linear-gradient(90deg, #dc3545, #c82333);"></div>
                </div>
            </div>
            <div class="chart-row">
                <div class="chart-label">
                    <span><strong>Kechikkanlar:</strong></span>
                    <span>{{ $attendance['late'] ?? 0 }} ({{ number_format(($attendance['late'] ?? 0) / max($attendance['total'] ?? 1, 1) * 100, 1) }}%)</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ ($attendance['late'] ?? 0) / max($attendance['total'] ?? 1, 1) * 100 }}%; background: linear-gradient(90deg, #ffc107, #fd7e14);"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Grades Stats -->
    @if(isset($grades))
    <div class="section">
        <h3>Baholar statistikasi</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">Baho darajasi</th>
                    <th style="width: 20%;" class="text-center">Soni</th>
                    <th style="width: 20%;" class="text-center">Foiz</th>
                    <th style="width: 20%;">Ko'rsatkich</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>A'lo (A, A-)</strong></td>
                    <td class="text-center">{{ $grades['excellent'] ?? 0 }}</td>
                    <td class="text-center">{{ number_format(($grades['excellent'] ?? 0) / max($grades['total'] ?? 1, 1) * 100, 1) }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ ($grades['excellent'] ?? 0) / max($grades['total'] ?? 1, 1) * 100 }}%; background: #28a745;"></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><strong>Yaxshi (B+, B, B-)</strong></td>
                    <td class="text-center">{{ $grades['good'] ?? 0 }}</td>
                    <td class="text-center">{{ number_format(($grades['good'] ?? 0) / max($grades['total'] ?? 1, 1) * 100, 1) }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ ($grades['good'] ?? 0) / max($grades['total'] ?? 1, 1) * 100 }}%; background: #17a2b8;"></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><strong>Qoniqarli (C+, C, C-)</strong></td>
                    <td class="text-center">{{ $grades['satisfactory'] ?? 0 }}</td>
                    <td class="text-center">{{ number_format(($grades['satisfactory'] ?? 0) / max($grades['total'] ?? 1, 1) * 100, 1) }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ ($grades['satisfactory'] ?? 0) / max($grades['total'] ?? 1, 1) * 100 }}%; background: #ffc107;"></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><strong>Qarzlar (D, F)</strong></td>
                    <td class="text-center">{{ $grades['failed'] ?? 0 }}</td>
                    <td class="text-center">{{ number_format(($grades['failed'] ?? 0) / max($grades['total'] ?? 1, 1) * 100, 1) }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ ($grades['failed'] ?? 0) / max($grades['total'] ?? 1, 1) * 100 }}%; background: #dc3545;"></div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Top Performers -->
    @if(isset($top_students))
    <div class="section">
        <h3>Eng yaxshi talabalar (Top 10)</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Reyting</th>
                    <th style="width: 40%;">F.I.O</th>
                    <th style="width: 20%;">Guruh</th>
                    <th style="width: 15%;" class="text-center">GPA</th>
                    <th style="width: 15%;" class="text-center">Davomat %</th>
                </tr>
            </thead>
            <tbody>
                @forelse($top_students as $index => $student)
                <tr>
                    <td class="text-center">
                        <strong style="color: {{ $index < 3 ? '#ffc107' : '#007bff' }}; font-size: 12pt;">
                            {{ $index + 1 }}
                        </strong>
                    </td>
                    <td><strong>{{ $student['name'] ?? 'N/A' }}</strong></td>
                    <td>{{ $student['group'] ?? '-' }}</td>
                    <td class="text-center"><strong style="color: #28a745;">{{ number_format($student['gpa'] ?? 0, 2) }}</strong></td>
                    <td class="text-center">{{ number_format($student['attendance'] ?? 0, 1) }}%</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center" style="padding: 15px; color: #999;">Ma'lumot yo'q</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <div style="float: left;"><strong>Rektor:</strong> _________________________</div>
        <div style="float: right;"><strong>Imzo va muhur:</strong> _____________</div>
        <div style="clear: both;"></div>
        <div style="text-align: center; margin-top: 10px; font-size: 7pt; color: #999;">
            Hisobot yaratilgan: {{ now()->format('d.m.Y H:i:s') }}
        </div>
    </div>
</body>
</html>
