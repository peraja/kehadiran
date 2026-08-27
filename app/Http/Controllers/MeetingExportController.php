<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Meeting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class MeetingExportController extends Controller
{
    /**
     * Generate a standardized, clean filename for exports.
     * Example: Notulen - Judul Agenda - 2026-08-24.pdf
     */
    protected function generateFileName(Meeting $meeting, string $prefix, string $extension = 'pdf'): string
    {
        $date = $meeting->date ? $meeting->date->format('Y-m-d') : date('Y-m-d');
        // Clean title: remove illegal filename characters, condense whitespace, and limit length
        $cleanTitle = trim(preg_replace('/[\/\\\\:*?"<>|]+/', ' ', $meeting->title));
        $cleanTitle = preg_replace('/\s+/', ' ', $cleanTitle);
        $cleanTitle = Str::limit($cleanTitle, 60, '');
        $cleanTitle = trim($cleanTitle, " -_");

        return "{$prefix} - {$cleanTitle} - {$date}.{$extension}";
    }

    protected function authorizeExport(Meeting $meeting): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        if ($user->hasActiveRole('admin')) {
            return;
        }

        if ($user->hasActiveRole('admin_opd')) {
            if ($user->unit_name === $meeting->creator?->unit_name || $user->unit_name === $meeting->opd?->name) {
                return;
            }
        }

        if ($user->hasActiveRole('pimpinan')) {
            if ($meeting->isSigner($user)) {
                return;
            }
        }

        if ($user->hasActiveRole('pegawai')) {
            if ($meeting->created_by === $user->id) {
                return;
            }
        }

        abort(403, 'Anda tidak memiliki hak untuk mengunduh dokumen rapat ini.');
    }

    /**
     * Serve document either from authentic signed PDF storage or generated preview.
     */
    protected function serveDocument(Meeting $meeting, string $prefix, ?string $signedPath, string $viewName, bool $forceDownload = false)
    {
        $fileName = $this->generateFileName($meeting, $prefix);
        $isDownload = $forceDownload || request()->query('action') === 'download' || request()->query('download');

        // If document is already electronically signed and stored on disk, directly download the authentic signed PDF
        if ($signedPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($signedPath)) {
            $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path($signedPath);

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // Otherwise generate on-the-fly preview
        $pdf = Pdf::loadView($viewName, compact('meeting'))->setPaper('a4', 'portrait');

        if ($isDownload) {
            return $pdf->download($fileName);
        }

        return $pdf->stream($fileName);
    }

    public function exportMinutes(Meeting $meeting)
    {
        $this->authorizeExport($meeting);

        // Dokumen hanya dapat diekspor jika rapat telah diselesaikan
        if ($meeting->status !== 'completed') {
            abort(403, 'Dokumen notulen hanya dapat diekspor setelah status rapat diselesaikan.');
        }

        return $this->serveDocument($meeting, 'Notulen', $meeting->minutes_signed_path, 'exports.meeting-minutes');
    }

    public function exportAttendance(Meeting $meeting)
    {
        $this->authorizeExport($meeting);

        // Dokumen hanya dapat diekspor jika rapat telah diselesaikan
        if ($meeting->status !== 'completed') {
            abort(403, 'Presensi hanya dapat diekspor setelah status rapat diselesaikan.');
        }

        return $this->serveDocument($meeting, 'Presensi', $meeting->attendance_signed_path, 'exports.meeting-attendance');
    }

    public function exportPhotos(Meeting $meeting)
    {
        $this->authorizeExport($meeting);

        // Dokumentasi hanya dapat diekspor jika rapat telah diselesaikan
        if ($meeting->status !== 'completed') {
            abort(403, 'Dokumentasi hanya dapat diekspor setelah status rapat diselesaikan.');
        }

        return $this->serveDocument($meeting, 'Dokumentasi', $meeting->photos_signed_path, 'exports.meeting-documentation');
    }

    /**
     * Export all meeting documents bundled into a single ZIP archive.
     */
    public function exportBundle(Meeting $meeting)
    {
        $this->authorizeExport($meeting);

        // Dokumen ZIP hanya dapat diekspor jika rapat telah diselesaikan dan semua dokumen telah di-TTE
        if ($meeting->status !== 'completed' || !$meeting->isFullySigned()) {
            abort(403, 'Berkas ZIP lengkap hanya dapat diunduh jika semua dokumen (Notulen, Presensi, dan Dokumentasi) telah ditandatangani secara elektronik (TTE).');
        }

        $zipFileName = $this->generateFileName($meeting, 'Dokumen', 'zip');
        $tempZipPath = tempnam(sys_get_temp_dir(), 'erapat_zip_');

        $zip = new \ZipArchive();
        if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Gagal membuat arsip berkas ZIP.');
        }

        // 1. Notulen PDF
        $minutesFileName = $this->generateFileName($meeting, 'Notulen');
        if ($meeting->minutes_signed_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($meeting->minutes_signed_path)) {
            $zip->addFile(\Illuminate\Support\Facades\Storage::disk('public')->path($meeting->minutes_signed_path), $minutesFileName);
        } elseif ($meeting->hasDocumentContent('minutes')) {
            $pdfContent = Pdf::loadView('exports.meeting-minutes', compact('meeting'))->setPaper('a4', 'portrait')->output();
            $zip->addFromString($minutesFileName, $pdfContent);
        }

        // 2. Presensi PDF
        $attendanceFileName = $this->generateFileName($meeting, 'Presensi');
        if ($meeting->attendance_signed_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($meeting->attendance_signed_path)) {
            $zip->addFile(\Illuminate\Support\Facades\Storage::disk('public')->path($meeting->attendance_signed_path), $attendanceFileName);
        } elseif ($meeting->hasDocumentContent('attendance')) {
            $pdfContent = Pdf::loadView('exports.meeting-attendance', compact('meeting'))->setPaper('a4', 'portrait')->output();
            $zip->addFromString($attendanceFileName, $pdfContent);
        }

        // 3. Dokumentasi PDF
        $photosFileName = $this->generateFileName($meeting, 'Dokumentasi');
        if ($meeting->photos_signed_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($meeting->photos_signed_path)) {
            $zip->addFile(\Illuminate\Support\Facades\Storage::disk('public')->path($meeting->photos_signed_path), $photosFileName);
        } elseif ($meeting->hasDocumentContent('photos')) {
            $pdfContent = Pdf::loadView('exports.meeting-documentation', compact('meeting'))->setPaper('a4', 'portrait')->output();
            $zip->addFromString($photosFileName, $pdfContent);
        }

        $zip->close();

        return response()->download($tempZipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Public download for verified electronically signed documents.
     */
    public function downloadSigned(Meeting $meeting, string $type)
    {
        $type = strtolower($type);

        switch ($type) {
            case 'presensi':
            case 'attendance':
                if (!$meeting->attendance_signed_at) {
                    abort(404, 'Dokumen presensi belum ditandatangani secara elektronik.');
                }
                return $this->serveDocument($meeting, 'Presensi', $meeting->attendance_signed_path, 'exports.meeting-attendance', true);

            case 'dokumentasi':
            case 'photos':
                if (!$meeting->photos_signed_at) {
                    abort(404, 'Dokumen dokumentasi belum ditandatangani secara elektronik.');
                }
                return $this->serveDocument($meeting, 'Dokumentasi', $meeting->photos_signed_path, 'exports.meeting-documentation', true);

            case 'notulen':
            case 'minutes':
            default:
                if (!$meeting->minutes_signed_at) {
                    abort(404, 'Dokumen notulen rapat belum ditandatangani secara elektronik.');
                }
                return $this->serveDocument($meeting, 'Notulen', $meeting->minutes_signed_path, 'exports.meeting-minutes', true);
        }
    }
}

