@extends('layouts.app')

@section('title', 'স্টুডিও — ' . $page->name . ' — Bondhoo Enterprise Page Operating System')

@section('styles')
<style>
    .studio-container {
        max-width: 1280px;
        margin: 0 auto;
        padding-bottom: 60px;
    }

    .studio-header {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        box-shadow: var(--shadow-sm);
    }

    .studio-header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .studio-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        font-weight: 800;
        color: #1e293b;
        overflow: hidden;
        border: 2px solid var(--fb-border);
    }

    .studio-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .studio-title-area h1 {
        font-size: 22px;
        font-weight: 800;
        color: var(--fb-text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .badge-role {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        background: #e0f2fe;
        color: #0369a1;
        letter-spacing: 0.5px;
    }

    .studio-header-meta {
        font-size: 13px;
        color: var(--fb-text-secondary);
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 4px;
    }

    /* Tabs Bar */
    .studio-tabs {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 4px;
        margin-bottom: 24px;
        border-bottom: 1px solid var(--fb-border);
    }

    .studio-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 8px 8px 0 0;
        font-size: 14px;
        font-weight: 600;
        color: var(--fb-text-secondary);
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .studio-tab-btn:hover {
        color: var(--fb-primary);
        background: rgba(24, 119, 242, 0.05);
    }

    .studio-tab-btn.active {
        color: var(--fb-primary);
        border-bottom-color: var(--fb-primary);
        background: rgba(24, 119, 242, 0.08);
    }

    /* KPI Metrics Cards */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .metric-card {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 12px;
        padding: 18px;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .metric-title {
        font-size: 13px;
        color: var(--fb-text-secondary);
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .metric-value {
        font-size: 26px;
        font-weight: 800;
        color: var(--fb-text-primary);
        margin: 10px 0 4px;
    }

    .metric-sub {
        font-size: 12px;
        color: #10b981;
        font-weight: 600;
    }

    /* Tab Panes */
    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }

    /* Cards & Containers */
    .panel-card {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm);
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--fb-border);
    }

    .panel-title {
        font-size: 17px;
        font-weight: 700;
        color: var(--fb-text-primary);
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }

    /* Tables */
    .studio-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .studio-table th {
        text-align: left;
        padding: 12px 14px;
        background: rgba(0, 0, 0, 0.02);
        color: var(--fb-text-secondary);
        font-weight: 600;
        border-bottom: 1px solid var(--fb-border);
    }

    .studio-table td {
        padding: 14px;
        border-bottom: 1px solid var(--fb-border);
        color: var(--fb-text-primary);
    }

    .studio-table tr:hover td {
        background: rgba(0, 0, 0, 0.015);
    }

    /* Forms & Inputs */
    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--fb-text-primary);
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid var(--fb-border);
        background: var(--fb-card);
        color: var(--fb-text-primary);
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .form-control:focus {
        border-color: var(--fb-primary);
    }

    /* Action Buttons */
    .btn-action-primary {
        background: var(--fb-primary);
        color: #ffffff;
        font-weight: 600;
        padding: 9px 16px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        text-decoration: none;
        transition: background 0.2s;
    }

    .btn-action-primary:hover {
        background: #166fe5;
    }

    .btn-action-secondary {
        background: var(--fb-secondary);
        color: var(--fb-text-primary);
        font-weight: 600;
        padding: 9px 16px;
        border-radius: 8px;
        border: 1px solid var(--fb-border);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        text-decoration: none;
    }

    .btn-action-danger {
        background: #fee2e2;
        color: #dc2626;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
    }

    .btn-action-danger:hover {
        background: #fecaca;
    }

    /* Modal */
    .studio-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .studio-modal {
        background: var(--fb-card);
        border-radius: 12px;
        width: 100%;
        max-width: 560px;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--fb-border);
        overflow: hidden;
    }

    .studio-modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--fb-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .studio-modal-body {
        padding: 20px;
        max-height: 80vh;
        overflow-y: auto;
    }

    .studio-modal-footer {
        padding: 14px 20px;
        border-top: 1px solid var(--fb-border);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background: rgba(0, 0, 0, 0.01);
    }

    /* Icon SVGs standardized Feather/Lucide style */
    .icon-svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
        stroke-width: 2;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
        display: inline-block;
        vertical-align: middle;
    }

    .icon-svg-sm {
        width: 15px;
        height: 15px;
        stroke: currentColor;
        stroke-width: 2;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
        display: inline-block;
        vertical-align: middle;
    }
</style>
@endsection

