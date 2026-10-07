@extends('layouts.app')

@section('title', 'নোটিফিকেশন সেন্টার — Bondhoo')

@section('styles')
<style>
    /* ==============================================================
       JUGAJUG ENTERPRISE NOTIFICATION CENTER DESIGN SYSTEM
       ============================================================== */
    :root {
        --nc-bg-primary: var(--fb-card, #ffffff);
        --nc-bg-secondary: var(--fb-bg, #f0f2f5);
        --nc-bg-hover: var(--fb-hover, #f2f4f7);
        --nc-border: var(--fb-border, #e4e6eb);
        --nc-text-primary: var(--fb-text-primary, #050505);
        --nc-text-secondary: var(--fb-text-secondary, #65676b);
        --nc-accent: #0084ff;
        --nc-accent-light: #e7f3ff;
        --nc-danger: #ef4444;
        --nc-danger-light: #fef2f2;
        --nc-security: #f59e0b;
        --nc-security-light: #fef3c7;
        --nc-radius: 14px;
        --nc-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    }

    .nc-page-container {
        max-width: 860px;
        margin: 24px auto 60px;
        padding: 0 16px;
    }

    /* Page Header */
    .nc-header-card {
        background: var(--nc-bg-primary);
        border: 1px solid var(--nc-border);
        border-radius: var(--nc-radius);
        padding: 24px 28px;
        box-shadow: var(--nc-shadow);
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .nc-title-area h1 {
        font-size: 24px;
        font-weight: 800;
        color: var(--nc-text-primary);
        margin: 0 0 6px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .nc-title-area p {
        font-size: 14px;
        color: var(--nc-text-secondary);
        margin: 0;
    }

    .nc-header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .nc-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13.5px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.18s ease;
        text-decoration: none;
        font-family: inherit;
    }

    .nc-btn-secondary {
        background: var(--nc-bg-secondary);
        color: var(--nc-text-primary);
    }

    .nc-btn-secondary:hover {
        background: var(--nc-bg-hover);
        transform: translateY(-1px);
    }

    .nc-btn-primary {
        background: var(--nc-accent);
        color: #ffffff;
    }

    .nc-btn-primary:hover {
        background: #0073e6;
        box-shadow: 0 4px 12px rgba(0, 132, 255, 0.25);
    }

    .nc-btn-ghost {
        background: transparent;
        color: var(--nc-text-secondary);
        border: 1px solid var(--nc-border);
    }

    .nc-btn-ghost:hover {
        background: var(--nc-bg-secondary);
        color: var(--nc-text-primary);
    }

    /* Tabs & Categories Bar */
    .nc-tabs-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        padding: 4px 2px 14px;
        margin-bottom: 12px;
        scrollbar-width: none;
    }

    .nc-tabs-wrapper::-webkit-scrollbar {
        display: none;
    }

    .nc-tab-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 22px;
        background: var(--nc-bg-primary);
        border: 1px solid var(--nc-border);
        color: var(--nc-text-secondary);
        font-size: 13.5px;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
        transition: all 0.18s ease;
    }

    .nc-tab-pill:hover {
        background: var(--nc-bg-hover);
        color: var(--nc-text-primary);
    }

    .nc-tab-pill.active {
        background: var(--nc-accent);
        border-color: var(--nc-accent);
        color: #ffffff;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(0, 132, 255, 0.25);
    }

    .nc-tab-badge {
        background: rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        padding: 2px 8px;
        font-size: 11.5px;
        font-weight: 700;
    }

    .nc-tab-pill.active .nc-tab-badge {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }

    /* Notification Items List */
    .nc-list-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .nc-card {
        background: var(--nc-bg-primary);
        border: 1px solid var(--nc-border);
        border-radius: var(--nc-radius);
        padding: 16px 20px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        transition: transform 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
        position: relative;
    }

    .nc-card:hover {
        background: var(--nc-bg-hover);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
    }

    .nc-card.unread {
        background: #f7fbff;
        border-color: #cbe2ff;
    }

    .nc-card.unread::before {
        content: '';
        position: absolute;
        left: 0;
        top: 14px;
        bottom: 14px;
        width: 4px;
        background: var(--nc-accent);
        border-radius: 0 4px 4px 0;
    }

    /* Left: Avatar with Category Icon Badge */
    .nc-avatar-wrapper {
        position: relative;
        flex-shrink: 0;
    }

    .nc-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0084ff 0%, #00c6ff 100%);
        color: #ffffff;
        font-weight: 700;
        font-size: 19px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 2px solid var(--nc-bg-primary);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .nc-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .nc-badge-icon {
        position: absolute;
        right: -3px;
        bottom: -3px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--nc-accent);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--nc-bg-primary);
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }

    .nc-badge-icon.security {
        background: var(--nc-security);
    }

    .nc-badge-icon.system {
        background: #6366f1;
    }

    .nc-badge-icon.group {
        background: #10b981;
    }

    /* Middle: Notification Content */
    .nc-content {
        flex: 1;
        min-width: 0;
    }

    .nc-message-line {
        font-size: 14.5px;
        line-height: 1.45;
        color: var(--nc-text-primary);
        margin-bottom: 5px;
    }

    .nc-actor-name {
        font-weight: 700;
        color: var(--nc-text-primary);
        text-decoration: none;
    }

    .nc-actor-name:hover {
        text-decoration: underline;
    }

    .nc-time-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12.5px;
        color: var(--nc-text-secondary);
    }

    .nc-priority-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .nc-priority-tag.security, .nc-priority-tag.critical {
        background: var(--nc-security-light);
        color: #b45309;
    }

    .nc-priority-tag.important {
        background: var(--nc-accent-light);
        color: var(--nc-accent);
    }

    /* Right: Card Actions */
    .nc-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .nc-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--nc-bg-secondary);
        border: none;
        color: var(--nc-text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .nc-action-btn:hover {
        background: var(--nc-bg-hover);
        color: var(--nc-text-primary);
        transform: scale(1.05);
    }

    .nc-action-btn.delete:hover {
        background: var(--nc-danger-light);
        color: var(--nc-danger);
    }

    .nc-unread-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--nc-accent);
        box-shadow: 0 0 0 3px rgba(0, 132, 255, 0.2);
        flex-shrink: 0;
    }

    /* Empty State */
    .nc-empty-card {
        background: var(--nc-bg-primary);
        border: 1px solid var(--nc-border);
        border-radius: var(--nc-radius);
        padding: 56px 24px;
        text-align: center;
        box-shadow: var(--nc-shadow);
    }

    .nc-empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: var(--nc-accent-light);
        color: var(--nc-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px;
    }

    .nc-empty-title {
        font-size: 19px;
        font-weight: 800;
        color: var(--nc-text-primary);
        margin-bottom: 6px;
    }

    .nc-empty-desc {
        font-size: 14px;
        color: var(--nc-text-secondary);
        max-width: 380px;
        margin: 0 auto 20px;
        line-height: 1.5;
    }

    /* Preferences Modal */
    .nc-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        z-index: 999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    .nc-modal-overlay.active {
        display: flex;
    }

    .nc-modal-box {
        background: var(--nc-bg-primary);
        width: 100%;
        max-width: 500px;
        border-radius: var(--nc-radius);
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        border: 1px solid var(--nc-border);
        animation: ncModalIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes ncModalIn {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .nc-modal-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--nc-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .nc-modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: var(--nc-text-primary);
    }

    .nc-modal-body {
        padding: 20px 24px;
        max-height: 480px;
        overflow-y: auto;
    }

    .nc-pref-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid var(--nc-border);
    }

    .nc-pref-item:last-child {
        border-bottom: none;
    }

    .nc-pref-title {
        font-size: 14.5px;
        font-weight: 700;
        color: var(--nc-text-primary);
        margin-bottom: 2px;
    }

    .nc-pref-desc {
        font-size: 12.5px;
        color: var(--nc-text-secondary);
    }

    .nc-toggle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }

    .nc-toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .nc-toggle-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background-color: #cbd5e1;
        transition: .2s;
        border-radius: 24px;
    }

    .nc-toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .2s;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    input:checked + .nc-toggle-slider {
        background-color: var(--nc-accent);
    }

    input:checked + .nc-toggle-slider:before {
        transform: translateX(20px);
    }

    /* Responsive */
    @media (max-width: 640px) {
        .nc-header-card {
            padding: 18px 20px;
            flex-direction: column;
            align-items: flex-start;
        }

        .nc-header-actions {
            width: 100%;
        }

        .nc-header-actions .nc-btn {
            flex: 1;
            justify-content: center;
        }

        .nc-card {
            padding: 14px 16px;
            gap: 12px;
        }

        .nc-avatar {
            width: 42px;
            height: 42px;
            font-size: 16px;
        }

        .nc-badge-icon {
            width: 20px;
            height: 20px;
        }
    }
