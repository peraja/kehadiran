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
        $url = rtrim(config('services.bsre.url', 'http://localhost:8080/api/v2'), '/');
        if (!str_contains($url, '/api/v2')) {
            $url .= '/api/v2';
        }
        $this->url = $url;
        $this->username = config('services.bsre.username', '');
        $this->password = config('services.bsre.password', '');
        $this->location = config('services.bsre.location', 'Kabupaten Sinjai');
    }

    /**
     * Check user TTE certificate status by NIK from BSrE API (Petunjuk Teknis Section 6.7).
     *
     * @param string|null $nik
     * @return array ['status' => string, 'label' => string, 'description' => string, 'can_sign' => bool, 'badge_class' => string]
     */
    public function checkUserStatus(?string $nik): array
    {
        if (empty($nik)) {
            return [
                'status' => 'NOT_REGISTERED',
                'label' => 'NIK Kosong',
                'description' => 'NIK penandatangan belum diatur pada profil.',
                'can_sign' => false,
                'badge_class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'dot_class' => 'bg-slate-400',
            ];
        }

        try {
            $response = Http::timeout(8)
                ->withBasicAuth($this->username, $this->password)
                ->post("{$this->url}/user/check/status", [
                    'nik' => $nik,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $rawStatus = strtoupper(trim((string)(
                    $data['status'] ?? $data['status_user'] ?? $data['statusUser'] ?? 'UNKNOWN'
                )));

                return $this->formatStatusResponse($rawStatus, $data['message'] ?? null);
            }

            $statusCode = $response->status();
            $data = $response->json();
            $rawStatus = strtoupper(trim((string)(
                $data['status'] ?? $data['status_user'] ?? $data['statusUser'] ?? ''
            )));

            if ($rawStatus) {
                return $this->formatStatusResponse($rawStatus, $data['message'] ?? null);
            }

            if ($statusCode === 404) {
                return $this->formatStatusResponse('NOT_REGISTERED');
            }

        } catch (\Throwable $e) {
            Log::warning("BSrE Check Status Error for NIK {$nik}: " . $e->getMessage());
        }

        // Local / test fallback simulation
        if (app()->environment('local', 'testing') || empty($this->username)) {
            return $this->formatStatusResponse('ISSUE', 'Sertifikat elektronik aktif dan siap digunakan.');
        }

        return [
            'status' => 'UNKNOWN',
            'label' => 'Offline',
            'description' => 'Tidak dapat terhubung ke server BSrE.',
            'can_sign' => false,
            'badge_class' => 'bg-amber-50 text-amber-700 border-amber-200',
            'dot_class' => 'bg-amber-500',
        ];
    }

    /**
     * Map BSrE raw status code to human readable metadata (Petunjuk Teknis BSrE).
     */
    public function formatStatusResponse(string $status, ?string $customMessage = null): array
    {
        return match ($status) {
            'ISSUE' => [
                'status' => 'ISSUE',
                'label' => 'Aktif',
                'description' => $customMessage ?: 'Sertifikat elektronik aktif dan siap digunakan.',
                'can_sign' => true,
                'badge_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'dot_class' => 'bg-emerald-500 animate-pulse',
            ],
            'EXPIRED' => [
                'status' => 'EXPIRED',
                'label' => 'Expired',
                'description' => $customMessage ?: 'Masa berlaku sertifikat telah berakhir.',
                'can_sign' => false,
                'badge_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'dot_class' => 'bg-rose-500',
            ],
            'RENEW' => [
                'status' => 'RENEW',
                'label' => 'Pembaruan',
                'description' => $customMessage ?: 'Sertifikat sedang dalam proses perpanjangan.',
                'can_sign' => false,
                'badge_class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'dot_class' => 'bg-amber-500',
            ],
            'WAITING_FOR_VERIFICATION' => [
                'status' => 'WAITING_FOR_VERIFICATION',
                'label' => 'Verifikasi',
                'description' => $customMessage ?: 'Sertifikat sedang diverifikasi oleh BSrE.',
                'can_sign' => false,
                'badge_class' => 'bg-sky-50 text-sky-700 border-sky-200',
                'dot_class' => 'bg-sky-500',
            ],
            'NEW' => [
                'status' => 'NEW',
                'label' => 'Belum Aktif',
                'description' => $customMessage ?: 'Sertifikat baru belum diaktivasi.',
                'can_sign' => false,
                'badge_class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'dot_class' => 'bg-amber-500',
            ],
            'NO_CERTIFICATE' => [
                'status' => 'NO_CERTIFICATE',
                'label' => 'Tanpa Sertifikat',
                'description' => $customMessage ?: 'Akun belum memiliki sertifikat elektronik.',
                'can_sign' => false,
                'badge_class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'dot_class' => 'bg-slate-500',
            ],
            'NOT_REGISTERED' => [
                'status' => 'NOT_REGISTERED',
                'label' => 'Belum Terdaftar',
                'description' => $customMessage ?: 'NIK belum terdaftar di server BSrE.',
                'can_sign' => false,
                'badge_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'dot_class' => 'bg-rose-500',
            ],
            'SUSPEND' => [
                'status' => 'SUSPEND',
                'label' => 'Suspend',
                'description' => $customMessage ?: 'Sertifikat sedang ditangguhkan.',
                'can_sign' => false,
                'badge_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'dot_class' => 'bg-rose-500',
            ],
            'REVOKE' => [
                'status' => 'REVOKE',
                'label' => 'Dicabut',
                'description' => $customMessage ?: 'Sertifikat elektronik telah dicabut.',
                'can_sign' => false,
                'badge_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'dot_class' => 'bg-rose-500',
            ],
            default => [
                'status' => $status,
                'label' => $status,
                'description' => $customMessage ?: 'Status sertifikat: ' . $status,
                'can_sign' => $status === 'ISSUE',
                'badge_class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'dot_class' => 'bg-slate-400',
            ],
        };
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
        $reason = $this->generateReason($meeting, $type);

        // 2. Call BSrE API /sign/pdf if configured and reachable
        try {
            $pdfBase64 = base64_encode($pdfOutput);
            $payload = [
                'nik' => $user->nik,
                'passphrase' => $passphrase,
                'signatureProperties' => [
                    [
                        'tampilan' => 'INVISIBLE',
                        'location' => $this->location,
                        'reason' => $reason,
                    ]
                ],
                'file' => [$pdfBase64],
            ];

            $response = Http::timeout(30)
                ->withBasicAuth($this->username, $this->password)
                ->post("{$this->url}/sign/pdf", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (is_array($responseData) && !empty($responseData['file'][0])) {
                    $signedPdfContent = base64_decode($responseData['file'][0]);
                } else {
                    $signedPdfContent = $response->body();
                }

                $filename = "signed_documents/{$type}_{$meeting->id}_" . time() . ".pdf";
                Storage::disk('public')->put($filename, $signedPdfContent);

                $this->updateMeetingSignedStatus($meeting, $user, $type, $filename);

                return [
                    'success' => true,
                    'message' => "Dokumen {$documentName} berhasil ditandatangani.",
                    'path' => $filename,
                ];
            }

            // Handle API error response from BSrE
            $errorMessage = $this->parseBsreSigningError($response);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning("BSrE Connection Exception: " . $e->getMessage());

            if (app()->environment('local', 'testing') && empty($this->username)) {
                $filename = "signed_documents/{$type}_{$meeting->id}_" . time() . ".pdf";
                Storage::disk('public')->put($filename, $pdfOutput);
                $this->updateMeetingSignedStatus($meeting, $user, $type, $filename);

                return [
                    'success' => true,
                    'message' => "Dokumen {$documentName} berhasil ditandatangani (Simulasi).",
                    'path' => $filename,
                ];
            }

            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke server BSrE.',
            ];

        } catch (\Throwable $e) {
            Log::warning("BSrE API Error: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal memproses TTE.',
            ];
        }
    }

    /**
     * Parse and extract user-friendly error message from BSrE API response.
     */
    public function parseBsreSigningError(\Illuminate\Http\Client\Response $response): string
    {
        $statusCode = $response->status();
        $errorData = $response->json();

        $apiMessage = is_array($errorData) ? ($errorData['error'] ?? $errorData['message'] ?? $errorData['desc'] ?? null) : null;
        $bsreCode = is_array($errorData) ? ($errorData['status_code'] ?? $errorData['code'] ?? null) : null;

        // BSrE official status code mapping
        if ($bsreCode) {
            return match ((int) $bsreCode) {
                2031 => 'Passphrase salah.',
                2011 => 'NIK belum terdaftar di BSrE.',
                2021 => 'Sertifikat belum aktif.',
                2022 => 'Sertifikat sudah expired.',
                2023 => 'Sertifikat dalam pembaruan.',
                2024 => 'Sertifikat ditangguhkan.',
                2025 => 'Sertifikat telah dicabut.',
                2041 => 'Dokumen PDF tidak valid.',
                default => $apiMessage ?: "Error BSrE: {$bsreCode}",
            };
        }

        // Check if message text specifically mentions passphrase or authentication
        if ($apiMessage) {
            $lower = strtolower($apiMessage);
            if (str_contains($lower, 'passphrase') || str_contains($lower, 'password') || str_contains($lower, 'kata sandi')) {
                return 'Passphrase salah.';
            }
            if (str_contains($lower, 'tidak terdaftar') || str_contains($lower, 'not registered')) {
                return 'NIK belum terdaftar di BSrE.';
            }
            if (str_contains($lower, 'expired') || str_contains($lower, 'kedaluwarsa')) {
                return 'Sertifikat sudah expired.';
            }
            return $apiMessage;
        }

        // HTTP status fallback mapping
        return match ($statusCode) {
            400, 401 => 'Passphrase salah.',
            403 => 'Akses TTE ditolak oleh BSrE.',
            404 => 'Sertifikat tidak ditemukan di BSrE.',
            422 => 'Sertifikat/dokumen tidak valid.',
            500, 502, 503, 504 => 'Gagal memproses di server BSrE.',
            default => 'Gagal memproses TTE.',
        };
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
                'message' => 'Semua dokumen telah ditandatangani.',
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
            'message' => "{$successCount} dokumen berhasil ditandatangani.",
            'signed_count' => $successCount,
        ];
    }

    /**
     * Render the original PDF binary content.
     */
    protected function renderDocumentPdf(Meeting $meeting, string $type): string
    {
        $meeting->loadMissing(['creator', 'opd', 'minutes', 'attendances.user', 'photos']);

        // Temporarily set the signed_at property so the template renders the official visual QR code and BSrE footer
        $tempMeeting = clone $meeting;
        $now = now();
        if ($type === 'minutes') {
            $tempMeeting->minutes_signed_at = $tempMeeting->minutes_signed_at ?: $now;
        } elseif ($type === 'attendance') {
            $tempMeeting->attendance_signed_at = $tempMeeting->attendance_signed_at ?: $now;
        } elseif ($type === 'photos' || $type === 'documentation') {
            $tempMeeting->photos_signed_at = $tempMeeting->photos_signed_at ?: $now;
        }

        $view = match ($type) {
            'minutes' => 'exports.meeting-minutes',
            'attendance' => 'exports.meeting-attendance',
            'photos', 'documentation' => 'exports.meeting-documentation',
            default => 'exports.meeting-minutes',
        };

        $pdf = Pdf::loadView($view, [
            'meeting' => $tempMeeting,
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
     * Generate clean, concise reason string for BSrE digital certificate signing.
     */
    public function generateReason(Meeting $meeting, string $type): string
    {
        $prefix = match ($type) {
            'minutes' => 'TTE Notulen',
            'attendance' => 'TTE Presensi',
            'photos', 'documentation' => 'TTE Dokumentasi',
            default => 'TTE Dokumen',
        };

        $cleanTitle = trim(preg_replace('/\s+/', ' ', strip_tags($meeting->title ?? '')));

        if (empty($cleanTitle)) {
            return $prefix;
        }

        $shortTitle = \Illuminate\Support\Str::limit($cleanTitle, 80, '');
        $shortTitle = trim($shortTitle, " -_:");

        return "{$prefix} - {$shortTitle}";
    }

    /**
     * Human readable document label.
     */
    public function getDocumentLabel(string $type): string
    {
        return match ($type) {
            'minutes' => 'Notulen Rapat',
            'attendance' => 'Presensi Rapat',
            'photos', 'documentation' => 'Dokumentasi Rapat',
            default => 'Dokumen Rapat',
        };
    }
}
