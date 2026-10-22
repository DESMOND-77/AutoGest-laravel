export default (Alpine) => {
    Alpine.store('toasts', {
        items: [],
        seq: 0,

        push(message, tone = 'info', options = {}) {
            if (!message) return;
            const id = ++this.seq;
            const sticky = options.sticky ?? tone === 'danger';
            this.items.push({ id, message, tone, sticky });
            if (!sticky) {
                setTimeout(() => this.dismiss(id), options.duration ?? 6000);
            }
            return id;
        },

        dismiss(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },

        pause(id) {
            // Le décompte est volontairement simple : la pause au survol
            // empêche la disparition en annulant le minuteur via l'élément.
            const item = this.items.find((t) => t.id === id);
            if (item) item.paused = true;
        },

        toneClass(tone) {
            return {
                success: 'border-success/40 bg-success-soft text-success',
                warning: 'border-warning/40 bg-warning-soft text-warning',
                danger: 'border-danger/40 bg-danger-soft text-danger',
                info: 'border-accent-line bg-accent-soft text-ink',
            }[tone] || 'border-line bg-solid text-ink';
        },
    });
};
