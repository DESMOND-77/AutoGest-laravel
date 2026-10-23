const KEY = 'ui_prefs';
const THEMES = { aurore: 'light', ocean: 'light', sable: 'light', foret: 'dark', crepuscule: 'dark', graphite: 'dark' };
const ACCENTS = ['indigo', 'violet', 'rose', 'orange', 'amber', 'emerald', 'teal', 'sky'];
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

export default (Alpine) => {
    Alpine.store('prefs', {
        theme: 'auto',
        accent: null,
        density: 'comfortable',
        perf: 'auto',
        _t: null,

        init() {
            // La valeur servie par le serveur (base de données) prime sur le cookie.
            const server = document.documentElement.dataset.uiPrefs;
            let stored = {};
            try {
                stored = JSON.parse(localStorage.getItem(KEY) || '{}');
            } catch (_) {
                stored = {};
            }
            Object.assign(this, server ? JSON.parse(server) : stored);
            this.apply(false);
            matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.theme === 'auto') this.apply(false);
            });
        },

        set(key, value) {
            if (key === 'theme' && value !== 'auto' && !(value in THEMES)) return;
            if (key === 'accent' && value !== null && !ACCENTS.includes(value)) return;
            if (key === 'density' && !['comfortable', 'compact'].includes(value)) return;
            if (key === 'perf' && !['auto', 'lite', 'full'].includes(value)) return;
            this[key] = value;
            this.apply();
            this.persist();
            window.dispatchEvent(new CustomEvent('prefs:changed', { detail: this.snapshot() }));
        },

        snapshot() {
            return { theme: this.theme, accent: this.accent, density: this.density, perf: this.perf };
        },

        resolvedTheme() {
            return this.theme === 'auto'
                ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'crepuscule' : 'aurore')
                : this.theme;
        },

        isLite() {
            const c = navigator.connection || {};
            return this.perf === 'lite'
                || (this.perf === 'auto' && (c.saveData || navigator.deviceMemory <= 2 || navigator.hardwareConcurrency <= 2));
        },

        apply(persistCookie = false) {
            const d = document.documentElement;
            const t = this.resolvedTheme();
            d.dataset.theme = t;
            d.dataset.mode = THEMES[t] || 'light';
            if (this.accent) d.dataset.accent = this.accent; else delete d.dataset.accent;
            d.dataset.density = this.density === 'compact' ? 'compact' : 'comfortable';
            if (this.isLite()) d.dataset.perf = 'lite'; else delete d.dataset.perf;
            if (persistCookie) this.writeCookie();
        },

        writeCookie() {
            const json = JSON.stringify(this.snapshot());
            document.cookie = `${KEY}=${encodeURIComponent(json)}; Path=/; Max-Age=31536000; SameSite=Lax`
                + (location.protocol === 'https:' ? '; Secure' : '');
        },

        persist() {
            const json = JSON.stringify(this.snapshot());
            try { localStorage.setItem(KEY, json); } catch (_) {}
            this.writeCookie();
            clearTimeout(this._t);
            this._t = setTimeout(() => {
                if (!document.body.dataset.auth) return;
                fetch('/profile/appearance', {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                    },
                    body: json,
                }).catch(() => {});
            }, 600);
        },

        reset() {
            this.theme = 'auto';
            this.accent = null;
            this.density = 'comfortable';
            this.perf = 'auto';
            this.apply();
            this.persist();
            window.dispatchEvent(new CustomEvent('prefs:changed', { detail: this.snapshot() }));
        },
    });
};
