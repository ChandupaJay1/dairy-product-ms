/**
 * Evening Settlement & Day-End Reconciliation View
 * 
 * Implements:
 * 1. Cash Handover: Physical cash vs invoiced bills reconciliation
 * 2. Stock Check-in: Returning unsold yogurt cups back to Cold Room
 * 3. Over / Short Variances: Real-time discrepancy flagging
 * 4. Crate & Container Tracking: Empty plastic crates and clay pots ledger
 */

const SettlementView = (() => {
    let previewData = null;
    let crateSummary = null;
    let settlementHistory = [];
    let recentReturns = [];

    async function loadData() {
        // Fast Cache-First Stale-While-Revalidate
        const cachedSettlement = OfflineStorage.getCachedData(OfflineStorage.CACHE_SETTLEMENT_KEY);
        if (cachedSettlement) {
            previewData = cachedSettlement.previewData || previewData;
            crateSummary = cachedSettlement.crateSummary || crateSummary;
            settlementHistory = cachedSettlement.settlementHistory || settlementHistory;
            recentReturns = cachedSettlement.recentReturns || recentReturns;
            render();
        } else {
            SecurityClient.renderLoading(
                'settlementViewContainer',
                I18n.getLanguage() === 'si' ? 'දවසේ ගිණුම් සාරාංශය පූරණය වෙමින් පවතී' : 'Day-End Reconciliation Loading...',
                I18n.getLanguage() === 'si' ? '🧾 විකුණුම් බිල්පත්, ලොරියේ ඉතිරි තොග සහ කූඩ ගිණුම් සසඳමින් පවතී...' : 'Auditing sales invoices, remaining cold-room returns, and crate ledgers...',
                '🧾'
            );
        }

        try {
            const [prevRes, crateRes, histRes, retRes] = await Promise.all([
                SecurityClient.secureFetch('/api/settlement/preview'),
                SecurityClient.secureFetch('/api/crates/summary'),
                SecurityClient.secureFetch('/api/settlement/history'),
                SecurityClient.secureFetch('/api/returns')
            ]);

            if (prevRes && prevRes.success) previewData = prevRes.dispatch;
            if (crateRes && crateRes.success) crateSummary = crateRes;
            if (histRes && histRes.success) settlementHistory = histRes.settlements;
            if (retRes && retRes.success) recentReturns = retRes.returns;

            OfflineStorage.cacheOperationalData(OfflineStorage.CACHE_SETTLEMENT_KEY, {
                previewData,
                crateSummary,
                settlementHistory,
                recentReturns
            });
        } catch (err) {
            console.warn('Settlement data background fetch error:', err);
        }

        render();
    }

    function render() {
        const container = document.getElementById('settlementViewContainer');
        if (!container) return;

        if (!previewData) {
            container.innerHTML = `
                <div class="empty-state-card">
                    <div class="empty-icon">🧾</div>
                    <h3>${I18n.t('no_manifest_settle', 'No Active Manifest to Settle')}</h3>
                    <p class="text-muted">${I18n.t('no_manifest_settle_sub', 'All daily vehicle manifests have been settled or no dispatches are active today.')}</p>
                </div>
            `;
            return;
        }

        const expectedStock = previewData.expected_remaining_cups;
        const invoicedCash = previewData.total_invoiced_cash;
        const invoicedCredit = previewData.total_invoiced_credit;
        const expectedCrates = previewData.expected_crates_return;

        container.innerHTML = `
            <div style="display:flex; flex-direction:column; gap:1.5rem;">
                <!-- Header Banner Card -->
                <div class="card">
                    <div class="card-sheen-top"></div>
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">${I18n.t('title_day_settlement', '🧾 Day-End Cash & Stock Reconciliation')}</h2>
                            <p class="card-subtitle">
                                ${I18n.t('settlement_depot', 'Polonnaruwa Main Depot')} &bull; Manifest <strong>#${SecurityClient.escapeHtml(previewData.manifest_number)}</strong> &bull; 
                                Vehicle: <strong>${SecurityClient.escapeHtml(previewData.vehicle_number)}</strong> (${SecurityClient.escapeHtml(previewData.driver_name)})
                            </p>
                        </div>
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <button class="btn btn-sm btn-outline" onclick="SettlementView.openCrateModal()">
                                ${I18n.t('tab_crates', '🧺 Crates & Pots (Crates)')}
                            </button>
                            <button class="btn btn-sm btn-outline" onclick="SettlementView.openReturnModal()">
                                ${I18n.t('tab_returns', '🥛 Damaged / Returns')}
                            </button>
                        </div>
                    </div>

                    <!-- Step-by-Step Stepper Header -->
                    <div class="stepper-nav" style="margin-bottom:0;">
                        <div class="stepper-step completed">
                            <span class="stepper-num">1</span>
                            <div class="stepper-label">
                                <span class="stepper-title">1. Cash Reconciliation</span>
                                <span class="stepper-desc">Verify Currency Notes</span>
                            </div>
                        </div>
                        <div class="stepper-connector completed"></div>
                        <div class="stepper-step completed">
                            <span class="stepper-num">2</span>
                            <div class="stepper-label">
                                <span class="stepper-title">2. Stock Cold Room Check</span>
                                <span class="stepper-desc">Unsold Yogurt Counts</span>
                            </div>
                        </div>
                        <div class="stepper-connector completed"></div>
                        <div class="stepper-step completed">
                            <span class="stepper-num">3</span>
                            <div class="stepper-label">
                                <span class="stepper-title">3. Assets & Sign-off</span>
                                <span class="stepper-desc">Crates & Ledger Lock</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3-Column Reconciliation Cards Grid -->
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.25rem;">
                    
                    <!-- CARD 1: CASH RECONCILIATION -->
                    <div class="card">
                        <div class="card-sheen-top" style="background:var(--color-cash);"></div>
                        <div class="card-header">
                            <div>
                                <h4 style="font-weight:800; font-size:1rem; color:var(--color-cash);">${I18n.t('settlement_cash_step', '💵 1. Cash Reconciliation')}</h4>
                                <span style="font-size:0.75rem; color:var(--text-muted);">Physical cash handed over</span>
                            </div>
                            <span class="status-pill online">Invoiced Cash</span>
                        </div>

                        <div style="margin-bottom:1rem;">
                            <span style="font-size:0.75rem; color:var(--text-muted);">${I18n.t('settlement_cash_sales', 'Total Cash Sales Today:')}</span>
                            <div style="font-size:1.6rem; font-weight:900; font-family:monospace; color:var(--color-cash);">${SecurityClient.formatCurrency(invoicedCash)}</div>
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                                Credit Sales (Pay Later): <strong style="color:var(--accent-amber);">${SecurityClient.formatCurrency(invoicedCredit)}</strong>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">${I18n.t('settlement_cash_received_lbl', 'Physical Cash Handed Over (Rs.):')}</label>
                            <input type="number" id="settlePhysicalCash" class="form-control text-center font-bold" style="font-size:1.25rem; font-family:monospace;"
                                value="${invoicedCash}" step="50" oninput="SettlementView.recalculateVariances()">
                        </div>

                        <div style="padding:0.75rem 1rem; border-radius:var(--radius-md); background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:0.75rem; color:var(--text-muted);">${I18n.t('settlement_cash_status_lbl', 'Cash Balance Status:')}</span>
                            <strong id="cashVarianceDisplay" style="color:var(--color-cash); font-family:monospace; font-size:0.85rem;">${I18n.t('settlement_balanced', 'Balanced (Rs. 0) ✓')}</strong>
                        </div>
                    </div>

                    <!-- CARD 2: COLD ROOM STOCK CHECK-IN -->
                    <div class="card">
                        <div class="card-sheen-top" style="background:var(--color-info);"></div>
                        <div class="card-header">
                            <div>
                                <h4 style="font-weight:800; font-size:1rem; color:var(--color-info);">${I18n.t('settlement_stock_step', '❄️ 2. Stock Checked Into Cold Room')}</h4>
                                <span style="font-size:0.75rem; color:var(--text-muted);">Unsold yogurt physical count</span>
                            </div>
                            <span class="status-pill online">Stock Tally</span>
                        </div>

                        <div style="margin-bottom:1rem; font-size:0.8rem; color:var(--text-secondary);">
                            <div>Loaded: <strong>${previewData.loaded_cups}</strong> &bull; Sold: <strong>${previewData.sold_cups}</strong></div>
                            <div>Damaged / Returns: <strong style="color:var(--color-sos);">${previewData.damaged_cups} cups</strong></div>
                            <div style="margin-top:0.35rem; font-size:0.85rem;">
                                Expected Return: <strong style="color:var(--color-info); font-size:1.1rem; font-family:monospace;">${expectedStock} cups</strong>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">${I18n.t('settlement_actual_stock_lbl', 'Actual Count Returned:')}</label>
                            <input type="number" id="settleActualStock" class="form-control text-center font-bold" style="font-size:1.25rem; font-family:monospace;"
                                value="${expectedStock}" min="0" oninput="SettlementView.recalculateVariances()">
                        </div>

                        <div style="padding:0.75rem 1rem; border-radius:var(--radius-md); background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:0.75rem; color:var(--text-muted);">${I18n.t('settlement_stock_status_lbl', 'Stock Status:')}</span>
                            <strong id="stockVarianceDisplay" style="color:var(--color-cash); font-family:monospace; font-size:0.85rem;">${I18n.t('settlement_stock_perfect', 'Balanced (Exact Count) ✓')}</strong>
                        </div>
                    </div>

                    <!-- CARD 3: CRATES & CONTAINER BALANCES -->
                    <div class="card">
                        <div class="card-sheen-top" style="background:var(--accent-amber);"></div>
                        <div class="card-header">
                            <div>
                                <h4 style="font-weight:800; font-size:1rem; color:var(--accent-amber);">${I18n.t('settlement_crate_step', '🧺 3. Crates & Pots Balance')}</h4>
                                <span style="font-size:0.75rem; color:var(--text-muted);">Asset tracking control</span>
                            </div>
                            <span class="status-pill queue-pill">Assets</span>
                        </div>

                        <div style="margin-bottom:1rem; font-size:0.8rem; color:var(--text-secondary);">
                            <div>Truck Crates: <strong>${previewData.crates_loaded}</strong> (30 cups/crate)</div>
                            <div>Issued: <strong>${previewData.crates_issued}</strong> &bull; Collected: <strong>${previewData.crates_collected}</strong></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">${I18n.t('settlement_empty_crates_returned', 'Empty Crates Returned:')}</label>
                            <input type="number" id="settleCratesReturned" class="form-control text-center font-bold" style="font-size:1.25rem; font-family:monospace;"
                                value="${expectedCrates}" min="0" oninput="SettlementView.recalculateVariances()">
                        </div>

                        <div style="padding:0.75rem 1rem; border-radius:var(--radius-md); background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:0.75rem; color:var(--text-muted);">${I18n.t('settlement_crate_status_lbl', 'Crates Status:')}</span>
                            <strong id="crateVarianceDisplay" style="color:var(--color-cash); font-family:monospace; font-size:0.85rem;">${I18n.t('settlement_crate_balanced', 'Balanced ✓')}</strong>
                        </div>
                    </div>
                </div>

                <!-- Settlement Form Sign-Off & Submission -->
                <div class="card">
                    <div class="card-sheen-top"></div>
                    <div style="display:grid; grid-template-columns:1fr; gap:1rem; @media(min-width:768px){grid-template-columns:1fr 2fr;}">
                        <div class="form-group">
                            <label class="form-label">${I18n.t('settlement_cashier_name_lbl', 'Cashier Name:')}</label>
                            <input type="text" id="settleCashierName" class="form-control" value="P. Rajapaksha (Chief Cashier)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">${I18n.t('settlement_notes_lbl', 'Additional Notes / Memo:')}</label>
                            <input type="text" id="settleVarianceNotes" class="form-control" value="All cash, returned yogurt, and crates verified accurately.">
                        </div>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-top:1rem; padding-top:1rem; border-top:1px solid var(--border-subtle);">
                        <span style="font-size:0.78rem; color:var(--text-muted);">
                            🔒 Confirming this closes the daily ledger and releases the vehicle for tomorrow.
                        </span>
                        <button type="button" class="btn btn-brand btn-lg" onclick="SettlementView.submitSettlement()">
                            ✓ Confirm & Close Daily Settlement
                    </div>
                </div>

                <!-- Section: Field Assets & Recent Settlement History -->
                <div class="grid grid-2 gap-3 mt-4" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <!-- Left: Recent Returns & Expiry Adjustments -->
                    <div class="card card-subtle p-3">
                        <div class="flex-between mb-2">
                            <h4 class="font-bold">${I18n.t('recent_returns_heading', '🥛 Recent Expiry & Damaged Returns')}</h4>
                            <span class="badge badge-outline">${recentReturns.length} Records</span>
                        </div>
                        <div class="table-responsive max-h-250">
                            <table class="table text-xs">
                                <thead>
                                    <tr>
                                        <th>${I18n.t('th_return_num', 'Return #')}</th>
                                        <th>${I18n.t('th_shop', 'Shop')}</th>
                                        <th>${I18n.t('th_flavor_batch', 'Flavor & Batch')}</th>
                                        <th>${I18n.t('th_qty', 'Qty')}</th>
                                        <th>${I18n.t('th_action', 'Action')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${recentReturns.slice(0, 5).map(r => `
                                        <tr>
                                            <td><strong>${SecurityClient.escapeHtml(r.return_number)}</strong></td>
                                            <td>${SecurityClient.escapeHtml(r.shop_name || 'Retail Shop')}</td>
                                            <td>
                                                <div>${SecurityClient.escapeHtml(r.flavor_name)}</div>
                                                <div class="text-muted text-xs font-mono">${SecurityClient.escapeHtml(r.batch_number)}</div>
                                            </td>
                                            <td class="font-bold">${r.quantity_cups}</td>
                                            <td>
                                                <span class="badge ${r.settlement_action === 'CREDIT_NOTE' ? 'badge-warning' : 'badge-primary'}">
                                                    ${SecurityClient.escapeHtml(r.settlement_action)}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Right: Past Settlement Slips -->
                    <div class="card card-subtle p-3">
                        <div class="flex-between mb-2">
                            <h4 class="font-bold">${I18n.t('recent_settlements_heading', '🧾 Past Settlement Ledger')}</h4>
                            <span class="badge badge-success">${I18n.t('audit_verified', 'Audit Verified')}</span>
                        </div>
                        <div class="table-responsive max-h-250">
                            <table class="table text-xs">
                                <thead>
                                    <tr>
                                        <th>${I18n.t('th_settlement_num', 'Settlement #')}</th>
                                        <th>${I18n.t('th_date', 'Date')}</th>
                                        <th>${I18n.t('th_driver', 'Driver')}</th>
                                        <th>${I18n.t('th_cash_handed', 'Cash Handed')}</th>
                                        <th>${I18n.t('th_variance', 'Variance')}</th>
                                        <th>${I18n.t('th_status', 'Status')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${settlementHistory.map(s => `
                                        <tr>
                                            <td><strong>${SecurityClient.escapeHtml(s.settlement_number)}</strong></td>
                                            <td>${s.settlement_date}</td>
                                            <td>${SecurityClient.escapeHtml(s.driver_name)}</td>
                                            <td class="font-bold text-success">${SecurityClient.formatCurrency(s.physical_cash_handed)}</td>
                                            <td class="${s.cash_variance != 0 ? 'text-danger font-bold' : 'text-success'}">
                                                ${s.cash_variance == 0 ? '✓ Balanced' : (s.cash_variance > 0 ? '+' + s.cash_variance : s.cash_variance)}
                                            </td>
                                            <td>
                                                <span class="badge ${s.status === 'BALANCED' ? 'badge-success' : 'badge-danger'}">
                                                    ${SecurityClient.escapeHtml(s.status)}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function recalculateVariances() {
        if (!previewData) return;

        const isSi = I18n.getLanguage() === 'si';
        const isTa = I18n.getLanguage() === 'ta';

        const physicalCash = parseFloat(document.getElementById('settlePhysicalCash')?.value || '0');
        const invoicedCash = previewData.total_invoiced_cash;
        const cashVariance = physicalCash - invoicedCash;

        const cashDisplay = document.getElementById('cashVarianceDisplay');
        if (cashDisplay) {
            if (Math.abs(cashVariance) < 0.01) {
                cashDisplay.textContent = isSi ? 'රු. 0.00 (හරියටම හරි ✓)' : (isTa ? 'ரூ. 0.00 (சரியானது ✓)' : 'Rs. 0.00 (Balanced ✓)');
                cashDisplay.className = 'text-success font-mono font-bold';
            } else if (cashVariance > 0) {
                cashDisplay.textContent = `+ ${SecurityClient.formatCurrency(cashVariance)} ` + (isSi ? '(වැඩි මුදලක් ℹ️)' : (isTa ? '(அதிக பணம் ℹ️)' : '(Surplus Cash ℹ️)'));
                cashDisplay.className = 'text-warning font-mono font-bold';
            } else {
                cashDisplay.textContent = `- ${SecurityClient.formatCurrency(Math.abs(cashVariance))} ` + (isSi ? '(අඩු මුදලක් ⚠️)' : (isTa ? '(குறைவான பணம் ⚠️)' : '(Shortage Cash ⚠️)'));
                cashDisplay.className = 'text-danger font-mono font-bold';
            }
        }

        const actualStock = parseInt(document.getElementById('settleActualStock')?.value || '0');
        const expectedStock = previewData.expected_remaining_cups;
        const stockVariance = actualStock - expectedStock;

        const stockDisplay = document.getElementById('stockVarianceDisplay');
        if (stockDisplay) {
            if (stockVariance === 0) {
                stockDisplay.textContent = isSi ? '0 කෝප්ප (හරියටම හරි ✓)' : (isTa ? '0 கோப்பைகள் (சரியானது ✓)' : '0 Cups (Balanced ✓)');
                stockDisplay.className = 'text-success font-mono font-bold';
            } else if (stockVariance > 0) {
                stockDisplay.textContent = `+${stockVariance} ` + (isSi ? 'කෝප්ප (වැඩි තොගයක් ℹ️)' : (isTa ? 'கோப்பைகள் (கூடுதல் இருப்பு ℹ️)' : 'Cups (Excess Stock ℹ️)'));
                stockDisplay.className = 'text-warning font-mono font-bold';
            } else {
                stockDisplay.textContent = `${stockVariance} ` + (isSi ? 'කෝප්ප (අඩු තොගයක් ⚠️)' : (isTa ? 'கோப்பைகள் (குறைவு ⚠️)' : 'Cups (Deficit Stock ⚠️)'));
                stockDisplay.className = 'text-danger font-mono font-bold';
            }
        }

        const cratesReturned = parseInt(document.getElementById('settleCratesReturned')?.value || '0');
        const expectedCrates = previewData.expected_crates_return;
        const crateVariance = cratesReturned - expectedCrates;

        const crateDisplay = document.getElementById('crateVarianceDisplay');
        if (crateDisplay) {
            if (crateVariance === 0) {
                crateDisplay.textContent = isSi ? 'හරියටම හරි (Balanced) ✓' : (isTa ? 'சரியானது (Balanced) ✓' : 'Balanced ✓');
                crateDisplay.className = 'text-success font-mono font-bold';
            } else if (crateVariance < 0) {
                crateDisplay.textContent = isSi ? `කූඩ ${Math.abs(crateVariance)} ක් කඩවල්වල තබා ඇත ⚠️` : (isTa ? `${Math.abs(crateVariance)} கூடைகள் கடைகளில் வைக்கப்பட்டுள்ளன ⚠️` : `${Math.abs(crateVariance)} Crates held at retail shops ⚠️`);
                crateDisplay.className = 'text-warning font-mono font-bold';
            } else {
                crateDisplay.textContent = isSi ? `අමතර කූඩ +${crateVariance} ක් ලැබී ඇත ℹ️` : (isTa ? `கூடுதல் கூடைகள் +${crateVariance} பெறப்பட்டது ℹ️` : `+${crateVariance} Extra crates returned ℹ️`);
                crateDisplay.className = 'text-info font-mono font-bold';
            }
        }
    }

    async function submitSettlement() {
        if (!previewData) return;

        const cashierName = document.getElementById('settleCashierName')?.value.trim();
        const physicalCash = parseFloat(document.getElementById('settlePhysicalCash')?.value || '0');
        const actualStock = parseInt(document.getElementById('settleActualStock')?.value || '0');
        const cratesReturned = parseInt(document.getElementById('settleCratesReturned')?.value || '0');
        const potsReturned = parseInt(document.getElementById('settlePotsReturned')?.value || '0');
        const notes = document.getElementById('settleVarianceNotes')?.value.trim();

        if (!cashierName) {
            SecurityClient.showToast(I18n.t('err_enter_cashier', 'Please enter the receiving cashier name.'), 'error');
            return;
        }

        const payload = {
            dispatch_id: previewData.id,
            cashier_name: cashierName,
            physical_cash_handed: physicalCash,
            actual_stock_checked_in: actualStock,
            crates_returned_total: cratesReturned,
            curd_pots_returned: potsReturned,
            variance_notes: notes
        };

        const res = await SecurityClient.secureFetch('/api/settlement/submit', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (res && res.success) {
            SecurityClient.showToast(`✓ Day-End Settlement #${res.settlement.settlement_number} confirmed! Dispatch closed.`, 'success', 5000);
            await loadData();
            if (window.AdminDashboard && typeof window.AdminDashboard.loadAllAdminData === 'function') {
                window.AdminDashboard.loadAllAdminData();
            }
        } else {
            SecurityClient.showToast(res ? res.error : 'Failed to record settlement.', 'error');
        }
    }

    function openReturnModal() {
        // Populate shops in return modal
        const shopSelect = document.getElementById('returnShopSelect');
        if (shopSelect) {
            SecurityClient.secureFetch('/api/shops').then(res => {
                if (res && res.success) {
                    shopSelect.innerHTML = res.shops.map(s => `
                        <option value="${s.id}">${SecurityClient.escapeHtml(s.name)} (${s.region_sector || 'Polonnaruwa'})</option>
                    `).join('');
                }
            });
        }
        document.getElementById('stockReturnModal').style.display = 'flex';
    }

    function openCrateModal() {
        const shopSelect = document.getElementById('crateShopSelect');
        if (shopSelect) {
            SecurityClient.secureFetch('/api/shops').then(res => {
                if (res && res.success) {
                    shopSelect.innerHTML = res.shops.map(s => `
                        <option value="${s.id}">${SecurityClient.escapeHtml(s.name)}</option>
                    `).join('');
                }
            });
        }
        document.getElementById('crateTrackModal').style.display = 'flex';
    }

    return {
        loadData,
        render,
        recalculateVariances,
        submitSettlement,
        openReturnModal,
        openCrateModal
    };
})();

window.SettlementView = SettlementView;

window.addEventListener('languageChanged', () => {
    if (document.getElementById('settlementViewContainer')) {
        SettlementView.loadData();
    }
});
