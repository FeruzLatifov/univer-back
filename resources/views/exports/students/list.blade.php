<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talabalar ro'yxati</title>
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

        .header .date {
            font-size: 9pt;
            color: #888;
        }

        .filters {
            background: #f5f5f5;
            padding: 8px 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 9pt;
        }

        .filters strong {
            color: #333;
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

        .text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
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

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #666;
        }

        .summary {
            background: #e9ecef;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #2c3e50;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>TALABALAR RO'YXATI</h1>
        <div class="subtitle">Universitet Boshqaruv Tizimi</div>
        <div class="date">Sana: {{ date('d.m.Y H:i') }}</div>
    </div>

    <!-- Filters -->
    @if(!empty($filters))
    <div class="filters">
        <strong>Filtrlar:</strong>
        @if(!empty($filters['group_id']))
            Guruh: {{ $filters['group_name'] ?? 'N/A' }} |
        @endif
        @if(!empty($filters['specialty_id']))
            Mutaxassislik: {{ $filters['specialty_name'] ?? 'N/A' }} |
        @endif
        @if(!empty($filters['course']))
            Kurs: {{ $filters['course'] }} |
        @endif
        @if(!empty($filters['status']))
            Status: {{ ucfirst($filters['status']) }} |
        @endif
        @if(!empty($filters['search']))
            Qidiruv: "{{ $filters['search'] }}"
        @endif
    </div>
    @endif

    <!-- Summary -->
    <div class="summary">
        <div class="summary-row">
            <strong>Jami talabalar:</strong>
            <span>{{ count($students) }} ta</span>
        </div>
        @if(isset($summary))
        <div class="summary-row">
            <strong>Faol:</strong>
            <span>{{ $summary['active'] ?? 0 }} ta</span>
        </div>
        <div class="summary-row">
            <strong>Akademik ta'til:</strong>
            <span>{{ $summary['academic_leave'] ?? 0 }} ta</span>
        </div>
        @endif
    </div>

    <!-- Students Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 25%;">F.I.O</th>
                <th style="width: 15%;">Guruh</th>
                <th style="width: 20%;">Mutaxassislik</th>
                <th style="width: 8%;">Kurs</th>
                <th style="width: 12%;">Telefon</th>
                <th style="width: 15%;">Status</th>
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
                <td>{{ $student->group->name ?? 'N/A' }}</td>
                <td>{{ $student->specialty->name ?? 'N/A' }}</td>
                <td class="text-center">{{ $student->course ?? '-' }}</td>
                <td>{{ $student->phone ?? '-' }}</td>
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
                            'academic_leave' => 'Akademik ta\'til',
                            'graduated' => 'Bitirgan',
                            'expelled' => 'Chetlashtirilgan',
                            default => 'Noma\'lum'
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px; color: #999;">
                    Talabalar topilmadi
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <div style="float: left;">
            <strong>Mas'ul shaxs:</strong> _________________________
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
