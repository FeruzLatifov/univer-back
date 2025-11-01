<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talaba davomat hisoboti</title>
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
        }

        .student-info {
            background: #f8f9fa;
            padding: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }

        .student-info h2 {
            font-size: 14pt;
            margin-bottom: 8px;
            color: #1a1a1a;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 9pt;
        }

        .info-item {
            padding: 4px 0;
        }

        .info-item strong {
            color: #495057;
        }

        .period {
            background: #e9ecef;
            padding: 8px 12px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 9pt;
            text-align: center;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            text-align: center;
        }

        .stat-card .number {
            font-size: 20pt;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .stat-card .label {
            font-size: 8pt;
            color: #6c757d;
            text-transform: uppercase;
        }

        .stat-card.present .number {
            color: #28a745;
        }

        .stat-card.absent .number {
            color: #dc3545;
        }

        .stat-card.late .number {
            color: #ffc107;
        }

        .stat-card.excused .number {
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

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: 600;
        }

        .badge-present {
            background: #d4edda;
            color: #155724;
        }

        .badge-absent {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-late {
            background: #fff3cd;
            color: #856404;
        }

        .badge-excused {
            background: #d1ecf1;
            color: #0c5460;
        }

        .summary {
            background: #f8f9fa;
            padding: 12px;
            margin-top: 20px;
            border-radius: 4px;
            border-left: 4px solid #28a745;
        }

        .summary h3 {
            font-size: 12pt;
            margin-bottom: 8px;
            color: #1a1a1a;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            font-size: 9pt;
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
        <div class="subtitle">Talaba davomat ma'lumotlari</div>
    </div>

    <!-- Student Info -->
    <div class="student-info">
        <h2>{{ $student->lastname }} {{ $student->firstname }} {{ $student->middlename }}</h2>
        <div class="info-grid">
            <div class="info-item">
                <strong>Guruh:</strong> {{ $student->group->name ?? 'N/A' }}
            </div>
            <div class="info-item">
                <strong>Kurs:</strong> {{ $student->course ?? '-' }}
            </div>
            <div class="info-item">
                <strong>Mutaxassislik:</strong> {{ $student->specialty->name ?? 'N/A' }}
            </div>
            <div class="info-item">
                <strong>Talaba ID:</strong> {{ $student->id }}
            </div>
        </div>
    </div>

    <!-- Period -->
    @if(isset($period))
    <div class="period">
        <strong>Davr:</strong>
        {{ $period['from'] ?? 'Boshi' }} dan {{ $period['to'] ?? 'Bugunga qadar' }} gacha
    </div>
    @endif

    <!-- Statistics -->
    @if(isset($statistics))
    <div class="stats">
        <div class="stat-card present">
            <div class="number">{{ $statistics['present'] ?? 0 }}</div>
            <div class="label">Qatnashgan</div>
        </div>
        <div class="stat-card absent">
            <div class="number">{{ $statistics['absent'] ?? 0 }}</div>
            <div class="label">Qatnashmagan</div>
        </div>
        <div class="stat-card late">
            <div class="number">{{ $statistics['late'] ?? 0 }}</div>
            <div class="label">Kechikkan</div>
        </div>
        <div class="stat-card excused">
            <div class="number">{{ $statistics['excused'] ?? 0 }}</div>
            <div class="label">Sababli</div>
        </div>
    </div>
    @endif

    <!-- Attendance Records -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 15%;">Sana</th>
                <th style="width: 25%;">Fan</th>
                <th style="width: 20%;">O'qituvchi</th>
                <th style="width: 12%;">Dars turi</th>
                <th style="width: 15%;">Status</th>
                <th style="width: 8%;">Vaqt</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $attendance)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($attendance->date)->format('d.m.Y') }}</td>
                <td>{{ $attendance->subject->name ?? 'N/A' }}</td>
                <td>{{ $attendance->teacher->full_name ?? 'N/A' }}</td>
                <td class="text-center">{{ ucfirst($attendance->lesson_type ?? '-') }}</td>
                <td>
                    @php
                        $statusClass = match($attendance->status) {
                            'present' => 'badge-present',
                            'absent' => 'badge-absent',
                            'late' => 'badge-late',
                            'excused' => 'badge-excused',
                            default => 'badge-present'
                        };
                        $statusText = match($attendance->status) {
                            'present' => 'Qatnashdi',
                            'absent' => 'Qatnashmadi',
                            'late' => 'Kechikdi',
                            'excused' => 'Sababli',
                            default => 'Noma\'lum'
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                </td>
                <td class="text-center">
                    {{ $attendance->lesson_number ?? '-' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px; color: #999;">
                    Davomat ma'lumotlari topilmadi
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Summary -->
    @if(isset($statistics))
    <div class="summary">
        <h3>Umumiy ko'rsatkichlar</h3>
        <div class="summary-grid">
            <div>
                <strong>Jami darslar:</strong> {{ $statistics['total'] ?? 0 }}
            </div>
            <div>
                <strong>Davomat foizi:</strong>
                @php
                    $total = $statistics['total'] ?? 0;
                    $present = $statistics['present'] ?? 0;
                    $percentage = $total > 0 ? round(($present / $total) * 100, 1) : 0;
                @endphp
                <strong style="color: {{ $percentage >= 75 ? '#28a745' : ($percentage >= 50 ? '#ffc107' : '#dc3545') }}">
                    {{ $percentage }}%
                </strong>
            </div>
            <div>
                <strong>O'rtacha ball:</strong> {{ number_format($statistics['average_grade'] ?? 0, 1) }}
            </div>
            <div>
                <strong>Hisobot sanasi:</strong> {{ date('d.m.Y H:i') }}
            </div>
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div style="float: left;">
            <strong>Dekan:</strong> _________________________
        </div>
        <div style="float: right;">
            <strong>Imzo:</strong> _____________
        </div>
        <div style="clear: both;"></div>
        <div style="text-align: center; margin-top: 10px; font-size: 7pt; color: #999;">
            Hujjat avtomatik ravishda tizim tomonidan yaratilgan | {{ now()->format('d.m.Y H:i:s') }}
        </div>
    </div>
</body>
</html>
