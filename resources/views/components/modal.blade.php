@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'dismissible' => true,
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    '6xl' => 'sm:max-w-6xl',
][$maxWidth] ?? 'sm:max-w-2xl';
@endphp

<div
    x-data="{
        show: @js($show),
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
            {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    x-on:open-modal.window="($event.detail == '{{ $name }}' || (Array.isArray($event.detail) && $event.detail[0] == '{{ $name }}') || ($event.detail && $event.detail.name == '{{ $name }}')) ? show = true : null"
    x-on:close-modal.window="($event.detail == '{{ $name }}' || (Array.isArray($event.detail) && $event.detail[0] == '{{ $name }}') || ($event.detail && $event.detail.name == '{{ $name }}')) ? show = false : null"
    @if($dismissible)
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    @endif
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    class="fixed inset-0 z-[150] overflow-hidden"
    style="display: {{ $show ? 'block' : 'none' }};"
>
    <!-- Modal Backdrop -->
    <div
        x-show="show"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
        @if($dismissible) x-on:click="show = false" @endif
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <!-- Centering & Viewport Boundary Wrapper -->
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4 sm:p-6 text-center pointer-events-none">
        <!-- Modal Content Card with Internal Scroll -->
        <div
            x-show="show"
            class="relative w-full {{ $maxWidth }} max-h-[calc(100dvh-2rem)] sm:max-h-[calc(100dvh-3.5rem)] transform overflow-y-auto rounded-3xl bg-white text-left shadow-2xl border border-slate-200/80 transition-all pointer-events-auto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        >
            {{ $slot }}
        </div>
    </div>
</div>