</style>
@endsection

@section('content')
<div class="nc-page-container">
    <!-- Header Card -->
    <div class="nc-header-card">
        <div class="nc-title-area">
            <h1>
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--nc-accent)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                নোটিফিকেশন সেন্টার
            </h1>
            <p>আপনার সমস্ত সামাজিক কার্যক্রম, বার্তা ও সুরক্ষামূলক সতর্কতা একনজরে।</p>
        </div>

        <div class="nc-header-actions">
            <button type="button" class="nc-btn nc-btn-secondary" onclick="markAllNotificationsAsRead()" id="btnMarkAllRead">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                সব পঠিত চিহ্নিত করুন
            </button>
            <button type="button" class="nc-btn nc-btn-ghost" onclick="openPreferencesModal()" title="নোটিফিকেশন সেটিংস">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                সেটিংস
            </button>
        </div>
    </div>

    <!-- Category / Filter Navigation Tabs -->
    <div class="nc-tabs-wrapper" role="tablist">
        <a href="{{ route('notifications.index', ['filter' => 'all']) }}" class="nc-tab-pill {{ ($filter === 'all' && $category === 'all') ? 'active' : '' }}">
            <span>সব</span>
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="nc-tab-pill {{ $filter === 'unread' ? 'active' : '' }}">
            <span>অপঠিত</span>
            @if($unreadCount > 0)
                <span class="nc-tab-badge" id="ncTabUnreadBadge">{{ $unreadCount }}</span>
            @endif
        </a>
        <a href="{{ route('notifications.index', ['category' => 'social']) }}" class="nc-tab-pill {{ $category === 'social' ? 'active' : '' }}">
            <span>সোশ্যাল</span>
        </a>
        <a href="{{ route('notifications.index', ['category' => 'group']) }}" class="nc-tab-pill {{ $category === 'group' ? 'active' : '' }}">
            <span>গ্রুপ</span>
        </a>
        <a href="{{ route('notifications.index', ['category' => 'security']) }}" class="nc-tab-pill {{ $category === 'security' ? 'active' : '' }}">
            <span>নিরাপত্তা</span>
        </a>
        <a href="{{ route('notifications.index', ['category' => 'system']) }}" class="nc-tab-pill {{ $category === 'system' ? 'active' : '' }}">
            <span>সিস্টেম</span>
        </a>
    </div>

    <!-- Notifications List -->
    <div class="nc-list-container" id="ncListContainer" role="region" aria-live="polite">
        @forelse($notifications as $rawNotif)
            @php
                $dto = \App\Services\Notification\NotificationDto::fromDatabase($rawNotif);
            @endphp
            <div class="nc-card {{ $dto->isRead ? 'read' : 'unread' }}" id="notifRow-{{ $dto->id }}" tabindex="0" onkeydown="handleCardKeydown(event, '{{ $dto->id }}', '{{ $dto->actionUrl }}')">
                <!-- Left: Avatar with Badge Icon -->
                <div class="nc-avatar-wrapper">
                    <div class="nc-avatar">
                        @if(!empty($dto->actor['avatar_url']))
                            <img src="{{ $dto->actor['avatar_url'] }}" alt="{{ $dto->actor['name'] }}" onerror="this.onerror=null; this.parentElement.innerText='{{ $dto->actor['initial'] }}';">
                        @else
                            {{ $dto->actor['initial'] }}
                        @endif
                    </div>
                    <div class="nc-badge-icon {{ $dto->category }}" title="{{ ucfirst($dto->category) }}">
                        {!! $dto->renderSvg(14) !!}
                    </div>
                </div>

                <!-- Middle: Body Content -->
                <div class="nc-content">
                    <div class="nc-message-line">
                        <a href="{{ $dto->actionUrl }}" class="nc-actor-name" onclick="trackNotificationClick('{{ $dto->id }}', '{{ $dto->actionUrl }}', event)">
                            {{ $dto->message }}
                        </a>
                    </div>
                    <div class="nc-time-row">
                        <span>{{ $dto->timeAgo }}</span>
                        @if($dto->priority === 'security' || $dto->priority === 'critical')
                            <span class="nc-priority-tag security">নিরাপত্তা</span>
                        @elseif($dto->priority === 'important')
                            <span class="nc-priority-tag important">গুরুত্বপূর্ণ</span>
                        @endif
                    </div>
                </div>

                <!-- Right: Action Buttons -->
                <div class="nc-actions">
                    @if(!$dto->isRead)
                        <div class="nc-unread-dot" id="unreadDot-{{ $dto->id }}" title="অপঠিত"></div>
                        <button type="button" class="nc-action-btn" onclick="markSingleAsRead('{{ $dto->id }}')" title="পঠিত হিসেবে চিহ্নিত করুন" aria-label="Mark as read">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </button>
                    @endif

                    <a href="{{ $dto->actionUrl }}" class="nc-action-btn" onclick="trackNotificationClick('{{ $dto->id }}', '{{ $dto->actionUrl }}', event)" title="দেখুন" aria-label="View target">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>

                    <button type="button" class="nc-action-btn delete" onclick="deleteSingleNotification('{{ $dto->id }}')" title="মুছে ফেলুন" aria-label="Delete notification">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="nc-empty-card">
                <div class="nc-empty-icon">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        <line x1="2" y1="2" x2="22" y2="22"></line>
                    </svg>
                </div>
                <div class="nc-empty-title">কোনো নোটিফিকেশন পাওয়া যায়নি</div>
                <div class="nc-empty-desc">
                    @if($filter === 'unread')
                        আপনার সব নোটিফিকেশন পঠিত রয়েছে। কোনো নতুন নোটিফিকেশন নেই।
                    @else
                        আপনার সোশ্যাল কার্যক্রমে কোনো আপডেট এলে তা এখানে সরাসরি দেখতে পাবেন।
                    @endif
                </div>
                @if($filter !== 'all' || $category !== 'all')
                    <a href="{{ route('notifications.index') }}" class="nc-btn nc-btn-primary">সব নোটিফিকেশন দেখুন</a>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div style="margin-top: 24px;">
        {{ $notifications->appends(request()->query())->links() }}
    </div>
