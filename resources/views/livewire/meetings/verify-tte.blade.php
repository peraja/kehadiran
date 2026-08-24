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
    <div class="text-center space-y-4">
        <div>
            <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-2.5">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Tanda Tangan Elektronik Sah
            </div>
        </div>

        <!-- Verification Detail Card -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 text-left divide-y divide-slate-100 text-xs sm:text-sm shadow-2xs">
            <div class="py-2.5 flex justify-between items-start gap-4">
                <span class="text-slate-400 font-medium shrink-0 pt-0.5">Dokumen</span>
                <div class="text-right min-w-0 max-w-[65%]">
                    <span class="font-bold text-slate-900 block leading-snug break-words">{{ $docTitle }}</span>
                    <span class="text-[11px] sm:text-xs text-slate-500 block mt-0.5 leading-snug break-words">{{ $meeting->title }}</span>
                </div>
            </div>
            <div class="py-2.5 flex justify-between items-start gap-4">
                <span class="text-slate-400 font-medium shrink-0 pt-0.5">Penandatangan</span>
                <div class="text-right min-w-0 max-w-[65%]">
                    <span class="font-bold text-slate-900 block leading-snug break-words">{{ $signerName }}</span>
                    <span class="text-[11px] sm:text-xs text-slate-500 block mt-0.5 leading-snug break-words">{{ $signerTitle }}</span>
                </div>
            </div>
            <div class="py-2.5 flex justify-between items-start gap-4">
                <span class="text-slate-400 font-medium shrink-0 pt-0.5">OPD</span>
                <span class="font-semibold text-slate-800 text-right leading-snug break-words max-w-[65%]">{{ $opdName }}</span>
            </div>
            <div class="py-2.5 flex justify-between items-center gap-4">
                <span class="text-slate-400 font-medium shrink-0">Waktu TTE</span>
                <span class="font-bold text-emerald-700 font-mono text-right">{{ $signedAt->translatedFormat('d F Y, H:i') }} WITA</span>
            </div>
            <div class="py-2.5 flex justify-between items-center gap-4">
                <span class="text-slate-400 font-medium shrink-0">Sertifikat</span>
                <span class="font-bold text-slate-800 text-right text-xs">BSrE - BSSN</span>
            </div>
        </div>

        <!-- Download Action Button -->
        <a href="{{ route('meetings.verify.download', ['meeting' => $meeting->id, 'type' => $type]) }}"
           download
           class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs transition-all focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:scale-[0.99]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Download PDF
        </a>
    </div>
    @else
    <div class="text-center py-8 space-y-6">
        <div>
            <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 mb-2">
                Belum Ditandatangani
            </span>
            <h2 class="text-xl font-extrabold text-slate-900">{{ $docTitle }}</h2>
            <p class="text-sm font-medium text-slate-500 mt-1">Dokumen untuk rapat "{{ $meeting->title }}" belum dibubuhi TTE.</p>
        </div>
    </div>
    @endif
</div>