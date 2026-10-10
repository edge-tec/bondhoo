<!-- ==============================================================================
     BONDOO / JUGAJUG — COMPLETE MOBILE APP-LIKE NAVIGATION & DRAWER
     Fixed Bottom Navigation Bar + Create Action Sheet + Logically Grouped Drawer
     ============================================================================== -->

@php
    $navUser = auth('web')->user();
    if (!$navUser && (request()->hasCookie('bondhoo_token') || request()->hasCookie('jugajug_token'))) {
        $rawTok = (string) (request()->cookie('bondhoo_token') ?: request()->cookie('jugajug_token'));
        $pToken = \Laravel\Sanctum\PersonalAccessToken::findToken($rawTok);
        if ($pToken && $pToken->tokenable instanceof \App\Models\User) {
            $navUser = $pToken->tokenable;
        }
    }
    $navUserName = $navUser ? $navUser->name : 'ব্যবহারকারী';
    $navUserUsername = $navUser ? ($navUser->username ?? '') : '';
    $navUserInitial = $navUser ? mb_substr($navUser->name, 0, 1) : 'ব';
    $navUserAvatar = $navUser?->profile?->avatar_url;
    if ($navUserAvatar && str_starts_with($navUserAvatar, '/storage/') && !file_exists(public_path($navUserAvatar))) {
        $navUserAvatar = '/images/default-avatar.svg';
    }
    $navUserAvatar = $navUserAvatar ?: '/images/default-avatar.svg';

    $isHomeActive = request()->is('/') || request()->is('dashboard') || request()->is('feed');
    $isFriendsActive = request()->is('friends*');
    $isWatchActive = request()->is('watch*');
    $isMarketplaceActive = request()->is('marketplace*');
    $isGroupsActive = request()->is('groups*');
    $isPagesActive = request()->is('pages*');
    $isMessagesActive = request()->is('messages*');
    $isNotifsActive = request()->is('notifications*');
@endphp

<!-- 1. FIXED MOBILE BOTTOM NAVIGATION BAR -->
<nav class="mobile-bottom-nav" id="bondhooMobileBottomNav" aria-label="মোবাইল নেভিগেশন">
    <!-- Home / Feed -->
    <button type="button" class="mobile-nav-btn {{ $isHomeActive ? 'active' : '' }}" id="mobileNavHomeBtn" onclick="handleMobileNavHome(event)" aria-label="হোম ফিড" title="হোম ফিড">
        <span class="mobile-nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9.5L12 3l9 6.5V20a1.5 1.5 0 0 1-1.5 1.5h-5A1.5 1.5 0 0 1 13 20v-5h-2v5a1.5 1.5 0 0 1-1.5 1.5h-5A1.5 1.5 0 0 1 3 20V9.5z"/>
            </svg>
        </span>
    </button>

    <!-- Search -->
    <button type="button" class="mobile-nav-btn {{ request()->is('search*') ? 'active' : '' }}" id="mobileNavSearchBtn" onclick="handleMobileNavSearch(event)" aria-label="অনুসন্ধান" title="অনুসন্ধান">
        <span class="mobile-nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </span>
    </button>

    <!-- Create (Center Elevated Action Button) -->
    <button type="button" class="mobile-nav-create-btn" id="mobileNavCreateBtn" onclick="handleMobileCenterCreate(event)" aria-label="নতুন তৈরি করুন" title="নতুন তৈরি করুন">
        <div class="mobile-nav-create-bubble">
            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
        </div>
    </button>

    <!-- Notifications -->
    <button type="button" class="mobile-nav-btn {{ $isNotifsActive ? 'active' : '' }}" id="mobileNavNotifBtn" onclick="handleMobileNavNotifications(event)" aria-label="বিজ্ঞপ্তি" title="বিজ্ঞপ্তি">
        <span class="mobile-nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="mobile-nav-badge" id="mobileNavNotifBadge" style="display: none;">0</span>
        </span>
    </button>

    <!-- Messenger -->
    <button type="button" class="mobile-nav-btn {{ $isMessagesActive ? 'active' : '' }}" id="mobileNavMessengerBtn" onclick="handleMobileNavMessenger(event)" aria-label="মেসেঞ্জার" title="মেসেঞ্জার">
        <span class="mobile-nav-icon">
            <svg width="24" height="24" viewBox="0 0 28 28" fill="currentColor">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M14 2C7.373 2 2 7.155 2 13.518c0 3.626 1.745 6.862 4.475 8.974V26l3.37-1.85c1.28.355 2.646.549 4.155.549 6.627 0 12-5.155 12-11.518C26 7.155 20.627 2 14 2zm1.203 15.534l-3.08-3.284-6.012 3.284 6.613-7.02 3.155 3.284 5.937-3.284-6.613 7.02z"/>
            </svg>
            <span class="mobile-nav-badge" id="mobileNavMessengerBadge" style="display: none;">0</span>
        </span>
    </button>
</nav>

