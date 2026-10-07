Bondhoo Enterprise SMTP কনফিগারেশন টেস্ট

অভিনন্দন! আপনার Bondhoo সোশ্যাল প্ল্যাটফর্মের SMTP ইমেইল সার্ভার সফলভাবে কনফিগার করা হয়েছে।

সংযোগ বিস্তারিত:
- SMTP Host: {{ $smtpHost }}
- SMTP Port: {{ $smtpPort }}
- Encryption: {{ strtoupper($encryption) }}
- From Address: {{ $fromAddress }}
- সময়: {{ now()->setTimezone('Asia/Dhaka')->format('d M, Y h:i:s A') }}

(C) {{ date('Y') }} Bondhoo Platform. সর্বস্বত্ব সংরক্ষিত।
