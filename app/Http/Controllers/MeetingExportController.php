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

        if ($meeting->photos->isEmpty()) {
            return back()->with('message', 'Tidak ada foto untuk diunduh.');
        }

        $zip = new \ZipArchive;
        $fileName = $this->generateFileName($meeting, 'Dokumentasi_Foto', 'zip');
        $filePath = storage_path('app/private/' . $fileName);

        if (!file_exists(storage_path('app/private'))) {
            mkdir(storage_path('app/private'), 0755, true);
        }

        if ($zip->open($filePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $counter = 1;
            foreach ($meeting->photos as $photo) {
                $absPath = storage_path('app/public/' . $photo->file);
                if (file_exists($absPath)) {
                    $ext = pathinfo($absPath, PATHINFO_EXTENSION);
                    $zip->addFile($absPath, 'Foto_' . str_pad($counter, 3, '0', STR_PAD_LEFT) . '.' . $ext);
                    $counter++;
                }
            }
            $zip->close();
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}

