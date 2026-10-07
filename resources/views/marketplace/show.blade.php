@extends('layouts.app')

@section('title', $product->title . ' — মার্কেটপ্লেস')

@section('styles')
<style>
    .product-details-container {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 24px;
        margin-bottom: 30px;
    }

    .gallery-box {
        background: var(--fb-card);
        border-radius: 12px;
        border: 1px solid var(--fb-border);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        height: 440px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
    }

    .gallery-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .product-info-pane {
        background: var(--fb-card);
        border-radius: 12px;
        border: 1px solid var(--fb-border);
        padding: 24px;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
    }

    .product-price-tag {
        font-size: 28px;
        font-weight: 800;
        color: var(--fb-primary);
        margin: 10px 0;
    }

    .escrow-feature-card {
        background: #ecfdf5;
        border: 1px solid #6ee7b7;
        border-radius: 8px;
        padding: 14px;
        margin: 16px 0;
        display: flex;
        gap: 12px;
    }

    .seller-box {
        border-top: 1px solid var(--fb-border);
        padding-top: 16px;
        margin-top: auto;
    }

    @media (max-width: 900px) {
        .product-details-container {
            grid-template-columns: 1fr;
        }
        .gallery-box {
            height: 300px;
        }
    }
</style>
@endsection

@section('content')
<div style="margin-bottom: 16px;">
    <a href="{{ route('marketplace.index') }}" class="btn-fb-secondary">← মার্কেটপ্লেসে ফিরে যান</a>
</div>

