@extends('layouts.app')

@section('title', 'পেইজসমূহ — Bondhoo')

@section('styles')
<style>
    .pages-header-banner {
        background: linear-gradient(135deg, #10b981, #0284c7);
        color: white;
        border-radius: 12px;
        padding: 32px 24px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .pages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 18px;
    }

    .page-card {
        background: var(--fb-card);
        border-radius: 10px;
        border: 1px solid var(--fb-border);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s;
        text-decoration: none;
        color: inherit;
    }

    .page-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .page-cover-box {
        height: 100px;
        background: linear-gradient(45deg, #059669, #0284c7);
        position: relative;
    }

    .page-cover-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .page-avatar-overlap {
        position: absolute;
        bottom: -24px;
        left: 16px;
        border: 3px solid var(--fb-card);
    }

    .page-card-body {
        padding: 32px 16px 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .page-name {
        font-size: 17px;
        font-weight: 700;
        color: var(--fb-text-primary);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .page-category {
        font-size: 13px;
        color: var(--fb-text-secondary);
        margin: 4px 0 8px;
    }

    .page-bio {
        font-size: 13px;
        color: var(--fb-text-secondary);
        line-height: 1.4;
        margin-bottom: 14px;
        flex: 1;
    }

    /* Modal */
    .modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal-box {
        background: var(--fb-card);
        border-radius: 12px;
        width: 480px;
        max-width: 90vw;
        box-shadow: var(--shadow-lg);
        overflow: hidden;
    }
</style>
@endsection

@section('content')
<div class="pages-header-banner">
    <div>
        <h1 style="font-size: 26px; font-weight: 800; margin-bottom: 6px;">📄 পেইজ ও পাবলিক এন্টিটি</h1>
        <p style="font-size: 14px; opacity: 0.9;">আপনার ব্র্যান্ড, ব্যবসা, সংগঠন বা ব্যক্তিত্বের জন্য পেইজ তৈরি ও অনুসরণ করুন।</p>
    </div>
    <div>
        <a href="{{ route('pages.create') }}" class="btn-fb-primary" style="background: white; color: #059669; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>নতুন পেইজ তৈরি করুন</span>
        </a>
    </div>
</div>

<!-- Search Bar & Filters -->
<div class="fb-card" style="margin-bottom: 20px;">
    <form action="{{ route('pages.index') }}" method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
        <input type="text" name="q" value="{{ $searchQuery }}" class="search-input" placeholder="পেইজের নাম, ক্যাটাগরি বা বিষয় খুঁজুন..." style="flex: 1; min-width: 220px; background: var(--fb-bg); border: 1px solid var(--fb-border);">
        @if($selectedCategory)
            <input type="hidden" name="category" value="{{ $selectedCategory }}">
        @endif
        <input type="hidden" name="sort" value="{{ $sort }}">
        <button type="submit" class="btn-fb-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <span>খুঁজুন</span>
        </button>
        <div style="display: flex; gap: 6px; align-items: center; margin-left: auto;">
            <a href="{{ route('pages.index', array_filter(array_merge(request()->query(), ['sort' => 'popular']))) }}" class="{{ $sort === 'popular' ? 'btn-fb-primary' : 'btn-fb-secondary' }}" style="font-size: 13px; text-decoration: none; padding: 6px 12px;">🔥 জনপ্রিয়</a>
            <a href="{{ route('pages.index', array_filter(array_merge(request()->query(), ['sort' => 'recent']))) }}" class="{{ $sort === 'recent' ? 'btn-fb-primary' : 'btn-fb-secondary' }}" style="font-size: 13px; text-decoration: none; padding: 6px 12px;">✨ নতুন</a>
            @if($searchQuery || $selectedCategory)
                <a href="{{ route('pages.index') }}" class="btn-fb-secondary" style="font-size: 13px; text-decoration: none; padding: 6px 12px;">রিসেট</a>
            @endif
        </div>
    </form>

    @if(isset($categories) && $categories->isNotEmpty())
        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--fb-border); align-items: center;">
            <span style="font-size: 12px; font-weight: 700; color: var(--fb-text-secondary); margin-right: 4px;">ফিল্টার:</span>
            <a href="{{ route('pages.index', array_filter(array_merge(request()->query(), ['category' => null]))) }}"
               style="text-decoration: none; padding: 4px 12px; border-radius: 16px; font-size: 12px; font-weight: 600; {{ !$selectedCategory ? 'background: #059669; color: white;' : 'background: var(--fb-bg); color: var(--fb-text-primary); border: 1px solid var(--fb-border);' }}">
                সকল
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('pages.index', array_filter(array_merge(request()->query(), ['category' => $cat]))) }}"
                   style="text-decoration: none; padding: 4px 12px; border-radius: 16px; font-size: 12px; font-weight: 600; {{ $selectedCategory === $cat ? 'background: #059669; color: white;' : 'background: var(--fb-bg); color: var(--fb-text-primary); border: 1px solid var(--fb-border);' }}">
                    {{ $cat }}
                </a>
            @endforeach
        </div>
    @endif
</div>

@if($currentUser && $managedPages->isNotEmpty())
    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 16px; color: var(--fb-text-primary); display: flex; align-items: center; gap: 8px;">
            <span>💼</span>
            <span>আপনার পরিচালিত পেইজসমূহ ({{ $managedPages->count() }})</span>
        </h2>
        <div class="pages-grid">
            @foreach($managedPages as $page)
                <div class="page-card">
                    <div class="page-cover-box">
                        @if($page->cover_image_url)
                            <img src="{{ $page->cover_image_url }}" alt="{{ $page->name }}">
                        @endif
                        <div class="avatar page-avatar-overlap" style="width: 50px; height: 50px;">
                            @if($page->avatar_url)
                                <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}">
                            @else
                                {{ mb_substr($page->name, 0, 1) }}
                            @endif
                        </div>
                    </div>
                    <div class="page-card-body">
                        <div class="page-name">
                            <span>{{ $page->name }}</span>
                            @if($page->is_verified)
                                <span title="ভেরিফাইড পেইজ" style="color: var(--fb-primary);">✓</span>
                            @endif
                        </div>
                        <div class="page-category">🏷️ {{ $page->category }} • 👥 {{ number_format($page->followers_count) }} ফলোয়ার</div>
                        <div class="page-bio">{{ Str::limit($page->bio, 60) }}</div>
                        <div style="display: flex; gap: 8px; margin-top: auto;">
                            <a href="{{ route('pages.manage', ['slug' => $page->slug]) }}" class="btn-fb-primary" style="flex: 1; text-align: center; text-decoration: none; font-size: 13px; padding: 8px;">
                                ⚙️ স্টুডিও
                            </a>
                            <a href="{{ route('pages.show', ['slug' => $page->slug]) }}" class="btn-fb-secondary" style="flex: 1; text-align: center; text-decoration: none; font-size: 13px; padding: 8px;">
                                পাবলিক ভিউ
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if($currentUser && $followedPages->isNotEmpty())
    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 16px;">⭐ আপনার ফলো করা পেইজসমূহ ({{ $followedPages->count() }})</h2>
        <div class="pages-grid">
            @foreach($followedPages as $page)
                <a href="{{ route('pages.show', ['slug' => $page->slug]) }}" class="page-card">
                    <div class="page-cover-box">
                        @if($page->cover_image_url)
                            <img src="{{ $page->cover_image_url }}" alt="{{ $page->name }}">
                        @endif
                        <div class="avatar page-avatar-overlap" style="width: 50px; height: 50px;">
                            @if($page->avatar_url)
                                <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}">
                            @else
                                {{ mb_substr($page->name, 0, 1) }}
                            @endif
                        </div>
                    </div>
                    <div class="page-card-body">
                        <div class="page-name">
                            <span>{{ $page->name }}</span>
                            @if($page->is_verified)
                                <span title="ভেরিফাইড পেইজ" style="color: var(--fb-primary);">✓</span>
                            @endif
                        </div>
                        <div class="page-category">🏷️ {{ $page->category }} • 👥 {{ number_format($page->followers_count) }} ফলোয়ার</div>
                        <div class="page-bio">{{ Str::limit($page->bio, 60) }}</div>
                        <span class="btn-fb-secondary" style="width: 100%; justify-content: center;">পেইজে যান</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif

