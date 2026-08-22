<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use Livewire\Attributes\Layout;

new #[Layout('layouts.guest')] class extends Component {
    public Meeting $meeting;
    public string $type = 'notulen';

    public string $docTitle = '';
    public ?\Carbon\Carbon $signedAt = null;
    public string $signerName = '';
    public string $signerNip = '';
    public string $signerTitle = '';
    public ?string $signerRank = null;
    public bool $isSigned = false;
    public string $opdName = '';

    public function mount(Meeting $meeting, string $type = 'notulen')
    {
        $this->meeting = $meeting;
        $this->type = strtolower($type);

        $opd = $this->meeting->opd;
        $unitName = $opd?->name ?? $this->meeting->creator?->unit_name ?? 'Sekretariat Daerah';
        $this->opdName = $unitName;

        $this->signerTitle = $this->meeting->signer_title ?: ($opd?->leader_title ?: ('Kepala ' . $unitName));
        $this->signerName = $this->meeting->signer_name ?: ($opd?->leader_name ?: 'Pejabat Penandatangan');
        $this->signerNip = $this->meeting->signer_nip ?: ($opd?->leader_nip ?: '-');
        $this->signerRank = $this->meeting->signer_rank ?: ($opd?->leader_rank ?: null);

        switch ($this->type) {
            case 'presensi':
            case 'attendance':
                $this->docTitle = 'Daftar Hadir Rapat';
                $this->signedAt = $this->meeting->attendance_signed_at;
                break;
            case 'dokumentasi':
            case 'photos':
                $this->docTitle = 'Dokumentasi Foto Rapat';
                $this->signedAt = $this->meeting->photos_signed_at;
                break;
            case 'notulen':
            case 'minutes':
            default:
                $this->type = 'notulen';
                $this->docTitle = 'Notulen Rapat';
                $this->signedAt = $this->meeting->minutes_signed_at;
                break;
        }

        $this->isSigned = !is_null($this->signedAt);
    }
}; ?>

<div class="space-y-6">
    {{-- Status Banner --}}
    @if($isSigned)
    <div class="text-center pb-6 border-b border-slate-100">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 mb-3.5 shadow-sm border border-emerald-100">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
        </div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100/80 text-emerald-800 mb-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Tanda Tangan Elektronik Sah
        </div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Verifikasi Dokumen Resmi</h1>
        <p class="text-xs text-slate-500 mt-1">Dokumen terdaftar dan telah ditandatangani secara digital via Balai Sertifikasi Elektronik (BSrE) - BSSN.</p>
    </div>
    @else
    <div class="text-center pb-6 border-b border-slate-100">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 mb-3.5 shadow-sm border border-amber-100">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100/80 text-amber-800 mb-2">
            Belum Ditandatangani
        </div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Status Dokumen</h1>
        <p class="text-xs text-slate-500 mt-1">Dokumen {{ $docTitle }} untuk rapat ini belum dibubuhi Tanda Tangan Elektronik.</p>
    </div>
    @endif

    {{-- Detail Informasi Dokumen --}}
    <div class="bg-slate-50/75 rounded-2xl p-5 border border-slate-100/80 space-y-4">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Informasi Dokumen</h2>

        <div class="grid grid-cols-1 gap-3 text-sm">
            <div>
                <span class="block text-xs text-slate-500 font-medium">Jenis Dokumen</span>
                <span class="font-semibold text-slate-800">{{ $docTitle }}</span>
            </div>
            <div>
                <span class="block text-xs text-slate-500 font-medium">Judul / Agenda Rapat</span>
                <span class="font-semibold text-slate-900 leading-snug">{{ $meeting->title }}</span>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <span class="block text-xs text-slate-500 font-medium">Tanggal Rapat</span>
                    <span class="font-semibold text-slate-800 text-xs">{{ $meeting->date ? $meeting->date->translatedFormat('l, d F Y') : '-' }}</span>
                </div>
                <div>
                    <span class="block text-xs text-slate-500 font-medium">Waktu</span>
                    <span class="font-semibold text-slate-800 text-xs">{{ substr($meeting->start_time, 0, 5) }} WITA</span>
                </div>
            </div>
            <div>
                <span class="block text-xs text-slate-500 font-medium">Lokasi / Tempat</span>
                <span class="font-medium text-slate-700 text-xs">{{ $meeting->location ?? 'Online / Menyesuaikan' }}</span>
            </div>
            <div>
                <span class="block text-xs text-slate-500 font-medium">Instansi Penyelenggara</span>
                <span class="font-medium text-slate-700 text-xs">{{ $opdName }}</span>
            </div>
        </div>
    </div>

    {{-- Detail Informasi Penandatangan --}}
    @if($isSigned)
    <div class="bg-slate-50/75 rounded-2xl p-5 border border-slate-100/80 space-y-4">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Informasi Penandatangan Elektronik</h2>

        <div class="grid grid-cols-1 gap-3 text-sm">
            <div>
                <span class="block text-xs text-slate-500 font-medium">Nama Pejabat</span>
                <span class="font-bold text-slate-900">{{ $signerName }}</span>
            </div>
            <div>
                <span class="block text-xs text-slate-500 font-medium">Jabatan</span>
                <span class="font-medium text-slate-800 text-xs">{{ $signerTitle }}</span>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <span class="block text-xs text-slate-500 font-medium">NIP</span>
                    <span class="font-medium text-slate-700 text-xs font-mono">{{ $signerNip }}</span>
                </div>
                @if($signerRank)
                <div>
                    <span class="block text-xs text-slate-500 font-medium">Pangkat / Golongan</span>
                    <span class="font-medium text-slate-700 text-xs">{{ $signerRank }}</span>
                </div>
                @endif
            </div>
            <div>
                <span class="block text-xs text-slate-500 font-medium">Waktu Penandatanganan (Stempel Waktu)</span>
                <span class="font-semibold text-emerald-700 text-xs flex items-center gap-1.5 mt-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ $signedAt->translatedFormat('d F Y, H:i') }} WITA
                </span>
            </div>
            <div>
                <span class="block text-xs text-slate-500 font-medium">Otoritas Penyelenggara Sertifikat</span>
                <span class="font-medium text-slate-700 text-xs">Balai Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara (BSSN)</span>
            </div>
        </div>
    </div>

    {{-- Tombol Unduh PDF --}}
    <div class="pt-2">
        <a href="{{ route('meetings.verify.download', ['meeting' => $meeting->id, 'type' => $type]) }}" target="_blank" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold shadow-sm transition-colors duration-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Unduh Dokumen PDF Resmi
        </a>
    </div>
    @endif

    {{-- Tombol Kembali --}}
    <div class="text-center pt-2">
        <a href="/" wire:navigate class="text-xs text-slate-500 hover:text-primary-600 transition-colors font-medium">
            &larr; Kembali ke Beranda eRapat
        </a>
    </div>
</div>
