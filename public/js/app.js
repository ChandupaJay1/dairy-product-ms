/**
 * Main Application Router & Orchestrator
 * 
 * Manages view switching, PWA install prompts, audio siren synthesizer,
 * device frame emulation, and offline event listeners.
 */

const App = (() => {
    let currentRole = 'admin'; // 'admin', 'driver', 'sales_rep', 'sos_hub'
    let deferredPrompt = null;
    let audioCtx = null;

    function getDefaultViewForRole() {
        if (window.CURRENT_USER && window.CURRENT_USER.role) {
            switch (window.CURRENT_USER.role) {
                case 'driver': return 'driver';
                case 'sales_rep': return 'sales_rep';
                case 'cashier': return 'settlement';
                case 'admin': return 'admin';
            }
        }
        return 'sales_rep';
    }

    function isViewPermittedForUser(viewName) {
        if (!window.CURRENT_USER || !window.CURRENT_USER.role) return true;
        const role = window.CURRENT_USER.role;
        if (role === 'admin') return true;
        if (role === 'driver') return viewName === 'driver';
        if (role === 'sales_rep') return viewName === 'sales_rep';
        if (role === 'cashier') return viewName === 'settlement' || viewName === 'reports';
        return false;
    }

    function init() {
        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').then((reg) => {
                console.log('[PWA] Service Worker registered:', reg.scope);
            }).catch((err) => {
                console.warn('[PWA] Service Worker registration failed:', err);
            });
        }

        // Standalone Mode Detection (checks if already running as an installed PWA)
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const installBtn = document.getElementById('btnInstallPwa');
        if (isStandalone && installBtn) {
            installBtn.style.display = 'none';
        }

        // Catch PWA Install Prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const btn = document.getElementById('btnInstallPwa');
            if (btn && !isStandalone) btn.style.display = 'inline-flex';
            const directBox = document.getElementById('pwaPromptDirectBtnBox');
            if (directBox) directBox.style.display = 'block';
        });

        // App Installed Event
        window.addEventListener('appinstalled', () => {
            console.log('[PWA] Application successfully installed on device home screen.');
            deferredPrompt = null;
            const btn = document.getElementById('btnInstallPwa');
            if (btn) btn.style.display = 'none';
            const modal = document.getElementById('pwaInstallModal');
            if (modal) modal.style.display = 'none';
            if (window.SecurityClient) {
                SecurityClient.showToast('🎉 Polonnaruwa Dairy App successfully added to your Home Screen!', 'success', 5000);
            }
        });

        // Network Status & Offline Auto-Sync Lifecycle
        function updateNetworkIndicators(isOnline) {
            const netBadge = document.getElementById('networkStatusBadge');
            if (netBadge) {
                if (isOnline) {
                    netBadge.textContent = '📶 Online';
                    netBadge.className = 'status-badge-network status-badge-online';
                } else {
                    netBadge.textContent = '⚠️ Offline (Polonnaruwa)';
                    netBadge.className = 'status-badge-network status-badge-offline';
                }
            }
            OfflineStorage.updateOfflineBadge();
        }

        window.addEventListener('online', () => {
            document.body.classList.remove('is-offline');
            updateNetworkIndicators(true);
            SecurityClient.showToast('📶 Cellular Network Restored! Auto-syncing pending offline orders to MySQL Database...', 'success', 5000);
            OfflineStorage.syncQueue();
        });

        window.addEventListener('offline', () => {
            document.body.classList.add('is-offline');
            updateNetworkIndicators(false);
            SecurityClient.showToast('⚠️ Rural Polonnaruwa Offline Mode: Orders will be queued and auto-synced upon reconnect.', 'warning', 6000);
        });

        // Initial badge and network status check on startup
        updateNetworkIndicators(navigator.onLine);
        if (navigator.onLine && OfflineStorage.getPendingQueue().length > 0) {
            setTimeout(() => OfflineStorage.syncQueue(), 1500);
        }

        // Periodic background sync heartbeat (every 30 seconds)
        setInterval(() => {
            if (navigator.onLine && OfflineStorage.getPendingQueue().length > 0) {
                OfflineStorage.syncQueue();
            }
        }, 30000);

        // Universal click event delegation for role buttons
        document.addEventListener('click', (e) => {
            const roleBtn = e.target.closest('.role-nav-btn');
            if (roleBtn && roleBtn.dataset.view) {
                switchView(roleBtn.dataset.view);
            }
        });

        // Initialize view based on hash or role-based default
        let initialView = window.location.hash.replace('#', '');
        if (!initialView || !isViewPermittedForUser(initialView)) {
            initialView = getDefaultViewForRole();
        }
        switchView(initialView);

        // Smoothly dismiss full page preloader
        setTimeout(dismissPreloader, 400);
    }

    function dismissPreloader() {
        const preloader = document.getElementById('appPreloader');
        if (preloader) {
            preloader.style.opacity = '0';
            preloader.style.pointerEvents = 'none';
            setTimeout(() => {
                if (preloader.parentNode) preloader.remove();
            }, 450);
        }
    }

    function toggleMobileDrawer(open) {
        const drawer = document.getElementById('mobileDrawer');
        const overlay = document.getElementById('mobileDrawerOverlay');
        if (drawer && overlay) {
            if (open) {
                drawer.classList.add('open');
                overlay.classList.add('open');
            } else {
                drawer.classList.remove('open');
                overlay.classList.remove('open');
            }
        }
    }

    function switchView(viewName) {
        // Enforce role permission
        if (!isViewPermittedForUser(viewName)) {
            const fallback = getDefaultViewForRole();
            if (viewName !== fallback) {
                console.warn(`[RBAC] Access to '${viewName}' restricted for role '${window.CURRENT_USER?.role}'. Directing to '${fallback}'.`);
                if (window.SecurityClient) {
                    SecurityClient.showToast(`🔒 Access restricted to your assigned role view.`, 'warning', 3500);
                }
                return switchView(fallback);
            }
        }

        currentRole = viewName;
        window.location.hash = viewName;

        // Close mobile drawer on navigation
        toggleMobileDrawer(false);

        // Update nav button active states across sidebar and drawer
        document.querySelectorAll('.role-nav-btn, .sidebar-nav-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === viewName);
        });

        // Hide all views
        document.querySelectorAll('.view-panel').forEach(panel => {
            panel.classList.remove('active');
        });

        // Show selected view
        const targetPanel = document.getElementById(`view-${viewName}`);
        if (targetPanel) {
            targetPanel.classList.add('active');
        }

        // Update breadcrumb text
        const breadcrumbEl = document.getElementById('currentViewBreadcrumb');
        if (breadcrumbEl) {
            const labels = {
                sales_rep: I18n.t('nav_rep', 'Sales Rep'),
                driver: I18n.t('nav_driver', 'Driver Mobile'),
                settlement: I18n.t('nav_settlement', 'Day Settlement'),
                reports: I18n.t('nav_reports', 'Reports'),
                admin: I18n.t('nav_admin', 'Office Admin'),
                audit: I18n.t('nav_audit', 'Activity Logs')
            };
            breadcrumbEl.textContent = labels[viewName] || viewName;
        }

        // Load data for specific view
        if (viewName === 'admin') {
            AdminDashboard.loadAllAdminData();
        } else if (viewName === 'driver') {
            DriverView.loadDriverData();
        } else if (viewName === 'sales_rep') {
            SalesRepWizard.initWizard();
        } else if (viewName === 'settlement') {
            SettlementView.loadData();
        } else if (viewName === 'reports') {
            ReportsView.loadData();
        } else if (viewName === 'audit') {
            AdminDashboard.loadAllAdminData();
        }
    }

    function toggleMobileFrame() {
        // The layout is now 100% fluidly responsive across all devices
    }

    /**
     * Synthesizes an emergency siren audio sound via HTML5 Web Audio API
     * (Zero external audio file dependency, guaranteed to work anywhere).
     */
    function playSirenAudio() {
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            osc.type = 'sawtooth';
            const now = audioCtx.currentTime;

            // Siren pitch sweep (low to high back to low)
            osc.frequency.setValueAtTime(650, now);
            osc.frequency.exponentialRampToValueAtTime(980, now + 0.3);
            osc.frequency.exponentialRampToValueAtTime(650, now + 0.6);
            osc.frequency.exponentialRampToValueAtTime(980, now + 0.9);
            osc.frequency.exponentialRampToValueAtTime(650, now + 1.2);

            gain.gain.setValueAtTime(0.3, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 1.4);

            osc.start(now);
            osc.stop(now + 1.4);
        } catch (e) {
            console.warn('Audio synthesis not permitted without user gesture:', e);
        }
    }

    function triggerSosAlarm(sosAlert) {
        SecurityClient.showToast(`🚨 HIGH PRIORITY: SOS Incident on ${sosAlert.vehicle_number} (${sosAlert.location_landmark})!`, 'emergency', 7000);
    }

    function installPwa() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choice) => {
                if (choice.outcome === 'accepted') {
                    console.log('[PWA] User accepted installation prompt');
                    const btn = document.getElementById('btnInstallPwa');
                    if (btn) btn.style.display = 'none';
                }
                deferredPrompt = null;
            });
        } else {
            // Open user-friendly Add to Home Screen step-by-step guide
            const modal = document.getElementById('pwaInstallModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }
    }

    function triggerDirectInstall() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choice) => {
                if (choice.outcome === 'accepted') {
                    const modal = document.getElementById('pwaInstallModal');
                    if (modal) modal.style.display = 'none';
                    const btn = document.getElementById('btnInstallPwa');
                    if (btn) btn.style.display = 'none';
                }
                deferredPrompt = null;
            });
        }
    }

    function refreshCurrentView() {
        switchView(currentRole);
    }

    function openTransferHandshakeModal(sosId, alertObj = null) {
        const modal = document.getElementById('transferHandshakeModal');
        if (!modal) return;
        modal.dataset.sosId = sosId;

        const contextCard = document.getElementById('handshakeContextCard');
        if (alertObj) {
            if (contextCard) contextCard.style.display = 'block';
            const ticketEl = document.getElementById('handshakeTicketDisplay');
            const riskEl = document.getElementById('handshakeRiskCupsDisplay');
            if (ticketEl) ticketEl.textContent = alertObj.sos_ticket_number || ('SOS #' + sosId);
            if (riskEl) riskEl.textContent = alertObj.remaining_stock_at_incident || 1320;
            const totalCupsInput = document.getElementById('transferTotalCups');
            if (totalCupsInput) totalCupsInput.value = alertObj.remaining_stock_at_incident || 1300;
        } else {
            if (contextCard) contextCard.style.display = 'none';
        }

        modal.style.display = 'flex';
        I18n.applyTranslations(modal);
    }

    function closeTransferHandshakeModal() {
        const modal = document.getElementById('transferHandshakeModal');
        if (modal) modal.style.display = 'none';
    }

    async function submitTransferHandshake() {
        const modal = document.getElementById('transferHandshakeModal');
        const sosId = modal ? modal.dataset.sosId : null;
        if (!sosId) {
            SecurityClient.showToast('No active SOS ticket found for transfer.', 'error');
            return;
        }

        const goodStock = parseInt(document.getElementById('transferTotalCups').value) || 0;
        const spoiledStock = parseInt(document.getElementById('transferSpoiledCups').value) || 0;
        const notes = document.getElementById('transferHandshakeNotes').value || 'Field verified count completed.';

        const payload = {
            transferredGoodStock: goodStock,
            spoiledUnusableStock: spoiledStock,
            handshakeNotes: notes
        };

        const res = await SecurityClient.secureFetch(`/api/sos/${sosId}/transfer-stock`, {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (res && res.success) {
            closeTransferHandshakeModal();

            // Clear local cached SOS
            OfflineStorage.cacheOperationalData(OfflineStorage.CACHE_ADMIN_KEY, null);

            // Trigger celebration alert
            showSosResolvedAlert({
                ticketCode: res.ticketCode || ('SOS #' + sosId),
                transferredCups: res.transferredCups || goodStock,
                spoiledCups: res.spoiledCups || spoiledStock,
                rescueManifestNo: res.rescueManifestNo
            });

            // Refresh active views
            if (window.AdminDashboard && typeof window.AdminDashboard.loadAllAdminData === 'function') {
                window.AdminDashboard.loadAllAdminData();
            }
            if (window.DriverView && typeof window.DriverView.loadDriverData === 'function') {
                window.DriverView.loadDriverData();
            }

            window.dispatchEvent(new CustomEvent('sosResolved', { detail: res }));
        } else {
            SecurityClient.showToast(res.error || 'Transfer failed', 'error');
        }
    }

    function showSosResolvedAlert(details) {
        const title = I18n.t('sos_resolved_alert_title', '🎉 SOS Emergency Ticket Successfully Resolved!');
        const msg = `${title}\n✅ ${details.transferredCups} cups transferred to Rescue Manifest (${details.rescueManifestNo}).\nTicket ${details.ticketCode} marked as RESOLVED.`;
        SecurityClient.showToast(msg, 'success', 8000);
    }

    return {
        init,
        switchView,
        toggleMobileDrawer,
        toggleMobileFrame,
        playSirenAudio,
        triggerSosAlarm,
        installPwa,
        triggerDirectInstall,
        refreshCurrentView,
        dismissPreloader,
        openTransferHandshakeModal,
        closeTransferHandshakeModal,
        submitTransferHandshake,
        showSosResolvedAlert
    };
})();

window.App = App;
if (document.readyState === 'loading') {
    window.addEventListener('DOMContentLoaded', () => App.init());
} else {
    App.init();
}
window.addEventListener('load', () => setTimeout(() => App.dismissPreloader(), 600));
