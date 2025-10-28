<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>O'qituvchi ish yuki</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 18pt; margin-bottom: 5px; }
        .teacher-info { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .teacher-info h2 { font-size: 16pt; margin-bottom: 10px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; font-size: 9pt; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .summary-card { background: white; padding: 12px; border-radius: 4px; text-align: center; border-top: 3px solid #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .summary-card .number { font-size: 18pt; font-weight: bold; margin-bottom: 4px; color: #007bff; }
        .summary-card .label { font-size: 7pt; color: #6c757d; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table thead { background: #2c3e50; color: white; }
        table thead th { padding: 8px 6px; text-align: left; font-weight: 600; font-size: 9pt; border: 1px solid #1a252f; }
        table tbody td { padding: 6px; border: 1px solid #ddd; font-size: 9pt; }
        table tbody tr:nth-child(even) { background: #f9f9f9; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 8pt; font-weight: 600; }
        .badge-lecture { background: #d1ecf1; color: #0c5460; }
        .badge-practical { background: #d4edda; color: #155724; }
        .badge-laboratory { background: #fff3cd; color: #856404; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 8pt; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>O'QITUVCHI ISH YUKI HISOBOTI</h1>
        <div style="font-size: 11pt; color: #666;">{{ date('Y') }}-{{ date('Y')+1 }} o'quv yili</div>
    </div>

    @if(isset($teacher))
    <div class="teacher-info">
        <h2>{{ $teacher->lastname }} {{ $teacher->firstname }} {{ $teacher->middlename }}</h2>
        <div class="info-grid">
            <div><strong>Lavozim:</strong> {{ $teacher->position ?? 'N/A' }}</div>
            <div><strong>Kafedra:</strong> {{ $teacher->department->name ?? 'N/A' }}</div>
            <div><strong>Ilmiy daraja:</strong> {{ $teacher->degree ?? '-' }}</div>
        </div>
    </div>
    @endif

    @if(isset($statistics))
    <div class="summary">
        <div class="summary-card">
            <div class="number">{{ $statistics['total_hours'] ?? 0 }}</div>
            <div class="label">Jami soatlar</div>
        </div>
        <div class="summary-card">
            <div class="number">{{ $statistics['total_groups'] ?? 0 }}</div>
            <div class="label">Guruhlar soni</div>
        </div>
        <div class="summary-card">
            <div class="number">{{ $statistics['total_students'] ?? 0 }}</div>
            <div class="label">Talabalar soni</div>
        </div>
        <div class="summary-card">
            <div class="number">{{ $statistics['total_subjects'] ?? 0 }}</div>
            <div class="label">Fanlar soni</div>
        </div>
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">№</th>
                <th style="width: 30%;">Fan nomi</th>
                <th style="width: 15%;">Guruh</th>
                <th style="width: 10%;">Dars turi</th>
                <th style="width: 10%;">Talabalar</th>
                <th style="width: 10%;">Soat/Hafta</th>
                <th style="width: 10%;">Jami soat</th>
                <th style="width: 10%;">Semestr</th>
            </tr>
        </thead>
        <tbody>
            @forelse($workload as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->subject->name ?? 'N/A' }}</strong>
                    <br><small style="color: #666;">{{ $item->subject->code ?? '' }}</small>
                </td>
                <td>{{ $item->group->name ?? '-' }}</td>
                <td class="text-center">
                    @php
                        $typeClass = match($item->lesson_type ?? 'lecture') {
                            'lecture' => 'badge-lecture',
                            'practical' => 'badge-practical',
                            'laboratory' => 'badge-laboratory',
                            default => 'badge-lecture'
                        };
                        $typeText = match($item->lesson_type ?? 'lecture') {
                            'lecture' => 'Ma\'ruza',
                            'practical' => 'Amaliy',
                            'laboratory' => 'Laboratoriya',
                            default => 'N/A'
                        };
                    @endphp
                    <span class="badge {{ $typeClass }}">{{ $typeText }}</span>
                </td>
                <td class="text-center">{{ $item->students_count ?? 0 }}</td>
                <td class="text-center">{{ $item->weekly_hours ?? 0 }}</td>
                <td class="text-center"><strong>{{ $item->total_hours ?? 0 }}</strong></td>
                <td class="text-center">{{ $item->semester ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center" style="padding: 20px; color: #999;">Ma'lumotlar topilmadi</td></tr>
            @endforelse
        </tbody>
        @if(isset($statistics))
        <tfoot style="background: #f8f9fa; font-weight: bold;">
            <tr>
                <td colspan="6" style="text-align: right; padding: 8px;">JAMI:</td>
                <td class="text-center" style="font-size: 11pt; color: #007bff;">{{ $statistics['total_hours'] ?? 0 }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    @if(isset($breakdown))
    <div style="background: #f8f9fa; padding: 12px; margin-top: 20px; border-radius: 4px;">
        <h3 style="font-size: 12pt; margin-bottom: 10px;">Dars turlari bo'yicha taqsimot</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; font-size: 9pt;">
            <div style="background: white; padding: 8px; border-radius: 3px; border-left: 3px solid #17a2b8;">
                <strong>Ma'ruzalar:</strong> {{ $breakdown['lecture_hours'] ?? 0 }} soat
            </div>
            <div style="background: white; padding: 8px; border-radius: 3px; border-left: 3px solid #28a745;">
                <strong>Amaliy:</strong> {{ $breakdown['practical_hours'] ?? 0 }} soat
            </div>
            <div style="background: white; padding: 8px; border-radius: 3px; border-left: 3px solid #ffc107;">
                <strong>Laboratoriya:</strong> {{ $breakdown['laboratory_hours'] ?? 0 }} soat
            </div>
        </div>
    </div>
    @endif

    <div class="footer">
        <div style="float: left;"><strong>Kafedra mudiri:</strong> _________________________</div>
        <div style="float: right;"><strong>Imzo:</strong> _____________</div>
        <div style="clear: both;"></div>
        <div style="text-align: center; margin-top: 10px; font-size: 7pt; color: #999;">
            Hisobot yaratilgan: {{ now()->format('d.m.Y H:i:s') }}
        </div>
    </div>
</body>
</html>