<div class="product-details-container">
    <!-- Left Column: Gallery & Description -->
    <div>
        <div class="gallery-box">
            @php
                $img = $product->images && count($product->images) > 0 ? $product->images[0] : null;
            @endphp
            @if($img)
                <img src="{{ $img }}" alt="{{ $product->title }}">
            @else
                <div style="font-size: 72px;">🛍️</div>
            @endif
        </div>

        <div class="fb-card" style="margin-top: 20px;">
            <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 12px;">📄 পণ্যের বিবরণ</h2>
            <p style="font-size: 15px; line-height: 1.6; color: var(--fb-text-primary); white-space: pre-line;">
                {{ $product->description ?: 'এই পণ্যের কোনো অতিরিক্ত বিবরণ দেওয়া হয়নি।' }}
            </p>
            <div style="margin-top: 20px; border-top: 1px solid var(--fb-border); padding-top: 12px; font-size: 13px; color: var(--fb-text-secondary); display: flex; flex-wrap: wrap; gap: 16px;">
                <span>👁️ দেখা হয়েছে: {{ number_format($product->views_count) }} বার</span>
                <span>🗓️ বিজ্ঞাপন পোস্ট: {{ $product->created_at ? $product->created_at->diffForHumans() : 'সম্প্রতি' }}</span>
                <span>🏷️ ক্যাটাগরি: {{ $product->category?->name ?? 'সাধারণ' }}</span>
            </div>
        </div>
    </div>

    <!-- Right Column: Purchase & Seller Info -->
    <div class="product-info-pane">
        <h1 style="font-size: 22px; font-weight: 800; line-height: 1.3;">{{ $product->title }}</h1>
        <div class="product-price-tag">৳ {{ number_format($product->price) }}</div>

        <div style="font-size: 14px; color: var(--fb-text-secondary); margin-bottom: 12px;">
            <span>📍 অবস্থান: <strong>{{ $product->location ?: 'বাংলাদেশ' }}</strong></span><br>
            <span>✨ অবস্থা: <strong>{{ $product->condition === 'new' ? 'একেবারে নতুন (Brand New)' : 'ব্যবহৃত (Used)' }}</strong></span>
        </div>

        <!-- Escrow Protection Badge -->
        <div class="escrow-feature-card">
            <div style="font-size: 24px;">🛡️</div>
            <div>
                <div style="font-weight: 800; font-size: 14px; color: #065f46;">Bondhoo এসক্রো নিরাপত্তা</div>
                <div style="font-size: 12px; color: #047857; margin-top: 2px;">
                    আপনি অর্ডার করলে আপনার টাকা Bondhoo এসক্রোতে সংরক্ষিত থাকবে। পণ্য বুঝে পেয়ে রিলিজ করলে তবেই বিক্রেতা টাকা পাবেন।
                </div>
            </div>
        </div>

        <!-- Order Action Button -->
        <button class="btn-fb-primary" style="padding: 12px 20px; font-size: 16px; justify-content: center; margin-bottom: 10px;" onclick="openOrderModal()">
            ⚡ অর্ডার করুন (নিরাপদ এসক্রো)
        </button>

        <a href="/messages" class="btn-fb-secondary" style="padding: 10px 20px; font-size: 14px; justify-content: center; margin-bottom: 20px;">
            💬 বিক্রেতাকে মেসেজ পাঠান
        </a>

        <!-- Seller Profile Card -->
        <div class="seller-box">
            <h3 style="font-size: 14px; font-weight: 700; color: var(--fb-text-secondary); margin-bottom: 10px;">বিক্রেতার তথ্য</h3>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="avatar" style="width: 46px; height: 46px;">
                    @if($product->seller?->profile?->avatar_url)
                        <img src="{{ $product->seller->profile->avatar_url }}" alt="{{ $product->seller->name }}">
                    @else
                        {{ mb_substr($product->seller?->name ?? 'স', 0, 1) }}
                    @endif
                </div>
                <div>
                    <a href="{{ getUserProfileUrl($product->seller) }}" style="font-weight: 700; font-size: 15px; color: var(--fb-text-primary); text-decoration: none;">
                        {{ $product->seller?->name ?? 'সেলার' }}
                    </a>
                    <div style="font-size: 12px; color: var(--fb-text-secondary);">
                        Bondhoo সদস্য • {{ $product->seller?->created_at ? $product->seller->created_at->format('Y') : '২০২৬' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Similar Products -->
@if($similarProducts->isNotEmpty())
    <div style="margin-top: 30px;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 16px;">🔍 একই ক্যাটাগরির আরও পণ্য</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
            @foreach($similarProducts as $sim)
                <a href="{{ route('marketplace.show', ['id' => $sim->id]) }}" class="fb-card" style="text-decoration: none; color: inherit;">
                    <div style="font-weight: 800; font-size: 16px; color: var(--fb-primary);">৳ {{ number_format($sim->price) }}</div>
                    <div style="font-weight: 700; font-size: 14px; margin-top: 4px;">{{ Str::limit($sim->title, 30) }}</div>
                    <div style="font-size: 12px; color: var(--fb-text-secondary); margin-top: 4px;">📍 {{ $sim->location }}</div>
                </a>
            @endforeach
        </div>
    </div>
@endif

<!-- Order Confirmation Modal -->
<div id="orderModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--fb-card); border-radius: 12px; width: 440px; max-width: 90vw; padding: 24px; box-shadow: var(--shadow-lg);">
        <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 10px;">অর্ডার নিশ্চিতকরণ</h3>
        <p style="font-size: 14px; color: var(--fb-text-secondary); margin-bottom: 16px;">
            আপনি <strong>{{ $product->title }}</strong> পণ্যটির জন্য <strong>৳ {{ number_format($product->price) }}</strong> এসক্রো পেমেন্ট সম্পন্ন করতে যাচ্ছেন।
        </p>
        <div style="background: var(--fb-bg); padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 13px;">
            <div>📦 পণ্য মূল্য: <strong>৳ {{ number_format($product->price) }}</strong></div>
            <div>🚚 ডেলিভারি চার্জ: <strong>৳ ০ (ফ্রি)</strong></div>
            <div style="border-top: 1px solid var(--fb-border); margin-top: 6px; padding-top: 6px; font-weight: 800;">
                মোট প্রদেয়: ৳ {{ number_format($product->price) }}
            </div>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button class="btn-fb-secondary" onclick="closeOrderModal()">বাতিল</button>
            <button class="btn-fb-primary" id="btnConfirmOrder" onclick="processEscrowOrder()">পেমেন্ট নিশ্চিত করুন</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openOrderModal() {
        @if(!$currentUser)
            window.location.href = '/login';
            return;
        @endif
        document.getElementById('orderModal').style.display = 'flex';
    }

    function closeOrderModal() {
        document.getElementById('orderModal').style.display = 'none';
    }

    function processEscrowOrder() {
        const btn = document.getElementById('btnConfirmOrder');
        btn.disabled = true;
        btn.textContent = 'প্রসেসিং...';

        fetch('/api/v2/marketplace/products/{{ $product->id }}/order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.data) {
                alert('অর্ডার সফল হয়েছে! আপনার টাকা এসক্রো সুরক্ষায় রক্ষিত আছে। বিক্রেতার সাথে মেসেঞ্জারে যোগাযোগ করুন।');
                closeOrderModal();
            } else {
                alert(data.message || 'অর্ডার সম্পন্ন করা যায়নি।');
                btn.disabled = false;
                btn.textContent = 'পেমেন্ট নিশ্চিত করুন';
            }
        })
        .catch(err => {
            alert('অর্ডার ব্যর্থ হয়েছে।');
            btn.disabled = false;
            btn.textContent = 'পেমেন্ট নিশ্চিত করুন';
        });
    }
</script>
@endsection
