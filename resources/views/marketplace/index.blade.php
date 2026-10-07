@extends('layouts.app')

@section('title', 'মার্কেটপ্লেস — Bondhoo')

@section('styles')
<style>
    .market-banner {
        background: linear-gradient(135deg, #f59e0b, #ea580c);
        color: white;
        border-radius: 12px;
        padding: 30px 24px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .category-pills {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 8px;
        margin-bottom: 20px;
    }

    .cat-pill {
        padding: 8px 18px;
        border-radius: 20px;
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        color: var(--fb-text-primary);
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s;
    }

    .cat-pill:hover, .cat-pill.active {
        background: var(--fb-primary);
        color: white;
        border-color: var(--fb-primary);
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
    }

    .product-card {
        background: var(--fb-card);
        border-radius: 10px;
        border: 1px solid var(--fb-border);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .product-thumb {
        height: 180px;
        background: #f1f5f9;
        position: relative;
        overflow: hidden;
    }

    .product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .escrow-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(16, 185, 129, 0.9);
        color: white;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .product-body {
        padding: 14px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .product-price {
        font-size: 18px;
        font-weight: 800;
        color: var(--fb-primary);
        margin-bottom: 4px;
    }

    .product-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--fb-text-primary);
        margin-bottom: 8px;
        line-height: 1.3;
    }

    .product-location {
        font-size: 12px;
        color: var(--fb-text-secondary);
        margin-top: auto;
        display: flex;
        align-items: center;
        gap: 4px;
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
        width: 500px;
        max-width: 90vw;
        box-shadow: var(--shadow-lg);
        overflow: hidden;
    }
</style>
@endsection

@section('content')
<div class="market-banner">
    <div>
        <h1 style="font-size: 26px; font-weight: 800; margin-bottom: 6px;">🏪 মার্কেটপ্লেস ও কেনাবেচা</h1>
        <p style="font-size: 14px; opacity: 0.95;">১০০% নিরাপদ এসক্রো গ্যারান্টি: পণ্য হাতে না পাওয়া পর্যন্ত টাকা সুরক্ষিত থাকে।</p>
    </div>
    <div>
        <button class="btn-fb-primary" style="background: white; color: #ea580c;" onclick="openSellModal()">
            💰 পণ্য বিক্রি করুন
        </button>
    </div>
</div>

<!-- Search & Filters -->
<div class="fb-card" style="margin-bottom: 20px;">
    <form action="{{ route('marketplace.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 10px;">
        <input type="text" name="q" value="{{ $searchQuery }}" class="search-input" placeholder="পণ্য বা মডেলের নাম দিয়ে খুঁজুন..." style="flex: 2; min-width: 200px; background: var(--fb-bg); border: 1px solid var(--fb-border);">
        
        <input type="number" name="min_price" value="{{ request('min_price') }}" class="search-input" placeholder="সর্বনিম্ন ৳" style="flex: 1; min-width: 100px; background: var(--fb-bg); border: 1px solid var(--fb-border);">
        <input type="number" name="max_price" value="{{ request('max_price') }}" class="search-input" placeholder="সর্বোচ্চ ৳" style="flex: 1; min-width: 100px; background: var(--fb-bg); border: 1px solid var(--fb-border);">
        
        <button type="submit" class="btn-fb-primary">ফিল্টার করুন</button>
        @if($searchQuery || request('min_price') || request('max_price'))
            <a href="{{ route('marketplace.index') }}" class="btn-fb-secondary">রিসেট</a>
        @endif
    </form>
</div>

<!-- Category Pills -->
@if($categories->isNotEmpty())
    <div class="category-pills">
        <a href="{{ route('marketplace.index') }}" class="cat-pill {{ !$activeCategory ? 'active' : '' }}">
            🌟 সকল ক্যাটাগরি
        </a>
        @foreach($categories as $cat)
            <a href="{{ route('marketplace.index', ['category' => $cat->slug]) }}" class="cat-pill {{ $activeCategory === $cat->slug ? 'active' : '' }}">
                {{ $cat->icon ?? '📦' }} {{ $cat->name }}
            </a>
        @endforeach
    </div>
@endif

<!-- Products Grid -->
@if($products->isNotEmpty())
    <div class="products-grid">
        @foreach($products as $prod)
            @php
                $img = $prod->images && count($prod->images) > 0 ? $prod->images[0] : null;
            @endphp
            <a href="{{ route('marketplace.show', ['id' => $prod->id]) }}" class="product-card">
                <div class="product-thumb">
                    @if($img)
                        <img src="{{ $img }}" alt="{{ $prod->title }}">
                    @else
                        <div style="display:flex; align-items:center; justify-content:center; height:100%; font-size:40px; color:#94a3b8;">🛍️</div>
                    @endif
                    <div class="escrow-badge">
                        <span>🛡️</span> <span>এসক্রো সুরক্ষিত</span>
                    </div>
                </div>
                <div class="product-body">
                    <div class="product-price">৳ {{ number_format($prod->price) }}</div>
                    <div class="product-title">{{ Str::limit($prod->title, 42) }}</div>
                    <div class="product-location">
                        <span>📍 {{ $prod->location ?: 'বাংলাদেশ' }}</span>
                        <span>•</span>
                        <span>{{ $prod->condition === 'new' ? 'নতুন' : 'ব্যবহৃত' }}</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <div style="margin-top: 24px;">
        {{ $products->links() }}
    </div>
@else
    <div class="fb-card" style="text-align: center; padding: 50px 20px; color: var(--fb-text-secondary);">
        <div style="font-size: 48px; margin-bottom: 12px;">🏪</div>
        <h3 style="font-size: 18px; font-weight: 800; color: var(--fb-text-primary);">কোনো পণ্য পাওয়া যায়নি</h3>
        <p style="font-size: 14px; margin-top: 6px;">অন্য কোনো কি-ওয়ার্ড দিয়ে খুঁজুন অথবা আপনিই প্রথম পণ্যটি বিক্রির জন্য আপলোড করুন।</p>
    </div>
@endif

<!-- Sell Product Modal -->
<div class="modal-backdrop" id="sellProductModal">
    <div class="modal-box">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-weight: 800; font-size: 18px;">পণ্য বিক্রির বিজ্ঞাপন দিন</h3>
            <button onclick="closeSellModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--fb-text-secondary);">✕</button>
        </div>
        <form id="sellProductForm" onsubmit="submitSellProduct(event)" style="padding: 20px; max-height: 80vh; overflow-y: auto;">
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 4px;">পণ্যের শিরোনাম *</label>
                <input type="text" id="prodTitle" required class="search-input" style="border: 1px solid var(--fb-border);" placeholder="উদাঃ iPhone 13 Pro 128GB">
            </div>
            <div style="margin-bottom: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 4px;">মূল্য (টাকা) *</label>
                    <input type="number" id="prodPrice" required class="search-input" style="border: 1px solid var(--fb-border);" placeholder="৳ 45000">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 4px;">অবস্থা</label>
                    <select id="prodCondition" class="search-input" style="border: 1px solid var(--fb-border); width: 100%;">
                        <option value="used">ব্যবহৃত (Used)</option>
                        <option value="new">একেবারে নতুন (Brand New)</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 4px;">ক্যাটাগরি</label>
                <select id="prodCategory" class="search-input" style="border: 1px solid var(--fb-border); width: 100%;">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 4px;">লোকেশন (শহর বা এলাকা)</label>
                <input type="text" id="prodLocation" class="search-input" style="border: 1px solid var(--fb-border);" placeholder="উদাঃ ধানমন্ডি, ঢাকা">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 4px;">বিস্তারিত বিবরণ</label>
                <textarea id="prodDesc" rows="3" class="search-input" style="border: 1px solid var(--fb-border); width: 100%; height: auto; border-radius: 8px; padding: 10px;" placeholder="পণ্যের বিস্তারিত বিবরণ দিন..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-fb-secondary" onclick="closeSellModal()">বাতিল</button>
                <button type="submit" class="btn-fb-primary" id="btnSellSubmit">বিজ্ঞাপন প্রকাশ করুন</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openSellModal() {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif
        document.getElementById('sellProductModal').style.display = 'flex';
    }

    function closeSellModal() {
        document.getElementById('sellProductModal').style.display = 'none';
    }

    function submitSellProduct(e) {
        e.preventDefault();
        const title = document.getElementById('prodTitle').value.trim();
        const price = document.getElementById('prodPrice').value;
        const condition = document.getElementById('prodCondition').value;
        const category_id = document.getElementById('prodCategory').value;
        const location = document.getElementById('prodLocation').value.trim();
        const description = document.getElementById('prodDesc').value.trim();
        const btn = document.getElementById('btnSellSubmit');
        btn.disabled = true;
        btn.textContent = 'পোস্ট হচ্ছে...';

        fetch('/api/v2/marketplace/products', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ title, price, condition, category_id, location, description, images: [] })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.data) {
                alert('পণ্যটি সফলভাবে মার্কেটপ্লেসে যুক্ত হয়েছে!');
                window.location.reload();
            } else {
                alert(data.message || 'পণ্য আপলোড ব্যর্থ হয়েছে।');
                btn.disabled = false;
                btn.textContent = 'বিজ্ঞাপন প্রকাশ করুন';
            }
        })
        .catch(err => {
            alert('পণ্য আপলোড ব্যর্থ হয়েছে।');
            btn.disabled = false;
            btn.textContent = 'বিজ্ঞাপন প্রকাশ করুন';
        });
    }
</script>
@endsection
