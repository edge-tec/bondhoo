@extends('emails.layouts.master')

@section('content')
    <div class="email-greeting">আসসালামু আলাইকুম, {{ $recipient->name ?: $recipient->username }}!</div>
    <p class="email-text">
        <strong>{{ $sender->name ?: $sender->username }}</strong> (@ {{ $sender->username }}) আপনাকে <strong>Bondhoo</strong> সোশ্যাল প্ল্যাটফর্মে ফ্রেন্ড রিকোয়েস্ট পাঠিয়েছেন।
    </p>

    <!-- Sender Profile Card -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; text-align: center; margin: 24px 0;">
        @if(!empty($senderAvatarUrl))
            <img src="{{ $senderAvatarUrl }}" alt="{{ $sender->name }}" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; margin-bottom: 12px; border: 3px solid #3b82f6;">
        @else
            <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #6366f1); color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700; margin-bottom: 12px;">
                {{ mb_substr($sender->name ?: $sender->username, 0, 1) }}
            </div>
        @endif
        <h3 style="margin: 0; font-size: 18px; color: #0f172a;">{{ $sender->name ?: $sender->username }}</h3>
        <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">{{ '@' . $sender->username }}</p>
        @if($sender->profile && !empty($sender->profile->bio))
            <p style="margin: 10px 0 0 0; font-size: 13px; color: #475569; font-style: italic;">
                "{{ Str::limit($sender->profile->bio, 90) }}"
            </p>
        @endif
    </div>

    <div class="btn-container">
        <a href="{{ $requestUrl }}" class="btn-primary">রিকোয়েস্ট দেখুন ও গ্রহণ করুন &rarr;</a>
    </div>

    <p style="font-size: 12px; color: #94a3b8; text-align: center; margin-top: 24px;">
        আপনি এই ধরণের ইমেইল পেতে না চাইলে <a href="{{ config('app.url') }}/settings/notifications" style="color: #64748b;">নোটিফিকেশন সেটিংসে</a> গিয়ে পরিবর্তন করতে পারেন।
    </p>
@endsection
