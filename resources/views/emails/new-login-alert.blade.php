@extends('emails.layouts.master')

@section('content')
    <div class="email-greeting">আসসালামু আলাইকুম, {{ $user->name ?: $user->username }}!</div>
    <p class="email-text">
        আমরা লক্ষ্য করেছি যে আপনার <strong>Bondhoo</strong> অ্যাকাউন্টে একটি নতুন ডিভাইস বা ব্রাউজার থেকে সফলভাবে লগইন করা হয়েছে।
    </p>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin: 20px 0;">
        <h4 style="margin: 0 0 10px 0; color: #1e3a8a; font-size: 14px;">লগইনের বিবরণ:</h4>
        <table style="width: 100%; font-size: 13px; color: #334155;">
            <tr>
                <td style="padding: 4px 0; font-weight: 600; width: 120px;">তারিখ ও সময়:</td>
                <td style="padding: 4px 0;">{{ now()->setTimezone('Asia/Dhaka')->format('d M, Y h:i A') }} (BST)</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; font-weight: 600;">ডিভাইস:</td>
                <td style="padding: 4px 0;">{{ $device }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; font-weight: 600;">আইপি ঠিকানা:</td>
                <td style="padding: 4px 0; font-family: monospace;">{{ $ip }}</td>
            </tr>
            @if(!empty($location))
            <tr>
                <td style="padding: 4px 0; font-weight: 600;">আনুমানিক অবস্থান:</td>
                <td style="padding: 4px 0;">{{ $location }}</td>
            </tr>
            @endif
        </table>
    </div>

    <p class="email-text">
        এটি যদি আপনার নিজস্ব কার্যক্রম হয়ে থাকে, তবে কোনো পদক্ষেপের প্রয়োজন নেই।
    </p>

    <div class="warning-box">
        🚨 <strong>এটি কি আপনি নন?</strong> যদি এই লগইন আপনি না করে থাকেন, তবে অনতিবিলম্বে আপনার সব সক্রিয় সেশন বাতিল করে পাসওয়ার্ড পরিবর্তন করুন।
    </div>

    <div class="btn-container">
        <a href="{{ config('app.url') }}/devices" class="btn-primary" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">অন্য সব ডিভাইস থেকে লগআউট করুন &rarr;</a>
    </div>
@endsection
