<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notulen Rapat - {{ $meeting->title }}</title>
    <style>
        @page { size: a4 landscape; margin: 15mm 20mm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11pt; line-height: 1.5; color: #333; }
        .kop-surat { width: 100%; border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; position: relative; }
        .kop-surat .logo { position: absolute; left: 0; top: 0; width: 75px; }
        .kop-surat .text { text-align: center; margin-left: 90px; }
        .kop-surat h1 { font-size: 16pt; margin: 0; text-transform: uppercase; font-weight: bold; }
        .kop-surat h2 { font-size: 14pt; margin: 5px 0; text-transform: uppercase; font-weight: bold; }
        .kop-surat p { font-size: 10pt; margin: 0; font-style: italic; }
        .doc-title { text-align: center; margin: 20px 0; font-size: 14pt; font-weight: bold; text-decoration: underline; text-transform: uppercase; }
        
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; font-size: 9.5pt; }
        .info-table td { padding: 3px 5px; vertical-align: top; }
        .info-table .label { font-weight: bold; width: 150px; }
        .section-title { font-size: 11pt; font-weight: bold; margin-top: 15px; margin-bottom: 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .content { font-size: 9.5pt; text-align: justify; white-space: pre-line; padding-left: 5px; }
        .footer { margin-top: 40px; text-align: right; width: 300px; float: right; font-size: 9.5pt; }
        .signature { margin-top: 60px; font-weight: bold; text-decoration: underline; text-align: center; }
        .signature-title { text-align: center; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>

    <div class="kop-surat">
        <?php
            $logoPath = public_path('img/logo.png');
            $logoBase64 = '';
            if (file_exists($logoPath)) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            }
        ?>
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
        @endif
        
        <div class="text">
            <h1>PEMERINTAH KABUPATEN SINJAI</h1>
            <h2>{{ strtoupper($meeting->creator->unit_name ?? 'SEKRETARIAT DAERAH') }}</h2>
            <p>Jalan Persatuan Raya No. 1, Sinjai, Sulawesi Selatan. Kode Pos: 92611</p>
        </div>
    </div>

    <div class="doc-title">NOTULEN RAPAT</div>

    <table class="info-table">
        <tr>
            <td class="label">Nama / Agenda Rapat</td>
            <td>: {{ $meeting->title }}</td>
        </tr>
        <tr>
            <td class="label">Hari / Tanggal</td>
            <td>: {{ $meeting->date->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Waktu</td>
            <td>: {{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time ? $meeting->end_time->format('H:i') : 'Selesai' }} WITA</td>
        </tr>
        <tr>
            <td class="label">Tempat</td>
            <td>: {{ $meeting->location ?? 'Online / Menyesuaikan' }}</td>
        </tr>
        <tr>
            <td class="label">Penyelenggara</td>
            <td>: {{ $meeting->creator->unit_name ?? 'Pemerintah Kabupaten Sinjai' }}</td>
        </tr>
    </table>

    <div class="section-title">Isi Notulen Rapat</div>
    <div class="content">{{ $meeting->minutes->content ?? 'Tidak ada catatan notulen.' }}</div>

    <div class="footer">
        <p>Disusun oleh,</p>
        <div class="signature">{{ $meeting->minutes->user->name ?? $meeting->creator->name }}</div>
        <p>Pembuat Notulen</p>
    </div>

</body>
</html>
