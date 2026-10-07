@extends('layouts.app')

@section('title', $stream->title . ' — Bondhoo লাইভ')

@section('styles')
<style>
    :root {
        --live-red: #ef4444;
        --live-red-glow: rgba(239, 68, 68, 0.45);
        --live-green: #10b981;
        --live-blue: #3b82f6;
        --player-bg: #07090e;
    }

    .viewer-layout {
        max-width: 1440px;
        margin: 0 auto;
        padding: 16px;
        display: grid;
        grid-template-columns: 1fr 390px;
        gap: 20px;
        min-height: calc(100vh - 75px);
    }

    /* Video Player & Stage */
    .player-stage-card {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
    }

    .video-viewport {
        position: relative;
        width: 100%;
        height: 560px;
        background: var(--player-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    #liveVideoElement, #replayVideoElement {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #000;
        outline: none;
    }

    /* Top Badges Overlay */
    .viewport-overlay-top {
        position: absolute;
        top: 16px;
        left: 16px;
        right: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 25;
        pointer-events: none;
    }

    .top-left-badges {
        display: flex;
        align-items: center;
        gap: 10px;
        pointer-events: auto;
    }

    .live-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.5px;
        color: #fff;
        backdrop-filter: blur(12px);
        box-shadow: 0 4px 14px rgba(0,0,0,0.3);
    }

    .live-status-pill.live {
        background: rgba(239, 68, 68, 0.92);
        box-shadow: 0 0 16px var(--live-red-glow);
    }

    .live-status-pill.replay {
        background: rgba(59, 130, 246, 0.92);
    }

    .live-status-pill.ended {
        background: rgba(107, 114, 128, 0.92);
    }

    .live-pulsing-dot {
        width: 9px;
        height: 9px;
        background-color: #ffffff;
        border-radius: 50%;
        animation: pulseLiveDot 1.4s infinite ease-in-out;
    }

    @keyframes pulseLiveDot {
        0%, 100% { transform: scale(0.9); opacity: 0.8; }
        50% { transform: scale(1.35); opacity: 1; }
    }

    .stream-duration-pill {
        background: rgba(0, 0, 0, 0.65);
        color: #f1f5f9;
        font-size: 12px;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        padding: 5px 12px;
        border-radius: 20px;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .top-right-badges {
        display: flex;
        align-items: center;
        gap: 10px;
        pointer-events: auto;
    }

    .viewer-count-pill {
        background: rgba(0, 0, 0, 0.7);
        color: #ffffff;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .stream-quality-badge {
        background: rgba(16, 185, 129, 0.2);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.4);
        font-size: 11px;
        font-weight: 800;
        padding: 4px 9px;
        border-radius: 6px;
        backdrop-filter: blur(8px);
        letter-spacing: 0.5px;
    }

    /* Bottom Custom Video Controls */
    .viewport-controls-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 24px 20px 14px 20px;
        background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 60%, transparent 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        z-index: 25;
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .video-viewport:hover .viewport-controls-bar,
    .video-viewport:focus-within .viewport-controls-bar,
    .viewport-controls-bar.always-show {
        opacity: 1;
    }

    .ctrl-group-left, .ctrl-group-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .player-ctrl-btn {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: white;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        backdrop-filter: blur(8px);
    }

    .player-ctrl-btn:hover {
        background: rgba(255, 255, 255, 0.28);
        transform: scale(1.08);
    }

    .volume-slider-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .volume-slider {
        width: 80px;
        height: 5px;
        -webkit-appearance: none;
        appearance: none;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 4px;
        outline: none;
        cursor: pointer;
    }

    .volume-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 13px;
        height: 13px;
        border-radius: 50%;
        background: #ffffff;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }

    /* Floating Reactions Box */
    .floating-reactions-box {
        position: absolute;
        bottom: 80px;
        right: 28px;
        width: 120px;
        height: 360px;
        pointer-events: none;
        overflow: hidden;
        z-index: 30;
    }

    .viewer-floating-emoji {
        position: absolute;
        bottom: 0;
        font-size: 32px;
        animation: floatViewerEmoji 2.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        filter: drop-shadow(0 4px 10px rgba(0,0,0,0.4));
    }

    @keyframes floatViewerEmoji {
        0% { transform: translateY(0) scale(0.6) rotate(0deg); opacity: 1; }
        40% { transform: translateY(-130px) scale(1.25) rotate(12deg); opacity: 0.95; }
        80% { transform: translateY(-260px) scale(1.05) rotate(-10deg); opacity: 0.8; }
        100% { transform: translateY(-340px) scale(0.85) rotate(6deg); opacity: 0; }
    }

    /* Broadcaster Bar */
    .broadcaster-bar {
        background: var(--fb-card);
        padding: 18px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid var(--fb-border);
        flex-wrap: wrap;
        gap: 16px;
    }

    .broadcaster-meta {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .avatar-wrapper {
        position: relative;
    }

    .broadcaster-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--fb-primary);
        display: block;
    }

    .broadcaster-avatar.live-ring {
        border-color: var(--live-red);
        animation: livePulseHalo 2s infinite ease-out;
    }

    @keyframes livePulseHalo {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    .stream-info-text h1 {
        font-size: 19px;
        font-weight: 800;
        color: var(--fb-text-primary);
        margin: 0 0 4px 0;
        line-height: 1.3;
    }

    .stream-sub-info {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--fb-text-secondary);
    }

    .author-link {
        font-weight: 700;
        color: var(--fb-text-primary);
        text-decoration: none;
        transition: color 0.15s;
    }

    .author-link:hover {
        color: var(--fb-primary);
    }

    .verified-badge {
        color: #1d9bf0;
        display: inline-flex;
    }

    .stream-action-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-stream-action {
        background: var(--fb-bg);
        border: 1px solid var(--fb-border);
        color: var(--fb-text-primary);
        padding: 9px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-stream-action:hover {
        background: var(--fb-hover);
        color: var(--fb-primary);
        border-color: var(--fb-primary);
    }

    .btn-stream-action.primary {
        background: var(--live-red);
        color: white;
        border-color: var(--live-red);
    }

    .btn-stream-action.primary:hover {
        background: #dc2626;
        color: white;
    }

    .stream-description-card {
        padding: 16px 24px;
        font-size: 14px;
        line-height: 1.6;
        color: var(--fb-text-secondary);
        background: var(--fb-card);
        border-top: 1px solid var(--fb-border);
    }

    /* Right Sidebar: Chat & Reactions */
    .viewer-chat-sidebar {
        display: flex;
        flex-direction: column;
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        height: calc(100vh - 75px);
        position: sticky;
        top: 85px;
        overflow: hidden;
    }

    .sidebar-header-bar {
        padding: 16px 20px;
        border-bottom: 1px solid var(--fb-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--fb-card);
    }

    .sidebar-title {
        font-size: 16px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--fb-text-primary);
        margin: 0;
    }

    .connection-status-pill {
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--live-green);
        background: rgba(16, 185, 129, 0.1);
        padding: 4px 10px;
        border-radius: 14px;
    }

    .comments-feed-area {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        scroll-behavior: smooth;
        position: relative;
    }

    .comment-bubble {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        animation: commentSlideIn 0.25s ease-out forwards;
    }

    @keyframes commentSlideIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .comment-user-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }

    .comment-bubble-body {
        background: var(--fb-bg);
        border: 1px solid var(--fb-border);
        border-radius: 14px;
        padding: 8px 14px;
        flex: 1;
        position: relative;
    }

    .comment-bubble-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }

    .comment-author-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--fb-text-primary);
    }

    .comment-author-name.host {
        color: var(--live-red);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .comment-time-text {
        font-size: 11px;
        color: var(--fb-text-secondary);
    }

    .comment-bubble-text {
        font-size: 13px;
        color: var(--fb-text-primary);
        line-height: 1.45;
        word-break: break-word;
    }

    .btn-delete-comment {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        padding: 2px 6px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 2px;
        opacity: 0.7;
        transition: opacity 0.15s;
    }

    .btn-delete-comment:hover {
        opacity: 1;
        background: rgba(239, 68, 68, 0.1);
    }

    /* Floating Scroll To Bottom Helper */
    .scroll-bottom-pill {
        position: absolute;
        bottom: 70px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--fb-primary);
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        cursor: pointer;
        display: none;
        align-items: center;
        gap: 6px;
        z-index: 10;
        transition: all 0.2s;
    }

    .scroll-bottom-pill:hover {
        transform: translateX(-50%) scale(1.05);
    }

    /* Reactions Bar */
    .reactions-strip-bar {
        padding: 10px 14px;
        background: var(--fb-bg);
        border-top: 1px solid var(--fb-border);
        display: flex;
        justify-content: space-around;
        align-items: center;
    }

    .viewer-react-btn {
        background: transparent;
        border: none;
        font-size: 24px;
        cursor: pointer;
        padding: 6px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.15s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .viewer-react-btn:hover {
        transform: scale(1.4);
    }

    .viewer-react-btn:active {
        transform: scale(0.9);
    }

    /* Chat Composer Row */
    .chat-composer-box {
        padding: 12px 16px;
        background: var(--fb-card);
        border-top: 1px solid var(--fb-border);
    }

    .chat-input-container {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .chat-text-input {
        flex: 1;
        padding: 10px 16px;
        border: 1px solid var(--fb-border);
        border-radius: 24px;
        background: var(--fb-bg);
        color: var(--fb-text-primary);
        font-size: 13px;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    .chat-text-input:focus {
        border-color: var(--fb-primary);
        box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.15);
    }

    .btn-send-chat {
        background: var(--fb-primary);
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s, transform 0.1s;
        flex-shrink: 0;
    }

    .btn-send-chat:hover:not(:disabled) {
        background: var(--fb-primary-hover);
        transform: scale(1.05);
    }

    .btn-send-chat:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Modal Backdrop & Card */
    .live-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(6px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 16px;
    }

    .live-modal-content {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 16px;
        width: 100%;
        max-width: 500px;
        padding: 24px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    .live-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--fb-border);
    }

    .live-modal-title {
        font-size: 17px;
        font-weight: 800;
        color: var(--fb-text-primary);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .viewer-layout {
            grid-template-columns: 1fr;
        }
        .viewer-chat-sidebar {
            height: 520px;
            position: static;
        }
    }

    @media (max-width: 768px) {
        .viewer-layout {
            padding: 8px;
            gap: 12px;
        }
        .video-viewport {
            height: 300px;
            border-radius: 10px;
        }
        .player-stage-card {
            border-radius: 12px;
        }
        .broadcaster-bar {
            padding: 14px 16px;
        }
        .stream-info-text h1 {
            font-size: 16px;
        }
        .stream-action-group {
            width: 100%;
            justify-content: flex-start;
        }
        .btn-stream-action {
            flex: 1;
            justify-content: center;
        }
    }
</style>
@endsection

@section('content')
<div class="viewer-layout">
    <!-- Left Column: Video Stage, Broadcaster Info & Details -->
    <div class="player-stage-card">
        <div class="video-viewport" id="videoBox">
            @if($stream->isLive())
                <!-- Live WebRTC Media Stream Video Player -->
                <video id="liveVideoElement" autoplay playsinline></video>
            @elseif($stream->hasReplay())
                <!-- Recorded Replay Video Player -->
                <video id="replayVideoElement" controls autoplay playsinline src="{{ $stream->recording_url }}"></video>
            @else
                <!-- Stream Concluded Presentation -->
                <div style="text-align: center; color: white; padding: 48px 24px; max-width: 520px;" id="streamEndedNotice">
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.08); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px auto; border: 1px solid rgba(255,255,255,0.15);">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                    </div>
                    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 8px;">লাইভ সম্প্রচার সমাপ্ত হয়েছে</h2>
                    <p style="font-size: 14px; opacity: 0.85; margin: 0 auto 20px auto; line-height: 1.5;">
                        @if($stream->recording_status === 'processing')
                            ব্রডকাস্টার দ্বারা সরাসরি সম্প্রচার সম্পন্ন হয়েছে। রিপ্লে এনকোড ও প্রস্তুত করা হচ্ছে...
                        @else
                            এই লাইভ সেশনটি শেষ হয়েছে। আরও দারুণ লাইভ ব্রডকাস্ট দেখতে ওয়াচ ফিড ব্রাউজ করুন।
                        @endif
                    </p>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <a href="{{ route('watch.index') }}" class="btn-stream-action primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            <span>অন্যান্য লাইভ ভিডিও দেখুন</span>
                        </a>
                        <a href="{{ route('dashboard') }}" class="btn-stream-action">
                            <span>হোম ফিডে যান</span>
                        </a>
                    </div>
                </div>
            @endif

            <!-- Top Overlays (LIVE badge, Live Duration, Real Viewers Count) -->
            <div class="viewport-overlay-top">
                <div class="top-left-badges">
                    @if($stream->isLive())
                        <div class="live-status-pill live">
                            <span class="live-pulsing-dot"></span>
                            <span>LIVE</span>
                        </div>
                        <div class="stream-duration-pill" id="liveDurationDisplay">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span id="liveDurationTimer">00:00</span>
                        </div>
                    @elseif($stream->hasReplay())
                        <div class="live-status-pill replay">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            <span>রিপ্লে (Replay)</span>
                        </div>
                    @else
                        <div class="live-status-pill ended">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                            <span>সমাপ্ত</span>
                        </div>
                    @endif
                </div>

                <div class="top-right-badges">
                    <span class="stream-quality-badge">HD 1080p</span>
                    <div class="viewer-count-pill" id="viewerCountBadge">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span id="currentViewerCountDisplay">{{ number_format($stream->viewers_count) }}</span>
                        <span style="font-size: 11px; opacity: 0.85;">দেখছেন</span>
                    </div>
                </div>
            </div>

            <!-- Custom Video Controls (Play/Pause, Mute/Unmute, Volume Slider, Fullscreen) -->
            @if($stream->isLive())
                <div class="viewport-controls-bar" id="customControlsBar">
                    <div class="ctrl-group-left">
                        <button type="button" class="player-ctrl-btn" id="ctrlPlayPauseBtn" onclick="togglePlayPause()" title="প্লে/পজ">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" id="playPauseIcon"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        </button>

                        <div class="volume-slider-wrapper">
                            <button type="button" class="player-ctrl-btn" id="ctrlMuteBtn" onclick="toggleMute()" title="মিউট/আনমিউট">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" id="muteIcon"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                            </button>
                            <input type="range" class="volume-slider" id="volumeSlider" min="0" max="1" step="0.05" value="1" oninput="changeVolume(this.value)">
                        </div>
                    </div>

                    <div class="ctrl-group-right">
                        <button type="button" class="player-ctrl-btn" onclick="toggleFullscreen()" title="ফুলস্ক্রিন">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Floating Reactions Stream Container -->
            <div class="floating-reactions-box" id="viewerFloatingArea"></div>
        </div>

        <!-- Broadcaster Metadata & Actions -->
        <div class="broadcaster-bar">
            <div class="broadcaster-meta">
                <div class="avatar-wrapper">
                    <img src="{{ $stream->user->profile?->avatar_url ?: '/images/default-avatar.svg' }}"
                         alt="{{ $stream->user->name }}"
                         class="broadcaster-avatar {{ $stream->isLive() ? 'live-ring' : '' }}">
                </div>
                <div class="stream-info-text">
                    <h1>{{ $stream->title }}</h1>
                    <div class="stream-sub-info">
                        <a href="{{ route('profile.show', $stream->user->username) }}" class="author-link">
                            {{ $stream->user->name }}
                        </a>
                        <span class="verified-badge" title="ভেরিফাইড ক্রিয়েটর">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="#1d9bf0"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        </span>
                        <span>•</span>
                        <span>{{ $stream->started_at ? $stream->started_at->diffForHumans() : $stream->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>

            <div class="stream-action-group">
                @if($isBroadcaster && $stream->isLive())
                    <a href="{{ route('live.studio') }}" class="btn-stream-action primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                        <span>ব্রডকাস্ট স্টুডিও</span>
                    </a>
                @endif

                <button type="button" class="btn-stream-action" onclick="openShareModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" x2="12" y1="2" y2="15"/></svg>
                    <span>শেয়ার করুন</span>
                </button>

                <button type="button" class="btn-stream-action" onclick="openReportModal()" title="রিপোর্ট করুন">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/></svg>
                    <span>রিপোর্ট</span>
                </button>
            </div>
        </div>

        @if($stream->description)
            <div class="stream-description-card">
                {{ $stream->description }}
            </div>
        @endif
    </div>

    <!-- Right Column: Real-time Live Chat & Reactions -->
    <div class="viewer-chat-sidebar">
        <div class="sidebar-header-bar">
            <h3 class="sidebar-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>লাইভ চ্যাট</span>
            </h3>
            <span class="connection-status-pill" id="chatConnectionStatus">
                <span style="width: 7px; height: 7px; background: currentColor; border-radius: 50%;"></span>
                <span>{{ $stream->isLive() ? 'সরাসরি সংযুক্ত' : 'চ্যাট আর্কাইভ' }}</span>
            </span>
        </div>

        <!-- Scrollable Comments Stream -->
        <div class="comments-feed-area" id="viewerCommentsBox" onscroll="handleChatScroll()">
            @forelse($stream->comments as $comment)
                <div class="comment-bubble" id="comment_{{ $comment->id }}">
                    <img src="{{ $comment->user->profile?->avatar_url ?: '/images/default-avatar.svg' }}"
                         alt="{{ $comment->user->name }}"
                         class="comment-user-avatar">
                    <div class="comment-bubble-body">
                        <div class="comment-bubble-top">
                            <span class="comment-author-name {{ $comment->user_id === $stream->user_id ? 'host' : '' }}">
                                {{ $comment->user->name }}
                                @if($comment->user_id === $stream->user_id)
                                    <span style="font-size: 10px; background: rgba(239,68,68,0.15); color: #ef4444; padding: 1px 5px; border-radius: 4px;">হোস্ট</span>
                                @endif
                            </span>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span class="comment-time-text">{{ $comment->created_at->diffForHumans(null, true) }}</span>
                                @if(($currentUser && $currentUser->id === $comment->user_id) || $isModerator || $isBroadcaster)
                                    <button type="button" class="btn-delete-comment" onclick="deleteLiveComment({{ $comment->id }})" title="মুছুন">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="comment-bubble-text">{{ $comment->message }}</div>
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px; margin: auto; padding: 20px;" id="emptyCommentNotice">
                    <div style="font-size: 32px; margin-bottom: 8px;">💬</div>
                    <div>এখনও কোনো মন্তব্য নেই।</div>
                    <div style="font-size: 12px; opacity: 0.8; margin-top: 2px;">সরাসরি আড্ডায় যুক্ত হতে প্রথম মন্তব্যটি লিখুন!</div>
                </div>
            @endforelse

            <!-- Floating Scroll To Bottom Button -->
            <div class="scroll-bottom-pill" id="scrollBottomBtn" onclick="scrollToChatBottom()">
                <span>নতুন মন্তব্য নিচে দেখুন</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>

        <!-- Quick Interactive Reactions Bar -->
        @if($stream->reactions_enabled)
            <div class="reactions-strip-bar">
                <button type="button" class="viewer-react-btn" onclick="sendReaction('like')" title="লাইক">👍</button>
                <button type="button" class="viewer-react-btn" onclick="sendReaction('love')" title="লাভ">❤️</button>
                <button type="button" class="viewer-react-btn" onclick="sendReaction('care')" title="কেয়ার">🥰</button>
                <button type="button" class="viewer-react-btn" onclick="sendReaction('haha')" title="হাহা">😆</button>
                <button type="button" class="viewer-react-btn" onclick="sendReaction('wow')" title="ওয়াও">😮</button>
                <button type="button" class="viewer-react-btn" onclick="sendReaction('sad')" title="স্যাড">😢</button>
                <button type="button" class="viewer-react-btn" onclick="sendReaction('angry')" title="অ্যাংরি">😡</button>
            </div>
        @endif

        <!-- Comment Composer Form -->
        @if($stream->comments_enabled && $stream->isLive())
            <div class="chat-composer-box">
                <form id="commentForm" onsubmit="handleSendComment(event)" class="chat-input-container">
                    <input type="text"
                           id="commentMessageInput"
                           class="chat-text-input"
                           placeholder="{{ $currentUser ? 'একটি মন্তব্য লিখুন...' : 'মন্তব্য করতে লগইন করুন' }}"
                           {{ $currentUser ? '' : 'disabled' }}
                           maxlength="500"
                           autocomplete="off">
                    <button type="submit" class="btn-send-chat" {{ $currentUser ? '' : 'disabled' }} title="পাঠান">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </form>
            </div>
        @elseif(!$stream->isLive())
            <div style="text-align: center; font-size: 12px; color: var(--fb-text-secondary); padding: 12px; background: var(--fb-card); border-top: 1px solid var(--fb-border);">
                লাইভ সম্প্রচার সমাপ্ত হওয়ায় নতুন মন্তব্য বন্ধ রয়েছে।
            </div>
        @else
            <div style="text-align: center; font-size: 12px; color: var(--fb-text-secondary); padding: 12px; background: var(--fb-card); border-top: 1px solid var(--fb-border);">
                ব্রডকাস্টার কর্তৃক এই লাইভের মন্তব্য অপশন বন্ধ রয়েছে।
            </div>
        @endif
    </div>
</div>

<!-- Professional Share Modal -->
<div class="live-modal-overlay" id="shareModal">
    <div class="live-modal-content">
        <div class="live-modal-header">
            <h3 class="live-modal-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" x2="12" y1="2" y2="15"/></svg>
                <span>লাইভ ভিডিও শেয়ার করুন</span>
            </h3>
            <button type="button" onclick="closeShareModal()" style="border:none;background:none;font-size:20px;cursor:pointer;color:var(--fb-text-secondary);">✕</button>
        </div>
        <p style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 16px; line-height: 1.5;">
            বন্ধুদের সাথে সরাসরি লিংক শেয়ার করুন অথবা আপনার সোশ্যাল প্রোফাইলে সম্প্রচারটি ছড়িয়ে দিন।
        </p>
        <div style="display: flex; gap: 8px; margin-bottom: 18px;">
            <input type="text" id="shareUrlInput" class="chat-text-input" value="{{ url()->current() }}" readonly style="border-radius: 8px;">
            <button type="button" class="btn-stream-action primary" onclick="copyShareUrl()" id="btnCopyUrl">কপি করুন</button>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn-stream-action" style="flex: 1; justify-content: center;" onclick="shareNative()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                <span>অন্যান্য অ্যাপে শেয়ার</span>
            </button>
        </div>
    </div>
</div>

<!-- Community Safety Report Modal -->
<div class="live-modal-overlay" id="reportModal">
    <div class="live-modal-content">
        <div class="live-modal-header">
            <h3 class="live-modal-title" style="color: #ef4444;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>লাইভ সম্প্রচার রিপোর্ট করুন</span>
            </h3>
            <button type="button" onclick="closeReportModal()" style="border:none;background:none;font-size:20px;cursor:pointer;color:var(--fb-text-secondary);">✕</button>
        </div>
        <form onsubmit="handleSendReport(event)">
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: var(--fb-text-primary);">রিপোর্টের সুনির্দিষ্ট কারণ:</label>
                <select id="reportReasonSelect" class="chat-text-input" style="width: 100%; border-radius: 8px;" required>
                    <option value="harassment">হয়রানি বা অবমাননাকর আচরণ (Harassment)</option>
                    <option value="violence">সহিংসতা বা রক্তপাত (Violence)</option>
                    <option value="sexual_content">যৌন উদ্দীপক বা অশ্লীল কন্টেন্ট (Sexual Content)</option>
                    <option value="hate">ঘৃণামূলক বক্তব্য (Hate Speech)</option>
                    <option value="spam">স্প্যাম বা প্রতারণা (Spam)</option>
                    <option value="copyright">কপিরাইট লঙ্ঘন (Copyright)</option>
                    <option value="other">অন্যান্য সমস্যা (Other)</option>
                </select>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: var(--fb-text-primary);">বিস্তারিত বিবরণ (ঐচ্ছিক):</label>
                <textarea id="reportDetailsInput" class="chat-text-input" rows="3" style="width: 100%; border-radius: 8px; resize: vertical;" placeholder="কেন এই লাইভটি কমিউনিটি গাইডলাইন লঙ্ঘন করছে উল্লেখ করুন..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-stream-action" onclick="closeReportModal()">বাতিল</button>
                <button type="submit" class="btn-stream-action primary">রিপোর্ট জমা দিন</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const STREAM_ID = {{ $stream->id }};
    const CHANNEL_ID = "{{ $stream->channel_id }}";
    const IS_LIVE = {{ $stream->isLive() ? 'true' : 'false' }};
    const CURRENT_USER_ID = {{ $currentUser ? $currentUser->id : 'null' }};
    const ICE_SERVERS = @json($iceServers);
    const SFU_ROOM_ID = "{{ $sfuRoomId ?? '' }}";
    const SFU_ENDPOINT = "{{ $sfuEndpoint ?? '' }}";
    let VIEWER_TOKEN = "{{ $viewerToken ?? '' }}";

    const SESSION_ID = 'viewer_' + Math.random().toString(36).substring(2, 15);
    const STARTED_AT_TIMESTAMP = {{ $stream->started_at ? $stream->started_at->timestamp : 'null' }};

    let peerConnection = null;
    let heartbeatTimer = null;
    let durationTimerInterval = null;
    let isUserScrolledUp = false;

    const liveVideoElement = document.getElementById('liveVideoElement');
    const currentViewerCountDisplay = document.getElementById('currentViewerCountDisplay');
    const viewerCommentsBox = document.getElementById('viewerCommentsBox');
    const emptyCommentNotice = document.getElementById('emptyCommentNotice');
    const viewerFloatingArea = document.getElementById('viewerFloatingArea');
    const scrollBottomBtn = document.getElementById('scrollBottomBtn');

    function getAuthHeaders() {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]')?.content;
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': tokenMeta || ''
        };
        const authToken = localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || (typeof currentToken !== 'undefined' ? currentToken : '');
        if (authToken) {
            headers['Authorization'] = `Bearer ${authToken}`;
        }
        return headers;
    }

    // 1. Viewer Join & Heartbeat Management
    async function initViewerLiveSession() {
        if (!IS_LIVE) return;

        // Start Duration counter
        startDurationTicker();

        try {
            // Join Presence in backend
            const joinRes = await fetch(`/api/v2/live/streams/${STREAM_ID}/join`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({ session_id: SESSION_ID })
            });
            const joinData = await joinRes.json();
            if (joinData.data && joinData.data.viewers_count) {
                currentViewerCountDisplay.innerText = joinData.data.viewers_count;
            }

            // Realtime presence heartbeat every 15s
            heartbeatTimer = setInterval(sendHeartbeat, 15000);

            // Setup WebRTC stream pipeline
            setupWebRtcViewer();

            // Setup Realtime Echo listeners
            setupRealtimeEcho();
        } catch (e) {
            console.warn('Init viewer session notice:', e);
        }
    }

    function startDurationTicker() {
        const timerEl = document.getElementById('liveDurationTimer');
        if (!timerEl) return;

        const startTime = STARTED_AT_TIMESTAMP ? STARTED_AT_TIMESTAMP * 1000 : Date.now();

        function updateTimer() {
            const elapsed = Math.max(0, Math.floor((Date.now() - startTime) / 1000));
            const hrs = Math.floor(elapsed / 3600);
            const mins = Math.floor((elapsed % 3600) / 60);
            const secs = elapsed % 60;
            if (hrs > 0) {
                timerEl.innerText = `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            } else {
                timerEl.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }
        }

        updateTimer();
        durationTimerInterval = setInterval(updateTimer, 1000);
    }

    async function sendHeartbeat() {
        try {
            const res = await fetch(`/api/v2/live/streams/${STREAM_ID}/heartbeat`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({ session_id: SESSION_ID })
            });
            const data = await res.json();
            if (data.data && data.data.viewers_count) {
                currentViewerCountDisplay.innerText = data.data.viewers_count;
            }
        } catch (e) {
            console.warn('Heartbeat notice:', e);
        }
    }

    // 2. WebRTC Media Receive Pipeline
    async function setupWebRtcViewer() {
        if (!liveVideoElement) return;

        if (!VIEWER_TOKEN) {
            try {
                const tokenRes = await fetch(`/api/v2/live/streams/${STREAM_ID}/viewer-token`, {
                    headers: getAuthHeaders()
                });
                const tokenData = await tokenRes.json();
                if (tokenData.status === 'success' && tokenData.data?.token) {
                    VIEWER_TOKEN = tokenData.data.token;
                }
            } catch (err) {
                console.warn('Viewer token notice:', err);
            }
        }

        try {
            peerConnection = new RTCPeerConnection({ iceServers: ICE_SERVERS });

            peerConnection.ontrack = (event) => {
                if (event.streams && event.streams[0]) {
                    liveVideoElement.srcObject = event.streams[0];
                }
            };

            peerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    sendSignal('candidate', event.candidate);
                }
            };

            peerConnection.addTransceiver('video', { direction: 'recvonly' });
            peerConnection.addTransceiver('audio', { direction: 'recvonly' });

            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            sendSignal('offer', offer);
        } catch (webrtcErr) {
            console.warn('WebRTC initialization notice:', webrtcErr);
        }
    }

    async function sendSignal(signalType, payload) {
        try {
            await fetch(`/api/v2/live/streams/${STREAM_ID}/signal`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({
                    signal_type: signalType,
                    payload: payload
                })
            });
        } catch (e) {
            console.warn('Send signal error:', e);
        }
    }

    // 3. Realtime Channel Subscriptions
    function setupRealtimeEcho() {
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel(`live.${CHANNEL_ID}`)
                .listen('.live.viewer.count', (e) => {
                    currentViewerCountDisplay.innerText = e.viewers_count || 0;
                })
                .listen('.live.comment.created', (e) => {
                    renderNewComment(e);
                })
                .listen('.live.comment.deleted', (e) => {
                    const el = document.getElementById(`comment_${e.comment_id}`);
                    if (el) el.remove();
                })
                .listen('.live.reaction.created', (e) => {
                    spawnReactionAnimation(e.reaction_type);
                })
                .listen('.live.signal', async (e) => {
                    if (e.target_user_id === CURRENT_USER_ID || !e.target_user_id) {
                        handleIncomingSignal(e);
                    }
                })
                .listen('.live.ended', (e) => {
                    handleStreamEndedNotice(e);
                })
                .listen('.live.replay.ready', (e) => {
                    handleReplayReadyNotice(e);
                });
        }
    }

    async function handleIncomingSignal(msg) {
        if (!peerConnection) return;
        if (msg.signal_type === 'answer' && peerConnection.signalingState !== 'stable') {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(msg.payload));
        } else if (msg.signal_type === 'candidate') {
            try {
                await peerConnection.addIceCandidate(new RTCIceCandidate(msg.payload));
            } catch (err) {
                console.warn('Candidate notice:', err);
            }
        }
    }

    function handleStreamEndedNotice(data) {
        clearInterval(heartbeatTimer);
        clearInterval(durationTimerInterval);
        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }

        const durationMinutes = data.duration_seconds ? Math.round(data.duration_seconds / 60) : 0;
        document.getElementById('videoBox').innerHTML = `
            <div style="text-align: center; color: white; padding: 48px 24px; max-width: 520px; margin: 0 auto;">
                <div style="width: 72px; height: 72px; background: rgba(239, 68, 68, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; border: 1px solid rgba(239, 68, 68, 0.3);">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                </div>
                <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 8px;">লাইভ সম্প্রচার সমাপ্ত হয়েছে</h2>
                <p style="font-size: 14px; opacity: 0.85; margin-bottom: 20px; line-height: 1.5;">
                    ব্রডকাস্টার এইমাত্র সম্প্রচার সমাপ্ত করেছেন ${durationMinutes > 0 ? '(' + durationMinutes + ' মিনিট স্থায়িত্ব)' : ''}। সম্পূর্ণ ভিডিওটি প্রসেস হয়ে স্থায়ীভাবে পোস্ট হবে।
                </p>
                <div id="endedReplayBox" style="margin-bottom: 18px;"></div>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <a href="{{ route('watch.index') }}" class="btn-stream-action primary">অন্যান্য লাইভ দেখুন</a>
                    <a href="{{ route('dashboard') }}" class="btn-stream-action">হোম ফিডে ফিরুন</a>
                </div>
            </div>
        `;
    }

    function handleReplayReadyNotice(data) {
        const replayBox = document.getElementById('endedReplayBox');
        if (replayBox && data.recording_url) {
            replayBox.innerHTML = `
                <div style="padding: 14px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 12px; margin-bottom: 14px;">
                    <div style="font-size: 14px; color: #34d399; font-weight: 800; margin-bottom: 6px;">✓ লাইভের পূর্ণাঙ্গ রেকর্ডিং প্রস্তুত!</div>
                    <a href="${data.post_url || '/dashboard'}" class="btn-stream-action primary" style="background: #10b981; border-color: #10b981;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <span>প্রকাশিত ভিডিও পোস্টে যান</span>
                    </a>
                </div>
            `;
        }
    }

    // 4. Live Chat Interactions
    async function handleSendComment(e) {
        e.preventDefault();
        const input = document.getElementById('commentMessageInput');
        const message = input.value.trim();
        if (!message) return;

        input.value = '';

        try {
            const res = await fetch(`/api/v2/live/streams/${STREAM_ID}/comments`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({ message: message })
            });

            const data = await res.json();
            if (data.status !== 'success') {
                alert(data.message || 'মন্তব্য পাঠাতে সমস্যা হয়েছে।');
            }
        } catch (err) {
            console.warn('Send comment error:', err);
        }
    }

    function renderNewComment(data) {
        if (emptyCommentNotice) emptyCommentNotice.style.display = 'none';

        const item = document.createElement('div');
        item.className = 'comment-bubble';
        item.id = `comment_${data.id}`;
        item.innerHTML = `
            <img src="${data.user.avatar_url || '/images/default-avatar.svg'}"
                 alt="${escapeHtml(data.user.name)}"
                 class="comment-user-avatar">
            <div class="comment-bubble-body">
                <div class="comment-bubble-top">
                    <span class="comment-author-name">
                        ${escapeHtml(data.user.name)}
                    </span>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span class="comment-time-text">এইমাত্র</span>
                        ${(CURRENT_USER_ID && CURRENT_USER_ID === data.user.id) ? `
                            <button type="button" class="btn-delete-comment" onclick="deleteLiveComment(${data.id})" title="মুছুন">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        ` : ''}
                    </div>
                </div>
                <div class="comment-bubble-text">${escapeHtml(data.message)}</div>
            </div>
        `;

        viewerCommentsBox.appendChild(item);

        if (!isUserScrolledUp) {
            scrollToChatBottom();
        } else if (scrollBottomBtn) {
            scrollBottomBtn.style.display = 'inline-flex';
        }
    }

    function handleChatScroll() {
        if (!viewerCommentsBox) return;
        const threshold = 60;
        const atBottom = viewerCommentsBox.scrollHeight - viewerCommentsBox.scrollTop - viewerCommentsBox.clientHeight <= threshold;
        isUserScrolledUp = !atBottom;
        if (scrollBottomBtn) {
            scrollBottomBtn.style.display = atBottom ? 'none' : 'inline-flex';
        }
    }

    function scrollToChatBottom() {
        if (viewerCommentsBox) {
            viewerCommentsBox.scrollTop = viewerCommentsBox.scrollHeight;
            isUserScrolledUp = false;
            if (scrollBottomBtn) scrollBottomBtn.style.display = 'none';
        }
    }

    async function deleteLiveComment(commentId) {
        if (!confirm('এই মন্তব্যটি মুছে ফেলতে চান?')) return;
        try {
            await fetch(`/api/v2/live/streams/${STREAM_ID}/comments/${commentId}`, {
                method: 'DELETE',
                headers: getAuthHeaders()
            });
            const el = document.getElementById(`comment_${commentId}`);
            if (el) el.remove();
        } catch (e) {
            alert('ত্রুটি: ' + e.message);
        }
    }

    // 5. Reactions Engine
    async function sendReaction(type) {
        spawnReactionAnimation(type);

        if (!CURRENT_USER_ID) return;

        try {
            await fetch(`/api/v2/live/streams/${STREAM_ID}/reactions`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({ reaction_type: type })
            });
        } catch (e) {
            console.warn('Reaction error:', e);
        }
    }

    function spawnReactionAnimation(type) {
        const emojiMap = { like: '👍', love: '❤️', care: '🥰', haha: '😆', wow: '😮', sad: '😢', angry: '😡' };
        const emoji = emojiMap[type] || '❤️';
        const span = document.createElement('span');
        span.className = 'viewer-floating-emoji';
        span.innerText = emoji;
        span.style.left = (Math.random() * 60) + 'px';
        viewerFloatingArea.appendChild(span);
        setTimeout(() => span.remove(), 2600);
    }

    // 6. Custom Video Controls Handlers
    function togglePlayPause() {
        if (!liveVideoElement) return;
        const icon = document.getElementById('playPauseIcon');
        if (liveVideoElement.paused) {
            liveVideoElement.play();
            if (icon) icon.innerHTML = '<rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>';
        } else {
            liveVideoElement.pause();
            if (icon) icon.innerHTML = '<polygon points="5 3 19 12 5 21 5 3"/>';
        }
    }

    function toggleMute() {
        if (!liveVideoElement) return;
        const icon = document.getElementById('muteIcon');
        liveVideoElement.muted = !liveVideoElement.muted;
        if (liveVideoElement.muted) {
            if (icon) icon.innerHTML = '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/>';
        } else {
            if (icon) icon.innerHTML = '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>';
        }
    }

    function changeVolume(val) {
        if (!liveVideoElement) return;
        liveVideoElement.volume = parseFloat(val);
        liveVideoElement.muted = (parseFloat(val) === 0);
    }

    function toggleFullscreen() {
        const box = document.getElementById('videoBox');
        if (!document.fullscreenElement) {
            if (box.requestFullscreen) box.requestFullscreen();
            else if (box.webkitRequestFullscreen) box.webkitRequestFullscreen();
        } else {
            if (document.exitFullscreen) document.exitFullscreen();
        }
    }

    // 7. Share & Report Modals
    function openShareModal() {
        document.getElementById('shareModal').style.display = 'flex';
    }

    function closeShareModal() {
        document.getElementById('shareModal').style.display = 'none';
    }

    function copyShareUrl() {
        const copyText = document.getElementById('shareUrlInput');
        copyText.select();
        navigator.clipboard.writeText(copyText.value);

        const btn = document.getElementById('btnCopyUrl');
        if (btn) {
            const originalText = btn.innerText;
            btn.innerText = '✓ কপি সম্পন্ন!';
            setTimeout(() => { btn.innerText = originalText; }, 2000);
        }

        fetch(`/api/v2/live/streams/${STREAM_ID}/share`, {
            method: 'POST',
            headers: getAuthHeaders(),
            body: JSON.stringify({ destination: 'clipboard' })
        });
    }

    async function shareNative() {
        if (navigator.share) {
            try {
                await navigator.share({
                    title: "{{ $stream->title }}",
                    text: "{{ $stream->user->name }}-এর লাইভ ভিডিও সম্প্রচার দেখুন",
                    url: window.location.href
                });

                fetch(`/api/v2/live/streams/${STREAM_ID}/share`, {
                    method: 'POST',
                    headers: getAuthHeaders(),
                    body: JSON.stringify({ destination: 'native_share' })
                });
            } catch (err) {
                console.warn(err);
            }
        } else {
            copyShareUrl();
        }
    }

    function openReportModal() {
        document.getElementById('reportModal').style.display = 'flex';
    }

    function closeReportModal() {
        document.getElementById('reportModal').style.display = 'none';
    }

    async function handleSendReport(e) {
        e.preventDefault();
        const reason = document.getElementById('reportReasonSelect').value;
        const details = document.getElementById('reportDetailsInput').value.trim();

        try {
            const res = await fetch(`/api/v2/live/streams/${STREAM_ID}/report`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({ reason, details })
            });

            const data = await res.json();
            alert(data.message || 'রিপোর্ট সফলভাবে জমা নেওয়া হয়েছে।');
            closeReportModal();
        } catch (e) {
            alert('রিপোর্ট পাঠাতে ত্রুটি: ' + e.message);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    // Window Events
    window.addEventListener('DOMContentLoaded', () => {
        initViewerLiveSession();
        scrollToChatBottom();
    });

    window.addEventListener('beforeunload', () => {
        clearInterval(heartbeatTimer);
        clearInterval(durationTimerInterval);
        const data = JSON.stringify({ session_id: SESSION_ID });
        navigator.sendBeacon(`/api/v2/live/streams/${STREAM_ID}/leave`, data);
        if (peerConnection) peerConnection.close();
    });
</script>
@endsection
