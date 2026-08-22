@props([
    'type' => 'success',
])

@php
$styles = [
    'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
    'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
    'danger'  => 'bg-rose-50 border-rose-200 text-rose-800',
    'info'    => 'bg-primary-50 border-primary-200 text-primary-800',
][$type] ?? 'bg-primary-50 border-primary-200 text-primary-800';

$iconColors = [
    'success' => 'text-emerald-600',
    'warning' => 'text-amber-600',
    'danger'  => 'text-rose-600',
    'info'    => 'text-primary-600',
][$type] ?? 'text-primary-600';
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 p-4 rounded-xl border {$styles} shadow-sm"]) }} role="alert">
    <div class="shrink-0 mt-0.5 {{ $iconColors }}">
        @if($type === 'success')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        @elseif($type === 'warning')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        @elseif($type === 'danger')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        @else
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        @endif
    </div>
    <div class="text-sm font-medium leading-relaxed flex-1">
        {{ $slot }}
    </div>
</div>
