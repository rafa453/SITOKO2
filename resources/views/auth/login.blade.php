<x-guest-layout>
    <div class="flex min-h-screen flex-col lg:flex-row" x-data="themeToggle" x-cloak>

        {{-- ===================== PANEL KIRI: MESH GRADIENT ===================== --}}
        <aside class="relative isolate flex min-h-[260px] shrink-0 flex-col justify-between overflow-hidden bg-[#EEF2FF] p-8 lg:min-h-screen lg:w-[40%] lg:p-12 dark:bg-[#0A0F1D]">
            {{-- Mesh --}}
            <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
                <div class="absolute -left-24 -top-24 h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle,rgba(79,70,229,0.55)_0%,rgba(79,70,229,0)_65%)]"></div>
                <div class="absolute -right-16 top-10 h-[380px] w-[380px] rounded-full bg-[radial-gradient(circle,rgba(59,130,246,0.5)_0%,rgba(59,130,246,0)_65%)]"></div>
                <div class="absolute -bottom-32 left-10 h-[400px] w-[400px] rounded-full bg-[radial-gradient(circle,rgba(56,189,248,0.45)_0%,rgba(56,189,248,0)_65%)]"></div>
                <div class="absolute bottom-[-120px] right-[-80px] h-[320px] w-[320px] rounded-full bg-[radial-gradient(circle,rgba(129,140,248,0.5)_0%,rgba(129,140,248,0)_65%)]"></div>
                <div class="absolute inset-0 bg-[linear-gradient(160deg,rgba(255,255,255,0.45)_0%,rgba(238,242,255,0)_55%)] dark:opacity-20"></div>
            </div>

            {{-- Wordmark --}}
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 shadow-sm dark:bg-indigo-600">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="3" y="3" width="7.5" height="7.5" rx="1.5" fill="white"/>
                        <rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5" fill="white" opacity="0.6"/>
                        <rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5" fill="white" opacity="0.6"/>
                        <rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5" fill="white" opacity="0.3"/>
                    </svg>
                </div>
                <span class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">sitoko</span>
            </div>

            {{-- Tagline --}}
            <div class="mt-10 lg:mt-0">
                <p class="text-xs font-medium tracking-wide text-slate-500 dark:text-slate-400">Anda bisa dengan mudah</p>
                <h1 class="mt-2 max-w-[13ch] text-[28px] font-bold leading-[1.15] tracking-tight text-slate-900 lg:text-[34px] dark:text-white">
                    Kelola inventaris &amp; kasir dalam satu tempat.
                </h1>
                <div class="mt-6 hidden items-center gap-2 text-sm text-slate-600 lg:flex dark:text-slate-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                    <span>Stok akurat, transaksi rapi, laporan instan.</span>
                </div>
            </div>
        </aside>

        {{-- ===================== PANEL KANAN: FORM ===================== --}}
        <main class="flex flex-1 items-center justify-center bg-white px-6 py-12 sm:px-10 lg:w-[60%] lg:py-16 dark:bg-[#0B1120]">
            <div class="w-full max-w-[400px]">

                {{-- Theme Toggle (Ganti bintang) --}}
                <button type="button" @click="toggle()" class="group relative flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-600 transition hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700" :title="dark ? 'Light Mode' : 'Dark Mode'">
                    <svg x-show="dark" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    <svg x-show="!dark" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>

                <h2 class="mt-6 text-[28px] font-bold tracking-tight text-slate-900 dark:text-white">Masuk ke SITOKO</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Gunakan akun admin atau kasir yang terdaftar untuk melanjutkan.
                </p>

                {{-- Error global --}}
                @if ($errors->any())
                    <div class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-900/20" role="alert">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-red-700 dark:text-red-300">Email atau password yang Anda masukkan tidak cocok.</p>
                    </div>
                @endif

                <form class="mt-8 space-y-5" action="{{ route('login') }}" method="POST">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                        <input id="email" name="email" type="email" autocomplete="email" required autofocus
                            value="{{ old('email') }}"
                            placeholder="nama@toko.com"
                            class="mt-2 block min-h-[44px] w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition duration-150 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 dark:border-slate-700 dark:bg-slate-800/50 dark:text-white dark:placeholder:text-slate-600">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            placeholder="••••••••"
                            class="mt-2 block min-h-[44px] w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition duration-150 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 dark:border-slate-700 dark:bg-slate-800/50 dark:text-white dark:placeholder:text-slate-600">
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                        <div class="flex items-center gap-2">
                            <input id="remember" name="remember" type="checkbox"
                                class="h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500/40 dark:border-slate-700 dark:bg-slate-800">
                            <label for="remember" class="cursor-pointer select-none text-sm text-slate-700 dark:text-slate-400">Ingat saya</label>
                        </div>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                                class="rounded text-sm font-medium text-indigo-600 underline-offset-4 transition hover:text-indigo-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50">
                                Lupa password?
                            </a>
                        @endif
                    </div>

                    <button type="submit"
                        class="flex min-h-[48px] w-full cursor-pointer items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 active:bg-indigo-800">
                        Masuk ke Sistem
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-slate-400 dark:text-slate-600">
                    &copy; {{ date('Y') }} SITOKO Enterprise. All rights reserved.
                </p>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('themeToggle', () => ({
                dark: document.documentElement.classList.contains('dark'),
                toggle() {
                    this.dark = !this.dark;
                    document.documentElement.classList.toggle('dark', this.dark);
                    localStorage.setItem('theme', this.dark ? 'dark' : 'light');
                }
            }));
        });
    </script>
</x-guest-layout>