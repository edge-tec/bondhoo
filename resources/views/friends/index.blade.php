@extends('layouts.app')

@section('title', 'বন্ধুরা ও সোশ্যাল কানেকশন — Bondhoo')

@section('styles')
<style>
    :root {
        --friends-sidebar-width: 340px;
    }

    .friends-layout {
        display: flex;
        min-height: calc(100vh - 56px);
        background-color: var(--fb-bg);
    }

    /* Left Sidebar */
    .friends-sidebar {
        width: var(--friends-sidebar-width);
        background: var(--fb-card);
        border-right: 1px solid var(--fb-border);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 56px);
        position: sticky;
        top: 56px;
        overflow-y: auto;
        flex-shrink: 0;
        z-index: 10;
    }

    .friends-sidebar-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--fb-border);
    }

    .friends-sidebar-title {
        font-size: 24px;
        font-weight: 800;
        color: var(--fb-text-primary);
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .friends-search-box {
        position: relative;
        margin-bottom: 6px;
    }

    .friends-search-box input {
        width: 100%;
        height: 38px;
        background: var(--fb-bg);
        border: 1px solid transparent;
        border-radius: 20px;
        padding: 0 16px 0 38px;
        font-size: 14px;
        color: var(--fb-text-primary);
        outline: none;
        transition: all 0.2s;
    }

    .friends-search-box input:focus {
        background: var(--fb-card);
        border-color: var(--fb-primary);
        box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.2);
    }

    .friends-search-box svg {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--fb-text-secondary);
        width: 16px;
        height: 16px;
    }

    .friends-nav-group {
        padding: 12px 10px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .friends-nav-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 8px;
        color: var(--fb-text-primary);
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        transition: background 0.15s;
    }

    .friends-nav-item:hover {
        background: var(--fb-hover);
    }

    .friends-nav-item.active {
        background: rgba(24, 119, 242, 0.12);
        color: var(--fb-primary);
    }

    .friends-nav-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .friends-nav-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--fb-btn-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--fb-text-primary);
        flex-shrink: 0;
    }

    .friends-nav-item.active .friends-nav-icon {
        background: var(--fb-primary);
        color: white;
    }

    .friends-nav-badge {
        background: var(--fb-red);
        color: white;
        font-size: 12px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 12px;
    }

    .friends-nav-count {
        font-size: 13px;
        color: var(--fb-text-secondary);
        font-weight: 500;
    }

    .sidebar-section-title {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--fb-text-secondary);
        padding: 14px 14px 6px;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Main Content */
    .friends-content {
        flex: 1;
        padding: 24px 32px;
        overflow-y: auto;
    }

    .friends-top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .friends-heading-area h1 {
        font-size: 26px;
        font-weight: 800;
        color: var(--fb-text-primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .friends-heading-sub {
        font-size: 14px;
        color: var(--fb-text-secondary);
        margin-top: 4px;
    }

    .friends-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .friends-select-ctrl {
        height: 38px;
        border-radius: 8px;
        border: 1px solid var(--fb-border);
        background: var(--fb-card);
        color: var(--fb-text-primary);
        padding: 0 12px;
        font-size: 14px;
        font-weight: 600;
        outline: none;
        cursor: pointer;
    }

    .view-switcher-btn {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        border: 1px solid var(--fb-border);
        background: var(--fb-card);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--fb-text-secondary);
        cursor: pointer;
        transition: all 0.15s;
    }

    .view-switcher-btn.active, .view-switcher-btn:hover {
        background: var(--fb-primary);
        color: white;
        border-color: var(--fb-primary);
    }

    /* Bulk actions bar */
    .bulk-bar {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 12px;
        padding: 12px 18px;
        margin-bottom: 20px;
        display: none;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow-sm);
        animation: fadeIn 0.2s ease;
    }

    .bulk-bar.active {
        display: flex;
    }

    .bulk-btn-group {
        display: flex;
        gap: 8px;
    }

    /* Cards Grid */
    .friends-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 18px;
    }

    .friends-grid.list-view {
        grid-template-columns: 1fr;
    }

    /* Friend Card */
    .friend-card {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .friend-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .friends-grid.list-view .friend-card {
        flex-direction: row;
        align-items: center;
        padding: 14px 18px;
    }

    .card-cover {
        height: 80px;
        background: linear-gradient(135deg, #1877f2 0%, #00c6ff 100%);
        position: relative;
    }

    .friends-grid.list-view .card-cover {
        display: none;
    }

    .card-avatar-wrapper {
        position: relative;
        width: 76px;
        height: 76px;
        margin: -38px auto 0;
    }

    .friends-grid.list-view .card-avatar-wrapper {
        margin: 0 16px 0 0;
        width: 56px;
        height: 56px;
    }

    .card-avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 3px solid var(--fb-card);
        object-fit: cover;
        background: #e2e8f0;
    }

    .online-indicator-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: var(--fb-green);
        border: 2px solid var(--fb-card);
        position: absolute;
        bottom: 2px;
        right: 2px;
    }

    .card-body {
        padding: 14px 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
        text-align: center;
    }

    .friends-grid.list-view .card-body {
        text-align: left;
        padding: 0;
    }

    .card-name-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-bottom: 2px;
    }

    .friends-grid.list-view .card-name-row {
        justify-content: flex-start;
    }

    .card-name {
        font-size: 16px;
        font-weight: 700;
        color: var(--fb-text-primary);
        text-decoration: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .card-name:hover {
        text-decoration: underline;
    }

    .card-username {
        font-size: 13px;
        color: var(--fb-text-secondary);
        margin-bottom: 6px;
    }

    .mutual-count-link {
        font-size: 13px;
        color: var(--fb-text-secondary);
        margin-bottom: 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
    }

    .mutual-count-link:hover {
        color: var(--fb-primary);
        text-decoration: underline;
    }

    .card-tag {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        background: var(--fb-hover);
        color: var(--fb-text-secondary);
        margin: 0 auto 10px;
    }

    .card-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: auto;
    }

    .friends-grid.list-view .card-actions {
        flex-direction: row;
        margin-top: 0;
        margin-left: auto;
        align-items: center;
    }

    .btn-action-primary {
        background: var(--fb-primary);
        color: white;
        border: none;
        border-radius: 8px;
        height: 36px;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        transition: background 0.15s;
        text-decoration: none;
        padding: 0 14px;
    }

    .btn-action-primary:hover {
        background: var(--fb-primary-hover);
    }

    .btn-action-secondary {
        background: var(--fb-btn-bg);
        color: var(--fb-text-primary);
        border: none;
        border-radius: 8px;
        height: 36px;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        transition: background 0.15s;
        text-decoration: none;
        padding: 0 14px;
    }

    .btn-action-secondary:hover {
        background: var(--fb-hover);
    }

    .btn-action-danger {
        background: #fee2e2;
        color: #dc2626;
        border: none;
        border-radius: 8px;
        height: 36px;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        transition: background 0.15s;
        padding: 0 14px;
    }

    .btn-action-danger:hover {
        background: #fecaca;
    }

    .btn-circle-menu {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        background: var(--fb-btn-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--fb-text-primary);
        transition: background 0.15s;
    }

    .btn-circle-menu:hover {
        background: var(--fb-hover);
    }

    /* Dropdown Action Menu */
    .card-dropdown {
        position: relative;
    }

    .card-dropdown-menu {
        position: absolute;
        right: 0;
        top: 42px;
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 10px;
        box-shadow: var(--shadow-lg);
        width: 220px;
        padding: 6px;
        display: none;
        flex-direction: column;
        z-index: 50;
    }

    .card-dropdown-menu.show {
        display: flex;
    }

    .card-dropdown-item {
        padding: 9px 12px;
        font-size: 14px;
        color: var(--fb-text-primary);
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.15s;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }

    .card-dropdown-item:hover {
        background: var(--fb-hover);
    }

    .card-dropdown-item.danger {
        color: var(--fb-red);
    }

    /* Birthday celebratory card */
    .birthday-section {
        margin-bottom: 28px;
    }

    .birthday-section-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--fb-text-primary);
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .birthday-card {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
        box-shadow: var(--shadow-sm);
    }

    .birthday-card.today {
        background: linear-gradient(to right, rgba(24, 119, 242, 0.05), #ffffff);
        border-color: #bfdbfe;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        max-width: 450px;
        margin: 0 auto;
    }

    .empty-state-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--fb-hover);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: var(--fb-text-secondary);
    }

    .empty-state-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--fb-text-primary);
        margin-bottom: 8px;
    }

    .empty-state-text {
        font-size: 14px;
        color: var(--fb-text-secondary);
        line-height: 1.5;
        margin-bottom: 20px;
    }

    /* Modal styling */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        backdrop-filter: blur(2px);
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-box {
        background: var(--fb-card);
        border-radius: 12px;
        width: 100%;
        max-width: 480px;
        padding: 24px;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--fb-border);
    }

    .modal-title {
        font-size: 20px;
        font-weight: 800;
        margin-bottom: 12px;
        color: var(--fb-text-primary);
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    /* Toast notification */
    .toast-container {
        position: fixed;
        bottom: 24px;
        right: 24px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        z-index: 2000;
    }

    .toast-item {
        background: #1e293b;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: var(--shadow-lg);
        animation: slideIn 0.25s ease;
    }

    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @media (max-width: 900px) {
        .friends-layout {
            flex-direction: column;
        }
        .friends-sidebar {
            width: 100%;
            height: auto;
            position: relative;
            top: 0;
            border-right: none;
            border-bottom: 1px solid var(--fb-border);
        }
        .friends-content {
            padding: 16px;
        }
    }
</style>
@endsection

@section('content')
<div class="friends-layout">
    <!-- Left Navigation Sidebar -->
    <aside class="friends-sidebar" aria-label="Friends Navigation">
        <div class="friends-sidebar-header">
            <div class="friends-sidebar-title">
                <span>বন্ধু ও সংযোগ</span>
                <span class="friends-nav-count" id="sidebarFriendsTotal">{{ $counters['total_friends'] }} জন বন্ধু</span>
            </div>
            <div class="friends-search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="sidebarQuickSearch" placeholder="তালিকায় খুঁজুন..." value="{{ $search }}">
            </div>
        </div>

        <nav class="friends-nav-group">
            <a href="/friends?tab=all" class="friends-nav-item {{ $tab === 'all' ? 'active' : '' }}">
                <div class="friends-nav-left">
                    <div class="friends-nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <span>সকল বন্ধুরা</span>
                </div>
                <span class="friends-nav-count" id="sidebarFriendsCount">{{ $counters['total_friends'] }}</span>
            </a>

            <a href="/friends?tab=requests" class="friends-nav-item {{ $tab === 'requests' ? 'active' : '' }}" id="navItemRequests">
                <div class="friends-nav-left">
                    <div class="friends-nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    </div>
                    <span>অনুরোধসমূহ</span>
                </div>
                @if($counters['pending_requests'] > 0)
                    <span class="friends-nav-badge" id="sidebarPendingBadge">{{ $counters['pending_requests'] }}</span>
                @else
                    <span class="friends-nav-count" id="sidebarPendingBadge">0</span>
                @endif
            </a>

            <a href="/friends?tab=sent" class="friends-nav-item {{ $tab === 'sent' ? 'active' : '' }}">
                <div class="friends-nav-left">
                    <div class="friends-nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </div>
                    <span>পাঠানো অনুরোধ</span>
                </div>
                <span class="friends-nav-count" id="sidebarSentCount">{{ $counters['sent_requests'] }}</span>
            </a>

            <a href="/friends?tab=suggestions" class="friends-nav-item {{ $tab === 'suggestions' ? 'active' : '' }}">
                <div class="friends-nav-left">
                    <div class="friends-nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </div>
                    <span>সুপারিশসমূহ (Suggestions)</span>
                </div>
            </a>

            <a href="/friends?tab=birthdays" class="friends-nav-item {{ $tab === 'birthdays' ? 'active' : '' }}">
                <div class="friends-nav-left">
                    <div class="friends-nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <span>জন্মদিন</span>
                </div>
                @if($counters['birthdays_today'] > 0)
                    <span class="friends-nav-badge" style="background:#10b981;">{{ $counters['birthdays_today'] }} আজ</span>
                @endif
            </a>

            <!-- Custom Lists Section -->
            <div class="sidebar-section-title">
                <span>কাস্টম ফ্রেন্ড লিস্ট</span>
                <button type="button" onclick="openCreateListModal()" style="background:none;border:none;color:var(--fb-primary);cursor:pointer;font-weight:700;font-size:16px;" title="নতুন লিস্ট তৈরি করুন">+</button>
            </div>

            @foreach($customLists as $cList)
                <a href="/friends?tab=custom_lists&list_id={{ $cList->id }}" class="friends-nav-item {{ $tab === 'custom_lists' && request('list_id') == $cList->id ? 'active' : '' }}">
                    <div class="friends-nav-left">
                        <div class="friends-nav-icon" style="background:transparent;border:1px dashed var(--fb-border);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                        </div>
                        <span>{{ $cList->name }}</span>
                    </div>
                    <span class="friends-nav-count">{{ $cList->members_count }}</span>
                </a>
            @endforeach
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="friends-content">
        <!-- Top Toolbar -->
        <div class="friends-top-bar">
            <div class="friends-heading-area">
                <h1>
                    @if($tab === 'requests')
                        বন্ধু অনুরোধসমূহ
                    @elseif($tab === 'sent')
                        পাঠানো অনুরোধ
                    @elseif($tab === 'suggestions')
                        আপনার পরিচিত হতে পারেন
                    @elseif($tab === 'birthdays')
                        বন্ধুদের জন্মদিন উদযাপন
                    @elseif($tab === 'custom_lists')
                        {{ $selectedList ? $selectedList->name : 'কাস্টম ফ্রেন্ড লিস্ট' }}
                    @else
                        সকল বন্ধুরা
                    @endif
                </h1>
                <div class="friends-heading-sub">
                    @if($tab === 'all')
                        আপনার সাথে সংযুক্ত সকল রিয়েল-টাইম প্রোফাইল
                    @elseif($tab === 'requests')
                        আপনাকে পাঠানো ফ্রেন্ড রিকোয়েস্ট ম্যানেজ করুন
                    @elseif($tab === 'suggestions')
                        পারস্পরিক বন্ধু, গ্রুপ ও পেজ সিগনাল থেকে সুপারিশ
                    @elseif($tab === 'birthdays')
                        আজকের ও সামনের জন্মদিনের তালিকা
                    @endif
                </div>
            </div>

            <div class="friends-controls">
                @if($tab === 'all')
                    <!-- Filter Dropdown -->
                    <select class="friends-select-ctrl" id="friendFilterSelect" aria-label="বন্ধুদের তালিকা ফিল্টার করুন" onchange="applyFilter(this.value)">
                        <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>সব বন্ধুরা</option>
                        <option value="online" {{ $filter === 'online' ? 'selected' : '' }}>অনলাইন</option>
                        <option value="close_friends" {{ $filter === 'close_friends' ? 'selected' : '' }}>ক্লোজ ফ্রেন্ডস</option>
                        <option value="favorites" {{ $filter === 'favorites' ? 'selected' : '' }}>ফেভারিট</option>
                        <option value="verified" {{ $filter === 'verified' ? 'selected' : '' }}>ভেরিফাইড প্রোফাইল</option>
                        <option value="same_city" {{ $filter === 'same_city' ? 'selected' : '' }}>একই শহর</option>
                        <option value="same_country" {{ $filter === 'same_country' ? 'selected' : '' }}>একই দেশ</option>
                        <option value="following" {{ $filter === 'following' ? 'selected' : '' }}>যাদের ফলো করছেন</option>
                    </select>

                    <!-- Sort Dropdown -->
                    <select class="friends-select-ctrl" id="friendSortSelect" aria-label="বন্ধুদের তালিকা সাজান" onchange="applySort(this.value)">
                        <option value="recently_added" {{ $sort === 'recently_added' ? 'selected' : '' }}>নতুন যোগ হওয়া</option>
                        <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>নাম (A থেকে Z)</option>
                        <option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>নাম (Z থেকে A)</option>
                        <option value="recently_active" {{ $sort === 'recently_active' ? 'selected' : '' }}>সম্প্রতি সক্রিয়</option>
                    </select>

                    <!-- View Switcher -->
                    <div style="display:flex;gap:4px;">
                        <button class="view-switcher-btn active" id="btnGridView" onclick="switchView('grid')" title="গ্রিড ভিউ" aria-label="গ্রিড ভিউ">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        </button>
                        <button class="view-switcher-btn" id="btnListView" onclick="switchView('list')" title="কমপ্যাক্ট লিস্ট ভিউ" aria-label="কমপ্যাক্ট লিস্ট ভিউ">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Bulk Operations Bar (on Requests tab) -->
        @if($tab === 'requests' && $requests && $requests->count() > 0)
            <div class="bulk-bar" id="requestsBulkBar">
                <div style="display:flex;align-items:center;gap:12px;">
                    <input type="checkbox" id="selectAllRequests" onchange="toggleSelectAllRequests(this.checked)" style="width:18px;height:18px;cursor:pointer;">
                    <label for="selectAllRequests" style="font-weight:700;font-size:14px;cursor:pointer;">সব সিলেক্ট করুন (<span id="selectedCountText">0</span>)</label>
                </div>
                <div class="bulk-btn-group">
                    <button type="button" class="btn-action-primary" onclick="bulkAcceptSelected()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        সব গ্রহণ করুন
                    </button>
                    <button type="button" class="btn-action-secondary" onclick="bulkDeclineSelected()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        সব প্রত্যাখ্যান করুন
                    </button>
                    <button type="button" class="btn-action-danger" onclick="bulkBlockSelected()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                        ব্লক করুন
                    </button>
                </div>
            </div>
        @endif

        <!-- TAB 1: ALL FRIENDS -->
        @if($tab === 'all')
            @if($friends && $friends->count() > 0)
                <div class="friends-grid" id="friendsGrid">
                    @foreach($friends as $friend)
                        <div class="friend-card" id="friend-card-{{ $friend->id }}">
                            <div class="card-cover"></div>
                            <div class="card-avatar-wrapper">
                                <img src="{{ $friend->profile?->avatar_url ?: '/images/default-avatar.svg' }}" alt="{{ $friend->name }}" class="card-avatar" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                @if($friend->is_online)
                                    <span class="online-indicator-dot" title="সক্রিয় আছেন"></span>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="card-name-row">
                                    <a href="{{ getUserProfileUrl($friend) }}" class="card-name">{{ $friend->name }}</a>
                                    @if($friend->is_verified)
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#1877f2" title="ভেরিফাইড"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                    @endif
                                </div>
                                <div class="card-username">&#64;{{ $friend->username }}</div>

                                <div class="mutual-count-link" onclick="openMutualModal({{ $friend->id }}, '{{ addslashes(e($friend->name)) }}')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                                    <span>{{ $friend->mutual_count }} জন পারস্পরিক বন্ধু</span>
                                </div>

                                @if($friend->is_favorite)
                                    <span class="card-tag" style="background:#fef3c7;color:#b45309;">★ ফেভারিট</span>
                                @elseif($friend->is_close_friend)
                                    <span class="card-tag" style="background:#e0e7ff;color:#4338ca;">♥ ক্লোজ ফ্রেন্ড</span>
                                @elseif($friend->profile?->city)
                                    <span class="card-tag">{{ $friend->profile->city }}</span>
                                @endif

                                <div class="card-actions">
                                    <a href="/messages?user={{ $friend->id }}" class="btn-action-primary">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                        মেসেজ
                                    </a>

                                    <div class="card-dropdown">
                                        <button class="btn-action-secondary" onclick="toggleCardMenu({{ $friend->id }})">
                                            <span>অন্যান্য</span>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                        </button>
                                        <div class="card-dropdown-menu" id="cardMenu-{{ $friend->id }}">
                                            <a href="{{ getUserProfileUrl($friend) }}" class="card-dropdown-item">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                                প্রোফাইল দেখুন
                                            </a>
                                            <button type="button" class="card-dropdown-item" onclick="toggleFavoriteFriend({{ $friend->id }})">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $friend->is_favorite ? '#f59e0b' : 'none' }}" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                                {{ $friend->is_favorite ? 'ফেভারিট থেকে সরান' : 'ফেভারিটে যোগ করুন' }}
                                            </button>
                                            <button type="button" class="card-dropdown-item" onclick="toggleCloseFriend({{ $friend->id }})">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $friend->is_close_friend ? '#ef4444' : 'none' }}" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                                {{ $friend->is_close_friend ? 'ক্লোজ ফ্রেন্ড তালিকা থেকে সরান' : 'ক্লোজ ফ্রেন্ডে যোগ করুন' }}
                                            </button>
                                            <button type="button" class="card-dropdown-item danger" onclick="confirmUnfriend({{ $friend->id }}, '{{ addslashes(e($friend->name)) }}')">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                                আনফ্রেন্ড করুন
                                            </button>
                                            <button type="button" class="card-dropdown-item danger" onclick="confirmBlock({{ $friend->id }}, '{{ addslashes(e($friend->name)) }}')">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                                ব্লক করুন
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="margin-top: 24px;">
                    {{ $friends->links() }}
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div class="empty-state-title">কোনো বন্ধু পাওয়া যায়নি</div>
                    <div class="empty-state-text">আপনার ফ্রেন্ড লিস্টে এখনও কেউ নেই অথবা ফিল্টারে কোনো ফলাফল মিলেনি। নতুন পরিচিতদের সাথে যোগাযোগ শুরু করুন!</div>
                    <a href="/friends?tab=suggestions" class="btn-action-primary" style="display:inline-flex;">বন্ধু খুঁজুন ও সুপারিশ দেখুন</a>
                </div>
            @endif
        @endif

        <!-- TAB 2: FRIEND REQUESTS -->
        @if($tab === 'requests')
            @if($requests && $requests->count() > 0)
                <div class="friends-grid" id="requestsGrid">
                    @foreach($requests as $req)
                        @php $sender = $req->user; @endphp
                        @if($sender)
                            <div class="friend-card" id="request-card-{{ $sender->id }}">
                                <div style="position:absolute;top:10px;left:10px;z-index:5;">
                                    <input type="checkbox" class="request-checkbox" value="{{ $sender->id }}" onchange="updateSelectedCount()" style="width:18px;height:18px;cursor:pointer;">
                                </div>
                                <div class="card-cover"></div>
                                <div class="card-avatar-wrapper">
                                    <img src="{{ $sender->profile?->avatar_url ?: '/images/default-avatar.svg' }}" alt="{{ $sender->name }}" class="card-avatar" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                    @if($sender->is_online)
                                        <span class="online-indicator-dot"></span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <div class="card-name-row">
                                        <a href="{{ getUserProfileUrl($sender) }}" class="card-name">{{ $sender->name }}</a>
                                        @if($sender->is_verified)
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#1877f2"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                        @endif
                                    </div>
                                    <div class="card-username">&#64;{{ $sender->username }}</div>

                                    <div class="mutual-count-link" onclick="openMutualModal({{ $sender->id }}, '{{ addslashes(e($sender->name)) }}')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                                        <span>{{ $sender->mutual_count }} জন পারস্পরিক বন্ধু</span>
                                    </div>

                                    <div class="card-actions">
                                        <button type="button" class="btn-action-primary" onclick="acceptFriendRequest({{ $sender->id }})">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            অনুমোদন করুন
                                        </button>
                                        <button type="button" class="btn-action-secondary" onclick="declineFriendRequest({{ $sender->id }})">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                            প্রত্যাখ্যান
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                <div style="margin-top:24px;">{{ $requests->links() }}</div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div class="empty-state-title">কোনো পেন্ডিং ফ্রেন্ড রিকোয়েস্ট নেই</div>
                    <div class="empty-state-text">আপাতত আপনার কাছে কোনো ফ্রেন্ড রিকোয়েস্ট জমা নেই। আপনি চাইলে নতুন বন্ধুদের সাথে সংযোগ তৈরি করতে পারেন।</div>
                    <a href="/friends?tab=suggestions" class="btn-action-primary" style="display:inline-flex;">বন্ধু সুপারিশ দেখুন</a>
                </div>
            @endif
        @endif

        <!-- TAB 3: SENT REQUESTS -->
        @if($tab === 'sent')
            @if($sentRequests && $sentRequests->count() > 0)
                <div class="friends-grid">
                    @foreach($sentRequests as $req)
                        @php $target = $req->friend; @endphp
                        @if($target)
                            <div class="friend-card" id="sent-card-{{ $target->id }}">
                                <div class="card-cover"></div>
                                <div class="card-avatar-wrapper">
                                    <img src="{{ $target->profile?->avatar_url ?: '/images/default-avatar.svg' }}" alt="{{ $target->name }}" class="card-avatar" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                </div>
                                <div class="card-body">
                                    <div class="card-name-row">
                                        <a href="{{ getUserProfileUrl($target) }}" class="card-name">{{ $target->name }}</a>
                                        @if($target->is_verified)
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#1877f2"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                        @endif
                                    </div>
                                    <div class="card-username">&#64;{{ $target->username }}</div>
                                    <div class="card-tag">অনুরোধ পাঠানো হয়েছে</div>

                                    <div class="card-actions">
                                        <button type="button" class="btn-action-secondary" onclick="cancelFriendRequest({{ $target->id }})">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                            অনুরোধ বাতিল করুন
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                <div style="margin-top:24px;">{{ $sentRequests->links() }}</div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </div>
                    <div class="empty-state-title">পাঠানো কোনো অনুরোধ বাকি নেই</div>
                    <div class="empty-state-text">আপনি যাদের ফ্রেন্ড রিকোয়েস্ট পাঠিয়েছিলেন তারা গ্রহণ করেছেন অথবা আপনি কোনো পেন্ডিং অনুরোধ রাখেননি।</div>
                </div>
            @endif
        @endif

        <!-- TAB 4: SUGGESTIONS -->
        @if($tab === 'suggestions')
            @if($suggestions && $suggestions->count() > 0)
                <div class="friends-grid">
                    @foreach($suggestions as $cand)
                        <div class="friend-card" id="suggestion-card-{{ $cand->id }}">
                            <div class="card-cover"></div>
                            <div class="card-avatar-wrapper">
                                <img src="{{ $cand->profile?->avatar_url ?: '/images/default-avatar.svg' }}" alt="{{ $cand->name }}" class="card-avatar" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                @if($cand->is_online)
                                    <span class="online-indicator-dot"></span>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="card-name-row">
                                    <a href="{{ getUserProfileUrl($cand) }}" class="card-name">{{ $cand->name }}</a>
                                    @if($cand->is_verified)
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#1877f2"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                    @endif
                                </div>
                                <div class="card-username">&#64;{{ $cand->username }}</div>

                                <div class="mutual-count-link" onclick="openMutualModal({{ $cand->id }}, '{{ addslashes(e($cand->name)) }}')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                                    <span>{{ $cand->mutual_count }} জন পারস্পরিক বন্ধু</span>
                                </div>

                                @if(!empty($cand->suggestion_reasons))
                                    <span class="card-tag">{{ $cand->suggestion_reasons[0] }}</span>
                                @endif

                                <div class="card-actions">
                                    <button type="button" class="btn-action-primary" id="btn-add-{{ $cand->id }}" onclick="sendFriendRequest({{ $cand->id }})">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                        বন্ধু হিসেবে যোগ করুন
                                    </button>
                                    <button type="button" class="btn-action-secondary" onclick="dismissSuggestion({{ $cand->id }})">
                                        সরিয়ে দিন
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </div>
                    <div class="empty-state-title">এই মুহূর্তে কোনো সুপারিশ নেই</div>
                    <div class="empty-state-text">আপনার কমিউনিটি, গ্রুপ এবং পেজগুলোতে আরও যুক্ত হোন যাতে Bondhoo আপনার নেটওয়ার্ক প্রসারিত করতে সাহায্য করতে পারে।</div>
                </div>
            @endif
        @endif

        <!-- TAB 5: BIRTHDAYS -->
        @if($tab === 'birthdays')
            @if($birthdays)
                <!-- Today -->
                <div class="birthday-section">
                    <div class="birthday-section-title">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.2"><path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8"></path><path d="M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1"></path><path d="M2 21h20"></path><path d="M7 8v3"></path><path d="M12 8v3"></path><path d="M17 8v3"></path><path d="M7 4h.01"></path><path d="M12 4h.01"></path><path d="M17 4h.01"></path></svg>
                        <span>আজকের জন্মদিন ({{ count($birthdays['today']) }})</span>
                    </div>
                    @if(count($birthdays['today']) > 0)
                        @foreach($birthdays['today'] as $bFriend)
                            <div class="birthday-card today">
                                <div style="display:flex;align-items:center;gap:14px;">
                                    <img src="{{ $bFriend['avatar_url'] ?: '/images/default-avatar.svg' }}" alt="{{ $bFriend['name'] }}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
                                    <div>
                                        <a href="{{ getUserProfileUrl($bFriend['username']) }}" style="font-weight:700;font-size:15px;color:var(--fb-text-primary);text-decoration:none;">{{ $bFriend['name'] }}</a>
                                        <div style="font-size:13px;color:#059669;font-weight:600;">আজকের জন্মদিন! শুভেচ্ছা পাঠান 🎉</div>
                                    </div>
                                </div>
                                <a href="/messages?user={{ $bFriend['id'] }}" class="btn-action-primary">
                                    শুভেচ্ছা পাঠান
                                </a>
                            </div>
                        @endforeach
                    @else
                        <div style="font-size:14px;color:var(--fb-text-secondary);padding:10px 0;">আজ আপনার কোনো বন্ধুর জন্মদিন নেই।</div>
                    @endif
                </div>

                <!-- Upcoming -->
                <div class="birthday-section">
                    <div class="birthday-section-title">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span>আসন্ন জন্মদিনসমূহ (পরবর্তী ৩০ দিন) ({{ count($birthdays['upcoming']) }})</span>
                    </div>
                    @if(count($birthdays['upcoming']) > 0)
                        @foreach($birthdays['upcoming'] as $bFriend)
                            <div class="birthday-card">
                                <div style="display:flex;align-items:center;gap:14px;">
                                    <img src="{{ $bFriend['avatar_url'] ?: '/images/default-avatar.svg' }}" alt="{{ $bFriend['name'] }}" style="width:44px;height:44px;border-radius:50%;object-fit:cover;">
                                    <div>
                                        <a href="{{ getUserProfileUrl($bFriend['username']) }}" style="font-weight:700;font-size:15px;color:var(--fb-text-primary);text-decoration:none;">{{ $bFriend['name'] }}</a>
                                        <div style="font-size:13px;color:var(--fb-text-secondary);">{{ $bFriend['birth_date'] }} ({{ $bFriend['days_away'] }} দিন পর)</div>
                                    </div>
                                </div>
                                <a href="{{ getUserProfileUrl($bFriend['username']) }}" class="btn-action-secondary">প্রোফাইল দেখুন</a>
                            </div>
                        @endforeach
                    @else
                        <div style="font-size:14px;color:var(--fb-text-secondary);padding:10px 0;">পরবর্তী ৩০ দিনে কোনো আসন্ন জন্মদিন নেই।</div>
                    @endif
                </div>
            @endif
        @endif

        <!-- TAB 6: CUSTOM LISTS -->
        @if($tab === 'custom_lists' && $selectedList)
            <div style="background:var(--fb-card);border:1px solid var(--fb-border);border-radius:12px;padding:20px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h2 style="font-size:22px;font-weight:800;color:var(--fb-text-primary);">{{ $selectedList->name }}</h2>
                    <div style="font-size:14px;color:var(--fb-text-secondary);margin-top:4px;">{{ $selectedList->description ?: 'কাস্টম ফ্রেন্ড লিস্ট' }} &bull; {{ $listMembers ? $listMembers->total() : 0 }} জন সদস্য</div>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" class="btn-action-danger" onclick="deleteCustomList({{ $selectedList->id }})">লিস্ট মুছুন</button>
                </div>
            </div>

            @if($listMembers && $listMembers->count() > 0)
                <div class="friends-grid">
                    @foreach($listMembers as $member)
                        <div class="friend-card" id="list-member-{{ $member->id }}">
                            <div class="card-cover"></div>
                            <div class="card-avatar-wrapper">
                                <img src="{{ $member->profile?->avatar_url ?: '/images/default-avatar.svg' }}" alt="{{ $member->name }}" class="card-avatar">
                            </div>
                            <div class="card-body">
                                <a href="{{ getUserProfileUrl($member) }}" class="card-name">{{ $member->name }}</a>
                                <div class="card-username">&#64;{{ $member->username }}</div>
                                <div class="card-actions">
                                    <button type="button" class="btn-action-danger" onclick="removeFromList({{ $selectedList->id }}, {{ $member->id }})">তালিকা থেকে সরান</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="margin-top:20px;">{{ $listMembers->links() }}</div>
            @else
                <div class="empty-state">
                    <div class="empty-state-title">এই লিস্টে এখনও কোনো বন্ধু নেই</div>
                    <div class="empty-state-text">আপনার ফ্রেন্ড তালিকা থেকে বন্ধুদের এই গ্রুপে যুক্ত করতে পারবেন।</div>
                </div>
            @endif
        @endif
    </main>
