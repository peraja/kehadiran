<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('model:prune')->daily();

Artisan::command('erapat:test-pppk {nip=197803292025212036}', function ($nip) {
    $this->info("=== Diagnosa Pengecekan NIP: {$nip} ===");

    $token = config('services.pppk_pw.token') ?: 'sJ9k2Lp5mN8qR1t4vW7xZ0y3bC6fH9hS';
    $baseUrl = config('services.pppk_pw.url') ?: 'https://tte.sinjaikab.go.id/api/v1/pppk-pw';

    $this->line("URL: {$baseUrl}");
    $this->line("Token: " . substr($token, 0, 6) . '...' . substr($token, -4));

    $endpoints = [
        'HTTPS (Standard)' => $baseUrl,
        'HTTP (Port 80)' => str_replace('https://', 'http://', $baseUrl),
    ];

    foreach ($endpoints as $label => $url) {
        $this->newLine();
        $this->info("1. Mencoba {$label} -> {$url}");
        try {
            $start = microtime(true);
            $res = \Illuminate\Support\Facades\Http::timeout(8)
                ->connectTimeout(3)
                ->withoutVerifying()
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; eRapat/1.5; +https://rapat.sinjaikab.go.id)',
                    'Accept' => 'application/json',
                ])
                ->withToken($token)
                ->get($url, ['nip' => $nip]);
            $dur = round((microtime(true) - $start) * 1000);
            $this->info("   HTTP Status: " . $res->status() . " ({$dur}ms)");
            $this->line("   Body: " . substr($res->body(), 0, 250));
            if ($res->successful() && !empty($res->json()['data'] ?? [])) {
                $data = $res->json()['data'][0];
                $this->info("   [BERHASIL DITEMUKAN]");
                $this->info("   Nama: " . ($data['name'] ?? '-'));
                $this->info("   Jabatan: " . ($data['jabatan'] ?? '-'));
                $this->info("   Unit ID: " . ($data['api_unit_id'] ?? '-'));
                return 0;
            }
        } catch (\Throwable $e) {
            $this->error("   Error: " . $e->getMessage());
        }

        $this->info("2. Mencoba Loopback 127.0.0.1 ({$label})");
        try {
            $host = parse_url($url, PHP_URL_HOST) ?: 'tte.sinjaikab.go.id';
            $start = microtime(true);
            $res = \Illuminate\Support\Facades\Http::timeout(8)
                ->connectTimeout(3)
                ->withoutVerifying()
                ->withOptions([
                    'force_ip_resolve' => 'v4',
                    'curl' => [
                        CURLOPT_RESOLVE => [
                            "{$host}:443:127.0.0.1",
                            "{$host}:80:127.0.0.1",
                        ],
                    ],
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; eRapat/1.5; +https://rapat.sinjaikab.go.id)',
                    'Accept' => 'application/json',
                ])
                ->withToken($token)
                ->get($url, ['nip' => $nip]);
            $dur = round((microtime(true) - $start) * 1000);
            $this->info("   HTTP Status: " . $res->status() . " ({$dur}ms)");
            $this->line("   Body: " . substr($res->body(), 0, 250));
            if ($res->successful() && !empty($res->json()['data'] ?? [])) {
                $data = $res->json()['data'][0];
                $this->info("   [BERHASIL DITEMUKAN]");
                $this->info("   Nama: " . ($data['name'] ?? '-'));
                $this->info("   Jabatan: " . ($data['jabatan'] ?? '-'));
                $this->info("   Unit ID: " . ($data['api_unit_id'] ?? '-'));
                return 0;
            }
        } catch (\Throwable $e) {
            $this->error("   Loopback Error: " . $e->getMessage());
        }
    }

    $this->newLine();
    $this->warn("Hasil: Data tidak dapat diambil dari seluruh endpoint di server ini.");
    return 1;
})->purpose('Uji koneksi dan diagnostik API PPPK Paruh Waktu');

