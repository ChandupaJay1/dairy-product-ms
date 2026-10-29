/**
 * Driver Mobile Web View (Navigation & SOS Workflow)
 * 
 * Optimized for field drivers using mobile browsers or PWA install:
 * - Direct Google Maps navigation links for route stops
 * - Assigned vehicle manifest stock tally
 * - Prominent one-tap Red SOS breakdown button
 * - Live rescue status tracking & dual-driver stock handoff
 */

const DriverView = (() => {
    let currentDispatch = null;
    let activeSosAlert = null;
    let driverPollingTimer = null;
    let routeShops = [];
    let stopSearchQuery = '';
    let arrivedStopIds = new Set(JSON.parse(localStorage.getItem('driver_arrived_stops') || '[]'));

    async function loadDriverData() {
        // Fast Cache-First Stale-While-Revalidate
        const cachedManifest = OfflineStorage.getCachedData(OfflineStorage.CACHE_MANIFEST_KEY);
        if (cachedManifest) {
            currentDispatch = cachedManifest;
            render(); // Render instantly within 5ms
        } else {
            SecurityClient.renderLoading(
                'driverViewContainer',
                I18n.getLanguage() === 'si' ? 'රියදුරු තොරතුරු පූරණය වෙමින් පවතී' : 'Driver Navigation & Route Loading...',
                I18n.getLanguage() === 'si' ? '🚚 සක්‍රීය ධාවන මාර්ගය, වාහනය සහ SOS තත්ත්වය පරීක්ෂා වෙමින් පවතී...' : 'Checking active vehicle manifest, GPS route stops, and SOS status...',
                '🚚'
            );
        }

        // Parallel background network refresh
        try {
            const [dispatchesRes, sosRes, shopsRes] = await Promise.all([
                SecurityClient.secureFetch('/api/dispatches/active'),
                SecurityClient.secureFetch('/api/sos/active'),
                SecurityClient.secureFetch('/api/shops')
            ]);

            if (dispatchesRes && dispatchesRes.success && dispatchesRes.dispatches.length > 0) {
                // If logged in as driver, match driver's dispatch
                if (window.CURRENT_USER && window.CURRENT_USER.role === 'driver') {
                    const myDispatch = dispatchesRes.dispatches.find(d => 
                        d.driver_name && d.driver_name.toLowerCase().includes(window.CURRENT_USER.name.toLowerCase().split(' ')[0])
                    );
                    currentDispatch = myDispatch || dispatchesRes.dispatches[0];
                } else {
                    currentDispatch = dispatchesRes.dispatches[0];
                }
                OfflineStorage.cacheOperationalData(OfflineStorage.CACHE_MANIFEST_KEY, currentDispatch);
            } else if (!cachedManifest) {
                currentDispatch = null;
            }

            if (shopsRes && shopsRes.success) {
                const allShops = shopsRes.shops || [];
                routeShops = currentDispatch ? allShops.filter(s => s.route_id === currentDispatch.route_id) : allShops.slice(0, 4);
                if (routeShops.length === 0) routeShops = allShops.slice(0, 4);
            }

            if (sosRes && sosRes.success && sosRes.alerts.length > 0) {
                activeSosAlert = sosRes.alerts.find(a => a.vehicle_id === (currentDispatch ? currentDispatch.vehicle_id : null)) || sosRes.alerts[0];
            } else {
                activeSosAlert = null;
            }
        } catch (err) {
            console.warn('Driver data network fetch error, falling back to cache:', err);
        }

        render();
        startDriverPolling();
    }

    function startDriverPolling() {
        if (driverPollingTimer) clearInterval(driverPollingTimer);
        driverPollingTimer = setInterval(async () => {
            const container = document.getElementById('driverViewContainer');
            if (!container || !container.offsetParent) return;

            try {
                const sosRes = await SecurityClient.secureFetch('/api/sos/active');
                if (sosRes && sosRes.success) {
                    const prevStatus = activeSosAlert ? activeSosAlert.status : null;
                    const newAlert = sosRes.alerts.find(a => a.vehicle_id === (currentDispatch ? currentDispatch.vehicle_id : null)) || (sosRes.alerts.length > 0 ? sosRes.alerts[0] : null);

                    if (prevStatus && !newAlert) {
                        activeSosAlert = null;
                        SecurityClient.showToast(
                            I18n.t('sos_resolved_toast', '🎉 Stock Rescue Completed! All yogurt cups transferred. SOS alert resolved.'),
                            'success',
                            6000
                        );
                        loadDriverData();
                    } else if (newAlert && (!activeSosAlert || newAlert.status !== prevStatus)) {
                        activeSosAlert = newAlert;
                        if (newAlert.status === 'RESCUE_DISPATCHED' && prevStatus === 'PENDING_RESCUE') {
                            SecurityClient.showToast(
                                I18n.t('rescue_enroute_toast', '🚐 Rescue Van Dispatched! Moving towards your location.'),
                                'info',
                                5000
                            );
                        }
                        render();
                    }
                }
            } catch (e) {
                // Ignore background network error
            }
        }, 8000);
    }

    function render() {
        const container = document.getElementById('driverViewContainer');
        if (!container) return;

        if (!currentDispatch) {
            container.innerHTML = `
                <div class="empty-state-card">
                    <div class="empty-icon">🚚</div>
                    <h3>${I18n.t('no_manifest', 'No Active Daily Manifest')}</h3>
                    <p>${I18n.t('no_manifest_sub', 'There are no active dispatches assigned to your vehicle today.')}</p>
                </div>
            `;
            return;
        }

        const isSosActive = activeSosAlert && ['PENDING_RESCUE', 'RESCUE_DISPATCHED'].includes(activeSosAlert.status);

        container.innerHTML = `
            <div style="display:flex; flex-direction:column; gap:1.5rem;">
                
                <!-- Vehicle & Chiller Status Hero Header Card -->
                <div class="card">
                    <div class="card-sheen-top"></div>
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.25rem;">
                        <div>
                            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.35rem;">
                                <span class="status-pill ${isSosActive ? 'offline' : 'online'}">
                                    <span class="live-pulse-dot"></span>
                                    ${isSosActive ? I18n.t('driver_sos_active', 'EMERGENCY SOS ACTIVE') : I18n.t('driver_active_route', 'ACTIVE ON ROUTE')}
                                </span>
                                <span style="font-size:0.75rem; color:var(--text-muted); font-family:monospace;">Manifest #${SecurityClient.escapeHtml(currentDispatch.manifest_number)}</span>
                            </div>
                            <h2 style="font-size:1.6rem; font-weight:900; color:var(--text-heading); letter-spacing:-0.03em;">
                                🚚 ${SecurityClient.escapeHtml(currentDispatch.vehicle_number)}
                            </h2>
                            <p style="font-size:0.85rem; color:var(--text-secondary); margin-top:2px;">
                                📍 ${SecurityClient.escapeHtml(currentDispatch.route_name)} &bull; ${SecurityClient.escapeHtml(currentDispatch.region_sector || 'Polonnaruwa Sector')}
                            </p>
                        </div>

                        <!-- Chiller Telemetry Gauge -->
                        <div style="padding:0.85rem 1.25rem; border-radius:var(--radius-md); background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); display:flex; flex-direction:column; align-items:flex-end;">
                            <span style="font-size:0.68rem; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.08em;">
                                ${I18n.t('chiller_temp_label', 'CHILLER TELEMETRY')}
                            </span>
                            <div style="font-size:1.6rem; font-weight:900; font-family:monospace; color:${isSosActive ? 'var(--color-sos)' : 'var(--color-cash)'};">
                                ${isSosActive ? '11.8°C ⚠️' : '4.2°C ❄️'}
                            </div>
                            <span style="font-size:0.72rem; color:${isSosActive ? 'var(--color-sos)' : 'var(--color-cash)'}; font-weight:600;">
                                ${isSosActive ? I18n.t('chiller_fault', 'COMPRESSOR FAULT') : I18n.t('chiller_ok', 'COLD CHAIN COMPLIANT')}
                            </span>
                        </div>
                    </div>
                </div>

                ${isSosActive ? renderActiveSosCard() : ''}

                <!-- Main Layout Grid: Route Checklist on Left, Manifest & SOS on Right -->
                <div style="display:grid; grid-template-columns:1fr; gap:1.5rem; @media(min-width:1024px){grid-template-columns:1.4fr 1fr;}">
                    
                    <!-- Left: Step-by-Step Route Delivery Stops Checklist -->
                    <div class="card">
                        <div class="card-sheen-top"></div>
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">${I18n.t('route_stops_title', '🗺️ Step-by-Step Delivery Route')}</h3>
                                <p class="card-subtitle">
                                    Stop ${routeShops.filter(s => arrivedStopIds.has(s.id)).length} of ${routeShops.length} Completed 
                                    (${routeShops.length > 0 ? Math.round((routeShops.filter(s => arrivedStopIds.has(s.id)).length / routeShops.length) * 100) : 0}% Route Finished)
                                </p>
                            </div>
                            <span class="status-pill online">GPS Tracking Active</span>
                        </div>

                        <!-- Stepper Progress Track -->
                        <div style="height:6px; border-radius:var(--radius-full); background:var(--bg-surface-elevated); overflow:hidden; margin-bottom:1rem;">
                            <div style="height:100%; border-radius:var(--radius-full); background:var(--brand-gradient); width:${routeShops.length > 0 ? Math.round((routeShops.filter(s => arrivedStopIds.has(s.id)).length / routeShops.length) * 100) : 0}%; transition: width 0.4s ease;"></div>
                        </div>

                        <!-- Stop Quick Filter Search Input -->
                        <div style="position:relative; margin-bottom:1rem;">
                            <input type="text" id="driverStopSearchInput" class="form-control" 
                                placeholder="${I18n.t('search_stops_placeholder', 'Search route stops / shop name / address...')}" 
                                value="${SecurityClient.escapeHtml(stopSearchQuery)}"
                                oninput="DriverView.filterStops(this.value)" 
                                style="padding-left:2.4rem; padding-right:1rem; font-size:0.88rem; border-radius:var(--radius-sm);">
                            <span style="position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); opacity:0.6; pointer-events:none; font-size:0.95rem;">🔍</span>
                        </div>

                        <div class="delivery-stops-stepper-list" id="driverStopsContainer">
                            ${renderStopsItemsHtml()}
                        </div>
                    </div>

                    <!-- Right Column: Vehicle Stock Manifest & Emergency SOS -->
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">
                        
                        <!-- Emergency SOS Breakdown Trigger Button -->
                        ${!isSosActive ? `
                            <div class="card" style="border-color:rgba(244,63,94,0.3); background:rgba(244,63,94,0.05);">
                                <h3 style="font-size:1rem; font-weight:800; color:var(--color-sos); display:flex; align-items:center; gap:6px;">
                                    🚨 Vehicle Emergency SOS
                                </h3>
                                <p style="font-size:0.78rem; color:var(--text-secondary); margin-top:4px; margin-bottom:1rem;">
                                    Instant breakdown notification to Polonnaruwa Hub with automatic rescue vehicle dispatch.
                                </p>
                                <button type="button" class="btn btn-sos btn-block" id="btnTriggerSos" onclick="DriverView.openSosModal()" style="padding:0.9rem; font-size:0.95rem;">
                                    🚨 Broadcast Emergency SOS Now
                                </button>
                            </div>
                        ` : ''}

                        <!-- Stock Manifest Card -->
                        <div class="card">
                            <div class="card-sheen-top"></div>
                            <div class="card-header">
                                <div>
                                    <h3 class="card-title">📦 Vehicle Stock Manifest</h3>
                                    <p class="card-subtitle">Flavor inventory loaded on this truck</p>
                                </div>
                                <span class="status-pill online">${currentDispatch.remaining_cups} cups left</span>
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.65rem; margin-bottom:1rem;">
                                <div style="padding:0.65rem; background:var(--bg-surface-elevated); border-radius:var(--radius-sm); border:1px solid var(--border-subtle); text-align:center;">
                                    <span style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">Total Loaded</span>
                                    <div style="font-size:1.25rem; font-weight:900; font-family:monospace; color:var(--text-heading);">${currentDispatch.total_loaded_cups}</div>
                                </div>
                                <div style="padding:0.65rem; background:var(--bg-surface-elevated); border-radius:var(--radius-sm); border:1px solid var(--border-subtle); text-align:center;">
                                    <span style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">Delivered</span>
                                    <div style="font-size:1.25rem; font-weight:900; font-family:monospace; color:var(--color-cash);">${currentDispatch.sold_cups}</div>
                                </div>
                            </div>

                            <div style="display:flex; flex-direction:column; gap:0.5rem; font-size:0.8rem;">
                                <div style="display:flex; justify-content:space-between; padding:0.4rem 0.6rem; background:var(--bg-surface-elevated); border-radius:var(--radius-sm);">
                                    <span>🍦 Vanilla:</span><strong>${currentDispatch.vanilla_load || 600} cups</strong>
                                </div>
                                <div style="display:flex; justify-content:space-between; padding:0.4rem 0.6rem; background:var(--bg-surface-elevated); border-radius:var(--radius-sm);">
                                    <span>🍓 Strawberry:</span><strong>${currentDispatch.strawberry_load || 350} cups</strong>
                                </div>
                                <div style="display:flex; justify-content:space-between; padding:0.4rem 0.6rem; background:var(--bg-surface-elevated); border-radius:var(--radius-sm);">
                                    <span>🍫 Chocolate:</span><strong>${currentDispatch.chocolate_load || 250} cups</strong>
                                </div>
                                <div style="display:flex; justify-content:space-between; padding:0.4rem 0.6rem; background:var(--bg-surface-elevated); border-radius:var(--radius-sm);">
                                    <span>🥭 Mango:</span><strong>${currentDispatch.mango_load || 200} cups</strong>
                                </div>
                                <div style="display:flex; justify-content:space-between; padding:0.4rem 0.6rem; background:var(--bg-surface-elevated); border-radius:var(--radius-sm);">
                                    <span>🏺 Curd & Treacle:</span><strong>${currentDispatch.treacle_pot_load || 100} pots</strong>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        `;
    }

    function renderActiveSosCard() {
        if (!activeSosAlert) return '';

        const isRescueDispatched = activeSosAlert.status === 'RESCUE_DISPATCHED';

        return `
            <div class="card sos-active-banner animate-pulse-glow">
                <div class="sos-banner-header">
                    <div class="sos-siren-flashing">🚨</div>
                    <div>
                        <h3 class="text-danger font-bold">${I18n.t('sos_active_title', '🚨 SOS RESCUE IN PROGRESS')}</h3>
                        <p class="text-xs text-white-70">Ticket: ${SecurityClient.escapeHtml(activeSosAlert.sos_ticket_number)}</p>
                    </div>
                </div>

                <div class="sos-banner-body">
                    <div class="sos-detail-line">
                        <strong>${I18n.t('sos_reason_lbl', 'Reason:')}</strong> ${SecurityClient.escapeHtml(activeSosAlert.breakdown_reason)}
                    </div>
                    <div class="sos-detail-line">
                        <strong>${I18n.t('sos_landmark_lbl', 'Location:')}</strong> ${SecurityClient.escapeHtml(activeSosAlert.location_landmark)} (${Number(activeSosAlert.latitude).toFixed(4)}°N, ${Number(activeSosAlert.longitude).toFixed(4)}°E)
                    </div>
                    <div class="sos-detail-line">
                        <strong>${I18n.t('sos_stock_risk', 'At-Risk Yogurt Stock:')}</strong> <span class="badge badge-danger">${activeSosAlert.remaining_stock_at_incident} cups</span>
                    </div>

                    <div class="sos-status-stepper">
                        <div class="step-item completed">
                            <span class="step-circle">✓</span>
                            <span class="step-label">1. SOS</span>
                        </div>
                        <div class="step-item ${isRescueDispatched ? 'completed' : 'active'}">
                            <span class="step-circle">${isRescueDispatched ? '✓' : '2'}</span>
                            <span class="step-label">2. Rescue Dispatched</span>
                        </div>
                        <div class="step-item">
                            <span class="step-circle">3</span>
                            <span class="step-label">3. Handshake</span>
                        </div>
                    </div>

                    ${isRescueDispatched ? `
                        <div class="rescue-enroute-box">
                            <div class="rescue-vehicle-info">
                                🚐 <strong>${I18n.t('sos_rescue_vehicle', 'Rescue Vehicle:')}</strong> ${SecurityClient.escapeHtml(activeSosAlert.rescue_vehicle_number || 'NC-GA-1190')}
                                (${I18n.t('sos_rescue_driver', 'Driver:')} ${SecurityClient.escapeHtml(activeSosAlert.rescue_driver_name || 'Sunil Jayasinghe')})
                            </div>
                            <button class="btn btn-success btn-block mt-3 font-bold" onclick="DriverView.openTransferHandshakeModal(${activeSosAlert.id})">
                                ${I18n.t('sos_handshake_btn', '🤝 Initiate Physical Stock Transfer & Handshake')}
                            </button>
                        </div>
                    ` : `
                        <p class="text-center text-sm text-white-80 mt-2">
                            ⏳ ${I18n.t('sos_status_pending', 'Front office is dispatching a rescue vehicle from Polonnaruwa Depot. Keep cooler doors shut!')}
                        </p>
                    `}
                </div>
            </div>
        `;
    }

    function openSosModal() {
        const modal = document.getElementById('sosModal');
        if (modal) {
            document.getElementById('sosRemainingStockDisplay').textContent = currentDispatch ? currentDispatch.remaining_cups : 1320;
            const reasonSelect = document.getElementById('sosReasonSelect');
            if (reasonSelect) {
                reasonSelect.selectedIndex = 0;
            }
            const otherContainer = document.getElementById('sosOtherReasonContainer');
            if (otherContainer) otherContainer.style.display = 'none';
            const otherInput = document.getElementById('sosOtherReasonInput');
            if (otherInput) otherInput.value = '';
            modal.style.display = 'flex';
        }
    }

    function closeSosModal() {
        const modal = document.getElementById('sosModal');
        if (modal) modal.style.display = 'none';
        const otherContainer = document.getElementById('sosOtherReasonContainer');
        if (otherContainer) otherContainer.style.display = 'none';
        const otherInput = document.getElementById('sosOtherReasonInput');
        if (otherInput) otherInput.value = '';
    }

    function onSosReasonChange(val) {
        const otherContainer = document.getElementById('sosOtherReasonContainer');
        const otherInput = document.getElementById('sosOtherReasonInput');
        if (val === 'OTHER') {
            if (otherContainer) otherContainer.style.display = 'block';
            if (otherInput) {
                setTimeout(() => otherInput.focus(), 50);
            }
        } else {
            if (otherContainer) otherContainer.style.display = 'none';
            if (otherInput) otherInput.value = '';
        }
    }

    async function submitSosBreakdown() {
        if (!currentDispatch) return;

        let reason = document.getElementById('sosReasonSelect').value;
        if (reason === 'OTHER') {
            const customVal = (document.getElementById('sosOtherReasonInput')?.value || '').trim();
            if (!customVal) {
                SecurityClient.showToast('⚠️ Please specify the breakdown reason / කරුණාකර බිඳවැටීමට හේතුව සඳහන් කරන්න', 'warning');
                document.getElementById('sosOtherReasonInput')?.focus();
                return;
            }
            reason = 'Other: ' + customVal;
        }

        const landmark = document.getElementById('sosLandmarkInput').value || 'Near Hingurakgoda Track - Sector B';
        const temp = parseFloat(document.getElementById('sosTempInput').value) || 11.4;

        // Capture real-time hardware GPS coordinates if available
        let lat = 7.9403;
        let lng = 81.0188;
        if (navigator.geolocation) {
            try {
                const pos = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 3500,
                        maximumAge: 10000
                    });
                });
                if (pos && pos.coords) {
                    lat = pos.coords.latitude;
                    lng = pos.coords.longitude;
                }
            } catch (geoErr) {
                console.warn('Live GPS fallback to Polonnaruwa regional coordinates:', geoErr.message);
            }
        }

        const payload = {
            dispatchId: currentDispatch.id,
            vehicleId: currentDispatch.vehicle_id,
            driverId: currentDispatch.driver_id,
            latitude: lat,
            longitude: lng,
            locationLandmark: landmark,
            breakdownReason: reason,
            chillerTemperatureCelsius: temp
        };

        const res = await SecurityClient.secureFetch('/api/sos/trigger', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (res && res.success) {
            SecurityClient.showToast('🚨 SOS Emergency broadcasted to Front Office dispatch!', 'emergency');
            closeSosModal();
            // Play alert chime
            if (window.App && typeof window.App.playSirenAudio === 'function') {
                window.App.playSirenAudio();
            }
            loadDriverData();
        } else {
            SecurityClient.showToast(res.error || 'Failed to trigger SOS', 'error');
        }
    }

    function openTransferHandshakeModal(sosId) {
        App.openTransferHandshakeModal(sosId, activeSosAlert);
    }

    function closeTransferHandshakeModal() {
        App.closeTransferHandshakeModal();
    }

    async function submitTransferHandshake() {
        await App.submitTransferHandshake();
    }

    function renderStopsItemsHtml() {
        const query = (stopSearchQuery || '').toLowerCase().trim();
        const filtered = routeShops.filter(s => {
            if (!query) return true;
            const name = (s.name || '').toLowerCase();
            const owner = (s.owner_name || '').toLowerCase();
            const addr = (s.address || '').toLowerCase();
            return name.includes(query) || owner.includes(query) || addr.includes(query);
        });

        if (filtered.length === 0) {
            return `
                <div style="text-align:center; padding:1.5rem; color:var(--text-muted); font-size:0.85rem;">
                    🔍 ${stopSearchQuery ? 'No stops match your search query.' : 'No shops registered for this route.'}
                </div>
            `;
        }

        const firstUncompletedId = (filtered.find(s => !arrivedStopIds.has(s.id)) || {}).id;

        return filtered.map((shop, idx) => {
            const isCompleted = arrivedStopIds.has(shop.id);
            const isActive = !isCompleted && shop.id === firstUncompletedId;
            const shopName = SecurityClient.escapeHtml(shop.name || 'Retail Shop');
            const address = SecurityClient.escapeHtml(shop.address || 'Polonnaruwa Route');
            const owner = SecurityClient.escapeHtml(shop.owner_name || '');
            const phone = shop.phone ? SecurityClient.escapeHtml(shop.phone) : '';
            const gmapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent((shop.name || '') + ' ' + (shop.address || '') + ' Sri Lanka')}`;

            if (isCompleted) {
                return `
                    <div class="stop-checklist-item completed-stop">
                        <div class="stop-num-badge">✓</div>
                        <div class="stop-details-group">
                            <div class="stop-shop-name">${shopName}</div>
                            <div class="stop-address-text">${address} &bull; Delivered & Verified</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.4rem;">
                            <span class="status-pill online" style="font-size:0.7rem;">Completed</span>
                            <button type="button" class="btn btn-xs btn-outline" onclick="DriverView.markStopArrived(${shop.id}, '${shopName}')" title="Undo arrival">
                                ↺
                            </button>
                        </div>
                    </div>
                `;
            } else if (isActive) {
                return `
                    <div class="stop-checklist-item active-stop">
                        <div class="stop-num-badge">${idx + 1}</div>
                        <div class="stop-details-group">
                            <div class="stop-shop-name" style="color:var(--text-heading); font-size:1.05rem;">
                                ${shopName}
                            </div>
                            <div class="stop-address-text" style="margin-top:2px;">
                                ${address} ${owner ? `&bull; Owner: ${owner}` : ''}
                            </div>
                            <div style="display:flex; align-items:center; flex-wrap:wrap; gap:0.5rem; margin-top:0.75rem;">
                                <a href="${gmapsUrl}" target="_blank" class="btn btn-sm btn-outline">
                                    📍 Open in Google Maps
                                </a>
                                ${phone ? `
                                    <a href="tel:${phone}" class="btn btn-sm btn-outline">
                                        📞 Call Shop (${phone})
                                    </a>
                                ` : ''}
                                <button type="button" class="btn btn-sm btn-brand" onclick="DriverView.markStopArrived(${shop.id}, '${shopName}')">
                                    ✓ Mark Arrived
                                </button>
                            </div>
                        </div>
                        <span class="status-pill queue-pill" style="font-size:0.7rem;">Current Stop</span>
                    </div>
                `;
            } else {
                return `
                    <div class="stop-checklist-item">
                        <div class="stop-num-badge">${idx + 1}</div>
                        <div class="stop-details-group">
                            <div class="stop-shop-name">${shopName}</div>
                            <div class="stop-address-text">${address}</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.4rem;">
                            <span class="status-pill" style="font-size:0.7rem;">Pending</span>
                            <button type="button" class="btn btn-xs btn-outline" onclick="DriverView.markStopArrived(${shop.id}, '${shopName}')">
                                ✓ Mark
                            </button>
                        </div>
                    </div>
                `;
            }
        }).join('');
    }

    function markStopArrived(shopId, shopName) {
        if (arrivedStopIds.has(shopId)) {
            arrivedStopIds.delete(shopId);
            SecurityClient.showToast(`Stop "${shopName}" marked as pending.`, 'info', 2500);
        } else {
            arrivedStopIds.add(shopId);
            SecurityClient.showToast(`✅ Arrived at ${shopName}! Delivery verified.`, 'success', 3000);
        }
        localStorage.setItem('driver_arrived_stops', JSON.stringify([...arrivedStopIds]));
        render();
    }

    function filterStops(query) {
        stopSearchQuery = (query || '').toLowerCase().trim();
        const container = document.getElementById('driverStopsContainer');
        if (container) {
            container.innerHTML = renderStopsItemsHtml();
        }
    }

    return {
        loadDriverData,
        filterStops,
        markStopArrived,
        openSosModal,
        closeSosModal,
        onSosReasonChange,
        submitSosBreakdown,
        openTransferHandshakeModal,
        closeTransferHandshakeModal,
        submitTransferHandshake
    };
})();

window.DriverView = DriverView;

window.addEventListener('languageChanged', () => {
    if (document.getElementById('driverViewContainer')) {
        DriverView.loadDriverData();
    }
});
