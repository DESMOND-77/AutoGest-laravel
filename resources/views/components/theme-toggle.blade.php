{{-- Cycles Light -> Dark -> System on click, persists to localStorage.
     x-init re-applies the class in case the page was restored from bfcache
     with a stale class list. --}}
<div
    x-data="{
        theme: localStorage.getItem('theme') || 'system',
        apply() {
            const isDark = this.theme === 'dark'
                || (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
            localStorage.setItem('theme', this.theme);
        },
        cycle() {
            this.theme = this.theme === 'light' ? 'dark' : (this.theme === 'dark' ? 'system' : 'light');
            this.apply();
        },
    }"
    x-init="apply()"
>
    <button
        type="button"
        @click="cycle()"
        class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
        :title="{
            light: 'Thème : clair (cliquer pour passer en sombre)',
            dark: 'Thème : sombre (cliquer pour passer en automatique)',
            system: 'Thème : automatique (cliquer pour passer en clair)',
        }[theme]"
    >
        <svg x-show="theme === 'light'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <svg x-show="theme === 'dark'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
        <svg x-show="theme === 'system'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    </button>
</div>
