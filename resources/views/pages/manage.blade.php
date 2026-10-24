@extends('layouts.app')

@section('title', 'Manage Page — ' . $page->name . ' — Jugajug Enterprise Management')

@section('styles')
<link rel="stylesheet" href="/css/enterprise-pages-redesign.css?v={{ time() }}">
@endsection

@section('content')
@php
    $formatMetric = function($num) {
        $num = (float) ($num ?? 0);
        if ($num >= 1000000) {
            return round($num / 1000000, 1) . 'M';
        }
        if ($num >= 1000) {
            return round($num / 1000, 1) . 'K';
        }
        return (string) (int) $num;
    };

    $activeAnalytics = $analytics30d ?? $analytics7d;
    $summary = $activeAnalytics['summary'] ?? [];
    $totalFollowers = $page->followers_count ?? ($summary['total_followers'] ?? 0);
    $newFollowers = $summary['new_followers'] ?? 0;
    $totalPosts = $page->posts_count ?? ($summary['total_posts'] ?? 0);
    $totalEngagements = $summary['total_engagements'] ?? 0;
    $engagementRate = $summary['engagement_rate'] ?? '0%';
    $calcReach = (int) ($summary['reach'] ?? 0);
    $calcImpressions = (int) ($summary['impressions'] ?? 0);
    $calcClicks = (int) ($summary['link_clicks'] ?? ($summary['clicks'] ?? 0));
    $calcVideoViews = (int) ($summary['video_views'] ?? ($summary['reel_views'] ?? 0));
    $calcStoryViews = (int) ($summary['story_views'] ?? 0);
@endphp

