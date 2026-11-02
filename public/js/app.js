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
                const adminSection = roleBtn.dataset.adminSection || (roleBtn.dataset.view === 'admin' ? 'adminTopSection' : null);
                switchView(roleBtn.dataset.view, adminSection);
            }
        });

        // Start 24/7 Global Fleet SOS Telemetry Monitor (Alerts Admin & Front Office across all tabs)
        startGlobalSosMonitor();

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

    function switchView(viewName, targetAdminSection) {
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

        // Manage module accordion expansion states
        if (viewName === 'admin' || viewName === 'audit') {
            const adminGroup = document.getElementById('moduleGroupAdmin');
            const adminChevron = document.getElementById('adminChevron');
            if (adminGroup && adminGroup.classList.contains('collapsed')) {
                adminGroup.classList.remove('collapsed');
                if (adminChevron) adminChevron.textContent = '▾';
            }
            const adminDrawerGroup = document.getElementById('drawerModuleGroupAdmin');
            const adminDrawerChevron = document.getElementById('drawerAdminChevron');
            if (adminDrawerGroup && adminDrawerGroup.classList.contains('collapsed')) {
                adminDrawerGroup.classList.remove('collapsed');
                if (adminDrawerChevron) adminDrawerChevron.textContent = '▾';
            }
        } else {
            // Ensure Distribution module accordion is expanded on active selection
            const distGroup = document.getElementById('moduleGroupDistribution');
            const distChevron = document.getElementById('distChevron');
            if (distGroup && distGroup.classList.contains('collapsed')) {
                distGroup.classList.remove('collapsed');
                if (distChevron) distChevron.textContent = '▾';
            }
            const distDrawerGroup = document.getElementById('drawerModuleGroupDistribution');
            const distDrawerChevron = document.getElementById('distDrawerChevron');
            if (distDrawerGroup && distDrawerGroup.classList.contains('collapsed')) {
                distDrawerGroup.classList.remove('collapsed');
                if (distDrawerChevron) distDrawerChevron.textContent = '▾';
            }
        }

        // Active admin section resolution
        const activeSection = targetAdminSection || (viewName === 'admin' ? 'adminTopSection' : null);

        // Update nav button active states across sidebar and drawer
        document.querySelectorAll('.role-nav-btn, .sidebar-nav-btn').forEach(btn => {
            if (viewName === 'admin' && btn.dataset.adminSection) {
                btn.classList.toggle('active', btn.dataset.adminSection === activeSection);
            } else if (viewName === 'admin' && !btn.dataset.adminSection) {
                btn.classList.toggle('active', btn.dataset.view === 'admin' && !activeSection);
            } else {
                btn.classList.toggle('active', btn.dataset.view === viewName);
            }
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

            const adminSubLabels = {
                adminTopSection: I18n.t('nav_admin_kpis', 'Command & KPIs'),
                approvalCenterSection: I18n.t('nav_admin_approvals', 'Approvals Center'),
                dailyDispatchesSection: I18n.t('nav_admin_dispatches', 'Daily Dispatches'),
                fleetRadarSection: I18n.t('nav_admin_radar', 'Fleet GPS Radar'),
                fleetStaffManagementSection: I18n.t('nav_admin_fleet', 'Fleet & Staff Control'),
                creditRiskSection: I18n.t('nav_admin_credit', 'Credit & Shop Risk')
            };

            if (viewName === 'admin' && activeSection && adminSubLabels[activeSection]) {
                breadcrumbEl.textContent = `${labels.admin} › ${adminSubLabels[activeSection]}`;
            } else {
                breadcrumbEl.textContent = labels[viewName] || viewName;
            }
        }

        // Load data for specific view
        if (viewName === 'admin') {
            AdminDashboard.loadAllAdminData();
            if (activeSection) {
                setTimeout(() => {
                    if (window.AdminDashboard && typeof window.AdminDashboard.scrollToSection === 'function') {
                        window.AdminDashboard.scrollToSection(activeSection);
                    }
                }, 160);
            }
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

    function toggleAdminModule() {
        const group = document.getElementById('moduleGroupAdmin');
        const chevron = document.getElementById('adminChevron');
        if (group) {
            group.classList.toggle('collapsed');
            if (chevron) {
                chevron.textContent = group.classList.contains('collapsed') ? '▸' : '▾';
            }
        }
    }

    function toggleAdminDrawerModule() {
        const group = document.getElementById('drawerModuleGroupAdmin');
        const chevron = document.getElementById('drawerAdminChevron');
        if (group) {
            group.classList.toggle('collapsed');
            if (chevron) {
                chevron.textContent = group.classList.contains('collapsed') ? '▸' : '▾';
            }
        }
    }

    function toggleMobileFrame() {
        // The layout is now 100% fluidly responsive across all devices
    }

    function toggleDistributionModule() {
        const group = document.getElementById('moduleGroupDistribution');
        const chevron = document.getElementById('distChevron');
        if (group) {
            group.classList.toggle('collapsed');
            if (chevron) {
                chevron.textContent = group.classList.contains('collapsed') ? '▸' : '▾';
            }
        }
    }

    function toggleDistributionDrawerModule() {
        const group = document.getElementById('drawerModuleGroupDistribution');
        const chevron = document.getElementById('distDrawerChevron');
        if (group) {
            group.classList.toggle('collapsed');
            if (chevron) {
                chevron.textContent = group.classList.contains('collapsed') ? '▸' : '▾';
            }
        }
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

    let globalSosTimer = null;
    let alertedSosTickets = new Set();

    /**
     * Continuous 24/7 Fleet SOS Telemetry Heartbeat
     * Polls active SOS breakdown tickets every 4 seconds.
     * Guaranteed to alert Admin & Command center immediately regardless of active view tab.
     */
    function startGlobalSosMonitor() {
        if (globalSosTimer) clearInterval(globalSosTimer);

        async function checkActiveSos() {
            try {
                const res = await SecurityClient.secureFetch('/api/sos/active');
                if (res && res.success) {
                    const activeAlerts = res.alerts || [];

                    // Update Top Header Badge and Nav Badges
                    const headerBadge = document.getElementById('globalSosHeaderBadge');
                    const headerCountText = document.getElementById('globalSosCountText');
                    const navBadge = document.getElementById('adminNavSosBadge');
                    const drawerNavBadge = document.getElementById('drawerNavSosBadge');

                    if (activeAlerts.length > 0) {
                        const countText = `${activeAlerts.length} Active SOS`;
                        if (headerBadge) {
                            headerBadge.style.display = 'inline-flex';
                            if (headerCountText) headerCountText.textContent = countText;
                        }
                        if (navBadge) {
                            navBadge.style.display = 'inline-block';
                            navBadge.textContent = activeAlerts.length;
                        }
                        if (drawerNavBadge) {
                            drawerNavBadge.style.display = 'inline-block';
                            drawerNavBadge.textContent = activeAlerts.length;
                        }

                        // Check for any unalerted SOS
                        activeAlerts.forEach(alert => {
                            if (!alertedSosTickets.has(alert.sos_ticket_number)) {
                                alertedSosTickets.add(alert.sos_ticket_number);
                                triggerSosAlarm(alert);
                                if (currentRole === 'admin' && window.AdminDashboard && typeof window.AdminDashboard.loadAllAdminData === 'function') {
                                    window.AdminDashboard.loadAllAdminData();
                                }
                            }
                        });
                    } else {
                        if (headerBadge) headerBadge.style.display = 'none';
                        if (navBadge) navBadge.style.display = 'none';
                        if (drawerNavBadge) drawerNavBadge.style.display = 'none';

                        // If previous alerts were resolved
                        if (alertedSosTickets.size > 0) {
                            alertedSosTickets.clear();
                            if (currentRole === 'admin' && window.AdminDashboard && typeof window.AdminDashboard.loadAllAdminData === 'function') {
                                window.AdminDashboard.loadAllAdminData();
                            }
                        }
                    }
                }
            } catch (err) {
                // Ignore transient network errors
            }
        }

        setTimeout(checkActiveSos, 800);
        globalSosTimer = setInterval(checkActiveSos, 4000);
    }

    function triggerSosAlarm(sosAlert) {
        if (!sosAlert) return;
        const vehicle = sosAlert.vehicle_number || ('Vehicle #' + sosAlert.vehicle_id);
        const driver = sosAlert.driver_name ? `${sosAlert.driver_name} (${sosAlert.driver_phone || ''})` : 'Driver';
        const landmark = sosAlert.location_landmark || 'Polonnaruwa Region';
        const reason = sosAlert.breakdown_reason || 'Refrigeration / Mechanical Breakdown';
        const risk = (sosAlert.remaining_stock_at_incident !== undefined && sosAlert.remaining_stock_at_incident !== null) ? sosAlert.remaining_stock_at_incident : 0;

        const isSi = window.I18n && window.I18n.getLanguage() === 'si';
        const isTa = window.I18n && window.I18n.getLanguage() === 'ta';

        let msg = '';
        if (isSi) {
            msg = `🚨 හදිසි ආපදා ඇඟවීමක් (${sosAlert.sos_ticket_number}): ${vehicle} රථය ආපදාවකට ලක්ව ඇත! රියදුරු: ${driver} | ස්ථානය: ${landmark} | හේතුව: ${reason} | අනතුරේ ඇති තොගය: ${risk} cups. වහාම Rescue රථයක් පිටත් කරන්න!`;
        } else if (isTa) {
            msg = `🚨 அவசர SOS எச்சரிக்கை (${sosAlert.sos_ticket_number}): ${vehicle} பழுதடைந்துள்ளது! சாரதி: ${driver} | இடம்: ${landmark} | இருப்பு: ${risk} cups.`;
        } else {
            msg = `🚨 CRITICAL SOS ALERT (${sosAlert.sos_ticket_number}): Vehicle ${vehicle} broken down at ${landmark}! Driver: ${driver} | Reason: ${reason} | Risk: ${risk} cups. Immediate rescue needed!`;
        }

        SecurityClient.showToast(msg, 'emergency', 12000);
        playSirenAudio();
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

    // Dynamic Rescue Handshake Inventory State
    let handshakeItems = [];
    let selectedHandshakeCategory = 'ALL';
    let currentSosAlert = null;

    function openTransferHandshakeModal(sosId, alertObj = null) {
        const modal = document.getElementById('transferHandshakeModal');
        if (!modal) return;
        modal.dataset.sosId = sosId;
        currentSosAlert = alertObj;

        const remainingAtRisk = parseInt(alertObj?.remaining_stock_at_incident, 10) || 0;

        // Context Display
        const ticketEl = document.getElementById('handshakeTicketDisplay');
        const strandedEl = document.getElementById('handshakeStrandedVehicleDisplay');
        const rescueEl = document.getElementById('handshakeRescueVehicleDisplay');
        const riskEl = document.getElementById('handshakeRiskCupsDisplay');

        if (ticketEl) ticketEl.textContent = alertObj?.sos_ticket_number || ('SOS #' + sosId);
        if (strandedEl) strandedEl.textContent = alertObj?.vehicle_number ? `${alertObj.vehicle_number} (${alertObj.driver_name || 'Driver'})` : ('Stranded #' + (alertObj?.vehicle_id || sosId));
        if (rescueEl) {
            rescueEl.textContent = alertObj?.rescue_vehicle_number 
                ? `${alertObj.rescue_vehicle_number} (${alertObj.rescue_driver_name || 'Rescue Driver'})`
                : 'Standby Rescue Vehicle';
        }
        if (riskEl) riskEl.textContent = remainingAtRisk.toLocaleString();

        // Parse stranded products from alertObj (items_json or fallback)
        let parsed = null;
        if (alertObj && alertObj.items_json) {
            try {
                parsed = typeof alertObj.items_json === 'string' ? JSON.parse(alertObj.items_json) : alertObj.items_json;
            } catch (e) {
                console.warn('Failed parsing items_json:', e);
            }
        }

        if (Array.isArray(parsed) && parsed.length > 0) {
            const totalOriginalLoaded = parsed.reduce((sum, it) => sum + (parseInt(it.loaded_cups || it.qty || 0, 10)), 0);
            if (totalOriginalLoaded > 0 && remainingAtRisk < totalOriginalLoaded) {
                // Scale proportionally so sum of items matches remainingAtRisk
                let allocated = 0;
                handshakeItems = parsed.map((it, idx) => {
                    const original = parseInt(it.loaded_cups || it.qty || 0, 10);
                    let count = 0;
                    if (idx === parsed.length - 1) {
                        count = Math.max(0, remainingAtRisk - allocated);
                    } else {
                        count = Math.round((original / totalOriginalLoaded) * remainingAtRisk);
                        allocated += count;
                    }
                    return {
                        id: it.product_id || (idx + 1),
                        flavor_name: it.flavor_name || it.name || 'Dairy Product',
                        category: it.category || 'Fresh Cup Yogurt (80g)',
                        sku_code: it.sku_code || 'YOG-PROD',
                        unit_price: parseFloat(it.unit_price) || 85.00,
                        stranded_cups: count,
                        rescued_cups: count,
                        spoiled_cups: 0
                    };
                });
            } else {
                handshakeItems = parsed.map((it, idx) => {
                    const count = parseInt(it.loaded_cups || it.qty || remainingAtRisk || 0, 10);
                    return {
                        id: it.product_id || (idx + 1),
                        flavor_name: it.flavor_name || it.name || 'Dairy Product',
                        category: it.category || 'Fresh Cup Yogurt (80g)',
                        sku_code: it.sku_code || 'YOG-PROD',
                        unit_price: parseFloat(it.unit_price) || 85.00,
                        stranded_cups: count,
                        rescued_cups: count,
                        spoiled_cups: 0
                    };
                });
            }
        } else {
            // Standard fallback products breakdown
            const defaultFlavors = [
                { id: 1, flavor_name: 'Fresh Vanilla Yogurt (80g)', category: 'Fresh Cup Yogurt (80g)', sku_code: 'YOG-VAN-80', unit_price: 80.00, ratio: 0.4 },
                { id: 2, flavor_name: 'Sweet Strawberry Yogurt (80g)', category: 'Fresh Cup Yogurt (80g)', sku_code: 'YOG-STR-80', unit_price: 90.00, ratio: 0.25 },
                { id: 3, flavor_name: 'Creamy Chocolate Yogurt (80g)', category: 'Fresh Cup Yogurt (80g)', sku_code: 'YOG-CHO-80', unit_price: 95.00, ratio: 0.2 },
                { id: 4, flavor_name: 'Tropical Mango Yogurt (80g)', category: 'Fresh Cup Yogurt (80g)', sku_code: 'YOG-MAN-80', unit_price: 95.00, ratio: 0.15 }
            ];
            let allocated = 0;
            handshakeItems = defaultFlavors.map((df, idx) => {
                let count = 0;
                if (idx === defaultFlavors.length - 1) {
                    count = Math.max(0, remainingAtRisk - allocated);
                } else {
                    count = Math.round(remainingAtRisk * df.ratio);
                    allocated += count;
                }
                return {
                    id: df.id,
                    flavor_name: df.flavor_name,
                    category: df.category,
                    sku_code: df.sku_code,
                    unit_price: df.unit_price,
                    stranded_cups: count,
                    rescued_cups: count,
                    spoiled_cups: 0
                };
            });
        }

        // Populate Category Filter dropdown
        selectedHandshakeCategory = 'ALL';
        const categoryFilter = document.getElementById('handshakeCategoryFilter');
        if (categoryFilter) {
            const categories = Array.from(new Set(handshakeItems.map(p => p.category || 'Fresh Cup Yogurt (80g)')));
            categoryFilter.innerHTML = `
                <option value="ALL">All Categories (සියල්ල)</option>
                ${categories.map(c => `<option value="${SecurityClient.escapeHtml(c)}">${SecurityClient.escapeHtml(c)}</option>`).join('')}
            `;
            categoryFilter.value = 'ALL';
        }

        renderHandshakeProductsList();
        updateHandshakeTotals();

        modal.style.display = 'flex';
        I18n.applyTranslations(modal);
    }

    function onHandshakeCategoryFilterChange(category) {
        selectedHandshakeCategory = category;
        renderHandshakeProductsList();
    }

    function renderHandshakeProductsList() {
        const container = document.getElementById('handshakeProductsListContainer');
        if (!container) return;

        if (!handshakeItems || handshakeItems.length === 0) {
            container.innerHTML = `<div class="p-3 text-center text-muted text-xs">No stranded products on manifest.</div>`;
            return;
        }

        const filtered = selectedHandshakeCategory === 'ALL'
            ? handshakeItems
            : handshakeItems.filter(p => (p.category || 'Fresh Cup Yogurt (80g)') === selectedHandshakeCategory);

        container.innerHTML = filtered.map(p => {
            const rescuedSubtotal = p.rescued_cups * p.unit_price;

            return `
                <div class="disp-product-row" id="hs-row-${p.id}">
                    <div class="disp-prod-info">
                        <div class="disp-prod-title">
                            ${SecurityClient.escapeHtml(p.flavor_name)}
                        </div>
                        <div class="disp-prod-meta">
                            <span class="badge badge-outline" style="font-size:0.65rem; padding:1px 6px;">${SecurityClient.escapeHtml(p.category || 'Yogurt')}</span>
                            <span class="font-mono text-muted">${p.sku_code}</span>
                            <span>&bull;</span>
                            <span class="text-info font-bold">${SecurityClient.formatCurrency(p.unit_price)}</span>
                        </div>
                        <div class="disp-prod-stock text-warning" style="font-size:0.75rem; margin-top:3px;">
                            ⚠️ Stranded on Truck: <strong>${p.stranded_cups} cups</strong>
                        </div>
                    </div>

                    <div class="disp-prod-controls" style="display:flex; align-items:center; gap:8px;">
                        <div class="disp-prod-qty-box">
                            <label class="disp-control-label" style="color:var(--color-cash, #10b981);">Rescued (Good):</label>
                            <input type="number" 
                                class="form-control hs-good-qty" 
                                id="hs-good-${p.id}" 
                                data-id="${p.id}" 
                                value="${p.rescued_cups}" 
                                min="0" 
                                max="${p.stranded_cups}" 
                                style="width:75px; text-align:center; font-weight:700;"
                                oninput="App.onHandshakeQtyChange(${p.id})">
                        </div>
                        <div class="disp-prod-qty-box">
                            <label class="disp-control-label" style="color:var(--color-sos, #f43f5e);">Spoiled (Bad):</label>
                            <input type="number" 
                                class="form-control hs-spoiled-qty" 
                                id="hs-spoiled-${p.id}" 
                                data-id="${p.id}" 
                                value="${p.spoiled_cups}" 
                                min="0" 
                                max="${p.stranded_cups}" 
                                style="width:75px; text-align:center; font-weight:700;"
                                oninput="App.onHandshakeQtyChange(${p.id})">
                        </div>
                        <div class="disp-prod-subtotal-box" style="min-width:95px; text-align:right;">
                            <span class="disp-control-label">Rescued Value:</span>
                            <span id="hs-subtotal-${p.id}" class="disp-subtotal-val font-bold text-success" style="font-size:0.85rem;">${SecurityClient.formatCurrency(rescuedSubtotal)}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function onHandshakeQtyChange(productId) {
        const item = handshakeItems.find(p => p.id == productId);
        if (!item) return;

        const goodInput = document.getElementById(`hs-good-${productId}`);
        const spoiledInput = document.getElementById(`hs-spoiled-${productId}`);

        let good = parseInt(goodInput?.value, 10);
        let spoiled = parseInt(spoiledInput?.value, 10);

        if (isNaN(good) || good < 0) good = 0;
        if (isNaN(spoiled) || spoiled < 0) spoiled = 0;

        // Auto-balance: if good + spoiled exceeds stranded_cups, clamp good
        if (good + spoiled > item.stranded_cups) {
            good = Math.max(0, item.stranded_cups - spoiled);
            if (goodInput) goodInput.value = good;
        }

        item.rescued_cups = good;
        item.spoiled_cups = spoiled;

        // Update row subtotal
        const subtotalEl = document.getElementById(`hs-subtotal-${productId}`);
        if (subtotalEl) {
            subtotalEl.textContent = SecurityClient.formatCurrency(item.rescued_cups * item.unit_price);
        }

        updateHandshakeTotals();
    }

    function updateHandshakeTotals() {
        const totalRescued = handshakeItems.reduce((sum, it) => sum + (parseInt(it.rescued_cups, 10) || 0), 0);
        const totalSpoiled = handshakeItems.reduce((sum, it) => sum + (parseInt(it.spoiled_cups, 10) || 0), 0);
        const totalValue = handshakeItems.reduce((sum, it) => sum + ((parseInt(it.rescued_cups, 10) || 0) * (parseFloat(it.unit_price) || 0)), 0);
        const remainingAtRisk = parseInt(currentSosAlert?.remaining_stock_at_incident, 10) || 0;

        const rescuedDisplay = document.getElementById('handshakeTotalRescuedDisplay');
        const spoiledDisplay = document.getElementById('handshakeTotalSpoiledDisplay');
        const valueDisplay = document.getElementById('handshakeTotalValueDisplay');
        const warningEl = document.getElementById('handshakeCountWarning');
        const progressFill = document.getElementById('handshakeProgressBar');

        if (rescuedDisplay) rescuedDisplay.textContent = totalRescued.toLocaleString();
        if (spoiledDisplay) spoiledDisplay.textContent = totalSpoiled.toLocaleString();
        if (valueDisplay) valueDisplay.textContent = SecurityClient.formatCurrency(totalValue);

        // Update hidden inputs for backwards compatibility
        const hiddenGood = document.getElementById('transferTotalCups');
        const hiddenSpoiled = document.getElementById('transferSpoiledCups');
        if (hiddenGood) hiddenGood.value = totalRescued;
        if (hiddenSpoiled) hiddenSpoiled.value = totalSpoiled;

        // Update Progress Bar: % Rescued
        if (progressFill) {
            const totalCounted = totalRescued + totalSpoiled;
            const pct = remainingAtRisk > 0 ? Math.min(100, Math.round((totalRescued / remainingAtRisk) * 100)) : 100;
            progressFill.style.width = pct + '%';
            if (totalCounted > remainingAtRisk) {
                progressFill.style.background = '#f43f5e';
            } else {
                progressFill.style.background = 'linear-gradient(90deg, #10b981, #06b6d4)';
            }
        }

        // Show/hide count mismatch warning
        if (warningEl) {
            if ((totalRescued + totalSpoiled) !== remainingAtRisk && remainingAtRisk > 0) {
                warningEl.style.display = 'block';
                warningEl.innerHTML = `⚠️ Notice: Verified total (${totalRescued + totalSpoiled} cups) differs from stranded truck inventory (${remainingAtRisk} cups). Physical field adjustment logged.`;
            } else {
                warningEl.style.display = 'none';
            }
        }
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

        const totalGood = handshakeItems.reduce((sum, it) => sum + (parseInt(it.rescued_cups, 10) || 0), 0);
        const totalSpoiled = handshakeItems.reduce((sum, it) => sum + (parseInt(it.spoiled_cups, 10) || 0), 0);
        const notes = document.getElementById('transferHandshakeNotes')?.value || 'Field verified count completed.';

        // Prepare itemized manifest payload
        const rescuedItemsPayload = handshakeItems.map(it => ({
            product_id: it.id,
            flavor_name: it.flavor_name,
            category: it.category,
            sku_code: it.sku_code,
            unit_price: it.unit_price,
            loaded_cups: it.rescued_cups,
            spoiled_cups: it.spoiled_cups,
            subtotal: it.rescued_cups * it.unit_price
        }));

        const payload = {
            transferredGoodStock: totalGood,
            spoiledUnusableStock: totalSpoiled,
            handshakeNotes: notes,
            itemsJson: rescuedItemsPayload
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
                transferredCups: res.transferredCups || totalGood,
                spoiledCups: res.spoiledCups || totalSpoiled,
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
        toggleAdminModule,
        toggleAdminDrawerModule,
        toggleDistributionModule,
        toggleDistributionDrawerModule,
        playSirenAudio,
        triggerSosAlarm,
        installPwa,
        triggerDirectInstall,
        refreshCurrentView,
        dismissPreloader,
        openTransferHandshakeModal,
        closeTransferHandshakeModal,
        submitTransferHandshake,
        onHandshakeCategoryFilterChange,
        onHandshakeQtyChange,
        renderHandshakeProductsList,
        updateHandshakeTotals,
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
