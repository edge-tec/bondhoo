@extends('layouts.app')

@section('title', 'আমার বিজ্ঞাপনসমূহ — মার্কেটপ্লেস')

@section('styles')
<style>
    .listings-container {
        max-width: 960px;
        margin: 0 auto 40px auto;
    }

    .listings-header {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
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

    .listing-card {
        background: var(--fb-card);
        border-radius: 12px;
        border: 1px solid var(--fb-border);
        box-shadow: var(--shadow-sm);
        margin-bottom: 16px;
        padding: 18px;
        display: grid;
        grid-template-columns: 80px 1fr auto;
        gap: 16px;
        align-items: center;
    }

    .listing-thumb {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        overflow: hidden;
    }

    .listing-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .badge-status-listing {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .status-active-badge { background: #d1fae5; color: #065f46; }
    .status-sold-badge { background: #f1f5f9; color: #475569; }
    .status-archived-badge { background: #fee2e2; color: #991b1b; }

    @media (max-width: 640px) {
        .listing-card {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="listings-container">
    <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('marketplace.index') }}" class="btn-fb-secondary">← মার্কেটপ্লেসে ফিরে যান</a>
        <a href="{{ route('marketplace.orders', ['tab' => 'sales']) }}" class="btn-fb-secondary">
            📦 ইনকামিং বিক্রয় অর্ডারসমূহ
        </a>
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

    <div class="listings-header">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; margin-bottom: 4px;">🏷️ আমার মার্কেটপ্লেস বিজ্ঞাপন</h1>
            <p style="font-size: 14px; opacity: 0.95;">আপনার পণ্যসমূহ পরিচালনা করুন, বিক্রি হলে স্ট্যাটাস পরিবর্তন করুন।</p>
        </div>
        <div>
            <a href="{{ route('marketplace.index') }}" class="btn-fb-primary" style="background: white; color: #1d4ed8; font-weight: 700; text-decoration: none;">
                ➕ নতুন বিজ্ঞাপন দিন
            </a>
        </div>
    </div>

    @if($listings->isNotEmpty())
        @foreach($listings as $item)
            @php
                $img = $item->images && count($item->images) > 0 ? $item->images[0] : null;
            @endphp
            <div class="listing-card">
                <div class="listing-thumb">
                    @if($img)
                        <img src="{{ $img }}" alt="{{ $item->title }}">
                    @else
                        🛍️
                    @endif
                </div>

                <div>
                    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px;">
                        <a href="{{ route('marketplace.show', ['id' => $item->id]) }}" style="font-weight: 800; font-size: 16px; color: var(--fb-text-primary); text-decoration: none;">
                            {{ $item->title }}
                        </a>
                        <span class="badge-status-listing status-{{ $item->status }}-badge">
                            {{ match($item->status) {
                                'active' => 'সক্রিয় (Active)',
                                'sold' => 'বিক্রিত (Sold)',
                                default => $item->status
                            } }}
                        </span>
                    </div>

                    <div style="font-size: 15px; font-weight: 800; color: var(--fb-primary);">
                        ৳ {{ number_format($item->price) }}
                    </div>

                    <div style="font-size: 12px; color: var(--fb-text-secondary); margin-top: 4px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <span>📂 ক্যাটাগরি: {{ $item->category?->name ?? 'সাধারণ' }}</span>
                        <span>👁️ ভিউ: {{ number_format($item->views_count) }} বার</span>
                        <span>📦 মোট অর্ডার: {{ number_format($item->orders_count ?? 0) }} টি</span>
                        <span>🗓️ পোস্ট: {{ $item->created_at ? $item->created_at->diffForHumans() : 'সম্প্রতি' }}</span>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; min-width: 140px;">
                    <a href="{{ route('marketplace.show', ['id' => $item->id]) }}" class="btn-fb-secondary" style="font-size: 12px; text-decoration: none; justify-content: center;">
                        👁️ বিজ্ঞাপন দেখুন
                    </a>

                    @if($item->status === 'active')
                        <form action="{{ route('marketplace.products.status', ['id' => $item->id]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="sold">
                            <button type="submit" class="btn-fb-secondary" style="font-size: 12px; width: 100%; justify-content: center;">
                                🏷️ বিক্রিত মার্ক করুন
                            </button>
                        </form>
                    @else
                        <form action="{{ route('marketplace.products.status', ['id' => $item->id]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="active">
                            <button type="submit" class="btn-fb-secondary" style="font-size: 12px; width: 100%; justify-content: center;">
                                🔄 পুনরায় সক্রিয় করুন
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('marketplace.products.destroy', ['id' => $item->id]) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই বিজ্ঞাপনটি ডিলিট করতে চান?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="background: none; border: none; color: #ef4444; font-size: 12px; font-weight: 700; cursor: pointer; padding: 4px 8px; width: 100%; text-align: center;">
                            🗑️ মুছে ফেলুন
                        </button>
                    </form>
                </div>
            </div>
        @endforeach

        <div style="margin-top: 20px;">
            {{ $listings->links() }}
        </div>
    @else
        <div class="fb-card" style="text-align: center; padding: 50px 20px; color: var(--fb-text-secondary);">
            <div style="font-size: 48px; margin-bottom: 12px;">🏷️</div>
            <h3 style="font-size: 18px; font-weight: 800; color: var(--fb-text-primary);">আপনার কোনো সক্রিয় বিজ্ঞাপন নেই</h3>
            <p style="font-size: 14px; margin-top: 6px;">আপনার ব্যবহৃত বা নতুন পণ্যের বিজ্ঞাপন দিন এবং দ্রুত ক্রেতা খুঁজে নিন।</p>
            <div style="margin-top: 16px;">
                <a href="{{ route('marketplace.index') }}" class="btn-fb-primary" style="display: inline-flex; text-decoration: none;">
                    ➕ নতুন বিজ্ঞাপন দিন
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
