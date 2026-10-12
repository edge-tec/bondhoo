@extends('layouts.app')

@section('title', 'পেইজসমূহ — Bondhoo Enterprise Social Pages')

@section('styles')
<link rel="stylesheet" href="/css/enterprise-pages-redesign.css?v={{ time() }}">
@endsection

@section('content')
<div class="ep-container" style="max-width: 1240px; margin: 0 auto; padding: 0 16px 40px 16px;">

    <!-- 1. EXECUTIVE DASHBOARD HERO BANNER -->
    <div class="ep-dashboard-hero">
        <div>
            <h1 class="ep-dashboard-hero-title">
                @include('pages.partials.icon', ['name' => 'page', 'size' => 26])
                <span>পেইজ ও পাবলিক এন্টারপ্রাইজ হাব</span>
            </h1>
            <p class="ep-dashboard-hero-desc">
                আপনার ব্র্যান্ড, ব্যবসা, সংগঠন বা পাবলিক প্রেজেন্সের জন্য প্রফেশনাল পেইজ পরিচালনা করুন এবং নতুন অডিয়েন্সের সাথে যুক্ত হন।
            </p>
        </div>
        <div class="ep-hero-actions">
            <a href="{{ route('pages.create') }}" class="ep-btn-create-hero">
                @include('pages.partials.icon', ['name' => 'plus', 'size' => 16, 'strokeWidth' => 2.5])
                <span>নতুন পেইজ তৈরি করুন</span>
            </a>
        </div>
    </div>

    <!-- 2. OVERVIEW METRICS DECK -->
    <div class="ep-overview-grid">
        <div class="ep-stat-card">
            <div class="ep-stat-icon-wrap">
                @include('pages.partials.icon', ['name' => 'compass', 'size' => 22])
            </div>
            <div class="ep-stat-body">
                <div class="ep-stat-number">{{ number_format($totalPagesCount ?? $allPages->total()) }}</div>
                <div class="ep-stat-label">ইকোসিস্টেমের মোট পেইজ</div>
            </div>
        </div>

        <div class="ep-stat-card">
            <div class="ep-stat-icon-wrap indigo">
                @include('pages.partials.icon', ['name' => 'briefcase', 'size' => 22])
            </div>
            <div class="ep-stat-body">
                <div class="ep-stat-number">{{ number_format($managedPages->count()) }}</div>
                <div class="ep-stat-label">পরিচালিত পেইজসমূহ</div>
            </div>
        </div>

        <div class="ep-stat-card">
            <div class="ep-stat-icon-wrap amber">
                @include('pages.partials.icon', ['name' => 'users', 'size' => 22])
            </div>
            <div class="ep-stat-body">
                <div class="ep-stat-number">{{ number_format($totalFollowersManaged ?? 0) }}</div>
                <div class="ep-stat-label">সর্বমোট ফলোয়ার সংখ্যা</div>
            </div>
        </div>

        <div class="ep-stat-card">
            <div class="ep-stat-icon-wrap cyan">
                @include('pages.partials.icon', ['name' => 'file-text', 'size' => 22])
            </div>
            <div class="ep-stat-body">
                <div class="ep-stat-number">{{ number_format($totalPostsManaged ?? 0) }}</div>
                <div class="ep-stat-label">পরিচালিত মোট পোস্ট</div>
            </div>
        </div>
    </div>

    <!-- 3. SEARCH & ADVANCED FILTER CONTROLS -->
    <div class="ep-controls-card">
        <form action="{{ route('pages.index') }}" method="GET">
            <div class="ep-search-row">
                <div class="ep-search-input-box">
                    <span class="ep-search-icon">
                        @include('pages.partials.icon', ['name' => 'search', 'size' => 16])
                    </span>
                    <input type="text"
                           name="q"
                           value="{{ $searchQuery }}"
                           class="ep-search-input"
                           placeholder="পেইজের নাম, ইউজারনেম বা ক্যাটাগরি দিয়ে খুঁজুন..."
                           autocomplete="off">
                </div>

                @if($selectedCategory)
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                @endif
                <input type="hidden" name="sort" value="{{ $sort }}">

                <button type="submit" class="ep-search-btn">
                    @include('pages.partials.icon', ['name' => 'search', 'size' => 15])
                    <span>অনুসন্ধান</span>
                </button>

                <div class="ep-sort-group">
                    <a href="{{ route('pages.index', array_filter(array_merge(request()->query(), ['sort' => 'popular']))) }}"
                       class="ep-sort-btn {{ $sort === 'popular' ? 'active' : '' }}">
                        @include('pages.partials.icon', ['name' => 'sparkles', 'size' => 13])
                        <span>জনপ্রিয়</span>
                    </a>
                    <a href="{{ route('pages.index', array_filter(array_merge(request()->query(), ['sort' => 'recent']))) }}"
                       class="ep-sort-btn {{ $sort === 'recent' ? 'active' : '' }}">
                        @include('pages.partials.icon', ['name' => 'calendar', 'size' => 13])
                        <span>নতুন</span>
                    </a>
                    @if($searchQuery || $selectedCategory)
                        <a href="{{ route('pages.index') }}" class="ep-sort-btn" style="color: var(--ep-danger);">
                            @include('pages.partials.icon', ['name' => 'x', 'size' => 13])
                            <span>রিসেট</span>
                        </a>
                    @endif
                </div>
            </div>

            @if(isset($categories) && $categories->isNotEmpty())
                <div class="ep-category-chips-row">
                    <span style="font-size: 12.5px; font-weight: 700; color: var(--ep-text-muted); display: inline-flex; align-items: center; gap: 4px;">
                        @include('pages.partials.icon', ['name' => 'filter', 'size' => 13])
                        <span>ক্যাটাগরি:</span>
                    </span>
                    <a href="{{ route('pages.index', array_filter(array_merge(request()->query(), ['category' => null]))) }}"
                       class="ep-cat-chip {{ !$selectedCategory ? 'active' : '' }}">
                        সকল
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('pages.index', array_filter(array_merge(request()->query(), ['category' => $cat]))) }}"
                           class="ep-cat-chip {{ $selectedCategory === $cat ? 'active' : '' }}">
                            {{ $cat }}
                        </a>
                    @endforeach
                </div>
            @endif
        </form>
    </div>

    <!-- 4. DOCK-STYLE NAVIGATION TABS -->
    <ul class="ep-nav-dock" id="epNavTabs">
        <li>
            <button type="button" class="ep-nav-tab-btn active" id="tabBtn-overview" onclick="switchDashboardTab('overview')">
                @include('pages.partials.icon', ['name' => 'compass', 'size' => 16])
                <span>ওভারভিউ ও সব পেইজ</span>
            </button>
        </li>
        @if($currentUser)
            <li>
                <button type="button" class="ep-nav-tab-btn" id="tabBtn-my-pages" onclick="switchDashboardTab('my-pages')">
                    @include('pages.partials.icon', ['name' => 'briefcase', 'size' => 16])
                    <span>আমার পেইজসমূহ</span>
                    <span class="ep-nav-badge">{{ $managedPages->count() }}</span>
                </button>
            </li>
            <li>
                <button type="button" class="ep-nav-tab-btn" id="tabBtn-saved" onclick="switchDashboardTab('saved')">
                    @include('pages.partials.icon', ['name' => 'bookmark', 'size' => 16])
                    <span>সংরক্ষিত ও ফলো করা</span>
                    <span class="ep-nav-badge">{{ $followedPages->count() }}</span>
                </button>
            </li>
            <li>
                <button type="button" class="ep-nav-tab-btn" id="tabBtn-invitations" onclick="switchDashboardTab('invitations')">
                    @include('pages.partials.icon', ['name' => 'mail', 'size' => 16])
                    <span>আমন্ত্রণসমূহ</span>
                    @if($pendingInvitations->isNotEmpty())
                        <span class="ep-nav-badge" id="invitationsBadge" style="background: var(--ep-danger); color: #ffffff;">{{ $pendingInvitations->count() }}</span>
                    @else
                        <span class="ep-nav-badge" id="invitationsBadge">0</span>
                    @endif
                </button>
            </li>
            <li>
                <button type="button" class="ep-nav-tab-btn" id="tabBtn-notifications" onclick="switchDashboardTab('notifications')">
                    @include('pages.partials.icon', ['name' => 'bell', 'size' => 16])
                    <span>পেইজ বিজ্ঞপ্তি</span>
                    <span class="ep-nav-badge">{{ $pageNotifications->count() }}</span>
                </button>
            </li>
        @endif
    </ul>

    <!-- 5. TAB PANES -->

    <!-- PANE: OVERVIEW & ALL PAGES -->
    <div id="pane-overview" class="ep-tab-pane">
        @if($currentUser && $managedPages->isNotEmpty() && !$searchQuery && !$selectedCategory)
            <!-- Quick Managed Pages Slider Strip -->
            <div style="margin-bottom: 28px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <h2 style="font-size: 18px; font-weight: 800; color: var(--ep-text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                        @include('pages.partials.icon', ['name' => 'briefcase', 'size' => 18])
                        <span>আপনার পরিচালিত পেইজসমূহ ({{ $managedPages->count() }})</span>
                    </h2>
                    <button type="button" onclick="switchDashboardTab('my-pages')" style="background: none; border: none; color: var(--ep-primary); font-size: 13px; font-weight: 700; cursor: pointer;">
                        সবগুলো দেখুন →
                    </button>
                </div>
                <div class="ep-pages-grid">
                    @foreach($managedPages->take(3) as $page)
                        @include('pages.partials.page-card', ['page' => $page])
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Popular & Directory Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 18px; font-weight: 800; color: var(--ep-text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                @include('pages.partials.icon', ['name' => 'compass', 'size' => 18])
                <span>{{ $searchQuery ? "অনুসন্ধান ফলাফল: '{$searchQuery}'" : 'এক্সপ্লোর ও জনপ্রিয় পেইজসমূহ' }}</span>
            </h2>
        </div>

        @if($allPages->isNotEmpty())
            <div class="ep-pages-grid" id="allPagesGrid">
                @foreach($allPages as $page)
                    @include('pages.partials.page-card', ['page' => $page])
                @endforeach
            </div>
            <div style="margin-top: 28px;">
                {{ $allPages->links() }}
            </div>
        @else
            <div class="ep-empty-state">
                <div class="ep-empty-icon-wrap">
                    @include('pages.partials.icon', ['name' => 'file-text', 'size' => 32])
                </div>
                <h3 class="ep-empty-title">কোনো পেইজ পাওয়া যায়নি</h3>
                <p class="ep-empty-desc">আপনার নির্বাচিত অনুসন্ধান বা ফিল্টারের সাথে মিলে এমন কোনো পেইজ খুঁজে পাওয়া যায়নি।</p>
                <a href="{{ route('pages.index') }}" class="ep-btn-view" style="display: inline-flex;">
                    @include('pages.partials.icon', ['name' => 'compass', 'size' => 15])
                    <span>সকল পেইজ দেখুন</span>
                </a>
            </div>
        @endif
    </div>

    @if($currentUser)
        <!-- PANE: MY MANAGED PAGES -->
        <div id="pane-my-pages" class="ep-tab-pane" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <h2 style="font-size: 18px; font-weight: 800; color: var(--ep-text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                    @include('pages.partials.icon', ['name' => 'briefcase', 'size' => 18])
                    <span>আপনার মালিকানাধীন ও পরিচালিত পেইজসমূহ</span>
                </h2>
                <a href="{{ route('pages.create') }}" class="ep-btn-follow" style="text-decoration: none; display: inline-flex; width: auto;">
                    @include('pages.partials.icon', ['name' => 'plus', 'size' => 14])
                    <span>নতুন পেইজ তৈরি</span>
                </a>
            </div>

            @if($managedPages->isNotEmpty())
                <div class="ep-pages-grid">
                    @foreach($managedPages as $page)
                        @include('pages.partials.page-card', ['page' => $page])
                    @endforeach
                </div>
            @else
                <div class="ep-empty-state">
                    <div class="ep-empty-icon-wrap">
                        @include('pages.partials.icon', ['name' => 'briefcase', 'size' => 32])
                    </div>
                    <h3 class="ep-empty-title">আপনি এখনো কোনো পেইজ পরিচালনা করছেন না</h3>
                    <p class="ep-empty-desc">একটি নতুন সোশ্যাল পেইজ খুলে আপনার ব্র্যান্ড, ব্যবসা বা সংগঠনের উপস্থিতি নিশ্চিত করুন।</p>
                    <a href="{{ route('pages.create') }}" class="ep-btn-create-hero" style="display: inline-flex; background: var(--ep-primary); color: #ffffff;">
                        @include('pages.partials.icon', ['name' => 'plus', 'size' => 16])
                        <span>প্রথম পেইজ তৈরি করুন</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- PANE: SAVED / FOLLOWED PAGES -->
        <div id="pane-saved" class="ep-tab-pane" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 18px; font-weight: 800; color: var(--ep-text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                    @include('pages.partials.icon', ['name' => 'bookmark-check', 'size' => 18])
                    <span>আপনার সংরক্ষিত ও ফলো করা পেইজসমূহ</span>
                </h2>
            </div>

            @if($followedPages->isNotEmpty())
                <div class="ep-pages-grid">
                    @foreach($followedPages as $page)
                        @include('pages.partials.page-card', ['page' => $page])
                    @endforeach
                </div>
            @else
                <div class="ep-empty-state">
                    <div class="ep-empty-icon-wrap">
                        @include('pages.partials.icon', ['name' => 'bookmark', 'size' => 32])
                    </div>
                    <h3 class="ep-empty-title">কোনো ফলো করা পেইজ নেই</h3>
                    <p class="ep-empty-desc">পাবলিক ডিরেক্টরি থেকে আপনার পছন্দের বিষয় বা ব্র্যান্ডের পেইজগুলো ফলো করে এখানে সংরক্ষণ করুন।</p>
                    <button type="button" class="ep-btn-view" onclick="switchDashboardTab('overview')" style="display: inline-flex;">
                        @include('pages.partials.icon', ['name' => 'compass', 'size' => 15])
                        <span>পেইজ এক্সপ্লোর করুন</span>
                    </button>
                </div>
            @endif
        </div>

        <!-- PANE: INVITATIONS -->
        <div id="pane-invitations" class="ep-tab-pane" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 18px; font-weight: 800; color: var(--ep-text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                    @include('pages.partials.icon', ['name' => 'mail', 'size' => 18])
                    <span>পেইজ টিম আমন্ত্রণসমূহ (Page Team Invitations)</span>
                </h2>
            </div>

            @if($pendingInvitations->isNotEmpty())
                <div style="display: flex; flex-direction: column; gap: 14px;" id="invitationsList">
                    @foreach($pendingInvitations as $invite)
                        @php
                            $pageInvited = $invite->page;
                        @endphp
                        <div class="ep-invitation-card" id="invitationCard-{{ $invite->id }}">
                            <div class="ep-invitation-info">
                                <img src="{{ $pageInvited->avatar_url ?? '/images/default-avatar.svg' }}"
                                     alt="{{ $pageInvited->name }}"
                                     class="ep-invitation-avatar"
                                     onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                <div>
                                    <div style="font-size: 16px; font-weight: 800; color: var(--ep-text-primary);">
                                        {{ $pageInvited->name }}
                                    </div>
                                    <div style="font-size: 13px; color: var(--ep-text-secondary); margin-top: 2px;">
                                        আমন্ত্রণকারী: <strong>{{ $invite->inviter->name ?? 'অ্যাডমিন' }}</strong> •
                                        নির্ধারিত রোল: <span class="badge-role" style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: #e0f2fe; color: #0284c7; font-weight: 700;">{{ strtoupper($invite->role) }}</span>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--ep-text-muted); margin-top: 4px;">
                                        {{ $invite->created_at?->diffForHumans() }}
                                    </div>
                                </div>
                            </div>

                            <div class="ep-invitation-actions">
                                <button type="button"
                                        class="ep-btn-accept"
                                        onclick="handleAcceptInvitation('{{ $invite->invitation_token }}', {{ $invite->id }}, this)">
                                    @include('pages.partials.icon', ['name' => 'check', 'size' => 14])
                                    <span>আমন্ত্রণ গ্রহণ করুন</span>
                                </button>
                                <button type="button"
                                        class="ep-btn-decline"
                                        onclick="handleDeclineInvitation('{{ $invite->invitation_token }}', {{ $invite->id }}, this)">
                                    @include('pages.partials.icon', ['name' => 'x', 'size' => 14])
                                    <span>প্রত্যাখ্যান</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="ep-empty-state">
                    <div class="ep-empty-icon-wrap">
                        @include('pages.partials.icon', ['name' => 'mail', 'size' => 32])
                    </div>
                    <h3 class="ep-empty-title">কোনো অপেক্ষমাণ আমন্ত্রণ নেই</h3>
                    <p class="ep-empty-desc">কেউ আপনাকে তাদের পেইজের দায়িত্ব বা মেম্বার হিসেবে আমন্ত্রণ জানালে তা এখানে দেখতে পাবেন।</p>
                </div>
            @endif
        </div>

        <!-- PANE: NOTIFICATIONS -->
        <div id="pane-notifications" class="ep-tab-pane" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 18px; font-weight: 800; color: var(--ep-text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                    @include('pages.partials.icon', ['name' => 'bell', 'size' => 18])
                    <span>পেইজ সংক্রান্ত নোটিফিকেশন ও সাম্প্রতিক কার্যকলাপ</span>
                </h2>
            </div>

            @if($pageNotifications->isNotEmpty())
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach($pageNotifications as $notif)
                        @php
                            $nData = $notif->data ?? [];
                            $actionUrl = $nData['action_url'] ?? ($nData['url'] ?? '/pages');
                        @endphp
                        <a href="{{ $actionUrl }}" class="ep-notification-item">
                            <div class="ep-notif-icon-circle">
                                @if(str_contains($notif->type, 'follow'))
                                    @include('pages.partials.icon', ['name' => 'user-plus', 'size' => 18])
                                @elseif(str_contains($notif->type, 'role'))
                                    @include('pages.partials.icon', ['name' => 'shield', 'size' => 18])
                                @else
                                    @include('pages.partials.icon', ['name' => 'file-text', 'size' => 18])
                                @endif
                            </div>
                            <div style="flex: 1;">
                                <div style="font-size: 14.5px; font-weight: 700; color: var(--ep-text-primary);">
                                    {{ $nData['title'] ?? 'পেইজ আপডেট' }}
                                </div>
                                <div style="font-size: 13px; color: var(--ep-text-secondary); margin-top: 3px;">
                                    {{ $nData['message'] ?? 'আপনার পেইজে নতুন কার্যক্রম রেকর্ড করা হয়েছে।' }}
                                </div>
                                <div style="font-size: 11.5px; color: var(--ep-text-muted); margin-top: 5px;">
                                    {{ $notif->created_at?->diffForHumans() }}
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="ep-empty-state">
                    <div class="ep-empty-icon-wrap">
                        @include('pages.partials.icon', ['name' => 'bell', 'size' => 32])
                    </div>
                    <h3 class="ep-empty-title">কোনো পেইজ নোটিফিকেশন নেই</h3>
                    <p class="ep-empty-desc">পেইজের নতুন ফলোয়ার, পোস্ট বা মেম্বার কার্যকলাপ সংক্রান্ত নোটিফিকেশন এখানে প্রদর্শিত হবে।</p>
                </div>
            @endif
        </div>
    @endif
</div>

<!-- SHARE MODAL -->
<div class="ep-modal-backdrop" id="epShareModal" onclick="if(event.target===this) closeSharePageModal()">
    <div class="ep-modal-card">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title">
                @include('pages.partials.icon', ['name' => 'share-2', 'size' => 18])
                <span>পেইজ শেয়ার করুন</span>
            </h3>
            <button type="button" class="ep-modal-close-btn" onclick="closeSharePageModal()">✕</button>
        </div>
        <div class="ep-modal-body">
            <p id="sharePageTitleDisplay" style="font-size: 14px; font-weight: 600; color: var(--ep-text-primary); margin-bottom: 16px;"></p>
            <div style="display: flex; gap: 8px;">
                <input type="text" id="sharePageUrlInput" readonly class="ep-form-control" style="font-size: 13px;">
                <button type="button" class="ep-btn-follow" onclick="copyShareModalUrl()" style="flex: 0 0 100px;">
                    @include('pages.partials.icon', ['name' => 'copy', 'size' => 14])
                    <span>কপি করুন</span>
                </button>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap;">
                <a href="#" id="shareWaLink" target="_blank" rel="noopener" class="ep-btn-view" style="flex: 1; justify-content: center; background: #e8f9ef; color: #15803d; border-color: #86efac;">
                    💬 WhatsApp
                </a>
                <a href="#" id="shareTgLink" target="_blank" rel="noopener" class="ep-btn-view" style="flex: 1; justify-content: center; background: #eff6ff; color: #1d4ed8; border-color: #93c5fd;">
                    ✈️ Telegram
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Real-Time Icon Mapping for dynamic JS updates
    const SVG_ICONS = {
        userPlus: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>`,
        userCheck: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>`,
        loader: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ep-spin"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>`
    };

    // Tab Switching
    function switchDashboardTab(tabId) {
        document.querySelectorAll('.ep-tab-pane').forEach(p => p.style.display = 'none');
        document.querySelectorAll('.ep-nav-tab-btn').forEach(b => b.classList.remove('active'));

        const targetPane = document.getElementById(`pane-${tabId}`);
        const targetBtn = document.getElementById(`tabBtn-${tabId}`);

        if (targetPane) targetPane.style.display = 'block';
        if (targetBtn) targetBtn.classList.add('active');

        // Close any open card dropdowns
        closeAllCardDropdowns();
    }

    // Real-Time Follow / Unfollow Toggle
    function handleRealtimeCardFollow(pageId, btn) {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif

        if (btn.disabled) return;

        const isFollowing = btn.getAttribute('data-following') === '1';
        const counterEl = document.getElementById(`card-followers-${pageId}`);
        const iconSpan = document.getElementById(`cardFollowIcon-${pageId}`);
        const textSpan = document.getElementById(`cardFollowText-${pageId}`);

        // Optimistic UI updates
        const nextFollowingState = !isFollowing;
        btn.setAttribute('data-following', nextFollowingState ? '1' : '0');
        if (nextFollowingState) {
            btn.classList.add('is-following');
            if (iconSpan) iconSpan.innerHTML = SVG_ICONS.userCheck;
            if (textSpan) textSpan.textContent = 'ফলো করা হয়েছে';
        } else {
            btn.classList.remove('is-following');
            if (iconSpan) iconSpan.innerHTML = SVG_ICONS.userPlus;
            if (textSpan) textSpan.textContent = 'ফলো করুন';
        }

        btn.disabled = true;

        fetch(`/api/v1/pages/${pageId}/follow`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            if (res.status === 'success' || res.data) {
                const count = res.data?.followers_count ?? (res.followers_count ?? null);
                if (count !== null && counterEl) {
                    counterEl.textContent = new Intl.NumberFormat('bn-BD').format(count);
                }
            } else {
                // Rollback on server rejection
                btn.setAttribute('data-following', isFollowing ? '1' : '0');
                if (isFollowing) {
                    btn.classList.add('is-following');
                    if (iconSpan) iconSpan.innerHTML = SVG_ICONS.userCheck;
                    if (textSpan) textSpan.textContent = 'ফলো করা হয়েছে';
                } else {
                    btn.classList.remove('is-following');
                    if (iconSpan) iconSpan.innerHTML = SVG_ICONS.userPlus;
                    if (textSpan) textSpan.textContent = 'ফলো করুন';
                }
                alert(res.message || 'অনুরোধটি সম্পন্ন করা যায়নি।');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.setAttribute('data-following', isFollowing ? '1' : '0');
            if (isFollowing) {
                btn.classList.add('is-following');
                if (iconSpan) iconSpan.innerHTML = SVG_ICONS.userCheck;
                if (textSpan) textSpan.textContent = 'ফলো করা হয়েছে';
            } else {
                btn.classList.remove('is-following');
                if (iconSpan) iconSpan.innerHTML = SVG_ICONS.userPlus;
                if (textSpan) textSpan.textContent = 'ফলো করুন';
            }
        });
    }

    // Card More Actions Dropdown
    function toggleCardDropdown(pageId, event) {
        event.stopPropagation();
        const menu = document.getElementById(`cardDropdown-${pageId}`);
        const isShown = menu.classList.contains('show');
        closeAllCardDropdowns();
        if (!isShown) menu.classList.add('show');
    }

    function closeAllCardDropdowns() {
        document.querySelectorAll('.ep-dropdown-menu').forEach(m => m.classList.remove('show'));
    }

    document.addEventListener('click', closeAllCardDropdowns);

    // Share & Copy Link Helpers
    function copyPageUrl(url) {
        navigator.clipboard.writeText(url).then(() => {
            alert('পেইজের লিংক সফলভাবে কপি করা হয়েছে!');
            closeAllCardDropdowns();
        }).catch(() => {
            prompt('নিচের লিংকটি কপি করুন:', url);
        });
    }

    function openSharePageModal(pageName, url) {
        closeAllCardDropdowns();
        const m = document.getElementById('epShareModal');
        document.getElementById('sharePageTitleDisplay').textContent = `${pageName} — পেইজটি বন্ধুদের সাথে শেয়ার করুন`;
        document.getElementById('sharePageUrlInput').value = url;
        document.getElementById('shareWaLink').href = `https://api.whatsapp.com/send?text=${encodeURIComponent(pageName + ' ' + url)}`;
        document.getElementById('shareTgLink').href = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(pageName)}`;
        m.classList.add('show');
    }

    function closeSharePageModal() {
        document.getElementById('epShareModal').classList.remove('show');
    }

    function copyShareModalUrl() {
        const inp = document.getElementById('sharePageUrlInput');
        copyPageUrl(inp.value);
    }

    // Accept & Decline Team Invitations with Real-Time DOM Removal
    function handleAcceptInvitation(token, inviteId, btn) {
        btn.disabled = true;
        btn.textContent = 'যুক্ত হচ্ছে...';

        fetch('/api/v2/pages/team/accept', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ token })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success || res.status === 'success') {
                const card = document.getElementById(`invitationCard-${inviteId}`);
                if (card) {
                    card.style.transition = 'opacity 0.3s, transform 0.3s';
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(-10px)';
                    setTimeout(() => card.remove(), 300);
                }
                decrementBadge('invitationsBadge');
                alert('অভিনন্দন! আপনি সফলভাবে পেইজ টিমে যুক্ত হয়েছেন।');
            } else {
                alert(res.message || 'আমন্ত্রণ গ্রহণ ব্যর্থ হয়েছে।');
                btn.disabled = false;
                btn.textContent = 'আমন্ত্রণ গ্রহণ করুন';
            }
        })
        .catch(err => {
            alert('নেটওয়ার্ক ত্রুটি ঘটেছে।');
            btn.disabled = false;
            btn.textContent = 'আমন্ত্রণ গ্রহণ করুন';
        });
    }

    function handleDeclineInvitation(token, inviteId, btn) {
        if (!confirm('আপনি কি নিশ্চিত যে এই পেইজ পরিচালনার আমন্ত্রণ প্রত্যাখ্যান করতে চান?')) return;
        btn.disabled = true;

        fetch('/api/v2/pages/team/decline', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ token })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success || res.status === 'success') {
                const card = document.getElementById(`invitationCard-${inviteId}`);
                if (card) {
                    card.style.transition = 'opacity 0.3s, transform 0.3s';
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(-10px)';
                    setTimeout(() => card.remove(), 300);
                }
                decrementBadge('invitationsBadge');
            } else {
                alert(res.message || 'প্রত্যাখ্যান সম্পন্ন করা যায়নি।');
                btn.disabled = false;
            }
        })
        .catch(err => {
            btn.disabled = false;
        });
    }

    function decrementBadge(badgeId) {
        const b = document.getElementById(badgeId);
        if (b) {
            let val = parseInt(b.textContent, 10) || 0;
            val = Math.max(0, val - 1);
            b.textContent = val;
            if (val === 0) b.style.background = 'rgba(15, 23, 42, 0.08)';
        }
    }
</script>
@endsection