<!-- 2. MOBILE CREATE ACTION SHEET (#mobileCreateActionSheet) -->
<div class="mobile-sheet-overlay" id="mobileCreateActionSheet" onclick="handleMobileSheetBackdrop(event)" role="dialog" aria-modal="true" aria-labelledby="mobileCreateSheetTitle">
    <div class="mobile-sheet-card" onclick="event.stopPropagation()">
        <div class="mobile-sheet-handle"></div>
        <h3 class="mobile-sheet-title" id="mobileCreateSheetTitle">নতুন কিছু শেয়ার করুন ✨</h3>
        
        <div class="mobile-create-grid">
            <!-- 1. Create Post -->
            <div class="mobile-create-item" onclick="handleMobileCreatePost()">
                <div class="mobile-create-icon" style="background: linear-gradient(135deg, #1877f2 0%, #0084ff 100%);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </div>
                <div class="mobile-create-info">
                    <h4>নতুন পোস্ট</h4>
                    <p>লেখা, ছবি বা ভিডিও</p>
                </div>
            </div>

            <!-- 2. Create Story -->
            <div class="mobile-create-item" onclick="handleMobileCreateStory()">
                <div class="mobile-create-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </div>
                <div class="mobile-create-info">
                    <h4>স্টোরি</h4>
                    <p>২৪ ঘণ্টার জন্য শেয়ার</p>
                </div>
            </div>

            <!-- 3. Upload Reel -->
            <div class="mobile-create-item" onclick="handleMobileCreateReel()">
                <div class="mobile-create-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="18" rx="3" ry="3"/><line x1="7" y1="3" x2="7" y2="21"/><line x1="17" y1="3" x2="17" y2="21"/><line x1="2" y1="9" x2="22" y2="9"/><line x1="2" y1="15" x2="22" y2="15"/><polygon points="10.5 10.5 14.5 12 10.5 13.5 10.5 10.5" fill="#ffffff" stroke="none"/></svg>
                </div>
                <div class="mobile-create-info">
                    <h4>রিল ভিডিও</h4>
                    <p>শর্ট ভিডিও ও মিউজিক</p>
                </div>
            </div>

            <!-- 4. Go Live -->
            <div class="mobile-create-item" onclick="handleMobileGoLive()">
                <div class="mobile-create-icon" style="background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3" fill="#ffffff" stroke="none"/><path d="M16.95 7.05a7 7 0 0 1 0 9.9"/><path d="M7.05 16.95a7 7 0 0 1 0-9.9"/><path d="M20.49 3.51a12 12 0 0 1 0 16.98"/><path d="M3.51 20.49a12 12 0 0 1 0-16.98"/></svg>
                </div>
                <div class="mobile-create-info">
                    <h4>লাইভ স্টুডিও</h4>
                    <p>সরাসরি ভিডিও স্ট্রিমিং</p>
                </div>
            </div>
        </div>

        <button type="button" class="mobile-sheet-close-btn" onclick="closeMobileCreateSheet()">বাতিল করুন</button>
    </div>
</div>

<!-- 3. MOBILE APP MENU DRAWER (#mobileMenuDrawer) -->
<div class="mobile-drawer-overlay" id="mobileMenuDrawer" onclick="handleMobileDrawerBackdrop(event)" role="dialog" aria-modal="true" aria-labelledby="mobileDrawerTitle">
    <div class="mobile-drawer-panel" onclick="event.stopPropagation()">
        <!-- Drawer Header -->
        <div class="mobile-drawer-header">
            <h3 id="mobileDrawerTitle">
                <span class="mobile-drawer-title-icon-badge" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3.5" y1="6" x2="20.5" y2="6"></line>
                        <line x1="3.5" y1="12" x2="15.5" y2="12"></line>
                        <circle cx="19.5" cy="12" r="1.3" fill="currentColor"></circle>
                        <line x1="3.5" y1="18" x2="20.5" y2="18"></line>
                    </svg>
                </span>
                <span>মেনু ও এক্সপ্লোর</span>
            </h3>
            <button type="button" class="mobile-drawer-close-btn" onclick="closeMobileMenuDrawer()" aria-label="বন্ধ করুন">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Drawer Body -->
        <div class="mobile-drawer-body is-grid" id="mobileDrawerBody">
            <!-- Search Filter Bar -->
            <div class="mobile-drawer-search-wrap">
                <span class="mobile-drawer-search-icon">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" class="mobile-drawer-search-input" id="mobileDrawerSearchInput" placeholder="মেনু ও ফিচার অনুসন্ধান করুন..." oninput="filterMobileDrawerItems(this.value)" autocomplete="off">
                <button type="button" class="mobile-drawer-search-clear" id="mobileDrawerSearchClear" onclick="clearMobileDrawerSearch()" style="display: none;" title="মুছে ফেলুন">✕</button>
            </div>

            <!-- View Switcher Toolbar (Grid vs List) -->
            <div class="mobile-drawer-toolbar">
                <div class="mobile-drawer-view-toggle">
                    <button type="button" class="mobile-drawer-view-btn active" id="mobileDrawerViewGridBtn" onclick="setMobileDrawerView('grid')" title="গ্রিড ভিউ">
                        <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
                        <span>গ্রিড ভিউ</span>
                    </button>
                    <button type="button" class="mobile-drawer-view-btn" id="mobileDrawerViewListBtn" onclick="setMobileDrawerView('list')" title="লিস্ট ভিউ">
                        <svg viewBox="0 0 24 24" fill="none"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        <span>লিস্ট ভিউ</span>
                    </button>
                </div>
            </div>

            <!-- Quick Creation Shortcuts Row -->
            <div class="mobile-drawer-quick-row">
                <button type="button" class="mobile-drawer-quick-chip" onclick="handleMobileCreatePost()" title="নতুন পোস্ট তৈরি করুন">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#1877f2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <span>+ পোস্ট</span>
                </button>
                <button type="button" class="mobile-drawer-quick-chip" onclick="handleMobileCreateStory()" title="নতুন স্টোরি আপলোড করুন">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#8b5cf6"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <span>+ স্টোরি</span>
                </button>
                <button type="button" class="mobile-drawer-quick-chip" onclick="handleMobileCreateReel()" title="শর্ট রিলস ভিডিও আপলোড">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b"><rect x="2" y="3" width="20" height="18" rx="3" ry="3"/><polygon points="10 8 16 11 10 14 10 8" fill="#f59e0b" stroke="none"/></svg>
                    <span>+ রিলস</span>
                </button>
                <button type="button" class="mobile-drawer-quick-chip" onclick="handleMobileGoLive()" title="সরাসরি লাইভ শুরু করুন">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444"><circle cx="12" cy="12" r="3" fill="#ef4444"/><path d="M16.95 7.05a7 7 0 0 1 0 9.9"/><path d="M7.05 16.95a7 7 0 0 1 0-9.9"/></svg>
                    <span>+ লাইভ</span>
                </button>
            </div>

            <!-- User Profile Card -->
            <div class="mobile-drawer-user-card" onclick="handleMobileNavProfile()" data-search="প্রোফাইল user profile bio timeline">
                <div class="mobile-drawer-user-avatar" id="mobileDrawerUserAvatar">
                    @if($navUserAvatar && $navUserAvatar !== '/images/default-avatar.svg')
                        <img src="{{ $navUserAvatar }}" alt="{{ $navUserName }}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                    @else
                        {{ $navUserInitial }}
                    @endif
                </div>
                <div class="mobile-drawer-user-info" style="flex: 1; min-width: 0;">
                    <h4 id="mobileDrawerUserName">{{ $navUserName }}</h4>
                    <p id="mobileDrawerUserSub">প্রোফাইল দেখুন @if($navUserUsername)({{ '@'.$navUserUsername }})@endif</p>
                </div>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" style="color: var(--fb-text-secondary); opacity: 0.5;"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </div>

            <!-- SECTION 1: ACCOUNT (অ্যাকাউন্ট ও প্রোফাইল) -->
            <div class="mobile-drawer-section-group" data-section="account">
                <div class="mobile-drawer-section-title">অ্যাকাউন্ট ও প্রোফাইল</div>
                <div class="mobile-drawer-items-wrap">
                    <div class="mobile-drawer-item" onclick="handleMobileNavProfile()" data-search="আমার প্রোফাইল টাইমলাইন বায়ো ফটো profile timeline account">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 4px 10px rgba(37, 99, 235, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">আমার প্রোফাইল</span>
                            <span class="mobile-drawer-item-subtitle">টাইমলাইন, বায়ো ও ফটো</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                    <div class="mobile-drawer-item" onclick="handleMobileNavFriends()" data-search="বন্ধুরা ফ্রেন্ড রিকোয়েস্ট বন্ধু তালিকা অনুরোধ friends request">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); box-shadow: 0 4px 10px rgba(2, 132, 199, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">বন্ধুরা ও রিকোয়েস্ট</span>
                            <span class="mobile-drawer-item-subtitle">বন্ধু তালিকা ও নতুন অনুরোধ</span>
                        </div>
                        <span class="mobile-drawer-badge" id="mobileDrawerFriendsBadge" style="display: none;">0</span>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                    <a href="/devices" class="mobile-drawer-item" onclick="closeMobileMenuDrawer()" data-search="ডিভাইস সক্রিয় সেশন ব্রাউজার লগইন devices session login">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); box-shadow: 0 4px 10px rgba(139, 92, 246, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="5" y="2" width="14" height="20" rx="3" ry="3"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">ডিভাইস ও সক্রিয় সেশন</span>
                            <span class="mobile-drawer-item-subtitle">লগইনকৃত ব্রাউজার ও ডিভাইস</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                </div>
            </div>

            <!-- SECTION 2: SOCIAL PLATFORMS (কমিউনিটি ও সোশ্যাল) -->
            <div class="mobile-drawer-section-group" data-section="community">
                <div class="mobile-drawer-section-title">কমিউনিটি ও সোশ্যাল</div>
                <div class="mobile-drawer-items-wrap">
                    <div class="mobile-drawer-item" onclick="handleMobileNavGroups()" data-search="গ্রুপ কমিউনিটি আলোচনা groups community">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); box-shadow: 0 4px 10px rgba(13, 148, 136, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M7 21v-2a4 4 0 0 1 3-3.87"/>
                                <circle cx="12" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M2 21v-2a4 4 0 0 1 3-3.87"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">গ্রুপ ও কমিউনিটি</span>
                            <span class="mobile-drawer-item-subtitle">পছন্দের গ্রুপ ও আলোচনা</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                    <a href="/pages" class="mobile-drawer-item" onclick="closeMobileMenuDrawer()" data-search="পেইজ পেইজসমূহ পেজ ব্র্যান্ড ব্যবসা পাবলিক পেজ pages business brand">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); box-shadow: 0 4px 10px rgba(249, 115, 22, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                <line x1="4" y1="22" x2="4" y2="15"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">পেইজসমূহ</span>
                            <span class="mobile-drawer-item-subtitle">ব্র্যান্ড, ব্যবসা ও পেজ</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                    <div class="mobile-drawer-item" onclick="handleMobileNavMarketplace()" data-search="মার্কেটপ্লেস কেনা-বেচা বাজার শপ স্থানীয় ডিল marketplace shop buy sell">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 10px rgba(16, 185, 129, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <path d="M16 10a4 4 0 0 1-8 0"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">মার্কেটপ্লেস</span>
                            <span class="mobile-drawer-item-subtitle">কেনা-বেচা ও স্থানীয় ডিল</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                    <div class="mobile-drawer-item" onclick="handleMobileNavEvents()" data-search="ইভেন্টস অনুষ্ঠান আয়োজন আমন্ত্রণ events calendar schedule">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #ec4899 0%, #be185d 100%); box-shadow: 0 4px 10px rgba(236, 72, 153, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="3" ry="3"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                                <circle cx="12" cy="15" r="1.5" fill="#ffffff"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">ইভেন্টস ও অনুষ্ঠান</span>
                            <span class="mobile-drawer-item-subtitle">আসন্ন আয়োজন ও আমন্ত্রণ</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: COMMUNICATION (মেসেজিং ও কল) -->
            <div class="mobile-drawer-section-group" data-section="communication">
                <div class="mobile-drawer-section-title">মেসেজিং ও কল</div>
                <div class="mobile-drawer-items-wrap">
                    <div class="mobile-drawer-item" onclick="handleMobileNavMessenger()" data-search="মেসেঞ্জার চ্যাট মেসেজ কল বার্তা messenger chat messages calls">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #00c6ff 0%, #0078ff 50%, #9030ff 100%); box-shadow: 0 4px 10px rgba(0, 120, 255, 0.32);">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path fill="#ffffff" d="M12 2C6.48 2 2 6.15 2 11.26c0 2.91 1.45 5.52 3.73 7.21V22l3.36-1.84c.93.26 1.9.4 2.91.4 5.52 0 10-4.15 10-9.26C22 6.15 17.52 2 12 2zm1.05 12.39l-2.61-2.78-5.1 2.78 5.6-5.95 2.68 2.78 5.03-2.78-5.6 5.95z"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">মেসেঞ্জার ও চ্যাট</span>
                            <span class="mobile-drawer-item-subtitle">তাৎক্ষণিক বার্তা ও কল</span>
                        </div>
                        <span class="mobile-drawer-badge" id="mobileDrawerMsgBadge" style="display: none;">0</span>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                    <div class="mobile-drawer-item" onclick="handleMobileNavNotifications()" data-search="নোটিফিকেশন সেন্টার বিজ্ঞপ্তি নোটিশ আপডেট notifications alerts">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%); box-shadow: 0 4px 10px rgba(244, 63, 94, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                <circle cx="18" cy="4.5" r="2.5" fill="#fef08a" stroke="#e11d48" stroke-width="1.2"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">নোটিফিকেশন সেন্টার</span>
                            <span class="mobile-drawer-item-subtitle">সকল আপডেট ও বিজ্ঞপ্তি</span>
                        </div>
                        <span class="mobile-drawer-badge" id="mobileDrawerNotifBadge" style="display: none;">0</span>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: CONTENT & MEDIA (ভিডিও ও বিনোদন) -->
            <div class="mobile-drawer-section-group" data-section="media">
                <div class="mobile-drawer-section-title">ভিডিও ও বিনোদন</div>
                <div class="mobile-drawer-items-wrap">
                    <div class="mobile-drawer-item" onclick="handleMobileNavWatch()" data-search="ওয়াচ ভিডিও ওয়াচ ও ভিডিও ট্রেন্ডিং watch video stream">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="14" rx="3" ry="3"/>
                                <polygon points="10 8 16 11 10 14 10 8" fill="#ffffff" stroke="none"/>
                                <line x1="8" y1="21" x2="16" y2="21"/>
                                <line x1="12" y1="18" x2="12" y2="21"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">ওয়াচ ও ভিডিও</span>
                            <span class="mobile-drawer-item-subtitle">ট্রেন্ডিং ভিডিও ও ক্লিপ</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                    <div class="mobile-drawer-item" onclick="handleMobileNavReels()" data-search="রিলস শর্ট ভিডিও রিলস ভিডিও মিউজিক reels short video tiktok">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 4px 10px rgba(245, 158, 11, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="3" width="20" height="18" rx="3" ry="3"/>
                                <line x1="7" y1="3" x2="7" y2="21"/>
                                <line x1="17" y1="3" x2="17" y2="21"/>
                                <line x1="2" y1="9" x2="22" y2="9"/>
                                <line x1="2" y1="15" x2="22" y2="15"/>
                                <polygon points="10.5 10.5 14.5 12 10.5 13.5 10.5 10.5" fill="#ffffff" stroke="none"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">রিলস শর্ট ভিডিও</span>
                            <span class="mobile-drawer-item-subtitle">ভার্টিক্যাল ছোট ভিডিও</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                    <a href="/live/studio" class="mobile-drawer-item" onclick="closeMobileMenuDrawer()" data-search="ক্রিয়েটর লাইভ স্টুডিও সরাসরি সম্প্রচার ব্রডকাস্ট live broadcast studio stream">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #e11d48 0%, #9f1239 100%); box-shadow: 0 4px 10px rgba(225, 29, 72, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3" fill="#ffffff" stroke="none"/>
                                <path d="M16.95 7.05a7 7 0 0 1 0 9.9"/>
                                <path d="M7.05 16.95a7 7 0 0 1 0-9.9"/>
                                <path d="M20.49 3.51a12 12 0 0 1 0 16.98"/>
                                <path d="M3.51 20.49a12 12 0 0 1 0-16.98"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">লাইভ স্টুডিও</span>
                            <span class="mobile-drawer-item-subtitle">সরাসরি ব্রডকাস্ট শুরু করুন</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                </div>
            </div>

            <!-- SECTION 5: SETTINGS & CONTROLS (নিরাপত্তা ও সেটিংস) -->
            <div class="mobile-drawer-section-group" data-section="settings">
                <div class="mobile-drawer-section-title">নিরাপত্তা ও সেটিংস</div>
                <div class="mobile-drawer-items-wrap">
                    <a href="/settings/two-factor" class="mobile-drawer-item" onclick="closeMobileMenuDrawer()" data-search="টু-ফ্যাক্টর নিরাপত্তা 2FA সুরক্ষা পাসওয়ার্ড two factor authentication security">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); box-shadow: 0 4px 10px rgba(79, 70, 229, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title">টু-ফ্যাক্টর নিরাপত্তা</span>
                            <span class="mobile-drawer-item-subtitle">অ্যাকাউন্ট দ্বিস্তর সুরক্ষা</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                    <div class="mobile-drawer-item" onclick="handleMobileToggleTheme()" data-search="ডার্ক মোড পরিবর্তন থিম লাইট ডার্ক dark mode light theme">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #334155 0%, #0f172a 100%); box-shadow: 0 4px 10px rgba(15, 23, 42, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" fill="#fef08a" stroke="#fef08a"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title" id="mobileThemeToggleLabel">ডার্ক মোড পরিবর্তন</span>
                            <span class="mobile-drawer-item-subtitle">লাইট ও ডার্ক থিম ইন্টারফেস</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                    <div class="mobile-drawer-item" onclick="handleMobileLogout()" data-search="লগআউট সাইন আউট logout sign out">
                        <span class="mobile-drawer-icon" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.28);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                        </span>
                        <div class="mobile-drawer-item-content">
                            <span class="mobile-drawer-item-title" style="color: #ef4444;">লগআউট করুন</span>
                            <span class="mobile-drawer-item-subtitle">সকল সেশন নিরাপদে শেষ করুন</span>
                        </div>
                        <svg class="mobile-drawer-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.3"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>
            </div>

<!-- 3. MOBILE SEARCH MODAL OVERLAY (#mobileSearchModal) -->
<div class="mobile-search-modal-overlay" id="mobileSearchModal" style="display: none;">
    <div class="mobile-search-modal-backdrop" onclick="closeMobileSearchModal()"></div>
    <div class="mobile-search-modal-container">
        <div class="mobile-search-modal-header">
            <div class="mobile-search-input-wrap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="mobileSearchModalInput" placeholder="Bondhoo-তে অনুসন্ধান করুন..." autocomplete="off">
                <button type="button" id="mobileSearchClearBtn" style="display: none;" onclick="clearMobileSearchInput()">✕</button>
            </div>
            <button type="button" class="mobile-search-cancel-btn" onclick="closeMobileSearchModal()">বাতিল</button>
        </div>
        <div class="mobile-search-results-area" id="mobileSearchResultsArea">
            <div class="mobile-search-hint">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <p>বন্ধু, প্রোফাইল বা পোস্ট খুঁজে পেতে সার্চ করুন</p>
            </div>
        </div>
    </div>
</div>

<!-- 4. INTERACTIVE JAVASCRIPT LOGIC -->
<script>
    /* Open & Close Mobile Create Action Sheet */
    function openMobileCreateSheet() {
        const sheet = document.getElementById('mobileCreateActionSheet');
        if (sheet) {
            sheet.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeMobileCreateSheet() {
        const sheet = document.getElementById('mobileCreateActionSheet');
        if (sheet) {
            sheet.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    function handleMobileSheetBackdrop(e) {
        if (e.target.id === 'mobileCreateActionSheet') {
            closeMobileCreateSheet();
        }
    }

    /* Switch Drawer View: 'grid' | 'list' */
    function setMobileDrawerView(mode) {
        const bodyEl = document.getElementById('mobileDrawerBody');
        const gridBtn = document.getElementById('mobileDrawerViewGridBtn');
        const listBtn = document.getElementById('mobileDrawerViewListBtn');
        if (!bodyEl) return;

        if (mode === 'list') {
            bodyEl.classList.remove('is-grid');
            bodyEl.classList.add('is-list');
            if (gridBtn) gridBtn.classList.remove('active');
            if (listBtn) listBtn.classList.add('active');
        } else {
            bodyEl.classList.remove('is-list');
            bodyEl.classList.add('is-grid');
            if (gridBtn) gridBtn.classList.add('active');
            if (listBtn) listBtn.classList.remove('active');
            mode = 'grid';
        }
        try {
            localStorage.setItem('bondhoo_drawer_view', mode);
        } catch(e) {}
    }

    function initMobileDrawerView() {
        let saved = 'grid';
        try {
            saved = localStorage.getItem('bondhoo_drawer_view') || 'grid';
        } catch(e) {}
        setMobileDrawerView(saved);
    }

    /* Filter Drawer Items */
    function filterMobileDrawerItems(val) {
        const query = (val || '').toLowerCase().trim();
        const clearBtn = document.getElementById('mobileDrawerSearchClear');
        if (clearBtn) {
            clearBtn.style.display = query ? 'flex' : 'none';
        }

        const sections = document.querySelectorAll('.mobile-drawer-section-group');
        const emptyState = document.getElementById('mobileDrawerEmptySearch');
        let totalVisible = 0;

        sections.forEach(sec => {
            const items = sec.querySelectorAll('.mobile-drawer-item');
            let secVisible = 0;
            items.forEach(item => {
                const searchData = (item.getAttribute('data-search') || '') + ' ' + (item.innerText || '');
                const matches = !query || searchData.toLowerCase().includes(query);
                item.style.display = matches ? '' : 'none';
                if (matches) {
                    secVisible++;
                    totalVisible++;
                }
            });
            sec.style.display = (secVisible > 0 || !query) ? '' : 'none';
        });

        // Also check user card
        const userCard = document.querySelector('.mobile-drawer-user-card');
        if (userCard) {
            const ucData = (userCard.getAttribute('data-search') || '') + ' ' + (userCard.innerText || '');
            userCard.style.display = (!query || ucData.toLowerCase().includes(query)) ? '' : 'none';
        }

        if (emptyState) {
            emptyState.style.display = (totalVisible === 0 && query) ? 'block' : 'none';
        }
    }

    function clearMobileDrawerSearch() {
        const input = document.getElementById('mobileDrawerSearchInput');
        if (input) {
            input.value = '';
            input.focus();
        }
        filterMobileDrawerItems('');
    }

    /* Open & Close Mobile Menu Drawer */
    function openMobileMenuDrawer() {
        const drawer = document.getElementById('mobileMenuDrawer');
        if (drawer) {
            drawer.classList.add('active');
            document.body.style.overflow = 'hidden';
            initMobileDrawerView();
            syncMobileDrawerUserInfo();
        }
    }

    function closeMobileMenuDrawer() {
        const drawer = document.getElementById('mobileMenuDrawer');
        if (drawer) {
            drawer.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    function handleMobileDrawerBackdrop(e) {
        if (e.target.id === 'mobileMenuDrawer') {
            closeMobileMenuDrawer();
        }
    }

    /* Keyboard Escape Listener */
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileCreateSheet();
            closeMobileMenuDrawer();
        }
    });

    /* Sync User Info in Drawer */
    function syncMobileDrawerUserInfo() {
        const u = window.currentUser || (window.auth && window.auth.user);
        if (!u) return;

        const nameEl = document.getElementById('mobileDrawerUserName');
        const subEl = document.getElementById('mobileDrawerUserSub');
        const avEl = document.getElementById('mobileDrawerUserAvatar');

        if (nameEl && u.name) nameEl.innerText = u.name;
        if (subEl) subEl.innerText = 'প্রোফাইল দেখুন' + (u.username ? ` (@${u.username})` : '');
        if (avEl) {
            const avatarUrl = u.profile?.avatar_url || u.avatar_url;
            if (avatarUrl && avatarUrl !== '/images/default-avatar.svg') {
                avEl.innerHTML = `<img src="${avatarUrl}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">`;
            } else if (u.name) {
                avEl.innerText = u.name.charAt(0);
            }
        }

        const bBadge = document.getElementById('mobileNavMenuAvatarBadge');
        if (bBadge) {
            const avatarUrl = u.profile?.avatar_url || u.avatar_url;
            const imgEl = bBadge.querySelector('.mobile-bottom-menu-avatar-img');
            const fbEl = bBadge.querySelector('.mobile-bottom-menu-avatar-fallback');
            if (avatarUrl && avatarUrl !== '/images/default-avatar.svg') {
                if (imgEl) { imgEl.src = avatarUrl; imgEl.style.display = 'block'; }
                if (fbEl) { fbEl.style.display = 'none'; }
            } else if (u.name) {
                if (imgEl) { imgEl.style.display = 'none'; }
                if (fbEl) { fbEl.innerText = u.name.charAt(0); fbEl.style.display = 'flex'; }
            }
        }
    }

    /* Navigation Action Handlers */
    function handleMobileNavHome(e) {
        closeMobileMenuDrawer();
        closeMobileCreateSheet();

        if (window.location.pathname === '/' || window.location.pathname === '/dashboard' || window.location.pathname === '/feed') {
            if (typeof switchMainTab === 'function') {
                switchMainTab('feed');
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
            updateBottomNavActive('mobileNavHomeBtn');
        } else {
            window.location.href = '/feed';
        }
    }

    function handleMobileNavSearch(e) {
        closeMobileMenuDrawer();
        closeMobileCreateSheet();
        openMobileSearchModal();
        updateBottomNavActive('mobileNavSearchBtn');
    }

    function handleMobileCenterCreate(e) {
        closeMobileMenuDrawer();
        if (typeof openCreatePostModal === 'function') {
            openCreatePostModal();
        } else {
            openMobileCreateSheet();
        }
    }

    function openMobileSearchModal() {
        const modal = document.getElementById('mobileSearchModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                const inp = document.getElementById('mobileSearchModalInput');
                if (inp) inp.focus();
            }, 120);
        }
    }

    function closeMobileSearchModal() {
        const modal = document.getElementById('mobileSearchModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    function clearMobileSearchInput() {
        const inp = document.getElementById('mobileSearchModalInput');
        const clearBtn = document.getElementById('mobileSearchClearBtn');
        const area = document.getElementById('mobileSearchResultsArea');
        if (inp) {
            inp.value = '';
            inp.focus();
        }
        if (clearBtn) clearBtn.style.display = 'none';
        if (area) {
            area.innerHTML = `
                <div class="mobile-search-hint">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <p>বন্ধু, প্রোফাইল বা পোস্ট খুঁজে পেতে সার্চ করুন</p>
                </div>`;
        }
    }

    let mobileSearchTimer = null;
    document.addEventListener('DOMContentLoaded', function() {
        const inp = document.getElementById('mobileSearchModalInput');
        const clearBtn = document.getElementById('mobileSearchClearBtn');
        if (inp) {
            inp.addEventListener('input', function(e) {
                clearTimeout(mobileSearchTimer);
                const q = e.target.value.trim();
                const area = document.getElementById('mobileSearchResultsArea');
                if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';
                if (!area) return;
                if (!q) {
                    clearMobileSearchInput();
                    return;
                }

                mobileSearchTimer = setTimeout(async () => {
                    try {
                        const tok = window.currentToken || (window.auth && window.auth.token) || '';
                        const headers = { 'Accept': 'application/json' };
                        if (tok) headers['Authorization'] = `Bearer ${tok}`;
                        const res = await fetch(`/api/v1/search?query=${encodeURIComponent(q)}`, { headers });
                        const json = await res.json();
                        const results = json.data || {};
                        const users = results.users || [];
                        const posts = results.posts || [];

                        let html = '';
                        if (users.length > 0) {
                            html += '<div class="mobile-search-section-title">ব্যবহারকারীগণ</div>';
                            users.forEach(u => {
                                const profileUrl = window.getUserProfileUrl ? window.getUserProfileUrl(u) : `/u/${encodeURIComponent(u.username || u.id)}`;
                                const avatar = u.profile?.avatar_url || u.avatar_url || '/images/default-avatar.svg';
                                html += `
                                    <div class="mobile-search-user-item" onclick="closeMobileSearchModal(); window.location.href='${profileUrl}';">
                                        <img src="${avatar}" class="mobile-search-user-avatar" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
                                        <div class="mobile-search-user-meta">
                                            <div class="mobile-search-user-name">${escapeHtml(u.name || u.username)}</div>
                                            <div class="mobile-search-user-handle">@${escapeHtml(u.username || '')}</div>
                                        </div>
                                    </div>`;
                            });
                        }

                        if (posts.length > 0) {
                            html += '<div class="mobile-search-section-title">পোস্টসমূহ</div>';
                            posts.forEach(p => {
                                html += `
                                    <div class="mobile-search-post-item" onclick="closeMobileSearchModal(); if(typeof showToast === 'function') showToast('পোস্ট ভিউ করা হচ্ছে');">
                                        <p>${escapeHtml((p.content || '').substring(0, 90))}...</p>
                                    </div>`;
                            });
                        }

                        if (users.length === 0 && posts.length === 0) {
                            html = '<div class="mobile-search-empty">কোনো ফলাফল পাওয়া যায়নি।</div>';
                        }

                        area.innerHTML = html;
                    } catch (err) {
                        console.error('Mobile search error:', err);
                    }
                }, 300);
            });
        }
    });

    function handleMobileNavFriends(e) {
        closeMobileMenuDrawer();
        closeMobileCreateSheet();

        if (typeof openFriendsModal === 'function') {
            openFriendsModal();
            updateBottomNavActive('mobileNavFriendsBtn');
        } else {
            window.location.href = '/friends';
        }
    }

    function handleMobileNavNotifications(e) {
        closeMobileMenuDrawer();
        closeMobileCreateSheet();

        if (typeof toggleNotificationsDropdown === 'function') {
            toggleNotificationsDropdown();
            updateBottomNavActive('mobileNavNotifBtn');
        } else if (typeof toggleHeaderNotificationDropdown === 'function') {
            toggleHeaderNotificationDropdown();
        } else {
            window.location.href = '/notifications';
        }
    }

    function handleMobileNavMessenger() {
        closeMobileMenuDrawer();
        closeMobileCreateSheet();
        window.location.href = '/messages';
    }

    function handleMobileNavProfile() {
        closeMobileMenuDrawer();
        closeMobileCreateSheet();

        const u = window.currentUser || (window.auth && window.auth.user);
        if (u && u.username) {
            window.location.href = `/profile/${u.username}`;
        } else if (typeof openUserProfileModal === 'function') {
            openUserProfileModal();
        } else {
            window.location.href = '/profile';
        }
    }

    function handleMobileNavGroups() {
        closeMobileMenuDrawer();
        if (typeof switchMainTab === 'function') {
            switchMainTab('groups');
        } else {
            window.location.href = '/groups';
        }
    }

    function handleMobileNavMarketplace() {
        closeMobileMenuDrawer();
        if (typeof switchMainTab === 'function') {
            switchMainTab('marketplace');
        } else {
            window.location.href = '/marketplace';
        }
    }

    function handleMobileNavEvents() {
        closeMobileMenuDrawer();
        if (typeof openEventsModal === 'function') {
            openEventsModal();
        }
    }

    function handleMobileNavWatch() {
        closeMobileMenuDrawer();
        if (typeof switchMainTab === 'function') {
            switchMainTab('watch');
        } else {
            window.location.href = '/watch';
        }
    }

    function handleMobileNavReels() {
        closeMobileMenuDrawer();
        if (typeof openReelsViewer === 'function') {
            openReelsViewer(0);
        } else if (typeof openReelsStudioModal === 'function') {
            openReelsStudioModal();
        } else {
            window.location.href = '/watch';
        }
    }

    function handleMobileCreatePost() {
        closeMobileCreateSheet();
        if (window.EnterprisePostComposer && typeof window.EnterprisePostComposer.open === 'function') {
            window.EnterprisePostComposer.open();
        } else if (typeof openCreatePostModal === 'function') {
            openCreatePostModal();
        } else {
            window.location.href = '/feed';
        }
    }

    function handleMobileCreateStory() {
        closeMobileCreateSheet();
        if (typeof openCreateStoryModal === 'function') {
            openCreateStoryModal();
        } else {
            window.location.href = '/feed';
        }
    }

    function handleMobileCreateReel() {
        closeMobileCreateSheet();
        if (typeof openReelsStudioModal === 'function') {
            openReelsStudioModal();
        } else {
            window.location.href = '/watch/studio';
        }
    }

    function handleMobileGoLive() {
        closeMobileCreateSheet();
        window.location.href = '/live/studio';
    }

    function handleMobileToggleTheme() {
        if (typeof toggleDarkMode === 'function') {
            toggleDarkMode();
        } else if (typeof toggleGlobalTheme === 'function') {
            toggleGlobalTheme();
        }
    }

    function handleMobileLogout() {
        closeMobileMenuDrawer();
        if (typeof logout === 'function') {
            logout();
        } else {
            const logoutForm = document.createElement('form');
            logoutForm.method = 'POST';
            logoutForm.action = '/logout';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrf) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = csrf;
                logoutForm.appendChild(input);
            }
            document.body.appendChild(logoutForm);
            logoutForm.submit();
        }
    }

    function updateBottomNavActive(btnId) {
        document.querySelectorAll('.mobile-nav-btn').forEach(b => b.classList.remove('active'));
        const target = document.getElementById(btnId);
        if (target) target.classList.add('active');
    }

    /* Realtime Badge Sync Helper */
    window.updateMobileNavBadges = function(counts) {
        if (!counts) return;
        if (counts.notifications !== undefined) {
            const b = document.getElementById('mobileNavNotifBadge');
            const d = document.getElementById('mobileDrawerNotifBadge');
            const show = counts.notifications > 0;
            const text = counts.notifications > 99 ? '99+' : counts.notifications.toString();
            if (b) { b.style.display = show ? 'flex' : 'none'; b.innerText = text; }
            if (d) { d.style.display = show ? 'inline-block' : 'none'; d.innerText = text; }
        }
        if (counts.messages !== undefined) {
            const m = document.getElementById('mobileDrawerMsgBadge');
            const mb = document.getElementById('mobileNavMessengerBadge');
            const show = counts.messages > 0;
            const text = counts.messages > 99 ? '99+' : counts.messages.toString();
            if (m) { m.style.display = show ? 'inline-block' : 'none'; m.innerText = text; }
            if (mb) { mb.style.display = show ? 'flex' : 'none'; mb.innerText = text; }
        }
        if (counts.friends !== undefined) {
            const fb = document.getElementById('mobileNavFriendsBadge');
            const fd = document.getElementById('mobileDrawerFriendsBadge');
            const show = counts.friends > 0;
            const text = counts.friends > 99 ? '99+' : counts.friends.toString();
            if (fb) { fb.style.display = show ? 'flex' : 'none'; fb.innerText = text; }
            if (fd) { fd.style.display = show ? 'inline-block' : 'none'; fd.innerText = text; }
        }

        const totalAlerts = (counts.notifications || 0) + (counts.messages || 0) + (counts.friends || 0);
        const headerDot = document.getElementById('mobileHeaderMenuDot');
        const bottomMenuBadge = document.getElementById('mobileNavMenuBadge');
        if (headerDot) {
            headerDot.style.display = totalAlerts > 0 ? 'block' : 'none';
        }
        if (bottomMenuBadge) {
            bottomMenuBadge.style.display = totalAlerts > 0 ? 'flex' : 'none';
            bottomMenuBadge.innerText = totalAlerts > 99 ? '99+' : totalAlerts.toString();
        }
    };
</script>
