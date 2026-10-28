<!DOCTYPE html>
<html lang="bn" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>এন্টারপ্রাইজ অ্যাডমিন ড্যাশবোর্ড — Bondhoo (যোগাযোগ)</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="admin-token" content="{{ $adminToken ?? session('admin_api_token') ?? '' }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --surface-elevated: #ffffff;
            --surface-subtle: #f1f5f9;
            --bg-sidebar: #0f172a;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --sidebar-hover: #1e293b;
            --sidebar-active-bg: #4f46e5;
            --border-color: #e2e8f0;
            --border-strong: #cbd5e1;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --accent-primary: #4f46e5;
            --accent-primary-hover: #4338ca;
            --accent-secondary: #3b82f6;
            --accent-success: #10b981;
            --accent-warning: #f59e0b;
            --accent-danger: #ef4444;
            --accent-info: #0ea5e9;
            --focus-ring: 0 0 0 3px rgba(79, 70, 229, 0.32);
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 220ms cubic-bezier(0.4, 0, 0.2, 1);
            --font-xs: 11px;
            --font-sm: 12.5px;
            --font-base: 13.5px;
            --font-md: 15px;
            --font-lg: 18px;
            --font-xl: 22px;
            --font-2xl: 26px;
            --space-1: 4px;
            --space-2: 8px;
            --space-3: 12px;
            --space-4: 16px;
            --space-5: 20px;
            --space-6: 24px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.07);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.08), 0 4px 6px -4px rgb(0 0 0 / 0.08);
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
        }

        html.dark {
            --bg-body: #0b0f19;
            --bg-card: #131b2e;
            --surface-elevated: #19233a;
            --surface-subtle: #0f1626;
            --bg-sidebar: #090d16;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --sidebar-hover: #1e293b;
            --sidebar-active-bg: #4f46e5;
            --border-color: #1e293b;
            --border-strong: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --focus-ring: 0 0 0 3px rgba(129, 140, 248, 0.4);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.4);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.4);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Hind Siliguri', 'Inter', sans-serif; }
        body { background: var(--bg-body); color: var(--text-primary); min-height: 100vh; display: flex; overflow-x: hidden; line-height: 1.5; }
        :focus-visible { outline: none; box-shadow: var(--focus-ring); }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }

        /* Sidebar Styling */
        #sidebar {
            width: 270px;
            background: var(--bg-sidebar);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            transition: width var(--transition-base), transform var(--transition-base);
            z-index: 60;
            position: sticky;
            top: 0;
            height: 100vh;
            border-right: 1px solid rgba(255, 255, 255, 0.06);
        }
        #sidebar.collapsed { width: 80px; }
        #sidebar.collapsed .nav-label,
        #sidebar.collapsed .sidebar-header-title,
        #sidebar.collapsed .sidebar-header-sub,
        #sidebar.collapsed .sidebar-category-title,
        #sidebar.collapsed .badge-pill { display: none; }
        #sidebar.collapsed .nav-item { justify-content: center; padding: 12px 0; }
        #sidebar.collapsed .nav-icon { margin-right: 0; font-size: 20px; }

        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        }
        .sidebar-logo-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 22px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }
        .sidebar-header-title {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
        }
        .sidebar-header-sub {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-scrollable {
            flex: 1;
            overflow-y: auto;
            padding: 16px 12px;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.1) transparent;
        }
        .sidebar-category-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            padding: 12px 10px 6px 10px;
            letter-spacing: 0.8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: var(--radius-md);
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 3px;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
        }
        .nav-item:hover { background: var(--sidebar-hover); color: var(--sidebar-text-active); }
        .nav-item.active { background: var(--sidebar-active-bg); color: #ffffff; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); }
        .nav-icon { font-size: 18px; margin-right: 12px; display: inline-flex; width: 22px; justify-content: center; }
        .badge-pill {
            margin-left: auto;
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 12px;
        }

        /* Main Content Wrapper */
        #main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 100vh;
        }

        /* Top Header */
        header.top-header {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            height: 64px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
            box-shadow: var(--shadow-sm);
        }
        .header-left { display: flex; align-items: center; gap: 16px; flex: 1; max-width: 600px; }
        .btn-icon {
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.2s;
        }
        .btn-icon:hover { background: var(--bg-body); color: var(--text-primary); }

        .global-search-box {
            position: relative;
            width: 100%;
            max-width: 450px;
        }
        .search-input {
            width: 100%;
            height: 38px;
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 0 14px 0 38px;
            font-size: 13.5px;
            color: var(--text-primary);
            outline: none;
            transition: all 0.2s;
        }
        .search-input:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
            background: var(--bg-card);
        }
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
            pointer-events: none;
        }
        .search-shortcut-badge {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--text-muted);
            font-weight: 700;
        }

        /* Search Dropdown Modal */
        #search-results-dropdown {
            position: absolute;
            top: 44px;
            left: 0;
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 100;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            padding: 8px 0;
        }
        .search-group-title {
            padding: 6px 14px;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .search-result-item {
            padding: 8px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            cursor: pointer;
            color: var(--text-primary);
            transition: background 0.15s;
        }
        .search-result-item:hover { background: var(--bg-body); }

        .header-right { display: flex; align-items: center; gap: 14px; }
        .system-status-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--accent-success);
            background: rgba(16, 185, 129, 0.1);
            padding: 4px 10px;
            border-radius: 14px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent-success);
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

        .admin-profile-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 8px 4px 4px;
            border-radius: 24px;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            cursor: pointer;
        }
        .admin-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }
        .admin-name { font-size: 13px; font-weight: 700; color: var(--text-primary); }
        .admin-role { font-size: 11px; color: var(--text-secondary); }

        /* Main Workspace Container */
        main.main-content {
            flex: 1;
            padding: 24px;
            max-width: 1600px;
            width: 100%;
            margin: 0 auto;
        }

        /* Page Title Bar */
        .page-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 14px;
        }
        .page-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-breadcrumb {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .action-button-group { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .btn-primary {
            background: var(--accent-primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
        }
        .btn-primary:hover { background: var(--accent-primary-hover); }
        .btn-secondary {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 8px 14px;
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }
        .btn-secondary:hover { background: var(--bg-body); }
        .btn-danger {
            background: var(--accent-danger);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .pill-btn {
            background: transparent;
            border: none;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .pill-btn:hover { color: var(--text-primary); }
        .pill-btn.active { background: var(--accent-primary); color: #ffffff; }

        .alert-item {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.15s;
        }
        .alert-item:hover { background: var(--bg-body); }
        .alert-item.unread { background: rgba(79, 70, 229, 0.05); }

        /* KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 18px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .kpi-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .kpi-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }
        .kpi-icon-bubble {
            width: 34px;
            height: 34px;
            border-radius: var(--radius-sm);
            background: rgba(79, 70, 229, 0.1);
            color: var(--accent-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .kpi-val {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.1;
        }
        .kpi-footer {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .trend-up { color: var(--accent-success); }
        .trend-down { color: var(--accent-danger); }

        /* Visual Analytics Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 1024px) {
            .analytics-grid { grid-template-columns: 1fr; }
        }
        .chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }
        .chart-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .chart-title { font-size: 15px; font-weight: 700; color: var(--text-primary); }

        /* SVG Bar / Area Chart Container */
        .svg-chart-container {
            width: 100%;
            height: 240px;
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding-top: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .chart-bar-group {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            position: relative;
        }
        .chart-bar {
            width: 100%;
            max-width: 28px;
            background: linear-gradient(180deg, #4f46e5, #818cf8);
            border-radius: 4px 4px 0 0;
            transition: height 0.4s ease;
            cursor: pointer;
        }
        .chart-bar:hover { background: #4338ca; }
        .chart-bar-label {
            font-size: 10.5px;
            color: var(--text-muted);
            margin-top: 6px;
            text-align: center;
        }

        /* Data Tables Card */
        .data-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .data-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .table-filter-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .filter-select, .table-search-input {
            height: 36px;
            padding: 0 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--bg-body);
            color: var(--text-primary);
            font-size: 13px;
            outline: none;
        }
        .filter-select:focus, .table-search-input:focus { border-color: var(--accent-primary); }

        /* Modern Web Guidance: responsive-table */
        .table-responsive {
            container-type: inline-size;
            width: 100%;
            overflow-x: auto;
            position: relative;
            -webkit-overflow-scrolling: touch;
        }
        .table-responsive:focus-visible {
            outline: 2px solid var(--accent-primary);
            outline-offset: -2px;
        }
        table.enterprise-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }
        table.enterprise-table thead {
            position: sticky;
            top: 0;
            z-index: 2;
        }
        table.enterprise-table th {
            background: var(--bg-body);
            color: var(--text-secondary);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11.5px;
            padding: 12px 18px;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }
        table.enterprise-table td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            vertical-align: middle;
        }
        table.enterprise-table tr:hover { background: rgba(79, 70, 229, 0.03); }

        @container (width < 600px) {
            table.enterprise-table th,
            table.enterprise-table td {
                padding: 10px 12px;
                font-size: 12px;
            }
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 12px;
        }
        .badge-active { background: rgba(16, 185, 129, 0.12); color: #059669; }
        .badge-suspended { background: rgba(239, 68, 68, 0.12); color: #dc2626; }
        .badge-pending { background: rgba(245, 158, 11, 0.12); color: #d97706; }
        .badge-verified { background: rgba(14, 165, 233, 0.12); color: #0284c7; }
        .badge-critical { background: rgba(220, 38, 38, 0.16); color: #b91c1c; border: 1px solid rgba(220, 38, 38, 0.3); }

        /* Action Menu Dropdown */
        .btn-table-action {
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-table-action:hover { background: var(--bg-body); color: var(--text-primary); border-color: var(--border-strong); }

        /* Pagination Footer */
        .table-pagination-footer {
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--border-color);
            font-size: 13px;
            color: var(--text-secondary);
            flex-wrap: wrap;
            gap: 10px;
        }
        .pagination-controls { display: flex; align-items: center; gap: 6px; }

        /* Modern Web Guidance: prevent-page-scroll-under-modal-dialog */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            overscroll-behavior: contain;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            overscroll-behavior: contain;
            box-shadow: var(--shadow-lg);
            animation: modalIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }
        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-title { font-size: 16px; font-weight: 700; color: var(--text-primary); }
        .modal-body { padding: 24px; overscroll-behavior: contain; }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: var(--bg-body);
        }

        /* Right-Side Contextual Detail Drawer */
        #context-drawer-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
            z-index: 1100;
            display: none;
            overflow: hidden;
            overscroll-behavior: contain;
        }
        #context-drawer-backdrop.active { display: block; }
        #context-detail-drawer {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 480px;
            max-width: 92vw;
            background: var(--bg-card);
            border-left: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            z-index: 1150;
            transform: translateX(100%);
            transition: transform var(--transition-base);
            display: flex;
            flex-direction: column;
            overscroll-behavior: contain;
        }
        #context-detail-drawer.active { transform: translateX(0); }
        .drawer-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--surface-subtle);
        }
        .drawer-body {
            padding: 20px 22px;
            flex: 1;
            overflow-y: auto;
            overscroll-behavior: contain;
        }
        .drawer-footer {
            padding: 14px 22px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: var(--bg-body);
        }

        /* Mobile Sidebar Backdrop */
        #sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 55;
            overflow: hidden;
            overscroll-behavior: contain;
        }
        #sidebar-backdrop.active { display: block; }

        /* Toast Notifications */
        #toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 12px 18px;
            box-shadow: var(--shadow-lg);
            font-size: 13.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 280px;
            max-width: 400px;
            animation: toastIn 0.25s ease;
        }
        @keyframes toastIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .toast.success { border-left: 4px solid var(--accent-success); }
        .toast.error { border-left: 4px solid var(--accent-danger); }
        .toast.info { border-left: 4px solid var(--accent-info); }
        .toast.warning { border-left: 4px solid var(--accent-warning); }

        /* Dynamic Panels (Sections) & Modern Web Guidance: defer-rendering-heavy-content */
        .admin-section { display: none; }
        .admin-section.active { display: block; animation: fadeIn 0.2s ease; }
        .defer-heavy-section,
        .admin-section.active .data-card:nth-of-type(n+2) {
            content-visibility: auto;
            contain-intrinsic-size: auto none auto 320px;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* Command Palette Modal */
        #command-palette {
            position: fixed;
            top: 14%;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 600px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.25), 0 8px 10px -6px rgb(0 0 0 / 0.2);
            z-index: 1500;
            display: none;
            overflow: hidden;
            overscroll-behavior: contain;
        }
        .cp-search-box {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cp-input {
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            font-size: 16px;
            color: var(--text-primary);
        }
        .cp-list { max-height: 380px; overflow-y: auto; padding: 10px; overscroll-behavior: contain; }
        .cp-item {
            padding: 10px 14px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 13.5px;
            cursor: pointer;
            color: var(--text-primary);
            transition: background 0.15s;
        }
        .cp-item:hover, .cp-item.selected { background: var(--surface-subtle); color: var(--accent-primary); font-weight: 600; }

        /* Form Controls */
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
        .form-control {
            width: 100%;
            height: 38px;
            padding: 0 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--bg-body);
            color: var(--text-primary);
            font-size: 13.5px;
            outline: none;
        }
        .form-control:focus { border-color: var(--accent-primary); background: var(--bg-card); box-shadow: var(--focus-ring); }
        textarea.form-control { height: 80px; padding: 8px 12px; resize: vertical; }

        /* Breadcrumb & Sub-Navigation Pills */
        .top-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            color: var(--text-secondary);
            font-weight: 600;
            white-space: nowrap;
        }
        .top-breadcrumb-current {
            color: var(--text-primary);
            font-weight: 700;
        }
        .funnel-step-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        .funnel-track {
            flex: 1;
            height: 22px;
            background: var(--surface-subtle);
            border-radius: 6px;
            overflow: hidden;
            position: relative;
        }
        .funnel-fill {
            height: 100%;
            background: linear-gradient(90deg, #4f46e5, #0ea5e9);
            border-radius: 6px;
            transition: width 0.35s ease;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            .charts-grid { grid-template-columns: 1fr; }
            .global-search-box { width: 240px; }
            .top-breadcrumb { display: none; }
        }
        @media (max-width: 768px) {
            #sidebar {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                transform: translateX(-100%);
                width: 270px !important;
            }
            #sidebar.mobile-open {
                transform: translateX(0);
            }
            .top-header { padding: 0 14px; }
            .main-content { padding: 16px 14px; }
            .global-search-box { width: 170px; }
            .system-status-indicator span { display: none; }
            .admin-profile-pill .admin-name,
            .admin-profile-pill .admin-role { display: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>
<body>

    <!-- Mobile Sidebar Backdrop -->
    <div id="sidebar-backdrop" onclick="closeMobileSidebar()" aria-hidden="true"></div>

    <!-- 1. Collapsible Left Sidebar (15 Organized Enterprise Workspaces) -->
    <aside id="sidebar" role="navigation" aria-label="প্রধান অ্যাডমিন নেভিগেশন">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon" aria-hidden="true">B</div>
            <div>
                <div class="sidebar-header-title">Bondhoo Admin</div>
                <div class="sidebar-header-sub">Enterprise Command v4</div>
            </div>
        </div>

        <div class="sidebar-scrollable">
            <div class="sidebar-category-title">কমান্ড ও ইন্টেলিজেন্স</div>
            <a class="nav-item active" role="button" tabindex="0" aria-current="page" title="এক্সিকিউটিভ ওভারভিউ" onclick="switchSection('overview')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('overview');}">
                <span class="nav-icon" aria-hidden="true">📊</span>
                <span class="nav-label">এক্সিকিউটিভ ওভারভিউ</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="প্ল্যাটফর্ম অ্যানালিটিক্স ও ফানেল" onclick="switchSection('analytics')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('analytics');}">
                <span class="nav-icon" aria-hidden="true">📈</span>
                <span class="nav-label">প্ল্যাটফর্ম অ্যানালিটিক্স</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="গ্লোবাল সার্চ ও ডসিয়ার তদন্ত" onclick="switchSection('search')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('search');}">
                <span class="nav-icon" aria-hidden="true">🔍</span>
                <span class="nav-label">গ্লোবাল সার্চ ও তদন্ত</span>
            </a>

            <div class="sidebar-category-title">ইউজার ও অ্যাক্সেস গভর্ন্যান্স</div>
            <a class="nav-item" role="button" tabindex="0" title="ব্যবহারকারী ব্যবস্থাপনা" onclick="switchSection('users')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('users');}">
                <span class="nav-icon" aria-hidden="true">👥</span>
                <span class="nav-label">ব্যবহারকারী ব্যবস্থাপনা</span>
                <span class="badge-pill" id="badge-users-count">{{ $kpis['users']['total'] ?? 0 }}</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="রোল, পারমিশন ও এলিভেশন (RBAC)" onclick="switchSection('roles')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('roles');}">
                <span class="nav-icon" aria-hidden="true">🛡️</span>
                <span class="nav-label">রোল ও পারমিশন (RBAC)</span>
            </a>

            <div class="sidebar-category-title">ট্রাস্ট, সেফটি ও কমিউনিটি</div>
            <a class="nav-item" role="button" tabindex="0" title="কন্টেন্ট ও পোস্টসমূহ" onclick="switchSection('content')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('content');}">
                <span class="nav-icon" aria-hidden="true">📝</span>
                <span class="nav-label">কন্টেন্ট ও পোস্টসমূহ</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="AI মডারেশন ও আপিল কিউ" onclick="switchSection('moderation')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('moderation');}">
                <span class="nav-icon" aria-hidden="true">🚨</span>
                <span class="nav-label">মডারেশন কিউ</span>
                <span class="badge-pill" id="badge-reports-count">{{ $kpis['moderation']['pending_reports'] ?? 0 }}</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="ট্রাস্ট, সেফটি ও অপব্যবহার প্রতিরোধ" onclick="switchSection('trust_safety')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('trust_safety');}">
                <span class="nav-icon" aria-hidden="true">🛑</span>
                <span class="nav-label">ট্রাস্ট, সেফটি ও অপব্যবহার</span>
                <span class="badge-pill" id="badge-restrictions-count">{{ $kpis['operations']['active_restrictions'] ?? 0 }}</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="গ্রুপ ও পেজ কমিউনিটি ডিরেক্টরি" onclick="switchSection('social')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('social');}">
                <span class="nav-icon" aria-hidden="true">🌐</span>
                <span class="nav-label">গ্রুপ ও পেজ ডিরেক্টরি</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="মেসেজিং ও সিস্টেম ঘোষণা" onclick="switchSection('messaging')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('messaging');}">
                <span class="nav-icon" aria-hidden="true">💬</span>
                <span class="nav-label">মেসেজিং ও সিস্টেম ঘোষণা</span>
            </a>

            <div class="sidebar-category-title">সিকিউরিটি ও এসআরই অপারেশনস</div>
            <a class="nav-item" role="button" tabindex="0" title="সিকিউরিটি অপারেশনস সেন্টার (SOC 2.0)" onclick="switchSection('security')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('security');}">
                <span class="nav-icon" aria-hidden="true">🔒</span>
                <span class="nav-label">সিকিউরিটি সেন্টার (SOC)</span>
                <span class="badge-pill" id="badge-incidents-count">{{ $kpis['operations']['critical_incidents'] ?? 0 }}</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="রিয়েল-টাইম অপারেশনস অ্যালার্টস" onclick="switchSection('alerts')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('alerts');}">
                <span class="nav-icon" aria-hidden="true">🔔</span>
                <span class="nav-label">অপারেশনস অ্যালার্টস</span>
                <span class="badge-pill" id="badge-alerts-nav-count">{{ $kpis['operations']['unacknowledged_alerts'] ?? 0 }}</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="ব্যাকগ্রাউন্ড জবস ও অটোমেশন" onclick="switchSection('jobs')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('jobs');}">
                <span class="nav-icon" aria-hidden="true">⚡</span>
                <span class="nav-label">ব্যাকগ্রাউন্ড জবস ও কিউ</span>
                <span class="badge-pill" id="badge-failed-jobs-count">{{ $kpis['operations']['failed_jobs'] ?? 0 }}</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="সিস্টেম ও ইনফ্রাস্ট্রাকচার স্বাস্থ্য" onclick="switchSection('health')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('health');}">
                <span class="nav-icon" aria-hidden="true">🩺</span>
                <span class="nav-label">সিস্টেম ও ইনফ্রা স্বাস্থ্য</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="প্রোডাকশন প্রস্তুতি ও ডিজাস্টার রিকভারি (Phase 5)" onclick="switchSection('readiness')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('readiness');}">
                <span class="nav-icon" aria-hidden="true">🚀</span>
                <span class="nav-label">প্রোডাকশন ও রিকভারি (DR)</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="জিরো-ট্রাস্ট, হাই অ্যাভেইলেবিলিটি, কমপ্লায়েন্স ও অটোনোমাস অপারেশনস (Phase 6)" onclick="switchSection('zerotrust')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('zerotrust');}">
                <span class="nav-icon" aria-hidden="true">🛡️</span>
                <span class="nav-label">জিরো-ট্রাস্ট ও রেজিলিয়েন্স</span>
            </a>

            <div class="sidebar-category-title">কমপ্লায়েন্স, ফ্ল্যাগস ও সাপোর্ট</div>
            <a class="nav-item" role="button" tabindex="0" title="অডিট ট্রেইল ও চেকপয়েন্ট" onclick="switchSection('audit')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('audit');}">
                <span class="nav-icon" aria-hidden="true">📜</span>
                <span class="nav-label">অডিট ও কমপ্লায়েন্স</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="ডাটা গভর্ন্যান্স, প্রাইভেসি ও রিটেনশন" onclick="switchSection('governance')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('governance');}">
                <span class="nav-icon" aria-hidden="true">🏛️</span>
                <span class="nav-label">ডাটা গভর্ন্যান্স ও প্রাইভেসি</span>
                <span class="badge-pill" id="badge-privacy-count">{{ $kpis['operations']['pending_privacy_requests'] ?? 0 }}</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="ফিচার ফ্ল্যাগ ও রোলআউট কন্ট্রোল" onclick="switchSection('flags')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('flags');}">
                <span class="nav-icon" aria-hidden="true">🚩</span>
                <span class="nav-label">ফিচার ফ্ল্যাগ ও রোলআউট</span>
                <span class="badge-pill" id="badge-flags-count">{{ $kpis['operations']['active_feature_flags'] ?? 0 }}</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="প্ল্যাটফর্ম কনফিগারেশন ও রোলব্যাক" onclick="switchSection('settings')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('settings');}">
                <span class="nav-icon" aria-hidden="true">⚙️</span>
                <span class="nav-label">প্ল্যাটফর্ম কনফিগারেশন</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="সাপোর্ট সেন্টার ও টিকেট" onclick="switchSection('support')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('support');}">
                <span class="nav-icon" aria-hidden="true">🎫</span>
                <span class="nav-label">সাপোর্ট সেন্টার</span>
                <span class="badge-pill" id="badge-tickets-count">{{ $kpis['moderation']['open_tickets'] ?? 0 }}</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="বিজ্ঞাপন ও ক্রিয়েটর ক্যাশআউট" onclick="switchSection('monetization')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('monetization');}">
                <span class="nav-icon" aria-hidden="true">💰</span>
                <span class="nav-label">বিজ্ঞাপন ও ক্যাশআউট</span>
            </a>
            <a class="nav-item" role="button" tabindex="0" title="ডেভেলপার ও এসআরই টুলস" onclick="switchSection('developer')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();switchSection('developer');}">
                <span class="nav-icon" aria-hidden="true">🛠️</span>
                <span class="nav-label">ডেভেলপার ও এসআরই</span>
            </a>
        </div>
    </aside>

    <!-- 2. Main Application Wrapper -->
    <div id="main-wrapper">
        
        <!-- Top Navigation Bar -->
        <header class="top-header" role="banner">
            <div class="header-left">
                <button class="btn-icon" onclick="toggleSidebar()" aria-label="সাইডবার টগল করুন" title="সাইডবার টগল করুন">
                    <span aria-hidden="true">☰</span>
                </button>
                <nav class="top-breadcrumb" aria-label="ব্রেডক্রাম্ব">
                    <span>কমান্ড সেন্টার</span>
                    <span aria-hidden="true">/</span>
                    <span id="top-breadcrumb-current" class="top-breadcrumb-current">এক্সিকিউটিভ ওভারভিউ</span>
                </nav>
                <div class="global-search-box" role="search">
                    <span class="search-icon" aria-hidden="true">🔍</span>
                    <input type="text" id="global-search-input" class="search-input" aria-label="গ্লোবাল অনুসন্ধান" placeholder="ইউজার, পোস্ট, গ্রুপ বা টিকিট অনুসন্ধান..." autocomplete="off">
                    <button type="button" class="search-shortcut-badge" onclick="openCommandPalette()" title="কমান্ড প্যালেট খুলুন (⌘K / Ctrl+K)" style="cursor: pointer; border: 1px solid var(--border-color);">⌘K</button>
                    
                    <div id="search-results-dropdown" role="listbox" aria-label="অনুসন্ধান ফলাফল">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>
            </div>

            <div class="header-right">
                <div class="system-status-indicator" title="ডাটাবেজ এবং ক্যাশ সার্ভার স্ট্যাটাস">
                    <div class="status-dot" aria-hidden="true"></div>
                    <span id="top-live-status-text">সিস্টেম সচল (লাইভ)</span>
                </div>

                <!-- Admin Alerts Bell Notification Hub -->
                <div class="alerts-dropdown-wrapper" style="position: relative;">
                    <button class="btn-icon" id="alerts-bell-btn" onclick="toggleAlertsDropdown()" aria-label="সিস্টেম অ্যালার্ট ও নোটিফিকেশন" aria-haspopup="true" title="সিস্টেম অ্যালার্ট ও নোটিফিকেশন">
                        <span aria-hidden="true">🔔</span>
                        <span id="alerts-unread-badge" class="badge-pill" style="position: absolute; top: 2px; right: 2px; background: #ef4444; color: white; font-size: 10px; padding: 1px 5px; border-radius: 10px; display: none;">0</span>
                    </button>
                    <div id="alerts-dropdown" role="region" aria-label="সাম্প্রতিক সিস্টেম অ্যালার্ট" style="display: none; position: absolute; right: 0; top: 44px; width: 340px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); z-index: 100; overflow: hidden;">
                        <div style="padding: 12px 16px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-weight: 700; font-size: 13px;">🚨 সিস্টেম রিয়েল-টাইম অ্যালার্ট</div>
                            <button onclick="markAllAlertsAsRead()" style="background: none; border: none; color: var(--accent-primary); font-size: 11px; cursor: pointer; font-weight: 600;">সব পঠিত করুন</button>
                        </div>
                        <div id="alerts-list" style="max-height: 320px; overflow-y: auto; overscroll-behavior: contain;">
                            <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 12px;">কোনো নতুন অ্যালার্ট নেই</div>
                        </div>
                    </div>
                </div>

                <button class="btn-icon" onclick="toggleTheme()" aria-label="ডার্ক অথবা লাইট থিম পরিবর্তন করুন" title="ডার্ক/লাইট থিম পরিবর্তন">
                    <span id="theme-icon" aria-hidden="true">🌙</span>
                </button>

                <div class="admin-profile-pill" onclick="handleLogout()" role="button" tabindex="0" aria-label="অ্যাডমিন প্রোফাইল ও লগআউট" title="লগআউট করতে ক্লিক করুন">
                    <div class="admin-avatar" aria-hidden="true">{{ strtoupper(substr($admin->name ?? 'A', 0, 1)) }}</div>
                    <div>
                        <div class="admin-name">{{ $admin->name ?? 'Admin' }}</div>
                        <div class="admin-role">{{ strtoupper($admin->role ?? 'SUPER_ADMIN') }}</div>
                    </div>
                    <span style="font-size: 14px; color: var(--text-muted); margin-left: 4px;" aria-hidden="true">🚪</span>
                </div>
            </div>
        </header>

        <!-- 3. Dynamic Main Content Panels -->
        <main class="main-content" role="main">

            <!-- ======================================================== -->
            <!-- SECTION 1: EXECUTIVE OVERVIEW                           -->
            <!-- ======================================================== -->
            <div id="sec-overview" class="admin-section active">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">📊 এক্সিকিউটিভ ওভারভিউ ড্যাশবোর্ড</h1>
                        <div class="page-breadcrumb">সোশ্যাল প্ল্যাটফর্মের সার্বিক পারফরম্যান্স, সক্রিয়তা ও রিয়েল-টাইম মেট্রিক্স</div>
                    </div>
                    <div class="action-button-group">
                        <button class="btn-secondary" onclick="exportData('users')">📥 ইউজার এক্সপোর্ট</button>
                        <button class="btn-primary" onclick="openAnnouncementModal()">📢 নতুন ঘোষণা দিন</button>
                    </div>
                </div>

                <!-- 10 Executive Command KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card" role="button" tabindex="0" onclick="switchSection('users')" onkeydown="if(event.key==='Enter')switchSection('users')">
                        <div class="kpi-card-header">
                            <span class="kpi-title">মোট ব্যবহারকারী</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">👥</div>
                        </div>
                        <div class="kpi-val" id="kpi-total-users">{{ number_format($kpis['users']['total'] ?? 0) }}</div>
                        <div class="kpi-footer">
                            <span class="trend-up">↑ {{ $kpis['users']['growth_pct'] ?? 0 }}%</span>
                            <span style="color: var(--text-muted);">গতকালের তুলনায় (ভেরিফাইড: {{ number_format($kpis['users']['verified'] ?? 0) }})</span>
                        </div>
                    </div>

                    <div class="kpi-card" role="button" tabindex="0" onclick="switchSection('analytics')" onkeydown="if(event.key==='Enter')switchSection('analytics')">
                        <div class="kpi-card-header">
                            <span class="kpi-title">আজকের সক্রিয় ইউজার (DAU)</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">⚡</div>
                        </div>
                        <div class="kpi-val" id="kpi-dau-users">{{ number_format($kpis['users']['dau'] ?? 0) }}</div>
                        <div class="kpi-footer">
                            <span style="color: var(--accent-info); font-weight: 600;">MAU: {{ number_format($kpis['users']['mau'] ?? 0) }}</span>
                            <span style="color: var(--text-muted);">সাসপেন্ডেড: {{ number_format($kpis['users']['suspended'] ?? 0) }}</span>
                        </div>
                    </div>

                    <div class="kpi-card" role="button" tabindex="0" onclick="switchSection('content')" onkeydown="if(event.key==='Enter')switchSection('content')">
                        <div class="kpi-card-header">
                            <span class="kpi-title">মোট প্রকাশিত পোস্ট</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">📝</div>
                        </div>
                        <div class="kpi-val">{{ number_format($kpis['content']['total_posts'] ?? 0) }}</div>
                        <div class="kpi-footer">
                            <span class="trend-up">+{{ $kpis['content']['posts_today'] ?? 0 }}</span>
                            <span style="color: var(--text-muted);">আজ প্রকাশিত</span>
                        </div>
                    </div>

                    <div class="kpi-card" role="button" tabindex="0" onclick="switchSection('social')" onkeydown="if(event.key==='Enter')switchSection('social')">
                        <div class="kpi-card-header">
                            <span class="kpi-title">মোট এনগেজমেন্ট ও কমিউনিটি</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">❤️</div>
                        </div>
                        <div class="kpi-val">{{ number_format(($kpis['content']['total_comments'] ?? 0) + ($kpis['content']['total_reactions'] ?? 0)) }}</div>
                        <div class="kpi-footer">
                            <span style="color: var(--text-muted);">💬 {{ $kpis['content']['total_comments'] ?? 0 }} কমেন্ট | 🌐 {{ ($kpis['content']['total_groups'] ?? 0) + ($kpis['content']['total_pages'] ?? 0) }} কমিউনিটি</span>
                        </div>
                    </div>

                    <div class="kpi-card" role="button" tabindex="0" onclick="switchSection('moderation')" onkeydown="if(event.key==='Enter')switchSection('moderation')">
                        <div class="kpi-card-header">
                            <span class="kpi-title">অমীমাংসিত রিপোর্ট (AI Queue)</span>
                            <div class="kpi-icon-bubble" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-danger);" aria-hidden="true">🚨</div>
                        </div>
                        <div class="kpi-val" style="color: var(--accent-danger);">{{ number_format($kpis['moderation']['pending_reports'] ?? 0) }}</div>
                        <div class="kpi-footer">
                            <span style="color: var(--accent-danger); font-weight: 700;">দ্রুত অ্যাকশন প্রয়োজন</span>
                        </div>
                    </div>

                    <div class="kpi-card" role="button" tabindex="0" onclick="switchSection('trust_safety')" onkeydown="if(event.key==='Enter')switchSection('trust_safety')">
                        <div class="kpi-card-header">
                            <span class="kpi-title">সক্রিয় অপব্যবহার সীমাবদ্ধতা</span>
                            <div class="kpi-icon-bubble" style="background: rgba(245, 158, 11, 0.12); color: var(--accent-warning);" aria-hidden="true">🛑</div>
                        </div>
                        <div class="kpi-val" id="kpi-active-restrictions">{{ number_format($kpis['operations']['active_restrictions'] ?? 0) }}</div>
                        <div class="kpi-footer">
                            <span style="color: var(--text-muted);">অটোমেটেড ও ম্যানুয়াল রিস্ক গার্ড</span>
                        </div>
                    </div>

                    <div class="kpi-card" role="button" tabindex="0" onclick="switchSection('security')" onkeydown="if(event.key==='Enter')switchSection('security')">
                        <div class="kpi-card-header">
                            <span class="kpi-title">ক্রিটিক্যাল সিকিউরিটি ইনসিডেন্ট</span>
                            <div class="kpi-icon-bubble" style="background: rgba(239, 68, 68, 0.12); color: var(--accent-danger);" aria-hidden="true">🔒</div>
                        </div>
                        <div class="kpi-val" id="kpi-critical-incidents" style="color: {{ ($kpis['operations']['critical_incidents'] ?? 0) > 0 ? 'var(--accent-danger)' : 'var(--text-primary)' }};">{{ number_format($kpis['operations']['critical_incidents'] ?? 0) }}</div>
                        <div class="kpi-footer">
                            <span style="color: var(--text-muted);">SOC 2.0 থ্রেট মনিটরিং</span>
                        </div>
                    </div>

                    <div class="kpi-card" role="button" tabindex="0" onclick="switchSection('alerts')" onkeydown="if(event.key==='Enter')switchSection('alerts')">
                        <div class="kpi-card-header">
                            <span class="kpi-title">অপঠিত অপারেশনস অ্যালার্ট</span>
                            <div class="kpi-icon-bubble" style="background: rgba(14, 165, 233, 0.12); color: var(--accent-info);" aria-hidden="true">🔔</div>
                        </div>
                        <div class="kpi-val" id="kpi-unread-alerts">{{ number_format($kpis['operations']['unacknowledged_alerts'] ?? 0) }}</div>
                        <div class="kpi-footer">
                            <span style="color: var(--text-muted);">ডিডুপ্লিকেটেড রিয়েল-টাইম ফিড</span>
                        </div>
                    </div>

                    <div class="kpi-card" role="button" tabindex="0" onclick="switchSection('jobs')" onkeydown="if(event.key==='Enter')switchSection('jobs')">
                        <div class="kpi-card-header">
                            <span class="kpi-title">ব্যর্থ ব্যাকগ্রাউন্ড জবস</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">⚙️</div>
                        </div>
                        <div class="kpi-val" id="kpi-failed-jobs" style="color: {{ ($kpis['operations']['failed_jobs'] ?? 0) > 0 ? 'var(--accent-danger)' : 'var(--accent-success)' }};">{{ number_format($kpis['operations']['failed_jobs'] ?? 0) }}</div>
                        <div class="kpi-footer">
                            <span style="color: var(--text-muted);">ড্রাইভার: {{ $kpis['system']['queue_driver'] ?? 'database' }}</span>
                        </div>
                    </div>

                    <div class="kpi-card" role="button" tabindex="0" onclick="switchSection('health')" onkeydown="if(event.key==='Enter')switchSection('health')">
                        <div class="kpi-card-header">
                            <span class="kpi-title">সার্ভার স্বাস্থ্য ও গভর্ন্যান্স</span>
                            <div class="kpi-icon-bubble" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-success);" aria-hidden="true">🩺</div>
                        </div>
                        <div class="kpi-val" style="color: var(--accent-success); font-size: 20px; padding-top: 4px;">Operational</div>
                        <div class="kpi-footer">
                            <span style="color: var(--text-muted);">DB: {{ $kpis['system']['database'] ?? 'healthy' }} | প্রাইভেসি কিউ: {{ $kpis['operations']['pending_privacy_requests'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Visual Time Series Analytics Charts -->
                <div class="analytics-grid">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <div class="chart-title">📈 ব্যবহারকারী নিবন্ধন ও প্রবৃদ্ধি প্রবণতা (গত ১৪ দিন)</div>
                            <span style="font-size: 12px; color: var(--text-muted);">রিয়েল ডাটাবেজ তথ্য</span>
                        </div>
                        <div class="svg-chart-container" id="registrations-chart">
                            @php
                                $regList = $analytics['registrations'] ?? [];
                                $maxVal = max(array_merge([1], array_column($regList, 'registrations')));
                            @endphp
                            @foreach($regList as $item)
                                @php
                                    $heightPct = min(100, max(8, round(($item['registrations'] / $maxVal) * 100)));
                                @endphp
                                <div class="chart-bar-group" title="{{ $item['label'] }}: {{ $item['registrations'] }} জন">
                                    <div class="chart-bar" style="height: {{ $heightPct }}%;"></div>
                                    <div class="chart-bar-label">{{ $item['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-card-header">
                            <div class="chart-title">🛡️ রিপোর্ট ও মডারেশন বণ্টন</div>
                        </div>
                        <div style="padding: 10px 0;">
                            @php
                                $categories = $analytics['reports_by_category'] ?? [];
                                $totalRep = max(1, array_sum($categories));
                            @endphp
                            @forelse($categories as $cat => $cnt)
                                @php $pct = round(($cnt / $totalRep) * 100); @endphp
                                <div style="margin-bottom: 12px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; margin-bottom: 4px;">
                                        <span>{{ ucfirst(str_replace('_', ' ', $cat)) }}</span>
                                        <span>{{ $cnt }} ({{ $pct }}%)</span>
                                    </div>
                                    <div style="height: 6px; background: var(--bg-body); border-radius: 4px; overflow: hidden;">
                                        <div style="height: 100%; width: {{ $pct }}%; background: var(--accent-danger);"></div>
                                    </div>
                                </div>
                            @empty
                                <div style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 30px 0;">বর্তমানে কোনো ক্যাটাগরির রিপোর্ট নেই</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 1.5: EXECUTIVE INTELLIGENCE CENTER (PHASE 2 & 3) -->
            <!-- ======================================================== -->
            <div id="sec-analytics" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">📈 এক্সিকিউটিভ ইন্টেলিজেন্স ও প্রেডিক্টিভ অ্যানালিটিক্স</h1>
                        <div class="page-breadcrumb">
                            DAU, WAU, MAU, স্টিকিনেস, কনভার্শন ফানেল, কোহর্ট রিটেনশন, অ্যানোমালি ডিটেকশন ও স্ট্রিমিং CSV এক্সপোর্ট
                            <span id="intel-freshness-badge" class="badge-status badge-verified" style="margin-left: 8px; font-size: 11px;">● Live Query</span>
                        </div>
                    </div>
                    <div class="action-button-group">
                        <div class="range-selector-pills" role="group" aria-label="অ্যানালিটিক্স সময়সীমা" style="display: flex; gap: 4px; background: var(--bg-card); padding: 4px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                            <button class="pill-btn" onclick="changeIntelligenceRange('7d', this)">৭ দিন</button>
                            <button class="pill-btn active" onclick="changeIntelligenceRange('14d', this)">১৪ দিন</button>
                            <button class="pill-btn" onclick="changeIntelligenceRange('30d', this)">৩০ দিন</button>
                            <button class="pill-btn" onclick="changeIntelligenceRange('90d', this)">৯০ দিন</button>
                            <button class="pill-btn" onclick="changeIntelligenceRange('1y', this)">১ বছর</button>
                        </div>
                        <button class="btn-primary" onclick="exportIntelligenceCsv()">📥 স্ট্রিমিং CSV রিপোর্ট</button>
                    </div>
                </div>

                <!-- Retention & Engagement KPIs -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">দৈনিক সক্রিয় ব্যবহারকারী (DAU)</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">⚡</div>
                        </div>
                        <div class="kpi-val" id="intel-dau">--</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">আজকের সক্রিয় ইউজার</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">সাপ্তাহিক সক্রিয় ব্যবহারকারী (WAU)</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">📅</div>
                        </div>
                        <div class="kpi-val" id="intel-wau">--</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">গত ৭ দিনে সক্রিয়</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">মাসিক সক্রিয় ব্যবহারকারী (MAU)</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">👥</div>
                        </div>
                        <div class="kpi-val" id="intel-mau">--</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">গত ৩০ দিনে সক্রিয়</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">প্ল্যাটফর্ম স্টিকিনেস (DAU/MAU)</span>
                            <div class="kpi-icon-bubble" style="background: rgba(79, 70, 229, 0.1); color: var(--accent-primary);" aria-hidden="true">🎯</div>
                        </div>
                        <div class="kpi-val" id="intel-stickiness" style="color: var(--accent-primary);">--%</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">ইউজার রিটেনশন ও এনগেজমেন্ট</span></div>
                    </div>
                </div>

                <!-- Growth Comparisons Grid -->
                <div class="analytics-grid" style="margin-top: 20px;">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <div class="chart-title">📊 ইউজার ও কন্টেন্ট প্রবৃদ্ধি তুলনা (পূর্ববর্তী মেয়াদের তুলনায়)</div>
                        </div>
                        <div style="padding: 16px 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                                <div>
                                    <div style="font-weight: 700;">নতুন ইউজার নিবন্ধন</div>
                                    <div style="font-size: 12px; color: var(--text-muted);" id="intel-users-count">--</div>
                                </div>
                                <div id="intel-users-growth" style="font-weight: 700; font-size: 16px;">--%</div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                                <div>
                                    <div style="font-weight: 700;">নতুন পোস্ট প্রকাশনা</div>
                                    <div style="font-size: 12px; color: var(--text-muted);" id="intel-posts-count">--</div>
                                </div>
                                <div id="intel-posts-growth" style="font-weight: 700; font-size: 16px;">--%</div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0;">
                                <div>
                                    <div style="font-weight: 700;">ডাটাবেজ ল্যাটেন্সি ও স্বাস্থ্য</div>
                                    <div style="font-size: 12px; color: var(--text-muted);">কোয়েরি এক্সিকিউশন টাইম</div>
                                </div>
                                <div id="intel-db-latency" style="font-weight: 700; color: var(--accent-success);">-- ms</div>
                            </div>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-card-header">
                            <div class="chart-title">💰 বিজ্ঞাপন রাজস্ব ও ক্রিয়েটর আর্নিং সারাংশ</div>
                        </div>
                        <div style="padding: 16px 0;">
                            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                                <span style="color: var(--text-secondary);">মোট বিজ্ঞাপন ক্যাম্পেইন রাজস্ব:</span>
                                <span style="font-weight: 700;" id="intel-ad-revenue">--</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                                <span style="color: var(--text-secondary);">অনুমোদিত ক্রিয়েটর পেআউট:</span>
                                <span style="font-weight: 700; color: var(--accent-success);" id="intel-payout-approved">--</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px 0;">
                                <span style="color: var(--text-secondary);">অমীমাংসিত মডারেশন রিপোর্ট:</span>
                                <span style="font-weight: 700; color: var(--accent-danger);" id="intel-pending-reports">--</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phase 3/4: Conversion Funnel & Anomaly Detection Grid -->
                <div class="analytics-grid" style="margin-top: 20px;">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <div class="chart-title">🌪️ রেজিস্ট্রেশন থেকে অ্যাক্টিভেশন ফানেল (Conversion Funnel)</div>
                            <span style="font-size: 11.5px; color: var(--text-muted);">৪ ধাপ রূপান্তর বিশ্লেষণ</span>
                        </div>
                        <div id="intel-funnel-container" style="padding: 10px 0;">
                            <div style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 24px;">ফানেল ডাটা লোড হচ্ছে...</div>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-card-header">
                            <div class="chart-title">🚨 স্ট্যাটিস্টিক্যাল অ্যানোমালি ডিটেকশন (Anomaly Engine)</div>
                            <span style="font-size: 11.5px; color: var(--text-muted);">বেসলাইন বনাম বর্তমান স্পাইক</span>
                        </div>
                        <div id="intel-anomalies-container" style="padding: 10px 0;">
                            <div style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 24px;">অ্যানোমালি স্ক্যান চলছে...</div>
                        </div>
                    </div>
                </div>

                <!-- Phase 3/4: 4-Week Retention Cohorts & Trending Topics -->
                <div class="analytics-grid" style="margin-top: 20px;">
                    <div class="data-card" style="margin-bottom: 0;">
                        <div class="data-card-header">
                            <div class="chart-title">📅 ৪-সপ্তাহের ইউজার রিটেনশন কোহর্ট (Cohort Matrix)</div>
                        </div>
                        <div class="table-responsive" role="region" aria-label="ইউজার রিটেনশন কোহর্ট টেবিল" tabindex="0">
                            <table class="enterprise-table">
                                <thead>
                                    <tr>
                                        <th>কোহর্ট সপ্তাহ</th>
                                        <th>নিবন্ধিত ইউজার</th>
                                        <th>সক্রিয় রিটেনশন</th>
                                        <th>রিটেনশন হার (%)</th>
                                    </tr>
                                </thead>
                                <tbody id="intel-cohorts-tbody">
                                    <tr><td colspan="4" style="text-align: center; padding: 20px;">কোহর্ট লোড হচ্ছে...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-card-header">
                            <div class="chart-title">🔥 ট্রেন্ডিং হ্যাশট্যাগ ও শীর্ষ সক্রিয় কমিউনিটি</div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding-top: 8px;">
                            <div>
                                <div style="font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 8px;">ট্রেন্ডিং টপিক / হ্যাশট্যাগ</div>
                                <div id="intel-trending-hashtags" style="font-size: 12.5px; color: var(--text-muted);">লোড হচ্ছে...</div>
                            </div>
                            <div>
                                <div style="font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 8px;">শীর্ষ গ্রুপ ও কমিউনিটি</div>
                                <div id="intel-top-communities" style="font-size: 12.5px; color: var(--text-muted);">লোড হচ্ছে...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 2: USER MANAGEMENT                              -->
            <!-- ======================================================== -->
            <div id="sec-users" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">👥 ব্যবহারকারী অ্যাকাউন্ট ব্যবস্থাপনা</h1>
                        <div class="page-breadcrumb">নিবন্ধন, পরিচয় যাচাইকরণ, একাউন্ট সাসপেনশন ও বিস্তারিত প্রোফাইল নিয়ন্ত্রণ</div>
                    </div>
                    <div class="action-button-group">
                        <button class="btn-secondary" onclick="exportData('users')">📥 CSV এক্সপোর্ট</button>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-card-header">
                        <div class="table-filter-bar">
                            <input type="text" id="user-search-filter" class="table-search-input" placeholder="নাম, ইউজারনেম বা ইমেইল..." onkeyup="handleUserSearch(event)">
                            <select id="user-status-filter" class="filter-select" onchange="loadUsersTable(1)">
                                <option value="all">সকল স্ট্যাটাস</option>
                                <option value="active">সক্রিয় (Active)</option>
                                <option value="verified">ভেরিফাইড (Verified)</option>
                                <option value="suspended">সাসপেন্ডেড (Suspended)</option>
                                <option value="banned">ব্যানকৃত (Banned)</option>
                                <option value="new">নতুন নিবন্ধিত (New)</option>
                            </select>
                        </div>
                        <div class="table-filter-bar">
                            <button class="btn-secondary" onclick="applyBulkUserAction('verify')">✓ ভেরিফাই করুন</button>
                            <button class="btn-secondary" onclick="applyBulkUserAction('suspend')" style="color: var(--accent-danger);">⛔ সাসপেন্ড করুন</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th width="40"><input type="checkbox" id="select-all-users" onclick="toggleSelectAllUsers(this)"></th>
                                    <th>ব্যবহারকারী</th>
                                    <th>ইউজারনেম / ইমেইল</th>
                                    <th>রোল</th>
                                    <th>স্ট্যাটাস</th>
                                    <th>নিবন্ধনের তারিখ</th>
                                    <th style="text-align: right;">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <tr><td colspan="7" style="text-align: center; padding: 30px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination-footer" id="users-pagination-bar">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 3: CONTENT MANAGEMENT                           -->
            <!-- ======================================================== -->
            <div id="sec-content" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">📝 সোশ্যাল কন্টেন্ট ও পোস্ট ম্যানেজমেন্ট</h1>
                        <div class="page-breadcrumb">প্রকাশিত পোস্টসমূহ তদারকি, লুকানো, পিন ও কন্টেন্ট ডিলিশন</div>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-card-header">
                        <div class="table-filter-bar">
                            <input type="text" id="post-search-filter" class="table-search-input" placeholder="পোস্টের বিষয়বস্তু খুঁজুন..." onkeyup="handlePostSearch(event)">
                            <select id="post-status-filter" class="filter-select" onchange="loadPostsTable(1)">
                                <option value="all">সকল পোস্ট</option>
                                <option value="reported">রিপোর্টকৃত পোস্ট (Reported)</option>
                                <option value="hidden">লুকানো পোস্ট (Hidden)</option>
                                <option value="pinned">পিন করা পোস্ট (Pinned)</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>লেখক (Author)</th>
                                    <th>পোস্টের বিবরণ</th>
                                    <th>এনগেজমেন্ট</th>
                                    <th>রিপোর্ট</th>
                                    <th>তারিখ</th>
                                    <th style="text-align: right;">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="posts-table-body">
                                <tr><td colspan="6" style="text-align: center; padding: 30px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination-footer" id="posts-pagination-bar"></div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 4: MODERATION & SAFETY (AI TRUST & SAFETY)      -->
            <!-- ======================================================== -->
            <div id="sec-moderation" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🚨 কন্টেন্ট মডারেশন ও AI সেফটি কিউ</h1>
                        <div class="page-breadcrumb">দ্বিভাষিক (বাংলা ও ইংরেজি) AI থ্রেট ক্লাসিফিকেশন, ডুপ্লিকেট গ্রুপিং, রিভিউয়ার অ্যাসাইনমেন্ট ও আপিল নিষ্পত্তি</div>
                    </div>
                    <div class="action-button-group">
                        <button class="btn-secondary" onclick="loadModerationTable(1)">🔄 কিউ রিফ্রেশ</button>
                    </div>
                </div>

                <!-- Reviewer Quality & AI Metrics Bar -->
                <div class="kpi-grid" style="margin-bottom: 20px;">
                    <div class="kpi-card">
                        <div class="kpi-card-header"><span class="kpi-title">মোট নিষ্পত্তিকৃত রিপোর্ট</span><div class="kpi-icon-bubble" aria-hidden="true">✅</div></div>
                        <div class="kpi-val" id="mod-metric-reviewed">--</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">মডারেটর ও AI দ্বারা সম্পন্ন</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header"><span class="kpi-title">গড় নিষ্পত্তি সময়</span><div class="kpi-icon-bubble" aria-hidden="true">⏱️</div></div>
                        <div class="kpi-val" id="mod-metric-avg-time">-- মিনিট</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">SLA রেসপন্স টাইম</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header"><span class="kpi-title">AI ফলস-পজিটিভ হার</span><div class="kpi-icon-bubble" aria-hidden="true">🎯</div></div>
                        <div class="kpi-val" id="mod-metric-fp-rate">--%</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">মডেল নির্ভুলতা সূচক</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header"><span class="kpi-title">আপিল ওভারটার্ন হার</span><div class="kpi-icon-bubble" aria-hidden="true">⚖️</div></div>
                        <div class="kpi-val" id="mod-metric-overturn-rate">--%</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">ন্যায্যতা ও গুণমান সূচক</span></div>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-card-header">
                        <div class="table-filter-bar">
                            <select id="report-status-filter" class="filter-select" aria-label="রিপোর্ট স্ট্যাটাস ফিল্টার" onchange="loadModerationTable(1)">
                                <option value="pending">অমীমাংসিত (Pending Review)</option>
                                <option value="actioned">অ্যাকশন গ্রহণকৃত (Actioned)</option>
                                <option value="dismissed">খারিজকৃত (Dismissed)</option>
                                <option value="all">সকল রিপোর্ট</option>
                            </select>
                            <select id="report-reason-filter" class="filter-select" aria-label="অভিযোগের ক্যাটাগরি ফিল্টার" onchange="loadModerationTable(1)">
                                <option value="all">সকল ক্যাটাগরি</option>
                                <option value="spam">স্প্যাম (Spam)</option>
                                <option value="scam_phishing">স্ক্যাম / ফিশিং (Scam)</option>
                                <option value="hate_harassment">হয়রানি / ঘৃণা (Harassment)</option>
                                <option value="violence_threat">সহিংসতা / হুমকি (Violence)</option>
                                <option value="adult_explicit">অপ্রাপ্তবয়স্ক অনুপযুক্ত (Adult)</option>
                            </select>
                            <select id="report-priority-filter" class="filter-select" aria-label="অগ্রাধিকার ফিল্টার" onchange="loadModerationTable(1)">
                                <option value="all">সকল অগ্রাধিকার (Priority)</option>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; cursor: pointer; padding: 0 8px;">
                                <input type="checkbox" id="report-ai-flagged-filter" onchange="loadModerationTable(1)"> 🤖 কেবল AI চিহ্নিত
                            </label>
                        </div>
                    </div>

                    <div class="table-responsive" role="region" aria-label="মডারেশন রিপোর্ট কিউ টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>অভিযোগকারী</th>
                                    <th>ক্যাটাগরি ও AI স্কোর</th>
                                    <th>টার্গেট কন্টেন্ট / প্রোফাইল</th>
                                    <th>বিবরণ ও এভিডেন্স</th>
                                    <th>তারিখ</th>
                                    <th>স্ট্যাটাস ও রিভিউয়ার</th>
                                    <th style="text-align: right;">সিদ্ধান্ত ও অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="moderation-table-body">
                                <tr><td colspan="7" style="text-align: center; padding: 30px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination-footer" id="moderation-pagination-bar"></div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 5: SOCIAL HUB (GROUPS & PAGES)                  -->
            <!-- ======================================================== -->
            <div id="sec-social" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🌐 গ্রুপ ও কমিউনিটি পেজ ডিরেক্টরি</h1>
                        <div class="page-breadcrumb">সোশ্যাল নেটওয়ার্কের সকল গ্রুপ, পেজ এবং সদস্য সংখ্যা পর্যবেক্ষণ</div>
                    </div>
                    <div class="action-button-group">
                        <select id="social-type-filter" class="filter-select" aria-label="কমিউনিটি ধরণ ফিল্টার" onchange="loadSocialTable(1)">
                            <option value="groups">গ্রুপসমূহ (Groups)</option>
                            <option value="pages">পাবলিক পেজসমূহ (Pages)</option>
                        </select>
                    </div>
                </div>

                <div class="data-card">
                    <div class="table-responsive" role="region" aria-label="গ্রুপ ও কমিউনিটি পেজ টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>নাম ও বিবরণ</th>
                                    <th>নির্মাতা / ওনার</th>
                                    <th>সদস্য / ফলোয়ার</th>
                                    <th>গোপনীয়তা মোড</th>
                                    <th>তৈরির তারিখ</th>
                                </tr>
                            </thead>
                            <tbody id="social-table-body">
                                <tr><td colspan="5" style="text-align: center; padding: 30px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-pagination-footer" id="social-pagination-bar"></div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 6: MESSAGING & ANNOUNCEMENTS                    -->
            <!-- ======================================================== -->
            <div id="sec-messaging" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">💬 মেসেজিং ও সিস্টেম ঘোষণা কেন্দ্র</h1>
                        <div class="page-breadcrumb">প্ল্যাটফর্ম ব্রডকাস্ট নোটিশ এবং রিয়েল-টাইম মেসেঞ্জার কার্যক্রম</div>
                    </div>
                    <button class="btn-primary" onclick="openAnnouncementModal()">📢 নতুন ঘোষণা দিন</button>
                </div>

                <div class="data-card">
                    <div class="data-card-header">
                        <div class="chart-title">সক্রিয় ও অতীত প্ল্যাটফর্ম ঘোষণা</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="প্ল্যাটফর্ম ঘোষণা টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>শিরোনাম</th>
                                    <th>বার্তা</th>
                                    <th>ধরণ</th>
                                    <th>টার্গেট অডিয়েন্স</th>
                                    <th>প্রকাশক</th>
                                    <th>তারিখ</th>
                                </tr>
                            </thead>
                            <tbody id="announcements-table-body">
                                <tr><td colspan="6" style="text-align: center; padding: 30px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 7: ROLES, PERMISSIONS & PRIVILEGE ELEVATION      -->
            <!-- ======================================================== -->
            <div id="sec-roles" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🛡️ রোল, পারমিশন ও প্রিভিলেজ গভর্ন্যান্স (RBAC / ABAC)</h1>
                        <div class="page-breadcrumb">অ্যাডমিনিস্ট্রেটর, মডারেটর, SOC লিড ও সাময়িক বিশেষাধিকার পদোন্নতি (Time-Limited Elevation)</div>
                    </div>
                </div>

                <!-- Grant Time-Limited Privilege Elevation & Role Assignment Grid -->
                <div class="analytics-grid" style="margin-bottom: 24px;">
                    <div class="data-card" style="padding: 20px; margin-bottom: 0;">
                        <div class="chart-title" style="margin-bottom: 12px;">⏱️ সাময়িক বিশেষাধিকার প্রদান (JIT Privilege Elevation)</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group" style="margin-bottom: 8px;">
                                <label class="form-label" for="elev-user-id">টার্গেট ইউজার আইডি</label>
                                <input type="number" id="elev-user-id" class="form-control" placeholder="যেমন: 2">
                            </div>
                            <div class="form-group" style="margin-bottom: 8px;">
                                <label class="form-label" for="elev-role-select">উন্নীত রোল (Elevated Role)</label>
                                <select id="elev-role-select" class="form-control">
                                    <option value="SOC_LEAD">SOC_LEAD</option>
                                    <option value="ADMIN">ADMIN</option>
                                    <option value="MODERATOR">MODERATOR</option>
                                    <option value="AUDITOR">AUDITOR</option>
                                    <option value="SUPER_ADMIN">SUPER_ADMIN</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 8px;">
                                <label class="form-label" for="elev-duration-mins">মেয়াদ (মিনিট, ৫-১৪৪০)</label>
                                <input type="number" id="elev-duration-mins" class="form-control" value="60" min="5" max="1440">
                            </div>
                            <div class="form-group" style="margin-bottom: 8px;">
                                <label class="form-label" for="elev-reason">অডিটযোগ্য কারণ</label>
                                <input type="text" id="elev-reason" class="form-control" placeholder="জরুরি ইনসিডেন্ট তদন্ত...">
                            </div>
                        </div>
                        <button class="btn-primary" style="margin-top: 8px;" onclick="grantPrivilegeElevation()">⚡ সাময়িক এলিভেশন অনুমোদন করুন</button>
                    </div>

                    <div class="data-card" style="padding: 20px; margin-bottom: 0;">
                        <div class="chart-title" style="margin-bottom: 12px;">🔑 স্থায়ী রোল নির্ধারণ (Assign Permanent Role)</div>
                        <div class="form-group">
                            <label class="form-label" for="assign-role-user-id">ব্যবহারকারী আইডি</label>
                            <input type="number" id="assign-role-user-id" class="form-control" placeholder="ইউজার আইডি লিখুন...">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="assign-role-name">রোল নির্বাচন করুন</label>
                            <select id="assign-role-name" class="form-control">
                                <option value="ADMIN">ADMIN</option>
                                <option value="SUPER_ADMIN">SUPER_ADMIN</option>
                                <option value="USER">USER</option>
                            </select>
                        </div>
                        <button class="btn-secondary" onclick="assignRoleQuick()">✓ রোল আপডেট করুন</button>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-card-header">
                        <div class="chart-title">সিস্টেমে বিদ্যমান রোল ও অনুমোদিত পারমিশন ম্যাট্রিক্স</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="রোল ও পারমিশন ম্যাট্রিক্স টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>রোলের নাম</th>
                                    <th>বিবরণ</th>
                                    <th>অনুমোদিত পারমিশনসমূহ</th>
                                </tr>
                            </thead>
                            <tbody id="roles-table-body">
                                <tr><td colspan="3" style="text-align: center; padding: 30px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Active & Recent Time-Limited Elevations Table -->
                <div class="data-card">
                    <div class="data-card-header">
                        <div class="chart-title">⏳ সাময়িক বিশেষাধিকার পদোন্নতি লগ (Time-Limited Elevations)</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="সাময়িক বিশেষাধিকার পদোন্নতি টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>আইডি</th>
                                    <th>ব্যবহারকারী</th>
                                    <th>উন্নীত রোল</th>
                                    <th>কারণ</th>
                                    <th>মেয়াদ উত্তীর্ণ</th>
                                    <th>স্ট্যাটাস</th>
                                    <th style="text-align: right;">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="elevations-table-body">
                                <tr><td colspan="7" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 8: SECURITY CENTER (SOC 2.0)                     -->
            <!-- ======================================================== -->
            <div id="sec-security" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🛡️ এন্টারপ্রাইজ সিকিউরিটি অপারেশন সেন্টার (SOC 2.0)</h1>
                        <div class="page-breadcrumb">থ্রেট মনিটরিং, সক্রিয় ইনসিডেন্ট তদন্ত, ব্রুট-ফোর্স ট্রেন্ড, রেসপন্স প্লেবুক ও তাৎক্ষণিক সেশন রিভোকেশন</div>
                    </div>
                </div>

                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">সক্রিয় সেশন (Active Tokens)</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">💻</div>
                        </div>
                        <div class="kpi-val" id="sec-active-sessions">--</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">অনলাইন পারসোনাল এক্সেস টোকেন</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">জরুরি নিরাপত্তা ইনসিডেন্ট (Critical)</span>
                            <div class="kpi-icon-bubble" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-danger);" aria-hidden="true">🚨</div>
                        </div>
                        <div class="kpi-val" id="sec-critical-incidents" style="color: var(--accent-danger);">--</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">অবিলম্বে নিষ্পত্তি প্রয়োজন</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">উচ্চ-ঝুঁকিপূর্ণ ইনসিডেন্ট (High)</span>
                            <div class="kpi-icon-bubble" style="background: rgba(245, 158, 11, 0.1); color: var(--accent-warning);" aria-hidden="true">⚠️</div>
                        </div>
                        <div class="kpi-val" id="sec-high-incidents" style="color: var(--accent-warning);">--</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">পর্যালোচনাধীন ইভেন্ট</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">জরুরি সেশন টার্মিনেশন</span>
                            <div class="kpi-icon-bubble" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-danger);" aria-hidden="true">🛑</div>
                        </div>
                        <div style="display: flex; gap: 6px; margin-top: 6px;">
                            <input type="number" id="soc-revoke-user-id" class="table-search-input" placeholder="User ID" style="width: 90px;" aria-label="সেশন বাতিলের ইউজার আইডি">
                            <button class="btn-danger" onclick="revokeUserSessionsQuick()" style="font-size: 12px; padding: 6px 10px;">টোকেন বাতিল</button>
                        </div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">সকল ডিভাইস থেকে তাৎক্ষণিক লগআউট</span></div>
                    </div>
                </div>

                <!-- SOC 2.0: 7-Day Auth Failure Trend & Incident Playbooks -->
                <div class="analytics-grid" style="margin-bottom: 24px;">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <div class="chart-title">📉 ৭-দিনের ব্যর্থ লগইন প্রবণতা (Auth Failure Trends)</div>
                            <span style="font-size: 11.5px; color: var(--text-muted);">ক্রেডেনশিয়াল স্টাফিং নিরীক্ষণ</span>
                        </div>
                        <div class="svg-chart-container" id="soc-auth-trends-chart" style="height: 180px;">
                            <div style="color: var(--text-muted); font-size: 13px; margin: auto;">ট্রেন্ড লোড হচ্ছে...</div>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-card-header">
                            <div class="chart-title">📘 ইনসিডেন্ট রেসপন্স প্লেবুক (SOC Playbooks)</div>
                        </div>
                        <div id="soc-playbooks-container" style="max-height: 190px; overflow-y: auto; font-size: 12.5px;">
                            <div style="color: var(--text-muted); text-align: center; padding: 20px;">প্লেবুক লোড হচ্ছে...</div>
                        </div>
                    </div>
                </div>

                <!-- SOC Open Incidents Table -->
                <div class="data-card" style="margin-bottom: 24px;">
                    <div class="data-card-header">
                        <div class="chart-title">🚨 সক্রিয় নিরাপত্তা ইনসিডেন্টসমূহ (Unresolved Threat Events)</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="সক্রিয় নিরাপত্তা ইনসিডেন্ট টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>ইনসিডেন্ট আইডি</th>
                                    <th>ঘটনার ধরণ (Event Type)</th>
                                    <th>তীব্রতা (Severity)</th>
                                    <th>উৎস আইপি</th>
                                    <th>সনাক্তকরণের সময়</th>
                                    <th style="text-align: right;">তদন্ত ও নিষ্পত্তি</th>
                                </tr>
                            </thead>
                            <tbody id="sec-incidents-table-body">
                                <tr><td colspan="6" style="text-align: center; padding: 24px;">ইনসিডেন্ট লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- High Risk Brute Force IPs & Failed Logins Grid -->
                <div class="analytics-grid">
                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">🛑 ব্রুট-ফোর্স আক্রমণকারী আইপি (High-Risk IPs)</div>
                        </div>
                        <div class="table-responsive" role="region" aria-label="ব্রুট-ফোর্স আক্রমণকারী আইপি টেবিল" tabindex="0">
                            <table class="enterprise-table">
                                <thead>
                                    <tr>
                                        <th>আইপি এড্রেস</th>
                                        <th>ব্যর্থ চেষ্টা (২৪ ঘণ্টায়)</th>
                                        <th>ঝুঁকি স্তর</th>
                                    </tr>
                                </thead>
                                <tbody id="high-risk-ips-table-body">
                                    <tr><td colspan="3" style="text-align: center; padding: 20px;">কোনো উচ্চ-ঝুঁকিপূর্ণ আইপি সনাক্ত হয়নি।</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">সাম্প্রতিক ব্যর্থ লগইন প্রচেষ্টা (Failed Logins)</div>
                        </div>
                        <div class="table-responsive" role="region" aria-label="সাম্প্রতিক ব্যর্থ লগইন টেবিল" tabindex="0">
                            <table class="enterprise-table">
                                <thead>
                                    <tr>
                                        <th>ইমেইল / একাউন্ট</th>
                                        <th>আইপি এড্রেস</th>
                                        <th>তারিখ</th>
                                    </tr>
                                </thead>
                                <tbody id="failed-logins-table-body">
                                    <tr><td colspan="3" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 9: SUPPORT TICKETS                              -->
            <!-- ======================================================== -->
            <div id="sec-support" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🎫 সাপোর্ট টিকেট ও অভিযোগ ব্যবস্থাপনা</h1>
                        <div class="page-breadcrumb">ব্যবহারকারীদের টেকনিক্যাল সমস্যা ও অ্যাকাউন্টের অভিযোগ সমাধান</div>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-card-header">
                        <div class="table-filter-bar">
                            <select id="ticket-status-filter" class="filter-select" aria-label="টিকিট স্ট্যাটাস ফিল্টার" onchange="loadSupportTable(1)">
                                <option value="all">সকল টিকিট</option>
                                <option value="open">ওপেন (Open)</option>
                                <option value="in_progress">প্রক্রিয়াধীন (In Progress)</option>
                                <option value="resolved">সমাধানকৃত (Resolved)</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive" role="region" aria-label="সাপোর্ট টিকেট টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>টিকিট আইডি</th>
                                    <th>ব্যবহারকারী</th>
                                    <th>বিষয়</th>
                                    <th>ক্যাটাগরি</th>
                                    <th>অগ্রাধিকার</th>
                                    <th>স্ট্যাটাস</th>
                                    <th style="text-align: right;">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="tickets-table-body">
                                <tr><td colspan="7" style="text-align: center; padding: 30px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-pagination-footer" id="tickets-pagination-bar"></div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 10: MONETIZATION & ADS                          -->
            <!-- ======================================================== -->
            <div id="sec-monetization" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">💰 বিজ্ঞাপন ও ক্রিয়েটর ক্যাশআউট</h1>
                        <div class="page-breadcrumb">সেলফ-সার্ভ পোস্ট বুস্টিং আয় এবং ক্রিয়েটরদের ওয়ালেট উত্তোলন পর্যালোচনা</div>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-card-header">
                        <div class="chart-title">ক্রিয়েটর পে-আউট রিকোয়েস্ট (Payout Queue)</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="ক্রিয়েটর পে-আউট রিকোয়েস্ট টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>ক্রিয়েটর</th>
                                    <th>উত্তোলনকৃত কয়েন</th>
                                    <th>সমপরিমাণ টাকা (BDT)</th>
                                    <th>পেমেন্ট মেথড</th>
                                    <th>স্ট্যাটাস</th>
                                    <th style="text-align: right;">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="payouts-table-body">
                                <tr><td colspan="6" style="text-align: center; padding: 30px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 10.5: FEATURE FLAGS & PROGRESSIVE ROLLOUT (G)    -->
            <!-- ======================================================== -->
            <div id="sec-flags" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🚩 ফিচার ফ্ল্যাগ ও প্রগ্রেসিভ রোলআউট কন্ট্রোল</h1>
                        <div class="page-breadcrumb">ডিপ্লয়মেন্ট ছাড়াই নতুন ফিচার চালু/বন্ধ, পার্সেন্টেজ রোলআউট (০-১০০%) ও টার্গেটেড রোল রিলিজ</div>
                    </div>
                    <div class="action-button-group">
                        <button class="btn-secondary" onclick="loadFeatureFlags()">🔄 রিফ্রেশ</button>
                    </div>
                </div>

                <!-- Create New Feature Flag Card -->
                <div class="data-card" style="padding: 20px; margin-bottom: 24px;">
                    <div class="chart-title" style="margin-bottom: 14px;">➕ নতুন ফিচার ফ্ল্যাগ তৈরি করুন</div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="new-flag-key">ফ্ল্যাগ কী (Key)</label>
                            <input type="text" id="new-flag-key" class="form-control" placeholder="যেমন: ai_video_captions">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="new-flag-name">প্রদর্শনের নাম (Name)</label>
                            <input type="text" id="new-flag-name" class="form-control" placeholder="AI Video Captions">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="new-flag-rollout">রোলআউট % (0-100)</label>
                            <input type="number" id="new-flag-rollout" class="form-control" value="100" min="0" max="100">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="new-flag-desc">বিবরণ</label>
                            <input type="text" id="new-flag-desc" class="form-control" placeholder="ফিচারের সংক্ষিপ্ত উদ্দেশ্য...">
                        </div>
                        <div>
                            <button class="btn-primary" style="width: 100%; justify-content: center;" onclick="submitNewFeatureFlag()">🚩 ফ্ল্যাগ যুক্ত করুন</button>
                        </div>
                    </div>
                </div>

                <!-- Feature Flags Table -->
                <div class="data-card">
                    <div class="data-card-header">
                        <div class="chart-title">সক্রিয় ও কনফিগারকৃত ফিচার ফ্ল্যাগসমূহ</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="ফিচার ফ্ল্যাগ তালিকা টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>ফ্ল্যাগ কী (Key)</th>
                                    <th>নাম ও বিবরণ</th>
                                    <th>রোলআউট শতাংশ (%)</th>
                                    <th>স্ট্যাটাস</th>
                                    <th style="text-align: right;">নিয়ন্ত্রণ</th>
                                </tr>
                            </thead>
                            <tbody id="flags-table-body">
                                <tr><td colspan="5" style="text-align: center; padding: 24px;">ফিচার ফ্ল্যাগ লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 11: PLATFORM SETTINGS                           -->
            <!-- ======================================================== -->
            <div id="sec-settings" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">⚙️ প্ল্যাটফর্ম কনফিগারেশন সেটিংস</h1>
                        <div class="page-breadcrumb">সোশ্যাল নেটওয়ার্কের সার্বিক নীতিমালা, সাইট নাম, আপলোড লিমিট, রিভিশন হিস্ট্রি ও রোলব্যাক</div>
                    </div>
                    <div class="action-button-group">
                        <button class="btn-secondary" onclick="switchSection('flags')">🚩 ফিচার ফ্ল্যাগস</button>
                        <button class="btn-secondary" onclick="openRollbackModal()">⏪ পূর্ববর্তী মানে রোলব্যাক</button>
                        <button class="btn-primary" onclick="savePlatformSettings()">💾 সেটিংস সংরক্ষণ করুন</button>
                    </div>
                </div>

                <div class="data-card" style="padding: 24px;">
                    <form id="settings-form" onsubmit="event.preventDefault(); savePlatformSettings();">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label class="form-label" style="display: flex; justify-content: space-between;">
                                    <span>প্ল্যাটফর্মের নাম (Site Name)</span>
                                    <button type="button" onclick="viewSettingHistory('site_name')" style="background:none;border:none;color:var(--accent-primary);font-size:11px;cursor:pointer;font-weight:700;">📜 হিস্ট্রি</button>
                                </label>
                                <input type="text" id="set-site_name" class="form-control" value="Bondhoo - যোগাযোগ">
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="display: flex; justify-content: space-between;">
                                    <span>ট্যাগলাইন (Site Tagline)</span>
                                    <button type="button" onclick="viewSettingHistory('site_tagline')" style="background:none;border:none;color:var(--accent-primary);font-size:11px;cursor:pointer;font-weight:700;">📜 হিস্ট্রি</button>
                                </label>
                                <input type="text" id="set-site_tagline" class="form-control" value="সংযোগ, সম্পর্ক ও সম্ভাবনার ডিজিটাল আঙিনা">
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="display: flex; justify-content: space-between;">
                                    <span>সহায়তা ইমেইল (Contact Email)</span>
                                    <button type="button" onclick="viewSettingHistory('contact_email')" style="background:none;border:none;color:var(--accent-primary);font-size:11px;cursor:pointer;font-weight:700;">📜 হিস্ট্রি</button>
                                </label>
                                <input type="email" id="set-contact_email" class="form-control" value="support@jugajug.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="display: flex; justify-content: space-between;">
                                    <span>সর্বোচ্চ ভিডিও/ফাইল আপলোড আকার (MB)</span>
                                    <button type="button" onclick="viewSettingHistory('max_upload_size_mb')" style="background:none;border:none;color:var(--accent-primary);font-size:11px;cursor:pointer;font-weight:700;">📜 হিস্ট্রি</button>
                                </label>
                                <input type="number" id="set-max_upload_size_mb" class="form-control" value="100">
                            </div>
                            <div class="form-group">
                                <label class="form-label">নতুন ইউজার রেজিস্ট্রেশন উন্মুক্ত রাখুন</label>
                                <select id="set-registration_enabled" class="form-control">
                                    <option value="1">হ্যাঁ (উন্মুক্ত)</option>
                                    <option value="0">না (সাময়িকভাবে বন্ধ)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">রক্ষণাবেক্ষণ মোড (Maintenance Mode)</label>
                                <select id="set-maintenance_mode" class="form-control">
                                    <option value="0">স্বাভাবিক সচল (Live)</option>
                                    <option value="1">রক্ষণাবেক্ষণ মোড চালু (Under Maintenance)</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 12: DEVELOPER & SRE                             -->
            <!-- ======================================================== -->
            <div id="sec-developer" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🛠️ ডেভেলপার ও এসআরই ইনফ্রাস্ট্রাকচার</h1>
                        <div class="page-breadcrumb">ক্যাশ ক্লিয়ারিং, কিউ রিস্টার্ট, সার্চ রেইনডেক্স এবং ব্যাকআপ অপারেশন</div>
                    </div>
                </div>

                <div class="data-card" style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                        <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px;">
                            <h3 style="font-size: 15px; margin-bottom: 6px;">⚡ অ্যাপ্লিকেশন ক্যাশ ক্লিয়ার</h3>
                            <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 12px;">রেডিস ও সিস্টেম ক্যাশ মুছে ফেলে তাৎক্ষণিক রিলোড করুন।</p>
                            <button class="btn-secondary" onclick="triggerDevAction('clear_cache')">ক্যাশ ক্লিয়ার করুন</button>
                        </div>
                        <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px;">
                            <h3 style="font-size: 15px; margin-bottom: 6px;">🔄 কিউ ওয়ার্কার্স রিস্টার্ট</h3>
                            <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 12px;">হরাইজন ও ব্যাকগ্রাউন্ড ওয়ার্কার্সদের গ্রেসফুল রিস্টার্ট সিগন্যাল পাঠান।</p>
                            <button class="btn-secondary" onclick="triggerDevAction('restart_queue')">কিউ রিস্টার্ট করুন</button>
                        </div>
                        <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px;">
                            <h3 style="font-size: 15px; margin-bottom: 6px;">🔍 সার্চ ইনডেক্স রিফ্রেশ</h3>
                            <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 12px;">মেইলিসার্চ ও সার্চ ক্লাস্টারের ডাটা পুনরায় সিঙ্ক করুন।</p>
                            <button class="btn-secondary" onclick="triggerDevAction('reindex_search')">সার্চ রেইনডেক্স</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 13: AUDIT & COMPLIANCE (MODULE F)               -->
            <!-- ======================================================== -->
            <div id="sec-audit" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">📜 অডিট ট্রেইল, চেকপয়েন্ট ও কমপ্লায়েন্স লগ</h1>
                        <div class="page-breadcrumb">ক্রিপ্টোগ্রাফিক SHA-256 HMAC চেইনযুক্ত অকাট্য প্রশাসনিক পদক্ষেপ, সাইন্ড চেকপয়েন্ট ও স্ট্রিমিং এক্সপোর্ট</div>
                    </div>
                    <div class="action-button-group">
                        <button class="btn-secondary" onclick="createAuditCheckpoint()">🔏 নতুন চেকপয়েন্ট সিল করুন</button>
                        <button class="btn-secondary" onclick="exportAuditLogsCsv()">📥 অডিট CSV এক্সপোর্ট</button>
                        <button class="btn-primary" onclick="verifyAuditIntegrity()">🔐 ক্রিপ্টোগ্রাফিক চেইন যাচাই</button>
                    </div>
                </div>

                <div id="audit-integrity-banner" role="status" aria-live="polite" style="display: none; padding: 12px 16px; margin-bottom: 16px; border-radius: var(--radius-md); font-weight: 600; font-size: 13px;"></div>

                <div class="data-card">
                    <div class="data-card-header">
                        <div class="chart-title">অকাট্য প্রশাসনিক অডিট লগ (Tamper-Evident Ledger)</div>
                        <div class="table-filter-bar">
                            <input type="text" id="audit-action-filter" class="table-search-input" aria-label="অডিট অ্যাকশন অনুসন্ধান" placeholder="অ্যাকশন দ্বারা ফিল্টার (যেমন: admin_user)..." onkeyup="if(event.key==='Enter')loadAuditLogs(1)">
                            <button class="btn-secondary" onclick="loadAuditLogs(1)">ফিল্টার</button>
                        </div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="অডিট লগ টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>প্রশাসক (Actor)</th>
                                    <th>পদক্ষেপ (Action)</th>
                                    <th>এনটিটি</th>
                                    <th>আইপি এড্রেস</th>
                                    <th>তারিখ ও সময়</th>
                                    <th style="text-align: right;">পরিদর্শন</th>
                                </tr>
                            </thead>
                            <tbody id="audit-table-body">
                                <tr><td colspan="6" style="text-align: center; padding: 30px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-pagination-footer" id="audit-pagination-bar"></div>
                </div>

                <!-- Signed Cryptographic Audit Checkpoints Table -->
                <div class="data-card">
                    <div class="data-card-header">
                        <div class="chart-title">🔏 সাইন্ড ক্রিপ্টোগ্রাফিক অডিট চেকপয়েন্টসমূহ (Signed Merkle/HMAC Checkpoints)</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="ক্রিপ্টোগ্রাফিক অডিট চেকপয়েন্ট টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>চেকপয়েন্ট #</th>
                                    <th>লগ রেঞ্জ (ID)</th>
                                    <th>মোট ইভেন্ট</th>
                                    <th>টার্মিনাল হ্যাশ</th>
                                    <th>চেকপয়েন্ট সিগনেচার</th>
                                    <th>সিলকারী প্রশাসক ও সময়</th>
                                </tr>
                            </thead>
                            <tbody id="audit-checkpoints-table-body">
                                <tr><td colspan="6" style="text-align: center; padding: 20px;">চেকপয়েন্ট লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 14: TRUST & SAFETY / ABUSE DETECTION             -->
            <!-- ======================================================== -->
            <div id="sec-trust_safety" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🛡️ ট্রাস্ট, সেফটি ও অপব্যবহার সনাক্তকরণ ইঞ্জিন</h1>
                        <div class="page-breadcrumb">মাল্টি-সিগন্যাল রিস্ক স্কোরিং, বট/স্প্যাম প্যাটার্ন ট্র্যাকিং ও ব্যবহারকারী সীমাবদ্ধতা নিয়ন্ত্রণ</div>
                    </div>
                </div>

                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">উচ্চ-ঝুঁকিপূর্ণ একাউন্ট (Risk >= 75)</span>
                            <div class="kpi-icon-bubble" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-danger);">⚠️</div>
                        </div>
                        <div class="kpi-val" id="ts-high-risk-count">0</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">তদন্ত ও পর্যালোচনা প্রয়োজন</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">সক্রিয় সীমাবদ্ধতা (Active Restrictions)</span>
                            <div class="kpi-icon-bubble" style="background: rgba(245, 158, 11, 0.1); color: var(--accent-warning);">🔒</div>
                        </div>
                        <div class="kpi-val" id="ts-active-restrictions-count">--</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">পোস্টিং/কমেন্ট ব্লক বা ব্যান</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">ফলস-পজিটিভ হার (AI Accuracy)</span>
                            <div class="kpi-icon-bubble" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-success);">🎯</div>
                        </div>
                        <div class="kpi-val" id="ts-false-positive-rate">0.0%</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">অভারটার্ন হওয়া রিপোর্টের অনুপাত</span></div>
                    </div>
                </div>

                <!-- User Risk Quick Evaluation Bar -->
                <div class="data-card" style="margin-bottom: 24px;">
                    <div class="data-card-header">
                        <div class="chart-title">🔍 ব্যবহারকারী নিরাপত্তা ঝুঁকি ও ডসিয়ার মূল্যায়ন</div>
                    </div>
                    <div style="padding: 16px 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <input type="number" id="ts-eval-user-id" class="form-control" style="max-width: 260px;" placeholder="ব্যবহারকারী আইডি (User ID) লিখুন...">
                        <button class="btn-primary" onclick="evaluateUserRiskQuick()">রিস্ক স্কোর ও ডসিয়ার মূল্যায়ন করুন</button>
                    </div>
                    <div id="ts-eval-result" style="padding: 0 20px 20px 20px; display: none;"></div>
                </div>

                <!-- Active Restrictions Table -->
                <div class="data-card">
                    <div class="data-card-header">
                        <div class="chart-title">🛑 অপব্যবহারের কারণে কার্যকর সীমাবদ্ধতাসমূহ (Enforced Restrictions)</div>
                        <div class="table-filter-bar">
                            <button class="btn-secondary" onclick="loadTrustSafety()">🔄 রিফ্রেশ তালিকা</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>সীমাবদ্ধতা আইডি</th>
                                    <th>ব্যবহারকারী</th>
                                    <th>সীমাবদ্ধতার ধরণ</th>
                                    <th>কারণ ও ব্যাখ্যা</th>
                                    <th>মেয়াদোত্তীর্ণের তারিখ</th>
                                    <th>স্ট্যাটাস</th>
                                    <th style="text-align: right;">প্রশাসনিক অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="ts-restrictions-table-body">
                                <tr><td colspan="7" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 15: OPERATIONS ALERTS CENTER                     -->
            <!-- ======================================================== -->
            <div id="sec-alerts" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🔔 সেন্ট্রালাইজড অপারেশনস অ্যালার্ট ও নোটিফিকেশন সেন্টার</h1>
                        <div class="page-breadcrumb">নিরাপত্তা হুমকি, সিস্টেম অস্বাভাবিকতা, মডারেশন স্পাইক ও ব্যাকগ্রাউন্ড ব্যর্থতা নিয়ন্ত্রণ</div>
                    </div>
                    <div class="action-button-group">
                        <button class="btn-secondary" onclick="loadAlertsCenter()">🔄 রিফ্রেশ</button>
                        <button class="btn-primary" onclick="markAllAlertsAsRead()">✓ সব পঠিত করুন</button>
                    </div>
                </div>

                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">মোট অ্যালার্ট (Total)</span>
                            <div class="kpi-icon-bubble">📢</div>
                        </div>
                        <div class="kpi-val" id="ops-alert-total">--</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">জরুরি অপঠিত (Critical Unread)</span>
                            <div class="kpi-icon-bubble" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-danger);">🚨</div>
                        </div>
                        <div class="kpi-val" id="ops-alert-critical" style="color: var(--accent-danger);">--</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">অ্যাকনলেজড (Acknowledged)</span>
                            <div class="kpi-icon-bubble" style="background: rgba(14, 165, 233, 0.1); color: var(--accent-info);">👁️</div>
                        </div>
                        <div class="kpi-val" id="ops-alert-acked">--</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">নিষ্পত্তিকৃত (Resolved)</span>
                            <div class="kpi-icon-bubble" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-success);">✓</div>
                        </div>
                        <div class="kpi-val" id="ops-alert-resolved" style="color: var(--accent-success);">--</div>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-card-header">
                        <div class="chart-title">🚨 রিয়েল-টাইম সিস্টেম অ্যালার্ট তালিকা</div>
                        <div class="table-filter-bar">
                            <select id="ops-alert-severity-filter" class="filter-select" onchange="loadAlertsCenter(1)">
                                <option value="all">সকল তীব্রতা (All Severity)</option>
                                <option value="critical">Critical (জরুরি)</option>
                                <option value="danger">Danger (বিপদজনক)</option>
                                <option value="warning">Warning (সতর্কতা)</option>
                                <option value="info">Info (তথ্যমূলক)</option>
                            </select>
                            <select id="ops-alert-status-filter" class="filter-select" onchange="loadAlertsCenter(1)">
                                <option value="all">সকল স্ট্যাটাস</option>
                                <option value="unread">অপঠিত (Unread)</option>
                                <option value="acknowledged">অ্যাকনলেজড</option>
                                <option value="resolved">নিষ্পত্তিকৃত</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>তীব্রতা</th>
                                    <th>শিরোনাম ও বিবরণ</th>
                                    <th>ক্যাটাগরি</th>
                                    <th>হিট কাউন্ট</th>
                                    <th>সময়</th>
                                    <th>স্ট্যাটাস</th>
                                    <th style="text-align: right;">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="ops-alerts-table-body">
                                <tr><td colspan="7" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-pagination-footer" id="ops-alerts-pagination"></div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 16: BACKGROUND JOBS & QUEUE CONTROL              -->
            <!-- ======================================================== -->
            <div id="sec-jobs" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">⚡ ব্যাকগ্রাউন্ড জবস, কিউ ও শিডিউলার কন্ট্রোল সেন্টার</h1>
                        <div class="page-breadcrumb">অ্যাসিনক্রোনাস টাস্ক এক্সিকিউশন, ফেইল্ড জবস ইন্স্পেকশন, রিট্রাই ও ক্রন জবস নিরীক্ষণ</div>
                    </div>
                    <div class="action-button-group">
                        <button class="btn-secondary" onclick="loadJobsCenter()">🔄 রিফ্রেশ</button>
                        <button class="btn-primary" onclick="retryFailedJob('all')">↻ সমস্ত ব্যর্থ জব পুনরায় চেষ্টা করুন</button>
                    </div>
                </div>

                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">কিউ ড্রাইভার (Queue Driver)</span>
                            <div class="kpi-icon-bubble">⚙️</div>
                        </div>
                        <div class="kpi-val" id="jobs-driver-val">--</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">পেন্ডিং জবস (Pending Backlog)</span>
                            <div class="kpi-icon-bubble">⏳</div>
                        </div>
                        <div class="kpi-val" id="jobs-pending-val">0</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">ব্যর্থ জবস (Failed Jobs)</span>
                            <div class="kpi-icon-bubble" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-danger);">❌</div>
                        </div>
                        <div class="kpi-val" id="jobs-failed-val" style="color: var(--accent-danger);">0</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">কিউ হেলথ স্ট্যাটাস</span>
                            <div class="kpi-icon-bubble" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-success);">✓</div>
                        </div>
                        <div class="kpi-val" id="jobs-health-status" style="color: var(--accent-success);">Healthy</div>
                    </div>
                </div>

                <!-- Failed Jobs Table -->
                <div class="data-card" style="margin-bottom: 24px;">
                    <div class="data-card-header">
                        <div class="chart-title">❌ ব্যর্থ জব তালিকা (Failed Queue Jobs) — সংবেদনশীল তথ্য গোপনকৃত</div>
                    </div>
                    <div class="table-responsive">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>জব আইডি</th>
                                    <th>কানেকশন / কিউ</th>
                                    <th>ব্যর্থতার সময়</th>
                                    <th>এক্সেপশন ক্লাস</th>
                                    <th>এক্সেপশন মেসেজ</th>
                                    <th style="text-align: right;">নিরাপদ অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="failed-jobs-table-body">
                                <tr><td colspan="6" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Scheduled Tasks Table -->
                <div class="data-card">
                    <div class="data-card-header">
                        <div class="chart-title">⏱️ প্ল্যাটফর্ম শিডিউলড টাস্ক ও অটোমেশন (Scheduled Tasks)</div>
                    </div>
                    <div class="table-responsive">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>কমান্ড (Artisan Command)</th>
                                    <th>ক্রন এক্সপ্রেশন</th>
                                    <th>ফ্রিকোয়েন্সি / বিবরণ</th>
                                    <th>পরবর্তী রান টাইম</th>
                                    <th>স্ট্যাটাস</th>
                                </tr>
                            </thead>
                            <tbody id="scheduled-tasks-table-body">
                                <tr><td colspan="5" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 17: SYSTEM & INFRASTRUCTURE HEALTH               -->
            <!-- ======================================================== -->
            <div id="sec-health" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🩺 সিস্টেম, ডাটাবেজ ও ক্লাউড ইনফ্রাস্ট্রাকচার স্বাস্থ্য</h1>
                        <div class="page-breadcrumb">লেটেন্সি বেঞ্চমার্ক, ক্যাশ এভেইলেবিলিটি, মেমোরি কনজাম্পশন ও স্টোরেজ অবস্থা</div>
                    </div>
                    <div class="action-button-group">
                        <button class="btn-primary" onclick="loadHealthCenter(true)">⚡ ডায়াগনস্টিক রিফ্রেশ করুন</button>
                    </div>
                </div>

                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">ডাটাবেজ লেটেন্সি (DB Latency)</span>
                            <div class="kpi-icon-bubble">🗄️</div>
                        </div>
                        <div class="kpi-val" id="health-db-latency">-- ms</div>
                        <div class="kpi-footer"><span id="health-db-status" class="badge-status badge-active">Active</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">ক্যাশ লেটেন্সি (Cache Latency)</span>
                            <div class="kpi-icon-bubble">⚡</div>
                        </div>
                        <div class="kpi-val" id="health-cache-latency">-- ms</div>
                        <div class="kpi-footer"><span id="health-cache-status" class="badge-status badge-active">Active</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">মেমোরি ব্যবহার (PHP Memory)</span>
                            <div class="kpi-icon-bubble">🧠</div>
                        </div>
                        <div class="kpi-val" id="health-memory-val">-- MB</div>
                        <div class="kpi-footer"><span style="color: var(--text-muted);">রিয়েলটাইম বরাদ্দকৃত মেমোরি</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">সামগ্রিক সিস্টেম স্বাস্থ্য</span>
                            <div class="kpi-icon-bubble" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-success);">❤️</div>
                        </div>
                        <div class="kpi-val" id="health-overall-status" style="color: var(--accent-success);">HEALTHY</div>
                        <div class="kpi-footer"><span id="health-freshness-info" style="color: var(--text-muted);">সদ্য যাচাইকৃত</span></div>
                    </div>
                </div>

                <div class="analytics-grid">
                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">🖥️ পরিবেশ ও সার্ভার স্পেসিফিকেশন</div>
                        </div>
                        <div style="padding: 20px;">
                            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                                <span style="font-weight: 600; color: var(--text-secondary);">পিএইচপি সংস্করণ (PHP)</span>
                                <span id="health-php-ver" style="font-weight: 700;">{{ PHP_VERSION }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                                <span style="font-weight: 600; color: var(--text-secondary);">লারাভেল ফ্রেমওয়ার্ক (Laravel)</span>
                                <span id="health-laravel-ver" style="font-weight: 700;">{{ app()->version() }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                                <span style="font-weight: 600; color: var(--text-secondary);">এনভায়রনমেন্ট (APP_ENV)</span>
                                <span id="health-env-ver" style="font-weight: 700;">{{ config('app.env') }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                                <span style="font-weight: 600; color: var(--text-secondary);">সার্ভার সময় (UTC)</span>
                                <span id="health-server-time" style="font-weight: 700;">{{ now()->toDateTimeString() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">💾 স্টোরেজ ও পাবলিক আপলোড অবস্থা</div>
                        </div>
                        <div style="padding: 20px;">
                            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                                <span style="font-weight: 600; color: var(--text-secondary);">Storage ডিরেক্টরি পারমিশন</span>
                                <span id="health-storage-writable" class="badge-status badge-active">Writable</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                                <span style="font-weight: 600; color: var(--text-secondary);">Public Uploads পারমিশন</span>
                                <span id="health-public-writable" class="badge-status badge-active">Writable</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                                <span style="font-weight: 600; color: var(--text-secondary);">ডিস্ক ব্যবহার অনুপাত (Disk Usage)</span>
                                <span id="health-disk-usage" style="font-weight: 700;">স্বাভাবিক</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 18: DATA GOVERNANCE & PRIVACY (GDPR)             -->
            <!-- ======================================================== -->
            <div id="sec-governance" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🏛️ ডাটা গভর্ন্যান্স, প্রাইভেসি ও রিটেনশন সেন্টার</h1>
                        <div class="page-breadcrumb">ডাটা ক্যাটাগরি ইনভেন্টরি, GDPR/DSAR প্রাইভেসি রিকোয়েস্ট, লিগ্যাল হোল্ড সুরক্ষা এবং মেয়াদোত্তীর্ণ লগ অপসারণ</div>
                    </div>
                    <div class="action-button-group">
                        <button class="btn-secondary" onclick="enforceDataRetention(true)">🔍 সিমুলেশন (Dry Run)</button>
                        <button class="btn-danger" onclick="enforceDataRetention(false)">🧹 রিটেনশন কার্যকর করুন</button>
                    </div>
                </div>

                <!-- Data Category Inventory Cards -->
                <div class="kpi-grid" id="gov-inventory-grid" style="margin-bottom: 20px;">
                    <div class="kpi-card"><div style="color: var(--text-muted); font-size: 13px;">ডাটা ইনভেন্টরি লোড হচ্ছে...</div></div>
                </div>

                <!-- Quick Legal Hold & Privacy Request Creation Bar -->
                <div class="analytics-grid" style="margin-bottom: 24px;">
                    <div class="data-card" style="padding: 18px 20px; margin-bottom: 0;">
                        <div class="chart-title" style="margin-bottom: 10px;">⚖️ অ্যাকাউন্ট লিগ্যাল হোল্ড (Legal Hold Protection)</div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                            <input type="number" id="gov-legal-hold-user-id" class="table-search-input" placeholder="ইউজার আইডি" style="width: 120px;" aria-label="লিগ্যাল হোল্ড ইউজার আইডি">
                            <input type="text" id="gov-legal-hold-reason" class="table-search-input" placeholder="আদালত/তদন্ত আদেশের সূত্র..." style="flex: 1; min-width: 180px;" aria-label="লিগ্যাল হোল্ড কারণ">
                            <button class="btn-primary" onclick="submitQuickLegalHold(true)">🔒 হোল্ড প্রয়োগ</button>
                            <button class="btn-secondary" onclick="submitQuickLegalHold(false)">🔓 প্রত্যাহার</button>
                        </div>
                    </div>

                    <div class="data-card" style="padding: 18px 20px; margin-bottom: 0;">
                        <div class="chart-title" style="margin-bottom: 10px;">📝 নতুন GDPR / DSAR প্রাইভেসি রিকোয়েস্ট নিবন্ধন</div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                            <input type="number" id="gov-new-req-user-id" class="table-search-input" placeholder="ইউজার আইডি" style="width: 110px;" aria-label="প্রাইভেসি রিকোয়েস্ট ইউজার আইডি">
                            <select id="gov-new-req-type" class="filter-select" aria-label="প্রাইভেসি রিকোয়েস্ট ধরণ">
                                <option value="export">ডাটা এক্সপোর্ট (Export)</option>
                                <option value="erasure">অ্যাকাউন্ট মুছে ফেলা (Erasure)</option>
                                <option value="rectification">তথ্য সংশোধন (Rectification)</option>
                            </select>
                            <button class="btn-primary" onclick="submitNewPrivacyRequest()">➕ রিকোয়েস্ট তৈরি করুন</button>
                        </div>
                    </div>
                </div>

                <!-- Retention Policies Table -->
                <div class="data-card" style="margin-bottom: 24px;">
                    <div class="data-card-header">
                        <div class="chart-title">📋 কনফিগারেবল ডাটা রিটেনশন নীতিসমূহ (Retention Policies)</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="ডাটা রিটেনশন নীতি টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>ডাটা ক্যাটাগরি</th>
                                    <th>নীতির নাম</th>
                                    <th>সংরক্ষণ মেয়াদ (দিন)</th>
                                    <th>অ্যাকশন</th>
                                    <th>সক্রিয়তা</th>
                                    <th>সর্বশেষ রান</th>
                                </tr>
                            </thead>
                            <tbody id="retention-policies-table-body">
                                <tr><td colspan="6" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Privacy & GDPR Requests Table -->
                <div class="data-card">
                    <div class="data-card-header">
                        <div class="chart-title">⚖️ ব্যবহারকারী প্রাইভেসি ও ডাটা রিকোয়েস্ট (GDPR Requests)</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="প্রাইভেসি ও GDPR রিকোয়েস্ট টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>রিকোয়েস্ট ট্র্যাকিং নং</th>
                                    <th>ব্যবহারকারী</th>
                                    <th>রিকোয়েস্ট টাইপ</th>
                                    <th>আবেদনের কারণ</th>
                                    <th>তারিখ</th>
                                    <th>স্ট্যাটাস</th>
                                    <th style="text-align: right;">নিষ্পত্তি</th>
                                </tr>
                            </thead>
                            <tbody id="privacy-requests-table-body">
                                <tr><td colspan="7" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 19: GLOBAL INVESTIGATION WORKSPACE               -->
            <!-- ======================================================== -->
            <div id="sec-search" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🔍 গ্লোবাল সার্চ ও প্রশাসনিক তদন্ত ওয়ার্কস্পেস</h1>
                        <div class="page-breadcrumb">মাল্টি-এনটিটি ক্রস-সার্চ, অডিট ক্রিপ্টোগ্রাফিক ম্যাচিং এবং একাউন্ট ডসিয়ার ইনভেস্টিগেশন</div>
                    </div>
                </div>

                <div class="data-card" style="margin-bottom: 24px;">
                    <div style="padding: 24px;">
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <input type="text" id="search-workspace-input" class="form-control" aria-label="গ্লোবাল তদন্ত অনুসন্ধান" style="font-size: 16px; padding: 12px 16px;" placeholder="নাম, ইমেইল, ইউজারনেম, হ্যাশট্যাগ, রিপোর্ট বা টিকিট টাইপ করে এন্টার চাপুন..." onkeyup="if(event.key === 'Enter') executeGlobalWorkspaceSearch();">
                            <button class="btn-primary" style="padding: 12px 24px;" onclick="executeGlobalWorkspaceSearch()">অনুসন্ধান করুন</button>
                        </div>
                    </div>
                </div>

                <div id="search-workspace-results">
                    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                        যেকোনো ব্যবহারকারী, পোস্ট, গ্রুপ, রিপোর্ট বা অডিট ইভেন্ট অনুসন্ধান করতে উপরে সার্চ করুন।
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION 20: PRODUCTION READINESS & DISASTER RECOVERY     -->
            <!-- ======================================================== -->
            <div id="sec-readiness" class="admin-section">
                <div class="page-header-bar">
                    <div>
                        <h1 class="page-title">🚀 প্রোডাকশন প্রস্তুতি, ডিজাস্টার রিকভারি ও নিরাপত্তা হার্ডেনিং (Phase 5)</h1>
                        <div class="page-breadcrumb">কনফিগারেশন অডিট, AES-256-CBC+HMAC ব্যাকআপ ইনভেন্টরি, আইসোলেটেড রিস্টোর ড্রিল এবং RPO/RTO কমপ্লায়েন্স</div>
                    </div>
                    <div class="action-button-group">
                        <button class="btn-secondary" onclick="loadProductionReadiness()">🔄 রিফ্রেশ</button>
                        <button class="btn-secondary" onclick="createDrBackup('db')">💾 নতুন এনক্রিপ্টেড ব্যাকআপ</button>
                        <button class="btn-primary" onclick="runFreshReadinessAudit()">🛡️ পূর্ণাঙ্গ প্রস্তুতি অডিট চালান</button>
                    </div>
                </div>

                <!-- Readiness & DR KPI Summary Grid -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">প্রোডাকশন প্রস্তুতি স্কোর</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">🎯</div>
                        </div>
                        <div class="kpi-val" id="readiness-score-val">--/100</div>
                        <div class="kpi-footer"><span id="readiness-overall-badge" class="badge-status badge-pending">Checking</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">ক্রিটিক্যাল ব্লকার ও ওয়ার্নিং</span>
                            <div class="kpi-icon-bubble" style="background: rgba(239, 68, 68, 0.12); color: var(--accent-danger);" aria-hidden="true">🚨</div>
                        </div>
                        <div class="kpi-val" id="readiness-blockers-val">--</div>
                        <div class="kpi-footer"><span id="readiness-warnings-text" style="color: var(--text-muted);">যাচাই করা হচ্ছে...</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">ডিজাস্টার রিকভারি (DR) স্ট্যাটাস</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">🛡️</div>
                        </div>
                        <div class="kpi-val" id="dr-ready-status-val">--</div>
                        <div class="kpi-footer"><span id="dr-rpo-rto-summary" style="color: var(--text-muted);">RPO / RTO যাচাই</span></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-header">
                            <span class="kpi-title">সর্বশেষ আইসোলেটেড রিস্টোর ড্রিল</span>
                            <div class="kpi-icon-bubble" aria-hidden="true">🧪</div>
                        </div>
                        <div class="kpi-val" id="dr-latest-drill-val">--</div>
                        <div class="kpi-footer"><span id="dr-latest-drill-meta" style="color: var(--text-muted);">বিচ্ছিন্ন ডাটাবেজ যাচাই</span></div>
                    </div>
                </div>

                <!-- Configurable RPO / RTO Objectives Card -->
                <div class="data-card" style="margin-bottom: 24px; padding: 18px 20px;">
                    <div class="chart-title" style="margin-bottom: 12px;">⏱️ রিকভারি অবজেক্টিভ কনফিগারেশন (RPO / RTO & Retention Policy)</div>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
                        <div>
                            <label style="display: block; font-size: 11.5px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;" for="dr-rpo-input">RPO লক্ষ্যমাত্রা (মিনিট)</label>
                            <input type="number" id="dr-rpo-input" class="table-search-input" style="width: 130px;" value="60" min="5">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11.5px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;" for="dr-rto-input">RTO লক্ষ্যমাত্রা (মিনিট)</label>
                            <input type="number" id="dr-rto-input" class="table-search-input" style="width: 130px;" value="30" min="1">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11.5px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;" for="dr-retention-input">ব্যাকআপ সংরক্ষণ (দিন)</label>
                            <input type="number" id="dr-retention-input" class="table-search-input" style="width: 130px;" value="30" min="1">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11.5px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;" for="dr-drill-freq-input">রিস্টোর ড্রিল ব্যবধান (দিন)</label>
                            <input type="number" id="dr-drill-freq-input" class="table-search-input" style="width: 140px;" value="7" min="1">
                        </div>
                        <button class="btn-primary" onclick="saveDrObjectives()">💾 লক্ষ্যমাত্রা সংরক্ষণ করুন</button>
                    </div>
                </div>

                <!-- Production Configuration Safety Checks Table -->
                <div class="data-card" style="margin-bottom: 24px;">
                    <div class="data-card-header">
                        <div class="chart-title">🔍 প্রোডাকশন কনফিগারেশন ও নিরাপত্তা যাচাই তালিকা (15 Live Checks — Zero Hardcoded Status)</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="প্রোডাকশন কনফিগারেশন যাচাই টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>চেক আইডি ও ক্যাটাগরি</th>
                                    <th>শিরোনাম</th>
                                    <th>তীব্রতা</th>
                                    <th>স্ট্যাটাস</th>
                                    <th>পর্যবেক্ষণ বিবরণ</th>
                                    <th>প্রতিকার নির্দেশনা (Remediation)</th>
                                </tr>
                            </thead>
                            <tbody id="readiness-checks-table-body">
                                <tr><td colspan="6" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Backup Inventory & Restore Verification Table -->
                <div class="data-card" style="margin-bottom: 24px;">
                    <div class="data-card-header">
                        <div class="chart-title">💾 এনক্রিপ্টেড ব্যাকআপ ইনভেন্টরি ও আইসোলেটেড রিস্টোর ড্রিল কন্ট্রোল</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="ব্যাকআপ ইনভেন্টরি টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>ব্যাকআপ আইডি ও ফাইল</th>
                                    <th>ধরণ ও ইঞ্জিন</th>
                                    <th>সাইজ ও সাইফার</th>
                                    <th>SHA-256 চেকসাম যাচাই</th>
                                    <th>রিস্টোর ড্রিল স্ট্যাটাস</th>
                                    <th>সময়কাল</th>
                                    <th style="text-align: right;">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="dr-backups-table-body">
                                <tr><td colspan="7" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Isolated Restore Drills History & Deployment Checklist -->
                <div class="analytics-grid">
                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">🧪 সাম্প্রতিক আইসোলেটেড রিস্টোর ড্রিল ইতিহাস</div>
                        </div>
                        <div class="table-responsive" role="region" aria-label="রিস্টোর ড্রিল ইতিহাস টেবিল" tabindex="0">
                            <table class="enterprise-table">
                                <thead>
                                    <tr>
                                        <th>ড্রিল আইডি</th>
                                        <th>ব্যাকআপ</th>
                                        <th>টেবিল / রেকর্ড</th>
                                        <th>সময় (ms)</th>
                                        <th>ক্লিনআপ</th>
                                        <th>ফলাফল</th>
                                    </tr>
                                </thead>
                                <tbody id="dr-drills-table-body">
                                    <tr><td colspan="6" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">📋 প্রোডাকশন ডেপ্লয়মেন্ট ও রোলব্যাক চেকলিস্ট</div>
                        </div>
                        <div id="readiness-deployment-checklist" style="padding: 16px 20px; font-size: 12.5px;">
                            লোড হচ্ছে...
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 21: ZERO-TRUST SECURITY, HIGH AVAILABILITY, COMPLIANCE & AUTONOMOUS OPS (PHASE 6) -->
            <div id="sec-zerotrust" class="admin-section" role="region" aria-label="জিরো-ট্রাস্ট সিকিউরিটি, হাই অ্যাভেইলেবিলিটি, কমপ্লায়েন্স ও অটোনোমাস অপারেশনস">
                <div class="section-header-bar">
                    <div>
                        <h2 class="section-main-title">🛡️ জিরো-ট্রাস্ট সিকিউরিটি, রেজিলিয়েন্স ও অটোনোমাস অপারেশনস (Phase 6)</h2>
                        <p class="section-sub-desc">পলিসি-চালিত জিরো-ট্রাস্ট, অ্যাক্টিভ সেশন ইনভেন্টরি, থ্রেট ডিটেকশন, ডুয়াল-অ্যাপ্রুভাল রেসপন্স, কমপ্লায়েন্স এভিডেন্স, কনফিগ ড্রিফট, SLO এবং CycloneDX SBOM গভর্ন্যান্স</p>
                    </div>
                    <div class="section-actions">
                        <button class="btn-secondary" onclick="loadZeroTrustWorkspace()">🔄 রিফ্রেশ</button>
                        <button class="btn-secondary" onclick="runPhase6ThreatEvaluation()">⚡ থ্রেট রুল মূল্যায়ন</button>
                        <button class="btn-secondary" onclick="runPhase6KeyRotationDrill()">🔑 কী রোটেশন ড্রিল (Gate D)</button>
                        <button class="btn-secondary" onclick="runPhase6OffsiteVerify()">☁️ অফ-সাইট যাচাই (Gate C)</button>
                        <button class="btn-primary" onclick="exportPhase6Sbom()">📦 CycloneDX SBOM এক্সপোর্ট</button>
                    </div>
                </div>

                <!-- Phase 6 Top KPI Row -->
                <div class="kpi-grid" style="margin-bottom: 24px;">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">জিরো-ট্রাস্ট পলিসি ইঞ্জিন</span>
                            <div class="kpi-icon" style="background: rgba(79, 70, 229, 0.12); color: #4f46e5;">🛡️</div>
                        </div>
                        <div class="kpi-value" id="zt-active-policies-val">--</div>
                        <div class="kpi-trend">
                            <span id="zt-decisions-24h-text">২৪ ঘণ্টায় অনুমোদন/প্রত্যাখ্যান লোড হচ্ছে...</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">থ্রেট ডিটেকশন ও ডুয়াল-অ্যাপ্রুভাল</span>
                            <div class="kpi-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">🚨</div>
                        </div>
                        <div class="kpi-value" id="zt-open-detections-val">--</div>
                        <div class="kpi-trend">
                            <span id="zt-pending-approvals-text">মানব অনুমোদনের অপেক্ষায়: 0</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">কমপ্লায়েন্স ও কনফিগ ড্রিফট</span>
                            <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">📋</div>
                        </div>
                        <div class="kpi-value" id="zt-compliance-effective-val">--</div>
                        <div class="kpi-trend">
                            <span id="zt-config-drift-text">কনফিগ ড্রিফট: --</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">SLO, HA টপোলজি ও রিলিজ গেট</span>
                            <div class="kpi-icon" style="background: rgba(14, 165, 233, 0.12); color: #0ea5e9;">📈</div>
                        </div>
                        <div class="kpi-value" id="zt-slo-meeting-val">--</div>
                        <div class="kpi-trend">
                            <span id="zt-release-gate-status-text">রিলিজ গেট স্ট্যাটাস লোড হচ্ছে...</span>
                        </div>
                    </div>
                </div>

                <!-- 1. Zero-Trust Policy Matrix & Access Evaluator -->
                <div class="data-card" style="margin-bottom: 24px;">
                    <div class="data-card-header">
                        <div class="chart-title">🔐 জিরো-ট্রাস্ট অ্যাক্সেস পলিসি ক্যাটালগ (Deny-by-Default & Versioned History)</div>
                    </div>
                    <div class="table-responsive" role="region" aria-label="জিরো-ট্রাস্ট পলিসি টেবিল" tabindex="0">
                        <table class="enterprise-table">
                            <thead>
                                <tr>
                                    <th>পলিসি কোড ও সংস্করণ</th>
                                    <th>অপারেশন কী ও নাম</th>
                                    <th>ঝুঁকি ও সর্বোচ্চ স্কোর</th>
                                    <th>MFA / JIT / Dual Approval</th>
                                    <th>স্ট্যাটাস</th>
                                    <th style="text-align: right;">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="zt-policies-table-body">
                                <tr><td colspan="6" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Active Session Security Inventory & Automated Response Approval Queue -->
                <div class="analytics-grid" style="margin-bottom: 24px;">
                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">💻 সেশন সিকিউরিটি ইনভেন্টরি (নিরাপদ হ্যাশড হ্যান্ডেল — কাঁচা টোকেন গোপনকৃত)</div>
                        </div>
                        <div class="table-responsive" role="region" aria-label="সক্রিয় সেশন ইনভেন্টরি" tabindex="0">
                            <table class="enterprise-table">
                                <thead>
                                    <tr>
                                        <th>হ্যান্ডেল ও চ্যানেল</th>
                                        <th>ব্যবহারকারী ও রোল</th>
                                        <th>ডিভাইস ও আইপি</th>
                                        <th>সংকেত</th>
                                        <th style="text-align: right;">বাতিল</th>
                                    </tr>
                                </thead>
                                <tbody id="zt-sessions-table-body">
                                    <tr><td colspan="5" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">⏳ স্বয়ংক্রিয় রেসপন্স ও ডুয়াল-অ্যাপ্রুভাল কিউ (Self-Approval নিষিদ্ধ)</div>
                        </div>
                        <div class="table-responsive" role="region" aria-label="রেসপন্স অনুমোদন কিউ" tabindex="0">
                            <table class="enterprise-table">
                                <thead>
                                    <tr>
                                        <th>অ্যাকশন ইউআইডি</th>
                                        <th>ধরণ ও টার্গেট</th>
                                        <th>স্ট্যাটাস</th>
                                        <th style="text-align: right;">অনুমোদন / রোলব্যাক</th>
                                    </tr>
                                </thead>
                                <tbody id="zt-response-actions-table-body">
                                    <tr><td colspan="4" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 3. Threat Detection Rules & Correlated Detections -->
                <div class="analytics-grid" style="margin-bottom: 24px;">
                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">📡 থ্রেট ডিটেকশন রুলস (টেলিমেট্রি ও থ্রেশহোল্ড)</div>
                        </div>
                        <div class="table-responsive" role="region" aria-label="থ্রেট ডিটেকশন রুলস" tabindex="0">
                            <table class="enterprise-table">
                                <thead>
                                    <tr>
                                        <th>রুল কোড</th>
                                        <th>ডাটা সোর্স ও থ্রেশহোল্ড</th>
                                        <th>তীব্রতা ও রেসপন্স</th>
                                        <th>TP / FP</th>
                                    </tr>
                                </thead>
                                <tbody id="zt-threat-rules-table-body">
                                    <tr><td colspan="4" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">🔎 কোরিলেটেড থ্রেট ডিটেকশনস ও এভিডেন্স</div>
                        </div>
                        <div class="table-responsive" role="region" aria-label="থ্রেট ডিটেকশন ইতিহাস" tabindex="0">
                            <table class="enterprise-table">
                                <thead>
                                    <tr>
                                        <th>ডিটেকশন ও কোরিলেশন</th>
                                        <th>রুল ও পর্যবেক্ষণ</th>
                                        <th>স্ট্যাটাস</th>
                                        <th style="text-align: right;">অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody id="zt-detections-table-body">
                                    <tr><td colspan="4" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 4. Compliance Evidence Center & SLO / Config Drift -->
                <div class="analytics-grid" style="margin-bottom: 24px;">
                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">🏛️ কমপ্লায়েন্স এভিডেন্স সেন্টার (ISO 27001 / SOC 2 / OWASP ASVS / CIS Mappings)</div>
                        </div>
                        <div class="table-responsive" role="region" aria-label="কমপ্লায়েন্স কন্ট্রোল টেবিল" tabindex="0">
                            <table class="enterprise-table">
                                <thead>
                                    <tr>
                                        <th>কন্ট্রোল আইডি</th>
                                        <th>শিরোনাম ও ফ্রেমওয়ার্ক ম্যাপিং</th>
                                        <th>এভিডেন্স স্ট্যাটাস</th>
                                        <th>মূল্যায়ন</th>
                                        <th style="text-align: right;">সাইন-অফ</th>
                                    </tr>
                                </thead>
                                <tbody id="zt-compliance-table-body">
                                    <tr><td colspan="5" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="data-card">
                        <div class="data-card-header">
                            <div class="chart-title">🎯 সার্ভিস-লেভেল অবজেক্টিভস (SLOs), এরর বাজেট ও রিলিজ গেটস</div>
                        </div>
                        <div class="table-responsive" role="region" aria-label="SLO ও এরর বাজেট টেবিল" tabindex="0">
                            <table class="enterprise-table">
                                <thead>
                                    <tr>
                                        <th>SLO কোড ও নাম</th>
                                        <th>লক্ষ্যমাত্রা</th>
                                        <th>পরিমাপকৃত মান</th>
                                        <th>স্ট্যাটাস</th>
                                    </tr>
                                </thead>
                                <tbody id="zt-slos-table-body">
                                    <tr><td colspan="4" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="zt-release-gates-box" style="padding: 14px 20px; border-top: 1px solid var(--border-color); font-size: 12px;">
                            রিলিজ গেটস লোড হচ্ছে...
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Contextual Right-Side Detail Drawer (Phase 4 Enterprise Inspector) -->
    <div id="context-drawer-backdrop" onclick="closeContextDrawer()" aria-hidden="true"></div>
    <aside id="context-detail-drawer" role="dialog" aria-modal="true" aria-labelledby="context-drawer-title">
        <div class="drawer-header">
            <div class="modal-title" id="context-drawer-title">🔎 রেকর্ড পরিদর্শন প্যানেল</div>
            <button class="btn-icon" onclick="closeContextDrawer()" aria-label="ড্রয়ার বন্ধ করুন">✕</button>
        </div>
        <div class="drawer-body" id="context-drawer-body">
            <div style="color: var(--text-muted); text-align: center; padding: 30px;">বিস্তারিত তথ্য লোড হচ্ছে...</div>
        </div>
        <div class="drawer-footer">
            <button class="btn-secondary" onclick="closeContextDrawer()">বন্ধ করুন</button>
        </div>
    </aside>

    <!-- 4. Global Modals & Dialogs -->

    <!-- User Action / Detail Modal -->
    <div id="user-action-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
        <div class="modal-box">
            <div class="modal-header">
                <div class="modal-title" id="user-modal-title">ব্যবহারকারী ব্যবস্থাপনা</div>
                <button class="btn-icon" onclick="closeUserModal()" aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="modal-body" id="user-modal-body">
                <input type="hidden" id="modal-target-user-id">
                <div class="form-group">
                    <label class="form-label">অ্যাকশন নির্বাচন করুন</label>
                    <select id="modal-user-action-select" class="form-control" onchange="handleModalActionChange()">
                        <option value="verify">পরিচয় ভেরিফাই করুন (Verify Badge)</option>
                        <option value="unverify">ভেরিফিকেশন প্রত্যাহার করুন</option>
                        <option value="suspend">অ্যাকাউন্ট সাময়িক সাসপেন্ড করুন</option>
                        <option value="ban">স্থায়ী ব্যান করুন (Ban User)</option>
                        <option value="activate">পুনরায় সক্রিয় করুন (Activate)</option>
                        <option value="force_logout">জোরপূর্বক লগআউট (Force Logout)</option>
                        <option value="reset_password">নতুন পাসওয়ার্ড রিসেট করুন</option>
                        <option value="assign_role">রোল পরিবর্তন করুন</option>
                    </select>
                </div>
                <div class="form-group" id="modal-duration-group" style="display: none;">
                    <label class="form-label">সাসপেনশনের মেয়াদ (দিন)</label>
                    <input type="number" id="modal-suspend-days" class="form-control" value="7" min="1" max="365">
                </div>
                <div class="form-group" id="modal-role-group" style="display: none;">
                    <label class="form-label">নতুন রোল</label>
                    <select id="modal-role-select" class="form-control">
                        <option value="USER">USER (সাধারণ ব্যবহারকারী)</option>
                        <option value="MODERATOR">MODERATOR (মডারেটর)</option>
                        <option value="ADMIN">ADMIN (অ্যাডমিনিস্ট্রেটর)</option>
                    </select>
                </div>
                <div class="form-group" id="modal-password-group" style="display: none;">
                    <label class="form-label">নতুন পাসওয়ার্ড</label>
                    <input type="password" id="modal-new-password" class="form-control" placeholder="কমপক্ষে ৮ অক্ষরের পাসওয়ার্ড">
                </div>
                <div class="form-group">
                    <label class="form-label">কারণ / প্রশাসনিক নোট (Reason)</label>
                    <textarea id="modal-action-reason" class="form-control" placeholder="অডিট লগের জন্য কারণ লিখুন..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeUserModal()">বাতিল</button>
                <button class="btn-primary" onclick="submitUserAction()">নিশ্চিত করুন</button>
            </div>
        </div>
    </div>

    <!-- Announcement Modal -->
    <div id="announcement-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-label="নতুন প্ল্যাটফর্ম ঘোষণা">
        <div class="modal-box">
            <div class="modal-header">
                <div class="modal-title">📢 নতুন প্ল্যাটফর্ম ঘোষণা প্রকাশ করুন</div>
                <button class="btn-icon" onclick="closeAnnouncementModal()" aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">ঘোষণার শিরোনাম</label>
                    <input type="text" id="ann-title" class="form-control" placeholder="যেমন: নতুন ফিচার বা মেইনটেন্যান্স নোটিশ">
                </div>
                <div class="form-group">
                    <label class="form-label">ঘোষণার বার্তা</label>
                    <textarea id="ann-message" class="form-control" style="height: 100px;" placeholder="বিস্তারিত ঘোষণা লিখুন..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">ঘোষণার ধরণ</label>
                    <select id="ann-type" class="form-control">
                        <option value="info">তথ্যমূলক (Info - Blue)</option>
                        <option value="success">সাফল্য (Success - Green)</option>
                        <option value="warning">সতর্কবার্তা (Warning - Orange)</option>
                        <option value="urgent">জরুরি বিজ্ঞপ্তি (Urgent - Red)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">টার্গেট অডিয়েন্স</label>
                    <select id="ann-target" class="form-control">
                        <option value="all">সকল ব্যবহারকারী (All Users)</option>
                        <option value="creators">শুধুমাত্র কনটেন্ট ক্রিয়েটর</option>
                        <option value="moderators">মডারেটর ও অ্যাডমিন দল</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeAnnouncementModal()">বাতিল</button>
                <button class="btn-primary" onclick="submitAnnouncement()">সম্প্রচার করুন</button>
            </div>
        </div>
    </div>

    <!-- 1. Bulk Users Preview Dry-Run Modal -->
    <div id="bulk-preview-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-label="বাল্ক ইউজার প্রিভিউ">
        <div class="modal-box" style="max-width: 550px;">
            <div class="modal-header">
                <div class="modal-title">👥 বাল্ক ইউজার অপারেশন প্রিভিউ (Dry-Run)</div>
                <button class="btn-icon" onclick="closeBulkPreviewModal()" aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="modal-body">
                <div id="bulk-preview-warning" style="display: none; padding: 12px; background: rgba(239, 68, 68, 0.1); border: 1px solid var(--accent-danger); border-radius: var(--radius-sm); color: var(--accent-danger); font-size: 13px; font-weight: 700; margin-bottom: 14px;"></div>
                <div style="font-size: 13.5px; margin-bottom: 12px;">
                    নির্বাচিত ব্যবহারকারীর মোট সংখ্যা: <strong id="bulk-preview-count">0</strong> জন
                </div>
                <div style="font-size: 13px; margin-bottom: 14px;">
                    প্রস্তাবিত অ্যাকশন: <strong id="bulk-preview-action" style="color: var(--accent-primary);">--</strong>
                </div>
                <div class="form-group">
                    <label class="form-label">প্রশাসনিক কারণ (Audit Reason)</label>
                    <input type="text" id="bulk-preview-reason" class="form-control" placeholder="বাল্ক পরিবর্তনের কারণ লিখুন...">
                </div>
                <div style="font-size: 12px; color: var(--text-muted);">
                    নিশ্চিত করার পর পরিবর্তনটি অবিলম্বে সকল নির্বাচিত অ্যাকাউন্টে কার্যকর হবে এবং অডিট ট্রেইলে সংরক্ষিত হবে।
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeBulkPreviewModal()">বাতিল</button>
                <button class="btn-primary" id="btn-confirm-bulk" onclick="executeConfirmedBulkAction()">নিশ্চিত কার্যকর করুন</button>
            </div>
        </div>
    </div>

    <!-- 2. Settings Rollback Modal -->
    <div id="rollback-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-label="সেটিংস রোলব্যাক">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header">
                <div class="modal-title">⏪ প্ল্যাটফর্ম সেটিংস ইনস্ট্যান্ট রোলব্যাক</div>
                <button class="btn-icon" onclick="closeRollbackModal()" aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">রোলব্যাকের জন্য সেটিংস নির্বাচন করুন</label>
                    <select id="rollback-setting-key" class="form-control">
                        <option value="site_name">সাইট নাম (site_name)</option>
                        <option value="site_tagline">ট্যাগলাইন (site_tagline)</option>
                        <option value="contact_email">যোগাযোগ ইমেইল (contact_email)</option>
                        <option value="max_upload_size_mb">আপলোড লিমিট (max_upload_size_mb)</option>
                        <option value="registration_enabled">রেজিস্ট্রেশন স্ট্যাটাস (registration_enabled)</option>
                        <option value="maintenance_mode">রক্ষণাবেক্ষণ মোড (maintenance_mode)</option>
                    </select>
                </div>
                <div style="font-size: 12.5px; color: var(--text-muted); padding: 8px 0;">
                    নির্বাচিত সেটিংসটিকে এর পূর্ববর্তী সংরক্ষিত মানে ফিরিয়ে নেওয়া হবে এবং ক্যাশ স্বয়ংক্রিয়ভাবে ইনভ্যালিডেট হবে।
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeRollbackModal()">বাতিল</button>
                <button class="btn-primary" onclick="submitSettingRollback()">রোলব্যাক নিশ্চিত করুন</button>
            </div>
        </div>
    </div>

    <!-- 3. Security Incident Resolve Modal -->
    <div id="incident-resolve-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-label="ইনসিডেন্ট নিষ্পত্তি">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-header">
                <div class="modal-title">🛡️ নিরাপত্তা ইনসিডেন্ট নিষ্পত্তি</div>
                <button class="btn-icon" onclick="closeIncidentModal()" aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="incident-target-id">
                <div class="form-group">
                    <label class="form-label">তদন্ত ও নিষ্পত্তির বিবরণ (Investigation Notes)</label>
                    <textarea id="incident-notes" class="form-control" style="height: 100px;" placeholder="ঘটনার কারণ এবং গৃহীত সুরক্ষামূলক ব্যবস্থা উল্লেখ করুন..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeIncidentModal()">বাতিল</button>
                <button class="btn-primary" onclick="submitIncidentResolution()">নিষ্পত্তি সম্পন্ন করুন</button>
            </div>
        </div>
    </div>

    <!-- 4. Moderation Appeal Modal -->
    <div id="appeal-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-label="মডারেশন আপিল নিষ্পত্তি">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-header">
                <div class="modal-title">⚖️ মডারেশন আপিল নিষ্পত্তি</div>
                <button class="btn-icon" onclick="closeAppealModal()" aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="appeal-target-report-id">
                <div class="form-group">
                    <label class="form-label">আপিল সিদ্ধান্ত নির্বাচন করুন</label>
                    <select id="appeal-decision-select" class="form-control">
                        <option value="overturn">আপিল মঞ্জুর করুন ও কন্টেন্ট পুনর্বহাল (Overturn)</option>
                        <option value="uphold">আপিল খারিজ ও আগের সিদ্ধান্ত বহাল (Uphold)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">প্রশাসনিক মন্তব্য / নোট</label>
                    <textarea id="appeal-notes" class="form-control" style="height: 90px;" placeholder="আপিল নিষ্পত্তির কারণ ও পর্যবেক্ষণ লিখুন..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeAppealModal()">বাতিল</button>
                <button class="btn-primary" onclick="submitAppealResolution()">সিদ্ধান্ত কার্যকর করুন</button>
            </div>
        </div>
    </div>

    <!-- Command Palette (Cmd + K / Ctrl + K) -->
    <div id="command-palette-overlay" class="modal-overlay" role="dialog" aria-modal="true" aria-label="গ্লোবাল কমান্ড প্যালেট" onclick="closeCommandPalette(event)">
        <div id="command-palette" onclick="event.stopPropagation()">
            <div class="cp-search-box">
                <span style="font-size: 18px; color: var(--text-muted);" aria-hidden="true">⚡</span>
                <input type="text" id="cp-input" class="cp-input" aria-label="কমান্ড বা অনুসন্ধান টাইপ করুন" placeholder="কমান্ড টাইপ করুন বা ইউজার/পোস্ট/টিকিট খুঁজুন (↑↓ Enter)..." onkeydown="handleCommandPaletteKeydown(event)" oninput="filterCommandPalette(event)">
                <span class="search-shortcut-badge">ESC</span>
            </div>
            <div class="cp-list" id="cp-list" role="listbox">
                <div class="cp-item selected" role="option" onclick="switchSection('overview'); closeCommandPalette();"><span>📊 এক্সিকিউটিভ ড্যাশবোর্ড ওভারভিউ</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('analytics'); closeCommandPalette();"><span>📈 এক্সিকিউটিভ ইন্টেলিজেন্স ও ফানেল</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('users'); closeCommandPalette();"><span>👥 ব্যবহারকারী অ্যাকাউন্ট ও প্রোফাইল</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('roles'); closeCommandPalette();"><span>🛡️ রোল, পারমিশন ও সাময়িক এলিভেশন (RBAC)</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('content'); closeCommandPalette();"><span>📝 সোশ্যাল কন্টেন্ট ও পোস্টসমূহ</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('moderation'); closeCommandPalette();"><span>🚨 AI কন্টেন্ট মডারেশন কিউ</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('trust_safety'); closeCommandPalette();"><span>🛑 ট্রাস্ট, সেফটি ও অপব্যবহার প্রতিরোধ</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('security'); closeCommandPalette();"><span>🔒 সিকিউরিটি সেন্টার (SOC 2.0)</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('alerts'); closeCommandPalette();"><span>🔔 রিয়েল-টাইম অপারেশনস অ্যালার্টস</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('jobs'); closeCommandPalette();"><span>⚡ ব্যাকগ্রাউন্ড জবস ও কিউ কন্ট্রোল</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('health'); closeCommandPalette();"><span>🩺 সিস্টেম ও ইনফ্রাস্ট্রাকচার স্বাস্থ্য</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('audit'); closeCommandPalette();"><span>📜 অডিট ট্রেইল ও সাইন্ড চেকপয়েন্ট</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('governance'); closeCommandPalette();"><span>🏛️ ডাটা গভর্ন্যান্স, প্রাইভেসি ও রিটেনশন</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('flags'); closeCommandPalette();"><span>🚩 ফিচার ফ্ল্যাগ ও রোলআউট কন্ট্রোল</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="switchSection('settings'); closeCommandPalette();"><span>⚙️ প্ল্যাটফর্ম কনফিগারেশন সেটিংস</span><span style="font-size:11px;color:var(--text-muted);">Workspace</span></div>
                <div class="cp-item" role="option" onclick="triggerDevAction('clear_cache'); closeCommandPalette();"><span>⚡ অ্যাপ ক্যাশ ক্লিয়ার করুন</span><span style="font-size:11px;color:var(--accent-primary);">Action</span></div>
                <div class="cp-item" role="option" onclick="openAnnouncementModal(); closeCommandPalette();"><span>📢 নতুন সিস্টেম ঘোষণা তৈরি করুন</span><span style="font-size:11px;color:var(--accent-primary);">Action</span></div>
            </div>
        </div>
    </div>

    <!-- 5. User Dossier Investigation Modal (Module K) -->
    <div id="dossier-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-label="ব্যবহারকারী তদন্ত ডসিয়ার">
        <div class="modal-box" style="max-width: 720px; max-height: 85vh; overflow-y: auto;">
            <div class="modal-header">
                <div class="modal-title">🕵️ ব্যবহারকারী তদন্ত ডসিয়ার (User Dossier)</div>
                <button class="btn-icon" onclick="closeDossierModal()" aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="modal-body" id="dossier-modal-body">
                <div style="text-align: center; padding: 30px;">ডসিয়ার তৈরি হচ্ছে...</div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeDossierModal()">বন্ধ করুন</button>
            </div>
        </div>
    </div>

    <!-- 6. Restrict User Modal (Module C) -->
    <div id="restrict-user-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-label="সীমাবদ্ধতা প্রয়োগ">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-header">
                <div class="modal-title">🛑 অপব্যবহার প্রতিরোধে সীমাবদ্ধতা প্রয়োগ</div>
                <button class="btn-icon" onclick="closeRestrictModal()" aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="restrict-target-user-id">
                <div class="form-group">
                    <label class="form-label">সীমাবদ্ধতার ধরণ</label>
                    <select id="restrict-type-select" class="form-control">
                        <option value="post_block">পোস্ট তৈরিতে নিষেধাজ্ঞা (Post Block)</option>
                        <option value="comment_block">কমেন্ট তৈরিতে নিষেধাজ্ঞা (Comment Block)</option>
                        <option value="message_block">মেসেজিং পাঠানোতে নিষেধাজ্ঞা (Message Block)</option>
                        <option value="read_only">রিড-অনলি মোড (Read-Only)</option>
                        <option value="rate_limited">রিকোয়েস্ট রেট লিমিট থ্রটলিং (Rate Limited)</option>
                        <option value="full_ban">সম্পূর্ণ ব্যান (Full Ban)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">মেয়াদ (ঘণ্টা)</label>
                    <input type="number" id="restrict-duration-hours" class="form-control" value="24" min="1" max="8760">
                </div>
                <div class="form-group">
                    <label class="form-label">বাধ্যতামূলক কারণ / ব্যাখ্যা</label>
                    <textarea id="restrict-reason" class="form-control" style="height: 80px;" placeholder="অডিট লগের জন্য সীমাবদ্ধতার সুনির্দিষ্ট কারণ লিখুন..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeRestrictModal()">বাতিল</button>
                <button class="btn-danger" onclick="submitUserRestriction()">সীমাবদ্ধতা প্রয়োগ করুন</button>
            </div>
        </div>
    </div>

    <!-- 7. Override Restriction Modal (Module C) -->
    <div id="override-restriction-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-label="সীমাবদ্ধতা প্রত্যাহার">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-header">
                <div class="modal-title">🔓 সীমাবদ্ধতা প্রত্যাহার / ওভাররাইড</div>
                <button class="btn-icon" onclick="closeOverrideModal()" aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="override-target-restriction-id">
                <div class="form-group">
                    <label class="form-label">বাধ্যতামূলক প্রশাসনিক ব্যাখ্যা (Justification)</label>
                    <textarea id="override-justification" class="form-control" style="height: 100px;" placeholder="কেন এই নিষেধাজ্ঞা অকালে প্রত্যাহার করা হচ্ছে তার অডিটযোগ্য কারণ লিখুন..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeOverrideModal()">বাতিল</button>
                <button class="btn-primary" onclick="submitRestrictionOverride()">প্রত্যাহার নিশ্চিত করুন</button>
            </div>
        </div>
    </div>

    <!-- 8. Privacy Request Process Modal (Module L) -->
    <div id="privacy-process-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-label="প্রাইভেসি রিকোয়েস্ট নিষ্পত্তি">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-header">
                <div class="modal-title">⚖️ প্রাইভেসি ও GDPR রিকোয়েস্ট নিষ্পত্তি</div>
                <button class="btn-icon" onclick="closePrivacyProcessModal()" aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="privacy-target-req-id">
                <div style="margin-bottom: 14px; font-size: 13px; color: var(--text-secondary);" id="privacy-req-desc">
                    রিকোয়েস্ট আইডি পর্যালোচনাধীন।
                </div>
                <div class="form-group">
                    <label class="form-label">প্রশাসনিক সিদ্ধান্ত</label>
                    <select id="privacy-decision-select" class="form-control">
                        <option value="approve">অনুমোদন ও কার্যকর করুন (Approve)</option>
                        <option value="reject">প্রত্যাখ্যান করুন (Reject)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">নিষ্পত্তির মন্তব্য / নোট</label>
                    <textarea id="privacy-notes" class="form-control" style="height: 80px;" placeholder="আইনি অনুপালনের বিবরণ লিখুন..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closePrivacyProcessModal()">বাতিল</button>
                <button class="btn-primary" onclick="submitProcessPrivacy()">নিষ্পত্তি নিশ্চিত করুন</button>
            </div>
        </div>
    </div>

    <!-- 9. Failed Job Inspect Modal (Module I) -->
    <div id="failed-job-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-label="ব্যর্থ জব বিস্তারিত">
        <div class="modal-box" style="max-width: 600px;">
            <div class="modal-header">
                <div class="modal-title">❌ ব্যর্থ জব বিস্তারিত ও পেলোড</div>
                <button class="btn-icon" onclick="closeFailedJobModal()" aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="modal-body">
                <div style="font-weight: 700; margin-bottom: 8px;" id="failed-job-class-name">Exception</div>
                <pre id="failed-job-exception-text" style="background: var(--bg-body); padding: 12px; border-radius: 6px; font-size: 12px; max-height: 180px; overflow-y: auto; white-space: pre-wrap; margin-bottom: 12px;"></pre>
                <div style="font-weight: 700; margin-bottom: 6px;">পেলোড (সংবেদনশীল ডাটা গোপনকৃত):</div>
                <pre id="failed-job-payload-text" style="background: var(--bg-body); padding: 12px; border-radius: 6px; font-size: 11.5px; max-height: 140px; overflow-y: auto; white-space: pre-wrap;"></pre>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeFailedJobModal()">বন্ধ করুন</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" role="status" aria-live="polite"></div>

    <!-- 5. Client JavaScript Execution Engine -->
    <script>
        const API_BASE = '/api/v2/admin/enterprise';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const adminToken = document.querySelector('meta[name="admin-token"]')?.getAttribute('content') || '';

        // API Request Helper
        async function apiRequest(endpoint, method = 'GET', body = null) {
            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            };
            if (adminToken) {
                headers['Authorization'] = `Bearer ${adminToken}`;
            }

            const options = { method, headers };
            if (body) {
                options.body = JSON.stringify(body);
            }

            try {
                const res = await fetch(`${API_BASE}${endpoint}`, options);
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'অনুরোধ ব্যর্থ হয়েছে।');
                }
                return data;
            } catch (err) {
                showToast(err.message, 'error');
                throw err;
            }
        }

        // Toast Notification Function
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? '✓' : (type === 'error' ? '✕' : 'ℹ');
            toast.innerHTML = `<span>${icon}</span><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        // Theme Toggle (Light / Dark)
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.classList.contains('dark') ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            html.classList.remove(current);
            html.classList.add(next);
            localStorage.setItem('admin_theme', next);
            document.getElementById('theme-icon').textContent = next === 'dark' ? '☀️' : '🌙';
        }

        // Initialize Theme from localStorage
        (function initTheme() {
            const saved = localStorage.getItem('admin_theme') || 'light';
            document.documentElement.className = saved;
            const icon = document.getElementById('theme-icon');
            if (icon) icon.textContent = saved === 'dark' ? '☀️' : '🌙';
        })();

        // Sidebar Toggle (Desktop Collapse + Mobile Slide-Out Drawer)
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-open');
                if (backdrop) backdrop.classList.toggle('active', sidebar.classList.contains('mobile-open'));
            } else {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('admin_sidebar_collapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
            }
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            sidebar?.classList.remove('mobile-open');
            backdrop?.classList.remove('active');
        }

        // Restore desktop sidebar collapsed preference
        (function initSidebarPreference() {
            if (window.innerWidth > 768 && localStorage.getItem('admin_sidebar_collapsed') === '1') {
                document.getElementById('sidebar')?.classList.add('collapsed');
            }
        })();

        // Contextual Detail Drawer Helpers
        function openContextDrawer(title, htmlContent) {
            const titleEl = document.getElementById('context-drawer-title');
            const bodyEl = document.getElementById('context-drawer-body');
            if (titleEl) titleEl.textContent = title;
            if (bodyEl) bodyEl.innerHTML = htmlContent;
            document.getElementById('context-detail-drawer')?.classList.add('open');
            document.getElementById('context-drawer-backdrop')?.classList.add('active');
        }

        function closeContextDrawer() {
            document.getElementById('context-detail-drawer')?.classList.remove('open');
            document.getElementById('context-drawer-backdrop')?.classList.remove('active');
        }

        const SECTION_TITLES = {
            overview: 'ওভারভিউ ড্যাশবোর্ড',
            analytics: 'এক্সিকিউটিভ ইন্টেলিজেন্স',
            search: 'গ্লোবাল সার্চ ও ডসিয়ার',
            users: 'ব্যবহারকারী ব্যবস্থাপনা',
            content: 'পোস্ট ও কন্টেন্ট',
            moderation: 'রিপোর্ট ও মডারেশন',
            trust_safety: 'ট্রাস্ট ও সেফটি ইঞ্জিন',
            social: 'গ্রুপ ও পেজসমূহ',
            messaging: 'মেসেজিং ও ঘোষণা',
            roles: 'রোল ও পারমিশন (RBAC)',
            security: 'নিরাপত্তা ও লগইন (SOC 2.0)',
            alerts: 'লাইভ অ্যালার্ট সেন্টার',
            support: 'সাপোর্ট ও হেল্পডেস্ক',
            monetization: 'মনিটাইজেশন ও অ্যাডস',
            jobs: 'জবস ও কিউ অটোমেশন',
            health: 'ইনফ্রাস্ট্রাকচার হেলথ',
            flags: 'ফিচার ফ্ল্যাগ ও রোলআউট',
            settings: 'প্ল্যাটফর্ম সেটিংস',
            developer: 'ডেভেলপার ও সিস্টেম',
            audit: 'অডিট লগসমূহ',
            governance: 'ডাটা গভর্ন্যান্স ও প্রাইভেসি',
            readiness: 'প্রোডাকশন প্রস্তুতি ও রিকভারি (DR)',
            zerotrust: 'জিরো-ট্রাস্ট ও এন্টারপ্রাইজ রেজিলিয়েন্স'
        };

        // Section Switcher
        function switchSection(secId) {
            document.querySelectorAll('.admin-section').forEach(sec => sec.classList.remove('active'));
            document.querySelectorAll('#sidebar .nav-item').forEach(item => {
                item.classList.remove('active');
                item.removeAttribute('aria-current');
            });

            const targetSec = document.getElementById(`sec-${secId}`);
            if (targetSec) {
                targetSec.classList.add('active');
            }

            // Highlight corresponding sidebar item & update aria-current
            const navItems = document.querySelectorAll('#sidebar .nav-item');
            navItems.forEach(item => {
                if (item.getAttribute('onclick')?.includes(`'${secId}'`)) {
                    item.classList.add('active');
                    item.setAttribute('aria-current', 'page');
                }
            });

            // Update Top Breadcrumb
            const breadcrumbCurrent = document.getElementById('top-breadcrumb-current');
            if (breadcrumbCurrent) {
                breadcrumbCurrent.textContent = SECTION_TITLES[secId] || secId;
            }

            closeMobileSidebar();

            // Lazy load table data on tab switch
            if (secId === 'analytics') loadExecutiveIntelligence();
            if (secId === 'users') loadUsersTable(1);
            if (secId === 'content') loadPostsTable(1);
            if (secId === 'moderation') loadModerationTable(1);
            if (secId === 'trust_safety') loadTrustSafety();
            if (secId === 'alerts') loadAlertsCenter(1);
            if (secId === 'jobs') loadJobsCenter();
            if (secId === 'health') loadHealthCenter();
            if (secId === 'flags') loadFeatureFlags();
            if (secId === 'governance') loadGovernanceCenter();
            if (secId === 'readiness') loadProductionReadiness();
            if (secId === 'zerotrust') loadZeroTrustWorkspace();
            if (secId === 'social') loadSocialTable(1);
            if (secId === 'messaging') loadMessagingOverview();
            if (secId === 'roles') loadRolesMatrix();
            if (secId === 'security') loadSecurityOverview();
            if (secId === 'support') loadSupportTable(1);
            if (secId === 'monetization') loadMonetization();
            if (secId === 'audit') loadAuditLogs(1);
            if (secId === 'search') document.getElementById('search-workspace-input')?.focus();
        }

        // ==========================================
        // 1. Users Management Logic
        // ==========================================
        let currentUserPage = 1;
        let selectedUserIds = new Set();

        async function loadUsersTable(page = 1) {
            currentUserPage = page;
            const status = document.getElementById('user-status-filter')?.value || 'all';
            const search = document.getElementById('user-search-filter')?.value || '';
            const tbody = document.getElementById('users-table-body');
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>`;

            try {
                const res = await apiRequest(`/users?page=${page}&status=${status}&search=${encodeURIComponent(search)}`);
                const users = res.data || [];
                selectedUserIds.clear();

                if (users.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">কোনো ব্যবহারকারী পাওয়া যায়নি।</td></tr>`;
                    return;
                }

                tbody.innerHTML = users.map(u => `
                    <tr>
                        <td><input type="checkbox" value="${u.id}" onchange="toggleSelectUser(${u.id}, this.checked)"></td>
                        <td>
                            <div style="font-weight: 700;">${u.name || 'N/A'}</div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">${u.phone || 'ফোন নেই'}</div>
                        </td>
                        <td>
                            <div>@${u.username || 'unknown'}</div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">${u.email || ''}</div>
                        </td>
                        <td><span style="font-size: 11px; font-weight: 700; background: rgba(79,70,229,0.1); color: var(--accent-primary); padding: 2px 6px; border-radius: 4px;">${u.roles?.[0]?.name || 'USER'}</span></td>
                        <td>
                            <span class="badge-status ${u.status === 'suspended' ? 'badge-suspended' : (u.is_verified ? 'badge-verified' : 'badge-active')}">
                                ${u.status === 'suspended' ? 'সাসপেন্ডেড' : (u.is_verified ? '✓ ভেরিফাইড' : 'সক্রিয়')}
                            </span>
                        </td>
                        <td style="font-size: 12px; color: var(--text-secondary);">${new Date(u.created_at).toLocaleDateString('bn-BD')}</td>
                        <td style="text-align: right;">
                            <button class="btn-table-action" onclick="openUserActionModal(${u.id}, '${escapeHtml(u.name)}')">অ্যাকশন ⚙</button>
                        </td>
                    </tr>
                `).join('');

                renderPagination('users-pagination-bar', res.meta, 'loadUsersTable');
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--accent-danger);">ডাটা লোড করতে সমস্যা হয়েছে।</td></tr>`;
            }
        }

        let userSearchTimer;
        function handleUserSearch(event) {
            clearTimeout(userSearchTimer);
            userSearchTimer = setTimeout(() => loadUsersTable(1), 300);
        }

        function toggleSelectUser(id, isChecked) {
            if (isChecked) selectedUserIds.add(id);
            else selectedUserIds.delete(id);
        }

        function toggleSelectAllUsers(master) {
            const boxes = document.querySelectorAll('#users-table-body input[type="checkbox"]');
            boxes.forEach(b => {
                b.checked = master.checked;
                toggleSelectUser(parseInt(b.value), master.checked);
            });
        }

        let pendingBulkAction = null;
        async function applyBulkUserAction(action) {
            if (selectedUserIds.size === 0) {
                showToast('অনুগ্রহ করে অন্তত একজন ব্যবহারকারী সিলেক্ট করুন।', 'error');
                return;
            }
            pendingBulkAction = action;

            try {
                // Dry-Run Preview
                const previewRes = await apiRequest('/users/bulk/preview', 'POST', {
                    user_ids: Array.from(selectedUserIds),
                    action: action
                });

                const data = previewRes.data || {};
                document.getElementById('bulk-preview-count').textContent = data.total_affected || selectedUserIds.size;
                document.getElementById('bulk-preview-action').textContent = action.toUpperCase();

                const warningBox = document.getElementById('bulk-preview-warning');
                if (data.super_admin_detected && data.warning) {
                    warningBox.textContent = data.warning;
                    warningBox.style.display = 'block';
                } else {
                    warningBox.style.display = 'none';
                }

                document.getElementById('bulk-preview-reason').value = '';
                document.getElementById('bulk-preview-modal').classList.add('active');
            } catch (e) {}
        }

        function closeBulkPreviewModal() {
            document.getElementById('bulk-preview-modal').classList.remove('active');
            pendingBulkAction = null;
        }

        async function executeConfirmedBulkAction() {
            if (!pendingBulkAction || selectedUserIds.size === 0) return;
            const reason = document.getElementById('bulk-preview-reason').value;
            const btn = document.getElementById('btn-confirm-bulk');
            btn.disabled = true;
            btn.textContent = 'কার্যকর হচ্ছে...';

            try {
                const res = await apiRequest('/users/bulk', 'POST', {
                    user_ids: Array.from(selectedUserIds),
                    action: pendingBulkAction,
                    reason: reason
                });
                showToast(res.message);
                closeBulkPreviewModal();
                selectedUserIds.clear();
                loadUsersTable(currentUserPage);
            } catch (e) {
            } finally {
                btn.disabled = false;
                btn.textContent = 'নিশ্চিত কার্যকর করুন';
            }
        }

        // ==========================================
        // User Action Modal
        // ==========================================
        function openUserActionModal(userId, userName) {
            document.getElementById('modal-target-user-id').value = userId;
            document.getElementById('user-modal-title').textContent = `ব্যবস্থাপনা: ${userName}`;
            document.getElementById('user-action-modal').classList.add('active');
            handleModalActionChange();
        }

        function closeUserModal() {
            document.getElementById('user-action-modal').classList.remove('active');
        }

        function handleModalActionChange() {
            const action = document.getElementById('modal-user-action-select').value;
            document.getElementById('modal-duration-group').style.display = action === 'suspend' ? 'block' : 'none';
            document.getElementById('modal-role-group').style.display = action === 'assign_role' ? 'block' : 'none';
            document.getElementById('modal-password-group').style.display = action === 'reset_password' ? 'block' : 'none';
        }

        async function submitUserAction() {
            const userId = document.getElementById('modal-target-user-id').value;
            const action = document.getElementById('modal-user-action-select').value;
            const reason = document.getElementById('modal-action-reason').value;
            const days = parseInt(document.getElementById('modal-suspend-days').value) || 7;
            const role = document.getElementById('modal-role-select').value;
            const newPassword = document.getElementById('modal-new-password').value;

            try {
                const res = await apiRequest(`/users/${userId}/action`, 'POST', {
                    action,
                    reason,
                    duration_days: days,
                    role,
                    new_password: newPassword
                });
                showToast(res.message);
                closeUserModal();
                loadUsersTable(currentUserPage);
            } catch (e) {}
        }

        // ==========================================
        // 2. Posts Management Logic
        // ==========================================
        async function loadPostsTable(page = 1) {
            const status = document.getElementById('post-status-filter')?.value || 'all';
            const search = document.getElementById('post-search-filter')?.value || '';
            const tbody = document.getElementById('posts-table-body');
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>`;

            try {
                const res = await apiRequest(`/posts?page=${page}&status=${status}&search=${encodeURIComponent(search)}`);
                const posts = res.data || [];

                if (posts.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">কোনো পোস্ট পাওয়া যায়নি।</td></tr>`;
                    return;
                }

                tbody.innerHTML = posts.map(p => `
                    <tr>
                        <td>
                            <div style="font-weight: 700;">${p.user?.name || 'N/A'}</div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">@${p.user?.username || ''}</div>
                        </td>
                        <td style="max-width: 320px;">
                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(p.content || '[ছবি বা ভিডিও পোস্ট]')}</div>
                            ${p.is_pinned ? '<span style="font-size: 10px; color: var(--accent-primary); font-weight: 700;">📌 পিন করা</span>' : ''}
                        </td>
                        <td>
                            <span style="font-size: 12px; font-weight: 600;">❤️ ${p.reactions_count || 0} | 💬 ${p.comments_count || 0}</span>
                        </td>
                        <td>
                            <span class="badge-status ${p.reports_count > 0 ? 'badge-suspended' : 'badge-active'}">
                                ${p.reports_count || 0} রিপোর্ট
                            </span>
                        </td>
                        <td style="font-size: 12px; color: var(--text-secondary);">${new Date(p.created_at).toLocaleDateString('bn-BD')}</td>
                        <td style="text-align: right;">
                            <button class="btn-table-action" onclick="executePostAction(${p.id}, '${p.status === 'hidden' ? 'unhide' : 'hide'}')">${p.status === 'hidden' ? 'আনহাইড' : 'হাইড'}</button>
                            <button class="btn-table-action" onclick="executePostAction(${p.id}, 'delete')" style="color: var(--accent-danger);">ডিলিট</button>
                        </td>
                    </tr>
                `).join('');

                renderPagination('posts-pagination-bar', res.meta, 'loadPostsTable');
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: var(--accent-danger);">ডাটা লোড করতে সমস্যা হয়েছে।</td></tr>`;
            }
        }

        function handlePostSearch(event) {
            clearTimeout(userSearchTimer);
            userSearchTimer = setTimeout(() => loadPostsTable(1), 300);
        }

        async function executePostAction(postId, action) {
            if (action === 'delete' && !confirm('আপনি কি নিশ্চিত যে পোস্টটি ডিলিট করতে চান?')) return;
            try {
                const res = await apiRequest(`/posts/${postId}/action`, 'POST', { action });
                showToast(res.message);
                loadPostsTable(1);
            } catch (e) {}
        }

        // ==========================================
        // 3. Moderation Queue Logic & Reviewer Metrics
        // ==========================================
        async function loadModerationMetrics() {
            try {
                const res = await apiRequest('/moderation/metrics');
                const m = res?.data || {};
                const revEl = document.getElementById('mod-metric-reviewed');
                const timeEl = document.getElementById('mod-metric-avg-time');
                const fpEl = document.getElementById('mod-metric-fp-rate');
                const otEl = document.getElementById('mod-metric-overturn-rate');
                if (revEl) revEl.textContent = m.total_reviewed ?? 0;
                if (timeEl) timeEl.textContent = `${m.avg_resolution_minutes ?? 0} m`;
                if (fpEl) fpEl.textContent = `${m.false_positive_rate_pct ?? 0}%`;
                if (otEl) otEl.textContent = `${m.appeal_overturn_rate_pct ?? 0}%`;
            } catch (e) {}
        }

        async function loadModerationTable(page = 1) {
            loadModerationMetrics();
            const status = document.getElementById('report-status-filter')?.value || 'pending';
            const reason = document.getElementById('report-reason-filter')?.value || 'all';
            const priority = document.getElementById('report-priority-filter')?.value || 'all';
            const aiCheckbox = document.getElementById('report-ai-flagged-filter');
            const aiFlagged = aiCheckbox ? (aiCheckbox.checked ? '1' : 'all') : (document.getElementById('report-ai-filter')?.value || 'all');
            const tbody = document.getElementById('moderation-table-body');
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>`;

            try {
                const query = new URLSearchParams({
                    page: String(page),
                    status,
                    reason,
                    priority,
                    ai_flagged: aiFlagged
                });
                const res = await apiRequest(`/moderation?${query.toString()}`);
                const reports = res.data || [];

                if (reports.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">বর্তমানে কোনো রিপোর্ট অমীমাংসিত নেই।</td></tr>`;
                    return;
                }

                tbody.innerHTML = reports.map(r => `
                    <tr>
                        <td style="font-weight: 700;">
                            ${escapeHtml(r.reporter?.name || 'অজ্ঞাতনামা')}
                            ${r.ai_flagged ? `<div style="font-size: 10px; color: #9333ea; font-weight: 700; margin-top: 2px;">🤖 AI (${Math.round((r.ai_confidence || 0) * 100)}%)</div>` : ''}
                            ${r.duplicates_count > 0 ? `<div style="font-size: 10px; color: var(--accent-info); font-weight: 700; margin-top: 2px;">🔗 +${r.duplicates_count} ডুপ্লিকেট</div>` : ''}
                        </td>
                        <td>
                            <span style="font-size: 11px; font-weight: 700; color: var(--accent-danger); background: rgba(239,68,68,0.1); padding: 2px 6px; border-radius: 4px;">${escapeHtml(r.reason)}</span>
                            ${r.priority ? `<div style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-top: 2px;">${escapeHtml(r.priority)}</div>` : ''}
                        </td>
                        <td>${escapeHtml(r.target_summary?.content || r.target_summary?.name || 'কন্টেন্ট')}</td>
                        <td style="font-size: 12px; color: var(--text-secondary); max-width: 200px;">
                            ${escapeHtml(r.details || 'কোনো বিবরণ নেই')}
                            ${r.assignee ? `<div style="font-size: 10.5px; color: var(--accent-primary); font-weight: 600; margin-top: 3px;">👤 অ্যাসাইনড: ${escapeHtml(r.assignee.name)}</div>` : ''}
                        </td>
                        <td style="font-size: 12px;">${new Date(r.created_at).toLocaleDateString('bn-BD')}</td>
                        <td>
                            <span class="badge-status ${r.status === 'pending' ? 'badge-pending' : 'badge-active'}">${r.status}</span>
                            ${r.appeal_status ? `<div style="font-size: 10px; font-weight: 700; color: var(--accent-warning); margin-top: 2px;">[আপিল: ${r.appeal_status}]</div>` : ''}
                            ${r.is_false_positive ? `<div style="font-size: 10px; font-weight: 700; color: var(--accent-info); margin-top: 2px;">[False Positive]</div>` : ''}
                        </td>
                        <td style="text-align: right;">
                            <button class="btn-table-action" onclick="scanReportAi(${r.id})" style="color: #9333ea;" title="AI দিয়ে স্ক্যান করুন">🤖 AI স্ক্যান</button>
                            <button class="btn-table-action" onclick="assignReportToMe(${r.id})" title="নিজের নামে অ্যাসাইন করুন">👤 অ্যাসাইন</button>
                            <button class="btn-table-action" onclick="markReportFalsePositive(${r.id})" title="ভুল পজিটিভ হিসেবে চিহ্নিত করুন">🏳️ FP</button>
                            <button class="btn-table-action" onclick="resolveReportAction(${r.id}, 'dismiss')">খারিজ</button>
                            <button class="btn-table-action" onclick="resolveReportAction(${r.id}, 'delete_content')" style="color: var(--accent-danger);">কন্টেন্ট ডিলিট</button>
                            <button class="btn-table-action" onclick="resolveReportAction(${r.id}, 'suspend_creator')" style="color: var(--accent-danger); font-weight: 700;">সাসপেন্ড</button>
                            ${r.appeal_status === 'requested' ? `<button class="btn-table-action" onclick="openAppealModal(${r.id})" style="color: var(--accent-warning); font-weight: 700;">⚖️ আপিল</button>` : ''}
                        </td>
                    </tr>
                `).join('');

                renderPagination('moderation-pagination-bar', res.meta, 'loadModerationTable');
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--accent-danger);">ডাটা লোড করতে সমস্যা হয়েছে।</td></tr>`;
            }
        }

        async function assignReportToMe(reportId) {
            try {
                const res = await apiRequest(`/moderation/${reportId}/assign`, 'POST', {});
                showToast(res.message || 'রিপোর্টটি আপনার নামে অ্যাসাইন করা হয়েছে।');
                loadModerationTable(1);
            } catch (e) {}
        }

        async function markReportFalsePositive(reportId) {
            try {
                const res = await apiRequest(`/moderation/${reportId}/false-positive`, 'POST', {});
                showToast(res.message || 'রিপোর্টটি ফলস-পজিটিভ হিসেবে চিহ্নিত করা হয়েছে।');
                loadModerationTable(1);
            } catch (e) {}
        }

        async function scanReportAi(reportId) {
            try {
                showToast('AI মডেল দ্বারা স্ক্যান করা হচ্ছে...', 'info');
                const res = await apiRequest(`/moderation/${reportId}/scan`, 'POST');
                showToast(`AI স্ক্যান সম্পন্ন: কনফিডেন্স ${Math.round((res.data?.ai_confidence || 0) * 100)}% (${res.data?.ai_reason || 'ক্লিন'})`);
                loadModerationTable(1);
            } catch (e) {}
        }

        function openAppealModal(reportId) {
            document.getElementById('appeal-target-report-id').value = reportId;
            document.getElementById('appeal-modal').classList.add('active');
        }

        function closeAppealModal() {
            document.getElementById('appeal-modal').classList.remove('active');
        }

        async function submitAppealResolution() {
            const reportId = document.getElementById('appeal-target-report-id').value;
            const decision = document.getElementById('appeal-decision-select').value;
            const notes = document.getElementById('appeal-notes').value;

            try {
                const res = await apiRequest(`/moderation/${reportId}/resolve-appeal`, 'POST', {
                    decision,
                    notes
                });
                showToast(res.message);
                closeAppealModal();
                loadModerationTable(1);
            } catch (e) {}
        }

        async function resolveReportAction(reportId, action) {
            const note = prompt('প্রশাসনিক মন্তব্য লিখুন (ঐচ্ছিক):') || '';
            try {
                const res = await apiRequest(`/moderation/${reportId}/resolve`, 'POST', { action, admin_notes: note });
                showToast(res.message);
                loadModerationTable(1);
            } catch (e) {}
        }

        // ==========================================
        // 4. Social Communities (Groups & Pages)
        // ==========================================
        async function loadSocialTable(page = 1) {
            const type = document.getElementById('social-type-filter')?.value || 'groups';
            const tbody = document.getElementById('social-table-body');
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>`;

            try {
                const res = await apiRequest(`/social?type=${type}&page=${page}`);
                const items = res.data || [];

                if (items.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 24px; color: var(--text-muted);">কোনো ${type === 'groups' ? 'গ্রুপ' : 'পেজ'} পাওয়া যায়নি।</td></tr>`;
                    return;
                }

                tbody.innerHTML = items.map(item => `
                    <tr>
                        <td>
                            <div style="font-weight: 700;">${item.name}</div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">${escapeHtml(item.description || item.about || 'বিবরণ নেই')}</div>
                        </td>
                        <td>${item.creator?.name || item.owner?.name || 'N/A'}</td>
                        <td style="font-weight: 600;">${item.members_count || item.followers_count || 0}</td>
                        <td><span style="font-size: 11px; text-transform: uppercase; background: var(--bg-body); padding: 2px 6px; border-radius: 4px;">${item.privacy || 'Public'}</span></td>
                        <td style="font-size: 12px; color: var(--text-secondary);">${new Date(item.created_at).toLocaleDateString('bn-BD')}</td>
                    </tr>
                `).join('');

                renderPagination('social-pagination-bar', res.meta, 'loadSocialTable');
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 24px; color: var(--accent-danger);">ডাটা লোড করতে সমস্যা হয়েছে।</td></tr>`;
            }
        }

        // ==========================================
        // 5. Messaging & Announcements
        // ==========================================
        async function loadMessagingOverview() {
            try {
                const res = await apiRequest('/messaging');
                const tbody = document.getElementById('announcements-table-body');
                const announcements = res.data?.announcements || [];

                if (announcements.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">কোনো সক্রিয় ঘোষণা নেই।</td></tr>`;
                    return;
                }

                tbody.innerHTML = announcements.map(a => `
                    <tr>
                        <td style="font-weight: 700;">${escapeHtml(a.title)}</td>
                        <td style="font-size: 12.5px; max-width: 300px;">${escapeHtml(a.message)}</td>
                        <td><span style="font-size: 11px; font-weight: 700; text-transform: uppercase;">${a.type}</span></td>
                        <td>${a.target_audience}</td>
                        <td>${a.creator?.name || 'System Admin'}</td>
                        <td style="font-size: 12px;">${new Date(a.created_at).toLocaleDateString('bn-BD')}</td>
                    </tr>
                `).join('');
            } catch (e) {}
        }

        function openAnnouncementModal() {
            document.getElementById('announcement-modal').classList.add('active');
        }
        function closeAnnouncementModal() {
            document.getElementById('announcement-modal').classList.remove('active');
        }

        async function submitAnnouncement() {
            const title = document.getElementById('ann-title').value.trim();
            const message = document.getElementById('ann-message').value.trim();
            const type = document.getElementById('ann-type').value;
            const target = document.getElementById('ann-target').value;

            if (!title || !message) {
                showToast('শিরোনাম ও বার্তা পূরণ করুন।', 'error');
                return;
            }

            try {
                const res = await apiRequest('/messaging/announcements', 'POST', {
                    title,
                    message,
                    type,
                    target_audience: target
                });
                showToast(res.message);
                closeAnnouncementModal();
                loadMessagingOverview();
            } catch (e) {}
        }

        // ==========================================
        // 6. Roles & Permissions (RBAC + JIT Elevations)
        // ==========================================
        async function loadRolesMatrix() {
            try {
                const res = await apiRequest('/roles');
                const roles = res.data?.roles || [];
                const elevations = res.data?.elevations || [];
                const tbody = document.getElementById('roles-table-body');

                tbody.innerHTML = roles.map(r => `
                    <tr>
                        <td style="font-weight: 800; color: var(--accent-primary);">${escapeHtml(r.name)}</td>
                        <td style="color: var(--text-secondary);">${escapeHtml(r.description || 'সিস্টেম অ্যাক্সেস রোল')}</td>
                        <td style="font-size: 11px;">
                            ${(r.permissions || []).map(p => `<span style="display: inline-block; background: var(--bg-body); border: 1px solid var(--border-color); padding: 2px 6px; border-radius: 4px; margin: 2px;">${escapeHtml(p.name)}</span>`).join('')}
                        </td>
                    </tr>
                `).join('');

                const elevBody = document.getElementById('elevations-table-body');
                if (elevBody) {
                    if (elevations.length === 0) {
                        elevBody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 20px; color: var(--text-muted);">কোনো সাম্প্রতিক প্রিভিলেজ এলিভেশন রেকর্ড নেই।</td></tr>`;
                    } else {
                        elevBody.innerHTML = elevations.map(el => `
                            <tr>
                                <td><code>#${el.id}</code></td>
                                <td><strong>${escapeHtml(el.user?.name || 'User #' + el.user_id)}</strong></td>
                                <td><span style="font-size: 11px; font-weight: 700; color: var(--accent-primary); text-transform: uppercase;">${escapeHtml(el.elevated_role || el.role_name || '')}</span></td>
                                <td style="font-size: 12px;">${escapeHtml(el.reason)}</td>
                                <td style="font-size: 12px;">${new Date(el.expires_at).toLocaleString('bn-BD')}</td>
                                <td>
                                    <span class="badge-status ${!el.revoked_at ? 'badge-active' : 'badge-suspended'}">
                                        ${el.revoked_at ? 'Revoked' : 'Active'}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    ${!el.revoked_at ? `<button class="btn-table-action" style="color: var(--accent-danger);" onclick="revokePrivilegeElevation(${el.id})">বাতিল করুন</button>` : '—'}
                                </td>
                            </tr>
                        `).join('');
                    }
                }
            } catch (e) {}
        }

        async function grantPrivilegeElevation() {
            const userId = parseInt(document.getElementById('elev-user-id')?.value || '0', 10);
            const elevatedRole = document.getElementById('elev-role-select')?.value || 'SOC_LEAD';
            const durationMinutes = parseInt(document.getElementById('elev-duration-mins')?.value || '60', 10);
            const reason = document.getElementById('elev-reason')?.value?.trim() || '';

            if (!userId || !reason) {
                showToast('ইউজার আইডি এবং বাধ্যতামূলক কারণ প্রদান করুন।', 'error');
                return;
            }

            try {
                const res = await apiRequest('/roles/elevate', 'POST', {
                    user_id: userId,
                    elevated_role: elevatedRole,
                    duration_minutes: durationMinutes,
                    reason
                });
                showToast(res.message || 'টাইম-লিমিটেড প্রিভিলেজ এলিভেশন অনুমোদিত হয়েছে।');
                document.getElementById('elev-reason').value = '';
                loadRolesMatrix();
            } catch (e) {}
        }

        async function revokePrivilegeElevation(elevationId) {
            const reason = prompt(`এলিভেশন #${elevationId} তাৎক্ষণিকভাবে বাতিলের কারণ লিখুন:`) || 'প্রশাসনিক নিরাপত্তা পর্যালোচনা';
            try {
                const res = await apiRequest(`/roles/elevations/${elevationId}/revoke`, 'POST', { reason });
                showToast(res.message || 'প্রিভিলেজ এলিভেশন বাতিল করা হয়েছে।');
                loadRolesMatrix();
            } catch (e) {}
        }

        async function assignRoleQuick() {
            const userId = parseInt(document.getElementById('assign-role-user-id')?.value || '0', 10);
            const roleName = document.getElementById('assign-role-name')?.value || 'ADMIN';

            if (!userId) {
                showToast('অনুগ্রহ করে বৈধ ইউজার আইডি দিন।', 'error');
                return;
            }

            try {
                const res = await apiRequest('/roles/assign', 'POST', {
                    user_id: userId,
                    role_name: roleName
                });
                showToast(res.message || 'ব্যবহারকারীর রোল সফলভাবে আপডেট করা হয়েছে।');
                loadRolesMatrix();
            } catch (e) {}
        }

        // ==========================================
        // 7. Security Operations Center (SOC 2.0)
        // ==========================================
        async function loadSecurityOverview() {
            try {
                const res = await apiRequest('/security/incidents');
                const data = res.data || {};

                document.getElementById('sec-active-sessions').textContent = data.active_sessions_count || 0;
                document.getElementById('sec-critical-incidents').textContent = data.critical_count || 0;
                document.getElementById('sec-high-incidents').textContent = data.high_count || 0;

                // 7-Day Auth Failure Trends Bar Chart
                const trendsContainer = document.getElementById('soc-auth-trends-chart');
                const rawTrends = data.auth_failure_trend || data.auth_failure_trends || [];
                const trendItems = Array.isArray(rawTrends)
                    ? rawTrends.map(item => ({ label: item.date || item.day || '', count: Number(item.failures ?? item.count ?? 0) }))
                    : Object.entries(rawTrends).map(([day, count]) => ({ label: String(day).slice(5), count: Number(count) }));
                if (trendsContainer) {
                    if (trendItems.length === 0) {
                        trendsContainer.innerHTML = `<div style="color: var(--text-muted); font-size: 12px; margin: auto;">গত ৭ দিনে কোনো ব্যর্থ লগইন প্রবণতা সনাক্ত হয়নি।</div>`;
                    } else {
                        const maxVal = Math.max(...trendItems.map(i => i.count), 1);
                        trendsContainer.innerHTML = trendItems.map(item => {
                            const heightPct = Math.max(12, Math.round((item.count / maxVal) * 100));
                            return `
                                <div class="bar-col">
                                    <div class="bar-fill" style="height: ${heightPct}%; background: linear-gradient(180deg, #ef4444, #f97316);" data-val="${item.count} ব্যর্থ লগইন"></div>
                                    <span class="bar-label">${escapeHtml(item.label)}</span>
                                </div>
                            `;
                        }).join('');
                    }
                }

                // SOC Playbooks
                const playbooksContainer = document.getElementById('soc-playbooks-container');
                const playbooks = data.playbooks || {};
                if (playbooksContainer) {
                    const pbEntries = Object.entries(playbooks);
                    playbooksContainer.innerHTML = pbEntries.map(([key, pb]) => `
                        <div style="padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 8px; background: var(--bg-body);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <strong style="font-size: 12.5px; color: var(--text-primary);">🛡️ ${escapeHtml(pb.name || pb.title || key)}</strong>
                                <span class="badge-status ${pb.severity === 'critical' ? 'badge-suspended' : 'badge-pending'}" style="font-size: 10px;">${escapeHtml(pb.severity || 'high')}</span>
                            </div>
                            <ol style="margin: 0; padding-left: 16px; font-size: 11.5px; color: var(--text-secondary);">
                                ${(pb.checklist || pb.steps || []).map(step => `<li style="margin-bottom: 2px;">${escapeHtml(step)}</li>`).join('')}
                            </ol>
                        </div>
                    `).join('');
                }

                // Incidents Table
                const incidentsBody = document.getElementById('sec-incidents-table-body');
                const incidents = data.open_incidents || [];
                if (incidents.length === 0) {
                    incidentsBody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">বর্তমানে কোনো অমীমাংসিত নিরাপত্তা ইনসিডেন্ট নেই। সিস্টেম সুরক্ষিত।</td></tr>`;
                } else {
                    incidentsBody.innerHTML = incidents.map(inc => `
                        <tr>
                            <td><code>#${inc.id}</code></td>
                            <td style="font-weight: 700;">
                                ${escapeHtml(inc.event_type)}
                                ${inc.assigned_to ? `<div style="font-size: 10px; color: var(--accent-primary);">👤 Analyst #${inc.assigned_to}</div>` : ''}
                            </td>
                            <td>
                                <span class="badge-status ${inc.severity === 'critical' ? 'badge-suspended' : (inc.severity === 'high' ? 'badge-pending' : 'badge-active')}">
                                    ${inc.severity.toUpperCase()}
                                </span>
                            </td>
                            <td><code>${inc.ip_address || '127.0.0.1'}</code></td>
                            <td style="font-size: 12px;">${new Date(inc.created_at).toLocaleString('bn-BD')}</td>
                            <td style="text-align: right;">
                                <button class="btn-table-action" onclick="assignIncidentToMe(${inc.id})" title="নিজের নামে অ্যাসাইন করুন">👤 অ্যাসাইন</button>
                                <button class="btn-table-action" onclick="openResolveIncidentModal(${inc.id})" style="color: var(--accent-success); font-weight: 700;">✓ সমাধান</button>
                            </td>
                        </tr>
                    `).join('');
                }

                // High Risk IPs
                const riskBody = document.getElementById('high-risk-ips-table-body');
                const riskIps = data.high_risk_ips || [];
                if (riskIps.length === 0) {
                    riskBody.innerHTML = `<tr><td colspan="3" style="text-align: center; padding: 20px; color: var(--text-muted);">কোনো উচ্চ-ঝুঁকিপূর্ণ আইপি সনাক্ত হয়নি।</td></tr>`;
                } else {
                    riskBody.innerHTML = riskIps.map(ip => `
                        <tr>
                            <td><code>${ip.ip_address}</code></td>
                            <td style="font-weight: 700; color: var(--accent-danger);">${ip.attempts} বার</td>
                            <td><span class="badge-status badge-suspended">উচ্চ ঝুঁকি (Brute Force)</span></td>
                        </tr>
                    `).join('');
                }

                // Failed Logins
                const tbody = document.getElementById('failed-logins-table-body');
                const failed = data.recent_failed_logins || [];
                if (failed.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" style="text-align: center; padding: 20px; color: var(--text-muted);">কোনো ব্যর্থ লগইন রেকর্ড নেই।</td></tr>`;
                } else {
                    tbody.innerHTML = failed.map(f => `
                        <tr>
                            <td style="font-weight: 700;">${escapeHtml(f.email || f.identifier || 'N/A')}</td>
                            <td><code>${f.ip_address || '127.0.0.1'}</code></td>
                            <td style="font-size: 12px;">${new Date(f.attempted_at).toLocaleDateString('bn-BD')}</td>
                        </tr>
                    `).join('');
                }
            } catch (e) {}
        }

        async function assignIncidentToMe(incidentId) {
            try {
                const res = await apiRequest(`/security/incidents/${incidentId}/assign`, 'POST', {});
                showToast(res.message || 'ইনসিডেন্টটি অ্যাসাইন করা হয়েছে।');
                loadSecurityOverview();
            } catch (e) {}
        }

        async function revokeUserSessionsQuick() {
            const userId = parseInt(document.getElementById('soc-revoke-user-id')?.value || '0', 10);
            if (!userId) {
                showToast('অনুগ্রহ করে ইউজার আইডি দিন।', 'error');
                return;
            }
            if (!confirm(`আপনি কি নিশ্চিত যে ইউজার #${userId}-এর সকল সক্রিয় সেশন ও টোকেন তাৎক্ষণিক বাতিল করতে চান?`)) return;

            try {
                const res = await apiRequest('/security/sessions/revoke', 'POST', {
                    user_id: userId,
                    reason: 'জরুরি প্রশাসনিক সেশন টার্মিনেশন'
                });
                showToast(res.message || 'ব্যবহারকারীর সকল সেশন বাতিল করা হয়েছে।');
                loadSecurityOverview();
            } catch (e) {}
        }

        function openResolveIncidentModal(incidentId) {
            document.getElementById('incident-target-id').value = incidentId;
            document.getElementById('incident-notes').value = '';
            document.getElementById('incident-resolve-modal').classList.add('active');
        }

        function closeIncidentModal() {
            document.getElementById('incident-resolve-modal').classList.remove('active');
        }

        async function submitIncidentResolution() {
            const incidentId = document.getElementById('incident-target-id').value;
            const notes = document.getElementById('incident-notes').value;

            try {
                const res = await apiRequest(`/security/incidents/${incidentId}/resolve`, 'POST', { notes });
                showToast(res.message);
                closeIncidentModal();
                loadSecurityOverview();
            } catch (e) {}
        }

        // ==========================================
        // 8. Support Tickets
        // ==========================================
        async function loadSupportTable(page = 1) {
            const status = document.getElementById('ticket-status-filter')?.value || 'all';
            const tbody = document.getElementById('tickets-table-body');
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>`;

            try {
                const res = await apiRequest(`/support/tickets?page=${page}&status=${status}`);
                const tickets = res.data || [];

                if (tickets.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">কোনো সাপোর্ট টিকিট পাওয়া যায়নি।</td></tr>`;
                    return;
                }

                tbody.innerHTML = tickets.map(t => `
                    <tr>
                        <td><code>${t.ticket_number}</code></td>
                        <td>${t.user?.name || t.guest_name || 'গেস্ট'}</td>
                        <td style="font-weight: 700;">${escapeHtml(t.subject)}</td>
                        <td>${t.category}</td>
                        <td><span style="font-size: 11px; font-weight: 700;">${t.priority}</span></td>
                        <td><span class="badge-status ${t.status === 'resolved' ? 'badge-active' : 'badge-pending'}">${t.status}</span></td>
                        <td style="text-align: right;">
                            <button class="btn-table-action" onclick="updateTicketStatus(${t.id}, 'resolved')">✓ সমাধান</button>
                        </td>
                    </tr>
                `).join('');

                renderPagination('tickets-pagination-bar', res.meta, 'loadSupportTable');
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--accent-danger);">ডাটা লোড করতে সমস্যা হয়েছে।</td></tr>`;
            }
        }

        async function updateTicketStatus(ticketId, status) {
            const note = prompt('অভ্যন্তরীণ নোট যুক্ত করুন (ঐচ্ছিক):') || '';
            try {
                const res = await apiRequest(`/support/tickets/${ticketId}/status`, 'POST', {
                    status,
                    internal_notes: note
                });
                showToast(res.message);
                loadSupportTable(1);
            } catch (e) {}
        }

        // ==========================================
        // 9. Monetization & Ads
        // ==========================================
        async function loadMonetization() {
            try {
                const res = await apiRequest('/monetization');
                const payouts = res.data?.payout_requests || [];
                const tbody = document.getElementById('payouts-table-body');

                if (payouts.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">বর্তমানে কোনো পেন্ডিং পে-আউট রিকোয়েস্ট নেই।</td></tr>`;
                    return;
                }

                tbody.innerHTML = payouts.map(p => `
                    <tr>
                        <td style="font-weight: 700;">${p.user?.name || 'Creator'}</td>
                        <td>⭐ ${p.coins_amount || 0} কয়েন</td>
                        <td style="font-weight: 700; color: var(--accent-success);">৳ ${p.amount || 0} BDT</td>
                        <td>${p.payment_method || 'bKash'} (${p.account_details || ''})</td>
                        <td><span class="badge-status ${p.status === 'approved' ? 'badge-active' : 'badge-pending'}">${p.status}</span></td>
                        <td style="text-align: right;">
                            <button class="btn-table-action" onclick="actionPayoutRequest(${p.id}, 'approve')">অনুমোদন</button>
                            <button class="btn-table-action" onclick="actionPayoutRequest(${p.id}, 'reject')" style="color: var(--accent-danger);">বাতিল</button>
                        </td>
                    </tr>
                `).join('');
            } catch (e) {}
        }

        async function actionPayoutRequest(id, action) {
            if (!confirm(`আপনি কি পে-আউট রিকোয়েস্টটি '${action}' করতে চান?`)) return;
            try {
                const res = await apiRequest(`/monetization/payouts/${id}/action`, 'POST', { action });
                showToast(res.message);
                loadMonetization();
            } catch (e) {}
        }

        // ==========================================
        // 10. Feature Flags & Platform Settings
        // ==========================================
        async function loadFeatureFlags() {
            const tbody = document.getElementById('flags-table-body');
            if (!tbody) return;
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 24px;">ফিচার ফ্ল্যাগ লোড হচ্ছে...</td></tr>`;

            try {
                const res = await apiRequest('/flags');
                const flags = res.data || [];

                if (flags.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 24px; color: var(--text-muted);">কোনো ফিচার ফ্ল্যাগ নিবন্ধিত নেই। উপরের ফর্ম থেকে নতুন ফ্ল্যাগ তৈরি করুন।</td></tr>`;
                    return;
                }

                tbody.innerHTML = flags.map(f => `
                    <tr>
                        <td><code>${escapeHtml(f.key)}</code></td>
                        <td>
                            <div style="font-weight: 700;">${escapeHtml(f.name)}</div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">${escapeHtml(f.description || '')}</div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 80px; height: 6px; background: var(--border-color); border-radius: 999px; overflow: hidden;">
                                    <div style="width: ${f.rollout_percentage || 0}%; height: 100%; background: var(--accent-primary);"></div>
                                </div>
                                <strong style="font-size: 12px;">${f.rollout_percentage ?? 0}%</strong>
                            </div>
                        </td>
                        <td>
                            <span class="badge-status ${f.is_enabled ? 'badge-active' : 'badge-suspended'}">
                                ${f.is_enabled ? 'ENABLED' : 'DISABLED'}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button class="btn-table-action" onclick="toggleFeatureFlag('${escapeHtml(f.key)}', ${!f.is_enabled}, ${f.rollout_percentage ?? 100})">
                                ${f.is_enabled ? '⏸ নিষ্ক্রিয় করুন' : '▶ সক্রিয় করুন'}
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 24px; color: var(--accent-danger);">ফিচার ফ্ল্যাগ লোড করতে ব্যর্থ।</td></tr>`;
            }
        }

        async function submitNewFeatureFlag() {
            const key = document.getElementById('new-flag-key')?.value?.trim() || '';
            const name = document.getElementById('new-flag-name')?.value?.trim() || '';
            const rolloutPercentage = parseInt(document.getElementById('new-flag-rollout')?.value || '100', 10);
            const description = document.getElementById('new-flag-desc')?.value?.trim() || '';

            if (!key || !name) {
                showToast('ফ্ল্যাগ কী এবং নাম আবশ্যক।', 'error');
                return;
            }

            try {
                const res = await apiRequest('/flags', 'POST', {
                    key,
                    name,
                    description,
                    is_enabled: true,
                    rollout_percentage: rolloutPercentage
                });
                showToast(res.message || 'নতুন ফিচার ফ্ল্যাগ সংরক্ষিত হয়েছে।');
                document.getElementById('new-flag-key').value = '';
                document.getElementById('new-flag-name').value = '';
                document.getElementById('new-flag-desc').value = '';
                loadFeatureFlags();
            } catch (e) {}
        }

        async function toggleFeatureFlag(key, isEnabled, rolloutPct) {
            try {
                const res = await apiRequest(`/flags/${encodeURIComponent(key)}`, 'POST', {
                    is_enabled: isEnabled,
                    rollout_percentage: rolloutPct
                });
                showToast(res.message || 'ফিচার ফ্ল্যাগ আপডেট করা হয়েছে।');
                loadFeatureFlags();
            } catch (e) {}
        }

        async function viewSettingHistory(key) {
            try {
                const res = await apiRequest(`/settings/${encodeURIComponent(key)}/history`);
                const items = res.data || [];
                const html = items.length === 0
                    ? `<div style="color: var(--text-muted); padding: 20px; text-align: center;">সেটিং <code>${escapeHtml(key)}</code>-এর কোনো পূর্ববর্তী পরিবর্তন ইতিহাস পাওয়া যায়নি।</div>`
                    : items.map(h => `
                        <div style="padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 10px; background: var(--bg-body);">
                            <div style="display: flex; justify-content: space-between; font-size: 11.5px; color: var(--text-muted); margin-bottom: 6px;">
                                <span>পরিবর্তনকারী: <strong>${escapeHtml(h.changer?.name || 'Admin #' + (h.changed_by || ''))}</strong></span>
                                <span>${new Date(h.created_at).toLocaleString('bn-BD')}</span>
                            </div>
                            <div style="font-size: 12px; margin-bottom: 4px;">পূর্বের মান: <code style="color: var(--accent-danger);">${escapeHtml(String(h.old_value ?? 'null'))}</code></div>
                            <div style="font-size: 12px;">নতুন মান: <code style="color: var(--accent-success);">${escapeHtml(String(h.new_value ?? 'null'))}</code></div>
                        </div>
                    `).join('');
                openContextDrawer(`📜 সেটিং ইতিহাস: ${key}`, html);
            } catch (e) {}
        }

        async function savePlatformSettings() {
            const settingsPayload = [
                { key: 'site_name', value: document.getElementById('set-site_name').value },
                { key: 'site_tagline', value: document.getElementById('set-site_tagline').value },
                { key: 'contact_email', value: document.getElementById('set-contact_email').value },
                { key: 'max_upload_size_mb', value: document.getElementById('set-max_upload_size_mb').value },
                { key: 'registration_enabled', value: document.getElementById('set-registration_enabled').value },
                { key: 'maintenance_mode', value: document.getElementById('set-maintenance_mode').value }
            ];

            try {
                const res = await apiRequest('/settings', 'POST', { settings: settingsPayload });
                showToast(res.message);
            } catch (e) {}
        }

        // ==========================================
        // 11. Developer Actions
        // ==========================================
        async function triggerDevAction(action) {
            try {
                const res = await apiRequest('/developer/action', 'POST', { action });
                showToast(res.message);
            } catch (e) {}
        }

        // ==========================================
        // 12. Audit Logs, Diff Drawer & Checkpoints
        // ==========================================
        let auditLogsCache = [];

        async function loadAuditCheckpoints() {
            const cpBody = document.getElementById('audit-checkpoints-table-body');
            if (!cpBody) return;
            try {
                const res = await apiRequest('/audit/checkpoints');
                const cps = res.data || [];
                if (cps.length === 0) {
                    cpBody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 18px; color: var(--text-muted);">কোনো ক্রিপ্টোগ্রাফিক চেকপয়েন্ট এখনও তৈরি হয়নি।</td></tr>`;
                } else {
                    cpBody.innerHTML = cps.map(c => `
                        <tr>
                            <td><code>#${c.checkpoint_number ?? c.id}</code></td>
                            <td>L#${c.record_start_id ?? c.start_log_id} → L#${c.record_end_id ?? c.end_log_id}</td>
                            <td><strong>${c.record_count ?? c.logs_count ?? 0}</strong> টি</td>
                            <td><code style="font-size: 11px;">${escapeHtml((c.root_hash || c.merkle_root || '').slice(0, 20))}...</code></td>
                            <td><code style="font-size: 11px; color: var(--accent-primary);">${escapeHtml((c.signature || '').slice(0, 20))}...</code></td>
                            <td style="font-size: 12px;">${escapeHtml(c.creator?.name || 'System')} (${new Date(c.checkpoint_at || c.created_at).toLocaleString('bn-BD')})</td>
                        </tr>
                    `).join('');
                }
            } catch (e) {}
        }

        async function createAuditCheckpoint() {
            try {
                const res = await apiRequest('/audit/checkpoints', 'POST', {});
                showToast(res.message || 'নতুন ক্রিপ্টোগ্রাফিক অডিট চেকপয়েন্ট সিল করা হয়েছে।');
                loadAuditCheckpoints();
            } catch (e) {}
        }

        function exportAuditLogsCsv() {
            window.location.href = `${API_BASE}/audit/export`;
        }

        async function loadAuditLogs(page = 1) {
            loadAuditCheckpoints();
            const actionFilter = document.getElementById('audit-action-filter')?.value?.trim() || '';
            const tbody = document.getElementById('audit-table-body');
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px;">লোড হচ্ছে...</td></tr>`;

            try {
                const res = await apiRequest(`/audit/logs?page=${page}&action=${encodeURIComponent(actionFilter)}`);
                auditLogsCache = res.data || [];

                if (auditLogsCache.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">কোনো অডিট লগ রেকর্ড নেই।</td></tr>`;
                    return;
                }

                tbody.innerHTML = auditLogsCache.map((l, idx) => `
                    <tr>
                        <td style="font-weight: 700;">${escapeHtml(l.user?.name || 'সিস্টেম অ্যাডমিন')}</td>
                        <td><code>${escapeHtml(l.action)}</code></td>
                        <td>${l.entity_type ? escapeHtml(l.entity_type.split('\\').pop()) : 'System'} #${l.entity_id || 0}</td>
                        <td><code>${l.ip_address || '127.0.0.1'}</code></td>
                        <td style="font-size: 12px; color: var(--text-secondary);">${new Date(l.created_at).toLocaleString('bn-BD')}</td>
                        <td style="text-align: right;">
                            <button class="btn-table-action" onclick="inspectAuditLogDiff(${idx})">🔍 ডিফ</button>
                        </td>
                    </tr>
                `).join('');

                renderPagination('audit-pagination-bar', res.meta, 'loadAuditLogs');
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: var(--accent-danger);">ডাটা লোড করতে সমস্যা হয়েছে।</td></tr>`;
            }
        }

        function inspectAuditLogDiff(idx) {
            const log = auditLogsCache[idx];
            if (!log) return;
            const oldJson = JSON.stringify(log.old_values || {}, null, 2);
            const newJson = JSON.stringify(log.new_values || {}, null, 2);
            const html = `
                <div style="margin-bottom: 14px; font-size: 12.5px;">
                    <div><strong>অ্যাকশন:</strong> <code>${escapeHtml(log.action)}</code></div>
                    <div><strong>অ্যাডমিন:</strong> ${escapeHtml(log.user?.name || 'System')} | <strong>IP:</strong> <code>${escapeHtml(log.ip_address || '127.0.0.1')}</code></div>
                    <div><strong>সময়:</strong> ${new Date(log.created_at).toLocaleString('bn-BD')}</div>
                    ${log.signature_hash ? `<div style="margin-top: 4px;"><strong>SHA-256 Hash:</strong> <code style="font-size: 10.5px; word-break: break-all;">${escapeHtml(log.signature_hash)}</code></div>` : ''}
                </div>
                <div style="font-weight: 700; font-size: 12px; color: var(--accent-danger); margin-bottom: 4px;">পূর্ববর্তী মান (Before / Old Values):</div>
                <pre style="background: var(--bg-body); padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); font-size: 11.5px; overflow-x: auto; margin-bottom: 12px;">${escapeHtml(oldJson)}</pre>
                <div style="font-weight: 700; font-size: 12px; color: var(--accent-success); margin-bottom: 4px;">পরিবর্তিত মান (After / New Values):</div>
                <pre style="background: var(--bg-body); padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); font-size: 11.5px; overflow-x: auto;">${escapeHtml(newJson)}</pre>
            `;
            openContextDrawer(`🔍 অডিট লগ ডিফ #${log.id}`, html);
        }

        // ==========================================
        // 13. Global Search & Command Palette (with AbortController & Keyboard Nav)
        // ==========================================
        const globalSearchInput = document.getElementById('global-search-input');
        const searchDropdown = document.getElementById('search-results-dropdown');
        let searchDebounce;
        let activeSearchAbortController = null;

        globalSearchInput?.addEventListener('input', (e) => {
            clearTimeout(searchDebounce);
            const query = e.target.value.trim();
            if (query.length < 2) {
                searchDropdown.style.display = 'none';
                if (activeSearchAbortController) activeSearchAbortController.abort();
                return;
            }

            searchDebounce = setTimeout(async () => {
                if (activeSearchAbortController) {
                    activeSearchAbortController.abort();
                }
                activeSearchAbortController = new AbortController();

                try {
                    const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken };
                    if (adminToken) headers['Authorization'] = `Bearer ${adminToken}`;
                    const raw = await fetch(`${API_BASE}/search?q=${encodeURIComponent(query)}`, {
                        headers,
                        signal: activeSearchAbortController.signal
                    });
                    if (!raw.ok) return;
                    const res = await raw.json();
                    const data = res.data || {};
                    const users = data.users || [];
                    const posts = data.posts || [];
                    const groups = data.groups || [];
                    const tickets = data.tickets || [];

                    let html = '';
                    if (users.length > 0) {
                        html += `<div class="search-group-title">ব্যবহারকারী (${users.length})</div>`;
                        users.forEach(u => {
                            html += `<div class="search-result-item" onclick="openDossierModal(${u.id}); searchDropdown.style.display='none';">👤 ${escapeHtml(u.name)} (@${escapeHtml(u.username || '')})</div>`;
                        });
                    }
                    if (posts.length > 0) {
                        html += `<div class="search-group-title">পোস্টসমূহ (${posts.length})</div>`;
                        posts.forEach(p => {
                            html += `<div class="search-result-item" onclick="switchSection('content'); searchDropdown.style.display='none';">📝 ${escapeHtml(p.author || '')}: ${escapeHtml(p.snippet || '')}</div>`;
                        });
                    }
                    if (groups.length > 0) {
                        html += `<div class="search-group-title">গ্রুপসমূহ (${groups.length})</div>`;
                        groups.forEach(g => {
                            html += `<div class="search-result-item" onclick="switchSection('social'); searchDropdown.style.display='none';">🌐 ${escapeHtml(g.name)}</div>`;
                        });
                    }
                    if (tickets.length > 0) {
                        html += `<div class="search-group-title">সাপোর্ট টিকিট (${tickets.length})</div>`;
                        tickets.forEach(t => {
                            html += `<div class="search-result-item" onclick="switchSection('support'); searchDropdown.style.display='none';">🎫 ${escapeHtml(t.ticket_number)}: ${escapeHtml(t.subject)}</div>`;
                        });
                    }

                    if (html === '') {
                        html = `<div style="padding: 12px; font-size: 13px; color: var(--text-muted); text-align: center;">কোনো ফলাফল পাওয়া যায়নি।</div>`;
                    }

                    searchDropdown.innerHTML = html;
                    searchDropdown.style.display = 'block';
                } catch (e) {
                    if (e.name !== 'AbortError') console.error(e);
                }
            }, 250);
        });

        // Close dropdown when clicked outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.global-search-box')) {
                searchDropdown.style.display = 'none';
            }
        });

        // Keyboard Shortcut: Cmd/Ctrl + K for Command Palette & Escape for Drawers/Modals
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                openCommandPalette();
            }
            if (e.key === 'Escape') {
                closeCommandPalette();
                closeContextDrawer();
                closeMobileSidebar();
                if (searchDropdown) searchDropdown.style.display = 'none';
            }
        });

        // Enable Enter/Space keyboard activation on sidebar navigation items
        document.querySelectorAll('#sidebar .nav-item[tabindex="0"]').forEach(item => {
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    item.click();
                }
            });
        });

        let cpActiveIndex = 0;

        function openCommandPalette() {
            document.getElementById('command-palette-overlay').classList.add('active');
            document.getElementById('command-palette').style.display = 'block';
            cpActiveIndex = 0;
            updateCommandPaletteHighlight();
            setTimeout(() => document.getElementById('cp-input')?.focus(), 50);
        }

        function closeCommandPalette() {
            document.getElementById('command-palette-overlay').classList.remove('active');
            document.getElementById('command-palette').style.display = 'none';
        }

        function getVisibleCommandItems() {
            return Array.from(document.querySelectorAll('#cp-list .cp-item')).filter(el => el.style.display !== 'none');
        }

        function updateCommandPaletteHighlight() {
            const visible = getVisibleCommandItems();
            document.querySelectorAll('#cp-list .cp-item').forEach(el => el.classList.remove('active-key'));
            if (visible.length > 0) {
                cpActiveIndex = Math.max(0, Math.min(cpActiveIndex, visible.length - 1));
                visible[cpActiveIndex].classList.add('active-key');
                visible[cpActiveIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        function handleCommandPaletteKeydown(e) {
            const visible = getVisibleCommandItems();
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                cpActiveIndex = (cpActiveIndex + 1) % Math.max(1, visible.length);
                updateCommandPaletteHighlight();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                cpActiveIndex = (cpActiveIndex - 1 + Math.max(1, visible.length)) % Math.max(1, visible.length);
                updateCommandPaletteHighlight();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (visible[cpActiveIndex]) {
                    visible[cpActiveIndex].click();
                }
            }
        }

        function filterCommandPalette(e) {
            const q = e.target.value.toLowerCase();
            const items = document.querySelectorAll('#cp-list .cp-item');
            items.forEach(item => {
                const match = item.textContent.toLowerCase().includes(q);
                item.style.display = match ? 'flex' : 'none';
            });
            cpActiveIndex = 0;
            updateCommandPaletteHighlight();
        }

        // Export Data
        function exportData(type) {
            window.location.href = `/api/v2/admin/auth/export?type=${type}`;
        }

        // Logout
        function handleLogout() {
            if (!confirm('আপনি কি নিশ্চিত যে অ্যাডমিন প্যানেল থেকে লগআউট করতে চান?')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/logout';
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = csrfToken;
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }

        // Reusable Pagination Generator
        function renderPagination(containerId, meta, functionName) {
            const container = document.getElementById(containerId);
            if (!container || !meta) return;

            const current = meta.current_page || 1;
            const last = meta.last_page || 1;
            const total = meta.total || 0;

            container.innerHTML = `
                <div>মোট <strong>${total}</strong> টির মধ্যে পেজ <strong>${current}</strong> / <strong>${last}</strong></div>
                <div class="pagination-controls">
                    <button class="btn-table-action" ${current <= 1 ? 'disabled' : ''} onclick="${functionName}(${current - 1})">পূর্ববর্তী</button>
                    <button class="btn-table-action" ${current >= last ? 'disabled' : ''} onclick="${functionName}(${current + 1})">পরবর্তী</button>
                </div>
            `;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // ==========================================
        // Phase 2: Settings Rollback Handlers
        // ==========================================
        function openRollbackModal() {
            document.getElementById('rollback-modal').classList.add('active');
        }

        function closeRollbackModal() {
            document.getElementById('rollback-modal').classList.remove('active');
        }

        async function submitSettingRollback() {
            const key = document.getElementById('rollback-setting-key').value;
            try {
                const res = await apiRequest('/settings/rollback', 'POST', { key });
                showToast(res.message);
                closeRollbackModal();
            } catch (e) {}
        }

        // ==========================================
        // Phase 2: Tamper-Evident Audit Integrity
        // ==========================================
        async function verifyAuditIntegrity() {
            const banner = document.getElementById('audit-integrity-banner');
            banner.style.display = 'block';
            banner.style.background = 'rgba(79, 70, 229, 0.1)';
            banner.style.color = 'var(--accent-primary)';
            banner.textContent = 'ক্রিপ্টোগ্রাফিক চেইন ও হ্যাশ অখণ্ডতা যাচাই করা হচ্ছে...';

            try {
                const res = await apiRequest('/audit/verify-integrity');
                const data = res.data || {};

                if (data.verified) {
                    banner.style.background = 'rgba(16, 185, 129, 0.1)';
                    banner.style.color = 'var(--accent-success)';
                    banner.innerHTML = `🛡️ ${data.message} (মোট যাচাইকৃত লগ: <strong>${data.total_checked}</strong> টি)`;
                    showToast('অডিট লগ চেইন ক্রিপ্টোগ্রাফিকভাবে সম্পূর্ণ সুরক্ষিত।');
                } else {
                    banner.style.background = 'rgba(239, 68, 68, 0.1)';
                    banner.style.color = 'var(--accent-danger)';
                    banner.innerHTML = `🚨 সতর্কতা: ${data.message}`;
                    showToast('অডিট লগে তথ্য বিকৃতি বা টেম্পারিং সনাক্ত হয়েছে!', 'error');
                }
            } catch (e) {
                banner.style.display = 'none';
            }
        }

        // ==========================================
        // Phase 2/3/4: Executive Intelligence Analytics
        // ==========================================
        let currentIntelligenceRange = '14d';
        function changeIntelligenceRange(range, btn) {
            currentIntelligenceRange = range;
            document.querySelectorAll('.range-selector-pills .pill-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            loadExecutiveIntelligence(range);
        }

        async function loadExecutiveIntelligence(range = currentIntelligenceRange) {
            try {
                const res = await apiRequest(`/intelligence?range=${range}`);
                const data = res.data || {};

                // Freshness Badge
                const freshnessBadge = document.getElementById('intel-freshness-badge');
                if (freshnessBadge && data.meta) {
                    const ts = data.meta.generated_at ? new Date(data.meta.generated_at).toLocaleTimeString('bn-BD') : '';
                    freshnessBadge.textContent = `${data.meta.cached ? '⚡ ক্যাশড' : '🟢 লাইভ'} (${ts})`;
                }

                document.getElementById('intel-dau').textContent = data.users?.dau ?? 0;
                document.getElementById('intel-wau').textContent = data.users?.wau ?? 0;
                document.getElementById('intel-mau').textContent = data.users?.mau ?? 0;
                document.getElementById('intel-stickiness').textContent = `${data.users?.stickiness_pct ?? 0}%`;

                document.getElementById('intel-users-count').textContent = `নিবন্ধিত: ${data.users?.new_period ?? 0} জন`;
                const userGrowth = data.users?.growth_pct ?? 0;
                const userGrowthEl = document.getElementById('intel-users-growth');
                userGrowthEl.textContent = `${userGrowth >= 0 ? '+' : ''}${userGrowth}%`;
                userGrowthEl.style.color = userGrowth >= 0 ? 'var(--accent-success)' : 'var(--accent-danger)';

                document.getElementById('intel-posts-count').textContent = `পোস্ট: ${data.content?.new_period_posts ?? 0} টি`;
                const postGrowth = data.content?.posts_growth_pct ?? 0;
                const postGrowthEl = document.getElementById('intel-posts-growth');
                postGrowthEl.textContent = `${postGrowth >= 0 ? '+' : ''}${postGrowth}%`;
                postGrowthEl.style.color = postGrowth >= 0 ? 'var(--accent-success)' : 'var(--accent-danger)';

                document.getElementById('intel-db-latency').textContent = `${data.infrastructure?.db_latency_ms ?? 0} ms`;
                document.getElementById('intel-ad-revenue').textContent = `BDT ${Number(data.monetization?.total_ad_revenue ?? 0).toLocaleString('bn-BD')}`;
                document.getElementById('intel-payout-approved').textContent = `BDT ${Number(data.monetization?.total_payout_approved ?? 0).toLocaleString('bn-BD')}`;
                document.getElementById('intel-pending-reports').textContent = `${data.moderation?.pending ?? 0} টি (AI চিহ্নিত: ${data.moderation?.ai_flagged_count ?? 0})`;

                // 4-Step Conversion Funnel
                const funnelEl = document.getElementById('intel-funnel-container');
                const funnelSteps = data.funnel || [];
                if (funnelEl) {
                    if (funnelSteps.length === 0) {
                        funnelEl.innerHTML = `<div style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 20px;">ফানেল ডাটা পাওয়া যায়নি।</div>`;
                    } else {
                        funnelEl.innerHTML = funnelSteps.map(step => `
                            <div style="margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12.5px; font-weight: 600; margin-bottom: 4px;">
                                    <span>${escapeHtml(step.step)}</span>
                                    <span><strong>${step.count}</strong> জন (${step.conversion_rate_pct}%)</span>
                                </div>
                                <div style="width: 100%; height: 10px; background: var(--bg-body); border-radius: 999px; overflow: hidden; border: 1px solid var(--border-color);">
                                    <div style="width: ${Math.max(4, step.conversion_rate_pct)}%; height: 100%; background: linear-gradient(90deg, var(--accent-primary), var(--accent-info)); border-radius: 999px;"></div>
                                </div>
                            </div>
                        `).join('');
                    }
                }

                // Anomaly Detection Cards
                const anomaliesEl = document.getElementById('intel-anomalies-container');
                const anomalies = data.anomalies || [];
                if (anomaliesEl) {
                    if (anomalies.length === 0) {
                        anomaliesEl.innerHTML = `<div style="padding: 20px; text-align: center; color: var(--accent-success); font-weight: 600;">✓ কোনো পরিসংখ্যানগত অসঙ্গতি সনাক্ত হয়নি।</div>`;
                    } else {
                        anomaliesEl.innerHTML = anomalies.map(a => {
                            const borderCol = a.severity === 'critical' ? 'var(--accent-danger)' : (a.severity === 'warning' ? 'var(--accent-warning)' : 'var(--accent-success)');
                            return `
                                <div style="padding: 12px 14px; border-left: 4px solid ${borderCol}; background: var(--bg-body); border-radius: 6px; margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                        <strong style="font-size: 13px;">${escapeHtml(a.title)}</strong>
                                        <span class="badge-status ${a.severity === 'critical' ? 'badge-suspended' : (a.severity === 'warning' ? 'badge-pending' : 'badge-active')}">${escapeHtml(a.severity)}</span>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-secondary);">${escapeHtml(a.detail)}</div>
                                </div>
                            `;
                        }).join('');
                    }
                }

                // 4-Week Retention Cohort Matrix
                const cohortBody = document.getElementById('intel-cohorts-tbody');
                const cohorts = data.cohorts || [];
                if (cohortBody) {
                    if (cohorts.length === 0) {
                        cohortBody.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 16px; color: var(--text-muted);">কোহর্ট ডাটা নেই।</td></tr>`;
                    } else {
                        cohortBody.innerHTML = cohorts.map(c => `
                            <tr>
                                <td style="font-weight: 600;">${escapeHtml(c.cohort)}</td>
                                <td>${c.size} জন</td>
                                <td>${c.retained_7d} জন</td>
                                <td>
                                    <span class="badge-status ${c.retention_rate_pct >= 50 ? 'badge-active' : 'badge-pending'}">
                                        ${c.retention_rate_pct}%
                                    </span>
                                </td>
                            </tr>
                        `).join('');
                    }
                }

                // Trending Hashtags & Active Communities
                const tagsEl = document.getElementById('intel-trending-hashtags');
                const commEl = document.getElementById('intel-top-communities');
                const hashtags = data.trending?.hashtags || data.trending_topics || [];
                const communities = data.trending?.communities || data.top_communities || [];
                if (tagsEl) {
                    tagsEl.innerHTML = hashtags.length === 0
                        ? `<span style="font-size: 12px; color: var(--text-muted);">কোনো ট্রেন্ডিং হ্যাশট্যাগ নেই</span>`
                        : hashtags.map(h => {
                            const label = h.topic || ('#' + (h.tag || h.name || ''));
                            const count = h.post_count ?? h.count ?? h.posts_count ?? 0;
                            return `<span style="background: rgba(79,70,229,0.1); color: var(--accent-primary); padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700;">${escapeHtml(label)} (${count})</span>`;
                        }).join('');
                }
                if (commEl) {
                    commEl.innerHTML = communities.length === 0
                        ? `<div style="font-size: 12px; color: var(--text-muted);">কোনো কমিউনিটি রেকর্ড নেই</div>`
                        : communities.map(g => `<div style="display: flex; justify-content: space-between; font-size: 12.5px; padding: 6px 0; border-bottom: 1px solid var(--border-color);"><strong>🌐 ${escapeHtml(g.name)}</strong><span>${g.members_count ?? g.followers_count ?? 0} সদস্য</span></div>`).join('');
                }
            } catch (e) {}
        }

        function exportIntelligenceCsv() {
            window.location.href = `${API_BASE}/intelligence/export?range=${currentIntelligenceRange}`;
        }

        // ==========================================
        // Phase 2: Admin Alerts Hub (Live Polling)
        // ==========================================
        function toggleAlertsDropdown() {
            const dropdown = document.getElementById('alerts-dropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }

        async function pollAlerts() {
            try {
                const res = await apiRequest('/alerts');
                const data = res.data || {};
                const alerts = data.alerts || [];
                const unread = data.unread_count || 0;

                const badge = document.getElementById('alerts-unread-badge');
                if (badge) {
                    if (unread > 0) {
                        badge.textContent = unread > 99 ? '99+' : unread;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }

                const list = document.getElementById('alerts-list');
                if (list) {
                    if (alerts.length === 0) {
                        list.innerHTML = `<div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 12px;">কোনো নতুন অ্যালার্ট নেই</div>`;
                    } else {
                        list.innerHTML = alerts.map(a => `
                            <div class="alert-item ${a.read_at ? '' : 'unread'}" onclick="markAlertRead(${a.id})">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2px;">
                                    <strong style="font-size: 12px; color: ${a.severity === 'critical' ? 'var(--accent-danger)' : (a.severity === 'warning' ? 'var(--accent-warning)' : 'var(--text-primary)')};">${escapeHtml(a.title)}</strong>
                                    <span style="font-size: 10px; color: var(--text-muted);">${new Date(a.created_at).toLocaleTimeString('bn-BD', {hour: '2-digit', minute: '2-digit'})}</span>
                                </div>
                                <div style="font-size: 11.5px; color: var(--text-secondary);">${escapeHtml(a.message)}</div>
                            </div>
                        `).join('');
                    }
                }
            } catch (e) {}
        }

        async function markAlertRead(alertId) {
            try {
                await apiRequest(`/alerts/${alertId}/read`, 'POST');
                pollAlerts();
            } catch (e) {}
        }

        async function markAllAlertsAsRead() {
            try {
                await apiRequest('/alerts/read-all', 'POST');
                showToast('সকল অ্যালার্ট পঠিত হয়েছে।');
                pollAlerts();
            } catch (e) {}
        }

        // Close alerts dropdown on click outside
        document.addEventListener('click', (e) => {
            const wrapper = document.querySelector('.alerts-dropdown-wrapper');
            const dropdown = document.getElementById('alerts-dropdown');
            if (dropdown && wrapper && !wrapper.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Initialize Alerts Polling
        pollAlerts();
        setInterval(pollAlerts, 30000);

        // ==========================================
        // PHASE 3 & 4: NEXT-GEN COMMAND CENTER LOGIC
        // ==========================================

        // 1. Trust & Safety & Abuse Detection (Real Database Restrictions)
        let failedJobsCache = [];

        async function loadTrustSafety() {
            try {
                // Fetch metrics
                const metricsRes = await apiRequest('/moderation/metrics');
                if (metricsRes?.data) {
                    document.getElementById('ts-false-positive-rate').textContent = (metricsRes.data.false_positive_rate_pct || 0) + '%';
                }

                const tbody = document.getElementById('ts-restrictions-table-body');
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px;">সীমাবদ্ধতা তালিকা লোড হচ্ছে...</td></tr>`;

                // Fetch high-risk users count
                const usersRes = await apiRequest('/users?per_page=20');
                const users = usersRes?.data || [];
                let highRiskCount = 0;
                users.forEach(u => {
                    if ((u.risk_score || 0) >= 75) highRiskCount++;
                });
                document.getElementById('ts-high-risk-count').textContent = highRiskCount;

                // Load real UserRestriction records from database
                const restRes = await apiRequest('/abuse/restrictions');
                const restrictions = restRes?.data || [];
                const activeCount = restrictions.filter(r => r.is_active).length;
                document.getElementById('ts-active-restrictions-count').textContent = activeCount;

                if (restrictions.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">বর্তমানে কোনো ব্যবহারকারীর উপর সক্রিয় সীমাবদ্ধতা নেই।</td></tr>`;
                    return;
                }

                tbody.innerHTML = restrictions.map(r => `
                    <tr>
                        <td><strong>#${r.id}</strong></td>
                        <td>
                            <div style="font-weight: 700;">${escapeHtml(r.user?.name || 'User #' + r.user_id)}</div>
                            <button class="btn-table-action" onclick="openDossierModal(${r.user_id})" style="font-size: 11px; margin-top: 4px;">🕵️ ডসিয়ার দেখুন</button>
                        </td>
                        <td><span class="badge-status badge-suspended">${escapeHtml(r.restriction_type)}</span></td>
                        <td>${escapeHtml(r.reason)}</td>
                        <td>${r.expires_at ? new Date(r.expires_at).toLocaleString('bn-BD') : 'স্থায়ী'}</td>
                        <td>
                            <span class="badge-status ${r.is_active ? 'badge-suspended' : 'badge-active'}">
                                ${r.is_active ? 'Active' : 'Overridden'}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            ${r.is_active ? `<button class="btn-secondary" onclick="openOverrideModal(${r.id})" style="font-size: 12px; padding: 4px 10px;">🔓 ওভাররাইড করুন</button>` : '—'}
                        </td>
                    </tr>
                `).join('');
            } catch (e) {
                console.error(e);
            }
        }

        async function evaluateUserRiskQuick() {
            const userId = document.getElementById('ts-eval-user-id')?.value;
            if (!userId) {
                showToast('অনুগ্রহ করে ইউজার আইডি প্রদান করুন।', 'error');
                return;
            }

            const container = document.getElementById('ts-eval-result');
            container.style.display = 'block';
            container.innerHTML = '<div style="padding: 14px; text-align: center;">রিস্ক স্কোর ও ফ্যাক্টরস মূল্যায়ন হচ্ছে...</div>';

            try {
                const res = await apiRequest(`/abuse/users/${userId}/risk`);
                const risk = res?.data || {};
                const color = risk.level === 'critical' ? 'var(--accent-danger)' : (risk.level === 'high' ? 'var(--accent-warning)' : 'var(--accent-success)');

                let factorsHtml = Object.entries(risk.factors || {}).map(([k, f]) => `
                    <div style="padding: 6px 0; border-bottom: 1px solid var(--border-color); font-size: 12.5px;">
                        <strong>[+${f.score} ঝুঁকি]</strong> ${escapeHtml(f.detail)}
                    </div>
                `).join('');

                container.innerHTML = `
                    <div style="background: var(--bg-body); padding: 16px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div>
                                <span style="font-size: 13px; font-weight: 700;">ইউজার #${userId} রিস্ক স্কোর:</span>
                                <span style="font-size: 20px; font-weight: 800; color: ${color}; margin-left: 8px;">${risk.score}/100</span>
                                <span class="badge-status" style="margin-left: 8px; text-transform: uppercase;">${escapeHtml(risk.level)}</span>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn-secondary" onclick="openDossierModal(${userId})" style="font-size: 12px;">🕵️ পূর্ণাঙ্গ ডসিয়ার</button>
                                <button class="btn-danger" onclick="openRestrictModal(${userId})" style="font-size: 12px;">🛑 সীমাবদ্ধতা প্রয়োগ</button>
                            </div>
                        </div>
                        <div style="font-weight: 600; font-size: 12px; margin-bottom: 6px; color: var(--text-secondary);">সনাক্তকৃত ঝুঁকি ফ্যাক্টরসমূহ:</div>
                        ${factorsHtml || '<div style="color: var(--accent-success); font-size: 12.5px;">কোনো অস্বাভাবিক ঝুঁকি উপাদান সনাক্ত হয়নি। অ্যাকাউন্টটি নিরাপদ।</div>'}
                    </div>
                `;
            } catch (e) {
                container.innerHTML = `<div style="color: var(--accent-danger); padding: 12px;">মূল্যায়ন ব্যর্থ হয়েছে: ${escapeHtml(e.message)}</div>`;
            }
        }

        async function openDossierModal(userId) {
            document.getElementById('dossier-modal').classList.add('active');
            const body = document.getElementById('dossier-modal-body');
            body.innerHTML = '<div style="text-align: center; padding: 40px;">তদন্ত ডসিয়ার সংকলন হচ্ছে...</div>';

            try {
                const [res, relRes] = await Promise.all([
                    apiRequest(`/search/dossier/${userId}`),
                    apiRequest(`/abuse/users/${userId}/related-accounts`).catch(() => ({ data: [] }))
                ]);
                const d = res?.data || {};
                const u = d.user || {};
                const r = d.risk_assessment || {};
                const relatedAccounts = Array.isArray(relRes?.data)
                    ? relRes.data
                    : (relRes?.data?.related_accounts || d.related_accounts || []);

                body.innerHTML = `
                    <div style="display: flex; gap: 20px; align-items: center; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); margin-bottom: 16px;">
                        <div class="admin-avatar" style="width: 50px; height: 50px; font-size: 20px;">${escapeHtml((u.name || 'U').charAt(0))}</div>
                        <div style="flex: 1;">
                            <div style="font-size: 18px; font-weight: 800;">${escapeHtml(u.name)} <span style="font-size: 13px; font-weight: 400; color: var(--text-muted);">(@${escapeHtml(u.username || '')})</span></div>
                            <div style="font-size: 12.5px; color: var(--text-secondary);">ইমেইল: ${escapeHtml(u.email || '')} | ফোন: ${escapeHtml(u.phone || 'নেই')}</div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">আইপি: ${escapeHtml(u.last_login_ip || 'N/A')} | নিবন্ধন: ${new Date(u.created_at).toLocaleDateString('bn-BD')}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">কম্পোজিট রিস্ক স্কোর</div>
                            <div style="font-size: 24px; font-weight: 800; color: ${r.score >= 50 ? 'var(--accent-danger)' : 'var(--accent-success)'};">${r.score || 0}/100</div>
                            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700;">${escapeHtml(r.level || 'low')}</div>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <div style="font-weight: 700; font-size: 14px; margin-bottom: 8px;">🔗 সম্পর্কিত অ্যাকাউন্ট ও আইপি কোরিলেশন (${relatedAccounts.length})</div>
                        <div style="background: var(--bg-body); border-radius: 8px; padding: 10px 12px; font-size: 12px;">
                            ${relatedAccounts.length === 0
                                ? '<div style="color: var(--text-muted);">একই আইপি বা ডিভাইস সিগন্যালের সাথে যুক্ত অন্য কোনো অ্যাকাউন্ট পাওয়া যায়নি।</div>'
                                : relatedAccounts.map(acc => `<div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid var(--border-color);"><span><strong>#${acc.id} ${escapeHtml(acc.name)}</strong> (@${escapeHtml(acc.username || '')})</span><span class="badge-status ${acc.status === 'suspended' ? 'badge-suspended' : 'badge-active'}">${escapeHtml(acc.status)}</span></div>`).join('')}
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <div style="font-weight: 700; font-size: 14px; margin-bottom: 8px;">⏳ টাইমলাইন ও ঘটনা ক্রম (Chronological Audit Timeline)</div>
                        <div style="background: var(--bg-body); border-radius: 8px; padding: 12px; max-height: 200px; overflow-y: auto;">
                            ${(d.investigation_timeline || []).map(t => `
                                <div style="display: flex; gap: 10px; margin-bottom: 10px; font-size: 12px; align-items: flex-start;">
                                    <span style="font-size: 14px;">📌</span>
                                    <div>
                                        <strong>${escapeHtml(t.title)}</strong>
                                        <div style="color: var(--text-secondary);">${escapeHtml(t.description)}</div>
                                        <div style="font-size: 10.5px; color: var(--text-muted);">${new Date(t.timestamp).toLocaleString('bn-BD')}</div>
                                    </div>
                                </div>
                            `).join('') || '<div style="color: var(--text-muted); font-size: 12px;">কোনো নিরাপত্তা বা অডিট ঘটনা পাওয়া যায়নি।</div>'}
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 14px;">
                        <button class="btn-secondary" onclick="toggleLegalHold(${u.id}, ${!u.is_legal_hold})">${u.is_legal_hold ? '🔓 লিগ্যাল হোল্ড প্রত্যাহার' : '⚖️ লিগ্যাল হোল্ড প্রয়োগ'}</button>
                        <button class="btn-danger" onclick="openRestrictModal(${u.id})">🛑 সীমাবদ্ধতা প্রয়োগ করুন</button>
                    </div>
                `;
            } catch (e) {
                body.innerHTML = `<div style="color: var(--accent-danger); padding: 20px;">ডসিয়ার লোড করতে ব্যর্থ: ${escapeHtml(e.message)}</div>`;
            }
        }

        function closeDossierModal() {
            document.getElementById('dossier-modal').classList.remove('active');
        }

        function openRestrictModal(userId) {
            document.getElementById('restrict-target-user-id').value = userId;
            document.getElementById('restrict-user-modal').classList.add('active');
        }

        function closeRestrictModal() {
            document.getElementById('restrict-user-modal').classList.remove('active');
        }

        async function submitUserRestriction() {
            const userId = document.getElementById('restrict-target-user-id').value;
            const type = document.getElementById('restrict-type-select').value;
            const hours = parseInt(document.getElementById('restrict-duration-hours').value) || 24;
            const reason = document.getElementById('restrict-reason').value;

            if (!reason) {
                showToast('বাধ্যতামূলক কারণ লিখুন।', 'error');
                return;
            }

            try {
                await apiRequest(`/abuse/users/${userId}/restrict`, 'POST', {
                    restriction_type: type,
                    duration_hours: hours,
                    reason: reason
                });
                showToast('ব্যবহারকারীর উপর সীমাবদ্ধতা কার্যকর হয়েছে।');
                closeRestrictModal();
                loadTrustSafety();
            } catch (e) {}
        }

        function openOverrideModal(restrictionId) {
            document.getElementById('override-target-restriction-id').value = restrictionId;
            document.getElementById('override-restriction-modal').classList.add('active');
        }

        function closeOverrideModal() {
            document.getElementById('override-restriction-modal').classList.remove('active');
        }

        async function submitRestrictionOverride() {
            const id = document.getElementById('override-target-restriction-id').value;
            const justification = document.getElementById('override-justification').value;

            if (!justification) {
                showToast('বাধ্যতামূলক ব্যাখ্যা লিখুন।', 'error');
                return;
            }

            try {
                await apiRequest(`/abuse/restrictions/${id}/override`, 'POST', { justification });
                showToast('সীমাবদ্ধতা সফলভাবে প্রত্যাহার করা হয়েছে।');
                closeOverrideModal();
                loadTrustSafety();
            } catch (e) {}
        }

        // 2. Real-Time Operations Alerts
        async function loadAlertsCenter(page = 1) {
            try {
                const summaryRes = await apiRequest('/alerts/summary');
                const s = summaryRes?.data || {};
                document.getElementById('ops-alert-total').textContent = s.total_alerts ?? 0;
                document.getElementById('ops-alert-critical').textContent = s.critical_unread_count ?? 0;
                document.getElementById('ops-alert-acked').textContent = s.acknowledged_count ?? 0;
                document.getElementById('ops-alert-resolved').textContent = s.resolved_count ?? 0;

                const severity = document.getElementById('ops-alert-severity-filter')?.value || 'all';
                const status = document.getElementById('ops-alert-status-filter')?.value || 'all';
                const tbody = document.getElementById('ops-alerts-table-body');
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px;">অ্যালার্ট লোড হচ্ছে...</td></tr>`;

                const res = await apiRequest(`/alerts?page=${page}&severity=${severity}&status=${status}`);
                const alerts = res?.data?.alerts || [];

                if (alerts.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">কোনো সতর্কবার্তা নেই।</td></tr>`;
                    return;
                }

                tbody.innerHTML = alerts.map(a => `
                    <tr>
                        <td><span class="badge-status ${a.severity === 'critical' ? 'badge-suspended' : 'badge-pending'}">${escapeHtml(a.severity)}</span></td>
                        <td>
                            <div style="font-weight: 700;">${escapeHtml(a.title)}</div>
                            <div style="font-size: 11.5px; color: var(--text-muted);">${escapeHtml(a.message)}</div>
                        </td>
                        <td><span style="font-size: 11px; text-transform: uppercase;">${escapeHtml(a.type)}</span></td>
                        <td><strong>${a.hit_count || 1}</strong> বার</td>
                        <td>${new Date(a.created_at).toLocaleTimeString('bn-BD', {hour: '2-digit', minute: '2-digit'})}</td>
                        <td><span class="badge-status ${a.status === 'resolved' ? 'badge-active' : 'badge-pending'}">${escapeHtml(a.status || 'unread')}</span></td>
                        <td style="text-align: right;">
                            <button class="btn-table-action" onclick="acknowledgeAlert(${a.id})">অ্যাকনলেজ</button>
                            <button class="btn-table-action" onclick="resolveAlert(${a.id})" style="color: var(--accent-success);">নিষ্পত্তি</button>
                        </td>
                    </tr>
                `).join('');
            } catch (e) {
                console.error(e);
            }
        }

        async function acknowledgeAlert(id) {
            try {
                await apiRequest(`/alerts/${id}/acknowledge`, 'POST');
                showToast('অ্যালার্ট অ্যাকনলেজ করা হয়েছে।');
                loadAlertsCenter();
            } catch (e) {}
        }

        async function resolveAlert(id) {
            try {
                await apiRequest(`/alerts/${id}/resolve`, 'POST');
                showToast('অ্যালার্ট সফলভাবে নিষ্পত্তি করা হয়েছে।');
                loadAlertsCenter();
            } catch (e) {}
        }

        // 3. Background Jobs & Automation
        async function loadJobsCenter() {
            try {
                const healthRes = await apiRequest('/jobs/health');
                const h = healthRes?.data || {};
                document.getElementById('jobs-driver-val').textContent = h.queue_driver || 'database';
                document.getElementById('jobs-pending-val').textContent = h.pending_jobs || 0;
                document.getElementById('jobs-failed-val').textContent = h.failed_jobs || 0;
                document.getElementById('jobs-health-status').textContent = (h.failed_jobs > 50) ? 'Degraded' : 'Healthy';

                // Load failed jobs
                const failedRes = await apiRequest('/jobs/failed');
                failedJobsCache = failedRes?.data || [];
                const tbody = document.getElementById('failed-jobs-table-body');

                if (failedJobsCache.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: var(--accent-success);">✓ কোনো ব্যর্থ জব নেই। ব্যাকগ্রাউন্ড কিউ সুশৃঙ্খলভাবে সচল রয়েছে।</td></tr>`;
                } else {
                    tbody.innerHTML = failedJobsCache.map((j, idx) => `
                        <tr>
                            <td><strong>#${j.id}</strong></td>
                            <td>${escapeHtml(j.connection)} / ${escapeHtml(j.queue)}</td>
                            <td>${new Date(j.failed_at).toLocaleString('bn-BD')}</td>
                            <td><code style="font-size: 11px; background: rgba(239,68,68,0.1); color: var(--accent-danger); padding: 2px 4px; border-radius: 4px;">${escapeHtml(j.exception_class)}</code></td>
                            <td><div style="font-size: 11.5px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(j.exception)}</div></td>
                            <td style="text-align: right;">
                                <button class="btn-table-action" onclick="inspectFailedJob(${idx})">পেলোড</button>
                                <button class="btn-table-action" onclick="retryFailedJob(${j.id})">রিট্রাই</button>
                                <button class="btn-table-action" style="color: var(--accent-danger);" onclick="deleteFailedJob(${j.id})">মুছুন</button>
                            </td>
                        </tr>
                    `).join('');
                }

                // Load scheduled tasks
                const schedRes = await apiRequest('/jobs/schedules');
                const scheds = schedRes?.data || [];
                const stbody = document.getElementById('scheduled-tasks-table-body');
                stbody.innerHTML = scheds.map(s => `
                    <tr>
                        <td><strong><code>${escapeHtml(s.command)}</code></strong></td>
                        <td><code>${escapeHtml(s.expression)}</code></td>
                        <td>${escapeHtml(s.description)} <span style="font-size: 11px; color: var(--text-muted);">(${escapeHtml(s.frequency)})</span></td>
                        <td>${new Date(s.next_run).toLocaleString('bn-BD')}</td>
                        <td><span class="badge-status badge-active">Active</span></td>
                    </tr>
                `).join('');
            } catch (e) {
                console.error(e);
            }
        }

        async function retryFailedJob(jobId) {
            try {
                const res = await apiRequest('/jobs/retry', 'POST', { job_id: jobId });
                showToast(res.message || 'জব পুনরায় কিউতে পাঠানো হয়েছে।');
                loadJobsCenter();
            } catch (e) {}
        }

        async function deleteFailedJob(jobId) {
            if (!confirm(`আপনি কি নিশ্চিত যে ব্যর্থ জব #${jobId} মুছে ফেলতে চান?`)) return;
            try {
                await apiRequest(`/jobs/failed/${jobId}`, 'DELETE');
                showToast('ব্যর্থ জব মুছে ফেলা হয়েছে।');
                loadJobsCenter();
            } catch (e) {}
        }

        function inspectFailedJob(idx) {
            const j = failedJobsCache[idx];
            if (!j) return;
            document.getElementById('failed-job-class-name').textContent = j.exception_class;
            document.getElementById('failed-job-exception-text').textContent = j.exception;
            document.getElementById('failed-job-payload-text').textContent = j.payload;
            document.getElementById('failed-job-modal').classList.add('active');
        }

        function closeFailedJobModal() {
            document.getElementById('failed-job-modal').classList.remove('active');
        }

        // 4. Infrastructure Health Center
        async function loadHealthCenter(forceRefresh = false) {
            try {
                const res = await apiRequest(`/health?refresh=${forceRefresh ? 'true' : 'false'}`);
                const h = res?.data || {};

                document.getElementById('health-db-latency').textContent = (h.database?.latency_ms ?? '--') + ' ms';
                document.getElementById('health-db-status').textContent = (h.database?.status || 'Active').toUpperCase();
                document.getElementById('health-cache-latency').textContent = (h.cache?.latency_ms ?? '--') + ' ms';
                document.getElementById('health-cache-status').textContent = (h.cache?.status || 'Active').toUpperCase();
                document.getElementById('health-memory-val').textContent = (h.system?.memory_usage_mb ?? '--') + ' MB';
                document.getElementById('health-overall-status').textContent = h.overall_status || 'HEALTHY';

                if (forceRefresh) showToast('ইনফ্রাস্ট্রাকচার স্বাস্থ্য ডায়াগনস্টিক সফলভাবে সম্পন্ন হয়েছে।');
            } catch (e) {
                console.error(e);
            }
        }

        // 5. Data Governance & Privacy
        async function loadGovernanceCenter() {
            try {
                // Data Inventory Grid
                const invGrid = document.getElementById('gov-inventory-grid');
                if (invGrid) {
                    const invRes = await apiRequest('/governance/inventory');
                    const inv = invRes?.data || [];
                    invGrid.innerHTML = inv.map(item => `
                        <div class="kpi-card">
                            <div class="kpi-header">
                                <span class="kpi-title">${escapeHtml(item.label || item.category)}</span>
                                <span class="badge-status badge-active" style="font-size: 10px;">${escapeHtml(item.pii_sensitivity || 'medium')}</span>
                            </div>
                            <div class="kpi-value">${Number(item.records_count || 0).toLocaleString('bn-BD')}</div>
                            <div class="kpi-trend"><code>${escapeHtml(item.category)}</code></div>
                        </div>
                    `).join('');
                }

                // Retention policies
                const polRes = await apiRequest('/governance/retention/policies');
                const policies = polRes?.data || [];
                const tbody = document.getElementById('retention-policies-table-body');
                tbody.innerHTML = policies.map(p => `
                    <tr>
                        <td><code>${escapeHtml(p.data_category)}</code></td>
                        <td><strong>${escapeHtml(p.name)}</strong></td>
                        <td><strong>${p.retention_days}</strong> দিন</td>
                        <td><span style="font-size: 11px; text-transform: uppercase;">${escapeHtml(p.action)}</span></td>
                        <td><span class="badge-status ${p.is_enabled ? 'badge-active' : 'badge-suspended'}">${p.is_enabled ? 'Active' : 'Disabled'}</span></td>
                        <td>${p.last_run_at ? new Date(p.last_run_at).toLocaleDateString('bn-BD') : 'এখনও রান হয়নি'}</td>
                    </tr>
                `).join('');

                // Privacy requests
                const privRes = await apiRequest('/governance/privacy-requests');
                const requests = privRes?.data || [];
                const ptbody = document.getElementById('privacy-requests-table-body');

                if (requests.length === 0) {
                    ptbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">কোনো অনিষ্পন্ন প্রাইভেসি বা GDPR আবেদন নেই।</td></tr>`;
                } else {
                    ptbody.innerHTML = requests.map(r => `
                        <tr>
                            <td><strong>${escapeHtml(r.request_number)}</strong></td>
                            <td>${escapeHtml(r.user?.name || 'User #' + r.user_id)}</td>
                            <td><span class="badge-status badge-pending" style="text-transform: uppercase;">${escapeHtml(r.request_type)}</span></td>
                            <td>${escapeHtml(r.reason || 'সাধারণ প্রাইভেসি আবেদন')}</td>
                            <td>${new Date(r.created_at).toLocaleDateString('bn-BD')}</td>
                            <td><span class="badge-status ${r.status === 'completed' ? 'badge-active' : 'badge-pending'}">${escapeHtml(r.status)}</span></td>
                            <td style="text-align: right;">
                                <button class="btn-table-action" onclick="openPrivacyProcessModal(${r.id}, '${escapeHtml(r.request_number)}', '${escapeHtml(r.request_type)}')">নিষ্পত্তি করুন</button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function submitQuickLegalHold(isHold) {
            const userId = parseInt(document.getElementById('gov-legal-hold-user-id')?.value || '0', 10);
            const reason = document.getElementById('gov-legal-hold-reason')?.value?.trim() || '';
            if (!userId || !reason) {
                showToast('ইউজার আইডি এবং বাধ্যতামূলক কারণ লিখুন।', 'error');
                return;
            }
            try {
                const res = await apiRequest('/governance/legal-hold', 'POST', {
                    user_id: userId,
                    is_legal_hold: isHold,
                    reason
                });
                showToast(res.message);
                document.getElementById('gov-legal-hold-reason').value = '';
                loadGovernanceCenter();
            } catch (e) {}
        }

        async function submitNewPrivacyRequest() {
            const userId = parseInt(document.getElementById('gov-new-req-user-id')?.value || '0', 10);
            const requestType = document.getElementById('gov-new-req-type')?.value || 'data_export';
            if (!userId) {
                showToast('অনুগ্রহ করে ইউজার আইডি দিন।', 'error');
                return;
            }
            try {
                const res = await apiRequest('/governance/privacy-requests', 'POST', {
                    user_id: userId,
                    request_type: requestType,
                    reason: 'প্রশাসনিক কমপ্লায়েন্স ডিসপ্যাচ'
                });
                showToast(res.message || 'নতুন প্রাইভেসি রিকোয়েস্ট নথিভুক্ত করা হয়েছে।');
                loadGovernanceCenter();
            } catch (e) {}
        }

        async function enforceDataRetention(dryRun = false) {
            const promptMsg = dryRun
                ? 'আপনি কি রিটেনশন নীতি সিমুলেশন (ড্রাই রান) করতে চান? কোনো ডাটা মুছে ফেলা হবে না।'
                : 'সতর্কতা: এটি মেয়াদোত্তীর্ণ লগ ও রেকর্ড স্থায়ীভাবে মুছে ফেলবে। আপনি কি নিশ্চিত?';
            if (!confirm(promptMsg)) return;

            try {
                const res = await apiRequest('/governance/retention/enforce', 'POST', { dry_run: dryRun });
                showToast(res.message);
                loadGovernanceCenter();
            } catch (e) {}
        }

        function openPrivacyProcessModal(id, number, type) {
            document.getElementById('privacy-target-req-id').value = id;
            document.getElementById('privacy-req-desc').textContent = `রিকোয়েস্ট #${number} (ধরণ: ${type.toUpperCase()}) এর উপর পদক্ষেপ নিন।`;
            document.getElementById('privacy-process-modal').classList.add('active');
        }

        function closePrivacyProcessModal() {
            document.getElementById('privacy-process-modal').classList.remove('active');
        }

        async function submitProcessPrivacy() {
            const id = document.getElementById('privacy-target-req-id').value;
            const decision = document.getElementById('privacy-decision-select').value;
            const notes = document.getElementById('privacy-notes').value;

            try {
                const res = await apiRequest(`/governance/privacy-requests/${id}/process`, 'POST', { decision, notes });
                showToast(res.message);
                closePrivacyProcessModal();
                loadGovernanceCenter();
            } catch (e) {}
        }

        async function toggleLegalHold(userId, isHold) {
            const reason = prompt(`ইউজার #${userId} এর উপর লিগ্যাল হোল্ড ${isHold ? 'প্রয়োগ' : 'প্রত্যাহার'} করার বাধ্যতামূলক কারণ লিখুন:`);
            if (!reason) return;

            try {
                const res = await apiRequest('/governance/legal-hold', 'POST', {
                    user_id: userId,
                    is_legal_hold: isHold,
                    reason: reason
                });
                showToast(res.message);
                openDossierModal(userId);
            } catch (e) {}
        }

        // 6. Global Search & Dossier Workspace
        async function executeGlobalWorkspaceSearch() {
            const query = document.getElementById('search-workspace-input')?.value;
            if (!query || query.trim().length < 2) {
                showToast('কমপক্ষে ২টি অক্ষর লিখে অনুসন্ধান করুন।', 'warning');
                return;
            }

            const container = document.getElementById('search-workspace-results');
            container.innerHTML = '<div style="text-align: center; padding: 40px;">ক্রস-এনটিটি ডাটাবেজ অনুসন্ধান চলছে...</div>';

            try {
                const res = await apiRequest(`/search?q=${encodeURIComponent(query)}`);
                const data = res?.data || {};
                const users = data.users || [];
                const posts = data.posts || [];
                const groups = data.groups || [];
                const tickets = data.tickets || [];

                let html = '';

                // Users
                if (users.length > 0) {
                    html += `
                        <div class="data-card" style="margin-bottom: 20px;">
                            <div class="data-card-header"><div class="chart-title">👥 ব্যবহারকারী ফলাফল (${users.length})</div></div>
                            <div style="padding: 12px 20px;">
                                ${users.map(u => `
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                                        <div>
                                            <strong>${escapeHtml(u.name)}</strong> (@${escapeHtml(u.username || '')})
                                            <div style="font-size: 11.5px; color: var(--text-muted);">${escapeHtml(u.email || '')}</div>
                                        </div>
                                        <button class="btn-primary" onclick="openDossierModal(${u.id})" style="font-size: 12px; padding: 4px 10px;">🕵️ তদন্ত ডসিয়ার</button>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }

                // Posts
                if (posts.length > 0) {
                    html += `
                        <div class="data-card" style="margin-bottom: 20px;">
                            <div class="data-card-header"><div class="chart-title">📝 সোশ্যাল পোস্টসমূহ (${posts.length})</div></div>
                            <div style="padding: 12px 20px;">
                                ${posts.map(p => `
                                    <div style="padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                                        <div style="font-size: 13px;">${escapeHtml(p.snippet || p.content || '')}</div>
                                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">লেখক: ${escapeHtml(p.author || 'N/A')} | আইডি: #${p.id}</div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }

                // Groups
                if (groups.length > 0) {
                    html += `
                        <div class="data-card" style="margin-bottom: 20px;">
                            <div class="data-card-header"><div class="chart-title">🌐 গ্রুপ ও কমিউনিটি (${groups.length})</div></div>
                            <div style="padding: 12px 20px;">
                                ${groups.map(g => `
                                    <div style="padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                                        <strong>${escapeHtml(g.name)}</strong> <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">(${escapeHtml(g.privacy || 'public')})</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }

                // Tickets
                if (tickets.length > 0) {
                    html += `
                        <div class="data-card" style="margin-bottom: 20px;">
                            <div class="data-card-header"><div class="chart-title">🎫 সাপোর্ট টিকিট (${tickets.length})</div></div>
                            <div style="padding: 12px 20px;">
                                ${tickets.map(t => `
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                                        <div>
                                            <code>${escapeHtml(t.ticket_number)}</code> — <strong>${escapeHtml(t.subject)}</strong>
                                        </div>
                                        <span class="badge-status badge-pending">${escapeHtml(t.status)}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }

                if (!html) {
                    html = '<div style="text-align: center; padding: 40px; color: var(--text-muted);">কোনো ফলাফল পাওয়া যায়নি। ভিন্ন কীওয়ার্ড দিয়ে অনুসন্ধান করুন।</div>';
                }

                container.innerHTML = html;
            } catch (e) {
                container.innerHTML = `<div style="color: var(--accent-danger); padding: 20px;">অনুসন্ধানে ত্রুটি: ${escapeHtml(e.message)}</div>`;
            }
        }

        // ==========================================
        // PHASE 5: PRODUCTION READINESS & DISASTER RECOVERY
        // ==========================================
        async function loadProductionReadiness() {
            const checksTbody = document.getElementById('readiness-checks-table-body');
            const backupsTbody = document.getElementById('dr-backups-table-body');
            const drillsTbody = document.getElementById('dr-drills-table-body');

            try {
                const res = await apiRequest('/production-readiness');
                const data = res?.data || {};
                const summary = data.summary || {};
                const dr = data.disaster_recovery || {};

                // Update KPI Cards
                const scoreEl = document.getElementById('readiness-score-val');
                if (scoreEl) scoreEl.textContent = `${data.readiness_score ?? 0}/100`;

                const badgeEl = document.getElementById('readiness-overall-badge');
                if (badgeEl) {
                    const st = (data.overall_status || 'warning').toUpperCase();
                    badgeEl.textContent = st;
                    badgeEl.className = `badge-status ${st === 'PASS' ? 'badge-active' : (st === 'FAIL' ? 'badge-suspended' : 'badge-pending')}`;
                }

                const blockersEl = document.getElementById('readiness-blockers-val');
                if (blockersEl) blockersEl.textContent = `${summary.critical_blockers_count ?? 0} ব্লকার`;

                const warnTextEl = document.getElementById('readiness-warnings-text');
                if (warnTextEl) warnTextEl.textContent = `উত্তীর্ণ: ${summary.pass_count ?? 0} | সতর্কতা: ${summary.warning_count ?? 0} | ব্যর্থ: ${summary.fail_count ?? 0}`;

                const drStatusEl = document.getElementById('dr-ready-status-val');
                if (drStatusEl) {
                    drStatusEl.textContent = dr.dr_ready ? 'DR READY' : 'ACTION NEEDED';
                    drStatusEl.style.color = dr.dr_ready ? 'var(--accent-success)' : 'var(--accent-warning)';
                }

                const drSummaryEl = document.getElementById('dr-rpo-rto-summary');
                if (drSummaryEl) {
                    const rpoOk = dr.compliance?.rpo_compliant ? 'RPO ✓' : 'RPO ✕';
                    const rtoOk = dr.compliance?.rto_compliant ? 'RTO ✓' : 'RTO ✕';
                    drSummaryEl.textContent = `${rpoOk} | ${rtoOk}`;
                }

                const latestDrill = dr.latest_restore_drill;
                const drillValEl = document.getElementById('dr-latest-drill-val');
                const drillMetaEl = document.getElementById('dr-latest-drill-meta');
                if (drillValEl) {
                    drillValEl.textContent = latestDrill ? latestDrill.status.toUpperCase() : 'NOT TESTED';
                    drillValEl.style.color = latestDrill?.status === 'passed' ? 'var(--accent-success)' : 'var(--accent-warning)';
                }
                if (drillMetaEl) {
                    drillMetaEl.textContent = latestDrill
                        ? `${latestDrill.drill_uid} (${latestDrill.duration_ms}ms, ${latestDrill.restored_records_count} rows)`
                        : 'কোনো রিস্টোর ড্রিল এখনো চালানো হয়নি';
                }

                // Populate RPO/RTO inputs
                if (dr.objectives) {
                    if (document.getElementById('dr-rpo-input')) document.getElementById('dr-rpo-input').value = dr.objectives.rpo_minutes ?? 60;
                    if (document.getElementById('dr-rto-input')) document.getElementById('dr-rto-input').value = dr.objectives.rto_minutes ?? 30;
                    if (document.getElementById('dr-retention-input')) document.getElementById('dr-retention-input').value = dr.objectives.retention_days ?? 30;
                    if (document.getElementById('dr-drill-freq-input')) document.getElementById('dr-drill-freq-input').value = dr.objectives.restore_drill_frequency_days ?? 7;
                }

                // Render Checks Table
                if (checksTbody) {
                    const checks = data.checks || [];
                    checksTbody.innerHTML = checks.map(c => {
                        const badgeClass = c.status === 'pass' ? 'badge-active' : (c.status === 'fail' ? 'badge-suspended' : 'badge-pending');
                        return `
                            <tr>
                                <td><code>${escapeHtml(c.id)}</code><div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">${escapeHtml(c.category)}</div></td>
                                <td><strong>${escapeHtml(c.title)}</strong></td>
                                <td><span style="text-transform: uppercase; font-weight: 700; font-size: 11px;">${escapeHtml(c.severity)}</span></td>
                                <td><span class="badge-status ${badgeClass}">${escapeHtml(c.status.toUpperCase())}</span></td>
                                <td style="font-size: 12.5px;">${escapeHtml(c.description)}</td>
                                <td style="font-size: 12px; color: var(--text-secondary);">${escapeHtml(c.remediation)}</td>
                            </tr>
                        `;
                    }).join('');
                }

                // Render Backups Table
                if (backupsTbody) {
                    const backups = dr.recent_backups || [];
                    if (backups.length === 0) {
                        backupsTbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">কোনো ব্যাকআপ রেকর্ড পাওয়া যায়নি।</td></tr>`;
                    } else {
                        backupsTbody.innerHTML = backups.map(b => {
                            const verBadge = b.verification_status === 'verified' ? 'badge-active' : (b.verification_status === 'corrupted' ? 'badge-suspended' : 'badge-pending');
                            const drillBadge = b.restore_drill_status === 'passed' ? 'badge-active' : (b.restore_drill_status === 'failed' ? 'badge-suspended' : 'badge-pending');
                            return `
                                <tr>
                                    <td>
                                        <strong>${escapeHtml(b.backup_uid || ('#' + b.id))}</strong>
                                        <div style="font-size: 11px; color: var(--text-muted);">${escapeHtml(b.filename)}</div>
                                    </td>
                                    <td>
                                        <span style="text-transform: uppercase; font-weight: 700;">${escapeHtml(b.type)}</span>
                                        <div style="font-size: 11px; color: var(--text-muted);">${escapeHtml(b.db_engine || 'db')} (${escapeHtml(b.db_version || '')})</div>
                                    </td>
                                    <td>
                                        ${Number(b.size_bytes || 0).toLocaleString()} bytes
                                        <div style="font-size: 11px; color: var(--text-muted);">${escapeHtml(b.cipher || 'AES-256-CBC')}</div>
                                    </td>
                                    <td><span class="badge-status ${verBadge}">${escapeHtml(b.verification_status || 'unverified')}</span></td>
                                    <td><span class="badge-status ${drillBadge}">${escapeHtml(b.restore_drill_status || 'not_tested')}</span></td>
                                    <td style="font-size: 12px;">${escapeHtml(b.completed_at || b.created_at || '')}</td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                            <button class="btn-secondary" style="font-size: 11.5px; padding: 4px 8px;" onclick="verifyDrBackup(${b.id})">✓ চেকসাম যাচাই</button>
                                            <button class="btn-primary" style="font-size: 11.5px; padding: 4px 8px;" onclick="runDrRestoreDrill(${b.id})">🧪 রিস্টোর ড্রিল</button>
                                            <button class="btn-danger" style="font-size: 11.5px; padding: 4px 8px;" onclick="deleteDrBackup(${b.id})">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    }
                }

                // Render Restore Drills Table
                if (drillsTbody) {
                    const drills = dr.recent_drills || [];
                    if (drills.length === 0) {
                        drillsTbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">কোনো রিস্টোর ড্রিল রেকর্ড নেই।</td></tr>`;
                    } else {
                        drillsTbody.innerHTML = drills.map(d => `
                            <tr>
                                <td><code>${escapeHtml(d.drill_uid)}</code></td>
                                <td>#${d.backup_id}</td>
                                <td>${d.restored_tables_count} টেবিল / ${d.restored_records_count} সারি</td>
                                <td>${d.duration_ms} ms</td>
                                <td>${d.isolated_resources_cleaned ? '✓ Cleaned' : 'Pending'}</td>
                                <td><span class="badge-status ${d.status === 'passed' ? 'badge-active' : 'badge-suspended'}">${escapeHtml(d.status.toUpperCase())}</span></td>
                            </tr>
                        `).join('');
                    }
                }

                // Render Deployment Checklist
                const checklistEl = document.getElementById('readiness-deployment-checklist');
                if (checklistEl && data.deployment_checklist) {
                    const sections = Object.entries(data.deployment_checklist);
                    checklistEl.innerHTML = sections.map(([secName, items]) => `
                        <div style="margin-bottom: 12px;">
                            <div style="font-weight: 700; text-transform: uppercase; font-size: 11px; color: var(--accent-primary); margin-bottom: 4px;">${escapeHtml(secName.replace(/_/g, ' '))}</div>
                            ${(items || []).map(item => `
                                <div style="padding: 4px 0; border-bottom: 1px dashed var(--border-color); display: flex; justify-content: space-between; gap: 8px;">
                                    <span>• ${escapeHtml(item.step)}</span>
                                    <code style="font-size: 11px;">${escapeHtml(item.command)}</code>
                                </div>
                            `).join('')}
                        </div>
                    `).join('');
                }
            } catch (e) {}
        }

        async function runFreshReadinessAudit() {
            try {
                const res = await apiRequest('/production-readiness/run', 'POST', {});
                showToast(res.message || 'প্রোডাকশন প্রস্তুতি অডিট সম্পন্ন হয়েছে।');
                loadProductionReadiness();
            } catch (e) {}
        }

        async function createDrBackup(type = 'db') {
            try {
                const res = await apiRequest('/disaster-recovery/backups', 'POST', { type, disk: 'local' });
                showToast(res.message || 'এনক্রিপ্টেড ব্যাকআপ তৈরি সম্পন্ন হয়েছে।');
                loadProductionReadiness();
            } catch (e) {}
        }

        async function verifyDrBackup(backupId) {
            try {
                const res = await apiRequest(`/disaster-recovery/backups/${backupId}/verify`, 'POST', {});
                showToast(res.message || 'ব্যাকআপ যাচাই সম্পন্ন হয়েছে।');
                loadProductionReadiness();
            } catch (e) {
                loadProductionReadiness();
            }
        }

        async function runDrRestoreDrill(backupId) {
            try {
                const res = await apiRequest(`/disaster-recovery/backups/${backupId}/restore-drill`, 'POST', {});
                showToast(res.message || 'আইসোলেটেড রিস্টোর ড্রিল সফলভাবে সম্পন্ন হয়েছে।');
                loadProductionReadiness();
            } catch (e) {
                loadProductionReadiness();
            }
        }

        async function deleteDrBackup(backupId) {
            const reason = prompt('ব্যাকআপটি মুছে ফেলার প্রশাসনিক কারণ উল্লেখ করুন (ন্যূনতম ৫ অক্ষর):');
            if (!reason || reason.trim().length < 5) {
                showToast('ব্যাকআপ মুছে ফেলার জন্য বৈধ কারণ আবশ্যক।', 'warning');
                return;
            }
            try {
                const res = await apiRequest(`/disaster-recovery/backups/${backupId}`, 'DELETE', { reason: reason.trim() });
                showToast(res.message || 'ব্যাকআপ অপসারিত হয়েছে।');
                loadProductionReadiness();
            } catch (e) {}
        }

        async function saveDrObjectives() {
            const rpo = parseInt(document.getElementById('dr-rpo-input')?.value || '60', 10);
            const rto = parseInt(document.getElementById('dr-rto-input')?.value || '30', 10);
            const retention = parseInt(document.getElementById('dr-retention-input')?.value || '30', 10);
            const drillFreq = parseInt(document.getElementById('dr-drill-freq-input')?.value || '7', 10);

            try {
                const res = await apiRequest('/disaster-recovery/objectives', 'POST', {
                    rpo_minutes: rpo,
                    rto_minutes: rto,
                    retention_days: retention,
                    restore_drill_frequency_days: drillFreq
                });
                showToast(res.message || 'ডিজাস্টার রিকভারি লক্ষ্যমাত্রা সংরক্ষিত হয়েছে।');
                loadProductionReadiness();
            } catch (e) {}
        }

        // ==========================================
        // PHASE 6: ZERO-TRUST SECURITY, HA, COMPLIANCE & AUTONOMOUS OPS
        // ==========================================
        async function loadZeroTrustWorkspace() {
            try {
                const [ztRes, sessRes, threatRes, compRes, sloRes] = await Promise.all([
                    apiRequest('/zero-trust/overview'),
                    apiRequest('/session-security'),
                    apiRequest('/threat-detection'),
                    apiRequest('/compliance-controls'),
                    apiRequest('/slo')
                ]);

                const zt = ztRes?.data || {};
                const sess = sessRes?.data || {};
                const threat = threatRes?.data || {};
                const comp = compRes?.data || {};
                const slo = sloRes?.data || {};

                // KPI Cards
                const polCountEl = document.getElementById('zt-policies-count-val');
                if (polCountEl) polCountEl.textContent = `${zt.summary?.active_policies ?? 0} Active`;
                const decSummaryEl = document.getElementById('zt-decisions-summary');
                if (decSummaryEl) {
                    decSummaryEl.textContent = `Deny: ${zt.summary?.denied_decisions_24h ?? 0} | Approval: ${zt.summary?.approval_required_24h ?? 0}`;
                }

                const threatCountEl = document.getElementById('zt-open-threats-val');
                if (threatCountEl) threatCountEl.textContent = `${threat.summary?.open_detections ?? 0} Open`;
                const pendingActEl = document.getElementById('zt-pending-actions-summary');
                if (pendingActEl) {
                    pendingActEl.textContent = `Awaiting Dual Approval: ${threat.summary?.pending_approval_actions ?? 0}`;
                }

                const compRateEl = document.getElementById('zt-compliance-rate-val');
                if (compRateEl) compRateEl.textContent = `${comp.summary?.compliance_rate_pct ?? 0}%`;
                const compMetaEl = document.getElementById('zt-compliance-meta');
                if (compMetaEl) {
                    compMetaEl.textContent = `Compliant: ${comp.summary?.compliant ?? 0}/${comp.summary?.total_controls ?? 0}`;
                }

                const sloValEl = document.getElementById('zt-slo-meeting-val');
                if (sloValEl) {
                    sloValEl.textContent = `${slo.summary?.meeting_slo ?? 0}/${slo.summary?.total_slos ?? 0} Meeting`;
                }
                const sloMetaEl = document.getElementById('zt-slo-meta');
                if (sloMetaEl) {
                    sloMetaEl.textContent = `Breached: ${slo.summary?.breached ?? 0} | Insufficient Telemetry: ${slo.summary?.insufficient_telemetry ?? 0}`;
                }

                // Policies Table
                const polTbody = document.getElementById('zt-policies-table-body');
                if (polTbody) {
                    const policies = zt.policies || [];
                    polTbody.innerHTML = policies.map(p => `
                        <tr>
                            <td><code>${escapeHtml(p.policy_key)}</code><div style="font-size: 11px; color: var(--text-muted);">${escapeHtml(p.name)}</div></td>
                            <td><code>${escapeHtml(p.resource)}:${escapeHtml(p.action)}</code></td>
                            <td>
                                <span class="badge-status ${p.require_mfa ? 'badge-active' : 'badge-pending'}">MFA:${p.require_mfa ? 'YES' : 'NO'}</span>
                                <span class="badge-status ${p.require_jit ? 'badge-active' : 'badge-pending'}">JIT:${p.require_jit ? 'YES' : 'NO'}</span>
                            </td>
                            <td><strong>${p.max_risk_score}</strong></td>
                            <td>${p.require_dual_approval ? '<span class="badge-status badge-suspended">DUAL APPROVAL</span>' : 'Single'}</td>
                            <td><code>v${p.version}</code></td>
                            <td style="text-align: right;">
                                <button class="btn-secondary" style="font-size: 11px; padding: 4px 8px;" onclick="togglePhase6PolicyMfa(${p.id}, ${p.require_mfa ? 'false' : 'true'})">
                                    MFA টগল
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }

                // Sessions Table
                const sessTbody = document.getElementById('zt-sessions-table-body');
                if (sessTbody) {
                    const sessions = sess.sessions || [];
                    if (sessions.length === 0) {
                        sessTbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 16px; color: var(--text-muted);">কোনো সক্রিয় সেশন রেকর্ড পাওয়া যায়নি।</td></tr>`;
                    } else {
                        sessTbody.innerHTML = sessions.map(s => `
                            <tr>
                                <td><code>${escapeHtml(s.session_handle)}</code></td>
                                <td><strong>${escapeHtml(s.user_name || ('User #' + s.user_id))}</strong> <span style="font-size: 11px; color: var(--text-muted);">(${escapeHtml(s.role || '')})</span></td>
                                <td><code>${escapeHtml(s.ip_address || '')}</code><div style="font-size: 11px; color: var(--text-muted);">${escapeHtml(s.user_agent_summary || '')}</div></td>
                                <td>${escapeHtml(s.last_activity_human || '')}</td>
                                <td><span class="badge-status ${s.suspicious ? 'badge-suspended' : 'badge-active'}">${s.suspicious ? 'SUSPICIOUS' : 'TRUSTED'}</span></td>
                                <td style="text-align: right;">
                                    <button class="btn-danger" style="font-size: 11px; padding: 4px 8px;" onclick="terminatePhase6Session('${escapeHtml(s.session_handle)}')">টার্মিনেট</button>
                                </td>
                            </tr>
                        `).join('');
                    }
                }

                // Threat Detections Table
                const threatsTbody = document.getElementById('zt-threats-table-body');
                if (threatsTbody) {
                    const detections = threat.detections || [];
                    if (detections.length === 0) {
                        threatsTbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 16px; color: var(--accent-success);">✓ কোনো সক্রিয় থ্রেট ডিটেকশন নেই।</td></tr>`;
                    } else {
                        threatsTbody.innerHTML = detections.map(d => `
                            <tr>
                                <td><code>${escapeHtml(d.detection_uid)}</code></td>
                                <td><strong>${escapeHtml(d.title)}</strong><div style="font-size: 11px; color: var(--text-muted);">${escapeHtml(d.summary)}</div></td>
                                <td><span class="badge-status ${d.severity === 'critical' ? 'badge-suspended' : 'badge-pending'}">${escapeHtml(d.severity.toUpperCase())}</span> (${d.confidence_score}%)</td>
                                <td>${d.occurrence_count}x</td>
                                <td><span class="badge-status ${d.status === 'false_positive' ? 'badge-pending' : 'badge-active'}">${escapeHtml(d.status)}</span></td>
                                <td style="text-align: right;">
                                    ${d.status !== 'false_positive' ? `<button class="btn-secondary" style="font-size: 11px; padding: 4px 8px;" onclick="markPhase6FalsePositive(${d.id})">False Positive</button>` : ''}
                                </td>
                            </tr>
                        `).join('');
                    }
                }

                // Automated Response Actions Table
                const actTbody = document.getElementById('zt-actions-table-body');
                if (actTbody) {
                    const actions = threat.response_actions || [];
                    if (actions.length === 0) {
                        actTbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 16px; color: var(--text-muted);">কোনো স্বয়ংক্রিয় রেসপন্স অ্যাকশন রেকর্ড নেই।</td></tr>`;
                    } else {
                        actTbody.innerHTML = actions.map(a => `
                            <tr>
                                <td><code>${escapeHtml(a.action_uid)}</code></td>
                                <td><strong>${escapeHtml(a.action_type)}</strong></td>
                                <td><code>${escapeHtml(a.target_type)}:${escapeHtml(a.target_identifier)}</code></td>
                                <td><span class="badge-status ${a.status === 'executed' ? 'badge-active' : (a.status === 'pending_approval' ? 'badge-pending' : 'badge-suspended')}">${escapeHtml(a.status)}</span></td>
                                <td>${a.requires_human_approval ? 'Dual Approval Required' : 'Autonomous'}</td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        ${a.status === 'pending_approval' ? `
                                            <button class="btn-primary" style="font-size: 11px; padding: 4px 8px;" onclick="approvePhase6Action(${a.id})">অনুমোদন</button>
                                            <button class="btn-danger" style="font-size: 11px; padding: 4px 8px;" onclick="rejectPhase6Action(${a.id})">প্রত্যাখ্যান</button>
                                        ` : ''}
                                        ${a.status === 'executed' ? `
                                            <button class="btn-secondary" style="font-size: 11px; padding: 4px 8px;" onclick="rollbackPhase6Action(${a.id})">রোলব্যাক</button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `).join('');
                    }
                }

                // Compliance Controls Table
                const compTbody = document.getElementById('zt-compliance-table-body');
                if (compTbody) {
                    const controls = comp.controls || [];
                    compTbody.innerHTML = controls.map(c => `
                        <tr>
                            <td><code>${escapeHtml(c.control_id)}</code></td>
                            <td><span class="badge-status badge-pending">${escapeHtml(c.framework)}</span></td>
                            <td><strong>${escapeHtml(c.title)}</strong></td>
                            <td><span class="badge-status ${c.status === 'compliant' ? 'badge-active' : 'badge-suspended'}">${escapeHtml(c.status.toUpperCase())}</span></td>
                            <td><code style="font-size: 11px;">${escapeHtml((c.evidence_hash || '').substring(0, 16))}...</code></td>
                            <td style="text-align: right;">
                                <button class="btn-secondary" style="font-size: 11px; padding: 4px 8px;" onclick="reviewPhase6Control(${c.id})">✓ সাইন-অফ</button>
                            </td>
                        </tr>
                    `).join('');
                }

                // SLO Table
                const sloTbody = document.getElementById('zt-slo-table-body');
                if (sloTbody) {
                    const slos = slo.slos || [];
                    sloTbody.innerHTML = slos.map(s => `
                        <tr>
                            <td><code>${escapeHtml(s.slo_key)}</code></td>
                            <td><strong>${escapeHtml(s.service_name)}</strong> — ${escapeHtml(s.sli_metric)}</td>
                            <td>${s.comparison_operator} ${s.target_threshold} ${escapeHtml(s.unit)}</td>
                            <td><strong>${s.current_value !== null ? `${s.current_value} ${escapeHtml(s.unit)}` : 'N/A (No Telemetry)'}</strong></td>
                            <td>${s.remaining_error_budget_pct}%</td>
                            <td><span class="badge-status ${s.status === 'MEETING_SLO' ? 'badge-active' : (s.status === 'BREACHED' ? 'badge-suspended' : 'badge-pending')}">${escapeHtml(s.status)}</span></td>
                        </tr>
                    `).join('');
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function runPhase6ThreatEvaluation() {
            try {
                const res = await apiRequest('/threat-detection/evaluate', 'POST', {});
                showToast(res.message || 'থ্রেট কোরিলেশন সম্পন্ন হয়েছে।');
                loadZeroTrustWorkspace();
            } catch (e) {}
        }

        async function runPhase6KeyRotationDrill() {
            try {
                const res = await apiRequest('/disaster-recovery/key-rotation-drill', 'POST', {});
                showToast(res.message || 'Gate D কী রোটেশন ড্রিল সম্পন্ন হয়েছে।');
            } catch (e) {}
        }

        async function runPhase6OffsiteVerify() {
            try {
                const res = await apiRequest('/disaster-recovery/offsite-verify', 'POST', { disk: 's3' });
                const st = res?.data?.gate_c_status || 'NOT_VERIFIED';
                showToast(`${res.message} (${st})`, res?.data?.verified ? 'success' : 'warning');
            } catch (e) {}
        }

        async function exportPhase6Sbom() {
            try {
                const res = await apiRequest('/sbom');
                const sbom = res?.data || {};
                showToast(`CycloneDX ${sbom.specVersion || '1.5'} SBOM জেনারেট হয়েছে (${sbom.summary?.total_components ?? 0} কম্পোনেন্ট)।`);
            } catch (e) {}
        }

        async function terminatePhase6Session(handle) {
            const reason = prompt(`সেশন ${handle} টার্মিনেট করার কারণ লিখুন:`, 'সন্দেহজনক সেশন কার্যকলাপ');
            if (!reason || reason.trim().length < 5) return;
            try {
                const res = await apiRequest('/session-security/terminate-session', 'POST', {
                    session_handle: handle,
                    reason: reason.trim()
                });
                showToast(res.message || 'সেশন টার্মিনেট করা হয়েছে।');
                loadZeroTrustWorkspace();
            } catch (e) {}
        }

        async function approvePhase6Action(actionId) {
            const reason = prompt('দ্বৈত অনুমোদনের (Dual Approval) কারণ লিখুন:', 'নিরাপত্তা যাচাইয়ের পর অনুমোদিত');
            if (!reason || reason.trim().length < 5) return;
            try {
                const res = await apiRequest(`/threat-detection/actions/${actionId}/approve`, 'POST', { reason: reason.trim() });
                showToast(res.message || 'অ্যাকশন অনুমোদিত ও কার্যকর হয়েছে।');
                loadZeroTrustWorkspace();
            } catch (e) {}
        }

        async function rejectPhase6Action(actionId) {
            const reason = prompt('অ্যাকশন প্রত্যাখ্যানের কারণ লিখুন:', 'ফলস পজিটিভ বা অতিরিক্ত প্রভাব');
            if (!reason || reason.trim().length < 5) return;
            try {
                const res = await apiRequest(`/threat-detection/actions/${actionId}/reject`, 'POST', { reason: reason.trim() });
                showToast(res.message || 'অ্যাকশন প্রত্যাখ্যাত হয়েছে।');
                loadZeroTrustWorkspace();
            } catch (e) {}
        }

        async function rollbackPhase6Action(actionId) {
            const reason = prompt('অ্যাকশন রোলব্যাক করার কারণ লিখুন:', 'প্রশাসনিক পুনরুদ্ধার');
            if (!reason || reason.trim().length < 5) return;
            try {
                const res = await apiRequest(`/threat-detection/actions/${actionId}/rollback`, 'POST', { reason: reason.trim() });
                showToast(res.message || 'অ্যাকশন রোলব্যাক সম্পন্ন হয়েছে।');
                loadZeroTrustWorkspace();
            } catch (e) {}
        }

        async function markPhase6FalsePositive(detectionId) {
            const reason = prompt('ফলস পজিটিভ হিসেবে চিহ্নিত করার কারণ লিখুন:', 'বৈধ প্রশাসনিক পরীক্ষা');
            if (!reason || reason.trim().length < 5) return;
            try {
                const res = await apiRequest(`/threat-detection/detections/${detectionId}/false-positive`, 'POST', { reason: reason.trim() });
                showToast(res.message || 'ফলস পজিটিভ হিসেবে চিহ্নিত হয়েছে।');
                loadZeroTrustWorkspace();
            } catch (e) {}
        }

        async function reviewPhase6Control(controlId) {
            const reason = prompt('কমপ্লায়েন্স কন্ট্রোল রিভিউ নোট লিখুন:', 'প্রমাণাদি এবং হ্যাশ যাচাইকৃত');
            if (!reason || reason.trim().length < 5) return;
            try {
                const res = await apiRequest(`/compliance-controls/${controlId}/review`, 'POST', { reason: reason.trim() });
                showToast(res.message || 'কমপ্লায়েন্স কন্ট্রোল রিভিউ সম্পন্ন হয়েছে।');
                loadZeroTrustWorkspace();
            } catch (e) {}
        }

        async function togglePhase6PolicyMfa(policyId, requireMfa) {
            const reason = prompt('জিরো-ট্রাস্ট পলিসি পরিবর্তনের কারণ লিখুন:', 'নিরাপত্তা নীতি হালনাগাদ');
            if (!reason || reason.trim().length < 5) return;
            try {
                const res = await apiRequest(`/zero-trust/policies/${policyId}`, 'PUT', {
                    require_mfa: requireMfa,
                    reason: reason.trim()
                });
                showToast(res.message || 'জিরো-ট্রাস্ট পলিসি আপডেট হয়েছে।');
                loadZeroTrustWorkspace();
            } catch (e) {}
        }
    </script>
</body>
</html>
