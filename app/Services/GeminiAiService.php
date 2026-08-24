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
                        'message' => "Koneksi ke Google Gemini API berhasil terhubung (Model: {$modelName})!",
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
            'message' => 'Gagal terhubung ke seluruh fallback model Gemini API: ' . $lastError,
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
                'message' => 'Gemini API Key belum dikonfigurasi. Hubungi Super Admin.',
            ];
        }

        if (empty(trim($rawNotes))) {
            return [
                'success' => false,
                'message' => 'Catatan mentah rapat wajib diisi.',
            ];
        }

        $context = $this->buildContextPrompt($meeting);
        $userPrompt = "Berikut adalah catatan mentah rapat yang ditulis oleh notulis:\n\n"
            . "\"\"\"\n" . trim($rawNotes) . "\n\"\"\"\n\n"
            . "Tolong rapikan menjadi isi notulen kedinasan resmi (1. Pembukaan, 2. Pembahasan, 3. Kesimpulan) dengan sub-poin huruf kecil (a., b., c.). Jangan gunakan tanda hubung (-) dan jangan sertakan header rapat atau tanda tangan penandatangan.";

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
                'message' => 'Gemini API Key belum dikonfigurasi. Hubungi Super Admin.',
            ];
        }

        if (!$audioFile) {
            return [
                'success' => false,
                'message' => 'Berkas rekaman audio rapat wajib diunggah.',
            ];
        }

        try {
            $mimeType = $audioFile->getMimeType() ?: 'audio/mp3';
            $audioData = base64_encode(file_get_contents($audioFile->getRealPath()));

            $context = $this->buildContextPrompt($meeting);
            $userPrompt = "Berikut terlampir rekaman audio jalannya rapat.\n";
            if (!empty(trim((string)$additionalNotes))) {
                $userPrompt .= "Catatan/petunjuk tambahan dari notulis: " . trim($additionalNotes) . "\n\n";
            }
            $userPrompt .= "Tolong rapikan menjadi isi notulen (1. Pembukaan, 2. Pembahasan, 3. Kesimpulan) dalam format poin-poin tanda hubung (-). Jangan sertakan informasi header rapat atau tanda tangan penandatangan.";

            $inlineAudio = [
                'mime_type' => $mimeType,
                'data' => $audioData,
            ];

            return $this->callGeminiApi($context, $userPrompt, $inlineAudio);
        } catch (\Exception $e) {
            Log::error('Gemini generateMinutesFromAudio error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal memproses berkas audio: ' . $e->getMessage(),
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

        $fullPrompt = (!empty($context) ? "[Konteks: {$context}]\n\n" : "") . $userPrompt;
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

        return [
            'success' => false,
            'message' => 'Gagal memproses AI pada seluruh fallback model: ' . $lastError,
        ];
    }

    /**
     * Build the system instruction for clean and concise minutes format.
     */
    protected function buildSystemInstruction(): string
    {
        return <<<INSTRUCTION
Anda adalah Asisten Notulis Pemerintah Kabupaten Sinjai. Tugas Anda adalah merapikan catatan mentah rapat menjadi isi notulen kedinasan yang baku, terstruktur, dan sesuai dengan Tata Naskah Dinas Pemerintahan.

PANDUAN KONTEKS RAPAT:
- Manfaatkan data agenda, pimpinan rapat, dan unsur instansi peserta rapat untuk memperkaya konteks notulen secara wajar dan proporsional.

PEDOMAN PENYUSUNAN NOTULEN DINAS:
Susun HANYA dalam 3 bagian utama berikut dengan penomoran hierarki resmi (gunakan sub-poin huruf kecil a., b., c. — JANGAN gunakan tanda hubung/strip '-' atau bullet '*'):

1. Pembukaan
Uraian pengantar pembukaan jalannya rapat oleh Pimpinan/Penyelenggara serta maksud dan tujuan diselenggarakannya rapat dalam kalimat kedinasan yang jelas dan lugas.

2. Pembahasan
a. Poin pokok materi atau isu utama yang dibahas.
b. Tanggapan, saran, atau masukan dari peserta rapat / instansi terkait.
c. Pokok bahasan teknis lainnya yang relevan.

3. Kesimpulan
a. Keputusan dan kesepakatan bersama yang dihasilkan dalam rapat.
b. Arahan atau rencana tindak lanjut yang disepakati.

ATURAN KETAT:
- JANGAN gunakan tanda hubung/strip (-) atau tanda bintang (*) untuk poin-poin; gunakan selalu huruf kecil berurutan (a., b., c., dst).
- JANGAN menyertakan informasi header rapat (seperti judul rapat, agenda, hari/tanggal, waktu, tempat, atau tabel daftar hadir).
- JANGAN menyertakan nama penandatangan, NIK, atau kolom tanda tangan di akhir teks.
- Tulis langsung teks isi tanpa membungkus dengan blok kode ```markdown atau ```.
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
