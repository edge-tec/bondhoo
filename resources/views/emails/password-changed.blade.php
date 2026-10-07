@extends('emails.layouts.master')

@section('content')
    <div class="email-greeting">আসসালামু আলাইকুম, {{ $user->name ?: $user->username }}!</div>
    <p class="email-text">
        আমরা আপনাকে জানাচ্ছি যে আপনার <strong>Bondhoo</strong> অ্যাকাউন্টের পাসওয়ার্ড সম্প্রতি পরিবর্তন করা হয়েছে।
    </p>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin: 20px 0;">
        <h4 style="margin: 0 0 10px 0; color: #1e3a8a; font-size: 14px;">পরিবর্তনের বিস্তারিত:</h4>
        <table style="width: 100%; font-size: 13px; color: #334155;">
            <tr>
                <td style="padding: 4px 0; font-weight: 600; width: 120px;">তারিখ ও সময়:</td>
                <td style="padding: 4px 0;">{{ now()->setTimezone('Asia/Dhaka')->format('d M, Y h:i A') }} (BST)</td>
            </tr>
            @if(!empty($device))
            <tr>
                <td style="padding: 4px 0; font-weight: 600;">ডিভাইস/ব্রাউজার:</td>
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
        ⚠️ <strong>এটি কি আপনি করেননি?</strong> যদি আপনি এই পরিবর্তন না করে থাকেন, তবে দ্রুত আপনার অ্যাকাউন্ট রিকভার করতে এবং পাসওয়ার্ড পুনরায় পরিবর্তন করতে নিচের বাটনে ক্লিক করুন।
    </div>

    <div class="btn-container">
        <a href="{{ config('app.url') }}/forgot-password" class="btn-primary" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">অ্যাকাউন্ট সুরক্ষিত করুন &rarr;</a>
    </div>
@endsection
