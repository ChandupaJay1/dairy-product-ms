<!DOCTYPE html>
<html lang="en" class="dark" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $appTitle ?? 'PulseERP — Polonnaruwa Dairy Fleet Operations' }}</title>
    <meta name="description" content="Specialized Dairy Fleet & Field Operations Management System.">
    <meta name="theme-color" content="#09090b">
    
    <!-- PWA Manifest & Favicons -->
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/jpeg" href="/assets/logo.jpg">
    <link rel="apple-touch-icon" href="/assets/logo.jpg">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    
    <!-- Zero-Flash Theme Script -->
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('theme') || localStorage.getItem('polonnaruwa_theme') || 'dark';
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                document.documentElement.setAttribute('data-theme', theme);
            } catch(e) {}
        })();
    </script>

    <!-- Figtree Typography -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Application Stylesheets -->
    <link rel="stylesheet" href="/css/global.css?v=3.3">
    <link rel="stylesheet" href="/css/mobile-views.css?v=3.3">
    <link rel="stylesheet" href="/css/admin-eyecare.css?v=3.9">
</head>
<body>

    <!-- Ambient Subtle Glowing Orbs (PulseERP Signature Background) -->
    <div class="ambient-glow-wrapper" aria-hidden="true">
        <div class="ambient-orb ambient-orb-1"></div>
        <div class="ambient-orb ambient-orb-2"></div>
        <div class="ambient-dots"></div>
    </div>

    <!-- App Main Layout Container -->
    <div class="app-layout-wrapper">

        <!-- ========================================================= -->
        <!-- 1. DESKTOP FIXED SIDEBAR NAVIGATION (PulseERP Standard)    -->
        <!-- ========================================================= -->
        <aside class="desktop-sidebar" aria-label="Desktop Navigation">
            <div class="sidebar-top-group">
                <!-- Strictly Solid Black (#000000) Logo Container -->
                <div class="brand-logo-card">
                    <div class="brand-logo-icon">
                        <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100%; height:100%;">
                            <defs>
                                <linearGradient id="navBrandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#F59E0B" />
                                    <stop offset="50%" stop-color="#EAB308" />
                                    <stop offset="100%" stop-color="#A3E635" />
                                </linearGradient>
                            </defs>
                            <!-- Hexagonal Shield Outline -->
                            <path d="M32 4L56 18V46L32 60L8 46V18L32 4Z" stroke="url(#navBrandGrad)" stroke-width="3.5" stroke-linejoin="round" />
                            <!-- Dynamic Pulse Wave -->
                            <path d="M14 32H23L27 23L31 42L35 18L39 37L43 32H50" stroke="url(#navBrandGrad)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                            <!-- Active Node -->
                            <circle cx="35" cy="18" r="2.5" fill="#A3E635" />
                        </svg>
                    </div>
                    <div class="brand-title-group">
                        <div class="brand-main-text">
                            <span>Pulse</span>
                            <span class="brand-gradient-text">ERP</span>
                        </div>
                        <span class="brand-sub-badge">Dairy Fleet Suite</span>
                    </div>
                </div>

                <!-- Navigation Section -->
                <div class="sidebar-nav-section">
                    <p class="sidebar-section-title" data-i18n="nav_section_main">Field & Fleet Operations</p>
                    <ul class="sidebar-nav-list" role="navigation">
                        @if(Auth::user()->isAdmin())
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn active" data-view="admin" id="sideBtnAdmin">
                                    <span class="sidebar-nav-icon">🖥️</span>
                                    <span class="sidebar-nav-text" data-i18n="nav_admin">Office Admin</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="sales_rep" id="sideBtnRep">
                                    <span class="sidebar-nav-icon">📱</span>
                                    <span class="sidebar-nav-text" data-i18n="nav_rep">Sales Rep</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="driver" id="sideBtnDriver">
                                    <span class="sidebar-nav-icon">🚚</span>
                                    <span class="sidebar-nav-text" data-i18n="nav_driver">Driver Mobile</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="settlement" id="sideBtnSettlement">
                                    <span class="sidebar-nav-icon">🧾</span>
                                    <span class="sidebar-nav-text" data-i18n="nav_settlement">Day Settlement</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="reports" id="sideBtnReports">
                                    <span class="sidebar-nav-icon">📊</span>
                                    <span class="sidebar-nav-text" data-i18n="nav_reports">Reports</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="audit" id="sideBtnAudit">
                                    <span class="sidebar-nav-icon">🔒</span>
                                    <span class="sidebar-nav-text" data-i18n="nav_audit">Activity Logs</span>
                                </button>
                            </li>
                        @elseif(Auth::user()->isSalesRep())
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn active" data-view="sales_rep" id="sideBtnRep">
                                    <span class="sidebar-nav-icon">📱</span>
                                    <span class="sidebar-nav-text" data-i18n="nav_rep">Sales Rep Order Wizard</span>
                                </button>
                            </li>
                        @elseif(Auth::user()->isDriver())
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn active" data-view="driver" id="sideBtnDriver">
                                    <span class="sidebar-nav-icon">🚚</span>
                                    <span class="sidebar-nav-text" data-i18n="nav_driver">Driver Mobile Manifest</span>
                                </button>
                            </li>
                        @elseif(Auth::user()->isCashier())
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn active" data-view="settlement" id="sideBtnSettlement">
                                    <span class="sidebar-nav-icon">🧾</span>
                                    <span class="sidebar-nav-text" data-i18n="nav_settlement">Day-End Settlement Hub</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="reports" id="sideBtnReports">
                                    <span class="sidebar-nav-icon">📊</span>
                                    <span class="sidebar-nav-text" data-i18n="nav_reports">Reports</span>
                                </button>
                            </li>
                        @endif
                    </ul>

                    <!-- Telemetry Health Box -->
                    <div class="sidebar-telemetry-card">
                        <div class="telemetry-row">
                            <span class="telemetry-label">Fleet Telemetry</span>
                            <span class="telemetry-val online">
                                <span class="live-pulse-dot"></span>
                                Live Sync
                            </span>
                        </div>
                        <div class="telemetry-row">
                            <span class="telemetry-label">Security</span>
                            <span class="telemetry-val">HMAC-256</span>
                        </div>
                        <div class="telemetry-row">
                            <span class="telemetry-label">Hub Region</span>
                            <span class="telemetry-val" style="color:var(--accent-amber);">Polonnaruwa</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Footer User Card -->
            <div class="sidebar-footer-card">
                <div class="sidebar-user-pill">
                    <div class="sidebar-user-avatar">
                        <div class="sidebar-user-avatar-inner">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name" title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</div>
                        <div class="sidebar-user-role">
                            @if(Auth::user()->isAdmin())
                                👑 Administrator
                            @elseif(Auth::user()->isSalesRep())
                                📱 Sales Representative
                            @elseif(Auth::user()->isDriver())
                                🚚 Fleet Driver
                            @elseif(Auth::user()->isCashier())
                                🧾 Cashier / Auditor
                            @else
                                {{ Auth::user()->role }}
                            @endif
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="margin-top: 8px;">
                    @csrf
                    <button type="submit" class="btn btn-xs btn-outline btn-block" style="font-size:0.75rem; border-color: rgba(244,63,94,0.3); color: #f43f5e; padding: 6px;">
                        🚪 Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- ========================================================= -->
        <!-- 2. MOBILE DRAWER NAVIGATION                               -->
        <!-- ========================================================= -->
        <div class="mobile-drawer-overlay" id="mobileDrawerOverlay" onclick="App.toggleMobileDrawer(false)"></div>
        <div class="mobile-drawer" id="mobileDrawer">
            <div>
                <div class="mobile-drawer-header">
                    <div class="brand-logo-card" style="padding:0.6rem 0.85rem;">
                        <span class="brand-main-text" style="font-size:1rem;">Pulse<span class="brand-gradient-text">ERP</span></span>
                    </div>
                    <button class="drawer-close-btn" onclick="App.toggleMobileDrawer(false)" aria-label="Close Navigation">&times;</button>
                </div>
                <ul class="sidebar-nav-list">
                    @if(Auth::user()->isAdmin())
                        <li><button class="sidebar-nav-btn role-nav-btn active" data-view="admin"><span class="sidebar-nav-icon">🖥️</span><span data-i18n="nav_admin">Office Admin</span></button></li>
                        <li><button class="sidebar-nav-btn role-nav-btn" data-view="sales_rep"><span class="sidebar-nav-icon">📱</span><span data-i18n="nav_rep">Sales Rep</span></button></li>
                        <li><button class="sidebar-nav-btn role-nav-btn" data-view="driver"><span class="sidebar-nav-icon">🚚</span><span data-i18n="nav_driver">Driver Mobile</span></button></li>
                        <li><button class="sidebar-nav-btn role-nav-btn" data-view="settlement"><span class="sidebar-nav-icon">🧾</span><span data-i18n="nav_settlement">Day Settlement</span></button></li>
                        <li><button class="sidebar-nav-btn role-nav-btn" data-view="reports"><span class="sidebar-nav-icon">📊</span><span data-i18n="nav_reports">Reports</span></button></li>
                        <li><button class="sidebar-nav-btn role-nav-btn" data-view="audit"><span class="sidebar-nav-icon">🔒</span><span data-i18n="nav_audit">Activity Logs</span></button></li>
                    @elseif(Auth::user()->isSalesRep())
                        <li><button class="sidebar-nav-btn role-nav-btn active" data-view="sales_rep"><span class="sidebar-nav-icon">📱</span><span data-i18n="nav_rep">Sales Rep</span></button></li>
                    @elseif(Auth::user()->isDriver())
                        <li><button class="sidebar-nav-btn role-nav-btn active" data-view="driver"><span class="sidebar-nav-icon">🚚</span><span data-i18n="nav_driver">Driver Mobile</span></button></li>
                    @elseif(Auth::user()->isCashier())
                        <li><button class="sidebar-nav-btn role-nav-btn active" data-view="settlement"><span class="sidebar-nav-icon">🧾</span><span data-i18n="nav_settlement">Day Settlement</span></button></li>
                        <li><button class="sidebar-nav-btn role-nav-btn" data-view="reports"><span class="sidebar-nav-icon">📊</span><span data-i18n="nav_reports">Reports</span></button></li>
                    @endif
                </ul>
            </div>
            <div class="sidebar-footer-card">
                <div style="font-size:0.8rem; font-weight:bold; color:var(--text-primary); margin-bottom:4px;">{{ Auth::user()->name }}</div>
                <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:8px;">{{ Auth::user()->getRoleDisplayName() }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-xs btn-outline btn-block" style="border-color: rgba(244,63,94,0.3); color: #f43f5e; padding: 6px;">
                        🚪 Sign Out
                    </button>
                </form>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- 3. MAIN VIEWPORT & WORKSPACE                              -->
        <!-- ========================================================= -->
        <div class="main-viewport">

            <!-- Top Frosted Glass Header -->
            <header class="top-frosted-header">
                <div class="header-left-group">
                    <!-- Hamburger Button for Mobile -->
                    <button class="mobile-hamburger-btn" onclick="App.toggleMobileDrawer(true)" aria-label="Open Menu">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>

                    <!-- Breadcrumbs -->
                    <div class="breadcrumb-trail">
                        <span class="breadcrumb-root">PulseERP</span>
                        <span class="breadcrumb-sep">/</span>
                        <span class="breadcrumb-root" style="display:none; @media(min-width:640px){display:inline;}">Polonnaruwa Fleet</span>
                        <span class="breadcrumb-sep" style="display:none; @media(min-width:640px){display:inline;}">/</span>
                        <span class="breadcrumb-current" id="currentViewBreadcrumb">Operations Hub</span>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="header-actions-group">
                    <!-- User Role Pill -->
                    <div class="status-pill online" style="display:none; @media(min-width:640px){display:inline-flex;}">
                        @if(Auth::user()->isAdmin())
                            👑 <span>Admin</span>
                        @elseif(Auth::user()->isSalesRep())
                            📱 <span>Sales Rep</span>
                        @elseif(Auth::user()->isDriver())
                            🚚 <span>Driver</span>
                        @elseif(Auth::user()->isCashier())
                            🧾 <span>Cashier</span>
                        @else
                            <span>{{ Auth::user()->name }}</span>
                        @endif
                    </div>

                    <!-- Online/Offline Sync Status -->
                    <span class="status-pill online" id="networkStatusBadge">
                        📶 Online
                    </span>
                    <span class="status-pill queue-pill" id="offlineSyncBadge" onclick="OfflineStorage.syncQueue()" title="Pending orders waiting for cellular sync">
                        0 Queued
                    </span>

                    <!-- Trilingual Language Selector -->
                    <div class="lang-select-box" title="භාෂාව තෝරන්න / Change Language">
                        <span class="lang-icon-prefix">🌐</span>
                        <select id="langSelectDropdown" class="lang-dropdown" onchange="I18n.setLanguage(this.value)" aria-label="Select Language">
                            <option value="si" selected>සිංහල</option>
                            <option value="en">English</option>
                            <option value="ta">தமிழ்</option>
                        </select>
                    </div>

                    <!-- Dark / Light Theme Toggle Button -->
                    <button type="button" class="theme-toggle-btn" id="btnThemeToggle" onclick="ThemeManager.toggleTheme()" title="Toggle Light/Dark Theme" aria-label="Toggle Theme">
                        <span id="themeIcon">☀️</span>
                    </button>
                </div>
            </header>

            <!-- Main Dynamic Workspace Views -->
            <main class="main-content-area" id="mainWorkspace">

                @if(Auth::user()->isAdmin() || Auth::user()->isSalesRep())
                <!-- VIEW 1: SALES REPRESENTATIVE GUIDED WIZARD -->
                <section class="view-panel" id="view-sales_rep" aria-label="Sales Representative Guided Wizard">
                    <div id="salesRepViewContainer">
                        <!-- Dynamically rendered by salesRepWizard.js -->
                    </div>
                </section>
                @endif

                @if(Auth::user()->isAdmin() || Auth::user()->isDriver())
                <!-- VIEW 2: DRIVER DELIVERY STOPS & SOS -->
                <section class="view-panel" id="view-driver" aria-label="Driver Navigation and SOS Panel">
                    <div id="driverViewContainer">
                        <!-- Dynamically rendered by driverView.js -->
                    </div>
                </section>
                @endif

                @if(Auth::user()->isAdmin() || Auth::user()->isCashier())
                <!-- VIEW 3: EVENING SETTLEMENT & RECONCILIATION -->
                <section class="view-panel" id="view-settlement" aria-label="Evening Day-End Settlement">
                    <div id="settlementViewContainer">
                        <!-- Dynamically rendered by settlementView.js -->
                    </div>
                </section>
                @endif

                @if(Auth::user()->isAdmin() || Auth::user()->isCashier())
                <!-- VIEW 4: FINANCIAL ANALYTICS & REPORTS -->
                <section class="view-panel" id="view-reports" aria-label="Financial Reports and Analytics">
                    <div id="reportsViewContainer">
                        <!-- Dynamically rendered by reportsView.js -->
                    </div>
                </section>
                @endif

                @if(Auth::user()->isAdmin())
                <!-- VIEW 5: FRONT OFFICE DESKTOP ADMIN UI -->
                <section class="view-panel" id="view-admin" aria-label="Front Office Admin Dashboard">
                    <div id="adminDashboardContainer">
                        <!-- Dynamically rendered by adminDashboard.js -->
                    </div>
                </section>

                <!-- VIEW 6: SYSTEM AUDIT & SECURITY LOGS -->
                <section class="view-panel" id="view-audit" aria-label="Security Audit Ledger">
                    <div class="card">
                        <div class="card-sheen-top"></div>
                        <div class="card-header">
                            <div>
                                <h2 class="card-title" data-i18n="audit_title">🔒 System Activity & Security Logs</h2>
                                <p class="card-subtitle" data-i18n="audit_sub">Cryptographically Verified Transaction Records & Operational Logs</p>
                            </div>
                            <button class="btn btn-sm btn-outline" onclick="App.switchView('admin')" data-i18n="btn_back_admin">
                                ← Back to Admin
                            </button>
                        </div>

                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1rem; margin-top:1rem;">
                            <div class="card" style="padding:1rem; background:var(--bg-surface-elevated);">
                                <h4 style="color:var(--color-cash); font-size:0.9rem;" data-i18n="audit_f1_title">1. Digital Signature & Verification</h4>
                                <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.35rem;" data-i18n="audit_f1_desc">
                                    Credit orders verified via touch digital signature and SMS verification code.
                                </p>
                            </div>
                            <div class="card" style="padding:1rem; background:var(--bg-surface-elevated);">
                                <h4 style="color:var(--color-info); font-size:0.9rem;" data-i18n="audit_f2_title">2. Smart Credit Tiering</h4>
                                <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.35rem;" data-i18n="audit_f2_desc">
                                    Automated shop credit limits and payment tracking for retail shops.
                                </p>
                            </div>
                            <div class="card" style="padding:1rem; background:var(--bg-surface-elevated);">
                                <h4 style="color:var(--color-sos); font-size:0.9rem;" data-i18n="audit_f3_title">3. Stock Rescue Protocol</h4>
                                <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.35rem;" data-i18n="audit_f3_desc">
                                    Verified physical count and inventory transfer during emergency breakdowns.
                                </p>
                            </div>
                        </div>

                        <div style="margin-top:1.5rem;">
                            <h3 style="font-size:1rem; margin-bottom:0.75rem;" data-i18n="audit_recent_title">Recent System Activity Logs</h3>
                            <div id="auditCenterLogsContainer"></div>
                        </div>
                    </div>
                </section>
                @endif

            </main>

            <!-- Minimalist Dashboard Footer -->
            <footer style="padding:1.25rem 2rem; border-top:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; font-size:0.75rem; color:var(--text-muted); font-family:monospace;">
                <span>&copy; {{ date('Y') }} PulseERP Intelligence Suite &bull; Polonnaruwa Dairy</span>
                <span>Version 3.0 &bull; All Systems Nominal</span>
            </footer>

        </div>
    </div>

    <!-- Injected Modals Partial View -->
    @include('partials.modals')

    <!-- Toast Notifications Container -->
    <div id="toastContainer" aria-live="polite"></div>

    <!-- Inject Authenticated User Context for Client-Side Role Enforcement -->
    <script>
        window.CURRENT_USER = <?php echo json_encode([
            'id' => Auth::user()->id,
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'role' => Auth::user()->role
        ]); ?>;
    </script>

    <!-- Application Client Scripts -->
    <script defer src="/js/i18n.js?v=3.8"></script>
    <script defer src="/js/theme.js?v=3.0"></script>
    <script defer src="/js/securityClient.js?v=3.0"></script>
    <script defer src="/js/offlineStorage.js?v=3.0"></script>
    <script defer src="/js/signaturePad.js?v=3.4"></script>
    <script defer src="/js/driverView.js?v=3.5"></script>
    <script defer src="/js/salesRepWizard.js?v=3.5"></script>
    <script defer src="/js/settlementView.js?v=3.5"></script>
    <script defer src="/js/reportsView.js?v=3.6"></script>
    <script defer src="/js/adminDashboard.js?v=3.9"></script>
    <script defer src="/js/app.js?v=3.0"></script>
</body>
</html>
