@extends('layouts.app')

@section('title', 'আমার অর্ডার ও এসক্রো — মার্কেটপ্লেস')

@section('styles')
<style>
    .orders-container {
        max-width: 960px;
        margin: 0 auto 40px auto;
    }

    .orders-header {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .order-tabs {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--fb-border);
        padding-bottom: 10px;
    }

    .order-tab-link {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 15px;
        text-decoration: none;
        color: var(--fb-text-secondary);
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        transition: all 0.2s;
    }

    .order-tab-link.active {
        background: var(--fb-primary);
        color: white;
        border-color: var(--fb-primary);
    }

    .order-card {
        background: var(--fb-card);
        border-radius: 12px;
        border: 1px solid var(--fb-border);
        box-shadow: var(--shadow-sm);
        margin-bottom: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .order-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--fb-border);
        padding-bottom: 12px;
        flex-wrap: wrap;
        gap: 8px;
    }

    .order-card-body {
        display: grid;
        grid-template-columns: 80px 1fr auto;
        gap: 16px;
        align-items: center;
    }

    .order-product-thumb {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        object-fit: cover;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        overflow: hidden;
    }

    .order-product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .badge-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: capitalize;
    }

    .status-pending { background: #fef3c7; color: #b45309; }
    .status-processing { background: #dbeafe; color: #1d4ed8; }
    .status-shipped { background: #e0e7ff; color: #4338ca; }
    .status-delivered { background: #d1fae5; color: #047857; }
    .status-cancelled { background: #fee2e2; color: #b91c1c; }

    .escrow-held { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .escrow-released { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

    @media (max-width: 640px) {
        .order-card-body {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="orders-container">
    <div style="margin-bottom: 14px;">
        <a href="{{ route('marketplace.index') }}" class="btn-fb-secondary">← মার্কেটপ্লেসে ফিরে যান</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600;">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600;">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    <div class="orders-header">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; margin-bottom: 4px;">📦 অর্ডার ও এসক্রো ট্র্যাকার</h1>
            <p style="font-size: 14px; opacity: 0.95;">সকল লেনদেন এসক্রো সুরক্ষিত। পণ্য ডেলিভারি কনফার্মের পর টাকা রিলিজ হয়।</p>
        </div>
        <div>
            <a href="{{ route('marketplace.my-listings') }}" class="btn-fb-secondary" style="background: white; color: #059669; font-weight: 700; text-decoration: none;">
                🏷️ আমার বিজ্ঞাপন
            </a>
        </div>
    </div>

    <!-- Tab navigation -->
    <div class="order-tabs">
        <a href="{{ route('marketplace.orders', ['tab' => 'purchases']) }}" class="order-tab-link {{ $activeTab === 'purchases' ? 'active' : '' }}">
            🛍️ আমার ক্রয় (Purchases)
        </a>
        <a href="{{ route('marketplace.orders', ['tab' => 'sales']) }}" class="order-tab-link {{ $activeTab === 'sales' ? 'active' : '' }}">
            💼 আমার বিক্রয় (Sales)
        </a>
    </div>

    @php
        $ordersList = $activeTab === 'purchases' ? $purchases : $sales;
    @endphp

    @if($ordersList->isNotEmpty())
        @foreach($ordersList as $ord)
            @php
                $prod = $ord->product;
                $img = $prod?->images && count($prod->images) > 0 ? $prod->images[0] : null;
            @endphp
            <div class="order-card">
                <div class="order-card-header">
                    <div>
                        <span style="font-weight: 800; font-size: 15px;">অর্ডার #{{ $ord->id }}</span>
                        <span style="color: var(--fb-text-secondary); font-size: 13px; margin-left: 8px;">
                            • {{ $ord->created_at ? $ord->created_at->format('d M, Y h:i A') : 'সম্প্রতি' }}
                        </span>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span class="badge-status status-{{ $ord->status }}">
                            {{ match($ord->status) {
                                'pending' => 'পেন্ডিং',
                                'processing' => 'প্রসেসিং',
                                'shipped' => 'শিপড / ট্রানজিটে',
                                'delivered' => 'ডেলিভার্ড',
                                'cancelled' => 'বাতিল',
                                default => $ord->status
                            } }}
                        </span>
                        <span class="badge-status {{ $ord->escrow_status === 'released' ? 'escrow-released' : 'escrow-held' }}">
                            🛡️ {{ $ord->escrow_status === 'released' ? 'এসক্রো সম্পন্ন (Released)' : 'এসক্রো জমা (Held)' }}
                        </span>
                    </div>
                </div>

                <div class="order-card-body">
                    <div class="order-product-thumb">
                        @if($img)
                            <img src="{{ $img }}" alt="{{ $prod?->title ?? 'Product' }}">
                        @else
                            🛍️
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('marketplace.show', ['id' => $ord->product_id]) }}" style="font-weight: 800; font-size: 16px; color: var(--fb-text-primary); text-decoration: none;">
                            {{ $prod?->title ?? 'পণ্য উপলভ্য নয়' }}
                        </a>
                        <div style="font-size: 14px; font-weight: 700; color: var(--fb-primary); margin-top: 4px;">
                            ৳ {{ number_format($ord->amount) }}
                            <span style="font-size: 12px; font-weight: 500; color: var(--fb-text-secondary); margin-left: 8px;">
                                পেমেন্ট: {{ strtoupper($ord->payment_method) }}
                            </span>
                        </div>
                        <div style="font-size: 12px; color: var(--fb-text-secondary); margin-top: 4px;">
                            📍 ডেলিভারি ঠিকানা: {{ $ord->shipping_address ?: 'ঠিকানা দেওয়া হয়নি' }}
                        </div>
                        <div style="font-size: 12px; color: var(--fb-text-secondary); margin-top: 2px;">
                            @if($activeTab === 'purchases')
                                👤 বিক্রেতা: <strong>{{ $ord->seller?->name ?? 'সেলার' }}</strong>
                            @else
                                👤 ক্রেতা: <strong>{{ $ord->buyer?->name ?? 'ক্রেতা' }}</strong>
                            @endif
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="{{ route('marketplace.orders.show', ['id' => $ord->id]) }}" class="btn-fb-secondary" style="font-size: 13px; text-decoration: none; justify-content: center;">
                            📄 ইনভয়েস বিবরণ
                        </a>

                        @if($activeTab === 'purchases')
                            @if($ord->escrow_status === 'held' && in_array($ord->status, ['processing', 'shipped', 'delivered']))
                                <form action="{{ route('marketplace.orders.release-escrow', ['id' => $ord->id]) }}" method="POST" onsubmit="return confirm('আপনি কি পণ্যটি হাতে পেয়েছেন এবং বিক্রেতাকে ফান্ড রিলিজ করতে চান?');">
                                    @csrf
                                    <button type="submit" class="btn-fb-primary" style="background: #10b981; border-color: #10b981; font-size: 13px; width: 100%; justify-content: center;">
                                        ✅ পণ্য পেয়েছি (রিলিজ এসক্রো)
                                    </button>
                                </form>
                            @endif
                        @else
                            {{-- Seller Actions --}}
                            @if($ord->status === 'pending' || $ord->status === 'processing')
                                <form action="{{ route('marketplace.orders.status', ['id' => $ord->id]) }}" method="POST" style="display: flex; gap: 4px;">
                                    @csrf
                                    <input type="hidden" name="status" value="shipped">
                                    <button type="submit" class="btn-fb-primary" style="font-size: 12px; width: 100%; justify-content: center;">
                                        🚚 শিপড মার্ক করুন
                                    </button>
                                </form>
                            @elseif($ord->status === 'shipped')
                                <form action="{{ route('marketplace.orders.status', ['id' => $ord->id]) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="delivered">
                                    <button type="submit" class="btn-fb-secondary" style="font-size: 12px; width: 100%; justify-content: center;">
                                        📦 ডেলিভার্ড মার্ক করুন
                                    </button>
                                </form>
                            @endif
                        @endif

                        <a href="/messages" class="btn-fb-secondary" style="font-size: 12px; text-decoration: none; justify-content: center;">
                            💬 মেসেঞ্জারে বার্তা
                        </a>
                    </div>
                </div>
            </div>
        @endforeach

        <div style="margin-top: 20px;">
            {{ $ordersList->links() }}
        </div>
    @else
        <div class="fb-card" style="text-align: center; padding: 50px 20px; color: var(--fb-text-secondary);">
            <div style="font-size: 48px; margin-bottom: 12px;">📭</div>
            <h3 style="font-size: 18px; font-weight: 800; color: var(--fb-text-primary);">
                {{ $activeTab === 'purchases' ? 'আপনার কোনো কেনাকাটার অর্ডার নেই' : 'আপনার কোনো বিক্রয়ের অর্ডার নেই' }}
            </h3>
            <p style="font-size: 14px; margin-top: 6px;">
                {{ $activeTab === 'purchases' ? 'মার্কেটপ্লেস ঘুরে পছন্দের পণ্যের জন্য নিরাপদ এসক্রো অর্ডার করুন।' : 'আপনার পণ্যের বিজ্ঞাপন দিন এবং সহজে কেনাবেচা শুরু করুন।' }}
            </p>
            <div style="margin-top: 16px;">
                <a href="{{ route('marketplace.index') }}" class="btn-fb-primary" style="display: inline-flex; text-decoration: none;">
                    🛍️ মার্কেটপ্লেস ব্রাউজ করুন
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
