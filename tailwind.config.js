import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
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
