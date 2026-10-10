@extends('layouts.app')

@section('title', 'ওয়াচ ও লাইভ স্ট্রিমিং — Bondhoo')

@section('styles')
<style>
    :root {
        --live-red: #ef4444;
        --live-red-dark: #dc2626;
        --live-red-glow: rgba(239, 68, 68, 0.45);
        --live-gradient: linear-gradient(135deg, #ef4444 0%, #f43f5e 50%, #e11d48 100%);
    }

    .watch-page-container {
        max-width: 1400px;
        margin: 16px auto;
        padding: 0 16px 48px 16px;
    }

    /* -------------------------------------------------------------------------
       1. HERO BANNER - High-end Glassmorphic Studio Header
       ------------------------------------------------------------------------- */
    .watch-hero-banner {
        background: linear-gradient(135deg, #090d1a 0%, #151b34 45%, #1e1b4b 100%);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #ffffff;
        border-radius: 22px;
        padding: 32px 36px;
        margin-bottom: 22px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 24px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.35);
        position: relative;
        overflow: hidden;
    }

    /* Ambient Lighting Mesh Glows */
    .watch-hero-banner::before {
        content: '';
        position: absolute;
        top: -40%;
        right: -8%;
        width: 420px;
        height: 420px;
        background: radial-gradient(circle, rgba(239, 68, 68, 0.28) 0%, rgba(244, 63, 94, 0.08) 50%, transparent 70%);
        pointer-events: none;
        z-index: 1;
    }

    .watch-hero-banner::after {
        content: '';
        position: absolute;
        bottom: -50%;
        left: 20%;
        width: 380px;
        height: 380px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
        pointer-events: none;
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 680px;
    }

    .hero-kicker {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.16);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.3px;
        color: #fca5a5;
        margin-bottom: 12px;
    }

    .hero-headline {
        font-size: 27px;
        font-weight: 800;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        letter-spacing: -0.4px;
        line-height: 1.25;
        color: #ffffff;
    }

    .hero-headline svg {
        flex-shrink: 0;
        filter: drop-shadow(0 2px 8px rgba(239, 68, 68, 0.4));
    }

    .hero-subline {
        font-size: 14.5px;
        opacity: 0.92;
        line-height: 1.55;
        margin-bottom: 18px;
        color: #e2e8f0;
    }

    .hero-metrics {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .hero-metric-tag {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.14);
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 12.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #f1f5f9;
        transition: all 0.2s ease;
    }

    .hero-metric-tag:hover {
        background: rgba(255, 255, 255, 0.14);
        border-color: rgba(255, 255, 255, 0.25);
    }

    .hero-action-box {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn-go-live {
        background: var(--live-gradient);
        color: #ffffff !important;
        font-weight: 700;
        font-size: 14.5px;
        padding: 12px 26px;
        border-radius: 50px;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        box-shadow: 0 4px 22px var(--live-red-glow), 0 1px 2px rgba(0, 0, 0, 0.2);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-go-live:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 28px rgba(239, 68, 68, 0.6);
        color: #ffffff !important;
    }

    .btn-go-live:active {
        transform: translateY(0) scale(0.98);
    }

    .btn-live-studio-secondary {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #ffffff !important;
        font-weight: 600;
        font-size: 14px;
        padding: 11px 20px;
        border-radius: 50px;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        backdrop-filter: blur(8px);
        transition: all 0.2s ease;
    }

    .btn-live-studio-secondary:hover {
        background: rgba(255, 255, 255, 0.18);
        border-color: rgba(255, 255, 255, 0.35);
        color: #ffffff !important;
    }

    /* -------------------------------------------------------------------------
       2. QUICK NAVIGATION PILLS BAR
       ------------------------------------------------------------------------- */
    .watch-nav-pills-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        padding: 4px 2px 14px 2px;
        margin-bottom: 20px;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }

    .watch-nav-pills-bar::-webkit-scrollbar {
        display: none;
    }

    .watch-nav-pill {
        padding: 8px 16px;
        border-radius: 50px;
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        color: var(--fb-text-secondary);
        font-size: 13.5px;
        font-weight: 600;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .watch-nav-pill:hover {
        background: var(--fb-hover);
        color: var(--fb-text-primary);
        border-color: var(--fb-divider);
    }

    .watch-nav-pill.active {
        background: var(--fb-primary);
        color: #ffffff !important;
        border-color: var(--fb-primary);
        box-shadow: 0 2px 10px rgba(24, 119, 242, 0.3);
    }

    /* -------------------------------------------------------------------------
       3. LIVE BADGES & PULSE ANIMATIONS
       ------------------------------------------------------------------------- */
    .badge-live-now {
        background: var(--live-gradient);
        color: #ffffff;
        font-weight: 800;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 4px 10px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 10px rgba(239, 68, 68, 0.4);
    }

    .live-dot-pulse {
        width: 7px;
        height: 7px;
        background: #ffffff;
        border-radius: 50%;
        animation: pulseBadgeDot 1.4s infinite ease-in-out;
    }

    @keyframes pulseBadgeDot {
        0%, 100% { transform: scale(0.85); opacity: 0.7; }
        50% { transform: scale(1.35); opacity: 1; }
    }

    .live-radar-icon {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
    }

    .live-radar-dot {
        width: 9px;
        height: 9px;
        background: var(--live-red);
        border-radius: 50%;
        position: relative;
        z-index: 2;
    }

    .live-radar-wave {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 2px solid var(--live-red);
        opacity: 0.8;
        animation: liveRadarPing 1.8s cubic-bezier(0, 0.2, 0.8, 1) infinite;
    }

    @keyframes liveRadarPing {
        0% { transform: scale(0.6); opacity: 1; }
        100% { transform: scale(1.8); opacity: 0; }
    }

    /* -------------------------------------------------------------------------
       4. FEATURED STREAM CARD
       ------------------------------------------------------------------------- */
    .featured-stream-card {
        background: #000000;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 32px;
        border: 1px solid var(--fb-border);
        box-shadow: 0 12px 36px rgba(0, 0, 0, 0.16);
        display: block;
        text-decoration: none;
        color: inherit;
        position: relative;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .featured-stream-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 44px rgba(0, 0, 0, 0.28);
    }

    .featured-stage {
        height: 420px;
        background: linear-gradient(180deg, #0f172a 0%, #000000 100%);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .featured-overlay-content {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 30px;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.92) 0%, rgba(0, 0, 0, 0.55) 65%, transparent 100%);
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 20px;
    }

    .featured-meta-info {
        color: #ffffff;
    }

    .featured-title {
        font-size: 24px;
        font-weight: 800;
        margin-bottom: 8px;
        line-height: 1.3;
    }

    .featured-host-row {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        opacity: 0.95;
    }

    .featured-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: 2px solid var(--live-red);
        object-fit: cover;
    }

    /* -------------------------------------------------------------------------
       5. SECTION HEADERS
       ------------------------------------------------------------------------- */
    .section-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .section-title {
        font-size: 19px;
        font-weight: 800;
        color: var(--fb-text-primary);
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        letter-spacing: -0.2px;
    }

    .section-counter-pill {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--fb-text-secondary);
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        padding: 5px 12px;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* -------------------------------------------------------------------------
       6. STREAM CARDS GRID
       ------------------------------------------------------------------------- */
    .stream-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
        gap: 20px;
        margin-bottom: 36px;
    }

    .stream-card {
        background: var(--fb-card);
        border-radius: 16px;
        border: 1px solid var(--fb-border);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        text-decoration: none !important;
        color: inherit !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    }

    .stream-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
        border-color: rgba(239, 68, 68, 0.35);
    }

    .stream-thumb-box {
        position: relative;
        height: 185px;
        background: #090d16;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .stream-thumb-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .stream-card:hover .stream-thumb-preview {
        transform: scale(1.04);
    }

    .stream-thumb-placeholder-icon {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
    }

    .viewer-tag-pill {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.78);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        color: #ffffff;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .stream-card-body {
        padding: 16px;
        display: flex;
        gap: 12px;
    }

    .broadcaster-mini-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--live-red);
        flex-shrink: 0;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25);
    }

    .stream-card-info {
        flex: 1;
        min-width: 0;
    }

    .stream-card-title {
        font-weight: 700;
        font-size: 14.5px;
        color: var(--fb-text-primary);
        line-height: 1.35;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .stream-card-author {
        font-size: 13px;
        color: var(--fb-text-secondary);
        font-weight: 600;
    }

    /* -------------------------------------------------------------------------
       7. BEAUTIFUL EMPTY STATE - Studio Invitation Card
       ------------------------------------------------------------------------- */
    .watch-empty-studio-card {
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.03) 0%, rgba(24, 119, 242, 0.02) 100%), var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 22px;
        padding: 44px 28px;
        text-align: center;
        margin-bottom: 36px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.03);
        position: relative;
        overflow: hidden;
    }

    .empty-studio-icon-wrap {
        width: 76px;
        height: 76px;
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.12) 0%, rgba(244, 63, 94, 0.18) 100%);
        border: 1px solid rgba(239, 68, 68, 0.25);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px auto;
        color: var(--live-red);
        box-shadow: 0 4px 16px rgba(239, 68, 68, 0.15);
    }

    .empty-studio-title {
        font-size: 19px;
        font-weight: 800;
        color: var(--fb-text-primary);
        margin-bottom: 8px;
        letter-spacing: -0.2px;
    }

    .empty-studio-desc {
        font-size: 14.5px;
        color: var(--fb-text-secondary);
        max-width: 480px;
        margin: 0 auto 24px auto;
        line-height: 1.55;
    }

    .empty-features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 14px;
        max-width: 740px;
        margin: 28px auto 0 auto;
        padding-top: 24px;
        border-top: 1px solid var(--fb-border);
        text-align: left;
    }

    .empty-feature-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: var(--fb-bg);
        border: 1px solid var(--fb-border);
        border-radius: 12px;
        padding: 12px 14px;
    }

    .empty-feature-icon {
        font-size: 18px;
        line-height: 1;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .empty-feature-text-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--fb-text-primary);
        margin-bottom: 2px;
    }

    .empty-feature-text-sub {
        font-size: 11.5px;
        color: var(--fb-text-secondary);
        line-height: 1.35;
    }

    /* -------------------------------------------------------------------------
       8. REELS HORIZONTAL STRIP
       ------------------------------------------------------------------------- */
    .reels-horizontal-strip {
        display: flex;
        gap: 14px;
        overflow-x: auto;
        padding-bottom: 16px;
        scrollbar-width: thin;
        margin-bottom: 36px;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
    }

    .fb-reel-card {
        scroll-snap-align: start;
        flex: 0 0 160px;
        height: 270px;
        border-radius: 16px;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        background: #0b0f19;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        border: 1px solid var(--fb-border);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .fb-reel-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
    }

    .fb-reel-card-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .fb-reel-top-badges {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 2;
    }

    .fb-reel-badge-views {
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .fb-reel-hover-play {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.8);
        width: 44px;
        height: 44px;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(6px);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.2s ease;
        z-index: 2;
    }

    .fb-reel-card:hover .fb-reel-hover-play {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }

    .fb-reel-bottom-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 12px 10px;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, transparent 100%);
        color: #ffffff;
        z-index: 2;
    }

    .fb-reel-author-row {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .fb-reel-author-mini-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .fb-reel-author-name-text {
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .fb-reel-bottom-caption {
        font-size: 11px;
        color: #e2e8f0;
        margin-top: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* -------------------------------------------------------------------------
       9. VIDEO FEED POSTS GRID
       ------------------------------------------------------------------------- */
    .videos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
        gap: 20px;
    }

    .video-post-card {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .video-post-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.1);
    }

    .video-thumb-container {
        height: 180px;
        background: #0b1120;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .play-circle-overlay {
        width: 48px;
        height: 48px;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        transition: transform 0.2s ease, background 0.2s ease;
    }

    .video-post-card:hover .play-circle-overlay {
        transform: scale(1.12);
        background: var(--fb-primary);
    }

    .video-details-box {
        padding: 14px 16px;
    }

    /* -------------------------------------------------------------------------
       10. RESPONSIVE DESIGN (< 768px and < 480px)
       ------------------------------------------------------------------------- */
    @media (max-width: 768px) {
        .watch-page-container {
            margin: 10px auto;
            padding: 0 12px 36px 12px;
        }

        .watch-hero-banner {
            padding: 22px 18px;
            border-radius: 18px;
            gap: 18px;
        }

        .hero-headline {
            font-size: 21px;
            gap: 8px;
        }

        .hero-headline svg {
            width: 26px;
            height: 26px;
        }

        .hero-subline {
            font-size: 13.5px;
            margin-bottom: 14px;
        }

        .hero-metric-tag {
            font-size: 12px;
            padding: 5px 11px;
        }

        .hero-action-box {
            width: 100%;
        }

        .btn-go-live,
        .btn-live-studio-secondary {
            flex: 1;
            min-width: 140px;
            font-size: 13.5px;
            padding: 10px 18px;
        }

        .featured-stage {
            height: 280px;
        }

        .featured-title {
            font-size: 18px;
        }

        .featured-overlay-content {
            padding: 18px;
        }

        .stream-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .videos-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .watch-empty-studio-card {
            padding: 32px 18px;
            border-radius: 18px;
        }

        .empty-studio-icon-wrap {
            width: 64px;
            height: 64px;
            margin-bottom: 14px;
        }

        .empty-studio-title {
            font-size: 17px;
        }

        .empty-studio-desc {
            font-size: 13.5px;
            margin-bottom: 20px;
        }

        .empty-features-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
    }

    @media (max-width: 480px) {
        .watch-page-container {
            padding: 0 10px 32px 10px;
        }

        .watch-hero-banner {
            padding: 18px 14px;
            border-radius: 16px;
        }

        .hero-headline {
            font-size: 19px;
        }

        .hero-metrics {
            gap: 6px;
        }

        .hero-metric-tag {
            font-size: 11px;
            padding: 4px 9px;
        }

        .hero-action-box {
            flex-direction: column;
            width: 100%;
            gap: 8px;
        }

        .btn-go-live,
        .btn-live-studio-secondary {
            width: 100%;
        }

        .fb-reel-card {
            flex: 0 0 140px;
            height: 240px;
        }
    }
</style>
@endsection

@section('content')
<div class="watch-page-container">
    <!-- Hero Banner with Live Studio Shortcut -->
    <div class="watch-hero-banner">
        <div class="hero-content">
            <div class="hero-kicker">
                <span class="live-dot-pulse" style="background: #ef4444;"></span>
                <span>Bondhoo লাইভ ও ভিডিও স্ট্রিমিং হাব</span>
            </div>

            <h1 class="hero-headline">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/>
                    <line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
                <span>Bondhoo ওয়াচ ও লাইভ স্ট্রিমিং</span>
            </h1>

            <p class="hero-subline">
                আপনার পছন্দের ক্রিয়েটরদের সাথে সরাসরি যুক্ত থাকুন এবং মুহূর্তের মধ্যেই নিজস্ব লাইভ ব্রডকাস্ট শুরু করে বন্ধুদের সাথে আড্ডা দিন।
            </p>

            <div class="hero-metrics">
                <div class="hero-metric-tag">
                    <span class="live-dot-pulse" style="background: #ef4444; width: 6px; height: 6px;"></span>
                    <span>{{ $liveStreams->count() }}টি লাইভ চলছে</span>
                </div>
                <div class="hero-metric-tag">
                    <span>🎬</span>
                    <span>এইচডি ভিডিও ফিড</span>
                </div>
                <div class="hero-metric-tag">
                    <span>⚡</span>
                    <span>রিয়েল-টাইম ইন্টারঅ্যাকশন</span>
                </div>
            </div>
        </div>

        <div class="hero-action-box">
            <a href="{{ route('live.studio') }}" class="btn-go-live" title="সরাসরি লাইভ শুরু করুন">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M23 7l-7 5 7 5V7z"/>
                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
                </svg>
                <span>🔴 লাইভ শুরু করুন</span>
            </a>
            <a href="{{ route('live.studio') }}" class="btn-live-studio-secondary" title="ক্রিয়েটর স্টুডিও খুলুন">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                    <line x1="12" y1="19" x2="12" y2="22"/>
                </svg>
                <span>স্টুডিও ড্যাশবোর্ড</span>
            </a>
        </div>
    </div>

    <!-- Quick Navigation Filter Pills -->
    <div class="watch-nav-pills-bar">
        <a href="#liveSection" class="watch-nav-pill active">
            <span class="live-dot-pulse" style="background: #ef4444; width: 6px; height: 6px;"></span>
            <span>লাইভ সেশনসমূহ ({{ $liveStreams->count() }})</span>
        </a>
        @if(isset($reels) && $reels->isNotEmpty())
            <a href="#reelsSection" class="watch-nav-pill">
                <span>✨</span>
                <span>জনপ্রিয় রিলস</span>
            </a>
        @endif
        <a href="#videosSection" class="watch-nav-pill">
            <span>📹</span>
            <span>ভিডিও ফিড</span>
        </a>
    </div>

    @if($featuredStream)
        <!-- Featured Live Stream Showcase Card -->
        <a href="{{ route('live.show', $featuredStream->id) }}" class="featured-stream-card">
            <div class="featured-stage">
                @if($featuredStream->cover_url)
                    <img src="{{ $featuredStream->cover_url }}" style="width: 100%; height: 100%; object-fit: cover;" alt="{{ $featuredStream->title }}">
                @else
                    <div style="text-align: center; color: white;">
                        <div style="width: 80px; height: 80px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        </div>
                        <div style="font-size: 13px; font-weight: 700; color: #ef4444; letter-spacing: 1px; text-transform: uppercase;">বৈশিষ্ট্যযুক্ত লাইভ সেশন</div>
                    </div>
                @endif

                <div class="featured-overlay-content">
                    <div class="featured-meta-info">
                        <div style="margin-bottom: 8px;">
                            <span class="badge-live-now">
                                <span class="live-dot-pulse"></span>
                                <span>সরাসরি সম্প্রচার</span>
                            </span>
                        </div>
                        <div class="featured-title">{{ $featuredStream->title }}</div>
                        <div class="featured-host-row">
                            <img src="{{ $featuredStream->user?->profile?->avatar_url ?: '/images/default-avatar.svg' }}"
                                 alt="{{ $featuredStream->user?->name }}"
                                 class="featured-avatar">
                            <div>
                                <div style="font-weight: 700;">{{ $featuredStream->user?->name }}</div>
                                <div style="font-size: 12px; opacity: 0.8;">{{ $featuredStream->started_at ? $featuredStream->started_at->diffForHumans() : 'এইমাত্র শুরু হয়েছে' }}</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="viewer-tag-pill" style="font-size: 13px; padding: 6px 14px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <span>{{ number_format($featuredStream->viewers_count) }} জন দেখছেন</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    @endif

    <!-- Active Live Streams Grid -->
    <div id="liveSection" style="margin-bottom: 36px;">
        <div class="section-header-row">
            <h2 class="section-title">
                <span class="live-radar-icon">
                    <span class="live-radar-wave"></span>
                    <span class="live-radar-dot"></span>
                </span>
                <span>চলমান লাইভ সেশনসমূহ</span>
            </h2>
            <span class="section-counter-pill">
                <span class="live-dot-pulse" style="background: var(--live-red); width: 6px; height: 6px;"></span>
                <span>মোট {{ $liveStreams->count() }}টি লাইভ সক্রিয়</span>
            </span>
        </div>

        @if($liveStreams->isNotEmpty())
            <div class="stream-grid">
                @foreach($liveStreams as $stream)
                    <a href="{{ route('live.show', $stream->id) }}" class="stream-card">
                        <div class="stream-thumb-box">
                            @if($stream->cover_url)
                                <img src="{{ $stream->cover_url }}" alt="{{ $stream->title }}" class="stream-thumb-preview">
                            @else
                                <div class="stream-thumb-placeholder-icon">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                                </div>
                            @endif

                            <div style="position: absolute; top: 10px; left: 10px;">
                                <span class="badge-live-now">
                                    <span class="live-dot-pulse"></span>
                                    <span>LIVE</span>
                                </span>
                            </div>

                            <div class="viewer-tag-pill">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>{{ number_format($stream->viewers_count) }}</span>
                            </div>
                        </div>

                        <div class="stream-card-body">
                            <img src="{{ $stream->user?->profile?->avatar_url ?: '/images/default-avatar.svg' }}"
                                 alt="{{ $stream->user?->name }}"
                                 class="broadcaster-mini-avatar">
                            <div class="stream-card-info">
                                <div class="stream-card-title">{{ $stream->title }}</div>
                                <div class="stream-card-author">{{ $stream->user?->name }}</div>
                                <div style="font-size: 11.5px; color: var(--fb-text-secondary); margin-top: 3px; display: flex; align-items: center; gap: 4px;">
                                    <span>⏱️</span>
                                    <span>{{ $stream->started_at ? $stream->started_at->diffForHumans() : 'এইমাত্র শুরু হয়েছে' }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <!-- Redesigned Studio Invitation Empty State -->
            <div class="watch-empty-studio-card">
                <div class="empty-studio-icon-wrap">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"/>
                    </svg>
                </div>
                <h3 class="empty-studio-title">বর্তমানে কোনো লাইভ ব্রডকাস্ট চলছে না</h3>
                <p class="empty-studio-desc">
                    প্রথম ক্রিয়েটর হিসেবে আপনার নিজস্ব লাইভ স্ট্রিমিং শুরু করুন অথবা বন্ধুদের সাথে আড্ডায় মেতে উঠুন।
                </p>
                <a href="{{ route('live.studio') }}" class="btn-go-live" style="display: inline-flex;">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    <span>স্টুডিওতে গিয়ে লাইভ শুরু করুন</span>
                </a>

                <!-- 3 Quick Value Highlights -->
                <div class="empty-features-grid">
                    <div class="empty-feature-item">
                        <span class="empty-feature-icon">⚡</span>
                        <div>
                            <div class="empty-feature-text-title">লো-লেটেন্সি স্ট্রিমিং</div>
                            <div class="empty-feature-text-sub">অতি দ্রুত ও মসৃণ ব্রডকাস্ট অভিজ্ঞতা</div>
                        </div>
                    </div>
                    <div class="empty-feature-item">
                        <span class="empty-feature-icon">💬</span>
                        <div>
                            <div class="empty-feature-text-title">রিয়েল-টাইম কমেন্ট</div>
                            <div class="empty-feature-text-sub">দর্শকদের সাথে সরাসরি আড্ডা ও রিঅ্যাকশন</div>
                        </div>
                    </div>
                    <div class="empty-feature-item">
                        <span class="empty-feature-icon">👥</span>
                        <div>
                            <div class="empty-feature-text-title">স্বয়ংক্রিয় নোটিফিকেশন</div>
                            <div class="empty-feature-text-sub">লাইভ শুরু হলেই বন্ধুরা নোটিফিকেশন পাবে</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Trending Reels Section -->
    @if(isset($reels) && $reels->isNotEmpty())
        <div id="reelsSection" style="margin-bottom: 36px;">
            <div class="section-header-row">
                <h2 class="section-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <rect x="2" y="2" width="20" height="20" rx="4" fill="url(#watchReelsGrad)"/>
                        <path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5" stroke="#ffffff" stroke-width="1.5"/>
                        <polygon points="10 9 15 12 10 15 10 9" fill="#ffffff"/>
                        <defs>
                            <linearGradient id="watchReelsGrad" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#ec4899"/>
                                <stop offset="1" stop-color="#8b5cf6"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <span>জনপ্রিয় Bondhoo রিলস</span>
                </h2>
                <a href="{{ route('dashboard') }}" style="font-size: 13px; font-weight: 700; color: var(--fb-primary); text-decoration: none;">
                    সব রিল দেখুন ➔
                </a>
            </div>

            <div class="reels-horizontal-strip">
                @foreach($reels as $reel)
                    <div class="fb-reel-card" onclick="window.location.href='{{ route('dashboard') }}'">
                        <img src="{{ $reel->cover_image_url ?? $reel->thumbnail_url ?? 'https://images.unsplash.com/photo-1536240478700-b869070f9279?auto=format&fit=crop&w=300&q=80' }}" class="fb-reel-card-thumb" alt="Reel">
                        <div class="fb-reel-top-badges">
                            <div class="fb-reel-badge-views">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                <span>{{ number_format($reel->views_count) }}</span>
                            </div>
                        </div>
                        <div class="fb-reel-hover-play">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#ffffff"><polygon points="7 4 19 12 7 20 7 4"></polygon></svg>
                        </div>
                        <div class="fb-reel-bottom-info">
                            <div class="fb-reel-author-row">
                                <div class="fb-reel-author-mini-avatar" style="background: #3b82f6;">
                                    <span>{{ mb_substr($reel->user?->name ?? 'ক', 0, 1) }}</span>
                                </div>
                                <div class="fb-reel-author-name-text">
                                    <span>{{ $reel->user?->name }}</span>
                                </div>
                            </div>
                            @if($reel->caption)
                                <div class="fb-reel-bottom-caption">{{ $reel->caption }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Video Feed Posts Section -->
    <div id="videosSection">
        <div class="section-header-row">
            <h2 class="section-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                <span>প্রকাশিত ভিডিও ফিড</span>
            </h2>
        </div>

        @if($videoPosts->isNotEmpty())
            <div class="videos-grid">
                @foreach($videoPosts as $vPost)
                    <div class="video-post-card">
                        <div class="video-thumb-container">
                            <div class="play-circle-overlay">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            </div>
                        </div>
                        <div class="video-details-box">
                            <div style="font-weight: 700; font-size: 14.5px; color: var(--fb-text-primary); margin-bottom: 6px; line-height: 1.4;">
                                {{ Str::limit($vPost->content, 56) }}
                            </div>
                            <div style="font-size: 12px; color: var(--fb-text-secondary); display: flex; align-items: center; justify-content: space-between;">
                                <span>{{ $vPost->user?->name }}</span>
                                <span>{{ $vPost->created_at ? $vPost->created_at->diffForHumans() : 'সম্প্রতি' }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div style="margin-top: 24px;">
                {{ $videoPosts->links() }}
            </div>
        @else
            <div style="background: var(--fb-card); border: 1px solid var(--fb-border); border-radius: 18px; text-align: center; padding: 40px; color: var(--fb-text-secondary);">
                <div style="font-size: 38px; margin-bottom: 8px;">📹</div>
                <div style="font-weight: 700; font-size: 16px; color: var(--fb-text-primary);">কোনো ভিডিও পাওয়া যায়নি</div>
                <div style="font-size: 13px; margin-top: 4px;">লাইভ সম্প্রচার শেষে ভিডিওগুলো স্বয়ংক্রিয়ভাবে এখানে তালিকাভুক্ত হবে।</div>
            </div>
        @endif
    </div>
</div>
@endsection
