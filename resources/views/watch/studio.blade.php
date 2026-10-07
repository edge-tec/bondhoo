@extends('layouts.app')

@section('title', 'ক্রিয়েটর লাইভ স্টুডিও — Bondhoo')

@section('styles')
<style>
    /* ==========================================================================
       BONDHOO ENTERPRISE LIVE BROADCAST STUDIO DESIGN SYSTEM
       Theme-aware, accessible, high-performance WebRTC broadcasting
       ========================================================================== */
    :root {
        --studio-bg: #090d16;
        --studio-surface: #0f172a;
        --studio-surface-alt: #1e293b;
        --studio-border: rgba(255, 255, 255, 0.12);
        --studio-text: #f8fafc;
        --studio-text-muted: #94a3b8;
        --studio-primary: #ef4444;
        --studio-primary-hover: #dc2626;
        --studio-accent: #2563eb;
        --studio-success: #10b981;
        --studio-warning: #f59e0b;
    }

    body {
        background-color: var(--studio-bg) !important;
        color: var(--studio-text);
    }

    .studio-root {
        max-width: 1540px;
        margin: 0 auto;
        padding: 16px;
        min-height: calc(100vh - 75px);
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    /* Top Command Header */
    .studio-navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--studio-surface);
        border: 1px solid var(--studio-border);
        border-radius: 14px;
        padding: 12px 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
        flex-wrap: wrap;
        gap: 12px;
    }

    .studio-brand-group {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .studio-brand-logo {
        height: 32px;
        max-width: 140px;
        object-fit: contain;
    }

    .studio-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.5px;
        padding: 4px 10px;
        border-radius: 20px;
        text-transform: uppercase;
    }

    .studio-badge-pill.ready {
        background: rgba(37, 99, 235, 0.18);
        border: 1px solid rgba(37, 99, 235, 0.4);
        color: #60a5fa;
    }

    .studio-badge-pill.live {
        background: rgba(239, 68, 68, 0.2);
        border: 1px solid rgba(239, 68, 68, 0.6);
        color: #f87171;
    }

    .studio-pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #ef4444;
        animation: studioPulse 1.4s infinite;
    }

    @keyframes studioPulse {
        0% { transform: scale(0.9); opacity: 1; }
        50% { transform: scale(1.4); opacity: 0.4; }
        100% { transform: scale(0.9); opacity: 1; }
    }

    .studio-header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .studio-nav-btn {
        background: var(--studio-surface-alt);
        color: var(--studio-text);
        border: 1px solid var(--studio-border);
        border-radius: 8px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .studio-nav-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    /* Main Grid Layout */
    .studio-grid {
        display: grid;
        grid-template-columns: 1fr 390px;
        gap: 18px;
        align-items: start;
    }

    @media (max-width: 1080px) {
        .studio-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Left Stage Viewport */
    .studio-stage-box {
        background: #000;
        border: 1px solid var(--studio-border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        display: flex;
        flex-direction: column;
    }

    .stage-monitor {
        position: relative;
        width: 100%;
        height: 520px;
        background: #020617;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    @media (max-width: 768px) {
        .stage-monitor {
            height: 320px;
        }
    }

    #mainVideoPreview, #screenVideoPreview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        background: #000;
    }

    #screenVideoPreview {
        display: none;
    }

    /* Draggable / Floating PiP Camera when screen sharing */
    .pip-cam-card {
        position: absolute;
        bottom: 20px;
        right: 20px;
        width: 180px;
        height: 120px;
        background: #111;
        border: 2px solid var(--studio-primary);
        border-radius: 12px;
        overflow: hidden;
        display: none;
        z-index: 30;
        box-shadow: 0 8px 24px rgba(0,0,0,0.7);
        cursor: move;
    }

    .pip-cam-card video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Top Overlays on Stage */
    .stage-overlay-header {
        position: absolute;
        top: 14px;
        left: 14px;
        right: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        pointer-events: none;
        z-index: 25;
    }

    .stage-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: white;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 700;
        pointer-events: auto;
    }

    .stage-stats-cluster {
        display: flex;
        gap: 8px;
        pointer-events: auto;
    }

    /* Bottom Toolbar */
    .studio-toolbar {
        background: var(--studio-surface);
        border-top: 1px solid var(--studio-border);
        padding: 14px 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .toolbar-left, .toolbar-center, .toolbar-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .tool-icon-btn {
        background: var(--studio-surface-alt);
        color: var(--studio-text);
        border: 1px solid var(--studio-border);
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        user-select: none;
    }

    .tool-icon-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        color: white;
    }

    .tool-icon-btn.active {
        background: var(--studio-accent);
        border-color: #3b82f6;
        color: white;
    }

    .tool-icon-btn.danger-active {
        background: var(--studio-primary);
        border-color: #f87171;
        color: white;
    }

    .tool-icon-btn.muted {
        background: rgba(239, 68, 68, 0.2);
        border-color: rgba(239, 68, 68, 0.5);
        color: #fca5a5;
    }

    .tool-icon-btn svg {
        width: 18px;
        height: 18px;
    }

    /* Hardware Audio Visualizer Bar */
    .audio-vu-meter {
        width: 48px;
        height: 8px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 4px;
        overflow: hidden;
        position: relative;
    }

    .audio-vu-fill {
        width: 0%;
        height: 100%;
        background: linear-gradient(90deg, #10b981 0%, #f59e0b 70%, #ef4444 100%);
        transition: width 0.08s ease;
    }

    /* Device Dropdown */
    .dropdown-relative {
        position: relative;
    }

    .device-menu-box {
        position: absolute;
        bottom: calc(100% + 8px);
        left: 0;
        background: var(--studio-surface);
        border: 1px solid var(--studio-border);
        border-radius: 12px;
        padding: 8px;
        min-width: 240px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.6);
        display: none;
        flex-direction: column;
        gap: 4px;
        z-index: 50;
    }

    .device-menu-box.show {
        display: flex;
    }

    .device-menu-item {
        background: transparent;
        border: none;
        color: var(--studio-text);
        padding: 8px 12px;
        border-radius: 6px;
        text-align: left;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .device-menu-item:hover {
        background: var(--studio-surface-alt);
        color: white;
    }

    .device-menu-item.selected {
        background: rgba(37, 99, 235, 0.2);
        color: #60a5fa;
        font-weight: 700;
    }

    /* Right Studio Control & Chat Panels */
    .studio-sidebar-stack {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .studio-card {
        background: var(--studio-surface);
        border: 1px solid var(--studio-border);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }

    .card-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--studio-border);
    }

    .card-title-text {
        font-size: 16px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 8px;
        color: white;
    }

    .studio-form-label {
        font-size: 13px;
        font-weight: 700;
        color: var(--studio-text);
        display: block;
        margin-bottom: 6px;
    }

    .studio-input, .studio-textarea, .studio-select {
        width: 100%;
        background: #020617;
        border: 1px solid var(--studio-border);
        border-radius: 8px;
        padding: 10px 14px;
        color: white;
        font-size: 14px;
        outline: none;
        transition: border 0.2s;
    }

    .studio-input:focus, .studio-textarea:focus, .studio-select:focus {
        border-color: #3b82f6;
    }

    .studio-switch-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .studio-switch-row:last-child {
        border-bottom: none;
    }

    .switch-desc h4 {
        font-size: 13px;
        font-weight: 700;
        color: white;
        margin: 0;
    }

    .switch-desc p {
        font-size: 11.5px;
        color: var(--studio-text-muted);
        margin: 2px 0 0 0;
    }

    /* Toggle Switch */
    .switch-toggle {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }

    .switch-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .switch-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: var(--studio-surface-alt);
        transition: .3s;
        border-radius: 24px;
        border: 1px solid var(--studio-border);
    }

    .switch-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }

    .switch-toggle input:checked + .switch-slider {
        background-color: #2563eb;
    }

    .switch-toggle input:checked + .switch-slider:before {
        transform: translateX(20px);
    }

    /* Big Start Live Button */
    .btn-golive-master {
        width: 100%;
        background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 14px;
        font-size: 16px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4);
        transition: transform 0.15s, box-shadow 0.15s;
        margin-top: 16px;
    }

    .btn-golive-master:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.6);
    }

    .btn-golive-master:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    /* End Broadcast Button */
    .btn-end-master {
        width: 100%;
        background: #dc2626;
        color: white;
        border: none;
        border-radius: 10px;
        padding: 12px;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background 0.2s;
    }

    .btn-end-master:hover {
        background: #b91c1c;
    }

    /* Live Chat Stream in Studio */
    .studio-chat-container {
        height: 380px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding-right: 4px;
    }

    .studio-comment-card {
        background: var(--studio-surface-alt);
        border: 1px solid var(--studio-border);
        border-radius: 10px;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .comment-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .comment-card-author {
        font-size: 12.5px;
        font-weight: 700;
        color: #93c5fd;
    }

    .comment-card-text {
        font-size: 13.5px;
        color: white;
        word-break: break-word;
    }

    /* Floating Reactions Box */
    .floating-reactions-zone {
        position: absolute;
        bottom: 80px;
        right: 20px;
        width: 80px;
        height: 240px;
        pointer-events: none;
        overflow: hidden;
        z-index: 40;
    }

    .studio-floating-emoji {
        position: absolute;
        bottom: 0;
        font-size: 32px;
        animation: floatUpAnim 2.5s ease-out forwards;
    }

    @keyframes floatUpAnim {
        0% { transform: translateY(0) scale(0.6); opacity: 1; }
        50% { transform: translateY(-100px) scale(1.2) rotate(8deg); opacity: 0.9; }
        100% { transform: translateY(-220px) scale(0.8) rotate(-8deg); opacity: 0; }
    }

    /* Modals */
    .studio-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 16px;
    }

    .studio-modal-box {
        background: var(--studio-surface);
        border: 1px solid var(--studio-border);
        border-radius: 16px;
        max-width: 500px;
        width: 100%;
        padding: 24px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.8);
    }
