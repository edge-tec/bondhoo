@extends('emails.layouts.master')

@section('content')
    <div class="email-greeting">আসসালামু আলাইকুম, {{ $recipient->name ?: $recipient->username }}!</div>
    <p class="email-text">
        আনন্দময় সংবাদ! <strong>{{ $friend->name ?: $friend->username }}</strong> (@ {{ $friend->username }}) আপনার পাঠানো ফ্রেন্ড রিকোয়েস্ট গ্রহণ করেছেন। আপনারা এখন <strong>Bondhoo</strong>-তে পরস্পরের বন্ধু।
    </p>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; text-align: center; margin: 24px 0;">
        @if(!empty($friendAvatarUrl))
            <img src="{{ $friendAvatarUrl }}" alt="{{ $friend->name }}" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; margin-bottom: 12px; border: 3px solid #10b981;">
        @else
            <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700; margin-bottom: 12px;">
                {{ mb_substr($friend->name ?: $friend->username, 0, 1) }}
            </div>
        @endif
        <h3 style="margin: 0; font-size: 18px; color: #0f172a;">{{ $friend->name ?: $friend->username }}</h3>
        <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">{{ '@' . $friend->username }}</p>
    </div>

    <div class="btn-container">
        <a href="{{ $profileUrl }}" class="btn-primary">বন্ধুর প্রোফাইল দেখুন ও মেসেজ দিন &rarr;</a>
    </div>

    <p style="font-size: 12px; color: #94a3b8; text-align: center; margin-top: 24px;">
        আপনি এই ধরণের ইমেইল পেতে না চাইলে <a href="{{ config('app.url') }}/settings/notifications" style="color: #64748b;">নোটিফিকেশন সেটিংসে</a> গিয়ে পরিবর্তন করতে পারেন।
    </p>
@endsection
