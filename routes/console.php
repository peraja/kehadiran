<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('model:prune')->daily();

Artisan::command('erapat:test-pppk {nip=197803292025212036}', function ($nip) {
    $this->info("=== Diagnosa Mendalam API PPPK Paruh Waktu: {$nip} ===");

    $token = config('services.pppk_pw.token');
    if (empty($token)) {
        $this->error('Token PPPK PW belum dikonfigurasi. Set PPPK_PW_TOKEN di .env.');
        return 1;
    }
    $host = 'tte.sinjaikab.go.id';
    $resolvedIp = gethostbyname($host);
    $serverIp = gethostbyname(gethostname());

    $this->line("Hostname: " . gethostname());
    $this->line("Resolved IP tte: {$resolvedIp}");
    $this->line("Server IP: {$serverIp}");

    $candidates = [
        '1. HTTPS via Server Interface IP (' . $serverIp . ':443)' => [
            'url' => "https://{$host}/api/v1/pppk-pw",
            'options' => [
                'force_ip_resolve' => 'v4',
                'curl' => [CURLOPT_RESOLVE => ["{$host}:443:{$serverIp}"]],
            ],
        ],
        '2. HTTP via Server Interface IP (' . $serverIp . ':80)' => [
            'url' => "http://{$host}/api/v1/pppk-pw",
            'options' => [
                'force_ip_resolve' => 'v4',
                'curl' => [CURLOPT_RESOLVE => ["{$host}:80:{$serverIp}"]],
            ],
        ],
        '3. HTTPS Standard (Port 443)' => [
            'url' => "https://{$host}/api/v1/pppk-pw",
            'options' => ['force_ip_resolve' => 'v4'],
        ],
        '4. HTTP Standard (Port 80)' => [
            'url' => "http://{$host}/api/v1/pppk-pw",
            'options' => ['force_ip_resolve' => 'v4'],
        ],
    ];

    $workingCandidate = null;

    foreach ($candidates as $name => $cfg) {
        $this->newLine();
        $this->info("Menguji: {$name}");
        $this->line("URL: " . $cfg['url']);
        try {
            $start = microtime(true);
            $req = \Illuminate\Support\Facades\Http::timeout(5)
                ->connectTimeout(2)
                ->withoutVerifying()
                ->withHeaders([
                    'Host' => $host,
                    'User-Agent' => 'Mozilla/5.0 (compatible; eRapat/1.5)',
                    'Accept' => 'application/json',
                ])
                ->withToken($token);

            if (!empty($cfg['options'])) {
                $req->withOptions($cfg['options']);
            }

            $res = $req->get($cfg['url'], ['nip' => $nip]);
            $dur = round((microtime(true) - $start) * 1000);

            $this->info("Status: " . $res->status() . " ({$dur}ms)");
            $body = substr(trim($res->body()), 0, 150);
            $this->line("Body: " . $body);

            if ($res->successful()) {
                $json = $res->json();
                if (!empty($json['data'] ?? [])) {
                    $this->info(">>> [SUKSES BERHASIL] Data ditemukan! <<<");
                    $this->info("Nama: " . ($json['data'][0]['name'] ?? '-'));
                    $this->info("Jabatan: " . ($json['data'][0]['jabatan'] ?? '-'));
                    $workingCandidate = $cfg;
                    break;
                }
            }
        } catch (\Throwable $e) {
            $this->error("Gagal: " . $e->getMessage());
        }
    }

    $this->newLine();
    if ($workingCandidate) {
        $this->info("SOLUSI DITEMUKAN!");
    } else {
        $this->warn("Semua jalur gagal diuji.");
    }
    return 0;
})->purpose('Uji koneksi dan diagnostik API PPPK Paruh Waktu');


