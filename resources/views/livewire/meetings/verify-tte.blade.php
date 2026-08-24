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
                $this->docTitle = 'Presensi Rapat';
                $this->signedAt = $this->meeting->attendance_signed_at;
                break;
            case 'dokumentasi':
            case 'photos':
                $this->docTitle = 'Dokumentasi Rapat';
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
    <!-- Verification Status Banner -->
    @if($isSigned)
    <div class="text-center space-y-6">
        <div>
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3.5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Tanda Tangan Elektronik Sah
            </span>
        </div>

        <!-- Verification Detail Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 text-left divide-y divide-slate-100 shadow-2xs">
            <div class="py-2.5 flex justify-between items-start gap-4">
                <span class="text-slate-500 font-medium text-sm shrink-0 pt-0.5">Instansi</span>
                <span class="font-semibold text-slate-800 text-right leading-snug break-words">Pemerintah Kabupaten Sinjai</span>
            </div>
            <div class="py-2.5 flex justify-between items-start gap-4">
                <span class="text-slate-500 font-medium text-sm shrink-0 pt-0.5">Penandatangan</span>
                <div class="text-right min-w-0">
                    <span class="font-bold text-slate-900 block leading-snug break-words">{{ $signerName }}</span>
                    <span class="text-xs text-slate-500 block mt-0.5 leading-snug break-words">{{ $signerTitle }}</span>
                    @if($signerNip && $signerNip !== '-')
                    <span class="text-xs text-slate-400 font-mono block mt-0.5">NIP. {{ $signerNip }}</span>
                    @endif
                </div>
            </div>
            <div class="py-2.5 flex justify-between items-center gap-4">
                <span class="text-slate-500 font-medium text-sm shrink-0">Waktu TTE</span>
                <span class="font-bold text-emerald-700 font-mono text-right text-sm">{{ $signedAt->translatedFormat('d F Y, H:i') }} WITA</span>
            </div>
            <div class="py-2.5 flex justify-between items-center gap-4">
                <span class="text-slate-500 font-medium text-sm shrink-0">Sertifikat</span>
                <span class="font-bold text-slate-800 text-right text-xs">BSrE - BSSN</span>
            </div>
        </div>

        <!-- Action Button -->
        <div class="pt-2 space-y-3">
            <a href="{{ route('meetings.verify.download', ['meeting' => $meeting->id, 'type' => $type]) }}" target="_blank" class="w-full flex justify-center items-center px-6 py-3.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-extrabold text-sm transition-all shadow-sm gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Unduh PDF</span>
            </a>

            <div>
                <a href="/" wire:navigate class="text-xs text-slate-400 hover:text-slate-700 transition-colors font-semibold">
                    &larr; Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-8 space-y-6">
        <div>
            <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-3.5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 mb-2">
                Belum Ditandatangani
            </span>
            <h2 class="text-xl font-extrabold text-slate-900">{{ $docTitle }}</h2>
            <p class="text-sm font-medium text-slate-500 mt-1">Dokumen untuk rapat "{{ $meeting->title }}" belum dibubuhi TTE.</p>
        </div>

        <div>
            <a href="/" wire:navigate class="inline-flex justify-center items-center px-6 py-3 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm">
                &larr; Kembali ke Beranda
            </a>
        </div>
    </div>
    @endif
</div>