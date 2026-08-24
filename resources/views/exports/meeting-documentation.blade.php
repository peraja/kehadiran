<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dokumentasi - {{ $meeting->title }}</title>
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
            line-height: 1.45;
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
            margin: 14px 0;
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
            font-size: 9.5pt;
        }

        .info-table td {
            padding: 3px 4px;
            vertical-align: top;
        }

        .info-table .label {
            font-weight: bold;
            width: 130px;
            color: #111827;
        }

        .divider {
            border-bottom: 1px solid #cbd5e1;
            margin-bottom: 14px;
        }

        .photo-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
            margin-top: 5px;
        }

        .photo-card {
            width: 50%;
            vertical-align: middle;
            border: 1px solid #e2e8f0;
            padding: 8px;
            background-color: #f8fafc;
            text-align: center;
            page-break-inside: avoid;
        }

        .photo-img {
            max-width: 100%;
            height: auto;
            max-height: 280px;
            display: block;
            margin: 0 auto;
        }

        .photo-caption {
            font-size: 8.5pt;
            color: #475569;
            font-weight: bold;
            margin-top: 5px;
            text-align: center;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            font-style: italic;
            color: #64748b;
            font-size: 9.5pt;
        }
    </style>
</head>

<body>
    @if($meeting->photos_signed_at)
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

    <div class="doc-title">DOKUMENTASI FOTO RAPAT</div>

    <table class="info-table">
        <tr>
            <td class="label">Agenda Rapat</td>
            <td>: {{ $meeting->title }}</td>
        </tr>
        <tr>
            <td class="label">Hari / Tanggal</td>
            <td>: {{ $meeting->date ? $meeting->date->translatedFormat('l, d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tempat</td>
            <td>: {{ $meeting->location ?? 'Online / Menyesuaikan' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    @if($meeting->photos->count() > 0)
    <table class="photo-table">
        <?php
        $photos = $meeting->photos->values();
        $chunks = $photos->chunk(2);
        $counter = 1;
        ?>
        @foreach($chunks as $row)
        <tr>
            @foreach($row as $photo)
            <?php
            $photoPath = storage_path('app/public/' . $photo->file);
            $photoBase64 = '';
            if (file_exists($photoPath)) {
                $ext = pathinfo($photoPath, PATHINFO_EXTENSION);
                $photoBase64 = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,' . base64_encode(file_get_contents($photoPath));
            }
            ?>
            <td class="photo-card">
                @if($photoBase64)
                <img src="{{ $photoBase64 }}" class="photo-img" alt="Foto Rapat">
                @else
                <div style="padding: 40px 0; color: #94a3b8; font-size: 8pt;">Foto tidak tersedia</div>
                @endif
            </td>
            <?php $counter++; ?>
            @endforeach

            @if($row->count() === 1)
            <td style="width: 50%; border: none; background: transparent;"></td>
            @endif
        </tr>
        @endforeach
    </table>
    @else
    <div class="empty-state">
        Tidak ada foto dokumentasi yang dilampirkan pada rapat ini.
    </div>
    @endif

    <?php
    $signerTitle = $meeting->signer_title ?: ($opd?->leader_title ?: ('Kepala ' . $unitName));
    $signerName = $meeting->signer_name ?: ($opd?->leader_name ?: '..................................................');
    $signerNip = $meeting->signer_nip ?: ($opd?->leader_nip ?: '..................................................');
    $signerRank = $meeting->signer_rank ?: ($opd?->leader_rank ?: null);
    $signedAt = $meeting->photos_signed_at;
    $qrCodeBase64 = '';
    if ($signedAt) {
        $qrData = route('meetings.verify.tte', ['meeting' => $meeting->id, 'type' => 'dokumentasi']);
        $qrCodeBase64 = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(46)->errorCorrection('H')->generate($qrData));
    }
    ?>

    <div style="margin-top: 25px; float: right; width: 285px; text-align: left; font-size: 9.5pt; line-height: 1.4; page-break-inside: avoid;">
        <p style="margin-bottom: 2px; font-size: 9.5pt;">Sinjai, {{ $meeting->date ? $meeting->date->translatedFormat('d F Y') : date('d F Y') }}</p>
        <p style="font-weight: bold; margin-top: 0; margin-bottom: 6px; font-size: 9.5pt; line-height: 1.3;">
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
