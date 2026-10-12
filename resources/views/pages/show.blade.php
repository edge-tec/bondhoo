@extends('layouts.app')

@section('title', $page->name . ' — অফিসিয়াল পেইজ — Bondhoo')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/enterprise-pages-redesign.css') }}?v={{ time() }}">
<style>
    .page-hero {
        background: var(--fb-card);
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--fb-border);
        box-shadow: var(--shadow-sm);
        margin-bottom: 20px;
    }

    .page-hero-cover {
        height: 280px;
        background: linear-gradient(135deg, #059669, #0284c7);
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
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 20px;
        margin-top: 20px;
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
        .page-nav-tab {
            padding: 8px 10px;
            font-size: 12px;
        }
    }
</style>
@endsection

@section('content')
<!-- 1. PAGE HERO -->
<div class="page-hero">
    <div class="page-hero-cover" id="pageCoverBox">
        @if($page->cover_image_url)
            <img src="{{ $page->cover_image_url }}" alt="{{ $page->name }}" id="pageCoverImg">
        @endif
        @if($canManage)
            <label class="cover-upload-btn" title="কভার ছবি পরিবর্তন করুন">
                @include('pages.partials.icon', ['name' => 'camera', 'size' => 16])
                <span>কভার পরিবর্তন</span>
                <input type="file" id="coverFileInput" accept="image/*" style="display: none;" onchange="uploadPageBrandingImage('cover', event)">
            </label>
        @endif
    </div>

    <div class="page-hero-info">
        <div style="display: flex; align-items: flex-end; gap: 18px; flex-wrap: wrap;">
            <div class="page-avatar-wrapper">
                <div class="page-avatar-large" id="pageAvatarBox">
                    @if($page->avatar_url)
                        <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}" id="pageAvatarImg">
                    @else
                        {{ mb_substr($page->name, 0, 1) }}
                    @endif
                </div>
                @if($canManage)
                    <label class="avatar-upload-btn" title="প্রোফাইল ছবি পরিবর্তন করুন">
                        @include('pages.partials.icon', ['name' => 'camera', 'size' => 18])
                        <input type="file" id="avatarFileInput" accept="image/*" style="display: none;" onchange="uploadPageBrandingImage('avatar', event)">
                    </label>
                @endif
            </div>

            <div>
                <h1 class="page-hero-title">
                    <span>{{ $page->name }}</span>
                    @if($page->is_verified)
                        <span title="ভেরিফাইড অফিসিয়াল পেইজ" style="color: #059669; display: inline-flex; align-items: center;" aria-label="Verified">
                            @include('pages.partials.icon', ['name' => 'badge-check', 'size' => 22])
                        </span>
                    @endif
                </h1>
                <div class="page-hero-meta">
                    @if($page->username)
                        <span style="font-weight: 600; color: var(--fb-text-primary);">{{ '@' . $page->username }}</span>
                        <span>•</span>
                    @endif
                    <span style="display: inline-flex; align-items: center; gap: 4px;">
                        @include('pages.partials.icon', ['name' => 'tag', 'size' => 14])
                        <span>{{ $page->category }}</span>
                    </span>
                    <span>•</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;">
                        @include('pages.partials.icon', ['name' => 'users', 'size' => 14])
                        <strong id="headerFollowersCount">{{ number_format($page->followers_count) }}</strong> জন ফলোয়ার
                    </span>
                    <span>•</span>
                    <span><strong>{{ number_format($page->posts_count) }}</strong> টি পোস্ট</span>
                </div>
            </div>
        </div>

        <!-- Hero Actions -->
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            @if(!$currentUser)
                <a href="/login" class="btn-fb-primary">লগইন করে ফলো করুন</a>
            @else
                @if($canManage)
                    <a href="/pages/{{ $page->slug }}/manage" class="btn-fb-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                        @include('pages.partials.icon', ['name' => 'settings', 'size' => 16])
                        <span>পেইজ পরিচালনা ({{ strtoupper($userRole ?? 'ADMIN') }})</span>
                    </a>
                @endif

                <button class="{{ $isFollowing ? 'btn-fb-secondary' : 'btn-fb-primary' }}" id="btnPageFollow" onclick="handlePageFollowToggle()" style="display: inline-flex; align-items: center; gap: 6px;">
                    <span id="followIcon" style="display: inline-flex; align-items: center;">
                        @if($isFollowing)
                            @include('pages.partials.icon', ['name' => 'user-check', 'size' => 16, 'strokeWidth' => 2.5])
                        @else
                            @include('pages.partials.icon', ['name' => 'user-plus', 'size' => 16, 'strokeWidth' => 2.5])
                        @endif
                    </span>
                    <span id="followText">{{ $isFollowing ? 'ফলো করা হয়েছে' : 'ফলো করুন' }}</span>
                </button>

                <button class="btn-fb-secondary" onclick="openPageMessageModal()" style="display: inline-flex; align-items: center; gap: 6px;">
                    @include('pages.partials.icon', ['name' => 'message-square', 'size' => 16])
                    <span>বার্তা পাঠান</span>
                </button>

                <button class="btn-fb-secondary" onclick="openPageShareModal()" style="display: inline-flex; align-items: center; gap: 6px;" title="পেইজ শেয়ার করুন">
                    @include('pages.partials.icon', ['name' => 'share-2', 'size' => 16])
                    <span>শেয়ার</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="page-nav-tabs">
        <button class="page-nav-tab {{ $activeTab === 'posts' ? 'active' : '' }}" onclick="switchPageTab('posts')">
            @include('pages.partials.icon', ['name' => 'file-text', 'size' => 16])
            <span>পোস্টসমূহ</span>
        </button>
        <button class="page-nav-tab {{ $activeTab === 'about' ? 'active' : '' }}" onclick="switchPageTab('about')">
            @include('pages.partials.icon', ['name' => 'info', 'size' => 16])
            <span>পরিচিতি ও বিবরণ</span>
        </button>
        <button class="page-nav-tab {{ $activeTab === 'photos' ? 'active' : '' }}" onclick="switchPageTab('photos')">
            @include('pages.partials.icon', ['name' => 'image', 'size' => 16])
            <span>ছবি ও ভিডিও ({{ $mediaItems->count() }})</span>
        </button>
        <button class="page-nav-tab {{ $activeTab === 'followers' ? 'active' : '' }}" onclick="switchPageTab('followers')">
            @include('pages.partials.icon', ['name' => 'users', 'size' => 16])
            <span>ফলোয়ারবৃন্দ ({{ number_format($page->followers_count) }})</span>
        </button>
        @if($events->isNotEmpty() || $canManage)
            <button class="page-nav-tab {{ $activeTab === 'events' ? 'active' : '' }}" onclick="switchPageTab('events')">
                @include('pages.partials.icon', ['name' => 'calendar', 'size' => 16])
                <span>ইভেন্টস ({{ $events->count() }})</span>
            </button>
        @endif
        @if($products->isNotEmpty() || $canManage)
            <button class="page-nav-tab {{ $activeTab === 'shop' ? 'active' : '' }}" onclick="switchPageTab('shop')">
                @include('pages.partials.icon', ['name' => 'shopping-bag', 'size' => 16])
                <span>পণ্য ও শপ ({{ $products->count() }})</span>
            </button>
        @endif
    </div>
