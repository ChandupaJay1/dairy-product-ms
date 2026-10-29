/**
 * Advanced Financial Analytics & Operations Reports
 * 
 * Features:
 * 1. Route Profitability & Diesel Fuel Cost Comparison
 * 2. Flavor Popularity Breakdown (Fast vs Slow Moving Flavors)
 * 3. Shop Monthly Statement Ledger (PDF/Print Export)
 * 4. Sales Rep Commission & Target Incentive Tracker
 */

const ReportsView = (() => {
    let routeData = [];
    let flavorData = [];
    let repData = [];
    let shopsList = [];
    let currentStatement = null;
    let activeSubTab = 'routes'; // 'routes', 'flavors', 'statement', 'commissions'

    async function loadData() {
        // Fast Cache-First Stale-While-Revalidate
        const cachedReports = OfflineStorage.getCachedData(OfflineStorage.CACHE_REPORTS_KEY);
        if (cachedReports) {
            routeData = cachedReports.routeData || routeData;
            flavorData = cachedReports.flavorData || flavorData;
            repData = cachedReports.repData || repData;
            shopsList = cachedReports.shopsList || shopsList;
            if (cachedReports.currentStatement) currentStatement = cachedReports.currentStatement;
            render();
        } else {
            SecurityClient.renderLoading(
                'reportsViewContainer',
                I18n.getLanguage() === 'si' ? 'ව්‍යාපාරික වාර්තා සහ ප්‍රස්ථාර පූරණය වෙමින් පවතී' : 'Business Analytics & Reports Preparing...',
                I18n.getLanguage() === 'si' ? '📊 Route Profitability, Flavor Trends සහ Rep කොමිස් විශ්ලේෂණය වෙමින් පවතී...' : 'Analyzing route profitability, diesel consumption, and flavor sales...',
                '📊'
            );
        }

        try {
            const [routesRes, flavorsRes, repsRes, shopsRes] = await Promise.all([
                SecurityClient.secureFetch('/api/reports/route-profitability'),
                SecurityClient.secureFetch('/api/reports/flavor-analytics'),
                SecurityClient.secureFetch('/api/reports/rep-commissions'),
                SecurityClient.secureFetch('/api/shops')
            ]);

            if (routesRes && routesRes.success) routeData = routesRes.route_profitability;
            if (flavorsRes && flavorsRes.success) flavorData = flavorsRes.flavors;
            if (repsRes && repsRes.success) repData = repsRes.reps;
            if (shopsRes && shopsRes.success) shopsList = shopsRes.shops;

            if (shopsList.length > 0 && !currentStatement) {
                await loadShopStatement(shopsList[0].id);
            }

            OfflineStorage.cacheOperationalData(OfflineStorage.CACHE_REPORTS_KEY, {
                routeData,
                flavorData,
                repData,
                shopsList,
                currentStatement
            });
        } catch (err) {
            console.warn('Reports background data fetch error:', err);
        }

        render();
    }

    async function loadShopStatement(shopId) {
        const res = await SecurityClient.secureFetch(`/api/reports/shop-statement/${shopId}`);
        if (res && res.success) {
            currentStatement = res;
        }
        render();
    }

    function switchSubTab(tab) {
        activeSubTab = tab;
        render();
    }

    function render() {
        const container = document.getElementById('reportsViewContainer');
        if (!container) return;

        container.innerHTML = `
            <div class="reports-shell eyecare-card p-4">
                <!-- Header Banner -->
                <div class="report-header-banner">
                    <div class="report-title-area">
                        <h2>${I18n.t('reports_title', '📊 Financial Analytics & Reports')}</h2>
                        <p>${I18n.t('reports_subtitle', 'Polonnaruwa Sector • Operations Intelligence & Profitability')}</p>
                    </div>
                    <div>
                        <button class="btn-report-action" onclick="ReportsView.exportReportPdf()">
                            ${I18n.t('btn_print_pdf', '🖨️ Export PDF')}
                        </button>
                    </div>
                </div>

                <!-- Sub-Tabs Navigation (Segmented Minimal Pill Buttons) -->
                <div class="report-subnav">
                    <button type="button" class="btn report-tab-btn ${activeSubTab === 'routes' ? 'active' : ''}" onclick="ReportsView.switchSubTab('routes')">
                        ${I18n.t('tab_route_profit', '🛣️ Route Profit')}
                    </button>
                    <button type="button" class="btn report-tab-btn ${activeSubTab === 'flavors' ? 'active' : ''}" onclick="ReportsView.switchSubTab('flavors')">
                        ${I18n.t('tab_flavor_trends', '🍦 Flavor Trends')}
                    </button>
                    <button type="button" class="btn report-tab-btn ${activeSubTab === 'statement' ? 'active' : ''}" onclick="ReportsView.switchSubTab('statement')">
                        ${I18n.t('tab_shop_statement', '🏪 Shop Statement')}
                    </button>
                    <button type="button" class="btn report-tab-btn ${activeSubTab === 'commissions' ? 'active' : ''}" onclick="ReportsView.switchSubTab('commissions')">
                        ${I18n.t('tab_rep_commissions', '🏆 Rep Commissions')}
                    </button>
                </div>

                <!-- Active Sub-Tab View Container -->
                <div class="report-content-body">
                    ${renderActiveSubTab()}
                </div>
            </div>
        `;
    }

    function renderActiveSubTab() {
        switch (activeSubTab) {
            case 'routes':
                return renderRouteProfitability();
            case 'flavors':
                return renderFlavorAnalytics();
            case 'statement':
                return renderShopStatementSection();
            case 'commissions':
                return renderRepCommissions();
            default:
                return renderRouteProfitability();
        }
    }

    // 1. ROUTE PROFITABILITY TAB
    function renderRouteProfitability() {
        return `
            <div>
                <div style="margin-bottom: 1rem;">
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-heading); margin: 0;">🛣️ Route Profitability & Efficiency</h3>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin: 2px 0 0;">Diesel: Rs. 370/L &bull; Consumption: 4.2 km/L &bull; Crew Shift Allowance: Rs. 3,500</p>
                </div>

                <!-- 4 Route Profit Cards -->
                <div class="report-cards-grid">
                    ${routeData.map(r => `
                        <div class="report-card">
                            <div>
                                <div class="report-card-header">
                                    <div>
                                        <h4 class="report-card-title">${SecurityClient.escapeHtml(r.route_name)}</h4>
                                        <div class="report-card-sub">${SecurityClient.escapeHtml(r.region_sector)} &bull; ${r.distance_km} km</div>
                                    </div>
                                    <span class="report-pill-badge">${r.profit_margin_percent}% Margin</span>
                                </div>
                                <div class="report-hero-metric">
                                    <span style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Net Profit</span>
                                    <span class="report-hero-val">${SecurityClient.formatCurrency(r.gross_profit)}</span>
                                </div>
                            </div>
                            <div class="report-stats-row">
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Revenue</span>
                                    <span class="report-stat-val">${SecurityClient.formatCurrency(r.total_revenue)}</span>
                                </div>
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Diesel</span>
                                    <span class="report-stat-val text-danger">Rs. ${r.fuel_expense.toLocaleString()}</span>
                                </div>
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Crew</span>
                                    <span class="report-stat-val text-muted">Rs. ${r.crew_allowance.toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>

                <!-- Comprehensive Route Financial Table -->
                <div class="report-table-box">
                    <h4 class="report-table-title">Route Financial Breakdown</h4>
                    <div class="table-responsive">
                        <table class="table text-xs">
                            <thead>
                                <tr>
                                    <th>${I18n.t('th_route', 'Route')} & Sector</th>
                                    <th>${I18n.t('th_distance', 'Distance')}</th>
                                    <th>${I18n.t('th_diesel_litres', 'Fuel (L)')}</th>
                                    <th>${I18n.t('th_fuel_cost', 'Diesel Cost')}</th>
                                    <th>${I18n.t('th_production_cost', 'Production COGS')}</th>
                                    <th>${I18n.t('th_crew_allowance', 'Crew Pay')}</th>
                                    <th>${I18n.t('th_total_revenue', 'Total Revenue')}</th>
                                    <th>${I18n.t('th_gross_profit', 'Net Margin')}</th>
                                    <th>${I18n.t('th_efficiency', 'Rating')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${routeData.map(r => `
                                    <tr>
                                        <td><strong>${SecurityClient.escapeHtml(r.route_name)}</strong></td>
                                        <td>${r.distance_km} km</td>
                                        <td>${r.diesel_litres} L</td>
                                        <td class="text-danger font-mono">${SecurityClient.formatCurrency(r.fuel_expense)}</td>
                                        <td class="text-muted font-mono">${SecurityClient.formatCurrency(r.cogs_production)}</td>
                                        <td class="text-muted font-mono">${SecurityClient.formatCurrency(r.crew_allowance)}</td>
                                        <td class="font-bold text-main font-mono">${SecurityClient.formatCurrency(r.total_revenue)}</td>
                                        <td class="font-bold text-success font-mono">${SecurityClient.formatCurrency(r.gross_profit)} (${r.profit_margin_percent}%)</td>
                                        <td>
                                            <span class="report-pill-badge" style="display:inline-block;">
                                                ${r.efficiency_status}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // 2. FLAVOR POPULARITY TAB
    function renderFlavorAnalytics() {
        return `
            <div>
                <div style="margin-bottom: 1rem;">
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-heading); margin: 0;">🍦 Flavor Demand & Velocity</h3>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin: 2px 0 0;">Monthly demand velocity and market share across the Polonnaruwa regional network</p>
                </div>

                <!-- Visual Distribution Bars -->
                <div class="report-table-box" style="margin-bottom: 1.25rem;">
                    <h4 class="report-table-title">Regional Market Share by Flavor</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        ${flavorData.map(f => `
                            <div>
                                <div class="flex-between" style="font-size: 0.8rem; margin-bottom: 4px;">
                                    <span style="font-weight: 600; color: var(--text-heading);">
                                        ${SecurityClient.escapeHtml(I18n.getProductFlavor(f))}
                                        <span class="report-pill-badge" style="margin-left: 6px; font-size: 0.68rem;">${f.ranking}</span>
                                    </span>
                                    <span style="font-weight: 700; color: var(--color-info, #38bdf8);">${f.cups_sold_month.toLocaleString()} cups (${f.share_percent}%)</span>
                                </div>
                                <div style="height: 7px; background: rgba(255,255,255,0.06); border-radius: 999px; overflow: hidden;">
                                    <div style="width: ${f.share_percent}%; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #38bdf8, #10b981);"></div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <!-- Flavor Sales Data Table -->
                <div class="report-table-box">
                    <h4 class="report-table-title">Flavor Profitability & Shelf Velocity</h4>
                    <div class="table-responsive">
                        <table class="table text-xs">
                            <thead>
                                <tr>
                                    <th>${I18n.t('th_flavor', 'Flavor Description')}</th>
                                    <th>${I18n.t('th_sku', 'SKU Code')}</th>
                                    <th>${I18n.t('th_unit_price', 'Unit Price')}</th>
                                    <th>${I18n.t('th_monthly_sales', 'Monthly Volume')}</th>
                                    <th>${I18n.t('th_market_share', 'Market Share')}</th>
                                    <th>${I18n.t('th_monthly_rev', 'Monthly Gross Revenue')}</th>
                                    <th>Shelf Life</th>
                                    <th>${I18n.t('th_popularity', 'Velocity Tier')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${flavorData.map(f => `
                                    <tr>
                                        <td><strong>${SecurityClient.escapeHtml(I18n.getProductFlavor(f))}</strong></td>
                                        <td class="font-mono text-muted">${f.sku_code}</td>
                                        <td>${SecurityClient.formatCurrency(f.unit_price)}</td>
                                        <td class="font-bold text-main font-mono">${f.cups_sold_month.toLocaleString()} cups</td>
                                        <td><strong class="text-info">${f.share_percent}%</strong></td>
                                        <td class="font-bold text-success font-mono">${SecurityClient.formatCurrency(f.monthly_revenue)}</td>
                                        <td>${f.shelf_life_days} Days</td>
                                        <td>
                                            <span class="report-pill-badge">
                                                ${f.ranking}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // 3. SHOP MONTHLY STATEMENT TAB
    function renderShopStatementSection() {
        if (!currentStatement) {
            return `<div class="p-4 text-center text-muted">Loading shop ledger...</div>`;
        }

        const shop = currentStatement.shop;
        const ledger = currentStatement.ledger;

        return `
            <div>
                <div class="flex-between" style="flex-wrap: wrap; gap: 10px; margin-bottom: 1.25rem;">
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-heading); margin: 0;">🏪 Customer Monthly Statement</h3>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin: 2px 0 0;">Invoices, payments, and credit notes ledger</p>
                    </div>

                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        <select class="form-control" style="width: 240px; padding: 6px 10px; font-size: 0.82rem;" onchange="ReportsView.onShopSelectChange(this.value)">
                            ${shopsList.map(s => `
                                <option value="${s.id}" ${s.id === shop.id ? 'selected' : ''}>
                                    ${SecurityClient.escapeHtml(s.name)}
                                </option>
                            `).join('')}
                        </select>
                        <button class="btn-report-action" onclick="ReportsView.openStatementModal()">
                            <span>🖨️</span> <span>Statement Slip</span>
                        </button>
                    </div>
                </div>

                <!-- Shop Profile Summary Cards -->
                <div class="report-cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.25rem;">
                    <div class="report-card">
                        <span class="report-stat-label">Customer Name</span>
                        <div style="font-size: 0.95rem; font-weight: 700; color: var(--text-heading); margin-top: 4px;">${SecurityClient.escapeHtml(shop.name)}</div>
                        <div class="text-xs text-muted" style="margin-top: 2px;">${SecurityClient.escapeHtml(shop.owner_name)} &bull; ${shop.phone}</div>
                    </div>
                    <div class="report-card">
                        <span class="report-stat-label">Assigned Route</span>
                        <div style="font-size: 0.95rem; font-weight: 700; color: var(--color-info, #38bdf8); margin-top: 4px;">${SecurityClient.escapeHtml(shop.route_name)}</div>
                        <div class="text-xs text-muted" style="margin-top: 2px;">Polonnaruwa Sector</div>
                    </div>
                    <div class="report-card">
                        <span class="report-stat-label">Credit Tier & Limit</span>
                        <div style="font-size: 0.95rem; font-weight: 700; color: var(--accent-amber, #f59e0b); margin-top: 4px;">${shop.credit_tier}</div>
                        <div class="text-xs text-muted" style="margin-top: 2px;">Limit: ${SecurityClient.formatCurrency(shop.credit_limit)}</div>
                    </div>
                    <div class="report-card">
                        <span class="report-stat-label">Outstanding Balance</span>
                        <div style="font-size: 1.25rem; font-weight: 800; font-family: monospace; color: var(--color-sos, #ef4444); margin-top: 4px;">${SecurityClient.formatCurrency(shop.outstanding_balance)}</div>
                        <div class="text-xs text-muted" style="margin-top: 2px;">Current Debt</div>
                    </div>
                </div>

                <!-- Ledger History Table -->
                <div class="report-table-box">
                    <h4 class="report-table-title">Chronological Transaction Ledger</h4>
                    <div class="table-responsive">
                        <table class="table text-xs">
                            <thead>
                                <tr>
                                    <th>${I18n.t('stmt_ledger_date', 'Date')}</th>
                                    <th>Type</th>
                                    <th>${I18n.t('stmt_ledger_ref', 'Reference #')}</th>
                                    <th>${I18n.t('stmt_ledger_desc', 'Description')}</th>
                                    <th class="text-right">${I18n.t('stmt_ledger_debit', 'Debit (Invoices +)')}</th>
                                    <th class="text-right">${I18n.t('stmt_ledger_credit', 'Credit (Payments/Returns -)')}</th>
                                    <th class="text-right">${I18n.t('stmt_ledger_balance', 'Running Balance')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${ledger.length === 0 ? `
                                    <tr><td colspan="7" class="text-center text-muted p-3">No recorded ledger history for this shop yet.</td></tr>
                                ` : ledger.map(l => `
                                    <tr>
                                        <td>${l.date}</td>
                                        <td>
                                            <span class="badge ${l.type === 'INVOICE' ? 'badge-primary' : (l.type === 'PAYMENT' ? 'badge-success' : 'badge-warning')}">
                                                ${l.type}
                                            </span>
                                        </td>
                                        <td class="font-mono"><strong>${SecurityClient.escapeHtml(l.ref_no)}</strong></td>
                                        <td>${SecurityClient.escapeHtml(l.description)}</td>
                                        <td class="text-right font-mono text-danger font-bold">
                                            ${l.debit > 0 ? SecurityClient.formatCurrency(l.debit) : '-'}
                                        </td>
                                        <td class="text-right font-mono text-success font-bold">
                                            ${l.credit > 0 ? SecurityClient.formatCurrency(l.credit) : '-'}
                                        </td>
                                        <td class="text-right font-mono font-bold ${l.running_balance > 0 ? 'text-danger' : 'text-main'}">
                                            ${SecurityClient.formatCurrency(l.running_balance)}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // 4. SALES REP COMMISSIONS TAB
    function renderRepCommissions() {
        return `
            <div>
                <div style="margin-bottom: 1rem;">
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-heading); margin: 0;">🏆 Sales Rep Performance & Incentives</h3>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin: 2px 0 0;">Volume: Rs. 1.50/cup &bull; Target Bonus: Rs. 2,500 &bull; Cash Recovery Incentive</p>
                </div>

                <div class="report-cards-grid">
                    ${repData.map(r => `
                        <div class="report-card">
                            <div>
                                <div class="report-card-header">
                                    <div>
                                        <h4 class="report-card-title">${SecurityClient.escapeHtml(r.name)}</h4>
                                        <div class="report-card-sub">${r.phone}</div>
                                    </div>
                                    <span class="report-pill-badge">${r.status}</span>
                                </div>
                                <div style="margin: 0.5rem 0;">
                                    <div class="flex-between text-xs" style="margin-bottom: 3px;">
                                        <span style="color:var(--text-muted);">Target:</span>
                                        <span style="font-weight:700; color:var(--color-info, #38bdf8);">${r.cups_sold.toLocaleString()} / ${r.target_cups.toLocaleString()} (${r.achievement_percent}%)</span>
                                    </div>
                                    <div style="height: 6px; background: rgba(255,255,255,0.06); border-radius: 999px; overflow: hidden;">
                                        <div style="width: ${Math.min(100, r.achievement_percent)}%; height: 100%; border-radius: 999px; background: ${r.achievement_percent >= 100 ? '#10b981' : '#38bdf8'};"></div>
                                    </div>
                                </div>
                                <div class="report-hero-metric">
                                    <span style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Total Payout</span>
                                    <span class="report-hero-val">${SecurityClient.formatCurrency(r.total_incentive)}</span>
                                </div>
                            </div>
                            <div class="report-stats-row">
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Volume</span>
                                    <span class="report-stat-val">Rs. ${r.volume_commission.toLocaleString()}</span>
                                </div>
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Target</span>
                                    <span class="report-stat-val">Rs. ${r.target_bonus.toLocaleString()}</span>
                                </div>
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Cash</span>
                                    <span class="report-stat-val">Rs. ${r.cash_collection_bonus.toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>

                <div class="report-table-box">
                    <h4 class="report-table-title">Monthly Representative Payroll & Commission Audit</h4>
                    <div class="table-responsive">
                        <table class="table text-xs">
                            <thead>
                                <tr>
                                    <th>${I18n.t('th_rep_name', 'Sales Rep Name')}</th>
                                    <th>Contact</th>
                                    <th>${I18n.t('th_target_cups', 'Volume Delivered')}</th>
                                    <th>${I18n.t('th_achievement', 'Target Progress')}</th>
                                    <th>${I18n.t('th_vol_commission', 'Base Commission')}</th>
                                    <th>${I18n.t('th_bonuses', 'Target Bonus')}</th>
                                    <th>Cash Bonus</th>
                                    <th>${I18n.t('th_total_payout', 'Total Incentive Payout')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${repData.map(r => `
                                    <tr>
                                        <td><strong>${SecurityClient.escapeHtml(r.name)}</strong></td>
                                        <td>${r.phone}</td>
                                        <td class="font-bold text-main font-mono">${r.cups_sold.toLocaleString()} cups</td>
                                        <td>
                                            <span style="font-weight:600; color:${r.achievement_percent >= 100 ? '#10b981' : '#38bdf8'};">${r.achievement_percent}%</span>
                                        </td>
                                        <td class="font-mono text-muted">${SecurityClient.formatCurrency(r.volume_commission)}</td>
                                        <td class="font-mono text-muted">${SecurityClient.formatCurrency(r.target_bonus)}</td>
                                        <td class="font-mono text-muted">${SecurityClient.formatCurrency(r.cash_collection_bonus)}</td>
                                        <td class="font-mono font-bold text-success" style="font-size:0.9rem;">
                                            ${SecurityClient.formatCurrency(r.total_incentive)}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    function onShopSelectChange(shopId) {
        loadShopStatement(shopId);
    }

    function exportReportPdf() {
        const activeContainer = document.querySelector('.report-content-body');
        if (!activeContainer) return;

        const tabTitles = {
            'routes': 'Route Profitability & COGS Fleet Economics',
            'flavors': '5-Flavor Portfolio Performance & Regional Demand Trends',
            'statement': 'Retail Shop Monthly Running Statement Ledger',
            'commissions': 'Sales Representatives & Driver Commission Pay-Sheet'
        };

        const title = tabTitles[activeSubTab] || 'PulseERP Financial Report';
        const printWindow = window.open('', '_blank', 'width=900,height=850');
        if (!printWindow) {
            window.print();
            return;
        }

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset="utf-8">
                    <title>${title} - PulseERP Polonnaruwa</title>
                    <style>
                        @page { size: A4 portrait; margin: 12mm 10mm; }
                        * { box-sizing: border-box; }
                        body {
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            color: #0F172A;
                            background: #FFFFFF;
                            padding: 20px;
                            margin: 0;
                            font-size: 11px;
                            line-height: 1.4;
                        }
                        .print-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            border-bottom: 2px solid #0F172A;
                            padding-bottom: 10px;
                            margin-bottom: 15px;
                        }
                        .print-title { font-size: 16px; font-weight: 800; color: #1E3A8A; margin: 0; }
                        .print-sub { font-size: 10px; color: #64748B; margin-top: 2px; }
                        .print-meta { text-align: right; font-size: 10px; color: #475569; }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 10px;
                            font-size: 10.5px;
                        }
                        th {
                            background-color: #F1F5F9;
                            color: #1E293B;
                            font-weight: 700;
                            text-align: left;
                            padding: 6px 8px;
                            border: 1px solid #CBD5E1;
                            text-transform: uppercase;
                            font-size: 9.5px;
                        }
                        td {
                            padding: 6px 8px;
                            border: 1px solid #E2E8F0;
                        }
                        tr:nth-child(even) td { background-color: #F8FAFC; }
                        .text-right { text-align: right; }
                        .text-center { text-align: center; }
                        .font-bold { font-weight: bold; }
                        .font-mono { font-family: monospace; }
                        .badge {
                            display: inline-block;
                            padding: 2px 6px;
                            border-radius: 4px;
                            font-size: 9px;
                            font-weight: 700;
                            border: 1px solid #94A3B8;
                        }
                        .card, .eyecare-card {
                            border: 1px solid #E2E8F0;
                            border-radius: 6px;
                            padding: 10px;
                            margin-bottom: 12px;
                            background: #FFFFFF;
                        }
                        .grid { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
                        .grid > div { flex: 1; min-width: 140px; }
                        .btn, button, select, input { display: none !important; }
                        .print-footer {
                            margin-top: 25px;
                            padding-top: 10px;
                            border-top: 1px dashed #CBD5E1;
                            display: flex;
                            justify-content: space-between;
                            font-size: 9px;
                            color: #64748B;
                        }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <div>
                            <div class="print-title">PULSE-ERP • POLONNARUWA DAIRY FLEET</div>
                            <div class="print-sub">${title}</div>
                        </div>
                        <div class="print-meta">
                            <div>Generated: <strong>${new Date().toLocaleString()}</strong></div>
                            <div>Sector: <strong>Polonnaruwa Main Depot</strong></div>
                            <div>Confidential Audit Report</div>
                        </div>
                    </div>

                    ${activeContainer.innerHTML}

                    <div class="print-footer">
                        <div>Verified by PulseERP Intelligence Core Suite &bull; System Generated</div>
                        <div>Page 1 of 1</div>
                    </div>

                    <script>
                        window.onload = function() {
                            setTimeout(() => {
                                window.print();
                            }, 250);
                        };
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }

    function openStatementModal() {
        if (!currentStatement) return;

        const shop = currentStatement.shop;
        const ledger = currentStatement.ledger;

        document.getElementById('stmtDate').textContent = shop.statement_date || new Date().toISOString().split('T')[0];
        document.getElementById('stmtShopName').textContent = shop.name;
        document.getElementById('stmtOwnerName').textContent = shop.owner_name;
        document.getElementById('stmtPhone').textContent = shop.phone;
        document.getElementById('stmtRouteName').textContent = shop.route_name;
        document.getElementById('stmtTier').textContent = shop.credit_tier;
        document.getElementById('stmtCreditLimit').textContent = SecurityClient.formatCurrency(shop.credit_limit);
        document.getElementById('stmtOutstanding').textContent = SecurityClient.formatCurrency(shop.outstanding_balance);
        document.getElementById('stmtFinalBalance').textContent = SecurityClient.formatCurrency(shop.outstanding_balance);

        const tbody = document.getElementById('stmtLedgerTbody');
        if (tbody) {
            tbody.innerHTML = ledger.length === 0 ? `
                <tr><td colspan="6" style="text-align:center; padding:12px; color:#64748B;">No ledger transactions found.</td></tr>
            ` : ledger.map(l => `
                <tr style="border-bottom: 1px solid #E2E8F0;">
                    <td style="padding:6px;">${l.date}</td>
                    <td style="padding:6px; font-family:monospace;"><strong>${SecurityClient.escapeHtml(l.ref_no)}</strong></td>
                    <td style="padding:6px;">${SecurityClient.escapeHtml(l.description)}</td>
                    <td style="padding:6px; text-align:right; color:#DC2626; font-weight:bold;">
                        ${l.debit > 0 ? SecurityClient.formatCurrency(l.debit) : '-'}
                    </td>
                    <td style="padding:6px; text-align:right; color:#16A34A; font-weight:bold;">
                        ${l.credit > 0 ? SecurityClient.formatCurrency(l.credit) : '-'}
                    </td>
                    <td style="padding:6px; text-align:right; font-weight:bold;">
                        ${SecurityClient.formatCurrency(l.running_balance)}
                    </td>
                </tr>
            `).join('');
        }

        const modal = document.getElementById('shopStatementModal');
        if (modal) {
            modal.style.display = 'flex';
            I18n.applyTranslations(modal);
        }
    }

    return {
        loadData,
        render,
        switchSubTab,
        onShopSelectChange,
        exportReportPdf,
        openStatementModal
    };
})();

window.ReportsView = ReportsView;

window.addEventListener('languageChanged', () => {
    if (document.getElementById('reportsViewContainer')) {
        ReportsView.loadData();
    }
});