@section('content')
<div class="studio-container">
    <!-- Top Header -->
    <div class="studio-header">
        <div class="studio-header-left">
            <div class="studio-avatar">
                @if($page->avatar_url)
                    <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}">
                @else
                    {{ mb_substr($page->name, 0, 1) }}
                @endif
            </div>
            <div class="studio-title-area">
                <h1>
                    <span>{{ $page->name }}</span>
                    @if($page->is_verified)
                        <svg class="icon-svg" style="color: var(--fb-primary);" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    @endif
                    <span class="badge-role">{{ $userRole }}</span>
                </h1>
                <div class="studio-header-meta">
                    <span>
                        <svg class="icon-svg-sm" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                        {{ $page->category }}
                    </span>
                    <span>•</span>
                    <span>
                        <svg class="icon-svg-sm" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span id="headerFollowersCount">{{ number_format($page->followers_count) }}</span> ফলোয়ার
                    </span>
                    <span>•</span>
                    <span>
                        <svg class="icon-svg-sm" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                        {{ $page->username ? '@' . $page->username : $page->slug }}
                    </span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="/pages/{{ $page->slug }}" class="btn-action-secondary" target="_blank">
                <svg class="icon-svg" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                পাবলিক ভিউ
            </a>
            <button class="btn-action-primary" onclick="openCreatePostModal()">
                <svg class="icon-svg" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                নতুন পোস্ট
            </button>
        </div>
    </div>

    <!-- Live Performance Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-title">
                <span>মোট ফলোয়ার্স</span>
                <svg class="icon-svg" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            </div>
            <div class="metric-value" id="kpiFollowers">{{ number_format($page->followers_count) }}</div>
            <div class="metric-sub" id="kpiNewFollowers">+০ নতুন (গত ৩০ দিনে)</div>
        </div>

        <div class="metric-card">
            <div class="metric-title">
                <span>মোট কনটেন্ট পোস্ট</span>
                <svg class="icon-svg" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </div>
            <div class="metric-value" id="kpiPosts">{{ number_format($page->posts_count) }}</div>
            <div class="metric-sub" id="kpiScheduledCount" style="color: #6366f1;">০ শিডিউলড পোস্ট</div>
        </div>

        <div class="metric-card">
            <div class="metric-title">
                <span>মোট এনগেজমেন্ট</span>
                <svg class="icon-svg" viewBox="0 0 24 24"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
            </div>
            <div class="metric-value" id="kpiEngagements">০</div>
            <div class="metric-sub" id="kpiEngagementRate">০% এনগেজমেন্ট রেট</div>
        </div>

        <div class="metric-card">
            <div class="metric-title">
                <span>ইনবক্স কনভারসেশন</span>
                <svg class="icon-svg" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </div>
            <div class="metric-value" id="kpiConversations">০</div>
            <div class="metric-sub" id="kpiOpenConversations" style="color: #0284c7;">০ উন্মুক্ত চ্যাট</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="studio-tabs">
        <button class="studio-tab-btn active" onclick="switchStudioTab('overview')">
            <svg class="icon-svg" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            ওভারভিউ
        </button>
        <button class="studio-tab-btn" onclick="switchStudioTab('content')">
            <svg class="icon-svg" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            কনটেন্ট ও শিডিউলিং
        </button>
        <button class="studio-tab-btn" onclick="switchStudioTab('team')">
            <svg class="icon-svg" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            টিম ও আরব্যাক
        </button>
        <button class="studio-tab-btn" onclick="switchStudioTab('inbox')">
            <svg class="icon-svg" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            ইনবক্স মেসেজিং
        </button>
        <button class="studio-tab-btn" onclick="switchStudioTab('moderation')">
            <svg class="icon-svg" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            মডারেশন সেন্টার
        </button>
        <button class="studio-tab-btn" onclick="switchStudioTab('events')">
            <svg class="icon-svg" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            ইভেন্টস
        </button>
        <button class="studio-tab-btn" onclick="switchStudioTab('commerce')">
            <svg class="icon-svg" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            শপ ও প্রোডাক্টস
        </button>
        <button class="studio-tab-btn" onclick="switchStudioTab('audit')">
            <svg class="icon-svg" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            অডিট ট্রেইল
        </button>
        <button class="studio-tab-btn" onclick="switchStudioTab('settings')">
            <svg class="icon-svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            সেটিংস
        </button>
    </div>

    <!-- TAB 1: OVERVIEW & REAL ANALYTICS -->
    <div id="tab-overview" class="tab-pane active">
        <div class="panel-card">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="icon-svg" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    শীর্ষ পারফর্মিং কনটেন্ট
                </h2>
                <button class="btn-action-secondary" onclick="loadOverviewAnalytics()">
                    <svg class="icon-svg-sm" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    রিফ্রেশ
                </button>
            </div>
            <div id="topContentContainer">
                <div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">লোড হচ্ছে...</div>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="icon-svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                    অডিয়েন্স ভৌগোলিক ডিস্ট্রিবিউশন (রিয়েল ডাটাবেজ এগ্রিগেশন)
                </h2>
            </div>
            <div id="audienceCitiesContainer">
                <div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">লোড হচ্ছে...</div>
            </div>
        </div>
    </div>

    <!-- TAB 2: CONTENT & SCHEDULING -->
    <div id="tab-content" class="tab-pane">
        <div class="panel-card">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="icon-svg" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    পেজ পোস্ট ম্যানেজমেন্ট
                </h2>
                <div style="display: flex; gap: 8px;">
                    <select id="contentFilterStatus" class="form-control" style="width: auto; padding: 6px 12px;" onchange="loadContentPosts()">
                        <option value="published">প্রকাশিত পোস্ট</option>
                        <option value="scheduled">শিডিউলড পোস্ট</option>
                        <option value="drafts">ড্রাফট পোস্ট</option>
                    </select>
                    <button class="btn-action-primary" onclick="openCreatePostModal()">
                        <svg class="icon-svg-sm" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        তৈরি করুন
                    </button>
                </div>
            </div>
            <div id="contentTableContainer">
                <div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">পোস্ট লোড হচ্ছে...</div>
            </div>
        </div>
    </div>

    <!-- TAB 3: TEAM & RBAC -->
    <div id="tab-team" class="tab-pane">
        <div class="panel-card">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="icon-svg" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    টিম মেম্বার ও অ্যাক্সেস কন্ট্রোল (RBAC)
                </h2>
                <button class="btn-action-primary" onclick="openInviteTeamModal()">
                    <svg class="icon-svg-sm" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    মেম্বার ইনভাইট করুন
                </button>
            </div>
            <div id="teamTableContainer">
                <div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">টিম মেম্বার লোড হচ্ছে...</div>
            </div>
        </div>
    </div>

    <!-- TAB 4: INBOX MESSAGING -->
    <div id="tab-inbox" class="tab-pane">
        <div class="panel-card">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="icon-svg" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    মাল্টি-এজেন্ট ইনবক্স কনভারসেশন
                </h2>
                <button class="btn-action-secondary" onclick="loadInboxConversations()">
                    <svg class="icon-svg-sm" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    রিফ্রেশ
                </button>
            </div>
            <div id="inboxContainer">
                <div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">ইনবক্স লোড হচ্ছে...</div>
            </div>
        </div>
    </div>

    <!-- TAB 5: MODERATION CENTER -->
    <div id="tab-moderation" class="tab-pane">
        <div class="panel-card">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="icon-svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                    ব্লক করা ব্যবহারকারী তালিকা
                </h2>
                <button class="btn-action-primary" onclick="openBlockUserModal()">
                    <svg class="icon-svg-sm" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    ইউজার ব্লক করুন
                </button>
            </div>
            <div id="blockedUsersContainer">
                <div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">লোড হচ্ছে...</div>
            </div>
        </div>
    </div>

    <!-- TAB 6: EVENTS -->
    <div id="tab-events" class="tab-pane">
        <div class="panel-card">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="icon-svg" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    পেজ ইভেন্টসমূহ
                </h2>
                <button class="btn-action-primary" onclick="openCreateEventModal()">
                    <svg class="icon-svg-sm" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    নতুন ইভেন্ট
                </button>
            </div>
            <div id="eventsContainer">
                <div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">ইভেন্ট লোড হচ্ছে...</div>
            </div>
        </div>
    </div>

    <!-- TAB 7: COMMERCE & PRODUCTS -->
    <div id="tab-commerce" class="tab-pane">
        <div class="panel-card">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="icon-svg" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    প্রোডাক্ট ক্যাটালগ
                </h2>
                <button class="btn-action-primary" onclick="openCreateProductModal()">
                    <svg class="icon-svg-sm" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    নতুন প্রোডাক্ট
                </button>
            </div>
            <div id="productsContainer">
                <div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">প্রোডাক্ট লোড হচ্ছে...</div>
            </div>
        </div>
    </div>

    <!-- TAB 8: AUDIT TRAIL -->
    <div id="tab-audit" class="tab-pane">
        <div class="panel-card">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="icon-svg" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    ইমিউটেবল অডিট লগ ও সিকিউরিটি মিউটেশন
                </h2>
                <button class="btn-action-secondary" onclick="loadAuditLogs()">
                    <svg class="icon-svg-sm" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    রিফ্রেশ
                </button>
            </div>
            <div id="auditLogsContainer">
                <div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">অডিট লগ লোড হচ্ছে...</div>
            </div>
        </div>
    </div>

    <!-- TAB 9: SETTINGS -->
    <div id="tab-settings" class="tab-pane">
        <div class="panel-card">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="icon-svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    পেজ সেটিংস ও কনফিগারেশন
                </h2>
            </div>

            <form onsubmit="savePageSettings(event)">
                <div class="form-group">
                    <label class="form-label">অটো-রিপ্লাই সক্রিয় করুন</label>
                    <input type="checkbox" id="settingAutoReplyEnabled" {{ !empty($page->settings['messaging']['auto_reply_enabled']) ? 'checked' : '' }}>
                </div>
                <div class="form-group">
                    <label class="form-label">অটো-রিপ্লাই বার্তা</label>
                    <textarea id="settingAutoReplyMessage" rows="3" class="form-control" placeholder="ধন্যবাদ আমাদের সাথে যোগাযোগের জন্য! আমরা দ্রুত উত্তর দিচ্ছি।">{{ $page->settings['messaging']['auto_reply_message'] ?? '' }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">ব্লক করা কিওয়ার্ড (কমা দিয়ে পৃথক করুন)</label>
                    <input type="text" id="settingBlockedKeywords" class="form-control" value="{{ implode(', ', $page->settings['moderation']['blocked_keywords'] ?? []) }}" placeholder="scam, spam, abusive">
                </div>
                <button type="submit" class="btn-action-primary">সেটিংস সংরক্ষণ করুন</button>
            </form>

            @if((int) $page->owner_id === (int) $currentUser->id)
                <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid #fee2e2;">
                    <h3 style="color: #dc2626; font-size: 16px; font-weight: 700;">বিপজ্জনক এলাকা (Danger Zone)</h3>
                    <p style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 16px;">
                        পেজ ডিলিট করলে এটি ট্র্যাশে চলে যাবে এবং ৩০ দিন পর স্থায়ীভাবে মুছে ফেলা হতে পারে।
                    </p>
                    <button class="btn-action-danger" onclick="deletePageToTrash()">
                        <svg class="icon-svg-sm" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        পেইজ ট্র্যাশে স্থানান্তর করুন
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- MODAL: CREATE / SCHEDULE POST -->
<div id="modalCreatePost" class="studio-modal-overlay">
    <div class="studio-modal">
        <div class="studio-modal-header">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">নতুন পোস্ট তৈরি ও শিডিউল</h3>
            <button style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);" onclick="closeModal('modalCreatePost')">✕</button>
        </div>
        <form onsubmit="submitStudioPost(event)">
            <div class="studio-modal-body">
                <div class="form-group">
                    <label class="form-label">পোস্ট কনটেন্ট</label>
                    <textarea id="modalPostContent" rows="4" class="form-control" placeholder="এই পেইজ হিসেবে কিছু লিখুন..." required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">পোস্টের ধরন</label>
                    <select id="modalPostStatus" class="form-control" onchange="toggleScheduledDateInput()">
                        <option value="published">সরাসরি প্রকাশ (Publish Now)</option>
                        <option value="scheduled">শিডিউল করুন (Schedule for Future)</option>
                        <option value="draft">ড্রাফট হিসেবে সংরক্ষণ (Save as Draft)</option>
                    </select>
                </div>
                <div class="form-group" id="scheduledAtGroup" style="display: none;">
                    <label class="form-label">শিডিউল প্রকাশের তারিখ ও সময়</label>
                    <input type="datetime-local" id="modalPostScheduledAt" class="form-control">
                </div>
            </div>
            <div class="studio-modal-footer">
                <button type="button" class="btn-action-secondary" onclick="closeModal('modalCreatePost')">বাতিল</button>
                <button type="submit" class="btn-action-primary" id="btnSubmitPost">নিশ্চিত করুন</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: INVITE TEAM MEMBER -->
