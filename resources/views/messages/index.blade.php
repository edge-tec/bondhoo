@extends('layouts.app')

@section('title', 'মেসেঞ্জার — যোগাযোগ রিয়েল-টাইম মেসেজিং')

@section('styles')
<style>
    /* Facebook Messenger Design System Tokens */
    :root {
        --ms-primary: #0084ff;
        --ms-primary-hover: #0073e6;
        --ms-bg-main: #ffffff;
        --ms-bg-card: #ffffff;
        --ms-bg-input: #f0f2f5;
        --ms-bg-hover: #f2f2f2;
        --ms-bg-active: #eaf3ff;
        --ms-bubble-incoming: #f0f2f5;
        --ms-bubble-outgoing: #0084ff;
        --ms-text-primary: #050505;
        --ms-text-secondary: #65676b;
        --ms-border: rgba(0, 0, 0, 0.08);
        --ms-green: #31a24c;
        --ms-red: #fa383e;
    }

    [data-theme="dark"] {
        --ms-primary: #0084ff;
        --ms-primary-hover: #1a8cff;
        --ms-bg-main: #18191a;
        --ms-bg-card: #242526;
        --ms-bg-input: #3a3b3c;
        --ms-bg-hover: #3a3b3c;
        --ms-bg-active: #263951;
        --ms-bubble-incoming: #3a3b3c;
        --ms-bubble-outgoing: #0084ff;
        --ms-text-primary: #e4e6eb;
        --ms-text-secondary: #b0b3b8;
        --ms-border: rgba(255, 255, 255, 0.08);
    }

    /* Edge-to-edge container */
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
        background: var(--ms-bg-main);
    }

    .messenger-wrapper {
        display: flex;
        width: 100% !important;
        max-width: 100% !important;
        height: 100% !important;
        max-height: 100% !important;
        flex: 1 1 auto;
        background: var(--ms-bg-main);
        overflow: hidden;
        position: relative;
        font-family: 'Hind Siliguri', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
    }

    .avatar {
        border-radius: 50%;
        background: linear-gradient(135deg, #0084ff 0%, #00c6ff 100%);
        color: #ffffff !important;
        font-weight: 700;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: none !important;
        box-shadow: 0 2px 6px rgba(0, 132, 255, 0.2);
    }

    /* ==============================================================
       COLUMN 1: CONVERSATIONS LIST (MESSENGER STYLE)
       ============================================================== */
    .conversations-pane {
        width: 360px;
        min-width: 320px;
        max-width: 390px;
        border-right: 1px solid var(--ms-border);
        display: flex;
        flex-direction: column;
        background: var(--ms-bg-card);
        flex-shrink: 0;
        height: 100%;
        overflow: hidden;
        z-index: 10;
        transition: width 0.25s ease;
    }

    .pane-header {
        padding: 16px 18px 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--ms-bg-card);
    }

    .pane-title {
        font-size: 24px;
        font-weight: 800;
        color: var(--ms-text-primary);
        letter-spacing: -0.5px;
        margin: 0;
    }

    .pane-header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .icon-circle-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--ms-bg-input);
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        color: var(--ms-text-primary);
        transition: background 0.15s, transform 0.1s;
        text-decoration: none;
        flex-shrink: 0;
    }

    .icon-circle-btn svg {
        width: 19px;
        height: 19px;
        stroke: var(--ms-text-primary);
        display: block;
    }

    .icon-circle-btn:hover {
        background: var(--ms-bg-hover);
        transform: scale(1.04);
    }

    .conversations-search {
        padding: 4px 16px 10px;
    }

    .search-input-box {
        display: flex;
        align-items: center;
        background: var(--ms-bg-input);
        border: none;
        border-radius: 20px;
        padding: 7px 14px;
        gap: 8px;
        height: 36px;
        box-sizing: border-box;
    }

    .search-input-box input {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        font-size: 14px;
        color: var(--ms-text-primary);
        font-family: inherit;
    }

    .search-input-box input::placeholder {
        color: var(--ms-text-secondary);
        font-size: 13.5px;
    }

    /* Filter Tabs (Subtle Messenger Style) */
    .filter-tabs {
        display: flex;
        gap: 6px;
        padding: 0 16px 10px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        scroll-behavior: smooth;
    }
    .filter-tabs::-webkit-scrollbar { display: none; }

    .filter-pill {
        flex-shrink: 0;
        padding: 6px 13px;
        border-radius: 18px;
        font-size: 13px;
        font-weight: 600;
        background: var(--ms-bg-input);
        color: var(--ms-text-secondary);
        border: none;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s ease;
        user-select: none;
    }

    .filter-pill:hover {
        background: var(--ms-bg-hover);
        color: var(--ms-text-primary);
    }

    .filter-pill.active {
        background: var(--ms-bg-active);
        color: var(--ms-primary);
        font-weight: 700;
    }

    .conversations-list {
        flex: 1;
        overflow-y: auto;
        padding: 4px 8px 12px;
    }

    .conversation-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 10px;
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.15s;
        text-decoration: none;
        color: inherit;
        position: relative;
        margin-bottom: 2px;
    }

    .conversation-item:hover {
        background: var(--ms-bg-hover);
    }

    .conversation-item.active {
        background: var(--ms-bg-active);
    }

    .conv-avatar-wrapper {
        position: relative;
        flex-shrink: 0;
    }

    .conv-avatar-wrapper .avatar {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        overflow: hidden;
    }

    .online-indicator {
        position: absolute;
        bottom: 1px;
        right: 1px;
        width: 14px;
        height: 14px;
        background: var(--ms-green);
        border: 2.5px solid var(--ms-bg-card);
        border-radius: 50%;
    }

    .conv-content {
        flex: 1;
        min-width: 0;
    }

    .conv-top-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 2px;
    }

    .conv-name {
        font-weight: 600;
        font-size: 15px;
        color: var(--ms-text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .conv-time {
        font-size: 12px;
        color: var(--ms-text-secondary);
        flex-shrink: 0;
        margin-left: 6px;
    }

    .conv-bottom-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .conv-snippet {
        font-size: 13px;
        color: var(--ms-text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .conv-item-unread .conv-name {
        font-weight: 700;
        color: var(--ms-text-primary);
    }

    .conv-item-unread .conv-snippet {
        font-weight: 600;
        color: var(--ms-text-primary);
    }

    /* Messenger Blue Unread Dot */
    .badge-unread-pill {
        background: var(--ms-primary);
        border-radius: 50%;
        width: 12px;
        height: 12px;
        padding: 0;
        font-size: 0;
        flex-shrink: 0;
    }

    .badge-pinned, .badge-muted {
        color: var(--ms-text-secondary);
        font-size: 12px;
        display: inline-flex;
        align-items: center;
    }

    /* ==============================================================
       COLUMN 2: ACTIVE CHAT PANE (MESSENGER STYLE)
       ============================================================== */
    .chat-pane {
        flex: 1 1 0%;
        min-width: 0;
        display: flex;
        flex-direction: column;
        background: var(--ms-bg-main);
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .chat-header {
        height: 64px;
        padding: 0 16px;
        border-bottom: 1px solid var(--ms-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--ms-bg-card);
        flex-shrink: 0;
        z-index: 20;
        gap: 8px;
        position: relative;
    }

    .chat-header-user {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1 1 auto;
        overflow: hidden;
    }

    .chat-header-info {
        min-width: 0;
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        justify-content: center;
        overflow: hidden;
    }

    .chat-header-name {
        font-weight: 800;
        font-size: 15.5px;
        color: var(--fb-text-primary);
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.25;
    }

    .chat-header-name-link {
        text-decoration: none;
        color: inherit;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
        display: block;
        cursor: pointer;
    }

    .chat-header-name-link:hover {
        text-decoration: underline;
    }

    .chat-header-group-badge {
        font-size: 11px;
        background: rgba(0,0,0,0.06);
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 600;
        flex-shrink: 0;
    }

    .chat-header-status {
        font-size: 11.5px;
        color: var(--ms-green);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
        line-height: 1.35;
        cursor: pointer;
    }

    .chat-header-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }

    .ms-header-action-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: transparent;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ms-primary);
        transition: background 0.15s;
        flex-shrink: 0;
    }

    .ms-header-action-btn:hover {
        background: var(--ms-bg-hover);
    }

    .ms-header-action-btn svg {
        width: 20px;
        height: 20px;
        stroke: var(--ms-primary);
    }

    .chat-header-actions .icon-circle-btn {
        background: transparent;
        color: var(--ms-primary);
        width: 36px;
        height: 36px;
        flex-shrink: 0;
    }

    .chat-header-actions .icon-circle-btn svg {
        stroke: var(--ms-primary);
        width: 20px;
        height: 20px;
    }

    .chat-header-actions .icon-circle-btn:hover {
        background: var(--ms-bg-hover);
    }

    .btn-mobile-back {
        display: none;
        background: var(--ms-bg-input);
        border: none;
        width: 40px;
        height: 40px;
        min-width: 40px;
        min-height: 40px;
        border-radius: 50%;
        cursor: pointer;
        color: var(--ms-text-primary);
        margin-right: 8px;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background 0.15s, transform 0.1s;
        -webkit-tap-highlight-color: transparent;
    }
    .btn-mobile-back:active {
        background: var(--ms-bg-hover);
        transform: scale(0.95);
    }

    /* In-Chat Search Bar */
    .in-chat-search-bar {
        padding: 8px 16px;
        background: var(--ms-bg-input);
        border-bottom: 1px solid var(--ms-border);
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    /* Messages Stream (Crisp Clean White Canvas) */
    .messages-stream {
        flex: 1 1 0%;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 16px 20px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        background: var(--ms-bg-main);
        position: relative;
    }

    /* Messenger Signature Empty Conversation Hero */
    .empty-conversation-hero {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: auto;
        padding: 40px 20px;
        text-align: center;
    }

    .hero-avatar-wrapper {
        position: relative;
        margin-bottom: 12px;
    }

    .hero-avatar {
        width: 80px;
        height: 80px;
        font-size: 32px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
    }

    .hero-online-dot {
        width: 18px;
        height: 18px;
        border-width: 3px;
        bottom: 2px;
        right: 2px;
    }

    .hero-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--ms-text-primary);
        margin-bottom: 4px;
    }

    .hero-subtitle {
        font-size: 13.5px;
        color: var(--ms-text-secondary);
        margin-bottom: 18px;
    }

    .hero-wave-prompt {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--ms-bg-input);
        padding: 9px 18px;
        border-radius: 20px;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--ms-text-primary);
        cursor: pointer;
        transition: background 0.15s, transform 0.1s;
    }

    .hero-wave-prompt:hover {
        background: var(--ms-bg-hover);
        transform: scale(1.03);
    }

    .hero-wave-prompt .wave-hand {
        font-size: 20px;
        animation: waveAnim 2s infinite ease-in-out;
        transform-origin: 70% 70%;
        display: inline-block;
    }

    @keyframes waveAnim {
        0%, 100% { transform: rotate(0deg); }
        20% { transform: rotate(14deg); }
        40% { transform: rotate(-12deg); }
        60% { transform: rotate(14deg); }
        80% { transform: rotate(-4deg); }
    }

    .date-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 14px 0 8px;
    }

    .date-divider span {
        font-size: 11.5px;
        font-weight: 600;
        color: var(--ms-text-secondary);
    }

    .system-message-row {
        text-align: center;
        margin: 6px auto;
        font-size: 12px;
        color: var(--ms-text-secondary);
        padding: 3px 12px;
        border-radius: 12px;
        max-width: 80%;
    }

    .message-row {
        display: flex;
        gap: 8px;
        max-width: min(72%, 580px);
        position: relative;
        margin-bottom: 2px;
    }

    .message-row.incoming {
        align-self: flex-start;
    }

    .message-row.outgoing {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .message-bubble-wrapper {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .message-sender-name {
        font-size: 11.5px;
        font-weight: 600;
        color: var(--ms-text-secondary);
        margin-bottom: 2px;
        margin-left: 8px;
    }

    .reply-preview-box {
        background: rgba(0, 0, 0, 0.05);
        border-left: 3px solid var(--ms-primary);
        padding: 5px 9px;
        border-radius: 6px;
        margin-bottom: 4px;
        font-size: 12px;
        cursor: pointer;
    }

    .message-row.outgoing .reply-preview-box {
        background: rgba(255, 255, 255, 0.2);
        border-left-color: white;
        color: white;
    }

    /* Authentic Messenger Bubbles (No Borders, Perfect Curvature) */
    .message-bubble {
        padding: 10px 16px;
        border-radius: 18px;
        font-size: 15px;
        line-height: 1.45;
        word-break: break-word;
        overflow-wrap: anywhere;
        position: relative;
        border: none;
        min-width: 44px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .message-row.incoming .message-bubble {
        background: var(--ms-bubble-incoming);
        color: var(--ms-text-primary);
        border-bottom-left-radius: 4px;
    }

    .message-row.outgoing .message-bubble {
        background: var(--ms-primary);
        background: linear-gradient(135deg, #0084ff 0%, #0099ff 100%);
        color: #ffffff;
        border-bottom-right-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 132, 255, 0.25);
    }

    .forwarded-indicator {
        font-size: 11px;
        font-style: italic;
        opacity: 0.85;
        margin-bottom: 3px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .message-media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 4px;
        margin-top: 4px;
        border-radius: 12px;
        overflow: hidden;
    }

    .media-preview-item img, .media-preview-item video {
        width: 100%;
        max-height: 240px;
        object-fit: cover;
        border-radius: 10px;
        cursor: pointer;
    }

    .file-attachment-card {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(0, 0, 0, 0.05);
        padding: 8px 12px;
        border-radius: 10px;
        margin-top: 4px;
        text-decoration: none;
        color: inherit;
    }

    .message-row.outgoing .file-attachment-card {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .message-meta-row {
        font-size: 11px;
        margin-top: 3px;
        color: var(--ms-text-secondary);
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
        padding: 0 4px;
        opacity: 0.85;
    }

    .message-row.outgoing .message-meta-row {
        justify-content: flex-end;
    }

    .edited-badge {
        font-size: 10px;
        font-style: italic;
        opacity: 0.8;
    }

    .delivery-check {
        font-size: 12px;
        color: var(--ms-text-secondary);
    }
    .delivery-check.seen {
        color: var(--ms-primary);
        font-weight: 700;
    }

    /* Hover Quick Action Toolbar (Floating Pill) */
    .message-action-toolbar {
        position: absolute;
        top: -26px;
        background: var(--ms-bg-card);
        border: 1px solid var(--ms-border);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
        border-radius: 20px;
        padding: 2px 6px;
        display: none;
        align-items: center;
        gap: 2px;
        z-index: 15;
    }

    .message-row.incoming .message-action-toolbar {
        left: 20px;
    }

    .message-row.outgoing .message-action-toolbar {
        right: 10px;
    }

    .message-row:hover .message-action-toolbar {
        display: flex;
    }

    .toolbar-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px 6px;
        font-size: 14px;
        border-radius: 50%;
        transition: transform 0.1s;
        color: var(--ms-text-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .toolbar-btn:hover {
        background: var(--ms-bg-hover);
        transform: scale(1.18);
    }

    /* Reactions Badge */
    .reactions-badge-row {
        position: absolute;
        bottom: -9px;
        background: var(--ms-bg-card);
        border: 1px solid var(--ms-border);
        box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        border-radius: 12px;
        padding: 2px 6px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        z-index: 6;
    }

    .message-row.incoming .reactions-badge-row {
        left: 10px;
    }

    .message-row.outgoing .reactions-badge-row {
        right: 10px;
    }

    /* Typing Indicator Bubble */
    .typing-bubble {
        align-self: flex-start;
        display: flex;
        align-items: center;
        gap: 6px;
        background: var(--ms-bubble-incoming);
        padding: 8px 14px;
        border-radius: 18px;
        font-size: 12.5px;
        color: var(--ms-text-secondary);
    }

    .typing-dots span {
        display: inline-block;
        width: 5px;
        height: 5px;
        background: var(--ms-text-secondary);
        border-radius: 50%;
        animation: typingDot 1.4s infinite ease-in-out;
    }
    .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
    .typing-dots span:nth-child(3) { animation-delay: 0.4s; }

    @keyframes typingDot {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }

    /* Chat Footer & Composer (Authentic Messenger Style) */
    .chat-composer-wrapper {
        border-top: 1px solid var(--ms-border);
        background: var(--ms-bg-card);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        z-index: 5;
        width: 100%;
        box-sizing: border-box;
    }

    .composer-reply-banner {
        padding: 8px 18px;
        background: var(--ms-bg-input);
        border-bottom: 1px solid var(--ms-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13px;
    }

    .chat-footer {
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        box-sizing: border-box;
    }

    .ms-composer-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: transparent;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ms-primary);
        transition: background 0.15s;
        flex-shrink: 0;
    }

    .ms-composer-btn:hover {
        background: var(--ms-bg-hover);
    }

    .chat-textarea-box {
        flex: 1 1 auto;
        min-width: 0;
        background: var(--ms-bg-input);
        border-radius: 20px;
        padding: 8px 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-sizing: border-box;
        border: 1.5px solid transparent;
        transition: background 0.18s, border-color 0.18s, box-shadow 0.18s;
    }

    .chat-textarea-box:focus-within {
        background: #ffffff;
        border-color: var(--ms-primary);
        box-shadow: 0 0 0 3px rgba(0, 132, 255, 0.14);
    }

    .chat-textarea {
        flex: 1;
        background: transparent;
        border: none;
        outline: none;
        font-size: 15px;
        color: var(--ms-text-primary);
        resize: none;
        max-height: 100px;
        min-height: 22px;
        line-height: 1.35;
        font-family: inherit;
        box-sizing: border-box;
        width: 100%;
    }

    .ms-input-icon-btn {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--ms-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2px;
        transition: transform 0.1s;
    }

    .ms-input-icon-btn:hover {
        transform: scale(1.15);
    }

    .btn-send-message {
        background: var(--ms-primary);
        color: #ffffff;
        border: none;
        border-radius: 50%;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 132, 255, 0.35);
        transition: transform 0.15s ease, background 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
        flex-shrink: 0;
        touch-action: manipulation;
        user-select: none;
        -webkit-user-select: none;
        -webkit-tap-highlight-color: transparent;
        position: relative;
        z-index: 5;
    }

    .btn-send-message:hover {
        background: var(--ms-primary-hover);
        transform: scale(1.08);
        box-shadow: 0 4px 12px rgba(0, 132, 255, 0.45);
    }

    .btn-send-message:active {
        transform: scale(0.95);
    }

    .btn-send-message:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
        pointer-events: none;
    }

    .btn-send-message svg {
        stroke: #ffffff;
        fill: #ffffff;
        width: 18px;
        height: 18px;
        margin-left: 2px;
    }

    /* Live Voice Recording UI */
    .voice-recording-bar {
        display: none;
        flex: 1;
        align-items: center;
        justify-content: space-between;
        background: rgba(239, 68, 68, 0.08);
        border-radius: 20px;
        padding: 6px 16px;
    }

    .voice-record-pulse {
        width: 10px;
        height: 10px;
        background: var(--ms-red);
        border-radius: 50%;
        animation: pulseRecord 1s infinite;
    }

    @keyframes pulseRecord {
        0% { transform: scale(0.9); opacity: 0.7; }
        50% { transform: scale(1.3); opacity: 1; }
        100% { transform: scale(0.9); opacity: 0.7; }
    }

    /* ==============================================================
       COLUMN 3: RIGHT DETAILS PANEL (MESSENGER STYLE)
       ============================================================== */
    .details-sidebar-pane {
        width: 340px;
        min-width: 320px;
        border-left: 1px solid var(--ms-border);
        background: var(--ms-bg-card);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        flex-shrink: 0;
        height: 100%;
        transition: width 0.25s ease, transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 25;
    }

    .details-sidebar-pane.collapsed {
        display: none !important;
    }

    .details-drawer-header {
        display: none;
        padding: 14px 18px;
        border-bottom: 1px solid var(--ms-border);
        align-items: center;
        justify-content: space-between;
    }

    .details-profile-card {
        padding: 24px 16px 16px;
        text-align: center;
    }

    .details-quick-actions {
        display: flex;
        justify-content: center;
        gap: 28px;
        margin: 16px 0 6px;
    }

    .details-quick-action-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: var(--ms-text-primary);
        cursor: pointer;
        text-decoration: none;
        transition: transform 0.1s;
    }

    .details-quick-action-item:hover {
        transform: translateY(-2px);
    }

    .details-quick-action-btn {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #e4e6eb;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        color: var(--ms-text-primary);
        transition: background 0.15s, transform 0.1s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    .details-quick-action-btn:hover {
        background: #d8dadf;
    }

    .details-quick-action-btn svg {
        width: 20px;
        height: 20px;
        stroke: var(--ms-text-primary);
    }

    .details-nav-section {
        padding: 10px 14px;
        border-top: 1px solid var(--ms-border);
    }

    .details-section-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--ms-text-secondary);
        margin-bottom: 6px;
    }

    .details-menu-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        padding: 9px 12px;
        border-radius: 10px;
        border: none;
        background: transparent;
        cursor: pointer;
        color: var(--ms-text-primary);
        font-size: 14px;
        font-weight: 600;
        text-align: left;
        transition: background 0.15s, transform 0.05s;
        font-family: inherit;
    }

    .details-menu-btn:hover {
        background: var(--ms-bg-hover);
    }

    .details-menu-btn:active {
        transform: scale(0.98);
    }

    .menu-icon-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--ms-bg-input);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--ms-text-primary);
        transition: background 0.15s;
    }

    .details-menu-btn:hover .menu-icon-circle {
        background: #e4e6eb;
    }

    .details-menu-btn.warning {
        color: #d97706;
    }

    .details-menu-btn.warning .menu-icon-circle {
        background: rgba(245, 158, 11, 0.12);
        color: #d97706;
    }

    .details-menu-btn.danger {
        color: #ef4444;
    }

    .details-menu-btn.danger .menu-icon-circle {
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
    }

    .shared-media-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
        margin-top: 6px;
    }

    .shared-media-thumb img, .shared-media-item img {
        width: 100%;
        height: 76px;
        object-fit: cover;
        border-radius: 6px;
        cursor: pointer;
    }

    /* Backdrop for slide-over drawer on tablet / mobile */
    .details-drawer-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(2px);
        z-index: 990;
        display: none;
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .details-drawer-backdrop.active {
        display: block;
        opacity: 1;
    }

    /* Modals & Advanced Search UI (Messenger Style) */
    .chat-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 2100;
        display: none;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(5px);
    }

    .chat-modal-box {
        background: var(--ms-bg-card);
        border: 1px solid var(--ms-border);
        border-radius: 20px;
        width: 480px;
        max-width: 94vw;
        box-shadow: 0 16px 48px rgba(0, 0, 0, 0.22);
        overflow: hidden;
        animation: modalScale 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalScale {
        from { transform: scale(0.94); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .modal-segmented-control {
        display: flex;
        background: var(--ms-bg-input);
        padding: 4px;
        border-radius: 24px;
        gap: 4px;
        margin-bottom: 16px;
    }

    .modal-segmented-tab {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 20px;
        border: none;
        background: transparent;
        color: var(--ms-text-secondary);
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: inherit;
    }

    .modal-segmented-tab:hover {
        color: var(--ms-text-primary);
    }

    .modal-segmented-tab.active {
        background: var(--ms-bg-card);
        color: var(--ms-primary);
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    /* Messenger Advanced Search Pill */
    .ms-search-capsule {
        background: var(--ms-bg-input);
        border: 1.5px solid transparent;
        border-radius: 24px;
        padding: 9px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-sizing: border-box;
        transition: background 0.18s, border-color 0.18s, box-shadow 0.18s;
        margin-bottom: 14px;
        position: relative;
    }

    .ms-search-capsule:focus-within {
        background: #ffffff;
        border-color: var(--ms-primary);
        box-shadow: 0 0 0 3.5px rgba(0, 132, 255, 0.15);
    }

    .ms-search-capsule svg.search-icon,
    .ms-search-capsule .search-icon,
    .ms-search-capsule > svg {
        width: 18px !important;
        height: 18px !important;
        stroke: var(--ms-text-secondary);
        flex-shrink: 0;
        position: static !important;
        transform: none !important;
        left: auto !important;
        top: auto !important;
        display: inline-block !important;
        margin: 0 !important;
        transition: stroke 0.18s;
    }

    .ms-search-capsule:focus-within svg.search-icon,
    .ms-search-capsule:focus-within > svg {
        stroke: var(--ms-primary);
    }

    .ms-search-capsule input {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        font-size: 14.5px;
        font-weight: 500;
        color: var(--ms-text-primary);
        font-family: inherit;
    }

    .ms-search-capsule input::placeholder {
        color: var(--ms-text-secondary);
        font-weight: 400;
    }

    .search-clear-btn {
        background: #d8dadf;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        color: #050505;
        cursor: pointer;
        flex-shrink: 0;
        transition: background 0.15s;
    }

    .search-clear-btn:hover {
        background: #bcc0c4;
    }

    /* Search Results List */
    .search-results-container {
        max-height: 290px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding-right: 2px;
    }

    .search-section-header {
        font-size: 12px;
        font-weight: 700;
        color: var(--ms-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 4px 6px 8px;
    }

    .search-user-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        border-radius: 12px;
        cursor: pointer;
        transition: background 0.15s, transform 0.08s;
    }

    .search-user-card:hover {
        background: var(--ms-bg-hover);
        transform: translateX(2px);
    }

    .search-user-card .avatar {
        width: 44px;
        height: 44px;
        font-size: 17px;
        flex-shrink: 0;
    }

    .btn-chat-action {
        padding: 6px 16px;
        font-size: 12.5px;
        font-weight: 700;
        border-radius: 18px;
        border: none;
        background: #e7f3ff;
        color: var(--ms-primary);
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }

    .search-user-card:hover .btn-chat-action {
        background: var(--ms-primary);
        color: #ffffff;
    }

    /* Friendly Empty Search State */
    .search-empty-state {
        text-align: center;
        padding: 32px 16px;
        color: var(--ms-text-secondary);
    }

    .search-empty-icon {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: #e7f3ff;
        color: var(--ms-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
    }

    .search-empty-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--ms-text-primary);
        margin-bottom: 4px;
    }

    .search-empty-desc {
        font-size: 13px;
        line-height: 1.4;
        max-width: 320px;
        margin: 0 auto;
    }

    /* ==============================================================
       ENTERPRISE EMPTY CHAT WELCOME HERO STATE
       ============================================================== */
    .empty-chat-welcome-container {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        padding: 40px 24px;
        background: var(--ms-bg-chat);
        overflow-y: auto;
        box-sizing: border-box;
    }

    .empty-chat-welcome-card {
        max-width: 520px;
        width: 100%;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: auto;
    }

    .empty-chat-hero-icon-wrap {
        margin-bottom: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s ease;
    }

    .empty-chat-hero-icon-wrap:hover {
        transform: scale(1.06) rotate(-2deg);
    }

    .empty-chat-welcome-title {
        font-size: 24px;
        font-weight: 800;
        color: var(--ms-text-primary);
        margin: 0 0 10px;
        letter-spacing: -0.4px;
    }

    .empty-chat-welcome-desc {
        font-size: 14.5px;
        line-height: 1.6;
        color: var(--ms-text-secondary);
        max-width: 440px;
        margin: 0 0 26px;
    }

    .empty-chat-actions-row {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 34px;
        width: 100%;
    }

    .btn-ms-hero-primary {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        background: linear-gradient(135deg, #0084ff 0%, #0066cc 100%);
        color: #ffffff;
        padding: 12px 24px;
        border-radius: 24px;
        font-size: 14.5px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0, 132, 255, 0.35);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .btn-ms-hero-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 132, 255, 0.45);
    }

    .btn-ms-hero-secondary {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        background: var(--ms-bg-card);
        color: var(--ms-text-primary);
        padding: 12px 22px;
        border-radius: 24px;
        font-size: 14.5px;
        font-weight: 600;
        border: 1px solid var(--ms-border);
        cursor: pointer;
        transition: transform 0.15s ease, background 0.15s ease, border-color 0.15s ease;
    }

    .btn-ms-hero-secondary:hover {
        background: var(--ms-bg-hover);
        border-color: rgba(0, 132, 255, 0.3);
        transform: translateY(-2px);
    }

    .empty-chat-features-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
        width: 100%;
        text-align: left;
        background: var(--ms-bg-card);
        border: 1px solid var(--ms-border);
        border-radius: 18px;
        padding: 18px 22px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    }

    .empty-chat-feature-item {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .ec-feat-icon {
        font-size: 20px;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 132, 255, 0.08);
        border-radius: 12px;
        flex-shrink: 0;
    }

    .ec-feat-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--ms-text-primary);
    }

    .ec-feat-sub {
        font-size: 12.5px;
        color: var(--ms-text-secondary);
        margin-top: 1px;
    }

    /* ==============================================================
       RESPONSIVE BREAKPOINTS (DESKTOP, TABLET, MOBILE)
       ============================================================== */
    @media (max-width: 1199px) {
        .details-sidebar-pane {
            position: fixed;
            right: 0;
            top: 56px;
            bottom: 0;
            width: min(340px, 85vw);
            z-index: 1000;
            box-shadow: -6px 0 24px rgba(0, 0, 0, 0.15);
            transform: translateX(100%);
            display: flex !important;
        }

        .details-sidebar-pane.drawer-open {
            transform: translateX(0);
        }

        .details-drawer-header {
            display: flex;
        }
    }

    @media (max-width: 767px) {
        .main-app-container.main-messenger-mode {
            height: calc(100dvh - 56px) !important;
            max-height: calc(100dvh - 56px) !important;
        }

        .conversations-pane {
            width: 100%;
            max-width: 100%;
            border-right: none;
            display: {{ $activeConversation ? 'none' : 'flex' }};
        }

        .chat-pane {
            width: 100%;
            display: {{ $activeConversation ? 'flex' : 'none' }};
        }

        .btn-mobile-back {
            display: inline-flex;
            width: 36px;
            height: 36px;
            min-width: 36px;
            min-height: 36px;
            margin-right: 4px;
        }

        .chat-header {
            padding: 0 10px;
            height: 56px;
            gap: 6px;
        }

        .chat-header-user {
            gap: 8px;
        }

        .chat-header-user .avatar {
            width: 38px !important;
            height: 38px !important;
            font-size: 15px !important;
        }

        .chat-header-name {
            font-size: 14.5px;
        }

        .chat-header-status {
            font-size: 11px;
        }

        /* Hide secondary buttons on mobile to avoid overcrowding and header control collision */
        .chat-header-actions .btn-header-profile,
        .chat-header-actions .btn-header-search,
        .chat-header-actions .btn-header-multicall {
            display: none !important;
        }

        .chat-header-actions {
            gap: 4px;
        }

        .chat-header-actions .icon-circle-btn {
            width: 34px !important;
            height: 34px !important;
        }

        .chat-header-actions .icon-circle-btn svg {
            width: 17px !important;
            height: 17px !important;
        }

        .messages-stream {
            padding: 10px 12px;
        }

        .message-row {
            max-width: 88%;
        }

        .chat-composer-wrapper {
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        .chat-footer {
            padding: 6px 8px;
            gap: 5px;
        }

        .chat-textarea-box {
            padding: 6px 10px;
            gap: 4px;
        }

        .chat-textarea {
            font-size: 14px;
            min-height: 20px;
        }

        .ms-composer-btn {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            padding: 0 !important;
        }

        .ms-composer-btn svg {
            width: 19px !important;
            height: 19px !important;
        }

        .btn-send-message {
            width: 36px !important;
            height: 36px !important;
            min-width: 36px !important;
            min-height: 36px !important;
        }

        .btn-send-message svg {
            width: 17px !important;
            height: 17px !important;
        }
    }

    /* Messenger Voice Note Player */
    .voice-message-player {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 220px;
        max-width: 320px;
        padding: 8px 12px;
        border-radius: 18px;
        background: rgba(0, 0, 0, 0.05);
        user-select: none;
    }
    .message-row.outgoing .voice-message-player {
        background: rgba(255, 255, 255, 0.22);
    }
    .voice-play-btn {
        width: 36px;
        height: 36px;
        min-width: 36px;
        border-radius: 50%;
        background: var(--ms-primary, #0084ff);
        color: #ffffff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.15s ease, background 0.2s ease;
        box-shadow: 0 2px 6px rgba(0, 132, 255, 0.35);
    }
    .message-row.outgoing .voice-play-btn {
        background: #ffffff;
        color: var(--ms-primary, #0084ff);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
    .voice-play-btn:hover {
        transform: scale(1.06);
    }
    .voice-player-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }
    .voice-progress-track {
        position: relative;
        width: 100%;
        height: 6px;
        border-radius: 3px;
        background: rgba(0, 0, 0, 0.15);
        cursor: pointer;
        overflow: hidden;
    }
    .message-row.outgoing .voice-progress-track {
        background: rgba(255, 255, 255, 0.35);
    }
    .voice-progress-fill {
        height: 100%;
        width: 0%;
        border-radius: 3px;
        background: var(--ms-primary, #0084ff);
        transition: width 0.1s linear;
    }
    .message-row.outgoing .voice-progress-fill {
        background: #ffffff;
    }
    .voice-meta-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 11px;
        font-weight: 600;
        opacity: 0.85;
    }

    /* Upload & Message States */
    .upload-progress-container {
        width: 100%;
    }
    .upload-progress-bar-wrap {
        width: 100%;
        height: 5px;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 3px;
        overflow: hidden;
    }
    .upload-progress-bar-fill {
        height: 100%;
        width: 0%;
        background: var(--ms-primary, #0084ff);
        transition: width 0.2s ease;
    }
    .message-row.failed .message-bubble {
        border: 1px solid #ef4444 !important;
        background: #fef2f2 !important;
        color: #991b1b !important;
    }
    .retry-send-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: none;
        border: none;
        color: #ef4444;
        font-weight: 700;
        font-size: 11px;
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 4px;
    }
    .retry-send-btn:hover {
        background: rgba(239, 68, 68, 0.1);
    }

    /* Active Friends Section & Carousel */
    .active-friends-section {
        padding: 10px 14px 6px;
        border-bottom: 1px solid var(--ms-border);
        background: var(--ms-bg-card);
        flex-shrink: 0;
    }
    .active-friends-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .active-friends-title {
        font-size: 12px;
        font-weight: 700;
        color: var(--ms-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .active-friends-badge {
        background: rgba(49, 162, 76, 0.15);
        color: var(--ms-green);
        padding: 1px 7px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
    }
    .active-friends-refresh-btn {
        background: transparent;
        border: none;
        color: var(--ms-text-secondary);
        cursor: pointer;
        padding: 4px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.15s, transform 0.2s;
    }
    .active-friends-refresh-btn:hover {
        color: var(--ms-primary);
        transform: rotate(90deg);
    }
    .active-friends-rail {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        overflow-x: auto;
        padding-bottom: 6px;
        scrollbar-width: thin;
        scrollbar-color: rgba(0,0,0,0.15) transparent;
    }
    .active-friends-rail::-webkit-scrollbar {
        height: 4px;
    }
    .active-friends-rail::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.15);
        border-radius: 4px;
    }
    .active-friend-pill {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 68px;
        min-width: 68px;
        cursor: pointer;
        text-align: center;
        position: relative;
        padding: 4px;
        border-radius: 10px;
        transition: background 0.15s, transform 0.15s;
    }
    .active-friend-pill:hover {
        background: var(--ms-bg-hover);
        transform: translateY(-2px);
    }
    .active-friend-avatar-wrap {
        position: relative;
        width: 44px;
        height: 44px;
        margin-bottom: 4px;
    }
    .active-friend-avatar-wrap .avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .active-friend-avatar-wrap .online-indicator {
        position: absolute;
        bottom: 1px;
        right: 1px;
        width: 13px;
        height: 13px;
        border-radius: 50%;
        border: 2.5px solid var(--ms-bg-card);
    }
    .active-friend-avatar-wrap .online-indicator.is-online {
        background: var(--ms-green);
        box-shadow: 0 0 6px var(--ms-green);
    }
    .active-friend-avatar-wrap .online-indicator.is-offline {
        background: #94a3b8;
    }
    .active-friend-name {
        font-size: 11.5px;
        font-weight: 600;
        color: var(--ms-text-primary);
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.25;
        min-width: 0;
    }
    .active-friend-status {
        font-size: 10px;
        color: var(--ms-text-secondary);
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
    }
    .active-friend-actions {
        display: none;
        position: absolute;
        bottom: 22px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--ms-bg-card);
        border: 1px solid var(--ms-border);
        box-shadow: 0 4px 14px rgba(0,0,0,0.18);
        border-radius: 18px;
        padding: 3px 6px;
        gap: 5px;
        z-index: 25;
    }
    .active-friend-pill:hover .active-friend-actions {
        display: flex;
    }
    .af-action-btn {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--ms-bg-input);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--ms-text-primary);
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .af-action-btn:hover {
        background: var(--ms-primary);
        color: #ffffff;
    }

    /* Presence Status Dropdown in Pane Header */
    .presence-status-btn {
        position: relative;
    }
    .presence-status-dot {
        width: 11px;
        height: 11px;
        border-radius: 50%;
        display: inline-block;
        transition: background 0.2s;
    }
    .presence-status-dot.online {
        background: var(--ms-green);
        box-shadow: 0 0 7px var(--ms-green);
    }
    .presence-status-dot.offline {
        background: #94a3b8;
    }
    .presence-dropdown-menu {
        position: absolute;
        top: 48px;
        right: 0;
        background: var(--ms-bg-card);
        border: 1px solid var(--ms-border);
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.18);
        padding: 8px 0;
        width: 240px;
        z-index: 999;
    }
    .presence-dropdown-header {
        padding: 6px 14px;
        font-size: 11px;
        font-weight: 700;
        color: var(--ms-text-secondary);
        text-transform: uppercase;
        border-bottom: 1px solid var(--ms-border);
        letter-spacing: 0.5px;
    }
    .presence-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        cursor: pointer;
        transition: background 0.15s;
    }
    .presence-option:hover {
        background: var(--ms-bg-hover);
    }
    .presence-option.active {
        background: var(--ms-bg-active);
    }
    .presence-dot-sample {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .presence-dot-sample.online {
        background: var(--ms-green);
    }
    .presence-dot-sample.offline {
        background: #94a3b8;
    }
    .presence-option-text strong {
        display: block;
        font-size: 13px;
        color: var(--ms-text-primary);
    }
    .presence-option-text small {
        display: block;
        font-size: 11px;
        color: var(--ms-text-secondary);
    }

    /* Floating Dock Workspace (Desktop Multi-Chat) */
    .messenger-docked-tray {
        position: fixed;
        bottom: 0;
        right: 20px;
        z-index: 9999;
        display: flex;
        align-items: flex-end;
        gap: 14px;
        pointer-events: none;
    }
    .docked-chat-window {
        pointer-events: auto;
        width: 320px;
        background: var(--ms-bg-card);
        border-radius: 12px 12px 0 0;
        box-shadow: 0 6px 26px rgba(0,0,0,0.22), 0 0 0 1px var(--ms-border);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: height 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        height: 430px;
        max-height: calc(100vh - 80px);
    }
    .docked-chat-window.minimized {
        height: 46px !important;
    }
    .docked-header {
        height: 46px;
        min-height: 46px;
        padding: 0 12px;
        background: var(--ms-bg-card);
        border-bottom: 1px solid var(--ms-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
    }
    .docked-header-user {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 1;
    }
    .docked-avatar-wrap {
        position: relative;
        width: 28px;
        height: 28px;
        flex-shrink: 0;
    }
    .docked-avatar-wrap .avatar {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }
    .docked-avatar-wrap .online-indicator {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 8px;
        height: 8px;
        border: 1.5px solid var(--ms-bg-card);
        border-radius: 50%;
        background: var(--ms-green);
    }
    .docked-header-info {
        min-width: 0;
    }
    .docked-header-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--ms-text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
        min-width: 0;
        overflow-wrap: anywhere;
    }
    .docked-header-status {
        font-size: 10.5px;
        color: var(--ms-text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.1;
    }
    .docked-header-actions {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
    }
    .docked-action-btn {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: transparent;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--ms-text-secondary);
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .docked-action-btn:hover {
        background: var(--ms-bg-hover);
        color: var(--ms-text-primary);
    }
    .docked-unread-badge {
        background: var(--ms-primary);
        color: #ffffff;
        font-size: 10px;
        font-weight: 800;
        padding: 1px 6px;
        border-radius: 10px;
        margin-right: 4px;
        display: none;
    }
    .docked-body {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        background: var(--ms-bg-main);
    }
    .docked-msg-row {
        display: flex;
        flex-direction: column;
        max-width: 82%;
    }
    .docked-msg-row.outgoing {
        align-self: flex-end;
    }
    .docked-msg-row.incoming {
        align-self: flex-start;
    }
    .docked-msg-bubble {
        padding: 7px 12px;
        border-radius: 14px;
        font-size: 13px;
        line-height: 1.4;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .docked-msg-row.incoming .docked-msg-bubble {
        background: var(--ms-bubble-incoming);
        color: var(--ms-text-primary);
        border-bottom-left-radius: 3px;
    }
    .docked-msg-row.outgoing .docked-msg-bubble {
        background: var(--ms-primary);
        color: #ffffff;
        border-bottom-right-radius: 3px;
    }
    .docked-msg-time {
        font-size: 9.5px;
        color: var(--ms-text-secondary);
        margin-top: 2px;
        display: flex;
        align-items: center;
        gap: 3px;
    }
    .docked-msg-row.outgoing .docked-msg-time {
        align-self: flex-end;
    }
    .docked-composer {
        padding: 8px 10px;
        background: var(--ms-bg-card);
        border-top: 1px solid var(--ms-border);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .docked-composer input {
        flex: 1;
        border: none;
        background: var(--ms-bg-input);
        border-radius: 18px;
        padding: 7px 12px;
        font-size: 13px;
        color: var(--ms-text-primary);
        outline: none;
        font-family: inherit;
    }
    .docked-send-btn {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--ms-primary);
        border: none;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: transform 0.1s;
    }
    .docked-send-btn:hover {
        transform: scale(1.08);
    }

    /* Mobile Open Chats Switcher Bar */
    .mobile-open-chats-bar {
        display: none;
        padding: 6px 12px;
        background: var(--ms-bg-card);
        border-bottom: 1px solid var(--ms-border);
        overflow-x: auto;
        gap: 8px;
        align-items: center;
        flex-shrink: 0;
        scrollbar-width: none;
    }
    .mobile-open-chats-bar::-webkit-scrollbar {
        display: none;
    }
    @media (max-width: 768px) {
        .mobile-open-chats-bar {
            display: flex;
        }
        .messenger-docked-tray {
            display: none !important;
        }
    }
    .mobile-chat-pill {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 16px;
        background: var(--ms-bg-input);
        color: var(--ms-text-primary);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        text-decoration: none;
        flex-shrink: 0;
        transition: background 0.15s;
    }
    .mobile-chat-pill.active {
        background: var(--ms-primary);
        color: #ffffff;
    }
    .mobile-chat-pill-avatar {
        width: 20px;
        height: 20px;
        border-radius: 50%;
    }
    .mobile-chat-close {
        background: none;
        border: none;
        color: inherit;
        font-size: 12px;
        cursor: pointer;
        padding: 0 2px;
        margin-left: 2px;
    }

    /* Open in dock button in conversation item */
    .btn-open-dock {
        opacity: 0;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--ms-bg-input);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--ms-text-secondary);
        cursor: pointer;
        transition: opacity 0.15s, background 0.15s;
        flex-shrink: 0;
        margin-left: 4px;
    }
    .conversation-item:hover .btn-open-dock {
        opacity: 1;
    }
    .btn-open-dock:hover {
        background: var(--ms-primary);
        color: #ffffff;
    }
</style>
@endsection

@section('content')
@php
    $currentUser = $currentUser ?? auth()->user();
@endphp
<div class="messenger-wrapper" id="messengerApp">
    <!-- ==============================================
         COLUMN 1: CONVERSATIONS LIST
         ============================================== -->
    <div class="conversations-pane" id="conversationsPane">
        <div class="pane-header">
            <h1 class="pane-title">চ্যাট ও বার্তা</h1>
            <div class="pane-header-actions">
                <!-- Presence status button -->
                <div style="position: relative;">
                    <button type="button" class="icon-circle-btn presence-status-btn" id="presenceStatusBtn" onclick="togglePresenceMenu()" title="উপস্থিতি স্থিতি">
                        <span class="presence-status-dot online" id="myPresenceDot"></span>
                    </button>
                    <div class="presence-dropdown-menu" id="presenceDropdownMenu" style="display: none;">
                        <div class="presence-dropdown-header">উপস্থিতি স্থিতি</div>
                        <div class="presence-option active" id="presenceOptionOnline" onclick="setMyPresence(false)">
                            <span class="presence-dot-sample online"></span>
                            <div class="presence-option-text">
                                <strong>সক্রিয় (Online)</strong>
                                <small>অন্যরা আপনাকে সক্রিয় দেখতে পাবে</small>
                            </div>
                        </div>
                        <div class="presence-option" id="presenceOptionOffline" onclick="setMyPresence(true)">
                            <span class="presence-dot-sample offline"></span>
                            <div class="presence-option-text">
                                <strong>অদৃশ্য (Appear Offline)</strong>
                                <small>মেসেঞ্জার ব্যবহার করলেও অফলাইন দেখাবে</small>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="icon-circle-btn" onclick="openMessengerSoundModal()" title="মেসেঞ্জার সাউন্ড ও রিংটোন সেটিংস">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                </button>

                <button type="button" class="icon-circle-btn" onclick="openNewChatModal()" title="নতুন বার্তা বা গ্রুপ">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Sidebar Search -->
        <div class="conversations-search">
            <div class="search-input-box">
                <span style="display: flex; align-items: center; color: var(--fb-text-secondary);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" id="convSearchInput" placeholder="চ্যাট ও ব্যবহারকারী খুঁজুন..." oninput="handleConvSearch(this.value)">
            </div>
        </div>

        <!-- Active Friends Horizontal Rail -->
        <div class="active-friends-section" id="activeFriendsSection">
            <div class="active-friends-header">
                <div class="active-friends-title">
                    <span>অনলাইন বন্ধুরা</span>
                    <span class="active-friends-badge" id="activeFriendsCount">০</span>
                </div>
                <button type="button" class="active-friends-refresh-btn" onclick="fetchActiveFriends(true)" title="উপস্থিতি রিফ্রেশ করুন">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                </button>
            </div>
            <div class="active-friends-rail" id="activeFriendsRail">
                <div style="font-size: 11.5px; color: var(--ms-text-secondary); padding: 8px 4px;">বন্ধুদের তথ্য লোড হচ্ছে...</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <button class="filter-pill active" onclick="switchConvTab('all', this)">সব</button>
            <button class="filter-pill" onclick="switchConvTab('unread', this)">আনরিড</button>
            <button class="filter-pill" onclick="switchConvTab('pinned', this)">পিন করা</button>
            <button class="filter-pill" onclick="switchConvTab('groups', this)">গ্রুপ</button>
            <button class="filter-pill" onclick="switchConvTab('requests', this)">রিকোয়েস্ট</button>
            <button class="filter-pill" onclick="switchConvTab('archived', this)">আর্কাইভ</button>
        </div>

        <!-- Conversations Stream List -->
        <div class="conversations-list" id="conversationsListContainer">
            @forelse($conversations as $conv)
                @php
                    $cId = is_array($conv) ? $conv['id'] : $conv->id;
                    $isActive = $activeConversation && $activeConversation->id == $cId;
                    $chatTitle = is_array($conv) ? ($conv['title'] ?? 'ব্যবহারকারী') : ($conv->title ?? 'ব্যবহারকারী');
                    $chatAvatar = is_array($conv) ? ($conv['avatar_url'] ?? null) : $conv->avatar_url;
                    $chatInitial = mb_substr($chatTitle, 0, 1);
                    $lastMsg = is_array($conv) ? ($conv['last_message'] ?? null) : $conv->lastMessage;
                    $lastMsgBody = is_array($lastMsg) ? ($lastMsg['body'] ?? '') : ($lastMsg?->body ?? '');
                    $lastMsgSender = is_array($lastMsg) ? ($lastMsg['sender_id'] ?? null) : ($lastMsg?->sender_id ?? null);
                    $unread = is_array($conv) ? ($conv['unread_count'] ?? 0) : ($conv->unread_count ?? 0);
                    $isPinned = is_array($conv) ? ($conv['is_pinned'] ?? false) : false;
                    $isMuted = is_array($conv) ? ($conv['is_muted'] ?? false) : false;
                    $otherUser = is_array($conv) ? ($conv['other_user'] ?? null) : (method_exists($conv, 'getOtherParticipant') ? $conv->getOtherParticipant($currentUser->id) : null);
                    $otherUserId = $otherUser ? (is_array($otherUser) ? ($otherUser['id'] ?? null) : $otherUser->id) : null;
                @endphp
                <a href="{{ route('messages.show', ['id' => $cId]) }}" class="conversation-item {{ $isActive ? 'active' : '' }} {{ $unread > 0 ? 'conv-item-unread' : '' }}" id="convItem-{{ $cId }}" data-id="{{ $cId }}" data-user-id="{{ $otherUserId ?? '' }}" data-title="{{ strtolower($chatTitle) }}">
                    <div class="conv-avatar-wrapper">
                        <div class="avatar" style="width: 48px; height: 48px;">
                            @if($chatAvatar)
                                <img src="{{ $chatAvatar }}" alt="{{ $chatTitle }}">
                            @else
                                {{ $chatInitial }}
                            @endif
                        </div>
                        <div class="online-indicator" id="convPresence-{{ $cId }}"></div>
                    </div>
                    <div class="conv-content">
                        <div class="conv-top-row">
                            <div class="conv-name">
                                {{ $chatTitle }}
                                @if($isPinned)
                                    <span class="badge-pinned" title="পিন করা" style="display: inline-flex; align-items: center; justify-content: center; vertical-align: middle;">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6l.8.8.8-.8v-6H18v-2l-2-2z"/>
                                        </svg>
                                    </span>
                                @endif
                                @if($isMuted)
                                    <span class="badge-muted" title="মিউট করা" style="display: inline-flex; align-items: center; justify-content: center; vertical-align: middle;">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                            <path d="M18.63 13A17.89 17.89 0 0 1 18 8"></path>
                                            <path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9h14"></path>
                                            <path d="M18 8a6 6 0 0 0-9.33-5"></path>
                                            <line x1="1" y1="1" x2="23" y2="23"></line>
                                        </svg>
                                    </span>
                                @endif
                            </div>
                            <div style="display: flex; align-items: center; gap: 4px; flex-shrink: 0;">
                                <span class="conv-time" id="convTime-{{ $cId }}">
                                    @if($conv['last_message_at'] ?? null)
                                        {{ \Carbon\Carbon::parse($conv['last_message_at'])->shortRelativeDiffForHumans() }}
                                    @endif
                                </span>
                                <button type="button" class="btn-open-dock" onclick="event.preventDefault(); event.stopPropagation(); openDockedChat({{ $cId }}, '{{ addslashes($chatTitle) }}', '{{ $chatAvatar ?? '' }}', false);" title="ডক উইন্ডোতে খুলুন">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="conv-bottom-row">
                            <div class="conv-snippet" id="convSnippet-{{ $cId }}">
                                @if($lastMsgBody)
                                    {{ $lastMsgSender == $currentUser->id ? 'আপনি: ' : '' }}{{ Str::limit($lastMsgBody, 28) }}
                                @else
                                    কথোপকথন শুরু করুন...
                                @endif
                            </div>
                            @if($unread > 0)
                                <span class="badge-unread-pill" id="convUnreadBadge-{{ $cId }}">{{ $unread }}</span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div style="text-align: center; padding: 48px 16px; color: var(--fb-text-secondary);">
                    <div style="display: flex; justify-content: center; margin-bottom: 14px;">
                        <div style="width: 58px; height: 58px; border-radius: 50%; background: linear-gradient(135deg, rgba(0, 120, 255, 0.1), rgba(0, 229, 255, 0.1)); display: flex; align-items: center; justify-content: center;">
                            <svg width="30" height="30" viewBox="0 0 28 28" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M14 2C7.373 2 2 7.155 2 13.518c0 3.626 1.745 6.862 4.475 8.974V26l3.37-1.85c1.28.355 2.646.549 4.155.549 6.627 0 12-5.155 12-11.518C26 7.155 20.627 2 14 2zm1.203 15.534l-3.08-3.284-6.012 3.284 6.613-7.02 3.155 3.284 5.937-3.284-6.613 7.02z" fill="url(#emptyConvGrad)"/>
                                <defs>
                                    <linearGradient id="emptyConvGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                                        <stop offset="0%" stop-color="#0078FF"/>
                                        <stop offset="70%" stop-color="#00C6FF"/>
                                        <stop offset="100%" stop-color="#00E5FF"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                    </div>
                    <div style="font-weight: 700; font-size: 15px; color: var(--fb-text-primary);">কোনো কথোপকথন নেই</div>
                    <div style="font-size: 13px; margin-top: 4px;">নতুন বার্তা পাঠাতে উপরের পেন্সিল বাটনে ক্লিক করুন।</div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- ==============================================
         COLUMN 2: ACTIVE CHAT THREAD
         ============================================== -->
    <div class="chat-pane" id="chatPane">
        @if($activeConversation)
            @php
                $isGroup = $activeConversation->isGroup();
                $isSaved = $activeConversation->isSaved();
                $activeOther = $activeConversation->getOtherParticipant($currentUser->id);
                $activeTitle = $isSaved ? 'Saved Messages' : ($isGroup ? ($activeConversation->title ?? 'গ্রুপ চ্যাট') : ($activeOther?->name ?? 'বন্ধু'));
                $activeAvatar = $isGroup ? $activeConversation->avatar_url : ($activeOther?->profile?->avatar_url ?? null);
                $activeRole = $activeConversation->participants->firstWhere('user_id', $currentUser->id)?->role ?? 'member';
            @endphp
            <!-- Mobile Open Chats Switcher Bar -->
            <div class="mobile-open-chats-bar" id="mobileOpenChatsBar"></div>

            <!-- Chat Header -->
            <div class="chat-header">
                <div class="chat-header-user">
                    <button type="button" class="btn-mobile-back" aria-label="ইনবক্সে ফিরে যান" title="ইনবক্সে ফিরে যান" onclick="event.stopPropagation(); window.location.href='{{ route('messages.index') }}'">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                    </button>
                    @if(!$isGroup && !$isSaved && $activeOther)
                        <a href="{{ route('profile.show', ['username' => $activeOther->username ?? $activeOther->id]) }}" class="conv-avatar-wrapper" title="{{ $activeTitle }}-এর প্রোফাইল দেখুন" style="text-decoration: none; cursor: pointer; flex-shrink: 0;">
                            <div class="avatar" style="width: 44px; height: 44px;">
                                @if($activeAvatar)
                                    <img src="{{ $activeAvatar }}" alt="{{ $activeTitle }}">
                                @else
                                    {{ mb_substr($activeTitle, 0, 1) }}
                                @endif
                            </div>
                            <div class="online-indicator" id="headerOnlineDot"></div>
                        </a>
                    @else
                        <div class="conv-avatar-wrapper" onclick="toggleDetailsSidebar()" style="cursor: pointer; flex-shrink: 0;">
                            <div class="avatar" style="width: 44px; height: 44px;">
                                @if($activeAvatar)
                                    <img src="{{ $activeAvatar }}" alt="{{ $activeTitle }}">
                                @else
                                    {{ mb_substr($activeTitle, 0, 1) }}
                                @endif
                            </div>
                            @if(!$isGroup && !$isSaved)
                                <div class="online-indicator" id="headerOnlineDot"></div>
                            @endif
                        </div>
                    @endif
                    <div class="chat-header-info">
                        <div class="chat-header-name">
                            @if(!$isGroup && !$isSaved && $activeOther)
                                <a href="{{ route('profile.show', ['username' => $activeOther->username ?? $activeOther->id]) }}" class="chat-header-name-link" title="{{ $activeTitle }}-এর প্রোফাইল দেখুন">
                                    {{ $activeTitle }}
                                </a>
                            @else
                                <span onclick="toggleDetailsSidebar()" class="chat-header-name-link">{{ $activeTitle }}</span>
                            @endif
                            @if($isGroup)
                                <span class="chat-header-group-badge">গ্রুপ ({{ $activeConversation->participants->count() }})</span>
                            @endif
                        </div>
                        <div class="chat-header-status" onclick="toggleDetailsSidebar()" id="headerPresenceText">
                            @if($isGroup)
                                {{ $activeConversation->participants->count() }} জন সদস্য
                            @elseif($isSaved)
                                সংরক্ষিত বার্তা
                            @else
                                সক্রিয় আছেন · প্রোফাইল দেখুন ↗
                            @endif
                        </div>
                    </div>
                </div>

                <div class="chat-header-actions">
                    @if(!$isGroup && !$isSaved && $activeOther)
                        <a href="{{ route('profile.show', ['username' => $activeOther->username ?? $activeOther->id]) }}" class="icon-circle-btn btn-header-profile" title="{{ $activeTitle }}-এর প্রোফাইল দেখুন" style="text-decoration: none; color: inherit;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </a>
                    @endif
                    <button type="button" class="icon-circle-btn btn-header-search" title="মেসেজ সার্চ করুন" onclick="toggleInChatSearch()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                    @if(!$isSaved)
                        <button type="button" class="icon-circle-btn btn-header-multicall" id="multiFriendCallBtn" title="মাল্টি-ফ্রেন্ড কল (অন্য বন্ধুদের যুক্ত করে কল)" onclick="openMultiFriendCallModal(currentConvId, '{{ addslashes($activeTitle) }}')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </button>
                        <button type="button" class="icon-circle-btn btn-header-audiocall" title="{{ $isGroup ? 'গ্রুপ অডিও কল' : 'অডিও কল' }}" onclick="startCall('{{ $isGroup ? 'group_audio' : 'audio' }}', '{{ addslashes($activeTitle) }}')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </button>
                        <button type="button" class="icon-circle-btn btn-header-videocall" title="{{ $isGroup ? 'গ্রুপ ভিডিও কল' : 'ভিডিও কল' }}" onclick="startCall('{{ $isGroup ? 'group_video' : 'video' }}', '{{ addslashes($activeTitle) }}')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                            </svg>
                        </button>
                    @endif
                    <button type="button" class="icon-circle-btn btn-header-details" title="চ্যাট বিবরণ ও ফাইলসমূহ" onclick="toggleDetailsSidebar()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- In-Chat Search Bar -->
            <div class="in-chat-search-bar" id="inChatSearchBar" style="display: none;">
                <span style="display: flex; align-items: center; color: var(--fb-text-secondary);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" id="inChatMessageSearchInput" placeholder="এই চ্যাটে বার্তা অনুসন্ধান করুন..." style="flex: 1; border: none; background: transparent; outline: none; font-size: 14px;" oninput="searchInChatMessages(this.value)">
                <button type="button" onclick="toggleInChatSearch()" style="background: none; border: none; font-size: 16px; cursor: pointer; color: var(--fb-text-secondary); display: flex; align-items: center;">✕</button>
            </div>

            <!-- Messages Stream -->
            <div class="messages-stream" id="messagesStream">
                @forelse($messages as $msg)
                    @php
                        $mId = is_array($msg) ? $msg['id'] : $msg->id;
                        $mBody = is_array($msg) ? ($msg['body'] ?? '') : $msg->body;
                        $isOut = is_array($msg) ? ($msg['is_mine'] ?? ($msg['sender']['id'] == $currentUser->id)) : ($msg->sender_id === $currentUser->id);
                        $mTime = is_array($msg) ? ($msg['sent_at'] ? \Carbon\Carbon::parse($msg['sent_at'])->format('h:i A') : '') : ($msg->sent_at ? $msg->sent_at->format('h:i A') : '');
                        $mType = is_array($msg) ? ($msg['type'] ?? 'text') : $msg->type;
                        $isDeleted = is_array($msg) ? ($msg['is_deleted'] ?? false) : $msg->is_deleted_for_everyone;
                        $isForwarded = is_array($msg) ? ($msg['is_forwarded'] ?? false) : $msg->is_forwarded;
                        $isEdited = is_array($msg) ? ($msg['is_edited'] ?? false) : $msg->is_edited;
                        $deliveryStatus = is_array($msg) ? ($msg['delivery_status'] ?? 'sent') : $msg->delivery_status;
                        $replyPreview = is_array($msg) ? ($msg['reply_preview'] ?? null) : null;
                        $mediaItems = is_array($msg) ? ($msg['media'] ?? []) : [];
                        $reactions = is_array($msg) ? ($msg['reactions'] ?? ['counts' => [], 'total' => 0, 'my_reaction' => null]) : ['counts' => [], 'total' => 0, 'my_reaction' => null];
                    @endphp

                    @if($mType === 'system' || $mType === 'call')
                        @php
                            $callMetaId = is_array($msg) ? ($msg['metadata']['call_id'] ?? $mId) : ($msg->metadata['call_id'] ?? $mId);
                        @endphp
                        <div class="system-message-row" id="callSummaryRow-{{ $callMetaId }}" data-call-id="{{ $callMetaId }}" style="{{ $mType === 'call' ? 'background: rgba(24,119,242,0.1); color: var(--fb-primary); padding: 8px 16px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; margin: 8px auto;' : '' }}">{{ $mBody }}</div>
                    @else
                        <div class="message-row {{ $isOut ? 'outgoing' : 'incoming' }}" id="messageRow-{{ $mId }}" data-id="{{ $mId }}">
                            @if(!$isOut && $isGroup)
                                <div class="avatar" style="width: 32px; height: 32px; flex-shrink: 0; align-self: flex-end;" title="{{ is_array($msg) ? ($msg['sender']['name'] ?? '') : $msg->sender?->name }}">
                                    @if(is_array($msg) && !empty($msg['sender']['avatar_url']))
                                        <img src="{{ $msg['sender']['avatar_url'] }}" alt="">
                                    @else
                                        {{ mb_substr(is_array($msg) ? ($msg['sender']['name'] ?? 'U') : 'U', 0, 1) }}
                                    @endif
                                </div>
                            @endif

                            <div class="message-bubble-wrapper">
                                @if(!$isOut && $isGroup)
                                    <div class="message-sender-name">{{ is_array($msg) ? ($msg['sender']['name'] ?? '') : $msg->sender?->name }}</div>
                                @endif

                                <!-- Hover Quick Action Toolbar -->
                                <div class="message-action-toolbar">
                                    <button type="button" class="toolbar-btn" title="লাইক" onclick="toggleReaction({{ $mId }}, '👍')">👍</button>
                                    <button type="button" class="toolbar-btn" title="লাভ" onclick="toggleReaction({{ $mId }}, '❤️')">❤️</button>
                                    <button type="button" class="toolbar-btn" title="হাসি" onclick="toggleReaction({{ $mId }}, '😂')">😂</button>
                                    <button type="button" class="toolbar-btn" title="বিস্ময়" onclick="toggleReaction({{ $mId }}, '😮')">😮</button>
                                    <button type="button" class="toolbar-btn" title="কপি করুন" onclick="copyMessageText('{{ addslashes($mBody) }}')">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    </button>
                                    <button type="button" class="toolbar-btn" title="রিপ্লাই" onclick="initReply({{ $mId }}, '{{ addslashes($mBody) }}', '{{ is_array($msg) ? ($msg['sender']['name'] ?? 'User') : ($msg->sender?->name ?? 'User') }}')">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 17 4 12 9 7"></polyline><path d="M20 18v-2a4 4 0 0 0-4-4H4"></path></svg>
                                    </button>
                                    <button type="button" class="toolbar-btn" title="ফরোয়ার্ড" onclick="openForwardModal({{ $mId }})">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 17 20 12 15 7"></polyline><path d="M4 18v-2a4 4 0 0 1 4-4h12"></path></svg>
                                    </button>
                                    @if($isOut && !$isDeleted)
                                        <button type="button" class="toolbar-btn" title="এডিট" onclick="openEditMessageModal({{ $mId }}, '{{ addslashes($mBody) }}')">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        </button>
                                    @endif
                                    <button type="button" class="toolbar-btn" title="পিন করুন" onclick="pinMessage({{ $mId }})">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="17" x2="12" y2="22"></line><path d="M5 17h14v-2l-2-2V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v8l-2 2v2z"></path></svg>
                                    </button>
                                    <button type="button" class="toolbar-btn" title="সংরক্ষণ করুন" onclick="saveMessage({{ $mId }})">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                                    </button>
                                    <button type="button" class="toolbar-btn" title="ডিলিট" onclick="promptDeleteMessage({{ $mId }}, {{ $isOut ? 'true' : 'false' }})">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                    <button type="button" class="toolbar-btn" title="রিপোর্ট" onclick="openReportModal('message', {{ $mId }})">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                    </button>
                                </div>

                                <div class="message-bubble">
                                    @if($isForwarded)
                                        <div class="forwarded-indicator" style="display: flex; align-items: center; gap: 4px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 17 20 12 15 7"></polyline><path d="M4 18v-2a4 4 0 0 1 4-4h12"></path></svg>
                                            <span>ফরোয়ার্ড করা বার্তা</span>
                                        </div>
                                    @endif

                                    @if($replyPreview)
                                        <div class="reply-preview-box" onclick="scrollToMessage({{ $replyPreview['id'] }})">
                                            <strong>{{ $replyPreview['sender_name'] }}:</strong> {{ Str::limit($replyPreview['body'], 40) }}
                                        </div>
                                    @endif

                                    @if($isDeleted)
                                        <em style="opacity: 0.7;">🚫 এই বার্তাটি মুছে ফেলা হয়েছে।</em>
                                    @else
                                        @php
                                            $showBody = true;
                                            $trimmedBody = trim($mBody);
                                            $metadata = is_array($msg) ? ($msg['metadata'] ?? []) : ($msg->metadata ?? []);
                                            $audioUrl = $metadata['audio_url'] ?? null;
                                            $audioDur = (int) ($metadata['duration'] ?? 0);
                                            $isVoice = ($mType === 'voice') || !empty($audioUrl);

                                            if ($isVoice) {
                                                $showBody = false;
                                            } elseif (!empty($mediaItems) && !empty($trimmedBody)) {
                                                foreach ($mediaItems as $med) {
                                                    $fn = trim($med['name'] ?? $med['filename'] ?? '');
                                                    $baseFn = isset($med['original_path']) ? basename($med['original_path']) : '';
                                                    if ($trimmedBody === $fn || $trimmedBody === $baseFn || ($fn !== '' && str_ends_with($trimmedBody, $fn))) {
                                                        $showBody = false;
                                                        break;
                                                    }
                                                }
                                                if ($showBody && preg_match('/\.(png|jpe?g|webp|gif|avif|pdf|docx?|zip|mp4)$/i', $trimmedBody)) {
                                                    $showBody = false;
                                                }
                                            }
                                        @endphp

                                        @if($isVoice)
                                            @php
                                                $streamUrl = '/api/v1/messages/'.$mId.'/voice';
                                            @endphp
                                            <div class="voice-message-player" id="voicePlayer-{{ $mId }}" data-url="{{ $streamUrl }}">
                                                <button type="button" class="voice-play-btn" onclick="toggleVoicePlay({{ $mId }}, '{{ $streamUrl }}')" id="voicePlayBtn-{{ $mId }}" title="প্লে/পজ">
                                                    <svg id="voicePlayIcon-{{ $mId }}" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                                    <svg id="voicePauseIcon-{{ $mId }}" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display:none;"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
                                                </button>
                                                <div class="voice-player-body">
                                                    <div class="voice-progress-track" onclick="seekVoicePlay({{ $mId }}, event)" id="voiceTrack-{{ $mId }}">
                                                        <div class="voice-progress-fill" id="voiceFill-{{ $mId }}" style="width: 0%;"></div>
                                                    </div>
                                                    <div class="voice-meta-info">
                                                        <span id="voiceTime-{{ $mId }}">0:00</span>
                                                        <span>{{ $audioDur > 0 ? gmdate('i:s', $audioDur) : '' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if($showBody && !empty($trimmedBody))
                                            {!! nl2br(e($mBody)) !!}
                                        @endif


                                        <!-- Media Files Grid -->
                                        @if(!empty($mediaItems))
                                            <div class="message-media-grid">
                                                @foreach($mediaItems as $media)
                                                    @php
                                                        $mime = $media['mime_type'] ?? '';
                                                        $isImage = str_contains($mime, 'image/') || preg_match('/\.(png|jpe?g|webp|gif|avif)$/i', $media['url'] ?? $media['name'] ?? $media['filename'] ?? '');
                                                        $isVideo = str_contains($mime, 'video/') || preg_match('/\.(mp4|webm|mov)$/i', $media['url'] ?? $media['name'] ?? $media['filename'] ?? '');
                                                        $previewUrl = $media['preview_url'] ?? $media['urls']['medium'] ?? $media['urls']['original'] ?? $media['url'] ?? (!empty($media['id']) ? "/api/v1/messages/attachments/{$media['id']}/view" : '');
                                                        $fullUrl = $media['original_url'] ?? $media['urls']['original'] ?? $media['url'] ?? $previewUrl;
                                                        $downloadUrl = $media['download_url'] ?? $media['urls']['download'] ?? (!empty($media['id']) ? "/api/v1/messages/attachments/{$media['id']}/download" : $fullUrl);
                                                        $mediaName = $media['name'] ?? $media['filename'] ?? 'ছবি';
                                                    @endphp
                                                    @if($isImage)
                                                        <div class="media-preview-item" onclick="openLightbox('{{ $fullUrl }}')">
                                                            <img src="{{ $previewUrl }}" alt="{{ $mediaName }}" loading="lazy" onerror="this.onerror=null; this.src='{{ $fullUrl }}'">
                                                        </div>
                                                    @elseif($isVideo)
                                                        <div class="media-preview-item">
                                                            <video controls src="{{ $fullUrl }}"></video>
                                                        </div>
                                                    @else
                                                        <a href="{{ $downloadUrl }}" class="file-attachment-card" target="_blank" download>
                                                            <span style="display: flex; align-items: center; color: var(--fb-primary);">
                                                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                                    <polyline points="14 2 14 8 20 8"></polyline>
                                                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                                                    <polyline points="10 9 9 9 8 9"></polyline>
                                                                </svg>
                                                            </span>
                                                            <div style="flex: 1; min-width: 0;">
                                                                <div style="font-weight: 700; font-size: 13px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">{{ $mediaName }}</div>
                                                                <div style="font-size: 11px; opacity: 0.8;">{{ round(($media['size'] ?? 1024) / 1024, 1) }} KB</div>
                                                            </div>
                                                            <span style="display: flex; align-items: center; color: var(--fb-text-secondary);">
                                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                                    <polyline points="7 10 12 15 17 10"></polyline>
                                                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                                                </svg>
                                                            </span>
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>

                                <!-- Meta & Delivery Row -->
                                <div class="message-meta-row">
                                    <span>{{ $mTime }}</span>
                                    @if($isEdited) <span class="edited-badge">(সম্পাদিত)</span> @endif
                                    @if($isOut)
                                        <span class="delivery-check {{ $deliveryStatus === 'seen' ? 'seen' : '' }}">
                                            @if($deliveryStatus === 'seen') ✓✓ @elseif($deliveryStatus === 'delivered') ✓✓ @else ✓ @endif
                                        </span>
                                    @endif
                                </div>

                                <!-- Reactions Row -->
                                @if(!empty($reactions['counts']))
                                    <div class="reactions-badge-row" onclick="openReactionsModal({{ $mId }})">
                                        @foreach($reactions['counts'] as $rxEmoji => $rxCount)
                                            <span>{{ $rxEmoji }} {{ $rxCount }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="empty-conversation-hero" id="emptyChatPlaceholder">
                        <div class="hero-avatar-wrapper">
                            <div class="avatar hero-avatar">
                                @if($activeAvatar)
                                    <img src="{{ $activeAvatar }}" alt="{{ $activeTitle }}">
                                @else
                                    {{ mb_substr($activeTitle, 0, 1) }}
                                @endif
                            </div>
                            @if(!$isGroup && !$isSaved)
                                <div class="online-indicator hero-online-dot"></div>
                            @endif
                        </div>
                        <h2 class="hero-title">{{ $activeTitle }}</h2>
                        <div class="hero-subtitle">
                            @if($isGroup)
                                গ্রুপ চ্যাট · {{ $activeConversation->participants->count() }} জন সদস্য
                            @elseif($isSaved)
                                আপনার ব্যক্তিগত সংরক্ষিত নোট ও বার্তা
                            @else
                                আপনারা মেসেঞ্জারে যুক্ত আছেন
                            @endif
                        </div>
                        <div class="hero-wave-prompt" onclick="sendWaveGreeting()">
                            <span class="wave-hand">👋</span>
                            <span>কথোপকথন শুরু করতে একটি হাই পাঠান</span>
                        </div>
                    </div>
                @endforelse

                <!-- Realtime Typing Indicator -->
                <div class="typing-bubble" id="typingIndicator" style="display: none;">
                    <span id="typingUserName">ব্যবহারকারী</span> টাইপ করছেন
                    <div class="typing-dots"><span></span><span></span><span></span></div>
                </div>
            </div>

            <!-- Composer & Inputs -->
            <div class="chat-composer-wrapper">
                <!-- Reply Banner -->
                <div class="composer-reply-banner" id="composerReplyBanner" style="display: none;">
                    <div>
                        <span style="font-weight: 700; color: var(--ms-primary);">রিপ্লাই দিচ্ছেন: </span>
                        <span id="replyBannerTargetText" style="color: var(--ms-text-secondary);"></span>
                    </div>
                    <button type="button" onclick="cancelReply()" style="background: none; border: none; font-size: 16px; cursor: pointer;">✕</button>
                </div>

                <div class="chat-footer">
                    <!-- File Attachment Button -->
                    <button type="button" class="ms-composer-btn" title="ছবি, ভিডিও বা ফাইল সংযুক্ত করুন" onclick="triggerAttachmentUpload()">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                        </svg>
                    </button>
                    <input type="file" id="attachmentFileInput" style="display: none;" multiple onchange="handleAttachmentFiles(this.files)">

                    <!-- Text Composer Box (Capsule Pill with embedded emoji picker) -->
                    <div class="chat-textarea-box" id="composerTextBox">
                        <textarea class="chat-textarea" id="chatMessageInput" placeholder="বার্তা লিখুন..." rows="1" onkeydown="handleComposerKeydown(event)" oninput="handleComposerInput(this)"></textarea>
                        <button type="button" class="ms-input-icon-btn" title="ইমোজি" onclick="toggleEmojiPicker()">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                <line x1="15" y1="9" x2="15.01" y2="9"></line>
                            </svg>
                        </button>
                    </div>

                    <!-- Live Voice Recording UI -->
                    <div class="voice-recording-bar" id="voiceRecordingBar">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div class="voice-record-pulse"></div>
                            <span id="voiceRecordTimer" style="font-weight: 700; font-size: 13px; color: #ef4444;">0:00</span>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" onclick="cancelVoiceRecording()" style="background:none; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; color:#ef4444;" title="বাতিল করুন">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="stopAndSendVoiceRecording()" style="background:#ef4444; color:white; border:none; border-radius:50%; width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer;" title="ভয়েস পাঠান">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Voice Recorder Toggle Button -->
                    <button type="button" class="ms-composer-btn" id="voiceRecordBtn" title="ভয়েস রেকর্ড করুন" onclick="startVoiceRecording()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                            <line x1="12" y1="19" x2="12" y2="22"></line>
                        </svg>
                    </button>

                    <!-- Send Button -->
                    <button type="button" class="btn-send-message" id="btnSendMessage" title="বার্তা পাঠান">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        @else
            <!-- No active conversation selected placeholder (Enterprise Messenger Hero) -->
            <div class="empty-chat-welcome-container">
                <div class="empty-chat-welcome-card">
                    <div class="empty-chat-hero-icon-wrap">
                        <svg width="88" height="88" viewBox="0 0 36 36" fill="none">
                            <defs>
                                <linearGradient id="msgHeroSelectGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#0078FF" />
                                    <stop offset="50%" stop-color="#00C6FF" />
                                    <stop offset="100%" stop-color="#00E5FF" />
                                </linearGradient>
                                <filter id="msgHeroSelectShadow" x="-20%" y="-20%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#0084ff" flood-opacity="0.25"/>
                                </filter>
                            </defs>
                            <path filter="url(#msgHeroSelectShadow)" fill="url(#msgHeroSelectGrad)" d="M18 2C9.163 2 2 8.716 2 17c0 4.717 2.33 8.91 5.98 11.644V34l5.127-2.82c1.558.432 3.197.664 4.893.664 8.837 0 16-6.716 16-15S26.837 2 18 2z"/>
                            <path fill="#ffffff" d="M19.467 19.987l-3.905-4.167-7.622 4.167 8.384-8.905 4.025 4.166 7.502-4.166-8.384 8.905z"/>
                        </svg>
                    </div>
                    <h2 class="empty-chat-welcome-title">আপনার চ্যাট নির্বাচন করুন</h2>
                    <p class="empty-chat-welcome-desc">
                        বাম পাশের তালিকা থেকে একটি কনভার্সেশন নির্বাচন করুন অথবা বন্ধুদের সাথে যুক্ত হতে একটি নতুন বার্তা বা গ্রুপ শুরু করুন।
                    </p>
                    <div class="empty-chat-actions-row">
                        <button type="button" class="btn-ms-hero-primary" onclick="openNewChatModal()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            <span>নতুন বার্তা পাঠান</span>
                        </button>
                        <button type="button" class="btn-ms-hero-secondary" onclick="openNewGroupModal()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <span>নতুন গ্রুপ চ্যাট</span>
                        </button>
                    </div>

                    <!-- Enterprise Security / Feature Indicators -->
                    <div class="empty-chat-features-grid">
                        <div class="empty-chat-feature-item">
                            <span class="ec-feat-icon">🔒</span>
                            <div>
                                <div class="ec-feat-title">এন্ড-টু-এন্ড সুরক্ষিত</div>
                                <div class="ec-feat-sub">আপনার সকল ব্যক্তিগত বার্তা সম্পূর্ণ নিরাপদ</div>
                            </div>
                        </div>
                        <div class="empty-chat-feature-item">
                            <span class="ec-feat-icon">⚡</span>
                            <div>
                                <div class="ec-feat-title">রিয়েল-টাইম বার্তা</div>
                                <div class="ec-feat-sub">তাৎক্ষণিক বিতরণ ও টাইপিং স্ট্যাটাস</div>
                            </div>
                        </div>
                        <div class="empty-chat-feature-item">
                            <span class="ec-feat-icon">📞</span>
                            <div>
                                <div class="ec-feat-title">এইচডি অডিও ও ভিডিও</div>
                                <div class="ec-feat-sub">বন্ধুদের সাথে বিনামূল্যে উচ্চমানের কল</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- ==============================================
         COLUMN 3: RIGHT CHAT DETAILS SIDEBAR
         ============================================== -->
    @if($activeConversation)
        <!-- Backdrop for slide-over drawer on tablet / mobile -->
        <div class="details-drawer-backdrop" id="detailsDrawerBackdrop" onclick="toggleDetailsSidebar()"></div>

        <div class="details-sidebar-pane" id="detailsSidebarPane">
            <!-- Drawer Header for tablet and mobile screens -->
            <div class="details-drawer-header">
                <span style="font-weight: 800; font-size: 15px; color: var(--fb-text-primary);">চ্যাট বিবরণ</span>
                <button type="button" class="icon-circle-btn" style="width: 32px; height: 32px; font-size: 15px;" onclick="toggleDetailsSidebar()" title="বন্ধ করুন">✕</button>
            </div>

            <!-- Profile Card (Messenger Style) -->
            <div class="details-profile-card">
                <div class="avatar" style="width: 76px; height: 76px; margin: 0 auto 12px; font-size: 28px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    @if($activeAvatar)
                        <img src="{{ $activeAvatar }}" alt="{{ $activeTitle }}">
                    @else
                        {{ mb_substr($activeTitle, 0, 1) }}
                    @endif
                </div>
                <h3 style="font-size: 18px; font-weight: 800; color: var(--ms-text-primary); margin-bottom: 2px;">{{ $activeTitle }}</h3>
                @if(!$isGroup && $activeOther)
                    <div style="font-size: 13px; color: var(--ms-text-secondary); margin-bottom: 6px;">{{ '@'.$activeOther->username }}</div>
                    @if($activeOther->profile?->bio)
                        <p style="font-size: 12px; color: var(--ms-text-secondary); padding: 0 10px; margin-bottom: 10px;">{{ $activeOther->profile->bio }}</p>
                    @endif
                @elseif($isGroup && $activeConversation->description)
                    <p style="font-size: 13px; color: var(--ms-text-secondary); margin-top: 6px; margin-bottom: 10px;">{{ $activeConversation->description }}</p>
                @endif

                <!-- Messenger 3 Quick Action Buttons: Profile/Add, Mute, Search -->
                <div class="details-quick-actions">
                    @if(!$isGroup && $activeOther)
                        <a href="{{ route('profile.show', ['username' => $activeOther->username]) }}" class="details-quick-action-item" title="প্রোফাইল দেখুন">
                            <div class="details-quick-action-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <span>প্রোফাইল</span>
                        </a>
                    @elseif($isGroup && in_array($activeRole, ['admin', 'moderator']))
                        <div class="details-quick-action-item" onclick="openAddMemberModal()" title="সদস্য যোগ করুন">
                            <div class="details-quick-action-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                </svg>
                            </div>
                            <span>সদস্য যোগ</span>
                        </div>
                    @endif

                    <div class="details-quick-action-item" onclick="openMuteModal()" title="মিউট করুন">
                        <div class="details-quick-action-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                                <path d="M17 17H3v-2l2-2V9a7 7 0 0 1 .74-3.15"></path>
                                <path d="M9 17v1a3 3 0 0 0 6 0v-1"></path>
                                <path d="M10.26 4.74A7 7 0 0 1 19 9v4l1.2 1.2"></path>
                            </svg>
                        </div>
                        <span>মিউট</span>
                    </div>

                    <div class="details-quick-action-item" onclick="toggleInChatSearch()" title="খুঁজুন">
                        <div class="details-quick-action-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </div>
                        <span>খুঁজুন</span>
                    </div>
                </div>
            </div>

            <!-- Actions Section -->
            <div class="details-nav-section">
                <div class="details-section-title">চ্যাট অপশনস</div>
                <button type="button" class="details-menu-btn" onclick="toggleInChatSearch()">
                    <span class="menu-icon-circle">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <span>চ্যাটে সার্চ করুন</span>
                </button>
                <button type="button" class="details-menu-btn" onclick="openMuteModal()">
                    <span class="menu-icon-circle">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                            <path d="M17 17H3v-2l2-2V9a7 7 0 0 1 .74-3.15"></path>
                            <path d="M9 17v1a3 3 0 0 0 6 0v-1"></path>
                            <path d="M10.26 4.74A7 7 0 0 1 19 9v4l1.2 1.2"></path>
                        </svg>
                    </span>
                    <span>নোটিফিকেশন মিউট করুন</span>
                </button>
                <button type="button" class="details-menu-btn" onclick="togglePinActiveConv()">
                    <span class="menu-icon-circle">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="17" x2="12" y2="22"></line>
                            <path d="M5 17h14v-2l-2-2V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v8l-2 2v2z"></path>
                        </svg>
                    </span>
                    <span>কনভার্সন পিন করুন</span>
                </button>
                <button type="button" class="details-menu-btn" onclick="toggleArchiveActiveConv()">
                    <span class="menu-icon-circle">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="21 8 21 21 3 21 3 8"></polyline>
                            <rect x="1" y="3" width="22" height="5"></rect>
                            <line x1="10" y1="12" x2="14" y2="12"></line>
                        </svg>
                    </span>
                    <span>চ্যাট আর্কাইভ করুন</span>
                </button>
                <button type="button" class="details-menu-btn" onclick="markCurrentConvUnread()">
                    <span class="menu-icon-circle">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </span>
                    <span>অপঠিত হিসেবে চিহ্নিত করুন</span>
                </button>
                <button type="button" class="details-menu-btn" onclick="openCallHistoryModal()">
                    <span class="menu-icon-circle">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 14 14"></polyline>
                        </svg>
                    </span>
                    <span>কল হিস্ট্রি দেখুন</span>
                </button>
            </div>

            <!-- Group Management Section (if group) -->
            @if($isGroup)
                <div class="details-nav-section">
                    <div class="details-section-title" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>সদস্যবৃন্দ ({{ $activeConversation->participants->count() }})</span>
                        @if($activeRole === 'admin')
                            <button type="button" onclick="openAddMemberModal()" style="background: none; border: none; color: var(--fb-primary); font-size: 12px; font-weight: 700; cursor: pointer;">+ যুক্ত করুন</button>
                        @endif
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach($activeConversation->participants as $part)
                            @php $pUser = $part->user; @endphp
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div class="avatar" style="width: 32px; height: 32px;">
                                        @if($pUser->profile?->avatar_url)
                                            <img src="{{ $pUser->profile->avatar_url }}" alt="">
                                        @else
                                            {{ mb_substr($pUser->name, 0, 1) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; font-size: 13px; color: var(--fb-text-primary);">{{ $pUser->name }}</div>
                                        <div style="font-size: 11px; color: var(--fb-text-secondary);">{{ $part->role === 'admin' ? 'অ্যাডমিন' : 'সদস্য' }}</div>
                                    </div>
                                </div>
                                @if($activeRole === 'admin' && $pUser->id !== $currentUser->id)
                                    <button type="button" onclick="removeGroupMember({{ $pUser->id }}, '{{ addslashes($pUser->name) }}')" style="background: none; border: none; color: #ef4444; font-size: 11px; cursor: pointer;">রিমুভ</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Shared Content Tabs -->
            <div class="details-nav-section">
                <div class="details-section-title">শেয়ারকৃত কনটেন্ট</div>
                <div style="display: flex; gap: 6px; margin-bottom: 10px;">
                    <button type="button" class="filter-pill active" onclick="loadSharedTab('media', this)">মিডিয়া</button>
                    <button type="button" class="filter-pill" onclick="loadSharedTab('files', this)">ফাইলস</button>
                    <button type="button" class="filter-pill" onclick="loadSharedTab('links', this)">লিঙ্কস</button>
                </div>
                <div id="sharedContentBox">
                    <div class="shared-media-grid" id="sharedMediaGrid">
                        <!-- Loaded dynamically via API -->
                    </div>
                    <div id="sharedFilesList" style="display: none;"></div>
                    <div id="sharedLinksList" style="display: none;"></div>
                </div>
            </div>

            <!-- Privacy & Danger Zone -->
            <div class="details-nav-section">
                <div class="details-section-title">গোপনীয়তা ও সহায়তা</div>
                <button type="button" class="details-menu-btn warning" onclick="clearCurrentChatHistory()">
                    <span class="menu-icon-circle">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </span>
                    <span>চ্যাট হিস্ট্রি ক্লিয়ার করুন</span>
                </button>
                @if(!$isGroup && $activeOther)
                    <button type="button" class="details-menu-btn danger" onclick="promptBlockUser({{ $activeOther->id }}, '{{ addslashes($activeOther->name) }}')">
                        <span class="menu-icon-circle">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                            </svg>
                        </span>
                        <span>ব্যবহারকারীকে ব্লক করুন</span>
                    </button>
                @endif
                <button type="button" class="details-menu-btn danger" onclick="openReportModal()">
                    <span class="menu-icon-circle">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                            <line x1="4" y1="22" x2="4" y2="15"></line>
                        </svg>
                    </span>
                    <span>রিপোর্ট করুন</span>
                </button>
                @if($isGroup)
                    <button type="button" class="details-menu-btn danger" onclick="confirmLeaveGroup()">
                        <span class="menu-icon-circle">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </span>
                        <span>গ্রুপ ত্যাগ করুন</span>
                    </button>
                @endif
                <button type="button" class="details-menu-btn danger" onclick="deleteCurrentConversation()">
                    <span class="menu-icon-circle">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </span>
                    <span>কনভার্সেশন ডিলিট করুন</span>
                </button>
            </div>
        </div>
    @endif
</div>

<!-- Multi-Chat Floating Dock Workspace (Desktop) -->
<div class="messenger-docked-tray" id="messengerDockedTray"></div>

<!-- ==============================================
     MODALS
     ============================================== -->

<!-- 1. New Message / Group Modal -->
<div class="chat-modal-overlay" id="newChatModal">
    <div class="chat-modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--ms-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 18px; font-weight: 800; color: var(--ms-text-primary); margin: 0;">নতুন বার্তা বা গ্রুপ</h3>
            <button type="button" class="icon-circle-btn" style="width: 32px; height: 32px; font-size: 15px;" onclick="closeChatModal('newChatModal')" title="বন্ধ করুন">✕</button>
        </div>
        <div style="padding: 16px 20px;">
            <!-- Segmented Control Mode Switcher -->
            <div class="modal-segmented-control">
                <button type="button" class="modal-segmented-tab active" id="tabModeDirect" onclick="switchNewChatMode('direct')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>১-অন-১ চ্যাট</span>
                </button>
                <button type="button" class="modal-segmented-tab" id="tabModeGroup" onclick="switchNewChatMode('group')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>নতুন গ্রুপ চ্যাট</span>
                </button>
            </div>

            <!-- Direct User Search Section -->
            <div id="newChatDirectSection">
                <!-- Advanced Search Capsule Input -->
                <div class="ms-search-capsule">
                    <svg class="search-icon ms-capsule-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="position: static !important; transform: none !important; left: auto !important; top: auto !important; margin: 0 !important; flex-shrink: 0;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="newChatUserSearchInput" placeholder="বন্ধুর নাম বা ইউজারনেম দিয়ে খুঁজুন..." autocomplete="off" oninput="handleNewChatSearchInput(this)">
                    <button type="button" class="search-clear-btn" id="newChatSearchClearBtn" onclick="clearNewChatSearch()" title="মুছুন">✕</button>
                </div>

                <div class="search-results-container" id="newChatUserResults">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>

            <!-- Group Creation Form -->
            <div id="newChatGroupSection" style="display: none;">
                <div class="ms-search-capsule" style="margin-bottom: 10px;">
                    <svg class="search-icon ms-capsule-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="position: static !important; transform: none !important; left: auto !important; top: auto !important; margin: 0 !important; flex-shrink: 0;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                    <input type="text" id="groupTitleInput" placeholder="গ্রুপের নাম লিখুন..." style="width: 100%;">
                </div>
                <div class="ms-search-capsule" style="border-radius: 14px; margin-bottom: 10px; padding: 8px 14px;">
                    <textarea id="groupDescInput" placeholder="গ্রুপের বিবরণ (ঐচ্ছিক)..." style="width: 100%; border: none; background: transparent; outline: none; resize: none; font-size: 13px; height: 42px; font-family: inherit; color: var(--ms-text-primary);"></textarea>
                </div>
                <div class="ms-search-capsule" style="margin-bottom: 10px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="flex-shrink: 0; color: var(--ms-text-secondary);"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <input type="url" id="groupAvatarUrlInput" placeholder="গ্রুপ ছবি URL (ঐচ্ছিক)..." style="width: 100%; font-size: 12.5px;">
                </div>
                <!-- Search friends inside group creation -->
                <div class="ms-search-capsule" style="margin-bottom: 10px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="flex-shrink: 0; color: var(--ms-text-secondary);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="groupFriendSearchInput" placeholder="সদস্য খুঁজতে নাম লিখুন..." oninput="filterGroupFriends(this.value)" style="width: 100%; font-size: 12.5px;">
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 700; font-size: 12.5px; color: var(--ms-text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">সদস্য নির্বাচন করুন:</span>
                    <span id="groupSelectedCount" style="font-size: 12px; font-weight: 700; color: var(--ms-primary); background: var(--ms-bg-active); padding: 2px 8px; border-radius: 10px;">নির্বাচিত: ০ জন</span>
                </div>
                <div id="groupMembersSelectionList" style="max-height: 180px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; border: 1px solid var(--ms-border); border-radius: 12px; padding: 10px; background: var(--ms-bg-input);"></div>
                <div style="display: flex; gap: 10px; margin-top: 14px;">
                    <button type="button" class="icon-circle-btn" style="flex: 1; border-radius: 20px; font-weight: 700; height: 42px;" onclick="closeChatModal('newChatModal')">বাতিল</button>
                    <button type="button" id="btnSubmitCreateGroup" class="btn-fb-primary" style="flex: 2; border-radius: 20px; padding: 10px; font-weight: 700; justify-content: center; background: var(--ms-primary); height: 42px;" onclick="submitCreateGroup()">গ্রুপ তৈরি করুন</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. Forward Message Modal -->
<div class="chat-modal-overlay" id="forwardModal">
    <div class="chat-modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 17px; font-weight: 800;">বার্তা ফরোয়ার্ড করুন</h3>
            <button type="button" onclick="closeChatModal('forwardModal')" style="background: none; border: none; font-size: 18px; cursor: pointer;">✕</button>
        </div>
        <div style="padding: 16px 20px;">
            <p style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 12px;">যেসব চ্যাটে এই বার্তাটি পাঠাতে চান তা নির্বাচন করুন:</p>
            <div id="forwardConversationsList" style="max-height: 240px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                @foreach($conversations as $c)
                    @php
                        $targetId = is_array($c) ? $c['id'] : $c->id;
                        $targetTitle = is_array($c) ? ($c['title'] ?? 'চ্যাট') : ($c->title ?? 'চ্যাট');
                    @endphp
                    <label style="display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; cursor: pointer; background: var(--fb-bg);">
                        <input type="checkbox" name="forward_targets[]" value="{{ $targetId }}" style="width: 18px; height: 18px;">
                        <span style="font-weight: 700; font-size: 14px;">{{ $targetTitle }}</span>
                    </label>
                @endforeach
            </div>
            <button type="button" class="btn-fb-primary" style="width: 100%; margin-top: 16px; border-radius: 8px; justify-content: center;" onclick="submitForwardMessage()">ফরোয়ার্ড করুন</button>
        </div>
    </div>
</div>

<!-- 3. Edit Message Modal -->
<div class="chat-modal-overlay" id="editMessageModal">
    <div class="chat-modal-box" style="max-width: 480px; border-radius: 16px; overflow: hidden; padding: 0;">
        <div class="jj-edit-modal-header" style="padding: 14px 18px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 34px; height: 34px; border-radius: 10px; background: linear-gradient(135deg, #1877f2, #2563eb); display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3); flex-shrink: 0;">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 15px; font-weight: 700; margin: 0;">মেসেজ সম্পাদনা করুন</h3>
                    <div style="font-size: 11px; color: var(--fb-text-secondary); margin-top: 1px;">সোশ্যাল মিডিয়ার মতো লাইভ এডিট ও আপডেট</div>
                </div>
            </div>
            <button type="button" onclick="closeChatModal('editMessageModal')" style="width: 32px; height: 32px; border-radius: 50%; border: none; background: rgba(0,0,0,0.06); font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center;">✕</button>
        </div>

        <div style="padding: 12px 18px 0;">
            <div class="jj-edit-quote-box">
                <div style="font-size: 10px; font-weight: 700; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">পূর্ববর্তী বার্তা (Original):</div>
                <div id="fullChatEditOriginalPreview" style="white-space: pre-wrap; word-break: break-word; font-style: italic;">...</div>
            </div>
        </div>

        <div style="padding: 12px 18px 6px;">
            <input type="hidden" id="editMessageId">
            <input type="hidden" id="editMessageOriginalText">
            <textarea id="editMessageTextarea" class="jj-edit-textarea" placeholder="আপনার নতুন বার্তা লিখুন..." oninput="handleFullChatEditInput(this)" onkeydown="handleFullChatEditKeydown(event)"></textarea>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 6px; font-size: 11px; color: var(--fb-text-secondary);">
                <span style="display: flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                    <span><kbd style="background: rgba(0,0,0,0.07); padding: 1px 4px; border-radius: 4px; font-size: 10px;">Enter</kbd> সংরক্ষণ</span>
                    <span>·</span>
                    <span><kbd style="background: rgba(0,0,0,0.07); padding: 1px 4px; border-radius: 4px; font-size: 10px;">Shift+Enter</kbd> নতুন লাইন</span>
                    <span>·</span>
                    <span><kbd style="background: rgba(0,0,0,0.07); padding: 1px 4px; border-radius: 4px; font-size: 10px;">Esc</kbd> বাতিল</span>
                </span>
                <span id="fullChatEditCharCounter" style="font-variant-numeric: tabular-nums; font-weight: 600;">0/2000</span>
            </div>
        </div>

        <div style="padding: 0 18px 12px;">
            <div style="font-size: 11px; font-weight: 600; color: var(--fb-text-secondary); margin-bottom: 6px;">ইমোজি যুক্ত করুন:</div>
            <div style="display: flex; gap: 4px; overflow-x: auto; padding-bottom: 2px; scrollbar-width: none;">
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('👍')">👍</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('❤️')">❤️</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('😊')">😊</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('😂')">😂</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('🔥')">🔥</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('🎉')">🎉</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('👏')">👏</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('🙏')">🙏</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('😍')">😍</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('🥺')">🥺</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('🥳')">🥳</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('✨')">✨</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('💯')">💯</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('😮')">😮</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('😢')">😢</button>
                <button type="button" class="jj-edit-emoji-btn" onclick="insertFullChatEditEmoji('🤝')">🤝</button>
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 12px 18px; border-top: 1px solid var(--fb-border); background: var(--fb-card-bg, #f8fafc);">
            <button type="button" onclick="closeChatModal('editMessageModal')" style="padding: 8px 16px; border-radius: 8px; border: 1px solid var(--fb-border); background: transparent; color: var(--fb-text-secondary); font-size: 13px; font-weight: 600; cursor: pointer;">বাতিল (Cancel)</button>
            <button type="button" id="fullChatEditSubmitBtn" class="btn-fb-primary" style="padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" onclick="submitEditMessage()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span id="fullChatEditSubmitBtnText">সংরক্ষণ করুন</span>
            </button>
        </div>
    </div>
</div>

<!-- 4. Report Modal -->
<div class="chat-modal-overlay" id="reportModal">
    <div class="chat-modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 17px; font-weight: 800;">রিপোর্ট জমা দিন</h3>
            <button type="button" onclick="closeChatModal('reportModal')" style="background: none; border: none; font-size: 18px; cursor: pointer;">✕</button>
        </div>
        <div style="padding: 16px 20px;">
            <input type="hidden" id="reportTargetType" value="conversation">
            <input type="hidden" id="reportTargetId">
            <div style="margin-bottom: 12px;">
                <label style="font-size: 13px; font-weight: 700; display: block; margin-bottom: 6px;">কারণে নির্বাচন করুন:</label>
                <select id="reportReasonSelect" class="chat-input" style="width: 100%; border-radius: 8px;">
                    <option value="spam">স্প্যাম বা অযাচিত মেসেজ</option>
                    <option value="harassment">হয়রানি বা হুমকি</option>
                    <option value="abuse">অপব্যবহারমূলক আচরণ</option>
                    <option value="fraud">প্রতারণা বা স্ক্যাম</option>
                    <option value="other">অন্যান্য</option>
                </select>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: 700; display: block; margin-bottom: 6px;">বিস্তারিত তথ্য (ঐচ্ছিক):</label>
                <textarea id="reportDetailsTextarea" placeholder="পরিস্থিতি ব্যাখ্যা করুন..." class="chat-input" style="width: 100%; height: 80px; border-radius: 8px;"></textarea>
            </div>
            <button type="button" class="btn-fb-primary" style="width: 100%; border-radius: 8px; justify-content: center;" onclick="submitReport()">রিপোর্ট সাবমিট করুন</button>
        </div>
    </div>
</div>

<!-- 5. Emoji Picker Popover -->
<div id="emojiPickerBox" style="display: none; position: absolute; bottom: 70px; left: 60px; background: var(--fb-card); border: 1px solid var(--fb-border); box-shadow: var(--shadow-lg); border-radius: 14px; padding: 12px; width: 280px; z-index: 50;">
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; font-size: 20px; text-align: center;">
        @foreach(['😀','😂','😍','🥰','😎','🤔','😭','😡','👍','👎','❤️','🔥','🎉','👏','🙏','✨','💯','🚀','☕','🎈','🍕'] as $em)
            <span style="cursor: pointer; padding: 4px; border-radius: 6px;" onclick="insertEmoji('{{ $em }}')">{{ $em }}</span>
        @endforeach
    </div>
</div>

<!-- Enhanced Lightbox Modal -->
<div id="imageLightbox" style="display: none; position: fixed; inset: 0; background: rgba(10, 15, 29, 0.95); z-index: 3000; flex-direction: column; justify-content: space-between; padding: 16px; user-select: none; backdrop-filter: blur(12px);" onclick="closeLightboxModal()">
    <!-- Top Toolbar -->
    <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; color: white; padding: 4px 12px; z-index: 10;" onclick="event.stopPropagation()">
        <div id="lightboxTitle" style="font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.85); display: flex; align-items: center; gap: 8px;">
            <span id="lightboxCounter">ছবি প্রিভিউ</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <button type="button" onclick="zoomLightboxImage(0.25)" title="জুম ইন (+)" style="background: rgba(255,255,255,0.12); border: none; color: white; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
            </button>
            <button type="button" onclick="zoomLightboxImage(-0.25)" title="জুম আউট (-)" style="background: rgba(255,255,255,0.12); border: none; color: white; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
            </button>
            <button type="button" onclick="resetLightboxZoom()" title="রিসেট" style="background: rgba(255,255,255,0.12); border: none; color: white; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
            </button>
            <a id="lightboxDownloadBtn" href="" download target="_blank" title="ডাউনলোড" style="background: rgba(255,255,255,0.12); border: none; color: white; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            </a>
            <button type="button" onclick="closeLightboxModal()" title="বন্ধ করুন (Esc)" style="background: rgba(255,255,255,0.12); border: none; color: white; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
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

<!-- WebRTC Active Call Modal -->
<div id="webrtcCallModal" style="display: none; position: fixed; inset: 0; background: rgba(10, 15, 29, 0.95); z-index: 4000; flex-direction: column; justify-content: space-between; padding: 24px; backdrop-filter: blur(12px);">
    <div style="display: flex; justify-content: space-between; align-items: center; color: white;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div id="callRemoteAvatar" class="avatar" style="width: 44px; height: 44px; border: 2px solid rgba(255,255,255,0.2);"></div>
            <div>
                <h3 id="callRemoteName" style="font-size: 17px; font-weight: 800; margin: 0; color: white;">চ্যাট কল</h3>
                <span id="callStatusBadge" style="font-size: 13px; color: #10b981; font-weight: 600;">সংযুক্ত হচ্ছে...</span>
            </div>
        </div>
        <div id="callDurationTimer" style="font-size: 15px; font-weight: 700; background: rgba(255,255,255,0.1); padding: 6px 14px; border-radius: 20px; color: white;">00:00</div>
    </div>

    <div style="position: relative; flex: 1; margin: 20px 0; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 16px; background: rgba(0,0,0,0.4);">
        <div id="callAudioModeVisualizer" style="display: flex; flex-direction: column; align-items: center; gap: 16px;">
            <div id="callAudioBigAvatar" style="width: 110px; height: 110px; border-radius: 50%; background: linear-gradient(135deg, #0078FF, #00C6FF); display: flex; align-items: center; justify-content: center; font-size: 40px; color: white; font-weight: 800; box-shadow: 0 0 30px rgba(0,198,255,0.4);">
                A
            </div>
            <div style="color: rgba(255,255,255,0.7); font-size: 14px;">অডিও কল চলমান রয়েছে</div>
        </div>

        <video id="remoteVideo" autoplay playsinline style="display: none; width: 100%; height: 100%; object-fit: cover; border-radius: 16px;"></video>
        <video id="localVideo" autoplay muted playsinline style="display: none; position: absolute; bottom: 16px; right: 16px; width: 160px; height: 110px; object-fit: cover; border-radius: 10px; border: 2px solid white; box-shadow: 0 8px 24px rgba(0,0,0,0.5); z-index: 10;"></video>
    </div>

    <div style="display: flex; justify-content: center; align-items: center; gap: 16px;">
        <button type="button" id="btnToggleMic" onclick="toggleMuteMic()" style="width: 50px; height: 50px; border-radius: 50%; border: none; background: rgba(255,255,255,0.15); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" title="মাইক্রোফোন মিউট/আনমিউট">
            <svg id="iconMicOn" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="22"></line></svg>
        </button>
        <button type="button" id="btnToggleCam" onclick="toggleCamera()" style="width: 50px; height: 50px; border-radius: 50%; border: none; background: rgba(255,255,255,0.15); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" title="ক্যামেরা অন/অফ">
            <svg id="iconCamOn" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
        </button>
        <button type="button" id="btnToggleScreen" onclick="toggleScreenShare()" style="width: 50px; height: 50px; border-radius: 50%; border: none; background: rgba(255,255,255,0.15); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" title="স্ক্রিন শেয়ার">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
        </button>
        <button type="button" onclick="hangUpCall()" style="width: 56px; height: 56px; border-radius: 50%; border: none; background: #ef4444; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 4px 16px rgba(239,68,68,0.4);" title="কল শেষ করুন">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.42 19.42 0 0 1-3.33-2.67m-2.67-3.34a19.79 19.79 0 0 1-3.07-8.63A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
        </button>
    </div>
</div>

<!-- Incoming Call Dialog -->
<div id="incomingCallModal" style="display: none; position: fixed; top: 24px; right: 24px; z-index: 5000; background: var(--fb-card); border: 1px solid var(--fb-border); border-radius: 16px; box-shadow: var(--shadow-lg); padding: 18px 24px; width: 320px; animation: slideInDown 0.3s ease;">
    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
        <div id="incomingCallerAvatar" class="avatar" style="width: 50px; height: 50px; font-size: 20px;"></div>
        <div>
            <h4 id="incomingCallerName" style="margin: 0; font-size: 16px; font-weight: 800; color: var(--fb-text-primary);">ইনকামিং কল...</h4>
            <span id="incomingCallTypeBadge" style="font-size: 13px; color: var(--fb-primary); font-weight: 600;">অডিও কল</span>
        </div>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" onclick="rejectIncomingCall()" style="flex: 1; padding: 10px; border-radius: 20px; border: none; background: #fee2e2; color: #dc2626; font-weight: 700; cursor: pointer;">প্রত্যাখ্যান</button>
        <button type="button" onclick="acceptIncomingCall()" style="flex: 1; padding: 10px; border-radius: 20px; border: none; background: #10b981; color: white; font-weight: 700; cursor: pointer;">গ্রহণ করুন</button>
    </div>
</div>

<!-- Mute Conversation Modal -->
<div class="chat-modal-overlay" id="muteConversationModal">
    <div class="chat-modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 17px; font-weight: 800;">নোটিফিকেশন মিউট করুন</h3>
            <button type="button" onclick="closeChatModal('muteConversationModal')" style="background: none; border: none; font-size: 18px; cursor: pointer;">✕</button>
        </div>
        <div style="padding: 16px 20px;">
            <p style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 12px;">কত সময়ের জন্য মিউট করতে চান নির্বাচন করুন:</p>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer;">
                    <input type="radio" name="muteDuration" value="1h" checked> ১ ঘণ্টা
                </label>
                <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer;">
                    <input type="radio" name="muteDuration" value="8h"> ৮ ঘণ্টা
                </label>
                <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer;">
                    <input type="radio" name="muteDuration" value="24h"> ২৪ ঘণ্টা
                </label>
                <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer;">
                    <input type="radio" name="muteDuration" value="forever"> যতক্ষণ না পুনরায় আনমিউট করা হয়
                </label>
                <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer; color: var(--fb-primary);">
                    <input type="radio" name="muteDuration" value="unmute"> মিউট বন্ধ করুন (আনমিউট)
                </label>
            </div>
            <button type="button" class="btn-fb-primary" style="width: 100%; border-radius: 8px; justify-content: center;" onclick="submitMuteConversation()">সংরক্ষণ করুন</button>
        </div>
    </div>
</div>

<!-- Add Member to Group Modal -->
<!-- 8. Add Member Modal -->
<div class="chat-modal-overlay" id="addMemberModal">
    <div class="chat-modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--ms-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 18px; font-weight: 800; color: var(--ms-text-primary); margin: 0;">গ্রুপে নতুন সদস্য যুক্ত করুন</h3>
            <button type="button" class="icon-circle-btn" style="width: 32px; height: 32px; font-size: 15px;" onclick="closeChatModal('addMemberModal')" title="বন্ধ করুন">✕</button>
        </div>
        <div style="padding: 16px 20px;">
            <div class="ms-search-capsule" style="margin-bottom: 12px;">
                <svg class="search-icon ms-capsule-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="position: static !important; transform: none !important; left: auto !important; top: auto !important; margin: 0 !important; flex-shrink: 0;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="addMemberSearchInput" placeholder="বন্ধুর নাম বা ইউজারনেম দিয়ে খুঁজুন..." oninput="filterAddMembersList(this.value)">
            </div>
            <div id="addMembersChecklist" style="max-height: 240px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding: 2px; margin-bottom: 16px;">
                <div style="color: var(--ms-text-secondary); font-size: 13px; text-align: center; padding: 12px;">বন্ধুদের লোড করা হচ্ছে...</div>
            </div>
            <button type="button" class="btn-fb-primary" style="width: 100%; border-radius: 12px; height: 42px; font-size: 14.5px; font-weight: 700; justify-content: center;" onclick="submitAddGroupMembers()">সদস্য যুক্ত করুন</button>
        </div>
    </div>
</div>

<!-- Message Reactions Viewer Modal -->
<div class="chat-modal-overlay" id="reactionsListModal">
    <div class="chat-modal-box" style="max-width: 380px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 800;">মেসেজের প্রতিক্রিয়া</h3>
            <button type="button" onclick="closeChatModal('reactionsListModal')" style="background: none; border: none; font-size: 18px; cursor: pointer;">✕</button>
        </div>
        <div id="reactionsListContent" style="padding: 16px 20px; max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
            <!-- Rendered dynamically -->
        </div>
    </div>
</div>

<!-- Call History Modal -->
<div class="chat-modal-overlay" id="callHistoryModal">
    <div class="chat-modal-box" style="max-width: 480px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 17px; font-weight: 800;">কলের ইতিহাস (Call Logs)</h3>
            <button type="button" onclick="closeChatModal('callHistoryModal')" style="background: none; border: none; font-size: 18px; cursor: pointer;">✕</button>
        </div>
        <div id="callHistoryListContent" style="padding: 16px 20px; max-height: 350px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
            <div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px;">কল হিস্ট্রি লোড হচ্ছে...</div>
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

@include('partials.messenger-sound-settings-modal')

<!-- Global Messenger In-App Toast -->
<div id="toastNotification" style="display: none; position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); background: rgba(15, 23, 42, 0.92); color: white; padding: 10px 22px; border-radius: 30px; font-size: 13px; font-weight: 600; box-shadow: var(--shadow-lg); z-index: 6000; backdrop-filter: blur(6px); transition: all 0.2s ease;">
    <span id="toastNotificationText"></span>
</div>

@endsection

@section('scripts')
<script>
    const currentUserId = {{ $currentUser?->id ?? 'null' }};
    const currentConvId = {{ $activeConversation ? $activeConversation->id : 'null' }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const serverAuthToken = @json($userToken ?? '');

    function getAuthToken() {
        return serverAuthToken || localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '';
    }

    if (serverAuthToken) {
        try {
            localStorage.setItem('bondhoo_token', serverAuthToken);
            localStorage.setItem('jugajug_token', serverAuthToken);
        } catch (e) {}
    }

    function getMessengerHeaders(extraHeaders = {}) {
        const token = getAuthToken();
        return {
            'Accept': 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
            ...extraHeaders
        };
    }

    let activeReplyToId = null;
    let voiceMediaRecorder = null;
    let voiceAudioChunks = [];
    let voiceTimerInterval = null;
    let voiceSeconds = 0;
    let currentForwardMessageId = null;

    // Scroll to bottom on initial load
    window.addEventListener('DOMContentLoaded', () => {
        scrollToBottom();
        initRealtimeMessenger();
        restoreDraft();
        if (typeof initConversationsInfiniteScroll === 'function') {
            initConversationsInfiniteScroll();
        }
        if (currentConvId) {
            loadSharedTab('media');
        }

        // Attach touch and click listeners to send button for cross-device responsiveness
        const sendBtn = document.getElementById('btnSendMessage');
        if (sendBtn) {
            sendBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                submitMessage();
            });
            sendBtn.addEventListener('touchend', (e) => {
                e.preventDefault();
                e.stopPropagation();
                submitMessage();
            }, { passive: false });
        }
    });

    function scrollToBottom() {
        const stream = document.getElementById('messagesStream');
        if (stream) {
            stream.scrollTop = stream.scrollHeight;
        }
    }

    function scrollToMessage(id) {
        const row = document.getElementById(`messageRow-${id}`);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.style.transition = 'background 0.5s';
            row.style.background = 'rgba(24, 119, 242, 0.15)';
            setTimeout(() => row.style.background = 'transparent', 1500);
        }
    }

    // Toggle In-Chat Search
    function toggleInChatSearch() {
        const bar = document.getElementById('inChatSearchBar');
        if (!bar) return;
        if (bar.style.display === 'none') {
            bar.style.display = 'flex';
            document.getElementById('inChatMessageSearchInput')?.focus();
        } else {
            bar.style.display = 'none';
            // Reset message visibility
            document.querySelectorAll('.message-row').forEach(r => r.style.display = 'flex');
        }
    }

    function searchInChatMessages(term) {
        term = term.toLowerCase().trim();
        document.querySelectorAll('.message-row').forEach(row => {
            const text = row.querySelector('.message-bubble')?.innerText.toLowerCase() || '';
            row.style.display = (!term || text.includes(term)) ? 'flex' : 'none';
        });
    }

    // Details Sidebar Toggle (Adaptive Desktop / Drawer Mode)
    function toggleDetailsSidebar() {
        const sidebar = document.getElementById('detailsSidebarPane');
        const backdrop = document.getElementById('detailsDrawerBackdrop');
        if (!sidebar) return;

        if (window.innerWidth < 1200) {
            const isOpen = sidebar.classList.toggle('drawer-open');
            if (backdrop) {
                if (isOpen) {
                    backdrop.classList.add('active');
                } else {
                    backdrop.classList.remove('active');
                }
            }
        } else {
            sidebar.classList.toggle('collapsed');
        }
    }

    // Filter Tabs Switch
    function switchConvTab(tab, el) {
        document.querySelectorAll('.filter-tabs .filter-pill').forEach(p => p.classList.remove('active'));
        el.classList.add('active');

        fetch(`/api/v1/conversations?filter=${tab}`, {
            headers: { 'Accept': 'application/json', ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                renderConversationsList(res.data);
            }
        });
    }

    let convSearchTimeout = null;
    let initialInboxItemsHtml = null;
    let convCurrentPage = 1;
    let convHasMore = {{ ($conversations instanceof \Illuminate\Pagination\LengthAwarePaginator && $conversations->hasMorePages()) ? 'true' : 'false' }};
    let convIsLoading = false;
    let currentConvTab = 'all';

    function formatRelativeTime(dateStr) {
        if (!dateStr) return '';
        try {
            const date = new Date(dateStr);
            const now = new Date();
            const diffMs = now - date;
            const diffSec = Math.floor(diffMs / 1000);
            if (diffSec < 60) return 'এখনই';
            const diffMin = Math.floor(diffSec / 60);
            if (diffMin < 60) return `${diffMin} মি.`;
            const diffHours = Math.floor(diffMin / 60);
            if (diffHours < 24) return `${diffHours} ঘ.`;
            const diffDays = Math.floor(diffHours / 24);
            if (diffDays < 7) return `${diffDays} দিন`;
            return date.toLocaleDateString('bn-BD', { month: 'short', day: 'numeric' });
        } catch (e) {
            return '';
        }
    }

    function handleConvSearch(term) {
        term = (term || '').trim();
        const container = document.getElementById('conversationsListContainer');
        if (!container) return;

        if (initialInboxItemsHtml === null && !term) {
            initialInboxItemsHtml = container.innerHTML;
        }

        const lowerTerm = term.toLowerCase();
        let localMatches = 0;
        document.querySelectorAll('#conversationsListContainer .conversation-item').forEach(item => {
            const title = (item.getAttribute('data-title') || '').toLowerCase();
            const snippet = (item.querySelector('.conv-snippet')?.innerText || '').toLowerCase();
            const matches = !lowerTerm || title.includes(lowerTerm) || snippet.includes(lowerTerm);
            item.style.display = matches ? 'flex' : 'none';
            if (matches) localMatches++;
        });

        clearTimeout(convSearchTimeout);
        if (!term) {
            return;
        }

        // Debounced remote backend search for conversations not currently in DOM
        convSearchTimeout = setTimeout(async () => {
            try {
                const token = localStorage.getItem('jugajug_token') || localStorage.getItem('bondhoo_token') || '';
                const res = await fetch(`/api/v1/conversations?search=${encodeURIComponent(term)}&per_page=30`, {
                    headers: {
                        'Accept': 'application/json',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {})
                    }
                });
                const json = await res.json();
                if (json.success && Array.isArray(json.data)) {
                    if (localMatches === 0 && json.data.length > 0) {
                        renderConversationsList(json.data);
                    }
                }
            } catch (e) {}
        }, 300);
    }

    function renderConversationsList(items) {
        const container = document.getElementById('conversationsListContainer');
        if (!container) return;
        if (!items || items.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px 16px; color: var(--fb-text-secondary);">
                    <div style="margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 50%; background: var(--fb-hover);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                    <div style="font-weight: 700; font-size: 15px; color: var(--fb-text-primary);">কোনো চ্যাট পাওয়া যায়নি</div>
                    <div style="font-size: 12.5px; color: var(--ms-text-secondary); margin-top: 4px;">ভিন্ন নামে খুঁজুন অথবা নতুন চ্যাট শুরু করুন</div>
                </div>
            `;
            return;
        }

        container.innerHTML = items.map(c => {
            const isActive = currentConvId && Number(currentConvId) === Number(c.id);
            const snippet = c.last_message ? (c.last_message.sender_id === currentUserId ? 'আপনি: ' : '') + (c.last_message.body || '') : 'কথোপকথন শুরু করুন...';
            const otherUser = c.other_user || (c.participants ? c.participants.find(p => p.id !== currentUserId) : null);
            const otherUserId = otherUser ? (otherUser.id || '') : (c.user_id || '');
            const timeStr = c.last_message_at ? formatRelativeTime(c.last_message_at) : '';

            return `
                <a href="/messages/${c.id}" class="conversation-item ${isActive ? 'active' : ''} ${c.unread_count > 0 ? 'conv-item-unread' : ''}" id="convItem-${c.id}" data-id="${c.id}" data-user-id="${otherUserId}" data-title="${escapeHtml((c.title || '').toLowerCase())}">
                    <div class="conv-avatar-wrapper">
                        <div class="avatar" style="width: 48px; height: 48px;">
                            ${c.avatar_url ? `<img src="${c.avatar_url}" alt="${escapeHtml(c.title || '')}">` : escapeHtml((c.title ? c.title.charAt(0) : 'U'))}
                        </div>
                        <div class="online-indicator" id="convPresence-${c.id}"></div>
                    </div>
                    <div class="conv-content">
                        <div class="conv-top-row">
                            <div class="conv-name">
                                ${escapeHtml(c.title || 'ব্যবহারকারী')}
                                ${c.is_pinned ? `<span class="badge-pinned" title="পিন করা"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg></span>` : ''}
                                ${c.is_muted ? `<span class="badge-muted" title="মিউট করা"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"></line><path d="M17 17H3v-2l2-2V9a7 7 0 0 1 .74-3.15"></path><path d="M9 17v1a3 3 0 0 0 6 0v-1"></path><path d="M10.26 4.74A7 7 0 0 1 19 9v4l1.2 1.2"></path></svg></span>` : ''}
                            </div>
                            <div style="display: flex; align-items: center; gap: 4px; flex-shrink: 0;">
                                <span class="conv-time" id="convTime-${c.id}">${timeStr}</span>
                                <button type="button" class="btn-open-dock" onclick="event.preventDefault(); event.stopPropagation(); openDockedChat(${c.id}, '${escapeJs(c.title)}', '${c.avatar_url || ''}', false);" title="ডক উইন্ডোতে খুলুন">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="conv-bottom-row">
                            <div class="conv-snippet" id="convSnippet-${c.id}">${escapeHtml(snippet)}</div>
                            ${c.unread_count > 0 ? `<span class="badge-unread-pill" id="convUnreadBadge-${c.id}">${c.unread_count}</span>` : ''}
                        </div>
                    </div>
                </a>
            `;
        }).join('');
    }

    function appendMoreConversationsToInbox(items) {
        const container = document.getElementById('conversationsListContainer');
        if (!container || !Array.isArray(items)) return;

        items.forEach(c => {
            if (document.getElementById(`convItem-${c.id}`)) return;
            const isActive = currentConvId && Number(currentConvId) === Number(c.id);
            const snippet = c.last_message ? (c.last_message.sender_id === currentUserId ? 'আপনি: ' : '') + (c.last_message.body || '') : 'কথোপকথন শুরু করুন...';
            const otherUser = c.other_user || (c.participants ? c.participants.find(p => p.id !== currentUserId) : null);
            const otherUserId = otherUser ? (otherUser.id || '') : (c.user_id || '');
            const timeStr = c.last_message_at ? formatRelativeTime(c.last_message_at) : '';

            const rowHtml = `
                <a href="/messages/${c.id}" class="conversation-item ${isActive ? 'active' : ''} ${c.unread_count > 0 ? 'conv-item-unread' : ''}" id="convItem-${c.id}" data-id="${c.id}" data-user-id="${otherUserId}" data-title="${escapeHtml((c.title || '').toLowerCase())}">
                    <div class="conv-avatar-wrapper">
                        <div class="avatar" style="width: 48px; height: 48px;">
                            ${c.avatar_url ? `<img src="${c.avatar_url}" alt="${escapeHtml(c.title || '')}">` : escapeHtml((c.title ? c.title.charAt(0) : 'U'))}
                        </div>
                        <div class="online-indicator" id="convPresence-${c.id}"></div>
                    </div>
                    <div class="conv-content">
                        <div class="conv-top-row">
                            <div class="conv-name">
                                ${escapeHtml(c.title || 'ব্যবহারকারী')}
                                ${c.is_pinned ? `<span class="badge-pinned" title="পিন করা"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg></span>` : ''}
                                ${c.is_muted ? `<span class="badge-muted" title="মিউট করা"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"></line><path d="M17 17H3v-2l2-2V9a7 7 0 0 1 .74-3.15"></path><path d="M9 17v1a3 3 0 0 0 6 0v-1"></path><path d="M10.26 4.74A7 7 0 0 1 19 9v4l1.2 1.2"></path></svg></span>` : ''}
                            </div>
                            <div style="display: flex; align-items: center; gap: 4px; flex-shrink: 0;">
                                <span class="conv-time" id="convTime-${c.id}">${timeStr}</span>
                                <button type="button" class="btn-open-dock" onclick="event.preventDefault(); event.stopPropagation(); openDockedChat(${c.id}, '${escapeJs(c.title)}', '${c.avatar_url || ''}', false);" title="ডক উইন্ডোতে খুলুন">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="conv-bottom-row">
                            <div class="conv-snippet" id="convSnippet-${c.id}">${escapeHtml(snippet)}</div>
                            ${c.unread_count > 0 ? `<span class="badge-unread-pill" id="convUnreadBadge-${c.id}">${c.unread_count}</span>` : ''}
                        </div>
                    </div>
                </a>
            `;
            container.insertAdjacentHTML('beforeend', rowHtml);
        });
    }

    function initConversationsInfiniteScroll() {
        const container = document.getElementById('conversationsListContainer');
        if (!container) return;

        container.addEventListener('scroll', () => {
            if (!convHasMore || convIsLoading) return;
            const scrollBottom = container.scrollHeight - container.scrollTop - container.clientHeight;
            if (scrollBottom < 120) {
                loadMoreConversations();
            }
        });
    }

    async function loadMoreConversations() {
        if (convIsLoading || !convHasMore) return;
        convIsLoading = true;
        convCurrentPage++;

        try {
            const token = localStorage.getItem('jugajug_token') || localStorage.getItem('bondhoo_token') || '';
            const searchVal = document.getElementById('convSearchInput')?.value.trim() || '';
            let url = `/api/v1/conversations?page=${convCurrentPage}&per_page=20`;
            if (currentConvTab && currentConvTab !== 'all') url += `&filter=${currentConvTab}`;
            if (searchVal) url += `&search=${encodeURIComponent(searchVal)}`;

            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    ...(token ? { 'Authorization': `Bearer ${token}` } : {})
                }
            });
            const json = await res.json();
            if (json.success && json.data) {
                const items = Array.isArray(json.data) ? json.data : (json.data.data || []);
                if (items.length === 0) {
                    convHasMore = false;
                } else {
                    appendMoreConversationsToInbox(items);
                    if (json.meta && json.meta.current_page >= json.meta.last_page) {
                        convHasMore = false;
                    }
                }
            } else {
                convHasMore = false;
            }
        } catch (e) {
            convCurrentPage--;
        } finally {
            convIsLoading = false;
        }
    }

    function updateConversationPreviewInInbox(convId, m) {
        if (!convId || !m) return;
        const container = document.getElementById('conversationsListContainer');
        if (!container) return;

        let item = document.getElementById(`convItem-${convId}`);
        const isMe = Number(m.sender_id || m.sender?.id) === Number(currentUserId);
        const rawBody = m.body || (m.media_ids ? '📷 ছবি/ফাইল' : (m.type === 'voice' ? '🎙️ ভয়েস বার্তা' : 'বার্তা'));
        const snippetText = (isMe ? 'আপনি: ' : '') + (rawBody.length > 28 ? rawBody.substring(0, 28) + '...' : rawBody);

        if (item) {
            // 1. Update snippet
            const snippetEl = item.querySelector('.conv-snippet') || document.getElementById(`convSnippet-${convId}`);
            if (snippetEl) snippetEl.textContent = snippetText;

            // 2. Update time
            const timeEl = item.querySelector('.conv-time') || document.getElementById(`convTime-${convId}`);
            if (timeEl) timeEl.textContent = 'এখনই';

            // 3. Update unread badge if not in active conversation
            const isCurrentChat = currentConvId && Number(currentConvId) === Number(convId);
            if (!isCurrentChat && !isMe) {
                let badge = item.querySelector('.badge-unread-pill') || document.getElementById(`convUnreadBadge-${convId}`);
                if (badge) {
                    const currentCount = parseInt(badge.textContent, 10) || 0;
                    badge.textContent = currentCount + 1;
                } else {
                    const bottomRow = item.querySelector('.conv-bottom-row');
                    if (bottomRow) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge-unread-pill';
                        newBadge.id = `convUnreadBadge-${convId}`;
                        newBadge.textContent = '1';
                        bottomRow.appendChild(newBadge);
                    }
                }
                item.classList.add('conv-item-unread');
            }

            // 4. Sort to top of inbox
            container.prepend(item);
        } else {
            // New conversation row not in DOM yet
            fetchSingleConversationForInbox(convId);
        }
    }

    async function fetchSingleConversationForInbox(convId) {
        try {
            const token = localStorage.getItem('jugajug_token') || localStorage.getItem('bondhoo_token') || '';
            const res = await fetch(`/api/v1/conversations/${convId}`, {
                headers: {
                    'Accept': 'application/json',
                    ...(token ? { 'Authorization': `Bearer ${token}` } : {})
                }
            });
            const data = await res.json();
            if (data.success && data.data) {
                appendMoreConversationsToInbox([data.data]);
                const newlyAdded = document.getElementById(`convItem-${convId}`);
                const container = document.getElementById('conversationsListContainer');
                if (newlyAdded && container) {
                    container.prepend(newlyAdded);
                }
            }
        } catch (e) {}
    }

    // Message Sending & Input
    function handleComposerKeydown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            submitMessage();
        }
    }

    function handleComposerInput(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        saveDraft();

        // Broadcast Typing Status
        if (currentConvId) {
            fetch('/api/v1/presence/typing', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}) },
                body: JSON.stringify({ conversation_id: currentConvId })
            }).catch(() => {});
        }
    }

    function saveDraft() {
        if (!currentConvId) return;
        const text = document.getElementById('chatMessageInput')?.value || '';
        localStorage.setItem(`draft_conv_${currentConvId}`, text);
        // Persist to server
        fetch(`/api/v1/conversations/${currentConvId}/draft`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}) },
            body: JSON.stringify({ draft: text })
        }).catch(() => {});
    }

    function restoreDraft() {
        if (!currentConvId) return;
        const saved = localStorage.getItem(`draft_conv_${currentConvId}`);
        const input = document.getElementById('chatMessageInput');
        if (input && saved) {
            input.value = saved;
            handleComposerInput(input);
        }
    }

    let isSendingMessage = false;
    let lastSendTriggerTime = 0;
    const pendingFailedMessages = {};
    const activeUploads = {};

    function restoreFailedMessageToInput(clientMsgId) {
        const record = pendingFailedMessages[clientMsgId];
        if (!record) return;
        const input = document.getElementById('chatMessageInput');
        if (input) {
            input.value = record.originalText || record.payload.body || '';
            handleComposerInput(input);
            input.focus();
            showToast('মেসেজটি ইনপুটে ফিরিয়ে আনা হয়েছে');
        }
    }

    async function submitMessage(extraPayload = {}) {
        if (!currentConvId) return;

        const now = Date.now();
        if (isSendingMessage || (now - lastSendTriggerTime < 250)) {
            return;
        }

        const input = document.getElementById('chatMessageInput');
        const rawBodyText = input?.value || '';
        const bodyText = (extraPayload.body !== undefined ? extraPayload.body : rawBodyText).trim();

        if (!bodyText && !extraPayload.media_ids) {
            return;
        }

        lastSendTriggerTime = now;
        isSendingMessage = true;

        const sendBtn = document.getElementById('btnSendMessage');
        if (sendBtn) {
            sendBtn.disabled = true;
            sendBtn.classList.add('sending');
            sendBtn.style.opacity = '0.65';
        }

        const clientMsgId = 'msg_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
        const payload = {
            body: bodyText,
            reply_to_id: activeReplyToId,
            client_message_id: clientMsgId,
            idempotency_key: clientMsgId,
            ...extraPayload
        };

        const originalText = rawBodyText;
        pendingFailedMessages[clientMsgId] = { payload, originalText };

        // Optimistic UI Append
        const textToDisplay = payload.body || (payload.media_ids ? 'মিডিয়া ফাইল...' : '');
        if (textToDisplay && !extraPayload.type) {
            appendLocalMessageBubble(textToDisplay, clientMsgId);
        }

        if (input) {
            input.value = '';
            input.style.height = 'auto';
        }
        cancelReply();
        localStorage.removeItem(`draft_conv_${currentConvId}`);

        try {
            let resData = null;
            let success = false;
            let lastError = null;

            // Attempt 1: API endpoint with Bearer auth token and CSRF
            try {
                const apiRes = await fetch(`/api/v1/conversations/${currentConvId}/messages`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: getMessengerHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify(payload)
                });

                if (apiRes.ok) {
                    resData = await apiRes.json();
                    if (resData && (resData.success || resData.data)) {
                        success = true;
                    }
                } else {
                    const errBody = await apiRes.json().catch(() => null);
                    lastError = errBody?.message || `API error (${apiRes.status})`;
                }
            } catch (apiErr) {
                lastError = 'API network error';
            }

            // Attempt 2: Fallback to web session endpoint if API request failed
            if (!success) {
                try {
                    const webRes = await fetch(`/messages/${currentConvId}/send`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                        },
                        body: JSON.stringify(payload)
                    });

                    if (webRes.ok) {
                        resData = await webRes.json();
                        if (resData && (resData.success || resData.data)) {
                            success = true;
                        }
                    } else {
                        const webErr = await webRes.json().catch(() => null);
                        lastError = webErr?.message || (lastError ?? `সার্ভার এরর (${webRes.status})`);
                    }
                } catch (webErr) {
                    lastError = 'নেটওয়ার্ক সংযোগ বিঘ্নিত হয়েছে। পুনরায় চেষ্টা করুন।';
                }
            }

            if (success && resData?.data) {
                delete pendingFailedMessages[clientMsgId];
                if (window.bondhooSoundManager && typeof window.bondhooSoundManager.playOutgoingMessageSentTone === 'function') {
                    window.bondhooSoundManager.playOutgoingMessageSentTone(resData.data.id);
                }
                const localRow = document.getElementById(`localRow-${clientMsgId}`);
                if (localRow) {
                    localRow.id = `messageRow-${resData.data.id}`;
                    localRow.setAttribute('data-id', resData.data.id);
                    localRow.style.opacity = '1';
                    const check = localRow.querySelector('.delivery-check');
                    if (check) check.textContent = '✓';
                }
            } else {
                markMessageAsFailed(clientMsgId, payload, originalText, lastError || 'মেসেজ পাঠানো সম্ভব হয়নি');
            }
        } catch (e) {
            console.error('Fatal send error:', e);
            markMessageAsFailed(clientMsgId, payload, originalText, 'মেসেজ পাঠাতে অপ্রত্যাশিত ত্রুটি ঘটেছে।');
        } finally {
            isSendingMessage = false;
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.classList.remove('sending');
                sendBtn.style.opacity = '';
            }
        }
    }

    function markMessageAsFailed(clientMsgId, payload, originalText, errorMsg) {
        pendingFailedMessages[clientMsgId] = { payload, originalText };
        const localRow = document.getElementById(`localRow-${clientMsgId}`);
        if (localRow) {
            localRow.classList.add('failed');
            const metaRow = localRow.querySelector('.message-meta-row');
            if (metaRow) {
                metaRow.innerHTML = `
                    <span style="color: #ef4444; font-size: 11px; font-weight: 500;">⚠️ ${escapeHtml(errorMsg)}</span>
                    <button type="button" class="retry-send-btn" onclick="retryFailedMessage('${clientMsgId}')" style="background:#0084ff; color:white; border:none; padding:3px 8px; border-radius:12px; font-size:11px; cursor:pointer; font-weight:600; margin-left:6px;">পুনরায় পাঠান</button>
                    <button type="button" class="restore-send-btn" onclick="restoreFailedMessageToInput('${clientMsgId}')" style="background:#e4e6eb; color:#050505; border:none; padding:3px 8px; border-radius:12px; font-size:11px; cursor:pointer; font-weight:600; margin-left:4px;">ইনপুটে নিন</button>
                `;
            }
        }
        showToast(errorMsg);
    }

    async function retryFailedMessage(clientMsgId) {
        const record = pendingFailedMessages[clientMsgId];
        if (!record || !currentConvId) return;

        const payload = record.payload;
        const originalText = record.originalText;

        const localRow = document.getElementById(`localRow-${clientMsgId}`);
        if (localRow) {
            localRow.classList.remove('failed');
            const metaRow = localRow.querySelector('.message-meta-row');
            if (metaRow) {
                metaRow.innerHTML = `<span>${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span> <span class="delivery-check">🕒</span>`;
            }
        }

        try {
            let resData = null;
            let success = false;
            let lastError = null;

            try {
                const response = await fetch(`/api/v1/conversations/${currentConvId}/messages`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: getMessengerHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify(payload)
                });
                if (response.ok) {
                    resData = await response.json();
                    if (resData && (resData.success || resData.data)) {
                        success = true;
                    }
                } else {
                    const errBody = await response.json().catch(() => null);
                    lastError = errBody?.message || `API error (${response.status})`;
                }
            } catch (err) {
                lastError = 'API network error';
            }

            if (!success) {
                try {
                    const fallbackRes = await fetch(`/messages/${currentConvId}/send`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                        },
                        body: JSON.stringify(payload)
                    });
                    if (fallbackRes.ok) {
                        resData = await fallbackRes.json();
                        if (resData && (resData.success || resData.data)) {
                            success = true;
                        }
                    } else {
                        const webErr = await fallbackRes.json().catch(() => null);
                        lastError = webErr?.message || (lastError ?? `সার্ভার এরর (${fallbackRes.status})`);
                    }
                } catch (webErr) {
                    lastError = 'নেটওয়ার্ক সংযোগ বিঘ্নিত হয়েছে। পুনরায় চেষ্টা করুন।';
                }
            }

            if (success && resData?.data) {
                delete pendingFailedMessages[clientMsgId];
                if (window.bondhooSoundManager && typeof window.bondhooSoundManager.playOutgoingMessageSentTone === 'function') {
                    window.bondhooSoundManager.playOutgoingMessageSentTone(resData.data.id);
                }
                if (localRow) {
                    localRow.id = `messageRow-${resData.data.id}`;
                    localRow.setAttribute('data-id', resData.data.id);
                    localRow.style.opacity = '1';
                    const check = localRow.querySelector('.delivery-check');
                    if (check) check.textContent = '✓';
                }
            } else {
                markMessageAsFailed(clientMsgId, payload, originalText, lastError || 'পুনরায় ব্যর্থ হয়েছে');
            }
        } catch (e) {
            markMessageAsFailed(clientMsgId, payload, originalText, 'নেটওয়ার্ক সংযোগ বিঘ্নিত হয়েছে');
        }
    }

    function appendLocalMessageBubble(text, clientMsgId) {
        const stream = document.getElementById('messagesStream');
        if (!stream) return;
        document.getElementById('emptyChatPlaceholder')?.remove();

        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const bubbleHtml = `
            <div class="message-row outgoing" id="localRow-${clientMsgId}" style="opacity: 0.85;">
                <div class="message-bubble-wrapper">
                    <div class="message-bubble">${escapeHtml(text)}</div>
                    <div class="message-meta-row">
                        <span>${time}</span>
                        <span class="delivery-check">🕒</span>
                    </div>
                </div>
            </div>
        `;
        stream.insertAdjacentHTML('beforeend', bubbleHtml);
        scrollToBottom();
        return stream.lastElementChild;
    }

    function sendWaveGreeting() {
        const input = document.getElementById('chatMessageInput');
        if (input) {
            input.value = '👋';
            submitMessage();
        }
    }

    // Attachment Uploading with Progress, Cancel & Retry
    function triggerAttachmentUpload() {
        document.getElementById('attachmentFileInput')?.click();
    }

    function handleAttachmentFiles(files) {
        if (!files || files.length === 0) return;

        const maxFileSize = 52428800; // 50MB
        const blockedExtensions = ['exe', 'bat', 'sh', 'php', 'phtml', 'phar', 'cgi', 'pl', 'py', 'js', 'vbs'];

        // Client-side Validation
        for (let i = 0; i < files.length; i++) {
            const f = files[i];
            if (f.size > maxFileSize) {
                alert(`"${f.name}" ফাইলটি ৫০ মেগাবাইটের চেয়ে বড়। অনুগ্রহ করে ছোট ফাইল নির্বাচন করুন।`);
                return;
            }
            const ext = (f.name.split('.').pop() || '').toLowerCase();
            if (blockedExtensions.includes(ext)) {
                alert(`"${f.name}" ফাইল ফরম্যাটটি নিরাপত্তার কারণে আপলোড করা যাবে না।`);
                return;
            }
        }

        const uploadId = 'upload_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6);
        const stream = document.getElementById('messagesStream');
        document.getElementById('emptyChatPlaceholder')?.remove();

        const firstFile = files[0];
        const isImg = firstFile.type.startsWith('image/');
        let localPreviewUrl = '';
        if (isImg && files.length === 1) {
            try { localPreviewUrl = URL.createObjectURL(firstFile); } catch (e) {}
        }

        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const uploadCardHtml = `
            <div class="message-row outgoing" id="${uploadId}" style="opacity: 0.9;">
                <div class="message-bubble-wrapper">
                    <div class="message-bubble" style="padding: 10px 14px; max-width: 280px;">
                        ${localPreviewUrl ? `<img src="${localPreviewUrl}" style="width: 100%; max-height: 160px; object-fit: cover; border-radius: 8px; margin-bottom: 8px;">` : ''}
                        <div style="font-size: 13px; font-weight: 700; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            📁 ${files.length > 1 ? `${files.length}টি ফাইল আপলোড হচ্ছে` : escapeHtml(firstFile.name)}
                        </div>
                        <div class="upload-progress-container">
                            <div class="upload-progress-bar-wrap">
                                <div class="upload-progress-bar-fill" id="${uploadId}_fill" style="width: 0%;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                <span class="upload-progress-text" id="${uploadId}_status">০% আপলোড সম্পন্ন...</span>
                                <button type="button" class="upload-cancel-btn" onclick="cancelAttachmentUpload('${uploadId}')">বাতিল</button>
                            </div>
                        </div>
                    </div>
                    <div class="message-meta-row"><span>${time}</span> <span>আপলোড হচ্ছে...</span></div>
                </div>
            </div>
        `;
        if (stream) {
            stream.insertAdjacentHTML('beforeend', uploadCardHtml);
            scrollToBottom();
        }

        const input = document.getElementById('chatMessageInput');
        const caption = (input?.value || '').trim();
        if (input) input.value = '';

        const formData = new FormData();
        if (files.length > 1) {
            Array.from(files).forEach(f => formData.append('files[]', f));
        } else {
            formData.append('file', files[0]);
        }
        formData.append('collection', 'message');

        const xhr = new XMLHttpRequest();
        activeUploads[uploadId] = { xhr, files, caption };

        xhr.open('POST', '/api/v1/messages/attachments');
        xhr.withCredentials = true;
        xhr.setRequestHeader('Accept', 'application/json');
        if (csrfToken) xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        const uploadToken = getAuthToken();
        if (uploadToken) xhr.setRequestHeader('Authorization', `Bearer ${uploadToken}`);

        xhr.upload.onprogress = (evt) => {
            if (evt.lengthComputable) {
                const percent = Math.round((evt.loaded / evt.total) * 100);
                const fillEl = document.getElementById(`${uploadId}_fill`);
                const statusEl = document.getElementById(`${uploadId}_status`);
                if (fillEl) fillEl.style.width = percent + '%';
                if (statusEl) statusEl.textContent = `${percent}% আপলোড সম্পন্ন...`;
            }
        };

        xhr.onload = () => {
            delete activeUploads[uploadId];
            if (localPreviewUrl) URL.revokeObjectURL(localPreviewUrl);

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.success && res.data) {
                        document.getElementById(uploadId)?.remove();
                        const mediaIds = Array.isArray(res.data) ? res.data.map(m => m.id) : [res.data.id];
                        submitMessage({ media_ids: mediaIds, body: caption });
                    } else {
                        markUploadAsFailed(uploadId, res.message || 'আপলোড ব্যর্থ');
                    }
                } catch (e) {
                    markUploadAsFailed(uploadId, 'সার্ভার রেসপন্স প্রক্রিয়াকরণে ত্রুটি');
                }
            } else {
                markUploadAsFailed(uploadId, 'সার্ভার ত্রুটি (' + xhr.status + ')');
            }
        };

        xhr.onerror = () => {
            delete activeUploads[uploadId];
            markUploadAsFailed(uploadId, 'নেটওয়ার্ক সংযোগ বিঘ্নিত হয়েছে');
        };

        xhr.send(formData);
    }

    function cancelAttachmentUpload(uploadId) {
        if (activeUploads[uploadId]) {
            activeUploads[uploadId].xhr.abort();
            delete activeUploads[uploadId];
        }
        document.getElementById(uploadId)?.remove();
    }

    function markUploadAsFailed(uploadId, errorMsg) {
        const row = document.getElementById(uploadId);
        if (row) {
            row.classList.add('failed');
            const statusEl = document.getElementById(`${uploadId}_status`);
            if (statusEl) {
                statusEl.innerHTML = `<span style="color: #ef4444;">⚠️ ${escapeHtml(errorMsg)}</span>`;
            }
        }
        showToast(errorMsg);
    }

    // ==============================================
    // VOICE NOTE PLAYBACK AUDIO ENGINE
    // ==============================================
    let currentVoiceAudio = null;
    let currentVoiceMessageId = null;

    function resolvePlayableAudioUrl(messageId, rawUrl) {
        const token = (typeof currentToken !== 'undefined' && currentToken) ? currentToken : (localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '');
        const tokenParam = token ? `?token=${encodeURIComponent(token)}` : '';
        if (messageId) {
            return `/api/v1/messages/${messageId}/voice${tokenParam}`;
        }
        if (!rawUrl) return '';
        try {
            const parsed = new URL(rawUrl, window.location.origin);
            return parsed.pathname + parsed.search;
        } catch (e) {
            return rawUrl;
        }
    }

    function toggleVoicePlay(messageId, audioUrl) {
        const targetUrl = resolvePlayableAudioUrl(messageId, audioUrl);
        if (!targetUrl) return;

        if (currentVoiceMessageId === messageId && currentVoiceAudio) {
            if (!currentVoiceAudio.paused) {
                currentVoiceAudio.pause();
                updateVoicePlayIcon(messageId, false);
            } else {
                currentVoiceAudio.play().then(() => updateVoicePlayIcon(messageId, true)).catch(() => {});
            }
            return;
        }

        if (currentVoiceAudio) {
            currentVoiceAudio.pause();
            if (currentVoiceMessageId) updateVoicePlayIcon(currentVoiceMessageId, false);
        }

        currentVoiceMessageId = messageId;
        currentVoiceAudio = new Audio(targetUrl);
        currentVoiceAudio.preload = 'auto';

        updateVoicePlayIcon(messageId, true);

        currentVoiceAudio.ontimeupdate = () => {
            if (!currentVoiceAudio) return;
            const cur = currentVoiceAudio.currentTime;
            const dur = currentVoiceAudio.duration || 1;
            const pct = Math.min(100, (cur / dur) * 100);

            const fillEl = document.getElementById(`voiceFill-${messageId}`);
            const timeEl = document.getElementById(`voiceTime-${messageId}`);
            if (fillEl) fillEl.style.width = pct + '%';
            if (timeEl) {
                const m = Math.floor(cur / 60);
                const s = Math.floor(cur % 60);
                timeEl.textContent = `${m}:${s < 10 ? '0' : ''}${s}`;
            }
        };

        currentVoiceAudio.onended = () => {
            updateVoicePlayIcon(messageId, false);
            const fillEl = document.getElementById(`voiceFill-${messageId}`);
            const timeEl = document.getElementById(`voiceTime-${messageId}`);
            if (fillEl) fillEl.style.width = '0%';
            if (timeEl) timeEl.textContent = '0:00';
            currentVoiceAudio = null;
            currentVoiceMessageId = null;
        };

        currentVoiceAudio.onerror = (e) => {
            console.error('Audio play error:', e);
            if (!currentVoiceAudio.dataset?.retried && messageId) {
                currentVoiceAudio.dataset = currentVoiceAudio.dataset || {};
                currentVoiceAudio.dataset.retried = '1';
                const token = (typeof currentToken !== 'undefined' && currentToken) ? currentToken : (localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '');
                const fallbackUrl = `/messages/${messageId}/voice` + (token ? `?token=${encodeURIComponent(token)}` : '');
                currentVoiceAudio.src = fallbackUrl;
                currentVoiceAudio.play().catch(() => {});
                return;
            }
            showToast('অডিও ফাইল প্লে করা সম্ভব হয়নি।');
            updateVoicePlayIcon(messageId, false);
            currentVoiceAudio = null;
            currentVoiceMessageId = null;
        };

        currentVoiceAudio.play().catch(e => {
            console.warn('Audio play failed:', e);
            updateVoicePlayIcon(messageId, false);
        });
    }

    function updateVoicePlayIcon(messageId, isPlaying) {
        const playIcon = document.getElementById(`voicePlayIcon-${messageId}`);
        const pauseIcon = document.getElementById(`voicePauseIcon-${messageId}`);
        if (playIcon) playIcon.style.display = isPlaying ? 'none' : 'block';
        if (pauseIcon) pauseIcon.style.display = isPlaying ? 'block' : 'none';
    }

    function seekVoicePlay(messageId, event) {
        if (currentVoiceMessageId !== messageId || !currentVoiceAudio) return;
        const track = document.getElementById(`voiceTrack-${messageId}`);
        if (!track) return;
        const rect = track.getBoundingClientRect();
        const clickX = event.clientX - rect.left;
        const pct = Math.max(0, Math.min(1, clickX / rect.width));
        currentVoiceAudio.currentTime = pct * (currentVoiceAudio.duration || 0);
    }

    // ==============================================
    // VOICE MESSAGE RECORDING (HTML5 MediaRecorder)
    // ==============================================
    let voiceStream = null;

    function startVoiceRecording() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('আপনার ব্রাউজারে অডিও রেকর্ডিং সাপোর্ট নেই।');
            return;
        }

        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(stream => {
                voiceStream = stream;
                voiceAudioChunks = [];

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
                    voiceMediaRecorder = selectedOptions ? new MediaRecorder(stream, selectedOptions) : new MediaRecorder(stream);
                } catch (e) {
                    voiceMediaRecorder = new MediaRecorder(stream);
                }

                voiceMediaRecorder.ondataavailable = e => {
                    if (e.data && e.data.size > 0) voiceAudioChunks.push(e.data);
                };
                voiceMediaRecorder.start();

                document.getElementById('composerTextBox').style.display = 'none';
                document.getElementById('voiceRecordBtn').style.display = 'none';
                document.getElementById('voiceRecordingBar').style.display = 'flex';

                voiceSeconds = 0;
                voiceTimerInterval = setInterval(() => {
                    voiceSeconds++;
                    const mins = Math.floor(voiceSeconds / 60);
                    const secs = voiceSeconds % 60;
                    document.getElementById('voiceRecordTimer').innerText = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
                }, 1000);
            })
            .catch(err => {
                console.error('Microphone access denied:', err);
                alert('মাইক্রোফোন অ্যাক্সেসের অনুমতি দেওয়া হয়নি। ব্রাউজার সেটিংসে গিয়ে অনুমতি দিন।');
            });
    }

    function stopAndSendVoiceRecording() {
        if (!voiceMediaRecorder) return;
        const recDuration = Math.max(1, voiceSeconds);

        voiceMediaRecorder.onstop = () => {
            const recordedType = voiceMediaRecorder.mimeType || (voiceAudioChunks[0]?.type) || 'audio/webm';
            let ext = 'webm';
            if (recordedType.includes('mp4') || recordedType.includes('aac')) ext = 'm4a';
            else if (recordedType.includes('ogg')) ext = 'ogg';
            else if (recordedType.includes('wav')) ext = 'wav';
            const audioBlob = new Blob(voiceAudioChunks, { type: recordedType });

            if (audioBlob.size < 100) {
                showToast('রেকর্ডিংটি খুব ছোট ছিল।');
                cleanupVoiceMedia();
                return;
            }

            const formData = new FormData();
            formData.append('audio', audioBlob, `voice_note_${Date.now()}.${ext}`);
            formData.append('duration', recDuration);

            const tempVoiceId = 'voice_temp_' + Date.now();
            const stream = document.getElementById('messagesStream');
            if (stream) {
                document.getElementById('emptyChatPlaceholder')?.remove();
                const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const optHtml = `
                    <div class="message-row outgoing" id="${tempVoiceId}">
                        <div class="message-bubble-wrapper">
                            <div class="message-bubble">
                                <div class="voice-message-player">
                                    <div style="font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                                        <span>ভয়েস মেসেজ পাঠানো হচ্ছে... (${recDuration} সে.)</span>
                                    </div>
                                </div>
                            </div>
                            <div class="message-meta-row"><span>${time}</span> <span>🕒</span></div>
                        </div>
                    </div>
                `;
                stream.insertAdjacentHTML('beforeend', optHtml);
                scrollToBottom();
            }

            fetch(`/api/v1/conversations/${currentConvId}/voice`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: getMessengerHeaders(),
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                document.getElementById(tempVoiceId)?.remove();
                if (res.success && res.data) {
                    appendOutgoingVoiceMessageBubble(res.data);
                    if (window.bondhooSoundManager && typeof window.bondhooSoundManager.playOutgoingMessageSentTone === 'function') {
                        window.bondhooSoundManager.playOutgoingMessageSentTone(res.data.id);
                    }
                } else {
                    alert(res.message || 'ভয়েস মেসেজ পাঠাতে ব্যর্থ হয়েছে।');
                }
            })
            .catch(() => {
                document.getElementById(tempVoiceId)?.remove();
                alert('নেটওয়ার্ক সমস্যার কারণে ভয়েস মেসেজ পাঠানো যায়নি।');
            });

            cleanupVoiceMedia();
        };

        voiceMediaRecorder.stop();
        resetVoiceUI();
    }

    function cancelVoiceRecording() {
        if (voiceMediaRecorder) {
            voiceMediaRecorder.ondataavailable = null;
            voiceMediaRecorder.onstop = null;
            if (voiceMediaRecorder.state !== 'inactive') {
                voiceMediaRecorder.stop();
            }
        }
        cleanupVoiceMedia();
        resetVoiceUI();
    }

    function cleanupVoiceMedia() {
        if (voiceStream) {
            voiceStream.getTracks().forEach(t => t.stop());
            voiceStream = null;
        }
        voiceAudioChunks = [];
        voiceMediaRecorder = null;
    }

    function resetVoiceUI() {
        clearInterval(voiceTimerInterval);
        document.getElementById('composerTextBox').style.display = 'flex';
        document.getElementById('voiceRecordBtn').style.display = 'inline-flex';
        document.getElementById('voiceRecordingBar').style.display = 'none';
        const timerEl = document.getElementById('voiceRecordTimer');
        if (timerEl) timerEl.innerText = '0:00';
    }

    function appendOutgoingVoiceMessageBubble(m) {
        const stream = document.getElementById('messagesStream');
        if (!stream) return;
        const time = new Date(m.sent_at || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const audioUrl = `/api/v1/messages/${m.id}/voice`;
        const durSec = m.metadata?.duration || 0;
        const durMin = Math.floor(durSec / 60);
        const durRem = durSec % 60;
        const durFormatted = `${durMin}:${durRem < 10 ? '0' : ''}${durRem}`;

        const html = `
            <div class="message-row outgoing" id="messageRow-${m.id}" data-id="${m.id}">
                <div class="message-bubble-wrapper">
                    <div class="message-bubble">
                        <div class="voice-message-player" id="voicePlayer-${m.id}" data-url="${audioUrl}">
                            <button type="button" class="voice-play-btn" onclick="toggleVoicePlay(${m.id}, '${audioUrl}')" id="voicePlayBtn-${m.id}" title="প্লে/পজ">
                                <svg id="voicePlayIcon-${m.id}" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                <svg id="voicePauseIcon-${m.id}" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display:none;"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
                            </button>
                            <div class="voice-player-body">
                                <div class="voice-progress-track" onclick="seekVoicePlay(${m.id}, event)" id="voiceTrack-${m.id}">
                                    <div class="voice-progress-fill" id="voiceFill-${m.id}" style="width: 0%;"></div>
                                </div>
                                <div class="voice-meta-info">
                                    <span id="voiceTime-${m.id}">0:00</span>
                                    <span>${durSec > 0 ? durFormatted : ''}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="message-meta-row">
                        <span>${time}</span>
                        <span class="delivery-check">✓</span>
                    </div>
                </div>
            </div>
        `;
        stream.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
    }


    // Emoji Picker
    function toggleEmojiPicker() {
        const picker = document.getElementById('emojiPickerBox');
        if (picker) {
            picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
        }
    }

    function insertEmoji(emoji) {
        const input = document.getElementById('chatMessageInput');
        if (input) {
            input.value += emoji;
            input.focus();
            toggleEmojiPicker();
        }
    }

    // Forwarding
    function openForwardModal(msgId) {
        currentForwardMessageId = msgId;
        openChatModal('forwardModal');
    }

    function submitForwardMessage() {
        if (!currentForwardMessageId) return;
        const targets = Array.from(document.querySelectorAll('input[name="forward_targets[]"]:checked')).map(cb => parseInt(cb.value));
        if (targets.length === 0) {
            alert('অনুগ্রহ করে অন্তত একটি চ্যাট নির্বাচন করুন।');
            return;
        }

        fetch(`/api/v1/messages/${currentForwardMessageId}/forward`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}) },
            body: JSON.stringify({ target_conversation_ids: targets })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeChatModal('forwardModal');
                alert('বার্তা সফলভাবে ফরোয়ার্ড করা হয়েছে।');
            }
        });
    }

    // Edit Message
    function openEditMessageModal(msgId, currentText) {
        document.getElementById('editMessageId').value = msgId;
        document.getElementById('editMessageOriginalText').value = currentText || '';
        
        const preview = document.getElementById('fullChatEditOriginalPreview');
        if (preview) preview.textContent = currentText || '(কোনো টেক্সট নেই)';

        const textarea = document.getElementById('editMessageTextarea');
        if (textarea) {
            textarea.value = currentText || '';
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(220, Math.max(95, textarea.scrollHeight)) + 'px';
        }

        updateFullChatEditCounter();
        openChatModal('editMessageModal');

        setTimeout(() => {
            if (textarea) {
                textarea.focus();
                textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            }
        }, 60);
    }

    function handleFullChatEditInput(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(220, Math.max(95, textarea.scrollHeight)) + 'px';
        updateFullChatEditCounter();
    }

    function handleFullChatEditKeydown(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeChatModal('editMessageModal');
            return;
        }
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            submitEditMessage();
        }
    }

    function insertFullChatEditEmoji(emoji) {
        const textarea = document.getElementById('editMessageTextarea');
        if (!textarea) return;
        const start = textarea.selectionStart || textarea.value.length;
        const end = textarea.selectionEnd || textarea.value.length;
        textarea.value = textarea.value.substring(0, start) + emoji + textarea.value.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
        textarea.focus();
        updateFullChatEditCounter();
    }

    function updateFullChatEditCounter() {
        const textarea = document.getElementById('editMessageTextarea');
        const counter = document.getElementById('fullChatEditCharCounter');
        const submitBtn = document.getElementById('fullChatEditSubmitBtn');
        const original = document.getElementById('editMessageOriginalText')?.value || '';

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

    function submitEditMessage() {
        const id = document.getElementById('editMessageId').value;
        const body = document.getElementById('editMessageTextarea').value.trim();
        const original = document.getElementById('editMessageOriginalText')?.value || '';
        if (!id || !body) return;
        if (body === original.trim()) {
            closeChatModal('editMessageModal');
            return;
        }

        const submitBtn = document.getElementById('fullChatEditSubmitBtn');
        const submitBtnText = document.getElementById('fullChatEditSubmitBtnText');
        const origText = submitBtnText ? submitBtnText.textContent : 'সংরক্ষণ করুন';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
            if (submitBtnText) submitBtnText.textContent = 'সংরক্ষণ হচ্ছে...';
        }

        fetch(`/api/v1/messages/${id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}) },
            body: JSON.stringify({ body })
        })
        .then(async r => {
            const res = await r.json();
            if (r.status === 409) {
                showToast('⚠️ অন্য কোনো সেশন থেকে বার্তাটি ইতিমধ্যেই পরিবর্তিত হয়েছে।');
                closeChatModal('editMessageModal');
                setTimeout(() => window.location.reload(), 1000);
                return;
            }
            if (res.success) {
                const row = document.getElementById(`messageRow-${id}`);
                const bubble = row?.querySelector('.message-bubble');
                if (bubble) {
                    const firstDiv = bubble.querySelector('div');
                    if (firstDiv) firstDiv.innerText = body;
                    else bubble.innerText = body;
                }
                const metaRow = row?.querySelector('.message-meta-row');
                if (metaRow && !metaRow.querySelector('.edited-badge')) {
                    const badge = document.createElement('span');
                    badge.className = 'edited-badge';
                    badge.textContent = '(সম্পাদিত)';
                    metaRow.appendChild(badge);
                }
                closeChatModal('editMessageModal');
                showToast('বার্তা সফলভাবে সম্পাদিত হয়েছে');
            } else {
                showToast(res.message || 'সম্পাদনা ব্যর্থ হয়েছে');
            }
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                if (submitBtnText) submitBtnText.textContent = origText;
            }
        });
    }

    // Delete Message
    function promptDeleteMessage(msgId, isMine) {
        let forEveryone = false;
        if (isMine) {
            forEveryone = confirm('আপনি কি এই বার্তাটি সবার জন্য মুছে ফেলতে চান? (Cancel চাপলে শুধুমাত্র আপনার জন্য মুছবে)');
        } else {
            if (!confirm('আপনি কি এই বার্তাটি আপনার চ্যাট থেকে মুছে ফেলতে চান?')) return;
        }

        const endpoint = forEveryone ? `/api/v1/messages/${msgId}/delete-for-everyone` : `/api/v1/messages/${msgId}/delete-for-me`;
        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const row = document.getElementById(`messageRow-${msgId}`);
                if (forEveryone) {
                    const bubble = row?.querySelector('.message-bubble');
                    if (bubble) bubble.innerHTML = `<em style="opacity: 0.7;">🚫 এই বার্তাটি মুছে ফেলা হয়েছে।</em>`;
                    const toolbar = row?.querySelector('.message-hover-toolbar');
                    if (toolbar) toolbar.remove();
                } else {
                    row?.remove();
                }
                showToast(forEveryone ? 'সবার জন্য বার্তাটি মুছে ফেলা হয়েছে' : 'বার্তাটি আপনার জন্য মুছে ফেলা হয়েছে');
            } else {
                showToast(res.message || 'বার্তাটি মুছে ফেলা যায়নি');
            }
        });
    }

    // Real-Time WebSocket & Polling Engine
    function initRealtimeMessenger() {
        // Polling fallback every 3.5s to ensure rock-solid syncing
        setInterval(() => {
            if (!currentConvId) return;
            fetch(`/api/v1/conversations/${currentConvId}/messages?per_page=15`, {
                headers: { 'Accept': 'application/json', ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}) }
            })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    syncIncomingMessages(res.data);
                }
            })
            .catch(() => {});
        }, 3500);
    }

    function syncIncomingMessages(messages) {
        messages.forEach(m => {
            if (!document.getElementById(`messageRow-${m.id}`)) {
                appendIncomingMessageBubble(m);
            }
        });
    }

    function appendIncomingMessageBubble(m) {
        if (m.sender && (m.sender.id === currentUserId || m.sender_id === currentUserId)) return;
        const stream = document.getElementById('messagesStream');
        if (!stream) return;

        if (m.type === 'call') {
            const callId = m.metadata?.call_id || m.id;
            const existingRow = document.getElementById(`callSummaryRow-${callId}`) || (m.id ? document.getElementById(`callSummaryRow-${m.id}`) : null);
            if (existingRow) {
                existingRow.innerText = m.body || 'কল';
                return;
            }
            const html = `
                <div class="system-message-row" id="callSummaryRow-${callId}" data-call-id="${callId}" style="background: rgba(24,119,242,0.1); color: var(--fb-primary); padding: 8px 16px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; margin: 8px auto;">
                    ${escapeHtml(m.body || 'কল')}
                </div>
            `;
            stream.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
            return;
        }

        const time = new Date(m.sent_at || m.created_at || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const mediaList = m.media || m.attachments || [];
        let mediaHtml = '';
        let hasImageMedia = false;

        if (mediaList.length > 0) {
            mediaHtml = `<div class="message-media-grid">` + mediaList.map(media => {
                const mime = media.mime_type || media.type || '';
                const isImg = mime.startsWith('image/') || /\.(png|jpe?g|webp|gif|avif)$/i.test(media.url || media.name || media.filename || '');
                const isVid = mime.startsWith('video/') || /\.(mp4|webm|mov)$/i.test(media.url || media.name || media.filename || '');
                const displayUrl = media.preview_url || media.url || media.urls?.preview || media.urls?.medium || media.urls?.original || (media.id ? `/api/v1/messages/attachments/${media.id}/view` : '');
                const fullUrl = media.original_url || media.urls?.original || displayUrl;
                const downloadUrl = media.download_url || media.urls?.download || (media.id ? `/api/v1/messages/attachments/${media.id}/download` : fullUrl);
                const mediaName = media.name || media.filename || 'ছবি';

                if (isImg) {
                    hasImageMedia = true;
                    return `
                        <div class="media-preview-item" onclick="openLightbox('${fullUrl}')" style="position: relative;">
                            <img src="${displayUrl}" alt="${escapeHtml(mediaName)}" loading="lazy" onerror="this.onerror=null; this.src='${fullUrl}'">
                        </div>
                    `;
                }
                if (isVid) {
                    return `
                        <div class="media-preview-item">
                            <video controls src="${fullUrl}"></video>
                        </div>
                    `;
                }
                return `
                    <a href="${downloadUrl}" class="file-attachment-card" target="_blank" download>
                        <span style="display: flex; align-items: center; color: var(--fb-primary);">📎</span>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 700; font-size: 13px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">${escapeHtml(mediaName)}</div>
                            <div style="font-size: 11px; opacity: 0.8;">${media.size ? Math.round(media.size / 1024) + ' KB' : 'ডাউনলোড'}</div>
                        </div>
                    </a>
                `;
            }).join('') + `</div>`;
        }

        // Suppress m.body if it is merely the filename
        let bodyText = (m.body || '').trim();
        let isFilenameText = false;
        if (bodyText && mediaList.length > 0) {
            isFilenameText = mediaList.some(med => {
                const fn = (med.name || med.filename || '').trim();
                const baseFn = (med.original_path ? med.original_path.split('/').pop() : '').trim();
                return bodyText === fn || bodyText === baseFn || (fn && bodyText.endsWith(fn));
            }) || /\.(png|jpe?g|webp|gif|avif|pdf|docx?|zip|mp4)$/i.test(bodyText);
        }

        const showBody = bodyText && !isFilenameText && m.type !== 'voice' && !m.metadata?.audio_url;

        let voiceHtml = '';
        if (m.type === 'voice' || m.metadata?.audio_url) {
            const audioUrl = `/api/v1/messages/${m.id}/voice`;
            const durSec = m.metadata?.duration || 0;
            const durMin = Math.floor(durSec / 60);
            const durRem = durSec % 60;
            const durFormatted = `${durMin}:${durRem < 10 ? '0' : ''}${durRem}`;
            voiceHtml = `
                <div class="voice-message-player" id="voicePlayer-${m.id}" data-url="${audioUrl}">
                    <button type="button" class="voice-play-btn" onclick="toggleVoicePlay(${m.id}, '${audioUrl}')" id="voicePlayBtn-${m.id}" title="প্লে/পজ">
                        <svg id="voicePlayIcon-${m.id}" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                        <svg id="voicePauseIcon-${m.id}" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display:none;"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
                    </button>
                    <div class="voice-player-body">
                        <div class="voice-progress-track" onclick="seekVoicePlay(${m.id}, event)" id="voiceTrack-${m.id}">
                            <div class="voice-progress-fill" id="voiceFill-${m.id}" style="width: 0%;"></div>
                        </div>
                        <div class="voice-meta-info">
                            <span id="voiceTime-${m.id}">0:00</span>
                            <span>${durSec > 0 ? durFormatted : ''}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        const isImageOnly = hasImageMedia && !showBody && !voiceHtml;

        const html = `
            <div class="message-row incoming" id="messageRow-${m.id}" data-id="${m.id}">
                <div class="message-bubble-wrapper">
                    <div class="message-bubble" style="${isImageOnly ? 'padding: 2px !important; background: transparent !important; border: none !important; box-shadow: none !important;' : ''}">
                        ${showBody ? `<div>${escapeHtml(bodyText)}</div>` : ''}
                        ${voiceHtml}
                        ${mediaHtml}
                    </div>
                    <div class="message-meta-row"><span>${time}</span></div>
                </div>
            </div>
        `;
        stream.insertAdjacentHTML('beforeend', html);
        scrollToBottom();

        // Mark as read
        fetch(`/api/v1/conversations/${currentConvId}/read`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}) }
        }).catch(() => {});
    }


    // Modals open/close helpers
    function openChatModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeChatModal(id) { document.getElementById(id).style.display = 'none'; }

    let currentLightboxScale = 1.0;
    let currentLightboxImages = [];
    let currentLightboxIndex = 0;

    function openLightbox(url, allImages = [], index = 0) {
        const modal = document.getElementById('imageLightbox');
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
        const modal = document.getElementById('imageLightbox');
        if (modal) modal.style.display = 'none';
        resetLightboxZoom();
    }

    window.addEventListener('keydown', (e) => {
        const modal = document.getElementById('imageLightbox');
        if (!modal || modal.style.display === 'none') return;
        if (e.key === 'Escape') closeLightboxModal();
        else if (e.key === 'ArrowLeft') navigateLightbox(-1);
        else if (e.key === 'ArrowRight') navigateLightbox(1);
    });

    function escapeHtml(str) {
        return (str || '').replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag));
    }

    // ==============================================
    // TOAST NOTIFICATIONS & CLIPBOARD
    // ==============================================
    function showToast(msg) {
        const toast = document.getElementById('toastNotification');
        const txt = document.getElementById('toastNotificationText');
        if (toast && txt) {
            txt.innerText = msg;
            toast.style.display = 'block';
            clearTimeout(window.__toastTimeout);
            window.__toastTimeout = setTimeout(() => {
                toast.style.display = 'none';
            }, 3200);
        }
    }

    function copyMessageText(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('টেক্সট ক্লিপবোর্ডে কপি করা হয়েছে');
            }).catch(() => {
                showToast('টেক্সট কপি করা সম্ভব হয়নি');
            });
        } else {
            const ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            showToast('টেক্সট ক্লিপবোর্ডে কপি করা হয়েছে');
        }
    }

    // ==============================================
    // MESSAGE REACTIONS VIEWER MODAL
    // ==============================================
    function openReactionsModal(messageId) {
        const listEl = document.getElementById('reactionsListContent');
        if (!listEl) return;
        listEl.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px;">লোড হচ্ছে...</div>';
        openChatModal('reactionsListModal');

        fetch(`/api/v1/messages/${messageId}/reactions`, {
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                if (res.data.length === 0) {
                    listEl.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px;">এখনো কোনো প্রতিক্রিয়া যোগ করা হয়নি।</div>';
                    return;
                }
                listEl.innerHTML = res.data.map(item => `
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="avatar" style="width: 34px; height: 34px;">
                                ${item.user.avatar_url ? `<img src="${item.user.avatar_url}" alt="">` : (item.user.name || 'U').charAt(0)}
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 13px; color: var(--fb-text-primary);">${escapeHtml(item.user.name)}</div>
                                <div style="font-size: 11px; color: var(--fb-text-secondary);">@${escapeHtml(item.user.username || '')}</div>
                            </div>
                        </div>
                        <span style="font-size: 20px;">${item.reaction}</span>
                    </div>
                `).join('');
            } else {
                listEl.innerHTML = '<div style="text-align: center; color: #ef4444; font-size: 13px;">প্রতিক্রিয়া লোড করা সম্ভব হয়নি।</div>';
            }
        })
        .catch(() => {
            listEl.innerHTML = '<div style="text-align: center; color: #ef4444; font-size: 13px;">সার্ভারে ত্রুটি হয়েছে।</div>';
        });
    }

    // ==============================================
    // NEW CHAT & GROUP CREATION MODAL
    // ==============================================
    function openNewChatModal() {
        switchNewChatMode('direct');
        const input = document.getElementById('newChatUserSearchInput');
        if (input) input.value = '';
        const clearBtn = document.getElementById('newChatSearchClearBtn');
        if (clearBtn) clearBtn.style.display = 'none';
        searchUsersForDirectChat('');
        openChatModal('newChatModal');
        setTimeout(() => {
            if (input) input.focus();
        }, 150);
    }

    function openNewGroupModal() {
        openNewChatModal();
        switchNewChatMode('group');
    }

    function switchNewChatMode(mode) {
        const directSec = document.getElementById('newChatDirectSection');
        const groupSec = document.getElementById('newChatGroupSection');
        const tabDirect = document.getElementById('tabModeDirect');
        const tabGroup = document.getElementById('tabModeGroup');

        if (mode === 'direct') {
            directSec.style.display = 'block';
            groupSec.style.display = 'none';
            tabDirect.classList.add('active');
            tabGroup.classList.remove('active');
        } else {
            directSec.style.display = 'none';
            groupSec.style.display = 'block';
            tabDirect.classList.remove('active');
            tabGroup.classList.add('active');
            loadFriendsForGroupModal();
        }
    }

    function handleNewChatSearchInput(input) {
        const val = input.value.trim();
        const clearBtn = document.getElementById('newChatSearchClearBtn');
        if (clearBtn) {
            clearBtn.style.display = val ? 'flex' : 'none';
        }
        searchUsersForDirectChat(input.value);
    }

    function clearNewChatSearch() {
        const input = document.getElementById('newChatUserSearchInput');
        const clearBtn = document.getElementById('newChatSearchClearBtn');
        if (input) {
            input.value = '';
            input.focus();
        }
        if (clearBtn) {
            clearBtn.style.display = 'none';
        }
        searchUsersForDirectChat('');
    }

    let __searchUsersTimeout = null;
    function searchUsersForDirectChat(query) {
        clearTimeout(__searchUsersTimeout);
        __searchUsersTimeout = setTimeout(() => {
            const resultsEl = document.getElementById('newChatUserResults');
            if (!resultsEl) return;

            resultsEl.innerHTML = `
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 36px 16px; gap: 10px;">
                    <div style="width: 24px; height: 24px; border: 3px solid #e4e6eb; border-top-color: var(--ms-primary); border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                    <div style="font-size: 13px; color: var(--ms-text-secondary); font-weight: 500;">ব্যবহারকারী খোঁজা হচ্ছে...</div>
                </div>
            `;

            const trimmed = query.trim();
            const endpoint = trimmed
                ? `/api/v1/search?q=${encodeURIComponent(trimmed)}&type=users`
                : `/api/v1/friends`;

            fetch(endpoint, {
                headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
            })
            .then(r => r.json())
            .then(res => {
                let list = [];
                if (Array.isArray(res.data)) {
                    list = res.data;
                } else if (res.data?.results?.users && Array.isArray(res.data.results.users)) {
                    list = res.data.results.users;
                } else if (res.data?.users && Array.isArray(res.data.users)) {
                    list = res.data.users;
                } else if (Array.isArray(res)) {
                    list = res;
                }

                const users = list.filter(u => u && u.id !== currentUserId);

                if (users.length === 0) {
                    if (trimmed) {
                        resultsEl.innerHTML = `
                            <div class="search-empty-state">
                                <div class="search-empty-icon">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        <line x1="8" y1="11" x2="14" y2="11"></line>
                                    </svg>
                                </div>
                                <div class="search-empty-title">কোনো ফলাফল পাওয়া যায়নি</div>
                                <div class="search-empty-desc">"${escapeHtml(trimmed)}" নামের কাউকে খুঁজে পাওয়া যায়নি। সঠিক নাম বা ইউজারনেম দিয়ে আবার চেষ্টা করুন।</div>
                            </div>
                        `;
                    } else {
                        resultsEl.innerHTML = `
                            <div class="search-empty-state">
                                <div class="search-empty-icon">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                                <div class="search-empty-title">সরাসরি বার্তা পাঠান</div>
                                <div class="search-empty-desc">যেকোনো বন্ধু বা ব্যবহারকারীকে খুঁজে পেতে উপরের বক্সে নাম বা ইউজারনেম টাইপ করুন।</div>
                            </div>
                        `;
                    }
                    return;
                }

                const headerLabel = trimmed ? `সার্চ ফলাফল (${users.length})` : `প্রস্তাবিত বন্ধু (${users.length})`;
                let html = `<div class="search-section-header">${headerLabel}</div>`;

                html += users.map(u => {
                    const avatarUrl = u.profile?.avatar_url || u.avatar_url || '';
                    const initial = (u.name || 'U').charAt(0).toUpperCase();
                    const username = u.username ? `@${escapeHtml(u.username)}` : '';
                    const subtitle = username || (u.bio ? escapeHtml(u.bio) : 'যোগাযোগ করুন');

                    return `
                        <div class="search-user-card" onclick="createOrOpenDirectChat(${u.id})">
                            <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                <div class="avatar">
                                    ${avatarUrl ? `<img src="${avatarUrl}" alt="${escapeHtml(u.name)}" style="width: 100%; height: 100%; object-fit: cover;">` : initial}
                                </div>
                                <div style="min-width: 0;">
                                    <div style="font-weight: 700; font-size: 14.5px; color: var(--ms-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        ${escapeHtml(u.name)}
                                    </div>
                                    <div style="font-size: 12px; color: var(--ms-text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        ${subtitle}
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-chat-action">বার্তা পাঠান</button>
                        </div>
                    `;
                }).join('');

                resultsEl.innerHTML = html;
            })
            .catch(err => {
                console.error('Search error:', err);
                resultsEl.innerHTML = `
                    <div class="search-empty-state">
                        <div style="color: #ef4444; font-size: 14px; font-weight: 600;">সার্চ করতে সমস্যা হয়েছে।</div>
                        <div class="search-empty-desc" style="margin-top: 4px;">অনুগ্রহ করে পুনরায় চেষ্টা করুন।</div>
                    </div>
                `;
            });
        }, 250);
    }

    function createOrOpenDirectChat(userId) {
        fetch('/api/v1/conversations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) },
            body: JSON.stringify({ type: 'direct', recipient_id: userId })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                closeChatModal('newChatModal');
                window.location.href = `/messages?conversation_id=${res.data.id}`;
            } else {
                alert(res.message || 'চ্যাট শুরু করা সম্ভব হয়নি।');
            }
        })
        .catch(() => {
            alert('সার্ভারে সমস্যা হয়েছে। পুনরায় চেষ্টা করুন।');
        });
    }

    let __cachedFriends = [];
    function loadFriendsForGroupModal() {
        const listEl = document.getElementById('groupMembersSelectionList');
        if (!listEl) return;
        if (__cachedFriends.length > 0) {
            renderGroupMembersChecklist(__cachedFriends);
            return;
        }
        listEl.innerHTML = '<div style="font-size: 12.5px; color: var(--ms-text-secondary); text-align: center; padding: 14px;">বন্ধুদের তালিকা লোড হচ্ছে...</div>';
        fetch('/api/v1/friends', {
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            __cachedFriends = Array.isArray(res.data) ? res.data : (res.data?.friends || []);
            renderGroupMembersChecklist(__cachedFriends);
        })
        .catch(() => {
            listEl.innerHTML = '<div style="font-size: 12.5px; color: #ef4444; text-align: center; padding: 14px;">তালিকা লোড করা যায়নি।</div>';
        });
    }

    let __selectedGroupMemberIds = new Set();

    function renderGroupMembersChecklist(friends) {
        const listEl = document.getElementById('groupMembersSelectionList');
        if (!listEl) return;
        if (!friends || friends.length === 0) {
            listEl.innerHTML = '<div style="font-size: 12.5px; color: var(--ms-text-secondary); text-align: center; padding: 14px;">গ্রুপে যুক্ত করার মতো কোনো বন্ধু নেই।</div>';
            return;
        }
        listEl.innerHTML = friends.map(f => {
            const avatarUrl = f.profile?.avatar_url || f.avatar_url || '';
            const initial = (f.name || 'U').charAt(0).toUpperCase();
            const isChecked = __selectedGroupMemberIds.has(Number(f.id));
            return `
                <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-radius: 10px; cursor: pointer; background: var(--ms-bg-card); transition: background 0.15s; margin-bottom: 4px;" onmouseover="this.style.background='var(--ms-bg-hover)'" onmouseout="this.style.background='var(--ms-bg-card)'">
                    <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                        <div class="avatar" style="width: 34px; height: 34px; font-size: 13px; flex-shrink: 0;">
                            ${avatarUrl ? `<img src="${avatarUrl}" alt="${escapeHtml(f.name)}" style="width: 100%; height: 100%; object-fit: cover;">` : initial}
                        </div>
                        <div style="min-width: 0;">
                            <div style="font-size: 13.5px; font-weight: 700; color: var(--ms-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(f.name)}</div>
                            ${f.username ? `<div style="font-size: 11.5px; color: var(--ms-text-secondary);">@${escapeHtml(f.username)}</div>` : ''}
                        </div>
                    </div>
                    <input type="checkbox" name="group_members_select[]" value="${f.id}" ${isChecked ? 'checked' : ''} onchange="handleGroupMemberToggle(this)" style="width: 18px; height: 18px; accent-color: var(--ms-primary); cursor: pointer;">
                </label>
            `;
        }).join('');
    }

    function handleGroupMemberToggle(checkbox) {
        const id = Number(checkbox.value);
        if (checkbox.checked) {
            __selectedGroupMemberIds.add(id);
        } else {
            __selectedGroupMemberIds.delete(id);
        }
        updateGroupSelectedCount();
    }

    function updateGroupSelectedCount() {
        const countEl = document.getElementById('groupSelectedCount');
        if (countEl) {
            countEl.textContent = `নির্বাচিত: ${__selectedGroupMemberIds.size} জন`;
        }
    }

    function filterGroupFriends(query) {
        const q = (query || '').toLowerCase().trim();
        if (!__cachedFriends) return;
        if (!q) {
            renderGroupMembersChecklist(__cachedFriends);
            return;
        }
        const filtered = __cachedFriends.filter(f => {
            const name = (f.name || '').toLowerCase();
            const username = (f.username || '').toLowerCase();
            return name.includes(q) || username.includes(q);
        });
        renderGroupMembersChecklist(filtered);
    }

    function submitCreateGroup() {
        const title = document.getElementById('groupTitleInput')?.value.trim();
        const description = document.getElementById('groupDescInput')?.value.trim();
        const avatarUrl = document.getElementById('groupAvatarUrlInput')?.value.trim();
        const btnSubmit = document.getElementById('btnSubmitCreateGroup');

        if (!title) {
            alert('অনুগ্রহ করে গ্রুপের নাম লিখুন।');
            return;
        }
        const memberIds = Array.from(__selectedGroupMemberIds);
        if (memberIds.length < 1) {
            alert('অনুগ্রহ করে অন্তত একজন বন্ধুকে সদস্য হিসেবে নির্বাচন করুন।');
            return;
        }

        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'গ্রুপ তৈরি হচ্ছে...';
        }

        fetch('/api/v1/conversations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) },
            body: JSON.stringify({
                type: 'group',
                title: title,
                description: description || null,
                avatar_url: avatarUrl || null,
                participant_ids: memberIds
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                closeChatModal('newChatModal');
                window.location.href = `/messages?conversation_id=${res.data.id}`;
            } else {
                alert(res.message || 'গ্রুপ তৈরি করা যায়নি।');
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'গ্রুপ তৈরি করুন';
                }
            }
        })
        .catch(() => {
            alert('সার্ভারে ত্রুটি হয়েছে।');
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'গ্রুপ তৈরি করুন';
            }
        });
    }

    // ==============================================
    // CONVERSATION MUTE, PIN, ARCHIVE & UNREAD
    // ==============================================
    function openMuteModal() {
        if (!currentConvId) return;
        openChatModal('muteConversationModal');
    }

    function submitMuteConversation() {
        if (!currentConvId) return;
        const duration = document.querySelector('input[name="muteDuration"]:checked')?.value || '1h';
        fetch(`/api/v1/conversations/${currentConvId}/mute`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) },
            body: JSON.stringify({ duration: duration })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeChatModal('muteConversationModal');
                showToast(duration === 'unmute' ? 'নোটিফিকেশন আনমিউট করা হয়েছে' : 'নোটিফিকেশন মিউট করা হয়েছে');
            } else {
                alert(res.message || 'মিউট করা সম্ভব হয়নি।');
            }
        });
    }

    function togglePinActiveConv() {
        if (!currentConvId) return;
        fetch(`/api/v1/conversations/${currentConvId}/pin`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(res.data?.is_pinned ? 'চ্যাট পিন করা হয়েছে' : 'চ্যাট আনপিন করা হয়েছে');
                setTimeout(() => window.location.reload(), 800);
            }
        });
    }

    function toggleArchiveActiveConv() {
        if (!currentConvId) return;
        fetch(`/api/v1/conversations/${currentConvId}/archive`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(res.data?.is_archived ? 'চ্যাট আর্কাইভ করা হয়েছে' : 'চ্যাট আনআর্কাইভ করা হয়েছে');
                setTimeout(() => window.location.href = '/messages', 800);
            }
        });
    }

    function markCurrentConvUnread() {
        if (!currentConvId) return;
        fetch(`/api/v1/conversations/${currentConvId}/unread`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('চ্যাট অপঠিত হিসেবে চিহ্নিত করা হয়েছে');
                setTimeout(() => window.location.href = '/messages', 800);
            }
        });
    }

    function clearCurrentChatHistory() {
        if (!currentConvId) return;
        const msg = "Clear chat history?\n\nThis will remove the conversation history from your chat view. It will not delete the other participant's copy.\n\n(এটি শুধুমাত্র আপনার চ্যাট ভিউ থেকে মেসেজগুলো মুছে ফেলবে। অপর প্রান্তের ব্যবহারকারীর চ্যাট কপি অক্ষত থাকবে।)";
        if (!confirm(msg)) return;
        fetch(`/api/v1/conversations/${currentConvId}/clear`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('চ্যাট হিস্ট্রি সফলভাবে ক্লিয়ার করা হয়েছে');
                const stream = document.getElementById('messagesStream');
                if (stream) {
                    stream.innerHTML = '<div class="empty-chat-state" style="text-align: center; color: var(--fb-text-secondary); padding: 40px;"><p>চ্যাট হিস্ট্রি মুছে ফেলা হয়েছে। নতুন মেসেজ দিয়ে কথোপকথন শুরু করুন!</p></div>';
                }
            } else {
                showToast(res.message || 'চ্যাট হিস্ট্রি ক্লিয়ার করা যায়নি');
            }
        });
    }

    function deleteCurrentConversation() {
        if (!currentConvId) return;
        if (!confirm('আপনি কি এই কনভার্সেশনটি সম্পূর্ণ ডিলিট করতে চান?')) return;
        fetch(`/api/v1/conversations/${currentConvId}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('কনভার্সেশন সফলভাবে মুছে ফেলা হয়েছে');
                setTimeout(() => window.location.href = '/messages', 700);
            } else {
                alert(res.message || 'ডিলিট করা সম্ভব হয়নি।');
            }
        });
    }

    // ==============================================
    // GROUP MEMBERS MANAGEMENT
    // ==============================================
    function openAddMemberModal() {
        openChatModal('addMemberModal');
        loadFriendsToAddMemberModal();
    }

    let __cachedAddFriends = [];
    function loadFriendsToAddMemberModal() {
        const listEl = document.getElementById('addMembersChecklist');
        if (!listEl) return;
        listEl.innerHTML = '<div style="color: var(--ms-text-secondary); font-size: 13px; text-align: center; padding: 12px;">বন্ধুদের লোড করা হচ্ছে...</div>';
        fetch('/api/v1/friends', {
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            __cachedAddFriends = Array.isArray(res.data) ? res.data : (res.data?.friends || []);
            renderAddMembersChecklist(__cachedAddFriends);
        })
        .catch(() => {
            listEl.innerHTML = '<div style="color: #ef4444; font-size: 13px; text-align: center; padding: 12px;">তালিকা লোড করা যায়নি।</div>';
        });
    }

    function filterAddMembersList(query) {
        const q = query.toLowerCase().trim();
        const filtered = __cachedAddFriends.filter(f => (f.name || '').toLowerCase().includes(q) || (f.username || '').toLowerCase().includes(q));
        renderAddMembersChecklist(filtered);
    }

    function renderAddMembersChecklist(friends) {
        const listEl = document.getElementById('addMembersChecklist');
        if (!listEl) return;
        if (!friends || friends.length === 0) {
            listEl.innerHTML = '<div style="color: var(--ms-text-secondary); font-size: 13px; text-align: center; padding: 12px;">কোনো বন্ধু পাওয়া যায়নি।</div>';
            return;
        }
        listEl.innerHTML = friends.map(f => {
            const avatarUrl = f.profile?.avatar_url || f.avatar_url || '';
            const initial = (f.name || 'U').charAt(0).toUpperCase();
            return `
                <label style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-radius: 10px; cursor: pointer; background: var(--ms-bg-card); transition: background 0.15s; margin-bottom: 2px;" onmouseover="this.style.background='var(--ms-bg-hover)'" onmouseout="this.style.background='var(--ms-bg-card)'">
                    <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                        <div class="avatar" style="width: 32px; height: 32px; font-size: 12.5px; flex-shrink: 0;">
                            ${avatarUrl ? `<img src="${avatarUrl}" alt="${escapeHtml(f.name)}" style="width: 100%; height: 100%; object-fit: cover;">` : initial}
                        </div>
                        <div style="min-width: 0;">
                            <div style="font-size: 13.5px; font-weight: 700; color: var(--ms-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(f.name)}</div>
                            ${f.username ? `<div style="font-size: 11.5px; color: var(--ms-text-secondary);">@${escapeHtml(f.username)}</div>` : ''}
                        </div>
                    </div>
                    <input type="checkbox" name="add_group_members_select[]" value="${f.id}" style="width: 18px; height: 18px; accent-color: var(--ms-primary); cursor: pointer;">
                </label>
            `;
        }).join('');
    }

    function submitAddGroupMembers() {
        if (!currentConvId) return;
        const selected = Array.from(document.querySelectorAll('input[name="add_group_members_select[]"]:checked')).map(cb => parseInt(cb.value));
        if (selected.length === 0) {
            alert('অনুগ্রহ করে অন্তত একজন সদস্য নির্বাচন করুন।');
            return;
        }
        fetch(`/api/v1/conversations/${currentConvId}/members`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) },
            body: JSON.stringify({ user_ids: selected })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeChatModal('addMemberModal');
                showToast('সদস্যদের গ্রুপে যুক্ত করা হয়েছে');
                setTimeout(() => window.location.reload(), 700);
            } else {
                alert(res.message || 'সদস্য যুক্ত করা যায়নি।');
            }
        });
    }

    function removeGroupMember(userId, name) {
        if (!currentConvId) return;
        if (!confirm(`${name}-কে গ্রুপ থেকে অপসারণ করতে চান?`)) return;
        fetch(`/api/v1/conversations/${currentConvId}/members/${userId}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(`${name}-কে গ্রুপ থেকে রিমুভ করা হয়েছে`);
                setTimeout(() => window.location.reload(), 700);
            }
        });
    }

    function confirmLeaveGroup() {
        if (!currentConvId) return;
        if (!confirm('আপনি কি সত্যিই এই গ্রুপটি ত্যাগ করতে চান?')) return;
        fetch(`/api/v1/conversations/${currentConvId}/leave`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('আপনি গ্রুপ ত্যাগ করেছেন');
                setTimeout(() => window.location.href = '/messages', 700);
            }
        });
    }

    // ==============================================
    // BLOCK & REPORT
    // ==============================================
    function promptBlockUser(userId, name) {
        if (!confirm(`আপনি কি সত্যিই ${name}-কে ব্লক করতে চান? ব্লক করার পর তিনি আর বার্তা পাঠাতে বা কল করতে পারবেন না।`)) return;
        fetch(`/api/v1/users/${userId}/block`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(`${name}-কে ব্লক করা হয়েছে`);
                setTimeout(() => window.location.href = '/messages', 800);
            } else {
                alert(res.message || 'ব্লক করা সম্ভব হয়নি।');
            }
        });
    }

    function openReportModal(type = 'conversation', id = null) {
        document.getElementById('reportTargetType').value = type;
        document.getElementById('reportTargetId').value = id || currentConvId;
        openChatModal('reportModal');
    }

    function submitReport() {
        const type = document.getElementById('reportTargetType').value;
        const id = document.getElementById('reportTargetId').value;
        const reason = document.getElementById('reportReasonSelect').value;
        const details = document.getElementById('reportDetailsTextarea').value.trim();

        fetch('/api/v1/reports', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) },
            body: JSON.stringify({
                reportable_type: type === 'message' ? 'message' : 'conversation',
                reportable_id: parseInt(id),
                reason: reason,
                details: details || null
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeChatModal('reportModal');
                showToast('আপনার রিপোর্টটি পর্যালোচনার জন্য জমা নেওয়া হয়েছে');
            } else {
                alert(res.message || 'রিপোর্ট জমা নেওয়া সম্ভব হয়নি।');
            }
        })
        .catch(() => {
            alert('সার্ভারে ত্রুটি হয়েছে।');
        });
    }

    // ==============================================
    // SHARED CONTENT TABS (MEDIA, FILES, LINKS)
    // ==============================================
    function loadSharedTab(type, el) {
        if (el) {
            document.querySelectorAll('#sharedContentBox ~ div button, .details-nav-section .filter-pill').forEach(b => {
                if (b.parentNode === el.parentNode) b.classList.remove('active');
            });
            el.classList.add('active');
        }

        const grid = document.getElementById('sharedMediaGrid');
        const files = document.getElementById('sharedFilesList');
        const links = document.getElementById('sharedLinksList');

        if (!grid || !files || !links || !currentConvId) return;

        grid.style.display = 'none';
        files.style.display = 'none';
        links.style.display = 'none';

        if (type === 'media') {
            grid.style.display = 'grid';
            grid.innerHTML = '<div style="font-size: 11px; color: var(--fb-text-secondary); grid-column: span 3; text-align: center; padding: 12px;">মিডিয়া লোড হচ্ছে...</div>';
        } else if (type === 'files') {
            files.style.display = 'flex';
            files.style.flexDirection = 'column';
            files.style.gap = '8px';
            files.innerHTML = '<div style="font-size: 11px; color: var(--fb-text-secondary); text-align: center; padding: 12px;">ফাইল লোড হচ্ছে...</div>';
        } else if (type === 'links') {
            links.style.display = 'flex';
            links.style.flexDirection = 'column';
            links.style.gap = '8px';
            links.innerHTML = '<div style="font-size: 11px; color: var(--fb-text-secondary); text-align: center; padding: 12px;">লিঙ্ক লোড হচ্ছে...</div>';
        }

        fetch(`/api/v1/conversations/${currentConvId}/media?type=${type}`, {
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            const items = res.data || [];
            if (type === 'media') {
                if (items.length === 0) {
                    grid.innerHTML = '<div style="font-size: 11px; color: var(--fb-text-secondary); grid-column: span 3; text-align: center; padding: 12px;">কোনো মিডিয়া পাওয়া যায়নি</div>';
                    return;
                }
                grid.innerHTML = items.map(m => {
                    const displayUrl = m.preview_url || m.urls?.medium || m.urls?.thumbnail || m.url || '';
                    const fullUrl = m.original_url || m.urls?.original || m.url || displayUrl;
                    const fileName = m.name || m.filename || 'ছবি';
                    return `
                        <div class="shared-media-item" onclick="openLightbox('${fullUrl}')" title="${escapeHtml(fileName)}">
                            <img src="${displayUrl}" alt="${escapeHtml(fileName)}" loading="lazy" onerror="this.onerror=null; this.src='${fullUrl}'">
                        </div>
                    `;
                }).join('');
            } else if (type === 'files') {
                if (items.length === 0) {
                    files.innerHTML = '<div style="font-size: 11px; color: var(--fb-text-secondary); text-align: center; padding: 12px;">কোনো ফাইল পাওয়া যায়নি</div>';
                    return;
                }
                files.innerHTML = items.map(f => `
                    <a href="${f.url}" target="_blank" download style="display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; background: var(--fb-bg); border-radius: 8px; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            <span style="font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">${escapeHtml(f.filename || 'ডকুমেন্ট')}</span>
                        </div>
                        <span style="font-size: 11px; color: var(--fb-text-secondary);">${f.formatted_size || ''}</span>
                    </a>
                `).join('');
            } else if (type === 'links') {
                if (items.length === 0) {
                    links.innerHTML = '<div style="font-size: 11px; color: var(--fb-text-secondary); text-align: center; padding: 12px;">কোনো লিঙ্ক পাওয়া যায়নি</div>';
                    return;
                }
                links.innerHTML = items.map(l => `
                    <a href="${escapeHtml(l.url || '#')}" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; gap: 8px; padding: 8px 10px; background: var(--fb-bg); border-radius: 8px; text-decoration: none; color: inherit;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                        <span style="font-size: 12px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;">${escapeHtml(l.title || l.url || 'লিঙ্ক')}</span>
                    </a>
                `).join('');
            }
        })
        .catch(() => {});
    }

    // ==============================================
    // CALL HISTORY MODAL & LOGS
    // ==============================================
    function openCallHistoryModal() {
        openChatModal('callHistoryModal');
        loadCallHistory();
    }

    let callHistoryPage = 1;
    let callHistoryLoading = false;

    function openCallHistoryModal() {
        openChatModal('callHistoryModal');
        callHistoryPage = 1;
        loadCallHistory(1, false);
    }

    function loadCallHistory(page = 1, append = false) {
        const listEl = document.getElementById('callHistoryListContent');
        if (!listEl || callHistoryLoading) return;
        callHistoryLoading = true;

        if (!append) {
            listEl.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px; padding: 16px;">কল হিস্ট্রি লোড হচ্ছে...</div>';
        }

        fetch(`/api/v1/calls/history?page=${page}&per_page=15`, {
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            callHistoryLoading = false;
            let calls = res.data || [];
            if (!append && calls.length === 0) {
                listEl.innerHTML = '<div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px; padding: 24px;">কোনো কলের ইতিহাস নেই।</div>';
                return;
            }

            // Deduplicate items against existing DOM when appending, and within current page
            const seenCallIds = new Set();
            if (append) {
                listEl.querySelectorAll('[data-call-history-id]').forEach(el => {
                    const cid = el.getAttribute('data-call-history-id');
                    if (cid) seenCallIds.add(String(cid));
                });
            }
            calls = calls.filter(c => {
                const idStr = String(c.id);
                if (seenCallIds.has(idStr)) return false;
                seenCallIds.add(idStr);
                return true;
            });

            const html = calls.map(c => {
                const isOutgoing = Number(c.caller?.id) === Number(currentUserId);
                const peer = isOutgoing
                    ? (c.participants?.find(p => p.user && Number(p.user.id) !== Number(currentUserId))?.user || c.recipient || { name: 'ইউজার' })
                    : (c.caller?.id && Number(c.caller.id) !== Number(currentUserId) ? c.caller : (c.participants?.find(p => p.user && Number(p.user.id) !== Number(currentUserId))?.user || { name: 'ইউজার' }));
                const formattedTime = new Date(c.started_at || c.created_at).toLocaleString([], { dateStyle: 'short', timeStyle: 'short' });

                const durSec = Math.max(0, parseInt(c.duration_seconds || 0, 10));
                const durMin = Math.floor(durSec / 60);
                const durRem = durSec % 60;
                const durStr = durSec > 0 ? `${durMin}:${durRem < 10 ? '0' : ''}${durRem}` : '';

                let statusLabel = 'অজানা';
                let statusColor = '#6b7280';
                if (c.status === 'ended') {
                    if (durSec > 0) {
                        statusLabel = `সম্পন্ন (${durStr})`;
                        statusColor = '#10b981';
                    } else {
                        statusLabel = isOutgoing ? 'উত্তর মেলেনি' : 'মিসড কল';
                        statusColor = '#ef4444';
                    }
                } else if (c.status === 'active') {
                    statusLabel = 'চলমান';
                    statusColor = '#3b82f6';
                } else if (c.status === 'missed') {
                    statusLabel = isOutgoing ? 'বাতিল' : 'মিসড কল';
                    statusColor = '#ef4444';
                } else if (c.status === 'rejected') {
                    statusLabel = 'প্রত্যাখ্যাত';
                    statusColor = '#f59e0b';
                } else if (c.status === 'busy') {
                    statusLabel = 'ব্যস্ত';
                    statusColor = '#f59e0b';
                } else if (c.status === 'failed') {
                    statusLabel = 'ব্যর্থ';
                    statusColor = '#ef4444';
                } else {
                    statusLabel = c.status || 'কল';
                }

                const iconSvg = (c.call_type === 'video' || c.call_type === 'group_video')
                    ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>`
                    : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>`;

                const peerAvatar = peer?.avatar_url || peer?.profile?.avatar_url;
                const peerName = peer?.name || 'ইউজার';
                const callTypeLabel = (c.call_type === 'video' || c.call_type === 'group_video') ? 'ভিডিও' : 'অডিও';

                return `
                    <div id="callHistoryItem-${c.id}" data-call-history-id="${c.id}" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: var(--fb-bg); border-radius: 10px; transition: background 0.2s;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="avatar" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; background: #e4e6eb; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #1c1e21;">
                                ${peerAvatar ? `<img src="${peerAvatar}" style="width: 100%; height: 100%; object-fit: cover;">` : escapeHtml(peerName.charAt(0))}
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 13px; color: var(--fb-text-primary);">${escapeHtml(peerName)}</div>
                                <div style="font-size: 11px; color: var(--fb-text-secondary); display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                                    <span>${isOutgoing ? '↗ আউটগোয়িং' : '↙ ইনকামিং'} (${callTypeLabel})</span>
                                    <span>•</span>
                                    <span>${formattedTime}</span>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 11px; font-weight: 700; color: ${statusColor}; background: rgba(0,0,0,0.04); padding: 4px 8px; border-radius: 12px;">${statusLabel}</span>
                            <button type="button" onclick="closeChatModal('callHistoryModal'); ${c.conversation_id ? `window.location.href='/call/${c.conversation_id}?type=${(c.call_type === 'video' || c.call_type === 'group_video') ? 'video' : 'audio'}'` : ''}" class="icon-circle-btn" style="width: 34px; height: 34px;" title="কল ব্যাক">
                                ${iconSvg}
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            // Remove previous load more button if present
            document.getElementById('btnLoadMoreCallHistory')?.remove();

            if (append) {
                listEl.insertAdjacentHTML('beforeend', html);
            } else {
                listEl.innerHTML = html;
            }

            if (res.meta && res.meta.current_page < res.meta.last_page) {
                const loadMoreBtn = document.createElement('button');
                loadMoreBtn.id = 'btnLoadMoreCallHistory';
                loadMoreBtn.type = 'button';
                loadMoreBtn.style.cssText = 'padding: 8px; margin-top: 8px; border: none; background: var(--fb-hover); color: var(--fb-primary); font-weight: 700; border-radius: 8px; cursor: pointer; font-size: 13px; width: 100%;';
                loadMoreBtn.innerText = 'আরও কল দেখুন...';
                loadMoreBtn.onclick = () => {
                    callHistoryPage++;
                    loadCallHistory(callHistoryPage, true);
                };
                listEl.appendChild(loadMoreBtn);
            }
        })
        .catch(() => {
            callHistoryLoading = false;
            listEl.innerHTML = '<div style="text-align: center; color: #ef4444; font-size: 13px; padding: 16px;">কল হিস্ট্রি পাওয়া যায়নি।</div>';
        });
    }

    /**
     * Start WebRTC Audio or Video call
     */
    function startCall(callType = 'audio', targetName = '', convId = null) {
        const id = convId || currentConvId;
        if (!id) {
            alert('অনুগ্রহ করে প্রথমে একটি চ্যাট নির্বাচন করুন।');
            return;
        }
        const type = (callType === 'video' || callType === 'group_video') ? 'video' : 'audio';
        window.location.href = `/call/${id}?type=${type}`;
    }

    // ==============================================
    // MULTI-FRIEND CALLING LOGIC (MESSENGER)
    // ==============================================
    let multiCallSelectedFriendIds = new Set();
    let multiCallTargetConvId = null;
    let cachedMultiCallFriends = [];

    async function openMultiFriendCallModal(convId = null, chatTitle = '', initialFriendId = null) {
        multiCallTargetConvId = convId || currentConvId;
        multiCallSelectedFriendIds.clear();

        if (initialFriendId) {
            multiCallSelectedFriendIds.add(Number(initialFriendId));
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
            const authToken = (typeof currentToken !== 'undefined' && currentToken) ? currentToken : (localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '');
            const res = await fetch(`/api/v1/presence/friends/active${qParam}`, {
                headers: {
                    ...(authToken ? { 'Authorization': `Bearer ${authToken}` } : {}),
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
        if (typeof showToast === 'function') {
            showToast(`${type === 'video' ? 'গ্রুপ ভিডিও' : 'গ্রুপ অডিও'} কল প্রস্তুত হচ্ছে... 📞`);
        }
        closeMultiFriendCallModal();

        try {
            const authToken = (typeof currentToken !== 'undefined' && currentToken) ? currentToken : (localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '');
            const res = await fetch('/api/v1/calls', {
                method: 'POST',
                headers: {
                    ...(authToken ? { 'Authorization': `Bearer ${authToken}` } : {}),
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

    // ==============================================
    // INCOMING CALL HANDLING (MESSENGER IN-PAGE)
    // ==============================================
    let currentIncomingCallPayload = null;
    let messengerRingtoneCtx = null;
    let messengerRingtoneTimer = null;

    function startIncomingRingtone(callId) {
        stopIncomingRingtone(callId);
        if (window.bondhooSoundManager && typeof window.bondhooSoundManager.playIncomingCallRingtone === 'function') {
            window.bondhooSoundManager.playIncomingCallRingtone(callId || currentIncomingCallPayload?.call_id);
        } else {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                messengerRingtoneCtx = new AudioCtx();
                const burst = () => {
                    if (!messengerRingtoneCtx) return;
                    [0, 0.45].forEach(offset => {
                        const osc = messengerRingtoneCtx.createOscillator();
                        const gain = messengerRingtoneCtx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(offset ? 659 : 523, messengerRingtoneCtx.currentTime + offset);
                        gain.gain.setValueAtTime(0.0001, messengerRingtoneCtx.currentTime + offset);
                        gain.gain.exponentialRampToValueAtTime(0.12, messengerRingtoneCtx.currentTime + offset + 0.03);
                        gain.gain.exponentialRampToValueAtTime(0.0001, messengerRingtoneCtx.currentTime + offset + 0.4);
                        osc.connect(gain).connect(messengerRingtoneCtx.destination);
                        osc.start(messengerRingtoneCtx.currentTime + offset);
                        osc.stop(messengerRingtoneCtx.currentTime + offset + 0.42);
                    });
                    messengerRingtoneTimer = setTimeout(burst, 2000);
                };
                burst();
            } catch (e) {}
        }
        if (navigator.vibrate) navigator.vibrate([400, 200, 400]);
    }

    function stopIncomingRingtone(callId) {
        if (window.bondhooSoundManager && typeof window.bondhooSoundManager.stopIncomingCallRingtone === 'function') {
            window.bondhooSoundManager.stopIncomingCallRingtone(callId || currentIncomingCallPayload?.call_id);
        }
        clearTimeout(messengerRingtoneTimer);
        if (messengerRingtoneCtx) {
            try { messengerRingtoneCtx.close(); } catch (e) {}
            messengerRingtoneCtx = null;
        }
    }

    function showIncomingCallModal(payload) {
        if (!payload || !payload.call_id) return;
        // Avoid duplicate popups for same call
        if (currentIncomingCallPayload && Number(currentIncomingCallPayload.call_id) === Number(payload.call_id)) {
            return;
        }

        currentIncomingCallPayload = payload;
        const modal = document.getElementById('incomingCallModal');
        if (!modal) return;
        const caller = payload.caller || {};
        const avatarEl = document.getElementById('incomingCallerAvatar');
        const nameEl = document.getElementById('incomingCallerName');
        const badgeEl = document.getElementById('incomingCallTypeBadge');

        if (avatarEl) {
            avatarEl.innerHTML = caller.avatar_url
                ? `<img src="${caller.avatar_url}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`
                : escapeHtml((caller.name || 'ইউ').charAt(0));
        }
        if (nameEl) nameEl.textContent = caller.name || 'ইনকামিং কল...';
        if (badgeEl) badgeEl.textContent = (payload.call_type === 'video' || payload.call_type === 'group_video') ? 'ভিডিও কল আসছে...' : 'অডিও কল আসছে...';
        modal.style.display = 'block';
        startIncomingRingtone(payload.call_id);
    }

    function hideIncomingCallModal() {
        stopIncomingRingtone(currentIncomingCallPayload?.call_id);
        currentIncomingCallPayload = null;
        const modal = document.getElementById('incomingCallModal');
        if (modal) modal.style.display = 'none';
    }

    async function acceptIncomingCall() {
        if (!currentIncomingCallPayload) return;
        const call = currentIncomingCallPayload;
        const type = (call.call_type === 'video' || call.call_type === 'group_video') ? 'video' : 'audio';
        hideIncomingCallModal();
        window.location.href = `/call/${call.conversation_id}?type=${type}&answer=1&call_id=${call.call_id}`;
    }

    async function rejectIncomingCall() {
        if (!currentIncomingCallPayload) return;
        const call = currentIncomingCallPayload;
        hideIncomingCallModal();
        try {
            await fetch(`/api/v1/calls/${call.call_id}/respond`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                },
                body: JSON.stringify({ action: 'reject' })
            });
        } catch (e) {}
    }


    // ==============================================
    // PINNING & SAVING MESSAGES ACTIONS
    // ==============================================
    function pinMessage(messageId) {
        if (!currentConvId) return;
        fetch(`/api/v1/conversations/${currentConvId}/messages/${messageId}/pin`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('বার্তাটি পিন করা হয়েছে।');
            }
        });
    }

    function unpinMessage(messageId) {
        if (!currentConvId) return;
        fetch(`/api/v1/conversations/${currentConvId}/messages/${messageId}/pin`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('বার্তাটি আনপিন করা হয়েছে।');
            }
        });
    }

    function saveMessage(messageId) {
        fetch(`/api/v1/messages/${messageId}/save`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('বার্তাটি সেভড মেসেজে সংরক্ষণ করা হয়েছে।');
            }
        });
    }

    // ==============================================
    // SYNC EVENT REPLAY ENGINE & PRESENCE POLLING
    // ==============================================
    let lastSyncEventId = {{ rescue(fn () => (int) \App\Models\MessengerSyncEvent::max('id'), 0, false) }};
    let messengerSyncInFlight = false;
    const activePeerUserId = {{ (isset($isGroup) && !$isGroup && empty($isSaved) && !empty($activeOther)) ? (int) $activeOther->id : 'null' }};

    // Presence Heartbeat: Keep current user online every 25 seconds
    setInterval(() => {
        fetch('/api/v1/presence/heartbeat', {
            method: 'POST',
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        }).catch(() => {});
    }, 25000);

    // In-Chat Peer Presence & Typing Check
    function pollPresenceAndTyping() {
        if (!currentConvId) return;

        // 1. Live Typing Indicator Check
        fetch(`/api/v1/presence/typing/${currentConvId}`, {
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            const typingUsers = res.data?.typing_users || [];
            const typingIndicator = document.getElementById('typingIndicator');
            const typingUserName = document.getElementById('typingUserName');
            if (typingIndicator && typingUserName) {
                if (typingUsers.length > 0) {
                    typingUserName.textContent = typingUsers[0].name;
                    typingIndicator.style.display = 'inline-flex';
                } else {
                    typingIndicator.style.display = 'none';
                }
            }
        })
        .catch(() => {});

        // 2. Peer User Online Status Check
        if (activePeerUserId) {
            fetch(`/api/v1/presence/${activePeerUserId}`, {
                headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
            })
            .then(r => r.json())
            .then(res => {
                const presenceTextEl = document.getElementById('headerPresenceText');
                if (!presenceTextEl || !res.data) return;
                if (res.data.online) {
                    presenceTextEl.style.color = 'var(--fb-green)';
                    presenceTextEl.innerHTML = '🟢 সক্রিয় আছেন (অনলাইন)';
                } else if (res.data.last_seen) {
                    presenceTextEl.style.color = 'var(--fb-text-secondary)';
                    presenceTextEl.innerHTML = `সর্বশেষ দেখা গেছে: ${formatPresenceTime(res.data.last_seen)}`;
                } else {
                    presenceTextEl.style.color = 'var(--fb-text-secondary)';
                    presenceTextEl.innerHTML = 'অফলাইন';
                }
            })
            .catch(() => {});
        }
    }

    if (currentConvId) {
        setInterval(pollPresenceAndTyping, 4000);
        pollPresenceAndTyping();
    }

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

    // Polling Sync Events from MessengerSyncEvent Table
    setInterval(async () => {
        if (messengerSyncInFlight) return;
        messengerSyncInFlight = true;
        try {
            const res = await fetch(`/api/v1/messenger/sync?since_id=${lastSyncEventId}&limit=200`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    ...(token ? { 'Authorization': `Bearer ${token}` } : {})
                }
            });
            const data = await res.json();
            if (data.success && data.data?.events) {
                lastSyncEventId = data.data.latest_event_id || lastSyncEventId;
                data.data.events.forEach(evt => {
                    handleSyncEvent(evt);
                });
            }
        } catch (e) {
        } finally {
            messengerSyncInFlight = false;
        }
    }, 2000);

    function handleSyncEvent(evt) {
        if (!evt || !evt.event_type) return;

        // 1. New Message Created
        if (evt.event_type === 'message.created') {
            const m = evt.payload || {};
            if (m.id && Number(m.sender_id || m.sender?.id) !== Number(currentUserId)) {
                if (window.bondhooSoundManager && typeof window.bondhooSoundManager.playIncomingMessageTone === 'function') {
                    window.bondhooSoundManager.playIncomingMessageTone(m.id, evt.conversation_id || m.conversation_id);
                }
            }
            if (currentConvId && Number(evt.conversation_id) === Number(currentConvId) && m.id && !document.getElementById(`messageRow-${m.id}`)) {
                appendIncomingMessageBubble(m);
            }
            const convId = Number(evt.conversation_id || m.conversation_id);
            if (convId && typeof openDockedChats !== 'undefined' && openDockedChats.includes(convId)) {
                appendDockedMessage(convId, m);
            }
            if (convId && typeof updateConversationPreviewInInbox === 'function') {
                updateConversationPreviewInInbox(convId, m);
            }
        }
        // 2. Message Read Receipts
        else if (evt.event_type === 'conversation.read') {
            if (currentConvId && Number(evt.conversation_id) === Number(currentConvId)) {
                document.querySelectorAll('.message-row.outgoing .delivery-check').forEach(el => {
                    el.textContent = '✓✓';
                    el.classList.add('seen');
                });
            }
            const convId = Number(evt.conversation_id || evt.payload?.conversation_id);
            if (convId) {
                const item = document.getElementById(`convItem-${convId}`);
                if (item) {
                    item.classList.remove('conv-item-unread');
                    const badge = item.querySelector('.badge-unread-pill');
                    if (badge) badge.remove();
                }
            }
            if (convId && typeof openDockedChats !== 'undefined' && openDockedChats.includes(convId)) {
                const bodyEl = document.getElementById(`dockedMessages-${convId}`);
                if (bodyEl) {
                    bodyEl.querySelectorAll('.docked-msg-row.outgoing .docked-check').forEach(c => {
                        c.textContent = '✓✓';
                        c.style.color = 'var(--ms-primary)';
                    });
                }
            }
        }
        // 3. Message Delivered Receipts
        else if (evt.event_type === 'message.delivered') {
            if (currentConvId && Number(evt.conversation_id) === Number(currentConvId)) {
                const messageIds = evt.payload?.message_ids || [];
                messageIds.forEach(id => {
                    const row = document.getElementById(`messageRow-${id}`);
                    const check = row?.querySelector('.delivery-check');
                    if (check && !check.classList.contains('seen')) {
                        check.textContent = '✓✓';
                    }
                });
            }
        }
        // 4. Message Reaction Updated
        else if (evt.event_type === 'message.reaction') {
            const messageId = evt.payload?.message_id;
            const counts = evt.payload?.counts;
            if (messageId && counts) {
                updateReactionsBadgeUI(messageId, counts);
            }
        }
        // 5. Message Content Edited
        else if (evt.event_type === 'message.updated') {
            const messageId = evt.payload?.id || evt.payload?.message_id;
            const body = evt.payload?.body;
            if (messageId && body) {
                const row = document.getElementById(`messageRow-${messageId}`);
                const bubble = row?.querySelector('.message-bubble');
                if (bubble) {
                    const textDiv = bubble.querySelector('div:first-child');
                    if (textDiv) textDiv.textContent = body;
                    const metaRow = row.querySelector('.message-meta-row');
                    if (metaRow && !metaRow.querySelector('.edited-badge')) {
                        const badge = document.createElement('span');
                        badge.className = 'edited-badge';
                        badge.textContent = '(সম্পাদিত)';
                        metaRow.appendChild(badge);
                    }
                }
            }
        }
        // 6. Message Deleted
        else if (evt.event_type === 'message.deleted') {
            const messageId = evt.payload?.id || evt.payload?.message_id;
            if (messageId) {
                const row = document.getElementById(`messageRow-${messageId}`);
                const bubble = row?.querySelector('.message-bubble');
                if (bubble) {
                    bubble.innerHTML = '<em style="opacity: 0.7;">🚫 এই বার্তাটি মুছে ফেলা হয়েছে।</em>';
                }
                const toolbar = row?.querySelector('.message-hover-toolbar');
                if (toolbar) toolbar.remove();
            }
        }
        else if (evt.event_type === 'message.deleted_for_me') {
            const messageId = evt.payload?.id || evt.payload?.message_id;
            if (messageId) {
                const row = document.getElementById(`messageRow-${messageId}`);
                if (row) row.remove();
            }
        }
        else if (evt.event_type === 'conversation.cleared') {
            if (currentConvId && Number(evt.conversation_id) === Number(currentConvId)) {
                const stream = document.getElementById('messagesStream');
                if (stream) {
                    stream.innerHTML = '<div class="empty-chat-state" style="text-align: center; color: var(--fb-text-secondary); padding: 40px;"><p>চ্যাট হিস্ট্রি মুছে ফেলা হয়েছে। নতুন মেসেজ দিয়ে কথোপকথন শুরু করুন!</p></div>';
                }
            }
        }
        else if (evt.event_type === 'presence.online' || evt.event_type === 'presence.offline') {
            const targetUserId = Number(evt.payload?.user_id || evt.user_id);
            const isOnline = evt.event_type === 'presence.online';
            if (activePeerUserId && Number(activePeerUserId) === targetUserId) {
                const presenceTextEl = document.getElementById('headerPresenceText');
                if (presenceTextEl) {
                    if (isOnline) {
                        presenceTextEl.style.color = 'var(--fb-green)';
                        presenceTextEl.innerHTML = '🟢 সক্রিয় আছেন (অনলাইন)';
                    } else {
                        presenceTextEl.style.color = 'var(--fb-text-secondary)';
                        presenceTextEl.innerHTML = 'অফলাইন';
                    }
                }
            }
            const convItem = document.querySelector(`.conversation-item[data-user-id="${targetUserId}"]`);
            if (convItem) {
                const ind = convItem.querySelector('.online-indicator');
                if (ind) ind.style.display = isOnline ? 'block' : 'none';
            }
            if (typeof updateActiveFriendPresence === 'function') {
                updateActiveFriendPresence(targetUserId, isOnline);
            }
            if (typeof updateDockedPresence === 'function') {
                updateDockedPresence(targetUserId, isOnline);
            }
        }
        // 7. Calling Events (Real-time in Messenger)
        else if (evt.event_type === 'call.incoming') {
            const p = evt.payload || {};
            if (Number(p.caller?.id) !== Number(currentUserId)) {
                // Ignore stale invitations (older than 3 minutes, accounting for clock skew)
                if (!evt.timestamp || Math.abs(Date.now() - new Date(evt.timestamp).getTime()) <= 180000) {
                    showIncomingCallModal(p);
                }
            }
        }
        else if (evt.event_type === 'call.accepted') {
            const p = evt.payload || {};
            if (currentIncomingCallPayload && Number(p.call_id) === Number(currentIncomingCallPayload.call_id)) {
                hideIncomingCallModal();
            }
        }
        else if (evt.event_type === 'call.rejected' || evt.event_type === 'call.ended') {
            const p = evt.payload || {};
            if (currentIncomingCallPayload && (!p.call_id || Number(p.call_id) === Number(currentIncomingCallPayload.call_id))) {
                hideIncomingCallModal();
            }
            // Auto-refresh Call History if modal is open
            const histModal = document.getElementById('callHistoryModal');
            if (histModal && (histModal.classList.contains('active') || histModal.style.display === 'flex' || histModal.style.display === 'block')) {
                loadCallHistory();
            }
        }
        else if (evt.event_type === 'call.history_updated') {
            const histModal = document.getElementById('callHistoryModal');
            if (histModal && (histModal.classList.contains('active') || histModal.style.display === 'flex' || histModal.style.display === 'block')) {
                loadCallHistory();
            }
        }
    }

    // ==============================================
    // ACTIVE FRIENDS & PRESENCE PRIVACY SYSTEM
    // ==============================================
    let __activeFriendsList = [];
    let __activeFriendsInFlight = false;

    function fetchActiveFriends(forceRefresh = false, searchQuery = '') {
        if (__activeFriendsInFlight && !forceRefresh) return;
        __activeFriendsInFlight = true;

        const q = searchQuery !== '' ? `?q=${encodeURIComponent(searchQuery)}` : '';
        fetch(`/api/v1/presence/friends/active${q}`, {
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && Array.isArray(res.data)) {
                __activeFriendsList = res.data;
                renderActiveFriendsRail(__activeFriendsList);
            }
        })
        .catch(() => {})
        .finally(() => {
            __activeFriendsInFlight = false;
        });
    }

    function renderActiveFriendsRail(friends) {
        const railEl = document.getElementById('activeFriendsRail');
        const countEl = document.getElementById('activeFriendsCount');
        if (!railEl) return;

        const onlineCount = friends.filter(f => f.online).length;
        if (countEl) {
            countEl.textContent = onlineCount.toString();
        }

        if (friends.length === 0) {
            railEl.innerHTML = '<div style="font-size: 11.5px; color: var(--ms-text-secondary); padding: 8px 6px;">বর্তমানে কোনো বন্ধু সক্রিয় নেই</div>';
            return;
        }

        railEl.innerHTML = friends.map(f => {
            const avatarUrl = f.avatar_url || '';
            const initial = (f.name || 'U').charAt(0).toUpperCase();
            const statusText = f.online ? 'সক্রিয়' : (f.last_seen_display || 'অফলাইন');
            const safeName = escapeHtml(f.name);
            const safeUsername = escapeHtml(f.username || '');
            const hasConv = f.conversation_id ? Number(f.conversation_id) : 'null';

            return `
                <div class="active-friend-pill" data-friend-id="${f.id}" onclick="openDirectChat(${f.id}, ${hasConv})" title="${safeName} (@${safeUsername}) - ${statusText}">
                    <div class="active-friend-avatar-wrap">
                        <div class="avatar">
                            ${avatarUrl ? `<img src="${avatarUrl}" alt="${safeName}" style="width: 100%; height: 100%; object-fit: cover;">` : initial}
                        </div>
                        <span class="online-indicator ${f.online ? 'is-online' : 'is-offline'}"></span>
                    </div>
                    <span class="active-friend-name">${safeName}</span>
                    <span class="active-friend-status">${statusText}</span>
                    <div class="active-friend-actions" onclick="event.stopPropagation()">
                        <button type="button" class="af-action-btn" onclick="callFriend(${f.id}, ${hasConv}, 'audio', '${escapeJs(f.name)}')" title="অডিও কল">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </button>
                        <button type="button" class="af-action-btn" onclick="callFriend(${f.id}, ${hasConv}, 'video', '${escapeJs(f.name)}')" title="ভিডিও কল">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                        </button>
                        <button type="button" class="af-action-btn" onclick="openDockedChat(${hasConv}, '${escapeJs(f.name)}', '${escapeJs(avatarUrl)}', ${f.online ? 'true' : 'false'})" title="ডক উইন্ডোতে চ্যাট করুন">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    function updateActiveFriendPresence(userId, isOnline) {
        const friendPill = document.querySelector(`.active-friend-pill[data-friend-id="${userId}"]`);
        if (friendPill) {
            const ind = friendPill.querySelector('.online-indicator');
            const statusEl = friendPill.querySelector('.active-friend-status');
            if (ind) {
                ind.className = `online-indicator ${isOnline ? 'is-online' : 'is-offline'}`;
            }
            if (statusEl) {
                statusEl.textContent = isOnline ? 'সক্রিয়' : 'অফলাইন';
            }
        }
        const item = __activeFriendsList.find(f => Number(f.id) === Number(userId));
        if (item) {
            item.online = isOnline;
            const countEl = document.getElementById('activeFriendsCount');
            if (countEl) {
                countEl.textContent = __activeFriendsList.filter(f => f.online).length.toString();
            }
        }
    }

    function openDirectChat(friendId, existingConvId) {
        if (existingConvId && Number(existingConvId) > 0) {
            if (window.innerWidth <= 768) {
                window.location.href = `/messages?conversation_id=${existingConvId}`;
            } else {
                const friend = __activeFriendsList.find(f => Number(f.id) === Number(friendId));
                openDockedChat(existingConvId, friend?.name || 'বন্ধু', friend?.avatar_url || '', friend?.online || false);
            }
            return;
        }

        // Create direct conversation on demand
        fetch('/api/v1/conversations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) },
            body: JSON.stringify({ recipient_id: friendId, type: 'direct' })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                const convId = res.data.id;
                if (window.innerWidth <= 768) {
                    window.location.href = `/messages?conversation_id=${convId}`;
                } else {
                    const friend = __activeFriendsList.find(f => Number(f.id) === Number(friendId));
                    openDockedChat(convId, friend?.name || res.data.title || 'বন্ধু', friend?.avatar_url || '', friend?.online || false);
                }
            } else {
                alert(res.message || 'চ্যাট শুরু করা যায়নি।');
            }
        })
        .catch(() => alert('সার্ভারে ত্রুটি হয়েছে।'));
    }

    function callFriend(friendId, existingConvId, callType = 'audio', friendName = '') {
        if (existingConvId && Number(existingConvId) > 0) {
            startCall(callType, friendName, existingConvId);
            return;
        }

        fetch('/api/v1/conversations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) },
            body: JSON.stringify({ recipient_id: friendId, type: 'direct' })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                startCall(callType, friendName, res.data.id);
            } else {
                alert(res.message || 'কল সংযোগ স্থাপন করা যায়নি।');
            }
        })
        .catch(() => alert('সার্ভারে ত্রুটি হয়েছে।'));
    }

    function togglePresenceMenu() {
        const menu = document.getElementById('presenceDropdownMenu');
        if (menu) {
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }
    }

    document.addEventListener('click', (e) => {
        const menu = document.getElementById('presenceDropdownMenu');
        const btn = document.getElementById('presenceStatusBtn');
        if (menu && btn && !btn.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = 'none';
        }
    });

    function setMyPresence(appearOffline) {
        togglePresenceMenu();
        fetch('/api/v1/presence/visibility', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) },
            body: JSON.stringify({ appear_offline: appearOffline })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const isOffline = res.data?.appear_offline;
                const dot = document.getElementById('myPresenceDot');
                const optOnline = document.getElementById('presenceOptionOnline');
                const optOffline = document.getElementById('presenceOptionOffline');

                if (dot) {
                    dot.className = `presence-status-dot ${isOffline ? 'offline' : 'online'}`;
                }
                if (optOnline) optOnline.classList.toggle('active', !isOffline);
                if (optOffline) optOffline.classList.toggle('active', isOffline);

                showToast(isOffline ? 'আপনি এখন অন্যদের কাছে অফলাইন দেখাচ্ছেন।' : 'আপনি এখন অনলাইনে সক্রিয় আছেন।');
            }
        })
        .catch(() => showToast('উপস্থিতি পরিবর্তন ব্যর্থ হয়েছে।'));
    }

    // ==============================================
    // DESKTOP MULTI-CHAT WORKSPACE & DOCKED CHATS
    // ==============================================
    const MAX_DOCKED_CHATS = 3;
    let openDockedChats = []; // Array of conversation IDs

    function openDockedChat(convId, title, avatarUrl, isOnline = false) {
        if (!convId || Number(convId) <= 0) return;
        convId = Number(convId);

        // If on mobile, register to mobile switcher and navigate
        if (window.innerWidth <= 768) {
            registerMobileOpenChat(convId, title, avatarUrl);
            window.location.href = `/messages?conversation_id=${convId}`;
            return;
        }

        const tray = document.getElementById('messengerDockedTray');
        if (!tray) return;

        // If window already open, restore and focus
        let existingWin = document.getElementById(`dockedChat-${convId}`);
        if (existingWin) {
            existingWin.classList.remove('minimized');
            const btnMin = existingWin.querySelector('.btn-dock-min svg');
            if (btnMin) btnMin.innerHTML = '<line x1="5" y1="12" x2="19" y2="12"></line>';
            const badge = existingWin.querySelector('.docked-unread-badge');
            if (badge) badge.style.display = 'none';
            const input = existingWin.querySelector('.docked-input');
            if (input) input.focus();
            return;
        }

        // Limit to MAX_DOCKED_CHATS by closing oldest
        if (openDockedChats.length >= MAX_DOCKED_CHATS) {
            const oldestId = openDockedChats.shift();
            closeDockedChat(oldestId, false);
        }

        openDockedChats.push(convId);

        const safeTitle = escapeHtml(title);
        const safeAvatar = escapeHtml(avatarUrl || '');
        const initial = (title || 'U').charAt(0).toUpperCase();

        const winHtml = `
            <div class="docked-chat-window" id="dockedChat-${convId}" data-conv-id="${convId}">
                <div class="docked-header" onclick="toggleDockedChat(${convId})">
                    <div class="docked-header-user">
                        <div class="docked-avatar-wrap">
                            <div class="avatar">
                                ${safeAvatar ? `<img src="${safeAvatar}" alt="${safeTitle}" style="width: 100%; height: 100%; object-fit: cover;">` : initial}
                            </div>
                            <span class="online-indicator" id="dockedPresence-${convId}" style="display: ${isOnline ? 'block' : 'none'};"></span>
                        </div>
                        <div class="docked-header-info">
                            <div class="docked-header-name">${safeTitle}</div>
                            <div class="docked-header-status" id="dockedStatus-${convId}">${isOnline ? 'সক্রিয় আছেন' : 'মেসেঞ্জার'}</div>
                        </div>
                    </div>
                    <div class="docked-header-actions" onclick="event.stopPropagation()">
                        <span class="docked-unread-badge" id="dockedBadge-${convId}">০</span>
                        <button type="button" class="docked-action-btn" onclick="openMultiFriendCallModal(${convId}, '${escapeJs(title)}')" title="মাল্টি-ফ্রেন্ড কল">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </button>
                        <button type="button" class="docked-action-btn" onclick="startCall('audio', '${escapeJs(title)}', ${convId})" title="অডিও কল">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </button>
                        <button type="button" class="docked-action-btn" onclick="startCall('video', '${escapeJs(title)}', ${convId})" title="ভিডিও কল">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                        </button>
                        <button type="button" class="docked-action-btn btn-dock-min" onclick="toggleDockedChat(${convId})" title="মিনিমাইজ/রিস্টোর">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>
                        <button type="button" class="docked-action-btn" onclick="window.location.href='/messages?conversation_id=${convId}'" title="ফুলস্ক্রিন চ্যাটে যান">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                        </button>
                        <button type="button" class="docked-action-btn" onclick="closeDockedChat(${convId})" title="বন্ধ করুন">✕</button>
                    </div>
                </div>
                <div class="docked-body" id="dockedMessages-${convId}">
                    <div style="font-size: 11.5px; color: var(--ms-text-secondary); text-align: center; padding: 20px;">বার্তা লোড হচ্ছে...</div>
                </div>
                <div class="docked-composer" onclick="event.stopPropagation()">
                    <input type="text" class="docked-input" id="dockedInput-${convId}" placeholder="একটি বার্তা লিখুন..." oninput="handleDockedDraft(${convId}, this.value)" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendDockedMessage(${convId});}">
                    <button type="button" class="docked-send-btn" onclick="sendDockedMessage(${convId})" title="পাঠান">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </div>
            </div>
        `;

        tray.insertAdjacentHTML('beforeend', winHtml);

        // Restore draft if exists
        const savedDraft = localStorage.getItem(`ms_draft_${convId}`);
        if (savedDraft) {
            const inp = document.getElementById(`dockedInput-${convId}`);
            if (inp) inp.value = savedDraft;
        }

        // Fetch recent messages
        fetchDockedMessages(convId);
    }

    function toggleDockedChat(convId) {
        const win = document.getElementById(`dockedChat-${convId}`);
        if (!win) return;
        const isMin = win.classList.toggle('minimized');
        const minBtn = win.querySelector('.btn-dock-min svg');
        if (minBtn) {
            minBtn.innerHTML = isMin ? '<rect x="3" y="3" width="18" height="18" rx="2"/>' : '<line x1="5" y1="12" x2="19" y2="12"/>';
        }
        if (!isMin) {
            const badge = document.getElementById(`dockedBadge-${convId}`);
            if (badge) badge.style.display = 'none';
            const body = document.getElementById(`dockedMessages-${convId}`);
            if (body) body.scrollTop = body.scrollHeight;
        }
    }

    function closeDockedChat(convId, updateArray = true) {
        const win = document.getElementById(`dockedChat-${convId}`);
        if (win) win.remove();
        if (updateArray) {
            openDockedChats = openDockedChats.filter(id => id !== convId);
        }
    }

    function handleDockedDraft(convId, text) {
        if (text) {
            localStorage.setItem(`ms_draft_${convId}`, text);
        } else {
            localStorage.removeItem(`ms_draft_${convId}`);
        }
    }

    function fetchDockedMessages(convId) {
        fetch(`/api/v1/conversations/${convId}/messages?per_page=25`, {
            headers: { 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) }
        })
        .then(r => r.json())
        .then(res => {
            const body = document.getElementById(`dockedMessages-${convId}`);
            if (!body) return;
            const msgs = (res.data || []).reverse();
            if (msgs.length === 0) {
                body.innerHTML = '<div style="font-size: 11.5px; color: var(--ms-text-secondary); text-align: center; padding: 20px;">কোনো বার্তা পাওয়া যায়নি। কথোপকথন শুরু করুন!</div>';
                return;
            }
            body.innerHTML = msgs.map(m => renderDockedMessageItem(m)).join('');
            body.scrollTop = body.scrollHeight;
        })
        .catch(() => {
            const body = document.getElementById(`dockedMessages-${convId}`);
            if (body) body.innerHTML = '<div style="font-size: 11.5px; color: #ef4444; text-align: center; padding: 20px;">বার্তা লোড ব্যর্থ হয়েছে।</div>';
        });
    }

    function renderDockedMessageItem(m) {
        const isMine = m.is_mine || (m.sender?.id && Number(m.sender.id) === Number(currentUserId));
        const safeBody = escapeHtml(m.body || '');
        const timeStr = m.created_at ? new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
        const isSeen = m.delivery_status === 'seen';

        return `
            <div class="docked-msg-row ${isMine ? 'outgoing' : 'incoming'}" id="dockedMsg-${m.id}">
                <div class="docked-msg-bubble">${safeBody}</div>
                <div class="docked-msg-time">
                    <span>${timeStr}</span>
                    ${isMine ? `<span class="docked-check" style="color: ${isSeen ? 'var(--ms-primary)' : 'inherit'}; font-weight: 700;">${isSeen ? '✓✓' : '✓'}</span>` : ''}
                </div>
            </div>
        `;
    }

    function appendDockedMessage(convId, m) {
        const body = document.getElementById(`dockedMessages-${convId}`);
        if (!body) return;
        body.insertAdjacentHTML('beforeend', renderDockedMessageItem(m));
        body.scrollTop = body.scrollHeight;

        const win = document.getElementById(`dockedChat-${convId}`);
        if (win && win.classList.contains('minimized')) {
            const badge = document.getElementById(`dockedBadge-${convId}`);
            if (badge) {
                const cur = parseInt(badge.textContent) || 0;
                badge.textContent = (cur + 1).toString();
                badge.style.display = 'inline-block';
            }
        }
    }

    function sendDockedMessage(convId) {
        const input = document.getElementById(`dockedInput-${convId}`);
        if (!input) return;
        const bodyText = input.value.trim();
        if (!bodyText) return;

        input.value = '';
        localStorage.removeItem(`ms_draft_${convId}`);

        // Optimistic append
        const tempMsg = {
            id: 'temp-' + Date.now(),
            body: bodyText,
            is_mine: true,
            delivery_status: 'sending',
            created_at: new Date().toISOString()
        };
        appendDockedMessage(convId, tempMsg);

        fetch(`/api/v1/conversations/${convId}/messages`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}) },
            body: JSON.stringify({ body: bodyText })
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                alert(res.message || 'বার্তা পাঠানো সম্ভব হয়নি।');
            } else if (res.data && res.data.id) {
                if (window.bondhooSoundManager && typeof window.bondhooSoundManager.playOutgoingMessageSentTone === 'function') {
                    window.bondhooSoundManager.playOutgoingMessageSentTone(res.data.id);
                }
            }
        })
        .catch(() => alert('বার্তা পাঠাতে ত্রুটি হয়েছে।'));
    }

    function updateDockedPresence(userId, isOnline) {
        openDockedChats.forEach(cId => {
            const win = document.getElementById(`dockedChat-${cId}`);
            if (win) {
                const ind = document.getElementById(`dockedPresence-${cId}`);
                const status = document.getElementById(`dockedStatus-${cId}`);
                if (ind) ind.style.display = isOnline ? 'block' : 'none';
                if (status) status.textContent = isOnline ? 'সক্রিয় আছেন' : 'অফলাইন';
            }
        });
    }

    // ==============================================
    // MOBILE OPEN CHATS SWITCHER
    // ==============================================
    let mobileOpenChats = JSON.parse(localStorage.getItem('ms_mobile_open_chats') || '[]');

    function registerMobileOpenChat(convId, title, avatarUrl) {
        convId = Number(convId);
        mobileOpenChats = mobileOpenChats.filter(c => c.id !== convId);
        mobileOpenChats.unshift({ id: convId, title: title, avatar_url: avatarUrl });
        if (mobileOpenChats.length > 5) mobileOpenChats.pop();
        localStorage.setItem('ms_mobile_open_chats', JSON.stringify(mobileOpenChats));
        renderMobileOpenChatsBar();
    }

    function renderMobileOpenChatsBar() {
        const bar = document.getElementById('mobileOpenChatsBar');
        if (!bar || window.innerWidth > 768) return;
        if (mobileOpenChats.length <= 1) {
            bar.style.display = 'none';
            return;
        }
        bar.style.display = 'flex';
        bar.innerHTML = mobileOpenChats.map(c => {
            const isActive = currentConvId && Number(currentConvId) === Number(c.id);
            const safeTitle = escapeHtml(c.title);
            return `
                <div class="mobile-chat-pill ${isActive ? 'active' : ''}" onclick="window.location.href='/messages?conversation_id=${c.id}'">
                    <span>${safeTitle}</span>
                    <button type="button" class="mobile-chat-close" onclick="event.stopPropagation(); removeMobileOpenChat(${c.id});">✕</button>
                </div>
            `;
        }).join('');
    }

    function removeMobileOpenChat(convId) {
        convId = Number(convId);
        mobileOpenChats = mobileOpenChats.filter(c => c.id !== convId);
        localStorage.setItem('ms_mobile_open_chats', JSON.stringify(mobileOpenChats));
        renderMobileOpenChatsBar();
    }

    function escapeJs(str) {
        return (str || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
    }

    // Initialize Active Friends & Switcher on page load
    document.addEventListener('DOMContentLoaded', () => {
        fetchActiveFriends();
        setInterval(() => fetchActiveFriends(), 25000);

        // Check current user presence setting
        @if($currentUser && $currentUser->privacySettings && !$currentUser->privacySettings->show_online_status)
            const dot = document.getElementById('myPresenceDot');
            const optOnline = document.getElementById('presenceOptionOnline');
            const optOffline = document.getElementById('presenceOptionOffline');
            if (dot) dot.className = 'presence-status-dot offline';
            if (optOnline) optOnline.classList.remove('active');
            if (optOffline) optOffline.classList.add('active');
        @endif

        @if($activeConversation)
            registerMobileOpenChat({{ $activeConversation->id }}, '{{ addslashes($activeTitle) }}', '{{ $activeAvatar ?? '' }}');
        @else
            renderMobileOpenChatsBar();
        @endif
    });
</script>
@endsection

