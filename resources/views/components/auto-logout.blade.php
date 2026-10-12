@props([
    'timeoutMinutes' => 15,
    'warningSeconds' => 60,
])

@php
    $totalSeconds = max(60, $timeoutMinutes * 60);
    $warningSecs = min($warningSeconds, (int) ($totalSeconds / 2));
@endphp

<div x-data="autoLogoutWatcher({
        timeoutSeconds: {{ $totalSeconds }},
        warningSeconds: {{ $warningSecs }},
        logoutUrl: '{{ route('logout') }}',
        csrfToken: '{{ csrf_token() }}'
    })"
    x-init="init()"
    class="relative">

    <!-- Modal Peringatan Sesi Akan Berakhir -->
    <template x-teleport="body">
        <div x-show="showWarningModal"
             x-cloak
             class="fixed inset-0 z-[200] overflow-y-auto"
             role="dialog"
             aria-modal="true"
             aria-labelledby="session-timeout-modal-title">

            <!-- Backdrop -->
            <div x-show="showWarningModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click.prevent></div>

            <!-- Modal Panel Centering -->
            <div class="fixed inset-0 z-10 flex items-center justify-center p-4 text-center">
                <div x-show="showWarningModal"
                     x-transition:enter="ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                     class="relative w-full max-w-md transform overflow-hidden rounded-3xl bg-white p-6 sm:p-7 text-left shadow-2xl border border-slate-100 transition-all">

                    <div class="flex items-center gap-3.5 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 shadow-xs">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 id="session-timeout-modal-title" class="text-base sm:text-lg font-extrabold text-slate-900 leading-tight">
                                Peringatan Sesi Berakhir
                            </h3>
                            <p class="text-xs text-slate-500 font-semibold mt-0.5">
                                Tidak ada aktivitas terdeteksi
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm text-slate-600">
                        <p class="text-xs sm:text-sm leading-relaxed">
                            Sesi Anda akan berakhir karena tidak ada aktivitas.
                        </p>

                        <div class="bg-amber-50/80 border border-amber-200 rounded-2xl p-4 flex items-center justify-between">
                            <span class="text-xs font-bold text-amber-900">Logout otomatis dalam:</span>
                            <span class="font-mono text-xl font-extrabold text-amber-700 bg-white px-3 py-1 rounded-xl shadow-2xs border border-amber-200"
                                  x-text="countdownText">
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5">
                        <button type="button"
                                @click="doLogout()"
                                class="w-full sm:w-auto px-4 py-2.5 text-xs sm:text-sm font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all active:scale-95 cursor-pointer">
                            Logout Sekarang
                        </button>
                        <button type="button"
                                @click="stayLoggedIn()"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-xs sm:text-sm font-bold rounded-xl shadow-xs transition-all active:scale-95 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Tetap Masuk</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function autoLogoutWatcher(config) {
    return {
        timeoutSeconds: config.timeoutSeconds,
        warningSeconds: config.warningSeconds,
        logoutUrl: config.logoutUrl,
        csrfToken: config.csrfToken,

        storageKey: 'erapat_last_activity',
        logoutKey: 'erapat_session_logged_out',
        showWarningModal: false,
        remainingSeconds: config.warningSeconds,
        timerInterval: null,
        isLoggingOut: false,

        get countdownText() {
            const m = Math.floor(this.remainingSeconds / 60);
            const s = this.remainingSeconds % 60;
            return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        },

        getLastActivity() {
            const saved = localStorage.getItem(this.storageKey);
            return saved ? parseInt(saved, 10) : Date.now();
        },

        init() {
            // Inisialisasi last activity
            if (!localStorage.getItem(this.storageKey)) {
                localStorage.setItem(this.storageKey, Date.now().toString());
            }

            this.recordActivity();
            this.attachActivityListeners();

            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }

            this.timerInterval = setInterval(() => {
                this.checkIdleTime();
            }, 1000);

            // Dengarkan aktivitas atau logout dari tab lain
            window.addEventListener('storage', (event) => {
                if (event.key === this.storageKey && this.showWarningModal) {
                    const elapsed = Math.floor((Date.now() - parseInt(event.newValue || '0', 10)) / 1000);
                    if (elapsed < (this.timeoutSeconds - this.warningSeconds)) {
                        this.showWarningModal = false;
                    }
                } else if (event.key === this.logoutKey) {
                    // Jika tab lain telah logout otomatis, redirect tab ini juga
                    window.location.reload();
                }
            });

            // Update aktivitas saat navigasi Livewire
            document.addEventListener('livewire:navigating', () => {
                this.recordActivity();
            });
        },

        recordActivity() {
            if (!this.showWarningModal) {
                localStorage.setItem(this.storageKey, Date.now().toString());
            }
        },

        attachActivityListeners() {
            const events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'];
            let throttleTimeout = null;

            events.forEach(eventName => {
                window.addEventListener(eventName, () => {
                    if (!throttleTimeout) {
                        this.recordActivity();
                        throttleTimeout = setTimeout(() => {
                            throttleTimeout = null;
                        }, 1000);
                    }
                }, { passive: true });
            });
        },

        checkIdleTime() {
            if (this.isLoggingOut) return;

            const now = Date.now();
            const lastActivity = this.getLastActivity();
            const elapsedSeconds = Math.floor((now - lastActivity) / 1000);
            const idleLimit = this.timeoutSeconds;
            const warningLimit = idleLimit - this.warningSeconds;

            if (elapsedSeconds >= idleLimit) {
                this.doLogout();
            } else if (elapsedSeconds >= warningLimit) {
                this.showWarningModal = true;
                this.remainingSeconds = Math.max(0, idleLimit - elapsedSeconds);
            } else {
                if (this.showWarningModal) {
                    this.showWarningModal = false;
                }
            }
        },

        stayLoggedIn() {
            this.showWarningModal = false;
            localStorage.setItem(this.storageKey, Date.now().toString());

            // Kirim request ping untuk memperbarui sesi di server
            fetch('{{ route('session.ping') }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            }).catch(() => {});
        },

        doLogout() {
            if (this.isLoggingOut) return;
            this.isLoggingOut = true;
            if (this.timerInterval) clearInterval(this.timerInterval);

            // Tandai untuk tab lain
            localStorage.setItem(this.logoutKey, Date.now().toString());

            // Buat form submit POST logout
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.logoutUrl;

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = this.csrfToken;
            form.appendChild(tokenInput);

            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'reason';
            reasonInput.value = 'timeout';
            form.appendChild(reasonInput);

            document.body.appendChild(form);
            form.submit();
        }
    };
}
</script>
