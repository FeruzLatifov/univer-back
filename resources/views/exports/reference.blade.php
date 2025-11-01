<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma'lumotnoma</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }
        .university-name {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .document-title {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 40px;
            margin-bottom: 10px;
        }
        .reference-number {
            font-size: 12pt;
            margin-bottom: 30px;
        }
        .content {
            text-align: justify;
            margin: 30px 0;
            line-height: 2;
        }
        .student-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #333;
        }
        .info-row {
            margin: 10px 0;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 200px;
        }
        .info-value {
            display: inline;
        }
        .footer {
            margin-top: 60px;
        }
        .signature-block {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-item {
            width: 45%;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
        }
        .date-block {
            margin-top: 30px;
            text-align: right;
        }
        .stamp-area {
            margin-top: 30px;
            text-align: center;
            font-size: 10pt;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="university-name">
            {{ $university->name ?? "O'ZBEKISTON RESPUBLIKASI OLIY TA'LIM, FAN VA INNOVATSIYALAR VAZIRLIGI" }}
        </div>
        <div style="margin-top: 10px;">
            {{ $university->full_name ?? "" }}
        </div>
    </div>

    <div class="reference-number">
        <strong>№ {{ $reference->number }}</strong>
    </div>

    <div class="document-title">
        MA'LUMOTNOMA
    </div>

    <div class="content">
        <p>
            Ushbu ma'lumotnoma <strong>{{ $student->surname }} {{ $student->name }} {{ $student->patronymic }}</strong>
            {{ $student->birthday ? '(' . date('d.m.Y', strtotime($student->birthday)) . ' yil tug\'ilgan)' : '' }}
            ga {{ $university->name ?? "Oliy ta'lim muassasasi" }}ning
            <strong>{{ $student->specialty->name ?? 'N/A' }}</strong>
            ta'lim yo'nalishi
            <strong>{{ $student->_semestr ?? 'N/A' }}-kurs</strong>
            talabasi ekanligini tasdiqlash uchun berildi.
        </p>

        <div class="student-info">
            <div class="info-row">
                <span class="info-label">F.I.SH:</span>
                <span class="info-value">{{ $student->surname }} {{ $student->name }} {{ $student->patronymic }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tug'ilgan sana:</span>
                <span class="info-value">{{ $student->birthday ? date('d.m.Y', strtotime($student->birthday)) : 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Pasport seriya va raqami:</span>
                <span class="info-value">{{ $student->passport_number ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">JSHSHIR:</span>
                <span class="info-value">{{ $student->passport_pin ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ta'lim yo'nalishi:</span>
                <span class="info-value">{{ $student->specialty->code ?? 'N/A' }} - {{ $student->specialty->name ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ta'lim shakli:</span>
                <span class="info-value">{{ $student->meta->educationType->name ?? 'Kunduzgi' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Guruh:</span>
                <span class="info-value">{{ $student->group->name ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Kurs:</span>
                <span class="info-value">{{ $student->_semestr ?? 'N/A' }}-kurs</span>
            </div>
            <div class="info-row">
                <span class="info-label">Semestr:</span>
                <span class="info-value">{{ $reference->semester->name ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">O'qish holati:</span>
                <span class="info-value">{{ $student->meta->studentStatus->name ?? 'O\'qiydi' }}</span>
            </div>
        </div>

        <p>
            Talaba hozirgi vaqtda {{ date('Y') }}-{{ date('Y') + 1 }} o'quv yilida
            {{ $student->_semestr ?? 'N/A' }}-kursda o'qimoqda.
        </p>

        <p style="margin-top: 30px;">
            Ma'lumotnoma talabning so'rovi bo'yicha taqdim etish uchun berildi.
        </p>
    </div>

    <div class="footer">
        <div class="date-block">
            <strong>Berilgan sana:</strong> {{ date('d.m.Y') }}
        </div>

        <div class="signature-block">
            <div class="signature-item">
                <div>Rektor</div>
                <div class="signature-line">
                    ________________
                </div>
                <div style="margin-top: 5px; font-size: 10pt;">
                    (imzo)
                </div>
            </div>

            <div class="signature-item">
                <div>O'quv ishlari bo'yicha prorektor</div>
                <div class="signature-line">
                    ________________
                </div>
                <div style="margin-top: 5px; font-size: 10pt;">
                    (imzo)
                </div>
            </div>
        </div>

        <div class="stamp-area">
            <p>M.O'. (muhur o'rni)</p>
        </div>
    </div>
</body>
</html>
