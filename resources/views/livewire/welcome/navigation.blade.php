<nav class="-mx-3 flex flex-1 justify-end">
    @auth
        <a
            href="{{ url('/dashboard') }}"
            class="rounded-xl px-3 py-2 text-slate-700 font-medium transition hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
        >
            Dashboard
        </a>
    @else
        <a
            href="{{ route('login') }}"
            class="rounded-xl px-3 py-2 text-slate-700 font-medium transition hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
        >
            Masuk
        </a>

        @if (Route::has('register'))
            <a
                href="{{ route('register') }}"
                class="rounded-xl px-3 py-2 text-slate-700 font-medium transition hover:text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
            >
                Register
            </a>
        @endif
    @endauth
</nav>
