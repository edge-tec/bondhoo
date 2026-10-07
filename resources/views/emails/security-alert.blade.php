@extends('emails.layouts.master')

@section('content')
    <div class="email-greeting">আসসালামু আলাইকুম, {{ $user->name ?: $user->username }}!</div>
    <p class="email-text">
        আপনার <strong>Bondhoo</strong> অ্যাকাউন্টে একটি গুরুত্বপূর্ণ নিরাপত্তা পরিবর্তন শনাক্ত করা হয়েছে:
    </p>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin: 20px 0;">
        <h4 style="margin: 0 0 10px 0; color: #1e3a8a; font-size: 15px;">{{ $actionTitle }}</h4>
        <p style="margin: 0 0 12px 0; font-size: 14px; color: #334155;">{{ $actionDescription }}</p>
        <table style="width: 100%; font-size: 13px; color: #475569;">
            <tr>
                <td style="padding: 4px 0; font-weight: 600; width: 120px;">তারিখ ও সময়:</td>
                <td style="padding: 4px 0;">{{ now()->setTimezone('Asia/Dhaka')->format('d M, Y h:i A') }} (BST)</td>
            </tr>
            @if(!empty($device))
            <tr>
                <td style="padding: 4px 0; font-weight: 600;">ডিভাইস:</td>
                <td style="padding: 4px 0;">{{ $device }}</td>
            </tr>
            @endif
            @if(!empty($ip))
            <tr>
                <td style="padding: 4px 0; font-weight: 600;">আইপি ঠিকানা:</td>
                <td style="padding: 4px 0; font-family: monospace;">{{ $ip }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="warning-box">
        ⚠️ যদি আপনি নিজে এই পদক্ষেপটি না নিয়ে থাকেন, তবে এখনই আপনার পাসওয়ার্ড পরিবর্তন করে অ্যাকাউন্টটি পুনরুদ্ধার করুন।
    </div>

    <div class="btn-container">
        <a href="{{ config('app.url') }}/settings/two-factor" class="btn-primary">নিরাপত্তা সেটিংস পরীক্ষা করুন &rarr;</a>
    </div>
@endsection
