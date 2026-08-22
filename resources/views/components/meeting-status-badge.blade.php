@props(['status'])

@if($status == 'scheduled')
    <span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
        Dijadwalkan
    </span>
@elseif($status == 'ongoing')
    <span class="inline-flex items-center px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-[11px] font-bold uppercase tracking-wider shadow-2xs whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5 animate-pulse"></span>
        Berlangsung
    </span>
@elseif($status == 'completed')
    <span class="inline-flex items-center px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
        Selesai
    </span>
@endif
