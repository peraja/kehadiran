<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class BsreEsignService
{
    protected string $url;
    protected string $username;
    protected string $password;
    protected string $location;

    public function __construct()
    {
        $this->url = rtrim(config('services.bsre.url', 'http://localhost:8080/api/v2'), '/');
        $this->username = config('services.bsre.username', '');
        $this->password = config('services.bsre.password', '');
        $this->location = config('services.bsre.location', 'Kabupaten Sinjai');
    }

    /**
     * Sign a meeting document (minutes, attendance, or photos) electronically via BSrE API.
     *
     * @param Meeting $meeting
     * @param User $user
     * @param string $type 'minutes' | 'attendance' | 'photos'
     * @param string $passphrase
     * @return array ['success' => bool, 'message' => string, 'path' => ?string]
     */
    public function signDocument(Meeting $meeting, User $user, string $type, string $passphrase): array
    {
        if (!$user->hasRole('pimpinan')) {
            return [
                'success' => false,
                'message' => 'Hanya pengguna dengan peran Pimpinan yang berhak menandatangani dokumen secara elektronik.',
            ];
        }

        if (empty($user->nik)) {
            return [
                'success' => false,
                'message' => 'NIK penandatangan belum terdaftar. Silakan lengkapi NIK pada profil.',
            ];
        }

        if ($meeting->status !== 'completed') {
            return [
                'success' => false,
                'message' => 'Pengesahan TTE hanya dapat dilakukan setelah status rapat diselesaikan.',
            ];
        }

        if (!$meeting->isSigner($user)) {
            return [
                'success' => false,
                'message' => 'Anda bukan pejabat penandatangan yang ditunjuk untuk rapat ini.',
            ];
        }

        // 1. Generate fresh PDF content based on document type
        $pdfOutput = $this->renderDocumentPdf($meeting, $type);
        $documentName = $this->getDocumentLabel($type);
        $reason = "Pengesahan {$documentName} - Agenda: {$meeting->title}";

        // 2. Call BSrE API /sign/pdf if configured and reachable
        try {
            $response = Http::timeout(20)
                ->withBasicAuth($this->username, $this->password)
                ->attach('file', $pdfOutput, "{$type}-{$meeting->id}.pdf", ['Content-Type' => 'application/pdf'])
                ->post("{$this->url}/sign/pdf", [
                    'nik' => $user->nik,
                    'passphrase' => $passphrase,
                    'tampilan' => 'VISIBLE',
                    'image' => 'false',
                    'location' => $this->location,
                    'reason' => $reason,
                    'page' => 1,
                    'xAxis' => 380,
                    'yAxis' => 120,
                    'width' => 100,
                    'height' => 100,
                ]);

            if ($response->successful()) {
                $signedPdfContent = $response->body();
                $filename = "signed_documents/{$type}_{$meeting->id}_" . time() . ".pdf";
                Storage::disk('public')->put($filename, $signedPdfContent);

                $this->updateMeetingSignedStatus($meeting, $user, $type, $filename);

                return [
                    'success' => true,
                    'message' => "Dokumen {$documentName} berhasil ditandatangani secara elektronik (BSrE).",
                    'path' => $filename,
                ];
            }

            // Handle API error response from BSrE
            $statusCode = $response->status();
            $errorData = $response->json();
            $apiMessage = $errorData['message'] ?? $errorData['error'] ?? $errorData['desc'] ?? null;

            if ($statusCode === 400 || $statusCode === 401 || $statusCode === 403) {
                $errorMessage = $apiMessage ?: 'Passphrase tidak valid atau akun tidak memiliki hak akses.';
            } elseif ($statusCode === 404) {
                $errorMessage = $apiMessage ?: 'NIK atau sertifikat elektronik tidak ditemukan di sistem BSrE.';
            } elseif ($statusCode === 422) {
                $errorMessage = $apiMessage ?: 'Sertifikat elektronik kedaluwarsa, belum aktif, atau format dokumen tidak sesuai.';
            } elseif ($statusCode >= 500) {
                $errorMessage = 'Layanan server BSrE sedang mengalami gangguan. Silakan coba kembali nanti.';
            } else {
                $errorMessage = $apiMessage ?: 'Terjadi kesalahan saat memproses tanda tangan elektronik.';
            }

            return [
                'success' => false,
                'message' => "Gagal TTE: {$errorMessage}",
            ];

        } catch (\Throwable $e) {
            Log::warning("BSrE API Connection Error: " . $e->getMessage());

            // If BSrE service is in local / test mode or no live BSrE credentials configured, fallback to simulated signing
            if (app()->environment('local', 'testing') || empty($this->username)) {
                $filename = "signed_documents/{$type}_{$meeting->id}_" . time() . ".pdf";
                Storage::disk('public')->put($filename, $pdfOutput);

                $this->updateMeetingSignedStatus($meeting, $user, $type, $filename);

                return [
                    'success' => true,
                    'message' => "Dokumen {$documentName} berhasil disahkan secara elektronik (Mode Pengujian Lokal).",
                    'path' => $filename,
                ];
            }

            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke server BSrE. Pastikan koneksi jaringan aktif dan URL BSrE dapat dijangkau.',
            ];
        }
    }

    /**
     * Sign all pending documents for a meeting in batch with a single passphrase entry.
     *
     * @param Meeting $meeting
     * @param User $user
     * @param string $passphrase
     * @return array ['success' => bool, 'message' => string, 'signed_count' => int]
     */
    public function signAllDocuments(Meeting $meeting, User $user, string $passphrase): array
    {
        $types = [];
        if (!$meeting->minutes_signed_at) {
            $types[] = 'minutes';
        }
        if (!$meeting->attendance_signed_at) {
            $types[] = 'attendance';
        }
        if (!$meeting->photos_signed_at) {
            $types[] = 'photos';
        }

        if (empty($types)) {
            return [
                'success' => true,
                'message' => 'Semua dokumen telah ditandatangani sebelumnya.',
                'signed_count' => 0,
            ];
        }

        $successCount = 0;
        $errorMessage = '';

        foreach ($types as $type) {
            $result = $this->signDocument($meeting, $user, $type, $passphrase);
            if ($result['success']) {
                $successCount++;
            } else {
                $errorMessage = $result['message'];
                break;
            }
        }

        if ($successCount === 0 && !empty($errorMessage)) {
            return [
                'success' => false,
                'message' => $errorMessage,
                'signed_count' => 0,
            ];
        }

        return [
            'success' => true,
            'message' => "Berhasil menandatangani {$successCount} dokumen secara elektronik sekaligus.",
            'signed_count' => $successCount,
        ];
    }

    /**
     * Render the original PDF binary content.
     */
    protected function renderDocumentPdf(Meeting $meeting, string $type): string
    {
        $meeting->loadMissing(['creator', 'opd', 'minutes', 'attendances.user', 'photos']);

        $view = match ($type) {
            'minutes' => 'exports.meeting-minutes',
            'attendance' => 'exports.meeting-attendance',
            'photos', 'documentation' => 'exports.meeting-documentation',
            default => 'exports.meeting-minutes',
        };

        $pdf = Pdf::loadView($view, [
            'meeting' => $meeting,
        ])->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Update database fields after signing.
     */
    protected function updateMeetingSignedStatus(Meeting $meeting, User $user, string $type, string $path): void
    {
        if ($type === 'minutes') {
            $meeting->update([
                'minutes_signed_at' => now(),
                'minutes_signed_by' => $user->id,
                'minutes_signed_path' => $path,
            ]);
        } elseif ($type === 'attendance') {
            $meeting->update([
                'attendance_signed_at' => now(),
                'attendance_signed_by' => $user->id,
                'attendance_signed_path' => $path,
            ]);
        } elseif (in_array($type, ['photos', 'documentation'])) {
            $meeting->update([
                'photos_signed_at' => now(),
                'photos_signed_by' => $user->id,
                'photos_signed_path' => $path,
            ]);
        }
    }

    /**
     * Human readable document label.
     */
    protected function getDocumentLabel(string $type): string
    {
        return match ($type) {
            'minutes' => 'Notulen Rapat',
            'attendance' => 'Presensi',
            'photos', 'documentation' => 'Dokumentasi',
            default => 'Dokumen Rapat',
        };
    }
}
