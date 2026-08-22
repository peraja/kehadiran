<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Notulen Rapat - {{ $meeting->title }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 8mm 10mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }

        .kop-surat {
            width: 100%;
            border-bottom: 2.5px solid #000;
            padding-bottom: 8px;
            margin-bottom: 16px;
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
            line-height: 1.2;
        }

        .kop-surat h2 {
            font-size: 11.5pt;
            margin: 3px 0;
            text-transform: uppercase;
            font-weight: bold;
            line-height: 1.2;
        }

        .kop-surat p {
            font-size: 8.5pt;
            margin: 0;
            font-style: italic;
            line-height: 1.3;
        }

        .doc-title {
            text-align: center;
            margin: 16px 0 12px 0;
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
            font-size: 9pt;
        }

        .info-table td {
            padding: 2.5px 4px;
            vertical-align: top;
        }

        .info-table .label {
            font-weight: bold;
            width: 130px;
        }

        .section-title {
            font-size: 10.5pt;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 6px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }

        .content {
            font-size: 9pt;
            text-align: justify;
            white-space: pre-line;
            padding-left: 5px;
            line-height: 1.5;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
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

    <div class="doc-title">NOTULEN RAPAT</div>

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
            <td class="label">Waktu</td>
            <td>: {{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time ? $meeting->end_time->format('H:i') : 'Selesai' }} WITA</td>
        </tr>
        <tr>
            <td class="label">Tempat</td>
            <td>: {{ $meeting->location ?? 'Online / Menyesuaikan' }}</td>
        </tr>
        <tr>
            <td class="label">Dibuat oleh</td>
            <td>: {{ $meeting->creator->name ?? 'Administrator' }} - {{ $meeting->creator->unit_name ?? 'Pemerintah Kabupaten Sinjai' }}</td>
        </tr>
    </table>

    <div class="section-title">Isi Notulen Rapat</div>
    <div class="content">{{ $meeting->minutes->content ?? 'Tidak ada catatan notulen.' }}</div>

    <?php
    $signerTitle = $meeting->signer_title ?: ($opd?->leader_title ?: ('Kepala ' . $unitName));
    $signerName = $meeting->signer_name ?: ($opd?->leader_name ?: '..................................................');
    $signerNip = $meeting->signer_nip ?: ($opd?->leader_nip ?: '..................................................');
    $signerRank = $meeting->signer_rank ?: ($opd?->leader_rank ?: null);
    ?>

    <div style="margin-top: 30px; width: 100%; font-size: 9.5pt; line-height: 1.4; page-break-inside: avoid;">
        <table style="width: 100%; border: none; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; text-align: left; vertical-align: top; border: none; padding: 0 15px 0 0;">
                    <p style="margin-bottom: 2px;">&nbsp;</p>
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
                </td>
                <td style="width: 50%; text-align: left; vertical-align: top; border: none; padding: 0 0 0 15px;">
                    <p style="margin-bottom: 2px;">Sinjai, {{ $meeting->date->translatedFormat('d F Y') }}</p>
                    <p style="font-weight: bold; margin-top: 0; margin-bottom: 50px; line-height: 1.3;">
                        Disusun oleh,<br>
                        Pembuat Notulen
                    </p>
                    <p style="font-weight: bold; text-decoration: underline; margin-bottom: 1px;">
                        {{ $meeting->minutes->user->name ?? $meeting->creator->name }}
                    </p>
                    <p style="margin: 0; font-size: 9pt;">
                        NIP. {{ ($meeting->minutes->user->nip ?? $meeting->creator->nip) ?: '..................................................' }}
                    </p>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>