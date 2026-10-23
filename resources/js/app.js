import Alpine from 'alpinejs';
import anchor from '@alpinejs/anchor';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import intersect from '@alpinejs/intersect';
import mask from '@alpinejs/mask';
import persist from '@alpinejs/persist';

import prefs from './alpine/prefs';
import palette from './alpine/palette';
import toasts from './alpine/toasts';

window.Alpine = Alpine;

Alpine.plugin(anchor);
Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.plugin(intersect);
Alpine.plugin(mask);
Alpine.plugin(persist);

Alpine.plugin(prefs);
Alpine.plugin(palette);
Alpine.plugin(toasts);

// État du shell (repli de la sidebar mémorisé, tiroir mobile)
Alpine.store('shell', {
    collapsed: false,
    mobileOpen: false,
    init() {
        try { this.collapsed = localStorage.getItem('agb:sidebar') === '1'; } catch (_) {}
    },
    toggle() {
        this.collapsed = !this.collapsed;
        try { localStorage.setItem('agb:sidebar', this.collapsed ? '1' : '0'); } catch (_) {}
    },
    closeMobile() { this.mobileOpen = false; },
});

// Bannière « navigateur trop ancien » (§5.2) - test de fonctionnalité, pas de polyfill.
Alpine.data('browserCheck', () => ({
    outdated: false,
    init() {
        try {
            this.outdated = !(window.CSS && CSS.supports('color', 'color-mix(in srgb, red, blue)'));
        } catch (_) {
            this.outdated = true;
        }
    },
}));

Alpine.start();
