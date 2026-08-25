<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Models\Opd;
use App\Models\OpdSigner;
use App\Services\BsreEsignService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Livewire\Attributes\On;

new class extends Component {
    public Meeting $meeting;
    public $title, $date, $start_time, $end_time, $location;
    public $start_hour = '09', $start_minute = '00';
    public $end_hour = '11', $end_minute = '00';
    public $signer_title, $signer_name, $signer_nip, $signer_rank;
    public $selected_signer_id = 'kepala_opd';
    public $selected_opd_id = '';

    public bool $showSignModal = false;
    public string $passphrase = '';
    public string $errorMessage = '';

    #[On('meeting-updated')]
    public function refreshMeeting(): void
    {
        $this->meeting->refresh();
    }

    public function getOpdProperty(): ?Opd
    {
        if ($this->selected_opd_id) {
            return Opd::find($this->selected_opd_id);
        }

        $userUnit = $this->meeting->creator?->unit_name ?? auth()->user()->unit_name;
        if ($userUnit) {
            $opd = Opd::where('name', $userUnit)->first();
            if (!$opd) {
                $cleanUnit = str_replace([',', '.', '-'], '', $userUnit);
                $opd = Opd::whereRaw("REPLACE(REPLACE(REPLACE(name, ',', ''), '.', ''), '-', '') LIKE ?", ['%' . $cleanUnit . '%'])->first();
            }
            return $opd;
        }

        return null;
    }

    public function getOpdSignersProperty(): \Illuminate\Support\Collection
    {
        $opd = $this->opd;
        if (!$opd) {
            return collect();
        }

        $signers = $opd->signers()
            ->where('is_active', true)
            ->orderByRaw("CASE eselon WHEN 'II.a' THEN 1 WHEN 'II.b' THEN 2 WHEN 'III.a' THEN 3 WHEN 'III.b' THEN 4 ELSE 5 END, id ASC")
            ->get()
            ->map(fn($s) => (object)[
                'id'          => (string) $s->id,
                'name'        => $s->name,
                'nip'         => $s->nip,
                'title'       => $s->title,
                'bidang_name' => $s->bidang_name,
                'eselon'      => $s->eselon,
                'rank'        => $s->rank,
                'is_manual'   => false,
            ]);

        $signerNips = $signers->pluck('nip')->filter()->values();
        $leaderNip  = $opd->leader_nip;

        $manualPimpinan = \App\Models\User::role('pimpinan')
            ->where(function ($q) use ($opd) {
                $q->where('unit_name', $opd->name);
                if (auth()->user()->unit_name) {
                    $q->orWhere('unit_name', auth()->user()->unit_name);
                }
            })
            ->get()
            ->filter(fn($u) => ($leaderNip ? $u->nip !== $leaderNip : true) && (empty($u->nip) || !$signerNips->contains($u->nip)))
            ->map(fn($u) => (object)[
                'id'          => 'pimpinan_' . $u->id,
                'name'        => $u->name,
                'nip'         => $u->nip,
                'title'       => $u->jabatan ?: 'Pimpinan',
                'bidang_name' => null,
                'eselon'      => null,
                'rank'        => $u->pangkat,
                'is_manual'   => true,
            ]);

        return $signers->concat($manualPimpinan);
    }

    /**
     * True jika ada dokumen yang sudah di-TTE — perubahan penandatangan diblokir.
     */
    public function getSignerLockedProperty(): bool
    {
        return !empty($this->meeting->minutes_signed_at)
            || !empty($this->meeting->attendance_signed_at)
            || !empty($this->meeting->photos_signed_at);
    }

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;

        $user = auth()->user();
        if ($user->hasActiveRole('pimpinan') && !$meeting->isSigner($user)) {
            abort(403, 'Anda hanya dapat mengakses rapat yang penandatangannya adalah Anda sendiri.');
        }

        if ($user->hasActiveRole('pegawai') && $meeting->created_by !== $user->id) {
            abort(403, 'Anda hanya dapat mengakses rapat yang Anda buat sendiri.');
        }

        $this->loadMeetingData();
    }

    public function openSignModal()
    {
        $this->passphrase = '';
        $this->errorMessage = '';
        $this->showSignModal = true;
        $this->dispatch('open-modal', 'sign-all-modal');
    }

    public function closeSignModal()
    {
        $this->showSignModal = false;
        $this->passphrase = '';
        $this->errorMessage = '';
        $this->dispatch('close-modal', 'sign-all-modal');
    }

    public function executeSign(BsreEsignService $esignService)
    {
        if (!auth()->user()->hasActiveRole('pimpinan') || !$this->meeting->isSigner(auth()->user())) {
            abort(403, 'Anda bukan pejabat penandatangan yang ditunjuk untuk rapat ini.');
        }

        $this->errorMessage = '';
        $this->validate([
            'passphrase' => 'required|string',
        ], [
            'passphrase.required' => 'Passphrase BSrE wajib diisi.',
        ]);

        $result = $esignService->signAllDocuments($this->meeting, auth()->user(), $this->passphrase);

        if ($result['success']) {
            $this->meeting->refresh();
            $this->closeSignModal();
            session()->flash('message', $result['message']);
            $this->redirect(request()->header('Referer') ?: route('meetings.overview', $this->meeting->id), navigate: true);
        } else {
            $this->errorMessage = $result['message'];
            $this->passphrase = '';
        }
    }

    public function loadMeetingData()
    {
        $this->title = $this->meeting->title;
        $this->date = $this->meeting->date ? $this->meeting->date->format('Y-m-d') : '';
        
        $startTime = $this->meeting->start_time ? $this->meeting->start_time->format('H:i') : '09:00';
        $endTime = $this->meeting->end_time ? $this->meeting->end_time->format('H:i') : '11:00';
        $this->start_time = $startTime;
        $this->end_time = $endTime;

        $startParts = explode(':', $startTime);
        $this->start_hour = $startParts[0] ?? '09';
        $this->start_minute = $startParts[1] ?? '00';

        $endParts = explode(':', $endTime);
        $this->end_hour = $endParts[0] ?? '11';
        $this->end_minute = $endParts[1] ?? '00';

        $this->location = $this->meeting->location;
        $this->signer_title = $this->meeting->signer_title ?? '';
        $this->signer_name = $this->meeting->signer_name ?? '';
        $this->signer_nip = $this->meeting->signer_nip ?? '';
        $this->signer_rank = $this->meeting->signer_rank ?? '';
        $this->selected_opd_id = (string)($this->meeting->opd_id ?? '');

        $opd = $this->opd;
        $isKepalaOpd = false;
        if ($opd) {
            $kepalaTitle = $opd->leader_title ?: ('Kepala ' . $opd->name);
            if ($this->meeting->signer_title === $kepalaTitle && $this->meeting->signer_name === $opd->leader_name) {
                $isKepalaOpd = true;
            }
        }

        if ($isKepalaOpd) {
            $this->selected_signer_id = 'kepala_opd';
        } else {
            // 1. Prioritas utama: Cocokkan Jabatan (title) DAN Nama secara presisi
            $matchedSigner = $this->opdSigners->first(function ($s) {
                return $s->title === $this->meeting->signer_title && $s->name === $this->meeting->signer_name;
            });

            // 2. Jika tidak ditemukan, cocokkan berdasarkan Jabatan (title)
            if (!$matchedSigner && $this->meeting->signer_title) {
                $matchedSigner = $this->opdSigners->first(function ($s) {
                    return $s->title === $this->meeting->signer_title;
                });
            }

            // 3. Fallback: Cocokkan berdasarkan Nama
            if (!$matchedSigner && $this->meeting->signer_name) {
                $matchedSigner = $this->opdSigners->first(function ($s) {
                    return $s->name === $this->meeting->signer_name;
                });
            }

            $this->selected_signer_id = $matchedSigner ? (string) $matchedSigner->id : 'kepala_opd';
        }

        if (empty($this->signer_rank)) {
            if (isset($matchedSigner) && $matchedSigner && !empty($matchedSigner->rank)) {
                $this->signer_rank = $matchedSigner->rank;
            } elseif ($this->meeting->signer_nip) {
                $this->signer_rank = \App\Models\User::where('nip', $this->meeting->signer_nip)->value('pangkat') ?? '';
            }
        }
    }

    public function updatedSelectedOpdId($val)
    {
        $this->selected_signer_id = 'kepala_opd';
        $opd = $this->opd;

        if ($opd) {
            $this->signer_title = $opd->leader_title ?: ('Kepala ' . $opd->name);
            $this->signer_name  = $opd->leader_name;
            $this->signer_nip   = $opd->leader_nip;
            $this->signer_rank  = $opd->leader_rank ?: $opd->leader_eselon;
        } else {
            $this->signer_title = '';
            $this->signer_name  = '';
            $this->signer_nip   = '';
            $this->signer_rank  = '';
        }
    }

    public function updatedSelectedSignerId($val)
    {
        if ($val === 'kepala_opd' || empty($val)) {
            if ($this->opd) {
                $this->signer_title = $this->opd->leader_title ?: ('Kepala ' . $this->opd->name);
                $this->signer_name = $this->opd->leader_name;
                $this->signer_nip = $this->opd->leader_nip;
                $this->signer_rank = $this->opd->leader_rank ?: $this->opd->leader_eselon;
            } else {
                $this->signer_title = '';
                $this->signer_name = '';
                $this->signer_nip = '';
                $this->signer_rank = '';
            }
        } else {
            if (str_starts_with($val, 'pimpinan_')) {
                $userId = (int) str_replace('pimpinan_', '', $val);
                $pimpinanUser = \App\Models\User::find($userId);
                if ($pimpinanUser) {
                    $this->signer_title = $pimpinanUser->jabatan ?: 'Pimpinan';
                    $this->signer_name  = $pimpinanUser->name;
                    $this->signer_nip   = $pimpinanUser->nip ?? '';
                    $this->signer_rank  = $pimpinanUser->pangkat ?? '';
                }
            } else {
                $signer = OpdSigner::find($val);
                if ($signer) {
                    $this->signer_title = $signer->title;
                    $this->signer_name = $signer->name;
                    $this->signer_nip = $signer->nip ?? '';
                    $this->signer_rank = $signer->rank ?? $signer->eselon ?? '';
                }
            }
        }
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'required|string|max:255',
            'signer_title' => 'nullable|string|max:255',
            'signer_name' => 'nullable|string|max:255',
            'signer_nip' => 'nullable|string|max:50',
            'signer_rank' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Agenda wajib diisi.',
            'date.required' => 'Tanggal wajib diisi.',
            'start_time.required' => 'Waktu mulai wajib diisi.',
            'end_time.required' => 'Waktu selesai wajib diisi.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'location.required' => 'Lokasi wajib diisi.',
        ];
    }

    public function canManageMeeting(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->hasActiveRole('admin')) return true;
        if ($user->hasActiveRole('admin_opd')) {
            return $user->unit_name === $this->meeting->opd?->name || $user->unit_name === $this->meeting->creator?->unit_name;
        }
        // Pegawai biasa: hanya bisa mengelola rapat yang dibuat sendiri
        return $this->meeting->created_by === $user->id;
    }

    public function startMeeting()
    {
        if (!$this->canManageMeeting()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $this->meeting->update(['status' => 'ongoing']);
        $this->meeting->refresh();
        $this->dispatch('meeting-updated');
        session()->flash('message', 'Rapat dimulai.');
        $this->redirect(request()->header('Referer', route('meetings.overview', $this->meeting->id)), navigate: true);
    }

    public function finishMeeting()
    {
        if (!$this->canManageMeeting()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $this->meeting->update(['status' => 'completed']);
        $this->meeting->refresh();
        $this->dispatch('meeting-updated');
        session()->flash('message', 'Rapat diselesaikan.');
        $this->redirect(request()->header('Referer', route('meetings.overview', $this->meeting->id)), navigate: true);
    }

    public function reopenMeeting()
    {
        if (auth()->user()?->hasActiveRole('pimpinan')) {
            abort(403, 'Akses tidak diizinkan untuk role pimpinan.');
        }

        if (!$this->canManageMeeting()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($this->signerLocked) {
            abort(403, 'Rapat tidak dapat dilanjutkan karena dokumen telah ditandatangani.');
        }

        $this->meeting->update(['status' => 'ongoing']);
        $this->meeting->refresh();
        $this->dispatch('meeting-updated');
        session()->flash('message', 'Rapat dilanjutkan.');
        $this->redirect(request()->header('Referer', route('meetings.overview', $this->meeting->id)), navigate: true);
    }

    public function openEditModal()
    {
        if (!$this->canManageMeeting()) {
            abort(403, 'Akses tidak diizinkan.');
        }
        $this->resetValidation();
        $this->loadMeetingData();
    }

    public function updatedStartTime($val): void
    {
        if (!empty($val)) {
            try {
                $c = \Carbon\Carbon::parse($val);
                $this->start_hour = $c->format('H');
                $this->start_minute = $c->format('i');
            } catch (\Throwable $e) {}
        }
    }

    public function updatedEndTime($val): void
    {
        if (!empty($val)) {
            try {
                $c = \Carbon\Carbon::parse($val);
                $this->end_hour = $c->format('H');
                $this->end_minute = $c->format('i');
            } catch (\Throwable $e) {}
        }
    }

    protected function normalizeTimes(): void
    {
        if ($this->start_hour !== '' && $this->start_minute !== '') {
            $this->start_time = sprintf('%02d:%02d', (int) $this->start_hour, (int) $this->start_minute);
        } elseif (!empty($this->start_time)) {
            try {
                $this->start_time = \Carbon\Carbon::parse($this->start_time)->format('H:i');
            } catch (\Throwable $e) {
                // Keep original to let validator catch invalid format
            }
        }

        if ($this->end_hour !== '' && $this->end_minute !== '') {
            $this->end_time = sprintf('%02d:%02d', (int) $this->end_hour, (int) $this->end_minute);
        } elseif (!empty($this->end_time)) {
            try {
                $this->end_time = \Carbon\Carbon::parse($this->end_time)->format('H:i');
            } catch (\Throwable $e) {
                // Keep original to let validator catch invalid format
            }
        }
    }

    public function updateMeeting()
    {
        if (!$this->canManageMeeting()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        // Blokir semua perubahan jika ada dokumen yang sudah TTE
        if ($this->signerLocked) {
            session()->flash('error', 'Rapat dikunci karena dokumen telah TTE. Buka revisi terlebih dahulu.');
            $this->dispatch('close-modal', 'edit-meeting-modal');
            return;
        }

        $this->normalizeTimes();

        // Sinkronisasi data penandatangan berdasarkan selected_signer_id
        if ($this->selected_signer_id && $this->selected_signer_id !== 'kepala_opd') {
            if (str_starts_with($this->selected_signer_id, 'pimpinan_')) {
                $userId = (int) str_replace('pimpinan_', '', $this->selected_signer_id);
                $pimpinanUser = \App\Models\User::find($userId);
                if ($pimpinanUser) {
                    $this->signer_title = $pimpinanUser->jabatan ?: 'Pimpinan';
                    $this->signer_name  = $pimpinanUser->name;
                    $this->signer_nip   = $pimpinanUser->nip ?? '';
                    $this->signer_rank  = $pimpinanUser->pangkat ?? '';
                }
            } else {
                $signer = OpdSigner::find($this->selected_signer_id);
                if ($signer) {
                    $this->signer_title = $signer->title;
                    $this->signer_name  = $signer->name;
                    $this->signer_nip   = $signer->nip ?? '';
                    $this->signer_rank  = $signer->rank ?? $signer->eselon ?? '';
                }
            }
        } elseif ($this->opd) {
            $this->signer_title = $this->opd->leader_title ?: ('Kepala ' . $this->opd->name);
            $this->signer_name  = $this->opd->leader_name;
            $this->signer_nip   = $this->opd->leader_nip;
            $this->signer_rank  = $this->opd->leader_rank ?: $this->opd->leader_eselon;
        }


        $validated = $this->validate();
        $validated['agenda'] = $validated['title'];

        if (auth()->user()->hasActiveRole('admin')) {
            $validated['opd_id'] = $this->selected_opd_id ?: null;
        } elseif ($this->opd) {
            $validated['opd_id'] = $this->opd->id;
        }

        $this->meeting->update($validated);
        $this->meeting->refresh();
        $this->loadMeetingData();

        $this->dispatch('close-modal', 'edit-meeting-modal');
        $this->dispatch('meeting-updated');
        session()->flash('message', 'Rapat berhasil diperbarui.');

        $this->redirect(request()->header('Referer', route('meetings.overview', $this->meeting->id)), navigate: true);
    }

    public function deleteMeeting()
    {
        if (!$this->canManageMeeting()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $meetingTitle = $this->meeting->title;
        $this->meeting->delete();

        \App\Services\AuditLogger::log('delete_meeting', "Hapus rapat: {$meetingTitle}");

        session()->flash('message', 'Rapat berhasil dihapus.');
        $this->redirect(route('meetings.index'), navigate: true);
    }

    public function with(BsreEsignService $esignService): array
    {
        return [
            'opd' => $this->opd,
            'opdSigners' => $this->opdSigners,
            'allOpds' => auth()->user()->hasActiveRole('admin') ? Opd::where('is_active', true)->orderBy('name')->get() : collect(),
            'isAdmin' => auth()->user()->hasActiveRole('admin'),
            'tteStatus' => $esignService->checkUserStatus(auth()->user()?->nik),
        ];
    }
}; ?>

<div class="relative">
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-5 relative z-10">
        <div class="space-y-2.5 flex-1 min-w-0">
            @unless(auth()->user()?->hasActiveRole('pimpinan'))
            <div class="flex flex-wrap items-center gap-2">
                <x-meeting-status-badge :status="$meeting->status" />
            </div>
            @endunless

            <h1 class="font-extrabold text-2xl sm:text-3xl text-slate-900 tracking-tight leading-tight break-words">{{ trim($meeting->title) }}</h1>

            @php
                $opdName = $meeting->opd?->name ?? $meeting->creator?->unit_name ?? 'Pemerintah Kabupaten Sinjai';
            @endphp
            <p class="text-sm font-semibold text-slate-600 pt-0.5 break-words">{{ $opdName }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0 w-full md:w-auto mt-2 md:mt-0">
            <x-document-status-badge :meeting="$meeting" />

            @if($meeting->status === 'completed' && auth()->user()->hasActiveRole('pimpinan') && !$meeting->isFullySigned())
                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'sign-all-modal'); $wire.openSignModal()" class="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    <span>TTE Semua Dokumen</span>
                </button>
            @endif

            @if($this->canManageMeeting())
                @if($meeting->status == 'scheduled')
                <button wire:click="startMeeting" wire:loading.attr="disabled" wire:target="startMeeting" wire:confirm="Mulai rapat ini?" class="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm hover:shadow-md gap-2 cursor-pointer">
                    <svg wire:loading.remove wire:target="startMeeting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <svg wire:loading wire:target="startMeeting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Mulai Rapat</span>
                </button>
                @elseif($meeting->status == 'ongoing')
                <button wire:click="finishMeeting" wire:loading.attr="disabled" wire:target="finishMeeting" wire:confirm="Selesaikan rapat ini?" class="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm hover:shadow-md gap-2 cursor-pointer">
                    <svg wire:loading.remove wire:target="finishMeeting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <svg wire:loading wire:target="finishMeeting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Selesaikan</span>
                </button>

                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'qr-presensi-modal')" class="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    <span>QR Code</span>
                </button>
                @elseif($meeting->status == 'completed' && !$this->signerLocked && !auth()->user()?->hasActiveRole('pimpinan'))
                <button wire:click="reopenMeeting" wire:loading.attr="disabled" wire:target="reopenMeeting" wire:confirm="Lanjutkan rapat ini ke status sedang berlangsung?" class="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-amber-50 border border-amber-300 hover:border-amber-400 active:scale-95 text-amber-800 rounded-xl font-bold text-sm transition-all shadow-sm gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Lanjutkan Rapat</span>
                </button>
                @endif

                @if($meeting->status !== 'completed')
                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'edit-meeting-modal'); $wire.openEditModal()" class="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 hover:border-slate-400 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                    <span>Edit</span>
                </button>
                @endif
            @endif
        </div>
    </div>

    <!-- Modal Edit Rapat -->
    <x-modal name="edit-meeting-modal" maxWidth="2xl" :show="$errors->isNotEmpty()">
        <div class="p-6 sm:p-8">
            <div class="flex justify-between items-center pb-4 mb-6 border-b border-slate-100">
                <h2 class="text-xl font-extrabold text-slate-900">
                    Edit Rapat
                </h2>
                <div class="flex items-center gap-2">
                    <button wire:click="deleteMeeting" wire:confirm="Hapus rapat ini beserta seluruh datanya?" class="inline-flex items-center px-3 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl text-xs font-bold transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Hapus
                    </button>
                    <button type="button" x-on:click="$dispatch('close')" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            @if($this->signerLocked)
            <div class="flex items-center gap-2.5 p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 font-medium mb-5">
                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Rapat dikunci — ada dokumen yang sudah TTE. Buka revisi untuk mengedit.</span>
            </div>
            @endif

            <form wire:submit="updateMeeting" class="space-y-5">
                @php $locked = $this->signerLocked; $lockedClass = $locked ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white focus:ring-primary-500 focus:border-primary-500'; @endphp
                <div>
                    <label for="edit_title" class="block text-sm font-bold text-slate-700 mb-1">Agenda</label>
                    <input wire:model="title" id="edit_title" type="text" class="w-full text-sm py-2.5 px-3 border border-slate-300 rounded-xl text-slate-900 shadow-sm transition-colors {{ $lockedClass }}" placeholder="Contoh: Rapat Koordinasi SPBE" {{ $locked ? 'disabled' : 'required' }} />
                    @error('title') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-4">
                    <div>
                        <label for="edit_date" class="block text-sm font-bold text-slate-700 mb-1">Tanggal</label>
                        <input wire:model="date" id="edit_date" type="date" class="w-full text-sm py-2.5 px-3 border border-slate-300 rounded-xl text-slate-900 shadow-sm transition-colors {{ $lockedClass }}" {{ $locked ? 'disabled' : 'required' }} />
                        @error('date') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <!-- Jam Mulai -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Waktu Mulai</label>
                        <div class="flex items-center gap-1.5">
                            <div>
                                <select wire:model="start_hour" class="w-16 text-sm py-2.5 px-2 border border-slate-300 rounded-xl text-slate-900 shadow-sm transition-colors cursor-pointer {{ $lockedClass }}" {{ $locked ? 'disabled' : '' }}>
                                    @for($h = 8; $h <= 16; $h++)
                                        @php $hStr = sprintf('%02d', $h); @endphp
                                        <option value="{{ $hStr }}">{{ $hStr }}</option>
                                    @endfor
                                    @if(!in_array((int)$start_hour, range(8, 16)) && $start_hour !== '')
                                        <option value="{{ $start_hour }}">{{ $start_hour }}</option>
                                    @endif
                                </select>
                            </div>
                            <span class="text-slate-400 font-bold text-sm">:</span>
                            <div>
                                <select wire:model="start_minute" class="w-16 text-sm py-2.5 px-2 border border-slate-300 rounded-xl text-slate-900 shadow-sm transition-colors cursor-pointer {{ $lockedClass }}" {{ $locked ? 'disabled' : '' }}>
                                    @foreach(['00', '15', '30', '45'] as $mOption)
                                        <option value="{{ $mOption }}">{{ $mOption }}</option>
                                    @endforeach
                                    @if(!in_array($start_minute, ['00', '15', '30', '45']) && $start_minute !== '')
                                        <option value="{{ $start_minute }}">{{ $start_minute }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        @error('start_time') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <!-- Jam Selesai -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Waktu Selesai</label>
                        <div class="flex items-center gap-1.5">
                            <div>
                                <select wire:model="end_hour" class="w-16 text-sm py-2.5 px-2 border border-slate-300 rounded-xl text-slate-900 shadow-sm transition-colors cursor-pointer {{ $lockedClass }}" {{ $locked ? 'disabled' : '' }}>
                                    @for($h = 8; $h <= 16; $h++)
                                        @php $hStr = sprintf('%02d', $h); @endphp
                                        <option value="{{ $hStr }}">{{ $hStr }}</option>
                                    @endfor
                                    @if(!in_array((int)$end_hour, range(8, 16)) && $end_hour !== '')
                                        <option value="{{ $end_hour }}">{{ $end_hour }}</option>
                                    @endif
                                </select>
                            </div>
                            <span class="text-slate-400 font-bold text-sm">:</span>
                            <div>
                                <select wire:model="end_minute" class="w-16 text-sm py-2.5 px-2 border border-slate-300 rounded-xl text-slate-900 shadow-sm transition-colors cursor-pointer {{ $lockedClass }}" {{ $locked ? 'disabled' : '' }}>
                                    @foreach(['00', '15', '30', '45'] as $mOption)
                                        <option value="{{ $mOption }}">{{ $mOption }}</option>
                                    @endforeach
                                    @if(!in_array($end_minute, ['00', '15', '30', '45']) && $end_minute !== '')
                                        <option value="{{ $end_minute }}">{{ $end_minute }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        @error('end_time') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label for="edit_location" class="block text-sm font-bold text-slate-700 mb-1">Lokasi</label>
                    <input wire:model="location" id="edit_location" type="text" class="w-full text-sm py-2.5 px-3 border border-slate-300 rounded-xl text-slate-900 shadow-sm transition-colors {{ $lockedClass }}" placeholder="Contoh: Ruang Pola Kantor Bupati" {{ $locked ? 'disabled' : 'required' }} />
                    @error('location') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                @if($isAdmin)
                <div>
                    <label for="edit_selected_opd_id" class="block text-sm font-bold text-slate-700 mb-1">OPD</label>
                    <select wire:model.live="selected_opd_id" id="edit_selected_opd_id" class="w-full text-sm py-2.5 px-3 border border-slate-300 rounded-xl text-slate-900 shadow-sm transition-colors {{ $lockedClass }}" {{ $locked ? 'disabled' : 'required' }}>
                        <option value="">-- Pilih OPD --</option>
                        @foreach($allOpds as $opdItem)
                        <option value="{{ $opdItem->id }}">{{ $opdItem->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Penandatangan Dokumen -->
                <div>
                    <label for="edit_selected_signer_id" class="block text-sm font-bold text-slate-700 mb-1">Penandatangan Dokumen</label>
                    <select wire:model.live="selected_signer_id" id="edit_selected_signer_id"
                        class="w-full text-sm py-2.5 px-3 border border-slate-300 rounded-xl text-slate-900 shadow-sm transition-colors {{ $lockedClass }}"
                        {{ ($isAdmin && empty($selected_opd_id)) || $locked ? 'disabled' : '' }}>
                        @php
                            $leaderTitle = $opd?->leader_title ?: ($opd ? 'Kepala ' . $opd->name : 'Kepala OPD');
                        @endphp
                        <option value="kepala_opd">{{ $leaderTitle }}{{ $opd?->leader_name ? ' — ' . $opd->leader_name : '' }}</option>
                        @foreach($opdSigners as $s)
                        <option value="{{ $s->id }}">{{ $s->title }} — {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm">
                        {{ $locked ? 'Tutup' : 'Batal' }}
                    </button>

                    @if(!$locked)
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateMeeting" class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                        <svg wire:loading.remove wire:target="updateMeeting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg wire:loading wire:target="updateMeeting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Simpan Perubahan</span>
                    </button>
                    @endif
                </div>
            </form>
        </div>
    </x-modal>

    <!-- QR Code Presensi Modal (Global for Meeting Workspace) -->
    <x-modal name="qr-presensi-modal" maxWidth="md">
        <div class="p-6 sm:p-8 text-center">
            <div class="flex justify-between items-center pb-4 border-b border-slate-100 mb-6">
                <h2 class="text-xl font-extrabold text-slate-900">
                    QR Code Presensi
                </h2>
                <button type="button" x-on:click="$dispatch('close')" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            @if($meeting->status === 'ongoing')
            <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm mb-6 inline-block">
                {!! QrCode::size(240)->generate(route('meetings.check-in', $meeting->id)) !!}
            </div>

            <p class="text-sm font-bold text-slate-900 mb-4">Scan QR atau bagikan link berikut:</p>

            <div class="space-y-3 text-left" x-data="{ copied: false, copyLink() { navigator.clipboard.writeText('{{ route('meetings.check-in', $meeting->id) }}'); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
                <div class="relative flex items-center">
                    <input type="text" readonly value="{{ route('meetings.check-in', $meeting->id) }}" class="w-full text-xs font-mono font-medium text-slate-600 bg-slate-50 border border-slate-200 rounded-xl pl-4 pr-12 py-3 text-left select-all focus:ring-primary-500 focus:border-primary-500 shadow-inner transition-colors">
                    <button type="button" @click="copyLink" :title="copied ? 'Tersalin!' : 'Salin Link'" class="absolute right-1.5 p-2 rounded-xl text-slate-400 hover:text-primary-600 hover:bg-white hover:shadow-sm transition-all">
                        <template x-if="!copied">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </template>
                        <template x-if="copied">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </template>
                    </button>
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="copyLink" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 font-bold text-sm rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                        <span x-text="copied ? 'Tersalin!' : 'Salin Link'"></span>
                    </button>
                    <a href="{{ route('meetings.check-in', $meeting->id) }}" target="_blank" class="flex-1 inline-flex items-center justify-center py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white text-sm font-bold rounded-xl shadow-sm transition-all">
                        Buka Link &rarr;
                    </a>
                </div>
            </div>
            @elseif($meeting->status === 'scheduled')
            <div class="p-8 bg-amber-50 border border-amber-200 rounded-2xl text-center">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="font-extrabold text-amber-900 text-lg">Presensi Belum Dibuka</p>
                <p class="text-sm font-medium text-amber-800 mt-2">Silakan mulai rapat untuk membuka sesi presensi.</p>
            </div>
            @else
            <div class="p-8 bg-slate-50 border border-slate-200 rounded-2xl text-center">
                <div class="w-16 h-16 bg-slate-200 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="font-extrabold text-slate-800 text-lg">Presensi Telah Ditutup</p>
                <p class="text-sm font-medium text-slate-500 mt-2">Sesi presensi untuk rapat ini telah ditutup.</p>
            </div>
            @endif
        </div>
    </x-modal>

    <!-- Modal Konfirmasi TTE BSrE (Semua Dokumen) -->
    <x-modal name="sign-all-modal" maxWidth="lg" :show="$showSignModal">
        <div class="p-6 sm:p-8">
            <div class="flex justify-between items-center pb-4 mb-5 border-b border-slate-100">
                <h2 class="text-xl font-extrabold text-slate-900">
                    Tanda Tangan Elektronik
                </h2>
                <button type="button" x-on:click="$dispatch('close')" wire:click="closeSignModal" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            @if($errorMessage)
            <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="flex-1 leading-relaxed">{{ $errorMessage }}</span>
            </div>
            @endif

            <form wire:submit="executeSign" class="space-y-5">
                <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-2xl space-y-2.5 text-sm">
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">Dokumen</span>
                        <span class="font-extrabold text-slate-900 text-right">Semua Dokumen Rapat</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">Penandatangan</span>
                        <span class="font-bold text-slate-900 text-right">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">NIK</span>
                        <span class="font-mono font-bold text-slate-800 text-right">{{ auth()->user()->nik ?: '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm pt-2.5 border-t border-slate-200/70">
                        <span class="text-slate-500 font-medium">Status TTE</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $tteStatus['badge_class'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $tteStatus['dot_class'] ?? ($tteStatus['can_sign'] ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500') }}"></span>
                            {{ $tteStatus['label'] }}
                        </span>
                    </div>
                </div>

                @if(!$tteStatus['can_sign'])
                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800 flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium leading-relaxed">{{ $tteStatus['description'] }}</span>
                </div>
                @endif

                <div x-data="{ showPassphrase: false }">
                    <label for="passphrase_header" class="block text-sm font-bold text-slate-700 mb-1">Passphrase BSrE</label>
                    <div class="relative">
                        <input wire:model="passphrase"
                               id="passphrase_header"
                               :type="showPassphrase ? 'text' : 'password'"
                               class="w-full text-sm py-2.5 pl-3.5 pr-10 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors"
                               placeholder="Masukkan passphrase"
                               required
                               autofocus />
                        <button type="button"
                                @click="showPassphrase = !showPassphrase"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                :title="showPassphrase ? 'Sembunyikan Passphrase' : 'Lihat Passphrase'">
                            <svg x-show="showPassphrase" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                            <svg x-show="!showPassphrase" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    @error('passphrase') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" x-on:click="$dispatch('close')" wire:click="closeSignModal" class="px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 font-bold text-sm rounded-xl transition-all shadow-sm">
                        Batal
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="executeSign" class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white text-sm font-bold rounded-xl shadow-sm transition-all gap-2">
                        <svg wire:loading.remove wire:target="executeSign" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <svg wire:loading wire:target="executeSign" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Tandatangani</span>
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>