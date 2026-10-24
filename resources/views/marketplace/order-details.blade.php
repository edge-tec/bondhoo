@extends('layouts.app')

@section('title', 'অর্ডার ইনভয়েস #' . $order->id . ' — মার্কেটপ্লেস')

@section('styles')
<style>
    .invoice-container {
        max-width: 800px;
        margin: 0 auto 40px auto;
    }

    .timeline {
        display: flex;
        justify-content: space-between;
        position: relative;
        margin: 24px 0 32px 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        top: 14px;
        left: 20px;
        right: 20px;
        height: 3px;
        background: var(--fb-border);
        z-index: 1;
    }

    .timeline-step {
        position: relative;
        z-index: 2;
        text-align: center;
        flex: 1;
    }

    .timeline-dot {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--fb-card);
        border: 3px solid var(--fb-border);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px auto;
        font-size: 13px;
        font-weight: 800;
    }

    .timeline-step.completed .timeline-dot {
        background: #10b981;
        border-color: #10b981;
        color: white;
    }

    .timeline-step.active .timeline-dot {
        background: var(--fb-primary);
        border-color: var(--fb-primary);
        color: white;
    }

    .timeline-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--fb-text-secondary);
    }

    .timeline-step.completed .timeline-label,
    .timeline-step.active .timeline-label {
        color: var(--fb-text-primary);
    }

    @media (max-width: 600px) {
        .timeline {
            flex-direction: column;
            gap: 16px;
            margin-left: 20px;
        }
        .timeline::before {
            top: 0;
            bottom: 0;
            left: 15px;
            width: 3px;
            height: auto;
            right: auto;
        }
        .timeline-step {
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
        }
        .timeline-dot {
            margin: 0;
        }
    }
</style>
@endsection

