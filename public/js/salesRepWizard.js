/**
 * Sales Representative Mobile Guided Wizard
 * 
 * Implements Section 2c & Section 3 of the proposal document:
 * - Guided wizard workflow with shop selection and credit tiering
 * - Large touch-friendly yogurt quantity counters (+1, +10, +50, +100)
 * - Color-coded Green CASH and Orange CREDIT payment buttons
 * - HTML5 Canvas Digital Signature and SMS OTP legal verification
 * - Cryptographic SHA-256 HMAC tamper-evident receipt generation
 */

const SalesRepWizard = (() => {
    let shopsList = [];
    let productsList = [];
    let currentDispatch = null;
    let selectedShop = null;
    let orderCart = {}; // { productId: { product, quantity, subtotal } }
    let signaturePad = null;
    let currentOtpReference = null;
    let otpCountdownInterval = null;
    let currentReceiptData = null;
    let currentDebtReceiptData = null;

    async function initWizard() {
        // 1. Instant Cache-First Render (Stale-While-Revalidate)
        // If data is already in LocalStorage, render IMMEDIATELY in 5 milliseconds!
        const cachedShops = OfflineStorage.getCachedData(OfflineStorage.CACHE_SHOPS_KEY);
        const cachedProducts = OfflineStorage.getCachedData(OfflineStorage.CACHE_PRODUCTS_KEY);
        const cachedDispatch = OfflineStorage.getCachedData(OfflineStorage.CACHE_MANIFEST_KEY);

        let renderedFromCache = false;
        if (cachedShops && cachedShops.length > 0 && cachedProducts && cachedProducts.length > 0) {
            shopsList = cachedShops;
            productsList = cachedProducts;
            currentDispatch = cachedDispatch || null;
            if (Object.keys(orderCart).length === 0) {
                productsList.forEach(p => {
                    orderCart[p.id] = { product: p, quantity: 0, subtotal: 0 };
                });
            }
            render();
            renderedFromCache = true;
        } else {
            // First time ever: show the beautiful dairy loading animation
            SecurityClient.renderLoading(
                'salesRepViewContainer',
                I18n.getLanguage() === 'si' ? 'සේල්ස් රෙප් පද්ධතිය සූදානම් වෙමින් පවතී' : 'Sales Representative Workspace Preparing...',
                I18n.getLanguage() === 'si' ? '🏪 වෙළඳසැල්, යෝගට් වර්ග සහ ලොරියේ තොග විස්තර පූරණය වෙමින් පවතී...' : 'Loading retail shops, yogurt inventory, and vehicle manifest...',
                '🍦'
            );
        }

        // 2. Background Revalidation: Fetch latest data asynchronously
        try {
            const [shopsRes, productsRes, dispatchRes] = await Promise.all([
                SecurityClient.secureFetch('/api/shops'),
                SecurityClient.secureFetch('/api/fleet/products'),
                SecurityClient.secureFetch('/api/dispatches/active')
            ]);

            if (shopsRes && shopsRes.success) {
                shopsList = shopsRes.shops;
                OfflineStorage.cacheOperationalData(OfflineStorage.CACHE_SHOPS_KEY, shopsRes.shops);
            }

            if (productsRes && productsRes.success) {
                productsList = productsRes.products;
                OfflineStorage.cacheOperationalData(OfflineStorage.CACHE_PRODUCTS_KEY, productsRes.products);
            }

            if (dispatchRes && dispatchRes.success && dispatchRes.dispatches.length > 0) {
                currentDispatch = dispatchRes.dispatches[0];
                OfflineStorage.cacheOperationalData(OfflineStorage.CACHE_MANIFEST_KEY, currentDispatch);
            }

            // Sync cart items with fresh products list
            productsList.forEach(p => {
                if (!orderCart[p.id]) {
                    orderCart[p.id] = { product: p, quantity: 0, subtotal: 0 };
                } else {
                    orderCart[p.id].product = p;
                }
            });

            // Re-render with fresh live data
            render();
        } catch (err) {
            console.warn('Background sync failed:', err);
            if (!renderedFromCache) render();
        }
    }

    function getProductIcon(name) {
        const lower = (name || '').toLowerCase();
        if (lower.includes('vanilla')) return '🍦';
        if (lower.includes('straw')) return '🍓';
        if (lower.includes('choc')) return '🍫';
        if (lower.includes('mango')) return '🥭';
        if (lower.includes('curd') || lower.includes('treacle')) return '🏺';
        return '🥛';
    }

    function render() {
        const container = document.getElementById('salesRepViewContainer');
        if (!container) return;

        const cartTotal = calculateCartTotal();
        const totalCups = calculateTotalCups();

        container.innerHTML = `
            <div class="sales-wizard-shell">
                <!-- Visual Step-by-Step Progress Bar (PulseERP Signature Stepper) -->
                <div class="stepper-nav">
                    <div class="stepper-step ${selectedShop ? 'completed' : 'active'}" onclick="document.getElementById('shopSelectDropdown')?.focus()">
                        <span class="stepper-num">${selectedShop ? '✓' : '1'}</span>
                        <div class="stepper-label">
                            <span class="stepper-title">${I18n.t('step_guide_1', '1. කඩය තෝරන්න')}</span>
                            <span class="stepper-desc">${selectedShop ? SecurityClient.escapeHtml(selectedShop.name) : 'Select Shop'}</span>
                        </div>
                    </div>
                    <div class="stepper-connector ${selectedShop ? 'completed' : ''}"></div>
                    <div class="stepper-step ${cartTotal > 0 ? 'completed' : (selectedShop ? 'active' : '')}">
                        <span class="stepper-num">${cartTotal > 0 ? '✓' : '2'}</span>
                        <div class="stepper-label">
                            <span class="stepper-title">${I18n.t('step_guide_2', '2. යෝගට් ගණන (+10, +50)')}</span>
                            <span class="stepper-desc">${totalCups > 0 ? totalCups + ' cups added' : 'Add Quantities'}</span>
                        </div>
                    </div>
                    <div class="stepper-connector ${cartTotal > 0 && selectedShop ? 'completed' : ''}"></div>
                    <div class="stepper-step ${cartTotal > 0 && selectedShop ? 'active' : ''}">
                        <span class="stepper-num">3</span>
                        <div class="stepper-label">
                            <span class="stepper-title">${I18n.t('step_guide_3', '3. මුදල් හෝ ණය')}</span>
                            <span class="stepper-desc">Cash or Credit</span>
                        </div>
                    </div>
                </div>

                <div class="wizard-grid-layout">
                    <!-- Step 1: Shop Selection & Credit Health Hub (Full-Width Card) -->
                    <div class="card eyecare-card" id="step1ShopCard">
                        <div class="card-sheen-top"></div>
                        <div class="card-header flex-between" style="flex-wrap: wrap; gap: 12px; padding: 1.25rem 1.5rem;">
                            <div>
                                <h3 class="card-title" style="display:flex; align-items:center; gap:8px; font-size:1.15rem; font-weight:800; margin:0;">
                                    ${I18n.t('step1_title', '🏪 1. කඩය තෝරන්න')}
                                </h3>
                                <p class="card-subtitle" style="margin-top:3px; font-size:0.8rem; color:var(--text-muted);">
                                    Polonnaruwa Route Sector & Real-Time Smart Credit Control
                                </p>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <button type="button" class="btn btn-brand font-bold" onclick="SalesRepWizard.openNewShopModal()" style="padding: 7px 16px;">
                                    ${I18n.t('btn_new_shop', '➕ අලුත් කඩයක්')}
                                </button>
                            </div>
                        </div>

                        <div style="padding: 1.25rem 1.5rem;">
                            <div class="shop-selection-toolbar" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 1.25rem;">
                                <div style="flex: 1; min-width: 260px;">
                                    <label for="shopSearchInput" class="form-label" style="font-size:0.82rem; font-weight:700; color:var(--text-secondary); margin-bottom: 6px; display:block;">
                                        ${I18n.t('step1_label', 'බෙදාහරින වෙළඳසැල තෝරන්න:')}
                                    </label>
                                    <div style="position:relative;">
                                        <input type="text" id="shopSearchInput" class="form-control" placeholder="${I18n.t('shop_search_placeholder', 'Search shop name or owner...')}" oninput="SalesRepWizard.filterShops(this.value)" style="padding-left: 2.4rem; padding-right: 1rem; font-size: 0.9rem; height: 44px; border-radius: var(--radius-md);">
                                        <span style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); opacity: 0.6; pointer-events: none; font-size: 1rem;">🔍</span>
                                    </div>
                                </div>

                                <div style="flex: 2; min-width: 320px;">
                                    <label for="shopSelectDropdown" class="form-label" style="font-size:0.82rem; font-weight:700; color:var(--text-secondary); margin-bottom: 6px; display:block;">
                                        🏪 ${I18n.t('shop_roster_label', 'Retail Outlets Roster:')}
                                    </label>
                                    <select id="shopSelectDropdown" class="form-control" onchange="SalesRepWizard.onShopSelected(this.value)" style="height: 44px; font-weight: 700; font-size: 0.95rem; border-radius: var(--radius-md);">
                                        <option value="">${I18n.t('step1_placeholder', '-- පොළොන්නරුව කලාපයේ වෙළඳසැලක් තෝරන්න --')}</option>
                                        ${shopsList.map(s => `
                                            <option value="${s.id}" ${selectedShop && selectedShop.id === s.id ? 'selected' : ''}>
                                                🏪 ${SecurityClient.escapeHtml(s.name)} - ${s.region_sector || 'Polonnaruwa'} (${SecurityClient.escapeHtml(s.owner_name)})
                                            </option>
                                        `).join('')}
                                    </select>
                                </div>

                                <div style="display:flex; gap:0.65rem; flex-wrap:wrap;">
                                    <button type="button" class="btn btn-outline font-bold" onclick="SalesRepWizard.openReturnModal()" style="height: 44px; padding: 0 16px; display:flex; align-items:center; gap:6px;">
                                        <span style="font-size:1.1rem;">🥛</span>
                                        <span>${I18n.t('tile_return_title', 'නරක් වූ යෝගට්')}</span>
                                    </button>
                                    <button type="button" class="btn btn-outline font-bold" onclick="SalesRepWizard.openCrateModal()" style="height: 44px; padding: 0 16px; display:flex; align-items:center; gap:6px;">
                                        <span style="font-size:1.1rem;">🧺</span>
                                        <span>${I18n.t('tile_crate_title', 'කූඩ සහ මුට්ටි')}</span>
                                    </button>
                                </div>
                            </div>

                            ${selectedShop ? renderShopCreditCard() : `
                                <div style="text-align:center; padding:2rem 1.5rem; border-radius:var(--radius-lg); border:1.5px dashed var(--border-medium); background:var(--bg-surface-elevated); font-size:0.95rem; color:var(--text-muted);">
                                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🏪</div>
                                    ${I18n.t('select_shop_hint', '💡 ඉහතින් කඩය තෝරාගෙන යෝගට් ගණන සටහන් කරන්න')}
                                </div>
                            `}
                        </div>
                    </div>

                    <!-- Step 2: Vehicle Cold-Chain Stock & Rapid Flavor Counters (Full-Width Card) -->
                    <div class="card eyecare-card" id="step2FlavorsCard">
                        <div class="card-sheen-top"></div>
                        <div class="card-header flex-between" style="flex-wrap: wrap; gap: 12px; padding: 1.25rem 1.5rem;">
                            <div>
                                <h3 class="card-title" style="display:flex; align-items:center; gap:8px; font-size:1.15rem; font-weight:800; margin:0;">
                                    ${I18n.t('step2_title', '🍦 2. යෝගට් ප්‍රමාණය තෝරන්න')}
                                </h3>
                                <p class="card-subtitle" style="margin-top:3px; font-size:0.8rem; color:var(--text-muted);">
                                    Touch Rapid Counters (-10, -1, +1, +10, +50, +100) with Live Cold-Chain Stock Tracking
                                </p>
                            </div>
                            <span class="status-pill online" style="font-size:0.78rem; padding: 5px 12px;">❄️ Cold Chain Active 4°C</span>
                        </div>

                        <div style="padding: 1.25rem 1.5rem;">
                            <!-- Live Truck Inventory Bar -->
                            ${renderTruckInventoryBar()}

                            <div class="products-flavor-grid" style="margin-top:1.25rem;">
                                ${productsList.map(prod => {
                                    const cartItem = orderCart[prod.id] || { quantity: 0, subtotal: 0 };
                                    const icon = getProductIcon(prod.flavor_name);
                                    return `
                                        <div class="flavor-product-card" id="prod-row-${prod.id}">
                                            <div class="flavor-card-top">
                                                <div class="flavor-icon-box">${icon}</div>
                                                <div class="flavor-info">
                                                    <div class="flavor-name">${SecurityClient.escapeHtml(I18n.getProductFlavor(prod))}</div>
                                                    <div class="flavor-price-tag">${SecurityClient.formatCurrency(prod.unit_price)} / cup</div>
                                                    <div class="flavor-truck-stock">
                                                        <span class="stock-tag-indicator"></span>
                                                        <span>${I18n.t('subtotal', 'එකතුව')}: <strong id="subtotal-display-${prod.id}" style="color:var(--accent-lime); font-family:monospace;">${SecurityClient.formatCurrency(cartItem.subtotal)}</strong></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flavor-counter-box" style="margin-top:0.75rem;">
                                                <button type="button" class="counter-btn" onclick="SalesRepWizard.adjustQuantity(${prod.id}, -10)">-10</button>
                                                <button type="button" class="counter-btn" onclick="SalesRepWizard.adjustQuantity(${prod.id}, -1)">-1</button>
                                                <div class="counter-input-field" id="qty-display-${prod.id}">${cartItem.quantity}</div>
                                                <button type="button" class="counter-btn" onclick="SalesRepWizard.adjustQuantity(${prod.id}, 1)">+1</button>
                                                <button type="button" class="counter-btn" onclick="SalesRepWizard.adjustQuantity(${prod.id}, 10)">+10</button>
                                                <button type="button" class="counter-btn" onclick="SalesRepWizard.adjustQuantity(${prod.id}, 50)">+50</button>
                                                <button type="button" class="counter-btn" onclick="SalesRepWizard.adjustQuantity(${prod.id}, 100)" style="font-size:0.75rem;">+100</button>
                                            </div>
                                        </div>
                                    `;
                                }).join('')}
                            </div>

                            <!-- Order Summary Bar -->
                            <div style="margin-top:1.5rem; padding:1.25rem 1.5rem; border-radius:var(--radius-lg); background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.25rem;">
                                <div style="display:flex; align-items:center; gap:2rem; flex-wrap:wrap;">
                                    <div>
                                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                                            ${I18n.t('total_cups_lbl', 'මුළු යෝගට් කෝප්ප:')}
                                        </div>
                                        <div style="font-size:1.6rem; font-weight:900; font-family:monospace; color:var(--text-heading); margin-top:2px;">
                                            <span id="totalCupsDisplay">${totalCups}</span> <span style="font-size:0.85rem; font-weight:600; color:var(--text-secondary);">cups</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                                            ${I18n.t('grand_total_label', 'මුළු එකතුව (Grand Total):')}
                                        </div>
                                        <div style="font-size:1.85rem; font-weight:900; font-family:monospace; color:var(--accent-amber); margin-top:2px;" id="grandTotalDisplay">
                                            ${SecurityClient.formatCurrency(cartTotal)}
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline" onclick="SalesRepWizard.clearCart()" style="color:var(--color-sos); border-color:rgba(244,63,94,0.4); padding:8px 16px; font-weight:700;">
                                    🔄 ${I18n.t('reset_btn', 'නැවත මුල සිට')}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Payment Buttons (Full-Width Card) -->
                    <div class="card eyecare-card" id="step3PaymentCard">
                        <div class="card-sheen-top"></div>
                        <div class="card-header flex-between" style="padding: 1.25rem 1.5rem; flex-wrap:wrap; gap:8px;">
                            <div>
                                <h3 class="card-title" style="display:flex; align-items:center; gap:8px; font-size:1.15rem; font-weight:800; margin:0;">
                                    ${I18n.t('step3_title', '💰 3. මුදල් හෝ ණය ගෙවීම')}
                                </h3>
                                <p class="card-subtitle" style="margin-top:3px; font-size:0.8rem; color:var(--text-muted);">
                                    Choose Payment Method & Issue Legally Verified Tamper-Evident Receipt
                                </p>
                            </div>
                            <span class="badge badge-outline" style="font-size:0.75rem;">SHA-256 HMAC Sealed</span>
                        </div>

                        <div style="padding: 1.25rem 1.5rem;">
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                                <!-- Cash Button -->
                                <button type="button" class="btn btn-cash btn-block" onclick="SalesRepWizard.processCashSale()" style="padding:1.4rem; border-radius:var(--radius-lg); display:flex; flex-direction:column; align-items:center; gap:8px; height:auto; transition:all 0.25s ease; box-shadow:0 4px 18px rgba(16,185,129,0.25);">
                                    <span style="font-size:2.2rem;">💵</span>
                                    <span style="font-size:1.15rem; font-weight:800;">${I18n.t('btn_cash_title', 'මුදල් ලැබුණා')}</span>
                                    <span style="font-size:0.8rem; opacity:0.9;">Instant Receipt &bull; Stock Decrement &bull; No Debt</span>
                                    <span style="font-size:1.3rem; font-weight:900; font-family:monospace; background:rgba(0,0,0,0.2); padding:4px 16px; border-radius:999px; margin-top:4px;" id="cashButtonAmountDisplay">${SecurityClient.formatCurrency(cartTotal)}</span>
                                </button>

                                <!-- Credit Button -->
                                <button type="button" class="btn btn-credit btn-block" onclick="SalesRepWizard.initiateCreditSale()" style="padding:1.4rem; border-radius:var(--radius-lg); display:flex; flex-direction:column; align-items:center; gap:8px; height:auto; transition:all 0.25s ease; box-shadow:0 4px 18px rgba(245,158,11,0.25);">
                                    <span style="font-size:2.2rem;">📝</span>
                                    <span style="font-size:1.15rem; font-weight:800;">${I18n.t('btn_credit_title', 'ණයට දුන්නා')}</span>
                                    <span style="font-size:0.8rem; opacity:0.9;">Credit Tier Validated &bull; Digital Signature &bull; SMS OTP</span>
                                    <span style="font-size:1.3rem; font-weight:900; font-family:monospace; background:rgba(0,0,0,0.2); padding:4px 16px; border-radius:999px; margin-top:4px;" id="creditButtonAmountDisplay">${SecurityClient.formatCurrency(cartTotal)}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderShopCreditCard() {
        if (!selectedShop) return '';

        const availableCredit = Math.max(0, selectedShop.credit_limit - selectedShop.outstanding_balance);
        let tierBadgeClass = 'tier-a';
        if (selectedShop.credit_tier === 'Tier B') tierBadgeClass = 'tier-b';
        if (selectedShop.credit_tier === 'Tier C') tierBadgeClass = 'tier-c';
        if (selectedShop.credit_tier === 'Cash Only') tierBadgeClass = 'tier-blocked';

        return `
            <div style="margin-top:0.75rem; padding:1.25rem 1.5rem; border-radius:var(--radius-lg); background:var(--bg-surface-elevated); border:1px solid var(--border-subtle);">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.25rem; padding-bottom:1rem; border-bottom:1px solid var(--border-subtle);">
                    <div>
                        <div style="font-weight:900; font-size:1.2rem; color:var(--text-heading); display:flex; align-items:center; gap:8px;">
                            <span>🏪</span> ${SecurityClient.escapeHtml(selectedShop.name)}
                        </div>
                        <div style="font-size:0.85rem; color:var(--text-muted); margin-top:4px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <span>👤 ${SecurityClient.escapeHtml(selectedShop.owner_name)}</span>
                            <span>📞 <a href="tel:${SecurityClient.escapeHtml(selectedShop.phone)}" style="color:var(--accent-lime); font-weight:700; text-decoration:none;">${SecurityClient.escapeHtml(selectedShop.phone)}</a></span>
                            <span>📍 ${SecurityClient.escapeHtml(selectedShop.region_sector || 'Polonnaruwa Central')}</span>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="tier-badge ${tierBadgeClass}" style="font-size:0.85rem; padding:6px 14px;">
                            ${SecurityClient.escapeHtml(selectedShop.credit_tier || 'Tier A')}
                        </span>
                        <span style="font-size:0.85rem; font-weight:700; color:var(--text-secondary); background:rgba(255,255,255,0.06); padding:6px 12px; border-radius:999px; border:1px solid var(--border-subtle);">
                            ⭐ ${selectedShop.payment_rating_percent || 98}% Reliability
                        </span>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; font-size:0.85rem;">
                    <div style="padding:1rem; background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-subtle); display:flex; flex-direction:column; justify-content:space-between;">
                        <span style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:0.04em;">${I18n.t('credit_limit', 'ණය සීමාව')}</span>
                        <div style="font-weight:900; font-size:1.35rem; font-family:monospace; color:var(--text-heading); margin-top:6px;">${SecurityClient.formatCurrency(selectedShop.credit_limit)}</div>
                    </div>
                    <div style="padding:1rem; background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-subtle); display:flex; flex-direction:column; justify-content:space-between;">
                        <span style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:0.04em;">${I18n.t('credit_outstanding', 'හිඟ මුදල')}</span>
                        <div style="font-weight:900; font-size:1.35rem; font-family:monospace; color:var(--color-sos); margin-top:6px;">${SecurityClient.formatCurrency(selectedShop.outstanding_balance)}</div>
                    </div>
                    <div style="padding:1rem; background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-subtle); display:flex; flex-direction:column; justify-content:space-between;">
                        <span style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:0.04em;">${I18n.t('credit_available', 'ලබාගත හැකි ණය')}</span>
                        <div style="font-weight:900; font-size:1.35rem; font-family:monospace; color:var(--color-cash); margin-top:6px;">${SecurityClient.formatCurrency(availableCredit)}</div>
                    </div>
                    <div style="padding:1rem; background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-subtle); display:flex; flex-direction:column; justify-content:space-between;">
                        <span style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:0.04em;">${I18n.t('credit_rating', 'විශ්වාසනීයත්වය')}</span>
                        <div style="font-weight:900; font-size:1.35rem; font-family:monospace; color:var(--text-heading); margin-top:6px;">${selectedShop.payment_rating_percent}% Good</div>
                    </div>
                </div>

                ${selectedShop.outstanding_balance > 0 ? `
                    <div style="margin-top:1.25rem; padding:1rem 1.25rem; border-radius:var(--radius-md); background:rgba(245, 158, 11, 0.1); border:1px solid rgba(245, 158, 11, 0.3); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:1.4rem;">⚠️</span>
                            <div>
                                <strong style="color:var(--accent-amber); font-size:0.92rem;">පැරණි හිඟ මුදලක් පවතී: ${SecurityClient.formatCurrency(selectedShop.outstanding_balance)}</strong>
                                <p style="font-size:0.75rem; color:var(--text-muted); margin:2px 0 0;">කරුණාකර නව ඇණවුමක් ලබාදීමට පෙර හෝ පසුව පැරණි ණය අයකරගන්න.</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-warning font-bold" onclick="SalesRepWizard.openFieldDebtModal()" style="padding:8px 18px;">
                            ${I18n.t('btn_collect_debt', '💵 පරණ ණය අයකරගැනීම')}
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    function renderTruckInventoryBar() {
        if (!currentDispatch) return '';
        const loaded = currentDispatch.total_loaded_cups || 1500;
        const remaining = currentDispatch.remaining_cups !== undefined ? currentDispatch.remaining_cups : loaded;
        const percent = Math.min(100, Math.max(0, Math.round((remaining / loaded) * 100)));
        const isWarning = percent < 25;

        return `
            <div style="padding:0.85rem 1rem; border-radius:var(--radius-md); background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); margin-bottom:1rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; margin-bottom:0.5rem;">
                    <span style="font-weight:700; color:var(--text-heading); display:flex; align-items:center; gap:6px;">
                        🚚 ${I18n.t('truck_label', 'ලොරිය')}: <strong>${SecurityClient.escapeHtml(currentDispatch.vehicle_number || 'NC-GA-4521')}</strong>
                    </span>
                    <span style="font-weight:700; font-family:monospace; color:${isWarning ? 'var(--color-sos)' : 'var(--color-cash)'};">
                        ${I18n.t('remaining_label', 'ඉතිරි')}: ${remaining} / ${loaded} (${percent}%)
                    </span>
                </div>
                <div style="height:6px; border-radius:var(--radius-full); background:var(--bg-surface); overflow:hidden;">
                    <div style="height:100%; border-radius:var(--radius-full); background:${isWarning ? 'var(--color-sos)' : 'var(--brand-gradient)'}; width:${percent}%; transition:width 0.3s ease;"></div>
                </div>
            </div>
        `;
    }

    function onShopSelected(shopId) {
        selectedShop = shopsList.find(s => s.id === parseInt(shopId)) || null;
        render();
    }

    function adjustQuantity(productId, delta) {
        if (!orderCart[productId]) return;

        // Check truck stock limit
        let totalCups = calculateTotalCups();
        if (delta > 0 && currentDispatch && currentDispatch.remaining_cups !== undefined) {
            if (totalCups + delta > currentDispatch.remaining_cups) {
                const stockMsg = I18n.getLanguage() === 'si'
                    ? `⚠️ ලොරියේ ඉතිරිව ඇත්තේ කෝප්ප ${currentDispatch.remaining_cups} ක් පමණි!`
                    : (I18n.getLanguage() === 'ta'
                        ? `⚠️ வாகனத்தில் ${currentDispatch.remaining_cups} கோப்பைகள் மட்டுமே உள்ளன!`
                        : `⚠️ Only ${currentDispatch.remaining_cups} yogurt cups remaining in the vehicle!`);
                SecurityClient.showToast(stockMsg, 'warning');
                return;
            }
        }

        const currentQty = orderCart[productId].quantity;
        const newQty = Math.max(0, currentQty + delta);
        orderCart[productId].quantity = newQty;
        orderCart[productId].subtotal = newQty * orderCart[productId].product.unit_price;

        // Update single display element without full re-render for maximum speed
        const display = document.getElementById(`qty-display-${productId}`);
        if (display) display.textContent = newQty;

        const subtotalDisplay = document.getElementById(`subtotal-display-${productId}`);
        if (subtotalDisplay) subtotalDisplay.textContent = SecurityClient.formatCurrency(orderCart[productId].subtotal);

        const cartTotal = calculateCartTotal();
        totalCups = calculateTotalCups();

        const grandDisplay = document.getElementById('grandTotalDisplay');
        if (grandDisplay) grandDisplay.textContent = SecurityClient.formatCurrency(cartTotal);

        const cupsLabel = document.getElementById('totalCupsDisplay');
        if (cupsLabel) cupsLabel.textContent = totalCups;

        // Update payment button amounts
        const cashAmt = document.getElementById('cashButtonAmountDisplay') || document.querySelector('.btn-pay-cash .pay-amount');
        if (cashAmt) cashAmt.textContent = SecurityClient.formatCurrency(cartTotal);

        const creditAmt = document.getElementById('creditButtonAmountDisplay') || document.querySelector('.btn-pay-credit .pay-amount');
        if (creditAmt) creditAmt.textContent = SecurityClient.formatCurrency(cartTotal);
    }

    function clearCart() {
        productsList.forEach(p => {
            orderCart[p.id] = { product: p, quantity: 0, subtotal: 0 };
        });
        render();
    }

    function calculateCartTotal() {
        return Object.values(orderCart).reduce((sum, item) => sum + item.subtotal, 0);
    }

    function calculateTotalCups() {
        return Object.values(orderCart).reduce((sum, item) => sum + item.quantity, 0);
    }

    function getSelectedItemsArray() {
        return Object.values(orderCart)
            .filter(item => item.quantity > 0)
            .map(item => ({
                productId: item.product.id,
                name: item.product.flavor_name,
                unitPrice: item.product.unit_price,
                quantity: item.quantity,
                subtotal: item.subtotal
            }));
    }

    /**
     * Process Immediate Cash Sale
     */
    async function processCashSale() {
        if (!selectedShop) {
            const msg = I18n.getLanguage() === 'si' ? 'කරුණාකර පළමුව වෙළඳසැලක් තෝරන්න (පියවර 1).' : 'Please select a retail shop first.';
            SecurityClient.showToast(msg, 'warning');
            return;
        }

        const totalAmount = calculateCartTotal();
        const items = getSelectedItemsArray();

        if (items.length === 0 || totalAmount <= 0) {
            const msg = I18n.getLanguage() === 'si' ? 'කරුණාකර පළමුව යෝගට් ප්‍රමාණයක් තෝරන්න (+10, +50 ඔබන්න).' : 'Please select at least 1 yogurt item.';
            SecurityClient.showToast(msg, 'warning');
            return;
        }

        const payload = {
            dispatchId: currentDispatch ? currentDispatch.id : 1,
            shopId: selectedShop.id,
            salesRepId: 4, // Nishan
            paymentType: 'CASH',
            totalAmount,
            items
        };

        // If offline, queue in LocalStorage
        if (!navigator.onLine) {
            OfflineStorage.enqueueOrder(payload);
            const offlineMsg = I18n.getLanguage() === 'si' 
                ? '📶 සිග්නල් නොමැත (Offline): මුදල් ඇනවුම දුරකථනයේ සුරැකිණි. සිග්නල් ලැබුණු වහාම Auto-Sync වේ.' 
                : '📶 Rural Offline Mode: Cash sale queued locally! Will auto-sync to MySQL database when signal returns.';
            SecurityClient.showToast(offlineMsg, 'warning', 6000);
            const simOrder = {
                invoiceNumber: 'OFFLINE-' + Math.floor(Math.random() * 90000 + 10000),
                totalAmount,
                securityTamperSeal: 'PENDING-MYSQL-SYNC'
            };
            showReceiptModal(simOrder, items, selectedShop, 'CASH (OFFLINE)');
            clearCart();
            return;
        }

        const res = await SecurityClient.secureFetch('/api/orders/create', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (res && res.success) {
            const successMsg = I18n.getLanguage() === 'si' 
                ? `✅ මුදල් බිල සාර්ථකව සටහන් විය! ඉන්වොයිස් අංකය: ${res.order.invoiceNumber}`
                : `✅ Cash sale processed! Invoice: ${res.order.invoiceNumber}`;
            SecurityClient.showToast(successMsg, 'success');
            showReceiptModal(res.order, items, selectedShop, 'CASH');
            clearCart();
            initWizard();
        } else if (res && res.offline) {
            OfflineStorage.enqueueOrder(payload);
            SecurityClient.showToast('📶 Network signal dropped. Cash sale queued locally and will auto-sync.', 'warning', 6000);
            clearCart();
        } else {
            SecurityClient.showToast(res.error || 'Failed to process cash sale.', 'error');
        }
    }

    /**
     * Initiate Credit Sale Workflow with Smart Tier validation & Signature/OTP modal
     */
    async function initiateCreditSale() {
        if (!selectedShop) {
            const msg = I18n.getLanguage() === 'si' ? 'කරුණාකර පළමුව වෙළඳසැලක් තෝරන්න (පියවර 1).' : 'Please select a retail shop first.';
            SecurityClient.showToast(msg, 'warning');
            return;
        }

        const totalAmount = calculateCartTotal();
        const items = getSelectedItemsArray();

        if (items.length === 0 || totalAmount <= 0) {
            const msg = I18n.getLanguage() === 'si' ? 'කරුණාකර පළමුව යෝගට් ප්‍රමාණයක් තෝරන්න (+10, +50 ඔබන්න).' : 'Please select at least 1 yogurt item.';
            SecurityClient.showToast(msg, 'warning');
            return;
        }

        // 1. Check Smart Credit Tier Limits
        const checkRes = await SecurityClient.secureFetch(`/api/shops/${selectedShop.id}/check-credit`, {
            method: 'POST',
            body: JSON.stringify({ amount: totalAmount })
        });

        if (!checkRes || !checkRes.allowed) {
            SecurityClient.showToast(`⛔ Credit Blocked: ${checkRes.reason || 'Credit limit exceeded.'}`, 'error', 5000);
            return;
        }

        // 2. Open Credit Authorization Modal (Digital Canvas Signature + SMS OTP)
        openCreditAuthModal(totalAmount);
    }

    let activeAuthMethod = 'signature';

    function switchAuthMethod(method) {
        activeAuthMethod = method;
        const tabSig = document.getElementById('tabBtnSignature');
        const tabOtp = document.getElementById('tabBtnOtp');
        const panelSig = document.getElementById('authPanelSignature');
        const panelOtp = document.getElementById('authPanelOtp');

        if (tabSig) tabSig.classList.toggle('active', method === 'signature');
        if (tabOtp) tabOtp.classList.toggle('active', method === 'otp');

        if (panelSig) panelSig.style.display = method === 'signature' ? 'block' : 'none';
        if (panelOtp) panelOtp.style.display = method === 'otp' ? 'block' : 'none';

        if (method === 'signature') {
            setTimeout(() => {
                const canvas = document.getElementById('signatureCanvas');
                if (canvas) {
                    if (!signaturePad) {
                        signaturePad = new SignaturePadManager(canvas);
                    } else {
                        signaturePad.resize();
                    }
                }
            }, 60);
        }
    }

    function openCreditAuthModal(amount) {
        const modal = document.getElementById('creditAuthModal');
        if (!modal) return;

        document.getElementById('creditShopNameDisplay').textContent = selectedShop.name;
        document.getElementById('creditAmountDisplay').textContent = SecurityClient.formatCurrency(amount);
        document.getElementById('creditPhoneDisplay').textContent = selectedShop.phone;

        modal.style.display = 'flex';
        switchAuthMethod('signature');

        // Initialize Canvas Signature Pad
        setTimeout(() => {
            const canvas = document.getElementById('signatureCanvas');
            if (canvas) {
                if (!signaturePad) {
                    signaturePad = new SignaturePadManager(canvas);
                } else {
                    signaturePad.resize();
                }
                signaturePad.clear();
            }
        }, 120);
    }

    function closeCreditAuthModal() {
        const modal = document.getElementById('creditAuthModal');
        if (modal) modal.style.display = 'none';
        if (otpCountdownInterval) clearInterval(otpCountdownInterval);
    }

    function clearSignature() {
        if (signaturePad) signaturePad.clear();
    }

    async function generateCreditOtp() {
        if (!selectedShop) return;
        const amount = calculateCartTotal();

        const btn = document.getElementById('btnGenerateOtp');
        if (btn) btn.disabled = true;

        const res = await SecurityClient.secureFetch('/api/auth/otp/generate', {
            method: 'POST',
            body: JSON.stringify({
                shopId: selectedShop.id,
                shopPhone: selectedShop.phone,
                amount
            })
        });

        if (btn) btn.disabled = false;

        if (res && res.success) {
            currentOtpReference = res.referenceCode;
            SecurityClient.showToast(`📩 SMS Verification Code sent to ${selectedShop.phone}! Code: ${res.devSimulationCode}`, 'info', 6000);
            document.getElementById('otpInput').value = res.devSimulationCode;

            // Countdown timer display
            let seconds = res.expiresInSeconds || 180;
            const timerDisplay = document.getElementById('otpTimerDisplay');
            if (otpCountdownInterval) clearInterval(otpCountdownInterval);

            otpCountdownInterval = setInterval(() => {
                seconds--;
                if (timerDisplay) timerDisplay.textContent = `Expires in ${seconds}s`;
                if (seconds <= 0) {
                    clearInterval(otpCountdownInterval);
                    if (timerDisplay) timerDisplay.textContent = 'OTP Expired';
                }
            }, 1000);
        } else {
            SecurityClient.showToast(res.error || 'Failed to generate OTP.', 'error');
        }
    }

    /**
     * Submit Credit Sale with Signature or OTP
     */
    async function submitCreditOrder() {
        if (!selectedShop) return;

        const totalAmount = calculateCartTotal();
        const items = getSelectedItemsArray();
        const signatureImage = signaturePad && !signaturePad.isEmpty() ? signaturePad.getImageDataUrl() : null;
        const otpValue = document.getElementById('otpInput').value.trim();

        if (activeAuthMethod === 'signature' && !signatureImage) {
            SecurityClient.showToast('Legal binding required: Please have the shopkeeper sign on the digital pad.', 'warning');
            return;
        }

        if (activeAuthMethod === 'otp' && !otpValue) {
            SecurityClient.showToast('Verification required: Please enter the 6-digit SMS OTP code.', 'warning');
            return;
        }

        if (!signatureImage && !otpValue) {
            SecurityClient.showToast('Legal binding required: Please sign on the canvas pad or verify with SMS OTP.', 'warning');
            return;
        }

        let otpVerified = 0;
        let otpRef = null;

        // If OTP provided, verify it first
        if (otpValue) {
            const verifyRes = await SecurityClient.secureFetch('/api/auth/otp/verify', {
                method: 'POST',
                body: JSON.stringify({
                    shopId: selectedShop.id,
                    otp: otpValue
                })
            });

            if (!verifyRes || !verifyRes.success) {
                SecurityClient.showToast(verifyRes.error || 'Invalid OTP code.', 'error');
                return;
            }
            otpVerified = 1;
            otpRef = verifyRes.referenceCode;
        }

        const payload = {
            dispatchId: currentDispatch ? currentDispatch.id : 1,
            shopId: selectedShop.id,
            salesRepId: 4,
            paymentType: 'CREDIT',
            totalAmount,
            items,
            signatureImage,
            otpVerified,
            otpReferenceCode: otpRef
        };

        // If offline, queue in LocalStorage
        if (!navigator.onLine) {
            OfflineStorage.enqueueOrder(payload);
            SecurityClient.showToast('📶 Rural Offline Mode: Credit order & Digital Signature queued locally! Will auto-sync to MySQL database when cellular signal returns.', 'warning', 6000);
            closeCreditAuthModal();
            const simOrder = {
                invoiceNumber: 'OFFLINE-CR-' + Math.floor(Math.random() * 90000 + 10000),
                totalAmount,
                securityTamperSeal: 'PENDING-MYSQL-SYNC'
            };
            showReceiptModal(simOrder, items, selectedShop, 'CREDIT (OFFLINE)', signatureImage);
            clearCart();
            return;
        }

        const res = await SecurityClient.secureFetch('/api/orders/create', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (res && res.success) {
            SecurityClient.showToast(`✅ Credit Order authorized! Invoice: ${res.order.invoiceNumber}`, 'success');
            closeCreditAuthModal();
            showReceiptModal(res.order, items, selectedShop, 'CREDIT', signatureImage);
            clearCart();
            initWizard();
        } else if (res && res.offline) {
            OfflineStorage.enqueueOrder(payload);
            SecurityClient.showToast('📶 Network signal dropped. Credit order queued locally and will auto-sync.', 'warning', 6000);
            closeCreditAuthModal();
            clearCart();
        } else {
            SecurityClient.showToast(res.error || 'Failed to submit credit order.', 'error');
        }
    }

    function showReceiptModal(order, items, shop, paymentType, signatureImage = null) {
        currentReceiptData = { order, items, shop, paymentType, signatureImage };
        const modal = document.getElementById('receiptModal');
        if (!modal) return;

        document.getElementById('receiptInvoiceNo').textContent = order.invoiceNumber;
        document.getElementById('receiptDate').textContent = new Date().toLocaleString();
        document.getElementById('receiptShopName').textContent = shop.name;
        document.getElementById('receiptShopAddr').textContent = shop.address;
        document.getElementById('receiptPaymentType').textContent = paymentType;
        document.getElementById('receiptPaymentType').className = `badge ${paymentType === 'CASH' ? 'badge-success' : 'badge-warning'}`;
        document.getElementById('receiptTotal').textContent = SecurityClient.formatCurrency(order.totalAmount);
        document.getElementById('receiptTamperSeal').textContent = order.securityTamperSeal;

        // Render receipt items
        const tbody = document.getElementById('receiptItemsTbody');
        tbody.innerHTML = items.map(item => `
            <tr>
                <td>${SecurityClient.escapeHtml(item.name)}</td>
                <td class="text-center">${item.quantity}</td>
                <td class="text-right">${SecurityClient.formatCurrency(item.unitPrice)}</td>
                <td class="text-right font-bold">${SecurityClient.formatCurrency(item.subtotal)}</td>
            </tr>
        `).join('');

        // Signature display
        const sigContainer = document.getElementById('receiptSignatureDisplay');
        if (signatureImage) {
            sigContainer.innerHTML = `
                <div class="sig-stamp">
                    <img src="${signatureImage}" alt="Authorized Signature" class="sig-preview-img" />
                    <div class="text-xs text-muted">Legal Electronic Signature Binding</div>
                </div>
            `;
        } else {
            sigContainer.innerHTML = '<span class="text-xs text-muted">Direct Cash Settlement - Signature Not Required</span>';
        }

        modal.style.display = 'flex';
    }

    function closeReceiptModal() {
        const modal = document.getElementById('receiptModal');
        if (modal) modal.style.display = 'none';
    }

    function openReturnModal() {
        const shopSelect = document.getElementById('returnShopSelect');
        if (shopSelect) {
            shopSelect.innerHTML = shopsList.map(s => `
                <option value="${s.id}" ${selectedShop && selectedShop.id === s.id ? 'selected' : ''}>
                    ${SecurityClient.escapeHtml(s.name)}
                </option>
            `).join('');
        }
        calculateReturnTotal();
        document.getElementById('stockReturnModal').style.display = 'flex';
    }

    function onReturnFlavorChange() {
        calculateReturnTotal();
    }

    function calculateReturnTotal() {
        const flavorSelect = document.getElementById('returnFlavorSelect');
        const qtyInput = document.getElementById('returnQtyInput');
        const totalDisplay = document.getElementById('returnTotalValueDisplay');
        if (!flavorSelect || !qtyInput || !totalDisplay) return;

        const selectedOption = flavorSelect.options[flavorSelect.selectedIndex];
        const price = parseFloat(selectedOption?.getAttribute('data-price') || '80.00');
        const qty = parseInt(qtyInput.value || '0');
        const total = price * qty;
        totalDisplay.value = SecurityClient.formatCurrency(total);
    }

    async function submitStockReturn() {
        const shopSelect = document.getElementById('returnShopSelect');
        const flavorSelect = document.getElementById('returnFlavorSelect');
        const batchInput = document.getElementById('returnBatchInput');
        const reasonSelect = document.getElementById('returnReasonSelect');
        const actionSelect = document.getElementById('returnActionSelect');
        const qtyInput = document.getElementById('returnQtyInput');
        const notesInput = document.getElementById('returnNotesInput');

        const shopId = parseInt(shopSelect?.value || '0');
        const selectedOption = flavorSelect.options[flavorSelect.selectedIndex];
        const flavorName = selectedOption?.value || 'Fresh Vanilla Yogurt (80g)';
        const unitPrice = parseFloat(selectedOption?.getAttribute('data-price') || '80.00');
        const batchNumber = batchInput?.value.trim() || 'LOT-20261014-A';
        const returnReason = reasonSelect?.value || 'EXPIRED';
        const settlementAction = actionSelect?.value || 'CREDIT_NOTE';
        const qty = parseInt(qtyInput?.value || '0');
        const notes = notesInput?.value.trim();

        if (qty <= 0) {
            SecurityClient.showToast('Please enter a valid quantity of cups.', 'error');
            return;
        }

        const payload = {
            shop_id: shopId,
            flavor_name: flavorName,
            batch_number: batchNumber,
            quantity_cups: qty,
            unit_price: unitPrice,
            return_reason: returnReason,
            settlement_action: settlementAction,
            notes: notes
        };

        const res = await SecurityClient.secureFetch('/api/returns/record', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (res && res.success) {
            if (res.is_pending) {
                const pendMsg = I18n.getLanguage() === 'si'
                    ? `⏳ Return #${res.return.return_number} Front Office අනුමැතිය සඳහා යොමු කරන ලදී! Warehouse verify කළ පසු Credit note එක හිමිවේ.`
                    : `⏳ Return #${res.return.return_number} forwarded for Front Office Approval! Credit note will be applied once verified.`;
                SecurityClient.showToast(pendMsg, 'info', 6000);
            } else {
                SecurityClient.showToast(`✓ Return #${res.return.return_number} logged! Credit adjustment applied.`, 'success', 5000);
            }
            document.getElementById('stockReturnModal').style.display = 'none';

            // Refresh shop data if current shop was modified
            if (selectedShop && selectedShop.id === shopId && !res.is_pending) {
                selectedShop.outstanding_balance = res.updated_shop_balance;
                render();
            }
        } else {
            SecurityClient.showToast(res ? res.error : 'Failed to record stock return.', 'error');
        }
    }

    function openCrateModal() {
        const shopSelect = document.getElementById('crateShopSelect');
        if (shopSelect) {
            shopSelect.innerHTML = shopsList.map(s => `
                <option value="${s.id}" ${selectedShop && selectedShop.id === s.id ? 'selected' : ''}>
                    ${SecurityClient.escapeHtml(s.name)}
                </option>
            `).join('');
        }
        document.getElementById('crateTrackModal').style.display = 'flex';
    }

    async function submitCrateLog() {
        const shopSelect = document.getElementById('crateShopSelect');
        const issuedInput = document.getElementById('cratesIssuedInput');
        const collectedInput = document.getElementById('cratesCollectedInput');
        const potsIssuedInput = document.getElementById('potsIssuedInput');
        const potsCollectedInput = document.getElementById('potsCollectedInput');

        const payload = {
            dispatch_id: currentDispatch ? currentDispatch.id : 1,
            shop_id: parseInt(shopSelect?.value || '0'),
            crates_issued: parseInt(issuedInput?.value || '0'),
            crates_collected: parseInt(collectedInput?.value || '0'),
            curd_pots_issued: parseInt(potsIssuedInput?.value || '0'),
            curd_pots_collected: parseInt(potsCollectedInput?.value || '0')
        };

        const res = await SecurityClient.secureFetch('/api/crates/record', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (res && res.success) {
            SecurityClient.showToast('✓ Crate and container balance updated!', 'success');
            document.getElementById('crateTrackModal').style.display = 'none';
        } else {
            SecurityClient.showToast('Failed to log crates.', 'error');
        }
    }

    // ========================================================
    // WHATSAPP BILL & RECEIPT SHARING (Field Realities)
    // ========================================================
    function shareReceiptViaWhatsApp() {
        if (!currentReceiptData) {
            SecurityClient.showToast(I18n.getLanguage() === 'si' ? 'රිසිට්පත් දත්ත සොයාගත නොහැකි විය.' : 'Receipt data not found.', 'warning');
            return;
        }
        const { order, items, shop, paymentType } = currentReceiptData;
        const itemsText = items.map(i => `• ${i.name} x ${i.quantity} = ${SecurityClient.formatCurrency(i.subtotal)}`).join('\n');
        const isSi = I18n.getLanguage() === 'si';
        
        const message = isSi ? 
`*🥛 පොළොන්නරුව යෝගට් සමාගම*
*බෙදාහැරීමේ බිල්පත (Delivery Invoice)*
--------------------------------
📄 ඉන්වොයිස් අංකය: ${order.invoiceNumber}
📅 දිනය: ${new Date().toLocaleString()}
🏪 වෙළඳසැල: ${shop.name}
👤 හිමිකරු: ${shop.owner_name || ''}
--------------------------------
📦 ලබාගත් යෝගට් තොග:
${itemsText}
--------------------------------
💰 මුළු එකතුව: ${SecurityClient.formatCurrency(order.totalAmount)}
💵 ගෙවීම් ක්‍රමය: ${paymentType}
${paymentType.includes('CREDIT') ? `⚠️ ඉතිරි ණය මුදල: ${SecurityClient.formatCurrency(shop.outstanding_balance || 0)}\n` : ''}--------------------------------
🔒 ආරක්ෂිත කේතය: ${order.securityTamperSeal || ''}
ස්තූතියි! නැවත හමුවෙමු.
☎️ විමසීම්: 027-2224501` :
`*🥛 Polonnaruwa Yogurt Company*
*Delivery Invoice & Stock Receipt*
--------------------------------
📄 Invoice No: ${order.invoiceNumber}
📅 Date: ${new Date().toLocaleString()}
🏪 Shop: ${shop.name}
👤 Owner: ${shop.owner_name || ''}
--------------------------------
📦 Delivered Inventory:
${itemsText}
--------------------------------
💰 Total Amount: ${SecurityClient.formatCurrency(order.totalAmount)}
💵 Payment Mode: ${paymentType}
${paymentType.includes('CREDIT') ? `⚠️ Outstanding Credit: ${SecurityClient.formatCurrency(shop.outstanding_balance || 0)}\n` : ''}--------------------------------
🔒 Tamper Seal: ${order.securityTamperSeal || ''}
Thank you for your business!
☎️ Inquiries: 027-2224501`;

        let phone = (shop.phone || '').replace(/\D/g, '');
        if (phone.startsWith('0')) {
            phone = '94' + phone.substring(1);
        }
        const encoded = encodeURIComponent(message);
        const url = phone ? `https://api.whatsapp.com/send?phone=${phone}&text=${encoded}` : `https://api.whatsapp.com/send?text=${encoded}`;
        window.open(url, '_blank');
    }

    // ========================================================
    // FIELD DEBT COLLECTION (Mobile Past Credit Recovery)
    // ========================================================
    function openFieldDebtModal() {
        if (!selectedShop) {
            SecurityClient.showToast(I18n.getLanguage() === 'si' ? 'කරුණාකර පළමුව වෙළඳසැලක් තෝරන්න.' : 'Please select a shop first.', 'warning');
            return;
        }

        document.getElementById('fieldDebtShopName').textContent = selectedShop.name;
        document.getElementById('fieldDebtOwnerPhone').textContent = `${selectedShop.owner_name} (${selectedShop.phone})`;
        document.getElementById('fieldDebtCurrentBalance').textContent = SecurityClient.formatCurrency(selectedShop.outstanding_balance);
        
        const input = document.getElementById('fieldDebtAmountInput');
        if (input) {
            input.value = '';
            input.max = selectedShop.outstanding_balance;
        }
        const notes = document.getElementById('fieldDebtNotesInput');
        if (notes) notes.value = '';

        document.getElementById('fieldDebtPaymentModal').style.display = 'flex';
    }

    function closeFieldDebtModal() {
        const modal = document.getElementById('fieldDebtPaymentModal');
        if (modal) modal.style.display = 'none';
    }

    function setDebtPreset(amount) {
        const input = document.getElementById('fieldDebtAmountInput');
        if (input) {
            const current = parseFloat(input.value) || 0;
            input.value = current + amount;
        }
    }

    function setDebtFullAmount() {
        if (!selectedShop) return;
        const input = document.getElementById('fieldDebtAmountInput');
        if (input) {
            input.value = selectedShop.outstanding_balance;
        }
    }

    async function submitFieldDebtPayment() {
        if (!selectedShop) return;
        const input = document.getElementById('fieldDebtAmountInput');
        const notesInput = document.getElementById('fieldDebtNotesInput');
        const amount = parseFloat(input?.value || '0');

        if (amount <= 0) {
            SecurityClient.showToast(I18n.getLanguage() === 'si' ? 'කරුණාකර වලංගු මුදලක් ඇතුළත් කරන්න (අවම රු. 1).' : 'Please enter a valid amount (min LKR 1).', 'error');
            return;
        }

        const res = await SecurityClient.secureFetch(`/api/shops/${selectedShop.id}/record-payment`, {
            method: 'POST',
            body: JSON.stringify({
                paymentAmount: amount,
                notes: notesInput?.value || '',
                salesRepName: 'Field Sales Rep'
            })
        });

        if (res && res.success) {
            closeFieldDebtModal();
            const isSi = I18n.getLanguage() === 'si';
            if (res.is_pending) {
                SecurityClient.showToast(isSi ? `⏳ රු. ${amount} ක ගෙවීම් රිසිට්පත (#${res.receipt.receiptNumber}) Front Office Admin අනුමැතිය සඳහා යොමු කරන ලදී!` : `⏳ Payment of LKR ${amount} (#${res.receipt.receiptNumber}) forwarded for Front Office Approval!`, 'info', 6000);
            } else {
                selectedShop.outstanding_balance = res.receipt ? res.receipt.newBalance : Math.max(0, selectedShop.outstanding_balance - amount);
                selectedShop.available_credit = Math.max(0, selectedShop.credit_limit - selectedShop.outstanding_balance);
                SecurityClient.showToast(isSi ? `✅ රු. ${amount} ක ණය මුදලක් සාර්ථකව අයකරගන්නා ලදී!` : `✅ Debt payment of LKR ${amount} collected successfully!`, 'success');
            }
            
            showDebtReceiptModal(res.receipt || {
                receiptNumber: 'PAY-' + Date.now(),
                shopName: selectedShop.name,
                ownerName: selectedShop.owner_name,
                phone: selectedShop.phone,
                paymentAmount: amount,
                previousBalance: (res.receipt ? res.receipt.previousBalance : selectedShop.outstanding_balance),
                newBalance: (res.receipt ? res.receipt.newBalance : selectedShop.outstanding_balance),
                status: res.is_pending ? 'PENDING_APPROVAL' : 'CONFIRMED',
                date: new Date().toLocaleString(),
                salesRep: 'Field Sales Rep'
            });

            render();
        } else {
            SecurityClient.showToast(res ? res.error : 'ණය මුදල සටහන් කිරීම අසාර්ථක විය.', 'error');
        }
    }

    function showDebtReceiptModal(receipt) {
        currentDebtReceiptData = receipt;
        const modal = document.getElementById('fieldDebtReceiptModal');
        if (!modal) return;

        document.getElementById('debtReceiptNo').textContent = receipt.receiptNumber;
        document.getElementById('debtReceiptDate').textContent = receipt.date || new Date().toLocaleString();
        document.getElementById('debtReceiptShopName').textContent = receipt.shopName;
        document.getElementById('debtReceiptOwnerName').textContent = receipt.ownerName;
        document.getElementById('debtReceiptPhone').textContent = receipt.phone;
        document.getElementById('debtReceiptRepName').textContent = receipt.salesRep || (I18n.getLanguage() === 'si' ? 'සේල්ස් රෙප්' : 'Sales Rep');
        document.getElementById('debtReceiptPrevBalance').textContent = SecurityClient.formatCurrency(receipt.previousBalance);
        document.getElementById('debtReceiptPaidAmount').textContent = SecurityClient.formatCurrency(receipt.paymentAmount);
        document.getElementById('debtReceiptNewBalance').textContent = SecurityClient.formatCurrency(receipt.newBalance);

        modal.style.display = 'flex';
    }

    function closeFieldDebtReceiptModal() {
        const modal = document.getElementById('fieldDebtReceiptModal');
        if (modal) modal.style.display = 'none';
    }

    function shareDebtReceiptViaWhatsApp() {
        if (!currentDebtReceiptData) {
            SecurityClient.showToast(I18n.getLanguage() === 'si' ? 'රිසිට්පත් දත්ත සොයාගත නොහැකි විය.' : 'Receipt data not found.', 'warning');
            return;
        }
        const r = currentDebtReceiptData;
        const isSi = I18n.getLanguage() === 'si';
        const message = isSi ?
`*🥛 පොළොන්නරුව යෝගට් සමාගම*
*ණය මුදල් අයකරගැනීමේ රිසිට්පත (Receipt)*
--------------------------------
📄 රිසිට් අංකය: ${r.receiptNumber}
📅 දිනය: ${r.date || new Date().toLocaleString()}
🏪 වෙළඳසැල: ${r.shopName}
👤 හිමිකරු: ${r.ownerName || ''}
--------------------------------
💵 අද ගෙවූ මුදල: ${SecurityClient.formatCurrency(r.paymentAmount)} (CASH)
📊 කලින් තිබූ ණය: ${SecurityClient.formatCurrency(r.previousBalance)}
⚠️ ඉතිරි ණය මුදල: ${SecurityClient.formatCurrency(r.newBalance)}
--------------------------------
✓ මුදල් සාර්ථකව පද්ධතියේ සටහන් විය.
ස්තූතියි!
☎️ විමසීම්: 027-2224501` :
`*🥛 Polonnaruwa Yogurt Company*
*Debt Recovery Payment Receipt*
--------------------------------
📄 Receipt No: ${r.receiptNumber}
📅 Date: ${r.date || new Date().toLocaleString()}
🏪 Shop: ${r.shopName}
👤 Owner: ${r.ownerName || ''}
--------------------------------
💵 Amount Collected: ${SecurityClient.formatCurrency(r.paymentAmount)} (CASH)
📊 Previous Balance: ${SecurityClient.formatCurrency(r.previousBalance)}
⚠️ Remaining Balance: ${SecurityClient.formatCurrency(r.newBalance)}
--------------------------------
✓ Payment successfully recorded.
Thank you!
☎️ Inquiries: 027-2224501`;

        let phone = (r.phone || '').replace(/\D/g, '');
        if (phone.startsWith('0')) {
            phone = '94' + phone.substring(1);
        }
        const encoded = encodeURIComponent(message);
        const url = phone ? `https://api.whatsapp.com/send?phone=${phone}&text=${encoded}` : `https://api.whatsapp.com/send?text=${encoded}`;
        window.open(url, '_blank');
    }

    // ========================================================
    // ADD NEW SHOP ON ROUTE (Field Mobile Registration)
    // ========================================================
    function openNewShopModal() {
        document.getElementById('newShopNameInput').value = '';
        document.getElementById('newShopOwnerInput').value = '';
        document.getElementById('newShopPhoneInput').value = '';
        document.getElementById('newShopAddressInput').value = '';
        document.getElementById('newShopCreditTierSelect').value = 'Tier C';
        document.getElementById('newShopModal').style.display = 'flex';
    }

    function closeNewShopModal() {
        const modal = document.getElementById('newShopModal');
        if (modal) modal.style.display = 'none';
    }

    async function submitNewShopRegistration() {
        const name = document.getElementById('newShopNameInput')?.value.trim();
        const owner = document.getElementById('newShopOwnerInput')?.value.trim();
        const phone = document.getElementById('newShopPhoneInput')?.value.trim();
        const address = document.getElementById('newShopAddressInput')?.value.trim();
        const tier = document.getElementById('newShopCreditTierSelect')?.value || 'Tier C';

        if (!name || !owner || !phone) {
            SecurityClient.showToast(I18n.getLanguage() === 'si' ? 'කරුණාකර කඩේ නම, හිමිකරුගේ නම සහ දුරකථන අංකය ඇතුළත් කරන්න.' : 'Please enter shop name, owner name, and phone number.', 'error');
            return;
        }

        const res = await SecurityClient.secureFetch('/api/shops/create', {
            method: 'POST',
            body: JSON.stringify({
                name,
                owner_name: owner,
                phone,
                address,
                credit_tier: tier,
                route_id: currentDispatch ? currentDispatch.route_id : 1
            })
        });

        if (res && res.success && res.shop) {
            closeNewShopModal();
            shopsList.unshift(res.shop);
            selectedShop = res.shop;
            const shopMsg = I18n.getLanguage() === 'si'
                ? `⏳ '${name}' වෙළඳසැල Front Office අනුමැතිය සඳහා යොමු කරන ලදී (Pending Approval)!`
                : `⏳ Retail shop '${name}' submitted for Front Office Approval!`;
            SecurityClient.showToast(shopMsg, 'info', 6000);
            render();
        } else {
            SecurityClient.showToast(res ? res.error : (I18n.getLanguage() === 'si' ? 'වෙළඳසැල ලියාපදිංචි කිරීම අසාර්ථක විය.' : 'Failed to register shop.'), 'error');
        }
    }

    function filterShops(query) {
        const select = document.getElementById('shopSelectDropdown');
        if (!select) return;

        const q = (query || '').toLowerCase().trim();
        const filtered = !q ? shopsList : shopsList.filter(s => 
            (s.name && s.name.toLowerCase().includes(q)) ||
            (s.owner_name && s.owner_name.toLowerCase().includes(q)) ||
            (s.phone && s.phone.includes(q)) ||
            (s.region_sector && s.region_sector.toLowerCase().includes(q))
        );

        select.innerHTML = `
            <option value="">${I18n.t('step1_placeholder', '-- පොළොන්නරුව කලාපයේ වෙළඳසැලක් තෝරන්න --')}</option>
            ${filtered.map(s => `
                <option value="${s.id}" ${selectedShop && selectedShop.id === s.id ? 'selected' : ''}>
                    🏪 ${SecurityClient.escapeHtml(s.name)} - ${s.region_sector || 'Polonnaruwa'} (${SecurityClient.escapeHtml(s.owner_name)})
                </option>
            `).join('')}
        `;

        if (filtered.length === 1 && q.length > 2) {
            onShopSelected(filtered[0].id);
        }
    }

    return {
        initWizard,
        filterShops,
        onShopSelected,
        adjustQuantity,
        clearCart,
        processCashSale,
        initiateCreditSale,
        openCreditAuthModal,
        closeCreditAuthModal,
        switchAuthMethod,
        clearSignature,
        generateCreditOtp,
        submitCreditOrder,
        closeReceiptModal,
        openReturnModal,
        onReturnFlavorChange,
        calculateReturnTotal,
        submitStockReturn,
        openCrateModal,
        submitCrateLog,
        shareReceiptViaWhatsApp,
        openFieldDebtModal,
        closeFieldDebtModal,
        setDebtPreset,
        setDebtFullAmount,
        submitFieldDebtPayment,
        showDebtReceiptModal,
        closeFieldDebtReceiptModal,
        shareDebtReceiptViaWhatsApp,
        openNewShopModal,
        closeNewShopModal,
        submitNewShopRegistration
    };
})();

window.SalesRepWizard = SalesRepWizard;

window.addEventListener('languageChanged', () => {
    if (document.getElementById('salesRepViewContainer')) {
        SalesRepWizard.initWizard();
    }
});
