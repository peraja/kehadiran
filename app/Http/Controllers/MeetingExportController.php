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

        // If document is already electronically signed and stored on disk, serve the real signed file
        if ($signedPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($signedPath)) {
            $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path($signedPath);
            $disposition = $isDownload ? 'attachment' : 'inline';

            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$fileName}\"",
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

