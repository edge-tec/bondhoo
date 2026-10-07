@extends('emails.layouts.master')

@section('content')
    <div class="email-greeting">আসসালামু আলাইকুম, {{ $user->name ?: $user->username }}!</div>
    <p class="email-text">
        আমরা আপনার <strong>Bondhoo</strong> অ্যাকাউন্টের জন্য একটি পাসওয়ার্ড রিসেট অনুরোধ পেয়েছি। আপনি যদি এই অনুরোধ করে থাকেন, তবে নিচের বাটনে ক্লিক করে নতুন পাসওয়ার্ড সেট করুন:
    </p>

    <div class="btn-container">
        <a href="{{ $resetUrl }}" class="btn-primary">পাসওয়ার্ড রিসেট করুন &rarr;</a>
    </div>

    @if(!empty($otp))
    <div class="otp-box">
        <div style="font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;">অথবা রিসেট ওটিপি (OTP) কোড দিন</div>
        <div class="otp-code">{{ $otp }}</div>
        <div class="otp-hint">এই কোডটি আগামী {{ $expiresMinutes }} মিনিট কার্যকর থাকবে।</div>
    </div>
    @endif

    <div class="warning-box">
        ⚠️ <strong>জরুরি নিরাপত্তা বিজ্ঞপ্তি:</strong> এই পাসওয়ার্ড রিসেট লিংকটি মাত্র <strong>{{ $expiresMinutes }} মিনিট</strong> কার্যকর থাকবে এবং শুধুমাত্র <strong>একবারই ব্যবহারযোগ্য</strong>। আপনি যদি এই অনুরোধ না করে থাকেন, তবে অবিলম্বে এই ইমেইলটি এড়িয়ে চলুন এবং আপনার বর্তমান পাসওয়ার্ড নিরাপদ রাখুন।
    </div>

    <div class="fallback-box">
        <strong>বাটন কাজ না করলে নিচের লিংকটি ব্রাউজারে পেস্ট করুন:</strong><br>
        <a href="{{ $resetUrl }}" style="color: #2563eb;">{{ $resetUrl }}</a>
    </div>
@endsection