</style>
@endsection

@section('content')
<div class="studio-root">
    <!-- Top Command Header -->
    <header class="studio-navbar">
        <div class="studio-brand-group">
            <a href="{{ route('dashboard') }}" style="display: flex; align-items: center; text-decoration: none;">
                <img src="/images/bondhoo-logo-white.png" alt="Bondhoo" class="studio-brand-logo" onerror="this.src='/images/bondhoo-logo.png'">
            </a>
            <h1 style="font-size: 15px; font-weight: 800; color: #ffffff; margin: 0; display: flex; align-items: center; gap: 8px;">ক্রিয়েটর লাইভ ব্রডকাস্ট স্টুডিও</h1>
            <span class="studio-badge-pill {{ $stream->status === 'live' ? 'live' : 'ready' }}" id="studioHeaderBadge">
                <span class="studio-pulse-dot" id="headerLiveDot" style="{{ $stream->status === 'live' ? '' : 'display: none;' }}"></span>
                <span id="headerLiveStatusText">{{ $stream->status === 'live' ? 'লাইভ চলছে (BROADCASTING)' : 'স্টুডিও প্রস্তুত (STANDBY)' }}</span>
            </span>
        </div>

        <div class="studio-header-actions">
            <a href="{{ route('watch.index') }}" class="studio-nav-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="15" x="2" y="7" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/></svg>
                <span>ওয়াচ হাব</span>
            </a>
            <a href="{{ route('live.show', $stream->id) }}" target="_blank" class="studio-nav-btn" title="দর্শক ইন্টারফেস দেখুন">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <span>দর্শক ভিউ ↗</span>
            </a>
        </div>
    </header>

    <!-- Main Live Workspace -->
    <main class="studio-grid">
        <!-- Left: Stage Monitor & Stream Hardware Toolbar -->
        <section class="studio-stage-box" aria-label="ব্রডকাস্ট স্টুডিও মনিটর">
            <div class="stage-monitor" id="videoViewport">
                <!-- Video Previews -->
                <video id="mainVideoPreview" autoplay muted playsinline></video>
                <video id="screenVideoPreview" autoplay muted playsinline></video>

                <!-- PiP Camera Video when screen sharing -->
                <div class="pip-cam-card" id="pipCamOverlay" title="ক্যামেরা ভিউ">
                    <video id="pipCameraVideo" autoplay muted playsinline></video>
                </div>

                <!-- Stage Overlays -->
                <div class="stage-overlay-header">
                    <div class="stage-meta-pill">
                        <span class="studio-pulse-dot" id="liveDot" style="{{ $stream->status === 'live' ? '' : 'display:none;' }}"></span>
                        <span id="liveStateLabel">{{ $stream->status === 'live' ? '🔴 LIVE' : 'স্ট্যান্ডবাই' }}</span>
                        <span id="liveTimerLabel" style="{{ $stream->status === 'live' ? '' : 'display:none;' }}; margin-left: 6px; font-family: monospace;">00:00:00</span>
                    </div>

                    <div class="stage-stats-cluster" id="liveMetricsPill" style="{{ $stream->status === 'live' ? '' : 'display:none;' }}">
                        <div class="stage-meta-pill">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <span id="liveViewerCount">{{ $stream->viewers_count }}</span>
                            <span>দর্শক</span>
                        </div>
                        <div class="stage-meta-pill">
                            <span style="color: #f87171;">❤️</span>
                            <span id="liveReactionCount">{{ $stream->total_reactions }}</span>
                        </div>
                        <div class="stage-meta-pill" id="streamHealthPill" title="স্ট্রিম সংযোগ মান">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                            <span style="color: #10b981; font-size: 11px;">চমৎকার</span>
                        </div>
                    </div>
                </div>

                <!-- Floating Reactions Display -->
                <div class="floating-reactions-zone" id="floatingReactionsArea"></div>
            </div>

            <!-- Bottom Hardware Controls Toolbar -->
            <div class="studio-toolbar">
                <div class="toolbar-left">
                    <!-- Microphone Toggle -->
                    <div class="dropdown-relative">
                        <button type="button" class="tool-icon-btn" id="micToggleBtn" onclick="toggleMicrophone()" aria-label="মাইক্রোফোন মিউট/আনমিউট">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="22"/></svg>
                            <span id="micLabel">মাইক চালু</span>
                        </button>
                    </div>

                    <!-- Mic VU Meter Indicator -->
                    <div class="audio-vu-meter" title="মাইক্রোফোন ইনপুট সাউন্ড লেভেল">
                        <div class="audio-vu-fill" id="micVuFill"></div>
                    </div>

                    <!-- Camera Toggle -->
                    <div class="dropdown-relative">
                        <button type="button" class="tool-icon-btn" id="camToggleBtn" onclick="toggleCamera()" aria-label="ক্যামেরা অন/অফ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2" ry="2"/></svg>
                            <span id="camLabel">ক্যামেরা চালু</span>
                        </button>
                    </div>

                    <!-- Screen Share -->
                    <button type="button" class="tool-icon-btn" id="screenShareBtn" onclick="toggleScreenShare()" aria-label="স্ক্রিন শেয়ার">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                        <span id="screenShareLabel">স্ক্রিন শেয়ার</span>
                    </button>
                </div>

                <div class="toolbar-right">
                    <!-- Switch / Flip Camera -->
                    <button type="button" class="tool-icon-btn" id="flipCameraBtn" onclick="flipCameraFacingMode()" title="ক্যামেরা ফ্লিপ করুন (Switch Camera)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                        <span>ফ্লিপ</span>
                    </button>

                    <!-- Fullscreen -->
                    <button type="button" class="tool-icon-btn" onclick="toggleFullscreen()" title="ফুলস্ক্রিন মোড">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                        <span>ফুলস্ক্রিন</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Right: Pre-Live Settings & Live Interaction Control Center -->
        <aside class="studio-sidebar-stack">
            <!-- Setup Form Panel (Before Live) -->
            <div class="studio-card" id="preLiveSettingsPanel" style="{{ $stream->status === 'live' ? 'display: none;' : '' }}">
                <div class="card-header-row">
                    <span class="card-title-text">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        লাইভ কনফিগারেশন ও সেটিংস
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label class="studio-form-label" for="liveTitle">লাইভের শিরোনাম <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="liveTitle" class="studio-input" value="{{ $stream->title }}" placeholder="লাইভের একটি শিরোনাম লিখুন..." required>
                    </div>

                    <div>
                        <label class="studio-form-label" for="liveDescription">বিবরণ (Description)</label>
                        <textarea id="liveDescription" class="studio-textarea" rows="2" placeholder="লাইভের বিষয়বস্তু সম্পর্কে লিখুন...">{{ $stream->description }}</textarea>
                    </div>

                    <div>
                        <label class="studio-form-label" for="livePrivacy">গোপনীয়তা (Audience Privacy)</label>
                        <select id="livePrivacy" class="studio-select">
                            <option value="public" {{ $stream->privacy === 'public' ? 'selected' : '' }}>🌐 পাবলিক (সকলের জন্য উন্মুক্ত)</option>
                            <option value="friends" {{ $stream->privacy === 'friends' ? 'selected' : '' }}>👥 বন্ধুরা (শুধুমাত্র Bondhoo বন্ধুরা)</option>
                            <option value="only_me" {{ $stream->privacy === 'only_me' ? 'selected' : '' }}>🔒 শুধুমাত্র আমি (পরীক্ষামূলক ব্রডকাস্ট)</option>
                        </select>
                    </div>

                    <div style="border-top: 1px solid var(--studio-border); padding-top: 10px;">
                        <div class="studio-switch-row">
                            <div class="switch-desc">
                                <h4>মন্তব্য সক্রিয় রাখুন</h4>
                                <p>দর্শকরা লাইভ চলাকালীন মন্তব্য করতে পারবেন</p>
                            </div>
                            <label class="switch-toggle">
                                <input type="checkbox" id="commentsEnabledToggle" {{ $stream->comments_enabled ? 'checked' : '' }}>
                                <span class="switch-slider"></span>
                            </label>
                        </div>

                        <div class="studio-switch-row">
                            <div class="switch-desc">
                                <h4>রিঅ্যাকশন সক্রিয় রাখুন</h4>
                                <p>ভালোবাসা, লাইক ও ফ্লোটিং রিঅ্যাকশন প্রদর্শন</p>
                            </div>
                            <label class="switch-toggle">
                                <input type="checkbox" id="reactionsEnabledToggle" {{ $stream->reactions_enabled ? 'checked' : '' }}>
                                <span class="switch-slider"></span>
                            </label>
                        </div>

                        <div class="studio-switch-row">
                            <div class="switch-desc">
                                <h4>শেয়ারিং সক্রিয় রাখুন</h4>
                                <p>দর্শকরা টাইমলাইনে শেয়ার করতে পারবেন</p>
                            </div>
                            <label class="switch-toggle">
                                <input type="checkbox" id="sharingEnabledToggle" {{ $stream->sharing_enabled ? 'checked' : '' }}>
                                <span class="switch-slider"></span>
                            </label>
                        </div>

                        <div class="studio-switch-row">
                            <div class="switch-desc">
                                <h4>লাইভ শেষে রিপ্লে সংরক্ষণ</h4>
                                <p>লাইভ শেষ হলে প্রোফাইলে পূর্ণ ভিডিও পোস্ট তৈরি হবে</p>
                            </div>
                            <label class="switch-toggle">
                                <input type="checkbox" id="recordingEnabledToggle" {{ $stream->recording_enabled ? 'checked' : '' }}>
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                    </div>

                    <button type="button" class="btn-golive-master" id="startLiveBtn" onclick="handleStartLive()">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                        <span>গো লাইভ শুরু করুন (GO LIVE)</span>
                    </button>
                </div>
            </div>

            <!-- OBS & Software Streaming Setup Panel -->
            <div class="studio-card">
                <div class="card-header-row">
                    <span class="card-title-text">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="m9 8 6 4-6 4Z"/></svg>
                        OBS ও সফটওয়্যার কনফিগারেশন
                    </span>
                </div>
                <div style="font-size: 12px; color: var(--studio-text-muted); margin-bottom: 12px; line-height: 1.4;">
                    OBS Studio বা Prism Live দিয়ে স্ট্রিম করতে চাইলে এই সার্ভার ইনজেস্ট URL ও স্ট্রিম কি ব্যবহার করুন:
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <label class="studio-form-label" style="font-size: 11px;">সার্ভার URL (RTMP Ingest)</label>
                        <div style="display: flex; gap: 6px;">
                            <input type="text" class="studio-input" value="{{ $stream->ingest_url }}" readonly style="font-family: monospace; font-size: 11px; padding: 6px 10px;">
                            <button type="button" class="tool-icon-btn" style="padding: 6px 10px; font-size: 12px;" onclick="navigator.clipboard.writeText('{{ $stream->ingest_url }}'); showStudioNotice('ইনজেস্ট URL কপি হয়েছে!');">কপি</button>
                        </div>
                    </div>

                    <div>
                        <label class="studio-form-label" style="font-size: 11px;">স্ট্রিম কি (Stream Key)</label>
                        <div style="display: flex; gap: 6px;">
                            <input type="password" id="obsKeyInput" class="studio-input" value="{{ $stream->stream_key }}" readonly style="font-family: monospace; font-size: 11px; padding: 6px 10px;">
                            <button type="button" class="tool-icon-btn" style="padding: 6px 10px;" onclick="const el=document.getElementById('obsKeyInput'); el.type = el.type === 'password' ? 'text' : 'password';" title="দেখুন/লুকান">👁️</button>
                            <button type="button" class="tool-icon-btn" style="padding: 6px 10px; font-size: 12px;" onclick="navigator.clipboard.writeText('{{ $stream->stream_key }}'); showStudioNotice('স্ট্রিম কি কপি হয়েছে!');">কপি</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Chat & Moderation Panel (Active During Live) -->
            <div class="studio-card" id="liveInteractivePanel" style="{{ $stream->status === 'live' ? '' : 'display: none;' }}">
                <div class="card-header-row">
                    <span class="card-title-text">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        লাইভ চ্যাট ও দর্শক মন্তব্য
                    </span>
                    <span style="font-size: 11px; font-weight: 800; color: #10b981;">রিয়েল-টাইম</span>
                </div>

                <div class="studio-chat-container" id="studioChatBox">
                    <div style="text-align: center; color: var(--studio-text-muted); font-size: 13px; margin: 40px 0;" id="noCommentsNotice">
                        এখনও কোনো মন্তব্য আসেনি।
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <button type="button" class="btn-end-master" onclick="confirmEndLive()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/></svg>
                        <span>লাইভ শেষ করুন (End Broadcast)</span>
                    </button>
                </div>
            </div>
        </aside>
    </main>
