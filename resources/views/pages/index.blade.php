@extends('layouts.app')

@section('title', 'Pages — Professional Enterprise Social Management')

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
@endphp

<div class="ep-dashboard-root">
    <div class="ep-dashboard-wrapper">
        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- 1. LEFT SIDEBAR                                           -->
        <!-- ═════════════════════════════════════════════════════════ -->
        <aside class="ep-sidebar-left" aria-label="Main Navigation">
            <!-- Jugajug Sidebar Branding -->
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
                    <span class="ep-sidebar-badge">5</span>
                </a>
                <a href="{{ route('notifications.index') }}" class="ep-sidebar-link">
                    <span class="ep-sidebar-icon">@include('pages.partials.icon', ['name' => 'bell', 'size' => 20])</span>
                    <span class="ep-sidebar-label">Notifications</span>
                    <span class="ep-sidebar-badge">3</span>
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
                <a href="javascript:void(0)" class="ep-sidebar-link">
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
                <a href="javascript:void(0)" onclick="epSwitchTab('saved')" class="ep-sidebar-link">
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
                    @if($managedPages->count() > 0)
                        <a href="javascript:void(0)" class="ep-sidebar-see-all" onclick="epSwitchTab('my-pages')">See All</a>
                    @endif
                </div>

                <div class="ep-sidebar-pages-list" id="epSidebarPagesList">
                    @forelse($managedPages->take(5) as $idx => $mPage)
                        <div class="ep-sidebar-page-item" id="side-item-{{ $mPage->id }}">
                            <a href="{{ route('pages.show', $mPage->slug) }}" class="ep-sidebar-page-link" title="{{ $mPage->name }}">
                                <div class="ep-sidebar-page-avatar">
                                    @if($mPage->avatar_url)
                                        <img src="{{ $mPage->avatar_url }}" alt="{{ $mPage->name }}">
                                    @else
                                        <div class="ep-avatar-fallback accent-{{ $idx % 4 }}">
                                            {{ mb_strtoupper(mb_substr($mPage->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <span class="ep-sidebar-page-name">{{ $mPage->name }}</span>
                            </a>
                            <div class="ep-dropdown-wrap">
                                <button type="button" class="ep-btn-more-dots" onclick="epToggleMenu(event, 'side-page-{{ $mPage->id }}')" aria-label="More options">
                                    @include('pages.partials.icon', ['name' => 'more-horizontal', 'size' => 16])
                                </button>
                                <div id="side-page-{{ $mPage->id }}" class="ep-popover-menu">
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
                    <button type="button" class="ep-btn-sidebar-create" onclick="epOpenCreatePageModal()" style="border: none; cursor: pointer; text-align: center; justify-content: center; width: 100%;">
                        @include('pages.partials.icon', ['name' => 'plus', 'size' => 16, 'strokeWidth' => 2.5])
                        <span>+ Create Page</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- 2. CENTER DASHBOARD CONTENT                               -->
        <!-- ═════════════════════════════════════════════════════════ -->
        <main class="ep-main-content">
            <!-- Dashboard Title Row -->
            <div class="ep-dashboard-header">
                <div>
                    <h1 class="ep-page-title">Pages <span style="font-size: 15px; font-weight: 600; color: var(--ep-text-secondary);">(পেইজ ও পাবলিক এন্টারপ্রাইজ হাব)</span></h1>
                    <p class="ep-page-subtitle">Manage your pages, grow your audience and connect with your community. আপনার ব্র্যান্ড, ব্যবসা বা সংগঠনের প্রফেশনাল পেইজ পরিচালনা করুন।</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="ep-btn-header-create" onclick="epOpenCreatePageModal()">
                        @include('pages.partials.icon', ['name' => 'plus', 'size' => 16, 'strokeWidth' => 2.5])
                        <span>+ Create Page / নতুন পেইজ তৈরি করুন</span>
                    </button>
                </div>
            </div>

            <!-- 4 Overview Statistics Cards in 1 Row -->
            <div class="ep-stats-grid">
                <!-- Card 1: Total Pages -->
                <div class="ep-stat-card">
                    <div class="ep-stat-icon-circle blue">
                        @include('pages.partials.icon', ['name' => 'flag', 'size' => 22])
                    </div>
                    <div class="ep-stat-content">
                        <span class="ep-stat-label">Total Pages</span>
                        <div class="ep-stat-value-row">
                            <span class="ep-stat-value" id="epStatTotalPages">{{ $managedPages->count() }}</span>
                            @if($newPagesCount > 0)
                                <span class="ep-stat-trend green">↑ {{ $newPagesCount }} new</span>
                            @else
                                <span class="ep-stat-trend neutral">—</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Card 2: Total Followers -->
                <div class="ep-stat-card">
                    <div class="ep-stat-icon-circle purple">
                        @include('pages.partials.icon', ['name' => 'users', 'size' => 22])
                    </div>
                    <div class="ep-stat-content">
                        <span class="ep-stat-label">Total Followers</span>
                        <div class="ep-stat-value-row">
                            <span class="ep-stat-value">{{ $formatMetric($totalFollowersManaged) }}</span>
                            @if($followersGrowthPct > 0)
                                <span class="ep-stat-trend green">↑ {{ $followersGrowthPct }}%</span>
                            @elseif($followersGrowthPct < 0)
                                <span class="ep-stat-trend red">↓ {{ abs($followersGrowthPct) }}%</span>
                            @else
                                <span class="ep-stat-trend neutral">—</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Card 3: Total Posts -->
                <div class="ep-stat-card">
                    <div class="ep-stat-icon-circle green">
                        @include('pages.partials.icon', ['name' => 'file-text', 'size' => 22])
                    </div>
                    <div class="ep-stat-content">
                        <span class="ep-stat-label">Total Posts</span>
                        <div class="ep-stat-value-row">
                            <span class="ep-stat-value">{{ $formatMetric($totalPostsManaged) }}</span>
                            @if($postsGrowthPct > 0)
                                <span class="ep-stat-trend green">↑ {{ $postsGrowthPct }}%</span>
                            @elseif($postsGrowthPct < 0)
                                <span class="ep-stat-trend red">↓ {{ abs($postsGrowthPct) }}%</span>
                            @else
                                <span class="ep-stat-trend neutral">—</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Card 4: Total Engagement -->
                <div class="ep-stat-card">
                    <div class="ep-stat-icon-circle orange">
                        @include('pages.partials.icon', ['name' => 'bullhorn', 'size' => 22])
                    </div>
                    <div class="ep-stat-content">
                        <span class="ep-stat-label">Total Engagement</span>
                        <div class="ep-stat-value-row">
                            <span class="ep-stat-value">{{ $formatMetric($totalEngagement) }}</span>
                            @if($engagementGrowthPct > 0)
                                <span class="ep-stat-trend green">↑ {{ $engagementGrowthPct }}%</span>
                            @elseif($engagementGrowthPct < 0)
                                <span class="ep-stat-trend red">↓ {{ abs($engagementGrowthPct) }}%</span>
                            @else
                                <span class="ep-stat-trend neutral">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Horizontal Navigation Tabs -->
            <div class="ep-tabs-nav-bar" role="tablist">
                <button type="button" class="ep-tab-item active" onclick="epSwitchTab('my-pages')" data-tab="my-pages" role="tab">
                    <span>My Pages / আমার পেইজসমূহ</span>
                    <span class="ep-tab-indicator"></span>
                </button>
                <button type="button" class="ep-tab-item" onclick="epSwitchTab('discover')" data-tab="discover" role="tab">
                    <span>Discover / ওভারভিউ ও সব পেইজ</span>
                    <span class="ep-tab-indicator"></span>
                </button>
                <button type="button" class="ep-tab-item" onclick="epSwitchTab('invitations')" data-tab="invitations" role="tab">
                    <span>Invitations / আমন্ত্রণসমূহ</span>
                    @if($pendingInvitations->count() > 0)
                        <span class="ep-tab-badge">{{ $pendingInvitations->count() }}</span>
                    @endif
                    <span class="ep-tab-indicator"></span>
                </button>
                <button type="button" class="ep-tab-item" onclick="epSwitchTab('notifications')" data-tab="notifications" role="tab">
                    <span>Notifications / নোটিফিকেশন</span>
                    @if($pageNotifications->count() > 0)
                        <span class="ep-tab-badge">{{ $pageNotifications->count() }}</span>
                    @endif
                    <span class="ep-tab-indicator"></span>
                </button>
                <button type="button" class="ep-tab-item" onclick="epSwitchTab('saved')" data-tab="saved" role="tab">
                    <span>Saved / সংরক্ষিত পেইজ</span>
                    <span class="ep-tab-indicator"></span>
                </button>
            </div>

            <!-- TAB 1: MY PAGES PANE -->
            <div id="tab-pane-my-pages" class="ep-tab-pane active">
                <!-- My Pages Card with Horizontal Rows -->
                <div class="ep-card">
                    <div class="ep-card-header">
                        <div>
                            <h2 class="ep-card-title">My Pages</h2>
                            <p class="ep-card-subtitle">Pages you manage or own</p>
                        </div>
                        <div class="ep-card-controls">
                            <div class="ep-search-input-wrap">
                                <span class="ep-search-icon">
                                    @include('pages.partials.icon', ['name' => 'search', 'size' => 15])
                                </span>
                                <input type="text"
                                       id="epSearchPagesInput"
                                       placeholder="Search pages..."
                                       autocomplete="off"
                                       oninput="epFilterPagesList(this.value)">
                            </div>
                            <div class="ep-sort-select-wrap">
                                <span>Sort by:</span>
                                <select id="epSortPagesSelect" onchange="epSortPagesList(this.value)">
                                    <option value="followers">Most Followers</option>
                                    <option value="recent">Most Recent</option>
                                    <option value="name">Name</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="ep-pages-rows-list" id="epPagesRowsList">
                        @forelse($managedPages as $idx => $page)
                            <div class="ep-page-row-item"
                                 data-name="{{ strtolower($page->name) }}"
                                 data-category="{{ strtolower($page->category ?? '') }}"
                                 data-followers="{{ $page->followers_count ?? 0 }}"
                                 data-created="{{ $page->created_at ? $page->created_at->timestamp : 0 }}">
                                <!-- Left: Cover Image Thumbnail with Overlayed Avatar Badge -->
                                <div class="ep-page-thumbnail-box">
                                    @if($page->cover_url)
                                        <img src="{{ $page->cover_url }}" alt="{{ $page->name }} cover" class="ep-row-cover">
                                    @else
                                        <div class="ep-row-cover-placeholder">
                                            <span>{{ $page->name }}</span>
                                        </div>
                                    @endif
                                    <div class="ep-row-avatar-badge">
                                        @if($page->avatar_url)
                                            <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}">
                                        @else
                                            <div class="ep-avatar-fallback accent-{{ $idx % 4 }}" style="width: 100%; height: 100%;">
                                                {{ mb_strtoupper(mb_substr($page->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Center: Details -->
                                <div class="ep-page-details-box">
                                    <div class="ep-page-name-row">
                                        <a href="{{ route('pages.show', $page->slug) }}" class="ep-page-title-link">
                                            {{ $page->name }}
                                        </a>
                                        @if($page->is_verified)
                                            <span class="ep-verified-badge" title="Verified Page">
                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="#1877F2">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="ep-page-meta-row">
                                        <span class="ep-page-category">{{ $page->category ?: 'Community & Media' }}</span>
                                        <span class="ep-meta-bullet">•</span>
                                        <span class="ep-page-followers">{{ $formatMetric($page->followers_count ?? 0) }} followers</span>
                                    </div>
                                    <p class="ep-page-short-desc">
                                        {{ $page->bio ?: ($page->description ?: 'No description provided for this page yet.') }}
                                    </p>
                                </div>

                                <!-- Right: Action Buttons -->
                                <div class="ep-page-actions-box">
                                    <a href="{{ route('pages.manage', $page->slug) }}" class="ep-btn-manage">Manage</a>
                                    <a href="{{ route('pages.show', $page->slug) }}" class="ep-btn-view">View Page</a>
                                    <div class="ep-dropdown-wrap">
                                        <button type="button" class="ep-btn-more-actions" onclick="epToggleMenu(event, 'page-menu-{{ $page->id }}')" aria-label="More options">
                                            @include('pages.partials.icon', ['name' => 'more-horizontal', 'size' => 18])
                                        </button>
                                        <div id="page-menu-{{ $page->id }}" class="ep-popover-menu">
                                            <a href="{{ route('pages.manage', $page->slug) }}">
                                                @include('pages.partials.icon', ['name' => 'edit', 'size' => 14])
                                                <span>Edit Page</span>
                                            </a>
                                            <button type="button" onclick="epOpenInviteModal({{ $page->id }}, '{{ addslashes($page->name) }}')">
                                                @include('pages.partials.icon', ['name' => 'user-plus', 'size' => 14])
                                                <span>Invite People</span>
                                            </button>
                                            <button type="button" onclick="epSharePage('{{ addslashes($page->name) }}', '{{ route('pages.show', $page->slug) }}')">
                                                @include('pages.partials.icon', ['name' => 'share-2', 'size' => 14])
                                                <span>Share Page</span>
                                            </button>
                                            <a href="{{ route('pages.manage', $page->slug) }}">
                                                @include('pages.partials.icon', ['name' => 'settings', 'size' => 14])
                                                <span>Page Settings</span>
                                            </a>
                                            <button type="button" onclick="epCopyLink('{{ route('pages.show', $page->slug) }}')">
                                                @include('pages.partials.icon', ['name' => 'copy', 'size' => 14])
                                                <span>Copy Page Link</span>
                                            </button>
                                            @if((int) $page->owner_id === (int) ($currentUser->id ?? 0))
                                                <button type="button" style="color: #ef4444;" onclick="epOpenDeleteModal({{ $page->id }}, '{{ addslashes($page->name) }}')">
                                                    @include('pages.partials.icon', ['name' => 'trash-2', 'size' => 14])
                                                    <span>Delete Page</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="ep-empty-pages-box">
                                <div class="ep-empty-icon-wrap">
                                    @include('pages.partials.icon', ['name' => 'flag', 'size' => 28])
                                </div>
                                <h3 class="ep-empty-title">You don't manage any pages yet</h3>
                                <p class="ep-empty-desc">Create your own business or creator page to connect with your community, publish updates, and grow your brand.</p>
                                <a href="{{ route('pages.create') }}" class="ep-btn-header-create">
                                    @include('pages.partials.icon', ['name' => 'plus', 'size' => 15, 'strokeWidth' => 2.5])
                                    <span>+ Create Your First Page</span>
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recommended Pages Section -->
                <div class="ep-card ep-recommended-section" style="margin-top: 20px;">
                    <div class="ep-card-header">
                        <div>
                            <h2 class="ep-card-title">Recommended Pages</h2>
                            <p class="ep-card-subtitle">Pages you might like to follow</p>
                        </div>
                        <a href="javascript:void(0)" class="ep-link-see-all" onclick="epSwitchTab('discover')">See All</a>
                    </div>

                    <div class="ep-recommended-grid">
                        @forelse($recommendedPages as $idx => $rPage)
                            <div class="ep-rec-card">
                                <div class="ep-rec-card-top">
                                    <div class="ep-rec-avatar">
                                        @if($rPage->avatar_url)
                                            <img src="{{ $rPage->avatar_url }}" alt="{{ $rPage->name }}">
                                        @else
                                            <div class="ep-avatar-fallback accent-{{ $idx % 4 }}">
                                                {{ mb_strtoupper(mb_substr($rPage->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ep-dropdown-wrap">
                                        <button type="button" class="ep-btn-rec-more" onclick="epToggleMenu(event, 'rec-menu-{{ $rPage->id }}')" aria-label="More">
                                            @include('pages.partials.icon', ['name' => 'more-horizontal', 'size' => 15])
                                        </button>
                                        <div id="rec-menu-{{ $rPage->id }}" class="ep-popover-menu">
                                            <a href="{{ route('pages.show', $rPage->slug) }}">
                                                @include('pages.partials.icon', ['name' => 'eye', 'size' => 14])
                                                <span>View Page</span>
                                            </a>
                                            <button type="button" onclick="epCopyLink('{{ route('pages.show', $rPage->slug) }}')">
                                                @include('pages.partials.icon', ['name' => 'copy', 'size' => 14])
                                                <span>Copy Link</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="ep-rec-info">
                                    <a href="{{ route('pages.show', $rPage->slug) }}" class="ep-rec-name" title="{{ $rPage->name }}">
                                        {{ $rPage->name }}
                                    </a>
                                    <span class="ep-rec-category">{{ $rPage->category ?: 'Organization' }}</span>
                                </div>
                                <button type="button"
                                        class="ep-btn-rec-follow {{ $followedPages->contains('id', $rPage->id) ? 'following' : '' }}"
                                        data-page-id="{{ $rPage->id }}"
                                        onclick="epToggleFollow(this, {{ $rPage->id }})">
                                    <span class="ep-follow-icon">
                                        @include('pages.partials.icon', ['name' => $followedPages->contains('id', $rPage->id) ? 'check' : 'plus', 'size' => 13])
                                    </span>
                                    <span class="ep-follow-text">{{ $followedPages->contains('id', $rPage->id) ? 'Following' : '+ Follow' }}</span>
                                </button>
                            </div>
                        @empty
                            <div style="grid-column: 1 / -1; padding: 24px; text-align: center; color: var(--ep-text-secondary); font-size: 14px;">
                                No recommended pages found at the moment.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- TAB 2: DISCOVER PANE -->
            <div id="tab-pane-discover" class="ep-tab-pane" style="display: none;">
                <div class="ep-card">
                    <div class="ep-card-header">
                        <div>
                            <h2 class="ep-card-title">Discover Pages</h2>
                            <p class="ep-card-subtitle">Explore public business, community, and creator pages</p>
                        </div>
                    </div>

                    <!-- Filter Bar -->
                    <form action="{{ route('pages.index') }}" method="GET" style="padding: 16px 24px 0 24px;">
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                            <div class="ep-search-input-wrap" style="flex: 1; min-width: 220px;">
                                <span class="ep-search-icon">
                                    @include('pages.partials.icon', ['name' => 'search', 'size' => 15])
                                </span>
                                <input type="text"
                                       name="q"
                                       value="{{ $searchQuery }}"
                                       placeholder="Search by name, category or bio..."
                                       autocomplete="off">
                            </div>
                            @if($selectedCategory)
                                <input type="hidden" name="category" value="{{ $selectedCategory }}">
                            @endif
                            <button type="submit" class="ep-btn-view" style="padding: 9px 18px;">Search</button>
                            @if($searchQuery || $selectedCategory)
                                <a href="{{ route('pages.index') }}" class="ep-btn-manage" style="padding: 9px 14px;">Clear</a>
                            @endif
                        </div>

                        <!-- Categories Horizontal Pills -->
                        @if($categories->count() > 0)
                            <div style="display: flex; gap: 8px; overflow-x: auto; padding: 14px 0 6px; scrollbar-width: none;">
                                <a href="{{ route('pages.index', array_merge(request()->query(), ['category' => null])) }}"
                                   style="padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid var(--ep-border); background: {{ !$selectedCategory ? 'var(--ep-brand-blue)' : 'var(--ep-card-bg)' }}; color: {{ !$selectedCategory ? '#fff' : 'var(--ep-text-body)' }};">
                                    All Categories
                                </a>
                                @foreach($categories as $cat)
                                    <a href="{{ route('pages.index', array_merge(request()->query(), ['category' => $cat])) }}"
                                       style="padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid var(--ep-border); background: {{ $selectedCategory === $cat ? 'var(--ep-brand-blue)' : 'var(--ep-card-bg)' }}; color: {{ $selectedCategory === $cat ? '#fff' : 'var(--ep-text-body)' }}; white-space: nowrap;">
                                        {{ $cat }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </form>

                    <!-- Discover Cards Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; padding: 20px 24px;">
                        @forelse($allPages as $idx => $dPage)
                            <div class="ep-card" style="display: flex; flex-direction: column; overflow: hidden; border-radius: 10px;">
                                <div style="height: 80px; position: relative; background: #0F172A;">
                                    @if($dPage->cover_url)
                                        <img src="{{ $dPage->cover_url }}" alt="{{ $dPage->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #1E293B, #0F172A);"></div>
                                    @endif
                                    <div style="position: absolute; bottom: -20px; left: 16px; width: 44px; height: 44px; border-radius: 8px; border: 2px solid #fff; overflow: hidden; background: #fff;">
                                        @if($dPage->avatar_url)
                                            <img src="{{ $dPage->avatar_url }}" alt="{{ $dPage->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <div class="ep-avatar-fallback accent-{{ $idx % 4 }}" style="width: 100%; height: 100%;">
                                                {{ mb_strtoupper(mb_substr($dPage->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div style="padding: 26px 16px 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 4px; margin-bottom: 2px;">
                                            <a href="{{ route('pages.show', $dPage->slug) }}" style="font-size: 15px; font-weight: 800; color: var(--ep-text-title); text-decoration: none;">
                                                {{ $dPage->name }}
                                            </a>
                                            @if($dPage->is_verified)
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="#1877F2">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div style="font-size: 12.5px; color: var(--ep-text-secondary); margin-bottom: 8px;">
                                            {{ $dPage->category ?: 'General' }} • {{ $formatMetric($dPage->followers_count ?? 0) }} followers
                                        </div>
                                        <p style="font-size: 13px; color: var(--ep-text-body); line-height: 1.4; margin: 0 0 14px 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ $dPage->bio ?: 'No description provided.' }}
                                        </p>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="{{ route('pages.show', $dPage->slug) }}" class="ep-btn-manage" style="flex: 1; text-align: center; font-size: 13px;">View</a>
                                        <button type="button"
                                                class="ep-btn-rec-follow {{ $followedPages->contains('id', $dPage->id) ? 'following' : '' }}"
                                                style="flex: 1;"
                                                data-page-id="{{ $dPage->id }}"
                                                onclick="epToggleFollow(this, {{ $dPage->id }})">
                                            <span class="ep-follow-icon">
                                                @include('pages.partials.icon', ['name' => $followedPages->contains('id', $dPage->id) ? 'check' : 'plus', 'size' => 13])
                                            </span>
                                            <span class="ep-follow-text">{{ $followedPages->contains('id', $dPage->id) ? 'Following' : 'Follow' }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--ep-text-secondary);">
                                No pages match the specified query.
                            </div>
                        @endforelse
                    </div>

                    @if($allPages->hasPages())
                        <div style="padding: 16px 24px; border-top: 1px solid var(--ep-border);">
                            {{ $allPages->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- TAB 3: INVITATIONS PANE -->
            <div id="tab-pane-invitations" class="ep-tab-pane" style="display: none;">
                <div class="ep-card">
                    <div class="ep-card-header">
                        <div>
                            <h2 class="ep-card-title">Page Invitations</h2>
                            <p class="ep-card-subtitle">Invitations to manage or join pages</p>
                        </div>
                    </div>
                    <div style="padding: 16px 24px;">
                        @forelse($pendingInvitations as $invitation)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid var(--ep-border); gap: 16px; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="ep-rec-avatar">
                                        @if($invitation->page && $invitation->page->avatar_url)
                                            <img src="{{ $invitation->page->avatar_url }}" alt="{{ $invitation->page->name }}">
                                        @else
                                            <div class="ep-avatar-fallback accent-0">
                                                {{ mb_strtoupper(mb_substr($invitation->page->name ?? 'P', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div style="font-size: 15px; font-weight: 700; color: var(--ep-text-title);">
                                            {{ $invitation->page->name ?? 'Page' }}
                                        </div>
                                        <div style="font-size: 13px; color: var(--ep-text-secondary);">
                                            Invited by <strong>{{ $invitation->inviter->name ?? 'Administrator' }}</strong> as <strong>{{ ucfirst($invitation->role) }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="{{ route('pages.show', $invitation->page->slug ?? '#') }}" class="ep-btn-view" style="font-size: 13px; padding: 7px 14px;">Accept / গ্রহণ করুন</a>
                                    <button type="button" class="ep-btn-manage" style="font-size: 13px; padding: 7px 14px;" onclick="this.closest('div').style.display='none'">Decline / প্রত্যাখ্যান</button>
                                </div>
                            </div>
                        @empty
                            <div class="ep-empty-pages-box">
                                <div class="ep-empty-icon-wrap">
                                    @include('pages.partials.icon', ['name' => 'mail', 'size' => 28])
                                </div>
                                <h3 class="ep-empty-title">No Pending Invitations</h3>
                                <p class="ep-empty-desc">When other users invite you to manage their pages or join page leadership, invitations will appear here.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- TAB 4: NOTIFICATIONS PANE -->
            <div id="tab-pane-notifications" class="ep-tab-pane" style="display: none;">
                <div class="ep-card">
                    <div class="ep-card-header">
                        <div>
                            <h2 class="ep-card-title">Page Notifications</h2>
                            <p class="ep-card-subtitle">Activity, updates, and interactions on your pages</p>
                        </div>
                    </div>
                    <div style="padding: 12px 20px;">
                        @forelse($pageNotifications as $notif)
                            <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--ep-border);">
                                <div class="ep-activity-icon-wrap follower">
                                    @include('pages.partials.icon', ['name' => 'bell', 'size' => 16])
                                </div>
                                <div style="flex: 1;">
                                    <p style="margin: 0 0 2px 0; font-size: 13.5px; color: var(--ep-text-title); font-weight: 500;">
                                        {{ $notif->data['message'] ?? 'New notification on your page.' }}
                                    </p>
                                    <span style="font-size: 12px; color: var(--ep-text-muted);">
                                        {{ $notif->created_at ? $notif->created_at->diffForHumans() : 'Recently' }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="ep-empty-pages-box">
                                <div class="ep-empty-icon-wrap">
                                    @include('pages.partials.icon', ['name' => 'bell', 'size' => 28])
                                </div>
                                <h3 class="ep-empty-title">All Caught Up</h3>
                                <p class="ep-empty-desc">You don't have any unread page notifications right now.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- TAB 5: SAVED PANE -->
            <div id="tab-pane-saved" class="ep-tab-pane" style="display: none;">
                <div class="ep-card">
                    <div class="ep-card-header">
                        <div>
                            <h2 class="ep-card-title">Followed & Saved Pages</h2>
                            <p class="ep-card-subtitle">Pages you currently follow in your network</p>
                        </div>
                    </div>
                    <div style="padding: 16px 24px;">
                        @forelse($followedPages as $idx => $fPage)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid var(--ep-border); gap: 16px; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 14px;">
                                    <div class="ep-rec-avatar">
                                        @if($fPage->avatar_url)
                                            <img src="{{ $fPage->avatar_url }}" alt="{{ $fPage->name }}">
                                        @else
                                            <div class="ep-avatar-fallback accent-{{ $idx % 4 }}">
                                                {{ mb_strtoupper(mb_substr($fPage->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('pages.show', $fPage->slug) }}" style="font-size: 15px; font-weight: 700; color: var(--ep-text-title); text-decoration: none;">
                                            {{ $fPage->name }}
                                        </a>
                                        <div style="font-size: 12.5px; color: var(--ep-text-secondary);">
                                            {{ $fPage->category ?: 'Community' }} • {{ $formatMetric($fPage->followers_count ?? 0) }} followers
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="{{ route('pages.show', $fPage->slug) }}" class="ep-btn-manage" style="font-size: 13px;">View Page</a>
                                    <button type="button"
                                            class="ep-btn-rec-follow following"
                                            data-page-id="{{ $fPage->id }}"
                                            onclick="epToggleFollow(this, {{ $fPage->id }})">
                                        <span class="ep-follow-icon">@include('pages.partials.icon', ['name' => 'check', 'size' => 13])</span>
                                        <span class="ep-follow-text">Following</span>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="ep-empty-pages-box">
                                <div class="ep-empty-icon-wrap">
                                    @include('pages.partials.icon', ['name' => 'bookmark', 'size' => 28])
                                </div>
                                <h3 class="ep-empty-title">No Saved or Followed Pages</h3>
                                <p class="ep-empty-desc">When you follow public pages across the network, they will be listed here for quick access.</p>
                                <button type="button" class="ep-btn-header-create" onclick="epSwitchTab('discover')">
                                    <span>Discover Pages</span>
                                </button>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </main>

        <!-- ═════════════════════════════════════════════════════════ -->
        <!-- 3. RIGHT SIDEBAR                                          -->
        <!-- ═════════════════════════════════════════════════════════ -->
        <aside class="ep-sidebar-right" aria-label="Dashboard Performance and Tools">
            <!-- Card 1: Page Performance -->
            <div class="ep-card">
                <div class="ep-card-header-compact">
                    <h2 class="ep-sidebar-card-title">Page Performance</h2>
                    <select id="epTimeRangeSelect" class="ep-time-range-select" onchange="epChangeTimeRange(this.value)">
                        <option value="7d" selected>Last 7 days</option>
                        <option value="30d">Last 30 days</option>
                        <option value="90d">Last 90 days</option>
                    </select>
                </div>
                <div class="ep-performance-metrics-grid">
                    <!-- Metric 1: Reach -->
                    <div class="ep-perf-metric-box">
                        <div class="ep-perf-icon-wrap blue">
                            @include('pages.partials.icon', ['name' => 'eye', 'size' => 16])
                        </div>
                        <span class="ep-perf-label">Post Reach</span>
                        <div class="ep-perf-value-wrap">
                            <span class="ep-perf-value" id="epPerfReach">{{ $formatMetric($performanceData['7d']['reach'] ?? 0) }}</span>
                            @php $rg = $performanceData['7d']['reach_growth'] ?? 0; @endphp
                            <span class="ep-perf-trend {{ $rg > 0 ? 'green' : ($rg < 0 ? 'red' : 'neutral') }}" id="epPerfReachGrowth">
                                {{ $rg > 0 ? '↑ ' . $rg . '%' : ($rg < 0 ? '↓ ' . abs($rg) . '%' : '—') }}
                            </span>
                        </div>
                    </div>

                    <!-- Metric 2: Engagement -->
                    <div class="ep-perf-metric-box">
                        <div class="ep-perf-icon-wrap blue">
                            @include('pages.partials.icon', ['name' => 'bullhorn', 'size' => 16])
                        </div>
                        <span class="ep-perf-label">Engagement</span>
                        <div class="ep-perf-value-wrap">
                            <span class="ep-perf-value" id="epPerfEngagement">{{ $formatMetric($performanceData['7d']['engagement'] ?? 0) }}</span>
                            @php $eg = $performanceData['7d']['engagement_growth'] ?? 0; @endphp
                            <span class="ep-perf-trend {{ $eg > 0 ? 'green' : ($eg < 0 ? 'red' : 'neutral') }}" id="epPerfEngagementGrowth">
                                {{ $eg > 0 ? '↑ ' . $eg . '%' : ($eg < 0 ? '↓ ' . abs($eg) . '%' : '—') }}
                            </span>
                        </div>
                    </div>

                    <!-- Metric 3: Followers -->
                    <div class="ep-perf-metric-box">
                        <div class="ep-perf-icon-wrap blue">
                            @include('pages.partials.icon', ['name' => 'user-plus', 'size' => 16])
                        </div>
                        <span class="ep-perf-label">New Followers</span>
                        <div class="ep-perf-value-wrap">
                            <span class="ep-perf-value" id="epPerfFollowers">{{ $formatMetric($performanceData['7d']['new_followers'] ?? 0) }}</span>
                            @php $fg = $performanceData['7d']['followers_growth'] ?? 0; @endphp
                            <span class="ep-perf-trend {{ $fg > 0 ? 'green' : ($fg < 0 ? 'red' : 'neutral') }}" id="epPerfFollowersGrowth">
                                {{ $fg > 0 ? '↑ ' . $fg . '%' : ($fg < 0 ? '↓ ' . abs($fg) . '%' : '—') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Recent Activity -->
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
                            <button type="button" class="ep-btn-activity-more" aria-label="Activity Options">
                                @include('pages.partials.icon', ['name' => 'more-horizontal', 'size' => 14])
                            </button>
                        </div>
                    @empty
                        <div style="padding: 16px; text-align: center; color: var(--ep-text-muted); font-size: 13px;">
                            No recent activity recorded yet.
                        </div>
                    @endforelse
                </div>
                <div class="ep-activity-footer">
                    <a href="javascript:void(0)" class="ep-link-view-all" onclick="epSwitchTab('notifications')">
                        <span>View All Activity</span>
                        @include('pages.partials.icon', ['name' => 'arrow-right', 'size' => 13])
                    </a>
                </div>
            </div>

            <!-- Card 3: Quick Actions -->
            <div class="ep-card">
                <div class="ep-card-header-compact">
                    <h2 class="ep-sidebar-card-title">Quick Actions</h2>
                </div>
                <div class="ep-quick-actions-list">
                    <button type="button" class="ep-quick-action-row" onclick="epOpenCreatePageModal()" style="width: 100%; border: none; background: transparent; cursor: pointer; text-align: left;">
                        <div class="ep-qa-icon-wrap blue">
                            @include('pages.partials.icon', ['name' => 'plus-circle', 'size' => 18])
                        </div>
                        <div class="ep-qa-body">
                            <span class="ep-qa-title">Create New Page</span>
                            <span class="ep-qa-desc">Start a new page for your business, brand, or community.</span>
                        </div>
                        <span class="ep-qa-chevron">
                            @include('pages.partials.icon', ['name' => 'chevron-right', 'size' => 16])
                        </span>
                    </button>

                    @php
                        $firstManagedPage = $managedPages->first();
                        $manageHref = $firstManagedPage ? route('pages.manage', $firstManagedPage->slug) : 'javascript:epOpenCreatePageModal()';
                    @endphp

                    <a href="{{ $manageHref }}" class="ep-quick-action-row">
                        <div class="ep-qa-icon-wrap blue">
                            @include('pages.partials.icon', ['name' => 'settings', 'size' => 18])
                        </div>
                        <div class="ep-qa-body">
                            <span class="ep-qa-title">Manage Page</span>
                            <span class="ep-qa-desc">Edit settings, roles, and permissions.</span>
                        </div>
                        <span class="ep-qa-chevron">
                            @include('pages.partials.icon', ['name' => 'chevron-right', 'size' => 16])
                        </span>
                    </a>

                    <a href="{{ $firstManagedPage ? route('pages.manage', ['slug' => $firstManagedPage->slug, 'tab' => 'overview']) : 'javascript:epOpenCreatePageModal()' }}" class="ep-quick-action-row">
                        <div class="ep-qa-icon-wrap blue">
                            @include('pages.partials.icon', ['name' => 'bar-chart-2', 'size' => 18])
                        </div>
                        <div class="ep-qa-body">
                            <span class="ep-qa-title">Page Insights</span>
                            <span class="ep-qa-desc">View detailed analytics and performance.</span>
                        </div>
                        <span class="ep-qa-chevron">
                            @include('pages.partials.icon', ['name' => 'chevron-right', 'size' => 16])
                        </span>
                    </a>

                    <a href="{{ $firstManagedPage ? route('pages.manage', ['slug' => $firstManagedPage->slug, 'tab' => 'settings']) : 'javascript:epOpenCreatePageModal()' }}" class="ep-quick-action-row">
                        <div class="ep-qa-icon-wrap blue">
                            @include('pages.partials.icon', ['name' => 'shield', 'size' => 18])
                        </div>
                        <div class="ep-qa-body">
                            <span class="ep-qa-title">Page Settings</span>
                            <span class="ep-qa-desc">Configure page preferences.</span>
                        </div>
                        <span class="ep-qa-chevron">
                            @include('pages.partials.icon', ['name' => 'chevron-right', 'size' => 16])
                        </span>
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Floating Toast Container -->
<div id="epToast" class="ep-toast" style="display: none;" role="status">
    <span id="epToastText"></span>
</div>

<!-- ═════════════════════════════════════════════════════════ -->
<!-- CREATE PAGE MODAL                                         -->
<!-- ═════════════════════════════════════════════════════════ -->
<div id="epCreatePageModal" class="ep-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="epCreatePageModalTitle">
    <div class="ep-modal-dialog" style="max-width: 680px; width: 100%;">
        <div class="ep-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="ep-stat-icon-circle blue" style="width: 32px; height: 32px;">
                    @include('pages.partials.icon', ['name' => 'plus', 'size' => 16])
                </div>
                <h3 class="ep-modal-title" id="epCreatePageModalTitle">Create New Page</h3>
            </div>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseCreatePageModal()" aria-label="Close modal">&times;</button>
        </div>

        <form id="epCreatePageForm" onsubmit="epSubmitCreatePage(event)" style="padding: 20px 24px;">
            <div id="epCreatePageAlert" class="alert alert-danger" style="display: none; margin-bottom: 16px; padding: 10px 14px; border-radius: 8px; font-size: 13px; background: #FEF2F2; color: #991B1B; border: 1px solid #FCA5A5;"></div>

            <!-- Page Name -->
            <div class="ep-form-group">
                <label class="ep-form-label" for="epNewPageName">Page Name <span style="color: #ef4444;">*</span></label>
                <input type="text" id="epNewPageName" name="name" class="ep-form-control" placeholder="e.g. Acme Tech Solutions, Bengal Foods" required autocomplete="off" oninput="epHandlePageNameInput(this.value)">
                <span style="font-size: 12px; color: var(--ep-text-secondary); margin-top: 4px; display: block;">Use the name of your business, brand, or organization, or a name that helps explain your page.</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Category -->
                <div class="ep-form-group">
                    <label class="ep-form-label" for="epNewPageCategory">Category <span style="color: #ef4444;">*</span></label>
                    <select id="epNewPageCategory" name="category" class="ep-form-control" required onchange="epUpdatePreviewCategory(this.value)">
                        <option value="">Select a category</option>
                        <option value="Business & Software">Business & Software</option>
                        <option value="Technology">Technology</option>
                        <option value="Brand & Product">Brand & Product</option>
                        <option value="Community & Non-Profit">Community & Non-Profit</option>
                        <option value="Media & News Company">Media & News Company</option>
                        <option value="Entertainment & Creator">Entertainment & Creator</option>
                        <option value="Education">Education</option>
                        <option value="Fashion & Apparel">Fashion & Apparel</option>
                        <option value="Retail & E-commerce">Retail & E-commerce</option>
                        <option value="Public Figure">Public Figure</option>
                        <option value="Organization">Organization</option>
                    </select>
                </div>

                <!-- Username / URL Slug -->
                <div class="ep-form-group">
                    <label class="ep-form-label" for="epNewPageUsername">Username / Handle <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 10px; top: 9px; font-size: 13px; color: var(--ep-text-muted); font-weight: 600;">@</span>
                        <input type="text" id="epNewPageUsername" name="username" class="ep-form-control" style="padding-left: 26px;" placeholder="acmetech" required autocomplete="off" oninput="epHandleUsernameInput(this.value)">
                    </div>
                    <span id="epSlugCheckStatus" style="font-size: 11.5px; margin-top: 4px; display: block; color: var(--ep-text-secondary);">Direct URL: jugajug.com/pages/<span id="epSlugPreviewText">...</span></span>
                </div>
            </div>

            <!-- Description / Bio -->
            <div class="ep-form-group">
                <label class="ep-form-label" for="epNewPageBio">Description</label>
                <textarea id="epNewPageBio" name="bio" class="ep-form-control" rows="3" placeholder="Tell people what your page is about..." oninput="epUpdatePreviewBio(this.value)"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Profile Avatar Image -->
                <div class="ep-form-group">
                    <label class="ep-form-label" for="epNewPageAvatar">Profile Picture</label>
                    <input type="file" id="epNewPageAvatar" accept="image/*" class="ep-form-control" onchange="epPreviewImage(this, 'epAvatarPreviewImg')">
                    <span style="font-size: 11.5px; color: var(--ep-text-secondary); margin-top: 4px; display: block;">Square PNG or JPG recommended.</span>
                </div>

                <!-- Cover Image -->
                <div class="ep-form-group">
                    <label class="ep-form-label" for="epNewPageCover">Cover Photo</label>
                    <input type="file" id="epNewPageCover" accept="image/*" class="ep-form-control" onchange="epPreviewImage(this, 'epCoverPreviewImg')">
                    <span style="font-size: 11.5px; color: var(--ep-text-secondary); margin-top: 4px; display: block;">Wide panoramic banner.</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Website -->
                <div class="ep-form-group">
                    <label class="ep-form-label" for="epNewPageWebsite">Website URL</label>
                    <input type="url" id="epNewPageWebsite" name="website" class="ep-form-control" placeholder="https://example.com" autocomplete="off">
                </div>

                <!-- Contact Email -->
                <div class="ep-form-group">
                    <label class="ep-form-label" for="epNewPageEmail">Contact Email</label>
                    <input type="email" id="epNewPageEmail" name="email" class="ep-form-control" placeholder="contact@example.com" autocomplete="off">
                </div>
            </div>

            <!-- Live Card Preview -->
            <div style="margin-top: 10px; margin-bottom: 20px; padding: 14px; background: var(--ep-bg-main); border: 1px solid var(--ep-border); border-radius: var(--ep-radius-sm);">
                <span style="font-size: 11.5px; font-weight: 700; color: var(--ep-text-secondary); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Live Page Identity Preview</span>
                <div style="border-radius: 8px; overflow: hidden; background: var(--ep-card-bg); border: 1px solid var(--ep-border);">
                    <div style="height: 70px; background: linear-gradient(135deg, #1E293B, #0F172A); position: relative;">
                        <img id="epCoverPreviewImg" src="" alt="Cover preview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; bottom: -18px; left: 16px; width: 44px; height: 44px; border-radius: 8px; border: 2px solid #fff; overflow: hidden; background: #fff;">
                            <img id="epAvatarPreviewImg" src="" alt="Avatar preview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                            <div id="epAvatarFallbackPreview" class="ep-avatar-fallback accent-0" style="width: 100%; height: 100%;">
                                P
                            </div>
                        </div>
                    </div>
                    <div style="padding: 24px 16px 14px;">
                        <div id="epPreviewNameText" style="font-size: 15px; font-weight: 800; color: var(--ep-text-title);">Your Page Name</div>
                        <div style="font-size: 12px; color: var(--ep-text-secondary);">
                            <span id="epPreviewCategoryText">Category</span> • <span id="epPreviewHandleText">@username</span>
                        </div>
                        <p id="epPreviewBioText" style="font-size: 12.5px; color: var(--ep-text-body); margin: 6px 0 0 0; line-height: 1.4;">Tell people what your page is about...</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Controls -->
            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--ep-border); padding-top: 16px;">
                <button type="button" class="ep-btn-view" onclick="epCloseCreatePageModal()" style="padding: 8px 18px;">Cancel</button>
                <button type="submit" id="epBtnCreatePageSubmit" class="ep-btn-manage" style="padding: 8px 24px; font-weight: 700; background: var(--ep-brand-blue); color: #fff; border: none;">
                    <span id="epCreatePageSubmitText">+ Create Page</span>
                    <span id="epCreatePageSpinner" style="display: none;">Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═════════════════════════════════════════════════════════ -->
<!-- INVITE TEAM MEMBER MODAL                                   -->
<!-- ═════════════════════════════════════════════════════════ -->
<div id="epInvitePeopleModal" class="ep-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="epInviteModalTitle">
    <div class="ep-modal-dialog" style="max-width: 480px; width: 100%;">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title" id="epInviteModalTitle">Invite Team Member</h3>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseInviteModal()" aria-label="Close modal">&times;</button>
        </div>
        <form onsubmit="epSubmitInvite(event)" style="padding: 20px 22px;">
            <input type="hidden" id="epInviteTargetPageId" value="">
            <p style="font-size: 13.5px; color: var(--ep-text-secondary); margin-top: 0; margin-bottom: 14px;">
                Invite a user to help manage <strong id="epInviteTargetPageName" style="color: var(--ep-text-title);">this page</strong>.
            </p>
            <div class="ep-form-group">
                <label class="ep-form-label" for="epInviteUserIdentifier">User Handle or Email <span style="color: #ef4444;">*</span></label>
                <input type="text" id="epInviteUserIdentifier" class="ep-form-control" placeholder="e.g. @rahim or rahim@example.com" required autocomplete="off">
            </div>
            <div class="ep-form-group">
                <label class="ep-form-label" for="epInviteRoleSelect">Assigned Role</label>
                <select id="epInviteRoleSelect" class="ep-form-control">
                    <option value="administrator">Administrator (Full settings & publishing)</option>
                    <option value="editor">Editor (Create, edit, & publish posts)</option>
                    <option value="moderator" selected>Moderator (Review comments & moderate)</option>
                    <option value="analyst">Analyst (View-only analytics & reports)</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="ep-btn-view" onclick="epCloseInviteModal()">Cancel</button>
                <button type="submit" id="epBtnSendInvite" class="ep-btn-manage" style="background: var(--ep-brand-blue); color: #fff; border: none;">Send Invitation</button>
            </div>
        </form>
    </div>
</div>

<!-- ═════════════════════════════════════════════════════════ -->
<!-- DELETE PAGE MODAL                                         -->
<!-- ═════════════════════════════════════════════════════════ -->
<div id="epDeletePageModal" class="ep-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="epDeleteModalTitle">
    <div class="ep-modal-dialog" style="max-width: 440px; width: 100%;">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title" id="epDeleteModalTitle" style="color: #ef4444;">Delete Page</h3>
            <button type="button" class="ep-btn-close-modal" onclick="epCloseDeleteModal()" aria-label="Close modal">&times;</button>
        </div>
        <div style="padding: 20px 22px;">
            <input type="hidden" id="epDeleteTargetPageId" value="">
            <p style="font-size: 14px; color: var(--ep-text-title); margin-top: 0;">
                Are you sure you want to delete <strong id="epDeleteTargetPageName">this page</strong>?
            </p>
            <p style="font-size: 13px; color: var(--ep-text-secondary); line-height: 1.4;">
                This page will be moved to trash and will no longer be visible to followers. You can restore it from your enterprise settings if needed.
            </p>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
                <button type="button" class="ep-btn-view" onclick="epCloseDeleteModal()">Cancel</button>
                <button type="button" id="epBtnConfirmDelete" class="ep-btn-manage" style="background: #ef4444; color: #fff; border: none;" onclick="epConfirmDeletePage()">
                    Confirm Delete
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    /**
     * Enterprise Pages Interactive Engine
     */
    window.EP_PERF_DATA = @json($performanceData);
    window.EP_CSRF_TOKEN = '{{ csrf_token() }}';

    function epFormatNumber(num) {
        num = Number(num) || 0;
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
        return String(num);
    }

    /**
     * Switch Time Range in Performance Card
     */
    function epChangeTimeRange(rangeKey) {
        const data = window.EP_PERF_DATA[rangeKey];
        if (!data) return;

        const reachEl = document.getElementById('epPerfReach');
        const reachGrowthEl = document.getElementById('epPerfReachGrowth');
        const engEl = document.getElementById('epPerfEngagement');
        const engGrowthEl = document.getElementById('epPerfEngagementGrowth');
        const folEl = document.getElementById('epPerfFollowers');
        const folGrowthEl = document.getElementById('epPerfFollowersGrowth');

        if (reachEl) reachEl.textContent = epFormatNumber(data.reach);
        if (reachGrowthEl) {
            reachGrowthEl.textContent = data.reach_growth > 0 ? ('↑ ' + data.reach_growth + '%') : (data.reach_growth < 0 ? ('↓ ' + Math.abs(data.reach_growth) + '%') : '—');
            reachGrowthEl.className = 'ep-perf-trend ' + (data.reach_growth > 0 ? 'green' : (data.reach_growth < 0 ? 'red' : 'neutral'));
        }
        if (engEl) engEl.textContent = epFormatNumber(data.engagement);
        if (engGrowthEl) {
            engGrowthEl.textContent = data.engagement_growth > 0 ? ('↑ ' + data.engagement_growth + '%') : (data.engagement_growth < 0 ? ('↓ ' + Math.abs(data.engagement_growth) + '%') : '—');
            engGrowthEl.className = 'ep-perf-trend ' + (data.engagement_growth > 0 ? 'green' : (data.engagement_growth < 0 ? 'red' : 'neutral'));
        }
        if (folEl) folEl.textContent = epFormatNumber(data.new_followers);
        if (folGrowthEl) {
            folGrowthEl.textContent = data.followers_growth > 0 ? ('↑ ' + data.followers_growth + '%') : (data.followers_growth < 0 ? ('↓ ' + Math.abs(data.followers_growth) + '%') : '—');
            folGrowthEl.className = 'ep-perf-trend ' + (data.followers_growth > 0 ? 'green' : (data.followers_growth < 0 ? 'red' : 'neutral'));
        }
    }

    /**
     * Switch Navigation Tabs
     */
    function epSwitchTab(tabName) {
        document.querySelectorAll('.ep-tab-item').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
        });

        document.querySelectorAll('.ep-tab-pane').forEach(pane => {
            const isMatch = pane.id === ('tab-pane-' + tabName);
            pane.style.display = isMatch ? 'block' : 'none';
            pane.classList.toggle('active', isMatch);
        });
    }

    /**
     * Live search filtering on My Pages list
     */
    function epFilterPagesList(query) {
        query = (query || '').toLowerCase().trim();
        const rows = document.querySelectorAll('#epPagesRowsList .ep-page-row-item');
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const category = row.getAttribute('data-category') || '';
            const matches = !query || name.includes(query) || category.includes(query);
            row.style.display = matches ? 'grid' : 'none';
            if (matches) visibleCount++;
        });

        let noMatchEl = document.getElementById('epNoPagesSearchMatch');
        if (visibleCount === 0 && rows.length > 0) {
            if (!noMatchEl) {
                noMatchEl = document.createElement('div');
                noMatchEl.id = 'epNoPagesSearchMatch';
                noMatchEl.style.padding = '32px';
                noMatchEl.style.textAlign = 'center';
                noMatchEl.style.color = 'var(--ep-text-secondary)';
                noMatchEl.style.fontSize = '14px';
                noMatchEl.textContent = 'No pages match your search keyword.';
                document.getElementById('epPagesRowsList').appendChild(noMatchEl);
            }
            noMatchEl.style.display = 'block';
        } else if (noMatchEl) {
            noMatchEl.style.display = 'none';
        }
    }

    /**
     * Sort My Pages list
     */
    function epSortPagesList(sortBy) {
        const list = document.getElementById('epPagesRowsList');
        if (!list) return;

        const rows = Array.from(list.querySelectorAll('.ep-page-row-item'));
        rows.sort((a, b) => {
            if (sortBy === 'followers') {
                return (Number(b.getAttribute('data-followers')) || 0) - (Number(a.getAttribute('data-followers')) || 0);
            } else if (sortBy === 'recent') {
                return (Number(b.getAttribute('data-created')) || 0) - (Number(a.getAttribute('data-created')) || 0);
            } else if (sortBy === 'name') {
                return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
            }
            return 0;
        });

        rows.forEach(r => list.appendChild(r));
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

        if (!isOpen) {
            menu.classList.add('open');
        }
    }

    document.addEventListener('click', () => {
        document.querySelectorAll('.ep-popover-menu.open').forEach(m => m.classList.remove('open'));
    });

    /**
     * Sidebar More Toggle
     */
    function epToggleSidebarMore(e) {
        e.preventDefault();
        epSwitchTab('discover');
    }

    /**
     * Create Page Modal Controller
     */
    let epSlugManuallyEdited = false;

    function epOpenCreatePageModal() {
        const modal = document.getElementById('epCreatePageModal');
        if (!modal) {
            window.location.href = '{{ route('pages.create') }}';
            return;
        }
        document.getElementById('epCreatePageForm').reset();
        document.getElementById('epCreatePageAlert').style.display = 'none';
        document.getElementById('epCoverPreviewImg').style.display = 'none';
        document.getElementById('epAvatarPreviewImg').style.display = 'none';
        const fallback = document.getElementById('epAvatarFallbackPreview');
        if (fallback) {
            fallback.style.display = 'flex';
            fallback.textContent = 'P';
        }
        document.getElementById('epPreviewNameText').textContent = 'Your Page Name';
        document.getElementById('epPreviewCategoryText').textContent = 'Category';
        document.getElementById('epPreviewHandleText').textContent = '@username';
        document.getElementById('epPreviewBioText').textContent = 'Tell people what your page is about...';
        document.getElementById('epSlugPreviewText').textContent = '...';
        epSlugManuallyEdited = false;
        modal.style.display = 'flex';
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function epCloseCreatePageModal() {
        const modal = document.getElementById('epCreatePageModal');
        if (!modal) return;
        modal.style.display = 'none';
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    function epHandlePageNameInput(val) {
        const name = (val || '').trim();
        document.getElementById('epPreviewNameText').textContent = name || 'Your Page Name';
        const fallback = document.getElementById('epAvatarFallbackPreview');
        if (fallback) fallback.textContent = (name ? name.charAt(0).toUpperCase() : 'P');

        if (!epSlugManuallyEdited) {
            const autoSlug = name.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            document.getElementById('epNewPageUsername').value = autoSlug;
            document.getElementById('epPreviewHandleText').textContent = '@' + (autoSlug || 'username');
            document.getElementById('epSlugPreviewText').textContent = autoSlug || '...';
        }
    }

    function epHandleUsernameInput(val) {
        epSlugManuallyEdited = true;
        const clean = (val || '').toLowerCase().replace(/[^a-z0-9_-]/g, '');
        document.getElementById('epNewPageUsername').value = clean;
        document.getElementById('epPreviewHandleText').textContent = '@' + (clean || 'username');
        document.getElementById('epSlugPreviewText').textContent = clean || '...';
    }

    function epUpdatePreviewCategory(val) {
        document.getElementById('epPreviewCategoryText').textContent = val || 'Category';
    }

    function epUpdatePreviewBio(val) {
        document.getElementById('epPreviewBioText').textContent = val || 'Tell people what your page is about...';
    }

    function epPreviewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(previewId);
                if (img) {
                    img.src = e.target.result;
                    img.style.display = 'block';
                    if (previewId === 'epAvatarPreviewImg') {
                        const fallback = document.getElementById('epAvatarFallbackPreview');
                        if (fallback) fallback.style.display = 'none';
                    }
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    async function epSubmitCreatePage(e) {
        e.preventDefault();
        const form = document.getElementById('epCreatePageForm');
        const alertBox = document.getElementById('epCreatePageAlert');
        const submitBtn = document.getElementById('epBtnCreatePageSubmit');
        const submitText = document.getElementById('epCreatePageSubmitText');
        const spinner = document.getElementById('epCreatePageSpinner');

        alertBox.style.display = 'none';
        submitBtn.disabled = true;
        submitText.style.display = 'none';
        spinner.style.display = 'inline';

        const name = document.getElementById('epNewPageName').value.trim();
        const category = document.getElementById('epNewPageCategory').value;
        const username = document.getElementById('epNewPageUsername').value.trim();
        const bio = document.getElementById('epNewPageBio').value.trim();
        const website = document.getElementById('epNewPageWebsite').value.trim();
        const email = document.getElementById('epNewPageEmail').value.trim();

        try {
            const res = await fetch('/api/v2/pages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.EP_CSRF_TOKEN
                },
                body: JSON.stringify({
                    name: name,
                    category: category,
                    username: username,
                    bio: bio,
                    website: website || null,
                    email: email || null
                })
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Failed to create page.'));
            }

            const page = data.data;

            // Optional image uploads if selected
            const avatarInput = document.getElementById('epNewPageAvatar');
            if (avatarInput.files && avatarInput.files[0]) {
                const avatarData = new FormData();
                avatarData.append('avatar', avatarInput.files[0]);
                await fetch('/api/v2/pages/' + page.id + '/avatar', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.EP_CSRF_TOKEN, 'Accept': 'application/json' },
                    body: avatarData
                }).catch(() => {});
            }

            const coverInput = document.getElementById('epNewPageCover');
            if (coverInput.files && coverInput.files[0]) {
                const coverData = new FormData();
                coverData.append('cover', coverInput.files[0]);
                await fetch('/api/v2/pages/' + page.id + '/cover', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.EP_CSRF_TOKEN, 'Accept': 'application/json' },
                    body: coverData
                }).catch(() => {});
            }

            // Update UI dynamically
            epInsertNewPageRow(page);
            epInsertSidebarPage(page);

            const countEl = document.getElementById('epStatTotalPages');
            if (countEl) countEl.textContent = (Number(countEl.textContent) || 0) + 1;

            epCloseCreatePageModal();
            epShowToast('Page "' + page.name + '" created successfully!');
        } catch (err) {
            alertBox.textContent = err.message || 'Error creating page. Please check your inputs.';
            alertBox.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            submitText.style.display = 'inline';
            spinner.style.display = 'none';
        }
    }

    function epInsertNewPageRow(page) {
        const list = document.getElementById('epPagesRowsList');
        if (!list) return;

        const empty = list.querySelector('.ep-empty-pages-box');
        if (empty) empty.remove();

        const row = document.createElement('div');
        row.className = 'ep-page-row-item';
        row.id = 'page-row-' + page.id;
        row.setAttribute('data-name', (page.name || '').toLowerCase());
        row.setAttribute('data-category', (page.category || '').toLowerCase());
        row.setAttribute('data-followers', '0');
        row.setAttribute('data-created', String(Math.floor(Date.now() / 1000)));

        row.innerHTML = `
            <div class="ep-page-thumbnail-box">
                <div class="ep-row-cover-placeholder"><span>${page.name}</span></div>
                <div class="ep-row-avatar-badge">
                    <div class="ep-avatar-fallback accent-0" style="width: 100%; height: 100%;">
                        ${page.name.charAt(0).toUpperCase()}
                    </div>
                </div>
            </div>
            <div class="ep-page-details-box">
                <div class="ep-page-name-row">
                    <a href="/pages/${page.slug}" class="ep-page-title-link">${page.name}</a>
                </div>
                <div class="ep-page-meta-row">
                    <span class="ep-page-category">${page.category || 'Organization'}</span>
                    <span class="ep-meta-bullet">•</span>
                    <span class="ep-page-followers">0 followers</span>
                </div>
                <p class="ep-page-short-desc">${page.bio || 'New page created.'}</p>
            </div>
            <div class="ep-page-actions-box">
                <a href="/pages/${page.slug}/manage" class="ep-btn-manage">Manage</a>
                <a href="/pages/${page.slug}" class="ep-btn-view">View Page</a>
            </div>
        `;
        list.prepend(row);
    }

    function epInsertSidebarPage(page) {
        const sideList = document.getElementById('epSidebarPagesList');
        if (!sideList) return;

        const emptyNotice = sideList.querySelector('div[style*="font-size: 13px"]');
        if (emptyNotice) emptyNotice.remove();

        const item = document.createElement('div');
        item.className = 'ep-sidebar-page-item';
        item.id = 'side-item-' + page.id;
        item.innerHTML = `
            <a href="/pages/${page.slug}" class="ep-sidebar-page-link" title="${page.name}">
                <div class="ep-sidebar-page-avatar">
                    <div class="ep-avatar-fallback accent-0">${page.name.charAt(0).toUpperCase()}</div>
                </div>
                <span class="ep-sidebar-page-name">${page.name}</span>
            </a>
            <div class="ep-dropdown-wrap">
                <button type="button" class="ep-btn-more-dots" onclick="epToggleMenu(event, 'side-page-${page.id}')" aria-label="More options">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                </button>
                <div id="side-page-${page.id}" class="ep-popover-menu">
                    <a href="/pages/${page.slug}"><span>View Page</span></a>
                    <a href="/pages/${page.slug}/manage"><span>Manage</span></a>
                </div>
            </div>
        `;
        sideList.prepend(item);
    }

    /**
     * Invite Modal Controller
     */
    function epOpenInviteModal(pageId, pageName) {
        document.getElementById('epInviteTargetPageId').value = pageId;
        document.getElementById('epInviteTargetPageName').textContent = pageName;
        document.getElementById('epInviteUserIdentifier').value = '';
        const modal = document.getElementById('epInvitePeopleModal');
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    }

    function epCloseInviteModal() {
        const modal = document.getElementById('epInvitePeopleModal');
        if (!modal) return;
        modal.style.display = 'none';
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    async function epSubmitInvite(e) {
        e.preventDefault();
        const pageId = document.getElementById('epInviteTargetPageId').value;
        const identifier = document.getElementById('epInviteUserIdentifier').value.trim();
        const role = document.getElementById('epInviteRoleSelect').value;
        const btn = document.getElementById('epBtnSendInvite');

        btn.disabled = true;
        try {
            const res = await fetch('/api/v2/pages/' + pageId + '/team', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.EP_CSRF_TOKEN
                },
                body: JSON.stringify({ identifier: identifier, role: role })
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Failed to send invitation.');
            }
            epCloseInviteModal();
            epShowToast('Invitation sent successfully!');
        } catch (err) {
            alert(err.message || 'Error sending invitation.');
        } finally {
            btn.disabled = false;
        }
    }

    /**
     * Delete Modal Controller
     */
    function epOpenDeleteModal(pageId, pageName) {
        document.getElementById('epDeleteTargetPageId').value = pageId;
        document.getElementById('epDeleteTargetPageName').textContent = pageName;
        const modal = document.getElementById('epDeletePageModal');
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    }

    function epCloseDeleteModal() {
        const modal = document.getElementById('epDeletePageModal');
        if (!modal) return;
        modal.style.display = 'none';
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    async function epConfirmDeletePage() {
        const pageId = document.getElementById('epDeleteTargetPageId').value;
        const btn = document.getElementById('epBtnConfirmDelete');
        btn.disabled = true;

        try {
            const res = await fetch('/api/v2/pages/' + pageId, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.EP_CSRF_TOKEN
                }
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Failed to delete page.');
            }

            const row = document.getElementById('page-row-' + pageId) || document.querySelector('[data-page-id="' + pageId + '"]');
            if (row) row.remove();

            const sideItem = document.getElementById('side-item-' + pageId);
            if (sideItem) sideItem.remove();

            const countEl = document.getElementById('epStatTotalPages');
            if (countEl) countEl.textContent = Math.max(0, (Number(countEl.textContent) || 1) - 1);

            epCloseDeleteModal();
            epShowToast('Page moved to trash.');
        } catch (err) {
            alert(err.message || 'Error deleting page.');
        } finally {
            btn.disabled = false;
        }
    }

    /**
     * AJAX Follow Toggle
     */
    function epToggleFollow(btn, pageId) {
        btn.disabled = true;
        fetch('/pages/' + pageId + '/follow', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.EP_CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (res.status === 401) {
                window.location.href = '{{ route('login') }}';
                return null;
            }
            return res.json();
        })
        .then(data => {
            btn.disabled = false;
            if (!data || !data.success) return;

            const isFollowing = !!data.is_following;
            btn.classList.toggle('following', isFollowing);

            const iconSpan = btn.querySelector('.ep-follow-icon');
            const textSpan = btn.querySelector('.ep-follow-text');

            if (textSpan) textSpan.textContent = isFollowing ? 'Following' : '+ Follow';
            if (iconSpan) {
                iconSpan.innerHTML = isFollowing
                    ? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
                    : '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
            }

            // Update card follower count text if present
            const card = btn.closest('.ep-rec-card') || btn.closest('.ep-card');
            if (card && typeof data.followers_count !== 'undefined') {
                const countMeta = card.querySelector('.ep-rec-category') || card.querySelector('div[style*="font-size: 12.5px"]');
                if (countMeta && countMeta.textContent.includes('followers')) {
                    countMeta.textContent = countMeta.textContent.replace(/\d+.*followers/, epFormatNumber(data.followers_count) + ' followers');
                }
            }

            epShowToast(isFollowing ? 'Page followed successfully!' : 'Page unfollowed.');
        })
        .catch(err => {
            btn.disabled = false;
            console.error('Follow toggle error:', err);
        });
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
            navigator.share({
                title: title,
                text: 'Check out ' + title + ' on Jugajug',
                url: url
            }).catch(() => {});
        } else {
            epCopyLink(url);
        }
    }

    /**
     * Notification Toast
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
</script>
@endpush
@endsection
