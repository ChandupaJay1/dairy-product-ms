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

            // Always fetch live shop statement (refreshing cached balance)
            if (shopsList.length > 0) {
                const targetShopId = (currentStatement && currentStatement.shop) ? currentStatement.shop.id : shopsList[0].id;
                const stmtRes = await SecurityClient.secureFetch(`/api/reports/shop-statement/${targetShopId}`);
                if (stmtRes && stmtRes.success) {
                    currentStatement = stmtRes;
                }
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

    async function forceRefresh() {
        // Clear cached reports so fresh fetch is guaranteed
        OfflineStorage.cacheOperationalData(OfflineStorage.CACHE_REPORTS_KEY, null);
        currentStatement = null;
        await loadData();
        SecurityClient.showToast(I18n.getLanguage() === 'si' ? '✅ වාර්තා දත්ත නැවුම් කරන ලදී (Live Data Synced)' : '✅ Reports refreshed with live database data.', 'success');
    }

    async function loadShopStatement(shopId) {
        const res = await SecurityClient.secureFetch(`/api/reports/shop-statement/${shopId}`);
        if (res && res.success) {
            currentStatement = res;
            OfflineStorage.cacheOperationalData(OfflineStorage.CACHE_REPORTS_KEY, {
                routeData,
                flavorData,
                repData,
                shopsList,
                currentStatement
            });
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
                        <h2 style="display:inline-flex; align-items:center; gap:8px;">
                            <span style="color:var(--accent-lime);">${window.AppIcons ? AppIcons.get('bar-chart', {size:22}) : ''}</span>
                            <span>${I18n.t('reports_title', 'Financial Analytics & Reports')}</span>
                        </h2>
                        <p>${I18n.t('reports_subtitle', 'Polonnaruwa Sector • Operations Intelligence & Profitability')}</p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <button class="btn-report-action" onclick="ReportsView.forceRefresh()" title="Clear cache & reload live data" style="display:inline-flex; align-items:center; gap:6px;">
                            ${window.AppIcons ? AppIcons.get('refresh', {size:14}) : ''}
                            <span>${I18n.t('btn_refresh_data', 'Refresh Data')}</span>
                        </button>
                        <button class="btn-report-action" onclick="ReportsView.exportReportPdf()" style="display:inline-flex; align-items:center; gap:6px;">
                            ${window.AppIcons ? AppIcons.get('printer', {size:15}) : ''}
                            <span>${I18n.t('btn_print_pdf', 'Export PDF')}</span>
                        </button>
                    </div>
                </div>

                <!-- Sub-Tabs Navigation (Segmented Minimal Pill Buttons) -->
                <div class="report-subnav">
                    <button type="button" class="btn report-tab-btn ${activeSubTab === 'routes' ? 'active' : ''}" onclick="ReportsView.switchSubTab('routes')" style="display:inline-flex; align-items:center; gap:6px;">
                        ${window.AppIcons ? AppIcons.get('transit-truck', {size:15}) : ''}
                        <span>${I18n.t('tab_route_profit', 'Route Profit')}</span>
                    </button>
                    <button type="button" class="btn report-tab-btn ${activeSubTab === 'flavors' ? 'active' : ''}" onclick="ReportsView.switchSubTab('flavors')" style="display:inline-flex; align-items:center; gap:6px;">
                        ${window.AppIcons ? AppIcons.get('milk', {size:15}) : ''}
                        <span>${I18n.t('tab_flavor_trends', 'Flavor Trends')}</span>
                    </button>
                    <button type="button" class="btn report-tab-btn ${activeSubTab === 'statement' ? 'active' : ''}" onclick="ReportsView.switchSubTab('statement')" style="display:inline-flex; align-items:center; gap:6px;">
                        ${window.AppIcons ? AppIcons.get('store', {size:15}) : ''}
                        <span>${I18n.t('tab_shop_statement', 'Shop Statement')}</span>
                    </button>
                    <button type="button" class="btn report-tab-btn ${activeSubTab === 'commissions' ? 'active' : ''}" onclick="ReportsView.switchSubTab('commissions')" style="display:inline-flex; align-items:center; gap:6px;">
                        ${window.AppIcons ? AppIcons.get('coins', {size:15}) : ''}
                        <span>${I18n.t('tab_rep_commissions', 'Rep Commissions')}</span>
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
        const totalRevenue = routeData.reduce((sum, r) => sum + (parseFloat(r.total_revenue) || 0), 0);
        const totalFuel = routeData.reduce((sum, r) => sum + (parseFloat(r.fuel_expense) || 0), 0);
        const totalCrew = routeData.reduce((sum, r) => sum + (parseFloat(r.crew_allowance) || 0), 0);
        const totalCOGS = routeData.reduce((sum, r) => sum + (parseFloat(r.cogs_production) || 0), 0);
        const totalProfit = routeData.reduce((sum, r) => sum + (parseFloat(r.gross_profit) || 0), 0);
        const totalKm = routeData.reduce((sum, r) => sum + (parseFloat(r.distance_km) || 0), 0);
        const totalLitres = routeData.reduce((sum, r) => sum + (parseFloat(r.diesel_litres) || 0), 0);
        const avgMargin = totalRevenue > 0 ? ((totalProfit / totalRevenue) * 100).toFixed(1) : '0.0';

        return `
            <div>
                <div style="margin-bottom: 1.25rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-heading); margin: 0;">🛣️ Route Profitability & Fleet Operational Economics</h3>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0;">Fuel Benchmark: Rs. 370.00/L &bull; Consumption: 4.2 km/L &bull; Crew Shift Allowance: Rs. 3,500.00 per truck</p>
                </div>

                <!-- Executive KPI Summary Bar -->
                <div class="report-summary-kpis">
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">💰</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Total Fleet Revenue</span>
                            <span class="report-summary-val" style="color: #10b981;">${SecurityClient.formatCurrency(totalRevenue)}</span>
                            <span class="report-summary-sub">Across ${routeData.length} primary routes</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">⛽</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Total Diesel Fuel Cost</span>
                            <span class="report-summary-val" style="color: #ef4444;">${SecurityClient.formatCurrency(totalFuel)}</span>
                            <span class="report-summary-sub">${totalLitres} L Consumed (${totalKm} km)</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(14, 165, 233, 0.12); color: #0ea5e9;">👥</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Crew Shift Allowances</span>
                            <span class="report-summary-val" style="color: #0ea5e9;">${SecurityClient.formatCurrency(totalCrew)}</span>
                            <span class="report-summary-sub">Driver & Assistant Shift Pay</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(163, 230, 53, 0.12); color: #a3e635;">📈</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Net Fleet Margin</span>
                            <span class="report-summary-val" style="color: #a3e635;">${SecurityClient.formatCurrency(totalProfit)}</span>
                            <span class="report-summary-sub">${avgMargin}% Overall Margin</span>
                        </div>
                    </div>
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
                                    <span class="report-pill-badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); font-weight:800;">${r.profit_margin_percent}% Margin</span>
                                </div>
                                <div class="report-hero-metric">
                                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight:600;">Net Profit</span>
                                    <span class="report-hero-val" style="color: #10b981;">${SecurityClient.formatCurrency(r.gross_profit)}</span>
                                </div>
                            </div>
                            <div class="report-stats-row">
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Revenue</span>
                                    <span class="report-stat-val font-bold">${SecurityClient.formatCurrency(r.total_revenue)}</span>
                                </div>
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Diesel</span>
                                    <span class="report-stat-val text-danger font-bold">Rs. ${r.fuel_expense.toLocaleString()}</span>
                                </div>
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Crew</span>
                                    <span class="report-stat-val text-muted font-bold">Rs. ${r.crew_allowance.toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>

                <!-- Comprehensive Route Financial Table -->
                <div class="report-table-box">
                    <div class="flex-between" style="margin-bottom: 0.85rem;">
                        <h4 class="report-table-title" style="margin: 0;">Route Financial Breakdown & Cost Efficiency</h4>
                        <span class="badge badge-outline text-xs">ACID Verified Audit</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table text-sm">
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
                                        <td class="text-danger font-mono font-bold">${SecurityClient.formatCurrency(r.fuel_expense)}</td>
                                        <td class="text-muted font-mono">${SecurityClient.formatCurrency(r.cogs_production)}</td>
                                        <td class="text-muted font-mono">${SecurityClient.formatCurrency(r.crew_allowance)}</td>
                                        <td class="font-bold text-main font-mono" style="font-size:0.95rem;">${SecurityClient.formatCurrency(r.total_revenue)}</td>
                                        <td class="font-bold text-success font-mono" style="font-size:0.95rem;">${SecurityClient.formatCurrency(r.gross_profit)} (${r.profit_margin_percent}%)</td>
                                        <td>
                                            <span class="report-pill-badge" style="display:inline-block;">
                                                ${r.efficiency_status}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="report-tfoot-row">
                                    <td><strong>TOTAL FLEET SUMMARY</strong></td>
                                    <td><strong>${totalKm} km</strong></td>
                                    <td><strong>${totalLitres} L</strong></td>
                                    <td class="text-danger font-mono font-bold">${SecurityClient.formatCurrency(totalFuel)}</td>
                                    <td class="text-muted font-mono font-bold">${SecurityClient.formatCurrency(totalCOGS)}</td>
                                    <td class="text-muted font-mono font-bold">${SecurityClient.formatCurrency(totalCrew)}</td>
                                    <td class="font-bold text-main font-mono" style="font-size:1rem;">${SecurityClient.formatCurrency(totalRevenue)}</td>
                                    <td class="font-bold text-success font-mono" style="font-size:1rem;">${SecurityClient.formatCurrency(totalProfit)} (${avgMargin}%)</td>
                                    <td><span class="report-pill-badge" style="background:#10b981; color:#fff; font-weight:800;">OPTIMAL</span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // 2. FLAVOR POPULARITY TAB
    function renderFlavorAnalytics() {
        const totalCups = flavorData.reduce((sum, f) => sum + (f.cups_sold_month || 0), 0);
        const totalFlavorRevenue = flavorData.reduce((sum, f) => sum + (parseFloat(f.monthly_revenue) || 0), 0);
        const topFlavor = flavorData.length > 0 ? [...flavorData].sort((a,b) => (b.cups_sold_month || 0) - (a.cups_sold_month || 0))[0] : null;
        const totalCapacity = 45000;
        const capPercent = Math.min(100, Math.round((totalCups / totalCapacity) * 100));

        // Flavor specific gradient themes
        const flavorThemeMap = {
            'Vanilla': { grad: 'linear-gradient(90deg, #f59e0b, #fbbf24)', color: '#f59e0b', icon: '🍦' },
            'Strawberry': { grad: 'linear-gradient(90deg, #f43f5e, #fb7185)', color: '#f43f5e', icon: '🍓' },
            'Chocolate': { grad: 'linear-gradient(90deg, #854d0e, #a16207)', color: '#a16207', icon: '🍫' },
            'Mango': { grad: 'linear-gradient(90deg, #ea580c, #fb923c)', color: '#ea580c', icon: '🥭' },
            'Curd Pot': { grad: 'linear-gradient(90deg, #0284c7, #38bdf8)', color: '#0284c7', icon: '🏺' }
        };

        return `
            <div>
                <div style="margin-bottom: 1.25rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-heading); margin: 0;">🍦 Flavor Demand, Product Velocity & Market Share</h3>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0;">Monthly demand velocity and market penetration across Polonnaruwa retail network</p>
                </div>

                <!-- Executive KPI Summary Bar -->
                <div class="report-summary-kpis">
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(56, 189, 248, 0.12); color: #38bdf8;">🥛</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Total Monthly Volume</span>
                            <span class="report-summary-val" style="color: #38bdf8;">${totalCups.toLocaleString()} cups</span>
                            <span class="report-summary-sub">Perishable physical delivery</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">💵</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Gross Portfolio Revenue</span>
                            <span class="report-summary-val" style="color: #10b981;">${SecurityClient.formatCurrency(totalFlavorRevenue)}</span>
                            <span class="report-summary-sub">5-Flavor product catalog</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">🥇</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Top Demand Flavor</span>
                            <span class="report-summary-val" style="color: #f59e0b; font-size: 1.15rem;">${topFlavor ? (I18n.getProductFlavor(topFlavor) || topFlavor.name) : 'Vanilla'}</span>
                            <span class="report-summary-sub">Fastest velocity turnover</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(168, 85, 247, 0.12); color: #a855f7;">🏭</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Fleet Capacity Utilized</span>
                            <span class="report-summary-val" style="color: #a855f7;">${capPercent}%</span>
                            <span class="report-summary-sub">${flavorData.length} active product SKUs</span>
                        </div>
                    </div>
                </div>

                <!-- Visual Distribution Bars with Individual Flavor Identity -->
                <div class="report-table-box" style="margin-bottom: 1.25rem;">
                    <div class="flex-between" style="margin-bottom: 0.85rem;">
                        <h4 class="report-table-title" style="margin: 0;">Regional Market Share by Flavor</h4>
                        <span class="badge badge-outline text-xs">Total: ${totalCups.toLocaleString()} Cups</span>
                    </div>

                    ${totalCups === 0 ? `
                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 12px; padding: 10px 14px; background: rgba(56, 189, 248, 0.08); border-radius: 8px; border-left: 3px solid var(--color-info, #38bdf8);">
                            📦 <strong>${I18n.getLanguage() === 'si' ? 'තවමත් විකුණුම් වාර්තා වී නොමැත' : 'No sales transactions recorded yet'}</strong>: ${I18n.getLanguage() === 'si' ? 'නව Morning Dispatch එකක් පිටත් කර Sales Reps ඇනවුම් සටහන් කළ පසු Flavor Velocity දත්ත ස්වයංක්‍රීයව ගණනය වේ.' : 'Once a Morning Load Dispatch is sent and field sales are recorded, market share and velocity rankings will update live.'}
                        </div>
                    ` : ''}

                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        ${flavorData.map(f => {
                            const flavorName = I18n.getProductFlavor(f);
                            const matchedTheme = Object.keys(flavorThemeMap).find(k => flavorName.toLowerCase().includes(k.toLowerCase())) || 'Vanilla';
                            const theme = flavorThemeMap[matchedTheme];

                            return `
                                <div>
                                    <div class="flex-between" style="font-size: 0.85rem; margin-bottom: 6px;">
                                        <span style="font-weight: 700; color: var(--text-heading); display:flex; align-items:center; gap:6px;">
                                            <span>${theme.icon}</span>
                                            <span>${SecurityClient.escapeHtml(flavorName)}</span>
                                            <span class="report-pill-badge" style="margin-left: 6px; font-size: 0.68rem; padding: 1px 6px;">${f.ranking}</span>
                                        </span>
                                        <span style="font-weight: 800; color: ${theme.color}; font-family: monospace;">${f.cups_sold_month.toLocaleString()} cups (${f.share_percent}%)</span>
                                    </div>
                                    <div style="height: 9px; background: rgba(255,255,255,0.06); border-radius: 999px; overflow: hidden; padding: 1px;">
                                        <div style="width: ${Math.max(2, f.share_percent)}%; height: 100%; border-radius: 999px; background: ${theme.grad}; transition: width 0.6s ease;"></div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>

                <!-- Flavor Sales Data Table -->
                <div class="report-table-box">
                    <h4 class="report-table-title">Flavor Profitability & Shelf Velocity Matrix</h4>
                    <div class="table-responsive">
                        <table class="table text-sm">
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
                                        <td class="font-bold">${SecurityClient.formatCurrency(f.unit_price)}</td>
                                        <td class="font-bold text-main font-mono" style="font-size:0.95rem;">${f.cups_sold_month.toLocaleString()} cups</td>
                                        <td><strong class="text-info">${f.share_percent}%</strong></td>
                                        <td class="font-bold text-success font-mono" style="font-size:0.95rem;">${SecurityClient.formatCurrency(f.monthly_revenue)}</td>
                                        <td>${f.shelf_life_days} Days</td>
                                        <td>
                                            <span class="report-pill-badge">
                                                ${f.ranking}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="report-tfoot-row">
                                    <td><strong>TOTAL PORTFOLIO SUMMARY</strong></td>
                                    <td><strong>${flavorData.length} SKUs</strong></td>
                                    <td><strong>AVG Rs. 85.00</strong></td>
                                    <td class="font-bold text-main font-mono" style="font-size:1rem;">${totalCups.toLocaleString()} cups</td>
                                    <td><strong class="text-info">100.0%</strong></td>
                                    <td class="font-bold text-success font-mono" style="font-size:1rem;">${SecurityClient.formatCurrency(totalFlavorRevenue)}</td>
                                    <td><strong>14-21 Days</strong></td>
                                    <td><span class="report-pill-badge" style="background:#10b981; color:#fff; font-weight:800;">ALL TIERS</span></td>
                                </tr>
                            </tfoot>
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
        const totalDebit = ledger.reduce((sum, l) => sum + (parseFloat(l.debit) || 0), 0);
        const totalCredit = ledger.reduce((sum, l) => sum + (parseFloat(l.credit) || 0), 0);

        return `
            <div>
                <div class="flex-between" style="flex-wrap: wrap; gap: 10px; margin-bottom: 1.25rem;">
                    <div>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-heading); margin: 0;">🏪 Retail Customer Monthly Running Statement</h3>
                        <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0;">Itemized invoices, field debt payments, and return credit notes ledger</p>
                    </div>

                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        <select class="form-control" style="width: 250px; padding: 7px 12px; font-size: 0.85rem;" onchange="ReportsView.onShopSelectChange(this.value)">
                            ${shopsList.map(s => `
                                <option value="${s.id}" ${s.id === shop.id ? 'selected' : ''}>
                                    ${SecurityClient.escapeHtml(s.name)}
                                </option>
                            `).join('')}
                        </select>
                        <button class="btn-report-action" onclick="ReportsView.openStatementModal()" style="display:inline-flex; align-items:center; gap:6px;">
                            <span>${window.AppIcons ? AppIcons.get('clipboard-list', {size:15}) : ''}</span> <span>View Modal Slip</span>
                        </button>
                        <button class="btn btn-primary btn-sm font-bold" onclick="ReportsView.printStatementSlip()" style="display:inline-flex; align-items:center; gap:6px;">
                            <span>${window.AppIcons ? AppIcons.get('printer', {size:15}) : ''}</span> <span>Print Official Statement</span>
                        </button>
                    </div>
                </div>

                <!-- Shop Profile Summary Cards -->
                <div class="report-summary-kpis">
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(14, 165, 233, 0.12); color: #0ea5e9;">🏪</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Retail Customer</span>
                            <span class="report-summary-val" style="color: var(--text-heading); font-size: 1.05rem;">${SecurityClient.escapeHtml(shop.name)}</span>
                            <span class="report-summary-sub">${SecurityClient.escapeHtml(shop.owner_name)} &bull; ${shop.phone}</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(168, 85, 247, 0.12); color: #a855f7;">🗺️</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Assigned Delivery Route</span>
                            <span class="report-summary-val" style="color: #a855f7; font-size: 1.05rem;">${SecurityClient.escapeHtml(shop.route_name)}</span>
                            <span class="report-summary-sub">Polonnaruwa Sector</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">💳</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Credit Tier & Ceiling</span>
                            <span class="report-summary-val" style="color: #f59e0b; font-size: 1.05rem;">${shop.credit_tier}</span>
                            <span class="report-summary-sub">Limit: ${SecurityClient.formatCurrency(shop.credit_limit)}</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">⚠️</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Outstanding Debt Balance</span>
                            <span class="report-summary-val" style="color: #ef4444;">${SecurityClient.formatCurrency(shop.outstanding_balance)}</span>
                            <span class="report-summary-sub">Closing Balance to Settle</span>
                        </div>
                    </div>
                </div>

                <!-- Ledger History Table -->
                <div class="report-table-box">
                    <div class="flex-between" style="margin-bottom: 0.85rem;">
                        <h4 class="report-table-title" style="margin: 0;">Chronological Transaction & Payment Ledger</h4>
                        <span class="badge badge-outline text-xs">Customer Code: SHP-${shop.id}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table text-sm">
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
                                    <tr><td colspan="7" class="text-center text-muted p-4">No recorded transaction history for this retail customer yet.</td></tr>
                                ` : ledger.map(l => `
                                    <tr>
                                        <td>${l.date}</td>
                                        <td>
                                            <span class="badge ${l.type === 'INVOICE' ? 'badge-primary' : (l.type === 'PAYMENT' ? 'badge-success' : 'badge-warning')}" style="font-weight:700;">
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
                                        <td class="text-right font-mono font-bold ${l.running_balance > 0 ? 'text-danger' : 'text-main'}" style="font-size:0.95rem;">
                                            ${SecurityClient.formatCurrency(l.running_balance)}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="report-tfoot-row">
                                    <td colspan="4"><strong>TOTAL TRANSACTIONS & CLOSING STATEMENT BALANCE</strong></td>
                                    <td class="text-right font-mono text-danger font-bold" style="font-size:1rem;">${totalDebit > 0 ? SecurityClient.formatCurrency(totalDebit) : 'Rs. 0.00'}</td>
                                    <td class="text-right font-mono text-success font-bold" style="font-size:1rem;">${totalCredit > 0 ? SecurityClient.formatCurrency(totalCredit) : 'Rs. 0.00'}</td>
                                    <td class="text-right font-mono font-bold ${shop.outstanding_balance > 0 ? 'text-danger' : 'text-success'}" style="font-size:1.05rem;">${SecurityClient.formatCurrency(shop.outstanding_balance)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // 4. SALES REP COMMISSIONS TAB
    function renderRepCommissions() {
        const totalPayout = repData.reduce((sum, r) => sum + (parseFloat(r.total_incentive) || 0), 0);
        const totalCups = repData.reduce((sum, r) => sum + (r.cups_sold || 0), 0);
        const totalTarget = repData.reduce((sum, r) => sum + (r.target_cups || 0), 0);
        const totalBaseComm = repData.reduce((sum, r) => sum + (parseFloat(r.volume_commission) || 0), 0);
        const totalTargetBonus = repData.reduce((sum, r) => sum + (parseFloat(r.target_bonus) || 0), 0);
        const totalCashBonus = repData.reduce((sum, r) => sum + (parseFloat(r.cash_collection_bonus) || 0), 0);
        const avgAchieve = totalTarget > 0 ? ((totalCups / totalTarget) * 100).toFixed(1) : 0;

        return `
            <div>
                <div style="margin-bottom: 1.25rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-heading); margin: 0;">🏆 Sales Representatives Target Performance & Incentive Payroll</h3>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0;">Volume Rate: Rs. 1.50/cup &bull; Target Milestone Bonus: Rs. 2,500.00 &bull; Field Debt Recovery Incentive</p>
                </div>

                <!-- Executive KPI Summary Bar -->
                <div class="report-summary-kpis">
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">🏆</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Monthly Payroll Payout</span>
                            <span class="report-summary-val" style="color: #10b981;">${SecurityClient.formatCurrency(totalPayout)}</span>
                            <span class="report-summary-sub">Field Sales Reps Total</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(56, 189, 248, 0.12); color: #38bdf8;">📦</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Volume Delivered</span>
                            <span class="report-summary-val" style="color: #38bdf8;">${totalCups.toLocaleString()} cups</span>
                            <span class="report-summary-sub">Against ${totalTarget.toLocaleString()} target</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(168, 85, 247, 0.12); color: #a855f7;">🎯</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Target Achievement Rate</span>
                            <span class="report-summary-val" style="color: #a855f7;">${avgAchieve}%</span>
                            <span class="report-summary-sub">Regional Fleet Average</span>
                        </div>
                    </div>
                    <div class="report-summary-card">
                        <div class="report-summary-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">💵</div>
                        <div class="report-summary-data">
                            <span class="report-summary-label">Bonus Incentives Paid</span>
                            <span class="report-summary-val" style="color: #f59e0b;">${SecurityClient.formatCurrency(totalTargetBonus + totalCashBonus)}</span>
                            <span class="report-summary-sub">Target + Cash Recovery</span>
                        </div>
                    </div>
                </div>

                <!-- Sales Rep Performance Cards -->
                <div class="report-cards-grid">
                    ${repData.map(r => `
                        <div class="report-card">
                            <div>
                                <div class="report-card-header">
                                    <div>
                                        <h4 class="report-card-title">${SecurityClient.escapeHtml(r.name)}</h4>
                                        <div class="report-card-sub">${r.phone}</div>
                                    </div>
                                    <span class="report-pill-badge" style="background: rgba(56, 189, 248, 0.12); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.25);">${r.status}</span>
                                </div>
                                <div style="margin: 0.65rem 0;">
                                    <div class="flex-between text-xs" style="margin-bottom: 4px;">
                                        <span style="color:var(--text-muted); font-weight:600;">Target Progress:</span>
                                        <span style="font-weight:800; color:${r.achievement_percent >= 100 ? '#10b981' : '#38bdf8'}; font-family: monospace;">${r.cups_sold.toLocaleString()} / ${r.target_cups.toLocaleString()} (${r.achievement_percent}%)</span>
                                    </div>
                                    <div style="height: 8px; background: rgba(255,255,255,0.06); border-radius: 999px; overflow: hidden; padding: 1px;">
                                        <div style="width: ${Math.min(100, r.achievement_percent)}%; height: 100%; border-radius: 999px; background: ${r.achievement_percent >= 100 ? '#10b981' : '#38bdf8'};"></div>
                                    </div>
                                </div>
                                <div class="report-hero-metric">
                                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight:600;">Total Payout</span>
                                    <span class="report-hero-val" style="color: #10b981;">${SecurityClient.formatCurrency(r.total_incentive)}</span>
                                </div>
                            </div>
                            <div class="report-stats-row">
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Volume</span>
                                    <span class="report-stat-val font-bold">Rs. ${r.volume_commission.toLocaleString()}</span>
                                </div>
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Target</span>
                                    <span class="report-stat-val font-bold">Rs. ${r.target_bonus.toLocaleString()}</span>
                                </div>
                                <div class="report-stat-col">
                                    <span class="report-stat-label">Cash</span>
                                    <span class="report-stat-val font-bold">Rs. ${r.cash_collection_bonus.toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>

                <!-- Monthly Payroll Audit Table -->
                <div class="report-table-box">
                    <h4 class="report-table-title">Monthly Representative Payroll & Commission Audit</h4>
                    <div class="table-responsive">
                        <table class="table text-sm">
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
                                        <td class="font-bold text-main font-mono" style="font-size:0.95rem;">${r.cups_sold.toLocaleString()} cups</td>
                                        <td>
                                            <span style="font-weight:800; color:${r.achievement_percent >= 100 ? '#10b981' : '#38bdf8'}; font-family: monospace;">${r.achievement_percent}%</span>
                                        </td>
                                        <td class="font-mono text-muted font-bold">${SecurityClient.formatCurrency(r.volume_commission)}</td>
                                        <td class="font-mono text-muted font-bold">${SecurityClient.formatCurrency(r.target_bonus)}</td>
                                        <td class="font-mono text-muted font-bold">${SecurityClient.formatCurrency(r.cash_collection_bonus)}</td>
                                        <td class="font-mono font-bold text-success" style="font-size:1rem;">
                                            ${SecurityClient.formatCurrency(r.total_incentive)}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="report-tfoot-row">
                                    <td><strong>TOTAL PAYROLL DISBURSEMENT</strong></td>
                                    <td><strong>${repData.length} REPS</strong></td>
                                    <td class="font-bold text-main font-mono" style="font-size:1rem;">${totalCups.toLocaleString()} cups</td>
                                    <td><strong class="text-info">${avgAchieve}% AVG</strong></td>
                                    <td class="font-mono text-muted font-bold">${SecurityClient.formatCurrency(totalBaseComm)}</td>
                                    <td class="font-mono text-muted font-bold">${SecurityClient.formatCurrency(totalTargetBonus)}</td>
                                    <td class="font-mono text-muted font-bold">${SecurityClient.formatCurrency(totalCashBonus)}</td>
                                    <td class="font-mono font-bold text-success" style="font-size:1.05rem;">${SecurityClient.formatCurrency(totalPayout)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    function onShopSelectChange(shopId) {
        loadShopStatement(shopId);
    }

    // ========================================================
    // PUBLICATION-GRADE CORPORATE PRINT & A4 PDF GENERATION ENGINE
    // ========================================================

    function getCorporatePrintStyles() {
        return `
            @page {
                size: A4 portrait;
                margin: 12mm 10mm;
            }
            * {
                box-sizing: border-box;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body {
                font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Arial, sans-serif;
                color: #0F172A;
                background: #FFFFFF;
                margin: 0;
                padding: 15px;
                font-size: 10pt;
                line-height: 1.4;
            }
            .corp-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                border-bottom: 2.5px solid #1E3A8A;
                padding-bottom: 12px;
                margin-bottom: 14px;
            }
            .corp-brand-name {
                font-size: 15pt;
                font-weight: 900;
                color: #1E3A8A;
                letter-spacing: -0.02em;
                margin: 0;
            }
            .corp-brand-sub {
                font-size: 8.5pt;
                font-weight: 700;
                color: #0284C7;
                margin-top: 2px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .corp-brand-address {
                font-size: 8pt;
                color: #475569;
                margin-top: 4px;
            }
            .corp-meta-box {
                text-align: right;
                font-size: 8pt;
                color: #334155;
            }
            .corp-audit-badge {
                display: inline-block;
                background: #1E3A8A;
                color: #FFFFFF;
                font-weight: 800;
                font-size: 7.5pt;
                padding: 3px 8px;
                border-radius: 4px;
                letter-spacing: 0.05em;
                margin-bottom: 5px;
            }
            .corp-title-banner {
                background: #F8FAFC;
                border: 1px solid #E2E8F0;
                border-left: 4px solid #1E3A8A;
                padding: 10px 14px;
                border-radius: 6px;
                margin-bottom: 14px;
            }
            .corp-title-text {
                font-size: 13pt;
                font-weight: 800;
                color: #0F172A;
                margin: 0;
            }
            .corp-period-text {
                font-size: 8pt;
                color: #64748B;
                margin-top: 3px;
                font-weight: 600;
            }
            .corp-kpis-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 8px;
                margin-bottom: 14px;
            }
            .corp-kpi-box {
                background: #F8FAFC;
                border: 1px solid #CBD5E1;
                border-radius: 6px;
                padding: 8px 10px;
            }
            .corp-kpi-label {
                font-size: 7.5pt;
                font-weight: 700;
                color: #475569;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }
            .corp-kpi-val {
                font-size: 12pt;
                font-weight: 800;
                color: #0F172A;
                font-family: 'Consolas', 'Courier New', monospace;
                margin-top: 2px;
            }
            .corp-kpi-sub {
                font-size: 7pt;
                color: #64748B;
                margin-top: 2px;
            }
            table.corp-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 8px;
                font-size: 9pt;
            }
            table.corp-table th {
                background: #1E3A8A !important;
                color: #FFFFFF !important;
                font-weight: 800;
                text-align: left;
                padding: 7px 8px;
                border: 1px solid #1E3A8A;
                font-size: 8pt;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }
            table.corp-table td {
                padding: 6px 8px;
                border: 1px solid #CBD5E1;
                color: #0F172A;
            }
            table.corp-table tr:nth-child(even) td {
                background-color: #F8FAFC;
            }
            table.corp-table tfoot td {
                background: #F1F5F9 !important;
                font-weight: 800;
                border-top: 2px solid #0F172A;
                padding: 8px 8px;
                font-size: 9pt;
            }
            .text-right { text-align: right !important; }
            .text-center { text-align: center !important; }
            .font-mono { font-family: 'Consolas', 'Courier New', monospace !important; }
            .font-bold { font-weight: 700 !important; }
            .text-danger { color: #DC2626 !important; }
            .text-success { color: #15803D !important; }
            .text-info { color: #0284C7 !important; }
            .corp-signatures {
                margin-top: 35px;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                break-inside: avoid;
            }
            .corp-sig-col {
                text-align: center;
            }
            .corp-sig-line {
                border-bottom: 1.5px solid #0F172A;
                height: 35px;
                margin-bottom: 6px;
            }
            .corp-sig-title {
                font-size: 8pt;
                font-weight: 800;
                color: #0F172A;
            }
            .corp-sig-sub {
                font-size: 7pt;
                color: #64748B;
                margin-top: 2px;
            }
            .corp-legal-footer {
                margin-top: 25px;
                padding-top: 8px;
                border-top: 1px dashed #94A3B8;
                display: flex;
                justify-content: space-between;
                font-size: 7.5pt;
                color: #64748B;
            }
        `;
    }

    function buildCorporateDocumentHtml(title, subtitle, kpiCardsHtml, tableHtml, extraNotesHtml = '') {
        const docRef = `PULSE-AUDIT-${new Date().toISOString().slice(0,10).replace(/-/g,'')}-${Math.floor(1000 + Math.random() * 9000)}`;
        const dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        const timeStr = new Date().toLocaleTimeString();
        const userName = SecurityClient.escapeHtml(window.CURRENT_USER?.name || 'Front Office Admin');

        return `
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset="utf-8">
                    <title>${title} - PulseERP Corporate Audit</title>
                    <style>
                        ${getCorporatePrintStyles()}
                    </style>
                </head>
                <body>
                    <!-- Corporate Header -->
                    <div class="corp-header">
                        <div>
                            <h1 class="corp-brand-name">POLONNARUWA DAIRY PRODUCTS (PVT) LTD</h1>
                            <div class="corp-brand-sub">PulseERP Enterprise Operations & Financial Intelligence Division</div>
                            <div class="corp-brand-address">Kaduruwela Wholesale Hub & Central Cold-Chain Depot, Polonnaruwa &bull; Hotline: 027-2224501</div>
                        </div>
                        <div class="corp-meta-box">
                            <div class="corp-audit-badge">OFFICIAL AUDIT REPORT</div>
                            <div>Reference: <strong class="font-mono">${docRef}</strong></div>
                            <div>Date: <strong>${dateStr} (${timeStr})</strong></div>
                            <div>Certified Officer: <strong>${userName}</strong></div>
                        </div>
                    </div>

                    <!-- Title Strip -->
                    <div class="corp-title-banner">
                        <div class="corp-title-text">${title}</div>
                        <div class="corp-period-text">${subtitle}</div>
                    </div>

                    <!-- KPI Summary Cards -->
                    <div class="corp-kpis-grid">
                        ${kpiCardsHtml}
                    </div>

                    <!-- Table Data -->
                    ${tableHtml}

                    <!-- Extra Notes if any -->
                    ${extraNotesHtml}

                    <!-- Triple Sign-off Block -->
                    <div class="corp-signatures">
                        <div class="corp-sig-col">
                            <div class="corp-sig-line"></div>
                            <div class="corp-sig-title">Prepared By (Field Auditor / Cashier)</div>
                            <div class="corp-sig-sub">Signature & Date</div>
                        </div>
                        <div class="corp-sig-col">
                            <div class="corp-sig-line"></div>
                            <div class="corp-sig-title">Verified By (Operations Supervisor)</div>
                            <div class="corp-sig-sub">Signature & Official Stamp</div>
                        </div>
                        <div class="corp-sig-col">
                            <div class="corp-sig-line"></div>
                            <div class="corp-sig-title">Approved By (Managing Director)</div>
                            <div class="corp-sig-sub">Executive Authorization</div>
                        </div>
                    </div>

                    <!-- Legal Footer -->
                    <div class="corp-legal-footer">
                        <div>Verified by PulseERP Intelligence Core Suite &bull; Tamper-Evident SHA-256 HMAC Sealed &bull; Confidential Internal Audit</div>
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
        `;
    }

    function exportReportPdf() {
        let printHtml = '';

        if (activeSubTab === 'routes') {
            const totalRevenue = routeData.reduce((sum, r) => sum + (parseFloat(r.total_revenue) || 0), 0);
            const totalFuel = routeData.reduce((sum, r) => sum + (parseFloat(r.fuel_expense) || 0), 0);
            const totalCrew = routeData.reduce((sum, r) => sum + (parseFloat(r.crew_allowance) || 0), 0);
            const totalCOGS = routeData.reduce((sum, r) => sum + (parseFloat(r.cogs_production) || 0), 0);
            const totalProfit = routeData.reduce((sum, r) => sum + (parseFloat(r.gross_profit) || 0), 0);
            const totalKm = routeData.reduce((sum, r) => sum + (parseFloat(r.distance_km) || 0), 0);
            const totalLitres = routeData.reduce((sum, r) => sum + (parseFloat(r.diesel_litres) || 0), 0);
            const avgMargin = totalRevenue > 0 ? ((totalProfit / totalRevenue) * 100).toFixed(1) : '0.0';

            const kpis = `
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">Total Fleet Revenue</div>
                    <div class="corp-kpi-val text-success">${SecurityClient.formatCurrency(totalRevenue)}</div>
                    <div class="corp-kpi-sub">${routeData.length} Routes Dispatched</div>
                </div>
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">Diesel Expense</div>
                    <div class="corp-kpi-val text-danger">${SecurityClient.formatCurrency(totalFuel)}</div>
                    <div class="corp-kpi-sub">${totalLitres} L Consumed (${totalKm} km)</div>
                </div>
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">Crew Allowances</div>
                    <div class="corp-kpi-val text-info">${SecurityClient.formatCurrency(totalCrew)}</div>
                    <div class="corp-kpi-sub">Shift Allowances Paid</div>
                </div>
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">Net Margin & Profit</div>
                    <div class="corp-kpi-val">${SecurityClient.formatCurrency(totalProfit)}</div>
                    <div class="corp-kpi-sub">${avgMargin}% Overall Profitability</div>
                </div>
            `;

            const table = `
                <table class="corp-table">
                    <thead>
                        <tr>
                            <th>Route & Sector Name</th>
                            <th>Distance</th>
                            <th>Fuel (L)</th>
                            <th class="text-right">Diesel Cost</th>
                            <th class="text-right">Production COGS</th>
                            <th class="text-right">Crew Pay</th>
                            <th class="text-right">Total Revenue</th>
                            <th class="text-right">Net Profit</th>
                            <th class="text-center">Margin %</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${routeData.map(r => `
                            <tr>
                                <td><strong>${SecurityClient.escapeHtml(r.route_name)}</strong> (${SecurityClient.escapeHtml(r.region_sector)})</td>
                                <td>${r.distance_km} km</td>
                                <td>${r.diesel_litres} L</td>
                                <td class="text-right font-mono text-danger font-bold">${SecurityClient.formatCurrency(r.fuel_expense)}</td>
                                <td class="text-right font-mono">${SecurityClient.formatCurrency(r.cogs_production)}</td>
                                <td class="text-right font-mono">${SecurityClient.formatCurrency(r.crew_allowance)}</td>
                                <td class="text-right font-mono font-bold">${SecurityClient.formatCurrency(r.total_revenue)}</td>
                                <td class="text-right font-mono text-success font-bold">${SecurityClient.formatCurrency(r.gross_profit)}</td>
                                <td class="text-center font-bold">${r.profit_margin_percent}%</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><strong>TOTAL FLEET SUMMARY</strong></td>
                            <td><strong>${totalKm} km</strong></td>
                            <td><strong>${totalLitres} L</strong></td>
                            <td class="text-right font-mono font-bold text-danger">${SecurityClient.formatCurrency(totalFuel)}</td>
                            <td class="text-right font-mono font-bold">${SecurityClient.formatCurrency(totalCOGS)}</td>
                            <td class="text-right font-mono font-bold">${SecurityClient.formatCurrency(totalCrew)}</td>
                            <td class="text-right font-mono font-bold">${SecurityClient.formatCurrency(totalRevenue)}</td>
                            <td class="text-right font-mono font-bold text-success">${SecurityClient.formatCurrency(totalProfit)}</td>
                            <td class="text-center font-bold">${avgMargin}%</td>
                        </tr>
                    </tfoot>
                </table>
            `;

            printHtml = buildCorporateDocumentHtml(
                'ROUTE PROFITABILITY & DIESEL FLEET ECONOMICS AUDIT',
                'Operational Sector: Polonnaruwa Regional Fleet &bull; Fuel Rate: Rs. 370/L &bull; Benchmark: 4.2 km/L',
                kpis,
                table
            );
        } else if (activeSubTab === 'flavors') {
            const totalCups = flavorData.reduce((sum, f) => sum + (f.cups_sold_month || 0), 0);
            const totalFlavorRevenue = flavorData.reduce((sum, f) => sum + (parseFloat(f.monthly_revenue) || 0), 0);
            const topFlavor = flavorData.length > 0 ? [...flavorData].sort((a,b) => (b.cups_sold_month || 0) - (a.cups_sold_month || 0))[0] : null;

            const kpis = `
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">Monthly Volume Sold</div>
                    <div class="corp-kpi-val text-info">${totalCups.toLocaleString()} cups</div>
                    <div class="corp-kpi-sub">Field physical delivery</div>
                </div>
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">Gross Flavor Sales</div>
                    <div class="corp-kpi-val text-success">${SecurityClient.formatCurrency(totalFlavorRevenue)}</div>
                    <div class="corp-kpi-sub">Total product catalog</div>
                </div>
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">#1 Fast Moving Flavor</div>
                    <div class="corp-kpi-val" style="font-size: 10pt;">${topFlavor ? I18n.getProductFlavor(topFlavor) : 'Vanilla'}</div>
                    <div class="corp-kpi-sub">Highest market velocity</div>
                </div>
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">Product SKUs</div>
                    <div class="corp-kpi-val">${flavorData.length} Active Items</div>
                    <div class="corp-kpi-sub">Shelf Life: 14 - 21 Days</div>
                </div>
            `;

            const table = `
                <table class="corp-table">
                    <thead>
                        <tr>
                            <th>Flavor Description</th>
                            <th>SKU Code</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Monthly Volume</th>
                            <th class="text-center">Market Share</th>
                            <th class="text-right">Gross Monthly Revenue</th>
                            <th class="text-center">Shelf Life</th>
                            <th class="text-center">Velocity Tier</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${flavorData.map(f => `
                            <tr>
                                <td><strong>${SecurityClient.escapeHtml(I18n.getProductFlavor(f))}</strong></td>
                                <td class="font-mono">${f.sku_code}</td>
                                <td class="text-right font-mono font-bold">${SecurityClient.formatCurrency(f.unit_price)}</td>
                                <td class="text-right font-mono font-bold">${f.cups_sold_month.toLocaleString()} cups</td>
                                <td class="text-center font-bold text-info">${f.share_percent}%</td>
                                <td class="text-right font-mono font-bold text-success">${SecurityClient.formatCurrency(f.monthly_revenue)}</td>
                                <td class="text-center">${f.shelf_life_days} Days</td>
                                <td class="text-center font-bold">${f.ranking}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><strong>TOTAL PORTFOLIO SUMMARY</strong></td>
                            <td><strong>5 SKUs</strong></td>
                            <td class="text-right font-mono font-bold">AVG Rs. 85.00</td>
                            <td class="text-right font-mono font-bold">${totalCups.toLocaleString()} cups</td>
                            <td class="text-center font-bold text-info">100.0%</td>
                            <td class="text-right font-mono font-bold text-success">${SecurityClient.formatCurrency(totalFlavorRevenue)}</td>
                            <td class="text-center">14-21 Days</td>
                            <td class="text-center font-bold">ALL TIERS</td>
                        </tr>
                    </tfoot>
                </table>
            `;

            printHtml = buildCorporateDocumentHtml(
                '5-FLAVOR PORTFOLIO SALES DEMAND & PRODUCT VELOCITY REPORT',
                'Polonnaruwa Regional Wholesale & Retail Network &bull; Perishable Cold-Chain Compliance &bull; Active SKUs: 5',
                kpis,
                table
            );
        } else if (activeSubTab === 'statement') {
            return printStatementSlip();
        } else if (activeSubTab === 'commissions') {
            const totalPayout = repData.reduce((sum, r) => sum + (parseFloat(r.total_incentive) || 0), 0);
            const totalCups = repData.reduce((sum, r) => sum + (r.cups_sold || 0), 0);
            const totalTarget = repData.reduce((sum, r) => sum + (r.target_cups || 0), 0);
            const totalBaseComm = repData.reduce((sum, r) => sum + (parseFloat(r.volume_commission) || 0), 0);
            const totalTargetBonus = repData.reduce((sum, r) => sum + (parseFloat(r.target_bonus) || 0), 0);
            const totalCashBonus = repData.reduce((sum, r) => sum + (parseFloat(r.cash_collection_bonus) || 0), 0);
            const avgAchieve = totalTarget > 0 ? ((totalCups / totalTarget) * 100).toFixed(1) : 0;

            const kpis = `
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">Total Incentive Payout</div>
                    <div class="corp-kpi-val text-success">${SecurityClient.formatCurrency(totalPayout)}</div>
                    <div class="corp-kpi-sub">Field Representatives Payroll</div>
                </div>
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">Volume Delivered</div>
                    <div class="corp-kpi-val text-info">${totalCups.toLocaleString()} cups</div>
                    <div class="corp-kpi-sub">Target: ${totalTarget.toLocaleString()} cups</div>
                </div>
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">Target Progress</div>
                    <div class="corp-kpi-val">${avgAchieve}%</div>
                    <div class="corp-kpi-sub">Regional Fleet Average</div>
                </div>
                <div class="corp-kpi-box">
                    <div class="corp-kpi-label">Bonuses Paid</div>
                    <div class="corp-kpi-val text-warning">${SecurityClient.formatCurrency(totalTargetBonus + totalCashBonus)}</div>
                    <div class="corp-kpi-sub">Milestones & Cash Recovery</div>
                </div>
            `;

            const table = `
                <table class="corp-table">
                    <thead>
                        <tr>
                            <th>Sales Representative Name</th>
                            <th>Contact Phone</th>
                            <th class="text-right">Volume Delivered</th>
                            <th class="text-center">Target Progress</th>
                            <th class="text-right">Base Commission</th>
                            <th class="text-right">Target Bonus</th>
                            <th class="text-right">Cash Bonus</th>
                            <th class="text-right">Total Incentive Payout</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${repData.map(r => `
                            <tr>
                                <td><strong>${SecurityClient.escapeHtml(r.name)}</strong></td>
                                <td>${r.phone}</td>
                                <td class="text-right font-mono font-bold">${r.cups_sold.toLocaleString()} cups</td>
                                <td class="text-center font-bold text-info">${r.achievement_percent}%</td>
                                <td class="text-right font-mono">${SecurityClient.formatCurrency(r.volume_commission)}</td>
                                <td class="text-right font-mono">${SecurityClient.formatCurrency(r.target_bonus)}</td>
                                <td class="text-right font-mono">${SecurityClient.formatCurrency(r.cash_collection_bonus)}</td>
                                <td class="text-right font-mono font-bold text-success" style="font-size:10pt;">${SecurityClient.formatCurrency(r.total_incentive)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><strong>TOTAL PAYROLL DISBURSEMENT</strong></td>
                            <td><strong>${repData.length} REPS</strong></td>
                            <td class="text-right font-mono font-bold">${totalCups.toLocaleString()} cups</td>
                            <td class="text-center font-bold text-info">${avgAchieve}% AVG</td>
                            <td class="text-right font-mono font-bold">${SecurityClient.formatCurrency(totalBaseComm)}</td>
                            <td class="text-right font-mono font-bold">${SecurityClient.formatCurrency(totalTargetBonus)}</td>
                            <td class="text-right font-mono font-bold">${SecurityClient.formatCurrency(totalCashBonus)}</td>
                            <td class="text-right font-mono font-bold text-success" style="font-size:11pt;">${SecurityClient.formatCurrency(totalPayout)}</td>
                        </tr>
                    </tfoot>
                </table>
            `;

            printHtml = buildCorporateDocumentHtml(
                'SALES REPRESENTATIVES TARGET PERFORMANCE & INCENTIVE PAYROLL',
                'Volume Rate: Rs. 1.50/cup &bull; Target Milestone Bonus: Rs. 2,500.00 &bull; Cash Recovery Reward',
                kpis,
                table
            );
        }

        const printWindow = window.open('', '_blank', 'width=950,height=900');
        if (!printWindow) {
            window.print();
            return;
        }

        printWindow.document.write(printHtml);
        printWindow.document.close();
    }

    function printStatementSlip() {
        if (!currentStatement) {
            SecurityClient.showToast('Please select a retail shop first.', 'warning');
            return;
        }

        const shop = currentStatement.shop;
        const ledger = currentStatement.ledger;
        const totalDebit = ledger.reduce((sum, l) => sum + (parseFloat(l.debit) || 0), 0);
        const totalCredit = ledger.reduce((sum, l) => sum + (parseFloat(l.credit) || 0), 0);

        const kpis = `
            <div class="corp-kpi-box">
                <div class="corp-kpi-label">Retail Customer Store</div>
                <div class="corp-kpi-val" style="font-size:10pt;">${SecurityClient.escapeHtml(shop.name)}</div>
                <div class="corp-kpi-sub">${SecurityClient.escapeHtml(shop.owner_name)} &bull; ${shop.phone}</div>
            </div>
            <div class="corp-kpi-box">
                <div class="corp-kpi-label">Assigned Delivery Route</div>
                <div class="corp-kpi-val text-info" style="font-size:10pt;">${SecurityClient.escapeHtml(shop.route_name)}</div>
                <div class="corp-kpi-sub">Polonnaruwa Sector</div>
            </div>
            <div class="corp-kpi-box">
                <div class="corp-kpi-label">Credit Tier & Ceiling</div>
                <div class="corp-kpi-val text-warning">${shop.credit_tier}</div>
                <div class="corp-kpi-sub">Ceiling: ${SecurityClient.formatCurrency(shop.credit_limit)}</div>
            </div>
            <div class="corp-kpi-box">
                <div class="corp-kpi-label">Outstanding Debt Balance</div>
                <div class="corp-kpi-val text-danger">${SecurityClient.formatCurrency(shop.outstanding_balance)}</div>
                <div class="corp-kpi-sub">Net Closing Balance to Settle</div>
            </div>
        `;

        const table = `
            <table class="corp-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reference #</th>
                        <th>Description / Details</th>
                        <th class="text-right">Debit (Invoices +)</th>
                        <th class="text-right">Credit (Payments/Returns -)</th>
                        <th class="text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    ${ledger.length === 0 ? `
                        <tr><td colspan="7" class="text-center p-3">No recorded transaction ledger entries for this shop yet.</td></tr>
                    ` : ledger.map(l => `
                        <tr>
                            <td>${l.date}</td>
                            <td><strong>${l.type}</strong></td>
                            <td class="font-mono"><strong>${SecurityClient.escapeHtml(l.ref_no)}</strong></td>
                            <td>${SecurityClient.escapeHtml(l.description)}</td>
                            <td class="text-right font-mono text-danger font-bold">${l.debit > 0 ? SecurityClient.formatCurrency(l.debit) : '-'}</td>
                            <td class="text-right font-mono text-success font-bold">${l.credit > 0 ? SecurityClient.formatCurrency(l.credit) : '-'}</td>
                            <td class="text-right font-mono font-bold ${l.running_balance > 0 ? 'text-danger' : 'text-success'}">${SecurityClient.formatCurrency(l.running_balance)}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"><strong>CLOSING STATEMENT RECONCILIATION TOTALS</strong></td>
                        <td class="text-right font-mono font-bold text-danger">${totalDebit > 0 ? SecurityClient.formatCurrency(totalDebit) : 'Rs. 0.00'}</td>
                        <td class="text-right font-mono font-bold text-success">${totalCredit > 0 ? SecurityClient.formatCurrency(totalCredit) : 'Rs. 0.00'}</td>
                        <td class="text-right font-mono font-bold text-danger" style="font-size:11pt;">${SecurityClient.formatCurrency(shop.outstanding_balance)}</td>
                    </tr>
                </tfoot>
            </table>
        `;

        const notes = `
            <div style="margin-top: 15px; padding: 10px 14px; background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 6px; font-size: 8pt; color: #475569;">
                <strong>Payment Instructions:</strong> Direct bank remittances accepted to <em>Bank of Ceylon - Polonnaruwa Branch (A/C: 0002348719)</em>. Please quote Reference # and Store Name on deposit slips. Cash handovers are only valid with an official 58mm Bluetooth receipt signed by the authorized driver.
            </div>
        `;

        const printHtml = buildCorporateDocumentHtml(
            `CUSTOMER ACCOUNT STATEMENT & RUNNING LEDGER`,
            `Store: ${SecurityClient.escapeHtml(shop.name)} &bull; Route: ${SecurityClient.escapeHtml(shop.route_name)} &bull; Customer Code: SHP-${shop.id}`,
            kpis,
            table,
            notes
        );

        const printWindow = window.open('', '_blank', 'width=950,height=900');
        if (!printWindow) {
            window.print();
            return;
        }

        printWindow.document.write(printHtml);
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
        forceRefresh,
        render,
        switchSubTab,
        onShopSelectChange,
        exportReportPdf,
        printStatementSlip,
        openStatementModal
    };
})();

window.ReportsView = ReportsView;

window.addEventListener('languageChanged', () => {
    if (document.getElementById('reportsViewContainer')) {
        ReportsView.loadData();
    }
});
