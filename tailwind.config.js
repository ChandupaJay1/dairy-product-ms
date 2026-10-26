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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            animation: {
                'float-slow': 'floatSlow 10s ease-in-out infinite',
                'float-reverse': 'floatReverse 12s ease-in-out infinite',
                'pulse-glow': 'pulseGlow 7s ease-in-out infinite',
                'fade-slide-up': 'fadeSlideUp 0.75s cubic-bezier(0.16, 1, 0.3, 1) both',
            },
            keyframes: {
                floatSlow: {
                    '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
                    '50%': { transform: 'translate(-15px, -20px) scale(1.06)' },
                },
                floatReverse: {
                    '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
                    '50%': { transform: 'translate(20px, 15px) scale(1.08)' },
                },
                pulseGlow: {
                    '0%, 100%': { opacity: '0.18', transform: 'scale(1)' },
                    '50%': { opacity: '0.32', transform: 'scale(1.1)' },
                },
                fadeSlideUp: {
                    '0%': { opacity: '0', transform: 'translateY(24px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
        },
    },

    plugins: [forms],
};
