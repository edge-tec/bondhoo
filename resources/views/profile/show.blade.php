<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#18191a" media="(prefers-color-scheme: dark)">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $profile['name'] }} (@ {{ $profile['username'] }}) | Bondhoo — বন্ধু সোশ্যাল নেটওয়ার্ক</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="apple-touch-icon" href="/images/bondhoo-icon-192.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/enterprise-mobile-app.css?v={{ time() }}">
    <link rel="stylesheet" href="/css/enterprise-dashboard-upgrade.css">
    <link rel="stylesheet" href="/css/enterprise-post-composer.css?v={{ time() }}">

    <style>
        :root {
            /* ═══════ PREMIUM COLOR PALETTE ═══════ */
            --fb-bg: #f4f6fa;
            --fb-card: #ffffff;
            --fb-primary: #4f46e5;
            --fb-primary-hover: #4338ca;
            --fb-primary-light: rgba(79, 70, 229, 0.08);
            --fb-text-primary: #111827;
            --fb-text-secondary: #6b7280;
            --fb-border: #e5e7eb;
            --fb-divider: #f0f1f3;
            --fb-hover: #f8f9fb;
            --fb-btn-bg: #f3f4f6;
            --fb-btn-text: #1f2937;
            --fb-green: #10b981;
            --fb-red: #ef4444;
            --fb-blue-light: #eef2ff;
            --fb-badge-blue: #4f46e5;
            --fb-accent-gradient: linear-gradient(135deg, #6366f1, #8b5cf6, #a78bfa);
            --fb-cover-gradient: radial-gradient(circle at 85% 20%, rgba(255, 255, 255, 0.18) 0%, transparent 40%), radial-gradient(circle at 20% 80%, rgba(0, 132, 255, 0.4) 0%, transparent 50%), linear-gradient(135deg, #0284c7 0%, #0084ff 45%, #1d4ed8 100%);

            /* ═══════ REFINED SHADOWS ═══════ */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.08), 0 4px 8px rgba(0, 0, 0, 0.04);
            --shadow-xl: 0 20px 50px rgba(0, 0, 0, 0.12), 0 8px 16px rgba(0, 0, 0, 0.06);
            --shadow-primary: 0 4px 14px rgba(79, 70, 229, 0.25);
            --shadow-card-hover: 0 8px 24px rgba(0, 0, 0, 0.08), 0 2px 6px rgba(0, 0, 0, 0.04);

            /* ═══════ BORDER RADII ═══════ */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-pill: 100px;

            /* ═══════ TRANSITIONS ═══════ */
            --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
            --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
            --transition-fast: 0.15s var(--ease-out-expo);
            --transition-smooth: 0.25s var(--ease-out-expo);
            --transition-slow: 0.4s var(--ease-out-expo);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Hind Siliguri', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            background: var(--fb-bg);
            background-image: radial-gradient(ellipse at 50% 0%, rgba(79, 70, 229, 0.03) 0%, transparent 60%);
            color: var(--fb-text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            line-height: 1.5;
        }

        /* ------------------------------------------------------------- */
        /* TOP NAVIGATION BAR */
        /* ------------------------------------------------------------- */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(16px) saturate(1.6);
            -webkit-backdrop-filter: blur(16px) saturate(1.6);
            background: rgba(255, 255, 255, 0.82);
            border-bottom: 1px solid rgba(229, 231, 235, 0.6);
            height: 60px;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            max-width: 340px;
        }

        .fb-logo {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: transparent !important;
            display: flex;
            align-items: center;
            justify-content: center;
            color: inherit;
            text-decoration: none;
            box-shadow: none !important;
            flex-shrink: 0;
        }

        .search-box {
            position: relative;
            width: 100%;
        }

        .search-input {
            width: 100%;
            height: 40px;
            background: var(--fb-hover);
            border: 1px solid transparent;
            border-radius: var(--radius-pill);
            padding: 0 16px 0 38px;
            font-size: 14px;
            outline: none;
            color: var(--fb-text-primary);
            transition: all var(--transition-smooth);
        }

        .search-input:focus {
            background: var(--fb-card);
            border-color: var(--fb-primary);
            box-shadow: 0 0 0 3px var(--fb-primary-light);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--fb-text-secondary);
            font-size: 16px;
            pointer-events: none;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            height: 100%;
        }

        .nav-tab-btn {
            height: 48px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            color: var(--fb-text-secondary);
            text-decoration: none;
            font-size: 18px;
            transition: all 0.2s;
        }

        .nav-tab-btn:hover {
            background-color: var(--fb-primary-light);
            color: var(--fb-primary);
        }

        .nav-tab-btn.active {
            color: var(--fb-primary);
            border-bottom: 3px solid var(--fb-primary);
            border-radius: 0;
            background: var(--fb-primary-light);
        }

        .nav-tab-btn svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            transition: transform var(--transition-fast), stroke var(--transition-fast);
        }

        .nav-tab-btn:hover svg {
            transform: scale(1.1);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .icon-circle-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--fb-btn-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            color: var(--fb-text-primary);
            font-size: 18px;
            transition: all var(--transition-smooth);
            text-decoration: none;
        }

        .icon-circle-btn svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            display: block;
        }

        .icon-circle-btn:hover {
            background: var(--fb-primary-light);
            color: var(--fb-primary);
            transform: scale(1.06);
        }

        /* ------------------------------------------------------------- */
        /* PROFILE HEADER CONTAINER (COVER, AVATAR, NAME, ACTIONS, TABS) */
        /* ------------------------------------------------------------- */
        .profile-header-wrapper {
            background: var(--fb-card);
            box-shadow: var(--shadow-sm);
            border-bottom: none;
        }

        .profile-header-container {
            max-width: 1380px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Cover Photo */
        .cover-photo-wrapper {
            position: relative;
            width: 100%;
            height: 380px;
            border-radius: 18px;
            overflow: hidden;
            background: linear-gradient(135deg, #c084fc 0%, #f472b6 22%, #818cf8 48%, #60a5fa 72%, #34d399 90%, #a78bfa 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-top: 14px;
        }

        .cover-photo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .cover-photo-edit-btn {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(4px);
            color: var(--fb-text-primary);
            border: none;
            border-radius: var(--radius-sm);
            padding: 8px 14px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            transition: background 0.2s;
        }

        .cover-photo-edit-btn:hover {
            background: #ffffff;
        }

        /* Profile Info Bar (Avatar + Info + Buttons) */
        .profile-main-bar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--fb-divider);
            position: relative;
            flex-wrap: wrap;
            gap: 16px;
        }

        .profile-avatar-and-names {
            display: flex;
            align-items: flex-start;
            gap: 24px;
            margin-top: 0;
            flex: 1;
            min-width: 0;
        }

        .avatar-wrapper {
            position: relative;
            width: 168px;
            height: 168px;
            border-radius: 50%;
            border: 5px solid var(--fb-card);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(79, 70, 229, 0.1);
            background: #ffffff;
            flex-shrink: 0;
            margin-top: -84px;
            z-index: 4;
            transition: box-shadow var(--transition-smooth);
        }

        .avatar-wrapper:hover {
            box-shadow: 0 4px 24px rgba(79, 70, 229, 0.25), 0 0 0 2px rgba(79, 70, 229, 0.2);
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        .avatar-edit-btn {
            position: absolute;
            bottom: 6px;
            right: 6px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--fb-btn-bg);
            border: 2px solid #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            color: var(--fb-text-primary);
            transition: background 0.2s;
        }

        .avatar-edit-btn:hover {
            background: #d8dadf;
        }

        .profile-names-block {
            margin-top: 14px;
            margin-bottom: 12px;
            flex: 1;
            min-width: 0;
        }

        .profile-fullname {
            font-size: 28px;
            font-weight: 700;
            color: var(--fb-text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
            line-height: 1.2;
        }

        .verified-badge {
            color: var(--fb-badge-blue);
            display: inline-flex;
            align-items: center;
        }

        .profile-username-sub {
            font-size: 15px;
            color: var(--fb-text-secondary);
            margin-top: 2px;
            font-weight: 500;
        }

        .profile-friends-count-sub {
            font-size: 14px;
            color: var(--fb-text-secondary);
            font-weight: 600;
            margin-top: 4px;
        }

        .profile-friends-count-sub a {
            color: var(--fb-text-secondary);
            text-decoration: none;
        }

        .profile-friends-count-sub a:hover {
            text-decoration: underline;
        }

        /* Action Buttons */
        .profile-actions-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            align-self: flex-start;
        }

        .profile-actions-row {
            display: contents;
        }

        .fb-btn {
            height: 38px;
            padding: 0 18px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            transition: all var(--transition-smooth);
            text-decoration: none;
            letter-spacing: -0.01em;
        }

        .fb-btn-primary {
            background: var(--fb-accent-gradient);
            color: #ffffff;
            box-shadow: var(--shadow-primary);
        }

        .fb-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
        }

        .fb-btn-primary:active {
            transform: translateY(0);
            box-shadow: var(--shadow-primary);
        }

        .fb-btn-secondary {
            background: var(--fb-btn-bg);
            color: var(--fb-btn-text);
            border: 1px solid var(--fb-border);
        }

        .fb-btn-secondary:hover {
            background: var(--fb-hover);
            border-color: var(--fb-primary);
            color: var(--fb-primary);
        }

        .fb-btn-lock {
            background: var(--fb-primary-light);
            color: var(--fb-primary);
        }

        .fb-btn-lock:hover {
            background: rgba(79, 70, 229, 0.12);
        }

        .fb-btn-locked-active {
            background: #fde8e8;
            color: var(--fb-red);
        }

        .fb-btn-locked-active:hover {
            background: #fcd0d0;
        }

        /* Profile Tabs */
        .profile-nav-tabs {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
            margin-top: 4px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .profile-nav-tabs::-webkit-scrollbar {
            display: none;
        }

        .profile-nav-tab {
            padding: 12px 18px;
            font-size: 15px;
            font-weight: 600;
            color: var(--fb-text-secondary);
            border-bottom: 3px solid transparent;
            cursor: pointer;
            text-decoration: none;
            border-radius: var(--radius-sm) var(--radius-sm) 0 0;
            transition: all var(--transition-smooth);
            white-space: nowrap;
            position: relative;
        }

        .profile-nav-tab:hover {
            background: var(--fb-primary-light);
            color: var(--fb-primary);
        }

        .profile-nav-tab.active {
            color: var(--fb-primary);
            border-bottom-color: var(--fb-primary);
            background: var(--fb-primary-light);
            font-weight: 700;
        }

        /* Profile Nav More Dropdown */
        .profile-nav-more-item {
            position: relative;
        }

        .profile-nav-more-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: transparent;
            border: none;
            font-family: inherit;
        }

        .profile-nav-more-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 6px;
            background: var(--fb-card);
            border: 1px solid var(--fb-border);
            border-radius: var(--radius-md);
            box-shadow: 0 12px 28px 0 rgba(0, 0, 0, 0.2), 0 2px 4px 0 rgba(0, 0, 0, 0.1);
            min-width: 250px;
            z-index: 1000;
            display: none;
            flex-direction: column;
            padding: 8px;
            animation: navDropdownFade 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .profile-nav-more-dropdown.show {
            display: flex;
        }

        .profile-action-more-wrap {
            position: relative;
            display: inline-block;
        }

        .profile-action-more-wrap .profile-nav-more-dropdown {
            right: 0;
            left: auto;
            min-width: 280px;
        }

        .sheet-drag-handle {
            display: none;
        }

        .sheet-mobile-header {
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: 2px 4px 10px;
            margin-bottom: 6px;
            border-bottom: 1px solid var(--fb-border);
        }

        @keyframes navDropdownFade {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .more-tab-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            color: var(--fb-text-primary);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.15s ease;
            cursor: pointer;
        }

        .more-tab-link:hover {
            background: var(--fb-hover);
        }

        .more-tab-link.active {
            background: #e7f3ff;
            color: var(--fb-primary);
        }

        .more-tab-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .more-dropdown-divider {
            height: 1px;
            background: var(--fb-divider);
            margin: 6px 0;
        }

        /* Nav tabs fully scrollable across all viewports */
        .profile-nav-tabs {
            -webkit-overflow-scrolling: touch;
        }

        /* Intro Direct Connect & Social Links */
        .intro-connect-section {
            margin-bottom: 12px;
            padding-top: 8px;
            border-top: 1px solid var(--fb-divider);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .connect-action-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            text-decoration: none;
            color: var(--fb-text-primary);
            background: var(--fb-hover);
            border: 1px solid var(--fb-divider);
            transition: all 0.2s ease;
        }

        .connect-action-pill:hover {
            background: #e7f3ff;
            border-color: #bad3fb;
            color: var(--fb-primary);
        }

        .connect-action-pill.wa-pill {
            border-left: 3px solid #25d366;
        }

        .connect-action-pill.wa-pill:hover {
            background: #e8f9ef;
            border-color: #25d366;
            color: #128c7e;
        }

        .connect-action-pill.messenger-pill {
            border-left: 3px solid #0084ff;
        }

        .connect-action-pill.messenger-pill:hover {
            background: #ebf5ff;
            border-color: #0084ff;
            color: #0084ff;
        }

        .connect-action-pill.telegram-pill {
            border-left: 3px solid #229ed9;
        }

        .connect-action-pill.telegram-pill:hover {
            background: #ebf7fd;
            border-color: #229ed9;
            color: #0088cc;
        }

        .connect-action-pill.signal-pill {
            border-left: 3px solid #3a76f0;
        }

        .connect-action-pill.portfolio-pill {
            border-left: 3px solid #8b5cf6;
        }

        .connect-action-pill.portfolio-pill:hover {
            background: #f5f3ff;
            border-color: #8b5cf6;
            color: #6d28d9;
        }

        .connect-pill-icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .social-brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            background: var(--fb-hover);
            color: var(--fb-text-primary);
            border: 1px solid var(--fb-divider);
            transition: all 0.2s ease;
        }

        .social-brand-pill:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .social-brand-pill.social-facebook {
            background: #e7f3ff;
            color: var(--fb-primary);
            border-color: #c7dffb;
        }

        .social-brand-pill.social-instagram {
            background: #fdf2f8;
            color: #db2777;
            border-color: #fbcfe8;
        }

        .social-brand-pill.social-youtube {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .social-brand-pill.social-linkedin {
            background: #eff6ff;
            color: #0284c7;
            border-color: #bae6fd;
        }

        .social-brand-pill.social-twitter, .social-brand-pill.social-x {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .social-brand-pill.social-github {
            background: #f8fafc;
            color: #1e293b;
            border-color: #cbd5e1;
        }

        /* Profile Lock Notice Banner */
        .profile-lock-banner {
            margin: 16px auto;
            max-width: 1050px;
            padding: 16px 20px;
            background: #ffffff;
            border: 1px solid #c7d8f5;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .lock-shield-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #e7f3ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--fb-primary);
            flex-shrink: 0;
            font-size: 24px;
        }

        .lock-banner-text h4 {
            font-size: 17px;
            font-weight: 700;
            color: var(--fb-text-primary);
        }

        .lock-banner-text p {
            font-size: 14px;
            color: var(--fb-text-secondary);
            margin-top: 2px;
        }

        /* ------------------------------------------------------------- */
        /* SLIM VERTICAL DOCK (LEFT APP RAIL) */
        /* ------------------------------------------------------------- */
        .app-slim-dock {
            position: fixed;
            left: 0;
            top: 60px;
            bottom: 0;
            width: 64px;
            background: var(--fb-card);
            border-right: 1px solid var(--fb-border);
            z-index: 95;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0 20px 0;
            box-shadow: 1px 0 4px rgba(0,0,0,0.03);
            transition: all var(--transition-smooth);
        }

        .dock-top-group, .dock-bottom-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            width: 100%;
        }

        .dock-item {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--fb-text-secondary);
            background: transparent;
            text-decoration: none;
            position: relative;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .dock-item:hover {
            background: var(--fb-hover);
            color: var(--fb-primary);
            transform: translateY(-2px);
        }

        .dock-item.active {
            background: var(--fb-primary-light);
            color: var(--fb-primary);
        }

        .dock-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #0084ff;
            color: white;
            font-size: 10px;
            font-weight: 800;
            border-radius: 10px;
            padding: 1px 5px;
            line-height: 1.2;
            border: 2px solid var(--fb-card);
        }

        .dock-badge-red {
            background: #ef4444;
        }

        @media (min-width: 1025px) {
            body.has-slim-dock {
                padding-left: 64px;
            }
        }

        @media (max-width: 1024px) {
            .app-slim-dock {
                display: none !important;
            }
            body.has-slim-dock {
                padding-left: 0 !important;
            }
        }

        /* ------------------------------------------------------------- */
        /* MAIN BODY GRID (3-COLUMN DESKTOP SOCIAL DASHBOARD) */
        /* ------------------------------------------------------------- */
        .profile-content-container {
            max-width: 1380px;
            margin: 16px auto 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 290px minmax(0, 1fr) 320px;
            gap: 20px;
            align-items: start;
        }

        @media (max-width: 1259px) {
            .profile-content-container {
                grid-template-columns: 290px minmax(0, 1fr);
            }
            .profile-telemetry-col {
                grid-column: span 2;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }
        }

        @media (max-width: 900px) {
            .profile-content-container {
                grid-template-columns: 1fr;
                padding: 0 12px;
            }
            .profile-telemetry-col {
                grid-column: span 1;
                display: flex;
                flex-direction: column;
            }
        }

        /* Giant Profile Completion Styling */
        .completion-giant-pct {
            font-size: 38px;
            font-weight: 900;
            color: #0084ff;
            line-height: 1;
            letter-spacing: -1px;
        }

        /* Live Analytics Card & Dots Matrix */
        .live-pulse-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.35);
            animation: livePulseAnim 1.8s infinite;
        }

        @keyframes livePulseAnim {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.6; }
        }

        .dot-circle {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #3b82f6;
            animation: dotFadeAnim 2s infinite ease-in-out;
        }
        .dot-circle:nth-child(2) { animation-delay: 0.3s; background: #06b6d4; }
        .dot-circle:nth-child(3) { animation-delay: 0.6s; background: #10b981; }
        .dot-circle:nth-child(4) { animation-delay: 0.9s; background: #8b5cf6; }

        @keyframes dotFadeAnim {
            0%, 100% { opacity: 0.4; transform: scale(0.9); }
            50% { opacity: 1; transform: scale(1.25); }
        }

        /* Soundwave Voice/Live Visualizer */
        .wave-bar {
            width: 3px;
            background: #0084ff;
            border-radius: 3px;
            animation: soundWaveAnim 1.2s ease-in-out infinite alternate;
        }
        .wave-bar:nth-child(1) { height: 8px; animation-delay: 0.1s; }
        .wave-bar:nth-child(2) { height: 16px; animation-delay: 0.25s; }
        .wave-bar:nth-child(3) { height: 22px; animation-delay: 0.05s; }
        .wave-bar:nth-child(4) { height: 12px; animation-delay: 0.35s; }
        .wave-bar:nth-child(5) { height: 18px; animation-delay: 0.15s; }
        .wave-bar:nth-child(6) { height: 24px; animation-delay: 0.4s; }
        .wave-bar:nth-child(7) { height: 14px; animation-delay: 0.2s; }
        .wave-bar:nth-child(8) { height: 20px; animation-delay: 0.3s; }
        .wave-bar:nth-child(9) { height: 10px; animation-delay: 0.1s; }

        @keyframes soundWaveAnim {
            0% { transform: scaleY(0.4); opacity: 0.5; }
            100% { transform: scaleY(1.2); opacity: 1; }
        }

        .live-chat-tab-btn {
            flex: 1;
            text-align: center;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 700;
            color: var(--fb-text-secondary);
            border: none;
            background: transparent;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .live-chat-tab-btn.active {
            color: #0084ff;
            border-bottom-color: #0084ff;
        }

        /* Card common styling */
        .fb-card {
            background: var(--fb-card);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 18px;
            margin-bottom: 16px;
            border: 1px solid var(--fb-divider);
            transition: box-shadow var(--transition-smooth), transform var(--transition-smooth);
        }

        .fb-card:hover {
            box-shadow: var(--shadow-card-hover);
        }

        .card-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .card-header-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--fb-text-primary);
        }

        .card-header-link {
            font-size: 14px;
            color: var(--fb-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .card-header-link:hover {
            text-decoration: underline;
        }

        /* Intro Card */
        .intro-bio {
            text-align: center;
            font-size: 15px;
            color: var(--fb-text-primary);
            line-height: 1.4;
            padding: 4px 0 12px 0;
            border-bottom: 1px solid var(--fb-divider);
            margin-bottom: 12px;
        }

        .intro-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: var(--fb-text-primary);
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .intro-item-icon {
            width: 20px;
            height: 20px;
            color: var(--fb-text-secondary);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .intro-item a {
            color: var(--fb-primary);
            text-decoration: none;
        }

        .intro-item a:hover {
            text-decoration: underline;
        }

        /* Photo & Friend Grids (3x3) */
        .preview-grid-3x3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            border-radius: var(--radius-sm);
            overflow: hidden;
        }

        .preview-photo-item {
            aspect-ratio: 1 / 1;
            overflow: hidden;
            background: #eee;
            border-radius: var(--radius-sm);
        }

        .preview-photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.2s;
        }

        .preview-photo-item:hover img {
            transform: scale(1.05);
        }

        .friend-preview-card {
            text-decoration: none;
            color: var(--fb-text-primary);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .friend-preview-card .friend-photo {
            aspect-ratio: 1 / 1;
            border-radius: var(--radius-sm);
            object-fit: cover;
            width: 100%;
            background: #eee;
        }

        .friend-preview-name {
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Post Composer */
        .composer-top {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--fb-divider);
        }

        .composer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .composer-fake-input {
            flex: 1;
            height: 40px;
            background: var(--fb-bg);
            border-radius: 50px;
            padding: 0 16px;
            display: flex;
            align-items: center;
            color: var(--fb-text-secondary);
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .composer-fake-input:hover {
            background: #e4e6eb;
        }

        .composer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding-top: 10px;
        }

        .composer-btn-action {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            color: var(--fb-text-secondary);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: transparent;
            border: none;
            transition: background 0.2s;
        }

        .composer-btn-action:hover {
            background: var(--fb-hover);
        }

        /* Timeline Feed Posts */
        .post-card {
            background: var(--fb-card);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            margin-bottom: 16px;
            border: 1px solid var(--fb-divider);
            transition: box-shadow var(--transition-smooth), transform var(--transition-smooth);
        }

        .post-card:hover {
            box-shadow: var(--shadow-card-hover);
        }

        .post-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px 8px 16px;
        }

        .post-author-block {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .post-author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .post-author-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--fb-text-primary);
            text-decoration: none;
        }

        .post-author-name:hover {
            text-decoration: underline;
        }

        .post-meta {
            font-size: 12px;
            color: var(--fb-text-secondary);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .post-content-body {
            padding: 4px 16px 12px 16px;
            font-size: 15px;
            line-height: 1.5;
            color: var(--fb-text-primary);
            white-space: pre-line;
        }

        .post-media-container {
            width: 100%;
            background: #000;
            overflow: hidden;
        }

        .post-media-container img {
            width: 100%;
            max-height: 520px;
            object-fit: contain;
            display: block;
        }

        .post-stats-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            border-bottom: 1px solid var(--fb-divider);
            font-size: 14px;
            color: var(--fb-text-secondary);
        }

        .reactions-count-badge {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .post-actions-row {
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 4px 16px;
        }

        .post-action-btn {
            flex: 1;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--fb-text-secondary);
            border: none;
            background: transparent;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background 0.2s;
        }

        .post-action-btn:hover {
            background: var(--fb-primary-light);
            color: var(--fb-primary);
        }

        .post-action-btn.reacted {
            color: var(--fb-primary);
            font-weight: 700;
        }

        /* Locked Profile Big Notice */
        .locked-timeline-placeholder {
            background: #ffffff;
            border-radius: var(--radius-md);
            padding: 48px 24px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--fb-divider);
        }

        .locked-shield-big {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #e7f3ff;
            color: var(--fb-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 16px auto;
        }

        /* ------------------------------------------------------------- */
        /* MODAL STYLES (EDIT PROFILE, PHOTO UPLOAD, POST CREATION) */
        /* ------------------------------------------------------------- */
        .fb-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 100000 !important;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .fb-modal-overlay.active {
            display: flex;
        }

        .fb-modal-card {
            background: var(--fb-card);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 580px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            animation: modalPop 0.3s var(--ease-out-expo);
            max-height: 90vh;
            max-height: 92dvh;
            display: flex;
            flex-direction: column;
        }

        .fb-modal-card > form {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }

        @keyframes modalPop {
            from { transform: scale(0.92) translateY(12px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--fb-divider);
            flex-shrink: 0;
        }

        .modal-title {
            font-size: 19px;
            font-weight: 700;
            color: var(--fb-text-primary);
        }

        .modal-close-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--fb-btn-bg);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--fb-text-secondary);
        }

        .modal-close-btn:hover {
            background: #d8dadf;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            flex: 1;
            min-height: 0;
        }

        .modal-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--fb-divider);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            background: var(--fb-hover);
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--fb-text-primary);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--fb-border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            color: var(--fb-text-primary);
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--fb-primary);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .modal-subtabs {
            display: flex;
            gap: 6px;
            padding: 8px 16px;
            background: #f0f2f5;
            border-bottom: 1px solid var(--fb-divider);
            overflow-x: auto;
        }

        .modal-subtab {
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 600;
            color: var(--fb-text-secondary);
            border-radius: var(--radius-pill);
            cursor: pointer;
            border: none;
            background: transparent;
            white-space: nowrap;
            transition: all 0.2s;
        }

        .modal-subtab:hover {
            background: #e4e6eb;
            color: var(--fb-text-primary);
        }

        .modal-subtab.active {
            background: var(--fb-primary);
            color: #fff;
        }

        .edit-tab-panel {
            display: none;
        }

        .edit-tab-panel.active {
            display: block;
        }

        /* Responsive Breakpoints */
        @media (max-width: 900px) {
            .profile-content-container {
                grid-template-columns: 1fr;
            }
            .cover-photo-wrapper {
                height: 240px;
            }
            .avatar-wrapper {
                width: 136px;
                height: 136px;
                margin-top: -68px;
                margin-left: auto;
                margin-right: auto;
            }
            .profile-avatar-and-names {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 8px;
                width: 100%;
            }
            .profile-names-block {
                margin-top: 4px;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                width: 100%;
            }
            .profile-names-block > div {
                justify-content: center;
            }
            .profile-fullname {
                font-size: 24px;
                justify-content: center;
            }
            .profile-main-bar {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .profile-actions-bar {
                width: 100%;
                justify-content: center;
                margin-top: 12px;
                margin-bottom: 12px;
            }
        }

        /* Avatar Frames */
        .avatar-frame-overlay {
            position: absolute;
            top: -6px;
            left: -6px;
            right: -6px;
            bottom: -6px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 5;
        }
        .frame-badge-gold {
            border: 4px solid #f59e0b;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.6);
        }
        .frame-badge-bd_flag {
            border: 4px solid #059669;
            box-shadow: 0 0 12px rgba(220, 38, 38, 0.7);
        }
        .frame-badge-creator {
            border: 4px solid #8b5cf6;
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.6);
        }
        .frame-badge-tech {
            border: 4px solid #0ea5e9;
            box-shadow: 0 0 15px rgba(14, 165, 233, 0.6);
        }

        /* Highlights */
        .story-highlights-container {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 16px;
            background: var(--fb-card);
            border-top: 1px solid var(--fb-divider);
            overflow-x: auto;
        }
        .highlight-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            flex-shrink: 0;
            text-decoration: none;
        }
        .highlight-circle {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            border: 2.5px solid transparent;
            background: linear-gradient(var(--fb-card), var(--fb-card)) padding-box, var(--fb-accent-gradient) border-box;
            padding: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform var(--transition-smooth);
        }
        .highlight-item:hover .highlight-circle {
            transform: scale(1.05);
        }
        .highlight-circle img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .highlight-add-circle {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            border: 2px dashed var(--fb-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: var(--fb-primary);
            background: var(--fb-primary-light);
            transition: all var(--transition-smooth);
        }
        .highlight-item:hover .highlight-add-circle {
            background: rgba(79, 70, 229, 0.12);
            transform: scale(1.08);
            border-style: solid;
        }

        /* Featured Collection Styles */
        .featured-cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 6px;
        }
        .featured-card-item {
            position: relative;
            aspect-ratio: 3 / 4;
            border-radius: 8px;
            overflow: hidden;
            background: #1c1e21;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease;
        }
        .featured-card-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }
        .featured-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.35s ease;
        }
        .featured-card-item:hover .featured-card-img {
            transform: scale(1.05);
        }
        .featured-card-gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.2) 40%, rgba(0,0,0,0.85) 100%);
            pointer-events: none;
        }
        .featured-card-del-btn {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(0,0,0,0.65);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            opacity: 0;
            transition: opacity 0.2s, background 0.2s, transform 0.15s;
            z-index: 5;
        }
        .featured-card-item:hover .featured-card-del-btn {
            opacity: 1;
        }
        .featured-card-del-btn:hover {
            background: #e41e3f;
            transform: scale(1.1);
        }
        .featured-card-content {
            position: absolute;
            bottom: 8px;
            left: 8px;
            right: 8px;
            z-index: 2;
            pointer-events: none;
        }
        .featured-card-title {
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            line-height: 1.25;
            text-shadow: 0 1px 3px rgba(0,0,0,0.85);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .featured-card-count {
            color: rgba(255,255,255,0.85);
            font-size: 11px;
            font-weight: 500;
            margin-top: 3px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.6);
        }
        .featured-empty-box {
            text-align: center;
            padding: 20px 14px;
            background: var(--fb-card-bg, rgba(0,0,0,0.02));
            border-radius: 8px;
            border: 1px dashed var(--fb-divider);
            margin-top: 6px;
        }
        .featured-empty-icon {
            font-size: 32px;
            margin-bottom: 6px;
        }
        .featured-empty-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--fb-text-primary);
            margin-bottom: 4px;
        }
        .featured-empty-text {
            font-size: 12px;
            color: var(--fb-text-secondary);
            line-height: 1.45;
        }
        .selectable-cover-thumb {
            width: 52px;
            height: 52px;
            border-radius: 6px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .selectable-cover-thumb:hover {
            opacity: 0.9;
            transform: scale(1.04);
        }
        .selectable-cover-thumb.active {
            border-color: var(--fb-primary) !important;
            box-shadow: 0 0 0 2px var(--fb-primary);
        }

        /* Story Highlight Viewer Modal */
        .story-highlight-player-card {
            max-width: 440px;
            width: 100%;
            height: 720px;
            max-height: 92vh;
            padding: 0;
            background: #000;
            border: none;
            border-radius: var(--radius-lg);
            overflow: hidden;
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.8);
            display: flex;
            flex-direction: column;
        }
        .highlight-progress-bars {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            display: flex;
            gap: 4px;
            z-index: 30;
        }
        .highlight-progress-seg {
            flex: 1;
            height: 3px;
            background: rgba(255, 255, 255, 0.35);
            border-radius: 999px;
            overflow: hidden;
        }
        .highlight-progress-fill {
            height: 100%;
            width: 0%;
            background: #ffffff;
            border-radius: 999px;
            transition: width 0.1s linear;
        }
        .highlight-header-overlay {
            position: absolute;
            top: 24px;
            left: 12px;
            right: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 30;
            color: #fff;
        }
        .highlight-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .highlight-header-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 2px solid #fff;
            object-fit: cover;
        }
        .highlight-header-info {
            display: flex;
            flex-direction: column;
            text-shadow: 0 1px 3px rgba(0,0,0,0.8);
        }
        .highlight-header-title {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.2;
        }
        .highlight-header-sub {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.85);
        }
        .highlight-ctrl-btn {
            background: rgba(0, 0, 0, 0.4);
            border: none;
            color: #fff;
            border-radius: 50%;
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.2s;
        }
        .highlight-ctrl-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        .highlight-media-stage {
            width: 100%;
            height: 100%;
            position: relative;
            background: #0a0a0a;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .highlight-media-stage img,
        .highlight-media-stage video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .highlight-tap-prev,
        .highlight-tap-next {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 50%;
            z-index: 20;
            cursor: pointer;
        }
        .highlight-tap-prev { left: 0; }
        .highlight-tap-next { right: 0; }
        .highlight-nav-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 25;
            background: rgba(0, 0, 0, 0.5);
            border: none;
            color: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            opacity: 0;
            transition: opacity 0.2s, background 0.2s;
        }
        .story-highlight-player-card:hover .highlight-nav-arrow {
            opacity: 1;
        }
        .highlight-nav-arrow:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .highlight-nav-arrow.prev { left: 12px; }
        .highlight-nav-arrow.next { right: 12px; }

        /* Filter Chips */
        .filter-chips-row {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .filter-chip {
            padding: 7px 16px;
            border-radius: var(--radius-pill);
            font-size: 13px;
            font-weight: 600;
            background: var(--fb-btn-bg);
            color: var(--fb-text-primary);
            border: 1px solid transparent;
            cursor: pointer;
            white-space: nowrap;
            transition: all var(--transition-smooth);
        }
        .filter-chip:hover {
            background: var(--fb-hover);
            border-color: var(--fb-border);
        }
        .filter-chip.active {
            background: var(--fb-primary-light);
            color: var(--fb-primary);
            border-color: var(--fb-primary);
            font-weight: 700;
        }

        /* Albums & Reels Grids */
        .albums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
        }
        .album-card {
            border: 1px solid var(--fb-divider);
            border-radius: var(--radius-md);
            overflow: hidden;
            background: var(--fb-card);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s;
        }
        .album-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .album-cover-img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            background: #f0f2f5;
        }
        .reels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }
        .reel-card {
            position: relative;
            aspect-ratio: 9/16;
            border-radius: var(--radius-md);
            overflow: hidden;
            background: #000;
            cursor: pointer;
        }
        .reel-card video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .reel-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px 8px;
            background: linear-gradient(transparent, rgba(0,0,0,0.85));
            color: #fff;
            font-size: 13px;
        }

        /* Professional Mode Switch & Cards */
        .pro-dashboard-card {
            background: var(--fb-accent-gradient);
            border-radius: var(--radius-lg);
            color: white;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-primary);
        }
        .toggle-switch-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 52px;
            height: 28px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 28px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #10b981;
        }
        /* Facebook About Tab Layout */
        .about-card-container {
            display: flex;
            background: var(--fb-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--fb-divider);
            overflow: hidden;
            min-height: 520px;
        }
        .about-sidebar {
            width: 250px;
            border-right: 1px solid var(--fb-divider);
            padding: 16px 8px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex-shrink: 0;
            background: var(--fb-card);
        }
        .about-sidebar-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--fb-text-primary);
            padding: 4px 12px 14px;
            border-bottom: 1px solid var(--fb-divider);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .about-subtab-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--fb-text-secondary);
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            text-align: left;
            width: 100%;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }
        .about-subtab-btn:hover {
            background: var(--fb-hover);
            color: var(--fb-text-primary);
        }
        .about-subtab-btn.active {
            background: var(--fb-primary-light, #e7f3ff);
            color: var(--fb-primary, #1877f2);
            border-color: rgba(24, 119, 242, 0.2);
            font-weight: 700;
        }
        .about-subtab-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--fb-hover, rgba(0,0,0,0.04));
            color: var(--fb-text-secondary);
            flex-shrink: 0;
            transition: all 0.2s ease;
        }
        .about-subtab-btn:hover .about-subtab-icon {
            color: var(--fb-text-primary);
            background: rgba(0,0,0,0.08);
        }
        .about-subtab-btn.active .about-subtab-icon {
            color: var(--fb-primary, #1877f2);
            background: rgba(24, 119, 242, 0.15);
        }
        .about-subtab-svg {
            display: block;
            width: 18px;
            height: 18px;
            stroke-width: 2.2;
            transition: transform 0.2s ease;
        }
        .about-subtab-btn:hover .about-subtab-svg {
            transform: scale(1.08);
        }
        .about-content-panel {
            flex: 1;
            padding: 24px 28px;
            overflow-y: auto;
        }
        .about-content-panel > div {
            animation: aboutPanelFade 0.25s ease-out;
        }
        @keyframes aboutPanelFade {
            from {
                opacity: 0;
                transform: translateY(4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .about-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--fb-divider);
        }
        .about-section-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--fb-text-primary);
        }
        .about-info-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .about-info-item:last-child {
            border-bottom: none;
        }
        .about-info-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--fb-hover);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .about-info-details {
            flex: 1;
            min-width: 0;
        }
        .about-info-main {
            font-size: 15px;
            font-weight: 600;
            color: var(--fb-text-primary);
            line-height: 1.4;
            word-break: break-word;
        }
        .about-info-sub {
            font-size: 13px;
            color: var(--fb-text-secondary);
            margin-top: 3px;
        }
        .about-info-edit-btn {
            background: none;
            border: none;
            color: var(--fb-text-secondary);
            cursor: pointer;
            font-size: 14px;
            padding: 6px 10px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
            font-family: inherit;
            font-weight: 600;
        }
        .about-info-edit-btn:hover {
            background: var(--fb-hover);
            color: var(--fb-primary);
        }
        .about-add-prompt {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--fb-primary);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            padding: 8px 0;
            background: none;
            border: none;
            font-family: inherit;
        }
        .about-add-prompt:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .about-card-container {
                flex-direction: column;
                overflow: visible;
                min-height: auto;
            }
            .about-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--fb-divider);
                display: flex;
                flex-direction: row;
                flex-wrap: wrap; /* CRITICAL: Enables all items to wrap naturally */
                gap: 8px;
                padding: 12px 14px;
                background: var(--fb-card);
                overflow: visible;
                white-space: normal;
            }
            .about-sidebar-title {
                display: flex;
                align-items: center;
                gap: 6px;
                width: 100%;
                font-size: 13px;
                font-weight: 700;
                color: var(--fb-text-secondary);
                padding: 0 0 6px 2px;
                border-bottom: 1px dashed var(--fb-divider);
                margin-bottom: 2px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .about-subtab-btn {
                width: auto;
                flex: 0 1 auto;
                max-width: 100%;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 14px;
                border-radius: 9999px; /* Modern pill badge */
                font-size: 13px;
                font-weight: 600;
                line-height: 1.25;
                color: var(--fb-text-secondary);
                background: var(--fb-bg, #f0f2f5);
                border: 1px solid var(--fb-divider, #e4e6eb);
                white-space: nowrap;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            }
            .about-subtab-btn:hover {
                background: var(--fb-hover);
                color: var(--fb-text-primary);
                border-color: rgba(0, 0, 0, 0.15);
            }
            .about-subtab-btn.active {
                background: #e7f3ff;
                border-color: var(--fb-primary, #1877f2);
                color: var(--fb-primary, #1877f2);
                font-weight: 700;
                box-shadow: 0 2px 6px rgba(24, 119, 242, 0.18);
            }
            .about-subtab-btn .about-subtab-icon {
                width: 20px;
                height: 20px;
                background: transparent;
                border-radius: 0;
            }
            .about-subtab-btn .about-subtab-svg {
                width: 16px;
                height: 16px;
            }
            .about-content-panel {
                padding: 16px 14px;
            }
        }

        @media (max-width: 480px) {
            .about-sidebar {
                padding: 10px 8px;
                gap: 6px;
            }
            .about-subtab-btn {
                padding: 7px 11px;
                font-size: 12.5px;
                gap: 6px;
                border-radius: 18px;
            }
            .about-subtab-btn .about-subtab-svg {
                width: 15px;
                height: 15px;
            }
        }

        /* Friends Tab Enhanced Styles */
        .friends-tab-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--fb-divider);
        }
        .friends-search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--fb-hover);
            border-radius: 20px;
            padding: 7px 14px;
            border: 1px solid var(--fb-divider);
            min-width: 250px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .friends-search-box:focus-within {
            border-color: var(--fb-primary);
            box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.2);
            background: #fff;
        }
        .friends-search-input {
            background: transparent;
            border: none;
            outline: none;
            font-size: 13px;
            color: var(--fb-text-primary);
            width: 100%;
            font-family: inherit;
        }
        .friends-grid-enhanced {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 12px;
        }
        .friend-card-enhanced {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            border: 1px solid var(--fb-divider);
            border-radius: var(--radius-md);
            background: var(--fb-card);
            transition: all var(--transition-smooth);
        }
        .friend-card-enhanced:hover {
            box-shadow: var(--shadow-card-hover);
            border-color: rgba(79, 70, 229, 0.15);
            transform: translateY(-1px);
        }
        .friend-avatar-enhanced {
            width: 72px;
            height: 72px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            flex-shrink: 0;
        }
        .friend-info-enhanced {
            flex: 1;
            min-width: 0;
        }
        .friend-name-link {
            font-size: 15px;
            font-weight: 700;
            color: var(--fb-text-primary);
            text-decoration: none;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .friend-name-link:hover {
            text-decoration: underline;
            color: var(--fb-primary);
        }
        .friend-meta-text {
            font-size: 12px;
            color: var(--fb-text-secondary);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .friend-action-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        /* Timeline Manage Bar & Filter Controls */
        .timeline-manage-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            margin-bottom: 12px;
            border-radius: 8px;
        }
        .timeline-manage-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--fb-text-primary);
        }
        .timeline-manage-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .timeline-ctrl-btn {
            background: var(--fb-hover);
            border: 1px solid var(--fb-border);
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 600;
            color: var(--fb-text-primary);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .timeline-ctrl-btn:hover {
            background: var(--fb-border);
        }
        .timeline-ctrl-btn.active {
            background: #e7f3ff;
            color: var(--fb-primary);
            border-color: #bfdbfe;
        }
        .view-switch-group {
            display: inline-flex;
            background: var(--fb-hover);
            border-radius: 6px;
            padding: 2px;
            border: 1px solid var(--fb-border);
        }
        .view-switch-btn {
            background: transparent;
            border: none;
            padding: 4px 10px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            color: var(--fb-text-secondary);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .view-switch-btn:hover {
            color: var(--fb-text-primary);
        }
        .view-switch-btn.active {
            background: var(--fb-card);
            color: var(--fb-primary);
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        /* Filter Drawer */
        .timeline-filter-drawer {
            padding: 14px 16px;
            margin-bottom: 12px;
            background: var(--fb-card);
            border: 1px solid var(--fb-border);
            border-radius: 8px;
            animation: filterSlideDown 0.2s ease-out;
        }
        @keyframes filterSlideDown {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .filter-drawer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 10px;
        }
        .filter-drawer-field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--fb-text-secondary);
            margin-bottom: 4px;
        }
        .filter-drawer-field select {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid var(--fb-border);
            border-radius: 6px;
            font-size: 13px;
            background: var(--fb-bg);
            color: var(--fb-text-primary);
        }

        /* Grid View Mode for Posts */
        #timelinePostsStream.grid-mode {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        @media (max-width: 640px) {
            #timelinePostsStream.grid-mode {
                grid-template-columns: 1fr;
            }
        }
        #timelinePostsStream.grid-mode .post-card {
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        #timelinePostsStream.grid-mode .post-card .post-content-body {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 14px;
        }
        #timelinePostsStream.grid-mode .post-card .post-media-container img {
            max-height: 160px;
            object-fit: cover;
            width: 100%;
        }

        /* Pinned Post Banner */
        .pinned-post-banner {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            color: var(--fb-primary);
            background: #eff6ff;
            border-bottom: 1px solid #dbeafe;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        /* Life Event Post Banner */
        .life-event-badge-banner {
            padding: 16px;
            margin: -12px -16px 12px -16px;
            background: linear-gradient(135deg, #fdf2f8 0%, #ede9fe 100%);
            border-bottom: 1px solid #e9d5ff;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            text-align: center;
        }
        .life-event-icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 8px;
        }
        .life-event-badge-title {
            font-size: 16px;
            font-weight: 700;
            color: #4c1d95;
            margin-bottom: 2px;
        }
        .life-event-location {
            font-size: 13px;
            color: #6b21a8;
            font-weight: 500;
        }

        /* Post 3-Dot Dropdown */
        .post-header-action-wrap {
            position: relative;
        }
        .post-options-dropdown {
            position: absolute;
            top: 36px;
            right: 0;
            background: var(--fb-card);
            border: 1px solid var(--fb-border);
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            min-width: 200px;
            z-index: 100;
            padding: 6px 0;
            display: none;
        }
        .post-options-dropdown.show {
            display: block;
        }
        .post-option-item {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 500;
            color: var(--fb-text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s;
        }
        .post-option-item:hover {
            background: var(--fb-hover);
            color: var(--fb-primary);
        }
        .post-option-item.danger-item:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Life Event Modal Category Chips */
        .life-event-cat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }
        .life-event-cat-card {
            border: 1.5px solid var(--fb-border);
            border-radius: 8px;
            padding: 10px 8px;
            text-align: center;
            cursor: pointer;
            background: var(--fb-bg);
            transition: all 0.2s;
        }
        .life-event-cat-card:hover {
            border-color: var(--fb-primary);
            background: rgba(24, 119, 242, 0.04);
        }
        .life-event-cat-card.active {
            border-color: var(--fb-primary);
            background: #eff6ff;
            color: var(--fb-primary);
            font-weight: 700;
        }
        .life-event-cat-icon {
            font-size: 24px;
            margin-bottom: 4px;
            display: block;
        }
        .life-event-cat-label {
            font-size: 12px;
            font-weight: 600;
        }

        /* View As Public Sticky Banner */
        .view-as-banner {
            background: linear-gradient(135deg, #111827 0%, #1e1b4b 100%);
            color: white;
            padding: 14px 24px;
            position: sticky;
            top: 60px;
            z-index: 999;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
        }
        .view-as-banner-inner {
            max-width: 1080px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .view-as-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .view-as-eye-icon {
            font-size: 24px;
            background: rgba(255,255,255,0.1);
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .view-as-headline {
            font-size: 15px;
            font-weight: 700;
            color: #f8fafc;
        }
        .view-as-subline {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 2px;
        }
        .view-as-exit-btn {
            background: var(--fb-accent-gradient);
            color: white !important;
            border: none;
            padding: 9px 20px;
            border-radius: var(--radius-pill);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all var(--transition-smooth);
            cursor: pointer;
            box-shadow: var(--shadow-primary);
        }
        .view-as-exit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }

        /* AVATAR SHIELD & CONTEXT MENU */
        .avatar-shield-badge {
            position: absolute;
            bottom: 6px;
            left: 50%;
            transform: translateX(-50%);
            width: 38px;
            height: 38px;
            background: var(--fb-primary);
            color: #ffffff;
            border-radius: 50%;
            border: 3px solid var(--fb-card, #ffffff);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            z-index: 7;
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
        }
        .avatar-shield-badge:hover {
            transform: translateX(-50%) scale(1.1);
            background: var(--fb-primary-hover);
        }
        .avatar-menu-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            background: var(--fb-card, #ffffff);
            border: 1px solid var(--fb-border, #dadde1);
            border-radius: 10px;
            box-shadow: 0 12px 28px 0 rgba(0, 0, 0, 0.2), 0 2px 4px 0 rgba(0, 0, 0, 0.1);
            min-width: 280px;
            padding: 8px;
            z-index: 100;
            display: none;
            animation: menuFadeIn 0.15s ease-out;
        }
        .avatar-menu-dropdown.show {
            display: block;
        }
        @keyframes menuFadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .avatar-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--fb-text-primary, #050505);
            cursor: pointer;
            transition: background 0.15s;
            text-decoration: none;
            border: none;
            width: 100%;
            text-align: left;
            background: transparent;
        }
        .avatar-menu-item:hover {
            background: var(--fb-hover, #f2f2f2);
        }
        .avatar-menu-item .menu-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--fb-bg, #f0f2f5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .avatar-menu-divider {
            height: 1px;
            background: var(--fb-border, #dadde1);
            margin: 6px 0;
        }

        /* COVER MENU DROPDOWN & REPOSITION BANNER */
        .cover-menu-dropdown {
            position: absolute;
            bottom: calc(100% + 8px);
            right: 0;
            background: var(--fb-card, #ffffff);
            border: 1px solid var(--fb-border, #dadde1);
            border-radius: 10px;
            box-shadow: 0 12px 28px 0 rgba(0, 0, 0, 0.2), 0 2px 4px 0 rgba(0, 0, 0, 0.1);
            min-width: 260px;
            padding: 8px;
            z-index: 100;
            display: none;
            animation: menuFadeIn 0.15s ease-out;
        }
        .cover-menu-dropdown.show {
            display: block;
        }
        .cover-reposition-banner {
            position: absolute;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            display: none;
            align-items: center;
            gap: 20px;
            z-index: 20;
            animation: menuFadeIn 0.2s ease-out;
        }
        .cover-reposition-banner.active {
            display: flex;
        }
        .cover-photo-img.is-dragging {
            cursor: grab;
            cursor: -webkit-grab;
            user-select: none;
        }
        .cover-photo-img.is-dragging:active {
            cursor: grabbing;
            cursor: -webkit-grabbing;
        }

        /* PHOTO THEATER / LIGHTBOX (FACEBOOK STYLE) */
        .photo-theater-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.96);
            z-index: 100000;
            display: none;
            flex-direction: row;
            backdrop-filter: blur(8px);
        }
        .photo-theater-modal.active {
            display: flex;
        }
        .theater-stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            height: 100%;
            padding: 20px;
            user-select: none;
        }
        .theater-main-img {
            max-width: 100%;
            max-height: 92vh;
            object-fit: contain;
            border-radius: 6px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.8);
            transition: transform 0.2s ease;
        }
        .theater-close-btn {
            position: absolute;
            top: 16px;
            left: 16px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            cursor: pointer;
            z-index: 10;
            transition: background 0.2s, transform 0.15s;
        }
        .theater-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.08);
        }
        .theater-sidebar {
            width: 380px;
            max-width: 100%;
            background: var(--fb-card, #ffffff);
            height: 100%;
            display: flex;
            flex-direction: column;
            border-left: 1px solid var(--fb-border, rgba(255,255,255,0.1));
            z-index: 10;
            overflow-y: auto;
        }
        .theater-sidebar-header {
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--fb-border, #e5e7eb);
        }
        .theater-author-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
        }
        .theater-author-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--fb-text-primary, #050505);
            line-height: 1.2;
        }
        .theater-meta-date {
            font-size: 12px;
            color: var(--fb-text-secondary, #65676b);
            margin-top: 2px;
        }
        .theater-caption {
            padding: 16px;
            font-size: 14px;
            line-height: 1.5;
            color: var(--fb-text-primary, #050505);
            white-space: pre-wrap;
            border-bottom: 1px solid var(--fb-border, #e5e7eb);
        }
        .theater-guard-banner {
            margin: 12px 16px;
            background: #e7f3ff;
            border: 1px solid var(--fb-primary);
            border-radius: 8px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: var(--fb-primary);
            font-weight: 600;
        }
        .theater-actions {
            padding: 12px 16px;
            display: flex;
            gap: 8px;
            border-bottom: 1px solid var(--fb-border, #e5e7eb);
        }
        .theater-action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px;
            border-radius: 6px;
            border: none;
            background: var(--fb-bg, #f0f2f5);
            color: var(--fb-text-primary, #050505);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .theater-action-btn:hover {
            background: var(--fb-hover, #e4e6eb);
        }
        @media (max-width: 900px) {
            .photo-theater-modal {
                flex-direction: column;
            }
            .theater-stage {
                height: 60vh;
                padding: 10px;
            }
            .theater-sidebar {
                width: 100%;
                height: 40vh;
            }
        }

        /* VIDEO CARDS & HUB */
        .video-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }
        .video-card-item {
            background: var(--fb-card, #ffffff);
            border: 1px solid var(--fb-border, #dadde1);
            border-radius: var(--radius-md, 8px);
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }
        .video-card-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md, 0 4px 12px rgba(0,0,0,0.1));
        }
        .video-card-thumb-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000000;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .video-card-thumb-wrap video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .video-play-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .video-card-item:hover .video-play-overlay {
            background: rgba(0, 0, 0, 0.15);
        }
        .video-play-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.65);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            padding-left: 3px;
            backdrop-filter: blur(4px);
            border: 2px solid rgba(255, 255, 255, 0.8);
            transition: transform 0.2s, background 0.2s;
        }
        .video-card-item:hover .video-play-circle {
            transform: scale(1.15);
            background: var(--fb-primary);
            border-color: #ffffff;
        }
        .video-duration-badge {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.75);
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 6px;
            border-radius: 4px;
        }
        .video-card-info {
            padding: 12px;
        }
        .video-card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--fb-text-primary, #050505);
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .video-card-date {
            font-size: 12px;
            color: var(--fb-text-secondary, #65676b);
        }

        /* ═══════ PREMIUM FINISHING TOUCHES ═══════ */

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Text selection */
        ::selection {
            background: rgba(79, 70, 229, 0.15);
            color: #312e81;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 100px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        /* Links global transition */
        a {
            transition: color var(--transition-fast), opacity var(--transition-fast);
        }

        /* Profile content max-width harmonized */
        .profile-content-container {
            max-width: 1100px;
            margin: 20px auto 48px auto;
            padding: 0 20px;
        }

        /* Verified badge pulse on hover */
        .verified-badge {
            transition: transform var(--transition-smooth);
        }
        .verified-badge:hover {
            transform: scale(1.15);
        }

        /* Card entrance animation */
        @keyframes cardFadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fb-card, .post-card {
            animation: cardFadeIn 0.4s var(--ease-out-expo) both;
        }
        .fb-card:nth-child(2) { animation-delay: 0.05s; }
        .fb-card:nth-child(3) { animation-delay: 0.1s; }
        .fb-card:nth-child(4) { animation-delay: 0.15s; }

        /* Profile names block refined */
        .profile-fullname {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.15;
        }

        .profile-username-sub {
            font-size: 15px;
            color: var(--fb-text-secondary);
            margin-top: 3px;
            font-weight: 500;
            letter-spacing: -0.01em;
        }

        .profile-friends-count-sub {
            font-size: 14px;
            color: var(--fb-text-secondary);
            font-weight: 600;
            margin-top: 4px;
        }

        .profile-friends-count-sub a {
            color: var(--fb-text-secondary);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .profile-friends-count-sub a:hover {
            color: var(--fb-primary);
            text-decoration: underline;
        }

        /* Cover photo edit button refinement */
        .cover-photo-edit-btn {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: var(--radius-sm);
            padding: 9px 16px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-smooth);
        }

        .cover-photo-edit-btn:hover {
            background: #ffffff;
            box-shadow: var(--shadow-lg);
            transform: translateY(-1px);
        }

        /* Intro card item refined */
        .intro-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: var(--fb-text-primary);
            margin-bottom: 12px;
            line-height: 1.5;
            padding: 4px 0;
        }

        .intro-item-icon {
            width: 22px;
            height: 22px;
            color: var(--fb-primary);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.7;
        }

        /* Composer refined */
        .composer-fake-input {
            flex: 1;
            height: 42px;
            background: var(--fb-hover);
            border-radius: var(--radius-pill);
            padding: 0 18px;
            display: flex;
            align-items: center;
            color: var(--fb-text-secondary);
            font-size: 15px;
            cursor: pointer;
            transition: all var(--transition-smooth);
            border: 1px solid transparent;
        }

        .composer-fake-input:hover {
            background: var(--fb-btn-bg);
            border-color: var(--fb-border);
        }

        /* More tabs dropdown refined */
        .profile-nav-more-dropdown {
            border-radius: var(--radius-md) !important;
            box-shadow: var(--shadow-xl) !important;
            border: 1px solid var(--fb-divider) !important;
            animation: navDropdownFade 0.25s var(--ease-out-expo) !important;
        }

        .more-tab-link {
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
        }

        .more-tab-link:hover {
            background: var(--fb-primary-light);
            color: var(--fb-primary);
        }

        /* Profile lock banner refined */
        .profile-lock-banner {
            margin: 20px auto;
            max-width: 1100px;
            padding: 18px 24px;
            background: #ffffff;
            border: 1px solid rgba(79, 70, 229, 0.15);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .lock-shield-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--fb-primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--fb-primary);
            flex-shrink: 0;
            font-size: 26px;
        }

        /* Avatar edit button refined */
        .avatar-edit-btn {
            background: var(--fb-btn-bg);
            border: 2px solid #ffffff;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-smooth);
        }

        .avatar-edit-btn:hover {
            background: var(--fb-primary-light);
            color: var(--fb-primary);
            transform: scale(1.08);
        }

        /* Responsive refinements */
        @media (max-width: 900px) {
            .profile-content-container {
                grid-template-columns: 1fr;
            }
            /* When a tab other than posts is active on mobile, hide the left sidebar cards so tab content appears immediately */
            body.tab-not-posts .profile-left-col {
                display: none !important;
            }
            .cover-photo-wrapper {
                height: 210px;
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
            }
            .avatar-wrapper {
                width: 130px;
                height: 130px;
                margin-top: -65px;
                margin-left: auto;
                margin-right: auto;
                border-width: 4px;
            }
            .profile-fullname {
                font-size: 23px;
                justify-content: center;
                text-align: center;
            }
            .profile-main-bar {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            header {
                height: 56px;
                padding: 0 12px;
            }
        }

        @media (max-width: 640px) {
            .profile-header-container {
                padding: 0 !important;
                max-width: 100vw !important;
                overflow-x: hidden !important;
            }
            .profile-main-bar {
                padding: 0 12px 14px 12px !important;
                box-sizing: border-box !important;
                width: 100% !important;
                max-width: 100vw !important;
                overflow: hidden !important;
            }
            .profile-avatar-and-names {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .profile-names-block {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                word-break: break-word !important;
            }
            .profile-content-container {
                padding: 0 10px !important;
                margin-top: 10px !important;
                max-width: 100vw !important;
                box-sizing: border-box !important;
                overflow-x: hidden !important;
            }
            .fb-card {
                padding: 12px 14px !important;
                border-radius: var(--radius-sm) !important;
                margin-bottom: 12px !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                overflow-x: hidden !important;
            }
            .card-header-bar {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 8px !important;
                align-items: center !important;
                justify-content: space-between !important;
            }
            .card-header-bar .card-header-title {
                font-size: 16px !important;
            }
            .card-header-bar .fb-btn {
                padding: 6px 12px !important;
                font-size: 12px !important;
                white-space: nowrap !important;
            }

            /* Responsive Cover Photo on Mobile (Card format with rounded top corners matching mockup) */
            .profile-header-container {
                background: #ffffff !important;
                border-radius: 0 0 16px 16px !important;
                margin: 0 !important;
                padding-bottom: 6px !important;
            }
            .cover-photo-wrapper {
                height: 200px !important;
                border-radius: 18px 18px 0 0 !important;
                margin: 6px 8px 0 8px !important;
                overflow: hidden !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
            }
            #coverActionsWrapper {
                bottom: 12px !important;
                right: 16px !important;
            }
            .cover-photo-edit-btn {
                bottom: auto !important;
                right: auto !important;
                width: 38px !important;
                height: 38px !important;
                border-radius: 50% !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                background: #ffffff !important;
                color: #111827 !important;
                border: 1px solid rgba(0, 0, 0, 0.08) !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.22) !important;
            }
            .cover-photo-edit-btn span {
                display: none !important;
            }
            .cover-photo-edit-btn svg {
                width: 18px !important;
                height: 18px !important;
                stroke: #111827 !important;
            }

            /* Mobile Profile Avatar & Hero Information */
            .profile-avatar-and-names {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                width: 100% !important;
            }
            .avatar-wrapper {
                width: 128px !important;
                height: 128px !important;
                margin: -64px auto 0 !important;
                border: 4px solid #ffffff !important;
                border-radius: 50% !important;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.14) !important;
                position: relative !important;
                background: #0084ff !important;
            }
            .avatar-img {
                width: 100% !important;
                height: 100% !important;
                border-radius: 50% !important;
                object-fit: cover !important;
            }
            .avatar-edit-btn {
                width: 36px !important;
                height: 36px !important;
                bottom: 2px !important;
                right: 2px !important;
                border: 2.5px solid #ffffff !important;
                background: #e4e6eb !important;
                border-radius: 50% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18) !important;
            }
            .avatar-edit-btn svg {
                width: 18px !important;
                height: 18px !important;
                stroke: #050505 !important;
            }

            .profile-names-block {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                width: 100% !important;
                margin-top: 8px !important;
                padding: 0 12px !important;
                box-sizing: border-box !important;
            }
            .profile-fullname {
                font-size: 23px !important;
                line-height: 1.3 !important;
                font-weight: 700 !important;
                color: #0f172a !important;
                margin-top: 4px !important;
                text-align: center !important;
                justify-content: center !important;
            }
            .profile-username-sub {
                font-size: 14px !important;
                color: #64748b !important;
                font-weight: 500 !important;
            }
            .profile-meta-row {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 8px !important;
                flex-wrap: wrap !important;
                margin-top: 3px !important;
            }
            .profile-id-pill {
                display: inline-flex !important;
                align-items: center !important;
                gap: 5px !important;
                padding: 2px 8px !important;
                border-radius: 6px !important;
                background: #f1f5f9 !important;
                border: 1px solid #e2e8f0 !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                color: #475569 !important;
                font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace !important;
            }
            .profile-bio-text {
                font-size: 13.5px !important;
                color: #334155 !important;
                text-align: center !important;
                max-width: 92% !important;
                margin: 4px auto 6px auto !important;
                line-height: 1.45 !important;
            }
            .profile-loc-time-row {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 14px !important;
                flex-wrap: wrap !important;
                margin-top: 4px !important;
                font-size: 13px !important;
                color: #64748b !important;
            }
            .profile-friends-count-sub {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-wrap: wrap !important;
                gap: 4px !important;
                font-size: 12.5px !important;
                line-height: 1.45 !important;
                color: #334155 !important;
                margin-top: 6px !important;
                text-align: center !important;
            }

            /* Mobile Action Buttons Bar (Clean 2-Row Layout matching screenshot) */
            .profile-actions-bar {
                display: flex !important;
                flex-direction: column !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                gap: 8px !important;
                margin-top: 12px !important;
                margin-bottom: 12px !important;
            }
            .profile-actions-row {
                display: flex !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                gap: 6px !important;
                align-items: center !important;
            }
            .profile-actions-row-primary .btn-action-story {
                flex: 1.2 1 0 !important;
                min-width: 0 !important;
                height: 40px !important;
                padding: 0 8px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                border-radius: 8px !important;
                background: #0084ff !important;
                color: #ffffff !important;
                border: none !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 4px !important;
                box-sizing: border-box !important;
            }
            .profile-actions-row-primary .btn-action-edit,
            .profile-actions-row-primary .btn-action-friend,
            .profile-actions-row-primary .btn-action-message {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                height: 40px !important;
                padding: 0 8px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                border-radius: 8px !important;
                background: #e4e6eb !important;
                color: #050505 !important;
                border: none !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 4px !important;
                box-sizing: border-box !important;
            }
            .profile-actions-row-primary .profile-action-more-wrap {
                position: relative !important;
                flex-shrink: 0 !important;
            }
            .profile-actions-row-primary .btn-action-more {
                width: 44px !important;
                min-width: 44px !important;
                height: 40px !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 16px !important;
                font-weight: 800 !important;
                border-radius: 8px !important;
                background: #e4e6eb !important;
                color: #050505 !important;
                border: none !important;
                box-sizing: border-box !important;
            }
            .profile-actions-row-secondary .btn-action-lock {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                height: 38px !important;
                padding: 0 6px !important;
                font-size: 12.5px !important;
                font-weight: 600 !important;
                border-radius: 8px !important;
                background: #ede9fe !important;
                color: #7c3aed !important;
                border: none !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 4px !important;
                box-sizing: border-box !important;
            }
            .profile-actions-row-secondary .btn-action-share,
            .profile-actions-row-secondary .btn-action-follow,
            .profile-actions-row-secondary .btn-action-dashboard {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                height: 38px !important;
                padding: 0 6px !important;
                font-size: 12.5px !important;
                font-weight: 600 !important;
                border-radius: 8px !important;
                background: #e4e6eb !important;
                color: #050505 !important;
                border: none !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 4px !important;
                box-sizing: border-box !important;
            }

            /* Native Bottom Sheet for Action More Menu, Nav Tabs More Menu, Avatar Menu, and Cover Menu on Mobile */
            #profileActionMoreDropdown,
            #visitorActionMoreDropdown,
            #profileMoreTabsDropdown,
            #avatarMenuDropdown,
            #coverMenuDropdown {
                position: fixed !important;
                top: auto !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                border-radius: 20px 20px 0 0 !important;
                max-height: 80vh !important;
                max-height: 80dvh !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch !important;
                padding: 14px 14px calc(14px + env(safe-area-inset-bottom, 0px)) 14px !important;
                box-shadow: 0 -10px 35px rgba(0, 0, 0, 0.28) !important;
                z-index: 9999 !important;
                border-top: 1px solid var(--fb-border) !important;
                animation: slideUpActionMenu 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
            }
            @keyframes slideUpActionMenu {
                from { transform: translateY(100%); }
                to { transform: translateY(0); }
            }
            .more-tab-link {
                padding: 10px 12px !important;
                border-radius: 10px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                gap: 12px !important;
            }
            .sheet-drag-handle {
                display: block !important;
                width: 44px !important;
                height: 4px !important;
                background: var(--fb-border) !important;
                border-radius: 2px !important;
                margin: 0 auto 12px !important;
                flex-shrink: 0 !important;
            }
            .sheet-mobile-header {
                display: flex !important;
                flex-shrink: 0 !important;
            }

            /* Story Highlights Bar on Mobile */
            .story-highlights-container {
                padding: 10px 12px !important;
                gap: 12px !important;
                overflow-x: auto !important;
                scrollbar-width: none !important;
                -webkit-overflow-scrolling: touch !important;
                border-top: 1px solid var(--fb-divider) !important;
                border-bottom: 1px solid var(--fb-divider) !important;
                margin: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
                background: var(--fb-card) !important;
            }
            .highlight-add-circle {
                width: 62px !important;
                height: 62px !important;
                border-radius: 50% !important;
                border: 2px dashed #7c3aed !important;
                color: #7c3aed !important;
                font-size: 26px !important;
                background: rgba(124, 58, 237, 0.04) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 auto 5px !important;
            }
            .highlight-circle {
                width: 62px !important;
                height: 62px !important;
                border-radius: 50% !important;
                overflow: hidden !important;
                margin: 0 auto 5px !important;
            }
            .highlight-item {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                flex-shrink: 0 !important;
            }
            .highlight-item span {
                font-size: 11.5px !important;
                font-weight: 600 !important;
                color: var(--fb-text-primary) !important;
                max-width: 66px !important;
                line-height: 1.2 !important;
                text-align: center !important;
            }

            /* Mobile Navigation Tabs: Sticky Touch-Friendly Pill Bar */
            .profile-nav-tabs {
                position: sticky !important;
                top: 56px !important;
                z-index: 95 !important;
                background: var(--fb-card) !important;
                overflow-x: auto !important;
                scrollbar-width: none !important;
                -webkit-overflow-scrolling: touch !important;
                white-space: nowrap !important;
                margin: 0 !important;
                padding: 8px 12px !important;
                gap: 6px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                border-bottom: 1px solid var(--fb-divider) !important;
                display: flex !important;
                align-items: center !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
            }
            .profile-nav-tab {
                padding: 7px 14px !important;
                font-size: 13.5px !important;
                font-weight: 600 !important;
                border-radius: 20px !important;
                border-bottom: none !important;
                background: var(--fb-hover) !important;
                color: var(--fb-text-secondary) !important;
                transition: all 0.15s ease !important;
                flex-shrink: 0 !important;
            }
            .profile-nav-tab.active {
                background: rgba(24, 119, 242, 0.12) !important;
                color: var(--fb-primary) !important;
                font-weight: 700 !important;
            }

            /* Completion Card Mobile Row */
            .completion-item-row {
                padding: 7px 10px !important;
                font-size: 12.5px !important;
            }
        }

        @media (max-width: 480px) {
            .profile-header-container {
                padding: 0 !important;
            }
            .profile-content-container {
                padding: 0 8px !important;
            }
            .fb-modal-card {
                width: 95% !important;
                margin: 10px auto !important;
                padding: 14px !important;
            }
            .view-as-banner-inner {
                flex-direction: column;
                align-items: flex-start;
            }

            /* Mobile Profile Top Header (Matching user screenshot) */
            header.desktop-header {
                display: none !important;
            }
            .mobile-profile-header-bar {
                display: block !important;
                position: sticky;
                top: 0;
                z-index: 100;
                background: #ffffff;
                border-bottom: 1px solid #e5e7eb;
                padding: 8px 12px 10px 12px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            }
            .mobile-profile-header-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                width: 100%;
                margin-bottom: 8px;
            }
            .mobile-profile-brand {
                display: inline-flex;
                align-items: center;
                text-decoration: none;
                flex-shrink: 0;
            }
            .mobile-profile-brand img {
                height: 28px;
                width: auto;
                max-width: 120px;
                object-fit: contain;
                display: block;
            }
            .mobile-profile-top-actions {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-shrink: 0;
            }
            .mobile-head-icon-btn {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #1c1e21;
                text-decoration: none;
                position: relative;
                background: transparent;
                transition: background 0.15s ease;
            }
            .mobile-head-icon-btn:active {
                background: #f0f2f5;
            }
            .mobile-head-icon-btn svg {
                width: 20px;
                height: 20px;
                stroke: #1c1e21;
                stroke-width: 2.1;
            }
            .mobile-head-badge {
                position: absolute;
                top: -2px;
                right: -2px;
                background: #e41e3f;
                color: #ffffff;
                font-size: 10px;
                font-weight: 700;
                min-width: 16px;
                height: 16px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0 4px;
                border: 1.5px solid #ffffff;
                line-height: 1;
            }
            .mobile-head-avatar-circle {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                overflow: hidden;
                border: 1.5px solid #0084ff;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .mobile-head-avatar-circle img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .mobile-profile-search-wrap {
                width: 100%;
            }
            .mobile-profile-search-pill {
                display: flex;
                align-items: center;
                gap: 8px;
                background: #f0f2f5;
                border-radius: 20px;
                padding: 7px 14px;
                cursor: pointer;
                color: #65676b;
                font-size: 13.5px;
                user-select: none;
                transition: background 0.15s ease;
            }
            .mobile-profile-search-pill:active {
                background: #e4e6eb;
            }
            .mobile-profile-search-pill svg {
                stroke: #65676b;
                flex-shrink: 0;
            }
        }
        .mobile-profile-header-bar {
            display: none;
        }
    </style>
</head>
<body class="has-slim-dock">

    <!-- SLIM VERTICAL APP RAIL DOCK (AS SHOWN IN DESKTOP MOCKUP) -->
    <aside class="app-slim-dock" aria-label="Quick App Dock">
        <div class="dock-top-group">
            <a href="/" class="dock-item active" title="হোম ফিড">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </a>
            <a href="/messages" class="dock-item" title="মেসেঞ্জার ও চ্যাট">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span class="dock-badge">3</span>
            </a>
            <a href="#friends" onclick="switchTab('friends')" class="dock-item" title="বন্ধুরা">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </a>
            <a href="/reels" class="dock-item" title="ভিডিও ও রিলস">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
            </a>
            <a href="javascript:void(0)" onclick="toggleNotificationsDropdown()" class="dock-item" title="নোটিফিকেশন">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="dock-badge dock-badge-red">1</span>
            </a>
            <a href="/marketplace" class="dock-item" title="মার্কেটপ্লেস ও স্টোর">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"></path>
                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                    <path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"></path>
                    <path d="M2 7h20"></path>
                </svg>
            </a>
            <a href="#analytics" onclick="switchTab('analytics')" class="dock-item" title="অ্যানালিটিক্স">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
            </a>
        </div>
        <div class="dock-bottom-group">
            <a href="javascript:void(0)" onclick="openPrivacyModal()" class="dock-item" title="সেটিংস ও নিরাপত্তা">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
            </a>
        </div>
    </aside>

    <!-- MOBILE TOP NAVIGATION BAR (AS IN MOBILE SCREENSHOT) -->
    <header class="mobile-profile-header-bar mobile-only">
        <div class="mobile-profile-header-top">
            <a href="/" class="mobile-profile-brand" title="Bondhoo">
                <img src="/images/bondhoo-logo.png" alt="Bondhoo" class="bondhoo-main-brand-logo">
            </a>
            <div class="mobile-profile-top-actions">
                <a href="/watch" class="mobile-head-icon-btn" title="লাইভ ও ভিডিও">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="23 7 16 12 23 17 23 7"></polygon>
                        <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                    </svg>
                </a>
                <a href="/messages" class="mobile-head-icon-btn" title="মেসেঞ্জার">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span class="mobile-head-badge">3</span>
                </a>
                <a href="/friends" class="mobile-head-icon-btn" title="বন্ধুরা">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </a>
                <a href="/watch" class="mobile-head-icon-btn" title="ওয়াচ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect>
                        <polyline points="17 2 12 7 7 2"></polyline>
                    </svg>
                </a>
                <a href="/saved" class="mobile-head-icon-btn" title="সংরক্ষিত">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                    </svg>
                </a>
                <a href="/settings/devices" class="mobile-head-icon-btn" title="সেটিংস">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </a>
                <a href="{{ getUserProfileUrl($user) }}" class="mobile-head-avatar-circle" title="প্রোফাইল">
                    <img src="{{ $profile['avatar'] ?? '/images/default-avatar.png' }}" alt="{{ $profile['name'] }}">
                </a>
            </div>
        </div>
        <div class="mobile-profile-search-wrap">
            <div class="mobile-profile-search-pill" onclick="if(typeof openMobileSearchModal === 'function'){ openMobileSearchModal(); } else { const m = document.getElementById('mobileSearchModal'); if(m) m.style.display='block'; }">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <span>অনুসন্ধান করুন...</span>
            </div>
        </div>
    </header>

    <!-- TOP NAVIGATION BAR -->
    <header class="desktop-header">
        <div class="header-left">
            <a href="/" class="fb-logo" title="Bondhoo Home" style="background:transparent;box-shadow:none;padding:0;display:inline-flex;align-items:center;height:38px;text-decoration:none;">
                <img src="/images/bondhoo-logo.png" alt="Bondhoo" class="bondhoo-main-brand-logo" style="height: 34px; max-width: 155px; width: auto; object-fit: contain; display: block;">
            </a>
            <div class="search-box">
                <span class="search-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" class="search-input" placeholder="Bondhoo-তে খুঁজুন...">
                <span class="header-search-badge desktop-only" style="display:inline-flex;align-items:center;padding:2px 7px;font-size:11px;font-weight:700;background:var(--fb-btn-bg);border:1px solid var(--fb-border);border-radius:6px;color:var(--fb-text-secondary);font-family:inherit;">⌘ Keyword</span>
            </div>
        </div>

        <nav class="header-nav desktop-only">
            <a href="/" class="nav-tab-btn" title="হোম ফিড">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </a>
            <a href="/messages" class="nav-tab-btn" title="মেসেঞ্জার ও চ্যাট">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </a>
            <a href="/reels" class="nav-tab-btn" title="ভিডিও ও রিলস">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
            </a>
            <a href="/profile" class="nav-tab-btn {{ $isOwner ? 'active' : '' }}" title="আমার প্রোফাইল">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
        </nav>

        <div class="header-right">
            <!-- Applications button -->
            <a href="/marketplace" class="header-app-link desktop-only" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--fb-text-secondary);text-decoration:none;padding:6px 10px;border-radius:8px;transition:background 0.2s;">
                Applications
            </a>
            <!-- 9-dot App Grid -->
            <button type="button" class="icon-circle-btn" title="অ্যাপ মেনু ও এক্সপ্লোর গ্রিড" onclick="openMobileMenuDrawer()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="5" cy="5" r="2"></circle>
                    <circle cx="12" cy="5" r="2"></circle>
                    <circle cx="19" cy="5" r="2"></circle>
                    <circle cx="5" cy="12" r="2"></circle>
                    <circle cx="12" cy="12" r="2"></circle>
                    <circle cx="19" cy="12" r="2"></circle>
                    <circle cx="5" cy="19" r="2"></circle>
                    <circle cx="12" cy="19" r="2"></circle>
                    <circle cx="19" cy="19" r="2"></circle>
                </svg>
            </button>
            <!-- Messenger Quick Link -->
            <a href="/messages" class="icon-circle-btn" title="মেসেঞ্জার">
                <svg width="20" height="20" viewBox="0 0 36 36" fill="none">
                    <defs>
                        <linearGradient id="profNavMsgGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#0078FF" />
                            <stop offset="50%" stop-color="#00C6FF" />
                            <stop offset="100%" stop-color="#00E5FF" />
                        </linearGradient>
                    </defs>
                    <path fill="url(#profNavMsgGrad)" d="M18 2C9.163 2 2 8.716 2 17c0 4.717 2.33 8.91 5.98 11.644V34l5.127-2.82c1.558.432 3.197.664 4.893.664 8.837 0 16-6.716 16-15S26.837 2 18 2z"/>
                    <path fill="#ffffff" d="M19.467 19.987l-3.905-4.167-7.622 4.167 8.384-8.905 4.025 4.166 7.502-4.166-8.384 8.905z"/>
                </svg>
            </a>

            @if($viewer)
                <div style="position: relative;">
                    <div class="avatar" style="width: 38px; height: 38px; cursor: pointer; border-radius: 50%; overflow: hidden; border: 2px solid var(--fb-border);" onclick="toggleProfileTopUserDropdown()" title="{{ $viewer->name }}">
                        <img src="{{ $viewer->profile?->avatar_url ?: '/images/default-avatar.svg' }}" alt="{{ $viewer->name }}" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                    </div>
                    <div id="profileTopUserDropdown" style="display: none; position: absolute; right: 0; top: 48px; width: 260px; background: white; border-radius: 12px; box-shadow: var(--shadow-lg); border: 1px solid var(--fb-border); z-index: 200; padding: 8px;">
                        <a href="/profile" style="display: flex; gap: 10px; align-items: center; padding: 8px; border-radius: 8px; text-decoration: none; color: inherit; border-bottom: 1px solid var(--fb-border); margin-bottom: 6px;">
                            <div class="avatar" style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden;">
                                <img src="{{ $viewer->profile?->avatar_url ?: '/images/default-avatar.svg' }}" alt="{{ $viewer->name }}" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 14px;">{{ $viewer->name }}</div>
                                <div style="font-size: 12px; color: var(--fb-text-secondary);">আমার প্রোফাইল দেখুন (@ {{ $viewer->username }})</div>
                            </div>
                        </a>
                        <button type="button" onclick="handleLogout()" style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 10px; font-size: 14px; color: #ef4444; border: none; background: transparent; cursor: pointer; border-radius: 6px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            <span>লগআউট করুন</span>
                        </button>
                    </div>
                </div>
            @else
                <a href="/login" class="fb-btn fb-btn-primary" style="padding: 6px 14px; font-size: 13px;">লগইন করুন</a>
                <a href="/register" class="fb-btn fb-btn-secondary" style="padding: 6px 14px; font-size: 13px;">নিবন্ধন</a>
            @endif

            <!-- Mobile Menu Drawer Toggle -->
            <button type="button" class="icon-circle-btn mobile-menu-toggle-btn" id="mobileHeaderMenuBtn" title="মেনু ও এক্সপ্লোর" onclick="openMobileMenuDrawer()" aria-label="মেনু খুলুন">
                <svg class="mobile-menu-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path class="menu-bar menu-bar-top" d="M3.75 6.75h16.5" stroke-width="2.3" stroke-linecap="round"/>
                    <path class="menu-bar menu-bar-mid" d="M3.75 12h11.5" stroke-width="2.3" stroke-linecap="round"/>
                    <circle class="menu-bar-dot" cx="19.25" cy="12" r="1.4" fill="currentColor"/>
                    <path class="menu-bar-bot" d="M3.75 17.25h16.5" stroke-width="2.3" stroke-linecap="round"/>
                </svg>
                <span class="mobile-menu-status-dot" id="mobileHeaderMenuDot" style="display: none;"></span>
            </button>
        </div>
    </header>

    @if($isViewAsPublic ?? false)
        <!-- VIEW AS PUBLIC STICKY BANNER -->
        <div class="view-as-banner" style="background: #1e293b; color: #ffffff; padding: 10px 16px; border-bottom: 1px solid rgba(255,255,255,0.1); position: sticky; top: 56px; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <div class="view-as-banner-inner" style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                <div class="view-as-info" style="display: flex; align-items: center; gap: 10px;">
                    <span class="view-as-eye-icon" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: rgba(59, 130, 246, 0.2); color: #60a5fa;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                    <div>
                        <div class="view-as-headline" style="font-weight: 700; font-size: 14px;">আপনি এখন পাবলিক দৃষ্টিতে আপনার প্রোফাইল দেখছেন</div>
                        <div class="view-as-subline" style="font-size: 12px; color: rgba(255,255,255,0.75);">যেকোনো সাধারণ দর্শক বা অপরিচিত ব্যক্তি আপনার প্রোফাইলে যা যা দেখতে পান, তা নিচে প্রদর্শিত হচ্ছে।</div>
                    </div>
                </div>
                <a href="{{ getUserProfileUrl($user) }}" class="view-as-exit-btn" style="background: #2563eb; color: #ffffff; text-decoration: none; padding: 7px 14px; border-radius: 6px; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    <span>ভিউ অ্যাজ বন্ধ করুন</span>
                </a>
            </div>
        </div>
    @endif

    <!-- PROFILE HEADER SECTION -->
    <div class="profile-header-wrapper">
        <div class="profile-header-container">
            <!-- Cover Photo Area -->
            <div class="cover-photo-wrapper" id="coverContainer">
                @if(!empty($profile['cover_photo']))
                    <img src="{{ $profile['cover_photo'] ?: '/images/default-cover.svg' }}"
                         alt="Cover Photo"
                         class="cover-photo-img"
                         id="coverPhotoImg"
                         onerror="this.onerror=null; this.src='/images/default-cover.svg';"
                         style="object-position: center {{ $user->profile->cover_position_y ?? 50 }}%; cursor:pointer;"
                         onclick="handleCoverClick(event)">
                @else
                    <div id="coverFallback" style="width:100%;height:100%;background: var(--fb-cover-gradient);"></div>
                @endif

                <!-- Live Cover Repositioning Floating Top Bar -->
                <div class="cover-reposition-banner" id="coverRepositionBanner">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5 9 2 12 5 15"></polyline><polyline points="9 5 12 2 15 5"></polyline><polyline points="19 9 22 12 19 15"></polyline><polyline points="9 19 12 22 15 19"></polyline><line x1="2" y1="12" x2="22" y2="12"></line><line x1="12" y1="2" x2="12" y2="22"></line></svg>
                        <span style="font-weight:600;font-size:14px;">টেনে এনে কভারের অবস্থান নির্ধারণ করুন (Drag to Reposition)</span>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <button type="button" class="fb-btn fb-btn-secondary" style="padding:6px 14px;font-size:13px;" onclick="cancelCoverReposition()">বাতিল</button>
                        <button type="button" class="fb-btn fb-btn-primary" style="padding:6px 14px;font-size:13px;" id="saveCoverRepositionLiveBtn" onclick="saveCoverRepositionLive()">✓ সংরক্ষণ করুন</button>
                    </div>
                </div>

                @if($isOwner)
                    <div style="position:absolute;bottom:16px;right:16px;z-index:10;" id="coverActionsWrapper">
                        <button class="cover-photo-edit-btn" onclick="toggleCoverMenu(event)" title="কভার ফটো পরিচালনা">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <span>কভার ফটো পরিবর্তন</span>
                        </button>

                        <!-- Cover Options Dropdown (Facebook Style) -->
                        <div class="cover-menu-dropdown" id="coverMenuDropdown">
                            <div class="sheet-drag-handle"></div>
                            <div class="sheet-mobile-header">
                                <span style="font-size:15px;font-weight:700;color:var(--fb-text-primary);">কভার ফটো পরিচালনা</span>
                                <button type="button" onclick="closeCoverMenu()" style="border:none;background:var(--fb-hover);width:28px;height:28px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--fb-text-secondary);font-size:14px;font-weight:bold;">✕</button>
                            </div>
                            <button type="button" class="avatar-menu-item cover-menu-has-photo" onclick="handleCoverClick(event)" style="{{ empty($profile['cover_photo']) ? 'display:none;' : '' }}">
                                    <span class="menu-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    </span>
                                    <div>
                                        <div>কভার ফটো দেখুন</div>
                                        <div style="font-size:11px;color:var(--fb-text-secondary);font-weight:normal;">ফুল স্ক্রিনে কভার ফটো দেখুন</div>
                                    </div>
                                </button>
                                <button type="button" class="avatar-menu-item cover-menu-has-photo" onclick="startLiveCoverReposition()" style="{{ empty($profile['cover_photo']) ? 'display:none;' : '' }}">
                                    <span class="menu-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5 9 2 12 5 15"></polyline><polyline points="9 5 12 2 15 5"></polyline><polyline points="19 9 22 12 19 15"></polyline><polyline points="9 19 12 22 15 19"></polyline><line x1="2" y1="12" x2="22" y2="12"></line><line x1="12" y1="2" x2="12" y2="22"></line></svg>
                                    </span>
                                    <div>
                                        <div>পজিশন ঠিক করুন</div>
                                        <div style="font-size:11px;color:var(--fb-text-secondary);font-weight:normal;">টেনে পছন্দের পজিশনে বসান</div>
                                    </div>
                                </button>
                            <button type="button" class="avatar-menu-item" onclick="openCoverModal()">
                                <span class="menu-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                </span>
                                <div>
                                    <div>নতুন ছবি আপলোড</div>
                                    <div style="font-size:11px;color:var(--fb-text-secondary);font-weight:normal;">কম্পিউটার বা ডিভাইস থেকে আপলোড</div>
                                </div>
                            </button>
                            <button type="button" class="avatar-menu-item" onclick="openCoverHistoryModal()">
                                <span class="menu-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                </span>
                                <div>
                                    <div>কভার ছবির ইতিহাস</div>
                                    <div style="font-size:11px;color:var(--fb-text-secondary);font-weight:normal;">পূর্বে ব্যবহৃত কভার ছবিগুলো দেখুন</div>
                                </div>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Profile Info Bar -->
            <div class="profile-main-bar">
                <div class="profile-avatar-and-names">
                    @php
                        $currentPhoto = $user->profilePhotos()->where('is_current', true)->first();
                        $currentFrame = $currentPhoto->frame_id ?? null;
                    @endphp
                    <div class="avatar-wrapper" style="position:relative;">
                        <img src="{{ $profile['avatar'] ?: '/images/default-avatar.svg' }}"
                             alt="{{ $profile['name'] }}"
                             class="avatar-img {{ !empty($hasActiveStory) ? 'avatar-has-story' : '' }}"
                             id="profileAvatarImg"
                             onerror="this.onerror=null; this.src='/images/default-avatar.svg';"
                             style="cursor:pointer; {{ !empty($activeLiveStream) ? 'border: 3px solid #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,0.4);' : (!empty($hasActiveStory) ? 'border: 3.5px solid #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.35); padding: 2px;' : '') }}"
                             onclick="handleAvatarClick(event)">

                        @if(!empty($activeLiveStream))
                            <a href="{{ route('live.show', $activeLiveStream->id) }}" style="position:absolute;bottom:6px;left:50%;transform:translateX(-50%);background:#ef4444;color:white;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:800;text-decoration:none;z-index:9;box-shadow:0 2px 6px rgba(0,0,0,0.4);display:flex;align-items:center;gap:4px;">
                                <span style="width:6px;height:6px;border-radius:50%;background:white;"></span>
                                LIVE
                            </a>
                        @elseif(!empty($hasActiveStory))
                            <div class="avatar-story-badge" onclick="openProfileStoriesModal(event)" title="সক্রিয় স্টোরি দেখুন" style="position:absolute;top:4px;right:4px;background:linear-gradient(135deg, #2563eb, #7c3aed);color:white;padding:3px 8px;border-radius:12px;font-size:10px;font-weight:700;z-index:9;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;gap:3px;">
                                <span>📖</span> স্টোরি
                            </div>
                        @endif

                        @if(!empty($profile['has_avatar_guard']))
                            <div class="avatar-shield-badge" id="avatarShieldBadge" title="প্রোফাইল পিকচার গার্ড সক্রিয়" onclick="handleShieldClick(event)">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                                </svg>
                            </div>
                        @endif

                        @if($currentFrame)
                            <div class="avatar-frame-overlay frame-badge-{{ $currentFrame }}" id="currentFrameOverlay"></div>
                        @endif

                        @if($isOwner)
                            <div style="position:absolute;bottom:0;right:0;display:flex;gap:4px;z-index:8;">
                                <button class="avatar-edit-btn" onclick="toggleAvatarMenu(event)" title="প্রোফাইল ছবি পরিচালনা">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                        <circle cx="12" cy="13" r="4"></circle>
                                    </svg>
                                </button>
                            </div>

                            <!-- Avatar Options Dropdown (Facebook Style) -->
                            <div class="avatar-menu-dropdown" id="avatarMenuDropdown">
                                <div class="sheet-drag-handle"></div>
                                <div class="sheet-mobile-header">
                                    <span style="font-size:15px;font-weight:700;color:var(--fb-text-primary);">প্রোফাইল ছবি পরিচালনা</span>
                                    <button type="button" onclick="closeAvatarMenu()" style="border:none;background:var(--fb-hover);width:28px;height:28px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--fb-text-secondary);font-size:14px;font-weight:bold;">✕</button>
                                </div>
                                @if(!empty($hasActiveStory))
                                    <button type="button" class="avatar-menu-item" onclick="openProfileStoriesModal(event)">
                                        <span class="menu-icon">
                                            <span style="font-size:16px;">📖</span>
                                        </span>
                                        <div>
                                            <div>সক্রিয় স্টোরি দেখুন (Active Story)</div>
                                            <div style="font-size:11px;color:var(--fb-text-secondary);font-weight:normal;">চলমান ২৪ ঘণ্টার স্টোরি দেখুন</div>
                                        </div>
                                    </button>
                                @endif
                                <button type="button" class="avatar-menu-item" onclick="openPhotoTheater('{{ $profile['avatar'] ?? '' }}', '{{ addslashes($profile['name']) }}', 'প্রোফাইল ছবি', 'সম্প্রতি', null, {{ !empty($profile['has_avatar_guard']) ? 'true' : 'false' }})">
                                    <span class="menu-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    </span>
                                    <div>
                                        <div>প্রোফাইল ছবি দেখুন</div>
                                        <div style="font-size:11px;color:var(--fb-text-secondary);font-weight:normal;">ফুল স্ক্রিনে ছবিটি বড় করে দেখুন</div>
                                    </div>
                                </button>
                                <button type="button" class="avatar-menu-item" onclick="openAvatarModal()">
                                    <span class="menu-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                    </span>
                                    <div>
                                        <div>প্রোফাইল ছবি পরিবর্তন</div>
                                        <div style="font-size:11px;color:var(--fb-text-secondary);font-weight:normal;">নতুন ছবি আপলোড বা চয়ন করুন</div>
                                    </div>
                                </button>
                                <button type="button" class="avatar-menu-item" onclick="openAvatarFrameModal()">
                                    <span class="menu-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><path d="m9 9 6 6"></path><path d="m15 9-6 6"></path></svg>
                                    </span>
                                    <div>
                                        <div>ফ্রেম যোগ করুন</div>
                                        <div style="font-size:11px;color:var(--fb-text-secondary);font-weight:normal;">প্রোফাইলে সুন্দর ফ্রেম যুক্ত করুন</div>
                                    </div>
                                </button>
                                <button type="button" class="avatar-menu-item" onclick="openAvatarHistoryModal()">
                                    <span class="menu-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    </span>
                                    <div>
                                        <div>ছবির ইতিহাস</div>
                                        <div style="font-size:11px;color:var(--fb-text-secondary);font-weight:normal;">পূর্বের ব্যবহৃত ছবিগুলো দেখুন</div>
                                    </div>
                                </button>
                                <div class="avatar-menu-divider"></div>
                                <button type="button" class="avatar-menu-item" onclick="handleToggleAvatarGuard()">
                                    <span class="menu-icon" style="background:var(--fb-primary-light);color:var(--fb-primary);">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                    </span>
                                    <div>
                                        <div id="avatarGuardMenuTitle">{{ !empty($profile['has_avatar_guard']) ? 'প্রোফাইল পিকচার গার্ড বন্ধ করুন' : 'প্রোফাইল পিকচার গার্ড চালু করুন' }}</div>
                                        <div style="font-size:11px;color:var(--fb-text-secondary);font-weight:normal;">
                                            {{ !empty($profile['has_avatar_guard']) ? 'গার্ড সরালে যেকেউ ছবি ডাউনলোড বা শেয়ার করতে পারবে' : 'অন্যরা আপনার ছবি ডাউনলোড বা শেয়ার করতে পারবে না' }}
                                        </div>
                                    </div>
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="profile-names-block">
                        <h1 class="profile-fullname" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            {{ $profile['name'] }}
                            @if(!empty($profile['pronouns']))
                                <span class="profile-pronouns-badge" style="font-size:13px;background:var(--fb-btn-bg);padding:2px 8px;border-radius:12px;color:var(--fb-text-secondary);font-weight:500;">({{ $profile['pronouns'] }})</span>
                            @endif
                            @if($profile['is_verified'])
                                <span class="verified-badge" title="ভেরিফাইড নাগরিক একাউন্ট">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5l-4-4 1.41-1.41L11 13.67l6.59-6.59L19 8.5l-8 8z"/>
                                    </svg>
                                </span>
                            @endif
                            @if(!empty($profile['category']))
                                <span style="font-size:13px;background:var(--fb-primary-light);color:var(--fb-primary);padding:3px 10px;border-radius:20px;font-weight:600;">{{ $profile['category'] }}</span>
                            @endif
                            @if(!empty($activeLiveStream))
                                <a href="{{ route('live.show', $activeLiveStream->id) }}" style="text-decoration:none;">
                                    <span style="font-size:12px;background:#ef4444;color:white;padding:3px 10px;border-radius:20px;font-weight:800;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(239,68,68,0.5);">
                                        <span style="width:6px;height:6px;border-radius:50%;background:white;animation:pulse 1s infinite;"></span>
                                        <span>LIVE NOW</span>
                                    </span>
                                </a>
                            @endif
                            @if($profile['is_professional_mode'] ?? false)
                                <span style="font-size:12px;background:#fef3c7;color:#b45309;padding:2px 8px;border-radius:12px;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                                    <span>প্রফেশনাল মোড</span>
                                </span>
                            @endif
                        </h1>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:2px;">
                            <span class="profile-username-sub">{{ '@' . $profile['username'] }}</span>
                            <span style="display:inline-flex;align-items:center;gap:5px;padding:2px 8px;border-radius:6px;background:var(--fb-hover);border:1px solid var(--fb-border);font-size:12px;font-weight:700;color:var(--fb-text-secondary);font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;" title="প্রোফাইল আইডি (Profile ID)">
                                <span>ID: #{{ $profile['id'] }}</span>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ $profile['id'] }}'); if(typeof showToast==='function'){showToast('প্রোফাইল আইডি কপি হয়েছে (#{{ $profile['id'] }})');}else{alert('প্রোফাইল আইডি কপি হয়েছে (#{{ $profile['id'] }})');}" style="background:none;border:none;padding:0;cursor:pointer;color:inherit;display:inline-flex;align-items:center;" title="প্রোফাইল আইডি কপি করুন">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                </button>
                            </span>
                        </div>
                        @if(!empty($profile['headline']))
                            <div style="font-size:14px;color:var(--fb-text-secondary);margin-top:3px;font-weight:500;">{{ $profile['headline'] }}</div>
                        @elseif(!empty($profile['bio']))
                            <div style="font-size:14px;color:var(--fb-text-secondary);margin-top:3px;font-weight:400;max-width:550px;">{{ \Illuminate\Support\Str::limit($profile['bio'], 90) }}</div>
                        @endif

                        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:12px;margin-top:6px;font-size:13px;color:var(--fb-text-secondary);">
                            @if(!empty($profile['city']) || !empty($profile['country']))
                                <span style="display:inline-flex;align-items:center;gap:4px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <span>{{ implode(', ', array_filter([$profile['city'] ?? null, $profile['country'] ?? null])) }}</span>
                                </span>
                            @endif
                            @if(!empty($profile['website']))
                                <span style="display:inline-flex;align-items:center;gap:4px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                    <a href="{{ \Illuminate\Support\Str::startsWith($profile['website'], 'http') ? $profile['website'] : 'https://'.$profile['website'] }}" target="_blank" rel="noopener" style="color:var(--fb-primary);text-decoration:none;">{{ preg_replace('#^https?://#', '', $profile['website']) }}</a>
                                </span>
                            @endif
                            @if(!empty($profile['member_since']))
                                <span style="display:inline-flex;align-items:center;gap:4px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <span>যোগদান: {{ $profile['member_since'] }}</span>
                                </span>
                            @endif
                        </div>

                        <div class="profile-friends-count-sub" style="margin-top: 6px;">
                            <a href="#friends" onclick="switchTab('friends')">{{ number_format($profile['friends_count']) }} জন বন্ধু</a>
                            @if($profile['mutual_friends_count'] > 0)
                                • {{ $profile['mutual_friends_count'] }} জন মিউচুয়াল ফ্রেন্ড
                            @endif
                            • <a href="javascript:void(0)" onclick="openFollowModal('followers')" style="color:var(--fb-text-secondary);text-decoration:none;cursor:pointer;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ number_format($profile['followers_count'] ?? 0) }} জন ফলোয়ার</a>
                            • <a href="javascript:void(0)" onclick="openFollowModal('following')" style="color:var(--fb-text-secondary);text-decoration:none;cursor:pointer;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ number_format($profile['following_count'] ?? 0) }} জন ফলোয়িং</a>
                            • <span>{{ number_format($profile['posts_count'] ?? 0) }} টি পোস্ট</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons in One Sleek Flex Row -->
                <div class="profile-actions-bar">
                    @if($isOwner)
                        <div class="profile-actions-row profile-actions-row-primary">
                            <button class="fb-btn fb-btn-primary btn-action-story" onclick="openStoryModal()" style="background:#0084ff;color:white;border:none;box-shadow:0 2px 8px rgba(0,132,255,0.3);">
                                <span style="font-size:15px;line-height:1;">+</span>
                                <span>স্টোরি যোগ করুন</span>
                            </button>
                            <button class="fb-btn fb-btn-secondary btn-action-edit" onclick="openEditProfileModal()">
                                <span>প্রোফাইল সম্পাদনা</span>
                            </button>

                            <!-- Facebook-style '...' More Options Dropdown -->
                            <div class="profile-action-more-wrap" id="profileActionMoreWrapper">
                                <button class="fb-btn fb-btn-secondary btn-action-more" onclick="toggleProfileActionMoreMenu(event)" title="আরও বিকল্প">
                                    •••
                                </button>
                                <div class="profile-nav-more-dropdown" id="profileActionMoreDropdown">
                                    <div class="sheet-drag-handle"></div>
                                    <div class="sheet-mobile-header">
                                        <span style="font-size:15px;font-weight:700;color:var(--fb-text-primary);">প্রোফাইল সেটিংস ও বিকল্প</span>
                                        <button type="button" onclick="closeProfileActionMoreMenu()" style="border:none;background:var(--fb-hover);width:28px;height:28px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--fb-text-secondary);font-size:14px;font-weight:bold;">✕</button>
                                    </div>
                                    <a href="{{ getUserProfileUrl($user) }}?view_as=public" class="more-tab-link" onclick="closeProfileActionMoreMenu()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        <span>ভিউ অ্যাজ (পাবলিক ভিউ)</span>
                                    </a>
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeProfileActionMoreMenu(); copyProfileLink()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                        <span>প্রোফাইল লিংক কপি করুন</span>
                                    </button>
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeProfileActionMoreMenu(); openProfileShareModal()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                        <span>কিউআর কোড দেখুন</span>
                                    </button>
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeProfileActionMoreMenu(); exportProfileData()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                        <span>প্রোফাইল ডেটা এক্সপোর্ট (JSON)</span>
                                    </button>
                                    <div class="more-dropdown-divider"></div>
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeProfileActionMoreMenu(); openSecurityModal()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                                        <span>সিকিউরিটি ও ডিভাইস সেন্টার</span>
                                    </button>
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeProfileActionMoreMenu(); openNotificationSettingsModal()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                        <span>নোটিফিকেশন সেটিংস</span>
                                    </button>
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeProfileActionMoreMenu(); openPrivacyModal()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                        <span>প্রাইভেসি সেন্টার</span>
                                    </button>
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeProfileActionMoreMenu(); openBlockingCenterModal()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                        <span>ব্লকিং সেন্টার (Blocked Users)</span>
                                    </button>
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeProfileActionMoreMenu(); openVerificationModal()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                                        <span>পরিচয় যাচাইকরণ (ভেরিফিকেশন)</span>
                                    </button>
                                    <a href="#activity" class="more-tab-link" onclick="closeProfileActionMoreMenu(); switchTab('activity')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                                        <span>অ্যাক্টিভিটি হিস্ট্রি</span>
                                    </a>
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeProfileActionMoreMenu(); switchTab('professional')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                                        <span>প্রফেশনাল ড্যাশবোর্ড</span>
                                    </button>
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeProfileActionMoreMenu(); openManageSectionsModal()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                                        <span>সেকশন পরিচালনা করুন</span>
                                    </button>
                                    <div class="more-dropdown-divider"></div>
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;color:var(--fb-red);" onclick="closeProfileActionMoreMenu(); openAccountDeactivateModal()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                        <span>অ্যাকাউন্ট ডিঅ্যাক্টিভেট বা ডিলিট</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="profile-actions-row profile-actions-row-secondary">
                            <button id="lockToggleBtn" class="fb-btn btn-action-lock {{ $profile['is_profile_locked'] ? 'fb-btn-locked-active' : 'fb-btn-lock' }}" onclick="openProfileLockModal()">
                                <span>{{ $profile['is_profile_locked'] ? 'আনলক করুন' : 'প্রোফাইল লক' }}</span>
                            </button>
                            <button class="fb-btn fb-btn-secondary btn-action-share" onclick="openProfileShareModal()" title="প্রোফাইল শেয়ার" style="display:inline-flex;align-items:center;gap:5px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>
                                <span>শেয়ার</span>
                            </button>
                            <button class="fb-btn fb-btn-secondary btn-action-dashboard" onclick="switchTab('professional')" title="প্রফেশনাল ড্যাশবোর্ড" style="display:inline-flex;align-items:center;gap:5px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                                <span>ড্যাশবোর্ড</span>
                            </button>
                        </div>
                    @else
                        <div class="profile-actions-row profile-actions-row-primary">
                            @if(($profile['friendship_status'] ?? '') === 'friends')
                                <button class="fb-btn fb-btn-secondary btn-action-friend" onclick="confirmUnfriend({{ $user->id }}, '{{ addslashes($profile['name']) }}')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <span>বন্ধু আছেন ▾</span>
                                </button>
                            @elseif(($profile['friendship_status'] ?? '') === 'request_sent')
                                <button class="fb-btn fb-btn-secondary btn-action-friend" onclick="handleCancelFriendRequest({{ $user->id }})" style="color:#e11d48;border-color:#ffe4e6;background:#fff1f2;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    <span>অনুরোধ বাতিল</span>
                                </button>
                            @elseif(($profile['friendship_status'] ?? '') === 'request_received')
                                <button class="fb-btn fb-btn-primary btn-action-friend" onclick="handleAcceptFriendRequest({{ $user->id }})">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <span>অনুরোধ গ্রহণ</span>
                                </button>
                                <button class="fb-btn fb-btn-secondary btn-action-friend" onclick="handleDeclineFriendRequest({{ $user->id }})">
                                    বাতিল
                                </button>
                            @else
                                <button class="fb-btn fb-btn-primary btn-action-friend" onclick="handleSendFriendRequest({{ $user->id }})">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                    <span>বন্ধু যোগ করুন</span>
                                </button>
                            @endif

                            <button type="button" class="fb-btn fb-btn-secondary btn-action-message" onclick="openDirectChatWithUser({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->username }}', '{{ $profile['avatar_url'] ?? '' }}')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <span>বার্তা পাঠান</span>
                            </button>

                            <!-- Visitor More (•••) Dropdown -->
                            <div class="profile-action-more-wrap" id="visitorActionMoreWrapper">
                                <button type="button" class="fb-btn fb-btn-secondary btn-action-more" onclick="toggleVisitorActionMoreMenu(event)" title="আরও বিকল্প">
                                    •••
                                </button>
                                <div class="profile-nav-more-dropdown" id="visitorActionMoreDropdown">
                                    <!-- Mobile Drag Handle & Header -->
                                    <div class="sheet-drag-handle"></div>
                                    <div class="sheet-mobile-header">
                                        <span style="font-size:15px;font-weight:700;color:var(--fb-text-primary);">বিকল্প ও অ্যাকশন</span>
                                        <button type="button" onclick="closeVisitorActionMoreMenu()" style="border:none;background:var(--fb-hover);width:28px;height:28px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--fb-text-secondary);font-size:14px;font-weight:bold;">✕</button>
                                    </div>

                                    <!-- 1. Send Message -->
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeVisitorActionMoreMenu(); openDirectChatWithUser({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->username }}', '{{ $profile['avatar_url'] ?? '' }}')">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                        <span>বার্তা পাঠান</span>
                                    </button>

                                    <!-- 2. Call (Audio/Video) via Chat -->
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeVisitorActionMoreMenu(); openDirectChatWithUser({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->username }}', '{{ $profile['avatar_url'] ?? '' }}')">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                        <span>কল করুন (অডিও / ভিডিও)</span>
                                    </button>

                                    <!-- 3. Friendship Status & Actions -->
                                    @if(($profile['friendship_status'] ?? '') === 'friends')
                                        <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeVisitorActionMoreMenu(); switchTab('friends')">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                            <span>বন্ধুত্ব ও মিউচুয়াল ফ্রেন্ডস দেখুন</span>
                                        </button>
                                        <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;color:var(--fb-red);" onclick="closeVisitorActionMoreMenu(); confirmUnfriend({{ $user->id }}, '{{ addslashes($profile['name']) }}')">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="18" y1="8" x2="23" y2="13"></line><line x1="23" y1="8" x2="18" y2="13"></line></svg>
                                            <span>ফ্রেন্ডলিস্ট থেকে সরান (Unfriend)</span>
                                        </button>
                                    @elseif(($profile['friendship_status'] ?? '') === 'request_sent')
                                        <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;color:#e11d48;" onclick="closeVisitorActionMoreMenu(); handleCancelFriendRequest({{ $user->id }})">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                            <span>বন্ধুত্বের অনুরোধ প্রত্যাহার করুন</span>
                                        </button>
                                    @elseif(($profile['friendship_status'] ?? '') === 'request_received')
                                        <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;color:var(--fb-primary);" onclick="closeVisitorActionMoreMenu(); handleAcceptFriendRequest({{ $user->id }})">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            <span>বন্ধুত্বের অনুরোধ গ্রহণ করুন</span>
                                        </button>
                                    @else
                                        <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;color:var(--fb-primary);" onclick="closeVisitorActionMoreMenu(); handleSendFriendRequest({{ $user->id }})">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                            <span>বন্ধু যোগ করুন</span>
                                        </button>
                                    @endif

                                    <!-- 4. Follow / Unfollow -->
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeVisitorActionMoreMenu(); toggleFollowUser({{ $user->id }})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                        <span>{{ ($profile['is_following'] ?? false) ? 'আনফলো করুন' : 'ফলো করুন' }}</span>
                                    </button>

                                    <!-- 5. Search / Filter Timeline Posts -->
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeVisitorActionMoreMenu(); scrollToProfilePostsSearch()">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                        <span>টাইমলাইনে পোস্ট খুঁজুন ও ফিল্টার</span>
                                    </button>

                                    <!-- 6. View Photos & Albums -->
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeVisitorActionMoreMenu(); switchTab('photos')">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                        <span>ছবি ও অ্যালবাম ব্রাউজ করুন</span>
                                    </button>

                                    <!-- 7. Copy Profile Link -->
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeVisitorActionMoreMenu(); copyProfileLink()">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                        <span>প্রোফাইল লিংক কপি করুন</span>
                                    </button>

                                    <!-- 8. Share Profile & QR Code -->
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeVisitorActionMoreMenu(); openProfileShareModal()">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                                        <span>শেয়ার ও কিউআর কোড দেখুন</span>
                                    </button>

                                    <div class="more-dropdown-divider"></div>

                                    <!-- 9. Restrict User -->
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;" onclick="closeVisitorActionMoreMenu(); handleRestrictUser('{{ $user->username }}')">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                        <span>প্রোফাইল রেস্ট্রিক্ট করুন</span>
                                    </button>

                                    <!-- 10. Block User -->
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;color:var(--fb-red);" onclick="closeVisitorActionMoreMenu(); handleBlockUser({{ $user->id }}, '{{ addslashes($profile['name']) }}')">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                        <span>ব্যবহারকারীকে ব্লক করুন</span>
                                    </button>

                                    <!-- 11. Find Support or Report Profile -->
                                    <button type="button" class="more-tab-link" style="width:100%;border:none;background:transparent;text-align:left;color:var(--fb-red);" onclick="closeVisitorActionMoreMenu(); openReportUserModal({{ $user->id }}, '{{ addslashes($profile['name']) }}')">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                        <span>রিপোর্ট বা সহায়তা নিন</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="profile-actions-row profile-actions-row-secondary">
                            <!-- Follow/Unfollow Button with Follow Back state -->
                            <button class="fb-btn btn-action-follow {{ ($profile['is_following'] ?? false) ? 'fb-btn-secondary' : 'fb-btn-primary' }}" id="headerFollowBtn" onclick="toggleFollowUser({{ $user->id }})">
                                @if($profile['is_following'] ?? false)
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <span>ফলো করছেন</span>
                                @elseif($profile['is_followed_by_target'] ?? false)
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                    <span>ফলো ব্যাক করুন</span>
                                @else
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                    <span>ফলো করুন</span>
                                @endif
                            </button>

                            <button class="fb-btn fb-btn-secondary btn-action-share" onclick="openProfileShareModal()" title="প্রোফাইল শেয়ার ও কিউআর কোড">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                                <span>শেয়ার</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Story Highlights Carousel -->
            @if((isset($highlights) && $highlights->isNotEmpty()) || $isOwner)
                <div class="story-highlights-container">
                    @if($isOwner)
                        <div class="highlight-item" onclick="openCreateHighlightModal()" title="নতুন হাইলাইট যোগ করুন">
                            <div class="highlight-add-circle">+</div>
                            <span style="font-size:12px;font-weight:600;color:var(--fb-text-primary);">নতুন হাইলাইট</span>
                        </div>
                    @endif
                    @foreach($highlights ?? [] as $hl)
                        @php
                            $hlItems = $hl->items ? $hl->items->map(function($it) {
                                return [
                                    'id' => $it->id,
                                    'media_path' => $it->media_path ?: ($it->story ? $it->story->media_url : null),
                                    'media_type' => $it->media_type ?? 'image',
                                ];
                            })->filter(function($it) {
                                return !empty($it['media_path']);
                            })->values() : collect();

                            if ($hlItems->isEmpty()) {
                                $hlItems = collect([[
                                    'id' => 0,
                                    'media_path' => $hl->cover_image_path ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600',
                                    'media_type' => 'image',
                                ]]);
                            }
                        @endphp
                        <div class="highlight-item" onclick='viewHighlight({{ $hl->id }}, @json($hl->title), @json($hl->cover_image_path), @json($hlItems))' title="{{ $hl->title }}">
                            <div class="highlight-circle">
                                <img src="{{ $hl->cover_image_path ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120' }}" alt="{{ $hl->title }}">
                            </div>
                            <span style="font-size:12px;font-weight:600;color:var(--fb-text-primary);max-width:72px;text-overflow:ellipsis;white-space:nowrap;overflow:hidden;">{{ $hl->title }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Profile Navigation Tabs -->
            <ul class="profile-nav-tabs" id="profileNavTabsList">
                <li><a href="#posts" class="profile-nav-tab active" id="tab-posts" onclick="switchTab('posts')">পোস্ট</a></li>
                <li><a href="#about" class="profile-nav-tab" id="tab-about" onclick="switchTab('about')">পরিচিতি</a></li>
                <li><a href="#friends" class="profile-nav-tab" id="tab-friends" onclick="switchTab('friends')">বন্ধুরা ({{ $profile['friends_count'] }})</a></li>
                <li><a href="#photos" class="profile-nav-tab" id="tab-photos" onclick="switchTab('photos')">ছবি ও অ্যালবাম ({{ $profile['photos_count'] }})</a></li>
                <li><a href="#videos" class="profile-nav-tab" id="tab-videos" onclick="switchTab('videos')">ভিডিও ({{ $profile['videos_count'] ?? 0 }})</a></li>
                <li><a href="#reels" class="profile-nav-tab" id="tab-reels" onclick="switchTab('reels')">রিলস</a></li>
                @if($isOwner)
                    <li><a href="#saved" class="profile-nav-tab" id="tab-saved" onclick="switchTab('saved')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                        <span>সংরক্ষিত</span>
                    </a></li>
                    <li><a href="#activity" class="profile-nav-tab" id="tab-activity" onclick="switchTab('activity')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        <span>অ্যাক্টিভিটি</span>
                    </a></li>
                    <li><a href="#professional" class="profile-nav-tab" id="tab-professional" onclick="switchTab('professional')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        <span>ড্যাশবোর্ড</span>
                    </a></li>
                    <li><a href="#analytics" class="profile-nav-tab" id="tab-analytics" onclick="switchTab('analytics')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        <span>অ্যানালিটিক্স</span>
                    </a></li>
                @endif
                <li class="profile-nav-more-item" style="position: relative;">
                    <button type="button" class="profile-nav-tab profile-nav-more-btn" id="tab-more-btn" onclick="toggleMoreTabsDropdown(event)">
                        <span id="tab-more-btn-text">আরও</span> <span style="font-size: 11px; margin-left: 3px;">▾</span>
                    </button>
                    <div class="profile-nav-more-dropdown" id="profileMoreTabsDropdown">
                        <div class="sheet-drag-handle"></div>
                        <div class="sheet-mobile-header">
                            <span style="font-size:15px;font-weight:700;color:var(--fb-text-primary);">আরও অপশন ও বিভাগ</span>
                            <button type="button" onclick="closeMoreTabsDropdown()" style="border:none;background:var(--fb-hover);width:28px;height:28px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--fb-text-secondary);font-size:14px;font-weight:bold;">✕</button>
                        </div>
                        @if($isOwner)
                            <a href="#saved" class="more-tab-link" id="moreLink-saved" onclick="selectMoreTab('saved', 'সংরক্ষিত', event)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                                <span>সংরক্ষিত আইটেম</span>
                            </a>
                            <a href="#activity" class="more-tab-link" id="moreLink-activity" onclick="selectMoreTab('activity', 'অ্যাক্টিভিটি', event)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                                <span>অ্যাক্টিভিটি হিস্ট্রি</span>
                            </a>
                            <a href="#professional" class="more-tab-link" id="moreLink-professional" onclick="selectMoreTab('professional', 'ড্যাশবোর্ড', event)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                                <span>প্রফেশনাল ড্যাশবোর্ড</span>
                            </a>
                            <a href="#analytics" class="more-tab-link" id="moreLink-analytics" onclick="selectMoreTab('analytics', 'অ্যানালিটিক্স', event)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                                <span>প্রোফাইল অ্যানালিটিক্স</span>
                            </a>
                            <div class="more-dropdown-divider"></div>
                            <a href="javascript:void(0)" class="more-tab-link" onclick="closeAllProfileDropdowns(); openModal('manageSectionsModal');">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                                <span>সেকশন পরিচালনা</span>
                            </a>
                        @endif
                        <a href="javascript:void(0)" class="more-tab-link" onclick="closeAllProfileDropdowns(); openVerificationModal();">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                            <span>পরিচয় ভেরিফিকেশন</span>
                        </a>
                        <a href="javascript:void(0)" class="more-tab-link" onclick="closeAllProfileDropdowns(); openPrivacyModal();">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <span>গোপনীয়তা সেটিংস</span>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <!-- LOCKED PROFILE BANNER (IF LOCKED) -->
    @if($profile['is_profile_locked'])
        <div class="profile-lock-banner">
            <div class="lock-shield-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div>
            <div class="lock-banner-text">
                <h4>{{ $isOwner ? 'আপনার প্রোফাইল লক করা আছে' : $profile['name'] . '-এর প্রোফাইল লক করা আছে' }}</h4>
                <p>{{ $isOwner ? 'লক করা প্রোফাইলে শুধুমাত্র আপনার বন্ধুরা টাইমলাইন পোস্ট, ছবি এবং পূর্ণ তথ্য দেখতে পাবেন।' : 'শুধুমাত্র বন্ধুরা এই প্রোফাইলের পোস্ট ও ছবি দেখতে পারবেন।' }}</p>
            </div>
        </div>
    @endif

    <!-- MAIN PROFILE BODY -->
    <div class="profile-content-container">

        <!-- ======================= LEFT SIDEBAR ======================= -->
        <div class="profile-left-col">
            <!-- Profile Completion Giant Card (As Shown in Mockup) -->
            <div class="fb-card" id="completionCard" style="margin-bottom: 16px; border: 1px solid var(--fb-border); border-radius: 14px; padding: 18px;">
                <div class="completion-giant-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 10px;">
                    <div>
                        <div style="font-size:16px; font-weight:800; color:var(--fb-text-primary);">প্রোফাইল সম্পূর্ণতা</div>
                        <div class="completion-giant-pct" style="font-size:38px; font-weight:900; color:#0084ff; line-height:1; margin-top:4px;">
                            {{ $completion['percentage'] ?? 31 }}%
                        </div>
                    </div>
                </div>
                <div class="completion-progress-track" style="width:100%; height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden; margin-bottom:14px;">
                    <div class="completion-progress-fill" style="height:100%; width:{{ $completion['percentage'] ?? 31 }}%; background:linear-gradient(90deg, #3b82f6, #0084ff, #06b6d4); border-radius:999px; transition:width 0.6s ease;"></div>
                </div>

                <!-- Checklist -->
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 10px; background:var(--fb-hover); border-radius:8px; font-size:13px;">
                        <span style="display:flex; align-items:center; gap:8px; font-weight:600;">
                            <span style="color:#10b981; font-size:14px;">✔</span>
                            <span>প্রোফাইল ছবি আপলোড</span>
                        </span>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 10px; background:var(--fb-hover); border-radius:8px; font-size:13px;">
                        <span style="display:flex; align-items:center; gap:8px; font-weight:500;">
                            <span style="color:#94a3b8; font-size:12px;">○</span>
                            <span>কভার ছবি যোগ করুন</span>
                        </span>
                        <button type="button" class="fb-btn fb-btn-secondary" style="padding:2px 10px; height:26px; font-size:11px; font-weight:700;" onclick="openCoverModal()">+ যোগ</button>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 10px; background:var(--fb-hover); border-radius:8px; font-size:13px;">
                        <span style="display:flex; align-items:center; gap:8px; font-weight:500;">
                            <span style="color:#94a3b8; font-size:12px;">○</span>
                            <span>Onboarding</span>
                        </span>
                        <button type="button" class="fb-btn fb-btn-secondary" style="padding:2px 10px; height:26px; font-size:11px; font-weight:700;" onclick="openEditProfileWithTab('basic')">+ যোগ</button>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 10px; background:var(--fb-hover); border-radius:8px; font-size:13px;">
                        <span style="display:flex; align-items:center; gap:8px; font-weight:500;">
                            <span style="color:#94a3b8; font-size:12px;">○</span>
                            <span>বায়ো যোগ করুন</span>
                        </span>
                        <button type="button" class="fb-btn fb-btn-secondary" style="padding:2px 10px; height:26px; font-size:11px; font-weight:700;" onclick="openEditProfileWithTab('about')">+ যোগ</button>
                    </div>
                </div>
            </div>

            <!-- Live Analytics Card (As Shown in Mockup) -->
            <div class="live-analytics-card fb-card" style="margin-bottom:16px; border:1px solid var(--fb-border); border-radius:14px; padding:18px;">
                <div class="live-analytics-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                    <div class="live-analytics-title" style="font-size:15px; font-weight:800; color:var(--fb-text-primary); display:flex; align-items:center; gap:8px;">
                        <span class="live-pulse-indicator"></span>
                        <span>Live analytics</span>
                    </div>
                </div>
                <div class="analytics-metrics-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                    <div class="metric-spark-box" style="background:var(--fb-hover); border-radius:10px; padding:10px 12px;">
                        <div class="metric-spark-label" style="font-size:11px; font-weight:700; color:var(--fb-text-secondary);">Profile Views</div>
                        <svg width="100%" height="24" viewBox="0 0 80 24" fill="none">
                            <path d="M2 18 L18 8 L34 16 L50 6 L66 12 L78 2" stroke="#3b82f6" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="metric-spark-val" style="font-size:16px; font-weight:800; color:var(--fb-text-primary);">+{{ $analytics['views_today'] ?? 142 }}</div>
                    </div>
                    <div class="metric-spark-box" style="background:var(--fb-hover); border-radius:10px; padding:10px 12px;">
                        <div class="metric-spark-label" style="font-size:11px; font-weight:700; color:var(--fb-text-secondary);">Engagements</div>
                        <svg width="100%" height="24" viewBox="0 0 80 24" fill="none">
                            <path d="M2 20 L20 14 L38 18 L56 10 L78 4" stroke="#10b981" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="metric-spark-val" style="font-size:16px; font-weight:800; color:var(--fb-text-primary);">+{{ $analytics['engagements_count'] ?? 89 }}</div>
                    </div>
                </div>
                <div class="telemetry-dots-row" style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; background:var(--fb-hover); border-radius:10px;">
                    <div style="font-size:11px; font-weight:700; color:var(--fb-text-secondary);">Interactive dots</div>
                    <div class="interactive-dots-matrix" style="display:flex; gap:6px;">
                        <span class="dot-circle"></span>
                        <span class="dot-circle"></span>
                        <span class="dot-circle"></span>
                        <span class="dot-circle"></span>
                    </div>
                </div>
            </div>

            <!-- Intro Card -->
            <div class="fb-card" id="introBioCard">
                <div class="card-header-bar">
                    <span class="card-header-title">পরিচিতি</span>
                </div>

                @if(!empty($profile['bio']))
                    <div class="intro-bio" id="introBioDisplay">{{ $profile['bio'] }}</div>
                @elseif($isOwner)
                    <div class="intro-bio" id="introBioDisplay" style="color:var(--fb-text-secondary);font-style:italic;">
                        আপনার সম্পর্কে সংক্ষেপে কিছু লিখুন...
                    </div>
                @endif

                @if($isOwner)
                    <!-- Facebook-Style Inline Bio Editor -->
                    <div id="inlineBioEditor" style="display:none;margin-bottom:12px;">
                        <textarea id="inlineBioInput" class="form-control" maxlength="255" oninput="updateBioCharCount(this.value.length)" style="min-height:75px;font-size:14px;resize:none;" placeholder="নিজের সম্পর্কে সংক্ষেপে লিখুন...">{{ $profile['bio'] ?? '' }}</textarea>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;font-size:12px;color:var(--fb-text-secondary);">
                            <span id="inlineBioCharCount">{{ 255 - strlen($profile['bio'] ?? '') }} অক্ষর বাকি</span>
                            <div style="display:flex;gap:6px;">
                                <button type="button" class="fb-btn fb-btn-secondary" style="padding:4px 10px;font-size:12px;" onclick="cancelInlineBio()">বাতিল</button>
                                <button type="button" class="fb-btn fb-btn-primary" style="padding:4px 12px;font-size:12px;" id="saveInlineBioBtn" onclick="saveInlineBio()">সংরক্ষণ</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="fb-btn fb-btn-secondary" id="openInlineBioBtn" style="width:100%;margin-bottom:12px;font-size:13px;padding:6px;display:inline-flex;align-items:center;justify-content:center;gap:6px;" onclick="toggleInlineBio()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                        <span>{{ !empty($profile['bio']) ? 'বায়ো সম্পাদনা' : 'বায়ো যোগ করুন' }}</span>
                    </button>
                @endif

                @if(!empty($profile['work']))
                    <div class="intro-item">
                        <span class="intro-item-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        </span>
                        <span>কর্মক্ষেত্র: <strong>{{ $profile['work'] }}</strong></span>
                    </div>
                @endif

                @if(!empty($profile['education']))
                    <div class="intro-item">
                        <span class="intro-item-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                        </span>
                        <span>শিক্ষা: <strong>{{ $profile['education'] }}</strong></span>
                    </div>
                @endif

                @if(!empty($profile['city']))
                    <div class="intro-item">
                        <span class="intro-item-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        </span>
                        <span>বর্তমান শহর: <strong>{{ $profile['city'] }}</strong></span>
                    </div>
                @endif

                @if(!empty($profile['hometown']))
                    <div class="intro-item">
                        <span class="intro-item-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        </span>
                        <span>নিজ শহর: <strong>{{ $profile['hometown'] }}</strong></span>
                    </div>
                @endif

                @if(!empty($profile['relationship_status']))
                    <div class="intro-item">
                        <span class="intro-item-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#e41e3f"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        </span>
                        <span>সম্পর্ক: <strong>{{ $profile['relationship_status'] }}</strong></span>
                    </div>
                @endif

                @if(!empty($profile['website']))
                    <div class="intro-item">
                        <span class="intro-item-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        </span>
                        <span>ওয়েবসাইট: <a href="{{ $profile['website'] }}" target="_blank" rel="noopener">{{ $profile['website'] }}</a></span>
                    </div>
                @endif

                @if(!empty($profile['sections']['skills']) && count($profile['sections']['skills']) > 0)
                    <div style="margin-bottom:12px;padding-top:6px;border-top:1px solid var(--fb-divider);">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                            <div style="font-size:13px;color:var(--fb-text-secondary);font-weight:600;">দক্ষতাসমূহ:</div>
                            @if($isOwner)
                                <button type="button" class="about-info-edit-btn" onclick="openEditProfileWithTab('skills_lang')" title="দক্ষতা সম্পাদনা">✏️</button>
                            @endif
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach(array_slice($profile['sections']['skills'], 0, 5) as $sk)
                                <span style="background:var(--fb-blue-light);color:var(--fb-primary);padding:2px 8px;border-radius:12px;font-size:12px;font-weight:600;">
                                    {{ $sk['name'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Hobbies Section in Intro Card -->
                @php
                    $introHobbies = $profile['hobbies'] ?? [];
                    if (is_string($introHobbies)) $introHobbies = json_decode($introHobbies, true) ?: [];
                @endphp
                @if(!empty($introHobbies) && count($introHobbies) > 0)
                    <div style="margin-bottom:12px;padding-top:8px;border-top:1px solid var(--fb-divider);">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                            <div style="font-size:13px;color:var(--fb-text-secondary);font-weight:700;">🎯 শখ ও আগ্রহ:</div>
                            @if($isOwner)
                                <button type="button" class="about-info-edit-btn" onclick="openEditProfileWithTab('skills_lang')" title="শখ সম্পাদনা">✏️</button>
                            @endif
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach($introHobbies as $hb)
                                <span style="display:inline-flex;align-items:center;gap:4px;background:var(--fb-bg);border:1px solid var(--fb-border);color:var(--fb-text-primary);padding:3px 10px;border-radius:14px;font-size:12px;font-weight:600;">
                                    <span>🎯</span> {{ $hb }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @elseif($isOwner)
                    <div style="margin-bottom:12px;padding-top:6px;border-top:1px solid var(--fb-divider);">
                        <button type="button" class="about-add-prompt" style="width:100%;font-size:13px;" onclick="openEditProfileWithTab('skills_lang')">
                            + শখ ও প্রিয় বিষয় যোগ করুন
                        </button>
                    </div>
                @endif

                <!-- Direct Connect & Social Links -->
                @php
                    $hasDirectConnect = !empty($profile['whatsapp']) || !empty($profile['messenger']) || !empty($profile['telegram']) || !empty($profile['signal']) || !empty($profile['portfolio']);
                    $hasSocialLinks = !empty($profile['sections']['social_links']) && count($profile['sections']['social_links']) > 0;
                @endphp

                @if($hasDirectConnect || $hasSocialLinks)
                    <div class="intro-connect-section" id="introConnectSection">
                        <div style="font-size:12px;color:var(--fb-text-secondary);font-weight:700;text-transform:uppercase;margin-bottom:4px;">যোগাযোগ ও সোশ্যাল লিংক:</div>

                        @if(!empty($profile['whatsapp']))
                            @php
                                $cleanWa = preg_replace('/[^0-9]/', '', $profile['whatsapp']);
                            @endphp
                            <a href="https://wa.me/{{ $cleanWa }}" target="_blank" rel="noopener" class="connect-action-pill wa-pill" id="introWaLink" title="WhatsApp-এ চ্যাট করুন">
                                <span class="connect-pill-icon">💬</span>
                                <span>হোয়াটসঅ্যাপ: <strong>{{ $profile['whatsapp'] }}</strong></span>
                            </a>
                        @endif

                        @if(!empty($profile['messenger']))
                            @php
                                $cleanMessenger = ltrim($profile['messenger'], '@');
                            @endphp
                            <a href="https://m.me/{{ $cleanMessenger }}" target="_blank" rel="noopener" class="connect-action-pill messenger-pill" id="introMessengerLink" title="মেসেঞ্জারে বার্তা পাঠান">
                                <span class="connect-pill-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                </span>
                                <span>মেসেঞ্জার: <strong>{{ $profile['messenger'] }}</strong></span>
                            </a>
                        @endif

                        @if(!empty($profile['telegram']))
                            @php
                                $cleanTg = ltrim($profile['telegram'], '@');
                            @endphp
                            <a href="https://t.me/{{ $cleanTg }}" target="_blank" rel="noopener" class="connect-action-pill telegram-pill" id="introTelegramLink" title="টেলিগ্রামে নক করুন">
                                <span class="connect-pill-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                </span>
                                <span>টেলিগ্রাম: <strong>{{ $profile['telegram'] }}</strong></span>
                            </a>
                        @endif

                        @if(!empty($profile['signal']))
                            <div class="connect-action-pill signal-pill" id="introSignalLink" title="সিগন্যাল যোগাযোগ">
                                <span class="connect-pill-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                </span>
                                <span>সিগন্যাল: <strong>{{ $profile['signal'] }}</strong></span>
                            </div>
                        @endif

                        @if(!empty($profile['portfolio']))
                            <a href="{{ $profile['portfolio'] }}" target="_blank" rel="noopener" class="connect-action-pill portfolio-pill" id="introPortfolioLink" title="পোর্টফোলিও ওয়েবসাইট">
                                <span class="connect-pill-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                                </span>
                                <span>পোর্টফোলিও: <strong>{{ parse_url($profile['portfolio'], PHP_URL_HOST) ?: $profile['portfolio'] }}</strong></span>
                            </a>
                        @endif

                        @if($hasSocialLinks)
                            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;">
                                @foreach($profile['sections']['social_links'] as $sl)
                                    @php
                                        $platformLower = strtolower($sl['platform'] ?? '');
                                    @endphp
                                    <a href="{{ $sl['url'] }}" target="_blank" rel="noopener" class="social-brand-pill social-{{ $platformLower }}" style="display:inline-flex;align-items:center;gap:4px;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                        <span>{{ ucfirst($sl['platform']) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @elseif($isOwner)
                    <div style="margin-bottom:12px;padding-top:6px;border-top:1px solid var(--fb-divider);">
                        <button type="button" class="about-add-prompt" style="width:100%;font-size:13px;" onclick="openEditProfileWithTab('social')">
                            + সোশ্যাল ও যোগাযোগ মাধ্যম যোগ করুন
                        </button>
                    </div>
                @endif

                <div class="intro-item">
                    <span class="intro-item-icon">📅</span>
                    <span>যোগদান: {{ $profile['member_since'] ?? 'তথ্য নেই' }}</span>
                </div>

                @if($isOwner)
                    <button class="fb-btn fb-btn-secondary" style="width:100%;margin-top:8px;" onclick="openEditProfileModal()">
                        পরিচিতি সম্পাদনা
                    </button>
                @endif
            </div>

            <!-- Featured Collection Card -->
            @if((isset($highlights) && $highlights->isNotEmpty()) || $isOwner)
                <div class="fb-card featured-section-card" id="featuredStoriesCard">
                    <div class="card-header-bar">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span class="card-header-title">ফিচারড</span>
                            @if(isset($highlights) && $highlights->isNotEmpty())
                                <span style="font-size:12px;color:var(--fb-text-secondary);font-weight:600;">({{ $highlights->count() }})</span>
                            @endif
                        </div>
                        @if($isOwner)
                            <button type="button" class="card-header-link" style="background:none;border:none;cursor:pointer;font-family:inherit;padding:0;font-size:13px;" onclick="openCreateHighlightModal()">
                                + নতুন যোগ করুন
                            </button>
                        @endif
                    </div>

                    @if(isset($highlights) && $highlights->isNotEmpty())
                        <div class="featured-cards-grid">
                            @foreach($highlights as $hl)
                                @php
                                    $hlItems = $hl->items ? $hl->items->map(function($it) {
                                        return [
                                            'id' => $it->id,
                                            'media_path' => $it->media_path ?: ($it->story ? $it->story->media_url : null),
                                            'media_type' => $it->media_type ?? 'image',
                                        ];
                                    })->filter(function($it) {
                                        return !empty($it['media_path']);
                                    })->values() : collect();

                                    if ($hlItems->isEmpty()) {
                                        $hlItems = collect([[
                                            'id' => 0,
                                            'media_path' => $hl->cover_image_path ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600',
                                            'media_type' => 'image',
                                        ]]);
                                    }
                                    $coverUrl = $hl->cover_image_path ?: ($hlItems->first()['media_path'] ?? 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400');
                                @endphp
                                <div class="featured-card-item" onclick='viewHighlight({{ $hl->id }}, @json($hl->title), @json($hl->cover_image_path), @json($hlItems))' title="{{ $hl->title }}">
                                    <img src="{{ $coverUrl }}" alt="{{ $hl->title }}" class="featured-card-img" loading="lazy">
                                    <div class="featured-card-gradient"></div>
                                    @if($isOwner)
                                        <button type="button" class="featured-card-del-btn" onclick="deleteHighlightItem({{ $hl->id }}, event)" title="কালেকশন মুছুন">
                                            ✕
                                        </button>
                                    @endif
                                    <div class="featured-card-content">
                                        <div class="featured-card-title">{{ $hl->title }}</div>
                                        <div class="featured-card-count">{{ $hlItems->count() }} টি আইটেম</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($isOwner)
                        <div class="featured-empty-box">
                            <div class="featured-empty-icon">🌟</div>
                            <div class="featured-empty-title">আপনার প্রিয় মুহূর্তগুলো ফিচার করুন</div>
                            <div class="featured-empty-text">আপনার প্রোফাইলে স্থায়ীভাবে প্রিয় ছবি ও স্টোরিগুলো দর্শকদের সাথে শেয়ার করতে কালেকশন তৈরি করুন।</div>
                            <button type="button" class="fb-btn fb-btn-secondary" style="width:100%;margin-top:10px;font-weight:600;" onclick="openCreateHighlightModal()">
                                + ফিচারড কালেকশন তৈরি করুন
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Photos Card Preview -->
            <div class="fb-card" id="sidebarPhotosCard">
                <div class="card-header-bar">
                    <span class="card-header-title">ছবি</span>
                    <a href="#photos" class="card-header-link" onclick="switchTab('photos')">সব ছবি দেখুন</a>
                </div>
                <div class="preview-grid-3x3">
                    @forelse(array_slice($photos, 0, 9) as $photo)
                        <div class="preview-photo-item" style="cursor:pointer;" onclick="openPhotoTheater('{{ $photo['url'] ?? '' }}', '{{ addslashes($profile['name']) }}', 'প্রোফাইল ফটো গ্যালারি', 'সম্প্রতি')">
                            <img src="{{ $photo['url'] ?? '' }}" alt="User photo" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                        </div>
                    @empty
                        <div style="grid-column:span 3;text-align:center;padding:16px;color:var(--fb-text-secondary);font-size:13px;">
                            কোনো ছবি পাওয়া যায়নি
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Friends Card Preview -->
            <div class="fb-card" id="sidebarFriendsCard">
                <div class="card-header-bar">
                    <div>
                        <div class="card-header-title">বন্ধুরা</div>
                        <div style="font-size:13px;color:var(--fb-text-secondary);">{{ number_format($profile['friends_count']) }} জন বন্ধু</div>
                    </div>
                    <a href="#friends" class="card-header-link" onclick="switchTab('friends')">সব বন্ধু দেখুন</a>
                </div>
                <div class="preview-grid-3x3">
                    @forelse($friends as $friend)
                        @if($loop->index < 9)
                            <a href="{{ getUserProfileUrl($friend) }}" class="friend-preview-card">
                                <img src="{{ $friend['avatar_url'] ?? '/images/default-avatar.svg' }}"
                                     alt="{{ $friend['name'] }}"
                                     class="friend-photo"
                                     onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                <span class="friend-preview-name">{{ $friend['name'] }}</span>
                            </a>
                        @endif
                    @empty
                        <div style="grid-column:span 3;text-align:center;padding:16px;color:var(--fb-text-secondary);font-size:13px;">
                            এখনও কোনো বন্ধু নেই
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- ======================= RIGHT MAIN FEED / TAB CONTENT ======================= -->
        <div class="profile-right-col">

            <!-- TAB: POSTS -->
            <div id="tabContent-posts">
                @if($profile['locked_view'])
                    <!-- Restricted Locked View Notice -->
                    <div class="locked-timeline-placeholder">
                        <div class="locked-shield-big" style="display:flex;align-items:center;justify-content:center;color:var(--fb-primary);">
                            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </div>
                        <h3 style="font-size:20px;font-weight:700;color:var(--fb-text-primary);margin-bottom:8px;">
                            {{ $profile['name'] }}-এর প্রোফাইল লক করা আছে
                        </h3>
                        <p style="color:var(--fb-text-secondary);max-width:440px;margin:0 auto 20px auto;font-size:15px;">
                            এই ব্যবহারকারী তার প্রোফাইল লক করে রেখেছেন। শুধুমাত্র বন্ধুদের সাথে শেয়ার করা পোস্ট, ফটো এবং স্টোরি দেখা সম্ভব।
                        </p>
                        <button class="fb-btn fb-btn-primary" style="display:inline-flex;align-items:center;gap:6px;" onclick="handleSendFriendRequest({{ $profile['id'] }})">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                            <span>বন্ধুত্বের অনুরোধ পাঠান</span>
                        </button>
                    </div>
                @else
                    <!-- Center Column Intro / About Card (As Shown in Mockup) -->
                    <div class="fb-card" style="margin-bottom:16px; border-radius:14px; padding:18px;">
                        <div style="font-size:16px; font-weight:800; margin-bottom:8px;">পরিচিতি</div>
                        <div style="font-size:13.5px; color:var(--fb-text-secondary); line-height:1.5; margin-bottom:10px;">
                            {{ $profile['bio'] ?: ($profile['headline'] ?: 'Lead System Administrator at Bondhoo Platform, Dhaka, Bangladesh') }}
                        </div>
                        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding-top:10px; border-top:1px solid var(--fb-divider);">
                            <div style="display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; color:var(--fb-text-secondary);">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span>{{ $profile['city'] ?? 'Location' }}</span>
                            </div>
                            <div style="display:inline-flex; align-items:center; gap:8px;">
                                <span style="font-size:11px; font-weight:700; color:var(--fb-text-secondary);">Page engagement 8 metrics</span>
                                <svg width="60" height="20" viewBox="0 0 60 20" fill="none">
                                    <path d="M2 15 L15 8 L30 14 L45 5 L58 12" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Post Composer (For Profile Owner) -->
                    @if($isOwner)
                        <div class="fb-card">
                            <div class="composer-top">
                                <img src="{{ $profile['avatar'] ?? '/images/default-avatar.svg' }}"
                                     alt="{{ $profile['name'] }}"
                                     class="composer-avatar"
                                     onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                <div class="composer-fake-input" onclick="openPostModal()">
                                    আপনার মনে কি চলছে, {{ explode(' ', $profile['name'])[0] }}?
                                </div>
                            </div>
                            <div class="composer-bottom">
                                <button class="composer-btn-action" onclick="openPostModal('photo')">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#45bd62" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    <span>ফটো / ভিডিও</span>
                                </button>
                                <button class="composer-btn-action" onclick="openPostModal('feeling')">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f7b125" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
                                    <span>অনুভূতি / অ্যাক্টিভিটি</span>
                                </button>
                                <button class="composer-btn-action" onclick="openLifeEventModal()">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e41e3f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                    <span>লাইফ ইভেন্ট</span>
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Timeline Posts Header Bar (Manage / Filters / List & Grid View) -->
                    <div class="timeline-manage-bar fb-card">
                        <div class="timeline-manage-title">পোস্টসমূহ</div>
                        <div class="timeline-manage-actions">
                            <button type="button" class="timeline-ctrl-btn" onclick="toggleTimelineFilterBar()" id="filterToggleBtn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                                <span>ফিল্টার</span>
                            </button>
                            @if($isOwner)
                                <button type="button" class="timeline-ctrl-btn" onclick="openManagePostsModal()" id="managePostsToggleBtn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                                    <span>পোস্ট পরিচালনা</span>
                                </button>
                            @endif
                            <div class="view-switch-group">
                                <button type="button" class="view-switch-btn active" id="btnListView" onclick="setTimelineViewMode('list')" title="তালিকা ভিউ (List)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                                </button>
                                <button type="button" class="view-switch-btn" id="btnGridView" onclick="setTimelineViewMode('grid')" title="গ্রিড ভিউ (Grid)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Filter Drawer Bar (Hidden by default) -->
                    <div class="timeline-filter-drawer fb-card" id="timelineFilterBar" style="display:none;">
                        <div class="filter-drawer-grid">
                            <div class="filter-drawer-field">
                                <label>বছর / সাল</label>
                                <select id="timelineFilterYear" onchange="applyTimelineFilter()">
                                    <option value="all">সকল বছর</option>
                                    @for($y = (int)date('Y'); $y >= 2020; $y--)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="filter-drawer-field">
                                <label>পোস্টের ধরন</label>
                                <select id="timelineFilterType" onchange="applyTimelineFilter()">
                                    <option value="all">সকল পোস্ট</option>
                                    <option value="life_event">লাইফ ইভেন্ট / মাইলস্টোন</option>
                                    <option value="media">ছবি / ভিডিও যুক্ত পোস্ট</option>
                                    <option value="text">স্ট্যাটাস / টেক্সট</option>
                                </select>
                            </div>
                            <div class="filter-drawer-field">
                                <label>গোপনীয়তা</label>
                                <select id="timelineFilterPrivacy" onchange="applyTimelineFilter()">
                                    <option value="all">সব গোপনীয়তা</option>
                                    <option value="public">পাবলিক</option>
                                    <option value="friends">বন্ধুরা</option>
                                    <option value="only_me">শুধুমাত্র আমি</option>
                                </select>
                            </div>
                        </div>
                        <div style="display:flex;justify-content:flex-end;gap:8px;">
                            <button type="button" class="timeline-ctrl-btn" onclick="resetTimelineFilter()" style="font-size:12px;padding:4px 10px;">
                                ✕ রিসেট ফিল্টার
                            </button>
                        </div>
                    </div>

                    <!-- Timeline Posts Stream -->
                    <div id="timelinePostsStream">
                        @forelse($timeline as $post)
                            <div class="post-card timeline-post-item" id="post-card-{{ $post->id }}"
                                 data-year="{{ $post->created_at?->format('Y') }}"
                                 data-type="{{ $post->type ?? 'text' }}"
                                 data-has-media="{{ ($post->media && $post->media->count() > 0) ? 'true' : 'false' }}"
                                 data-privacy="{{ $post->audience ?? 'public' }}"
                                 data-pinned="{{ $post->is_pinned ? 'true' : 'false' }}">
                                
                                @if($post->is_pinned)
                                    <div class="pinned-post-banner" id="pinned-banner-{{ $post->id }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="17" x2="12" y2="22"></line><path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.89A4 4 0 0 1 14 9V4h1a1 1 0 0 0 0-2H9a1 1 0 0 0 0 2h1v5a4 4 0 0 1-2.11 3.56l-1.78.89A2 2 0 0 0 5 15.24Z"></path></svg>
                                        <span>পিন করা পোস্ট</span>
                                    </div>
                                @endif

                                @if($post->type === 'life_event' || str_starts_with($post->feeling_activity ?? '', '🚩'))
                                    <div class="life-event-badge-banner">
                                        <div class="life-event-icon-circle">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e41e3f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                        </div>
                                        <div class="life-event-badge-title">{{ str_replace('🚩', '', $post->feeling_activity ?: 'লাইফ ইভেন্ট / মাইলস্টোন') }}</div>
                                        @if($post->location)
                                            <div class="life-event-location">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:-2px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                                {{ $post->location }}
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div class="post-header">
                                    <div class="post-author-block">
                                        <img src="{{ $profile['avatar'] ?? '/images/default-avatar.svg' }}"
                                             alt="{{ $profile['name'] }}"
                                             class="post-author-avatar"
                                             onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                        <div>
                                            <a href="{{ getUserProfileUrl($profile['username'] ?? $user) }}" class="post-author-name">
                                                {{ $profile['name'] }}
                                            </a>
                                            <div class="post-meta">
                                                <span>{{ $post->created_at?->diffForHumans() }}</span>
                                                <span>•</span>
                                                <span title="{{ $post->audience === 'public' ? 'পাবলিক' : ($post->audience === 'friends' ? 'বন্ধুরা' : 'শুধুমাত্র আমি') }}">
                                                    @if($post->audience === 'public')
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                                    @elseif($post->audience === 'friends')
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                                    @else
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="post-header-action-wrap">
                                        <button type="button" class="icon-circle-btn" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;" onclick="togglePostDropdown({{ $post->id }}, event)">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="2"></circle><circle cx="19" cy="12" r="2"></circle><circle cx="5" cy="12" r="2"></circle></svg>
                                        </button>
                                        <div class="post-options-dropdown" id="postDropdown-{{ $post->id }}">
                                            @if($isOwner)
                                                <button type="button" class="post-option-item" onclick="handleTogglePinPost({{ $post->id }})">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="17" x2="12" y2="22"></line><path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.89A4 4 0 0 1 14 9V4h1a1 1 0 0 0 0-2H9a1 1 0 0 0 0 2h1v5a4 4 0 0 1-2.11 3.56l-1.78.89A2 2 0 0 0 5 15.24Z"></path></svg>
                                                    <span id="pinText-{{ $post->id }}">{{ $post->is_pinned ? 'প্রোফাইল থেকে আনপিন করুন' : 'প্রোফাইলে পিন করুন' }}</span>
                                                </button>
                                                <button type="button" class="post-option-item" onclick="handleToggleCommentsPost({{ $post->id }})">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                                    <span>{{ $post->comments_disabled ? 'মন্তব্য চালু করুন' : 'মন্তব্য বন্ধ করুন' }}</span>
                                                </button>
                                                <button type="button" class="post-option-item danger-item" onclick="handleDeletePost({{ $post->id }})">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                    <span>পোস্ট মুছে ফেলুন</span>
                                                </button>
                                                <div style="height:1px;background:var(--fb-border);margin:4px 0;"></div>
                                            @else
                                                <button type="button" class="post-option-item" onclick="handleSavePostAction({{ $post->id }}, '{{ addslashes(urlencode($post->content ?? '')) }}')">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                                                    <span>পোস্ট সংরক্ষণ করুন</span>
                                                </button>
                                                <button type="button" class="post-option-item" onclick="handleHidePostAction({{ $post->id }})">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                                    <span>পোস্ট লুকান</span>
                                                </button>
                                                <button type="button" class="post-option-item danger-item" onclick="handleReportPostAction({{ $post->id }})">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                                    <span>রিপোর্ট করুন</span>
                                                </button>
                                                <div style="height:1px;background:var(--fb-border);margin:4px 0;"></div>
                                            @endif
                                            <button type="button" class="post-option-item" onclick="handleCopyPostLink({{ $post->id }})">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                                                <span>পোস্টের লিংক কপি করুন</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                @if(!empty($post->content))
                                    <div class="post-content-body">{{ $post->content }}</div>
                                @endif

                                @if($post->media && $post->media->count() > 0)
                                    <div class="post-media-container" style="cursor:pointer;" onclick="openPhotoTheater('{{ $post->media->first()->media_url }}', '{{ addslashes($post->user->name ?? $profile['name']) }}', '{{ addslashes(Str::limit($post->content ?? '', 120)) }}', '{{ $post->created_at->diffForHumans() }}', {{ $post->id }})">
                                        <img src="{{ $post->media->first()->media_url }}" alt="Post attachment" onerror="this.style.display='none';">
                                    </div>
                                @endif

                                <div class="post-stats-bar">
                                    <div class="reactions-count-badge" style="display:flex;align-items:center;gap:4px;">
                                        <div style="display:inline-flex;align-items:center;">
                                            <span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;background:#1877f2;border-radius:50%;color:#fff;margin-right:-4px;box-shadow:0 1px 2px rgba(0,0,0,0.2);">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="#ffffff"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
                                            </span>
                                            <span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;background:#f3425f;border-radius:50%;color:#fff;box-shadow:0 1px 2px rgba(0,0,0,0.2);">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="#ffffff"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                            </span>
                                        </div>
                                        <span id="reactions-count-{{ $post->id }}">{{ $post->reactions_count ?? 0 }}</span>
                                    </div>
                                    <div style="font-size: 13px; color: var(--fb-text-secondary); display: flex; align-items: center; gap: 6px;">
                                        <span><span id="comments-count-{{ $post->id }}">{{ $post->comments_count ?? 0 }}</span> টি মন্তব্য</span>
                                        <span>•</span>
                                        <span><span id="shares-count-{{ $post->id }}">{{ $post->shares_count ?? 0 }}</span> টি শেয়ার</span>
                                    </div>
                                </div>

                                <div class="post-actions-row">
                                    <button class="post-action-btn" id="like-btn-{{ $post->id }}" onclick="toggleReaction({{ $post->id }})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
                                        <span>লাইক</span>
                                    </button>
                                    <button class="post-action-btn" onclick="toggleCommentsSection({{ $post->id }})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                        <span>মন্তব্য</span>
                                    </button>
                                    <button class="post-action-btn" onclick="sharePost({{ $post->id }})">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>
                                        <span>শেয়ার</span>
                                    </button>
                                </div>

                                <!-- Inline Comments Container -->
                                <div id="comments-container-{{ $post->id }}" style="display: none; padding-top: 12px; border-top: 1px solid var(--fb-border); margin-top: 8px;">
                                    <div id="comments-list-{{ $post->id }}" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px;"></div>
                                    <form onsubmit="submitPostComment(event, {{ $post->id }})" style="display: flex; gap: 8px; align-items: center;">
                                        <input type="text" name="comment_text" id="comment-input-{{ $post->id }}" class="form-control" placeholder="একটি মন্তব্য লিখুন..." style="border-radius: 20px; font-size: 13px; padding: 6px 14px; height: 36px;" required>
                                        <button type="submit" class="fb-btn fb-btn-primary" style="border-radius: 20px; padding: 0 16px; height: 36px; font-size: 13px;">পাঠান</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="fb-card" style="text-align:center;padding:40px;color:var(--fb-text-secondary);">
                                <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;background:var(--fb-hover);border-radius:50%;margin-bottom:12px;color:var(--fb-text-secondary);">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                </div>
                                <h3 style="font-size:18px;color:var(--fb-text-primary);margin-bottom:6px;">এখনও কোনো পোস্ট নেই</h3>
                                <p style="font-size:14px;">টাইমলাইনে প্রথম পোস্টটি প্রকাশ করুন।</p>
                            </div>
                        @endforelse

                        <!-- Empty Filter Results Card -->
                        <div id="noFilterResultsCard" class="fb-card" style="display:none;text-align:center;padding:36px 20px;color:var(--fb-text-secondary);">
                            <div style="font-size:36px;margin-bottom:8px;">🔍</div>
                            <h4 style="font-size:16px;color:var(--fb-text-primary);margin-bottom:4px;">কোনো পোস্ট পাওয়া যায়নি</h4>
                            <p style="font-size:13px;margin-bottom:12px;">আপনার নির্বাচিত ফিল্টারে কোনো পোস্ট মেলেনি।</p>
                            <button type="button" class="fb-btn fb-btn-secondary" onclick="resetTimelineFilter()" style="display:inline-flex;padding:6px 16px;font-size:13px;">সব পোস্ট দেখুন</button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- TAB: ABOUT (Facebook Sub-Tabs Layout) -->
            <div id="tabContent-about" style="display:none;">
                <div class="about-card-container">
                    <!-- Left Sub-Tabs Navigation -->
                    <div class="about-sidebar">
                        <div class="about-sidebar-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--fb-primary);flex-shrink:0;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <span>পরিচিতি ক্যাটাগরি</span>
                        </div>
                        <button type="button" class="about-subtab-btn active" onclick="switchAboutSubtab('overview', this)">
                            <span class="about-subtab-icon">
                                <svg class="about-subtab-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <span>ওভারভিউ</span>
                        </button>
                        <button type="button" class="about-subtab-btn" onclick="switchAboutSubtab('work_edu', this)">
                            <span class="about-subtab-icon">
                                <svg class="about-subtab-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                </svg>
                            </span>
                            <span>কর্ম ও শিক্ষা</span>
                        </button>
                        <button type="button" class="about-subtab-btn" onclick="switchAboutSubtab('places', this)">
                            <span class="about-subtab-icon">
                                <svg class="about-subtab-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </span>
                            <span>বসবাসের স্থান</span>
                        </button>
                        <button type="button" class="about-subtab-btn" onclick="switchAboutSubtab('contact_basic', this)">
                            <span class="about-subtab-icon">
                                <svg class="about-subtab-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                            </span>
                            <span>যোগাযোগ ও মৌলিক তথ্য</span>
                        </button>
                        <button type="button" class="about-subtab-btn" onclick="switchAboutSubtab('family', this)">
                            <span class="about-subtab-icon">
                                <svg class="about-subtab-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </span>
                            <span>পরিবার ও সম্পর্ক</span>
                        </button>
                        <button type="button" class="about-subtab-btn" onclick="switchAboutSubtab('hobbies', this)">
                            <span class="about-subtab-icon">
                                <svg class="about-subtab-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.65-.75 1.65-1.69 0-.44-.18-.84-.44-1.13-.29-.29-.44-.65-.44-1.12a1.64 1.64 0 0 1 1.67-1.67h2c3.05 0 5.56-2.5 5.56-5.55C22 6.01 17.5 2 12 2z"></path>
                                    <circle cx="7.5" cy="11.5" r="1.5" fill="currentColor"></circle>
                                    <circle cx="12" cy="7.5" r="1.5" fill="currentColor"></circle>
                                    <circle cx="16.5" cy="11.5" r="1.5" fill="currentColor"></circle>
                                </svg>
                            </span>
                            <span>শখ ও প্রিয় বিষয়</span>
                        </button>
                        <button type="button" class="about-subtab-btn" onclick="switchAboutSubtab('skills', this)">
                            <span class="about-subtab-icon">
                                <svg class="about-subtab-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="8" r="6"></circle>
                                    <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                                </svg>
                            </span>
                            <span>দক্ষতা ও পারদর্শিতা</span>
                        </button>
                    </div>

                    <!-- Right Panels -->
                    <div class="about-content-panel">
                        <!-- 1. OVERVIEW PANEL -->
                        <div id="about-panel-overview" style="display:block;">
                            <div class="about-section-header">
                                <span class="about-section-title">একনজরে পরিচিতি</span>
                                @if($isOwner)
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <button type="button" class="fb-btn fb-btn-secondary" style="font-size:13px;padding:6px 14px;" onclick="openEditProfileModal()">
                                            ✏️ সম্পূর্ণ প্রোফাইল সম্পাদনা
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <!-- Bio / About -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">💬</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['bio']) || !empty($profile['about']))
                                        <div class="about-info-main">{{ $profile['bio'] ?: 'বায়ো যোগ করা নেই' }}</div>
                                        @if(!empty($profile['about']))
                                            <div class="about-info-sub" style="line-height:1.5;margin-top:4px;">{{ $profile['about'] }}</div>
                                        @endif
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">
                                            + সংক্ষিপ্ত বায়ো বা পরিচিতি যোগ করুন
                                        </button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">বায়ো বা পরিচিতির তথ্য নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && (!empty($profile['bio']) || !empty($profile['about'])))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Primary Work -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">💼</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['sections']['experiences']) && count($profile['sections']['experiences']) > 0)
                                        @php $topExp = $profile['sections']['experiences'][0]; @endphp
                                        <div class="about-info-main">
                                            <strong>{{ $topExp['job_title'] }}</strong> হিসাবে কর্মরত আছেন @ <strong>{{ $topExp['company_name'] }}</strong>
                                        </div>
                                        <div class="about-info-sub">
                                            {{ $topExp['employment_type'] ?? 'ফুল-টাইম' }} • {{ $topExp['location'] ?? 'অন-সাইট' }}
                                            @if($topExp['is_current'])(বর্তমান)@endif
                                        </div>
                                    @elseif(!empty($profile['work']))
                                        <div class="about-info-main">{{ $profile['work'] }}</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('work_edu')">
                                            + কর্মক্ষেত্র যোগ করুন
                                        </button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">কোনো কর্মসংস্থানের তথ্য নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && (!empty($profile['sections']['experiences']) || !empty($profile['work'])))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('work_edu')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Primary Education -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🎓</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['sections']['educations']) && count($profile['sections']['educations']) > 0)
                                        @php $topEdu = $profile['sections']['educations'][0]; @endphp
                                        <div class="about-info-main">
                                            <strong>{{ $topEdu['institution_name'] }}</strong> এ পড়াশোনা করেছেন
                                        </div>
                                        <div class="about-info-sub">
                                            {{ $topEdu['degree'] ?? '' }} {{ $topEdu['field_of_study'] ? '('.$topEdu['field_of_study'].')' : '' }}
                                            @if($topEdu['is_current'])• (চলমান)@endif
                                        </div>
                                    @elseif(!empty($profile['education']))
                                        <div class="about-info-main">{{ $profile['education'] }}</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('work_edu')">
                                            + স্কুল, কলেজ বা বিশ্ববিদ্যালয় যোগ করুন
                                        </button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">কোনো শিক্ষাগত তথ্য নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && (!empty($profile['sections']['educations']) || !empty($profile['education'])))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('work_edu')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Current City -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🏠</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['city']) || !empty($profile['location']))
                                        <div class="about-info-main">
                                            <strong>{{ $profile['city'] ?? $profile['location'] }}</strong> এ বসবাস করেন
                                        </div>
                                        <div class="about-info-sub">বর্তমান শহর</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">
                                            + বর্তমান শহর যোগ করুন
                                        </button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">বর্তমান শহরের তথ্য নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && (!empty($profile['city']) || !empty($profile['location'])))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Hometown -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">📍</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['hometown']))
                                        <div class="about-info-main">
                                            <strong>{{ $profile['hometown'] }}</strong> থেকে এসেছেন
                                        </div>
                                        <div class="about-info-sub">নিজ শহর / হোমটাউন</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">
                                            + নিজ শহর যোগ করুন
                                        </button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">নিজ শহরের তথ্য নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['hometown']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Relationship Status -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">❤️</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['relationship_status']))
                                        <div class="about-info-main">
                                            সম্পর্কের অবস্থা: <strong>{{ $profile['relationship_status'] }}</strong>
                                        </div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('basic')">
                                            + সম্পর্কের অবস্থা যোগ করুন
                                        </button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">সম্পর্কের অবস্থা উল্লেখ নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['relationship_status']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('basic')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Member Since -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">📅</div>
                                <div class="about-info-details">
                                    <div class="about-info-main">যোগদান করেছেন <strong>{{ $profile['member_since'] ?? 'তথ্য নেই' }}</strong>-এ</div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. WORK AND EDUCATION PANEL -->
                        <div id="about-panel-work_edu" style="display:none;">
                            <!-- Work Subsection -->
                            <div class="about-section-header">
                                <span class="about-section-title">💼 কর্মসংস্থান (Work)</span>
                                @if($isOwner)
                                    <button type="button" class="fb-btn fb-btn-secondary" style="font-size:13px;padding:6px 12px;" onclick="openEditProfileWithTab('work_edu')">
                                        + চাকরি যোগ করুন
                                    </button>
                                @endif
                            </div>

                            @if(!empty($profile['sections']['experiences']) && count($profile['sections']['experiences']) > 0)
                                @foreach($profile['sections']['experiences'] as $exp)
                                    <div class="about-info-item">
                                        <div class="about-info-icon-box">🏢</div>
                                        <div class="about-info-details">
                                            <div class="about-info-main">
                                                <strong>{{ $exp['job_title'] }}</strong> @ <strong>{{ $exp['company_name'] }}</strong>
                                            </div>
                                            <div class="about-info-sub">
                                                {{ $exp['employment_type'] ?? 'ফুল-টাইম' }} • {{ $exp['location'] ?? 'অন-সাইট' }}
                                                @if($exp['is_current']) • <span style="color:#059669;font-weight:600;">বর্তমান কর্মক্ষেত্র</span>@endif
                                            </div>
                                        </div>
                                        @if($isOwner)
                                            <button class="about-info-edit-btn" onclick="openEditProfileWithTab('work_edu')" title="সম্পাদনা">✏️</button>
                                        @endif
                                    </div>
                                @endforeach
                            @elseif(!empty($profile['work']))
                                <div class="about-info-item">
                                    <div class="about-info-icon-box">🏢</div>
                                    <div class="about-info-details">
                                        <div class="about-info-main">{{ $profile['work'] }}</div>
                                    </div>
                                    @if($isOwner)
                                        <button class="about-info-edit-btn" onclick="openEditProfileWithTab('work_edu')" title="সম্পাদনা">✏️</button>
                                    @endif
                                </div>
                            @else
                                @if($isOwner)
                                    <div style="padding:14px 0;">
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('work_edu')">
                                            + নতুন কর্মক্ষেত্র বা চাকরি যোগ করুন
                                        </button>
                                    </div>
                                @else
                                    <div style="padding:16px 0;color:var(--fb-text-secondary);font-size:14px;">
                                        কোনো কর্মসংস্থানের তথ্য যোগ করা হয়নি।
                                    </div>
                                @endif
                            @endif

                            <!-- Education Subsection -->
                            <div class="about-section-header" style="margin-top:28px;">
                                <span class="about-section-title">🎓 শিক্ষাগত যোগ্যতা (Education)</span>
                                @if($isOwner)
                                    <button type="button" class="fb-btn fb-btn-secondary" style="font-size:13px;padding:6px 12px;" onclick="openEditProfileWithTab('work_edu')">
                                        + শিক্ষা প্রতিষ্ঠান যোগ করুন
                                    </button>
                                @endif
                            </div>

                            @if(!empty($profile['sections']['educations']) && count($profile['sections']['educations']) > 0)
                                @foreach($profile['sections']['educations'] as $edu)
                                    <div class="about-info-item">
                                        <div class="about-info-icon-box">🏫</div>
                                        <div class="about-info-details">
                                            <div class="about-info-main">
                                                <strong>{{ $edu['institution_name'] }}</strong>
                                            </div>
                                            <div class="about-info-sub">
                                                {{ $edu['degree'] ?? '' }} {{ $edu['field_of_study'] ? '('.$edu['field_of_study'].')' : '' }}
                                                @if($edu['is_current']) • <span style="color:#059669;font-weight:600;">চলমান শিক্ষার্থী</span>@endif
                                            </div>
                                        </div>
                                        @if($isOwner)
                                            <button class="about-info-edit-btn" onclick="openEditProfileWithTab('work_edu')" title="সম্পাদনা">✏️</button>
                                        @endif
                                    </div>
                                @endforeach
                            @elseif(!empty($profile['education']))
                                <div class="about-info-item">
                                    <div class="about-info-icon-box">🏫</div>
                                    <div class="about-info-details">
                                        <div class="about-info-main">{{ $profile['education'] }}</div>
                                    </div>
                                    @if($isOwner)
                                        <button class="about-info-edit-btn" onclick="openEditProfileWithTab('work_edu')" title="সম্পাদনা">✏️</button>
                                    @endif
                                </div>
                            @else
                                @if($isOwner)
                                    <div style="padding:14px 0;">
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('work_edu')">
                                            + স্কুল, কলেজ বা বিশ্ববিদ্যালয় যোগ করুন
                                        </button>
                                    </div>
                                @else
                                    <div style="padding:16px 0;color:var(--fb-text-secondary);font-size:14px;">
                                        কোনো শিক্ষাপ্রতিষ্ঠানের তথ্য যোগ করা হয়নি।
                                    </div>
                                @endif
                            @endif
                        </div>

                        <!-- 3. PLACES LIVED PANEL -->
                        <div id="about-panel-places" style="display:none;">
                            <div class="about-section-header">
                                <span class="about-section-title">📍 বসবাসের স্থান (Places Lived)</span>
                                @if($isOwner)
                                    <button class="fb-btn fb-btn-secondary" style="font-size:13px;padding:6px 12px;" onclick="openEditProfileWithTab('about')">
                                        ✏️ স্থান সম্পাদনা
                                    </button>
                                @endif
                            </div>

                            <!-- Current City -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🏠</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['city']) || !empty($profile['location']))
                                        <div class="about-info-main"><strong>{{ $profile['city'] ?? $profile['location'] }}</strong></div>
                                        <div class="about-info-sub">বর্তমান শহর (Current City)</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">+ বর্তমান শহর যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">বর্তমান শহর দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && (!empty($profile['city']) || !empty($profile['location'])))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Hometown -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">📍</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['hometown']))
                                        <div class="about-info-main"><strong>{{ $profile['hometown'] }}</strong></div>
                                        <div class="about-info-sub">নিজ শহর / জন্মস্থান (Hometown)</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">+ নিজ শহর যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">নিজ শহর দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['hometown']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Division / District / Address -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🗺️</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['division']) || !empty($profile['district']) || !empty($profile['upazila']) || !empty($profile['address']))
                                        <div class="about-info-main">
                                            {{ implode(', ', array_filter([$profile['upazila'] ?? null, $profile['district'] ?? null, $profile['division'] ?? null])) }}
                                        </div>
                                        @if(!empty($profile['address']))
                                            <div class="about-info-sub">পূর্ণ ঠিকানা: {{ $profile['address'] }}</div>
                                        @endif
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">+ বিভাগ, জেলা ও ঠিকানা যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">ঠিকানার বিস্তারিত তথ্য দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && (!empty($profile['division']) || !empty($profile['district']) || !empty($profile['upazila']) || !empty($profile['address'])))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>
                        </div>

                        <!-- 4. CONTACT AND BASIC INFO PANEL -->
                        <div id="about-panel-contact_basic" style="display:none;">
                            <!-- Contact Info -->
                            <div class="about-section-header">
                                <span class="about-section-title">📞 যোগাযোগের তথ্য (Contact Info)</span>
                                @if($isOwner)
                                    <button class="fb-btn fb-btn-secondary" style="font-size:13px;padding:6px 12px;" onclick="openEditProfileWithTab('about')">
                                        ✏️ যোগাযোগ সম্পাদনা
                                    </button>
                                @endif
                            </div>

                            <!-- Phone -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">📱</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['phone']))
                                        <div class="about-info-main">{{ $profile['phone'] }}</div>
                                        <div class="about-info-sub">মোবাইল নম্বর</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">+ মোবাইল নম্বর যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">মোবাইল নম্বর দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['phone']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Website -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🌐</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['website']))
                                        <div class="about-info-main">
                                            <a href="{{ $profile['website'] }}" target="_blank" rel="noopener" style="color:var(--fb-primary);text-decoration:none;">{{ $profile['website'] }}</a>
                                        </div>
                                        <div class="about-info-sub">ওয়েবসাইট</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">+ ওয়েবসাইট যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">ওয়েবসাইট দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['website']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Portfolio -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">💼</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['portfolio']))
                                        <div class="about-info-main">
                                            <a href="{{ $profile['portfolio'] }}" target="_blank" rel="noopener" style="color:var(--fb-primary);text-decoration:none;">{{ $profile['portfolio'] }}</a>
                                        </div>
                                        <div class="about-info-sub">পোর্টফোলিও লিংক</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">+ পোর্টফোলিও লিংক যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">পোর্টফোলিও দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['portfolio']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- WhatsApp -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box" style="color:#25d366;">💬</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['whatsapp']))
                                        <div class="about-info-main">
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $profile['whatsapp']) }}" target="_blank" rel="noopener" style="color:#25d366;text-decoration:none;font-weight:700;">{{ $profile['whatsapp'] }}</a>
                                        </div>
                                        <div class="about-info-sub">হোয়াটসঅ্যাপ (WhatsApp)</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">+ হোয়াটসঅ্যাপ নম্বর যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">হোয়াটসঅ্যাপ নম্বর দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['whatsapp']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Telegram -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box" style="color:#0088cc;">✈️</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['telegram']))
                                        <div class="about-info-main">
                                            <a href="https://t.me/{{ ltrim($profile['telegram'], '@') }}" target="_blank" rel="noopener" style="color:#0088cc;text-decoration:none;font-weight:700;">{{ $profile['telegram'] }}</a>
                                        </div>
                                        <div class="about-info-sub">টেলিগ্রাম (Telegram)</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">+ টেলিগ্রাম আইডি যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">টেলিগ্রাম দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['telegram']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Messenger -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box" style="color:#0084ff;">⚡</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['messenger']))
                                        <div class="about-info-main">
                                            <a href="https://m.me/{{ ltrim($profile['messenger'], '@') }}" target="_blank" rel="noopener" style="color:#0084ff;text-decoration:none;font-weight:700;">{{ $profile['messenger'] }}</a>
                                        </div>
                                        <div class="about-info-sub">মেসেঞ্জার (Messenger)</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('about')">+ ফেসবুক মেসেঞ্জার হ্যান্ডেল যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">মেসেঞ্জার দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['messenger']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('about')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Social Links -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🔗</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['sections']['social_links']) && count($profile['sections']['social_links']) > 0)
                                        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:2px;">
                                            @foreach($profile['sections']['social_links'] as $sl)
                                                <a href="{{ $sl['url'] }}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:16px;background:var(--fb-hover);color:var(--fb-primary);font-size:13px;font-weight:600;text-decoration:none;">
                                                    🔗 {{ ucfirst($sl['platform']) }}
                                                </a>
                                            @endforeach
                                        </div>
                                        <div class="about-info-sub" style="margin-top:6px;">সোশ্যাল মিডিয়া প্রোফাইলসমূহ</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('social')">+ সোশ্যাল মিডিয়া লিংক যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">কোনো সোশ্যাল লিংক নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['sections']['social_links']) && count($profile['sections']['social_links']) > 0)
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('social')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Basic Info -->
                            <div class="about-section-header" style="margin-top:32px;">
                                <span class="about-section-title">👤 মৌলিক তথ্য (Basic Info)</span>
                                @if($isOwner)
                                    <button class="fb-btn fb-btn-secondary" style="font-size:13px;padding:6px 12px;" onclick="openEditProfileWithTab('basic')">
                                        ✏️ মৌলিক তথ্য সম্পাদনা
                                    </button>
                                @endif
                            </div>

                            <!-- Gender -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">⚧</div>
                                <div class="about-info-details">
                                    <div class="about-info-main">
                                        @if(($profile['gender'] ?? '') === 'male') পুরুষ (Male)
                                        @elseif(($profile['gender'] ?? '') === 'female') নারী (Female)
                                        @elseif(($profile['gender'] ?? '') === 'other') অন্যান্য (Other)
                                        @else {{ ucfirst($profile['gender'] ?? 'তথ্য নেই') }}
                                        @endif
                                    </div>
                                    <div class="about-info-sub">লিঙ্গ (Gender)</div>
                                </div>
                                @if($isOwner)
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('basic')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Blood Group -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box" style="color:#e41e3f;">🩸</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['blood_group']))
                                        <div class="about-info-main">{{ $profile['blood_group'] }}</div>
                                        <div class="about-info-sub">রক্তের গ্রুপ (Blood Group)</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('basic')">+ রক্তের গ্রুপ যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">রক্তের গ্রুপ দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['blood_group']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('basic')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Religion -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🕌</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['religion']))
                                        <div class="about-info-main">{{ $profile['religion'] }}</div>
                                        <div class="about-info-sub">ধর্মীয় বিশ্বাস (Religious Views)</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('basic')">+ ধর্মীয় বিশ্বাস যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">ধর্মীয় বিশ্বাস দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['religion']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('basic')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Pronouns -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🏷️</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['pronouns']))
                                        <div class="about-info-main">{{ $profile['pronouns'] }}</div>
                                        <div class="about-info-sub">সর্বনাম (Pronouns)</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('basic')">+ সর্বনাম যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">সর্বনাম দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['pronouns']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('basic')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Category -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">⭐</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['category']))
                                        <div class="about-info-main">{{ $profile['category'] }}</div>
                                        <div class="about-info-sub">প্রোফাইল ক্যাটাগরি (Profile Category)</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('basic')">+ প্রোফাইল ক্যাটাগরি যোগ করুন</button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">ক্যাটাগরি দেওয়া নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['category']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('basic')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>
                        </div>

                        <!-- 5. FAMILY AND RELATIONSHIPS PANEL -->
                        <div id="about-panel-family" style="display:none;">
                            <div class="about-section-header">
                                <span class="about-section-title">❤️ পরিবার ও সম্পর্ক (Family and Relationships)</span>
                                @if($isOwner)
                                    <button class="fb-btn fb-btn-secondary" style="font-size:13px;padding:6px 12px;" onclick="openEditProfileWithTab('basic')">
                                        ✏️ সম্পাদনা
                                    </button>
                                @endif
                            </div>

                            <!-- Relationship Status -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box" style="color:#e41e3f;">❤️</div>
                                <div class="about-info-details">
                                    @if(!empty($profile['relationship_status']))
                                        <div class="about-info-main">
                                            <strong>{{ $profile['relationship_status'] }}</strong>
                                        </div>
                                        <div class="about-info-sub">বর্তমান সম্পর্কের অবস্থা</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('basic')">
                                            + আপনার সম্পর্কের অবস্থা যোগ করুন
                                        </button>
                                    @else
                                        <div class="about-info-main" style="color:var(--fb-text-secondary);font-weight:normal;">কোনো সম্পর্কের তথ্য প্রকাশ করা হয়নি</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($profile['relationship_status']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('basic')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Family Members -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">👨‍👩‍👧‍👦</div>
                                <div class="about-info-details">
                                    <div class="about-info-main">পারিবারিক সদস্য ও আত্মীয়স্বজন</div>
                                    <div class="about-info-sub">আপনার পরিবারের সদস্যদের সাথে যোগাযোগ যুক্ত রাখুন।</div>
                                    @if($isOwner)
                                        <div style="margin-top:6px;">
                                            <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('basic')">+ পারিবারিক সদস্য যোগ করুন</button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 6. HOBBIES AND FAVORITES PANEL -->
                        <div id="about-panel-hobbies" style="display:none;">
                            <div class="about-section-header">
                                <span class="about-section-title">🎨 শখ ও প্রিয় বিষয় (Hobbies & Interests)</span>
                                @if($isOwner)
                                    <button class="fb-btn fb-btn-secondary" style="font-size:13px;padding:6px 12px;" onclick="openEditProfileWithTab('skills_lang')">
                                        ✏️ সম্পাদনা
                                    </button>
                                @endif
                            </div>

                            <!-- Hobbies -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🎨</div>
                                <div class="about-info-details">
                                    <div class="about-info-main">শখসমূহ (Hobbies)</div>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
                                        @if(!empty($profile['hobbies']))
                                            @php $hList = is_array($profile['hobbies']) ? $profile['hobbies'] : explode(',', $profile['hobbies']); @endphp
                                            @foreach($hList as $h)
                                                @if(trim($h))
                                                    <span style="background:var(--fb-hover);padding:4px 12px;border-radius:16px;font-size:13px;font-weight:600;color:var(--fb-text-primary);">
                                                        🎯 {{ trim($h) }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        @elseif($isOwner)
                                            <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('skills_lang')">+ আপনার শখ যোগ করুন</button>
                                        @else
                                            <span style="font-size:13px;color:var(--fb-text-secondary);">তথ্য নেই</span>
                                        @endif
                                    </div>
                                </div>
                                @if($isOwner && !empty($profile['hobbies']))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('skills_lang')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Favorite Music -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🎵</div>
                                <div class="about-info-details">
                                    <div class="about-info-main">প্রিয় গান ও মিউজিক (Favorite Music)</div>
                                    @php
                                        $musicStr = !empty($profile['favorite_music']) ? (is_array($profile['favorite_music']) ? implode(', ', $profile['favorite_music']) : $profile['favorite_music']) : '';
                                    @endphp
                                    @if(!empty($musicStr))
                                        <div class="about-info-sub" style="color:var(--fb-text-primary);font-weight:600;margin-top:4px;">{{ $musicStr }}</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('skills_lang')">+ প্রিয় গান ও মিউজিক যোগ করুন</button>
                                    @else
                                        <div class="about-info-sub">তথ্য নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($musicStr))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('skills_lang')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Favorite Books -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">📚</div>
                                <div class="about-info-details">
                                    <div class="about-info-main">প্রিয় বই ও সাহিত্য (Favorite Books)</div>
                                    @php
                                        $booksStr = !empty($profile['favorite_books']) ? (is_array($profile['favorite_books']) ? implode(', ', $profile['favorite_books']) : $profile['favorite_books']) : '';
                                    @endphp
                                    @if(!empty($booksStr))
                                        <div class="about-info-sub" style="color:var(--fb-text-primary);font-weight:600;margin-top:4px;">{{ $booksStr }}</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('skills_lang')">+ প্রিয় বই ও সাহিত্য যোগ করুন</button>
                                    @else
                                        <div class="about-info-sub">তথ্য নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($booksStr))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('skills_lang')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Favorite Movies -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🎬</div>
                                <div class="about-info-details">
                                    <div class="about-info-main">প্রিয় সিনেমা ও সিরিজ (Favorite Movies)</div>
                                    @php
                                        $moviesStr = !empty($profile['favorite_movies']) ? (is_array($profile['favorite_movies']) ? implode(', ', $profile['favorite_movies']) : $profile['favorite_movies']) : '';
                                    @endphp
                                    @if(!empty($moviesStr))
                                        <div class="about-info-sub" style="color:var(--fb-text-primary);font-weight:600;margin-top:4px;">{{ $moviesStr }}</div>
                                    @elseif($isOwner)
                                        <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('skills_lang')">+ প্রিয় সিনেমা ও সিরিজ যোগ করুন</button>
                                    @else
                                        <div class="about-info-sub">তথ্য নেই</div>
                                    @endif
                                </div>
                                @if($isOwner && !empty($moviesStr))
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('skills_lang')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>
                        </div>

                        <!-- 7. SKILLS AND LANGUAGES PANEL -->
                        <div id="about-panel-skills" style="display:none;">
                            <div class="about-section-header">
                                <span class="about-section-title">🏆 দক্ষতা ও পারদর্শিতা (Skills & Languages)</span>
                                @if($isOwner)
                                    <button class="fb-btn fb-btn-secondary" style="font-size:13px;padding:6px 12px;" onclick="openEditProfileWithTab('skills_lang')">
                                        + দক্ষতা ও ভাষা যোগ করুন
                                    </button>
                                @endif
                            </div>

                            <!-- Skills -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">💡</div>
                                <div class="about-info-details">
                                    <div class="about-info-main">দক্ষতাসমূহ (Professional Skills)</div>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                                        @forelse($profile['sections']['skills'] ?? [] as $skill)
                                            <span style="background:#e7f3ff;color:var(--fb-primary);padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:6px;">
                                                ⭐ {{ $skill['name'] }}
                                                @if(!empty($skill['level']))
                                                    <span style="font-size:11px;opacity:0.85;background:rgba(24,119,242,0.15);padding:1px 6px;border-radius:10px;">{{ $skill['level'] }}</span>
                                                @endif
                                            </span>
                                        @empty
                                            @if($isOwner)
                                                <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('skills_lang')">+ নতুন দক্ষতা যোগ করুন</button>
                                            @else
                                                <span style="font-size:13px;color:var(--fb-text-secondary);">কোনো দক্ষতা যোগ করা হয়নি।</span>
                                            @endif
                                        @endforelse
                                    </div>
                                </div>
                                @if($isOwner && !empty($profile['sections']['skills']) && count($profile['sections']['skills']) > 0)
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('skills_lang')" title="সম্পাদনা">✏️</button>
                                @endif
                            </div>

                            <!-- Languages -->
                            <div class="about-info-item">
                                <div class="about-info-icon-box">🌐</div>
                                <div class="about-info-details">
                                    <div class="about-info-main">জানা ভাষাসমূহ (Languages)</div>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                                        @forelse($profile['sections']['languages'] ?? [] as $lang)
                                            <span style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:6px;">
                                                🗣️ {{ $lang['language'] }}
                                                @if(!empty($lang['proficiency']))
                                                    <span style="font-size:11px;opacity:0.85;background:rgba(22,101,52,0.15);padding:1px 6px;border-radius:10px;">{{ ucfirst($lang['proficiency']) }}</span>
                                                @endif
                                            </span>
                                        @empty
                                            @if($isOwner)
                                                <button type="button" class="about-add-prompt" onclick="openEditProfileWithTab('skills_lang')">+ জানা ভাষা সমূহ যোগ করুন</button>
                                            @else
                                                <span style="font-size:13px;color:var(--fb-text-secondary);">কোনো ভাষা যোগ করা হয়নি।</span>
                                            @endif
                                        @endforelse
                                    </div>
                                </div>
                                @if($isOwner && !empty($profile['sections']['languages']) && count($profile['sections']['languages']) > 0)
                                    <button class="about-info-edit-btn" onclick="openEditProfileWithTab('skills_lang')" title="ভাষা সম্পাদনা">✏️</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: FRIENDS -->
            <div id="tabContent-friends" style="display:none;">
                <div class="fb-card">
                    <!-- Top Bar with Title, Subtitle, Search and Filters -->
                    <div class="friends-tab-header">
                        <div>
                            <span class="card-header-title">সকল বন্ধু (<span id="friendsCountDisplay">{{ number_format($profile['friends_count']) }}</span>)</span>
                            <div style="font-size:12px;color:var(--fb-text-secondary);margin-top:2px;">ঘনিষ্ঠ বন্ধু ও পছন্দের বন্ধুদের তালিকা পরিচালনা করুন</div>
                        </div>

                        <!-- Real-time Live Search Input -->
                        <div class="friends-search-box">
                            <span style="color:var(--fb-text-secondary);font-size:13px;">🔍</span>
                            <input type="text"
                                   id="friendSearchInput"
                                   placeholder="বন্ধুর নাম বা ইউজারনেম দিয়ে খুঁজুন..."
                                   oninput="handleFriendSearch(this.value)"
                                   class="friends-search-input"
                                   autocomplete="off">
                            <button type="button"
                                    id="clearFriendSearchBtn"
                                    onclick="clearFriendSearch()"
                                    style="display:none;background:none;border:none;color:var(--fb-text-secondary);cursor:pointer;font-size:13px;padding:2px 4px;"
                                    title="সার্চ মুছুন">✕</button>
                        </div>
                    </div>

                    <!-- Filter Chips Row -->
                    <div class="filter-chips-row" style="margin-bottom:16px;">
                        <button type="button" class="filter-chip active" onclick="filterFriends('all', this)">সকল বন্ধু (<span id="allFriendsCountBadge">{{ count($friends) }}</span>)</button>
                        @if($isOwner)
                            <button type="button" class="filter-chip" onclick="filterFriends('close_friends', this)">⭐ ক্লোজ ফ্রেন্ডস</button>
                            <button type="button" class="filter-chip" onclick="filterFriends('favorites', this)">❤️ ফেভারিটস</button>
                            @if(isset($friendSuggestions) && $friendSuggestions->isNotEmpty())
                                <button type="button" class="filter-chip" onclick="filterFriends('suggestions', this)">💡 নতুন পরামর্শ ({{ $friendSuggestions->count() }})</button>
                            @endif
                        @endif
                    </div>

                    <!-- Friends List Grid -->
                    <div id="friendsListGrid" class="friends-grid-enhanced">
                        @forelse($friends as $friend)
                            @php
                                $isFav = !empty($friend['is_favorite']);
                                $isClose = !empty($friend['is_close_friend']);
                            @endphp
                            <div class="friend-card-enhanced friend-item-card"
                                 id="friendCard-{{ $friend['id'] }}"
                                 data-name="{{ strtolower($friend['name']) }}"
                                 data-username="{{ strtolower($friend['username']) }}"
                                 data-favorite="{{ $isFav ? '1' : '0' }}"
                                 data-close="{{ $isClose ? '1' : '0' }}">
                                <a href="{{ getUserProfileUrl($friend) }}">
                                    <img src="{{ $friend['avatar_url'] ?? '/images/default-avatar.svg' }}"
                                         alt="{{ $friend['name'] }}"
                                         class="friend-avatar-enhanced"
                                         onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                </a>
                                <div class="friend-info-enhanced">
                                    <a href="{{ getUserProfileUrl($friend) }}" class="friend-name-link">
                                        {{ $friend['name'] }}
                                    </a>
                                    <div class="friend-meta-text">{{ '@' . $friend['username'] }}</div>
                                    @if(!empty($friend['mutual_friends_count']) && $friend['mutual_friends_count'] > 0)
                                        <div style="font-size:11px;color:var(--fb-primary);margin-top:2px;font-weight:600;display:flex;align-items:center;gap:4px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                            <span>{{ $friend['mutual_friends_count'] }} জন মিউচুয়াল বন্ধু</span>
                                        </div>
                                    @endif

                                    <div class="friend-action-row">
                                        <button type="button" class="fb-btn fb-btn-secondary" onclick="openDirectChatWithUser({{ $friend['id'] }}, '{{ addslashes($friend['name']) }}', '{{ $friend['username'] }}', '{{ $friend['avatar'] ?? '' }}')" style="font-size:11px;padding:3px 8px;border-radius:12px;display:inline-flex;align-items:center;gap:4px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                            <span>বার্তা</span>
                                        </button>

                                        @if($isOwner)
                                            <button type="button"
                                                    class="fb-btn fb-btn-secondary btn-fav-toggle"
                                                    style="font-size:11px;padding:3px 8px;border-radius:12px;display:inline-flex;align-items:center;gap:4px;{{ $isFav ? 'background:#fee2e2;color:#dc2626;border-color:#fca5a5;' : '' }}"
                                                    onclick="toggleFavoriteFriendEnhanced({{ $friend['id'] }}, this)"
                                                    title="ফেভারিট তালিকায় যুক্ত/বাদ দিন">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="{{ $isFav ? '#dc2626' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                                <span>{{ $isFav ? 'ফেভারিট (হ্যাঁ)' : 'ফেভারিট' }}</span>
                                            </button>

                                            <button type="button"
                                                    class="fb-btn fb-btn-secondary btn-close-toggle"
                                                    style="font-size:11px;padding:3px 8px;border-radius:12px;display:inline-flex;align-items:center;gap:4px;{{ $isClose ? 'background:#fef3c7;color:#d97706;border-color:#fcd34d;' : '' }}"
                                                    onclick="toggleCloseFriendEnhanced({{ $friend['id'] }}, this)"
                                                    title="ক্লোজ ফ্রেন্ড তালিকায় যুক্ত/বাদ দিন">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="{{ $isClose ? '#d97706' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                                <span>{{ $isClose ? 'ক্লোজ (হ্যাঁ)' : 'ক্লোজ' }}</span>
                                            </button>

                                            <button type="button"
                                                    class="fb-btn fb-btn-secondary"
                                                    style="font-size:11px;padding:3px 7px;border-radius:12px;color:#dc2626;"
                                                    onclick="handleUnfriendUser({{ $friend['id'] }}, '{{ addslashes($friend['name']) }}')"
                                                    title="আনফ্রেন্ড করুন">
                                                ✕
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="grid-column:1/-1;text-align:center;padding:32px;color:var(--fb-text-secondary);">
                                কোনো ফ্রেন্ড তালিকা পাওয়া যায়নি।
                            </div>
                        @endforelse
                    </div>

                    <!-- Search / Filter Empty State (hidden by default) -->
                    <div id="friendsSearchEmptyState" style="display:none;text-align:center;padding:36px 16px;color:var(--fb-text-secondary);">
                        <div style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:50%;background:var(--fb-hover);margin-bottom:8px;color:var(--fb-text-secondary);">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </div>
                        <h4 style="font-size:15px;color:var(--fb-text-primary);font-weight:700;">কোনো বন্ধু পাওয়া যায়নি</h4>
                        <p style="font-size:13px;margin-top:4px;">আপনার অনুসন্ধানের সাথে মিল রেখে কোনো ফলাফল পাওয়া যায়নি।</p>
                        <button type="button" class="fb-btn fb-btn-secondary" style="margin-top:12px;font-size:13px;" onclick="clearFriendSearch()">
                            সকল বন্ধু দেখুন
                        </button>
                    </div>

                    <!-- Friend Suggestions Section -->
                    @if($isOwner && isset($friendSuggestions) && $friendSuggestions->isNotEmpty())
                        <div id="friendSuggestionsSection" style="display:none;margin-top:24px;border-top:1px solid var(--fb-divider);padding-top:16px;">
                            <h4 style="font-size:15px;font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"></path><path d="M10 22h4"></path><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"></path></svg>
                                <span>নতুন ফ্রেন্ডস পরামর্শ (Friend Suggestions)</span>
                            </h4>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:12px;">
                                @foreach($friendSuggestions as $sug)
                                    <div style="padding:14px;border:1px solid var(--fb-divider);border-radius:var(--radius-md);text-align:center;background:var(--fb-hover);">
                                        <img src="{{ $sug->profile->avatar_url ?? '/images/default-avatar.svg' }}"
                                             alt="{{ $sug->name }}"
                                             style="width:68px;height:68px;border-radius:50%;object-fit:cover;margin:0 auto 8px;"
                                             onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                        <a href="{{ getUserProfileUrl($sug) }}" style="font-weight:700;font-size:14px;color:var(--fb-text-primary);text-decoration:none;display:block;">
                                            {{ $sug->name }}
                                        </a>
                                        <div style="font-size:12px;color:var(--fb-text-secondary);margin-bottom:10px;">{{ '@' . $sug->username }}</div>
                                        <button type="button" class="fb-btn fb-btn-primary" style="font-size:12px;padding:6px 12px;width:100%;display:inline-flex;align-items:center;justify-content:center;gap:6px;" onclick="handleSendFriendRequest({{ $sug->id }})">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                            <span>বন্ধু যোগ করুন</span>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- TAB: PHOTOS & ALBUMS -->
            <div id="tabContent-photos" style="display:none;">
                <div class="fb-card">
                    <div class="card-header-bar">
                        <div>
                            <span class="card-header-title">ছবি ও ফটো অ্যালবাম ({{ $profile['photos_count'] }})</span>
                            <div style="font-size:12px;color:var(--fb-text-secondary);margin-top:2px;">সকল একক ছবি এবং অ্যালবাম কালেকশন</div>
                        </div>
                        @if($isOwner)
                            <button type="button" class="fb-btn fb-btn-primary" onclick="openCreateAlbumModal()">
                                📁 নতুন অ্যালবাম তৈরি
                            </button>
                        @endif
                    </div>

                    <!-- Subtabs for Photos and Albums -->
                    <div class="filter-chips-row">
                        <button type="button" class="filter-chip active" onclick="switchPhotoSubtab('photos', this)">🖼️ সকল ফটো</button>
                        <button type="button" class="filter-chip" onclick="switchPhotoSubtab('albums', this)">📁 অ্যালবাম ({{ isset($albums) ? $albums->count() : 0 }})</button>
                    </div>

                    <!-- Single Photos Grid -->
                    <div id="subtabContent-photos">
                        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(150px, 1fr));gap:10px;">
                            @forelse($photos as $photo)
                                <div style="aspect-ratio:1/1;border-radius:var(--radius-sm);overflow:hidden;background:#eee;position:relative;cursor:pointer;" onclick="openPhotoTheater('{{ $photo['url'] ?? '' }}', '{{ addslashes($profile['name']) }}', 'ফটো গ্যালারি', 'সম্প্রতি')">
                                    <img src="{{ $photo['url'] ?? '' }}" alt="Photo" style="width:100%;height:100%;object-fit:cover;display:block;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                </div>
                            @empty
                                <div style="grid-column:1/-1;text-align:center;padding:32px;color:var(--fb-text-secondary);">
                                    কোনো ছবি আপলোড করা হয়নি।
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Albums Grid -->
                    <div id="subtabContent-albums" style="display:none;">
                        <div class="albums-grid">
                            @forelse($albums ?? [] as $album)
                                <div class="album-card">
                                    <img src="{{ $album->cover_photo_path ?: 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=400' }}"
                                         alt="{{ $album->title }}"
                                         class="album-cover-img">
                                    <div style="padding:10px;">
                                        <h4 style="font-size:14px;font-weight:700;color:var(--fb-text-primary);">{{ $album->title }}</h4>
                                        <div style="font-size:12px;color:var(--fb-text-secondary);margin-top:2px;">
                                            {{ $album->items_count ?? $album->items->count() }} টি ছবি • {{ ucfirst($album->privacy) }}
                                        </div>
                                        @if($isOwner)
                                            <div style="display:flex;gap:6px;margin-top:8px;">
                                                <button type="button" class="fb-btn fb-btn-secondary" style="font-size:11px;padding:3px 8px;flex:1;" onclick="openAddAlbumItemModal({{ $album->id }})">
                                                    ➕ ফটো যোগ
                                                </button>
                                                <button type="button" class="fb-btn fb-btn-secondary" style="font-size:11px;padding:3px 8px;color:#dc2626;" onclick="deleteAlbum({{ $album->id }})">
                                                    🗑️
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div style="grid-column:1/-1;text-align:center;padding:32px;color:var(--fb-text-secondary);">
                                    কোনো অ্যালবাম তৈরি করা হয়নি।
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: VIDEOS & REELS -->
            <div id="tabContent-videos" style="display:none;">
                <div class="fb-card">
                    <div class="card-header-bar">
                        <div>
                            <span class="card-header-title">ভিডিও ও রিলস হাব ({{ $profile['videos_count'] ?? 0 }})</span>
                            <div style="font-size:12px;color:var(--fb-text-secondary);margin-top:2px;">সকল দীর্ঘ ভিডিও এবং শর্ট রিলস ভিডিও স্ট্রিম</div>
                        </div>
                        @if($isOwner)
                            <button type="button" class="fb-btn fb-btn-primary" onclick="openPostModal('video')">
                                📹 নতুন ভিডিও যোগ করুন
                            </button>
                        @endif
                    </div>

                    <div class="filter-chips-row">
                        <button type="button" class="filter-chip active" onclick="switchVideoSubtab('videos', this)">📹 বড় ভিডিও</button>
                        <button type="button" class="filter-chip" onclick="switchVideoSubtab('reels', this)" style="display: inline-flex; align-items: center; gap: 4px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="3.5"></rect><polygon points="10 8 16 12 10 16 10 8" fill="currentColor"></polygon></svg>
                            <span>রিলস ও শর্টস</span>
                        </button>
                    </div>

                    <!-- Regular Videos -->
                    <div id="subtabContent-videos">
                        <div class="video-cards-grid">
                            @forelse($videos ?? [] as $video)
                                <div class="video-card-item" onclick="openVideoTheater('{{ $video['url'] }}', '{{ addslashes($profile['name']) }}', 'ভিডিও #{{ $video['id'] ?? $loop->iteration }}', '{{ isset($video['created_at']) ? \Carbon\Carbon::parse($video['created_at'])->diffForHumans() : 'সম্প্রতি' }}')">
                                    <div class="video-card-thumb-wrap">
                                        <video src="{{ $video['url'] }}#t=0.5" preload="metadata" muted playsinline></video>
                                        <div class="video-play-overlay">
                                            <div class="video-play-circle">▶</div>
                                        </div>
                                        <div class="video-duration-badge">📹 ভিডিও</div>
                                    </div>
                                    <div class="video-card-info">
                                        <div class="video-card-title">ভিডিও #{{ $video['id'] ?? $loop->iteration }}</div>
                                        <div class="video-card-date">{{ isset($video['created_at']) ? \Carbon\Carbon::parse($video['created_at'])->diffForHumans() : 'সম্প্রতি' }}</div>
                                    </div>
                                </div>
                            @empty
                                <div style="grid-column:1/-1;text-align:center;padding:40px 16px;color:var(--fb-text-secondary);">
                                    <div style="font-size:36px;margin-bottom:8px;">📹</div>
                                    <div style="font-size:15px;font-weight:600;">কোনো ভিডিও আপলোড করা হয়নি</div>
                                    <div style="font-size:13px;margin-top:4px;">নতুন ভিডিও পোস্ট করলে তা এখানে প্রদর্শিত হবে।</div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Reels / Shorts (Active 24-Hour Ephemeral Reels) -->
                    <div id="subtabContent-reels" style="display:none;">
                        <div class="reels-grid" id="profileReelsGrid">
                            @forelse($activeReels ?? [] as $reel)
                                <div class="reel-card" id="profile-reel-card-{{ $reel['id'] }}" onclick="playReelModal('{{ $reel['video_url'] ?? ($reel['media_url'] ?? '') }}', '{{ addslashes($profile['name']) }}', '{{ addslashes($reel['caption'] ?? '') }}', {{ (int) ($reel['likes_count'] ?? 0) }}, {{ (int) ($reel['comments_count'] ?? 0) }}, {{ (int) $reel['id'] }})">
                                    @if(!empty($reel['thumbnail_url']))
                                        <img src="{{ $reel['thumbnail_url'] }}" alt="Reel thumbnail" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='block';">
                                        <video src="{{ $reel['video_url'] ?? ($reel['media_url'] ?? '') }}" muted playsinline preload="metadata" style="display:none;width:100%;height:100%;object-fit:cover;"></video>
                                    @else
                                        <video src="{{ $reel['video_url'] ?? ($reel['media_url'] ?? '') }}" muted playsinline preload="metadata" style="width:100%;height:100%;object-fit:cover;"></video>
                                    @endif
                                    <div class="reel-overlay">
                                        <div style="display:flex;justify-content:space-between;align-items:center;width:100%;">
                                            <div style="font-weight:700;font-size:13px;display:flex;align-items:center;gap:4px;">
                                                <span>▶</span> {{ !empty($reel['views_count']) ? number_format($reel['views_count']) : 0 }}
                                            </div>
                                            @if(!empty($reel['time_remaining_seconds']))
                                                <div style="font-size:10px;background:rgba(0,0,0,0.6);padding:2px 6px;border-radius:10px;color:#facc15;" title="মেয়াদ শেষ হতে বাকি">
                                                    ⏳ {{ gmdate("H:i", $reel['time_remaining_seconds']) }}
                                                </div>
                                            @endif
                                        </div>
                                        @if(!empty($reel['caption']))
                                            <div style="font-size:11px;opacity:0.95;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:4px;">{{ $reel['caption'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div id="noProfileReelsMsg" style="grid-column:1/-1;text-align:center;padding:48px 20px;color:var(--fb-text-secondary);">
                                    <div style="font-size:36px;margin-bottom:8px;">🎬</div>
                                    <div style="font-weight:600;font-size:15px;color:var(--fb-text-primary);">কোনো সক্রিয় রিলস নেই</div>
                                    <div style="font-size:13px;margin-top:4px;">রিলস প্রকাশের ২৪ ঘণ্টার জন্য সক্রিয় থাকে।</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            @if($isOwner)
                <!-- TAB: SAVED ITEMS & COLLECTIONS -->
                <div id="tabContent-saved" style="display:none;">
                    <div class="fb-card">
                        <div class="card-header-bar" style="flex-wrap:wrap;gap:12px;margin-bottom:16px;">
                            <div>
                                <span class="card-header-title">🔖 সংরক্ষিত আইটেম ও কালেকশন (Saved Items)</span>
                                <div style="font-size:12px;color:var(--fb-text-secondary);margin-top:2px;">ভবিষ্যতে দেখার জন্য সংরক্ষিত পোস্ট, ভিডিও ও গুরুত্বপূর্ণ কন্টেন্ট</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="friend-search-wrap" style="position:relative;width:220px;">
                                    <input type="text" id="savedSearchInput" placeholder="সংরক্ষিত আইটেম খুঁজুন..." oninput="handleSavedSearch(this.value)" style="width:100%;padding:8px 30px 8px 12px;border-radius:20px;border:1px solid var(--fb-border);background:var(--fb-hover);font-size:13px;color:var(--fb-text-primary);font-family:inherit;outline:none;">
                                    <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--fb-text-secondary);font-size:13px;">🔍</span>
                                </div>
                            </div>
                        </div>

                        <!-- Saved Item Category Filter Chips -->
                        <div style="display:flex;gap:8px;margin-bottom:16px;overflow-x:auto;padding-bottom:4px;" id="savedCategoryFilterWrap">
                            <button type="button" class="friend-filter-chip active" data-saved-type="all" onclick="filterSavedItems('all', this)">
                                📂 সব সংরক্ষিত ({{ count($savedItems ?? []) }})
                            </button>
                            <button type="button" class="friend-filter-chip" data-saved-type="post" onclick="filterSavedItems('post', this)">
                                📄 পোস্টসমূহ
                            </button>
                            <button type="button" class="friend-filter-chip" data-saved-type="video" onclick="filterSavedItems('video', this)">
                                🎬 ভিডিও ও রিলস
                            </button>
                            <button type="button" class="friend-filter-chip" data-saved-type="photo" onclick="filterSavedItems('photo', this)">
                                🖼️ ছবি
                            </button>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:12px;" id="savedItemsContainer">
                            @forelse($savedItems ?? [] as $saved)
                                @php
                                    $itemClass = strtolower(class_basename($saved->item_type));
                                    $icon = '📌';
                                    $typeLabel = 'কন্টেন্ট';
                                    $itemUrl = '#';
                                    if (str_contains($itemClass, 'post')) {
                                        $icon = '📄';
                                        $typeLabel = 'পোস্ট';
                                        $itemUrl = url('/post/' . $saved->item_id);
                                    } elseif (str_contains($itemClass, 'video')) {
                                        $icon = '🎬';
                                        $typeLabel = 'ভিডিও';
                                        $itemUrl = url('/post/' . $saved->item_id);
                                    } elseif (str_contains($itemClass, 'photo')) {
                                        $icon = '🖼️';
                                        $typeLabel = 'ছবি';
                                        $itemUrl = url('/post/' . $saved->item_id);
                                    }
                                @endphp
                                <div class="saved-item-row" data-saved-type="{{ $itemClass }}" data-saved-collection="{{ strtolower($saved->collection_name) }}" id="saved-item-{{ $saved->id }}" style="display:flex;align-items:center;justify-content:space-between;padding:14px;border:1px solid var(--fb-border);border-radius:var(--radius-md);background:var(--fb-hover);transition:background 0.2s;">
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div style="width:40px;height:40px;border-radius:10px;background:#e7f3ff;color:#1877f2;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                                            {{ $icon }}
                                        </div>
                                        <div>
                                            <div style="font-weight:700;font-size:14px;color:var(--fb-text-primary);" class="saved-title">
                                                {{ $typeLabel }} #{{ $saved->item_id }}
                                            </div>
                                            <div style="font-size:12px;color:var(--fb-text-secondary);margin-top:2px;">
                                                কালেকশন: <strong style="color:var(--fb-primary);">{{ $saved->collection_name }}</strong> • সংরক্ষিত: {{ $saved->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        @if($itemUrl !== '#')
                                            <a href="{{ $itemUrl }}" class="fb-btn fb-btn-secondary" style="font-size:12px;text-decoration:none;padding:6px 12px;">
                                                🔗 দেখুন
                                            </a>
                                        @endif
                                        <button type="button" class="fb-btn fb-btn-secondary" style="font-size:12px;color:#dc2626;padding:6px 12px;" onclick="handleUnsaveItem('{{ addslashes($saved->item_type) }}', {{ $saved->item_id }}, 'saved-item-{{ $saved->id }}')">
                                            🗑️ সরান
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div style="text-align:center;padding:48px 16px;color:var(--fb-text-secondary);" id="savedEmptyNotice">
                                    <div style="font-size:48px;margin-bottom:12px;opacity:0.6;">🔖</div>
                                    <div style="font-size:16px;font-weight:700;color:var(--fb-text-primary);margin-bottom:4px;">আপনার কোনো সংরক্ষিত আইটেম নেই</div>
                                    <div style="font-size:13px;">টাইমলাইনের যেকোনো পোস্টের ৩-ডট মেনু থেকে 'সংরক্ষণ করুন' এ ক্লিক করে এখানে যোগ করতে পারেন।</div>
                                </div>
                            @endforelse
                            <div style="display:none;text-align:center;padding:32px;color:var(--fb-text-secondary);" id="savedNoSearchResults">
                                কোনো সংরক্ষিত আইটেম পাওয়া যায়নি।
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: ACTIVITY LOG -->
                <div id="tabContent-activity" style="display:none;">
                    <div class="fb-card">
                        <div class="card-header-bar" style="flex-wrap:wrap;gap:12px;margin-bottom:16px;">
                            <div>
                                <span class="card-header-title">📜 প্রোফাইল অ্যাক্টিভিটি হিস্ট্রি (Activity Log)</span>
                                <div style="font-size:12px;color:var(--fb-text-secondary);margin-top:2px;">আপনার অ্যাকাউন্টের সাম্প্রতিক কার্যকলাপ ও নিরাপত্তা রেকর্ড</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="friend-search-wrap" style="position:relative;width:220px;">
                                    <input type="text" id="activitySearchInput" placeholder="অ্যাক্টিভিটি সার্চ করুন..." oninput="handleActivitySearch(this.value)" style="width:100%;padding:8px 30px 8px 12px;border-radius:20px;border:1px solid var(--fb-border);background:var(--fb-hover);font-size:13px;color:var(--fb-text-primary);font-family:inherit;outline:none;">
                                    <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--fb-text-secondary);font-size:13px;">🔍</span>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Filter Chips -->
                        <div style="display:flex;gap:8px;margin-bottom:16px;overflow-x:auto;padding-bottom:4px;" id="activityFilterWrap">
                            <button type="button" class="friend-filter-chip active" data-activity-type="all" onclick="filterActivityLog('all', this)">
                                📜 সকল কার্যকলাপ ({{ count($activityLogs ?? []) }})
                            </button>
                            <button type="button" class="friend-filter-chip" data-activity-type="media" onclick="filterActivityLog('media', this)">
                                📷 ছবি ও মিডিয়া
                            </button>
                            <button type="button" class="friend-filter-chip" data-activity-type="friend" onclick="filterActivityLog('friend', this)">
                                👥 বন্ধুত্ব
                            </button>
                            <button type="button" class="friend-filter-chip" data-activity-type="pro" onclick="filterActivityLog('pro', this)">
                                💼 প্রফেশনাল
                            </button>
                            <button type="button" class="friend-filter-chip" data-activity-type="security" onclick="filterActivityLog('security', this)">
                                ⚡ নিরাপত্তা ও সেটিংস
                            </button>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:10px;" id="activityLogsContainer">
                            @forelse($activityLogs ?? [] as $log)
                                @php
                                    $actType = strtolower($log->activity_type ?? '');
                                    $category = 'security';
                                    $icon = '⚡';
                                    if (str_contains($actType, 'avatar') || str_contains($actType, 'cover') || str_contains($actType, 'album') || str_contains($actType, 'photo')) {
                                        $category = 'media';
                                        $icon = '📷';
                                    } elseif (str_contains($actType, 'friend')) {
                                        $category = 'friend';
                                        $icon = '👥';
                                    } elseif (str_contains($actType, 'professional')) {
                                        $category = 'pro';
                                        $icon = '💼';
                                    }
                                @endphp
                                <div class="activity-log-row" data-activity-category="{{ $category }}" style="display:flex;align-items:flex-start;gap:12px;padding:12px;border-bottom:1px solid var(--fb-divider);">
                                    <div style="width:38px;height:38px;border-radius:50%;background:#e7f3ff;color:#1877f2;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                                        {{ $icon }}
                                    </div>
                                    <div style="flex:1;">
                                        <div class="activity-desc" style="font-size:14px;font-weight:600;color:var(--fb-text-primary);">{{ $log->description }}</div>
                                        <div style="font-size:12px;color:var(--fb-text-secondary);margin-top:2px;">
                                            {{ $log->created_at->diffForHumans() }} • IP: {{ $log->ip_address }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div style="text-align:center;padding:48px 16px;color:var(--fb-text-secondary);" id="activityEmptyNotice">
                                    <div style="font-size:48px;margin-bottom:12px;opacity:0.6;">📜</div>
                                    <div style="font-size:16px;font-weight:700;color:var(--fb-text-primary);margin-bottom:4px;">কোনো অ্যাক্টিভিটি লগ পাওয়া যায়নি</div>
                                    <div style="font-size:13px;">আপনার প্রোফাইল সম্পাদনা বা কার্যক্রম এখানে সংরক্ষিত থাকবে।</div>
                                </div>
                            @endforelse
                            <div style="display:none;text-align:center;padding:32px;color:var(--fb-text-secondary);" id="activityNoSearchResults">
                                ফিল্টারের সাথে মিলে এমন কোনো অ্যাক্টিভিটি নেই।
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: PROFESSIONAL DASHBOARD -->
                <div id="tabContent-professional" style="display:none;">
                    <div class="fb-card">
                        <div class="pro-dashboard-card">
                            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                                <div>
                                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                        <h2 style="font-size:22px;font-weight:800;margin:0;">💼 প্রফেশনাল ক্রিয়েটর ড্যাশবোর্ড</h2>
                                        @if(!empty($profile['category']))
                                            <span style="font-size:12px;background:rgba(255,255,255,0.25);padding:3px 10px;border-radius:12px;font-weight:600;">{{ $profile['category'] }}</span>
                                        @endif
                                    </div>
                                    <p style="font-size:14px;opacity:0.9;margin-top:6px;">আপনার প্রোফাইলকে প্রফেশনাল ক্রিয়েটর একাউন্টে রূপান্তর করে রিচ ও আয়ের সুযোগ বাড়ান</p>
                                    <div style="margin-top:10px;display:flex;align-items:center;gap:10px;">
                                        <button type="button" class="fb-btn fb-btn-secondary" style="background:rgba(255,255,255,0.2);color:#fff;border:none;padding:6px 12px;font-size:13px;" onclick="openCreatorCategoryModal()">
                                            🏷️ ক্যাটাগরি নির্ধারণ
                                        </button>
                                    </div>
                                </div>
                                <div class="toggle-switch-container">
                                    <span style="font-size:15px;font-weight:700;">{{ ($profile['is_professional_mode'] ?? false) ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}</span>
                                    <label class="switch">
                                        <input type="checkbox" id="proModeToggleCheckbox" {{ ($profile['is_professional_mode'] ?? false) ? 'checked' : '' }} onchange="toggleProfessionalMode(this)">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Metrics -->
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
                            <h3 style="font-size:16px;font-weight:700;margin:0;">📈 ক্রিয়েটর পারফরম্যান্স ইনসাইটস</h3>
                            <span style="font-size:12px;color:var(--fb-text-secondary);background:var(--fb-bg);padding:4px 10px;border-radius:12px;font-weight:600;">গত ৩০ দিন</span>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px;margin-bottom:20px;">
                            <div style="padding:16px;background:var(--fb-hover);border-radius:var(--radius-md);border:1px solid var(--fb-divider);">
                                <div style="font-size:12px;color:var(--fb-text-secondary);font-weight:700;">সর্বমোট রিচ / ভিউ</div>
                                <div style="font-size:24px;font-weight:800;color:var(--fb-primary);margin-top:4px;">
                                    {{ number_format($professionalAnalytics['profile_views_total'] ?? 0) }}
                                </div>
                                <div style="font-size:11px;color:#10b981;font-weight:600;margin-top:2px;">↑ ১২% বৃদ্ধি</div>
                            </div>
                            <div style="padding:16px;background:var(--fb-hover);border-radius:var(--radius-md);border:1px solid var(--fb-divider);">
                                <div style="font-size:12px;color:var(--fb-text-secondary);font-weight:700;">গত ৩০ দিনের ভিউ</div>
                                <div style="font-size:24px;font-weight:800;color:#059669;margin-top:4px;">
                                    {{ number_format($professionalAnalytics['profile_views_30d'] ?? 0) }}
                                </div>
                                <div style="font-size:11px;color:#10b981;font-weight:600;margin-top:2px;">↑ ৮.৫% নতুন ভিউ</div>
                            </div>
                            <div style="padding:16px;background:var(--fb-hover);border-radius:var(--radius-md);border:1px solid var(--fb-divider);">
                                <div style="font-size:12px;color:var(--fb-text-secondary);font-weight:700;">মোট ফলোয়ার</div>
                                <div style="font-size:24px;font-weight:800;color:#7c3aed;margin-top:4px;">
                                    {{ number_format($professionalAnalytics['followers_count'] ?? 0) }}
                                </div>
                                <div style="font-size:11px;color:#7c3aed;font-weight:600;margin-top:2px;">নেট ফলোয়ার গ্রোথ</div>
                            </div>
                            <div style="padding:16px;background:var(--fb-hover);border-radius:var(--radius-md);border:1px solid var(--fb-divider);">
                                <div style="font-size:12px;color:var(--fb-text-secondary);font-weight:700;">এনগেজমেন্ট রেট</div>
                                <div style="font-size:24px;font-weight:800;color:#ea580c;margin-top:4px;">
                                    {{ $professionalAnalytics['engagement_rate'] ?? '0%' }}
                                </div>
                                <div style="font-size:11px;color:#10b981;font-weight:600;margin-top:2px;">↑ সন্তোষজনক অনুপাত</div>
                            </div>
                        </div>

                        <!-- Creator Tools Section -->
                        <h3 style="font-size:16px;font-weight:700;margin:24px 0 12px 0;">🛠️ ক্রিয়েটর টুলস ও ফিচারস</h3>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:14px;margin-bottom:20px;">
                            <div style="padding:16px;border:1px solid var(--fb-border);border-radius:var(--radius-md);background:var(--fb-card);">
                                <div style="font-size:24px;margin-bottom:6px;">⭐</div>
                                <div style="font-size:15px;font-weight:700;color:var(--fb-text-primary);">স্টারস ও ক্রিয়েটর ব্যাজ</div>
                                <div style="font-size:13px;color:var(--fb-text-secondary);margin-top:4px;">ভক্ত ও দর্শকদের কাছ থেকে সাপোর্ট ও ভার্চুয়াল উপহার গ্রহণ করুন।</div>
                            </div>
                            <div style="padding:16px;border:1px solid var(--fb-border);border-radius:var(--radius-md);background:var(--fb-card);">
                                <div style="font-size:24px;margin-bottom:6px;">🎯</div>
                                <div style="font-size:15px;font-weight:700;color:var(--fb-text-primary);">অডিয়েন্স ও ফলোয়ার গ্রোথ</div>
                                <div style="font-size:13px;color:var(--fb-text-secondary);margin-top:4px;">আপনার অডিয়েন্স কোন সময়ে সবচেয়ে বেশি সক্রিয় তা ট্র্যাক করুন।</div>
                            </div>
                            <div style="padding:16px;border:1px solid var(--fb-border);border-radius:var(--radius-md);background:var(--fb-card);">
                                <div style="font-size:24px;margin-bottom:6px;">💡</div>
                                <div style="font-size:15px;font-weight:700;color:var(--fb-text-primary);">কনটেন্ট রিকমেন্ডেশন টিপস</div>
                                <div style="font-size:13px;color:var(--fb-text-secondary);margin-top:4px;">নিয়মিত রিলস ও ভিডিও পোস্ট করে নতুন দর্শকদের কাছে পৌঁছান।</div>
                            </div>
                        </div>

                        <!-- Monetization Readiness Card -->
                        <div style="padding:16px;border:1px solid {{ ($professionalAnalytics['monetization_ready'] ?? false) ? '#86efac' : '#fed7aa' }};border-radius:var(--radius-md);background:{{ ($professionalAnalytics['monetization_ready'] ?? false) ? '#f0fdf4' : '#fffbeb' }};">
                            <h4 style="font-size:15px;font-weight:700;color:{{ ($professionalAnalytics['monetization_ready'] ?? false) ? '#15803d' : '#9a3412' }};">
                                {{ ($professionalAnalytics['monetization_ready'] ?? false) ? '✅ মনিটাইজেশন ও ক্রিয়েটর আর্নিং-এর জন্য উপযুক্ত' : '⏳ মনিটাইজেশনের শর্তাদি পূরণ করুন' }}
                            </h4>
                            <p style="font-size:13px;color:var(--fb-text-secondary);margin-top:4px;">
                                কমপক্ষে ১০০ জন ফলোয়ার এবং ১০টি পাবলিক পোস্ট সম্পন্ন হলে আপনি যোগাযোগ পার্টনার প্রোগ্রাম ও ব্যাজ অর্জনের সুযোগ পাবেন।
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @if($isOwner)
                <!-- TAB: ANALYTICS & PROFILE VIEWS -->
                <div id="tabContent-analytics" style="display:none;">
                    <div class="fb-card" style="margin-bottom: 16px;">
                        <div class="card-header-bar">
                            <div>
                                <span class="card-header-title">📊 অ্যানালিটিক্স ও ভিউ (গত ৩০ দিন)</span>
                                <div style="font-size: 12px; color: var(--fb-text-secondary); margin-top: 2px;">
                                    আপনার প্রোফাইল কারা দেখছে এবং ট্রাফিকের বিস্তারিত পরিসংখ্যান
                                </div>
                            </div>
                            <button type="button" class="fb-btn fb-btn-secondary" onclick="refreshAnalytics()">🔄 রিফ্রেশ</button>
                        </div>

                        <!-- 4 Stat Metric Cards -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin: 16px 0;">
                            <div style="padding: 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px;">
                                <div style="font-size: 12px; color: #1e40af; font-weight: 700; text-transform: uppercase;">সর্বমোট প্রোফাইল ভিউ</div>
                                <div style="font-size: 28px; font-weight: 800; color: #1d4ed8; margin-top: 4px;" id="statTotalViews">
                                    {{ number_format($analytics['total_views'] ?? 0) }}
                                </div>
                                <div style="font-size: 12px; color: #60a5fa; margin-top: 2px;">সব সময়ের হিসাব</div>
                            </div>

                            <div style="padding: 16px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px;">
                                <div style="font-size: 12px; color: #065f46; font-weight: 700; text-transform: uppercase;">আজকের ভিউ</div>
                                <div style="font-size: 28px; font-weight: 800; color: #047857; margin-top: 4px;" id="statTodayViews">
                                    {{ number_format($analytics['views_today'] ?? 0) }}
                                </div>
                                <div style="font-size: 12px; color: #34d399; margin-top: 2px;">আজকের ভিজিটর</div>
                            </div>

                            <div style="padding: 16px; background: #fdf4ff; border: 1px solid #f5d0fe; border-radius: 12px;">
                                <div style="font-size: 12px; color: #86198f; font-weight: 700; text-transform: uppercase;">এই সপ্তাহের ভিউ</div>
                                <div style="font-size: 28px; font-weight: 800; color: #a21caf; margin-top: 4px;" id="statWeekViews">
                                    {{ number_format($analytics['views_this_week'] ?? 0) }}
                                </div>
                                <div style="font-size: 12px; color: #c084fc; margin-top: 2px;">গত ৭ দিন</div>
                            </div>

                            <div style="padding: 16px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px;">
                                <div style="font-size: 12px; color: #92400e; font-weight: 700; text-transform: uppercase;">অনন্য ভিজিটর</div>
                                <div style="font-size: 28px; font-weight: 800; color: #b45309; margin-top: 4px;" id="statUniqueViewers">
                                    {{ number_format($analytics['unique_viewers'] ?? 0) }}
                                </div>
                                <div style="font-size: 12px; color: #fbbf24; margin-top: 2px;">ইউনিক ব্যক্তি ও ডিভাইস</div>
                            </div>
                        </div>

                        <!-- Trend Chart / Day Breakdown -->
                        <div style="margin-top: 20px; padding: 16px; background: var(--fb-hover); border-radius: var(--radius-sm);">
                            <div style="font-size: 14px; font-weight: 700; margin-bottom: 12px;">📈 দৈনিক ভিউয়ের ধারা (Daily Views Trend)</div>
                            <div style="display: flex; align-items: flex-end; gap: 8px; height: 140px; padding: 10px 0; overflow-x: auto;" id="analyticsTrendBars">
                                @php
                                    $trendData = $analytics['trend'] ?? [];
                                    $maxTrend = count($trendData) > 0 ? max(array_values($trendData)) : 1;
                                    if ($maxTrend < 1) $maxTrend = 1;
                                @endphp
                                @forelse($trendData as $tDate => $tCount)
                                    @php $barHeight = max(8, ($tCount / $maxTrend) * 100); @endphp
                                    <div style="display: flex; flex-direction: column; align-items: center; flex: 1; min-width: 26px;">
                                        <div style="font-size: 10px; color: var(--fb-text-secondary); margin-bottom: 4px;">{{ $tCount }}</div>
                                        <div style="width: 16px; height: {{ $barHeight }}px; background: #1877f2; border-radius: 4px 4px 0 0;" title="{{ $tDate }}: {{ $tCount }} views"></div>
                                        <div style="font-size: 9px; color: var(--fb-text-secondary); margin-top: 4px; white-space: nowrap;">
                                            {{ \Illuminate\Support\Carbon::parse($tDate)->format('d M') }}
                                        </div>
                                    </div>
                                @empty
                                    <div style="width: 100%; text-align: center; color: var(--fb-text-secondary); padding: 40px 0;">
                                        এই সময়সীমায় কোনো ভিউ রেকর্ড নেই।
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Discovery Sources & Devices Grid -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                            <!-- Sources -->
                            <div style="padding: 14px; border: 1px solid var(--fb-border); border-radius: 8px;">
                                <div style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">🔍 ট্রাফিকের উৎস (Discovery Sources)</div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    @forelse($analytics['discovery_sources'] ?? [] as $source => $scount)
                                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                            <span>
                                                @if($source === 'direct') 🌐 সরাসরি লিংক
                                                @elseif($source === 'feed') 📰 নিউজফিড
                                                @elseif($source === 'search') 🔍 অনুসন্ধান
                                                @elseif($source === 'group') 👥 গ্রুপ
                                                @else 🔗 {{ ucfirst($source) }}
                                                @endif
                                            </span>
                                            <strong>{{ number_format($scount) }}</strong>
                                        </div>
                                    @empty
                                        <div style="font-size: 13px; color: var(--fb-text-secondary);">কোনো উৎস ডেটা নেই।</div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Devices -->
                            <div style="padding: 14px; border: 1px solid var(--fb-border); border-radius: 8px;">
                                <div style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">📱 ডিভাইসের ধরন (Device Types)</div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    @forelse($analytics['device_breakdown'] ?? [] as $device => $dcount)
                                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                            <span>
                                                @if($device === 'mobile') 📱 মোবাইল
                                                @elseif($device === 'desktop') 💻 ডেস্কটপ
                                                @elseif($device === 'tablet') 📟 ট্যাবলেট
                                                @else 🖥️ {{ ucfirst($device) }}
                                                @endif
                                            </span>
                                            <strong>{{ number_format($dcount) }}</strong>
                                        </div>
                                    @empty
                                        <div style="font-size: 13px; color: var(--fb-text-secondary);">কোনো ডিভাইস ডেটা নেই।</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- Recent Viewers -->
                        <div style="margin-top: 16px; padding: 14px; border: 1px solid var(--fb-border); border-radius: 8px;">
                            <div style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">👥 সাম্প্রতিক পরিদর্শক (Recent Viewers)</div>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                @forelse($analytics['recent_viewers'] ?? [] as $rViewer)
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; background: var(--fb-hover); border-radius: var(--radius-sm);">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="avatar" style="width: 36px; height: 36px; font-size: 14px;">
                                                {{ mb_substr($rViewer['name'] ?? 'U', 0, 1) }}
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; font-size: 14px;">{{ $rViewer['name'] ?? 'বেনামী পরিদর্শক' }}</div>
                                                <div style="font-size: 12px; color: var(--fb-text-secondary);">
                                                    {{ $rViewer['device'] ?? 'ডিভাইস' }} • {{ $rViewer['viewed_ago'] ?? 'সম্প্রতি' }}
                                                </div>
                                            </div>
                                        </div>
                                        @if(!empty($rViewer['username']))
                                            <a href="{{ getUserProfileUrl($rViewer) }}" class="fb-btn fb-btn-secondary" style="font-size: 12px; padding: 4px 10px; text-decoration: none;">
                                                প্রোফাইল দেখুন
                                            </a>
                                        @endif
                                    </div>
                                @empty
                                    <div style="text-align: center; color: var(--fb-text-secondary); padding: 16px; font-size: 13px;">
                                        কোনো সাম্প্রতিক পরিদর্শক পাওয়া যায়নি।
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- ======================= RIGHT TELEMETRY & LIVE COLUMN ======================= -->
        <div class="profile-telemetry-col" id="profileTelemetryCol">
            <!-- Live & Chat Widget (As Shown in Mockup) -->
            <div class="live-chat-widget-card fb-card" style="border-radius:14px; padding:18px;">
                <div class="live-chat-tabs-bar" style="display:flex; border-bottom:1px solid var(--fb-border); margin-bottom:12px;">
                    <button type="button" class="live-chat-tab-btn active" id="btnLiveTab" onclick="switchLiveChatTab('live')">Live</button>
                    <button type="button" class="live-chat-tab-btn" id="btnChatTab" onclick="switchLiveChatTab('chat')">Chat</button>
                </div>
                <div id="liveTabContent">
                    <!-- Soundwave Visualizer -->
                    <div class="soundwave-visualizer-box" style="display:flex; align-items:center; gap:10px; padding:10px 14px; background:rgba(0,132,255,0.06); border-radius:12px; border:1px solid rgba(0,132,255,0.15); margin-bottom:14px;">
                        <span class="soundwave-mic-icon" style="font-size:16px;">🎙️</span>
                        <div class="soundwave-bars" style="display:flex; align-items:center; gap:3px; flex:1; height:24px;">
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                            <span class="wave-bar"></span>
                        </div>
                        <span class="soundwave-label" style="font-size:12px; font-weight:700; color:#0084ff;">Voice / Live</span>
                    </div>

                    @php
                        $activeFriendsNormalized = [];
                        if (isset($friends)) {
                            if ($friends instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                                $activeFriendsNormalized = $friends->items();
                            } elseif ($friends instanceof \Illuminate\Support\Collection) {
                                $activeFriendsNormalized = $friends->all();
                            } elseif (is_array($friends)) {
                                $activeFriendsNormalized = $friends;
                            } elseif (is_iterable($friends)) {
                                $activeFriendsNormalized = iterator_to_array($friends);
                            }
                        }
                    @endphp

                    <!-- Active Friends list in Live tab -->
                    <div id="profileLiveActiveFriendsList" style="display:flex; flex-direction:column; gap:10px;">
                        @forelse(array_slice($activeFriendsNormalized, 0, 5) as $fr)
                            @php
                                $frId = is_array($fr) ? ($fr['id'] ?? 0) : ($fr->id ?? 0);
                                $frName = is_array($fr) ? ($fr['name'] ?? '') : ($fr->name ?? '');
                                $frUser = is_array($fr) ? ($fr['username'] ?? '') : ($fr->username ?? '');
                                $frAv = is_array($fr) ? ($fr['avatar_url'] ?? ($fr['avatar'] ?? '/images/default-avatar.svg')) : ($fr->profile->avatar_url ?? '/images/default-avatar.svg');
                            @endphp
                            <div style="display:flex; align-items:center; gap:10px; cursor:pointer;" onclick="openDirectChatWithUser({{ $frId }}, '{{ addslashes($frName) }}', '{{ $frUser }}', '{{ $frAv }}')">
                                <div style="position:relative; width:34px; height:34px; flex-shrink:0;">
                                    <img src="{{ $frAv ?: '/images/default-avatar.svg' }}" alt="{{ $frName }}" style="width:100%; height:100%; border-radius:50%; object-fit:cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                    <span style="position:absolute; bottom:0; right:0; width:9px; height:9px; background:#22c55e; border-radius:50%; border:2px solid var(--fb-card);"></span>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:13px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $frName }}</div>
                                    <div style="height:6px; background:var(--fb-hover); border-radius:4px; width:65%; margin-top:4px;"></div>
                                </div>
                            </div>
                        @empty
                            <div style="font-size:12px; color:var(--fb-text-secondary); text-align:center; padding:10px;">কোনো সক্রিয় চ্যাট নেই</div>
                        @endforelse
                    </div>
                </div>

                <div id="chatTabContent" style="display:none;">
                    <div style="font-size:12px; color:var(--fb-text-secondary); margin-bottom:8px;">সরাসরি বার্তা ও সক্রিয় বন্ধুরা:</div>
                    <div id="profileChatFriendsList" style="display:flex; flex-direction:column; gap:8px;">
                        @foreach(array_slice($activeFriendsNormalized, 0, 6) as $fr)
                            @php
                                $frId = is_array($fr) ? ($fr['id'] ?? 0) : ($fr->id ?? 0);
                                $frName = is_array($fr) ? ($fr['name'] ?? '') : ($fr->name ?? '');
                                $frUser = is_array($fr) ? ($fr['username'] ?? '') : ($fr->username ?? '');
                                $frAv = is_array($fr) ? ($fr['avatar_url'] ?? ($fr['avatar'] ?? '/images/default-avatar.svg')) : ($fr->profile->avatar_url ?? '/images/default-avatar.svg');
                            @endphp
                            <div style="display:flex; align-items:center; justify-content:space-between; padding:6px 8px; border-radius:8px; cursor:pointer; background:var(--fb-hover);" onclick="openDirectChatWithUser({{ $frId }}, '{{ addslashes($frName) }}', '{{ $frUser }}', '{{ $frAv }}')">
                                <span style="font-size:13px; font-weight:600;">{{ $frName }}</span>
                                <span style="font-size:11px; color:#0084ff; font-weight:700;">চ্যাট ➔</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Post activity and visitor telemetry Card (As Shown in Mockup) -->
            <div class="visitor-telemetry-card fb-card" style="border-radius:14px; padding:18px;">
                <div class="telemetry-card-title" style="font-size:14px; font-weight:800; color:var(--fb-text-primary); margin-bottom:12px; display:flex; align-items:center; justify-content:space-between;">
                    <span>Post activity and visitor telemetry</span>
                    <span style="width:8px; height:8px; border-radius:50%; background:#06b6d4; box-shadow:0 0 0 3px rgba(6,182,212,0.25);"></span>
                </div>
                <div class="telemetry-chart-wrap" style="position:relative; width:100%; height:130px;">
                    <div class="telemetry-axis-y" style="position:absolute; left:0; top:0; bottom:16px; display:flex; flex-direction:column; justify-content:space-between; font-size:10px; color:var(--fb-text-secondary); font-weight:700;">
                        <span>15K</span>
                        <span>10K</span>
                        <span>5K</span>
                        <span>0</span>
                    </div>
                    <svg class="telemetry-svg-canvas" style="width:100%; height:110px; padding-left:26px;" viewBox="0 0 240 100" fill="none">
                        <defs>
                            <linearGradient id="telemetryGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.35"/>
                                <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.0"/>
                            </linearGradient>
                        </defs>
                        <!-- Area Fill -->
                        <path d="M 0 90 Q 20 85 30 75 T 60 55 T 90 70 T 120 40 T 150 65 T 180 20 T 210 50 T 240 35 L 240 100 L 0 100 Z" fill="url(#telemetryGrad)"/>
                        <!-- Smooth Bezier Spline Line -->
                        <path d="M 0 90 Q 20 85 30 75 T 60 55 T 90 70 T 120 40 T 150 65 T 180 20 T 210 50 T 240 35" stroke="#06b6d4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <!-- Peak Pulse Dot -->
                        <circle cx="180" cy="20" r="4" fill="#06b6d4"/>
                        <circle cx="180" cy="20" r="8" stroke="#06b6d4" stroke-width="1.5" opacity="0.6"/>
                    </svg>
                </div>
                <div class="telemetry-axis-x" style="display:flex; justify-content:space-between; padding-left:26px; font-size:10px; color:var(--fb-text-secondary); font-weight:700;">
                    <span>1</span>
                    <span>12</span>
                    <span>1K</span>
                    <span>2K</span>
                    <span>10K</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================= MODALS ======================= -->

    <!-- EDIT PROFILE MODAL -->
    <div class="fb-modal-overlay" id="editProfileModal">
        <div class="fb-modal-card" style="max-width: 650px;">
            <div class="modal-header">
                <span class="modal-title">প্রোফাইল তথ্য ও সেটিংস সম্পাদনা</span>
                <button class="modal-close-btn" onclick="closeModal('editProfileModal')">✕</button>
            </div>
            <div class="modal-subtabs">
                <button type="button" class="modal-subtab active" onclick="switchEditTab('basic', this)">১. মূল তথ্য</button>
                <button type="button" class="modal-subtab" onclick="switchEditTab('about', this)">২. সম্পর্কে ও যোগাযোগ</button>
                <button type="button" class="modal-subtab" onclick="switchEditTab('work_edu', this)">৩. শিক্ষা ও ক্যারিয়ার</button>
                <button type="button" class="modal-subtab" onclick="switchEditTab('skills_lang', this)">৪. দক্ষতা ও ভাষা</button>
                <button type="button" class="modal-subtab" onclick="switchEditTab('social', this)">৫. সোশ্যাল লিংক</button>
            </div>
            <form id="editProfileForm" onsubmit="submitEditProfile(event)">
                <div class="modal-body" style="max-height: 60vh;">
                    <!-- Facebook-Style Profile Photo & Cover Quick Actions -->
                    <div style="background:var(--fb-bg);border:1px solid var(--fb-divider);border-radius:var(--radius-md);padding:10px 14px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="{{ $profile['avatar'] ?? '/images/default-avatar.svg' }}"
                                 alt="Avatar"
                                 style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid var(--fb-primary);"
                                 onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                            <div>
                                <div style="font-weight:700;font-size:13px;color:var(--fb-text-primary);">প্রোফাইল ছবি ও ফ্রেম</div>
                                <div style="font-size:11px;color:var(--fb-text-secondary);">ছবি পরিবর্তন করুন বা নতুন ফ্রেম যুক্ত করুন</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:6px;">
                            <button type="button" class="fb-btn fb-btn-secondary" style="font-size:12px;padding:4px 10px;display:inline-flex;align-items:center;gap:4px;" onclick="openAvatarModal()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                <span>ছবি পরিবর্তন</span>
                            </button>
                            <button type="button" class="fb-btn fb-btn-secondary" style="font-size:12px;padding:4px 10px;display:inline-flex;align-items:center;gap:4px;" onclick="openAvatarFrameModal()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                <span>ফ্রেম</span>
                            </button>
                        </div>
                    </div>

                    <div style="background:var(--fb-bg);border:1px solid var(--fb-divider);border-radius:var(--radius-md);padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            @if(!empty($profile['cover_photo']))
                                <img src="{{ $profile['cover_photo'] }}"
                                     alt="Cover"
                                     style="width:68px;height:38px;border-radius:var(--radius-sm);object-fit:cover;border:1px solid var(--fb-border);"
                                     onerror="this.onerror=null; this.src='/images/default-cover.svg';">
                            @else
                                <div style="width:68px;height:38px;border-radius:var(--radius-sm);background:linear-gradient(135deg, #1877f2, #00c6ff);display:flex;align-items:center;justify-content:center;color:#fff;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                </div>
                            @endif
                            <div>
                                <div style="font-weight:700;font-size:13px;color:var(--fb-text-primary);">কভার ফটো</div>
                                <div style="font-size:11px;color:var(--fb-text-secondary);">নতুন কভার আপলোড বা লাইভ পজিশন ঠিক করুন</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:6px;">
                            <button type="button" class="fb-btn fb-btn-secondary" style="font-size:12px;padding:4px 10px;display:inline-flex;align-items:center;gap:4px;" onclick="openCoverModal()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                <span>কভার আপলোড</span>
                            </button>
                            <button type="button" class="fb-btn fb-btn-secondary" style="font-size:12px;padding:4px 10px;display:inline-flex;align-items:center;gap:4px;" onclick="closeModal('editProfileModal');startLiveCoverReposition()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
                                <span>পজিশন</span>
                            </button>
                        </div>
                    </div>

                    <!-- Panel 1: Basic Info -->
                    <div class="edit-tab-panel active" id="editPanel-basic">
                        <div class="form-group">
                            <label class="form-label">পূর্ণ নাম (Full Name)</label>
                            <input type="text" name="name" class="form-control" value="{{ $profile['name'] }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ডিসপ্লে নাম (Display Name)</label>
                            <input type="text" name="display_name" class="form-control" value="{{ $profile['display_name'] ?? $profile['name'] }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">প্রোফাইল স্লাগ / ইউজারনেম (Profile Slug)</label>
                            <input type="text" name="slug" class="form-control" placeholder="যেমন: mizanur-rahman" value="{{ $profile['slug'] ?? $profile['username'] }}">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="form-group">
                                <label class="form-label">মিডল নেম (Middle Name)</label>
                                <input type="text" name="middle_name" class="form-control" value="{{ $profile['middle_name'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">সর্বনাম (Pronouns)</label>
                                <input type="text" name="pronouns" class="form-control" placeholder="যেমন: He/Him, She/Her" value="{{ $profile['pronouns'] ?? '' }}">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="form-group">
                                <label class="form-label">ধর্ম (Religion)</label>
                                <input type="text" name="religion" class="form-control" placeholder="যেমন: ইসলাম, হিন্দু" value="{{ $profile['religion'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">রক্তের গ্রুপ (Blood Group)</label>
                                <select name="blood_group" class="form-control">
                                    <option value="">নির্বাচন করুন</option>
                                    @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg)
                                        <option value="{{ $bg }}" {{ ($profile['blood_group'] ?? '') === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">পেশাগত ক্যাটাগরি (Professional Category)</label>
                            <input type="text" name="category" class="form-control" placeholder="যেমন: সফটওয়্যার ইঞ্জিনিয়ার, ডিজিটাল ক্রিয়েটর" value="{{ $profile['category'] ?? '' }}">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="form-group">
                                <label class="form-label">লিঙ্গ (Gender)</label>
                                <select name="gender" class="form-control">
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="male" {{ ($profile['gender'] ?? '') === 'male' ? 'selected' : '' }}>পুরুষ (Male)</option>
                                    <option value="female" {{ ($profile['gender'] ?? '') === 'female' ? 'selected' : '' }}>নারী (Female)</option>
                                    <option value="other" {{ ($profile['gender'] ?? '') === 'other' ? 'selected' : '' }}>অন্যান্য (Other)</option>
                                    <option value="prefer_not_to_say" {{ ($profile['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : '' }}>বলতে অনিচ্ছুক</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">জন্মতারিখ (Date of Birth)</label>
                                <input type="date" name="birth_date" class="form-control" value="{{ isset($profile['birth_date']) ? \Illuminate\Support\Carbon::parse($profile['birth_date'])->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">সম্পর্কের অবস্থা (Relationship Status)</label>
                            <select name="relationship_status" class="form-control">
                                <option value="">নির্বাচন করুন</option>
                                <option value="single" {{ in_array(strtolower($profile['relationship_status'] ?? ''), ['single']) ? 'selected' : '' }}>সিঙ্গেল (Single)</option>
                                <option value="in_a_relationship" {{ in_array(strtolower($profile['relationship_status'] ?? ''), ['in a relationship', 'in_a_relationship']) ? 'selected' : '' }}>সম্পর্কে জড়িত (In a relationship)</option>
                                <option value="engaged" {{ in_array(strtolower($profile['relationship_status'] ?? ''), ['engaged']) ? 'selected' : '' }}>বাগদান সম্পন্ন (Engaged)</option>
                                <option value="married" {{ in_array(strtolower($profile['relationship_status'] ?? ''), ['married']) ? 'selected' : '' }}>বিবাহিত (Married)</option>
                                <option value="complicated" {{ in_array(strtolower($profile['relationship_status'] ?? ''), ['complicated']) ? 'selected' : '' }}>জটিল (It's complicated)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Panel 2: About, Location, Contact -->
                    <div class="edit-tab-panel" id="editPanel-about">
                        <div class="form-group">
                            <label class="form-label">সংক্ষিপ্ত বায়ো (Bio)</label>
                            <textarea name="bio" class="form-control" placeholder="নিজের সম্পর্কে সংক্ষেপে লিখুন...">{{ $profile['bio'] ?? '' }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">বিস্তারিত বিবরণ (Detailed About)</label>
                            <textarea name="about" class="form-control" style="min-height:90px;" placeholder="আপনার বিস্তারিত পরিচিতি লিখুন...">{{ $profile['about'] ?? '' }}</textarea>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="form-group">
                                <label class="form-label">বিভাগ (Division)</label>
                                <input type="text" name="division" class="form-control" placeholder="যেমন: ঢাকা" value="{{ $profile['division'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">জেলা (District)</label>
                                <input type="text" name="district" class="form-control" placeholder="যেমন: ঢাকা" value="{{ $profile['district'] ?? '' }}">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="form-group">
                                <label class="form-label">উপজেলা / থানা (Upazila)</label>
                                <input type="text" name="upazila" class="form-control" placeholder="যেমন: ধানমন্ডি" value="{{ $profile['upazila'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">বর্তমান শহর (City)</label>
                                <input type="text" name="city" class="form-control" placeholder="উদা: ঢাকা" value="{{ $profile['city'] ?? $profile['location'] ?? '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">নিজ জেলা / শহর (Hometown)</label>
                            <input type="text" name="hometown" class="form-control" placeholder="উদা: ময়মনসিংহ" value="{{ $profile['hometown'] ?? '' }}">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="form-group">
                                <label class="form-label">ওয়েবসাইট (Website)</label>
                                <input type="url" name="website" class="form-control" placeholder="https://example.com" value="{{ $profile['website'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">পোর্টফোলিও লিংক (Portfolio)</label>
                                <input type="url" name="portfolio" class="form-control" placeholder="https://portfolio.com" value="{{ $profile['portfolio'] ?? '' }}">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="form-group">
                                <label class="form-label">WhatsApp নম্বর</label>
                                <input type="text" name="whatsapp" class="form-control" placeholder="+8801..." value="{{ $profile['whatsapp'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Telegram হ্যান্ডেল</label>
                                <input type="text" name="telegram" class="form-control" placeholder="@username" value="{{ $profile['telegram'] ?? '' }}">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="form-group">
                                <label class="form-label">Messenger হ্যান্ডেল</label>
                                <input type="text" name="messenger" class="form-control" placeholder="username" value="{{ $profile['messenger'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Signal নম্বর/হ্যান্ডেল</label>
                                <input type="text" name="signal" class="form-control" placeholder="+8801..." value="{{ $profile['signal'] ?? '' }}">
                            </div>
                        </div>
                    </div>

                    <!-- Panel 3: Work & Education -->
                    <div class="edit-tab-panel" id="editPanel-work_edu">
                        <!-- Summary Inputs -->
                        <div class="form-group">
                            <label class="form-label">কর্মক্ষেত্র সংক্ষেপ (Work Summary)</label>
                            <input type="text" name="work" class="form-control" placeholder="উদা: সফটওয়্যার ইঞ্জিনিয়ার @ কোম্পানি" value="{{ $profile['work'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">শিক্ষা প্রতিষ্ঠান সংক্ষেপ (Education Summary)</label>
                            <input type="text" name="education" class="form-control" placeholder="উদা: ঢাকা বিশ্ববিদ্যালয়" value="{{ $profile['education'] ?? '' }}">
                        </div>

                        <!-- Structured Work Experience Section -->
                        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--fb-divider);">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                                <label class="form-label" style="font-weight:700;margin-bottom:0;">💼 কর্মসংস্থান ও অভিজ্ঞতা তালিকা</label>
                                <button type="button" class="fb-btn fb-btn-secondary" onclick="toggleAddWorkForm()" style="padding:4px 10px;font-size:12px;">➕ অভিজ্ঞতা যোগ করুন</button>
                            </div>

                            <!-- Add Work Form (Collapsible) -->
                            <div id="addWorkInlineForm" style="display:none;background:var(--fb-bg);border:1px solid var(--fb-border);border-radius:var(--radius-sm);padding:12px;margin-bottom:14px;">
                                <div style="font-weight:700;font-size:13px;margin-bottom:8px;color:var(--fb-primary);">নতুন কর্মসংস্থান যোগ করুন</div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                    <div>
                                        <label style="font-size:11px;font-weight:600;color:var(--fb-text-secondary);">প্রতিষ্ঠানের নাম *</label>
                                        <input type="text" id="newWorkCompany" class="form-control" style="font-size:13px;height:34px;" placeholder="কোম্পানির নাম">
                                    </div>
                                    <div>
                                        <label style="font-size:11px;font-weight:600;color:var(--fb-text-secondary);">পদবি *</label>
                                        <input type="text" id="newWorkTitle" class="form-control" style="font-size:13px;height:34px;" placeholder="উদা: সফটওয়্যার ইঞ্জিনিয়ার">
                                    </div>
                                    <div>
                                        <label style="font-size:11px;font-weight:600;color:var(--fb-text-secondary);">চাকরির ধরন</label>
                                        <select id="newWorkType" class="form-control" style="font-size:13px;height:34px;">
                                            <option value="full_time">পূর্ণকালীন (Full-time)</option>
                                            <option value="part_time">খন্ডকালীন (Part-time)</option>
                                            <option value="freelance">ফ্রিল্যান্স (Freelance)</option>
                                            <option value="contract">চুক্তিভিত্তিক (Contract)</option>
                                            <option value="internship">ইন্টার্নশিপ (Internship)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-size:11px;font-weight:600;color:var(--fb-text-secondary);">স্থান/শহর</label>
                                        <input type="text" id="newWorkLocation" class="form-control" style="font-size:13px;height:34px;" placeholder="ঢাকা, বাংলাদেশ">
                                    </div>
                                    <div style="grid-column:1/-1;display:flex;align-items:center;gap:8px;margin:4px 0;">
                                        <input type="checkbox" id="newWorkIsCurrent" checked>
                                        <label for="newWorkIsCurrent" style="font-size:12px;font-weight:600;cursor:pointer;">বর্তমানে এখানে কর্মরত</label>
                                    </div>
                                </div>
                                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px;">
                                    <button type="button" class="fb-btn fb-btn-secondary" onclick="toggleAddWorkForm()" style="padding:4px 10px;font-size:12px;">বাতিল</button>
                                    <button type="button" class="fb-btn fb-btn-primary" onclick="submitInlineWork()" style="padding:4px 12px;font-size:12px;">সংরক্ষণ করুন</button>
                                </div>
                            </div>

                            <!-- List of Experiences -->
                            <div id="workExperienceListContainer" style="display:flex;flex-direction:column;gap:8px;">
                                @forelse($profile['sections']['experiences'] ?? [] as $exp)
                                    <div class="work-item-row" id="workRow_{{ $exp['id'] ?? $loop->index }}" style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--fb-hover);border-radius:var(--radius-sm);font-size:13px;">
                                        <div>
                                            <strong>{{ $exp['job_title'] }}</strong> @ {{ $exp['company_name'] }}
                                            <div style="font-size:11px;color:var(--fb-text-secondary);">
                                                {{ $exp['employment_type'] ?? 'চাকরি' }} • {{ $exp['location'] ?? 'অন-সাইট' }}
                                                @if(!empty($exp['is_current'])) (বর্তমান) @endif
                                            </div>
                                        </div>
                                        @if(!empty($exp['id']))
                                            <button type="button" class="fb-btn fb-btn-secondary" onclick="deleteInlineWork({{ $exp['id'] }}, this)" style="padding:2px 8px;font-size:11px;color:var(--fb-red);" title="মুছে ফেলুন">✕</button>
                                        @endif
                                    </div>
                                @empty
                                    <div id="noWorkPlaceholder" style="font-size:12px;color:var(--fb-text-secondary);font-style:italic;">এখনও কোনো বিস্তারিত কর্মসংস্থান যোগ করা হয়নি।</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Structured Education Section -->
                        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--fb-divider);">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                                <label class="form-label" style="font-weight:700;margin-bottom:0;">🎓 শিক্ষাগত যোগ্যতা তালিকা</label>
                                <button type="button" class="fb-btn fb-btn-secondary" onclick="toggleAddEduForm()" style="padding:4px 10px;font-size:12px;">➕ শিক্ষা প্রতিষ্ঠান যোগ করুন</button>
                            </div>

                            <!-- Add Edu Form (Collapsible) -->
                            <div id="addEduInlineForm" style="display:none;background:var(--fb-bg);border:1px solid var(--fb-border);border-radius:var(--radius-sm);padding:12px;margin-bottom:14px;">
                                <div style="font-weight:700;font-size:13px;margin-bottom:8px;color:var(--fb-primary);">নতুন শিক্ষাগত যোগ্যতা যোগ করুন</div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                    <div style="grid-column:1/-1;">
                                        <label style="font-size:11px;font-weight:600;color:var(--fb-text-secondary);">প্রতিষ্ঠান/বিশ্ববিদ্যালয়ের নাম *</label>
                                        <input type="text" id="newEduInstitution" class="form-control" style="font-size:13px;height:34px;" placeholder="উদা: ঢাকা বিশ্ববিদ্যালয়">
                                    </div>
                                    <div>
                                        <label style="font-size:11px;font-weight:600;color:var(--fb-text-secondary);">ডিগ্রি/শ্রেণি</label>
                                        <input type="text" id="newEduDegree" class="form-control" style="font-size:13px;height:34px;" placeholder="উদা: বিএসসি, এইচএসসি, এসএসসি">
                                    </div>
                                    <div>
                                        <label style="font-size:11px;font-weight:600;color:var(--fb-text-secondary);">বিষয়/বিভাগ (Field of study)</label>
                                        <input type="text" id="newEduField" class="form-control" style="font-size:13px;height:34px;" placeholder="উদা: কম্পিউটার সায়েন্স">
                                    </div>
                                    <div style="grid-column:1/-1;display:flex;align-items:center;gap:8px;margin:4px 0;">
                                        <input type="checkbox" id="newEduIsCurrent" checked>
                                        <label for="newEduIsCurrent" style="font-size:12px;font-weight:600;cursor:pointer;">বর্তমানে এখানে অধ্যয়নরত</label>
                                    </div>
                                </div>
                                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px;">
                                    <button type="button" class="fb-btn fb-btn-secondary" onclick="toggleAddEduForm()" style="padding:4px 10px;font-size:12px;">বাতিল</button>
                                    <button type="button" class="fb-btn fb-btn-primary" onclick="submitInlineEdu()" style="padding:4px 12px;font-size:12px;">সংরক্ষণ করুন</button>
                                </div>
                            </div>

                            <!-- List of Educations -->
                            <div id="educationListContainer" style="display:flex;flex-direction:column;gap:8px;">
                                @forelse($profile['sections']['educations'] ?? [] as $edu)
                                    <div class="edu-item-row" id="eduRow_{{ $edu['id'] ?? $loop->index }}" style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--fb-hover);border-radius:var(--radius-sm);font-size:13px;">
                                        <div>
                                            <strong>{{ $edu['institution_name'] }}</strong>
                                            <div style="font-size:11px;color:var(--fb-text-secondary);">
                                                {{ $edu['degree'] ?? '' }} {{ !empty($edu['field_of_study']) ? '('.$edu['field_of_study'].')' : '' }}
                                                @if(!empty($edu['is_current'])) • অধ্যয়নরত @endif
                                            </div>
                                        </div>
                                        @if(!empty($edu['id']))
                                            <button type="button" class="fb-btn fb-btn-secondary" onclick="deleteInlineEdu({{ $edu['id'] }}, this)" style="padding:2px 8px;font-size:11px;color:var(--fb-red);" title="মুছে ফেলুন">✕</button>
                                        @endif
                                    </div>
                                @empty
                                    <div id="noEduPlaceholder" style="font-size:12px;color:var(--fb-text-secondary);font-style:italic;">এখনও কোনো বিস্তারিত শিক্ষাগত যোগ্যতা যোগ করা হয়নি।</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Panel 4: Skills, Interests, Favorites -->
                    <div class="edit-tab-panel" id="editPanel-skills_lang">
                        <div class="form-group">
                            <label class="form-label">দক্ষতা সমূহ (Skills — কমা দিয়ে আলাদা করুন)</label>
                            @php
                                $skillsArr = isset($profile['sections']['skills']) && is_array($profile['sections']['skills'])
                                    ? array_column($profile['sections']['skills'], 'name')
                                    : [];
                            @endphp
                            <input type="text" name="skills_input" class="form-control" placeholder="উদা: PHP, Laravel, React, Cloud" value="{{ implode(', ', $skillsArr) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">আগ্রহ ও শখ (Hobbies — কমা দিয়ে আলাদা করুন)</label>
                            <input type="text" name="hobbies_input" class="form-control" placeholder="উদা: বই পড়া, ফটোগ্রাফি, বাগান করা" value="{{ is_array($profile['hobbies'] ?? null) ? implode(', ', $profile['hobbies']) : '' }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">প্রিয় গান ও মিউজিক (Favorite Music)</label>
                            <input type="text" name="music_input" class="form-control" placeholder="উদা: রবীন্দ্রসঙ্গীত, রক, ক্লাসিক্যাল" value="{{ is_array($profile['favorite_music'] ?? null) ? implode(', ', $profile['favorite_music']) : '' }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">প্রিয় বই (Favorite Books)</label>
                            <input type="text" name="books_input" class="form-control" placeholder="উদা: শেষের কবিতা, একাত্তরের দিনগুলি" value="{{ is_array($profile['favorite_books'] ?? null) ? implode(', ', $profile['favorite_books']) : '' }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">প্রিয় সিনেমা ও সিরিজ (Favorite Movies)</label>
                            <input type="text" name="movies_input" class="form-control" placeholder="উda: মাটির ময়না, ইন্টারস্টেলার" value="{{ is_array($profile['favorite_movies'] ?? null) ? implode(', ', $profile['favorite_movies']) : '' }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">জানা ভাষা সমূহ (Languages — কমা দিয়ে আলাদা করুন)</label>
                            @php
                                $langArr = isset($profile['sections']['languages']) && is_array($profile['sections']['languages'])
                                    ? array_column($profile['sections']['languages'], 'language')
                                    : [];
                            @endphp
                            <input type="text" name="languages_input" class="form-control" placeholder="উদা: বাংলা, ইংরেজি, হিন্দি" value="{{ implode(', ', $langArr) }}">
                        </div>
                    </div>

                    <!-- Panel 5: Social Links -->
                    <div class="edit-tab-panel" id="editPanel-social">
                        @php
                            $socialMap = [];
                            if (isset($profile['sections']['social_links']) && is_array($profile['sections']['social_links'])) {
                                foreach ($profile['sections']['social_links'] as $linkItem) {
                                    if (is_array($linkItem) && isset($linkItem['platform'])) {
                                        $socialMap[strtolower($linkItem['platform'])] = $linkItem['url'] ?? '';
                                    }
                                }
                            }
                            if (empty($socialMap) && isset($profile['social_links']) && is_array($profile['social_links'])) {
                                $socialMap = $profile['social_links'];
                            }
                        @endphp
                        <div class="form-group">
                            <label class="form-label">Facebook Profile URL</label>
                            <input type="url" name="social_facebook" class="form-control" placeholder="https://facebook.com/yourprofile" value="{{ $socialMap['facebook'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Twitter / X URL</label>
                            <input type="url" name="social_twitter" class="form-control" placeholder="https://x.com/yourhandle" value="{{ $socialMap['twitter'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">GitHub URL</label>
                            <input type="url" name="social_github" class="form-control" placeholder="https://github.com/yourusername" value="{{ $socialMap['github'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">LinkedIn URL</label>
                            <input type="url" name="social_linkedin" class="form-control" placeholder="https://linkedin.com/in/yourname" value="{{ $socialMap['linkedin'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Instagram URL</label>
                            <input type="url" name="social_instagram" class="form-control" placeholder="https://instagram.com/yourhandle" value="{{ $socialMap['instagram'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">YouTube Channel URL</label>
                            <input type="url" name="social_youtube" class="form-control" placeholder="https://youtube.com/@yourchannel" value="{{ $socialMap['youtube'] ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('editProfileModal')">বাতিল</button>
                    <button type="submit" class="fb-btn fb-btn-primary" id="saveProfileBtn">সংরক্ষণ করুন</button>
                </div>
            </form>
        </div>
    </div>

    <!-- AVATAR FRAME MODAL -->
    <div class="fb-modal-overlay" id="avatarFrameModal">
        <div class="fb-modal-card" style="max-width: 500px;">
            <div class="modal-header">
                <span class="modal-title">প্রোফাইল ছবির ফ্রেম নির্বাচন</span>
                <button class="modal-close-btn" onclick="closeModal('avatarFrameModal')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size:14px;color:var(--fb-text-secondary);margin-bottom:16px;">আপনার প্রোফাইল ছবিতে আকর্ষণীয় ফ্রেম যুক্ত করুন:</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="frame-choice-box" style="padding:12px;border:2px solid #e4e6eb;border-radius:var(--radius-md);cursor:pointer;text-align:center;" onclick="selectAvatarFrame('')">
                        <div style="font-size:24px;">🚫</div>
                        <div style="font-weight:600;font-size:14px;margin-top:4px;">কোনো ফ্রেম নেই</div>
                    </div>
                    <div class="frame-choice-box" style="padding:12px;border:2px solid #f59e0b;border-radius:var(--radius-md);cursor:pointer;text-align:center;background:#fffbeb;" onclick="selectAvatarFrame('gold')">
                        <div style="font-size:24px;">👑</div>
                        <div style="font-weight:600;font-size:14px;color:#b45309;margin-top:4px;">গোল্ডেন ভিআইপি</div>
                    </div>
                    <div class="frame-choice-box" style="padding:12px;border:2px solid #059669;border-radius:var(--radius-md);cursor:pointer;text-align:center;background:#ecfdf5;" onclick="selectAvatarFrame('bd_flag')">
                        <div style="font-size:24px;">🇧🇩</div>
                        <div style="font-weight:600;font-size:14px;color:#047857;margin-top:4px;">বাংলাদেশ গর্ব</div>
                    </div>
                    <div class="frame-choice-box" style="padding:12px;border:2px solid #8b5cf6;border-radius:var(--radius-md);cursor:pointer;text-align:center;background:#f5f3ff;" onclick="selectAvatarFrame('creator')">
                        <div style="font-size:24px;">🎨</div>
                        <div style="font-weight:600;font-size:14px;color:#6d28d9;margin-top:4px;">প্রো ক্রিয়েটর</div>
                    </div>
                    <div class="frame-choice-box" style="padding:12px;border:2px solid #0ea5e9;border-radius:var(--radius-md);cursor:pointer;text-align:center;background:#f0f9ff;" onclick="selectAvatarFrame('tech')">
                        <div style="font-size:24px;">💻</div>
                        <div style="font-weight:600;font-size:14px;color:#0369a1;margin-top:4px;">টেক গিক</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('avatarFrameModal')">বাতিল</button>
            </div>
        </div>
    </div>

    <!-- COVER REPOSITION MODAL -->
    <div class="fb-modal-overlay" id="coverRepositionModal">
        <div class="fb-modal-card" style="max-width: 550px;">
            <div class="modal-header">
                <span class="modal-title">কভার ছবির পজিশন ঠিক করুন</span>
                <button class="modal-close-btn" onclick="closeModal('coverRepositionModal')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;color:var(--fb-text-secondary);margin-bottom:12px;">স্লাইডার টেনে ছবির উল্লম্ব পজিশন (Vertical Position) নির্ধারণ করুন:</p>
                <input type="range" id="coverPositionSlider" min="0" max="100" value="{{ $user->profile->cover_position_y ?? 50 }}" style="width:100%;cursor:pointer;" oninput="updateCoverPositionLive(this.value)">
                <div style="text-align:center;font-weight:700;margin-top:8px;font-size:14px;" id="coverPosValueLabel">{{ $user->profile->cover_position_y ?? 50 }}%</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('coverRepositionModal')">বাতিল</button>
                <button type="button" class="fb-btn fb-btn-primary" onclick="saveCoverReposition()">সংরক্ষণ করুন</button>
            </div>
        </div>
    </div>

    <!-- AVATAR & COVER HISTORY MODALS -->
    <div class="fb-modal-overlay" id="avatarHistoryModal">
        <div class="fb-modal-card" style="max-width: 600px;">
            <div class="modal-header">
                <span class="modal-title">পূর্ববর্তী প্রোফাইল ছবির ইতিহাস</span>
                <button class="modal-close-btn" onclick="closeModal('avatarHistoryModal')">✕</button>
            </div>
            <div class="modal-body" style="max-height:60vh;overflow-y:auto;">
                <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(130px, 1fr));gap:12px;">
                    @forelse($avatarHistory ?? [] as $aphoto)
                        <div style="border:1px solid var(--fb-divider);border-radius:var(--radius-md);overflow:hidden;text-align:center;padding:8px;">
                            <img src="{{ $aphoto->photo_path }}" style="width:100%;height:100px;object-fit:cover;border-radius:var(--radius-sm);">
                            <div style="font-size:11px;color:var(--fb-text-secondary);margin:4px 0;">{{ $aphoto->created_at->format('d M Y') }}</div>
                            @if(!$aphoto->is_current)
                                <button type="button" class="fb-btn fb-btn-secondary" style="font-size:11px;padding:2px 6px;width:100%;" onclick="restoreAvatar({{ $aphoto->id }})">
                                    পুনরুদ্ধার
                                </button>
                            @else
                                <span style="font-size:11px;color:#059669;font-weight:700;">বর্তমান ছবি</span>
                            @endif
                        </div>
                    @empty
                        <div style="grid-column:1/-1;text-align:center;padding:24px;color:var(--fb-text-secondary);">
                            কোনো পূর্ববর্তী ছবির ইতিহাস নেই।
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('avatarHistoryModal')">বন্ধ করুন</button>
            </div>
        </div>
    </div>

    <div class="fb-modal-overlay" id="coverHistoryModal">
        <div class="fb-modal-card" style="max-width: 600px;">
            <div class="modal-header">
                <span class="modal-title">পূর্ববর্তী কভার ছবির ইতিহাস</span>
                <button class="modal-close-btn" onclick="closeModal('coverHistoryModal')">✕</button>
            </div>
            <div class="modal-body" style="max-height:60vh;overflow-y:auto;">
                <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(160px, 1fr));gap:12px;">
                    @forelse($coverHistory ?? [] as $cphoto)
                        <div style="border:1px solid var(--fb-divider);border-radius:var(--radius-md);overflow:hidden;text-align:center;padding:8px;">
                            <img src="{{ $cphoto->photo_path }}" style="width:100%;height:80px;object-fit:cover;border-radius:var(--radius-sm);">
                            <div style="font-size:11px;color:var(--fb-text-secondary);margin:4px 0;">{{ $cphoto->created_at->format('d M Y') }}</div>
                            @if($cphoto->is_current)
                                <span style="font-size:11px;color:#059669;font-weight:700;">বর্তমান কভার</span>
                            @endif
                        </div>
                    @empty
                        <div style="grid-column:1/-1;text-align:center;padding:24px;color:var(--fb-text-secondary);">
                            কোনো পূর্ববর্তী কভার ছবির ইতিহাস নেই।
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('coverHistoryModal')">বন্ধ করুন</button>
            </div>
        </div>
    </div>

    <!-- CREATE ALBUM MODAL -->
    <div class="fb-modal-overlay" id="createAlbumModal">
        <div class="fb-modal-card" style="max-width: 500px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    <span>নতুন ফটো অ্যালবাম তৈরি</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('createAlbumModal')">✕</button>
            </div>
            <form onsubmit="submitCreateAlbum(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">অ্যালবামের নাম (Album Title)</label>
                        <input type="text" id="albumTitleInput" class="form-control" placeholder="যেমন: কক্সবাজার ভ্রমণ ২০২৬" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">বিবরণ (Description)</label>
                        <textarea id="albumDescInput" class="form-control" placeholder="অ্যালবামের বিবরণ লিখুন..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">কভার ছবি URL (ঐচ্ছিক)</label>
                        <input type="url" id="albumCoverInput" class="form-control" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">প্রাইভেসি (Privacy)</label>
                        <select id="albumPrivacySelect" class="form-control">
                            <option value="public">পাবলিক (Public)</option>
                            <option value="friends">বন্ধুরা (Friends)</option>
                            <option value="only_me">শুধুমাত্র আমি (Only Me)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('createAlbumModal')">বাতিল</button>
                    <button type="submit" class="fb-btn fb-btn-primary" id="saveAlbumBtn">অ্যালবাম তৈরি করুন</button>
                </div>
            </form>
        </div>
    </div>

    <!-- CREATE HIGHLIGHT / FEATURED MODAL -->
    <div class="fb-modal-overlay" id="createHighlightModal">
        <div class="fb-modal-card" style="max-width: 480px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <span>নতুন ফিচারড কালেকশন / হাইলাইট</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('createHighlightModal')">✕</button>
            </div>
            <form onsubmit="submitCreateHighlight(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" style="font-weight:600;">কালেকশনের শিরোনাম (Title) <span style="color:#e41e3f;">*</span></label>
                        <input type="text" id="highlightTitleInput" class="form-control" placeholder="যেমন: প্রিয় মুহূর্ত, ভ্রমণ, পরিবার, মেমোরিজ" required>
                    </div>

                    @if(isset($photos) && count($photos) > 0)
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">আপনার আপলোড করা ছবি থেকে কভার বেছে নিন:</label>
                            <div style="display:flex;gap:8px;overflow-x:auto;padding:4px 2px 8px;scrollbar-width:thin;">
                                @foreach(array_slice($photos, 0, 10) as $ph)
                                    <img src="{{ $ph['url'] ?? '' }}"
                                         alt="Photo thumbnail"
                                         class="selectable-cover-thumb"
                                         onclick="selectHighlightCover('{{ $ph['url'] ?? '' }}', this)"
                                         title="কভার হিসেবে নির্বাচন করুন"
                                         onerror="this.onerror=null; this.style.display='none';">
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label" style="font-weight:600;">কভার ছবি URL (বা কাস্টম লিংক)</label>
                        <input type="url" id="highlightCoverInput" class="form-control" placeholder="https://example.com/photo.jpg">
                        <div style="font-size:11px;color:var(--fb-text-secondary);margin-top:4px;">
                            উপরের ছবি থেকে ক্লিক করুন অথবা যেকোনো ছবির লিংক পেস্ট করুন।
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('createHighlightModal')">বাতিল</button>
                    <button type="submit" class="fb-btn fb-btn-primary">সংরক্ষণ করুন</button>
                </div>
            </form>
        </div>
    </div>

    <!-- REEL PLAYER MODAL -->
    <div class="fb-modal-overlay" id="reelPlayerModal" onclick="if(event.target===this) closeReelPlayerModal();">
        <div class="fb-modal-card" style="max-width: 420px;padding:0;background:#000;border:none;overflow:hidden;border-radius:16px;">
            <div style="position:relative;width:100%;height:650px;">
                <button class="modal-close-btn" onclick="closeReelPlayerModal()" style="position:absolute;top:12px;right:12px;z-index:20;background:rgba(0,0,0,0.6);color:#fff;border-radius:50%;width:36px;height:36px;border:none;cursor:pointer;">✕</button>
                <video id="activeReelVideo" src="" controls autoplay playsinline style="width:100%;height:100%;object-fit:cover;"></video>
                <div style="position:absolute;bottom:0;left:0;right:0;padding:24px 16px;background:linear-gradient(transparent, rgba(0,0,0,0.85));color:#fff;pointer-events:none;z-index:10;">
                    <div style="font-weight:700;font-size:15px;" id="activeReelAuthorName">{{ $profile['name'] }}</div>
                    <div style="font-size:13px;margin-top:4px;opacity:0.9;" id="activeReelCaption"></div>
                    <div style="display:flex;align-items:center;gap:16px;margin-top:8px;font-size:12px;opacity:0.85;">
                        <span id="activeReelLikes">❤️ 0 লাইক</span>
                        <span id="activeReelComments">💬 0 মন্তব্য</span>
                        <span style="color:#facc15;">⏳ ২৪ ঘণ্টা সক্রিয়</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ACTIVE 24-HOUR STORY PLAYER MODAL -->
    <div class="fb-modal-overlay" id="activeStoryViewerModal" onclick="handleActiveStoryOverlayClick(event)">
        <div class="story-highlight-player-card">
            <!-- Progress bars container -->
            <div id="activeStoryProgressBars" class="highlight-progress-bars"></div>

            <!-- Top Header Overlay -->
            <div class="highlight-header-overlay">
                <div class="highlight-header-left">
                    <img src="{{ $profile['avatar'] ?: '/images/default-avatar.svg' }}" alt="{{ $profile['name'] }}" class="highlight-header-avatar" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                    <div class="highlight-header-info">
                        <span class="highlight-header-title">{{ $profile['name'] }}</span>
                        <span class="highlight-header-sub" id="activeStoryTimeRemaining">২৪ ঘণ্টা স্টোরি</span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" class="highlight-ctrl-btn" id="activeStoryPauseBtn" onclick="toggleActiveStoryPause()" title="Pause / Play">⏸️</button>
                    <button type="button" class="highlight-ctrl-btn" onclick="closeActiveStoryViewer()" title="বন্ধ করুন">✕</button>
                </div>
            </div>

            <!-- Media Stage with Tap Navigators -->
            <div class="highlight-media-stage" id="activeStoryMediaStage">
                <img id="activeStoryImage" src="" alt="Story" style="display:none;width:100%;height:100%;object-fit:cover;">
                <video id="activeStoryVideo" src="" playsinline style="display:none;width:100%;height:100%;object-fit:cover;"></video>
                <div id="activeStoryCaptionOverlay" style="position:absolute;bottom:70px;left:16px;right:16px;background:rgba(0,0,0,0.6);color:#fff;padding:8px 12px;border-radius:10px;font-size:14px;backdrop-filter:blur(4px);display:none;"></div>

                <!-- Left / Right Tap zones -->
                <div class="highlight-tap-prev" onclick="prevActiveStorySlide()"></div>
                <div class="highlight-tap-next" onclick="nextActiveStorySlide()"></div>

                <!-- Nav Arrow Buttons -->
                <button type="button" class="highlight-nav-arrow prev" onclick="prevActiveStorySlide()" title="আগের স্টোরি">‹</button>
                <button type="button" class="highlight-nav-arrow next" onclick="nextActiveStorySlide()" title="পরের স্টোরি">›</button>
            </div>

            <!-- Reaction bar at bottom -->
            <div style="position:absolute;bottom:12px;left:16px;right:16px;z-index:20;display:flex;align-items:center;gap:8px;">
                <button type="button" onclick="reactToActiveStory('like')" style="flex:1;background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);color:#fff;padding:8px;border-radius:20px;cursor:pointer;font-size:13px;backdrop-filter:blur(4px);">👍 লাইক</button>
                <button type="button" onclick="reactToActiveStory('love')" style="flex:1;background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);color:#fff;padding:8px;border-radius:20px;cursor:pointer;font-size:13px;backdrop-filter:blur(4px);">❤️ লাভ</button>
            </div>
        </div>
    </div>

    <!-- STORY HIGHLIGHT PLAYER MODAL -->
    <div class="fb-modal-overlay" id="storyHighlightModal" onclick="handleHighlightOverlayClick(event)">
        <div class="story-highlight-player-card">
            <!-- Progress bars container -->
            <div id="highlightProgressBars" class="highlight-progress-bars"></div>

            <!-- Top Header Overlay -->
            <div class="highlight-header-overlay">
                <div class="highlight-header-left">
                    <img src="{{ $profile['avatar'] ?: '/images/default-avatar.svg' }}" alt="{{ $profile['name'] }}" class="highlight-header-avatar" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                    <div class="highlight-header-info">
                        <span class="highlight-header-title" id="highlightModalTitle">স্টোরি হাইলাইট</span>
                        <span class="highlight-header-sub" id="highlightModalCounter">1 / 1</span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" class="highlight-ctrl-btn" id="highlightPauseBtn" onclick="toggleHighlightPause()" title="Pause / Play">⏸️</button>
                    <button type="button" class="highlight-ctrl-btn" onclick="closeStoryHighlightViewer()" title="বন্ধ করুন">✕</button>
                </div>
            </div>

            <!-- Media Stage with Tap Navigators -->
            <div class="highlight-media-stage" id="highlightMediaStage">
                <img id="highlightImage" src="" alt="Story Highlight" style="display:none;">
                <video id="highlightVideo" src="" playsinline style="display:none;"></video>
                
                <!-- Left / Right Tap zones -->
                <div class="highlight-tap-prev" onclick="prevHighlightSlide()"></div>
                <div class="highlight-tap-next" onclick="nextHighlightSlide()"></div>

                <!-- Nav Arrow Buttons -->
                <button type="button" class="highlight-nav-arrow prev" onclick="prevHighlightSlide()" title="আগের স্টোরি">‹</button>
                <button type="button" class="highlight-nav-arrow next" onclick="nextHighlightSlide()" title="পরের স্টোরি">›</button>
            </div>
        </div>
    </div>

    <!-- FOLLOWERS & FOLLOWING MODAL -->
    <div class="fb-modal-overlay" id="profileFollowModal">
        <div class="fb-modal-card" style="max-width: 500px; padding: 0; overflow: hidden;">
            <div class="modal-header" style="padding: 12px 16px; border-bottom: 1px solid var(--fb-divider); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="fb-btn" id="followModalTabFollowers" onclick="switchFollowModalTab('followers')" style="background: transparent; font-weight: 700; font-size: 15px; border-bottom: 3px solid var(--fb-primary); border-radius: 0; padding: 8px 12px; color: var(--fb-primary);">
                        ফলোয়ার ({{ count($followers ?? []) }})
                    </button>
                    <button type="button" class="fb-btn" id="followModalTabFollowing" onclick="switchFollowModalTab('following')" style="background: transparent; font-weight: 600; font-size: 15px; border-bottom: 3px solid transparent; border-radius: 0; padding: 8px 12px; color: var(--fb-text-secondary);">
                        ফলোয়িং ({{ count($following ?? []) }})
                    </button>
                </div>
                <button class="modal-close-btn" onclick="closeModal('profileFollowModal')">✕</button>
            </div>

            <!-- Search box within modal -->
            <div style="padding: 10px 16px; border-bottom: 1px solid var(--fb-divider); background: var(--fb-bg);">
                <input type="text" id="followSearchInput" class="form-control" placeholder="নাম বা ইউজারনেম দিয়ে খুঁজুন..." oninput="filterFollowList(this.value)" style="border-radius: 20px; font-size: 13px; height: 36px;">
            </div>

            <div class="modal-body" style="max-height: 420px; overflow-y: auto; padding: 12px 16px;">
                <!-- Followers List Panel -->
                <div id="followPanelFollowers">
                    @forelse($followers ?? [] as $flw)
                        @php
                            $flwProfile = $flw->profile;
                            $flwAvatar = $flwProfile->avatar_url ?? null;
                            if (!$flwAvatar) {
                                $flwAvatar = '/images/default-avatar.svg';
                            }
                        @endphp
                        <div class="follow-user-item" data-name="{{ strtolower($flw->name) }}" data-username="{{ strtolower($flw->username) }}" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--fb-divider);">
                            <a href="{{ route('profile.handle', ['username' => $flw->username]) }}" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; flex: 1;">
                                <img src="{{ $flwAvatar }}" alt="{{ $flw->name }}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1px solid var(--fb-divider);" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                <div>
                                    <div style="font-weight: 700; font-size: 14px; color: var(--fb-text-primary); display: flex; align-items: center; gap: 4px;">
                                        {{ $flw->name }}
                                        @if($flw->is_verified)
                                            <span style="color: var(--fb-primary); font-size: 13px;">✓</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">{{ '@' . $flw->username }}</div>
                                </div>
                            </a>
                            @if(auth()->check() && auth()->id() !== $flw->id)
                                <button type="button" class="fb-btn fb-btn-secondary" id="followBtn_{{ $flw->id }}" onclick="toggleFollowUser({{ $flw->id }}, this)" style="padding: 4px 12px; font-size: 13px;">
                                    {{ auth()->user()->isFollowing($flw) ? 'ফলোয়িং' : 'ফলো করুন' }}
                                </button>
                            @endif
                        </div>
                    @empty
                        <div style="text-align: center; padding: 30px 16px; color: var(--fb-text-secondary); font-size: 14px;">
                            <div style="font-size: 32px; margin-bottom: 8px;">👥</div>
                            এখনও কোনো ফলোয়ার নেই।
                        </div>
                    @endforelse
                </div>

                <!-- Following List Panel -->
                <div id="followPanelFollowing" style="display: none;">
                    @forelse($following ?? [] as $flw)
                        @php
                            $flwProfile = $flw->profile;
                            $flwAvatar = $flwProfile->avatar_url ?? null;
                            if (!$flwAvatar) {
                                $flwAvatar = '/images/default-avatar.svg';
                            }
                        @endphp
                        <div class="follow-user-item" data-name="{{ strtolower($flw->name) }}" data-username="{{ strtolower($flw->username) }}" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--fb-divider);">
                            <a href="{{ route('profile.handle', ['username' => $flw->username]) }}" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; flex: 1;">
                                <img src="{{ $flwAvatar }}" alt="{{ $flw->name }}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1px solid var(--fb-divider);" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                <div>
                                    <div style="font-weight: 700; font-size: 14px; color: var(--fb-text-primary); display: flex; align-items: center; gap: 4px;">
                                        {{ $flw->name }}
                                        @if($flw->is_verified)
                                            <span style="color: var(--fb-primary); font-size: 13px;">✓</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">{{ '@' . $flw->username }}</div>
                                </div>
                            </a>
                            @if(auth()->check() && auth()->id() !== $flw->id)
                                <button type="button" class="fb-btn fb-btn-secondary" id="followBtn_{{ $flw->id }}" onclick="toggleFollowUser({{ $flw->id }}, this)" style="padding: 4px 12px; font-size: 13px;">
                                    {{ auth()->user()->isFollowing($flw) ? 'ফলোয়িং' : 'ফলো করুন' }}
                                </button>
                            @endif
                        </div>
                    @empty
                        <div style="text-align: center; padding: 30px 16px; color: var(--fb-text-secondary); font-size: 14px;">
                            <div style="font-size: 32px; margin-bottom: 8px;">🔍</div>
                            কাউকে ফলো করছেন না।
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- PROFILE SHARE & QR CODE MODAL -->
    <div class="fb-modal-overlay" id="profileShareModal">
        <div class="fb-modal-card" style="max-width: 460px; text-align: center;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;justify-content:center;gap:6px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                    <span>প্রোফাইল শেয়ার ও কিউআর কোড</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('profileShareModal')">✕</button>
            </div>
            <div class="modal-body" style="padding: 20px 16px;">
                <!-- QR Code Container -->
                @php
                    $profileUrl = route('profile.handle', ['username' => $profile['username']]);
                    $qrApiUrl = url('/api/v2/profile/' . $profile['username'] . '/qrcode');
                @endphp
                <div style="display: inline-block; padding: 14px; background: #ffffff; border-radius: var(--radius-md); box-shadow: var(--shadow-md); border: 1px solid var(--fb-border); margin-bottom: 16px; position: relative;">
                    <img id="profileQrCodeImg" src="{{ $qrApiUrl }}" alt="Profile QR Code" style="width: 180px; height: 180px; display: block; border-radius: 8px;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 42px; height: 42px; border-radius: 50%; border: 3px solid #fff; background: #fff; overflow: hidden; box-shadow: var(--shadow-sm);">
                        <img src="{{ $profile['avatar'] ?: '/images/default-avatar.svg' }}" alt="{{ $profile['name'] }}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                    </div>
                </div>

                <div style="font-weight: 700; font-size: 16px; color: var(--fb-text-primary);">{{ $profile['name'] }}</div>
                <div style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 16px;">{{ '@' . $profile['username'] }}</div>

                <!-- Link Copy Box -->
                <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                    <input type="text" id="shareProfileUrlInput" class="form-control" value="{{ $profileUrl }}" readonly style="font-size: 13px; background: var(--fb-bg); text-align: left;">
                    <button type="button" class="fb-btn fb-btn-primary" onclick="copyProfileLink()" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        <span>কপি করুন</span>
                    </button>
                </div>

                <!-- Social Share Buttons -->
                <div style="font-size: 12px; font-weight: 700; color: var(--fb-text-secondary); text-transform: uppercase; margin-bottom: 12px;">সোশ্যাল মিডিয়ায় শেয়ার করুন</div>
                <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
                    <a href="https://api.whatsapp.com/send?text={{ urlencode($profile['name'] . ' এর সাথে Bondhoo-তে যুক্ত হোন: ' . $profileUrl) }}" target="_blank" class="fb-btn" style="background: #25D366; color: #fff; text-decoration: none; font-size: 13px; border-radius: 50px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        <span>WhatsApp</span>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($profileUrl) }}" target="_blank" class="fb-btn" style="background: #1877f2; color: #fff; text-decoration: none; font-size: 13px; border-radius: 50px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        <span>Facebook</span>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode($profileUrl) }}&text={{ urlencode($profile['name'] . ' on Bondhoo') }}" target="_blank" class="fb-btn" style="background: #000; color: #fff; text-decoration: none; font-size: 13px; border-radius: 50px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path></svg>
                        <span>X (Twitter)</span>
                    </a>
                    <a href="https://t.me/share/url?url={{ urlencode($profileUrl) }}&text={{ urlencode($profile['name'] . ' on Bondhoo') }}" target="_blank" class="fb-btn" style="background: #0088cc; color: #fff; text-decoration: none; font-size: 13px; border-radius: 50px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        <span>Telegram</span>
                    </a>
                </div>

                <!-- Download QR Button -->
                <button type="button" class="fb-btn fb-btn-secondary" onclick="downloadQrCode('{{ $qrApiUrl }}?download=1', '{{ $profile['username'] }}_qr.svg')" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    <span>কিউআর কোড ডাউনলোড করুন (SVG)</span>
                </button>
            </div>
        </div>
    </div>

    <!-- REPORT & BLOCK USER MODAL -->
    <div class="fb-modal-overlay" id="reportUserModal">
        <div class="fb-modal-card" style="max-width: 460px;">
            <div class="modal-header">
                <span class="modal-title" style="color: var(--fb-red); display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span>ব্যবহারকারী রিপোর্ট বা ব্লক করুন</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('reportUserModal')">✕</button>
            </div>
            <div class="modal-body" style="padding: 16px;">
                <div style="font-size: 14px; margin-bottom: 16px; color: var(--fb-text-secondary);">
                    আপনি <strong style="color: var(--fb-text-primary);" id="reportTargetUserName">{{ $profile['name'] }}</strong>-এর বিরুদ্ধে রিপোর্ট করছেন।
                </div>

                <div class="form-group">
                    <label class="form-label">রিপোর্টের কারণ নির্বাচন করুন *</label>
                    <select id="reportReasonSelect" class="form-control" required>
                        <option value="spam">স্প্যাম বা অনাকাঙ্ক্ষিত বিজ্ঞাপন (Spam)</option>
                        <option value="harassment">হয়রানি বা অবমাননাকর আচরণ (Harassment)</option>
                        <option value="fake_account">নকল বা ভুয়া অ্যাকাউন্ট (Fake Account)</option>
                        <option value="hate_speech">বিদ্বেষমূলক বক্তব্য (Hate Speech)</option>
                        <option value="violence">সহিংসতা বা ভীতি প্রদর্শন (Violence)</option>
                        <option value="other">অন্যান্য সমস্যা (Other)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">বিস্তারিত বিবরণ (ঐচ্ছিক)</label>
                    <textarea id="reportDetailsInput" class="form-control" rows="3" placeholder="সমস্যাটি সংক্ষেপে ব্যাখ্যা করুন..."></textarea>
                </div>

                <div style="display: flex; gap: 8px; margin-top: 20px;">
                    <button type="button" class="fb-btn fb-btn-primary" onclick="submitUserReport({{ $user->id }})" style="flex: 1; background: var(--fb-red); display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        <span>রিপোর্ট জমা দিন</span>
                    </button>
                    <button type="button" class="fb-btn fb-btn-secondary" onclick="handleBlockUser({{ $user->id }}, '{{ addslashes($profile['name']) }}')" style="color: var(--fb-red); border-color: #fee2e2; background: #fff1f2; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                        <span>ইউজার ব্লক করুন</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PROFILE NOTIFICATION SETTINGS MODAL (Requirement 20) -->
    <div class="fb-modal-overlay" id="notificationSettingsModal">
        <div class="fb-modal-card" style="max-width: 520px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:6px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span>প্রোফাইল নোটিফিকেশন সেটিংস</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('notificationSettingsModal')">✕</button>
            </div>
            <form id="notificationSettingsForm" onsubmit="submitNotificationSettings(event)">
                <div class="modal-body" style="padding: 20px;">
                    <p style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 16px;">
                        আপনার প্রোফাইল সংক্রান্ত কোন নোটিফিকেশনগুলো পেতে চান তা নির্বাচন করুন।
                    </p>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--fb-bg); border-radius: var(--radius-sm); cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px; color: var(--fb-text-primary);">ইমেইল নোটিফিকেশন</div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">গুরুত্বপূর্ণ নোটিফিকেশন ইমেইলে পাঠানো হবে</div>
                                </div>
                            </div>
                            <input type="checkbox" id="notif_email" name="email_notifications" value="1" {{ ($notificationSettings->email_notifications ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                        </label>

                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--fb-bg); border-radius: var(--radius-sm); cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: #059669; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path><path d="M2 8c0-2.2 1.8-4 4-4"></path><path d="M22 8c0-2.2-1.8-4-4-4"></path></svg>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px; color: var(--fb-text-primary);">পুশ নোটিফিকেশন</div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">ব্রাউজার এবং ডিভাইসে রিয়েলটাইম অ্যালার্ট</div>
                                </div>
                            </div>
                            <input type="checkbox" id="notif_push" name="push_notifications" value="1" {{ ($notificationSettings->push_notifications ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                        </label>

                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--fb-bg); border-radius: var(--radius-sm); cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99, 102, 241, 0.1); color: #4f46e5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px; color: var(--fb-text-primary);">ফ্রেন্ড রিকোয়েস্ট নোটিফিকেশন</div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">নতুন বন্ধু অনুরোধ আসলে আপনাকে জানানো হবে</div>
                                </div>
                            </div>
                            <input type="checkbox" id="notif_friend" name="friend_request_alerts" value="1" {{ ($notificationSettings->friend_request_alerts ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                        </label>

                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--fb-bg); border-radius: var(--radius-sm); cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(245, 158, 11, 0.1); color: #d97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px; color: var(--fb-text-primary);">মন্তব্য ও প্রতিক্রিয়া অ্যালার্ট</div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">আপনার পোস্টে কেউ লাইক বা কমেন্ট করলে জানাবে</div>
                                </div>
                            </div>
                            <input type="checkbox" id="notif_comment" name="comment_alerts" value="1" {{ ($notificationSettings->comment_alerts ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                        </label>

                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--fb-bg); border-radius: var(--radius-sm); cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(139, 92, 246, 0.1); color: #7c3aed; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"></path></svg>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px; color: var(--fb-text-primary);">মেনশন ও ট্যাগ নোটিফিকেশন</div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">কেউ পোস্টে ট্যাগ বা কমেন্টে মেনশন করলে জানাবে</div>
                                </div>
                            </div>
                            <input type="checkbox" id="notif_mention" name="mention_alerts" value="1" {{ ($notificationSettings->mention_alerts ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                        </label>

                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--fb-bg); border-radius: var(--radius-sm); cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px; color: var(--fb-text-primary);">সিকিউরিটি অ্যালার্ট</div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">সন্দেহজনক লগইন বা পাসওয়ার্ড পরিবর্তনে সতর্কবার্তা</div>
                                </div>
                            </div>
                            <input type="checkbox" id="notif_security" name="security_alerts" value="1" {{ ($notificationSettings->security_alerts ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                        </label>
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('notificationSettingsModal')">বাতিল</button>
                    <button type="submit" class="fb-btn fb-btn-primary" id="saveNotifBtn">সংরক্ষণ করুন</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ACCOUNT DEACTIVATE & DELETE MODAL (Requirement 29) -->
    <div class="fb-modal-overlay" id="accountDeactivateModal">
        <div class="fb-modal-card" style="max-width: 500px;">
            <div class="modal-header">
                <span class="modal-title" style="color: var(--fb-red); display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span>অ্যাকাউন্ট ডিঅ্যাক্টিভেশন বা ডিলিট</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('accountDeactivateModal')">✕</button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                    <button type="button" id="deactivateTabBtn" class="fb-btn fb-btn-primary" style="flex: 1;" onclick="switchDeactMode('deactivate')">সাময়িক ডিঅ্যাক্টিভেট</button>
                    <button type="button" id="deleteTabBtn" class="fb-btn fb-btn-secondary" style="flex: 1; color: var(--fb-red);" onclick="switchDeactMode('delete')">স্থায়ী ডিলিট</button>
                </div>

                <!-- Deactivate Form -->
                <form id="deactivateAccountForm" onsubmit="submitAccountDeactivate(event)">
                    <div style="font-size: 13px; color: var(--fb-text-secondary); line-height: 1.5; margin-bottom: 14px;">
                        অ্যাকাউন্ট ডিঅ্যাক্টিভেট করলে আপনার টাইমলাইন এবং ছবি অন্যদের জন্য সাময়িকভাবে অদৃশ্য হয়ে যাবে। আপনার সব ডেটা অক্ষত থাকবে এবং যেকোনো সময় পুনরায় লগইন করে আপনি অ্যাকাউন্ট সক্রিয় করতে পারবেন।
                    </div>
                    <div class="form-group">
                        <label class="form-label">ডিঅ্যাক্টিভেশনের কারণ (ঐচ্ছিক)</label>
                        <input type="text" id="deactReason" class="form-control" placeholder="যেমন: কিছুদিনের জন্য সোশ্যাল মিডিয়া থেকে বিরতি নিচ্ছি...">
                    </div>
                    <div class="form-group" style="margin-top: 12px;">
                        <label class="form-label">পাসওয়ার্ড নিশ্চিত করুন</label>
                        <input type="password" id="deactPassword" class="form-control" placeholder="আপনার বর্তমান পাসওয়ার্ড">
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;">
                        <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('accountDeactivateModal')">বাতিল</button>
                        <button type="submit" class="fb-btn" style="background: #f59e0b; color: #fff;">ডিঅ্যাক্টিভেট করুন</button>
                    </div>
                </form>

                <!-- Delete Form -->
                <form id="deleteAccountForm" onsubmit="submitAccountDelete(event)" style="display: none;">
                    <div style="background: #fff1f2; border: 1px solid #fecdd3; padding: 12px; border-radius: var(--radius-sm); font-size: 13px; color: #be123c; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <span><strong>সতর্কতা:</strong> অ্যাকাউন্ট স্থায়ীভাবে মুছে ফেললে আপনার সব পোস্ট, ছবি ও মেসেজ মুছে ফেলা হবে। এই কাজটি আর পূর্বাবস্থায় ফিরিয়ে আনা যাবে না।</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">নিশ্চিত করতে নিচের বক্সে <strong style="color:red;">DELETE</strong> শব্দটি লিখুন *</label>
                        <input type="text" id="deleteConfirmText" class="form-control" placeholder="DELETE" required>
                    </div>
                    <div class="form-group" style="margin-top: 12px;">
                        <label class="form-label">আপনার পাসওয়ার্ড প্রদান করুন *</label>
                        <input type="password" id="deletePassword" class="form-control" placeholder="আপনার বর্তমান পাসওয়ার্ড" required>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;">
                        <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('accountDeactivateModal')">বাতিল</button>
                        <button type="submit" class="fb-btn" style="background: var(--fb-red); color: #fff;">স্থায়ীভাবে ডিলিট করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AVATAR UPLOAD MODAL (Advanced Studio: Live Circular Cropper, Pan & Zoom, 90° Rotate, Camera Capture, Drag & Drop, Paste, Progress Bar) -->
    <style>
        #avatarModal .avm-card {
            max-width: 520px;
            width: 100%;
            max-height: 92vh;
            max-height: 92dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 20px;
            background: var(--fb-card, #ffffff);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.28), 0 0 1px rgba(0,0,0,0.1);
        }
        #avatarModal form#avatarUploadForm {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }
        #avatarModal .modal-header {
            flex-shrink: 0;
            padding: 14px 20px;
            border-bottom: 1px solid var(--fb-divider, #e4e6eb);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        #avatarModal .avm-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 700;
            color: var(--fb-text-primary, #050505);
        }
        #avatarModal .avm-header-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1877f2, #7c3aed);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(24, 119, 242, 0.3);
            flex-shrink: 0;
        }
        #avatarModal .modal-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            padding: 16px 20px;
        }
        #avatarModal .modal-footer.avm-footer {
            flex-shrink: 0;
            background: var(--fb-card, #ffffff);
            border-top: 1px solid var(--fb-divider, #e4e6eb);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.04);
            z-index: 10;
        }
        #avatarModal .avm-drag-handle {
            display: none;
            width: 44px;
            height: 5px;
            background: #cbd5e1;
            border-radius: 999px;
            margin: 8px auto 2px;
        }
        #avatarModal .avm-tabs {
            display: flex;
            gap: 6px;
            padding: 4px;
            background: var(--fb-hover, #f0f2f5);
            border-radius: 12px;
            margin-bottom: 14px;
        }
        #avatarModal .avm-tab {
            flex: 1;
            border: 0;
            background: transparent;
            padding: 8px 10px;
            border-radius: 9px;
            font-weight: 600;
            font-size: 13px;
            color: var(--fb-text-secondary, #65676b);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        #avatarModal .avm-tab.active {
            background: var(--fb-card, #ffffff);
            color: #1877f2;
            box-shadow: 0 1px 4px rgba(0,0,0,0.12);
        }
        #avatarModal .avm-pane {
            display: none;
            animation: avmFadeIn 0.25s ease;
        }
        #avatarModal .avm-pane.active {
            display: block;
        }
        @keyframes avmFadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: none; }
        }

        /* DROPZONE */
        #avatarModal .avm-drop {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-align: center;
            min-height: 220px;
            border: 2px dashed #93c5fd;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(24,119,242,0.03), rgba(124,58,237,0.05));
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 24px 16px;
        }
        #avatarModal .avm-drop:hover, #avatarModal .avm-drop.dragover {
            border-color: #2563eb;
            background: linear-gradient(135deg, rgba(24,119,242,0.08), rgba(124,58,237,0.09));
            transform: scale(1.005);
        }
        #avatarModal .avm-drop-icon {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1877f2, #7c3aed);
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(24,119,242,0.35);
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        #avatarModal .avm-drop:hover .avm-drop-icon {
            transform: scale(1.06);
        }
        #avatarModal .avm-drop-title {
            font-weight: 700;
            font-size: 15px;
            color: var(--fb-text-primary, #050505);
        }
        #avatarModal .avm-drop-sub {
            font-size: 12px;
            color: var(--fb-text-secondary, #65676b);
            max-width: 320px;
            line-height: 1.4;
        }
        #avatarModal .avm-quick-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 4px;
        }
        #avatarModal .avm-btn-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background: #ffffff;
            color: #1877f2;
            border: 1px solid #bfdbfe;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        #avatarModal .avm-btn-pill:hover {
            background: #eff6ff;
            border-color: #93c5fd;
        }
        #avatarModal .avm-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
            margin-top: 4px;
        }
        #avatarModal .avm-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 999px;
            background: rgba(24,119,242,0.08);
            color: #1877f2;
        }

        /* INTERACTIVE CIRCULAR STUDIO STAGE */
        #avatarModal .avm-stage-container {
            position: relative;
            width: 100%;
            background: #0f172a;
            border-radius: 16px;
            padding: 16px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            user-select: none;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.3);
        }
        #avatarModal .avm-stage-hint {
            color: rgba(255,255,255,0.85);
            font-size: 11px;
            font-weight: 600;
            background: rgba(0,0,0,0.5);
            padding: 4px 12px;
            border-radius: 999px;
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        #avatarModal .avm-circle-viewport {
            position: relative;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.72), 0 0 0 3px #3b82f6, 0 8px 24px rgba(0,0,0,0.5);
            cursor: grab;
            touch-action: none;
            background: #1e293b;
        }
        #avatarModal .avm-circle-viewport.grabbing {
            cursor: grabbing;
        }
        #avatarModal .avm-stage-img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform-origin: center center;
            pointer-events: none;
            max-width: none;
            max-height: none;
            will-change: transform;
        }
        #avatarModal .avm-viewport-crosshair {
            position: absolute;
            inset: 0;
            pointer-events: none;
            border-radius: 50%;
            border: 1px dashed rgba(255,255,255,0.35);
        }

        /* CONTROLS TOOLBAR */
        #avatarModal .avm-stage-toolbar {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        #avatarModal .avm-tool-btn {
            background: rgba(255,255,255,0.12);
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.18);
            font-size: 11px;
            font-weight: 600;
            padding: 5px 11px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
            backdrop-filter: blur(4px);
        }
        #avatarModal .avm-tool-btn:hover {
            background: rgba(255,255,255,0.24);
        }

        /* ZOOM BAR */
        #avatarModal .avm-zoom-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 14px;
            padding: 10px 14px;
            background: var(--fb-hover, #f0f2f5);
            border-radius: 12px;
        }
        #avatarModal .avm-zoom-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 0;
            background: #ffffff;
            color: #1e293b;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            flex-shrink: 0;
            transition: all 0.15s ease;
        }
        #avatarModal .avm-zoom-btn:hover {
            background: #eff6ff;
            color: #1877f2;
        }
        #avatarModal .avm-zoom-slider {
            flex: 1;
            accent-color: #1877f2;
            cursor: pointer;
            height: 6px;
        }
        #avatarModal .avm-zoom-badge {
            font-size: 12px;
            font-weight: 700;
            color: var(--fb-text-secondary, #65676b);
            min-width: 42px;
            text-align: right;
        }

        /* FILE INFO & CAPTION */
        #avatarModal .avm-file-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 6px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 12px;
            color: var(--fb-text-secondary, #65676b);
            margin-top: 10px;
        }
        #avatarModal .avm-caption-box {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
            padding: 8px 12px;
            border: 1px solid var(--fb-divider, #e4e6eb);
            border-radius: 12px;
            background: var(--fb-card, #ffffff);
            transition: border-color 0.2s ease;
        }
        #avatarModal .avm-caption-box:focus-within {
            border-color: #1877f2;
            box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.15);
        }
        #avatarModal .avm-mini-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid #cbd5e1;
        }
        #avatarModal .avm-caption-input {
            flex: 1;
            border: 0;
            outline: 0;
            background: transparent;
            font-size: 13px;
            color: var(--fb-text-primary, #050505);
        }

        /* FEED SHARE TOGGLE */
        #avatarModal .avm-share-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            font-size: 12px;
            color: var(--fb-text-secondary, #65676b);
            cursor: pointer;
            user-select: none;
        }
        #avatarModal .avm-share-toggle input {
            accent-color: #1877f2;
            cursor: pointer;
        }

        /* PROGRESS & ERROR */
        #avatarModal .avm-progress {
            display: none;
            margin-top: 12px;
        }
        #avatarModal .avm-progress-track {
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }
        #avatarModal .avm-progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #1877f2, #7c3aed);
            border-radius: 999px;
            transition: width 0.15s ease;
        }
        #avatarModal .avm-progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 600;
            color: var(--fb-text-secondary, #65676b);
            margin-top: 4px;
        }
        #avatarModal .avm-error {
            display: none;
            margin-top: 10px;
            padding: 8px 12px;
            border-radius: 10px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #fecaca;
        }

        /* CURRENT TAB STYLES */
        #avatarModal .avm-current-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 20px 10px;
            gap: 12px;
        }
        #avatarModal .avm-current-avatar-ring {
            position: relative;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            padding: 4px;
            background: linear-gradient(135deg, #1877f2, #7c3aed);
            box-shadow: 0 10px 25px rgba(24, 119, 242, 0.25);
        }
        #avatarModal .avm-current-avatar-ring img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            background: #ffffff;
            display: block;
        }
        #avatarModal .avm-current-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--fb-text-primary, #050505);
        }
        #avatarModal .avm-current-sub {
            font-size: 12px;
            color: var(--fb-text-secondary, #65676b);
            margin-top: -8px;
        }
        #avatarModal .avm-current-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 6px;
        }

        /* DEFAULT / SYSTEM AVATARS */
        #avatarModal .avm-presets-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            padding: 10px 2px;
        }
        #avatarModal .avm-preset-btn {
            border: 2px solid transparent;
            background: var(--fb-hover, #f0f2f5);
            border-radius: 14px;
            padding: 10px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        #avatarModal .avm-preset-btn:hover {
            border-color: #1877f2;
            background: #eff6ff;
            transform: translateY(-2px);
        }
        #avatarModal .avm-preset-btn img,
        #avatarModal .avm-preset-btn .avm-preset-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }
        #avatarModal .avm-preset-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--fb-text-primary, #050505);
        }

        /* SPINNER */
        #avatarModal .avm-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.5);
            border-top-color: #ffffff;
            border-radius: 50%;
            display: inline-block;
            animation: avmSpin 0.7s linear infinite;
            vertical-align: -2px;
            margin-right: 6px;
        }
        @keyframes avmSpin { to { transform: rotate(360deg); } }

        /* DEDICATED MOBILE RESPONSIVE STYLES (Bottom Sheet) */
        @media (max-width: 640px) {
            #avatarModal.fb-modal-overlay {
                padding: 0;
                align-items: flex-end; /* Mobile Bottom Sheet */
            }
            #avatarModal .avm-card {
                max-width: 100% !important;
                width: 100% !important;
                max-height: 90vh;
                max-height: 90dvh;
                border-bottom-left-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
                border-top-left-radius: 22px !important;
                border-top-right-radius: 22px !important;
                margin: 0 !important;
            }
            #avatarModal .avm-drag-handle {
                display: block;
            }
            #avatarModal .modal-header {
                padding: 10px 16px;
            }
            #avatarModal .avm-header-title {
                font-size: 15px;
            }
            #avatarModal .modal-body {
                padding: 12px 14px;
            }
            #avatarModal .avm-drop {
                min-height: 160px;
                padding: 16px 10px;
                gap: 8px;
            }
            #avatarModal .avm-drop-icon {
                width: 46px;
                height: 46px;
            }
            #avatarModal .avm-drop-title {
                font-size: 13px;
            }
            #avatarModal .avm-drop-sub {
                font-size: 11px;
            }
            #avatarModal .avm-circle-viewport {
                width: 190px;
                height: 190px;
            }
            #avatarModal .avm-stage-container {
                padding: 12px 0;
            }
            #avatarModal .avm-tool-btn {
                padding: 4px 8px;
                font-size: 10px;
            }
            #avatarModal .avm-zoom-wrap {
                padding: 8px 10px;
                gap: 6px;
            }
            #avatarModal .modal-footer.avm-footer {
                padding: 10px 14px calc(10px + env(safe-area-inset-bottom, 0px)) 14px;
                gap: 8px;
            }
            #avatarModal .avm-footer .fb-btn {
                height: 38px;
                padding: 0 12px;
                font-size: 13px;
                white-space: nowrap;
            }
            #avatarModal .avm-presets-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
        }
    </style>
    <div class="fb-modal-overlay" id="avatarModal" onclick="if(event.target===this) closeAvatarModal()">
        <div class="fb-modal-card avm-card">
            <div class="avm-drag-handle"></div>
            <div class="modal-header">
                <div class="avm-header-title">
                    <div class="avm-header-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <span>প্রোফাইল ছবি পরিবর্তন করুন</span>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeAvatarModal()" title="বন্ধ করুন">✕</button>
            </div>

            <form id="avatarUploadForm" onsubmit="submitAvatarUpload(event)">
                <div class="modal-body">
                    <!-- Navigation Tabs -->
                    <div class="avm-tabs" role="tablist">
                        <button type="button" class="avm-tab active" id="avmTabUpload" onclick="switchAvatarTab('upload')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            নতুন ছবি আপলোড
                        </button>
                        <button type="button" class="avm-tab" id="avmTabCurrent" onclick="switchAvatarTab('current')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="10" r="3"></circle><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"></path></svg>
                            বর্তমান ছবি
                        </button>
                        <button type="button" class="avm-tab" id="avmTabPresets" onclick="switchAvatarTab('presets')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
                            ডিফল্ট অবতার
                        </button>
                    </div>

                    <!-- TAB 1: UPLOAD PANE -->
                    <div class="avm-pane active" id="avmPaneUpload">
                        <!-- Hidden inputs: File & Camera -->
                        <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/webp" hidden onchange="handleAvatarFileSelect(event)">
                        <input type="file" id="avatarCameraInput" accept="image/*" capture="user" hidden onchange="handleAvatarFileSelect(event)">

                        <!-- DROPZONE (when no image selected) -->
                        <div class="avm-drop" id="avmDropzone" tabindex="0" role="button" aria-label="প্রোফাইল ছবি নির্বাচন করুন" onclick="document.getElementById('avatarFileInput').click()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
                            <div class="avm-drop-icon">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                            </div>
                            <div class="avm-drop-title">ছবি এখানে টেনে এনে ছাড়ুন অথবা বাছুন</div>
                            <div class="avm-drop-sub">কম্পিউটার বা ফোনের গ্যালারি থেকে ছবি আপলোড করুন অথবা ক্লিপবোর্ড (Ctrl+V) পেস্ট করুন</div>
                            <div class="avm-quick-btns" onclick="event.stopPropagation()">
                                <button type="button" class="avm-btn-pill" onclick="document.getElementById('avatarFileInput').click()">
                                    <span>📁</span> গ্যালারি বা ফাইল
                                </button>
                                <button type="button" class="avm-btn-pill" onclick="document.getElementById('avatarCameraInput').click()">
                                    <span>📸</span> ক্যামেরা দিয়ে তুলুন
                                </button>
                            </div>
                            <div class="avm-badges">
                                <span class="avm-badge">JPG / PNG / WebP</span>
                                <span class="avm-badge">সর্বোচ্চ 10MB</span>
                                <span class="avm-badge">স্মার্ট প্যান ও জুম ক্রপ</span>
                            </div>
                        </div>

                        <!-- INTERACTIVE CROP STUDIO (when image selected) -->
                        <div id="avmEditorWrap" style="display:none;">
                            <div class="avm-stage-container">
                                <div class="avm-stage-hint">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5 9 2 12 5 15"></polyline><polyline points="9 5 12 2 15 5"></polyline><polyline points="15 19 12 22 9 19"></polyline><polyline points="19 9 22 12 19 15"></polyline><line x1="2" y1="12" x2="22" y2="12"></line><line x1="12" y1="2" x2="12" y2="22"></line></svg>
                                    টেনে মুখমণ্ডল মাঝে আনুন
                                </div>
                                <div class="avm-circle-viewport" id="avmCircleViewport">
                                    <img id="avmStageImg" class="avm-stage-img" src="" alt="প্রোফাইল ছবি প্রিভিউ">
                                    <div class="avm-viewport-crosshair"></div>
                                </div>
                                <div class="avm-stage-toolbar">
                                    <button type="button" class="avm-tool-btn" onclick="rotateAvatarStage()" title="৯০ ডিগ্রি ঘোরান">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                        ৯০° ঘোরান
                                    </button>
                                    <button type="button" class="avm-tool-btn" onclick="resetAvatarTransform()" title="সেন্টার ও জুম রিসেট">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="2"></circle></svg>
                                        রিসেট
                                    </button>
                                    <button type="button" class="avm-tool-btn" onclick="document.getElementById('avatarFileInput').click()" title="অন্য ছবি পছন্দ করুন">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                        অন্য ছবি
                                    </button>
                                </div>
                            </div>

                            <!-- Zoom Slider Bar -->
                            <div class="avm-zoom-wrap">
                                <button type="button" class="avm-zoom-btn" onclick="adjustAvatarZoom(-0.1)" title="জুম আউট">−</button>
                                <input type="range" class="avm-zoom-slider" id="avmZoomSlider" min="100" max="300" value="100" oninput="setAvatarZoomFromSlider(this.value)">
                                <button type="button" class="avm-zoom-btn" onclick="adjustAvatarZoom(0.1)" title="জুম ইন">+</button>
                                <span class="avm-zoom-badge" id="avmZoomBadge">100%</span>
                            </div>

                            <!-- File Details Chip -->
                            <div class="avm-file-chip" id="avmFileChip">
                                <span id="avmFileName" style="font-weight:600;color:var(--fb-text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px;">ছবি</span>
                                <span id="avmFileSize" style="opacity:0.85;">0 KB</span>
                            </div>

                            <!-- Caption Box -->
                            <div class="avm-caption-box">
                                <img src="{{ $profile['avatar'] ?: '/images/default-avatar.svg' }}" alt="Avatar" class="avm-mini-avatar" id="avmMiniAvatarThumb">
                                <input type="text" name="caption" id="avatarCaptionInput" class="avm-caption-input" placeholder="প্রোফাইল ছবি সম্পর্কিত কিছু লিখুন... (ঐচ্ছিক)" maxlength="500">
                            </div>

                            <!-- Share to Feed Checkbox -->
                            <label class="avm-share-toggle">
                                <input type="checkbox" id="avatarShareTimeline" checked>
                                <span>টাইমলাইনে এই আপডেটটি পোস্ট করুন</span>
                            </label>
                        </div>

                        <!-- Progress Bar -->
                        <div class="avm-progress" id="avmProgress">
                            <div class="avm-progress-track">
                                <div class="avm-progress-fill" id="avmProgressFill"></div>
                            </div>
                            <div class="avm-progress-text">
                                <span id="avmProgressLabel">আপলোড হচ্ছে...</span>
                                <span id="avmProgressPct">0%</span>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div class="avm-error" id="avmError"></div>
                    </div>

                    <!-- TAB 2: CURRENT AVATAR PANE -->
                    <div class="avm-pane" id="avmPaneCurrent">
                        <div class="avm-current-card">
                            <div class="avm-current-avatar-ring">
                                <img src="{{ $profile['avatar'] ?: '/images/default-avatar.svg' }}" alt="{{ $profile['name'] }}" id="avmCurrentPreview">
                            </div>
                            <div class="avm-current-name">{{ $profile['name'] }}</div>
                            <div class="avm-current-sub">&#64;{{ $profile['username'] ?? '' }}</div>
                            @if(!empty($profile['avatar']))
                                <div class="avm-current-actions">
                                    <button type="button" class="fb-btn fb-btn-secondary" onclick="openPhotoTheater('{{ $profile['avatar'] ?? '' }}', '{{ addslashes($profile['name']) }}', 'প্রোফাইল ছবি')">
                                        👁️ বড় করে দেখুন
                                    </button>
                                    <a href="{{ $profile['avatar'] }}" download="avatar.jpg" class="fb-btn fb-btn-secondary" style="text-decoration:none;">
                                        📥 ডাউনলোড
                                    </a>
                                </div>
                            @else
                                <div style="font-size:13px;color:var(--fb-text-secondary);margin-top:8px;">
                                    বর্তমানে কোনো প্রোফাইল ছবি যুক্ত নেই।
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- TAB 3: DEFAULT AVATARS PANE -->
                    <div class="avm-pane" id="avmPanePresets">
                        <div style="font-size:13px;font-weight:600;color:var(--fb-text-secondary);margin-bottom:8px;">
                            সিস্টেম ডিফল্ট অবতার নির্বাচন করুন:
                        </div>
                        <div class="avm-presets-grid">
                            <button type="button" class="avm-preset-btn" onclick="applySystemAvatar('default')">
                                <img src="/images/default-avatar.svg" alt="Default Avatar">
                                <span class="avm-preset-label">স্ট্যান্ডার্ড</span>
                            </button>
                            <button type="button" class="avm-preset-btn" onclick="applySystemAvatar('male')">
                                <div class="avm-preset-icon" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;">👨</div>
                                <span class="avm-preset-label">পুরুষ</span>
                            </button>
                            <button type="button" class="avm-preset-btn" onclick="applySystemAvatar('female')">
                                <div class="avm-preset-icon" style="background:linear-gradient(135deg,#ec4899,#be185d);display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;">👩</div>
                                <span class="avm-preset-label">মহিলা</span>
                            </button>
                            <button type="button" class="avm-preset-btn" onclick="applySystemAvatar('neutral')">
                                <div class="avm-preset-icon" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;">✨</div>
                                <span class="avm-preset-label">মিনিমাল</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- MODAL FOOTER -->
                <div class="modal-footer avm-footer">
                    @if(!empty($profile['avatar']))
                        <button type="button" class="fb-btn" style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;" onclick="handleDeleteAvatar()">
                            ছবি মুছুন 🗑️
                        </button>
                    @else
                        <div></div>
                    @endif
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="fb-btn fb-btn-secondary" onclick="closeAvatarModal()">বাতিল</button>
                        <button type="submit" class="fb-btn fb-btn-primary" id="avatarUploadBtn" style="background:linear-gradient(135deg,#1877f2,#7c3aed);color:#fff;border:0;">
                            আপলোড ও সংরক্ষণ করুন ✨
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- COVER PHOTO UPLOAD MODAL (Advanced: drag & drop, paste, gallery, live preview, reposition, progress) -->
    <style>
        #coverModal .cvm-card {
            max-width: 640px;
            width: 100%;
            max-height: 90vh;
            max-height: 90dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        #coverModal form#coverUploadForm {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }
        #coverModal .modal-header {
            flex-shrink: 0;
            padding: 14px 20px;
        }
        #coverModal .modal-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            padding: 16px 20px;
        }
        #coverModal .modal-footer.cvm-footer {
            flex-shrink: 0;
            background: var(--fb-card, #fff);
            border-top: 1px solid var(--fb-divider);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.04);
            z-index: 10;
        }
        #coverModal .cvm-tabs { display:flex; gap:6px; padding:4px; background:var(--fb-hover, #f0f2f5); border-radius:12px; margin-bottom:14px; }
        #coverModal .cvm-tab { flex:1; border:0; background:transparent; padding:9px 12px; border-radius:9px; font-weight:600; font-size:13px; color:var(--fb-text-secondary, #65676b); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:all .2s ease; }
        #coverModal .cvm-tab.active { background:var(--fb-card, #fff); color:var(--fb-blue, #1877f2); box-shadow:0 1px 4px rgba(0,0,0,.12); }
        #coverModal .cvm-pane { display:none; animation:cvmFade .25s ease; }
        #coverModal .cvm-pane.active { display:block; }
        @keyframes cvmFade { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }
        #coverModal .cvm-drop { position:relative; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; text-align:center; aspect-ratio: 820 / 312; min-height:160px; border:2px dashed #c7cdd6; border-radius:14px; background:linear-gradient(135deg, rgba(24,119,242,.04), rgba(124,58,237,.05)); cursor:pointer; transition:all .2s ease; padding:16px; }
        #coverModal .cvm-drop:hover, #coverModal .cvm-drop.dragover { border-color:#1877f2; background:linear-gradient(135deg, rgba(24,119,242,.10), rgba(124,58,237,.10)); transform:scale(1.005); }
        #coverModal .cvm-drop-icon { width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#1877f2,#7c3aed); color:#fff; box-shadow:0 6px 18px rgba(24,119,242,.35); flex-shrink:0; }
        #coverModal .cvm-drop-title { font-weight:700; font-size:14px; color:var(--fb-text-primary, #050505); }
        #coverModal .cvm-drop-sub { font-size:12px; color:var(--fb-text-secondary, #65676b); }
        #coverModal .cvm-badges { display:flex; flex-wrap:wrap; gap:6px; justify-content:center; }
        #coverModal .cvm-badge { font-size:11px; font-weight:600; padding:2px 7px; border-radius:999px; background:rgba(24,119,242,.1); color:#1877f2; }
        #coverModal .cvm-stage { position:relative; aspect-ratio: 820 / 312; border-radius:14px; overflow:hidden; background:#111; cursor:grab; touch-action:none; user-select:none; box-shadow:inset 0 0 0 1px rgba(0,0,0,.08); }
        #coverModal .cvm-stage.grabbing { cursor:grabbing; }
        #coverModal .cvm-stage img { width:100%; height:100%; object-fit:cover; display:block; pointer-events:none; transition:object-position .05s linear; }
        #coverModal .cvm-stage-hint { position:absolute; top:10px; left:50%; transform:translateX(-50%); background:rgba(0,0,0,.6); color:#fff; font-size:12px; font-weight:600; padding:5px 12px; border-radius:999px; display:flex; align-items:center; gap:6px; backdrop-filter:blur(6px); pointer-events:none; white-space:nowrap; }
        #coverModal .cvm-stage-actions { position:absolute; bottom:10px; right:10px; display:flex; gap:6px; }
        #coverModal .cvm-chip-btn { border:0; background:rgba(255,255,255,.92); color:#111; font-size:12px; font-weight:600; padding:5px 10px; border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:5px; box-shadow:0 2px 6px rgba(0,0,0,.2); }
        #coverModal .cvm-chip-btn:hover { background:#fff; }
        #coverModal .cvm-avatar-ghost { position:absolute; left:16px; bottom:-34px; width:84px; height:84px; border-radius:50%; border:4px solid #fff; background:rgba(255,255,255,.35); backdrop-filter:blur(2px); pointer-events:none; }
        #coverModal .cvm-row { display:flex; align-items:center; gap:10px; margin-top:12px; }
        #coverModal .cvm-pos-control { margin-top: 40px; }
        #coverModal .cvm-row input[type=range] { flex:1; accent-color:#1877f2; cursor:pointer; }
        #coverModal .cvm-pos-label { font-weight:700; font-size:12px; min-width:40px; text-align:right; color:var(--fb-text-secondary, #65676b); }
        #coverModal .cvm-fileinfo { display:flex; align-items:center; gap:8px; margin-top:10px; font-size:12px; color:var(--fb-text-secondary, #65676b); flex-wrap:wrap; }
        #coverModal .cvm-fileinfo strong { color:var(--fb-text-primary, #050505); max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        #coverModal .cvm-error { display:none; margin-top:10px; padding:10px 12px; border-radius:10px; background:#fef2f2; color:#b91c1c; font-size:13px; font-weight:600; border:1px solid #fecaca; }
        #coverModal .cvm-progress { display:none; margin-top:14px; }
        #coverModal .cvm-progress-track { height:8px; border-radius:999px; background:var(--fb-hover, #e4e6eb); overflow:hidden; }
        #coverModal .cvm-progress-fill { height:100%; width:0%; background:linear-gradient(90deg,#1877f2,#7c3aed); border-radius:999px; transition:width .15s ease; }
        #coverModal .cvm-progress-text { display:flex; justify-content:space-between; font-size:12px; font-weight:600; color:var(--fb-text-secondary, #65676b); margin-top:6px; }
        #coverModal .cvm-gallery { display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:10px; max-height:42vh; overflow-y:auto; padding:2px; }
        #coverModal .cvm-gallery-item { position:relative; aspect-ratio: 16 / 9; border-radius:10px; overflow:hidden; cursor:pointer; border:2px solid transparent; transition:all .2s ease; background:#eee; padding:0; }
        #coverModal .cvm-gallery-item img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .3s ease; }
        #coverModal .cvm-gallery-item:hover img { transform:scale(1.06); }
        #coverModal .cvm-gallery-item:hover { border-color:#1877f2; box-shadow:0 4px 12px rgba(24,119,242,.25); }
        #coverModal .cvm-gallery-item .cvm-tag { position:absolute; top:6px; left:6px; font-size:10px; font-weight:700; padding:2px 6px; border-radius:6px; background:#059669; color:#fff; }
        #coverModal .cvm-gallery-heading { font-size:13px; font-weight:700; margin:4px 0 8px; color:var(--fb-text-primary, #050505); }
        #coverModal .cvm-empty { grid-column:1/-1; text-align:center; padding:24px; color:var(--fb-text-secondary, #65676b); font-size:13px; }
        #coverModal .cvm-footer { display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; }
        #coverModal .cvm-delete-btn { color:#e41e3f !important; border-color:#fbd5d5 !important; background:#fef2f2 !important; }
        #coverModal .cvm-spinner { width:14px; height:14px; border:2px solid rgba(255,255,255,.5); border-top-color:#fff; border-radius:50%; display:inline-block; animation:cvmSpin .7s linear infinite; vertical-align:-2px; margin-right:6px; }
        @keyframes cvmSpin { to { transform:rotate(360deg); } }
        .cover-photo-img.cover-fade-in { animation:coverFadeIn .6s ease; }
        @keyframes coverFadeIn { from { opacity:0; filter:blur(8px); transform:scale(1.02); } to { opacity:1; filter:none; transform:none; } }
        .cover-uploading-overlay { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,.35); color:#fff; font-weight:700; font-size:14px; z-index:5; backdrop-filter:blur(2px); gap:8px; }

        /* Dedicated Mobile Responsive Styles for Cover Modal */
        @media (max-width: 640px) {
            #coverModal.fb-modal-overlay {
                padding: 0;
                align-items: flex-end; /* Mobile Bottom Sheet */
            }
            #coverModal .cvm-card {
                max-width: 100% !important;
                width: 100% !important;
                max-height: 88vh;
                max-height: 88dvh;
                border-bottom-left-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
                border-top-left-radius: 20px !important;
                border-top-right-radius: 20px !important;
                margin: 0 !important;
            }
            #coverModal .modal-header {
                padding: 12px 16px;
            }
            #coverModal .modal-title {
                font-size: 16px;
            }
            #coverModal .modal-close-btn {
                width: 32px;
                height: 32px;
                font-size: 15px;
            }
            #coverModal .modal-body {
                padding: 12px 14px;
            }
            #coverModal .cvm-tabs {
                margin-bottom: 10px;
                padding: 3px;
                gap: 4px;
            }
            #coverModal .cvm-tab {
                padding: 7px 8px;
                font-size: 12px;
                gap: 4px;
            }
            #coverModal .cvm-tab svg {
                width: 13px;
                height: 13px;
            }
            #coverModal .cvm-drop {
                aspect-ratio: auto;
                min-height: 110px;
                padding: 12px 10px;
                gap: 6px;
                border-radius: 12px;
            }
            #coverModal .cvm-drop-icon {
                width: 38px;
                height: 38px;
            }
            #coverModal .cvm-drop-icon svg {
                width: 18px;
                height: 18px;
            }
            #coverModal .cvm-drop-title {
                font-size: 13px;
                line-height: 1.3;
            }
            #coverModal .cvm-drop-sub {
                font-size: 11px;
            }
            #coverModal .cvm-badges {
                gap: 4px;
            }
            #coverModal .cvm-badge {
                font-size: 10px;
                padding: 2px 6px;
            }
            #coverModal .cvm-stage {
                aspect-ratio: 16 / 8;
                max-height: 170px;
                border-radius: 12px;
            }
            #coverModal .cvm-stage-hint {
                font-size: 11px;
                padding: 4px 10px;
                top: 6px;
            }
            #coverModal .cvm-avatar-ghost {
                display: none !important;
            }
            #coverModal .cvm-pos-control {
                margin-top: 10px !important;
            }
            #coverModal .cvm-gallery {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
                max-height: 34vh;
            }
            #coverModal .cvm-gallery-heading {
                font-size: 12px;
                margin: 4px 0 6px;
            }
            #coverModal .cvm-fileinfo {
                font-size: 11px;
                gap: 6px;
                margin-top: 6px;
            }
            #coverModal .form-group {
                margin-bottom: 8px;
                margin-top: 8px !important;
            }
            #coverModal .form-label {
                font-size: 12px;
                margin-bottom: 4px;
            }
            #coverModal .form-control {
                padding: 8px 12px;
                font-size: 13px;
            }
            #coverModal .modal-footer.cvm-footer {
                padding: 10px 14px calc(10px + env(safe-area-inset-bottom, 0px)) 14px;
                gap: 8px;
                flex-wrap: nowrap;
            }
            #coverModal .cvm-footer .fb-btn {
                height: 36px;
                padding: 0 12px;
                font-size: 13px;
                white-space: nowrap;
            }
            #coverModal .cvm-delete-btn {
                padding: 0 8px !important;
                font-size: 12px !important;
            }
        }
    </style>
    <div class="fb-modal-overlay" id="coverModal">
        <div class="fb-modal-card cvm-card">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    <span>কভার ফটো পরিবর্তন করুন</span>
                </span>
                <button type="button" class="modal-close-btn" onclick="closeCoverModal()">✕</button>
            </div>
            <form id="coverUploadForm" onsubmit="submitCoverUpload(event)">
                <div class="modal-body">
                    <div class="cvm-tabs" role="tablist">
                        <button type="button" class="cvm-tab active" id="cvmTabUpload" onclick="switchCoverTab('upload')">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            নতুন ছবি আপলোড
                        </button>
                        <button type="button" class="cvm-tab" id="cvmTabGallery" onclick="switchCoverTab('gallery')">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            গ্যালারি থেকে বেছে নিন
                        </button>
                    </div>

                    <!-- Upload pane -->
                    <div class="cvm-pane active" id="cvmPaneUpload">
                        <input type="file" id="coverFileInput" accept="image/jpeg,image/png,image/webp" hidden onchange="previewCover(event)">

                        <div class="cvm-drop" id="cvmDropzone" tabindex="0" role="button" aria-label="কভার ছবি নির্বাচন করুন" onclick="document.getElementById('coverFileInput').click()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
                            <div class="cvm-drop-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            </div>
                            <div class="cvm-drop-title">ছবি এখানে টেনে আনুন অথবা ক্লিক করে নির্বাচন করুন</div>
                            <div class="cvm-drop-sub">Ctrl/⌘ + V দিয়ে ক্লিপবোর্ড থেকেও পেস্ট করতে পারবেন</div>
                            <div class="cvm-badges">
                                <span class="cvm-badge">JPG</span>
                                <span class="cvm-badge">PNG</span>
                                <span class="cvm-badge">WebP</span>
                                <span class="cvm-badge">সর্বোচ্চ 10MB</span>
                                <span class="cvm-badge">প্রস্তাবিত 1640×624</span>
                            </div>
                        </div>

                        <div id="cvmPreviewWrap" style="display:none;">
                            <div class="cvm-stage" id="cvmStage">
                                <img id="coverPreviewImg" src="" alt="কভার প্রিভিউ">
                                <div class="cvm-stage-hint">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 5 12 2 15 5"></polyline><polyline points="9 19 12 22 15 19"></polyline><line x1="12" y1="2" x2="12" y2="22"></line></svg>
                                    টেনে অবস্থান ঠিক করুন
                                </div>
                                <div class="cvm-avatar-ghost"></div>
                                <div class="cvm-stage-actions">
                                    <button type="button" class="cvm-chip-btn" onclick="event.stopPropagation(); document.getElementById('coverFileInput').click()">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                                        পরিবর্তন
                                    </button>
                                    <button type="button" class="cvm-chip-btn" onclick="event.stopPropagation(); resetCoverSelection()">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                        সরান
                                    </button>
                                </div>
                            </div>
                            <div class="cvm-row cvm-pos-control">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 5 12 2 15 5"></polyline><polyline points="9 19 12 22 15 19"></polyline><line x1="12" y1="2" x2="12" y2="22"></line></svg>
                                <input type="range" id="cvmPosSlider" min="0" max="100" value="50" oninput="setCoverPreviewPos(this.value)" aria-label="কভারের উল্লম্ব অবস্থান">
                                <span class="cvm-pos-label" id="cvmPosLabel">50%</span>
                                <button type="button" class="fb-btn fb-btn-secondary" style="padding:4px 10px;font-size:12px;" onclick="setCoverPreviewPos(50)">কেন্দ্রে</button>
                            </div>
                            <div class="cvm-fileinfo" id="cvmFileInfo"></div>
                        </div>

                        <div class="form-group" style="margin-top:14px;">
                            <label class="form-label" for="coverCaptionInput">ক্যাপশন (ঐচ্ছিক)</label>
                            <input type="text" name="caption" id="coverCaptionInput" maxlength="500" class="form-control" placeholder="কভার ছবি সম্পর্কিত কিছু লিখুন...">
                        </div>
                    </div>

                    <!-- Gallery pane -->
                    <div class="cvm-pane" id="cvmPaneGallery">
                        @php
                            $coverGalleryHistory = collect($coverHistory ?? [])->filter(fn ($c) => !empty($c->photo_path ?? $c->cover_url ?? null));
                            $coverGalleryPhotos = collect($photos ?? [])->filter(fn ($p) => !empty($p['url'] ?? null))->take(24);
                        @endphp
                        @if($coverGalleryHistory->isNotEmpty())
                            <div class="cvm-gallery-heading">পূর্ববর্তী কভার ছবি</div>
                            <div class="cvm-gallery" style="margin-bottom:14px;">
                                @foreach($coverGalleryHistory as $cphoto)
                                    @php $cUrl = $cphoto->photo_path ?? $cphoto->cover_url; @endphp
                                    <button type="button" class="cvm-gallery-item" onclick="selectCoverFromGallery(@js($cUrl))" title="কভার হিসেবে বেছে নিন">
                                        <img src="{{ $cUrl }}" alt="পূর্ববর্তী কভার" loading="lazy" onerror="this.closest('.cvm-gallery-item').style.display='none';">
                                        @if($cphoto->is_current)
                                            <span class="cvm-tag">বর্তমান</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        <div class="cvm-gallery-heading">আপনার ছবিসমূহ</div>
                        <div class="cvm-gallery">
                            @forelse($coverGalleryPhotos as $gph)
                                <button type="button" class="cvm-gallery-item" onclick="selectCoverFromGallery(@js($gph['url']))" title="কভার হিসেবে বেছে নিন">
                                    <img src="{{ $gph['url'] }}" alt="ছবি" loading="lazy" onerror="this.closest('.cvm-gallery-item').style.display='none';">
                                </button>
                            @empty
                                <div class="cvm-empty">এখনো কোনো ছবি নেই। "নতুন ছবি আপলোড" ট্যাব থেকে আপলোড করুন।</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="cvm-error" id="cvmError" role="alert"></div>

                    <div class="cvm-progress" id="cvmProgress">
                        <div class="cvm-progress-track"><div class="cvm-progress-fill" id="cvmProgressFill"></div></div>
                        <div class="cvm-progress-text"><span id="cvmProgressLabel">আপলোড হচ্ছে...</span><span id="cvmProgressPct">0%</span></div>
                    </div>
                </div>
                <div class="modal-footer cvm-footer">
                    <div>
                        <button type="button" class="fb-btn fb-btn-secondary cvm-delete-btn" id="coverDeleteBtn" onclick="handleDeleteCover()" style="{{ empty($profile['cover_photo']) ? 'display:none;' : '' }}">
                            কভার ছবি মুছুন
                        </button>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="fb-btn fb-btn-secondary" id="coverCancelBtn" onclick="closeCoverModal()">বাতিল</button>
                        <button type="submit" class="fb-btn fb-btn-primary" id="coverUploadBtn" disabled>সংরক্ষণ করুন</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- JUGAJUG ENTERPRISE POST COMPOSER SYSTEM -->
    @include('partials.enterprise-post-composer')

    <!-- LIFE EVENT MODAL -->
    <div class="fb-modal-overlay" id="lifeEventModal">
        <div class="fb-modal-card" style="max-width: 540px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                    <span>লাইফ ইভেন্ট / মাইলস্টোন যোগ করুন</span>
                </span>
                <button type="button" class="modal-close-btn" onclick="closeModal('lifeEventModal')">✕</button>
            </div>
            <form id="createLifeEventForm" onsubmit="submitLifeEvent(event)">
                <div class="modal-body" style="max-height: 72vh; overflow-y: auto;">
                    <!-- Category Selector -->
                    <label class="form-label" style="font-weight:700;margin-bottom:8px;display:block;">ক্যাটাগরি নির্বাচন করুন</label>
                    <div class="life-event-cat-grid">
                        <div class="life-event-cat-card active" onclick="selectLifeEventCat('work', '💼', 'নতুন কর্মক্ষেত্র', this)">
                            <span class="life-event-cat-icon">💼</span>
                            <span class="life-event-cat-label">কর্মজীবন</span>
                        </div>
                        <div class="life-event-cat-card" onclick="selectLifeEventCat('education', '🎓', 'নতুন ডিগ্রি / শিক্ষা', this)">
                            <span class="life-event-cat-icon">🎓</span>
                            <span class="life-event-cat-label">শিক্ষা</span>
                        </div>
                        <div class="life-event-cat-card" onclick="selectLifeEventCat('relationship', '❤️', 'নতুন সম্পর্ক / বিবাহ', this)">
                            <span class="life-event-cat-icon">❤️</span>
                            <span class="life-event-cat-label">সম্পর্ক</span>
                        </div>
                        <div class="life-event-cat-card" onclick="selectLifeEventCat('living', '🏠', 'নতুন বাসস্থান / শহর', this)">
                            <span class="life-event-cat-icon">🏠</span>
                            <span class="life-event-cat-label">বসবাস</span>
                        </div>
                        <div class="life-event-cat-card" onclick="selectLifeEventCat('travel', '✈️', 'নতুন ভ্রমণ / অভিজ্ঞতা', this)">
                            <span class="life-event-cat-icon">✈️</span>
                            <span class="life-event-cat-label">ভ্রমণ</span>
                        </div>
                        <div class="life-event-cat-card" onclick="selectLifeEventCat('achievement', '🏆', 'বিশেষ অর্জন / পুরস্কার', this)">
                            <span class="life-event-cat-icon">🏆</span>
                            <span class="life-event-cat-label">অর্জন</span>
                        </div>
                    </div>

                    <input type="hidden" name="life_event_cat" id="lifeEventCatInput" value="work">
                    <input type="hidden" name="life_event_icon" id="lifeEventIconInput" value="💼">

                    <!-- Milestone Title -->
                    <div class="form-group">
                        <label class="form-label">মাইলস্টোনের শিরোনাম</label>
                        <input type="text" name="life_event_title" id="lifeEventTitleInput" class="form-control" placeholder="যেমন: নতুন কোম্পানিতে সিনিয়র সফটওয়্যার ইঞ্জিনিয়ার হিসেবে যোগদান" required>
                    </div>

                    <!-- Organization / Place -->
                    <div class="form-group">
                        <label class="form-label">প্রতিষ্ঠান / স্থান</label>
                        <input type="text" name="location" id="lifeEventLocationInput" class="form-control" placeholder="যেমন: ঢাকা, বাংলাদেশ">
                    </div>

                    <!-- Date -->
                    <div class="form-group">
                        <label class="form-label">তারিখ</label>
                        <input type="date" name="event_date" id="lifeEventDateInput" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>

                    <!-- Story / Description -->
                    <div class="form-group">
                        <label class="form-label">আপনার অনুভূতি বা বিবরণ</label>
                        <textarea name="content" id="lifeEventContentInput" class="form-control" placeholder="এই মাইলস্টোনটি আপনার জীবনের জন্য কেন বিশেষ?" style="min-height:90px;"></textarea>
                    </div>

                    <!-- Privacy -->
                    <div class="form-group">
                        <label class="form-label">কে দেখতে পাবে?</label>
                        <select name="audience" class="form-control">
                            <option value="public">পাবলিক (Public)</option>
                            <option value="friends">বন্ধুরা (Friends)</option>
                            <option value="only_me">শুধুমাত্র আমি (Only Me)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('lifeEventModal')">বাতিল</button>
                    <button type="submit" class="fb-btn fb-btn-primary" id="saveLifeEventBtn" style="display:inline-flex;align-items:center;gap:6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                        <span>মাইলস্টোন প্রকাশ করুন</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SOCIAL SHARE MODAL -->
    <div class="fb-modal-overlay" id="socialShareModal">
        <div class="fb-modal-card" style="max-width: 480px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>
                    <span>পোস্ট শেয়ার করুন</span>
                </span>
                <button type="button" class="modal-close-btn" onclick="closeModal('socialShareModal')">✕</button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <input type="hidden" id="shareTargetPostId" value="">
                <div style="margin-bottom:16px;">
                    <label class="form-label" style="font-weight:600;margin-bottom:6px;display:block;">শেয়ার ক্যাপশন (ঐচ্ছিক):</label>
                    <textarea id="sharePostCaption" class="form-control" rows="2" placeholder="এই পোস্ট সম্পর্কে আপনার মন্তব্য লিখুন..." style="width:100%;border-radius:10px;padding:8px 12px;font-size:14px;resize:none;border:1px solid var(--fb-border);"></textarea>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <button type="button" id="confirmShareFeedBtn" onclick="executeSharePostToFeed()" class="fb-btn fb-btn-primary" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;border-radius:10px;font-weight:600;width:100%;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>
                        <span>আমার টাইমলাইনে শেয়ার করুন</span>
                    </button>
                    <button type="button" onclick="sharePostViaMessenger()" class="fb-btn fb-btn-secondary" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;border-radius:10px;font-weight:600;width:100%;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        <span>মেসেঞ্জারে পাঠান</span>
                    </button>
                    <button type="button" onclick="copyPostDirectLink()" class="fb-btn fb-btn-secondary" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;border-radius:10px;font-weight:600;width:100%;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        <span>পোস্টের লিংক কপি করুন</span>
                    </button>
                </div>
            </div>
            <div class="modal-footer" style="padding:12px 20px;border-top:1px solid var(--fb-border);display:flex;justify-content:flex-end;">
                <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('socialShareModal')">বন্ধ করুন</button>
            </div>
        </div>
    </div>

    <!-- MANAGE POSTS MODAL -->
    <div class="fb-modal-overlay" id="managePostsModal">
        <div class="fb-modal-card" style="max-width: 600px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>পোস্ট পরিচালনা (Manage Posts)</span>
                </span>
                <button type="button" class="modal-close-btn" onclick="closeModal('managePostsModal')">✕</button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div style="background: var(--fb-hover); border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 13px; color: var(--fb-text-secondary); display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <span>আপনার টাইমলাইনের পোস্টগুলো সহজে পরিচালনা করুন। যেকোনো পোস্ট পিন/আনপিন করতে বা ডিলিট করতে পাশের বোতাম ব্যবহার করুন।</span>
                </div>
                <div id="managePostsListContainer" style="display:flex;flex-direction:column;gap:10px;">
                    @forelse($timeline as $p)
                        <div class="fb-card" style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border:1px solid var(--fb-border);" id="manage-post-row-{{ $p->id }}">
                            <div style="flex:1;min-width:0;padding-right:12px;">
                                <div style="font-weight:600;font-size:13px;color:var(--fb-text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ Str::limit($p->content ?: ($p->feeling_activity ?: 'মিডিয়া পোস্ট'), 50) }}
                                </div>
                                <div style="font-size:11px;color:var(--fb-text-secondary);margin-top:2px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <span>{{ $p->created_at?->format('d M, Y') }}</span>
                                    <span>•</span>
                                    <span>{{ $p->audience === 'public' ? 'পাবলিক' : ($p->audience === 'friends' ? 'বন্ধুরা' : 'অনলি মি') }}</span>
                                    @if($p->is_pinned)
                                        <span>•</span>
                                        <span style="color:var(--fb-primary);font-weight:600;display:inline-flex;align-items:center;gap:3px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                                            <span>পিন করা</span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
                                <button type="button" class="fb-btn fb-btn-secondary" style="font-size:11px;padding:4px 8px;" onclick="handleTogglePinPost({{ $p->id }}, true)">
                                    {{ $p->is_pinned ? 'আনপিন' : 'পিন করুন' }}
                                </button>
                                <button type="button" class="fb-btn fb-btn-secondary" style="font-size:11px;padding:4px 8px;color:#dc2626;display:inline-flex;align-items:center;" onclick="handleDeletePost({{ $p->id }}, true)" title="পোস্ট মুছুন">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center;padding:24px;color:var(--fb-text-secondary);font-size:13px;">
                            পরিচালনা করার মতো কোনো পোস্ট নেই।
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('managePostsModal')">বন্ধ করুন</button>
            </div>
        </div>
    </div>

    <!-- PROFILE LOCK CONFIRMATION MODAL -->
    <div class="fb-modal-overlay" id="profileLockModal">
        <div class="fb-modal-card" style="max-width: 500px; text-align: center;">
            <div class="modal-header" style="justify-content: flex-end; border-bottom: none; padding-bottom: 0;">
                <button type="button" class="modal-close-btn" onclick="closeModal('profileLockModal')">✕</button>
            </div>
            <div class="modal-body" style="padding: 10px 24px 24px 24px;">
                @if($profile['is_profile_locked'])
                    <div style="display:inline-flex;align-items:center;justify-content:center;width:72px;height:72px;border-radius:50%;background:#e0f2fe;color:#0284c7;margin-bottom:12px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 800; color: var(--fb-text-primary); margin-bottom: 8px;">
                        আপনার প্রোফাইল আনলক করতে চান?
                    </h3>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); line-height: 1.6; margin-bottom: 20px;">
                        প্রোফাইল আনলক করলে যেকেউ আপনার পাবলিক পোস্ট, ছবি ও কভার ফটো দেখতে পারবেন এবং আরও সহজে আপনার সাথে যোগাযোগ করতে পারবেন।
                    </p>
                    <button type="button" class="fb-btn fb-btn-primary" style="width: 100%; padding: 12px; font-size: 15px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 8px;" onclick="handleLockToggle()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>
                        <span>আনলক নিশ্চিত করুন</span>
                    </button>
                @else
                    <div style="display:inline-flex;align-items:center;justify-content:center;width:72px;height:72px;border-radius:50%;background:#e0e7ff;color:#4f46e5;margin-bottom:12px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 800; color: var(--fb-text-primary); margin-bottom: 8px;">
                        আপনার প্রোফাইল লক করুন
                    </h3>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); line-height: 1.5; margin-bottom: 20px;">
                        আপনার ফটো ও পোস্টগুলো অপরিচিতদের কাছ থেকে নিরাপদ ও ব্যক্তিগত রাখুন।
                    </p>
                    <div style="text-align: left; background: var(--fb-bg); border-radius: 8px; padding: 14px 16px; margin-bottom: 20px; display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--fb-hover);flex-shrink:0;color:var(--fb-primary);">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </span>
                            <div style="font-size: 13px; color: var(--fb-text-primary);">
                                <strong>শুধুমাত্র বন্ধুরা</strong> আপনার টাইমলাইনের ছবি, পোস্ট ও পূর্ণাঙ্গ স্টোরি দেখতে পাবে।
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--fb-hover);flex-shrink:0;color:var(--fb-primary);">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            </span>
                            <div style="font-size: 13px; color: var(--fb-text-primary);">
                                <strong>ফুল-সাইজ প্রোফাইল ফটো ও কভার</strong> অপরিচিতরা জুম বা ডাউনলোড করতে পারবে না।
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--fb-hover);flex-shrink:0;color:var(--fb-primary);">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </span>
                            <div style="font-size: 13px; color: var(--fb-text-primary);">
                                অপরিচিতরা এখনও আপনাকে যোগাযোগ-এ খুঁজতে ও বন্ধুত্বের অনুরোধ পাঠাতে পারবে।
                            </div>
                        </div>
                    </div>
                    <button type="button" class="fb-btn fb-btn-primary" style="width: 100%; padding: 12px; font-size: 15px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 8px;" onclick="handleLockToggle()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>প্রোফাইল লক নিশ্চিত করুন</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- FACEBOOK-STYLE PHOTO THEATER / LIGHTBOX MODAL -->
    <div class="photo-theater-modal" id="photoTheaterModal" onclick="handleTheaterBackdropClick(event)">
        <button type="button" class="theater-close-btn" onclick="closePhotoTheater()" title="বন্ধ করুন (Esc)">✕</button>
        <div class="theater-stage" id="theaterStage">
            <img src="" alt="Photo" class="theater-main-img" id="theaterMainImg" oncontextmenu="return !theaterIsProtected;">
        </div>
        <div class="theater-sidebar" id="theaterSidebar">
            <div class="theater-sidebar-header">
                <img src="{{ $profile['avatar'] }}" alt="{{ $profile['name'] }}" class="theater-author-avatar" id="theaterAuthorAvatar">
                <div>
                    <div class="theater-author-name" id="theaterAuthorName">{{ $profile['name'] }}</div>
                    <div class="theater-meta-date" id="theaterMetaDate">সম্প্রতি</div>
                </div>
            </div>

            <div id="theaterGuardNotice" class="theater-guard-banner" style="display:none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0;">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                </svg>
                <span>🛡️ প্রোফাইল পিকচার গার্ড সক্রিয়। এই ছবিটি ডাউনলোড বা শেয়ার করা থেকে সুরক্ষিত।</span>
            </div>

            <div class="theater-caption" id="theaterCaption"></div>

            <div class="theater-actions">
                <button type="button" class="theater-action-btn" onclick="theaterLike()">
                    👍 লাইক
                </button>
                <button type="button" class="theater-action-btn" onclick="theaterCommentFocus()">
                    💬 মন্তব্য
                </button>
                <button type="button" class="theater-action-btn" id="theaterShareBtn" onclick="theaterShare()">
                    ↗️ শেয়ার
                </button>
            </div>

            <div style="flex:1;padding:16px;overflow-y:auto;display:flex;flex-direction:column;gap:12px;" id="theaterCommentsList">
                <div style="text-align:center;color:var(--fb-text-secondary);font-size:13px;padding:20px 0;">
                    সুন্দর মুহূর্তগুলো যোগাযোগ বন্ধুদের সাথে উপভোগ করুন!
                </div>
            </div>
        </div>
    </div>

    <!-- FACEBOOK-STYLE VIDEO THEATER MODAL -->
    <div class="photo-theater-modal" id="videoTheaterModal" onclick="handleVideoTheaterBackdropClick(event)">
        <button type="button" class="theater-close-btn" onclick="closeVideoTheater()" title="বন্ধ করুন (Esc)">✕</button>
        <div class="theater-stage" id="videoTheaterStage">
            <video src="" controls autoplay playsinline class="theater-main-img" id="theaterMainVideo" style="max-height:88vh;max-width:100%;background:#000;border-radius:8px;"></video>
        </div>
        <div class="theater-sidebar" id="videoTheaterSidebar">
            <div class="theater-sidebar-header">
                <img src="{{ $profile['avatar'] }}" alt="{{ $profile['name'] }}" class="theater-author-avatar" id="videoAuthorAvatar">
                <div>
                    <div class="theater-author-name" id="videoAuthorName">{{ $profile['name'] }}</div>
                    <div class="theater-meta-date" id="videoMetaDate">ভিডিও • সম্প্রতি</div>
                </div>
            </div>

            <div class="theater-caption" id="videoCaption">ভিডিও বিবরণী</div>

            <div class="theater-actions">
                <button type="button" class="theater-action-btn" onclick="theaterLike()">
                    👍 লাইক
                </button>
                <button type="button" class="theater-action-btn" onclick="theaterCommentFocus()">
                    💬 মন্তব্য
                </button>
                <button type="button" class="theater-action-btn" onclick="videoShare()">
                    ↗️ শেয়ার
                </button>
            </div>

            <div style="flex:1;padding:16px;overflow-y:auto;display:flex;flex-direction:column;gap:12px;" id="videoCommentsList">
                <div style="text-align:center;color:var(--fb-text-secondary);font-size:13px;padding:20px 0;">
                    ভিডিওটি উপভোগ করুন এবং বন্ধুদের সাথে শেয়ার করুন!
                </div>
            </div>
        </div>
    </div>

    <!-- CREATOR CATEGORY MODAL -->
    <div class="fb-modal-overlay" id="creatorCategoryModal">
        <div class="fb-modal-card" style="max-width: 480px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:6px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    <span>ক্রিয়েটর ক্যাটাগরি নির্ধারণ</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('creatorCategoryModal')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 14px; color: var(--fb-text-secondary); margin-bottom: 16px; line-height: 1.5;">
                    আপনার কনটেন্ট ও পেশার সাথে মানানসই ক্যাটাগরি নির্বাচন করুন। এটি আপনার প্রোফাইলে নামের নিচে ক্রিয়েটর ব্যাজ হিসেবে প্রদর্শিত হবে।
                </p>
                <div style="margin-bottom: 20px;">
                    <label for="creatorCategorySelect" style="display: block; font-size: 13px; font-weight: 600; color: var(--fb-text-primary); margin-bottom: 8px;">
                        ক্যাটাগরি নির্বাচন করুন
                    </label>
                    <select id="creatorCategorySelect" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-input-bg, #fff); color: var(--fb-text-primary); font-size: 14px; font-family: inherit;">
                        <option value="">(কোনোটিই নয় / ক্যাটাগরি মুছুন)</option>
                        <option value="ডিজিটাল ক্রিয়েটর" {{ ($profile['category'] ?? '') === 'ডিজিটাল ক্রিয়েটর' ? 'selected' : '' }}>ডিজিটাল ক্রিয়েটর</option>
                        <option value="ভিডিও ক্রিয়েটর" {{ ($profile['category'] ?? '') === 'ভিডিও ক্রিয়েটর' ? 'selected' : '' }}>ভিডিও ক্রিয়েটর</option>
                        <option value="লেখক ও ব্লগার" {{ ($profile['category'] ?? '') === 'লেখক ও ব্লগার' ? 'selected' : '' }}>লেখক ও ব্লগার</option>
                        <option value="সফ্টওয়্যার ডেভেলপার" {{ ($profile['category'] ?? '') === 'সফ্টওয়্যার ডেভেলপার' ? 'selected' : '' }}>সফ্টওয়্যার ডেভেলপার</option>
                        <option value="ফটোগ্রাফার" {{ ($profile['category'] ?? '') === 'ফটোগ্রাফার' ? 'selected' : '' }}>ফটোগ্রাফার</option>
                        <option value="শিক্ষাবিদ" {{ ($profile['category'] ?? '') === 'শিক্ষাবিদ' ? 'selected' : '' }}>শিক্ষাবিদ</option>
                        <option value="উদ্যোক্তা" {{ ($profile['category'] ?? '') === 'উদ্যোক্তা' ? 'selected' : '' }}>উদ্যোক্তা</option>
                        <option value="পাবলিক ফিগার" {{ ($profile['category'] ?? '') === 'পাবলিক ফিগার' ? 'selected' : '' }}>পাবলিক ফিগার</option>
                        <option value="সঙ্গীতশিল্পী" {{ ($profile['category'] ?? '') === 'সঙ্গীতশিল্পী' ? 'selected' : '' }}>সঙ্গীতশিল্পী</option>
                        <option value="সংবাদ ও সাংবাদিকতা" {{ ($profile['category'] ?? '') === 'সংবাদ ও সাংবাদিকতা' ? 'selected' : '' }}>সংবাদ ও সাংবাদিকতা</option>
                        <option value="গেমিং ক্রিয়েটর" {{ ($profile['category'] ?? '') === 'গেমিং ক্রিয়েটর' ? 'selected' : '' }}>গেমিং ক্রিয়েটর</option>
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('creatorCategoryModal')" style="padding: 8px 16px; border-radius: 6px; font-size: 14px;">বাতিল</button>
                    <button type="button" class="fb-btn fb-btn-primary" id="saveCreatorCategoryBtn" onclick="saveCreatorCategory()" style="padding: 8px 18px; border-radius: 6px; font-size: 14px; background: #1877f2; color: #fff; border: none; font-weight: 600;">সংরক্ষণ করুন</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MANAGE SECTIONS MODAL -->
    <div class="fb-modal-overlay" id="manageSectionsModal">
        <div class="fb-modal-card" style="max-width: 500px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    <span>সেকশন পরিচালনা (Manage Sections)</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('manageSectionsModal')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 14px; color: var(--fb-text-secondary); margin-bottom: 16px; line-height: 1.5;">
                    আপনার প্রোফাইলের সাইডবারে কোন কোন সেকশন প্রদর্শিত হবে তা কাস্টমাইজ করুন।
                </p>
                <div style="display: flex; flex-direction: column; gap: 12px;" id="sectionsToggleList">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border:1px solid var(--fb-border);border-radius:var(--radius-md);background:var(--fb-hover);">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(59,130,246,0.1);color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:var(--fb-text-primary);">পরিচিতি ও বায়ো (Intro)</div>
                                <div style="font-size:12px;color:var(--fb-text-secondary);">বায়ো, শহর, কর্মক্ষেত্র ও সোশ্যাল লিংকস</div>
                            </div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="sectionToggle-intro" checked onchange="toggleSectionVisibility('intro', this.checked)">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border:1px solid var(--fb-border);border-radius:var(--radius-md);background:var(--fb-hover);">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(245,158,11,0.1);color:#d97706;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:var(--fb-text-primary);">ফিচারড কালেকশন (Featured)</div>
                                <div style="font-size:12px;color:var(--fb-text-secondary);">হাইলাইটস ও নির্বাচিত ছবিসমূহ</div>
                            </div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="sectionToggle-featured" checked onchange="toggleSectionVisibility('featured', this.checked)">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border:1px solid var(--fb-border);border-radius:var(--radius-md);background:var(--fb-hover);">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(16,185,129,0.1);color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:var(--fb-text-primary);">ছবি গ্যালারি (Photos Preview)</div>
                                <div style="font-size:12px;color:var(--fb-text-secondary);">সাম্প্রতিক ৯টি ছবির গ্রিড</div>
                            </div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="sectionToggle-photos" checked onchange="toggleSectionVisibility('photos', this.checked)">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border:1px solid var(--fb-border);border-radius:var(--radius-md);background:var(--fb-hover);">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(99,102,241,0.1);color:#4f46e5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:var(--fb-text-primary);">বন্ধুদের তালিকা (Friends Preview)</div>
                                <div style="font-size:12px;color:var(--fb-text-secondary);">বন্ধুদের সংক্ষিপ্ত তালিকা ও সংখ্যা</div>
                            </div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="sectionToggle-friends" checked onchange="toggleSectionVisibility('friends', this.checked)">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border:1px solid var(--fb-border);border-radius:var(--radius-md);background:var(--fb-hover);">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(236,72,153,0.1);color:#db2777;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:var(--fb-text-primary);">প্রোফাইল সম্পূর্ণতা বার (Completion)</div>
                                <div style="font-size:12px;color:var(--fb-text-secondary);">প্রোফাইল সম্পূর্ণ করার প্রগ্রেস নির্দেশক</div>
                            </div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="sectionToggle-completion" checked onchange="toggleSectionVisibility('completion', this.checked)">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
                    <button type="button" class="fb-btn fb-btn-primary" onclick="closeModal('manageSectionsModal')" style="padding:8px 20px;">
                        সম্পন্ন করুন
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($isOwner)
    <!-- VERIFICATION MODAL -->
    <div class="fb-modal-overlay" id="verificationModal">
        <div class="fb-modal-card" style="max-width: 600px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                    <span>পরিচয় যাচাইকরণ ও ভেরিফায়েড ব্যাজ</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('verificationModal')">✕</button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                @if($profile['is_verified'])
                    <div style="text-align: center; padding: 24px 16px;">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: #ecfdf5; color: #10b981; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <h3 style="font-size: 20px; font-weight: 800; color: #10b981; margin-bottom: 6px;">আপনার প্রোফাইল ভেরিফায়েড!</h3>
                        <p style="font-size: 14px; color: var(--fb-text-secondary); line-height: 1.6;">
                            আপনার নাগরিক পরিচয় সফলভাবে যাচাইকৃত হয়েছে এবং আপনার নামের পাশে নীল ভেরিফাইড ব্যাজ সক্রিয় রয়েছে।
                        </p>
                    </div>
                @elseif(($profile['verification_status'] ?? '') === 'PENDING')
                    <div style="text-align: center; padding: 24px 16px;">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: #fffbeb; color: #f59e0b; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <h3 style="font-size: 20px; font-weight: 800; color: #f59e0b; margin-bottom: 6px;">আবেদন পর্যালোচনায় আছে</h3>
                        <p style="font-size: 14px; color: var(--fb-text-secondary); line-height: 1.6;">
                            আপনার আইডি ভেরিফিকেশনের আবেদন আমাদের সিকিউরিটি টিম যাচাই করছে। পর্যালোচনার ফলাফল শীঘ্রই জানানো হবে।
                        </p>
                    </div>
                @else
                    @if(($profile['verification_status'] ?? '') === 'REJECTED')
                        <div style="background: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 14px; color: #dc2626; display: flex; align-items: center; gap: 8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            <span><strong>পূর্ববর্তী আবেদন প্রত্যাখ্যাত হয়েছে।</strong> সঠিক ও স্পষ্ট ডকুমেন্ট যুক্ত করে অনুগ্রহ করে পুনরায় আবেদন করুন।</span>
                        </div>
                    @endif

                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 13px; color: #1e40af; line-height: 1.5; display: flex; align-items: center; gap: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>সরকারি পরিচয়পত্র (NID, পাসপোর্ট অথবা ড্রাইভিং লাইসেন্স) প্রদান করে আপনার প্রোফাইল ভেরিফাই করুন।</span>
                    </div>

                    <form id="verificationForm" onsubmit="submitVerification(event)">
                        <div class="form-group">
                            <label class="form-label">ডকুমেন্টের ধরন (Document Type)</label>
                            <select name="document_type" class="form-control" required>
                                <option value="nid">জাতীয় পরিচয়পত্র (National ID / NID)</option>
                                <option value="passport">পাসপোর্ট (Passport)</option>
                                <option value="driving_license">ড্রাইভিং লাইসেন্স (Driving License)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ডকুমেন্ট / আইডি নম্বর</label>
                            <input type="text" name="document_number" class="form-control" placeholder="উদা: 19951234567890" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">আইডি কার্ড অনুযায়ী পূর্ণ নাম</label>
                            <input type="text" name="full_name" class="form-control" value="{{ $profile['name'] }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">আইডি কার্ডের সামনের ছবি (Front Side — JPG, PNG, WebP)</label>
                            <input type="file" name="document_front" class="form-control" accept="image/*" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">আইডি কার্ডের পেছনের ছবি (Back Side — ঐচ্ছিক)</label>
                            <input type="file" name="document_back" class="form-control" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label class="form-label">আইডিসহ সেলফি (Selfie with ID — ঐচ্ছিক)</label>
                            <input type="file" name="selfie" class="form-control" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label class="form-label">আবেদনের কারণ বা অতিরিক্ত বিবরণ (ঐচ্ছিক)</label>
                            <textarea name="reason" class="form-control" placeholder="ভেরিফিকেশন আবেদনের বিশেষ বিবরণ থাকলে লিখুন..."></textarea>
                        </div>

                        <div class="modal-footer" style="padding: 12px 0 0;">
                            <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('verificationModal')">বাতিল</button>
                            <button type="submit" class="fb-btn fb-btn-primary" id="submitVerifyBtn">আবেদন জমা দিন</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- ADVANCED PRIVACY & SETTINGS CENTER MODAL (AS REQUESTED) -->
    <div class="fb-modal-overlay" id="privacyModal">
        <div class="fb-modal-card" style="max-width: 660px;">
            <div class="modal-header" style="padding:16px 20px; border-bottom:1px solid var(--fb-border); display:flex; align-items:center; justify-content:space-between;">
                <span class="modal-title" style="display:flex;align-items:center;gap:10px;font-size:16px;font-weight:800;color:var(--fb-text-primary);">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;background:rgba(0,132,255,0.1);color:#0084ff;border-radius:10px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </span>
                    <span>প্রোফাইল প্রাইভেসি ও নিরাপত্তা সেটিংস</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('privacyModal')" style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--fb-text-secondary);">✕</button>
            </div>

            <!-- Subtabs Nav Bar -->
            <div class="privacy-subtabs-nav" style="display:flex;gap:4px;overflow-x:auto;padding:8px 16px;border-bottom:1px solid var(--fb-border);background:var(--fb-hover);">
                <button type="button" class="privacy-subtab-btn active" onclick="switchPrivacySubtab('visibility', this)" style="padding:8px 14px;border:none;background:none;border-bottom:2px solid #0084ff;color:#0084ff;font-weight:700;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <span>🌐 দৃশ্যমানতা ও তথ্য</span>
                </button>
                <button type="button" class="privacy-subtab-btn" onclick="switchPrivacySubtab('interactions', this)" style="padding:8px 14px;border:none;background:none;border-bottom:2px solid transparent;color:var(--fb-text-secondary);font-weight:500;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <span>👥 ফ্রেন্ড ও পোস্ট</span>
                </button>
                <button type="button" class="privacy-subtab-btn" onclick="switchPrivacySubtab('discovery', this)" style="padding:8px 14px;border:none;background:none;border-bottom:2px solid transparent;color:var(--fb-text-secondary);font-weight:500;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <span>🔍 অনুসন্ধান ও স্ট্যাটাস</span>
                </button>
                <button type="button" class="privacy-subtab-btn" onclick="switchPrivacySubtab('security', this)" style="padding:8px 14px;border:none;background:none;border-bottom:2px solid transparent;color:var(--fb-text-secondary);font-weight:500;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <span>🛡️ নিরাপত্তা ও ডিভাইস</span>
                </button>
                <button type="button" class="privacy-subtab-btn" onclick="switchPrivacySubtab('blocking', this)" style="padding:8px 14px;border:none;background:none;border-bottom:2px solid transparent;color:var(--fb-text-secondary);font-weight:500;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <span>🚫 ব্লক তালিকা</span>
                </button>
            </div>

            <form id="privacyForm" onsubmit="submitPrivacySettings(event)">
                <div class="modal-body" style="max-height: 65vh; overflow-y: auto; padding: 18px 20px;">
                    <div style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 14px;">
                        আপনার ব্যক্তিগত তথ্য, ফ্রেন্ড রিকোয়েস্ট ও পোস্ট কারা দেখতে পাবে তা পছন্দমত নির্ধারণ করুন:
                    </div>

                    <!-- TAB 1: VISIBILITY & INFO (EXACTLY MATCHING MOCKUP & USER SCREENSHOT) -->
                    <div id="privacyTabContent-visibility" class="privacy-subtab-pane" style="display:block;">
                        <div style="padding: 16px; background: var(--fb-bg); border-radius: var(--radius-md); border: 1px solid var(--fb-border); margin-bottom: 16px;">
                            <div style="font-weight: 700; font-size: 13px; color: var(--fb-text-primary); text-transform: uppercase; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0084ff" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                <span>প্রোফাইল ও তথ্য দৃশ্যমানতা</span>
                            </div>

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">প্রোফাইল দৃশ্যমানতা (Profile Visibility)</label>
                                <select name="profile_visibility" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="public" {{ ($privacySettings->profile_visibility ?? 'public') === 'public' ? 'selected' : '' }}>সবাই দেখতে পাবে (Public)</option>
                                    <option value="friends" {{ ($privacySettings->profile_visibility ?? '') === 'friends' ? 'selected' : '' }}>শুধুমাত্র বন্ধুরা (Friends Only)</option>
                                    <option value="only_me" {{ ($privacySettings->profile_visibility ?? '') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি (Only Me)</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">বায়ো ও পরিচিতি তথ্যের প্রাইভেসি</label>
                                <select name="bio_privacy" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="public" {{ ($privacySettings->bio_privacy ?? 'public') === 'public' ? 'selected' : '' }}>পাবলিক (Public)</option>
                                    <option value="friends" {{ ($privacySettings->bio_privacy ?? '') === 'friends' ? 'selected' : '' }}>শুধুমাত্র বন্ধুরা</option>
                                    <option value="only_me" {{ ($privacySettings->bio_privacy ?? '') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">কর্মজীবন ও পেশার প্রাইভেসি</label>
                                <select name="work_privacy" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="public" {{ ($privacySettings->work_privacy ?? 'public') === 'public' ? 'selected' : '' }}>পাবলিক (Public)</option>
                                    <option value="friends" {{ ($privacySettings->work_privacy ?? '') === 'friends' ? 'selected' : '' }}>শুধুমাত্র বন্ধুরা</option>
                                    <option value="only_me" {{ ($privacySettings->work_privacy ?? '') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">শিক্ষা প্রতিষ্ঠানের তথ্যের প্রাইভেসি</label>
                                <select name="education_privacy" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="public" {{ ($privacySettings->education_privacy ?? 'public') === 'public' ? 'selected' : '' }}>পাবলিক (Public)</option>
                                    <option value="friends" {{ ($privacySettings->education_privacy ?? '') === 'friends' ? 'selected' : '' }}>শুধুমাত্র বন্ধুরা</option>
                                    <option value="only_me" {{ ($privacySettings->education_privacy ?? '') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">সোশ্যাল মিডিয়া লিংকসমূহের প্রাইভেসি</label>
                                <select name="social_links_privacy" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="public" {{ ($privacySettings->social_links_privacy ?? 'public') === 'public' ? 'selected' : '' }}>পাবলিক (Public)</option>
                                    <option value="friends" {{ ($privacySettings->social_links_privacy ?? '') === 'friends' ? 'selected' : '' }}>শুধুমাত্র বন্ধুরা</option>
                                    <option value="only_me" {{ ($privacySettings->social_links_privacy ?? '') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">বন্ধুদের তালিকা কে দেখতে পারবে (Friends List)</label>
                                <select name="friends_list_visibility" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="public" {{ ($privacySettings->friends_list_visibility ?? 'public') === 'public' ? 'selected' : '' }}>পাবলিক (সবাই)</option>
                                    <option value="friends" {{ ($privacySettings->friends_list_visibility ?? '') === 'friends' ? 'selected' : '' }}>শুধুমাত্র বন্ধুরা</option>
                                    <option value="only_me" {{ ($privacySettings->friends_list_visibility ?? '') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 6px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">জন্মদিনের দৃশ্যমানতা (Birthday Visibility)</label>
                                <select name="birthday_visibility" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="public" {{ ($privacySettings->birthday_visibility ?? '') === 'public' ? 'selected' : '' }}>পাবলিক (সবাই)</option>
                                    <option value="friends" {{ ($privacySettings->birthday_visibility ?? 'friends') === 'friends' ? 'selected' : '' }}>শুধুমাত্র বন্ধুরা</option>
                                    <option value="only_me" {{ ($privacySettings->birthday_visibility ?? '') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি</option>
                                </select>
                            </div>
                        </div>

                        <!-- Quick Profile Security Toggles -->
                        <div style="padding:14px; background:var(--fb-hover); border-radius:var(--radius-md); border:1px solid var(--fb-border);">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                                <div>
                                    <div style="font-size:13px;font-weight:700;color:var(--fb-text-primary);">🔒 প্রোফাইল লক (Profile Lock)</div>
                                    <div style="font-size:12px;color:var(--fb-text-secondary);">অপরিচিতদের জন্য ফটো ও পোস্ট ফুল-ভিউ সম্পূর্ণ বন্ধ রাখুন</div>
                                </div>
                                <button type="button" class="fb-btn {{ ($profile['is_profile_locked'] ?? false) ? 'fb-btn-primary' : 'fb-btn-secondary' }}" style="padding:4px 12px;font-size:12px;" onclick="closeModal('privacyModal'); openProfileLockModal();">
                                    {{ ($profile['is_profile_locked'] ?? false) ? 'আনলক করুন' : 'লক করুন' }}
                                </button>
                            </div>
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <div>
                                    <div style="font-size:13px;font-weight:700;color:var(--fb-text-primary);">🛡️ অবতার প্রোটেকশন গার্ড (Avatar Guard)</div>
                                    <div style="font-size:12px;color:var(--fb-text-secondary);">প্রোফাইল ছবি ডাউনলোড ও স্ক্রিনশট নেওয়া রোধ করুন</div>
                                </div>
                                <button type="button" class="fb-btn {{ ($profile['has_avatar_guard'] ?? false) ? 'fb-btn-primary' : 'fb-btn-secondary' }}" style="padding:4px 12px;font-size:12px;" onclick="toggleAvatarGuardAction();">
                                    {{ ($profile['has_avatar_guard'] ?? false) ? 'সক্রিয় আছে' : 'গার্ড চালু করুন' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: INTERACTIONS & POSTS -->
                    <div id="privacyTabContent-interactions" class="privacy-subtab-pane" style="display:none;">
                        <div style="padding: 16px; background: var(--fb-bg); border-radius: var(--radius-md); border: 1px solid var(--fb-border);">
                            <div style="font-weight: 700; font-size: 13px; color: var(--fb-text-primary); text-transform: uppercase; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                <span>পোস্ট ও ফ্রেন্ড রিকোয়েস্ট নিয়ন্ত্রণ</span>
                            </div>

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">নতুন পোস্টের ডিফল্ট প্রাইভেসি</label>
                                <select name="post_default_privacy" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="public" {{ ($privacySettings->post_default_privacy ?? 'public') === 'public' ? 'selected' : '' }}>পাবলিক (Public)</option>
                                    <option value="friends" {{ ($privacySettings->post_default_privacy ?? '') === 'friends' ? 'selected' : '' }}>শুধুমাত্র বন্ধুরা</option>
                                    <option value="only_me" {{ ($privacySettings->post_default_privacy ?? '') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">কে বন্ধুত্বের অনুরোধ পাঠাতে পারবে</label>
                                <select name="who_can_send_friend_requests" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="everyone" {{ ($privacySettings->who_can_send_friend_requests ?? 'everyone') === 'everyone' ? 'selected' : '' }}>যেকেউ (Everyone)</option>
                                    <option value="friends_of_friends" {{ ($privacySettings->who_can_send_friend_requests ?? '') === 'friends_of_friends' ? 'selected' : '' }}>বন্ধুদের বন্ধুরা (Friends of Friends)</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">কে আমাকে ফলো করতে পারবে</label>
                                <select name="who_can_follow_me" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="everyone" {{ ($privacySettings->who_can_follow_me ?? 'everyone') === 'everyone' ? 'selected' : '' }}>সবাই (Everyone)</option>
                                    <option value="friends" {{ ($privacySettings->who_can_follow_me ?? '') === 'friends' ? 'selected' : '' }}>শুধুমাত্র বন্ধুরা</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 6px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">কে সরাসরি মেসেজ পাঠাতে পারবে</label>
                                <select name="who_can_message_me" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="everyone" {{ ($privacySettings->who_can_message_me ?? 'everyone') === 'everyone' ? 'selected' : '' }}>সবাই (Everyone)</option>
                                    <option value="friends" {{ ($privacySettings->who_can_message_me ?? '') === 'friends' ? 'selected' : '' }}>শুধুমাত্র বন্ধুরা</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: DISCOVERY & STATUS -->
                    <div id="privacyTabContent-discovery" class="privacy-subtab-pane" style="display:none;">
                        <div style="padding: 16px; background: var(--fb-bg); border-radius: var(--radius-md); border: 1px solid var(--fb-border); margin-bottom: 14px;">
                            <div style="font-weight: 700; font-size: 13px; color: var(--fb-text-primary); text-transform: uppercase; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <span>যোগাযোগ ও অনুসন্ধান (Discovery)</span>
                            </div>

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">ইমেইল ঠিকানার দৃশ্যমানতা</label>
                                <select name="email_visibility" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="only_me" {{ ($privacySettings->email_visibility ?? 'only_me') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি (গোপন)</option>
                                    <option value="friends" {{ ($privacySettings->email_visibility ?? '') === 'friends' ? 'selected' : '' }}>বন্ধুরা দেখতে পাবে</option>
                                    <option value="public" {{ ($privacySettings->email_visibility ?? '') === 'public' ? 'selected' : '' }}>পাবলিক (সবাই)</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label" style="font-weight:600;font-size:13px;color:var(--fb-text-primary);">ফোন নম্বরের দৃশ্যমানতা</label>
                                <select name="phone_visibility" class="form-control" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-card);color:var(--fb-text-primary);">
                                    <option value="only_me" {{ ($privacySettings->phone_visibility ?? 'only_me') === 'only_me' ? 'selected' : '' }}>শুধুমাত্র আমি (গোপন)</option>
                                    <option value="friends" {{ ($privacySettings->phone_visibility ?? '') === 'friends' ? 'selected' : '' }}>বন্ধুরা দেখতে পাবে</option>
                                    <option value="public" {{ ($privacySettings->phone_visibility ?? '') === 'public' ? 'selected' : '' }}>পাবলিক (সবাই)</option>
                                </select>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 6px;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 600; color: var(--fb-text-primary);">সার্চ ইঞ্জিনে প্রোফাইল ইনডেক্সিং</div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">Google বা অন্যান্য সার্চ ইঞ্জিনে প্রোফাইল রেজাল্ট আসবে</div>
                                </div>
                                <input type="checkbox" name="search_engine_indexing" value="1" {{ ($privacySettings->search_engine_indexing ?? true) ? 'checked' : '' }} style="width: 20px; height: 20px; accent-color:#0084ff;">
                            </div>
                        </div>

                        <div style="padding: 16px; background: var(--fb-bg); border-radius: var(--radius-md); border: 1px solid var(--fb-border);">
                            <div style="font-weight: 700; font-size: 13px; color: var(--fb-text-primary); text-transform: uppercase; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                                <span>সক্রিয় স্ট্যাটাস ও রিড রিসিট</span>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 600; color: var(--fb-text-primary);">সক্রিয় / অনলাইন স্ট্যাটাস প্রদর্শন</div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">আপনি সক্রিয় থাকলে বন্ধুদের কাছে সবুজ সংকেত প্রদর্শিত হবে</div>
                                </div>
                                <input type="checkbox" name="show_online_status" value="1" {{ ($privacySettings->show_online_status ?? true) ? 'checked' : '' }} style="width: 20px; height: 20px; accent-color:#0084ff;">
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 600; color: var(--fb-text-primary);">মেসেজ রিড রিসিট (Seen Indicators)</div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">মেসেজ পড়া হলে প্রেরককে দৃশ্যমান করবে</div>
                                </div>
                                <input type="checkbox" name="read_receipts_enabled" value="1" {{ ($privacySettings->read_receipts_enabled ?? true) ? 'checked' : '' }} style="width: 20px; height: 20px; accent-color:#0084ff;">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: SECURITY & DEVICES -->
                    <div id="privacyTabContent-security" class="privacy-subtab-pane" style="display:none;">
                        <div style="padding:16px; background:var(--fb-bg); border-radius:var(--radius-md); border:1px solid var(--fb-border); margin-bottom:14px;">
                            <div style="font-weight:700; font-size:13px; color:var(--fb-text-primary); text-transform:uppercase; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                <span>অ্যাকাউন্ট নিরাপত্তা ও সুরক্ষা</span>
                            </div>
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--fb-border);">
                                <div>
                                    <div style="font-size:13px;font-weight:700;color:var(--fb-text-primary);">পাসওয়ার্ড পরিবর্তন</div>
                                    <div style="font-size:12px;color:var(--fb-text-secondary);">আপনার বর্তমান পাসওয়ার্ড পরিবর্তন ও শক্তিশালী করুন</div>
                                </div>
                                <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('privacyModal'); openSecurityModal(); switchSecuritySubtab('password');" style="padding:4px 12px;font-size:12px;">
                                    পরিবর্তন ➔
                                </button>
                            </div>
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--fb-border);">
                                <div>
                                    <div style="font-size:13px;font-weight:700;color:var(--fb-text-primary);">টু-ফ্যাক্টর অথেনটিকেশন (2FA)</div>
                                    <div style="font-size:12px;color:var(--fb-text-secondary);">লগইনের সময় ওটিপি বা প্রমাণীকরণ অ্যাপের মাধ্যমে অতিরিক্ত সুরক্ষা</div>
                                </div>
                                <a href="/settings/two-factor" class="fb-btn fb-btn-secondary" style="padding:4px 12px;font-size:12px;text-decoration:none;">
                                    কনফিগারেশন ➔
                                </a>
                            </div>
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;">
                                <div>
                                    <div style="font-size:13px;font-weight:700;color:var(--fb-text-primary);">সক্রিয় ডিভাইস ও সেশন</div>
                                    <div style="font-size:12px;color:var(--fb-text-secondary);">কোন কোন ব্রাউজার ও ডিভাইসে আপনার অ্যাকাউন্ট সক্রিয় আছে</div>
                                </div>
                                <a href="/devices" class="fb-btn fb-btn-secondary" style="padding:4px 12px;font-size:12px;text-decoration:none;">
                                    ডিভাইস তালিকা ➔
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: BLOCKING CENTER -->
                    <div id="privacyTabContent-blocking" class="privacy-subtab-pane" style="display:none;">
                        <div style="padding:16px; background:var(--fb-bg); border-radius:var(--radius-md); border:1px solid var(--fb-border);">
                            <div style="font-weight:700; font-size:13px; color:var(--fb-text-primary); text-transform:uppercase; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                <span>ব্লক করা ব্যবহারকারীদের তালিকা</span>
                            </div>
                            <div style="font-size:12px; color:var(--fb-text-secondary); margin-bottom:14px;">
                                কোনো ব্যক্তিকে ব্লক করলে সে আপনার পোস্ট দেখতে পারবে না, মেসেজ বা রিকোয়েস্ট পাঠাতে পারবে না।
                            </div>
                            <div id="privacyBlockedUsersList" style="min-height:80px; display:flex; flex-direction:column; gap:8px;">
                                <div style="text-align:center; padding:16px; color:var(--fb-text-secondary); font-size:13px;">
                                    তালিকা লোড হচ্ছে...
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer" style="padding: 14px 20px; display: flex; justify-content: flex-end; gap: 10px; border-top:1px solid var(--fb-border);">
                    <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('privacyModal')">বাতিল</button>
                    <button type="submit" class="fb-btn fb-btn-primary" id="savePrivacyBtn" style="background:#0084ff;color:#fff;border:none;font-weight:700;padding:8px 20px;border-radius:8px;">
                        প্রাইভেসি সংরক্ষণ করুন
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- POST CREATION MODAL (FULL INTERACTIVE DESKTOP/MOBILE COMPOSER) -->
    <div class="fb-modal-overlay" id="postModal">
        <div class="fb-modal-card" style="max-width: 560px;">
            <div class="modal-header" style="padding:14px 20px; border-bottom:1px solid var(--fb-border); display:flex; align-items:center; justify-content:space-between;">
                <span class="modal-title" style="font-size:16px; font-weight:800; color:var(--fb-text-primary);">পোস্ট তৈরি করুন</span>
                <button class="modal-close-btn" onclick="closeModal('postModal')" style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--fb-text-secondary);">✕</button>
            </div>
            <form id="postCreateModalForm" onsubmit="submitPostModalForm(event)">
                <div class="modal-body" style="padding:18px 20px;">
                    <!-- User Info & Audience -->
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                        <img src="{{ $profile['avatar'] ?? '/images/default-avatar.svg' }}" alt="{{ $profile['name'] }}" style="width:42px; height:42px; border-radius:50%; object-fit:cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                        <div>
                            <div style="font-size:14px; font-weight:700; color:var(--fb-text-primary);">{{ $profile['name'] }}</div>
                            <select name="privacy" id="postModalPrivacy" style="padding:2px 8px; border-radius:6px; font-size:11px; font-weight:600; border:1px solid var(--fb-border); background:var(--fb-hover); color:var(--fb-text-primary); cursor:pointer;">
                                <option value="public">🌐 সবাই (Public)</option>
                                <option value="friends">👥 শুধুমাত্র বন্ধুরা</option>
                                <option value="only_me">🔒 শুধুমাত্র আমি</option>
                            </select>
                        </div>
                    </div>

                    <!-- Post Text Area -->
                    <textarea name="content" id="postModalTextarea" placeholder="আপনার মনে কি চলছে, {{ explode(' ', $profile['name'])[0] }}?" style="width:100%; min-height:110px; border:none; resize:none; font-size:15px; font-family:inherit; background:transparent; color:var(--fb-text-primary); outline:none;" required></textarea>

                    <!-- Media Upload Preview Box -->
                    <div id="postModalMediaPreview" style="display:none; margin-top:12px; position:relative; border-radius:10px; overflow:hidden; max-height:220px; background:#000;">
                        <img id="postModalImgPreview" src="" alt="Preview" style="width:100%; max-height:220px; object-fit:contain; display:none;">
                        <video id="postModalVidPreview" src="" controls style="width:100%; max-height:220px; display:none;"></video>
                        <button type="button" onclick="clearPostModalMedia()" style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.7); color:#fff; border:none; width:26px; height:26px; border-radius:50%; cursor:pointer; font-weight:700;">✕</button>
                    </div>

                    <input type="file" id="postModalFileInput" name="media" accept="image/*,video/*" style="display:none;" onchange="previewPostModalFile(this)">

                    <!-- Action Attachments Toolbar -->
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border:1px solid var(--fb-border); border-radius:10px; margin-top:14px; background:var(--fb-hover);">
                        <span style="font-size:13px; font-weight:600; color:var(--fb-text-primary);">পোস্টে যোগ করুন</span>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button type="button" onclick="document.getElementById('postModalFileInput').click()" style="background:none;border:none;cursor:pointer;display:inline-flex;align-items:center;padding:4px;" title="ফটো বা ভিডিও যোগ করুন">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            </button>
                            <button type="button" onclick="closeModal('postModal'); openLifeEventModal();" style="background:none;border:none;cursor:pointer;display:inline-flex;align-items:center;padding:4px;" title="লাইভ ইভেন্ট">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="padding:14px 20px; border-top:1px solid var(--fb-border);">
                    <button type="submit" id="submitPostModalBtn" class="fb-btn fb-btn-primary" style="width:100%; background:#0084ff; color:#fff; border:none; padding:10px; border-radius:8px; font-size:14px; font-weight:700;">
                        পোস্ট প্রকাশ করুন
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SECURITY & DEVICES CENTER MODAL -->
    <div class="fb-modal-overlay" id="securityModal">
        <div class="fb-modal-card" style="max-width: 640px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                    <span>সিকিউরিটি ও ডিভাইস সেন্টার</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('securityModal')">✕</button>
            </div>
            
            <!-- Security Subtabs -->
            <div style="display:flex;border-bottom:1px solid var(--fb-border);background:var(--fb-bg);padding:0 16px;">
                <button type="button" class="security-subtab-btn active" id="secTabBtn-devices" onclick="switchSecuritySubtab('devices')" style="padding:12px 16px;border:none;background:none;font-weight:700;font-size:13px;cursor:pointer;color:var(--fb-primary);border-bottom:2px solid var(--fb-primary);display:inline-flex;align-items:center;gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                    <span>সক্রিয় ডিভাইসসমূহ</span>
                </button>
                <button type="button" class="security-subtab-btn" id="secTabBtn-password" onclick="switchSecuritySubtab('password')" style="padding:12px 16px;border:none;background:none;font-weight:600;font-size:13px;cursor:pointer;color:var(--fb-text-secondary);border-bottom:2px solid transparent;display:inline-flex;align-items:center;gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <span>পাসওয়ার্ড পরিবর্তন</span>
                </button>
                <button type="button" class="security-subtab-btn" id="secTabBtn-twofactor" onclick="switchSecuritySubtab('twofactor')" style="padding:12px 16px;border:none;background:none;font-weight:600;font-size:13px;cursor:pointer;color:var(--fb-text-secondary);border-bottom:2px solid transparent;display:inline-flex;align-items:center;gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <span>দ্বি-স্তরীয় নিরাপত্তা (2FA)</span>
                </button>
            </div>

            <div class="modal-body" style="max-height: 65vh; overflow-y: auto; padding: 20px;">
                <!-- SUBTAB 1: DEVICES -->
                <div id="secTabContent-devices">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
                        <div>
                            <div style="font-weight:700;font-size:14px;color:var(--fb-text-primary);">যেসব ডিভাইসে আপনার অ্যাকাউন্ট লগইন আছে</div>
                            <div style="font-size:12px;color:var(--fb-text-secondary);">অপরিচিত কোনো ডিভাইস দেখলে সাথে সাথে সেশন বাতিল করুন</div>
                        </div>
                        <button type="button" class="fb-btn fb-btn-secondary" onclick="logoutAllDevicesFromSecurityCenter()" style="font-size:12px;padding:6px 12px;color:var(--fb-red);border-color:#fecaca;">
                            অন্যান্য ডিভাইস লগআউট
                        </button>
                    </div>

                    <div id="securityDevicesList" style="display:flex;flex-direction:column;gap:10px;">
                        <div style="text-align:center;padding:24px;color:var(--fb-text-secondary);font-size:13px;">ডিভাইস তথ্য লোড হচ্ছে...</div>
                    </div>
                </div>

                <!-- SUBTAB 2: PASSWORD -->
                <div id="secTabContent-password" style="display:none;">
                    <form id="secPasswordForm" onsubmit="submitChangePassword(event)">
                        <div class="form-group">
                            <label class="form-label">বর্তমান পাসওয়ার্ড (Current Password) *</label>
                            <input type="password" name="current_password" id="secCurrentPassword" class="form-control" required placeholder="আপনার বর্তমান পাসওয়ার্ড লিখুন">
                        </div>
                        <div class="form-group">
                            <label class="form-label">নতুন পাসওয়ার্ড (New Password) *</label>
                            <input type="password" name="password" id="secNewPassword" class="form-control" required placeholder="কমপক্ষে ৮ অক্ষরের শক্তিশালী পাসওয়ার্ড" oninput="checkPasswordStrength(this.value)">
                            <div style="margin-top:6px;">
                                <div style="height:5px;width:100%;background:#e5e7eb;border-radius:3px;overflow:hidden;">
                                    <div id="pwdStrengthBar" style="height:100%;width:0;background:#ef4444;transition:width 0.3s, background 0.3s;"></div>
                                </div>
                                <div id="pwdStrengthText" style="font-size:11px;color:var(--fb-text-secondary);margin-top:4px;">পাসওয়ার্ডে বড়-ছোট অক্ষর, সংখ্যা এবং বিশেষ চিহ্ন ব্যবহার করুন।</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">নতুন পাসওয়ার্ড নিশ্চিত করুন (Confirm Password) *</label>
                            <input type="password" name="password_confirmation" id="secConfirmPassword" class="form-control" required placeholder="নতুন পাসওয়ার্ডটি পুনরায় লিখুন">
                        </div>
                        <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                            <button type="submit" class="fb-btn fb-btn-primary" id="secSavePasswordBtn">পাসওয়ার্ড আপডেট করুন</button>
                        </div>
                    </form>
                </div>

                <!-- SUBTAB 3: 2FA -->
                <div id="secTabContent-twofactor" style="display:none;">
                    <div style="text-align:center;padding:16px 0;">
                        <div style="width:54px;height:54px;border-radius:50%;background:#ecfdf5;color:#059669;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
                        </div>
                        <h4 style="font-size:16px;font-weight:700;color:var(--fb-text-primary);margin-bottom:6px;">দ্বি-স্তরীয় যাচাইকরণ ও অ্যাকাউন্ট গার্ড</h4>
                        <p style="font-size:13px;color:var(--fb-text-secondary);line-height:1.6;max-width:440px;margin:0 auto 16px;">
                            প্রতিবার নতুন কোনো ব্রাউজার বা ডিভাইসে লগইন করার সময় এসএমএস বা অথেন্টিকেটর অ্যাপ কোড প্রয়োজন হবে।
                        </p>
                        <a href="/settings/two-factor" class="fb-btn fb-btn-primary" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <span>2FA সিকিউরিটি কনফিগার করুন</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer" style="padding:12px 20px;">
                <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('securityModal')">বন্ধ করুন</button>
            </div>
        </div>
    </div>

    <!-- BLOCKING & RESTRICTION CENTER MODAL -->
    <div class="fb-modal-overlay" id="blockingCenterModal">
        <div class="fb-modal-card" style="max-width: 540px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                    <span>ব্লকিং ও বিধিনিষেধ সেন্টার</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('blockingCenterModal')">✕</button>
            </div>
            <div class="modal-body" style="max-height:65vh;overflow-y:auto;padding:20px;">
                <div style="font-size:13px;color:var(--fb-text-secondary);margin-bottom:16px;line-height:1.5;">
                    আপনি যেসকল অ্যাকাউন্ট ব্লক করেছেন তাদের তালিকা। ব্লক করা ব্যক্তি আপনার প্রোফাইল বা পোস্ট দেখতে পারবে না এবং আপনাকে কোনো বার্তা পাঠাতে পারবে না।
                </div>

                <div id="blockedUsersContainer" style="display:flex;flex-direction:column;gap:10px;">
                    <div style="text-align:center;padding:24px;color:var(--fb-text-secondary);font-size:13px;">ব্লক করা ব্যবহারকারীদের তালিকা লোড হচ্ছে...</div>
                </div>
            </div>
            <div class="modal-footer" style="padding:12px 20px;">
                <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('blockingCenterModal')">বন্ধ করুন</button>
            </div>
        </div>
    </div>

    <!-- STORY CREATION MODAL -->
    <div class="fb-modal-overlay" id="storyModal">
        <div class="fb-modal-card" style="max-width: 500px;">
            <div class="modal-header">
                <span class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    <span>নতুন স্টোরি যোগ করুন</span>
                </span>
                <button class="modal-close-btn" onclick="closeModal('storyModal')">✕</button>
            </div>
            <form id="storyForm" onsubmit="submitStory(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">স্টোরির ধরন</label>
                        <select name="type" id="storyTypeSelect" class="form-control" onchange="toggleStoryType(this.value)">
                            <option value="photo">ছবি বা ফটো স্টোরি (Photo Story)</option>
                            <option value="text">টেক্সট বা বাণী স্টোরি (Text Story)</option>
                        </select>
                    </div>
                    <div class="form-group" id="storyMediaGroup">
                        <label class="form-label">ছবি নির্বাচন করুন (JPG, PNG, WebP)</label>
                        <input type="file" name="media" id="storyFileInput" class="form-control" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label">স্টোরি ক্যাপশন বা বার্তা</label>
                        <textarea name="caption" id="storyCaptionInput" class="form-control" placeholder="আপনার স্টোরিতে কিছু লিখুন..." style="min-height: 80px;"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">প্রাইভেসি</label>
                        <select name="privacy" class="form-control">
                            <option value="friends">শুধুমাত্র বন্ধুরা (Friends)</option>
                            <option value="public">সবাই দেখতে পাবে (Public)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="fb-btn fb-btn-secondary" onclick="closeModal('storyModal')">বাতিল</button>
                    <button type="submit" class="fb-btn fb-btn-primary" id="submitStoryBtn">স্টোরি শেয়ার করুন</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- ENHANCED FULLSCREEN IMAGE LIGHTBOX MODAL -->
    <div class="modal-overlay" id="imageLightboxModal" style="display: none; position: fixed; inset: 0; z-index: 100010; background: rgba(10, 15, 29, 0.95); backdrop-filter: blur(12px); flex-direction: column; justify-content: space-between; padding: 16px; user-select: none;" onclick="closeLightboxModal()">
        <!-- Top Toolbar -->
        <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; color: white; padding: 4px 12px; z-index: 10;" onclick="event.stopPropagation()">
            <div id="lightboxTitle" style="font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.85); display: flex; align-items: center; gap: 8px;">
                <span id="lightboxCounter">ছবি প্রিভিউ</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="lightbox-action-btn" onclick="zoomLightboxImage(0.25)" title="জুম ইন (+)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                </button>
                <button type="button" class="lightbox-action-btn" onclick="zoomLightboxImage(-0.25)" title="জুম আউট (-)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                </button>
                <button type="button" class="lightbox-action-btn" onclick="resetLightboxZoom()" title="রিসেট">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                </button>
                <a id="lightboxDownloadBtn" href="" download target="_blank" class="lightbox-action-btn" title="ডাউনলোড" style="text-decoration: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </a>
                <button type="button" class="lightbox-action-btn" onclick="closeLightboxModal()" title="বন্ধ করুন (Esc)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </div>

        <!-- Center Viewport with Nav Arrows -->
        <div style="flex: 1; position: relative; width: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden;" onclick="event.stopPropagation()">
            <button id="lightboxPrevBtn" type="button" onclick="navigateLightbox(-1)" style="display: none; position: absolute; left: 16px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.15); border: none; color: white; width: 44px; height: 44px; border-radius: 50%; cursor: pointer; align-items: center; justify-content: center; z-index: 5; transition: background 0.2s;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>

            <img id="lightboxImage" src="" alt="ছবি প্রিভিউ" style="max-width: 90vw; max-height: 82vh; object-fit: contain; border-radius: 8px; box-shadow: 0 12px 48px rgba(0,0,0,0.8); transition: transform 0.2s ease, opacity 0.2s ease;">

            <button id="lightboxNextBtn" type="button" onclick="navigateLightbox(1)" style="display: none; position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.15); border: none; color: white; width: 44px; height: 44px; border-radius: 50%; cursor: pointer; align-items: center; justify-content: center; z-index: 5; transition: background 0.2s;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </div>
    </div>

    <!-- REAL-TIME ENTERPRISE FLOATING MESSENGER CHAT BOX -->
    <div id="messengerChatBox" class="jj-chat-box" style="display: none;">
        <!-- Chat Header -->
        <div class="jj-chat-header">
            <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;">
                <div style="position: relative; flex-shrink: 0;">
                    <div class="avatar" id="messengerChatAvatar" style="width: 36px; height: 36px; font-size: 14px;">র</div>
                    <span id="messengerChatOnlineDot" class="status-indicator online" style="position: absolute; bottom: 0; right: 0; border: 2px solid white;"></span>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div id="messengerChatTitle" style="font-weight: 700; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">চ্যাট</div>
                    <div id="messengerChatSubtitle" style="font-size: 11px; opacity: 0.85; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">অফলাইন</div>
                </div>
            </div>
            <div class="jj-chat-header-actions" style="display: flex; align-items: center; gap: 3px;">
                <div style="position: relative;">
                    <button type="button" class="jj-chat-header-btn" id="chatMoreMenuBtn" title="আরও অপশন" onclick="toggleChatOptionsMenu(event)">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="1.5"></circle><circle cx="12" cy="5" r="1.5"></circle><circle cx="12" cy="19" r="1.5"></circle></svg>
                    </button>
                    <div id="chatOptionsMenuDropdown" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 4px; background: white; border: 1px solid var(--fb-border); box-shadow: 0 4px 14px rgba(0,0,0,0.12); border-radius: 8px; width: 220px; z-index: 1000; padding: 4px 0;">
                        <button type="button" onclick="openMultiFriendCallModal(activeChatConversationId, activeChatUser?.name, activeChatUser?.id)" style="width: 100%; display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: none; border: none; font-size: 13px; color: #1e293b; cursor: pointer; text-align: left;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1877f2" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span>মাল্টি-ফ্রেন্ড কল</span>
                        </button>
                        <button type="button" onclick="confirmClearChatHistory()" style="width: 100%; display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: none; border: none; font-size: 13px; color: #dc2626; cursor: pointer; text-align: left;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='none'">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            <span>চ্যাট হিস্ট্রি ক্লিয়ার করুন</span>
                        </button>
                    </div>
                </div>
                <button type="button" class="jj-chat-header-btn" id="expandChatWidthBtn" title="প্রস্থ পরিবর্তন করুন (বড় / স্বাভাবিক)" onclick="toggleChatWidth(event)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>
                </button>
                <button type="button" class="jj-chat-header-btn" title="অডিও কল" onclick="startChatCall('audio')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                </button>
                <button type="button" class="jj-chat-header-btn" title="ভিডিও কল" onclick="startChatCall('video')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                </button>
                <button type="button" class="jj-chat-header-btn" id="multiFriendCallBtn" title="মাল্টি-ফ্রেন্ড কল (অন্য বন্ধুদের সাথে যুক্ত করে কল)" onclick="openMultiFriendCallModal(activeChatConversationId, activeChatUser?.name, activeChatUser?.id)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                </button>
                <button type="button" class="jj-chat-header-btn" id="minimizeRealChatBtn" title="ছোট করুন" onclick="minimizeRealChat(event)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </button>
                <button type="button" class="jj-chat-header-btn" id="closeRealChatBtn" title="বন্ধ করুন" onclick="closeRealChat(event)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </div>

        <!-- Clear Chat History Confirmation Modal -->
        <div id="clearChatConfirmModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 12px; max-width: 420px; width: 90%; padding: 20px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; color: #dc2626; font-size: 20px;">⚠️</div>
                    <h3 style="font-size: 17px; font-weight: 700; color: #1e293b; margin: 0;">Clear chat history?</h3>
                </div>
                <p style="font-size: 13px; line-height: 1.5; color: #475569; margin-bottom: 8px;">
                    This will remove the conversation history from your chat view. It will not delete the other participant's copy.
                </p>
                <p style="font-size: 12px; line-height: 1.5; color: #64748b; margin-bottom: 18px;">
                    (এটি শুধুমাত্র আপনার চ্যাট ভিউ থেকে মেসেজগুলো মুছে ফেলবে। অপর প্রান্তের ব্যবহারকারীর চ্যাট কপি অক্ষত থাকবে।)
                </p>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeClearChatModal()" style="padding: 8px 16px; border-radius: 6px; border: 1px solid #cbd5e1; background: white; font-size: 13px; font-weight: 600; cursor: pointer;">Cancel / বাতিল</button>
                    <button type="button" id="confirmClearChatBtn" onclick="executeClearChatHistory()" style="padding: 8px 16px; border-radius: 6px; border: none; background: #dc2626; color: white; font-size: 13px; font-weight: 600; cursor: pointer;">Clear / ক্লিয়ার করুন</button>
                </div>
            </div>
        </div>

        <!-- MULTI-FRIEND CALLING MODAL -->
        <div id="multiFriendCallModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div style="background: white; border-radius: 16px; max-width: 440px; width: 92%; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; border: 1px solid #e2e8f0;">
                <!-- Header -->
                <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(24, 119, 242, 0.12); color: #1877f2; display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 15px; color: #0f172a;">মাল্টি-ফ্রেন্ড কল</div>
                            <div style="font-size: 11.5px; color: #64748b;">বন্ধুদের যুক্ত করে একসাথে কল শুরু করুন</div>
                        </div>
                    </div>
                    <button type="button" onclick="closeMultiFriendCallModal()" style="background: none; border: none; font-size: 18px; color: #64748b; cursor: pointer; padding: 4px; border-radius: 50%;" title="বন্ধ করুন">✕</button>
                </div>
                <!-- Search bar -->
                <div style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; background: white;">
                    <div style="display: flex; align-items: center; gap: 8px; background: #f1f5f9; border-radius: 20px; padding: 6px 14px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="multiCallSearchInput" placeholder="বন্ধু খুঁজুন..." oninput="filterMultiCallFriends(this.value)" style="border: none; background: transparent; outline: none; width: 100%; font-size: 13px;">
                    </div>
                </div>
                <!-- Friend List -->
                <div id="multiCallFriendsList" style="flex: 1; overflow-y: auto; max-height: 320px; padding: 8px 12px;">
                    <div style="text-align: center; color: #64748b; font-size: 13px; padding: 24px;">বন্ধুদের তালিকা লোড হচ্ছে...</div>
                </div>
                <!-- Footer -->
                <div style="padding: 14px 18px; border-top: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                    <span id="multiCallSelectedCount" style="font-size: 12.5px; font-weight: 600; color: #64748b;">০ জন নির্বাচিত</span>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" onclick="startMultiFriendCall('audio')" style="display: flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 20px; border: 1px solid #1877f2; background: white; color: #1877f2; font-size: 12.5px; font-weight: 700; cursor: pointer; transition: all 0.15s ease;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <span>অডিও কল</span>
                        </button>
                        <button type="button" onclick="startMultiFriendCall('video')" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 20px; border: none; background: #1877f2; color: white; font-size: 12.5px; font-weight: 700; cursor: pointer; transition: all 0.15s ease; box-shadow: 0 4px 10px rgba(24, 119, 242, 0.3);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                            <span>ভিডিও কল</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Messages Container -->
        <div id="messengerChatMessages" class="jj-chat-messages">
            <!-- Dynamically populated -->
        </div>

        <!-- Typing Indicator Banner -->
        <div id="messengerTypingIndicator" style="display: none; padding: 4px 14px; font-size: 11px; color: var(--fb-text-secondary); background: #f8fafc; font-style: italic;">
            টাইপ করছেন...
        </div>

        <!-- Voice Recording Floating Control Bar -->
        <div id="voiceRecordingBar" style="display: none; background: #fff1f2; border-top: 1px solid #fecdd3; padding: 8px 14px; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444; animation: pulse 1s infinite;"></span>
                <span style="font-weight: 700; font-size: 13px; color: #b91c1c;">ভয়েস রেকর্ড হচ্ছে...</span>
                <span id="voiceRecordTimer" style="font-size: 12px; font-weight: 800; color: #b91c1c; font-variant-numeric: tabular-nums;">00:00</span>
            </div>
            <div style="display: flex; gap: 6px;">
                <button type="button" onclick="cancelVoiceRecording()" style="background: white; border: 1px solid #fca5a5; color: #ef4444; padding: 4px 10px; border-radius: 14px; font-size: 12px; font-weight: 700; cursor: pointer;">বাতিল</button>
                <button type="button" onclick="stopVoiceRecordingAndSend()" style="background: #ef4444; border: none; color: white; padding: 4px 12px; border-radius: 14px; font-size: 12px; font-weight: 700; cursor: pointer;">পাঠান 🎙️</button>
            </div>
        </div>

        <!-- Quick Emoji Popover -->
        <div id="chatEmojiPicker" style="display: none; position: absolute; bottom: 58px; right: 10px; width: 260px; background: white; border: 1px solid var(--fb-border); border-radius: 12px; box-shadow: var(--shadow-lg); padding: 10px; z-index: 105;">
            <div style="font-size: 12px; font-weight: 700; color: var(--fb-text-secondary); margin-bottom: 6px;">ইমোজি নির্বাচন করুন</div>
            <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; font-size: 20px; text-align: center;">
                <span class="emoji-chip" onclick="insertChatEmoji('👍')">👍</span>
                <span class="emoji-chip" onclick="insertChatEmoji('❤️')">❤️</span>
                <span class="emoji-chip" onclick="insertChatEmoji('😊')">😊</span>
                <span class="emoji-chip" onclick="insertChatEmoji('😂')">😂</span>
                <span class="emoji-chip" onclick="insertChatEmoji('🔥')">🔥</span>
                <span class="emoji-chip" onclick="insertChatEmoji('🎉')">🎉</span>
                <span class="emoji-chip" onclick="insertChatEmoji('👏')">👏</span>
                <span class="emoji-chip" onclick="insertChatEmoji('🙏')">🙏</span>
                <span class="emoji-chip" onclick="insertChatEmoji('😍')">😍</span>
                <span class="emoji-chip" onclick="insertChatEmoji('🥺')">🥺</span>
                <span class="emoji-chip" onclick="insertChatEmoji('🥳')">🥳</span>
                <span class="emoji-chip" onclick="insertChatEmoji('✨')">✨</span>
                <span class="emoji-chip" onclick="insertChatEmoji('💯')">💯</span>
                <span class="emoji-chip" onclick="insertChatEmoji('😮')">😮</span>
                <span class="emoji-chip" onclick="insertChatEmoji('😢')">😢</span>
                <span class="emoji-chip" onclick="insertChatEmoji('😡')">😡</span>
                <span class="emoji-chip" onclick="insertChatEmoji('🤝')">🤝</span>
                <span class="emoji-chip" onclick="insertChatEmoji('💪')">💪</span>
            </div>
        </div>

        <!-- Chat Composer Bar -->
        <div class="jj-chat-composer">
            <input type="file" id="chatAttachmentInput" style="display: none;" onchange="handleChatFileUpload(event)" accept="image/*,video/*,.pdf,.doc,.docx,.zip">
            
            <button type="button" class="jj-chat-action-btn" title="ছবি ও ফাইল যুক্ত করুন" onclick="document.getElementById('chatAttachmentInput').click()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
            </button>

            <button type="button" class="jj-chat-action-btn" id="voiceRecordBtn" title="ভয়েস রেকর্ড করুন" onclick="toggleVoiceRecording()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="22"></line></svg>
            </button>

            <button type="button" class="jj-chat-action-btn" title="ইমোজি" onclick="toggleChatEmojiPicker()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
            </button>

            <input type="text" id="messengerInputText" placeholder="মেসেজ লিখুন..." class="jj-chat-input" onkeydown="if(event.key==='Enter') sendChatMessage()" oninput="handleTypingBroadcast()">
            
            <button type="button" class="jj-chat-send-btn" title="পাঠান" onclick="sendChatMessage()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
        </div>
    </div>

    <!-- ADVANCED SOCIAL-STYLE MESSAGE EDIT MODAL (FACEBOOK/TELEGRAM INSPIRED) -->
    <div id="chatEditMessageModal" class="jj-edit-modal-overlay">
        <div class="jj-edit-modal-box">
            <!-- Modal Header -->
            <div class="jj-edit-modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 34px; height: 34px; border-radius: 10px; background: linear-gradient(135deg, #1877f2, #2563eb); display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3); flex-shrink: 0;">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="jj-edit-modal-title">মেসেজ সম্পাদনা করুন</h3>
                        <div style="font-size: 11px; color: var(--fb-text-secondary, #64748b); margin-top: 1px;">সোশ্যাল মিডিয়ার মতো লাইভ এডিট ও আপডেট</div>
                    </div>
                </div>
                <button type="button" onclick="closeChatMessageEditModal()" title="বন্ধ করুন (Esc)" style="width: 32px; height: 32px; border-radius: 50%; border: none; background: rgba(0,0,0,0.06); color: var(--fb-text-secondary, #475569); font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s;" onmouseover="this.style.background='rgba(0,0,0,0.12)';" onmouseout="this.style.background='rgba(0,0,0,0.06)';">✕</button>
            </div>

            <!-- Original Message Snippet -->
            <div style="padding: 12px 18px 0;">
                <div class="jj-edit-quote-box">
                    <div style="font-size: 10px; font-weight: 700; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">পূর্ববর্তী বার্তা (Original):</div>
                    <div id="chatEditOriginalPreview" style="white-space: pre-wrap; word-break: break-word; font-style: italic;">...</div>
                </div>
            </div>

            <!-- Hidden Inputs -->
            <input type="hidden" id="chatEditMessageId" value="">
            <input type="hidden" id="chatEditMessageVersion" value="1">
            <input type="hidden" id="chatEditOriginalText" value="">

            <!-- Textarea Editor -->
            <div style="padding: 12px 18px 6px;">
                <textarea id="chatEditTextarea" 
                    class="jj-edit-textarea"
                    placeholder="আপনার নতুন বার্তা লিখুন..." 
                    oninput="handleChatEditTextareaInput(this)"
                    onkeydown="handleChatEditTextareaKeydown(event)"></textarea>

                <!-- Status & Keyboard Shortcuts Info -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 6px; font-size: 11px; color: var(--fb-text-secondary, #64748b);">
                    <span style="display: flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                        <span><kbd style="background: rgba(0,0,0,0.07); padding: 1px 4px; border-radius: 4px; font-size: 10px; font-family: inherit;">Enter</kbd> সংরক্ষণ</span>
                        <span>·</span>
                        <span><kbd style="background: rgba(0,0,0,0.07); padding: 1px 4px; border-radius: 4px; font-size: 10px; font-family: inherit;">Shift+Enter</kbd> নতুন লাইন</span>
                        <span>·</span>
                        <span><kbd style="background: rgba(0,0,0,0.07); padding: 1px 4px; border-radius: 4px; font-size: 10px; font-family: inherit;">Esc</kbd> বাতিল</span>
                    </span>
                    <span id="chatEditCharCounter" style="font-variant-numeric: tabular-nums; font-weight: 600;">0/2000</span>
                </div>
            </div>

            <!-- Quick Emoji Reactions Strip -->
            <div style="padding: 0 18px 12px;">
                <div style="font-size: 11px; font-weight: 600; color: var(--fb-text-secondary, #64748b); margin-bottom: 6px;">ইমোজি যুক্ত করুন:</div>
                <div style="display: flex; gap: 4px; overflow-x: auto; padding-bottom: 2px; scrollbar-width: none;">
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('👍')">👍</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('❤️')">❤️</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('😊')">😊</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('😂')">😂</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('🔥')">🔥</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('🎉')">🎉</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('👏')">👏</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('🙏')">🙏</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('😍')">😍</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('🥺')">🥺</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('🥳')">🥳</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('✨')">✨</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('💯')">💯</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('😮')">😮</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('😢')">😢</button>
                    <button type="button" class="jj-edit-emoji-btn" onclick="insertChatEditEmoji('🤝')">🤝</button>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="jj-edit-modal-footer">
                <button type="button" onclick="closeChatMessageEditModal()" style="padding: 8px 16px; border-radius: 8px; border: 1px solid var(--fb-border, #cbd5e1); background: transparent; color: var(--fb-text-secondary, #475569); font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s;">বাতিল (Cancel)</button>
                <button type="button" id="chatEditSubmitBtn" onclick="submitChatMessageEdit()" style="padding: 8px 20px; border-radius: 8px; border: none; background: #1877f2; color: white; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(24, 119, 242, 0.3); transition: all 0.15s;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span id="chatEditSubmitBtnText">সংরক্ষণ করুন</span>
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const serverAuthToken = @json($authToken ?? '');
        const targetUsername = @json($profile['username'] ?? '');
        const targetUserId = @json($profile['id'] ?? null);
        const viewerId = @json($viewer?->id ?? null);
        const isOwner = @json((bool) ($isOwner ?? false));

        // ONLY store token in localStorage if it was issued for an authenticated viewer!
        if (serverAuthToken && viewerId) {
            localStorage.setItem('bondhoo_token', serverAuthToken);
            localStorage.setItem('jugajug_token', serverAuthToken);
        }
        let authToken = (viewerId ? (serverAuthToken || localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token')) : '') || '';

        // Global centralized Profile URL and identity resolver
        window.getUserProfileUrl = function(user) {
            if (!user) return '/profile';
            if (typeof user === 'string') {
                const clean = user.replace(/^@/, '').trim();
                return clean ? `/u/${encodeURIComponent(clean)}` : '/profile';
            }
            const username = user.username || user.handle || '';
            if (username) {
                return `/u/${encodeURIComponent(username)}`;
            }
            if (user.id) {
                return `/u/${encodeURIComponent(user.id)}`;
            }
            return '/profile';
        };
        window.resolveUserProfile = function(identity) {
            return window.getUserProfileUrl(identity);
        };

        function toggleProfileTopUserDropdown() {
            const dd = document.getElementById('profileTopUserDropdown');
            if (dd) dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
        }
        window.addEventListener('click', (e) => {
            const dd = document.getElementById('profileTopUserDropdown');
            if (dd && !e.target.closest('#profileTopUserDropdown') && !e.target.closest('.avatar')) {
                dd.style.display = 'none';
            }
        });

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function normalizeMediaUrl(url) {
            if (!url || typeof url !== 'string') return '';
            const match = url.match(/^(?:https?:\/\/[^\/]+)?(\/storage\/.*)$/i);
            return match ? match[1] : url;
        }

        function getAuthHeaders(extra = {}) {
            const headers = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...extra
            };
            if (authToken) {
                headers['Authorization'] = `Bearer ${authToken}`;
            }
            return headers;
        }

        /* ------------------------------------------------------------- */
        /* ENTERPRISE FLOATING MESSENGER LOGIC FOR PROFILE */
        /* ------------------------------------------------------------- */
        let activeChatConversationId = null;
        let activeChatPollingTimer = null;
        let mediaRecorderInstance = null;
        let voiceAudioChunks = [];
        let voiceRecordTimerInterval = null;
        let voiceRecordSeconds = 0;

        function showToast(msg) {
            let toast = document.getElementById('globalToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'globalToast';
                toast.className = 'jj-toast';
                document.body.appendChild(toast);
            }
            toast.innerText = msg;
            toast.style.display = 'block';
            clearTimeout(window.__toastTimer);
            window.__toastTimer = setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        function escapeChatHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        async function openDirectChatWithUser(userId, name, username, avatar) {
            if (!authToken) {
                window.location.href = `/messages?user=${userId}`;
                return;
            }
            try {
                const res = await fetch('/api/v1/conversations', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ recipient_id: userId })
                });
                const data = await res.json();
                if (data.success && data.data) {
                    openRealChat(data.data.id, name, avatar, userId);
                } else {
                    window.location.href = `/messages?user=${userId}`;
                }
            } catch (err) {
                window.location.href = `/messages?user=${userId}`;
            }
        }

        async function openRealChat(convId, title, avatar = '', recipientId = null) {
            activeChatConversationId = convId;
            const box = document.getElementById('messengerChatBox');
            if (!box) return;
            document.getElementById('messengerChatTitle').innerText = title;
            const avatarEl = document.getElementById('messengerChatAvatar');
            if (avatarEl) {
                if (avatar) {
                    avatarEl.innerHTML = `<img src="${avatar}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">`;
                } else {
                    avatarEl.innerText = title.charAt(0);
                }
            }
            box.classList.remove('hidden', 'closed');
            box.classList.remove('minimized');
            box.classList.add('active');
            if (localStorage.getItem('jj_chat_expanded') === '1') {
                box.classList.add('expanded');
                const btn = document.getElementById('expandChatWidthBtn');
                if (btn) {
                    btn.title = 'স্বাভাবিক প্রস্থ করুন';
                    btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>';
                }
            } else {
                box.classList.remove('expanded');
            }
            box.style.setProperty('display', 'flex', 'important');
            box.style.display = 'flex';

            // Realtime Header Presence Update
            const updateActiveChatHeaderPresence = (isOnline, lastSeen = null) => {
                const dot = document.getElementById('messengerChatOnlineDot');
                const sub = document.getElementById('messengerChatSubtitle');
                if (dot) dot.style.background = isOnline ? '#22c55e' : '#94a3b8';
                if (sub) {
                    if (isOnline) {
                        sub.innerHTML = '<span class="contact-online-badge" style="padding: 1px 7px;"><span class="online-pulse-dot"></span>সক্রিয় আছেন</span>';
                    } else if (lastSeen) {
                        sub.innerText = 'সর্বশেষ দেখা গেছে: ' + new Date(lastSeen).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    } else {
                        sub.innerText = 'অফলাইন';
                    }
                }
            };

            if (recipientId) {
                fetch(`/api/v1/presence/${recipientId}`, { headers: getAuthHeaders() })
                    .then(r => r.json())
                    .then(res => {
                        if (res.data) updateActiveChatHeaderPresence(!!res.data.online, res.data.last_seen);
                    }).catch(() => {});
            }

            fetch(`/api/v1/conversations/${convId}/read`, {
                method: 'POST',
                headers: getAuthHeaders()
            }).catch(() => {});

            await loadMessages(convId);

            setTimeout(() => {
                const input = document.getElementById('messengerInputText');
                if (input) input.focus();
            }, 100);

            // Gentle Fallback Polling (every 25 seconds) — live chat is driven by realtime WebSocket events
            clearInterval(activeChatPollingTimer);
            activeChatPollingTimer = setInterval(() => {
                if (activeChatConversationId === convId && (box.classList.contains('active') || box.style.display === 'flex') && !box.classList.contains('hidden') && box.style.display !== 'none') {
                    // Do not poll or disrupt if audio/video is currently playing
                    const chatCont = document.getElementById('messengerChatMessages');
                    if (chatCont) {
                        const playingMedia = Array.from(chatCont.querySelectorAll('audio, video')).some(el => !el.paused && !el.ended && el.readyState > 0);
                        if (playingMedia) return;
                    }
                    loadMessagesSilent(convId);
                } else {
                    clearInterval(activeChatPollingTimer);
                }
            }, 25000);
        }

        function closeRealChat(event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            const box = document.getElementById('messengerChatBox');
            if (box) {
                box.style.setProperty('display', 'none', 'important');
                box.style.display = 'none';
                box.classList.remove('active', 'open');
                box.classList.add('hidden');
            }
            activeChatConversationId = null;
            if (activeChatPollingTimer) {
                clearInterval(activeChatPollingTimer);
                activeChatPollingTimer = null;
            }
            if (typeof cancelVoiceRecording === 'function') {
                cancelVoiceRecording();
            }
        }

        function minimizeRealChat(event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            const box = document.getElementById('messengerChatBox');
            if (box) {
                box.classList.toggle('minimized');
            }
        }

        function toggleChatWidth(event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            const box = document.getElementById('messengerChatBox');
            if (!box) return;
            const isExpanded = box.classList.toggle('expanded');
            try {
                localStorage.setItem('jj_chat_expanded', isExpanded ? '1' : '0');
            } catch (e) {}
            const btn = document.getElementById('expandChatWidthBtn');
            if (btn) {
                btn.title = isExpanded ? 'স্বাভাবিক প্রস্থ করুন' : 'প্রসারিত করুন';
                btn.innerHTML = isExpanded
                    ? '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>'
                    : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>';
            }
        }

        function toggleChatOptionsMenu(event) {
            if (event) event.stopPropagation();
            const dd = document.getElementById('chatOptionsMenuDropdown');
            if (dd) dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
        }

        function confirmClearChatHistory() {
            const dd = document.getElementById('chatOptionsMenuDropdown');
            if (dd) dd.style.display = 'none';
            const modal = document.getElementById('clearChatConfirmModal');
            if (modal) modal.style.display = 'flex';
        }

        function closeClearChatModal() {
            const modal = document.getElementById('clearChatConfirmModal');
            if (modal) modal.style.display = 'none';
        }

        async function executeClearChatHistory() {
            if (!activeChatConversationId) return;
            closeClearChatModal();
            try {
                const res = await fetch(`/api/v1/conversations/${activeChatConversationId}/clear`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (data.success) {
                    showToast('চ্যাট হিস্ট্রি সফলভাবে ক্লিয়ার করা হয়েছে।');
                    const container = document.getElementById('messengerChatMessages');
                    if (container) {
                        container.innerHTML = `
                            <div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px; padding: 24px;">
                                <div style="font-size: 28px; margin-bottom: 6px;">👋</div>
                                <div style="font-weight: 700;">কথোপকথন শুরু করুন!</div>
                                <div style="font-size: 11px; margin-top: 2px;">চ্যাট হিস্ট্রি মুছে ফেলা হয়েছে</div>
                            </div>`;
                    }
                } else {
                    showToast(data.message || 'চ্যাট হিস্ট্রি ক্লিয়ার করা যায়নি।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        function toggleMsgActionsDropdown(msgId, event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            const currentDd = document.getElementById(`msgDropdown_${msgId}`);
            const isCurrentlyOpen = currentDd && currentDd.style.display === 'block';

            document.querySelectorAll('[id^="msgDropdown_"]').forEach(el => {
                el.style.display = 'none';
            });

            if (!currentDd || isCurrentlyOpen) return;

            currentDd.style.display = 'block';

            // Smart boundary clamp to ensure dropdown options are never cropped or clipped
            const container = document.getElementById('messengerChatMessages') || currentDd.closest('.jj-chat-messages');
            if (container) {
                const contRect = container.getBoundingClientRect();
                const ddRect = currentDd.getBoundingClientRect();

                // Prevent left clipping (ensure at least 6px clearance)
                if (ddRect.left < contRect.left + 6) {
                    currentDd.style.left = '0';
                    currentDd.style.right = 'auto';
                }
                // Prevent right clipping (ensure at least 6px clearance)
                if (ddRect.right > contRect.right - 6) {
                    currentDd.style.right = '0';
                    currentDd.style.left = 'auto';
                }
                // Prevent top clipping: flip downwards if close to top header
                if (ddRect.top < contRect.top + 10) {
                    currentDd.style.bottom = 'auto';
                    currentDd.style.top = 'calc(100% + 4px)';
                } else {
                    currentDd.style.bottom = '100%';
                    currentDd.style.top = 'auto';
                }
            }
        }

        window.openChatMessageEditModal = function(msgId, currentText, currentVersion) {
            document.querySelectorAll('[id^="msgDropdown_"]').forEach(el => el.style.display = 'none');
            const modal = document.getElementById('chatEditMessageModal');
            if (!modal) return;

            document.getElementById('chatEditMessageId').value = msgId;
            document.getElementById('chatEditMessageVersion').value = currentVersion || 1;
            document.getElementById('chatEditOriginalText').value = currentText || '';

            const preview = document.getElementById('chatEditOriginalPreview');
            if (preview) preview.textContent = currentText || '(কোনো টেক্সট নেই)';

            const textarea = document.getElementById('chatEditTextarea');
            if (textarea) {
                textarea.value = currentText || '';
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(220, Math.max(95, textarea.scrollHeight)) + 'px';
            }

            updateChatEditCounter();
            modal.style.display = 'flex';

            setTimeout(() => {
                if (textarea) {
                    textarea.focus();
                    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
                }
            }, 60);
        };

        window.closeChatMessageEditModal = function() {
            const modal = document.getElementById('chatEditMessageModal');
            if (modal) modal.style.display = 'none';
        };

        window.handleChatEditTextareaInput = function(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(220, Math.max(95, textarea.scrollHeight)) + 'px';
            updateChatEditCounter();
        };

        window.handleChatEditTextareaKeydown = function(event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeChatMessageEditModal();
                return;
            }
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                submitChatMessageEdit();
            }
        };

        window.insertChatEditEmoji = function(emoji) {
            const textarea = document.getElementById('chatEditTextarea');
            if (!textarea) return;
            const start = textarea.selectionStart || textarea.value.length;
            const end = textarea.selectionEnd || textarea.value.length;
            textarea.value = textarea.value.substring(0, start) + emoji + textarea.value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
            textarea.focus();
            updateChatEditCounter();
        };

        function updateChatEditCounter() {
            const textarea = document.getElementById('chatEditTextarea');
            const counter = document.getElementById('chatEditCharCounter');
            const submitBtn = document.getElementById('chatEditSubmitBtn');
            const original = document.getElementById('chatEditOriginalText')?.value || '';

            if (!textarea || !counter) return;
            const len = textarea.value.length;
            counter.textContent = `${len}/2000`;

            const trimmed = textarea.value.trim();
            const isChanged = trimmed !== original.trim();
            const isValid = trimmed.length > 0 && len <= 2000;

            if (submitBtn) {
                if (!isValid || !isChanged) {
                    submitBtn.style.opacity = '0.55';
                    submitBtn.style.cursor = 'not-allowed';
                } else {
                    submitBtn.style.opacity = '1';
                    submitBtn.style.cursor = 'pointer';
                }
            }
        }

        window.submitChatMessageEdit = async function() {
            const msgId = document.getElementById('chatEditMessageId')?.value;
            const version = document.getElementById('chatEditMessageVersion')?.value || 1;
            const original = document.getElementById('chatEditOriginalText')?.value || '';
            const textarea = document.getElementById('chatEditTextarea');
            const newText = textarea ? textarea.value.trim() : '';

            if (!msgId || !newText) return;
            if (newText === original.trim()) {
                closeChatMessageEditModal();
                return;
            }

            const submitBtn = document.getElementById('chatEditSubmitBtn');
            const submitBtnText = document.getElementById('chatEditSubmitBtnText');
            const originalBtnContent = submitBtn ? submitBtn.innerHTML : '';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.7';
                if (submitBtnText) submitBtnText.textContent = 'সংরক্ষণ হচ্ছে...';
            }

            try {
                const headers = typeof getAuthHeaders === 'function' 
                    ? getAuthHeaders({ 'Content-Type': 'application/json' }) 
                    : {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    };

                const res = await fetch(`/api/v1/messages/${msgId}`, {
                    method: 'PATCH',
                    headers: headers,
                    body: JSON.stringify({ body: newText, expected_version: Number(version) })
                });
                const data = await res.json();

                if (res.status === 409) {
                    showToast('⚠️ অন্য কোনো ডিভাইস থেকে বার্তাটি ইতিমধ্যে পরিবর্তিত হয়েছে। চ্যাট রিফ্রেশ হচ্ছে...');
                    closeChatMessageEditModal();
                    if (activeChatConversationId) loadMessagesSilent(activeChatConversationId);
                    return;
                }

                if (data.success) {
                    showToast('মেসেজ সফলভাবে সম্পাদিত হয়েছে');
                    closeChatMessageEditModal();
                    if (activeChatConversationId) loadMessagesSilent(activeChatConversationId);
                } else {
                    showToast(data.message || 'মেসেজ এডিট করা যায়নি।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = originalBtnContent;
                }
            }
        };

        // Backward compatibility alias for any existing callers
        window.promptEditChatMessage = function(msgId, currentText, currentVersion) {
            openChatMessageEditModal(msgId, currentText, currentVersion);
        };

        async function promptDeleteChatMessage(msgId, forEveryone) {
            document.querySelectorAll('[id^="msgDropdown_"]').forEach(el => el.style.display = 'none');
            const confirmMsg = forEveryone 
                ? 'আপনি কি এই বার্তাটি সবার জন্য মুছে ফেলতে চান? (Delete for everyone)' 
                : 'আপনি কি এই বার্তাটি শুধুমাত্র আপনার চ্যাট থেকে মুছে ফেলতে চান? (Delete for me)';
            if (!confirm(confirmMsg)) return;

            try {
                const url = forEveryone ? `/api/v1/messages/${msgId}/delete-for-everyone` : `/api/v1/messages/${msgId}/delete-for-me`;
                const res = await fetch(url, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (data.success) {
                    showToast(forEveryone ? 'সবার জন্য বার্তাটি মুছে ফেলা হয়েছে' : 'বার্তাটি আপনার জন্য মুছে ফেলা হয়েছে');
                    if (activeChatConversationId) loadMessagesSilent(activeChatConversationId);
                } else {
                    showToast(data.message || 'বার্তাটি মুছে ফেলা যায়নি।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        document.addEventListener('click', (e) => {
            const dd = document.getElementById('chatOptionsMenuDropdown');
            if (dd && !dd.contains(e.target) && e.target.id !== 'chatMoreMenuBtn') dd.style.display = 'none';
            document.querySelectorAll('[id^="msgDropdown_"]').forEach(el => {
                if (!el.contains(e.target) && !e.target.closest('button[onclick*="toggleMsgActionsDropdown"]')) {
                    el.style.display = 'none';
                }
            });
            const editModal = document.getElementById('chatEditMessageModal');
            if (editModal && editModal.style.display === 'flex' && e.target === editModal) {
                closeChatMessageEditModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeChatMessageEditModal();
                document.querySelectorAll('[id^="msgDropdown_"]').forEach(el => el.style.display = 'none');
            }
        });

        async function loadMessages(convId) {
            const container = document.getElementById('messengerChatMessages');
            container.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px; padding: 16px;">মেসেজ লোড হচ্ছে...</div>';
            await loadMessagesSilent(convId);
        }

        function renderProfileChatMessageHtml(m) {
            if (m.type === 'call') {
                return `
                    <div id="chatMsg_${m.id}" data-id="${m.id}" data-version="${m.version || 1}" style="text-align: center; margin: 8px auto; width: 100%;">
                        <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(24,119,242,0.1); color: var(--fb-primary, #1877f2); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                            ${escapeChatHtml(m.body || 'কল')}
                        </div>
                    </div>
                `;
            }

            const isMe = m.is_mine ?? (m.sender_id === {{ auth()->id() ?? 'null' }});
            const isDeleted = !!(m.deleted_at || m.is_deleted_for_everyone);
            const isEdited = !!(m.is_edited || m.edited_at);
            const time = m.created_at ? new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
            const senderAvatar = m.sender?.avatar_url || '/images/default-avatar.svg';

            let voiceHtml = '';
            if (!isDeleted) {
                const rawAudioUrl = m.metadata?.audio_url || m.media?.find(med => med.collection === 'voice' || med.mime_type?.includes('audio'))?.url;
                const audioTokenParam = authToken ? `?token=${encodeURIComponent(authToken)}` : '';
                const audioUrl = m.id ? `/api/v1/messages/${m.id}/voice${audioTokenParam}` : rawAudioUrl;
                if (m.type === 'voice' || rawAudioUrl) {
                    voiceHtml = `
                        <div style="margin: 4px 0;">
                            <audio controls src="${audioUrl}" style="max-width: 210px; height: 32px;" preload="metadata" onerror="if(!this.dataset.retry && ${m.id ? 'true' : 'false'}){this.dataset.retry='1';this.src='/messages/${m.id}/voice${audioTokenParam}';}"></audio>
                            <div style="font-size: 10px; opacity: 0.8; margin-top: 2px;">🎙️ ভয়েস বার্তা (${Math.round(m.metadata?.duration || 0)}s)</div>
                        </div>
                    `;
                }
            }

            let mediaAttachmentHtml = '';
            let hasImageMedia = false;
            const mediaList = m.media || m.attachments || [];
            if (!isDeleted && mediaList.length > 0) {
                mediaAttachmentHtml = mediaList.map(media => {
                    const mime = media.mime_type || media.type || '';
                    const isImg = mime.startsWith('image/') || /\.(png|jpe?g|webp|gif|avif)$/i.test(media.url || media.name || '');
                    const isVid = mime.startsWith('video/') || /\.(mp4|webm|mov)$/i.test(media.url || media.name || '');

                    const displayUrl = media.preview_url || media.url || media.urls?.preview || media.urls?.medium || media.urls?.original || (media.id ? `/api/v1/messages/attachments/${media.id}/view` : '');
                    const fullUrl = media.original_url || media.urls?.original || displayUrl;
                    const downloadUrl = media.download_url || media.urls?.download || (media.id ? `/api/v1/messages/attachments/${media.id}/download` : fullUrl);
                    const fileName = media.name || media.filename || 'ছবি';

                    if (isImg) {
                        hasImageMedia = true;
                        return `
                            <div class="jj-chat-image-wrap" style="position: relative; margin-top: 4px; border-radius: 12px; overflow: hidden; background: rgba(0,0,0,0.04); max-width: 260px;">
                                <img src="${displayUrl}" 
                                     data-original="${fullUrl}" 
                                     alt="${escapeChatHtml(fileName)}" 
                                     loading="lazy"
                                     style="display: block; width: 100%; max-height: 280px; object-fit: cover; border-radius: 12px; cursor: pointer; transition: opacity 0.2s ease;" 
                                     onmouseover="this.style.opacity='0.92'"
                                     onmouseout="this.style.opacity='1'"
                                     onclick="openImageLightbox('${fullUrl}')"
                                     onerror="handleChatImageError(this, ${media.id || 'null'}, '${displayUrl}')">
                                <div class="jj-chat-img-actions" style="position: absolute; top: 6px; right: 6px; display: flex; gap: 4px; background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); border-radius: 20px; padding: 2px 6px;">
                                    <button type="button" onclick="event.stopPropagation(); openImageLightbox('${fullUrl}')" title="পূর্ণ পর্দায় দেখুন" style="background: none; border: none; color: white; cursor: pointer; padding: 3px; display: flex; align-items: center;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                                    </button>
                                    <a href="${downloadUrl}" download onclick="event.stopPropagation()" title="ডাউনলোড" style="color: white; padding: 3px; display: flex; align-items: center;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                                    </a>
                                </div>
                            </div>
                        `;
                    }

                    if (isVid) {
                        return `
                            <div style="margin-top: 4px; border-radius: 12px; overflow: hidden; max-width: 260px;">
                                <video controls src="${fullUrl}" style="width: 100%; border-radius: 12px; max-height: 240px; display: block;"></video>
                            </div>
                        `;
                    }

                    return `
                        <a href="${downloadUrl}" download target="_blank" style="display: flex; align-items: center; gap: 8px; color: inherit; text-decoration: none; font-size: 12px; margin-top: 4px; padding: 6px 10px; background: rgba(0,0,0,0.05); border-radius: 8px; max-width: 240px;">
                            <span style="font-size: 18px;">📎</span>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 600; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">${escapeChatHtml(fileName)}</div>
                                <div style="font-size: 10px; opacity: 0.7;">${media.size ? Math.round(media.size / 1024) + ' KB' : 'ডাউনলোড'}</div>
                            </div>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        </a>
                    `;
                }).join('');
            }

            let bodyText = (m.body || '').trim();
            let isFilenameText = false;
            if (bodyText && mediaList.length > 0) {
                isFilenameText = mediaList.some(med => {
                    const fn = (med.name || med.filename || '').trim();
                    const baseFn = (med.original_path ? med.original_path.split('/').pop() : '').trim();
                    return bodyText === fn || bodyText === baseFn || (fn && bodyText.endsWith(fn));
                }) || /\.(png|jpe?g|webp|gif|avif|pdf|docx?|zip|mp4)$/i.test(bodyText);
            }

            const showBody = bodyText && !isFilenameText && m.type !== 'voice';
            const isBubbleImageOnly = hasImageMedia && !showBody && !voiceHtml;

            let actionsHtml = '';
            if (!isDeleted) {
                actionsHtml = `
                    <div class="chat-msg-actions-hover" style="display: flex; gap: 2px; opacity: 0; transition: opacity 0.15s ease; align-self: center;">
                        ${isMe && m.type !== 'voice' ? `
                            <button type="button" onclick="openChatMessageEditModal(${m.id}, '${escapeChatHtml(bodyText).replace(/'/g, "\\'")}', ${m.version || 1})" title="এডিট করুন" style="background: none; border: none; padding: 2px 4px; cursor: pointer; color: var(--fb-text-secondary); border-radius: 4px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            </button>
                        ` : ''}
                        <div style="position: relative;">
                            <button type="button" onclick="toggleMsgActionsDropdown(${m.id}, event)" title="মুছুন বা অন্যান্য অপশন" style="background: none; border: none; padding: 2px 4px; cursor: pointer; color: var(--fb-text-secondary); border-radius: 4px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1.5"></circle><circle cx="12" cy="5" r="1.5"></circle><circle cx="12" cy="19" r="1.5"></circle></svg>
                            </button>
                            <div id="msgDropdown_${m.id}" class="jj-chat-dropdown-menu" style="display: none; ${isMe ? 'left: 0; right: auto;' : 'right: 0; left: auto;'}">
                                ${isMe && m.type !== 'voice' ? `
                                    <button type="button" class="jj-chat-dropdown-item" onclick="openChatMessageEditModal(${m.id}, '${escapeChatHtml(bodyText).replace(/'/g, "\\'")}', ${m.version || 1})">
                                        <span style="font-size: 13px;">✏️</span>
                                        <span>মেসেজ এডিট করুন</span>
                                    </button>
                                ` : ''}
                                ${isMe ? `
                                    <button type="button" class="jj-chat-dropdown-item danger" onclick="promptDeleteChatMessage(${m.id}, true)">
                                        <span style="font-size: 13px;">🗑️</span>
                                        <span>সবার জন্য মুছুন</span>
                                    </button>
                                ` : ''}
                                <button type="button" class="jj-chat-dropdown-item" onclick="promptDeleteChatMessage(${m.id}, false)">
                                    <span style="font-size: 13px;">🗑️</span>
                                    <span>আমার জন্য মুছুন</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }

            return `
                <div id="chatMsg_${m.id}" data-id="${m.id}" data-version="${m.version || 1}" data-deleted="${isDeleted ? '1' : '0'}" onmouseenter="const a=this.querySelector('.chat-msg-actions-hover'); if(a) a.style.opacity='1';" onmouseleave="const a=this.querySelector('.chat-msg-actions-hover'); if(a) a.style.opacity='0';" style="display: flex; justify-content: ${isMe ? 'flex-end' : 'flex-start'}; align-items: flex-end; gap: 6px; margin-bottom: 6px;">
                    ${!isMe ? `<img src="${senderAvatar}" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">` : ''}
                    ${isMe ? actionsHtml : ''}
                    <div style="max-width: 78%; display: flex; flex-direction: column; align-items: ${isMe ? 'flex-end' : 'flex-start'};">
                        <div class="${isMe ? 'jj-chat-bubble-sent' : 'jj-chat-bubble-received'}" style="${isBubbleImageOnly ? 'padding: 2px !important; background: transparent !important; border: none !important; box-shadow: none !important;' : ''}">
                            ${isDeleted ? `
                                <div style="font-style: italic; opacity: 0.75; color: var(--fb-text-secondary); display: flex; align-items: center; gap: 4px;">
                                    <span>🚫</span><span>এই বার্তাটি মুছে ফেলা হয়েছে</span>
                                </div>
                            ` : `
                                ${showBody ? `<div>${escapeChatHtml(bodyText)}</div>` : ''}
                                ${voiceHtml}
                                ${mediaAttachmentHtml}
                            `}
                        </div>
                        <div class="chat-meta" style="font-size: 10px; color: var(--fb-text-secondary); margin-top: 2px; display: flex; align-items: center; gap: 3px;">
                            <span>${time}</span>
                            ${isEdited && !isDeleted ? '<span class="chat-edited-badge" style="font-size: 10px; opacity: 0.7; margin-left: 2px;">(সম্পাদিত)</span>' : ''}
                            ${isMe && !isDeleted ? `<span style="color: ${m.delivery_status === 'read' ? '#0084ff' : 'var(--fb-text-secondary)'};">${m.delivery_status === 'read' ? '✓✓' : '✓'}</span>` : ''}
                        </div>
                    </div>
                    ${!isMe ? actionsHtml : ''}
                </div>
            `;
        }

        async function loadMessagesSilent(convId) {
            const container = document.getElementById('messengerChatMessages');
            if (!container) return;
            try {
                const res = await fetch(`/api/v1/conversations/${convId}/messages`, {
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                const messages = Array.isArray(data.data) ? data.data : (data.data?.messages || []);

                if (messages.length === 0) {
                    if (!container.querySelector('.jj-empty-chat-state')) {
                        container.innerHTML = `
                            <div class="jj-empty-chat-state" style="text-align: center; color: var(--fb-text-secondary); font-size: 13px; padding: 24px;">
                                <div style="font-size: 28px; margin-bottom: 6px;">👋</div>
                                <div style="font-weight: 700;">কথোপকথন শুরু করুন!</div>
                                <div style="font-size: 11px; margin-top: 2px;">আপনার প্রথম মেসেজ পাঠান</div>
                            </div>`;
                    }
                    return;
                }

                // Remove empty state placeholder if present
                const emptyEl = container.querySelector('.jj-empty-chat-state');
                if (emptyEl) emptyEl.remove();

                const atBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;

                // Smart Reconciliation: Check currently rendered messages to prevent DOM wipes
                const renderedEls = Array.from(container.querySelectorAll('[id^="chatMsg_"]'));
                const renderedMap = new Map();
                renderedEls.forEach(el => {
                    const id = el.getAttribute('data-id') || el.id.replace('chatMsg_', '');
                    renderedMap.set(String(id), el);
                });

                const newIds = messages.map(m => String(m.id));
                const renderedIds = Array.from(renderedMap.keys());

                // Check exact match (same order, same version, same deleted status)
                const isExactMatch = renderedIds.length === newIds.length &&
                    renderedIds.every((id, idx) => id === newIds[idx]) &&
                    messages.every(m => {
                        const el = renderedMap.get(String(m.id));
                        if (!el) return false;
                        const v = el.getAttribute('data-version') || '1';
                        const d = el.getAttribute('data-deleted') || '0';
                        const isDel = (m.deleted_at || m.is_deleted_for_everyone) ? '1' : '0';
                        const ver = String(m.version || 1);
                        return v === ver && d === isDel;
                    });

                // If identical, DO NOT touch the DOM! Prevents any audio reload/interruption!
                if (isExactMatch) {
                    return;
                }

                // If container is fresh (e.g. initial load):
                if (renderedMap.size === 0) {
                    container.innerHTML = messages.map(m => renderProfileChatMessageHtml(m)).join('');
                    if (atBottom) container.scrollTop = container.scrollHeight;
                    return;
                }

                // Incremental DOM update:
                // 1. Remove messages no longer visible (cleared or deleted-for-me)
                renderedMap.forEach((el, id) => {
                    if (!newIds.includes(id)) {
                        el.remove();
                    }
                });

                // 2. Insert new messages or update modified ones in order without touching intact ones
                let lastInsertedNode = null;
                messages.forEach(m => {
                    const id = String(m.id);
                    const existingNode = renderedMap.get(id);
                    const isDel = (m.deleted_at || m.is_deleted_for_everyone) ? '1' : '0';
                    const ver = String(m.version || 1);

                    if (existingNode) {
                        const prevVer = existingNode.getAttribute('data-version') || '1';
                        const prevDel = existingNode.getAttribute('data-deleted') || '0';

                        // Only replace this specific row if it was edited or deleted
                        if (prevVer !== ver || prevDel !== isDel) {
                            const temp = document.createElement('div');
                            temp.innerHTML = renderProfileChatMessageHtml(m).trim();
                            const newNode = temp.firstElementChild;
                            if (newNode) {
                                container.replaceChild(newNode, existingNode);
                                renderedMap.set(id, newNode);
                                lastInsertedNode = newNode;
                                return;
                            }
                        }
                        lastInsertedNode = existingNode;
                    } else {
                        // New message arriving: append/insert without touching playing audio or existing nodes
                        const temp = document.createElement('div');
                        temp.innerHTML = renderProfileChatMessageHtml(m).trim();
                        const newNode = temp.firstElementChild;
                        if (newNode) {
                            if (lastInsertedNode && lastInsertedNode.nextSibling) {
                                container.insertBefore(newNode, lastInsertedNode.nextSibling);
                            } else {
                                container.appendChild(newNode);
                            }
                            renderedMap.set(id, newNode);
                            lastInsertedNode = newNode;
                        }
                    }
                });

                if (atBottom) {
                    container.scrollTop = container.scrollHeight;
                }
            } catch (err) {
                console.warn('Load messages silent error:', err);
            }
        }

        async function sendChatMessage() {
            if (!activeChatConversationId) return;
            const input = document.getElementById('messengerInputText');
            const body = input.value.trim();
            if (!body) return;

            input.value = '';
            input.focus();

            try {
                const res = await fetch(`/api/v1/conversations/${activeChatConversationId}/messages`, {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ body })
                });
                const data = await res.json();
                if (data.success) {
                    if (data.data?.id && window.bondhooSoundManager && typeof window.bondhooSoundManager.playOutgoingMessageSentTone === 'function') {
                        window.bondhooSoundManager.playOutgoingMessageSentTone(data.data.id);
                    }
                    await loadMessagesSilent(activeChatConversationId);
                    const container = document.getElementById('messengerChatMessages');
                    if (container) container.scrollTop = container.scrollHeight;
                } else {
                    showToast(data.message || 'মেসেজ পাঠানো যায়নি।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        async function toggleVoiceRecording() {
            if (!activeChatConversationId) {
                showToast('ভয়েস পাঠাতে প্রথমে একটি চ্যাট ওপেন করুন।');
                return;
            }
            if (mediaRecorderInstance && mediaRecorderInstance.state === 'recording') {
                stopVoiceRecordingAndSend();
                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                voiceAudioChunks = [];
                voiceRecordSeconds = 0;

                let selectedOptions = undefined;
                if (typeof MediaRecorder !== 'undefined' && typeof MediaRecorder.isTypeSupported === 'function') {
                    const candidates = [
                        'audio/webm;codecs=opus',
                        'audio/webm',
                        'audio/mp4',
                        'audio/aac',
                        'audio/ogg;codecs=opus',
                        'audio/ogg'
                    ];
                    for (const c of candidates) {
                        if (MediaRecorder.isTypeSupported(c)) {
                            selectedOptions = { mimeType: c };
                            break;
                        }
                    }
                }

                try {
                    mediaRecorderInstance = selectedOptions ? new MediaRecorder(stream, selectedOptions) : new MediaRecorder(stream);
                } catch (e) {
                    mediaRecorderInstance = new MediaRecorder(stream);
                }

                mediaRecorderInstance.ondataavailable = e => {
                    if (e.data && e.data.size > 0) voiceAudioChunks.push(e.data);
                };

                mediaRecorderInstance.start(250);

                const bar = document.getElementById('voiceRecordingBar');
                const timerEl = document.getElementById('voiceRecordTimer');
                if (bar) bar.style.display = 'flex';
                if (timerEl) timerEl.innerText = '00:00';

                clearInterval(voiceRecordTimerInterval);
                voiceRecordTimerInterval = setInterval(() => {
                    voiceRecordSeconds++;
                    const m = Math.floor(voiceRecordSeconds / 60);
                    const s = voiceRecordSeconds % 60;
                    if (timerEl) timerEl.innerText = `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
                }, 1000);

            } catch (err) {
                showToast('মাইক্রোফোন অনুমতি প্রয়োজন: ' + err.message);
            }
        }

        function cancelVoiceRecording() {
            if (mediaRecorderInstance) {
                try {
                    mediaRecorderInstance.stream.getTracks().forEach(t => t.stop());
                    mediaRecorderInstance.stop();
                } catch (e) {}
                mediaRecorderInstance = null;
            }
            clearInterval(voiceRecordTimerInterval);
            voiceAudioChunks = [];
            const bar = document.getElementById('voiceRecordingBar');
            if (bar) bar.style.display = 'none';
        }

        async function stopVoiceRecordingAndSend() {
            if (!mediaRecorderInstance || mediaRecorderInstance.state !== 'recording') return;
            const duration = Math.max(1, voiceRecordSeconds);

            clearInterval(voiceRecordTimerInterval);
            const bar = document.getElementById('voiceRecordingBar');
            if (bar) bar.style.display = 'none';

            mediaRecorderInstance.onstop = async () => {
                mediaRecorderInstance.stream.getTracks().forEach(t => t.stop());
                const recordedType = mediaRecorderInstance.mimeType || (voiceAudioChunks[0]?.type) || 'audio/webm';
                let ext = 'webm';
                if (recordedType.includes('mp4') || recordedType.includes('aac')) ext = 'm4a';
                else if (recordedType.includes('ogg')) ext = 'ogg';
                else if (recordedType.includes('wav')) ext = 'wav';

                const blob = new Blob(voiceAudioChunks, { type: recordedType });
                const file = new File([blob], `voice_${Date.now()}.webm`, { type: recordedType });

                const formData = new FormData();
                formData.append('audio', file);
                formData.append('duration', duration);

                showToast('🎙️ ভয়েস বার্তা আপলোড হচ্ছে...');

                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                    const headers = { 'Accept': 'application/json' };
                    if (authToken) headers['Authorization'] = `Bearer ${authToken}`;
                    if (csrf) headers['X-CSRF-TOKEN'] = csrf;

                    const res = await fetch(`/api/v1/conversations/${activeChatConversationId}/voice`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: headers,
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (data.data?.id && window.bondhooSoundManager && typeof window.bondhooSoundManager.playOutgoingMessageSentTone === 'function') {
                            window.bondhooSoundManager.playOutgoingMessageSentTone(data.data.id);
                        }
                        showToast('ভয়েস বার্তা পাঠানো হয়েছে! 🚀');
                        await loadMessagesSilent(activeChatConversationId);
                        const container = document.getElementById('messengerChatMessages');
                        if (container) container.scrollTop = container.scrollHeight;
                    } else {
                        showToast(data.message || 'ভয়েস বার্তা পাঠানো যায়নি।');
                    }
                } catch (err) {
                    showToast('ভয়েস আপলোড এরর: ' + err.message);
                }
            };

            mediaRecorderInstance.stop();
        }

        async function handleChatFileUpload(event) {
            const file = event.target.files?.[0];
            if (!file || !activeChatConversationId) return;

            const isImg = file.type.startsWith('image/');
            const container = document.getElementById('messengerChatMessages');
            const tempId = 'optimistic_upload_' + Date.now();
            let localPreviewUrl = '';

            if (isImg) {
                try {
                    localPreviewUrl = URL.createObjectURL(file);
                } catch (e) {}
            }

            // Render optimistic bubble
            if (container) {
                const optimisticHtml = `
                    <div id="${tempId}" style="display: flex; justify-content: flex-end; align-items: flex-end; gap: 6px; margin-bottom: 6px;">
                        <div style="max-width: 78%; display: flex; flex-direction: column; align-items: flex-end;">
                            <div style="position: relative; border-radius: 14px; overflow: hidden; background: #e2e8f0; max-width: 240px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                ${isImg && localPreviewUrl ? `<img src="${localPreviewUrl}" style="width: 100%; max-height: 240px; object-fit: cover; opacity: 0.7; filter: blur(1px); display: block;">` : `<div style="padding: 16px; font-size: 13px;">📎 ${escapeChatHtml(file.name)}</div>`}
                                <div id="${tempId}_status" style="position: absolute; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(2px); display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; gap: 6px; font-size: 12px; font-weight: 700;">
                                    <div style="width: 22px; height: 22px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: jjSpin 0.8s linear infinite;"></div>
                                    <span>আপলোড হচ্ছে...</span>
                                </div>
                            </div>
                            <div style="font-size: 10px; color: var(--fb-text-secondary); margin-top: 2px;">পাঠানো হচ্ছে...</div>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', optimisticHtml);
                container.scrollTop = container.scrollHeight;
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('collection', 'message');

            showToast('অ্যাটাচমেন্ট আপলোড হচ্ছে... 📎');

            try {
                const uploadRes = await fetch('/api/v1/messages/attachments', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${authToken}`, 'Accept': 'application/json' },
                    body: formData
                });
                const uploadData = await uploadRes.json();
                if (uploadData.success && uploadData.data?.id) {
                    const mediaId = uploadData.data.id;
                    const inputEl = document.getElementById('messengerInputText');
                    const caption = inputEl ? inputEl.value.trim() : '';
                    if (inputEl) inputEl.value = '';

                    // Send message linked to media with caption (or empty string, NEVER raw filename!)
                    const msgRes = await fetch(`/api/v1/conversations/${activeChatConversationId}/messages`, {
                        method: 'POST',
                        headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                        body: JSON.stringify({
                            body: caption,
                            media_ids: [mediaId],
                            type: 'media'
                        })
                    });
                    const msgData = await msgRes.json();
                    if (msgData.success) {
                        if (msgData.data?.id && window.bondhooSoundManager && typeof window.bondhooSoundManager.playOutgoingMessageSentTone === 'function') {
                            window.bondhooSoundManager.playOutgoingMessageSentTone(msgData.data.id);
                        }
                        showToast('ফাইল পাঠানো সম্পন্ন! 🚀');
                        document.getElementById(tempId)?.remove();
                        if (localPreviewUrl) URL.revokeObjectURL(localPreviewUrl);
                        await loadMessagesSilent(activeChatConversationId);
                        if (container) container.scrollTop = container.scrollHeight;
                    } else {
                        showUploadError(tempId, file, msgData.message || 'মেসেজ পাঠানো ব্যর্থ হয়েছে।');
                    }
                } else {
                    showUploadError(tempId, file, uploadData.message || 'ফাইল আপলোড ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                showUploadError(tempId, file, 'ফাইল এরর: ' + err.message);
            } finally {
                event.target.value = '';
            }
        }

        function showUploadError(tempId, file, errMsg) {
            showToast(errMsg);
            const statusEl = document.getElementById(`${tempId}_status`);
            if (statusEl) {
                statusEl.style.background = 'rgba(239, 68, 68, 0.88)';
                statusEl.innerHTML = `
                    <span style="font-size: 16px;">⚠️</span>
                    <span style="font-size: 11px;">ব্যর্থ হয়েছে</span>
                    <button type="button" onclick="retryChatFileUpload(this, '${tempId}')" style="background: white; color: #dc2626; border: none; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; cursor: pointer; margin-top: 4px;">পুনরায় চেষ্টা</button>
                `;
                statusEl._retryFile = file;
            }
        }

        async function retryChatFileUpload(btn, tempId) {
            const statusEl = document.getElementById(`${tempId}_status`);
            const file = statusEl?._retryFile;
            if (!file) return;
            document.getElementById(tempId)?.remove();
            const fakeEvent = { target: { files: [file], value: '' } };
            await handleChatFileUpload(fakeEvent);
        }

        function handleChatImageError(imgEl, mediaId, fallbackUrl) {
            if (!imgEl) return;
            if (mediaId && !imgEl.dataset.retried) {
                imgEl.dataset.retried = '1';
                imgEl.src = `/api/v1/messages/attachments/${mediaId}/view?t=${Date.now()}`;
                return;
            }
            imgEl.style.display = 'none';
            const box = document.createElement('div');
            box.style.cssText = 'padding: 10px 12px; background: rgba(239,68,68,0.08); border: 1px dashed rgba(239,68,68,0.4); border-radius: 10px; font-size: 11px; color: #dc2626; display: flex; align-items: center; justify-content: space-between; gap: 8px;';
            box.innerHTML = `<span>⚠️ ছবি লোড করা যায়নি</span> <button type="button" onclick="retryChatImageLoad(this, '${fallbackUrl}', ${mediaId})" style="background: white; border: 1px solid #dc2626; color: #dc2626; border-radius: 4px; padding: 2px 6px; font-size: 10px; font-weight: 600; cursor: pointer;">রিফ্রেশ</button>`;
            imgEl.parentNode.appendChild(box);
        }

        function retryChatImageLoad(btn, fallbackUrl, mediaId) {
            const wrap = btn.closest('.jj-chat-image-wrap');
            if (!wrap) return;
            const img = wrap.querySelector('img');
            if (img) {
                img.style.display = 'block';
                img.src = `${fallbackUrl.split('?')[0]}?refresh=${Date.now()}`;
                btn.closest('div').remove();
            }
        }

        /* ------------------------------------------------------------- */
        /* FULLSCREEN IMAGE LIGHTBOX VIEWER */
        /* ------------------------------------------------------------- */
        let currentLightboxScale = 1.0;
        let currentLightboxImages = [];
        let currentLightboxIndex = 0;

        function openImageLightbox(url, allImages = [], index = 0) {
            const modal = document.getElementById('imageLightboxModal');
            const img = document.getElementById('lightboxImage');
            const downloadBtn = document.getElementById('lightboxDownloadBtn');
            const counterEl = document.getElementById('lightboxCounter');
            const prevBtn = document.getElementById('lightboxPrevBtn');
            const nextBtn = document.getElementById('lightboxNextBtn');

            if (!modal || !img) return;

            currentLightboxScale = 1.0;
            img.style.transform = `scale(${currentLightboxScale})`;

            if (Array.isArray(allImages) && allImages.length > 0) {
                currentLightboxImages = allImages;
                currentLightboxIndex = Math.max(0, Math.min(index, allImages.length - 1));
                const activeItem = currentLightboxImages[currentLightboxIndex];
                const activeUrl = typeof activeItem === 'string' ? activeItem : (activeItem.original_url || activeItem.url);
                img.src = activeUrl;
                if (downloadBtn) downloadBtn.href = activeUrl;
                if (counterEl) counterEl.innerText = `ছবি ${currentLightboxIndex + 1} / ${currentLightboxImages.length}`;
                if (prevBtn) prevBtn.style.display = currentLightboxImages.length > 1 ? 'flex' : 'none';
                if (nextBtn) nextBtn.style.display = currentLightboxImages.length > 1 ? 'flex' : 'none';
            } else {
                currentLightboxImages = [url];
                currentLightboxIndex = 0;
                img.src = url;
                if (downloadBtn) downloadBtn.href = url;
                if (counterEl) counterEl.innerText = 'ছবি প্রিভিউ';
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
            }

            modal.style.display = 'flex';
        }

        function zoomLightboxImage(delta) {
            const img = document.getElementById('lightboxImage');
            if (!img) return;
            currentLightboxScale = Math.max(0.5, Math.min(3.5, currentLightboxScale + delta));
            img.style.transform = `scale(${currentLightboxScale})`;
        }

        function resetLightboxZoom() {
            const img = document.getElementById('lightboxImage');
            if (!img) return;
            currentLightboxScale = 1.0;
            img.style.transform = 'scale(1)';
        }

        function navigateLightbox(dir) {
            if (!currentLightboxImages || currentLightboxImages.length <= 1) return;
            currentLightboxIndex = (currentLightboxIndex + dir + currentLightboxImages.length) % currentLightboxImages.length;
            const item = currentLightboxImages[currentLightboxIndex];
            const url = typeof item === 'string' ? item : (item.original_url || item.url);
            const img = document.getElementById('lightboxImage');
            const downloadBtn = document.getElementById('lightboxDownloadBtn');
            const counterEl = document.getElementById('lightboxCounter');

            if (img) {
                img.src = url;
                resetLightboxZoom();
            }
            if (downloadBtn) downloadBtn.href = url;
            if (counterEl) counterEl.innerText = `ছবি ${currentLightboxIndex + 1} / ${currentLightboxImages.length}`;
        }

        function closeLightboxModal() {
            const modal = document.getElementById('imageLightboxModal');
            if (modal) modal.style.display = 'none';
            resetLightboxZoom();
        }

        window.addEventListener('keydown', (e) => {
            const modal = document.getElementById('imageLightboxModal');
            if (!modal || modal.style.display === 'none') return;
            if (e.key === 'Escape') closeLightboxModal();
            else if (e.key === 'ArrowLeft') navigateLightbox(-1);
            else if (e.key === 'ArrowRight') navigateLightbox(1);
        });

        function toggleChatEmojiPicker() {
            const picker = document.getElementById('chatEmojiPicker');
            if (picker) {
                picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
            }
        }

        function insertChatEmoji(emoji) {
            const input = document.getElementById('messengerInputText');
            if (input) {
                input.value += emoji;
                input.focus();
            }
            const picker = document.getElementById('chatEmojiPicker');
            if (picker) picker.style.display = 'none';
        }

        let profileTypingTimer = null;
        function handleTypingBroadcast() {
            if (!activeChatConversationId) return;
            clearTimeout(profileTypingTimer);
            profileTypingTimer = setTimeout(() => {
                fetch('/api/v1/presence/typing', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ conversation_id: activeChatConversationId })
                }).catch(() => {});
            }, 600);
        }

        function startChatCall(type) {
            if (!activeChatConversationId) {
                showToast('কোনো সক্রিয় চ্যাট নেই');
                return;
            }
            showToast(`${type === 'audio' ? 'অডিও' : 'ভিডিও'} কল সংযুক্ত হচ্ছে... 📞`);
            const callUrl = `/call/${activeChatConversationId}?type=${type}`;
            closeRealChat();
            const win = window.open(callUrl, '_blank');
            if (!win || win.closed || typeof win.closed === 'undefined') {
                window.location.href = callUrl;
            }
        }

        /* ------------------------------------------------------------- */
        /* MULTI-FRIEND CALLING WORKSPACE */
        /* ------------------------------------------------------------- */
        let multiCallTargetConvId = null;
        let multiCallSelectedFriendIds = new Set();
        let cachedMultiCallFriends = [];

        async function openMultiFriendCallModal(convId = null, chatTitle = '', initialFriendId = null) {
            multiCallTargetConvId = convId || activeChatConversationId;
            multiCallSelectedFriendIds.clear();

            const activePartnerId = initialFriendId || activeChatUser?.id;
            if (activePartnerId) {
                multiCallSelectedFriendIds.add(Number(activePartnerId));
            }

            const modal = document.getElementById('multiFriendCallModal');
            if (!modal) return;
            modal.style.display = 'flex';

            const searchInput = document.getElementById('multiCallSearchInput');
            if (searchInput) searchInput.value = '';

            updateMultiCallSelectedCounter();
            await loadMultiCallFriends();
        }

        function closeMultiFriendCallModal() {
            const modal = document.getElementById('multiFriendCallModal');
            if (modal) modal.style.display = 'none';
        }

        async function loadMultiCallFriends(query = '') {
            const listEl = document.getElementById('multiCallFriendsList');
            if (!listEl) return;

            if (cachedMultiCallFriends.length === 0 && !query) {
                listEl.innerHTML = '<div style="text-align: center; color: #64748b; font-size: 13px; padding: 24px;">বন্ধুদের তালিকা লোড হচ্ছে...</div>';
            }

            try {
                const qParam = query ? `?q=${encodeURIComponent(query)}` : '';
                const res = await fetch(`/api/v1/presence/friends/active${qParam}`, {
                    headers: getAuthHeaders({ 'Accept': 'application/json' })
                });
                const data = await res.json();
                if (data.success && Array.isArray(data.data)) {
                    if (!query) cachedMultiCallFriends = data.data;
                    renderMultiCallFriendsList(data.data);
                } else {
                    listEl.innerHTML = '<div style="text-align: center; color: #64748b; font-size: 13px; padding: 24px;">কোনো বন্ধু পাওয়া যায়নি</div>';
                }
            } catch (e) {
                listEl.innerHTML = '<div style="text-align: center; color: #dc2626; font-size: 13px; padding: 24px;">তালিকা লোড করতে ব্যর্থ হয়েছে</div>';
            }
        }

        function filterMultiCallFriends(query) {
            query = query.trim().toLowerCase();
            if (!query) {
                renderMultiCallFriendsList(cachedMultiCallFriends);
                return;
            }
            const filtered = cachedMultiCallFriends.filter(f =>
                (f.name && f.name.toLowerCase().includes(query)) ||
                (f.username && f.username.toLowerCase().includes(query))
            );
            if (filtered.length > 0) {
                renderMultiCallFriendsList(filtered);
            } else {
                loadMultiCallFriends(query);
            }
        }

        function renderMultiCallFriendsList(friends) {
            const listEl = document.getElementById('multiCallFriendsList');
            if (!listEl) return;

            if (!friends || friends.length === 0) {
                listEl.innerHTML = '<div style="text-align: center; color: #64748b; font-size: 13px; padding: 24px;">কোনো বন্ধু পাওয়া যায়নি</div>';
                return;
            }

            let html = '';
            friends.forEach(f => {
                const isSelected = multiCallSelectedFriendIds.has(Number(f.id));
                const avatar = f.avatar_url;
                const initial = (f.name || 'U').charAt(0).toUpperCase();

                html += `
                    <div onclick="toggleMultiCallFriend(${f.id})" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-radius: 10px; cursor: pointer; transition: background 0.15s ease; ${isSelected ? 'background: rgba(24, 119, 242, 0.08);' : ''}" onmouseover="if(!multiCallSelectedFriendIds.has(${f.id}))this.style.background='#f8fafc'" onmouseout="if(!multiCallSelectedFriendIds.has(${f.id}))this.style.background='transparent'">
                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                            <div style="position: relative; width: 38px; height: 38px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #1e293b; flex-shrink: 0; overflow: hidden;">
                                ${avatar ? `<img src="${avatar}" alt="" style="width: 100%; height: 100%; object-fit: cover;">` : initial}
                                ${f.online ? `<span style="position: absolute; bottom: 1px; right: 1px; width: 9px; height: 9px; border-radius: 50%; background: #10b981; border: 2px solid white;"></span>` : ''}
                            </div>
                            <div style="min-width: 0;">
                                <div style="font-weight: 600; font-size: 13px; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(f.name)}</div>
                                <div style="font-size: 11px; color: #64748b;">${f.online ? '<span style="color: #10b981; font-weight: 600;">সক্রিয়</span>' : (f.last_seen_human || 'অফলাইন')}</div>
                            </div>
                        </div>
                        <div>
                            <input type="checkbox" id="multiCheck-${f.id}" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleMultiCallFriend(${f.id});" style="width: 17px; height: 17px; cursor: pointer; accent-color: #1877f2;">
                        </div>
                    </div>
                `;
            });

            listEl.innerHTML = html;
        }

        function toggleMultiCallFriend(friendId) {
            friendId = Number(friendId);
            if (multiCallSelectedFriendIds.has(friendId)) {
                multiCallSelectedFriendIds.delete(friendId);
            } else {
                multiCallSelectedFriendIds.add(friendId);
            }
            updateMultiCallSelectedCounter();
            renderMultiCallFriendsList(cachedMultiCallFriends);
        }

        function updateMultiCallSelectedCounter() {
            const countEl = document.getElementById('multiCallSelectedCount');
            if (countEl) {
                const count = multiCallSelectedFriendIds.size;
                countEl.innerText = `${count} জন নির্বাচিত`;
                countEl.style.color = count > 0 ? '#1877f2' : '#64748b';
            }
        }

        async function startMultiFriendCall(type) {
            if (multiCallSelectedFriendIds.size === 0) {
                alert('অনুগ্রহ করে কমপক্ষে একজন বন্ধু নির্বাচন করুন।');
                return;
            }

            const receiverIds = Array.from(multiCallSelectedFriendIds);
            showToast(`${type === 'video' ? 'গ্রুপ ভিডিও' : 'গ্রুপ অডিও'} কল প্রস্তুত হচ্ছে... 📞`);
            closeMultiFriendCallModal();
            closeRealChat();

            try {
                const res = await fetch('/api/v1/calls', {
                    method: 'POST',
                    headers: getAuthHeaders({
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }),
                    body: JSON.stringify({
                        conversation_id: multiCallTargetConvId || null,
                        receiver_ids: receiverIds,
                        call_type: type === 'video' ? 'group_video' : 'group_audio'
                    })
                });
                const data = await res.json();
                if (res.ok && data.success && data.data) {
                    const convId = data.data.conversation_id;
                    const callUrl = `/call/${convId}?type=${type}&call_id=${data.data.id}`;
                    window.location.href = callUrl;
                } else if (multiCallTargetConvId) {
                    window.location.href = `/call/${multiCallTargetConvId}?type=${type}`;
                } else {
                    alert(data.message || 'কল সংযোগ স্থাপন করা সম্ভব হয়নি।');
                }
            } catch (e) {
                if (multiCallTargetConvId) {
                    window.location.href = `/call/${multiCallTargetConvId}?type=${type}`;
                } else {
                    alert('নেটওয়ার্ক সমস্যার কারণে কল শুরু করা যায়নি।');
                }
            }
        }

        // Action Bar More Menu Dropdowns (Owner & Visitor/Friend)
        function toggleProfileActionMoreMenu(event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            closeVisitorActionMoreMenu();
            closeMoreTabsDropdown();
            const dd = document.getElementById('profileActionMoreDropdown');
            if (dd) dd.classList.toggle('show');
        }

        function closeProfileActionMoreMenu() {
            const dd = document.getElementById('profileActionMoreDropdown');
            if (dd) dd.classList.remove('show');
        }

        function toggleVisitorActionMoreMenu(event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            closeProfileActionMoreMenu();
            closeMoreTabsDropdown();
            const dd = document.getElementById('visitorActionMoreDropdown');
            if (dd) dd.classList.toggle('show');
        }

        function closeVisitorActionMoreMenu() {
            const dd = document.getElementById('visitorActionMoreDropdown');
            if (dd) dd.classList.remove('show');
        }

        function closeAllProfileDropdowns() {
            closeProfileActionMoreMenu();
            closeVisitorActionMoreMenu();
            closeMoreTabsDropdown();
            if (typeof closeAvatarMenu === 'function') closeAvatarMenu();
            if (typeof closeCoverMenu === 'function') closeCoverMenu();
        }

        function openManageSectionsModal() {
            closeAllProfileDropdowns();
            openModal('manageSectionsModal');
        }

        function scrollToProfilePostsSearch() {
            closeVisitorActionMoreMenu();
            switchTab('posts');
            const filterBar = document.getElementById('timelineFilterBar');
            if (filterBar && (filterBar.style.display === 'none' || !filterBar.style.display)) {
                if (typeof toggleTimelineFilterBar === 'function') {
                    toggleTimelineFilterBar();
                } else {
                    filterBar.style.display = 'block';
                }
            }
            const target = document.querySelector('.timeline-manage-bar') || document.getElementById('tabContent-posts');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Close dropdowns on outside click
        document.addEventListener('click', function(e) {
            const profileDd = document.getElementById('profileActionMoreDropdown');
            if (profileDd && !e.target.closest('#profileActionMoreWrapper')) {
                profileDd.classList.remove('show');
            }
            const visitorDd = document.getElementById('visitorActionMoreDropdown');
            if (visitorDd && !e.target.closest('#visitorActionMoreWrapper')) {
                visitorDd.classList.remove('show');
            }
            const moreTabsDd = document.getElementById('profileMoreTabsDropdown');
            if (moreTabsDd && !e.target.closest('.profile-nav-more-item')) {
                moreTabsDd.classList.remove('show');
            }
            const coverDd = document.getElementById('coverMenuDropdown');
            if (coverDd && !e.target.closest('#coverActionsWrapper')) {
                coverDd.classList.remove('show');
            }
            const avatarDd = document.getElementById('avatarMenuDropdown');
            if (avatarDd && !e.target.closest('#avatarActionsWrapper') && !e.target.closest('.profile-avatar-wrapper')) {
                avatarDd.classList.remove('show');
            }
        });

        // Close dropdowns on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllProfileDropdowns();
            }
        });

        // Facebook-Style Inline Bio Handlers
        function toggleInlineBio() {
            const editor = document.getElementById('inlineBioEditor');
            const btn = document.getElementById('openInlineBioBtn');
            const display = document.getElementById('introBioDisplay');
            if (editor) {
                const isHidden = editor.style.display === 'none' || editor.style.display === '';
                editor.style.display = isHidden ? 'block' : 'none';
                if (btn) btn.style.display = isHidden ? 'none' : 'block';
                if (display && isHidden) display.style.display = 'none';
                if (isHidden) {
                    const input = document.getElementById('inlineBioInput');
                    if (input) {
                        input.focus();
                        updateBioCharCount(input.value.length);
                    }
                }
            }
        }

        function cancelInlineBio() {
            const editor = document.getElementById('inlineBioEditor');
            const btn = document.getElementById('openInlineBioBtn');
            const display = document.getElementById('introBioDisplay');
            if (editor) editor.style.display = 'none';
            if (btn) btn.style.display = 'block';
            if (display) display.style.display = 'block';
        }

        function updateBioCharCount(len) {
            const el = document.getElementById('inlineBioCharCount');
            if (el) el.innerText = `${255 - len} অক্ষর বাকি`;
        }

        async function saveInlineBio() {
            const input = document.getElementById('inlineBioInput');
            const btn = document.getElementById('saveInlineBioBtn');
            const newBio = input ? input.value.trim() : '';

            if (btn) {
                btn.disabled = true;
                btn.innerText = 'সংরক্ষণ হচ্ছে...';
            }

            try {
                const res = await fetch('/api/v2/profile/about', {
                    method: 'PUT',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ bio: newBio })
                });

                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    const display = document.getElementById('introBioDisplay');
                    if (display) {
                        display.innerText = newBio || 'আপনার সম্পর্কে সংক্ষেপে কিছু লিখুন...';
                        display.style.fontStyle = newBio ? 'normal' : 'italic';
                        display.style.color = newBio ? 'var(--fb-text-primary)' : 'var(--fb-text-secondary)';
                        display.style.display = 'block';
                    }
                    const openBtn = document.getElementById('openInlineBioBtn');
                    if (openBtn) {
                        openBtn.innerText = newBio ? '✏️ বায়ো সম্পাদনা' : '➕ বায়ো যোগ করুন';
                        openBtn.style.display = 'block';
                    }
                    const editor = document.getElementById('inlineBioEditor');
                    if (editor) editor.style.display = 'none';
                    alert('বায়ো সফলভাবে সংরক্ষিত হয়েছে!');
                } else {
                    alert(data.message || 'বায়ো সংরক্ষণ করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগ ত্রুটি।');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'সংরক্ষণ';
                }
            }
        }

        // Tab Switcher & More Dropdown
        function toggleMoreTabsDropdown(event) {
            if (event) event.stopPropagation();
            const dropdown = document.getElementById('profileMoreTabsDropdown');
            if (dropdown) dropdown.classList.toggle('show');
        }

        function closeMoreTabsDropdown() {
            const dropdown = document.getElementById('profileMoreTabsDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }

        function selectMoreTab(tabName, label, event) {
            if (event) event.preventDefault();
            switchTab(tabName);
            const moreBtn = document.getElementById('tab-more-btn');
            const moreBtnText = document.getElementById('tab-more-btn-text');
            if (moreBtnText) moreBtnText.innerText = label;
            if (moreBtn) moreBtn.classList.add('active');
            closeMoreTabsDropdown();
        }

        function switchTab(tabName) {
            closeAllProfileDropdowns();
            if (tabName === 'reels') {
                const tabs = ['posts', 'about', 'friends', 'photos', 'videos', 'saved', 'activity', 'professional', 'analytics'];
                tabs.forEach(t => {
                    const el = document.getElementById(`tabContent-${t}`);
                    const btn = document.getElementById(`tab-${t}`);
                    if (el) el.style.display = (t === 'videos') ? 'block' : 'none';
                    if (btn) btn.classList.remove('active');
                });
                const reelsNavBtn = document.getElementById('tab-reels');
                if (reelsNavBtn) {
                    reelsNavBtn.classList.add('active');
                    try { reelsNavBtn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' }); } catch(e) {}
                }
                document.body.classList.add('tab-not-posts');
                const reelsBtn = document.querySelector(".filter-chips-row button[onclick*='reels']");
                if (typeof switchVideoSubtab === 'function') {
                    switchVideoSubtab('reels', reelsBtn);
                }
                return;
            }

            const tabs = ['posts', 'about', 'friends', 'photos', 'videos', 'saved', 'activity', 'professional', 'analytics'];
            const topTabs = ['posts', 'about', 'friends', 'photos', 'videos'];

            tabs.forEach(t => {
                const el = document.getElementById(`tabContent-${t}`);
                const btn = document.getElementById(`tab-${t}`);
                if (el) el.style.display = (t === tabName) ? 'block' : 'none';
                if (btn) {
                    if (t === tabName) {
                        btn.classList.add('active');
                        try {
                            btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                        } catch(e) {}
                    } else {
                        btn.classList.remove('active');
                    }
                }
            });

            // On mobile devices (<= 900px), hide profile-left-col when viewing tabs other than 'posts'
            // so users immediately see About, Friends, Photos, etc. directly without endless scrolling
            if (tabName === 'posts') {
                document.body.classList.remove('tab-not-posts');
            } else {
                document.body.classList.add('tab-not-posts');
            }

            // Update More dropdown state
            const moreBtn = document.getElementById('tab-more-btn');
            const moreBtnText = document.getElementById('tab-more-btn-text');
            document.querySelectorAll('.more-tab-link').forEach(link => link.classList.remove('active'));

            if (topTabs.includes(tabName)) {
                if (moreBtnText) moreBtnText.innerText = 'আরও';
                if (moreBtn) moreBtn.classList.remove('active');
            } else {
                const activeMoreLink = document.getElementById(`moreLink-${tabName}`);
                if (activeMoreLink) activeMoreLink.classList.add('active');
                if (moreBtn) moreBtn.classList.add('active');
            }
        }

        // Section Visibility Management
        const SECTION_CARD_MAP = {
            'intro': 'introBioCard',
            'featured': 'featuredStoriesCard',
            'photos': 'sidebarPhotosCard',
            'friends': 'sidebarFriendsCard',
            'completion': 'completionCard'
        };

        function toggleSectionVisibility(sectionKey, isVisible) {
            const cardId = SECTION_CARD_MAP[sectionKey];
            if (cardId) {
                const card = document.getElementById(cardId);
                if (card) {
                    card.style.display = isVisible ? '' : 'none';
                }
            }

            try {
                const raw = localStorage.getItem('jugajug_sections_pref');
                const prefs = raw ? JSON.parse(raw) : {};
                prefs[sectionKey] = isVisible;
                localStorage.setItem('jugajug_sections_pref', JSON.stringify(prefs));
            } catch (e) {
                console.error(e);
            }
        }

        function loadSectionPreferences() {
            try {
                const raw = localStorage.getItem('jugajug_sections_pref');
                if (!raw) return;
                const prefs = JSON.parse(raw);
                Object.keys(prefs).forEach(key => {
                    const isVisible = prefs[key];
                    const toggleInput = document.getElementById(`sectionToggle-${key}`);
                    if (toggleInput) toggleInput.checked = isVisible;
                    const cardId = SECTION_CARD_MAP[key];
                    if (cardId) {
                        const card = document.getElementById(cardId);
                        if (card) card.style.display = isVisible ? '' : 'none';
                    }
                });
            } catch (e) {
                console.error(e);
            }
        }

        // Subtab Switchers
        function switchAboutSubtab(subtabKey, btn) {
            document.querySelectorAll('.about-subtab-btn').forEach(b => b.classList.remove('active'));
            if (btn) {
                btn.classList.add('active');
            } else {
                const targetBtn = document.querySelector(`.about-subtab-btn[onclick*="'${subtabKey}'"]`);
                if (targetBtn) targetBtn.classList.add('active');
            }
            const subtabs = ['overview', 'work_edu', 'places', 'contact_basic', 'family', 'hobbies', 'skills'];
            subtabs.forEach(key => {
                const panel = document.getElementById(`about-panel-${key}`);
                if (panel) {
                    panel.style.display = (key === subtabKey) ? 'block' : 'none';
                }
            });
        }

        function switchPhotoSubtab(sub, btn) {
            document.querySelectorAll('#tabContent-photos .filter-chip').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            const pEl = document.getElementById('subtabContent-photos');
            const aEl = document.getElementById('subtabContent-albums');
            if (pEl) pEl.style.display = (sub === 'photos') ? 'block' : 'none';
            if (aEl) aEl.style.display = (sub === 'albums') ? 'block' : 'none';
        }

        function switchVideoSubtab(sub, btn) {
            document.querySelectorAll('#tabContent-videos .filter-chip').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            const vEl = document.getElementById('subtabContent-videos');
            const rEl = document.getElementById('subtabContent-reels');
            if (vEl) vEl.style.display = (sub === 'videos') ? 'block' : 'none';
            if (rEl) rEl.style.display = (sub === 'reels') ? 'block' : 'none';
        }

        // Modal Helpers
        function openEditProfileModal() {
            closeAllProfileDropdowns();
            document.getElementById('editProfileModal').classList.add('active');
        }

        function openEditProfileWithTab(tabKey) {
            openEditProfileModal();
            const btn = document.querySelector(`.modal-subtab[onclick*="${tabKey}"]`);
            if (btn) switchEditTab(tabKey, btn);
        }

        function openAvatarModal() {
            const dd = document.getElementById('avatarMenuDropdown');
            if (dd) dd.classList.remove('show');
            if (typeof resetAvatarSelection === 'function') resetAvatarSelection();
            if (typeof switchAvatarTab === 'function') switchAvatarTab('upload');
            const el = document.getElementById('avatarModal');
            if (el) el.classList.add('active');
        }

        function closeAvatarModal() {
            if (typeof avatarEditorState !== 'undefined' && avatarEditorState.uploading) {
                if (typeof showToast === 'function') showToast('আপলোড চলছে, অনুগ্রহ করে অপেক্ষা করুন...');
                return;
            }
            closeModal('avatarModal');
            if (typeof resetAvatarSelection === 'function') resetAvatarSelection();
        }

        function openCoverModal() {
            const dd = document.getElementById('coverMenuDropdown');
            if (dd) dd.classList.remove('show');
            resetCoverSelection();
            switchCoverTab('upload');
            document.getElementById('coverModal').classList.add('active');
        }

        function openAvatarFrameModal() {
            document.getElementById('avatarFrameModal').classList.add('active');
        }

        function openRepositionCoverModal() {
            document.getElementById('coverRepositionModal').classList.add('active');
        }

        function openAvatarHistoryModal() {
            document.getElementById('avatarHistoryModal').classList.add('active');
        }

        function openCoverHistoryModal() {
            document.getElementById('coverHistoryModal').classList.add('active');
        }

        function openCreateAlbumModal() {
            document.getElementById('createAlbumModal').classList.add('active');
        }

        function openCreateHighlightModal() {
            document.getElementById('createHighlightModal').classList.add('active');
        }

        function openPostModal(type = 'text') {
            const modal = document.getElementById('postModal');
            if (modal) {
                modal.classList.add('active');
                if (type === 'photo' || type === 'video') {
                    const input = document.getElementById('postModalFileInput');
                    if (input) {
                        input.accept = type === 'video' ? 'video/*' : 'image/*';
                        setTimeout(() => input.click(), 150);
                    }
                } else if (type === 'feeling') {
                    const txt = document.getElementById('postModalTextarea');
                    if (txt) {
                        txt.value = '😊 অনুভূতি: চমৎকার লাগছে ';
                        txt.focus();
                    }
                }
            }
        }

        function previewPostModalFile(input) {
            const file = input.files && input.files[0];
            const previewBox = document.getElementById('postModalMediaPreview');
            const imgPreview = document.getElementById('postModalImgPreview');
            const vidPreview = document.getElementById('postModalVidPreview');
            if (!file || !previewBox) return;

            previewBox.style.display = 'block';
            if (file.type.startsWith('video/')) {
                if (imgPreview) imgPreview.style.display = 'none';
                if (vidPreview) {
                    vidPreview.src = URL.createObjectURL(file);
                    vidPreview.style.display = 'block';
                }
            } else {
                if (vidPreview) vidPreview.style.display = 'none';
                if (imgPreview) {
                    imgPreview.src = URL.createObjectURL(file);
                    imgPreview.style.display = 'block';
                }
            }
        }

        function clearPostModalMedia() {
            const input = document.getElementById('postModalFileInput');
            if (input) input.value = '';
            const previewBox = document.getElementById('postModalMediaPreview');
            if (previewBox) previewBox.style.display = 'none';
            const imgPreview = document.getElementById('postModalImgPreview');
            if (imgPreview) imgPreview.src = '';
            const vidPreview = document.getElementById('postModalVidPreview');
            if (vidPreview) vidPreview.src = '';
        }

        async function submitPostModalForm(e) {
            e.preventDefault();
            const btn = document.getElementById('submitPostModalBtn');
            const content = document.getElementById('postModalTextarea')?.value || '';
            const privacy = document.getElementById('postModalPrivacy')?.value || 'public';
            const fileInput = document.getElementById('postModalFileInput');
            const file = fileInput?.files?.[0];

            if (!content.trim() && !file) {
                showToast('অনুগ্রহ করে কিছু লিখুন বা ফাইল সংযুক্ত করুন');
                return;
            }

            btn.disabled = true;
            btn.innerText = 'পোস্ট হচ্ছে...';

            const formData = new FormData();
            formData.append('content', content);
            formData.append('privacy', privacy);
            if (file) {
                formData.append('media', file);
            }

            try {
                const res = await fetch('/api/v1/posts', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        ...(authToken ? { 'Authorization': `Bearer ${authToken}` } : {})
                    },
                    body: formData
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success' || data.data)) {
                    showToast('পোস্ট সফলভাবে তৈরি হয়েছে!');
                    closeModal('postModal');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast(data.message || 'পোস্ট প্রকাশ করা যায়নি');
                    btn.disabled = false;
                    btn.innerText = 'পোস্ট প্রকাশ করুন';
                }
            } catch (err) {
                console.error(err);
                showToast('সার্ভার যোগাযোগে সমস্যা হয়েছে');
                btn.disabled = false;
                btn.innerText = 'পোস্ট প্রকাশ করুন';
            }
        }

        function openModal(id) {
            closeAllProfileDropdowns();
            const el = document.getElementById(id);
            if (el) el.classList.add('active');
        }

        function openVerificationModal() {
            closeAllProfileDropdowns();
            const el = document.getElementById('verificationModal');
            if (el) el.classList.add('active');
        }

        function openPrivacyModal() {
            closeAllProfileDropdowns();
            const el = document.getElementById('privacyModal');
            if (el) el.classList.add('active');
        }

        function openSecurityModal() {
            closeAllProfileDropdowns();
            const el = document.getElementById('securityModal');
            if (el) {
                el.classList.add('active');
                if (typeof loadSecurityDevices === 'function') loadSecurityDevices();
            }
        }

        function openBlockingCenterModal() {
            closeAllProfileDropdowns();
            const el = document.getElementById('blockingCenterModal');
            if (el) {
                el.classList.add('active');
                if (typeof loadBlockedUsers === 'function') loadBlockedUsers();
            }
        }

        function openStoryModal() {
            closeAllProfileDropdowns();
            const el = document.getElementById('storyModal');
            if (el) el.classList.add('active');
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.remove('active');
            const hash = window.location.hash.replace('#', '');
            const modalHashMap = {
                'privacyModal': 'privacy',
                'securityModal': 'security',
                'blockingCenterModal': 'blocking',
                'profileLockModal': 'lock',
                'verificationModal': 'verify',
                'notificationSettingsModal': 'notifications',
                'profileShareModal': 'share',
                'storyModal': 'story',
                'manageSectionsModal': 'sections'
            };
            if (modalHashMap[id] === hash) {
                history.replaceState(null, document.title, window.location.pathname + window.location.search);
            }
        }

        // Avatar Frame API
        async function selectAvatarFrame(frameId) {
            try {
                const res = await fetch('/api/v1/profile/avatar/frame', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ frame_id: frameId || null })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('প্রোফাইল ফ্রেম সফলভাবে আপডেট হয়েছে!');
                    closeModal('avatarFrameModal');
                    window.location.reload();
                } else {
                    alert(data.message || 'ফ্রেম পরিবর্তন ব্যর্থ হয়েছে।');
                }
            } catch (e) {
                console.error(e);
                alert('সার্ভার যোগাযোগ ত্রুটি।');
            }
        }

        // Cover Photo Menu & Live Repositioning
        let isRepositioningCover = false;
        let coverInitialPosY = {{ $user->profile->cover_position_y ?? 50 }};
        let coverCurrentPosY = {{ $user->profile->cover_position_y ?? 50 }};

        function toggleCoverMenu(e) {
            if (e) e.stopPropagation();
            const dd = document.getElementById('coverMenuDropdown');
            if (dd) dd.classList.toggle('show');
        }

        function closeCoverMenu() {
            const dd = document.getElementById('coverMenuDropdown');
            if (dd) dd.classList.remove('show');
        }

        function handleCoverClick(e) {
            if (isRepositioningCover) return;
            const dd = document.getElementById('coverMenuDropdown');
            if (dd) dd.classList.remove('show');
            const img = document.getElementById('coverPhotoImg');
            if (img && img.getAttribute('src')) {
                openPhotoTheater(img.getAttribute('src'), @js($profile['name'] ?? ''), 'কভার ফটো', 'সম্প্রতি');
            }
        }

        function startLiveCoverReposition() {
            const dd = document.getElementById('coverMenuDropdown');
            if (dd) dd.classList.remove('show');

            const banner = document.getElementById('coverRepositionBanner');
            const actionsWrapper = document.getElementById('coverActionsWrapper');
            const img = document.getElementById('coverPhotoImg');
            const container = document.getElementById('coverContainer');

            if (!img || !container) return;

            isRepositioningCover = true;
            if (banner) banner.classList.add('active');
            if (actionsWrapper) actionsWrapper.style.display = 'none';

            img.classList.add('is-dragging');

            let isDragging = false;
            let startY = 0;
            let startPos = coverCurrentPosY;

            function onMouseDown(e) {
                if (!isRepositioningCover) return;
                isDragging = true;
                startY = e.clientY || (e.touches && e.touches[0].clientY);
                startPos = coverCurrentPosY;
                e.preventDefault();
            }

            function onMouseMove(e) {
                if (!isDragging || !isRepositioningCover) return;
                const clientY = e.clientY || (e.touches && e.touches[0].clientY);
                const delta = clientY - startY;
                const containerHeight = container.offsetHeight || 350;
                let newPos = startPos - (delta / containerHeight) * 100;
                newPos = Math.max(0, Math.min(100, Math.round(newPos)));
                coverCurrentPosY = newPos;
                img.style.objectPosition = `center ${newPos}%`;
            }

            function onMouseUp() {
                isDragging = false;
            }

            container.onmousedown = onMouseDown;
            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);

            container.ontouchstart = onMouseDown;
            window.addEventListener('touchmove', onMouseMove, { passive: false });
            window.addEventListener('touchend', onMouseUp);
        }

        function cancelCoverReposition() {
            isRepositioningCover = false;
            const banner = document.getElementById('coverRepositionBanner');
            const actionsWrapper = document.getElementById('coverActionsWrapper');
            const img = document.getElementById('coverPhotoImg');

            if (banner) banner.classList.remove('active');
            if (actionsWrapper) actionsWrapper.style.display = '';
            if (img) {
                img.classList.remove('is-dragging');
                coverCurrentPosY = coverInitialPosY;
                img.style.objectPosition = `center ${coverInitialPosY}%`;
            }
        }

        async function saveCoverRepositionLive() {
            const btn = document.getElementById('saveCoverRepositionLiveBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'সংরক্ষণ হচ্ছে...';
            }

            try {
                const res = await fetch('/api/v1/profile/cover/reposition', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ position_y: parseInt(coverCurrentPosY) })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    coverInitialPosY = coverCurrentPosY;
                    showToast("✓ কভার ছবির অবস্থান সংরক্ষিত হয়েছে"); if (btn) { btn.disabled = false; btn.innerText = "✓ সংরক্ষণ করুন"; }
                    cancelCoverReposition();
                } else {
                    showToast(data.message || 'সংরক্ষণ ব্যর্থ হয়েছে।');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerText = '✓ সংরক্ষণ করুন';
                    }
                }
            } catch (err) {
                console.error(err);
                showToast('সার্ভার যোগাযোগ সমস্যা।');
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = '✓ সংরক্ষণ করুন';
                }
            }
        }

        // Close cover menu on outside click
        document.addEventListener('click', function(e) {
            const dd = document.getElementById('coverMenuDropdown');
            if (dd && !e.target.closest('#coverActionsWrapper')) {
                dd.classList.remove('show');
            }
        });

        // Cover Reposition API (Modal Fallback)
        function updateCoverPositionLive(val) {
            const lbl = document.getElementById('coverPosValueLabel');
            if (lbl) lbl.innerText = val + '%';
            const img = document.getElementById('coverPhotoImg');
            if (img) img.style.objectPosition = `center ${val}%`;
        }

        async function saveCoverReposition() {
            const val = document.getElementById('coverPositionSlider')?.value || 50;
            try {
                const res = await fetch('/api/v1/profile/cover/reposition', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ position_y: parseInt(val) })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('কভার ছবির পজিশন সফলভাবে সংরক্ষিত হয়েছে!');
                    closeModal('coverRepositionModal');
                } else {
                    alert(data.message || 'সংরক্ষণ ব্যর্থ হয়েছে।');
                }
            } catch (e) {
                console.error(e);
                alert('যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        // Restore Avatar
        async function restoreAvatar(photoId) {
            if (!confirm('আপনি কি এই পূর্ববর্তী ছবিটি বর্তমান প্রোফাইল ছবি হিসেবে ব্যবহার করতে চান?')) return;
            try {
                const res = await fetch('/api/v1/profile/avatar/restore', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ photo_id: photoId })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('ছবি সফলভাবে পুনরুদ্ধার করা হয়েছে!');
                    closeModal('avatarHistoryModal');
                    window.location.reload();
                } else {
                    alert(data.message || 'পুনরুদ্ধার করা যায়নি।');
                }
            } catch (e) {
                console.error(e);
            }
        }

        // Create Album
        async function submitCreateAlbum(e) {
            e.preventDefault();
            const title = document.getElementById('albumTitleInput')?.value;
            const desc = document.getElementById('albumDescInput')?.value;
            const cover = document.getElementById('albumCoverInput')?.value;
            const privacy = document.getElementById('albumPrivacySelect')?.value || 'public';

            try {
                const res = await fetch('/api/v1/profile/albums', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({
                        title: title,
                        description: desc,
                        cover_photo_path: cover,
                        privacy: privacy
                    })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('ফটো অ্যালবাম সফলভাবে তৈরি হয়েছে!');
                    closeModal('createAlbumModal');
                    window.location.reload();
                } else {
                    alert(data.message || 'অ্যালবাম তৈরি ব্যর্থ হয়েছে।');
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function deleteAlbum(albumId) {
            if (!confirm('আপনি কি নিশ্চিত যে এই অ্যালবামটি ডিলিট করতে চান?')) return;
            try {
                const res = await fetch(`/api/v1/profile/albums/${albumId}`, {
                    method: 'DELETE',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('অ্যালবাম ডিলিট করা হয়েছে।');
                    window.location.reload();
                } else {
                    alert(data.message || 'অ্যালবাম ডিলিট করা যায়নি।');
                }
            } catch (e) {
                console.error(e);
            }
        }

        // Story Highlights & Featured Collection
        function selectHighlightCover(url, el) {
            const input = document.getElementById('highlightCoverInput');
            if (input) input.value = url;
            document.querySelectorAll('.selectable-cover-thumb').forEach(t => t.classList.remove('active'));
            if (el) el.classList.add('active');
        }

        async function deleteHighlightItem(id, event) {
            if (event) event.stopPropagation();
            if (!confirm('আপনি কি নিশ্চিত এই ফিচারড কালেকশনটি মুছে ফেলতে চান?')) return;
            try {
                const res = await fetch(`/api/v1/profile/highlights/${id}`, {
                    method: 'DELETE',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('ফিচারড কালেকশন সফলভাবে মুছে ফেলা হয়েছে');
                    window.location.reload();
                } else {
                    alert(data.message || 'মুছে ফেলতে ব্যর্থ হয়েছে');
                }
            } catch (e) {
                console.error(e);
                alert('সার্ভার এরর');
            }
        }

        async function submitCreateHighlight(e) {
            e.preventDefault();
            const title = document.getElementById('highlightTitleInput')?.value;
            const cover = document.getElementById('highlightCoverInput')?.value;

            try {
                const res = await fetch('/api/v1/profile/highlights', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({
                        title: title,
                        cover_image_path: cover
                    })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('ফিচারড কালেকশন সফলভাবে তৈরি হয়েছে!');
                    closeModal('createHighlightModal');
                    window.location.reload();
                } else {
                    alert(data.message || 'কালেকশন তৈরি ব্যর্থ হয়েছে।');
                }
            } catch (e) {
                console.error(e);
            }
        }

        // Story Highlight Viewer Logic
        let activeHighlightItems = [];
        let activeHighlightIndex = 0;
        let highlightTimer = null;
        let highlightProgressTimer = null;
        let isHighlightPaused = false;
        const HIGHLIGHT_SLIDE_DURATION = 5000;
        let highlightStartTime = 0;
        let highlightElapsedTime = 0;

        function viewHighlight(id, title, coverUrl, items) {
            if (!items || !Array.isArray(items) || items.length === 0) {
                items = [{
                    id: 0,
                    media_path: coverUrl || 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600',
                    media_type: 'image'
                }];
            }
            activeHighlightItems = items;
            activeHighlightIndex = 0;
            isHighlightPaused = false;

            const titleEl = document.getElementById('highlightModalTitle');
            if (titleEl) titleEl.textContent = title;

            // Render segmented progress bars
            const progressContainer = document.getElementById('highlightProgressBars');
            if (progressContainer) {
                progressContainer.innerHTML = '';
                for (let i = 0; i < activeHighlightItems.length; i++) {
                    const seg = document.createElement('div');
                    seg.className = 'highlight-progress-seg';
                    seg.innerHTML = `<div class="highlight-progress-fill" id="highlightFill_${i}"></div>`;
                    progressContainer.appendChild(seg);
                }
            }

            document.getElementById('storyHighlightModal')?.classList.add('active');
            renderHighlightSlide(0);
        }

        function renderHighlightSlide(index) {
            if (index < 0 || index >= activeHighlightItems.length) {
                closeStoryHighlightViewer();
                return;
            }

            activeHighlightIndex = index;
            highlightElapsedTime = 0;
            isHighlightPaused = false;

            const counterEl = document.getElementById('highlightModalCounter');
            if (counterEl) {
                counterEl.textContent = `${index + 1} / ${activeHighlightItems.length}`;
            }

            const pauseBtn = document.getElementById('highlightPauseBtn');
            if (pauseBtn) pauseBtn.textContent = '⏸️';

            // Update progress bars state
            for (let i = 0; i < activeHighlightItems.length; i++) {
                const fill = document.getElementById(`highlightFill_${i}`);
                if (fill) {
                    fill.style.transition = 'none';
                    if (i < index) {
                        fill.style.width = '100%';
                    } else if (i > index) {
                        fill.style.width = '0%';
                    } else {
                        fill.style.width = '0%';
                    }
                }
            }

            const current = activeHighlightItems[index];
            const imgEl = document.getElementById('highlightImage');
            const vidEl = document.getElementById('highlightVideo');

            if (vidEl) {
                vidEl.pause();
                vidEl.src = '';
                vidEl.style.display = 'none';
            }
            if (imgEl) {
                imgEl.src = '';
                imgEl.style.display = 'none';
            }

            if (current.media_type === 'video' || (current.media_path && (current.media_path.endsWith('.mp4') || current.media_path.endsWith('.webm')))) {
                if (vidEl) {
                    vidEl.src = current.media_path;
                    vidEl.style.display = 'block';
                    vidEl.play().catch(() => {});
                }
            } else {
                if (imgEl) {
                    imgEl.src = current.media_path;
                    imgEl.style.display = 'block';
                }
            }

            startHighlightTimer();
        }

        function startHighlightTimer() {
            clearTimeout(highlightTimer);
            clearInterval(highlightProgressTimer);

            highlightStartTime = Date.now();
            const currentFill = document.getElementById(`highlightFill_${activeHighlightIndex}`);
            if (currentFill) {
                currentFill.style.transition = 'width 0.1s linear';
            }

            highlightProgressTimer = setInterval(() => {
                if (!isHighlightPaused) {
                    highlightElapsedTime += 100;
                    const pct = Math.min(100, (highlightElapsedTime / HIGHLIGHT_SLIDE_DURATION) * 100);
                    const fill = document.getElementById(`highlightFill_${activeHighlightIndex}`);
                    if (fill) fill.style.width = `${pct}%`;

                    if (highlightElapsedTime >= HIGHLIGHT_SLIDE_DURATION) {
                        clearInterval(highlightProgressTimer);
                        nextHighlightSlide();
                    }
                }
            }, 100);
        }

        function nextHighlightSlide() {
            if (activeHighlightIndex + 1 < activeHighlightItems.length) {
                renderHighlightSlide(activeHighlightIndex + 1);
            } else {
                closeStoryHighlightViewer();
            }
        }

        function prevHighlightSlide() {
            if (activeHighlightIndex > 0) {
                renderHighlightSlide(activeHighlightIndex - 1);
            } else {
                renderHighlightSlide(0);
            }
        }

        function toggleHighlightPause() {
            isHighlightPaused = !isHighlightPaused;
            const pauseBtn = document.getElementById('highlightPauseBtn');
            const vidEl = document.getElementById('highlightVideo');

            if (isHighlightPaused) {
                if (pauseBtn) pauseBtn.textContent = '▶️';
                if (vidEl && vidEl.style.display !== 'none') vidEl.pause();
            } else {
                if (pauseBtn) pauseBtn.textContent = '⏸️';
                if (vidEl && vidEl.style.display !== 'none') vidEl.play().catch(() => {});
            }
        }

        function closeStoryHighlightViewer() {
            clearTimeout(highlightTimer);
            clearInterval(highlightProgressTimer);
            const vidEl = document.getElementById('highlightVideo');
            if (vidEl) {
                vidEl.pause();
                vidEl.src = '';
            }
            closeModal('storyHighlightModal');
        }

        function handleHighlightOverlayClick(e) {
            if (e.target.id === 'storyHighlightModal') {
                closeStoryHighlightViewer();
            }
        }

        // Global keyboard controls for highlight viewer
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('storyHighlightModal');
            if (modal && modal.classList.contains('active')) {
                if (e.key === 'Escape') {
                    closeStoryHighlightViewer();
                } else if (e.key === 'ArrowRight') {
                    nextHighlightSlide();
                } else if (e.key === 'ArrowLeft') {
                    prevHighlightSlide();
                } else if (e.key === ' ') {
                    e.preventDefault();
                    toggleHighlightPause();
                }
            }
        });

        // Followers & Following Modal Logic
        function openFollowModal(type = 'followers') {
            switchFollowModalTab(type);
            const searchInput = document.getElementById('followSearchInput');
            if (searchInput) {
                searchInput.value = '';
                filterFollowList('');
            }
            document.getElementById('profileFollowModal')?.classList.add('active');
        }

        function switchFollowModalTab(type) {
            const tabFlw = document.getElementById('followModalTabFollowers');
            const tabFlg = document.getElementById('followModalTabFollowing');
            const panelFlw = document.getElementById('followPanelFollowers');
            const panelFlg = document.getElementById('followPanelFollowing');

            if (type === 'followers') {
                if (tabFlw) {
                    tabFlw.style.color = 'var(--fb-primary)';
                    tabFlw.style.borderBottom = '3px solid var(--fb-primary)';
                }
                if (tabFlg) {
                    tabFlg.style.color = 'var(--fb-text-secondary)';
                    tabFlg.style.borderBottom = '3px solid transparent';
                }
                if (panelFlw) panelFlw.style.display = 'block';
                if (panelFlg) panelFlg.style.display = 'none';
            } else {
                if (tabFlg) {
                    tabFlg.style.color = 'var(--fb-primary)';
                    tabFlg.style.borderBottom = '3px solid var(--fb-primary)';
                }
                if (tabFlw) {
                    tabFlw.style.color = 'var(--fb-text-secondary)';
                    tabFlw.style.borderBottom = '3px solid transparent';
                }
                if (panelFlw) panelFlw.style.display = 'none';
                if (panelFlg) panelFlg.style.display = 'block';
            }
        }

        function filterFollowList(keyword) {
            const q = (keyword || '').toLowerCase().trim();
            document.querySelectorAll('.follow-user-item').forEach(el => {
                const name = el.getAttribute('data-name') || '';
                const user = el.getAttribute('data-username') || '';
                if (!q || name.includes(q) || user.includes(q)) {
                    el.style.display = 'flex';
                } else {
                    el.style.display = 'none';
                }
            });
        }

        async function toggleFollowUser(userId, btn) {
            if (!btn) return;
            const isCurrentlyFollowing = btn.textContent.trim() === 'ফলোয়িং';
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '...';

            try {
                const url = `/api/v1/users/${userId}/follow`;
                const method = isCurrentlyFollowing ? 'DELETE' : 'POST';
                const res = await fetch(url, {
                    method: method,
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    if (isCurrentlyFollowing) {
                        btn.textContent = 'ফলো করুন';
                    } else {
                        btn.textContent = 'ফলোয়িং';
                    }
                } else {
                    alert(data.message || 'অপারেশন ব্যর্থ হয়েছে।');
                    btn.textContent = originalText;
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
                btn.textContent = originalText;
            } finally {
                btn.disabled = false;
            }
        }

        // Work Experience Inline CRUD
        function toggleAddWorkForm() {
            const f = document.getElementById('addWorkInlineForm');
            if (f) f.style.display = (f.style.display === 'none' || f.style.display === '') ? 'block' : 'none';
        }

        async function submitInlineWork() {
            const company = document.getElementById('newWorkCompany')?.value.trim();
            const title = document.getElementById('newWorkTitle')?.value.trim();
            const type = document.getElementById('newWorkType')?.value;
            const location = document.getElementById('newWorkLocation')?.value.trim();
            const isCurrent = document.getElementById('newWorkIsCurrent')?.checked;

            if (!company || !title) {
                alert('অনুগ্রহ করে প্রতিষ্ঠানের নাম এবং পদবি পূরণ করুন।');
                return;
            }

            try {
                const res = await fetch('/api/v2/profile/work', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({
                        company_name: company,
                        job_title: title,
                        employment_type: type,
                        location: location,
                        is_current: isCurrent,
                        privacy: 'public'
                    })
                });

                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    const row = document.createElement('div');
                    const exp = data.data || {};
                    row.className = 'work-item-row';
                    row.id = `workRow_${exp.id || Date.now()}`;
                    row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--fb-hover);border-radius:var(--radius-sm);font-size:13px;';
                    row.innerHTML = `
                        <div>
                            <strong>${title}</strong> @ ${company}
                            <div style="font-size:11px;color:var(--fb-text-secondary);">
                                ${type} • ${location || 'অন-সাইট'} ${isCurrent ? '(বর্তমান)' : ''}
                            </div>
                        </div>
                        <button type="button" class="fb-btn fb-btn-secondary" onclick="deleteInlineWork(${exp.id || 0}, this)" style="padding:2px 8px;font-size:11px;color:var(--fb-red);" title="মুছে ফেলুন">✕</button>
                    `;
                    document.getElementById('noWorkPlaceholder')?.remove();
                    document.getElementById('workExperienceListContainer')?.prepend(row);

                    // Reset form
                    document.getElementById('newWorkCompany').value = '';
                    document.getElementById('newWorkTitle').value = '';
                    document.getElementById('newWorkLocation').value = '';
                    toggleAddWorkForm();
                    alert('কর্মসংস্থান সফলভাবে যুক্ত করা হয়েছে!');
                } else {
                    alert(data.message || 'কর্মসংস্থান যুক্ত করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        async function deleteInlineWork(id, btn) {
            if (!id || !confirm('আপনি কি নিশ্চিত যে এই কর্মসংস্থানটি মুছে ফেলতে চান?')) return;
            try {
                const res = await fetch(`/api/v2/profile/work/${id}`, {
                    method: 'DELETE',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    btn?.closest('.work-item-row')?.remove();
                } else {
                    alert(data.message || 'মুছে ফেলা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        // Follow / Unfollow User (Requirement 9)
        async function toggleFollowUser(userId) {
            const btn = document.getElementById('headerFollowBtn');
            if (!btn) return;
            const isCurrentlyFollowing = btn.textContent.includes('ফলো করছেন');

            try {
                const method = isCurrentlyFollowing ? 'DELETE' : 'POST';
                const res = await fetch(`/api/v1/users/${userId}/follow`, {
                    method: method,
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    if (isCurrentlyFollowing) {
                        btn.className = 'fb-btn fb-btn-primary';
                        btn.textContent = '🔔 ফলো করুন';
                    } else {
                        btn.className = 'fb-btn fb-btn-secondary';
                        btn.textContent = '✓ ফলো করছেন';
                    }
                } else {
                    alert(data.message || 'অপারেশন সম্পন্ন করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        // Profile Data Export (Requirement 30)
        function exportProfileData() {
            window.location.href = '/api/v2/profile/export?download=1';
        }

        // Restrict User (Requirement 19)
        async function handleRestrictUser(username) {
            try {
                const res = await fetch(`/api/v2/profile/${username}/restrict`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok) {
                    alert(data.message || 'রেস্ট্রিকশন স্ট্যাটাস পরিবর্তিত হয়েছে।');
                } else {
                    alert(data.message || 'রেস্ট্রিক্ট করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            }
        }



        // Notification Settings Modal (Requirement 20)
        function openNotificationSettingsModal() {
            closeAllProfileDropdowns();
            document.getElementById('notificationSettingsModal')?.classList.add('active');
        }

        async function submitNotificationSettings(e) {
            e.preventDefault();
            const form = document.getElementById('notificationSettingsForm');
            if (!form) return;

            const payload = {
                email_notifications: document.getElementById('notif_email')?.checked || false,
                push_notifications: document.getElementById('notif_push')?.checked || false,
                friend_request_alerts: document.getElementById('notif_friend')?.checked || false,
                comment_alerts: document.getElementById('notif_comment')?.checked || false,
                mention_alerts: document.getElementById('notif_mention')?.checked || false,
                security_alerts: document.getElementById('notif_security')?.checked || false
            };

            const btn = document.getElementById('saveNotifBtn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'সংরক্ষণ হচ্ছে...';
            }

            try {
                const res = await fetch('/api/v2/profile/notifications/settings', {
                    method: 'PUT',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok) {
                    alert(data.message || 'নোটিফিকেশন সেটিংস সংরক্ষিত হয়েছে।');
                    closeModal('notificationSettingsModal');
                } else {
                    alert(data.message || 'সংরক্ষণ করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'সংরক্ষণ করুন';
                }
            }
        }

        // Account Deactivation & Deletion (Requirement 29)
        function openAccountDeactivateModal() {
            closeAllProfileDropdowns();
            document.getElementById('accountDeactivateModal')?.classList.add('active');
        }

        function switchDeactMode(mode) {
            const deactTab = document.getElementById('deactivateTabBtn');
            const delTab = document.getElementById('deleteTabBtn');
            const deactForm = document.getElementById('deactivateAccountForm');
            const delForm = document.getElementById('deleteAccountForm');

            if (mode === 'deactivate') {
                deactTab.className = 'fb-btn fb-btn-primary';
                delTab.className = 'fb-btn fb-btn-secondary';
                delTab.style.color = 'var(--fb-red)';
                deactForm.style.display = 'block';
                delForm.style.display = 'none';
            } else {
                delTab.className = 'fb-btn';
                delTab.style.background = 'var(--fb-red)';
                delTab.style.color = '#fff';
                deactTab.className = 'fb-btn fb-btn-secondary';
                deactForm.style.display = 'none';
                delForm.style.display = 'block';
            }
        }

        async function submitAccountDeactivate(e) {
            e.preventDefault();
            const reason = document.getElementById('deactReason')?.value.trim();
            const password = document.getElementById('deactPassword')?.value;

            if (!confirm('আপনি কি নিশ্চিত যে সাময়িকভাবে অ্যাকাউন্ট ডিঅ্যাক্টিভেট করতে চান?')) return;

            try {
                const res = await fetch('/api/v2/profile/deactivate', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ reason: reason, password: password })
                });
                const data = await res.json();
                if (res.ok) {
                    alert(data.message || 'অ্যাকাউন্ট ডিঅ্যাক্টিভেট করা হয়েছে।');
                    window.location.href = '/login';
                } else {
                    alert(data.message || 'ডিঅ্যাক্টিভেট করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        async function submitAccountDelete(e) {
            e.preventDefault();
            const confirmText = document.getElementById('deleteConfirmText')?.value.trim();
            const password = document.getElementById('deletePassword')?.value;

            if (confirmText !== 'DELETE') {
                alert('নিশ্চিত করতে বক্সে DELETE শব্দটি বড় হাতের অক্ষরে লিখুন।');
                return;
            }

            if (!confirm('সতর্কতা: অ্যাকাউন্ট ডিলিট করলে আপনার প্রোফাইল মুছে ফেলা হবে। আপনি কি নিশ্চিত?')) return;

            try {
                const res = await fetch('/api/v2/profile/delete', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ confirmation: confirmText, password: password })
                });
                const data = await res.json();
                if (res.ok) {
                    alert(data.message || 'অ্যাকাউন্ট স্থায়ীভাবে ডিলিট করা হয়েছে।');
                    window.location.href = '/register';
                } else {
                    alert(data.message || 'ডিলিট করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        // Education Inline CRUD
        function toggleAddEduForm() {
            const f = document.getElementById('addEduInlineForm');
            if (f) f.style.display = (f.style.display === 'none' || f.style.display === '') ? 'block' : 'none';
        }

        async function submitInlineEdu() {
            const institution = document.getElementById('newEduInstitution')?.value.trim();
            const degree = document.getElementById('newEduDegree')?.value.trim();
            const field = document.getElementById('newEduField')?.value.trim();
            const isCurrent = document.getElementById('newEduIsCurrent')?.checked;

            if (!institution) {
                alert('অনুগ্রহ করে শিক্ষা প্রতিষ্ঠানের নাম পূরণ করুন।');
                return;
            }

            try {
                const res = await fetch('/api/v2/profile/education', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({
                        institution_name: institution,
                        degree: degree,
                        field_of_study: field,
                        is_current: isCurrent,
                        privacy: 'public'
                    })
                });

                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    const row = document.createElement('div');
                    const edu = data.data || {};
                    row.className = 'edu-item-row';
                    row.id = `eduRow_${edu.id || Date.now()}`;
                    row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--fb-hover);border-radius:var(--radius-sm);font-size:13px;';
                    row.innerHTML = `
                        <div>
                            <strong>${institution}</strong>
                            <div style="font-size:11px;color:var(--fb-text-secondary);">
                                ${degree || ''} ${field ? '(' + field + ')' : ''} ${isCurrent ? '• অধ্যয়নরত' : ''}
                            </div>
                        </div>
                        <button type="button" class="fb-btn fb-btn-secondary" onclick="deleteInlineEdu(${edu.id || 0}, this)" style="padding:2px 8px;font-size:11px;color:var(--fb-red);" title="মুছে ফেলুন">✕</button>
                    `;
                    document.getElementById('noEduPlaceholder')?.remove();
                    document.getElementById('educationListContainer')?.prepend(row);

                    // Reset form
                    document.getElementById('newEduInstitution').value = '';
                    document.getElementById('newEduDegree').value = '';
                    document.getElementById('newEduField').value = '';
                    toggleAddEduForm();
                    alert('শিক্ষাগত যোগ্যতা সফলভাবে যুক্ত করা হয়েছে!');
                } else {
                    alert(data.message || 'শিক্ষা প্রতিষ্ঠান যুক্ত করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        async function deleteInlineEdu(id, btn) {
            if (!id || !confirm('আপনি কি নিশ্চিত যে এই শিক্ষা প্রতিষ্ঠানটি মুছে ফেলতে চান?')) return;
            try {
                const res = await fetch(`/api/v2/profile/education/${id}`, {
                    method: 'DELETE',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    btn?.closest('.edu-item-row')?.remove();
                } else {
                    alert(data.message || 'মুছে ফেলা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        // Profile Share & QR Code Functions
        function openProfileShareModal() {
            closeAllProfileDropdowns();
            document.getElementById('profileShareModal')?.classList.add('active');
        }

        function copyProfileLink() {
            const input = document.getElementById('shareProfileUrlInput');
            if (input) {
                input.select();
                navigator.clipboard.writeText(input.value).then(() => {
                    alert('প্রোফাইল লিংক কপি করা হয়েছে!');
                }).catch(() => {
                    document.execCommand('copy');
                    alert('প্রোফাইল লিংক কপি করা হয়েছে!');
                });
            }
        }

        function downloadQrCode(url, filename) {
            fetch(url)
                .then(resp => resp.blob())
                .then(blob => {
                    const blobUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = blobUrl;
                    a.download = filename || 'profile_qr.png';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(blobUrl);
                    a.remove();
                })
                .catch(() => {
                    window.open(url, '_blank');
                });
        }

        // Report & Block User Functions
        let activeReportUserId = null;

        function openReportUserModal(userId, name) {
            activeReportUserId = userId;
            const nameEl = document.getElementById('reportTargetUserName');
            if (nameEl) nameEl.textContent = name;
            document.getElementById('reportUserModal')?.classList.add('active');
        }

        async function submitUserReport(userId) {
            const targetId = userId || activeReportUserId;
            const reason = document.getElementById('reportReasonSelect')?.value;
            const details = document.getElementById('reportDetailsInput')?.value.trim();

            if (!targetId || !reason) {
                alert('অনুগ্রহ করে প্রয়োজনীয় তথ্য পূরণ করুন।');
                return;
            }

            try {
                const res = await fetch('/api/v1/reports', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({
                        reportable_type: 'user',
                        reportable_id: targetId,
                        reason: reason,
                        details: details
                    })
                });

                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('আপনার রিপোর্ট সফলভাবে জমা হয়েছে। মডারেশন টিম এটি পর্যালোচনা করবে।');
                    closeModal('reportUserModal');
                } else {
                    alert(data.message || 'রিপোর্ট জমা দেওয়া যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        async function handleBlockUser(userId, name) {
            if (!confirm(`আপনি কি নিশ্চিত যে ${name}-কে ব্লক করতে চান? ব্লক করার পর আপনারা একে অপরের প্রোফাইল বা পোস্ট দেখতে পারবেন না।`)) {
                return;
            }

            try {
                const res = await fetch(`/api/v1/users/${userId}/block`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });

                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert(data.message || 'ব্যবহারকারীকে ব্লক করা হয়েছে।');
                    closeModal('reportUserModal');
                    window.location.href = '/dashboard';
                } else {
                    alert(data.message || 'ব্লক করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        // Reels Player
        let currentProfileReelId = null;
        function playReelModal(url, name, caption = '', likes = 0, comments = 0, reelId = null) {
            currentProfileReelId = reelId;
            const v = document.getElementById('activeReelVideo');
            if (v) {
                v.src = url;
                v.play().catch(() => {});
            }
            const nameEl = document.getElementById('activeReelAuthorName');
            if (nameEl) nameEl.innerText = name || '{{ addslashes($profile['name']) }}';
            const capEl = document.getElementById('activeReelCaption');
            if (capEl) capEl.innerText = caption;
            const likesEl = document.getElementById('activeReelLikes');
            if (likesEl) likesEl.innerText = `❤️ ${likes} লাইক`;
            const commentsEl = document.getElementById('activeReelComments');
            if (commentsEl) commentsEl.innerText = `💬 ${comments} মন্তব্য`;

            if (reelId) {
                fetch(`/api/v2/reels/${reelId}/view`, {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ watch_time_seconds: 5.0 })
                }).then(res => {
                    if (res.status === 410) {
                        alert('এই রিলটির ২৪ ঘণ্টার মেয়াদ শেষ হয়ে গেছে।');
                        closeReelPlayerModal();
                        document.getElementById(`profile-reel-card-${reelId}`)?.remove();
                    }
                }).catch(() => {});
            }
            document.getElementById('reelPlayerModal')?.classList.add('active');
        }

        function closeReelPlayerModal() {
            currentProfileReelId = null;
            const v = document.getElementById('activeReelVideo');
            if (v) {
                v.pause();
                v.src = '';
            }
            closeModal('reelPlayerModal');
        }

        // Active 24-Hour Stories Viewer
        let profileActiveStories = @json($activeStories ?? []);
        let activeStoryCurrentIndex = 0;
        let activeStoryTimer = null;
        let activeStoryProgressTimer = null;
        let activeStoryElapsedTime = 0;
        let isActiveStoryPaused = false;
        const ACTIVE_STORY_DURATION = 6000;

        function openProfileStoriesModal(e) {
            if (e) e.stopPropagation();
            if (!profileActiveStories || profileActiveStories.length === 0) {
                alert('বর্তমানে কোনো সক্রিয় স্টোরি নেই।');
                return;
            }
            activeStoryCurrentIndex = 0;
            const modal = document.getElementById('activeStoryViewerModal');
            if (modal) modal.classList.add('active');
            renderActiveStoryProgressBars();
            renderActiveStorySlide(0);
        }

        function renderActiveStoryProgressBars() {
            const container = document.getElementById('activeStoryProgressBars');
            if (!container) return;
            container.innerHTML = '';
            profileActiveStories.forEach((_, idx) => {
                const bar = document.createElement('div');
                bar.className = 'highlight-progress-bar';
                const fill = document.createElement('div');
                fill.className = 'highlight-progress-fill';
                fill.id = `activeStoryFill_${idx}`;
                bar.appendChild(fill);
                container.appendChild(bar);
            });
        }

        function renderActiveStorySlide(index) {
            if (index < 0 || index >= profileActiveStories.length) {
                closeActiveStoryViewer();
                return;
            }
            activeStoryCurrentIndex = index;
            activeStoryElapsedTime = 0;
            isActiveStoryPaused = false;

            for (let i = 0; i < profileActiveStories.length; i++) {
                const fill = document.getElementById(`activeStoryFill_${i}`);
                if (fill) {
                    fill.style.transition = 'none';
                    fill.style.width = i < index ? '100%' : '0%';
                }
            }

            const story = profileActiveStories[index];
            const imgEl = document.getElementById('activeStoryImage');
            const vidEl = document.getElementById('activeStoryVideo');
            const capEl = document.getElementById('activeStoryCaptionOverlay');
            const timeEl = document.getElementById('activeStoryTimeRemaining');

            if (timeEl && story.time_remaining_seconds !== undefined) {
                const m = Math.floor(story.time_remaining_seconds / 60);
                const h = Math.floor(m / 60);
                timeEl.innerText = h > 0 ? `⏳ ${h} ঘণ্টা বাকি` : `⏳ ${m} মিনিট বাকি`;
            }

            if (capEl) {
                if (story.caption) {
                    capEl.innerText = story.caption;
                    capEl.style.display = 'block';
                } else {
                    capEl.style.display = 'none';
                }
            }

            if (vidEl) {
                vidEl.pause();
                vidEl.src = '';
                vidEl.style.display = 'none';
            }
            if (imgEl) {
                imgEl.src = '';
                imgEl.style.display = 'none';
            }

            const rawMediaUrl = story.media_url || (story.media && story.media[0] ? story.media[0].urls?.original || story.media[0].url : '');
            const mediaUrl = normalizeMediaUrl(rawMediaUrl);
            const isVideo = story.media_type === 'video' || (mediaUrl && (mediaUrl.endsWith('.mp4') || mediaUrl.endsWith('.webm')));

            if (isVideo) {
                if (vidEl) {
                    vidEl.src = mediaUrl;
                    vidEl.style.display = 'block';
                    vidEl.play().catch(() => {});
                }
            } else {
                if (imgEl) {
                    imgEl.src = mediaUrl;
                    imgEl.style.display = 'block';
                }
            }

            // Record view via API
            if (story.id) {
                fetch(`/api/v2/stories/${story.id}/view`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                }).then(res => {
                    if (res.status === 410) {
                        alert('এই স্টোরিটির ২৪ ঘণ্টার মেয়াদ শেষ হয়ে গেছে।');
                        profileActiveStories.splice(index, 1);
                        if (profileActiveStories.length === 0) {
                            closeActiveStoryViewer();
                        } else {
                            renderActiveStorySlide(Math.min(index, profileActiveStories.length - 1));
                        }
                    }
                }).catch(() => {});
            }

            startActiveStoryTimer();
        }

        function startActiveStoryTimer() {
            clearTimeout(activeStoryTimer);
            clearInterval(activeStoryProgressTimer);

            const currentFill = document.getElementById(`activeStoryFill_${activeStoryCurrentIndex}`);
            if (currentFill) {
                currentFill.style.transition = 'width 0.1s linear';
            }

            activeStoryProgressTimer = setInterval(() => {
                if (!isActiveStoryPaused) {
                    activeStoryElapsedTime += 100;
                    const pct = Math.min(100, (activeStoryElapsedTime / ACTIVE_STORY_DURATION) * 100);
                    const fill = document.getElementById(`activeStoryFill_${activeStoryCurrentIndex}`);
                    if (fill) fill.style.width = `${pct}%`;

                    if (activeStoryElapsedTime >= ACTIVE_STORY_DURATION) {
                        clearInterval(activeStoryProgressTimer);
                        nextActiveStorySlide();
                    }
                }
            }, 100);
        }

        function nextActiveStorySlide() {
            if (activeStoryCurrentIndex + 1 < profileActiveStories.length) {
                renderActiveStorySlide(activeStoryCurrentIndex + 1);
            } else {
                closeActiveStoryViewer();
            }
        }

        function prevActiveStorySlide() {
            if (activeStoryCurrentIndex > 0) {
                renderActiveStorySlide(activeStoryCurrentIndex - 1);
            } else {
                renderActiveStorySlide(0);
            }
        }

        function toggleActiveStoryPause() {
            isActiveStoryPaused = !isActiveStoryPaused;
            const pauseBtn = document.getElementById('activeStoryPauseBtn');
            const vidEl = document.getElementById('activeStoryVideo');

            if (isActiveStoryPaused) {
                if (pauseBtn) pauseBtn.textContent = '▶️';
                if (vidEl && vidEl.style.display !== 'none') vidEl.pause();
            } else {
                if (pauseBtn) pauseBtn.textContent = '⏸️';
                if (vidEl && vidEl.style.display !== 'none') vidEl.play().catch(() => {});
            }
        }

        function closeActiveStoryViewer() {
            clearTimeout(activeStoryTimer);
            clearInterval(activeStoryProgressTimer);
            const vidEl = document.getElementById('activeStoryVideo');
            if (vidEl) {
                vidEl.pause();
                vidEl.src = '';
            }
            closeModal('activeStoryViewerModal');
        }

        function handleActiveStoryOverlayClick(e) {
            if (e.target.id === 'activeStoryViewerModal') {
                closeActiveStoryViewer();
            }
        }

        async function reactToActiveStory(type) {
            const story = profileActiveStories[activeStoryCurrentIndex];
            if (!story || !story.id) return;
            try {
                const res = await fetch(`/api/v2/stories/${story.id}/react`, {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ type })
                });
                if (res.status === 410) {
                    alert('এই স্টোরিটির মেয়াদ শেষ হয়ে গেছে।');
                    closeActiveStoryViewer();
                    return;
                }
                const data = await res.json();
                if (data.success) {
                    alert('রিঅ্যাকশন দেওয়া হয়েছে! ❤️');
                }
            } catch (e) {}
        }

        // Professional Mode Toggle
        async function toggleProfessionalMode(checkbox) {
            try {
                const res = await fetch('/api/v1/profile/professional-mode/toggle', {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert(data.message || 'প্রফেশনাল মোড আপডেট হয়েছে।');
                    window.location.reload();
                } else {
                    checkbox.checked = !checkbox.checked;
                    alert('প্রফেশনাল মোড পরিবর্তন করা যায়নি।');
                }
            } catch (e) {
                checkbox.checked = !checkbox.checked;
                console.error(e);
            }
        }

        // Creator Category Modal & Update
        function openCreatorCategoryModal() {
            const modal = document.getElementById('creatorCategoryModal');
            if (modal) modal.classList.add('active');
        }

        async function saveCreatorCategory() {
            const select = document.getElementById('creatorCategorySelect');
            const btn = document.getElementById('saveCreatorCategoryBtn');
            const category = select ? select.value : '';

            if (btn) {
                btn.disabled = true;
                btn.innerText = 'সংরক্ষণ হচ্ছে...';
            }

            try {
                const res = await fetch('/api/v1/profile/about/personal', {
                    method: 'PUT',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ category: category })
                });

                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('ক্রিয়েটর ক্যাটাগরি সফলভাবে সংরক্ষিত হয়েছে!');
                    closeModal('creatorCategoryModal');
                    window.location.reload();
                } else {
                    alert(data.message || 'ক্যাটাগরি আপডেট করতে সমস্যা হয়েছে।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভারের সাথে সংযোগ স্থাপন করা সম্ভব হয়নি।');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'সংরক্ষণ করুন';
                }
            }
        }

        // Friend List Management
        async function toggleFavoriteFriend(friendId, btn) {
            try {
                const res = await fetch(`/api/v1/profile/friends/${friendId}/favorite`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success' || data.is_favorite !== undefined)) {
                    btn.innerText = data.is_favorite ? '❤️ ফেভারিট (হ্যাঁ)' : '❤️ ফেভারিট';
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function toggleCloseFriend(friendId, btn) {
            try {
                const res = await fetch(`/api/v1/profile/friends/${friendId}/close`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success' || data.is_close_friend !== undefined)) {
                    btn.innerText = data.is_close_friend ? '⭐ ক্লোজ (হ্যাঁ)' : '⭐ ক্লোজ';
                }
            } catch (e) {
                console.error(e);
            }
        }

        // Saved Items Hub
        let currentSavedTypeFilter = 'all';

        function filterSavedItems(type, btn) {
            currentSavedTypeFilter = type;
            document.querySelectorAll('#savedCategoryFilterWrap .friend-filter-chip').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            applySavedFilterAndSearch();
        }

        function handleSavedSearch(query) {
            applySavedFilterAndSearch();
        }

        function applySavedFilterAndSearch() {
            const query = (document.getElementById('savedSearchInput')?.value || '').trim().toLowerCase();
            const rows = document.querySelectorAll('.saved-item-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const itemType = (row.getAttribute('data-saved-type') || '').toLowerCase();
                const collection = (row.getAttribute('data-saved-collection') || '').toLowerCase();
                const text = row.innerText.toLowerCase();

                let matchesFilter = (currentSavedTypeFilter === 'all') || itemType.includes(currentSavedTypeFilter);
                let matchesSearch = !query || text.includes(query) || collection.includes(query);

                if (matchesFilter && matchesSearch) {
                    row.style.display = 'flex';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const noResults = document.getElementById('savedNoSearchResults');
            if (noResults) {
                noResults.style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
            }
        }

        async function handleUnsaveItem(itemType, itemId, rowId) {
            if (!confirm('আপনি কি নিশ্চিত যে এই আইটেমটি সংরক্ষিত তালিকা থেকে মুছে ফেলতে চান?')) return;
            const row = document.getElementById(rowId);
            if (row) row.style.opacity = '0.5';

            try {
                const res = await fetch('/api/v1/profile/saved-items/toggle', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ item_type: itemType, item_id: itemId })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success' || res.ok)) {
                    if (row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.transform = 'scale(0.95)';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            const remaining = document.querySelectorAll('.saved-item-row');
                            if (remaining.length === 0) {
                                const emptyNotice = document.getElementById('savedEmptyNotice');
                                if (emptyNotice) emptyNotice.style.display = 'block';
                            }
                        }, 300);
                    }
                } else {
                    if (row) row.style.opacity = '1';
                    alert('সংরক্ষিত আইটেম মোছা সম্ভব হয়নি।');
                }
            } catch (e) {
                if (row) row.style.opacity = '1';
                console.error(e);
            }
        }

        async function toggleSaveItem(itemType, itemId) {
            try {
                const res = await fetch('/api/v1/profile/saved-items/toggle', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ item_type: itemType, item_id: itemId })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success' || res.ok)) {
                    alert('সংরক্ষিত আইটেম সফলভাবে আপডেট হয়েছে।');
                    window.location.reload();
                } else {
                    alert(data.message || 'সংরক্ষিত আইটেম পরিবর্তন করা যায়নি।');
                }
            } catch (e) {
                console.error(e);
            }
        }

        // Activity Log Filter & Live Search
        let currentActivityCategory = 'all';

        function filterActivityLog(category, btn) {
            currentActivityCategory = category;
            document.querySelectorAll('#activityFilterWrap .friend-filter-chip').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            applyActivityFilterAndSearch();
        }

        function handleActivitySearch(query) {
            applyActivityFilterAndSearch();
        }

        function applyActivityFilterAndSearch() {
            const query = (document.getElementById('activitySearchInput')?.value || '').trim().toLowerCase();
            const rows = document.querySelectorAll('.activity-log-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const cat = (row.getAttribute('data-activity-category') || '').toLowerCase();
                const desc = (row.querySelector('.activity-desc')?.innerText || '').toLowerCase();

                let matchesCat = (currentActivityCategory === 'all') || (cat === currentActivityCategory);
                let matchesSearch = !query || desc.includes(query);

                if (matchesCat && matchesSearch) {
                    row.style.display = 'flex';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const noResults = document.getElementById('activityNoSearchResults');
            if (noResults) {
                noResults.style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
            }
        }

        // Friend List Management & Live Search
        let currentFriendsFilter = 'all';

        function handleFriendSearch(query) {
            const clearBtn = document.getElementById('clearFriendSearchBtn');
            if (clearBtn) clearBtn.style.display = query.trim() ? 'inline-block' : 'none';
            applyFriendsFilterAndSearch();
        }

        function clearFriendSearch() {
            const input = document.getElementById('friendSearchInput');
            if (input) input.value = '';
            const clearBtn = document.getElementById('clearFriendSearchBtn');
            if (clearBtn) clearBtn.style.display = 'none';
            applyFriendsFilterAndSearch();
        }

        function filterFriends(filterType, btn) {
            currentFriendsFilter = filterType;
            document.querySelectorAll('#tabContent-friends .filter-chip').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const grid = document.getElementById('friendsListGrid');
            const sugSec = document.getElementById('friendSuggestionsSection');
            const emptyState = document.getElementById('friendsSearchEmptyState');

            if (filterType === 'suggestions') {
                if (grid) grid.style.display = 'none';
                if (emptyState) emptyState.style.display = 'none';
                if (sugSec) sugSec.style.display = 'block';
                return;
            }

            if (grid) grid.style.display = 'grid';
            if (sugSec) sugSec.style.display = 'none';
            applyFriendsFilterAndSearch();
        }

        function applyFriendsFilterAndSearch() {
            const query = (document.getElementById('friendSearchInput')?.value || '').trim().toLowerCase();
            const cards = document.querySelectorAll('.friend-item-card');
            const emptyState = document.getElementById('friendsSearchEmptyState');
            const grid = document.getElementById('friendsListGrid');

            let visibleCount = 0;

            cards.forEach(card => {
                const name = (card.getAttribute('data-name') || '').toLowerCase();
                const username = (card.getAttribute('data-username') || '').toLowerCase();
                const isFav = card.getAttribute('data-favorite') === '1';
                const isClose = card.getAttribute('data-close') === '1';

                // Match filter tab
                let matchesTab = true;
                if (currentFriendsFilter === 'favorites') {
                    matchesTab = isFav;
                } else if (currentFriendsFilter === 'close_friends') {
                    matchesTab = isClose;
                }

                // Match search query
                const matchesQuery = !query || name.includes(query) || username.includes(query);

                if (matchesTab && matchesQuery) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (emptyState) {
                emptyState.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
            }
            if (grid && visibleCount === 0 && cards.length > 0) {
                grid.style.display = 'none';
            } else if (grid && currentFriendsFilter !== 'suggestions') {
                grid.style.display = 'grid';
            }
        }

        async function toggleFavoriteFriendEnhanced(friendId, btn) {
            try {
                const res = await fetch(`/api/v1/profile/friends/${friendId}/favorite`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success' || data.is_favorite !== undefined)) {
                    const card = document.getElementById(`friendCard-${friendId}`);
                    if (data.is_favorite) {
                        btn.innerText = '❤️ ফেভারিট (হ্যাঁ)';
                        btn.style.background = '#fee2e2';
                        btn.style.color = '#dc2626';
                        btn.style.borderColor = '#fca5a5';
                        if (card) card.setAttribute('data-favorite', '1');
                    } else {
                        btn.innerText = '❤️ ফেভারিট';
                        btn.style.background = '';
                        btn.style.color = '';
                        btn.style.borderColor = '';
                        if (card) card.setAttribute('data-favorite', '0');
                        if (currentFriendsFilter === 'favorites') applyFriendsFilterAndSearch();
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function toggleCloseFriendEnhanced(friendId, btn) {
            try {
                const res = await fetch(`/api/v1/profile/friends/${friendId}/close`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success' || data.is_close_friend !== undefined)) {
                    const card = document.getElementById(`friendCard-${friendId}`);
                    if (data.is_close_friend) {
                        btn.innerText = '⭐ ক্লোজ (হ্যাঁ)';
                        btn.style.background = '#fef3c7';
                        btn.style.color = '#d97706';
                        btn.style.borderColor = '#fcd34d';
                        if (card) card.setAttribute('data-close', '1');
                    } else {
                        btn.innerText = '⭐ ক্লোজ';
                        btn.style.background = '';
                        btn.style.color = '';
                        btn.style.borderColor = '';
                        if (card) card.setAttribute('data-close', '0');
                        if (currentFriendsFilter === 'close_friends') applyFriendsFilterAndSearch();
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function handleUnfriendUser(friendId, friendName) {
            if (!confirm(`আপনি কি নিশ্চিত যে ${friendName} কে আনফ্রেন্ড করতে চান?`)) return;
            try {
                const res = await fetch(`/api/v1/friends/${friendId}`, {
                    method: 'DELETE',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    const card = document.getElementById(`friendCard-${friendId}`);
                    if (card) {
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        setTimeout(() => {
                            card.remove();
                            applyFriendsFilterAndSearch();
                        }, 300);
                    }
                    const counter = document.getElementById('friendsCountDisplay');
                    if (counter) {
                        const current = parseInt(counter.innerText.replace(/,/g, '')) || 0;
                        if (current > 0) counter.innerText = (current - 1).toLocaleString();
                    }
                    alert(`${friendName} কে আনফ্রেন্ড করা হয়েছে।`);
                } else {
                    alert(data.message || 'আনফ্রেন্ড করতে ব্যর্থ হয়েছে।');
                }
            } catch (e) {
                console.error(e);
                alert('সার্ভার যোগাযোগ ত্রুটি।');
            }
        }

        // Friendship Actions
        async function handleSendFriendRequest(userId) {
            try {
                const res = await fetch(`/api/v1/friends/${userId}/request`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert(data.message || 'ফ্রেন্ড রিকোয়েস্ট সফলভাবে পাঠানো হয়েছে।');
                    window.location.reload();
                } else {
                    alert(data.message || 'ফ্রেন্ড রিকোয়েস্ট পাঠানো যায়নি।');
                }
            } catch (e) {
                console.error(e);
                alert('যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        async function handleCancelFriendRequest(userId) {
            if (!confirm('আপনি কি ফ্রেন্ড রিকোয়েস্ট বাতিল করতে চান?')) return;
            try {
                const res = await fetch(`/api/v1/friends/${userId}/cancel`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert(data.message || 'অনুরোধ বাতিল করা হয়েছে।');
                    window.location.reload();
                } else {
                    alert(data.message || 'বাতিল করা যায়নি।');
                }
            } catch (e) {
                console.error(e);
                alert('যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        async function handleAcceptFriendRequest(userId) {
            try {
                const res = await fetch(`/api/v1/friends/${userId}/accept`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('অভিনন্দন! আপনারা এখন বন্ধু।');
                    window.location.reload();
                } else {
                    alert(data.message || 'অনুরোধ গ্রহণ করা যায়নি।');
                }
            } catch (e) {
                console.error(e);
                alert('যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        async function handleDeclineFriendRequest(userId) {
            try {
                const res = await fetch(`/api/v1/friends/${userId}/decline`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    window.location.reload();
                } else {
                    alert(data.message || 'অনুরোধ বাতিল করা যায়নি।');
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function confirmUnfriend(userId, userName) {
            if (!confirm(`আপনি কি নিশ্চিত যে ${userName}-কে ফ্রেন্ডলিস্ট থেকে সরাতে চান?`)) return;
            try {
                const res = await fetch(`/api/v1/friends/${userId}`, {
                    method: 'DELETE',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('বন্ধু সফলভাবে তালিকা থেকে সরানো হয়েছে।');
                    window.location.reload();
                } else {
                    alert(data.message || 'ব্যর্থ হয়েছে।');
                }
            } catch (e) {
                console.error(e);
                alert('যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        // Story Helpers & Submit
        function toggleStoryType(val) {
            const mediaGroup = document.getElementById('storyMediaGroup');
            const fileInput = document.getElementById('storyFileInput');
            if (val === 'text') {
                if (mediaGroup) mediaGroup.style.display = 'none';
                if (fileInput) fileInput.required = false;
            } else {
                if (mediaGroup) mediaGroup.style.display = 'block';
                if (fileInput) fileInput.required = true;
            }
        }

        async function submitStory(e) {
            e.preventDefault();
            const btn = document.getElementById('submitStoryBtn');
            btn.disabled = true;
            btn.innerText = 'আপলোড হচ্ছে...';

            const form = document.getElementById('storyForm');
            const formData = new FormData(form);

            try {
                const res = await fetch('/api/v1/stories', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: getAuthHeaders(),
                    body: formData
                });
                const data = await res.json().catch(() => null);
                if (res.ok && data && (data.success || data.status === 'success')) {
                    alert(data.message || 'আপনার স্টোরি সফলভাবে প্রকাশিত হয়েছে!');
                    closeModal('storyModal');
                    window.location.reload();
                } else {
                    const errorMsg = data?.message || data?.errors?.media?.[0] || data?.errors?.file?.[0] || data?.errors?.content?.[0] || 'স্টোরি প্রকাশ ব্যর্থ হয়েছে।';
                    alert(errorMsg);
                    btn.disabled = false;
                    btn.innerText = 'স্টোরি শেয়ার করুন';
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগ ত্রুটি। অনুগ্রহ করে রিফ্রেশ করে আবার চেষ্টা করুন।');
                btn.disabled = false;
                btn.innerText = 'স্টোরি শেয়ার করুন';
            }
        }

        // Verification Submit
        async function submitVerification(e) {
            e.preventDefault();
            const btn = document.getElementById('submitVerifyBtn');
            btn.disabled = true;
            btn.innerText = 'আবেদন পাঠানো হচ্ছে...';

            const form = document.getElementById('verificationForm');
            const formData = new FormData(form);

            try {
                const res = await fetch('/api/v2/profile/verification/submit', {
                    method: 'POST',
                    headers: getAuthHeaders(),
                    body: formData
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert(data.message || 'ভেরিফিকেশন আবেদন সফলভাবে জমা হয়েছে।');
                    window.location.reload();
                } else {
                    let errMsg = data.message || 'আবেদন জমা দিতে সমস্যা হয়েছে।';
                    if (data.errors) {
                        errMsg += '\n• ' + Object.values(data.errors).flat().join('\n• ');
                    }
                    alert(errMsg);
                    btn.disabled = false;
                    btn.innerText = 'আবেদন জমা দিন';
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
                btn.disabled = false;
                btn.innerText = 'আবেদন জমা দিন';
            }
        }

        // Privacy Settings Subtab Switcher
        function switchPrivacySubtab(tab, btn) {
            document.querySelectorAll('.privacy-subtab-pane').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.privacy-subtab-btn').forEach(b => {
                b.classList.remove('active');
                b.style.color = 'var(--fb-text-secondary)';
                b.style.borderBottomColor = 'transparent';
                b.style.fontWeight = '500';
            });
            const pane = document.getElementById(`privacyTabContent-${tab}`);
            if (pane) pane.style.display = 'block';
            if (btn) {
                btn.classList.add('active');
                btn.style.color = '#0084ff';
                btn.style.borderBottomColor = '#0084ff';
                btn.style.fontWeight = '700';
            }
            if (tab === 'blocking') {
                loadBlockedUsersInPrivacyTab();
            }
        }

        async function loadBlockedUsersInPrivacyTab() {
            const listEl = document.getElementById('privacyBlockedUsersList');
            if (!listEl) return;
            listEl.innerHTML = '<div style="text-align:center;padding:16px;color:var(--fb-text-secondary);font-size:13px;">তালিকা লোড হচ্ছে...</div>';
            try {
                const res = await fetch('/api/v2/profile/blocked-users', {
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                const users = (data.data && Array.isArray(data.data)) ? data.data : (Array.isArray(data) ? data : []);
                if (users.length === 0) {
                    listEl.innerHTML = '<div style="text-align:center;padding:20px;color:var(--fb-text-secondary);font-size:13px;">কোনো ব্যবহারকারীকে ব্লক করা হয়নি।</div>';
                    return;
                }
                listEl.innerHTML = users.map(u => `
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--fb-hover);border-radius:8px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <img src="${u.avatar_url || '/images/default-avatar.svg'}" style="width:34px;height:34px;border-radius:50%;object-fit:cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                            <div>
                                <div style="font-size:13px;font-weight:700;color:var(--fb-text-primary);">${u.name || u.username}</div>
                                <div style="font-size:11px;color:var(--fb-text-secondary);">@${u.username}</div>
                            </div>
                        </div>
                        <button type="button" class="fb-btn fb-btn-secondary" style="font-size:12px;padding:4px 12px;" onclick="unblockUserFromPrivacyModal('${u.username}')">
                            আনব্লক
                        </button>
                    </div>
                `).join('');
            } catch (err) {
                console.error(err);
                listEl.innerHTML = '<div style="text-align:center;padding:16px;color:var(--fb-text-secondary);font-size:13px;">তালিকা লোড করা যায়নি।</div>';
            }
        }

        async function unblockUserFromPrivacyModal(username) {
            if (!confirm(`আপনি কি @${username} কে আনব্লক করতে চান?`)) return;
            try {
                const res = await fetch(`/api/v2/profile/${username}/unblock`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    showToast('সফলভাবে আনব্লক করা হয়েছে!');
                    loadBlockedUsersInPrivacyTab();
                } else {
                    showToast(data.message || 'আনব্লক করা যায়নি।');
                }
            } catch (e) {
                console.error(e);
                showToast('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            }
        }

        // Privacy Settings Submit
        async function submitPrivacySettings(e) {
            e.preventDefault();
            const btn = document.getElementById('savePrivacyBtn');
            btn.disabled = true;
            btn.innerText = 'সংরক্ষণ হচ্ছে...';

            const form = document.getElementById('privacyForm');
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());

            // Explicitly handle boolean checkboxes
            payload.search_engine_indexing = form.querySelector('[name="search_engine_indexing"]')?.checked ? true : false;
            payload.show_online_status = form.querySelector('[name="show_online_status"]')?.checked ? true : false;
            payload.read_receipts_enabled = form.querySelector('[name="read_receipts_enabled"]')?.checked ? true : false;

            try {
                const res = await fetch('/api/v2/profile/privacy/settings', {
                    method: 'PUT',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    showToast(data.message || 'প্রাইভেসি সেটিংস সফলভাবে আপডেট হয়েছে!');
                    closeModal('privacyModal');
                } else {
                    showToast(data.message || 'প্রাইভেসি আপডেট করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                showToast('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            } finally {
                btn.disabled = false;
                btn.innerText = 'প্রাইভেসি সংরক্ষণ করুন';
            }
        }

        // ================= SECURITY & DEVICES CENTER JS =================
        function switchSecuritySubtab(tab) {
            ['devices', 'password', 'twofactor'].forEach(t => {
                const btn = document.getElementById(`secTabBtn-${t}`);
                const content = document.getElementById(`secTabContent-${t}`);
                if (btn) {
                    if (t === tab) {
                        btn.classList.add('active');
                        btn.style.color = 'var(--fb-primary)';
                        btn.style.borderBottomColor = 'var(--fb-primary)';
                        btn.style.fontWeight = '700';
                    } else {
                        btn.classList.remove('active');
                        btn.style.color = 'var(--fb-text-secondary)';
                        btn.style.borderBottomColor = 'transparent';
                        btn.style.fontWeight = '600';
                    }
                }
                if (content) {
                    content.style.display = t === tab ? 'block' : 'none';
                }
            });
        }

        async function loadSecurityDevices() {
            const container = document.getElementById('securityDevicesList');
            if (!container) return;
            container.innerHTML = '<div style="text-align:center;padding:24px;color:var(--fb-text-secondary);font-size:13px;">ডিভাইস তথ্য লোড হচ্ছে...</div>';
            
            try {
                const res = await fetch('/devices', {
                    headers: getAuthHeaders({ 'Accept': 'application/json' })
                });
                const data = await res.json();
                if (res.ok && data.success && data.data) {
                    const sessions = data.data.sessions || [];
                    const currentSession = data.data.current_session;
                    
                    if (sessions.length === 0) {
                        container.innerHTML = '<div style="text-align:center;padding:24px;color:var(--fb-text-secondary);font-size:13px;">কোনো সক্রিয় সেশন পাওয়া যায়নি।</div>';
                        return;
                    }

                    container.innerHTML = sessions.map(s => {
                        const isCurrent = s.is_current || (currentSession && currentSession.id === s.id);
                        const deviceName = s.device_name || s.browser || s.platform || 'ব্রাউজার / ডিভাইস';
                        const ip = s.ip_address || 'অজ্ঞাত আইপি';
                        const lastActive = s.last_active_at ? new Date(s.last_active_at).toLocaleString('bn-BD', { dateStyle: 'medium', timeStyle: 'short' }) : 'সক্রিয়';
                        const location = s.location_name || s.city || '';

                        return `
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border:1px solid var(--fb-border);border-radius:var(--radius-md);background:${isCurrent ? '#f0fdf4' : 'var(--fb-bg)'};">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div style="width:38px;height:38px;border-radius:50%;background:${isCurrent ? '#dcfce7' : 'var(--fb-hover)'};color:${isCurrent ? '#15803d' : 'var(--fb-text-primary)'};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                                    </div>
                                    <div>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <span style="font-weight:700;font-size:14px;color:var(--fb-text-primary);">${deviceName}</span>
                                            ${isCurrent ? '<span style="background:#bbf7d0;color:#166534;font-size:10px;font-weight:700;padding:2px 6px;border-radius:10px;">বর্তমান সেশন</span>' : ''}
                                        </div>
                                        <div style="font-size:12px;color:var(--fb-text-secondary);margin-top:2px;">
                                            আইপি: ${ip} ${location ? '• ' + location : ''} • সর্বশেষ: ${lastActive}
                                        </div>
                                    </div>
                                </div>
                                ${!isCurrent ? `
                                    <button type="button" class="fb-btn fb-btn-secondary" onclick="logoutDevice(${s.id})" style="font-size:12px;padding:4px 10px;color:var(--fb-red);border-color:#fecaca;">
                                        লগআউট
                                    </button>
                                ` : ''}
                            </div>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = '<div style="text-align:center;padding:24px;color:var(--fb-text-secondary);font-size:13px;">ডিভাইস তথ্য লোড করা যায়নি।</div>';
                }
            } catch (e) {
                console.error(e);
                container.innerHTML = '<div style="text-align:center;padding:24px;color:var(--fb-text-secondary);font-size:13px;">সার্ভার যোগাযোগে সমস্যা হয়েছে।</div>';
            }
        }

        async function logoutDevice(sessionId) {
            if (!confirm('আপনি কি এই ডিভাইসটি লগআউট করতে নিশ্চিত?')) return;
            try {
                const res = await fetch(`/devices/${sessionId}`, {
                    method: 'DELETE',
                    headers: getAuthHeaders({ 'Accept': 'application/json' })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert(data.message || 'ডিভাইসটি সফলভাবে লগআউট করা হয়েছে।');
                    loadSecurityDevices();
                } else {
                    alert(data.message || 'ডিভাইস লগআউট ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                console.error(err);
                alert('অনুরোধ ব্যর্থ হয়েছে।');
            }
        }

        async function logoutAllDevicesFromSecurityCenter() {
            if (!confirm('আপনি কি অন্যান্য সকল ডিভাইস থেকে লগআউট করতে চান?')) return;
            try {
                const res = await fetch('/devices/logout-all', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Accept': 'application/json' })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert(data.message || 'অন্যান্য সকল ডিভাইস থেকে সফলভাবে লগআউট সম্পন্ন হয়েছে।');
                    loadSecurityDevices();
                } else {
                    alert(data.message || 'লগআউট ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                console.error(err);
                alert('অনুরোধ ব্যর্থ হয়েছে।');
            }
        }

        function checkPasswordStrength(password) {
            const bar = document.getElementById('pwdStrengthBar');
            const text = document.getElementById('pwdStrengthText');
            if (!bar || !text) return;

            let score = 0;
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            if (!password) {
                bar.style.width = '0%';
                bar.style.background = '#ef4444';
                text.innerText = 'পাসওয়ার্ডে বড়-ছোট অক্ষর, সংখ্যা এবং বিশেষ চিহ্ন ব্যবহার করুন।';
                text.style.color = 'var(--fb-text-secondary)';
            } else if (score <= 1) {
                bar.style.width = '25%';
                bar.style.background = '#ef4444';
                text.innerText = 'খুব দুর্বল পাসওয়ার্ড। কমপক্ষে ৮ অক্ষরের মিশ্রণ ব্যবহার করুন।';
                text.style.color = '#ef4444';
            } else if (score <= 3) {
                bar.style.width = '60%';
                bar.style.background = '#f59e0b';
                text.innerText = 'মোটামুটি মানের পাসওয়ার্ড। বড়-ছোট অক্ষর ও চিহ্ন যোগ করুন।';
                text.style.color = '#d97706';
            } else {
                bar.style.width = '100%';
                bar.style.background = '#10b981';
                text.innerText = 'অসাধারণ! এটি একটি শক্তিশালী ও নিরাপদ পাসওয়ার্ড।';
                text.style.color = '#059669';
            }
        }

        async function submitChangePassword(e) {
            e.preventDefault();
            const btn = document.getElementById('secSavePasswordBtn');
            const current_password = document.getElementById('secCurrentPassword').value;
            const password = document.getElementById('secNewPassword').value;
            const password_confirmation = document.getElementById('secConfirmPassword').value;

            if (password !== password_confirmation) {
                alert('নতুন পাসওয়ার্ড এবং নিশ্চিতকরণ পাসওয়ার্ড মিলছে না!');
                return;
            }

            btn.disabled = true;
            btn.innerText = 'আপডেট হচ্ছে...';

            try {
                const res = await fetch('/api/v1/auth/password', {
                    method: 'PUT',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json', 'Accept': 'application/json' }),
                    body: JSON.stringify({ current_password, password, password_confirmation })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert(data.message || 'পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে।');
                    document.getElementById('secPasswordForm').reset();
                    checkPasswordStrength('');
                    closeModal('securityModal');
                } else {
                    const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'পাসওয়ার্ড পরিবর্তন করা যায়নি।');
                    alert(errMsg);
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
            } finally {
                btn.disabled = false;
                btn.innerText = 'পাসওয়ার্ড আপডেট করুন';
            }
        }

        // ================= BLOCKING & RESTRICTIONS CENTER JS =================
        async function loadBlockedUsers() {
            const container = document.getElementById('blockedUsersContainer');
            if (!container) return;
            container.innerHTML = '<div style="text-align:center;padding:24px;color:var(--fb-text-secondary);font-size:13px;">ব্লক করা ব্যবহারকারীদের তালিকা লোড হচ্ছে...</div>';

            try {
                const res = await fetch('/api/v2/profile/blocked-users', {
                    headers: getAuthHeaders({ 'Accept': 'application/json' })
                });
                const data = await res.json();
                if (res.ok && data.success && data.data) {
                    const blocked = data.data;
                    if (blocked.length === 0) {
                        container.innerHTML = `
                            <div style="text-align:center;padding:32px 16px;">
                                <div style="width:48px;height:48px;border-radius:50%;background:var(--fb-hover);color:var(--fb-text-secondary);display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>
                                </div>
                                <div style="font-weight:700;font-size:14px;color:var(--fb-text-primary);">কোনো ব্লক করা ব্যবহারকারী নেই</div>
                                <div style="font-size:12px;color:var(--fb-text-secondary);margin-top:4px;">আপনার ব্লকলিস্ট একদম খালি।</div>
                            </div>
                        `;
                        return;
                    }

                    container.innerHTML = blocked.map(u => `
                        <div id="blocked-user-row-${u.id}" style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border:1px solid var(--fb-border);border-radius:var(--radius-md);background:var(--fb-bg);">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <img src="${u.avatar || '/images/default-avatar.svg'}" alt="${u.name}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                <div>
                                    <div style="font-weight:700;font-size:14px;color:var(--fb-text-primary);">${u.name}</div>
                                    <div style="font-size:12px;color:var(--fb-text-secondary);">${'@' + u.username}</div>
                                </div>
                            </div>
                            <button type="button" class="fb-btn fb-btn-secondary" onclick="unblockUserFromModal('${u.username}', ${u.id})" style="font-size:12px;padding:6px 14px;">
                                আনব্লক
                            </button>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div style="text-align:center;padding:24px;color:var(--fb-text-secondary);font-size:13px;">ব্লকলিস্ট লোড করা যায়নি।</div>';
                }
            } catch (err) {
                console.error(err);
                container.innerHTML = '<div style="text-align:center;padding:24px;color:var(--fb-text-secondary);font-size:13px;">সার্ভার যোগাযোগে সমস্যা হয়েছে।</div>';
            }
        }

        async function unblockUserFromModal(username, userId) {
            if (!confirm(`আপনি কি @${username} কে আনব্লক করতে চান?`)) return;
            try {
                const res = await fetch(`/api/v2/profile/${username}/unblock`, {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Accept': 'application/json' })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert(data.message || 'ব্যবহারকারীকে আনব্লক করা হয়েছে।');
                    const row = document.getElementById(`blocked-user-row-${userId}`);
                    if (row) {
                        row.remove();
                    }
                    const container = document.getElementById('blockedUsersContainer');
                    if (container && container.children.length === 0) {
                        loadBlockedUsers();
                    }
                } else {
                    alert(data.message || 'আনব্লক করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('অনুরোধ ব্যর্থ হয়েছে।');
            }
        }

        // Comments & Share Handlers
        async function toggleCommentsSection(postId) {
            const container = document.getElementById(`comments-container-${postId}`);
            if (!container) return;
            if (container.style.display === 'none') {
                container.style.display = 'block';
                loadPostComments(postId);
            } else {
                container.style.display = 'none';
            }
        }

        async function loadPostComments(postId) {
            const list = document.getElementById(`comments-list-${postId}`);
            if (!list) return;
            list.innerHTML = '<div style="font-size:12px;color:var(--fb-text-secondary);padding:6px 0;">মন্তব্য লোড হচ্ছে...</div>';
            try {
                const res = await fetch(`/api/v1/posts/${postId}/comments`, {
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                const comments = data.data || [];
                const countBadge = document.getElementById(`comments-count-${postId}`);
                if (countBadge && data.meta && data.meta.total !== undefined) {
                    countBadge.innerText = data.meta.total;
                } else if (countBadge && Array.isArray(comments)) {
                    countBadge.innerText = comments.length;
                }

                if (res.ok && Array.isArray(comments) && comments.length > 0) {
                    list.innerHTML = comments.map(c => {
                        const authorName = escapeHtml(c.author?.name || c.user?.name || 'ব্যবহারকারী');
                        const commentText = escapeHtml(c.body || c.content || '');
                        const avatarUrl = c.author?.avatar_url || c.user?.profile?.avatar_url || '/images/default-avatar.svg';
                        return `
                            <div style="display:flex;gap:10px;align-items:flex-start;">
                                <img src="${avatarUrl}" onerror="this.onerror=null;this.src='/images/default-avatar.svg';" style="width:28px;height:28px;border-radius:50%;object-fit:cover;margin-top:2px;">
                                <div style="background:var(--fb-hover);padding:8px 12px;border-radius:14px;font-size:13px;flex:1;">
                                    <div style="font-weight:600;color:var(--fb-text-primary);margin-bottom:2px;">${authorName}</div>
                                    <div style="color:var(--fb-text-primary);line-height:1.4;">${commentText}</div>
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    list.innerHTML = '<div style="font-size:12px;color:var(--fb-text-secondary);padding:6px 0;">এখনও কোনো মন্তব্য নেই। প্রথম মন্তব্যটি লিখুন!</div>';
                }
            } catch (e) {
                list.innerHTML = '<div style="font-size:12px;color:var(--fb-text-secondary);">মন্তব্য লোড করা যায়নি।</div>';
            }
        }

        async function submitPostComment(e, postId) {
            e.preventDefault();
            const input = document.getElementById(`comment-input-${postId}`);
            const content = input.value.trim();
            if (!content) return;

            try {
                const res = await fetch(`/api/v1/posts/${postId}/comments`, {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ body: content, content: content })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success' || data.data)) {
                    input.value = '';
                    const countBadge = document.getElementById(`comments-count-${postId}`);
                    if (countBadge) {
                        const current = parseInt(countBadge.innerText) || 0;
                        countBadge.innerText = current + 1;
                    }
                    loadPostComments(postId);
                } else {
                    alert(data.message || 'মন্তব্য প্রকাশ করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('মন্তব্য পাঠাতে সমস্যা হয়েছে।');
            }
        }

        function sharePost(postId) {
            const targetIdInput = document.getElementById('shareTargetPostId');
            if (targetIdInput) {
                targetIdInput.value = postId;
                const captionInput = document.getElementById('sharePostCaption');
                if (captionInput) captionInput.value = '';
                const modal = document.getElementById('socialShareModal');
                if (modal) modal.classList.add('active');
            } else {
                copyPostDirectLinkById(postId);
            }
        }

        async function executeSharePostToFeed() {
            const postId = document.getElementById('shareTargetPostId')?.value;
            if (!postId) return;
            const caption = document.getElementById('sharePostCaption')?.value.trim() || '';
            const btn = document.getElementById('confirmShareFeedBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'শেয়ার হচ্ছে...';
            }

            try {
                const res = await fetch(`/api/v1/posts/${postId}/share`, {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ caption: caption })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    const sharesBadge = document.getElementById(`shares-count-${postId}`);
                    if (sharesBadge && data.data && data.data.shares_count !== undefined) {
                        sharesBadge.innerText = data.data.shares_count;
                    }
                    closeModal('socialShareModal');
                    alert('আপনার টাইমলাইনে পোস্টটি সফলভাবে শেয়ার হয়েছে!');
                } else {
                    alert(data.message || 'পোস্ট শেয়ার করা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('যোগাযোগে সমস্যা হয়েছে।');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg> <span>আমার টাইমলাইনে শেয়ার করুন</span>';
                }
            }
        }

        function sharePostViaMessenger() {
            const postId = document.getElementById('shareTargetPostId')?.value;
            const postUrl = window.location.origin + '/posts/' + postId;
            closeModal('socialShareModal');
            window.location.href = `/messages?share_link=${encodeURIComponent(postUrl)}`;
        }

        function copyPostDirectLink() {
            const postId = document.getElementById('shareTargetPostId')?.value;
            copyPostDirectLinkById(postId);
            closeModal('socialShareModal');
        }

        function copyPostDirectLinkById(postId) {
            const postUrl = window.location.origin + '/posts/' + postId;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(postUrl).then(() => {
                    alert('পোস্টের লিংক কপি করা হয়েছে:\n' + postUrl);
                }).catch(() => {
                    prompt('পোস্ট লিংক:', postUrl);
                });
            } else {
                prompt('পোস্ট লিংক:', postUrl);
            }
        }

        function refreshAnalytics() {
            window.location.hash = 'analytics';
            window.location.reload();
        }

        /* ------------------------------------------------------------- */
        /* ADVANCED REAL-TIME AVATAR STUDIO & CIRCULAR CROP EDITOR       */
        /* ------------------------------------------------------------- */
        const AVATAR_MAX_BYTES = 10 * 1024 * 1024;
        const AVATAR_ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

        const avatarEditorState = {
            file: null,
            objectUrl: null,
            img: null,
            naturalWidth: 0,
            naturalHeight: 0,
            scale: 1.0,
            rotation: 0,
            posX: 0,
            posY: 0,
            uploading: false,
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,
            startPosX: 0,
            startPosY: 0
        };

        function switchAvatarTab(tab) {
            const isUpload = tab === 'upload';
            const isCurrent = tab === 'current';
            const isPresets = tab === 'presets';

            document.getElementById('avmTabUpload')?.classList.toggle('active', isUpload);
            document.getElementById('avmTabCurrent')?.classList.toggle('active', isCurrent);
            document.getElementById('avmTabPresets')?.classList.toggle('active', isPresets);

            document.getElementById('avmPaneUpload')?.classList.toggle('active', isUpload);
            document.getElementById('avmPaneCurrent')?.classList.toggle('active', isCurrent);
            document.getElementById('avmPanePresets')?.classList.toggle('active', isPresets);

            const uploadBtn = document.getElementById('avatarUploadBtn');
            if (uploadBtn) {
                uploadBtn.style.display = isUpload ? '' : 'none';
            }
        }

        function showAvatarError(msg) {
            const el = document.getElementById('avmError');
            if (!el) return;
            el.textContent = msg || '';
            el.style.display = msg ? 'block' : 'none';
        }

        function formatAvatarBytes(bytes) {
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }

        function setAvatarProgress(pct, label) {
            const wrap = document.getElementById('avmProgress');
            if (!wrap) return;
            if (pct === null) {
                wrap.style.display = 'none';
                return;
            }
            wrap.style.display = 'block';
            const fill = document.getElementById('avmProgressFill');
            const pctText = document.getElementById('avmProgressPct');
            const lblText = document.getElementById('avmProgressLabel');
            if (fill) fill.style.width = pct + '%';
            if (pctText) pctText.textContent = pct + '%';
            if (lblText && label) lblText.textContent = label;
        }

        function resetAvatarSelection() {
            if (avatarEditorState.uploading) return;
            if (avatarEditorState.objectUrl) {
                try { URL.revokeObjectURL(avatarEditorState.objectUrl); } catch (_) {}
            }
            avatarEditorState.file = null;
            avatarEditorState.objectUrl = null;
            avatarEditorState.img = null;
            avatarEditorState.naturalWidth = 0;
            avatarEditorState.naturalHeight = 0;
            avatarEditorState.scale = 1.0;
            avatarEditorState.rotation = 0;
            avatarEditorState.posX = 0;
            avatarEditorState.posY = 0;

            const fileInput = document.getElementById('avatarFileInput');
            if (fileInput) fileInput.value = '';
            const camInput = document.getElementById('avatarCameraInput');
            if (camInput) camInput.value = '';

            const dropzone = document.getElementById('avmDropzone');
            const editorWrap = document.getElementById('avmEditorWrap');
            if (dropzone) dropzone.style.display = '';
            if (editorWrap) editorWrap.style.display = 'none';

            const stageImg = document.getElementById('avmStageImg');
            if (stageImg) stageImg.removeAttribute('src');

            const slider = document.getElementById('avmZoomSlider');
            if (slider) slider.value = 100;
            const badge = document.getElementById('avmZoomBadge');
            if (badge) badge.textContent = '100%';

            setAvatarProgress(null);
            showAvatarError('');
        }

        function clampAndApplyAvatarTransform() {
            const img = document.getElementById('avmStageImg');
            if (!img || !avatarEditorState.file || !avatarEditorState.naturalWidth) return;

            const vp = document.getElementById('avmCircleViewport');
            const D = vp ? (vp.clientWidth || 230) : 230;

            const nw = avatarEditorState.naturalWidth;
            const nh = avatarEditorState.naturalHeight;
            const baseFit = Math.max(D / nw, D / nh);

            const scale = avatarEditorState.scale;
            const curW = nw * baseFit * scale;
            const curH = nh * baseFit * scale;

            const isRotated90 = (avatarEditorState.rotation % 180 !== 0);
            const effW = isRotated90 ? curH : curW;
            const effH = isRotated90 ? curW : curH;

            const maxDeltaX = Math.max(0, (effW - D) / 2);
            const maxDeltaY = Math.max(0, (effH - D) / 2);

            avatarEditorState.posX = Math.max(-maxDeltaX, Math.min(maxDeltaX, avatarEditorState.posX));
            avatarEditorState.posY = Math.max(-maxDeltaY, Math.min(maxDeltaY, avatarEditorState.posY));

            img.style.width = (nw * baseFit) + 'px';
            img.style.height = (nh * baseFit) + 'px';
            img.style.transform = `translate(calc(-50% + ${avatarEditorState.posX}px), calc(-50% + ${avatarEditorState.posY}px)) rotate(${avatarEditorState.rotation}deg) scale(${scale})`;
        }

        function setAvatarZoomFromSlider(val) {
            const num = Math.max(100, Math.min(300, parseInt(val) || 100));
            avatarEditorState.scale = num / 100;
            const badge = document.getElementById('avmZoomBadge');
            if (badge) badge.textContent = num + '%';
            clampAndApplyAvatarTransform();
        }

        function adjustAvatarZoom(delta) {
            const newScale = Math.max(1.0, Math.min(3.0, avatarEditorState.scale + delta));
            avatarEditorState.scale = Math.round(newScale * 100) / 100;
            const slider = document.getElementById('avmZoomSlider');
            if (slider) slider.value = Math.round(avatarEditorState.scale * 100);
            const badge = document.getElementById('avmZoomBadge');
            if (badge) badge.textContent = Math.round(avatarEditorState.scale * 100) + '%';
            clampAndApplyAvatarTransform();
        }

        function rotateAvatarStage() {
            avatarEditorState.rotation = (avatarEditorState.rotation + 90) % 360;
            clampAndApplyAvatarTransform();
        }

        function resetAvatarTransform() {
            avatarEditorState.scale = 1.0;
            avatarEditorState.rotation = 0;
            avatarEditorState.posX = 0;
            avatarEditorState.posY = 0;
            const slider = document.getElementById('avmZoomSlider');
            if (slider) slider.value = 100;
            const badge = document.getElementById('avmZoomBadge');
            if (badge) badge.textContent = '100%';
            clampAndApplyAvatarTransform();
        }

        async function handleAvatarFile(file) {
            showAvatarError('');
            if (!file) return;

            if (!AVATAR_ALLOWED_TYPES.includes(file.type)) {
                showAvatarError('শুধুমাত্র JPG, PNG অথবা WebP ফরম্যাটের ছবি গ্রহণযোগ্য।');
                return;
            }
            if (file.size > AVATAR_MAX_BYTES) {
                showAvatarError(`ফাইল সাইজ অনেক বড় (${formatAvatarBytes(file.size)})। সর্বোচ্চ 10MB অনুমোদিত।`);
                return;
            }

            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = function () {
                if (img.naturalWidth < 100 || img.naturalHeight < 100) {
                    showAvatarError('ছবির মাপ সর্বনিম্ন ১০০x১০০ পিক্সেল হতে হবে।');
                    URL.revokeObjectURL(url);
                    return;
                }

                avatarEditorState.file = file;
                avatarEditorState.objectUrl = url;
                avatarEditorState.img = img;
                avatarEditorState.naturalWidth = img.naturalWidth;
                avatarEditorState.naturalHeight = img.naturalHeight;
                avatarEditorState.scale = 1.0;
                avatarEditorState.rotation = 0;
                avatarEditorState.posX = 0;
                avatarEditorState.posY = 0;

                const dropzone = document.getElementById('avmDropzone');
                const editorWrap = document.getElementById('avmEditorWrap');
                if (dropzone) dropzone.style.display = 'none';
                if (editorWrap) editorWrap.style.display = 'block';

                const stageImg = document.getElementById('avmStageImg');
                if (stageImg) {
                    stageImg.src = url;
                }

                const fileNameEl = document.getElementById('avmFileName');
                const fileSizeEl = document.getElementById('avmFileSize');
                if (fileNameEl) fileNameEl.textContent = file.name;
                if (fileSizeEl) fileSizeEl.textContent = `${formatAvatarBytes(file.size)} • ${img.naturalWidth}×${img.naturalHeight}px`;

                const miniThumb = document.getElementById('avmMiniAvatarThumb');
                if (miniThumb) miniThumb.src = url;

                const slider = document.getElementById('avmZoomSlider');
                if (slider) slider.value = 100;
                const badge = document.getElementById('avmZoomBadge');
                if (badge) badge.textContent = '100%';

                clampAndApplyAvatarTransform();
            };
            img.onerror = function () {
                showAvatarError('ছবিটি লোড করা সম্ভব হয়নি। অনুগ্রহ করে অন্য ছবি নির্বাচন করুন।');
                URL.revokeObjectURL(url);
            };
            img.src = url;
        }

        function handleAvatarFileSelect(event) {
            const file = event.target.files && event.target.files[0];
            if (file) handleAvatarFile(file);
        }

        // Setup Drag & Drop and Pan pointer listeners
        document.addEventListener('DOMContentLoaded', function () {
            const dropzone = document.getElementById('avmDropzone');
            if (dropzone) {
                ['dragenter', 'dragover'].forEach(name => {
                    dropzone.addEventListener(name, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.add('dragover');
                    });
                });
                ['dragleave', 'drop'].forEach(name => {
                    dropzone.addEventListener(name, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('dragover');
                    });
                });
                dropzone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    if (dt && dt.files && dt.files[0]) {
                        handleAvatarFile(dt.files[0]);
                    }
                });
            }

            // Global paste support when avatarModal is open
            window.addEventListener('paste', (e) => {
                const modal = document.getElementById('avatarModal');
                if (!modal || !modal.classList.contains('active')) return;
                const items = e.clipboardData && e.clipboardData.items;
                if (!items) return;
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('image') !== -1) {
                        const file = items[i].getAsFile();
                        if (file) {
                            switchAvatarTab('upload');
                            handleAvatarFile(file);
                            break;
                        }
                    }
                }
            });

            // Pointer drag on Circular Viewport
            const vp = document.getElementById('avmCircleViewport');
            if (vp) {
                vp.addEventListener('pointerdown', (e) => {
                    if (!avatarEditorState.file) return;
                    avatarEditorState.isDragging = true;
                    avatarEditorState.dragStartX = e.clientX;
                    avatarEditorState.dragStartY = e.clientY;
                    avatarEditorState.startPosX = avatarEditorState.posX;
                    avatarEditorState.startPosY = avatarEditorState.posY;
                    vp.classList.add('grabbing');
                    try { vp.setPointerCapture(e.pointerId); } catch (_) {}
                });

                vp.addEventListener('pointermove', (e) => {
                    if (!avatarEditorState.isDragging) return;
                    const dx = e.clientX - avatarEditorState.dragStartX;
                    const dy = e.clientY - avatarEditorState.dragStartY;
                    avatarEditorState.posX = avatarEditorState.startPosX + dx;
                    avatarEditorState.posY = avatarEditorState.startPosY + dy;
                    clampAndApplyAvatarTransform();
                });

                const endDrag = (e) => {
                    if (!avatarEditorState.isDragging) return;
                    avatarEditorState.isDragging = false;
                    vp.classList.remove('grabbing');
                    try { vp.releasePointerCapture(e.pointerId); } catch (_) {}
                };
                vp.addEventListener('pointerup', endDrag);
                vp.addEventListener('pointercancel', endDrag);

                // Mouse wheel zoom
                vp.addEventListener('wheel', (e) => {
                    if (!avatarEditorState.file) return;
                    e.preventDefault();
                    const delta = e.deltaY < 0 ? 0.08 : -0.08;
                    adjustAvatarZoom(delta);
                }, { passive: false });
            }
        });

        // Generate high-definition cropped canvas blob
        function getAvatarCroppedBlob() {
            return new Promise((resolve) => {
                if (!avatarEditorState.file || !avatarEditorState.img) {
                    resolve(null);
                    return;
                }

                try {
                    const canvas = document.createElement('canvas');
                    const outputSize = 600; // High resolution 600x600 square
                    canvas.width = outputSize;
                    canvas.height = outputSize;
                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        resolve(avatarEditorState.file);
                        return;
                    }

                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, outputSize, outputSize);

                    const vp = document.getElementById('avmCircleViewport');
                    const D = vp ? (vp.clientWidth || 230) : 230;
                    const scaleRatio = outputSize / D;

                    ctx.save();
                    ctx.translate(outputSize / 2, outputSize / 2);
                    ctx.translate(avatarEditorState.posX * scaleRatio, avatarEditorState.posY * scaleRatio);
                    ctx.rotate((avatarEditorState.rotation * Math.PI) / 180);

                    const nw = avatarEditorState.naturalWidth;
                    const nh = avatarEditorState.naturalHeight;
                    const baseFit = Math.max(D / nw, D / nh);
                    const w = nw * baseFit * avatarEditorState.scale * scaleRatio;
                    const h = nh * baseFit * avatarEditorState.scale * scaleRatio;

                    ctx.drawImage(avatarEditorState.img, -w / 2, -h / 2, w, h);
                    ctx.restore();

                    const mime = avatarEditorState.file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                    canvas.toBlob((blob) => {
                        if (blob) {
                            const name = (avatarEditorState.file.name || 'avatar.jpg').replace(/\.[^/.]+$/, '') + (mime === 'image/png' ? '.png' : '.jpg');
                            const file = new File([blob], name, { type: mime });
                            resolve(file);
                        } else {
                            resolve(avatarEditorState.file);
                        }
                    }, mime, 0.92);
                } catch (err) {
                    console.error('Canvas crop error, fallback to raw file:', err);
                    resolve(avatarEditorState.file);
                }
            });
        }

        // Apply System Default Avatar
        async function applySystemAvatar(type) {
            if (!confirm('আপনি কি ডিফল্ট সিস্টেম অবতার ব্যবহার করতে চান?')) return;
            try {
                const res = await fetch('/api/v2/profile/avatar/default', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'ডিফল্ট অবতার সেট করা হয়েছে!');
                    } else {
                        alert(data.message);
                    }
                    const avatarImg = document.getElementById('profileAvatarImg');
                    if (avatarImg) avatarImg.src = '/images/default-avatar.svg?v=' + Date.now();
                    closeAvatarModal();
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    alert(data.message || 'ডিফল্ট অবতার সেট করতে সমস্যা হয়েছে।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগ ত্রুটি।');
            }
        }

        /* ------------------------------------------------------------- */
        /* ADVANCED REAL-TIME COVER PHOTO EDITOR                          */
        /* ------------------------------------------------------------- */
        const COVER_MAX_BYTES = 10 * 1024 * 1024;
        const COVER_ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
        const coverEditorState = { file: null, objectUrl: null, posY: 50, uploading: false, dragBound: false };

        function switchCoverTab(tab) {
            const isUpload = tab === 'upload';
            document.getElementById('cvmTabUpload')?.classList.toggle('active', isUpload);
            document.getElementById('cvmTabGallery')?.classList.toggle('active', !isUpload);
            document.getElementById('cvmPaneUpload')?.classList.toggle('active', isUpload);
            document.getElementById('cvmPaneGallery')?.classList.toggle('active', !isUpload);
        }

        function showCoverError(msg) {
            const el = document.getElementById('cvmError');
            if (!el) return;
            el.textContent = msg || '';
            el.style.display = msg ? 'block' : 'none';
        }

        function formatCoverBytes(bytes) {
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }

        function resetCoverSelection() {
            if (coverEditorState.uploading) return;
            if (coverEditorState.objectUrl) URL.revokeObjectURL(coverEditorState.objectUrl);
            coverEditorState.file = null;
            coverEditorState.objectUrl = null;
            const input = document.getElementById('coverFileInput');
            if (input) input.value = '';
            const wrap = document.getElementById('cvmPreviewWrap');
            const drop = document.getElementById('cvmDropzone');
            if (wrap) wrap.style.display = 'none';
            if (drop) drop.style.display = '';
            const img = document.getElementById('coverPreviewImg');
            if (img) img.removeAttribute('src');
            const btn = document.getElementById('coverUploadBtn');
            if (btn) { btn.disabled = true; btn.innerHTML = 'সংরক্ষণ করুন'; }
            setCoverProgress(null);
            showCoverError('');
            setCoverPreviewPos(typeof coverInitialPosY !== 'undefined' ? coverInitialPosY : 50);
        }

        function closeCoverModal() {
            if (coverEditorState.uploading) {
                showToast('আপলোড চলছে, অনুগ্রহ করে অপেক্ষা করুন...');
                return;
            }
            closeModal('coverModal');
            resetCoverSelection();
        }

        function setCoverPreviewPos(val) {
            const pos = Math.max(0, Math.min(100, Math.round(Number(val) || 0)));
            coverEditorState.posY = pos;
            const img = document.getElementById('coverPreviewImg');
            if (img) img.style.objectPosition = `center ${pos}%`;
            const slider = document.getElementById('cvmPosSlider');
            if (slider && Number(slider.value) !== pos) slider.value = pos;
            const lbl = document.getElementById('cvmPosLabel');
            if (lbl) lbl.textContent = pos + '%';
        }

        function setCoverProgress(pct, label) {
            const wrap = document.getElementById('cvmProgress');
            if (!wrap) return;
            if (pct === null) { wrap.style.display = 'none'; return; }
            wrap.style.display = 'block';
            document.getElementById('cvmProgressFill').style.width = pct + '%';
            document.getElementById('cvmProgressPct').textContent = pct + '%';
            if (label) document.getElementById('cvmProgressLabel').textContent = label;
        }

        function readImageDimensions(url) {
            return new Promise((resolve, reject) => {
                const probe = new Image();
                probe.onload = () => resolve({ width: probe.naturalWidth, height: probe.naturalHeight });
                probe.onerror = () => reject(new Error('invalid image'));
                probe.src = url;
            });
        }

        async function handleCoverFile(file) {
            showCoverError('');
            if (!file) return;
            if (!COVER_ALLOWED_TYPES.includes(file.type)) {
                showCoverError('শুধুমাত্র JPG, PNG অথবা WebP ফরম্যাটের ছবি গ্রহণযোগ্য।');
                return;
            }
            if (file.size > COVER_MAX_BYTES) {
                showCoverError(`ফাইলটি অনেক বড় (${formatCoverBytes(file.size)})। সর্বোচ্চ 10MB অনুমোদিত।`);
                return;
            }

            const url = URL.createObjectURL(file);
            let dims;
            try {
                dims = await readImageDimensions(url);
            } catch (e) {
                URL.revokeObjectURL(url);
                showCoverError('ফাইলটি একটি বৈধ ছবি নয় অথবা করাপ্টেড।');
                return;
            }
            if (dims.width < 400 || dims.height < 150) {
                URL.revokeObjectURL(url);
                showCoverError(`ছবিটি খুব ছোট (${dims.width}×${dims.height})। সর্বনিম্ন ৪০০×১৫০ পিক্সেল প্রয়োজন।`);
                return;
            }

            if (coverEditorState.objectUrl) URL.revokeObjectURL(coverEditorState.objectUrl);
            coverEditorState.file = file;
            coverEditorState.objectUrl = url;

            const img = document.getElementById('coverPreviewImg');
            img.src = url;
            document.getElementById('cvmDropzone').style.display = 'none';
            document.getElementById('cvmPreviewWrap').style.display = 'block';
            setCoverPreviewPos(50);

            const lowRes = dims.width < 820;
            document.getElementById('cvmFileInfo').innerHTML =
                `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                 <strong>${escapeChatHtml(file.name || 'clipboard-image')}</strong>
                 <span>• ${formatCoverBytes(file.size)}</span>
                 <span>• ${dims.width}×${dims.height}px</span>
                 ${lowRes ? '<span style="color:#d97706;font-weight:600;">• কম রেজোলিউশন, ঝাপসা দেখাতে পারে</span>' : '<span style="color:#059669;font-weight:600;">• চমৎকার মান ✓</span>'}`;

            const btn = document.getElementById('coverUploadBtn');
            btn.disabled = false;
            btn.innerHTML = 'সংরক্ষণ করুন';
            switchCoverTab('upload');
            bindCoverStageDrag();
        }

        function previewCover(event) {
            const file = event.target.files && event.target.files[0];
            handleCoverFile(file);
        }

        async function selectCoverFromGallery(url) {
            if (!url || coverEditorState.uploading) return;
            showCoverError('');
            switchCoverTab('upload');
            setCoverProgress(0, 'ছবি লোড হচ্ছে...');
            try {
                const res = await fetch(url, { credentials: 'same-origin' });
                if (!res.ok) throw new Error('fetch failed');
                const blob = await res.blob();
                setCoverProgress(null);
                const type = COVER_ALLOWED_TYPES.includes(blob.type) ? blob.type : 'image/jpeg';
                const ext = type.split('/')[1].replace('jpeg', 'jpg');
                const name = (url.split('/').pop() || 'cover').split('?')[0] || ('cover.' + ext);
                await handleCoverFile(new File([blob], name, { type }));
            } catch (e) {
                console.error(e);
                setCoverProgress(null);
                showCoverError('ছবিটি লোড করা যায়নি। অনুগ্রহ করে অন্য একটি ছবি বেছে নিন।');
            }
        }

        function bindCoverStageDrag() {
            if (coverEditorState.dragBound) return;
            const stage = document.getElementById('cvmStage');
            if (!stage) return;
            coverEditorState.dragBound = true;
            let dragging = false;
            let startY = 0;
            let startPos = 50;

            stage.addEventListener('pointerdown', (e) => {
                if (e.target.closest('.cvm-chip-btn')) return;
                dragging = true;
                startY = e.clientY;
                startPos = coverEditorState.posY;
                stage.classList.add('grabbing');
                stage.setPointerCapture(e.pointerId);
            });
            stage.addEventListener('pointermove', (e) => {
                if (!dragging) return;
                const delta = e.clientY - startY;
                const h = stage.offsetHeight || 250;
                setCoverPreviewPos(startPos - (delta / h) * 100);
            });
            const stop = (e) => {
                dragging = false;
                stage.classList.remove('grabbing');
                if (e && e.pointerId !== undefined && stage.hasPointerCapture(e.pointerId)) {
                    stage.releasePointerCapture(e.pointerId);
                }
            };
            stage.addEventListener('pointerup', stop);
            stage.addEventListener('pointercancel', stop);
            stage.addEventListener('wheel', (e) => {
                e.preventDefault();
                setCoverPreviewPos(coverEditorState.posY + (e.deltaY > 0 ? 2 : -2));
            }, { passive: false });
        }

        /** Swap the profile banner to the given cover URL immediately (no page reload). */
        function applyCoverToBanner(url, posY) {
            const container = document.getElementById('coverContainer');
            if (!container) return;
            let img = document.getElementById('coverPhotoImg');
            if (!img) {
                document.getElementById('coverFallback')?.remove();
                img = document.createElement('img');
                img.id = 'coverPhotoImg';
                img.className = 'cover-photo-img';
                img.alt = 'Cover Photo';
                img.style.cursor = 'pointer';
                img.onclick = handleCoverClick;
                container.insertBefore(img, container.firstChild);
            }
            img.onerror = function () { this.onerror = null; this.src = '/images/default-cover.svg'; };
            img.style.objectPosition = `center ${posY}%`;
            img.classList.remove('cover-fade-in');
            void img.offsetWidth;
            img.src = url;
            img.classList.add('cover-fade-in');

            coverInitialPosY = posY;
            coverCurrentPosY = posY;
            toggleCoverOwnerControls(true);
        }

        /** Reset the profile banner to the default gradient immediately. */
        function removeCoverFromBanner() {
            const container = document.getElementById('coverContainer');
            const img = document.getElementById('coverPhotoImg');
            if (img) img.remove();
            if (container && !document.getElementById('coverFallback')) {
                const fb = document.createElement('div');
                fb.id = 'coverFallback';
                fb.style.cssText = 'width:100%;height:100%;background: var(--fb-cover-gradient);';
                container.insertBefore(fb, container.firstChild);
            }
            coverInitialPosY = 50;
            coverCurrentPosY = 50;
            toggleCoverOwnerControls(false);
        }

        function toggleCoverOwnerControls(hasCover) {
            document.querySelectorAll('.cover-menu-has-photo').forEach(el => { el.style.display = hasCover ? '' : 'none'; });
            const del = document.getElementById('coverDeleteBtn');
            if (del) del.style.display = hasCover ? '' : 'none';
        }

        function showCoverBannerOverlay(show, text) {
            const container = document.getElementById('coverContainer');
            if (!container) return;
            let ov = document.getElementById('coverUploadingOverlay');
            if (!show) { ov?.remove(); return; }
            if (!ov) {
                ov = document.createElement('div');
                ov.id = 'coverUploadingOverlay';
                ov.className = 'cover-uploading-overlay';
                container.appendChild(ov);
            }
            ov.innerHTML = `<span class="cvm-spinner"></span>${escapeChatHtml(text || 'আপডেট হচ্ছে...')}`;
        }

        // Drag & drop + paste wiring for the cover modal
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('coverModal');
            const drop = document.getElementById('cvmDropzone');
            if (!modal || !drop) return;

            ['dragenter', 'dragover'].forEach(evt => modal.addEventListener(evt, (e) => {
                if (!e.dataTransfer || !Array.from(e.dataTransfer.types || []).includes('Files')) return;
                e.preventDefault();
                drop.classList.add('dragover');
            }));
            ['dragleave', 'dragend'].forEach(evt => modal.addEventListener(evt, (e) => {
                if (e.target === modal || e.target === drop) drop.classList.remove('dragover');
            }));
            modal.addEventListener('drop', (e) => {
                if (!e.dataTransfer || !e.dataTransfer.files.length) return;
                e.preventDefault();
                drop.classList.remove('dragover');
                if (!coverEditorState.uploading) handleCoverFile(e.dataTransfer.files[0]);
            });

            document.addEventListener('paste', (e) => {
                if (!modal.classList.contains('active') || coverEditorState.uploading) return;
                const item = Array.from(e.clipboardData?.items || []).find(i => i.type.startsWith('image/'));
                if (!item) return;
                e.preventDefault();
                const blob = item.getAsFile();
                if (blob) handleCoverFile(new File([blob], 'clipboard-cover.' + (blob.type.split('/')[1] || 'png'), { type: blob.type }));
            });

            document.addEventListener('keydown', (e) => {
                if (!modal.classList.contains('active')) return;
                if (e.key === 'Escape') { closeCoverModal(); return; }
                if (!coverEditorState.file || ['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) return;
                if (e.key === 'ArrowUp') { e.preventDefault(); setCoverPreviewPos(coverEditorState.posY - 2); }
                if (e.key === 'ArrowDown') { e.preventDefault(); setCoverPreviewPos(coverEditorState.posY + 2); }
            });
        });

        function openProfileLockModal() {
            closeAllProfileDropdowns();
            document.getElementById('profileLockModal').classList.add('active');
        }

        // Lock Toggle Handler
        async function handleLockToggle() {
            const btn = document.getElementById('lockToggleBtn');
            btn.disabled = true;
            btn.innerText = 'অপেক্ষা করুন...';

            try {
                const res = await fetch('/api/v2/profile/lock', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'ব্যর্থ হয়েছে। পুনরায় চেষ্টা করুন।');
                    btn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।');
                btn.disabled = false;
            }
        }

        // Avatar Context Menu and Guard
        function toggleAvatarMenu(e) {
            if (e) e.stopPropagation();
            const dd = document.getElementById('avatarMenuDropdown');
            if (dd) {
                dd.classList.toggle('show');
            }
        }

        function closeAvatarMenu() {
            const dd = document.getElementById('avatarMenuDropdown');
            if (dd) {
                dd.classList.remove('show');
            }
        }

        function handleAvatarClick(e) {
            @if(!empty($hasActiveStory))
                openProfileStoriesModal(e);
            @elseif($isOwner)
                toggleAvatarMenu(e);
            @else
                openPhotoTheater('{{ $profile['avatar'] ?? '' }}', '{{ addslashes($profile['name']) }}', 'প্রোফাইল ছবি', 'সম্প্রতি', null, {{ !empty($profile['has_avatar_guard']) ? 'true' : 'false' }});
            @endif
        }

        function handleShieldClick(e) {
            if (e) e.stopPropagation();
            @if($isOwner)
                toggleAvatarMenu(e);
            @else
                alert('🛡️ প্রোফাইল পিকচার গার্ড সক্রিয় রয়েছে। এই ছবি সম্পূর্ণ সুরক্ষিত।');
            @endif
        }

        // Close avatar menu on document click
        document.addEventListener('click', function(e) {
            const dd = document.getElementById('avatarMenuDropdown');
            if (dd && !e.target.closest('.avatar-wrapper')) {
                dd.classList.remove('show');
            }
        });

        async function handleToggleAvatarGuard() {
            const menuDropdown = document.getElementById('avatarMenuDropdown');
            if (menuDropdown) menuDropdown.classList.remove('show');

            try {
                const res = await fetch('/api/v1/profile/avatar/guard', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'গার্ড আপডেট ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগ সমস্যা। পুনরায় চেষ্টা করুন।');
            }
        }

        // Photo Theater / Lightbox Modal
        let theaterIsProtected = false;
        let theaterCurrentPostId = null;

        function openPhotoTheater(imageUrl, authorName, caption, dateStr, postId = null, isProtected = false) {
            if (!imageUrl) return;
            const modal = document.getElementById('photoTheaterModal');
            const img = document.getElementById('theaterMainImg');
            const author = document.getElementById('theaterAuthorName');
            const date = document.getElementById('theaterMetaDate');
            const cap = document.getElementById('theaterCaption');
            const guardNotice = document.getElementById('theaterGuardNotice');
            const shareBtn = document.getElementById('theaterShareBtn');

            theaterIsProtected = !!isProtected;
            theaterCurrentPostId = postId;

            if (img) img.src = normalizeMediaUrl(imageUrl);
            if (author) author.innerText = authorName || 'ব্যবহারকারী';
            if (date) date.innerText = dateStr || 'সম্প্রতি';
            if (cap) {
                cap.innerText = caption || '';
                cap.style.display = caption ? 'block' : 'none';
            }
            if (guardNotice) {
                guardNotice.style.display = theaterIsProtected ? 'flex' : 'none';
            }
            if (shareBtn) {
                shareBtn.style.display = theaterIsProtected ? 'none' : 'flex';
            }

            if (modal) modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePhotoTheater() {
            const modal = document.getElementById('photoTheaterModal');
            if (modal) modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function handleTheaterBackdropClick(e) {
            if (e.target.id === 'photoTheaterModal' || e.target.id === 'theaterStage') {
                closePhotoTheater();
            }
        }

        function theaterLike() {
            alert('ছবিটিতে লাইক দেয়া হয়েছে!');
        }

        function theaterCommentFocus() {
            alert('মন্তব্য করতে পোস্টের বিস্তারিত পাতায় যান।');
        }

        function theaterShare() {
            if (theaterIsProtected) {
                alert('🛡️ এই ছবিটিতে প্রোফাইল পিকচার গার্ড থাকায় শেয়ার করা যাবে না।');
                return;
            }
            const img = document.getElementById('theaterMainImg');
            if (navigator.clipboard && img && img.src) {
                navigator.clipboard.writeText(img.src).then(() => {
                    alert('ছবির লিংক কপি করা হয়েছে!');
                }).catch(() => {
                    prompt('ছবির লিংক কপি করুন:', img.src);
                });
            } else {
                alert('শেয়ার করার জন্য প্রস্তুত!');
            }
        }

        // Video Theater / Lightbox Modal
        let videoTheaterCurrentPostId = null;

        function openVideoTheater(videoUrl, authorName, caption, dateStr, postId = null) {
            if (!videoUrl) return;
            const modal = document.getElementById('videoTheaterModal');
            const video = document.getElementById('theaterMainVideo');
            const author = document.getElementById('videoAuthorName');
            const date = document.getElementById('videoMetaDate');
            const cap = document.getElementById('videoCaption');

            videoTheaterCurrentPostId = postId;

            if (video) {
                video.src = videoUrl;
                video.play().catch(() => {});
            }
            if (author) author.innerText = authorName || 'ব্যবহারকারী';
            if (date) date.innerText = dateStr || 'ভিডিও • সম্প্রতি';
            if (cap) {
                cap.innerText = caption || '';
                cap.style.display = caption ? 'block' : 'none';
            }

            if (modal) modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeVideoTheater() {
            const modal = document.getElementById('videoTheaterModal');
            const video = document.getElementById('theaterMainVideo');
            if (video) {
                video.pause();
                video.src = '';
            }
            if (modal) modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function handleVideoTheaterBackdropClick(e) {
            if (e.target.id === 'videoTheaterModal' || e.target.id === 'videoTheaterStage') {
                closeVideoTheater();
            }
        }

        function videoShare() {
            const video = document.getElementById('theaterMainVideo');
            if (navigator.clipboard && video && video.src) {
                navigator.clipboard.writeText(video.src).then(() => {
                    alert('ভিডিওর লিংক কপি করা হয়েছে!');
                }).catch(() => {
                    prompt('ভিডিও লিংক কপি করুন:', video.src);
                });
            } else {
                alert('ভিডিও শেয়ার করার জন্য প্রস্তুত!');
            }
        }

        // Global Esc Key for Photo & Video Theaters
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePhotoTheater();
                closeVideoTheater();
            }
        });

        // Modal Subtab Switcher
        function switchEditTab(tabKey, btn) {
            document.querySelectorAll('.modal-subtab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.edit-tab-panel').forEach(p => p.classList.remove('active'));

            if (btn) btn.classList.add('active');
            const target = document.getElementById(`editPanel-${tabKey}`);
            if (target) target.classList.add('active');
        }

        // Edit Profile Submit
        async function submitEditProfile(e) {
            e.preventDefault();
            const btn = document.getElementById('saveProfileBtn');
            btn.disabled = true;
            btn.innerText = 'সংরক্ষণ হচ্ছে...';

            const form = document.getElementById('editProfileForm');
            const formData = new FormData(form);

            const payload = {
                name: formData.get('name') || null,
                display_name: formData.get('display_name') || null,
                slug: formData.get('slug') || null,
                gender: formData.get('gender') || null,
                birth_date: formData.get('birth_date') || null,
                relationship_status: formData.get('relationship_status') || null,
                bio: formData.get('bio') || null,
                about: formData.get('about') || null,
                website: formData.get('website') || null,
                city: formData.get('city') || null,
                location: formData.get('city') || null,
                hometown: formData.get('hometown') || null,
                work: formData.get('work') || null,
                education: formData.get('education') || null,
                middle_name: formData.get('middle_name') || null,
                pronouns: formData.get('pronouns') || null,
                category: formData.get('category') || null,
                religion: formData.get('religion') || null,
                blood_group: formData.get('blood_group') || null,
                division: formData.get('division') || null,
                district: formData.get('district') || null,
                upazila: formData.get('upazila') || null,
                portfolio: formData.get('portfolio') || null,
                whatsapp: formData.get('whatsapp') || null,
                telegram: formData.get('telegram') || null,
                signal: formData.get('signal') || null,
                messenger: formData.get('messenger') || null,
                hobbies: formData.get('hobbies_input') ? formData.get('hobbies_input').split(',').map(s=>s.trim()).filter(s=>s.length) : null,
                favorite_music: formData.get('music_input') ? formData.get('music_input').split(',').map(s=>s.trim()).filter(s=>s.length) : null,
                favorite_books: formData.get('books_input') ? formData.get('books_input').split(',').map(s=>s.trim()).filter(s=>s.length) : null,
                favorite_movies: formData.get('movies_input') ? formData.get('movies_input').split(',').map(s=>s.trim()).filter(s=>s.length) : null,
            };

            // Skills
            const rawSkills = formData.get('skills_input');
            if (rawSkills && rawSkills.trim()) {
                payload.skills = rawSkills.split(',')
                    .map(s => s.trim())
                    .filter(s => s.length > 0)
                    .map(s => ({ name: s, level: 'intermediate' }));
            }

            // Interests
            const rawInterests = formData.get('interests_input');
            if (rawInterests && rawInterests.trim()) {
                payload.interests = rawInterests.split(',')
                    .map(s => s.trim())
                    .filter(s => s.length > 0)
                    .map(s => ({ name: s }));
            }

            // Languages
            const rawLanguages = formData.get('languages_input');
            if (rawLanguages && rawLanguages.trim()) {
                payload.languages = rawLanguages.split(',')
                    .map(s => s.trim())
                    .filter(s => s.length > 0)
                    .map(s => ({ language: s, proficiency: 'conversational' }));
            }

            // Social Links
            const socialLinks = [];
            const platforms = ['facebook', 'twitter', 'github', 'linkedin', 'instagram', 'youtube'];
            platforms.forEach(p => {
                const url = formData.get(`social_${p}`);
                if (url && url.trim()) {
                    socialLinks.push({
                        platform: p,
                        url: url.trim(),
                        is_visible: true
                    });
                }
            });
            if (socialLinks.length > 0) {
                payload.social_links = socialLinks;
            }

            try {
                const res = await fetch('/api/v2/profile', {
                    method: 'PUT',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert(data.message || 'প্রোফাইল সফলভাবে আপডেট করা হয়েছে।');
                    window.location.reload();
                } else {
                    let errMsg = data.message || 'তথ্য আপডেট করা যায়নি।';
                    if (data.errors) {
                        const details = Object.values(data.errors).flat().join('\n• ');
                        errMsg += '\n• ' + details;
                    }
                    alert(errMsg);
                    btn.disabled = false;
                    btn.innerText = 'সংরক্ষণ করুন';
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগ ত্রুটি। অনুগ্রহ করে কিছুক্ষণ পর আবার চেষ্টা করুন।');
                btn.disabled = false;
                btn.innerText = 'সংরক্ষণ করুন';
            }
        }

        // Avatar Upload Submit (High-Definition Canvas Cropped, Real-time XHR Progress)
        async function submitAvatarUpload(e) {
            e.preventDefault();
            if (avatarEditorState.uploading) return;

            if (!avatarEditorState.file) {
                showAvatarError('অনুগ্রহ করে একটি ছবি নির্বাচন করুন।');
                switchAvatarTab('upload');
                return;
            }

            const btn = document.getElementById('avatarUploadBtn');
            avatarEditorState.uploading = true;
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="avm-spinner"></span>আপলোড হচ্ছে...';
            }
            showAvatarError('');
            setAvatarProgress(0, 'ছবি প্রসেসিং হচ্ছে...');

            try {
                // Get precisely cropped client-side canvas file
                const croppedFile = await getAvatarCroppedBlob();
                const fileToUpload = croppedFile || avatarEditorState.file;

                const formData = new FormData();
                formData.append('file', fileToUpload);

                const caption = document.getElementById('avatarCaptionInput')?.value?.trim();
                if (caption) {
                    formData.append('caption', caption);
                }

                setAvatarProgress(20, 'আপলোড শুরু হচ্ছে...');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/api/v2/profile/avatar', true);
                xhr.withCredentials = true;

                const authHeaders = getAuthHeaders();
                for (const key in authHeaders) {
                    if (key.toLowerCase() !== 'content-type') {
                        xhr.setRequestHeader(key, authHeaders[key]);
                    }
                }

                xhr.upload.onprogress = function (event) {
                    if (event.lengthComputable) {
                        const pct = Math.min(95, Math.round((event.loaded / event.total) * 100));
                        setAvatarProgress(pct, pct >= 90 ? 'সার্ভারে সংরক্ষণ হচ্ছে...' : `আপলোড হচ্ছে (${pct}%)...`);
                    }
                };

                xhr.onload = function () {
                    avatarEditorState.uploading = false;
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = 'আপলোড ও সংরক্ষণ করুন ✨';
                    }

                    let data;
                    try {
                        data = JSON.parse(xhr.responseText);
                    } catch (parseErr) {
                        data = {};
                    }

                    if (xhr.status >= 200 && xhr.status < 300 && (data.success || data.status === 'success')) {
                        setAvatarProgress(100, 'সম্পন্ন হয়েছে ✓');
                        const newAvatarUrl = (data.data && data.data.avatar_url) ? (data.data.avatar_url + '?v=' + Date.now()) : '';
                        if (newAvatarUrl) {
                            const avatarImg = document.getElementById('profileAvatarImg');
                            if (avatarImg) avatarImg.src = newAvatarUrl;
                            const miniThumb = document.getElementById('avmMiniAvatarThumb');
                            if (miniThumb) miniThumb.src = newAvatarUrl;
                            const currPreview = document.getElementById('avmCurrentPreview');
                            if (currPreview) currPreview.src = newAvatarUrl;
                            document.querySelectorAll('.user-avatar, .nav-avatar, img.avatar-img').forEach(el => el.src = newAvatarUrl);
                        }

                        if (typeof showToast === 'function') {
                            showToast('✓ প্রোফাইল ছবি সফলভাবে আপডেট হয়েছে!');
                        } else {
                            alert(data.message || 'প্রোফাইল ছবি সফলভাবে আপডেট করা হয়েছে।');
                        }

                        closeAvatarModal();
                        setTimeout(() => window.location.reload(), 450);
                    } else {
                        setAvatarProgress(null);
                        const errorMsg = data.errors?.file?.[0]
                            || data.errors?.caption?.[0]
                            || data.message
                            || 'ছবি আপলোড সফল হয়নি। আবার চেষ্টা করুন।';
                        showAvatarError(errorMsg);
                        if (typeof showToast === 'function') showToast(errorMsg);
                    }
                };

                xhr.onerror = function () {
                    avatarEditorState.uploading = false;
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = 'আপলোড ও সংরক্ষণ করুন ✨';
                    }
                    setAvatarProgress(null);
                    showAvatarError('সার্ভার যোগাযোগ ত্রুটি। অনুগ্রহ করে ইন্টারনেট সংযোগ চেক করুন।');
                };

                xhr.send(formData);
            } catch (err) {
                console.error(err);
                avatarEditorState.uploading = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'আপলোড ও সংরক্ষণ করুন ✨';
                }
                setAvatarProgress(null);
                showAvatarError('ছবি প্রসেসিংয়ে সমস্যা হয়েছে: ' + err.message);
            }
        }

        // Delete Avatar Submit
        async function handleDeleteAvatar() {
            if (!confirm('আপনি কি নিশ্চিত যে বর্তমান প্রোফাইল ছবিটি মুছে ফেলতে চান?')) {
                return;
            }

            try {
                const res = await fetch('/api/v2/profile/avatar', {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'প্রোফাইল ছবি সফলভাবে মুছে ফেলা হয়েছে।');
                    } else {
                        alert(data.message);
                    }
                    const avatarImg = document.getElementById('profileAvatarImg');
                    if (avatarImg) avatarImg.src = '/images/default-avatar.svg?v=' + Date.now();
                    closeAvatarModal();
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    alert(data.message || 'ছবি মুছতে সমস্যা হয়েছে।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগ ত্রুটি।');
            }
        }

        // Real-Time Cover Upload Submit (XHR with live progress, real-time DOM banner update, no page reload)
        async function submitCoverUpload(e) {
            e.preventDefault();
            if (coverEditorState.uploading) return;

            const file = coverEditorState.file;
            if (!file) {
                showCoverError('অনুগ্রহ করে একটি ছবি নির্বাচন করুন।');
                switchCoverTab('upload');
                return;
            }

            const btn = document.getElementById('coverUploadBtn');
            const cancelBtn = document.getElementById('coverCancelBtn');
            coverEditorState.uploading = true;
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="cvm-spinner"></span>আপলোড হচ্ছে...';
            }
            if (cancelBtn) cancelBtn.disabled = true;
            showCoverError('');
            setCoverProgress(0, 'আপলোড শুরু হচ্ছে...');
            showCoverBannerOverlay(true, 'কভার ছবি আপডেট হচ্ছে...');

            const formData = new FormData();
            formData.append('file', file);
            const caption = document.getElementById('coverCaptionInput')?.value?.trim() || '';
            if (caption) {
                formData.append('caption', caption);
            }
            formData.append('cover_position_y', coverEditorState.posY);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/v2/profile/cover', true);
            xhr.withCredentials = true;

            const authHeaders = getAuthHeaders();
            for (const key in authHeaders) {
                if (key.toLowerCase() !== 'content-type') {
                    xhr.setRequestHeader(key, authHeaders[key]);
                }
            }

            xhr.upload.onprogress = function (event) {
                if (event.lengthComputable) {
                    const pct = Math.min(98, Math.round((event.loaded / event.total) * 100));
                    setCoverProgress(pct, pct >= 95 ? 'প্রসেসিং হচ্ছে...' : `আপলোড হচ্ছে (${pct}%)...`);
                }
            };

            xhr.onload = function () {
                coverEditorState.uploading = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'সংরক্ষণ করুন';
                }
                if (cancelBtn) cancelBtn.disabled = false;
                showCoverBannerOverlay(false);

                let data;
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (parseErr) {
                    data = {};
                }

                if (xhr.status >= 200 && xhr.status < 300 && (data.success || data.status === 'success')) {
                    setCoverProgress(100, 'সম্পন্ন হয়েছে ✓');
                    const coverUrl = (data.data && data.data.cover_url) ? (data.data.cover_url + '?v=' + Date.now()) : '';
                    if (coverUrl) {
                        applyCoverToBanner(coverUrl, coverEditorState.posY);
                    }
                    showToast('✓ কভার ছবি সফলভাবে আপডেট হয়েছে!');
                    closeModal('coverModal');
                    resetCoverSelection();
                } else {
                    setCoverProgress(null);
                    const errorMsg = data.errors?.file?.[0]
                        || data.errors?.caption?.[0]
                        || data.message
                        || 'কভার ছবি আপলোড ব্যর্থ হয়েছে। আবার চেষ্টা করুন।';
                    showCoverError(errorMsg);
                    showToast(errorMsg);
                }
            };

            xhr.onerror = function () {
                coverEditorState.uploading = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'সংরক্ষণ করুন';
                }
                if (cancelBtn) cancelBtn.disabled = false;
                showCoverBannerOverlay(false);
                setCoverProgress(null);
                showCoverError('সার্ভারের সাথে যোগাযোগে ত্রুটি ঘটেছে। ইন্টারনেট সংযোগ পরীক্ষা করুন।');
                showToast('সার্ভার যোগাযোগ ত্রুটি।');
            };

            xhr.send(formData);
        }

        // Real-Time Delete Cover Submit (No page reload)
        async function handleDeleteCover() {
            if (!confirm('আপনি কি নিশ্চিত যে বর্তমান কভার ছবিটি মুছে ফেলতে চান?')) {
                return;
            }

            const delBtn = document.getElementById('coverDeleteBtn');
            const origText = delBtn ? delBtn.innerText : '';
            if (delBtn) {
                delBtn.disabled = true;
                delBtn.innerText = 'মুছে ফেলা হচ্ছে...';
            }
            showCoverBannerOverlay(true, 'কভার ছবি মুছে ফেলা হচ্ছে...');

            try {
                const res = await fetch('/api/v2/profile/cover', {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' })
                });
                const data = await res.json();
                showCoverBannerOverlay(false);

                if (res.ok && (data.success || data.status === 'success')) {
                    removeCoverFromBanner();
                    closeModal('coverModal');
                    resetCoverSelection();
                    showToast('✓ কভার ছবি সফলভাবে মুছে ফেলা হয়েছে');
                } else {
                    if (delBtn) {
                        delBtn.disabled = false;
                        delBtn.innerText = origText;
                    }
                    showToast(data.message || 'কভার ছবি মুছতে সমস্যা হয়েছে।');
                }
            } catch (err) {
                console.error(err);
                showCoverBannerOverlay(false);
                if (delBtn) {
                    delBtn.disabled = false;
                    delBtn.innerText = origText;
                }
                showToast('সার্ভার যোগাযোগ ত্রুটি।');
            }
        }

        // Post Create Submit
        async function submitCreatePost(e) {
            e.preventDefault();
            const btn = document.getElementById('createPostBtn');
            btn.disabled = true;
            btn.innerText = 'পোস্ট হচ্ছে...';

            const form = document.getElementById('createPostForm');
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('/api/v1/posts', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('পোস্ট সফলভাবে তৈরি হয়েছে!');
                    window.location.reload();
                } else {
                    alert(data.message || 'পোস্ট প্রকাশ করা যায়নি।');
                    btn.disabled = false;
                    btn.innerText = 'পোস্ট করুন';
                }
            } catch (err) {
                console.error(err);
                alert('পোস্ট প্রকাশে ত্রুটি।');
                btn.disabled = false;
                btn.innerText = 'পোস্ট করুন';
            }
        }

        // Reaction Handler
        async function toggleReaction(postId) {
            try {
                const res = await fetch(`/api/v1/posts/${postId}/react`, {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ type: 'like' })
                });
                const data = await res.json();
                if (res.ok && data.data) {
                    const countBadge = document.getElementById(`reactions-count-${postId}`);
                    const newCount = data.data.total_reactions ?? data.data.reactions_count ?? data.data.likes_count;
                    if (countBadge && newCount !== undefined) {
                        countBadge.innerText = newCount;
                    }
                    const likeBtn = document.getElementById(`like-btn-${postId}`);
                    if (likeBtn) {
                        if (data.data.reacted) {
                            likeBtn.style.color = '#1877f2';
                            likeBtn.style.fontWeight = '600';
                            const svg = likeBtn.querySelector('svg');
                            if (svg) svg.style.fill = '#1877f2';
                        } else {
                            likeBtn.style.color = '';
                            likeBtn.style.fontWeight = '';
                            const svg = likeBtn.querySelector('svg');
                            if (svg) svg.style.fill = 'none';
                        }
                    }
                }
            } catch (err) {
                console.error(err);
            }
        }

        // Life Event Functions
        function openLifeEventModal() {
            document.getElementById('lifeEventModal').classList.add('active');
        }

        function selectLifeEventCat(cat, icon, defaultPlaceholder, el) {
            document.querySelectorAll('.life-event-cat-card').forEach(c => c.classList.remove('active'));
            if (el) el.classList.add('active');
            document.getElementById('lifeEventCatInput').value = cat;
            document.getElementById('lifeEventIconInput').value = icon;
            const titleInput = document.getElementById('lifeEventTitleInput');
            if (titleInput && (!titleInput.value || titleInput.value.trim() === '')) {
                titleInput.placeholder = `যেমন: ${defaultPlaceholder}`;
            }
        }

        async function submitLifeEvent(e) {
            e.preventDefault();
            const btn = document.getElementById('saveLifeEventBtn');
            btn.disabled = true;
            btn.innerText = 'মাইলস্টোন প্রকাশ হচ্ছে...';

            const form = document.getElementById('createLifeEventForm');
            const formData = new FormData(form);
            const cat = formData.get('life_event_cat') || 'work';
            const icon = formData.get('life_event_icon') || '🚩';
            const title = formData.get('life_event_title') || '';
            const location = formData.get('location') || '';
            const eventDate = formData.get('event_date') || '';
            const content = formData.get('content') || '';
            const audience = formData.get('audience') || 'public';

            const milestoneBadge = `${icon} ${title}${eventDate ? ' (' + eventDate + ')' : ''}`;

            const payload = {
                type: 'life_event',
                feeling_activity: milestoneBadge,
                location: location || null,
                content: content || title,
                audience: audience
            };

            try {
                const res = await fetch('/api/v1/posts', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('🚩 আপনার লাইফ ইভেন্ট সফলভাবে টাইমলাইনে প্রকাশিত হয়েছে!');
                    closeModal('lifeEventModal');
                    window.location.reload();
                } else {
                    alert(data.message || 'লাইফ ইভেন্ট প্রকাশ করা যায়নি।');
                    btn.disabled = false;
                    btn.innerText = '🚩 মাইলস্টোন প্রকাশ করুন';
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগ ত্রুটি।');
                btn.disabled = false;
                btn.innerText = '🚩 মাইলস্টোন প্রকাশ করুন';
            }
        }

        // Timeline Controls (Manage, Filters, View Switcher)
        function openManagePostsModal() {
            document.getElementById('managePostsModal').classList.add('active');
        }

        function toggleTimelineFilterBar() {
            const bar = document.getElementById('timelineFilterBar');
            const btn = document.getElementById('filterToggleBtn');
            if (bar.style.display === 'none' || !bar.style.display) {
                bar.style.display = 'block';
                if (btn) btn.classList.add('active');
            } else {
                bar.style.display = 'none';
                if (btn) btn.classList.remove('active');
            }
        }

        function applyTimelineFilter() {
            const year = document.getElementById('timelineFilterYear')?.value || 'all';
            const type = document.getElementById('timelineFilterType')?.value || 'all';
            const privacy = document.getElementById('timelineFilterPrivacy')?.value || 'all';

            const items = document.querySelectorAll('.timeline-post-item');
            let visibleCount = 0;

            items.forEach(item => {
                const itemYear = item.dataset.year;
                const itemType = item.dataset.type;
                const itemHasMedia = item.dataset.hasMedia === 'true';
                const itemPrivacy = item.dataset.privacy;

                let matchYear = (year === 'all' || itemYear === year);
                let matchPrivacy = (privacy === 'all' || itemPrivacy === privacy);
                let matchType = true;

                if (type === 'life_event') {
                    matchType = (itemType === 'life_event' || (item.innerText && item.innerText.includes('🚩')));
                } else if (type === 'media') {
                    matchType = itemHasMedia;
                } else if (type === 'text') {
                    matchType = (itemType === 'text' && !itemHasMedia);
                }

                if (matchYear && matchPrivacy && matchType) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            const noResultsCard = document.getElementById('noFilterResultsCard');
            if (noResultsCard) {
                noResultsCard.style.display = (visibleCount === 0 && items.length > 0) ? 'block' : 'none';
            }
        }

        function resetTimelineFilter() {
            if (document.getElementById('timelineFilterYear')) document.getElementById('timelineFilterYear').value = 'all';
            if (document.getElementById('timelineFilterType')) document.getElementById('timelineFilterType').value = 'all';
            if (document.getElementById('timelineFilterPrivacy')) document.getElementById('timelineFilterPrivacy').value = 'all';
            applyTimelineFilter();
        }

        function setTimelineViewMode(mode) {
            const stream = document.getElementById('timelinePostsStream');
            const btnList = document.getElementById('btnListView');
            const btnGrid = document.getElementById('btnGridView');

            if (mode === 'grid') {
                stream.classList.add('grid-mode');
                btnGrid?.classList.add('active');
                btnList?.classList.remove('active');
            } else {
                stream.classList.remove('grid-mode');
                btnList?.classList.add('active');
                btnGrid?.classList.remove('active');
            }
        }

        // Post 3-Dot Dropdown & Actions
        function togglePostDropdown(postId, event) {
            if (event) event.stopPropagation();
            const allDropdowns = document.querySelectorAll('.post-options-dropdown');
            allDropdowns.forEach(dd => {
                if (dd.id !== `postDropdown-${postId}`) dd.classList.remove('show');
            });
            const target = document.getElementById(`postDropdown-${postId}`);
            if (target) target.classList.toggle('show');
        }

        window.addEventListener('click', (e) => {
            if (!e.target.closest('.post-header-action-wrap')) {
                document.querySelectorAll('.post-options-dropdown').forEach(dd => dd.classList.remove('show'));
            }
        });

        async function handleTogglePinPost(postId, fromManageModal = false) {
            try {
                const res = await fetch(`/api/v1/posts/${postId}/pin`, {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success' || data.data)) {
                    alert(data.data?.is_pinned ? '📌 পোস্টটি আপনার প্রোফাইলে পিন করা হয়েছে!' : 'পোস্টটি প্রোফাইল থেকে আনপিন করা হয়েছে।');
                    window.location.reload();
                } else {
                    alert(data.message || 'পিন স্ট্যাটাস পরিবর্তন ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগ ত্রুটি।');
            }
        }

        async function handleToggleCommentsPost(postId) {
            try {
                const res = await fetch(`/api/v1/posts/${postId}/comments/toggle`, {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('পোস্টের মন্তব্য সেটিংস আপডেট হয়েছে!');
                    window.location.reload();
                } else {
                    alert(data.message || 'মন্তব্য সেটিংস পরিবর্তন ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগ ত্রুটি।');
            }
        }

        async function handleDeletePost(postId, fromManageModal = false) {
            if (!confirm('আপনি কি নিশ্চিত যে এই পোস্টটি চিরতরে মুছে ফেলতে চান?')) return;
            try {
                const res = await fetch(`/api/v1/posts/${postId}`, {
                    method: 'DELETE',
                    headers: getAuthHeaders()
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('পোস্টটি সফলভাবে মুছে ফেলা হয়েছে।');
                    const card = document.getElementById(`post-card-${postId}`);
                    if (card) card.remove();
                    const row = document.getElementById(`manage-post-row-${postId}`);
                    if (row) row.remove();
                } else {
                    alert(data.message || 'পোস্ট মোছা যায়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগ ত্রুটি।');
            }
        }

        function handleCopyPostLink(postId) {
            const url = `${window.location.origin}/post/${postId}`;
            navigator.clipboard.writeText(url).then(() => {
                alert('পোস্টের লিংক ক্লিপবোর্ডে কপি করা হয়েছে!');
            }).catch(() => {
                prompt('পোস্ট লিংকটি কপি করুন:', url);
            });
            document.querySelectorAll('.post-options-dropdown').forEach(dd => dd.classList.remove('show'));
        }

        function handleSavePostAction(id, contentEncoded) {
            document.querySelectorAll('.post-options-dropdown').forEach(dd => dd.classList.remove('show'));
            const content = decodeURIComponent(contentEncoded || '');
            let saved = JSON.parse(localStorage.getItem('jugajug_saved_posts') || '[]');
            const index = saved.findIndex(item => item.id === id);
            if (index >= 0) {
                saved.splice(index, 1);
                localStorage.setItem('jugajug_saved_posts', JSON.stringify(saved));
                alert('পোস্টটি সংরক্ষিত তালিকা থেকে সরানো হয়েছে। 🔖');
            } else {
                saved.push({ id, content, saved_at: new Date().toISOString() });
                localStorage.setItem('jugajug_saved_posts', JSON.stringify(saved));
                alert('পোস্টটি সফলভাবে সংরক্ষিত তালিকায় সেভ করা হয়েছে! 🔖');
            }
        }

        function handleHidePostAction(postId) {
            document.querySelectorAll('.post-options-dropdown').forEach(dd => dd.classList.remove('show'));
            const card = document.getElementById(`post-card-${postId}`);
            if (card) {
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => card.remove(), 300);
            }
            alert('পোস্টটি আপনার টাইমলাইন থেকে লুকানো হয়েছে।');
        }

        async function handleReportPostAction(postId) {
            document.querySelectorAll('.post-options-dropdown').forEach(dd => dd.classList.remove('show'));
            const reason = prompt('রিপোর্টের কারণ নির্বাচন করুন:\n1. spam (স্প্যাম)\n2. harassment (হয়রানি)\n3. hate_speech (বিদ্বেষমূলক বক্তব্য)\n4. false_information (ভুল তথ্য)\n5. violence (সহিংসতা)\n\nঅনুগ্রহ করে কারণটি ইংরেজিতে টাইপ করুন (যেমন spam, harassment, hate_speech):', 'spam');
            if (!reason) return;

            const validReasons = ['spam', 'harassment', 'hate_speech', 'false_information', 'violence', 'other'];
            const normalizedReason = validReasons.includes(reason.toLowerCase()) ? reason.toLowerCase() : 'other';

            try {
                const res = await fetch('/api/v1/reports', {
                    method: 'POST',
                    headers: getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({
                        reportable_type: 'post',
                        reportable_id: postId,
                        reason: normalizedReason,
                        details: 'Reported from profile timeline'
                    })
                });
                const data = await res.json();
                if (res.ok && (data.success || data.status === 'success')) {
                    alert('আপনার রিপোর্ট সফলভাবে জমা হয়েছে। আমাদের মডারেশন টিম এটি পর্যালোচনা করবে।');
                } else {
                    alert(data.message || 'রিপোর্ট জমা দেওয়া সম্ভব হয়নি।');
                }
            } catch (err) {
                console.error(err);
                alert('সার্ভার যোগাযোগ ত্রুটি।');
            }
        }

        // Logout helper
        async function handleLogout() {
            try {
                await fetch('/api/v1/auth/logout', {
                    method: 'POST',
                    headers: getAuthHeaders()
                });
            } catch (e) {}
            localStorage.removeItem('bondhoo_token');
            localStorage.removeItem('jugajug_token');
            localStorage.removeItem('bondhoo_user');
            localStorage.removeItem('jugajug_user');
            document.cookie = 'bondhoo_token=; path=/; max-age=0; SameSite=Lax';
            document.cookie = 'jugajug_token=; path=/; max-age=0; SameSite=Lax';
            window.location.href = '/login';
        }

        // Close More Tabs Dropdown when clicking outside
        window.addEventListener('click', (e) => {
            if (!e.target.closest('.profile-nav-more-item')) {
                closeMoreTabsDropdown();
            }
        });

        // Check URL hash on load & load section preferences
        function handleHashRouting() {
            const hash = window.location.hash.replace('#', '');
            if (!hash) {
                document.querySelectorAll('.fb-modal-overlay.active').forEach(m => m.classList.remove('active'));
                return;
            }
            if (['posts', 'about', 'friends', 'photos', 'videos', 'saved', 'activity', 'professional', 'analytics'].includes(hash)) {
                switchTab(hash);
            } else if (hash === 'verify') {
                if (typeof openVerificationModal === 'function' && document.getElementById('verificationModal')) openVerificationModal();
            } else if (hash === 'privacy') {
                if (typeof openPrivacyModal === 'function' && document.getElementById('privacyModal')) openPrivacyModal();
            } else if (hash === 'security') {
                if (typeof openSecurityModal === 'function' && document.getElementById('securityModal')) openSecurityModal();
            } else if (hash === 'blocking') {
                if (typeof openBlockingCenterModal === 'function' && document.getElementById('blockingCenterModal')) openBlockingCenterModal();
            } else if (hash === 'lock') {
                if (document.getElementById('profileLockModal')) openModal('profileLockModal');
            } else if (hash === 'notifications') {
                if (typeof openNotificationSettingsModal === 'function' && document.getElementById('notificationSettingsModal')) openNotificationSettingsModal();
            } else if (hash === 'share') {
                if (document.getElementById('profileShareModal')) openModal('profileShareModal');
            } else if (hash === 'story') {
                if (typeof openStoryModal === 'function' && document.getElementById('storyModal')) openStoryModal();
            } else if (hash === 'sections') {
                if (document.getElementById('manageSectionsModal')) openModal('manageSectionsModal');
            } else if (hash.startsWith('edit')) {
                const parts = hash.split('-');
                if (typeof openEditProfileWithTab === 'function') openEditProfileWithTab(parts[1] || 'basic');
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            loadSectionPreferences();
            handleHashRouting();

            // Real-time Echo listeners for post reactions and friendship updates
            if (window.Echo) {
                const currentAuthUserId = {{ auth()->id() ?? 'null' }};
                if (currentAuthUserId) {
                    window.Echo.private(`user.${currentAuthUserId}`)
                        .listen('.friend.request.received', (data) => {
                            if (typeof showToast === 'function') {
                                showToast(`${data.sender?.name || 'ব্যবহারকারী'} আপনাকে একটি ফ্রেন্ড রিকোয়েস্ট পাঠিয়েছেন!`);
                            }
                        })
                        .listen('.friend.request.accepted', (data) => {
                            if (typeof showToast === 'function') {
                                showToast(`${data.friend?.name || 'ব্যবহারকারী'} আপনার ফ্রেন্ড রিকোয়েস্ট গ্রহণ করেছেন!`);
                            }
                        });
                }

                // Listen for reaction updates on all visible timeline posts
                document.querySelectorAll('[id^="reactions-count-"]').forEach(el => {
                    const postId = el.id.replace('reactions-count-', '');
                    if (postId) {
                        window.Echo.channel(`post.${postId}`)
                            .listen('.post.reaction.updated', (event) => {
                                if (el && event.total_reactions !== undefined) {
                                    el.innerText = event.total_reactions;
                                }
                            });
                    }
                });

                // Realtime listen for reels and stories expiration on profile
                window.Echo.channel('reels')
                    .listen('.reel.expired', (e) => {
                        if (e && e.reel_id) {
                            document.getElementById(`profile-reel-card-${e.reel_id}`)?.remove();
                            if (currentProfileReelId == e.reel_id) {
                                alert('এই রিলটির ২৪ ঘণ্টার মেয়াদ শেষ হয়ে গেছে।');
                                closeReelPlayerModal();
                            }
                        }
                    });

                window.Echo.channel('stories')
                    .listen('.story.expired', (e) => {
                        if (e && e.story_id) {
                            profileActiveStories = profileActiveStories.filter(s => s.id != e.story_id);
                            if (profileActiveStories.length === 0) {
                                document.querySelector('.avatar-story-badge')?.remove();
                                document.getElementById('profileAvatarImg')?.classList.remove('avatar-has-story');
                                closeActiveStoryViewer();
                            }
                        }
                    });
            }
        });

        function switchLiveChatTab(tab) {
            const liveTab = document.getElementById('btnLiveTab');
            const chatTab = document.getElementById('btnChatTab');
            const liveContent = document.getElementById('liveTabContent');
            const chatContent = document.getElementById('chatTabContent');
            if (tab === 'live') {
                if (liveTab) liveTab.classList.add('active');
                if (chatTab) chatTab.classList.remove('active');
                if (liveContent) liveContent.style.display = 'block';
                if (chatContent) chatContent.style.display = 'none';
            } else {
                if (liveTab) liveTab.classList.remove('active');
                if (chatTab) chatTab.classList.add('active');
                if (liveContent) liveContent.style.display = 'none';
                if (chatContent) chatContent.style.display = 'block';
            }
        }

        window.addEventListener('hashchange', () => {
            handleHashRouting();
        });
    </script>
    @include('partials.mobile-navigation')
    @include('partials.realtime-listener')
</body>
</html>
