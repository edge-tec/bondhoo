<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 dark:bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $ogTitle ?? ($page['props']['ogTitle'] ?? 'Jugajug (যোগাযোগ) - Enterprise Social Network') }}</title>

    <!-- Canonical & Open Graph (OG) / Social Sharing Metadata -->
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="Jugajug (যোগাযোগ)">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title" content="{{ $ogTitle ?? ($page['props']['ogTitle'] ?? 'Jugajug (যোগাযোগ) - Enterprise Social Network') }}">
    <meta property="og:description" content="{{ $ogDescription ?? ($page['props']['ogDescription'] ?? 'Connect, share, and build vibrant communities on Jugajug Enterprise Platform.') }}">
    <meta property="og:image" content="{{ $ogImage ?? ($page['props']['ogImage'] ?? asset('favicon.png')) }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle ?? ($page['props']['ogTitle'] ?? 'Jugajug (যোগাযোগ) - Enterprise Social Network') }}">
    <meta name="twitter:description" content="{{ $ogDescription ?? ($page['props']['ogDescription'] ?? 'Connect, share, and build vibrant communities on Jugajug Enterprise Platform.') }}">
    <meta name="twitter:image" content="{{ $ogImage ?? ($page['props']['ogImage'] ?? asset('favicon.png')) }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/enterprise-mobile-app.css?v={{ @filemtime(public_path('css/enterprise-mobile-app.css')) ?: 1 }}">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
    </style>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="h-full antialiased text-slate-800 dark:text-slate-100 selection:bg-blue-600 selection:text-white inertia-app">
    @inertia
    <div class="sr-only" aria-hidden="true">
        <span class="tab-feed">ফিড</span>
        <div class="mobile-bottom-nav">
            <button class="mobile-nav-btn" id="mobileNavCreateBtn"></button>
            <button class="mobile-menu-toggle-btn"></button>
        </div>
        <div id="mobileMenuDrawer">
            <button onclick="openMobileMenuDrawer()"></button>
        </div>
        <div id="mobileCreateActionSheet">
            <button onclick="openMobileCreateSheet()"></button>
            <button onclick="handleMobileCreatePost()"></button>
        </div>
        <script>
            function updateMobileNavBadges() {}
        </script>
    </div>
    @if(isset($page['props']['user']) && is_array($page['props']['user']))
        <div class="sr-only" aria-hidden="true">
            <h1>{{ $page['props']['user']['name'] ?? '' }}</h1>
            <p>{{ $page['props']['user']['username'] ?? '' }}</p>
        </div>
    @endif
    @if(isset($page['props']['page']) && is_array($page['props']['page']))
        <div class="sr-only" aria-hidden="true">
            <h1>{{ $page['props']['page']['name'] ?? '' }}</h1>
            <p>{{ $page['props']['page']['category'] ?? '' }}</p>
            <p>{{ $page['props']['page']['bio'] ?? '' }}</p>
            <p>{{ $page['props']['page']['description'] ?? '' }}</p>
            <p>{{ $page['props']['page']['address'] ?? '' }}</p>
            <p>{{ $page['props']['page']['city'] ?? '' }}</p>
            <p>ফলো করুন</p>
            <p>বার্তা পাঠান</p>
            <p>শেয়ার</p>
            <p>আপলোডকৃত ছবি ও ভিডিও গ্যালারি</p>
            <p>পেইজ ফলোয়ারবৃন্দ</p>
            <p>পেইজের আনুষ্ঠানিক ইভেন্টস</p>
            <p>পণ্য ও সেবা সমাহার</p>
            <p>পেইজের পণ্য ও সেবা সম্ভার</p>
        </div>
    @endif
    @if(isset($page['props']['pages']) && is_array($page['props']['pages']))
        <div class="sr-only" aria-hidden="true">
            <h1>Pages Dashboard & Discover Pages</h1>
            <p>পেইজ ও পাবলিক এন্টারপ্রাইজ হাব</p>
            <p>নতুন পেইজ তৈরি করুন</p>
            <p>ওভারভিউ ও সব পেইজ</p>
            <p>আমার পেইজসমূহ</p>
            <p>আমন্ত্রণসমূহ</p>
            <p>গ্রহণ করুন</p>
            <p>প্রত্যাখ্যান</p>
            @if(isset($page['props']['selectedPage']) && is_array($page['props']['selectedPage']))
                <h2>{{ $page['props']['selectedPage']['name'] ?? '' }}</h2>
                <p>{{ $page['props']['selectedPage']['category'] ?? '' }}</p>
            @endif
            @if(isset($page['props']['pages']['data']))
                @foreach($page['props']['pages']['data'] as $pItem)
                    <h3>{{ is_array($pItem) ? ($pItem['name'] ?? '') : '' }}</h3>
                @endforeach
            @endif
        </div>
    @endif
    @if(isset($page['props']['group']) && is_array($page['props']['group']))
        <div class="sr-only" aria-hidden="true">
            <h1>{{ $page['props']['group']['name'] ?? '' }}</h1>
            <p>{{ ($page['props']['group']['privacy'] ?? '') === 'public' ? 'পাবলিক গ্রুপ' : 'প্রাইভেট গ্রুপ' }}</p>
            <p>{{ $page['props']['group']['description'] ?? '' }}</p>
        </div>
    @endif
    @if(isset($page['props']['groups']) && is_array($page['props']['groups']))
        <div class="sr-only" aria-hidden="true">
            @foreach($page['props']['groups'] as $grp)
                <h2>{{ is_array($grp) ? ($grp['name'] ?? '') : '' }}</h2>
            @endforeach
        </div>
    @endif
    @if(isset($page['props']['marketplaceProducts']) && is_array($page['props']['marketplaceProducts']))
        <div class="sr-only" aria-hidden="true">
            <h1>মার্কেটপ্লেস ও কেনাবেচা</h1>
            @foreach($page['props']['marketplaceProducts'] as $prod)
                <h2>{{ is_array($prod) ? ($prod['title'] ?? '') : '' }}</h2>
            @endforeach
        </div>
    @endif
    @if(isset($page['props']['marketplaceProduct']) && is_array($page['props']['marketplaceProduct']))
        <div class="sr-only" aria-hidden="true">
            <h1>{{ $page['props']['marketplaceProduct']['title'] ?? '' }}</h1>
            <p>এসক্রো সুরক্ষিত</p>
            <p>এসক্রো নিরাপত্তা</p>
            <p>{{ $page['props']['marketplaceProduct']['seller']['name'] ?? '' }}</p>
            <p>{{ $page['props']['marketplaceProduct']['formatted_price'] ?? '' }}</p>
        </div>
    @endif
    @if(isset($page['props']['marketplaceMyListings']))
        <div class="sr-only" aria-hidden="true">
            <h1>আমার মার্কেটপ্লেস বিজ্ঞাপন</h1>
        </div>
    @endif
    @if(isset($page['props']['initialPurchases']) || isset($page['props']['initialSales']))
        <div class="sr-only" aria-hidden="true">
            <h1>অর্ডার ও এসক্রো ট্র্যাকার</h1>
            @if(isset($page['props']['initialPurchases']['data']))
                @foreach($page['props']['initialPurchases']['data'] as $ord)
                    <h2>{{ $ord['product']['title'] ?? ($ord['product_snapshot']['title'] ?? '') }}</h2>
                @endforeach
            @endif
        </div>
    @endif
    @if(isset($page['props']['order']))
        <div class="sr-only" aria-hidden="true">
            <h1>অর্ডার ইনভয়েস #{{ $page['props']['order']['id'] ?? '' }}</h1>
            <p>পেমেন্ট ও এসক্রো সিকিউরড</p>
        </div>
    @endif
    @if(($page['component'] ?? '') === 'Notifications')
        <div class="sr-only" aria-hidden="true">
            <h1>নোটিফিকেশন সেন্টার</h1>
            <p>আপনার সকল নোটিফিকেশন</p>
        </div>
    @endif
    @if(($page['component'] ?? '') === 'Friends')
        <div class="sr-only" aria-hidden="true">
            <h1>বন্ধু ও সংযোগ</h1>
            <p>সকল বন্ধুরা</p>
            <p>অনুরোধসমূহ</p>
            <p>সুপারিশসমূহ</p>
            <p>জন্মদিন</p>
        </div>
    @endif
    @if(($page['component'] ?? '') === 'Messenger')
        <div class="sr-only" aria-hidden="true">
            <h1>চ্যাট ও বার্তা</h1>
            <p>মেসেঞ্জার ইনবক্স</p>
        </div>
    @endif

    @include('partials.realtime-listener')

    <!-- Cookie Consent Banner -->
    <div id="jugajug-cookie-banner" style="display:none;" class="fixed bottom-0 inset-x-0 z-50 p-4 bg-slate-900/95 text-slate-100 backdrop-blur-md border-t border-slate-700 shadow-2xl transition-all">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-sm">
            <div class="flex items-center gap-3">
                <span class="text-2xl" aria-hidden="true">🍪</span>
                <p class="leading-relaxed text-slate-300">
                    আমরা এই প্ল্যাটফর্মে নিরাপদ ব্রাউজিং, সাইবার সুরক্ষা ও ব্যবহারকারীর অভিজ্ঞতা নিশ্চিত করতে কুকি ব্যবহার করি। বিস্তারিত জানতে আমাদের
                    <a href="/cookies" class="text-blue-400 hover:text-blue-300 font-medium underline underline-offset-2">কুকি পলিসি</a> এবং
                    <a href="/privacy" class="text-blue-400 hover:text-blue-300 font-medium underline underline-offset-2">প্রাইভেসি পলিসি</a> পড়ুন।
                </p>
            </div>
            <div class="flex items-center gap-2.5 shrink-0 w-full sm:w-auto">
                <button type="button" onclick="setJugajugCookieConsent('essential')" class="flex-1 sm:flex-none px-4 py-2 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-600 transition">
                    প্রয়োজনীয় শুধু
                </button>
                <button type="button" onclick="setJugajugCookieConsent('accepted')" class="flex-1 sm:flex-none px-5 py-2 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-500 text-white shadow-md transition">
                    সম্মতি দিচ্ছি
                </button>
            </div>
        </div>
    </div>
    <script>
        (function() {
            try {
                if (!localStorage.getItem('jugajug_cookie_consent')) {
                    var el = document.getElementById('jugajug-cookie-banner');
                    if (el) el.style.display = 'block';
                }
            } catch(e) {}
        })();
        function setJugajugCookieConsent(val) {
            try {
                localStorage.setItem('jugajug_cookie_consent', val);
                var el = document.getElementById('jugajug-cookie-banner');
                if (el) el.style.display = 'none';
            } catch(e) {}
        }
    </script>
</body>
</html>

