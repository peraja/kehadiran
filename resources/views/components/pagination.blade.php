@props(['paginator', 'onEachSide' => 1])

@if ($paginator && $paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'p-4 sm:px-6 sm:py-4 border-t border-slate-100 bg-slate-50/50']) }}>
        {{ $paginator->onEachSide($onEachSide)->links('vendor.pagination.tailwind') }}
    </div>
@endif
