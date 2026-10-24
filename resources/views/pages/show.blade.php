@extends('layouts.app')

@section('title', $page->name . ' — অফিসিয়াল পেইজ — SocialHub')

@section('styles')
@php
    $pageSettings = $page->getMergedSettings();
    $allowIndexing = $pageSettings['discoverability']['search_engine_indexing'] ?? true;
@endphp
@if(! $allowIndexing || $page->visibility === 'private' || $page->visibility === 'unlisted')
<meta name="robots" content="noindex, nofollow">
@endif
<link rel="stylesheet" href="{{ asset('css/enterprise-pages-redesign.css') }}?v={{ time() }}">
<style>
    /* 3-Column Desktop Layout (Matching SocialHub Reference Design) */
    .sh-page-layout-3col {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        gap: 20px;
        width: 100%;
        max-width: 1480px;
        margin: 0 auto;
    }

    .sh-col-left {
        width: 270px;
        flex-shrink: 0;
        position: sticky;
        top: 72px;
        max-height: calc(100vh - 84px);
        overflow-y: auto;
        scrollbar-width: none;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .sh-col-left::-webkit-scrollbar { display: none; }

    .sh-col-center {
        flex: 1;
        min-width: 0;
        max-width: 680px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .sh-col-right {
        width: 340px;
        flex-shrink: 0;
        position: sticky;
        top: 72px;
        max-height: calc(100vh - 84px);
        overflow-y: auto;
        scrollbar-width: none;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .sh-col-right::-webkit-scrollbar { display: none; }

    @media (max-width: 1200px) {
        .sh-col-left { display: none; }
    }
    @media (max-width: 960px) {
        .sh-page-layout-3col { flex-direction: column; }
        .sh-col-right { width: 100%; position: static; max-height: none; }
        .sh-col-center { max-width: 100%; width: 100%; }
    }

    /* Left Sidebar Elements */
    .sh-user-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 10px;
        border-radius: 12px;
        text-decoration: none;
        color: inherit;
        transition: background 0.15s ease;
    }
    .sh-user-card:hover {
        background: var(--fb-hover, #f1f5f9);
    }
    .sh-user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        background: #cbd5e1;
        flex-shrink: 0;
    }
    .sh-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .sh-user-meta {
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .sh-user-name {
        font-size: 14px;
        font-weight: 700;
        color: var(--fb-text-primary, #1e293b);
        line-height: 1.2;
    }
    .sh-user-sub {
        font-size: 12px;
        color: var(--fb-text-secondary, #64748b);
    }

    .sh-nav-list {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .sh-nav-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 9px 12px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        color: var(--fb-text-primary, #1e293b);
        text-decoration: none;
        transition: background 0.15s ease;
        border: none;
        background: transparent;
        cursor: pointer;
        width: 100%;
        text-align: left;
    }
    .sh-nav-item:hover {
        background: var(--fb-hover, #f1f5f9);
    }
    .sh-nav-item.active {
        background: #e7f3ff;
        color: #1877f2;
    }
    .sh-nav-icon {
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: inherit;
    }
    .sh-nav-icon svg {
        width: 20px;
        height: 20px;
    }

    .sh-sidebar-divider {
        height: 1px;
        background: var(--fb-border, #e2e8f0);
        margin: 4px 10px;
    }

    .sh-shortcuts-section {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .sh-shortcuts-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--fb-text-secondary, #64748b);
        padding: 0 12px;
        letter-spacing: 0.5px;
    }
    .sh-shortcuts-list {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .sh-shortcut-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 7px 12px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: var(--fb-text-primary, #1e293b);
        text-decoration: none;
        transition: background 0.15s ease;
    }
    .sh-shortcut-item:hover {
        background: var(--fb-hover, #f1f5f9);
    }
    .sh-shortcut-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }
    .sh-icon-dev { background: #1e293b; color: white; font-family: monospace; font-size: 11px; font-weight: 700; }
    .sh-icon-travel { background: #0084ff; color: white; }
    .sh-icon-photo { background: #10b981; color: white; }
    .sh-icon-food { background: #f59e0b; color: white; }
    .sh-icon-tech { background: #6366f1; color: white; }

    .sh-create-page-card {
        background: var(--fb-card, #ffffff);
        border: 1px solid var(--fb-border, #e2e8f0);
        border-radius: 14px;
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05));
        margin-top: 6px;
    }
    .sh-card-header-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .sh-card-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #e7f3ff;
        color: #1877f2;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .sh-card-icon-box svg {
        width: 20px;
        height: 20px;
    }
    .sh-card-text {
        overflow: hidden;
    }
    .sh-card-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--fb-text-primary, #1e293b);
        line-height: 1.2;
    }
    .sh-card-desc {
        font-size: 11px;
        color: var(--fb-text-secondary, #64748b);
        line-height: 1.3;
        margin-top: 2px;
    }
    .sh-card-btn {
        width: 100%;
        padding: 8px;
        border-radius: 10px;
        background: #1877f2;
        color: white;
        font-size: 13px;
        font-weight: 700;
        text-align: center;
        text-decoration: none;
        transition: background 0.15s ease;
        border: none;
        cursor: pointer;
        display: block;
    }
    .sh-card-btn:hover {
        background: #1666d8;
    }

    .page-hero {
        background: var(--fb-card);
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--fb-border);
        box-shadow: var(--shadow-sm);
        margin-bottom: 0;
    }

    .page-hero-cover {
        height: 220px;
        background: linear-gradient(135deg, #0284c7, #0369a1);
        position: relative;
        overflow: hidden;
    }

    .page-hero-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cover-upload-btn {
        position: absolute;
        bottom: 16px;
        right: 16px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        backdrop-filter: blur(4px);
        transition: all 0.2s;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .cover-upload-btn:hover {
        background: rgba(0, 0, 0, 0.85);
        transform: translateY(-1px);
    }

    .page-hero-info {
        padding: 0 24px 20px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 16px;
        position: relative;
    }

    .page-avatar-wrapper {
        position: relative;
        margin-top: -65px;
    }

    .page-avatar-large {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        border: 4px solid var(--fb-card);
        background: #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 44px;
        font-weight: 800;
        color: #1e293b;
        overflow: hidden;
        box-shadow: var(--shadow-md);
        position: relative;
    }

    .page-avatar-large img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-upload-btn {
        position: absolute;
        bottom: 4px;
        right: 4px;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--fb-primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
        border: 2px solid var(--fb-card);
        transition: transform 0.2s;
    }

    .avatar-upload-btn:hover {
        transform: scale(1.08);
    }

    .page-hero-title {
        font-size: 26px;
        font-weight: 800;
        color: var(--fb-text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .page-hero-meta {
        font-size: 14px;
        color: var(--fb-text-secondary);
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 6px;
        flex-wrap: wrap;
    }

    /* Page Navigation Tabs */
    .page-nav-tabs {
        display: flex;
        gap: 6px;
        padding: 0 24px;
        border-top: 1px solid var(--fb-border);
        overflow-x: auto;
        background: var(--fb-card);
    }

    .page-nav-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 18px;
        font-size: 14px;
        font-weight: 600;
        color: var(--fb-text-secondary);
        border: none;
        background: transparent;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        white-space: nowrap;
    }

    .page-nav-tab:hover {
        color: var(--fb-primary);
        background: rgba(24, 119, 242, 0.04);
    }

    .page-nav-tab.active {
        color: var(--fb-primary);
        border-bottom-color: var(--fb-primary);
        background: rgba(24, 119, 242, 0.08);
    }

    /* Layout */
    .page-main-layout {
        display: block;
        margin-top: 0;
    }

    /* Post Card Styles */
    .post-card {
        background: var(--fb-card);
        border-radius: 12px;
        border: 1px solid var(--fb-border);
        padding: 18px;
        margin-bottom: 18px;
        box-shadow: var(--shadow-sm);
    }

    .post-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .post-author-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .post-media-grid {
        display: grid;
        gap: 6px;
        border-radius: 10px;
        overflow: hidden;
        margin: 14px 0;
    }

    .post-media-grid.single {
        grid-template-columns: 1fr;
    }

    .post-media-grid.dual {
        grid-template-columns: 1fr 1fr;
    }

    .post-media-grid img, .post-media-grid video {
        width: 100%;
        max-height: 480px;
        object-fit: cover;
        border-radius: 6px;
    }

    .post-actions-row {
        display: flex;
        border-top: 1px solid var(--fb-border);
        border-bottom: 1px solid var(--fb-border);
        padding: 6px 0;
        margin: 12px 0 8px;
    }

    .post-action-btn {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 12px;
        border: none;
        background: transparent;
        color: var(--fb-text-secondary);
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .post-action-btn:hover {
        background: rgba(0, 0, 0, 0.05);
        color: var(--fb-text-primary);
    }

    .post-action-btn.active-liked {
        color: var(--fb-primary);
        font-weight: 700;
    }

    /* Comments Section */
    .comments-section {
        margin-top: 12px;
        padding-top: 8px;
    }

    .comment-item {
        display: flex;
        gap: 10px;
        margin-bottom: 12px;
    }

    .comment-bubble {
        background: var(--fb-bg);
        border: 1px solid var(--fb-border);
        border-radius: 12px;
        padding: 8px 14px;
        flex: 1;
    }

    .reply-item {
        display: flex;
        gap: 8px;
        margin-top: 8px;
        margin-left: 24px;
    }

    /* Modal Backdrop */
    .page-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(2px);
    }

    .page-modal-box {
        background: var(--fb-card);
        border-radius: 14px;
        width: 520px;
        max-width: 92vw;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--fb-border);
    }

    /* Media Grid */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }

    .gallery-item {
        aspect-ratio: 1;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid var(--fb-border);
        background: #000;
        position: relative;
        cursor: pointer;
    }

    .gallery-item img, .gallery-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.25s;
    }

    .gallery-item:hover img {
        transform: scale(1.05);
    }

    /* Followers Grid */
    .followers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 14px;
    }

    .follower-card {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 10px;
        padding: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    @media (max-width: 900px) {
        .page-main-layout {
            grid-template-columns: 1fr;
        }
        .page-hero-cover {
            height: 200px;
        }
        .page-hero-info {
            align-items: center;
            flex-direction: column;
            text-align: center;
        }
        .page-avatar-wrapper {
            margin: -60px auto 10px;
        }
    }

    @media (max-width: 480px) {
        .page-hero-cover {
            height: 150px;
        }
        .cover-upload-btn {
            padding: 6px 10px;
            font-size: 11px;
            bottom: 8px;
            right: 8px;
        }
        .page-avatar-large {
            width: 90px;
            height: 90px;
            font-size: 32px;
        }
        .avatar-upload-btn {
            width: 30px;
            height: 30px;
        }
        .page-hero-title {
            font-size: 20px;
            justify-content: center;
            word-break: break-word;
        }
        .page-hero-info {
            padding: 0 14px 16px;
        }
        .page-nav-tabs {
            padding: 0 10px;
        }
        .page-nav-tab {
            padding: 10px 12px;
            font-size: 13px;
        }
        .post-card {
            padding: 14px 12px;
        }
    }

    @media (max-width: 374px) {
        .page-hero-cover {
            height: 120px;
        }
        .page-avatar-large {
            width: 76px;
            height: 76px;
            font-size: 26px;
        }
        .page-hero-title {
            font-size: 17px;
        }
    /* Stories & Reels Tray */
    .stories-tray-container {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        padding-bottom: 8px;
        margin-bottom: 18px;
        scrollbar-width: none;
    }
    .stories-tray-container::-webkit-scrollbar {
        display: none;
    }
    .story-card-item {
        flex: 0 0 115px;
        height: 195px;
        border-radius: 12px;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid var(--fb-border, #e2e8f0);
        background: #fff;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .story-card-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .create-story-card {
        display: flex;
        flex-direction: column;
        background: #fff;
    }
    .create-story-card .top-thumb {
        height: 135px;
        background: #f1f5f9;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .create-story-card .top-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .create-story-card .plus-btn-wrap {
        position: absolute;
        top: 115px;
        left: 50%;
        transform: translateX(-50%);
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #1877F2;
        border: 3px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 22px;
        font-weight: 700;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    .create-story-card .label {
        margin-top: 26px;
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
    }
    .active-story-card {
        background-size: cover;
        background-position: center;
        background-color: #334155;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 10px;
    }
    .active-story-card .avatar-ring {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 3px solid #1877F2;
        overflow: hidden;
        background: #cbd5e1;
    }
    .active-story-card .avatar-ring img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .active-story-card .meta {
        background: linear-gradient(transparent, rgba(0,0,0,0.8));
        margin: -10px;
        padding: 24px 10px 10px;
        color: #fff;
    }
    .active-story-card .meta .name {
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .active-story-card .meta .time {
        font-size: 11px;
        opacity: 0.85;
    }
    .create-reel-card {
        background: linear-gradient(135deg, #ec4899, #8b5cf6, #3b82f6);
        color: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 14px;
        gap: 8px;
    }
    .create-reel-card .play-icon-box {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: rgba(255,255,255,0.25);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    /* 4-Tile Publishing Studio Quick Hub */
    .publishing-hub-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    @media (max-width: 768px) {
        .publishing-hub-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .pub-card {
        background: #fff;
        border: 1px solid var(--fb-border, #e2e8f0);
        border-radius: 12px;
        padding: 14px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        text-decoration: none;
        color: inherit;
    }
    .pub-card:hover {
        border-color: #1877F2;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(24, 119, 242, 0.12);
    }
    .pub-card .icon-box {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 18px;
    }
    .pub-card .title {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .pub-card .desc {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
        line-height: 1.3;
    }

    /* Unified Composer Bar */
    .composer-quick-bar {
        background: #fff;
        border: 1px solid var(--fb-border, #e2e8f0);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .composer-quick-input-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .composer-quick-input {
        flex: 1;
        background: #f1f5f9;
        border: 1px solid transparent;
        border-radius: 24px;
        padding: 10px 16px;
        font-size: 14px;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s;
    }
    .composer-quick-input:hover {
        background: #e2e8f0;
    }
    .composer-action-buttons {
        display: flex;
        border-top: 1px solid var(--fb-border, #e2e8f0);
        padding-top: 10px;
        gap: 6px;
    }
    .composer-action-btn {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #475569;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .composer-action-btn:hover {
        background: #f1f5f9;
    }

    /* Right Column Widgets */
    .right-widget-card {
        background: #fff;
        border: 1px solid var(--fb-border, #e2e8f0);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .right-widget-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }
    .quick-action-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border: 1px solid var(--fb-border, #e2e8f0);
        border-radius: 10px;
        padding: 12px 6px;
        gap: 6px;
        font-size: 11px;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        transition: all 0.15s;
        text-decoration: none;
    }
    .quick-action-item:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1877F2;
    }
    .quick-action-item .icon-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e0f2fe;
        color: #0284c7;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Page Insights Widget */
    .insights-metric-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-bottom: 14px;
    }
    .insights-metric-box {
        background: #f8fafc;
        border: 1px solid var(--fb-border, #e2e8f0);
        border-radius: 8px;
        padding: 10px;
        text-align: left;
    }
    .insights-metric-val {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
    }
    .insights-metric-label {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
    }
    .insights-growth {
        font-size: 11px;
        font-weight: 600;
        color: #16a34a;
        display: flex;
        align-items: center;
        gap: 2px;
        margin-top: 4px;
    }
</style>
@endsection

@section('content')
<div class="sh-page-layout-3col">
    <!-- ============================================================== -->
    <!-- COLUMN 1: LEFT NAVIGATION & SHORTCUTS (DESKTOP)               -->
    <!-- ============================================================== -->
    <aside class="sh-col-left" aria-label="Main Navigation">
        <!-- User Profile Quick Link -->
        <a href="{{ $currentUser ? getUserProfileUrl($currentUser) : '/login' }}" class="sh-user-card">
            <div class="sh-user-avatar">
                @if($currentUser && $currentUser->avatar_url)
                    <img src="{{ $currentUser->avatar_url }}" alt="{{ $currentUser->name }}">
                @else
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80" alt="Guest User">
                @endif
            </div>
            <div class="sh-user-meta">
                <span class="sh-user-name">{{ $currentUser ? $currentUser->name : 'Guest User' }}</span>
                <span class="sh-user-sub">View your profile</span>
            </div>
        </a>

        <!-- Main Nav Items -->
        <nav class="sh-nav-list">
            <a href="/" class="sh-nav-item">
                <span class="sh-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                </span>
                <span class="sh-nav-label">Home</span>
            </a>
            <a href="/friends" class="sh-nav-item">
                <span class="sh-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </span>
                <span class="sh-nav-label">Friends</span>
            </a>
            <a href="/groups" class="sh-nav-item">
                <span class="sh-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </span>
                <span class="sh-nav-label">Groups</span>
            </a>
            <a href="/marketplace" class="sh-nav-item">
                <span class="sh-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                </span>
                <span class="sh-nav-label">Marketplace</span>
            </a>
            <a href="/saved" class="sh-nav-item">
                <span class="sh-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                </span>
                <span class="sh-nav-label">Saved</span>
            </a>
            <a href="/memories" class="sh-nav-item">
                <span class="sh-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </span>
                <span class="sh-nav-label">Memories</span>
            </a>
            <a href="/pages" class="sh-nav-item active">
                <span class="sh-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                </span>
                <span class="sh-nav-label">Pages</span>
            </a>
            <a href="/events" class="sh-nav-item">
                <span class="sh-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </span>
                <span class="sh-nav-label">Events</span>
            </a>
            <button type="button" class="sh-nav-item sh-nav-more" onclick="toggleShSeeMore(this)">
                <span class="sh-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
                <span class="sh-nav-label">See more</span>
            </button>
        </nav>

        <div class="sh-sidebar-divider"></div>

        <!-- YOUR SHORTCUTS -->
        <div class="sh-shortcuts-section">
            <h4 class="sh-shortcuts-title">YOUR SHORTCUTS</h4>
            <div class="sh-shortcuts-list">
                <a href="/groups?category=Development" class="sh-shortcut-item">
                    <span class="sh-shortcut-icon sh-icon-dev">&lt;/&gt;</span>
                    <span class="sh-shortcut-label">Web Developers</span>
                </a>
                <a href="/groups?category=Travel" class="sh-shortcut-item">
                    <span class="sh-shortcut-icon sh-icon-travel">🚀</span>
                    <span class="sh-shortcut-label">Travel & Adventure</span>
                </a>
                <a href="/groups?category=Photography" class="sh-shortcut-item">
                    <span class="sh-shortcut-icon sh-icon-photo">📷</span>
                    <span class="sh-shortcut-label">Photography</span>
                </a>
                <a href="/groups?category=Food" class="sh-shortcut-item">
                    <span class="sh-shortcut-icon sh-icon-food">🍕</span>
                    <span class="sh-shortcut-label">FoodLovers</span>
                </a>
                <a href="/groups?category=Technology" class="sh-shortcut-item">
                    <span class="sh-shortcut-icon sh-icon-tech">📱</span>
                    <span class="sh-shortcut-label">Technology</span>
                </a>
            </div>
        </div>

        <!-- Create Your Page Card -->
        <div class="sh-create-page-card">
            <div class="sh-card-header-row">
                <div class="sh-card-icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                </div>
                <div class="sh-card-text">
                    <h5 class="sh-card-title">Create Your Page</h5>
                    <p class="sh-card-desc">Build your brand, grow your community.</p>
                </div>
            </div>
            <a href="/pages/create" class="sh-card-btn">Create Page</a>
        </div>
    </aside>

    <!-- ============================================================== -->
    <!-- COLUMN 2: CENTER WORKSPACE (PAGE BANNER + CONTENT FEED)       -->
    <!-- ============================================================== -->
    <main class="sh-col-center">
        <!-- 1. PAGE HERO -->
        <div class="page-hero">
            <div class="page-hero-cover" id="pageCoverBox">
                <img src="{{ $page->cover_image_url ?: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1400&q=80' }}" alt="{{ $page->name }}" id="pageCoverImg">
                @if($canManage)
                    <label class="cover-upload-btn" title="Change Cover">
                        @include('pages.partials.icon', ['name' => 'camera', 'size' => 16])
                        <span>Change Cover</span>
                        <input type="file" id="coverFileInput" accept="image/*" style="display: none;" onchange="uploadPageBrandingImage('cover', event)">
                    </label>
                @endif
            </div>

            <div class="page-hero-info">
                <div style="display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap;">
                    <div class="page-avatar-wrapper">
                        <div class="page-avatar-large" id="pageAvatarBox" style="background: #1877f2; color: white;">
                            @if($page->avatar_url)
                                <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}" id="pageAvatarImg">
                            @else
                                <svg viewBox="0 0 24 24" fill="currentColor" style="width: 50px; height: 50px;">
                                    <path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/>
                                </svg>
                            @endif
                        </div>
                        @if($canManage)
                            <label class="avatar-upload-btn" title="Change Avatar">
                                @include('pages.partials.icon', ['name' => 'camera', 'size' => 18])
                                <input type="file" id="avatarFileInput" accept="image/*" style="display: none;" onchange="uploadPageBrandingImage('avatar', event)">
                            </label>
                        @endif
                    </div>

                    <div>
                        <h1 class="page-hero-title">
                            <span>{{ $page->name }}</span>
                            @if($page->is_verified)
                                <span title="Verified Page" style="color: #1877F2; display: inline-flex; align-items: center;" aria-label="Verified">
                                    <svg viewBox="0 0 24 24" fill="currentColor" style="width: 22px; height: 22px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                </span>
                            @endif
                        </h1>
                        <div class="page-hero-meta">
                            <span style="font-weight: 600; color: var(--fb-text-primary);">{{ $page->category ?: 'Technology' }}</span>
                            <span>•</span>
                            <span style="display: inline-flex; align-items: center; gap: 4px;">
                                <strong id="headerFollowersCount">{{ $page->followers_count >= 1000 ? round($page->followers_count / 1000) . 'K' : number_format($page->followers_count) }}</strong> followers
                            </span>
                        </div>
                        <p style="font-size: 13px; color: var(--fb-text-secondary); margin-top: 4px; line-height: 1.4;">
                            {{ $page->bio ?: ($page->description ?: 'Connecting people, ideas and opportunities. Join our community and be part of something bigger!') }}
                        </p>
                    </div>
                </div>

                <!-- Hero Actions -->
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <button class="btn-fb-primary" id="btnPageFollow" onclick="handlePageFollowToggle()" style="display: inline-flex; align-items: center; gap: 6px; background: {{ $isFollowing ? '#e4e6eb' : '#1877f2' }}; color: {{ $isFollowing ? 'var(--fb-text-primary)' : '#ffffff' }}; padding: 8px 18px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s ease;">
                        <span id="followIcon" style="display: inline-flex; align-items: center;">
                            @if($isFollowing)
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 16px; height: 16px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="currentColor" style="width: 16px; height: 16px;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                            @endif
                        </span>
                        <span id="followText">{{ $isFollowing ? 'Following' : 'Follow' }}</span>
                    </button>

                    <button class="btn-fb-secondary" id="btnPageMessage" onclick="openPageMessageModal()" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;" title="Message Page">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        <span>Message</span>
                    </button>

                    @if($canManage)
                        <button class="btn-fb-secondary" id="btnEditPage" onclick="openEditPageModal()" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;" title="Edit Page Details">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            <span>Edit Page</span>
                        </button>
                        <a href="{{ route('pages.manage', $page->slug) }}" class="btn-fb-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none;" title="Manage Page Studio">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            <span>Manage</span>
                        </a>
                    @endif

                    <!-- More Menu Dropdown -->
                    <div style="position: relative;">
                        <button class="btn-fb-secondary" id="btnPageMoreMenu" onclick="togglePageMoreMenu(event)" style="display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; padding: 0; border-radius: 8px; cursor: pointer;" title="More options" aria-expanded="false" aria-haspopup="true">
                            <svg viewBox="0 0 24 24" fill="currentColor" style="width: 18px; height: 18px;"><circle cx="12" cy="12" r="2"></circle><circle cx="19" cy="12" r="2"></circle><circle cx="5" cy="12" r="2"></circle></svg>
                        </button>
                        <div id="pageMoreDropdown" class="dropdown-menu" style="display: none; position: absolute; right: 0; top: 44px; width: 230px; background: #ffffff; border: 1px solid var(--fb-border); border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); z-index: 200; overflow: hidden; padding: 6px 0;">
                            <button type="button" class="dropdown-item" onclick="copyPageUrl()" style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: none; background: transparent; font-size: 13.5px; font-weight: 600; color: var(--fb-text-primary); text-align: left; cursor: pointer;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                                <span>Copy Page Link</span>
                            </button>
                            <button type="button" class="dropdown-item" onclick="openPageShareModal()" style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: none; background: transparent; font-size: 13.5px; font-weight: 600; color: var(--fb-text-primary); text-align: left; cursor: pointer;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                                <span>Share Page</span>
                            </button>
                            @if($canManage)
                                <div style="height: 1px; background: var(--fb-border); margin: 4px 0;"></div>
                                <button type="button" class="dropdown-item" onclick="openEditPageModal()" style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: none; background: transparent; font-size: 13.5px; font-weight: 600; color: var(--fb-text-primary); text-align: left; cursor: pointer;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    <span>Edit Page Details</span>
                                </button>
                                <a href="{{ route('pages.manage', $page->slug) }}" class="dropdown-item" style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: 13.5px; font-weight: 600; color: var(--fb-text-primary); text-decoration: none;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                                    <span>Page Studio / Settings</span>
                                </a>
                            @endif
                            <div style="height: 1px; background: var(--fb-border); margin: 4px 0;"></div>
                            <button type="button" class="dropdown-item" onclick="openPageMessageModal()" style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: none; background: transparent; font-size: 13.5px; font-weight: 600; color: var(--fb-text-primary); text-align: left; cursor: pointer;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                <span>Message Page</span>
                            </button>
                            <button type="button" class="dropdown-item" onclick="reportPageModal()" style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: none; background: transparent; font-size: 13.5px; font-weight: 600; color: #dc2626; text-align: left; cursor: pointer;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                <span>Report Page</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="page-nav-tabs">
                <button class="page-nav-tab {{ $activeTab === 'home' || empty($activeTab) ? 'active' : '' }}" onclick="switchPageTab('home')">
                    <span>Home</span>
                </button>
                <button class="page-nav-tab {{ $activeTab === 'posts' ? 'active' : '' }}" onclick="switchPageTab('posts')">
                    <span>Posts</span>
                </button>
                <button class="page-nav-tab {{ $activeTab === 'about' ? 'active' : '' }}" onclick="switchPageTab('about')">
                    <span>About</span>
                </button>
                <button class="page-nav-tab {{ $activeTab === 'photos' ? 'active' : '' }}" onclick="switchPageTab('photos')">
                    <span>Photos</span>
                </button>
                <button class="page-nav-tab {{ $activeTab === 'videos' ? 'active' : '' }}" onclick="switchPageTab('videos')">
                    <span>Videos</span>
                </button>
                <button class="page-nav-tab {{ $activeTab === 'events' ? 'active' : '' }}" onclick="switchPageTab('events')">
                    <span>Events</span>
                </button>
                <div style="position: relative; display: inline-block;">
                    <button class="page-nav-tab {{ in_array($activeTab, ['reels', 'followers', 'shop'], true) ? 'active' : '' }}" onclick="toggleMoreMenu(event)" style="display: inline-flex; align-items: center; gap: 4px;">
                        <span>More</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <div id="moreNavDropdown" style="display: none; position: absolute; top: calc(100% + 4px); right: 0; background: var(--fb-card); border: 1px solid var(--fb-border); border-radius: 8px; box-shadow: var(--shadow-md); z-index: 50; min-width: 190px; overflow: hidden; padding: 6px 0;">
                        <button type="button" class="dropdown-item" onclick="switchPageTab('reels'); closeMoreMenu();" style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: none; background: transparent; font-size: 13.5px; font-weight: 600; color: var(--fb-text-primary); text-align: left; cursor: pointer;">
                            <span style="font-size: 16px;">🎬</span>
                            <span>Reels</span>
                        </button>
                        <button type="button" class="dropdown-item" onclick="switchPageTab('followers'); closeMoreMenu();" style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: none; background: transparent; font-size: 13.5px; font-weight: 600; color: var(--fb-text-primary); text-align: left; cursor: pointer;">
                            <span style="font-size: 16px;">👥</span>
                            <span>Followers</span>
                        </button>
                        <button type="button" class="dropdown-item" onclick="switchPageTab('shop'); closeMoreMenu();" style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: none; background: transparent; font-size: 13.5px; font-weight: 600; color: var(--fb-text-primary); text-align: left; cursor: pointer;">
                            <span style="font-size: 16px;">🛍️</span>
                            <span>Shop</span>
                        </button>
                        <button type="button" class="dropdown-item" onclick="switchPageTab('about'); closeMoreMenu();" style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: none; background: transparent; font-size: 13.5px; font-weight: 600; color: var(--fb-text-primary); text-align: left; cursor: pointer;">
                            <span style="font-size: 16px;">ℹ️</span>
                            <span>About Page</span>
                        </button>
                        @if($canManage)
                            <div style="height: 1px; background: var(--fb-border); margin: 4px 0;"></div>
                            <a href="{{ route('pages.manage', $page->slug) }}" class="dropdown-item" style="width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: 13.5px; font-weight: 600; color: var(--fb-text-primary); text-decoration: none;">
                                <span style="font-size: 16px;">⚙️</span>
                                <span>Page Studio / Settings</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. TAB CONTENT PANES -->

        <!-- TAB: HOME -->
        <div id="tab-pane-home" class="page-tab-pane" style="{{ $activeTab === 'home' || empty($activeTab) ? '' : 'display: none;' }}">
            <!-- Stories & Reels Tray -->
            @if($canManage || (isset($stories) && count($stories) > 0))
                <div class="stories-tray-container">
                    @if($canManage)
                        <!-- Create Story Card -->
                        <div class="story-card-item create-story-card" onclick="openStoryCreatorModal()">
                            <div class="top-thumb">
                                @if($page->avatar_url)
                                    <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}">
                                @else
                                    <div style="font-size: 32px; font-weight: 700; color: #64748b;">{{ mb_substr($page->name, 0, 1) }}</div>
                                @endif
                            </div>
                            <div class="plus-btn-wrap">+</div>
                            <div class="label">Create Story</div>
                        </div>
                    @endif

                    <!-- Active Stories -->
                    @if(isset($stories) && count($stories) > 0)
                        @foreach($stories as $s)
                            @php
                                $mediaObj = $s->storyMedia->first()?->media ?? $s->media->first();
                                $mediaUrl = $mediaObj ? ($mediaObj->getVariantUrl('original') ?: ($mediaObj->url ?: asset('storage/' . $mediaObj->original_path))) : null;
                                $isVideo = $mediaObj && str_starts_with($mediaObj->mime_type ?? '', 'video/');
                            @endphp
                            <div class="story-card-item active-story-card" style="{{ $mediaUrl && !$isVideo ? 'background-image: url(' . $mediaUrl . ');' : ($s->background_color ? 'background-color: ' . $s->background_color . ';' : 'background-color: #1877F2;') }}" onclick="viewStory({{ $s->id }}, '{{ addslashes($s->page?->name ?? $s->user?->name ?? $page->name) }}', '{{ $mediaUrl ?: '' }}', {{ $isVideo ? 'true' : 'false' }}, {{ (int)$s->views_count }}, {{ $canManage ? 'true' : 'false' }}, '{{ $s->privacy }}', '{{ $s->created_at ? $s->created_at->diffForHumans() : 'Just now' }}', '{{ addslashes($s->content ?? '') }}')">
                                <div class="avatar-ring">
                                    <img src="{{ $s->page?->avatar_url ?: ($s->user?->profile?->avatar_url ?: ($page->avatar_url ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100')) }}" alt="Story">
                                </div>
                                <div class="meta">
                                    <div class="name">{{ $s->page?->name ?: ($s->user?->name ?: 'Community') }}</div>
                                    <div class="time">{{ $s->created_at ? $s->created_at->diffForHumans(null, true) : '2h' }}</div>
                                </div>
                                @if($isVideo)
                                    <div style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.6); border-radius: 4px; padding: 2px 6px; font-size: 10px; color: #fff;">▶ Video</div>
                                @endif
                            </div>
                        @endforeach
                    @endif

                    @if($canManage)
                        <!-- Create Reel Card -->
                        <div class="story-card-item create-reel-card" onclick="openReelCreatorModal()">
                            <div class="play-icon-box">▶</div>
                            <div style="font-weight: 700; font-size: 13px;">Create Reel</div>
                        </div>
                    @endif
                </div>
            @endif

            @if($canManage)
                <!-- Quick Composer Bar (Matching Reference Screenshot) -->
                <div class="composer-quick-bar">
                    <div class="composer-quick-input-row">
                        <div class="avatar" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #e2e8f0; flex-shrink: 0;">
                            @if($page->avatar_url)
                                <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 700;">{{ mb_substr($page->name, 0, 1) }}</div>
                            @endif
                        </div>
                        <div class="composer-quick-input" onclick="focusPageComposer()">
                            What's on your mind, {{ $page->name }}?
                        </div>
                    </div>
                    <div class="composer-action-buttons">
                        <button type="button" class="composer-action-btn" onclick="focusPageComposerWithMedia()">
                            <span style="color: #22c55e;">🖼️</span>
                            <span>Photo/Video</span>
                        </button>
                        <button type="button" class="composer-action-btn" onclick="openReelCreatorModal()">
                            <span style="color: #ec4899;">🎬</span>
                            <span>Reel</span>
                        </button>
                        <button type="button" class="composer-action-btn" onclick="openLiveStudioModal()">
                            <span style="color: #ef4444;">🔴</span>
                            <span>Live</span>
                        </button>
                        <button type="button" class="composer-action-btn" onclick="focusPageComposer()">
                            <span style="color: #eab308;">😊</span>
                            <span>Feeling/Activity</span>
                        </button>
                    </div>
                </div>

                <!-- 4-Tile Publishing Studio Quick Hub -->
                <div class="publishing-hub-grid">
                    <div class="pub-card" onclick="focusPageComposer()">
                        <div class="icon-box" style="background: #eff6ff; color: #1877F2;">✍️</div>
                        <div>
                            <div class="title">
                                <span>Create Post</span>
                                <span style="color: #1877F2;">→</span>
                            </div>
                            <div class="desc">Share updates with your followers</div>
                        </div>
                    </div>
                    <div class="pub-card" onclick="openReelCreatorModal()">
                        <div class="icon-box" style="background: #fdf2f8; color: #ec4899;">🎬</div>
                        <div>
                            <div class="title">Reel</div>
                            <div class="desc">Create short videos (15s - 90s)</div>
                        </div>
                    </div>
                    <div class="pub-card" onclick="openLiveStudioModal()">
                        <div class="icon-box" style="background: #fef2f2; color: #ef4444;">🔴</div>
                        <div>
                            <div class="title">Live</div>
                            <div class="desc">Go live and connect with your audience</div>
                        </div>
                    </div>
                    <div class="pub-card" onclick="openShareVideoModal()">
                        <div class="icon-box" style="background: #f0fdf4; color: #16a34a;">📹</div>
                        <div>
                            <div class="title">Share Video</div>
                            <div class="desc">Upload or share videos</div>
                        </div>
                    </div>
                </div>

                <!-- Enterprise Post Composer -->
                <div class="fb-card" style="margin-bottom: 20px;">
                    <form onsubmit="handlePagePostSubmit(event)" id="pagePostComposerForm">
                        <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                            <div class="avatar" style="width: 44px; height: 44px;">
                                @if($page->avatar_url)
                                    <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}">
                                @else
                                    {{ mb_substr($page->name, 0, 1) }}
                                @endif
                            </div>
                            <textarea id="pagePostContent" rows="3" class="search-input" style="flex: 1; height: auto; border-radius: 10px; border: 1px solid var(--fb-border); padding: 12px; font-size: 14px; background: var(--fb-bg);" placeholder="{{ $page->name }} হিসেবে নতুন কিছু প্রকাশ করুন..." required></textarea>
                        </div>

                        <!-- Media Preview Container -->
                        <div id="composerMediaPreview" style="display: none; margin-bottom: 12px; position: relative;">
                            <div id="previewMediaBox" style="max-height: 200px; overflow: hidden; border-radius: 8px; border: 1px solid var(--fb-border);"></div>
                            <button type="button" onclick="clearSelectedComposerMedia()" style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                @include('pages.partials.icon', ['name' => 'x', 'size' => 14])
                            </button>
                        </div>

                        <!-- Composer Options Row -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; margin-bottom: 12px; padding: 10px; background: var(--fb-hover); border-radius: 10px;">
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: var(--fb-text-secondary); display: block; margin-bottom: 4px;">দৃশ্যমানতা (Audience)</label>
                                <select id="pagePostAudience" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 6px; padding: 5px 8px; font-size: 12px; background: var(--fb-card); color: var(--fb-text-primary);">
                                    <option value="public" selected>🌐 সবাই (Public)</option>
                                    <option value="followers">👥 শুধুমাত্র ফলোয়ার্স (Followers)</option>
                                    <option value="only_me">🔒 শুধুমাত্র এডমিন (Private)</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: var(--fb-text-secondary); display: block; margin-bottom: 4px;">অ্যাকশন (Action)</label>
                                <select id="pagePostStatus" onchange="toggleComposerSchedule(this.value)" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 6px; padding: 5px 8px; font-size: 12px; background: var(--fb-card); color: var(--fb-text-primary);">
                                    <option value="published" selected>🚀 এখনই প্রকাশ করুন</option>
                                    <option value="draft">💾 ড্রাফট সংরক্ষণ করুন</option>
                                    <option value="scheduled">⏰ শিডিউল করুন</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700; color: var(--fb-text-secondary); display: block; margin-bottom: 4px;">অনুভূতি (Feeling)</label>
                                <select id="pagePostFeeling" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 6px; padding: 5px 8px; font-size: 12px; background: var(--fb-card); color: var(--fb-text-primary);">
                                    <option value="">কোনো অনুভূতি নেই</option>
                                    <option value="Feeling Happy 😊">Feeling Happy 😊</option>
                                    <option value="Feeling Excited 🤩">Feeling Excited 🤩</option>
                                    <option value="Feeling Blessed 😇">Feeling Blessed 😇</option>
                                    <option value="Celebrating 🎉">Celebrating 🎉</option>
                                    <option value="Traveling ✈️">Traveling ✈️</option>
                                    <option value="Working hard 💼">Working hard 💼</option>
                                </select>
                            </div>
                        </div>

                        <!-- Schedule Box -->
                        <div id="composerScheduleBox" style="display: none; margin-bottom: 12px; padding: 10px; background: var(--fb-hover); border-radius: 10px;">
                            <label style="font-size: 11px; font-weight: 700; color: var(--fb-text-secondary); display: block; margin-bottom: 4px;">শিডিউলের তারিখ ও সময় (ভবিষ্যতের সময় নির্বাচন করুন)</label>
                            <input type="datetime-local" id="pagePostScheduledAt" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 6px; padding: 6px 10px; font-size: 13px; background: var(--fb-card); color: var(--fb-text-primary);">
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--fb-border); padding-top: 10px;">
                            <div>
                                <label class="btn-fb-secondary" style="font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;">
                                    @include('pages.partials.icon', ['name' => 'image', 'size' => 16])
                                    <span>ছবি / ভিডিও যোগ করুন</span>
                                    <input type="file" id="composerMediaInput" accept="image/*,video/*" multiple style="display: none;" onchange="handleComposerMediaSelect(event)">
                                </label>
                            </div>
                            <button type="submit" class="btn-fb-primary" id="btnPagePostSubmit" style="display: inline-flex; align-items: center; gap: 6px;">
                                @include('pages.partials.icon', ['name' => 'send', 'size' => 16])
                                <span id="btnPagePostSubmitText">পোস্ট প্রকাশ করুন</span>
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <!-- Page Overview Banner for Visitors -->
                <div class="fb-card" style="margin-bottom: 20px; padding: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                        <div>
                            <h2 style="font-size: 18px; font-weight: 800; color: var(--fb-text-primary); margin: 0 0 4px;">{{ $page->name }} এ স্বাগতম</h2>
                            <p style="font-size: 13.5px; color: var(--fb-text-secondary); margin: 0; line-height: 1.45;">
                                {{ $page->bio ?: ($page->description ? Str::limit($page->description, 140) : 'অফিসিয়াল পেজে সংযুক্ত থেকে সকল আপডেট ও পোস্ট জানুন।') }}
                            </p>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn-fb-primary" onclick="handlePageFollowToggle()" style="font-size: 13px; padding: 8px 16px;">
                                <span>{{ $isFollowing ? 'ফলোয়িং (Following)' : 'ফলো করুন (Follow)' }}</span>
                            </button>
                            <button type="button" class="btn-fb-secondary" onclick="openPageMessageModal()" style="font-size: 13px; padding: 8px 16px;">
                                <span>মেসেজ</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Live Stream Target -->
            <div id="liveStreamTarget">
                @if(isset($activeLiveStream) && $activeLiveStream && $activeLiveStream->status === \App\Models\LiveStream::STATUS_LIVE)
                    <div class="post-card" id="pageActiveLiveStreamCard" style="border: 2px solid #ef4444; box-shadow: 0 4px 16px rgba(239, 68, 68, 0.15); margin-bottom: 16px;">
                        <div class="post-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="post-author-info">
                                <div class="avatar" style="width: 42px; height: 42px;">
                                    @if($page->avatar_url)
                                        <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}">
                                    @else
                                        {{ mb_substr($page->name, 0, 1) }}
                                    @endif
                                </div>
                                <div>
                                    <div style="font-weight: 700; font-size: 15px; color: var(--fb-text-primary); display: flex; align-items: center; gap: 6px;">
                                        <span>{{ $page->name }}</span>
                                        <span style="background: #ef4444; color: #fff; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 4px; letter-spacing: 0.5px; animation: pulse 1.5s infinite;">LIVE NOW</span>
                                    </div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary); margin-top: 2px;">
                                        Started {{ $activeLiveStream->started_at ? $activeLiveStream->started_at->diffForHumans() : 'just now' }} • 👁️ <span id="viewerActiveLiveCount">{{ $activeLiveStream->viewers_count ?? 0 }}</span> viewers
                                    </div>
                                </div>
                            </div>
                            @if($canManage)
                                <button type="button" class="btn-fb-secondary" onclick="openLiveStudioModal()" style="font-size: 12px; padding: 5px 12px; font-weight: 600;">
                                    Studio
                                </button>
                            @endif
                        </div>
                        <div style="padding: 0 16px 12px 16px; font-size: 15px; font-weight: 600; color: var(--fb-text-primary);">
                            {{ $activeLiveStream->title }}
                        </div>
                        <div style="position: relative; background: #000; overflow: hidden; border-radius: 6px; margin: 0 16px 16px 16px;">
                            <video id="activePageLivePlayer" controls autoplay playsinline style="width: 100%; max-height: 480px; display: block;" poster="{{ $activeLiveStream->thumbnail ?: '' }}">
                                @if($activeLiveStream->playback_url)
                                    <source src="{{ $activeLiveStream->playback_url }}" type="application/x-mpegURL">
                                @endif
                                Your browser does not support live video playback.
                            </video>
                        </div>
                        <div style="padding: 0 16px 16px 16px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--fb-border); padding-top: 12px;">
                            <div style="font-size: 12px; color: var(--fb-text-secondary); display: flex; align-items: center; gap: 6px;">
                                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></span>
                                <span>Realtime Live Stream Broadcast</span>
                            </div>
                            <button class="post-action-btn" onclick="openSharePostModal({{ $activeLiveStream->generated_post_id ?: 0 }}, '{{ addslashes($activeLiveStream->title) }}')">
                                @include('pages.partials.icon', ['name' => 'share-2', 'size' => 18])
                                <span>শেয়ার</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Featured / Pinned Content Card -->
            @if(isset($featuredPost) && $featuredPost)
                <div style="margin-bottom: 20px; border: 2px solid #1877F2; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(24, 119, 242, 0.1);">
                    <div style="background: linear-gradient(90deg, #1877F2, #3b82f6); color: white; padding: 8px 16px; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                        <span>📌</span>
                        <span>ফিচার্ড পোস্ট (Featured Highlight)</span>
                    </div>
                    @include('pages.partials.post-item', ['post' => $featuredPost, 'page' => $page, 'canManage' => $canManage, 'currentUser' => $currentUser])
                </div>
            @endif

            <!-- Recent Media Showcase -->
            @if(isset($photoItems) && $photoItems->isNotEmpty())
                <div class="fb-card" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                        <h3 style="font-size: 16px; font-weight: 800; color: var(--fb-text-primary); margin: 0; display: flex; align-items: center; gap: 6px;">
                            <span>🖼️</span>
                            <span>সাম্প্রতিক ছবি (Recent Photos)</span>
                        </h3>
                        <button type="button" class="btn-fb-secondary" onclick="switchPageTab('photos')" style="font-size: 12.5px; padding: 5px 12px;">
                            সব ছবি দেখুন ({{ $photoItems->total() }}) →
                        </button>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px;">
                        @foreach($photoItems->take(6) as $media)
                            @php
                                $mUrl = $media->getVariantUrl('large') ?: ($media->getVariantUrl('original') ?: ($media->url ?: asset('storage/' . $media->original_path)));
                                $caption = $media->mediable?->content ?: 'Photo';
                            @endphp
                            <div style="aspect-ratio: 1/1; border-radius: 8px; overflow: hidden; background: #000; cursor: pointer;" onclick="openPhotoLightbox('{{ $mUrl }}', '{{ addslashes($caption) }}', '{{ $media->created_at ? $media->created_at->diffForHumans() : 'Recently' }}', {{ $media->id }})">
                                <img src="{{ $mUrl }}" alt="Photo" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Recent Page Posts -->
            <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 17px; font-weight: 800; color: var(--fb-text-primary); margin: 0;">
                    সাম্প্রতিক পোস্টসমূহ (Latest Updates)
                </h3>
                <button type="button" class="btn-fb-secondary" onclick="switchPageTab('posts')" style="font-size: 12.5px; padding: 5px 12px;">
                    সকল পোস্ট ({{ $posts->total() }}) →
                </button>
            </div>

            @if($posts && $posts->isNotEmpty())
                @foreach($posts->take(5) as $post)
                    @include('pages.partials.post-item', ['post' => $post, 'page' => $page, 'canManage' => $canManage, 'currentUser' => $currentUser])
                @endforeach
                @if($posts->total() > 5)
                    <div style="text-align: center; margin: 16px 0 24px 0;">
                        <button type="button" class="btn-fb-secondary" onclick="switchPageTab('posts')" style="padding: 10px 24px; font-size: 14px; font-weight: 700;">
                            সকল পোস্ট দেখুন ({{ $posts->total() }}) →
                        </button>
                    </div>
                @endif
            @else
                <div class="fb-card" style="text-align: center; padding: 48px; color: var(--fb-text-secondary);">
                    <div style="color: var(--fb-text-secondary); margin-bottom: 12px; display: flex; justify-content: center;">
                        @include('pages.partials.icon', ['name' => 'file-text', 'size' => 44])
                    </div>
                    <div style="font-size: 17px; font-weight: 700; color: var(--fb-text-primary);">এই পেইজে এখনও কোনো পোস্ট নেই</div>
                    <div style="font-size: 13px; margin-top: 4px;">নতুন কোনো ঘোষণা বা আপডেট প্রকাশ করা হলে এখানে দেখা যাবে।</div>
                </div>
            @endif
        </div>

        <!-- TAB: POSTS -->
        <div id="tab-pane-posts" class="page-tab-pane" style="{{ $activeTab === 'posts' ? '' : 'display: none;' }}">
            <!-- Filter Bar for Posts -->
            <div class="fb-card" style="margin-bottom: 16px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="btn-fb-secondary {{ ($postFilter ?? 'all') === 'all' ? 'active-filter' : '' }}" onclick="switchPostFilter('all')" style="font-size: 13px; padding: 6px 14px; font-weight: 600; {{ ($postFilter ?? 'all') === 'all' ? 'background: #1877F2; color: #fff;' : '' }}">
                        সকল পোস্ট (All)
                    </button>
                    <button type="button" class="btn-fb-secondary {{ ($postFilter ?? '') === 'photos' ? 'active-filter' : '' }}" onclick="switchPostFilter('photos')" style="font-size: 13px; padding: 6px 14px; font-weight: 600; {{ ($postFilter ?? '') === 'photos' ? 'background: #1877F2; color: #fff;' : '' }}">
                        🖼️ ছবিযুক্ত পোস্ট (Photos)
                    </button>
                    <button type="button" class="btn-fb-secondary {{ ($postFilter ?? '') === 'videos' ? 'active-filter' : '' }}" onclick="switchPostFilter('videos')" style="font-size: 13px; padding: 6px 14px; font-weight: 600; {{ ($postFilter ?? '') === 'videos' ? 'background: #1877F2; color: #fff;' : '' }}">
                        📹 ভিডিওযুক্ত পোস্ট (Videos)
                    </button>
                    <button type="button" class="btn-fb-secondary {{ ($postFilter ?? '') === 'pinned' ? 'active-filter' : '' }}" onclick="switchPostFilter('pinned')" style="font-size: 13px; padding: 6px 14px; font-weight: 600; {{ ($postFilter ?? '') === 'pinned' ? 'background: #1877F2; color: #fff;' : '' }}">
                        📌 পিন করা পোস্ট (Pinned)
                    </button>
                </div>
                <div style="font-size: 13px; font-weight: 700; color: var(--fb-text-secondary);">
                    মোট {{ number_format($posts->total()) }} টি পোস্ট
                </div>
            </div>

            <!-- Existing Posts Stream -->
            @if($posts && $posts->isNotEmpty())
                @foreach($posts as $post)
                    @include('pages.partials.post-item', ['post' => $post, 'page' => $page, 'canManage' => $canManage, 'currentUser' => $currentUser])
                @endforeach

                <div style="margin-top: 16px;">
                    {{ $posts->links() }}
                </div>
            @else
                <div class="fb-card" style="text-align: center; padding: 48px; color: var(--fb-text-secondary);">
                    <div style="color: var(--fb-text-secondary); margin-bottom: 12px; display: flex; justify-content: center;">
                        @include('pages.partials.icon', ['name' => 'file-text', 'size' => 44])
                    </div>
                    <div style="font-size: 17px; font-weight: 700; color: var(--fb-text-primary);">কোনো পোস্ট খুঁজে পাওয়া যায়নি</div>
                    <div style="font-size: 13px; margin-top: 4px;">নির্বাচিত ফিল্টারের সাথে মিলে এমন কোনো পোস্ট নেই।</div>
                    <button type="button" class="btn-fb-secondary" onclick="switchPostFilter('all')" style="margin-top: 14px; font-size: 13px; padding: 8px 16px;">
                        সব পোস্ট দেখুন
                    </button>
                </div>
            @endif
        </div>

        <!-- TAB: ABOUT -->
        <div id="tab-pane-about" class="page-tab-pane" style="{{ $activeTab === 'about' ? '' : 'display: none;' }}">
            <div class="fb-card" style="max-width: 900px; margin: 0 auto;">
                <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 20px; border-bottom: 1px solid var(--fb-border); padding-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    @include('pages.partials.icon', ['name' => 'info', 'size' => 20])
                    <span>পেইজের সম্পূর্ণ পরিচিতি ও বিবরণ</span>
                </h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                    <!-- Column 1: Basic Info -->
                    <div>
                        <h3 style="font-size: 15px; font-weight: 700; color: var(--fb-primary); margin-bottom: 12px;">সাধারণ পরিচিতি</h3>
                        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px;">
                            <div><strong>পেইজের নাম:</strong> {{ $page->name }}</div>
                            <div><strong>ইউজারনেম / হ্যান্ডেল:</strong> {{ '@' . ($page->username ?: $page->slug) }}</div>
                            <div><strong>ক্যাটাগরি:</strong> {{ $page->category }} {{ $page->sub_category ? '• ' . $page->sub_category : '' }}</div>
                            <div><strong>ভেরিফিকেশন স্ট্যাটাস:</strong> {{ $page->is_verified ? '✓ ভেরিফাইড প্রাতিষ্ঠানিক পেইজ' : 'রেগুলার পেইজ' }}</div>
                            <div>
                                <strong>সংক্ষিপ্ত পরিচিতি:</strong>
                                <p style="margin-top: 4px; color: var(--fb-text-secondary); line-height: 1.5;">{{ $page->bio ?: 'কোনো সংক্ষিপ্ত পরিচিতি দেওয়া নেই।' }}</p>
                            </div>
                            @if($page->description)
                                <div>
                                    <strong>বিস্তারিত বিবরণ:</strong>
                                    <p style="margin-top: 4px; color: var(--fb-text-secondary); line-height: 1.5;">{{ $page->description }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Business Hours Section -->
                        <div style="margin-top: 20px;">
                            <h3 style="font-size: 15px; font-weight: 700; color: var(--fb-primary); margin-bottom: 10px;">ব্যবসায়িক কর্মঘণ্টা (Business Hours)</h3>
                            @if(!empty($page->business_hours) && is_array($page->business_hours))
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    @foreach($page->business_hours as $day => $hours)
                                        <div style="display: flex; justify-content: space-between; font-size: 13px; border-bottom: 1px dashed var(--fb-border); padding-bottom: 4px;">
                                            <span style="font-weight: 600; text-transform: capitalize;">{{ $day }}:</span>
                                            <span style="color: var(--fb-text-secondary);">
                                                @if(is_array($hours))
                                                    {{ ($hours['open'] ?? '') . ' - ' . ($hours['close'] ?? '') }}
                                                @else
                                                    {{ $hours }}
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif(!empty($page->business_hours) && is_string($page->business_hours))
                                <p style="margin-top: 4px; color: var(--fb-text-secondary); line-height: 1.5;">{{ $page->business_hours }}</p>
                            @else
                                <p style="margin-top: 4px; color: var(--fb-text-secondary); font-style: italic; font-size: 13px;">ব্যবসায়িক কর্মঘণ্টা নির্ধারিত নেই</p>
                            @endif
                        </div>
                    </div>

                    <!-- Column 2: Contact & Location (Privacy-Aware) -->
                    <div>
                        <h3 style="font-size: 15px; font-weight: 700; color: var(--fb-primary); margin-bottom: 12px;">যোগাযোগ ও ঠিকানা</h3>
                        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px;">
                            <!-- Website -->
                            <div>
                                <strong>ওয়েবসাইট:</strong>
                                @if($page->website)
                                    <a href="{{ Str::startsWith($page->website, ['http://', 'https://']) ? $page->website : 'https://' . $page->website }}" target="_blank" rel="noopener noreferrer" style="color: var(--fb-primary); font-weight: 600; margin-left: 4px;">
                                        {{ $page->website }} ↗
                                    </a>
                                @else
                                    <span style="color: var(--fb-text-secondary); font-style: italic; margin-left: 4px;">নির্ধারিত নেই</span>
                                @endif
                            </div>

                            <!-- Email with Privacy Check -->
                            @php
                                $hideEmail = (bool) ($page->privacy_settings['hide_email'] ?? false);
                            @endphp
                            <div>
                                <strong>ইমেইল:</strong>
                                @if($hideEmail && !$canManage)
                                    <span style="color: var(--fb-text-secondary); font-style: italic; margin-left: 4px;">গোপনীয় (Private)</span>
                                @elseif($page->email)
                                    <span style="margin-left: 4px;">{{ $page->email }}</span>
                                    @if($hideEmail && $canManage)
                                        <span style="font-size: 11px; background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">পাবলিকের কাছে লুকানো</span>
                                    @endif
                                @else
                                    <span style="color: var(--fb-text-secondary); font-style: italic; margin-left: 4px;">নির্ধারিত নেই</span>
                                @endif
                            </div>

                            <!-- Phone with Privacy Check -->
                            @php
                                $hidePhone = (bool) ($page->privacy_settings['hide_phone'] ?? false);
                            @endphp
                            <div>
                                <strong>ফোন নম্বর:</strong>
                                @if($hidePhone && !$canManage)
                                    <span style="color: var(--fb-text-secondary); font-style: italic; margin-left: 4px;">গোপনীয় (Private)</span>
                                @elseif($page->phone)
                                    <span style="margin-left: 4px;">{{ $page->phone }}</span>
                                    @if($hidePhone && $canManage)
                                        <span style="font-size: 11px; background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">পাবলিকের কাছে লুকানো</span>
                                    @endif
                                @else
                                    <span style="color: var(--fb-text-secondary); font-style: italic; margin-left: 4px;">নির্ধারিত নেই</span>
                                @endif
                            </div>

                            <!-- Address with Privacy Check -->
                            @php
                                $hideAddress = (bool) ($page->privacy_settings['hide_address'] ?? false);
                            @endphp
                            <div>
                                <strong>ঠিকানা:</strong>
                                @if($hideAddress && !$canManage)
                                    <span style="color: var(--fb-text-secondary); font-style: italic; margin-left: 4px;">গোপনীয় (Private)</span>
                                @elseif($page->address)
                                    <span style="margin-left: 4px;">{{ $page->address }}</span>
                                    @if($hideAddress && $canManage)
                                        <span style="font-size: 11px; background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">পাবলিকের কাছে লুকানো</span>
                                    @endif
                                @else
                                    <span style="color: var(--fb-text-secondary); font-style: italic; margin-left: 4px;">নির্ধারিত নেই</span>
                                @endif
                            </div>

                            @if($page->city)
                                <div><strong>শহর:</strong> {{ $page->city }}</div>
                            @endif
                            @if($page->country)
                                <div><strong>দেশ:</strong> {{ $page->country }}</div>
                            @endif
                            @if($page->zip_code)
                                <div><strong>পোস্টাল কোড:</strong> {{ $page->zip_code }}</div>
                            @endif
                        </div>

                        @if(!empty($page->social_links) && is_array($page->social_links))
                            <div style="margin-top: 20px;">
                                <h3 style="font-size: 15px; font-weight: 700; color: var(--fb-primary); margin-bottom: 10px;">সোশ্যাল মিডিয়া লিংক</h3>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    @foreach($page->social_links as $network => $url)
                                        @if(!empty($url))
                                            <a href="{{ $url }}" target="_blank" rel="noopener" class="btn-fb-secondary" style="font-size: 12px; text-transform: capitalize;">
                                                {{ $network }} ↗
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: PHOTOS & MEDIA -->
        <div id="tab-pane-photos" class="page-tab-pane" style="{{ $activeTab === 'photos' ? '' : 'display: none;' }}">
            <div class="fb-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
                    <h2 style="font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 8px; margin: 0;">
                        @include('pages.partials.icon', ['name' => 'image', 'size' => 20])
                        <span>আপলোডকৃত ছবি ও গ্যালারি ({{ $photoItems->total() }})</span>
                        <span style="display: none;">আপলোডকৃত ছবি ও ভিডিও গ্যালারি</span>
                    </h2>
                    @if($canManage)
                        <button type="button" class="btn-fb-primary" onclick="openPhotoUploadModal()" style="font-size: 13px; padding: 7px 14px; display: inline-flex; align-items: center; gap: 6px;">
                            @include('pages.partials.icon', ['name' => 'plus', 'size' => 14])
                            <span>+ ছবি আপলোড করুন</span>
                        </button>
                    @endif
                </div>

                @if($photoItems->isNotEmpty())
                    <div class="gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px;">
                        @foreach($photoItems as $media)
                            @php
                                $photoUrl = $media->getVariantUrl('large') ?: ($media->getVariantUrl('original') ?: ($media->url ?: asset('storage/' . $media->original_path)));
                                $postCaption = $media->mediable?->content ?? '';
                            @endphp
                            <div class="gallery-item" style="position: relative; aspect-ratio: 1; border-radius: 8px; overflow: hidden; cursor: pointer; background: #000; border: 1px solid var(--fb-border);" onclick="openPhotoLightbox('{{ $photoUrl }}', '{{ addslashes($postCaption) }}', '{{ $media->created_at?->diffForHumans() }}', {{ $media->id }})">
                                <img src="{{ $photoUrl }}" alt="Page Photo" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.2s ease;">
                            </div>
                        @endforeach
                    </div>

                    @if($photoItems->hasPages())
                        <div style="margin-top: 24px; display: flex; justify-content: center;">
                            {{ $photoItems->appends(['tab' => 'photos'])->links() }}
                        </div>
                    @endif
                @else
                    <div style="text-align: center; padding: 48px; color: var(--fb-text-secondary);">
                        <div style="color: var(--fb-text-secondary); margin-bottom: 8px; display: flex; justify-content: center;">
                            @include('pages.partials.icon', ['name' => 'image', 'size' => 44])
                        </div>
                        <div style="font-weight: 700; font-size: 16px;">এখনও কোনো ছবি আপলোড করা হয়নি</div>
                        <div style="font-size: 13px; margin-top: 4px;">পেইজে ছবি আপলোড করলে এখানে পূর্ণ গ্যালারি আকারে প্রদর্শিত হবে।</div>
                        @if($canManage)
                            <button type="button" class="btn-fb-primary" onclick="openPhotoUploadModal()" style="margin-top: 14px; font-size: 13px; padding: 8px 16px;">
                                প্রথম ছবি আপলোড করুন
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

<!-- TAB: VIDEOS -->
<div id="tab-pane-videos" class="page-tab-pane" style="{{ $activeTab === 'videos' ? '' : 'display: none;' }}">
    <div class="fb-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
            <h2 style="font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 8px; margin: 0;">
                @include('pages.partials.icon', ['name' => 'video', 'size' => 20])
                <span>Page Videos ({{ $mediaItems->filter(fn($m) => str_starts_with($m->mime_type ?? '', 'video/'))->count() + $reels->count() }})</span>
            </h2>
            @if($canManage)
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn-fb-primary" onclick="openReelCreatorModal()" style="font-size: 13px; padding: 7px 14px;">
                        <span>+ Create Reel</span>
                    </button>
                    <button type="button" class="btn-fb-secondary" onclick="focusPageComposerWithMedia()" style="font-size: 13px; padding: 7px 14px;">
                        <span>+ Upload Video</span>
                    </button>
                </div>
            @endif
        </div>

        @php
            $videoItems = $mediaItems->filter(fn($m) => str_starts_with($m->mime_type ?? '', 'video/'));
        @endphp

        @if($videoItems->isNotEmpty() || $reels->isNotEmpty())
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;">
                @foreach($videoItems as $media)
                    @php
                        $videoUrl = $media->getVariantUrl('original') ?: ($media->url ?: asset('storage/' . $media->original_path));
                        $thumbUrl = $media->thumbnail_url ?: asset('storage/' . ($media->thumbnail_path ?? $media->original_path));
                        $postObj = $media->mediable_type === App\Models\Post::class ? App\Models\Post::find($media->mediable_id) : null;
                    @endphp
                    <div style="background: var(--fb-surface); border: 1px solid var(--fb-border); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column;">
                        <div style="position: relative; aspect-ratio: 16/9; background: #000; cursor: pointer;" onclick="openVideoPlayer('{{ $videoUrl }}', '{{ addslashes($postObj?->content ?? 'Page Video') }}', '{{ isset($media->metadata['duration']) ? gmdate('i:s', (int)$media->metadata['duration']) : 'Video' }}', {{ (int)($postObj?->views_count ?? 0) }}, {{ (int)($postObj?->likes_count ?? 0) }})">
                            <video src="{{ $videoUrl }}" style="width: 100%; height: 100%; object-fit: cover;" preload="metadata"></video>
                            <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center;">
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(0,0,0,0.6); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px;">▶</div>
                            </div>
                            @if(isset($media->metadata['duration']))
                                <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 4px;">{{ gmdate('i:s', (int)$media->metadata['duration']) }}</span>
                            @endif
                        </div>
                        <div style="padding: 12px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="font-weight: 700; font-size: 13.5px; color: var(--fb-text-primary); line-height: 1.4; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    {{ $postObj?->content ? Str::limit($postObj->content, 65) : 'Page Video Upload' }}
                                </div>
                                <div style="font-size: 12px; color: var(--fb-text-secondary); display: flex; gap: 10px;">
                                    <span>{{ number_format((int)($postObj?->views_count ?? 0)) }} views</span>
                                    <span>•</span>
                                    <span>{{ $media->created_at ? $media->created_at->diffForHumans() : 'Recently' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                @foreach($reels as $reel)
                    @php
                        $primary = $reel->media->firstWhere('quality', 'original') ?? $reel->media->first();
                        $rUrl = $primary?->video_url ?? ($reel->video_url ?? ($primary?->original_path ? asset('storage/' . $primary->original_path) : null));
                    @endphp
                    <div style="background: var(--fb-surface); border: 1px solid var(--fb-border); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column;">
                        <div style="position: relative; aspect-ratio: 9/16; max-height: 320px; background: #000; cursor: pointer;" onclick="openVideoPlayer('{{ $rUrl }}', '{{ addslashes($reel->caption ?? 'Reel') }}', '{{ $reel->duration ? gmdate('i:s', (int)$reel->duration) : '0:30' }}', {{ (int)$reel->views_count }}, {{ (int)$reel->likes_count }})">
                            @if($reel->thumbnail_url)
                                <img src="{{ $reel->thumbnail_url }}" alt="Reel Thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1877f2, #8b5cf6); color: #fff;">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                </div>
                            @endif
                            <div style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.6); color: #fff; border-radius: 4px; padding: 2px 6px; font-size: 11px; font-weight: 700;">Reel</div>
                            <div style="position: absolute; bottom: 8px; left: 8px; color: #fff; font-size: 12px; font-weight: 700; text-shadow: 0 1px 3px rgba(0,0,0,0.8);">
                                ▶ {{ number_format((int)$reel->views_count) }}
                            </div>
                        </div>
                        <div style="padding: 10px;">
                            <div style="font-weight: 600; font-size: 13px; color: var(--fb-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $reel->caption ?: 'Reel clip' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 48px; color: var(--fb-text-secondary);">
                <div style="color: var(--fb-text-secondary); margin-bottom: 8px; display: flex; justify-content: center;">
                    @include('pages.partials.icon', ['name' => 'video', 'size' => 44])
                </div>
                <div style="font-weight: 700; font-size: 16px;">এখনও কোনো ভিডিও প্রকাশ করা হয়নি</div>
                <div style="font-size: 13px; margin-top: 4px;">ভিডিও বা রিল আপলোড করলে এখানে প্রদর্শিত হবে।</div>
            </div>
        @endif
    </div>
</div>

<!-- TAB: REELS -->
<div id="tab-pane-reels" class="page-tab-pane" style="{{ $activeTab === 'reels' ? '' : 'display: none;' }}">
    <div class="fb-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
            <h2 style="font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 8px; margin: 0;">
                <span style="font-size: 22px;">🎬</span>
                <span>Page Reels ({{ $reels->count() }})</span>
            </h2>
            @if($canManage)
                <button type="button" class="btn-fb-primary" onclick="openReelCreatorModal()" style="font-size: 13px; padding: 7px 14px;">
                    <span>+ Create Reel</span>
                </button>
            @endif
        </div>

        @if($reels->isNotEmpty())
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px;">
                @foreach($reels as $reel)
                    @php
                        $primary = $reel->media->firstWhere('quality', 'original') ?? $reel->media->first();
                        $rUrl = $primary?->video_url ?? ($reel->video_url ?? ($primary?->original_path ? asset('storage/' . $primary->original_path) : null));
                    @endphp
                    <div style="position: relative; aspect-ratio: 9/16; border-radius: 12px; overflow: hidden; background: #000; cursor: pointer; border: 1px solid var(--fb-border);" onclick="openVideoPlayer('{{ $rUrl }}', '{{ addslashes($reel->caption ?? 'Page Reel') }}', '{{ $reel->duration ? gmdate('i:s', (int)$reel->duration) : '0:30' }}', {{ (int)$reel->views_count }}, {{ (int)$reel->likes_count }})">
                        @if($reel->thumbnail_url)
                            <img src="{{ $reel->thumbnail_url }}" alt="Reel" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1877f2, #ec4899); color: #fff;">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                            </div>
                        @endif
                        <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.1), transparent 40%, rgba(0,0,0,0.85)); display: flex; flex-direction: column; justify-content: space-between; padding: 10px;">
                            <div style="display: flex; justify-content: flex-end;">
                                <span style="background: rgba(0,0,0,0.6); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px;">{{ $reel->privacy }}</span>
                            </div>
                            <div>
                                <div style="color: #fff; font-size: 13px; font-weight: 600; line-height: 1.3; margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-shadow: 0 1px 3px rgba(0,0,0,0.8);">
                                    {{ $reel->caption ?: 'Reel clip' }}
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px; color: #cbd5e1; font-size: 11.5px; font-weight: 600;">
                                    <span>▶ {{ number_format((int)$reel->views_count) }}</span>
                                    <span>❤️ {{ number_format((int)$reel->likes_count) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 48px; color: var(--fb-text-secondary);">
                <div style="font-size: 40px; margin-bottom: 8px;">🎬</div>
                <div style="font-weight: 700; font-size: 16px;">এখনও কোনো রিল প্রকাশ করা হয়নি</div>
                <div style="font-size: 13px; margin-top: 4px;">শর্ট-ফর্ম ৯:১৬ রিল আপলোড করলে এখানে প্রদর্শিত হবে।</div>
                @if($canManage)
                    <button type="button" class="btn-fb-primary" onclick="openReelCreatorModal()" style="margin-top: 14px; font-size: 13px; padding: 8px 16px;">
                        প্রথম রিল তৈরি করুন
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- TAB: FOLLOWERS -->
<div id="tab-pane-followers" class="page-tab-pane" style="{{ $activeTab === 'followers' ? '' : 'display: none;' }}">
    <div class="fb-card">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
            @include('pages.partials.icon', ['name' => 'users', 'size' => 20])
            <span>পেইজ ফলোয়ারবৃন্দ ({{ number_format($page->followers_count) }})</span>
        </h2>

        @if($followers->isNotEmpty())
            <div class="followers-grid">
                @foreach($followers as $follower)
                    @if($follower->user)
                        <div class="follower-card">
                            <div class="avatar" style="width: 48px; height: 48px;">
                                @if($follower->user->profile?->avatar_url)
                                    <img src="{{ $follower->user->profile->avatar_url }}" alt="{{ $follower->user->name }}">
                                @else
                                    {{ mb_substr($follower->user->name, 0, 1) }}
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <a href="{{ getUserProfileUrl($follower->user) }}" style="font-weight: 700; font-size: 14px; color: var(--fb-text-primary); text-decoration: none; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $follower->user->name }}
                                </a>
                                <div style="font-size: 12px; color: var(--fb-text-secondary);">
                                    {{ '@' . $follower->user->username }}
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 48px; color: var(--fb-text-secondary);">
                <div style="color: var(--fb-text-secondary); margin-bottom: 8px; display: flex; justify-content: center;">
                    @include('pages.partials.icon', ['name' => 'users', 'size' => 44])
                </div>
                <div style="font-weight: 700; font-size: 16px;">এখনও কোনো ফলোয়ার নেই</div>
                <div style="font-size: 13px;">প্রথম ব্যক্তি হিসেবে এই পেইজটি ফলো করুন!</div>
            </div>
        @endif
    </div>
</div>

<!-- TAB: EVENTS -->
<div id="tab-pane-events" class="page-tab-pane" style="{{ $activeTab === 'events' ? '' : 'display: none;' }}">
    <div class="fb-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
            <h2 style="font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 8px; margin: 0;">
                @include('pages.partials.icon', ['name' => 'calendar', 'size' => 20])
                <span>পেইজের আনুষ্ঠানিক ইভেন্টস ({{ $events->count() }})</span>
            </h2>
            @if($canManage)
                <button type="button" class="btn-fb-primary" onclick="openCreateEventModal()" style="font-size: 13px; padding: 7px 14px; display: inline-flex; align-items: center; gap: 6px;">
                    @include('pages.partials.icon', ['name' => 'plus', 'size' => 14])
                    <span>+ ইভেন্ট তৈরি করুন</span>
                </button>
            @endif
        </div>

        @if($events->isNotEmpty())
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                @foreach($events as $event)
                    @php
                        $isGoing = $currentUser ? $event->rsvps->where('user_id', $currentUser->id)->where('status', 'going')->isNotEmpty() : false;
                        $rsvpCount = $event->rsvp_count ?? $event->rsvps->where('status', 'going')->count();
                    @endphp
                    <div style="border: 1px solid var(--fb-border); border-radius: 12px; padding: 16px; background: var(--fb-surface, #ffffff); display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--shadow-sm);">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 8px;">
                                <div style="font-size: 12px; font-weight: 700; color: #059669; text-transform: uppercase;">
                                    {{ $event->start_time ? $event->start_time->format('d M, Y • h:i A') : 'তারিখ নির্ধারিত হয়নি' }}
                                </div>
                                @if($event->is_online)
                                    <span style="font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0284c7; padding: 2px 7px; border-radius: 6px;">🌐 অনলাইন</span>
                                @endif
                            </div>

                            <h4 style="font-size: 16px; font-weight: 800; margin: 0 0 6px; color: var(--fb-text-primary);">{{ $event->title }}</h4>

                            <div style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                                @include('pages.partials.icon', ['name' => 'map-pin', 'size' => 14])
                                <span>{{ $event->location ?: ($event->is_online ? 'ভার্চুয়াল মিটিং' : 'স্থান নির্ধারিত হয়নি') }}</span>
                            </div>

                            @if($event->description)
                                <p style="font-size: 13px; line-height: 1.5; color: var(--fb-text-secondary); margin-bottom: 14px;">
                                    {{ Str::limit($event->description, 120) }}
                                </p>
                            @endif
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 12.5px; color: var(--fb-text-secondary);">
                                <span id="event-rsvp-count-{{ $event->id }}" style="font-weight: 700;">{{ $rsvpCount }} জন অংশ নিচ্ছেন</span>
                            </div>

                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button class="{{ $isGoing ? 'btn-fb-secondary' : 'btn-fb-primary' }}" id="event-rsvp-btn-{{ $event->id }}" style="flex: 1; justify-content: center; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px;" onclick="handleEventRsvp({{ $event->id }})">
                                    <span>{{ $isGoing ? '✓ Going (অংশ নিচ্ছেন)' : '+ Going (অংশ নিন)' }}</span>
                                </button>

                                @if($canManage)
                                    <button type="button" class="btn-fb-secondary" onclick="openEditEventModal({{ $event->id }}, '{{ addslashes($event->title) }}', '{{ addslashes($event->description ?? '') }}', '{{ addslashes($event->location ?? '') }}', '{{ $event->start_time?->format('Y-m-d\TH:i') }}', '{{ $event->end_time?->format('Y-m-d\TH:i') }}', {{ $event->is_online ? 1 : 0 }})" style="font-size: 12px; padding: 7px 10px;" title="ইভেন্ট সম্পাদনা">
                                        ✏️
                                    </button>
                                    <button type="button" class="btn-fb-secondary" onclick="handleDeleteEvent({{ $event->id }})" style="font-size: 12px; padding: 7px 10px; color: #ef4444;" title="ইভেন্ট মুছুন">
                                        🗑️
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 48px; color: var(--fb-text-secondary);">
                <div style="color: var(--fb-text-secondary); margin-bottom: 8px; display: flex; justify-content: center;">
                    @include('pages.partials.icon', ['name' => 'calendar', 'size' => 44])
                </div>
                <div style="font-weight: 700; font-size: 16px;">বর্তমানে কোনো নির্ধারিত ইভেন্ট নেই</div>
                <div style="font-size: 13px; margin-top: 4px;">ভবিষ্যতের কোনো ইভেন্ট আয়োজন করা হলে এখানে পাওয়া যাবে।</div>
                @if($canManage)
                    <button type="button" class="btn-fb-primary" onclick="openCreateEventModal()" style="margin-top: 14px; font-size: 13px; padding: 8px 16px;">
                        প্রথম ইভেন্ট তৈরি করুন
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- TAB: SHOP & PRODUCTS -->
<div id="tab-pane-shop" class="page-tab-pane" style="{{ $activeTab === 'shop' ? '' : 'display: none;' }}">
    <div class="fb-card">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
            @include('pages.partials.icon', ['name' => 'shopping-bag', 'size' => 20])
            <span>পেইজের পণ্য ও সেবা সম্ভার</span>
        </h2>

        @if($products->isNotEmpty())
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
                @foreach($products as $product)
                    <div style="border: 1px solid var(--fb-border); border-radius: 10px; overflow: hidden; background: var(--fb-bg); display: flex; flex-direction: column;">
                        <div style="height: 140px; background: #e2e8f0;">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @endif
                        </div>
                        <div style="padding: 14px; flex: 1; display: flex; flex-direction: column;">
                            <h4 style="font-size: 15px; font-weight: 800; margin: 0 0 6px;">{{ $product->name }}</h4>
                            <div style="font-size: 16px; font-weight: 800; color: #059669; margin-bottom: 8px;">
                                ৳ {{ number_format($product->price, 2) }}
                            </div>
                            <p style="font-size: 12px; color: var(--fb-text-secondary); line-height: 1.4; margin-bottom: 14px; flex: 1;">
                                {{ Str::limit($product->description, 80) }}
                            </p>
                            <button class="btn-fb-primary" style="width: 100%; justify-content: center; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;" onclick="openPageMessageModal('{{ addslashes($product->name) }} পণ্যটি সম্পর্কে বিস্তারিত জানতে চাই')">
                                @include('pages.partials.icon', ['name' => 'message-square', 'size' => 14])
                                <span>মেসেজে অর্ডার করুন</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 48px; color: var(--fb-text-secondary);">
                <div style="color: var(--fb-text-secondary); margin-bottom: 8px; display: flex; justify-content: center;">
                    @include('pages.partials.icon', ['name' => 'shopping-bag', 'size' => 44])
                </div>
                <div style="font-weight: 700; font-size: 16px;">বর্তমানে কোনো পণ্য তালিকায় নেই</div>
                <div style="font-size: 13px;">পেইজ অ্যাডমিন পণ্য যুক্ত করলে এখানে প্রদর্শিত হবে।</div>
            </div>
        @endif
    </div>
</div>
    </main>

    <!-- ============================================================== -->
    <!-- COLUMN 3: RIGHT SIDEBAR (PAGE INFO, ACTIONS, INSIGHTS, ACTIVITY) -->
    <!-- ============================================================== -->
    <aside class="sh-col-right" aria-label="Page Information and Insights">
        <!-- 1. Page Information Card -->
        <div class="right-widget-card">
            <div style="display: flex; gap: 12px; align-items: flex-start; margin-bottom: 12px;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #1877F2; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; flex-shrink: 0; overflow: hidden;">
                    @if($page->avatar_url)
                        <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        📖
                    @endif
                </div>
                <div>
                    <div style="font-size: 16px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 4px;">
                        <span>{{ $page->name }}</span>
                        <span style="color: #1877F2;" title="Verified">✓</span>
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 1px;">{{ $page->category ?: 'Technology' }}</div>
                </div>
            </div>

            <p style="font-size: 13px; color: #475569; line-height: 1.45; margin-bottom: 14px;">
                {{ $page->bio ?: ($page->description ? Str::limit($page->description, 140) : 'Connecting people, ideas and opportunities. Join our community and be part of something bigger!') }}
            </p>

            <div style="border-top: 1px solid var(--fb-border, #e2e8f0); padding-top: 12px; display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: #334155;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span>👥</span>
                    <span>{{ number_format($page->followers_count) }} followers</span>
                </div>
                @if($page->city || $page->country || $page->address)
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span>📍</span>
                        <span>{{ $page->address ?: ($page->city ? $page->city . ($page->country ? ', ' . $page->country : '') : $page->country) }}</span>
                    </div>
                @endif
                @if(!empty($page->business_hours))
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span>🕒</span>
                        <span>{{ is_array($page->business_hours) ? ($page->business_hours['formatted'] ?? ($page->business_hours['text'] ?? 'Business Hours Configured')) : $page->business_hours }}</span>
                    </div>
                @endif
                @if($page->website)
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span>🌐</span>
                        <a href="{{ $page->website }}" target="_blank" rel="noopener" style="color: #1877F2; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ parse_url($page->website, PHP_URL_HOST) ?: $page->website }}</a>
                    </div>
                @endif
            </div>

            <div style="margin-top: 14px;">
                <a href="/messages?page={{ $page->id }}" class="btn-fb-secondary" style="width: 100%; justify-content: center; display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; text-decoration: none; padding: 9px;">
                    <span>💬</span>
                    <span>Message Page</span>
                </a>
            </div>
        </div>

        <!-- 2. Quick Actions Card -->
        <div class="right-widget-card">
            <div class="right-widget-title">
                <span>Quick Actions</span>
            </div>
            <div class="quick-actions-grid">
                <div class="quick-action-item" onclick="focusPageComposer()">
                    <div class="icon-circle">✍️</div>
                    <span>Create Post</span>
                </div>
                <div class="quick-action-item" onclick="focusPageComposerWithMedia()">
                    <div class="icon-circle" style="background: #fdf2f8; color: #db2777;">📷</div>
                    <span>Photo</span>
                </div>
                <div class="quick-action-item" onclick="handleQuickActionEvent()">
                    <div class="icon-circle" style="background: #fef3c7; color: #d97706;">📅</div>
                    <span>Event</span>
                </div>
                <div class="quick-action-item" onclick="openInviteFriendsModal()">
                    <div class="icon-circle" style="background: #f0fdf4; color: #16a34a;">👥</div>
                    <span>Invite Friends</span>
                </div>
            </div>
        </div>

        <!-- 3. Page Insights Card -->
        <div class="right-widget-card">
            <div class="right-widget-title">
                <span>Page Insights</span>
                <span style="font-size: 12px; color: #64748b; font-weight: 500;">Last 7 days ▾</span>
            </div>

            <div class="insights-metric-grid">
                <div class="insights-metric-box">
                    <div class="insights-metric-val">{{ $sevenDayInsights['reach'] ?? '0' }}</div>
                    <div class="insights-metric-label">Page Reach</div>
                    <div class="insights-growth">{{ $sevenDayInsights['reach_growth'] ?? '+0%' }}</div>
                </div>
                <div class="insights-metric-box">
                    <div class="insights-metric-val">{{ $sevenDayInsights['engagement'] ?? '0' }}</div>
                    <div class="insights-metric-label">Engagement</div>
                    <div class="insights-growth">{{ $sevenDayInsights['engagement_growth'] ?? '+0%' }}</div>
                </div>
                <div class="insights-metric-box">
                    <div class="insights-metric-val">{{ $sevenDayInsights['new_followers'] ?? '0' }}</div>
                    <div class="insights-metric-label">New Followers</div>
                    <div class="insights-growth">{{ $sevenDayInsights['followers_growth'] ?? '+0%' }}</div>
                </div>
            </div>

            <!-- Legend -->
            <div style="display: flex; gap: 14px; font-size: 11px; color: #64748b; margin-bottom: 8px;">
                <div style="display: flex; align-items: center; gap: 4px;">
                    <span style="width: 10px; height: 3px; background: #1877F2; display: inline-block; border-radius: 2px;"></span>
                    <span>Reach</span>
                </div>
                <div style="display: flex; align-items: center; gap: 4px;">
                    <span style="width: 10px; height: 3px; background: #10b981; display: inline-block; border-radius: 2px;"></span>
                    <span>Followers</span>
                </div>
            </div>

            <!-- Sparkline SVG Chart -->
            <div style="background: #f8fafc; border-radius: 8px; padding: 10px; border: 1px solid var(--fb-border, #e2e8f0);">
                <svg viewBox="0 0 320 80" style="width: 100%; height: 75px; overflow: visible;">
                    <line x1="0" y1="20" x2="320" y2="20" stroke="#f1f5f9" stroke-width="1" />
                    <line x1="0" y1="50" x2="320" y2="50" stroke="#f1f5f9" stroke-width="1" />
                    <!-- Blue Reach Polyline -->
                    <polyline fill="none" stroke="#1877F2" stroke-width="2.5" points="10,65 55,70 105,52 155,56 205,38 255,42 305,15" />
                    <circle cx="10" cy="65" r="3" fill="#1877F2" />
                    <circle cx="55" cy="70" r="3" fill="#1877F2" />
                    <circle cx="105" cy="52" r="3" fill="#1877F2" />
                    <circle cx="155" cy="56" r="3" fill="#1877F2" />
                    <circle cx="205" cy="38" r="3" fill="#1877F2" />
                    <circle cx="255" cy="42" r="3" fill="#1877F2" />
                    <circle cx="305" cy="15" r="4" fill="#1877F2" />
                    <!-- Green Followers Polyline -->
                    <polyline fill="none" stroke="#10b981" stroke-width="2" stroke-dasharray="3,3" points="10,55 55,58 105,48 155,45 205,40 255,30 305,25" />
                    <circle cx="305" cy="25" r="3.5" fill="#10b981" />
                </svg>
                <div style="display: flex; justify-content: space-between; font-size: 10px; color: #94a3b8; margin-top: 4px;">
                    <span>Oct 10</span>
                    <span>Oct 11</span>
                    <span>Oct 12</span>
                    <span>Oct 13</span>
                    <span>Oct 14</span>
                    <span>Oct 15</span>
                    <span>Oct 16</span>
                </div>
            </div>
        </div>

        <!-- 4. Recent Activity Card -->
        <div class="right-widget-card">
            <div class="right-widget-title">
                <span>Recent Activity</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @if(isset($recentActivity) && count($recentActivity) > 0)
                    @foreach($recentActivity as $act)
                        <div style="display: flex; gap: 10px; align-items: flex-start; font-size: 13px;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: #e2e8f0; flex-shrink: 0;">
                                @if(!empty($act['avatar_url']))
                                    <img src="{{ $act['avatar_url'] }}" alt="{{ $act['user_name'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #475569; font-size: 12px;">{{ mb_substr($act['user_name'] ?? 'U', 0, 1) }}</div>
                                @endif
                            </div>
                            <div style="flex: 1;">
                                <div style="color: #1e293b; line-height: 1.35;">{{ $act['text'] }}</div>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">{{ $act['time_ago'] }}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div style="color: #64748b; font-size: 13px; text-align: center; padding: 12px 0;">
                        কোনো সাম্প্রতিক অ্যাক্টিভিটি পাওয়া যায়নি।
                    </div>
                @endif
            </div>
        </div>

        <!-- 5. Related Pages Card -->
        <div class="right-widget-card">
            <div class="right-widget-title">
                <span>Related Pages</span>
                <a href="/pages" style="font-size: 12px; color: #1877F2; text-decoration: none; font-weight: 600;">See All</a>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @if(isset($relatedPages) && count($relatedPages) > 0)
                    @foreach($relatedPages as $rp)
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px; overflow: hidden;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden; background: #e2e8f0; flex-shrink: 0;">
                                    @if($rp->avatar_url)
                                        <img src="{{ $rp->avatar_url }}" alt="{{ $rp->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #475569; font-size: 13px;">{{ mb_substr($rp->name, 0, 1) }}</div>
                                    @endif
                                </div>
                                <div style="overflow: hidden;">
                                    <div style="font-size: 13px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $rp->name }}</div>
                                    <div style="font-size: 11px; color: #64748b;">{{ number_format($rp->followers_count) }} followers</div>
                                </div>
                            </div>
                            <button type="button" class="btn-fb-secondary" style="font-size: 12px; padding: 5px 12px; font-weight: 600;" onclick="handleToggleFollow({{ $rp->id }}, this)">Follow</button>
                        </div>
                    @endforeach
                @else
                    <div style="color: #64748b; font-size: 13px; text-align: center; padding: 8px 0;">
                        কোনো সম্পর্কিত পেজ নেই।
                    </div>
                @endif
            </div>
        </div>

        <!-- 6. Photos & Media Snapshot -->
        @if($mediaItems->isNotEmpty())
            <div class="right-widget-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="font-weight: 700; font-size: 14px; margin: 0; color: #0f172a;">Photos & Media</h3>
                    <a href="javascript:void(0)" onclick="switchPageTab('photos')" style="font-size: 12px; color: #1877F2; text-decoration: none; font-weight: 600;">See All</a>
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
                    @foreach($mediaItems->take(6) as $m)
                        <div style="aspect-ratio: 1; border-radius: 6px; overflow: hidden; background: #eee;">
                            <img src="{{ $m->getVariantUrl('medium') ?: ($m->getVariantUrl('original') ?: ($m->url ?: asset('storage/' . $m->original_path))) }}" alt="Media" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </aside>
</div> <!-- end of sh-page-layout-3col -->

<!-- 3. SHARE MODAL -->
<div class="page-modal-backdrop" id="socialShareModal">
    <div class="page-modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-weight: 800; font-size: 17px; margin: 0;">শেয়ার করুন</h3>
            <button onclick="closeShareModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary); display: flex; align-items: center;">
                @include('pages.partials.icon', ['name' => 'x', 'size' => 20])
            </button>
        </div>
        <div style="padding: 20px;">
            <div id="sharePostPreviewText" style="padding: 10px; background: var(--fb-bg); border-radius: 8px; font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 16px; border: 1px solid var(--fb-border);"></div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button class="btn-fb-primary" id="btnShareTimeline" style="width: 100%; justify-content: center; padding: 12px; display: inline-flex; align-items: center; gap: 8px;">
                    @include('pages.partials.icon', ['name' => 'share-2', 'size' => 16])
                    <span>আপনার টাইমলাইনে শেয়ার করুন</span>
                </button>
                <button class="btn-fb-secondary" id="btnShareMessenger" style="width: 100%; justify-content: center; padding: 12px; display: inline-flex; align-items: center; gap: 8px;">
                    @include('pages.partials.icon', ['name' => 'message-square', 'size' => 16])
                    <span>মেসেঞ্জারে বন্ধুদের পাঠান</span>
                </button>
                <button class="btn-fb-secondary" id="btnShareCopyLink" style="width: 100%; justify-content: center; padding: 12px; display: inline-flex; align-items: center; gap: 8px;">
                    @include('pages.partials.icon', ['name' => 'copy', 'size' => 16])
                    <span>লিঙ্ক কপি করুন</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 4. MESSAGE TO PAGE MODAL -->
<div class="page-modal-backdrop" id="pageMessageModal" style="display: none; align-items: center; justify-content: center; z-index: 1000;">
    <div class="page-modal-box" style="max-width: 520px; width: 95%; max-height: 85vh; display: flex; flex-direction: column; border-radius: 12px; overflow: hidden; background: #ffffff; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <!-- Modal Header -->
        <div style="padding: 14px 18px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #1877f2;">
                    @if($page->avatar_url)
                        <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        {{ mb_substr($page->name, 0, 1) }}
                    @endif
                </div>
                <div>
                    <h3 style="font-weight: 800; font-size: 15px; margin: 0; color: var(--fb-text-primary); display: flex; align-items: center; gap: 4px;">
                        <span>{{ $page->name }}</span>
                        @if($page->is_verified)
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="#1877f2"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                        @endif
                    </h3>
                    <div style="font-size: 11px; color: #16a34a; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #16a34a; display: inline-block;"></span>
                        <span>Direct Page Messaging • বার্তা পাঠান</span>
                    </div>
                </div>
            </div>
            <button onclick="closePageMessageModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary); display: flex; align-items: center;" title="Close">
                @include('pages.partials.icon', ['name' => 'x', 'size' => 20])
            </button>
        </div>

        <!-- Chat History List -->
        <div id="pageMessageHistory" style="flex: 1; min-height: 260px; max-height: 380px; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; background: #f0f2f5;">
            <div id="pageMessageLoading" style="text-align: center; padding: 30px; color: #64748b; font-size: 13px;">
                <div class="spinner" style="display: inline-block; width: 22px; height: 22px; border: 2px solid #cbd5e1; border-top-color: #1877f2; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 6px;"></div>
                <div>Loading conversation...</div>
            </div>
            <div id="pageMessageEmpty" style="display: none; text-align: center; padding: 40px 20px; color: #64748b;">
                <div style="font-size: 32px; margin-bottom: 8px;">💬</div>
                <div style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Start a conversation with {{ $page->name }} (বার্তা পাঠান)</div>
                <div style="font-size: 12px;">Send your questions or inquiries and our team will get back to you.</div>
            </div>
            <div id="pageMessageItems" style="display: flex; flex-direction: column; gap: 8px;"></div>
        </div>

        <!-- Message Input Box -->
        <form onsubmit="handleSendPageMessage(event)" style="padding: 12px 16px; background: #ffffff; border-top: 1px solid var(--fb-border); display: flex; align-items: center; gap: 8px;">
            <textarea id="pageMessageBody" rows="1" class="search-input" style="flex: 1; height: 40px; min-height: 40px; max-height: 100px; resize: none; border-radius: 20px; border: 1px solid var(--fb-border); padding: 9px 16px; font-size: 13.5px; outline: none;" placeholder="Write a message to {{ $page->name }}..." required onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); handleSendPageMessage(event); }"></textarea>
            <button type="submit" class="btn-fb-primary" id="btnSendPageMessage" style="border-radius: 20px; padding: 8px 18px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; height: 40px;">
                <span>বার্তা পাঠান</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
        </form>
    </div>
</div>

<!-- 4C. INVITE FRIENDS MODAL -->
<div class="page-modal-backdrop" id="inviteFriendsModal" style="display: none; align-items: center; justify-content: center; z-index: 1000;">
    <div class="page-modal-box" style="max-width: 540px; width: 95%; max-height: 85vh; display: flex; flex-direction: column; border-radius: 12px; overflow: hidden; background: #ffffff; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <!-- Header -->
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div>
                <h3 style="font-weight: 800; font-size: 17px; margin: 0; color: var(--fb-text-primary);">Invite Friends to Follow {{ $page->name }}</h3>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Help this page grow by inviting your friends</div>
            </div>
            <button onclick="closeInviteFriendsModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary); display: flex; align-items: center;">
                @include('pages.partials.icon', ['name' => 'x', 'size' => 20])
            </button>
        </div>

        <!-- Search & Filter Controls -->
        <div style="padding: 12px 18px; border-bottom: 1px solid var(--fb-border); background: #ffffff; display: flex; gap: 10px; align-items: center;">
            <div style="position: relative; flex: 1;">
                <input type="text" id="inviteFriendSearch" placeholder="Search friends by name or username..." oninput="filterInviteFriendsList()" style="width: 100%; padding: 8px 12px 8px 34px; border-radius: 20px; border: 1px solid var(--fb-border); font-size: 13px; outline: none;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
            <button type="button" class="btn-fb-secondary" id="btnSelectAllInvites" onclick="toggleSelectAllInvites()" style="font-size: 12px; padding: 7px 12px; font-weight: 600; white-space: nowrap;">Select All</button>
        </div>

        <!-- Friends List -->
        <div id="inviteFriendsList" style="flex: 1; max-height: 380px; overflow-y: auto; padding: 12px 18px; display: flex; flex-direction: column; gap: 8px;">
            <div id="inviteFriendsLoading" style="text-align: center; padding: 30px; color: #64748b; font-size: 13px;">
                <div class="spinner" style="display: inline-block; width: 20px; height: 20px; border: 2px solid #cbd5e1; border-top-color: #1877f2; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 6px;"></div>
                <div>Finding eligible friends...</div>
            </div>
            <div id="inviteFriendsEmpty" style="display: none; text-align: center; padding: 30px 10px; color: #64748b; font-size: 13px;">
                <div style="font-size: 26px; margin-bottom: 6px;">✨</div>
                <div>All your eligible friends are already following this page or have active invitations!</div>
            </div>
            <div id="inviteFriendsItems" style="display: flex; flex-direction: column; gap: 8px;"></div>
        </div>

        <!-- Footer -->
        <div style="padding: 12px 18px; border-top: 1px solid var(--fb-border); background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" class="btn-fb-secondary" onclick="copyPageLink()" style="font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                <span>Copy Page Link</span>
            </button>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn-fb-secondary" onclick="closeInviteFriendsModal()" style="font-size: 13px;">Cancel</button>
                <button type="button" class="btn-fb-primary" id="btnSubmitInvitations" onclick="submitPageInvitations()" style="font-size: 13px; font-weight: 700; padding: 7px 18px;" disabled>Send Invitations (0)</button>
            </div>
        </div>
    </div>
</div>

<!-- 4D. CREATE EVENT MODAL -->
@if($canManage)
<div class="page-modal-backdrop" id="createEventModal" style="display: none; align-items: center; justify-content: center; z-index: 1000;">
    <div class="page-modal-box" style="max-width: 560px; width: 95%; max-height: 88vh; display: flex; flex-direction: column; border-radius: 12px; overflow: hidden; background: #ffffff; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <div style="padding: 16px 22px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">📅</span>
                <h3 style="font-weight: 800; font-size: 17px; margin: 0; color: var(--fb-text-primary);">Create Page Event</h3>
            </div>
            <button onclick="closeCreateEventModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary); display: flex; align-items: center;">
                @include('pages.partials.icon', ['name' => 'x', 'size' => 20])
            </button>
        </div>
        <form onsubmit="handleCreatePageEvent(event)" style="padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px;">
            <div>
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">Event Name <span style="color: #ef4444;">*</span></label>
                <input type="text" id="eventTitle" required class="search-input" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px 12px; font-size: 13.5px;" placeholder="e.g. Official Grand Opening, Webinar, Workshop">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">Start Date & Time <span style="color: #ef4444;">*</span></label>
                    <input type="datetime-local" id="eventStartTime" required class="search-input" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 10px; font-size: 13px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">End Date & Time</label>
                    <input type="datetime-local" id="eventEndTime" class="search-input" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 10px; font-size: 13px;">
                </div>
            </div>
            <div>
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">Location / Streaming Link</label>
                <input type="text" id="eventLocation" class="search-input" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px 12px; font-size: 13.5px;" placeholder="e.g. Dhaka, Bangladesh or Online Google Meet / Zoom link">
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="eventIsOnline" value="1" style="width: 16px; height: 16px; cursor: pointer;">
                <label for="eventIsOnline" style="font-size: 13px; font-weight: 600; cursor: pointer; color: var(--fb-text-primary);">This is an online virtual event</label>
            </div>
            <div>
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">Description</label>
                <textarea id="eventDescription" rows="3" class="search-input" style="width: 100%; height: auto; border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px 12px; font-size: 13px;" placeholder="Provide details about the schedule, agenda, or guest speakers..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px;">
                <button type="button" class="btn-fb-secondary" onclick="closeCreateEventModal()">Cancel</button>
                <button type="submit" class="btn-fb-primary" id="btnSubmitCreateEvent" style="font-weight: 700;">Publish Event</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- PHOTO LIGHTBOX MODAL -->
<div class="page-modal-backdrop" id="photoLightboxModal" style="display: none; align-items: center; justify-content: center; z-index: 1050; background: rgba(0,0,0,0.88);">
    <div style="position: relative; max-width: 90vw; max-height: 90vh; display: flex; flex-direction: column; align-items: center;">
        <button type="button" onclick="closePhotoLightbox()" style="position: absolute; top: -38px; right: 0; background: none; border: none; color: #fff; font-size: 28px; cursor: pointer; line-height: 1; padding: 4px;">✕</button>
        <img id="photoLightboxImage" src="" alt="Full view" style="max-width: 85vw; max-height: 75vh; object-fit: contain; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; margin-top: 12px; color: #fff; background: rgba(0,0,0,0.6); padding: 10px 16px; border-radius: 8px; gap: 12px;">
            <div>
                <div id="photoLightboxCaption" style="font-size: 14px; font-weight: 600;"></div>
                <div id="photoLightboxDate" style="font-size: 12px; color: #cbd5e1; margin-top: 2px;"></div>
            </div>
            @if($canManage)
                <button type="button" id="photoLightboxDeleteBtn" onclick="handleDeletePhotoFromLightbox()" class="btn-fb-secondary" style="background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 12px; padding: 6px 12px; cursor: pointer;">
                    মুছে ফেলুন (Delete)
                </button>
            @endif
        </div>
    </div>
</div>

<!-- PHOTO UPLOAD MODAL -->
@if($canManage)
<div class="page-modal-backdrop" id="photoUploadModal" style="display: none; align-items: center; justify-content: center; z-index: 1000;">
    <div class="page-modal-box" style="max-width: 520px; width: 95%; max-height: 88vh; display: flex; flex-direction: column; border-radius: 12px; overflow: hidden; background: #ffffff; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">🖼️</span>
                <h3 style="font-weight: 800; font-size: 17px; margin: 0; color: var(--fb-text-primary);">ছবি আপলোড করুন</h3>
            </div>
            <button type="button" onclick="closePhotoUploadModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary); display: flex; align-items: center;">✕</button>
        </div>
        <form onsubmit="handlePhotoUploadSubmit(event)" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
            <div>
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">ছবি নির্বাচন করুন <span style="color: #ef4444;">*</span></label>
                <input type="file" id="pagePhotoFileInput" accept="image/*" multiple required style="width: 100%; border: 1px dashed var(--fb-border); border-radius: 8px; padding: 18px 12px; font-size: 13px; background: #f8fafc; cursor: pointer;">
            </div>
            <div>
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">ক্যাপশন বা বিবরণ (ঐচ্ছিক)</label>
                <textarea id="pagePhotoCaptionInput" rows="3" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px 12px; font-size: 13px; resize: vertical;" placeholder="ছবি সম্পর্কে কিছু লিখুন..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px;">
                <button type="button" class="btn-fb-secondary" onclick="closePhotoUploadModal()">বাতিল</button>
                <button type="submit" class="btn-fb-primary" id="btnSubmitPhotoUpload" style="font-weight: 700;">ছবি আপলোড করুন</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- EDIT EVENT MODAL -->
@if($canManage)
<div class="page-modal-backdrop" id="editEventModal" style="display: none; align-items: center; justify-content: center; z-index: 1000;">
    <div class="page-modal-box" style="max-width: 560px; width: 95%; max-height: 88vh; display: flex; flex-direction: column; border-radius: 12px; overflow: hidden; background: #ffffff; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <div style="padding: 16px 22px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">📅</span>
                <h3 style="font-weight: 800; font-size: 17px; margin: 0; color: var(--fb-text-primary);">ইভেন্ট সম্পাদনা করুন</h3>
            </div>
            <button type="button" onclick="closeEditEventModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary); display: flex; align-items: center;">✕</button>
        </div>
        <form onsubmit="handleUpdatePageEvent(event)" style="padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px;">
            <input type="hidden" id="editEventId">
            <div>
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">Event Name <span style="color: #ef4444;">*</span></label>
                <input type="text" id="editEventTitle" required style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px 12px; font-size: 13.5px;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">Start Date & Time <span style="color: #ef4444;">*</span></label>
                    <input type="datetime-local" id="editEventStartTime" required style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 10px; font-size: 13px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">End Date & Time</label>
                    <input type="datetime-local" id="editEventEndTime" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 10px; font-size: 13px;">
                </div>
            </div>
            <div>
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">Location / Streaming Link</label>
                <input type="text" id="editEventLocation" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px 12px; font-size: 13.5px;">
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="editEventIsOnline" value="1" style="width: 16px; height: 16px; cursor: pointer;">
                <label for="editEventIsOnline" style="font-size: 13px; font-weight: 600; cursor: pointer; color: var(--fb-text-primary);">This is an online virtual event</label>
            </div>
            <div>
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">Description</label>
                <textarea id="editEventDescription" rows="3" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px 12px; font-size: 13px;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px;">
                <button type="button" class="btn-fb-secondary" onclick="closeEditEventModal()">বাতিল</button>
                <button type="submit" class="btn-fb-primary" id="btnSubmitUpdateEvent" style="font-weight: 700;">পরিবর্তন সংরক্ষণ করুন</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- EDIT POST MODAL -->
@if($canManage)
<div class="page-modal-backdrop" id="editPostModal" style="display: none; align-items: center; justify-content: center; z-index: 1000;">
    <div class="page-modal-box" style="max-width: 520px; width: 95%; max-height: 88vh; display: flex; flex-direction: column; border-radius: 12px; overflow: hidden; background: #ffffff; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">✏️</span>
                <h3 style="font-weight: 800; font-size: 17px; margin: 0; color: var(--fb-text-primary);">পোস্ট সম্পাদনা করুন</h3>
            </div>
            <button type="button" onclick="closeEditPostModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary); display: flex; align-items: center;">✕</button>
        </div>
        <form onsubmit="submitEditPostForm(event)" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
            <input type="hidden" id="editPostModalId">
            <div>
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--fb-text-primary);">পোস্টের বিষয়বস্তু</label>
                <textarea id="editPostModalContent" rows="5" required style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 10px 12px; font-size: 14px; line-height: 1.5; resize: vertical;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px;">
                <button type="button" class="btn-fb-secondary" onclick="closeEditPostModal()">বাতিল</button>
                <button type="submit" class="btn-fb-primary" id="btnSubmitEditPost" style="font-weight: 700;">সংরক্ষণ করুন</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- 4B. EDIT PAGE DETAILS MODAL -->
@if($canManage)
<div class="page-modal-backdrop" id="editPageDetailsModal" style="display: none; align-items: center; justify-content: center; z-index: 1000;">
    <div class="page-modal-box" style="max-width: 680px; width: 95%; max-height: 90vh; display: flex; flex-direction: column; border-radius: 12px; overflow: hidden; background: #ffffff; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <div style="padding: 16px 22px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1877f2" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                <h3 style="font-weight: 800; font-size: 18px; margin: 0; color: var(--fb-text-primary);">Edit Page Profile</h3>
            </div>
            <button type="button" onclick="closeEditPageModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: var(--fb-text-secondary); line-height: 1;">✕</button>
        </div>

        <form id="editPageDetailsForm" onsubmit="handleSavePageDetails(event)" style="padding: 20px 24px; overflow-y: auto; flex: 1;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Page Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="editPageName" class="search-input" value="{{ $page->name }}" required style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Username (@handle)</label>
                    <input type="text" id="editPageUsername" class="search-input" value="{{ $page->username }}" placeholder="mybrand" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Category <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="editPageCategory" class="search-input" value="{{ $page->category }}" required style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Sub Category</label>
                    <input type="text" id="editPageSubCategory" class="search-input" value="{{ $page->sub_category }}" placeholder="e.g. Software, Apparel" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Bio / Tagline</label>
                <textarea id="editPageBio" rows="2" class="search-input" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px; resize: vertical;" placeholder="A brief hook about your page...">{{ $page->bio }}</textarea>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Short Description</label>
                <textarea id="editPageShortDesc" rows="2" class="search-input" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px; resize: vertical;" placeholder="Quick overview for previews">{{ $page->short_description }}</textarea>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Full Description / About Story</label>
                <textarea id="editPageDescription" rows="3" class="search-input" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px; resize: vertical;" placeholder="Comprehensive story, mission, and services...">{{ $page->description }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Website</label>
                    <input type="url" id="editPageWebsite" class="search-input" value="{{ $page->website }}" placeholder="https://example.com" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Email</label>
                    <input type="email" id="editPageEmail" class="search-input" value="{{ $page->email }}" placeholder="contact@example.com" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Phone</label>
                    <input type="text" id="editPagePhone" class="search-input" value="{{ $page->phone }}" placeholder="+880 1..." style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">City & Country</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="editPageCity" class="search-input" value="{{ $page->city }}" placeholder="City" style="flex: 1; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;">
                        <input type="text" id="editPageCountry" class="search-input" value="{{ $page->country }}" placeholder="Country" style="flex: 1; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;">
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Street Address</label>
                    <input type="text" id="editPageAddress" class="search-input" value="{{ $page->address }}" placeholder="Road, House, Area" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Zip Code</label>
                    <input type="text" id="editPageZipCode" class="search-input" value="{{ $page->zip_code }}" placeholder="1212" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Page Visibility</label>
                    <select id="editPageVisibility" class="search-input" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px; background: #fff;">
                        <option value="public" {{ $page->visibility === 'public' ? 'selected' : '' }}>Public (Searchable by everyone)</option>
                        <option value="unlisted" {{ $page->visibility === 'unlisted' ? 'selected' : '' }}>Unlisted (Accessible by link only)</option>
                        <option value="private" {{ $page->visibility === 'private' ? 'selected' : '' }}>Private (Page team members only)</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Call-to-Action Button</label>
                    <select id="editPageCtaType" class="search-input" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px; background: #fff;">
                        <option value="none" {{ empty($page->cta_type) || $page->cta_type === 'none' ? 'selected' : '' }}>None</option>
                        <option value="contact_us" {{ $page->cta_type === 'contact_us' ? 'selected' : '' }}>Contact Us</option>
                        <option value="send_message" {{ $page->cta_type === 'send_message' ? 'selected' : '' }}>Send Message</option>
                        <option value="learn_more" {{ $page->cta_type === 'learn_more' ? 'selected' : '' }}>Learn More</option>
                        <option value="shop_now" {{ $page->cta_type === 'shop_now' ? 'selected' : '' }}>Shop Now</option>
                        <option value="book_now" {{ $page->cta_type === 'book_now' ? 'selected' : '' }}>Book Now</option>
                    </select>
                </div>
            </div>

            <!-- Visual Media Management -->
            <div style="padding: 14px; border-radius: 10px; background: #f8fafc; border: 1px solid var(--fb-border); margin-bottom: 16px;">
                <div style="font-weight: 800; font-size: 13.5px; margin-bottom: 10px; color: var(--fb-text-primary);">Branding Media Controls</div>
                <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <label class="btn-fb-secondary" style="cursor: pointer; padding: 6px 12px; font-size: 12.5px; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                            <span>Change Avatar</span>
                            <input type="file" accept="image/*" style="display: none;" onchange="uploadPageBrandingImage('avatar', event)">
                        </label>
                        @if($page->avatar_url)
                            <button type="button" onclick="removePageBranding('avatar')" style="background: none; border: none; color: #dc2626; font-size: 12.5px; cursor: pointer; text-decoration: underline;">Remove Avatar</button>
                        @endif
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <label class="btn-fb-secondary" style="cursor: pointer; padding: 6px 12px; font-size: 12.5px; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            <span>Change Cover</span>
                            <input type="file" accept="image/*" style="display: none;" onchange="uploadPageBrandingImage('cover', event)">
                        </label>
                        @if($page->cover_image_url)
                            <button type="button" onclick="removePageBranding('cover')" style="background: none; border: none; color: #dc2626; font-size: 12.5px; cursor: pointer; text-decoration: underline;">Remove Cover</button>
                        @endif
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid var(--fb-border);">
                <button type="button" class="btn-fb-secondary" onclick="closeEditPageModal()" style="padding: 9px 18px; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn-fb-primary" id="btnSavePageDetails" style="background: #1877f2; color: #ffffff; padding: 9px 24px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer;">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- 5. LIVE STUDIO MODAL (WebRTC Real Video Broadcasting) -->
<div class="page-modal-backdrop" id="liveStudioModal" style="display: none; z-index: 9999;">
    <div class="page-modal-box" style="max-width: 680px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="color: #ef4444; font-size: 20px;">🔴</span>
                <h3 style="font-weight: 800; font-size: 17px; margin: 0;">Live Broadcast Studio — {{ $page->name }}</h3>
                <span id="liveConnectionStatusBadge" style="font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; background: #64748b; color: #fff; text-transform: uppercase; letter-spacing: 0.5px;">Idle</span>
            </div>
            <button onclick="closeLiveStudioModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);">✕</button>
        </div>
        <div style="padding: 20px;">
            <!-- Setup details (visible before going live) -->
            <div id="liveStreamSetupFields" style="margin-bottom: 16px;">
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Broadcast Title *</label>
                    <input type="text" id="liveStreamTitle" class="search-input" style="width: 100%; border: 1px solid var(--fb-border); border-radius: 8px; padding: 9px 12px; font-size: 14px;" value="Live Broadcast from {{ $page->name }}" placeholder="What is this live broadcast about?">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 5px;">Audience</label>
                        <select id="liveStreamPrivacy" class="search-input" style="width: 100%; height: 38px; border: 1px solid var(--fb-border); border-radius: 8px; padding: 0 10px;">
                            <option value="public">Public (Everyone)</option>
                            <option value="followers">Followers Only</option>
                            <option value="only_me">Only Admins</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; padding-top: 22px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" id="liveSaveRecording" checked style="width: 16px; height: 16px;">
                            <span>Record & Publish Video</span>
                        </label>
                    </div>
                </div>

                <!-- Hardware Device Selection -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border);">
                    <div>
                        <label style="display: block; font-weight: 700; font-size: 12px; margin-bottom: 4px; color: var(--fb-text-secondary);">📷 Camera Device</label>
                        <select id="liveCameraSelect" onchange="switchCameraDevice()" style="width: 100%; font-size: 12.5px; padding: 6px; border-radius: 6px; border: 1px solid var(--fb-border); background: #fff;">
                            <option value="">Default Camera</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 700; font-size: 12px; margin-bottom: 4px; color: var(--fb-text-secondary);">🎤 Microphone Device</label>
                        <select id="liveMicSelect" onchange="switchMicDevice()" style="width: 100%; font-size: 12.5px; padding: 6px; border-radius: 6px; border: 1px solid var(--fb-border); background: #fff;">
                            <option value="">Default Microphone</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Broadcaster Video Preview & Stream Canvas -->
            <div style="position: relative; border-radius: 10px; overflow: hidden; background: #0f172a; margin-bottom: 16px;">
                <video id="liveStudioVideoPreview" autoplay muted playsinline style="width: 100%; height: 320px; object-fit: cover; display: block;"></video>

                <!-- Permission and Error Notice -->
                <div id="liveStudioOfflineNotice" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; font-size: 13px; gap: 8px; background: #0f172a;">
                    <div style="font-size: 32px;">📷</div>
                    <div id="liveDeviceNoticeText">Requesting camera & microphone permissions...</div>
                </div>

                <!-- Reconnecting Overlay -->
                <div id="liveReconnectingOverlay" style="display: none; position: absolute; inset: 0; background: rgba(0,0,0,0.75); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #f59e0b; font-weight: 700; gap: 8px; z-index: 10;">
                    <div style="font-size: 26px; animation: spin 1.5s linear infinite;">🔄</div>
                    <div>Network interrupted. Reconnecting to streaming transport...</div>
                </div>

                <!-- LIVE NOW Overlay -->
                <div id="liveBadgeOverlay" style="display: none; position: absolute; top: 12px; left: 12px; background: #ef4444; color: #fff; font-weight: 800; font-size: 11px; padding: 4px 10px; border-radius: 6px; letter-spacing: 0.5px; animation: pulse 1.5s infinite; z-index: 5;">
                    LIVE NOW
                </div>

                <!-- Live Viewers Counter Overlay -->
                <div id="liveViewersOverlay" style="display: none; position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.65); color: #fff; font-weight: 700; font-size: 12px; padding: 4px 10px; border-radius: 6px; z-index: 5;">
                    👁️ <span id="liveConnectedViewersCount">0</span> viewers
                </div>
            </div>

            <!-- Controls row -->
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn-fb-secondary" id="btnToggleCamera" onclick="toggleLiveCamera()" style="font-size: 13px; padding: 8px 12px;">
                        <span>📷</span> <span id="lblToggleCamera">Camera Off</span>
                    </button>
                    <button type="button" class="btn-fb-secondary" id="btnToggleMic" onclick="toggleLiveMic()" style="font-size: 13px; padding: 8px 12px;">
                        <span>🎤</span> <span id="lblToggleMic">Mute</span>
                    </button>
                    <button type="button" class="btn-fb-secondary" id="btnToggleScreen" onclick="toggleLiveScreenShare()" style="font-size: 13px; padding: 8px 12px;">
                        <span>🖥️</span> <span>Screen Share</span>
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-fb-primary" id="btnStartBroadcast" onclick="startLiveBroadcast()" style="background: #ef4444; border-color: #ef4444; font-weight: 700; padding: 10px 20px;">
                        Go Live Now
                    </button>
                    <button type="button" class="btn-fb-secondary" id="btnEndBroadcast" onclick="stopLiveBroadcast()" style="display: none; background: #334155; color: #fff; border: none; font-weight: 700; padding: 10px 20px;">
                        End Broadcast
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5B. SHARE VIDEO / UPLOAD VIDEO MODAL -->
<div class="page-modal-backdrop" id="shareVideoModal" style="display: none; z-index: 9999;">
    <div class="page-modal-box" style="max-width: 560px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">📹</span>
                <h3 style="font-weight: 800; font-size: 17px; margin: 0;">Upload & Share Page Video</h3>
            </div>
            <button onclick="closeShareVideoModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);">✕</button>
        </div>
        <form onsubmit="handleDirectVideoSubmit(event)" style="padding: 20px;">
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Select Video File (MP4, WebM, MOV - Max 100MB) *</label>
                <input type="file" id="directVideoFileInput" accept="video/mp4,video/quicktime,video/webm,video/x-matroska" class="search-input" style="width: 100%; height: auto; padding: 8px;" onchange="handleDirectVideoPreview(event)" required>
            </div>

            <!-- Video Player Preview -->
            <div id="directVideoPreviewContainer" style="display: none; margin-bottom: 14px; border-radius: 8px; overflow: hidden; background: #000;">
                <video id="directVideoPlayerPreview" controls style="width: 100%; max-height: 240px; display: block;"></video>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Optional Custom Thumbnail</label>
                <input type="file" id="directVideoThumbInput" accept="image/jpeg,image/png,image/webp" class="search-input" style="width: 100%; height: auto; padding: 8px;">
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Caption & Description *</label>
                <textarea id="directVideoCaptionInput" rows="3" class="search-input" style="width: 100%; height: auto; border-radius: 8px; border: 1px solid var(--fb-border); padding: 10px;" placeholder="Describe this video, add #tags..." required></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Audience</label>
                    <select id="directVideoAudienceSelect" class="search-input" style="width: 100%; height: 38px;">
                        <option value="public">Public (Everyone)</option>
                        <option value="followers">Followers Only</option>
                        <option value="only_me">Only Admins</option>
                    </select>
                </div>
                <div style="display: flex; align-items: center; padding-top: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" id="directVideoAllowComments" checked style="width: 16px; height: 16px;">
                        <span>Allow Comments</span>
                    </label>
                </div>
            </div>

            <!-- Upload Progress Indicator -->
            <div id="directVideoProgressWrapper" style="display: none; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 4px;">
                    <span id="directVideoStatusText">Uploading & Processing Video...</span>
                    <span id="directVideoProgressPercent">0%</span>
                </div>
                <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                    <div id="directVideoProgressBar" style="width: 0%; height: 100%; background: #1877F2; transition: width 0.2s ease;"></div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-fb-secondary" onclick="closeShareVideoModal()">Cancel</button>
                <button type="submit" class="btn-fb-primary" id="btnPublishDirectVideo">Publish Video</button>
            </div>
        </form>
    </div>
</div>

<!-- 6. REEL CREATOR MODAL -->
<div class="page-modal-backdrop" id="reelCreatorModal" style="display: none;">
    <div class="page-modal-box" style="max-width: 520px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">🎬</span>
                <h3 style="font-weight: 800; font-size: 17px; margin: 0;">Create Page Reel (Vertical 9:16)</h3>
            </div>
            <button onclick="closeReelCreatorModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);">✕</button>
        </div>
        <form onsubmit="handleReelSubmit(event, false)" style="padding: 20px;">
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Vertical Video (MP4, WebM, MOV - Max 100MB) *</label>
                <input type="file" id="reelVideoInput" accept="video/mp4,video/quicktime,video/webm,video/x-matroska" class="search-input" style="width: 100%; height: auto; padding: 8px;" required>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Custom Thumbnail / Cover Image (Optional)</label>
                <input type="file" id="reelCoverInput" accept="image/jpeg,image/png,image/webp" class="search-input" style="width: 100%; height: auto; padding: 8px;">
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Caption & #Hashtags</label>
                <textarea id="reelCaptionInput" rows="3" class="search-input" style="width: 100%; height: auto; border-radius: 8px; border: 1px solid var(--fb-border); padding: 10px;" placeholder="Add caption and #hashtags..."></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Audience</label>
                    <select id="reelPrivacySelect" class="search-input" style="width: 100%; height: 38px;">
                        <option value="public">Public (Everyone)</option>
                        <option value="followers">Followers Only</option>
                        <option value="only_me">Only Admins</option>
                    </select>
                </div>
                <div style="display: flex; align-items: center; padding-top: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" id="reelAllowComments" checked style="width: 16px; height: 16px;">
                        <span>Allow Comments</span>
                    </label>
                </div>
            </div>

            <!-- Upload Progress Indicator -->
            <div id="reelUploadProgressWrapper" style="display: none; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 4px;">
                    <span id="reelUploadStatusText">Uploading & Processing...</span>
                    <span id="reelProgressPercent">0%</span>
                </div>
                <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                    <div id="reelProgressBar" style="width: 0%; height: 100%; background: #1877F2; transition: width 0.2s ease;"></div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-fb-secondary" onclick="closeReelCreatorModal()">Cancel</button>
                <button type="button" class="btn-fb-secondary" id="btnSaveReelDraft" onclick="handleReelSubmit(event, true)">Save Draft</button>
                <button type="submit" class="btn-fb-primary" id="btnPublishReel">Publish Reel</button>
            </div>
        </form>
    </div>
</div>

<!-- 7. STORY CREATOR MODAL -->
<div class="page-modal-backdrop" id="storyCreatorModal" style="display: none;">
    <div class="page-modal-box" style="max-width: 500px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 18px;">📖</span>
                <h3 style="font-weight: 800; font-size: 17px; margin: 0;">Create Page Story (24-Hour Expiration)</h3>
            </div>
            <button onclick="closeStoryCreatorModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);">✕</button>
        </div>
        <form onsubmit="handleStorySubmit(event)" style="padding: 20px;">
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Story Media (Photo or Video - Max 100MB)</label>
                <input type="file" id="storyMediaInput" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm" class="search-input" style="width: 100%; height: auto; padding: 8px;" required>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Optional Caption</label>
                <input type="text" id="storyCaptionInput" class="search-input" style="width: 100%; height: auto; border-radius: 8px; border: 1px solid var(--fb-border); padding: 10px;" placeholder="Add a caption or link...">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Audience</label>
                    <select id="storyPrivacySelect" class="search-input" style="width: 100%; height: 38px;">
                        <option value="public">Public (Everyone)</option>
                        <option value="followers">Followers Only</option>
                        <option value="only_me">Only Admins</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">Background Color</label>
                    <input type="color" id="storyBgColorInput" value="#1877F2" style="width: 100%; height: 38px; border-radius: 6px; border: 1px solid var(--fb-border); cursor: pointer; padding: 2px;">
                </div>
            </div>

            <!-- Upload Progress Indicator -->
            <div id="storyUploadProgressWrapper" style="display: none; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 4px;">
                    <span id="storyUploadStatusText">Publishing Story...</span>
                    <span id="storyProgressPercent">0%</span>
                </div>
                <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                    <div id="storyProgressBar" style="width: 0%; height: 100%; background: #1877F2; transition: width 0.2s ease;"></div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-fb-secondary" onclick="closeStoryCreatorModal()">Cancel</button>
                <button type="submit" class="btn-fb-primary" id="btnPublishStory">Share to Story</button>
            </div>
        </form>
    </div>
</div>

<!-- 8. STORY VIEWER MODAL -->
<div class="page-modal-backdrop" id="storyViewerModal" style="display: none; background: rgba(0,0,0,0.92); z-index: 10000;">
    <div style="position: relative; max-width: 440px; width: 100%; height: 90vh; max-height: 760px; background: #000; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column;">
        <!-- Top bar -->
        <div style="position: absolute; top: 0; left: 0; right: 0; padding: 16px; background: linear-gradient(rgba(0,0,0,0.8), transparent); z-index: 10; display: flex; justify-content: space-between; align-items: center; color: #fff;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 50%; border: 2px solid #1877F2; background: #334155; overflow: hidden; flex-shrink: 0;">
                    <img id="storyViewerAvatar" src="{{ $page->avatar_url }}" alt="Page" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div>
                    <div id="storyViewerName" style="font-weight: 700; font-size: 14px;">{{ $page->name }}</div>
                    <div id="storyViewerTime" style="font-size: 11.5px; opacity: 0.85;">24h active Story</div>
                </div>
            </div>
            <button onclick="closeStoryViewerModal()" style="background: none; border: none; color: #fff; font-size: 26px; cursor: pointer; padding: 4px;">✕</button>
        </div>

        <!-- Media Display (Image or Video) -->
        <div style="flex: 1; display: flex; align-items: center; justify-content: center; background: #0f172a; position: relative; overflow: hidden;">
            <img id="storyViewerImg" src="" alt="Story media" style="display: none; max-width: 100%; max-height: 100%; object-fit: contain;">
            <video id="storyViewerVideo" src="" style="display: none; max-width: 100%; max-height: 100%; object-fit: contain;" autoplay loop playsinline></video>
            <div id="storyViewerTextContent" style="display: none; padding: 30px; color: #fff; font-size: 20px; font-weight: 700; text-align: center; line-height: 1.5; word-break: break-word;"></div>
        </div>

        <!-- Bottom Actions & Views Counter -->
        <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 14px 18px; background: linear-gradient(transparent, rgba(0,0,0,0.85)); z-index: 10; display: flex; justify-content: space-between; align-items: center; color: #fff;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 16px;">👁️</span>
                <span id="storyViewerViewsCount" style="font-size: 13.5px; font-weight: 700;">0 views</span>
            </div>
            <div style="display: flex; gap: 8px;" id="storyManagerControls">
                <button type="button" class="btn-fb-secondary" id="btnStoryViewers" onclick="openStoryViewersModal()" style="font-size: 12px; padding: 6px 12px; background: rgba(255,255,255,0.2); color: #fff; border: none;">
                    Viewers List
                </button>
                <button type="button" class="btn-fb-secondary" id="btnDeleteStory" onclick="deleteCurrentStory()" style="font-size: 12px; padding: 6px 12px; background: rgba(239,68,68,0.8); color: #fff; border: none;">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 9. STORY VIEWERS MODAL (Authorized Viewers List) -->
<div class="page-modal-backdrop" id="storyViewersModal" style="display: none; z-index: 10001;">
    <div class="page-modal-box" style="max-width: 440px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 18px;">👁️</span>
                <h3 style="font-weight: 800; font-size: 16px; margin: 0;">Story Viewers</h3>
            </div>
            <button onclick="closeStoryViewersModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);">✕</button>
        </div>
        <div id="storyViewersList" style="max-height: 400px; overflow-y: auto; padding: 12px 16px;">
            <div style="text-align: center; padding: 20px; color: var(--fb-text-secondary);">Loading viewers...</div>
        </div>
    </div>
</div>

<!-- 10. VIDEO PLAYER MODAL -->
<div class="page-modal-backdrop" id="videoPlayerModal" style="display: none; background: rgba(0,0,0,0.9); z-index: 10000;">
    <div style="position: relative; max-width: 720px; width: 100%; background: #000; border-radius: 14px; overflow: hidden; display: flex; flex-direction: column;">
        <div style="padding: 12px 16px; background: #0f172a; display: flex; justify-content: space-between; align-items: center; color: #fff;">
            <div id="videoPlayerTitle" style="font-weight: 700; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 600px;">Video Playback</div>
            <button onclick="closeVideoPlayerModal()" style="background: none; border: none; color: #fff; font-size: 22px; cursor: pointer;">✕</button>
        </div>
        <div style="aspect-ratio: 16/9; background: #000; display: flex; align-items: center; justify-content: center;">
            <video id="mainVideoPlayerElement" controls autoplay style="width: 100%; height: 100%; object-fit: contain;"></video>
        </div>
        <div style="padding: 12px 16px; background: var(--fb-surface); color: var(--fb-text-primary); display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
            <div style="display: flex; gap: 14px; color: var(--fb-text-secondary);">
                <span id="videoPlayerViews">0 views</span>
                <span id="videoPlayerLikes">0 likes</span>
            </div>
            <button type="button" class="btn-fb-secondary" onclick="closeVideoPlayerModal()" style="font-size: 12px; padding: 5px 12px;">Close</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const PAGE_ID = {{ $page->id }};
    const PAGE_SLUG = '{{ $page->slug }}';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let selectedComposerMediaId = null;
    let activeShareTargetPostId = null;
    let currentStoryId = null;

    // Composer and Publishing Studio Helpers
    function copyPageLink() {
        const url = window.location.href;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(() => {
                alert('Page link copied to clipboard!');
            }).catch(() => {
                prompt('Copy this link:', url);
            });
        } else {
            prompt('Copy this link:', url);
        }
    }

    function focusPageComposer() {
        switchPageTab('posts');
        const input = document.getElementById('pagePostContent');
        if (input) {
            input.focus();
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function focusPageComposerWithMedia() {
        switchPageTab('posts');
        focusPageComposer();
        const mediaInput = document.getElementById('composerMediaInput');
        if (mediaInput) {
            mediaInput.click();
        }
    }

    // Story Helpers
    function openStoryCreatorModal() {
        document.getElementById('storyCreatorModal').style.display = 'flex';
    }
    function closeStoryCreatorModal() {
        document.getElementById('storyCreatorModal').style.display = 'none';
        const prog = document.getElementById('storyUploadProgressWrapper');
        if (prog) prog.style.display = 'none';
    }

    function handleStorySubmit(e) {
        e.preventDefault();
        const fileInput = document.getElementById('storyMediaInput');
        const captionInput = document.getElementById('storyCaptionInput');
        const privacyInput = document.getElementById('storyPrivacySelect');
        const bgColorInput = document.getElementById('storyBgColorInput');

        if (!fileInput || !fileInput.files[0]) {
            alert('Please select an image or video file for your story.');
            return;
        }

        const btn = document.getElementById('btnPublishStory');
        const progressWrapper = document.getElementById('storyUploadProgressWrapper');
        const progressBar = document.getElementById('storyProgressBar');
        const progressPercent = document.getElementById('storyProgressPercent');
        const statusText = document.getElementById('storyUploadStatusText');

        btn.disabled = true;
        btn.textContent = 'Publishing...';
        if (progressWrapper) progressWrapper.style.display = 'block';
        if (progressBar) progressBar.style.width = '0%';
        if (progressPercent) progressPercent.textContent = '0%';
        if (statusText) statusText.textContent = 'Uploading Story media...';

        const formData = new FormData();
        formData.append('media_file', fileInput.files[0]);
        if (captionInput) formData.append('caption', captionInput.value);
        if (privacyInput) formData.append('privacy', privacyInput.value);
        if (bgColorInput) formData.append('background_color', bgColorInput.value);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', `/pages/${encodeURIComponent(PAGE_SLUG)}/stories`, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', CSRF_TOKEN);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = function(event) {
            if (event.lengthComputable) {
                const percent = Math.round((event.loaded / event.total) * 100);
                if (progressBar) progressBar.style.width = percent + '%';
                if (progressPercent) progressPercent.textContent = percent + '%';
                if (percent === 100 && statusText) {
                    statusText.textContent = 'Processing & saving story...';
                }
            }
        };

        xhr.onload = function() {
            btn.disabled = false;
            btn.textContent = 'Share to Story';
            let data = null;
            try {
                data = JSON.parse(xhr.responseText);
            } catch (err) {
                data = null;
            }

            if (xhr.status >= 200 && xhr.status < 300 && data && data.success) {
                alert('Story successfully published! Active for 24 hours per policy.');
                closeStoryCreatorModal();
                window.location.reload();
            } else {
                const msg = (data && data.message) ? data.message : 'Story publishing failed. Please check file format and size.';
                alert(msg);
                if (progressWrapper) progressWrapper.style.display = 'none';
            }
        };

        xhr.onerror = function() {
            btn.disabled = false;
            btn.textContent = 'Share to Story';
            if (progressWrapper) progressWrapper.style.display = 'none';
            alert('A network error occurred while uploading your story.');
        };

        xhr.send(formData);
    }

    function viewStory(id, name, mediaUrl, isVideo, viewsCount, canManage, privacy, timeAgo, content) {
        currentStoryId = id;
        const nameEl = document.getElementById('storyViewerName');
        const timeEl = document.getElementById('storyViewerTime');
        const imgEl = document.getElementById('storyViewerImg');
        const videoEl = document.getElementById('storyViewerVideo');
        const textEl = document.getElementById('storyViewerTextContent');
        const countEl = document.getElementById('storyViewerViewsCount');
        const managerControls = document.getElementById('storyManagerControls');

        if (nameEl) nameEl.textContent = name || 'Page Story';
        if (timeEl) timeEl.textContent = timeAgo ? `${timeAgo} • ${privacy || 'public'}` : 'Active Story';
        if (countEl) countEl.textContent = `${viewsCount || 0} views`;
        if (managerControls) managerControls.style.display = canManage ? 'flex' : 'none';

        if (videoEl) {
            videoEl.pause();
            videoEl.src = '';
            videoEl.style.display = 'none';
        }
        if (imgEl) {
            imgEl.src = '';
            imgEl.style.display = 'none';
        }
        if (textEl) {
            textEl.textContent = '';
            textEl.style.display = 'none';
        }

        if (isVideo && mediaUrl) {
            if (videoEl) {
                videoEl.src = mediaUrl;
                videoEl.style.display = 'block';
                videoEl.play().catch(() => {});
            }
        } else if (mediaUrl) {
            if (imgEl) {
                imgEl.src = mediaUrl;
                imgEl.style.display = 'block';
            }
        } else if (content) {
            if (textEl) {
                textEl.textContent = content;
                textEl.style.display = 'block';
            }
        }

        document.getElementById('storyViewerModal').style.display = 'flex';

        // Record real view asynchronously
        fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/stories/${id}/view`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        }).then(res => res.json()).then(data => {
            if (data && data.success && typeof data.views_count !== 'undefined') {
                if (countEl) countEl.textContent = `${data.views_count} views`;
            }
        }).catch(() => {});
    }

    function closeStoryViewerModal() {
        const videoEl = document.getElementById('storyViewerVideo');
        if (videoEl) {
            videoEl.pause();
            videoEl.src = '';
        }
        document.getElementById('storyViewerModal').style.display = 'none';
        currentStoryId = null;
    }

    async function openStoryViewersModal() {
        if (!currentStoryId) return;
        const modal = document.getElementById('storyViewersModal');
        const list = document.getElementById('storyViewersList');
        if (modal) modal.style.display = 'flex';
        if (list) list.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--fb-text-secondary);">Loading viewers...</div>';

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/stories/${currentStoryId}/viewers`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data && data.success && Array.isArray(data.viewers)) {
                if (data.viewers.length === 0) {
                    list.innerHTML = '<div style="text-align: center; padding: 24px; color: var(--fb-text-secondary); font-size: 13.5px;">No viewers yet for this story.</div>';
                } else {
                    let html = '<div style="display: flex; flex-direction: column; gap: 10px;">';
                    data.viewers.forEach(v => {
                        const avatar = v.avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100';
                        html += `
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="${avatar}" alt="" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                                    <div style="font-weight: 600; font-size: 13.5px; color: var(--fb-text-primary);">${escapeHtml(v.name || 'User')}</div>
                                </div>
                                <div style="font-size: 11.5px; color: var(--fb-text-secondary);">${escapeHtml(v.viewed_at || '')}</div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    list.innerHTML = html;
                }
            } else {
                list.innerHTML = `<div style="text-align: center; padding: 20px; color: #dc2626;">${escapeHtml(data.message || 'Failed to load viewers')}</div>`;
            }
        } catch (err) {
            list.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc2626;">Network error loading viewers</div>';
        }
    }

    function closeStoryViewersModal() {
        const modal = document.getElementById('storyViewersModal');
        if (modal) modal.style.display = 'none';
    }

    async function deleteCurrentStory() {
        if (!currentStoryId) return;
        if (!confirm('Are you sure you want to delete this story? Media files will be removed.')) return;

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/stories/${currentStoryId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data && data.success) {
                alert('Story deleted successfully.');
                closeStoryViewerModal();
                window.location.reload();
            } else {
                alert(data.message || 'Failed to delete story.');
            }
        } catch (err) {
            alert('Server error while deleting story.');
        }
    }

    // Reel Helpers
    function openReelCreatorModal() {
        document.getElementById('reelCreatorModal').style.display = 'flex';
    }
    function closeReelCreatorModal() {
        document.getElementById('reelCreatorModal').style.display = 'none';
        const prog = document.getElementById('reelUploadProgressWrapper');
        if (prog) prog.style.display = 'none';
    }

    function handleReelSubmit(e, isDraft) {
        e.preventDefault();
        const videoInput = document.getElementById('reelVideoInput');
        const coverInput = document.getElementById('reelCoverInput');
        const captionInput = document.getElementById('reelCaptionInput');
        const privacyInput = document.getElementById('reelPrivacySelect');
        const allowCommentsInput = document.getElementById('reelAllowComments');

        if (!videoInput || !videoInput.files[0]) {
            alert('Please select a video file for your Reel.');
            return;
        }

        const btnPublish = document.getElementById('btnPublishReel');
        const btnDraft = document.getElementById('btnSaveReelDraft');
        const progressWrapper = document.getElementById('reelUploadProgressWrapper');
        const progressBar = document.getElementById('reelProgressBar');
        const progressPercent = document.getElementById('reelProgressPercent');
        const statusText = document.getElementById('reelUploadStatusText');

        if (btnPublish) btnPublish.disabled = true;
        if (btnDraft) btnDraft.disabled = true;
        if (progressWrapper) progressWrapper.style.display = 'block';
        if (progressBar) progressBar.style.width = '0%';
        if (progressPercent) progressPercent.textContent = '0%';
        if (statusText) statusText.textContent = isDraft ? 'Saving Reel draft...' : 'Uploading Reel video (9:16)...';

        const formData = new FormData();
        formData.append('video', videoInput.files[0]);
        if (coverInput && coverInput.files[0]) formData.append('cover_image', coverInput.files[0]);
        if (captionInput) formData.append('caption', captionInput.value);
        if (privacyInput) formData.append('privacy', privacyInput.value);
        if (allowCommentsInput) formData.append('allow_comments', allowCommentsInput.checked ? '1' : '0');
        if (isDraft) formData.append('is_draft', '1');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', `/pages/${encodeURIComponent(PAGE_SLUG)}/reels`, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', CSRF_TOKEN);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = function(event) {
            if (event.lengthComputable) {
                const percent = Math.round((event.loaded / event.total) * 100);
                if (progressBar) progressBar.style.width = percent + '%';
                if (progressPercent) progressPercent.textContent = percent + '%';
                if (percent === 100 && statusText) {
                    statusText.textContent = 'Transcoding & processing vertical reel...';
                }
            }
        };

        xhr.onload = function() {
            if (btnPublish) btnPublish.disabled = false;
            if (btnDraft) btnDraft.disabled = false;
            let data = null;
            try {
                data = JSON.parse(xhr.responseText);
            } catch (err) {
                data = null;
            }

            if (xhr.status >= 200 && xhr.status < 300 && data && data.success) {
                alert(isDraft ? 'Reel saved as draft!' : 'Reel successfully published!');
                closeReelCreatorModal();
                switchPageTab('reels');
                window.location.reload();
            } else {
                const msg = (data && data.message) ? data.message : 'Reel upload failed. Please verify format and size.';
                alert(msg);
                if (progressWrapper) progressWrapper.style.display = 'none';
            }
        };

        xhr.onerror = function() {
            if (btnPublish) btnPublish.disabled = false;
            if (btnDraft) btnDraft.disabled = false;
            if (progressWrapper) progressWrapper.style.display = 'none';
            alert('A network error occurred while uploading your Reel.');
        };

        xhr.send(formData);
    }

    async function deletePageReel(reelId) {
        if (!confirm('Are you sure you want to delete this Reel? This action cannot be undone.')) return;

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/reels/${reelId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data && data.success) {
                alert('Reel deleted successfully.');
                window.location.reload();
            } else {
                alert(data.message || 'Failed to delete Reel.');
            }
        } catch (err) {
            alert('Server error while deleting Reel.');
        }
    }

    // Video Player Modal Helpers
    function openVideoPlayer(url, title, duration, views, likes) {
        const modal = document.getElementById('videoPlayerModal');
        const videoEl = document.getElementById('mainVideoPlayerElement');
        const titleEl = document.getElementById('videoPlayerTitle');
        const viewsEl = document.getElementById('videoPlayerViews');
        const likesEl = document.getElementById('videoPlayerLikes');

        if (titleEl) titleEl.textContent = title || 'Page Video Playback';
        if (viewsEl) viewsEl.textContent = `${views || 0} views`;
        if (likesEl) likesEl.textContent = `${likes || 0} likes`;

        if (videoEl) {
            videoEl.src = url || '';
            videoEl.play().catch(() => {});
        }

        if (modal) modal.style.display = 'flex';
    }

    function closeVideoPlayerModal() {
        const modal = document.getElementById('videoPlayerModal');
        const videoEl = document.getElementById('mainVideoPlayerElement');
        if (videoEl) {
            videoEl.pause();
            videoEl.src = '';
        }
        if (modal) modal.style.display = 'none';
    }

    // WebRTC Live Studio & Production Media Infrastructure
    let liveMediaStream = null;
    let livePeerConnection = null;
    let liveHeartbeatTimer = null;
    let liveStreamId = null;
    let liveHostToken = null;
    let liveMediaRecorder = null;
    let liveRecordedChunks = [];
    let isBroadcasting = false;
    let currentVideoDeviceId = null;
    let currentAudioDeviceId = null;

    function setLiveConnectionBadge(state, label) {
        const badge = document.getElementById('liveConnectionStatusBadge');
        if (!badge) return;
        const colorMap = {
            idle: '#64748b',
            connecting: '#f59e0b',
            live: '#ef4444',
            reconnecting: '#d97706',
            ending: '#64748b',
            ended: '#10b981',
            failed: '#ef4444'
        };
        badge.style.background = colorMap[state] || '#64748b';
        badge.textContent = label || state.toUpperCase();
    }

    async function openLiveStudioModal() {
        document.getElementById('liveStudioModal').style.display = 'flex';
        setLiveConnectionBadge('connecting', 'Device Check');
        document.getElementById('liveStudioOfflineNotice').style.display = 'flex';
        document.getElementById('liveBadgeOverlay').style.display = 'none';
        document.getElementById('liveViewersOverlay').style.display = 'none';
        document.getElementById('liveReconnectingOverlay').style.display = 'none';
        document.getElementById('btnStartBroadcast').style.display = 'inline-block';
        document.getElementById('btnStartBroadcast').disabled = false;
        document.getElementById('btnStartBroadcast').textContent = 'Go Live Now';
        document.getElementById('btnEndBroadcast').style.display = 'none';
        document.getElementById('liveStreamSetupFields').style.display = 'block';

        try {
            const constraints = {
                video: currentVideoDeviceId ? { deviceId: { exact: currentVideoDeviceId } } : true,
                audio: currentAudioDeviceId ? { deviceId: { exact: currentAudioDeviceId } } : true
            };
            liveMediaStream = await navigator.mediaDevices.getUserMedia(constraints);
            const videoEl = document.getElementById('liveStudioVideoPreview');
            videoEl.srcObject = liveMediaStream;
            videoEl.play();
            document.getElementById('liveStudioOfflineNotice').style.display = 'none';
            setLiveConnectionBadge('idle', 'Ready');
            await populateLiveMediaDevices();
        } catch (err) {
            console.error('Camera/microphone hardware access error:', err);
            setLiveConnectionBadge('failed', 'Permission Denied');
            const notice = document.getElementById('liveDeviceNoticeText');
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                notice.innerHTML = '<strong style="color: #ef4444;">ক্যামেরা বা মাইক্রোফোন অনুমতি প্রত্যাখ্যাত হয়েছে।</strong><br><small>Camera/mic permission denied. Please allow device access in browser settings.</small>';
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                notice.innerHTML = '<strong style="color: #ef4444;">কোনো ক্যামেরা বা মাইক্রোফোন পাওয়া যায়নি।</strong><br><small>No video/audio hardware detected.</small>';
            } else {
                notice.innerHTML = `<strong style="color: #ef4444;">ডিভাইস ত্রুটি:</strong> <small>${err.message || 'Media capture failed.'}</small>`;
            }
            document.getElementById('btnStartBroadcast').disabled = true;
        }
    }

    async function populateLiveMediaDevices() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const camSelect = document.getElementById('liveCameraSelect');
            const micSelect = document.getElementById('liveMicSelect');
            camSelect.innerHTML = '<option value="">Default Camera</option>';
            micSelect.innerHTML = '<option value="">Default Microphone</option>';

            devices.forEach((dev, idx) => {
                const opt = document.createElement('option');
                opt.value = dev.deviceId;
                if (dev.kind === 'videoinput') {
                    opt.textContent = dev.label || `Camera ${idx + 1}`;
                    if (currentVideoDeviceId && dev.deviceId === currentVideoDeviceId) opt.selected = true;
                    camSelect.appendChild(opt);
                } else if (dev.kind === 'audioinput') {
                    opt.textContent = dev.label || `Microphone ${idx + 1}`;
                    if (currentAudioDeviceId && dev.deviceId === currentAudioDeviceId) opt.selected = true;
                    micSelect.appendChild(opt);
                }
            });
        } catch (e) {
            console.warn('Could not enumerate media devices:', e);
        }
    }

    async function switchCameraDevice() {
        const camSelect = document.getElementById('liveCameraSelect');
        currentVideoDeviceId = camSelect.value || null;
        if (!liveMediaStream) return;

        try {
            const newStream = await navigator.mediaDevices.getUserMedia({
                video: currentVideoDeviceId ? { deviceId: { exact: currentVideoDeviceId } } : true,
                audio: false
            });
            const newTrack = newStream.getVideoTracks()[0];
            const oldTrack = liveMediaStream.getVideoTracks()[0];
            if (oldTrack) {
                liveMediaStream.removeTrack(oldTrack);
                oldTrack.stop();
            }
            liveMediaStream.addTrack(newTrack);

            if (livePeerConnection) {
                const sender = livePeerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                if (sender) sender.replaceTrack(newTrack);
            }
            const videoEl = document.getElementById('liveStudioVideoPreview');
            videoEl.srcObject = liveMediaStream;
        } catch (e) {
            alert('ক্যামেরা ডিভাইস পরিবর্তন করা যায়নি: ' + e.message);
        }
    }

    async function switchMicDevice() {
        const micSelect = document.getElementById('liveMicSelect');
        currentAudioDeviceId = micSelect.value || null;
        if (!liveMediaStream) return;

        try {
            const newStream = await navigator.mediaDevices.getUserMedia({
                audio: currentAudioDeviceId ? { deviceId: { exact: currentAudioDeviceId } } : true,
                video: false
            });
            const newTrack = newStream.getAudioTracks()[0];
            const oldTrack = liveMediaStream.getAudioTracks()[0];
            if (oldTrack) {
                liveMediaStream.removeTrack(oldTrack);
                oldTrack.stop();
            }
            liveMediaStream.addTrack(newTrack);

            if (livePeerConnection) {
                const sender = livePeerConnection.getSenders().find(s => s.track && s.track.kind === 'audio');
                if (sender) sender.replaceTrack(newTrack);
            }
        } catch (e) {
            alert('মাইক্রোফোন ডিভাইস পরিবর্তন করা যায়নি: ' + e.message);
        }
    }

    function closeLiveStudioModal() {
        if (isBroadcasting) {
            if (!confirm('আপনি কি লাইভ ব্রডকাস্ট সমাপ্ত করতে চান?')) return;
            stopLiveBroadcast();
            return;
        }
        if (liveMediaStream) {
            liveMediaStream.getTracks().forEach(track => track.stop());
            liveMediaStream = null;
        }
        if (livePeerConnection) {
            livePeerConnection.close();
            livePeerConnection = null;
        }
        setLiveConnectionBadge('idle', 'Idle');
        document.getElementById('liveStudioModal').style.display = 'none';
    }

    function toggleLiveCamera() {
        if (!liveMediaStream) return;
        const videoTrack = liveMediaStream.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.enabled = !videoTrack.enabled;
            document.getElementById('lblToggleCamera').textContent = videoTrack.enabled ? 'Camera Off' : 'Camera On';
        }
    }

    function toggleLiveMic() {
        if (!liveMediaStream) return;
        const audioTrack = liveMediaStream.getAudioTracks()[0];
        if (audioTrack) {
            audioTrack.enabled = !audioTrack.enabled;
            document.getElementById('lblToggleMic').textContent = audioTrack.enabled ? 'Mute' : 'Unmute';
        }
    }

    async function toggleLiveScreenShare() {
        try {
            const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
            const screenTrack = screenStream.getVideoTracks()[0];
            const videoEl = document.getElementById('liveStudioVideoPreview');
            videoEl.srcObject = screenStream;

            if (livePeerConnection) {
                const sender = livePeerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                if (sender) sender.replaceTrack(screenTrack);
            }

            screenTrack.onended = () => {
                if (liveMediaStream) {
                    videoEl.srcObject = liveMediaStream;
                    const camTrack = liveMediaStream.getVideoTracks()[0];
                    if (livePeerConnection && camTrack) {
                        const sender = livePeerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) sender.replaceTrack(camTrack);
                    }
                }
            };
        } catch (err) {
            console.log('Screen share cancelled or unsupported:', err);
        }
    }

    function handleLiveNetworkOffline() {
        if (!isBroadcasting) return;
        setLiveConnectionBadge('reconnecting', 'Reconnecting...');
        document.getElementById('liveReconnectingOverlay').style.display = 'flex';
    }

    function handleLiveNetworkOnline() {
        if (!isBroadcasting) return;
        setLiveConnectionBadge('live', 'LIVE');
        document.getElementById('liveReconnectingOverlay').style.display = 'none';
    }

    async function startLiveBroadcast() {
        if (!liveMediaStream || liveMediaStream.getTracks().length === 0) {
            alert('ক্যামেরা এবং মাইক্রোফোন চালু থাকা আবশ্যক।');
            return;
        }

        const btn = document.getElementById('btnStartBroadcast');
        btn.disabled = true;
        btn.textContent = 'Connecting Media Server...';
        setLiveConnectionBadge('connecting', 'Connecting...');

        const title = document.getElementById('liveStreamTitle').value.trim() || ('Live Broadcast by ' + PAGE_SLUG);
        const privacy = document.getElementById('liveStreamPrivacy').value;
        const record = document.getElementById('liveSaveRecording').checked;

        try {
            // 1. Create Broadcast Session (receives short-lived host token)
            const sessionRes = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/live`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: title,
                    privacy: privacy,
                    record: record
                })
            });
            const sessionData = await sessionRes.json();
            if (!sessionData.success || !sessionData.stream) {
                throw new Error(sessionData.message || 'ব্রডকাস্ট সেশন তৈরি করা যায়নি।');
            }

            liveStreamId = sessionData.stream.id;
            liveHostToken = sessionData.host_token;

            // 2. Establish WebRTC connection transport
            const iceServers = sessionData.ice_servers && sessionData.ice_servers.length > 0
                ? sessionData.ice_servers
                : [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' }
                ];

            livePeerConnection = new RTCPeerConnection({ iceServers });

            liveMediaStream.getTracks().forEach(track => {
                livePeerConnection.addTrack(track, liveMediaStream);
            });

            livePeerConnection.oniceconnectionstatechange = () => {
                const state = livePeerConnection.iceConnectionState;
                if (state === 'disconnected' || state === 'failed') {
                    setLiveConnectionBadge('reconnecting', 'Reconnecting...');
                    document.getElementById('liveReconnectingOverlay').style.display = 'flex';
                } else if (state === 'connected' || state === 'completed') {
                    setLiveConnectionBadge('live', 'LIVE');
                    document.getElementById('liveReconnectingOverlay').style.display = 'none';
                }
            };

            window.addEventListener('offline', handleLiveNetworkOffline);
            window.addEventListener('online', handleLiveNetworkOnline);

            // 3. Setup local recording recorder if requested
            if (record && window.MediaRecorder) {
                try {
                    liveRecordedChunks = [];
                    const mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp8,opus')
                        ? 'video/webm;codecs=vp8,opus'
                        : 'video/webm';
                    liveMediaRecorder = new MediaRecorder(liveMediaStream, { mimeType });
                    liveMediaRecorder.ondataavailable = (e) => {
                        if (e.data && e.data.size > 0) liveRecordedChunks.push(e.data);
                    };
                    liveMediaRecorder.start(2000);
                } catch (recErr) {
                    console.warn('Local MediaRecorder initialization failed:', recErr);
                }
            }

            // 4. Confirm SFU publication to set broadcast genuinely active
            const pubRes = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/live/${liveStreamId}/publish`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ host_token: liveHostToken })
            });
            const pubData = await pubRes.json();
            if (!pubData.success) {
                throw new Error(pubData.message || 'লাইভ প্রকাশনা নিশ্চিত করা যায়নি।');
            }

            // 5. Broadcaster is genuinely LIVE
            isBroadcasting = true;
            setLiveConnectionBadge('live', 'LIVE');
            document.getElementById('liveBadgeOverlay').style.display = 'block';
            document.getElementById('liveViewersOverlay').style.display = 'block';
            document.getElementById('liveStreamSetupFields').style.display = 'none';
            btn.style.display = 'none';
            document.getElementById('btnEndBroadcast').style.display = 'inline-block';

            // 6. Start Heartbeat & Viewer Count Polling Loop
            liveHeartbeatTimer = setInterval(async () => {
                if (!isBroadcasting || !liveStreamId || !liveHostToken) return;
                try {
                    const hbRes = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/live/${liveStreamId}/heartbeat`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ host_token: liveHostToken })
                    });
                    const hbData = await hbRes.json();
                    if (hbData.success && typeof hbData.viewer_count !== 'undefined') {
                        document.getElementById('liveConnectedViewersCount').textContent = hbData.viewer_count;
                        const viewerCountEl = document.getElementById('viewerActiveLiveCount');
                        if (viewerCountEl) viewerCountEl.textContent = hbData.viewer_count;
                    }
                } catch (hbErr) {
                    console.warn('Live heartbeat tick failed:', hbErr);
                }
            }, 10000);

        } catch (err) {
            console.error('Live broadcast error:', err);
            setLiveConnectionBadge('failed', 'Broadcast Failed');
            alert(err.message || 'লাইভ সার্ভার কানেকশন ত্রুটি।');
            btn.disabled = false;
            btn.textContent = 'Go Live Now';
        }
    }

    async function stopLiveBroadcast() {
        const endBtn = document.getElementById('btnEndBroadcast');
        endBtn.disabled = true;
        endBtn.textContent = 'Ending Live...';
        setLiveConnectionBadge('ending', 'Ending...');

        if (liveHeartbeatTimer) {
            clearInterval(liveHeartbeatTimer);
            liveHeartbeatTimer = null;
        }

        window.removeEventListener('offline', handleLiveNetworkOffline);
        window.removeEventListener('online', handleLiveNetworkOnline);

        const currentId = liveStreamId;
        const currentToken = liveHostToken;

        // Stop recorder and collect final chunk
        let recordingBlob = null;
        if (liveMediaRecorder && liveMediaRecorder.state !== 'inactive') {
            try {
                liveMediaRecorder.stop();
                if (liveRecordedChunks.length > 0) {
                    recordingBlob = new Blob(liveRecordedChunks, { type: 'video/webm' });
                }
            } catch (recStopErr) {
                console.warn('Recorder stop error:', recStopErr);
            }
        }

        // Call End Broadcast API (invalidates host token & sets status ended)
        if (currentId && currentToken) {
            try {
                await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/live/${currentId}/end`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ host_token: currentToken })
                });
            } catch (endErr) {
                console.error('Error ending broadcast:', endErr);
            }

            // Upload recording replay if captured
            if (recordingBlob && recordingBlob.size > 0) {
                try {
                    const fd = new FormData();
                    fd.append('recording_file', recordingBlob, 'page_live_broadcast.webm');
                    fd.append('host_token', currentToken);
                    await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/live/${currentId}/recording`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'Accept': 'application/json'
                        },
                        body: fd
                    });
                } catch (recUploadErr) {
                    console.warn('Recording replay upload failed:', recUploadErr);
                }
            }
        }

        // Cleanup WebRTC and local states
        isBroadcasting = false;
        liveStreamId = null;
        liveHostToken = null;
        liveRecordedChunks = [];
        liveMediaRecorder = null;

        if (livePeerConnection) {
            livePeerConnection.close();
            livePeerConnection = null;
        }
        if (liveMediaStream) {
            liveMediaStream.getTracks().forEach(t => t.stop());
            liveMediaStream = null;
        }

        setLiveConnectionBadge('ended', 'Ended');
        document.getElementById('liveBadgeOverlay').style.display = 'none';
        document.getElementById('liveViewersOverlay').style.display = 'none';
        document.getElementById('btnStartBroadcast').style.display = 'inline-block';
        document.getElementById('btnStartBroadcast').textContent = 'Go Live Now';
        document.getElementById('btnStartBroadcast').disabled = false;
        endBtn.style.display = 'none';
        endBtn.disabled = false;
        endBtn.textContent = 'End Broadcast';

        alert('লাইভ ব্রডকাস্ট সফলভাবে সমাপ্ত হয়েছে। ভিডিও রেকর্ডিং প্রস্তুত হলে পেজের টাইমলাইনে প্রদর্শিত হবে।');
        document.getElementById('liveStudioModal').style.display = 'none';

        // Reload feed to show post-stream state
        setTimeout(() => {
            window.location.reload();
        }, 1200);
    }

    // Share Video & Upload Modal Functions
    function openShareVideoModal() {
        document.getElementById('shareVideoModal').style.display = 'flex';
        document.getElementById('directVideoFileInput').value = '';
        document.getElementById('directVideoCaptionInput').value = '';
        document.getElementById('directVideoThumbInput').value = '';
        document.getElementById('directVideoPreviewContainer').style.display = 'none';
        document.getElementById('directVideoProgressWrapper').style.display = 'none';
        document.getElementById('directVideoProgressBar').style.width = '0%';
        document.getElementById('directVideoProgressPercent').textContent = '0%';
        document.getElementById('btnPublishDirectVideo').disabled = false;
    }

    function closeShareVideoModal() {
        const player = document.getElementById('directVideoPlayerPreview');
        if (player) {
            player.pause();
            player.src = '';
        }
        document.getElementById('shareVideoModal').style.display = 'none';
    }

    function handleDirectVideoPreview(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (file.size > 104857600) { // 100MB
            alert('ভিডিও ফাইলের সাইজ সর্বোচ্চ ১০০ মেগাবাইট হতে পারবে।');
            event.target.value = '';
            return;
        }

        const player = document.getElementById('directVideoPlayerPreview');
        const container = document.getElementById('directVideoPreviewContainer');
        player.src = URL.createObjectURL(file);
        container.style.display = 'block';
    }

    function handleDirectVideoSubmit(event) {
        event.preventDefault();
        const fileInput = document.getElementById('directVideoFileInput');
        const captionInput = document.getElementById('directVideoCaptionInput');
        const thumbInput = document.getElementById('directVideoThumbInput');
        const audienceSelect = document.getElementById('directVideoAudienceSelect');
        const allowComments = document.getElementById('directVideoAllowComments');
        const btn = document.getElementById('btnPublishDirectVideo');

        if (!fileInput.files || fileInput.files.length === 0) {
            alert('দয়া করে একটি ভিডিও ফাইল নির্বাচন করুন।');
            return;
        }

        const formData = new FormData();
        formData.append('video_file', fileInput.files[0]);
        formData.append('caption', captionInput.value.trim());
        formData.append('audience', audienceSelect.value);
        formData.append('allow_comments', allowComments.checked ? '1' : '0');
        if (thumbInput.files && thumbInput.files[0]) {
            formData.append('thumbnail', thumbInput.files[0]);
        }

        const progressWrapper = document.getElementById('directVideoProgressWrapper');
        const progressBar = document.getElementById('directVideoProgressBar');
        const progressPercent = document.getElementById('directVideoProgressPercent');
        const statusText = document.getElementById('directVideoStatusText');

        progressWrapper.style.display = 'block';
        btn.disabled = true;
        statusText.textContent = 'ভিডিও আপলোড হচ্ছে...';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', `/pages/${encodeURIComponent(PAGE_SLUG)}/videos`, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', CSRF_TOKEN);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressPercent.textContent = percent + '%';
                if (percent >= 100) {
                    statusText.textContent = 'ভিডিও প্রসেসিং ও পাবলিশ হচ্ছে...';
                }
            }
        };

        xhr.onload = () => {
            btn.disabled = false;
            try {
                const res = JSON.parse(xhr.responseText);
                if (xhr.status >= 200 && xhr.status < 300 && res.success) {
                    alert('ভিডিও সফলভাবে আপলোড এবং শেয়ার করা হয়েছে!');
                    closeShareVideoModal();
                    window.location.reload();
                } else {
                    alert(res.message || 'ভিডিও প্রকাশনা ব্যর্থ হয়েছে।');
                }
            } catch (e) {
                alert('ভিডিও আপলোডে ত্রুটি দেখা দিয়েছে।');
            }
        };

        xhr.onerror = () => {
            btn.disabled = false;
            alert('নেটওয়ার্ক সংযোগ ত্রুটি। দয়া করে আবার চেষ্টা করুন।');
        };

        xhr.send(formData);
    }

    async function shareExistingVideo(videoId) {
        if (!confirm('আপনি কি এই ভিডিওটি আপনার টাইমলাইনে শেয়ার করতে চান?')) return;
        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/videos/${videoId}/share`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ caption: 'Shared from ' + PAGE_SLUG })
            });
            const data = await res.json();
            if (data.success) {
                alert('ভিডিওটি সফলভাবে শেয়ার করা হয়েছে!');
            } else {
                alert(data.message || 'ভিডিও শেয়ার করা সম্ভব হয়নি।');
            }
        } catch (e) {
            alert('শেয়ার করার সময় নেটওয়ার্ক ত্রুটি ঘটেছে।');
        }
    }

    async function handleToggleFollow(targetPageId, btn) {
        btn.disabled = true;
        try {
            const res = await fetch(`/api/v1/pages/${targetPageId}/follow`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                btn.textContent = data.data?.is_following ? 'Following' : 'Follow';
            }
        } catch (e) {
        } finally {
            btn.disabled = false;
        }
    }

    // 1. Tab Switching & Browser Navigation
    function switchPageTab(tabName, pushState = true) {
        document.querySelectorAll('.page-nav-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.page-tab-pane').forEach(p => p.style.display = 'none');

        const activeBtn = Array.from(document.querySelectorAll('.page-nav-tab')).find(b => {
            const text = b.textContent.trim().toLowerCase();
            const onclick = b.getAttribute('onclick') || '';
            return text === tabName.toLowerCase() || onclick.includes(`'${tabName}'`);
        });
        if (activeBtn) activeBtn.classList.add('active');

        const paneId = tabName;
        const targetPane = document.getElementById(`tab-pane-${paneId}`);
        if (targetPane) targetPane.style.display = 'block';

        if (pushState) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            window.history.pushState({ tab: tabName }, '', url.toString());
        }
    }

    window.addEventListener('popstate', (e) => {
        const url = new URL(window.location.href);
        const tab = url.searchParams.get('tab') || 'home';
        switchPageTab(tab, false);
    });

    function toggleMoreMenu(e) {
        if (e) e.stopPropagation();
        const dd = document.getElementById('moreNavDropdown') || document.getElementById('pageMoreDropdown');
        if (dd) {
            dd.style.display = (dd.style.display === 'none' || !dd.style.display) ? 'block' : 'none';
        }
    }

    function closeMoreMenu() {
        const dd1 = document.getElementById('moreNavDropdown');
        if (dd1) dd1.style.display = 'none';
        const dd2 = document.getElementById('pageMoreDropdown');
        if (dd2) dd2.style.display = 'none';
    }

    document.addEventListener('click', () => closeMoreMenu());

    function switchPostFilter(filter) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'posts');
        url.searchParams.set('post_filter', filter);
        window.location.href = url.toString();
    }

    // 2. Follow / Unfollow Live Toggle
    async function handlePageFollowToggle() {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif

        const btn = document.getElementById('btnPageFollow');
        const icon = document.getElementById('followIcon');
        const text = document.getElementById('followText');
        const countSpan = document.getElementById('headerFollowersCount');

        btn.disabled = true;

        try {
            const res = await fetch(`/api/v1/pages/${PAGE_ID}/follow`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();

            if (data.success) {
                const isFollowing = typeof data.is_following !== 'undefined' ? data.is_following : (data.data?.is_following ?? false);
                const folCount = typeof data.followers_count !== 'undefined' ? data.followers_count : (data.data?.followers_count ?? null);

                if (isFollowing) {
                    btn.className = 'btn-fb-secondary';
                    btn.style.background = '#e4e6eb';
                    btn.style.color = 'var(--fb-text-primary)';
                    icon.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                    text.textContent = 'Following';
                } else {
                    btn.className = 'btn-fb-primary';
                    btn.style.background = '#1877f2';
                    btn.style.color = '#ffffff';
                    icon.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>';
                    text.textContent = 'Follow';
                }
                if (countSpan && folCount !== null) {
                    countSpan.textContent = Number(folCount).toLocaleString();
                }
            } else {
                alert(data.message || 'অনুরোধ সম্পন্ন হতে ব্যর্থ হয়েছে।');
            }
        } catch (e) {
            alert('সার্ভার যোগাযোগে ত্রুটি ঘটেছে।');
        } finally {
            btn.disabled = false;
        }
    }

    function toggleComposerSchedule(status) {
        const scheduleBox = document.getElementById('composerScheduleBox');
        const submitBtnText = document.getElementById('btnPagePostSubmitText');
        if (status === 'scheduled') {
            if (scheduleBox) scheduleBox.style.display = 'block';
            if (submitBtnText) submitBtnText.textContent = 'পোস্ট শিডিউল করুন';
        } else if (status === 'draft') {
            if (scheduleBox) scheduleBox.style.display = 'none';
            if (submitBtnText) submitBtnText.textContent = 'ড্রাফট সংরক্ষণ করুন';
        } else {
            if (scheduleBox) scheduleBox.style.display = 'none';
            if (submitBtnText) submitBtnText.textContent = 'পোস্ট প্রকাশ করুন';
        }
    }

    // 3. Page Post Submission (Composer)
    async function handlePagePostSubmit(e) {
        e.preventDefault();
        const contentInput = document.getElementById('pagePostContent');
        const content = contentInput.value.trim();
        const btn = document.getElementById('btnPagePostSubmit');

        if (!content) return;

        btn.disabled = true;
        btn.innerHTML = '<span>পোস্ট হচ্ছে...</span>';

        const formData = new FormData();
        formData.append('content', content);

        const audienceInput = document.getElementById('pagePostAudience');
        if (audienceInput) formData.append('audience', audienceInput.value);

        const statusInput = document.getElementById('pagePostStatus');
        const statusVal = statusInput ? statusInput.value : 'published';
        formData.append('status', statusVal);

        const feelingInput = document.getElementById('pagePostFeeling');
        if (feelingInput && feelingInput.value) {
            formData.append('feeling_activity', feelingInput.value);
        }

        const scheduledAtInput = document.getElementById('pagePostScheduledAt');
        if (scheduledAtInput && scheduledAtInput.value) {
            formData.append('scheduled_at', scheduledAtInput.value);
        }

        const fileInput = document.getElementById('composerMediaInput');
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('media_files[]', fileInput.files[i]);
            }
        }

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/posts`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await res.json();

            if (data.success || data.post) {
                contentInput.value = '';
                clearSelectedComposerMedia();
                alert(data.message || 'পোস্ট সফলভাবে সম্পন্ন হয়েছে!');
                window.location.reload();
            } else {
                alert(data.message || 'পোস্ট প্রকাশ করতে ত্রুটি ঘটেছে।');
                btn.disabled = false;
                btn.innerHTML = '<span>পোস্ট প্রকাশ করুন</span>';
            }
        } catch (err) {
            alert('সার্ভারের সাথে যোগাযোগ করা যায়নি।');
            btn.disabled = false;
            btn.innerHTML = '<span>পোস্ট প্রকাশ করুন</span>';
        }
    }

    // 4. Composer Media Handling
    async function handleComposerMediaSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('collection', 'post');

        const previewContainer = document.getElementById('composerMediaPreview');
        const previewBox = document.getElementById('previewMediaBox');
        previewContainer.style.display = 'block';
        previewBox.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--fb-text-secondary); font-size: 13px;">মিডিয়া আপলোড হচ্ছে...</div>';

        try {
            const res = await fetch('/api/v1/media/upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await res.json();

            if (data.success && data.data) {
                selectedComposerMediaId = data.data.id;
                if (file.type.startsWith('video/')) {
                    previewBox.innerHTML = `<video controls src="${data.data.url}" style="max-height: 200px; width: 100%; object-fit: cover;"></video>`;
                } else {
                    previewBox.innerHTML = `<img src="${data.data.url}" style="max-height: 200px; width: 100%; object-fit: cover;">`;
                }
            } else {
                alert(data.message || 'মিডিয়া আপলোড ব্যর্থ হয়েছে।');
                clearSelectedComposerMedia();
            }
        } catch (err) {
            alert('মিডিয়া আপলোড সার্ভার ত্রুটি।');
            clearSelectedComposerMedia();
        }
    }

    function clearSelectedComposerMedia() {
        selectedComposerMediaId = null;
        document.getElementById('composerMediaInput').value = '';
        document.getElementById('composerMediaPreview').style.display = 'none';
        document.getElementById('previewMediaBox').innerHTML = '';
    }

    // 5. Page Branding Upload & Removal (Safe cleanup)
    async function uploadPageBrandingImage(type, e) {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        if (type === 'cover' || type === 'cover_image') {
            formData.append('cover_image', file);
        } else {
            formData.append('avatar', file);
        }

        try {
            const uploadRes = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/branding`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: formData
            });
            const uploadData = await uploadRes.json();

            if (uploadData.success && uploadData.url) {
                const imgUrl = uploadData.url;
                if (type === 'cover' || type === 'cover_image') {
                    const coverBox = document.getElementById('pageCoverBox');
                    if (coverBox) coverBox.innerHTML = `<img src="${imgUrl}" alt="{{ $page->name }}" id="pageCoverImg">`;
                } else {
                    const avatarBox = document.getElementById('pageAvatarBox');
                    if (avatarBox) avatarBox.innerHTML = `<img src="${imgUrl}" alt="{{ $page->name }}" id="pageAvatarImg">`;
                }
                alert(uploadData.message || 'Image updated successfully.');
            } else {
                alert(uploadData.message || 'Failed to update image.');
            }
        } catch (err) {
            alert('Server error while uploading image.');
        }
    }

    async function removePageBranding(type) {
        if (!confirm(`Are you sure you want to remove the page ${type}?`)) return;
        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/branding/${type}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                if (type === 'cover' || type === 'cover_image') {
                    const box = document.getElementById('pageCoverBox');
                    if (box) box.innerHTML = '<div class="page-cover-placeholder" style="width: 100%; height: 100%; background: linear-gradient(135deg, #0ea5e9, #3b82f6);"></div>';
                } else {
                    const box = document.getElementById('pageAvatarBox');
                    if (box) box.innerHTML = '<div class="page-avatar-placeholder" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #e2e8f0; font-size: 40px; font-weight: 800; color: #475569;"><span>{{ mb_substr($page->name, 0, 1) }}</span></div>';
                }
                alert(data.message || 'Image removed successfully.');
            } else {
                alert(data.message || 'Failed to remove image.');
            }
        } catch (e) {
            alert('Server error while removing image.');
        }
    }

    // 5B. Edit Page Modal Handlers & Live DOM Updates
    function openEditPageModal() {
        const modal = document.getElementById('editPageDetailsModal');
        if (modal) modal.style.display = 'flex';
        const dd = document.getElementById('pageMoreDropdown');
        if (dd) dd.style.display = 'none';
    }

    function closeEditPageModal() {
        const modal = document.getElementById('editPageDetailsModal');
        if (modal) modal.style.display = 'none';
    }

    async function handleSavePageDetails(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSavePageDetails');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Saving...';
        }

        const payload = {
            name: document.getElementById('editPageName')?.value.trim(),
            username: document.getElementById('editPageUsername')?.value.trim(),
            category: document.getElementById('editPageCategory')?.value.trim(),
            sub_category: document.getElementById('editPageSubCategory')?.value.trim(),
            bio: document.getElementById('editPageBio')?.value.trim(),
            short_description: document.getElementById('editPageShortDesc')?.value.trim(),
            description: document.getElementById('editPageDescription')?.value.trim(),
            website: document.getElementById('editPageWebsite')?.value.trim(),
            email: document.getElementById('editPageEmail')?.value.trim(),
            phone: document.getElementById('editPagePhone')?.value.trim(),
            address: document.getElementById('editPageAddress')?.value.trim(),
            city: document.getElementById('editPageCity')?.value.trim(),
            country: document.getElementById('editPageCountry')?.value.trim(),
            zip_code: document.getElementById('editPageZipCode')?.value.trim(),
            visibility: document.getElementById('editPageVisibility')?.value,
            cta_type: document.getElementById('editPageCtaType')?.value,
        };

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/details`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success && (data.page || data.data)) {
                const pageData = data.data || data.page;
                // Live reactive DOM updates
                const nameSpan = document.querySelector('.page-hero-title > span');
                if (nameSpan && pageData.name) nameSpan.textContent = pageData.name;

                const catSpan = document.querySelector('.page-hero-meta > span:first-child');
                if (catSpan && pageData.category) catSpan.textContent = pageData.category;

                const bioP = document.querySelector('.page-hero-meta + p');
                if (bioP && pageData.bio) bioP.textContent = pageData.bio;

                closeEditPageModal();
                alert(data.message || 'Page details updated successfully!');
            } else {
                alert(data.message || 'Validation error while updating page.');
            }
        } catch (err) {
            alert('Server error while saving page details.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            }
        }
    }

    // 5C. More Menu Dropdown & Actions
    function togglePageMoreMenu(e) {
        e.stopPropagation();
        const dd = document.getElementById('pageMoreDropdown');
        if (dd) {
            dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', (e) => {
        const dd = document.getElementById('pageMoreDropdown');
        if (dd && !e.target.closest('#pageMoreDropdown') && !e.target.closest('#btnPageMoreMenu')) {
            dd.style.display = 'none';
        }
    });

    function copyPageUrl() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            alert('Page link copied to clipboard!');
        }).catch(() => {
            prompt('Copy link:', url);
        });
        const dd = document.getElementById('pageMoreDropdown');
        if (dd) dd.style.display = 'none';
    }

    function reportPageModal() {
        alert('Thank you for reporting. Our moderation team will review this page.');
        const dd = document.getElementById('pageMoreDropdown');
        if (dd) dd.style.display = 'none';
    }

    // 6. Post Reaction Toggle
    async function handlePostReactionToggle(postId) {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif

        const btn = document.getElementById(`btn-react-${postId}`);
        const countSpan = document.getElementById(`reactions-count-${postId}`);
        const isLiked = btn.classList.contains('active-liked');

        // Optimistic UI toggle
        btn.classList.toggle('active-liked');

        try {
            const res = await fetch(`/api/v1/posts/${postId}/react`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ type: 'like' })
            });
            const data = await res.json();

            if (data.success && data.data) {
                if (data.data.reacted) {
                    btn.classList.add('active-liked');
                } else {
                    btn.classList.remove('active-liked');
                }
                const count = data.data.total_reactions ?? data.data.reactions_count ?? data.data.likes_count ?? 0;
                countSpan.textContent = `👍 ❤️ ${count}`;
            } else {
                // Rollback on failure
                btn.classList.toggle('active-liked', isLiked);
            }
        } catch (e) {
            btn.classList.toggle('active-liked', isLiked);
        }
    }

    // 7. Embedded Comments
    function togglePostCommentsSection(postId) {
        const container = document.getElementById(`comments-container-${postId}`);
        if (container.style.display === 'none' || container.style.display === '') {
            container.style.display = 'block';
            loadPostComments(postId);
        } else {
            container.style.display = 'none';
        }
    }

    async function loadPostComments(postId) {
        const list = document.getElementById(`comments-list-${postId}`);
        try {
            const res = await fetch(`/api/v1/posts/${postId}/comments`);
            const json = await res.json();

            if (json.success && Array.isArray(json.data)) {
                if (json.data.length === 0) {
                    list.innerHTML = '<div style="text-align: center; font-size: 13px; color: var(--fb-text-secondary); padding: 8px;">এখনও কোনো মন্তব্য নেই। প্রথম মন্তব্যটি করুন!</div>';
                    return;
                }

                list.innerHTML = json.data.map(c => `
                    <div class="comment-item">
                        <div class="avatar" style="width: 32px; height: 32px; font-size: 13px;">
                            ${escapeHtml((c.user?.name || 'U').charAt(0))}
                        </div>
                        <div class="comment-bubble">
                            <div style="font-weight: 700; font-size: 13px; color: var(--fb-text-primary);">${escapeHtml(c.user?.name || 'User')}</div>
                            <div style="font-size: 13px; color: var(--fb-text-primary); margin-top: 2px;">${escapeHtml(c.body || c.content || '')}</div>
                        </div>
                    </div>
                `).join('');
            }
        } catch (e) {
            list.innerHTML = '<div style="color: #ef4444; font-size: 13px;">মন্তব্য লোড করা সম্ভব হয়নি।</div>';
        }
    }

    async function submitPostComment(postId) {
        const input = document.getElementById(`comment-input-${postId}`);
        const body = input.value.trim();
        if (!body) return;

        try {
            const res = await fetch(`/api/v1/posts/${postId}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ body })
            });
            const data = await res.json();

            if (data.success && data.data) {
                input.value = '';
                loadPostComments(postId);
                const countSpan = document.getElementById(`comments-count-${postId}`);
                if (countSpan) {
                    const currentCount = parseInt(countSpan.textContent) || 0;
                    countSpan.textContent = `${currentCount + 1} টি মন্তব্য`;
                }
            } else {
                alert(data.message || 'মন্তব্য প্রকাশ ব্যর্থ হয়েছে।');
            }
        } catch (e) {
            alert('সার্ভার ত্রুটি।');
        }
    }

    // 8. Delete Post
    async function handleDeletePost(postId) {
        if (!confirm('আপনি কি নিশ্চিত যে এই পোস্টটি মুছে ফেলতে চান?')) return;

        try {
            const res = await fetch(`/api/v1/posts/${postId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();

            if (data.success) {
                const card = document.getElementById(`post-card-${postId}`);
                if (card) card.remove();
            } else {
                alert(data.message || 'পোস্ট মোছা ব্যর্থ হয়েছে।');
            }
        } catch (e) {
            alert('সার্ভার যোগাযোগে ত্রুটি।');
        }
    }

    function togglePostDropdown(postId) {
        const dd = document.getElementById(`postDropdown-${postId}`);
        if (dd) dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
    }

    // 9. Share Modal
    function openSharePostModal(postId, previewText) {
        activeShareTargetPostId = postId;
        document.getElementById('sharePostPreviewText').textContent = previewText.substring(0, 120) + (previewText.length > 120 ? '...' : '');
        document.getElementById('socialShareModal').style.display = 'flex';

        document.getElementById('btnShareTimeline').onclick = async () => {
            const btn = document.getElementById('btnShareTimeline');
            btn.disabled = true;
            btn.textContent = 'শেয়ার হচ্ছে...';
            try {
                const res = await fetch(`/api/v1/posts/${postId}/share`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ caption: 'পেইজ থেকে শেয়ারকৃত কন্টেন্ট' })
                });
                const json = await res.json();
                if (json.success) {
                    alert('আপনার টাইমলাইনে সফলভাবে শেয়ার করা হয়েছে!');
                    closeShareModal();
                } else {
                    alert(json.message || 'শেয়ার করা যায়নি।');
                }
            } catch (e) {
                alert('সার্ভার ত্রুটি।');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg><span>আপনার টাইমলাইনে শেয়ার করুন</span>';
            }
        };

        document.getElementById('btnShareCopyLink').onclick = () => {
            const postUrl = `${window.location.origin}/posts/${postId}`;
            navigator.clipboard.writeText(postUrl).then(() => {
                alert('পোস্টের সরাসরি লিঙ্ক ক্লিপবোর্ডে কপি করা হয়েছে!');
                closeShareModal();
            });
        };

        document.getElementById('btnShareMessenger').onclick = () => {
            window.location.href = `/messages?share_post=${postId}`;
        };
    }

    function openPageShareModal() {
        const pageUrl = window.location.href;
        navigator.clipboard.writeText(pageUrl).then(() => {
            alert('পেইজের লিঙ্ক ক্লিপবোর্ডে কপি করা হয়েছে! বন্ধুদের সাথে শেয়ার করুন।');
        });
    }

    function closeShareModal() {
        document.getElementById('socialShareModal').style.display = 'none';
        activeShareTargetPostId = null;
    }

    // 10. Message to Page Interactive Chat System
    let pageConversationId = null;

    async function openPageMessageModal(initialText = '') {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif

        const modal = document.getElementById('pageMessageModal');
        modal.style.display = 'flex';

        if (initialText) {
            document.getElementById('pageMessageBody').value = initialText;
        }

        const loading = document.getElementById('pageMessageLoading');
        const empty = document.getElementById('pageMessageEmpty');
        const items = document.getElementById('pageMessageItems');

        loading.style.display = 'block';
        empty.style.display = 'none';
        items.innerHTML = '';

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/messages`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                }
            });
            const data = await res.json();
            loading.style.display = 'none';

            if (data.success && data.data) {
                pageConversationId = data.data.conversation?.id || null;
                const msgs = data.data.messages || [];
                if (msgs.length === 0) {
                    empty.style.display = 'block';
                } else {
                    empty.style.display = 'none';
                    msgs.forEach(msg => {
                        appendPageMessageBubble(msg);
                    });
                    scrollPageChatToBottom();
                }
            } else {
                empty.style.display = 'block';
            }
        } catch (e) {
            loading.style.display = 'none';
            empty.style.display = 'block';
        }
    }

    function closePageMessageModal() {
        document.getElementById('pageMessageModal').style.display = 'none';
    }

    function appendPageMessageBubble(msg) {
        const items = document.getElementById('pageMessageItems');
        const empty = document.getElementById('pageMessageEmpty');
        if (empty) empty.style.display = 'none';

        const isUser = msg.sender_type === 'user';
        const bubble = document.createElement('div');
        bubble.style.display = 'flex';
        bubble.style.flexDirection = 'column';
        bubble.style.alignItems = isUser ? 'flex-end' : 'flex-start';
        bubble.style.marginBottom = '6px';

        const msgContent = document.createElement('div');
        msgContent.style.maxWidth = '78%';
        msgContent.style.padding = '9px 14px';
        msgContent.style.borderRadius = isUser ? '16px 16px 2px 16px' : '16px 16px 16px 2px';
        msgContent.style.fontSize = '13.5px';
        msgContent.style.lineHeight = '1.45';
        msgContent.style.wordBreak = 'break-word';

        if (isUser) {
            msgContent.style.background = '#1877f2';
            msgContent.style.color = '#ffffff';
        } else {
            msgContent.style.background = '#e4e6eb';
            msgContent.style.color = 'var(--fb-text-primary)';
        }
        msgContent.textContent = msg.body;

        const timeSpan = document.createElement('div');
        timeSpan.style.fontSize = '10.5px';
        timeSpan.style.color = '#94a3b8';
        timeSpan.style.marginTop = '2px';
        timeSpan.style.padding = '0 4px';
        timeSpan.textContent = msg.created_at || 'Just now';

        bubble.appendChild(msgContent);
        bubble.appendChild(timeSpan);
        items.appendChild(bubble);
    }

    function scrollPageChatToBottom() {
        const history = document.getElementById('pageMessageHistory');
        if (history) {
            history.scrollTop = history.scrollHeight;
        }
    }

    async function handleSendPageMessage(e) {
        e.preventDefault();
        const bodyInput = document.getElementById('pageMessageBody');
        const body = bodyInput.value.trim();
        const btn = document.getElementById('btnSendPageMessage');
        if (!body) return;

        btn.disabled = true;

        appendPageMessageBubble({
            sender_type: 'user',
            body: body,
            created_at: 'Sending...'
        });
        scrollPageChatToBottom();
        bodyInput.value = '';

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    body: body,
                    conversation_id: pageConversationId
                })
            });
            const data = await res.json();

            if (data.success) {
                if (data.data?.conversation_id) {
                    pageConversationId = data.data.conversation_id;
                }
                const refreshRes = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/messages`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
                });
                const refreshData = await refreshRes.json();
                if (refreshData.success && refreshData.data?.messages) {
                    const items = document.getElementById('pageMessageItems');
                    items.innerHTML = '';
                    refreshData.data.messages.forEach(msg => appendPageMessageBubble(msg));
                    scrollPageChatToBottom();
                }
            } else {
                alert(data.message || 'Could not send message.');
            }
        } catch (e) {
            alert('Server error while sending message.');
        } finally {
            btn.disabled = false;
        }
    }

    // 11. Invite Friends Workflow
    let eligibleFriendsCache = [];
    let selectedInviteUserIds = new Set();

    async function openInviteFriendsModal() {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif

        const modal = document.getElementById('inviteFriendsModal');
        modal.style.display = 'flex';

        const loading = document.getElementById('inviteFriendsLoading');
        const empty = document.getElementById('inviteFriendsEmpty');
        const items = document.getElementById('inviteFriendsItems');
        const searchInput = document.getElementById('inviteFriendSearch');

        if (searchInput) searchInput.value = '';
        selectedInviteUserIds.clear();
        updateInviteSubmitButton();

        loading.style.display = 'block';
        empty.style.display = 'none';
        items.innerHTML = '';

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/invitations/eligible-friends`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                }
            });
            const data = await res.json();
            loading.style.display = 'none';

            if (data.success && data.data && data.data.length > 0) {
                eligibleFriendsCache = data.data;
                renderInviteFriendsList(eligibleFriendsCache);
            } else {
                eligibleFriendsCache = [];
                empty.style.display = 'block';
            }
        } catch (e) {
            loading.style.display = 'none';
            empty.style.display = 'block';
        }
    }

    function closeInviteFriendsModal() {
        document.getElementById('inviteFriendsModal').style.display = 'none';
    }

    function renderInviteFriendsList(users) {
        const items = document.getElementById('inviteFriendsItems');
        const empty = document.getElementById('inviteFriendsEmpty');
        items.innerHTML = '';

        if (!users || users.length === 0) {
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';

        users.forEach(u => {
            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.alignItems = 'center';
            row.style.justifyContent = 'space-between';
            row.style.padding = '8px 10px';
            row.style.borderRadius = '8px';
            row.style.cursor = 'pointer';
            row.style.transition = 'background 0.15s ease';
            row.onmouseover = () => { row.style.background = '#f1f5f9'; };
            row.onmouseout = () => { row.style.background = 'transparent'; };

            const isChecked = selectedInviteUserIds.has(u.id);

            row.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #1877f2; font-size: 14px;">
                        ${u.avatar_url ? `<img src="${escapeHtml(u.avatar_url)}" alt="${escapeHtml(u.name)}" style="width: 100%; height: 100%; object-fit: cover;">` : escapeHtml((u.name || 'U').charAt(0))}
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 13.5px; color: var(--fb-text-primary); display: flex; align-items: center; gap: 4px;">
                            <span>${escapeHtml(u.name)}</span>
                            ${u.is_friend ? '<span style="font-size: 10px; background: #e0f2fe; color: #0284c7; padding: 1px 6px; border-radius: 10px; font-weight: 700;">Friend</span>' : ''}
                        </div>
                        <div style="font-size: 11.5px; color: #64748b;">@${escapeHtml(u.username)}</div>
                    </div>
                </div>
                <input type="checkbox" value="${u.id}" ${isChecked ? 'checked' : ''} style="width: 18px; height: 18px; cursor: pointer; accent-color: #1877f2;">
            `;

            const checkbox = row.querySelector('input[type="checkbox"]');
            row.onclick = (e) => {
                if (e.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                }
                if (checkbox.checked) {
                    selectedInviteUserIds.add(u.id);
                } else {
                    selectedInviteUserIds.delete(u.id);
                }
                updateInviteSubmitButton();
            };

            items.appendChild(row);
        });
    }

    function filterInviteFriendsList() {
        const query = (document.getElementById('inviteFriendSearch')?.value || '').toLowerCase().trim();
        if (!query) {
            renderInviteFriendsList(eligibleFriendsCache);
            return;
        }
        const filtered = eligibleFriendsCache.filter(u => 
            (u.name || '').toLowerCase().includes(query) ||
            (u.username || '').toLowerCase().includes(query)
        );
        renderInviteFriendsList(filtered);
    }

    function toggleSelectAllInvites() {
        const btn = document.getElementById('btnSelectAllInvites');
        const checkboxes = document.querySelectorAll('#inviteFriendsItems input[type="checkbox"]');
        const allSelected = selectedInviteUserIds.size === eligibleFriendsCache.length && eligibleFriendsCache.length > 0;

        if (allSelected) {
            selectedInviteUserIds.clear();
            checkboxes.forEach(cb => { cb.checked = false; });
            if (btn) btn.textContent = 'Select All';
        } else {
            eligibleFriendsCache.forEach(u => selectedInviteUserIds.add(u.id));
            checkboxes.forEach(cb => { cb.checked = true; });
            if (btn) btn.textContent = 'Deselect All';
        }
        updateInviteSubmitButton();
    }

    function updateInviteSubmitButton() {
        const btn = document.getElementById('btnSubmitInvitations');
        if (btn) {
            const count = selectedInviteUserIds.size;
            btn.textContent = `Send Invitations (${count})`;
            btn.disabled = count === 0;
        }
    }

    async function submitPageInvitations() {
        const ids = Array.from(selectedInviteUserIds);
        if (ids.length === 0) return;

        const btn = document.getElementById('btnSubmitInvitations');
        btn.disabled = true;
        btn.textContent = 'Sending...';

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/invitations`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    invitee_ids: ids
                })
            });
            const data = await res.json();

            if (data.success) {
                alert(`Successfully sent ${data.invitations_count || ids.length} invitation(s)!`);
                closeInviteFriendsModal();
                selectedInviteUserIds.clear();
            } else {
                alert(data.message || 'Failed to send invitations.');
            }
        } catch (e) {
            alert('Server error while sending invitations.');
        } finally {
            btn.disabled = false;
            updateInviteSubmitButton();
        }
    }

    // 12. Quick Actions: Event Creation & RSVP
    function handleQuickActionEvent() {
        @if($canManage)
            openCreateEventModal();
        @else
            switchPageTab('events');
        @endif
    }

    function openCreateEventModal() {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif
        const modal = document.getElementById('createEventModal');
        if (modal) {
            modal.style.display = 'flex';
        } else {
            switchPageTab('events');
        }
    }

    function closeCreateEventModal() {
        const modal = document.getElementById('createEventModal');
        if (modal) modal.style.display = 'none';
    }

    async function handleCreatePageEvent(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitCreateEvent');
        const title = document.getElementById('eventTitle').value.trim();
        const startTime = document.getElementById('eventStartTime').value;
        const endTime = document.getElementById('eventEndTime').value;
        const location = document.getElementById('eventLocation').value.trim();
        const isOnline = document.getElementById('eventIsOnline').checked ? 1 : 0;
        const description = document.getElementById('eventDescription').value.trim();

        if (!title || !startTime) {
            alert('Please provide an event title and start date.');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Publishing...';

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/events`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title,
                    start_time: startTime,
                    end_time: endTime || null,
                    location,
                    is_online: isOnline,
                    description
                })
            });
            const data = await res.json();

            if (data.success) {
                alert('Event created successfully!');
                closeCreateEventModal();
                switchPageTab('events');
                window.location.reload();
            } else {
                alert(data.message || 'Could not create event.');
            }
        } catch (e) {
            alert('Server error while creating event.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Publish Event';
        }
    }

    async function handleEventRsvp(eventId) {
        const btn = document.getElementById(`event-rsvp-btn-${eventId}`);
        const countSpan = document.getElementById(`event-rsvp-count-${eventId}`);
        if (btn) btn.disabled = true;

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/events/${eventId}/rsvp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: 'going' })
            });
            const data = await res.json();
            if (data.success) {
                if (btn) {
                    if (data.status === 'going') {
                        btn.className = 'btn-fb-secondary';
                        btn.innerHTML = '<span>✓ Going (অংশ নিচ্ছেন)</span>';
                    } else {
                        btn.className = 'btn-fb-primary';
                        btn.innerHTML = '<span>+ Going (অংশ নিন)</span>';
                    }
                }
                if (countSpan && typeof data.rsvp_count !== 'undefined') {
                    countSpan.textContent = `${data.rsvp_count} জন অংশ নিচ্ছেন`;
                }
            } else {
                alert(data.message || 'ব্যর্থ হয়েছে।');
            }
        } catch (e) {
            alert('সার্ভার ত্রুটি।');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    // Edit Event Modal
    function openEditEventModal(id, title, desc, loc, start, end, isOnline) {
        document.getElementById('editEventId').value = id;
        document.getElementById('editEventTitle').value = title || '';
        document.getElementById('editEventDescription').value = desc || '';
        document.getElementById('editEventLocation').value = loc || '';
        document.getElementById('editEventStartTime').value = start || '';
        document.getElementById('editEventEndTime').value = end || '';
        document.getElementById('editEventIsOnline').checked = Boolean(isOnline);
        document.getElementById('editEventModal').style.display = 'flex';
    }

    function closeEditEventModal() {
        document.getElementById('editEventModal').style.display = 'none';
    }

    async function handleUpdatePageEvent(e) {
        e.preventDefault();
        const id = document.getElementById('editEventId').value;
        const title = document.getElementById('editEventTitle').value.trim();
        const startTime = document.getElementById('editEventStartTime').value;
        const endTime = document.getElementById('editEventEndTime').value;
        const location = document.getElementById('editEventLocation').value.trim();
        const isOnline = document.getElementById('editEventIsOnline').checked ? 1 : 0;
        const description = document.getElementById('editEventDescription').value.trim();
        const btn = document.getElementById('btnSubmitUpdateEvent');

        if (!title || !startTime) {
            alert('অনুগ্রহ করে ইভেন্টের নাম এবং শুরুর সময় প্রদান করুন।');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'সংরক্ষণ হচ্ছে...';

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/events/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title,
                    start_time: startTime,
                    end_time: endTime || null,
                    location,
                    is_online: isOnline,
                    description
                })
            });
            const data = await res.json();
            if (data.success) {
                closeEditEventModal();
                window.location.reload();
            } else {
                alert(data.message || 'ইভেন্ট আপডেট করতে ব্যর্থ হয়েছে।');
            }
        } catch (e) {
            alert('সার্ভার যোগাযোগে ত্রুটি ঘটেছে।');
        } finally {
            btn.disabled = false;
            btn.textContent = 'পরিবর্তন সংরক্ষণ করুন';
        }
    }

    async function handleDeleteEvent(eventId) {
        if (!confirm('আপনি কি নিশ্চিত যে এই ইভেন্টটি মুছে ফেলতে চান?')) return;
        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/events/${eventId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'ইভেন্ট মুছে ফেলতে ব্যর্থ হয়েছে।');
            }
        } catch (e) {
            alert('সার্ভার যোগাযোগে ত্রুটি ঘটেছে।');
        }
    }

    // Photo Lightbox & Management
    let currentLightboxMediaId = null;

    function openPhotoLightbox(photoUrl, caption, createdAt, mediaId) {
        currentLightboxMediaId = mediaId || null;
        const imgEl = document.getElementById('photoLightboxImage');
        const capEl = document.getElementById('photoLightboxCaption');
        const dateEl = document.getElementById('photoLightboxDate');
        const delBtn = document.getElementById('photoLightboxDeleteBtn');

        if (imgEl) imgEl.src = photoUrl;
        if (capEl) capEl.textContent = caption || '';
        if (dateEl) dateEl.textContent = createdAt || '';
        if (delBtn) delBtn.style.display = (mediaId && CAN_MANAGE) ? 'inline-flex' : 'none';

        document.getElementById('photoLightboxModal').style.display = 'flex';
    }

    function closePhotoLightbox() {
        document.getElementById('photoLightboxModal').style.display = 'none';
        currentLightboxMediaId = null;
    }

    async function handleDeletePhotoFromLightbox() {
        if (!currentLightboxMediaId) return;
        if (!confirm('আপনি কি নিশ্চিত যে এই ছবিটি মুছে ফেলতে চান?')) return;

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/photos/${currentLightboxMediaId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                closePhotoLightbox();
                window.location.reload();
            } else {
                alert(data.message || 'ছবি মুছতে ব্যর্থ হয়েছে।');
            }
        } catch (e) {
            alert('সার্ভার যোগাযোগে ত্রুটি ঘটেছে।');
        }
    }

    // Photo Upload Modal
    function openPhotoUploadModal() {
        document.getElementById('photoUploadModal').style.display = 'flex';
    }

    function closePhotoUploadModal() {
        document.getElementById('photoUploadModal').style.display = 'none';
    }

    async function handlePhotoUploadSubmit(e) {
        e.preventDefault();
        const filesInput = document.getElementById('pagePhotoFileInput');
        const captionInput = document.getElementById('pagePhotoCaptionInput');
        const btn = document.getElementById('btnSubmitPhotoUpload');

        if (!filesInput.files || filesInput.files.length === 0) {
            alert('অনুগ্রহ করে অন্তত একটি ছবি নির্বাচন করুন।');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'আপলোড হচ্ছে...';

        const formData = new FormData();
        for (let i = 0; i < filesInput.files.length; i++) {
            formData.append('photos[]', filesInput.files[i]);
        }
        if (captionInput.value) {
            formData.append('caption', captionInput.value);
        }

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/photos`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                closePhotoUploadModal();
                switchPageTab('photos');
                window.location.reload();
            } else {
                alert(data.message || 'ছবি আপলোড করতে ব্যর্থ হয়েছে।');
            }
        } catch (err) {
            alert('সার্ভার যোগাযোগে ত্রুটি ঘটেছে।');
        } finally {
            btn.disabled = false;
            btn.textContent = 'ছবি আপলোড করুন';
        }
    }

    // Post Management: Pin / Edit / Delete
    async function handleTogglePinPost(postId) {
        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/posts/${postId}/pin`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'পিন স্ট্যাটাস পরিবর্তন করতে ব্যর্থ হয়েছে।');
            }
        } catch (e) {
            alert('সার্ভার যোগাযোগে ত্রুটি ঘটেছে।');
        }
    }

    function openEditPostModal(postId, content) {
        document.getElementById('editPostModalId').value = postId;
        document.getElementById('editPostModalContent').value = content || '';
        document.getElementById('editPostModal').style.display = 'flex';
    }

    function closeEditPostModal() {
        document.getElementById('editPostModal').style.display = 'none';
    }

    async function submitEditPostForm(e) {
        e.preventDefault();
        const postId = document.getElementById('editPostModalId').value;
        const content = document.getElementById('editPostModalContent').value.trim();
        const btn = document.getElementById('btnSubmitEditPost');

        btn.disabled = true;
        btn.textContent = 'সংরক্ষণ হচ্ছে...';

        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/posts/${postId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content })
            });
            const data = await res.json();
            if (data.success) {
                closeEditPostModal();
                window.location.reload();
            } else {
                alert(data.message || 'পোস্ট আপডেট করতে ব্যর্থ হয়েছে।');
            }
        } catch (err) {
            alert('সার্ভার যোগাযোগে ত্রুটি ঘটেছে।');
        } finally {
            btn.disabled = false;
            btn.textContent = 'সংরক্ষণ করুন';
        }
    }

    async function handleDeletePagePost(postId) {
        if (!confirm('আপনি কি নিশ্চিত যে এই পোস্টটি মুছে ফেলতে চান?')) return;
        try {
            const res = await fetch(`/pages/${encodeURIComponent(PAGE_SLUG)}/posts/${postId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                const card = document.getElementById(`post-card-${postId}`);
                if (card) {
                    card.remove();
                } else {
                    window.location.reload();
                }
            } else {
                alert(data.message || 'পোস্ট মুছে ফেলতে ব্যর্থ হয়েছে।');
            }
        } catch (e) {
            alert('সার্ভার যোগাযোগে ত্রুটি ঘটেছে।');
        }
    }

    // XSS Sanitizer Helper
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // 12. Realtime Echo Event Listener for Page Stream
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel(`page.${PAGE_ID}.feed`)
                .listen('.page.post.published', (e) => {
                    const target = document.getElementById('liveStreamTarget');
                    if (target && e.post) {
                        const newPostEl = document.createElement('div');
                        newPostEl.className = 'post-card';
                        newPostEl.style.borderLeft = '4px solid #059669';
                        newPostEl.innerHTML = `
                            <div style="font-size: 12px; color: #059669; font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path></svg>
                                <span>নতুন পোস্ট লাইভ প্রকাশিত হয়েছে</span>
                            </div>
                            <div style="font-size: 15px; color: var(--fb-text-primary); line-height: 1.6;">${escapeHtml(e.post.content || '')}</div>
                            <div style="margin-top: 10px; font-size: 12px; color: var(--fb-text-secondary); text-align: right;">এখনই রিফ্রেশ করুন সম্পূর্ণ দেখতে</div>
                        `;
                        target.prepend(newPostEl);
                    }
                });
        }
    });
</script>
@endsection
