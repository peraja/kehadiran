<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Hadir - {{ $meeting->title }}</title>
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
        .info-table .label { font-weight: bold; width: 140px; }
        
        .attendance-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 9pt; }
        .attendance-table th, .attendance-table td { border: 1px solid #000; padding: 5px 6px; text-align: left; }
        .attendance-table th { background-color: #f0f0f0; text-align: center; font-size: 8.5pt; font-weight: bold; }
        .attendance-table .center { text-align: center; }
        .signature-img { height: 30px; }
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

    <div class="doc-title">DAFTAR HADIR RAPAT</div>

    <table class="info-table">
        <tr>
            <td class="label">Agenda Rapat</td>
            <td>: {{ $meeting->title }}</td>
        </tr>
        <tr>
            <td class="label">Hari / Tanggal</td>
            <td>: {{ $meeting->date->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Tempat</td>
            <td>: {{ $meeting->location ?? 'Online / Menyesuaikan' }}</td>
        </tr>
    </table>

    <table class="attendance-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="28%">Nama / NIP</th>
                <th width="23%">Instansi / OPD</th>
                <th width="20%">Jabatan</th>
                <th width="12%">Waktu</th>
                <th width="12%">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($meeting->attendances as $index => $attendance)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>
                        @if($attendance->user)
                            <div style="font-weight: bold;">{{ $attendance->user->name }}</div>
                            @if($attendance->user->nip)
                                <div style="font-size: 9pt; color: #555;">NIP. {{ $attendance->user->nip }}</div>
                            @endif
                        @else
                            <div style="font-weight: bold;">{{ $attendance->guest_name }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $attendance->user ? ($attendance->user->unit_name ?? 'Pemkab Sinjai') : $attendance->guest_agency }}
                    </td>
                    <td>
                        {{ $attendance->user ? ($attendance->user->jabatan ?? '-') : ($attendance->guest_position ?: '-') }}
                    </td>
                    <td class="center">{{ $attendance->check_in->format('H:i') }} WITA</td>
                    <td class="center">
                        @if($attendance->signature)
                            <img src="{{ $attendance->signature }}" class="signature-img" onerror="this.style.display='none'">
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center">Belum ada peserta yang hadir.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
