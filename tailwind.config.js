import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * The palette below points at the CSS variables in
 * resources/css/global.css, so Tailwind classes and the
 * hand-written CSS always stay on the same colors.
 * Edit global.css — not this file — to reskin the app.
 */

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
            },

            colors: {
                forest: {
                    DEFAULT: 'var(--ll-forest)',
                    2: 'var(--ll-forest-2)',
                    3: 'var(--ll-forest-3)',
                    4: 'var(--ll-forest-4)',
                    5: 'var(--ll-forest-5)',
                },
                mint: {
                    DEFAULT: 'var(--ll-mint)',
                    dark: 'var(--ll-mint-dark)',
                    darker: 'var(--ll-mint-darker)',
                    deep: 'var(--ll-mint-deep)',
                    tint: 'var(--ll-mint-tint)',
                },
                surface: {
                    DEFAULT: 'var(--ll-surface)',
                    2: 'var(--ll-surface-2)',
                },
                canvas: 'var(--ll-bg)',
                ink: {
                    DEFAULT: 'var(--ll-text)',
                    muted: 'var(--ll-muted)',
                    soft: 'var(--ll-muted-2)',
                },
                hairline: {
                    DEFAULT: 'var(--ll-border)',
                    light: 'var(--ll-border-light)',
                    strong: 'var(--ll-border-strong)',
                },
                danger: 'var(--ll-danger)',
            },

            borderRadius: {
                ll: 'var(--ll-radius)',
                'll-lg': 'var(--ll-radius-lg)',
            },

            boxShadow: {
                ll: 'var(--ll-shadow)',
                'll-md': 'var(--ll-shadow-md)',
                'll-lg': 'var(--ll-shadow-lg)',
            },
        },
    },

    plugins: [forms],
};
