<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Hadir - {{ $meeting->title }}</title>
    <style>
        @page { size: a4 portrait; margin: 8mm 10mm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; line-height: 1.4; color: #333; }
        .kop-surat { width: 100%; border-bottom: 2.5px solid #000; padding-bottom: 8px; margin-bottom: 16px; position: relative; }
        .kop-surat .logo { position: absolute; left: 0; top: 0; width: 65px; }
        .kop-surat .text { text-align: center; margin-left: 75px; }
        .kop-surat h1 { font-size: 13pt; margin: 0; text-transform: uppercase; font-weight: bold; line-height: 1.2; }
        .kop-surat h2 { font-size: 11.5pt; margin: 3px 0; text-transform: uppercase; font-weight: bold; line-height: 1.2; }
        .kop-surat p { font-size: 8.5pt; margin: 0; font-style: italic; line-height: 1.3; }
        .doc-title { text-align: center; margin: 16px 0 12px 0; font-size: 12pt; font-weight: bold; text-decoration: underline; text-transform: uppercase; }

        .info-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; font-size: 9pt; }
        .info-table td { padding: 2.5px 4px; vertical-align: top; }
        .info-table .label { font-weight: bold; width: 130px; }
        
        .attendance-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 8.5pt; }
        .attendance-table th, .attendance-table td { border: 1px solid #000; padding: 4px 5px; text-align: left; }
        .attendance-table th { background-color: #f0f0f0; text-align: center; font-size: 8pt; font-weight: bold; }
        .attendance-table .center { text-align: center; }
        .signature-img { height: 26px; }
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
            $unitName = $meeting->creator?->unit_name ?? 'Sekretariat Daerah';
            $opd = \App\Models\Opd::where('name', $unitName)->first();
            if (!$opd && $unitName) {
                $cleanUnit = str_replace([',', '.', '-'], '', $unitName);
                $opd = \App\Models\Opd::whereRaw("REPLACE(REPLACE(REPLACE(name, ',', ''), '.', ''), '-', '') LIKE ?", ['%' . $cleanUnit . '%'])->first();
            }
            $opdAddress = $opd?->address ?: 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611';
            $opdPhone = $opd?->phone;
            $opdEmail = $opd?->email;
        ?>
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
        @endif
        
        <div class="text">
            <h1>PEMERINTAH KABUPATEN SINJAI</h1>
            <h2>{{ strtoupper($unitName) }}</h2>
            <p>
                {{ $opdAddress }}
                @if($opdPhone) &bull; Telp: {{ $opdPhone }} @endif
                @if($opdEmail) &bull; Email: {{ $opdEmail }} @endif
            </p>
        </div>
    </div>

    <div class="doc-title">DAFTAR HADIR</div>

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
                <th width="6%">No</th>
                <th width="32%">Nama Peserta</th>
                <th width="26%">OPD / Instansi</th>
                <th width="20%">Jabatan</th>
                <th width="16%">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($meeting->attendances as $index => $attendance)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
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
                    <td colspan="5" class="center">Belum ada peserta yang hadir.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <?php
        $signerTitle = $meeting->signer_title ?: ($opd?->leader_title ?: ('Kepala ' . $unitName));
        $signerName = $meeting->signer_name ?: ($opd?->leader_name ?: '..................................................');
        $signerNip = $meeting->signer_nip ?: ($opd?->leader_nip ?: '..................................................');
        $signerRank = $meeting->signer_rank ?: ($opd?->leader_rank ?: null);
    ?>

    <div style="margin-top: 25px; float: right; width: 320px; text-align: left; font-size: 9.5pt; line-height: 1.4; page-break-inside: avoid;">
        <p style="margin-bottom: 2px;">Sinjai, {{ $meeting->date->translatedFormat('d F Y') }}</p>
        <p style="font-weight: bold; margin-top: 0; margin-bottom: 50px; line-height: 1.3;">
            Mengetahui,<br>
            {{ $signerTitle }}
        </p>
        <p style="font-weight: bold; text-decoration: underline; margin-bottom: 1px;">
            {{ $signerName }}
        </p>
        <p style="margin: 0; font-size: 9pt;">
            NIP. {{ $signerNip }}
        </p>
        @if($signerRank)
            <p style="margin: 0; font-size: 9pt; color: #333;">
                Pangkat: {{ $signerRank }}
            </p>
        @endif
    </div>
    <div style="clear: both;"></div>

</body>
</html>
