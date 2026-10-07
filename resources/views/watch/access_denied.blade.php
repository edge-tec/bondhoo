@extends('layouts.app')

@section('title', 'অননুমোদিত অ্যাক্সেস — Bondhoo লাইভ')

@section('content')
<div style="max-width: 600px; margin: 60px auto; padding: 32px 24px; background: var(--fb-card); border: 1px solid var(--fb-border); border-radius: 14px; text-align: center; box-shadow: var(--shadow-md);">
    <div style="font-size: 54px; margin-bottom: 16px;">🔒</div>
    <h1 style="font-size: 22px; font-weight: 800; color: var(--fb-text-primary); margin-bottom: 10px;">
        ব্যক্তিগত লাইভ সম্প্রচার (Private Live)
    </h1>
    <p style="font-size: 14px; color: var(--fb-text-secondary); line-height: 1.6; margin-bottom: 24px;">
        এই লাইভ সম্প্রচারটি শুধুমাত্র অনুমোদিত দর্শকদের জন্য উন্মুক্ত। ব্রডকাস্টার এটি ফ্রেন্ডস অথবা নির্দিষ্ট দর্শকদের জন্য সীমিত করে রেখেছেন।
    </p>

    <div style="display: flex; gap: 12px; justify-content: center;">
        <a href="{{ route('watch.index') }}" class="btn-fb-primary" style="text-decoration: none; padding: 10px 24px; border-radius: 8px;">
            ← ওয়াচ ফিডে ফিরে যান
        </a>
        <a href="{{ url('/') }}" class="btn-fb-secondary" style="text-decoration: none; padding: 10px 24px; border-radius: 8px;">
            হোম পেজ
        </a>
    </div>
</div>
@endsection
