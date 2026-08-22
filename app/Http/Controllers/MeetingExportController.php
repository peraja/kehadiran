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
     */
    protected function generateFileName(Meeting $meeting, string $prefix, string $extension = 'pdf'): string
    {
        $date = $meeting->date ? $meeting->date->format('Y-m-d') : date('Y-m-d');
        $slug = Str::limit(Str::slug($meeting->title, '_'), 60, '');

        return "{$prefix}_{$date}_{$slug}.{$extension}";
    }

    public function exportMinutes(Meeting $meeting)
    {
        // Pastikan pengguna berhak mengakses
        if (!auth()->user()->hasRole('admin') && $meeting->created_by !== auth()->id() && auth()->user()->unit_name !== $meeting->creator?->unit_name) {
            abort(403);
        }

        // Dokumen hanya dapat diekspor jika rapat telah diselesaikan
        if ($meeting->status !== 'completed') {
            abort(403, 'Dokumen notulen hanya dapat diekspor setelah status rapat diselesaikan.');
        }

        $pdf = Pdf::loadView('exports.meeting-minutes', compact('meeting'))->setPaper('a4', 'portrait');
        $fileName = $this->generateFileName($meeting, 'Notulen_Rapat');

        return $pdf->stream($fileName);
    }

    public function exportAttendance(Meeting $meeting)
    {
        // Pastikan pengguna berhak mengakses
        if (!auth()->user()->hasRole('admin') && $meeting->created_by !== auth()->id() && auth()->user()->unit_name !== $meeting->creator?->unit_name) {
            abort(403);
        }

        // Dokumen hanya dapat diekspor jika rapat telah diselesaikan
        if ($meeting->status !== 'completed') {
            abort(403, 'Daftar hadir hanya dapat diekspor setelah status rapat diselesaikan.');
        }

        $pdf = Pdf::loadView('exports.meeting-attendance', compact('meeting'))->setPaper('a4', 'portrait');
        $fileName = $this->generateFileName($meeting, 'Daftar_Hadir');

        return $pdf->stream($fileName);
    }

    public function exportPhotos(Meeting $meeting)
    {
        // Pastikan pengguna berhak mengakses
        if (!auth()->user()->hasRole('admin') && $meeting->created_by !== auth()->id() && auth()->user()->unit_name !== $meeting->creator?->unit_name) {
            abort(403);
        }

        // Dokumentasi foto hanya dapat diekspor jika rapat telah diselesaikan
        if ($meeting->status !== 'completed') {
            abort(403, 'Dokumentasi foto hanya dapat diekspor setelah status rapat diselesaikan.');
        }

        $pdf = Pdf::loadView('exports.meeting-documentation', compact('meeting'))->setPaper('a4', 'portrait');
        $fileName = $this->generateFileName($meeting, 'Dokumentasi_Foto');

        return $pdf->stream($fileName);
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
                    abort(404, 'Dokumen daftar hadir belum ditandatangani secara elektronik.');
                }
                $pdf = Pdf::loadView('exports.meeting-attendance', compact('meeting'))->setPaper('a4', 'portrait');
                $fileName = $this->generateFileName($meeting, 'Daftar_Hadir_TTE');
                return $pdf->stream($fileName);

            case 'dokumentasi':
            case 'photos':
                if (!$meeting->photos_signed_at) {
                    abort(404, 'Dokumen dokumentasi foto belum ditandatangani secara elektronik.');
                }
                $pdf = Pdf::loadView('exports.meeting-documentation', compact('meeting'))->setPaper('a4', 'portrait');
                $fileName = $this->generateFileName($meeting, 'Dokumentasi_Foto_TTE');
                return $pdf->stream($fileName);

            case 'notulen':
            case 'minutes':
            default:
                if (!$meeting->minutes_signed_at) {
                    abort(404, 'Dokumen notulen rapat belum ditandatangani secara elektronik.');
                }
                $pdf = Pdf::loadView('exports.meeting-minutes', compact('meeting'))->setPaper('a4', 'portrait');
                $fileName = $this->generateFileName($meeting, 'Notulen_Rapat_TTE');
                return $pdf->stream($fileName);
        }
    }
}

