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
                sans: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
                display: ['"Rajdhani"', '"Chakra Petch"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                gms: {
                    bg: '#080B12',
                    surface: '#101522',
                    card: '#151D2B',
                    cyan: '#00F0FF',
                    purple: '#8B5CF6',
                    amber: '#F59E0B',
                    text: '#F5F7FA',
                    muted: '#A7B0C0',
                    border: '#1E293B',
                },
            },
        },
    },

    plugins: [forms],
};
