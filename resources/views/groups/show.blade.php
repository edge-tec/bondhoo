@extends('layouts.app')

@section('title', $group->name . ' — Bondhoo কমিউনিটি OS')

@section('styles')
<style>
    :root {
        --jj-brand: #4f46e5;
        --jj-brand-hover: #4338ca;
        --jj-brand-subtle: #eef2ff;
        --jj-card: #ffffff;
        --jj-border: #e2e8f0;
        --jj-text: #0f172a;
        --jj-text-muted: #64748b;
        --jj-emerald: #10b981;
        --jj-amber: #f59e0b;
        --jj-rose: #f43f5e;
        --jj-bg-subtle: #f8fafc;
    }

    [data-theme="dark"] {
        --jj-brand: #6366f1;
        --jj-brand-hover: #818cf8;
        --jj-brand-subtle: #1e1b4b;
        --jj-card: #1e293b;
        --jj-border: #334155;
        --jj-text: #f8fafc;
        --jj-text-muted: #94a3b8;
        --jj-bg-subtle: #0f172a;
    }

    .community-hero {
        background: var(--jj-card);
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid var(--jj-border);
        box-shadow: var(--shadow-sm);
        margin-bottom: 20px;
    }

    .community-hero-cover {
        height: 280px;
        background: linear-gradient(135deg, #312e81, #4f46e5 50%, #0284c7);
        position: relative;
    }

    .community-hero-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .community-hero-avatar {
        position: absolute;
        bottom: -24px;
        left: 32px;
        width: 90px;
        height: 90px;
        border-radius: 20px;
        background: var(--jj-card);
        border: 4px solid var(--jj-card);
        box-shadow: var(--shadow-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 38px;
        font-weight: 800;
        color: var(--jj-brand);
        overflow: hidden;
    }

    .community-hero-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .community-hero-info {
        padding: 36px 32px 20px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .community-hero-title {
        font-size: 26px;
        font-weight: 800;
        color: var(--jj-text);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .community-hero-meta {
        font-size: 14px;
        color: var(--jj-text-muted);
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 6px;
        flex-wrap: wrap;
    }

    /* Tabs Bar */
    .community-nav-tabs {
        display: flex;
        gap: 4px;
        border-top: 1px solid var(--jj-border);
        padding: 0 24px;
        background: var(--jj-card);
        overflow-x: auto;
        scrollbar-width: none;
    }

    .community-tab-btn {
        padding: 14px 18px;
        font-size: 14px;
        font-weight: 700;
        color: var(--jj-text-muted);
        text-decoration: none;
        border-bottom: 3px solid transparent;
        white-space: nowrap;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .community-tab-btn:hover {
        color: var(--jj-brand);
    }

    .community-tab-btn.active {
        color: var(--jj-brand);
        border-bottom-color: var(--jj-brand);
    }

    .btn-jj-primary {
        background: var(--jj-brand);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 10px 18px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s, transform 0.1s;
    }

    .btn-jj-primary:hover {
        background: var(--jj-brand-hover);
        transform: translateY(-1px);
    }

    .btn-jj-secondary {
        background: var(--jj-bg-subtle);
        color: var(--jj-text);
        border: 1px solid var(--jj-border);
        border-radius: 8px;
        padding: 10px 16px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
    }

    .btn-jj-secondary:hover {
        background: var(--jj-border);
    }

    /* Layout */
    .community-content-layout {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 20px;
    }

    .jj-card {
        background: var(--jj-card);
        border: 1px solid var(--jj-border);
        border-radius: 14px;
        box-shadow: var(--shadow-sm);
    }

    .post-box-card {
        background: var(--jj-card);
        border-radius: 14px;
        border: 1px solid var(--jj-border);
        padding: 18px 20px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-sm);
    }

    .post-card {
        background: var(--jj-card);
        border-radius: 14px;
        border: 1px solid var(--jj-border);
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: var(--shadow-sm);
    }

    .post-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .post-user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .post-user-name {
        font-weight: 700;
        font-size: 15px;
        color: var(--jj-text);
        text-decoration: none;
    }

    .post-time {
        font-size: 12px;
        color: var(--jj-text-muted);
    }

    .post-content {
        font-size: 14.5px;
        line-height: 1.6;
        color: var(--jj-text);
        margin-bottom: 16px;
        white-space: pre-wrap;
    }

    .post-actions-bar {
        display: flex;
        border-top: 1px solid var(--jj-border);
        padding-top: 12px;
        gap: 8px;
    }

    .post-action-btn {
        flex: 1;
        padding: 8px;
        border: none;
        background: none;
        border-radius: 8px;
        color: var(--jj-text-muted);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: background 0.15s;
    }

    .post-action-btn:hover {
        background: var(--jj-bg-subtle);
    }

    .post-action-btn.reacted {
        color: var(--jj-brand);
        font-weight: 700;
    }

    /* Discussion Specific Styles */
    .discussion-card {
        background: var(--jj-card);
        border-radius: 14px;
        border: 1px solid var(--jj-border);
        padding: 20px;
        margin-bottom: 16px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .discussion-card.is-solved {
        border-left: 4px solid var(--jj-emerald);
    }

    .badge-solved {
        background: #d1fae5;
        color: #065f46;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .badge-question {
        background: #fee2e2;
        color: #991b1b;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .accepted-answer-box {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        border-radius: 10px;
        padding: 14px 18px;
        margin: 14px 0;
    }

    /* Health Meter Widget */
    .health-meter-box {
        background: var(--jj-card);
        border: 1px solid var(--jj-border);
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .health-gauge-bar {
        height: 10px;
        border-radius: 5px;
        background: var(--jj-bg-subtle);
        overflow: hidden;
        margin: 10px 0 16px 0;
    }

    .health-gauge-fill {
        height: 100%;
        border-radius: 5px;
        transition: width 0.4s ease;
    }

    /* Modal Backdrop */
    .modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        z-index: 1050;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    .modal-box {
        background: var(--jj-card);
        border-radius: 16px;
        width: 560px;
        max-width: 100%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 40px rgba(0,0,0,0.25);
        overflow: hidden;
    }

    .toast-container {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .custom-toast {
        background: #0f172a;
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .search-input {
        background: var(--jj-bg-subtle);
        border: 1px solid var(--jj-border);
        color: var(--jj-text);
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        outline: none;
    }

    .search-input:focus {
        border-color: var(--jj-brand);
        box-shadow: 0 0 0 3px var(--jj-brand-subtle);
    }

    @media (max-width: 900px) {
        .community-content-layout {
            grid-template-columns: 1fr;
        }
        .community-hero-avatar {
            left: 20px;
            bottom: -20px;
            width: 70px;
            height: 70px;
            font-size: 28px;
        }
        .community-hero-info {
            padding: 30px 20px 16px 20px;
        }
    }
</style>
@endsection

@section('content')
<!-- Community Hero Banner -->
<div class="community-hero">
    <div class="community-hero-cover">
        @if($group->cover_image_url)
            <img src="{{ $group->cover_image_url }}" alt="{{ $group->name }}">
        @endif
        <div class="community-hero-avatar">
            @if($group->avatar_url)
                <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}">
            @else
                {{ mb_substr($group->name, 0, 1) }}
            @endif
        </div>
    </div>

    <div class="community-hero-info">
        <div>
            <h1 class="community-hero-title">
                {{ $group->name }}
                @if($group->is_verified)
                    <span style="color: var(--jj-brand); font-size: 22px;" title="ভেরিফাইড কমিউনিটি">✓</span>
                @endif
            </h1>
            <div class="community-hero-meta">
                <span>{{ $group->isPublic() ? '🌐 পাবলিক গ্রুপ' : ($group->isPrivate() ? '🔒 প্রাইভেট গ্রুপ' : '🛡️ সিক্রেট গ্রুপ') }}</span>
                <span>•</span>
                <span>👥 {{ number_format($group->members_count) }} জন সদস্য</span>
                @if($group->category)
                    <span>•</span>
                    <span style="background: var(--jj-bg-subtle); border: 1px solid var(--jj-border); padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 12px;">{{ $group->category }}</span>
                @endif
                @if($group->health_score > 0)
                    <span>•</span>
                    <span style="color: var(--jj-emerald); font-weight: 700;">🩺 হেলথ স্কোর: {{ $group->health_score }}%</span>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            @if($currentUser)
                @if($isMember)
                    <button class="btn-jj-secondary" onclick="toggleSubscribeResource('group', {{ $group->id }})" title="গ্র্যানুলার নোটিফিকেশন সাবস্ক্রিপশন" aria-label="সাবস্ক্রিপশন টগল করুন">
                        🔔 সাবস্ক্রাইব
                    </button>
                    <button class="btn-jj-secondary" onclick="toggleBookmarkResource('group', {{ $group->id }})" title="বুকমার্কে সেভ করুন" aria-label="বুকমার্ক টগল করুন">
                        🔖 বুকমার্ক
                    </button>
                    @if(!$isAdmin)
                        <button class="btn-jj-secondary" style="color: var(--jj-rose);" onclick="confirmLeaveGroup()" aria-label="কমিউনিটি ত্যাগ করুন">
                            ত্যাগ করুন
                        </button>
                    @else
                        <span style="font-size: 13px; font-weight: 700; color: var(--jj-brand); padding: 8px 12px; background: var(--jj-brand-subtle); border-radius: 8px;">
                            👑 ওনার / অ্যাডমিন
                        </span>
                    @endif
                @elseif($membership && $membership->isPending())
                    <button class="btn-jj-secondary" disabled style="opacity: 0.7;">
                        ⏳ অনুমোদনের অপেক্ষায়
                    </button>
                @else
                    <button class="btn-jj-primary" id="btnJoinMain" onclick="handleJoinClick()" aria-label="কমিউনিটিতে যোগ দিন">
                        ➕ যোগ দিন
                    </button>
                @endif
            @else
                <a href="/login" class="btn-jj-primary" style="text-decoration: none;">
                    যোগ দিতে লগইন করুন
                </a>
            @endif
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="community-nav-tabs" aria-label="কমিউনিটি বিভাগসমূহ">
        <a href="{{ route('groups.show', ['slug' => $group->slug, 'tab' => 'posts']) }}" class="community-tab-btn {{ in_array($activeTab, ['posts', 'home']) ? 'active' : '' }}">
            📝 পোস্ট ফিড
        </a>
        <a href="{{ route('groups.show', ['slug' => $group->slug, 'tab' => 'discussions']) }}" class="community-tab-btn {{ $activeTab === 'discussions' ? 'active' : '' }}">
            💬 প্রশ্নোত্তর ও ডিসকাশন
        </a>
        <a href="{{ route('groups.show', ['slug' => $group->slug, 'tab' => 'about']) }}" class="community-tab-btn {{ $activeTab === 'about' ? 'active' : '' }}">
            ℹ️ পরিচিতি ও রুলস
        </a>
        <a href="{{ route('groups.show', ['slug' => $group->slug, 'tab' => 'members']) }}" class="community-tab-btn {{ $activeTab === 'members' ? 'active' : '' }}">
            👥 সদস্য ({{ number_format($group->members_count) }})
        </a>
        <a href="{{ route('groups.show', ['slug' => $group->slug, 'tab' => 'polls']) }}" class="community-tab-btn {{ $activeTab === 'polls' ? 'active' : '' }}">
            📊 পোল
        </a>
        <a href="{{ route('groups.show', ['slug' => $group->slug, 'tab' => 'events']) }}" class="community-tab-btn {{ $activeTab === 'events' ? 'active' : '' }}">
            📅 ইভেন্ট
        </a>
        <a href="{{ route('groups.show', ['slug' => $group->slug, 'tab' => 'announcements']) }}" class="community-tab-btn {{ $activeTab === 'announcements' ? 'active' : '' }}">
            📢 ঘোষণা
        </a>
        <a href="{{ route('groups.show', ['slug' => $group->slug, 'tab' => 'files']) }}" class="community-tab-btn {{ $activeTab === 'files' ? 'active' : '' }}">
            📁 রিসোর্স ও ফাইল
        </a>
        @if($isModerator)
            <a href="{{ route('groups.show', ['slug' => $group->slug, 'tab' => 'moderation']) }}" class="community-tab-btn {{ $activeTab === 'moderation' ? 'active' : '' }}" style="color: var(--jj-amber);">
                🛡️ মডারেশন কিউ
            </a>
        @endif
        @if($isAdmin)
            <a href="{{ route('groups.show', ['slug' => $group->slug, 'tab' => 'analytics']) }}" class="community-tab-btn {{ $activeTab === 'analytics' ? 'active' : '' }}" style="color: var(--jj-brand);">
                📈 অ্যানালিটিক্স
            </a>
        @endif
    </nav>
</div>

<div class="community-content-layout">
    <!-- Main Content Area -->
    <div>
        <!-- TAB: Posts Stream -->
        @if(in_array($activeTab, ['posts', 'home']))
            @if($canViewPosts)
                <!-- Post Composer Box -->
                @if($currentUser && $isMember && $canPost)
                    <div class="post-box-card">
                        <form onsubmit="submitGroupPost(event)">
                            <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                                <div class="avatar" style="width: 42px; height: 42px; border-radius: 50%; background: var(--jj-brand); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                    {{ mb_substr($currentUser->name, 0, 1) }}
                                </div>
                                <textarea id="groupPostContent" rows="3" class="search-input" style="flex: 1; height: auto; border-radius: 10px; padding: 12px; font-size: 14px;" placeholder="{{ $currentUser->name }}, কমিউনিটিতে কিছু শেয়ার করুন..." required aria-label="কমিউনিটিতে পোস্ট লিখুন"></textarea>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--jj-border); padding-top: 10px;">
                                <div style="display: flex; gap: 8px;">
                                    <button type="button" class="btn-jj-secondary" style="font-size: 13px;" onclick="openCreatePollModal()" aria-label="পোল তৈরি করুন">📊 পোল</button>
                                    <button type="button" class="btn-jj-secondary" style="font-size: 13px;" onclick="openCreateDiscussionModal()" aria-label="প্রশ্ন বা আলোচনা শুরু করুন">💬 আলোচনা</button>
                                </div>
                                <button type="submit" class="btn-jj-primary" id="btnPostSubmit" aria-label="পোস্ট প্রকাশ করুন">পোস্ট করুন</button>
                            </div>
                        </form>
                    </div>
                @elseif($currentUser && $isMember && !$canPost)
                    <div class="jj-card" style="padding: 14px 20px; background: rgba(244, 63, 94, 0.08); border: 1px solid var(--jj-rose); border-radius: 10px; margin-bottom: 20px; color: var(--jj-rose); font-weight: 600;">
                        ⚠️ আপনার অ্যাকাউন্টটি বর্তমানে কমিউনিটিতে মিউট বা রেস্ট্রিক্ট অবস্থায় আছে।
                    </div>
                @endif

                <!-- Posts List -->
                @if($posts && count($posts) > 0)
                    @foreach($posts as $post)
                        <div class="post-card" id="postCard_{{ $post->id }}">
                            <div class="post-header">
                                <div class="post-user-info">
                                    <div class="avatar" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: var(--jj-bg-subtle); display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                        @if($post->user?->profile?->avatar_url)
                                            <img src="{{ $post->user->profile->avatar_url }}" alt="{{ $post->user->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            {{ mb_substr($post->user?->name ?? 'স', 0, 1) }}
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ getUserProfileUrl($post->user) }}" class="post-user-name">{{ $post->user?->name ?? 'সদস্য' }}</a>
                                        <div class="post-time">{{ $post->created_at ? $post->created_at->diffForHumans() : 'সম্প্রতি' }}</div>
                                    </div>
                                </div>

                                @if($currentUser && ($post->user_id === $currentUser->id || $group->hasPermission($currentUser->id, 'delete_posts')))
                                    <button onclick="deletePost({{ $post->id }})" style="background: none; border: none; cursor: pointer; color: var(--jj-text-muted); font-size: 16px;" title="পোস্ট ডিলিট করুন" aria-label="পোস্ট মুছুন">🗑️</button>
                                @endif
                            </div>

                            <div class="post-content">{{ $post->content }}</div>

                            <!-- Post Actions -->
                            <div class="post-actions-bar">
                                <button class="post-action-btn" id="reactBtn_{{ $post->id }}" onclick="togglePostReaction({{ $post->id }})" aria-label="লাইক দিন">
                                    👍 পছন্দ (<span id="likesCount_{{ $post->id }}">{{ $post->likes_count }}</span>)
                                </button>
                                <button class="post-action-btn" onclick="toggleCommentsBox({{ $post->id }})" aria-label="মন্তব্য দেখুন বা করুন">
                                    💬 মন্তব্য (<span id="commentsCount_{{ $post->id }}">{{ $post->comments_count }}</span>)
                                </button>
                                <button class="post-action-btn" onclick="copyPostLink({{ $post->id }})" aria-label="পোস্টের লিংক কপি করুন">
                                    ↗️ শেয়ার
                                </button>
                            </div>

                            <!-- Comments Section -->
                            <div id="commentsBox_{{ $post->id }}" style="display: none; border-top: 1px solid var(--jj-border); margin-top: 12px; padding-top: 12px;">
                                <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                    <input type="text" id="commentInput_{{ $post->id }}" class="search-input" placeholder="একটি মন্তব্য লিখুন..." style="flex: 1;" aria-label="মন্তব্য লিখুন">
                                    <button class="btn-jj-primary" onclick="submitPostComment({{ $post->id }})" aria-label="মন্তব্য পাঠান">পাঠান</button>
                                </div>
                                <div id="commentsList_{{ $post->id }}" style="display: flex; flex-direction: column; gap: 8px;"></div>
                            </div>
                        </div>
                    @endforeach

                    <div style="margin-top: 20px;">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="jj-card" style="text-align: center; padding: 45px 20px; color: var(--jj-text-muted); border-radius: 12px;">
                        <div style="font-size: 40px; margin-bottom: 8px;">📝</div>
                        <div style="font-weight: 800; font-size: 16px; color: var(--jj-text);">কমিউনিটিতে এখনও কোনো পোস্ট নেই</div>
                        <p style="font-size: 13px; margin-top: 4px;">প্রথম পোস্টটি করে নতুন আলোচনা শুরু করুন!</p>
                    </div>
                @endif
            @else
                <div class="jj-card" style="text-align: center; padding: 60px 20px; color: var(--jj-text-muted); border-radius: 12px;">
                    <div style="font-size: 54px; margin-bottom: 14px;">🔒</div>
                    <h2 style="font-size: 20px; font-weight: 800; color: var(--jj-text);">এটি একটি প্রাইভেট কমিউনিটি</h2>
                    <p style="font-size: 14px; margin-top: 8px; max-width: 480px; margin-left: auto; margin-right: auto; line-height: 1.5;">
                        এই কমিউনিটির পোস্ট ও রিসোর্স দেখতে অনুগ্রহ করে যোগদানের রিকোয়েস্ট পাঠান।
                    </p>
                    <button class="btn-jj-primary" style="margin-top: 18px;" onclick="handleJoinClick()">➕ যোগদানের রিকোয়েস্ট</button>
                </div>
            @endif

        <!-- TAB: Discussions & Q&A -->
        @elseif($activeTab === 'discussions')
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 18px; font-weight: 800; color: var(--jj-text);">💬 প্রশ্নোত্তর ও ডিসকাশন</h2>
                @if($currentUser && $isMember)
                    <button class="btn-jj-primary" onclick="openCreateDiscussionModal()">➕ নতুন আলোচনা / প্রশ্ন</button>
                @endif
            </div>

            @if($discussions && count($discussions) > 0)
                @foreach($discussions as $disc)
                    <div class="discussion-card {{ $disc->is_solved ? 'is-solved' : '' }}" id="discCard_{{ $disc->id }}">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                @if($disc->type === 'question')
                                    <span class="badge-question">❓ প্রশ্ন</span>
                                @else
                                    <span style="background: var(--jj-bg-subtle); border: 1px solid var(--jj-border); font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px;">💬 আলোচনা</span>
                                @endif

                                @if($disc->is_solved)
                                    <span class="badge-solved">✓ সমাধান হয়েছে</span>
                                @endif
                                <h3 style="font-size: 17px; font-weight: 800; color: var(--jj-text);">{{ $disc->title }}</h3>
                            </div>
                            <span style="font-size: 12px; color: var(--jj-text-muted);">{{ $disc->created_at ? $disc->created_at->diffForHumans() : '' }}</span>
                        </div>

                        <p style="font-size: 14px; line-height: 1.6; color: var(--jj-text); margin-bottom: 14px;">
                            {{ $disc->body }}
                        </p>

                        <!-- Accepted Solution Block (if present) -->
                        @if($disc->is_solved && $disc->acceptedAnswer)
                            <div class="accepted-answer-box">
                                <div style="display: flex; align-items: center; gap: 6px; font-weight: 800; font-size: 13px; color: #065f46; margin-bottom: 6px;">
                                    <span>🌟 গৃহীত সমাধান (Accepted Answer)</span>
                                </div>
                                <div style="font-size: 13.5px; color: #064e3b; line-height: 1.5;">
                                    {{ $disc->acceptedAnswer->body }}
                                </div>
                            </div>
                        @endif

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--jj-border); padding-top: 10px; font-size: 13px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-weight: 700; color: var(--jj-text);">{{ $disc->author?->name }}</span>
                                <span style="color: var(--jj-text-muted);">• {{ $disc->replies_count }} উত্তর/মন্তব্য</span>
                            </div>
                            <button class="btn-jj-secondary" style="font-size: 12px; padding: 6px 12px;" onclick="toggleDiscussionReplies({{ $disc->id }})">
                                উত্তর দিন ও বিস্তারিত ↓
                            </button>
                        </div>

                        <!-- Replies Section -->
                        <div id="discRepliesBox_{{ $disc->id }}" style="display: none; margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--jj-border);">
                            @if($currentUser && $isMember)
                                <div style="display: flex; gap: 8px; margin-bottom: 14px;">
                                    <input type="text" id="discReplyInput_{{ $disc->id }}" class="search-input" style="flex: 1;" placeholder="আপনার উত্তর বা মতামত লিখুন...">
                                    <button class="btn-jj-primary" onclick="submitDiscussionReply({{ $disc->id }})">উত্তর দিন</button>
                                </div>
                            @endif
                            <div id="discRepliesList_{{ $disc->id }}" style="display: flex; flex-direction: column; gap: 10px;">
                                @foreach($disc->replies as $reply)
                                    <div style="background: var(--jj-bg-subtle); border: 1px solid var(--jj-border); border-radius: 8px; padding: 10px 14px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                            <span style="font-weight: 700; font-size: 13px;">{{ $reply->author?->name }}</span>
                                            <span style="font-size: 11px; color: var(--jj-text-muted);">{{ $reply->created_at ? $reply->created_at->diffForHumans() : '' }}</span>
                                        </div>
                                        <div style="font-size: 13px; line-height: 1.5;">{{ $reply->body }}</div>
                                        @if($currentUser && ($disc->author_id === $currentUser->id || $isModerator) && !$disc->is_solved)
                                            <div style="margin-top: 8px; text-align: right;">
                                                <button class="btn-jj-secondary" style="font-size: 11px; padding: 4px 8px; color: var(--jj-emerald);" onclick="markAnswerSolved({{ $disc->id }}, {{ $reply->id }})">
                                                    ✓ গৃহীত সমাধান মার্ক করুন
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <div style="margin-top: 20px;">
                    {{ $discussions->links() }}
                </div>
            @else
                <div class="jj-card" style="text-align: center; padding: 45px 20px; color: var(--jj-text-muted);">
                    <div style="font-size: 40px; margin-bottom: 8px;">💬</div>
                    <div style="font-weight: 800; font-size: 16px; color: var(--jj-text);">এখনও কোনো প্রশ্নোত্তর বা আলোচনা শুরু হয়নি</div>
                    <p style="font-size: 13px; margin-top: 4px;">প্রথম আলোচনা বা প্রশ্নটি শুরু করে কমিউনিটিতে অংশগ্রহণ করুন।</p>
                </div>
            @endif

        <!-- TAB: About & Rules -->
        @elseif($activeTab === 'about')
            <div class="jj-card" style="padding: 24px; margin-bottom: 20px;">
                <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 14px;">ℹ️ কমিউনিটি পরিচিতি ও নিয়মাবলী</h2>
                <p style="font-size: 14px; line-height: 1.6; color: var(--jj-text-muted); margin-bottom: 20px;">
                    {{ $group->description ?: 'এই কমিউনিটির কোনো বিবরণ দেওয়া হয়নি।' }}
                </p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; border-top: 1px solid var(--jj-border); padding-top: 16px;">
                    <div>
                        <div style="font-size: 12px; color: var(--jj-text-muted);">ক্যাটাগরি</div>
                        <div style="font-weight: 700; font-size: 14px;">{{ $group->category ?? 'General' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--jj-text-muted);">কমিউনিটি টাইপ</div>
                        <div style="font-weight: 700; font-size: 14px;">{{ ucfirst($group->community_type ?: $group->privacy) }}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--jj-text-muted);">পোস্ট অনুমোদন</div>
                        <div style="font-weight: 700; font-size: 14px;">{{ $group->requires_post_approval ? 'মডারেশন সাপেক্ষ' : 'সরাসরি' }}</div>
                    </div>
                </div>
            </div>

            <!-- Rules List -->
            <div class="jj-card" style="padding: 24px;">
                <h3 style="font-size: 17px; font-weight: 800; margin-bottom: 16px;">📜 কমিউনিটির অফিশিয়াল রুলস ({{ $group->rules->count() }})</h3>
                @if($group->rules->isNotEmpty())
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        @foreach($group->rules as $index => $rule)
                            <div style="border-left: 3px solid var(--jj-brand); padding-left: 14px;">
                                <div style="font-weight: 700; font-size: 14.5px; color: var(--jj-text);">{{ $index + 1 }}. {{ $rule->title }}</div>
                                <div style="font-size: 13px; color: var(--jj-text-muted); margin-top: 4px; line-height: 1.5;">{{ $rule->description }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p style="font-size: 13px; color: var(--jj-text-muted);">এই কমিউনিটিতে এখনও কোনো নির্দিষ্ট রুলস যোগ করা হয়নি।</p>
                @endif
            </div>

        <!-- TAB: Members -->
        @elseif($activeTab === 'members')
            <div class="jj-card" style="padding: 24px;">
                <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 16px;">👥 কমিউনিটি সদস্য তালিকা</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px;">
                    @foreach($activeMembers as $mem)
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid var(--jj-border); border-radius: 10px; background: var(--jj-bg-subtle);">
                            <div class="avatar" style="width: 44px; height: 44px; border-radius: 50%; overflow: hidden; background: var(--jj-brand); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                {{ mb_substr($mem->user?->name ?? 'স', 0, 1) }}
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 700; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $mem->user?->name }}</div>
                                <div style="font-size: 11px; color: var(--jj-text-muted);">{{ ucfirst($mem->role) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        <!-- TAB: Polls -->
        @elseif($activeTab === 'polls')
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 18px; font-weight: 800;">📊 কমিউনিটি পোল ও মতামত</h2>
                @if($currentUser && $isMember)
                    <button class="btn-jj-primary" onclick="openCreatePollModal()">➕ নতুন পোল তৈরি</button>
                @endif
            </div>

            @if($group->polls->isNotEmpty())
                @foreach($group->polls as $poll)
                    <div class="jj-card" style="padding: 20px; margin-bottom: 16px;">
                        <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 12px;">{{ $poll->question }}</h3>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            @foreach($poll->options as $opt)
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border: 1px solid var(--jj-border); border-radius: 8px; background: var(--jj-bg-subtle);">
                                    <span style="font-size: 14px; font-weight: 600;">{{ $opt->option_text }}</span>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 12px; font-weight: 700; color: var(--jj-text-muted);">{{ $opt->votes_count }} ভোট</span>
                                        @if($currentUser && $isMember)
                                            <button class="btn-jj-secondary" style="font-size: 12px; padding: 4px 10px;" onclick="voteOnPoll({{ $poll->id }}, {{ $opt->id }})">ভোট</button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @else
                <div class="jj-card" style="text-align: center; padding: 45px 20px; color: var(--jj-text-muted);">
                    <p>কোনো সক্রিয় পোল নেই।</p>
                </div>
            @endif

        <!-- TAB: Events -->
        @elseif($activeTab === 'events')
            <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 16px;">📅 আপকামিং ইভেন্টস</h2>
            @if($group->events->isNotEmpty())
                @foreach($group->events as $ev)
                    <div class="jj-card" style="padding: 20px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="font-size: 16px; font-weight: 800;">{{ $ev->title }}</h3>
                            <p style="font-size: 13px; color: var(--jj-text-muted); margin-top: 4px;">{{ $ev->description }}</p>
                            <div style="font-size: 12px; color: var(--jj-brand); font-weight: 700; margin-top: 6px;">📍 {{ $ev->location ?: 'অনলাইন' }} • 🗓️ {{ $ev->start_time }}</div>
                        </div>
                        @if($currentUser && $isMember)
                            <div style="display: flex; gap: 6px;">
                                <button class="btn-jj-primary" style="font-size: 12px;" onclick="rsvpEventAction({{ $ev->id }}, 'going')">অংশ নেব ✓</button>
                                <button class="btn-jj-secondary" style="font-size: 12px;" onclick="rsvpEventAction({{ $ev->id }}, 'interested')">আগ্রহী</button>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="jj-card" style="text-align: center; padding: 45px 20px; color: var(--jj-text-muted);">
                    <p>বর্তমানে কোনো নির্ধারিত ইভেন্ট নেই।</p>
                </div>
            @endif

        <!-- TAB: Announcements -->
        @elseif($activeTab === 'announcements')
            <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 16px;">📢 অফিশিয়াল ঘোষণা</h2>
            @if($group->announcements->isNotEmpty())
                @foreach($group->announcements as $ann)
                    <div class="jj-card" style="padding: 20px; margin-bottom: 16px; border-left: 4px solid var(--jj-brand);">
                        <h3 style="font-size: 16px; font-weight: 800;">{{ $ann->title }}</h3>
                        <p style="font-size: 14px; line-height: 1.6; margin-top: 8px;">{{ $ann->body }}</p>
                        <div style="font-size: 12px; color: var(--jj-text-muted); margin-top: 10px;">প্রচার করেছেন: <strong>{{ $ann->author?->name }}</strong> • {{ $ann->created_at ? $ann->created_at->diffForHumans() : '' }}</div>
                    </div>
                @endforeach
            @else
                <div class="jj-card" style="text-align: center; padding: 45px 20px; color: var(--jj-text-muted);">
                    <p>বর্তমানে কোনো সক্রিয় ঘোষণা নেই।</p>
                </div>
            @endif

        <!-- TAB: Files & Resources -->
        @elseif($activeTab === 'files')
            <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 16px;">📁 রিসোর্স সেন্টার ও ডকুমেন্টস</h2>
            @if($group->files->isNotEmpty())
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach($group->files as $file)
                        <div class="jj-card" style="padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 700; font-size: 14px;">📄 {{ $file->title }}</div>
                                <div style="font-size: 12px; color: var(--jj-text-muted);">আপলোড করেছেন: {{ $file->user?->name }} • {{ number_format($file->file_size / 1024, 1) }} KB</div>
                            </div>
                            <a href="{{ $file->file_url }}" target="_blank" class="btn-jj-secondary" style="text-decoration: none; font-size: 12px;">ডাউনলোড ⬇️</a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="jj-card" style="text-align: center; padding: 45px 20px; color: var(--jj-text-muted);">
                    <p>এই কমিউনিটিতে কোনো ফাইল বা রিসোর্স শেয়ার করা হয়নি।</p>
                </div>
            @endif

        <!-- TAB: Moderation Queue (Moderators only) -->
        @elseif($activeTab === 'moderation' && $isModerator)
            <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 16px;">🛡️ মডারেশন কিউ (Pending Approvals)</h2>
            @if($moderationPosts && count($moderationPosts) > 0)
                @foreach($moderationPosts as $pPost)
                    <div class="jj-card" style="padding: 20px; margin-bottom: 16px;">
                        <div style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">{{ $pPost->user?->name }} এর পোস্ট:</div>
                        <p style="font-size: 14px; line-height: 1.5; margin-bottom: 12px;">{{ $pPost->content }}</p>
                        <div style="display: flex; gap: 10px;">
                            <button class="btn-jj-primary" onclick="approvePostAction({{ $pPost->id }})">✓ অনুমোদন করুন</button>
                            <button class="btn-jj-secondary" style="color: var(--jj-rose);" onclick="rejectPostAction({{ $pPost->id }})">✕ বাতিল করুন</button>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="jj-card" style="text-align: center; padding: 45px 20px; color: var(--jj-text-muted);">
                    <p>বর্তমানে কোনো পেন্ডিং পোস্ট নেই। মডারেশন কিউ পরিচ্ছন্ন!</p>
                </div>
            @endif

        <!-- TAB: Analytics (Admins only) -->
        @elseif($activeTab === 'analytics' && $isAdmin)
            <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 16px;">📈 কমিউনিটি গ্রোথ ও এনগেজমেন্ট অ্যানালিটিক্স</h2>
            @if($analytics)
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <div class="jj-card" style="padding: 16px;">
                        <div style="font-size: 12px; color: var(--jj-text-muted); font-weight: 700;">মোট সদস্য</div>
                        <div style="font-size: 26px; font-weight: 800; color: var(--jj-brand); margin-top: 4px;">{{ $analytics['overview']['total_members'] }}</div>
                    </div>
                    <div class="jj-card" style="padding: 16px;">
                        <div style="font-size: 12px; color: var(--jj-text-muted); font-weight: 700;">নতুন সদস্য (৩০ দিন)</div>
                        <div style="font-size: 26px; font-weight: 800; color: var(--jj-emerald); margin-top: 4px;">{{ $analytics['overview']['new_members_period'] }}</div>
                    </div>
                    <div class="jj-card" style="padding: 16px;">
                        <div style="font-size: 12px; color: var(--jj-text-muted); font-weight: 700;">সক্রিয় সদস্য অনুপাত</div>
                        <div style="font-size: 26px; font-weight: 800; color: var(--jj-amber); margin-top: 4px;">{{ $analytics['overview']['active_members_ratio'] }}%</div>
                    </div>
                    <div class="jj-card" style="padding: 16px;">
                        <div style="font-size: 12px; color: var(--jj-text-muted); font-weight: 700;">এনগেজমেন্ট রেট</div>
                        <div style="font-size: 26px; font-weight: 800; color: #9333ea; margin-top: 4px;">{{ $analytics['overview']['engagement_rate'] }}%</div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- Sidebar Column -->
    <div>
        <!-- Community Health Meter Widget -->
        <div class="health-meter-box">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-weight: 800; font-size: 16px; color: var(--jj-text);">🩺 কমিউনিটি হেলথ স্কোর</h3>
                <span style="font-size: 18px; font-weight: 800; color: var(--jj-emerald);">{{ $group->health_score }}%</span>
            </div>
            <div class="health-gauge-bar">
                <div class="health-gauge-fill" style="width: {{ max(10, $group->health_score) }}%; background: {{ $group->health_score >= 70 ? 'var(--jj-emerald)' : ($group->health_score >= 40 ? 'var(--jj-amber)' : 'var(--jj-rose)') }};"></div>
            </div>
            <div style="font-size: 12.5px; color: var(--jj-text-muted); display: flex; flex-direction: column; gap: 6px;">
                <div style="display: flex; justify-content: space-between;">
                    <span>সক্রিয় সদস্য অনুপাত:</span>
                    <strong>{{ $group->health_metrics['active_ratio'] ?? 0 }}%</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>৭ দিনে পোস্ট সংখ্যা:</span>
                    <strong>{{ $group->health_metrics['weekly_posts'] ?? 0 }} টি</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>অমীমাংসিত রিপোর্ট:</span>
                    <strong style="color: {{ ($group->health_metrics['pending_reports'] ?? 0) > 0 ? 'var(--jj-rose)' : 'var(--jj-emerald)' }};">
                        {{ $group->health_metrics['pending_reports'] ?? 0 }} টি
                    </strong>
                </div>
            </div>
        </div>

        <!-- Contributor Recognition Badges Card -->
        @if(isset($badges) && $badges->isNotEmpty())
            <div class="jj-card" style="margin-bottom: 20px; padding: 20px;">
                <h3 style="font-weight: 800; font-size: 16px; margin-bottom: 12px;">🏆 শীর্ষ কন্ট্রিবিউটর ব্যাজ</h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach($badges as $b)
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; padding: 6px 10px; background: var(--jj-bg-subtle); border-radius: 8px;">
                            <span>{{ $b->user?->name }}</span>
                            <span style="font-weight: 700; color: var(--jj-brand); font-size: 11px;">
                                @if($b->badge_type === 'community_mentor') 🌟 মেন্টর
                                @elseif($b->badge_type === 'discussion_starter') 💬 আলোচক
                                @else 💡 কন্ট্রিবিউটর
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Community Info Box -->
        <div class="jj-card" style="margin-bottom: 20px; padding: 20px;">
            <h3 style="font-weight: 800; font-size: 16px; margin-bottom: 12px;">ℹ️ কমিউনিটি সারসংক্ষেপ</h3>
            <p style="font-size: 13.5px; line-height: 1.5; color: var(--jj-text-muted); margin-bottom: 16px;">
                {{ $group->description ?: 'এই কমিউনিটির কোনো বিবরণ দেওয়া হয়নি।' }}
            </p>
            <div style="border-top: 1px solid var(--jj-border); padding-top: 12px; font-size: 13px; color: var(--jj-text-muted); display: flex; flex-direction: column; gap: 8px;">
                <div>🗓️ প্রতিষ্ঠার তারিখ: <strong>{{ $group->created_at ? $group->created_at->format('d M, Y') : 'অজানা' }}</strong></div>
                <div>👑 প্রতিষ্ঠাতা: <a href="{{ getUserProfileUrl($group->creator) }}" style="color: var(--jj-brand); text-decoration: none; font-weight: 700;">{{ $group->creator?->name }}</a></div>
                <div>🌐 মোড: <strong>{{ ucfirst($group->community_type ?: $group->privacy) }}</strong></div>
            </div>
        </div>

        @if($group->rules->isNotEmpty())
            <div class="jj-card" style="padding: 20px;">
                <h3 style="font-weight: 800; font-size: 16px; margin-bottom: 12px;">📜 মূল নিয়মাবলী</h3>
                <ul style="font-size: 13px; color: var(--jj-text-muted); padding-left: 18px; line-height: 1.6;">
                    @foreach($group->rules->take(3) as $r)
                        <li><strong>{{ $r->title }}:</strong> {{ Str::limit($r->description, 60) }}</li>
                    @endforeach
                </ul>
                <a href="{{ route('groups.show', ['slug' => $group->slug, 'tab' => 'about']) }}" style="display: block; margin-top: 10px; font-size: 12px; color: var(--jj-brand); font-weight: 700; text-decoration: none;">সকল রুলস দেখুন →</a>
            </div>
        @endif
    </div>
</div>

<!-- Screening Questions Modal -->
<div class="modal-backdrop" id="screeningModal">
    <div class="modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--jj-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-weight: 800; font-size: 16px;">মেম্বারশিপ স্ক্রিনিং প্রশ্ন</h3>
            <button onclick="closeScreeningModal()" style="background: none; border: none; font-size: 18px; cursor: pointer;">✕</button>
        </div>
        <form onsubmit="submitScreeningAndJoin(event)" style="padding: 20px;">
            <div id="screeningQuestionsList" style="margin-bottom: 16px; display: flex; flex-direction: column; gap: 14px;">
                @if($group->questions->isNotEmpty())
                    @foreach($group->questions as $q)
                        <div>
                            <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">{{ $q->question }}</label>
                            <input type="text" name="q_{{ $q->id }}" class="search-input" required style="width: 100%;" placeholder="আপনার উত্তর লিখুন...">
                        </div>
                    @endforeach
                @else
                    <p style="font-size: 13px; color: var(--jj-text-muted);">এই কমিউনিটিতে যোগদানের জন্য অ্যাডমিনের অনুমোদনের অনুরোধ পাঠানো হবে।</p>
                @endif
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-jj-secondary" onclick="closeScreeningModal()">বাতিল</button>
                <button type="submit" class="btn-jj-primary" id="btnSubmitScreening">রিকোয়েস্ট পাঠান</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Poll Modal -->
<div class="modal-backdrop" id="createPollModal">
    <div class="modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--jj-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-weight: 800; font-size: 16px;">নতুন পোল তৈরি করুন</h3>
            <button onclick="closeCreatePollModal()" style="background: none; border: none; font-size: 18px; cursor: pointer;">✕</button>
        </div>
        <form onsubmit="submitCreatePoll(event)" style="padding: 20px;">
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">পোলের প্রশ্ন *</label>
                <input type="text" id="pollQuestionInput" required class="search-input" style="width: 100%;" placeholder="উদাঃ পরবর্তী কমিউনিটি মিটআপ কবে হবে?">
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">অপশন ১ *</label>
                <input type="text" id="pollOpt1" required class="search-input" style="width: 100%;" placeholder="প্রথম বিকল্প">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">অপশন ২ *</label>
                <input type="text" id="pollOpt2" required class="search-input" style="width: 100%;" placeholder="দ্বিতীয় বিকল্প">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-jj-secondary" onclick="closeCreatePollModal()">বাতিল</button>
                <button type="submit" class="btn-jj-primary" id="btnSubmitPoll">পোল প্রকাশ করুন</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Discussion / Question Modal -->
<div class="modal-backdrop" id="createDiscussionModal">
    <div class="modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--jj-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-weight: 800; font-size: 16px;">নতুন আলোচনা বা প্রশ্ন শুরু করুন</h3>
            <button onclick="closeCreateDiscussionModal()" style="background: none; border: none; font-size: 18px; cursor: pointer;">✕</button>
        </div>
        <form onsubmit="submitDiscussionForm(event)" style="padding: 20px;">
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">ধরন</label>
                <select id="discTypeInput" class="search-input" style="width: 100%;">
                    <option value="discussion">💬 সাধারণ আলোচনা (Discussion)</option>
                    <option value="question">❓ সমস্যা / প্রশ্ন (Q&A Mode)</option>
                </select>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">শিরোনাম *</label>
                <input type="text" id="discTitleInput" required class="search-input" style="width: 100%;" placeholder="উদাঃ লারাভেল ১১ এ কীভাবে কাস্টম ব্রডকাস্টিং কনফিগার করবেন?">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">বিস্তারিত বিবরণ *</label>
                <textarea id="discBodyInput" required rows="4" class="search-input" style="width: 100%; height: auto;" placeholder="আপনার প্রশ্ন বা আলোচনার বিস্তারিত লিখুন..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-jj-secondary" onclick="closeCreateDiscussionModal()">বাতিল</button>
                <button type="submit" class="btn-jj-primary" id="btnSubmitDiscussion">প্রকাশ করুন</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>
@endsection

@section('scripts')
<script>
    const groupId = {{ $group->id }};

    function showToast(msg) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        toast.textContent = msg;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    function handleJoinClick() {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif

        @if($group->questions->isNotEmpty())
            document.getElementById('screeningModal').style.display = 'flex';
        @else
            executeJoin({});
        @endif
    }

    function closeScreeningModal() {
        document.getElementById('screeningModal').style.display = 'none';
    }

    function submitScreeningAndJoin(e) {
        e.preventDefault();
        const answers = {};
        @foreach($group->questions as $q)
            const input = document.querySelector('input[name="q_{{ $q->id }}"]');
            if (input) answers[{{ $q->id }}] = input.value.trim();
        @endforeach
        closeScreeningModal();
        executeJoin(answers);
    }

    function executeJoin(answers) {
        const btn = document.getElementById('btnJoinMain');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'যোগদান হচ্ছে...';
        }

        fetch(`/api/v1/groups/${groupId}/join`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ answers })
        })
        .then(res => res.json())
        .then(data => {
            showToast(data.message || 'অনুরোধ সম্পন্ন হয়েছে।');
            setTimeout(() => window.location.reload(), 800);
        })
        .catch(err => {
            showToast('অনুরোধ ব্যর্থ হয়েছে।');
            if (btn) {
                btn.disabled = false;
                btn.textContent = '➕ যোগ দিন';
            }
        });
    }

    function confirmLeaveGroup() {
        if (!confirm('আপনি কি নিশ্চিত যে এই কমিউনিটি ত্যাগ করতে চান?')) return;

        fetch(`/api/v1/groups/${groupId}/leave`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            showToast(data.message || 'কমিউনিটি ত্যাগ করা হয়েছে।');
            setTimeout(() => window.location.reload(), 800);
        })
        .catch(err => {
            showToast('কার্যক্রম ব্যর্থ হয়েছে।');
        });
    }

    function submitGroupPost(e) {
        e.preventDefault();
        const content = document.getElementById('groupPostContent').value.trim();
        const btn = document.getElementById('btnPostSubmit');
        btn.disabled = true;
        btn.textContent = 'পোস্ট হচ্ছে...';

        fetch(`/api/v1/groups/${groupId}/posts`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ content })
        })
        .then(res => res.json())
        .then(data => {
            showToast(data.message || 'পোস্ট প্রকাশিত হয়েছে।');
            setTimeout(() => window.location.reload(), 600);
        })
        .catch(err => {
            showToast('পোস্ট প্রকাশ ব্যর্থ হয়েছে।');
            btn.disabled = false;
            btn.textContent = 'পোস্ট করুন';
        });
    }

    function togglePostReaction(postId) {
        fetch(`/api/v1/posts/${postId}/react`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ type: 'like' })
        })
        .then(res => res.json())
        .then(data => {
            const btn = document.getElementById(`reactBtn_${postId}`);
            const countSpan = document.getElementById(`likesCount_${postId}`);
            if (btn && countSpan) {
                btn.classList.toggle('reacted');
                let count = parseInt(countSpan.textContent) || 0;
                countSpan.textContent = btn.classList.contains('reacted') ? count + 1 : Math.max(0, count - 1);
            }
            showToast('পছন্দ করা হয়েছে (Like)!');
        });
    }

    function toggleCommentsBox(postId) {
        const box = document.getElementById(`commentsBox_${postId}`);
        if (box) {
            box.style.display = box.style.display === 'none' ? 'block' : 'none';
        }
    }

    function submitPostComment(postId) {
        const input = document.getElementById(`commentInput_${postId}`);
        const body = input.value.trim();
        if (!body) return;

        fetch(`/api/v1/posts/${postId}/comments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ body })
        })
        .then(res => res.json())
        .then(data => {
            input.value = '';
            showToast('মন্তব্য যুক্ত হয়েছে।');
            const list = document.getElementById(`commentsList_${postId}`);
            if (list) {
                const item = document.createElement('div');
                item.style.padding = '8px 12px';
                item.style.background = 'var(--jj-bg-subtle)';
                item.style.borderRadius = '8px';
                item.style.fontSize = '13px';
                item.textContent = body;
                list.appendChild(item);
            }
        });
    }

    function copyPostLink(postId) {
        navigator.clipboard.writeText(`${window.location.origin}/posts/${postId}`);
        showToast('পোস্টের লিংক কপি করা হয়েছে!');
    }

    function deletePost(postId) {
        if (!confirm('আপনি কি নিশ্চিত যে এই পোস্টটি মুছে ফেলতে চান?')) return;

        fetch(`/api/v1/groups/${groupId}/posts/${postId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            showToast('পোস্ট মুছে ফেলা হয়েছে।');
            const card = document.getElementById(`postCard_${postId}`);
            if (card) card.remove();
        });
    }

    function voteOnPoll(pollId, optionId) {
        fetch(`/api/v1/groups/${groupId}/polls/${pollId}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ option_ids: [optionId] })
        })
        .then(res => res.json())
        .then(data => {
            showToast(data.message || 'ভোট গৃহীত হয়েছে!');
            setTimeout(() => window.location.reload(), 600);
        })
        .catch(err => showToast('ভোট দিতে ত্রুটি ঘটেছে।'));
    }

    function openCreatePollModal() {
        document.getElementById('createPollModal').style.display = 'flex';
    }

    function closeCreatePollModal() {
        document.getElementById('createPollModal').style.display = 'none';
    }

    function submitCreatePoll(e) {
        e.preventDefault();
        const question = document.getElementById('pollQuestionInput').value.trim();
        const opt1 = document.getElementById('pollOpt1').value.trim();
        const opt2 = document.getElementById('pollOpt2').value.trim();

        fetch(`/api/v1/groups/${groupId}/polls`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                question,
                options: [opt1, opt2]
            })
        })
        .then(res => res.json())
        .then(data => {
            showToast('পোল সফলভাবে তৈরি হয়েছে!');
            closeCreatePollModal();
            setTimeout(() => window.location.reload(), 600);
        });
    }

    /* V2 Discussions & Q&A handlers */
    function openCreateDiscussionModal() {
        document.getElementById('createDiscussionModal').style.display = 'flex';
    }

    function closeCreateDiscussionModal() {
        document.getElementById('createDiscussionModal').style.display = 'none';
    }

    async function submitDiscussionForm(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitDiscussion');
        btn.disabled = true;
        btn.innerText = 'প্রকাশ হচ্ছে...';

        const type = document.getElementById('discTypeInput').value;
        const title = document.getElementById('discTitleInput').value.trim();
        const body = document.getElementById('discBodyInput').value.trim();

        try {
            const res = await fetch(`/api/v2/groups/${groupId}/discussions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ type, title, body })
            });

            const data = await res.json();
            if (res.ok) {
                showToast('আলোচনা সফলভাবে প্রকাশিত হয়েছে!');
                closeCreateDiscussionModal();
                setTimeout(() => window.location.href = `?tab=discussions`, 600);
            } else {
                showToast(data.error?.message || 'প্রকাশে সমস্যা হয়েছে।');
                btn.disabled = false;
                btn.innerText = 'প্রকাশ করুন';
            }
        } catch(err) {
            showToast('সার্ভার যোগাযোগে ত্রুটি ঘটেছে।');
            btn.disabled = false;
            btn.innerText = 'প্রকাশ করুন';
        }
    }

    function toggleDiscussionReplies(discId) {
        const box = document.getElementById(`discRepliesBox_${discId}`);
        if (box) {
            box.style.display = box.style.display === 'none' ? 'block' : 'none';
        }
    }

    async function submitDiscussionReply(discId) {
        const input = document.getElementById(`discReplyInput_${discId}`);
        const body = input.value.trim();
        if (!body) return;

        try {
            const res = await fetch(`/api/v2/groups/${groupId}/discussions/${discId}/replies`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ body })
            });

            const data = await res.json();
            if (res.ok) {
                showToast('উত্তর সফলভাবে পোস্ট হয়েছে!');
                setTimeout(() => window.location.reload(), 600);
            } else {
                showToast(data.error?.message || 'উত্তর পোস্ট ব্যর্থ হয়েছে।');
            }
        } catch(err) {
            showToast('ত্রুটি ঘটেছে।');
        }
    }

    async function markAnswerSolved(discId, replyId) {
        if (!confirm('আপনি কি এই উত্তরটিকে গৃহীত সমাধান (Accepted Answer) হিসেবে নির্ধারণ করতে চান?')) return;

        try {
            const res = await fetch(`/api/v2/groups/${groupId}/discussions/${discId}/solve/${replyId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await res.json();
            if (res.ok) {
                showToast('সমাধান সফলভাবে চিহ্নিত হয়েছে!');
                setTimeout(() => window.location.reload(), 600);
            } else {
                showToast(data.error?.message || 'কার্যক্রম সম্পন্ন হয়নি।');
            }
        } catch(err) {
            showToast('ত্রুটি ঘটেছে।');
        }
    }

    /* Subscriptions & Bookmarks */
    async function toggleSubscribeResource(itemType, itemId) {
        try {
            const res = await fetch(`/api/v2/groups/${groupId}/subscriptions/${itemType}/${itemId}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await res.json();
            showToast(data.meta?.message || 'সাবস্ক্রিপশন স্টেট আপডেট হয়েছে।');
        } catch(e) {
            showToast('কার্যক্রম সম্পন্ন হয়নি।');
        }
    }

    async function toggleBookmarkResource(itemType, itemId) {
        try {
            const res = await fetch(`/api/v2/groups/${groupId}/bookmarks/${itemType}/${itemId}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await res.json();
            showToast(data.meta?.message || 'বুকমার্ক স্টেট আপডেট হয়েছে।');
        } catch(e) {
            showToast('কার্যক্রম সম্পন্ন হয়নি।');
        }
    }

    function rsvpEventAction(eventId, status) {
        fetch(`/api/v1/groups/${groupId}/events/${eventId}/rsvp`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status })
        })
        .then(res => res.json())
        .then(data => {
            showToast(`RSVP সম্পন্ন: ${status}`);
            setTimeout(() => window.location.reload(), 600);
        });
    }

    function approvePostAction(postId) {
        fetch(`/api/v1/groups/${groupId}/moderation/posts/${postId}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            showToast('পোস্ট অনুমোদিত হয়েছে!');
            setTimeout(() => window.location.reload(), 600);
        });
    }

    function rejectPostAction(postId) {
        fetch(`/api/v1/groups/${groupId}/moderation/posts/${postId}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            showToast('পোস্ট বাতিল করা হয়েছে।');
            setTimeout(() => window.location.reload(), 600);
        });
    }
</script>
@endsection
