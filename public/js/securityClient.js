/**
 * Security Client & Authenticated API Fetch Utility
 * 
 * Manages JWT tokens, handles XSS escaping, adds security headers,
 * and provides safe toast alerts for user feedback.
 */

const SecurityClient = (() => {
    const TOKEN_KEY = 'polonnaruwa_dairy_jwt_token';
    const USER_KEY = 'polonnaruwa_dairy_user_session';

    function setAuthSession(token, user) {
        localStorage.setItem(TOKEN_KEY, token);
        localStorage.setItem(USER_KEY, JSON.stringify(user));
    }

    function getToken() {
        return localStorage.getItem(TOKEN_KEY);
    }

    function getCurrentUser() {
        try {
            const raw = localStorage.getItem(USER_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    }

    function clearSession() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
    }

    /**
     * Authenticated fetch wrapper that attaches JWT bearer token and Content-Type.
     */
    async function secureFetch(url, options = {}) {
        const token = getToken();
        const headers = {
            'Content-Type': 'application/json',
            ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
            ...(options.headers || {})
        };

        try {
            const response = await fetch(url, { ...options, headers });
            const data = await response.json();
            return data;
        } catch (err) {
            console.warn('Network or server error during fetch:', err.message);
            // Check if client is offline
            if (!navigator.onLine) {
                return {
                    success: false,
                    offline: true,
                    error: 'Device is offline. Rural Polonnaruwa connectivity mode active.'
                };
            }
            return { success: false, error: err.message };
        }
    }

    /**
     * Prevents Cross-Site Scripting (XSS) when rendering dynamic strings into HTML.
     */
    function escapeHtml(unsafeText) {
        if (unsafeText === null || unsafeText === undefined) return '';
        return String(unsafeText)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Formats numeric amounts to Sri Lankan Rupee currency format (e.g. Rs. 15,250.00).
     */
    function formatCurrency(amount) {
        const num = Number(amount) || 0;
        return 'Rs. ' + num.toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /**
     * Shows a non-intrusive, modern toast notification on screen.
     */
    function showToast(message, type = 'info', duration = 3500) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type} animate-slide-in`;

        let icon = 'ℹ️';
        if (type === 'success') icon = '✅';
        if (type === 'error') icon = '⚠️';
        if (type === 'warning') icon = '🔔';
        if (type === 'emergency') icon = '🚨';

        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <div class="toast-content">${escapeHtml(message)}</div>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('animate-fade-out');
            setTimeout(() => toast.remove(), 400);
        }, duration);
    }

    /**
     * Generates a stunning, dairy-themed loading animation card with ripple ring & skeleton preview.
     */
    function getLoadingHtml(title, subtitle, icon = '🥛') {
        const isSi = window.I18n && window.I18n.getLanguage() === 'si';
        const finalTitle = title || (isSi ? 'දත්ත පූරණය වෙමින් පවතී...' : 'Loading operational data...');
        const finalSubtitle = subtitle || (isSi ? 'කරුණාකර මොහොතක් රැඳී සිටින්න...' : 'Please wait while records are synchronizing...');

        return `
            <div class="dairy-loader-wrapper animate-fade-in">
                <div class="dairy-loader-card">
                    <div class="dairy-spinner-stage">
                        <div class="dairy-ripple-wave"></div>
                        <div class="dairy-ripple-wave delay-1"></div>
                        <div class="dairy-spin-ring"></div>
                        <div class="dairy-floating-icon">${icon}</div>
                    </div>

                    <div class="dairy-loader-content">
                        <h3 class="dairy-loader-title">
                            ${finalTitle}
                            <span class="animated-loading-dots">
                                <span>.</span><span>.</span><span>.</span>
                            </span>
                        </h3>
                        <p class="dairy-loader-subtitle">${finalSubtitle}</p>
                    </div>

                    <!-- Modern Shimmer Skeleton Mockup -->
                    <div class="dairy-skeleton-preview">
                        <div class="skeleton-shimmer-bar header-bar"></div>
                        <div class="skeleton-shimmer-grid">
                            <div class="skeleton-shimmer-card"></div>
                            <div class="skeleton-shimmer-card"></div>
                            <div class="skeleton-shimmer-card"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Renders the loading animation into a target container element or ID.
     */
    function renderLoading(containerOrId, title, subtitle, icon) {
        const el = typeof containerOrId === 'string' ? document.getElementById(containerOrId) : containerOrId;
        if (el) {
            el.innerHTML = getLoadingHtml(title, subtitle, icon);
        }
    }

    /**
     * Inline mini spinner for buttons or table cells.
     */
    function getMiniLoadingHtml(text) {
        const isSi = window.I18n && window.I18n.getLanguage() === 'si';
        const finalText = text || (isSi ? 'පූරණය වෙමින් පවතී...' : 'Loading...');
        return `
            <div class="dairy-mini-loader">
                <span class="dairy-mini-spinner"></span>
                <span>${escapeHtml(finalText)}</span>
            </div>
        `;
    }

    return {
        setAuthSession,
        getToken,
        getCurrentUser,
        clearSession,
        secureFetch,
        escapeHtml,
        formatCurrency,
        showToast,
        getLoadingHtml,
        renderLoading,
        getMiniLoadingHtml
    };
})();

window.SecurityClient = SecurityClient;
