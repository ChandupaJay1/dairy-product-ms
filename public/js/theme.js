/**
 * PulseERP / Polonnaruwa Dairy Theme Manager
 * Completely aligned with main project (dairy-product-ms) theme mechanics:
 * - Uses class="dark" on <html>
 * - Persists in localStorage.theme ('dark' | 'light')
 * - Supports instant zero-flash switching
 * - Trilingual labels (Sinhala, English, Tamil)
 */

const ThemeManager = (() => {
    const THEME_KEY = 'theme';

    function init() {
        const saved = localStorage.getItem(THEME_KEY) || localStorage.getItem('polonnaruwa_theme') || 'dark';
        setTheme(saved, false);
    }

    function getTheme() {
        return localStorage.getItem(THEME_KEY) || 'dark';
    }

    function setTheme(theme, showToastNotification = true) {
        const isDark = theme === 'dark';
        
        // 1. Sync class "dark" for Tailwind compatibility (matching dairy-product-ms)
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // 2. Sync data-theme attribute
        document.documentElement.setAttribute('data-theme', theme);

        // 3. Persist to storage
        localStorage.setItem(THEME_KEY, theme);
        localStorage.setItem('polonnaruwa_theme', theme);

        // 4. Update Header Toggle UI
        const icon = document.getElementById('themeIcon');
        const label = document.getElementById('themeLabel');
        const btn = document.getElementById('btnThemeToggle');
        const lang = window.I18n ? I18n.getLanguage() : 'si';

        if (isDark) {
            if (icon) icon.textContent = '☀️';
            if (label) {
                const labels = {
                    en: 'Day Mode',
                    si: 'දිවා මාදිලිය',
                    ta: 'பகல் முறை'
                };
                label.textContent = labels[lang] || 'Day Mode';
            }
            if (btn) {
                btn.title = 'Switch to Light Mode';
            }
            if (showToastNotification && window.SecurityClient) {
                const toasts = {
                    en: '🌙 Switched to Dark Theme (PulseERP)',
                    si: '🌙 Dark Mode සක්‍රීය විය (PulseERP Theme)',
                    ta: '🌙 இருண்ட முறைக்கு மாற்றப்பட்டது'
                };
                SecurityClient.showToast(toasts[lang] || toasts.en, 'info', 2500);
            }
        } else {
            if (icon) icon.textContent = '🌙';
            if (label) {
                const labels = {
                    en: 'Night Mode',
                    si: 'රාත්‍රී මාදිලිය',
                    ta: 'இரவு முறை'
                };
                label.textContent = labels[lang] || 'Night Mode';
            }
            if (btn) {
                btn.title = 'Switch to Dark Mode';
            }
            if (showToastNotification && window.SecurityClient) {
                const toasts = {
                    en: '☀️ Switched to Light Theme (Slate-50)',
                    si: '☀️ Light Mode සක්‍රීය විය (Slate-50)',
                    ta: '☀️ ஒளி முறைக்கு மாற்றப்பட்டது'
                };
                SecurityClient.showToast(toasts[lang] || toasts.en, 'info', 2500);
            }
        }
    }

    function toggleTheme() {
        const next = getTheme() === 'dark' ? 'light' : 'dark';
        setTheme(next, true);
    }

    window.addEventListener('DOMContentLoaded', init);

    return {
        init,
        getTheme,
        setTheme,
        toggleTheme
    };
})();

window.ThemeManager = ThemeManager;

window.addEventListener('languageChanged', () => {
    ThemeManager.setTheme(ThemeManager.getTheme(), false);
});