</div>

<!-- Modal: Mutual Friends -->
<div class="modal-overlay" id="mutualModal">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div class="modal-title" id="mutualModalTitle">পারস্পরিক বন্ধুরা</div>
            <button type="button" onclick="closeMutualModal()" style="background:none;border:none;cursor:pointer;font-size:22px;color:var(--fb-text-secondary);">&times;</button>
        </div>
        <div id="mutualModalContent" style="max-height:360px;overflow-y:auto;display:flex;flex-direction:column;gap:10px;">
            <div style="text-align:center;padding:20px;color:var(--fb-text-secondary);">লোড হচ্ছে...</div>
        </div>
    </div>
</div>

<!-- Modal: Confirm Unfriend -->
<div class="modal-overlay" id="unfriendModal">
    <div class="modal-box">
        <div class="modal-title">বন্ধু তালিকা থেকে সরাতে চান?</div>
        <div style="font-size:14px;color:var(--fb-text-secondary);line-height:1.5;">
            আপনি কি নিশ্চিত যে <strong id="unfriendTargetName" style="color:var(--fb-text-primary);"></strong>-কে আপনার ফ্রেন্ড লিস্ট থেকে বাদ দিতে চান? আনফ্রেন্ড করার পর তিনি আর আপনার ফ্রেন্ডস-অনলি পোস্ট দেখতে পাবেন না।
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-action-secondary" onclick="closeUnfriendModal()">বাতিল</button>
            <button type="button" class="btn-action-danger" id="unfriendConfirmBtn" onclick="executeUnfriend()">আনফ্রেন্ড নিশ্চিত করুন</button>
        </div>
    </div>