<div id="modalInviteTeam" class="studio-modal-overlay">
    <div class="studio-modal">
        <div class="studio-modal-header">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">টিমে নতুন মেম্বার যুক্ত করুন</h3>
            <button style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);" onclick="closeModal('modalInviteTeam')">✕</button>
        </div>
        <form onsubmit="submitTeamInvite(event)">
            <div class="studio-modal-body">
                <div class="form-group">
                    <label class="form-label">ইউজারনেম বা ইমেইল</label>
                    <input type="text" id="inviteUserIdentifier" class="form-control" placeholder="rahim অথবা user@bondhoo.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">ভূমিকা (Role)</label>
                    <select id="inviteRole" class="form-control">
                        <option value="admin">Admin (পূর্ণ নিয়ন্ত্রণ)</option>
                        <option value="manager">Manager (ম্যানেজার)</option>
                        <option value="content_manager">Content Manager (কনটেন্ট ও শিডিউলিং)</option>
                        <option value="moderator">Moderator (মডারেশন ও ইনবক্স)</option>
                        <option value="analyst">Analyst (অ্যানালিটিক্স)</option>
                        <option value="editor">Editor (এডিটর)</option>
                        <option value="viewer">Viewer (শুধু দেখার অনুমতি)</option>
                    </select>
                </div>
            </div>
            <div class="studio-modal-footer">
                <button type="button" class="btn-action-secondary" onclick="closeModal('modalInviteTeam')">বাতিল</button>
                <button type="submit" class="btn-action-primary">ইনভাইট পাঠান</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: BLOCK USER -->
