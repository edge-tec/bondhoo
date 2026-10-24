<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>সেটিংস ও প্রাইভেসি সেন্টার — SocialHub</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --fb-bg: #f0f2f5;
            --fb-primary: #1877f2;
            --fb-primary-hover: #166fe5;
            --fb-green: #42b72a;
            --fb-red: #e41e3f;
            --fb-border: #ccd0d5;
            --fb-card-bg: #ffffff;
            --fb-text-primary: #050505;
            --fb-text-secondary: #65676b;
            --fb-hover: #e4e6eb;
            --fb-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Hind Siliguri', 'Inter', sans-serif; }
        body {
            background: var(--fb-bg);
            color: var(--fb-text-primary);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
        }

        /* Top Header Navigation */
        .site-header {
            background: #ffffff;
            border-bottom: 1px solid var(--fb-border);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        .header-inner {
            max-width: 1140px;
            margin: 0 auto;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .brand-wrap { display: flex; align-items: center; gap: 12px; }
        .brand-logo {
            font-size: 26px;
            font-weight: 800;
            color: var(--fb-primary);
            text-decoration: none;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .brand-tag {
            font-size: 11px;
            font-weight: 700;
            background: rgba(24, 119, 242, 0.1);
            color: var(--fb-primary);
            padding: 3px 8px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .header-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .btn-nav {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--fb-text-secondary);
            text-decoration: none;
            padding: 7px 14px;
            border-radius: 8px;
            background: var(--fb-hover);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-nav:hover { background: #d8dadf; color: var(--fb-text-primary); }
        .btn-nav.active { background: var(--fb-primary); color: #ffffff; }

        /* Main Container */
        .settings-wrapper {
            max-width: 1140px;
            margin: 24px auto;
            padding: 0 16px;
            flex: 1;
            width: 100%;
        }
        .settings-layout {
            display: grid;
            grid-template-columns: 290px 1fr;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 860px) {
            .settings-layout { grid-template-columns: 1fr; }
        }

        /* Sidebar Navigation */
        .settings-sidebar {
            background: var(--fb-card-bg);
            border-radius: 12px;
            border: 1px solid var(--fb-border);
            box-shadow: var(--fb-shadow);
            overflow: hidden;
            position: sticky;
            top: 76px;
        }
        .sidebar-header {
            padding: 16px 18px;
            border-bottom: 1px solid var(--fb-border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-header h2 { font-size: 18px; font-weight: 800; color: var(--fb-text-primary); }
        .sidebar-menu { list-style: none; padding: 8px; display: flex; flex-direction: column; gap: 4px; }
        .sidebar-menu-btn {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: var(--fb-text-primary);
            font-size: 14.5px;
            font-weight: 600;
            text-align: left;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
        }
        .sidebar-menu-btn:hover { background: var(--fb-hover); }
        .sidebar-menu-btn.active {
            background: rgba(24, 119, 242, 0.1);
            color: var(--fb-primary);
            font-weight: 700;
        }
        .sidebar-menu-btn svg { flex-shrink: 0; }

        .sidebar-divider {
            height: 1px;
            background: var(--fb-border);
            margin: 8px 12px;
        }
        .sidebar-subhead {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--fb-text-secondary);
            text-transform: uppercase;
            padding: 8px 14px 4px 14px;
            letter-spacing: 0.5px;
        }

        /* Content Panel */
        .settings-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .settings-card {
            background: var(--fb-card-bg);
            border-radius: 12px;
            border: 1px solid var(--fb-border);
            box-shadow: var(--fb-shadow);
            padding: 24px;
        }
        .card-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--fb-border);
            flex-wrap: wrap;
            gap: 12px;
        }
        .card-title-group h3 {
            font-size: 20px;
            font-weight: 800;
            color: var(--fb-text-primary);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-title-group p {
            font-size: 13.5px;
            color: var(--fb-text-secondary);
            line-height: 1.4;
        }

        /* Form Controls */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-label {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--fb-text-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 11px 14px;
            border-radius: 8px;
            border: 1px solid var(--fb-border);
            font-size: 14px;
            color: var(--fb-text-primary);
            background: #ffffff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--fb-primary);
            box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.15);
        }
        .form-input:disabled {
            background: #f0f2f5;
            color: var(--fb-text-secondary);
            cursor: not-allowed;
        }
        .form-hint { font-size: 12px; color: var(--fb-text-secondary); margin-top: 3px; }

        /* Buttons */
        .btn-submit {
            background: var(--fb-primary);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-submit:hover { background: var(--fb-primary-hover); }
        .btn-submit:active { transform: scale(0.98); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

        .btn-secondary {
            background: var(--fb-hover);
            color: var(--fb-text-primary);
            font-size: 13.5px;
            font-weight: 600;
            padding: 9px 16px;
            border-radius: 8px;
            border: 1px solid var(--fb-border);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-secondary:hover { background: #d8dadf; }

        .btn-danger-outline {
            background: transparent;
            color: var(--fb-red);
            font-size: 12.5px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid rgba(228, 30, 63, 0.4);
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-danger-outline:hover { background: rgba(228, 30, 63, 0.1); }

        /* Switch Toggle */
        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid #f0f2f5;
            gap: 16px;
        }
        .toggle-row:last-child { border-bottom: none; }
        .toggle-text h4 { font-size: 14.5px; font-weight: 700; color: var(--fb-text-primary); margin-bottom: 3px; }
        .toggle-text p { font-size: 12.5px; color: var(--fb-text-secondary); line-height: 1.4; }
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px;
            flex-shrink: 0;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .25s;
            border-radius: 26px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 20px; width: 20px;
            left: 3px; bottom: 3px;
            background-color: white;
            transition: .25s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        input:checked + .slider { background-color: var(--fb-primary); }
        input:checked + .slider:before { transform: translateX(22px); }

        /* Blocked Users List */
        .blocked-user-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-bottom: 8px;
            transition: background 0.2s;
        }
        .blocked-user-info { display: flex; align-items: center; gap: 12px; }
        .blocked-user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--fb-border);
        }
        .blocked-user-name { font-size: 14px; font-weight: 700; color: var(--fb-text-primary); }
        .blocked-user-username { font-size: 12px; color: var(--fb-text-secondary); }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
        }
        .badge-success { background: #e7f8ec; color: #1e8e3e; }
        .badge-warning { background: #fff8e1; color: #b78103; }

        /* Toast Alert */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        .toast {
            min-width: 280px;
            max-width: 420px;
            background: #1e293b;
            color: #ffffff;
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 14px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: auto;
        }
        .toast.show { opacity: 1; transform: translateY(0); }
        .toast.toast-success { background: #065f46; border-left: 4px solid #10b981; }
        .toast.toast-error { background: #991b1b; border-left: 4px solid #f87171; }

        /* Footer */
        .site-footer {
            background: #ffffff;
            border-top: 1px solid var(--fb-border);
            padding: 18px 16px;
            margin-top: 40px;
            text-align: center;
            font-size: 13px;
            color: var(--fb-text-secondary);
        }
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        .footer-link {
            color: var(--fb-text-secondary);
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-link:hover { color: var(--fb-primary); text-decoration: underline; }

        /* Tab Visibility */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; animation: fadeIn 0.25s ease-out; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <!-- Site Header -->
    <header class="site-header">
        <div class="header-inner">
            <div class="brand-wrap">
                <a href="/dashboard" class="brand-logo" title="SocialHub ড্যাশবোর্ডে ফিরুন" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:9px;background:#1877f2;color:#fff;box-shadow:0 2px 6px rgba(24,119,242,0.35);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </span>
                    <span style="font-size:1.35rem;font-weight:800;letter-spacing:-0.03em;color:#1877f2;font-family:'Inter',sans-serif;">SocialHub</span>
                </a>
                <span class="brand-tag">সেটিংস সেন্টার</span>
            </div>

            <div class="header-actions">
                <a href="/dashboard" class="btn-nav">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <span>হোম ফিড</span>
                </a>
                <a href="/app/profile/{{ $user->username }}" class="btn-nav">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span>প্রোফাইল</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Workspace -->
    <main class="settings-wrapper">
        <div class="settings-layout">
            
            <!-- Sidebar Navigation -->
            <aside class="settings-sidebar">
                <div class="sidebar-header">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--fb-primary);"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <h2>সেটিংস ও নিয়ন্ত্রণ</h2>
                </div>

                <ul class="sidebar-menu">
                    <li>
                        <button type="button" class="sidebar-menu-btn {{ $activeTab === 'account' ? 'active' : '' }}" onclick="switchSettingsTab('account')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>অ্যাকাউন্ট সাধারণ তথ্য</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="sidebar-menu-btn {{ $activeTab === 'security' ? 'active' : '' }}" onclick="switchSettingsTab('security')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <span>নিরাপত্তা ও লগইন</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="sidebar-menu-btn {{ $activeTab === 'privacy' ? 'active' : '' }}" onclick="switchSettingsTab('privacy')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            <span>প্রাইভেসি ও অডিয়েন্স</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="sidebar-menu-btn {{ $activeTab === 'blocking' ? 'active' : '' }}" onclick="switchSettingsTab('blocking')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                            <span>ব্লকিং সেন্টার</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="sidebar-menu-btn {{ $activeTab === 'notifications' ? 'active' : '' }}" onclick="switchSettingsTab('notifications')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                            <span>নোটিফিকেশন প্রেফারেন্স</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="sidebar-menu-btn {{ $activeTab === 'sounds' ? 'active' : '' }}" onclick="switchSettingsTab('sounds')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                            <span>মেসেঞ্জার সাউন্ড ও রিংটোন</span>
                        </button>
                    </li>

                    <li class="sidebar-divider"></li>
                    <li class="sidebar-subhead">উন্নত টুলস ও ডিভাইস</li>

                    <li>
                        <a href="/settings/two-factor" class="sidebar-menu-btn" title="সম্পূর্ণ QR কোড ও ব্যাকআপ কোড ম্যানেজমেন্ট">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                            <span>2FA অথেনটিকেশন</span>
                        </a>
                    </li>
                    <li>
                        <a href="/settings/devices" class="sidebar-menu-btn" title="কানেক্টেড ডিভাইস ও সেশন">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                            <span>কানেক্টেড ডিভাইস</span>
                        </a>
                    </li>
                    <li>
                        <a href="/settings/notifications" class="sidebar-menu-btn" title="ইমেইল ও এসএমএস নোটিফিকেশন">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            <span>ইমেইল প্রেফারেন্স</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- Main Content Area -->
            <div class="settings-content">
                
                <!-- TAB 1: ACCOUNT GENERAL -->
                <section id="panel-account" class="tab-panel {{ $activeTab === 'account' ? 'active' : '' }}">
                    <div class="settings-card">
                        <div class="card-header-bar">
                            <div class="card-title-group">
                                <h3>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    <span>অ্যাকাউন্ট সাধারণ তথ্য (General Account Settings)</span>
                                </h3>
                                <p>আপনার যোগাযোগের তথ্য, ইউজারনেম এবং পরিচিতি আপডেট করুন।</p>
                            </div>
                        </div>

                        <form id="accountForm" onsubmit="handleAccountSubmit(event)">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="accountName">পূর্ণ নাম (Full Name)</label>
                                    <input type="text" id="accountName" name="name" class="form-input" value="{{ $user->name }}" required maxlength="100">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="accountUsername">ইউজারনেম (Username)</label>
                                    <input type="text" id="accountUsername" name="username" class="form-input" value="{{ $user->username }}" required maxlength="50">
                                    <span class="form-hint">আপনার প্রোফাইল লিংক: bondhoo.com/app/profile/<strong id="previewUsername">{{ $user->username }}</strong></span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="accountEmail">
                                        <span>ইমেইল ঠিকানা (Email)</span>
                                        @if($user->hasVerifiedEmail())
                                            <span class="badge badge-success">যাচাইকৃত</span>
                                        @endif
                                    </label>
                                    <input type="email" id="accountEmail" class="form-input" value="{{ $user->email }}" disabled title="ইমেইল পরিবর্তন করতে ভেরিফিকেশন সহায়তা প্রয়োজন">
                                    <span class="form-hint">ইমেইলটি প্রাথমিক শনাক্তকারী হিসেবে সংরক্ষিত।</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="accountPhone">মোবাইল নম্বর (Phone)</label>
                                    <input type="text" id="accountPhone" name="phone" class="form-input" value="{{ $user->phone }}" placeholder="+8801XXXXXXXXX">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="accountGender">লিঙ্গ (Gender)</label>
                                    <select id="accountGender" name="gender" class="form-select">
                                        <option value="">নির্বাচন করুন</option>
                                        <option value="male" {{ ($user->gender ?? $user->profile?->gender) === 'male' ? 'selected' : '' }}>পুরুষ (Male)</option>
                                        <option value="female" {{ ($user->gender ?? $user->profile?->gender) === 'female' ? 'selected' : '' }}>নারী (Female)</option>
                                        <option value="other" {{ ($user->gender ?? $user->profile?->gender) === 'other' ? 'selected' : '' }}>অন্যান্য (Other)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="accountBirthDate">জন্ম তারিখ (Birth Date)</label>
                                    <input type="date" id="accountBirthDate" name="birth_date" class="form-input" value="{{ $user->birth_date?->format('Y-m-d') ?? ($user->profile?->birth_date ? date('Y-m-d', strtotime((string)$user->profile->birth_date)) : '') }}">
                                </div>

                                <div class="form-group full-width">
                                    <label class="form-label" for="accountBio">সংক্ষিপ্ত বায়ো (Bio)</label>
                                    <textarea id="accountBio" name="bio" class="form-textarea" rows="3" placeholder="নিজের সম্পর্কে সংক্ষেপে কিছু লিখুন...">{{ $user->profile?->bio }}</textarea>
                                </div>
                            </div>

                            <div style="margin-top: 16px; display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                                <button type="submit" class="btn-submit" id="btnSaveAccount">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                    <span>পরিবর্তন সংরক্ষণ করুন</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- TAB 2: SECURITY & LOGIN -->
                <section id="panel-security" class="tab-panel {{ $activeTab === 'security' ? 'active' : '' }}">
                    <!-- Password Change -->
                    <div class="settings-card" style="margin-bottom: 20px;">
                        <div class="card-header-bar">
                            <div class="card-title-group">
                                <h3>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    <span>পাসওয়ার্ড পরিবর্তন (Change Password)</span>
                                </h3>
                                <p>নিরাপদ ও শক্তিশালী পাসওয়ার্ড ব্যবহার করুন যাতে কেউ সহজে অনুমান না করতে পারে।</p>
                            </div>
                        </div>

                        <form id="passwordForm" onsubmit="handlePasswordSubmit(event)">
                            <div class="form-group">
                                <label class="form-label" for="currentPassword">বর্তমান পাসওয়ার্ড (Current Password)</label>
                                <input type="password" id="currentPassword" name="current_password" class="form-input" required autocomplete="current-password">
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="newPassword">নতুন পাসওয়ার্ড (New Password)</label>
                                    <input type="password" id="newPassword" name="password" class="form-input" required minlength="8" autocomplete="new-password">
                                    <span class="form-hint">কমপক্ষে ৮টি অক্ষর হতে হবে।</span>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="confirmPassword">নতুন পাসওয়ার্ড নিশ্চিত করুন</label>
                                    <input type="password" id="confirmPassword" name="password_confirmation" class="form-input" required minlength="8" autocomplete="new-password">
                                </div>
                            </div>

                            <div style="margin-top: 16px; display: flex; align-items: center; justify-content: flex-end;">
                                <button type="submit" class="btn-submit" id="btnSavePassword">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                    <span>পাসওয়ার্ড আপডেট করুন</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Two-Factor Authentication Card -->
                    <div class="settings-card" style="margin-bottom: 20px;">
                        <div class="card-header-bar">
                            <div class="card-title-group">
                                <h3>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                                    <span>টু-ফ্যাক্টর অথেনটিকেশন (Two-Factor Authentication)</span>
                                </h3>
                                <p>নতুন কোনো ডিভাইসে লগইন করার সময় অতিরিক্ত সিকিউরিটি কোডের মাধ্যমে অ্যাকাউন্ট সুরক্ষিত রাখুন।</p>
                            </div>
                            <span class="badge {{ $is2FaEnabled ? 'badge-success' : 'badge-warning' }}" id="twoFactorBadge">
                                {{ $is2FaEnabled ? 'সক্রিয় রয়েছে (Active)' : 'নিষ্ক্রিয় (Disabled)' }}
                            </span>
                        </div>

                        <div class="toggle-row">
                            <div class="toggle-text">
                                <h4>2FA দ্রুত নিয়ন্ত্রণ</h4>
                                <p>এটি চালু থাকলে প্রতিটি নতুন লগইনে ওয়ান-টাইম অথেনটিকেশন কোড প্রয়োজন হবে।</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="twoFactorToggle" {{ $is2FaEnabled ? 'checked' : '' }} onchange="handle2FaToggle(this.checked)">
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div style="margin-top: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <span style="font-size: 13px; color: var(--fb-text-secondary);">
                                অব্যবহৃত ব্যাকআপ কোড সংখ্যা: <strong>{{ $recoveryCodesCount }}</strong> টি
                            </span>
                            <a href="/settings/two-factor" class="btn-secondary">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                <span>QR কোড ও সম্পূর্ণ 2FA সেটআপ পেজ</span>
                            </a>
                        </div>
                    </div>

                    <!-- Connected Sessions -->
                    <div class="settings-card">
                        <div class="card-header-bar">
                            <div class="card-title-group">
                                <h3>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                                    <span>ডিভাইস ও লগইন সেশন (Where You're Logged In)</span>
                                </h3>
                                <p>আপনার অ্যাকাউন্টে সক্রিয় থাকা বিভিন্ন ব্রাউজার এবং মোবাইল ডিভাইসের বিবরণ পরিচালনা করুন।</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                            <div>
                                <p style="font-size: 13.5px; font-weight: 600; color: var(--fb-text-primary);">বর্তমান ব্রাউজার ও ডিভাইস সক্রিয়</p>
                                <p style="font-size: 12px; color: var(--fb-text-secondary);">অন্যান্য অপ্রয়োজনীয় বা সন্দেহজনক সেশন মুছে ফেলতে ডিভাইস ম্যানেজারে যান।</p>
                            </div>
                            <a href="/settings/devices" class="btn-secondary">
                                <span>ডিভাইস ম্যানেজার খুলুন</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        </div>
                    </div>
                </section>

                <!-- TAB 3: PRIVACY & AUDIENCE -->
                <section id="panel-privacy" class="tab-panel {{ $activeTab === 'privacy' ? 'active' : '' }}">
                    <div class="settings-card">
                        <div class="card-header-bar">
                            <div class="card-title-group">
                                <h3>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                    <span>প্রাইভেসি ও অডিয়েন্স সেটিংস (Privacy & Audience)</span>
                                </h3>
                                <p>কে আপনার পোস্ট, ফ্রেন্ড লিস্ট দেখতে পাবে এবং যোগাযোগ করতে পারবে তা নিয়ন্ত্রণ করুন।</p>
                            </div>
                        </div>

                        <form id="privacyForm" onsubmit="handlePrivacySubmit(event)">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="whoCanSeePosts">আপনার ভবিষ্যৎ পোস্ট কারা দেখতে পারবে?</label>
                                    <select id="whoCanSeePosts" name="who_can_see_posts" class="form-select">
                                        <option value="public" {{ ($userSetting->who_can_see_posts ?? 'public') === 'public' ? 'selected' : '' }}>পাবলিক (Public) — সবাই দেখতে পাবে</option>
                                        <option value="friends" {{ ($userSetting->who_can_see_posts ?? '') === 'friends' ? 'selected' : '' }}>বন্ধুরা (Friends Only)</option>
                                        <option value="only_me" {{ ($userSetting->who_can_see_posts ?? '') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি (Only Me)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="whoCanSendFriendRequests">কে আপনাকে বন্ধু হওয়ার অনুরোধ পাঠাতে পারবে?</label>
                                    <select id="whoCanSendFriendRequests" name="who_can_send_friend_requests" class="form-select">
                                        <option value="everyone" {{ ($userSetting->who_can_send_friend_requests ?? 'everyone') === 'everyone' ? 'selected' : '' }}>সবাই (Everyone)</option>
                                        <option value="friends_of_friends" {{ ($userSetting->who_can_send_friend_requests ?? '') === 'friends_of_friends' ? 'selected' : '' }}>বন্ধুদের বন্ধুরা (Friends of friends)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="whoCanSeeFriends">কে আপনার বন্ধুদের তালিকা দেখতে পারবে?</label>
                                    <select id="whoCanSeeFriends" name="who_can_see_friends" class="form-select">
                                        <option value="public" {{ ($userSetting->who_can_see_friends ?? 'public') === 'public' ? 'selected' : '' }}>পাবলিক (Public)</option>
                                        <option value="friends" {{ ($userSetting->who_can_see_friends ?? '') === 'friends' ? 'selected' : '' }}>বন্ধুরা (Friends)</option>
                                        <option value="only_me" {{ ($userSetting->who_can_see_friends ?? '') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি (Only Me)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="whoCanMessage">কে আপনাকে সরাসরি মেসেজ পাঠাতে পারবে?</label>
                                    <select id="whoCanMessage" name="who_can_message" class="form-select">
                                        <option value="everyone" {{ ($userSetting->who_can_message ?? 'everyone') === 'everyone' ? 'selected' : '' }}>সবাই (Everyone)</option>
                                        <option value="friends" {{ ($userSetting->who_can_message ?? '') === 'friends' ? 'selected' : '' }}>শুধুমাত্র বন্ধুরা (Friends Only)</option>
                                    </select>
                                </div>
                            </div>

                            <div style="margin-top: 10px; border-top: 1px solid #f0f2f5; padding-top: 10px;">
                                <div class="toggle-row">
                                    <div class="toggle-text">
                                        <h4>প্রোফাইল লক (Profile Lock)</h4>
                                        <p>লক করা থাকলে অপরিচিত কেউ আপনার বড় প্রোফাইল পিকচার বা ফ্রেন্ডস ছাড়া পোস্ট দেখতে পারবে না।</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="isProfileLocked" name="is_profile_locked" value="1" {{ $userSetting->is_profile_locked ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="toggle-row">
                                    <div class="toggle-text">
                                        <h4>প্রোফাইল পিকচার গার্ড (Avatar Guard)</h4>
                                        <p>অন্য ব্যবহারকারীরা আপনার প্রোফাইল ছবি ডাউনলোড বা স্ক্রিনশট নেওয়া থেকে প্রতিহত হবে।</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="hasAvatarGuard" name="has_avatar_guard" value="1" {{ $userSetting->has_avatar_guard ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="toggle-row">
                                    <div class="toggle-text">
                                        <h4>অনলাইন উপস্থিতি প্রদর্শন (Show Online Status)</h4>
                                        <p>আপনি সক্রিয় থাকলে বন্ধুদের কাছে সবুজ অ্যাক্টিভ স্ট্যাটাস দেখতে দেওয়া হবে।</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="showOnlineStatus" name="show_online_status" value="1" {{ $privacySetting->show_online_status ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="toggle-row">
                                    <div class="toggle-text">
                                        <h4>সার্চ ইঞ্জিন ইনডেক্সিং (Search Engine Indexing)</h4>
                                        <p>Google বা অন্যান্য সার্চ ইঞ্জিনে আপনার পাবলিক প্রোফাইল খুঁজে পাওয়ার অনুমতি।</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="searchEngineIndexing" name="search_engine_indexing" value="1" {{ $privacySetting->search_engine_indexing ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div style="margin-top: 20px; display: flex; align-items: center; justify-content: flex-end;">
                                <button type="submit" class="btn-submit" id="btnSavePrivacy">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                    <span>প্রাইভেসি পরিবর্তন সংরক্ষণ করুন</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- TAB 4: BLOCKING -->
                <section id="panel-blocking" class="tab-panel {{ $activeTab === 'blocking' ? 'active' : '' }}">
                    <div class="settings-card">
                        <div class="card-header-bar">
                            <div class="card-title-group">
                                <h3>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                    <span>ব্লকিং সেন্টার (Blocking Center)</span>
                                </h3>
                                <p>কাউকে ব্লক করলে সে আপনার টাইমলাইন দেখতে পারবে না, মেসেজ বা ফ্রেন্ড রিকোয়েস্ট পাঠাতে পারবে না।</p>
                            </div>
                        </div>

                        <!-- Quick Block Input -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 20px;">
                            <label class="form-label" style="margin-bottom: 8px;">কাউকে আইডি দিয়ে ব্লক করুন</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" id="blockUserIdInput" class="form-input" placeholder="ব্যবহারকারীর ইউজার আইডি (User ID) দিন" style="max-width: 300px;">
                                <button type="button" class="btn-submit" onclick="handleDirectBlock()" style="background: var(--fb-red);">
                                    <span>ব্লক করুন</span>
                                </button>
                            </div>
                        </div>

                        <!-- Blocked Users List -->
                        <div>
                            <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 12px; color: var(--fb-text-primary);">
                                বর্তমানে ব্লককৃত ব্যবহারকারী (<span id="blockedUsersCount">{{ count($blockedUsers) }}</span> জন)
                            </h4>

                            <div id="blockedUsersList">
                                @forelse($blockedUsers as $blocked)
                                    <div class="blocked-user-row" id="blocked-user-{{ $blocked['id'] }}">
                                        <div class="blocked-user-info">
                                            <img src="{{ $blocked['avatar'] }}" alt="{{ $blocked['name'] }}" class="blocked-user-avatar">
                                            <div>
                                                <div class="blocked-user-name">{{ $blocked['name'] }}</div>
                                                <div class="blocked-user-username">@ {{ $blocked['username'] }}</div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-danger-outline" onclick="handleUnblock({{ $blocked['id'] }}, '{{ addslashes($blocked['name']) }}')">
                                            <span>আনব্লক করুন</span>
                                        </button>
                                    </div>
                                @empty
                                    <p id="noBlockedMsg" style="font-size: 13.5px; color: var(--fb-text-secondary); padding: 16px; text-align: center; background: #f8fafc; border-radius: 8px;">
                                        আপনি কাউকে ব্লক করেননি। আপনার ব্লক তালিকা খালি।
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>

                <!-- TAB 5: NOTIFICATIONS -->
                <section id="panel-notifications" class="tab-panel {{ $activeTab === 'notifications' ? 'active' : '' }}">
                    <div class="settings-card">
                        <div class="card-header-bar">
                            <div class="card-title-group">
                                <h3>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                    <span>নোটিফিকেশন প্রেফারেন্স (Notifications Preferences)</span>
                                </h3>
                                <p>আপনার গুরুত্বপূর্ণ নোটিফিকেশনগুলো কোন মাধ্যমে পেতে চান তা নির্ধারণ করুন।</p>
                            </div>
                        </div>

                        <form id="notificationForm" onsubmit="handleNotificationSubmit(event)">
                            <div class="toggle-row">
                                <div class="toggle-text">
                                    <h4>ইমেইল নোটিফিকেশন (Email Alerts)</h4>
                                    <p>নতুন ফ্রেন্ড রিকোয়েস্ট, পোস্ট রিঅ্যাকশন বা গুরুত্বপূর্ণ অ্যাকাউন্টের তথ্যের ইমেইল নোটিফিকেশন।</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="notificationEmail" name="notification_email" value="1" {{ $userSetting->notification_email ? 'checked' : '' }}>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="toggle-row">
                                <div class="toggle-text">
                                    <h4>পুশ নোটিফিকেশন (Browser Push Alerts)</h4>
                                    <p>সরাসরি ব্রাউজারে রিয়েল-টাইম বেল অ্যালার্ট ও সাউন্ড প্লে হবে।</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="notificationPush" name="notification_push" value="1" {{ $userSetting->notification_push ? 'checked' : '' }}>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="toggle-row">
                                <div class="toggle-text">
                                    <h4>এসএমএস অ্যালার্ট (SMS Notifications)</h4>
                                    <p>লগইন সিকিউরিটি ও জরুরি নোটিফিকেশন মোবাইল এসএমএস এর মাধ্যমে পাঠানো হবে।</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="notificationSms" name="notification_sms" value="1" {{ $userSetting->notification_sms ? 'checked' : '' }}>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div style="margin-top: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                                <a href="/settings/notifications" class="btn-secondary">
                                    <span>বিস্তারিত ক্যাটাগরি অনুযায়ী নোটিফিকেশন পেজ</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </a>

                                <button type="submit" class="btn-submit" id="btnSaveNotification">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                    <span>নোটিফিকেশন প্রেফারেন্স সংরক্ষণ</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- TAB 6: MESSENGER SOUNDS & RINGTONES -->
                <section id="panel-sounds" class="tab-panel {{ $activeTab === 'sounds' ? 'active' : '' }}">
                    <div class="settings-card">
                        <div class="card-header-bar">
                            <div class="card-title-group">
                                <h3>
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                                    <span>মেসেঞ্জার সাউন্ড ও রিংটোন সেটিংস (Messenger Sounds & Ringtones)</span>
                                </h3>
                                <p>ইনকামিং অডিও/ভিডিও কল, বার্তা প্রাপ্তি ও পাঠানোর বিভিন্ন রিংটোন ও ভলিউম নিয়ন্ত্রণ করুন।</p>
                            </div>
                        </div>

                        <form id="soundsForm" onsubmit="handleSoundsSubmit(event)">
                            <!-- Master Sounds Switch -->
                            <div class="toggle-row" style="background: rgba(24, 119, 242, 0.05); padding: 14px 16px; border-radius: 10px; border: 1px solid rgba(24, 119, 242, 0.15); margin-bottom: 20px;">
                                <div class="toggle-text">
                                    <h4 style="color: var(--fb-primary);">🔔 মেসেঞ্জার সাউন্ড সক্রিয় রাখুন</h4>
                                    <p>এটি নিষ্ক্রিয় করলে সব ধরনের কল রিংটোন, বার্তা প্রাপ্তি ও পাঠানোর শব্দ নীরব থাকবে।</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="sndMasterToggle" name="messenger_sounds_enabled" value="1" {{ ($soundSettings['messenger_sounds_enabled'] ?? $soundSettings['settings']['messenger_sounds_enabled'] ?? true) ? 'checked' : '' }}>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <!-- CARD 1: Incoming Call Ringtone -->
                            <div style="border: 1px solid var(--fb-border); border-radius: 10px; padding: 16px; margin-bottom: 16px; background: #ffffff;">
                                <div class="toggle-row" style="padding-top: 0; padding-bottom: 12px;">
                                    <div class="toggle-text">
                                        <h4>📞 ইনকামিং কল রিংটোন (Incoming Call Ringtone)</h4>
                                        <p>অডিও বা ভিডিও কল আসার সময়ে পুনরাবৃত্তি হয়ে বাজবে।</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="sndCallToggle" name="incoming_call_sound_enabled" value="1" {{ ($soundSettings['incoming_call_sound_enabled'] ?? $soundSettings['settings']['incoming_call_sound_enabled'] ?? true) ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="form-grid" style="margin-top: 8px;">
                                    <div class="form-group">
                                        <label class="form-label" for="sndCallSelect">রিংটোন নির্বাচন করুন</label>
                                        <div style="display: flex; gap: 8px;">
                                            <select id="sndCallSelect" name="incoming_call_sound" class="form-select">
                                                <option value="bondhoo_ring" {{ ($soundSettings['incoming_call_sound'] ?? $soundSettings['settings']['incoming_call_sound'] ?? 'bondhoo_ring') === 'bondhoo_ring' ? 'selected' : '' }}>Bondhoo Ring (ডিফল্ট)</option>
                                                <option value="digital_bell" {{ ($soundSettings['incoming_call_sound'] ?? $soundSettings['settings']['incoming_call_sound'] ?? '') === 'digital_bell' ? 'selected' : '' }}>Digital Bell (ডিজিটাল বেল)</option>
                                                <option value="celesta_chime" {{ ($soundSettings['incoming_call_sound'] ?? $soundSettings['settings']['incoming_call_sound'] ?? '') === 'celesta_chime' ? 'selected' : '' }}>Celesta Chime (সেলেনিয়াম মেলোডি)</option>
                                                @if(!empty($soundSettings['custom_call_sound_path']) || !empty($soundSettings['settings']['custom_call_sound_url']))
                                                    <option value="custom" {{ ($soundSettings['incoming_call_sound'] ?? $soundSettings['settings']['incoming_call_sound'] ?? '') === 'custom' ? 'selected' : '' }}>কাস্টম আপলোডকৃত রিংটোন</option>
                                                @endif
                                            </select>
                                            <button type="button" class="btn-secondary" onclick="previewSoundTone('call')" title="সাউন্ড প্রিভিউ শুনুন" style="padding: 0 14px;">
                                                <span id="playIconCall">▶</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="sndCallVol">
                                            <span>ভলিউম</span>
                                            <span id="callVolLabel" style="font-weight: 600; color: var(--fb-primary);">{{ $soundSettings['incoming_call_volume'] ?? $soundSettings['settings']['incoming_call_volume'] ?? 80 }}%</span>
                                        </label>
                                        <input type="range" id="sndCallVol" name="incoming_call_volume" min="0" max="100" value="{{ $soundSettings['incoming_call_volume'] ?? $soundSettings['settings']['incoming_call_volume'] ?? 80 }}" class="form-input" style="padding: 0; cursor: pointer;" oninput="document.getElementById('callVolLabel').innerText = this.value + '%'">
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px; font-size: 12px; color: var(--fb-text-secondary); flex-wrap: wrap; gap: 8px;">
                                    <span>কাস্টম রিংটোন আপলোড করতে চান? (MP3/WAV, max 2MB)</span>
                                    <label class="btn-secondary" style="font-size: 12px; padding: 5px 10px; cursor: pointer;">
                                        <span>ফাইল বাছুন</span>
                                        <input type="file" accept="audio/*" style="display: none;" onchange="uploadCustomSoundAudio('incoming_call', this)">
                                    </label>
                                </div>
                            </div>

                            <!-- CARD 2: Incoming Message Tone -->
                            <div style="border: 1px solid var(--fb-border); border-radius: 10px; padding: 16px; margin-bottom: 16px; background: #ffffff;">
                                <div class="toggle-row" style="padding-top: 0; padding-bottom: 12px;">
                                    <div class="toggle-text">
                                        <h4>💬 ইনকামিং বার্তা টোন (Incoming Message Chime)</h4>
                                        <p>কোনো বন্ধু নতুন মেসেজ পাঠালে এই সাউন্ড বাজবে।</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="sndMsgInToggle" name="incoming_message_sound_enabled" value="1" {{ ($soundSettings['incoming_message_sound_enabled'] ?? $soundSettings['settings']['incoming_message_sound_enabled'] ?? true) ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="form-grid" style="margin-top: 8px;">
                                    <div class="form-group">
                                        <label class="form-label" for="sndMsgInSelect">মেসেজ টোন নির্বাচন করুন</label>
                                        <div style="display: flex; gap: 8px;">
                                            <select id="sndMsgInSelect" name="incoming_message_sound" class="form-select">
                                                <option value="bubble_pop" {{ ($soundSettings['incoming_message_sound'] ?? $soundSettings['settings']['incoming_message_sound'] ?? 'bubble_pop') === 'bubble_pop' ? 'selected' : '' }}>Bubble Pop (ডিফল্ট বাবল)</option>
                                                <option value="clear_ding" {{ ($soundSettings['incoming_message_sound'] ?? $soundSettings['settings']['incoming_message_sound'] ?? '') === 'clear_ding' ? 'selected' : '' }}>Clear Ding (ক্রিস্টাল ডিং)</option>
                                                <option value="soft_chime" {{ ($soundSettings['incoming_message_sound'] ?? $soundSettings['settings']['incoming_message_sound'] ?? '') === 'soft_chime' ? 'selected' : '' }}>Soft Chime (শান্ত চাইম)</option>
                                                @if(!empty($soundSettings['custom_incoming_msg_sound_path']) || !empty($soundSettings['settings']['custom_incoming_msg_sound_url']))
                                                    <option value="custom" {{ ($soundSettings['incoming_message_sound'] ?? $soundSettings['settings']['incoming_message_sound'] ?? '') === 'custom' ? 'selected' : '' }}>কাস্টম আপলোডকৃত টোন</option>
                                                @endif
                                            </select>
                                            <button type="button" class="btn-secondary" onclick="previewSoundTone('incoming_msg')" title="সাউন্ড প্রিভিউ শুনুন" style="padding: 0 14px;">
                                                <span id="playIconMsgIn">▶</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="sndMsgInVol">
                                            <span>ভলিউম</span>
                                            <span id="msgInVolLabel" style="font-weight: 600; color: var(--fb-primary);">{{ $soundSettings['incoming_message_volume'] ?? $soundSettings['settings']['incoming_message_volume'] ?? 70 }}%</span>
                                        </label>
                                        <input type="range" id="sndMsgInVol" name="incoming_message_volume" min="0" max="100" value="{{ $soundSettings['incoming_message_volume'] ?? $soundSettings['settings']['incoming_message_volume'] ?? 70 }}" class="form-input" style="padding: 0; cursor: pointer;" oninput="document.getElementById('msgInVolLabel').innerText = this.value + '%'">
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px; font-size: 12px; color: var(--fb-text-secondary); flex-wrap: wrap; gap: 8px;">
                                    <span>কাস্টম মেসেজ টোন আপলোড (MP3/WAV, max 2MB)</span>
                                    <label class="btn-secondary" style="font-size: 12px; padding: 5px 10px; cursor: pointer;">
                                        <span>ফাইল বাছুন</span>
                                        <input type="file" accept="audio/*" style="display: none;" onchange="uploadCustomSoundAudio('incoming_message', this)">
                                    </label>
                                </div>
                            </div>

                            <!-- CARD 3: Outgoing Message Tone -->
                            <div style="border: 1px solid var(--fb-border); border-radius: 10px; padding: 16px; margin-bottom: 20px; background: #ffffff;">
                                <div class="toggle-row" style="padding-top: 0; padding-bottom: 12px;">
                                    <div class="toggle-text">
                                        <h4>🚀 মেসেজ পাঠানো নিশ্চিতকরণ সাউন্ড (Outgoing Message Swoosh)</h4>
                                        <p>আপনার পাঠানো মেসেজ সফলভাবে ডেলিভার হলে মৃদু সাউন্ড প্লে হবে।</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="sndMsgOutToggle" name="outgoing_message_sound_enabled" value="1" {{ ($soundSettings['outgoing_message_sound_enabled'] ?? $soundSettings['settings']['outgoing_message_sound_enabled'] ?? true) ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="form-grid" style="margin-top: 8px;">
                                    <div class="form-group">
                                        <label class="form-label" for="sndMsgOutSelect">সেন্ট সাউন্ড নির্বাচন করুন</label>
                                        <div style="display: flex; gap: 8px;">
                                            <select id="sndMsgOutSelect" name="outgoing_message_sound" class="form-select">
                                                <option value="subtle_sent" {{ ($soundSettings['outgoing_message_sound'] ?? $soundSettings['settings']['outgoing_message_sound'] ?? 'subtle_sent') === 'subtle_sent' ? 'selected' : '' }}>Subtle Sent (মৃদু নিশ্চিতকরণ)</option>
                                                <option value="soft_swoosh" {{ ($soundSettings['outgoing_message_sound'] ?? $soundSettings['settings']['outgoing_message_sound'] ?? '') === 'soft_swoosh' ? 'selected' : '' }}>Soft Swoosh (সফট সোয়াশ)</option>
                                                <option value="quick_click" {{ ($soundSettings['outgoing_message_sound'] ?? $soundSettings['settings']['outgoing_message_sound'] ?? '') === 'quick_click' ? 'selected' : '' }}>Quick Click (কুইক ক্লিক)</option>
                                                @if(!empty($soundSettings['custom_outgoing_msg_sound_path']) || !empty($soundSettings['settings']['custom_outgoing_msg_sound_url']))
                                                    <option value="custom" {{ ($soundSettings['outgoing_message_sound'] ?? $soundSettings['settings']['outgoing_message_sound'] ?? '') === 'custom' ? 'selected' : '' }}>কাস্টম আপলোডকৃত সাউন্ড</option>
                                                @endif
                                            </select>
                                            <button type="button" class="btn-secondary" onclick="previewSoundTone('outgoing_msg')" title="সাউন্ড প্রিভিউ শুনুন" style="padding: 0 14px;">
                                                <span id="playIconMsgOut">▶</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="sndMsgOutVol">
                                            <span>ভলিউম</span>
                                            <span id="msgOutVolLabel" style="font-weight: 600; color: var(--fb-primary);">{{ $soundSettings['outgoing_message_volume'] ?? $soundSettings['settings']['outgoing_message_volume'] ?? 60 }}%</span>
                                        </label>
                                        <input type="range" id="sndMsgOutVol" name="outgoing_message_volume" min="0" max="100" value="{{ $soundSettings['outgoing_message_volume'] ?? $soundSettings['settings']['outgoing_message_volume'] ?? 60 }}" class="form-input" style="padding: 0; cursor: pointer;" oninput="document.getElementById('msgOutVolLabel').innerText = this.value + '%'">
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px; font-size: 12px; color: var(--fb-text-secondary); flex-wrap: wrap; gap: 8px;">
                                    <span>কাস্টম সেন্ট সাউন্ড আপলোড (MP3/WAV, max 2MB)</span>
                                    <label class="btn-secondary" style="font-size: 12px; padding: 5px 10px; cursor: pointer;">
                                        <span>ফাইল বাছুন</span>
                                        <input type="file" accept="audio/*" style="display: none;" onchange="uploadCustomSoundAudio('outgoing_message', this)">
                                    </label>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 20px;">
                                <button type="button" class="btn-secondary" onclick="resetSoundSettings()">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                                    <span>ডিফল্ট সাউন্ড পুনরুদ্ধার</span>
                                </button>

                                <button type="submit" class="btn-submit" id="btnSaveSounds">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                    <span>সাউন্ড প্রেফারেন্স সংরক্ষণ</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

            </div>
        </div>
    </main>

    <!-- Toast Notifications Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-links">
            <a href="/terms" class="footer-link">ব্যবহারের শর্তাবলী (Terms)</a>
            <a href="/cookies" class="footer-link">কুকিজ পলিসি (Cookies)</a>
            <a href="/settings/privacy" class="footer-link">গোপনীয়তা নীতি (Privacy)</a>
            <a href="/settings/devices" class="footer-link">ডিভাইস নিরাপত্তা</a>
            <a href="/dashboard" class="footer-link">হোম</a>
        </div>
        <div>SocialHub Platform © {{ date('Y') }} — সকল অধিকার সংরক্ষিত।</div>
    </footer>

    <!-- JavaScript Handling Interactivity -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Toast system
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <span>${message}</span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:#fff;cursor:pointer;font-size:16px;">&times;</button>
            `;
            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Tab Switching
        function switchSettingsTab(tabName) {
            document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
            document.querySelectorAll('.sidebar-menu-btn').forEach(btn => btn.classList.remove('active'));

            const targetPanel = document.getElementById(`panel-${tabName}`);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }

            // Update button active state
            document.querySelectorAll('.sidebar-menu-btn').forEach(btn => {
                if (btn.getAttribute('onclick')?.includes(tabName)) {
                    btn.classList.add('active');
                }
            });

            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        }

        // Real-time username preview
        document.getElementById('accountUsername')?.addEventListener('input', function(e) {
            const preview = document.getElementById('previewUsername');
            if (preview) preview.innerText = e.target.value || 'username';
        });

        // 1. Handle Account Form Submission
        async function handleAccountSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveAccount');
            btn.disabled = true;

            const payload = {
                name: document.getElementById('accountName').value.trim(),
                username: document.getElementById('accountUsername').value.trim(),
                phone: document.getElementById('accountPhone').value.trim(),
                gender: document.getElementById('accountGender').value,
                birth_date: document.getElementById('accountBirthDate').value,
                bio: document.getElementById('accountBio').value.trim()
            };

            try {
                const res = await fetch('/api/v1/settings/account', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message || 'অ্যাকাউন্টের তথ্য সফলভাবে আপডেট হয়েছে।', 'success');
                } else {
                    const errorMsg = data.errors ? Object.values(data.errors).flat().join('<br>') : (data.message || 'আপডেট করতে ব্যর্থ হয়েছে।');
                    showToast(errorMsg, 'error');
                }
            } catch (err) {
                showToast('সার্ভার সংযোগে ত্রুটি ঘটেছে। পুনরায় চেষ্টা করুন।', 'error');
            } finally {
                btn.disabled = false;
            }
        }

        // 2. Handle Password Change Submission
        async function handlePasswordSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSavePassword');
            const curPass = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confPass = document.getElementById('confirmPassword').value;

            if (newPass !== confPass) {
                showToast('নতুন পাসওয়ার্ড এবং নিশ্চিতকরণ মেলেনি।', 'error');
                return;
            }

            btn.disabled = true;

            try {
                const res = await fetch('/api/v1/settings/password', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        current_password: curPass,
                        password: newPass,
                        password_confirmation: confPass
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message || 'পাসওয়ার্ড সফলভাবে পরিবর্তিত হয়েছে।', 'success');
                    document.getElementById('passwordForm').reset();
                } else {
                    const errorMsg = data.errors ? Object.values(data.errors).flat().join('<br>') : (data.message || 'পাসওয়ার্ড পরিবর্তন ব্যর্থ হয়েছে।');
                    showToast(errorMsg, 'error');
                }
            } catch (err) {
                showToast('সার্ভার সংযোগে ত্রুটি ঘটেছে।', 'error');
            } finally {
                btn.disabled = false;
            }
        }

        // 3. Handle 2FA Quick Toggle
        async function handle2FaToggle(enabled) {
            try {
                const res = await fetch('/api/v1/settings/2fa/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ enabled: enabled })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message, 'success');
                    const badge = document.getElementById('twoFactorBadge');
                    if (badge) {
                        badge.className = `badge ${enabled ? 'badge-success' : 'badge-warning'}`;
                        badge.innerText = enabled ? 'সক্রিয় রয়েছে (Active)' : 'নিষ্ক্রিয় (Disabled)';
                    }
                } else {
                    showToast(data.message || '2FA স্ট্যাটাস পরিবর্তন ব্যর্থ হয়েছে।', 'error');
                    document.getElementById('twoFactorToggle').checked = !enabled;
                }
            } catch (err) {
                showToast('সার্ভারে যোগাযোগ করা যায়নি।', 'error');
                document.getElementById('twoFactorToggle').checked = !enabled;
            }
        }

        // 4. Handle Privacy Form Submission
        async function handlePrivacySubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSavePrivacy');
            btn.disabled = true;

            const payload = {
                who_can_see_posts: document.getElementById('whoCanSeePosts').value,
                who_can_send_friend_requests: document.getElementById('whoCanSendFriendRequests').value,
                who_can_see_friends: document.getElementById('whoCanSeeFriends').value,
                who_can_message: document.getElementById('whoCanMessage').value,
                is_profile_locked: document.getElementById('isProfileLocked').checked ? 1 : 0,
                has_avatar_guard: document.getElementById('hasAvatarGuard').checked ? 1 : 0,
                show_online_status: document.getElementById('showOnlineStatus').checked ? 1 : 0,
                search_engine_indexing: document.getElementById('searchEngineIndexing').checked ? 1 : 0
            };

            try {
                const res = await fetch('/api/v1/settings/privacy', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message || 'প্রাইভেসি সেটিংস সফলভাবে আপডেট হয়েছে।', 'success');
                } else {
                    showToast(data.message || 'প্রাইভেসি আপডেট করা যায়নি।', 'error');
                }
            } catch (err) {
                showToast('সার্ভার সংযোগে ত্রুটি ঘটেছে।', 'error');
            } finally {
                btn.disabled = false;
            }
        }

        // 5. Handle Notifications Submit
        async function handleNotificationSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveNotification');
            btn.disabled = true;

            const payload = {
                notification_email: document.getElementById('notificationEmail').checked ? 1 : 0,
                notification_push: document.getElementById('notificationPush').checked ? 1 : 0,
                notification_sms: document.getElementById('notificationSms').checked ? 1 : 0
            };

            try {
                const res = await fetch('/api/v1/settings/privacy', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast('নোটিফিকেশন প্রেফারেন্স সফলভাবে সংরক্ষণ হয়েছে।', 'success');
                } else {
                    showToast(data.message || 'সংরক্ষণ ব্যর্থ হয়েছে।', 'error');
                }
            } catch (err) {
                showToast('সার্ভারে যোগাযোগ করা যায়নি।', 'error');
            } finally {
                btn.disabled = false;
            }
        }

        // 6. Direct Block User by ID
        async function handleDirectBlock() {
            const input = document.getElementById('blockUserIdInput');
            const targetId = parseInt(input.value.trim(), 10);
            if (!targetId || targetId <= 0) {
                showToast('সঠিক ইউজার আইডি প্রদান করুন।', 'error');
                return;
            }

            try {
                const res = await fetch(`/api/v1/settings/blocking/${targetId}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message, 'success');
                    input.value = '';
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast(data.message || 'ব্লক করা সম্ভব হয়নি।', 'error');
                }
            } catch (err) {
                showToast('সার্ভারে ত্রুটি ঘটেছে।', 'error');
            }
        }

        // 7. Handle Unblock User
        async function handleUnblock(userId, userName) {
            if (!confirm(`${userName} কে আপনি কি আনব্লক করতে চান?`)) {
                return;
            }

            try {
                const res = await fetch(`/api/v1/settings/blocking/${userId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message, 'success');
                    const row = document.getElementById(`blocked-user-${userId}`);
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            row.remove();
                            const countEl = document.getElementById('blockedUsersCount');
                            if (countEl) {
                                const current = parseInt(countEl.innerText, 10) || 1;
                                countEl.innerText = Math.max(0, current - 1);
                            }
                        }, 250);
                    }
                } else {
                    showToast(data.message || 'আনব্লক করতে ব্যর্থ হয়েছে।', 'error');
                }
            } catch (err) {
                showToast('সার্ভার সংযোগে ত্রুটি ঘটেছে।', 'error');
            }
        }

        // 8. Sound Preview & Audio Player
        let currentAudio = null;
        let currentPlayBtn = null;

        function previewSoundTone(category) {
            let soundId = '';
            let vol = 0.8;
            let iconEl = null;

            if (category === 'call') {
                soundId = document.getElementById('sndCallSelect').value;
                vol = (parseInt(document.getElementById('sndCallVol').value, 10) || 80) / 100;
                iconEl = document.getElementById('playIconCall');
            } else if (category === 'incoming_msg') {
                soundId = document.getElementById('sndMsgInSelect').value;
                vol = (parseInt(document.getElementById('sndMsgInVol').value, 10) || 70) / 100;
                iconEl = document.getElementById('playIconMsgIn');
            } else if (category === 'outgoing_msg') {
                soundId = document.getElementById('sndMsgOutSelect').value;
                vol = (parseInt(document.getElementById('sndMsgOutVol').value, 10) || 60) / 100;
                iconEl = document.getElementById('playIconMsgOut');
            }

            if (currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
                if (currentPlayBtn) currentPlayBtn.innerText = '▶';
                if (currentAudio._cat === category) {
                    currentAudio = null;
                    return;
                }
            }

            const audioPath = `/sounds/messenger/${soundId}.wav`;
            currentAudio = new Audio(audioPath);
            currentAudio._cat = category;
            currentAudio.volume = Math.max(0, Math.min(1, vol));
            currentPlayBtn = iconEl;
            if (iconEl) iconEl.innerText = '⏹';

            currentAudio.play().catch(e => {
                showToast('অডিও প্লে করতে ব্রাউজারে ইউজার ইন্টারঅ্যাকশন প্রয়োজন বা ফাইলটি অনুপস্থিত।', 'error');
                if (iconEl) iconEl.innerText = '▶';
            });

            currentAudio.onended = () => {
                if (iconEl) iconEl.innerText = '▶';
                currentAudio = null;
            };
        }

        // 9. Handle Sounds Form Submission
        async function handleSoundsSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveSounds');
            btn.disabled = true;

            const payload = {
                messenger_sounds_enabled: document.getElementById('sndMasterToggle').checked ? 1 : 0,
                incoming_call_sound_enabled: document.getElementById('sndCallToggle').checked ? 1 : 0,
                incoming_call_sound: document.getElementById('sndCallSelect').value,
                incoming_call_volume: parseInt(document.getElementById('sndCallVol').value, 10) || 80,
                incoming_message_sound_enabled: document.getElementById('sndMsgInToggle').checked ? 1 : 0,
                incoming_message_sound: document.getElementById('sndMsgInSelect').value,
                incoming_message_volume: parseInt(document.getElementById('sndMsgInVol').value, 10) || 70,
                outgoing_message_sound_enabled: document.getElementById('sndMsgOutToggle').checked ? 1 : 0,
                outgoing_message_sound: document.getElementById('sndMsgOutSelect').value,
                outgoing_message_volume: parseInt(document.getElementById('sndMsgOutVol').value, 10) || 60
            };

            try {
                const res = await fetch('/api/v1/settings/messenger-sounds', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message || 'সাউন্ড প্রেফারেন্স সফলভাবে সংরক্ষিত হয়েছে।', 'success');
                } else {
                    showToast(data.message || 'সংরক্ষণ ব্যর্থ হয়েছে।', 'error');
                }
            } catch (err) {
                showToast('সার্ভার সংযোগে ত্রুটি ঘটেছে।', 'error');
            } finally {
                btn.disabled = false;
            }
        }

        // 10. Reset Sound Settings
        async function resetSoundSettings() {
            if (!confirm('আপনি কি সমস্ত সাউন্ড সেটিংস ডিফল্ট মানে ফিরিয়ে আনতে চান?')) {
                return;
            }

            try {
                const res = await fetch('/api/v1/settings/messenger-sounds/reset', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message || 'ডিফল্ট সাউন্ড সেটিংস সফলভাবে পুনরুদ্ধার হয়েছে।', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message || 'পুনরুদ্ধার ব্যর্থ হয়েছে।', 'error');
                }
            } catch (err) {
                showToast('সার্ভার সংযোগে ত্রুটি ঘটেছে।', 'error');
            }
        }

        // 11. Upload Custom Sound Audio
        async function uploadCustomSoundAudio(type, input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];

            if (file.size > 2 * 1024 * 1024) {
                showToast('অডিও ফাইলের আকার সর্বোচ্চ ২ মেগাবাইট হতে পারবে।', 'error');
                input.value = '';
                return;
            }

            const formData = new FormData();
            formData.append('type', type);
            formData.append('audio', file);

            showToast('অডিও আপলোড হচ্ছে...', 'success');

            try {
                const res = await fetch('/api/v1/settings/messenger-sounds/upload', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message || 'কাস্টম অডিও সফলভাবে আপলোড হয়েছে।', 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast(data.message || 'আপলোড ব্যর্থ হয়েছে।', 'error');
                }
            } catch (err) {
                showToast('সার্ভার সংযোগে ত্রুটি ঘটেছে।', 'error');
            }
        }
    </script>
</body>
</html>
