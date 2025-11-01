<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talaba baholar varag'i</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .student-info h2 {
            font-size: 14pt;
            margin-bottom: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
            font-size: 9pt;
        }

        .semester-badge {
            display: inline-block;
            background: #ffc107;
            color: #000;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: white;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #007bff;
            text-align: center;
        }

        .stat-box .number {
            font-size: 18pt;
            font-weight: bold;
            color: #007bff;
        }

        .stat-box .label {
            font-size: 8pt;
            color: #6c757d;
            text-transform: uppercase;
            margin-top: 4px;
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

        .grade-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10pt;
        }

        .grade-excellent {
            background: #28a745;
            color: white;
        }

        .grade-good {
            background: #17a2b8;
            color: white;
        }

        .grade-satisfactory {
            background: #ffc107;
            color: #000;
        }

        .grade-unsatisfactory {
            background: #dc3545;
            color: white;
        }

        .performance-indicator {
            background: #e9ecef;
            padding: 12px;
            margin-top: 20px;
            border-radius: 4px;
        }

        .performance-indicator h3 {
            font-size: 12pt;
            margin-bottom: 10px;
            color: #1a1a1a;
        }

        .indicator-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            font-size: 9pt;
        }

        .indicator-item {
            background: white;
            padding: 8px;
            border-radius: 3px;
            border-left: 3px solid #007bff;
        }

        .indicator-item strong {
            display: block;
            margin-bottom: 4px;
            color: #495057;
        }

        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #666;
        }

        .credit-info {
            font-size: 8pt;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>BAHOLAR VARAG'I</h1>
        <div class="subtitle">Talaba o'zlashtirish ko'rsatkichlari</div>
    </div>

    <!-- Student Info -->
    <div class="student-info">
        <h2>{{ $student->lastname }} {{ $student->firstname }} {{ $student->middlename }}</h2>
        <div class="info-grid">
            <div><strong>Guruh:</strong> {{ $student->group->name ?? 'N/A' }}</div>
            <div><strong>Kurs:</strong> {{ $student->course ?? '-' }}</div>
            <div><strong>Mutaxassislik:</strong> {{ $student->specialty->name ?? 'N/A' }}</div>
        </div>
    </div>

    <!-- Semester Badge -->
    @if(isset($semester))
    <div class="semester-badge">
        {{ $semester }}-semestr
    </div>
    @endif

    <!-- Statistics Summary -->
    @if(isset($statistics))
    <div class="stats-summary">
        <div class="stat-box">
            <div class="number">{{ number_format($statistics['gpa'] ?? 0, 2) }}</div>
            <div class="label">O'rtacha Ball (GPA)</div>
        </div>
        <div class="stat-box" style="border-left-color: #28a745;">
            <div class="number" style="color: #28a745;">{{ $statistics['total_credits'] ?? 0 }}</div>
            <div class="label">Jami Kreditlar</div>
        </div>
        <div class="stat-box" style="border-left-color: #ffc107;">
            <div class="number" style="color: #ffc107;">{{ $statistics['passed_subjects'] ?? 0 }}</div>
            <div class="label">O'tgan Fanlar</div>
        </div>
        <div class="stat-box" style="border-left-color: #dc3545;">
            <div class="number" style="color: #dc3545;">{{ $statistics['failed_subjects'] ?? 0 }}</div>
            <div class="label">Qarzlar</div>
        </div>
    </div>
    @endif

    <!-- Grades Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 35%;">Fan nomi</th>
                <th style="width: 15%;">O'qituvchi</th>
                <th style="width: 8%;">Kredit</th>
                <th style="width: 10%;">Oraliq</th>
                <th style="width: 10%;">Yakuniy</th>
                <th style="width: 12%;">Baho</th>
                <th style="width: 5%;">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grades as $index => $grade)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $grade->subject->name ?? 'N/A' }}</strong>
                    <div class="credit-info">
                        {{ $grade->subject->code ?? '' }}
                    </div>
                </td>
                <td>{{ $grade->teacher->full_name ?? 'N/A' }}</td>
                <td class="text-center">{{ $grade->credits ?? 0 }}</td>
                <td class="text-center">{{ $grade->midterm_grade ?? '-' }}</td>
                <td class="text-center">{{ $grade->final_grade ?? '-' }}</td>
                <td class="text-center">
                    @php
                        $totalGrade = $grade->total_grade ?? 0;
                        $gradeClass = $totalGrade >= 90 ? 'grade-excellent' :
                                     ($totalGrade >= 75 ? 'grade-good' :
                                     ($totalGrade >= 55 ? 'grade-satisfactory' : 'grade-unsatisfactory'));
                        $gradeLetter = $totalGrade >= 90 ? 'A' :
                                      ($totalGrade >= 85 ? 'A-' :
                                      ($totalGrade >= 80 ? 'B+' :
                                      ($totalGrade >= 75 ? 'B' :
                                      ($totalGrade >= 70 ? 'B-' :
                                      ($totalGrade >= 65 ? 'C+' :
                                      ($totalGrade >= 60 ? 'C' :
                                      ($totalGrade >= 55 ? 'C-' :
                                      ($totalGrade >= 50 ? 'D+' :
                                      ($totalGrade >= 0 ? 'F' : '-')))))))));
                    @endphp
                    <span class="grade-badge {{ $gradeClass }}">{{ $gradeLetter }}</span>
                </td>
                <td class="text-center">{{ $totalGrade }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px; color: #999;">
                    Baholar topilmadi
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Performance Indicators -->
    @if(isset($performance))
    <div class="performance-indicator">
        <h3>O'zlashtirish ko'rsatkichlari</h3>
        <div class="indicator-grid">
            <div class="indicator-item">
                <strong>A'lo (A, A-)</strong>
                {{ $performance['excellent'] ?? 0 }} ta fan
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ ($performance['excellent_percent'] ?? 0) }}%; background: #28a745;"></div>
                </div>
            </div>
            <div class="indicator-item">
                <strong>Yaxshi (B+, B, B-)</strong>
                {{ $performance['good'] ?? 0 }} ta fan
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ ($performance['good_percent'] ?? 0) }}%; background: #17a2b8;"></div>
                </div>
            </div>
            <div class="indicator-item">
                <strong>Qoniqarli (C+, C, C-)</strong>
                {{ $performance['satisfactory'] ?? 0 }} ta fan
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ ($performance['satisfactory_percent'] ?? 0) }}%; background: #ffc107;"></div>
                </div>
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
            <strong>Imzo va muhur:</strong> _____________
        </div>
        <div style="clear: both;"></div>
        <div style="text-align: center; margin-top: 10px; font-size: 7pt; color: #999;">
            Hujjat avtomatik ravishda tizim tomonidan yaratilgan | {{ now()->format('d.m.Y H:i:s') }}
        </div>
    </div>
</body>
</html>
