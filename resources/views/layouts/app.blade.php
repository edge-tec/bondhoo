<!DOCTYPE html>
<html lang="bn" data-theme="light" class="{{ request()->is('messages*') ? 'is-messenger-page' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#18191a" media="(prefers-color-scheme: dark)">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('title', 'Bondhoo — বন্ধু সোশ্যাল নেটওয়ার্ক')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="apple-touch-icon" href="/images/bondhoo-icon-192.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/enterprise-mobile-app.css?v={{ time() }}">
    <link rel="stylesheet" href="/css/enterprise-dashboard-upgrade.css?v={{ time() }}">
    <style>
        :root {
            --fb-bg: #f0f2f5;
            --fb-card: #ffffff;
            --fb-primary: #1877f2;
            --fb-primary-hover: #166fe5;
            --fb-text-primary: #050505;
            --fb-text-secondary: #65676b;
            --fb-border: #e4e6eb;
            --fb-divider: #ced0d4;
            --fb-hover: #f2f2f2;
            --fb-btn-bg: #e4e6eb;
            --fb-green: #42b72a;
            --fb-red: #f02849;
            --fb-yellow: #f7b125;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        [data-theme="dark"] {
            --fb-bg: #18191a;
            --fb-card: #242526;
            --fb-primary: #2d88ff;
            --fb-primary-hover: #3a91ff;
            --fb-text-primary: #e4e6eb;
            --fb-text-secondary: #b0b3b8;
            --fb-border: #393a3b;
            --fb-divider: #3e4042;
            --fb-hover: #3a3b3c;
            --fb-btn-bg: #3a3b3c;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.4);
            --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.5);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.6);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Hind Siliguri', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: var(--fb-bg);
            color: var(--fb-text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        html.is-messenger-page,
        body.is-messenger-page {
            height: 100% !important;
            min-height: 100% !important;
            max-height: 100% !important;
            overflow: hidden !important;
        }

        /* TOP NAVIGATION BAR */
        header.app-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--fb-card);
            border-bottom: 1px solid var(--fb-border);
            height: 56px;
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            max-width: 320px;
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
            background: var(--fb-bg);
            border: 1px solid transparent;
            border-radius: 50px;
            padding: 0 16px 0 38px;
            font-size: 14px;
            outline: none;
            color: var(--fb-text-primary);
            transition: all 0.2s;
        }

        .search-input:focus {
            background: var(--fb-card);
            border-color: var(--fb-primary);
            box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.2);
        }

        .search-box .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--fb-text-secondary);
            font-size: 14px;
        }

        /* Center Nav Icons */
        .header-center {
            display: flex;
            height: 100%;
            align-items: center;
            gap: 4px;
        }

        .nav-tab {
            height: 48px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            color: var(--fb-text-secondary);
            font-size: 20px;
            position: relative;
            transition: background 0.2s, color 0.2s;
            text-decoration: none;
        }

        .nav-tab svg {
            width: 26px;
            height: 26px;
            stroke: var(--fb-text-secondary);
            display: block;
            transition: transform 0.15s ease, stroke 0.15s ease, color 0.15s ease;
        }

        .nav-tab:hover svg {
            transform: scale(1.08);
            stroke: var(--fb-primary);
        }

        .nav-tab.active svg {
            stroke: var(--fb-primary);
        }

        .nav-tab:hover {
            background: var(--fb-hover);
        }

        .nav-tab.active {
            color: var(--fb-primary);
        }

        .nav-tab.active::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--fb-primary);
            border-radius: 2px 2px 0 0;
        }

        /* Right Action Icons */
        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }

        .circle-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--fb-btn-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: var(--fb-text-primary);
            transition: background 0.2s, transform 0.1s;
            text-decoration: none;
            position: relative;
        }

        .circle-btn svg {
            width: 22px;
            height: 22px;
            display: block;
            transition: transform 0.15s ease;
        }

        .circle-btn:hover svg {
            transform: scale(1.08);
        }

        .circle-btn:hover {
            background: var(--fb-hover);
            transform: scale(1.04);
        }

        .circle-btn.active {
            background: rgba(24, 119, 242, 0.15);
            color: var(--fb-primary);
        }

        .badge-count {
            position: absolute;
            top: -3px;
            right: -3px;
            background: var(--fb-red);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            overflow: hidden;
            font-size: 15px;
            border: 1px solid var(--fb-border);
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Dropdowns */
        .dropdown-menu-box {
            position: absolute;
            right: 0;
            top: 48px;
            width: 280px;
            background: var(--fb-card);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--fb-border);
            z-index: 150;
            padding: 8px;
            display: none;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--fb-text-primary);
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .dropdown-item:hover {
            background: var(--fb-hover);
        }

        .dropdown-divider {
            height: 1px;
            background: var(--fb-border);
            margin: 6px 0;
        }

        /* Header Notification Dropdown */
        .header-notif-dropdown {
            position: absolute;
            right: 0;
            top: 48px;
            width: 380px;
            max-width: calc(100vw - 24px);
            background: var(--fb-card);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--fb-border);
            z-index: 160;
            display: none;
            flex-direction: column;
            overflow: hidden;
            animation: hndFadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes hndFadeIn {
            from { opacity: 0; transform: translateY(-8px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .hnd-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--fb-border);
        }

        .hnd-unread-chip {
            background: rgba(24, 119, 242, 0.12);
            color: var(--fb-primary);
            font-size: 11.5px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .hnd-action-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--fb-btn-bg);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--fb-text-secondary);
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .hnd-action-icon:hover {
            background: var(--fb-hover);
            color: var(--fb-text-primary);
            transform: scale(1.05);
        }

        .hnd-tabs {
            display: flex;
            gap: 8px;
            padding: 8px 16px 6px;
            border-bottom: 1px solid var(--fb-border);
            background: var(--fb-card);
        }

        .hnd-tab {
            padding: 6px 14px;
            border-radius: 18px;
            border: none;
            background: var(--fb-btn-bg);
            color: var(--fb-text-secondary);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .hnd-tab:hover {
            background: var(--fb-hover);
            color: var(--fb-text-primary);
        }

        .hnd-tab.active {
            background: var(--fb-primary);
            color: #ffffff;
            font-weight: 700;
        }

        .hnd-list {
            max-height: 380px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            padding: 4px 0;
            scrollbar-width: thin;
        }

        .hnd-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 16px;
            text-decoration: none;
            color: var(--fb-text-primary);
            transition: background 0.15s ease;
            cursor: pointer;
            position: relative;
            border-left: 3px solid transparent;
        }

        .hnd-item:hover {
            background: var(--fb-hover);
        }

        .hnd-item.unread {
            background: rgba(24, 119, 242, 0.05);
            border-left-color: var(--fb-primary);
        }

        .hnd-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }

        .hnd-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1877f2, #00c6ff);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            overflow: hidden;
            border: 1px solid var(--fb-border);
        }

        .hnd-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hnd-badge {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--fb-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--fb-card);
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            font-size: 10px;
        }

        .hnd-badge.security { background: #f59e0b; }
        .hnd-badge.system { background: #6366f1; }
        .hnd-badge.group { background: #10b981; }

        .hnd-body {
            flex: 1;
            min-width: 0;
        }

        .hnd-msg {
            font-size: 13.5px;
            line-height: 1.4;
            color: var(--fb-text-primary);
            margin-bottom: 3px;
            word-break: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .hnd-time {
            font-size: 12px;
            color: var(--fb-text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .hnd-unread-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--fb-primary);
            flex-shrink: 0;
            margin-top: 14px;
        }

        .hnd-empty {
            padding: 36px 16px;
            text-align: center;
            color: var(--fb-text-secondary);
            font-size: 13.5px;
        }

        .hnd-footer {
            padding: 10px 16px;
            border-top: 1px solid var(--fb-border);
            text-align: center;
            background: var(--fb-card);
        }

        .hnd-footer a {
            font-size: 13px;
            font-weight: 700;
            color: var(--fb-primary);
            text-decoration: none;
        }

        .hnd-footer a:hover {
            text-decoration: underline;
        }

        .hnd-skeleton {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            align-items: center;
        }

        .hnd-skeleton-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--fb-hover);
            animation: hndPulse 1.2s infinite ease-in-out;
        }

        .hnd-skeleton-lines {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .hnd-skeleton-line {
            height: 12px;
            background: var(--fb-hover);
            border-radius: 4px;
            animation: hndPulse 1.2s infinite ease-in-out;
        }

        @keyframes hndPulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }

        @media (max-width: 480px) {
            .header-notif-dropdown {
                position: fixed;
                top: 56px;
                left: 8px;
                right: 8px;
                width: auto;
                max-width: none;
            }
        }

        /* Common Page Container */
        .main-app-container {
            flex: 1;
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 16px;
        }

        .main-app-container.main-messenger-mode {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            height: calc(100vh - 56px) !important;
            max-height: calc(100vh - 56px) !important;
            flex: 1 1 auto !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }

        .fb-card {
            background: var(--fb-card);
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--fb-border);
            padding: 16px;
        }

        .btn-fb-primary {
            background: var(--fb-primary);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-fb-primary:hover {
            background: var(--fb-primary-hover);
        }

        .btn-fb-secondary {
            background: var(--fb-btn-bg);
            color: var(--fb-text-primary);
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-fb-secondary:hover {
            background: var(--fb-hover);
        }

        @media (max-width: 768px) {
            .header-center {
                display: none;
            }
            .header-left {
                max-width: 200px;
            }
        }
    </style>
    @yield('styles')
</head>
<body class="{{ request()->is('messages*') ? 'is-messenger-page' : '' }}">
    @php
        $currentUser = auth('web')->user();
        if (!$currentUser && (request()->hasCookie('bondhoo_token') || request()->hasCookie('jugajug_token'))) {
            $rawToken = (string) (request()->cookie('bondhoo_token') ?: request()->cookie('jugajug_token'));
            $pat = \Laravel\Sanctum\PersonalAccessToken::findToken($rawToken);
            if ($pat && $pat->tokenable instanceof \App\Models\User) {
                $currentUser = $pat->tokenable;
            }
        }
        $userName = $currentUser ? $currentUser->name : 'অতিথি';
        $userInitial = $currentUser ? mb_substr($currentUser->name, 0, 1) : 'অ';
        $userAvatar = $currentUser?->profile?->avatar_url;
        if ($userAvatar && str_starts_with($userAvatar, '/storage/') && !file_exists(public_path($userAvatar))) {
            $userAvatar = '/images/default-avatar.svg';
        }
        $userAvatar = $userAvatar ?: '/images/default-avatar.svg';
        $unreadNotifs = $currentUser ? app(\App\Services\Contracts\NotificationServiceInterface::class)->getUnreadCount($currentUser) : 0;
        $unreadMsgCount = $currentUser ? app(\App\Services\Contracts\MessengerServiceInterface::class)->getUnreadMessageCount($currentUser) : 0;
        $pendingFriendCount = $currentUser ? \App\Models\Friendship::where('friend_id', $currentUser->id)->where('status', 'pending')->count() : 0;
    @endphp

    <header class="app-header">
        <!-- Left: Logo & Search -->
        <div class="header-left">
            <a href="/" class="fb-logo" title="Bondhoo হোম" style="background:transparent;box-shadow:none;padding:0;display:flex;align-items:center;text-decoration:none;">
                <img src="/images/bondhoo-icon.png" alt="Bondhoo" width="40" height="40" style="display:block;border-radius:10px;object-fit:contain;">
            </a>
            <div class="search-box">
                <span class="search-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" class="search-input" id="globalAppSearch" placeholder="Bondhoo-তে অনুসন্ধান করুন..." autocomplete="off">
            </div>
        </div>

        <!-- Center: Facebook Tabs -->
        <div class="header-center">
            <a href="/" class="nav-tab {{ request()->is('/') || request()->is('dashboard') ? 'active' : '' }}" title="হোম ফিড">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </a>
            <a href="/friends" class="nav-tab {{ request()->is('friends*') ? 'active' : '' }}" title="বন্ধুরা ও সোশ্যাল কানেকশন" id="navFriendsTab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span class="badge-count" id="navbarPendingFriendsBadge" style="{{ $pendingFriendCount > 0 ? '' : 'display: none;' }}">{{ $pendingFriendCount }}</span>
            </a>
            <a href="/groups" class="nav-tab {{ request()->is('groups*') ? 'active' : '' }}" title="গ্রুপস ও কমিউনিটি">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </a>
            <a href="/pages" class="nav-tab {{ request()->is('pages*') ? 'active' : '' }}" title="পেইজসমূহ">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    <line x1="9" y1="7" x2="16" y2="7"></line>
                    <line x1="9" y1="11" x2="14" y2="11"></line>
                </svg>
            </a>
            <a href="/watch" class="nav-tab {{ request()->is('watch*') || request()->is('live*') ? 'active' : '' }}" title="ওয়াচ ও লাইভ স্ট্রিমিং">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <polygon points="10 8 16 10 10 12 10 8" fill="currentColor"></polygon>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
            </a>
            <a href="/marketplace" class="nav-tab {{ request()->is('marketplace*') ? 'active' : '' }}" title="মার্কেটপ্লেস">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
            </a>
        </div>

        <!-- Right: Messages, Notifications, Avatar -->
        <div class="header-right">
            <a href="/messages" class="circle-btn {{ request()->is('messages*') ? 'active' : '' }}" title="মেসেঞ্জার" id="navbarMessengerBtn">
                <svg width="22" height="22" viewBox="0 0 28 28" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M14 2C7.373 2 2 7.155 2 13.518c0 3.626 1.745 6.862 4.475 8.974V26l3.37-1.85c1.28.355 2.646.549 4.155.549 6.627 0 12-5.155 12-11.518C26 7.155 20.627 2 14 2zm1.203 15.534l-3.08-3.284-6.012 3.284 6.613-7.02 3.155 3.284 5.937-3.284-6.613 7.02z" fill="url(#navMessengerGrad)"/>
                    <defs>
                        <linearGradient id="navMessengerGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#0078FF"/>
                            <stop offset="70%" stop-color="#00C6FF"/>
                            <stop offset="100%" stop-color="#00E5FF"/>
                        </linearGradient>
                    </defs>
                </svg>
                <span class="badge-count" id="navbarUnreadMessagesBadge" style="{{ $unreadMsgCount > 0 ? '' : 'display: none;' }}">{{ $unreadMsgCount }}</span>
            </a>

            <!-- Global Notification Button & Dropdown Panel -->
            <div style="position: relative;" id="globalHeaderNotifContainer">
                <button type="button"
                        class="circle-btn {{ request()->is('notifications*') ? 'active' : '' }}"
                        id="navbarNotificationBtn"
                        onclick="toggleHeaderNotificationDropdown()"
                        aria-expanded="false"
                        aria-haspopup="true"
                        aria-label="নোটিফিকেশন"
                        title="নোটিফিকেশন">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="badge-count" id="navbarUnreadNotifsBadge" style="{{ $unreadNotifs > 0 ? '' : 'display: none;' }}">
                        {{ $unreadNotifs > 99 ? '99+' : $unreadNotifs }}
                    </span>
                </button>

                <!-- Dropdown Menu -->
                <div class="header-notif-dropdown" id="headerNotifDropdown" role="dialog" aria-label="নোটিফিকেশন তালিকা" style="display: none;">
                    <div class="hnd-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 16px; font-weight: 800; color: var(--fb-text-primary);">নোটিফিকেশন</span>
                            <span class="hnd-unread-chip" id="dropdownUnreadChip" style="{{ $unreadNotifs > 0 ? '' : 'display: none;' }}">
                                {{ $unreadNotifs }} নতুন
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <button type="button" class="hnd-action-icon" onclick="headerMarkAllAsRead(event)" title="সব পঠিত চিহ্নিত করুন" aria-label="Mark all as read">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </button>
                            <a href="/notifications" class="hnd-action-icon" title="নোটিফিকেশন সেন্টার">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                    <polyline points="15 3 21 3 21 9"></polyline>
                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="hnd-tabs">
                        <button type="button" class="hnd-tab active" id="hndTabAll" onclick="switchHeaderNotifTab('all')">সব</button>
                        <button type="button" class="hnd-tab" id="hndTabUnread" onclick="switchHeaderNotifTab('unread')">অপঠিত</button>
                    </div>

                    <!-- List Area -->
                    <div class="hnd-list" id="headerNotifList" role="region" aria-live="polite">
                        <!-- Populated via AJAX -->
                    </div>

                    <!-- Footer Link -->
                    <div class="hnd-footer">
                        <a href="/notifications">সম্পূর্ণ নোটিফিকেশন সেন্টার দেখুন →</a>
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div style="position: relative;">
                <div class="avatar" onclick="toggleGlobalUserDropdown()" title="{{ $userName }}">
                    <img src="{{ $userAvatar }}" alt="{{ $userName }}" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                </div>

                <div class="dropdown-menu-box" id="globalUserDropdown">
                    @if($currentUser)
                        <a href="{{ route('profile.show', ['username' => $currentUser->username]) }}" class="dropdown-item" style="border-bottom: 1px solid var(--fb-border); margin-bottom: 6px;">
                            <div class="avatar" style="width: 36px; height: 36px;">
                                <img src="{{ $userAvatar }}" alt="{{ $userName }}" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 14px;">{{ $userName }}</div>
                                <div style="font-size: 12px; color: var(--fb-text-secondary);">প্রোফাইল দেখুন (@ {{ $currentUser->username }})</div>
                            </div>
                        </a>
                        <a href="/devices" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                            <span>ডিভাইস ও সক্রিয় সেশন</span>
                        </a>
                        <a href="/settings/two-factor" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            <span>টু-ফ্যাক্টর নিরাপত্তা (2FA)</span>
                        </a>
                        <a href="/live/studio" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"></circle><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"></path></svg>
                            <span>ক্রিয়েটর লাইভ স্টুডিও</span>
                        </a>
                    @else
                        <a href="/login" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                            <span>লগইন করুন</span>
                        </a>
                        <a href="/register" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                            <span>নতুন অ্যাকাউন্ট তৈরি করুন</span>
                        </a>
                    @endif

                    <div class="dropdown-item" onclick="toggleGlobalTheme()">
                        <span id="themeIconSpan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg></span>
                        <span id="themeTextSpan">ডার্ক মোড</span>
                    </div>

                    @if($currentUser)
                        <div class="dropdown-divider"></div>
                        <form action="/logout" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="dropdown-item" style="width: 100%; border: none; background: none; color: var(--fb-red);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                <span>লগআউট</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Mobile Menu Drawer Toggle (Visible on mobile screens) -->
            <button type="button" class="circle-btn mobile-menu-toggle-btn" id="mobileHeaderMenuBtn" title="মেনু ও এক্সপ্লোর" onclick="openMobileMenuDrawer()" aria-label="মেনু খুলুন" style="display: none;">
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

    <main class="main-app-container {{ request()->is('messages*') ? 'main-messenger-mode' : '' }}">
        @yield('content')
    </main>

    <!-- Global Floating Toast Notification Container -->
    <div id="jugajugToastContainer" style="position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; max-width: 360px; pointer-events: none;"></div>

    <script>
        // Header Notification Dropdown Logic
        let headerNotifCurrentTab = 'all';

        function toggleHeaderNotificationDropdown() {
            const dropdown = document.getElementById('headerNotifDropdown');
            const userDropdown = document.getElementById('globalUserDropdown');
            if (userDropdown) userDropdown.style.display = 'none';

            if (!dropdown) return;

            const isVisible = dropdown.style.display === 'flex' || dropdown.style.display === 'block';
            if (isVisible) {
                dropdown.style.display = 'none';
                document.getElementById('navbarNotificationBtn')?.setAttribute('aria-expanded', 'false');
            } else {
                dropdown.style.display = 'flex';
                document.getElementById('navbarNotificationBtn')?.setAttribute('aria-expanded', 'true');
                fetchHeaderNotifications(headerNotifCurrentTab);
            }
        }

        function switchHeaderNotifTab(tab) {
            headerNotifCurrentTab = tab;
            const tabAll = document.getElementById('hndTabAll');
            const tabUnread = document.getElementById('hndTabUnread');
            if (tab === 'unread') {
                tabAll?.classList.remove('active');
                tabUnread?.classList.add('active');
            } else {
                tabAll?.classList.add('active');
                tabUnread?.classList.remove('active');
            }
            fetchHeaderNotifications(tab);
        }

        async function fetchHeaderNotifications(filter = 'all') {
            const list = document.getElementById('headerNotifList');
            if (!list) return;

            list.innerHTML = `
                <div class="hnd-skeleton">
                    <div class="hnd-skeleton-avatar"></div>
                    <div class="hnd-skeleton-lines"><div class="hnd-skeleton-line" style="width: 70%;"></div><div class="hnd-skeleton-line" style="width: 45%;"></div></div>
                </div>
                <div class="hnd-skeleton">
                    <div class="hnd-skeleton-avatar"></div>
                    <div class="hnd-skeleton-lines"><div class="hnd-skeleton-line" style="width: 85%;"></div><div class="hnd-skeleton-line" style="width: 50%;"></div></div>
                </div>
                <div class="hnd-skeleton">
                    <div class="hnd-skeleton-avatar"></div>
                    <div class="hnd-skeleton-lines"><div class="hnd-skeleton-line" style="width: 60%;"></div><div class="hnd-skeleton-line" style="width: 35%;"></div></div>
                </div>
            `;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/api/v1/notifications?per_page=10&filter=${filter}`, {
                    headers: {
                        'Accept': 'application/json',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                    }
                });
                const json = await res.json();
                if (!json.success || !Array.isArray(json.data) || json.data.length === 0) {
                    list.innerHTML = `
                        <div class="hnd-empty">
                            <div style="font-size: 26px; margin-bottom: 6px;">🔔</div>
                            <div>${filter === 'unread' ? 'কোনো অপঠিত নোটিফিকেশন নেই' : 'কোনো নোটিফিকেশন নেই'}</div>
                        </div>
                    `;
                    return;
                }

                list.innerHTML = '';
                json.data.forEach(item => {
                    const row = document.createElement('a');
                    row.href = item.action_url || '/notifications';
                    row.className = `hnd-item ${item.is_read ? 'read' : 'unread'}`;
                    row.id = `hndItem-${item.id}`;
                    row.onclick = () => {
                        if (!item.is_read) {
                            markHeaderNotifAsRead(item.id);
                        }
                    };

                    const initial = item.actor?.initial || (item.actor?.name ? item.actor.name.charAt(0).toUpperCase() : 'য');
                    const avatarHtml = item.actor?.avatar_url
                        ? `<img src="${item.actor.avatar_url}" alt="${item.actor.name}" onerror="this.onerror=null; this.parentElement.innerText='${initial}';">`
                        : initial;

                    const category = item.category || 'social';
                    let catIcon = '🔔';
                    if (category === 'social') catIcon = '👥';
                    else if (category === 'group') catIcon = '🏛️';
                    else if (category === 'security') catIcon = '🛡️';
                    else if (category === 'system') catIcon = '⚙️';

                    row.innerHTML = `
                        <div class="hnd-avatar-wrap">
                            <div class="hnd-avatar">${avatarHtml}</div>
                            <div class="hnd-badge ${category}">${catIcon}</div>
                        </div>
                        <div class="hnd-body">
                            <div class="hnd-msg">${escapeHeaderHtml(item.message)}</div>
                            <div class="hnd-time">
                                <span>${item.time_ago || 'কিছুক্ষণ আগে'}</span>
                            </div>
                        </div>
                        ${!item.is_read ? `<div class="hnd-unread-dot" id="hndDot-${item.id}"></div>` : ''}
                    `;
                    list.appendChild(row);
                });

                if (typeof json.meta?.unread_count === 'number') {
                    updateHeaderUnreadBadges(json.meta.unread_count);
                }
            } catch (err) {
                console.error('Error fetching header notifications:', err);
                list.innerHTML = '<div class="hnd-empty">নোটিফিকেশন লোড করা সম্ভব হয়নি।</div>';
            }
        }

        function escapeHeaderHtml(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        async function markHeaderNotifAsRead(id) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/api/v1/notifications/${id}/read`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                    }
                });
                const data = await res.json();
                if (data.success) {
                    const item = document.getElementById(`hndItem-${id}`);
                    if (item) {
                        item.classList.remove('unread');
                        item.classList.add('read');
                        document.getElementById(`hndDot-${id}`)?.remove();
                    }
                    if (typeof data.data?.unread_count === 'number') {
                        updateHeaderUnreadBadges(data.data.unread_count);
                    }
                }
            } catch (e) {}
        }

        async function headerMarkAllAsRead(event) {
            if (event) event.stopPropagation();
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/api/v1/notifications/read-all', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                    }
                });
                const data = await res.json();
                if (data.success) {
                    document.querySelectorAll('.hnd-item.unread').forEach(el => {
                        el.classList.remove('unread');
                        el.classList.add('read');
                    });
                    document.querySelectorAll('.hnd-unread-dot').forEach(el => el.remove());
                    updateHeaderUnreadBadges(0);
                    if (window.showJugajugToast) {
                        showJugajugToast('সম্পন্ন', 'সমস্ত নোটিফিকেশন পঠিত চিহ্নিত করা হয়েছে।', '✓', 'success');
                    }
                    if (headerNotifCurrentTab === 'unread') {
                        fetchHeaderNotifications('unread');
                    }
                }
            } catch (err) {
                console.error('Error marking all as read from header:', err);
            }
        }

        function updateHeaderUnreadBadges(count) {
            const badge = document.getElementById('navbarUnreadNotifsBadge');
            if (badge) {
                badge.innerText = count > 99 ? '99+' : count;
                badge.style.display = count > 0 ? '' : 'none';
            }
            const chip = document.getElementById('dropdownUnreadChip');
            if (chip) {
                chip.innerText = `${count} নতুন`;
                chip.style.display = count > 0 ? '' : 'none';
            }
            const tabBadge = document.getElementById('ncTabUnreadBadge');
            if (tabBadge) {
                tabBadge.innerText = count;
                tabBadge.style.display = count > 0 ? '' : 'none';
            }
        }

        function toggleGlobalUserDropdown() {
            const dropdown = document.getElementById('globalUserDropdown');
            const notifDropdown = document.getElementById('headerNotifDropdown');
            if (notifDropdown) notifDropdown.style.display = 'none';

            if (dropdown) {
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            }
        }

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('globalUserDropdown');
            if (dropdown && !e.target.closest('.header-right')) {
                dropdown.style.display = 'none';
            }
            const notifDropdown = document.getElementById('headerNotifDropdown');
            if (notifDropdown && !e.target.closest('#globalHeaderNotifContainer')) {
                notifDropdown.style.display = 'none';
                document.getElementById('navbarNotificationBtn')?.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const notifDropdown = document.getElementById('headerNotifDropdown');
                if (notifDropdown && notifDropdown.style.display !== 'none') {
                    notifDropdown.style.display = 'none';
                    document.getElementById('navbarNotificationBtn')?.setAttribute('aria-expanded', 'false');
                    document.getElementById('navbarNotificationBtn')?.focus();
                }
                const userDropdown = document.getElementById('globalUserDropdown');
                if (userDropdown && userDropdown.style.display !== 'none') {
                    userDropdown.style.display = 'none';
                }
            }
        });

        function toggleGlobalTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', nextTheme);
            localStorage.setItem('bondhoo_theme', nextTheme);
            localStorage.setItem('jugajug_theme', nextTheme);
            updateThemeLabels(nextTheme);
        }

        function updateThemeLabels(theme) {
            const icon = document.getElementById('themeIconSpan');
            const text = document.getElementById('themeTextSpan');
            if (icon && text) {
                if (theme === 'dark') {
                    icon.textContent = '☀️';
                    text.textContent = 'লাইট মোড অন করুন';
                } else {
                    icon.textContent = '🌙';
                    text.textContent = 'ডার্ক মোড অন করুন';
                }
            }
        }

        // Initialize saved theme
        (function() {
            const savedTheme = localStorage.getItem('bondhoo_theme') || localStorage.getItem('jugajug_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeLabels(savedTheme);
        })();

        // Global Toast Notification Helper
        window.showBondhooToast = window.showJugajugToast = function(title, message, icon = '🔔', type = 'info') {
            const container = document.getElementById('jugajugToastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.style.pointerEvents = 'auto';
            toast.style.background = 'var(--fb-card)';
            toast.style.color = 'var(--fb-text-primary)';
            toast.style.border = '1px solid var(--fb-border)';
            toast.style.borderRadius = '12px';
            toast.style.padding = '14px 18px';
            toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.15)';
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.gap = '12px';
            toast.style.animation = 'slideInToast 0.3s ease-out';
            toast.style.transition = 'opacity 0.3s, transform 0.3s';

            toast.innerHTML = `
                <div style="font-size: 24px; flex-shrink: 0;">${icon}</div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 700; font-size: 14px; margin-bottom: 2px;">${title}</div>
                    <div style="font-size: 13px; color: var(--fb-text-secondary); word-break: break-word;">${message}</div>
                </div>
                <button onclick="this.parentElement.remove()" style="background:none; border:none; color:var(--fb-text-secondary); cursor:pointer; font-size:16px;">✕</button>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        };

        // Realtime Presence Heartbeat & Session Tracking for authenticated users
        @if($currentUser)
            (function initPresenceHeartbeat() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                let sessionId = sessionStorage.getItem('jj_presence_session_id');
                if (!sessionId) {
                    sessionId = 'sess_' + Math.random().toString(36).substring(2, 15) + '_' + Date.now();
                    sessionStorage.setItem('jj_presence_session_id', sessionId);
                }

                function sendPing() {
                    fetch('/api/v1/presence/heartbeat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                        },
                        body: JSON.stringify({ session_id: sessionId })
                    }).catch(() => {});
                }

                // Initial ping & periodic interval (every 25s within 45s TTL)
                sendPing();
                const heartbeatTimer = setInterval(sendPing, 25000);

                // Graceful disconnect on window/tab close or navigate away
                window.addEventListener('beforeunload', () => {
                    const payload = JSON.stringify({ session_id: sessionId });
                    if (navigator.sendBeacon) {
                        const blob = new Blob([payload], { type: 'application/json' });
                        navigator.sendBeacon('/api/v1/presence/offline', blob);
                    } else {
                        fetch('/api/v1/presence/offline', {
                            method: 'POST',
                            keepalive: true,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                            },
                            body: payload
                        }).catch(() => {});
                    }
                });
            })();

            // Global Realtime Notification Synchronization
            (function initRealtimeNotificationSync() {
                if (window.Echo && {{ $currentUser->id }}) {
                    window.Echo.private('user.{{ $currentUser->id }}')
                        .listen('.notification.new', (e) => {
                            const notif = e.notification || e;
                            const title = notif.actor?.name || notif.title || 'নতুন নোটিফিকেশন';
                            const msg = notif.message || notif.data?.message || 'আপনার একটি নতুন নোটিফিকেশন এসেছে';
                            
                            if (window.showJugajugToast) {
                                showJugajugToast(title, msg, '🔔', 'info');
                            }

                            // Fetch updated unread count
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                            fetch('/api/v1/notifications/unread-count', {
                                headers: {
                                    'Accept': 'application/json',
                                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                                }
                            })
                            .then(r => r.json())
                            .then(res => {
                                if (res.success && typeof res.data?.unread_count === 'number') {
                                    updateHeaderUnreadBadges(res.data.unread_count);
                                }
                            }).catch(() => {});

                            // If dropdown is open, refresh
                            const notifDropdown = document.getElementById('headerNotifDropdown');
                            if (notifDropdown && notifDropdown.style.display !== 'none') {
                                fetchHeaderNotifications(headerNotifCurrentTab);
                            }
                        })
                        .listen('.notification.read', (e) => {
                            if (typeof e.unread_count === 'number') {
                                updateHeaderUnreadBadges(e.unread_count);
                            }
                            const item = document.getElementById(`hndItem-${e.id}`);
                            if (item) {
                                item.classList.remove('unread');
                                item.classList.add('read');
                                document.getElementById(`hndDot-${e.id}`)?.remove();
                            }
                        })
                        .listen('.notification.read_all', () => {
                            updateHeaderUnreadBadges(0);
                            document.querySelectorAll('.hnd-item.unread').forEach(el => {
                                el.classList.remove('unread');
                                el.classList.add('read');
                            });
                            document.querySelectorAll('.hnd-unread-dot').forEach(el => el.remove());
                        })
                        .listen('.notification.deleted', (e) => {
                            if (typeof e.unread_count === 'number') {
                                updateHeaderUnreadBadges(e.unread_count);
                            }
                            const item = document.getElementById(`hndItem-${e.id}`);
                            if (item) item.remove();
                        })
                        .listen('.call.incoming', (e) => {
                            const payload = e.payload || e;
                            if (typeof window.showIncomingCall === 'function') {
                                window.showIncomingCall(payload);
                            }
                        })
                        .listen('.call.ended', () => {
                            if (typeof window.hideIncomingCall === 'function') {
                                window.hideIncomingCall();
                            }
                        })
                        .listen('.call.rejected', () => {
                            if (typeof window.hideIncomingCall === 'function') {
                                window.hideIncomingCall();
                            }
                        });
                }
            })();
        @endif
    </script>
    <style>
        @keyframes slideInToast {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    @yield('scripts')
    @include('partials.mobile-navigation')
    @include('partials.realtime-listener')
</body>
</html>
