<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Presensi - {{ $meeting->title }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">
    <style>
        @page {
            size: a4 portrait;
            margin: 8mm 10mm 12mm 10mm;
        }

        * {
            font-family: 'Helvetica', 'Arial', sans-serif;
        }

        body, table, td, th, p, div, span, h1, h2, h3, a, footer {
            font-family: 'Helvetica', 'Arial', sans-serif;
        }

        body {
            font-size: 10pt;
            line-height: 1.5;
            color: #111827;
        }

        footer {
            position: fixed;
            bottom: -7mm;
            left: 0;
            right: 0;
            height: 18px;
            border-top: 0.5px solid #cbd5e1;
            padding-top: 3px;
            font-size: 7.5pt;
            color: #64748b;
            font-style: italic;
            line-height: 1.2;
            text-align: center;
        }

        .kop-surat {
            width: 100%;
            border-bottom: 2.5px solid #000;
            padding-bottom: 8px;
            margin-bottom: 14px;
            position: relative;
        }

        .kop-surat .logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 65px;
        }

        .kop-surat .text {
            text-align: center;
            margin-left: 75px;
        }

        .kop-surat h1 {
            font-size: 13pt;
            margin: 0;
            text-transform: uppercase;
            font-weight: bold;
            line-height: 1.25;
            letter-spacing: 0.3px;
        }

        .kop-surat h2 {
            font-size: 11.5pt;
            margin: 2px 0 3px 0;
            text-transform: uppercase;
            font-weight: bold;
            line-height: 1.25;
            letter-spacing: 0.3px;
        }

        .kop-surat p {
            font-size: 8.5pt;
            margin: 0;
            font-style: italic;
            line-height: 1.35;
            color: #475569;
        }

        .doc-title {
            text-align: center;
            margin: 20px 0 16px 0;
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
            font-size: 9.5pt;
            line-height: 1.5;
        }

        .info-table td {
            padding: 4px 2px;
            vertical-align: top;
            line-height: 1.5;
        }

        .info-table .label {
            font-weight: bold;
            width: 125px;
            color: #111827;
        }

        .info-table .colon {
            width: 12px;
            text-align: left;
            color: #111827;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 9pt;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #94a3b8;
            padding: 5px 6px;
            text-align: left;
        }

        .attendance-table th {
            background-color: #f1f5f9;
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
            color: #111827;
        }

        .attendance-table .center {
            text-align: center;
        }

        .signature-img {
            height: 26px;
        }
    </style>
</head>

