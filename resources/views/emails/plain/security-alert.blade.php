নিরাপত্তা বিজ্ঞপ্তি: {{ $actionTitle }} — Bondhoo

আসসালামু আলাইকুম, {{ $user->name ?: $user->username }}!
{{ $actionDescription }}

তারিখ ও সময়: {{ now()->setTimezone('Asia/Dhaka')->format('d M, Y h:i A') }}
ডিভাইস: {{ $device ?? 'অজানা' }}
আইপি: {{ $ip ?? 'অজানা' }}

নিরাপত্তা সেটিংস পরীক্ষা করতে প্রবেশ করুন:
{{ config('app.url') }}/settings/two-factor

(C) {{ date('Y') }} Bondhoo Platform.
