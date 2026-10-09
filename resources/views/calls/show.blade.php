<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>{{ $callType === 'video' ? 'ভিডিও কল' : 'অডিও কল' }} — {{ $peerUser?->name ?? 'Bondhoo কল' }}</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/bondhoo-favicon.png">
    <link rel="apple-touch-icon" href="/images/bondhoo-icon-192.png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --call-bg: #0b0f19;
            --call-surface: rgba(30, 41, 59, 0.82);
            --call-border: rgba(255, 255, 255, 0.12);
            --call-primary: #1877f2;
            --call-success: #10b981;
            --call-danger: #ef4444;
            --call-warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            user-select: none;
            font-family: 'Hind Siliguri', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body, html {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: var(--call-bg);
            color: #ffffff;
        }

        .call-room {
            position: relative;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: radial-gradient(circle at 50% 20%, #1e293b 0%, #0b0f19 80%);
            overflow: hidden;
        }

        /* Top Bar */
        .call-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            padding: calc(14px + env(safe-area-inset-top, 0px)) calc(20px + env(safe-area-inset-right, 0px)) 14px calc(20px + env(safe-area-inset-left, 0px));
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(180deg, rgba(11, 15, 25, 0.9) 0%, rgba(11, 15, 25, 0.4) 75%, transparent 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .peer-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .peer-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.25);
            background: linear-gradient(135deg, #1877f2, #00c6ff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
        }

        .peer-text h2 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .peer-status {
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 2px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--call-warning);
            box-shadow: 0 0 8px var(--call-warning);
            animation: pulseDot 1.5s infinite;
        }

        .status-dot.connected {
            background-color: var(--call-success);
            box-shadow: 0 0 8px var(--call-success);
            animation: none;
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.2); }
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .network-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--call-border);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(6px);
            transition: all 0.3s ease;
        }

        .call-timer-badge {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--call-border);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: none;
        }

        /* Central Media Viewport */
        .call-viewport {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #000;
        }

        /* Video Elements */
        video.remote-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            background: #000;
        }

        /* Draggable Picture-in-Picture Local Video */
        .local-video-pip {
            position: absolute;
            top: calc(80px + env(safe-area-inset-top, 0px));
            right: calc(24px + env(safe-area-inset-right, 0px));
            width: 160px;
            height: 220px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 32px rgba(0, 0, 0, 0.65);
            border: 2px solid rgba(255, 255, 255, 0.25);
            z-index: 45;
            background: #1e293b;
            display: none;
            cursor: grab;
            touch-action: none;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .local-video-pip:active {
            cursor: grabbing;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.85);
            border-color: rgba(24, 119, 242, 0.6);
        }

        .local-video-pip video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1); /* Mirror local video by default */
            display: block;
        }

        .local-cam-off-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #0f172a;
            color: #94a3b8;
            gap: 6px;
            z-index: 1;
        }

        .pip-label {
            position: absolute;
            bottom: 8px;
            left: 8px;
            right: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0, 0, 0, 0.68);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            color: #ffffff;
            z-index: 2;
            pointer-events: auto;
        }

        .pip-drag-handle {
            opacity: 0.7;
            font-size: 10px;
            margin-right: 4px;
            cursor: grab;
        }

        .pip-action-btn {
            background: rgba(255, 255, 255, 0.22);
            border: none;
            color: #ffffff;
            border-radius: 4px;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .pip-action-btn:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: scale(1.05);
        }

        .pip-action-btn:active {
            transform: scale(0.95);
        }

        /* Audio Mode Visualizer */
        .audio-visualizer-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 20;
            gap: 24px;
        }

        .audio-pulse-avatar-wrapper {
            position: relative;
            width: 160px;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .audio-pulse-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid rgba(24, 119, 242, 0.4);
            animation: pulseRing 2.4s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }

        .audio-pulse-ring:nth-child(2) {
            animation-delay: 0.8s;
        }

        .audio-pulse-ring:nth-child(3) {
            animation-delay: 1.6s;
        }

        @keyframes pulseRing {
            0% {
                transform: scale(0.9);
                opacity: 0.8;
            }
            100% {
                transform: scale(2.2);
                opacity: 0;
            }
        }

        .audio-big-avatar {
            position: relative;
            z-index: 5;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.25);
            background: linear-gradient(135deg, #1877f2, #00c6ff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 54px;
            font-weight: 800;
            box-shadow: 0 0 40px rgba(24, 119, 242, 0.45);
        }

        .audio-wave-bars {
            display: flex;
            gap: 6px;
            height: 36px;
            align-items: center;
        }

        .wave-bar {
            width: 5px;
            background: #1877f2;
            border-radius: 4px;
            height: 10px;
            animation: waveBounce 1.2s ease-in-out infinite;
        }

        .wave-bar:nth-child(2) { animation-delay: 0.2s; height: 18px; }
        .wave-bar:nth-child(3) { animation-delay: 0.4s; height: 28px; }
        .wave-bar:nth-child(4) { animation-delay: 0.6s; height: 20px; }
        .wave-bar:nth-child(5) { animation-delay: 0.3s; height: 12px; }

        @keyframes waveBounce {
            0%, 100% { transform: scaleY(0.4); }
            50% { transform: scaleY(1.4); }
        }

        /* Floating Bottom Control Dock */
        .call-controls-dock {
            position: absolute;
            bottom: calc(28px + env(safe-area-inset-bottom, 0px));
            left: 50%;
            transform: translateX(-50%);
            z-index: 50;
            display: flex;
            align-items: center;
            gap: 16px;
            background: var(--call-surface);
            padding: 12px 24px;
            border-radius: 40px;
            border: 1px solid var(--call-border);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.55);
        }

        .control-btn {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        .control-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .control-btn:active {
            transform: scale(0.95);
        }

        .control-btn.active {
            background: #1877f2 !important;
            color: #ffffff !important;
            box-shadow: 0 0 16px rgba(24, 119, 242, 0.5);
        }

        .control-btn.muted, .control-btn.disabled {
            background: #ef4444 !important;
            color: #ffffff !important;
        }

        .control-btn.hangup {
            background: #ef4444;
            width: 60px;
            height: 60px;
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.5);
        }

        .control-btn.hangup:hover {
            background: #dc2626;
            transform: translateY(-2px) scale(1.05);
        }

        /* Toast notifications */
        .call-toast {
            position: absolute;
            top: calc(76px + env(safe-area-inset-top, 0px));
            left: 50%;
            transform: translateX(-50%);
            z-index: 65;
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid var(--call-border);
            padding: 10px 22px;
            border-radius: 30px;
            font-size: 13.5px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            display: none;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
            animation: fadeInDown 0.3s ease;
            white-space: nowrap;
            max-width: 90vw;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translate(-50%, -10px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }

        @media (max-width: 640px) {
            .call-header {
                padding: calc(10px + env(safe-area-inset-top, 0px)) 14px 10px 14px;
            }
            .peer-avatar {
                width: 38px;
                height: 38px;
                font-size: 16px;
            }
            .peer-text h2 {
                font-size: 14.5px;
            }
            .peer-status {
                font-size: 12px;
            }
            .network-badge {
                padding: 4px 9px;
                font-size: 11px;
            }
            .call-controls-dock {
                bottom: calc(18px + env(safe-area-inset-bottom, 0px));
                padding: 10px 18px;
                gap: 12px;
            }
            .control-btn {
                width: 46px;
                height: 46px;
            }
            .control-btn.hangup {
                width: 52px;
                height: 52px;
            }
            .local-video-pip {
                top: calc(72px + env(safe-area-inset-top, 0px));
                right: calc(16px + env(safe-area-inset-right, 0px));
                width: 110px;
                height: 154px;
                border-radius: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="call-room">
        <!-- Toast Banner -->
        <div id="callToast" class="call-toast"></div>

        <!-- Top Bar -->
        <div class="call-header">
            <div class="peer-info">
                @if($peerUser && $peerUser->profile?->avatar_url)
                    <img src="{{ $peerUser->profile->avatar_url }}" alt="{{ $peerUser->name }}" class="peer-avatar">
                @else
                    <div class="peer-avatar">{{ mb_substr($peerUser?->name ?? 'য', 0, 1) }}</div>
                @endif
                <div class="peer-text">
                    <h2>
                        {{ $peerUser?->name ?? ($conversation->isDirect() ? 'Bondhoo ব্যবহারকারী' : ($conversation->title ?: 'গ্রুপ কল')) }}
                    </h2>
                    <div class="peer-status">
                        <span id="statusDot" class="status-dot"></span>
                        <span id="callStatusText">কল রিং হচ্ছে...</span>
                    </div>
                </div>
            </div>

            <div class="header-actions">
                <!-- Network Quality Indicator Badge -->
                <div id="networkQualityBadge" class="network-badge" title="নেটওয়ার্ক গুণমান">
                    <span id="netSignalBars">📶</span>
                    <span id="netQualityText" style="color: #10b981;">উত্তম</span>
                </div>
                <div id="callTimerBadge" class="call-timer-badge">00:00</div>
                <button type="button" id="btnFullscreen" class="control-btn" style="width: 40px; height: 40px;" onclick="toggleFullscreen()" title="ফুলস্ক্রিন">
                    <svg id="iconEnterFs" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                    <svg id="iconExitFs" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;"><path d="M4 14h6m0 0v6m0-6L3 21m17-7h-6m0 0v6m0-6 7 7M4 10h6m0 0V4m0 6L3 3m17 7h-6m0 0V4m0 6 7-7"/></svg>
                </button>
            </div>
        </div>

        <!-- Central Viewport -->
        <div class="call-viewport">
            <!-- Remote Video Stream -->
            <video id="remoteVideo" class="remote-video" autoplay playsinline muted></video>

            <!-- Local Video Picture-in-Picture (Default: Top-Right, Draggable) -->
            <div id="localVideoContainer" class="local-video-pip" title="টেনে অন্য স্থানে স্থানান্তর করতে পারেন (ডাবল-ক্লিক করে ডিফল্টে ফেরান)">
                <video id="localVideo" autoplay playsinline muted></video>
                <div id="localCamOffOverlay" class="local-cam-off-overlay" style="display: none;">
                    <div style="font-size: 26px;">📷</div>
                    <span style="font-size: 11px; font-weight: 600; color: #94a3b8;">ক্যামেরা বন্ধ</span>
                </div>
                <div class="pip-label">
                    <span style="display: flex; align-items: center; gap: 4px;">
                        <span class="pip-drag-handle">⋮⋮</span>
                        <span>আপনি</span>
                    </span>
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <button type="button" id="btnFlipCam" class="pip-action-btn" onclick="flipCamera(event)" title="ক্যামেরা পরিবর্তন" style="display: none;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 10c0-4.4-3.6-8-8-8s-8 3.6-8 8h3l-4 5-4-5h3c0-5.5 4.5-10 10-10s10 4.5 10 10h-2Z"/></svg>
                        </button>
                        <button type="button" id="btnResetPip" class="pip-action-btn" onclick="resetPipPosition(event)" title="ডিফল্ট পজিশনে ফেরান">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Audio Mode Visualizer / Ringing Placeholder -->
            <div id="audioVisualizer" class="audio-visualizer-container">
                <div class="audio-pulse-avatar-wrapper">
                    <div class="audio-pulse-ring"></div>
                    <div class="audio-pulse-ring"></div>
                    <div class="audio-pulse-ring"></div>
                    @if($peerUser && $peerUser->profile?->avatar_url)
                        <img src="{{ $peerUser->profile->avatar_url }}" alt="{{ $peerUser->name }}" class="audio-big-avatar">
                    @else
                        <div class="audio-big-avatar">{{ mb_substr($peerUser?->name ?? 'য', 0, 1) }}</div>
                    @endif
                </div>

                <div class="audio-wave-bars">
                    <div class="wave-bar"></div>
                    <div class="wave-bar"></div>
                    <div class="wave-bar"></div>
                    <div class="wave-bar"></div>
                    <div class="wave-bar"></div>
                </div>

                <div id="audioVisualizerStatus" style="font-size: 14px; font-weight: 600; color: #94a3b8; text-align: center; margin-top: -6px;">কল সংযোগ হচ্ছে...</div>
            </div>
        </div>

        <!-- Floating Bottom Dock Controls -->
        <div class="call-controls-dock">
            <!-- Mic Toggle -->
            <button type="button" id="btnToggleMic" class="control-btn" onclick="toggleMicrophone()" title="মাইক্রোফোন বন্ধ/চালু">
                <svg id="micIconOn" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
                <svg id="micIconOff" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;"><line x1="2" x2="22" y1="2" y2="22"/><path d="M18.89 13.23A7.12 7.12 0 0 0 19 12v-2"/><path d="M5 10v2a7 7 0 0 0 12 5"/><path d="M15 9.34V5a3 3 0 0 0-5.68-1.33"/><path d="M9 9v3a3 3 0 0 0 5.12 2.12"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
            </button>

            <!-- Camera Toggle -->
            <button type="button" id="btnToggleCam" class="control-btn" onclick="toggleCamera()" title="ক্যামেরা বন্ধ/চালু">
                <svg id="camIconOn" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2" ry="2"/></svg>
                <svg id="camIconOff" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;"><line x1="2" x2="22" y1="2" y2="22"/><path d="m22 8-6 4 6 4V8Z"/><path d="M14 6H4a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2Z"/></svg>
            </button>

            <!-- Screen Share -->
            <button type="button" id="btnToggleScreen" class="control-btn" onclick="toggleScreenShare()" title="স্ক্রিন শেয়ার">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
            </button>

            <!-- End Call / Hang Up -->
            <button type="button" id="btnEndCall" class="control-btn hangup" onclick="hangUpCall()" title="কল শেষ করুন">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.42 19.42 0 0 1-3.33-2.67m-2.67-3.34a19.79 19.79 0 0 1-3.07-8.63A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
            </button>
        </div>
    </div>

    <!-- Hidden audio element for remote stream playback -->
    <audio id="remoteAudio" autoplay playsinline style="display: none;"></audio>

    <script>
        const CONVERSATION_ID = {{ $conversation->id }};
        const CURRENT_USER_ID = {{ $currentUser->id }};
        const INITIAL_CALL_TYPE = '{{ $callType }}';
        const CSRF_TOKEN = '{{ csrf_token() }}';
        const ICE_SERVERS = @json($iceServers);
        const ANSWER_CALL_ID = @json($answerCallId ?? null);
        const PEER_USER_ID = @json($peerUser?->id ?? null);
        const SERVER_AUTH_TOKEN = @json($apiToken ?? null);
        if (SERVER_AUTH_TOKEN) {
            try { localStorage.setItem('jugajug_token', SERVER_AUTH_TOKEN); } catch (e) {}
        }

        let activeCallId = null;
        let peerConnection = null;
        let localStream = null;
        let remoteStream = null;
        let screenStream = null;
        let callTimerInterval = null;
        let callDurationSecs = 0;
        let isAudioMuted = false;
        let isVideoMuted = (INITIAL_CALL_TYPE === 'audio');
        let isScreenSharing = false;
        let isCallAnswered = false;
        let lastSyncEventId = {{ (int) ($syncCursor ?? 0) }};
        let syncPollTimer = null;
        let ringtoneAudioContext = null;
        let ringtoneTimeout = null;
        let currentFacingMode = 'user';
        let availableVideoDevices = [];
        let currentDeviceIndex = 0;
        let isFlippingCam = false;
        let netStatsInterval = null;
        let isLowBitrateMode = false;
        let pipWasDragged = false;
        let pipPointerId = null;
        let pipStartX = 0;
        let pipStartY = 0;
        let pipInitLeft = 0;
        let pipInitTop = 0;

        window.__callDiagnostics = {
            getActiveCallId: () => activeCallId,
            getPeerConnection: () => peerConnection,
            getLocalStream: () => localStream,
            getRemoteStream: () => remoteStream,
            isAnswered: () => isCallAnswered,
            isEnded: () => callHasEnded
        };

        // 1. Toast Notification Helper
        function showToast(msg) {
            const el = document.getElementById('callToast');
            if (el) {
                el.innerText = msg;
                el.style.display = 'block';
                setTimeout(() => { el.style.display = 'none'; }, 3500);
            }
        }

        // 2. Pure Web Audio API Synthesized Ringtone Engine
        function startRingbackTone() {
            try {
                if (ringtoneAudioContext) return;
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                ringtoneAudioContext = new AudioCtx();

                function playBurst() {
                    if (!ringtoneAudioContext || isCallAnswered) return;

                    const osc1 = ringtoneAudioContext.createOscillator();
                    const osc2 = ringtoneAudioContext.createOscillator();
                    const gain = ringtoneAudioContext.createGain();

                    osc1.type = 'sine';
                    osc1.frequency.setValueAtTime(440, ringtoneAudioContext.currentTime);
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(480, ringtoneAudioContext.currentTime);

                    gain.gain.setValueAtTime(0.08, ringtoneAudioContext.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ringtoneAudioContext.currentTime + 1.8);

                    osc1.connect(gain);
                    osc2.connect(gain);
                    gain.connect(ringtoneAudioContext.destination);

                    osc1.start();
                    osc2.start();
                    osc1.stop(ringtoneAudioContext.currentTime + 1.8);
                    osc2.stop(ringtoneAudioContext.currentTime + 1.8);

                    ringtoneTimeout = setTimeout(playBurst, 4000);
                }

                playBurst();
            } catch (e) {
                console.warn('[WebAudio] Ringback tone init error:', e);
            }
        }

        function stopRingbackTone() {
            clearTimeout(ringtoneTimeout);
            if (ringtoneAudioContext) {
                try { ringtoneAudioContext.close(); } catch (e) {}
                ringtoneAudioContext = null;
            }
        }

        function playHangupTone() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(480, ctx.currentTime);
                gain.gain.setValueAtTime(0.12, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.5);
                setTimeout(() => { ctx.close(); }, 600);
            } catch (e) {}
        }

        // 3. Media Controls
        function toggleMicrophone() {
            if (!localStream) return;
            const audioTrack = localStream.getAudioTracks()[0];
            if (!audioTrack) return;

            isAudioMuted = !isAudioMuted;
            audioTrack.enabled = !isAudioMuted;

            const btn = document.getElementById('btnToggleMic');
            const iconOn = document.getElementById('micIconOn');
            const iconOff = document.getElementById('micIconOff');

            if (isAudioMuted) {
                btn.classList.add('muted');
                iconOn.style.display = 'none';
                iconOff.style.display = 'block';
                showToast('মাইক্রোফোন মিউট করা হয়েছে');
            } else {
                btn.classList.remove('muted');
                iconOn.style.display = 'block';
                iconOff.style.display = 'none';
                showToast('মাইক্রোফোন চালু করা হয়েছে');
            }

            if (activeCallId) {
                apiFetch(`/api/v1/calls/${activeCallId}/state`, {
                    method: 'POST',
                    body: JSON.stringify({ is_muted: isAudioMuted })
                }).catch(() => {});
            }
        }

        async function toggleCamera() {
            if (!localStream) return;
            const videoTrack = localStream.getVideoTracks()[0];
            const btn = document.getElementById('btnToggleCam');
            const iconOn = document.getElementById('camIconOn');
            const iconOff = document.getElementById('camIconOff');
            const pip = document.getElementById('localVideoContainer');
            const localVid = document.getElementById('localVideo');
            const overlay = document.getElementById('localCamOffOverlay');

            if (videoTrack) {
                isVideoMuted = !isVideoMuted;
                videoTrack.enabled = !isVideoMuted;

                if (isVideoMuted) {
                    btn.classList.add('muted');
                    iconOn.style.display = 'none';
                    iconOff.style.display = 'block';
                    if (overlay) overlay.style.display = 'flex';
                    showToast('ক্যামেরা বন্ধ করা হয়েছে');
                } else {
                    btn.classList.remove('muted');
                    iconOn.style.display = 'block';
                    iconOff.style.display = 'none';
                    if (overlay) overlay.style.display = 'none';
                    if (localVid) localVid.play().catch(() => {});
                    showToast('ক্যামেরা চালু করা হয়েছে');
                }

                if (activeCallId) {
                    apiFetch(`/api/v1/calls/${activeCallId}/state`, {
                        method: 'POST',
                        body: JSON.stringify({ is_camera_off: isVideoMuted })
                    }).catch(() => {});
                }
            } else {
                // If initial call was audio, request camera dynamically and renegotiate
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: { width: { ideal: 1280, max: 1920 }, height: { ideal: 720, max: 1080 }, facingMode: 'user' }
                    });
                    const newTrack = stream.getVideoTracks()[0];
                    localStream.addTrack(newTrack);

                    localVid.srcObject = localStream;
                    localVid.muted = true;
                    localVid.play().catch(() => {});
                    pip.style.display = 'block';
                    if (overlay) overlay.style.display = 'none';
                    btn.classList.remove('muted');
                    iconOn.style.display = 'block';
                    iconOff.style.display = 'none';
                    isVideoMuted = false;
                    showToast('ভিডিও ক্যামেরা চালু হয়েছে');

                    if (peerConnection) {
                        const videoSender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (videoSender) {
                            await videoSender.replaceTrack(newTrack);
                        } else {
                            peerConnection.addTrack(newTrack, localStream);
                            if (peerConnection.signalingState === 'stable') {
                                const offer = await peerConnection.createOffer();
                                const cleanSdp = normalizeSdp(offer.sdp);
                                await peerConnection.setLocalDescription(new RTCSessionDescription({ type: 'offer', sdp: cleanSdp }));
                                await sendSignal('offer', { type: 'offer', sdp: cleanSdp });
                            }
                        }
                    }

                    checkMultipleCameras();

                    if (activeCallId) {
                        apiFetch(`/api/v1/calls/${activeCallId}/state`, {
                            method: 'POST',
                            body: JSON.stringify({ is_camera_off: false })
                        }).catch(() => {});
                    }
                } catch (e) {
                    showToast('ক্যামেরা চালু করা সম্ভব হয়নি: ' + (e.message || e.name));
                }
            }
        }

        // Front/Rear Camera Switching with Fallbacks
        async function flipCamera(e) {
            if (e) {
                e.stopPropagation();
                e.preventDefault();
            }
            if (pipWasDragged) return;
            if (isFlippingCam) return;
            if (!localStream) return;

            const currentTrack = localStream.getVideoTracks()[0];
            if (!currentTrack) {
                showToast('ক্যামেরা চালু নেই');
                return;
            }

            isFlippingCam = true;
            showToast('ক্যামেরা পরিবর্তন করা হচ্ছে...');

            currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
            let newStream = null;

            // 1st attempt: facingMode ideal constraint
            try {
                newStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: currentFacingMode },
                        width: { ideal: 1280, max: 1920 },
                        height: { ideal: 720, max: 1080 }
                    }
                });
            } catch (err1) {
                console.warn('[WebRTC] facingMode switch error, trying deviceId fallback:', err1);
                // 2nd attempt: select next deviceId
                if (availableVideoDevices.length > 1) {
                    currentDeviceIndex = (currentDeviceIndex + 1) % availableVideoDevices.length;
                    const targetDevice = availableVideoDevices[currentDeviceIndex];
                    try {
                        newStream = await navigator.mediaDevices.getUserMedia({
                            video: { deviceId: { exact: targetDevice.deviceId } }
                        });
                    } catch (err2) {
                        console.warn('[WebRTC] deviceId switch error:', err2);
                    }
                }
            }

            if (!newStream) {
                currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
                showToast('ক্যামেরা পরিবর্তন সম্ভব হয়নি');
                isFlippingCam = false;
                return;
            }

            const newTrack = newStream.getVideoTracks()[0];
            if (!newTrack) {
                newStream.getTracks().forEach(t => t.stop());
                isFlippingCam = false;
                return;
            }

            try {
                currentTrack.stop();
                localStream.removeTrack(currentTrack);
                localStream.addTrack(newTrack);

                const localVid = document.getElementById('localVideo');
                if (localVid) {
                    localVid.srcObject = localStream;
                    localVid.style.transform = (currentFacingMode === 'user') ? 'scaleX(-1)' : 'scaleX(1)';
                    localVid.play().catch(() => {});
                }

                if (peerConnection) {
                    const sender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (sender) {
                        await sender.replaceTrack(newTrack);
                    }
                }

                showToast(currentFacingMode === 'environment' ? 'ব্যাক ক্যামেরা চালু হয়েছে' : 'ফ্রন্ট ক্যামেরা চালু হয়েছে');
            } catch (applyErr) {
                console.error('[WebRTC] Error applying flipped camera track:', applyErr);
                showToast('ক্যামেরা পরিবর্তনে সমস্যা হয়েছে');
            } finally {
                isFlippingCam = false;
            }
        }

        async function checkMultipleCameras() {
            try {
                if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    availableVideoDevices = devices.filter(d => d.kind === 'videoinput');
                    const flipBtn = document.getElementById('btnFlipCam');
                    if (flipBtn) {
                        if (availableVideoDevices.length > 1) {
                            flipBtn.style.display = 'flex';
                        } else {
                            flipBtn.style.display = 'none';
                        }
                    }
                }
            } catch (e) {
                console.warn('[WebRTC] enumerateDevices error:', e);
            }
        }

        // 4. Draggable Picture-in-Picture Local Video
        function initDraggablePip() {
            const pip = document.getElementById('localVideoContainer');
            if (!pip) return;

            // Restore saved position if valid
            try {
                const saved = localStorage.getItem('bondhoo_pip_pos');
                if (saved) {
                    const pos = JSON.parse(saved);
                    if (typeof pos.xPct === 'number' && typeof pos.yPct === 'number') {
                        const targetLeft = Math.round(pos.xPct * window.innerWidth);
                        const targetTop = Math.round(pos.yPct * window.innerHeight);
                        applyClampedPipPosition(targetLeft, targetTop, false);
                    }
                }
            } catch (e) {}

            pip.addEventListener('pointerdown', (e) => {
                if (e.target.closest('button')) return;

                pipPointerId = e.pointerId;
                pip.setPointerCapture(e.pointerId);
                pipStartX = e.clientX;
                pipStartY = e.clientY;

                const rect = pip.getBoundingClientRect();
                pipInitLeft = rect.left;
                pipInitTop = rect.top;
                pipWasDragged = false;
                pip.style.transition = 'none';
            });

            pip.addEventListener('pointermove', (e) => {
                if (pipPointerId !== e.pointerId) return;

                const dx = e.clientX - pipStartX;
                const dy = e.clientY - pipStartY;

                if (Math.hypot(dx, dy) > 5) {
                    pipWasDragged = true;
                }

                const newLeft = pipInitLeft + dx;
                const newTop = pipInitTop + dy;
                applyClampedPipPosition(newLeft, newTop, false);
            });

            const endDrag = (e) => {
                if (pipPointerId === e.pointerId) {
                    try { pip.releasePointerCapture(e.pointerId); } catch (err) {}
                    pipPointerId = null;
                    pip.style.transition = 'all 0.25s ease';

                    if (pipWasDragged) {
                        const rect = pip.getBoundingClientRect();
                        try {
                            localStorage.setItem('bondhoo_pip_pos', JSON.stringify({
                                xPct: rect.left / window.innerWidth,
                                yPct: rect.top / window.innerHeight
                            }));
                        } catch (err) {}
                    }
                    setTimeout(() => { pipWasDragged = false; }, 80);
                }
            };

            pip.addEventListener('pointerup', endDrag);
            pip.addEventListener('pointercancel', endDrag);

            // Double click to restore top-right
            pip.addEventListener('dblclick', (e) => {
                e.stopPropagation();
                resetPipPosition();
            });

            window.addEventListener('resize', () => {
                const rect = pip.getBoundingClientRect();
                applyClampedPipPosition(rect.left, rect.top, true);
            });
        }

        function applyClampedPipPosition(left, top, animate = false) {
            const pip = document.getElementById('localVideoContainer');
            if (!pip) return;

            const pipWidth = pip.offsetWidth || 160;
            const pipHeight = pip.offsetHeight || 220;

            const header = document.querySelector('.call-header');
            const dock = document.querySelector('.call-controls-dock');

            const minX = 12;
            const maxX = Math.max(minX, window.innerWidth - pipWidth - 12);
            const minY = header ? (header.offsetHeight + 10) : 70;
            const maxY = dock ? (dock.offsetTop - pipHeight - 12) : Math.max(minY, window.innerHeight - pipHeight - 96);

            const clampX = Math.max(minX, Math.min(maxX, left));
            const clampY = Math.max(minY, Math.min(maxY, top));

            pip.style.left = `${clampX}px`;
            pip.style.top = `${clampY}px`;
            pip.style.right = 'auto';
            pip.style.bottom = 'auto';

            if (animate) {
                pip.style.transition = 'all 0.25s ease';
            }
        }

        function resetPipPosition(e) {
            if (e) {
                e.stopPropagation();
                e.preventDefault();
            }
            const pip = document.getElementById('localVideoContainer');
            if (!pip) return;

            try { localStorage.removeItem('bondhoo_pip_pos'); } catch (err) {}

            pip.style.transition = 'all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1)';
            const pipWidth = pip.offsetWidth || 160;
            const defaultLeft = window.innerWidth - pipWidth - (window.innerWidth < 640 ? 16 : 24);
            const defaultTop = window.innerWidth < 640 ? 72 : 80;

            applyClampedPipPosition(defaultLeft, defaultTop, true);
            showToast('পজিশন রিসেট করা হয়েছে (টপ-রাইট)');
        }

        // 5. Screen Share
        async function toggleScreenShare() {
            if (!isScreenSharing) {
                try {
                    screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    const screenTrack = screenStream.getVideoTracks()[0];
                    const sender = peerConnection?.getSenders().find(s => s.track?.kind === 'video');

                    if (sender) {
                        await sender.replaceTrack(screenTrack);
                    }

                    screenTrack.onended = () => {
                        stopScreenShare();
                    };

                    isScreenSharing = true;
                    document.getElementById('btnToggleScreen')?.classList.add('active');
                    showToast('স্ক্রিন শেয়ার চালু হয়েছে');

                    if (activeCallId) {
                        apiFetch(`/api/v1/calls/${activeCallId}/state`, {
                            method: 'POST',
                            body: JSON.stringify({ is_screen_sharing: true })
                        }).catch(() => {});
                    }
                } catch (e) {
                    console.warn('Screen share cancelled/failed:', e);
                }
            } else {
                stopScreenShare();
            }
        }

        async function stopScreenShare() {
            if (screenStream) {
                screenStream.getTracks().forEach(t => t.stop());
                screenStream = null;
            }
            const videoTrack = localStream?.getVideoTracks()[0];
            const sender = peerConnection?.getSenders().find(s => s.track?.kind === 'video');
            if (sender && videoTrack) {
                await sender.replaceTrack(videoTrack).catch(() => {});
            }
            isScreenSharing = false;
            document.getElementById('btnToggleScreen')?.classList.remove('active');
            showToast('স্ক্রিন শেয়ার সমাপ্ত');

            if (activeCallId) {
                apiFetch(`/api/v1/calls/${activeCallId}/state`, {
                    method: 'POST',
                    body: JSON.stringify({ is_screen_sharing: false })
                }).catch(() => {});
            }
        }

        function toggleFullscreen() {
            const iconEnter = document.getElementById('iconEnterFs');
            const iconExit = document.getElementById('iconExitFs');

            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    if (iconEnter) iconEnter.style.display = 'none';
                    if (iconExit) iconExit.style.display = 'block';
                }).catch(() => {});
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen().then(() => {
                        if (iconEnter) iconEnter.style.display = 'block';
                        if (iconExit) iconExit.style.display = 'none';
                    }).catch(() => {});
                }
            }
        }

        document.addEventListener('fullscreenchange', () => {
            const isFs = !!document.fullscreenElement;
            const iconEnter = document.getElementById('iconEnterFs');
            const iconExit = document.getElementById('iconExitFs');
            if (iconEnter) iconEnter.style.display = isFs ? 'none' : 'block';
            if (iconExit) iconExit.style.display = isFs ? 'block' : 'none';
            clampPipPosition();
        });

        function clampPipPosition() {
            const pip = document.getElementById('localVideoContainer');
            if (pip && pip.style.left) {
                const rect = pip.getBoundingClientRect();
                applyClampedPipPosition(rect.left, rect.top, true);
            }
        }

        // 6. Timer Stopwatch
        function startCallTimer() {
            if (callTimerInterval) return;
            const badge = document.getElementById('callTimerBadge');
            badge.style.display = 'inline-block';
            callDurationSecs = 0;

            callTimerInterval = setInterval(() => {
                callDurationSecs++;
                const mins = String(Math.floor(callDurationSecs / 60)).padStart(2, '0');
                const secs = String(callDurationSecs % 60).padStart(2, '0');
                badge.innerText = `${mins}:${secs}`;
            }, 1000);
        }

        function stopCallTimer() {
            clearInterval(callTimerInterval);
            callTimerInterval = null;
        }

        // 7. Authenticated API helper
        function apiFetch(url, options = {}) {
            const token = SERVER_AUTH_TOKEN || localStorage.getItem('jugajug_token') || '';
            const headers = Object.assign({
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest'
            }, token ? { 'Authorization': `Bearer ${token}` } : {}, options.headers || {});

            return fetch(url, Object.assign({ credentials: 'same-origin' }, options, { headers }));
        }

        function setCallStatus(text) {
            const el = document.getElementById('callStatusText');
            if (el) el.innerText = text;
            const vizEl = document.getElementById('audioVisualizerStatus');
            if (vizEl) vizEl.innerText = text;
        }

        let pendingRemoteCandidates = [];
        let offerSent = false;
        let callHasEnded = false;
        let ringTimeoutTimer = null;
        let syncInFlight = false;
        let fastSignalTimer = null;
        let signalQueue = Promise.resolve();
        const processedSignals = new Set();

        // Cross-browser RFC 4566 SDP line ending normalizer
        function normalizeSdp(sdp) {
            if (!sdp || typeof sdp !== 'string') return sdp;
            const lines = sdp.replace(/\r\n/g, '\n').replace(/\r/g, '\n').trim().split('\n');
            return lines.map(l => l.trim()).filter(l => l.length > 0).join('\r\n') + '\r\n';
        }

        function handleMediaError(err) {
            console.error('[WebRTC] Media access error:', err);
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                showToast('ক্যামেরা ও মাইক্রোফোনের অনুমতি মেলেনি। ব্রাউজারে পারমিশন চালু করুন।');
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                showToast('কোনো ক্যামেরা বা মাইক্রোফোন ডিভাইস খুঁজে পাওয়া যায়নি।');
            } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                showToast('ক্যামেরা বা মাইক্রোফোন অন্য অ্যাপ্লিকেশন ব্যবহার করছে।');
            } else if (err.name === 'OverconstrainedError') {
                showToast('ডিভাইসের রেজোলিউশন কনস্ট্রেইন্ট মেলেনি।');
            } else {
                showToast('ক্যামেরা/মাইক্রোফোন সমস্যা: ' + (err.message || err.name));
            }
        }

        // 8. WebRTC Core Engine
        async function acquireLocalMedia() {
            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                console.warn('[WebRTC] navigator.mediaDevices not available.');
                showToast('ক্যামেরা/মাইক্রোফোন ব্রাউজারে সাপোর্ট করছে না বা সাইটটি HTTPS-এ নেই');
                return null;
            }

            const wantsVideo = INITIAL_CALL_TYPE === 'video';

            if (wantsVideo) {
                try {
                    return await navigator.mediaDevices.getUserMedia({
                        audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true },
                        video: { width: { ideal: 1280, max: 1920 }, height: { ideal: 720, max: 1080 }, facingMode: 'user' }
                    });
                } catch (err1) {
                    console.warn('[WebRTC] Preferred camera constraint failed, trying basic:', err1.name);
                    try {
                        return await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
                    } catch (err2) {
                        console.warn('[WebRTC] Video capture failed, falling back to audio only:', err2.name);
                        showToast('ক্যামেরা পাওয়া যায়নি, শুধু অডিওতে সংযোগ করা হচ্ছে');
                        try {
                            return await navigator.mediaDevices.getUserMedia({
                                audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true }
                            });
                        } catch (err3) {
                            handleMediaError(err3);
                            return null;
                        }
                    }
                }
            } else {
                try {
                    return await navigator.mediaDevices.getUserMedia({
                        audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true }
                    });
                } catch (err) {
                    try {
                        return await navigator.mediaDevices.getUserMedia({ audio: true });
                    } catch (err2) {
                        handleMediaError(err2);
                        return null;
                    }
                }
            }
        }

        async function initializeCallSession() {
            // Prevent reviving an ended call via browser Back / Forward history traversal
            const navEntries = (window.performance && window.performance.getEntriesByType) ? window.performance.getEntriesByType('navigation') : [];
            const isBackForwardNav = (navEntries.length > 0 && navEntries[0].type === 'back_forward') ||
                                     (window.performance && window.performance.navigation && window.performance.navigation.type === 2);
            if (isBackForwardNav && !ANSWER_CALL_ID) {
                console.warn('[WebRTC] Detected back/forward traversal to call page. Redirecting to messages.');
                window.location.replace(`/messages/${CONVERSATION_ID}`);
                return;
            }

            localStream = await acquireLocalMedia();

            const hasLocalVideo = !!(localStream && localStream.getVideoTracks().length > 0);
            const localVid = document.getElementById('localVideo');
            const localVidContainer = document.getElementById('localVideoContainer');

            if (hasLocalVideo) {
                localVid.srcObject = localStream;
                localVid.muted = true;
                localVid.setAttribute('playsinline', '');
                localVid.setAttribute('webkit-playsinline', '');
                localVid.play().catch(e => console.warn('[WebRTC] Local video play catch:', e));
                localVidContainer.style.display = 'block';
                isVideoMuted = false;
                checkMultipleCameras();
            } else {
                isVideoMuted = true;
                document.getElementById('btnToggleCam').classList.add('muted');
                document.getElementById('camIconOn').style.display = 'none';
                document.getElementById('camIconOff').style.display = 'block';
                localVidContainer.style.display = 'none';
            }

            // Central viewport shows audio visualizer / caller avatar during ringing / connecting
            document.getElementById('audioVisualizer').style.display = 'flex';
            document.getElementById('remoteVideo').style.display = 'none';

            setupPeerConnection();
            startSyncListener();
            initDraggablePip();

            try {
                if (ANSWER_CALL_ID) {
                    // Callee: accept the ringing call, then wait for or retrieve the caller's SDP offer
                    activeCallId = ANSWER_CALL_ID;
                    setCallStatus('সংযোগ স্থাপন হচ্ছে...');
                    startFastSignalPolling();

                    const res = await apiFetch(`/api/v1/calls/${activeCallId}/respond`, {
                        method: 'POST',
                        body: JSON.stringify({ action: 'accept' })
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) {
                        onCallEnded(data.message || 'কলটি আর সক্রিয় নেই', false);
                        return;
                    }

                    // Immediately fetch and process any signals already transmitted
                    await fetchAndApplyPendingSignals();
                    return;
                }

                // Caller: create call session and ring peer
                const res = await apiFetch('/api/v1/calls', {
                    method: 'POST',
                    body: JSON.stringify({
                        conversation_id: CONVERSATION_ID,
                        receiver_id: PEER_USER_ID,
                        call_type: INITIAL_CALL_TYPE
                    })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success || !data.data?.id) {
                    onCallEnded(data.message || 'কল শুরু করা সম্ভব হয়নি', false);
                    return;
                }

                activeCallId = data.data.id;
                setCallStatus('কল রিং হচ্ছে...');
                startRingbackTone();
                startFastSignalPolling();

                // Caller sends offer immediately
                sendOffer();

                clearTimeout(ringTimeoutTimer);
                ringTimeoutTimer = setTimeout(() => {
                    if (!isCallAnswered) {
                        hangUpCall('উত্তর মেলেনি (কল টাইমআউট)');
                    }
                }, 45000);
            } catch (err) {
                console.error('Call session error:', err);
                onCallEnded('নেটওয়ার্ক ত্রুটির কারণে কল সংযোগ ব্যর্থ হয়েছে', false);
            }
        }

        function showAudioUnlockPrompt() {
            let unlockBanner = document.getElementById('audioUnlockBanner');
            if (!unlockBanner) {
                unlockBanner = document.createElement('div');
                unlockBanner.id = 'audioUnlockBanner';
                unlockBanner.style.cssText = 'position: fixed; top: 76px; left: 50%; transform: translateX(-50%); background: #1877f2; color: #fff; padding: 10px 22px; border-radius: 26px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.6); z-index: 9999; cursor: pointer; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(8px);';
                unlockBanner.innerHTML = '<span>🔊 কথা শুনতে এখানে ট্যাপ করুন</span>';
                document.body.appendChild(unlockBanner);

                const unlockHandler = () => {
                    const remoteAud = document.getElementById('remoteAudio');
                    if (remoteAud) remoteAud.play().catch(() => {});
                    unlockBanner.remove();
                    document.removeEventListener('click', unlockHandler);
                };
                unlockBanner.addEventListener('click', unlockHandler);
                document.addEventListener('click', unlockHandler, { once: true });
            }
        }

        function setupPeerConnection() {
            const config = {
                iceServers: (ICE_SERVERS && ICE_SERVERS.length > 0) ? ICE_SERVERS : [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' },
                    { urls: 'stun:stun2.l.google.com:19302' },
                    { urls: 'stun:stun.cloudflare.com:3478' }
                ],
                iceCandidatePoolSize: 10
            };

            peerConnection = new RTCPeerConnection(config);

            if (localStream) {
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                });
            }
            const audioSender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'audio');
            if (!audioSender) {
                peerConnection.addTransceiver('audio', { direction: 'recvonly' });
            }
            const videoSender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
            if (!videoSender && INITIAL_CALL_TYPE === 'video') {
                peerConnection.addTransceiver('video', { direction: 'recvonly' });
            }

            peerConnection.ontrack = (event) => {
                console.log('[WebRTC] Remote track received:', event.track.kind, event.track.id);

                // 1. Maintain persistent MediaStream
                if (!remoteStream) {
                    remoteStream = new MediaStream();
                }
                if (!remoteStream.getTrackById(event.track.id)) {
                    remoteStream.addTrack(event.track);
                }

                // 2. Audio playback via dedicated unmuted remoteAudio element
                const remoteAud = document.getElementById('remoteAudio');
                if (remoteAud && (!remoteAud.srcObject || remoteAud.srcObject !== remoteStream)) {
                    remoteAud.srcObject = remoteStream;
                    const playPromise = remoteAud.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(err => {
                            console.warn('[WebRTC] Audio autoplay blocked:', err);
                            showAudioUnlockPrompt();
                        });
                    }
                }

                // 3. Video playback via remoteVideo element
                const remoteVid = document.getElementById('remoteVideo');
                if (remoteVid) {
                    if (!remoteVid.srcObject || remoteVid.srcObject !== remoteStream) {
                        remoteVid.srcObject = remoteStream;
                    }
                    remoteVid.muted = true;
                    remoteVid.setAttribute('playsinline', '');
                    remoteVid.setAttribute('webkit-playsinline', '');

                    const renderRemoteVideoIfReady = () => {
                        const videoTracks = remoteStream.getVideoTracks();
                        const hasLiveVideo = videoTracks.some(t => t.readyState === 'live' && t.enabled && !t.muted);
                        if (hasLiveVideo) {
                            remoteVid.style.display = 'block';
                            document.getElementById('audioVisualizer').style.display = 'none';
                            remoteVid.play().catch(e => console.warn('[WebRTC] Remote video play error:', e));
                        }
                    };

                    if (event.track.kind === 'video') {
                        renderRemoteVideoIfReady();

                        event.track.onunmute = () => {
                            console.log('[WebRTC] Remote video track unmuted');
                            renderRemoteVideoIfReady();
                        };

                        event.track.onmute = () => {
                            console.log('[WebRTC] Remote video track muted');
                            const videoTracks = remoteStream.getVideoTracks();
                            const stillHasActiveVideo = videoTracks.some(t => t.readyState === 'live' && !t.muted);
                            if (!stillHasActiveVideo) {
                                remoteVid.style.display = 'none';
                                document.getElementById('audioVisualizer').style.display = 'flex';
                                const vizStatus = document.getElementById('audioVisualizerStatus');
                                if (vizStatus) vizStatus.innerText = 'অপর প্রান্তের ক্যামেরা বন্ধ আছে';
                            }
                        };

                        event.track.onended = () => {
                            console.log('[WebRTC] Remote video track ended');
                            remoteVid.style.display = 'none';
                            document.getElementById('audioVisualizer').style.display = 'flex';
                        };
                    }
                }

                onCallConnected();
            };

            peerConnection.onicecandidate = (event) => {
                if (event.candidate && activeCallId) {
                    const cData = event.candidate.toJSON ? event.candidate.toJSON() : event.candidate;
                    sendSignal('candidate', cData);
                }
            };

            peerConnection.oniceconnectionstatechange = () => {
                if (!peerConnection) return;
                const state = peerConnection.iceConnectionState;
                console.log('[WebRTC] ICE Connection State:', state);
                if (state === 'connected' || state === 'completed') {
                    onCallConnected();
                } else if (state === 'failed') {
                    console.warn('[WebRTC] ICE failed. Attempting restart...');
                    if (!ANSWER_CALL_ID && peerConnection.restartIce) {
                        peerConnection.restartIce();
                    }
                } else if (state === 'disconnected') {
                    setCallStatus('পুনঃসংযোগ হচ্ছে...');
                    const dot = document.getElementById('statusDot');
                    if (dot) dot.className = 'status-dot';
                }
            };

            peerConnection.onconnectionstatechange = () => {
                if (!peerConnection) return;
                const state = peerConnection.connectionState;
                console.log('[WebRTC] Connection State:', state);
                if (state === 'connected') {
                    onCallConnected();
                } else if (state === 'disconnected') {
                    setCallStatus('পুনঃসংযোগ হচ্ছে...');
                    const dot = document.getElementById('statusDot');
                    if (dot) dot.className = 'status-dot';
                } else if (state === 'failed') {
                    setCallStatus('সংযোগ ব্যর্থ — নেটওয়ার্ক পরীক্ষা করুন');
                    const dot = document.getElementById('statusDot');
                    if (dot) dot.className = 'status-dot';
                    showToast('সংযোগ ব্যর্থ — নেটওয়ার্ক পরীক্ষা করুন');
                }
            };
        }

        function onCallConnected() {
            if (isCallAnswered) return;
            isCallAnswered = true;
            clearTimeout(ringTimeoutTimer);
            stopRingbackTone();
            setCallStatus('সংযুক্ত (Connected)');
            const dot = document.getElementById('statusDot');
            if (dot) dot.className = 'status-dot connected';
            startCallTimer();
            startNetworkQualityMonitor();
        }

        // 9. Adaptive Video Quality and Network Statistics Monitor
        function startNetworkQualityMonitor() {
            if (netStatsInterval) clearInterval(netStatsInterval);

            netStatsInterval = setInterval(async () => {
                if (!peerConnection || callHasEnded || !isCallAnswered) return;

                try {
                    const stats = await peerConnection.getStats();
                    let currentRtt = null;
                    let currentPacketsLost = 0;
                    let totalPackets = 0;
                    let currentFps = null;

                    stats.forEach(report => {
                        if (report.type === 'candidate-pair' && report.state === 'succeeded' && report.nominated) {
                            if (report.currentRoundTripTime !== undefined) {
                                currentRtt = Math.round(report.currentRoundTripTime * 1000);
                            }
                        } else if (report.type === 'outbound-rtp' && report.kind === 'video') {
                            if (report.framesPerSecond !== undefined) {
                                currentFps = Math.round(report.framesPerSecond);
                            }
                        } else if (report.type === 'remote-inbound-rtp') {
                            if (report.packetsLost !== undefined) {
                                currentPacketsLost += report.packetsLost;
                            }
                            if (report.packetsReceived !== undefined) {
                                totalPackets += report.packetsReceived;
                            }
                        }
                    });

                    let quality = 'excellent';
                    let qualityLabel = 'উত্তম';
                    let qualityColor = '#10b981';
                    let signalIcon = '📶';

                    const lossRate = (totalPackets > 0) ? (currentPacketsLost / (totalPackets + currentPacketsLost)) : 0;

                    if (currentRtt !== null) {
                        if (currentRtt > 450 || lossRate > 0.12) {
                            quality = 'poor';
                            qualityLabel = 'দুর্বল';
                            qualityColor = '#ef4444';
                            signalIcon = '⚠️';
                        } else if (currentRtt > 220 || lossRate > 0.05) {
                            quality = 'moderate';
                            qualityLabel = 'মাঝারি';
                            qualityColor = '#f59e0b';
                            signalIcon = '📶';
                        }
                    }

                    const netBadge = document.getElementById('networkQualityBadge');
                    const netText = document.getElementById('netQualityText');
                    const netIcon = document.getElementById('netSignalBars');

                    if (netText) {
                        netText.innerText = qualityLabel;
                        netText.style.color = qualityColor;
                    }
                    if (netIcon) {
                        netIcon.innerText = signalIcon;
                    }
                    if (netBadge) {
                        const rttText = currentRtt !== null ? `${currentRtt}ms` : 'স্বাভাবিক';
                        netBadge.title = `পিং: ${rttText} | লস: ${Math.round(lossRate * 100)}% | এফপিএস: ${currentFps || '—'}`;
                    }

                    const videoSender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (videoSender && typeof videoSender.getParameters === 'function') {
                        try {
                            const params = videoSender.getParameters();
                            if (params && params.encodings && params.encodings.length > 0) {
                                if (quality === 'poor' && !isLowBitrateMode) {
                                    isLowBitrateMode = true;
                                    params.encodings[0].maxBitrate = 180000;
                                    params.encodings[0].scaleResolutionDownBy = 2.0;
                                    await videoSender.setParameters(params);
                                    console.log('[WebRTC] Network degraded. Scaled video parameters down.');
                                } else if (quality !== 'poor' && isLowBitrateMode) {
                                    isLowBitrateMode = false;
                                    params.encodings[0].scaleResolutionDownBy = 1.0;
                                    delete params.encodings[0].maxBitrate;
                                    try {
                                        await videoSender.setParameters(params);
                                    } catch (restoreErr) {
                                        params.encodings[0].maxBitrate = 2500000;
                                        await videoSender.setParameters(params);
                                    }
                                    console.log('[WebRTC] Network recovered. Restored video parameters.');
                                }
                            }
                        } catch (paramErr) {
                            console.warn('[WebRTC] Video sender parameter adaptation error:', paramErr);
                        }
                    }
                } catch (e) {
                    console.warn('[WebRTC] Stats monitor error:', e);
                }
            }, 4000);
        }

        async function sendSignal(signalType, payload) {
            if (!activeCallId) return;
            try {
                await apiFetch(`/api/v1/calls/${activeCallId}/signal`, {
                    method: 'POST',
                    body: JSON.stringify({
                        signal_type: signalType,
                        payload: payload,
                        target_user_id: PEER_USER_ID
                    })
                });
            } catch (e) {
                console.warn('[WebRTC] Signal send failed:', e);
            }
        }

        async function sendOffer() {
            if (offerSent || !peerConnection) return;
            offerSent = true;

            try {
                const offer = await peerConnection.createOffer({
                    offerToReceiveAudio: true,
                    offerToReceiveVideo: (INITIAL_CALL_TYPE === 'video')
                });
                const cleanSdp = normalizeSdp(offer.sdp);
                await peerConnection.setLocalDescription(new RTCSessionDescription({ type: 'offer', sdp: cleanSdp }));
                await sendSignal('offer', { type: 'offer', sdp: cleanSdp });
                console.log('[WebRTC] Offer created and sent successfully.');
            } catch (err) {
                console.error('[WebRTC] Offer generation error:', err);
                offerSent = false;
            }
        }

        async function flushPendingCandidates() {
            if (!peerConnection || !peerConnection.remoteDescription) return;
            const queued = pendingRemoteCandidates.splice(0, pendingRemoteCandidates.length);
            for (const candidate of queued) {
                try {
                    if (candidate && (candidate.candidate || candidate.candidate === '' || candidate.sdpMid !== null || candidate.sdpMLineIndex !== null)) {
                        await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                    }
                } catch (e) {
                    console.warn('[WebRTC] Queued ICE candidate add error:', e);
                }
            }
        }

        function enqueueSignal(signal) {
            signalQueue = signalQueue.then(() => processIncomingSignal(signal)).catch(err => {
                console.warn('[WebRTC] Signal queue processing error:', err);
            });
            return signalQueue;
        }

        async function fetchAndApplyPendingSignals() {
            if (!activeCallId || callHasEnded) return;
            try {
                const res = await apiFetch(`/api/v1/calls/${activeCallId}/signals`);
                const data = await res.json();
                if (data.success && Array.isArray(data.data)) {
                    for (const sig of data.data) {
                        enqueueSignal(sig);
                    }
                }
            } catch (e) {
                console.warn('[WebRTC] Signal fetch failed:', e);
            }
        }

        async function processIncomingSignal(signal) {
            if (!signal || !peerConnection || callHasEnded) return;
            if (Number(signal.sender_id) === CURRENT_USER_ID) return;
            if (Number(signal.call_id) !== Number(activeCallId)) return;

            let sigKey = '';
            if (signal.signal_type === 'candidate' && signal.payload) {
                const cand = signal.payload;
                sigKey = `cand_${signal.sender_id}_${cand.candidate || ''}_${cand.sdpMid}_${cand.sdpMLineIndex}`;
            } else if (signal.signal_type === 'offer') {
                const sdp = signal.payload?.sdp || (typeof signal.payload === 'string' ? signal.payload : '');
                sigKey = `offer_${signal.sender_id}_${sdp.length}_${sdp.slice(0, 100)}`;
            } else if (signal.signal_type === 'answer') {
                const sdp = signal.payload?.sdp || (typeof signal.payload === 'string' ? signal.payload : '');
                sigKey = `answer_${signal.sender_id}_${sdp.length}_${sdp.slice(0, 100)}`;
            } else {
                sigKey = `${signal.signal_type}_${signal.sender_id}_${JSON.stringify(signal.payload || '')}`;
            }

            if (processedSignals.has(sigKey)) return;
            processedSignals.add(sigKey);

            try {
                if (signal.signal_type === 'offer') {
                    console.log('[WebRTC] Processing incoming offer from peer:', signal.sender_id);
                    const rawSdp = signal.payload?.sdp || (typeof signal.payload === 'string' ? signal.payload : '');
                    const cleanSdp = normalizeSdp(rawSdp);
                    const desc = new RTCSessionDescription({ type: 'offer', sdp: cleanSdp });

                    if (peerConnection.signalingState !== 'stable') {
                        console.warn('[WebRTC] State collision for offer. Current state:', peerConnection.signalingState);
                        if (peerConnection.signalingState === 'have-local-offer') {
                            await peerConnection.setLocalDescription({ type: 'rollback' }).catch(() => {});
                        }
                    }

                    await peerConnection.setRemoteDescription(desc);
                    await flushPendingCandidates();

                    const answer = await peerConnection.createAnswer();
                    const cleanAnswerSdp = normalizeSdp(answer.sdp);
                    await peerConnection.setLocalDescription(new RTCSessionDescription({ type: 'answer', sdp: cleanAnswerSdp }));

                    await sendSignal('answer', { type: 'answer', sdp: cleanAnswerSdp });
                    setCallStatus('সংযোগ স্থাপন হচ্ছে...');
                } else if (signal.signal_type === 'answer') {
                    console.log('[WebRTC] Processing incoming answer from peer:', signal.sender_id);
                    if (peerConnection.signalingState === 'have-local-offer') {
                        const rawSdp = signal.payload?.sdp || (typeof signal.payload === 'string' ? signal.payload : '');
                        const cleanSdp = normalizeSdp(rawSdp);
                        await peerConnection.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: cleanSdp }));
                        await flushPendingCandidates();
                    } else {
                        console.warn('[WebRTC] Answer ignored because signalingState is', peerConnection.signalingState);
                    }
                } else if (signal.signal_type === 'candidate' && signal.payload) {
                    const candData = signal.payload;
                    if (candData && (candData.candidate || candData.candidate === '' || candData.sdpMid !== null || candData.sdpMLineIndex !== null)) {
                        if (peerConnection.remoteDescription && peerConnection.remoteDescription.type) {
                            try {
                                await peerConnection.addIceCandidate(new RTCIceCandidate(candData));
                            } catch (ce) {
                                console.warn('[WebRTC] ICE candidate add error:', ce);
                            }
                        } else {
                            pendingRemoteCandidates.push(candData);
                        }
                    }
                }
            } catch (e) {
                console.error('[WebRTC] Signal handling error:', e);
                processedSignals.delete(sigKey);
            }
        }

        function handleSyncEvent(evt) {
            const payload = evt.payload || {};
            const isThisCall = activeCallId && Number(payload.call_id) === Number(activeCallId);

            if (evt.event_type === 'call.signal') {
                enqueueSignal(payload);
            } else if (evt.event_type === 'call.accepted' && isThisCall && Number(payload.user_id) !== CURRENT_USER_ID) {
                stopRingbackTone();
                setCallStatus('সংযোগ স্থাপন হচ্ছে...');
                if (!ANSWER_CALL_ID) {
                    if (!offerSent) {
                        sendOffer();
                    } else if (peerConnection && peerConnection.signalingState === 'stable') {
                        sendOffer();
                    }
                }
            } else if (evt.event_type === 'call.rejected' && isThisCall && Number(payload.user_id) !== CURRENT_USER_ID) {
                onCallEnded(payload.status === 'busy' ? 'ব্যবহারকারী ব্যস্ত আছেন' : 'কল প্রত্যাখ্যান করা হয়েছে', false);
            } else if (evt.event_type === 'call.ended' && isThisCall) {
                onCallEnded('অপর প্রান্ত থেকে কল শেষ করা হয়েছে', false);
            }
        }

        // 10. Polling & Real-time Listeners
        function startFastSignalPolling() {
            if (fastSignalTimer) clearInterval(fastSignalTimer);
            fastSignalTimer = setInterval(async () => {
                if (callHasEnded) {
                    clearInterval(fastSignalTimer);
                    fastSignalTimer = null;
                    return;
                }
                if (activeCallId) {
                    await fetchAndApplyPendingSignals();
                }
            }, 600);
        }

        function startSyncListener() {
            syncPollTimer = setInterval(async () => {
                if (syncInFlight || callHasEnded) return;
                syncInFlight = true;
                try {
                    const res = await apiFetch(`/api/v1/messenger/sync?since_id=${lastSyncEventId}&limit=200`);
                    const data = await res.json();
                    if (data.success && data.data) {
                        lastSyncEventId = data.data.latest_event_id || lastSyncEventId;
                        for (const evt of (data.data.events || [])) {
                            handleSyncEvent(evt);
                        }
                    }
                } catch (e) {
                } finally {
                    syncInFlight = false;
                }
            }, 1000);
        }

        function cleanupMedia() {
            if (localStream) {
                localStream.getTracks().forEach(t => t.stop());
                localStream = null;
            }
            if (screenStream) {
                screenStream.getTracks().forEach(t => t.stop());
                screenStream = null;
            }
            if (remoteStream) {
                remoteStream.getTracks().forEach(t => t.stop());
                remoteStream = null;
            }
            const localVid = document.getElementById('localVideo');
            if (localVid) localVid.srcObject = null;
            const remoteVid = document.getElementById('remoteVideo');
            if (remoteVid) remoteVid.srcObject = null;
            const remoteAud = document.getElementById('remoteAudio');
            if (remoteAud) remoteAud.srcObject = null;
        }

        // 11. Hangup and Cleanup
        let leaveRequestSent = false;

        function hangUpCall(reason = 'কল সমাপ্ত হয়েছে') {
            onCallEnded(typeof reason === 'string' ? reason : 'কল সমাপ্ত হয়েছে', true);
        }

        function onCallEnded(reason = 'কল সমাপ্ত হয়েছে', notifyServer = true) {
            if (callHasEnded) return;
            callHasEnded = true;

            clearTimeout(ringTimeoutTimer);
            stopRingbackTone();
            playHangupTone();
            stopCallTimer();
            clearInterval(syncPollTimer);
            if (fastSignalTimer) {
                clearInterval(fastSignalTimer);
                fastSignalTimer = null;
            }
            if (netStatsInterval) {
                clearInterval(netStatsInterval);
                netStatsInterval = null;
            }

            setCallStatus(reason);
            const dot = document.getElementById('statusDot');
            if (dot) dot.style.backgroundColor = 'var(--call-danger)';
            showToast(reason);

            cleanupMedia();

            if (peerConnection) {
                try { peerConnection.close(); } catch (e) {}
                peerConnection = null;
            }

            if (notifyServer && activeCallId && !leaveRequestSent) {
                leaveRequestSent = true;
                apiFetch(`/api/v1/calls/${activeCallId}/leave`, { method: 'POST', keepalive: true }).catch(() => {});
            }

            setTimeout(() => {
                if (window.opener) {
                    try { window.close(); } catch (e) {}
                }
                // Critical: replace history state to avoid reviving call via browser back button
                window.location.replace(`/messages/${CONVERSATION_ID}`);
            }, 1500);
        }

        // Unblock audio autoplay on any user interaction
        document.addEventListener('click', () => {
            const remoteAud = document.getElementById('remoteAudio');
            if (remoteAud && remoteAud.srcObject && remoteAud.paused) {
                remoteAud.play().catch(() => {});
            }
        }, { passive: true });

        // 12. Browser Navigation and BFcache Protection
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                window.location.replace(`/messages/${CONVERSATION_ID}`);
            }
        });

        window.addEventListener('popstate', () => {
            hangUpCall('কল থেকে বের হওয়া হয়েছে');
        });

        window.addEventListener('pagehide', () => {
            if (fastSignalTimer) {
                clearInterval(fastSignalTimer);
                fastSignalTimer = null;
            }
            if (!callHasEnded && activeCallId && !leaveRequestSent) {
                leaveRequestSent = true;
                apiFetch(`/api/v1/calls/${activeCallId}/leave`, { method: 'POST', keepalive: true }).catch(() => {});
            }
            cleanupMedia();
        });

        // Initialize on DOM Ready
        document.addEventListener('DOMContentLoaded', () => {
            initializeCallSession();
        });
    </script>
</body>
</html>
