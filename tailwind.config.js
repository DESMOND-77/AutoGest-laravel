import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                // Condensed road-sign lettering — landing page display type only.
                display: ['"Barlow Condensed"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                ink: '#0B1220',
                route: {
                    DEFAULT: '#1E40AF',
                    50: '#EEF3FF',
                    100: '#DCE6FF',
                    700: '#1B3586',
                },
                signal: {
                    // DEFAULT is the bright brand orange from the logo accent stripe —
                    // used for decorative strokes, icons and text sitting on the dark
                    // "asphalt" sections, where its contrast is high. 600/700 are for
                    // any white-text-on-solid-fill use (buttons, badges), where the
                    // bright value alone would fail WCAG AA (~2.8:1) — see route.
                    DEFAULT: '#F2790A',
                    600: '#B85500',
                    700: '#8F4200',
                },
                cream: '#FAF8F4',
                paper: '#FFFFFF',
                slate: {
                    DEFAULT: '#57607A',
                    light: '#8A93A6',
                },
                line: '#E6E2D8',
                asphalt: {
                    DEFAULT: '#0E1526',
                    2: '#151F38',
                },

                // Soft Neumorphic tokens for the authenticated app shell —
                // see resources/css/app.css for the underlying CSS custom
                // properties (light + dark values). Kept separate from the
                // landing-page palette above on purpose.
                background: 'rgb(var(--color-background) / <alpha-value>)',
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                'surface-elevated': 'rgb(var(--color-surface-elevated) / <alpha-value>)',
                'surface-inset': 'rgb(var(--color-surface-inset) / <alpha-value>)',
                border: 'rgb(var(--color-border) / <alpha-value>)',
                content: {
                    DEFAULT: 'rgb(var(--color-content) / <alpha-value>)',
                    secondary: 'rgb(var(--color-content-secondary) / <alpha-value>)',
                    muted: 'rgb(var(--color-content-muted) / <alpha-value>)',
                },
                primary: {
                    DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
                    content: 'rgb(var(--color-primary-content) / <alpha-value>)',
                },
                success: 'rgb(var(--color-success) / <alpha-value>)',
                warning: 'rgb(var(--color-warning) / <alpha-value>)',
                danger: 'rgb(var(--color-danger) / <alpha-value>)',
                info: 'rgb(var(--color-info) / <alpha-value>)',
            },
            borderRadius: {
                'ui-sm': 'var(--radius-sm)',
                'ui-md': 'var(--radius-md)',
                'ui-lg': 'var(--radius-lg)',
                'ui-xl': 'var(--radius-xl)',
            },
            boxShadow: {
                'soft-sm': 'var(--shadow-soft-sm)',
                soft: 'var(--shadow-soft)',
                'soft-hover': 'var(--shadow-soft-hover)',
                inset: 'var(--shadow-inset)',
                'inset-focus': 'var(--shadow-inset-focus)',
            },
            keyframes: {
                'dash-drift': {
                    to: { 'stroke-dashoffset': '-48' },
                },
                'fade-up': {
                    from: { opacity: '0', transform: 'translateY(0.75rem)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'dash-drift': 'dash-drift 3s linear infinite',
                'fade-up': 'fade-up 0.6s ease-out both',
            },
        },
    },

    plugins: [forms],
};