</div>

<!-- Modal: Confirm Block -->
<div class="modal-overlay" id="blockModal">
    <div class="modal-box">
        <div class="modal-title" style="color:var(--fb-red);">ব্যবহারকারীকে ব্লক করতে চান?</div>
        <div style="font-size:14px;color:var(--fb-text-secondary);line-height:1.5;">
            <strong id="blockTargetName" style="color:var(--fb-text-primary);"></strong>-কে ব্লক করলে:
            <ul style="margin:10px 0 0 20px;color:var(--fb-text-secondary);font-size:13px;">
                <li>ফ্রেন্ডশিপ বাতিল হয়ে যাবে</li>
                <li>তিনি আপনার প্রোফাইল বা পোস্ট দেখতে পাবেন না</li>
                <li>আপনাকে মেসেজ বা কল পাঠাতে পারবেন না</li>
                <li>পারস্পরিক ট্যাগ বা কমেন্ট রেস্ট্রিক্ট হবে</li>
            </ul>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-action-secondary" onclick="closeBlockModal()">বাতিল</button>
            <button type="button" class="btn-action-danger" id="blockConfirmBtn" onclick="executeBlock()">ব্লক নিশ্চিত করুন</button>
        </div>
    </div>
</div>

<!-- Modal: Create Custom List -->
<div class="modal-overlay" id="createListModal">
    <div class="modal-box">
        <div class="modal-title">নতুন ফ্রেন্ড লিস্ট তৈরি করুন</div>
        <form onsubmit="handleCreateList(event)">
            <div style="margin-bottom:14px;">
                <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px;">লিস্টের নাম</label>
                <input type="text" id="newListName" required placeholder="উদা: কলেজ ফ্রেন্ডস, অফিস টিম, পরিবার" style="width:100%;height:40px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-bg);padding:0 12px;font-size:14px;outline:none;color:var(--fb-text-primary);">
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px;">বিবরণ (ঐচ্ছিক)</label>
                <input type="text" id="newListDesc" placeholder="সংক্ষিপ্ত বিবরণ লিখুন..." style="width:100%;height:40px;border-radius:8px;border:1px solid var(--fb-border);background:var(--fb-bg);padding:0 12px;font-size:14px;outline:none;color:var(--fb-text-primary);">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-action-secondary" onclick="closeCreateListModal()">বাতিল</button>
                <button type="submit" class="btn-action-primary">লিস্ট তৈরি করুন</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>
