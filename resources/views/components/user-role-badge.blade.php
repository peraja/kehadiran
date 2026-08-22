@props(['role'])

@php
    $roleName = is_string($role) ? $role : ($role->name ?? 'pegawai');
@endphp

@if($roleName === 'admin')
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-3 py-1 bg-purple-50 text-purple-700 border border-purple-200 rounded-full text-[11px] font-bold uppercase tracking-wider shadow-2xs whitespace-nowrap']) }}>
        <span class="w-1.5 h-1.5 rounded-full bg-purple-500 mr-1.5"></span>
        Super Admin
    </span>
@elseif($roleName === 'admin_opd')
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-3 py-1 bg-primary-50 text-primary-700 border border-primary-200 rounded-full text-[11px] font-bold uppercase tracking-wider shadow-2xs whitespace-nowrap']) }}>
        <span class="w-1.5 h-1.5 rounded-full bg-primary-500 mr-1.5"></span>
        Admin OPD
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-3 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-[11px] font-bold uppercase tracking-wider whitespace-nowrap']) }}>
        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
        Pegawai
    </span>
@endif
