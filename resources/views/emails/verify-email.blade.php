@extends('emails.layouts.master')

@section('content')
    <div class="email-greeting">আসসালামু আলাইকুম, {{ $user->name ?: $user->username }}!</div>
    <p class="email-text">
        <strong>Bondhoo</strong> সোশ্যাল প্ল্যাটফর্মে আপনাকে আন্তরিক স্বাগতম। আপনার অ্যাকাউন্ট সম্পূর্ণ সক্রিয় করতে এবং নিরাপত্তা নিশ্চিত করতে অনুগ্রহ করে নিচের যেকোনো একটি পদ্ধতিতে ইমেইল ঠিকানাটি যাচাই করুন:
    </p>

    <!-- Method 1: 6-Digit OTP -->
    <div class="otp-box">
        <div style="font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;">৬ সংখ্যার ওটিপি (OTP) কোড</div>
        <div class="otp-code">{{ $otp }}</div>
        <div class="otp-hint">এই কোডটি আগামী {{ $expiresMinutes }} মিনিট পর্যন্ত সক্রিয় থাকবে।</div>
    </div>

    <!-- Method 2: 1-Click Verification Button -->
    <div class="btn-container">
        <a href="{{ $verificationUrl }}" class="btn-primary">ইমেইল যাচাই ও সক্রিয় করুন &rarr;</a>
    </div>

    <div class="security-box">
        🔒 <strong>নিরাপত্তা সতর্কতা:</strong> এই ওটিপি কোড বা লিংকটি কখনো কারো সাথে শেয়ার করবেন না। আপনি যদি এই অ্যাকাউন্টটি না খুলে থাকেন, তবে এই ইমেইলটি উপেক্ষা করুন।
    </div>

    <div class="fallback-box">
        <strong>বাটন কাজ না করলে নিচের লিংকটি ব্রাউজারে কপি-পেস্ট করুন:</strong><br>
        <a href="{{ $verificationUrl }}" style="color: #2563eb;">{{ $verificationUrl }}</a>
    </div>
@endsection
