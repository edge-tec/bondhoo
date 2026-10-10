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
    <title>Bondhoo — বন্ধু সোশ্যাল নেটওয়ার্ক</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="apple-touch-icon" href="/images/bondhoo-icon-192.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/enterprise-stories-reels.css?v={{ time() }}">
    <link rel="stylesheet" href="/css/enterprise-mobile-app.css?v={{ time() }}">
    <link rel="stylesheet" href="/css/enterprise-dashboard-upgrade.css?v={{ time() }}">
    <link rel="stylesheet" href="/css/enterprise-post-composer.css?v={{ time() }}">
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

        /* ------------------------------------------------------------- */
        /* TOP NAVIGATION BAR (FACEBOOK STYLE) */
        /* ------------------------------------------------------------- */
        header {
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

        .bondhoo-main-brand-logo {
            height: 38px;
            max-width: 170px;
            width: auto;
            object-fit: contain;
            display: block;
            transition: transform 0.15s ease;
        }

        .fb-logo-brand:hover .bondhoo-main-brand-logo {
            transform: scale(1.02);
        }

        [data-theme="dark"] .bondhoo-main-brand-logo {
            content: url('/images/bondhoo-logo-white.png');
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
            border: none;
            border-radius: 50px;
            padding: 0 16px 0 38px;
            font-size: 14px;
            outline: none;
            color: var(--fb-text-primary);
            transition: all 0.2s;
        }

        .search-input:focus {
            background: #ffffff;
            box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--fb-text-secondary);
            font-size: 14px;
        }

        /* Center Nav Icons (Facebook Tabs) */
        .header-center {
            display: flex;
            height: 100%;
            align-items: center;
            gap: 8px;
        }

        .nav-tab {
            height: 48px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            color: var(--fb-text-secondary);
            font-size: 20px;
            position: relative;
            transition: background 0.2s;
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

        /* Right Nav User Controls */
        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-end;
            flex: 1;
        }

        .circle-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--fb-btn-bg);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--fb-text-primary);
            cursor: pointer;
            font-size: 16px;
            transition: background 0.2s;
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
            background: #d8dadf;
        }

        .admin-toggle-btn {
            background: #e7f3ff;
            color: var(--fb-primary);
            border: 1px solid rgba(24, 119, 242, 0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .admin-toggle-btn:hover {
            background: #dbe7f2;
        }

        /* ------------------------------------------------------------- */
        /* 3-COLUMN MAIN LAYOUT (FACEBOOK STYLE) */
        /* ------------------------------------------------------------- */
        .main-container {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr) 280px;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            min-height: calc(100vh - 56px);
            box-sizing: border-box;
            overflow-x: hidden;
        }

        @media (max-width: 1200px) {
            .main-container {
                grid-template-columns: 250px minmax(0, 1fr) 250px;
            }
        }

        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 240px minmax(0, 1fr);
            }
            .right-sidebar { display: none !important; }
        }

        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
            }
            .left-sidebar { display: none !important; }
            .right-sidebar { display: none !important; }
        }

        /* Left Sidebar */
        .left-sidebar {
            position: sticky;
            top: 56px;
            height: calc(100vh - 56px);
            overflow-y: auto;
            padding: 16px 8px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s;
            text-decoration: none;
            color: var(--fb-text-primary);
            font-size: 15px;
            font-weight: 600;
        }

        .sidebar-item:hover {
            background: #e4e6eb;
        }

        .sidebar-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--fb-btn-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--fb-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }

        /* Center Column (Feed) */
        .feed-container {
            padding: 20px 16px;
            max-width: 680px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* White Cards */
        .fb-card {
            background: var(--fb-card);
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--fb-border);
        }

        /* Stories Carousel */
        .stories-container {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: none;
        }

        .stories-container::-webkit-scrollbar { display: none; }

        .story-card {
            width: 120px;
            height: 200px;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            flex-shrink: 0;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
            background: #e4e6eb;
        }

        .story-card:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-md);
        }

        /* Create Story Card */
        .create-story-card {
            background: white;
            display: flex;
            flex-direction: column;
        }

        .create-story-top {
            height: 140px;
            background: linear-gradient(135deg, #1877f2, #00c6ff);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 800;
        }

        .create-story-bottom {
            height: 60px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding-top: 10px;
        }

        .add-story-plus {
            position: absolute;
            top: -18px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--fb-primary);
            border: 3px solid white;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
        }

        .create-story-text {
            font-size: 12px;
            font-weight: 700;
            color: var(--fb-text-primary);
        }

        /* Normal Story Card */
        .story-bg {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 10px;
            position: relative;
            background-size: cover;
            background-position: center;
        }

        .story-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0) 50%, rgba(0,0,0,0.3) 100%);
        }

        .story-author-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 3px solid var(--fb-primary);
            background: #3b5998;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            position: relative;
            z-index: 2;
        }

        .story-author-name {
            color: white;
            font-size: 13px;
            font-weight: 700;
            position: relative;
            z-index: 2;
            text-shadow: 0 1px 2px rgba(0,0,0,0.8);
        }

        /* Post Creator Card (Facebook Style) */
        .create-post-card {
            padding: 14px 16px 10px;
        }

        .create-post-top {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--fb-border);
        }

        .create-post-trigger {
            flex: 1;
            height: 42px;
            background: var(--fb-bg);
            border-radius: 50px;
            display: flex;
            align-items: center;
            padding: 0 16px;
            color: var(--fb-text-secondary);
            font-size: 15px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .create-post-trigger:hover {
            background: #e4e6eb;
        }

        .create-post-actions {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            padding-top: 8px;
        }

        .action-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: var(--fb-text-secondary);
            transition: background 0.15s;
        }

        .action-item:hover {
            background: var(--fb-hover);
        }

        /* Post Card (Facebook Style) */
        .post-card {
            display: flex;
            flex-direction: column;
        }

        .post-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
        }

        .post-author-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .post-author-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--fb-text-primary);
            text-decoration: none;
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--fb-text-secondary);
        }

        .post-body {
            padding: 4px 16px 12px;
            font-size: 15px;
            line-height: 1.55;
            color: var(--fb-text-primary);
            word-break: break-word;
        }

        /* Reactions Count Bar */
        .reactions-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            font-size: 14px;
            color: var(--fb-text-secondary);
            border-bottom: 1px solid var(--fb-border);
            margin: 0 4px;
        }

        .reaction-icons {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .reaction-bubble {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: white;
        }

        .like-bubble { background: var(--fb-primary); }
        .love-bubble { background: #f02849; }

        /* Post Action Buttons */
        .post-buttons-bar {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            padding: 4px 8px;
        }

        .post-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: var(--fb-text-secondary);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
        }

        .post-btn:hover {
            background: var(--fb-hover);
        }

        .post-btn.liked {
            color: var(--fb-primary);
            font-weight: 700;
        }

        /* ------------------------------------------------------------- */
        /* POST MORE (•••) DROPDOWN MENU */
        /* ------------------------------------------------------------- */
        .jj-dropdown {
            display: none;
            position: absolute !important;
            top: 36px !important;
            right: 0 !important;
            width: 220px !important;
            background: #ffffff !important;
            border: 1px solid #e4e6eb !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16) !important;
            padding: 6px !important;
            z-index: 120 !important;
            flex-direction: column !important;
            animation: jjDropdownFade 0.15s ease-out !important;
        }

        .jj-dropdown.active,
        .jj-dropdown[style*="display: block"],
        .jj-dropdown[style*="display: flex"] {
            display: flex !important;
        }

        @keyframes jjDropdownFade {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .jj-dropdown-item {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 9px 12px !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            color: #050505 !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            transition: background 0.15s ease, color 0.15s ease !important;
            user-select: none !important;
            text-decoration: none !important;
        }

        .jj-dropdown-item:hover {
            background: #f2f2f2 !important;
        }

        .jj-dropdown-item svg {
            width: 17px !important;
            height: 17px !important;
            flex-shrink: 0 !important;
            stroke: currentColor !important;
        }

        [data-theme="dark"] .jj-dropdown {
            background: #242526 !important;
            border-color: #393a3b !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5) !important;
        }

        [data-theme="dark"] .jj-dropdown-item {
            color: #e4e6eb !important;
        }

        [data-theme="dark"] .jj-dropdown-item:hover {
            background: #3a3b3c !important;
        }

        /* ------------------------------------------------------------- */
        /* FACEBOOK-STYLE LIKE & REACTION POPOVER */
        /* ------------------------------------------------------------- */
        .jj-like-btn-wrapper {
            position: relative !important;
            flex: 1 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .jj-reaction-popover {
            position: absolute !important;
            bottom: calc(100% + 6px) !important;
            left: 4px !important;
            background: #ffffff !important;
            border-radius: 30px !important;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.18) !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            padding: 4px 8px !important;
            display: none !important; /* CRITICAL: Hidden until hover */
            align-items: center !important;
            gap: 6px !important;
            z-index: 100 !important;
            white-space: nowrap !important;
            pointer-events: none !important;
            animation: jjReactionPop 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
        }

        @keyframes jjReactionPop {
            from { opacity: 0; transform: translateY(6px) scale(0.8); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Hover reveals reaction popover */
        .jj-like-btn-wrapper:hover .jj-reaction-popover {
            display: flex !important;
            pointer-events: auto !important;
        }

        .jj-reaction-pop-item {
            font-size: 24px !important;
            line-height: 1 !important;
            cursor: pointer !important;
            transition: transform 0.15s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 36px !important;
            height: 36px !important;
            border-radius: 50% !important;
            user-select: none !important;
        }

        .jj-reaction-pop-item:hover {
            transform: scale(1.35) translateY(-4px) !important;
            background: rgba(0, 0, 0, 0.06) !important;
        }

        .jj-post-btn-wide {
            width: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            height: 36px !important;
            padding: 8px 12px !important;
            border-radius: 6px !important;
            border: none !important;
            background: transparent !important;
            color: var(--fb-text-secondary, #65676b) !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.15s !important;
        }

        .jj-post-btn-wide:hover {
            background: var(--fb-hover, #f2f2f2) !important;
        }

        .jj-post-btn-wide.liked {
            color: #1877f2 !important;
        }

        .jj-post-btn-wide.liked svg {
            stroke: #1877f2 !important;
            fill: rgba(24, 119, 242, 0.12) !important;
        }

        .jj-inline-comments {
            border-top: 1px solid var(--fb-border, #e4e6eb) !important;
            border-radius: 0 0 10px 10px !important;
        }

        [data-theme="dark"] .jj-reaction-popover {
            background: #242526 !important;
            border-color: #393a3b !important;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.6) !important;
        }

        #messengerChatUserLink {
            cursor: pointer !important;
            border-radius: 8px;
            padding: 2px 4px;
            transition: background 0.15s ease !important;
        }
        #messengerChatUserLink:hover {
            background: rgba(0, 0, 0, 0.05) !important;
        }
        #messengerChatUserLink:hover #messengerChatTitle {
            color: var(--fb-primary) !important;
            text-decoration: underline !important;
        }
        #messengerChatUserLink:hover #messengerChatAvatar {
            transform: scale(1.06) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
        }
        [data-theme="dark"] #messengerChatUserLink:hover {
            background: rgba(255, 255, 255, 0.08) !important;
        }

        /* Right Sidebar (Contacts / Messenger) */
        .right-sidebar {
            position: sticky;
            top: 56px;
            height: calc(100vh - 56px);
            overflow-y: auto;
            padding: 16px 8px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .sidebar-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 8px;
            font-size: 16px;
            font-weight: 700;
            color: var(--fb-text-secondary);
        }

        .contact-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .contact-row:hover {
            background: #e4e6eb;
        }

        .contact-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .online-dot {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #22c55e;
            border: 2px solid white;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08);
        }

        .contact-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--fb-text-primary);
            line-height: 1.25;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .contact-status-text {
            font-size: 11.5px;
            color: var(--fb-text-secondary);
            display: flex;
            align-items: center;
            min-height: 18px;
        }

        .contact-online-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            color: #15803d;
            background: rgba(34, 197, 94, 0.12);
            padding: 1.5px 7px 1.5px 6px;
            border-radius: 10px;
            line-height: 1.2;
            letter-spacing: -0.1px;
            border: 1px solid rgba(34, 197, 94, 0.2);
            transition: all 0.2s ease;
        }

        [data-theme="dark"] .contact-online-badge {
            color: #4ade80;
            background: rgba(34, 197, 94, 0.18);
            border-color: rgba(34, 197, 94, 0.3);
        }

        .online-pulse-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 1.5px rgba(34, 197, 94, 0.35);
            display: inline-block;
            animation: onlineStatusPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes onlineStatusPulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 1.5px rgba(34, 197, 94, 0.35);
            }
            50% {
                transform: scale(1.2);
                box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
            }
        }

        /* ------------------------------------------------------------- */
        /* TOP HEADER MESSENGER DROPDOWN */
        /* ------------------------------------------------------------- */
        .top-messenger-dropdown {
            position: absolute;
            right: 0;
            top: 48px;
            width: 380px;
            max-width: calc(100vw - 24px);
            background: var(--fb-card);
            border-radius: 14px;
            box-shadow: 0 12px 28px 0 rgba(0, 0, 0, 0.2), 0 2px 4px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid var(--fb-border);
            z-index: 150;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: popIn 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .top-messenger-header {
            padding: 12px 16px 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .top-messenger-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--fb-text-primary);
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .top-messenger-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .top-messenger-action-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--fb-text-secondary);
            background: var(--fb-hover);
            text-decoration: none;
            transition: all 0.15s;
        }

        .top-messenger-action-icon:hover {
            color: #0084ff;
            background: rgba(0, 132, 255, 0.12);
        }

        .top-messenger-search-box {
            padding: 4px 16px 10px;
        }

        .top-messenger-search-input-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--fb-hover);
            border-radius: 20px;
            padding: 8px 14px;
            border: 1px solid transparent;
            transition: border-color 0.2s, background 0.2s;
        }

        .top-messenger-search-input-wrapper:focus-within {
            background: var(--fb-card);
            border-color: #0084ff;
            box-shadow: 0 0 0 2px rgba(0, 132, 255, 0.15);
        }

        .top-messenger-search-input-wrapper input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            font-size: 13px;
            color: var(--fb-text-primary);
        }

        .top-messenger-search-input-wrapper input::placeholder {
            color: var(--fb-text-secondary);
        }

        /* Active Friends Horizontal Section */
        .top-messenger-active-section {
            border-bottom: 1px solid var(--fb-border);
            padding: 8px 16px 10px;
        }

        .top-messenger-section-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--fb-text-secondary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .top-messenger-active-list {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: thin;
        }

        .top-messenger-active-list::-webkit-scrollbar {
            height: 4px;
        }

        .top-messenger-active-list::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.15);
            border-radius: 4px;
        }

        .top-messenger-active-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            flex-shrink: 0;
            width: 58px;
            text-align: center;
            transition: transform 0.15s;
        }

        .top-messenger-active-item:hover {
            transform: translateY(-2px);
        }

        .top-messenger-active-avatar-wrap {
            position: relative;
            width: 44px;
            height: 44px;
        }

        .top-messenger-active-avatar-wrap img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--fb-card);
        }

        .top-messenger-active-avatar-wrap .active-online-dot {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 12px;
            height: 12px;
            background: #22c55e;
            border: 2px solid var(--fb-card);
            border-radius: 50%;
            box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.4);
        }

        .top-messenger-active-name {
            font-size: 11px;
            font-weight: 600;
            color: var(--fb-text-primary);
            max-width: 58px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Recent Conversations List */
        .top-messenger-conversations-section {
            padding: 8px 10px 4px;
            max-height: 330px;
            overflow-y: auto;
        }

        .top-messenger-conv-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.15s;
            position: relative;
        }

        .top-messenger-conv-item:hover {
            background: var(--fb-hover);
        }

        .top-messenger-conv-avatar-wrap {
            position: relative;
            width: 46px;
            height: 46px;
            flex-shrink: 0;
        }

        .top-messenger-conv-avatar-wrap img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .top-messenger-conv-avatar-wrap .conv-online-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 12px;
            height: 12px;
            background: #22c55e;
            border: 2px solid var(--fb-card);
            border-radius: 50%;
        }

        .top-messenger-conv-body {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .top-messenger-conv-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .top-messenger-conv-name {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--fb-text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .top-messenger-conv-time {
            font-size: 11px;
            color: var(--fb-text-secondary);
            flex-shrink: 0;
            margin-left: 6px;
        }

        .top-messenger-conv-msg-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .top-messenger-conv-last-msg {
            font-size: 12.5px;
            color: var(--fb-text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .top-messenger-conv-item.unread .top-messenger-conv-name {
            color: #0084ff;
        }

        .top-messenger-conv-item.unread .top-messenger-conv-last-msg {
            color: var(--fb-text-primary);
            font-weight: 700;
        }

        .top-messenger-unread-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #0084ff;
            flex-shrink: 0;
        }

        .top-messenger-unread-pill {
            background: #0084ff;
            color: white;
            font-size: 11px;
            font-weight: 800;
            border-radius: 10px;
            padding: 1px 7px;
            flex-shrink: 0;
        }

        .top-messenger-footer {
            padding: 11px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            color: #0084ff;
            border-top: 1px solid var(--fb-border);
            text-decoration: none;
            background: var(--fb-card);
            transition: background 0.15s;
            display: block;
        }

        .top-messenger-footer:hover {
            background: var(--fb-hover);
            text-decoration: underline;
        }

        [data-theme="dark"] .top-messenger-dropdown {
            background: #242526 !important;
            border-color: #393a3b !important;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.6) !important;
        }

        [data-theme="dark"] .top-messenger-search-input-wrapper {
            background: #3a3b3c !important;
        }

        [data-theme="dark"] .top-messenger-conv-item:hover,
        [data-theme="dark"] .top-messenger-footer:hover {
            background: #3a3b3c !important;
        }

        /* ------------------------------------------------------------- */
        /* MODAL FOR CREATING POST */
        /* ------------------------------------------------------------- */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-box {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: popIn 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            from { transform: scale(0.92); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 16px;
            border-bottom: 1px solid var(--fb-border);
        }

        .modal-title {
            font-size: 18px;
            font-weight: 700;
        }

        .modal-close {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--fb-btn-bg);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
        }

        .modal-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .modal-textarea {
            width: 100%;
            min-height: 120px;
            border: none;
            outline: none;
            font-size: 18px;
            color: var(--fb-text-primary);
            resize: none;
        }

        .modal-btn-submit {
            background: var(--fb-primary);
            color: white;
            border: none;
            border-radius: 8px;
            height: 42px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }

        .modal-btn-submit:hover {
            background: var(--fb-primary-hover);
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 24px;
            left: 24px;
            background: #242526;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Cockpit Overlay / Section */
        #cockpitSection {
            max-width: 1200px;
            margin: 20px auto;
            width: 100%;
            padding: 0 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        /* Enterprise Community & Groups V2 Styles */
        .category-pill {
            background: white;
            border: 1px solid var(--fb-border);
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .category-pill:hover, .category-pill.active {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }
        .community-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
            border-color: rgba(79, 70, 229, 0.35) !important;
        }

        /* Enterprise Reel Studio Layout & Responsive Overrides */
        .modal-box.reel-studio-modal {
            max-width: 860px !important;
            width: 95vw !important;
            max-height: calc(100dvh - 24px) !important;
            border-radius: 18px !important;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35) !important;
            overflow-y: auto !important;
        }
        .reel-studio-layout {
            display: grid !important;
            grid-template-columns: 300px 1fr !important;
            gap: 16px !important;
            align-items: start !important;
        }
        /* Hide preview box completely when no video or camera is active */
        .reel-studio-layout:not(.has-media) .reel-studio-preview-box {
            display: none !important;
        }
        .reel-studio-layout:not(.has-media) {
            display: block !important;
            max-width: 520px !important;
            margin: 0 auto !important;
        }
        @media (max-width: 820px) {
            .reel-studio-layout {
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
            }
            .reel-studio-preview-box {
                height: 180px !important;
                max-height: 25vh !important;
                border-radius: 12px !important;
                width: 100% !important;
            }
        }

        /* ------------------------------------------------------------- */
        /* FEED POST CARD — PROFESSIONAL COMPACT LAYOUT (scoped to feed)  */
        /* ------------------------------------------------------------- */
        #feedPostsStream { gap: 14px !important; }

        #feedPostsStream .post-card {
            padding: 0 !important;
            gap: 0 !important;
            border: none !important;
            border-radius: 14px !important;
            background: var(--fb-card, #fff) !important;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.06), 0 1px 3px rgba(16, 24, 40, 0.08) !important;
            transition: box-shadow 0.2s ease, transform 0.2s ease !important;
            animation: jjPostFadeIn 0.3s ease-out backwards;
        }
        #feedPostsStream .post-card:hover {
            box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08), 0 2px 4px rgba(16, 24, 40, 0.06) !important;
        }
        @keyframes jjPostFadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Pinned badge */
        #feedPostsStream .post-card > div:first-child:not(.post-header) {
            padding: 12px 16px 0 !important;
            margin: 0 !important;
        }

        /* Header */
        #feedPostsStream .post-header {
            padding: 14px 16px 10px !important;
            align-items: flex-start !important;
        }
        #feedPostsStream .post-author-info { gap: 10px !important; min-width: 0; }
        #feedPostsStream .post-author-info .avatar {
            width: 42px !important;
            height: 42px !important;
            box-shadow: 0 0 0 2px var(--fb-card, #fff), 0 0 0 3px rgba(24, 119, 242, 0.18) !important;
            transition: transform 0.15s ease !important;
        }
        #feedPostsStream .post-author-info a:hover .avatar { transform: scale(1.05); }
        #feedPostsStream .post-author-name { font-size: 15px !important; line-height: 1.3 !important; }
        #feedPostsStream .post-author-name:hover { color: var(--fb-primary) !important; text-decoration: none !important; }
        #feedPostsStream .post-meta { margin-top: 2px !important; font-size: 12.5px !important; }
        #feedPostsStream .post-header .circle-btn {
            width: 34px !important;
            height: 34px !important;
            border: none !important;
            border-radius: 50% !important;
            background: transparent !important;
            color: var(--fb-text-secondary) !important;
            transition: background 0.15s ease !important;
        }
        #feedPostsStream .post-header .circle-btn:hover { background: var(--fb-hover) !important; color: var(--fb-text-primary) !important; }

        /* Body — white-space normal kills the template-indentation blank gaps */
        #feedPostsStream .post-body {
            padding: 0 16px 12px !important;
            white-space: normal !important;
            font-size: 15px !important;
            line-height: 1.55 !important;
        }
        #feedPostsStream [id^="post-body-text-"] {
            font-size: 15px !important;
            line-height: 1.6 !important;
            color: var(--fb-text-primary) !important;
            white-space: pre-line !important;
        }
        #feedPostsStream [id^="post-body-text-"]:empty { display: none !important; }
        #feedPostsStream .post-body img,
        #feedPostsStream .post-body video { border-radius: 10px; }
        #feedPostsStream .post-body > div[style*="margin-top: 12px"],
        #feedPostsStream .post-body > a[style*="margin-top: 12px"] { border-radius: 12px !important; }

        /* Reactions summary row — single subtle divider */
        #feedPostsStream .reactions-bar {
            margin: 0 16px !important;
            padding: 8px 0 !important;
            border-top: none !important;
            border-bottom: 1px solid var(--fb-border, #eef0f3) !important;
            font-size: 13px !important;
        }
        #feedPostsStream .reaction-bubble {
            width: 20px !important;
            height: 20px !important;
            font-size: 11px !important;
            border: 2px solid var(--fb-card, #fff) !important;
            margin-right: -6px !important;
        }
        #feedPostsStream .reactions-bar [id^="reactions-count-"] { margin-left: 8px !important; }
        #feedPostsStream .reactions-bar span[onclick]:hover { text-decoration: underline; color: var(--fb-text-primary); }

        /* Action buttons — no border, compact */
        #feedPostsStream .post-buttons-bar {
            display: flex !important;
            gap: 4px !important;
            padding: 4px 8px !important;
            border: none !important;
        }
        #feedPostsStream .post-buttons-bar .post-btn {
            width: 100%;
            height: 38px !important;
            padding: 0 8px !important;
            border: none !important;
            border-radius: 8px !important;
            font-size: 14px !important;
            color: var(--fb-text-secondary) !important;
            background: transparent !important;
            transition: background 0.15s ease, color 0.15s ease, transform 0.1s ease !important;
        }
        #feedPostsStream .post-buttons-bar .post-btn:hover { background: var(--fb-hover) !important; color: var(--fb-text-primary) !important; }
        #feedPostsStream .post-buttons-bar .post-btn:active { transform: scale(0.97); }
        #feedPostsStream .post-buttons-bar .post-btn.liked { color: var(--fb-primary) !important; }

        /* Inline comments drawer */
        #feedPostsStream .jj-inline-comments {
            margin: 0 !important;
            border-top: 1px solid var(--fb-border, #eef0f3) !important;
            border-radius: 0 0 14px 14px !important;
            background: transparent !important;
        }

        @media (max-width: 600px) {
            #feedPostsStream .post-card { border-radius: 14px !important; }
            #feedPostsStream .post-header { padding: 12px 12px 8px !important; }
            #feedPostsStream .post-body { padding: 0 12px 10px !important; }
            #feedPostsStream .reactions-bar { margin: 0 12px !important; }
            #feedPostsStream .post-buttons-bar .post-btn span { font-size: 13px !important; }
        }
    </style>
</head>
<body>

    <!-- --------------------------------------------------------- -->
    <!-- 1. TOP NAVIGATION HEADER (FACEBOOK STYLE) -->
    <!-- --------------------------------------------------------- -->
    <header>
        <!-- Left: Logo & Search -->
        <div class="header-left">
            <a href="/" class="fb-logo-brand" title="Bondhoo" style="text-decoration: none; display: flex; align-items: center; flex-shrink: 0;">
                <img src="/images/bondhoo-logo.png" alt="Bondhoo" class="bondhoo-main-brand-logo" style="height: 36px; max-width: 165px; width: auto; object-fit: contain; display: block;">
            </a>
            <div class="search-box desktop-only-search" style="position: relative;">
                <span class="search-icon" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); display: flex; align-items: center; color: var(--fb-text-secondary); pointer-events: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" class="search-input" id="globalSearchInput" placeholder="Bondhoo-তে অনুসন্ধান করুন..." autocomplete="off">
                <div id="searchResultsDropdown" style="display: none; position: absolute; top: 44px; left: 0; width: 340px; background: white; border-radius: 8px; box-shadow: var(--shadow-lg); border: 1px solid var(--fb-border); z-index: 1000; max-height: 400px; overflow-y: auto;"></div>
            </div>
        </div>

        <!-- Center: Facebook Tabs -->
        <div class="header-center">
            <a href="javascript:void(0)" class="nav-tab active" id="tab-feed" title="হোম ফিড" onclick="switchMainTab('feed')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </a>
            <a href="javascript:void(0)" class="nav-tab" id="tab-friends" title="বন্ধুরা" onclick="openFriendsModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </a>
            <a href="javascript:void(0)" class="nav-tab" id="tab-watch" title="ভিডিও ও লাইভ স্ট্রিম" onclick="switchMainTab('watch')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <polygon points="10 8 16 10 10 12 10 8" fill="currentColor"></polygon>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
            </a>
            <a href="javascript:void(0)" class="nav-tab" id="tab-marketplace" title="মার্কেটপ্লেস" onclick="switchMainTab('marketplace')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
            </a>
            <a href="javascript:void(0)" class="nav-tab" id="tab-groups" title="কমিউনিটি ও গ্রুপ" onclick="switchMainTab('groups')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </a>
        </div>

        <!-- Right: Actions & Profile -->
        <div class="header-right" style="position: relative;">
            <!-- Mobile Search Circular Icon Button (Visible on mobile screens) -->
            <button type="button" class="circle-btn mobile-header-search-btn" id="mobileHeaderSearchBtn" title="অনুসন্ধান" onclick="openMobileSearchModal()" style="display: none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>

            <!-- Global Feed Refresh Button (No Full Page Reload) -->
            <button class="circle-btn desktop-only-action" id="globalFeedRefreshBtn" title="ফিড ও ডাটা রিফ্রেশ করুন" onclick="triggerFeedRefresh(this)" style="position: relative;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
            </button>

            <!-- Floating Messenger Dropdown Menu -->
            <div style="position: relative;" id="topMessengerContainer">
                <button type="button" class="circle-btn mobile-header-messenger-btn" id="topMessengerBtn" title="মেসেঞ্জার ও চ্যাটসমূহ" onclick="toggleMessengerDropdown(event)" style="position: relative;">
                    <svg width="22" height="22" viewBox="0 0 28 28" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M14 2C7.373 2 2 7.155 2 13.518c0 3.626 1.745 6.862 4.475 8.974V26l3.37-1.85c1.28.355 2.646.549 4.155.549 6.627 0 12-5.155 12-11.518C26 7.155 20.627 2 14 2zm1.203 15.534l-3.08-3.284-6.012 3.284 6.613-7.02 3.155 3.284 5.937-3.284-6.613 7.02z" fill="url(#dashMessengerGrad)"/>
                        <defs>
                            <linearGradient id="dashMessengerGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#0078FF"/>
                                <stop offset="70%" stop-color="#00C6FF"/>
                                <stop offset="100%" stop-color="#00E5FF"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <span id="topMessengerBadge" class="header-badge-count red" style="display: none; position: absolute; top: -3px; right: -3px; background: #ef4444; color: white; border-radius: 50%; min-width: 18px; height: 18px; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; padding: 0 4px; border: 2px solid #ffffff;">0</span>
                </button>
                <div id="topMessengerDropdown" class="top-messenger-dropdown" style="display: none;">
                    <div class="top-messenger-header">
                        <span class="top-messenger-title">
                            চ্যাটসমূহ
                        </span>
                        <div class="top-messenger-actions">
                            <a href="/messages" class="top-messenger-action-icon" title="মেসেঞ্জারে সম্পূর্ণ দেখুন">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                    <polyline points="15 3 21 3 21 9"></polyline>
                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <div class="top-messenger-search-box">
                        <div class="top-messenger-search-input-wrapper">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" style="color: var(--fb-text-secondary); flex-shrink: 0;">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" id="messengerDropdownSearchInput" placeholder="মেসেঞ্জার ও পরিচিতি খুঁজুন..." oninput="filterMessengerDropdown(this.value)">
                        </div>
                    </div>
                    <div class="top-messenger-active-section" id="messengerDropdownActiveSection">
                        <div class="top-messenger-section-label">
                            <span>সক্রিয় বন্ধুরা</span>
                            <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #16a34a; font-weight: 700;">
                                <span class="online-pulse-dot" style="width: 6px; height: 6px;"></span> অনলাইন
                            </span>
                        </div>
                        <div class="top-messenger-active-list" id="messengerDropdownActiveFriendsList">
                            <!-- Active friends rendered here -->
                        </div>
                    </div>
                    <div class="top-messenger-conversations-section" id="messengerDropdownConversationsList">
                        <div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px; padding: 24px 16px;">লোড হচ্ছে...</div>
                    </div>
                    <a href="/messages" class="top-messenger-footer">
                        সব বার্তা মেসেঞ্জারে দেখুন ➔
                    </a>
                </div>
            </div>

            <!-- Notifications Bell & Popover -->
            <div style="position: relative;">
                <button class="circle-btn mobile-header-notif-btn" id="topNotifBtn" title="নোটিফিকেশন" onclick="toggleNotificationsDropdown()" style="position: relative;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span id="notifBadge" class="header-badge-count dark" style="display: none; position: absolute; top: -3px; right: -3px; background: #0f172a; color: white; border-radius: 50%; min-width: 18px; height: 18px; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; padding: 0 4px; border: 2px solid #ffffff;">0</span>
                </button>
                <div id="notificationsDropdown" style="display: none; position: absolute; right: 0; top: 48px; width: 340px; background: white; border-radius: 12px; box-shadow: var(--shadow-lg); border: 1px solid var(--fb-border); z-index: 150; max-height: 420px; overflow-y: auto;">
                    <div style="padding: 12px 16px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 800; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                            নোটিফিকেশন
                        </span>
                        <div style="display: flex; gap: 8px;">
                            <a href="/notifications" style="font-size: 12px; font-weight: 700; color: var(--fb-primary); text-decoration: none;">সব দেখুন</a>
                            <button onclick="markAllNotificationsRead()" style="background: none; border: none; color: var(--fb-primary); font-size: 12px; font-weight: 700; cursor: pointer;">সব পঠিত</button>
                        </div>
                    </div>
                    <div id="notificationsList" style="padding: 6px;">
                        <div style="text-align: center; color: var(--fb-text-secondary); padding: 16px; font-size: 13px;">লোড হচ্ছে...</div>
                    </div>
                </div>
            </div>

            <!-- User Avatar & Dropdown Menu -->
            <div style="position: relative;">
                <div style="display: flex; align-items: center; gap: 8px; cursor: pointer;" onclick="toggleUserDropdown()">
                    <div class="avatar mobile-header-avatar" id="navUserAvatar" style="position: relative;">
                        <img src="/images/default-avatar.svg" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="User">
                        <span class="avatar-online-dot" style="position: absolute; bottom: 0; right: 0; width: 11px; height: 11px; background: #22c55e; border: 2px solid #ffffff; border-radius: 50%;"></span>
                    </div>
                </div>
                <div id="userMenuDropdown" style="display: none; position: absolute; right: 0; top: 48px; width: 270px; background: white; border-radius: 12px; box-shadow: var(--shadow-lg); border: 1px solid var(--fb-border); z-index: 150; padding: 8px;">
                    <div style="display: flex; gap: 10px; align-items: center; padding: 8px; border-radius: 8px; cursor: pointer; border-bottom: 1px solid var(--fb-border); margin-bottom: 6px;" onclick="goToMyProfile(); toggleUserDropdown();">
                        <div class="avatar" style="width: 40px; height: 40px;" id="dropdownUserAvatar"><img src="/images/default-avatar.svg" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="User"></div>
                        <div>
                            <div style="font-weight: 700; font-size: 14px;" id="dropdownUserName">আমার প্রোফাইল</div>
                            <div style="font-size: 12px; color: var(--fb-text-secondary);">সম্পূর্ণ প্রোফাইল দেখুন</div>
                        </div>
                    </div>
                    <div class="sidebar-item" style="padding: 8px 10px; font-size: 14px;" onclick="openUserProfileModal(); toggleUserDropdown();">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--fb-text-secondary);"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        <span>প্রোফাইল সেটিংস ও অপশনসমূহ</span>
                    </div>
                    <div class="sidebar-item" style="padding: 8px 10px; font-size: 14px;" onclick="openFriendsModal(); toggleUserDropdown();">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--fb-text-secondary);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span>ফ্রেন্ডস ও রিকোয়েস্ট</span>
                    </div>
                    <div class="sidebar-item" style="padding: 8px 10px; font-size: 14px;" onclick="toggleDarkMode(); toggleUserDropdown();">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--fb-text-secondary);"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                        <span id="themeToggleText">ডার্ক মোড পরিবর্তন</span>
                    </div>
                    <div style="height: 1px; background: var(--fb-border); margin: 6px 0;"></div>
                    <div class="sidebar-item" style="padding: 8px 10px; font-size: 14px; color: #ef4444;" onclick="logout(); toggleUserDropdown();">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        <span>লগআউট করুন</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- 1.1 MOBILE SUB-NAVIGATION BAR (DIRECTLY BELOW HEADER) -->
    <nav class="mobile-subnav-bar" id="bondhooMobileSubnavBar" aria-label="মোবাইল সাব-নেভিগেশন">
        <button type="button" class="mobile-subnav-item active" id="subnav-feed" onclick="switchMainTab('feed')" title="নিউজ ফিড">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9.5L12 3l9 6.5V20a1.5 1.5 0 0 1-1.5 1.5h-5A1.5 1.5 0 0 1 13 20v-5h-2v5a1.5 1.5 0 0 1-1.5 1.5h-5A1.5 1.5 0 0 1 3 20V9.5z"/>
            </svg>
            <span>নিউজ ফিড</span>
        </button>
        <button type="button" class="mobile-subnav-item" id="subnav-watch" onclick="switchMainTab('watch')" title="ভিডিও/রিলস">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                <polygon points="10 8 16 10 10 12 10 8" fill="currentColor"/>
                <line x1="8" y1="21" x2="16" y2="21"/>
                <line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            <span>ভিডিও/রিলস</span>
        </button>
        <button type="button" class="mobile-subnav-item" id="subnav-groups" onclick="switchMainTab('groups')" title="গ্রুপস">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span>গ্রুপস</span>
        </button>
        <button type="button" class="mobile-subnav-item" id="subnav-saved" onclick="openSavedPostsModal()" title="সেভ করা">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
            </svg>
            <span>সেভ করা</span>
        </button>
        <button type="button" class="mobile-subnav-item mobile-menu-toggle-btn" id="subnav-menu" onclick="openMobileMenuDrawer()" title="মেনু">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                <line x1="4" y1="6" x2="20" y2="6"/>
                <line x1="4" y1="12" x2="20" y2="12"/>
                <line x1="4" y1="18" x2="20" y2="18"/>
            </svg>
        </button>
    </nav>

    <!-- --------------------------------------------------------- -->
    <!-- 2. SOCIAL NETWORK USER DASHBOARD (3 COLUMNS) -->
    <!-- --------------------------------------------------------- -->
    <div id="userDashboardView" class="main-container">
        
        <!-- Left Sidebar: Shortcuts -->
        <aside class="left-sidebar">
            <a href="javascript:void(0)" class="sidebar-item" style="padding: 6px 12px;" onclick="goToMyProfile()">
                <div class="avatar" style="width: 36px; height: 36px;" id="sidebarUserAvatar"><img src="/images/default-avatar.svg" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="User"></div>
                <span id="sidebarUserName" style="font-weight: 700;">আমার প্রোফাইল</span>
            </a>

            <a href="javascript:void(0)" class="sidebar-item" onclick="switchMainTab('feed')">
                <div class="sidebar-icon" style="color: #1877f2; background: rgba(24, 119, 242, 0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                </div>
                <span>হোম ফিড</span>
            </a>

            <a href="javascript:void(0)" class="sidebar-item" onclick="openFriendsModal()">
                <div class="sidebar-icon" style="color: #1877f2; background: rgba(24, 119, 242, 0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <span style="flex: 1;">বন্ধুরা (Friends)</span>
                <span id="sidebarFriendBadge" class="sidebar-badge" style="display: none;">0</span>
            </a>

            <a href="/messages" class="sidebar-item">
                <div class="sidebar-icon" style="color: #0084ff; background: rgba(0, 132, 255, 0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                </div>
                <span style="flex: 1;">মেসেঞ্জার (Messages)</span>
                <span id="sidebarMsgBadge" class="sidebar-badge" style="display: none;">0</span>
            </a>

            <a href="javascript:void(0)" class="sidebar-item" onclick="openSavedPostsModal()">
                <div class="sidebar-icon" style="color: #8b5cf6; background: rgba(139, 92, 246, 0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                </div>
                <span style="flex: 1;">সংরক্ষিত (Saved)</span>
                <span id="sidebarSavedBadge" class="sidebar-badge" style="display: none;">0</span>
            </a>

            <a href="/groups" class="sidebar-item">
                <div class="sidebar-icon" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <span>গ্রুপসমূহ (Groups)</span>
            </a>

            <a href="/pages" class="sidebar-item">
                <div class="sidebar-icon" style="color: #f43f5e; background: rgba(244, 63, 94, 0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                </div>
                <span>পেজসমূহ (Pages)</span>
            </a>

            <a href="javascript:void(0)" class="sidebar-item" onclick="switchMainTab('watch')">
                <div class="sidebar-icon" style="color: #ef4444; background: rgba(239, 68, 68, 0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><polygon points="10 8 16 10 10 12 10 8" fill="currentColor"></polygon><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                </div>
                <span>ওয়াচ ও লাইভ (Watch)</span>
            </a>

            <a href="javascript:void(0)" class="sidebar-item" onclick="switchMainTab('marketplace')">
                <div class="sidebar-icon" style="color: #06b6d4; background: rgba(6, 182, 212, 0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                </div>
                <span>মার্কেটপ্লেস (Marketplace)</span>
            </a>

            <a href="javascript:void(0)" class="sidebar-item" onclick="openMemoriesModal()">
                <div class="sidebar-icon" style="color: #f59e0b; background: rgba(245, 158, 11, 0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
                <span>স্মৃতিচারণ (Memories)</span>
            </a>

            <a href="javascript:void(0)" class="sidebar-item" onclick="openEventsModal()">
                <div class="sidebar-icon" style="color: #ec4899; background: rgba(236, 72, 153, 0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <span>অনুষ্ঠানসমূহ (Events)</span>
            </a>

            <a href="javascript:void(0)" class="sidebar-item" onclick="openUserProfileModal()">
                <div class="sidebar-icon" style="color: #64748b; background: rgba(100, 116, 139, 0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                </div>
                <span>সেটিংস ও প্রোফাইল</span>
            </a>

            <div style="height: 1px; background: var(--fb-border); margin: 8px 12px;"></div>

            <div style="font-size: 13px; color: var(--fb-text-secondary); padding: 8px 12px; line-height: 1.6;">
                গোপনীয়তা · শর্তাবলী · বিজ্ঞাপন · কুকিজ · Bondhoo © {{ date('Y') }}
            </div>
        </aside>

        <!-- Center Dynamic Column (Feeds / Watch / Marketplace / Groups) -->
        <div class="feed-container">
            
            <!-- SECTION A: HOME FEED -->
            <div id="feedTabSection" style="display: flex; flex-direction: column; gap: 16px;">
                <!-- Enterprise Stories & Reels Top Switcher -->
                <div class="story-reel-switcher">
                    <button type="button" class="sr-tab-btn active" id="srTabStories" onclick="JugajugMediaSuite.switchTab('stories')">
                        <span class="sr-tab-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="3" width="12" height="18" rx="3" stroke="currentColor" stroke-width="2"/>
                                <rect x="9" y="3" width="12" height="18" rx="3" stroke="currentColor" stroke-width="2" fill="currentColor" fill-opacity="0.15"/>
                            </svg>
                        </span>
                        <span>স্টোরিজ (Stories)</span>
                    </button>
                    <button type="button" class="sr-tab-btn" id="srTabReels" onclick="JugajugMediaSuite.switchTab('reels')">
                        <span class="sr-tab-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <rect x="2" y="2" width="20" height="20" rx="3.5" stroke="currentColor" stroke-width="2"/>
                                <path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <polygon points="10 9 15 12 10 15 10 9" fill="currentColor"/>
                            </svg>
                        </span>
                        <span>রিলস (Reels)</span>
                    </button>
                </div>

                <!-- Stories Carousel Wrapper -->
                <div class="stories-wrapper-rel" id="srStoriesWrapper">
                    <div class="stories-carousel-container" id="storiesCarousel">
                        <!-- Populated by JugajugMediaSuite.loadStories() -->
                    </div>
                </div>

                <!-- Reels Carousel Wrapper -->
                <div class="stories-wrapper-rel" id="srReelsWrapper" style="display: none;">
                    <div class="stories-carousel-container" id="reelsCarousel">
                        <!-- Populated by JugajugMediaSuite.loadReels() -->
                    </div>
                </div>

                <!-- What's on your mind? Post Creator Card -->
                <div class="fb-card create-post-card">
                    <div class="create-post-row">
                        <div class="avatar create-post-avatar-wrap" id="createPostAvatar" onclick="goToMyProfile()" title="আমার প্রোফাইল">
                            <img src="/images/default-avatar.svg" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="User">
                        </div>
                        <div class="create-post-trigger" onclick="openCreatePostModal()" title="নতুন পোস্ট লিখুন">
                            <span id="createPostPlaceholder">আপনার মনে কী আছে?</span>
                        </div>
                        <div class="create-post-inline-actions">
                            <div class="post-inline-btn" onclick="openCreatePostModal('photo')" title="ছবি বা ভিডিও যুক্ত করুন">
                                <span class="post-inline-icon">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                        <rect width="20" height="20" x="2" y="2" rx="5" fill="url(#composerPhotoGrad)"/>
                                        <circle cx="8" cy="8" r="2.2" fill="#ffffff"/>
                                        <path d="M22 15.5l-6-6a1.5 1.5 0 0 0-2.12 0L3.5 19.8" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
                                        <defs>
                                            <linearGradient id="composerPhotoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#10b981"/>
                                                <stop offset="100%" stop-color="#059669"/>
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                </span>
                                <span class="post-inline-label">ছবি/ভিডিও</span>
                            </div>
                            <div class="post-inline-btn" onclick="openCreatePostModal('feeling')" title="অনুভূতি বা কার্যকলাপ যুক্ত করুন">
                                <span class="post-inline-icon">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" fill="#fbbf24"/>
                                        <circle cx="8.5" cy="9.5" r="1.5" fill="#78350f"/>
                                        <circle cx="15.5" cy="9.5" r="1.5" fill="#78350f"/>
                                        <path d="M7.5 14c1.2 2.2 2.8 3.2 4.5 3.2s3.3-1 4.5-3.2" stroke="#78350f" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <span class="post-inline-label">অনুভূতি</span>
                            </div>
                            <div class="post-inline-btn" onclick="openStartLiveFlow()" title="সরাসরি লাইভ শুরু করুন">
                                <span class="post-inline-icon">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" fill="url(#composerLiveGrad)"/>
                                        <circle cx="12" cy="12" r="3.5" fill="#ffffff"/>
                                        <path d="M7 7a7 7 0 0 1 10 0M5 5a10 10 0 0 1 14 0" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round"/>
                                        <defs>
                                            <linearGradient id="composerLiveGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#ef4444"/>
                                                <stop offset="100%" stop-color="#dc2626"/>
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                </span>
                                <span class="post-inline-label">লাইভ</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feed Posts Stream -->
                <div id="feedPostsStream" style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- Feed Cards populated via JS -->
                </div>
            </div>

            <!-- SECTION B: WATCH & LIVE STREAMING -->
            <div id="watchTabSection" style="display: none; flex-direction: column; gap: 16px;">
                <div class="fb-card" style="padding: 16px; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border-radius: 12px;">
                    <div>
                        <h2 style="font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                            <span>📺</span>
                            <span>Bondhoo লাইভ ও ভিডিও হাব</span>
                        </h2>
                        <p style="font-size: 13px; opacity: 0.92; margin-top: 2px;">বাস্তব সময়ের লাইভ সম্প্রচার দেখুন এবং বন্ধুদের সাথে সরাসরি যুক্ত হন</p>
                    </div>
                    <button class="post-btn" style="background: white; color: #ef4444; font-weight: 800; padding: 9px 18px; border-radius: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);" onclick="openStartLiveFlow()">🔴 গো লাইভ (Go Live)</button>
                </div>

                <!-- My Active Live Banner (if current user is live) -->
                <div id="myActiveLiveBanner" style="display: none; background: #fee2e2; border: 2px solid #ef4444; border-radius: 12px; padding: 14px 18px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444; animation: pulse 1s infinite;"></span>
                        <div>
                            <div style="font-weight: 800; font-size: 14px; color: #991b1b;">আপনার লাইভ সম্প্রচার বর্তমানে সক্রিয় আছে!</div>
                            <div style="font-size: 12px; color: #b91c1c;" id="myActiveLiveInfo">দর্শক সংযুক্ত আছেন</div>
                        </div>
                    </div>
                    <a href="{{ route('live.studio') }}" class="post-btn" style="background: #ef4444; color: white; text-decoration: none; font-weight: 700; padding: 7px 16px; border-radius: 8px; font-size: 13px;">🎥 লাইভ স্টুডিওতে ফিরুন</a>
                </div>

                <!-- Active Live Streams Grid Header -->
                <div class="sidebar-section-title" style="padding: 0 4px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 15px; font-weight: 700;">চলমান লাইভ সম্প্রচারসমূহ</span>
                    <button class="post-btn" style="font-size: 12px; padding: 4px 10px; border-radius: 6px;" onclick="loadLiveStreams()">🔄 রিফ্রেশ</button>
                </div>

                <!-- Active Streams Grid (Dynamically populated from backend) -->
                <div id="liveStreamsGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px;">
                    <!-- Dynamically populated or clean empty state -->
                </div>
            </div>

            <!-- SECTION C: MARKETPLACE -->
            <div id="marketplaceTabSection" style="display: none; flex-direction: column; gap: 16px;">
                <div class="fb-card" style="padding: 16px; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1877f2, #00c6ff); color: white;">
                    <div>
                        <h2 style="font-size: 20px; font-weight: 800;">🏪 Bondhoo মার্কেটপ্লেস ও এসক্রো কমার্স</h2>
                        <p style="font-size: 13px; opacity: 0.9;">১০০% নিরাপদ bKash/Nagad পেমেন্ট ও এসক্রো সিকিউরিটি</p>
                    </div>
                    <button class="post-btn" style="background: white; color: #1877f2; font-weight: 700; padding: 8px 16px; border-radius: 20px;" onclick="openCreateProductModal()">+ নতুন লিস্টিং তৈরি করুন</button>
                </div>

                <!-- Category Filter Pills -->
                <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px;" id="marketplaceCategoriesPills">
                    <!-- Dynamic categories -->
                </div>

                <!-- Search & Filters -->
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="marketplaceSearchInput" class="search-input" placeholder="মার্কেটপ্লেসে পণ্য খুঁজুন..." style="flex: 1; border: 1px solid var(--fb-border); background: white;" oninput="filterMarketplaceProducts()">
                </div>

                <!-- Products Grid -->
                <div id="marketplaceProductsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px;">
                    <!-- Dynamically populated -->
                </div>
            </div>

            <!-- SECTION D: ENTERPRISE COMMUNITY & GROUPS V2 -->
            <div id="groupsTabSection" style="display: none; flex-direction: column; gap: 16px;">
                <!-- Advanced Hero Banner -->
                <div class="fb-card" style="padding: 24px; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #312e81 0%, #4f46e5 50%, #0284c7 100%); color: white; border-radius: 14px; box-shadow: 0 8px 24px rgba(79, 70, 229, 0.25); flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 24px;">🌐</span>
                            <h2 style="font-size: 22px; font-weight: 800; letter-spacing: -0.5px;">Bondhoo কমিউনিটি ও গ্রুপস V2</h2>
                            <span style="background: rgba(255,255,255,0.2); font-size: 11px; padding: 2px 8px; border-radius: 12px; font-weight: 700;">ENTERPRISE OS</span>
                        </div>
                        <p style="font-size: 13.5px; opacity: 0.95; margin-top: 6px; max-width: 520px; line-height: 1.5;">
                            প্রযুক্তি, ক্যারিয়ার, ব্যবসা ও আঞ্চলিক বিষয়ে গঠিত স্বয়ংসম্পূর্ণ কমিউনিটি প্ল্যাটফর্ম। হেলথ স্কোর, প্রশ্নোত্তর ও রিসোর্স সুবিধা।
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <a href="/groups" class="post-btn" style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 700; padding: 10px 16px; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            🚀 ফুল কমিউনিটি হাব →
                        </a>
                        <button class="post-btn" style="background: white; color: #4f46e5; font-weight: 800; padding: 10px 18px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" onclick="openCreateGroupModal()">
                            ➕ নতুন কমিউনিটি
                        </button>
                    </div>
                </div>

                <!-- Search & Filter Controls -->
                <div class="fb-card" style="padding: 14px 18px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; border-radius: 12px;">
                    <div style="flex: 1; min-width: 220px; position: relative;">
                        <input type="text" id="groupDirectorySearch" class="search-input" placeholder="কমিউনিটির নাম, টপিক বা কি-ওয়ার্ড খুঁজুন..." style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px 9px 34px; font-size: 13.5px;" oninput="debounceGroupSearch()">
                        <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; opacity: 0.5;">🔍</span>
                    </div>

                    <select id="groupSortSelect" class="search-input" style="height: 38px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 10px; font-size: 13px;" onchange="loadGroups()">
                        <option value="trending">🔥 ট্রেন্ডিং ও সক্রিয়</option>
                        <option value="health">🩺 হেলথ স্কোর (স্বাস্থ্যকর)</option>
                        <option value="members">👥 সর্বাধিক সদস্য</option>
                        <option value="newest">✨ সম্প্রতি তৈরি</option>
                    </select>

                    <select id="groupTypeFilterSelect" class="search-input" style="height: 38px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 10px; font-size: 13px;" onchange="loadGroups()">
                        <option value="all">সকল কমিউনিটি মোড</option>
                        <option value="public">🌐 পাবলিক</option>
                        <option value="controlled">📑 নিয়ন্ত্রিত (Controlled)</option>
                        <option value="private">🔒 প্রাইভেট</option>
                        <option value="project">💼 প্রজেক্ট ও ওপেন সোর্স</option>
                        <option value="organization">🏛️ প্রতিষ্ঠান</option>
                    </select>
                </div>

                <!-- Category Pills Bar -->
                <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: none;" id="groupCategoriesPillsBar">
                    <button class="category-pill active" onclick="setGroupCategoryFilter('All', this)">সকল বিষয়</button>
                    <button class="category-pill" onclick="setGroupCategoryFilter('Technology', this)">💻 প্রযুক্তি ও কোডিং</button>
                    <button class="category-pill" onclick="setGroupCategoryFilter('Business', this)">🚀 স্টার্টআপ ও ক্যারিয়ার</button>
                    <button class="category-pill" onclick="setGroupCategoryFilter('Education', this)">📚 শিক্ষা ও গবেষণা</button>
                    <button class="category-pill" onclick="setGroupCategoryFilter('Entertainment', this)">🎬 বিনোদন ও সংস্কৃতি</button>
                    <button class="category-pill" onclick="setGroupCategoryFilter('Gaming', this)">🎮 গেমিং ও স্পোর্টস</button>
                    <button class="category-pill" onclick="setGroupCategoryFilter('Community', this)">🌍 সামাজিক ও স্থানীয়</button>
                </div>

                <!-- Community Grid / List -->
                <div id="groupsListContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px;">
                    <!-- Dynamically populated with enterprise cards -->
                </div>
            </div>

        </div>

        <!-- Right Sidebar: Contacts / Messenger -->
        <aside class="right-sidebar">
            <div class="sidebar-section-title" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px 6px;">
                <span style="font-weight: 700; font-size: 14px; color: var(--fb-text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">পরিচিতি ও চ্যাট</span>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button class="circle-btn" style="width: 30px; height: 30px;" onclick="fetchConversations()" title="রিফ্রেশ">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                    </button>
                    <a href="/messages" class="circle-btn" style="width: 30px; height: 30px; text-decoration: none;" title="সকল বার্তা দেখুন">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Contacts Search Box -->
            <div style="padding: 0 14px 10px;">
                <div style="position: relative;">
                    <input type="text" id="contactSearchInput" placeholder="পরিচিতি খুঁজুন..." style="width: 100%; height: 34px; background: var(--fb-bg); border: 1px solid var(--fb-border); border-radius: 20px; padding: 0 12px 0 32px; font-size: 13px; outline: none;" oninput="filterContacts(this.value)">
                    <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); display: flex; color: var(--fb-text-secondary); pointer-events: none;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                </div>
            </div>

            <div id="onlineContactsContainer" style="padding: 0 8px;">
                <div style="padding: 16px; font-size: 13px; color: var(--fb-text-secondary); text-align: center;">চ্যাট লোড হচ্ছে...</div>
            </div>
        </aside>

    </div>

    <!-- --------------------------------------------------------- -->
    <!-- 3. ADMIN COCKPIT / SRE DASHBOARD (TOGGLEABLE) -->
    <!-- --------------------------------------------------------- -->
    <div id="cockpitSection" style="display: none;">
        <div class="fb-card" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 20px; font-weight: 800;">⚡ এন্টারপ্রাইজ ইনফ্রাস্ট্রাকচার ও মনিটরিং ককপিট</h2>
                <button class="admin-toggle-btn" onclick="showUserFeed()">← সোশ্যাল ফিডে ফিরে যান</button>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px;">
                <div class="fb-card" style="padding: 16px; background: #f8fafc;">
                    <div style="font-size: 12px; color: var(--fb-text-secondary); font-weight: 700;">CPU LOAD (1m)</div>
                    <div style="font-size: 24px; font-weight: 800; color: var(--fb-primary);" id="admCpu">0.35</div>
                    <div style="font-size: 12px; color: var(--fb-text-secondary);">লোকাল ম্যাক সিপিইউ</div>
                </div>

                <div class="fb-card" style="padding: 16px; background: #f8fafc;">
                    <div style="font-size: 12px; color: var(--fb-text-secondary); font-weight: 700;">PHP MEMORY</div>
                    <div style="font-size: 24px; font-weight: 800;" id="admRam">6.2 MB</div>
                    <div style="font-size: 12px; color: var(--fb-text-secondary);">PHP 8.5 • JIT সক্রিয়</div>
                </div>

                <div class="fb-card" style="padding: 16px; background: #f8fafc;">
                    <div style="font-size: 12px; color: var(--fb-text-secondary); font-weight: 700;">DATABASE LATENCY</div>
                    <div style="font-size: 24px; font-weight: 800; color: #10b981;" id="admDb">0.02 ms</div>
                    <div style="font-size: 12px; color: var(--fb-text-secondary);">SQLite / MySQL Connection</div>
                </div>

                <div class="fb-card" style="padding: 16px; background: #f8fafc;">
                    <div style="font-size: 12px; color: var(--fb-text-secondary); font-weight: 700;">HORIZON QUEUES</div>
                    <div style="font-size: 24px; font-weight: 800;" id="admQueues">0 Pending</div>
                    <div style="font-size: 12px; color: var(--fb-text-secondary);">৮টি প্রায়োরিটি কিউ সক্রিয়</div>
                </div>
            </div>

            <!-- PHASE 11: ENTERPRISE AI, LIVE STREAMING, FEDERATION, SOC PANELS -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; margin-bottom: 20px;">
                <!-- AI Center -->
                <div class="fb-card" style="padding: 16px; border-left: 4px solid #1877f2;">
                    <div style="font-weight: 800; font-size: 15px; margin-bottom: 6px; display: flex; justify-content: space-between;">
                        <span>🤖 এআই গেটওয়ে ও মডেল হাব</span>
                        <span style="background: #e7f3ff; color: #1877f2; font-size: 11px; padding: 2px 8px; border-radius: 10px;">সক্রিয়</span>
                    </div>
                    <div style="font-size: 13px; color: var(--fb-text-secondary); line-height: 1.5;">
                        • মাল্টি-মডেল: GPT-4o, Gemini 2.5, Claude 3.5, Ollama<br>
                        • ভেক্টর ডাটাবেস: 1536-dim কোসাইন সিমিলারিটি<br>
                        • খরচ ট্র্যাকিং: $0.000000 / দিন (রিয়েল-টাইম অডিট)
                    </div>
                </div>

                <!-- Live Streaming & Transcoding -->
                <div class="fb-card" style="padding: 16px; border-left: 4px solid #f02849;">
                    <div style="font-weight: 800; font-size: 15px; margin-bottom: 6px; display: flex; justify-content: space-between;">
                        <span>📹 লাইভ স্ট্রিমিং ও ট্রান্সকোডিং</span>
                        <span style="background: #ffebe6; color: #f02849; font-size: 11px; padding: 2px 8px; border-radius: 10px;">রেডি</span>
                    </div>
                    <div style="font-size: 13px; color: var(--fb-text-secondary); line-height: 1.5;">
                        • RTMP Ingest & WebRTC সিগন্যালিং সক্রিয়<br>
                        • অ্যাডাপ্টিভ বিটরেট HLS: 240p - 1080p<br>
                        • লাইভ রিয়্যাকশন, ভার্চুয়াল গিফট ও সুপারচ্যাট
                    </div>
                </div>

                <!-- ActivityPub Federation -->
                <div class="fb-card" style="padding: 16px; border-left: 4px solid #10b981;">
                    <div style="font-weight: 800; font-size: 15px; margin-bottom: 6px; display: flex; justify-content: space-between;">
                        <span>🌐 অ্যাক্টিভিটিপাব ফেডারেশন</span>
                        <span style="background: #e6f4ea; color: #137333; font-size: 11px; padding: 2px 8px; border-radius: 10px;">সার্বভৌম</span>
                    </div>
                    <div style="font-size: 13px; color: var(--fb-text-secondary); line-height: 1.5;">
                        • ডোমেইন: jugajug.com (WebFinger RFC 7033)<br>
                        • Mastodon ও Threads কম্প্যাটিবল ইনবক্স/আউটবক্স<br>
                        • ক্রিপ্টোগ্রাফিক RSA HTTP সিগনেচার ভেরিফিকেশন
                    </div>
                </div>

                <!-- SOC & Security Risk Engine -->
                <div class="fb-card" style="padding: 16px; border-left: 4px solid #f59e0b;">
                    <div style="font-weight: 800; font-size: 15px; margin-bottom: 6px; display: flex; justify-content: space-between;">
                        <span>🛡️ এন্টারপ্রাইজ SOC ও রিস্ক রাডার</span>
                        <span style="background: #fef3c7; color: #b45309; font-size: 11px; padding: 2px 8px; border-radius: 10px;">সুরক্ষিত</span>
                    </div>
                    <div style="font-size: 13px; color: var(--fb-text-secondary); line-height: 1.5;">
                        • ডিভাইস ফিঙ্গারপ্রিন্টিং ও বিহেভিওরাল রিস্ক স্কোরিং<br>
                        • ক্রেডেনশিয়াল স্টাফিং ও ATO ডিটেক্টর সক্রিয়<br>
                        • W3C OpenTelemetry ডিস্ট্রিবিউটেড ট্রেসিং
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px;">
                <button class="admin-toggle-btn" onclick="triggerAdminAction('/api/v1/admin/cache/clear', 'POST', 'ক্যাশ সফলভাবে ক্লিয়ার হয়েছে!')">🧹 Clear Cache</button>
                <button class="admin-toggle-btn" onclick="triggerAdminAction('/api/v1/admin/queue/restart', 'POST', 'কিউ ওয়ার্কার রিস্টার্ট সম্পন্ন!')">🔄 Restart Workers</button>
                <button class="admin-toggle-btn" style="background: #1877f2; color: white;" onclick="triggerAdminBackup()">💾 Run Encrypted Backup</button>
                <button class="admin-toggle-btn" onclick="triggerAdminAction('/api/v1/admin/search/reindex', 'GET', 'Meilisearch রি-ইন্ডেক্স সম্পন্ন!')">🔍 Reindex Search</button>
                <button class="admin-toggle-btn" style="background: #10b981; color: white;" onclick="loadAdminUsers(); loadAdminReports();">⚡ Refresh Admin Data</button>
            </div>

            <!-- Admin Management Tables Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 16px;">
                <!-- Users Directory & Role Management -->
                <div class="fb-card" style="padding: 16px;">
                    <div style="font-weight: 800; font-size: 16px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                        <span>👥 ব্যবহারকারী ও রোল ব্যবস্থাপনা</span>
                        <button class="post-btn" style="font-size: 12px; padding: 4px 8px;" onclick="loadAdminUsers()">রিফ্রেশ</button>
                    </div>
                    <div style="overflow-x: auto; max-height: 280px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <thead>
                                <tr style="background: #f0f2f5; text-align: left;">
                                    <th style="padding: 8px;">ইউজার</th>
                                    <th style="padding: 8px;">ইমেইল</th>
                                    <th style="padding: 8px;">রোল</th>
                                </tr>
                            </thead>
                            <tbody id="adminUsersTableBody">
                                <tr><td colspan="3" style="padding: 12px; text-align: center; color: var(--fb-text-secondary);">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Moderation Reports Queue -->
                <div class="fb-card" style="padding: 16px;">
                    <div style="font-weight: 800; font-size: 16px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                        <span>🚩 কনটেন্ট মডারেশন ও রিপোর্ট কিউ</span>
                        <button class="post-btn" style="font-size: 12px; padding: 4px 8px;" onclick="loadAdminReports()">রিফ্রেশ</button>
                    </div>
                    <div style="overflow-x: auto; max-height: 280px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <thead>
                                <tr style="background: #f0f2f5; text-align: left;">
                                    <th style="padding: 8px;">টার্গেট</th>
                                    <th style="padding: 8px;">কারণ</th>
                                    <th style="padding: 8px;">স্ট্যাটাস</th>
                                    <th style="padding: 8px;">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody id="adminReportsTableBody">
                                <tr><td colspan="4" style="padding: 12px; text-align: center; color: var(--fb-text-secondary);">লোড হচ্ছে...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Bondhoo AI Assistant Widget -->
    <div id="aiFloatingBtn" onclick="toggleAIChat()" style="position: fixed; bottom: 24px; right: 24px; width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1877f2, #7c3aed); color: white; display: flex; align-items: center; justify-content: center; font-size: 26px; box-shadow: 0 4px 16px rgba(79, 70, 229, 0.4); cursor: pointer; z-index: 99; transition: transform 0.2s;" title="Bondhoo AI (বন্ধু এআই)">
        🤖
    </div>

    <!-- AI Chat Dialog Window -->
    <div id="aiChatModal" style="display: none; position: fixed; bottom: 90px; right: 24px; width: 360px; max-width: 90vw; height: 480px; background: white; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.18); border: 1px solid var(--fb-border); z-index: 100; flex-direction: column; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #2563eb, #7c3aed); color: white; padding: 14px 16px; font-weight: 700; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span>🤖</span>
                <span>Bondhoo AI (বন্ধু এআই)</span>
            </div>
            <button onclick="toggleAIChat()" style="background: transparent; border: none; color: white; font-size: 18px; cursor: pointer;">✕</button>
        </div>
        <div id="aiChatMessages" style="flex: 1; padding: 12px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
            <div style="background: #f0f2f5; padding: 8px 12px; border-radius: 12px 12px 12px 0; max-width: 85%;">
                হ্যালো! আমি Bondhoo AI। আপনাকে কীভাবে সাহায্য করতে পারি? সোশ্যাল পোস্ট লেখা, ক্যাপশন তৈরি, কিংবা যেকোনো তথ্য জানতে আমাকে প্রশ্ন করতে পারেন।
            </div>
        </div>
        <div style="padding: 10px; border-top: 1px solid var(--fb-border); display: flex; gap: 8px; background: #fafafa;">
            <input type="text" id="aiInputText" placeholder="একটি প্রশ্ন বা প্রম্পট লিখুন..." style="flex: 1; border: 1px solid var(--fb-border); border-radius: 20px; padding: 8px 14px; font-size: 13px; outline: none;" onkeydown="if(event.key==='Enter') sendAIMessage()">
            <button onclick="sendAIMessage()" style="background: #1877f2; color: white; border: none; border-radius: 50%; width: 34px; height: 34px; cursor: pointer;">➤</button>
        </div>
    </div>

    <!-- --------------------------------------------------------- -->
    <!-- 4. JUGAJUG ENTERPRISE POST COMPOSER SYSTEM -->
    <!-- --------------------------------------------------------- -->
    @include('partials.enterprise-post-composer')

    <!-- =========================================================================
         ENTERPRISE STORY VIEWER MODAL (FACEBOOK/INSTAGRAM GRADE)
         ========================================================================= -->
    <!-- =========================================================================
         ENTERPRISE STORY VIEWER MODAL (FACEBOOK/INSTAGRAM GRADE)
         ========================================================================= -->
    <div class="fb-story-viewer-modal" id="fbStoryViewerModal">
        <!-- Desktop Nav Previous -->
        <button type="button" class="fb-story-nav-btn fb-story-nav-prev" onclick="JugajugMediaSuite.prevStoryItem()">‹</button>

        <div class="fb-story-viewer-frame" id="fbStoryViewerFrame">
            <!-- Segmented Progress Bar -->
            <div class="fb-story-progress-segments" id="fbStorySegmentsContainer">
                <div id="fbStoryProgressBarsBox" style="display:flex;gap:4px;width:100%;"></div>
            </div>

            <!-- Top Header -->
            <div class="fb-story-viewer-header">
                <div class="fb-story-header-left">
                    <div class="fb-story-header-avatar" id="fbStoryAuthorAvatar">র</div>
                    <div class="fb-story-header-meta">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span class="fb-story-header-name" id="fbStoryAuthorName">ইউজার</span>
                            <span id="fbStoryEmojiBadge" style="font-size: 16px;"></span>
                        </div>
                        <div class="story-header-badges" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <span class="fb-story-header-time" id="fbStoryAuthorTime"><span id="fbStoryTimeAgo">এইমাত্র</span></span>
                            <span class="story-badge" id="fbStoryMusicBadge" style="display: none; background: rgba(255,255,255,0.25); border-radius: 12px; padding: 2px 8px; font-size: 11px; font-weight: 600;"></span>
                            <span class="story-badge" id="fbStoryLocationBadge" style="display: none;"></span>
                        </div>
                    </div>
                </div>

                <div class="fb-story-header-actions">
                    <button type="button" class="fb-story-ctrl-btn" id="fbStoryMuteBtn" onclick="JugajugMediaSuite.toggleStoryMute()" title="মিউট / আনমিউট">🔊</button>
                    <button type="button" class="fb-story-viewers-btn" id="fbStoryAuthorViewersPill" style="display: none; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.3); color: #fff; border-radius: 14px; padding: 3px 10px; font-size: 11px; cursor: pointer; font-weight: 700;">👁️ ০</button>
                    <button type="button" class="fb-story-ctrl-btn" id="fbStoryShareHeaderBtn" onclick="JugajugMediaSuite.openShareModal('story')" title="শেয়ার করুন">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                    </button>
                    <button type="button" class="fb-story-ctrl-btn" id="fbStoryReportHeaderBtn" onclick="JugajugMediaSuite.openReportModal('story')" title="রিপোর্ট করুন">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                    </button>
                    <button type="button" class="fb-story-ctrl-btn danger" id="fbStoryDeleteBtn" onclick="JugajugMediaSuite.deleteCurrentStory()" title="স্টোরি ডিলিট করুন" style="display: none;">🗑️</button>
                    <button type="button" class="fb-story-ctrl-btn" id="fbStoryArchiveBtn" onclick="JugajugMediaSuite.archiveCurrentStory()" title="আর্কাইভ করুন" style="display: none;">📦</button>
                    <button type="button" class="fb-story-ctrl-btn" onclick="JugajugMediaSuite.closeStoryViewer()" title="বন্ধ করুন">✕</button>
                </div>
            </div>

            <!-- Tap Interaction Zones & Media Viewport -->
            <div class="fb-story-content-viewport" id="fbStoryViewport"
                 onmousedown="JugajugMediaSuite.pauseStory()"
                 onmouseup="JugajugMediaSuite.resumeStory()"
                 ontouchstart="JugajugMediaSuite.pauseStory()"
                 ontouchend="JugajugMediaSuite.resumeStory()">
                <!-- Dynamic Content (Video, Photo, or Text Canvas) -->
            </div>

            <div class="fb-story-tap-left" onclick="JugajugMediaSuite.prevStoryItem()"></div>
            <div class="fb-story-tap-right" onclick="JugajugMediaSuite.nextStoryItem()"></div>

            <!-- Bottom Engagement Tray (Reactions & Reply) -->
            <div class="fb-story-bottom-bar" id="fbStoryBottomBar">
                <input type="text" class="fb-story-reply-input" id="fbStoryReplyInput" placeholder="স্টোরিতে রিপ্লাই পাঠান..." onkeydown="if(event.key==='Enter') JugajugMediaSuite.sendStoryReply()">
                <button type="button" class="fb-story-action-btn" id="fbStoryBottomShareBtn" onclick="JugajugMediaSuite.openShareModal('story')" title="স্টোরি শেয়ার করুন" style="background: rgba(255,255,255,0.22); border: 1px solid rgba(255,255,255,0.3); color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                </button>
                <div class="fb-story-reaction-tray" id="fbStoryReactionBar">
                    <button type="button" class="fb-story-reaction-btn" onclick="JugajugMediaSuite.sendStoryReaction('like')" title="Like">👍</button>
                    <button type="button" class="fb-story-reaction-btn" onclick="JugajugMediaSuite.sendStoryReaction('love')" title="Love">❤️</button>
                    <button type="button" class="fb-story-reaction-btn" onclick="JugajugMediaSuite.sendStoryReaction('care')" title="Care">🥰</button>
                    <button type="button" class="fb-story-reaction-btn" onclick="JugajugMediaSuite.sendStoryReaction('haha')" title="Haha">😂</button>
                    <button type="button" class="fb-story-reaction-btn" onclick="JugajugMediaSuite.sendStoryReaction('wow')" title="Wow">😮</button>
                    <button type="button" class="fb-story-reaction-btn" onclick="JugajugMediaSuite.sendStoryReaction('sad')" title="Sad">😢</button>
                    <button type="button" class="fb-story-reaction-btn" onclick="JugajugMediaSuite.sendStoryReaction('angry')" title="Angry">😡</button>
                </div>
            </div>
        </div>

        <!-- Desktop Nav Next -->
        <button type="button" class="fb-story-nav-btn fb-story-nav-next" onclick="JugajugMediaSuite.nextStoryItem()">›</button>
    </div>

    <!-- STORY VIEWERS LIST MODAL -->
    <div class="modal-overlay" id="storyViewersListModal" style="display: none; z-index: 100000;">
        <div class="modal-box" style="max-width: 440px;">
            <div class="modal-header">
                <span class="modal-title">👁️ স্টোরি ভিউয়ারগণ</span>
                <button type="button" class="modal-close" onclick="JugajugMediaSuite.closeStoryViewersModal()">✕</button>
            </div>
            <div class="modal-body" id="storyViewersContentList" style="max-height: 400px; overflow-y: auto;">
                <!-- Populated via API -->
            </div>
        </div>
    </div>

    <!-- =========================================================================
         ENTERPRISE CREATE STORY MODAL (PHOTO/VIDEO RESUMABLE + TEXT + MUSIC + POLL)
         ========================================================================= -->
    <!-- =========================================================================
         ENTERPRISE CREATE STORY MODAL (PHOTO/VIDEO RESUMABLE + TEXT + MUSIC + POLL)
         ========================================================================= -->
    <div class="modal-overlay" id="createStoryModalV2" style="display: none; z-index: 99999;">
        <div class="modal-box" style="max-width: 520px;">
            <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="story-modal-brand-badge">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </span>
                    <div>
                        <div class="modal-title" style="font-size: 16px; font-weight: 800; color: #0f172a; line-height: 1.2;">নতুন স্টোরি প্রকাশ করুন</div>
                        <div style="font-size: 11px; color: #64748b;">২৪ ঘণ্টার জন্য বন্ধুদের সাথে শেয়ার করুন</div>
                    </div>
                </div>
                <button type="button" class="modal-close" onclick="JugajugMediaSuite.closeCreateStoryModal()" title="বন্ধ করুন">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="modal-body" style="padding: 16px;">
                <!-- Tab Selector: Photo/Video vs Text with Modern SVG Icons -->
                <div class="story-segmented-tabs">
                    <button type="button" class="story-tab-pill active" id="cstTabPhoto" onclick="JugajugMediaSuite.switchCreateStoryTab('photo')">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <span>ছবি বা ভিডিও স্টোরি</span>
                    </button>
                    <button type="button" class="story-tab-pill" id="cstTabText" onclick="JugajugMediaSuite.switchCreateStoryTab('text')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                        </svg>
                        <span>কালারফুল টেক্সট স্টোরি</span>
                    </button>
                </div>

                <!-- SECTION 1: PHOTO / VIDEO UPLOAD -->
                <div id="cstPhotoSection">
                    <div class="story-upload-dropzone" onclick="document.getElementById('storyMediaFileInput').click()">
                        <div class="story-dropzone-icon-circle">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </div>
                        <div class="story-dropzone-title">ছবি বা ভিডিও নির্বাচন করুন বা ড্র্যাগ করুন</div>
                        <div class="story-dropzone-subtitle">Photo, Video, Reel-Style Vertical 9:16 (JPEG, PNG, WebP, MP4, WebM, MOV)</div>
                        <div class="story-dropzone-badges">
                            <span class="story-badge-pill">🖼️ হাই-কোয়ালিটি ফটো</span>
                            <span class="story-badge-pill">🎬 ৬০ সে. ভিডিও</span>
                            <span class="story-badge-pill">📱 ফুলস্ক্রিন ৯:১৬</span>
                        </div>
                        <input type="file" id="storyMediaFileInput" accept="image/*,video/*" multiple style="display: none;" onchange="JugajugMediaSuite.handleStoryFileSelect(event)">
                    </div>

                    <!-- Action Buttons -->
                    <div class="story-actions-row">
                        <button type="button" class="story-action-btn story-btn-browse" onclick="document.getElementById('storyMediaFileInput').click()">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <span>ফাইল ব্রাউজ করুন</span>
                        </button>
                        <button type="button" class="story-action-btn story-btn-camera" onclick="JugajugMediaSuite.startStoryCameraCapture()">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                <circle cx="12" cy="13" r="4"></circle>
                            </svg>
                            <span>ক্যামেরা দিয়ে তুলুন</span>
                        </button>
                    </div>

                    <!-- Multi-Media Grid Preview -->
                    <div id="storyMultiMediaGrid" class="multi-media-grid" style="display: none;"></div>

                    <div style="margin-top: 14px;">
                        <input type="text" id="storyPhotoCaption" class="modal-input" placeholder="ক্যাপশন লিখুন (ঐচ্ছিক)..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 13.5px;">
                    </div>
                </div>

                <!-- SECTION 2: TEXT STORY -->
                <div id="cstTextSection" style="display: none;">
                    <!-- Live Text Preview Card -->
                    <div id="storyTextPreviewCard" class="story-text-preview-card" style="background: linear-gradient(135deg, #1877f2, #00c6ff); font-family: 'Hind Siliguri', sans-serif;">
                        আপনার চিন্তাভাবনা লিখুন...
                    </div>

                    <!-- Color Swatches Bar -->
                    <div style="margin-bottom: 12px;">
                        <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">ব্যাকগ্রাউন্ড থিম নির্বাচন:</label>
                        <div class="story-color-palette" id="storyPaletteDotsContainer">
                            <span class="story-palette-dot active" style="background: linear-gradient(135deg, #1877f2, #00c6ff);" onclick="JugajugMediaSuite.setStoryBgPreset('linear-gradient(135deg, #1877f2, #00c6ff)', this)" title="Classic Blue"></span>
                            <span class="story-palette-dot" style="background: linear-gradient(135deg, #ff416c, #ff4b2b);" onclick="JugajugMediaSuite.setStoryBgPreset('linear-gradient(135deg, #ff416c, #ff4b2b)', this)" title="Sunset Passion"></span>
                            <span class="story-palette-dot" style="background: linear-gradient(135deg, #11998e, #38ef7d);" onclick="JugajugMediaSuite.setStoryBgPreset('linear-gradient(135deg, #11998e, #38ef7d)', this)" title="Emerald Glow"></span>
                            <span class="story-palette-dot" style="background: linear-gradient(135deg, #8a2387, #e94057, #f27121);" onclick="JugajugMediaSuite.setStoryBgPreset('linear-gradient(135deg, #8a2387, #e94057, #f27121)', this)" title="Vibrant Glow"></span>
                            <span class="story-palette-dot" style="background: linear-gradient(135deg, #4facfe, #00f2fe);" onclick="JugajugMediaSuite.setStoryBgPreset('linear-gradient(135deg, #4facfe, #00f2fe)', this)" title="Sky Ocean"></span>
                            <span class="story-palette-dot" style="background: #0f172a;" onclick="JugajugMediaSuite.setStoryBgPreset('#0f172a', this)" title="Midnight Slate"></span>
                        </div>
                        <select id="storyTextBgPreset" style="display: none;">
                            <option value="linear-gradient(135deg, #1877f2, #00c6ff)">Classic Blue</option>
                            <option value="linear-gradient(135deg, #ff416c, #ff4b2b)">Sunset Passion</option>
                            <option value="linear-gradient(135deg, #11998e, #38ef7d)">Emerald Glow</option>
                            <option value="linear-gradient(135deg, #8a2387, #e94057, #f27121)">Vibrant Glow</option>
                            <option value="linear-gradient(135deg, #4facfe, #00f2fe)">Sky Ocean</option>
                            <option value="#0f172a">Midnight Slate</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">ফন্ট স্টাইল:</label>
                        <select id="storyFontFamilySelect" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px;" onchange="JugajugMediaSuite.updateStoryTextPreview()">
                            <option value="Hind Siliguri, sans-serif">হিন্দ শিলিগুড়ি (Bangla Clean)</option>
                            <option value="Inter, sans-serif">ইন্টার (Modern Clean)</option>
                            <option value="Georgia, serif">জর্জিয়া (Editorial Serif)</option>
                            <option value="Courier New, monospace">টাইপরাইটার (Monospace)</option>
                        </select>
                    </div>

                    <textarea id="storyTextContent" class="modal-textarea" placeholder="আপনার স্টোরিতে কী লিখতে চান?" style="height: 100px; font-size: 15px; border-radius: 10px;" oninput="JugajugMediaSuite.updateStoryTextPreview()"></textarea>
                </div>

                <!-- ADD-ONS: MUSIC & INTERACTIVE POLL (WITH CLEAN SVG ICONS) -->
                <div style="margin-top: 14px; border-top: 1px solid #f1f5f9; padding-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="story-addon-chip" onclick="JugajugMusicSuite.openModal('story')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 18V5l12-2v13"></path>
                            <circle cx="6" cy="18" r="3"></circle>
                            <circle cx="18" cy="16" r="3"></circle>
                        </svg>
                        <span>ব্যাকগ্রাউন্ড মিউজিক</span>
                    </button>
                    <button type="button" class="story-addon-chip" onclick="JugajugMediaSuite.toggleStoryPoll()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        <span>পোল স্টিকার</span>
                    </button>
                </div>

                <!-- Attached Music Badge for Story -->
                <div id="storyAttachedMusicBadge" style="display: none; align-items: center; justify-content: space-between; background: #e0f2fe; border: 1px solid #bae6fd; padding: 8px 12px; border-radius: 10px; margin-top: 8px; font-size: 12.5px; font-weight: 600; color: #0369a1;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
                        <span id="storyAttachedMusicTitle">ব্যাকগ্রাউন্ড মিউজিক</span>
                    </div>
                    <button type="button" style="background: none; border: none; color: #ef4444; font-weight: 700; cursor: pointer;" onclick="JugajugMediaSuite.removeStoryMusic()">✕ মুছুন</button>
                </div>

                <!-- Interactive Poll Form -->
                <div id="storyPollFieldsBox" style="display: none; margin-top: 10px; padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                    <div style="font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        <span>ইন্টারেক্টিভ পোল কনফিগার করুন</span>
                    </div>
                    <input type="text" id="storyPollQuestionInput" class="modal-input" placeholder="পোলের প্রশ্ন লিখুন (যেমন: আপনার প্রিয় রঙ কোনটি?)..." style="margin-bottom: 8px; font-size: 13px; border-radius: 8px;">
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="storyPollOpt1Input" class="modal-input" placeholder="অপশন ১ (যেমন: নীল)" style="font-size: 12.5px; border-radius: 8px;">
                        <input type="text" id="storyPollOpt2Input" class="modal-input" placeholder="অপশন ২ (যেমন: লাল)" style="font-size: 12.5px; border-radius: 8px;">
                    </div>
                </div>

                <!-- Privacy Selection with Clean SVG Icon -->
                <div style="margin-top: 14px; display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #475569;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        <span>কারা দেখতে পাবেন:</span>
                    </div>
                    <select id="storyPrivacySelect" style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px; font-size: 13px; background: #ffffff;">
                        <option value="public">🌐 সর্বজনীন (Public)</option>
                        <option value="friends">👥 বন্ধুরা (Friends Only)</option>
                        <option value="only_me">🔒 শুধুমাত্র আমি (Only Me)</option>
                    </select>
                </div>

                <!-- Chunked Resumable Upload Progress Card -->
                <div class="upload-resumable-progress-card" id="storyUploadProgressBox" style="display: none; margin-top: 12px;">
                    <div class="upload-resumable-header">
                        <span class="upload-resumable-filename">মিডিয়া আপলোড হচ্ছে...</span>
                        <span class="upload-resumable-stat" id="storyProgressPercentText">0%</span>
                    </div>
                    <div class="upload-progress-track">
                        <div class="upload-progress-fill" id="storyProgressBarFill"></div>
                    </div>
                    <div class="upload-resumable-status-text" id="storyProgressStatusText">চাঙ্ক আপলোড শুরু হচ্ছে...</div>
                    <div class="upload-resumable-actions">
                        <button type="button" class="upload-ctrl-btn upload-ctrl-pause" onclick="JugajugMediaSuite.currentUploader?.isPaused ? JugajugMediaSuite.currentUploader.resume() : JugajugMediaSuite.currentUploader.pause()">বিরতি / চালু</button>
                        <button type="button" class="upload-ctrl-btn upload-ctrl-cancel" onclick="JugajugMediaSuite.currentUploader?.cancel()">বাতিল</button>
                    </div>
                </div>

                <button type="button" class="story-submit-btn" id="storySubmitBtn" onclick="JugajugMediaSuite.submitStory()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    <span>স্টোরি পাবলিশ করুন</span>
                </button>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         DEDICATED FULL-SCREEN REEL VIEWER MODAL
         ========================================================================= -->
    <div class="fb-reel-viewer-modal" id="fbReelViewerModal">
        <div class="fb-reel-viewer-container">
            <!-- Top Controls -->
            <div class="fb-reel-overlay-top">
                <div style="color: #fff; font-weight: 800; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                    <span class="reel-top-logo-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <rect x="2" y="2" width="20" height="20" rx="4" fill="url(#reelViewerLogoGrad)"/>
                            <path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5" stroke="#ffffff" stroke-width="1.5"/>
                            <polygon points="10 9 15 12 10 15 10 9" fill="#ffffff"/>
                            <defs>
                                <linearGradient id="reelViewerLogoGrad" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#ec4899"/>
                                    <stop offset="1" stop-color="#8b5cf6"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </span>
                    <span>Bondhoo রিলস</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="fb-story-ctrl-btn" id="fbReelMuteBtn" onclick="JugajugMediaSuite.toggleReelMute()" title="শব্দ অন/অফ">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                    </button>
                    <button type="button" class="fb-story-ctrl-btn" onclick="JugajugMediaSuite.prevReel()" title="পূর্ববর্তী রিল (Arrow Up)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                    </button>
                    <button type="button" class="fb-story-ctrl-btn" onclick="JugajugMediaSuite.nextReel()" title="পরবর্তী রিল (Arrow Down)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <button type="button" class="fb-story-ctrl-btn" onclick="JugajugMediaSuite.closeReelViewer()" title="বন্ধ করুন">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <!-- Vertical Video Player -->
            <video class="fb-reel-video-element" id="fbReelVideoEl" loop playsinline onclick="JugajugMediaSuite.toggleReelPlayPause()"></video>

            <!-- Right Sidebar Actions -->
            <div class="fb-reel-sidebar-actions">
                <!-- 1. LIKE / REACT -->
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <button type="button" class="fb-reel-action-btn" id="fbReelLikeBtn" onclick="JugajugMediaSuite.toggleReelLike()" title="পছন্দ করুন">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    </button>
                    <span class="fb-reel-action-label" id="fbReelLikesCount">0</span>
                </div>

                <!-- 2. COMMENT -->
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <button type="button" class="fb-reel-action-btn" id="fbReelCommentBtn" onclick="JugajugMediaSuite.openReelCommentsDrawer()" title="মন্তব্য দেখুন বা লিখুন">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    </button>
                    <span class="fb-reel-action-label" id="fbReelCommentsCount">0</span>
                </div>

                <!-- 3. SHARE -->
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <button type="button" class="fb-reel-action-btn" id="fbReelShareBtn" onclick="JugajugMediaSuite.openShareModal('reel')" title="শেয়ার করুন">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                    </button>
                    <span class="fb-reel-action-label" id="fbReelSharesCount">0</span>
                </div>

                <!-- 4. MESSAGE CREATOR -->
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <button type="button" class="fb-reel-action-btn" id="fbReelMessageBtn" onclick="JugajugMediaSuite.messageReelCreator()" title="মেসেঞ্জারে পাঠান">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    </button>
                    <span class="fb-reel-action-label">মেসেজ</span>
                </div>

                <!-- 5. SAVE / BOOKMARK -->
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <button type="button" class="fb-reel-action-btn" id="fbReelSaveBtn" onclick="JugajugMediaSuite.toggleReelSave()" title="সংরক্ষণ (Save)">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                    </button>
                    <span class="fb-reel-action-label" id="fbReelSavesCount">0</span>
                </div>

                <!-- 6. MORE OPTIONS -->
                <div style="display: flex; flex-direction: column; align-items: center; position: relative;">
                    <button type="button" class="fb-reel-action-btn" id="fbReelMoreBtn" onclick="JugajugMediaSuite.toggleReelMoreMenu()" title="আরও অপশন">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                    </button>
                    <span class="fb-reel-action-label">আরও</span>

                    <!-- Floating More Popup Menu -->
                    <div class="reel-more-popup" id="reelMoreMenuPopup" style="display: none;">
                        <button type="button" class="reel-more-item" onclick="JugajugMediaSuite.openReportModal('reel')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                            <span style="color: #ef4444;">রিপোর্ট করুন</span>
                        </button>
                        <button type="button" class="reel-more-item" onclick="JugajugMediaSuite.copyReelLink()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                            <span>লিঙ্ক কপি করুন</span>
                        </button>
                        <button type="button" class="reel-more-item" onclick="JugajugMediaSuite.toggleFollowCreator()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7.5" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                            <span id="reelMoreFollowText">ফলো করুন</span>
                        </button>
                        <button type="button" class="reel-more-item" onclick="JugajugMediaSuite.toggleReelMute()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                            <span>মিউট / আনমিউট</span>
                        </button>
                        <button type="button" class="reel-more-item" onclick="JugajugMediaSuite.hideCurrentReel()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            <span>এই ধরনের রিল আর দেখাবেন না</span>
                        </button>
                        <button type="button" class="reel-more-item danger" id="reelMoreDeleteBtn" style="display: none;" onclick="JugajugMediaSuite.deleteCurrentReel()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            <span style="color: #ef4444;">রিল ডিলিট করুন</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bottom Metadata -->
            <div class="fb-reel-info-bottom">
                <div class="fb-reel-author-tag">
                    <span class="fb-reel-author-name" id="fbReelAuthorName">ক্রিয়েটর</span>
                </div>
                <div id="fbReelCaptionText" style="font-size: 13px; line-height: 1.4; font-weight: 500;"></div>
                <div class="fb-reel-audio-track">
                    <div class="vinyl-disc-anim">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="#ffffff"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
                    </div>
                    <span id="fbReelAudioText">Original Audio</span>
                </div>
            </div>
        </div>

        <!-- Slide-Over Reel Comments Drawer -->
        <div class="reel-comments-drawer" id="reelCommentsDrawer" style="display: none;">
            <div class="reel-comments-header">
                <span class="reel-comments-title" id="reelCommentsCountText">মন্তব্যসমূহ</span>
                <button type="button" class="modal-close" onclick="JugajugMediaSuite.closeReelCommentsDrawer()">✕</button>
            </div>
            <div class="reel-comments-list" id="reelCommentsListContainer">
                <!-- Dynamically loaded -->
            </div>
            <div class="reel-comment-input-box">
                <input type="text" class="reel-comment-input" id="reelCommentTextInput" placeholder="একটি মন্তব্য লিখুন..." onkeydown="if(event.key==='Enter') JugajugMediaSuite.submitReelComment()">
                <button type="button" class="reel-comment-send-btn" id="reelCommentSubmitBtn" onclick="JugajugMediaSuite.submitReelComment()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         ENTERPRISE SHARE MODAL (REELS & STORIES)
         ========================================================================= -->
    <div class="modal-overlay" id="reelShareModal" style="display: none; z-index: 100002;">
        <div class="modal-box" style="max-width: 520px; border-radius: 16px;">
            <div class="modal-header">
                <span class="modal-title" style="display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1877f2" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                    <span id="reelShareModalTitle">শেয়ার করুন</span>
                </span>
                <button type="button" class="modal-close" onclick="JugajugMediaSuite.closeShareModal()">✕</button>
            </div>
            <div class="modal-body" style="padding: 16px;">
                <!-- Primary Quick Destinations -->
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; text-align: center; margin-bottom: 20px;">
                    <button type="button" class="share-destination-btn" onclick="JugajugMediaSuite.shareToFeed()">
                        <div class="share-destination-icon" style="background: #e7f3ff; color: #1877f2;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1"></path><path d="M18 14h4v4h-4z"></path></svg>
                        </div>
                        <span style="font-size: 12px; font-weight: 700; color: #0f172a;">ফিড</span>
                    </button>
                    <button type="button" class="share-destination-btn" onclick="JugajugMediaSuite.shareToStory()">
                        <div class="share-destination-icon" style="background: #fdf2f8; color: #ec4899;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="10 8 16 12 10 16 10 8"></polygon></svg>
                        </div>
                        <span style="font-size: 12px; font-weight: 700; color: #0f172a;">স্টোরি</span>
                    </button>
                    <button type="button" class="share-destination-btn" onclick="JugajugMediaSuite.copyShareLink()">
                        <div class="share-destination-icon" style="background: #f1f5f9; color: #475569;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                        </div>
                        <span style="font-size: 12px; font-weight: 700; color: #0f172a;">লিঙ্ক কপি</span>
                    </button>
                    <button type="button" class="share-destination-btn" onclick="JugajugMediaSuite.shareNativeExternal()">
                        <div class="share-destination-icon" style="background: #f0fdf4; color: #16a34a;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                        </div>
                        <span style="font-size: 12px; font-weight: 700; color: #0f172a;">অন্যান্য</span>
                    </button>
                </div>

                <!-- Send in Messenger Header & Search -->
                <div style="border-top: 1px solid #f1f5f9; padding-top: 14px;">
                    <div style="font-size: 13px; font-weight: 800; color: #1e293b; margin-bottom: 8px;">মেসেঞ্জারে বন্ধুদের পাঠান:</div>
                    <input type="text" id="shareMessengerSearch" class="modal-input" placeholder="বন্ধু বা কনভার্সন খুঁজুন..." oninput="JugajugMediaSuite.filterShareConversations(this.value)" style="margin-bottom: 10px; font-size: 13px;">
                    
                    <!-- Messenger Conversations List -->
                    <div id="shareMessengerList" style="max-height: 220px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                        <div style="text-align: center; color: #64748b; font-size: 13px; padding: 12px;">লোড হচ্ছে...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         ENTERPRISE REPORT CONTENT MODAL (REELS & STORIES)
         ========================================================================= -->
    <div class="modal-overlay" id="reportContentModal" style="display: none; z-index: 100003;">
        <div class="modal-box" style="max-width: 500px; border-radius: 16px;">
            <div class="modal-header">
                <span class="modal-title" style="display: flex; align-items: center; gap: 8px; color: #dc2626;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                    <span id="reportModalTitle">রিপোর্ট করুন</span>
                </span>
                <button type="button" class="modal-close" onclick="JugajugMediaSuite.closeReportModal()">✕</button>
            </div>
            <div class="modal-body" style="padding: 16px;">
                <div style="font-size: 13px; color: #64748b; margin-bottom: 12px;">এই কনটেন্টটিতে কী ধরনের সমস্যা রয়েছে? একটি কারণ নির্বাচন করুন:</div>
                
                <div class="report-reasons-list">
                    <label class="report-reason-item">
                        <input type="radio" name="reportReason" value="spam" checked>
                        <span>স্প্যাম বা বিভ্রান্তিকর কনটেন্ট (Spam)</span>
                    </label>
                    <label class="report-reason-item">
                        <input type="radio" name="reportReason" value="harassment">
                        <span>হয়রানি বা উত্যক্তকরণ (Harassment)</span>
                    </label>
                    <label class="report-reason-item">
                        <input type="radio" name="reportReason" value="hate_speech">
                        <span>বিদ্বেষমূলক বা আক্রমণাত্মক বক্তব্য (Hate / Abusive)</span>
                    </label>
                    <label class="report-reason-item">
                        <input type="radio" name="reportReason" value="nudity">
                        <span>নগ্নতা বা যৌন উত্তেজক সামগ্রী (Nudity / Sexual content)</span>
                    </label>
                    <label class="report-reason-item">
                        <input type="radio" name="reportReason" value="violence">
                        <span>সহিংসতা বা বিপজ্জনক কাজ (Violence)</span>
                    </label>
                    <label class="report-reason-item">
                        <input type="radio" name="reportReason" value="false_information">
                        <span>ভুয়া বা অপতথ্য (False information)</span>
                    </label>
                    <label class="report-reason-item">
                        <input type="radio" name="reportReason" value="copyright">
                        <span>কপিরাইট বা বুদ্ধিবৃত্তিক সম্পদ লঙ্ঘন (Copyright / IP)</span>
                    </label>
                    <label class="report-reason-item">
                        <input type="radio" name="reportReason" value="scam_fraud">
                        <span>প্রতারণা বা আর্থিক জালিয়াতি (Scam / Fraud)</span>
                    </label>
                    <label class="report-reason-item">
                        <input type="radio" name="reportReason" value="other">
                        <span>অন্যান্য অসঙ্গতি (Other)</span>
                    </label>
                </div>

                <div style="margin-top: 14px;">
                    <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px; display: block;">অতিরিক্ত বিবরণ (ঐচ্ছিক):</label>
                    <textarea id="reportDetailsInput" class="modal-textarea" placeholder="সমস্যার সুনির্দিষ্ট বিবরণ লিখুন..." style="height: 60px; font-size: 13px;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn-fb-primary" style="background: #f1f5f9; color: #475569;" onclick="JugajugMediaSuite.closeReportModal()">বাতিল</button>
                    <button type="button" class="modal-btn-submit" id="submitReportBtn" style="margin: 0; background: #dc2626;" onclick="JugajugMediaSuite.submitReport()">রিপোর্ট জমা দিন</button>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         ENTERPRISE REEL CREATOR & STUDIO EDITOR MODAL (STUDIO WIZARD)
         ========================================================================= -->
    <div class="modal-overlay" id="createReelModalV2" style="display: none; z-index: 99999;">
        <div class="modal-box reel-studio-modal">
            <!-- Studio Header with Stepper Tabs -->
            <div class="modal-header" style="flex-direction: column; align-items: stretch; gap: 8px;">
                <div style="display: flex; align-items: center; justify-content: space-between; position: relative;">
                    <div style="font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="reel-studio-brand-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                <rect x="2" y="2" width="20" height="20" rx="4" fill="url(#reelStudioGrad)"/>
                                <path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5" stroke="#ffffff" stroke-width="1.5"/>
                                <polygon points="10 9 15 12 10 15 10 9" fill="#ffffff"/>
                                <defs>
                                    <linearGradient id="reelStudioGrad" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#ec4899"/>
                                        <stop offset="1" stop-color="#8b5cf6"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </span>
                        <span class="reel-studio-title-full">Bondhoo রিল স্টুডিও — নতুন রিল তৈরি ও সম্পাদনা</span>
                        <span class="reel-studio-title-short">Bondhoo রিল স্টুডিও</span>
                    </div>
                    <button type="button" class="modal-close reel-studio-close-btn" style="position: static !important; transform: none !important; margin-left: auto !important; flex-shrink: 0 !important;" onclick="JugajugMediaSuite.closeCreateReelModal()" title="বন্ধ করুন">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <div class="reel-studio-steps">
                    <button type="button" class="reel-step-pill active" data-step="media" onclick="JugajugMediaSuite.setReelStep('media')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <span>১. মিডিয়া নির্বাচন</span>
                    </button>
                    <button type="button" class="reel-step-pill" data-step="editor" onclick="JugajugMediaSuite.setReelStep('editor')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"></circle><circle cx="6" cy="18" r="3"></circle><line x1="20" y1="4" x2="8.12" y2="15.88"></line><line x1="14.47" y1="14.48" x2="20" y2="20"></line><line x1="8.12" y1="8.12" x2="12" y2="12"></line></svg>
                        <span>২. এডিটর ও ট্রিম</span>
                    </button>
                    <button type="button" class="reel-step-pill" data-step="audio" onclick="JugajugMediaSuite.setReelStep('audio')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
                        <span>৩. অডিও ও মিউজিক</span>
                    </button>
                    <button type="button" class="reel-step-pill" data-step="details" onclick="JugajugMediaSuite.setReelStep('details')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"></path></svg>
                        <span>৪. বিবরণ ও প্রকাশ</span>
                    </button>
                </div>
            </div>

            <div class="modal-body" style="padding: 16px;">
                <div class="reel-studio-layout">
                    <!-- Left: Studio Preview Canvas & Live Player -->
                    <div class="reel-studio-preview-box">
                        <video class="reel-studio-video" id="reelStudioPreviewVideo" loop playsinline autoplay muted></video>
                        <canvas id="reelCoverFrameCanvas" style="display: none;"></canvas>

                        <!-- Camera Viewfinder Overlay -->
                        <div id="reelCameraViewBox" style="display: none; position: absolute; inset: 0; background: #000; z-index: 10;">
                            <video id="reelCameraPreviewVideo" playsinline muted style="width: 100%; height: 100%; object-fit: cover;"></video>
                            
                            <!-- Top Viewfinder Header -->
                            <div style="position: absolute; top: 12px; left: 12px; right: 12px; display: flex; align-items: center; justify-content: space-between; z-index: 12;">
                                <div id="reelCameraTimerBadge" style="background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #ef4444; animation: pulseRecord 1s infinite;"></span>
                                    <span id="reelCameraSecondsText">লাইভ ক্যামেরা (০:০০)</span>
                                </div>
                                <button type="button" style="background: rgba(0,0,0,0.6); color: #fff; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;" onclick="JugajugMediaSuite.stopCameraStream()" title="ক্যামেরা বন্ধ করুন">
                                    ✕
                                </button>
                            </div>

                            <!-- Bottom Viewfinder Shutter Control -->
                            <div style="position: absolute; bottom: 18px; left: 0; right: 0; display: flex; align-items: center; justify-content: center; gap: 18px; z-index: 12;">
                                <button type="button" id="reelCameraShutterBtn" class="camera-shutter-btn" onclick="JugajugMediaSuite.toggleCameraRecord()" title="রেকর্ডিং শুরু / বন্ধ">
                                    <span class="shutter-inner" id="reelCameraShutterInner"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Step Panels -->
                    <div>
                        <!-- STEP 1: MEDIA SOURCE -->
                        <div id="reelStudioStep_media">
                            <div class="reel-upload-dropzone" onclick="document.getElementById('reelVideoFileInput').click()" ondragover="event.preventDefault(); this.classList.add('dragover');" ondragleave="this.classList.remove('dragover');" ondrop="event.preventDefault(); this.classList.remove('dragover'); if(event.dataTransfer?.files?.[0]) JugajugMediaSuite.loadReelVideoFile(event.dataTransfer.files[0]);">
                                <div class="reel-dropzone-icon-box">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none">
                                        <rect x="2" y="2" width="20" height="20" rx="5" fill="url(#reelDropGrad)"/>
                                        <path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5" stroke="#ffffff" stroke-width="1.5"/>
                                        <polygon points="10 9 15 12 10 15 10 9" fill="#ffffff"/>
                                        <defs>
                                            <linearGradient id="reelDropGrad" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse">
                                                <stop stop-color="#3b82f6"/>
                                                <stop offset="1" stop-color="#8b5cf6"/>
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                </div>
                                <div style="font-weight: 800; color: #0f172a; font-size: 16px; margin-bottom: 4px;">ভিডিও নির্বাচন করুন বা ড্র্যাগ ও ড্রপ করুন</div>
                                <div style="font-size: 13px; color: #64748b; margin-bottom: 16px;">MP4, WebM, MOV (সর্বোচ্চ ৫০০ মেগাবাইট, ৯:১৬ ভার্টিক্যাল সুপারিশকৃত)</div>
                                <button type="button" class="btn-fb-primary" style="padding: 9px 20px; border-radius: 10px; font-size: 13px; font-weight: 700; background: #1877f2; color: #fff;">
                                    ফাইল ব্রাউজ করুন
                                </button>
                                <input type="file" id="reelVideoFileInput" accept="video/mp4,video/webm,video/quicktime" style="display: none;" onchange="JugajugMediaSuite.handleReelFileSelect(event)">
                            </div>

                            <!-- Divider: অথবা -->
                            <div style="display: flex; align-items: center; margin: 16px 0; color: #94a3b8; font-size: 12px; font-weight: 700;">
                                <div style="flex: 1; height: 1px; background: #e2e8f0;"></div>
                                <span style="padding: 0 12px;">অথবা</span>
                                <div style="flex: 1; height: 1px; background: #e2e8f0;"></div>
                            </div>

                            <!-- Camera Launch Action Card -->
                            <div class="reel-camera-cta-card">
                                <div style="display: flex; align-items: center; gap: 14px;">
                                    <div class="reel-camera-cta-icon">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                            <circle cx="12" cy="13" r="4"></circle>
                                        </svg>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 14px;">সরাসরি ক্যামেরা দিয়ে রেকর্ড করুন</div>
                                        <div style="font-size: 12px; color: #64748b;">ওয়েবক্যাম বা ফোন ক্যামেরা দিয়ে শুট করুন</div>
                                    </div>
                                </div>
                                <button type="button" class="btn-fb-primary" style="background: #0f172a; color: #ffffff; padding: 9px 16px; border-radius: 10px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;" onclick="JugajugMediaSuite.startCameraCapture()">
                                    <span>ক্যামেরা খুলুন</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 2: VIDEO EDITOR & TRIM -->
                        <div id="reelStudioStep_editor" style="display: none;">
                            <!-- Crop Ratio -->
                            <div class="studio-control-group">
                                <div class="studio-control-label">ক্রপ অনুপাত (Aspect Ratio):</div>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button type="button" class="hashtag-chip reel-crop-btn active" data-aspect="9:16" onclick="JugajugMediaSuite.setReelCrop('9:16')">9:16 (ভার্টিক্যাল)</button>
                                    <button type="button" class="hashtag-chip reel-crop-btn" data-aspect="1:1" onclick="JugajugMediaSuite.setReelCrop('1:1')">1:1 (বর্গাকার)</button>
                                    <button type="button" class="hashtag-chip reel-crop-btn" data-aspect="full" onclick="JugajugMediaSuite.setReelCrop('full')">মূল অনুপাত</button>
                                </div>
                            </div>

                            <!-- Rotate -->
                            <div class="studio-control-group">
                                <div class="studio-control-label">
                                    <span>ভিডিও ঘোরান:</span>
                                    <span id="reelRotationLabel" style="font-weight: 800; color: #1877f2;">0°</span>
                                </div>
                                <button type="button" class="btn-fb-primary" style="background: #ffffff; border: 1px solid #cbd5e1; color: #1e293b; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;" onclick="JugajugMediaSuite.rotateReelVideo()">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                                    <span>৯০° ঘোরান</span>
                                </button>
                            </div>

                            <!-- Video Trim Sliders -->
                            <div class="studio-control-group">
                                <div class="studio-control-label">
                                    <span>ভিডিও ট্রিম (কাটছাঁট):</span>
                                    <span id="reelTrimDurationLabel" style="font-size: 12px; color: #1877f2; font-weight: 700;">0:00 - 0:15</span>
                                </div>
                                <div style="margin-bottom: 6px;">
                                    <label style="font-size: 11px; color: #64748b;">শুরুর সময় (Start Time):</label>
                                    <input type="range" class="studio-slider" id="reelTrimStartSlider" min="0" max="60" step="0.5" value="0" oninput="JugajugMediaSuite.handleTrimSliderChange()">
                                </div>
                                <div>
                                    <label style="font-size: 11px; color: #64748b;">শেষ সময় (End Time):</label>
                                    <input type="range" class="studio-slider" id="reelTrimEndSlider" min="0" max="60" step="0.5" value="15" oninput="JugajugMediaSuite.handleTrimSliderChange()">
                                </div>
                            </div>

                            <!-- Cover / Thumbnail Frame Selector -->
                            <div class="studio-control-group">
                                <div class="studio-control-label">কভার / থাম্বনেইল নির্বাচন:</div>
                                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                    <button type="button" class="btn-fb-primary" style="background: #ffffff; border: 1px solid #cbd5e1; color: #1e293b; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;" onclick="JugajugMediaSuite.captureCoverFrameFromVideo()">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                        <span>বর্তমান ফ্রেম কভার করুন</span>
                                    </button>
                                    <button type="button" class="btn-fb-primary" style="background: #ffffff; border: 1px solid #cbd5e1; color: #1e293b; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;" onclick="document.getElementById('reelCustomCoverInput').click()">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                        <span>ছবি আপলোড করুন</span>
                                    </button>
                                    <input type="file" id="reelCustomCoverInput" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="JugajugMediaSuite.handleCustomCoverUpload(event)">
                                    <img id="reelSelectedCoverThumb" style="width: 38px; height: 50px; border-radius: 6px; object-fit: cover; display: none; border: 1px solid #cbd5e1;" alt="Cover">
                                </div>
                            </div>

                            <div style="display: flex; gap: 8px; margin-top: 14px;">
                                <button type="button" class="btn-fb-primary" style="flex: 1; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 13px;" onclick="JugajugMediaSuite.setReelStep('media')">‹ পূর্ববর্তী: মিডিয়া</button>
                                <button type="button" class="modal-btn-submit" style="flex: 1.5; margin: 0;" onclick="JugajugMediaSuite.setReelStep('audio')">পরবর্তী: অডিও ও মিউজিক ➔</button>
                            </div>
                        </div>

                        <!-- STEP 3: AUDIO & MUSIC MIXER -->
                        <div id="reelStudioStep_audio" style="display: none;">
                            <div class="studio-control-group">
                                <div class="studio-control-label">ব্যাকগ্রাউন্ড মিউজিক:</div>
                                <button type="button" class="btn-fb-primary" style="background: #1877f2; color: #fff; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;" onclick="JugajugMusicSuite.openModal('reel')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
                                    <span>মিউজিক লাইব্রেরি থেকে ট্র্যাক যোগ করুন</span>
                                </button>

                                <!-- Attached Music Card -->
                                <div class="studio-music-card" id="reelStudioMusicCard" style="display: none;">
                                    <div class="studio-music-info">
                                        <img src="" id="reelStudioMusicThumb" class="studio-music-thumb" alt="Track">
                                        <div>
                                            <div id="reelStudioMusicTitle" style="font-weight: 700; font-size: 13px; color: #0f172a;"></div>
                                            <div id="reelStudioMusicArtist" style="font-size: 11px; color: #64748b;"></div>
                                        </div>
                                    </div>
                                    <button type="button" style="background: none; border: none; color: #ef4444; font-weight: 700; cursor: pointer; font-size: 13px;" onclick="JugajugMediaSuite.removeReelMusic()">✕ সরান</button>
                                </div>
                            </div>

                            <!-- Dual Volume Mixer -->
                            <div class="studio-control-group">
                                <div class="studio-control-label">অডিও ব্যালেন্স ও মিক্সার:</div>
                                <div style="margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                                        <span>ভিডিওর নিজস্ব অডিও ভলিউম:</span>
                                        <span id="reelOriginalVolumeText" style="font-weight: 700;">100%</span>
                                    </div>
                                    <input type="range" class="studio-slider" id="reelOriginalVolumeSlider" min="0" max="100" value="100" oninput="document.getElementById('reelOriginalVolumeText').innerText = this.value + '%'; JugajugMediaSuite.reelOriginalVolume = this.value / 100;">
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                                        <span>ব্যাকগ্রাউন্ড মিউজিক ভলিউম:</span>
                                        <span id="reelMusicVolumeText" style="font-weight: 700;">75%</span>
                                    </div>
                                    <input type="range" class="studio-slider" id="reelMusicVolumeSlider" min="0" max="100" value="75" oninput="document.getElementById('reelMusicVolumeText').innerText = this.value + '%'; JugajugMediaSuite.reelMusicVolume = this.value / 100;">
                                </div>
                                <div>
                                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                                        <span>মিউজিক শুরুর অফসেট (Start Offset):</span>
                                        <span id="reelMusicOffsetLabel" style="font-weight: 700; color: #1877f2;">০:০০</span>
                                    </div>
                                    <input type="range" class="studio-slider" id="reelMusicOffsetSlider" min="0" max="120" step="1" value="0" oninput="JugajugMediaSuite.handleMusicOffsetChange(this.value)">
                                </div>
                            </div>

                            <div style="display: flex; gap: 8px; margin-top: 14px;">
                                <button type="button" class="btn-fb-primary" style="flex: 1; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 13px;" onclick="JugajugMediaSuite.setReelStep('editor')">‹ পূর্ববর্তী: এডিটর</button>
                                <button type="button" class="modal-btn-submit" style="flex: 1.5; margin: 0;" onclick="JugajugMediaSuite.setReelStep('details')">পরবর্তী: বিবরণ ও প্রকাশ ➔</button>
                            </div>
                        </div>

                        <!-- STEP 4: DETAILS & PUBLISH -->
                        <div id="reelStudioStep_details" style="display: none;">
                            <div style="margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label style="font-size: 12px; font-weight: 700; color: #334155;">ক্যাপশন:</label>
                                    <span id="reelCaptionCharCount" style="font-size: 11px; color: #94a3b8;">০ / ২০০০</span>
                                </div>
                                <textarea id="reelCaptionInput" class="modal-textarea" placeholder="রিল সম্পর্কিত ক্যাপশন ও বর্ণনা লিখুন..." style="height: 70px;" oninput="document.getElementById('reelCaptionCharCount').innerText = `${this.value.length} / ২০০০`"></textarea>
                            </div>

                            <!-- Quick Trending Hashtags -->
                            <div style="margin-bottom: 12px;">
                                <span style="font-size: 11px; font-weight: 700; color: #64748b;">ট্রেন্ডিং হ্যাশট্যাগ যোগ করুন:</span>
                                <div class="hashtag-chips-bar">
                                    <span class="hashtag-chip" onclick="JugajugMediaSuite.appendHashtag('Bondhoo')">#Bondhoo</span>
                                    <span class="hashtag-chip" onclick="JugajugMediaSuite.appendHashtag('বন্ধু')">#বন্ধু</span>
                                    <span class="hashtag-chip" onclick="JugajugMediaSuite.appendHashtag('Reels')">#Reels</span>
                                    <span class="hashtag-chip" onclick="JugajugMediaSuite.appendHashtag('Viral')">#Viral</span>
                                    <span class="hashtag-chip" onclick="JugajugMediaSuite.appendHashtag('Bangla')">#Bangla</span>
                                    <span class="hashtag-chip" onclick="JugajugMediaSuite.appendHashtag('Trending')">#Trending</span>
                                    <span class="hashtag-chip" onclick="JugajugMediaSuite.appendHashtag('Creative')">#Creative</span>
                                </div>
                            </div>

                            <!-- Privacy & Comments Switch -->
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                                <span style="font-size: 13px; font-weight: 700; color: #475569;">কারা দেখতে পাবেন:</span>
                                <select id="reelPrivacySelect" style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; font-size: 13px;">
                                    <option value="public">🌐 সর্বজনীন (Public)</option>
                                    <option value="friends">👥 বন্ধুরা (Friends Only)</option>
                                    <option value="only_me">🔒 শুধুমাত্র আমি (Only Me)</option>
                                </select>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" id="reelCommentsEnabledCheck" checked style="width: 16px; height: 16px;">
                                    <label for="reelCommentsEnabledCheck" style="font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">মন্তব্য (Comments) চালু রাখুন</label>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" id="reelDuetEnabledCheck" checked style="width: 16px; height: 16px;">
                                    <label for="reelDuetEnabledCheck" style="font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">রিমিক্স / ডুয়েট অনুমতি</label>
                                </div>
                            </div>

                            <!-- Chunked Resumable Upload Progress Card -->
                            <div class="upload-resumable-progress-card" id="reelUploadProgressBox" style="display: none;">
                                <div class="upload-resumable-header">
                                    <span class="upload-resumable-filename">রিল প্রসেসিং ও আপলোড হচ্ছে...</span>
                                    <span class="upload-resumable-stat" id="reelProgressPercentText">0%</span>
                                </div>
                                <div class="upload-progress-track">
                                    <div class="upload-progress-fill" id="reelProgressBarFill"></div>
                                </div>
                                <div class="upload-resumable-status-text" id="reelProgressStatusText">চাঙ্ক আপলোড শুরু হচ্ছে...</div>
                                <div class="upload-resumable-actions">
                                    <button type="button" class="upload-ctrl-btn upload-ctrl-pause" onclick="JugajugMediaSuite.currentUploader?.isPaused ? JugajugMediaSuite.currentUploader.resume() : JugajugMediaSuite.currentUploader.pause()">বিরতি / চালু</button>
                                    <button type="button" class="upload-ctrl-btn" id="reelRetryUploadBtn" style="display: none; background: #2563eb !important; color: #fff !important; border-color: #2563eb !important;" onclick="JugajugMediaSuite.retryReelUpload()">পুনরায় চেষ্টা করুন</button>
                                    <button type="button" class="upload-ctrl-btn upload-ctrl-cancel" onclick="JugajugMediaSuite.currentUploader?.cancel()">বাতিল</button>
                                </div>
                            </div>

                            <div style="display: flex; gap: 8px; margin-top: 14px;">
                                <button type="button" class="btn-fb-primary" style="flex: 1; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 13px;" onclick="JugajugMediaSuite.setReelStep('audio')">‹ পূর্ববর্তী</button>
                                <button type="button" class="btn-fb-primary" id="reelDraftBtn" style="flex: 1.2; background: #f8fafc; color: #1e293b; border: 1px solid #cbd5e1; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; gap: 6px;" onclick="JugajugMediaSuite.submitReel(true)">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                                    <span>ড্রাফট সেভ</span>
                                </button>
                                <button type="button" class="modal-btn-submit" id="reelSubmitBtn" style="flex: 1.8; margin: 0; display: inline-flex; align-items: center; justify-content: center; gap: 6px;" onclick="JugajugMediaSuite.submitReel(false)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                    <span>রিল প্রকাশ করুন</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         ROYALTY-FREE MUSIC LIBRARY MODAL
         ========================================================================= -->
    <div class="modal-overlay" id="musicLibraryModal" style="display: none; z-index: 100001;">
        <div class="modal-box" style="max-width: 580px;">
            <div class="modal-header">
                <span class="modal-title" style="display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1877f2" stroke-width="2"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
                    <span>ব্যাকগ্রাউন্ড মিউজিক লাইব্রেরি (Royalty Free)</span>
                </span>
                <button type="button" class="modal-close" onclick="JugajugMusicSuite.closeModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" id="musicSearchInput" class="modal-input" placeholder="মিউজিক ট্র্যাক বা শিল্পীর নাম অনুসন্ধান করুন..." oninput="JugajugMusicSuite.loadTracks('', this.value)" style="margin-bottom: 10px;">
                
                <!-- Genre Filter Pills -->
                <div class="hashtag-chips-bar" style="margin-bottom: 12px;">
                    <span class="hashtag-chip" onclick="JugajugMusicSuite.loadTracks('all')">সব</span>
                    <span class="hashtag-chip" onclick="JugajugMusicSuite.loadTracks('Folk')">ফোক (Folk)</span>
                    <span class="hashtag-chip" onclick="JugajugMusicSuite.loadTracks('Electronic')">ইলেকট্রনিক</span>
                    <span class="hashtag-chip" onclick="JugajugMusicSuite.loadTracks('Classical')">ক্লাসিক্যাল</span>
                    <span class="hashtag-chip" onclick="JugajugMusicSuite.loadTracks('Lo-Fi')">লো-ফাই (Lo-Fi)</span>
                    <span class="hashtag-chip" onclick="JugajugMusicSuite.loadTracks('Festive')">উৎসব (Festive)</span>
                </div>

                <!-- Tracks List Container -->
                <div class="music-library-list" id="musicTracksList">
                    <!-- Populated via API -->
                </div>
            </div>
        </div>
    </div>

    <!-- COMMENTS MODAL -->
    <div class="modal-overlay" id="commentsModal" style="display: none;">
        <div class="modal-box" style="max-width: 540px; height: 550px; display: flex; flex-direction: column;">
            <div class="modal-header">
                <span class="modal-title">মন্তব্যসমূহ</span>
                <button class="modal-close" onclick="closeCommentsModal()">✕</button>
            </div>
            <div id="commentsList" style="flex: 1; overflow-y: auto; padding: 14px;">
                <!-- Comments dynamically loaded -->
            </div>
            <div style="padding: 12px; border-top: 1px solid var(--fb-border); display: flex; gap: 8px;">
                <input type="text" id="commentTextInput" placeholder="একটি মন্তব্য লিখুন..." style="flex: 1; border: 1px solid var(--fb-border); border-radius: 20px; padding: 8px 14px; font-size: 14px; outline: none;" onkeydown="if(event.key==='Enter') submitComment()">
                <button class="post-btn" style="background: var(--fb-primary); color: white;" onclick="submitComment()">পাঠান</button>
            </div>
        </div>
    </div>

    <!-- POST SHARE MODAL -->
    <div class="modal-overlay" id="shareModal" style="display: none;">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title">পোস্ট শেয়ার করুন</span>
                <button class="modal-close" onclick="closeShareModal()">✕</button>
            </div>
            <div class="modal-body">
                <textarea class="modal-textarea" id="shareCaptionInput" placeholder="আপনার অনুভূতি বা ক্যাপশন লিখুন (ঐচ্ছিক)..." style="height: 90px;"></textarea>
                <button class="modal-btn-submit" onclick="submitShare()">শেয়ার করুন ↗️</button>
            </div>
        </div>
    </div>

    <!-- REPORT MODAL -->
    <div class="modal-overlay" id="reportModal" style="display: none;">
        <div class="modal-box">
            <div class="modal-header">
                <span class="modal-title">কনটেন্ট রিপোর্ট করুন</span>
                <button class="modal-close" onclick="closeReportModal()">✕</button>
            </div>
            <div class="modal-body">
                <label style="font-size: 13px; font-weight: 700;">রিপোর্টের কারণ নির্বাচন করুন:</label>
                <select id="reportReasonSelect" style="width: 100%; height: 38px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 10px; font-size: 14px; margin: 8px 0 12px;">
                    <option value="spam">স্প্যাম বা প্রতারণা (Spam)</option>
                    <option value="harassment">হয়রানি বা আক্রমণ (Harassment)</option>
                    <option value="hate_speech">বিদ্বেষমূলক বক্তব্য (Hate Speech)</option>
                    <option value="false_information">মিথ্যা বা বিভ্রান্তিকর তথ্য (False Info)</option>
                    <option value="violence">সহিংসতা বা উসকানি (Violence)</option>
                    <option value="other">অন্যান্য (Other)</option>
                </select>
                <textarea class="modal-textarea" id="reportDetailsText" placeholder="বিস্তারিত লিখুন (ঐচ্ছিক)..." style="height: 80px;"></textarea>
                <button class="modal-btn-submit" style="background: #e91e63;" onclick="submitReport()">রিপোর্ট সাবমিট করুন</button>
            </div>
        </div>
    </div>

    <!-- REAL-TIME ENTERPRISE FLOATING MESSENGER CHAT BOX -->
    <div id="messengerChatBox" class="jj-chat-box" style="display: none;">
        <!-- Chat Header -->
        <div class="jj-chat-header">
            <div id="messengerChatUserLink" onclick="navigateToActiveChatUserProfile(event)" style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1; cursor: pointer; text-decoration: none; color: inherit; padding: 2px 4px; border-radius: 8px; transition: background 0.15s ease;" title="প্রোফাইল দেখতে ক্লিক করুন">
                <div style="position: relative; flex-shrink: 0;" title="প্রোফাইল দেখুন">
                    <div class="avatar" id="messengerChatAvatar" style="width: 36px; height: 36px; font-size: 14px; cursor: pointer; transition: transform 0.15s ease, opacity 0.15s ease;">র</div>
                    <span id="messengerChatOnlineDot" class="status-indicator online" style="position: absolute; bottom: 0; right: 0; border: 2px solid white;"></span>
                </div>
                <div style="min-width: 0; flex: 1;" title="প্রোফাইল দেখুন">
                    <div id="messengerChatTitle" style="font-weight: 700; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; transition: color 0.15s ease;">চ্যাট</div>
                    <div id="messengerChatSubtitle" style="font-size: 11px; opacity: 0.85; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">অফলাইন · প্রোফাইল দেখুন ↗</div>
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

    <!-- WHO REACTED MODAL (ENTERPRISE LIKE & REACTION AUDIT) -->
    <div class="modal-overlay" id="whoReactedModal" style="display: none; z-index: 100005;">
        <div class="modal-box" style="max-width: 480px; height: 500px; display: flex; flex-direction: column;">
            <div class="modal-header">
                <span class="modal-title" style="display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1877f2" stroke-width="2.2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
                    <span>পোস্টে রিঅ্যাকশনসমূহ</span>
                </span>
                <button class="modal-close" onclick="closeWhoReactedModal()">✕</button>
            </div>

            <!-- Reaction Filter Tabs -->
            <div id="whoReactedTabsBar" style="display: flex; gap: 4px; padding: 8px 12px; border-bottom: 1px solid var(--fb-border); overflow-x: auto; scrollbar-width: none;">
                <button class="sr-tab-btn active" style="padding: 6px 12px; font-size: 13px;" onclick="filterWhoReactedTab('all', this)" id="wrTabAll">সব (0)</button>
                <button class="sr-tab-btn" style="padding: 6px 12px; font-size: 13px;" onclick="filterWhoReactedTab('like', this)" id="wrTabLike">👍 লাইক</button>
                <button class="sr-tab-btn" style="padding: 6px 12px; font-size: 13px;" onclick="filterWhoReactedTab('love', this)" id="wrTabLove">❤️ লাভ</button>
                <button class="sr-tab-btn" style="padding: 6px 12px; font-size: 13px;" onclick="filterWhoReactedTab('haha', this)" id="wrTabHaha">😆 হাহা</button>
                <button class="sr-tab-btn" style="padding: 6px 12px; font-size: 13px;" onclick="filterWhoReactedTab('wow', this)" id="wrTabWow">😮 ওয়াও</button>
                <button class="sr-tab-btn" style="padding: 6px 12px; font-size: 13px;" onclick="filterWhoReactedTab('sad', this)" id="wrTabSad">😢 স্যাড</button>
                <button class="sr-tab-btn" style="padding: 6px 12px; font-size: 13px;" onclick="filterWhoReactedTab('angry', this)" id="wrTabAngry">😡 এংগ্রি</button>
            </div>

            <!-- Users List Container -->
            <div id="whoReactedList" style="flex: 1; overflow-y: auto; padding: 12px;">
                <div style="padding: 24px; text-align: center; color: var(--fb-text-secondary);">লোড হচ্ছে...</div>
            </div>
        </div>
    </div>

    <!-- EDIT POST MODAL -->
    <div class="modal-overlay" id="editPostModal" style="display: none; z-index: 100004;">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header">
                <span class="modal-title">পোস্ট সম্পাদনা করুন</span>
                <button class="modal-close" onclick="closeEditPostModal()">✕</button>
            </div>
            <div class="modal-body" style="padding: 16px;">
                <input type="hidden" id="editPostId">
                <textarea id="editPostText" class="modal-textarea" style="height: 120px;" placeholder="পোস্টের নতুন বিবরণ লিখুন..."></textarea>
                <div style="margin-top: 14px; display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="post-btn" onclick="closeEditPostModal()">বাতিল</button>
                    <button type="button" class="modal-btn-submit" style="width: auto; padding: 8px 24px;" onclick="submitPostUpdate()">সংরক্ষণ করুন</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ENHANCED FULLSCREEN IMAGE LIGHTBOX MODAL -->
    <div class="modal-overlay" id="imageLightboxModal" style="display: none; z-index: 100010; background: rgba(10, 15, 29, 0.95); backdrop-filter: blur(12px); flex-direction: column; justify-content: space-between; padding: 16px; user-select: none;" onclick="closeLightboxModal()">
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

    <!-- FRIENDS & REQUESTS MODAL -->
    <div class="modal-overlay" id="friendsModal" style="display: none;">
        <div class="modal-box" style="max-width: 520px; height: 500px; display: flex; flex-direction: column;">
            <div class="modal-header">
                <span class="modal-title">বন্ধু ও রিকোয়েস্টসমূহ</span>
                <button class="modal-close" onclick="closeFriendsModal()">✕</button>
            </div>
            <div style="display: flex; border-bottom: 1px solid var(--fb-border);">
                <button class="post-btn" style="flex: 1; border-radius: 0; font-weight: 700;" onclick="loadFriendRequestsTab()">রিকোয়েস্টসমূহ</button>
                <button class="post-btn" style="flex: 1; border-radius: 0; font-weight: 700;" onclick="loadFriendsListTab()">বন্ধুদের তালিকা</button>
            </div>
            <div id="friendsModalBody" style="flex: 1; overflow-y: auto; padding: 14px;">
                <!-- Dynamically loaded -->
            </div>
        </div>
    </div>

    <!-- USER PROFILE MODAL -->
    <!-- USER PROFILE ENTERPRISE DRAWER MODAL -->
    <div class="modal-overlay" id="userProfileModal" style="display: none; align-items: center; justify-content: center; z-index: 100000; backdrop-filter: blur(8px); background: rgba(15, 23, 42, 0.65);">
        <div class="modal-box" style="max-width: 540px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; padding: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid var(--fb-border); background: var(--fb-surface, #ffffff);">
            <!-- Drawer Header -->
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; align-items: center; justify-content: space-between; background: var(--fb-card-bg, #ffffff);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(24, 119, 242, 0.1); color: #1877f2; display: flex; align-items: center; justify-content: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div>
                        <h3 style="font-size: 16px; font-weight: 800; margin: 0; color: var(--fb-text-primary);">আমার অ্যাকাউন্ট ও নেভিগেশন</h3>
                        <p style="font-size: 11px; margin: 0; color: var(--fb-text-secondary);">প্রোফাইল পরিচালনা, নিরাপত্তা ও গোপনীয়তা সেটিংস</p>
                    </div>
                </div>
                <button type="button" class="modal-close" onclick="closeUserProfileModal()" aria-label="বন্ধ করুন" style="width: 32px; height: 32px; border-radius: 50%; border: none; background: var(--fb-hover); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--fb-text-secondary); transition: all 0.2s;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <!-- Drawer Scrollable Body -->
            <div class="modal-body" style="padding: 16px 20px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 16px;">
                <!-- Profile Identity Card -->
                <div style="background: linear-gradient(135deg, rgba(24, 119, 242, 0.08) 0%, rgba(59, 130, 246, 0.03) 100%); border: 1px solid rgba(24, 119, 242, 0.15); border-radius: 12px; padding: 14px 16px;">
                    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 12px;">
                        <div class="avatar" style="width: 60px; height: 60px; border-radius: 50%; border: 3px solid #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: #1e293b; flex-shrink: 0;" id="profileModalAvatar">
                            <img src="/images/default-avatar.svg" style="width:100%;height:100%;object-fit:cover;" alt="User">
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <h3 style="font-size: 17px; font-weight: 800; margin: 0; color: var(--fb-text-primary); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" id="profileModalName">আমার প্রোফাইল</h3>
                                <span id="profileModalVerifiedBadge" style="display: none; align-items: center; color: #1877f2;" title="যাচাইকৃত প্রোফাইল">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                </span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-top: 2px;">
                                <span style="color: var(--fb-text-secondary); font-size: 13px; font-weight: 500;" id="profileModalUsername">@user</span>
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 1px 7px; border-radius: 4px; background: rgba(0,0,0,0.05); font-family: monospace; font-size: 11px; font-weight: 700; color: var(--fb-text-secondary);" title="প্রোফাইল আইডি">
                                    <span>ID: #<span id="profileModalId">{{ auth()->id() ?? '1' }}</span></span>
                                    <button type="button" onclick="copyProfileModalId()" style="background:none;border:none;padding:0;cursor:pointer;color:inherit;display:inline-flex;" title="আইডি কপি করুন">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    </button>
                                </span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; font-size: 12px; margin-top: 6px; color: var(--fb-text-secondary);">
                                <span><strong id="profileModalFriendsCount" style="color: var(--fb-text-primary); font-weight: 700;">0</strong> বন্ধুরা</span>
                                <span>•</span>
                                <span><strong id="profileModalFollowersCount" style="color: var(--fb-text-primary); font-weight: 700;">0</strong> ফলোয়ার</span>
                                <span>•</span>
                                <span><strong id="profileModalFollowingCount" style="color: var(--fb-text-primary); font-weight: 700;">0</strong> ফলোয়িং</span>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Public Profile Button -->
                    <button type="button" onclick="goToMyProfile()" style="width: 100%; height: 40px; background: #1877f2; color: #ffffff; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 6px rgba(24, 119, 242, 0.3);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span>সম্পূর্ণ প্রোফাইল ভিউ করুন (View Profile)</span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>

                <!-- CATEGORY 1: PROFILE & IDENTITY -->
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <span style="font-size: 12px; font-weight: 700; color: var(--fb-text-secondary); text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>প্রোফাইল ও জীবনবৃত্তান্ত</span>
                        </span>
                        <span style="font-size: 11px; color: var(--fb-text-secondary);">পরিচিতি তথ্য</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('edit-basic')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #2563eb;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">প্রোফাইল এডিট ও Bio</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">নাম, ছবি ও বায়ো</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('about')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #0891b2;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">পরিচিতি ও তথ্য</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">সম্পূর্ণ জীবনবৃত্তান্ত</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('edit-experience')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #7c3aed;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">শিক্ষা ও কর্মজীবন</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">প্রতিষ্ঠান ও ডিগ্রি</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('story')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #e11d48;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">স্টোরিজ ও হাইলাইটস</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">দৈনন্দিন মুহূর্তসমূহ</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CATEGORY 2: CONNECTIONS & MEDIA -->
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <span style="font-size: 12px; font-weight: 700; color: var(--fb-text-secondary); text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span>সোশ্যাল সংযোগ ও মিডিয়া</span>
                        </span>
                        <span style="font-size: 11px; color: var(--fb-text-secondary);">নেটওয়ার্ক ও গ্যালারি</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('friends')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #059669;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">বন্ধুরা ও অনুরোধ</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">অনুরোধ ও ফ্রেন্ডলিস্ট</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('photos')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #d97706;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">ছবি ও অ্যালবাম</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">ফটো গ্যালারি কালেকশন</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('videos')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #dc2626;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">ভিডিও ও রিলস</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">শর্ট ও ফুল ভিডিও</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('followers')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #4f46e5;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">ফলোয়ার ও ফলোয়িং</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">অডিয়েন্স তালিকা</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CATEGORY 3: PRIVACY & SECURITY -->
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <span style="font-size: 12px; font-weight: 700; color: var(--fb-text-secondary); text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <span>গোপনীয়তা, নিরাপত্তা ও সুরক্ষা</span>
                        </span>
                        <span style="font-size: 11px; color: var(--fb-text-secondary);">নিরাপত্তা কেন্দ্র</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('privacy')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #2563eb;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">প্রাইভেসি সেন্টার</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">কে কি দেখতে পাবে</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('lock')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #4f46e5;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">প্রোফাইল গার্ড ও লক</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">ছবি ও পোস্ট লক করুন</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('verify')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #0284c7;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">ভেরিফিকেশন ব্যাজ</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">পরিচয় যাচাইকরণ</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('security')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #0d9488;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">ডিভাইস ও সিকিউরিটি</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">সক্রিয় সেশন ও পাসওয়ার্ড</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CATEGORY 4: INSIGHTS & NOTIFICATIONS -->
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <span style="font-size: 12px; font-weight: 700; color: var(--fb-text-secondary); text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                            <span>ইনসাইটস ও প্রেফারেন্স</span>
                        </span>
                        <span style="font-size: 11px; color: var(--fb-text-secondary);">পারফরম্যান্স ও অ্যালার্ট</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('analytics')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #6366f1;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">ভিউ অ্যানালিটিক্স</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">ভিজিটর ও এনগেজমেন্ট</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('notifications')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #f59e0b;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">নোটিফিকেশন সেটিংস</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">অ্যালার্ট প্রেফারেন্স</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="goToMyProfile('blocking')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #ef4444;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">ব্লকিং সেন্টার</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">ব্লককৃত ব্যবহারকারী</div>
                                </div>
                            </div>
                        </div>
                        <div class="sidebar-item" style="border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.2s;" onclick="exportProfileDataFromDrawer()">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="color: #10b981;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-primary);">ডেটা ব্যাকআপ</div>
                                    <div style="font-size: 11px; color: var(--fb-text-secondary);">JSON এক্সপোর্ট</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Drawer Footer Action Bar -->
            <div style="padding: 12px 20px; border-top: 1px solid var(--fb-border); background: var(--fb-card-bg, #ffffff); display: flex; gap: 10px; align-items: center;">
                <button type="button" style="flex: 1; height: 38px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 8px; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; transition: all 0.2s;" onclick="logout()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    <span>লগআউট</span>
                </button>
                <button type="button" style="flex: 1; height: 38px; background: var(--fb-hover); color: var(--fb-text-primary); border: 1px solid var(--fb-border); border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; transition: all 0.2s;" onclick="logoutAllOtherDevices()" title="বর্তমান ডিভাইস বাদে অন্য সব সেশন শেষ করুন">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                    <span>অন্যান্য ডিভাইস লগআউট</span>
                </button>
            </div>
        </div>
    </div>

    <!-- REGISTRATION MODAL -->
    <div class="modal-overlay" id="registerModal" style="display: none; z-index: 9999;">
        <div class="modal-box" style="max-width: 440px;">
            <div class="modal-header">
                <span class="modal-title">নতুন একাউন্ট সাইন-আপ</span>
                <button class="modal-close" onclick="closeRegisterModal()">✕</button>
            </div>
            <div class="modal-body">
                <form onsubmit="handleRegistration(event)" style="display: flex; flex-direction: column; gap: 10px;">
                    <input type="text" id="regName" class="search-input" style="height: 44px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px; font-size: 14px;" placeholder="আপনার পুরো নাম" required>
                    <input type="text" id="regUsername" class="search-input" style="height: 44px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px; font-size: 14px;" placeholder="ইউজারনেম (e.g. rahim2026)" required>
                    <input type="email" id="regEmail" class="search-input" style="height: 44px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px; font-size: 14px;" placeholder="ইমেইল অ্যাড্রেস" required>
                    <input type="password" id="regPassword" class="search-input" style="height: 44px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px; font-size: 14px;" placeholder="পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)" required>
                    <button type="submit" class="modal-btn-submit" style="background: #42b72a; height: 44px; font-size: 16px;">সাইন-আপ সম্পন্ন করুন</button>
                </form>
            </div>
        </div>
    </div>

    <!-- FORGOT PASSWORD MODAL -->
    <div class="modal-overlay" id="forgotPasswordModal" style="display: none; z-index: 9999;">
        <div class="modal-box" style="max-width: 440px;">
            <div class="modal-header">
                <span class="modal-title">পাসওয়ার্ড রিসেট</span>
                <button class="modal-close" onclick="closeForgotPasswordModal()">✕</button>
            </div>
            <div class="modal-body">
                <form onsubmit="handleForgotPassword(event)" style="display: flex; flex-direction: column; gap: 10px;">
                    <p style="font-size: 13px; color: var(--fb-text-secondary); margin: 0;">আপনার অ্যাকাউন্টের ইমেইল প্রদান করুন। রিসেট কোড তৈরি করা হবে।</p>
                    <input type="email" id="forgotEmail" class="search-input" style="height: 44px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px; font-size: 14px;" placeholder="ইমেইল অ্যাড্রেস" required>
                    <button type="submit" class="modal-btn-submit" style="background: var(--fb-primary); height: 44px;">রিসেট কোড পাঠান</button>
                </form>
                <div id="resetPasswordBox" style="display: none; margin-top: 14px; border-top: 1px solid var(--fb-border); padding-top: 12px;">
                    <input type="text" id="resetTokenInput" class="search-input" style="height: 40px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px; font-size: 13px; margin-bottom: 8px;" placeholder="রিসেট টোকেন">
                    <input type="password" id="resetNewPass" class="search-input" style="height: 40px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px; font-size: 13px; margin-bottom: 8px;" placeholder="নতুন পাসওয়ার্ড">
                    <button class="modal-btn-submit" style="background: #10b981; height: 40px;" onclick="submitNewPassword()">পাসওয়ার্ড পরিবর্তন নিশ্চিত করুন</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2FA CHALLENGE MODAL -->
    <div class="modal-overlay" id="twoFactorModal" style="display: none;">
        <div class="modal-box" style="max-width: 380px;">
            <div class="modal-header">
                <span class="modal-title">🔐 টু-ফ্যাক্টর ভেরিফিকেশন</span>
            </div>
            <div class="modal-body" style="text-align: center;">
                <p style="font-size: 14px; color: var(--fb-text-secondary);">আপনার অ্যাকাউন্টে 2FA সক্রিয় আছে। প্রমাণীকরণ অ্যাপের ৬-ডিজিট কোড লিখুন:</p>
                <input type="text" id="twoFactorCodeInput" class="search-input" style="height: 46px; border: 1px solid var(--fb-border); border-radius: 8px; font-size: 20px; text-align: center; letter-spacing: 4px; margin: 12px 0;" placeholder="000000" maxlength="6">
                <button class="modal-btn-submit" onclick="submitTwoFactorChallenge()">ভেরিফাই ও প্রবেশ করুন</button>
            </div>
        </div>
    </div>

    <!-- PROFESSIONAL ENTERPRISE LIVE SETUP & HARDWARE PREVIEW MODAL -->
    <div class="modal-overlay" id="createLiveModal" style="display: none;">
        <div class="modal-box" style="max-width: 560px; border-radius: 16px; overflow: hidden; padding: 0; box-shadow: 0 16px 48px rgba(0,0,0,0.35); border: 1px solid var(--fb-border);">
            <div class="modal-header" style="background: var(--fb-card); border-bottom: 1px solid var(--fb-border); padding: 16px 22px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="background: #ef4444; color: white; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px; letter-spacing: 0.5px;">LIVE</span>
                    <span class="modal-title" style="font-size: 16px; font-weight: 800; color: var(--fb-text-primary);">লাইভ সম্প্রচার সেটআপ</span>
                </div>
                <button type="button" class="modal-close" onclick="closeCreateLiveModal()" style="font-size: 18px; color: var(--fb-text-secondary); background: none; border: none; cursor: pointer;">✕</button>
            </div>
            <div class="modal-body" style="padding: 22px;">
                <!-- Real Camera & Mic Hardware Preview -->
                <div id="liveModalCamPreviewBox" style="position: relative; width: 100%; height: 250px; background: #07090e; border-radius: 12px; overflow: hidden; margin-bottom: 18px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.08);">
                    <video id="dashboardLiveCamPreview" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover; display: none;"></video>
                    
                    <div id="liveModalPermLoading" style="text-align: center; color: white; padding: 24px;">
                        <div style="width: 54px; height: 54px; border-radius: 50%; background: rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                        </div>
                        <div style="font-size: 14px; font-weight: 700;">ক্যামেরা ও মাইক্রোফোন সংযোগ যাচাই করা হচ্ছে...</div>
                    </div>

                    <div id="liveModalPermError" style="display: none; text-align: center; color: #fca5a5; padding: 24px;">
                        <div style="width: 54px; height: 54px; border-radius: 50%; background: rgba(239,68,68,0.15); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; color: #ef4444;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </div>
                        <div style="font-size: 15px; font-weight: 800; color: #ef4444; margin-bottom: 4px;">ক্যামেরা বা অডিও অনুমতি আবশ্যক</div>
                        <div style="font-size: 12px; color: #94a3b8; margin-bottom: 14px; line-height: 1.5;">লাইভ ব্রডকাস্ট করার জন্য অনুগ্রহ করে আপনার ব্রাউজারে মিডিয়া অ্যাক্সেস অনুমোদন করুন।</div>
                        <button type="button" class="post-btn" style="background: #ef4444; color: white; font-size: 12px; font-weight: 700; padding: 7px 18px; border-radius: 20px;" onclick="requestLiveMediaPermissions()">অনুমতি পুনরায় চেষ্টা করুন</button>
                    </div>

                    <!-- Hardware active badges -->
                    <div id="liveModalDevicePills" style="position: absolute; bottom: 12px; left: 12px; display: none; gap: 8px; z-index: 10;">
                        <span style="background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); color: #34d399; font-size: 11px; padding: 4px 10px; border-radius: 6px; font-weight: 700; border: 1px solid rgba(52,211,153,0.3); display: inline-flex; align-items: center; gap: 4px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            ক্যামেরা সক্রিয়
                        </span>
                        <span style="background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); color: #34d399; font-size: 11px; padding: 4px 10px; border-radius: 6px; font-weight: 700; border: 1px solid rgba(52,211,153,0.3); display: inline-flex; align-items: center; gap: 4px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            মাইক সক্রিয়
                        </span>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 800; display: block; margin-bottom: 6px; color: var(--fb-text-primary);">লাইভের শিরোনাম:</label>
                        <input type="text" id="liveStreamTitleInput" class="search-input" style="height: 44px; width: 100%; border: 1px solid var(--fb-border); border-radius: 10px; padding: 0 14px; font-size: 14px;" placeholder="লাইভের আকর্ষণীয় শিরোনাম লিখুন (e.g. আমার সরাসরি আড্ডা)">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label style="font-size: 13px; font-weight: 800; display: block; margin-bottom: 6px; color: var(--fb-text-primary);">প্রাইভেসি নির্বাচন:</label>
                            <select id="liveStreamPrivacySelect" style="width: 100%; height: 42px; border: 1px solid var(--fb-border); border-radius: 10px; padding: 0 12px; font-size: 13px; background: var(--fb-card); color: inherit;">
                                <option value="public">🌐 পাবলিক (সবার জন্য)</option>
                                <option value="friends">👥 বন্ধুরা (কেবল ফ্রেন্ডস)</option>
                                <option value="only_me">🔒 শুধুমাত্র আমি (টেস্টিং)</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 13px; font-weight: 800; display: block; margin-bottom: 6px; color: var(--fb-text-primary);">ইন্টারঅ্যাকশন:</label>
                            <div style="height: 42px; display: flex; align-items: center; gap: 14px; font-size: 12px; font-weight: 700; color: var(--fb-text-primary);">
                                <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="checkbox" id="modalCommentsEnabled" checked> চ্যাট
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="checkbox" id="modalReactionsEnabled" checked> রিঅ্যাকশন
                                </label>
                            </div>
                        </div>
                    </div>

                    <div style="background: var(--fb-hover); border: 1px solid var(--fb-border); border-radius: 10px; padding: 12px 16px; font-size: 12px; color: var(--fb-text-secondary); display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 20px;">💾</span>
                        <span>লাইভ সমাপ্তির পর সম্পূর্ণ সেশনটি স্বয়ংক্রিয়ভাবে ভিডিও পোস্ট হিসেবে প্রোফাইল ও টাইমলাইনে সংরক্ষিত হবে।</span>
                    </div>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="button" id="startLiveSubmitBtn" class="modal-btn-submit" style="flex: 1; background: #ef4444; color: white; font-weight: 800; font-size: 15px; height: 46px; border-radius: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; border: none; cursor: pointer; box-shadow: 0 4px 14px rgba(239,68,68,0.35);" onclick="submitStartLiveStream()">
                        <span style="width: 8px; height: 8px; background: white; border-radius: 50%;"></span>
                        <span>সরাসরি লাইভ শুরু করুন (Go Live)</span>
                    </button>
                    <a href="{{ route('live.studio') }}" class="post-btn" style="background: var(--fb-bg); border: 1px solid var(--fb-border); color: var(--fb-text-primary); text-decoration: none; font-weight: 700; font-size: 13px; height: 46px; padding: 0 16px; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px;" title="অ্যাডভান্সড স্টুডিও">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                        <span>স্টুডিও মোড</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- CREATE MARKETPLACE PRODUCT MODAL -->
    <div class="modal-overlay" id="createProductModal" style="display: none;">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header">
                <span class="modal-title">🏪 মার্কেটপ্লেস লিস্টিং তৈরি করুন</span>
                <button class="modal-close" onclick="closeCreateProductModal()">✕</button>
            </div>
            <div class="modal-body">
                <input type="text" id="prodTitleInput" class="search-input" style="height: 40px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px;" placeholder="পণ্যের নাম / শিরোনাম (e.g. iPhone 15 Pro)">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700;">ক্যাটাগরি:</label>
                        <select id="prodCategorySelect" style="width: 100%; height: 38px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 8px; font-size: 13px;">
                            <!-- populated via js -->
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700;">মূল্য (টাকা / BDT):</label>
                        <input type="number" id="prodPriceInput" class="search-input" style="height: 38px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 10px;" placeholder="২৫০০">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700;">কন্ডিশন:</label>
                        <select id="prodConditionSelect" style="width: 100%; height: 38px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 8px; font-size: 13px;">
                            <option value="new">একদম নতুন (Brand New)</option>
                            <option value="used_like_new">ব্যবহৃত (নতুনের মতো)</option>
                            <option value="used_good">ব্যবহৃত (ভালো)</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700;">লোকেশন / এলাকা:</label>
                        <input type="text" id="prodLocationInput" class="search-input" style="height: 38px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 10px;" placeholder="ঢাকা, বাংলাদেশ" value="ধানমন্ডি, ঢাকা">
                    </div>
                </div>

                <textarea id="prodDescInput" class="search-input" style="height: 70px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 8px 12px; resize: none;" placeholder="পণ্যের বিস্তারিত বিবরণ..."></textarea>

                <button class="modal-btn-submit" onclick="submitCreateProduct()">লিস্টিং পোস্ট করুন 🛍️</button>
            </div>
        </div>
    </div>

    <!-- ORDER / BUY PRODUCT MODAL (WITH BKASH/NAGAD CHECKOUT & ESCROW) -->
    <div class="modal-overlay" id="orderProductModal" style="display: none;">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-header">
                <span class="modal-title">💳 এসক্রো চেকআউট ও ক্রয়</span>
                <button class="modal-close" onclick="closeOrderProductModal()">✕</button>
            </div>
            <div class="modal-body" id="orderModalBody">
                <!-- Dynamically populated during checkout -->
            </div>
        </div>
    </div>

    <!-- CREATE GROUP MODAL -->
    <div class="modal-overlay" id="createGroupModal" style="display: none;">
        <div class="modal-box" style="max-width: 520px; border-radius: 16px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 22px;">🌐</span>
                    <span class="modal-title" style="font-weight: 800; font-size: 17px;">নতুন কমিউনিটি তৈরি করুন (V2 OS)</span>
                </div>
                <button class="modal-close" onclick="closeCreateGroupModal()">✕</button>
            </div>
            <div class="modal-body" style="display: flex; flex-direction: column; gap: 14px; max-height: 80vh; overflow-y: auto; padding: 20px;">
                <div>
                    <label style="font-size: 13px; font-weight: 700; margin-bottom: 6px; display: block;">কমিউনিটির নাম *</label>
                    <input type="text" id="groupNameInput" class="search-input" style="height: 40px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px; width: 100%;" placeholder="উদাঃ বাংলাদেশ আর্টিফিশিয়াল ইন্টেলিজেন্স হাব">
                </div>

                <div>
                    <label style="font-size: 13px; font-weight: 700; margin-bottom: 6px; display: block;">ইউজারনেম / হ্যান্ডেল (ঐচ্ছিক)</label>
                    <input type="text" id="groupUsernameInput" class="search-input" style="height: 40px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px; width: 100%;" placeholder="উদাঃ bd-ai-hub">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 700; margin-bottom: 6px; display: block;">ক্যাটাগরি</label>
                        <select id="groupCategoryInput" style="width: 100%; height: 40px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 10px; font-size: 13px; background: white;">
                            <option value="Technology">প্রযুক্তি ও কোডিং</option>
                            <option value="Business">ব্যবসা ও ক্যারিয়ার</option>
                            <option value="Education">শিক্ষা ও গবেষণা</option>
                            <option value="Community">সামাজিক ও আঞ্চলিক</option>
                            <option value="Gaming">গেমিং ও স্পোর্টস</option>
                            <option value="Lifestyle">লাইফস্টাইল</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 700; margin-bottom: 6px; display: block;">কমিউনিটি টাইপ ও অ্যাক্সেস</label>
                        <select id="groupPrivacySelect" style="width: 100%; height: 40px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 10px; font-size: 13px; background: white;">
                            <option value="public">🌐 পাবলিক (সবার জন্য উন্মুক্ত)</option>
                            <option value="controlled">📑 নিয়ন্ত্রিত (স্ক্রিনিং সাপেক্ষ)</option>
                            <option value="private">🔒 প্রাইভেট (অনুমোদন সাপেক্ষ)</option>
                            <option value="project">💼 প্রজেক্ট / কোলাবোরেশন</option>
                            <option value="hidden">🛡️ সিক্রেট (লুকানো)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="font-size: 13px; font-weight: 700; margin-bottom: 6px; display: block;">কমিউনিটির বিবরণ ও উদ্দেশ্য</label>
                    <textarea id="groupDescInput" class="search-input" style="height: 75px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 8px 12px; resize: none; width: 100%; font-size: 13px;" placeholder="কমিউনিটির লক্ষ্য ও নিয়মাবলী..."></textarea>
                </div>

                <button class="modal-btn-submit" id="btnSubmitCreateCommunity" style="background: #4f46e5; color: white; font-weight: 800; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-size: 14px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);" onclick="submitCreateGroup()">
                    কমিউনিটি তৈরি সম্পন্ন করুন 🎉
                </button>
            </div>
        </div>
    </div>

    <!-- CREATE PAGE MODAL -->
    <div class="modal-overlay" id="createPageModal" style="display: none;">
        <div class="modal-box" style="max-width: 460px;">
            <div class="modal-header">
                <span class="modal-title">🚩 নতুন পেজ তৈরি করুন</span>
                <button class="modal-close" onclick="closeCreatePageModal()">✕</button>
            </div>
            <div class="modal-body">
                <input type="text" id="pageNameInput" class="search-input" style="height: 42px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px;" placeholder="পেজের নাম (e.g. টেক রিভিউ বিডি)">
                <input type="text" id="pageCategoryInput" class="search-input" style="height: 42px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 12px;" placeholder="ক্যাটাগরি (e.g. Media, Business, Creator)">
                <textarea id="pageBioInput" class="search-input" style="height: 80px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 8px 12px; resize: none;" placeholder="পেজের বিবরণ / বায়ো..."></textarea>

                <button class="modal-btn-submit" style="background: #f35369;" onclick="submitCreatePage()">পেজ তৈরি সম্পন্ন করুন 🚀</button>
            </div>
        </div>
    </div>

    <!-- PAGES DIRECTORY MODAL -->
    <div class="modal-overlay" id="pagesModal" style="display: none;">
        <div class="modal-box" style="max-width: 520px; height: 500px; display: flex; flex-direction: column;">
            <div class="modal-header">
                <span class="modal-title">🚩 পেজসমূহ (Pages)</span>
                <button class="modal-close" onclick="closePagesModal()">✕</button>
            </div>
            <div style="padding: 10px 14px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 13px; color: var(--fb-text-secondary);">জনপ্রিয় ও অফিসিয়াল পেজসমূহ</span>
                <button class="post-btn" style="background: #f35369; color: white; padding: 4px 10px; font-size: 12px; border-radius: 6px;" onclick="openCreatePageModal()">+ নতুন পেজ</button>
            </div>
            <div id="pagesListContainer" style="flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 10px;">
                <!-- Dynamically loaded -->
            </div>
        </div>
    </div>

    <!-- MEMORIES MODAL -->
    <div class="modal-overlay" id="memoriesModal" style="display: none;">
        <div class="modal-box" style="max-width: 540px; height: 520px; display: flex; flex-direction: column;">
            <div class="modal-header">
                <span class="modal-title">⏰ স্মৃতিচারণ (Your Memories)</span>
                <button class="modal-close" onclick="closeMemoriesModal()">✕</button>
            </div>
            <div id="memoriesListContainer" style="flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 12px;">
                <!-- Dynamically loaded -->
            </div>
        </div>
    </div>

    <!-- SAVED POSTS MODAL -->
    <div class="modal-overlay" id="savedPostsModal" style="display: none;">
        <div class="modal-box" style="max-width: 540px; height: 520px; display: flex; flex-direction: column;">
            <div class="modal-header">
                <span class="modal-title">🔖 সংরক্ষিত পোস্টসমূহ (Saved Items)</span>
                <button class="modal-close" onclick="closeSavedPostsModal()">✕</button>
            </div>
            <div id="savedPostsContainer" style="flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 10px;">
                <!-- Dynamically loaded -->
            </div>
        </div>
    </div>

    <!-- EVENTS MODAL -->
    <div class="modal-overlay" id="eventsModal" style="display: none;">
        <div class="modal-box" style="max-width: 520px; height: 500px; display: flex; flex-direction: column;">
            <div class="modal-header">
                <span class="modal-title">📅 অনুষ্ঠান ও মিটআপ (Events)</span>
                <button class="modal-close" onclick="closeEventsModal()">✕</button>
            </div>
            <div id="eventsListContainer" style="flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 12px;">
                <!-- Dynamically loaded events -->
            </div>
        </div>
    </div>

    <!-- MESSENGER POPUP MODAL -->
    <div class="modal-overlay" id="messengerModal" style="display: none;">
        <div class="modal-box" style="max-width: 480px; height: 520px; display: flex; flex-direction: column;">
            <div class="modal-header">
                <span class="modal-title">💬 মেসেঞ্জার ও সক্রিয় চ্যাট</span>
                <button class="modal-close" onclick="closeMessengerModal()">✕</button>
            </div>
            <div id="messengerModalList" style="flex: 1; overflow-y: auto; padding: 12px;">
                <!-- Dynamically loaded conversations -->
            </div>
        </div>
    </div>

    <!-- --------------------------------------------------------- -->
    <!-- 5. LOGIN OVERLAY (FACEBOOK WHITE & BLUE STYLE) -->
    <!-- --------------------------------------------------------- -->
    <div class="modal-overlay" id="authOverlay" style="display: none; background: #f0f2f5;">
        <div style="max-width: 980px; width: 100%; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 40px; padding: 20px;">
            <!-- Left Branding -->
            <div style="flex: 1; min-width: 320px;">
                <div style="margin-bottom: 16px;">
                    <img src="/images/bondhoo-logo.png" alt="Bondhoo" style="height: 54px; max-width: 240px; object-fit: contain;">
                </div>
                <p style="font-size: 24px; color: #1c1e21; line-height: 1.35; font-weight: 500;">
                    Bondhoo আপনাকে আপনার জীবনের মানুষের সাথে সংযুক্ত হতে এবং শেয়ার করতে সাহায্য করে।
                </p>
            </div>

            <!-- Right Login Card -->
            <div class="fb-card" style="width: 396px; padding: 24px; display: flex; flex-direction: column; gap: 14px; box-shadow: var(--shadow-lg);">
                <form onsubmit="handleManualLogin(event)" style="display: flex; flex-direction: column; gap: 12px;">
                    <input type="text" id="loginIdentifier" class="search-input" style="height: 48px; border: 1px solid var(--fb-border); border-radius: 8px; font-size: 16px; padding: 0 14px;" placeholder="ইমেইল, ইউজারনেম বা মোবাইল নম্বর" value="" required>
                    <input type="password" id="loginPassword" class="search-input" style="height: 48px; border: 1px solid var(--fb-border); border-radius: 8px; font-size: 16px; padding: 0 14px;" placeholder="পাসওয়ার্ড" value="" required>
                    <button type="submit" class="modal-btn-submit" style="background: #1877f2; height: 46px; font-size: 17px; font-weight: 700;">
                        লগইন করুন
                    </button>
                </form>

                <a href="/forgot-password" style="text-align: center; font-size: 14px; color: var(--fb-primary); margin-top: 4px; text-decoration: none; font-weight: 500;">
                    পাসওয়ার্ড ভুলে গেছেন?
                </a>

                <div style="height: 1px; background: var(--fb-border); margin: 4px 0;"></div>

                <a href="/register" class="modal-btn-submit" style="background: #42b72a; height: 46px; font-size: 16px; font-weight: 700; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #ffffff;">
                    নতুন অ্যাকাউন্ট তৈরি করুন
                </a>
            </div>
        </div>
    </div>

    <!-- Floating Toast Notification -->
    <div class="toast" id="toastBox">
        <span id="toastMsg">অপারেশন সফল হয়েছে!</span>
    </div>

    <script>
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return '';
        }

        // Global centralized Profile URL and identity resolver
        window.getUserProfileUrl = function(user) {
            if (!user) return '/profile';
            if (typeof user === 'string') {
                const clean = user.replace(/^@/, '').trim();
                return clean ? `/u/${encodeURIComponent(clean)}` : '/profile';
            }
            const username = user.username || user.user_name || user.handle || '';
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

        @php
            $sessionUser = Auth::guard('web')->user();
            $serverToken = $sessionUser ? $sessionUser->createToken('bondhoo_web')->plainTextToken : null;
        @endphp
        const __injectedServerToken = @json($serverToken);
        const __injectedServerUser = @json($sessionUser);

        let currentToken = __injectedServerToken || localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || getCookie('bondhoo_token') || getCookie('jugajug_token') || '';
        let currentUser = __injectedServerUser || JSON.parse(localStorage.getItem('bondhoo_user') || localStorage.getItem('jugajug_user') || 'null');
        window.currentToken = currentToken;
        window.currentUser = currentUser;

        /**
         * Harden every same-origin API request: always attach CSRF + session credentials,
         * attach valid Bearer token, and drop empty "Bearer " headers.
         */
        (function () {
            if (window.__jjFetchHardened) return;
            window.__jjFetchHardened = true;
            const nativeFetch = window.fetch.bind(window);
            window.fetch = function (input, init = {}) {
                const url = typeof input === 'string' ? input : (input?.url || '');
                if (url.startsWith('/api/') || url.startsWith(window.location.origin + '/api/')) {
                    init = Object.assign({ credentials: 'same-origin' }, init);
                    const headers = new Headers(init.headers || {});
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                    if (csrf && !headers.has('X-CSRF-TOKEN')) headers.set('X-CSRF-TOKEN', csrf);
                    if (!headers.has('X-Requested-With')) headers.set('X-Requested-With', 'XMLHttpRequest');
                    const auth = (headers.get('Authorization') || '').trim();
                    if (!auth || /^Bearer\s*(null|undefined)?$/i.test(auth)) {
                        const token = window.currentToken || currentToken || localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '';
                        if (token) {
                            headers.set('Authorization', `Bearer ${token}`);
                        } else {
                            headers.delete('Authorization');
                        }
                    }
                    init.headers = headers;
                }
                return nativeFetch(input, init);
            };
        })();

        if (currentToken) {
            try {
                localStorage.setItem('jugajug_token', currentToken);
                document.cookie = `jugajug_token=${currentToken}; path=/; max-age=2592000; SameSite=Lax`;
            } catch(e) {}
        }
        if (currentUser) {
            try {
                localStorage.setItem('jugajug_user', JSON.stringify(currentUser));
            } catch(e) {}
        }

        async function syncUserAuth() {
            if (currentToken) {
                try {
                    const res = await fetch('/api/v1/auth/me', {
                        headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success && data.data) {
                        currentUser = data.data;
                        localStorage.setItem('jugajug_user', JSON.stringify(currentUser));
                    }
                } catch (e) {
                    console.warn('Auth sync error:', e);
                }
            }
        }

        function showToast(msg) {
            const toast = document.getElementById('toastBox');
            document.getElementById('toastMsg').innerText = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        async function checkAuth() {
            const overlay = document.getElementById('authOverlay');
            if (!currentToken || !currentUser) {
                await syncUserAuth();
            }
            if (!currentToken || !currentUser) {
                window.location.replace('/login');
                return;
            } else {
                if (overlay) overlay.style.display = 'none';
                try {
                    updateUserUI();
                } catch (e) {
                    console.error('updateUserUI error:', e);
                }
                fetchFeedPosts();
                fetchConversations();
                fetchUnreadCounters();
            }
        }

        function updateUserUI() {
            if (!currentUser) return;
            const name = currentUser.name || (currentUser.username ? '@' + currentUser.username : 'ব্যবহারকারী');
            const initial = name.charAt(0);
            const avatarUrl = currentUser.profile?.avatar_url || currentUser.avatar_url || '/images/default-avatar.svg';
            const avatarImgHtml = `<img src="${avatarUrl}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">`;

            ['navUserAvatar', 'sidebarUserAvatar', 'createPostAvatar', 'modalUserAvatar', 'dropdownUserAvatar', 'jjComposerAvatar'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = avatarImgHtml;
            });
            const storyInit = document.getElementById('storyUserInitial');
            if (storyInit) storyInit.innerText = initial;

            const sidebarName = document.getElementById('sidebarUserName');
            if (sidebarName) sidebarName.innerText = name;
            const dropdownName = document.getElementById('dropdownUserName');
            if (dropdownName) dropdownName.innerText = name;
            const modalName = document.getElementById('modalUserName');
            if (modalName) modalName.innerText = name;
            const jjAuthor = document.getElementById('jjAuthorName');
            if (jjAuthor) jjAuthor.innerText = name;
            const placeholder = document.getElementById('createPostPlaceholder');
            if (placeholder) placeholder.innerText = `আপনার মনে কী আছে, ${name}?`;
        }

        async function handleManualLogin(e) {
            e.preventDefault();
            const id = document.getElementById('loginIdentifier').value;
            const pass = document.getElementById('loginPassword').value;
            await doLogin(id, pass);
        }

        let current2faChallengeToken = null;

        async function doLogin(identifier, password) {
            try {
                const res = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ identifier, password })
                });
                const data = await res.json();
                if (data.success && data.data) {
                    if (data.data.requires_2fa) {
                        current2faChallengeToken = data.data.challenge_token;
                        document.getElementById('twoFactorModal').style.display = 'flex';
                        document.getElementById('twoFactorCodeInput').focus();
                        return;
                    }

                    currentToken = data.data.token;
                    currentUser = data.data.user;
                    localStorage.setItem('jugajug_token', currentToken);
                    localStorage.setItem('jugajug_user', JSON.stringify(currentUser));
                    document.cookie = `jugajug_token=${currentToken}; path=/; max-age=2592000; SameSite=Lax`;
                    showToast(`স্বাগতম, ${currentUser.name}!`);
                    checkAuth();
                } else {
                    alert(data.message || 'লগইন ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                alert('কানেকশন এরর: ' + err.message);
            }
        }

        async function submitTwoFactorChallenge() {
            const code = document.getElementById('twoFactorCodeInput').value.trim();
            if (!code || !current2faChallengeToken) return;

            try {
                const res = await fetch('/api/v1/auth/2fa/challenge', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ challenge_token: current2faChallengeToken, code })
                });
                const data = await res.json();
                if (data.success && data.data && data.data.token) {
                    document.getElementById('twoFactorModal').style.display = 'none';
                    currentToken = data.data.token;
                    currentUser = data.data.user;
                    localStorage.setItem('jugajug_token', currentToken);
                    localStorage.setItem('jugajug_user', JSON.stringify(currentUser));
                    showToast(`2FA যাচাই সফল! স্বাগতম, ${currentUser.name}!`);
                    checkAuth();
                } else {
                    alert(data.message || 'ভুল ২FA কোড। আবার চেষ্টা করুন।');
                }
            } catch (err) {
                alert('ত্রুটি: ' + err.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* REGISTRATION & PASSWORD RESET */
        /* ------------------------------------------------------------- */
        function openRegisterModal() {
            window.location.href = '/register';
        }
        function closeRegisterModal() {
            const m = document.getElementById('registerModal');
            if (m) m.style.display = 'none';
        }
        async function handleRegistration(e) {
            e.preventDefault();
            const name = document.getElementById('regName').value.trim();
            const username = document.getElementById('regUsername').value.trim();
            const email = document.getElementById('regEmail').value.trim();
            const password = document.getElementById('regPassword').value;

            try {
                const res = await fetch('/api/v1/auth/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ name, username, email, password, password_confirmation: password })
                });
                const data = await res.json();
                if (data.success && data.data && data.data.token) {
                    closeRegisterModal();
                    currentToken = data.data.token;
                    currentUser = data.data.user;
                    localStorage.setItem('jugajug_token', currentToken);
                    localStorage.setItem('jugajug_user', JSON.stringify(currentUser));
                    showToast('অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে! স্বাগতম। 🎉');
                    checkAuth();
                } else {
                    alert(data.message || 'রেজিস্ট্রেশন ব্যর্থ হয়েছে। তথ্য সঠিকভাবে দিন।');
                }
            } catch (err) {
                alert('ত্রুটি: ' + err.message);
            }
        }

        function openForgotPasswordModal() {
            window.location.href = '/forgot-password';
        }
        function closeForgotPasswordModal() {
            const m = document.getElementById('forgotPasswordModal');
            if (m) m.style.display = 'none';
        }
        async function handleForgotPassword(e) {
            e.preventDefault();
            const email = document.getElementById('forgotEmail').value.trim();
            try {
                const res = await fetch('/api/v1/auth/forgot-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('রিসেট টোকেন তৈরি হয়েছে!');
                    if (data.data && data.data.reset_token) {
                        document.getElementById('resetTokenInput').value = data.data.reset_token;
                    }
                    document.getElementById('resetPasswordBox').style.display = 'block';
                } else {
                    alert(data.message || 'অনুরোধ ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                alert('ত্রুটি: ' + err.message);
            }
        }
        async function submitNewPassword() {
            const email = document.getElementById('forgotEmail').value.trim();
            const token = document.getElementById('resetTokenInput').value.trim();
            const password = document.getElementById('resetNewPass').value;
            if (!token || !password) return;

            try {
                const res = await fetch('/api/v1/auth/reset-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email, token, password, password_confirmation: password })
                });
                const data = await res.json();
                if (data.success) {
                    closeForgotPasswordModal();
                    showToast('পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে! নতুন পাসওয়ার্ড দিয়ে লগইন করুন।');
                } else {
                    alert(data.message || 'পাসওয়ার্ড পরিবর্তন ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                alert('ত্রুটি: ' + err.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* USER PROFILE & LOGOUT */
        /* ------------------------------------------------------------- */
        function goToMyProfile(tab = '') {
            const token = currentToken || localStorage.getItem('jugajug_token');
            if (token) {
                document.cookie = `jugajug_token=${token}; path=/; max-age=2592000; SameSite=Lax`;
            }
            const user = currentUser || JSON.parse(localStorage.getItem('jugajug_user') || '{}');
            let url = window.getUserProfileUrl ? window.getUserProfileUrl(user) : `/u/${encodeURIComponent(user.username || 'me')}`;
            if (tab) {
                url += `#${tab}`;
            }
            window.location.href = url;
        }

        async function openUserProfileModal() {
            if (!currentUser) return;
            const uid = currentUser.id || '{{ auth()->id() }}';
            const name = currentUser.name || 'ইউজার';
            const username = currentUser.username || 'user';

            const nameEl = document.getElementById('profileModalName');
            if (nameEl) nameEl.innerText = name;

            const uEl = document.getElementById('profileModalUsername');
            if (uEl) uEl.innerText = '@' + username;

            const idEl = document.getElementById('profileModalId');
            if (idEl) idEl.innerText = uid;

            const badgeEl = document.getElementById('profileModalVerifiedBadge');
            if (badgeEl) {
                badgeEl.style.display = (currentUser.is_verified) ? 'inline-flex' : 'none';
            }

            const avEl = document.getElementById('profileModalAvatar');
            if (avEl) {
                const avatarSrc = currentUser.avatar_url || currentUser.avatar || '';
                if (avatarSrc && !avatarSrc.includes('default-avatar')) {
                    avEl.innerHTML = `<img src="${avatarSrc}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="${name}">`;
                } else {
                    avEl.innerHTML = `<span style="text-transform:uppercase;">${name.charAt(0)}</span>`;
                }
            }

            document.getElementById('userProfileModal').style.display = 'flex';

            // Real counts from backend APIs
            try {
                const resF = await fetch('/api/v1/friends', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const dataF = await resF.json();
                const fCountEl = document.getElementById('profileModalFriendsCount');
                if (fCountEl) {
                    fCountEl.innerText = dataF.meta?.total ?? (Array.isArray(dataF.data) ? dataF.data.length : 0);
                }
            } catch (e) {}

            try {
                const resFo = await fetch('/api/v1/followers', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const dataFo = await resFo.json();
                const foCountEl = document.getElementById('profileModalFollowersCount');
                if (foCountEl) {
                    foCountEl.innerText = dataFo.meta?.total ?? (Array.isArray(dataFo.data) ? dataFo.data.length : 0);
                }
            } catch (e) {}

            try {
                const resFw = await fetch('/api/v1/following', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const dataFw = await resFw.json();
                const fwCountEl = document.getElementById('profileModalFollowingCount');
                if (fwCountEl) {
                    fwCountEl.innerText = dataFw.meta?.total ?? (Array.isArray(dataFw.data) ? dataFw.data.length : 0);
                }
            } catch (e) {}
        }

        function closeUserProfileModal() {
            document.getElementById('userProfileModal').style.display = 'none';
        }

        function copyProfileModalId() {
            const id = document.getElementById('profileModalId')?.innerText;
            if (id) {
                navigator.clipboard.writeText(id).then(() => {
                    if (typeof showToast === 'function') {
                        showToast(`প্রোফাইল আইডি #${id} কপি হয়েছে`);
                    } else {
                        alert(`প্রোফাইল আইডি #${id} কপি হয়েছে`);
                    }
                }).catch(() => {});
            }
        }

        function exportProfileDataFromDrawer() {
            const token = currentToken || localStorage.getItem('jugajug_token');
            if (token) {
                document.cookie = `jugajug_token=${token}; path=/; max-age=2592000; SameSite=Lax`;
            }
            window.location.href = '/api/v1/profile/export?download=1';
        }

        async function logoutAllOtherDevices() {
            if (!confirm('আপনি কি নিশ্চিত যে বর্তমান ডিভাইস ছাড়া অন্য সকল ডিভাইস থেকে লগআউট করতে চান?')) return;
            const token = currentToken || localStorage.getItem('jugajug_token');
            try {
                const res = await fetch('/devices/logout-all', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const data = await res.json();
                alert(data.message || 'অন্যান্য সকল ডিভাইস থেকে সফলভাবে লগআউট সম্পন্ন হয়েছে।');
            } catch (err) {
                alert('অন্যান্য ডিভাইস লগআউট সম্পন্ন হয়েছে।');
            }
        }

        async function logout() {
            if (!confirm('আপনি কি নিশ্চিত যে লগআউট করতে চান?')) {
                return;
            }

            const tokenToRevoke = currentToken || localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // 1. Realtime/WebSocket disconnect
            if (window.Echo) {
                try {
                    window.Echo.disconnect();
                } catch (e) {}
            }

            // 2. Server-side session & token revocation across guards
            try {
                await Promise.allSettled([
                    fetch('/logout', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            ...(tokenToRevoke ? { 'Authorization': `Bearer ${tokenToRevoke}` } : {})
                        }
                    }),
                    tokenToRevoke ? fetch('/api/v1/auth/logout', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Authorization': `Bearer ${tokenToRevoke}`
                        }
                    }) : Promise.resolve()
                ]);
            } catch (e) {
                console.warn('Logout network notification:', e);
            }

            // 3. Multi-tab logout synchronization via StorageEvent
            try {
                localStorage.setItem('bondhoo_logout_event', Date.now().toString());
            } catch (e) {}

            // 4. In-memory authentication state reset
            currentToken = '';
            currentUser = null;
            window.currentToken = '';
            window.currentUser = null;

            // 5. Clear client-side storage keys
            const authStorageKeys = [
                'bondhoo_token', 'jugajug_token', 'bondhoo_user', 'jugajug_user',
                'bondhoo_admin', 'jugajug_admin', 'admin_token', 'bondhoo_device_token',
                '2fa_challenge_token', '2fa_destination', '2fa_method', '2fa_guard'
            ];
            authStorageKeys.forEach(k => {
                try { localStorage.removeItem(k); } catch (e) {}
                try { sessionStorage.removeItem(k); } catch (e) {}
            });

            // 6. Expire all client-side authentication cookies
            const cookiesToClear = [
                'bondhoo_token', 'jugajug_token', 'bondhoo_admin', 'jugajug_admin',
                'admin_token', 'laravel_session', 'XSRF-TOKEN', 'remember_web'
            ];
            cookiesToClear.forEach(name => {
                document.cookie = `${name}=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; max-age=0; SameSite=Lax`;
            });

            closeUserProfileModal();

            // 7. Redirect to login page and replace history entry (prevent back button restore)
            window.location.replace('/login');
        }

        // Multi-tab logout synchronization listener
        window.addEventListener('storage', function(e) {
            if (e.key === 'bondhoo_logout_event') {
                window.location.replace('/login');
            }
        });

        /* ------------------------------------------------------------- */
        /* GLOBAL FEED REFRESH & DATA SYNC (NO FULL PAGE RELOAD) */
        /* ------------------------------------------------------------- */
        let isFeedRefreshing = false;
        async function triggerFeedRefresh(btn) {
            if (isFeedRefreshing) return;
            isFeedRefreshing = true;
            if (btn) {
                btn.disabled = true;
                const svg = btn.querySelector('svg');
                if (svg) svg.classList.add('spinning');
            }

            try {
                await Promise.all([
                    fetchFeedPosts(),
                    fetchStories(),
                    fetchConversations(),
                    fetchUnreadCounters()
                ]);
                showToast('ফিড ও ডাটা সফলভাবে রিফ্রেশ হয়েছে! ✨');
            } catch (err) {
                console.warn('Feed refresh error:', err);
                showToast('রিফ্রেশ সম্পন্ন হয়েছে।');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    const svg = btn.querySelector('svg');
                    if (svg) svg.classList.remove('spinning');
                }
                isFeedRefreshing = false;
            }
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

        function normalizeMediaUrl(url) {
            if (!url || typeof url !== 'string') return '';
            const match = url.match(/^(?:https?:\/\/[^\/]+)?(\/storage\/.*)$/i);
            return match ? match[1] : url;
        }

        function formatDuration(sec) {
            sec = Math.round(sec) || 0;
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        }

        function formatTimeAgo(dateStr) {
            if (!dateStr) return 'এইমাত্র';
            const diff = Math.floor((new Date() - new Date(dateStr)) / 1000);
            if (diff < 60) return 'এইমাত্র';
            if (diff < 3600) return `${Math.floor(diff/60)} মিনিট আগে`;
            if (diff < 86400) return `${Math.floor(diff/3600)} ঘণ্টা আগে`;
            return `${Math.floor(diff/86400)} দিন আগে`;
        }

        /* ------------------------------------------------------------- */
        /* FEED & POST FUNCTIONS (ENTERPRISE REAL APIS) */
        /* ------------------------------------------------------------- */
        let cachedPosts = [];

        async function fetchFeedPosts() {
            const container = document.getElementById('feedPostsStream');
            try {
                const headers = { 'Accept': 'application/json' };
                if (currentToken) {
                    headers['Authorization'] = `Bearer ${currentToken}`;
                }

                // Fetch active live streams for feed distribution
                let liveCardsHtml = '';
                try {
                    const liveRes = await fetch('/api/v2/live/streams?status=live', { headers });
                    const liveJson = await liveRes.json();
                    const liveStreams = Array.isArray(liveJson.data) ? liveJson.data : [];
                    if (liveStreams.length > 0) {
                        liveCardsHtml = liveStreams.map(ls => `
                            <div class="fb-card" style="border: 2px solid #ef4444; border-radius: 12px; overflow: hidden; margin-bottom: 8px;">
                                <div style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 10px 16px; display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 800; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: white; animation: pulse 1s infinite;"></span>
                                        🔴 লাইভ চলছে (LIVE NOW)
                                    </span>
                                    <span style="font-size: 12px; background: rgba(0,0,0,0.3); padding: 3px 10px; border-radius: 12px; font-weight: 700;">
                                        👁️ ${ls.viewers_count || 0} জন দেখছেন
                                    </span>
                                </div>
                                <div style="padding: 16px;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                                        <img src="${ls.user?.profile?.avatar_url || '/images/default-avatar.svg'}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #ef4444;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                        <div>
                                            <div style="font-weight: 800; font-size: 15px;">${escapeHtml(ls.user?.name || 'Bondhoo ক্রিয়েটর')}</div>
                                            <div style="font-size: 12px; color: var(--fb-text-secondary);">${escapeHtml(ls.title)}</div>
                                        </div>
                                    </div>
                                    <div style="background: #000; height: 180px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; position: relative; overflow: hidden;">
                                        <div style="position: absolute; top: 10px; left: 10px; background: rgba(239,68,68,0.9); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 800;">
                                            LIVE
                                        </div>
                                        <div style="text-align: center;">
                                            <div style="font-size: 36px; margin-bottom: 6px;">📡</div>
                                            <div style="font-weight: 700; font-size: 15px;">${escapeHtml(ls.title)}</div>
                                        </div>
                                    </div>
                                    <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                                        <a href="/live/${ls.id}" class="post-btn" style="background: #ef4444; color: white; text-decoration: none; padding: 8px 20px; border-radius: 8px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                                            ▶️ ওয়াচ লাইভ (Watch Live)
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    }
                } catch (e) {
                    console.warn('Live fetch error:', e);
                }

                const res = await fetch('/api/v1/feed', { headers });
                const json = await res.json();
                const posts = Array.isArray(json.data) ? json.data : (json.data && json.data.posts ? json.data.posts : []);
                cachedPosts = posts;

                if (posts.length === 0 && !liveCardsHtml) {
                    container.innerHTML = `
                        <div class="fb-card" style="padding: 32px 20px; text-align: center;">
                            <div style="font-size: 36px; margin-bottom: 8px;">✨</div>
                            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">এখনো কোনো নতুন পোস্ট নেই</h3>
                            <p style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 14px;">আপনার মনে কী আছে? বন্ধুদের সাথে শেয়ার করতে প্রথম পোস্টটি পাবলিশ করুন।</p>
                            <button class="post-btn" style="background: var(--fb-primary); color: white; padding: 8px 20px; border-radius: 20px; font-weight: 700;" onclick="openCreatePostModal()">পোস্ট তৈরি করুন</button>
                        </div>`;
                    return;
                }

                container.innerHTML = liveCardsHtml + posts.map(p => renderFeedPostCard(p)).join('');
            } catch (err) {
                console.error('Feed error:', err);
                container.innerHTML = `<div class="fb-card" style="padding: 16px; color: #ef4444; text-align: center;">ফিড লোড হতে সমস্যা হয়েছে। অনুগ্রহ করে রিফ্রেশ করুন।</div>`;
            }
        }

        function renderFeedPostCard(p) {
            const authorObj = p.author || p.user || {};
            const author = authorObj.name || 'Bondhoo মেম্বার';
            const username = authorObj.username || 'user';
            const authorProfileUrl = window.getUserProfileUrl ? window.getUserProfileUrl(authorObj) : `/u/${encodeURIComponent(username)}`;
            const postAvatarUrl = normalizeMediaUrl(authorObj.avatar_url || authorObj.profile?.avatar_url || '') || '/images/default-avatar.svg';
            const isVerified = authorObj.is_verified || p.user?.is_verified || false;
            const timeAgo = formatTimeAgo(p.created_at);
            const audienceIcon = p.audience === 'friends' ? 
                `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>` : 
                `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>`;
            const isMyPost = currentUser && (p.user_id === currentUser.id || authorObj.id === currentUser.id);
            const postContent = p.content || '';

            // Badges & Meta
            const pinnedBadge = p.is_pinned ? `<div style="display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--fb-primary); font-weight: 700; margin-bottom: 8px;">📌 পিন করা পোস্ট</div>` : '';
            const aiBadge = p.is_ai_generated ? `<span style="display:inline-flex; align-items:center; gap:3px; background:rgba(37,99,235,0.08); color:#2563eb; font-size:11px; font-weight:700; padding:2px 8px; border-radius:12px; border:1px solid rgba(37,99,235,0.2); margin-left:4px;">✨ এআই জেনারেটেড</span>` : '';

            // Feeling / Activity
            let feelingHtml = '';
            if (p.feeling_activity) {
                const f = p.feeling_activity;
                const fText = typeof f === 'object' ? `${f.icon || '✨'} ${f.text || ''}` : f;
                if (fText && fText.trim()) feelingHtml = `<span style="font-weight: 500; font-size: 13px; color: var(--fb-text-secondary);"> — ${escapeHtml(fText)}</span>`;
            }

            // Location
            let locationHtml = '';
            if (p.location) {
                const locText = typeof p.location === 'object' ? (p.location.name || p.location.address || '') : p.location;
                if (locText) locationHtml = `<span style="font-weight: 500; font-size: 12px; color: var(--fb-text-secondary);"> 📍 ${escapeHtml(locText)}</span>`;
            }

            // Collaborator & Tagged friends
            let collabHtml = '';
            if (p.collaborator && p.collaborator.name) {
                const collabUrl = window.getUserProfileUrl ? window.getUserProfileUrl(p.collaborator) : `/u/${encodeURIComponent(p.collaborator.username || '')}`;
                collabHtml = `<span style="font-size: 12px; color: var(--fb-text-secondary);"> এবং <a href="${collabUrl}" style="color:var(--fb-primary);font-weight:700;text-decoration:none;">${escapeHtml(p.collaborator.name)}</a> (সহযোগী)</span>`;
            }
            let taggedHtml = '';
            if (Array.isArray(p.tagged_users) && p.tagged_users.length > 0) {
                const names = p.tagged_users.map(u => {
                    const tagUrl = window.getUserProfileUrl ? window.getUserProfileUrl(u) : `/u/${encodeURIComponent(u.username || '')}`;
                    return `<a href="${tagUrl}" style="color:var(--fb-primary);font-weight:600;text-decoration:none;">${escapeHtml(u.name || '')}</a>`;
                }).join(', ');
                taggedHtml = `<span style="font-size: 12px; color: var(--fb-text-secondary);"> সাথে ${names}</span>`;
            }

            // Content warning
            let warningHtml = '';
            if (p.content_warning) {
                warningHtml = `
                    <div id="content-warning-${p.id}" style="background: #fef2f2; border: 1px dashed #f87171; border-radius: 8px; padding: 10px 14px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <div style="font-size: 12px; color: #b91c1c; font-weight: 600;">⚠️ সতর্কতা: ${escapeHtml(p.content_warning)}</div>
                        <button type="button" class="post-btn" style="padding: 4px 10px; font-size: 12px; background: white; border: 1px solid #f87171; color: #b91c1c; border-radius: 6px;" onclick="document.getElementById('content-warning-${p.id}').style.display='none'; const b = document.getElementById('post-body-text-${p.id}'); if(b) b.style.filter='none';">কন্টেন্ট দেখুন</button>
                    </div>
                `;
            }

            // Attached Media (images / videos)
            let mediaHtml = '';
            const mediaList = Array.isArray(p.media) ? p.media : [];
            if (mediaList.length > 0) {
                if (mediaList.length === 1) {
                    const m = mediaList[0];
                    const mUrl = normalizeMediaUrl(m.url);
                    if (m.type === 'video' || (mUrl && mUrl.match(/\.(mp4|webm|mov)$/i))) {
                        mediaHtml = `
                            <div style="margin-top: 12px; border-radius: 8px; overflow: hidden; background: #000;">
                                <video src="${mUrl}" controls playsinline style="width: 100%; max-height: 480px; display: block;"></video>
                            </div>`;
                    } else {
                        mediaHtml = `
                            <div style="margin-top: 12px; border-radius: 8px; overflow: hidden; cursor: pointer;" onclick="openImageLightbox('${mUrl}')">
                                <img src="${mUrl}" style="width: 100%; max-height: 500px; object-fit: cover; display: block;" onerror="this.parentElement.style.display='none'">
                            </div>`;
                    }
                } else if (mediaList.length === 2) {
                    mediaHtml = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; margin-top: 12px; border-radius: 8px; overflow: hidden;">
                            ${mediaList.map(m => {
                                const mUrl = normalizeMediaUrl(m.url);
                                return `
                                <div style="height: 240px; overflow: hidden; cursor: pointer; background: #000;" onclick="openImageLightbox('${mUrl}')">
                                    <img src="${mUrl}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.parentElement.style.display='none'">
                                </div>`;
                            }).join('')}
                        </div>`;
                } else {
                    mediaHtml = `
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; margin-top: 12px; border-radius: 8px; overflow: hidden;">
                            ${mediaList.slice(0, 4).map((m, idx) => {
                                const mUrl = normalizeMediaUrl(m.url);
                                return `
                                <div style="height: 180px; position: relative; overflow: hidden; cursor: pointer; background: #000;" onclick="openImageLightbox('${mUrl}')">
                                    <img src="${mUrl}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.parentElement.style.display='none'">
                                    ${idx === 3 && mediaList.length > 4 ? `
                                        <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: 800;">
                                            +${mediaList.length - 4}
                                        </div>` : ''}
                                </div>`;
                            }).join('')}
                        </div>`;
                }
            } else if (postContent && (postContent.includes('http://') || postContent.includes('https://'))) {
                const urls = postContent.match(/(https?:\/\/[^\s]+)/g) || [];
                const imgUrl = urls.find(u => u.match(/\.(jpeg|jpg|gif|png|webp)/i));
                if (imgUrl) {
                    mediaHtml = `
                        <div style="margin-top: 12px; border-radius: 8px; overflow: hidden; cursor: pointer;" onclick="openImageLightbox('${imgUrl}')">
                            <img src="${imgUrl}" style="width: 100%; max-height: 440px; object-fit: cover; display: block;" onerror="this.parentElement.style.display='none'">
                        </div>`;
                }
            }

            // Link Preview
            let linkPreviewHtml = '';
            if (p.link_preview && p.link_preview.url && !mediaHtml) {
                linkPreviewHtml = `
                    <a href="${escapeHtml(p.link_preview.url)}" target="_blank" rel="noopener noreferrer" style="display: block; text-decoration: none; color: inherit; border: 1px solid var(--fb-border); border-radius: 8px; overflow: hidden; margin-top: 12px; background: rgba(0,0,0,0.02);">
                        ${p.link_preview.image ? `<img src="${escapeHtml(p.link_preview.image)}" style="width: 100%; height: 190px; object-fit: cover; display: block;" onerror="this.style.display='none'">` : ''}
                        <div style="padding: 10px 14px;">
                            <div style="font-size: 11px; text-transform: uppercase; color: var(--fb-text-secondary); font-weight: 700;">${escapeHtml(p.link_preview.domain || '')}</div>
                            <div style="font-size: 14px; font-weight: 700; margin: 3px 0;">${escapeHtml(p.link_preview.title || '')}</div>
                            <div style="font-size: 12px; color: var(--fb-text-secondary); line-height: 1.4;">${escapeHtml((p.link_preview.description || '').substring(0, 120))}</div>
                        </div>
                    </a>`;
            }

            // Voting Poll
            let pollHtml = '';
            if (p.poll_data && Array.isArray(p.poll_data.options) && p.poll_data.options.length > 0) {
                const totalVotes = p.poll_data.options.reduce((sum, opt) => sum + (parseInt(opt.votes_count) || 0), 0);
                const isExpired = p.poll_data.expires_at && new Date(p.poll_data.expires_at) < new Date();
                const userVoteMap = p.poll_data.voted_user_ids || {};
                const userVotedOptId = currentUser ? userVoteMap[currentUser.id] : null;

                pollHtml = `
                    <div style="margin-top: 14px; padding: 14px; border: 1px solid var(--fb-border); border-radius: 10px; background: var(--fb-hover);">
                        <div style="font-size: 14px; font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                            <span>🗳️ ${escapeHtml(p.poll_data.question || 'পোল')}</span>
                            <span style="font-size: 12px; color: var(--fb-text-secondary); font-weight: normal;">${totalVotes} টি ভোট ${isExpired ? '· সমাপ্ত' : ''}</span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            ${p.poll_data.options.map(opt => {
                                const vCount = parseInt(opt.votes_count) || 0;
                                const pct = totalVotes > 0 ? Math.round((vCount / totalVotes) * 100) : 0;
                                const isMyVote = userVotedOptId && Number(userVotedOptId) === Number(opt.id);
                                return `
                                    <div style="position: relative; border: 1px solid ${isMyVote ? 'var(--fb-primary)' : 'var(--fb-border)'}; border-radius: 8px; overflow: hidden; background: white; cursor: ${isExpired ? 'default' : 'pointer'};" onclick="${isExpired ? '' : `voteOnPoll(${p.id}, ${opt.id})`}">
                                        <div style="position: absolute; left: 0; top: 0; bottom: 0; width: ${pct}%; background: ${isMyVote ? 'rgba(24, 119, 242, 0.2)' : 'rgba(0, 0, 0, 0.06)'}; transition: width 0.3s ease;"></div>
                                        <div style="position: relative; z-index: 1; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-size: 13px; font-weight: ${isMyVote ? '700' : '500'}; color: ${isMyVote ? 'var(--fb-primary)' : 'inherit'};">
                                                ${isMyVote ? '✓ ' : ''}${escapeHtml(opt.text)}
                                            </span>
                                            <span style="font-size: 12px; font-weight: 700; color: var(--fb-text-secondary);">${pct}% (${vCount})</span>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>`;
            }

            // Canvas gradient background style
            const canvasGradients = {
                'gradient-sunset': 'background: linear-gradient(135deg, #f97316 0%, #ec4899 100%); color: #fff;',
                'gradient-aurora': 'background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 50%, #8b5cf6 100%); color: #fff;',
                'gradient-midnight': 'background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%); color: #fff;',
                'gradient-forest': 'background: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%); color: #fff;',
                'gradient-cyberpunk': 'background: linear-gradient(135deg, #7c3aed 0%, #d946ef 50%, #f43f5e 100%); color: #fff;',
                'gradient-berry': 'background: linear-gradient(135deg, #e11d48 0%, #be185d 100%); color: #fff;',
            };
            const bgGradientStyle = (!mediaHtml && !pollHtml && p.background_style && canvasGradients[p.background_style]) ? canvasGradients[p.background_style] : null;

            const userReactionType = p.viewer_reaction || p.user_reaction || p.user_reaction_type || (p.user_has_reacted ? 'like' : null);
            const likedClass = userReactionType ? 'liked' : '';
            const reactionsCount = p.reactions_count ?? p.counters?.likes ?? p.counters?.likes_count ?? 0;
            const commentsCount = p.comments_count ?? p.counters?.comments ?? p.counters?.comments_count ?? 0;
            const sharesCount = p.shares_count ?? p.counters?.shares ?? p.counters?.shares_count ?? 0;

            return `
                <div class="fb-card post-card" id="post-card-${p.id}">
                    ${pinnedBadge}
                    <!-- Post Header -->
                    <div class="post-header" style="position: relative;">
                        <div class="post-author-info">
                            <a href="${authorProfileUrl}" style="position: relative; text-decoration: none;">
                                <img src="${postAvatarUrl}" class="avatar" style="width:40px;height:40px;object-fit:cover;border-radius:50%;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                            </a>
                            <div>
                                <div style="display: flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                                    <a href="${authorProfileUrl}" class="post-author-name" style="text-decoration: none; color: inherit; font-weight: 700; font-size: 14px;">${escapeHtml(author)}</a>
                                    ${isVerified ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="#1877f2"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>` : ''}
                                    ${aiBadge}
                                    ${feelingHtml}
                                    ${locationHtml}
                                    ${collabHtml}
                                    ${taggedHtml}
                                </div>
                                <div class="post-meta" style="display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--fb-text-secondary); margin-top: 2px;">
                                    <span>${timeAgo}</span>
                                    <span>·</span>
                                    <span style="display: inline-flex; align-items: center;" title="${p.audience === 'friends' ? 'শুধুমাত্র বন্ধুরা' : 'পাবলিক'}">${audienceIcon}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Post More Actions Dropdown -->
                        <div style="position: relative;">
                            <button type="button" class="circle-btn" style="width: 32px; height: 32px; background: transparent;" onclick="togglePostMoreMenu(${p.id}, event)" title="আরও অপশন">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="1"></circle>
                                    <circle cx="19" cy="12" r="1"></circle>
                                    <circle cx="5" cy="12" r="1"></circle>
                                </svg>
                            </button>
                            <div class="jj-dropdown" id="post-more-menu-${p.id}" style="display: none;">
                                <div class="jj-dropdown-item" onclick="toggleSavePostAction(${p.id}, '${encodeURIComponent(postContent.substring(0, 100))}')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                                    <span>পোস্ট সংরক্ষণ করুন</span>
                                </div>
                                <div class="jj-dropdown-item" onclick="copyPostLink(${p.id})">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                                    <span>লিংক কপি করুন</span>
                                </div>
                                ${isMyPost ? `
                                    <div class="jj-dropdown-item" onclick="openEditPostModal(${p.id})">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        <span>পোস্ট সম্পাদনা করুন</span>
                                    </div>
                                    <div class="jj-dropdown-item" style="color: #ef4444;" onclick="deletePost(${p.id})">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        <span>পোস্ট মুছে ফেলুন</span>
                                    </div>
                                ` : ''}
                                <div class="jj-dropdown-item" onclick="openReportModal('post', ${p.id})">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                    <span>রিপোর্ট করুন</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Post Body (Double Click to Like) -->
                    <div class="post-body" ondblclick="handlePostDoubleClickLike(${p.id})">
                        ${warningHtml}
                        ${bgGradientStyle ? `
                            <div style="${bgGradientStyle} min-height: 200px; display: flex; align-items: center; justify-content: center; text-align: center; padding: 32px 20px; border-radius: 8px; font-size: 20px; font-weight: 800; line-height: 1.5; word-break: break-word; text-shadow: 0 2px 6px rgba(0,0,0,0.3);">
                                ${escapeHtml(postContent)}
                            </div>
                        ` : `
                            <div id="post-body-text-${p.id}" style="line-height: 1.5; white-space: pre-line; word-break: break-word; font-size: 14px; ${p.content_warning ? 'filter: blur(8px);' : ''}">${escapeHtml(postContent)}</div>
                            ${mediaHtml}
                            ${linkPreviewHtml}
                            ${pollHtml}
                        `}
                    </div>

                    <!-- Reactions and Counters Bar -->
                    <div class="reactions-bar" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; border-bottom: 1px solid var(--fb-border); font-size: 13px; color: var(--fb-text-secondary);">
                        <div class="reaction-icons" style="cursor: pointer; display: flex; align-items: center; gap: 4px;" onclick="openWhoReactedModal(${p.id})" title="কে কে রিঅ্যাক্ট করেছেন দেখুন">
                            <span class="reaction-bubble like-bubble">👍</span>
                            <span class="reaction-bubble love-bubble">❤️</span>
                            <span style="font-weight: 600; margin-left: 2px;" id="reactions-count-${p.id}">${reactionsCount}</span>
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <span style="cursor: pointer;" onclick="toggleCommentsDrawer(${p.id})" id="comments-count-${p.id}">${commentsCount} টি মন্তব্য</span>
                            <span style="cursor: pointer;" onclick="openShareModal(${p.id})" id="shares-count-${p.id}">${sharesCount} টি শেয়ার</span>
                        </div>
                    </div>

                    <!-- Post Action Buttons (Hover Reaction Popover Bar) -->
                    <div class="post-buttons-bar" style="display: flex; padding: 4px 10px; border-bottom: 1px solid var(--fb-border);">
                        <!-- Like Button with Popover -->
                        <div class="jj-like-btn-wrapper" style="flex: 1; position: relative;">
                            <button type="button" class="post-btn jj-post-btn-wide ${likedClass}" id="like-btn-${p.id}" onclick="toggleLike(this, ${p.id})">
                                <span class="like-icon-slot" id="like-btn-icon-${p.id}">
                                    ${userReactionType ? (userReactionType === 'love' ? '❤️' : (userReactionType === 'haha' ? '😆' : (userReactionType === 'wow' ? '😮' : (userReactionType === 'sad' ? '😢' : (userReactionType === 'angry' ? '😡' : '👍'))))) : `
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
                                        </svg>`}
                                </span>
                                <span style="font-weight: 700;" id="like-btn-label-${p.id}">${userReactionType ? (userReactionType === 'love' ? 'লাভ' : (userReactionType === 'haha' ? 'হাহা' : (userReactionType === 'wow' ? 'ওয়াও' : (userReactionType === 'sad' ? 'স্যাড' : (userReactionType === 'angry' ? 'এংগ্রি' : 'লাইকড'))))) : 'লাইক'}</span>
                            </button>

                            <!-- Modern Facebook-style Reaction Popover on Hover -->
                            <div class="jj-reaction-popover">
                                <span class="jj-reaction-pop-item" onclick="reactToPost(${p.id}, 'like', event)" title="লাইক">👍</span>
                                <span class="jj-reaction-pop-item" onclick="reactToPost(${p.id}, 'love', event)" title="লাভ">❤️</span>
                                <span class="jj-reaction-pop-item" onclick="reactToPost(${p.id}, 'haha', event)" title="হাহা">😆</span>
                                <span class="jj-reaction-pop-item" onclick="reactToPost(${p.id}, 'wow', event)" title="ওয়াও">😮</span>
                                <span class="jj-reaction-pop-item" onclick="reactToPost(${p.id}, 'sad', event)" title="স্যাড">😢</span>
                                <span class="jj-reaction-pop-item" onclick="reactToPost(${p.id}, 'angry', event)" title="এংগ্রি">😡</span>
                            </div>
                        </div>

                        <!-- Comment Button -->
                        <button type="button" class="post-btn jj-post-btn-wide" style="flex: 1;" onclick="toggleCommentsDrawer(${p.id})">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                            </svg>
                            <span style="font-weight: 700;">মন্তব্য</span>
                        </button>

                        <!-- Share Button -->
                        <button type="button" class="post-btn jj-post-btn-wide" style="flex: 1;" onclick="openShareModal(${p.id})">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="18" cy="5" r="3"></circle>
                                <circle cx="6" cy="12" r="3"></circle>
                                <circle cx="18" cy="19" r="3"></circle>
                                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                            </svg>
                            <span style="font-weight: 700;">শেয়ার</span>
                        </button>
                    </div>

                    <!-- Inline Comments Drawer Section -->
                    <div class="jj-inline-comments" id="inline-comments-${p.id}" style="display: none; padding: 12px 16px; background: #fafafa;">
                        <!-- Comments Stream -->
                        <div id="inline-comments-list-${p.id}" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;">
                            <div style="font-size: 13px; color: var(--fb-text-secondary); text-align: center; padding: 8px;">মন্তব্য লোড হচ্ছে...</div>
                        </div>

                        <!-- Active Reply Banner -->
                        <div id="inline-reply-banner-${p.id}" style="display: none; background: #e7f3ff; color: var(--fb-primary); padding: 4px 10px; border-radius: 6px; font-size: 12px; margin-bottom: 6px; justify-content: space-between; align-items: center;">
                            <span id="inline-reply-text-${p.id}">উত্তর দিচ্ছেন...</span>
                            <button type="button" onclick="cancelInlineReply(${p.id})" style="background:none; border:none; color: var(--fb-primary); font-weight: 800; cursor:pointer;">✕</button>
                        </div>

                        <!-- Comment Composer -->
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <div class="avatar" style="width: 34px; height: 34px; font-size: 13px; flex-shrink: 0;">${currentUser?.name?.charAt(0) || 'র'}</div>
                            <div style="flex: 1; display: flex; background: white; border: 1px solid var(--fb-border); border-radius: 20px; padding: 4px 12px; align-items: center; gap: 6px;">
                                <input type="text" id="inline-comment-input-${p.id}" placeholder="একটি মন্তব্য লিখুন... (Enter চাপুন)" style="flex: 1; border: none; outline: none; font-size: 13px; background: transparent;" onkeydown="if(event.key==='Enter') submitInlineComment(${p.id})">
                                <button type="button" onclick="submitInlineComment(${p.id})" style="background: none; border: none; color: var(--fb-primary); cursor: pointer; display: flex; align-items: center;" title="মন্তব্য পাঠান">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        async function voteOnPoll(postId, optionId) {
            if (!currentToken) {
                showToast('ভোট দিতে অনুগ্রহ করে লগইন করুন।');
                return;
            }
            try {
                const res = await fetch(`/api/v1/posts/${postId}/poll/vote`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ option_id: optionId })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('আপনার ভোট সফলভাবে রেকর্ড হয়েছে! 🗳️');
                    fetchFeedPosts();
                } else {
                    showToast(data.message || 'ভোট দেওয়া যায়নি।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        function togglePostMoreMenu(postId, event) {
            if (event) event.stopPropagation();
            const targetMenu = document.getElementById(`post-more-menu-${postId}`);
            const isCurrentlyOpen = targetMenu && (targetMenu.style.display === 'flex' || targetMenu.style.display === 'block' || targetMenu.classList.contains('active'));

            document.querySelectorAll('.jj-dropdown').forEach(m => {
                m.style.display = 'none';
                m.classList.remove('active');
            });

            if (targetMenu && !isCurrentlyOpen) {
                targetMenu.style.display = 'flex';
                targetMenu.classList.add('active');
            }
        }

        document.addEventListener('click', () => {
            document.querySelectorAll('.jj-dropdown').forEach(m => {
                m.style.display = 'none';
                m.classList.remove('active');
            });
        });

        /* ------------------------------------------------------------- */
        /* REALTIME LIKE & REACTION SYSTEM */
        /* ------------------------------------------------------------- */
        async function reactToPost(postId, type = 'like', event = null) {
            if (event) event.stopPropagation();
            if (!currentToken) {
                showToast('রিঅ্যাক্ট করতে অনুগ্রহ করে লগইন করুন।');
                return;
            }

            const counterEl = document.getElementById(`reactions-count-${postId}`);
            const btnEl = document.getElementById(`like-btn-${postId}`);
            const iconSlot = document.getElementById(`like-btn-icon-${postId}`);
            const labelSlot = document.getElementById(`like-btn-label-${postId}`);

            const prevLiked = btnEl ? btnEl.classList.contains('liked') : false;
            const prevCount = parseInt(counterEl ? counterEl.innerText : '0') || 0;

            // Optimistic UI update
            if (btnEl) btnEl.classList.add('liked');
            if (iconSlot) iconSlot.innerText = type === 'love' ? '❤️' : (type === 'haha' ? '😆' : (type === 'wow' ? '😮' : (type === 'sad' ? '😢' : (type === 'angry' ? '😡' : '👍'))));
            if (labelSlot) labelSlot.innerText = type === 'love' ? 'লাভ' : 'লাইকড';
            if (counterEl && !prevLiked) counterEl.innerText = prevCount + 1;

            try {
                const res = await fetch(`/api/v1/posts/${postId}/react`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ type })
                });
                const data = await res.json();
                if (data.success) {
                    const total = data.data?.total_reactions ?? data.data?.reactions_count;
                    if (typeof total !== 'undefined' && counterEl) {
                        counterEl.innerText = total;
                    }
                    if (data.data?.reacted === false) {
                        // Reaction was toggled off
                        if (btnEl) btnEl.classList.remove('liked');
                        if (iconSlot) iconSlot.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>`;
                        if (labelSlot) labelSlot.innerText = 'লাইক';
                    }
                } else {
                    // Rollback on failure
                    if (btnEl && !prevLiked) btnEl.classList.remove('liked');
                    if (counterEl) counterEl.innerText = prevCount;
                    showToast(data.message || 'রিঅ্যাকশন আপডেট করা যায়নি।');
                }
            } catch (err) {
                // Rollback
                if (btnEl && !prevLiked) btnEl.classList.remove('liked');
                if (counterEl) counterEl.innerText = prevCount;
                showToast('কানেকশন ত্রুটি: ' + err.message);
            }
        }

        async function toggleLike(btn, postId) {
            const isLiked = btn.classList.contains('liked');
            await reactToPost(postId, 'like');
        }

        function handlePostDoubleClickLike(postId) {
            reactToPost(postId, 'love');
            showToast('❤️ পোস্টে লাভ রিঅ্যাকশন দেওয়া হয়েছে!');
        }

        /* ------------------------------------------------------------- */
        /* WHO REACTED MODAL (REAL BREAKDOWN & USER LIST) */
        /* ------------------------------------------------------------- */
        let currentPostReactionsData = null;

        async function openWhoReactedModal(postId) {
            const modal = document.getElementById('whoReactedModal');
            const list = document.getElementById('whoReactedList');
            modal.style.display = 'flex';
            list.innerHTML = '<div style="padding: 24px; text-align: center; color: var(--fb-text-secondary);">রিঅ্যাকশন লোড হচ্ছে...</div>';

            try {
                const res = await fetch(`/api/v1/posts/${postId}/reactions`, {
                    headers: { 'Accept': 'application/json', ...(currentToken ? { 'Authorization': `Bearer ${currentToken}` } : {}) }
                });
                const json = await res.json();
                currentPostReactionsData = json.data || {};

                // Update tab counters
                const total = currentPostReactionsData.total || 0;
                const breakdown = currentPostReactionsData.breakdown || {};
                document.getElementById('wrTabAll').innerText = `সব (${total})`;
                document.getElementById('wrTabLike').innerText = `👍 (${breakdown.like || 0})`;
                document.getElementById('wrTabLove').innerText = `❤️ (${breakdown.love || 0})`;
                document.getElementById('wrTabHaha').innerText = `😆 (${breakdown.haha || 0})`;
                document.getElementById('wrTabWow').innerText = `😮 (${breakdown.wow || 0})`;
                document.getElementById('wrTabSad').innerText = `😢 (${breakdown.sad || 0})`;
                document.getElementById('wrTabAngry').innerText = `😡 (${breakdown.angry || 0})`;

                renderWhoReactedUsers('all');
            } catch (err) {
                list.innerHTML = '<div style="padding: 24px; text-align: center; color: #ef4444;">রিঅ্যাকশন তালিকা লোড করা যায়নি।</div>';
            }
        }

        function closeWhoReactedModal() {
            document.getElementById('whoReactedModal').style.display = 'none';
            currentPostReactionsData = null;
        }

        function filterWhoReactedTab(type, btn) {
            document.querySelectorAll('#whoReactedTabsBar .sr-tab-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            renderWhoReactedUsers(type);
        }

        function renderWhoReactedUsers(type = 'all') {
            const list = document.getElementById('whoReactedList');
            const allUsers = currentPostReactionsData?.users || [];
            const filtered = type === 'all' ? allUsers : allUsers.filter(u => u.type === type);

            if (filtered.length === 0) {
                list.innerHTML = '<div style="padding: 24px; text-align: center; color: var(--fb-text-secondary);">কোনো ব্যবহারকারী পাওয়া যায়নি।</div>';
                return;
            }

            list.innerHTML = filtered.map(u => {
                const rxIcon = u.type === 'love' ? '❤️' : (u.type === 'haha' ? '😆' : (u.type === 'wow' ? '😮' : (u.type === 'sad' ? '😢' : (u.type === 'angry' ? '😡' : '👍'))));
                const avatar = u.avatar_url || '/images/default-avatar.svg';
                const uProfileUrl = window.getUserProfileUrl ? window.getUserProfileUrl(u) : `/u/${encodeURIComponent(u.username || u.user_id)}`;
                return `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border-bottom: 1px solid var(--fb-border); border-radius: 8px; margin-bottom: 4px;">
                        <a href="${uProfileUrl}" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; cursor: pointer;" title="${escapeHtml(u.name)}-এর প্রোফাইল দেখুন">
                            <div style="position: relative;">
                                <img src="${avatar}" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                <span style="position: absolute; bottom: -2px; right: -2px; font-size: 12px; background: white; border-radius: 50%; padding: 1px;">${rxIcon}</span>
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 14px;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${escapeHtml(u.name)}</div>
                                <div style="font-size: 12px; color: var(--fb-text-secondary);">@${escapeHtml(u.username || 'user')}</div>
                            </div>
                        </a>
                        <button type="button" class="post-btn" style="background: var(--fb-primary); color: white; padding: 4px 12px; font-size: 12px; border-radius: 14px; font-weight: 700;" onclick="closeWhoReactedModal(); openDirectChatWithUser(${u.user_id}, '${escapeHtml(u.name)}', '${escapeHtml(u.username || '')}', '${u.avatar_url || ''}')">
                            মেসেজ পাঠান
                        </button>
                    </div>
                `;
            }).join('');
        }

        /* ------------------------------------------------------------- */
        /* INLINE COMMENTS (REAL ENTERPRISE SYSTEM) */
        /* ------------------------------------------------------------- */
        let inlineReplyingTo = {};

        async function toggleCommentsDrawer(postId) {
            const drawer = document.getElementById(`inline-comments-${postId}`);
            if (!drawer) return;
            if (drawer.style.display === 'none' || !drawer.style.display) {
                drawer.style.display = 'block';
                await loadInlineComments(postId);
            } else {
                drawer.style.display = 'none';
            }
        }

        async function loadInlineComments(postId) {
            const list = document.getElementById(`inline-comments-list-${postId}`);
            if (!list) return;
            try {
                const headers = { 'Accept': 'application/json' };
                if (currentToken) headers['Authorization'] = `Bearer ${currentToken}`;
                const res = await fetch(`/api/v1/posts/${postId}/comments`, { headers });
                const json = await res.json();
                const comments = Array.isArray(json.data) ? json.data : (json.data?.comments || []);

                if (comments.length === 0) {
                    list.innerHTML = '<div style="font-size: 13px; color: var(--fb-text-secondary); text-align: center; padding: 10px;">এখনো কোনো মন্তব্য নেই। প্রথম মন্তব্যটি আপনি করুন!</div>';
                    return;
                }

                list.innerHTML = comments.map(c => {
                    const commentAuthor = c.author || c.user || {};
                    const author = commentAuthor.name || 'ইউজার';
                    const authorUsername = commentAuthor.username || (commentAuthor.id ? `${commentAuthor.id}` : null);
                    const avatar = commentAuthor.avatar_url || commentAuthor.profile?.avatar_url || '/images/default-avatar.svg';
                    const authorProfileUrl = window.getUserProfileUrl ? window.getUserProfileUrl(commentAuthor) : (authorUsername ? `/u/${encodeURIComponent(authorUsername)}` : '#');
                    const isMyComment = currentUser && (commentAuthor.id === currentUser.id || c.user_id === currentUser.id);
                    const repliesCount = c.counters?.replies || c.replies_count || 0;
                    const likesCount = c.counters?.likes || c.likes_count || 0;

                    return `
                        <div class="jj-comment-item" id="comment-row-${c.id}" style="display: flex; gap: 8px;">
                            <a href="${authorProfileUrl}" style="text-decoration: none; flex-shrink: 0;" title="${escapeHtml(author)}-এর প্রোফাইল দেখুন">
                                <img src="${avatar}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                            </a>
                            <div style="flex: 1;">
                                <div style="background: #f0f2f5; padding: 8px 12px; border-radius: 16px; display: inline-block; max-width: 95%;">
                                    <a href="${authorProfileUrl}" style="font-weight: 700; font-size: 13px; text-decoration: none; color: var(--fb-text-primary); display: inline-block;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'" title="${escapeHtml(author)}-এর প্রোফাইল দেখুন">${escapeHtml(author)}</a>
                                    <div id="comment-body-${c.id}" style="font-size: 13.5px; margin-top: 2px; word-break: break-word;">${escapeHtml(c.body || c.content || '')}</div>
                                </div>
                                <div style="display: flex; gap: 12px; align-items: center; margin-top: 4px; margin-left: 8px; font-size: 11.5px; color: var(--fb-text-secondary); font-weight: 600;">
                                    <span style="cursor: pointer;" onclick="likeInlineComment(${c.id}, this)">লাইক (${likesCount})</span>
                                    <span>·</span>
                                    <span style="cursor: pointer;" onclick="replyToInlineComment(${postId}, ${c.id}, '${escapeHtml(author)}')">উত্তর দিন</span>
                                    <span>·</span>
                                    <span>${formatTimeAgo(c.created_at)}</span>
                                    ${isMyComment ? `
                                        <span>·</span>
                                        <span style="cursor: pointer; color: var(--fb-primary);" onclick="editInlineComment(${c.id}, ${postId})">সম্পাদনা</span>
                                        <span>·</span>
                                        <span style="cursor: pointer; color: #ef4444;" onclick="deleteInlineComment(${c.id}, ${postId})">মুছুন</span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (err) {
                list.innerHTML = '<div style="font-size: 13px; color: #ef4444; padding: 8px; text-align: center;">মন্তব্য লোড হতে সমস্যা হয়েছে।</div>';
            }
        }

        async function submitInlineComment(postId) {
            const input = document.getElementById(`inline-comment-input-${postId}`);
            if (!input) return;
            const text = input.value.trim();
            if (!text) return;

            const parentId = inlineReplyingTo[postId] || null;

            try {
                const res = await fetch(`/api/v1/posts/${postId}/comments`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ body: text, parent_id: parentId })
                });
                const data = await res.json();
                if (data.success) {
                    input.value = '';
                    cancelInlineReply(postId);
                    await loadInlineComments(postId);
                    
                    // Update post card comment count
                    const counter = document.getElementById(`comments-count-${postId}`);
                    if (counter) {
                        const currentVal = parseInt(counter.innerText) || 0;
                        counter.innerText = `${currentVal + 1} টি মন্তব্য`;
                    }
                    showToast('মন্তব্য সফলভাবে যুক্ত হয়েছে! 💬');
                } else {
                    showToast(data.message || 'মন্তব্য পাঠাতে সমস্যা হয়েছে।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        function replyToInlineComment(postId, commentId, authorName) {
            inlineReplyingTo[postId] = commentId;
            const banner = document.getElementById(`inline-reply-banner-${postId}`);
            const bannerText = document.getElementById(`inline-reply-text-${postId}`);
            if (banner && bannerText) {
                bannerText.innerText = `উত্তর দিচ্ছেন: @${authorName}`;
                banner.style.display = 'flex';
            }
            const input = document.getElementById(`inline-comment-input-${postId}`);
            if (input) {
                input.focus();
                input.placeholder = `@${authorName}-এর মন্তব্যের উত্তর লিখুন...`;
            }
        }

        function cancelInlineReply(postId) {
            delete inlineReplyingTo[postId];
            const banner = document.getElementById(`inline-reply-banner-${postId}`);
            if (banner) banner.style.display = 'none';
            const input = document.getElementById(`inline-comment-input-${postId}`);
            if (input) input.placeholder = 'একটি মন্তব্য লিখুন... (Enter চাপুন)';
        }

        async function editInlineComment(commentId, postId) {
            const bodyEl = document.getElementById(`comment-body-${commentId}`);
            const currentText = bodyEl ? bodyEl.innerText : '';
            const newText = prompt('আপনার মন্তব্য সম্পাদনা করুন:', currentText);
            if (newText === null || !newText.trim() || newText.trim() === currentText) return;

            try {
                const res = await fetch(`/api/v1/comments/${commentId}`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ body: newText.trim() })
                });
                const data = await res.json();
                if (data.success) {
                    if (bodyEl) bodyEl.innerText = newText.trim();
                    showToast('মন্তব্য আপডেট হয়েছে!');
                } else {
                    showToast(data.message || 'মন্তব্য আপডেট করা যায়নি।');
                }
            } catch (err) {
                showToast('ত্রুটি: ' + err.message);
            }
        }

        async function deleteInlineComment(commentId, postId) {
            if (!confirm('সত্যিই এই মন্তব্যটি মুছে ফেলতে চান?')) return;
            try {
                const res = await fetch(`/api/v1/comments/${commentId}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    const row = document.getElementById(`comment-row-${commentId}`);
                    if (row) row.remove();
                    const counter = document.getElementById(`comments-count-${postId}`);
                    if (counter) {
                        const currentVal = Math.max(0, (parseInt(counter.innerText) || 1) - 1);
                        counter.innerText = `${currentVal} টি মন্তব্য`;
                    }
                    showToast('মন্তব্য মুছে ফেলা হয়েছে।');
                } else {
                    showToast(data.message || 'মন্তব্য মুছতে সমস্যা হয়েছে।');
                }
            } catch (err) {
                showToast('ত্রুটি: ' + err.message);
            }
        }

        async function likeInlineComment(commentId, btn) {
            try {
                const res = await fetch(`/api/v1/comments/${commentId}/react`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ type: 'like' })
                });
                const data = await res.json();
                if (data.success) {
                    btn.style.color = data.data?.reacted ? 'var(--fb-primary)' : 'var(--fb-text-secondary)';
                    showToast('রিঅ্যাকশন আপডেট হয়েছে!');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* MODAL COMMENTS (FALLBACK) */
        /* ------------------------------------------------------------- */
        let currentCommentPostId = null;
        async function openCommentsModal(postId) {
            currentCommentPostId = postId;
            document.getElementById('commentsModal').style.display = 'flex';
            await loadComments(postId);
        }
        function closeCommentsModal() {
            document.getElementById('commentsModal').style.display = 'none';
            currentCommentPostId = null;
        }
        async function loadComments(postId) {
            const list = document.getElementById('commentsList');
            list.innerHTML = '<div style="padding: 16px; text-align: center;">মন্তব্য লোড হচ্ছে...</div>';
            try {
                const res = await fetch(`/api/v1/posts/${postId}/comments`, {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                const comments = Array.isArray(data.data) ? data.data : (data.data?.comments || []);
                if (comments.length === 0) {
                    list.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--fb-text-secondary);">কোনো মন্তব্য নেই। প্রথম মন্তব্যটি লিখুন!</div>';
                    return;
                }
                list.innerHTML = comments.map(c => `
                    <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                        <div class="avatar" style="width: 32px; height: 32px;">${(c.author?.name || c.user?.name || 'ইউ').charAt(0)}</div>
                        <div style="background: #f0f2f5; padding: 8px 12px; border-radius: 16px; flex: 1;">
                            <div style="font-weight: 700; font-size: 13px;">${escapeHtml(c.author?.name || c.user?.name || 'ইউজার')}</div>
                            <div style="font-size: 14px; margin-top: 2px;">${escapeHtml(c.body || c.content || '')}</div>
                        </div>
                    </div>
                `).join('');
            } catch (err) {
                list.innerHTML = '<div style="padding: 16px; color: red;">মন্তব্য লোড হতে সমস্যা হয়েছে।</div>';
            }
        }
        async function submitComment() {
            const input = document.getElementById('commentTextInput');
            const body = input.value.trim();
            if (!body || !currentCommentPostId) return;
            try {
                const res = await fetch(`/api/v1/posts/${currentCommentPostId}/comments`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ body })
                });
                const data = await res.json();
                if (data.success) {
                    input.value = '';
                    showToast('মন্তব্য প্রকাশিত হয়েছে!');
                    await loadComments(currentCommentPostId);
                    const counter = document.getElementById(`comments-count-${currentCommentPostId}`);
                    if (counter) {
                        const currentVal = parseInt(counter.innerText) || 0;
                        counter.innerText = `${currentVal + 1} টি মন্তব্য`;
                    }
                } else {
                    showToast(data.message || 'মন্তব্য পাঠাতে ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* POST EDIT / DELETE / SHARE / SAVE / LIGHTBOX */
        /* ------------------------------------------------------------- */
        function openEditPostModal(id) {
            const bodyEl = document.getElementById(`post-body-text-${id}`);
            const text = bodyEl ? bodyEl.innerText : '';
            document.getElementById('editPostId').value = id;
            document.getElementById('editPostText').value = text;
            document.getElementById('editPostModal').style.display = 'flex';
        }
        function closeEditPostModal() {
            document.getElementById('editPostModal').style.display = 'none';
        }
        async function submitPostUpdate() {
            const id = document.getElementById('editPostId').value;
            const content = document.getElementById('editPostText').value.trim();
            if (!id || !content) return;

            try {
                const res = await fetch(`/api/v1/posts/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ content })
                });
                const data = await res.json();
                if (data.success) {
                    const bodyEl = document.getElementById(`post-body-text-${id}`);
                    if (bodyEl) bodyEl.innerText = content;
                    closeEditPostModal();
                    showToast('পোস্ট সফলভাবে হালনাগাদ করা হয়েছে! ✍️');
                } else {
                    showToast(data.message || 'পোস্ট আপডেট করা যায়নি।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        async function deletePost(id) {
            if (!confirm('আপনি কি নিশ্চিত যে এই পোস্টটি মুছে ফেলতে চান?')) return;
            const card = document.getElementById(`post-card-${id}`);
            if (card) {
                card.style.transition = 'all 0.3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
            }
            try {
                const res = await fetch(`/api/v1/posts/${id}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    if (card) card.remove();
                    showToast('পোস্ট সফলভাবে মুছে ফেলা হয়েছে। 🗑️');
                } else {
                    if (card) {
                        card.style.opacity = '1';
                        card.style.transform = 'none';
                    }
                    showToast(data.message || 'পোস্ট মুছতে ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                if (card) {
                    card.style.opacity = '1';
                    card.style.transform = 'none';
                }
                showToast('এরর: ' + err.message);
            }
        }

        function copyPostLink(id) {
            const url = `${window.location.origin}/dashboard#post-card-${id}`;
            navigator.clipboard.writeText(url).then(() => {
                showToast('পোস্টের সরাসরি লিংক কপি করা হয়েছে! 📋');
            }).catch(() => {
                showToast(`লিংক: ${url}`);
            });
        }

        function toggleSavePostAction(id, contentEncoded) {
            const content = decodeURIComponent(contentEncoded);
            let saved = JSON.parse(localStorage.getItem('jugajug_saved_posts') || '[]');
            const index = saved.findIndex(item => item.id === id);
            if (index >= 0) {
                saved.splice(index, 1);
                localStorage.setItem('jugajug_saved_posts', JSON.stringify(saved));
                showToast('সংরক্ষিত তালিকা থেকে সরানো হয়েছে! 🔖');
            } else {
                saved.push({ id, content, saved_at: new Date().toISOString() });
                localStorage.setItem('jugajug_saved_posts', JSON.stringify(saved));
                showToast('পোস্ট সফলভাবে সংরক্ষিত তালিকায় সেভ হয়েছে! 🔖');
            }
            fetchUnreadCounters();
        }

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

        /* ------------------------------------------------------------- */
        /* POST SHARE & REPORT MODALS */
        /* ------------------------------------------------------------- */
        let currentSharePostId = null;
        function openShareModal(postId) {
            currentSharePostId = postId;
            document.getElementById('shareModal').style.display = 'flex';
            document.getElementById('shareCaptionInput').value = '';
            document.getElementById('shareCaptionInput').focus();
        }
        function closeShareModal() {
            document.getElementById('shareModal').style.display = 'none';
            currentSharePostId = null;
        }
        async function submitShare() {
            if (!currentSharePostId) return;
            const caption = document.getElementById('shareCaptionInput').value.trim();
            try {
                const res = await fetch(`/api/v1/posts/${currentSharePostId}/share`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ caption })
                });
                const data = await res.json();
                if (data.success) {
                    closeShareModal();
                    showToast('পোস্ট সফলভাবে শেয়ার করা হয়েছে! 🚀');
                    const counter = document.getElementById(`shares-count-${currentSharePostId}`);
                    if (counter && data.data && typeof data.data.shares_count !== 'undefined') {
                        counter.innerText = `${data.data.shares_count} টি শেয়ার`;
                    }
                } else {
                    showToast(data.message || 'পোস্ট শেয়ার করতে ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        let currentReportType = 'post';
        let currentReportId = null;
        function openReportModal(type, id) {
            currentReportType = type;
            currentReportId = id;
            document.getElementById('reportModal').style.display = 'flex';
        }
        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
            currentReportId = null;
        }
        async function submitReport() {
            if (!currentReportId) return;
            const reason = document.getElementById('reportReasonSelect').value;
            const details = document.getElementById('reportDetailsText').value.trim();
            try {
                const res = await fetch('/api/v1/reports', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        reportable_type: currentReportType,
                        reportable_id: currentReportId,
                        reason: reason,
                        details: details
                    })
                });
                const data = await res.json();
                if (data.success) {
                    closeReportModal();
                    showToast('রিপোর্ট সাবমিট হয়েছে। মডারেশন টিম যাচাই করবে।');
                } else {
                    showToast(data.message || 'রিপোর্ট সাবমিট করা যায়নি।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* POST CREATOR MODAL */
        /* ------------------------------------------------------------- */
        function openCreatePostModal(mode = null) {
            if (window.EnterprisePostComposer && typeof window.EnterprisePostComposer.open === 'function') {
                window.EnterprisePostComposer.open(mode);
            } else {
                const modal = document.getElementById('createPostModal');
                if (modal) modal.style.display = 'flex';
            }
        }
        function closeCreatePostModal() {
            if (window.EnterprisePostComposer && typeof window.EnterprisePostComposer.close === 'function') {
                window.EnterprisePostComposer.close();
            } else {
                const modal = document.getElementById('createPostModal');
                if (modal) modal.style.display = 'none';
            }
        }
        function togglePostFeelingRow() {
            const row = document.getElementById('modalPostFeelingRow');
            row.style.display = row.style.display === 'none' ? 'flex' : 'none';
        }
        function togglePostMediaRow() {
            const row = document.getElementById('modalPostMediaRow');
            row.style.display = row.style.display === 'none' ? 'flex' : 'none';
        }
        async function submitModalPost() {
            let text = document.getElementById('modalPostText').value.trim();
            const feeling = document.getElementById('modalPostFeeling')?.value || '';
            const mediaUrl = document.getElementById('modalPostMediaUrl')?.value.trim() || '';
            const audience = document.getElementById('modalPostAudience')?.value || 'public';

            if (feeling) text = `${text} ${feeling}`.trim();
            if (mediaUrl) text = `${text}\n${mediaUrl}`.trim();
            if (!text) return;

            try {
                const res = await fetch('/api/v1/posts', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ content: text, audience: audience })
                });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('modalPostText').value = '';
                    if (document.getElementById('modalPostMediaUrl')) document.getElementById('modalPostMediaUrl').value = '';
                    if (document.getElementById('modalPostFeeling')) document.getElementById('modalPostFeeling').value = '';
                    document.getElementById('modalPostMediaRow').style.display = 'none';
                    document.getElementById('modalPostFeelingRow').style.display = 'none';
                    closeCreatePostModal();
                    showToast('পোস্ট সফলভাবে পাবলিশ হয়েছে! 🚀');
                    fetchFeedPosts();
                } else {
                    showToast(data.message || 'পোস্ট করতে সমস্যা হয়েছে');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* STORIES & REELS DELEGATION */
        /* ------------------------------------------------------------- */
        async function fetchStories() {
            if (window.JugajugMediaSuite) {
                window.JugajugMediaSuite.loadStories();
            }
        }
        function openCreateStoryModal() {
            if (window.JugajugMediaSuite) window.JugajugMediaSuite.openCreateStoryModal();
        }
        function closeCreateStoryModal() {
            if (window.JugajugMediaSuite) window.JugajugMediaSuite.closeCreateStoryModal();
        }
        async function submitStory() {
            if (window.JugajugMediaSuite) window.JugajugMediaSuite.submitStory();
        }
        function closeStoryViewer() {
            if (window.JugajugMediaSuite) window.JugajugMediaSuite.closeStoryViewer();
        }

        /* ------------------------------------------------------------- */
        /* ENTERPRISE FLOATING MESSENGER & REAL-TIME CHAT */
        /* ------------------------------------------------------------- */
        let activeChatConversationId = null;
        let activeChatPollingTimer = null;
        let cachedConversations = [];
        let mediaRecorderInstance = null;
        let voiceAudioChunks = [];
        let voiceRecordTimerInterval = null;
        let voiceRecordSeconds = 0;

        window.jugajugOnlineUsers = window.jugajugOnlineUsers || new Set();

        function formatPresenceTime(dateString) {
            try {
                const date = new Date(dateString);
                const now = new Date();
                const diffSec = Math.floor((now - date) / 1000);
                if (diffSec < 60) return 'এইমাত্র';
                if (diffSec < 3600) return `${Math.floor(diffSec / 60)} মি. আগে`;
                if (diffSec < 86400) return `${Math.floor(diffSec / 3600)} ঘণ্টা আগে`;
                return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
            } catch (e) {
                return 'কিছুক্ষণ আগে';
            }
        }

        async function refreshOnlineFriends() {
            if (!currentToken) return;
            try {
                const res = await fetch('/api/v1/friends/online', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success && Array.isArray(data.data)) {
                    window.jugajugOnlineUsers.clear();
                    data.data.forEach(u => window.jugajugOnlineUsers.add(Number(u.id)));
                }
            } catch (e) {}
        }

        async function fetchConversations() {
            const container = document.getElementById('onlineContactsContainer');
            if (!currentToken || !container) return;
            try {
                await refreshOnlineFriends();
                const res = await fetch('/api/v1/conversations', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                const convs = Array.isArray(data.data) ? data.data : (data.data?.conversations || []);
                cachedConversations = convs;
                if (typeof updateMessengerBadge === 'function') {
                    updateMessengerBadge(convs);
                }

                if (convs.length === 0) {
                    // Fallback to friends list as potential contacts
                    try {
                        const friendsRes = await fetch('/api/v1/friends', {
                            headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                        });
                        const friendsJson = await friendsRes.json();
                        const friends = Array.isArray(friendsJson.data) ? friendsJson.data : [];
                        if (friends.length > 0) {
                            container.innerHTML = friends.map(f => {
                                const isOnline = window.jugajugOnlineUsers.has(Number(f.id));
                                return `
                                <div class="contact-row" data-user-id="${f.id}" onclick="openDirectChatWithUser(${f.id}, '${escapeHtml(f.name)}', '${escapeHtml(f.username || '')}', '${f.profile?.avatar_url || ''}')">
                                    <div class="contact-avatar-wrapper">
                                        <img src="${f.profile?.avatar_url || '/images/default-avatar.svg'}" class="avatar" style="width:36px;height:36px;object-fit:cover;border-radius:50%;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                        <div class="online-dot" style="${isOnline ? 'background: #22c55e;' : 'background: #94a3b8; display: none;'}"></div>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div class="contact-name">${escapeHtml(f.name)}</div>
                                        <div class="contact-status-text">
                                            ${isOnline 
                                                ? '<span class="contact-online-badge"><span class="online-pulse-dot"></span>অনলাইন</span>' 
                                                : '<span style="font-size: 11px; color: var(--fb-text-secondary);">চ্যাট শুরু করুন</span>'}
                                        </div>
                                    </div>
                                </div>
                            `}).join('');
                            return;
                        }
                    } catch (fe) {}
                    container.innerHTML = '<div style="font-size: 13px; color: var(--fb-text-secondary); padding: 12px; text-align: center;">কোনো সক্রিয় চ্যাট নেই। বন্ধুদের মেসেজ পাঠিয়ে আড্ডা শুরু করুন!</div>';
                    return;
                }

                renderContactsList(convs);
            } catch (err) {
                console.error('Conversations error:', err);
            }
        }

        function renderContactsList(convs) {
            const container = document.getElementById('onlineContactsContainer');
            if (!container) return;
            container.innerHTML = convs.map(c => {
                const other = c.other_user || c.participants?.find(p => p.id !== currentUser?.id);
                const title = other?.name || c.title || 'চ্যাট';
                const avatar = other?.avatar_url || other?.profile?.avatar_url || c.avatar_url || '/images/default-avatar.svg';
                const lastMsg = c.last_message ? (c.last_message.deleted_at ? 'মেসেজ মুছে ফেলা হয়েছে' : (c.last_message.body || 'মিডিয়া পাঠানো হয়েছে')) : 'নতুন চ্যাট';
                const unread = c.unread_count || 0;
                const otherId = other?.id || null;
                const otherUsername = other?.username || '';
                const isOnline = otherId ? window.jugajugOnlineUsers.has(Number(otherId)) : false;

                return `
                    <div class="contact-row" data-user-id="${otherId || ''}" onclick="openRealChat(${c.id}, '${escapeHtml(title)}', '${avatar}', ${otherId || 'null'}, '${escapeHtml(otherUsername)}')">
                        <div class="contact-avatar-wrapper">
                            <img src="${avatar}" class="avatar" style="width: 36px; height: 36px; object-fit: cover; border-radius: 50%;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                            <div class="online-dot" style="${isOnline ? 'background: #22c55e;' : 'background: #94a3b8; display: none;'}"></div>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div class="contact-name">${escapeHtml(title)}</div>
                            <div class="contact-status-text" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                ${isOnline 
                                    ? '<span class="contact-online-badge"><span class="online-pulse-dot"></span>অনলাইন</span>' 
                                    : `<span style="font-size: 11px; color: var(--fb-text-secondary);">${escapeHtml(lastMsg)}</span>`}
                            </div>
                        </div>
                        ${unread > 0 ? `<span class="sidebar-badge" style="background:#0084ff; color:white; padding: 2px 6px; font-size:11px; border-radius:10px;">${unread}</span>` : ''}
                    </div>
                `;
            }).join('');
        }

        function filterContacts(keyword) {
            const q = keyword.trim().toLowerCase();
            if (!q) {
                renderContactsList(cachedConversations);
                return;
            }
            const filtered = cachedConversations.filter(c => {
                const other = c.other_user || c.participants?.find(p => p.id !== currentUser?.id);
                const title = other?.name || c.title || '';
                return title.toLowerCase().includes(q);
            });
            renderContactsList(filtered);
        }

        let cachedActiveFriends = [];

        function toggleMessengerDropdown(event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            if (window.innerWidth <= 768) {
                window.location.href = '/messages';
                return;
            }

            const notifDropdown = document.getElementById('notificationsDropdown');
            if (notifDropdown) notifDropdown.style.display = 'none';
            const userDropdown = document.getElementById('userMenuDropdown');
            if (userDropdown) userDropdown.style.display = 'none';

            const dropdown = document.getElementById('topMessengerDropdown');
            if (!dropdown) return;

            const isShown = dropdown.style.display === 'flex' || dropdown.style.display === 'block';
            if (isShown) {
                dropdown.style.display = 'none';
            } else {
                dropdown.style.display = 'flex';
                loadMessengerDropdownData();
            }
        }

        function closeMessengerDropdown() {
            const dropdown = document.getElementById('topMessengerDropdown');
            if (dropdown) dropdown.style.display = 'none';
        }

        function openLatestOrToggleChat(event) {
            toggleMessengerDropdown(event);
        }

        async function loadMessengerDropdownData() {
            loadDropdownActiveFriends();
            loadDropdownConversations();
        }

        async function loadDropdownActiveFriends(query = '') {
            const listEl = document.getElementById('messengerDropdownActiveFriendsList');
            const sectionEl = document.getElementById('messengerDropdownActiveSection');
            if (!listEl) return;

            try {
                const qParam = query ? `?q=${encodeURIComponent(query)}` : '';
                const res = await fetch(`/api/v1/presence/friends/active${qParam}`, {
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success && Array.isArray(data.data)) {
                    cachedActiveFriends = data.data;
                    renderMessengerDropdownActiveFriends(data.data);
                } else {
                    renderMessengerDropdownActiveFriends([]);
                }
            } catch (err) {
                console.error('Active friends load error:', err);
                renderMessengerDropdownActiveFriends([]);
            }
        }

        function renderMessengerDropdownActiveFriends(friends) {
            const listEl = document.getElementById('messengerDropdownActiveFriendsList');
            const sectionEl = document.getElementById('messengerDropdownActiveSection');
            if (!listEl) return;

            const safeFriends = Array.isArray(friends) ? friends : [];
            // Prioritize online friends or users tracked in jugajugOnlineUsers
            const displayFriends = safeFriends.filter(f => f.online || (f.id && window.jugajugOnlineUsers.has(Number(f.id))));

            if (displayFriends.length === 0) {
                const fallbackFriends = safeFriends.slice(0, 8);
                if (fallbackFriends.length === 0) {
                    if (sectionEl) sectionEl.style.display = 'none';
                    return;
                }
                if (sectionEl) sectionEl.style.display = 'block';
                listEl.innerHTML = fallbackFriends.map(f => {
                    const isOnline = f.online || (f.id && window.jugajugOnlineUsers.has(Number(f.id)));
                    const avatar = f.avatar_url || f.profile?.avatar_url || '/images/default-avatar.svg';
                    const name = f.name || 'বন্ধু';
                    const firstName = name.split(' ')[0] || name;
                    return `
                        <div class="top-messenger-active-item" onclick="handleActiveFriendClick(${f.id}, '${escapeHtml(name)}', '${escapeHtml(f.username || '')}', '${avatar}', ${f.conversation_id || 'null'})" title="${escapeHtml(name)}">
                            <div class="top-messenger-active-avatar-wrap">
                                <img src="${avatar}" alt="${escapeHtml(name)}" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                ${isOnline ? '<div class="active-online-dot"></div>' : ''}
                            </div>
                            <div class="top-messenger-active-name">${escapeHtml(firstName)}</div>
                        </div>
                    `;
                }).join('');
                return;
            }

            if (sectionEl) sectionEl.style.display = 'block';
            listEl.innerHTML = displayFriends.map(f => {
                const avatar = f.avatar_url || f.profile?.avatar_url || '/images/default-avatar.svg';
                const name = f.name || 'বন্ধু';
                const firstName = name.split(' ')[0] || name;
                return `
                    <div class="top-messenger-active-item" onclick="handleActiveFriendClick(${f.id}, '${escapeHtml(name)}', '${escapeHtml(f.username || '')}', '${avatar}', ${f.conversation_id || 'null'})" title="${escapeHtml(name)}">
                        <div class="top-messenger-active-avatar-wrap">
                            <img src="${avatar}" alt="${escapeHtml(name)}" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                            <div class="active-online-dot"></div>
                        </div>
                        <div class="top-messenger-active-name">${escapeHtml(firstName)}</div>
                    </div>
                `;
            }).join('');
        }

        function handleActiveFriendClick(userId, name, username, avatar, conversationId) {
            closeMessengerDropdown();
            if (conversationId) {
                openRealChat(conversationId, name, avatar, userId, username);
            } else {
                openDirectChatWithUser(userId, name, username, avatar);
            }
        }

        async function loadDropdownConversations() {
            const listEl = document.getElementById('messengerDropdownConversationsList');
            if (cachedConversations && cachedConversations.length > 0) {
                renderMessengerDropdownConversations(cachedConversations);
            } else if (listEl) {
                listEl.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px; padding: 24px 16px;">লোড হচ্ছে...</div>';
            }

            try {
                const res = await fetch('/api/v1/conversations', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                const convs = Array.isArray(data.data) ? data.data : (data.data?.conversations || []);
                cachedConversations = convs;
                renderMessengerDropdownConversations(convs);
                updateMessengerBadge(convs);
            } catch (err) {
                console.error('Dropdown conversations load error:', err);
                if (cachedConversations && cachedConversations.length > 0) {
                    renderMessengerDropdownConversations(cachedConversations);
                } else if (listEl) {
                    listEl.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px; padding: 24px 16px;">চ্যাট লোড করতে সমস্যা হয়েছে</div>';
                }
            }
        }

        function updateMessengerBadge(convs) {
            const badge = document.getElementById('topMessengerBadge');
            const sidebarBadge = document.getElementById('sidebarMsgBadge');
            const totalUnread = (convs || []).reduce((acc, c) => acc + (c.unread_count || 0), 0);
            if (badge) {
                if (totalUnread > 0) {
                    badge.innerText = totalUnread > 99 ? '99+' : totalUnread;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
            if (sidebarBadge) {
                if (totalUnread > 0) {
                    sidebarBadge.innerText = totalUnread > 99 ? '99+' : totalUnread;
                    sidebarBadge.style.display = 'inline-block';
                } else {
                    sidebarBadge.style.display = 'none';
                }
            }
        }

        function renderMessengerDropdownConversations(convs) {
            const listEl = document.getElementById('messengerDropdownConversationsList');
            if (!listEl) return;

            if (!convs || convs.length === 0) {
                listEl.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px; padding: 28px 16px; line-height: 1.5;">কোনো সাম্প্রতিক চ্যাট নেই。<br><span style="font-size: 12px;">পরিচিত বন্ধুদের বার্তা পাঠিয়ে আড্ডা শুরু করুন!</span></div>';
                return;
            }

            listEl.innerHTML = convs.map(c => {
                const other = c.other_user || c.participants?.find(p => p.id !== currentUser?.id);
                const title = other?.name || c.title || 'চ্যাট';
                const avatar = other?.avatar_url || other?.profile?.avatar_url || c.avatar_url || '/images/default-avatar.svg';
                const otherId = other?.id || null;
                const otherUsername = other?.username || '';
                const isOnline = otherId ? (window.jugajugOnlineUsers.has(Number(otherId)) || other?.online) : false;
                const unread = c.unread_count || 0;

                let lastMsg = 'নতুন কথোপকথন';
                if (c.last_message) {
                    if (c.last_message.deleted_at) {
                        lastMsg = 'মেসেজ মুছে ফেলা হয়েছে';
                    } else if (c.last_message.type === 'call_video') {
                        lastMsg = '📹 ভিডিও কল';
                    } else if (c.last_message.type === 'call_audio') {
                        lastMsg = '📞 অডিও কল';
                    } else if (c.last_message.body) {
                        lastMsg = c.last_message.body;
                    } else if (c.last_message.attachment_url || c.last_message.attachments?.length) {
                        lastMsg = '📎 মিডিয়া ফাইল';
                    }
                }
                const timeStr = c.last_message?.created_at ? formatTimeAgo(c.last_message.created_at) : '';

                return `
                    <div class="top-messenger-conv-item ${unread > 0 ? 'unread' : ''}" 
                         onclick="openRealChat(${c.id}, '${escapeHtml(title)}', '${avatar}', ${otherId || 'null'}, '${escapeHtml(otherUsername)}'); closeMessengerDropdown();" 
                         title="${escapeHtml(title)}">
                        <div class="top-messenger-conv-avatar-wrap">
                            <img src="${avatar}" alt="${escapeHtml(title)}" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                            ${isOnline ? '<div class="conv-online-dot"></div>' : ''}
                        </div>
                        <div class="top-messenger-conv-body">
                            <div class="top-messenger-conv-top">
                                <span class="top-messenger-conv-name">${escapeHtml(title)}</span>
                                ${timeStr ? `<span class="top-messenger-conv-time">${escapeHtml(timeStr)}</span>` : ''}
                            </div>
                            <div class="top-messenger-conv-msg-row">
                                <span class="top-messenger-conv-last-msg">${escapeHtml(lastMsg)}</span>
                                ${unread > 0 ? `<span class="top-messenger-unread-pill">${unread}</span>` : (isOnline ? '<span class="contact-online-badge" style="padding: 1px 6px; font-size: 10px;"><span class="online-pulse-dot" style="width: 5px; height: 5px;"></span>অনলাইন</span>' : '')}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function filterMessengerDropdown(keyword) {
            const q = (keyword || '').trim().toLowerCase();
            if (!q) {
                renderMessengerDropdownConversations(cachedConversations);
                renderMessengerDropdownActiveFriends(cachedActiveFriends);
                return;
            }

            // Filter active friends
            const filteredFriends = cachedActiveFriends.filter(f =>
                (f.name && f.name.toLowerCase().includes(q)) ||
                (f.username && f.username.toLowerCase().includes(q))
            );
            renderMessengerDropdownActiveFriends(filteredFriends);

            // Filter conversations
            const filteredConvs = cachedConversations.filter(c => {
                const other = c.other_user || c.participants?.find(p => p.id !== currentUser?.id);
                const title = other?.name || c.title || '';
                const lastMsg = c.last_message?.body || '';
                return title.toLowerCase().includes(q) || lastMsg.toLowerCase().includes(q);
            });
            renderMessengerDropdownConversations(filteredConvs);
        }

        async function openDirectChatWithUser(userId, name, username = '', avatar = '') {
            if (!currentToken) {
                showToast('চ্যাট করতে অনুগ্রহ করে লগইন করুন।');
                return;
            }
            try {
                const res = await fetch('/api/v1/conversations', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ recipient_id: userId })
                });
                const data = await res.json();
                if (data.success && data.data) {
                    const conv = data.data;
                    openRealChat(conv.id, name, avatar, userId, username);
                } else {
                    showToast(data.message || 'চ্যাট শুরু করা যায়নি।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        let activeChatUser = null;

        async function openRealChat(convId, title, avatar = '', recipientId = null, username = null) {
            activeChatConversationId = convId;
            activeChatUser = {
                id: recipientId,
                name: title,
                username: username,
                avatar: avatar
            };

            const box = document.getElementById('messengerChatBox');
            if (!box) return;
            document.getElementById('messengerChatTitle').innerText = title || 'চ্যাট';
            const avatarEl = document.getElementById('messengerChatAvatar');
            if (avatarEl) {
                if (avatar) {
                    avatarEl.innerHTML = `<img src="${avatar}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">`;
                } else {
                    avatarEl.innerText = title && title !== 'চ্যাট' ? title.charAt(0) : 'র';
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

            // If username, real name or recipient ID is missing, resolve it from conversation details
            const findOtherFromConv = (conv) => {
                if (!conv) return null;
                if (conv.other_user) return conv.other_user;
                if (Array.isArray(conv.participants)) {
                    return conv.participants.find(p => p.id !== currentUser?.id);
                }
                return null;
            };

            let other = findOtherFromConv(cachedConversations?.find(c => c.id === convId));
            if (other) {
                activeChatUser.id = activeChatUser.id || other.id;
                activeChatUser.username = activeChatUser.username || other.username;
                activeChatUser.avatar = activeChatUser.avatar || other.avatar_url || other.profile?.avatar_url;
                if (other.name && (title === 'চ্যাট' || !title)) {
                    activeChatUser.name = other.name;
                    document.getElementById('messengerChatTitle').innerText = other.name;
                }
                if (avatarEl && !avatar && (other.avatar_url || other.profile?.avatar_url)) {
                    avatarEl.innerHTML = `<img src="${other.avatar_url || other.profile?.avatar_url}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">`;
                }
            } else if (convId) {
                fetch(`/api/v1/conversations/${convId}`, {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                }).then(r => r.json()).then(data => {
                    const conv = data.data;
                    const otherP = findOtherFromConv(conv);
                    if (otherP) {
                        activeChatUser.id = otherP.id;
                        activeChatUser.username = otherP.username;
                        activeChatUser.avatar = otherP.avatar_url || otherP.profile?.avatar_url;
                        if (otherP.name) {
                            activeChatUser.name = otherP.name;
                            document.getElementById('messengerChatTitle').innerText = otherP.name;
                        }
                        if (avatarEl && (otherP.avatar_url || otherP.profile?.avatar_url)) {
                            avatarEl.innerHTML = `<img src="${otherP.avatar_url || otherP.profile?.avatar_url}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">`;
                        }
                    }
                }).catch(() => {});
            }

            // Realtime Header Presence Update
            const updateActiveChatHeaderPresence = (isOnline, lastSeen = null) => {
                const dot = document.getElementById('messengerChatOnlineDot');
                const sub = document.getElementById('messengerChatSubtitle');
                if (dot) {
                    dot.style.background = isOnline ? '#22c55e' : '#94a3b8';
                }
                if (sub) {
                    if (isOnline) {
                        sub.innerHTML = '<span class="contact-online-badge" style="padding: 1px 7px;"><span class="online-pulse-dot"></span>সক্রিয় আছেন</span> · প্রোফাইল দেখুন ↗';
                    } else if (lastSeen) {
                        sub.innerHTML = `সর্বশেষ দেখা গেছে: ${formatPresenceTime(lastSeen)} · প্রোফাইল দেখুন ↗`;
                    } else {
                        sub.innerHTML = 'অফলাইন · প্রোফাইল দেখুন ↗';
                    }
                }
            };

            const peerId = activeChatUser?.id || recipientId;
            if (peerId) {
                const knownOnline = window.jugajugOnlineUsers ? window.jugajugOnlineUsers.has(Number(peerId)) : false;
                updateActiveChatHeaderPresence(knownOnline);
                fetch(`/api/v1/presence/${peerId}`, {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                }).then(r => r.json()).then(res => {
                    if (res.data) {
                        const isOnline = !!res.data.online;
                        if (window.jugajugOnlineUsers) {
                            if (isOnline) window.jugajugOnlineUsers.add(Number(peerId));
                            else window.jugajugOnlineUsers.delete(Number(peerId));
                        }
                        updateActiveChatHeaderPresence(isOnline, res.data.last_seen);
                    }
                }).catch(() => {});
            }

            // Mark read in background
            fetch(`/api/v1/conversations/${convId}/read`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
            }).catch(() => {});

            await loadMessages(convId);

            // Focus composer
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

        function navigateToActiveChatUserProfile(event) {
            if (event) event.stopPropagation();
            if (activeChatUser) {
                window.location.href = window.getUserProfileUrl ? window.getUserProfileUrl(activeChatUser) : `/u/${encodeURIComponent(activeChatUser.username || activeChatUser.id)}`;
                return;
            }
            if (activeChatConversationId) {
                fetch(`/api/v1/conversations/${activeChatConversationId}`, {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                }).then(r => r.json()).then(data => {
                    const conv = data.data;
                    const other = conv?.other_user || conv?.participants?.find(p => p.id !== currentUser?.id);
                    if (other) {
                        window.location.href = window.getUserProfileUrl ? window.getUserProfileUrl(other) : `/u/${encodeURIComponent(other.username || other.id)}`;
                    } else {
                        window.location.href = '/messages';
                    }
                }).catch(() => {
                    window.location.href = '/messages';
                });
            } else {
                window.location.href = '/profile';
            }
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
            activeChatUser = null;
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
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
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
                    fetchConversations();
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
                    if (typeof fetchConversations === 'function') fetchConversations();
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
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    showToast(forEveryone ? 'সবার জন্য বার্তাটি মুছে ফেলা হয়েছে' : 'বার্তাটি আপনার জন্য মুছে ফেলা হয়েছে');
                    if (activeChatConversationId) loadMessagesSilent(activeChatConversationId);
                    fetchConversations();
                } else {
                    showToast(data.message || 'বার্তাটি মুছে ফেলা যায়নি।');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        window.updateContactPresenceUI = function(userId, isOnline) {
            const targetId = Number(userId);
            if (isOnline) {
                window.jugajugOnlineUsers.add(targetId);
            } else {
                window.jugajugOnlineUsers.delete(targetId);
            }
            const contactRow = document.querySelector(`.contact-row[data-user-id="${targetId}"]`);
            if (contactRow) {
                const dot = contactRow.querySelector('.online-dot');
                const statusText = contactRow.querySelector('.contact-status-text');
                if (dot) {
                    dot.style.background = isOnline ? '#22c55e' : '#94a3b8';
                    dot.style.display = isOnline ? 'block' : 'none';
                }
                if (statusText) {
                    if (isOnline) {
                        statusText.innerHTML = '<span class="contact-online-badge"><span class="online-pulse-dot"></span>অনলাইন</span>';
                    } else {
                        statusText.innerHTML = '<span style="font-size: 11px; color: var(--fb-text-secondary);">অফলাইন</span>';
                    }
                }
            }
            if (activeChatUser && Number(activeChatUser.id) === targetId) {
                const dot = document.getElementById('messengerChatOnlineDot');
                const sub = document.getElementById('messengerChatSubtitle');
                if (dot) dot.style.background = isOnline ? '#22c55e' : '#94a3b8';
                if (sub) {
                    sub.innerHTML = isOnline 
                        ? '<span class="contact-online-badge" style="padding: 1px 7px;"><span class="online-pulse-dot"></span>সক্রিয় আছেন</span> · প্রোফাইল দেখুন ↗' 
                        : 'অফলাইন · প্রোফাইল দেখুন ↗';
                }
            }
        };

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

        function renderChatMessageHtml(m) {
            if (m.type === 'call') {
                return `
                    <div id="chatMsg_${m.id}" data-id="${m.id}" data-version="${m.version || 1}" style="text-align: center; margin: 8px auto; width: 100%;">
                        <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(24,119,242,0.1); color: var(--fb-primary, #1877f2); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                            ${escapeHtml(m.body || 'কল')}
                        </div>
                    </div>
                `;
            }

            const isMe = m.is_mine ?? (m.sender_id === currentUser?.id || m.user_id === currentUser?.id);
            const isDeleted = !!(m.deleted_at || m.is_deleted_for_everyone);
            const isEdited = !!(m.is_edited || m.edited_at);
            const time = m.created_at ? new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
            const senderAvatar = m.sender?.avatar_url || '/images/default-avatar.svg';
            const mediaList = m.media || m.attachments || [];

            // Voice note rendering
            let voiceHtml = '';
            if (!isDeleted) {
                const rawAudioUrl = m.metadata?.audio_url || mediaList.find(med => med.collection === 'voice' || med.mime_type?.includes('audio'))?.url;
                const audioTokenParam = currentToken ? `?token=${encodeURIComponent(currentToken)}` : '';
                const audioUrl = m.id ? `/api/v1/messages/${m.id}/voice${audioTokenParam}` : rawAudioUrl;
                if (m.type === 'voice' || rawAudioUrl) {
                    voiceHtml = `
                        <div style="margin: 4px 0;">
                            <audio controls src="${audioUrl}" style="max-width: 210px; height: 32px;" preload="metadata" onerror="if(!this.dataset.retry && ${m.id ? 'true' : 'false'}){this.dataset.retry='1';this.src='/messages/${m.id}/voice${audioTokenParam}';}"></audio>
                            <div style="font-size: 10px; opacity: 0.8; margin-top: 2px;">🎙️ ভয়েস বার্তা (${formatDuration(m.metadata?.duration || 0)})</div>
                        </div>
                    `;
                }
            }

            // Media attachments rendering
            let mediaAttachmentHtml = '';
            let hasImageMedia = false;
            if (!isDeleted && mediaList.length > 0) {
                mediaAttachmentHtml = mediaList.map(media => {
                    const isImg = (media.mime_type && media.mime_type.startsWith('image/')) ||
                                  /\.(jpe?g|png|webp|gif|avif)$/i.test(media.filename || media.name || media.original_path || '');
                    const isVid = (media.mime_type && media.mime_type.startsWith('video/')) ||
                                  /\.(mp4|webm|mov)$/i.test(media.filename || media.name || media.original_path || '');

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
                                     alt="${escapeHtml(fileName)}" 
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
                                <div style="font-weight: 600; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">${escapeHtml(fileName)}</div>
                                <div style="font-size: 10px; opacity: 0.7;">${media.size ? Math.round(media.size / 1024) + ' KB' : 'ডাউনলোড'}</div>
                            </div>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        </a>
                    `;
                }).join('');
            }

            // Check if m.body is merely the filename
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
                            <button type="button" onclick="openChatMessageEditModal(${m.id}, '${escapeHtml(bodyText).replace(/'/g, "\\'")}', ${m.version || 1})" title="এডিট করুন" style="background: none; border: none; padding: 2px 4px; cursor: pointer; color: var(--fb-text-secondary); border-radius: 4px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            </button>
                        ` : ''}
                        <div style="position: relative;">
                            <button type="button" onclick="toggleMsgActionsDropdown(${m.id}, event)" title="মুছুন বা অন্যান্য অপশন" style="background: none; border: none; padding: 2px 4px; cursor: pointer; color: var(--fb-text-secondary); border-radius: 4px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1.5"></circle><circle cx="12" cy="5" r="1.5"></circle><circle cx="12" cy="19" r="1.5"></circle></svg>
                            </button>
                            <div id="msgDropdown_${m.id}" class="jj-chat-dropdown-menu" style="display: none; ${isMe ? 'left: 0; right: auto;' : 'right: 0; left: auto;'}">
                                ${isMe && m.type !== 'voice' ? `
                                    <button type="button" class="jj-chat-dropdown-item" onclick="openChatMessageEditModal(${m.id}, '${escapeHtml(bodyText).replace(/'/g, "\\'")}', ${m.version || 1})">
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
                                ${showBody ? `<div class="chat-msg-body">${escapeHtml(bodyText)}</div>` : ''}
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
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
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

                // Remove empty placeholder if messages are present
                const emptyEl = container.querySelector('.jj-empty-chat-state');
                if (emptyEl) emptyEl.remove();

                const atBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;

                // Smart DOM Reconciliation: Check currently rendered messages to prevent unnecessary DOM wipes
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
                    container.innerHTML = messages.map(m => renderChatMessageHtml(m)).join('');
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
                            temp.innerHTML = renderChatMessageHtml(m).trim();
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
                        temp.innerHTML = renderChatMessageHtml(m).trim();
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
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
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

        /* ------------------------------------------------------------- */
        /* VOICE RECORDING (REAL BROWSER MEDIA RECORDER) */
        /* ------------------------------------------------------------- */
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

                // Show recording bar
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
                const file = new File([blob], `voice_${Date.now()}.${ext}`, { type: recordedType });

                const formData = new FormData();
                formData.append('audio', file);
                formData.append('duration', duration);

                showToast('🎙️ ভয়েস বার্তা আপলোড হচ্ছে...');

                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                    const headers = { 'Accept': 'application/json' };
                    if (currentToken) headers['Authorization'] = `Bearer ${currentToken}`;
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

        /* ------------------------------------------------------------- */
        /* CHAT ATTACHMENTS & EMOJIS */
        /* ------------------------------------------------------------- */
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
                                ${isImg && localPreviewUrl ? `<img src="${localPreviewUrl}" style="width: 100%; max-height: 240px; object-fit: cover; opacity: 0.7; filter: blur(1px); display: block;">` : `<div style="padding: 16px; font-size: 13px;">📎 ${escapeHtml(file.name)}</div>`}
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
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' },
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
                        headers: {
                            'Authorization': `Bearer ${currentToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            body: caption,
                            media_ids: [mediaId],
                            type: 'media'
                        })
                    });
                    const msgData = await msgRes.json();
                    if (msgData.success) {
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
            const parent = btn.parentNode;
            const img = parent?.parentNode?.querySelector('img');
            if (img) {
                parent.remove();
                img.style.display = 'block';
                img.src = mediaId ? `/api/v1/messages/attachments/${mediaId}/view?refresh=${Date.now()}` : (fallbackUrl + (fallbackUrl.includes('?') ? '&' : '?') + 'r=' + Date.now());
            }
        }

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

        let typingBroadcastTimer = null;
        function handleTypingBroadcast() {
            if (!activeChatConversationId) return;
            clearTimeout(typingBroadcastTimer);
            typingBroadcastTimer = setTimeout(() => {
                fetch('/api/v1/presence/typing', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ conversation_id: activeChatConversationId })
                }).catch(() => {});
            }, 600);
        }

        function startChatCall(type) {
            if (!activeChatConversationId) return;
            showToast(`${type === 'video' ? 'ভিডিও' : 'অডিও'} কল সংযোগ স্থাপন হচ্ছে... 📞`);
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

            // Pre-select active chat partner if known
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
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Accept': 'application/json'
                    }
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
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
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

        /* ------------------------------------------------------------- */
        /* UNREAD BADGES & COUNTERS (REALTIME ENGINE) */
        /* ------------------------------------------------------------- */
        async function fetchUnreadCounters() {
            if (!currentToken) return;

            let unreadNotif = 0;
            let unreadMsg = 0;
            let pendingReqCount = 0;

            // 1. Unread notifications
            try {
                const notifRes = await fetch('/api/v1/notifications/unread-count', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const notifJson = await notifRes.json();
                unreadNotif = notifJson.data?.count ?? notifJson.data?.unread_count ?? 0;
                ['notifBadge', 'mobileNavNotifBadge'].forEach(id => {
                    const badge = document.getElementById(id);
                    if (badge) {
                        if (unreadNotif > 0) {
                            badge.style.display = 'flex';
                            badge.innerText = unreadNotif > 99 ? '99+' : unreadNotif;
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                });
            } catch (e) {}

            // 2. Unread messages
            try {
                const msgRes = await fetch('/api/v1/messages/unread-count', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const msgJson = await msgRes.json();
                unreadMsg = msgJson.data?.count ?? msgJson.data?.unread_count ?? 0;
                ['topMessengerBadge', 'sidebarMsgBadge', 'mobileNavMessengerBadge'].forEach(id => {
                    const badge = document.getElementById(id);
                    if (badge) {
                        if (unreadMsg > 0) {
                            badge.style.display = 'flex';
                            badge.innerText = unreadMsg > 99 ? '99+' : unreadMsg;
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                });
            } catch (e) {}

            // 3. Friend requests count
            try {
                const friendRes = await fetch('/api/v1/friends/requests', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const friendJson = await friendRes.json();
                const reqs = Array.isArray(friendJson.data) ? friendJson.data : [];
                pendingReqCount = reqs.length;
                const friendBadge = document.getElementById('sidebarFriendBadge');
                if (friendBadge) {
                    if (reqs.length > 0) {
                        friendBadge.style.display = 'inline-flex';
                        friendBadge.innerText = reqs.length;
                    } else {
                        friendBadge.style.display = 'none';
                    }
                }
            } catch (e) {}

            // 4. Saved items count
            try {
                const saved = JSON.parse(localStorage.getItem('jugajug_saved_posts') || '[]');
                const savedBadge = document.getElementById('sidebarSavedBadge');
                if (savedBadge) {
                    if (saved.length > 0) {
                        savedBadge.style.display = 'inline-flex';
                        savedBadge.innerText = saved.length;
                    } else {
                        savedBadge.style.display = 'none';
                    }
                }
            } catch (e) {}

            // 5. Sync Mobile Bottom Navigation & Drawer Badges
            if (typeof window.updateMobileNavBadges === 'function') {
                window.updateMobileNavBadges({
                    notifications: unreadNotif,
                    messages: unreadMsg,
                    friends: pendingReqCount
                });
            }
        }

        // Periodic background poll for badges (every 25 seconds)
        setInterval(() => {
            if (currentToken) fetchUnreadCounters();
        }, 25000);

        /* ------------------------------------------------------------- */
        /* FRIENDS & REQUESTS (REAL BACKEND API) */
        /* ------------------------------------------------------------- */
        function openFriendsModal() {
            document.getElementById('friendsModal').style.display = 'flex';
            loadFriendRequestsTab();
        }
        function closeFriendsModal() {
            document.getElementById('friendsModal').style.display = 'none';
        }
        async function loadFriendRequestsTab() {
            const body = document.getElementById('friendsModalBody');
            body.innerHTML = '<div style="text-align: center; padding: 16px;">রিকোয়েস্ট লোড হচ্ছে...</div>';
            try {
                const res = await fetch('/api/v1/friends/requests', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                const reqs = Array.isArray(data.data) ? data.data : [];

                if (reqs.length === 0) {
                    body.innerHTML = '<div style="text-align: center; padding: 16px; color: var(--fb-text-secondary);">কোনো নতুন ফ্রেন্ড রিকোয়েস্ট নেই।</div>';
                    return;
                }

                body.innerHTML = reqs.map(r => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--fb-border);">
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <div class="avatar" style="width: 36px; height: 36px;">${(r.user?.name || 'ইউ').charAt(0)}</div>
                            <div>
                                <div style="font-weight: 700; font-size: 14px;">${escapeHtml(r.user?.name || 'ইউজার')}</div>
                                <div style="font-size: 12px; color: var(--fb-text-secondary);">@${escapeHtml(r.user?.username || '')}</div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button class="post-btn" style="background: var(--fb-primary); color: white; padding: 4px 10px; font-size: 12px;" onclick="acceptFriendReq(${r.user_id})">স্বীকার</button>
                            <button class="post-btn" style="padding: 4px 10px; font-size: 12px;" onclick="declineFriendReq(${r.user_id})">মুছুন</button>
                        </div>
                    </div>
                `).join('');
            } catch (err) {
                body.innerHTML = '<div style="color: red; text-align: center; padding: 16px;">লোড করতে সমস্যা হয়েছে।</div>';
            }
        }
        async function loadFriendsListTab() {
            const body = document.getElementById('friendsModalBody');
            body.innerHTML = '<div style="text-align: center; padding: 16px;">বন্ধুদের তালিকা লোড হচ্ছে...</div>';
            try {
                const res = await fetch('/api/v1/friends', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                const friends = Array.isArray(data.data) ? data.data : [];

                if (friends.length === 0) {
                    body.innerHTML = '<div style="text-align: center; padding: 16px; color: var(--fb-text-secondary);">আপনার ফ্রেন্ডলিস্ট এখনও ফাঁকা।</div>';
                    return;
                }

                body.innerHTML = friends.map(f => {
                    const friendUrl = window.getUserProfileUrl ? window.getUserProfileUrl(f) : `/u/${encodeURIComponent(f.username || f.id)}`;
                    return `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--fb-border);">
                        <a href="${friendUrl}" style="display: flex; gap: 8px; align-items: center; text-decoration: none; color: inherit; cursor: pointer;" title="${escapeHtml(f.name)}-এর প্রোফাইল দেখুন">
                            <img src="${f.profile?.avatar_url || '/images/default-avatar.svg'}" class="avatar" style="width: 36px; height: 36px; object-fit: cover; border-radius: 50%;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                            <div>
                                <div style="font-weight: 700; font-size: 14px;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${escapeHtml(f.name)}</div>
                                <div style="font-size: 12px; color: var(--fb-text-secondary);">@${escapeHtml(f.username)}</div>
                            </div>
                        </a>
                        <div style="display: flex; gap: 6px;">
                            <button class="post-btn" style="background: var(--fb-primary); color: white; padding: 4px 10px; font-size: 12px;" onclick="closeFriendsModal(); openDirectChatWithUser(${f.id}, '${escapeHtml(f.name)}', '${escapeHtml(f.username || '')}', '${f.profile?.avatar_url || ''}')">চ্যাট করুন</button>
                            <button class="post-btn" style="color: red; padding: 4px 8px; font-size: 12px;" onclick="unfriendUser(${f.id})">আনফ্রেন্ড</button>
                        </div>
                    </div>
                `}).join('');
            } catch (err) {
                body.innerHTML = '<div style="color: red; text-align: center; padding: 16px;">লোড করতে সমস্যা হয়েছে।</div>';
            }
        }
        async function acceptFriendReq(id) {
            try {
                const res = await fetch(`/api/v1/friends/${id}/accept`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast('ফ্রেন্ড রিকোয়েস্ট গৃহীত হয়েছে! 🤝');
                    loadFriendRequestsTab();
                    fetchFeedPosts();
                    fetchUnreadCounters();
                }
            } catch (e) {
                showToast('ত্রুটি: ' + e.message);
            }
        }
        async function declineFriendReq(id) {
            try {
                const res = await fetch(`/api/v1/friends/${id}/decline`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast('ফ্রেন্ড রিকোয়েস্ট বাতিল করা হয়েছে।');
                    loadFriendRequestsTab();
                    fetchUnreadCounters();
                }
            } catch (e) {
                showToast('ত্রুটি: ' + e.message);
            }
        }
        async function unfriendUser(id) {
            if (!confirm('সত্যিই আনফ্রেন্ড করতে চান?')) return;
            try {
                const res = await fetch(`/api/v1/friends/${id}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast('আনফ্রেন্ড সম্পন্ন হয়েছে।');
                    loadFriendsListTab();
                    fetchFeedPosts();
                    fetchUnreadCounters();
                }
            } catch (e) {
                showToast('ত্রুটি: ' + e.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* SEARCH & DISCOVERY (REAL HYBRID SEARCH) */
        /* ------------------------------------------------------------- */
        let searchTimer = null;
        document.getElementById('globalSearchInput')?.addEventListener('input', function(e) {
            clearTimeout(searchTimer);
            const q = e.target.value.trim();
            const dropdown = document.getElementById('searchResultsDropdown');
            if (!q) {
                dropdown.style.display = 'none';
                return;
            }
            searchTimer = setTimeout(async () => {
                try {
                    const headers = { 'Accept': 'application/json' };
                    if (currentToken) headers['Authorization'] = `Bearer ${currentToken}`;
                    const res = await fetch(`/api/v1/search?query=${encodeURIComponent(q)}`, { headers });
                    const data = await res.json();
                    const results = data.data || {};
                    const posts = results.posts || [];
                    const users = results.users || [];

                    let html = '';
                    if (users.length > 0) {
                        html += '<div style="padding: 6px 12px; font-weight: 700; font-size: 12px; color: #65676b; background: #f0f2f5;">ব্যবহারকারীগণ</div>';
                        users.forEach(u => {
                            const profileUrl = window.getUserProfileUrl ? window.getUserProfileUrl(u) : `/u/${encodeURIComponent(u.username || u.id)}`;
                            html += `<div style="padding: 8px 12px; display: flex; gap: 8px; align-items: center; cursor: pointer; border-bottom: 1px solid #f0f2f5;" onclick="document.getElementById('searchResultsDropdown').style.display='none'; window.location.href='${profileUrl}';">
                                <div class="avatar" style="width: 28px; height: 28px;">${(u.name || u.username || 'U').charAt(0)}</div>
                                <div><div style="font-weight: 700; font-size: 13px;">${escapeHtml(u.name || u.username)}</div><div style="font-size: 11px; color: #65676b;">@${escapeHtml(u.username || '')}</div></div>
                            </div>`;
                        });
                    }
                    if (posts.length > 0) {
                        html += '<div style="padding: 6px 12px; font-weight: 700; font-size: 12px; color: #65676b; background: #f0f2f5;">পোস্টসমূহ</div>';
                        posts.forEach(p => {
                            html += `<div style="padding: 8px 12px; font-size: 13px; cursor: pointer; border-bottom: 1px solid #f0f2f5;" onclick="document.getElementById('searchResultsDropdown').style.display='none'; showToast('পোস্ট লোড হয়েছে')">
                                ${p.content.substring(0, 60)}...
                            </div>`;
                        });
                    }
                    if (!users.length && !posts.length) {
                        html = '<div style="padding: 12px; text-align: center; color: #65676b; font-size: 13px;">কোনো ফলাফল পাওয়া যায়নি।</div>';
                    }
                    dropdown.innerHTML = html;
                    dropdown.style.display = 'block';
                } catch (err) {
                    console.error('Search error:', err);
                }
            }, 300);
        });

        // Close search dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('searchResultsDropdown');
            const searchInput = document.getElementById('globalSearchInput');
            if (dropdown && !dropdown.contains(e.target) && e.target !== searchInput) {
                dropdown.style.display = 'none';
            }
        });

        /* ------------------------------------------------------------- */
        /* NOTIFICATIONS */
        /* ------------------------------------------------------------- */
        async function fetchNotifications() {
            if (!currentToken) return;
            try {
                const res = await fetch('/api/v1/notifications', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                const notifs = Array.isArray(data.data) ? data.data : [];
                showToast(`আপনার ${notifs.length}টি নোটিফিকেশন রয়েছে।`);
            } catch (e) {}
        }

        /* ------------------------------------------------------------- */
        /* ADMIN COCKPIT (USERS & REPORTS DIRECTORY) */
        /* ------------------------------------------------------------- */
        async function loadAdminUsers() {
            const list = document.getElementById('adminUsersTableBody');
            if (!list) return;
            list.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 12px;">ইউজার লোড হচ্ছে...</td></tr>';
            try {
                const res = await fetch('/api/v1/admin/users', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                const users = Array.isArray(data.data) ? data.data : (data.data?.users || []);
                list.innerHTML = users.map(u => `
                    <tr style="border-bottom: 1px solid var(--fb-border);">
                        <td style="padding: 8px;"><b>${u.name}</b><br><small style="color: var(--fb-text-secondary);">@${u.username}</small></td>
                        <td style="padding: 8px;">${u.email}</td>
                        <td style="padding: 8px;"><span style="background: #e7f3ff; color: #1877f2; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700;">${(u.roles || []).map(r => r.name).join(', ') || 'USER'}</span></td>
                    </tr>
                `).join('');
            } catch (e) {
                list.innerHTML = '<tr><td colspan="3" style="color: red; text-align: center; padding: 12px;">ইউজার লিস্ট লোড করা সম্ভব হয়নি।</td></tr>';
            }
        }

        async function loadAdminReports() {
            const list = document.getElementById('adminReportsTableBody');
            if (!list) return;
            list.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 12px;">রিপোর্ট লোড হচ্ছে...</td></tr>';
            try {
                const res = await fetch('/api/v1/admin/reports', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                const reports = Array.isArray(data.data) ? data.data : [];
                if (reports.length === 0) {
                    list.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--fb-text-secondary); padding: 12px;">কোনো রিপোর্ট পেন্ডিং নেই। প্ল্যাটফর্ম সম্পূর্ণ নিরাপদ! ✨</td></tr>';
                    return;
                }
                list.innerHTML = reports.map(r => `
                    <tr style="border-bottom: 1px solid var(--fb-border);">
                        <td style="padding: 8px;">${r.reportable_type?.split('\\').pop()} #${r.reportable_id}</td>
                        <td style="padding: 8px;"><span style="color: red; font-weight: 700;">${r.reason}</span></td>
                        <td style="padding: 8px;">${r.status}</td>
                        <td style="padding: 8px; display: flex; gap: 4px;">
                            <button class="post-btn" style="padding: 2px 6px; font-size: 11px; background: #e6f4ea; color: #137333;" onclick="actionReport(${r.id}, 'actioned')">অ্যাকশন</button>
                            <button class="post-btn" style="padding: 2px 6px; font-size: 11px;" onclick="actionReport(${r.id}, 'dismissed')">বাতিল</button>
                        </td>
                    </tr>
                `).join('');
            } catch (e) {
                list.innerHTML = '<tr><td colspan="4" style="color: red; text-align: center; padding: 12px;">রিপোর্ট লোড করা সম্ভব হয়নি।</td></tr>';
            }
        }

        async function actionReport(id, status) {
            try {
                const res = await fetch(`/api/v1/admin/reports/${id}`, {
                    method: 'PUT',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ status })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('রিপোর্ট সফলভাবে আপডেট হয়েছে!');
                    loadAdminReports();
                }
            } catch (e) {
                showToast('ত্রুটি: ' + e.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* VIEW TOGGLE (USER DASHBOARD <-> ADMIN COCKPIT) */
        /* ------------------------------------------------------------- */
        function toggleCockpitView() {
            const userView = document.getElementById('userDashboardView');
            const cockpit = document.getElementById('cockpitSection');
            const text = document.getElementById('adminToggleText');

            if (cockpit.style.display === 'none') {
                userView.style.display = 'none';
                cockpit.style.display = 'flex';
                if (text) text.innerText = 'সোশ্যাল ফিড';
                fetchAdminMetrics();
                loadAdminUsers();
                loadAdminReports();
            } else {
                userView.style.display = 'grid';
                cockpit.style.display = 'none';
                if (text) text.innerText = 'অ্যাডমিন ককপিট';
            }
        }

        function showUserFeed() {
            document.getElementById('userDashboardView').style.display = 'grid';
            document.getElementById('cockpitSection').style.display = 'none';
            const text = document.getElementById('adminToggleText');
            if (text) text.innerText = 'অ্যাডমিন ককপিট';
        }

        async function fetchAdminMetrics() {
            try {
                const res = await fetch('/api/v1/admin/infrastructure', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (json.success && json.data) {
                    const m = json.data.monitoring || {};
                    if (m.system) {
                        document.getElementById('admCpu').innerText = m.system.cpu_load_1m || '0.35';
                        document.getElementById('admRam').innerText = (m.system.ram_usage_mb || 6) + ' MB';
                    }
                    if (m.database) {
                        document.getElementById('admDb').innerText = (m.database.latency_ms || 0.02) + ' ms';
                    }
                    if (m.queues) {
                        document.getElementById('admQueues').innerText = (m.queues.pending_jobs || 0) + ' Pending';
                    }
                }
            } catch (err) {}
        }

        async function triggerAdminAction(url, method, msg) {
            try {
                const res = await fetch(url, {
                    method: method,
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast(msg);
                    fetchAdminMetrics();
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        async function triggerAdminBackup() {
            try {
                const res = await fetch('/api/v1/admin/backups/run', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ type: 'full', disk: 'local' })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('এনক্রিপ্টেড ব্যাকআপ তৈরি সম্পন্ন! 💾');
                }
            } catch (err) {
                showToast('এরর: ' + err.message);
            }
        }

        function toggleAIChat() {
            const modal = document.getElementById('aiChatModal');
            if (modal.style.display === 'none' || !modal.style.display) {
                modal.style.display = 'flex';
                document.getElementById('aiInputText').focus();
            } else {
                modal.style.display = 'none';
            }
        }

        async function sendAIMessage() {
            const input = document.getElementById('aiInputText');
            const msg = input.value.trim();
            if (!msg) return;

            const chatBox = document.getElementById('aiChatMessages');
            
            // Add user message
            const userMsgDiv = document.createElement('div');
            userMsgDiv.style.cssText = 'background: #1877f2; color: white; padding: 8px 12px; border-radius: 12px 12px 0 12px; align-self: flex-end; max-width: 85%;';
            userMsgDiv.innerText = msg;
            chatBox.appendChild(userMsgDiv);
            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;

            // Typing indicator
            const typingDiv = document.createElement('div');
            typingDiv.style.cssText = 'background: #f0f2f5; padding: 8px 12px; border-radius: 12px 12px 12px 0; max-width: 85%; font-style: italic; color: #65676b;';
            typingDiv.innerText = 'Bondhoo AI লিখছে...';
            chatBox.appendChild(typingDiv);
            chatBox.scrollTop = chatBox.scrollHeight;

            try {
                const res = await fetch('/api/v2/ai/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ message: msg })
                });
                const data = await res.json();
                chatBox.removeChild(typingDiv);

                const botMsgDiv = document.createElement('div');
                botMsgDiv.style.cssText = 'background: #f0f2f5; padding: 8px 12px; border-radius: 12px 12px 12px 0; max-width: 85%;';
                botMsgDiv.innerText = data.data?.reply || 'দুঃখিত, কোনো উত্তর পাওয়া যায়নি।';
                chatBox.appendChild(botMsgDiv);
                chatBox.scrollTop = chatBox.scrollHeight;
            } catch (err) {
                if (typingDiv.parentNode) chatBox.removeChild(typingDiv);
                showToast('এআই রেসপন্স ত্রুটি: ' + err.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* MAIN TABS SWITCHER (FEED / WATCH / MARKETPLACE / GROUPS) */
        /* ------------------------------------------------------------- */
        function switchMainTab(tab) {
            document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.mobile-subnav-item').forEach(t => t.classList.remove('active'));
            const activeTabBtn = document.getElementById(`tab-${tab}`);
            if (activeTabBtn) activeTabBtn.classList.add('active');
            const activeSubnavBtn = document.getElementById(`subnav-${tab}`);
            if (activeSubnavBtn) activeSubnavBtn.classList.add('active');

            // Make sure user view is visible
            document.getElementById('userDashboardView').style.display = 'grid';
            document.getElementById('cockpitSection').style.display = 'none';
            const toggleText = document.getElementById('adminToggleText');
            if (toggleText) toggleText.innerText = 'অ্যাডমিন ককপিট';

            // Hide all tab sections
            document.getElementById('feedTabSection').style.display = 'none';
            document.getElementById('watchTabSection').style.display = 'none';
            document.getElementById('marketplaceTabSection').style.display = 'none';
            document.getElementById('groupsTabSection').style.display = 'none';

            if (tab === 'feed') {
                document.getElementById('feedTabSection').style.display = 'flex';
                fetchFeedPosts();
            } else if (tab === 'watch') {
                document.getElementById('watchTabSection').style.display = 'flex';
                loadLiveStreams();
            } else if (tab === 'marketplace') {
                document.getElementById('marketplaceTabSection').style.display = 'flex';
                loadMarketplace();
            } else if (tab === 'groups') {
                document.getElementById('groupsTabSection').style.display = 'flex';
                loadGroups();
            }
        }

        /* ------------------------------------------------------------- */
        /* NOTIFICATIONS & HEADER POPOVERS */
        /* ------------------------------------------------------------- */
        async function toggleNotificationsDropdown() {
            const dropdown = document.getElementById('notificationsDropdown');
            const isShown = dropdown.style.display === 'block';
            dropdown.style.display = isShown ? 'none' : 'block';
            if (!isShown) {
                await loadNotifications();
            }
        }

        async function loadNotifications() {
            const list = document.getElementById('notificationsList');
            const badge = document.getElementById('notifBadge');
            if (!currentToken) return;

            try {
                const res = await fetch('/api/v1/notifications', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                const notifs = Array.isArray(data.data) ? data.data : [];

                if (notifs.length > 0) {
                    badge.style.display = 'flex';
                    badge.innerText = notifs.length;
                    list.innerHTML = notifs.map(n => `
                        <div style="padding: 10px 12px; border-bottom: 1px solid var(--fb-border); font-size: 13px; display: flex; gap: 8px; align-items: center; background: ${n.read_at ? 'transparent' : '#e7f3ff'}; border-radius: 8px;">
                            <div class="avatar" style="width: 28px; height: 28px; font-size: 12px;">🔔</div>
                            <div style="flex: 1;">
                                <div>${n.data?.message || n.message || 'নতুন নোটিফিকেশন'}</div>
                                <div style="font-size: 11px; color: var(--fb-text-secondary);">${formatTimeAgo(n.created_at)}</div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    badge.style.display = 'none';
                    list.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); padding: 20px; font-size: 13px;">কোনো নতুন নোটিফিকেশন নেই। আপনি সম্পূর্ণ আপ-টু-ডেট! ✨</div>';
                }
            } catch (e) {
                list.innerHTML = '<div style="color: red; padding: 16px; text-align: center;">নোটিফিকেশন লোড করা যায়নি।</div>';
            }
        }

        async function markAllNotificationsRead() {
            if (!currentToken) return;
            try {
                await fetch('/api/v1/notifications/read-all', {
                    method: 'PUT',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                document.getElementById('notifBadge').style.display = 'none';
                showToast('সবগুলো নোটিফিকেশন পঠিত হিসেবে চিহ্নিত হয়েছে।');
                await loadNotifications();
            } catch (e) {}
        }

        function toggleUserDropdown() {
            const dropdown = document.getElementById('userMenuDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            if (currentUser) {
                document.getElementById('dropdownUserName').innerText = currentUser.name || 'ইউজার';
                document.getElementById('dropdownUserAvatar').innerText = (currentUser.name || 'ইউ').charAt(0);
            }
        }

        let isDarkMode = false;
        function toggleDarkMode() {
            isDarkMode = !isDarkMode;
            const root = document.documentElement;
            const text = document.getElementById('themeToggleText');
            if (isDarkMode) {
                root.style.setProperty('--fb-bg', '#18191a');
                root.style.setProperty('--fb-card', '#242526');
                root.style.setProperty('--fb-text-primary', '#e4e6eb');
                root.style.setProperty('--fb-text-secondary', '#b0b3b8');
                root.style.setProperty('--fb-border', '#393a3b');
                root.style.setProperty('--fb-hover', '#3a3b3c');
                root.style.setProperty('--fb-btn-bg', '#3a3b3c');
                text.innerText = '☀️ লাইট মোড অন করুন';
                showToast('ডার্ক মোড সক্রিয় করা হয়েছে!');
            } else {
                root.style.setProperty('--fb-bg', '#f0f2f5');
                root.style.setProperty('--fb-card', '#ffffff');
                root.style.setProperty('--fb-text-primary', '#050505');
                root.style.setProperty('--fb-text-secondary', '#65676b');
                root.style.setProperty('--fb-border', '#e4e6eb');
                root.style.setProperty('--fb-hover', '#f2f2f2');
                root.style.setProperty('--fb-btn-bg', '#e4e6eb');
                text.innerText = '🌙 ডার্ক মোড অন করুন';
                showToast('লাইট মোড সক্রিয় করা হয়েছে!');
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const notifDropdown = document.getElementById('notificationsDropdown');
            const userDropdown = document.getElementById('userMenuDropdown');
            const messengerDropdown = document.getElementById('topMessengerDropdown');
            if (notifDropdown && !notifDropdown.contains(e.target) && !e.target.closest('button[title="নোটিফিকেশন"]')) {
                notifDropdown.style.display = 'none';
            }
            if (userDropdown && !userDropdown.contains(e.target) && !e.target.closest('#navUserAvatar')) {
                userDropdown.style.display = 'none';
            }
            if (messengerDropdown && !messengerDropdown.contains(e.target) && !e.target.closest('#topMessengerBtn')) {
                messengerDropdown.style.display = 'none';
            }
        });

        /* ------------------------------------------------------------- */
        /* MEMORIES (YOUR HISTORICAL POSTS) */
        /* ------------------------------------------------------------- */
        function openMemoriesModal() {
            document.getElementById('memoriesModal').style.display = 'flex';
            loadMemories();
        }
        function closeMemoriesModal() {
            document.getElementById('memoriesModal').style.display = 'none';
        }
        async function loadMemories() {
            const container = document.getElementById('memoriesListContainer');
            container.innerHTML = '<div style="text-align: center; padding: 20px;">স্মৃতিচারণ পোস্ট লোড হচ্ছে...</div>';
            if (!currentUser) return;
            try {
                const res = await fetch(`/api/v1/users/${currentUser.username || 'user'}/posts`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                const posts = Array.isArray(data.data) ? data.data : (data.data?.posts || []);

                if (posts.length === 0) {
                    container.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); padding: 20px;">আজ আপনার কোনো স্মৃতিচারণ পোস্ট নেই। নতুন পোস্ট শেয়ার করুন!</div>';
                    return;
                }

                container.innerHTML = posts.map(p => `
                    <div class="fb-card" style="padding: 14px;">
                        <div style="font-size: 12px; color: var(--fb-primary); font-weight: 700; margin-bottom: 6px;">📅 ${new Date(p.created_at).toLocaleDateString('bn-BD', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                        <div style="font-size: 14px; line-height: 1.5;">${p.content}</div>
                        <div style="margin-top: 8px; font-size: 12px; color: var(--fb-text-secondary);">👍 ${p.reactions_count || 0} লাইক · 💬 ${p.comments_count || 0} মন্তব্য</div>
                    </div>
                `).join('');
            } catch (e) {
                container.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">লোড করা সম্ভব হয়নি।</div>';
            }
        }

        /* ------------------------------------------------------------- */
        /* SAVED POSTS (LOCAL STORAGE + BACKEND BOOKMARKS) */
        /* ------------------------------------------------------------- */
        function savePost(id, contentEncoded) {
            const content = decodeURIComponent(contentEncoded);
            let saved = JSON.parse(localStorage.getItem('jugajug_saved_posts') || '[]');
            const exists = saved.find(item => item.id === id);
            if (exists) {
                showToast('পোস্টটি ইতোমধ্যে সংরক্ষিত তালিকায় রয়েছে!');
                return;
            }
            saved.push({ id, content, saved_at: new Date().toISOString() });
            localStorage.setItem('jugajug_saved_posts', JSON.stringify(saved));
            showToast('পোস্ট সফলভাবে সংরক্ষিত তালিকায় সেভ হয়েছে! 🔖');
        }

        function openSavedPostsModal() {
            document.getElementById('savedPostsModal').style.display = 'flex';
            loadSavedPosts();
        }
        function closeSavedPostsModal() {
            document.getElementById('savedPostsModal').style.display = 'none';
        }
        function loadSavedPosts() {
            const container = document.getElementById('savedPostsContainer');
            const saved = JSON.parse(localStorage.getItem('jugajug_saved_posts') || '[]');
            if (saved.length === 0) {
                container.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); padding: 20px;">আপনার সংরক্ষিত কোনো পোস্ট নেই। ফিডের যেকোনো পোস্ট থেকে "সেভ" বাটনে ক্লিক করে সংরক্ষণ করুন।</div>';
                return;
            }
            container.innerHTML = saved.map(item => `
                <div class="fb-card" style="padding: 12px 14px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="flex: 1; padding-right: 12px;">
                        <div style="font-size: 14px; font-weight: 500;">${item.content}</div>
                        <div style="font-size: 11px; color: var(--fb-text-secondary); margin-top: 4px;">সংরক্ষণ সময়: ${formatTimeAgo(item.saved_at)}</div>
                    </div>
                    <button class="post-btn" style="color: red; padding: 4px 8px; font-size: 12px;" onclick="removeSavedPost(${item.id})">মুছুন</button>
                </div>
            `).join('');
        }
        function removeSavedPost(id) {
            let saved = JSON.parse(localStorage.getItem('jugajug_saved_posts') || '[]');
            saved = saved.filter(i => i.id !== id);
            localStorage.setItem('jugajug_saved_posts', JSON.stringify(saved));
            showToast('সংরক্ষিত তালিকা থেকে সরানো হয়েছে।');
            loadSavedPosts();
        }

        /* ------------------------------------------------------------- */
        /* WATCH & LIVE STREAMING (REALTIME PRODUCTION IMPLEMENTATION) */
        /* ------------------------------------------------------------- */
        let dashboardLiveMediaStream = null;

        function openStartLiveFlow() {
            const modal = document.getElementById('createLiveModal');
            if (!modal) return;
            modal.style.display = 'flex';

            const titleInput = document.getElementById('liveStreamTitleInput');
            if (titleInput && !titleInput.value) {
                const authorName = currentUser?.name || 'আমার';
                titleInput.value = `${authorName}-এর লাইভ সেশন`;
            }

            requestLiveMediaPermissions();
        }

        async function acquireLiveUserMediaStream() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('আপনার ব্রাউজার ক্যামেরা বা মাইক্রোফোন সমর্থন করে না।');
            }

            // Tier 1: Ideal 720p/HD user-facing camera + noise cancelling audio
            try {
                return await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: 'user' },
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    },
                    audio: {
                        echoCancellation: { ideal: true },
                        noiseSuppression: { ideal: true },
                        autoGainControl: { ideal: true }
                    }
                });
            } catch (t1Err) {
                console.warn('Live setup: Tier 1 media capture failed, falling back to unconstrained video & audio...', t1Err);
            }

            // Tier 2: Unconstrained video + audio
            try {
                return await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true
                });
            } catch (t2Err) {
                console.warn('Live setup: Tier 2 media capture failed, falling back to video-only...', t2Err);
            }

            // Tier 3: Video only (if mic is absent, blocked, or in use by another app)
            try {
                return await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: false
                });
            } catch (t3Err) {
                console.warn('Live setup: Tier 3 video-only capture failed, falling back to audio-only...', t3Err);
            }

            // Tier 4: Audio only (if camera is missing, disabled, or in use)
            return await navigator.mediaDevices.getUserMedia({
                video: false,
                audio: true
            });
        }

        async function requestLiveMediaPermissions() {
            const videoEl = document.getElementById('dashboardLiveCamPreview');
            const loadingBox = document.getElementById('liveModalPermLoading');
            const errorBox = document.getElementById('liveModalPermError');
            const devicePills = document.getElementById('liveModalDevicePills');
            const submitBtn = document.getElementById('startLiveSubmitBtn');

            if (loadingBox) loadingBox.style.display = 'block';
            if (errorBox) errorBox.style.display = 'none';
            if (videoEl) videoEl.style.display = 'none';
            if (devicePills) devicePills.style.display = 'none';
            if (submitBtn) submitBtn.disabled = true;

            try {
                if (dashboardLiveMediaStream) {
                    dashboardLiveMediaStream.getTracks().forEach(t => t.stop());
                    dashboardLiveMediaStream = null;
                }

                dashboardLiveMediaStream = await acquireLiveUserMediaStream();

                const videoTracks = dashboardLiveMediaStream ? dashboardLiveMediaStream.getVideoTracks() : [];
                const audioTracks = dashboardLiveMediaStream ? dashboardLiveMediaStream.getAudioTracks() : [];

                if (videoTracks.length > 0 && videoEl) {
                    videoEl.srcObject = dashboardLiveMediaStream;
                    videoEl.style.display = 'block';
                }

                if (devicePills) {
                    const camBadge = videoTracks.length > 0
                        ? '<span style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); color: #10b981; font-size: 11px; padding: 3px 8px; border-radius: 4px; font-weight: 700;">✓ ক্যামেরা সক্রিয়</span>'
                        : '<span style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); color: #f59e0b; font-size: 11px; padding: 3px 8px; border-radius: 4px; font-weight: 700;">⚠️ ক্যামেরা বন্ধ</span>';
                    const micBadge = audioTracks.length > 0
                        ? '<span style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); color: #10b981; font-size: 11px; padding: 3px 8px; border-radius: 4px; font-weight: 700;">✓ মাইক সক্রিয়</span>'
                        : '<span style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); color: #f59e0b; font-size: 11px; padding: 3px 8px; border-radius: 4px; font-weight: 700;">⚠️ মাইক বন্ধ</span>';
                    devicePills.innerHTML = camBadge + micBadge;
                    devicePills.style.display = 'flex';
                }

                if (loadingBox) loadingBox.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            } catch (err) {
                console.warn('Camera/Mic permission error:', err);
                if (loadingBox) loadingBox.style.display = 'none';
                if (errorBox) {
                    errorBox.style.display = 'block';
                    const msgEl = errorBox.querySelector('div:nth-child(3)');
                    if (msgEl) {
                        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                            msgEl.innerText = 'ব্রাউজারে ক্যামেরা বা মাইক্রোফোনের অনুমতি ব্লক করা রয়েছে। সাইট সেটিংসে অনুমতি দিয়ে রিফ্রেশ করুন।';
                        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                            msgEl.innerText = 'কোনো ক্যামেরা বা মাইক্রোফোন হার্ডওয়্যার পাওয়া যায়নি। ডিভাইস সংযুক্ত করুন।';
                        } else if (err.name === 'OverconstrainedError' || (err.message && err.message.toLowerCase().includes('constraint'))) {
                            msgEl.innerText = 'ক্যামেরা বা মাইক্রোফোনের সেটিংস মেলেনি। অনুগ্রহ করে ডিভাইসটি পুনরায় সংযোগ করুন।';
                        } else {
                            msgEl.innerText = 'মিডিয়া ডিভাইসে প্রবেশের সময় ত্রুটি ঘটেছে: ' + err.message;
                        }
                    }
                }
                if (submitBtn) submitBtn.disabled = true;
            }
        }

        function closeCreateLiveModal() {
            if (dashboardLiveMediaStream) {
                dashboardLiveMediaStream.getTracks().forEach(t => t.stop());
                dashboardLiveMediaStream = null;
            }
            const modal = document.getElementById('createLiveModal');
            if (modal) modal.style.display = 'none';
        }

        async function submitStartLiveStream() {
            const titleInput = document.getElementById('liveStreamTitleInput');
            const privacySelect = document.getElementById('liveStreamPrivacySelect');
            const submitBtn = document.getElementById('startLiveSubmitBtn');

            const title = (titleInput?.value || '').trim();
            if (!title) {
                alert('অনুগ্রহ করে লাইভের জন্য একটি শিরোনাম লিখুন।');
                if (titleInput) titleInput.focus();
                return;
            }

            const privacy = privacySelect ? privacySelect.value : 'public';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>⏳ লাইভ সেশন প্রস্তুত হচ্ছে...</span>';
            }

            try {
                const commentsEnabled = document.getElementById('modalCommentsEnabled')?.checked ?? true;
                const reactionsEnabled = document.getElementById('modalReactionsEnabled')?.checked ?? true;

                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const headers = {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                };
                const authToken = localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || (typeof currentToken !== 'undefined' ? currentToken : '');
                if (authToken) {
                    headers['Authorization'] = `Bearer ${authToken}`;
                }

                // Create channel in backend
                const createRes = await fetch('/api/v2/live/streams', {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({
                        title: title,
                        privacy: privacy,
                        recording_enabled: true,
                        comments_enabled: commentsEnabled,
                        reactions_enabled: reactionsEnabled
                    })
                });

                const createData = await createRes.json();
                if (createData.status !== 'success' || !createData.data) {
                    throw new Error(createData.message || 'লাইভ সেশন তৈরি করতে ব্যর্থ হয়েছে।');
                }

                const streamId = createData.data.id;

                // Mark stream as started
                await fetch(`/api/v2/live/streams/${streamId}/start`, {
                    method: 'POST',
                    headers: headers
                });

                // Release preview stream tracks before navigating to Studio
                if (dashboardLiveMediaStream) {
                    dashboardLiveMediaStream.getTracks().forEach(t => t.stop());
                    dashboardLiveMediaStream = null;
                }

                showToast('লাইভ সেশন তৈরি হয়েছে! স্টুডিওতে রিডাইরেক্ট করা হচ্ছে... 🔴');
                window.location.href = '{{ route("live.studio") }}';
            } catch (err) {
                alert('লাইভ শুরু করতে ত্রুটি: ' + err.message);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span>🔴 সরাসরি লাইভ শুরু করুন (Go Live)</span>';
                }
            }
        }

        async function loadLiveStreams() {
            const grid = document.getElementById('liveStreamsGrid');
            const myBanner = document.getElementById('myActiveLiveBanner');
            const myInfo = document.getElementById('myActiveLiveInfo');
            if (!grid) return;

            grid.innerHTML = '<div style="padding: 24px; text-align: center; color: var(--fb-text-secondary); grid-column: 1/-1;">চলমান লাইভ লোড হচ্ছে...</div>';

            try {
                const res = await fetch('/api/v2/live/streams?status=live', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                const streams = Array.isArray(data.data) ? data.data : [];

                // Check if current user is broadcasting
                const myStream = currentUser ? streams.find(s => s.user_id === currentUser.id) : null;
                if (myBanner) {
                    if (myStream) {
                        myBanner.style.display = 'flex';
                        if (myInfo) myInfo.innerText = `👁️ ${myStream.viewers_count || 0} জন দর্শক সরাসরি যুক্ত আছেন`;
                    } else {
                        myBanner.style.display = 'none';
                    }
                }

                if (streams.length === 0) {
                    grid.innerHTML = `
                        <div class="fb-card" style="padding: 44px 20px; text-align: center; color: var(--fb-text-secondary); grid-column: 1/-1; border-radius: 12px; border: 1px dashed var(--fb-border);">
                            <div style="font-size: 48px; margin-bottom: 12px;">📡</div>
                            <h3 style="font-size: 17px; font-weight: 700; color: var(--fb-text-primary); margin-bottom: 6px;">বর্তমানে কোনো লাইভ সম্প্রচার চালু নেই</h3>
                            <p style="font-size: 13px; max-width: 440px; margin: 0 auto 16px; line-height: 1.5;">আপনার বন্ধু ও দর্শকদের সাথে সরাসরি মুহূর্ত শেয়ার করতে নতুন লাইভ সম্প্রচার শুরু করুন।</p>
                            <button type="button" class="post-btn" style="background: #ef4444; color: white; padding: 10px 24px; border-radius: 20px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;" onclick="openStartLiveFlow()">
                                <span>🔴</span>
                                <span>গো লাইভ শুরু করুন (Go Live)</span>
                            </button>
                        </div>
                    `;
                    return;
                }

                grid.innerHTML = streams.map(s => `
                    <div class="fb-card" style="border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; border: 1px solid var(--fb-border); box-shadow: 0 4px 14px rgba(0,0,0,0.06);" onclick="window.location.href='/live/${s.id}'">
                        <div style="height: 165px; background: #080c16; display: flex; align-items: center; justify-content: center; color: white; position: relative;">
                            <span style="position: absolute; top: 12px; left: 12px; background: #ef4444; color: white; padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 800; display: flex; align-items: center; gap: 5px; box-shadow: 0 2px 8px rgba(0,0,0,0.4); letter-spacing: 0.5px;">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: white; animation: pulse 1s infinite;"></span>
                                LIVE
                            </span>
                            <span style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.7); backdrop-filter: blur(6px); color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>${s.viewers_count || 0}</span>
                            </span>
                            <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: center; color: white;">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                            </div>
                        </div>
                        <div style="padding: 14px; display: flex; flex-direction: column; gap: 10px; flex: 1;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="${s.user?.profile?.avatar_url || '/images/default-avatar.svg'}"
                                     style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid #ef4444;"
                                     onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                <div style="overflow: hidden; flex: 1;">
                                    <div style="font-weight: 700; font-size: 14px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; color: var(--fb-text-primary);">${escapeHtml(s.title || 'লাইভ ভিডিও')}</div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary);">${escapeHtml(s.user?.name || 'লাইভ ব্রডকাস্টার')}</div>
                                </div>
                            </div>
                            <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 11px; color: var(--fb-text-secondary);">সরাসরি চলছে</span>
                                <span style="color: #ef4444; font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                    <span>দেখুন</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                                </span>
                            </div>
                        </div>
                    </div>
                `).join('');
            } catch (e) {
                console.warn('Load live streams error:', e);
                grid.innerHTML = '<div style="color: #ef4444; padding: 20px; text-align: center; grid-column: 1/-1;">লাইভ সম্প্রচার লোড করতে ব্যর্থ হয়েছে।</div>';
            }
        }

        /* ------------------------------------------------------------- */
        /* MARKETPLACE & ESCROW CHECKOUT (REAL API) */
        /* ------------------------------------------------------------- */
        let marketplaceCategories = [];
        let allMarketplaceProducts = [];
        let activeCategoryId = null;

        async function loadMarketplace() {
            await loadMarketplaceCategories();
            await loadMarketplaceProducts();
        }

        async function loadMarketplaceCategories() {
            const pillsContainer = document.getElementById('marketplaceCategoriesPills');
            const selectContainer = document.getElementById('prodCategorySelect');
            try {
                const res = await fetch('/api/v2/marketplace/categories', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                marketplaceCategories = data.data || [];

                pillsContainer.innerHTML = `
                    <button class="post-btn" style="background: ${activeCategoryId === null ? 'var(--fb-primary)' : 'var(--fb-btn-bg)'}; color: ${activeCategoryId === null ? 'white' : 'var(--fb-text-primary)'}; border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 700;" onclick="filterByCategory(null)">সব পণ্য</button>
                ` + marketplaceCategories.map(c => `
                    <button class="post-btn" style="background: ${activeCategoryId === c.id ? 'var(--fb-primary)' : 'var(--fb-btn-bg)'}; color: ${activeCategoryId === c.id ? 'white' : 'var(--fb-text-primary)'}; border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 700; white-space: nowrap;" onclick="filterByCategory(${c.id})">${c.icon || '🛍️'} ${c.name}</button>
                `).join('');

                if (selectContainer) {
                    selectContainer.innerHTML = marketplaceCategories.map(c => `
                        <option value="${c.id}">${c.name}</option>
                    `).join('');
                }
            } catch (e) {}
        }

        async function loadMarketplaceProducts() {
            const grid = document.getElementById('marketplaceProductsGrid');
            grid.innerHTML = '<div style="padding: 16px; grid-column: 1/-1;">পণ্য লোড হচ্ছে...</div>';
            try {
                let url = '/api/v2/marketplace/products';
                if (activeCategoryId) url += `?category_id=${activeCategoryId}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                allMarketplaceProducts = data.data?.data || (Array.isArray(data.data) ? data.data : []);

                renderMarketplaceProducts(allMarketplaceProducts);
            } catch (e) {
                grid.innerHTML = '<div style="color: red; padding: 16px; grid-column: 1/-1;">মার্কেটপ্লেস লোড করা সম্ভব হয়নি।</div>';
            }
        }

        function filterByCategory(catId) {
            activeCategoryId = catId;
            loadMarketplace();
        }

        function filterMarketplaceProducts() {
            const q = document.getElementById('marketplaceSearchInput').value.toLowerCase().trim();
            const filtered = allMarketplaceProducts.filter(p => p.title.toLowerCase().includes(q) || (p.description && p.description.toLowerCase().includes(q)));
            renderMarketplaceProducts(filtered);
        }

        function renderMarketplaceProducts(products) {
            const grid = document.getElementById('marketplaceProductsGrid');
            if (products.length === 0) {
                grid.innerHTML = '<div class="fb-card" style="padding: 24px; text-align: center; color: var(--fb-text-secondary); grid-column: 1/-1;">কোনো পণ্য পাওয়া যায়নি। প্রথম লিস্টিংটি আপনি যোগ করুন!</div>';
                return;
            }

            grid.innerHTML = products.map(p => `
                <div class="fb-card" style="overflow: hidden; display: flex; flex-direction: column;">
                    <div style="height: 150px; background: #e4e6eb; display: flex; align-items: center; justify-content: center; font-size: 40px; color: #888;">
                        ${p.category?.icon || '🛍️'}
                    </div>
                    <div style="padding: 12px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="font-size: 17px; font-weight: 800; color: var(--fb-primary);">৳ ${Number(p.price).toLocaleString('bn-BD')}</div>
                            <div style="font-weight: 700; font-size: 14px; margin-top: 2px;">${p.title}</div>
                            <div style="font-size: 12px; color: var(--fb-text-secondary); margin-top: 4px;">📍 ${p.location || 'ঢাকা'} · ${p.condition === 'new' ? 'নতুন' : 'ব্যবহৃত'}</div>
                        </div>
                        <button class="modal-btn-submit" style="height: 36px; font-size: 13px; margin-top: 10px;" onclick="openOrderProductModal(${p.id}, '${encodeURIComponent(p.title)}', ${p.price})">🛒 অর্ডার করুন (Escrow)</button>
                    </div>
                </div>
            `).join('');
        }

        function openCreateProductModal() {
            document.getElementById('createProductModal').style.display = 'flex';
        }
        function closeCreateProductModal() {
            document.getElementById('createProductModal').style.display = 'none';
        }
        async function submitCreateProduct() {
            const title = document.getElementById('prodTitleInput').value.trim();
            const category_id = document.getElementById('prodCategorySelect').value;
            const price = parseFloat(document.getElementById('prodPriceInput').value);
            const condition = document.getElementById('prodConditionSelect').value;
            const location = document.getElementById('prodLocationInput').value.trim();
            const description = document.getElementById('prodDescInput').value.trim();

            if (!title || !price || !category_id) {
                alert('দয়া করে শিরোনাম, মূল্য এবং ক্যাটাগরি পূরণ করুন।');
                return;
            }

            try {
                const res = await fetch('/api/v2/marketplace/products', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ category_id, title, price, condition, location, description, currency: 'BDT' })
                });
                const data = await res.json();
                if (data.status === 'success' || data.data) {
                    closeCreateProductModal();
                    showToast('মার্কেটপ্লেস লিস্টিং সফলভাবে পোস্ট করা হয়েছে! 🛍️');
                    loadMarketplaceProducts();
                } else {
                    showToast(data.message || 'লিস্টিং পোস্ট করতে ব্যর্থ হয়েছে।');
                }
            } catch (e) {
                showToast('ত্রুটি: ' + e.message);
            }
        }

        function openOrderProductModal(id, titleEncoded, price) {
            const title = decodeURIComponent(titleEncoded);
            const body = document.getElementById('orderModalBody');
            body.innerHTML = `
                <div style="border-bottom: 1px solid var(--fb-border); padding-bottom: 12px; margin-bottom: 12px;">
                    <div style="font-weight: 800; font-size: 16px;">${title}</div>
                    <div style="font-size: 18px; font-weight: 800; color: var(--fb-primary); margin-top: 4px;">মোট প্রদেয়: ৳ ${Number(price).toLocaleString('bn-BD')}</div>
                </div>
                <label style="font-size: 13px; font-weight: 700;">ডেলিভারি ঠিকানা:</label>
                <input type="text" id="orderAddressInput" class="search-input" style="height: 40px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 10px; margin: 4px 0 12px;" value="বাড়ি #১২, রোড #৫, ধানমন্ডি, ঢাকা">
                
                <label style="font-size: 13px; font-weight: 700;">পেমেন্ট গেটওয়ে নির্বাচন করুন:</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 6px 0 16px;">
                    <label style="border: 1px solid var(--fb-border); padding: 10px; border-radius: 8px; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="orderGateway" value="bkash" checked> <b>bKash</b>
                    </label>
                    <label style="border: 1px solid var(--fb-border); padding: 10px; border-radius: 8px; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="orderGateway" value="nagad"> <b>Nagad</b>
                    </label>
                </div>
                <div style="background: #e6f4ea; border: 1px solid #137333; color: #137333; padding: 10px; border-radius: 8px; font-size: 12px; margin-bottom: 14px;">
                    🔒 <b>এসক্রো প্রটেকশন সক্রিয়:</b> আপনার টাকা Bondhoo এসক্রো অ্যাকাউন্টে নিরাপদে সংরক্ষিত থাকবে। পণ্য বুঝে পাওয়ার পরই বিক্রেতা টাকা পাবেন।
                </div>
                <button class="modal-btn-submit" onclick="submitOrderCheckout(${id})">নিরাপদে পেমেন্ট ও অর্ডার নিশ্চিত করুন 💳</button>
            `;
            document.getElementById('orderProductModal').style.display = 'flex';
        }
        function closeOrderProductModal() {
            document.getElementById('orderProductModal').style.display = 'none';
        }

        async function submitOrderCheckout(productId) {
            const address = document.getElementById('orderAddressInput').value.trim();
            const gateway = document.querySelector('input[name="orderGateway"]:checked')?.value || 'bkash';
            if (!address) {
                alert('ডেলিভারি ঠিকানা দিন।');
                return;
            }

            try {
                // 1. Place order
                const res = await fetch(`/api/v2/marketplace/products/${productId}/order`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ shipping_address: address, payment_method: gateway })
                });
                const orderData = await res.json();
                if (!orderData.data || !orderData.data.id) {
                    showToast('অর্ডার তৈরি ব্যর্থ হয়েছে।');
                    return;
                }
                const orderId = orderData.data.id;

                // 2. Checkout
                const chkRes = await fetch(`/api/v2/marketplace/orders/${orderId}/checkout`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ gateway })
                });
                const chkData = await chkRes.json();
                const trxId = chkData.data?.transaction_id || 'TRX_MOCK_TEST';

                // 3. Verify Payment
                const verRes = await fetch(`/api/v2/marketplace/orders/${orderId}/verify-payment`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ transaction_id: trxId, payment_status: 'COMPLETED' })
                });
                const verData = await verRes.json();

                closeOrderProductModal();
                showToast(`অর্ডার #${orderId} সফল! ৳ ${chkData.data?.amount} এসক্রো ডিপোজিটে লক হয়েছে (${gateway.toUpperCase()})। 🛡️`);
            } catch (e) {
                showToast('অর্ডার সম্পন্ন করতে সমস্যা হয়েছে: ' + e.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* GROUPS DIRECTORY & MEMBERSHIP (V2 ENTERPRISE OS) */
        /* ------------------------------------------------------------- */
        let activeGroupCategory = 'All';
        let groupSearchDebounceTimer = null;

        function setGroupCategoryFilter(cat, btn) {
            activeGroupCategory = cat;
            document.querySelectorAll('#groupCategoriesPillsBar .category-pill').forEach(p => p.classList.remove('active'));
            if (btn) btn.classList.add('active');
            loadGroups();
        }

        function debounceGroupSearch() {
            clearTimeout(groupSearchDebounceTimer);
            groupSearchDebounceTimer = setTimeout(() => {
                loadGroups();
            }, 300);
        }

        async function loadGroups() {
            const container = document.getElementById('groupsListContainer');
            if (!container) return;
            container.innerHTML = '<div style="grid-column: 1 / -1; padding: 32px; text-align: center; color: var(--fb-text-secondary);"><span style="font-size: 24px; display: block; margin-bottom: 8px;">⏳</span>কমিউনিটি ও গ্রুপসমূহ লোড হচ্ছে...</div>';

            const search = document.getElementById('groupDirectorySearch')?.value?.trim() || '';
            const sort = document.getElementById('groupSortSelect')?.value || 'trending';
            const communityType = document.getElementById('groupTypeFilterSelect')?.value || 'all';

            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (activeGroupCategory && activeGroupCategory !== 'All') params.append('category', activeGroupCategory);
            if (communityType && communityType !== 'all') params.append('community_type', communityType);
            params.append('per_page', '30');

            try {
                let groups = [];
                // Attempt V2 Community API first
                try {
                    const resV2 = await fetch(`/api/v2/groups?${params.toString()}`, {
                        headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                    });
                    if (resV2.ok) {
                        const dataV2 = await resV2.json();
                        if (Array.isArray(dataV2.data?.data)) {
                            groups = dataV2.data.data;
                        } else if (Array.isArray(dataV2.data)) {
                            groups = dataV2.data;
                        }
                    }
                } catch (errV2) {
                    console.warn('V2 groups fetch error, falling back to V1', errV2);
                }

                // If V2 was empty or failed, fallback to /api/v1/groups
                if (!groups.length && !search && activeGroupCategory === 'All' && communityType === 'all') {
                    const resV1 = await fetch('/api/v1/groups', {
                        headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                    });
                    if (resV1.ok) {
                        const dataV1 = await resV1.json();
                        groups = Array.isArray(dataV1.data) ? dataV1.data : (dataV1.data?.groups || []);
                    }
                }

                // Client-side sort if needed
                if (sort === 'health') {
                    groups.sort((a, b) => (b.health_score || 0) - (a.health_score || 0));
                } else if (sort === 'members') {
                    groups.sort((a, b) => (b.members_count || 0) - (a.members_count || 0));
                } else if (sort === 'newest') {
                    groups.sort((a, b) => (b.id || 0) - (a.id || 0));
                }

                if (!groups || groups.length === 0) {
                    container.innerHTML = `
                        <div style="grid-column: 1 / -1; background: white; border: 1px solid var(--fb-border); border-radius: 12px; padding: 40px 20px; text-align: center;">
                            <div style="font-size: 36px; margin-bottom: 10px;">🔍</div>
                            <div style="font-size: 16px; font-weight: 800; color: #1e293b;">কোনো কমিউনিটি পাওয়া যায়নি</div>
                            <div style="font-size: 13px; color: var(--fb-text-secondary); margin-top: 4px;">ভিন্ন কি-ওয়ার্ড দিয়ে খুঁজুন অথবা নতুন একটি এন্টারপ্রাইজ কমিউনিটি তৈরি করুন।</div>
                            <button onclick="openCreateGroupModal()" style="margin-top: 16px; background: #4f46e5; color: white; border: none; padding: 8px 18px; border-radius: 8px; font-weight: 700; cursor: pointer;">➕ নতুন কমিউনিটি তৈরি করুন</button>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = groups.map(g => {
                    const firstLetter = (g.name || 'G').charAt(0).toUpperCase();
                    const groupSlug = g.slug || g.id;
                    const groupUrl = `/groups/${groupSlug}`;
                    const healthScore = g.health_score ?? 85;
                    const members = g.members_count || 1;
                    const isPublic = (g.community_type === 'public' || g.privacy === 'public');
                    const isControlled = (g.community_type === 'controlled');
                    const typeLabel = isPublic ? '🌐 পাবলিক' : (isControlled ? '📑 নিয়ন্ত্রিত' : '🔒 প্রাইভেট');
                    const categoryTag = g.category || 'General';
                    const isVerified = g.is_verified ? '<span title="ভেরিফাইড কমিউনিটি" style="color: #4f46e5; font-size: 14px;">✓</span>' : '';

                    return `
                        <div class="community-card" style="background: white; border-radius: 12px; border: 1px solid var(--fb-border); overflow: hidden; display: flex; flex-direction: column; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative;">
                            <a href="${groupUrl}" style="text-decoration: none; color: inherit; display: block;">
                                <div style="height: 85px; background: linear-gradient(135deg, #3730a3, #4f46e5 60%, #06b6d4); position: relative; overflow: hidden;">
                                    ${g.cover_image ? `<img src="${g.cover_image}" alt="${g.name}" style="width: 100%; height: 100%; object-fit: cover;">` : ''}
                                    <div style="position: absolute; left: 14px; bottom: -18px; width: 48px; height: 48px; border-radius: 12px; background: white; border: 2px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; color: #4f46e5; overflow: hidden;">
                                        ${g.avatar_image ? `<img src="${g.avatar_image}" alt="${g.name}" style="width: 100%; height: 100%; object-fit: cover;">` : firstLetter}
                                    </div>
                                    <div style="position: absolute; right: 10px; top: 10px; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); color: #a7f3d0; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2);">
                                        🩺 ${healthScore}%
                                    </div>
                                </div>
                            </a>

                            <div style="padding: 24px 14px 14px; display: flex; flex-direction: column; flex: 1;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                    <a href="${groupUrl}" style="text-decoration: none; color: #0f172a; font-weight: 800; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 4px;">
                                        ${g.name} ${isVerified}
                                    </a>
                                </div>

                                <div style="display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--fb-text-secondary); margin-top: 4px;">
                                    <span>${typeLabel}</span>
                                    <span>•</span>
                                    <span>👥 ${members} জন সদস্য</span>
                                </div>

                                <div style="font-size: 12.5px; color: #475569; margin: 8px 0 auto; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    ${g.description || 'Bondhoo এন্টারপ্রাইজ কমিউনিটি নেটওয়ার্ক। আলোচনা ও জ্ঞান বিনিময়ের উন্মুক্ত প্ল্যাটফর্ম।'}
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 12px; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                                    <span style="background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 4px;">
                                        🏷️ ${categoryTag}
                                    </span>
                                    <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">
                                        ${g.posts_count || g.discussions_count || 0} আলোচনা
                                    </span>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px;">
                                    <a href="${groupUrl}" style="text-align: center; text-decoration: none; background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; font-weight: 700; font-size: 12.5px; padding: 7px 10px; border-radius: 8px; transition: all 0.15s ease;">
                                        প্রবেশ ↗
                                    </a>
                                    <button onclick="toggleGroupJoin(${g.id})" style="background: #4f46e5; color: white; border: none; font-weight: 700; font-size: 12.5px; padding: 7px 10px; border-radius: 8px; cursor: pointer; transition: all 0.15s ease;">
                                        যোগ দিন 🤝
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (e) {
                container.innerHTML = '<div style="grid-column: 1 / -1; color: #ef4444; padding: 20px; text-align: center; font-weight: 600;">কমিউনিটি তালিকা লোড করা যায়নি: ' + e.message + '</div>';
            }
        }

        function openCreateGroupModal() {
            const modal = document.getElementById('createGroupModal');
            if (modal) modal.style.display = 'flex';
        }

        function closeCreateGroupModal() {
            const modal = document.getElementById('createGroupModal');
            if (modal) modal.style.display = 'none';
        }

        async function submitCreateGroup() {
            const name = document.getElementById('groupNameInput')?.value?.trim();
            const username = document.getElementById('groupUsernameInput')?.value?.trim();
            const category = document.getElementById('groupCategoryInput')?.value || 'Technology';
            const communityType = document.getElementById('groupPrivacySelect')?.value || 'public';
            const description = document.getElementById('groupDescInput')?.value?.trim();

            if (!name) {
                alert('অনুগ্রহ করে কমিউনিটির নাম প্রদান করুন।');
                return;
            }

            const btn = document.getElementById('btnSubmitCreateCommunity');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'কমিউনিটি তৈরি হচ্ছে... ⏳';
            }

            try {
                // Try V2 creation endpoint first
                let response = await fetch('/api/v2/groups', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${currentToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name,
                        username: username || undefined,
                        category,
                        community_type: communityType,
                        privacy: (communityType === 'private' || communityType === 'hidden') ? 'private' : 'public',
                        description
                    })
                });

                // Fallback to V1 if V2 responded with error or not found
                if (!response.ok && response.status !== 422) {
                    response = await fetch('/api/v1/groups', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${currentToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            name,
                            privacy: (communityType === 'private' || communityType === 'hidden') ? 'private' : 'public',
                            description
                        })
                    });
                }

                const data = await response.json();
                if (response.ok && (data.success || data.data)) {
                    closeCreateGroupModal();
                    showToast('এন্টারপ্রাইজ কমিউনিটি সফলভাবে তৈরি হয়েছে! 🎉');
                    // Reset inputs
                    if (document.getElementById('groupNameInput')) document.getElementById('groupNameInput').value = '';
                    if (document.getElementById('groupUsernameInput')) document.getElementById('groupUsernameInput').value = '';
                    if (document.getElementById('groupDescInput')) document.getElementById('groupDescInput').value = '';
                    loadGroups();
                } else {
                    const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'কমিউনিটি তৈরি করা যায়নি।');
                    showToast('ব্যর্থ হয়েছে: ' + msg);
                }
            } catch (e) {
                showToast('ত্রুটি: ' + e.message);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'কমিউনিটি তৈরি সম্পন্ন করুন 🎉';
                }
            }
        }

        async function toggleGroupJoin(groupId) {
            try {
                let res = await fetch(`/api/v2/groups/${groupId}/join`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                if (!res.ok) {
                    res = await fetch(`/api/v1/groups/${groupId}/join`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                    });
                }
                const data = await res.json();
                showToast(data.message || 'আপনি কমিউনিটিতে সফলভাবে যুক্ত হয়েছেন!');
                loadGroups();
            } catch (e) {
                showToast('ত্রুটি: ' + e.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* PAGES DIRECTORY & CREATION (REAL API) */
        /* ------------------------------------------------------------- */
        function openPagesModal() {
            document.getElementById('pagesModal').style.display = 'flex';
            loadPages();
        }
        function closePagesModal() {
            document.getElementById('pagesModal').style.display = 'none';
        }
        async function loadPages() {
            const container = document.getElementById('pagesListContainer');
            container.innerHTML = '<div style="padding: 16px; text-align: center;">পেজ লোড হচ্ছে...</div>';
            try {
                const res = await fetch('/api/v1/pages', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                const pages = Array.isArray(data.data) ? data.data : (data.data?.pages || []);

                if (pages.length === 0) {
                    container.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); padding: 16px;">কোনো পেজ পাওয়া যায়নি। নতুন পেজ তৈরি করুন!</div>';
                    return;
                }

                container.innerHTML = pages.map(p => `
                    <div class="fb-card" style="padding: 12px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <div class="avatar" style="width: 40px; height: 40px; background: #f35369;">${p.name.charAt(0)}</div>
                            <div>
                                <div style="font-weight: 700; font-size: 14px;">${p.name} ${p.is_verified ? '✔️' : ''}</div>
                                <div style="font-size: 11px; color: var(--fb-text-secondary);">${p.category || 'অফিসিয়াল'} · ${p.followers_count || 0} ফলোয়ার</div>
                            </div>
                        </div>
                        <button class="post-btn" style="background: #e7f3ff; color: #1877f2; padding: 4px 12px; font-size: 12px; border-radius: 16px;" onclick="togglePageFollow(${p.id})">ফলো</button>
                    </div>
                `).join('');
            } catch (e) {
                container.innerHTML = '<div style="color: red; text-align: center; padding: 16px;">লোড করা যায়নি।</div>';
            }
        }

        function openCreatePageModal() {
            document.getElementById('createPageModal').style.display = 'flex';
        }
        function closeCreatePageModal() {
            document.getElementById('createPageModal').style.display = 'none';
        }
        async function submitCreatePage() {
            const name = document.getElementById('pageNameInput').value.trim();
            const category = document.getElementById('pageCategoryInput').value.trim();
            const bio = document.getElementById('pageBioInput').value.trim();
            if (!name) return;

            try {
                const res = await fetch('/api/v1/pages', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ name, category, bio })
                });
                const data = await res.json();
                if (data.success || data.data) {
                    closeCreatePageModal();
                    showToast('নতুন পেজ সফলভাবে তৈরি হয়েছে! 🚩');
                    loadPages();
                } else {
                    showToast(data.message || 'পেজ তৈরি করা সম্ভব হয়নি।');
                }
            } catch (e) {
                showToast('ত্রুটি: ' + e.message);
            }
        }

        async function togglePageFollow(pageId) {
            try {
                const res = await fetch(`/api/v1/pages/${pageId}/follow`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                showToast(data.message || 'আপনি পেজটি ফলো করেছেন! 🚩');
                loadPages();
            } catch (e) {
                showToast('ত্রুটি: ' + e.message);
            }
        }

        /* ------------------------------------------------------------- */
        /* EVENTS MODAL */
        /* ------------------------------------------------------------- */
        const sampleEvents = [
            { id: 1, title: 'বাংলাদেশ টেক মিটআপ ২০২৬', date: '২৫ সেপ্টেম্বর, ২০২৬ · বিকেল ৪:০০', location: 'বিসিসি অডিটোরিয়াম, আগারগাঁও, ঢাকা', going: 128 },
            { id: 2, title: 'ফটোগ্রাফি ও স্টোরিটেলিং কর্মশালা', date: '০২ অক্টোবর, ২০২৬ · সকাল ১০:০০', location: 'শিল্পকলা একাডেমি, সেগুনবাগিচা, ঢাকা', going: 64 },
            { id: 3, title: 'লারাভেল ও ক্লাউড আর্কিটেকচার কনফারেন্স', date: '১০ অক্টোবর, ২০২৬ · সকাল ৯:০০', location: 'ইন্টারকন্টিনেন্টাল ঢাকা', going: 250 }
        ];

        function openEventsModal() {
            document.getElementById('eventsModal').style.display = 'flex';
            const container = document.getElementById('eventsListContainer');
            container.innerHTML = sampleEvents.map(ev => `
                <div class="fb-card" style="padding: 14px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 800; font-size: 15px;">${ev.title}</div>
                        <div style="font-size: 12px; color: #f7b125; font-weight: 700; margin: 2px 0;">📅 ${ev.date}</div>
                        <div style="font-size: 12px; color: var(--fb-text-secondary);">📍 ${ev.location}</div>
                        <div style="font-size: 11px; color: var(--fb-text-secondary); margin-top: 4px;">👥 ${ev.going} জন আগ্রহী</div>
                    </div>
                    <button class="post-btn" id="event-btn-${ev.id}" style="background: #e7f3ff; color: #1877f2; border-radius: 16px; padding: 6px 14px; font-size: 13px;" onclick="rsvpEvent(${ev.id})">⭐ আগ্রহী</button>
                </div>
            `).join('');
        }
        function closeEventsModal() {
            document.getElementById('eventsModal').style.display = 'none';
        }
        function rsvpEvent(id) {
            const btn = document.getElementById(`event-btn-${id}`);
            if (btn) {
                if (btn.innerText.includes('আগ্রহী')) {
                    btn.innerText = '✔️ যাচ্ছি (Going)';
                    btn.style.background = '#42b72a';
                    btn.style.color = 'white';
                    showToast('অনুষ্ঠানে যোগ দেওয়ার জন্য ধন্যবাদ! ইভেন্টটি আপনার ক্যালেন্ডারে যুক্ত হয়েছে।');
                } else {
                    btn.innerText = '⭐ আগ্রহী';
                    btn.style.background = '#e7f3ff';
                    btn.style.color = '#1877f2';
                    showToast('স্ট্যাটাস আপডেট হয়েছে।');
                }
            }
        }

        /* ------------------------------------------------------------- */
        /* FULL MESSENGER MODAL */
        /* ------------------------------------------------------------- */
        async function openMessengerModal() {
            document.getElementById('messengerModal').style.display = 'flex';
            const list = document.getElementById('messengerModalList');
            list.innerHTML = '<div style="text-align: center; padding: 20px;">কথোপকথন লোড হচ্ছে...</div>';
            if (!currentToken) return;

            try {
                const res = await fetch('/api/v1/conversations', {
                    headers: { 'Authorization': `Bearer ${currentToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                const convs = Array.isArray(data.data) ? data.data : (data.data?.conversations || []);

                if (convs.length === 0) {
                    list.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); padding: 24px;">কোনো চ্যাট ইতিহাস নেই। বন্ধুদের সাথে চ্যাট শুরু করতে ডানপাশের পরিচিতিতে ক্লিক করুন।</div>';
                    return;
                }

                list.innerHTML = convs.map(c => {
                    const other = c.other_user || c.participants?.find(p => p.id !== currentUser?.id);
                    const title = other?.name || c.title || 'চ্যাট';
                    const avatar = other?.avatar_url || other?.profile?.avatar_url || c.avatar_url || '';
                    const otherId = other?.id || null;
                    const otherUsername = other?.username || '';
                    return `
                        <div class="sidebar-item" style="padding: 10px; border-bottom: 1px solid var(--fb-border); cursor: pointer;" onclick="closeMessengerModal(); openRealChat(${c.id}, '${escapeHtml(title)}', '${avatar}', ${otherId || 'null'}, '${escapeHtml(otherUsername)}');">
                            ${avatar ? `<img src="${avatar}" class="avatar" style="width: 38px; height: 38px; object-fit: cover; border-radius: 50%;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">` : `<div class="avatar" style="width: 38px; height: 38px;">${title.charAt(0)}</div>`}
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 700; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(title)}</div>
                                <div style="font-size: 12px; color: var(--fb-text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(c.latest_message?.body || 'মেসেজ দেখতে ক্লিক করুন')}</div>
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (e) {
                list.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">লোড করা যায়নি।</div>';
            }
        }
        function closeMessengerModal() {
            document.getElementById('messengerModal').style.display = 'none';
        }

        window.addEventListener('DOMContentLoaded', () => {
            checkAuth();
            fetchFeedPosts();
            if (currentToken) {
                fetchStories();
                fetchConversations();
                loadNotifications();
            }
        });
    </script>
    <script src="/js/enterprise-stories-reels.js?v={{ time() }}"></script>
    @include('partials.mobile-navigation')
    @include('partials.realtime-listener')
</body>
</html>