</div>

<!-- 2. TAB CONTENT PANES -->

<!-- TAB: POSTS -->
<div id="tab-pane-posts" class="page-tab-pane" style="{{ $activeTab === 'posts' ? '' : 'display: none;' }}">
    <div class="page-main-layout">
        <!-- Main Column: Post Stream -->
        <div>
            @if($canManage)
                <!-- Enterprise Post Composer -->
                <div class="fb-card" style="margin-bottom: 20px;">
                    <form onsubmit="handlePagePostSubmit(event)">
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

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--fb-border); padding-top: 10px;">
                            <div>
                                <label class="btn-fb-secondary" style="font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;">
                                    @include('pages.partials.icon', ['name' => 'image', 'size' => 16])
                                    <span>ছবি / ভিডিও যোগ করুন</span>
                                    <input type="file" id="composerMediaInput" accept="image/*,video/*" style="display: none;" onchange="handleComposerMediaSelect(event)">
                                </label>
                            </div>
                            <button type="submit" class="btn-fb-primary" id="btnPagePostSubmit" style="display: inline-flex; align-items: center; gap: 6px;">
                                @include('pages.partials.icon', ['name' => 'send', 'size' => 16])
                                <span>পোস্ট প্রকাশ করুন</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Live Stream Target -->
            <div id="liveStreamTarget"></div>

            <!-- Existing Posts -->
            @if($posts && $posts->isNotEmpty())
                @foreach($posts as $post)
                    <div class="post-card" id="post-card-{{ $post->id }}">
                        <div class="post-header">
                            <div class="post-author-info">
                                <div class="avatar" style="width: 42px; height: 42px;">
                                    @if($page->avatar_url)
                                        <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}">
                                    @else
                                        {{ mb_substr($page->name, 0, 1) }}
                                    @endif
                                </div>
                                <div>
                                    <div style="font-weight: 700; font-size: 15px; color: var(--fb-text-primary); display: flex; align-items: center; gap: 4px;">
                                        <span>{{ $page->name }}</span>
                                        @if($page->is_verified)
                                            <span style="color: #059669; display: inline-flex; align-items: center;" title="ভেরিফাইড">
                                                @include('pages.partials.icon', ['name' => 'badge-check', 'size' => 14])
                                            </span>
                                        @endif
                                    </div>
                                    <div style="font-size: 12px; color: var(--fb-text-secondary); display: flex; align-items: center; gap: 4px;">
                                        <span>{{ $post->created_at ? $post->created_at->diffForHumans() : 'সম্প্রতি' }}</span>
                                        <span>•</span>
                                        @include('pages.partials.icon', ['name' => 'globe', 'size' => 12])
                                        <span>পাবলিক</span>
                                    </div>
                                </div>
                            </div>

                            @if($canManage || ($currentUser && $post->user_id === $currentUser->id))
                                <div style="position: relative;">
                                    <button class="btn-fb-secondary" style="padding: 4px 8px; font-size: 16px; border: none; background: transparent;" onclick="togglePostDropdown({{ $post->id }})" title="অপশন">⋮</button>
                                    <div id="postDropdown-{{ $post->id }}" style="display: none; position: absolute; right: 0; top: 100%; background: var(--fb-card); border: 1px solid var(--fb-border); border-radius: 8px; box-shadow: var(--shadow-md); z-index: 10; min-width: 140px; overflow: hidden;">
                                        <button onclick="handleDeletePost({{ $post->id }})" style="width: 100%; text-align: left; padding: 10px 14px; background: none; border: none; font-size: 13px; color: #ef4444; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            <span>পোস্ট মুছুন</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Post Body Text -->
                        <div style="font-size: 15px; line-height: 1.6; color: var(--fb-text-primary); margin-bottom: 12px; white-space: pre-line;">
                            {{ $post->content }}
                        </div>

                        <!-- Post Media Attachments -->
                        @if($post->media && $post->media->isNotEmpty())
                            <div class="post-media-grid {{ $post->media->count() === 1 ? 'single' : 'dual' }}">
                                @foreach($post->media as $media)
                                    @if(str_starts_with($media->mime_type ?? '', 'video/'))
                                        <video controls src="{{ $media->url ?? $media->original_path }}"></video>
                                    @else
                                        <img src="{{ $media->url ?? $media->original_path }}" alt="Media" loading="lazy">
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <!-- Engagement Counts Row -->
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--fb-text-secondary); padding: 4px 0;">
                            <div>
                                <span id="reactions-count-{{ $post->id }}">👍 ❤️ {{ number_format($post->likes_count ?? 0) }}</span>
                            </div>
                            <div style="display: flex; gap: 12px;">
                                <span id="comments-count-{{ $post->id }}">{{ number_format($post->comments_count ?? 0) }} টি মন্তব্য</span>
                                <span>{{ number_format($post->shares_count ?? 0) }} বার শেয়ার</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="post-actions-row">
                            <button class="post-action-btn {{ ($post->reactions && $currentUser && $post->reactions->contains('user_id', $currentUser->id)) ? 'active-liked' : '' }}" id="btn-react-{{ $post->id }}" onclick="handlePostReactionToggle({{ $post->id }})">
                                @include('pages.partials.icon', ['name' => 'thumbs-up', 'size' => 18])
                                <span>লাইক</span>
                            </button>
                            <button class="post-action-btn" onclick="togglePostCommentsSection({{ $post->id }})">
                                @include('pages.partials.icon', ['name' => 'message-square', 'size' => 18])
                                <span>মন্তব্য</span>
                            </button>
                            <button class="post-action-btn" onclick="openSharePostModal({{ $post->id }}, '{{ addslashes($post->content) }}')">
                                @include('pages.partials.icon', ['name' => 'share-2', 'size' => 18])
                                <span>শেয়ার</span>
                            </button>
                        </div>

                        <!-- Embedded Comments Container -->
                        <div id="comments-container-{{ $post->id }}" class="comments-section" style="display: none;">
                            <div id="comments-list-{{ $post->id }}">
                                <div style="text-align: center; padding: 10px; font-size: 13px; color: var(--fb-text-secondary);">লোড হচ্ছে...</div>
                            </div>

                            @if($currentUser)
                                <div style="display: flex; gap: 8px; margin-top: 12px; align-items: center;">
                                    <div class="avatar" style="width: 32px; height: 32px;">
                                        {{ mb_substr($currentUser->name ?? 'U', 0, 1) }}
                                    </div>
                                    <input type="text" id="comment-input-{{ $post->id }}" class="search-input" style="flex: 1; border-radius: 20px; font-size: 13px; border: 1px solid var(--fb-border); background: var(--fb-bg);" placeholder="আপনার মন্তব্য লিখুন এবং এন্টার চাপুন..." onkeypress="if(event.key === 'Enter') submitPostComment({{ $post->id }})">
                                    <button class="btn-fb-primary" style="padding: 6px 14px; border-radius: 20px; font-size: 13px;" onclick="submitPostComment({{ $post->id }})">পাঠান</button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div style="margin-top: 16px;">
                    {{ $posts->links() }}
                </div>
            @else
                <div class="fb-card" style="text-align: center; padding: 48px; color: var(--fb-text-secondary);">
                    <div style="color: var(--fb-text-secondary); margin-bottom: 12px; display: flex; justify-content: center;">
                        @include('pages.partials.icon', ['name' => 'file-text', 'size' => 44])
                    </div>
                    <div style="font-size: 17px; font-weight: 700; color: var(--fb-text-primary);">এই পেইজে এখনও কোনো পোস্ট নেই</div>
                    <div style="font-size: 13px; margin-top: 4px;">নতুন কোনো ঘোষণা বা কন্টেন্টের জন্য নিয়মিত চোখ রাখুন।</div>
                </div>
            @endif
        </div>

        <!-- Sidebar Column: Summary & Quick Details -->
        <div>
            <!-- About Card -->
            <div class="fb-card" style="margin-bottom: 20px;">
                <h3 style="font-weight: 800; font-size: 16px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    @include('pages.partials.icon', ['name' => 'info', 'size' => 18])
                    <span>পেইজ পরিচিতি</span>
                </h3>
                <p style="font-size: 14px; line-height: 1.5; color: var(--fb-text-secondary); margin-bottom: 16px;">
                    {{ $page->bio ?: ($page->description ? Str::limit($page->description, 150) : 'এই পেইজের কোনো বিবরণ উল্লেখ নেই।') }}
                </p>

                <div style="border-top: 1px solid var(--fb-border); padding-top: 12px; font-size: 13px; display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        @include('pages.partials.icon', ['name' => 'tag', 'size' => 14])
                        <span>ক্যাটাগরি: <strong>{{ $page->category }}</strong></span>
                    </div>
                    @if($page->website)
                        <div style="display: flex; align-items: center; gap: 8px; word-break: break-all;">
                            @include('pages.partials.icon', ['name' => 'globe', 'size' => 14])
                            <span>ওয়েবসাইট: <a href="{{ $page->website }}" target="_blank" rel="noopener" style="color: var(--fb-primary); text-decoration: none; font-weight: 600;">{{ $page->website }}</a></span>
                        </div>
                    @endif
                    @if($page->email)
                        <div style="display: flex; align-items: center; gap: 8px;">
                            @include('pages.partials.icon', ['name' => 'mail', 'size' => 14])
                            <span>ইমেইল: <a href="mailto:{{ $page->email }}" style="color: var(--fb-text-primary); text-decoration: none;">{{ $page->email }}</a></span>
                        </div>
                    @endif
                    @if($page->phone)
                        <div style="display: flex; align-items: center; gap: 8px;">
                            @include('pages.partials.icon', ['name' => 'phone', 'size' => 14])
                            <span>ফোন: <strong>{{ $page->phone }}</strong></span>
                        </div>
                    @endif
                    @if($page->city || $page->country)
                        <div style="display: flex; align-items: center; gap: 8px;">
                            @include('pages.partials.icon', ['name' => 'map-pin', 'size' => 14])
                            <span>অবস্থান: <strong>{{ implode(', ', array_filter([$page->address, $page->city, $page->country])) }}</strong></span>
                        </div>
                    @endif
                    <div style="display: flex; align-items: center; gap: 8px;">
                        @include('pages.partials.icon', ['name' => 'calendar', 'size' => 14])
                        <span>যাত্রা শুরু: {{ $page->created_at ? $page->created_at->format('d M, Y') : 'অজানা' }}</span>
                    </div>
                </div>

                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--fb-border);">
                    <button class="btn-fb-secondary" style="width: 100%; justify-content: center; font-size: 13px;" onclick="switchPageTab('about')">
                        বিস্তারিত সকল তথ্য দেখুন →
                    </button>
                </div>
            </div>

            <!-- Recent Media Snapshot -->
            @if($mediaItems->isNotEmpty())
                <div class="fb-card" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h3 style="font-weight: 800; font-size: 16px; margin: 0;">ছবি ও মিডিয়া</h3>
                        <a href="javascript:void(0)" onclick="switchPageTab('photos')" style="font-size: 13px; color: var(--fb-primary); text-decoration: none; font-weight: 600;">সবগুলো</a>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
                        @foreach($mediaItems->take(6) as $m)
                            <div style="aspect-ratio: 1; border-radius: 6px; overflow: hidden; background: #eee;">
                                <img src="{{ $m->url ?? $m->original_path }}" alt="Media" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
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
            </div>

            <!-- Column 2: Contact & Location -->
            <div>
                <h3 style="font-size: 15px; font-weight: 700; color: var(--fb-primary); margin-bottom: 12px;">যোগাযোগ ও ঠিকানা</h3>
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px;">
                    <div><strong>ওয়েবসাইট:</strong> {{ $page->website ?: 'উদ্বৃত্ত নয়' }}</div>
                    <div><strong>ইমেইল:</strong> {{ $page->email ?: 'উদ্বৃত্ত নয়' }}</div>
                    <div><strong>ফোন নম্বর:</strong> {{ $page->phone ?: 'উদ্বৃত্ত নয়' }}</div>
                    <div><strong>ঠিকানা:</strong> {{ $page->address ?: 'উদ্বৃত্ত নয়' }}</div>
                    <div><strong>শহর:</strong> {{ $page->city ?: 'উদ্বৃত্ত নয়' }}</div>
                    <div><strong>দেশ:</strong> {{ $page->country ?: 'বাংলাদেশ' }}</div>
                    <div><strong>পোস্টাল কোড:</strong> {{ $page->zip_code ?: 'উদ্বৃত্ত নয়' }}</div>
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
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
            @include('pages.partials.icon', ['name' => 'image', 'size' => 20])
            <span>আপলোডকৃত ছবি ও ভিডিও গ্যালারি ({{ $mediaItems->count() }})</span>
        </h2>

        @if($mediaItems->isNotEmpty())
            <div class="gallery-grid">
                @foreach($mediaItems as $media)
                    <div class="gallery-item">
                        @if(str_starts_with($media->mime_type ?? '', 'video/'))
                            <video controls src="{{ $media->url ?? $media->original_path }}"></video>
                        @else
                            <img src="{{ $media->url ?? $media->original_path }}" alt="Photo" loading="lazy">
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 48px; color: var(--fb-text-secondary);">
                <div style="color: var(--fb-text-secondary); margin-bottom: 8px; display: flex; justify-content: center;">
                    @include('pages.partials.icon', ['name' => 'image', 'size' => 44])
                </div>
                <div style="font-weight: 700; font-size: 16px;">এখনও কোনো ছবি বা ভিডিও আপলোড করা হয়নি</div>
                <div style="font-size: 13px;">পেইজে পোস্ট করার সময় ছবি যুক্ত করলে এখানে প্রদর্শিত হবে।</div>
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
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
            @include('pages.partials.icon', ['name' => 'calendar', 'size' => 20])
            <span>পেইজের আনুষ্ঠানিক ইভেন্টস</span>
        </h2>

        @if($events->isNotEmpty())
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                @foreach($events as $event)
                    <div style="border: 1px solid var(--fb-border); border-radius: 10px; padding: 16px; background: var(--fb-bg);">
                        <div style="font-size: 12px; font-weight: 700; color: #059669; text-transform: uppercase;">
                            {{ $event->start_time ? $event->start_time->format('d M, Y • h:i A') : 'তারিখ নির্ধারিত হয়নি' }}
                        </div>
                        <h4 style="font-size: 16px; font-weight: 800; margin: 8px 0 6px;">{{ $event->title }}</h4>
                        <div style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                            @include('pages.partials.icon', ['name' => 'map-pin', 'size' => 14])
                            <span>{{ $event->location ?: 'অনলাইন ইভেন্ট' }}</span>
                        </div>
                        <p style="font-size: 13px; line-height: 1.4; color: var(--fb-text-secondary); margin-bottom: 14px;">
                            {{ Str::limit($event->description, 100) }}
                        </p>
                        <button class="btn-fb-primary" style="width: 100%; justify-content: center; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;" onclick="handleEventRsvp({{ $event->id }})">
                            @include('pages.partials.icon', ['name' => 'check', 'size' => 14])
                            <span>অংশ গ্রহণ করুন (Going)</span>
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 48px; color: var(--fb-text-secondary);">
                <div style="color: var(--fb-text-secondary); margin-bottom: 8px; display: flex; justify-content: center;">
                    @include('pages.partials.icon', ['name' => 'calendar', 'size' => 44])
                </div>
                <div style="font-weight: 700; font-size: 16px;">বর্তমানে কোনো নির্ধারিত ইভেন্ট নেই</div>
                <div style="font-size: 13px;">ভবিষ্যতের কোনো ইভেন্ট আয়োজন করা হলে এখানে পাওয়া যাবে।</div>
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
<div class="page-modal-backdrop" id="pageMessageModal">
    <div class="page-modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-weight: 800; font-size: 17px; margin: 0;">{{ $page->name }} কে বার্তা পাঠান</h3>
            <button onclick="closePageMessageModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary); display: flex; align-items: center;">
                @include('pages.partials.icon', ['name' => 'x', 'size' => 20])
            </button>
        </div>
        <form onsubmit="handleSendPageMessage(event)" style="padding: 20px;">
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">বার্তা</label>
                <textarea id="pageMessageBody" rows="4" class="search-input" style="width: 100%; height: auto; border-radius: 8px; border: 1px solid var(--fb-border); padding: 10px;" placeholder="আপনার বার্তাটি এখানে লিখুন..." required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-fb-secondary" onclick="closePageMessageModal()">বাতিল</button>
                <button type="submit" class="btn-fb-primary" id="btnSendPageMessage">বার্তা পাঠান</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const PAGE_ID = {{ $page->id }};
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let selectedComposerMediaId = null;
    let activeShareTargetPostId = null;

    // 1. Tab Switching
    function switchPageTab(tabName) {
        document.querySelectorAll('.page-nav-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.page-tab-pane').forEach(p => p.style.display = 'none');

        const activeBtn = Array.from(document.querySelectorAll('.page-nav-tab')).find(b => b.textContent.toLowerCase().includes(tabName) || b.getAttribute('onclick')?.includes(tabName));
        if (activeBtn) activeBtn.classList.add('active');

        const targetPane = document.getElementById(`tab-pane-${tabName}`);
        if (targetPane) targetPane.style.display = 'block';

        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url.toString());
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

            if (data.success && data.data) {
                const isFollowing = data.data.is_following;
                if (isFollowing) {
                    btn.className = 'btn-fb-secondary';
                    icon.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>';
                    text.textContent = 'ফলো করা হয়েছে';
                } else {
                    btn.className = 'btn-fb-primary';
                    icon.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>';
                    text.textContent = 'ফলো করুন';
                }
                if (countSpan && typeof data.data.followers_count !== 'undefined') {
                    countSpan.textContent = Number(data.data.followers_count).toLocaleString();
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

    // 3. Page Post Submission (Composer)
    async function handlePagePostSubmit(e) {
        e.preventDefault();
        const contentInput = document.getElementById('pagePostContent');
        const content = contentInput.value.trim();
        const btn = document.getElementById('btnPagePostSubmit');

        if (!content) return;

        btn.disabled = true;
        btn.innerHTML = '<span>পোস্ট হচ্ছে...</span>';

        const payload = {
            content: content,
            type: selectedComposerMediaId ? 'media' : 'text'
        };

        if (selectedComposerMediaId) {
            payload.media_ids = [selectedComposerMediaId];
        }

        try {
            const res = await fetch(`/api/v1/pages/${PAGE_ID}/posts`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success || data.data) {
                contentInput.value = '';
                clearSelectedComposerMedia();
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

    // 5. Page Branding Upload (Cover & Avatar)
    async function uploadPageBrandingImage(type, e) {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('collection', type === 'cover' ? 'cover' : 'avatar');

        try {
            const uploadRes = await fetch('/api/v1/media/upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: formData
            });
            const uploadData = await uploadRes.json();

            if (uploadData.success && uploadData.data?.url) {
                const imgUrl = uploadData.data.url;
                const updatePayload = type === 'cover' ? { cover_image_url: imgUrl } : { avatar_url: imgUrl };

                const updateRes = await fetch(`/api/v2/pages/${PAGE_ID}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(updatePayload)
                });
                const updateJson = await updateRes.json();

                if (updateJson.success) {
                    if (type === 'cover') {
                        document.getElementById('pageCoverBox').innerHTML = `<img src="${imgUrl}" alt="{{ $page->name }}" id="pageCoverImg">`;
                    } else {
                        document.getElementById('pageAvatarBox').innerHTML = `<img src="${imgUrl}" alt="{{ $page->name }}" id="pageAvatarImg">`;
                    }
                } else {
                    alert(updateJson.message || 'ছবি সংরক্ষণ ব্যর্থ হয়েছে।');
                }
            } else {
                alert(uploadData.message || 'ছবি আপলোড ব্যর্থ হয়েছে।');
            }
        } catch (err) {
            alert('ছবি আপলোডে সার্ভার ত্রুটি।');
        }
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

    // 10. Message to Page Modal
    function openPageMessageModal(initialText = '') {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif
        if (initialText) {
            document.getElementById('pageMessageBody').value = initialText;
        }
        document.getElementById('pageMessageModal').style.display = 'flex';
    }

    function closePageMessageModal() {
        document.getElementById('pageMessageModal').style.display = 'none';
    }

    async function handleSendPageMessage(e) {
        e.preventDefault();
        const bodyInput = document.getElementById('pageMessageBody');
        const body = bodyInput.value.trim();
        const btn = document.getElementById('btnSendPageMessage');
        if (!body) return;

        btn.disabled = true;
        btn.textContent = 'পাঠানো হচ্ছে...';

        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/inbox/message`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ body })
            });
            const data = await res.json();

            if (data.success) {
                alert('আপনার বার্তা সফলভাবে পেইজে পাঠানো হয়েছে।');
                closePageMessageModal();
                bodyInput.value = '';
            } else {
                alert(data.message || 'বার্তা পাঠাতে সমস্যা হয়েছে।');
            }
        } catch (e) {
            alert('সার্ভার ত্রুটি।');
        } finally {
            btn.disabled = false;
            btn.textContent = 'বার্তা পাঠান';
        }
    }

    // 11. Event RSVP
    async function handleEventRsvp(eventId) {
        try {
            const res = await fetch(`/api/v2/pages/${PAGE_ID}/events/${eventId}/rsvp`, {
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
                alert('ইভেন্টে আপনার অংশগ্রহণ লিপিবদ্ধ হয়েছে!');
            } else {
                alert(data.message || 'ব্যর্থ হয়েছে।');
            }
        } catch (e) {
            alert('সার্ভার ত্রুটি।');
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
