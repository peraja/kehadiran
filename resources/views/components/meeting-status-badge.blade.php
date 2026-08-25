@props(['meeting' => null, 'status' => null, 'withDocumentStatus' => true])

@php
    $meetingModel = $meeting instanceof \App\Models\Meeting ? $meeting : null;
    $statusValue = $status ?? $meetingModel?->status ?? 'scheduled';
@endphp

@if($statusValue === 'scheduled')
    <span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
        Dijadwalkan
    </span>
@elseif($statusValue === 'ongoing')
    <span class="inline-flex items-center px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-[11px] font-bold uppercase tracking-wider shadow-2xs whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5 animate-pulse"></span>
        Berlangsung
    </span>
@elseif($statusValue === 'completed')
    @if(!$withDocumentStatus || !$meetingModel)
        <span class="inline-flex items-center px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap shadow-2xs">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
            Selesai
        </span>
    @else
        @php
            $isFullySigned = $meetingModel->isFullySigned();
            $signedCount = $meetingModel->signedTteCount();
            $hasContent = $meetingModel->hasAnyDocumentContent();
        @endphp

        @if($isFullySigned)
            <span class="inline-flex items-center px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap shadow-2xs" title="Notulen, Presensi & Dokumentasi telah TTE">
                <svg class="w-3 h-3 mr-1.5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Selesai</span>
                <span class="mx-1.5 text-emerald-300">•</span>
                <span class="font-extrabold text-emerald-800">TTE Lengkap</span>
            </span>
        @elseif($signedCount > 0)
            <span class="inline-flex items-center px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap shadow-2xs" title="{{ $signedCount }} dari 3 dokumen telah di-TTE">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                <span>Selesai</span>
                <span class="mx-1.5 text-amber-300">•</span>
                <span class="font-extrabold text-amber-800">{{ $signedCount }}/3 TTE</span>
            </span>
        @elseif($hasContent)
            <span class="inline-flex items-center px-3 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap shadow-2xs" title="Dokumen siap ditandatangani TTE">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mr-1.5"></span>
                <span>Selesai</span>
                <span class="mx-1.5 text-indigo-300">•</span>
                <span class="font-extrabold text-indigo-800">Menunggu TTE</span>
            </span>
        @else
            <span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap shadow-2xs" title="Penyelenggara belum melengkapi isi berkas dokumen rapat">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
                <span>Selesai</span>
                <span class="mx-1.5 text-slate-300">•</span>
                <span class="font-extrabold text-slate-700">Draft TTE</span>
            </span>
        @endif
    @endif
@endif
