<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Notulen - {{ $meeting->title }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">
    <style>
        @page {
            size: a4 portrait;
            margin: 8mm 10mm 12mm 10mm;
        }

        * {
            font-family: 'Helvetica', 'Arial', sans-serif;
            box-sizing: border-box;
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

        .divider {
            border-bottom: 1px solid #cbd5e1;
            margin-bottom: 20px;
        }

        .content {
            font-size: 10pt;
            line-height: 1.5;
            color: #111827;
            padding: 0 0 10px 0;
        }

        .section-title-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        .section-title-table:first-child {
            margin-top: 0;
        }

        .section-title-table td {
            border: none;
            padding: 0;
            vertical-align: top;
            font-size: 10pt;
            line-height: 1.5;
            font-weight: bold;
            color: #0f172a;
        }

        .section-title-table .col-sec-num {
            width: 22px;
            text-align: left;
        }

        .section-title-table .col-sec-title {
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .section-para {
            margin: 0 0 8px 0;
            padding-left: 22px;
            line-height: 1.5;
            text-align: justify;
            color: #111827;
        }

        .point-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin: 0 0 6px 0;
        }

        .point-table td {
            border: none;
            padding: 2.5px 0 3.5px 0;
            vertical-align: top;
            font-size: 10pt;
            line-height: 1.5;
        }

        .point-table .col-indent-lvl1 {
            width: 22px;
        }

        .point-table .col-indent-lvl2 {
            width: 44px;
        }

        .point-table .col-indent-lvl3 {
            width: 66px;
        }

        .point-table .col-num {
            width: 22px;
            text-align: left;
            vertical-align: top;
            color: #111827;
            line-height: 1.5;
        }

        .point-table .col-text {
            text-align: justify;
            color: #111827;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    @if($meeting->minutes_signed_at)
    <footer>
        Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan oleh BSrE - BSSN.
    </footer>
    @endif

    <div class="kop-surat">
        <?php
        $logoPath = public_path('img/logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $opd = $meeting->opd ?: \App\Models\Opd::where('name', $meeting->creator?->unit_name)->first();
        $unitName = $opd?->name ?? ($meeting->creator?->unit_name ?? 'Pemerintah Kabupaten Sinjai');
        $opdAddress = $opd?->address ?: 'Kabupaten Sinjai, Sulawesi Selatan';
        $opdPhone = $opd?->phone ?: null;
        $opdEmail = $opd?->email ?: null;

        $signerName = $meeting->signer_name ?: ($opd?->leader_name ?: '..................................................');
        $signerNip = $meeting->signer_nip ?: ($opd?->leader_nip ?: '..................................................');
        $signerRank = $meeting->signer_rank ?: ($opd?->leader_rank ?: null);
        $signerTitle = $meeting->signer_title ?: ($opd?->leader_title ?: ('Kepala ' . $unitName));
        $signedAt = $meeting->minutes_signed_at;

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

    <div class="doc-title">NOTULEN RAPAT</div>

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

    <div class="divider"></div>

    <?php
    $rawContent = $meeting->minutes ? trim((string)$meeting->minutes->content) : '';
    ?>

    <div class="content">
        @if(!empty($rawContent))
            <?php
            $lines = explode("\n", $rawContent);
            $inTable = false;

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }

                // 1. Deteksi Judul Bagian Utama (Level 1: 1. Pembukaan, 2. Pembahasan, 3. Kesimpulan, I. PEMBUKAAN, dst.)
                if (preg_match('/^(\d+\.|(?:I|II|III|IV|V|VI|VII|VIII|IX|X)\.)\s+([a-zA-Z\s\/\-\&]+)$/', $trimmed, $matches) && mb_strlen(trim($matches[2])) <= 40) {
                    if ($inTable) {
                        echo '</tbody></table>';
                        $inTable = false;
                    }
                    $secNum = htmlspecialchars($matches[1]);
                    $secTitle = htmlspecialchars(strtoupper(trim($matches[2])));
                    echo '<table class="section-title-table"><tr>'
                        . '<td class="col-sec-num">' . $secNum . '</td>'
                        . '<td class="col-sec-title">' . $secTitle . '</td>'
                        . '</tr></table>';
                    continue;
                }

                // 2. Deteksi Sub-Poin Huruf (Level 2: a. s/d z. atau A. s/d Z.)
                if (preg_match('/^([a-zA-Z]\.)\s+(.+)$/', $trimmed, $matches)) {
                    if (!$inTable) {
                        echo '<table class="point-table"><tbody>';
                        $inTable = true;
                    }
                    $bullet = $matches[1];
                    $text = htmlspecialchars(trim($matches[2]));
                    echo '<tr>'
                        . '<td class="col-indent-lvl1"></td>'
                        . '<td class="col-num">' . $bullet . '</td>'
                        . '<td class="col-text">' . $text . '</td>'
                        . '</tr>';
                    continue;
                }

                // 3. Deteksi Sub-Sub-Poin Angka Kurung / Huruf Kurung (Level 3: 1), 2), a), b), (1), (a))
                if (preg_match('/^(\(?\d+\)|\(?[a-zA-Z]\))\s+(.+)$/', $trimmed, $matches)) {
                    if (!$inTable) {
                        echo '<table class="point-table"><tbody>';
                        $inTable = true;
                    }
                    $bullet = $matches[1];
                    $text = htmlspecialchars(trim($matches[2]));
                    echo '<tr>'
                        . '<td class="col-indent-lvl2"></td>'
                        . '<td class="col-num">' . $bullet . '</td>'
                        . '<td class="col-text">' . $text . '</td>'
                        . '</tr>';
                    continue;
                }

                // 4. Deteksi Sub-Sub-Poin Bullet / Strip (Level 3: -, *, •, –)
                if (preg_match('/^(\-|\*|•|–)\s+(.+)$/', $trimmed, $matches)) {
                    if (!$inTable) {
                        echo '<table class="point-table"><tbody>';
                        $inTable = true;
                    }
                    $bullet = '&bull;';
                    $text = htmlspecialchars(trim($matches[2]));
                    echo '<tr>'
                        . '<td class="col-indent-lvl2"></td>'
                        . '<td class="col-num">' . $bullet . '</td>'
                        . '<td class="col-text">' . $text . '</td>'
                        . '</tr>';
                    continue;
                }

                // 5. Paragraf Narasi Biasa (Pengantar / Pembuka / Narasi Umum)
                if ($inTable) {
                    echo '</tbody></table>';
                    $inTable = false;
                }
                echo '<p class="section-para">' . htmlspecialchars($trimmed) . '</p>';
            }

            if ($inTable) {
                echo '</tbody></table>';
            }
            ?>
        @else
            <em style="color: #64748b; padding-left: 20px;">Tidak ada catatan notulen.</em>
        @endif
    </div>

    <?php
    $qrCodeBase64 = '';
    if ($signedAt) {
        $qrData = route('meetings.verify.tte', ['meeting' => $meeting->id, 'type' => 'notulen']);
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