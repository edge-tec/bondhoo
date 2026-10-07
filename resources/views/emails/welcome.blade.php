@extends('emails.layouts.master')

@section('content')
    <div class="email-greeting">অভিনন্দন, {{ $user->name ?: $user->username }}! 🎉</div>
    <p class="email-text">
        আপনার <strong>Bondhoo</strong> অ্যাকাউন্ট সফলভাবে যাচাই ও সক্রিয় হয়েছে। এখন আপনি সম্পূর্ণ স্বাধীনতা ও নিরাপত্তার সাথে আপনার জীবনের বিশেষ মুহূর্তগুলো বন্ধুদের সাথে ভাগ করে নিতে পারেন।
    </p>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 24px 0;">
        <h4 style="margin: 0 0 12px 0; color: #1e3a8a; font-size: 15px;">আপনার যা জানা প্রয়োজন:</h4>
        <ul style="margin: 0; padding-left: 20px; color: #334155; font-size: 14px; line-height: 1.8;">
            <li><strong>প্রোফাইল সাজান:</strong> প্রোফাইল ছবি ও তথ্য আপডেট করে নিজের পরিচিতি তুলে ধরুন।</li>
            <li><strong>বন্ধু খুঁজুন:</strong> আপনার প্রিয়জন ও পরিচিত মানুষদের সাথে যুক্ত হোন।</li>
            <li><strong>নিরাপদ যোগাযোগ:</strong> ভয়েস/ভিডিও কল ও এন্ড-টু-এন্ড এনক্রিপ্টেড মেসেজিং উপভোগ করুন।</li>
            <li><strong>রিলস ও স্টোরিজ:</strong> ছোট ভিডিও ও মুহূর্ত শেয়ার করে আনন্দ ছড়িয়ে দিন।</li>
        </ul>
    </div>

    <div class="btn-container">
        <a href="{{ config('app.url') }}/dashboard" class="btn-primary">ড্যাশবোর্ডে প্রবেশ করুন &rarr;</a>
    </div>

    <div class="security-box">
        💡 <strong>পরামর্শ:</strong> আপনার অ্যাকাউন্টের নিরাপত্তা আরও জোরদার করতে সেটিংস থেকে টু-ফ্যাক্টর অথেনটিকেশন (2FA) চালু করার পরামর্শ দেওয়া হচ্ছে।
    </div>
@endsection