<!-- Discover All Pages -->
<div>
    <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 16px;">🌐 জনপ্রিয় পেইজসমূহ এক্সপ্লোর করুন</h2>
    @if($allPages->isNotEmpty())
        <div class="pages-grid">
            @foreach($allPages as $page)
                <a href="{{ route('pages.show', ['slug' => $page->slug]) }}" class="page-card">
                    <div class="page-cover-box">
                        @if($page->cover_image_url)
                            <img src="{{ $page->cover_image_url }}" alt="{{ $page->name }}">
                        @endif
                        <div class="avatar page-avatar-overlap" style="width: 50px; height: 50px;">
                            @if($page->avatar_url)
                                <img src="{{ $page->avatar_url }}" alt="{{ $page->name }}">
                            @else
                                {{ mb_substr($page->name, 0, 1) }}
                            @endif
                        </div>
                    </div>
                    <div class="page-card-body">
                        <div class="page-name">
                            <span>{{ $page->name }}</span>
                            @if($page->is_verified)
                                <span title="ভেরিফাইড পেইজ" style="color: var(--fb-primary);">✓</span>
                            @endif
                        </div>
                        <div class="page-category">🏷️ {{ $page->category }} • 👥 {{ number_format($page->followers_count) }} ফলোয়ার</div>
                        <div class="page-bio">{{ Str::limit($page->bio, 70) }}</div>
                        <span class="btn-fb-primary" style="width: 100%; justify-content: center;">পেইজ দেখুন ও ফলো করুন</span>
                    </div>
                </a>
            @endforeach
        </div>
        <div style="margin-top: 24px;">
            {{ $allPages->links() }}
        </div>
    @else
        <div class="fb-card" style="text-align: center; padding: 40px; color: var(--fb-text-secondary);">
            <div style="font-size: 40px; margin-bottom: 10px;">📄</div>
            <div style="font-size: 16px; font-weight: 700;">কোনো পেইজ পাওয়া যায়নি</div>
            <div style="font-size: 13px; margin-top: 4px;">নতুন কোনো পেইজ তৈরি করতে পারেন বা অন্য কি-ওয়ার্ড দিয়ে খুঁজুন।</div>
        </div>
    @endif
