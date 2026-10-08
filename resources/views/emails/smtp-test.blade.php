@extends('emails.layouts.master')

@section('content')
    <div class="email-greeting">অভিনন্দন! SMTP টেস্ট সফল হয়েছে 🎉</div>
    <p class="email-text">
        আপনার <strong>Bondhoo</strong> সোশ্যাল প্ল্যাটফর্মের SMTP ইমেইল সার্ভার সফলভাবে কনফিগার করা হয়েছে এবং কাজ করছে।
    </p>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 20px 0;">
        <h4 style="margin: 0 0 12px 0; color: #1e3a8a; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">সিস্টেম সংযোগ বিস্তারিত</h4>
        <table style="width: 100%; font-size: 13px; color: #334155;">
            <tr>
                <td style="padding: 6px 0; font-weight: 600; width: 140px;">SMTP Host:</td>
                <td style="padding: 6px 0; font-family: monospace;">{{ $smtpHost }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-weight: 600;">SMTP Port:</td>
                <td style="padding: 6px 0; font-family: monospace;">{{ $smtpPort }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-weight: 600;">Encryption:</td>
                <td style="padding: 6px 0; font-family: monospace;">{{ strtoupper($encryption) }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-weight: 600;">From Address:</td>
                <td style="padding: 6px 0; font-family: monospace;">{{ $fromAddress }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-weight: 600;">পরীক্ষার সময়:</td>
                <td style="padding: 6px 0;">{{ now()->setTimezone('Asia/Dhaka')->format('d M, Y h:i:s A') }} (BST)</td>
            </tr>
        </table>
    </div>

    <div class="security-box">
        ✓ এখন আপনার প্ল্যাটফর্ম থেকে ব্যবহারকারীদের অ্যাকাউন্ট ভেরিফিকেশন, পাসওয়ার্ড রিসেট, ফ্রেন্ড রিকোয়েস্ট এবং সিকিউরিটি নোটিফিকেশন স্বয়ংক্রিয়ভাবে পৌঁছাবে।
    </div>

    @php
        $safeUrl = config('app.url', 'https://bondhoo.com');
        if (empty($safeUrl) || str_contains($safeUrl, 'localhost') || str_contains($safeUrl, '127.0.0.1')) {
            $safeUrl = 'https://bondhoo.com';
        }
    @endphp
    <div class="btn-container">
        <a href="{{ $safeUrl }}/admin/auth-management" class="btn-primary">অ্যাডমিন কনসোলে ফিরে যান</a>
    </div>
@endsection
