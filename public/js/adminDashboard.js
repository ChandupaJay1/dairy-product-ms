/**
 * Front Office Desktop Admin UI Controller
 * 
 * Specially optimized for desktop browsers with an 'Eye-Care Ergonomic Theme'
 * (low-fatigue slate and navy palette for long 10-12 hour operational shifts).
 * Centralizes:
 * - Daily load dispatch manifests
 * - Dynamic fleet and driver rotation
 * - Live SOS breakdown alerts & rescue vehicle dispatch
 * - Smart shop credit ledger and risk tiering
 * - Tamper-evident cryptographic audit log inspection
 */

const AdminDashboard = (() => {
    let dashboardStats = null;
    let vehiclesList = [];
    let driversList = [];
    let routesList = [];
    let activeDispatches = [];
    let shopsList = [];
    let sosAlerts = [];
    let auditLogs = [];
    let pendingApprovals = { total_pending: 0, pending_payments: [], pending_returns: [], pending_shops: [], pending_orders: [] };
    let activeApprovalTab = 'payments';
    let pollingTimer = null;
    let salesRepsList = [];
    let activeStaffTab = 'vehicles';
    let vehicleSearchQuery = '';
    let driverSearchQuery = '';
    let repSearchQuery = '';
    let routeSearchQuery = '';
    let editingVehicleId = null;
    let editingDriverId = null;
    let editingSalesRepId = null;
    let editingRouteId = null;

    async function loadAllAdminData() {
        // Fast Cache-First Stale-While-Revalidate
        const cachedAdmin = OfflineStorage.getCachedData(OfflineStorage.CACHE_ADMIN_KEY);
        if (cachedAdmin) {
            dashboardStats = cachedAdmin.dashboardStats || dashboardStats;
            vehiclesList = cachedAdmin.vehiclesList || vehiclesList;
            driversList = cachedAdmin.driversList || driversList;
            routesList = cachedAdmin.routesList || routesList;
            activeDispatches = cachedAdmin.activeDispatches || activeDispatches;
            shopsList = cachedAdmin.shopsList || shopsList;
            sosAlerts = cachedAdmin.sosAlerts || sosAlerts;
            auditLogs = cachedAdmin.auditLogs || auditLogs;
            pendingApprovals = cachedAdmin.pendingApprovals || pendingApprovals;
            salesRepsList = cachedAdmin.salesRepsList || salesRepsList;
            render();
        } else {
            SecurityClient.renderLoading(
                'adminDashboardContainer',
                I18n.getLanguage() === 'si' ? 'ප්‍රධාන පාලක මැදිරිය සූදානම් වෙමින් පවතී' : 'Office Admin Command Center Preparing...',
                I18n.getLanguage() === 'si' ? '🖥️ වාහන ධාවනය, රියදුරන්, active dispatches සහ logs පූරණය වෙමින් පවතී...' : 'Loading vehicle fleet, driver rotation, active dispatches, and logs...',
                '🥛'
            );
        }

        try {
            const [statsRes, vehiclesRes, driversRes, routesRes, dispatchesRes, shopsRes, sosRes, logsRes, approvalsRes, repsRes] = await Promise.all([
                SecurityClient.secureFetch('/api/audit/dashboard-stats'),
                SecurityClient.secureFetch('/api/fleet/vehicles'),
                SecurityClient.secureFetch('/api/fleet/drivers'),
                SecurityClient.secureFetch('/api/fleet/routes'),
                SecurityClient.secureFetch('/api/dispatches/active'),
                SecurityClient.secureFetch('/api/shops'),
                SecurityClient.secureFetch('/api/sos/active'),
                SecurityClient.secureFetch('/api/audit/logs'),
                SecurityClient.secureFetch('/api/approvals/pending'),
                SecurityClient.secureFetch('/api/admin/sales-reps')
            ]);

            if (statsRes && statsRes.success) dashboardStats = statsRes.stats;
            if (vehiclesRes && vehiclesRes.success) vehiclesList = vehiclesRes.vehicles;
            if (driversRes && driversRes.success) driversList = driversRes.drivers;
            if (routesRes && routesRes.success) routesList = routesRes.routes;
            if (dispatchesRes && dispatchesRes.success) activeDispatches = dispatchesRes.dispatches;
            if (shopsRes && shopsRes.success) shopsList = shopsRes.shops;
            if (sosRes && sosRes.success) sosAlerts = sosRes.alerts;
            if (logsRes && logsRes.success) auditLogs = logsRes.logs;
            if (approvalsRes && approvalsRes.success) pendingApprovals = approvalsRes;
            if (repsRes && repsRes.success) salesRepsList = repsRes.reps || [];

            OfflineStorage.cacheOperationalData(OfflineStorage.CACHE_ADMIN_KEY, {
                dashboardStats,
                vehiclesList,
                driversList,
                routesList,
                activeDispatches,
                shopsList,
                sosAlerts,
                auditLogs,
                salesRepsList
            });
        } catch (err) {
            console.warn('Admin background data refresh error:', err);
        }

        render();

        // Start real-time background SOS telemetry polling
        startSosPolling();

        // Check if there are active SOS alerts and trigger siren if unacknowledged
        if (sosAlerts.length > 0 && window.App && typeof window.App.triggerSosAlarm === 'function') {
            window.App.triggerSosAlarm(sosAlerts[0]);
        }
    }

    function startSosPolling() {
        if (pollingTimer) clearInterval(pollingTimer);
        pollingTimer = setInterval(async () => {
            const container = document.getElementById('adminDashboardContainer');
            if (!container || !container.offsetParent) return;

            try {
                const sosRes = await SecurityClient.secureFetch('/api/sos/active');
                if (sosRes && sosRes.success) {
                    const prevCount = sosAlerts ? sosAlerts.length : 0;
                    const newAlerts = sosRes.alerts || [];

                    if (prevCount > 0 && newAlerts.length === 0) {
                        sosAlerts = [];
                        SecurityClient.showToast(
                            I18n.t('sos_resolved_toast', '🎉 Stock Rescue Completed! All yogurt cups transferred. SOS alert resolved.'),
                            'success',
                            6000
                        );
                        loadAllAdminData();
                    } else if (JSON.stringify(newAlerts) !== JSON.stringify(sosAlerts)) {
                        sosAlerts = newAlerts;
                        render();
                    }
                }
            } catch (e) {
                // Ignore transient network errors
            }
        }, 8000);
    }

    function render() {
        const container = document.getElementById('adminDashboardContainer');
        if (!container) return;

        container.innerHTML = `
            <div class="admin-eyecare-shell">
                <!-- Top Eye-Care Status Banner -->
                <div class="admin-top-banner">
                    <div>
                        <h2>${I18n.t('admin_central_command', '🏢 Front Office Central Command (Polonnaruwa Region)')}</h2>
                        <p class="text-sm text-eyecare-sub">
                            ${I18n.t('admin_op_hours', 'Operational Hours: 06:00 - 18:00 • Cold Chain Telemetry: All Hubs Optimal')}
                        </p>
                    </div>
                    <div class="banner-actions">
                        <button class="btn btn-sm btn-outline font-bold" onclick="AdminDashboard.refreshData()" id="adminRefreshBtn" title="Refresh Live Fleet & Dashboard Data">
                            🔄 ${I18n.t('btn_refresh_admin', 'Refresh Data')}
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="AdminDashboard.openNewDispatchModal()">
                            ${I18n.t('btn_new_dispatch', '+ New Morning Load Dispatch')}
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="AdminDashboard.openRotationModal()">
                            ${I18n.t('btn_rotate_fleet_top', '🔄 Rotate Fleet & Drivers')}
                        </button>
                    </div>
                </div>

                <!-- High Priority SOS Alert Banner (If Active) -->
                ${renderSosAlertBanners()}

                <!-- Key Operational Performance Metrics (KPIs) -->
                <div class="kpi-grid">
                    <div class="kpi-card card-glow-cyan">
                        <div class="kpi-icon">🥛</div>
                        <div class="kpi-data">
                            <span class="kpi-label">${I18n.t('kpi_morning_dispatched', 'Morning Dispatched')}</span>
                            <span class="kpi-val">${dashboardStats ? dashboardStats.totalLoadedToday.toLocaleString() : '1,500'}</span>
                            <span class="kpi-sub">${I18n.t('kpi_total_cups_loaded', 'Total cups loaded today')}</span>
                        </div>
                    </div>

                    <div class="kpi-card card-glow-emerald">
                        <div class="kpi-icon">✅</div>
                        <div class="kpi-data">
                            <span class="kpi-label">${I18n.t('kpi_delivered_sold', 'Cups Delivered & Sold')}</span>
                            <span class="kpi-val text-success">${dashboardStats ? dashboardStats.totalSoldToday.toLocaleString() : '180'}</span>
                            <span class="kpi-sub">${I18n.t('kpi_delivered_network', 'Delivered to retail network')}</span>
                        </div>
                    </div>

                    <div class="kpi-card card-glow-amber">
                        <div class="kpi-icon">🚚</div>
                        <div class="kpi-data">
                            <span class="kpi-label">${I18n.t('kpi_remaining_fleet', 'Remaining On Fleet')}</span>
                            <span class="kpi-val text-warning">${dashboardStats ? dashboardStats.totalRemainingToday.toLocaleString() : '1,320'}</span>
                            <span class="kpi-sub">${I18n.t('kpi_in_transit_stock', 'In transit refrigerated stock')}</span>
                        </div>
                    </div>

                    <div class="kpi-card card-glow-rose">
                        <div class="kpi-icon">🚨</div>
                        <div class="kpi-data">
                            <span class="kpi-label">${I18n.t('kpi_sos_incidents', 'Active SOS Incidents')}</span>
                            <span class="kpi-val ${sosAlerts.length > 0 ? 'text-danger font-bold' : ''}">
                                ${sosAlerts.length}
                            </span>
                            <span class="kpi-sub">${sosAlerts.length > 0 ? I18n.t('kpi_emergency_req', 'Emergency Rescue Required!') : I18n.t('kpi_fleet_smooth', 'Fleet running smooth')}</span>
                        </div>
                    </div>

                    <div class="kpi-card card-glow-indigo">
                        <div class="kpi-icon">💰</div>
                        <div class="kpi-data">
                            <span class="kpi-label">${I18n.t('kpi_credit_exposure', 'Total Credit Exposure')}</span>
                            <span class="kpi-val text-info">
                                ${dashboardStats ? SecurityClient.formatCurrency(dashboardStats.financials.totalCreditExposure) : 'Rs. 67,300'}
                            </span>
                            <span class="kpi-sub">${dashboardStats ? dashboardStats.financials.highRiskShopsCount : 1} ${I18n.t('kpi_flagged_risk', 'shop(s) flagged high-risk')}</span>
                        </div>
                    </div>

                    <div class="kpi-card ${pendingApprovals.total_pending > 0 ? 'card-glow-amber' : ''}" onclick="AdminDashboard.scrollToSection('approvalCenterSection')" style="cursor: pointer;">
                        <div class="kpi-icon">📋</div>
                        <div class="kpi-data">
                            <span class="kpi-label">${I18n.t('kpi_pending_approvals', 'Front Office Approvals')}</span>
                            <span class="kpi-val ${pendingApprovals.total_pending > 0 ? 'text-warning font-bold' : ''}">
                                ${pendingApprovals.total_pending}
                            </span>
                            <span class="kpi-sub">${pendingApprovals.total_pending > 0 ? I18n.t('kpi_action_required', '⚠️ Front Office Review Required') : I18n.t('kpi_all_confirmed', '✅ All Data Confirmed')}</span>
                        </div>
                    </div>
                </div>

                <!-- Front Office Maker-Checker Approval Center -->
                <div id="approvalCenterSection">
                    ${renderApprovalCenter()}
                </div>

                <!-- Expansive Operations Layout: Full-Width Sequential Sections -->
                <div class="admin-main-grid mt-4">
                    <!-- 1. Daily Dispatches & Inventory Manifests -->
                    <div class="card eyecare-card" id="dailyDispatchesSection">
                        <div class="card-header flex-between" style="flex-wrap: wrap; gap: 12px; padding: 1.25rem 1.5rem;">
                            <div>
                                <h3 style="display:flex; align-items:center; gap:8px; font-size: 1.15rem; font-weight: 800; margin: 0;">
                                    ${I18n.t('table_daily_dispatches', '🚚 Daily Dispatches & Inventory Manifests')}
                                </h3>
                                <span class="badge badge-outline mt-1" style="font-size:0.75rem;">${activeDispatches.length} ${I18n.t('active_today', 'Active Today')}</span>
                            </div>
                            <div class="card-header-toolbar" style="display: flex; align-items: center; gap: 10px;">
                                <input type="text" id="dispatchSearchInput" placeholder="${I18n.t('search_dispatch_placeholder', 'Search manifest / driver...')}" class="form-control" style="width:260px; font-size:0.85rem; padding:7px 12px;" oninput="AdminDashboard.filterDispatches(this.value)">
                                <button class="btn btn-primary font-bold" onclick="AdminDashboard.openNewDispatchModal()" style="padding: 7px 16px;">
                                    ${I18n.t('btn_new_dispatch_short', '+ New Dispatch')}
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table eyecare-table">
                                <thead>
                                    <tr>
                                        <th>${I18n.t('th_manifest', 'Manifest #')}</th>
                                        <th>${I18n.t('th_vehicle_driver', 'Vehicle & Driver')}</th>
                                        <th>${I18n.t('th_route_sector', 'Route Sector')}</th>
                                        <th>${I18n.t('th_loaded', 'Loaded')}</th>
                                        <th>${I18n.t('th_remaining', 'Remaining')}</th>
                                        <th>${I18n.t('th_status', 'Status')}</th>
                                        <th style="text-align: right;">${I18n.t('th_actions', 'Actions')}</th>
                                    </tr>
                                </thead>
                                <tbody id="dispatchesTableBody">
                                    ${renderDispatchRows(activeDispatches)}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 2. Panoramic Regional Fleet GPS Radar & Sector Control -->
                    <div class="card eyecare-card" id="fleetRadarSection">
                        <div class="card-header flex-between" style="padding: 1.25rem 1.5rem; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0;">${I18n.t('map_radar_title', '🗺️ Polonnaruwa Regional Fleet Radar')}</h3>
                                <p class="text-xs text-muted" style="margin-top: 3px;">Live GPS positioning, route telemetry, and cold-chain temperature telemetry</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="badge badge-success" style="padding: 5px 12px; font-size: 0.75rem;">${I18n.t('gps_live', 'GPS Live')}</span>
                            </div>
                        </div>
                        <div class="map-simulation-container">
                            <!-- Radar Telemetry Top HUD -->
                            <div class="radar-telemetry-hud">
                                <div class="hud-item">
                                    <span class="hud-pill">🛰️ 12 SATS</span>
                                    <span>SEC: LK-PLN-01</span>
                                </div>
                                <div class="hud-item" style="font-weight:600;">
                                    <span>📍 7.9403° N, 81.0188° E</span>
                                </div>
                                <div class="hud-item">
                                    <span class="live-pulse-dot" style="display:inline-block; width:6px; height:6px; border-radius:50%; background:#10b981;"></span>
                                    <span style="color:#10b981; font-weight:700;">LIVE SYNC</span>
                                </div>
                            </div>

                            <div class="map-graphic-layer">
                                <!-- SVG Vector Background Grid, Concentric Rings & Connecting Route Paths -->
                                <svg class="radar-svg-grid" viewBox="0 0 1200 500" preserveAspectRatio="none">
                                    <defs>
                                        <radialGradient id="radarCenterGlow" cx="50%" cy="50%" r="50%">
                                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.14" />
                                            <stop offset="60%" stop-color="#10b981" stop-opacity="0.02" />
                                            <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                                        </radialGradient>
                                    </defs>

                                    <!-- Background Sector Fill -->
                                    <rect width="100%" height="100%" fill="url(#radarCenterGlow)" />

                                    <!-- Tactical Grid Lines -->
                                    <line x1="50%" y1="0" x2="50%" y2="100%" stroke="rgba(255,255,255,0.06)" stroke-width="1" stroke-dasharray="4 4" />
                                    <line x1="0" y1="50%" x2="100%" y2="50%" stroke="rgba(255,255,255,0.06)" stroke-width="1" stroke-dasharray="4 4" />

                                    <!-- Concentric Range Rings (10km, 25km, 50km) -->
                                    <circle cx="50%" cy="50%" r="100" fill="none" stroke="rgba(16,185,129,0.22)" stroke-width="1" stroke-dasharray="3 3" />
                                    <circle cx="50%" cy="50%" r="220" fill="none" stroke="rgba(16,185,129,0.14)" stroke-width="1" />
                                    <circle cx="50%" cy="50%" r="380" fill="none" stroke="rgba(16,185,129,0.08)" stroke-width="1" stroke-dasharray="5 5" />

                                    <!-- Range Labels -->
                                    <text x="60%" y="49%" fill="rgba(16,185,129,0.55)" font-size="10" font-family="monospace">10 KM</text>
                                    <text x="70%" y="49%" fill="rgba(16,185,129,0.4)" font-size="10" font-family="monospace">25 KM</text>
                                    <text x="83%" y="49%" fill="rgba(16,185,129,0.25)" font-size="10" font-family="monospace">50 KM</text>

                                    <!-- Connecting Route Vector Paths from Kaduruwela (600, 250) -->
                                    <!-- Route 1: Kaduruwela -> Hingurakgoda (312, 160) -->
                                    <path d="M 600 250 L 312 160" stroke="#10b981" stroke-width="2" stroke-opacity="0.45" class="route-path-line" />
                                    <!-- Route 2: Hingurakgoda -> Minneriya (144, 240) -->
                                    <path d="M 312 160 L 144 240" stroke="#06b6d4" stroke-width="1.5" stroke-opacity="0.35" class="route-path-line" />
                                    <!-- Route 3: Kaduruwela -> Medirigiriya (888, 100) -->
                                    <path d="M 600 250 L 888 100" stroke="#a855f7" stroke-width="1.5" stroke-opacity="0.35" class="route-path-line" />
                                    <!-- Route 4: Kaduruwela -> Dimbulagala (936, 380) -->
                                    <path d="M 600 250 L 936 380" stroke="#f59e0b" stroke-width="1.5" stroke-opacity="0.35" class="route-path-line" />
                                </svg>

                                <!-- 360-degree Radar Sweep Beam -->
                                <div class="radar-sweep"></div>

                                <!-- Regional Landmarks & Route Pins -->
                                <div class="map-hub hub-kaduruwela" title="Kaduruwela Central Wholesale Hub & Cold-Chain Depot">
                                    <div class="hub-radar-ring"></div>
                                    <div class="hub-label">🏢 Kaduruwela (Central Hub)</div>
                                </div>
                                <div class="map-hub hub-hingurakgoda" title="Hingurakgoda Commercial Sector (Route 01)">
                                    <div class="hub-radar-ring"></div>
                                    <div class="hub-label">📍 Hingurakgoda Town</div>
                                </div>
                                <div class="map-hub hub-minneriya" title="Minneriya Dairy Hub (Highway Junction)">
                                    <div class="hub-radar-ring"></div>
                                    <div class="hub-label">📍 Minneriya Junction</div>
                                </div>
                                <div class="map-hub hub-medirigiriya" title="Medirigiriya Dairy Belt (Route 03)">
                                    <div class="hub-radar-ring"></div>
                                    <div class="hub-label">📍 Medirigiriya Vatadage</div>
                                </div>
                                <div class="map-hub hub-dimbulagala" title="Dimbulagala Sector (Mahaweli Zone Link)">
                                    <div class="hub-radar-ring"></div>
                                    <div class="hub-label">📍 Dimbulagala Coop</div>
                                </div>

                                <!-- Fleet Vehicle Pins with Real-Time Telemetry Badges -->
                                ${renderRadarVehicleMarkers()}

                                <!-- SOS Incident Beacon if active -->
                                ${sosAlerts.length > 0 ? `
                                    <div class="sos-radar-ping" style="top: 24%; left: 70%;" title="🚨 EMERGENCY SOS: ${SecurityClient.escapeHtml(sosAlerts[0].vehicle_number)}">
                                        <div class="ping-wave"></div>
                                        <div class="ping-core">🚨 SOS: ${SecurityClient.escapeHtml(sosAlerts[0].vehicle_number)}</div>
                                    </div>
                                ` : ''}
                            </div>

                            <!-- Telemetry Map Legend Footer -->
                            <div class="map-legend">
                                <span>${I18n.t('legend_active_fleet', '🟢 Active Fleet (1)')}</span>
                                <span>${I18n.t('legend_depot_reserve', '🔵 Depot Reserve (3)')}</span>
                                <span>${I18n.t('legend_sos_radar', sosAlerts.length > 0 ? '🔴 SOS Active (1)' : '🔴 SOS Clear (0)')}</span>
                                <span>📍 ${I18n.t('legend_hubs', 'Regional Hubs (5)')}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Dedicated Fleet & Staff Roster Management Section -->
                    <div id="fleetStaffManagementSection">
                        ${renderFleetStaffManagementHub()}
                    </div>

                    <!-- 4. Smart Credit Tiering & Shop Risk Table -->
                    <div class="card eyecare-card" id="creditRiskSection">
                        <div class="card-header flex-between" style="flex-wrap: wrap; gap: 12px; padding: 1.25rem 1.5rem;">
                            <div>
                                <h3 style="display:flex; align-items:center; gap:8px; font-size: 1.15rem; font-weight: 800; margin: 0;">
                                    ${I18n.t('credit_tiering_title', '📊 Smart Credit Tiering & Shop Risk Control')}
                                </h3>
                                <span class="text-xs text-muted mt-1" style="display:inline-block;">${I18n.t('auto_limits', 'Automated Limits')}</span>
                            </div>
                            <div class="card-header-toolbar">
                                <input type="text" id="shopRiskSearchInput" placeholder="${I18n.t('search_shops_placeholder', 'Filter shops by name / phone...')}" class="form-control" style="width:260px; font-size:0.85rem; padding:7px 12px;" oninput="AdminDashboard.filterShopRisk(this.value)">
                            </div>
                        </div>
                        <div class="table-responsive max-h-500">
                            <table class="table eyecare-table text-sm">
                                <thead>
                                    <tr>
                                        <th>${I18n.t('th_shop_name', 'Shop Name')}</th>
                                        <th>${I18n.t('th_tier', 'Tier')}</th>
                                        <th>${I18n.t('th_limit', 'Limit')}</th>
                                        <th>${I18n.t('th_balance', 'Balance')}</th>
                                        <th>${I18n.t('th_rating', 'Rating')}</th>
                                        <th>${I18n.t('th_action', 'Action')}</th>
                                    </tr>
                                </thead>
                                <tbody id="shopRiskTableBody">
                                    ${renderShopRiskRows(shopsList)}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- System Activity & Audit Trail -->
                <div class="card eyecare-card mt-4">
                    <div class="card-header flex-between">
                        <h3>${I18n.t('audit_trail_title', '🔒 System Activity & Audit Trail')}</h3>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button class="btn btn-xs btn-outline-cyan font-bold" onclick="App.switchView('audit')">${I18n.t('btn_view_full_audit', '🔍 Full Audit Ledger')}</button>
                            <span class="badge badge-success">${I18n.t('audit_verified', 'Audit Verified')}</span>
                        </div>
                    </div>
                    <div class="table-responsive max-h-300">
                        <table class="table eyecare-table text-xs">
                            <thead>
                                <tr>
                                    <th>${I18n.t('th_timestamp', 'Timestamp')}</th>
                                    <th>${I18n.t('th_action_type', 'Action Type')}</th>
                                    <th>${I18n.t('th_actor', 'Actor')}</th>
                                    <th>${I18n.t('th_target_entity', 'Target Entity')}</th>
                                    <th>${I18n.t('th_verification_ref', 'Verification Ref')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${auditLogs.map(log => `
                                    <tr>
                                        <td>${new Date(log.timestamp).toLocaleTimeString()}</td>
                                        <td><strong>${SecurityClient.escapeHtml(log.action_type)}</strong></td>
                                        <td>${SecurityClient.escapeHtml(log.actor_name)} (${log.actor_role})</td>
                                        <td>${SecurityClient.escapeHtml(log.target_entity)}</td>
                                        <td class="font-mono text-cyan truncate-seal" title="${log.security_seal}">
                                            ${log.security_seal ? log.security_seal.substring(0, 24) + '...' : 'SEALED'}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

        const auditCenterEl = document.getElementById('auditCenterLogsContainer');
        if (auditCenterEl) {
            auditCenterEl.innerHTML = `
                <div class="table-responsive max-h-500 mt-2">
                    <table class="table eyecare-table text-xs">
                        <thead>
                            <tr>
                                <th>${I18n.t('th_timestamp', 'Timestamp')}</th>
                                <th>${I18n.t('th_action_type', 'Action Type')}</th>
                                <th>${I18n.t('th_actor', 'Actor')}</th>
                                <th>${I18n.t('th_target_entity', 'Target Entity')}</th>
                                <th>${I18n.t('th_verification_ref', 'Verification Ref')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${auditLogs.map(log => `
                                <tr>
                                    <td>${new Date(log.timestamp).toLocaleString()}</td>
                                    <td><strong class="badge badge-outline">${SecurityClient.escapeHtml(log.action_type)}</strong></td>
                                    <td>${SecurityClient.escapeHtml(log.actor_name)} <span class="text-xs text-muted">(${log.actor_role})</span></td>
                                    <td>${SecurityClient.escapeHtml(log.target_entity)}</td>
                                    <td class="font-mono text-cyan truncate-seal" title="${log.security_seal}">
                                        ${log.security_seal ? log.security_seal.substring(0, 24) + '...' : 'SEALED'}
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
    }

    function renderSosAlertBanners() {
        if (sosAlerts.length === 0) return '';

        return sosAlerts.map(alert => `
            <div class="admin-sos-emergency-banner animate-pulse-glow">
                <div class="sos-banner-left">
                    <span class="sos-big-icon">🚨</span>
                    <div>
                        <h3 class="text-danger">${I18n.t('sos_alert_title', 'CRITICAL VEHICLE BREAKDOWN - SOS TICKET #')}${SecurityClient.escapeHtml(alert.sos_ticket_number)}</h3>
                        <p class="text-sm">
                            <strong>Vehicle:</strong> ${SecurityClient.escapeHtml(alert.vehicle_number)} &bull; 
                            <strong>Driver:</strong> ${SecurityClient.escapeHtml(alert.driver_name)} (${SecurityClient.escapeHtml(alert.driver_phone)}) &bull; 
                            <strong>Reason:</strong> ${SecurityClient.escapeHtml(alert.breakdown_reason)} &bull; 
                            <strong>Location:</strong> ${SecurityClient.escapeHtml(alert.location_landmark)} &bull; 
                            <strong class="text-danger">${I18n.t('sos_stock_risk', 'Stock at Risk:')} ${alert.remaining_stock_at_incident} cups</strong>
                        </p>
                    </div>
                </div>
                <div class="sos-banner-right">
                    ${alert.status === 'PENDING_RESCUE' ? `
                        <button class="btn btn-danger font-bold btn-dispatch-rescue" onclick="AdminDashboard.openDispatchRescueModal(${alert.id})">
                            ${I18n.t('btn_dispatch_rescue', '⚡ Dispatch Rescue Vehicle Immediately')}
                        </button>
                    ` : `
                        <div style="display:flex; flex-direction:column; gap:0.5rem; align-items:flex-end;">
                            <span class="badge badge-warning text-sm p-2">
                                🚐 Rescue Van En Route (${SecurityClient.escapeHtml(alert.rescue_vehicle_number || 'NC-GA-1190')})
                            </span>
                            <button class="btn btn-success font-bold" onclick="AdminDashboard.openTransferHandshakeModal(${alert.id})">
                                ${I18n.t('btn_complete_handshake_admin', '🤝 Complete Handshake & Transfer')}
                            </button>
                        </div>
                    `}
                </div>
            </div>
        `).join('');
    }

    function renderRadarVehicleMarkers() {
        if (!vehiclesList || vehiclesList.length === 0) {
            return `
                <div class="vehicle-gps-marker marker-truck-1" title="NC-GA-4521 | In Transit">
                    🚚 NC-GA-4521 <span style="font-size:0.65rem; padding:1px 5px; border-radius:4px; background:rgba(16,185,129,0.3); color:#fff;">42 km/h</span>
                </div>
            `;
        }

        const routePositions = {
            1: { top: '32%', left: '32%' },  // En route to Hingurakgoda
            2: { top: '46%', left: '18%' },  // En route to Minneriya
            3: { top: '24%', left: '68%' },  // En route to Medirigiriya
            4: { top: '70%', left: '72%' },  // En route to Dimbulagala
        };

        // Generously spaced depot parking bays around Kaduruwela Central Hub (top: 50%, left: 50%)
        // Spaced out so badges never overlap each other or the depot label
        const depotBays = [
            { top: '59%', left: '41%' }, // Bay A: South-West
            { top: '66%', left: '50%' }, // Bay B: Direct South
            { top: '59%', left: '59%' }, // Bay C: South-East
            { top: '41%', left: '61%' }, // Bay D: North-East
            { top: '41%', left: '39%' }, // Bay E: North-West
        ];

        return vehiclesList.slice(0, 5).map((v, idx) => {
            let pos;
            if (v.status === 'IN_TRANSIT' && v.current_route_id && routePositions[v.current_route_id]) {
                pos = routePositions[v.current_route_id];
            } else {
                pos = depotBays[idx % depotBays.length];
            }

            const isTransit = v.status === 'IN_TRANSIT';
            const speed = isTransit ? (38 + (idx * 4)) + ' km/h' : (v.status === 'BREAKDOWN' ? 'Alert' : 'Standby');
            const bg = isTransit ? 'rgba(16,185,129,0.35)' : (v.status === 'BREAKDOWN' ? 'rgba(239,68,68,0.5)' : 'rgba(14,165,233,0.3)');

            return `
                <div class="vehicle-gps-marker" style="top:${pos.top}; left:${pos.left}; z-index:25;" title="${SecurityClient.escapeHtml(v.vehicle_number)} | ${v.status} | Driver: ${SecurityClient.escapeHtml(v.driver_name || 'Standby')} | Chiller: ${v.cooling_system_status === 'NORMAL_COOLING' ? '4.0°C Optimal' : 'Check'}">
                    🚚 ${SecurityClient.escapeHtml(v.vehicle_number)} <span style="font-size:0.65rem; padding:1px 5px; border-radius:4px; background:${bg}; color:#fff;">${speed}</span>
                </div>
            `;
        }).join('');
    }

    function scrollToSection(id) {
        const el = document.getElementById(id);
        if (!el) return;
        const headerOffset = 95;
        const elementPosition = el.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
        window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
    }

    function switchApprovalTab(tab) {
        activeApprovalTab = tab;
        render();
    }

    function renderApprovalCenter() {
        const total = pendingApprovals.total_pending || 0;
        const payments = pendingApprovals.pending_payments || [];
        const returns = pendingApprovals.pending_returns || [];
        const shops = pendingApprovals.pending_shops || [];
        const orders = pendingApprovals.pending_orders || [];

        return `
            <div class="approval-center-card">
                <div class="approval-center-header">
                    <div class="approval-center-title">
                        <span style="font-size: 1.35rem;">🛡️</span>
                        <div>
                            <h3>${I18n.t('approval_center_title', 'Front Office Approval Center (Maker-Checker Protocol)')}</h3>
                            <div class="text-xs text-eyecare-sub">
                                ${I18n.t('approval_center_sub', 'Data submitted by Field Sales Reps must be verified and approved by Front Office Admin.')}
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="approval-badge-count ${total === 0 ? 'approval-badge-zero' : ''}">
                            ${total > 0 ? `${I18n.t('status_action_required', '⚠️ Action Required')}: ${total}` : I18n.t('status_all_verified', '✅ All Verified')}
                        </span>
                    </div>
                </div>

                <div class="approval-nav-tabs">
                    <button type="button" class="btn approval-tab-btn ${activeApprovalTab === 'payments' ? 'active' : ''}" onclick="AdminDashboard.switchApprovalTab('payments')">
                        <span>💳</span>
                        <span>${I18n.t('tab_pending_payments', 'Rep Cash Handover')}</span>
                        <span class="approval-tab-pill ${payments.length > 0 ? 'pill-alert' : ''}">${payments.length}</span>
                    </button>
                    <button type="button" class="btn approval-tab-btn ${activeApprovalTab === 'returns' ? 'active' : ''}" onclick="AdminDashboard.switchApprovalTab('returns')">
                        <span>🥛</span>
                        <span>${I18n.t('tab_pending_returns', 'Returns & Expiry')}</span>
                        <span class="approval-tab-pill ${returns.length > 0 ? 'pill-alert' : ''}">${returns.length}</span>
                    </button>
                    <button type="button" class="btn approval-tab-btn ${activeApprovalTab === 'shops' ? 'active' : ''}" onclick="AdminDashboard.switchApprovalTab('shops')">
                        <span>🏪</span>
                        <span>${I18n.t('tab_pending_shops', 'New Shop Approvals')}</span>
                        <span class="approval-tab-pill ${shops.length > 0 ? 'pill-alert' : ''}">${shops.length}</span>
                    </button>
                    <button type="button" class="btn approval-tab-btn ${activeApprovalTab === 'orders' ? 'active' : ''}" onclick="AdminDashboard.switchApprovalTab('orders')">
                        <span>📝</span>
                        <span>${I18n.t('tab_pending_orders', 'Credit Invoices')}</span>
                        <span class="approval-tab-pill ${orders.length > 0 ? 'pill-alert' : ''}">${orders.length}</span>
                    </button>
                </div>

                <div class="approval-content-box">
                    ${renderActiveApprovalTabContent()}
                </div>
            </div>
        `;
    }

    function renderActiveApprovalTabContent() {
        if (activeApprovalTab === 'payments') {
            const payments = pendingApprovals.pending_payments || [];
            if (payments.length === 0) {
                return `<div class="approval-empty-state">${I18n.t('approval_empty_payments', '✅ No pending payments to verify. All Sales Rep cash collections have been verified.')}</div>`;
            }
            const totalCash = payments.reduce((sum, p) => sum + parseFloat(p.payment_amount || 0), 0);
            return `
                <div class="approval-summary-banner">
                    <div class="approval-summary-stats">
                        <span>💳 <strong>${payments.length}</strong> Pending Handover(s)</span>
                        <span>💵 Total Cash: <strong class="text-success">${SecurityClient.formatCurrency(totalCash)}</strong></span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table eyecare-table">
                        <thead>
                            <tr>
                                <th>${I18n.t('th_receipt_num', 'Receipt #')}</th>
                                <th>${I18n.t('th_shop_phone', 'Shop & Contact')}</th>
                                <th>${I18n.t('th_collected_rep', 'Collected Rep')}</th>
                                <th>${I18n.t('th_amount', 'Amount (Rs.)')}</th>
                                <th>${I18n.t('th_current_debt', 'Current Debt')}</th>
                                <th>${I18n.t('th_notes', 'Notes')}</th>
                                <th style="text-align: right;">${I18n.t('th_actions', 'Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${payments.map(p => `
                                <tr id="payment-row-${p.id}">
                                    <td><strong>${SecurityClient.escapeHtml(p.receipt_number)}</strong><div class="text-xs text-muted">${new Date(p.created_at).toLocaleTimeString()}</div></td>
                                    <td>
                                        <div><strong>${SecurityClient.escapeHtml(p.shop_name || 'Shop #' + p.shop_id)}</strong></div>
                                        <div class="text-xs text-muted">${SecurityClient.escapeHtml(p.owner_name || '')} (${SecurityClient.escapeHtml(p.shop_phone || '')})</div>
                                    </td>
                                    <td>${SecurityClient.escapeHtml(p.sales_rep_name || 'Field Sales Rep')}</td>
                                    <td><strong class="text-success" style="font-size: 1.05rem;">${SecurityClient.formatCurrency(p.payment_amount)}</strong></td>
                                    <td>${SecurityClient.formatCurrency(p.outstanding_balance || 0)}</td>
                                    <td><span class="text-xs text-muted">${SecurityClient.escapeHtml(p.notes || 'Cash handed over')}</span></td>
                                    <td style="text-align: right;">
                                        <div class="approval-actions-cell" style="justify-content: flex-end;">
                                            <button type="button" class="btn-approve" onclick="AdminDashboard.approvePayment(${p.id}, this)">
                                                ${I18n.t('btn_confirm_received', '✅ Cash Received (Confirm)')}
                                            </button>
                                            <button type="button" class="btn-reject" onclick="AdminDashboard.rejectPayment(${p.id}, this)">
                                                ${I18n.t('btn_reject', '❌ Reject')}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        if (activeApprovalTab === 'returns') {
            const returns = pendingApprovals.pending_returns || [];
            if (returns.length === 0) {
                return `<div class="approval-empty-state">${I18n.t('approval_empty_returns', '✅ No pending return notes to inspect. All return records verified.')}</div>`;
            }
            const totalCups = returns.reduce((sum, r) => sum + parseInt(r.quantity_cups || 0), 0);
            const totalVal = returns.reduce((sum, r) => sum + parseFloat(r.total_value || 0), 0);
            return `
                <div class="approval-summary-banner">
                    <div class="approval-summary-stats">
                        <span>📋 <strong>${returns.length}</strong> Pending Return Note(s)</span>
                        <span>🥛 <strong>${totalCups}</strong> Cups Returned</span>
                        <span>💰 Total Value: <strong class="text-warning">${SecurityClient.formatCurrency(totalVal)}</strong></span>
                    </div>
                    <div>
                        <button type="button" class="btn-batch-verify" onclick="AdminDashboard.approveAllReturns(this)">
                            ✅ ${I18n.t('btn_verify_all_returns', 'Verify All Returns (Batch Credit)')}
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table eyecare-table">
                        <thead>
                            <tr>
                                <th>${I18n.t('th_return_num', 'Return #')}</th>
                                <th>${I18n.t('th_shop', 'Shop')}</th>
                                <th>${I18n.t('th_flavor_batch', 'Flavor & Batch')}</th>
                                <th>${I18n.t('th_quantity_cups', 'Quantity (Cups)')}</th>
                                <th>${I18n.t('th_total_value', 'Total Value (Rs.)')}</th>
                                <th>${I18n.t('th_reason_settlement', 'Reason & Settlement')}</th>
                                <th style="text-align: right;">${I18n.t('th_physical_verification', 'Physical Verification')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${returns.map(r => `
                                <tr id="return-row-${r.id}">
                                    <td><strong>${SecurityClient.escapeHtml(r.return_number)}</strong></td>
                                    <td>
                                        <div><strong>${SecurityClient.escapeHtml(r.shop_name || 'Shop #' + r.shop_id)}</strong></div>
                                        <div class="text-xs text-muted">${SecurityClient.escapeHtml(r.shop_phone || '')}</div>
                                    </td>
                                    <td><strong>${SecurityClient.escapeHtml(r.flavor_name)}</strong><div class="text-xs text-muted">Batch: ${SecurityClient.escapeHtml(r.batch_number)}</div></td>
                                    <td><strong class="text-warning">${r.quantity_cups}</strong></td>
                                    <td><strong>${SecurityClient.formatCurrency(r.total_value)}</strong></td>
                                    <td>
                                        <span class="badge badge-outline">${SecurityClient.escapeHtml(r.return_reason)}</span>
                                        <div class="text-xs text-muted mt-1">${SecurityClient.escapeHtml(r.settlement_action)}</div>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="approval-actions-cell" style="justify-content: flex-end;">
                                            <button type="button" class="btn-approve" onclick="AdminDashboard.approveReturn(${r.id}, this)">
                                                ${I18n.t('btn_verify_credit', '✅ Verify & Credit')}
                                            </button>
                                            <button type="button" class="btn-reject" onclick="AdminDashboard.rejectReturn(${r.id}, this)">
                                                ${I18n.t('btn_reject', '❌ Reject')}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        if (activeApprovalTab === 'shops') {
            const shops = pendingApprovals.pending_shops || [];
            if (shops.length === 0) {
                return `<div class="approval-empty-state">${I18n.t('approval_empty_shops', '✅ No pending new shop registrations. All shops active.')}</div>`;
            }
            return `
                <div class="approval-summary-banner">
                    <div class="approval-summary-stats">
                        <span>🏪 <strong>${shops.length}</strong> Pending Shop Registration(s)</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table eyecare-table">
                        <thead>
                            <tr>
                                <th>${I18n.t('th_shop_owner', 'Shop Name & Owner')}</th>
                                <th>${I18n.t('th_phone', 'Phone')}</th>
                                <th>${I18n.t('th_route_sector', 'Route Sector')}</th>
                                <th>${I18n.t('th_address', 'Address')}</th>
                                <th>${I18n.t('th_requested_credit', 'Requested Credit Tier')}</th>
                                <th style="text-align: right;">${I18n.t('th_admin_approval', 'Admin Approval')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${shops.map(s => `
                                <tr id="shop-row-${s.id}">
                                    <td>
                                        <div><strong>${SecurityClient.escapeHtml(s.name)}</strong></div>
                                        <div class="text-xs text-muted">${SecurityClient.escapeHtml(s.owner_name)}</div>
                                    </td>
                                    <td>${SecurityClient.escapeHtml(s.phone)}</td>
                                    <td>${SecurityClient.escapeHtml(s.route_name || 'Route #' + s.route_id)}</td>
                                    <td><span class="text-xs">${SecurityClient.escapeHtml(s.address)}</span></td>
                                    <td>
                                        <span class="badge badge-primary">${SecurityClient.escapeHtml(s.credit_tier)}</span>
                                        <div class="text-xs text-muted mt-1">Limit: ${SecurityClient.formatCurrency(s.credit_limit)}</div>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="approval-actions-cell" style="justify-content: flex-end;">
                                            <button type="button" class="btn-approve" onclick="AdminDashboard.approveShop(${s.id}, this)">
                                                ${I18n.t('btn_approve_activate', '✅ Approve & Activate')}
                                            </button>
                                            <button type="button" class="btn-reject" onclick="AdminDashboard.rejectShop(${s.id}, this)">
                                                ${I18n.t('btn_reject', '❌ Reject')}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        if (activeApprovalTab === 'orders') {
            const orders = pendingApprovals.pending_orders || [];
            if (orders.length === 0) {
                return `<div class="approval-empty-state">${I18n.t('approval_empty_orders', '✅ No pending credit invoices requiring special approval.')}</div>`;
            }
            const totalInvoiceVal = orders.reduce((sum, o) => sum + parseFloat(o.total_amount || 0), 0);
            return `
                <div class="approval-summary-banner">
                    <div class="approval-summary-stats">
                        <span>📝 <strong>${orders.length}</strong> Pending Credit Order(s)</span>
                        <span>💰 Total Invoiced: <strong class="text-warning">${SecurityClient.formatCurrency(totalInvoiceVal)}</strong></span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table eyecare-table">
                        <thead>
                            <tr>
                                <th>${I18n.t('th_invoice_num', 'Invoice #')}</th>
                                <th>${I18n.t('th_shop', 'Shop')}</th>
                                <th>${I18n.t('th_payment_type', 'Payment Method')}</th>
                                <th>${I18n.t('th_amount', 'Amount (Rs.)')}</th>
                                <th>${I18n.t('th_current_debt', 'Current Debt')}</th>
                                <th style="text-align: right;">${I18n.t('th_actions', 'Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${orders.map(o => `
                                <tr id="order-row-${o.id}">
                                    <td><strong>${SecurityClient.escapeHtml(o.invoice_number)}</strong></td>
                                    <td><strong>${SecurityClient.escapeHtml(o.shop_name || 'Shop #' + o.shop_id)}</strong></td>
                                    <td><span class="badge badge-warning">${SecurityClient.escapeHtml(o.payment_type)}</span></td>
                                    <td><strong class="text-warning">${SecurityClient.formatCurrency(o.total_amount)}</strong></td>
                                    <td>${SecurityClient.formatCurrency(o.outstanding_balance || 0)}</td>
                                    <td style="text-align: right;">
                                        <div class="approval-actions-cell" style="justify-content: flex-end;">
                                            <button type="button" class="btn-approve" onclick="AdminDashboard.approveOrder(${o.id}, this)">
                                                ${I18n.t('btn_confirm_order', '✅ Confirm Order')}
                                            </button>
                                            <button type="button" class="btn-reject" onclick="AdminDashboard.rejectOrder(${o.id}, this)">
                                                ${I18n.t('btn_reject', '❌ Reject')}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        return '';
    }

    function updateApprovalBadgeCounts() {
        const payments = pendingApprovals.pending_payments || [];
        const returns = pendingApprovals.pending_returns || [];
        const shops = pendingApprovals.pending_shops || [];
        const orders = pendingApprovals.pending_orders || [];
        const total = payments.length + returns.length + shops.length + orders.length;
        pendingApprovals.total_pending = total;

        const overallBadge = document.querySelector('.approval-badge-count');
        if (overallBadge) {
            if (total === 0) {
                overallBadge.className = 'approval-badge-count approval-badge-zero';
                overallBadge.textContent = I18n.t('status_all_verified', '✅ All Verified');
            } else {
                overallBadge.className = 'approval-badge-count';
                overallBadge.textContent = `${I18n.t('status_action_required', '⚠️ Action Required')}: ${total}`;
            }
        }

        const tabBtns = document.querySelectorAll('.approval-tab-btn');
        if (tabBtns.length >= 4) {
            const pPill = tabBtns[0].querySelector('.approval-tab-pill');
            if (pPill) {
                pPill.textContent = payments.length;
                pPill.className = `approval-tab-pill ${payments.length > 0 ? 'pill-alert' : ''}`;
            }
            const rPill = tabBtns[1].querySelector('.approval-tab-pill');
            if (rPill) {
                rPill.textContent = returns.length;
                rPill.className = `approval-tab-pill ${returns.length > 0 ? 'pill-alert' : ''}`;
            }
            const sPill = tabBtns[2].querySelector('.approval-tab-pill');
            if (sPill) {
                sPill.textContent = shops.length;
                sPill.className = `approval-tab-pill ${shops.length > 0 ? 'pill-alert' : ''}`;
            }
            const oPill = tabBtns[3].querySelector('.approval-tab-pill');
            if (oPill) {
                oPill.textContent = orders.length;
                oPill.className = `approval-tab-pill ${orders.length > 0 ? 'pill-alert' : ''}`;
            }
        }
    }

    function refreshApprovalBox() {
        const box = document.querySelector('.approval-content-box');
        if (box) {
            box.innerHTML = renderActiveApprovalTabContent();
        }
    }

    async function approvePayment(id, btnElement) {
        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '⏳ Verifying...';
        }
        try {
            const res = await SecurityClient.secureFetch(`/api/approvals/payments/${id}/action`, {
                method: 'POST',
                body: JSON.stringify({ action: 'APPROVE', admin_name: 'Front Office Admin' })
            });
            if (res && res.success) {
                SecurityClient.showToast(res.message, 'success', 5000);
                const row = document.getElementById(`payment-row-${id}`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        row.remove();
                        if (pendingApprovals.pending_payments) {
                            pendingApprovals.pending_payments = pendingApprovals.pending_payments.filter(p => p.id !== id);
                        }
                        updateApprovalBadgeCounts();
                        if (!pendingApprovals.pending_payments || pendingApprovals.pending_payments.length === 0) {
                            refreshApprovalBox();
                        }
                    }, 300);
                } else {
                    loadAllAdminData();
                }
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to approve payment.', 'error');
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = I18n.t('btn_confirm_received', '✅ Cash Received (Confirm)');
                }
            }
        } catch (err) {
            SecurityClient.showToast('Network error while approving payment.', 'error');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = I18n.t('btn_confirm_received', '✅ Cash Received (Confirm)');
            }
        }
    }

    async function rejectPayment(id, btnElement) {
        const promptMsg = I18n.getLanguage() === 'si'
            ? 'ගෙවීම ප්‍රතික්ෂේප කිරීමට හේතුව ඇතුළත් කරන්න:'
            : 'Enter reason for rejecting this payment:';
        const reason = prompt(promptMsg, 'Cash/Cheque was not physically handed over or mismatched');
        if (reason === null) return;

        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '⏳ Rejecting...';
        }
        try {
            const res = await SecurityClient.secureFetch(`/api/approvals/payments/${id}/action`, {
                method: 'POST',
                body: JSON.stringify({ action: 'REJECT', admin_name: 'Front Office Admin', reason })
            });
            if (res && res.success) {
                SecurityClient.showToast(res.message, 'info', 5000);
                const row = document.getElementById(`payment-row-${id}`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        row.remove();
                        if (pendingApprovals.pending_payments) {
                            pendingApprovals.pending_payments = pendingApprovals.pending_payments.filter(p => p.id !== id);
                        }
                        updateApprovalBadgeCounts();
                        if (!pendingApprovals.pending_payments || pendingApprovals.pending_payments.length === 0) {
                            refreshApprovalBox();
                        }
                    }, 300);
                } else {
                    loadAllAdminData();
                }
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to reject payment.', 'error');
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = I18n.t('btn_reject', '❌ Reject');
                }
            }
        } catch (err) {
            SecurityClient.showToast('Network error while rejecting payment.', 'error');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = I18n.t('btn_reject', '❌ Reject');
            }
        }
    }

    async function approveReturn(id, btnElement) {
        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '⏳ Verifying...';
        }
        try {
            const res = await SecurityClient.secureFetch(`/api/approvals/returns/${id}/action`, {
                method: 'POST',
                body: JSON.stringify({ action: 'APPROVE', admin_name: 'Front Office Admin' })
            });
            if (res && res.success) {
                SecurityClient.showToast(res.message, 'success', 5000);
                const row = document.getElementById(`return-row-${id}`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        row.remove();
                        if (pendingApprovals.pending_returns) {
                            pendingApprovals.pending_returns = pendingApprovals.pending_returns.filter(r => r.id !== id);
                        }
                        updateApprovalBadgeCounts();
                        if (!pendingApprovals.pending_returns || pendingApprovals.pending_returns.length === 0) {
                            refreshApprovalBox();
                        }
                    }, 300);
                } else {
                    loadAllAdminData();
                }
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to approve return.', 'error');
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = I18n.t('btn_verify_credit', '✅ Verify & Credit');
                }
            }
        } catch (err) {
            SecurityClient.showToast('Network error while verifying return.', 'error');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = I18n.t('btn_verify_credit', '✅ Verify & Credit');
            }
        }
    }

    async function approveAllReturns(btnElement) {
        const returns = pendingApprovals.pending_returns || [];
        if (returns.length === 0) return;

        const count = returns.length;
        const msg = I18n.getLanguage() === 'si'
            ? `Return සටහන් ${count} ම Warehouse එකට භෞතිකව ලැබී ඇති බව පරීක්ෂා කළාද? සියල්ලම එකවර Confirm කර Credit notes ක්‍රියාත්මක කරන්නද?`
            : `Confirm physical receipt of all ${count} returns at the warehouse to issue credit notes now?`;
        if (!confirm(msg)) return;

        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '⏳ Verifying All...';
        }
        try {
            const res = await SecurityClient.secureFetch('/api/approvals/returns/bulk-action', {
                method: 'POST',
                body: JSON.stringify({ action: 'APPROVE', admin_name: 'Front Office Admin' })
            });
            if (res && res.success) {
                SecurityClient.showToast(res.message, 'success', 6000);
                pendingApprovals.pending_returns = [];
                updateApprovalBadgeCounts();
                refreshApprovalBox();
                loadAllAdminData();
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to verify returns.', 'error');
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = `✅ ${I18n.t('btn_verify_all_returns', 'Verify All Returns (Batch Credit)')}`;
                }
            }
        } catch (err) {
            SecurityClient.showToast('Network error during batch verification.', 'error');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = `✅ ${I18n.t('btn_verify_all_returns', 'Verify All Returns (Batch Credit)')}`;
            }
        }
    }

    async function rejectReturn(id, btnElement) {
        const promptMsg = I18n.getLanguage() === 'si'
            ? 'Return Note එක ප්‍රතික්ෂේප කිරීමට හේතුව:'
            : 'Enter reason for rejecting this return note:';
        const reason = prompt(promptMsg, 'Physical damaged stock not returned or batch invalid');
        if (reason === null) return;

        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '⏳ Rejecting...';
        }
        try {
            const res = await SecurityClient.secureFetch(`/api/approvals/returns/${id}/action`, {
                method: 'POST',
                body: JSON.stringify({ action: 'REJECT', admin_name: 'Front Office Admin', reason })
            });
            if (res && res.success) {
                SecurityClient.showToast(res.message, 'info', 5000);
                const row = document.getElementById(`return-row-${id}`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        row.remove();
                        if (pendingApprovals.pending_returns) {
                            pendingApprovals.pending_returns = pendingApprovals.pending_returns.filter(r => r.id !== id);
                        }
                        updateApprovalBadgeCounts();
                        if (!pendingApprovals.pending_returns || pendingApprovals.pending_returns.length === 0) {
                            refreshApprovalBox();
                        }
                    }, 300);
                } else {
                    loadAllAdminData();
                }
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to reject return.', 'error');
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = I18n.t('btn_reject', '❌ Reject');
                }
            }
        } catch (err) {
            SecurityClient.showToast('Network error while rejecting return.', 'error');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = I18n.t('btn_reject', '❌ Reject');
            }
        }
    }

    async function approveShop(id, btnElement) {
        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '⏳ Approving...';
        }
        try {
            const res = await SecurityClient.secureFetch(`/api/approvals/shops/${id}/action`, {
                method: 'POST',
                body: JSON.stringify({ action: 'APPROVE', admin_name: 'Front Office Admin' })
            });
            if (res && res.success) {
                SecurityClient.showToast(res.message, 'success', 5000);
                const row = document.getElementById(`shop-row-${id}`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        row.remove();
                        if (pendingApprovals.pending_shops) {
                            pendingApprovals.pending_shops = pendingApprovals.pending_shops.filter(s => s.id !== id);
                        }
                        updateApprovalBadgeCounts();
                        if (!pendingApprovals.pending_shops || pendingApprovals.pending_shops.length === 0) {
                            refreshApprovalBox();
                        }
                    }, 300);
                } else {
                    loadAllAdminData();
                }
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to approve shop.', 'error');
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = I18n.t('btn_approve_activate', '✅ Approve & Activate');
                }
            }
        } catch (err) {
            SecurityClient.showToast('Network error while approving shop.', 'error');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = I18n.t('btn_approve_activate', '✅ Approve & Activate');
            }
        }
    }

    async function rejectShop(id, btnElement) {
        const promptMsg = I18n.getLanguage() === 'si'
            ? 'වෙළඳසැල ප්‍රතික්ෂේප කිරීමට හේතුව:'
            : 'Enter reason for rejecting this shop:';
        const reason = prompt(promptMsg, 'Shop registration details could not be verified');
        if (reason === null) return;

        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '⏳ Rejecting...';
        }
        try {
            const res = await SecurityClient.secureFetch(`/api/approvals/shops/${id}/action`, {
                method: 'POST',
                body: JSON.stringify({ action: 'REJECT', admin_name: 'Front Office Admin', reason })
            });
            if (res && res.success) {
                SecurityClient.showToast(res.message, 'info', 5000);
                const row = document.getElementById(`shop-row-${id}`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        row.remove();
                        if (pendingApprovals.pending_shops) {
                            pendingApprovals.pending_shops = pendingApprovals.pending_shops.filter(s => s.id !== id);
                        }
                        updateApprovalBadgeCounts();
                        if (!pendingApprovals.pending_shops || pendingApprovals.pending_shops.length === 0) {
                            refreshApprovalBox();
                        }
                    }, 300);
                } else {
                    loadAllAdminData();
                }
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to reject shop.', 'error');
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = I18n.t('btn_reject', '❌ Reject');
                }
            }
        } catch (err) {
            SecurityClient.showToast('Network error while rejecting shop.', 'error');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = I18n.t('btn_reject', '❌ Reject');
            }
        }
    }

    async function approveOrder(id, btnElement) {
        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '⏳ Confirming...';
        }
        try {
            const res = await SecurityClient.secureFetch(`/api/approvals/orders/${id}/action`, {
                method: 'POST',
                body: JSON.stringify({ action: 'APPROVE', admin_name: 'Front Office Admin' })
            });
            if (res && res.success) {
                SecurityClient.showToast(res.message, 'success', 5000);
                const row = document.getElementById(`order-row-${id}`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        row.remove();
                        if (pendingApprovals.pending_orders) {
                            pendingApprovals.pending_orders = pendingApprovals.pending_orders.filter(o => o.id !== id);
                        }
                        updateApprovalBadgeCounts();
                        if (!pendingApprovals.pending_orders || pendingApprovals.pending_orders.length === 0) {
                            refreshApprovalBox();
                        }
                    }, 300);
                } else {
                    loadAllAdminData();
                }
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to approve order.', 'error');
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = I18n.t('btn_confirm_order', '✅ Confirm Order');
                }
            }
        } catch (err) {
            SecurityClient.showToast('Network error while approving order.', 'error');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = I18n.t('btn_confirm_order', '✅ Confirm Order');
            }
        }
    }

    async function rejectOrder(id, btnElement) {
        const promptMsg = I18n.getLanguage() === 'si'
            ? 'Order එක ප්‍රතික්ෂේප කර තොග නැවත truck එකට හරවන්නද? හේතුව:'
            : 'Reject order and return stock to truck manifest? Enter reason:';
        const reason = prompt(promptMsg, 'Credit clearance rejected by Front Office Admin');
        if (reason === null) return;

        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '⏳ Rejecting...';
        }
        try {
            const res = await SecurityClient.secureFetch(`/api/approvals/orders/${id}/action`, {
                method: 'POST',
                body: JSON.stringify({ action: 'REJECT', admin_name: 'Front Office Admin', reason })
            });
            if (res && res.success) {
                SecurityClient.showToast(res.message, 'info', 5000);
                const row = document.getElementById(`order-row-${id}`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        row.remove();
                        if (pendingApprovals.pending_orders) {
                            pendingApprovals.pending_orders = pendingApprovals.pending_orders.filter(o => o.id !== id);
                        }
                        updateApprovalBadgeCounts();
                        if (!pendingApprovals.pending_orders || pendingApprovals.pending_orders.length === 0) {
                            refreshApprovalBox();
                        }
                    }, 300);
                } else {
                    loadAllAdminData();
                }
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to reject order.', 'error');
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = I18n.t('btn_reject', '❌ Reject');
                }
            }
        } catch (err) {
            SecurityClient.showToast('Network error while rejecting order.', 'error');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = I18n.t('btn_reject', '❌ Reject');
            }
        }
    }

    async function refreshData() {
        const btn = document.getElementById('adminRefreshBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '🔄 Syncing...';
        }
        await loadAllAdminData();
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `🔄 ${I18n.t('btn_refresh_admin', 'Refresh Data')}`;
        }
        SecurityClient.showToast(I18n.getLanguage() === 'si' ? '✅ තත්‍ය කාලීන දත්ත නැවුම් කරන ලදී' : '✅ Live Fleet & Telemetry refreshed', 'success', 2500);
    }

    function onRotateVehicleChange(vehicleId) {
        const v = vehiclesList.find(item => item.id == vehicleId);
        if (!v) return;
        const driverSelect = document.getElementById('rotateDriverSelect');
        const routeSelect = document.getElementById('rotateRouteSelect');
        if (driverSelect && v.current_driver_id) {
            driverSelect.value = v.current_driver_id;
        }
        if (routeSelect && v.current_route_id) {
            routeSelect.value = v.current_route_id;
        }
    }

    function openRotationModal() {
        const modal = document.getElementById('rotationModal');
        if (!modal) return;

        // Populate selects
        const vehicleSelect = document.getElementById('rotateVehicleSelect');
        const driverSelect = document.getElementById('rotateDriverSelect');
        const routeSelect = document.getElementById('rotateRouteSelect');

        vehicleSelect.innerHTML = vehiclesList.map(v => `<option value="${v.id}">${v.vehicle_number} (${v.vehicle_type})</option>`).join('');
        driverSelect.innerHTML = driversList.map(d => `<option value="${d.id}">${d.name} (${d.status})</option>`).join('');
        routeSelect.innerHTML = routesList.map(r => `<option value="${r.id}">${r.route_name}</option>`).join('');

        if (vehicleSelect.value) {
            onRotateVehicleChange(vehicleSelect.value);
        }

        modal.style.display = 'flex';
    }

    function closeRotationModal() {
        const modal = document.getElementById('rotationModal');
        if (modal) modal.style.display = 'none';
    }

    async function submitRotation() {
        const vehicleId = document.getElementById('rotateVehicleSelect').value;
        const driverId = document.getElementById('rotateDriverSelect').value;
        const routeId = document.getElementById('rotateRouteSelect').value;

        const res = await SecurityClient.secureFetch('/api/fleet/rotate', {
            method: 'POST',
            body: JSON.stringify({ vehicleId, driverId, routeId })
        });

        if (res && res.success) {
            SecurityClient.showToast('✅ Fleet and driver rotation saved successfully.', 'success');
            closeRotationModal();
            loadAllAdminData();
        } else {
            SecurityClient.showToast(res.error || 'Failed to rotate fleet.', 'error');
        }
    }

    function openDispatchRescueModal(sosId) {
        const modal = document.getElementById('dispatchRescueModal');
        if (!modal) return;
        modal.dataset.sosId = sosId;

        const vehicleSelect = document.getElementById('rescueVehicleSelect');
        const driverSelect = document.getElementById('rescueDriverSelect');

        // Filter available vehicles and drivers
        vehicleSelect.innerHTML = vehiclesList
            .filter(v => v.status !== 'BREAKDOWN')
            .map(v => `<option value="${v.id}">${v.vehicle_number} - ${v.vehicle_type}</option>`).join('');

        driverSelect.innerHTML = driversList
            .map(d => `<option value="${d.id}">${d.name} (${d.status})</option>`).join('');

        modal.style.display = 'flex';
    }

    function closeDispatchRescueModal() {
        const modal = document.getElementById('dispatchRescueModal');
        if (modal) modal.style.display = 'none';
    }

    async function submitDispatchRescue() {
        const modal = document.getElementById('dispatchRescueModal');
        const sosId = modal.dataset.sosId;
        const rescueVehicleId = document.getElementById('rescueVehicleSelect').value;
        const rescueDriverId = document.getElementById('rescueDriverSelect').value;
        const notes = document.getElementById('rescueNotesInput').value || 'Dispatched immediately from Polonnaruwa Depot';

        const res = await SecurityClient.secureFetch(`/api/sos/${sosId}/dispatch-rescue`, {
            method: 'POST',
            body: JSON.stringify({ rescueVehicleId, rescueDriverId, notes })
        });

        if (res && res.success) {
            SecurityClient.showToast('🚐 Rescue vehicle dispatched! Tracking live telemetry.', 'success');
            closeDispatchRescueModal();
            loadAllAdminData();
        } else {
            SecurityClient.showToast(res.error || 'Failed to dispatch rescue.', 'error');
        }
    }

    function openPaymentModal(shopId, shopName, currentBalance) {
        const modal = document.getElementById('shopPaymentModal');
        if (!modal) return;
        modal.dataset.shopId = shopId;
        document.getElementById('paymentShopName').textContent = shopName;
        document.getElementById('paymentCurrentBalance').textContent = SecurityClient.formatCurrency(currentBalance);
        document.getElementById('paymentAmountInput').value = currentBalance;
        modal.style.display = 'flex';
    }

    function closePaymentModal() {
        const modal = document.getElementById('shopPaymentModal');
        if (modal) modal.style.display = 'none';
    }

    async function submitShopPayment() {
        const modal = document.getElementById('shopPaymentModal');
        const shopId = modal.dataset.shopId;
        const paymentAmount = document.getElementById('paymentAmountInput').value;

        const res = await SecurityClient.secureFetch(`/api/shops/${shopId}/record-payment`, {
            method: 'POST',
            body: JSON.stringify({ paymentAmount, paymentMethod: 'CASH', admin_direct: true })
        });

        if (res && res.success) {
            SecurityClient.showToast(res.message, 'success');
            closePaymentModal();
            loadAllAdminData();
        } else {
            SecurityClient.showToast(res.error || 'Payment failed.', 'error');
        }
    }

    // ========================================================
    // SHOP CREDIT TIER & LIMIT MODAL CONTROLLER
    // ========================================================
    let editingShopCreditId = null;

    function openEditShopCreditModal(shopId) {
        const shop = shopsList.find(s => s.id === shopId);
        if (!shop) return;
        editingShopCreditId = shopId;

        const nameEl = document.getElementById('editShopCreditName');
        const phoneEl = document.getElementById('editShopCreditPhone');
        const balEl = document.getElementById('editShopCreditBalance');
        const tierSelect = document.getElementById('editShopCreditTier');
        const limitInput = document.getElementById('editShopCreditLimit');

        if (nameEl) nameEl.textContent = shop.name;
        if (phoneEl) phoneEl.textContent = `${shop.owner_name ? shop.owner_name + ' • ' : ''}${shop.phone || ''}`;
        if (balEl) balEl.textContent = SecurityClient.formatCurrency(shop.outstanding_balance || 0);
        if (tierSelect) tierSelect.value = shop.credit_tier || 'Tier B';
        if (limitInput) limitInput.value = shop.credit_limit || 0;

        const modal = document.getElementById('editShopCreditModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeEditShopCreditModal() {
        const modal = document.getElementById('editShopCreditModal');
        if (modal) modal.style.display = 'none';
        editingShopCreditId = null;
    }

    async function submitEditShopCredit() {
        if (!editingShopCreditId) return;
        const creditTier = document.getElementById('editShopCreditTier')?.value || 'Tier B';
        const creditLimit = parseFloat(document.getElementById('editShopCreditLimit')?.value || '0') || 0;

        try {
            const res = await SecurityClient.secureFetch(`/api/admin/shops/${editingShopCreditId}/credit`, {
                method: 'PUT',
                body: JSON.stringify({ credit_tier: creditTier, credit_limit: creditLimit })
            });

            if (res && res.success) {
                SecurityClient.showToast(res.message || '✅ Shop credit terms updated successfully!', 'success', 4000);
                closeEditShopCreditModal();
                loadAllAdminData();
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to update credit terms.', 'danger', 4000);
            }
        } catch(e) {
            SecurityClient.showToast('Network error updating credit terms.', 'danger');
        }
    }

    // ========================================================
    // MORNING LOAD DISPATCH CONTROLLER & CAPACITY GAUGE
    // ========================================================
    function openNewDispatchModal() {
        const modal = document.getElementById('newDispatchModal');
        if (!modal) return;

        const vehicleSelect = document.getElementById('dispVehicleSelect');
        const driverSelect = document.getElementById('dispDriverSelect');
        const repSelect = document.getElementById('dispSalesRepSelect');
        const routeSelect = document.getElementById('dispRouteSelect');

        if (vehicleSelect) {
            vehicleSelect.innerHTML = vehiclesList.map(v => {
                const badge = v.status === 'AVAILABLE' ? '🟢 Available' : (v.status === 'IN_TRANSIT' ? '🚚 In Transit' : '⚠️ ' + v.status);
                return `<option value="${v.id}" data-capacity="${v.capacity_cups || 2500}" data-driver="${v.current_driver_id || ''}" data-route="${v.current_route_id || ''}">${v.vehicle_number} (${v.vehicle_type || 'Cold Truck'}) - ${v.capacity_cups || 2500} cups [${badge}]</option>`;
            }).join('');
        }

        if (driverSelect) {
            driverSelect.innerHTML = driversList.map(d => `<option value="${d.id}">${d.name} (${d.status || 'Active'})</option>`).join('');
        }

        if (repSelect) {
            repSelect.innerHTML = salesRepsList.length > 0 
                ? salesRepsList.map(r => `<option value="${r.id}">${r.name} (${r.employee_code}) - ${r.assigned_route_sector || 'Polonnaruwa'}</option>`).join('')
                : '<option value="4">Field Sales Rep (Default)</option>';
        }

        if (routeSelect) {
            routeSelect.innerHTML = routesList.map(r => `<option value="${r.id}">${r.route_name}</option>`).join('');
        }

        modal.style.display = 'flex';

        // Trigger initial vehicle auto-fill & capacity gauge
        if (vehicleSelect && vehicleSelect.value) {
            onDispatchVehicleChange(vehicleSelect.value);
        } else {
            updateDispatchLoadTotal();
        }
    }

    function onDispatchVehicleChange(vehicleId) {
        const vehicle = vehiclesList.find(v => v.id == vehicleId);
        if (vehicle) {
            if (vehicle.current_driver_id) {
                const driverSelect = document.getElementById('dispDriverSelect');
                if (driverSelect) driverSelect.value = vehicle.current_driver_id;
            }
            if (vehicle.current_route_id) {
                const routeSelect = document.getElementById('dispRouteSelect');
                if (routeSelect) routeSelect.value = vehicle.current_route_id;
            }
        }
        updateDispatchLoadTotal();
    }

    function updateDispatchLoadTotal() {
        const v = parseInt(document.getElementById('dispVanilla')?.value || '0', 10) || 0;
        const s = parseInt(document.getElementById('dispStrawberry')?.value || '0', 10) || 0;
        const c = parseInt(document.getElementById('dispChocolate')?.value || '0', 10) || 0;
        const m = parseInt(document.getElementById('dispMango')?.value || '0', 10) || 0;
        const t = parseInt(document.getElementById('dispTreacle')?.value || '0', 10) || 0;
        const total = v + s + c + m + t;

        const vehicleSelect = document.getElementById('dispVehicleSelect');
        const selectedOption = vehicleSelect?.selectedOptions?.[0];
        const capacity = selectedOption ? parseInt(selectedOption.dataset.capacity || '2500', 10) : 2500;

        const totalEl = document.getElementById('dispTotalCupsDisplay');
        if (totalEl) totalEl.textContent = total.toLocaleString();

        const capEl = document.getElementById('dispVehicleCapacityDisplay');
        if (capEl) capEl.textContent = capacity.toLocaleString();

        const percent = Math.min(Math.round((total / (capacity || 1)) * 100), 100);
        const bar = document.getElementById('dispCapacityProgressBar');
        if (bar) {
            bar.style.width = percent + '%';
            if (total > capacity) {
                bar.style.background = '#ef4444';
            } else if (percent > 90) {
                bar.style.background = '#f59e0b';
            } else {
                bar.style.background = '#06b6d4';
            }
        }

        const warningEl = document.getElementById('dispCapacityWarning');
        if (warningEl) {
            warningEl.style.display = total > capacity ? 'block' : 'none';
        }
    }

    function closeNewDispatchModal() {
        const modal = document.getElementById('newDispatchModal');
        if (modal) modal.style.display = 'none';
    }

    async function submitNewDispatch() {
        const vehicleId = document.getElementById('dispVehicleSelect').value;
        const driverId = document.getElementById('dispDriverSelect').value;
        const salesRepId = document.getElementById('dispSalesRepSelect')?.value || 4;
        const routeId = document.getElementById('dispRouteSelect').value;

        const vanillaLoad = parseInt(document.getElementById('dispVanilla').value) || 0;
        const strawberryLoad = parseInt(document.getElementById('dispStrawberry').value) || 0;
        const chocolateLoad = parseInt(document.getElementById('dispChocolate').value) || 0;
        const mangoLoad = parseInt(document.getElementById('dispMango').value) || 0;
        const treaclePotLoad = parseInt(document.getElementById('dispTreacle').value) || 0;

        const totalCups = vanillaLoad + strawberryLoad + chocolateLoad + mangoLoad + treaclePotLoad;
        if (totalCups <= 0) {
            return SecurityClient.showToast('⚠️ Please enter load quantities greater than 0.', 'warning', 3500);
        }

        const payload = {
            vehicleId,
            driverId,
            salesRepId,
            routeId,
            vanillaLoad,
            strawberryLoad,
            chocolateLoad,
            mangoLoad,
            treaclePotLoad,
            notes: 'Morning Cold-Chain Dispatch'
        };

        const res = await SecurityClient.secureFetch('/api/dispatches/create', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (res && res.success) {
            SecurityClient.showToast(`✅ Dispatch manifest created! #${res.manifestNumber}`, 'success');
            closeNewDispatchModal();
            loadAllAdminData();
        } else {
            SecurityClient.showToast(res.error || 'Failed to create dispatch.', 'error');
        }
    }

    // ========================================================
    // MANIFEST DETAILS MODAL & PRINT CONTROLLER
    // ========================================================
    async function openManifestDetailsModal(id) {
        let dispatch = activeDispatches.find(d => d.id == id);
        const modal = document.getElementById('manifestDetailsModal');
        const modalBody = document.getElementById('manifestDetailsModalBody');
        if (!modal || !modalBody) return;

        modal.style.display = 'flex';
        modalBody.innerHTML = `<div style="text-align:center; padding:2rem; color:var(--text-muted);">Loading manifest #${id}...</div>`;

        try {
            const res = await SecurityClient.secureFetch(`/api/dispatches/${id}`);
            if (res && res.success && res.dispatch) {
                dispatch = res.dispatch;
            }
        } catch(e) {}

        if (!dispatch) {
            modalBody.innerHTML = `<div class="text-danger p-3">Failed to load manifest details.</div>`;
            return;
        }

        const percentRemaining = Math.round((dispatch.remaining_cups / (dispatch.total_loaded_cups || 1)) * 100);
        const percentDelivered = 100 - percentRemaining;

        modalBody.innerHTML = `
            <div class="manifest-print-card" id="printableManifestCard">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid var(--border-subtle); padding-bottom:12px; margin-bottom:12px;">
                    <div>
                        <h4 style="margin:0; font-size:1.15rem; color:var(--color-cyan); font-family:monospace;">${SecurityClient.escapeHtml(dispatch.manifest_number)}</h4>
                        <div class="text-xs text-muted" style="margin-top:2px;">📅 ${new Date(dispatch.created_at || dispatch.dispatch_date).toLocaleString()} &bull; Sector: <strong>${SecurityClient.escapeHtml(dispatch.region_sector || dispatch.route_name)}</strong></div>
                    </div>
                    <span class="badge ${dispatch.status === 'SOS_BREAKDOWN' ? 'badge-danger animate-pulse' : 'badge-primary'}" style="font-size:0.8rem; padding:4px 8px;">
                        ${SecurityClient.escapeHtml(dispatch.status)}
                    </span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:10px; margin-bottom:14px; background:var(--bg-surface-elevated); padding:10px; border-radius:8px;">
                    <div>
                        <div class="text-xs text-muted">Vehicle:</div>
                        <div class="font-bold text-sm font-mono text-cyan">${SecurityClient.escapeHtml(dispatch.vehicle_number)}</div>
                        <div class="text-xs text-muted">${SecurityClient.escapeHtml(dispatch.cooling_system_status === 'NORMAL_COOLING' ? '❄️ 4°C Optimal' : '⚠️ ' + (dispatch.cooling_system_status || ''))}</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted">Assigned Driver:</div>
                        <div class="font-bold text-sm">${SecurityClient.escapeHtml(dispatch.driver_name)}</div>
                        <div class="text-xs text-muted font-mono">${SecurityClient.escapeHtml(dispatch.driver_phone || '--')}</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted">Sales Representative:</div>
                        <div class="font-bold text-sm">${SecurityClient.escapeHtml(dispatch.sales_rep_name || 'Field Sales Rep')}</div>
                        <div class="text-xs text-muted">Route: ${SecurityClient.escapeHtml(dispatch.route_name || '--')}</div>
                    </div>
                </div>

                <h5 class="text-xs font-bold text-muted uppercase" style="letter-spacing:0.05em; margin-bottom:6px;">Inventory & Flavor Breakdown:</h5>
                <div class="table-responsive">
                    <table class="table eyecare-table text-xs" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Flavor Item</th>
                                <th style="text-align:right;">Loaded</th>
                                <th style="text-align:right;">Sold</th>
                                <th style="text-align:right;">Damaged</th>
                                <th style="text-align:right;">Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>🥛 Vanilla Yogurt (80g)</td>
                                <td style="text-align:right; font-weight:bold;">${dispatch.vanilla_load || 0}</td>
                                <td style="text-align:right; color:var(--color-cash);">${Math.round((dispatch.sold_cups || 0) * 0.4)}</td>
                                <td style="text-align:right; color:var(--color-sos);">${Math.round((dispatch.damaged_cups || 0) * 0.4)}</td>
                                <td style="text-align:right; font-weight:bold; color:var(--color-info);">${Math.max(0, (dispatch.vanilla_load || 0) - Math.round((dispatch.sold_cups || 0) * 0.4))}</td>
                            </tr>
                            <tr>
                                <td>🍓 Strawberry Yogurt (80g)</td>
                                <td style="text-align:right; font-weight:bold;">${dispatch.strawberry_load || 0}</td>
                                <td style="text-align:right; color:var(--color-cash);">${Math.round((dispatch.sold_cups || 0) * 0.25)}</td>
                                <td style="text-align:right; color:var(--color-sos);">${Math.round((dispatch.damaged_cups || 0) * 0.25)}</td>
                                <td style="text-align:right; font-weight:bold; color:var(--color-info);">${Math.max(0, (dispatch.strawberry_load || 0) - Math.round((dispatch.sold_cups || 0) * 0.25))}</td>
                            </tr>
                            <tr>
                                <td>🍫 Chocolate Yogurt (80g)</td>
                                <td style="text-align:right; font-weight:bold;">${dispatch.chocolate_load || 0}</td>
                                <td style="text-align:right; color:var(--color-cash);">${Math.round((dispatch.sold_cups || 0) * 0.15)}</td>
                                <td style="text-align:right; color:var(--color-sos);">${Math.round((dispatch.damaged_cups || 0) * 0.15)}</td>
                                <td style="text-align:right; font-weight:bold; color:var(--color-info);">${Math.max(0, (dispatch.chocolate_load || 0) - Math.round((dispatch.sold_cups || 0) * 0.15))}</td>
                            </tr>
                            <tr>
                                <td>🥭 Mango Yogurt (80g)</td>
                                <td style="text-align:right; font-weight:bold;">${dispatch.mango_load || 0}</td>
                                <td style="text-align:right; color:var(--color-cash);">${Math.round((dispatch.sold_cups || 0) * 0.12)}</td>
                                <td style="text-align:right; color:var(--color-sos);">${Math.round((dispatch.damaged_cups || 0) * 0.12)}</td>
                                <td style="text-align:right; font-weight:bold; color:var(--color-info);">${Math.max(0, (dispatch.mango_load || 0) - Math.round((dispatch.sold_cups || 0) * 0.12))}</td>
                            </tr>
                            <tr>
                                <td>🍯 Curd & Treacle Pot (180g)</td>
                                <td style="text-align:right; font-weight:bold;">${dispatch.treacle_pot_load || 0}</td>
                                <td style="text-align:right; color:var(--color-cash);">${Math.round((dispatch.sold_cups || 0) * 0.08)}</td>
                                <td style="text-align:right; color:var(--color-sos);">${Math.round((dispatch.damaged_cups || 0) * 0.08)}</td>
                                <td style="text-align:right; font-weight:bold; color:var(--color-info);">${Math.max(0, (dispatch.treacle_pot_load || 0) - Math.round((dispatch.sold_cups || 0) * 0.08))}</td>
                            </tr>
                            <tr style="border-top:2px solid var(--border-medium); font-weight:bold;">
                                <td>Total Overall Cups</td>
                                <td style="text-align:right; color:var(--text-heading); font-size:0.85rem;">${dispatch.total_loaded_cups}</td>
                                <td style="text-align:right; color:var(--color-cash); font-size:0.85rem;">${dispatch.sold_cups || 0}</td>
                                <td style="text-align:right; color:var(--color-sos); font-size:0.85rem;">${dispatch.damaged_cups || 0}</td>
                                <td style="text-align:right; color:var(--color-cyan); font-size:0.85rem;">${dispatch.remaining_cups}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:12px;">
                    <div class="flex-between text-xs" style="margin-bottom:4px;">
                        <span>Delivery Completion: <strong>${percentDelivered}%</strong></span>
                        <span>Remaining On Fleet: <strong>${dispatch.remaining_cups} cups (${percentRemaining}%)</strong></span>
                    </div>
                    <div class="progress-bar-track" style="height:8px; border-radius:4px; overflow:hidden;">
                        <div class="progress-bar-fill" style="width:${percentDelivered}%; background:linear-gradient(90deg, #10b981, #06b6d4);"></div>
                    </div>
                </div>

                ${dispatch.notes ? `
                    <div class="text-xs text-muted" style="margin-top:10px; font-style:italic;">
                        Notes: ${SecurityClient.escapeHtml(dispatch.notes)}
                    </div>
                ` : ''}
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-sm btn-outline" onclick="AdminDashboard.closeManifestDetailsModal()">
                    Close
                </button>
                <button type="button" class="btn btn-sm btn-primary font-bold" onclick="AdminDashboard.printManifest(${dispatch.id})">
                    🖨️ ${I18n.t('manifest_print_btn', 'Print Manifest')}
                </button>
            </div>
        `;
    }

    function closeManifestDetailsModal() {
        const modal = document.getElementById('manifestDetailsModal');
        if (modal) modal.style.display = 'none';
    }

    function printManifest(dispatchId) {
        const printContent = document.getElementById('printableManifestCard');
        if (!printContent) return;

        const printWindow = window.open('', '_blank', 'width=700,height=800');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Manifest #${dispatchId} - PulseERP Polonnaruwa</title>
                    <style>
                        body { font-family: monospace, sans-serif; padding: 20px; color: #111; line-height: 1.5; }
                        h4 { font-size: 1.2rem; margin-bottom: 4px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 12px; }
                        th { background: #f0f0f0; }
                        .text-right { text-align: right; }
                        .badge { display: inline-block; padding: 2px 6px; border: 1px solid #333; font-size: 10px; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div style="text-align:center; border-bottom:2px dashed #333; padding-bottom:10px; margin-bottom:15px;">
                        <h2 style="margin:0;">PULSE ERP • POLONNARUWA DAIRY</h2>
                        <div style="font-size:12px;">Daily Fleet Delivery Manifest & Inventory Gate Pass</div>
                    </div>
                    ${printContent.innerHTML}
                    <div style="margin-top:30px; display:flex; justify-content:space-between; font-size:12px; border-top:1px dashed #aaa; padding-top:20px;">
                        <div>________________________<br>Dispatcher Signature</div>
                        <div>________________________<br>Driver Acknowledgment</div>
                        <div>________________________<br>Front Office Clearance</div>
                    </div>
                    <script>window.onload = function() { window.print(); }<\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }

    function openTransferHandshakeModal(sosId) {
        const targetAlert = sosAlerts.find(a => a.id == sosId) || (sosAlerts.length > 0 ? sosAlerts[0] : null);
        App.openTransferHandshakeModal(sosId, targetAlert);
    }

    // ========================================================
    // FLEET & STAFF MANAGEMENT COMPONENT
    // ========================================================
    function renderFleetStaffManagementHub() {
        return `
            <div class="card eyecare-card" id="fleetStaffManagementCard">
                <div class="card-header flex-between" style="flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h3 style="display:flex; align-items:center; gap:8px;">
                            <span>⚙️</span> ${I18n.t('fleet_staff_hub_title', 'Fleet & Staff Management Hub')}
                        </h3>
                        <p class="text-xs text-muted" style="margin-top: 2px;">
                            ${I18n.t('fleet_hub_sub', 'Live vehicle rotation, certified drivers, and field representatives')}
                        </p>
                    </div>
                </div>

                <div class="approval-nav-tabs" style="margin: 0.5rem 1rem 0;">
                    <button type="button" class="btn approval-tab-btn ${activeStaffTab === 'vehicles' ? 'active' : ''}" onclick="AdminDashboard.switchStaffTab('vehicles')">
                        <span>🚐</span>
                        <span>${I18n.t('tab_vehicles', 'Fleet & Rotation')}</span>
                        <span class="approval-tab-pill">${vehiclesList.length}</span>
                    </button>
                    <button type="button" class="btn approval-tab-btn ${activeStaffTab === 'drivers' ? 'active' : ''}" onclick="AdminDashboard.switchStaffTab('drivers')">
                        <span>🚚</span>
                        <span>${I18n.t('tab_drivers', 'Drivers Roster')}</span>
                        <span class="approval-tab-pill">${driversList.length}</span>
                    </button>
                    <button type="button" class="btn approval-tab-btn ${activeStaffTab === 'reps' ? 'active' : ''}" onclick="AdminDashboard.switchStaffTab('reps')">
                        <span>📱</span>
                        <span>${I18n.t('tab_reps', 'Sales Representatives')}</span>
                        <span class="approval-tab-pill">${salesRepsList.length}</span>
                    </button>
                    <button type="button" class="btn approval-tab-btn ${activeStaffTab === 'routes' ? 'active' : ''}" onclick="AdminDashboard.switchStaffTab('routes')">
                        <span>🗺️</span>
                        <span>${I18n.t('tab_routes', 'Delivery Routes')}</span>
                        <span class="approval-tab-pill">${routesList.length}</span>
                    </button>
                </div>

                <div style="padding: 1rem;">
                    ${renderStaffTabContent()}
                </div>
            </div>
        `;
    }

    function renderStaffTabContent() {
        if (activeStaffTab === 'vehicles') {
            const filtered = !vehicleSearchQuery ? vehiclesList : vehiclesList.filter(v => 
                (v.vehicle_number && v.vehicle_number.toLowerCase().includes(vehicleSearchQuery)) ||
                (v.vehicle_type && v.vehicle_type.toLowerCase().includes(vehicleSearchQuery)) ||
                (v.driver_name && v.driver_name.toLowerCase().includes(vehicleSearchQuery)) ||
                (v.route_name && v.route_name.toLowerCase().includes(vehicleSearchQuery)) ||
                (v.status && v.status.toLowerCase().includes(vehicleSearchQuery))
            );

            return `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 0.75rem; flex-wrap:wrap; gap:8px;">
                    <input type="text" id="vehicleSearchInput" class="form-control" placeholder="${I18n.t('search_vehicles_placeholder', 'Search plate, driver, route, status...')}" value="${vehicleSearchQuery}" oninput="AdminDashboard.filterVehicles(this.value)" style="max-width:240px; font-size:0.8rem; padding:5px 10px;">
                    <div class="card-header-toolbar">
                        <button class="btn btn-xs btn-outline-primary font-bold" onclick="AdminDashboard.openRotationModal()">
                            ${I18n.t('btn_edit_rotation', '🔄 Rotate Fleet')}
                        </button>
                        <button class="btn btn-xs btn-primary font-bold" onclick="AdminDashboard.openRegisterVehicleModal()">
                            ${I18n.t('btn_register_vehicle', '🚐 + Add Vehicle')}
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table eyecare-table text-sm">
                        <thead>
                            <tr>
                                <th>${I18n.t('th_vehicle_no', 'Vehicle No')}</th>
                                <th>${I18n.t('th_type_capacity', 'Type & Capacity')}</th>
                                <th>${I18n.t('th_assigned_driver', 'Assigned Driver')}</th>
                                <th>${I18n.t('th_assigned_route', 'Assigned Route')}</th>
                                <th>${I18n.t('th_chiller_status', 'Chiller Status')}</th>
                                <th>${I18n.t('th_state', 'State')}</th>
                                <th>${I18n.t('th_action', 'Action')}</th>
                            </tr>
                        </thead>
                        <tbody id="fleetVehiclesTableBody">
                            ${renderVehicleRows(filtered)}
                        </tbody>
                    </table>
                </div>
            `;
        } else if (activeStaffTab === 'drivers') {
            const filtered = !driverSearchQuery ? driversList : driversList.filter(d => 
                (d.name && d.name.toLowerCase().includes(driverSearchQuery)) ||
                (d.phone && d.phone.includes(driverSearchQuery)) ||
                (d.license_number && d.license_number.toLowerCase().includes(driverSearchQuery)) ||
                (d.status && d.status.toLowerCase().includes(driverSearchQuery))
            );

            return `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 0.75rem; flex-wrap:wrap; gap:8px;">
                    <input type="text" id="driverSearchInput" class="form-control" placeholder="${I18n.t('search_drivers_placeholder', 'Search driver name, phone, license...')}" value="${driverSearchQuery}" oninput="AdminDashboard.filterDrivers(this.value)" style="max-width:240px; font-size:0.8rem; padding:5px 10px;">
                    <div class="card-header-toolbar">
                        <button class="btn btn-xs btn-brand font-bold" onclick="AdminDashboard.openRegisterDriverModal()">
                            ${I18n.t('btn_register_driver', '🚚 + Add Driver')}
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table eyecare-table text-sm">
                        <thead>
                            <tr>
                                <th>${I18n.t('th_driver_name', 'Driver Name')}</th>
                                <th>${I18n.t('th_mobile_phone', 'Mobile Phone')}</th>
                                <th>${I18n.t('th_license_nic', 'License & NIC')}</th>
                                <th>${I18n.t('th_assigned_truck', 'Assigned Truck')}</th>
                                <th>${I18n.t('th_status', 'Status')}</th>
                                <th>${I18n.t('th_actions', 'Actions')}</th>
                            </tr>
                        </thead>
                        <tbody id="fleetDriversTableBody">
                            ${renderDriverRows(filtered)}
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            const filtered = !repSearchQuery ? salesRepsList : salesRepsList.filter(r => 
                (r.name && r.name.toLowerCase().includes(repSearchQuery)) ||
                (r.employee_code && r.employee_code.toLowerCase().includes(repSearchQuery)) ||
                (r.phone && r.phone.includes(repSearchQuery)) ||
                (r.assigned_route_sector && r.assigned_route_sector.toLowerCase().includes(repSearchQuery))
            );

            return `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 0.75rem; flex-wrap:wrap; gap:8px;">
                    <input type="text" id="repSearchInput" class="form-control" placeholder="${I18n.t('search_reps_placeholder', 'Search rep code, name, sector...')}" value="${repSearchQuery}" oninput="AdminDashboard.filterReps(this.value)" style="max-width:240px; font-size:0.8rem; padding:5px 10px;">
                    <div class="card-header-toolbar">
                        <button class="btn btn-xs btn-success font-bold" onclick="AdminDashboard.openRegisterSalesRepModal()">
                            ${I18n.t('btn_register_rep', '📱 + Add Sales Rep')}
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table eyecare-table text-sm">
                        <thead>
                            <tr>
                                <th>${I18n.t('th_emp_code', 'Employee Code')}</th>
                                <th>${I18n.t('th_rep_name', 'Representative Name')}</th>
                                <th>${I18n.t('th_phone_number', 'Phone Number')}</th>
                                <th>${I18n.t('th_assigned_sector', 'Assigned Route Sector')}</th>
                                <th>${I18n.t('th_commission_pct', 'Commission %')}</th>
                                <th>${I18n.t('th_status', 'Status')}</th>
                                <th>${I18n.t('th_actions', 'Actions')}</th>
                            </tr>
                        </thead>
                        <tbody id="fleetRepsTableBody">
                            ${renderRepRows(filtered)}
                        </tbody>
                    </table>
                </div>
            `;
        } else if (activeStaffTab === 'routes') {
            const filtered = !routeSearchQuery ? routesList : routesList.filter(r => 
                (r.route_name && r.route_name.toLowerCase().includes(routeSearchQuery)) ||
                (r.region_sector && r.region_sector.toLowerCase().includes(routeSearchQuery)) ||
                (r.primary_destination && r.primary_destination.toLowerCase().includes(routeSearchQuery)) ||
                (r.status && r.status.toLowerCase().includes(routeSearchQuery))
            );

            return `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 0.75rem; flex-wrap:wrap; gap:8px;">
                    <input type="text" id="routeSearchInput" class="form-control" placeholder="${I18n.t('search_routes_placeholder', 'Search route, sector, destination...')}" value="${routeSearchQuery}" oninput="AdminDashboard.filterRoutes(this.value)" style="max-width:260px; font-size:0.8rem; padding:5px 10px;">
                    <div class="card-header-toolbar">
                        <button class="btn btn-xs btn-primary font-bold" onclick="AdminDashboard.openRegisterRouteModal()">
                            ${I18n.t('btn_register_route', '🗺️ + Add Route')}
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table eyecare-table text-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>${I18n.t('th_route_name', 'Route Corridor')}</th>
                                <th>${I18n.t('th_route_sector', 'Region Sector')}</th>
                                <th>${I18n.t('th_destination', 'Primary Destination')}</th>
                                <th>${I18n.t('th_distance_km', 'Distance (KM)')}</th>
                                <th>${I18n.t('th_shop_count', 'Active Shops')}</th>
                                <th>${I18n.t('th_status', 'Status')}</th>
                                <th>${I18n.t('th_actions', 'Actions')}</th>
                            </tr>
                        </thead>
                        <tbody id="fleetRoutesTableBody">
                            ${renderRouteRows(filtered)}
                        </tbody>
                    </table>
                </div>
            `;
        }
    }

    function switchStaffTab(tab) {
        activeStaffTab = tab;
        const container = document.getElementById('fleetStaffManagementSection');
        if (container) {
            container.innerHTML = renderFleetStaffManagementHub();
        } else {
            render();
        }
    }

    function filterVehicles(query) {
        vehicleSearchQuery = (query || '').toLowerCase().trim();
        const tbody = document.getElementById('fleetVehiclesTableBody');
        if (!tbody) return;
        const filtered = !vehicleSearchQuery ? vehiclesList : vehiclesList.filter(v => 
            (v.vehicle_number && v.vehicle_number.toLowerCase().includes(vehicleSearchQuery)) ||
            (v.vehicle_type && v.vehicle_type.toLowerCase().includes(vehicleSearchQuery)) ||
            (v.driver_name && v.driver_name.toLowerCase().includes(vehicleSearchQuery)) ||
            (v.route_name && v.route_name.toLowerCase().includes(vehicleSearchQuery)) ||
            (v.status && v.status.toLowerCase().includes(vehicleSearchQuery))
        );
        tbody.innerHTML = renderVehicleRows(filtered);
    }

    function filterDrivers(query) {
        driverSearchQuery = (query || '').toLowerCase().trim();
        const tbody = document.getElementById('fleetDriversTableBody');
        if (!tbody) return;
        const filtered = !driverSearchQuery ? driversList : driversList.filter(d => 
            (d.name && d.name.toLowerCase().includes(driverSearchQuery)) ||
            (d.phone && d.phone.includes(driverSearchQuery)) ||
            (d.license_number && d.license_number.toLowerCase().includes(driverSearchQuery)) ||
            (d.status && d.status.toLowerCase().includes(driverSearchQuery))
        );
        tbody.innerHTML = renderDriverRows(filtered);
    }

    function filterReps(query) {
        repSearchQuery = (query || '').toLowerCase().trim();
        const tbody = document.getElementById('fleetRepsTableBody');
        if (!tbody) return;
        const filtered = !repSearchQuery ? salesRepsList : salesRepsList.filter(r => 
            (r.name && r.name.toLowerCase().includes(repSearchQuery)) ||
            (r.employee_code && r.employee_code.toLowerCase().includes(repSearchQuery)) ||
            (r.phone && r.phone.includes(repSearchQuery)) ||
            (r.assigned_route_sector && r.assigned_route_sector.toLowerCase().includes(repSearchQuery))
        );
        tbody.innerHTML = renderRepRows(filtered);
    }

    function filterRoutes(query) {
        routeSearchQuery = (query || '').toLowerCase().trim();
        const tbody = document.getElementById('fleetRoutesTableBody');
        if (!tbody) return;
        const filtered = !routeSearchQuery ? routesList : routesList.filter(r => 
            (r.route_name && r.route_name.toLowerCase().includes(routeSearchQuery)) ||
            (r.region_sector && r.region_sector.toLowerCase().includes(routeSearchQuery)) ||
            (r.primary_destination && r.primary_destination.toLowerCase().includes(routeSearchQuery)) ||
            (r.status && r.status.toLowerCase().includes(routeSearchQuery))
        );
        tbody.innerHTML = renderRouteRows(filtered);
    }

    function filterDispatches(query) {
        const q = (query || '').toLowerCase().trim();
        const tbody = document.getElementById('dispatchesTableBody');
        if (!tbody) return;
        const filtered = !q ? activeDispatches : activeDispatches.filter(d => 
            (d.manifest_number && d.manifest_number.toLowerCase().includes(q)) ||
            (d.vehicle_number && d.vehicle_number.toLowerCase().includes(q)) ||
            (d.driver_name && d.driver_name.toLowerCase().includes(q)) ||
            (d.region_sector && d.region_sector.toLowerCase().includes(q))
        );
        tbody.innerHTML = renderDispatchRows(filtered);
    }

    function filterRotation(query) {
        filterVehicles(query);
    }

    function filterShopRisk(query) {
        const q = (query || '').toLowerCase().trim();
        const tbody = document.getElementById('shopRiskTableBody');
        if (!tbody) return;
        const filtered = !q ? shopsList : shopsList.filter(s => 
            (s.name && s.name.toLowerCase().includes(q)) ||
            (s.phone && s.phone.includes(q)) ||
            (s.credit_tier && s.credit_tier.toLowerCase().includes(q))
        );
        tbody.innerHTML = renderShopRiskRows(filtered);
    }

    function renderVehicleRows(list) {
        if (!list || list.length === 0) {
            return `<tr><td colspan="7" style="text-align:center; padding:1.5rem; color:var(--text-muted);">No vehicles match your search.</td></tr>`;
        }
        return list.map(v => `
            <tr>
                <td><strong class="font-mono text-cyan" style="font-size:0.92rem;">${SecurityClient.escapeHtml(v.vehicle_number)}</strong></td>
                <td>
                    <div>${SecurityClient.escapeHtml(v.vehicle_type)}</div>
                    <div class="text-xs text-muted"><span class="font-mono">${v.capacity_cups ? v.capacity_cups.toLocaleString() : '2,500'}</span> cups</div>
                </td>
                <td>
                    <strong>${SecurityClient.escapeHtml(v.driver_name || 'Standby Driver')}</strong>
                </td>
                <td>
                    <span class="badge badge-outline" style="font-size:0.75rem;">${SecurityClient.escapeHtml(v.route_name || 'Depot Reserve')}</span>
                </td>
                <td>
                    <span class="text-xs ${v.cooling_system_status === 'NORMAL_COOLING' ? 'text-success' : 'text-danger font-bold'}">
                        ${v.cooling_system_status === 'NORMAL_COOLING' ? '❄️ 4°C Optimal' : '⚠️ ' + SecurityClient.escapeHtml(v.cooling_system_status || 'FAULT')}
                    </span>
                </td>
                <td>
                    <span class="badge ${v.status === 'IN_TRANSIT' ? 'badge-primary' : (v.status === 'BREAKDOWN' ? 'badge-danger' : 'badge-secondary')}" style="font-size:0.72rem;">
                        ${SecurityClient.escapeHtml(v.status)}
                    </span>
                </td>
                <td>
                    <div style="display:flex; align-items:center; gap:5px;">
                        <button class="btn btn-xs btn-outline-primary" title="Edit Vehicle" onclick="AdminDashboard.openEditVehicleModal(${v.id})">
                            ✏️ Edit
                        </button>
                        <button class="btn btn-xs btn-outline-danger" title="Remove Vehicle" onclick="AdminDashboard.deleteVehicle(${v.id}, '${SecurityClient.escapeHtml(v.vehicle_number)}')">
                            🗑️
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderDriverRows(list) {
        if (!list || list.length === 0) {
            return `<tr><td colspan="6" style="text-align:center; padding:1.5rem; color:var(--text-muted);">No drivers match your search.</td></tr>`;
        }
        return list.map(d => `
            <tr>
                <td><strong>${SecurityClient.escapeHtml(d.name)}</strong></td>
                <td class="font-mono">${SecurityClient.escapeHtml(d.phone || '--')}</td>
                <td>
                    <div>Lic: <span class="font-mono">${SecurityClient.escapeHtml(d.license_number || '--')}</span></div>
                    <div class="text-xs text-muted">NIC: ${SecurityClient.escapeHtml(d.nic_number || '--')}</div>
                </td>
                <td>${SecurityClient.escapeHtml(d.vehicle_number || 'Standby Spare')}</td>
                <td>
                    <span class="badge ${d.status === 'ACTIVE' ? 'badge-success' : (d.status === 'ON_TRIP' ? 'badge-primary' : 'badge-secondary')}">
                        ${SecurityClient.escapeHtml(d.status)}
                    </span>
                </td>
                <td>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <button class="btn btn-xs btn-outline-primary" onclick="AdminDashboard.openEditDriverModal(${d.id})">
                            ✏️ Edit
                        </button>
                        <button class="btn btn-xs btn-outline-danger" onclick="AdminDashboard.deleteDriver(${d.id}, '${SecurityClient.escapeHtml(d.name)}')">
                            🗑️ Delete
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderRepRows(list) {
        if (!list || list.length === 0) {
            return `<tr><td colspan="7" style="text-align:center; padding:1.5rem; color:var(--text-muted);">No sales representatives match your search.</td></tr>`;
        }
        return list.map(r => `
            <tr>
                <td><span class="badge badge-brand font-mono">${SecurityClient.escapeHtml(r.employee_code)}</span></td>
                <td><strong>${SecurityClient.escapeHtml(r.name)}</strong></td>
                <td class="font-mono">${SecurityClient.escapeHtml(r.phone)}</td>
                <td>${SecurityClient.escapeHtml(r.assigned_route_sector || 'Polonnaruwa Sector')}</td>
                <td><span class="font-mono font-bold">${r.commission_rate_percent || 3.5}%</span></td>
                <td>
                    <span class="badge ${r.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary'}">
                        ${SecurityClient.escapeHtml(r.status)}
                    </span>
                </td>
                <td>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <button class="btn btn-xs btn-outline-primary" onclick="AdminDashboard.openEditSalesRepModal(${r.id})">
                            ✏️ Edit
                        </button>
                        <button class="btn btn-xs btn-outline-danger" onclick="AdminDashboard.deleteSalesRep(${r.id}, '${SecurityClient.escapeHtml(r.name)}')">
                            🗑️ Delete
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderRouteRows(list) {
        if (!list || list.length === 0) {
            return `<tr><td colspan="8" style="text-align:center; padding:1.5rem; color:var(--text-muted);">No delivery routes match your search.</td></tr>`;
        }
        return list.map(r => `
            <tr>
                <td><span class="badge badge-outline">#${r.id}</span></td>
                <td>
                    <strong class="text-primary" style="font-size:0.92rem;">${SecurityClient.escapeHtml(r.route_name)}</strong>
                    <div class="text-xs text-muted">Vehicle: ${r.vehicle_number ? SecurityClient.escapeHtml(r.vehicle_number) : 'Unassigned'}</div>
                </td>
                <td><strong>${SecurityClient.escapeHtml(r.region_sector)}</strong></td>
                <td>${SecurityClient.escapeHtml(r.primary_destination || 'N/A')}</td>
                <td><span class="font-mono font-bold">${parseFloat(r.estimated_distance_km || 0).toFixed(1)}</span> km</td>
                <td><span class="badge badge-primary">${r.shop_count || 0} shops</span></td>
                <td>
                    <span class="badge ${r.status === 'ACTIVE' ? 'badge-success' : 'badge-outline'}">${SecurityClient.escapeHtml(r.status || 'ACTIVE')}</span>
                </td>
                <td>
                    <div class="table-actions" style="display:flex; gap:6px;">
                        <button class="btn btn-xs btn-outline-primary font-bold" onclick="AdminDashboard.openEditRouteModal(${r.id})" title="Edit Route">
                            ✏️ Edit
                        </button>
                        <button class="btn btn-xs btn-outline-danger font-bold" onclick="AdminDashboard.deleteRoute(${r.id}, '${SecurityClient.escapeHtml(r.route_name)}')" title="Delete Route">
                            🗑️
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderDispatchRows(list) {
        if (!list || list.length === 0) {
            return `<tr><td colspan="7" style="text-align:center; padding:1.5rem; color:var(--text-muted);">No manifests found.</td></tr>`;
        }
        return list.map(d => {
            const percentRemaining = Math.round((d.remaining_cups / (d.total_loaded_cups || 1)) * 100);
            return `
                <tr>
                    <td><strong>${SecurityClient.escapeHtml(d.manifest_number)}</strong></td>
                    <td>
                        <div>${SecurityClient.escapeHtml(d.vehicle_number)}</div>
                        <div class="text-xs text-muted">${SecurityClient.escapeHtml(d.driver_name)}</div>
                    </td>
                    <td>${SecurityClient.escapeHtml(d.region_sector)}</td>
                    <td>${d.total_loaded_cups}</td>
                    <td>
                        <div><strong>${d.remaining_cups}</strong> (${percentRemaining}%)</div>
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill" style="width: ${percentRemaining}%"></div>
                        </div>
                    </td>
                    <td>
                        <span class="badge ${d.status === 'SOS_BREAKDOWN' ? 'badge-danger animate-pulse' : 'badge-primary'}">
                            ${SecurityClient.escapeHtml(d.status)}
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <button class="btn btn-xs btn-outline-cyan font-bold" onclick="AdminDashboard.openManifestDetailsModal(${d.id})" title="View Manifest & Stock Breakdown">
                            📋 ${I18n.t('btn_view_manifest', 'Manifest')}
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderRotationRows(list) {
        if (!list || list.length === 0) {
            return `<tr><td colspan="6" style="text-align:center; padding:1.5rem; color:var(--text-muted);">No rotation entries.</td></tr>`;
        }
        return list.map(v => `
            <tr>
                <td><strong>${SecurityClient.escapeHtml(v.vehicle_number)}</strong></td>
                <td>${SecurityClient.escapeHtml(v.vehicle_type)} (${v.capacity_cups} cups)</td>
                <td>${SecurityClient.escapeHtml(v.driver_name || 'Standby Driver')}</td>
                <td>${SecurityClient.escapeHtml(v.route_name || 'Depot Reserve')}</td>
                <td>
                    <span class="text-xs ${v.cooling_system_status === 'NORMAL_COOLING' ? 'text-success' : 'text-danger font-bold'}">
                        ${v.cooling_system_status === 'NORMAL_COOLING' ? I18n.t('chiller_temp_optimal', '❄️ 4°C Optimal') : I18n.t('chiller_temp_fault', '⚠️ FAULT')}
                    </span>
                </td>
                <td>
                    <span class="badge ${v.status === 'IN_TRANSIT' ? 'badge-primary' : (v.status === 'BREAKDOWN' ? 'badge-danger' : 'badge-secondary')}">
                        ${SecurityClient.escapeHtml(v.status)}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    function renderShopRiskRows(list) {
        if (!list || list.length === 0) {
            return `<tr><td colspan="6" style="text-align:center; padding:1.5rem; color:var(--text-muted);">No shops found.</td></tr>`;
        }
        return list.map(s => {
            let tierBadge = 'badge-primary';
            if (s.credit_tier === 'Tier A') tierBadge = 'badge-success';
            if (s.credit_tier === 'Tier B') tierBadge = 'badge-warning';
            if (s.credit_tier === 'Tier C') tierBadge = 'badge-danger';
            if (s.credit_tier === 'Cash Only') tierBadge = 'badge-secondary';

            return `
                <tr>
                    <td>
                        <strong>${SecurityClient.escapeHtml(s.name)}</strong>
                        <div class="text-xs text-muted">${SecurityClient.escapeHtml(s.phone)}</div>
                    </td>
                    <td><span class="badge ${tierBadge}">${s.credit_tier}</span></td>
                    <td>${SecurityClient.formatCurrency(s.credit_limit)}</td>
                    <td class="${s.outstanding_balance > 0 ? 'text-danger font-bold' : ''}">
                        ${SecurityClient.formatCurrency(s.outstanding_balance)}
                    </td>
                    <td>${s.payment_rating_percent}%</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <button class="btn btn-xs btn-outline-success font-bold" onclick="AdminDashboard.openPaymentModal(${s.id}, '${SecurityClient.escapeHtml(s.name)}', ${s.outstanding_balance})">
                                ${I18n.t('btn_pay', 'Pay')}
                            </button>
                            <button class="btn btn-xs btn-outline-primary font-bold" onclick="AdminDashboard.openEditShopCreditModal(${s.id})" title="Adjust Credit Risk Tier & Limit">
                                ${I18n.t('btn_edit_credit_tier', '⚙️ Tier')}
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Modal Handlers: Vehicle
    function openRegisterVehicleModal() {
        editingVehicleId = null;
        const title = document.querySelector('#registerVehicleModal .modal-header h3');
        if (title) title.textContent = '🚐 Register New Fleet Vehicle';
        const btn = document.querySelector('#registerVehicleModal .modal-body button.btn-primary');
        if (btn) btn.textContent = '💾 Save Vehicle to Fleet';
        const input = document.getElementById('newVehicleNumber');
        if (input) { input.value = ''; input.disabled = false; }
        const cap = document.getElementById('newVehicleCapacity');
        if (cap) cap.value = '2500';
        const modal = document.getElementById('registerVehicleModal');
        if (modal) modal.style.display = 'flex';
    }

    function openEditVehicleModal(id) {
        const vehicle = vehiclesList.find(v => v.id === id);
        if (!vehicle) return;
        editingVehicleId = id;
        const title = document.querySelector('#registerVehicleModal .modal-header h3');
        if (title) title.textContent = `✏️ Edit Vehicle #${vehicle.vehicle_number}`;
        const btn = document.querySelector('#registerVehicleModal .modal-body button.btn-primary');
        if (btn) btn.textContent = '💾 Update Vehicle';
        const input = document.getElementById('newVehicleNumber');
        if (input) { input.value = vehicle.vehicle_number; input.disabled = true; }
        const type = document.getElementById('newVehicleType');
        if (type) type.value = vehicle.vehicle_type || 'Refrigerated Van';
        const cap = document.getElementById('newVehicleCapacity');
        if (cap) cap.value = vehicle.capacity_cups || 2500;
        const cooling = document.getElementById('newVehicleCooling');
        if (cooling) cooling.value = vehicle.cooling_system_status || 'NORMAL_COOLING';
        const status = document.getElementById('newVehicleStatus');
        if (status) status.value = vehicle.status || 'AVAILABLE';
        const modal = document.getElementById('registerVehicleModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeRegisterVehicleModal() {
        const modal = document.getElementById('registerVehicleModal');
        if (modal) modal.style.display = 'none';
        editingVehicleId = null;
    }

    async function submitRegisterVehicle() {
        const vehicleNumber = (document.getElementById('newVehicleNumber')?.value || '').trim();
        const vehicleType = document.getElementById('newVehicleType')?.value || 'Refrigerated Van';
        const capacity = parseInt(document.getElementById('newVehicleCapacity')?.value || '0', 10);
        const cooling = document.getElementById('newVehicleCooling')?.value || 'NORMAL_COOLING';
        const status = document.getElementById('newVehicleStatus')?.value || 'AVAILABLE';

        if (!vehicleNumber) {
            return SecurityClient.showToast('⚠️ Please enter vehicle registration plate number.', 'warning', 3500);
        }
        if (!capacity || capacity <= 0) {
            return SecurityClient.showToast('⚠️ Please enter a valid cup capacity.', 'warning', 3500);
        }

        try {
            const isEdit = Boolean(editingVehicleId);
            const endpoint = isEdit ? `/api/admin/vehicles/${editingVehicleId}` : '/api/admin/vehicles';
            const method = isEdit ? 'PUT' : 'POST';

            const res = await SecurityClient.secureFetch(endpoint, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    vehicle_number: vehicleNumber,
                    vehicle_type: vehicleType,
                    capacity_cups: capacity,
                    cooling_system_status: cooling,
                    status: status
                })
            });

            if (res && res.success) {
                SecurityClient.showToast(isEdit ? `✅ Vehicle ${vehicleNumber} updated!` : `✅ Vehicle ${vehicleNumber} registered into fleet!`, 'success', 4000);
                closeRegisterVehicleModal();
                loadAllAdminData();
            } else {
                SecurityClient.showToast(res.error || 'Failed to save vehicle.', 'danger', 4000);
            }
        } catch (err) {
            SecurityClient.showToast('Error saving vehicle.', 'danger', 4000);
        }
    }

    async function deleteVehicle(id, vehicleNumber) {
        if (!confirm(`Are you sure you want to remove vehicle ${vehicleNumber} from the fleet?`)) return;

        try {
            const res = await SecurityClient.secureFetch(`/api/admin/vehicles/${id}`, {
                method: 'DELETE'
            });
            if (res && res.success) {
                SecurityClient.showToast(`Vehicle ${vehicleNumber} removed.`, 'success', 3500);
                loadAllAdminData();
            } else {
                SecurityClient.showToast(res.error || 'Failed to remove vehicle.', 'danger', 4000);
            }
        } catch (err) {
            SecurityClient.showToast('Error removing vehicle.', 'danger', 4000);
        }
    }

    // Modal Handlers: Driver
    function openRegisterDriverModal() {
        editingDriverId = null;
        const title = document.querySelector('#registerDriverModal .modal-header h3');
        if (title) title.textContent = '🚚 Register New Fleet Driver';
        const btn = document.querySelector('#registerDriverModal .modal-body button.btn-primary');
        if (btn) btn.textContent = '💾 Register Fleet Driver';
        const provBox = document.getElementById('provisionDriverAccountCheck')?.closest('.card');
        if (provBox) provBox.style.display = 'block';

        const select = document.getElementById('newDriverVehicleSelect');
        if (select) {
            select.innerHTML = `
                <option value="">-- No Vehicle Assigned (Spare) --</option>
                ${vehiclesList.map(v => `<option value="${v.id}">🚐 ${SecurityClient.escapeHtml(v.vehicle_number)} (${SecurityClient.escapeHtml(v.vehicle_type)})</option>`).join('')}
            `;
        }
        const nameInput = document.getElementById('newDriverName');
        if (nameInput) nameInput.value = '';
        const nicInput = document.getElementById('newDriverNic');
        if (nicInput) nicInput.value = '';
        const licInput = document.getElementById('newDriverLicense');
        if (licInput) licInput.value = '';
        const phoneInput = document.getElementById('newDriverPhone');
        if (phoneInput) phoneInput.value = '';

        const provCheck = document.getElementById('provisionDriverAccountCheck');
        if (provCheck) provCheck.checked = false;
        const acctFields = document.getElementById('driverAccountFields');
        if (acctFields) acctFields.style.display = 'none';
        const emailInput = document.getElementById('newDriverEmail');
        if (emailInput) emailInput.value = '';
        const passInput = document.getElementById('newDriverPassword');
        if (passInput) passInput.value = 'password';

        const modal = document.getElementById('registerDriverModal');
        if (modal) modal.style.display = 'flex';
    }

    function openEditDriverModal(id) {
        const driver = driversList.find(d => d.id === id);
        if (!driver) return;
        editingDriverId = id;
        const title = document.querySelector('#registerDriverModal .modal-header h3');
        if (title) title.textContent = `✏️ Edit Driver: ${driver.name}`;
        const btn = document.querySelector('#registerDriverModal .modal-body button.btn-primary');
        if (btn) btn.textContent = '💾 Update Driver Details';
        const provBox = document.getElementById('provisionDriverAccountCheck')?.closest('.card');
        if (provBox) provBox.style.display = 'none';

        const select = document.getElementById('newDriverVehicleSelect');
        if (select) {
            select.innerHTML = `
                <option value="">-- No Vehicle Assigned (Spare) --</option>
                ${vehiclesList.map(v => `<option value="${v.id}" ${v.id === driver.assigned_vehicle_id ? 'selected' : ''}>🚐 ${SecurityClient.escapeHtml(v.vehicle_number)} (${SecurityClient.escapeHtml(v.vehicle_type)})</option>`).join('')}
            `;
        }
        const nameInput = document.getElementById('newDriverName');
        if (nameInput) nameInput.value = driver.name || '';
        const nicInput = document.getElementById('newDriverNic');
        if (nicInput) nicInput.value = driver.nic_number || '';
        const licInput = document.getElementById('newDriverLicense');
        if (licInput) licInput.value = driver.license_number || '';
        const phoneInput = document.getElementById('newDriverPhone');
        if (phoneInput) phoneInput.value = driver.phone || '';
        const statusSelect = document.getElementById('newDriverStatus');
        if (statusSelect) statusSelect.value = driver.status || 'ACTIVE';
        const modal = document.getElementById('registerDriverModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeRegisterDriverModal() {
        const modal = document.getElementById('registerDriverModal');
        if (modal) modal.style.display = 'none';
        editingDriverId = null;
    }

    async function submitRegisterDriver() {
        const name = (document.getElementById('newDriverName')?.value || '').trim();
        const nic = (document.getElementById('newDriverNic')?.value || '').trim();
        const license = (document.getElementById('newDriverLicense')?.value || '').trim();
        const phone = (document.getElementById('newDriverPhone')?.value || '').trim();
        const vehicleId = document.getElementById('newDriverVehicleSelect')?.value || '';
        const status = document.getElementById('newDriverStatus')?.value || 'ACTIVE';
        const provisionAccount = document.getElementById('provisionDriverAccountCheck')?.checked;
        const email = provisionAccount ? (document.getElementById('newDriverEmail')?.value || '').trim() : '';
        const password = provisionAccount ? (document.getElementById('newDriverPassword')?.value || '').trim() : '';

        if (!name || !phone) {
            return SecurityClient.showToast('⚠️ Driver full name and phone number are required.', 'warning', 3500);
        }

        try {
            const isEdit = Boolean(editingDriverId);
            const endpoint = isEdit ? `/api/admin/drivers/${editingDriverId}` : '/api/admin/drivers';
            const method = isEdit ? 'PUT' : 'POST';

            const res = await SecurityClient.secureFetch(endpoint, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name,
                    nic_number: nic,
                    license_number: license,
                    phone,
                    assigned_vehicle_id: vehicleId || null,
                    status,
                    email,
                    password
                })
            });

            if (res && res.success) {
                SecurityClient.showToast(isEdit ? `✅ Driver ${name} updated!` : `✅ Driver ${name} registered successfully!`, 'success', 4000);
                closeRegisterDriverModal();
                loadAllAdminData();
            } else {
                SecurityClient.showToast(res.error || 'Failed to save driver.', 'danger', 4000);
            }
        } catch (err) {
            SecurityClient.showToast('Error saving driver.', 'danger', 4000);
        }
    }

    async function deleteDriver(id, driverName) {
        if (!confirm(`Are you sure you want to remove driver ${driverName}?`)) return;

        try {
            const res = await SecurityClient.secureFetch(`/api/admin/drivers/${id}`, {
                method: 'DELETE'
            });
            if (res && res.success) {
                SecurityClient.showToast(`Driver ${driverName} deleted.`, 'success', 3500);
                loadAllAdminData();
            } else {
                SecurityClient.showToast(res.error || 'Failed to delete driver.', 'danger', 4000);
            }
        } catch (err) {
            SecurityClient.showToast('Error deleting driver.', 'danger', 4000);
        }
    }

    // Modal Handlers: Sales Rep
    function openRegisterSalesRepModal() {
        editingSalesRepId = null;
        const title = document.querySelector('#registerSalesRepModal .modal-header h3');
        if (title) title.textContent = '💼 Register New Sales Representative';
        const btn = document.querySelector('#registerSalesRepModal .modal-body button.btn-primary');
        if (btn) btn.textContent = '💾 Register Sales Representative';
        const provBox = document.getElementById('provisionRepAccountCheck')?.closest('.card');
        if (provBox) provBox.style.display = 'block';

        const nameInput = document.getElementById('newRepName');
        if (nameInput) nameInput.value = '';
        const codeInput = document.getElementById('newRepCode');
        if (codeInput) { codeInput.value = 'REP-00' + (salesRepsList.length + 1); codeInput.disabled = false; }
        const phoneInput = document.getElementById('newRepPhone');
        if (phoneInput) phoneInput.value = '';

        const provCheck = document.getElementById('provisionRepAccountCheck');
        if (provCheck) provCheck.checked = false;
        const acctFields = document.getElementById('repAccountFields');
        if (acctFields) acctFields.style.display = 'none';
        const emailInput = document.getElementById('newRepEmail');
        if (emailInput) emailInput.value = '';
        const passInput = document.getElementById('newRepPassword');
        if (passInput) passInput.value = 'password';

        const modal = document.getElementById('registerSalesRepModal');
        if (modal) modal.style.display = 'flex';
    }

    function openEditSalesRepModal(id) {
        const rep = salesRepsList.find(r => r.id === id);
        if (!rep) return;
        editingSalesRepId = id;
        const title = document.querySelector('#registerSalesRepModal .modal-header h3');
        if (title) title.textContent = `✏️ Edit Representative: ${rep.name}`;
        const btn = document.querySelector('#registerSalesRepModal .modal-body button.btn-primary');
        if (btn) btn.textContent = '💾 Update Sales Representative';
        const provBox = document.getElementById('provisionRepAccountCheck')?.closest('.card');
        if (provBox) provBox.style.display = 'none';

        const nameInput = document.getElementById('newRepName');
        if (nameInput) nameInput.value = rep.name || '';
        const codeInput = document.getElementById('newRepCode');
        if (codeInput) { codeInput.value = rep.employee_code || ''; codeInput.disabled = true; }
        const phoneInput = document.getElementById('newRepPhone');
        if (phoneInput) phoneInput.value = rep.phone || '';
        const sectorInput = document.getElementById('newRepSector');
        if (sectorInput) sectorInput.value = rep.assigned_route_sector || 'Polonnaruwa Central';
        const commInput = document.getElementById('newRepCommission');
        if (commInput) commInput.value = rep.commission_rate_percent || 3.5;
        const statusInput = document.getElementById('newRepStatus');
        if (statusInput) statusInput.value = rep.status || 'ACTIVE';
        const modal = document.getElementById('registerSalesRepModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeRegisterSalesRepModal() {
        const modal = document.getElementById('registerSalesRepModal');
        if (modal) modal.style.display = 'none';
        editingSalesRepId = null;
    }

    async function submitRegisterSalesRep() {
        const name = (document.getElementById('newRepName')?.value || '').trim();
        const code = (document.getElementById('newRepCode')?.value || '').trim();
        const phone = (document.getElementById('newRepPhone')?.value || '').trim();
        const sector = document.getElementById('newRepSector')?.value || 'Polonnaruwa Central';
        const commission = parseFloat(document.getElementById('newRepCommission')?.value || '3.5') || 3.5;
        const status = document.getElementById('newRepStatus')?.value || 'ACTIVE';
        const provisionAccount = document.getElementById('provisionRepAccountCheck')?.checked;
        const email = provisionAccount ? (document.getElementById('newRepEmail')?.value || '').trim() : '';
        const password = provisionAccount ? (document.getElementById('newRepPassword')?.value || '').trim() : '';

        if (!name || !phone) {
            return SecurityClient.showToast('⚠️ Representative name and phone number are required.', 'warning', 3500);
        }

        try {
            const isEdit = Boolean(editingSalesRepId);
            const endpoint = isEdit ? `/api/admin/sales-reps/${editingSalesRepId}` : '/api/admin/sales-reps';
            const method = isEdit ? 'PUT' : 'POST';

            const res = await SecurityClient.secureFetch(endpoint, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name,
                    employee_code: code,
                    phone,
                    assigned_route_sector: sector,
                    commission_rate_percent: commission,
                    status,
                    email,
                    password
                })
            });

            if (res && res.success) {
                SecurityClient.showToast(isEdit ? `✅ Sales Representative ${name} updated!` : `✅ Sales Representative ${name} registered successfully!`, 'success', 4000);
                closeRegisterSalesRepModal();
                loadAllAdminData();
            } else {
                SecurityClient.showToast(res.error || 'Failed to save sales rep.', 'danger', 4000);
            }
        } catch (err) {
            SecurityClient.showToast('Error saving sales rep.', 'danger', 4000);
        }
    }

    async function deleteSalesRep(id, repName) {
        if (!confirm(`Are you sure you want to remove sales representative ${repName}?`)) return;

        try {
            const res = await SecurityClient.secureFetch(`/api/admin/sales-reps/${id}`, {
                method: 'DELETE'
            });
            if (res && res.success) {
                SecurityClient.showToast(`Representative ${repName} deleted.`, 'success', 3500);
                loadAllAdminData();
            } else {
                SecurityClient.showToast(res.error || 'Failed to delete representative.', 'danger', 4000);
            }
        } catch (err) {
            SecurityClient.showToast('Error deleting representative.', 'danger', 4000);
        }
    }

    // ========================================================
    // 4. ROUTE MODAL ACTIONS
    // ========================================================
    function openRegisterRouteModal() {
        editingRouteId = null;
        const title = document.getElementById('registerRouteModalTitle');
        if (title) title.innerHTML = '🗺️ Register New Delivery Route';
        const nameEl = document.getElementById('newRouteName');
        if (nameEl) nameEl.value = '';
        const regEl = document.getElementById('newRouteRegion');
        if (regEl) regEl.value = '';
        const distEl = document.getElementById('newRouteDistance');
        if (distEl) distEl.value = '35.0';
        const destEl = document.getElementById('newRouteDestination');
        if (destEl) destEl.value = '';
        const statEl = document.getElementById('newRouteStatus');
        if (statEl) statEl.value = 'ACTIVE';

        const vehicleSelect = document.getElementById('newRouteVehicle');
        if (vehicleSelect) {
            vehicleSelect.innerHTML = '<option value="">-- No Vehicle Assigned --</option>' +
                vehiclesList.map(v => `<option value="${v.id}">${v.vehicle_number} (${v.vehicle_type})</option>`).join('');
        }

        const modal = document.getElementById('registerRouteModal');
        if (modal) modal.style.display = 'flex';
    }

    function openEditRouteModal(id) {
        const route = routesList.find(r => r.id == id);
        if (!route) return;

        editingRouteId = id;
        const title = document.getElementById('registerRouteModalTitle');
        if (title) title.innerHTML = `✏️ Edit Route: ${SecurityClient.escapeHtml(route.route_name)}`;
        const nameEl = document.getElementById('newRouteName');
        if (nameEl) nameEl.value = route.route_name;
        const regEl = document.getElementById('newRouteRegion');
        if (regEl) regEl.value = route.region_sector;
        const distEl = document.getElementById('newRouteDistance');
        if (distEl) distEl.value = route.estimated_distance_km;
        const destEl = document.getElementById('newRouteDestination');
        if (destEl) destEl.value = route.primary_destination || '';
        const statEl = document.getElementById('newRouteStatus');
        if (statEl) statEl.value = route.status || 'ACTIVE';

        const vehicleSelect = document.getElementById('newRouteVehicle');
        if (vehicleSelect) {
            vehicleSelect.innerHTML = '<option value="">-- No Vehicle Assigned --</option>' +
                vehiclesList.map(v => `<option value="${v.id}" ${v.id == route.assigned_vehicle_id ? 'selected' : ''}>${v.vehicle_number} (${v.vehicle_type})</option>`).join('');
        }

        const modal = document.getElementById('registerRouteModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeRegisterRouteModal() {
        const modal = document.getElementById('registerRouteModal');
        if (modal) modal.style.display = 'none';
        editingRouteId = null;
    }

    async function submitRegisterRoute() {
        const name = (document.getElementById('newRouteName')?.value || '').trim();
        const region = (document.getElementById('newRouteRegion')?.value || '').trim();
        const distance = parseFloat(document.getElementById('newRouteDistance')?.value || '25.0') || 25.0;
        const destination = (document.getElementById('newRouteDestination')?.value || '').trim();
        const vehicleId = document.getElementById('newRouteVehicle')?.value || null;
        const status = document.getElementById('newRouteStatus')?.value || 'ACTIVE';

        if (!name || !region) {
            SecurityClient.showToast('⚠️ Route Name and Region Sector are required.', 'warning', 3500);
            return;
        }

        const isEdit = Boolean(editingRouteId);
        const endpoint = isEdit ? `/api/admin/routes/${editingRouteId}` : '/api/admin/routes';
        const method = isEdit ? 'PUT' : 'POST';

        try {
            const res = await SecurityClient.secureFetch(endpoint, {
                method,
                body: JSON.stringify({
                    route_name: name,
                    region_sector: region,
                    primary_destination: destination,
                    estimated_distance_km: distance,
                    assigned_vehicle_id: vehicleId ? parseInt(vehicleId) : null,
                    status
                })
            });

            if (res && res.success) {
                SecurityClient.showToast(isEdit ? `✅ Route '${name}' updated!` : `✅ Route '${name}' registered successfully!`, 'success', 4000);
                closeRegisterRouteModal();
                loadAllAdminData();
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to save route.', 'danger', 4000);
            }
        } catch (err) {
            SecurityClient.showToast('Error saving route corridor.', 'danger', 4000);
        }
    }

    async function deleteRoute(id, routeName) {
        if (!confirm(`Are you sure you want to delete delivery route "${routeName}"?`)) return;

        try {
            const res = await SecurityClient.secureFetch(`/api/admin/routes/${id}`, {
                method: 'DELETE'
            });
            if (res && res.success) {
                SecurityClient.showToast(`Route "${routeName}" deleted successfully.`, 'success', 3500);
                loadAllAdminData();
            } else {
                SecurityClient.showToast(res ? res.error : 'Failed to delete route.', 'danger', 5000);
            }
        } catch (err) {
            SecurityClient.showToast('Error deleting route.', 'danger', 4000);
        }
    }

    return {
        loadAllAdminData,
        refreshData,
        openRotationModal,
        closeRotationModal,
        submitRotation,
        onRotateVehicleChange,
        openDispatchRescueModal,
        closeDispatchRescueModal,
        submitDispatchRescue,
        openTransferHandshakeModal,
        openPaymentModal,
        closePaymentModal,
        submitShopPayment,
        openEditShopCreditModal,
        closeEditShopCreditModal,
        submitEditShopCredit,
        openNewDispatchModal,
        closeNewDispatchModal,
        submitNewDispatch,
        onDispatchVehicleChange,
        updateDispatchLoadTotal,
        openManifestDetailsModal,
        closeManifestDetailsModal,
        printManifest,
        scrollToSection,
        switchApprovalTab,
        approvePayment,
        rejectPayment,
        approveReturn,
        approveAllReturns,
        rejectReturn,
        approveShop,
        rejectShop,
        approveOrder,
        rejectOrder,
        // Fleet & Staff Management & Search Exports
        switchStaffTab,
        filterVehicles,
        filterDrivers,
        filterReps,
        filterRoutes,
        filterDispatches,
        filterRotation,
        filterShopRisk,
        openRegisterVehicleModal,
        openEditVehicleModal,
        closeRegisterVehicleModal,
        submitRegisterVehicle,
        deleteVehicle,
        openRegisterDriverModal,
        openEditDriverModal,
        closeRegisterDriverModal,
        submitRegisterDriver,
        deleteDriver,
        openRegisterSalesRepModal,
        openEditSalesRepModal,
        closeRegisterSalesRepModal,
        submitRegisterSalesRep,
        deleteSalesRep,
        openRegisterRouteModal,
        openEditRouteModal,
        closeRegisterRouteModal,
        submitRegisterRoute,
        deleteRoute
    };
})();

window.AdminDashboard = AdminDashboard;

window.addEventListener('languageChanged', () => {
    if (document.getElementById('adminDashboardContainer')) {
        AdminDashboard.loadAllAdminData();
    }
});
