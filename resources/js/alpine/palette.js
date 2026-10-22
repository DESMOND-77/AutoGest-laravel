/**
 * Palette de commandes : navigation clavier, filtrage local et chargement
 * paresseux des résultats serveur (GET /search) désactivé par défaut tant que
 * l'endpoint n'est pas livré (§7.5, phase 2).
 */
export default (Alpine) => {
    Alpine.data('commandPalette', (config = {}) => ({
        open: false,
        query: '',
        index: 0,
        remote: [],
        remoteLoading: false,
        remoteEnabled: config.remote === true,
        recent: [],
        get items() {
            const local = (config.items || []).filter((item) => {
                if (!this.query) return true;
                return (item.label + ' ' + (item.group || '') + ' ' + (item.keywords || ''))
                    .toLowerCase()
                    .includes(this.query.toLowerCase());
            });
            const remoteItems = this.remote;
            return [...local, ...remoteItems];
        },
        get hasResults() { return this.items.length > 0; },
        show() {
            this.open = true;
            this.query = '';
            this.index = 0;
            this.$nextTick(() => this.$refs.input?.focus());
        },
        close() {
            this.open = false;
            this.$refs.trigger?.focus();
        },
        move(step) {
            if (!this.items.length) return;
            this.index = (this.index + step + this.items.length) % this.items.length;
            this.$nextTick(() => {
                const el = this.$refs.list?.querySelector('[data-active="true"]');
                el?.scrollIntoView({ block: 'nearest' });
            });
        },
        run(item) {
            if (!item) return;
            if (item.url) {
                window.location.href = item.url;
                return;
            }
            if (item.action) {
                window.dispatchEvent(new CustomEvent('palette:action', { detail: item }));
            }
            this.close();
        },
        async search() {
            if (!this.remoteEnabled || this.query.length < 2) { this.remote = []; return; }
            this.remoteLoading = true;
            clearTimeout(this._debounce);
            this._debounce = setTimeout(async () => {
                try {
                    const res = await fetch(`/search?q=${encodeURIComponent(this.query)}`, {
                        headers: { Accept: 'application/json' },
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.remote = (data.results || []).map((r) => ({
                            label: r.label,
                            group: r.group,
                            url: r.url,
                            icon: r.icon,
                        }));
                    }
                } catch (_) {
                    this.remote = [];
                } finally {
                    this.remoteLoading = false;
                }
            }, 200);
        },
    }));
};
