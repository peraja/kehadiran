@props([
    'type' => 'success',
    'timeout' => null,
    'dismissible' => true,
])

@php
// Default timeout: 4000ms untuk success dan info, 0 (tetap tampil) untuk danger dan warning
$defaultTimeout = in_array($type, ['success', 'info']) ? 4000 : 0;
$timeoutMs = $timeout !== null ? (int) $timeout : $defaultTimeout;

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

<div x-data="{ show: true }"
     x-show="show"
     x-cloak
     @if($timeoutMs > 0)
     x-init="setTimeout(() => show = false, {{ $timeoutMs }})"
     @endif
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-1"
     {{ $attributes->merge(['class' => "flex items-start gap-3 p-4 rounded-xl border {$styles} shadow-xs relative transition-all"]) }}
     role="alert">
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

    <div class="text-sm font-medium leading-relaxed flex-1 pr-6">
        {{ $slot }}
    </div>

    @if($dismissible)
    <button type="button"
            @click="show = false"
            class="absolute top-3.5 right-3.5 p-1 rounded-lg opacity-60 hover:opacity-100 hover:bg-black/5 transition-all cursor-pointer"
            title="Tutup">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
    @endif
</div>
