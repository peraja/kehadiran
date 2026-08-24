<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    /**
     * Prioritized active models with automatic fallback.
     */
    protected array $models = [
        'gemini-3.6-flash',       // Primary: Cepat, stabil & akurat
        'gemini-3.5-flash',       // Fallback 1: Sangat stabil
        'gemini-3.1-flash-lite',  // Fallback 2: Ringan & hemat kuota
        'gemini-3-flash-preview', // Fallback 3: Preview flash engine
        'gemini-3.7-flash',       // Fallback 4: Next-gen flash engine
    ];

    /**
     * Get the active Gemini API Key from environment configuration.
     */
    public function getApiKey(): string
    {
        return trim((string) config('services.gemini.api_key', ''));
    }

    /**
     * Get the list of fallback models.
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * Get the primary model name.
     */
    public function getModel(): string
    {
        return $this->models[0] ?? 'gemini-3.6-flash';
    }

    /**
     * Check if Gemini service is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->getApiKey());
    }

    /**
     * Test connection to Gemini API with fallback models.
     */
    public function testConnection(?string $apiKey = null): array
    {
        $key = $apiKey ?: $this->getApiKey();

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'API Key Gemini belum diisi.',
            ];
        }

        $lastError = '';

        foreach ($this->models as $modelName) {
            try {
                $url = "{$this->baseUrl}/{$modelName}:generateContent?key={$key}";
                $response = Http::timeout(15)->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => 'Halo, ini uji koneksi API eRapat Pemkab Sinjai. Balas dengan: KONEKSI_BERHASIL']
                            ]
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'model' => $modelName,
                        'message' => "Koneksi ke Google Gemini API berhasil (Model: {$modelName}).",
                    ];
                }

                $errorData = $response->json();
                $lastError = $errorData['error']['message'] ?? ('HTTP ' . $response->status());
                Log::warning("Gemini testConnection model '{$modelName}' failed: {$lastError}. Trying fallback...");
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("Gemini testConnection model '{$modelName}' exception: {$lastError}. Trying fallback...");
            }
        }

        return [
            'success' => false,
            'message' => 'Gagal terhubung ke Gemini API: ' . $lastError,
        ];
    }

    /**
     * Generate meeting minutes from raw text notes.
     */
    public function generateMinutesFromText(Meeting $meeting, string $rawNotes): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'API Key Gemini belum dikonfigurasi.',
            ];
        }

        if (empty(trim($rawNotes))) {
            return [
                'success' => false,
                'message' => 'Catatan rapat wajib diisi.',
            ];
        }

        $context = $this->buildContextPrompt($meeting);
        $userPrompt = "Berikut adalah data konteks rapat dan catatan dari notulis:\n\n"
            . "[KONTEKS RAPAT]\n" . $context . "\n\n"
            . "[CATATAN DARI EDITOR]\n"
            . "\"\"\"\n" . trim($rawNotes) . "\n\"\"\"\n\n"
            . "Instruksi:\n"
            . "1. Susun menjadi notulen kedinasan resmi dalam 3 bagian: 1. Pembukaan, 2. Pembahasan, 3. Kesimpulan.\n"
            . "2. Manfaatkan konteks rapat (pimpinan rapat, agenda, OPD, unsur peserta) pada Pembukaan dan kaitkan dengan pembahasan jika relevan.\n"
            . "3. Rapikan catatan dari editor menjadi poin-poin pembahasan (a., b., c.) dan kesimpulan yang baku dan jelas tanpa mengarang isu yang tidak relevan.";

        return $this->callGeminiApi($context, $userPrompt);
    }

    /**
     * Generate meeting minutes from uploaded audio file and optional notes.
     */
    public function generateMinutesFromAudio(Meeting $meeting, $audioFile, ?string $additionalNotes = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'API Key Gemini belum dikonfigurasi.',
            ];
        }

        try {
            $mimeType = $audioFile->getMimeType() ?: 'audio/mp3';
            $fileContent = file_get_contents($audioFile->getRealPath());
            $base64Audio = base64_encode($fileContent);

            $inlineAudio = [
                'mime_type' => $mimeType,
                'data' => $base64Audio,
            ];

            $context = $this->buildContextPrompt($meeting);
            $userPrompt = "Dengarkan rekaman audio rapat terlampir"
                . (!empty($additionalNotes) ? " beserta catatan pendukung berikut:\n\"\"\"" . trim($additionalNotes) . "\"\"\"" : "")
                . ".\n\nSusun notulen kedinasan resmi (1. Pembukaan, 2. Pembahasan, 3. Kesimpulan) dengan memanfaatkan data konteks rapat secara proporsional.";

            return $this->callGeminiApi($context, $userPrompt, $inlineAudio);
        } catch (\Exception $e) {
            Log::error('Gemini generateMinutesFromAudio error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal memproses audio.',
            ];
        }
    }

    /**
     * Call Gemini API with automatic model fallback iteration.
     */
    protected function callGeminiApi(string $context, string $userPrompt, ?array $inlineAudio = null): array
    {
        $apiKey = $this->getApiKey();
        $systemInstruction = $this->buildSystemInstruction();

        $parts = [];
        if ($inlineAudio) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $inlineAudio['mime_type'],
                    'data' => $inlineAudio['data'],
                ]
            ];
        }

        $fullPrompt = (!empty($context) ? "[Konteks Rapat: {$context}]\n\n" : "") . $userPrompt;
        $parts[] = ['text' => $fullPrompt];

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'contents' => [
                [
                    'parts' => $parts
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 4096,
            ],
        ];

        $lastError = '';

        foreach ($this->models as $modelName) {
            try {
                $url = "{$this->baseUrl}/{$modelName}:generateContent?key={$apiKey}";
                $response = Http::timeout(60)->post($url, $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $candidates = $data['candidates'] ?? [];
                    $candidateParts = $candidates[0]['content']['parts'] ?? [];

                    $generatedText = '';
                    foreach ($candidateParts as $part) {
                        if (isset($part['text'])) {
                            $generatedText .= $part['text'];
                        }
                    }

                    if (!empty(trim($generatedText))) {
                        $cleanText = $this->cleanMarkdownOutput($generatedText);

                        return [
                            'success' => true,
                            'model' => $modelName,
                            'content' => $cleanText,
                            'message' => 'Notulen resmi berhasil disusun oleh AI.',
                        ];
                    }
                }

                $errorData = $response->json();
                $lastError = $errorData['error']['message'] ?? ('HTTP ' . $response->status());
                Log::warning("Gemini model '{$modelName}' failed: {$lastError}. Attempting next fallback model...");
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("Gemini model '{$modelName}' exception: {$lastError}. Attempting next fallback model...");
            }
        }

        $userFriendlyMessage = 'Gagal menyusun notulen AI. Silakan coba lagi.';
        if (str_contains($lastError, 'quota') || str_contains($lastError, 'RESOURCE_EXHAUSTED')) {
            $userFriendlyMessage = 'Kuota API Gemini telah habis. Silakan coba beberapa saat lagi.';
        } elseif (str_contains($lastError, 'API key') || str_contains($lastError, 'INVALID_ARGUMENT')) {
            $userFriendlyMessage = 'API Key Gemini tidak valid.';
        }

        return [
            'success' => false,
            'message' => $userFriendlyMessage,
        ];
    }

    /**
     * Build the system instruction for clean and concise minutes format.
     */
    protected function buildSystemInstruction(): string
    {
        return <<<INSTRUCTION
Anda adalah Asisten Notulis Resmi Pemerintah Kabupaten Sinjai. Tugas Anda adalah merapikan catatan mentah rapat dari pengguna ke dalam format notulen kedinasan resmi (1. Pembukaan, 2. Pembahasan, 3. Kesimpulan) yang baku, lugas, dan terstruktur.

PANDUAN PEMANFAATAN KONTEKS & CATATAN:
1. Bagian 1 (Pembukaan):
   - Manfaatkan data konteks rapat (Pimpinan rapat, agenda/topik rapat, instansi penyelenggara/OPD, dan unsur instansi peserta yang hadir) untuk menyusun kalimat pengantar pembukaan jalannya rapat kedinasan secara wajar dan formal.
2. Bagian 2 (Pembahasan):
   - Ambil materi pokok, bahasan teknis, saran, dan masukan dari CATATAN PENGGUNA.
   - Rapikan ejaan dan tata bahasa kedinasan, lalu susun dalam sub-poin huruf kecil (a., b., c., dst).
   - Hubungkan secara proporsional dengan konteks instansi/peserta terkait yang relevan.
3. Bagian 3 (Kesimpulan):
   - Rumuskan kesepakatan, arahan pimpinan, atau rencana tindak lanjut dari hasil catatan rapat ke dalam sub-poin huruf kecil (a., b., dst).

ATURAN FORMAT:
- JANGAN gunakan tanda hubung/strip (-) atau bintang (*); gunakan selalu sub-poin huruf kecil (a., b., c., dst).
- JANGAN menyertakan informasi header (judul, hari/tanggal, waktu, tempat, tabel presensi).
- JANGAN menyertakan kolom tanda tangan atau nama pejabat penandatangan di akhir teks.
- Tulis langsung isi teks tanpa membungkus dengan blok kode markdown.
INSTRUCTION;
    }

    /**
     * Build contextual information from the meeting.
     */
    protected function buildContextPrompt(Meeting $meeting): string
    {
        $opdName = $meeting->opd?->name ?? ($meeting->creator?->unit_name ?? 'Pemerintah Kabupaten Sinjai');
        $dateFormatted = $meeting->start_time ? $meeting->start_time->translatedFormat('l, d F Y') : ($meeting->date ? $meeting->date->translatedFormat('l, d F Y') : '-');
        $timeFormatted = $meeting->start_time && $meeting->end_time
            ? $meeting->start_time->format('H:i') . ' - ' . $meeting->end_time->format('H:i') . ' WITA'
            : ($meeting->start_time ? $meeting->start_time->format('H:i') . ' WITA' : '-');

        $signerName = $meeting->signer_name ?? ($meeting->signer?->name ?? '-');
        $signerTitle = $meeting->signer_title ?? ($meeting->signer?->title ?? 'Pimpinan');

        // Unsur/Instansi peserta yang hadir (tanpa nama individu)
        $attendingUnits = $meeting->attendances()
            ->with('user')
            ->get()
            ->map(function ($att) {
                return $att->user ? ($att->user->unit_name ?: null) : ($att->guest_agency ?: null);
            })
            ->filter()
            ->unique()
            ->values();

        $totalAttendees = $meeting->attendances()->count();
        $attendeesSummary = $attendingUnits->isNotEmpty()
            ? $attendingUnits->implode(', ') . ($totalAttendees > 0 ? " ({$totalAttendees} orang hadir)" : '')
            : ($totalAttendees > 0 ? "{$totalAttendees} orang hadir" : 'Unsur Perangkat Daerah terkait');

        return <<<CONTEXT
DATA & KONTEKS RAPAT:
- Judul / Topik Rapat : {$meeting->title}
- Agenda Pembahasan  : {$meeting->agenda}
- Penyelenggara / OPD : {$opdName}
- Waktu Pelaksanaan   : {$dateFormatted}, Pukul {$timeFormatted}
- Tempat / Lokasi     : {$meeting->location}
- Pimpinan / Pengesah : {$signerName} ({$signerTitle})
- Unsur / Instansi Peserta : {$attendeesSummary}
CONTEXT;
    }

    /**
     * Remove wrapping markdown code fences if any.
     */
    protected function cleanMarkdownOutput(string $text): string
    {
        $text = trim($text);

        // Remove markdown code fences
        if (preg_match('/^```(?:markdown|text)?\s*\n([\s\S]*?)\n```$/i', $text, $matches)) {
            $text = trim($matches[1]);
        }

        // Remove markdown bold / heading markers from section headers
        $text = preg_replace('/^\*\*(1\.\s*Pembukaan|2\.\s*Pembahasan|3\.\s*Kesimpulan)\*\*/mi', '$1', $text);
        $text = preg_replace('/^#+\s*(1\.\s*Pembukaan|2\.\s*Pembahasan|3\.\s*Kesimpulan)/mi', '$1', $text);

        // Normalize trailing spaces per line
        $text = preg_replace('/[ \t]+$/m', '', $text);

        // Collapse 3 or more consecutive newlines to exactly 2 newlines
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }
}