<body>
    @if($meeting->attendance_signed_at)
    <footer>
        Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan oleh BSrE - BSSN.
    </footer>
    @endif

    <div class="kop-surat">
        <?php
        $logoPath = public_path('img/logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
        $opd = $meeting->opd;
        $unitName = $opd?->name ?? $meeting->creator?->unit_name ?? 'Sekretariat Daerah';
        if (!$opd && $unitName) {
            $cleanUnit = str_replace([',', '.', '-'], '', $unitName);
            $opd = \App\Models\Opd::whereRaw("REPLACE(REPLACE(REPLACE(name, ',', ''), '.', ''), '-', '') LIKE ?", ['%' . $cleanUnit . '%'])->first();
        }
        $opdAddress = $opd?->address ?: 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611';
        $opdPhone = $opd?->phone;
        $opdEmail = $opd?->email;

        $signerTitle = $meeting->signer_title ?: ($opd?->leader_title ?: ('Kepala ' . $unitName));
        $signerName = $meeting->signer_name ?: ($opd?->leader_name ?: '..................................................');
        $signerNip = $meeting->signer_nip ?: ($opd?->leader_nip ?: '..................................................');
        $signerRank = $meeting->signer_rank ?: (\App\Models\User::where('nip', $meeting->signer_nip)->value('pangkat') ?: (\App\Models\User::where('name', $meeting->signer_name)->value('pangkat') ?: ($opd?->leader_rank ?: null)));
        $signedAt = $meeting->attendance_signed_at;

        $timeFormatted = $meeting->start_time && $meeting->end_time
            ? $meeting->start_time->format('H:i') . ' - ' . $meeting->end_time->format('H:i') . ' WITA'
            : ($meeting->start_time ? $meeting->start_time->format('H:i') . ' WITA' : '-');
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

    <div class="doc-title">PRESENSI RAPAT</div>

    <table class="info-table">
        <tr>
            <td class="label">Agenda</td>
            <td class="colon">:</td>
            <td>{{ $meeting->title }}</td>
        </tr>
        <tr>
            <td class="label">Hari / Tanggal</td>
            <td class="colon">:</td>
            <td>{{ $meeting->date ? $meeting->date->translatedFormat('l, d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Waktu</td>
            <td class="colon">:</td>
            <td>{{ $timeFormatted }}</td>
        </tr>
        <tr>
            <td class="label">Tempat</td>
            <td class="colon">:</td>
            <td>{{ $meeting->location ?? 'Online / Menyesuaikan' }}</td>
        </tr>
        <tr>
            <td class="label">Pimpinan</td>
            <td class="colon">:</td>
            <td>
                <div>{{ $signerName }}</div>
                @if($signerTitle)
                <div style="font-size: 8.5pt; color: #475569; margin-top: 1.5px; line-height: 1.5;">{{ $signerTitle }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="attendance-table">
        <thead>
            <tr>
                <th width="6%">No</th>
                <th width="32%">Nama Peserta</th>
                <th width="26%">Unit Kerja / Instansi</th>
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
                    @if($attendance->guest_nip)
                    <div style="font-size: 9pt; color: #555;">NIP. {{ $attendance->guest_nip }}</div>
                    @endif
                    @endif
                </td>
                <td>
                    @php
                        $agency = $attendance->guest_agency;
                        if ($agency && str_contains($agency, ' /// ')) {
                            $agency = explode(' /// ', $agency, 2)[0];
                        }

                        $formatTitle = function(?string $str) {
                            if (empty($str)) return '';
                            $acronyms = ['SD', 'SMP', 'SMA', 'SMK', 'TK', 'PAUD', 'UPTD', 'RSUD', 'PUSTU', 'PNS', 'PPPK', 'PUPR', 'BKAD', 'BPBD', 'BAPPEDA', 'DISHUB', 'DLHK', 'DPRD', 'OPD', 'SPBE', 'TTE', 'BSRE', 'NIK', 'KTP', 'KTU', 'SKM', 'M.SI', 'S.PD', 'S.SOS', 'S.KOM', 'S.STP', 'SE', 'SH', 'ST', 'MM'];
                            $words = explode(' ', strtolower(trim($str)));
                            $words = array_map(function($w) use ($acronyms) {
                                $upper = strtoupper($w);
                                $cleanUpper = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $w));
                                if (in_array($cleanUpper, $acronyms) || in_array($upper, $acronyms)) {
                                    return $upper;
                                }
                                if (in_array($w, ['dan', 'atau', 'di', 'ke', 'dari', 'pada', 'untuk', 'dengan', 'tentang', 'serta'])) {
                                    return $w;
                                }
                                return ucfirst($w);
                            }, $words);
                            return ucfirst(implode(' ', $words));
                        };

                        $rawUnit = $agency ?: ($attendance->user ? ($attendance->user->unit_name ?? 'Pemkab Sinjai') : '-');
                        $displayUnit = $formatTitle($rawUnit);
                        $position = ($attendance->user ? $attendance->user->jabatan : $attendance->guest_position) ?: '-';
                        $displayPosition = $formatTitle($position);
                    @endphp
                    <div style="line-height: 1.25;">{{ $displayUnit }}</div>
                </td>
                <td>
                    <div style="line-height: 1.25;">{{ $displayPosition }}</div>
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
    $signerRank = $meeting->signer_rank ?: (\App\Models\User::where('nip', $meeting->signer_nip)->value('pangkat') ?: (\App\Models\User::where('name', $meeting->signer_name)->value('pangkat') ?: ($opd?->leader_rank ?: null)));
    $signedAt = $meeting->attendance_signed_at;
    $qrCodeBase64 = '';
    if ($signedAt) {
        $qrData = route('meetings.verify.tte', ['meeting' => $meeting->id, 'type' => 'presensi']);
        $qrCodeBase64 = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(46)->errorCorrection('H')->generate($qrData));
    }
    ?>

    <div style="margin-top: 25px; float: right; width: 285px; text-align: left; font-size: 9.5pt; line-height: 1.45; page-break-inside: avoid;">
        <p style="margin-bottom: 2px; font-size: 9.5pt; line-height: 1.45;">Sinjai, {{ $meeting->date ? $meeting->date->translatedFormat('d F Y') : date('d F Y') }}</p>
        <p style="font-weight: bold; margin-top: 0; margin-bottom: 6px; font-size: 9.5pt; line-height: 1.4;">
            Mengetahui,<br>
            {{ $signerTitle }}
        </p>

        @if($signedAt && $qrCodeBase64)
        <a href="{{ $qrData }}" target="_blank" style="text-decoration: none; color: inherit; display: inline-block; margin: 0 0 6px 0;">
            <div style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 8px; background-color: #f8fafc; display: inline-block;">
                <table style="border-collapse: collapse; border: none; font-family: 'Helvetica', 'Arial', sans-serif;">
                    <tr>
                        <td style="border: none; padding: 0 8px 0 0; vertical-align: middle; width: 42px;">
                            <img src="data:image/svg+xml;base64,{{ $qrCodeBase64 }}" style="width: 42px; height: 42px; display: block;" alt="QR Code TTE">
                        </td>
                        <td style="border: none; padding: 0; vertical-align: middle; line-height: 1.35; font-family: 'Helvetica', 'Arial', sans-serif; white-space: nowrap;">
                            <div style="font-family: 'Helvetica', 'Arial', sans-serif; font-weight: bold; color: #0f172a; font-size: 7.5pt;">Ditandatangani Secara Elektronik</div>
                            <div style="font-family: 'Helvetica', 'Arial', sans-serif; color: #334155; font-size: 7.5pt;">Pemerintah Kabupaten Sinjai</div>
                            <div style="font-family: 'Helvetica', 'Arial', sans-serif; color: #64748b; font-size: 7pt;">BSrE - BSSN</div>
                        </td>
                    </tr>
                </table>
            </div>
        </a>
        @else
        <div style="height: 50px;"></div>
        @endif

        <p style="font-weight: bold; margin: 0 0 1px 0; font-size: 10pt;">
            {{ $signerName }}
        </p>
        @if($signerRank)
        <p style="margin: 0; font-size: 9pt; color: #334155;">
            {{ $signerRank }}
        </p>
        @endif
        <p style="margin: 0; font-size: 9pt; color: #334155;">
            NIP. {{ $signerNip }}
        </p>
    </div>
    <div style="clear: both;"></div>

</body>

</html>
