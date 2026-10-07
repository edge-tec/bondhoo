@extends('emails.layouts.master')

@section('content')
    <div class="email-greeting">আসসালামু আলাইকুম, {{ $recipient->name ?: $recipient->username }}!</div>

    @if(!empty($actorName))
        <div style="display: flex; align-items: center; gap: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin: 18px 0;">
            @if(!empty($actorAvatarUrl))
                <img src="{{ $actorAvatarUrl }}" alt="{{ $actorName }}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
            @else
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #3b82f6; color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700;">
                    {{ mb_substr($actorName, 0, 1) }}
                </div>
            @endif
            <div>
                <strong style="font-size: 15px; color: #0f172a;">{{ $actorName }}</strong>
                <p style="margin: 2px 0 0 0; font-size: 13px; color: #475569;">{{ $notificationMessage }}</p>
            </div>
        </div>
    @else
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin: 18px 0;">
            <p style="margin: 0; font-size: 14px; color: #334155;">{{ $notificationMessage }}</p>
        </div>
    @endif

    <div class="btn-container">
        <a href="{{ $actionUrl }}" class="btn-primary">{{ $actionButtonText }} &rarr;</a>
    </div>

    <p style="font-size: 12px; color: #94a3b8; text-align: center; margin-top: 24px;">
        আপনি এই ধরণের ইমেইল পেতে না চাইলে <a href="{{ config('app.url') }}/settings/notifications" style="color: #64748b;">নোটিফিকেশন সেটিংসে</a> গিয়ে পরিবর্তন করতে পারেন।
    </p>
@endsection
