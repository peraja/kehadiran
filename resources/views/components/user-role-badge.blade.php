@props(['role'])

@php
    $roleName = is_string($role) ? $role : ($role->name ?? 'pegawai');
@endphp

@if($roleName === 'admin')
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 bg-purple-50 text-purple-700 border border-purple-200/80 rounded-full text-xs font-bold shadow-2xs whitespace-nowrap']) }}>
        <span class="w-1.5 h-1.5 rounded-full bg-purple-500 mr-1.5 shrink-0"></span>
        Super Admin
    </span>
@elseif($roleName === 'admin_opd')
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 bg-primary-50 text-primary-700 border border-primary-200/80 rounded-full text-xs font-bold shadow-2xs whitespace-nowrap']) }}>
        <span class="w-1.5 h-1.5 rounded-full bg-primary-500 mr-1.5 shrink-0"></span>
        Admin OPD
    </span>
@elseif($roleName === 'pimpinan')
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200/80 rounded-full text-xs font-bold shadow-2xs whitespace-nowrap']) }}>
        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mr-1.5 shrink-0"></span>
        Pimpinan
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 bg-slate-100 text-slate-600 border border-slate-200/80 rounded-full text-xs font-bold shadow-2xs whitespace-nowrap']) }}>
        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5 shrink-0"></span>
        Pegawai
    </span>
@endif
