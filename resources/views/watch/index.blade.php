@extends('layouts.app')

@section('title', 'ওয়াচ ও লাইভ স্ট্রিমিং — Bondhoo')

@section('styles')
<style>
    :root {
        --live-red: #ef4444;
        --live-red-dark: #dc2626;
        --live-red-glow: rgba(239, 68, 68, 0.4);
    }

    .watch-page-container {
        max-width: 1400px;
        margin: 20px auto;
        padding: 0 16px 40px 16px;
    }

    /* Hero Banner */
    .watch-hero-banner {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #0f172a 100%);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: white;
        border-radius: 18px;
        padding: 32px 36px;
        margin-bottom: 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 24px;
        box-shadow: 0 12px 36px rgba(0, 0, 0, 0.25);
        position: relative;
        overflow: hidden;
    }

    .watch-hero-banner::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(239, 68, 68, 0.22) 0%, transparent 70%);
        pointer-events: none;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 650px;
    }

    .hero-headline {
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
        letter-spacing: -0.5px;
    }

    .hero-subline {
        font-size: 15px;
        opacity: 0.9;
        line-height: 1.5;
        margin-bottom: 16px;
    }

    .hero-metrics {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .hero-metric-tag {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .hero-action-box {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .btn-go-live {
        background: var(--live-red);
        color: white;
        font-weight: 800;
        font-size: 15px;
        padding: 12px 24px;
        border-radius: 30px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 20px var(--live-red-glow);
        transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .btn-go-live:hover {
        background: #dc2626;
        transform: translateY(-2px) scale(1.03);
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.55);
        color: white;
    }

    /* Live Badge & Pills */
    .badge-live-now {
        background: var(--live-red);
        color: white;
        font-weight: 800;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 4px 10px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .live-dot-pulse {
        width: 7px;
        height: 7px;
        background: white;
        border-radius: 50%;
        animation: pulseBadgeDot 1.4s infinite ease-in-out;
    }

    @keyframes pulseBadgeDot {
        0%, 100% { transform: scale(0.9); opacity: 0.8; }
        50% { transform: scale(1.4); opacity: 1; }
    }

    /* Featured Stream Showcase Card */
    .featured-stream-card {
        background: #000;
        border-radius: 18px;
        overflow: hidden;
        margin-bottom: 32px;
        border: 1px solid var(--fb-border);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        display: block;
        text-decoration: none;
        color: inherit;
        position: relative;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .featured-stream-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 40px rgba(0, 0, 0, 0.25);
    }

    .featured-stage {
        height: 420px;
        background: linear-gradient(180deg, #0f172a 0%, #000 100%);
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
        background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.5) 60%, transparent 100%);
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 20px;
    }

    .featured-meta-info {
        color: white;
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

    /* Section Headers */
    .section-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
    }

    .section-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--fb-text-primary);
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }

    /* Stream Cards Grid */
    .stream-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
        gap: 20px;
        margin-bottom: 36px;
    }

    .stream-card {
        background: var(--fb-card);
        border-radius: 14px;
        border: 1px solid var(--fb-border);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stream-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
        border-color: rgba(239, 68, 68, 0.3);
    }

    .stream-thumb-box {
        position: relative;
        height: 180px;
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
    }

    .stream-thumb-placeholder-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .viewer-tag-pill {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.75);
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        backdrop-filter: blur(6px);
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .stream-card-body {
        padding: 16px;
        display: flex;
        gap: 12px;
    }

    .broadcaster-mini-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--live-red);
        flex-shrink: 0;
    }

    .stream-card-info {
        flex: 1;
        min-width: 0;
    }

    .stream-card-title {
        font-weight: 700;
        font-size: 15px;
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

    /* Reels Strip */
    .reels-horizontal-strip {
        display: flex;
        gap: 14px;
        overflow-x: auto;
        padding-bottom: 14px;
        scrollbar-width: thin;
        margin-bottom: 36px;
    }

    /* Video Feed Grid */
    .videos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
        gap: 20px;
    }

    .video-post-card {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s;
    }

    .video-post-card:hover {
        transform: translateY(-3px);
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
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        transition: transform 0.2s;
    }

    .video-post-card:hover .play-circle-overlay {
        transform: scale(1.15);
        background: var(--fb-primary);
    }

    .video-details-box {
        padding: 14px 16px;
    }

    @media (max-width: 768px) {
        .watch-hero-banner {
            padding: 22px 20px;
        }
        .hero-headline {
            font-size: 22px;
        }
        .featured-stage {
            height: 280px;
        }
        .featured-title {
            font-size: 18px;
        }
    }
