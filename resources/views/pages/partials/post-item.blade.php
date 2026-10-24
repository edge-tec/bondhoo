@php
    $canEditPost = $canManage || ($currentUser && (int)$post->user_id === (int)$currentUser->id);
@endphp
<div class="post-card" id="post-card-{{ $post->id }}">
    @if($post->is_pinned)
        <div style="padding: 6px 16px; background: rgba(24, 119, 242, 0.08); border-bottom: 1px solid var(--fb-border); font-size: 12px; font-weight: 700; color: #1877F2; display: flex; align-items: center; gap: 6px;">
            <span>📌</span>
            <span>পিন করা পোস্ট (Pinned Post)</span>
        </div>
    @endif
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
                    @if(($post->audience ?? 'public') === 'public')
                        @include('pages.partials.icon', ['name' => 'globe', 'size' => 12])
                        <span>পাবলিক</span>
                    @elseif($post->audience === 'followers')
                        @include('pages.partials.icon', ['name' => 'users', 'size' => 12])
                        <span>ফলোয়ার্স</span>
                    @else
                        @include('pages.partials.icon', ['name' => 'lock', 'size' => 12])
                        <span>শুধুমাত্র এডমিন</span>
                    @endif
                </div>
            </div>
        </div>

        @if($canEditPost)
            <div style="position: relative;">
                <button class="btn-fb-secondary" style="padding: 4px 8px; font-size: 16px; border: none; background: transparent; cursor: pointer;" onclick="togglePostDropdown({{ $post->id }})" title="অপশন">⋮</button>
                <div id="postDropdown-{{ $post->id }}" style="display: none; position: absolute; right: 0; top: 100%; background: var(--fb-card); border: 1px solid var(--fb-border); border-radius: 8px; box-shadow: var(--shadow-md); z-index: 10; min-width: 150px; overflow: hidden;">
                    @if($canManage)
                        <button onclick="handleTogglePinPost({{ $post->id }})" style="width: 100%; text-align: left; padding: 10px 14px; background: none; border: none; font-size: 13px; color: var(--fb-text-primary); cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--fb-border);">
                            <span>📌</span>
                            <span>{{ $post->is_pinned ? 'আনপিন করুন' : 'পিন করুন' }}</span>
                        </button>
                    @endif
                    <button onclick="openEditPostModal({{ $post->id }}, '{{ addslashes($post->content) }}', '{{ $post->audience }}')" style="width: 100%; text-align: left; padding: 10px 14px; background: none; border: none; font-size: 13px; color: var(--fb-text-primary); cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--fb-border);">
                        <span>✏️</span>
                        <span>সম্পাদনা করুন</span>
                    </button>
                    <button onclick="handleDeletePost({{ $post->id }})" style="width: 100%; text-align: left; padding: 10px 14px; background: none; border: none; font-size: 13px; color: #ef4444; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        <span>পোস্ট মুছুন</span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Post Body Text -->
    <div style="font-size: 15px; line-height: 1.6; color: var(--fb-text-primary); margin-bottom: 12px; white-space: pre-line;" id="post-content-{{ $post->id }}">
        {{ $post->content }}
    </div>

    <!-- Post Media Attachments -->
    @if($post->media && $post->media->isNotEmpty())
        <div class="post-media-grid {{ $post->media->count() === 1 ? 'single' : 'dual' }}">
            @foreach($post->media as $media)
                @php
                    $mediaUrl = $media->getVariantUrl('large') ?: ($media->getVariantUrl('original') ?: ($media->url ?: (str_starts_with($media->original_path ?? '', 'http') ? $media->original_path : asset('storage/' . $media->original_path))));
                @endphp
                @if(str_starts_with($media->mime_type ?? '', 'video/'))
                    <div style="position: relative; overflow: hidden; border-radius: 8px; cursor: pointer;" onclick="openVideoPlayer('{{ $mediaUrl }}', '{{ addslashes($post->content) }}', '{{ isset($media->metadata['duration']) ? gmdate('i:s', (int)$media->metadata['duration']) : 'Video' }}', {{ (int)($post->views_count ?? 0) }}, {{ (int)($post->likes_count ?? 0) }})">
                        <video controls src="{{ $mediaUrl }}" style="width: 100%; border-radius: 8px;"></video>
                    </div>
                @else
                    <div style="position: relative; overflow: hidden; border-radius: 8px; cursor: pointer;" onclick="openPhotoLightbox('{{ $mediaUrl }}', '{{ addslashes($post->content) }}', '{{ $post->created_at ? $post->created_at->diffForHumans() : 'Recently' }}', {{ $media->id }})">
                        <img src="{{ $mediaUrl }}" alt="Media" loading="lazy" style="width: 100%; display: block; border-radius: 8px;">
                        @if(isset($media->metadata['duration']))
                            <div style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.7); color: white; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                {{ gmdate('i:s', (int)$media->metadata['duration']) }}
                            </div>
                        @endif
                    </div>
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