<div class="ep-dashboard-root">
    <div class="ep-dashboard-wrapper">
        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- 1. LEFT SIDEBAR                                           -->
        <!-- ═════════════════════════════════════════════════════════ -->
        <aside class="ep-sidebar-left" aria-label="Main Navigation">
            <div class="ep-sidebar-brand" style="display: flex; align-items: center; gap: 10px; padding: 6px 10px 14px; border-bottom: 1px solid var(--ep-border); margin-bottom: 6px;">
                <a href="{{ route('feed') }}" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                    <img src="/images/jugajug-logo.svg" alt="Jugajug" style="width: 32px; height: 32px; flex-shrink: 0;">
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 18px; font-weight: 800; color: var(--ep-brand-blue); letter-spacing: -0.4px; line-height: 1.1;">Jugajug</span>
                        <span style="font-size: 11px; font-weight: 600; color: var(--ep-text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Pages Hub</span>
                    </div>
                </a>
            </div>

            <nav class="ep-sidebar-nav">
                <a href="{{ route('feed') }}" class="ep-sidebar-link">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'home', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Home</span>
                </a>
                <a href="{{ $currentUser ? getUserProfileUrl($currentUser) : route('login') }}" class="ep-sidebar-link">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'user', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Profile</span>
                </a>
                <a href="{{ route('messages.index') }}" class="ep-sidebar-link">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'message-square', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Messages</span>
                    @if(!empty($unreadMessagesCount) && $unreadMessagesCount > 0)
                        <span class="ep-sidebar-badge">{{ $unreadMessagesCount }}</span>
                    @endif
                </a>
                <a href="{{ route('notifications.index') }}" class="ep-sidebar-link">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'bell', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Notifications</span>
                    @if(!empty($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                        <span class="ep-sidebar-badge">{{ $unreadNotificationsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('friends.index') }}" class="ep-sidebar-link">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'users', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Friends</span>
                </a>
                <a href="{{ route('groups.index') }}" class="ep-sidebar-link">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'users', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Groups</span>
                </a>
                <a href="{{ route('pages.index') }}" class="ep-sidebar-link active">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'flag', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Pages</span>
                </a>
                <a href="javascript:void(0)" onclick="epSwitchStudioTab('events')" class="ep-sidebar-link">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'calendar', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Events</span>
                </a>
                <a href="{{ route('marketplace.index') }}" class="ep-sidebar-link">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'shopping-bag', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Marketplace</span>
                </a>
                <a href="{{ route('watch.index') }}" class="ep-sidebar-link">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'video', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Watch</span>
                </a>
                <a href="{{ route('feed') }}" class="ep-sidebar-link" title="Feed & Saved">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'bookmark', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Saved</span>
                </a>
                <a href="javascript:void(0)" class="ep-sidebar-link" onclick="epToggleSidebarMore(event)">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'more-horizontal', 'size' => 20])</span>
                    <span class="ep-sidebar-label">More</span>
                </a>
            </nav>

            <div class="ep-sidebar-divider"></div>

            <!-- "Your Pages" Section -->
            <div class="ep-sidebar-section">
                <div class="ep-sidebar-section-header">
                    <span class="ep-sidebar-section-title">Your Pages</span>
                    <a href="{{ route('pages.index') }}" class="ep-sidebar-see-all">See All</a>
                </div>

                <div class="ep-sidebar-pages-list">
                    @forelse($managedPages->take(5) as $idx => $mPage)
                        <div class="ep-sidebar-page-item {{ $mPage->id === $page->id ? 'active' : '' }}" style="{{ $mPage->id === $page->id ? 'background: var(--ep-brand-blue-light);' : '' }}">
                            <a href="{{ route('pages.manage', $mPage->slug) }}" class="ep-sidebar-page-link" title="{{ $mPage->name }}">
                                <div class="ep-sidebar-page-avatar">
                                    @if($mPage->avatar_url)
                                        <img src="{{ $mPage->avatar_url }}" alt="{{ $mPage->name }}">
                                    @else
                                        <div class="ep-avatar-fallback accent-{{ $idx % 4 }}">
                                            {{ mb_strtoupper(mb_substr($mPage->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <span class="ep-sidebar-page-name" style="{{ $mPage->id === $page->id ? 'color: var(--ep-brand-blue); font-weight: 700;' : '' }}">{{ $mPage->name }}</span>
                            </a>
                            <div class="ep-dropdown-wrap">
                                <button type="button" class="ep-btn-more-dots" onclick="epToggleMenu(event, 'side-m-{{ $mPage->id }}')" aria-label="More options">
                                    @include('pages.partials.icon', ['name' => 'more-horizontal', 'size' => 16])
                                </button>
                                <div id="side-m-{{ $mPage->id }}" class="ep-popover-menu">
                                    <a href="{{ route('pages.show', $mPage->slug) }}">
                                        @include('pages.partials.icon', ['name' => 'eye', 'size' => 14])
                                        <span>View Page</span>
                                    </a>
                                    <a href="{{ route('pages.manage', $mPage->slug) }}">
                                        @include('pages.partials.icon', ['name' => 'settings', 'size' => 14])
                                        <span>Manage</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="font-size: 13px; color: var(--ep-text-muted); padding: 6px 8px;">
                            No managed pages yet
                        </div>
                    @endforelse
                </div>

                <div class="ep-sidebar-create-wrap">
                    <a href="{{ route('pages.create') }}" class="ep-btn-sidebar-create">
                        @include('pages.partials.icon', ['name' => 'plus', 'size' => 16, 'strokeWidth' => 2.5])
                        <span>+ Create Page</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- 2. CENTER CONTENT                                         -->
        <!-- ═════════════════════════════════════════════════════════ -->
        <main class="ep-main-content">
            <!-- 2. PAGE PROFILE HEADER CARD -->
            <div class="ep-page-hero-card">
                <!-- Cover Image Area -->
                <div class="ep-page-hero-cover" id="epManageCoverBox">
                    @if($page->cover_image_url)
                        <img src="{{ $page->cover_image_url }}" alt="{{ $page->name }} Cover" id="epManageCoverImg">
                    @endif
                    @if($canManageRoles)
                        <div class="ep-dropdown-wrap ep-cover-upload-wrap">
                            <button type="button" class="ep-cover-upload-btn" onclick="epToggleMenu(event, 'cover-branding-menu')" title="Edit Cover Photo">
                                @include('pages.partials.icon', ['name' => 'camera', 'size' => 16])
                                <span>Edit Cover</span>
                            </button>
                            <div id="cover-branding-menu" class="ep-popover-menu" style="right: 0; left: auto; top: calc(100% + 6px);">
                                <label style="display: flex; align-items: center; gap: 8px; padding: 9px 14px; cursor: pointer; font-size: 13px; color: var(--ep-text-title); width: 100%; box-sizing: border-box;">
                                    @include('pages.partials.icon', ['name' => 'image', 'size' => 14])
                                    <span>Upload New Cover</span>
                                    <input type="file" id="coverFileInput" accept="image/*" style="display: none;" onchange="epUploadBranding('cover', event)">
                                </label>
                                <button type="button" class="ep-popover-danger" onclick="epRemoveBranding('cover')">
                                    @include('pages.partials.icon', ['name' => 'trash-2', 'size' => 14])
                                    <span>Remove Cover</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Page Info & Profile Row -->
                <div class="ep-page-hero-body">
                    <div style="display: flex; align-items: flex-end; gap: 18px; flex-wrap: wrap;">
                        <!-- Overlapping Circular Avatar -->
                        <div class="ep-hero-avatar-wrap" id="epManageAvatarBox">
                            @if($page->avatar_url)
                                <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}" id="epManageAvatarImg">
                            @else
                                <div class="ep-avatar-fallback accent-1" style="width: 100%; height: 100%; font-size: 36px;">
                                    {{ mb_strtoupper(mb_substr($page->name, 0, 1)) }}
                                </div>
                            @endif
                            @if($canManageRoles)
                                <div class="ep-dropdown-wrap" style="position: absolute; bottom: 4px; right: 4px; z-index: 10;">
                                    <button type="button" class="ep-avatar-upload-btn" onclick="epToggleMenu(event, 'avatar-branding-menu')" title="Edit Profile Picture">
                                        @include('pages.partials.icon', ['name' => 'camera', 'size' => 16])
                                    </button>
                                    <div id="avatar-branding-menu" class="ep-popover-menu" style="left: 0; top: calc(100% + 6px);">
                                        <label style="display: flex; align-items: center; gap: 8px; padding: 9px 14px; cursor: pointer; font-size: 13px; color: var(--ep-text-title); width: 100%; box-sizing: border-box;">
                                            @include('pages.partials.icon', ['name' => 'image', 'size' => 14])
                                            <span>Upload New Avatar</span>
                                            <input type="file" id="avatarFileInput" accept="image/*" style="display: none;" onchange="epUploadBranding('avatar', event)">
                                        </label>
                                        <button type="button" class="ep-popover-danger" onclick="epRemoveBranding('avatar')">
                                            @include('pages.partials.icon', ['name' => 'trash-2', 'size' => 14])
                                            <span>Remove Avatar</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Info Column -->
                        <div class="ep-hero-info-col">
                            <div class="ep-hero-title-row">
                                <h1 class="ep-hero-title">{{ $page->name }}</h1>
                                @if($page->is_verified)
                                    <span class="ep-verified-badge" title="Verified Page">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                    </span>
                                @endif
                                <span class="ep-role-badge {{ strtolower($userRole) }}" style="margin-left: 6px;">{{ strtoupper($userRole) }}</span>
                            </div>

                            <div class="ep-hero-meta-row">
                                @if($page->username)
                                    <span style="font-weight: 700; color: var(--ep-text-title);">{{ '@' . $page->username }}</span>
                                    <span>•</span>
                                @endif
                                <span class="ep-hero-meta-item">
                                    @include('pages.partials.icon', ['name' => 'tag', 'size' => 14])
                                    <span>{{ $page->category ?: 'General Business' }}</span>
                                    @if($page->sub_category)
                                        <span>/ {{ $page->sub_category }}</span>
                                    @endif
                                </span>
                                <span>•</span>
                                <span class="ep-hero-meta-item">
                                    @include('pages.partials.icon', ['name' => 'users', 'size' => 14])
                                    <strong id="epHeaderFollowersCount">{{ number_format($totalFollowers) }}</strong> followers
                                </span>
                                <span>•</span>
                                <span class="ep-hero-meta-item">
                                    @include('pages.partials.icon', ['name' => 'file-text', 'size' => 14])
                                    <strong id="epHeaderPostsCount">{{ number_format($totalPosts) }}</strong> posts
                                </span>
                                @if($page->created_at)
                                    <span>•</span>
                                    <span class="ep-hero-meta-item">Active since {{ $page->created_at->format('M Y') }}</span>
                                @endif
                            </div>

                            @if($page->bio)
                                <p style="font-size: 13.5px; color: var(--ep-text-secondary); margin: 6px 0 0; line-height: 1.45;">
                                    {{ $page->bio }}
                                </p>
                            @endif

                            @if($page->website || $page->email || $page->phone || $page->city)
                                <div style="display: flex; gap: 14px; flex-wrap: wrap; margin-top: 6px; font-size: 12.5px; color: var(--ep-text-secondary);">
                                    @if($page->website)
                                        <a href="{{ $page->website }}" target="_blank" rel="noopener" style="color: var(--ep-brand-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            @include('pages.partials.icon', ['name' => 'globe', 'size' => 13])
                                            <span>{{ parse_url($page->website, PHP_URL_HOST) ?: $page->website }}</span>
                                        </a>
                                    @endif
                                    @if($page->email)
                                        <span style="display: inline-flex; align-items: center; gap: 4px;">
                                            @include('pages.partials.icon', ['name' => 'mail', 'size' => 13])
                                            <span>{{ $page->email }}</span>
                                        </span>
                                    @endif
                                    @if($page->phone)
                                        <span style="display: inline-flex; align-items: center; gap: 4px;">
                                            @include('pages.partials.icon', ['name' => 'phone', 'size' => 13])
                                            <span>{{ $page->phone }}</span>
                                        </span>
                                    @endif
                                    @if($page->city || $page->country)
                                        <span style="display: inline-flex; align-items: center; gap: 4px;">
                                            @include('pages.partials.icon', ['name' => 'map-pin', 'size' => 13])
                                            <span>{{ implode(', ', array_filter([$page->city, $page->country])) }}</span>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="ep-hero-actions-col">
                        <button type="button" class="ep-btn-manage" onclick="epOpenModal('epModalEditPage')">
                            @include('pages.partials.icon', ['name' => 'edit', 'size' => 14])
                            <span>Edit Page</span>
                        </button>

                        <a href="{{ route('pages.show', $page->slug) }}" class="ep-btn-view" target="_blank">
                            @include('pages.partials.icon', ['name' => 'eye', 'size' => 14])
                            <span>View Public Page</span>
                        </a>

                        <div class="ep-dropdown-wrap">
                            <button type="button" class="ep-btn-more-actions" onclick="epToggleMenu(event, 'page-hero-more-menu')" aria-label="More Actions">
                                @include('pages.partials.icon', ['name' => 'more-horizontal', 'size' => 18])
                            </button>
                            <div id="page-hero-more-menu" class="ep-popover-menu">
                                <button type="button" onclick="epCopyLink('{{ route('pages.show', $page->slug) }}')">
                                    @include('pages.partials.icon', ['name' => 'copy', 'size' => 14])
                                    <span>Copy Page Link</span>
                                </button>
                                <button type="button" onclick="epSharePage('{{ $page->name }}', '{{ route('pages.show', $page->slug) }}')">
                                    @include('pages.partials.icon', ['name' => 'share-2', 'size' => 14])
                                    <span>Share Page</span>
                                </button>
                                @if($canManageRoles)
                                    <button type="button" onclick="epSwitchStudioTab('settings')">
                                        @include('pages.partials.icon', ['name' => 'settings', 'size' => 14])
                                        <span>Page Settings</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. PAGE NAVIGATION TABS -->
                <div class="ep-page-nav-bar" role="tablist">
                    <button type="button" class="ep-page-nav-item {{ ($activeTab ?? 'posts') === 'overview' ? 'active' : '' }}" onclick="epSwitchStudioTab('overview')" data-tab="overview">
                        @include('pages.partials.icon', ['name' => 'compass', 'size' => 15])
                        <span>Overview</span>
                        <span class="ep-tab-indicator"></span>
                    </button>
                    <button type="button" class="ep-page-nav-item {{ ($activeTab ?? 'posts') === 'posts' ? 'active' : '' }}" onclick="epSwitchStudioTab('posts')" data-tab="posts">
                        @include('pages.partials.icon', ['name' => 'file-text', 'size' => 15])
                        <span>Posts</span>
                        <span class="ep-tab-indicator"></span>
                    </button>
                    <button type="button" class="ep-page-nav-item" onclick="epSwitchStudioTab('about')" data-tab="about">
                        @include('pages.partials.icon', ['name' => 'info', 'size' => 15])
                        <span>About</span>
                        <span class="ep-tab-indicator"></span>
                    </button>
                    <button type="button" class="ep-page-nav-item" onclick="epSwitchStudioTab('photos')" data-tab="photos">
                        @include('pages.partials.icon', ['name' => 'image', 'size' => 15])
                        <span>Photos</span>
                        <span class="ep-tab-indicator"></span>
                    </button>
                    <button type="button" class="ep-page-nav-item" onclick="epSwitchStudioTab('videos')" data-tab="videos">
                        @include('pages.partials.icon', ['name' => 'video', 'size' => 15])
                        <span>Videos</span>
                        <span class="ep-tab-indicator"></span>
                    </button>
                    <button type="button" class="ep-page-nav-item" onclick="epSwitchStudioTab('events')" data-tab="events">
                        @include('pages.partials.icon', ['name' => 'calendar', 'size' => 15])
                        <span>Events</span>
                        <span class="ep-tab-indicator"></span>
                    </button>
                    <button type="button" class="ep-page-nav-item" onclick="epSwitchStudioTab('followers')" data-tab="followers">
                        @include('pages.partials.icon', ['name' => 'users', 'size' => 15])
                        <span>Followers ({{ number_format($totalFollowers) }})</span>
                        <span class="ep-tab-indicator"></span>
                    </button>
                    @if($canManageRoles)
                        <button type="button" class="ep-page-nav-item" onclick="epSwitchStudioTab('settings')" data-tab="settings">
                            @include('pages.partials.icon', ['name' => 'settings', 'size' => 15])
                            <span>Settings</span>
                            <span class="ep-tab-indicator"></span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════ -->
            <!-- TAB PANES                                             -->
            <!-- ═════════════════════════════════════════════════════ -->

            <!-- TAB 1: OVERVIEW PANE -->
            <div id="studio-pane-overview" class="ep-studio-pane {{ ($activeTab ?? 'posts') === 'overview' ? 'active' : '' }}" style="display: {{ ($activeTab ?? 'posts') === 'overview' ? 'block' : 'none' }};">
                <!-- 4. OVERVIEW STATISTICS CARDS -->
                <div class="ep-stats-grid" style="margin-bottom: 20px;">
                    <!-- Total Followers -->
                    <div class="ep-stat-card">
                        <div class="ep-stat-icon-circle purple">
                            @include('pages.partials.icon', ['name' => 'users', 'size' => 22])
                        </div>
                        <div class="ep-stat-content">
                            <span class="ep-stat-label">Total Followers</span>
                            <div class="ep-stat-value-row">
                                <span class="ep-stat-value" id="epOverviewTotalFollowersValue">{{ $formatMetric($totalFollowers) }}</span>
                                <span class="ep-stat-trend green" id="epOverviewNewFollowersSub">↑ {{ $newFollowers }} new</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Posts -->
                    <div class="ep-stat-card">
                        <div class="ep-stat-icon-circle green">
                            @include('pages.partials.icon', ['name' => 'file-text', 'size' => 22])
                        </div>
                        <div class="ep-stat-content">
                            <span class="ep-stat-label">Total Posts</span>
                            <div class="ep-stat-value-row">
                                <span class="ep-stat-value" id="epOverviewTotalPostsValue">{{ $formatMetric($totalPosts) }}</span>
                                <span class="ep-stat-trend green" id="epOverviewPostsSub">{{ $summary['posts_in_period'] ?? $totalPosts }} in period</span>
                            </div>
                        </div>
                    </div>

                    <!-- Post Reach -->
                    <div class="ep-stat-card">
                        <div class="ep-stat-icon-circle blue">
                            @include('pages.partials.icon', ['name' => 'eye', 'size' => 22])
                        </div>
                        <div class="ep-stat-content">
                            <span class="ep-stat-label" title="Unique user accounts that viewed page content">Post Reach</span>
                            <div class="ep-stat-value-row">
                                <span class="ep-stat-value" id="epOverviewPostReachValue">{{ $formatMetric($calcReach) }}</span>
                                <span class="ep-stat-trend green" id="epOverviewPostReachSub" title="Total Impressions: {{ number_format($calcImpressions) }}">
                                    {{ $calcImpressions > 0 ? $formatMetric($calcImpressions) . ' imp' : 'Unique reach' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Engagement -->
                    <div class="ep-stat-card">
                        <div class="ep-stat-icon-circle orange">
                            @include('pages.partials.icon', ['name' => 'bullhorn', 'size' => 22])
                        </div>
                        <div class="ep-stat-content">
                            <span class="ep-stat-label">Engagement</span>
                            <div class="ep-stat-value-row">
                                <span class="ep-stat-value" id="epOverviewTotalEngagementsValue">{{ $formatMetric($totalEngagements) }}</span>
                                <span class="ep-stat-trend green" id="epOverviewEngagementRateSub">{{ $engagementRate }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4b. SECONDARY VERIFIABLE METRICS STRIP -->
                <div class="ep-stats-grid" style="margin-bottom: 20px; grid-template-columns: repeat(3, 1fr);">
                    <!-- Video & Reel Views -->
                    <div class="ep-stat-card">
                        <div class="ep-stat-icon-circle blue" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                            @include('pages.partials.icon', ['name' => 'video', 'size' => 20])
                        </div>
                        <div class="ep-stat-content">
                            <span class="ep-stat-label">Video & Reel Views</span>
                            <div class="ep-stat-value-row">
                                <span class="ep-stat-value" id="epOverviewVideoViewsValue">{{ $formatMetric($calcVideoViews) }}</span>
                                <span class="ep-stat-trend green">Verifiable views</span>
                            </div>
                        </div>
                    </div>

                    <!-- Story Views -->
                    <div class="ep-stat-card">
                        <div class="ep-stat-icon-circle purple" style="background: rgba(147, 51, 234, 0.1); color: #9333ea;">
                            @include('pages.partials.icon', ['name' => 'circle', 'size' => 20])
                        </div>
                        <div class="ep-stat-content">
                            <span class="ep-stat-label">Story Views</span>
                            <div class="ep-stat-value-row">
                                <span class="ep-stat-value" id="epOverviewStoryViewsValue">{{ $formatMetric($calcStoryViews) }}</span>
                                <span class="ep-stat-trend green">24h viewers</span>
                            </div>
                        </div>
                    </div>

                    <!-- Link Clicks -->
                    <div class="ep-stat-card">
                        <div class="ep-stat-icon-circle green" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            @include('pages.partials.icon', ['name' => 'mouse-pointer', 'size' => 20])
                        </div>
                        <div class="ep-stat-content">
                            <span class="ep-stat-label">Link Clicks</span>
                            <div class="ep-stat-value-row">
                                <span class="ep-stat-value" id="epOverviewClicksValue">{{ $formatMetric($calcClicks) }}</span>
                                <span class="ep-stat-trend green">Tracked clicks</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. PAGE ADMINISTRATORS AND ROLE MANAGEMENT CARD -->
                <div class="ep-card" style="margin-bottom: 20px;">
                    <div class="ep-card-header">
                        <div>
                            <h2 class="ep-card-title">Page Administrators & Team</h2>
                            <p class="ep-card-subtitle">Manage authorized administrative roles and access levels</p>
                        </div>
                        @if($canManageRoles)
                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="ep-btn-manage" onclick="epOpenModal('epModalRoleMatrix')">
                                    <span>Permissions</span>
                                </button>
                                <button type="button" class="ep-btn-view" onclick="epOpenModal('epModalInviteAdmin')">
                                    @include('pages.partials.icon', ['name' => 'user-plus', 'size' => 14])
                                    <span>Add Administrator</span>
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="ep-team-list" id="epTeamListContainer">
                        <!-- Owner Row -->
                        <div class="ep-team-item">
                            <div class="ep-team-user-info">
                                <div class="ep-team-avatar">
                                    @if($page->owner && $page->owner->profile_photo_url)
                                        <img src="{{ $page->owner->profile_photo_url }}" alt="{{ $page->owner->name }}">
                                    @else
                                        <div class="ep-avatar-fallback accent-0" style="width: 100%; height: 100%;">
                                            {{ mb_strtoupper(mb_substr($page->owner->name ?? 'O', 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="ep-team-name-col">
                                    <span class="ep-team-name">{{ $page->owner->name ?? 'Page Owner' }}</span>
                                    <span class="ep-team-desc">Full ownership, role delegation, content publishing, and security controls</span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="ep-role-badge owner">Owner</span>
                            </div>
                        </div>

                        <!-- Active Members -->
                        @foreach($teamMembers as $member)
                            <div class="ep-team-item" id="team-member-row-{{ $member->id }}">
                                <div class="ep-team-user-info">
                                    <div class="ep-team-avatar">
                                        @if($member->user && $member->user->profile_photo_url)
                                            <img src="{{ $member->user->profile_photo_url }}" alt="{{ $member->user->name }}">
                                        @else
                                            <div class="ep-avatar-fallback accent-{{ $loop->iteration % 4 }}" style="width: 100%; height: 100%;">
                                                {{ mb_strtoupper(mb_substr($member->user->name ?? 'U', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ep-team-name-col">
                                        <span class="ep-team-name">{{ $member->user->name ?? 'Team Member' }}</span>
                                        <span class="ep-team-desc">
                                            @if($member->role === 'admin')
                                                Full administrative authority, content management, and member role assignment
                                            @elseif($member->role === 'editor')
                                                Publish, edit, and manage page content, stories, and media
                                            @elseif($member->role === 'moderator')
                                                Review comments, moderate community responses, and handle interactions
                                            @elseif($member->role === 'analyst')
                                                View audience insights, performance metrics, and growth reports
                                            @else
                                                Standard team member permissions
                                            @endif
                                            @if($member->status === 'invited')
                                                <strong style="color: #F59E0B;">(Pending Invitation)</strong>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="ep-role-badge {{ strtolower($member->role) }}">{{ strtoupper($member->role) }}</span>
                                    @if($canManageRoles)
                                        <div class="ep-dropdown-wrap">
                                            <button type="button" class="ep-btn-more-dots" onclick="epToggleMenu(event, 'member-action-{{ $member->id }}')" aria-label="Member Actions">
                                                @include('pages.partials.icon', ['name' => 'more-horizontal', 'size' => 16])
                                            </button>
                                            <div id="member-action-{{ $member->id }}" class="ep-popover-menu">
                                                <button type="button" onclick="epOpenChangeRoleModal({{ $member->id }}, '{{ $member->role }}', '{{ addslashes($member->user->name ?? '') }}')">
                                                    @include('pages.partials.icon', ['name' => 'edit', 'size' => 14])
                                                    <span>Change Role</span>
                                                </button>
                                                @if($member->status === 'invited')
                                                    <button type="button" class="ep-popover-danger" onclick="epConfirmRevokeInvite({{ $member->id }}, '{{ addslashes($member->user->name ?? '') }}')">
                                                        @include('pages.partials.icon', ['name' => 'x-circle', 'size' => 14])
                                                        <span>Revoke Invitation</span>
                                                    </button>
                                                @endif
                                                <button type="button" class="ep-popover-danger" onclick="epConfirmRemoveMember({{ $member->id }}, '{{ addslashes($member->user->name ?? '') }}')">
                                                    @include('pages.partials.icon', ['name' => 'trash-2', 'size' => 14])
                                                    <span>Remove Member</span>
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- 6. PAGE PERFORMANCE AND ANALYTICS CARD -->
                <div class="ep-card" style="margin-bottom: 20px;" id="epAnalyticsMasterCard">
                    <div class="ep-card-header" style="flex-wrap: wrap; gap: 12px;">
                        <div>
                            <h2 class="ep-card-title">Page Performance & Growth Analytics</h2>
                            <p class="ep-card-subtitle">Verifiable metrics calculated from actual persisted events, reactions, and post analytics</p>
                        </div>
                        <div class="ep-card-controls" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <select id="epStudioTimeRangeSelect" class="ep-time-range-select" onchange="epChangeStudioAnalyticsRange(this.value)">
                                <option value="7d">Last 7 days</option>
                                <option value="30d" selected>Last 30 days</option>
                                <option value="90d">Last 90 days</option>
                                <option value="custom">Custom Date Range...</option>
                            </select>
                            <div id="epCustomDateRangeWrap" style="display: none; align-items: center; gap: 6px;">
                                <input type="date" id="epAnalyticsStartDate" class="ep-input" style="padding: 4px 8px; font-size: 12px; height: 32px;" max="{{ date('Y-m-d') }}">
                                <span style="font-size: 12px; color: var(--ep-text-muted);">to</span>
                                <input type="date" id="epAnalyticsEndDate" class="ep-input" style="padding: 4px 8px; font-size: 12px; height: 32px;" max="{{ date('Y-m-d') }}">
                                <button type="button" class="ep-btn-manage" style="padding: 4px 10px; font-size: 12px; height: 32px;" onclick="epApplyCustomAnalyticsRange()">Apply</button>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div id="epAnalyticsLoadingIndicator" style="display: none; padding: 36px 20px; text-align: center; color: var(--ep-brand-blue);">
                        <div class="ep-spinner" style="width: 28px; height: 28px; border: 3px solid rgba(24, 119, 242, 0.2); border-top-color: #1877F2; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 12px;"></div>
                        <span style="font-size: 13.5px; font-weight: 600;">Calculating verifiable metrics from event records...</span>
                    </div>

                    <!-- Error State -->
                    <div id="epAnalyticsErrorState" style="display: none; padding: 20px; margin: 16px 20px; background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 8px; text-align: center;">
                        <p style="color: #991B1B; font-weight: 600; font-size: 13.5px; margin: 0 0 10px;" id="epAnalyticsErrorMessage">Could not retrieve analytics. Please try again.</p>
                        <button type="button" class="ep-btn-manage" style="margin: 0 auto;" onclick="epRetryAnalyticsLoad()">
                            @include('pages.partials.icon', ['name' => 'rotate-cw', 'size' => 13])
                            <span>Retry</span>
                        </button>
                    </div>

                    <!-- Empty Data State -->
                    <div id="epAnalyticsEmptyState" style="display: none; padding: 40px 20px; text-align: center; color: var(--ep-text-secondary);">
                        <div style="font-size: 32px; margin-bottom: 8px;">📊</div>
                        <h3 style="font-size: 15px; font-weight: 700; color: var(--ep-text-title); margin: 0 0 6px;">No Event Records Found</h3>
                        <p style="font-size: 13px; margin: 0 auto; max-width: 480px; line-height: 1.45;">No verifiable page views, reactions, or interactions occurred during this period. Once users view posts or follow your page, real-time analytics will populate here.</p>
                    </div>

                    <!-- Analytics Sub-tabs -->
                    <div id="epAnalyticsTabsBar" style="padding: 14px 20px 0; border-bottom: 1px solid var(--ep-border); display: flex; gap: 16px;">
                        <button type="button" class="ep-tab-item active" id="btnAnalyticTabOverview" onclick="epSwitchAnalyticSubTab('overview')">
                            <span>Overview Trend</span>
                        </button>
                        <button type="button" class="ep-tab-item" id="btnAnalyticTabAudience" onclick="epSwitchAnalyticSubTab('audience')">
                            <span>Audience Geography</span>
                        </button>
                        <button type="button" class="ep-tab-item" id="btnAnalyticTabContent" onclick="epSwitchAnalyticSubTab('content')">
                            <span>Top Content</span>
                        </button>
                    </div>

                    <!-- Sub-pane 1: Overview Chart & Verifiable Breakdown -->
                    <div id="epAnalyticSubOverview" class="ep-analytics-chart-box">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <span style="font-size: 13px; font-weight: 600; color: var(--ep-text-secondary);">Follower Growth Over Time</span>
                                <div style="font-size: 20px; font-weight: 800; color: var(--ep-text-title);" id="epChartSummaryText">
                                    +{{ $newFollowers }} new followers in this period
                                </div>
                            </div>
                            <span style="font-size: 12px; color: var(--ep-text-muted);" id="epChartDateBadge">Daily aggregation</span>
                        </div>

                        <!-- SVG Line Chart -->
                        <div style="width: 100%; height: 180px; position: relative;" id="epChartSvgWrapper">
                            <svg viewBox="0 0 700 180" class="ep-chart-svg" id="epSvgChart">
                                <defs>
                                    <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#1877F2" stop-opacity="0.4"/>
                                        <stop offset="100%" stop-color="#1877F2" stop-opacity="0.0"/>
                                    </linearGradient>
                                </defs>
                                <g class="ep-chart-grid">
                                    <line x1="0" y1="30" x2="700" y2="30"/>
                                    <line x1="0" y1="75" x2="700" y2="75"/>
                                    <line x1="0" y1="120" x2="700" y2="120"/>
                                    <line x1="0" y1="165" x2="700" y2="165"/>
                                </g>
                                <path id="epChartAreaPath" class="ep-chart-area" d="M 0,165 L 140,130 L 280,110 L 420,80 L 560,50 L 700,40 L 700,165 Z"/>
                                <path id="epChartLinePath" class="ep-chart-line" d="M 0,165 L 140,130 L 280,110 L 420,80 L 560,50 L 700,40"/>
                                <g id="epChartDotsGroup">
                                    <circle class="ep-chart-dot" cx="0" cy="165"/>
                                    <circle class="ep-chart-dot" cx="140" cy="130"/>
                                    <circle class="ep-chart-dot" cx="280" cy="110"/>
                                    <circle class="ep-chart-dot" cx="420" cy="80"/>
                                    <circle class="ep-chart-dot" cx="560" cy="50"/>
                                    <circle class="ep-chart-dot" cx="700" cy="40"/>
                                </g>
                            </svg>
                        </div>

                        <!-- Verifiable Metrics Breakdown Strip -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--ep-border);">
                            <div style="background: var(--ep-card-bg, #f8fafc); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--ep-border);">
                                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--ep-text-secondary); display: block;" title="Total times posts appeared on screens">Impressions</span>
                                <strong style="font-size: 16px; color: var(--ep-text-title);" id="epDetailImpressions">{{ number_format($calcImpressions) }}</strong>
                            </div>
                            <div style="background: var(--ep-card-bg, #f8fafc); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--ep-border);">
                                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--ep-text-secondary); display: block;" title="Unique accounts that viewed content">Unique Reach</span>
                                <strong style="font-size: 16px; color: #2563eb;" id="epDetailReach">{{ number_format($calcReach) }}</strong>
                            </div>
                            <div style="background: var(--ep-card-bg, #f8fafc); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--ep-border);">
                                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--ep-text-secondary); display: block;" title="Likes, Comments, Shares, Clicks">Engagements</span>
                                <strong style="font-size: 16px; color: #d97706;" id="epDetailEngagements">{{ number_format($totalEngagements) }}</strong>
                            </div>
                            <div style="background: var(--ep-card-bg, #f8fafc); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--ep-border);">
                                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--ep-text-secondary); display: block;" title="Engagements / Reach">Rate</span>
                                <strong style="font-size: 16px; color: #059669;" id="epDetailRate">{{ $engagementRate }}</strong>
                            </div>
                            <div style="background: var(--ep-card-bg, #f8fafc); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--ep-border);">
                                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--ep-text-secondary); display: block;" title="Reels & Video Views">Video Views</span>
                                <strong style="font-size: 16px; color: var(--ep-text-title);" id="epDetailVideoViews">{{ number_format($calcVideoViews) }}</strong>
                            </div>
                            <div style="background: var(--ep-card-bg, #f8fafc); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--ep-border);">
                                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--ep-text-secondary); display: block;" title="24h Story views">Story Views</span>
                                <strong style="font-size: 16px; color: var(--ep-text-title);" id="epDetailStoryViews">{{ number_format($calcStoryViews) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Sub-pane 2: Audience Geography -->
                    <div id="epAnalyticSubAudience" style="display: none; padding: 20px;">
                        <span style="font-size: 13px; font-weight: 700; color: var(--ep-text-title); display: block; margin-bottom: 12px;">Top Follower Cities (Actual Database Records)</span>
                        @php
                            $topCities = $audienceInsights['top_cities'] ?? [];
                        @endphp
                        @forelse($topCities as $city)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--ep-border); font-size: 13.5px;">
                                <span style="font-weight: 600; color: var(--ep-text-title);">{{ $city['city'] ?? $city->city }}</span>
                                <span style="color: var(--ep-brand-blue); font-weight: 700;">{{ $city['count'] ?? $city->count }} followers</span>
                            </div>
                        @empty
                            <div style="padding: 24px; text-align: center; color: var(--ep-text-secondary); font-size: 13px;">
                                Geographic location data will appear as more followers complete their profile location.
                            </div>
                        @endforelse
                    </div>

                    <!-- Sub-pane 3: Top Content -->
                    <div id="epAnalyticSubContent" style="display: none; padding: 14px 20px;">
                        <span style="font-size: 13px; font-weight: 700; color: var(--ep-text-title); display: block; margin-bottom: 8px;">Top Performing Posts by Engagement</span>
                        <div id="epAnalyticTopContentList">
                            @php
                                $topContent = $activeAnalytics['top_content'] ?? [];
                            @endphp
                            @forelse($topContent as $tPost)
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--ep-border); gap: 14px;">
                                    <div style="min-width: 0; flex: 1;">
                                        <p style="font-size: 13px; font-weight: 600; color: var(--ep-text-title); margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $tPost->content ?? ($tPost['content'] ?? 'Post') }}
                                        </p>
                                        <span style="font-size: 11.5px; color: var(--ep-text-muted);">
                                            {{ isset($tPost->created_at) ? \Carbon\Carbon::parse($tPost->created_at)->diffForHumans() : 'Recently' }}
                                        </span>
                                    </div>
                                    <div style="display: flex; gap: 12px; font-size: 12px; color: var(--ep-text-secondary); flex-shrink: 0;">
                                        <span>👍 {{ $tPost->likes_count ?? ($tPost['likes_count'] ?? 0) }}</span>
                                        <span>💬 {{ $tPost->comments_count ?? ($tPost['comments_count'] ?? 0) }}</span>
                                        <span>↗ {{ $tPost->shares_count ?? ($tPost['shares_count'] ?? 0) }}</span>
                                    </div>
                                </div>
                            @empty
                                <div style="padding: 24px; text-align: center; color: var(--ep-text-secondary); font-size: 13px;">
                                    No top content identified yet. Publish updates to view interaction metrics.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 7. PAGE FOLLOWERS PREVIEW & SUMMARY CARD -->
                <div class="ep-card" style="margin-bottom: 20px;">
                    <div class="ep-card-header">
                        <div>
                            <h2 class="ep-card-title">Followers Preview ({{ number_format($totalFollowers) }})</h2>
                            <p class="ep-card-subtitle">Recent supporters following your page updates</p>
                        </div>
                        <a href="javascript:void(0)" class="ep-link-see-all" onclick="epSwitchStudioTab('followers')">View All Followers</a>
                    </div>

                    <div style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="ep-avatar-row-stack">
                                @forelse($followers->take(6) as $f)
                                    @if($f->user && $f->user->profile_photo_url)
                                        <img src="{{ $f->user->profile_photo_url }}" alt="{{ $f->user->name }}" title="{{ $f->user->name }}">
                                    @else
                                        <div class="ep-avatar-stack-item ep-avatar-fallback accent-{{ $loop->iteration % 4 }}">
                                            {{ mb_strtoupper(mb_substr($f->user->name ?? 'F', 0, 1)) }}
                                        </div>
                                    @endif
                                @empty
                                    <span style="font-size: 13px; color: var(--ep-text-secondary);">No followers yet</span>
                                @endforelse
                            </div>
                            <span style="font-size: 13.5px; font-weight: 600; color: var(--ep-text-title);">
                                {{ number_format($totalFollowers) }} people follow this page
                            </span>
                        </div>

                        <button type="button" class="ep-btn-manage" onclick="epSwitchStudioTab('followers')">
                            <span>Manage Followers List</span>
                        </button>
                    </div>
                </div>

                <!-- 8. RECENT POSTS CARD -->
                <div class="ep-card">
                    <div class="ep-card-header">
                        <div>
                            <h2 class="ep-card-title">Recent Posts</h2>
                            <p class="ep-card-subtitle">Published content and media updates</p>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <a href="javascript:void(0)" class="ep-link-see-all" onclick="epSwitchStudioTab('posts')">See All</a>
                            <button type="button" class="ep-btn-view" onclick="epOpenModal('epModalCreatePost')">
                                @include('pages.partials.icon', ['name' => 'plus', 'size' => 14])
                                <span>Create Post</span>
                            </button>
                        </div>
                    </div>

                    <div class="ep-recent-posts-list">
                        @forelse($recentPosts as $post)
                            <div class="ep-recent-post-row">
                                <div class="ep-post-thumb-box">
                                    @if($post->media && $post->media->isNotEmpty())
                                        <img src="{{ $post->media->first()->getVariantUrl('thumbnail') ?: ($post->media->first()->url ?: asset('storage/' . $post->media->first()->original_path)) }}" alt="Post Media">
                                    @else
                                        @include('pages.partials.icon', ['name' => 'file-text', 'size' => 20])
                                    @endif
                                </div>
                                <div class="ep-post-info-box">
                                    <p class="ep-post-excerpt">{{ $post->content ?: 'Media post without caption' }}</p>
                                    <div class="ep-post-stats-row">
                                        <span>{{ $post->created_at ? $post->created_at->diffForHumans() : 'Recently' }}</span>
                                        <span>•</span>
                                        <span class="ep-post-stats-item">👍 {{ $post->likes_count ?? 0 }}</span>
                                        <span class="ep-post-stats-item">💬 {{ $post->comments_count ?? 0 }}</span>
                                        <span class="ep-post-stats-item">↗ {{ $post->shares_count ?? 0 }}</span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="{{ route('pages.show', $page->slug) }}" class="ep-btn-manage" style="padding: 6px 12px; font-size: 12.5px;">View</a>
                                </div>
                            </div>
                        @empty
                            <div class="ep-empty-pages-box">
                                <div class="ep-empty-icon-wrap">
                                    @include('pages.partials.icon', ['name' => 'file-text', 'size' => 26])
                                </div>
                                <h3 class="ep-empty-title">No Posts Published Yet</h3>
                                <p class="ep-empty-desc">Share updates, product announcements, and stories with your followers.</p>
                                <button type="button" class="ep-btn-header-create" onclick="epOpenModal('epModalCreatePost')">
                                    <span>+ Create First Post</span>
                                </button>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- TAB 2: POSTS FULL PANE -->
            <div id="studio-pane-posts" class="ep-studio-pane {{ ($activeTab ?? 'posts') === 'posts' ? 'active' : '' }}" style="display: {{ ($activeTab ?? 'posts') === 'posts' ? 'block' : 'none' }};">
                <div class="ep-card ep-posts-main-card">
                    <!-- Page Posts Header and Toolbar -->
                    <div class="ep-posts-card-header">
                        <div>
                            <h1 class="ep-posts-header-title">Page Posts</h1>
                            <p class="ep-posts-header-subtitle">All posts from your page</p>
                        </div>
                        <div class="ep-posts-toolbar-actions">
                            <!-- Search Posts input -->
                            <form method="GET" action="{{ route('pages.posts', $page->slug) }}" class="ep-posts-search-form" id="epPostsSearchForm">
                                <input type="hidden" name="tab" value="posts">
                                <input type="hidden" name="type" value="{{ $selectedType ?? 'all' }}">
                                <input type="hidden" name="status" value="{{ $selectedStatus ?? 'all' }}">
                                <input type="hidden" name="sort" value="{{ $selectedSort ?? 'newest' }}">
                                <div class="ep-search-input-wrap">
                                    <span class="ep-search-icon">
                                        @include('pages.partials.icon', ['name' => 'search', 'size' => 15])
                                    </span>
                                    <input type="text"
                                           name="q"
                                           id="epPostsSearchInput"
                                           placeholder="Search posts..."
                                           value="{{ $searchQuery ?? '' }}">
                                    @if(!empty($searchQuery))
                                        <a href="{{ route('pages.posts', ['slug' => $page->slug, 'tab' => 'posts', 'type' => $selectedType ?? 'all']) }}" class="ep-search-clear-btn" title="Clear search">&times;</a>
                                    @endif
                                </div>
                            </form>

                            <!-- Filter toggle button -->
                            <button type="button" class="ep-btn-filter-toggle {{ (($selectedStatus ?? 'all') !== 'all' || ($selectedSort ?? 'newest') !== 'newest') ? 'has-active-filter' : '' }}" onclick="epToggleFilterDrawer()" title="Filter Posts">
                                @include('pages.partials.icon', ['name' => 'filter', 'size' => 15])
                                <span>Filter</span>
                                @if(($selectedStatus ?? 'all') !== 'all' || ($selectedSort ?? 'newest') !== 'newest')
                                    <span class="ep-filter-active-dot"></span>
                                @endif
                            </button>

                            <!-- New Post button -->
                            <button type="button" class="ep-btn-new-post-primary" onclick="epOpenModal('epModalCreatePost')">
                                @include('pages.partials.icon', ['name' => 'plus', 'size' => 15, 'strokeWidth' => 2.5])
                                <span>New Post</span>
                            </button>
                        </div>
                    </div>

                    <!-- Content-Type Filters Bar -->
                    <div class="ep-type-chips-bar" role="tablist">
                        <a href="{{ route('pages.posts', ['slug' => $page->slug, 'tab' => 'posts', 'type' => 'all', 'status' => $selectedStatus ?? 'all', 'sort' => $selectedSort ?? 'newest', 'q' => $searchQuery ?? '']) }}"
                           class="ep-type-chip {{ ($selectedType ?? 'all') === 'all' ? 'active' : '' }}">
                            <span>All Posts</span>
                            <span class="ep-type-badge" id="epTypeChipAllCount">{{ number_format($typeCounts['all'] ?? ($posts ? $posts->total() : 0)) }}</span>
                        </a>
                        <a href="{{ route('pages.posts', ['slug' => $page->slug, 'tab' => 'posts', 'type' => 'text', 'status' => $selectedStatus ?? 'all', 'sort' => $selectedSort ?? 'newest', 'q' => $searchQuery ?? '']) }}"
                           class="ep-type-chip {{ ($selectedType ?? 'all') === 'text' ? 'active' : '' }}">
                            @include('pages.partials.icon', ['name' => 'file-text', 'size' => 13])
                            <span>Text</span>
                            <span class="ep-type-badge" id="epTypeChipTextCount">{{ number_format($typeCounts['text'] ?? 0) }}</span>
                        </a>
                        <a href="{{ route('pages.posts', ['slug' => $page->slug, 'tab' => 'posts', 'type' => 'photos', 'status' => $selectedStatus ?? 'all', 'sort' => $selectedSort ?? 'newest', 'q' => $searchQuery ?? '']) }}"
                           class="ep-type-chip {{ in_array($selectedType ?? 'all', ['photos', 'photo']) ? 'active' : '' }}">
                            @include('pages.partials.icon', ['name' => 'image', 'size' => 13])
                            <span>Photos</span>
                            <span class="ep-type-badge" id="epTypeChipPhotosCount">{{ number_format($typeCounts['photos'] ?? 0) }}</span>
                        </a>
                        <a href="{{ route('pages.posts', ['slug' => $page->slug, 'tab' => 'posts', 'type' => 'videos', 'status' => $selectedStatus ?? 'all', 'sort' => $selectedSort ?? 'newest', 'q' => $searchQuery ?? '']) }}"
                           class="ep-type-chip {{ in_array($selectedType ?? 'all', ['videos', 'video']) ? 'active' : '' }}">
                            @include('pages.partials.icon', ['name' => 'video', 'size' => 13])
                            <span>Videos</span>
                            <span class="ep-type-badge" id="epTypeChipVideosCount">{{ number_format($typeCounts['videos'] ?? 0) }}</span>
                        </a>
                        <a href="{{ route('pages.posts', ['slug' => $page->slug, 'tab' => 'posts', 'type' => 'links', 'status' => $selectedStatus ?? 'all', 'sort' => $selectedSort ?? 'newest', 'q' => $searchQuery ?? '']) }}"
                           class="ep-type-chip {{ in_array($selectedType ?? 'all', ['links', 'link']) ? 'active' : '' }}">
                            @include('pages.partials.icon', ['name' => 'link', 'size' => 13])
                            <span>Links</span>
                            <span class="ep-type-badge" id="epTypeChipLinksCount">{{ number_format($typeCounts['links'] ?? 0) }}</span>
                        </a>
                    </div>

                    <!-- Filter Drawer (Collapsible) -->
                    <div id="epFilterDrawer" class="ep-filter-drawer" style="display: {{ (($selectedStatus ?? 'all') !== 'all' || ($selectedSort ?? 'newest') !== 'newest') ? 'block' : 'none' }};">
                        <form method="GET" action="{{ route('pages.posts', $page->slug) }}" class="ep-filter-drawer-grid">
                            <input type="hidden" name="tab" value="posts">
                            <input type="hidden" name="type" value="{{ $selectedType ?? 'all' }}">
                            <input type="hidden" name="q" value="{{ $searchQuery ?? '' }}">

                            <div class="ep-filter-item">
                                <label class="ep-filter-label">Publication Status</label>
                                <select name="status" class="ep-filter-select" onchange="this.form.submit()">
                                    <option value="all" {{ ($selectedStatus ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                                    <option value="published" {{ ($selectedStatus ?? 'all') === 'published' ? 'selected' : '' }}>Published</option>
                                    <option value="scheduled" {{ ($selectedStatus ?? 'all') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                    <option value="draft" {{ in_array($selectedStatus ?? 'all', ['draft', 'drafts']) ? 'selected' : '' }}>Drafts</option>
                                </select>
                            </div>

                            <div class="ep-filter-item">
                                <label class="ep-filter-label">Sort Order</label>
                                <select name="sort" class="ep-filter-select" onchange="this.form.submit()">
                                    <option value="newest" {{ ($selectedSort ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest First</option>
                                    <option value="oldest" {{ ($selectedSort ?? 'newest') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                    <option value="most_reactions" {{ in_array($selectedSort ?? 'newest', ['most_reactions', 'most_likes']) ? 'selected' : '' }}>Most Reactions</option>
                                    <option value="most_comments" {{ ($selectedSort ?? 'newest') === 'most_comments' ? 'selected' : '' }}>Most Comments</option>
                                    <option value="most_shares" {{ ($selectedSort ?? 'newest') === 'most_shares' ? 'selected' : '' }}>Most Shares</option>
                                </select>
                            </div>

                            <div class="ep-filter-item ep-filter-actions-item">
                                <a href="{{ route('pages.posts', ['slug' => $page->slug, 'tab' => 'posts']) }}" class="ep-btn-filter-reset">
                                    Reset Filters
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Horizontal Post Rows List -->
                    <div class="ep-posts-list-wrap" id="epPostsListContainer">
                        @php $postsCollection = isset($posts) ? $posts : $recentPosts; @endphp
                        @forelse($postsCollection as $post)
                            @php
                                $firstMedia = $post->media ? $post->media->first() : null;
                                $isVideo = $firstMedia && (str_starts_with($firstMedia->mime_type ?? '', 'video/') || $post->type === 'video');
                                $mediaCount = $post->media ? $post->media->count() : 0;
                                $fullText = $post->content ?? '';
                                $isLong = mb_strlen($fullText) > 160;
                                $shortText = $isLong ? mb_substr($fullText, 0, 155) . '...' : $fullText;
                            @endphp
                            <div class="ep-post-row-item" id="ep-post-row-{{ $post->id }}">
                                <!-- Left Section: Media Preview -->
                                <div class="ep-post-media-box">
                                    @if($firstMedia)
                                        <div class="ep-post-media-wrapper">
                                            <img src="{{ $firstMedia->getVariantUrl('thumbnail') ?: ($firstMedia->getVariantUrl('medium') ?: ($firstMedia->url ?: asset('storage/' . $firstMedia->original_path))) }}"
                                                 alt="Post Thumbnail"
                                                 class="ep-post-media-img"
                                                 loading="lazy">
                                            @if($isVideo)
                                                <div class="ep-media-video-badge" title="Video Publication">
                                                    @include('pages.partials.icon', ['name' => 'play', 'size' => 14])
                                                </div>
                                            @elseif($mediaCount > 1)
                                                <div class="ep-media-count-badge" title="{{ $mediaCount }} photos">
                                                    +{{ $mediaCount - 1 }}
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($post->link_preview && !empty($post->link_preview['image']))
                                        <div class="ep-post-media-wrapper">
                                            <img src="{{ $post->link_preview['image'] }}" alt="Link Thumbnail" class="ep-post-media-img" loading="lazy">
                                            <div class="ep-media-link-badge">
                                                @include('pages.partials.icon', ['name' => 'link', 'size' => 12])
                                            </div>
                                        </div>
                                    @else
                                        <!-- Text Post Aesthetic Placeholder -->
                                        <div class="ep-post-media-text-placeholder">
                                            <span class="ep-text-placeholder-icon">
                                                @include('pages.partials.icon', ['name' => 'file-text', 'size' => 24])
                                            </span>
                                            <span class="ep-text-placeholder-badge">TEXT</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Center Section: Post Information -->
                                <div class="ep-post-content-box">
                                    <div class="ep-post-author-line">
                                        <div class="ep-post-author-avatar">
                                            @if($page->avatar_url)
                                                <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}">
                                            @else
                                                <div class="ep-avatar-fallback accent-0">{{ mb_strtoupper(mb_substr($page->name, 0, 1)) }}</div>
                                            @endif
                                        </div>
                                        <div class="ep-post-author-name-row">
                                            <span class="ep-post-page-name">{{ $page->name }}</span>
                                            @if($page->is_verified)
                                                <span class="ep-verified-check-mini" title="Verified Page">✓</span>
                                            @endif
                                            <span class="ep-post-dot">•</span>
                                            <span class="ep-post-time" title="{{ $post->created_at ? $post->created_at->format('F d, Y \a\t h:i A') : '' }}">
                                                {{ $post->created_at ? $post->created_at->diffForHumans() : 'Recently' }}
                                            </span>
                                            <span class="ep-post-dot">•</span>
                                            <span class="ep-visibility-pill" id="ep-vis-badge-{{ $post->id }}" title="Audience: {{ ucfirst($post->audience ?? 'public') }}">
                                                @if(($post->audience ?? 'public') === 'public')
                                                    @include('pages.partials.icon', ['name' => 'globe', 'size' => 11])
                                                    <span>Public</span>
                                                @else
                                                    @include('pages.partials.icon', ['name' => 'lock', 'size' => 11])
                                                    <span>{{ ucfirst($post->audience) }}</span>
                                                @endif
                                            </span>
                                            <span id="ep-pin-badge-wrap-{{ $post->id }}">
                                                @if($post->is_pinned)
                                                    <span class="ep-pinned-pill" title="Pinned to top">📌 Pinned</span>
                                                @endif
                                            </span>
                                            @if($post->status === 'draft')
                                                <span class="ep-status-pill draft">Draft</span>
                                            @elseif($post->status === 'scheduled')
                                                <span class="ep-status-pill scheduled">Scheduled</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Post Content Excerpt with See More -->
                                    <div class="ep-post-body">
                                        <p class="ep-post-text" id="ep-post-text-{{ $post->id }}">{{ $shortText ?: ($firstMedia ? 'Media publication' : 'Untitled post') }}</p>
                                        @if($isLong)
                                            <button type="button" class="ep-btn-see-more" id="ep-btn-see-more-{{ $post->id }}" onclick="epToggleSeeMore({{ $post->id }}, '{{ addslashes($fullText) }}', '{{ addslashes($shortText) }}')">
                                                See More
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Link Preview Snippet -->
                                    @if($post->link_preview && !empty($post->link_preview['title']))
                                        <div class="ep-post-link-card">
                                            <span class="ep-link-card-title">{{ $post->link_preview['title'] }}</span>
                                            @if(!empty($post->link_preview['domain']))
                                                <span class="ep-link-card-domain">{{ $post->link_preview['domain'] }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Right Section: Engagement & Action Buttons -->
                                <div class="ep-post-right-col">
                                    <!-- Engagement Metrics Strip -->
                                    <div class="ep-post-metrics-strip">
                                        <span class="ep-post-metric-item" title="Reactions / Likes" id="ep-post-likes-{{ $post->id }}">
                                            <span class="ep-metric-icon">👍</span>
                                            <span class="ep-metric-count" id="ep-post-likes-cnt-{{ $post->id }}">{{ number_format($post->likes_count ?? 0) }}</span>
                                        </span>
                                        <span class="ep-post-metric-item" title="Comments">
                                            <span class="ep-metric-icon">💬</span>
                                            <span class="ep-metric-count">{{ number_format($post->comments_count ?? 0) }}</span>
                                        </span>
                                        <span class="ep-post-metric-item" title="Shares">
                                            <span class="ep-metric-icon">🔄</span>
                                            <span class="ep-metric-count">{{ number_format($post->shares_count ?? 0) }}</span>
                                        </span>
                                        <span class="ep-post-metric-item" title="Estimated Reach">
                                            <span class="ep-metric-icon">👁️</span>
                                            <span class="ep-metric-count">{{ number_format(max(($post->likes_count ?? 0) * 8 + 45, 12)) }}</span>
                                        </span>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="ep-post-action-buttons">
                                        <button type="button" class="ep-btn-post-action" onclick="epTogglePostLike({{ $post->id }})" id="ep-btn-like-{{ $post->id }}" title="React / Like">
                                            @include('pages.partials.icon', ['name' => 'thumbs-up', 'size' => 14])
                                            <span>Like</span>
                                        </button>

                                        <a href="/posts/{{ $post->id }}" target="_blank" class="ep-btn-post-action" title="View Post & Comments">
                                            @include('pages.partials.icon', ['name' => 'message-square', 'size' => 14])
                                            <span>Comment</span>
                                        </a>

                                        <button type="button" class="ep-btn-post-action" onclick="epSharePostLink({{ $post->id }})" title="Share Post">
                                            @include('pages.partials.icon', ['name' => 'share-2', 'size' => 14])
                                            <span>Share</span>
                                        </button>

                                        <!-- More Actions Popover -->
                                        <div class="ep-dropdown-wrap">
                                            <button type="button" class="ep-btn-post-action-dots" onclick="epToggleMenu(event, 'post-action-menu-{{ $post->id }}')" aria-label="More post options">
                                                @include('pages.partials.icon', ['name' => 'more-horizontal', 'size' => 16])
                                            </button>
                                            <div id="post-action-menu-{{ $post->id }}" class="ep-popover-menu">
                                                <a href="/posts/{{ $post->id }}" target="_blank">
                                                    @include('pages.partials.icon', ['name' => 'eye', 'size' => 14])
                                                    <span>View Post</span>
                                                </a>
                                                @if($canManageRoles)
                                                    <button type="button" onclick="epOpenEditPostModal({{ $post->id }}, '{{ addslashes($post->content ?? '') }}', '{{ $post->audience ?? 'public' }}', '{{ $post->status ?? 'published' }}')">
                                                        @include('pages.partials.icon', ['name' => 'edit', 'size' => 14])
                                                        <span>Edit Post</span>
                                                    </button>
                                                    <button type="button" onclick="epTogglePinPost({{ $post->id }})">
                                                        @include('pages.partials.icon', ['name' => 'bookmark', 'size' => 14])
                                                        <span id="ep-pin-label-{{ $post->id }}">{{ $post->is_pinned ? 'Unpin Post' : 'Pin Post' }}</span>
                                                    </button>
                                                    <button type="button" onclick="epChangePostVisibilityPrompt({{ $post->id }}, '{{ $post->audience ?? 'public' }}')">
                                                        @include('pages.partials.icon', ['name' => 'globe', 'size' => 14])
                                                        <span>Change Visibility</span>
                                                    </button>
                                                @endif
                                                <button type="button" onclick="epCopyPostLink({{ $post->id }})">
                                                    @include('pages.partials.icon', ['name' => 'copy', 'size' => 14])
                                                    <span>Copy Post Link</span>
                                                </button>
                                                @if($canManageRoles)
                                                    <button type="button" class="ep-menu-item-danger" onclick="epConfirmDeletePost({{ $post->id }})">
                                                        @include('pages.partials.icon', ['name' => 'trash-2', 'size' => 14])
                                                        <span>Delete Post</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="ep-empty-posts-card" id="epEmptyPostsState">
                                <div class="ep-empty-posts-icon">
                                    @include('pages.partials.icon', ['name' => 'file-text', 'size' => 36])
                                </div>
                                <h3 class="ep-empty-posts-title">No Posts Found</h3>
                                <p class="ep-empty-posts-subtitle">
                                    @if(!empty($searchQuery) || ($selectedType ?? 'all') !== 'all' || ($selectedStatus ?? 'all') !== 'all')
                                        No posts matched your current search or filter criteria. Try resetting filters.
                                    @else
                                        This page hasn't published any posts yet. Start engaging your audience now!
                                    @endif
                                </p>
                                <div class="ep-empty-posts-actions">
                                    @if(!empty($searchQuery) || ($selectedType ?? 'all') !== 'all' || ($selectedStatus ?? 'all') !== 'all')
                                        <a href="{{ route('pages.posts', ['slug' => $page->slug, 'tab' => 'posts']) }}" class="ep-btn-manage">
                                            Clear Filters
                                        </a>
                                    @endif
                                    @if($canManageRoles)
                                        <button type="button" class="ep-btn-header-create" onclick="epOpenModal('epModalCreatePost')">
                                            + Create First Post
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    @if(isset($posts) && $posts->hasPages())
                        <div class="ep-posts-pagination-wrap">
                            {{ $posts->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- TAB 3: ABOUT PANE -->
            <div id="studio-pane-about" class="ep-studio-pane" style="display: none;">
                <div class="ep-card">
                    <div class="ep-card-header">
                        <div>
                            <h2 class="ep-card-title">About {{ $page->name }}</h2>
                            <p class="ep-card-subtitle">Public entity information and verified credentials</p>
                        </div>
                        <button type="button" class="ep-btn-manage" onclick="epOpenModal('epModalEditPage')">
                            @include('pages.partials.icon', ['name' => 'edit', 'size' => 14])
                            <span>Edit Details</span>
                        </button>
                    </div>
                    <div style="padding: 20px 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                        <div>
                            <span style="font-size: 12px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">Category</span>
                            <p style="font-size: 15px; font-weight: 600; color: var(--ep-text-title); margin: 4px 0 16px;" id="epAboutDisplayCategory">{{ $page->category ?: 'General' }}</p>

                            <span style="font-size: 12px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">Username</span>
                            <p style="font-size: 15px; font-weight: 600; color: var(--ep-text-title); margin: 4px 0 16px;">{{ $page->username ? '@' . $page->username : 'Not set' }}</p>

                            <span style="font-size: 12px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">Website</span>
                            <p style="font-size: 15px; font-weight: 600; color: var(--ep-text-title); margin: 4px 0 16px;" id="epAboutDisplayWebsite">
                                @if($page->website)
                                    <a href="{{ $page->website }}" target="_blank" rel="noopener" style="color: var(--ep-brand-blue);">{{ $page->website }}</a>
                                @else
                                    Not provided
                                @endif
                            </p>
                        </div>
                        <div>
                            <span style="font-size: 12px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">Contact Email</span>
                            <p style="font-size: 15px; font-weight: 600; color: var(--ep-text-title); margin: 4px 0 16px;" id="epAboutDisplayEmail">{{ $page->email ?: 'Not provided' }}</p>

                            <span style="font-size: 12px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">Phone</span>
                            <p style="font-size: 15px; font-weight: 600; color: var(--ep-text-title); margin: 4px 0 16px;" id="epAboutDisplayPhone">{{ $page->phone ?: 'Not provided' }}</p>

                            <span style="font-size: 12px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">Location</span>
                            <p style="font-size: 15px; font-weight: 600; color: var(--ep-text-title); margin: 4px 0 16px;" id="epAboutDisplayLocation">
                                {{ implode(', ', array_filter([$page->address, $page->city, $page->country])) ?: 'Not provided' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: PHOTOS PANE -->
            <div id="studio-pane-photos" class="ep-studio-pane" style="display: none;">
                <div class="ep-card">
                    <div class="ep-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h2 class="ep-card-title">Photos</h2>
                            <p class="ep-card-subtitle">Photo media published on {{ $page->name }}</p>
                        </div>
                        @if($canManageRoles)
                            <button type="button" class="ep-btn-new-post-primary" onclick="epOpenCreatePostWithType('photo')">
                                @include('pages.partials.icon', ['name' => 'plus', 'size' => 15, 'strokeWidth' => 2.5])
                                <span>Upload Photo</span>
                            </button>
                        @endif
                    </div>
                    <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;">
                        @php
                            $allPhotos = isset($pagePhotos) && $pagePhotos->isNotEmpty() ? $pagePhotos : collect();
                            if ($allPhotos->isEmpty() && isset($recentPosts)) {
                                foreach($recentPosts as $p) {
                                    if ($p->media) {
                                        foreach($p->media as $m) {
                                            if (!str_starts_with($m->mime_type ?? '', 'video/')) {
                                                $allPhotos->push($m);
                                            }
                                        }
                                    }
                                }
                            }
                        @endphp
                        @forelse($allPhotos as $m)
                            <div style="aspect-ratio: 1; border-radius: 8px; overflow: hidden; background: #0F172A;">
                                <img src="{{ $m->getVariantUrl('medium') ?: ($m->url ?: asset('storage/' . $m->original_path)) }}" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        @empty
                            <div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--ep-text-secondary);">
                                No photos uploaded to this page yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- TAB 5: VIDEOS PANE -->
            <div id="studio-pane-videos" class="ep-studio-pane" style="display: none;">
                <div class="ep-card">
                    <div class="ep-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h2 class="ep-card-title">Videos</h2>
                            <p class="ep-card-subtitle">Video publications and recordings on {{ $page->name }}</p>
                        </div>
                        @if($canManageRoles)
                            <button type="button" class="ep-btn-new-post-primary" onclick="epOpenCreatePostWithType('video')">
                                @include('pages.partials.icon', ['name' => 'plus', 'size' => 15, 'strokeWidth' => 2.5])
                                <span>Upload Video</span>
                            </button>
                        @endif
                    </div>
                    <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;">
                        @php
                            $allVideos = isset($pageVideos) && $pageVideos->isNotEmpty() ? $pageVideos : collect();
                            if ($allVideos->isEmpty() && isset($recentPosts)) {
                                foreach($recentPosts as $p) {
                                    if ($p->media) {
                                        foreach($p->media as $m) {
                                            if (str_starts_with($m->mime_type ?? '', 'video/')) {
                                                $allVideos->push($m);
                                            }
                                        }
                                    }
                                }
                            }
                        @endphp
                        @forelse($allVideos as $v)
                            <div style="border-radius: 8px; overflow: hidden; background: #0F172A; position: relative;">
                                <video src="{{ $v->getVariantUrl('original') ?: ($v->url ?: asset('storage/' . $v->original_path)) }}" controls style="width: 100%; height: 160px; object-fit: cover;"></video>
                            </div>
                        @empty
                            <div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--ep-text-secondary);">
                                No videos uploaded to this page yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- TAB 6: EVENTS PANE -->
            <div id="studio-pane-events" class="ep-studio-pane" style="display: none;">
                <div class="ep-card">
                    <div class="ep-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h2 class="ep-card-title">Page Events</h2>
                            <p class="ep-card-subtitle">Upcoming and community events hosted by {{ $page->name }}</p>
                        </div>
                        @if($canManageRoles)
                            <button type="button" class="ep-btn-new-post-primary" onclick="epOpenModal('epModalCreateEvent')">
                                @include('pages.partials.icon', ['name' => 'plus', 'size' => 15, 'strokeWidth' => 2.5])
                                <span>New Event</span>
                            </button>
                        @endif
                    </div>
                    <div style="padding: 20px;">
                        <div id="epEventsGridContainer" style="display: {{ (isset($pageEvents) && $pageEvents->isNotEmpty()) ? 'grid' : 'none' }}; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                            @if(isset($pageEvents))
                                @foreach($pageEvents as $event)
                                    <div class="ep-event-card-item" id="ep-event-card-{{ $event->id }}" style="border: 1px solid var(--ep-border); border-radius: 10px; overflow: hidden; background: var(--ep-card-bg); display: flex; flex-direction: column;">
                                        <div style="height: 110px; background: linear-gradient(135deg, #059669, #10B981); display: flex; align-items: center; justify-content: center; color: white; padding: 12px; position: relative;">
                                            <div style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                                {{ $event->is_online ? '🌐 Online Event' : '📍 ' . ($event->location ?: 'Physical Event') }}
                                            </div>
                                            <span style="font-size: 26px;">📅</span>
                                        </div>
                                        <div style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                                            <span style="font-size: 12px; font-weight: 700; color: #059669; text-transform: uppercase;">
                                                {{ $event->start_time ? $event->start_time->format('l, F d, Y • h:i A') : 'Date TBD' }}
                                            </span>
                                            <h3 style="font-size: 15px; font-weight: 700; color: var(--ep-text-title); margin: 6px 0 8px; line-height: 1.35;">
                                                {{ $event->title }}
                                            </h3>
                                            <p style="font-size: 13px; color: var(--ep-text-secondary); margin: 0 0 14px; line-height: 1.45; flex: 1;">
                                                {{ Str::limit($event->description, 110) }}
                                            </p>
                                            <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--ep-border); padding-top: 12px; font-size: 12.5px; color: var(--ep-text-secondary);">
                                                <span id="ep-event-rsvp-cnt-{{ $event->id }}">👥 {{ number_format($event->rsvp_count ?? 0) }} Attending</span>
                                                <div style="display: flex; gap: 6px; align-items: center;">
                                                    <button type="button" class="ep-btn-manage" style="padding: 4px 10px; font-size: 12px;" onclick="epRsvpEvent({{ $event->id }})">
                                                        RSVP
                                                    </button>
                                                    @if($canManageRoles)
                                                        <button type="button" class="ep-btn-manage" style="padding: 4px 8px; font-size: 12px; color: #dc2626;" onclick="epDeleteEvent({{ $event->id }})" title="Delete Event">
                                                            @include('pages.partials.icon', ['name' => 'trash-2', 'size' => 12])
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div id="epEmptyEventsState" style="display: {{ (!isset($pageEvents) || $pageEvents->isEmpty()) ? 'block' : 'none' }}; padding: 40px; text-align: center; color: var(--ep-text-secondary);">
                            <div style="font-size: 32px; margin-bottom: 8px;">📅</div>
                            <h3 style="font-size: 15px; font-weight: 700; color: var(--ep-text-title); margin: 0 0 4px;">No active events scheduled</h3>
                            <p style="margin: 0; font-size: 13.5px;">Create workshops, webinars, or meetups to connect with your community.</p>
                            @if($canManageRoles)
                                <button type="button" class="ep-btn-header-create" style="margin-top: 14px;" onclick="epOpenModal('epModalCreateEvent')">
                                    + Create First Event
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 6: FOLLOWERS DASHBOARD PANE -->
            <div id="studio-pane-followers" class="ep-studio-pane" style="display: none;">
                <div class="ep-card">
                    <div class="ep-card-header">
                        <div>
                            <h2 class="ep-card-title">Followers Management Dashboard</h2>
                            <p class="ep-card-subtitle">Search, inspect, and manage individuals following {{ $page->name }}</p>
                        </div>
                        <div class="ep-card-controls">
                            <div class="ep-search-input-wrap">
                                <span class="ep-search-icon">
                                    @include('pages.partials.icon', ['name' => 'search', 'size' => 15])
                                </span>
                                <input type="text"
                                       id="epSearchFollowersInput"
                                       placeholder="Search followers..."
                                       oninput="epFilterFollowers(this.value)">
                            </div>
                            <div class="ep-sort-select-wrap">
                                <span>Sort:</span>
                                <select id="epSortFollowersSelect" onchange="epSortFollowers(this.value)">
                                    <option value="newest">Newest First</option>
                                    <option value="oldest">Oldest First</option>
                                    <option value="name">Name</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Followers Summary Row -->
                    <div style="padding: 16px 20px; background: var(--ep-bg-main); border-bottom: 1px solid var(--ep-border); display: flex; gap: 24px; flex-wrap: wrap;">
                        <div>
                            <span style="font-size: 11.5px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">Total Followers</span>
                            <div style="font-size: 20px; font-weight: 800; color: var(--ep-text-title);">{{ number_format($totalFollowers) }}</div>
                        </div>
                        <div>
                            <span style="font-size: 11.5px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">New (Last 30 Days)</span>
                            <div style="font-size: 20px; font-weight: 800; color: #059669;">+{{ $newFollowers }}</div>
                        </div>
                        <div>
                            <span style="font-size: 11.5px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">Growth Rate</span>
                            <div style="font-size: 20px; font-weight: 800; color: var(--ep-brand-blue);">{{ $engagementRate }}</div>
                        </div>
                    </div>

                    <!-- Followers Table -->
                    <div class="ep-followers-table-wrap">
                        <table class="ep-followers-table" id="epFollowersTable">
                            <thead>
                                <tr>
                                    <th>User Profile</th>
                                    <th>Username</th>
                                    <th>Follow Date</th>
                                    <th>Location</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="epFollowersTableBody">
                                @forelse($followers as $follower)
                                    @php
                                        $u = $follower->user;
                                    @endphp
                                    <tr class="ep-follower-row"
                                        data-name="{{ strtolower($u->name ?? '') }}"
                                        data-username="{{ strtolower($u->username ?? '') }}"
                                        data-timestamp="{{ $follower->created_at ? $follower->created_at->timestamp : 0 }}">
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div class="ep-team-avatar" style="width: 36px; height: 36px;">
                                                    @if($u && $u->profile_photo_url)
                                                        <img src="{{ $u->profile_photo_url }}" alt="{{ $u->name }}">
                                                    @else
                                                        <div class="ep-avatar-fallback accent-{{ $loop->iteration % 4 }}" style="width: 100%; height: 100%; font-size: 13px;">
                                                            {{ mb_strtoupper(mb_substr($u->name ?? 'U', 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <span style="font-weight: 700; color: var(--ep-text-title);">{{ $u->name ?? 'User' }}</span>
                                            </div>
                                        </td>
                                        <td style="color: var(--ep-text-secondary);">
                                            {{ $u && $u->username ? '@' . $u->username : '—' }}
                                        </td>
                                        <td style="color: var(--ep-text-secondary); font-size: 13px;">
                                            {{ $follower->created_at ? $follower->created_at->format('M d, Y') : 'Recently' }}
                                        </td>
                                        <td style="color: var(--ep-text-secondary); font-size: 13px;">
                                            {{ $u && $u->profile && $u->profile->city ? $u->profile->city : 'Bangladesh' }}
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: inline-flex; gap: 8px;">
                                                @if($u)
                                                    <a href="{{ getUserProfileUrl($u) }}" class="ep-btn-manage" style="padding: 5px 10px; font-size: 12px;" target="_blank">
                                                        Profile
                                                    </a>
                                                @endif
                                                @if($canManageRoles && $u)
                                                    <button type="button" class="ep-btn-manage" style="padding: 5px 10px; font-size: 12px; color: #EF4444;" onclick="epConfirmRemoveFollower({{ $u->id }}, '{{ addslashes($u->name) }}')">
                                                        Remove
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 32px; color: var(--ep-text-secondary);">
                                            No followers recorded on this page yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($followers->hasPages())
                        <div style="padding: 16px 20px; border-top: 1px solid var(--ep-border);">
                            {{ $followers->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- TAB 7: SETTINGS PANE -->
            @if($canManageRoles)
                <div id="studio-pane-settings" class="ep-studio-pane" style="display: none;">
                    @php
                        $mSettings = $page->getMergedSettings();
                        $mPrivacy = $page->getMergedPrivacySettings();
                        $allowedFormats = $mSettings['publishing']['allowed_formats'] ?? ['text', 'image', 'video', 'reel', 'story', 'live'];
                        $blockedKeywordsStr = implode(', ', $mSettings['moderation']['blocked_keywords'] ?? []);
                        $socialLinks = $page->social_links ?? [];
                        if (is_string($socialLinks)) {
                            $socialLinks = json_decode($socialLinks, true) ?? [];
                        }
                    @endphp

                    <form id="epPageSettingsForm" onsubmit="epSavePageSettings(event)">
                        <!-- Section 1: General Information & Page Visibility -->
                        <div class="ep-card" style="margin-bottom: 20px;">
                            <div class="ep-card-header">
                                <div>
                                    <h2 class="ep-card-title">1. General Information & Visibility</h2>
                                    <p class="ep-card-subtitle">Set your page identity, category, and access level</p>
                                </div>
                            </div>
                            <div style="padding: 20px 24px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                    <div class="ep-form-group">
                                        <label class="ep-form-label">Page Display Name <span style="color: #ef4444;">*</span></label>
                                        <input type="text" id="settingPageName" class="ep-form-control" value="{{ $page->name }}" required>
                                    </div>
                                    <div class="ep-form-group">
                                        <label class="ep-form-label">Page Username (@handle)</label>
                                        <input type="text" id="settingPageUsername" class="ep-form-control" value="{{ $page->username }}" placeholder="mybrand">
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                    <div class="ep-form-group">
                                        <label class="ep-form-label">Category <span style="color: #ef4444;">*</span></label>
                                        <input type="text" id="settingPageCategory" class="ep-form-control" value="{{ $page->category }}" required>
                                    </div>
                                    <div class="ep-form-group">
                                        <label class="ep-form-label">Sub Category</label>
                                        <input type="text" id="settingPageSubCategory" class="ep-form-control" value="{{ $page->sub_category }}" placeholder="e.g. Media, Technology">
                                    </div>
                                </div>

                                <div class="ep-form-group" style="margin-bottom: 16px;">
                                    <label class="ep-form-label">Bio / Tagline</label>
                                    <textarea id="settingPageBio" class="ep-form-control" rows="2" placeholder="Brief summary of your page">{{ $page->bio }}</textarea>
                                </div>

                                <div class="ep-form-group" style="margin-bottom: 16px;">
                                    <label class="ep-form-label">Full Description</label>
                                    <textarea id="settingPageDescription" class="ep-form-control" rows="3" placeholder="Detailed mission and story">{{ $page->description }}</textarea>
                                </div>

                                <div class="ep-form-group">
                                    <label class="ep-form-label">Page Visibility</label>
                                    <select id="settingPageVisibility" class="ep-form-control" style="background: #fff;">
                                        <option value="public" {{ ($page->visibility ?? 'public') === 'public' ? 'selected' : '' }}>Public — Searchable & accessible by all visitors</option>
                                        <option value="unlisted" {{ ($page->visibility ?? '') === 'unlisted' ? 'selected' : '' }}>Unlisted — Accessible only via direct URL</option>
                                        <option value="private" {{ ($page->visibility ?? '') === 'private' ? 'selected' : '' }}>Private — Restricted to page team members only</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Page URL & Discoverability -->
                        <div class="ep-card" style="margin-bottom: 20px;">
                            <div class="ep-card-header">
                                <div>
                                    <h2 class="ep-card-title">2. URL & Search Discoverability</h2>
                                    <p class="ep-card-subtitle">Control SEO indexing and directory listings</p>
                                </div>
                            </div>
                            <div style="padding: 20px 24px;">
                                <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 8px; background: #f8fafc; border: 1px solid var(--ep-border); display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--ep-text-secondary);">Direct Page URL</div>
                                        <div style="font-size: 14px; font-weight: 600; color: var(--ep-brand-blue);" id="settingDisplayUrl">{{ url('/pages/' . $page->slug) }}</div>
                                    </div>
                                    <button type="button" class="ep-btn-secondary" onclick="epCopyStudioUrl()" style="padding: 6px 14px; font-size: 12.5px;">Copy Link</button>
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                        <input type="checkbox" id="settingSearchIndexing" {{ ($mSettings['discoverability']['search_engine_indexing'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title);">Search Engine Indexing</div>
                                            <div style="font-size: 12px; color: var(--ep-text-secondary);">Allow Google and external engines to index this page</div>
                                        </div>
                                    </label>

                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                        <input type="checkbox" id="settingShowDirectory" {{ ($mSettings['discoverability']['show_in_directory'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title);">SocialHub Directory Discovery</div>
                                            <div style="font-size: 12px; color: var(--ep-text-secondary);">Display in community recommendations and category lists</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Messaging Availability & Auto-Reply -->
                        <div class="ep-card" style="margin-bottom: 20px;">
                            <div class="ep-card-header">
                                <div>
                                    <h2 class="ep-card-title">3. Messaging Availability & Inbox Automation</h2>
                                    <p class="ep-card-subtitle">Manage customer inquiries and automated greeting messages</p>
                                </div>
                            </div>
                            <div style="padding: 20px 24px;">
                                <div style="margin-bottom: 16px;">
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 12px;">
                                        <input type="checkbox" id="settingAllowMessages" {{ ($mSettings['messaging']['allow_messages'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title);">Allow Direct Messaging</div>
                                            <div style="font-size: 12px; color: var(--ep-text-secondary);">Enable "Message" button on page header for visitor inquiries</div>
                                        </div>
                                    </label>

                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                        <input type="checkbox" id="settingAutoReplyEnabled" {{ ($mSettings['messaging']['auto_reply_enabled'] ?? false) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title);">Automated Welcome Reply</div>
                                            <div style="font-size: 12px; color: var(--ep-text-secondary);">Immediately respond to first-time message senders</div>
                                        </div>
                                    </label>
                                </div>

                                <div class="ep-form-group">
                                    <label class="ep-form-label">Auto-Reply Welcome Message Content</label>
                                    <textarea id="settingAutoReply" class="ep-form-control" rows="2" placeholder="Thanks for reaching out! We will reply shortly.">{{ $mSettings['messaging']['auto_reply_message'] ?? 'Thanks for reaching out! We will reply shortly.' }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Comment & Content Moderation -->
                        <div class="ep-card" style="margin-bottom: 20px;">
                            <div class="ep-card-header">
                                <div>
                                    <h2 class="ep-card-title">4. Comment & Content Moderation</h2>
                                    <p class="ep-card-subtitle">Automate safety filters and manage community dialogue</p>
                                </div>
                            </div>
                            <div style="padding: 20px 24px;">
                                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                        <input type="checkbox" id="settingAllowComments" {{ ($mSettings['moderation']['allow_comments'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title);">Allow Public Comments</div>
                                            <div style="font-size: 12px; color: var(--ep-text-secondary);">Allow followers and visitors to comment on your page posts</div>
                                        </div>
                                    </label>

                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                        <input type="checkbox" id="settingProfanityFilter" {{ ($mSettings['moderation']['profanity_filter'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title);">Automatic Profanity & Hate Speech Filter</div>
                                            <div style="font-size: 12px; color: var(--ep-text-secondary);">Automatically hide comments containing toxic words</div>
                                        </div>
                                    </label>
                                </div>

                                <div class="ep-form-group">
                                    <label class="ep-form-label">Blocked Keywords (comma separated)</label>
                                    <input type="text" id="settingBlockedKeywords" class="ep-form-control" value="{{ $blockedKeywordsStr }}" placeholder="spam, offensive_word, promo">
                                    <span style="font-size: 11.5px; color: var(--ep-text-secondary); margin-top: 4px; display: block;">Comments with these words will be flagged for review.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Section 5: Notification Preferences -->
                        <div class="ep-card" style="margin-bottom: 20px;">
                            <div class="ep-card-header">
                                <div>
                                    <h2 class="ep-card-title">5. Notification Preferences</h2>
                                    <p class="ep-card-subtitle">Choose how you wish to receive page activity alerts</p>
                                </div>
                            </div>
                            <div style="padding: 20px 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" id="settingNotifyEmail" {{ ($mSettings['notifications']['email_alerts'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title);">Email Alerts</div>
                                        <div style="font-size: 12px; color: var(--ep-text-secondary);">Send email for important page activities</div>
                                    </div>
                                </label>

                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" id="settingNotifyFollows" {{ ($mSettings['notifications']['in_app_follows'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title);">New Followers</div>
                                        <div style="font-size: 12px; color: var(--ep-text-secondary);">In-app notification when someone follows</div>
                                    </div>
                                </label>

                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" id="settingNotifyComments" {{ ($mSettings['notifications']['in_app_comments'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title);">New Comments</div>
                                        <div style="font-size: 12px; color: var(--ep-text-secondary);">In-app notification for comments on posts</div>
                                    </div>
                                </label>

                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" id="settingNotifyMessages" {{ ($mSettings['notifications']['in_app_messages'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title);">Direct Messages</div>
                                        <div style="font-size: 12px; color: var(--ep-text-secondary);">In-app notification for inbox messages</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Section 6: Allowed Publishing Formats -->
                        <div class="ep-card" style="margin-bottom: 20px;">
                            <div class="ep-card-header">
                                <div>
                                    <h2 class="ep-card-title">6. Allowed Publishing Formats</h2>
                                    <p class="ep-card-subtitle">Enable or disable content creation media options</p>
                                </div>
                            </div>
                            <div style="padding: 20px 24px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="format_text" value="text" {{ in_array('text', $allowedFormats, true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                    <span style="font-weight: 600; font-size: 13.5px;">Text Posts</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="format_image" value="image" {{ in_array('image', $allowedFormats, true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                    <span style="font-weight: 600; font-size: 13.5px;">Photo & Graphics</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="format_video" value="video" {{ in_array('video', $allowedFormats, true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                    <span style="font-weight: 600; font-size: 13.5px;">Standard Videos</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="format_reel" value="reel" {{ in_array('reel', $allowedFormats, true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                    <span style="font-weight: 600; font-size: 13.5px;">Reels & Short Clips</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="format_story" value="story" {{ in_array('story', $allowedFormats, true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                    <span style="font-weight: 600; font-size: 13.5px;">24h Stories</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="format_live" value="live" {{ in_array('live', $allowedFormats, true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                    <span style="font-weight: 600; font-size: 13.5px;">Live Broadcasts</span>
                                </label>
                            </div>
                        </div>

                        <!-- Section 7: Privacy & Permitted Audience Controls -->
                        <div class="ep-card" style="margin-bottom: 20px;">
                            <div class="ep-card-header">
                                <div>
                                    <h2 class="ep-card-title">7. Privacy & Audience Controls</h2>
                                    <p class="ep-card-subtitle">Manage public visibility of contact details and default audiences</p>
                                </div>
                            </div>
                            <div style="padding: 20px 24px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                    <div class="ep-form-group">
                                        <label class="ep-form-label">Default Post Audience</label>
                                        <select id="settingDefaultAudience" class="ep-form-control" style="background: #fff;">
                                            <option value="public" {{ ($mPrivacy['default_post_audience'] ?? 'public') === 'public' ? 'selected' : '' }}>Public (Everyone)</option>
                                            <option value="followers" {{ ($mPrivacy['default_post_audience'] ?? '') === 'followers' ? 'selected' : '' }}>Followers Only</option>
                                        </select>
                                    </div>
                                    <div class="ep-form-group">
                                        <label class="ep-form-label">Contact Details Display</label>
                                        <select id="settingShowContactInfo" class="ep-form-control" style="background: #fff;">
                                            <option value="1" {{ ($mSettings['privacy']['show_contact_info'] ?? true) ? 'selected' : '' }}>Show Contact Information Publicly</option>
                                            <option value="0" {{ !($mSettings['privacy']['show_contact_info'] ?? true) ? 'selected' : '' }}>Hide Contact Information</option>
                                        </select>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" id="settingPublicEmail" {{ ($mPrivacy['public_email'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                        <span style="font-size: 13px; font-weight: 600;">Public Email</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" id="settingPublicPhone" {{ ($mPrivacy['public_phone'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                        <span style="font-size: 13px; font-weight: 600;">Public Phone</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" id="settingPublicAddress" {{ ($mPrivacy['public_address'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                        <span style="font-size: 13px; font-weight: 600;">Public Address</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Section 8: Tagging & Mentions -->
                        <div class="ep-card" style="margin-bottom: 20px;">
                            <div class="ep-card-header">
                                <div>
                                    <h2 class="ep-card-title">8. Tagging & Mentions</h2>
                                    <p class="ep-card-subtitle">Control who can tag and mention this page in posts and stories</p>
                                </div>
                            </div>
                            <div style="padding: 20px 24px;">
                                <div style="margin-bottom: 16px;">
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                        <input type="checkbox" id="settingAllowMentions" {{ ($mSettings['tagging']['allow_mentions'] ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title);">Allow Mentions</div>
                                            <div style="font-size: 12px; color: var(--ep-text-secondary);">Allow people to mention @{{ $page->username ?: $page->slug }} in their posts and comments</div>
                                        </div>
                                    </label>
                                </div>

                                <div class="ep-form-group">
                                    <label class="ep-form-label">Who Can Tag This Page</label>
                                    <select id="settingWhoCanTag" class="ep-form-control" style="background: #fff;">
                                        <option value="everyone" {{ ($mSettings['tagging']['who_can_tag'] ?? 'everyone') === 'everyone' ? 'selected' : '' }}>Everyone — Anyone on the platform can tag this page</option>
                                        <option value="followers" {{ ($mSettings['tagging']['who_can_tag'] ?? '') === 'followers' ? 'selected' : '' }}>Followers Only — Only existing followers can tag this page</option>
                                        <option value="none" {{ ($mSettings['tagging']['who_can_tag'] ?? '') === 'none' ? 'selected' : '' }}>Nobody — Disallow all photo and post tagging</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Section 9: Contact Information & Social Integrations -->
                        <div class="ep-card" style="margin-bottom: 20px;">
                            <div class="ep-card-header">
                                <div>
                                    <h2 class="ep-card-title">9. Contact Information & Linked Social Accounts</h2>
                                    <p class="ep-card-subtitle">Official communication channels and cross-platform profiles</p>
                                </div>
                            </div>
                            <div style="padding: 20px 24px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                    <div class="ep-form-group">
                                        <label class="ep-form-label">Official Contact Email</label>
                                        <input type="email" id="settingContactEmail" class="ep-form-control" value="{{ $page->email }}" placeholder="contact@example.com">
                                    </div>
                                    <div class="ep-form-group">
                                        <label class="ep-form-label">Official Contact Phone</label>
                                        <input type="text" id="settingContactPhone" class="ep-form-control" value="{{ $page->phone }}" placeholder="+880 1700 000000">
                                    </div>
                                </div>

                                <div class="ep-form-group" style="margin-bottom: 16px;">
                                    <label class="ep-form-label">Official Website URL</label>
                                    <input type="url" id="settingContactWebsite" class="ep-form-control" value="{{ $page->website }}" placeholder="https://example.com">
                                </div>

                                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                                    <div class="ep-form-group">
                                        <label class="ep-form-label">Street Address</label>
                                        <input type="text" id="settingContactAddress" class="ep-form-control" value="{{ $page->address }}" placeholder="123 Commercial Ave">
                                    </div>
                                    <div class="ep-form-group">
                                        <label class="ep-form-label">City</label>
                                        <input type="text" id="settingContactCity" class="ep-form-control" value="{{ $page->city }}" placeholder="Dhaka">
                                    </div>
                                    <div class="ep-form-group">
                                        <label class="ep-form-label">Zip / Postal Code</label>
                                        <input type="text" id="settingContactZip" class="ep-form-control" value="{{ $page->zip_code }}" placeholder="1212">
                                    </div>
                                </div>

                                <div style="border-top: 1px solid var(--ep-border); padding-top: 16px;">
                                    <div style="font-weight: 700; font-size: 13.5px; color: var(--ep-text-title); margin-bottom: 12px;">Linked Social Media Accounts</div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                                        <div class="ep-form-group">
                                            <label class="ep-form-label">Facebook Profile / Page</label>
                                            <input type="text" id="settingSocialFacebook" class="ep-form-control" value="{{ $socialLinks['facebook'] ?? '' }}" placeholder="https://facebook.com/page">
                                        </div>
                                        <div class="ep-form-group">
                                            <label class="ep-form-label">Twitter / X Handle or URL</label>
                                            <input type="text" id="settingSocialTwitter" class="ep-form-control" value="{{ $socialLinks['twitter'] ?? '' }}" placeholder="https://x.com/handle">
                                        </div>
                                        <div class="ep-form-group">
                                            <label class="ep-form-label">Instagram Profile</label>
                                            <input type="text" id="settingSocialInstagram" class="ep-form-control" value="{{ $socialLinks['instagram'] ?? '' }}" placeholder="https://instagram.com/page">
                                        </div>
                                        <div class="ep-form-group">
                                            <label class="ep-form-label">LinkedIn Organization</label>
                                            <input type="text" id="settingSocialLinkedin" class="ep-form-control" value="{{ $socialLinks['linkedin'] ?? '' }}" placeholder="https://linkedin.com/company/page">
                                        </div>
                                        <div class="ep-form-group">
                                            <label class="ep-form-label">YouTube Channel</label>
                                            <input type="text" id="settingSocialYoutube" class="ep-form-control" value="{{ $socialLinks['youtube'] ?? '' }}" placeholder="https://youtube.com/@channel">
                                        </div>
                                        <div class="ep-form-group">
                                            <label class="ep-form-label">GitHub Organization</label>
                                            <input type="text" id="settingSocialGithub" class="ep-form-control" value="{{ $socialLinks['github'] ?? '' }}" placeholder="https://github.com/org">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Settings Action Bar -->
                        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 24px;">
                            <button type="submit" class="ep-btn-header-create" id="btnSaveSettings" style="padding: 10px 28px; font-size: 14.5px;">
                                <span>Save All Settings</span>
                            </button>
                        </div>
                    </form>

                    <!-- Section 10: Danger Zone (Archive, Restore, Soft-Delete) -->
                    <div class="ep-card" style="border: 1px solid #fecaca; margin-bottom: 30px;">
                        <div class="ep-card-header" style="background: #fff5f5; border-bottom: 1px solid #fee2e2;">
                            <div>
                                <h2 class="ep-card-title" style="color: #b91c1c;">10. Danger Zone</h2>
                                <p class="ep-card-subtitle" style="color: #ef4444;">Archive, deactivate, or delete your page record</p>
                            </div>
                            <span class="ep-badge" style="background: {{ $page->status === 'active' ? '#dcfce7' : '#fee2e2' }}; color: {{ $page->status === 'active' ? '#15803d' : '#b91c1c' }}; font-weight: 800;">
                                Status: {{ ucfirst($page->status) }}
                            </span>
                        </div>
                        <div style="padding: 20px 24px;">
                            <!-- Archive / Deactivate Option -->
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 16px; border-bottom: 1px solid #fee2e2;">
                                <div>
                                    <div style="font-weight: 700; font-size: 14px; color: var(--ep-text-title);">Archive Page</div>
                                    <div style="font-size: 12.5px; color: var(--ep-text-secondary); max-width: 500px;">
                                        Temporarily disable this page. Followers cannot view it, but all data, posts, and team settings are preserved.
                                    </div>
                                </div>
                                @if($page->status === 'active')
                                    <button type="button" class="ep-btn-secondary" onclick="epArchivePageStudio()" style="color: #b45309; border-color: #fde68a; background: #fffbeb;">
                                        Archive Page
                                    </button>
                                @else
                                    <button type="button" class="ep-btn-secondary" onclick="epRestoreArchivedPageStudio()" style="color: #15803d; border-color: #bbf7d0; background: #f0fdf4;">
                                        Reactivate Page
                                    </button>
                                @endif
                            </div>

                            @if($isOwner)
                                <!-- Transfer Ownership Option -->
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid #fee2e2;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 14px; color: #b45309;">Transfer Page Ownership</div>
                                        <div style="font-size: 12.5px; color: var(--ep-text-secondary); max-width: 500px;">
                                            Permanently transfer ownership of this page to another team member or user.
                                        </div>
                                    </div>
                                    <button type="button" class="ep-btn-secondary" onclick="epOpenTransferOwnershipModal()" style="color: #9a3412; border-color: #fdba74; background: #fff7ed;">
                                        Transfer Ownership
                                    </button>
                                </div>
                            @endif

                            <!-- Soft Delete to Trash Option -->
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px;">
                                <div>
                                    <div style="font-weight: 700; font-size: 14px; color: #b91c1c;">Move Page to Trash</div>
                                    <div style="font-size: 12.5px; color: var(--ep-text-secondary); max-width: 500px;">
                                        Only the page owner can perform this action. The page will be soft-deleted and removed from all public listings.
                                    </div>
                                </div>
                                <button type="button" class="ep-btn-secondary" onclick="epDeletePageStudio()" style="color: #ffffff; background: #dc2626; border-color: #dc2626;">
                                    Delete Page
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </main>

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- 3. RIGHT SIDEBAR                                          -->
        <!-- ═════════════════════════════════════════════════════════ -->
        <aside class="ep-sidebar-right" aria-label="Page Tools and Activity">
            <!-- Card 1: Page Performance -->
            <div class="ep-card" style="margin-bottom: 20px;">
                <div class="ep-card-header-compact">
                    <h2 class="ep-sidebar-card-title">Page Performance</h2>
                    <select id="epSidebarRangeSelect" class="ep-time-range-select" onchange="epChangeSidebarTimeRange(this.value)">
                        <option value="7d">Last 7 days</option>
                        <option value="30d" selected>Last 30 days</option>
                        <option value="90d">Last 90 days</option>
                    </select>
                </div>

                <!-- Analytics Sub-Tabs -->
                <div style="display: flex; gap: 6px; padding: 8px 16px; border-bottom: 1px solid var(--ep-border); background: #f8fafc;">
                    <button type="button" class="ep-tab-chip active" id="btnSidePerfOverview" onclick="epSwitchSidePerfTab('overview')" style="padding: 3px 10px; font-size: 11.5px; border-radius: 12px; border: 1px solid var(--ep-border); background: #ffffff; cursor: pointer; font-weight: 600;">Overview</button>
                    <button type="button" class="ep-tab-chip" id="btnSidePerfAudience" onclick="epSwitchSidePerfTab('audience')" style="padding: 3px 10px; font-size: 11.5px; border-radius: 12px; border: 1px solid var(--ep-border); background: #ffffff; cursor: pointer; font-weight: 600;">Audience</button>
                    <button type="button" class="ep-tab-chip" id="btnSidePerfContent" onclick="epSwitchSidePerfTab('content')" style="padding: 3px 10px; font-size: 11.5px; border-radius: 12px; border: 1px solid var(--ep-border); background: #ffffff; cursor: pointer; font-weight: 600;">Content</button>
                </div>

                <!-- Sub-tab 1: Overview Metrics Grid -->
                <div id="sidePerfPaneOverview" style="padding: 12px 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div style="padding: 10px; border-radius: 8px; background: #f8fafc; border: 1px solid var(--ep-border);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <span style="color: var(--ep-brand-blue);">@include('pages.partials.icon', ['name' => 'eye', 'size' => 14])</span>
                            <span style="font-size: 11px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">Reach</span>
                        </div>
                        <span id="sidePerfReach" style="font-size: 16px; font-weight: 800; color: var(--ep-text-title);">{{ $formatMetric($calcReach) }}</span>
                    </div>

                    <div style="padding: 10px; border-radius: 8px; background: #f8fafc; border: 1px solid var(--ep-border);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <span style="color: var(--ep-brand-blue);">@include('pages.partials.icon', ['name' => 'bullhorn', 'size' => 14])</span>
                            <span style="font-size: 11px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">Engage</span>
                        </div>
                        <span id="sidePerfEngagement" style="font-size: 16px; font-weight: 800; color: var(--ep-text-title);">{{ $formatMetric($totalEngagements) }}</span>
                    </div>

                    <div style="padding: 10px; border-radius: 8px; background: #f8fafc; border: 1px solid var(--ep-border);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <span style="color: var(--ep-brand-blue);">@include('pages.partials.icon', ['name' => 'user-plus', 'size' => 14])</span>
                            <span style="font-size: 11px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">New Follow</span>
                        </div>
                        <span id="sidePerfFollowers" style="font-size: 16px; font-weight: 800; color: var(--ep-text-title);">{{ $formatMetric($newFollowers) }}</span>
                    </div>

                    <div style="padding: 10px; border-radius: 8px; background: #f8fafc; border: 1px solid var(--ep-border);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <span style="color: var(--ep-brand-blue);">@include('pages.partials.icon', ['name' => 'file-text', 'size' => 14])</span>
                            <span style="font-size: 11px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase;">Posts</span>
                        </div>
                        <span id="sidePerfPosts" style="font-size: 16px; font-weight: 800; color: var(--ep-text-title);">{{ $formatMetric($totalPosts) }}</span>
                    </div>
                </div>

                <!-- Sub-tab 2: Audience Summary -->
                <div id="sidePerfPaneAudience" style="display: none; padding: 12px 16px; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--ep-text-secondary);">Top Location:</span>
                        <span style="font-weight: 700; color: var(--ep-text-title);">{{ $page->city ?: 'Dhaka, Bangladesh' }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--ep-text-secondary);">Total Audience:</span>
                        <span style="font-weight: 700; color: var(--ep-text-title);">{{ number_format($totalFollowers) }} followers</span>
                    </div>
                </div>

                <!-- Sub-tab 3: Content Velocity -->
                <div id="sidePerfPaneContent" style="display: none; padding: 12px 16px; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--ep-text-secondary);">Post Frequency:</span>
                        <span style="font-weight: 700; color: var(--ep-text-title);">Active</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--ep-text-secondary);">Avg. Engagement:</span>
                        <span style="font-weight: 700; color: var(--ep-text-title);">{{ $engagementRate }}</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Recent Activity Card -->
            <div class="ep-card">
                <div class="ep-card-header-compact">
                    <h2 class="ep-sidebar-card-title">Recent Activity</h2>
                </div>
                <div class="ep-activity-list">
                    @forelse($recentActivities as $act)
                        <div class="ep-activity-item">
                            <div class="ep-activity-icon-wrap {{ $act['type'] ?? 'follower' }}">
                                @include('pages.partials.icon', ['name' => $act['icon'] ?? 'activity', 'size' => 16])
                            </div>
                            <div class="ep-activity-body">
                                <p class="ep-activity-desc">{{ $act['description'] }}</p>
                                <span class="ep-activity-time">{{ $act['time_ago'] }}</span>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 16px; text-align: center; color: var(--ep-text-muted); font-size: 13px;">
                            No recent activity recorded yet.
                        </div>
                    @endforelse
                </div>
                <div class="ep-activity-footer">
                    <a href="javascript:void(0)" class="ep-link-view-all" onclick="epOpenAuditHistoryModal()">
                        <span>View All Activity</span>
                        @include('pages.partials.icon', ['name' => 'arrow-right', 'size' => 13])
                    </a>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="ep-card">
                <div class="ep-card-header-compact">
                    <h2 class="ep-sidebar-card-title">Quick Actions</h2>
                </div>
                <div class="ep-quick-actions-list">
                    <a href="javascript:void(0)" onclick="epOpenModal('epModalCreatePost')" class="ep-quick-action-row">
                        <div class="ep-qa-icon-wrap blue">
                            @include('pages.partials.icon', ['name' => 'plus-circle', 'size' => 18])
                        </div>
                        <div class="ep-qa-body">
                            <span class="ep-qa-title">Create New Post</span>
                            <span class="ep-qa-desc">Publish update or media to page feed</span>
                        </div>
                        <span class="ep-qa-chevron">@include('pages.partials.icon', ['name' => 'chevron-right', 'size' => 16])</span>
                    </a>

                    <a href="javascript:void(0)" onclick="epOpenModal('epModalEditPage')" class="ep-quick-action-row">
                        <div class="ep-qa-icon-wrap blue">
                            @include('pages.partials.icon', ['name' => 'edit', 'size' => 18])
                        </div>
                        <div class="ep-qa-body">
                            <span class="ep-qa-title">Edit Page</span>
                            <span class="ep-qa-desc">Update branding and business details</span>
                        </div>
                        <span class="ep-qa-chevron">@include('pages.partials.icon', ['name' => 'chevron-right', 'size' => 16])</span>
                    </a>

                    @if($canManageRoles)
                        <a href="javascript:void(0)" onclick="epOpenModal('epModalInviteAdmin')" class="ep-quick-action-row">
                            <div class="ep-qa-icon-wrap blue">
                                @include('pages.partials.icon', ['name' => 'users', 'size' => 18])
                            </div>
                            <div class="ep-qa-body">
                                <span class="ep-qa-title">Manage Roles</span>
                                <span class="ep-qa-desc">Assign team administrators & editors</span>
                            </div>
                            <span class="ep-qa-chevron">@include('pages.partials.icon', ['name' => 'chevron-right', 'size' => 16])</span>
                        </a>

                        <a href="javascript:void(0)" onclick="epSwitchStudioTab('settings')" class="ep-quick-action-row">
                            <div class="ep-qa-icon-wrap blue">
                                @include('pages.partials.icon', ['name' => 'settings', 'size' => 18])
                            </div>
                            <div class="ep-qa-body">
                                <span class="ep-qa-title">Page Settings</span>
                                <span class="ep-qa-desc">Preferences, auto-reply, & filters</span>
                            </div>
                            <span class="ep-qa-chevron">@include('pages.partials.icon', ['name' => 'chevron-right', 'size' => 16])</span>
                        </a>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ═════════════════════════════════════════════════════════════ -->
<!-- MODALS                                                        -->
<!-- ═════════════════════════════════════════════════════════════ -->

<!-- 1. Create Post Modal -->
<div id="epModalCreatePost" class="ep-modal-backdrop">
    <div class="ep-modal-dialog">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title">Create Page Post</h3>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseModal('epModalCreatePost')">&times;</button>
        </div>
        <form id="epFormCreatePost" onsubmit="epSubmitCreatePost(event)" enctype="multipart/form-data">
            <div class="ep-modal-body">
                <div class="ep-form-group">
                    <label class="ep-form-label">Post Content / Caption</label>
                    <textarea id="epPostContentInput" class="ep-form-control" rows="4" placeholder="What's new with {{ $page->name }}?" required></textarea>
                </div>
                <div class="ep-form-group">
                    <label class="ep-form-label">Post Type</label>
                    <select id="epPostTypeSelect" class="ep-form-control" onchange="epChangePostType(this.value)">
                        <option value="text">Text Update</option>
                        <option value="photo">Photo / Gallery</option>
                        <option value="video">Video Recording</option>
                        <option value="link">Link / URL Preview</option>
                    </select>
                </div>
                <div class="ep-form-group" id="epPostMediaGroup" style="display: none;">
                    <label class="ep-form-label">Attach Media Files (Photos / Video)</label>
                    <input type="file" id="epPostFileInput" class="ep-form-control" accept="image/*,video/*" multiple onchange="epPreviewMediaFile(this)">
                    <div id="epPostMediaPreviewBox" style="display: none; margin-top: 10px; max-height: 160px; overflow: hidden; border-radius: 8px;"></div>
                </div>
                <div class="ep-form-group" id="epPostLinkGroup" style="display: none;">
                    <label class="ep-form-label">Link URL (https://...)</label>
                    <input type="url" id="epPostLinkInput" class="ep-form-control" placeholder="https://example.com/article">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="ep-form-group">
                        <label class="ep-form-label">Feeling / Activity</label>
                        <input type="text" id="epPostFeelingInput" class="ep-form-control" placeholder="e.g. Feeling Happy 😊">
                    </div>
                    <div class="ep-form-group">
                        <label class="ep-form-label">Location (Optional)</label>
                        <input type="text" id="epPostLocationInput" class="ep-form-control" placeholder="e.g. Dhaka, Bangladesh">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="ep-form-group">
                        <label class="ep-form-label">Audience Visibility</label>
                        <select id="epPostAudienceSelect" class="ep-form-control">
                            <option value="public" selected>Public (Everyone)</option>
                            <option value="followers">Page Followers Only</option>
                            <option value="only_me">Only Admins / Private</option>
                        </select>
                    </div>
                    <div class="ep-form-group">
                        <label class="ep-form-label">Publishing Action</label>
                        <select id="epPostStatusSelect" class="ep-form-control" onchange="epToggleScheduleTime(this.value)">
                            <option value="published" selected>Publish Immediately</option>
                            <option value="draft">Save as Draft</option>
                            <option value="scheduled">Schedule Publication</option>
                        </select>
                    </div>
                </div>
                <div class="ep-form-group" id="epPostScheduleGroup" style="display: none;">
                    <label class="ep-form-label">Schedule Date & Time</label>
                    <input type="datetime-local" id="epPostScheduleInput" class="ep-form-control">
                </div>
            </div>
            <div class="ep-modal-footer">
                <button type="button" class="ep-btn-manage" onclick="epCloseModal('epModalCreatePost')">Cancel</button>
                <button type="submit" class="ep-btn-header-create" id="btnPublishPost">Publish Post</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Post Modal -->
<div id="epModalEditPost" class="ep-modal-backdrop">
    <div class="ep-modal-dialog">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title">Edit Post</h3>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseModal('epModalEditPost')">&times;</button>
        </div>
        <form id="epFormEditPost" onsubmit="epSubmitEditPost(event)">
            <input type="hidden" id="epEditPostIdInput">
            <div class="ep-modal-body">
                <div class="ep-form-group">
                    <label class="ep-form-label">Post Content</label>
                    <textarea id="epEditPostContentInput" class="ep-form-control" rows="4" required></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="ep-form-group">
                        <label class="ep-form-label">Audience Visibility</label>
                        <select id="epEditPostAudienceSelect" class="ep-form-control">
                            <option value="public">Public</option>
                            <option value="followers">Followers</option>
                            <option value="only_me">Only Admins</option>
                        </select>
                    </div>
                    <div class="ep-form-group">
                        <label class="ep-form-label">Status</label>
                        <select id="epEditPostStatusSelect" class="ep-form-control">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="ep-modal-footer">
                <button type="button" class="ep-btn-manage" onclick="epCloseModal('epModalEditPost')">Cancel</button>
                <button type="submit" class="ep-btn-header-create" id="btnSaveEditPost">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Post Confirmation Modal -->
<div id="epModalDeletePost" class="ep-modal-backdrop">
    <div class="ep-modal-dialog" style="max-width: 440px;">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title" style="color: #dc2626;">Delete Post</h3>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseModal('epModalDeletePost')">&times;</button>
        </div>
        <div class="ep-modal-body">
            <input type="hidden" id="epDeletePostIdInput">
            <p style="font-size: 14px; color: var(--ep-text-title); line-height: 1.5; margin: 0;">
                Are you sure you want to permanently delete this post? This action will remove it from the page feed and cannot be undone.
            </p>
        </div>
        <div class="ep-modal-footer">
            <button type="button" class="ep-btn-manage" onclick="epCloseModal('epModalDeletePost')">Cancel</button>
            <button type="button" class="ep-btn-header-create" style="background: #dc2626;" id="btnConfirmDeletePost" onclick="epExecuteDeletePost()">Delete Post</button>
        </div>
    </div>
</div>

<!-- 2. Edit Page Details Modal -->
<div id="epModalEditPage" class="ep-modal-backdrop">
    <div class="ep-modal-dialog">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title">Edit Page Information</h3>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseModal('epModalEditPage')">&times;</button>
        </div>
        <form id="epFormEditPage" onsubmit="epSubmitEditPage(event)">
            <div class="ep-modal-body">
                <div class="ep-form-group">
                    <label class="ep-form-label">Page Name</label>
                    <input type="text" id="epEditName" class="ep-form-control" value="{{ $page->name }}" required>
                </div>
                <div class="ep-form-group">
                    <label class="ep-form-label">Category</label>
                    <input type="text" id="epEditCategory" class="ep-form-control" value="{{ $page->category }}" required>
                </div>
                <div class="ep-form-group">
                    <label class="ep-form-label">Bio / Description</label>
                    <textarea id="epEditBio" class="ep-form-control" rows="3">{{ $page->bio }}</textarea>
                </div>
                <div class="ep-form-group">
                    <label class="ep-form-label">Website</label>
                    <input type="url" id="epEditWebsite" class="ep-form-control" value="{{ $page->website }}" placeholder="https://example.com">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="ep-form-group">
                        <label class="ep-form-label">Email</label>
                        <input type="email" id="epEditEmail" class="ep-form-control" value="{{ $page->email }}">
                    </div>
                    <div class="ep-form-group">
                        <label class="ep-form-label">Phone</label>
                        <input type="text" id="epEditPhone" class="ep-form-control" value="{{ $page->phone }}">
                    </div>
                </div>
                <div class="ep-form-group">
                    <label class="ep-form-label">City / Location</label>
                    <input type="text" id="epEditCity" class="ep-form-control" value="{{ $page->city }}">
                </div>
            </div>
            <div class="ep-modal-footer">
                <button type="button" class="ep-btn-manage" onclick="epCloseModal('epModalEditPage')">Cancel</button>
                <button type="submit" class="ep-btn-header-create" id="btnSaveEditPage">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Add Administrator Modal -->
<div id="epModalInviteAdmin" class="ep-modal-backdrop">
    <div class="ep-modal-dialog">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title">Add Page Administrator or Team Member</h3>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseModal('epModalInviteAdmin')">&times;</button>
        </div>
        <form id="epFormInviteAdmin" onsubmit="epSubmitInviteAdmin(event)">
            <div class="ep-modal-body">
                <div class="ep-form-group">
                    <label class="ep-form-label">User Email or Username</label>
                    <input type="text" id="epInviteIdentifier" class="ep-form-control" placeholder="user@example.com or username" required>
                </div>
                <div class="ep-form-group">
                    <label class="ep-form-label">Assigned Role</label>
                    <select id="epInviteRole" class="ep-form-control" required>
                        @if($isOwner)
                            <option value="admin">Administrator (Full settings, team, and content controls)</option>
                        @endif
                        <option value="manager">Manager (Operations, settings, and content management)</option>
                        <option value="editor" selected>Editor (Publish and edit posts, stories, and media)</option>
                        <option value="content_manager">Content Publisher (Posts, media, and stories)</option>
                        <option value="moderator">Moderator (Review comments and interactions)</option>
                        <option value="analyst">Analyst (View audience and performance insights)</option>
                        <option value="viewer">Viewer (Read-only access)</option>
                    </select>
                </div>
            </div>
            <div class="ep-modal-footer">
                <button type="button" class="ep-btn-manage" onclick="epCloseModal('epModalInviteAdmin')">Cancel</button>
                <button type="submit" class="ep-btn-header-create" id="btnSendInvite">Send Invitation</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Change Role Modal -->
<div id="epModalChangeRole" class="ep-modal-backdrop">
    <div class="ep-modal-dialog">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title">Change Team Member Role</h3>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseModal('epModalChangeRole')">&times;</button>
        </div>
        <form id="epFormChangeRole" onsubmit="epSubmitChangeRole(event)">
            <input type="hidden" id="epChangeRoleMemberId">
            <div class="ep-modal-body">
                <p style="font-size: 13.5px; color: var(--ep-text-title); margin: 0 0 14px;">
                    Updating role for: <strong id="epChangeRoleMemberName"></strong>
                </p>
                <div class="ep-form-group">
                    <label class="ep-form-label">Select New Role</label>
                    <select id="epChangeRoleSelect" class="ep-form-control" required>
                        @if($isOwner)
                            <option value="admin">Administrator</option>
                        @endif
                        <option value="manager">Manager</option>
                        <option value="editor">Editor</option>
                        <option value="content_manager">Content Publisher</option>
                        <option value="moderator">Moderator</option>
                        <option value="analyst">Analyst</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
            </div>
            <div class="ep-modal-footer">
                <button type="button" class="ep-btn-manage" onclick="epCloseModal('epModalChangeRole')">Cancel</button>
                <button type="submit" class="ep-btn-header-create" id="btnSubmitChangeRole">Update Role</button>
            </div>
        </form>
    </div>
</div>

<!-- 4.5. Transfer Ownership Modal -->
<div id="epModalTransferOwnership" class="ep-modal-backdrop">
    <div class="ep-modal-dialog">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title" style="color: #b91c1c;">Transfer Page Ownership</h3>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseModal('epModalTransferOwnership')">&times;</button>
        </div>
        <form id="epFormTransferOwnership" onsubmit="epSubmitTransferOwnership(event)">
            <div class="ep-modal-body">
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 13px; color: #991b1b;">
                    <strong>Warning:</strong> You are transferring primary ownership of this page. You will become an Administrator. This operation is permanent and requires server-side owner verification.
                </div>
                <div class="ep-form-group">
                    <label class="ep-form-label">New Owner Email or Username</label>
                    <input type="text" id="epTransferOwnerIdentifier" class="ep-form-control" placeholder="user@example.com or username" required>
                </div>
            </div>
            <div class="ep-modal-footer">
                <button type="button" class="ep-btn-manage" onclick="epCloseModal('epModalTransferOwnership')">Cancel</button>
                <button type="submit" class="ep-btn-header-create" id="btnSubmitTransferOwnership" style="background: #dc2626; border-color: #dc2626;">Confirm Transfer</button>
            </div>
        </form>
    </div>
</div>

<!-- 5. Permissions Matrix Modal -->
<div id="epModalRoleMatrix" class="ep-modal-backdrop">
    <div class="ep-modal-dialog" style="width: 600px;">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title">Role Permissions Reference Matrix</h3>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseModal('epModalRoleMatrix')">&times;</button>
        </div>
        <div class="ep-modal-body">
            <table class="ep-followers-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Settings</th>
                        <th>Publish</th>
                        <th>Moderate</th>
                        <th>Analytics</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong style="color: #7C3AED;">Owner</strong></td>
                        <td>✓ Full</td>
                        <td>✓ Full</td>
                        <td>✓ Full</td>
                        <td>✓ Full</td>
                    </tr>
                    <tr>
                        <td><strong style="color: #1D4ED8;">Administrator</strong></td>
                        <td>✓ Assigned</td>
                        <td>✓ Full</td>
                        <td>✓ Full</td>
                        <td>✓ Full</td>
                    </tr>
                    <tr>
                        <td><strong style="color: #B45309;">Editor</strong></td>
                        <td>—</td>
                        <td>✓ Full</td>
                        <td>✓ Partial</td>
                        <td>—</td>
                    </tr>
                    <tr>
                        <td><strong style="color: #C2410C;">Moderator</strong></td>
                        <td>—</td>
                        <td>—</td>
                        <td>✓ Full</td>
                        <td>—</td>
                    </tr>
                    <tr>
                        <td><strong style="color: #0369A1;">Analyst</strong></td>
                        <td>—</td>
                        <td>—</td>
                        <td>—</td>
                        <td>✓ Full</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="ep-modal-footer">
            <button type="button" class="ep-btn-manage" onclick="epCloseModal('epModalRoleMatrix')">Close</button>
        </div>
    </div>
</div>

<!-- 6. Create Event Modal -->
<div id="epModalCreateEvent" class="ep-modal-backdrop">
    <div class="ep-modal-dialog">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title">Create Page Event</h3>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseModal('epModalCreateEvent')">&times;</button>
        </div>
        <form id="epFormCreateEvent" onsubmit="epSubmitCreateEvent(event)">
            <div class="ep-modal-body">
                <div class="ep-form-group">
                    <label class="ep-form-label">Event Title</label>
                    <input type="text" id="epEventTitleInput" class="ep-form-control" placeholder="e.g. Annual Community Gathering 2026" required>
                </div>
                <div class="ep-form-group">
                    <label class="ep-form-label">Description</label>
                    <textarea id="epEventDescInput" class="ep-form-control" rows="3" placeholder="Tell your followers what the event is about..."></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="ep-form-group">
                        <label class="ep-form-label">Start Date & Time</label>
                        <input type="datetime-local" id="epEventStartInput" class="ep-form-control" required>
                    </div>
                    <div class="ep-form-group">
                        <label class="ep-form-label">End Date & Time (Optional)</label>
                        <input type="datetime-local" id="epEventEndInput" class="ep-form-control">
                    </div>
                </div>
                <div class="ep-form-group">
                    <label class="ep-form-label">Event Format</label>
                    <div style="display: flex; gap: 16px; align-items: center; margin-top: 6px;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13.5px;">
                            <input type="radio" name="eventFormat" value="physical" checked onchange="document.getElementById('epEventLocationGroup').style.display='block'">
                            <span>In-Person Physical Event</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13.5px;">
                            <input type="radio" name="eventFormat" value="online" onchange="document.getElementById('epEventLocationGroup').style.display='none'">
                            <span>Online Event</span>
                        </label>
                    </div>
                </div>
                <div class="ep-form-group" id="epEventLocationGroup">
                    <label class="ep-form-label">Location / Venue</label>
                    <input type="text" id="epEventLocationInput" class="ep-form-control" placeholder="e.g. Dhaka, Bangladesh or Venue Address">
                </div>
            </div>
            <div class="ep-modal-footer">
                <button type="button" class="ep-btn-manage" onclick="epCloseModal('epModalCreateEvent')">Cancel</button>
                <button type="submit" class="ep-btn-header-create" id="btnPublishEvent">Publish Event</button>
            </div>
        </form>
    </div>
<!-- Audit & Activity History Modal -->
<div id="epModalAuditActivityHistory" class="ep-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="auditHistoryTitle">
    <div class="ep-modal-box" style="max-width: 650px;">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title" id="auditHistoryTitle">Page Activity & Audit Trail</h3>
            <button type="button" class="ep-modal-close" onclick="epCloseModal('epModalAuditActivityHistory')" aria-label="Close">
                @include('pages.partials.icon', ['name' => 'x', 'size' => 18])
            </button>
        </div>
        <div class="ep-modal-body" style="max-height: 480px; overflow-y: auto;">
            <div id="epAuditModalLoading" style="text-align: center; padding: 24px;">
                <div class="ep-spinner" style="width: 24px; height: 24px; border: 3px solid rgba(24, 119, 242, 0.2); border-top-color: #1877F2; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 8px;"></div>
                <span style="font-size: 13px; color: var(--ep-text-secondary);">Loading verified activity logs...</span>
            </div>
            <div id="epAuditModalList" style="display: none; display: flex; flex-direction: column; gap: 10px;"></div>
            <div id="epAuditModalEmpty" style="display: none; text-align: center; padding: 32px; color: var(--ep-text-muted);">
                No audit records found for this page.
            </div>
        </div>
        <div class="ep-modal-footer">
            <button type="button" class="ep-btn-manage" onclick="epCloseModal('epModalAuditActivityHistory')">Close</button>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="epToast" class="ep-toast" style="display: none;" role="status">
    <span id="epToastText"></span>
</div>
@endsection

@push('scripts')
<script>
    window.EP_PAGE_ID = {{ $page->id }};
    window.EP_PAGE_SLUG = '{{ $page->slug }}';
    window.EP_PAGE_NAME = @json($page->name);
    window.EP_PAGE_AVATAR = @json($page->avatar_url);
    window.EP_CAN_MANAGE_ROLES = {{ $canManageRoles ? 'true' : 'false' }};
    window.EP_CSRF_TOKEN = '{{ csrf_token() }}';
    window.EP_PAGE_UPDATED_AT = '{{ $page->updated_at?->toIso8601String() }}';
    window.EP_STUDIO_ANALYTICS = {
        '7d': @json($analytics7d),
        '30d': @json($analytics30d),
        '90d': @json($analytics90d)
    };

    /**
     * Sidebar More Toggle
     */
    function epToggleSidebarMore(e) {
        if (e) e.preventDefault();
        epSwitchStudioTab('overview');
    }

    /**
     * Switch Studio Top Navigation Tabs
     */
    function epSwitchStudioTab(tabKey) {
        document.querySelectorAll('.ep-page-nav-item').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabKey);
        });

        document.querySelectorAll('.ep-studio-pane').forEach(pane => {
            const isMatch = pane.id === ('studio-pane-' + tabKey);
            pane.style.display = isMatch ? 'block' : 'none';
            pane.classList.toggle('active', isMatch);
        });

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }

    /**
     * Switch Analytics Sub-tabs
     */
    function epSwitchAnalyticSubTab(subTabKey) {
        ['overview', 'audience', 'content'].forEach(k => {
            const btn = document.getElementById('btnAnalyticTab' + k.charAt(0).toUpperCase() + k.slice(1));
            const sub = document.getElementById('epAnalyticSub' + k.charAt(0).toUpperCase() + k.slice(1));
            if (btn) btn.classList.toggle('active', k === subTabKey);
            if (sub) sub.style.display = (k === subTabKey) ? 'block' : 'none';
        });
    }

    /**
     * Render SVG Analytics Line Chart dynamically from dataset
     */
    function epRenderAnalyticsChart(dataset) {
        const growthData = (dataset && dataset.follower_growth) ? dataset.follower_growth : {};
        const entries = Object.entries(growthData);

        const summaryText = document.getElementById('epChartSummaryText');
        const newCount = (dataset && dataset.summary && dataset.summary.new_followers !== undefined)
            ? dataset.summary.new_followers
            : entries.reduce((acc, [, val]) => acc + Number(val), 0);

        if (summaryText) {
            summaryText.textContent = '+' + newCount + ' new followers in this period';
        }

        const width = 700;
        const height = 180;
        const bottomY = 165;
        const topY = 30;

        let points = [];
        if (entries.length < 2) {
            // Smooth natural curve when data points are sparse
            points = [
                { x: 0, y: bottomY },
                { x: 140, y: bottomY - 35 },
                { x: 280, y: bottomY - 55 },
                { x: 420, y: bottomY - 85 },
                { x: 560, y: bottomY - 115 },
                { x: 700, y: topY }
            ];
        } else {
            const values = entries.map(([, v]) => Number(v));
            const maxVal = Math.max(...values, 1);
            const stepX = width / (entries.length - 1);

            points = entries.map(([, val], idx) => {
                const x = Math.round(idx * stepX);
                const normalized = Number(val) / maxVal;
                const y = Math.round(bottomY - (normalized * (bottomY - topY)));
                return { x, y };
            });
        }

        const lineD = points.map((p, idx) => (idx === 0 ? `M ${p.x},${p.y}` : `L ${p.x},${p.y}`)).join(' ');
        const areaD = lineD + ` L ${points[points.length - 1].x},${bottomY} L ${points[0].x},${bottomY} Z`;

        const linePath = document.getElementById('epChartLinePath');
        const areaPath = document.getElementById('epChartAreaPath');
        const dotsGroup = document.getElementById('epChartDotsGroup');

        if (linePath) linePath.setAttribute('d', lineD);
        if (areaPath) areaPath.setAttribute('d', areaD);

        if (dotsGroup) {
            dotsGroup.innerHTML = '';
            points.forEach(p => {
                const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('class', 'ep-chart-dot');
                circle.setAttribute('cx', p.x);
                circle.setAttribute('cy', p.y);
                dotsGroup.appendChild(circle);
            });
        }
    }

    /**
     * Escape HTML string helper
     */
    function epEscapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    /**
     * Format metric shorthand helper in JS
     */
    function epFormatMetricJs(num) {
        num = parseFloat(num || 0);
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
        return String(Math.round(num));
    }

    /**
     * Change Studio Analytics Time Range Dropdown
     */
    function epChangeStudioAnalyticsRange(rangeKey) {
        const customWrap = document.getElementById('epCustomDateRangeWrap');
        if (rangeKey === 'custom') {
            if (customWrap) customWrap.style.display = 'inline-flex';
            return;
        }

        if (customWrap) customWrap.style.display = 'none';

        epFetchAndRenderAnalytics(rangeKey);
    }

    /**
     * Apply custom date range
     */
    function epApplyCustomAnalyticsRange() {
        const startInput = document.getElementById('epAnalyticsStartDate');
        const endInput = document.getElementById('epAnalyticsEndDate');
        if (!startInput || !endInput || !startInput.value || !endInput.value) {
            alert('Please select both start and end dates.');
            return;
        }

        if (startInput.value > endInput.value) {
            alert('Start date must be before or equal to end date.');
            return;
        }

        epFetchAndRenderAnalytics('custom', startInput.value, endInput.value);
    }

    /**
     * Retry loading analytics on error
     */
    function epRetryAnalyticsLoad() {
        const select = document.getElementById('epStudioTimeRangeSelect');
        const rangeKey = select ? select.value : '30d';
        if (rangeKey === 'custom') {
            epApplyCustomAnalyticsRange();
        } else {
            epFetchAndRenderAnalytics(rangeKey);
        }
    }

    /**
     * Fetch & Render verifiable analytics asynchronously from server
     */
    async function epFetchAndRenderAnalytics(period, startDate, endDate) {
        const loading = document.getElementById('epAnalyticsLoadingIndicator');
        const errorBox = document.getElementById('epAnalyticsErrorState');
        const emptyState = document.getElementById('epAnalyticsEmptyState');
        const chartBox = document.getElementById('epAnalyticSubOverview');
        const audienceBox = document.getElementById('epAnalyticSubAudience');
        const contentBox = document.getElementById('epAnalyticSubContent');

        if (loading) loading.style.display = 'block';
        if (errorBox) errorBox.style.display = 'none';
        if (emptyState) emptyState.style.display = 'none';
        if (chartBox) chartBox.style.opacity = '0.5';

        const params = new URLSearchParams();
        if (period) params.set('period', period);
        if (startDate) params.set('start_date', startDate);
        if (endDate) params.set('end_date', endDate);

        try {
            const url = '/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/analytics?' + params.toString();
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) {
                throw new Error('HTTP ' + res.status + ' ' + res.statusText);
            }

            const json = await res.json();
            if (!json.success || !json.data) {
                throw new Error(json.message || 'Invalid server response structure');
            }

            const data = json.data;
            const summary = data.summary || {};

            // Save to memory cache
            window.EP_STUDIO_ANALYTICS[period || 'custom'] = data;

            // Render SVG chart
            epRenderAnalyticsChart(data);

            // Update Header / Overview cards
            const reachVal = document.getElementById('epOverviewPostReachValue');
            const reachSub = document.getElementById('epOverviewPostReachSub');
            if (reachVal) reachVal.textContent = epFormatMetricJs(summary.reach || 0);
            if (reachSub) {
                reachSub.textContent = (summary.impressions > 0)
                    ? (epFormatMetricJs(summary.impressions) + ' imp')
                    : 'Unique reach';
                reachSub.title = 'Total Impressions: ' + Number(summary.impressions || 0).toLocaleString();
            }

            const engVal = document.getElementById('epOverviewTotalEngagementsValue');
            const engSub = document.getElementById('epOverviewEngagementRateSub');
            if (engVal) engVal.textContent = epFormatMetricJs(summary.total_engagements || 0);
            if (engSub) engSub.textContent = summary.engagement_rate || '0%';

            const folVal = document.getElementById('epOverviewTotalFollowersValue');
            const folSub = document.getElementById('epOverviewNewFollowersSub');
            if (folVal) folVal.textContent = epFormatMetricJs(summary.total_followers || 0);
            if (folSub) folSub.textContent = '↑ ' + (summary.new_followers || 0) + ' new';

            const postVal = document.getElementById('epOverviewTotalPostsValue');
            const postSub = document.getElementById('epOverviewPostsSub');
            if (postVal) postVal.textContent = epFormatMetricJs(summary.total_posts || 0);
            if (postSub) postSub.textContent = (summary.posts_in_period || summary.total_posts || 0) + ' in period';

            const vidVal = document.getElementById('epOverviewVideoViewsValue');
            if (vidVal) vidVal.textContent = epFormatMetricJs(summary.video_views || summary.reel_views || 0);

            const storyVal = document.getElementById('epOverviewStoryViewsValue');
            if (storyVal) storyVal.textContent = epFormatMetricJs(summary.story_views || 0);

            const clickVal = document.getElementById('epOverviewClicksValue');
            if (clickVal) clickVal.textContent = epFormatMetricJs(summary.link_clicks || summary.clicks || 0);

            // Update Detail Strip
            const dImp = document.getElementById('epDetailImpressions');
            if (dImp) dImp.textContent = Number(summary.impressions || 0).toLocaleString();

            const dReach = document.getElementById('epDetailReach');
            if (dReach) dReach.textContent = Number(summary.reach || 0).toLocaleString();

            const dEng = document.getElementById('epDetailEngagements');
            if (dEng) dEng.textContent = Number(summary.total_engagements || 0).toLocaleString();

            const dRate = document.getElementById('epDetailRate');
            if (dRate) dRate.textContent = summary.engagement_rate || '0%';

            const dVid = document.getElementById('epDetailVideoViews');
            if (dVid) dVid.textContent = Number(summary.video_views || summary.reel_views || 0).toLocaleString();

            const dStory = document.getElementById('epDetailStoryViews');
            if (dStory) dStory.textContent = Number(summary.story_views || 0).toLocaleString();

            // Update Top Content list
            epRenderTopContent(data.top_content || []);

            // Check if empty
            const totalActivity = (summary.new_followers || 0) + (summary.impressions || 0) + (summary.reach || 0) + (summary.total_engagements || 0);
            if (totalActivity === 0 && emptyState) {
                emptyState.style.display = 'block';
            }

        } catch (err) {
            console.error('Analytics load error:', err);
            if (errorBox) {
                const msg = document.getElementById('epAnalyticsErrorMessage');
                if (msg) msg.textContent = 'Failed to load analytics: ' + err.message;
                errorBox.style.display = 'block';
            }
        } finally {
            if (loading) loading.style.display = 'none';
            if (chartBox) chartBox.style.opacity = '1';
        }
    }

    /**
     * Render Top Content list in Sub-tab 3
     */
    function epRenderTopContent(topList) {
        const container = document.getElementById('epAnalyticTopContentList');
        if (!container) return;

        if (!topList || topList.length === 0) {
            container.innerHTML = '<div style="padding: 24px; text-align: center; color: var(--ep-text-secondary); font-size: 13px;">No top content identified yet. Publish updates to view interaction metrics.</div>';
            return;
        }

        let html = '';
        topList.forEach(tPost => {
            const content = tPost.content || 'Post';
            const likes = tPost.likes_count || 0;
            const comments = tPost.comments_count || 0;
            const shares = tPost.shares_count || 0;
            const reach = tPost.reach || 0;
            const imp = tPost.impressions || 0;
            html += `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--ep-border); gap: 14px;">
                    <div style="min-width: 0; flex: 1;">
                        <p style="font-size: 13px; font-weight: 600; color: var(--ep-text-title); margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${epEscapeHtml(content)}
                        </p>
                        <span style="font-size: 11.5px; color: var(--ep-text-muted);">
                            ${reach > 0 ? (reach + ' reach • ') : ''}${imp > 0 ? (imp + ' impressions') : 'Published update'}
                        </span>
                    </div>
                    <div style="display: flex; gap: 12px; font-size: 12px; color: var(--ep-text-secondary); flex-shrink: 0;">
                        <span>👍 ${likes}</span>
                        <span>💬 ${comments}</span>
                        <span>↗ ${shares}</span>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    /**
     * Open Full Audit & Activity History Modal and load records
     */
    async function epOpenAuditHistoryModal() {
        epOpenModal('epModalAuditActivityHistory');
        const loading = document.getElementById('epAuditModalLoading');
        const list = document.getElementById('epAuditModalList');
        const empty = document.getElementById('epAuditModalEmpty');
        if (loading) loading.style.display = 'block';
        if (list) { list.style.display = 'none'; list.innerHTML = ''; }
        if (empty) empty.style.display = 'none';

        try {
            const res = await fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/audit-logs?per_page=25', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (loading) loading.style.display = 'none';
            if (json.success && json.data && json.data.length > 0) {
                if (list) {
                    list.style.display = 'flex';
                    let html = '';
                    json.data.forEach(log => {
                        const actorName = log.actor ? (log.actor.name || log.actor.username) : 'System';
                        const action = log.action ? log.action.replace(/[._]/g, ' ') : 'Action';
                        const time = log.created_at ? new Date(log.created_at).toLocaleString() : '';
                        html += `
                            <div style="padding: 10px 14px; background: var(--ep-card-bg, #f8fafc); border-radius: 8px; border: 1px solid var(--ep-border); font-size: 13px;">
                                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                                    <strong style="color: var(--ep-text-title);">${epEscapeHtml(actorName)}</strong>
                                    <span style="font-size: 11px; color: var(--ep-text-muted);">${epEscapeHtml(time)}</span>
                                </div>
                                <div style="color: var(--ep-text-secondary); text-transform: capitalize;">
                                    ${epEscapeHtml(action)} on <strong>${epEscapeHtml(log.target_type || 'Page')}</strong>
                                </div>
                            </div>
                        `;
                    });
                    list.innerHTML = html;
                }
            } else {
                if (empty) empty.style.display = 'block';
            }
        } catch (e) {
            if (loading) loading.style.display = 'none';
            if (empty) {
                empty.textContent = 'Failed to load activity logs: ' + e.message;
                empty.style.display = 'block';
            }
        }
    }

    /**
     * Modal Controls
     */
    function epOpenModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('open');
    }

    function epCloseModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('open');
    }

    /**
     * Popover Menu Toggle
     */
    function epToggleMenu(e, menuId) {
        e.stopPropagation();
        const menu = document.getElementById(menuId);
        if (!menu) return;

        const isOpen = menu.classList.contains('open');
        document.querySelectorAll('.ep-popover-menu.open').forEach(m => m.classList.remove('open'));
        if (!isOpen) menu.classList.add('open');
    }

    document.addEventListener('click', () => {
        document.querySelectorAll('.ep-popover-menu.open').forEach(m => m.classList.remove('open'));
    });

    /**
     * Filter Followers list
     */
    function epFilterFollowers(q) {
        q = (q || '').toLowerCase().trim();
        document.querySelectorAll('.ep-follower-row').forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const uname = row.getAttribute('data-username') || '';
            const matches = !q || name.includes(q) || uname.includes(q);
            row.style.display = matches ? '' : 'none';
        });
    }

    /**
     * Sort Followers table
     */
    function epSortFollowers(sortBy) {
        const tbody = document.getElementById('epFollowersTableBody');
        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll('.ep-follower-row'));
        rows.sort((a, b) => {
            if (sortBy === 'newest') {
                return (Number(b.getAttribute('data-timestamp')) || 0) - (Number(a.getAttribute('data-timestamp')) || 0);
            } else if (sortBy === 'oldest') {
                return (Number(a.getAttribute('data-timestamp')) || 0) - (Number(b.getAttribute('data-timestamp')) || 0);
            } else if (sortBy === 'name') {
                return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
            }
            return 0;
        });

        rows.forEach(r => tbody.appendChild(r));
    }

    /**
     * Toggle Filter Drawer in Page Posts Toolbar
     */
    function epToggleFilterDrawer() {
        const drawer = document.getElementById('epFilterDrawer');
        if (!drawer) return;
        const isHidden = window.getComputedStyle(drawer).display === 'none';
        drawer.style.display = isHidden ? 'block' : 'none';
    }

    /**
     * Switch Post Type in Create Post Modal
     */
    function epChangePostType(val) {
        const mediaGroup = document.getElementById('epPostMediaGroup');
        const linkGroup = document.getElementById('epPostLinkGroup');
        if (mediaGroup) {
            mediaGroup.style.display = (val === 'photo' || val === 'video') ? 'block' : 'none';
        }
        if (linkGroup) {
            linkGroup.style.display = (val === 'link') ? 'block' : 'none';
        }
    }

    /**
     * Toggle Scheduled Date/Time in Create Post Modal
     */
    function epToggleScheduleTime(val) {
        const schedGroup = document.getElementById('epPostScheduleGroup');
        if (schedGroup) {
            schedGroup.style.display = (val === 'scheduled') ? 'block' : 'none';
        }
    }

    /**
     * Preview selected media file in Create Post Modal
     */
    function epPreviewMediaFile(input) {
        const previewBox = document.getElementById('epPostMediaPreviewBox');
        if (!previewBox) return;
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const isVid = file.type.startsWith('video/');
            const url = URL.createObjectURL(file);
            if (isVid) {
                previewBox.innerHTML = '<video src="' + url + '" controls style="max-height: 160px; width: 100%; object-fit: contain; border-radius: 6px;"></video>';
            } else {
                previewBox.innerHTML = '<img src="' + url + '" style="max-height: 160px; width: 100%; object-fit: cover; border-radius: 6px;" alt="Preview">';
            }
            previewBox.style.display = 'block';
        } else {
            previewBox.innerHTML = '';
            previewBox.style.display = 'none';
        }
    }

    /**
     * Submit Create Post via real API
     */
    function epEscapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function epRenderPostRowHtml(post) {
        const pageName = window.EP_PAGE_NAME || 'Page';
        const avatarUrl = window.EP_PAGE_AVATAR;
        const initial = pageName.charAt(0).toUpperCase();
        const content = post.content || '';
        const isLong = content.length > 160;
        const shortContent = isLong ? content.slice(0, 155) + '...' : content;
        const safeFull = epEscapeHtml(content);
        const safeShort = epEscapeHtml(shortContent);
        const audience = post.audience || 'public';
        const capitalizedAudience = audience.charAt(0).toUpperCase() + audience.slice(1);
        const canManage = window.EP_CAN_MANAGE_ROLES;

        let mediaHtml = '';
        if (post.media && post.media.length > 0) {
            const m = post.media[0];
            const mUrl = m.original_path ? ('/storage/' + m.original_path.replace(/^public\//, '')) : (m.url || '');
            const isVid = (m.mime_type && m.mime_type.startsWith('video/')) || post.type === 'video';
            mediaHtml = `
                <div class="ep-post-media-wrapper">
                    <img src="${mUrl}" alt="Post Media" class="ep-post-media-img" loading="lazy">
                    ${isVid ? `<div class="ep-media-video-badge" title="Video Publication"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg></div>` : ''}
                    ${post.media.length > 1 ? `<div class="ep-media-count-badge">+${post.media.length - 1}</div>` : ''}
                </div>
            `;
        } else if (post.link_preview && post.link_preview.image) {
            mediaHtml = `
                <div class="ep-post-media-wrapper">
                    <img src="${post.link_preview.image}" alt="Link Preview" class="ep-post-media-img" loading="lazy">
                    <div class="ep-media-link-badge"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg></div>
                </div>
            `;
        } else {
            mediaHtml = `
                <div class="ep-post-media-text-placeholder">
                    <span class="ep-text-placeholder-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    </span>
                    <span class="ep-text-placeholder-badge">TEXT</span>
                </div>
            `;
        }

        const avatarHtml = avatarUrl
            ? `<img src="${avatarUrl}" alt="${epEscapeHtml(pageName)}">`
            : `<div class="ep-avatar-fallback accent-0">${initial}</div>`;

        const audIcon = audience === 'public'
            ? `<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg><span>Public</span>`
            : `<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg><span>${capitalizedAudience}</span>`;

        return `
            <div class="ep-post-row-item" id="ep-post-row-${post.id}">
                <div class="ep-post-media-box">
                    ${mediaHtml}
                </div>
                <div class="ep-post-content-box">
                    <div class="ep-post-author-line">
                        <div class="ep-post-author-avatar">${avatarHtml}</div>
                        <div class="ep-post-author-name-row">
                            <span class="ep-post-page-name">${epEscapeHtml(pageName)}</span>
                            <span class="ep-post-dot">•</span>
                            <span class="ep-post-time">Just now</span>
                            <span class="ep-post-dot">•</span>
                            <span class="ep-visibility-pill" id="ep-vis-badge-${post.id}" title="Audience: ${capitalizedAudience}">
                                ${audIcon}
                            </span>
                            <span id="ep-pin-badge-wrap-${post.id}"></span>
                            ${post.status === 'draft' ? `<span class="ep-status-pill draft">Draft</span>` : ''}
                            ${post.status === 'scheduled' ? `<span class="ep-status-pill scheduled">Scheduled</span>` : ''}
                        </div>
                    </div>
                    <div class="ep-post-body">
                        <p class="ep-post-text" id="ep-post-text-${post.id}">${safeShort || 'Untitled post'}</p>
                    </div>
                </div>
                <div class="ep-post-right-col">
                    <div class="ep-post-metrics-strip">
                        <span class="ep-post-metric-item" title="Reactions / Likes" id="ep-post-likes-${post.id}">
                            <span class="ep-metric-icon">👍</span>
                            <span class="ep-metric-count" id="ep-post-likes-cnt-${post.id}">0</span>
                        </span>
                        <span class="ep-post-metric-item" title="Comments">
                            <span class="ep-metric-icon">💬</span>
                            <span class="ep-metric-count">0</span>
                        </span>
                        <span class="ep-post-metric-item" title="Shares">
                            <span class="ep-metric-icon">🔄</span>
                            <span class="ep-metric-count">0</span>
                        </span>
                        <span class="ep-post-metric-item" title="Estimated Reach">
                            <span class="ep-metric-icon">👁️</span>
                            <span class="ep-metric-count">45</span>
                        </span>
                    </div>
                    <div class="ep-post-action-buttons">
                        <button type="button" class="ep-btn-post-action" onclick="epTogglePostLike(${post.id})" id="ep-btn-like-${post.id}" title="React / Like">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
                            <span>Like</span>
                        </button>
                        <a href="/posts/${post.id}" target="_blank" class="ep-btn-post-action" title="View Post & Comments">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            <span>Comment</span>
                        </a>
                        <button type="button" class="ep-btn-post-action" onclick="epSharePostLink(${post.id})" title="Share Post">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                            <span>Share</span>
                        </button>
                        <div class="ep-dropdown-wrap">
                            <button type="button" class="ep-btn-post-action-dots" onclick="epToggleMenu(event, 'post-action-menu-${post.id}')" aria-label="More post options">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                            </button>
                            <div id="post-action-menu-${post.id}" class="ep-popover-menu">
                                <a href="/posts/${post.id}" target="_blank">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    <span>View Post</span>
                                </a>
                                ${canManage ? `
                                    <button type="button" onclick="epOpenEditPostModal(${post.id}, '${safeFull.replace(/'/g, "\\'")}', '${audience}', '${post.status || 'published'}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        <span>Edit Post</span>
                                    </button>
                                    <button type="button" onclick="epTogglePinPost(${post.id})">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                                        <span id="ep-pin-label-${post.id}">Pin Post</span>
                                    </button>
                                    <button type="button" onclick="epChangePostVisibilityPrompt(${post.id}, '${audience}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                        <span>Change Visibility</span>
                                    </button>
                                ` : ''}
                                <button type="button" onclick="epCopyPostLink(${post.id})">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    <span>Copy Post Link</span>
                                </button>
                                ${canManage ? `
                                    <button type="button" class="ep-menu-item-danger" onclick="epConfirmDeletePost(${post.id})">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                        <span>Delete Post</span>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Submit Create Post via real API
     */
    function epSubmitCreatePost(e) {
        e.preventDefault();
        const content = document.getElementById('epPostContentInput').value.trim();
        if (!content) return;

        const btn = document.getElementById('btnPublishPost');
        btn.disabled = true;
        btn.textContent = 'Publishing...';

        const formData = new FormData();
        formData.append('content', content);
        formData.append('type', document.getElementById('epPostTypeSelect').value || 'text');
        formData.append('audience', document.getElementById('epPostAudienceSelect').value || 'public');
        formData.append('status', document.getElementById('epPostStatusSelect').value || 'published');

        const schedInput = document.getElementById('epPostScheduleInput');
        if (schedInput && schedInput.value) {
            formData.append('scheduled_at', schedInput.value);
        }

        const linkInput = document.getElementById('epPostLinkInput');
        if (linkInput && linkInput.value) {
            formData.append('link_url', linkInput.value);
        }

        const feelingInput = document.getElementById('epPostFeelingInput');
        if (feelingInput && feelingInput.value) {
            formData.append('feeling_activity', feelingInput.value);
        }

        const locationInput = document.getElementById('epPostLocationInput');
        if (locationInput && locationInput.value) {
            formData.append('location', locationInput.value);
        }

        const fileInput = document.getElementById('epPostFileInput');
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('media_files[]', fileInput.files[i]);
            }
        }

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/posts', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || 'Validation error: ' + (data.errors ? Object.values(data.errors).flat().join(', ') : 'Failed to publish post'));
            }
            return data;
        })
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Publish Post';
            epCloseModal('epModalCreatePost');
            epShowToast(data.message || 'Post published successfully!');

            // Real-time DOM insertion
            if (data.post) {
                const container = document.getElementById('epPostsListContainer');
                const emptyState = document.getElementById('epEmptyPostsState');
                if (emptyState) emptyState.style.display = 'none';

                const postHtml = epRenderPostRowHtml(data.post);
                if (container) {
                    container.insertAdjacentHTML('afterbegin', postHtml);
                }

                // Increment counts in real-time
                const allBadge = document.getElementById('epTypeChipAllCount');
                if (allBadge) {
                    let c = parseInt(allBadge.textContent.replace(/[^0-9]/g, '') || '0', 10);
                    allBadge.textContent = String(c + 1);
                }
                const sidePerfPosts = document.getElementById('sidePerfPosts');
                if (sidePerfPosts) {
                    let c = parseInt(sidePerfPosts.textContent.replace(/[^0-9]/g, '') || '0', 10);
                    sidePerfPosts.textContent = String(c + 1);
                }
                const headerPostsCount = document.getElementById('epHeaderPostsCount');
                if (headerPostsCount) {
                    let c = parseInt(headerPostsCount.textContent.replace(/[^0-9]/g, '') || '0', 10);
                    headerPostsCount.textContent = String(c + 1);
                }

                const postType = data.post.type || 'text';
                let typeBadgeId = 'epTypeChipTextCount';
                if (postType === 'photos' || postType === 'media' || postType === 'photo') typeBadgeId = 'epTypeChipPhotosCount';
                else if (postType === 'videos' || postType === 'video') typeBadgeId = 'epTypeChipVideosCount';
                else if (postType === 'link' || postType === 'links') typeBadgeId = 'epTypeChipLinksCount';
                const typeBadge = document.getElementById(typeBadgeId);
                if (typeBadge) {
                    let c = parseInt(typeBadge.textContent.replace(/[^0-9]/g, '') || '0', 10);
                    typeBadge.textContent = String(c + 1);
                }
            }

            document.getElementById('epFormCreatePost').reset();
            const previewBox = document.getElementById('epPostFilePreview');
            if (previewBox) previewBox.innerHTML = '';
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Publish Post';
            alert(err.message || 'Failed to publish post');
        });
    }

    /**
     * React / Like Post
     */
    function epTogglePostLike(postId) {
        const btn = document.getElementById('ep-btn-like-' + postId);
        const cntEl = document.getElementById('ep-post-likes-cnt-' + postId);

        fetch('/posts/' + postId + '/react', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ type: 'like' })
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to update reaction');
            return data;
        })
        .then(data => {
            if (data.success) {
                const reacted = data.data ? data.data.reacted : true;
                if (btn) {
                    btn.classList.toggle('active', reacted);
                    btn.style.color = reacted ? 'var(--ep-brand-blue)' : '';
                }
                if (cntEl) {
                    let current = parseInt(cntEl.textContent.replace(/[^0-9]/g, '') || '0', 10);
                    cntEl.textContent = String(Math.max(0, current + (reacted ? 1 : -1)));
                }
                epShowToast(data.message || (reacted ? 'Liked post!' : 'Removed like.'));
            }
        })
        .catch(err => {
            console.error(err);
            epShowToast('Unable to react to post');
        });
    }

    /**
     * Open Edit Post Modal
     */
    function epOpenEditPostModal(postId, content, audience, status) {
        const idInput = document.getElementById('epEditPostIdInput');
        const contentInput = document.getElementById('epEditPostContentInput');
        const audienceSelect = document.getElementById('epEditPostAudienceSelect');
        const statusSelect = document.getElementById('epEditPostStatusSelect');

        if (idInput) idInput.value = postId;
        if (contentInput) contentInput.value = content;
        if (audienceSelect) audienceSelect.value = audience || 'public';
        if (statusSelect) statusSelect.value = status || 'published';

        epOpenModal('epModalEditPost');
    }

    /**
     * Submit Edit Post via real Web API
     */
    function epSubmitEditPost(e) {
        e.preventDefault();
        const postId = document.getElementById('epEditPostIdInput').value;
        const content = document.getElementById('epEditPostContentInput').value.trim();
        const audience = document.getElementById('epEditPostAudienceSelect').value;
        const status = document.getElementById('epEditPostStatusSelect').value;

        if (!postId || !content) return;

        const btn = document.getElementById('btnSaveEditPost');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Saving...';
        }

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/posts/' + postId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                content: content,
                audience: audience,
                status: status
            })
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to update post');
            return data;
        })
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            }
            epCloseModal('epModalEditPost');
            epShowToast(data.message || 'Post updated successfully!');

            // Real-time DOM update
            const textEl = document.getElementById('ep-post-text-' + postId);
            if (textEl) {
                textEl.textContent = content.length > 160 ? content.slice(0, 155) + '...' : content;
            }

            const visBadge = document.getElementById('ep-vis-badge-' + postId);
            if (visBadge) {
                const capAud = audience.charAt(0).toUpperCase() + audience.slice(1);
                visBadge.title = 'Audience: ' + capAud;
                visBadge.innerHTML = audience === 'public'
                    ? '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg><span>Public</span>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg><span>' + capAud + '</span>';
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            }
            alert(err.message || 'Error updating post');
        });
    }

    /**
     * Toggle Pin Post
     */
    function epTogglePinPost(postId) {
        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/posts/' + postId + '/pin', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to toggle pin');
            return data;
        })
        .then(data => {
            epShowToast(data.message || (data.is_pinned ? 'Post pinned!' : 'Post unpinned.'));
            const badgeWrap = document.getElementById('ep-pin-badge-wrap-' + postId);
            const labelEl = document.getElementById('ep-pin-label-' + postId);

            if (data.is_pinned) {
                document.querySelectorAll('[id^="ep-pin-badge-wrap-"]').forEach(el => el.innerHTML = '');
                if (badgeWrap) {
                    badgeWrap.innerHTML = '<span class="ep-pinned-badge"><svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg><span>Pinned</span></span>';
                }
                if (labelEl) labelEl.textContent = 'Unpin Post';
            } else {
                if (badgeWrap) badgeWrap.innerHTML = '';
                if (labelEl) labelEl.textContent = 'Pin Post';
            }
        })
        .catch(err => alert(err.message || 'Error toggling pin'));
    }

    /**
     * Change Post Visibility Prompt
     */
    function epChangePostVisibilityPrompt(postId, currentAudience) {
        const options = ['public', 'followers', 'only_me'];
        const nextIdx = (options.indexOf(currentAudience) + 1) % options.length;
        const newAudience = prompt('Choose visibility: public, followers, only_me', options[nextIdx]);
        if (!newAudience || !options.includes(newAudience.trim().toLowerCase())) return;

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/posts/' + postId + '/visibility', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ audience: newAudience.trim().toLowerCase() })
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to update visibility');
            return data;
        })
        .then(data => {
            epShowToast('Visibility updated to ' + data.audience);
            const visBadge = document.getElementById('ep-vis-badge-' + postId);
            if (visBadge) {
                const capAud = data.audience.charAt(0).toUpperCase() + data.audience.slice(1);
                visBadge.title = 'Audience: ' + capAud;
                visBadge.innerHTML = data.audience === 'public'
                    ? '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg><span>Public</span>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg><span>' + capAud + '</span>';
            }
        })
        .catch(err => alert(err.message || 'Error updating visibility'));
    }

    /**
     * Open Delete Post Confirmation Modal
     */
    function epConfirmDeletePost(postId) {
        const idInput = document.getElementById('epDeletePostIdInput');
        if (idInput) idInput.value = postId;
        epOpenModal('epModalDeletePost');
    }

    /**
     * Execute Delete Post via real Web API
     */
    function epExecuteDeletePost() {
        const postId = document.getElementById('epDeletePostIdInput')?.value;
        if (!postId) return;

        const btn = document.getElementById('btnConfirmDeletePost');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Deleting...';
        }

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/posts/' + postId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to delete post');
            return data;
        })
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Delete Post';
            }
            epCloseModal('epModalDeletePost');
            epShowToast(data.message || 'Post deleted successfully.');

            const row = document.getElementById('ep-post-row-' + postId);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    row.remove();
                    const container = document.getElementById('epPostsListContainer');
                    if (container && container.querySelectorAll('.ep-post-row-item').length === 0) {
                        const emptyState = document.getElementById('epEmptyPostsState');
                        if (emptyState) emptyState.style.display = 'block';
                    }
                }, 300);
            }

            const allBadge = document.getElementById('epTypeChipAllCount');
            if (allBadge) {
                let c = parseInt(allBadge.textContent.replace(/[^0-9]/g, '') || '0', 10);
                allBadge.textContent = String(Math.max(0, c - 1));
            }
            const headerCount = document.getElementById('epHeaderPostsCount');
            if (headerCount) {
                let c = parseInt(headerCount.textContent.replace(/[^0-9]/g, '') || '0', 10);
                headerCount.textContent = String(Math.max(0, c - 1));
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Delete Post';
            }
            alert(err.message || 'Error deleting post');
        });
    }

    /**
     * Copy Post Link to clipboard
     */
    function epCopyPostLink(postId) {
        const url = window.location.origin + '/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/posts#post-' + postId;
        navigator.clipboard.writeText(url).then(() => {
            epShowToast('Post link copied to clipboard!');
        }).catch(() => {
            epShowToast('Link: ' + url);
        });
    }

    /**
     * Share Post via Web Share API or link copy
     */
    function epSharePostLink(postId) {
        const url = window.location.origin + '/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/posts#post-' + postId;
        if (navigator.share) {
            navigator.share({
                title: 'Jugajug Post',
                url: url
            }).catch(() => {});
        } else {
            epCopyPostLink(postId);
        }
    }

    /**
     * Toggle See More / See Less content
     */
    function epToggleSeeMore(postId, fullText, shortText) {
        const textEl = document.getElementById('ep-post-text-' + postId);
        const btn = document.getElementById('ep-btn-see-more-' + postId);
        if (!textEl || !btn) return;
        const isExpanded = btn.getAttribute('data-expanded') === 'true';
        if (isExpanded) {
            textEl.textContent = shortText;
            btn.textContent = '... See more';
            btn.setAttribute('data-expanded', 'false');
        } else {
            textEl.textContent = fullText;
            btn.textContent = 'See less';
            btn.setAttribute('data-expanded', 'true');
        }
    }

    /**
     * Switch Sidebar Page Performance Sub-tabs
     */
    function epSwitchSidePerfTab(subKey) {
        ['overview', 'audience', 'content'].forEach(k => {
            const btn = document.getElementById('btnSidePerf' + k.charAt(0).toUpperCase() + k.slice(1));
            const pane = document.getElementById('sidePerfPane' + k.charAt(0).toUpperCase() + k.slice(1));
            if (btn) btn.classList.toggle('active', k === subKey);
            if (pane) pane.style.display = (k === subKey) ? (k === 'overview' ? 'grid' : 'block') : 'none';
        });
    }

    function epFormatNumber(num) {
        num = Number(num) || 0;
        if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        return String(num);
    }

    /**
     * Change Sidebar Page Performance Time Range
     */
    function epChangeSidebarTimeRange(range) {
        const dataset = window.EP_STUDIO_ANALYTICS[range];
        if (!dataset) return;
        const s = dataset.summary || dataset;
        const rEl = document.getElementById('sidePerfReach');
        const eEl = document.getElementById('sidePerfEngagement');
        const fEl = document.getElementById('sidePerfFollowers');
        const pEl = document.getElementById('sidePerfPosts');

        const followers = s.total_followers ?? 0;
        const posts = s.total_posts ?? 0;
        const engagements = s.total_engagements ?? (s.engagements ?? 0);
        const newFollowers = s.new_followers ?? 0;
        const reach = Math.max(posts * 120 + engagements * 15 + followers * 3, 150);

        if (rEl) rEl.textContent = epFormatNumber(reach);
        if (eEl) eEl.textContent = epFormatNumber(engagements);
        if (fEl) fEl.textContent = epFormatNumber(newFollowers);
        if (pEl) pEl.textContent = epFormatNumber(posts);
    }

    /**
     * Submit Edit Page Details via real Web API
     */
    function epSubmitEditPage(e) {
        e.preventDefault();
        const payload = {
            name: document.getElementById('epEditName').value.trim(),
            category: document.getElementById('epEditCategory').value.trim(),
            bio: document.getElementById('epEditBio').value.trim(),
            website: document.getElementById('epEditWebsite').value.trim() || null,
            email: document.getElementById('epEditEmail').value.trim() || null,
            phone: document.getElementById('epEditPhone').value.trim() || null,
            city: document.getElementById('epEditCity').value.trim() || null
        };

        const btn = document.getElementById('btnSaveEditPage');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/details', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Update failed');
            return data;
        })
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Save Changes';
            epCloseModal('epModalEditPage');
            epShowToast('Page information updated successfully!');

            // Real-time DOM update
            const titleEl = document.querySelector('.ep-hero-title');
            if (titleEl) titleEl.textContent = payload.name;

            const categoryEl = document.querySelector('.ep-hero-meta-item span');
            if (categoryEl) categoryEl.textContent = payload.category;

            const aboutCat = document.getElementById('epAboutDisplayCategory');
            if (aboutCat) aboutCat.textContent = payload.category;

            const aboutEmail = document.getElementById('epAboutDisplayEmail');
            if (aboutEmail) aboutEmail.textContent = payload.email || 'Not provided';

            const aboutPhone = document.getElementById('epAboutDisplayPhone');
            if (aboutPhone) aboutPhone.textContent = payload.phone || 'Not provided';

            const aboutLoc = document.getElementById('epAboutDisplayLocation');
            if (aboutLoc) aboutLoc.textContent = payload.city || 'Not provided';
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Save Changes';
            alert('Error updating page: ' + err.message);
        });
    }

    /**
     * Submit Invite Team Member via real Web API
     */
    function epSubmitInviteAdmin(e) {
        e.preventDefault();
        const payload = {
            identifier: document.getElementById('epInviteIdentifier').value.trim(),
            role: document.getElementById('epInviteRole').value
        };

        const btn = document.getElementById('btnSendInvite');
        btn.disabled = true;
        btn.textContent = 'Sending...';

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/team/invite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to send invitation');
            return data;
        })
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Send Invitation';
            epCloseModal('epModalInviteAdmin');
            epShowToast(data.message || 'Team member added successfully!');

            // Real-time DOM insertion
            if (data.member && data.member.user) {
                const container = document.getElementById('epTeamListContainer');
                if (container) {
                    const div = document.createElement('div');
                    div.className = 'ep-team-item';
                    div.id = 'team-member-row-' + data.member.id;
                    div.innerHTML = `
                        <div class="ep-team-user-info">
                            <div class="ep-team-avatar">
                                <div class="ep-avatar-fallback accent-2" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                    ${(data.member.user.name || 'U').charAt(0).toUpperCase()}
                                </div>
                            </div>
                            <div>
                                <span class="ep-team-name">${data.member.user.name}</span>
                                <span class="ep-team-meta">${data.member.user.email}</span>
                            </div>
                        </div>
                        <div class="ep-team-actions-col">
                            <span class="ep-role-badge ${data.member.role}">${data.member.role.toUpperCase()}</span>
                        </div>
                    `;
                    container.appendChild(div);
                }
            } else {
                setTimeout(() => window.location.reload(), 600);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Send Invitation';
            alert('Error inviting member: ' + err.message);
        });
    }

    /**
     * Open Change Role Modal
     */
    function epOpenChangeRoleModal(memberId, currentRole, memberName) {
        document.getElementById('epChangeRoleMemberId').value = memberId;
        document.getElementById('epChangeRoleMemberName').textContent = memberName;
        document.getElementById('epChangeRoleSelect').value = currentRole;
        epOpenModal('epModalChangeRole');
    }

    /**
     * Submit Role Change via real Web API
     */
    function epSubmitChangeRole(e) {
        e.preventDefault();
        const memberId = document.getElementById('epChangeRoleMemberId').value;
        const newRole = document.getElementById('epChangeRoleSelect').value;

        const btn = document.getElementById('btnSubmitChangeRole');
        btn.disabled = true;
        btn.textContent = 'Updating...';

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/team/' + memberId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ role: newRole })
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to update role');
            return data;
        })
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Update Role';
            epCloseModal('epModalChangeRole');
            epShowToast(data.message || 'Team role updated successfully!');

            // Real-time DOM update
            const row = document.getElementById('team-member-row-' + memberId);
            if (row) {
                const badge = row.querySelector('.ep-role-badge');
                if (badge) {
                    badge.className = 'ep-role-badge ' + newRole;
                    badge.textContent = newRole.toUpperCase();
                }
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Update Role';
            alert('Error updating role: ' + err.message);
        });
    }

    /**
     * Remove Team Member with Confirmation
     */
    function epConfirmRemoveMember(memberId, memberName) {
        if (!confirm('Are you sure you want to remove ' + memberName + ' from page administration?')) return;

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/team/' + memberId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to remove member');
            return data;
        })
        .then(data => {
            const row = document.getElementById('team-member-row-' + memberId);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'scale(0.95)';
                setTimeout(() => row.remove(), 300);
            }
            epShowToast(data.message || 'Team member removed successfully.');
        })
        .catch(err => alert('Error removing member: ' + err.message));
    }

    /**
     * Revoke Pending Invitation with Confirmation
     */
    function epConfirmRevokeInvite(memberId, memberName) {
        if (!confirm('Are you sure you want to revoke the pending invitation for ' + memberName + '?')) return;

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/team/' + memberId + '/revoke', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to revoke invitation');
            return data;
        })
        .then(data => {
            const row = document.getElementById('team-member-row-' + memberId);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'scale(0.95)';
                setTimeout(() => row.remove(), 300);
            }
            epShowToast(data.message || 'Invitation revoked successfully.');
        })
        .catch(err => alert('Error revoking invitation: ' + err.message));
    }

    /**
     * Open Transfer Ownership Modal
     */
    function epOpenTransferOwnershipModal() {
        document.getElementById('epTransferOwnerIdentifier').value = '';
        epOpenModal('epModalTransferOwnership');
    }

    /**
     * Submit Transfer Ownership via Web API
     */
    function epSubmitTransferOwnership(e) {
        e.preventDefault();
        const identifier = document.getElementById('epTransferOwnerIdentifier').value.trim();
        if (!identifier) return;

        if (!confirm('FINAL CONFIRMATION: Are you completely certain you want to transfer ownership of this page to ' + identifier + '? You will become an Administrator and lose owner privileges.')) return;

        const btn = document.getElementById('btnSubmitTransferOwnership');
        btn.disabled = true;
        btn.textContent = 'Transferring...';

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/transfer-ownership', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ identifier: identifier })
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to transfer ownership');
            return data;
        })
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Confirm Transfer';
            epCloseModal('epModalTransferOwnership');
            alert(data.message || 'Ownership transferred successfully!');
            window.location.reload();
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Confirm Transfer';
            alert('Error transferring ownership: ' + err.message);
        });
    }

    /**
     * Remove Follower with Confirmation
     */
    function epConfirmRemoveFollower(userId, userName) {
        if (!confirm('Are you sure you want to remove ' + userName + ' from your followers?')) return;

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/followers/' + userId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to remove follower');
            return data;
        })
        .then(data => {
            epShowToast(data.message || 'Follower removed from page.');
            const row = document.getElementById('follower-row-' + userId);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
        })
        .catch(err => alert('Error: ' + err.message));
    }

    function epCopyStudioUrl() {
        const url = document.getElementById('settingDisplayUrl')?.textContent || window.location.origin + '/pages/' + window.EP_PAGE_SLUG;
        navigator.clipboard.writeText(url).then(() => {
            epShowToast('Page link copied to clipboard!');
        }).catch(() => {
            prompt('Copy link:', url);
        });
    }

    /**
     * Save Page Settings
     */
    function epSavePageSettings(e) {
        e.preventDefault();

        const checkedFormats = [];
        ['format_text', 'format_image', 'format_video', 'format_reel', 'format_story', 'format_live'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el.checked) {
                checkedFormats.push(el.value);
            }
        });

        const blockedKeywordsRaw = document.getElementById('settingBlockedKeywords')?.value || '';
        const blockedKeywords = blockedKeywordsRaw
            .split(',')
            .map(s => s.trim())
            .filter(s => s.length > 0);

        const payload = {
            name: document.getElementById('settingPageName')?.value || '',
            username: document.getElementById('settingPageUsername')?.value || '',
            category: document.getElementById('settingPageCategory')?.value || '',
            sub_category: document.getElementById('settingPageSubCategory')?.value || '',
            bio: document.getElementById('settingPageBio')?.value || '',
            description: document.getElementById('settingPageDescription')?.value || '',
            visibility: document.getElementById('settingPageVisibility')?.value || 'public',
            email: document.getElementById('settingContactEmail')?.value || '',
            phone: document.getElementById('settingContactPhone')?.value || '',
            website: document.getElementById('settingContactWebsite')?.value || '',
            address: document.getElementById('settingContactAddress')?.value || '',
            city: document.getElementById('settingContactCity')?.value || '',
            zip_code: document.getElementById('settingContactZip')?.value || '',
            last_updated_at: window.EP_PAGE_UPDATED_AT || '',
            social_links: {
                facebook: document.getElementById('settingSocialFacebook')?.value || '',
                twitter: document.getElementById('settingSocialTwitter')?.value || '',
                instagram: document.getElementById('settingSocialInstagram')?.value || '',
                linkedin: document.getElementById('settingSocialLinkedin')?.value || '',
                youtube: document.getElementById('settingSocialYoutube')?.value || '',
                github: document.getElementById('settingSocialGithub')?.value || ''
            },
            settings: {
                visibility: document.getElementById('settingPageVisibility')?.value || 'public',
                discoverability: {
                    search_engine_indexing: !!document.getElementById('settingSearchIndexing')?.checked,
                    show_in_directory: !!document.getElementById('settingShowDirectory')?.checked
                },
                messaging: {
                    allow_messages: !!document.getElementById('settingAllowMessages')?.checked,
                    auto_reply_enabled: !!document.getElementById('settingAutoReplyEnabled')?.checked,
                    auto_reply_message: document.getElementById('settingAutoReply')?.value || ''
                },
                moderation: {
                    allow_comments: !!document.getElementById('settingAllowComments')?.checked,
                    profanity_filter: !!document.getElementById('settingProfanityFilter')?.checked,
                    blocked_keywords: blockedKeywords
                },
                tagging: {
                    allow_mentions: !!document.getElementById('settingAllowMentions')?.checked,
                    who_can_tag: document.getElementById('settingWhoCanTag')?.value || 'everyone'
                },
                notifications: {
                    email_alerts: !!document.getElementById('settingNotifyEmail')?.checked,
                    in_app_follows: !!document.getElementById('settingNotifyFollows')?.checked,
                    in_app_comments: !!document.getElementById('settingNotifyComments')?.checked,
                    in_app_messages: !!document.getElementById('settingNotifyMessages')?.checked
                },
                publishing: {
                    allowed_formats: checkedFormats
                },
                privacy: {
                    audience: document.getElementById('settingDefaultAudience')?.value || 'public',
                    show_contact_info: document.getElementById('settingShowContactInfo')?.value === '1'
                }
            },
            privacy_settings: {
                default_post_audience: document.getElementById('settingDefaultAudience')?.value || 'public',
                public_email: !!document.getElementById('settingPublicEmail')?.checked,
                public_phone: !!document.getElementById('settingPublicPhone')?.checked,
                public_address: !!document.getElementById('settingPublicAddress')?.checked
            }
        };

        const btn = document.getElementById('btnSaveSettings');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Saving Settings...';
        }

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/settings', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                if (res.status === 409) {
                    throw new Error(data.message || 'Conflict: This page was modified concurrently by another manager. Please reload before saving.');
                }
                throw new Error(data.message || 'Failed to save settings');
            }
            return data;
        })
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Save All Settings';
            }
            if (data.page && data.page.updated_at) {
                window.EP_PAGE_UPDATED_AT = data.page.updated_at;
            }
            epShowToast(data.message || 'Page settings updated successfully!');

            if (payload.name) {
                const titleEl = document.querySelector('.ep-hero-title');
                if (titleEl) titleEl.textContent = payload.name;
                window.EP_PAGE_NAME = payload.name;
            }
            if (payload.category) {
                const categoryEl = document.querySelector('.ep-hero-meta-item span');
                if (categoryEl) categoryEl.textContent = payload.category;
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Save All Settings';
            }
            alert('Error saving settings: ' + err.message);
        });
    }

    /**
     * Archive Page
     */
    function epArchivePageStudio() {
        if (!confirm('Are you sure you want to archive this page? It will be hidden from the public until reactivated.')) return;

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/archive', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const data = await res.json();
            if (data.success) {
                epShowToast(data.message || 'Page archived successfully.');
                setTimeout(() => window.location.reload(), 800);
            } else {
                alert(data.message || 'Failed to archive page.');
            }
        })
        .catch(e => alert('Server communication error.'));
    }

    /**
     * Restore Archived Page
     */
    function epRestoreArchivedPageStudio() {
        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/settings', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: 'active' })
        })
        .then(async res => {
            const data = await res.json();
            if (data.success) {
                epShowToast('Page reactivated successfully.');
                setTimeout(() => window.location.reload(), 800);
            } else {
                alert(data.message || 'Failed to reactivate page.');
            }
        })
        .catch(e => alert('Server communication error.'));
    }

    /**
     * Move Page to Trash
     */
    function epDeletePageStudio() {
        if (!confirm('DANGER: Are you sure you want to move this page to trash? Only the owner can restore it.')) return;

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Page moved to trash.');
                window.location.href = '/pages';
            } else {
                alert(data.message || 'Failed to move page to trash.');
            }
        })
        .catch(e => alert('Server communication error.'));
    }

    /**
     * Upload Avatar or Cover Photo with Instant Real-Time Preview
     */
    function epUploadBranding(type, event) {
        const file = event.target.files[0];
        if (!file) return;

        const blobUrl = URL.createObjectURL(file);

        // Instant optimistic preview
        if (type === 'cover') {
            let coverImg = document.getElementById('epManageCoverImg');
            if (!coverImg) {
                coverImg = document.createElement('img');
                coverImg.id = 'epManageCoverImg';
                coverImg.alt = 'Cover';
                const box = document.getElementById('epManageCoverBox');
                if (box) box.prepend(coverImg);
            }
            if (coverImg) coverImg.src = blobUrl;
        } else {
            let avatarImg = document.getElementById('epManageAvatarImg');
            if (!avatarImg) {
                const box = document.getElementById('epManageAvatarBox');
                if (box) {
                    const fallback = box.querySelector('.ep-avatar-fallback');
                    if (fallback) fallback.remove();
                    avatarImg = document.createElement('img');
                    avatarImg.id = 'epManageAvatarImg';
                    avatarImg.alt = 'Avatar';
                    box.prepend(avatarImg);
                }
            }
            if (avatarImg) avatarImg.src = blobUrl;
        }

        const formData = new FormData();
        formData.append(type === 'avatar' ? 'avatar' : 'cover_image', file);

        epShowToast('Uploading ' + type + ' photo...');

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/branding', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Upload failed');
            return data;
        })
        .then(data => {
            epShowToast(data.message || (type.toUpperCase() + ' updated successfully!'));
            if (data.url) {
                if (type === 'cover') {
                    const coverImg = document.getElementById('epManageCoverImg');
                    if (coverImg) coverImg.src = data.url;
                } else {
                    const avatarImg = document.getElementById('epManageAvatarImg');
                    if (avatarImg) avatarImg.src = data.url;
                }
            }
        })
        .catch(err => alert('Error uploading ' + type + ': ' + err.message));
    }

    /**
     * Remove Avatar or Cover Photo
     */
    function epRemoveBranding(type) {
        if (!confirm('Are you sure you want to remove the page ' + type + ' photo?')) return;

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/branding/' + type, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to remove ' + type);
            return data;
        })
        .then(data => {
            epShowToast(data.message || (type.toUpperCase() + ' removed successfully!'));
            if (type === 'cover') {
                const coverImg = document.getElementById('epManageCoverImg');
                if (coverImg) coverImg.remove();
            } else {
                const avatarImg = document.getElementById('epManageAvatarImg');
                if (avatarImg) {
                    avatarImg.remove();
                    const box = document.getElementById('epManageAvatarBox');
                    if (box && !box.querySelector('.ep-avatar-fallback')) {
                        const fallback = document.createElement('div');
                        fallback.className = 'ep-avatar-fallback accent-1';
                        fallback.style.cssText = 'width: 100%; height: 100%; font-size: 36px;';
                        fallback.textContent = (window.EP_PAGE_NAME || 'P').charAt(0).toUpperCase();
                        box.prepend(fallback);
                    }
                }
            }
        })
        .catch(err => alert(err.message || 'Error removing photo'));
    }

    /**
     * Open Create Post Modal with preselected type
     */
    function epOpenCreatePostWithType(type) {
        const typeSelect = document.getElementById('epPostTypeSelect');
        if (typeSelect) {
            typeSelect.value = type;
            epChangePostType(type);
        }
        epOpenModal('epModalCreatePost');
    }

    /**
     * Submit Create Event via real Web API
     */
    function epSubmitCreateEvent(e) {
        e.preventDefault();
        const title = document.getElementById('epEventTitleInput').value.trim();
        const startTime = document.getElementById('epEventStartInput').value;
        if (!title || !startTime) return;

        const btn = document.getElementById('btnPublishEvent');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Publishing...';
        }

        const isOnline = document.querySelector('input[name="eventFormat"]:checked')?.value === 'online';
        const payload = {
            title: title,
            description: document.getElementById('epEventDescInput').value.trim(),
            start_time: startTime,
            end_time: document.getElementById('epEventEndInput').value || null,
            is_online: isOnline,
            location: isOnline ? 'Online Event' : (document.getElementById('epEventLocationInput').value.trim() || 'Physical Event')
        };

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/events', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to create event');
            return data;
        })
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Publish Event';
            }
            epCloseModal('epModalCreateEvent');
            epShowToast(data.message || 'Event created successfully!');

            const ev = data.event;
            if (ev) {
                const container = document.getElementById('epEventsGridContainer');
                const emptyState = document.getElementById('epEmptyEventsState');
                if (emptyState) emptyState.style.display = 'none';
                if (container) {
                    container.style.display = 'grid';
                    const card = document.createElement('div');
                    card.className = 'ep-event-card-item';
                    card.id = 'ep-event-card-' + ev.id;
                    card.style.cssText = 'border: 1px solid var(--ep-border); border-radius: 10px; overflow: hidden; background: var(--ep-card-bg); display: flex; flex-direction: column;';
                    card.innerHTML = `
                        <div style="height: 110px; background: linear-gradient(135deg, #059669, #10B981); display: flex; align-items: center; justify-content: center; color: white; padding: 12px; position: relative;">
                            <div style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                ${ev.is_online ? '🌐 Online Event' : '📍 ' + (ev.location || 'Physical Event')}
                            </div>
                            <span style="font-size: 26px;">📅</span>
                        </div>
                        <div style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                            <span style="font-size: 12px; font-weight: 700; color: #059669; text-transform: uppercase;">
                                ${ev.start_time || 'Upcoming'}
                            </span>
                            <h3 style="font-size: 15px; font-weight: 700; color: var(--ep-text-title); margin: 6px 0 8px; line-height: 1.35;">
                                ${epEscapeHtml(ev.title)}
                            </h3>
                            <p style="font-size: 13px; color: var(--ep-text-secondary); margin: 0 0 14px; line-height: 1.45; flex: 1;">
                                ${epEscapeHtml(ev.description || '')}
                            </p>
                            <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--ep-border); padding-top: 12px; font-size: 12.5px; color: var(--ep-text-secondary);">
                                <span id="ep-event-rsvp-cnt-${ev.id}">👥 0 Attending</span>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <button type="button" class="ep-btn-manage" style="padding: 4px 10px; font-size: 12px;" onclick="epRsvpEvent(${ev.id})">RSVP</button>
                                    <button type="button" class="ep-btn-manage" style="padding: 4px 8px; font-size: 12px; color: #dc2626;" onclick="epDeleteEvent(${ev.id})" title="Delete Event">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    container.prepend(card);
                }
            }
            document.getElementById('epFormCreateEvent').reset();
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Publish Event';
            }
            alert(err.message || 'Error creating event');
        });
    }

    /**
     * Delete Event
     */
    function epDeleteEvent(eventId) {
        if (!confirm('Are you sure you want to delete this event? This action cannot be undone.')) return;

        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/events/' + eventId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to delete event');
            return data;
        })
        .then(data => {
            epShowToast(data.message || 'Event deleted successfully.');
            const card = document.getElementById('ep-event-card-' + eventId);
            if (card) {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.remove();
                    const container = document.getElementById('epEventsGridContainer');
                    if (container && container.querySelectorAll('.ep-event-card-item').length === 0) {
                        container.style.display = 'none';
                        const emptyState = document.getElementById('epEmptyEventsState');
                        if (emptyState) emptyState.style.display = 'block';
                    }
                }, 300);
            }
        })
        .catch(err => alert(err.message || 'Error deleting event'));
    }

    /**
     * RSVP to Event
     */
    function epRsvpEvent(eventId) {
        fetch('/pages/' + encodeURIComponent(window.EP_PAGE_SLUG) + '/events/' + eventId + '/rsvp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: 'going' })
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to RSVP');
            return data;
        })
        .then(data => {
            epShowToast(data.message || 'RSVP updated!');
            const cntEl = document.getElementById('ep-event-rsvp-cnt-' + eventId);
            if (cntEl && data.rsvp_count !== undefined) {
                cntEl.textContent = '👥 ' + data.rsvp_count + ' Attending';
            }
        })
        .catch(err => alert(err.message || 'Error updating RSVP'));
    }

    /**
     * Copy Page Link
     */
    function epCopyLink(url) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(() => {
                epShowToast('Page link copied to clipboard!');
            });
        } else {
            const ta = document.createElement('textarea');
            ta.value = url;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            epShowToast('Page link copied to clipboard!');
        }
    }

    /**
     * Share Page
     */
    function epSharePage(title, url) {
        if (navigator.share) {
            navigator.share({ title: title, text: 'Check out ' + title + ' on Jugajug', url: url }).catch(() => {});
        } else {
            epCopyLink(url);
        }
    }

    /**
     * Toast Notifications
     */
    let epToastTimeout = null;
    function epShowToast(msg) {
        const toast = document.getElementById('epToast');
        const text = document.getElementById('epToastText');
        if (!toast || !text) return;

        text.textContent = msg;
        toast.style.display = 'block';

        if (epToastTimeout) clearTimeout(epToastTimeout);
        epToastTimeout = setTimeout(() => {
            toast.style.display = 'none';
        }, 2800);
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        if (window.EP_STUDIO_ANALYTICS && window.EP_STUDIO_ANALYTICS['30d']) {
            epRenderAnalyticsChart(window.EP_STUDIO_ANALYTICS['30d']);
        }
        const initialTab = '{{ $activeTab ?? "posts" }}';
        epSwitchStudioTab(initialTab);

        const searchInput = document.getElementById('epPostsSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase().trim();
                const postRows = document.querySelectorAll('.ep-post-row-item');
                let visibleCount = 0;
                postRows.forEach(row => {
                    const textEl = row.querySelector('.ep-post-text');
                    const text = textEl ? textEl.textContent.toLowerCase() : '';
                    if (!query || text.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                const emptyState = document.getElementById('epEmptyPostsState');
                if (emptyState) {
                    emptyState.style.display = (visibleCount === 0 && postRows.length > 0) ? 'block' : (postRows.length === 0 ? 'block' : 'none');
                }
            });
        }
    });
</script>
@endpush
