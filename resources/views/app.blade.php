<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 dark:bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SocialHub - Enterprise Social Network</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/enterprise-mobile-app.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="h-full antialiased text-slate-800 dark:text-slate-100 selection:bg-blue-600 selection:text-white">
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
</body>
</html>