</div>

<!-- End Live Confirmation Modal -->
<div class="studio-modal-overlay" id="endLiveConfirmModal">
    <div class="studio-modal-box">
        <h2 style="font-size: 20px; font-weight: 800; color: #ef4444; margin-bottom: 10px;">লাইভ সম্প্রচার সমাপ্ত করবেন?</h2>
        <p style="font-size: 14px; color: var(--studio-text-muted); line-height: 1.5; margin-bottom: 20px;">
            আপনি কি নিশ্চিত যে আপনার লাইভ ব্রডকাস্ট সমাপ্ত করতে চান? লাইভ শেষ হলে সকল দর্শকের কাছে স্ট্রিম সমাপ্ত হবে এবং স্বয়ংক্রিয়ভাবে ভিডিও রিপ্লে পোস্ট তৈরি হবে।
        </p>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" class="studio-nav-btn" onclick="closeEndLiveModal()">চালিয়ে যান</button>
            <button type="button" class="tool-icon-btn danger-active" style="padding: 10px 20px;" onclick="executeEndLive()">হ্যাঁ, সমাপ্ত করুন</button>
        </div>
    </div>
</div>

<!-- Live Summary Modal -->
<div class="studio-modal-overlay" id="liveSummaryModal">
    <div class="studio-modal-box" style="text-align: center; max-width: 540px;">
        <div style="font-size: 48px; margin-bottom: 10px;">🎉</div>
        <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 8px;">আপনার লাইভ সম্প্রচার সমাপ্ত হয়েছে!</h2>
        <p style="font-size: 14px; color: var(--studio-text-muted); margin-bottom: 20px;">
            লাইভের বিস্তারিত পরিসংখ্যান ও দর্শক এনগেজমেন্ট:
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
            <div style="background: var(--studio-surface-alt); border: 1px solid var(--studio-border); border-radius: 12px; padding: 14px;">
                <div style="font-size: 12px; color: var(--studio-text-muted);">মোট সময়কাল</div>
                <div style="font-size: 22px; font-weight: 800; color: #60a5fa;" id="summaryDuration">00:00</div>
            </div>
            <div style="background: var(--studio-surface-alt); border: 1px solid var(--studio-border); border-radius: 12px; padding: 14px;">
                <div style="font-size: 12px; color: var(--studio-text-muted);">সর্বোচ্চ দর্শক (Peak)</div>
                <div style="font-size: 22px; font-weight: 800; color: #ef4444;" id="summaryPeak">0</div>
            </div>
            <div style="background: var(--studio-surface-alt); border: 1px solid var(--studio-border); border-radius: 12px; padding: 14px;">
                <div style="font-size: 12px; color: var(--studio-text-muted);">মোট মন্তব্য</div>
                <div style="font-size: 22px; font-weight: 800; color: white;" id="summaryComments">0</div>
            </div>
            <div style="background: var(--studio-surface-alt); border: 1px solid var(--studio-border); border-radius: 12px; padding: 14px;">
                <div style="font-size: 12px; color: var(--studio-text-muted);">মোট রিঅ্যাকশন</div>
                <div style="font-size: 22px; font-weight: 800; color: #f472b6;" id="summaryReactions">0</div>
            </div>
        </div>

        <div id="replayUploadStatusBox" style="margin-bottom: 20px; font-size: 13px; font-weight: 600; color: #10b981; line-height: 1.5; padding: 12px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 10px;">
            রিপ্লে সংরক্ষণ ও ভিডিও পোস্ট তৈরির প্রক্রিয়া চলছে...
        </div>

        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <a id="viewGeneratedPostBtn" href="#" class="tool-icon-btn active" style="display: none; text-decoration: none; padding: 10px 20px; font-weight: 700;">
                🎥 তৈরি হওয়া ভিডিও পোস্ট দেখুন
            </a>
            <a href="{{ route('profile.show', $currentUser->username) }}" class="tool-icon-btn" style="text-decoration: none; padding: 10px 18px;">
                আমার প্রোফাইল
            </a>
            <a href="{{ route('dashboard') }}" class="studio-nav-btn" style="padding: 10px 18px;">
                ড্যাশবোর্ডে ফিরুন
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Configuration & State
    const STREAM_ID = {{ $stream->id }};
    const CHANNEL_ID = "{{ $stream->channel_id }}";
    const CURRENT_USER_ID = {{ $currentUser->id }};
    const ICE_SERVERS = @json($iceServers);
    const SFU_ROOM_ID = "{{ $sfuRoomId ?? '' }}";
    const SFU_ENDPOINT = "{{ $sfuEndpoint ?? '' }}";
    const HOST_TOKEN = "{{ $hostToken ?? '' }}";

    let localCameraStream = null;
    let localScreenStream = null;
    let mediaRecorder = null;
    let recordedChunks = [];
    let audioContext = null;
    let audioAnalyser = null;
    let animFrameVu = null;

    let isCameraMuted = false;
    let isMicMuted = false;
    let isScreenSharing = false;
    let currentFacingMode = 'user';
    let isLiveBroadcasting = {{ $stream->status === 'live' ? 'true' : 'false' }};
    let broadcastStartTime = null;
    let timerInterval = null;
    let heartbeatInterval = null;

    let sfuPeerConnection = null;
    let peerConnections = {};

    // Elements
    const mainVideoPreview = document.getElementById('mainVideoPreview');
    const screenVideoPreview = document.getElementById('screenVideoPreview');
    const pipCamOverlay = document.getElementById('pipCamOverlay');
    const pipCameraVideo = document.getElementById('pipCameraVideo');

    const studioHeaderBadge = document.getElementById('studioHeaderBadge');
    const headerLiveDot = document.getElementById('headerLiveDot');
    const headerLiveStatusText = document.getElementById('headerLiveStatusText');
    const liveDot = document.getElementById('liveDot');
    const liveStateLabel = document.getElementById('liveStateLabel');
    const liveTimerLabel = document.getElementById('liveTimerLabel');
    const liveMetricsPill = document.getElementById('liveMetricsPill');
    const liveViewerCount = document.getElementById('liveViewerCount');
    const liveReactionCount = document.getElementById('liveReactionCount');

    const micToggleBtn = document.getElementById('micToggleBtn');
    const micLabel = document.getElementById('micLabel');
    const micVuFill = document.getElementById('micVuFill');
    const camToggleBtn = document.getElementById('camToggleBtn');
    const camLabel = document.getElementById('camLabel');
    const screenShareBtn = document.getElementById('screenShareBtn');
    const screenShareLabel = document.getElementById('screenShareLabel');

    const preLiveSettingsPanel = document.getElementById('preLiveSettingsPanel');
    const liveInteractivePanel = document.getElementById('liveInteractivePanel');
    const startLiveBtn = document.getElementById('startLiveBtn');
    const studioChatBox = document.getElementById('studioChatBox');
    const noCommentsNotice = document.getElementById('noCommentsNotice');

    // Helper for auth headers
    function getApiHeaders() {
        const token = (typeof currentToken !== 'undefined' && currentToken) ? currentToken : (localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest'
        };
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        return headers;
    }

    function showStudioNotice(msg) {
        if (window.showBondhooToast) {
            window.showBondhooToast('স্টুডিও বার্তা', msg, '🎙️', 'info');
        } else {
            alert(msg);
        }
    }

    // 1. Initial Media Hardware Setup
    async function initMediaHardware() {
        try {
            const constraints = {
                video: {
                    facingMode: currentFacingMode ? { ideal: currentFacingMode } : undefined,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: {
                    echoCancellation: { ideal: true },
                    noiseSuppression: { ideal: true },
                    autoGainControl: { ideal: true }
                }
            };

            try {
                localCameraStream = await navigator.mediaDevices.getUserMedia(constraints);
            } catch (strictErr) {
                console.warn('Studio initial capture falling back...', strictErr);
                try {
                    localCameraStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                } catch (pairErr) {
                    try {
                        localCameraStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                    } catch (vidErr) {
                        localCameraStream = await navigator.mediaDevices.getUserMedia({ video: false, audio: true });
                    }
                }
            }

            if (localCameraStream && localCameraStream.getVideoTracks().length > 0) {
                mainVideoPreview.srcObject = localCameraStream;
            }

            // Setup audio visualizer VU meter
            setupAudioAnalyser(localCameraStream);

            if (isLiveBroadcasting) {
                resumeLiveBroadcastState();
            }
        } catch (err) {
            console.error('Camera/Mic permission failed:', err);
            showStudioNotice('ক্যামেরা অথবা মাইক্রোফোনের অনুমতি পাওয়া যায়নি: ' + err.message);
        }
    }

    function setupAudioAnalyser(stream) {
        if (!stream || stream.getAudioTracks().length === 0) return;
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            if (!audioContext) audioContext = new AudioCtx();
            const source = audioContext.createMediaStreamSource(stream);
            audioAnalyser = audioContext.createAnalyser();
            audioAnalyser.fftSize = 64;
            source.connect(audioAnalyser);

            const buffer = new Uint8Array(audioAnalyser.frequencyBinCount);
            function updateVu() {
                if (!audioAnalyser || isMicMuted) {
                    if (micVuFill) micVuFill.style.width = '0%';
                    animFrameVu = requestAnimationFrame(updateVu);
                    return;
                }
                audioAnalyser.getByteFrequencyData(buffer);
                let sum = 0;
                for (let i = 0; i < buffer.length; i++) {
                    sum += buffer[i];
                }
                const avg = sum / buffer.length;
                const pct = Math.min(100, Math.round((avg / 128) * 100));
                if (micVuFill) micVuFill.style.width = pct + '%';
                animFrameVu = requestAnimationFrame(updateVu);
            }
            if (animFrameVu) cancelAnimationFrame(animFrameVu);
            animFrameVu = requestAnimationFrame(updateVu);
        } catch (e) {
            console.warn('Audio analyser notice:', e);
        }
    }

    // 2. Microphone & Camera Controls
    function toggleMicrophone() {
        if (!localCameraStream) return;
        const audioTracks = localCameraStream.getAudioTracks();
        if (audioTracks.length === 0) return;

        isMicMuted = !isMicMuted;
        audioTracks.forEach(t => t.enabled = !isMicMuted);

        if (isMicMuted) {
            micToggleBtn.classList.add('muted');
            micLabel.innerText = 'মাইক বন্ধ';
            if (micVuFill) micVuFill.style.width = '0%';
        } else {
            micToggleBtn.classList.remove('muted');
            micLabel.innerText = 'মাইক চালু';
        }
    }

    function toggleCamera() {
        if (!localCameraStream) return;
        const videoTracks = localCameraStream.getVideoTracks();
        if (videoTracks.length === 0) return;

        isCameraMuted = !isCameraMuted;
        videoTracks.forEach(t => t.enabled = !isCameraMuted);

        if (isCameraMuted) {
            camToggleBtn.classList.add('muted');
            camLabel.innerText = 'ক্যামেরা বন্ধ';
        } else {
            camToggleBtn.classList.remove('muted');
            camLabel.innerText = 'ক্যামেরা চালু';
        }
    }

    async function flipCameraFacingMode() {
        currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
        try {
            const newStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: currentFacingMode } },
                audio: false
            });

            const newTrack = newStream.getVideoTracks()[0];
            const oldTrack = localCameraStream?.getVideoTracks()[0];

            if (oldTrack && localCameraStream) {
                localCameraStream.removeTrack(oldTrack);
                oldTrack.stop();
                localCameraStream.addTrack(newTrack);
            }

            mainVideoPreview.srcObject = localCameraStream;

            // Replace track in peer connections
            Object.values(peerConnections).forEach(pc => {
                const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                if (sender) sender.replaceTrack(newTrack);
            });
            if (sfuPeerConnection) {
                const sender = sfuPeerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                if (sender) sender.replaceTrack(newTrack);
            }
        } catch (e) {
            console.warn('Flip camera not available:', e);
            showStudioNotice('ডিভাইসে ক্যামেরা ফ্লিপ করার সুবিধা সমর্থিত নয়।');
        }
    }

    // 3. Screen Sharing with Camera PiP
    async function toggleScreenShare() {
        if (!isScreenSharing) {
            try {
                localScreenStream = await navigator.mediaDevices.getDisplayMedia({
                    video: true,
                    audio: true
                });

                isScreenSharing = true;
                screenShareBtn.classList.add('active');
                screenShareLabel.innerText = 'স্ক্রিন বন্ধ';

                screenVideoPreview.srcObject = localScreenStream;
                screenVideoPreview.style.display = 'block';
                mainVideoPreview.style.display = 'none';

                pipCameraVideo.srcObject = localCameraStream;
                pipCamOverlay.style.display = 'block';

                const screenVideoTrack = localScreenStream.getVideoTracks()[0];
                screenVideoTrack.onended = () => stopScreenSharing();

                Object.values(peerConnections).forEach(pc => {
                    const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (sender) sender.replaceTrack(screenVideoTrack);
                });
                if (sfuPeerConnection) {
                    const sender = sfuPeerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (sender) sender.replaceTrack(screenVideoTrack);
                }

                notifySignaling('screen_share_started', {});
            } catch (err) {
                console.warn('Screen share cancelled/failed:', err);
            }
        } else {
            stopScreenSharing();
        }
    }

    function stopScreenSharing() {
        if (localScreenStream) {
            localScreenStream.getTracks().forEach(t => t.stop());
            localScreenStream = null;
        }

        isScreenSharing = false;
        screenShareBtn.classList.remove('active');
        screenShareLabel.innerText = 'স্ক্রিন শেয়ার';

        screenVideoPreview.style.display = 'none';
        pipCamOverlay.style.display = 'none';
        mainVideoPreview.style.display = 'block';

        if (localCameraStream) {
            const camVideoTrack = localCameraStream.getVideoTracks()[0];
            if (camVideoTrack) {
                Object.values(peerConnections).forEach(pc => {
                    const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (sender) sender.replaceTrack(camVideoTrack);
                });
                if (sfuPeerConnection) {
                    const sender = sfuPeerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (sender) sender.replaceTrack(camVideoTrack);
                }
            }
        }

        notifySignaling('screen_share_stopped', {});
    }

    function toggleFullscreen() {
        const vp = document.getElementById('videoViewport');
        if (!document.fullscreenElement) {
            vp.requestFullscreen().catch(err => console.warn(err));
        } else {
            document.exitFullscreen().catch(err => console.warn(err));
        }
    }

    // 4. Starting Live Broadcast
    async function handleStartLive() {
        const title = document.getElementById('liveTitle').value.trim();
        if (!title) {
            alert('অনুগ্রহ করে লাইভের একটি শিরোনাম লিখুন।');
            document.getElementById('liveTitle').focus();
            return;
        }

        startLiveBtn.disabled = true;
        startLiveBtn.innerHTML = '<span>⏳ সম্প্রচার প্রস্তুত হচ্ছে...</span>';
        liveStateLabel.innerText = 'সংযোগ স্থাপন হচ্ছে...';

        const payload = {
            title: title,
            description: document.getElementById('liveDescription').value.trim(),
            privacy: document.getElementById('livePrivacy').value,
            comments_enabled: document.getElementById('commentsEnabledToggle').checked,
            reactions_enabled: document.getElementById('reactionsEnabledToggle').checked,
            sharing_enabled: document.getElementById('sharingEnabledToggle').checked,
            recording_enabled: document.getElementById('recordingEnabledToggle').checked,
        };

        try {
            const startRes = await fetch(`/api/v2/live/streams/${STREAM_ID}/start`, {
                method: 'POST',
                headers: getApiHeaders(),
                body: JSON.stringify(payload)
            });

            const data = await startRes.json();
            if (data.status === 'success') {
                isLiveBroadcasting = true;
                resumeLiveBroadcastState();

                if (payload.recording_enabled) {
                    startLocalRecording();
                }

                startPresenceHeartbeat();
            } else {
                throw new Error(data.message || 'লাইভ শুরু করতে ব্যর্থ হয়েছে।');
            }
        } catch (e) {
            alert('ত্রুটি: ' + e.message);
            startLiveBtn.disabled = false;
            startLiveBtn.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg><span>গো লাইভ শুরু করুন (GO LIVE)</span>';
            liveStateLabel.innerText = 'স্ট্যান্ডবাই';
        }
    }

    function resumeLiveBroadcastState() {
        if (liveDot) liveDot.style.display = 'inline-block';
        if (headerLiveDot) headerLiveDot.style.display = 'inline-block';
        if (studioHeaderBadge) {
            studioHeaderBadge.className = 'studio-badge-pill live';
        }
        if (headerLiveStatusText) headerLiveStatusText.innerText = 'লাইভ চলছে (BROADCASTING)';

        liveStateLabel.innerText = '🔴 LIVE';
        liveTimerLabel.style.display = 'inline';
        liveMetricsPill.style.display = 'flex';

        preLiveSettingsPanel.style.display = 'none';
        liveInteractivePanel.style.display = 'block';

        broadcastStartTime = new Date();
        clearInterval(timerInterval);
        timerInterval = setInterval(updateLiveTimer, 1000);

        initSfuUpstream();
        setupRealtimeSubscriptions();
    }

    async function initSfuUpstream() {
        const activeStream = isScreenSharing ? localScreenStream : localCameraStream;
        if (!activeStream) return;

        try {
            if (sfuPeerConnection) {
                try { sfuPeerConnection.close(); } catch (e) {}
            }

            sfuPeerConnection = new RTCPeerConnection({ iceServers: ICE_SERVERS });
            activeStream.getTracks().forEach(track => {
                sfuPeerConnection.addTrack(track, activeStream);
            });

            sfuPeerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    notifySignaling('candidate', event.candidate, null);
                }
            };

            const offer = await sfuPeerConnection.createOffer();
            await sfuPeerConnection.setLocalDescription(offer);

            notifySignaling('offer', offer, null);

            if (HOST_TOKEN) {
                await fetch(`/api/v2/live/streams/${STREAM_ID}/sfu/publish`, {
                    method: 'POST',
                    headers: getApiHeaders(),
                    body: JSON.stringify({
                        host_token: HOST_TOKEN,
                        track_meta: { fps: 30, resolution: '720p', codec: 'H264/AAC' }
                    })
                });
            }
        } catch (e) {
            console.warn('SFU Upstream initialization notice:', e);
        }
    }

    function updateLiveTimer() {
        if (!broadcastStartTime) return;
        const diffSeconds = Math.floor((new Date() - broadcastStartTime) / 1000);
        const hrs = String(Math.floor(diffSeconds / 3600)).padStart(2, '0');
        const mins = String(Math.floor((diffSeconds % 3600) / 60)).padStart(2, '0');
        const secs = String(diffSeconds % 60).padStart(2, '0');
        liveTimerLabel.innerText = `${hrs}:${mins}:${secs}`;
    }

    // 5. Media Recording for Replay Generation
    function startLocalRecording() {
        try {
            const streamToRecord = isScreenSharing ? localScreenStream : localCameraStream;
            if (!streamToRecord) return;

            recordedChunks = [];
            const mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp9,opus')
                ? 'video/webm;codecs=vp9,opus'
                : 'video/webm';

            mediaRecorder = new MediaRecorder(streamToRecord, { mimeType });
            mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    recordedChunks.push(event.data);
                }
            };
            mediaRecorder.start(2000);
        } catch (e) {
            console.warn('MediaRecorder init notice:', e);
        }
    }

    // 6. Realtime Signaling & Subscriptions
    function setupRealtimeSubscriptions() {
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel(`live.${CHANNEL_ID}`)
                .listen('.live.viewer.count', (e) => {
                    if (liveViewerCount) liveViewerCount.innerText = e.viewers_count || 0;
                })
                .listen('.live.comment.created', (e) => {
                    appendComment(e);
                })
                .listen('.live.comment.deleted', (e) => {
                    removeComment(e.comment_id);
                })
                .listen('.live.reaction.created', (e) => {
                    spawnFloatingReaction(e.reaction_type);
                    if (liveReactionCount) liveReactionCount.innerText = e.total_reactions || 0;
                })
                .listen('.live.signal', async (e) => {
                    if (e.sender_id !== CURRENT_USER_ID) {
                        handleIncomingWebRtcSignal(e);
                    }
                });
        }
    }

    async function handleIncomingWebRtcSignal(msg) {
        if (msg.signal_type === 'answer' && sfuPeerConnection) {
            try {
                if (sfuPeerConnection.signalingState !== 'stable') {
                    await sfuPeerConnection.setRemoteDescription(new RTCSessionDescription(msg.payload));
                }
            } catch (e) {
                console.warn('SFU Answer processing notice:', e);
            }
            return;
        }

        if (msg.signal_type === 'candidate' && sfuPeerConnection && !msg.target_user_id) {
            try {
                await sfuPeerConnection.addIceCandidate(new RTCIceCandidate(msg.payload));
            } catch (e) {
                console.warn('SFU ICE Candidate notice:', e);
            }
            return;
        }

        const viewerId = msg.sender_id;
        if (!viewerId) return;

        if (msg.signal_type === 'offer') {
            const pc = new RTCPeerConnection({ iceServers: ICE_SERVERS });
            peerConnections[viewerId] = pc;

            const activeStream = isScreenSharing ? localScreenStream : localCameraStream;
            if (activeStream) {
                activeStream.getTracks().forEach(track => pc.addTrack(track, activeStream));
            }

            pc.onicecandidate = (event) => {
                if (event.candidate) {
                    notifySignaling('candidate', event.candidate, viewerId);
                }
            };

            await pc.setRemoteDescription(new RTCSessionDescription(msg.payload));
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);

            notifySignaling('answer', answer, viewerId);
        } else if (msg.signal_type === 'candidate' && peerConnections[viewerId]) {
            try {
                await peerConnections[viewerId].addIceCandidate(new RTCIceCandidate(msg.payload));
            } catch (e) {
                console.warn('Add ice candidate error:', e);
            }
        }
    }

    async function notifySignaling(signalType, payload, targetUserId = null) {
        try {
            await fetch(`/api/v2/live/streams/${STREAM_ID}/signal`, {
                method: 'POST',
                headers: getApiHeaders(),
                body: JSON.stringify({
                    signal_type: signalType,
                    payload: payload,
                    target_user_id: targetUserId
                })
            });
        } catch (e) {
            console.warn('Signaling push error:', e);
        }
    }

    // 7. Presence Heartbeat
    function startPresenceHeartbeat() {
        const sessionId = 'broadcaster_' + CURRENT_USER_ID;
        clearInterval(heartbeatInterval);
        heartbeatInterval = setInterval(async () => {
            try {
                const res = await fetch(`/api/v2/live/streams/${STREAM_ID}/heartbeat`, {
                    method: 'POST',
                    headers: getApiHeaders(),
                    body: JSON.stringify({ session_id: sessionId })
                });
                const data = await res.json();
                if (data.data && liveViewerCount) {
                    liveViewerCount.innerText = data.data.viewers_count || 0;
                }
            } catch (e) {
                console.warn('Heartbeat error:', e);
            }
        }, 15000);
    }

    // 8. Live Chat Functions
    function appendComment(commentData) {
        if (noCommentsNotice) noCommentsNotice.style.display = 'none';

        const bubble = document.createElement('div');
        bubble.className = 'studio-comment-card';
        bubble.id = `studioComment_${commentData.id}`;
        bubble.innerHTML = `
            <div class="comment-card-top">
                <span class="comment-card-author">${escapeHtml(commentData.user.name)}</span>
                <button type="button" class="tool-icon-btn" style="padding: 2px 8px; font-size: 11px; color: #f87171;" onclick="deleteCommentAsBroadcaster(${commentData.id})">মুছুন</button>
            </div>
            <div class="comment-card-text">${escapeHtml(commentData.message)}</div>
        `;
        studioChatBox.appendChild(bubble);
        studioChatBox.scrollTop = studioChatBox.scrollHeight;
    }

    function removeComment(commentId) {
        const el = document.getElementById(`studioComment_${commentId}`);
        if (el) el.remove();
    }

    async function deleteCommentAsBroadcaster(commentId) {
        if (!confirm('এই মন্তব্যটি মুছে ফেলতে চান?')) return;
        try {
            await fetch(`/api/v2/live/streams/${STREAM_ID}/comments/${commentId}`, {
                method: 'DELETE',
                headers: getApiHeaders()
            });
            removeComment(commentId);
        } catch (e) {
            alert('মন্তব্য মুছতে ব্যর্থ হয়েছে: ' + e.message);
        }
    }

    // 9. Floating Reaction Animation
    function spawnFloatingReaction(reactionType) {
        const emojiMap = {
            like: '👍',
            love: '❤️',
            care: '🥰',
            haha: '😆',
            wow: '😮',
            sad: '😢',
            angry: '😡'
        };
        const emoji = emojiMap[reactionType] || '❤️';
        const area = document.getElementById('floatingReactionsArea');
        if (!area) return;
        const span = document.createElement('span');
        span.className = 'studio-floating-emoji';
        span.innerText = emoji;
        span.style.left = (Math.random() * 40) + 'px';
        area.appendChild(span);
        setTimeout(() => span.remove(), 2500);
    }

    // 10. End Live Flow & Summary
    function confirmEndLive() {
        document.getElementById('endLiveConfirmModal').style.display = 'flex';
    }

    function closeEndLiveModal() {
        document.getElementById('endLiveConfirmModal').style.display = 'none';
    }

    function captureCurrentVideoFrameBlob() {
        return new Promise((resolve) => {
            try {
                const videoEl = isScreenSharing ? screenVideoPreview : mainVideoPreview;
                if (!videoEl || !videoEl.videoWidth) {
                    return resolve(null);
                }
                const canvas = document.createElement('canvas');
                canvas.width = 640;
                canvas.height = 360;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);
                canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.88);
            } catch (e) {
                console.warn('Frame capture notice:', e);
                resolve(null);
            }
        });
    }

    async function executeEndLive() {
        closeEndLiveModal();

        try {
            let thumbnailBlob = null;
            try {
                thumbnailBlob = await captureCurrentVideoFrameBlob();
            } catch (err) {
                console.warn('Thumbnail generation skipped:', err);
            }

            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                await new Promise((resolve) => {
                    mediaRecorder.onstop = () => resolve();
                    try {
                        mediaRecorder.stop();
                    } catch (e) {
                        resolve();
                    }
                    setTimeout(resolve, 1200);
                });
            }

            // Immediately stop camera, microphone, and audio analyser
            if (animFrameVu) cancelAnimationFrame(animFrameVu);
            if (audioContext) {
                try { audioContext.close(); } catch (e) {}
            }
            if (localCameraStream) {
                localCameraStream.getTracks().forEach(t => t.stop());
                localCameraStream = null;
            }
            if (localScreenStream) {
                localScreenStream.getTracks().forEach(t => t.stop());
                localScreenStream = null;
            }
            mainVideoPreview.srcObject = null;
            screenVideoPreview.srcObject = null;
            pipCameraVideo.srcObject = null;

            if (sfuPeerConnection) {
                try { sfuPeerConnection.close(); } catch (e) {}
                sfuPeerConnection = null;
            }
            Object.values(peerConnections).forEach(pc => {
                try { pc.close(); } catch (e) {}
            });
            peerConnections = {};

            clearInterval(timerInterval);
            clearInterval(heartbeatInterval);
            isLiveBroadcasting = false;

            if (liveDot) liveDot.style.display = 'none';
            if (headerLiveDot) headerLiveDot.style.display = 'none';
            if (studioHeaderBadge) {
                studioHeaderBadge.className = 'studio-badge-pill ready';
            }
            if (headerLiveStatusText) headerLiveStatusText.innerText = 'লাইভ সমাপ্ত (CONCLUDED)';
            liveStateLabel.innerText = '⏹️ সমাপ্ত';

            const res = await fetch(`/api/v2/live/streams/${STREAM_ID}/end`, {
                method: 'POST',
                headers: getApiHeaders()
            });

            const data = await res.json();
            const summary = data.data || {};

            const durationSec = summary.ended_at && summary.started_at ?
                Math.floor((new Date(summary.ended_at) - new Date(summary.started_at)) / 1000) : (summary.duration || 0);
            const mins = String(Math.floor(durationSec / 60)).padStart(2, '0');
            const secs = String(durationSec % 60).padStart(2, '0');

            document.getElementById('summaryDuration').innerText = `${mins}:${secs}`;
            document.getElementById('summaryPeak').innerText = summary.peak_viewers || 0;
            document.getElementById('summaryComments').innerText = studioChatBox.children.length;
            document.getElementById('summaryReactions').innerText = summary.total_reactions || 0;

            document.getElementById('liveSummaryModal').style.display = 'flex';

            // Upload recorded stream to backend
            if (recordedChunks.length > 0) {
                const recordedBlob = new Blob(recordedChunks, { type: 'video/webm' });
                const formData = new FormData();
                formData.append('replay_file', recordedBlob, `live_${STREAM_ID}.webm`);
                if (thumbnailBlob) {
                    formData.append('thumbnail_file', thumbnailBlob, `live_thumb_${STREAM_ID}.jpg`);
                }

                document.getElementById('replayUploadStatusBox').innerHTML = '⏳ রেকর্ডিং প্রসেস হচ্ছে এবং আপনার প্রোফাইলে স্থায়ী ভিডিও পোস্ট তৈরি হচ্ছে...';

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const token = (typeof currentToken !== 'undefined' && currentToken) ? currentToken : (localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '');
                const headers = { 'X-CSRF-TOKEN': csrf };
                if (token) headers['Authorization'] = `Bearer ${token}`;

                const uploadRes = await fetch(`/api/v2/live/streams/${STREAM_ID}/replay-upload`, {
                    method: 'POST',
                    headers: headers,
                    body: formData
                });

                const uploadData = await uploadRes.json();
                if (uploadData.status === 'success') {
                    document.getElementById('replayUploadStatusBox').innerHTML = '✓ লাইভ ভিডিওটি সফলভাবে আপনার প্রোফাইল এবং ফিডে সাধারণ ভিডিও হিসেবে পোস্ট করা হয়েছে!';
                    if (uploadData.post_url) {
                        const viewPostBtn = document.getElementById('viewGeneratedPostBtn');
                        if (viewPostBtn) {
                            viewPostBtn.href = uploadData.post_url;
                            viewPostBtn.style.display = 'inline-block';
                        }
                    }
                } else {
                    document.getElementById('replayUploadStatusBox').innerText = 'লাইভ সমাপ্ত হয়েছে।';
                }
            } else {
                document.getElementById('replayUploadStatusBox').innerText = 'লাইভ সেশন সফলভাবে সম্পন্ন হয়েছে।';
            }
        } catch (e) {
            console.error('Error ending live:', e);
            alert('লাইভ শেষ করতে ত্রুটি: ' + e.message);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    // Initialize on window load
    window.addEventListener('DOMContentLoaded', () => {
        initMediaHardware();
    });

    // Cleanup on beforeunload
    window.addEventListener('beforeunload', () => {
        if (animFrameVu) cancelAnimationFrame(animFrameVu);
        if (localCameraStream) localCameraStream.getTracks().forEach(t => t.stop());
        if (localScreenStream) localScreenStream.getTracks().forEach(t => t.stop());

        if (isLiveBroadcasting) {
            try {
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(`/api/v2/live/streams/${STREAM_ID}/leave`, new Blob([JSON.stringify({ session_id: 'broadcaster_' + CURRENT_USER_ID })], { type: 'application/json' }));
                }
            } catch (e) {}
        }
    });
</script>
@endsection
