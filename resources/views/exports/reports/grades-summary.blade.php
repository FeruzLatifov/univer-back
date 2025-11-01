<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Baholar hisoboti</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 18pt; margin-bottom: 5px; }
        .summary { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 20px; }
        .summary-card { background: white; padding: 10px; border-radius: 4px; text-align: center; border-top: 3px solid #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .summary-card .number { font-size: 18pt; font-weight: bold; margin-bottom: 4px; }
        .summary-card .label { font-size: 7pt; color: #6c757d; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table thead { background: #2c3e50; color: white; }
        table thead th { padding: 8px 6px; text-align: left; font-weight: 600; font-size: 9pt; border: 1px solid #1a252f; }
        table tbody td { padding: 6px; border: 1px solid #ddd; font-size: 9pt; }
        table tbody tr:nth-child(even) { background: #f9f9f9; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 8pt; font-weight: 600; }
        .badge-excellent { background: #d4edda; color: #155724; }
        .badge-good { background: #d1ecf1; color: #0c5460; }
        .badge-satisfactory { background: #fff3cd; color: #856404; }
        .badge-poor { background: #f8d7da; color: #721c24; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 8pt; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BAHOLAR HISOBOTI</h1>
        <div style="font-size: 11pt; color: #666;">Umumiy ko'rsatkichlar</div>
    </div>

    @if(isset($filters))
    <div style="background: #e9ecef; padding: 10px; margin-bottom: 20px; border-radius: 4px; text-align: center; font-size: 10pt;">
        @if(isset($filters['semester'])) <strong>Semestr:</strong> {{ $filters['semester'] }} | @endif
        @if(isset($filters['group_name'])) <strong>Guruh:</strong> {{ $filters['group_name'] }} | @endif
        @if(isset($filters['subject_name'])) <strong>Fan:</strong> {{ $filters['subject_name'] }} @endif
    </div>
    @endif

    @if(isset($statistics))
    <div class="summary">
        <div class="summary-card">
            <div class="number" style="color: #007bff;">{{ number_format($statistics['average_gpa'] ?? 0, 2) }}</div>
            <div class="label">O'rtacha GPA</div>
        </div>
        <div class="summary-card">
            <div class="number" style="color: #28a745;">{{ $statistics['excellent'] ?? 0 }}</div>
            <div class="label">A'lo (A)</div>
        </div>
        <div class="summary-card">
            <div class="number" style="color: #17a2b8;">{{ $statistics['good'] ?? 0 }}</div>
            <div class="label">Yaxshi (B)</div>
        </div>
        <div class="summary-card">
            <div class="number" style="color: #ffc107;">{{ $statistics['satisfactory'] ?? 0 }}</div>
            <div class="label">Qoniqarli (C)</div>
        </div>
        <div class="summary-card">
            <div class="number" style="color: #dc3545;">{{ $statistics['failed'] ?? 0 }}</div>
            <div class="label">Qarzlar (F)</div>
        </div>
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 30%;">Talaba / Fan</th>
                <th style="width: 15%;">Guruh</th>
                <th style="width: 10%;">Oraliq</th>
                <th style="width: 10%;">Yakuniy</th>
                <th style="width: 10%;">Jami</th>
                <th style="width: 10%;">Baho</th>
                <th style="width: 10%;">Ko'rsatkich</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
            @php
                $grade = $item['total_grade'] ?? 0;
                $letter = $grade >= 90 ? 'A' : ($grade >= 85 ? 'A-' : ($grade >= 80 ? 'B+' : ($grade >= 75 ? 'B' : ($grade >= 70 ? 'B-' : ($grade >= 65 ? 'C+' : ($grade >= 60 ? 'C' : ($grade >= 55 ? 'C-' : 'F')))))));
                $badgeClass = $grade >= 85 ? 'badge-excellent' : ($grade >= 70 ? 'badge-good' : ($grade >= 55 ? 'badge-satisfactory' : 'badge-poor'));
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item['name'] ?? 'N/A' }}</strong>
                    @if(isset($item['subtext']))
                        <br><small style="color: #666;">{{ $item['subtext'] }}</small>
                    @endif
                </td>
                <td>{{ $item['group'] ?? '-' }}</td>
                <td class="text-center">{{ $item['midterm'] ?? '-' }}</td>
                <td class="text-center">{{ $item['final'] ?? '-' }}</td>
                <td class="text-center"><strong>{{ $grade }}</strong></td>
                <td class="text-center">
                    <span class="badge {{ $badgeClass }}">{{ $letter }}</span>
                </td>
                <td class="text-center">
                    <div style="height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden;">
                        <div style="height: 100%; width: {{ $grade }}%; background: {{ $grade >= 85 ? '#28a745' : ($grade >= 70 ? '#17a2b8' : ($grade >= 55 ? '#ffc107' : '#dc3545')) }};"></div>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center" style="padding: 20px; color: #999;">Ma'lumotlar topilmadi</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div style="float: left;"><strong>Dekan:</strong> _________________________</div>
        <div style="float: right;"><strong>Imzo:</strong> _____________</div>
        <div style="clear: both;"></div>
        <div style="text-align: center; margin-top: 10px; font-size: 7pt; color: #999;">
            Hisobot yaratilgan: {{ now()->format('d.m.Y H:i:s') }}
        </div>
    </div>
</body>
</html>
