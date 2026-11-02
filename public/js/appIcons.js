/**
 * PulseERP Intelligence Suite
 * Crisp SVG Vector Icon Library (Lucide/Tailwind Style)
 * 100% Offline-Safe, High-DPI Vector Graphics
 */
(function(window) {
    'use strict';

    const ICONS = {
        // Navigation & Modules
        'building': '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M8 10h.01"></path><path d="M16 10h.01"></path><path d="M8 14h.01"></path><path d="M16 14h.01"></path>',
        'truck': '<rect x="1" y="3" width="15" height="13" rx="1"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle>',
        'dashboard': '<rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18"></path><path d="M9 21V9"></path>',
        'clipboard-check': '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="m9 14 2 2 4-4"></path>',
        'package': '<path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path>',
        'radar': '<circle cx="12" cy="12" r="10"></circle><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36z"></path>',
        'users-truck': '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path><path d="M15 18H9"></path><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"></path><circle cx="17" cy="18" r="2"></circle><circle cx="7" cy="18" r="2"></circle>',
        'credit-card': '<rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line>',
        'shield-lock': '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 11v4"></path><circle cx="12" cy="9" r="1"></circle>',
        'smartphone': '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"></rect><path d="M12 18h.01"></path>',
        'receipt': '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path><path d="M16 8h-8"></path><path d="M16 12h-8"></path><path d="M12 16h-4"></path>',
        'bar-chart': '<line x1="12" x2="12" y1="20" y2="10"></line><line x1="18" x2="18" y1="20" y2="4"></line><line x1="6" x2="6" y1="20" y2="16"></line>',
        'crown': '<path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"></path>',
        'log-out': '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" x2="9" y1="12" y2="12"></line>',

        // Operations & KPIs
        'milk': '<path d="M8 2h8v2H8z"></path><path d="M9 4v2l-2 3v11a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9l-2-3V4"></path><path d="M7 14h10"></path>',
        'check-circle': '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
        'transit-truck': '<path d="M10 17h4V5H2v12h3"></path><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle>',
        'siren': '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
        'coins': '<circle cx="8" cy="8" r="6"></circle><path d="M18.09 10.37A6 6 0 1 1 10.34 18"></path><path d="M7 6h1v4"></path><path d="m16.71 13.88.7.71-2.82 2.82"></path>',
        'clipboard-list': '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M12 11h4"></path><path d="M12 16h4"></path><path d="M8 11h.01"></path><path d="M8 16h.01"></path>',

        // Actions & Buttons
        'refresh': '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path><path d="M21 3v5h-5"></path><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path><path d="M8 16H3v5"></path>',
        'plus-circle': '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line>',
        'zap': '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>',
        'shield-check': '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path>',
        'store': '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"></path><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"></path><path d="M2 7h20"></path><path d="M22 7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2"></path>',
        'file-invoice': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
        'landmark': '<line x1="3" x2="21" y1="22" y2="22"></line><line x1="6" x2="6" y1="18" y2="11"></line><line x1="10" x2="10" y1="18" y2="11"></line><line x1="14" x2="14" y1="18" y2="11"></line><line x1="18" x2="18" y1="18" y2="11"></line><polygon points="12 2 20 7 4 7"></polygon>',
        'handshake': '<path d="m11 17 2 2a1 1 0 0 0 1.42 0l6.17-6.17a3 3 0 0 0 0-4.24l-.88-.88a3 3 0 0 0-4.24 0L13.1 10.1"></path><path d="m13 14 2-2"></path><path d="M10.9 9.9 8.53 7.53a3 3 0 0 0-4.24 0l-.88.88a3 3 0 0 0 0 4.24L9.58 18.8a1 1 0 0 0 1.42 0l2-2"></path>',
        'printer': '<polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect>',
        'sun': '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>',
        'moon': '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>',
        'globe': '<circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>',
        'wifi': '<path d="M5 12.55a11 11 0 0 1 14.08 0"></path><path d="M1.42 9a16 16 0 0 1 21.16 0"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line>',
        'cash': '<rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle><path d="M6 12h.01M18 12h.01"></path>',
        'filter': '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>',
        'download': '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>'
    };

    const AppIcons = {
        /**
         * Get SVG markup for a given icon name
         * @param {string} name - Icon identifier
         * @param {Object} [options] - Options (size, className, strokeWidth, style)
         * @returns {string} SVG HTML string
         */
        get(name, options = {}) {
            const body = ICONS[name] || ICONS['dashboard'];
            const size = options.size || 18;
            const cls = options.className ? `class="svg-icon ${options.className}"` : 'class="svg-icon"';
            const strokeWidth = options.strokeWidth || 2;
            const style = options.style ? `style="${options.style}"` : '';

            return `<svg viewBox="0 0 24 24" width="${size}" height="${size}" fill="none" stroke="currentColor" stroke-width="${strokeWidth}" stroke-linecap="round" stroke-linejoin="round" ${cls} ${style} aria-hidden="true">${body}</svg>`;
        },

        /**
         * Convenience helper to return just the SVG inner paths
         */
        paths(name) {
            return ICONS[name] || '';
        }
    };

    window.AppIcons = AppIcons;
})(window);
