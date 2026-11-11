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

    <!-- High-Legibility Premium Enterprise Typography (Plus Jakarta Sans, Inter & JetBrains Mono) -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:300,400,500,600,700,800|jetbrains-mono:400,500,600,700,800|inter:400,500,600,700|figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

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
                    <p class="sidebar-section-title" data-i18n="nav_modules_title">Enterprise Modules</p>

                    <!-- 1. Main Office Admin Module Parent Group (Admin Only) -->
                    @if(Auth::user()->isAdmin())
                    <div class="sidebar-module-group active" id="moduleGroupAdmin">
                        <button type="button" class="sidebar-module-btn" id="btnAdminModule" onclick="App.toggleAdminModule()" title="Toggle Office Admin Operations">
                            <div class="module-btn-left">
                                <span class="module-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M8 10h.01"></path><path d="M16 10h.01"></path><path d="M8 14h.01"></path><path d="M16 14h.01"></path></svg></span>
                                <div class="module-title-box">
                                    <span class="module-title" data-i18n="nav_admin_suite">Office Admin</span>
                                    <span class="module-sub-tag" data-i18n="nav_admin_suite_sub">Central Command & Ops</span>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span class="badge badge-danger" id="adminNavSosBadge" style="display:none; font-size:0.65rem; padding:2px 6px; border-radius:10px; background:#ef4444; color:#fff; font-weight:800;">1</span>
                                <span class="module-chevron" id="adminChevron">▾</span>
                            </div>
                        </button>

                        <ul class="sidebar-subnav-list" id="adminSubnav" role="navigation">
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn active" data-view="admin" data-admin-section="adminTopSection" id="sideBtnAdminOverview">
                                    <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18"></path><path d="M9 21V9"></path></svg></span>
                                    <span class="sidebar-nav-text" data-i18n="nav_admin_kpis">Command & KPIs</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="approvalCenterSection" id="sideBtnApprovals">
                                    <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="m9 14 2 2 4-4"></path></svg></span>
                                    <span class="sidebar-nav-text" data-i18n="nav_admin_approvals">Approvals Center</span>
                                    <span class="badge badge-warning ml-auto" id="sideNavApprovalBadge" style="display:none; margin-left:auto; font-size:0.65rem; padding:2px 6px; border-radius:10px;">0</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="dailyDispatchesSection" id="sideBtnDispatches">
                                    <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg></span>
                                    <span class="sidebar-nav-text" data-i18n="nav_admin_dispatches">Daily Dispatches</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="fleetRadarSection" id="sideBtnFleetRadar">
                                    <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36z"></path></svg></span>
                                    <span class="sidebar-nav-text" data-i18n="nav_admin_radar">Fleet GPS Radar</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="fleetStaffManagementSection" id="sideBtnFleetStaff">
                                    <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path><path d="M15 18H9"></path><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"></path><circle cx="17" cy="18" r="2"></circle><circle cx="7" cy="18" r="2"></circle></svg></span>
                                    <span class="sidebar-nav-text" data-i18n="nav_admin_fleet">Fleet & Staff Control</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="creditRiskSection" id="sideBtnCreditRisk">
                                    <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line></svg></span>
                                    <span class="sidebar-nav-text" data-i18n="nav_admin_credit">Credit & Shop Risk</span>
                                </button>
                            </li>
                            <li>
                                <button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="auditLogsSection" id="sideBtnAudit">
                                    <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 11v4"></path><circle cx="12" cy="9" r="1"></circle></svg></span>
                                    <span class="sidebar-nav-text" data-i18n="nav_audit">Activity Logs</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    @endif

                    <!-- 2. Main Distribution Module Parent Group -->
                    <div class="sidebar-module-group active" id="moduleGroupDistribution">
                        <button type="button" class="sidebar-module-btn" id="btnDistributionModule" onclick="App.toggleDistributionModule()" title="Toggle Distribution Operations Suite">
                            <div class="module-btn-left">
                                <span class="module-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span>
                                <div class="module-title-box">
                                    <span class="module-title" data-i18n="nav_distribution">Distribution</span>
                                    <span class="module-sub-tag" data-i18n="nav_distribution_sub">Field Fleet & Sales</span>
                                </div>
                            </div>
                            <span class="module-chevron" id="distChevron">▾</span>
                        </button>

                        <ul class="sidebar-subnav-list" id="distributionSubnav" role="navigation">
                            @if(Auth::user()->isAdmin())
                                <li>
                                    <button class="sidebar-nav-btn role-nav-btn" data-view="sales_rep" id="sideBtnRep">
                                        <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"></rect><path d="M12 18h.01"></path></svg></span>
                                        <span class="sidebar-nav-text" data-i18n="nav_rep">Sales Rep Order Wizard</span>
                                    </button>
                                </li>
                                <li>
                                    <button class="sidebar-nav-btn role-nav-btn" data-view="driver" id="sideBtnDriver">
                                        <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span>
                                        <span class="sidebar-nav-text" data-i18n="nav_driver">Driver Mobile Manifest</span>
                                    </button>
                                </li>
                                <li>
                                    <button class="sidebar-nav-btn role-nav-btn" data-view="settlement" id="sideBtnSettlement">
                                        <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path><path d="M16 8h-8"></path><path d="M16 12h-8"></path><path d="M12 16h-4"></path></svg></span>
                                        <span class="sidebar-nav-text" data-i18n="nav_settlement">Day Settlement Hub</span>
                                    </button>
                                </li>
                                <li>
                                    <button class="sidebar-nav-btn role-nav-btn" data-view="reports" id="sideBtnReports">
                                        <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="20" y2="10"></line><line x1="18" x2="18" y1="20" y2="4"></line><line x1="6" x2="6" y1="20" y2="16"></line></svg></span>
                                        <span class="sidebar-nav-text" data-i18n="nav_reports">Distribution Reports</span>
                                    </button>
                                </li>
                            @elseif(Auth::user()->isSalesRep())
                                <li>
                                    <button class="sidebar-nav-btn role-nav-btn active" data-view="sales_rep" id="sideBtnRep">
                                        <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"></rect><path d="M12 18h.01"></path></svg></span>
                                        <span class="sidebar-nav-text" data-i18n="nav_rep">Sales Rep Order Wizard</span>
                                    </button>
                                </li>
                            @elseif(Auth::user()->isDriver())
                                <li>
                                    <button class="sidebar-nav-btn role-nav-btn active" data-view="driver" id="sideBtnDriver">
                                        <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span>
                                        <span class="sidebar-nav-text" data-i18n="nav_driver">Driver Mobile Manifest</span>
                                    </button>
                                </li>
                            @elseif(Auth::user()->isCashier())
                                <li>
                                    <button class="sidebar-nav-btn role-nav-btn active" data-view="settlement" id="sideBtnSettlement">
                                        <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path><path d="M16 8h-8"></path><path d="M16 12h-8"></path><path d="M12 16h-4"></path></svg></span>
                                        <span class="sidebar-nav-text" data-i18n="nav_settlement">Day-End Settlement Hub</span>
                                    </button>
                                </li>
                                <li>
                                    <button class="sidebar-nav-btn role-nav-btn" data-view="reports" id="sideBtnReports">
                                        <span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="20" y2="10"></line><line x1="18" x2="18" y1="20" y2="4"></line><line x1="6" x2="6" y1="20" y2="16"></line></svg></span>
                                        <span class="sidebar-nav-text" data-i18n="nav_reports">Reports</span>
                                    </button>
                                </li>
                            @endif
                        </ul>
                    </div>

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
                        <div class="sidebar-user-role" style="display:inline-flex; align-items:center; gap:4px;">
                            @if(Auth::user()->isAdmin())
                                <svg class="svg-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"></path></svg> Administrator
                            @elseif(Auth::user()->isSalesRep())
                                <svg class="svg-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"></rect><path d="M12 18h.01"></path></svg> Sales Representative
                            @elseif(Auth::user()->isDriver())
                                <svg class="svg-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg> Fleet Driver
                            @elseif(Auth::user()->isCashier())
                                <svg class="svg-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path><path d="M16 8h-8"></path><path d="M16 12h-8"></path><path d="M12 16h-4"></path></svg> Cashier / Auditor
                            @else
                                {{ Auth::user()->role }}
                            @endif
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="margin-top: 8px;">
                    @csrf
                    <button type="submit" class="btn btn-xs btn-outline btn-block" style="font-size:0.75rem; border-color: rgba(244,63,94,0.3); color: #f43f5e; padding: 6px; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                        <svg class="svg-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" x2="9" y1="12" y2="12"></line></svg> Sign Out
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
                
                <div class="sidebar-nav-section" style="margin-top:0.75rem; padding: 0 0.5rem;">
                    <p class="sidebar-section-title" data-i18n="nav_modules_title">Enterprise Modules</p>

                    <!-- 1. Office Admin Module Group for Mobile Drawer (Admin Only) -->
                    @if(Auth::user()->isAdmin())
                    <div class="sidebar-module-group active" id="drawerModuleGroupAdmin">
                        <button type="button" class="sidebar-module-btn" id="btnDrawerAdminModule" onclick="App.toggleAdminDrawerModule()" title="Toggle Office Admin Suite">
                            <div class="module-btn-left">
                                <span class="module-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M8 10h.01"></path><path d="M16 10h.01"></path><path d="M8 14h.01"></path><path d="M16 14h.01"></path></svg></span>
                                <div class="module-title-box">
                                    <span class="module-title" data-i18n="nav_admin_suite">Office Admin</span>
                                    <span class="module-sub-tag" data-i18n="nav_admin_suite_sub">Central Command & Ops</span>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span class="badge badge-danger" id="drawerNavSosBadge" style="display:none; font-size:0.65rem; padding:2px 6px; border-radius:10px; background:#ef4444; color:#fff; font-weight:800;">1</span>
                                <span class="module-chevron" id="drawerAdminChevron">▾</span>
                            </div>
                        </button>

                        <ul class="sidebar-subnav-list" id="adminDrawerSubnav" role="navigation">
                            <li><button class="sidebar-nav-btn role-nav-btn active" data-view="admin" data-admin-section="adminTopSection"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18"></path><path d="M9 21V9"></path></svg></span><span data-i18n="nav_admin_kpis">Command & KPIs</span></button></li>
                            <li><button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="approvalCenterSection"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="m9 14 2 2 4-4"></path></svg></span><span data-i18n="nav_admin_approvals">Approvals Center</span><span class="badge badge-warning ml-auto" id="drawerNavApprovalBadge" style="display:none; margin-left:auto; font-size:0.65rem; padding:2px 6px; border-radius:10px;">0</span></button></li>
                            <li><button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="dailyDispatchesSection"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg></span><span data-i18n="nav_admin_dispatches">Daily Dispatches</span></button></li>
                            <li><button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="fleetRadarSection"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36z"></path></svg></span><span data-i18n="nav_admin_radar">Fleet GPS Radar</span></button></li>
                            <li><button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="fleetStaffManagementSection"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path><path d="M15 18H9"></path><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"></path><circle cx="17" cy="18" r="2"></circle><circle cx="7" cy="18" r="2"></circle></svg></span><span data-i18n="nav_admin_fleet">Fleet & Staff Control</span></button></li>
                            <li><button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="creditRiskSection"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line></svg></span><span data-i18n="nav_admin_credit">Credit & Shop Risk</span></button></li>
                            <li><button class="sidebar-nav-btn role-nav-btn" data-view="admin" data-admin-section="auditLogsSection"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 11v4"></path><circle cx="12" cy="9" r="1"></circle></svg></span><span data-i18n="nav_audit">Activity Logs</span></button></li>
                        </ul>
                    </div>
                    @endif

                    <!-- 2. Main Distribution Module Group for Mobile Drawer -->
                    <div class="sidebar-module-group active" id="drawerModuleGroupDistribution">
                        <button type="button" class="sidebar-module-btn" id="btnDrawerDistributionModule" onclick="App.toggleDistributionDrawerModule()" title="Toggle Distribution Suite">
                            <div class="module-btn-left">
                                <span class="module-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span>
                                <div class="module-title-box">
                                    <span class="module-title" data-i18n="nav_distribution">Distribution</span>
                                    <span class="module-sub-tag" data-i18n="nav_distribution_sub">Field Fleet & Sales</span>
                                </div>
                            </div>
                            <span class="module-chevron" id="distDrawerChevron">▾</span>
                        </button>

                        <ul class="sidebar-subnav-list" id="distributionDrawerSubnav" role="navigation">
                            @if(Auth::user()->isAdmin())
                                <li><button class="sidebar-nav-btn role-nav-btn" data-view="sales_rep"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"></rect><path d="M12 18h.01"></path></svg></span><span data-i18n="nav_rep">Sales Rep Order Wizard</span></button></li>
                                <li><button class="sidebar-nav-btn role-nav-btn" data-view="driver"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span><span data-i18n="nav_driver">Driver Mobile Manifest</span></button></li>
                                <li><button class="sidebar-nav-btn role-nav-btn" data-view="settlement"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path><path d="M16 8h-8"></path><path d="M16 12h-8"></path><path d="M12 16h-4"></path></svg></span><span data-i18n="nav_settlement">Day Settlement Hub</span></button></li>
                                <li><button class="sidebar-nav-btn role-nav-btn" data-view="reports"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg></span><span data-i18n="nav_reports">Distribution Reports</span></button></li>
                            @elseif(Auth::user()->isSalesRep())
                                <li><button class="sidebar-nav-btn role-nav-btn active" data-view="sales_rep"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"></rect><path d="M12 18h.01"></path></svg></span><span data-i18n="nav_rep">Sales Rep Order Wizard</span></button></li>
                            @elseif(Auth::user()->isDriver())
                                <li><button class="sidebar-nav-btn role-nav-btn active" data-view="driver"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span><span data-i18n="nav_driver">Driver Mobile Manifest</span></button></li>
                            @elseif(Auth::user()->isCashier())
                                <li><button class="sidebar-nav-btn role-nav-btn active" data-view="settlement"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path><path d="M16 8h-8"></path><path d="M16 12h-8"></path><path d="M12 16h-4"></path></svg></span><span data-i18n="nav_settlement">Day-End Settlement Hub</span></button></li>
                                <li><button class="sidebar-nav-btn role-nav-btn" data-view="reports"><span class="sidebar-nav-icon"><svg class="svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg></span><span data-i18n="nav_reports">Reports</span></button></li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
            <div class="sidebar-footer-card">
                <div style="font-size:0.8rem; font-weight:bold; color:var(--text-primary); margin-bottom:4px;">{{ Auth::user()->name }}</div>
                <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:8px;">{{ Auth::user()->getRoleDisplayName() }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-xs btn-outline btn-block" style="border-color: rgba(244,63,94,0.3); color: #f43f5e; padding: 6px; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                        <svg class="svg-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" x2="9" y1="12" y2="12"></line></svg> Sign Out
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
                        <span class="breadcrumb-root" id="currentModuleBreadcrumb" style="color:var(--accent-lime); font-weight:700; display:inline-flex; align-items:center; gap:5px;">
                            <svg class="svg-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path></svg>
                            <span>Office Admin</span>
                        </span>
                        <span class="breadcrumb-sep">/</span>
                        <span class="breadcrumb-current" id="currentViewBreadcrumb">Command & KPIs</span>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="header-actions-group">
                    <!-- Global SOS Emergency Pulsing Pill (Auto-displays when an SOS is active) -->
                    <button id="globalSosHeaderBadge" type="button" class="btn btn-xs btn-sos animate-pulse" style="display:none; align-items:center; gap:6px; cursor:pointer; font-weight:800; border-radius:20px; padding:6px 14px; background:linear-gradient(135deg, #ef4444, #dc2626); color:#fff; border:1px solid #fca5a5; box-shadow:0 0 14px rgba(239, 68, 68, 0.6); font-size:0.8rem;" onclick="App.switchView('admin'); setTimeout(() => AdminDashboard.scrollToSection('adminSosSection'), 250);" title="View Critical SOS Breakdown Incident">
                        <svg class="svg-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <span id="globalSosCountText">1 Active SOS</span>
                    </button>

                    <!-- User Role Pill -->
                    <div class="status-pill online" style="display:none; @media(min-width:640px){display:inline-flex; align-items:center; gap:5px;}">
                        @if(Auth::user()->isAdmin())
                            <svg class="svg-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"></path></svg> <span>Admin</span>
                        @elseif(Auth::user()->isSalesRep())
                            <svg class="svg-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"></rect><path d="M12 18h.01"></path></svg> <span>Sales Rep</span>
                        @elseif(Auth::user()->isDriver())
                            <svg class="svg-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg> <span>Driver</span>
                        @elseif(Auth::user()->isCashier())
                            <svg class="svg-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path><path d="M16 8h-8"></path><path d="M16 12h-8"></path><path d="M12 16h-4"></path></svg> <span>Cashier</span>
                        @else
                            <span>{{ Auth::user()->name }}</span>
                        @endif
                    </div>

                    <!-- Online/Offline Sync Status -->
                    <span class="status-pill online" id="networkStatusBadge" style="display:inline-flex; align-items:center; gap:5px;">
                        <svg class="svg-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"></path><path d="M1.42 9a16 16 0 0 1 21.16 0"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line></svg>
                        <span>Online</span>
                    </span>
                    <span class="status-pill queue-pill" id="offlineSyncBadge" onclick="OfflineStorage.syncQueue()" title="Pending orders waiting for cellular sync">
                        0 Queued
                    </span>

                    <!-- Trilingual Language Selector -->
                    <div class="lang-select-box" title="භාෂාව තෝරන්න / Change Language">
                        <span class="lang-icon-prefix" style="display:inline-flex; align-items:center;">
                            <svg class="svg-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" x2="22" y1="12" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        </span>
                        <select id="langSelectDropdown" class="lang-dropdown" onchange="I18n.setLanguage(this.value)" aria-label="Select Language">
                            <option value="si" selected>සිංහල</option>
                            <option value="en">English</option>
                            <option value="ta">தமிழ்</option>
                        </select>
                    </div>

                    <!-- Dark / Light Theme Toggle Button -->
                    <button type="button" class="theme-toggle-btn" id="btnThemeToggle" onclick="ThemeManager.toggleTheme()" title="Toggle Light/Dark Theme" aria-label="Toggle Theme">
                        <span id="themeIcon" style="display:inline-flex; align-items:center;">
                            <svg class="svg-icon" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                        </span>
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
    <script defer src="/js/appIcons.js?v=1.0"></script>
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
