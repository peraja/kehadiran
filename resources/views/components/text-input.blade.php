@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed']) }}>
