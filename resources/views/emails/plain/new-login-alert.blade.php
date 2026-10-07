নিরাপত্তা সতর্কতা: নতুন ডিভাইস থেকে লগইন শনাক্ত হয়েছে — Bondhoo

আসসালামু আলাইকুম, {{ $user->name ?: $user->username }}!
আপনার অ্যাকাউন্টে একটি নতুন ডিভাইস থেকে লগইন করা হয়েছে:
তারিখ ও সময়: {{ now()->setTimezone('Asia/Dhaka')->format('d M, Y h:i A') }}
ডিভাইস: {{ $device }}
আইপি: {{ $ip }}

যদি এটি আপনি না হন, তবে অবিলম্বে সেশন বাতিল করুন:
{{ config('app.url') }}/devices

(C) {{ date('Y') }} Bondhoo Platform.
