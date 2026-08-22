<div>
    @if ($paginator->hasPages())
        @php
            $isLivewire = isset($this) && method_exists($this, 'gotoPage');
            $current = $paginator->currentPage();
            $last = $paginator->lastPage();
        @endphp
        <nav role="navigation" aria-label="Navigasi Halaman" class="flex flex-col sm:flex-row items-center justify-between gap-3 w-full">
            {{-- Info Count --}}
            <div>
                <p class="text-xs sm:text-sm text-slate-500 font-medium">
                    Menampilkan
                    <span class="font-bold text-slate-800">{{ $paginator->firstItem() ?? 0 }}</span>
                    &ndash;
                    <span class="font-bold text-slate-800">{{ $paginator->lastItem() ?? 0 }}</span>
                    dari
                    <span class="font-bold text-slate-800">{{ $paginator->total() }}</span>
                    data
                </p>
            </div>

            {{-- Controls --}}
            <div class="flex items-center gap-1.5">
                {{-- Previous Button --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Halaman Sebelumnya" class="inline-flex items-center justify-center w-8 h-8 text-slate-300 bg-slate-50 border border-slate-200 rounded-xl cursor-not-allowed select-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </span>
                @else
                    <button type="button" @if($isLivewire) wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @else onclick="window.location.href='{{ $paginator->previousPageUrl() }}'" @endif class="inline-flex items-center justify-center w-8 h-8 text-slate-700 bg-white hover:bg-slate-100 hover:text-slate-900 border border-slate-200 rounded-xl transition-all shadow-xs active:scale-95" title="Sebelumnya" aria-label="Halaman Sebelumnya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                @endif

                {{-- Current Page Pill --}}
                <span class="inline-flex items-center px-3 py-1.5 text-xs font-extrabold text-slate-700 bg-white border border-slate-200 rounded-xl shadow-2xs select-none">
                    {{ $current }} / {{ $last }}
                </span>

                {{-- Next Button --}}
                @if ($paginator->hasMorePages())
                    <button type="button" @if($isLivewire) wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @else onclick="window.location.href='{{ $paginator->nextPageUrl() }}'" @endif class="inline-flex items-center justify-center w-8 h-8 text-slate-700 bg-white hover:bg-slate-100 hover:text-slate-900 border border-slate-200 rounded-xl transition-all shadow-xs active:scale-95" title="Berikutnya" aria-label="Halaman Berikutnya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                @else
                    <span aria-disabled="true" aria-label="Halaman Berikutnya" class="inline-flex items-center justify-center w-8 h-8 text-slate-300 bg-slate-50 border border-slate-200 rounded-xl cursor-not-allowed select-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>