@endsection

@section('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const currentUserId = {{ $currentUser->id }};
    let unfriendTargetId = null;
    let blockTargetId = null;

    // Toast Utility
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast-item';
        if (type === 'error') toast.style.background = '#dc2626';
        if (type === 'success') toast.style.background = '#059669';
        toast.innerHTML = `<span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // View Switcher
    function switchView(view) {
        const grid = document.getElementById('friendsGrid') || document.getElementById('requestsGrid');
        const btnGrid = document.getElementById('btnGridView');
        const btnList = document.getElementById('btnListView');
        if (!grid) return;

        if (view === 'list') {
            grid.classList.add('list-view');
            btnList.classList.add('active');
            btnGrid.classList.remove('active');
        } else {
            grid.classList.remove('list-view');
            btnGrid.classList.add('active');
            btnList.classList.remove('active');
        }
    }

    // Filter and Sort
    function applyFilter(val) {
        const url = new URL(window.location.href);
        url.searchParams.set('filter', val);
        window.location.href = url.toString();
    }

    function applySort(val) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', val);
        window.location.href = url.toString();
    }

    // Quick Search Input in Sidebar
    document.getElementById('sidebarQuickSearch')?.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            const url = new URL(window.location.href);
            if (this.value.trim() !== '') {
                url.searchParams.set('search', this.value.trim());
            } else {
                url.searchParams.delete('search');
            }
            window.location.href = url.toString();
        }
    });

    // Toggle Dropdown Action Menu
    function toggleCardMenu(id) {
        document.querySelectorAll('.card-dropdown-menu').forEach(el => {
            if (el.id !== `cardMenu-${id}`) el.classList.remove('show');
        });
        const menu = document.getElementById(`cardMenu-${id}`);
        if (menu) menu.classList.toggle('show');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.card-dropdown')) {
            document.querySelectorAll('.card-dropdown-menu').forEach(el => el.classList.remove('show'));
        }
    });

    // API: Accept Friend Request
    async function acceptFriendRequest(id) {
        try {
            const res = await fetch(`/api/v1/friends/${id}/accept`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                showToast('ফ্রেন্ড রিকোয়েস্ট অনুমোদিত হয়েছে!', 'success');
                const card = document.getElementById(`request-card-${id}`);
                if (card) {
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 250);
                }
                decrementPendingCount();
            } else {
                showToast(data.message || 'অনুরোধ অনুমোদন করা যায়নি।', 'error');
            }
        } catch (err) {
            showToast('নেটওয়ার্ক ত্রুটি। পুনরায় চেষ্টা করুন।', 'error');
        }
    }

    // API: Decline Friend Request
    async function declineFriendRequest(id) {
        try {
            const res = await fetch(`/api/v1/friends/${id}/decline`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                showToast('ফ্রেন্ড রিকোয়েস্ট প্রত্যাখ্যান করা হয়েছে।', 'info');
                const card = document.getElementById(`request-card-${id}`);
                if (card) {
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 250);
                }
                decrementPendingCount();
            } else {
                showToast(data.message || 'প্রত্যাখ্যান করা যায়নি।', 'error');
            }
        } catch (err) {
            showToast('নেটওয়ার্ক ত্রুটি।', 'error');
        }
    }

    // API: Send Friend Request
    async function sendFriendRequest(friendId) {
        const btn = document.getElementById(`btn-add-${friendId}`);
        if (btn) btn.disabled = true;

        try {
            const res = await fetch(`/api/v1/friends/${friendId}/request`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                showToast('ফ্রেন্ড রিকোয়েস্ট সফলভাবে পাঠানো হয়েছে!', 'success');
                if (btn) {
                    btn.innerText = 'অনুরোধ পাঠানো হয়েছে';
                    btn.classList.replace('btn-action-primary', 'btn-action-secondary');
                }
            } else {
                showToast(data.message || 'রিকোয়েস্ট পাঠানো যায়নি।', 'error');
                if (btn) btn.disabled = false;
            }
        } catch (err) {
            showToast('সার্ভার এরর।', 'error');
            if (btn) btn.disabled = false;
        }
    }

    // API: Cancel Outgoing Friend Request
    async function cancelFriendRequest(id) {
        try {
            const res = await fetch(`/api/v1/friends/${id}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                showToast('অনুরোধ বাতিল করা হয়েছে।', 'info');
                const card = document.getElementById(`sent-card-${id}`);
                if (card) card.remove();
            } else {
                showToast(data.message || 'বাতিল করা সম্ভব হয়নি।', 'error');
            }
        } catch (err) {
            showToast('ত্রুটি ঘটেছে।', 'error');
        }
    }

    // Dismiss Suggestion
    function dismissSuggestion(id) {
        const card = document.getElementById(`suggestion-card-${id}`);
        if (card) {
            card.style.opacity = '0';
            setTimeout(() => card.remove(), 200);
        }
    }

    // Unfriend Dialog
    function confirmUnfriend(id, name) {
        unfriendTargetId = id;
        document.getElementById('unfriendTargetName').innerText = name;
        document.getElementById('unfriendModal').classList.add('active');
    }
    function closeUnfriendModal() {
        document.getElementById('unfriendModal').classList.remove('active');
        unfriendTargetId = null;
    }
    async function executeUnfriend() {
        if (!unfriendTargetId) return;
        const btn = document.getElementById('unfriendConfirmBtn');
        btn.disabled = true;

        try {
            const res = await fetch(`/api/v1/friends/${unfriendTargetId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                showToast('বন্ধু তালিকা থেকে সরানো হয়েছে।', 'info');
                const card = document.getElementById(`friend-card-${unfriendTargetId}`);
                if (card) card.remove();
                closeUnfriendModal();
            } else {
                showToast(data.message || 'আনফ্রেন্ড করা যায়নি।', 'error');
            }
        } catch (err) {
            showToast('সার্ভার সমস্যা।', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    // Block Dialog
    function confirmBlock(id, name) {
        blockTargetId = id;
        document.getElementById('blockTargetName').innerText = name;
        document.getElementById('blockModal').classList.add('active');
    }
    function closeBlockModal() {
        document.getElementById('blockModal').classList.remove('active');
        blockTargetId = null;
    }
    async function executeBlock() {
        if (!blockTargetId) return;
        const btn = document.getElementById('blockConfirmBtn');
        btn.disabled = true;

        try {
            const res = await fetch(`/api/v1/friends/${blockTargetId}/block`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                showToast('ব্যবহারকারীকে সফলভাবে ব্লক করা হয়েছে।', 'success');
                const card = document.getElementById(`friend-card-${blockTargetId}`) || document.getElementById(`request-card-${blockTargetId}`);
                if (card) card.remove();
                closeBlockModal();
            } else {
                showToast(data.message || 'ব্লক করা সম্ভব হয়নি।', 'error');
            }
        } catch (err) {
            showToast('নেটওয়ার্ক এরর।', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    // Toggle Favorite
    async function toggleFavoriteFriend(id) {
        try {
            const res = await fetch(`/api/v1/friends/${id}/favorite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 300);
            }
        } catch (e) {
            showToast('ত্রুটি ঘটেছে।', 'error');
        }
    }

    // Toggle Close Friend
    async function toggleCloseFriend(id) {
        try {
            const res = await fetch(`/api/v1/friends/${id}/close`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 300);
            }
        } catch (e) {
            showToast('ত্রুটি ঘটেছে।', 'error');
        }
    }

    // Modal: Mutual Friends
    async function openMutualModal(userId, name) {
        document.getElementById('mutualModalTitle').innerText = `${name}-এর সাথে পারস্পরিক বন্ধুরা`;
        const modal = document.getElementById('mutualModal');
        const content = document.getElementById('mutualModalContent');
        content.innerHTML = '<div style="text-align:center;padding:20px;color:var(--fb-text-secondary);">লোড হচ্ছে...</div>';
        modal.classList.add('active');

        try {
            const res = await fetch(`/api/v1/friends/mutual/${userId}`);
            const data = await res.json();
            if (data.success && data.data.length > 0) {
                content.innerHTML = data.data.map(m => `
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--fb-border);">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <img src="${m.profile?.avatar_url || '/images/default-avatar.svg'}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                            <div>
                                <a href="/u/${m.username}" style="font-weight:700;font-size:14px;color:var(--fb-text-primary);text-decoration:none;">${m.name}</a>
                                <div style="font-size:12px;color:var(--fb-text-secondary);">&#64;${m.username}</div>
                            </div>
                        </div>
                        <a href="/u/${m.username}" class="btn-action-secondary" style="height:32px;font-size:12px;">প্রোফাইল</a>
                    </div>
                `).join('');
            } else {
                content.innerHTML = '<div style="text-align:center;padding:20px;color:var(--fb-text-secondary);">কোনো পারস্পরিক বন্ধু নেই।</div>';
            }
        } catch (err) {
            content.innerHTML = '<div style="text-align:center;padding:20px;color:#dc2626;">পারস্পরিক বন্ধু আনতে ব্যর্থ হয়েছে।</div>';
        }
    }
    function closeMutualModal() {
        document.getElementById('mutualModal').classList.remove('active');
    }

    // Modal: Custom Lists
    function openCreateListModal() {
        document.getElementById('createListModal').classList.add('active');
    }
    function closeCreateListModal() {
        document.getElementById('createListModal').classList.remove('active');
    }
    async function handleCreateList(e) {
        e.preventDefault();
        const name = document.getElementById('newListName').value.trim();
        const description = document.getElementById('newListDesc').value.trim();
        if (!name) return;

        try {
            const res = await fetch('/api/v1/friends/lists', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name, description })
            });
            const data = await res.json();
            if (data.success) {
                showToast('কাস্টম ফ্রেন্ড লিস্ট সফলভাবে তৈরি হয়েছে!', 'success');
                closeCreateListModal();
                setTimeout(() => window.location.href = `/friends?tab=custom_lists&list_id=${data.data.id}`, 300);
            } else {
                showToast(data.message || 'লিস্ট তৈরি করা যায়নি।', 'error');
            }
        } catch (err) {
            showToast('সার্ভার এরর।', 'error');
        }
    }

    async function deleteCustomList(id) {
        if (!confirm('আপনি কি এই লিস্টটি মুছে ফেলতে নিশ্চিত?')) return;
        try {
            const res = await fetch(`/api/v1/friends/lists/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                showToast('লিস্ট মুছে ফেলা হয়েছে।', 'info');
                setTimeout(() => window.location.href = '/friends', 300);
            }
        } catch (e) {
            showToast('লিস্ট মুছতে ব্যর্থ হয়েছে।', 'error');
        }
    }

    async function removeFromList(listId, friendId) {
        try {
            const res = await fetch(`/api/v1/friends/lists/${listId}/members/${friendId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                showToast('লিস্ট থেকে সরানো হয়েছে।', 'info');
                const card = document.getElementById(`list-member-${friendId}`);
                if (card) card.remove();
            }
        } catch (e) {
            showToast('ত্রুটি ঘটেছে।', 'error');
        }
    }

    // Bulk selection handlers
    function toggleSelectAllRequests(checked) {
        document.querySelectorAll('.request-checkbox').forEach(cb => cb.checked = checked);
        updateSelectedCount();
    }

    function getSelectedRequestIds() {
        return Array.from(document.querySelectorAll('.request-checkbox:checked')).map(cb => parseInt(cb.value));
    }

    function updateSelectedCount() {
        const ids = getSelectedRequestIds();
        const bar = document.getElementById('requestsBulkBar');
        const countText = document.getElementById('selectedCountText');
        if (countText) countText.innerText = ids.length;
        if (bar) {
            if (ids.length > 0) bar.classList.add('active');
            else bar.classList.remove('active');
        }
    }

    async function bulkAcceptSelected() {
        const ids = getSelectedRequestIds();
        if (ids.length === 0) return;

        try {
            const res = await fetch('/api/v1/friends/requests/bulk-accept', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids })
            });
            const data = await res.json();
            if (data.success) {
                showToast(`${data.data.success.length}টি অনুরোধ গ্রহণ করা হয়েছে!`, 'success');
                data.data.success.forEach(id => {
                    const c = document.getElementById(`request-card-${id}`);
                    if (c) c.remove();
                });
                updateSelectedCount();
                setTimeout(() => window.location.reload(), 600);
            }
        } catch (e) {
            showToast('বাল্ক একশনে সমস্যা হয়েছে।', 'error');
        }
    }

    async function bulkDeclineSelected() {
        const ids = getSelectedRequestIds();
        if (ids.length === 0) return;

        try {
            const res = await fetch('/api/v1/friends/requests/bulk-decline', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids })
            });
            const data = await res.json();
            if (data.success) {
                showToast('নির্বাচিত অনুরোধ প্রত্যাখ্যান করা হয়েছে।', 'info');
                data.data.success.forEach(id => {
                    const c = document.getElementById(`request-card-${id}`);
                    if (c) c.remove();
                });
                updateSelectedCount();
            }
        } catch (e) {
            showToast('বাল্ক একশনে সমস্যা হয়েছে।', 'error');
        }
    }

    async function bulkBlockSelected() {
        const ids = getSelectedRequestIds();
        if (ids.length === 0 || !confirm('নির্বাচিত সবাইকে ব্লক করতে চান?')) return;

        try {
            const res = await fetch('/api/v1/friends/requests/bulk-block', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids })
            });
            const data = await res.json();
            if (data.success) {
                showToast('নির্বাচিত ইউজারদের ব্লক করা হয়েছে।', 'success');
                data.data.success.forEach(id => {
                    const c = document.getElementById(`request-card-${id}`);
                    if (c) c.remove();
                });
                updateSelectedCount();
            }
        } catch (e) {
            showToast('ত্রুটি ঘটেছে।', 'error');
        }
    }

    function decrementPendingCount() {
        const badge = document.getElementById('sidebarPendingBadge');
        if (badge) {
            let count = parseInt(badge.innerText) || 0;
            count = Math.max(0, count - 1);
            badge.innerText = count;
            if (count === 0) badge.className = 'friends-nav-count';
        }
        const navBadge = document.getElementById('navbarPendingFriendsBadge');
        if (navBadge) {
            let count = parseInt(navBadge.innerText) || 0;
            count = Math.max(0, count - 1);
            navBadge.innerText = count;
            if (count === 0) navBadge.style.display = 'none';
        }
    }

    // REAL-TIME WEBSOCKET LISTENER VIA ECHO
    document.addEventListener('DOMContentLoaded', () => {
        if (window.Echo) {
            window.Echo.private(`user.${currentUserId}`)
                .listen('.friend.request.received', (data) => {
                    showToast(`${data.sender.name} আপনাকে একটি ফ্রেন্ড রিকোয়েস্ট পাঠিয়েছেন!`, 'info');
                    const navBadge = document.getElementById('navbarPendingFriendsBadge');
                    if (navBadge) {
                        let count = (parseInt(navBadge.innerText) || 0) + 1;
                        navBadge.innerText = count;
                        navBadge.style.display = 'flex';
                    }
                    const sBadge = document.getElementById('sidebarPendingBadge');
                    if (sBadge) {
                        let count = (parseInt(sBadge.innerText) || 0) + 1;
                        sBadge.innerText = count;
                        sBadge.className = 'friends-nav-badge';
                    }
                })
                .listen('.friend.request.accepted', (data) => {
                    showToast(`${data.friend.name} আপনার ফ্রেন্ড রিকোয়েস্ট গ্রহণ করেছেন!`, 'success');
                    const totalEl = document.getElementById('sidebarFriendsTotal');
                    if (totalEl) {
                        let count = parseInt(totalEl.innerText) || 0;
                        totalEl.innerText = `${count + 1} জন বন্ধু`;
                    }
                })
                .listen('.friend.removed', (data) => {
                    const card = document.getElementById(`friend-card-${data.friend_id || data.user_id}`);
                    if (card) card.remove();
                })
                .listen('.friend.counters.updated', (counters) => {
                    const navBadge = document.getElementById('navbarPendingFriendsBadge');
                    if (navBadge) {
                        if (counters.pending_requests > 0) {
                            navBadge.innerText = counters.pending_requests;
                            navBadge.style.display = 'flex';
                        } else {
                            navBadge.style.display = 'none';
                        }
                    }
                    const sBadge = document.getElementById('sidebarPendingBadge');
                    if (sBadge) {
                        sBadge.innerText = counters.pending_requests;
                        sBadge.className = counters.pending_requests > 0 ? 'friends-nav-badge' : 'friends-nav-count';
                    }
                    const totalEl = document.getElementById('sidebarFriendsTotal');
                    if (totalEl) totalEl.innerText = `${counters.total_friends} জন বন্ধু`;
                });
        }
    });
</script>
@endsection
