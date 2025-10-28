<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guruh talabalar ro'yxati</title>
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

        .group-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .group-info h2 {
            font-size: 16pt;
            margin-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            font-size: 9pt;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 12px;
            border-radius: 4px;
            border-left: 4px solid #007bff;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card .number {
            font-size: 20pt;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 4px;
        }

        .stat-card .label {
            font-size: 8pt;
            color: #6c757d;
            text-transform: uppercase;
        }

        .stat-card.active {
            border-left-color: #28a745;
        }

        .stat-card.active .number {
            color: #28a745;
        }

        .stat-card.academic {
            border-left-color: #ffc107;
        }

        .stat-card.academic .number {
            color: #ffc107;
        }

        .stat-card.other {
            border-left-color: #6c757d;
        }

        .stat-card.other .number {
            color: #6c757d;
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

        table tbody tr:hover {
            background: #f0f0f0;
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

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-academic {
            background: #fff3cd;
            color: #856404;
        }

        .badge-graduated {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-expelled {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-male {
            background: #cce5ff;
            color: #004085;
        }

        .badge-female {
            background: #f8d7da;
            color: #721c24;
        }

        .performance-section {
            margin-top: 20px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .performance-section h3 {
            font-size: 12pt;
            margin-bottom: 10px;
            color: #1a1a1a;
        }

        .performance-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .performance-item {
            background: white;
            padding: 8px;
            border-radius: 3px;
            font-size: 9pt;
        }

        .performance-item strong {
            display: block;
            margin-bottom: 4px;
            color: #495057;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #666;
        }

        .photo-placeholder {
            width: 30px;
            height: 30px;
            background: #e9ecef;
            border-radius: 50%;
            display: inline-block;
            vertical-align: middle;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>GURUH TALABALAR RO'YXATI</h1>
        <div class="subtitle">To'liq ma'lumot varag'i</div>
    </div>

    <!-- Group Info -->
    <div class="group-info">
        <h2>{{ $group->name }}</h2>
        <div class="info-grid">
            <div>
                <strong>Fakultet:</strong> {{ $group->faculty->name ?? 'N/A' }}
            </div>
            <div>
                <strong>Mutaxassislik:</strong> {{ $group->specialty->name ?? 'N/A' }}
            </div>
            <div>
                <strong>Kurs:</strong> {{ $group->course ?? '-' }}
            </div>
            <div>
                <strong>Ta'lim turi:</strong> {{ ucfirst($group->education_type ?? 'Kunduzgi') }}
            </div>
            <div>
                <strong>O'quv yili:</strong> {{ $group->academic_year ?? date('Y') }}
            </div>
            <div>
                <strong>Kurator:</strong> {{ $group->curator->full_name ?? 'Belgilanmagan' }}
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    @if(isset($statistics))
    <div class="stats-cards">
        <div class="stat-card">
            <div class="number">{{ $statistics['total'] ?? 0 }}</div>
            <div class="label">Jami Talabalar</div>
        </div>
        <div class="stat-card active">
            <div class="number">{{ $statistics['active'] ?? 0 }}</div>
            <div class="label">Faol</div>
        </div>
        <div class="stat-card academic">
            <div class="number">{{ $statistics['male'] ?? 0 }}</div>
            <div class="label">Erkak</div>
        </div>
        <div class="stat-card other">
            <div class="number">{{ $statistics['female'] ?? 0 }}</div>
            <div class="label">Ayol</div>
        </div>
    </div>
    @endif

    <!-- Students Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">№</th>
                <th style="width: 25%;">F.I.O</th>
                <th style="width: 12%;">Tug'ilgan sana</th>
                <th style="width: 6%;">Jinsi</th>
                <th style="width: 13%;">Telefon</th>
                <th style="width: 20%;">Manzil</th>
                <th style="width: 10%;">GPA</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $student->lastname }} {{ $student->firstname }}</strong>
                    @if($student->middlename)
                        <br><small style="color: #666;">{{ $student->middlename }}</small>
                    @endif
                </td>
                <td class="text-center">
                    {{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d.m.Y') : '-' }}
                </td>
                <td class="text-center">
                    <span class="badge badge-{{ $student->gender }}">
                        {{ $student->gender === 'male' ? 'Erkak' : 'Ayol' }}
                    </span>
                </td>
                <td>{{ $student->phone ?? '-' }}</td>
                <td>
                    <small>{{ $student->address ?? '-' }}</small>
                </td>
                <td class="text-center">
                    <strong style="color: {{ ($student->gpa ?? 0) >= 4.0 ? '#28a745' : (($student->gpa ?? 0) >= 3.0 ? '#17a2b8' : '#6c757d') }}">
                        {{ number_format($student->gpa ?? 0, 2) }}
                    </strong>
                </td>
                <td>
                    @php
                        $statusClass = match($student->status) {
                            'active' => 'badge-active',
                            'academic_leave' => 'badge-academic',
                            'graduated' => 'badge-graduated',
                            'expelled' => 'badge-expelled',
                            default => 'badge-active'
                        };
                        $statusText = match($student->status) {
                            'active' => 'Faol',
                            'academic_leave' => 'Akademik',
                            'graduated' => 'Bitirgan',
                            'expelled' => 'Chetlashgan',
                            default => 'Noma\'lum'
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px; color: #999;">
                    Talabalar topilmadi
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Performance Section -->
    @if(isset($performance))
    <div class="performance-section">
        <h3>Guruh o'zlashtirish ko'rsatkichlari</h3>
        <div class="performance-grid">
            <div class="performance-item">
                <strong>O'rtacha GPA</strong>
                <div style="font-size: 16pt; color: #007bff; font-weight: bold;">
                    {{ number_format($performance['average_gpa'] ?? 0, 2) }}
                </div>
            </div>
            <div class="performance-item">
                <strong>Davomat foizi</strong>
                <div style="font-size: 16pt; color: #28a745; font-weight: bold;">
                    {{ number_format($performance['attendance_rate'] ?? 0, 1) }}%
                </div>
            </div>
            <div class="performance-item">
                <strong>A'lo talabalar</strong>
                <div style="font-size: 16pt; color: #17a2b8; font-weight: bold;">
                    {{ $performance['excellent_students'] ?? 0 }} ta
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div style="margin-bottom: 15px;">
            <div style="float: left; width: 48%;">
                <strong>Dekan:</strong><br><br>
                _________________________ <small>(F.I.O)</small>
            </div>
            <div style="float: right; width: 48%;">
                <strong>Kurator:</strong><br><br>
                _________________________ <small>(F.I.O)</small>
            </div>
            <div style="clear: both;"></div>
        </div>
        <div style="text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
            <div style="font-size: 7pt; color: #999;">
                Hujjat avtomatik ravishda tizim tomonidan yaratilgan | {{ now()->format('d.m.Y H:i:s') }}
            </div>
        </div>
    </div>
</body>
</html>
