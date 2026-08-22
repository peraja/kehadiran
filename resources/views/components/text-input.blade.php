@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm']) }}>