</style>
@endsection

@section('content')
<div class="watch-page-container">
    <!-- Hero Banner with Live Studio Shortcut -->
    <div class="watch-hero-banner">
        <div class="hero-content">
            <h1 class="hero-headline">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                <span>Bondhoo ওয়াচ ও লাইভ স্ট্রিমিং</span>
            </h1>
            <p class="hero-subline">
                আপনার পছন্দের ক্রিয়েটরদের সাথে সরাসরি যুক্ত থাকুন এবং মুহূর্তের মধ্যেই নিজস্ব লাইভ ব্রডকাস্ট শুরু করে বন্ধুদের সাথে আড্ডা দিন।
            </p>
            <div class="hero-metrics">
                <div class="hero-metric-tag">
                    <span style="color: #ef4444;">🔴</span>
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
            <a href="{{ route('live.studio') }}" class="btn-go-live">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                <span>🔴 লাইভ শুরু করুন</span>
            </a>
        </div>
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
                        <div class="viewer-tag-pill" style="font-size: 14px; padding: 6px 14px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <span>{{ number_format($featuredStream->viewers_count) }} জন দেখছেন</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    @endif

    <!-- Active Live Streams Grid -->
    <div style="margin-bottom: 36px;">
        <div class="section-header-row">
            <h2 class="section-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.2"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"/></svg>
                <span>চলমান লাইভ সেশনসমূহ</span>
            </h2>
            <span style="font-size: 13px; font-weight: 700; color: var(--fb-text-secondary);">
                মোট {{ $liveStreams->count() }}টি লাইভ актив
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
                                <div style="font-size: 11px; color: var(--fb-text-secondary); margin-top: 3px;">
                                    {{ $stream->started_at ? $stream->started_at->diffForHumans() : 'এইমাত্র' }}
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div style="background: var(--fb-card); border: 1px dashed var(--fb-border); border-radius: 16px; padding: 48px 24px; text-align: center; margin-bottom: 36px;">
                <div style="width: 64px; height: 64px; background: rgba(239,68,68,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; color: #ef4444;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>
                </div>
                <h3 style="font-size: 18px; font-weight: 800; color: var(--fb-text-primary); margin-bottom: 6px;">বর্তমানে কোনো লাইভ ব্রডকাস্ট চলছে না</h3>
                <p style="font-size: 14px; color: var(--fb-text-secondary); max-width: 440px; margin: 0 auto 20px auto;">
                    প্রথম ক্রিয়েটর হিসেবে আপনার নিজস্ব লাইভ স্ট্রিমিং শুরু করুন অথবা বন্ধুদের সাথে আড্ডায় মেতে উঠুন।
                </p>
                <a href="{{ route('live.studio') }}" class="btn-go-live" style="display: inline-flex;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    <span>স্টুডিওতে গিয়ে লাইভ শুরু করুন</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Trending Reels Section -->
    @if(isset($reels) && $reels->isNotEmpty())
        <div style="margin-bottom: 36px;">
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
    <div>
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
                            <div style="font-weight: 700; font-size: 15px; color: var(--fb-text-primary); margin-bottom: 6px; line-height: 1.4;">
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
            <div style="background: var(--fb-card); border: 1px solid var(--fb-border); border-radius: 14px; text-align: center; padding: 40px; color: var(--fb-text-secondary);">
                <div style="font-size: 38px; margin-bottom: 8px;">📹</div>
                <div style="font-weight: 700; font-size: 16px; color: var(--fb-text-primary);">কোনো ভিডিও পাওয়া যায়নি</div>
                <div style="font-size: 13px; margin-top: 4px;">লাইভ সম্প্রচার শেষে ভিডিওগুলো স্বয়ংক্রিয়ভাবে এখানে তালিকাভুক্ত হবে।</div>
            </div>
        @endif
    </div>
</div>
@endsection