</div>

<!-- Preferences Modal -->
<div class="nc-modal-overlay" id="ncPreferencesModal" role="dialog" aria-modal="true" aria-labelledby="ncPrefTitle">
    <div class="nc-modal-box">
        <div class="nc-modal-header">
            <h3 id="ncPrefTitle">নোটিফিকেশন পছন্দসমূহ</h3>
            <button type="button" class="nc-action-btn" onclick="closePreferencesModal()" aria-label="Close modal">✕</button>
        </div>
        <div class="nc-modal-body">
            <div class="nc-pref-item">
                <div>
                    <div class="nc-pref-title">ইমেইল নোটিফিকেশন</div>
                    <div class="nc-pref-desc">গুরুত্বপূর্ণ আপডেট আপনার ইমেইলে পাঠানো হবে।</div>
                </div>
                <label class="nc-toggle-switch">
                    <input type="checkbox" id="prefEmail" onchange="saveNotificationPreferences()">
                    <span class="nc-toggle-slider"></span>
                </label>
            </div>

            <div class="nc-pref-item">
                <div>
                    <div class="nc-pref-title">পুশ নোটিফিকেশন</div>
                    <div class="nc-pref-desc">ডিভাইসে রিয়েল-টাইম লাইভ সতর্কতা গ্রহণ করুন।</div>
                </div>
                <label class="nc-toggle-switch">
                    <input type="checkbox" id="prefPush" onchange="saveNotificationPreferences()">
                    <span class="nc-toggle-slider"></span>
                </label>
            </div>

            <div class="nc-pref-item">
                <div>
                    <div class="nc-pref-title">ফ্রেন্ড রিকোয়েস্ট সতর্কতা</div>
                    <div class="nc-pref-desc">কেউ ফ্রেন্ড রিকোয়েস্ট পাঠালে বা গ্রহণ করলে জানান।</div>
                </div>
                <label class="nc-toggle-switch">
                    <input type="checkbox" id="prefFriend" onchange="saveNotificationPreferences()">
                    <span class="nc-toggle-slider"></span>
                </label>
            </div>

            <div class="nc-pref-item">
                <div>
                    <div class="nc-pref-title">মন্তব্য ও প্রতিক্রিয়া সতর্কতা</div>
                    <div class="nc-pref-desc">আপনার পোস্টে কেউ মন্তব্য বা লাইক দিলে নোটিফিকেশন দিন।</div>
                </div>
                <label class="nc-toggle-switch">
                    <input type="checkbox" id="prefComment" onchange="saveNotificationPreferences()">
                    <span class="nc-toggle-slider"></span>
                </label>
            </div>

            <div class="nc-pref-item">
                <div>
                    <div class="nc-pref-title">মেনশন সতর্কতা</div>
                    <div class="nc-pref-desc">কেউ কোনো মন্তব্য বা পোস্টে আপনাকে উল্লেখ করলে জানান।</div>
                </div>
                <label class="nc-toggle-switch">
                    <input type="checkbox" id="prefMention" onchange="saveNotificationPreferences()">
                    <span class="nc-toggle-slider"></span>
                </label>
            </div>

            <div class="nc-pref-item">
                <div>
                    <div class="nc-pref-title">নিরাপত্তা সতর্কতা</div>
                    <div class="nc-pref-desc">নতুন ডিভাইস বা অস্বাভাবিক লগইন সম্পর্কিত জরুরি নোটিফিকেশন।</div>
                </div>
                <label class="nc-toggle-switch">
                    <input type="checkbox" id="prefSecurity" onchange="saveNotificationPreferences()">
                    <span class="nc-toggle-slider"></span>
                </label>
            </div>
        </div>
        <div style="padding: 14px 24px; background: var(--nc-bg-secondary); border-top: 1px solid var(--nc-border); text-align: right;">
            <button type="button" class="nc-btn nc-btn-primary" onclick="closePreferencesModal()">সম্পন্ন</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const currentUserId = {{ $currentUser ? $currentUser->id : 0 }};

    // Mark Single As Read
    async function markSingleAsRead(id) {
        try {
            const res = await fetch(`/api/v1/notifications/${id}/read`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {})
                }
            });
            const data = await res.json();
            if (data.success) {
                applyNotificationReadState(id, data.data?.unread_count);
            }
        } catch (err) {
            console.error('Error marking notification as read:', err);
        }
    }

    // Handle Click on Notification link & mark read
    function trackNotificationClick(id, url, event) {
        const row = document.getElementById(`notifRow-${id}`);
        if (row && row.classList.contains('unread')) {
            markSingleAsRead(id);
        }
    }

    // Apply Read State to Row
    function applyNotificationReadState(id, unreadCount) {
        const row = document.getElementById(`notifRow-${id}`);
        if (row) {
            row.classList.remove('unread');
            row.classList.add('read');
            const dot = document.getElementById(`unreadDot-${id}`);
            if (dot) dot.remove();
        }
        if (typeof unreadCount === 'number') {
            updateUnreadBadges(unreadCount);
        }
    }

    // Mark All As Read
    async function markAllNotificationsAsRead() {
        try {
            const res = await fetch('/api/v1/notifications/read-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {})
                }
            });
            const data = await res.json();
            if (data.success) {
                document.querySelectorAll('.nc-card.unread').forEach(card => {
                    card.classList.remove('unread');
                    card.classList.add('read');
                });
                document.querySelectorAll('.nc-unread-dot').forEach(d => d.remove());
                updateUnreadBadges(0);
                if (window.showJugajugToast) {
                    showJugajugToast('সম্পন্ন', 'সমস্ত নোটিফিকেশন পঠিত চিহ্নিত করা হয়েছে।', '✓', 'success');
                }
            }
        } catch (err) {
            console.error('Error marking all notifications read:', err);
        }
    }

    // Delete Single Notification
    async function deleteSingleNotification(id) {
        if (!confirm('আপনি কি এই নোটিফিকেশনটি মুছে ফেলতে চান?')) return;

        try {
            const res = await fetch(`/api/v1/notifications/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {})
                }
            });
            const data = await res.json();
            if (data.success) {
                const row = document.getElementById(`notifRow-${id}`);
                if (row) {
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => row.remove(), 250);
                }
                if (typeof data.data?.unread_count === 'number') {
                    updateUnreadBadges(data.data.unread_count);
                }
            }
        } catch (err) {
            console.error('Error deleting notification:', err);
        }
    }

    // Update Badges across UI
    function updateUnreadBadges(count) {
        const badge = document.getElementById('navbarUnreadNotifsBadge');
        if (badge) {
            badge.innerText = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? '' : 'none';
        }
        const tabBadge = document.getElementById('ncTabUnreadBadge');
        if (tabBadge) {
            tabBadge.innerText = count;
            tabBadge.style.display = count > 0 ? '' : 'none';
        }
        const chip = document.getElementById('dropdownUnreadChip');
        if (chip) {
            chip.innerText = `${count} নতুন`;
            chip.style.display = count > 0 ? '' : 'none';
        }
    }

    // Keyboard accessibility for notification rows
    function handleCardKeydown(e, id, url) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            trackNotificationClick(id, url);
            window.location.href = url;
        } else if (e.key === 'Delete') {
            e.preventDefault();
            deleteSingleNotification(id);
        }
    }

    // Preferences Modal Controls
    async function openPreferencesModal() {
        document.getElementById('ncPreferencesModal').classList.add('active');
        try {
            const res = await fetch('/api/v1/notifications/preferences', {
                headers: { 'Accept': 'application/json', ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}) }
            });
            const data = await res.json();
            if (data.success && data.data) {
                const p = data.data;
                document.getElementById('prefEmail').checked = !!p.email_notifications;
                document.getElementById('prefPush').checked = !!p.push_notifications;
                document.getElementById('prefFriend').checked = !!p.friend_request_alerts;
                document.getElementById('prefComment').checked = !!p.comment_alerts;
                document.getElementById('prefMention').checked = !!p.mention_alerts;
                document.getElementById('prefSecurity').checked = !!p.security_alerts;
            }
        } catch (err) {
            console.error('Error fetching preferences:', err);
        }
    }

    function closePreferencesModal() {
        document.getElementById('ncPreferencesModal').classList.remove('active');
    }

    async function saveNotificationPreferences() {
        const body = {
            email_notifications: document.getElementById('prefEmail').checked,
            push_notifications: document.getElementById('prefPush').checked,
            friend_request_alerts: document.getElementById('prefFriend').checked,
            comment_alerts: document.getElementById('prefComment').checked,
            mention_alerts: document.getElementById('prefMention').checked,
            security_alerts: document.getElementById('prefSecurity').checked,
        };

        try {
            await fetch('/api/v1/notifications/preferences', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {})
                },
                body: JSON.stringify(body)
            });
        } catch (err) {
            console.error('Error saving notification preferences:', err);
        }
    }

    // Realtime Multi-Device Synchronization
    if (window.Echo && currentUserId) {
        window.Echo.private(`user.${currentUserId}`)
            .listen('.notification.new', (e) => {
                // Prepend live incoming notification
                prependLiveNotification(e);
            })
            .listen('.notification.read', (e) => {
                applyNotificationReadState(e.id, e.unread_count);
            })
            .listen('.notification.read_all', () => {
                document.querySelectorAll('.nc-card.unread').forEach(card => {
                    card.classList.remove('unread');
                    card.classList.add('read');
                });
                document.querySelectorAll('.nc-unread-dot').forEach(d => d.remove());
                updateUnreadBadges(0);
            })
            .listen('.notification.deleted', (e) => {
                const row = document.getElementById(`notifRow-${e.id}`);
                if (row) row.remove();
                if (typeof e.unread_count === 'number') {
                    updateUnreadBadges(e.unread_count);
                }
            });
    }

    function prependLiveNotification(e) {
        const container = document.getElementById('ncListContainer');
        if (!container) return;

        const notif = e.notification || e;
        const id = notif.id || `live-${Date.now()}`;
        const title = notif.title || notif.data?.title || 'নতুন নোটিফিকেশন';
        const message = notif.message || notif.data?.message || title;
        const link = notif.action_url || notif.data?.action_url || notif.data?.link || '/notifications';
        const actorName = notif.actor?.name || notif.data?.actor_name || notif.data?.sender_name || 'ব্যবহারকারী';
        const initial = actorName.charAt(0).toUpperCase();

        const card = document.createElement('div');
        card.className = 'nc-card unread';
        card.id = `notifRow-${id}`;
        card.tabIndex = 0;
        card.style.animation = 'slideInToast 0.3s ease-out';

        card.innerHTML = `
            <div class="nc-avatar-wrapper">
                <div class="nc-avatar">${initial}</div>
                <div class="nc-badge-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                </div>
            </div>
            <div class="nc-content">
                <div class="nc-message-line">
                    <a href="${link}" class="nc-actor-name" onclick="trackNotificationClick('${id}', '${link}', event)">${escapeHtml(message)}</a>
                </div>
                <div class="nc-time-row">
                    <span>এইমাত্র</span>
                </div>
            </div>
            <div class="nc-actions">
                <div class="nc-unread-dot" id="unreadDot-${id}"></div>
                <button type="button" class="nc-action-btn" onclick="markSingleAsRead('${id}')" title="পঠিত হিসেবে চিহ্নিত করুন">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </button>
                <a href="${link}" class="nc-action-btn" title="দেখুন">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        `;

        container.prepend(card);

        // Fetch fresh unread count
        fetch('/api/v1/notifications/unread-count', {
            headers: { 'Accept': 'application/json', ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}) }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && typeof res.data?.unread_count === 'number') {
                updateUnreadBadges(res.data.unread_count);
            }
        }).catch(() => {});
    }

    function escapeHtml(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
</script>
@endsection