<div id="modalBlockUser" class="studio-modal-overlay">
    <div class="studio-modal">
        <div class="studio-modal-header">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">ব্যবহারকারীকে ব্লক করুন</h3>
            <button style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);" onclick="closeModal('modalBlockUser')">✕</button>
        </div>
        <form onsubmit="submitBlockUser(event)">
            <div class="studio-modal-body">
                <div class="form-group">
                    <label class="form-label">টার্গেট ইউজার আইডি</label>
                    <input type="number" id="blockTargetUserId" class="form-control" placeholder="যেমন: 2" required>
                </div>
                <div class="form-group">
                    <label class="form-label">ব্লক করার কারণ (ঐচ্ছিক)</label>
                    <textarea id="blockReason" rows="2" class="form-control" placeholder="স্প্যামিং বা আপত্তিকর মন্তব্য..."></textarea>
                </div>
            </div>
            <div class="studio-modal-footer">
                <button type="button" class="btn-action-secondary" onclick="closeModal('modalBlockUser')">বাতিল</button>
                <button type="submit" class="btn-action-danger">ব্লক করুন</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: CREATE EVENT -->
<div id="modalCreateEvent" class="studio-modal-overlay">
    <div class="studio-modal">
        <div class="studio-modal-header">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">নতুন পেজ ইভেন্ট তৈরি করুন</h3>
            <button style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);" onclick="closeModal('modalCreateEvent')">✕</button>
        </div>
        <form onsubmit="submitCreateEvent(event)">
            <div class="studio-modal-body">
                <div class="form-group">
                    <label class="form-label">ইভেন্টের শিরোনাম</label>
                    <input type="text" id="eventTitle" class="form-control" placeholder="যেমন: টেক সামিট ২০২৬" required>
                </div>
                <div class="form-group">
                    <label class="form-label">স্থান বা প্ল্যাটফর্ম</label>
                    <input type="text" id="eventLocation" class="form-control" placeholder="ঢাকা অথবা অনলাইন ওয়েবিনার">
                </div>
                <div class="form-group">
                    <label class="form-label">শুরুর তারিখ ও সময়</label>
                    <input type="datetime-local" id="eventStartTime" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">বিবরণ</label>
                    <textarea id="eventDescription" rows="3" class="form-control" placeholder="ইভেন্টের বিস্তারিত তথ্য..."></textarea>
                </div>
            </div>
            <div class="studio-modal-footer">
                <button type="button" class="btn-action-secondary" onclick="closeModal('modalCreateEvent')">বাতিল</button>
                <button type="submit" class="btn-action-primary">ইভেন্ট প্রকাশ করুন</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: CREATE PRODUCT -->
