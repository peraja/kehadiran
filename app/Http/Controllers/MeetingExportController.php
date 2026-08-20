<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Meeting;
use Barryvdh\DomPDF\Facade\Pdf;

class MeetingExportController extends Controller
{
    public function exportMinutes(Meeting $meeting)
    {
        // Pastikan pengguna berhak mengakses
        if (!auth()->user()->hasRole('admin') && $meeting->created_by !== auth()->id() && auth()->user()->unit_name !== $meeting->creator->unit_name) {
            abort(403);
        }

        $pdf = Pdf::loadView('exports.meeting-minutes', compact('meeting'))->setPaper('a4', 'landscape');
        return $pdf->download('Notulen_Rapat_' . \Str::slug($meeting->title) . '.pdf');
    }

    public function exportAttendance(Meeting $meeting)
    {
        // Pastikan pengguna berhak mengakses
        if (!auth()->user()->hasRole('admin') && $meeting->created_by !== auth()->id() && auth()->user()->unit_name !== $meeting->creator->unit_name) {
            abort(403);
        }

        $pdf = Pdf::loadView('exports.meeting-attendance', compact('meeting'))->setPaper('a4', 'landscape');
        return $pdf->download('Daftar_Hadir_' . \Str::slug($meeting->title) . '.pdf');
    }

    public function exportPhotos(Meeting $meeting)
    {
        // Pastikan pengguna berhak mengakses
        if (!auth()->user()->hasRole('admin') && $meeting->created_by !== auth()->id() && auth()->user()->unit_name !== $meeting->creator->unit_name) {
            abort(403);
        }

        if ($meeting->photos->isEmpty()) {
            return back()->with('message', 'Tidak ada foto untuk diunduh.');
        }

        $zip = new \ZipArchive;
        $fileName = 'Dokumentasi_' . \Str::slug($meeting->title) . '_' . date('Ymd_His') . '.zip';
        $filePath = storage_path('app/private/' . $fileName);

        if ($zip->open($filePath, \ZipArchive::CREATE) === TRUE) {
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
