@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Navigasi Halaman" class="flex items-center justify-between w-full">
            {{-- Mobile Pagination (< sm) --}}
            <div class="flex items-center justify-between w-full sm:hidden">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center gap-1 px-3.5 py-2 text-xs font-bold text-slate-400 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed">
                        &larr; Sebelumnya
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="inline-flex items-center gap-1 px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-slate-900 rounded-xl transition-all shadow-xs active:scale-95">
                        &larr; Sebelumnya
                    </button>
                @endif

                <span class="text-xs font-semibold text-slate-500">
                    {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
                </span>

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="inline-flex items-center gap-1 px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-slate-900 rounded-xl transition-all shadow-xs active:scale-95">
                        Berikutnya &rarr;
                    </button>
                @else
                    <span class="inline-flex items-center gap-1 px-3.5 py-2 text-xs font-bold text-slate-400 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed">
                        Berikutnya &rarr;
                    </span>
                @endif
            </div>

            {{-- Desktop Pagination (>= sm) --}}
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-500 font-medium">
                        Menampilkan
                        <span class="font-bold text-slate-800">{{ $paginator->firstItem() }}</span>
                        &ndash;
                        <span class="font-bold text-slate-800">{{ $paginator->lastItem() }}</span>
                        dari
                        <span class="font-bold text-slate-800">{{ $paginator->total() }}</span>
                        data
                    </p>
                </div>

                <div class="flex items-center gap-1.5">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="inline-flex items-center justify-center w-9 h-9 text-slate-300 bg-slate-50 border border-slate-200 rounded-xl cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="inline-flex items-center justify-center w-9 h-9 text-slate-600 bg-white hover:bg-slate-100 hover:text-slate-900 border border-slate-200 rounded-xl transition-all shadow-xs active:scale-95" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    @endif

                    {{-- Page Links --}}
                    @foreach ($elements as $element)
                        {{-- Separator --}}
                        @if (is_string($element))
                            <span class="inline-flex items-center justify-center w-9 h-9 text-xs font-bold text-slate-400 select-none">
                                {{ $element }}
                            </span>
                        @endif

                        {{-- Array of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                    @if ($page == $paginator->currentPage())
                                        <span class="inline-flex items-center justify-center w-9 h-9 text-xs font-bold text-white bg-primary-600 rounded-xl shadow-sm select-none">
                                            {{ $page }}
                                        </span>
                                    @else
                                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="inline-flex items-center justify-center w-9 h-9 text-xs font-bold text-slate-600 bg-white hover:bg-slate-100 hover:text-slate-900 border border-slate-200 rounded-xl transition-all shadow-xs active:scale-95">
                                            {{ $page }}
                                        </button>
                                    @endif
                                </span>
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="inline-flex items-center justify-center w-9 h-9 text-slate-600 bg-white hover:bg-slate-100 hover:text-slate-900 border border-slate-200 rounded-xl transition-all shadow-xs active:scale-95" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="inline-flex items-center justify-center w-9 h-9 text-slate-300 bg-slate-50 border border-slate-200 rounded-xl cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    @endif
                </div>
            </div>
        </nav>
    @endif
</div>