@section('content')
<div class="invoice-container">
    <div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('marketplace.orders') }}" class="btn-fb-secondary">← সকল অর্ডারে ফিরে যান</a>
        <button onclick="window.print()" class="btn-fb-secondary">🖨️ ইনভয়েস প্রিন্ট</button>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600;">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="fb-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--fb-border); padding-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 style="font-size: 20px; font-weight: 800; margin: 0;">অর্ডার ইনভয়েস #{{ $order->id }}</h1>
                <div style="font-size: 13px; color: var(--fb-text-secondary); margin-top: 4px;">
                    তারিখ: {{ $order->created_at ? $order->created_at->format('d F, Y h:i A') : 'সম্প্রতি' }}
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: var(--fb-text-secondary);">এসক্রো স্ট্যাটাস</div>
                <div style="font-size: 14px; font-weight: 800; color: {{ $order->escrow_status === 'released' ? '#047857' : '#065f46' }};">
                    🛡️ {{ $order->escrow_status === 'released' ? 'ফান্ড রিলিজড (Released)' : 'ফান্ড এসক্রো তহবিলে জমা (Held)' }}
                </div>
            </div>
        </div>

        <!-- Order Lifecycle Timeline -->
        <div class="timeline">
            @php
                $isProcessing = in_array($order->status, ['processing', 'shipped', 'delivered']);
                $isShipped = in_array($order->status, ['shipped', 'delivered']);
                $isDelivered = $order->status === 'delivered';
            @endphp
            <div class="timeline-step completed">
                <div class="timeline-dot">✓</div>
                <div class="timeline-label">অর্ডার প্লেসড</div>
            </div>
            <div class="timeline-step {{ $isProcessing ? 'completed' : 'active' }}">
                <div class="timeline-dot">{{ $isProcessing ? '✓' : '২' }}</div>
                <div class="timeline-label">পেমেন্ট ও এসক্রো সিকিউরড</div>
            </div>
            <div class="timeline-step {{ $isShipped ? 'completed' : ($order->status === 'processing' ? 'active' : '') }}">
                <div class="timeline-dot">{{ $isShipped ? '✓' : '৩' }}</div>
                <div class="timeline-label">শিপিং ও ট্রানজিট</div>
            </div>
            <div class="timeline-step {{ $isDelivered ? 'completed' : ($order->status === 'shipped' ? 'active' : '') }}">
                <div class="timeline-dot">{{ $isDelivered ? '✓' : '৪' }}</div>
                <div class="timeline-label">ডেলিভারি ও এসক্রো রিলিজ</div>
            </div>
        </div>

        <!-- Counterparties Box -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: var(--fb-bg); padding: 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--fb-border);">
            <div>
                <h4 style="font-size: 12px; font-weight: 800; color: var(--fb-text-secondary); text-transform: uppercase; margin-bottom: 8px;">ক্রেতার বিবরণ</h4>
                <div style="font-weight: 700; font-size: 14px;">{{ $order->buyer?->name ?? 'ক্রেতা' }}</div>
                <div style="font-size: 13px; color: var(--fb-text-secondary); margin-top: 4px;">
                    📍 শিপিং ঠিকানা: {{ $order->shipping_address ?: 'ঠিকানা দেওয়া হয়নি' }}
                </div>
            </div>
            <div>
                <h4 style="font-size: 12px; font-weight: 800; color: var(--fb-text-secondary); text-transform: uppercase; margin-bottom: 8px;">বিক্রেতার বিবরণ</h4>
                <div style="font-weight: 700; font-size: 14px;">{{ $order->seller?->name ?? 'সেলার' }}</div>
                <div style="font-size: 13px; color: var(--fb-text-secondary); margin-top: 4px;">
                    লোকেশন: {{ $order->product?->location ?: 'বাংলাদেশ' }}
                </div>
            </div>
        </div>

        <!-- Product Table -->
        <div style="border: 1px solid var(--fb-border); border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background: var(--fb-bg); border-bottom: 1px solid var(--fb-border); text-align: left;">
                        <th style="padding: 12px 16px;">পণ্য</th>
                        <th style="padding: 12px 16px; text-align: center;">পরিমাণ</th>
                        <th style="padding: 12px 16px; text-align: right;">মূল্য</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 14px 16px; border-bottom: 1px solid var(--fb-border);">
                            <div style="font-weight: 700;">{{ $order->product?->title ?? 'পণ্য' }}</div>
                            <div style="font-size: 12px; color: var(--fb-text-secondary);">
                                ক্যাটাগরি: {{ $order->product?->category?->name ?? 'সাধারণ' }} • অবস্থা: {{ $order->product?->condition === 'new' ? 'নতুন' : 'ব্যবহৃত' }}
                            </div>
                        </td>
                        <td style="padding: 14px 16px; border-bottom: 1px solid var(--fb-border); text-align: center;">১</td>
                        <td style="padding: 14px 16px; border-bottom: 1px solid var(--fb-border); text-align: right; font-weight: 700;">
                            ৳ {{ number_format($order->amount) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr style="background: var(--fb-bg); font-weight: 800; font-size: 15px;">
                        <td colspan="2" style="padding: 12px 16px;">সর্বমোট পরিশোধিত (এসক্রো):</td>
                        <td style="padding: 12px 16px; text-align: right; color: var(--fb-primary);">
                            ৳ {{ number_format($order->amount) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Action Section -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; align-items: center; border-top: 1px solid var(--fb-border); padding-top: 16px;">
            @if($isBuyer && $order->escrow_status === 'held')
                <form action="{{ route('marketplace.orders.release-escrow', ['id' => $order->id]) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে পণ্য হাতে পেয়ে সন্তুষ্ট হয়েছেন এবং এসক্রো ফান্ড ছাড়তে চান?');">
                    @csrf
                    <button type="submit" class="btn-fb-primary" style="background: #10b981; border-color: #10b981;">
                        ✅ পণ্য পেয়েছি — এসক্রো ফান্ড রিলিজ করুন
                    </button>
                </form>
            @endif

            <a href="/messages" class="btn-fb-secondary">
                💬 মেসেঞ্জারে চ্যাট করুন
            </a>
        </div>
    </div>
</div>
@endsection
