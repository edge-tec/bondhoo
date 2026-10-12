@php
    $isOwner = $currentUser && ((int)$page->owner_id === (int)$currentUser->id);
    $memberRole = $currentUser ? $page->getMemberRole($currentUser->id) : null;
    $isManager = $isOwner || in_array($memberRole, ['owner', 'admin', 'manager', 'content_manager', 'moderator', 'editor']);
    $roleDisplay = $isOwner ? 'মালিক' : ($memberRole ? strtoupper($memberRole) : null);
    $following = $currentUser ? $page->isFollowedBy($currentUser->id) : false;
@endphp

<div class="ep-page-card" id="pageCard-{{ $page->id }}" data-page-id="{{ $page->id }}" data-category="{{ $page->category }}">
    <!-- Cover Banner -->
    <div class="ep-card-cover-box">
        @if($page->cover_image_url)
            <img src="{{ $page->cover_image_url }}" alt="{{ $page->name }}" class="ep-card-cover-img" loading="lazy">
        @endif

        @if($roleDisplay)
            <span class="ep-role-badge {{ $isOwner ? 'owner' : '' }}">{{ $roleDisplay }}</span>
        @endif

        <!-- Overlapping Avatar -->
        <div class="ep-card-avatar-wrap">
            @if($page->avatar_url)
                <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}" class="ep-card-avatar-img" loading="lazy" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
            @else
                <div class="ep-card-avatar-fallback">{{ mb_substr($page->name, 0, 1) }}</div>
            @endif
        </div>
    </div>

    <!-- Card Content -->
    <div class="ep-card-body">
        <div class="ep-card-title-row">
            <a href="{{ route('pages.show', ['slug' => $page->slug]) }}" class="ep-card-name" title="{{ $page->name }}">
                <span>{{ $page->name }}</span>
                @if($page->is_verified)
                    <span class="ep-verify-badge" title="অফিশিয়াল ভেরিফাইড পেইজ">
                        @include('pages.partials.icon', ['name' => 'check-circle', 'size' => 15, 'class' => 'ep-verified-icon'])
                    </span>
                @endif
            </a>

            <!-- Contextual Dropdown Trigger -->
            <div style="position: relative;">
                <button type="button" class="ep-btn-more" onclick="toggleCardDropdown({{ $page->id }}, event)" title="আরও অপশন" aria-label="More actions for {{ $page->name }}">
                    @include('pages.partials.icon', ['name' => 'more-horizontal', 'size' => 16])
                </button>
                <div class="ep-dropdown-menu" id="cardDropdown-{{ $page->id }}">
                    <button type="button" class="ep-dropdown-item" onclick="copyPageUrl('{{ url('/pages/' . $page->slug) }}')">
                        @include('pages.partials.icon', ['name' => 'copy', 'size' => 14])
                        <span>লিংক কপি করুন</span>
                    </button>
                    <button type="button" class="ep-dropdown-item" onclick="openSharePageModal('{{ addslashes($page->name) }}', '{{ url('/pages/' . $page->slug) }}')">
                        @include('pages.partials.icon', ['name' => 'share-2', 'size' => 14])
                        <span>শেয়ার করুন</span>
                    </button>
                    <a href="{{ route('pages.show', ['slug' => $page->slug]) }}?action=message" class="ep-dropdown-item">
                        @include('pages.partials.icon', ['name' => 'message-square', 'size' => 14])
                        <span>বার্তা পাঠান</span>
                    </a>
                    @if($isManager)
                        <div style="height: 1px; background: var(--ep-divider); margin: 4px 0;"></div>
                        <a href="{{ route('pages.manage', ['slug' => $page->slug]) }}" class="ep-dropdown-item" style="color: var(--ep-accent); font-weight: 600;">
                            @include('pages.partials.icon', ['name' => 'settings', 'size' => 14])
                            <span>ম্যানেজমেন্ট স্টুডিও</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="ep-card-category-row">
            @include('pages.partials.icon', ['name' => 'briefcase', 'size' => 12])
            <span>{{ $page->category ?: 'জেনারেল পেজ' }}</span>
            @if($page->username)
                <span>•</span>
                <span>{{ '@' . $page->username }}</span>
            @endif
        </div>

        <div class="ep-card-bio">
            {{ $page->bio ?: 'এই পেইজের কোনো সংক্ষিপ্ত বিবরণ এখনো যুক্ত করা হয়নি।' }}
        </div>

        <!-- Real-Time Metrics Row -->
        <div class="ep-card-stats-row">
            <span class="ep-card-stat-item">
                @include('pages.partials.icon', ['name' => 'users', 'size' => 14])
                <span><strong id="card-followers-{{ $page->id }}">{{ number_format($page->followers_count) }}</strong> ফলোয়ার</span>
            </span>
            <span class="ep-card-stat-item">
                @include('pages.partials.icon', ['name' => 'file-text', 'size' => 14])
                <span><strong>{{ number_format($page->posts_count) }}</strong> পোস্ট</span>
            </span>
        </div>

        <!-- Action Buttons -->
        <div class="ep-card-actions">
            @if(!$currentUser)
                <a href="/login" class="ep-btn-follow" style="text-decoration: none;">
                    @include('pages.partials.icon', ['name' => 'user-plus', 'size' => 14])
                    <span>ফলো করুন</span>
                </a>
            @else
                <button type="button"
                        class="ep-btn-follow {{ $following ? 'is-following' : '' }}"
                        id="btnCardFollow-{{ $page->id }}"
                        data-page-id="{{ $page->id }}"
                        data-following="{{ $following ? '1' : '0' }}"
                        onclick="handleRealtimeCardFollow({{ $page->id }}, this)">
                    <span class="ep-follow-icon" id="cardFollowIcon-{{ $page->id }}">
                        @if($following)
                            @include('pages.partials.icon', ['name' => 'user-check', 'size' => 14])
                        @else
                            @include('pages.partials.icon', ['name' => 'user-plus', 'size' => 14])
                        @endif
                    </span>
                    <span class="ep-follow-text" id="cardFollowText-{{ $page->id }}">
                        {{ $following ? 'ফলো করা হয়েছে' : 'ফলো করুন' }}
                    </span>
                </button>
            @endif

            @if($isManager)
                <a href="{{ route('pages.manage', ['slug' => $page->slug]) }}" class="ep-btn-view ep-btn-manage" title="ম্যানেজমেন্ট স্টুডিও">
                    @include('pages.partials.icon', ['name' => 'settings', 'size' => 14])
                    <span>স্টুডিও</span>
                </a>
            @else
                <a href="{{ route('pages.show', ['slug' => $page->slug]) }}" class="ep-btn-view" title="পেইজ বিস্তারিত দেখুন">
                    @include('pages.partials.icon', ['name' => 'eye', 'size' => 14])
                    <span>পেইজ দেখুন</span>
                </a>
            @endif
        </div>
    </div>
</div>