<div id="modalCreateProduct" class="studio-modal-overlay">
    <div class="studio-modal">
        <div class="studio-modal-header">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">নতুন প্রোডাক্ট যোগ করুন</h3>
            <button style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);" onclick="closeModal('modalCreateProduct')">✕</button>
        </div>
        <form onsubmit="submitCreateProduct(event)">
            <div class="studio-modal-body">
                <div class="form-group">
                    <label class="form-label">প্রোডাক্টের নাম</label>
                    <input type="text" id="productTitle" class="form-control" placeholder="যেমন: কটন টি-শার্ট" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">মূল্য (টাকা)</label>
                        <input type="number" id="productPrice" step="0.01" class="form-control" placeholder="750" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">মজুত পরিমাণ (Stock)</label>
                        <input type="number" id="productStock" class="form-control" value="10">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">বিবরণ</label>
                    <textarea id="productDescription" rows="2" class="form-control" placeholder="প্রোডাক্টের বিশদ বিবরণ..."></textarea>
                </div>
            </div>
            <div class="studio-modal-footer">
                <button type="button" class="btn-action-secondary" onclick="closeModal('modalCreateProduct')">বাতিল</button>
                <button type="submit" class="btn-action-primary">প্রোডাক্ট যোগ করুন</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const PAGE_ID = {{ $page->id }};
    const CSRF_TOKEN = '{{ csrf_token() }}';

    function getAuthHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    function switchStudioTab(tabName) {
        document.querySelectorAll('.studio-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

        const targetPane = document.getElementById('tab-' + tabName);
        if (targetPane) {
            targetPane.classList.add('active');
        }

        const clickedBtn = event ? event.currentTarget : null;
        if (clickedBtn && clickedBtn.classList.contains('studio-tab-btn')) {
            clickedBtn.classList.add('active');
        }

        // Lazy load tab contents
        if (tabName === 'overview') loadOverviewAnalytics();
        if (tabName === 'content') loadContentPosts();
        if (tabName === 'team') loadTeamMembers();
        if (tabName === 'inbox') loadInboxConversations();
        if (tabName === 'moderation') loadBlockedUsers();
        if (tabName === 'events') loadEvents();
        if (tabName === 'commerce') loadProducts();
        if (tabName === 'audit') loadAuditLogs();
    }

    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function openCreatePostModal() {
        openModal('modalCreatePost');
    }

    function openInviteTeamModal() {
        openModal('modalInviteTeam');
    }

    function openBlockUserModal() {
        openModal('modalBlockUser');
    }

    function openCreateEventModal() {
        openModal('modalCreateEvent');
    }

    function openCreateProductModal() {
        openModal('modalCreateProduct');
    }

    function toggleScheduledDateInput() {
        const status = document.getElementById('modalPostStatus').value;
        const group = document.getElementById('scheduledAtGroup');
        group.style.display = status === 'scheduled' ? 'block' : 'none';
    }

    // 1. OVERVIEW & ANALYTICS LOAD
    async function loadOverviewAnalytics() {
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/analytics/overview?days=30`, {
                headers: getAuthHeaders()
            });
            const result = await res.json();
            if (result.success) {
                const s = result.data.summary;
                document.getElementById('kpiFollowers').innerText = Number(s.total_followers).toLocaleString();
                document.getElementById('kpiNewFollowers').innerText = `+${s.new_followers} নতুন (গত ৩০ দিনে)`;
                document.getElementById('kpiPosts').innerText = Number(s.total_posts).toLocaleString();
                document.getElementById('kpiScheduledCount').innerText = `${s.scheduled_posts} শিডিউলড পোস্ট`;
                document.getElementById('kpiEngagements').innerText = Number(s.total_engagements).toLocaleString();
                document.getElementById('kpiEngagementRate').innerText = `${s.engagement_rate} এনগেজমেন্ট রেট`;
                
                const m = result.data.messaging;
                document.getElementById('kpiConversations').innerText = m.total_conversations;
                document.getElementById('kpiOpenConversations').innerText = `${m.open_conversations} উন্মুক্ত চ্যাট`;

                // Top Content render
                const topPosts = result.data.top_content || [];
                const topDiv = document.getElementById('topContentContainer');
                if (topPosts.length === 0) {
                    topDiv.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--fb-text-secondary);">কোনো কনটেন্ট পোস্ট পাওয়া যায়নি।</div>';
                } else {
                    let html = '<table class="studio-table"><thead><tr><th>পোস্ট</th><th>লাইক</th><th>মন্তব্য</th><th>শেয়ার</th><th>তারিখ</th></tr></thead><tbody>';
                    topPosts.forEach(p => {
                        html += `<tr>
                            <td style="max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${p.content}</td>
                            <td>${p.likes_count || 0}</td>
                            <td>${p.comments_count || 0}</td>
                            <td>${p.shares_count || 0}</td>
                            <td>${new Date(p.created_at).toLocaleDateString()}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    topDiv.innerHTML = html;
                }
            }

            // Audience
            const audRes = await fetch(`/api/v2/pages/${PAGE_ID}/analytics/audience`, { headers: getAuthHeaders() });
            const audData = await audRes.json();
            if (audData.success) {
                const cities = audData.data.top_cities || {};
                const audDiv = document.getElementById('audienceCitiesContainer');
                let cityHtml = '<div style="display: flex; gap: 16px; flex-wrap: wrap;">';
                for (const [city, count] of Object.entries(cities)) {
                    cityHtml += `<div style="background: rgba(0,0,0,0.03); padding: 12px 18px; border-radius: 8px;">
                        <div style="font-size: 13px; color: var(--fb-text-secondary);">${city}</div>
                        <div style="font-size: 18px; font-weight: 700; color: var(--fb-text-primary);">${count} জন</div>
                    </div>`;
                }
                cityHtml += '</div>';
                audDiv.innerHTML = cityHtml;
            }
        } catch (e) {
            console.error('Error loading analytics:', e);
        }
    }

    // 2. CONTENT POSTS LOAD
    async function loadContentPosts() {
        const filter = document.getElementById('contentFilterStatus').value;
        const container = document.getElementById('contentTableContainer');
        container.innerHTML = '<div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">লোড হচ্ছে...</div>';

        let url = `/api/v2/pages/${PAGE_ID}/posts`;
        if (filter === 'drafts') url = `/api/v2/pages/${PAGE_ID}/posts/drafts`;
        if (filter === 'scheduled') url = `/api/v2/pages/${PAGE_ID}/posts/scheduled`;

        try {
            const res = await fetch(url, { headers: getAuthHeaders() });
            const result = await res.json();
            const posts = result.data || [];

            if (posts.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 30px; color: var(--fb-text-secondary);">কোনো পোস্ট নেই।</div>';
                return;
            }

            let html = '<table class="studio-table"><thead><tr><th>কনটেন্ট</th><th>লেখক</th><th>স্ট্যাটাস</th><th>সময়সূচী</th><th>অ্যাকশন</th></tr></thead><tbody>';
            posts.forEach(p => {
                html += `<tr>
                    <td style="max-width: 350px;">${p.content}</td>
                    <td>${p.author ? p.author.name : 'পেইজ'}</td>
                    <td><span class="badge-role">${p.status || 'published'}</span></td>
                    <td>${p.scheduled_at ? new Date(p.scheduled_at).toLocaleString() : new Date(p.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn-action-danger" style="padding: 4px 8px; font-size: 11px;" onclick="deleteContentPost(${p.id})">মুছুন</button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div style="color: #dc2626; padding: 20px;">পোস্ট লোড করা যায়নি।</div>';
        }
    }

    // CREATE POST SUBMIT
    async function submitStudioPost(e) {
        e.preventDefault();
        const content = document.getElementById('modalPostContent').value.trim();
        const status = document.getElementById('modalPostStatus').value;
        const scheduled_at = document.getElementById('modalPostScheduledAt').value;

        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/posts`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({
                    content,
                    status,
                    scheduled_at: status === 'scheduled' ? scheduled_at : null
                })
            });
            const result = await res.json();
            if (result.success) {
                alert(result.message);
                closeModal('modalCreatePost');
                document.getElementById('modalPostContent').value = '';
                loadContentPosts();
                loadOverviewAnalytics();
            } else {
                alert(result.message || 'ব্যর্থ হয়েছে');
            }
        } catch (e) {
            alert('সার্ভার যোগাযোগে সমস্যা');
        }
    }

    async function deleteContentPost(postId) {
        if (!confirm('আপনি কি এই পোস্টটি মুছে ফেলতে চান?')) return;
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/posts/${postId}`, {
                method: 'DELETE',
                headers: getAuthHeaders()
            });
            const result = await res.json();
            if (result.success) {
                alert('পোস্ট মুছে ফেলা হয়েছে।');
                loadContentPosts();
            } else {
                alert(result.message || 'ব্যর্থ');
            }
        } catch (e) {
            alert('ত্রুটি');
        }
    }

    // 3. TEAM MEMBERS LOAD
    async function loadTeamMembers() {
        const container = document.getElementById('teamTableContainer');
        container.innerHTML = '<div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">লোড হচ্ছে...</div>';
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/team`, { headers: getAuthHeaders() });
            const result = await res.json();
            const members = result.data || [];

            let html = '<table class="studio-table"><thead><tr><th>সদস্য</th><th>ইউজারনেম</th><th>ভূমিকা (Role)</th><th>যোগদান</th><th>অ্যাকশন</th></tr></thead><tbody>';
            members.forEach(m => {
                html += `<tr>
                    <td><strong>${m.name}</strong></td>
                    <td>@${m.username}</td>
                    <td><span class="badge-role">${m.role}</span></td>
                    <td>${m.joined_at ? new Date(m.joined_at).toLocaleDateString() : 'আমন্ত্রিত'}</td>
                    <td>
                        ${m.role !== 'owner' ? `<button class="btn-action-danger" style="padding: 4px 8px; font-size: 11px;" onclick="removeTeamMember(${m.member_id})">অপসারণ</button>` : '<span style="font-size:12px; color:var(--fb-text-secondary);">মালিক</span>'}
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div style="color: #dc2626; padding: 20px;">টিম মেম্বার তালিকা লোড করা যায়নি।</div>';
        }
    }

    async function submitTeamInvite(e) {
        e.preventDefault();
        const identifier = document.getElementById('inviteUserIdentifier').value.trim();
        const role = document.getElementById('inviteRole').value;

        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/team/invite`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({
                    email_or_username: identifier,
                    role: role
                })
            });
            const result = await res.json();
            if (result.success) {
                alert(result.message);
                closeModal('modalInviteTeam');
                loadTeamMembers();
            } else {
                alert(result.message || 'ব্যর্থ হয়েছে');
            }
        } catch (e) {
            alert('সার্ভার যোগাযোগে সমস্যা');
        }
    }

    async function removeTeamMember(memberId) {
        if (!confirm('আপনি কি এই মেম্বারকে টিম থেকে অপসারণ করতে চান?')) return;
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/team/${memberId}`, {
                method: 'DELETE',
                headers: getAuthHeaders()
            });
            const result = await res.json();
            if (result.success) {
                alert('মেম্বার অপসারিত হয়েছে।');
                loadTeamMembers();
            } else {
                alert(result.message || 'ব্যর্থ');
            }
        } catch (e) {
            alert('ত্রুটি');
        }
    }

    // 4. INBOX CONVERSATIONS
    async function loadInboxConversations() {
        const container = document.getElementById('inboxContainer');
        container.innerHTML = '<div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">ইনবক্স লোড হচ্ছে...</div>';
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/inbox/conversations`, { headers: getAuthHeaders() });
            const result = await res.json();
            const convs = result.data || [];

            if (convs.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 30px; color: var(--fb-text-secondary);">কোনো কনভারসেশন নেই।</div>';
                return;
            }

            let html = '<table class="studio-table"><thead><tr><th>ব্যবহারকারী</th><th>স্ট্যাটাস</th><th>অ্যাসাইনকৃত এজেন্ট</th><th>সর্বশেষ বার্তা</th><th>অ্যাকশন</th></tr></thead><tbody>';
            convs.forEach(c => {
                html += `<tr>
                    <td><strong>${c.user ? c.user.name : 'Unknown'}</strong></td>
                    <td><span class="badge-role">${c.status}</span></td>
                    <td>${c.assigned_agent ? c.assigned_agent.name : 'অ্যাসাইন করা হয়নি'}</td>
                    <td>${c.last_message_at ? new Date(c.last_message_at).toLocaleString() : 'নতুন'}</td>
                    <td>
                        <button class="btn-action-primary" style="padding: 4px 8px; font-size: 11px;" onclick="replyConversation(${c.id})">উত্তর দিন</button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div style="color: #dc2626; padding: 20px;">ইনবক্স লোড করা যায়নি।</div>';
        }
    }

    async function replyConversation(convId) {
        const body = prompt('বার্তা লিখুন:');
        if (!body) return;
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/inbox/conversations/${convId}/reply`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({ body })
            });
            const result = await res.json();
            if (result.success) {
                alert('বার্তা পাঠানো হয়েছে।');
                loadInboxConversations();
            } else {
                alert(result.message || 'ব্যর্থ');
            }
        } catch (e) {
            alert('ত্রুটি');
        }
    }

    // 5. BLOCKED USERS
    async function loadBlockedUsers() {
        const container = document.getElementById('blockedUsersContainer');
        container.innerHTML = '<div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">লোড হচ্ছে...</div>';
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/moderation/blocked`, { headers: getAuthHeaders() });
            const result = await res.json();
            const blocked = result.data || [];

            if (blocked.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 30px; color: var(--fb-text-secondary);">ব্লক করা কোনো ইউজার নেই।</div>';
                return;
            }

            let html = '<table class="studio-table"><thead><tr><th>ইউজার</th><th>কারণ</th><th>ব্লককারী</th><th>তারিখ</th><th>অ্যাকশন</th></tr></thead><tbody>';
            blocked.forEach(b => {
                html += `<tr>
                    <td><strong>${b.user ? b.user.name : 'User #' + b.user_id}</strong></td>
                    <td>${b.reason || 'উল্লেখ নেই'}</td>
                    <td>${b.blocked_by ? b.blocked_by.name : 'অ্যাডমিন'}</td>
                    <td>${new Date(b.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn-action-secondary" style="padding: 4px 8px; font-size: 11px;" onclick="unblockUser(${b.user_id})">আনব্লক</button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div style="color: #dc2626; padding: 20px;">লোড করা যায়নি।</div>';
        }
    }

    async function submitBlockUser(e) {
        e.preventDefault();
        const target_user_id = document.getElementById('blockTargetUserId').value;
        const reason = document.getElementById('blockReason').value.trim();

        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/moderation/block`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({ target_user_id, reason })
            });
            const result = await res.json();
            if (result.success) {
                alert(result.message);
                closeModal('modalBlockUser');
                loadBlockedUsers();
            } else {
                alert(result.message || 'ব্যর্থ');
            }
        } catch (e) {
            alert('ত্রুটি');
        }
    }

    async function unblockUser(userId) {
        if (!confirm('ইউজারকে আনব্লক করতে চান?')) return;
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/moderation/unblock/${userId}`, {
                method: 'DELETE',
                headers: getAuthHeaders()
            });
            const result = await res.json();
            if (result.success) {
                alert('ইউজার আনব্লক হয়েছে।');
                loadBlockedUsers();
            } else {
                alert(result.message || 'ব্যর্থ');
            }
        } catch (e) {
            alert('ত্রুটি');
        }
    }

    // 6. EVENTS
    async function loadEvents() {
        const container = document.getElementById('eventsContainer');
        container.innerHTML = '<div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">ইভেন্ট লোড হচ্ছে...</div>';
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/events`, { headers: getAuthHeaders() });
            const result = await res.json();
            const events = result.data || [];

            if (events.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 30px; color: var(--fb-text-secondary);">কোনো ইভেন্ট নেই।</div>';
                return;
            }

            let html = '<table class="studio-table"><thead><tr><th>শিরোনাম</th><th>স্থান</th><th>শুরুর সময়</th><th>RSVP সংখ্যা</th></tr></thead><tbody>';
            events.forEach(ev => {
                html += `<tr>
                    <td><strong>${ev.title}</strong></td>
                    <td>${ev.location || (ev.is_online ? 'অনলাইন' : 'স্থান নির্ধারিত হয়নি')}</td>
                    <td>${new Date(ev.start_time).toLocaleString()}</td>
                    <td>${ev.going_count || 0} জন নিশ্চিত</td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div style="color: #dc2626; padding: 20px;">ইভেন্ট লোড করা যায়নি।</div>';
        }
    }

    async function submitCreateEvent(e) {
        e.preventDefault();
        const title = document.getElementById('eventTitle').value.trim();
        const location = document.getElementById('eventLocation').value.trim();
        const start_time = document.getElementById('eventStartTime').value;
        const description = document.getElementById('eventDescription').value.trim();

        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/events`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({ title, location, start_time, description })
            });
            const result = await res.json();
            if (result.success) {
                alert(result.message);
                closeModal('modalCreateEvent');
                loadEvents();
            } else {
                alert(result.message || 'ব্যর্থ');
            }
        } catch (e) {
            alert('ত্রুটি');
        }
    }

    // 7. COMMERCE & PRODUCTS
    async function loadProducts() {
        const container = document.getElementById('productsContainer');
        container.innerHTML = '<div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">প্রোডাক্ট লোড হচ্ছে...</div>';
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/products`, { headers: getAuthHeaders() });
            const result = await res.json();
            const prods = result.data || [];

            if (prods.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 30px; color: var(--fb-text-secondary);">কোনো প্রোডাক্ট নেই।</div>';
                return;
            }

            let html = '<table class="studio-table"><thead><tr><th>প্রোডাক্ট</th><th>মূল্য</th><th>স্টক</th><th>স্ট্যাটাস</th></tr></thead><tbody>';
            prods.forEach(pr => {
                html += `<tr>
                    <td><strong>${pr.title}</strong></td>
                    <td>৳ ${pr.price}</td>
                    <td>${pr.stock_quantity} টি</td>
                    <td><span class="badge-role">${pr.status}</span></td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div style="color: #dc2626; padding: 20px;">প্রোডাক্ট লোড করা যায়নি।</div>';
        }
    }

    async function submitCreateProduct(e) {
        e.preventDefault();
        const title = document.getElementById('productTitle').value.trim();
        const price = document.getElementById('productPrice').value;
        const stock_quantity = document.getElementById('productStock').value;
        const description = document.getElementById('productDescription').value.trim();

        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/products`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify({ title, price, stock_quantity, description })
            });
            const result = await res.json();
            if (result.success) {
                alert(result.message);
                closeModal('modalCreateProduct');
                loadProducts();
            } else {
                alert(result.message || 'ব্যর্থ');
            }
        } catch (e) {
            alert('ত্রুটি');
        }
    }

    // 8. AUDIT LOGS
    async function loadAuditLogs() {
        const container = document.getElementById('auditLogsContainer');
        container.innerHTML = '<div style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">অডিট লগ লোড হচ্ছে...</div>';
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/audit-logs`, { headers: getAuthHeaders() });
            const result = await res.json();
            const logs = result.data || [];

            if (logs.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 30px; color: var(--fb-text-secondary);">কোনো অডিট লগ রেকর্ড নেই।</div>';
                return;
            }

            let html = '<table class="studio-table"><thead><tr><th>অ্যাকশন</th><th>কর্তা (Actor)</th><th>টার্গেট</th><th>আইপি</th><th>তারিখ</th></tr></thead><tbody>';
            logs.forEach(l => {
                html += `<tr>
                    <td><code>${l.action}</code></td>
                    <td>${l.actor ? l.actor.name : 'সিস্টেম'}</td>
                    <td>${l.target_type || ''} #${l.target_id || ''}</td>
                    <td><small>${l.ip_address || '127.0.0.1'}</small></td>
                    <td>${new Date(l.created_at).toLocaleString()}</td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div style="color: #dc2626; padding: 20px;">অডিট লগ লোড করা যায়নি।</div>';
        }
    }

    // 9. SETTINGS
    async function savePageSettings(e) {
        e.preventDefault();
        const autoReply = document.getElementById('settingAutoReplyEnabled').checked;
        const autoReplyMsg = document.getElementById('settingAutoReplyMessage').value;
        const keywords = document.getElementById('settingBlockedKeywords').value.split(',').map(s => s.trim()).filter(Boolean);

        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/settings`, {
                method: 'PUT',
                headers: getAuthHeaders(),
                body: JSON.stringify({
                    messaging: {
                        auto_reply_enabled: autoReply,
                        auto_reply_message: autoReplyMsg
                    },
                    moderation: {
                        blocked_keywords: keywords
                    }
                })
            });
            const result = await res.json();
            if (result.success) {
                alert('সেটিংস সফলভাবে সংরক্ষিত হয়েছে।');
            } else {
                alert(result.message || 'ব্যর্থ');
            }
        } catch (e) {
            alert('ত্রুটি');
        }
    }

    async function deletePageToTrash() {
        if (!confirm('আপনি কি নিশ্চিত যে পেইজটি ট্র্যাশে স্থানান্তর করতে চান?')) return;
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}`, {
                method: 'DELETE',
                headers: getAuthHeaders()
            });
            const result = await res.json();
            if (result.success) {
                alert('পেইজটি ট্র্যাশে স্থানান্তর করা হয়েছে।');
                window.location.href = '/pages';
            } else {
                alert(result.message || 'ব্যর্থ');
            }
        } catch (e) {
            alert('ত্রুটি');
        }
    }

    // Auto initialize on load
    document.addEventListener('DOMContentLoaded', () => {
        loadOverviewAnalytics();
    });
</script>
@endsection