</div>

<!-- Create Page Modal -->
<div class="modal-backdrop" id="createPageModal">
    <div class="modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-weight: 800; font-size: 18px;">নতুন পেইজ তৈরি করুন</h3>
            <button onclick="closeCreatePageModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);">✕</button>
        </div>
        <form id="createPageForm" onsubmit="submitCreatePage(event)" style="padding: 20px;">
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">পেইজের নাম *</label>
                <input type="text" id="pageName" required class="search-input" style="border: 1px solid var(--fb-border);" placeholder="উদাঃ বাংলা টেক নিউজ">
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">ক্যাটাগরি</label>
                <select id="pageCat" class="search-input" style="border: 1px solid var(--fb-border); width: 100%;">
                    <option value="প্রযুক্তি ও মিডিয়া">প্রযুক্তি ও মিডিয়া</option>
                    <option value="ব্যবসা ও ই-কমার্স">ব্যবসা ও ই-কমার্স</option>
                    <option value="বিনোদন ও আর্ট">বিনোদন ও আর্ট</option>
                    <option value="শিক্ষা ও ক্যারিয়ার">শিক্ষা ও ক্যারিয়ার</option>
                    <option value="ব্যক্তিগত ব্লগ">ব্যক্তিগত ব্লগ</option>
                </select>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px;">পেইজের বিবরণ</label>
                <textarea id="pageBio" rows="3" class="search-input" style="border: 1px solid var(--fb-border); width: 100%; height: auto; border-radius: 8px; padding: 10px;" placeholder="এই পেইজটি সম্পর্কে সংক্ষেপে লিখুন..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-fb-secondary" onclick="closeCreatePageModal()">বাতিল</button>
                <button type="submit" class="btn-fb-primary" id="btnSubmitPage">তৈরি করুন</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openCreatePageModal() {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif
        document.getElementById('createPageModal').style.display = 'flex';
    }

    function closeCreatePageModal() {
        document.getElementById('createPageModal').style.display = 'none';
    }

    function submitCreatePage(e) {
        e.preventDefault();
        const name = document.getElementById('pageName').value.trim();
        const category = document.getElementById('pageCat').value;
        const bio = document.getElementById('pageBio').value.trim();
        const btn = document.getElementById('btnSubmitPage');
        btn.disabled = true;
        btn.textContent = 'তৈরি হচ্ছে...';

        fetch('/api/v1/pages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name, category, bio })
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' || res.data) {
                const slug = res.data?.slug || '';
                window.location.href = `/pages/${slug}`;
            } else {
                alert(res.message || 'পেইজ তৈরি ব্যর্থ হয়েছে।');
                btn.disabled = false;
                btn.textContent = 'তৈরি করুন';
            }
        })
        .catch(err => {
            alert('পেইজ তৈরি করতে একটি ত্রুটি ঘটেছে।');
            btn.disabled = false;
            btn.textContent = 'তৈরি করুন';
        });
    }
</script>
@endsection
