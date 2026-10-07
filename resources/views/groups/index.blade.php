@extends('layouts.app')

@section('title', 'কমিউনিটি হাব — Bondhoo এন্টারপ্রাইজ')

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
        background: linear-gradient(135deg, #312e81 0%, #4f46e5 50%, #0284c7 100%);
        color: white;
        border-radius: 16px;
        padding: 38px 32px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        box-shadow: 0 10px 30px rgba(79, 70, 229, 0.25);
    }

    .community-hero h1 {
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }

    .community-hero p {
        font-size: 15px;
        opacity: 0.95;
        max-width: 620px;
        line-height: 1.6;
    }

    .category-pills-bar {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 12px;
        margin-bottom: 20px;
        scrollbar-width: thin;
    }

    .category-pill {
        padding: 8px 18px;
        border-radius: 30px;
        background: var(--jj-card);
        border: 1px solid var(--jj-border);
        color: var(--jj-text);
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .category-pill:hover, .category-pill.active {
        background: var(--jj-brand);
        color: white;
        border-color: var(--jj-brand);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }

    .search-filter-card {
        background: var(--jj-card);
        border: 1px solid var(--jj-border);
        border-radius: 14px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .groups-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
        gap: 20px;
    }

    .community-card {
        background: var(--jj-card);
        border-radius: 14px;
        border: 1px solid var(--jj-border);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        text-decoration: none;
        color: inherit;
        position: relative;
    }

    .community-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: rgba(79, 70, 229, 0.4);
    }

    .community-cover-box {
        height: 130px;
        background: linear-gradient(135deg, #4f46e5, #0ea5e9);
        position: relative;
        overflow: hidden;
    }

    .community-cover-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .community-avatar-overlay {
        position: absolute;
        bottom: 10px;
        left: 16px;
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: var(--jj-card);
        border: 2px solid white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 20px;
        color: var(--jj-brand);
        overflow: hidden;
    }

    .community-avatar-overlay img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .community-card-body {
        padding: 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .community-title-row {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 4px;
    }

    .community-name {
        font-size: 17px;
        font-weight: 700;
        color: var(--jj-text);
        line-height: 1.3;
    }

    .verified-badge {
        color: var(--jj-brand);
        font-size: 16px;
    }

    .community-meta {
        font-size: 13px;
        color: var(--jj-text-muted);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .community-desc {
        font-size: 13px;
        color: var(--jj-text-muted);
        line-height: 1.45;
        margin-bottom: 16px;
        flex: 1;
    }

    .section-title {
        font-size: 20px;
        font-weight: 800;
        margin-bottom: 16px;
        color: var(--jj-text);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Modal & Wizard */
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
        width: 620px;
        max-width: 100%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 40px rgba(0,0,0,0.25);
        overflow: hidden;
        animation: modalScale 0.25s ease-out;
    }

    @keyframes modalScale {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .wizard-steps-header {
        display: flex;
        border-bottom: 1px solid var(--jj-border);
        background: var(--jj-bg-subtle);
    }

    .wizard-step-item {
        flex: 1;
        padding: 12px 8px;
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        color: var(--jj-text-muted);
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
    }

    .wizard-step-item.active {
        color: var(--jj-brand);
        border-bottom-color: var(--jj-brand);
        background: var(--jj-card);
    }

    .wizard-content {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
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
        animation: slideIn 0.3s ease-out;
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

    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
</style>
@endsection

@section('content')
<div class="community-hero">
    <div>
        <h1>🌐 কমিউনিটি হাব ও গ্রুপস V2</h1>
        <p>Bondhoo-এর নিজস্ব এন্টারপ্রাইজ ডিজিটাল কমিউনিটি প্ল্যাটফর্ম। আপনার পছন্দের বিষয়, পেশা, প্রজেক্ট বা স্থানীয় কমিউনিটি খুঁজুন ও যুক্ত হোন।</p>
    </div>
    <div>
        <button class="btn-jj-primary" style="background: white; color: var(--jj-brand); font-weight: 800; font-size: 15px; padding: 12px 24px; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.15);" onclick="openCreateGroupModal()" aria-label="নতুন কমিউনিটি তৈরি করুন">
            ➕ নতুন কমিউনিটি তৈরি
        </button>
    </div>
</div>

<!-- Search & Sort Filter Bar -->
<div class="search-filter-card">
    <form action="{{ route('groups.index') }}" method="GET" style="display: flex; gap: 10px; flex: 1; flex-wrap: wrap;">
        <input type="text" name="q" value="{{ $searchQuery }}" class="search-input" placeholder="কমিউনিটির নাম, বিবরণ বা টপিক খুঁজুন..." style="flex: 1; min-width: 200px;" aria-label="কমিউনিটি সার্চ করুন">
        
        <select name="sort" class="search-input" style="width: auto;" onchange="this.form.submit()" aria-label="কমিউনিটি সাজানোর ক্রম">
            <option value="trending" {{ $activeSort === 'trending' ? 'selected' : '' }}>🔥 ট্রেন্ডিং ও সক্রিয়</option>
            <option value="health" {{ $activeSort === 'health' ? 'selected' : '' }}>🩺 হেলথ স্কোর (স্বাস্থ্যকর)</option>
            <option value="members" {{ $activeSort === 'members' ? 'selected' : '' }}>👥 সর্বাধিক সদস্য</option>
            <option value="newest" {{ $activeSort === 'newest' ? 'selected' : '' }}>✨ সম্প্রতি তৈরি</option>
        </select>

        @if($activeCategory && $activeCategory !== 'All')
            <input type="hidden" name="category" value="{{ $activeCategory }}">
        @endif

        <button type="submit" class="btn-jj-primary" aria-label="সার্চ সম্পন্ন করুন">🔍 খুঁজুন</button>
        @if($searchQuery || $activeCategory !== 'All' || $activeSort !== 'trending')
            <a href="{{ route('groups.index') }}" class="btn-jj-secondary" style="text-decoration: none;" aria-label="ফিল্টার রিসেট করুন">রিসেট</a>
        @endif
    </form>
</div>

<!-- Category Filter Pills Bar -->
<div class="category-pills-bar" role="navigation" aria-label="ক্যাটাগরি ফিল্টার">
    @foreach($categories as $key => $title)
        <a href="{{ route('groups.index', ['category' => $key, 'q' => $searchQuery, 'sort' => $activeSort]) }}" 
           class="category-pill {{ $activeCategory === $key ? 'active' : '' }}">
            {{ $title }}
        </a>
    @endforeach
</div>

<!-- Section: My Joined Groups (If logged in) -->
@if($currentUser && $myGroups->isNotEmpty())
    <div style="margin-bottom: 32px;">
        <div class="section-title">
            <span>আমার কমিউনিটি ও গ্রুপ ({{ $myGroups->count() }})</span>
            <span style="font-size: 13px; font-weight: 600; color: var(--jj-brand);">সদস্য ও অ্যাডমিন</span>
        </div>
        <div class="groups-grid">
            @foreach($myGroups as $group)
                <a href="{{ route('groups.show', ['slug' => $group->slug]) }}" class="community-card">
                    <div class="community-cover-box">
                        @if($group->cover_image_url)
                            <img src="{{ $group->cover_image_url }}" alt="{{ $group->name }}">
                        @endif
                        <div class="community-avatar-overlay">
                            @if($group->avatar_url)
                                <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}">
                            @else
                                {{ mb_substr($group->name, 0, 1) }}
                            @endif
                        </div>
                    </div>
                    <div class="community-card-body">
                        <div class="community-title-row">
                            <span class="community-name">{{ $group->name }}</span>
                            @if($group->is_verified)
                                <span class="verified-badge" title="ভেরিফাইড কমিউনিটি">✓</span>
                            @endif
                        </div>
                        <div class="community-meta">
                            <span>{{ $group->isPublic() ? '🌐 পাবলিক' : ($group->isPrivate() ? '🔒 প্রাইভেট' : '🛡️ সিক্রেট') }}</span>
                            <span>•</span>
                            <span>👥 {{ number_format($group->members_count) }} সদস্য</span>
                            @if($group->health_score > 0)
                                <span>•</span>
                                <span style="color: var(--jj-emerald); font-weight: 700;">🩺 {{ $group->health_score }}%</span>
                            @endif
                        </div>
                        <p class="community-desc">{{ Str::limit($group->description, 80) }}</p>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                            @if($group->category)
                                <span style="background: var(--jj-bg-subtle); border: 1px solid var(--jj-border); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">{{ $group->category }}</span>
                            @endif
                        </div>
                        <div style="margin-top: 12px;">
                            <span class="btn-jj-secondary" style="width: 100%; justify-content: center; font-weight: 700;">কমিউনিটিতে প্রবেশ</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif

<!-- Section: Discover All Groups / Search Results -->
<div style="margin-bottom: 40px;">
    <div class="section-title">
        <span>{{ $searchQuery ? "খোঁজার ফলাফল: \"{$searchQuery}\"" : ($activeCategory !== 'All' ? $categories[$activeCategory] . ' কমিউনিটি' : 'আবিষ্কার করুন কমিউনিটি') }}</span>
        <span style="font-size: 13px; color: var(--jj-text-muted);">মোট {{ number_format($allGroups->total()) }} টি কমিউনিটি</span>
    </div>

    @if($allGroups->isNotEmpty())
        <div class="groups-grid">
            @foreach($allGroups as $group)
                <div class="community-card">
                    <a href="{{ route('groups.show', ['slug' => $group->slug]) }}" style="text-decoration: none; color: inherit;">
                        <div class="community-cover-box">
                            @if($group->cover_image_url)
                                <img src="{{ $group->cover_image_url }}" alt="{{ $group->name }}">
                            @endif
                            <div class="community-avatar-overlay">
                                @if($group->avatar_url)
                                    <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}">
                                @else
                                    {{ mb_substr($group->name, 0, 1) }}
                                @endif
                            </div>
                        </div>
                    </a>
                    <div class="community-card-body">
                        <div class="community-title-row">
                            <a href="{{ route('groups.show', ['slug' => $group->slug]) }}" class="community-name" style="text-decoration: none;">{{ $group->name }}</a>
                            @if($group->is_verified)
                                <span class="verified-badge" title="ভেরিফাইড কমিউনিটি">✓</span>
                            @endif
                        </div>
                        <div class="community-meta">
                            <span>{{ $group->isPublic() ? '🌐 পাবলিক' : ($group->isPrivate() ? '🔒 প্রাইভেট' : '🛡️ হিডেন') }}</span>
                            <span>•</span>
                            <span>👥 {{ number_format($group->members_count) }} সদস্য</span>
                            @if($group->health_score > 0)
                                <span>•</span>
                                <span style="color: var(--jj-emerald); font-weight: 700;" title="কমিউনিটি হেলথ স্কোর">🩺 {{ $group->health_score }}%</span>
                            @endif
                        </div>
                        <p class="community-desc">{{ Str::limit($group->description, 85) }}</p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 10px; border-top: 1px solid var(--jj-border);">
                            @if($group->category)
                                <span style="background: var(--jj-bg-subtle); border: 1px solid var(--jj-border); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">{{ $group->category }}</span>
                            @endif
                            <span style="font-size: 12px; color: var(--jj-text-muted);">{{ $group->posts_count ?? 0 }} পোস্ট</span>
                        </div>

                        <div style="margin-top: 14px; display: flex; gap: 8px;">
                            <a href="{{ route('groups.show', ['slug' => $group->slug]) }}" class="btn-jj-secondary" style="flex: 1; text-decoration: none; justify-content: center; font-size: 13px;">বিস্তারিত</a>
                            @if($currentUser)
                                <button class="btn-jj-primary" style="flex: 1; justify-content: center; font-size: 13px;" onclick="quickJoinGroup({{ $group->id }}, this)">যোগ দিন</button>
                            @else
                                <a href="/login" class="btn-jj-primary" style="flex: 1; text-decoration: none; justify-content: center; font-size: 13px;">লগইন</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top: 24px;">
            {{ $allGroups->links() }}
        </div>
    @else
        <div style="text-align: center; padding: 50px 20px; color: var(--jj-text-muted); background: var(--jj-card); border-radius: 14px; border: 1px solid var(--jj-border);">
            <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
            <div style="font-size: 18px; font-weight: 800; color: var(--jj-text);">কোনো কমিউনিটি খুঁজে পাওয়া যায়নি</div>
            <p style="font-size: 14px; margin-top: 6px;">আপনার অনুসন্ধানের সাথে মেলে এমন কোনো গ্রুপ পাওয়া যায়নি। আপনি নতুন একটি শুরু করতে পারেন!</p>
            <button class="btn-jj-primary" style="margin-top: 16px;" onclick="openCreateGroupModal()">➕ নতুন কমিউনিটি তৈরি করুন</button>
        </div>
    @endif
</div>

<!-- Multi-Step Create Group Wizard Modal -->
<div class="modal-backdrop" id="createGroupModal" role="dialog" aria-modal="true" aria-labelledby="wizardTitle">
    <div class="modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--jj-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="wizardTitle" style="font-weight: 800; font-size: 18px; color: var(--jj-text);">নতুন এন্টারপ্রাইজ কমিউনিটি তৈরি করুন</h3>
            <button onclick="closeCreateGroupModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--jj-text-muted);" aria-label="উইন্ডো বন্ধ করুন">✕</button>
        </div>

        <!-- Wizard Step Indicators -->
        <div class="wizard-steps-header">
            <div class="wizard-step-item active" id="stepIndicator1">১. বেসিক তথ্য</div>
            <div class="wizard-step-item" id="stepIndicator2">২. ধরন ও প্রাইভেসি</div>
            <div class="wizard-step-item" id="stepIndicator3">৩. অনুমোদন নীতি</div>
            <div class="wizard-step-item" id="stepIndicator4">৪. নিয়মাবলী</div>
        </div>

        <form id="createGroupForm" onsubmit="submitCreateGroupWizard(event)">
            <div class="wizard-content">
                <!-- Step 1: Basic Information -->
                <div id="wizardStep1">
                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">কমিউনিটির নাম *</label>
                        <input type="text" id="grpName" required class="search-input" style="width: 100%;" placeholder="উদাঃ বাংলাদেশের প্রযুক্তিপ্রেমী">
                    </div>
                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">ইউনিক ইউজারনেম / হ্যান্ডেল (ঐচ্ছিক)</label>
                        <input type="text" id="grpUsername" class="search-input" style="width: 100%;" placeholder="উদাঃ bd-techies">
                    </div>
                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">ক্যাটাগরি</label>
                        <select id="grpCategory" class="search-input" style="width: 100%;">
                            <option value="Technology">প্রযুক্তি ও গ্যাজেট</option>
                            <option value="Business">ব্যবসা ও ক্যারিয়ার</option>
                            <option value="Education">শিক্ষা ও দক্ষতা</option>
                            <option value="Entertainment">বিনোদন ও সিনেমা</option>
                            <option value="Sports">খেলাধুলা ও ফিটনেস</option>
                            <option value="Gaming">গেমিং ও ই-স্পোর্টস</option>
                            <option value="Lifestyle">লাইফস্টাইল ও ভ্রমণ</option>
                            <option value="Community">সামাজিক ও আঞ্চলিক</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">কমিউনিটির বিবরণ ও লক্ষ্য</label>
                        <textarea id="grpDesc" rows="3" class="search-input" style="width: 100%; height: auto; border-radius: 8px; padding: 10px;" placeholder="এই কমিউনিটি কী উদ্দেশ্যে তৈরি, সদস্যরা কী নিয়ে আলোচনা করবেন..."></textarea>
                    </div>
                </div>

                <!-- Step 2: Community Type & Privacy -->
                <div id="wizardStep2" style="display: none;">
                    <label style="display: block; font-weight: 700; font-size: 14px; margin-bottom: 12px;">কমিউনিটি টাইপ নির্বাচন করুন *</label>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <label style="display: flex; gap: 12px; padding: 14px; border: 1px solid var(--jj-border); border-radius: 10px; cursor: pointer; background: var(--jj-bg-subtle);">
                            <input type="radio" name="grpPrivacy" value="public" checked style="margin-top: 4px;">
                            <div>
                                <div style="font-weight: 700; font-size: 15px;">🌐 পাবলিক কমিউনিটি (Public)</div>
                                <div style="font-size: 13px; color: var(--jj-text-muted); margin-top: 2px;">যে কেউ সার্চে খুঁজে পাবে, পোস্ট ও আলোচনা দেখতে পারবে এবং সরাসরি সদস্য হতে পারবে।</div>
                            </div>
                        </label>
                        <label style="display: flex; gap: 12px; padding: 14px; border: 1px solid var(--jj-border); border-radius: 10px; cursor: pointer; background: var(--jj-bg-subtle);">
                            <input type="radio" name="grpPrivacy" value="controlled" style="margin-top: 4px;">
                            <div>
                                <div style="font-weight: 700; font-size: 15px;">📑 নিয়ন্ত্রিত কমিউনিটি (Controlled)</div>
                                <div style="font-size: 13px; color: var(--jj-text-muted); margin-top: 2px;">কন্টেন্ট যে কেউ দেখতে পারবে, কিন্তু পোস্ট বা মন্তব্যের জন্য মেম্বারশিপ স্ক্রিনিং প্রয়োজন হবে।</div>
                            </div>
                        </label>
                        <label style="display: flex; gap: 12px; padding: 14px; border: 1px solid var(--jj-border); border-radius: 10px; cursor: pointer; background: var(--jj-bg-subtle);">
                            <input type="radio" name="grpPrivacy" value="private" style="margin-top: 4px;">
                            <div>
                                <div style="font-weight: 700; font-size: 15px;">🔒 প্রাইভেট কমিউনিটি (Private)</div>
                                <div style="font-size: 13px; color: var(--jj-text-muted); margin-top: 2px;">কমিউনিটি সার্চে পাওয়া যাবে, কিন্তু অভ্যন্তরীণ কন্টেন্ট দেখার জন্য অনুমোদন আবশ্যক।</div>
                            </div>
                        </label>
                        <label style="display: flex; gap: 12px; padding: 14px; border: 1px solid var(--jj-border); border-radius: 10px; cursor: pointer; background: var(--jj-bg-subtle);">
                            <input type="radio" name="grpPrivacy" value="hidden" style="margin-top: 4px;">
                            <div>
                                <div style="font-weight: 700; font-size: 15px;">🛡️ সিক্রেট বা হিডেন (Hidden)</div>
                                <div style="font-size: 13px; color: var(--jj-text-muted); margin-top: 2px;">সার্চ থেকে লুকানো থাকবে। শুধুমাত্র ইনভাইটেশন বা ডিরেক্ট লিংকের মাধ্যমে অ্যাক্সেস সম্ভব।</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Step 3: Membership & Post Approvals -->
                <div id="wizardStep3" style="display: none;">
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">সদস্যপদ অনুমোদন নীতি</label>
                        <select id="grpApprovalMode" class="search-input" style="width: 100%;">
                            <option value="anyone">সরাসরি যোগদান অনুমোদিত (স্বয়ংক্রিয়)</option>
                            <option value="approval_required">অ্যাডমিন বা মডারেটরের অনুমোদন প্রয়োজন</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">পোস্ট প্রকাশনা নীতি</label>
                        <select id="grpPostApprovalMode" class="search-input" style="width: 100%;">
                            <option value="auto">সরাসরি পোস্ট প্রকাশিত হবে</option>
                            <option value="admin_approval">প্রতিটি পোস্ট মডারেশন অনুমোদনের পর প্রকাশিত হবে</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">সদস্য স্ক্রিনিং প্রশ্ন (ঐচ্ছিক)</label>
                        <input type="text" id="grpQuestion1" class="search-input" style="width: 100%;" placeholder="উদাঃ আপনি কেন এই কমিউনিটিতে যোগ দিতে চান?">
                    </div>
                </div>

                <!-- Step 4: Community Rules -->
                <div id="wizardStep4" style="display: none;">
                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">নিয়ম ১ এর শিরোনাম</label>
                        <input type="text" id="ruleTitle1" class="search-input" style="width: 100%;" value="পারস্পরিক শ্রদ্ধা ও প্রফেশনালিজম বজায় রাখুন">
                    </div>
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">নিয়ম ১ এর বিস্তারিত বিবরণ</label>
                        <textarea id="ruleDesc1" rows="2" class="search-input" style="width: 100%; height: auto; border-radius: 8px; padding: 10px;">ব্যক্তিগত আক্রমণ, অশালীন ভাষা বা হেট স্পিচ সম্পূর্ণ নিষিদ্ধ।</textarea>
                    </div>
                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">নিয়ম ২ এর শিরোনাম</label>
                        <input type="text" id="ruleTitle2" class="search-input" style="width: 100%;" value="স্প্যাম ও অনিবন্ধিত বিজ্ঞাপন পরিহার করুন">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">নিয়ম ২ এর বিস্তারিত বিবরণ</label>
                        <textarea id="ruleDesc2" rows="2" class="search-input" style="width: 100%; height: auto; border-radius: 8px; padding: 10px;">অনুমোদনহীন বাণিজ্যিক প্রচার বা ক্ষতিকর লিংক পোস্ট করা যাবে না।</textarea>
                    </div>
                </div>
            </div>

            <!-- Wizard Footer Buttons -->
            <div style="padding: 16px 24px; border-top: 1px solid var(--jj-border); display: flex; justify-content: space-between; align-items: center; background: var(--jj-bg-subtle);">
                <button type="button" class="btn-jj-secondary" id="btnPrevStep" onclick="navigateWizard(-1)" style="display: none;">পূর্ববর্তী</button>
                <div style="display: flex; gap: 10px; margin-left: auto;">
                    <button type="button" class="btn-jj-secondary" onclick="closeCreateGroupModal()">বাতিল</button>
                    <button type="button" class="btn-jj-primary" id="btnNextStep" onclick="navigateWizard(1)">পরবর্তী</button>
                    <button type="submit" class="btn-jj-primary" id="btnFinishWizard" style="display: none;">তৈরি সম্পন্ন করুন</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Toast Feedback Container -->
<div class="toast-container" id="toastContainer"></div>
@endsection

@section('scripts')
<script>
    let currentStep = 1;

    function openCreateGroupModal() {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif
        currentStep = 1;
        updateWizardUI();
        document.getElementById('createGroupModal').style.display = 'flex';
    }

    function closeCreateGroupModal() {
        document.getElementById('createGroupModal').style.display = 'none';
    }

    function navigateWizard(delta) {
        if (delta > 0 && currentStep === 1) {
            const name = document.getElementById('grpName').value.trim();
            if (!name) {
                showToast('অনুগ্রহ করে গ্রুপের নাম প্রদান করুন।', 'error');
                return;
            }
        }
        currentStep += delta;
        if (currentStep < 1) currentStep = 1;
        if (currentStep > 4) currentStep = 4;
        updateWizardUI();
    }

    function updateWizardUI() {
        for (let i = 1; i <= 4; i++) {
            const stepEl = document.getElementById('wizardStep' + i);
            const indicatorEl = document.getElementById('stepIndicator' + i);
            if (stepEl) stepEl.style.display = i === currentStep ? 'block' : 'none';
            if (indicatorEl) {
                if (i === currentStep) {
                    indicatorEl.classList.add('active');
                } else {
                    indicatorEl.classList.remove('active');
                }
            }
        }

        const btnPrev = document.getElementById('btnPrevStep');
        const btnNext = document.getElementById('btnNextStep');
        const btnFinish = document.getElementById('btnFinishWizard');

        if (btnPrev) btnPrev.style.display = currentStep > 1 ? 'inline-flex' : 'none';
        if (btnNext) btnNext.style.display = currentStep < 4 ? 'inline-flex' : 'none';
        if (btnFinish) btnFinish.style.display = currentStep === 4 ? 'inline-flex' : 'none';
    }

    async function submitCreateGroupWizard(e) {
        e.preventDefault();
        const finishBtn = document.getElementById('btnFinishWizard');
        finishBtn.disabled = true;
        finishBtn.innerText = 'কমিউনিটি তৈরি হচ্ছে...';

        const name = document.getElementById('grpName').value.trim();
        const username = document.getElementById('grpUsername').value.trim();
        const category = document.getElementById('grpCategory').value;
        const description = document.getElementById('grpDesc').value.trim();
        const privacyRadio = document.querySelector('input[name="grpPrivacy"]:checked');
        const privacyVal = privacyRadio ? privacyRadio.value : 'public';
        const approvalMode = document.getElementById('grpApprovalMode').value;
        const postApprovalMode = document.getElementById('grpPostApprovalMode').value;

        const payload = {
            name: name,
            username: username || undefined,
            category: category,
            description: description,
            privacy: (privacyVal === 'controlled' || privacyVal === 'public') ? 'public' : (privacyVal === 'hidden' ? 'hidden' : 'private'),
            community_type: privacyVal,
            requires_member_approval: approvalMode === 'approval_required',
            requires_post_approval: postApprovalMode === 'admin_approval',
            post_permission: 'all',
        };

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/api/v2/groups', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (res.ok && data.data) {
                showToast('কমিউনিটি সফলভাবে তৈরি হয়েছে!', 'success');
                setTimeout(() => {
                    window.location.href = '/groups/' + (data.data.slug || data.data.id);
                }, 800);
            } else {
                const msg = data.error?.message || data.message || 'গ্রুপ তৈরি ব্যর্থ হয়েছে।';
                showToast(msg, 'error');
                finishBtn.disabled = false;
                finishBtn.innerText = 'তৈরি সম্পন্ন করুন';
            }
        } catch (err) {
            console.error(err);
            showToast('সার্ভার যোগাযোগে ত্রুটি ঘটেছে।', 'error');
            finishBtn.disabled = false;
            finishBtn.innerText = 'তৈরি সম্পন্ন করুন';
        }
    }

    async function quickJoinGroup(groupId, btn) {
        btn.disabled = true;
        btn.innerText = 'যোগ হচ্ছে...';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/api/groups/${groupId}/join`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                }
            });

            const data = await res.json();
            if (res.ok) {
                btn.innerText = 'যুক্ত ✓';
                btn.style.background = 'var(--jj-emerald)';
                showToast(data.message || 'কমিউনিটিতে যুক্ত হয়েছেন!', 'success');
            } else {
                showToast(data.message || 'যুক্ত হতে ব্যর্থ হয়েছে।', 'error');
                btn.disabled = false;
                btn.innerText = 'যোগ দিন';
            }
        } catch (err) {
            console.error(err);
            showToast('নেটওয়ার্ক ত্রুটি ঘটেছে।', 'error');
            btn.disabled = false;
            btn.innerText = 'যোগ দিন';
        }
    }

    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        if (type === 'error') toast.style.borderLeft = '4px solid #ef4444';
        if (type === 'success') toast.style.borderLeft = '4px solid var(--jj-emerald)';
        if (type === 'info') toast.style.borderLeft = '4px solid var(--jj-brand)';

        toast.innerText = message;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }
</script>
@endsection
