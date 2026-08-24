@props(['meeting'])

@php
    $status = $meeting->status ?? 'scheduled';
    $isFullySigned = $meeting->isFullySigned();
    $signedCount = $meeting->signedTteCount();
@endphp

@if($status === 'completed')
    @if($isFullySigned)
        <span class="inline-flex items-center px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap shadow-2xs" title="Notulen, Presensi & Dokumentasi telah TTE">
            <svg class="w-3 h-3 mr-1.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            TTE Lengkap
        </span>
    @elseif($signedCount > 0)
        <span class="inline-flex items-center px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap shadow-2xs" title="{{ $signedCount }} dari 3 dokumen telah di-TTE">
            <svg class="w-3 h-3 mr-1.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            {{ $signedCount }}/3 Sudah TTE
        </span>
    @else
        <span class="inline-flex items-center px-3 py-1 bg-sky-50 text-sky-700 border border-sky-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap shadow-2xs" title="Belum ada dokumen yang di-TTE">
            <svg class="w-3 h-3 mr-1.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Menunggu TTE
        </span>
    @endif
@else
    <span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-500 border border-slate-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap" title="Dokumen disahkan setelah status rapat diselesaikan">
        <svg class="w-3 h-3 mr-1.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
        Draft
    </span>
@endif
