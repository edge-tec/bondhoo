<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>সন্দেহজনক লগইন সতর্কতা — Bondhoo</title>
    <meta name="description" content="Bondhoo অ্যাকাউন্টে সন্দেহজনক লগইন কার্যকলাপ শনাক্ত হয়েছে।">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="apple-touch-icon" href="/images/bondhoo-icon-192.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bh-bg: #f8fafc;
            --bh-card: #ffffff;
            --bh-primary: #4f46e5;
            --bh-gradient: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            --bh-text-primary: #0f172a;
            --bh-text-secondary: #64748b;
            --bh-border: #e2e8f0;
            --bh-yellow: #f59e0b;
            --bh-red: #ef4444;
            --shadow-card: 0 20px 40px -15px rgba(99, 102, 241, 0.12), 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Hind Siliguri', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        body {
            background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .brand-header {
            margin-bottom: 24px;
            text-align: center;
        }
        .logo-img {
            height: 48px;
            width: auto;
            max-width: 220px;
            object-fit: contain;
            display: block;
        }
        .card {
            width: 100%;
            max-width: 480px;
            background: var(--bh-card);
            border-radius: 16px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--bh-border);
            padding: 32px 28px;
            text-align: center;
        }
        .icon-warn {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            background: #fffbeb;
            color: var(--bh-yellow);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        .title { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .desc { font-size: 14px; color: var(--bh-text-secondary); line-height: 1.5; margin-bottom: 20px; }
        .info-card {
            background: #fffbeb;
            border: 1px solid #fde68a;
            padding: 14px 16px;
            border-radius: 10px;
            text-align: left;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 22px;
            color: #92400e;
        }
        .btn-group { display: flex; gap: 12px; }
        .btn-confirm {
            flex: 1;
            height: 46px;
            background: var(--bh-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
            transition: all 0.2s;
        }
        .btn-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
        }
        .btn-deny {
            flex: 1;
            height: 46px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
            transition: all 0.2s;
        }
        .btn-deny:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="brand-header">
        <a href="/" title="Bondhoo">
            <img src="/images/bondhoo-logo.png" alt="Bondhoo Logo" class="logo-img">
        </a>
    </div>

    <div class="card">
        <div class="icon-warn">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        <h1 class="title">সন্দেহজনক লগইন কার্যকলাপ শনাক্ত হয়েছে</h1>
        <p class="desc">আপনার পরিচিত ডিভাইস বা অবস্থান ছাড়াও ভিন্ন কোনো অবস্থান থেকে লগইনের চেষ্টা লক্ষ্য করা গেছে।</p>

        <div class="info-card">
            <div><strong>আইপি অ্যাড্রেস:</strong> <span id="alertIp">127.0.0.1</span></div>
            <div><strong>স্থান:</strong> <span id="alertLocation">বাংলাদেশ (BD)</span></div>
            <div><strong>ব্রাউজার:</strong> <span id="alertBrowser">Web Browser</span></div>
        </div>

        <p class="desc" style="font-size: 13px;">এটি কি আপনি ছিলেন?</p>

        <div class="btn-group">
            <button type="button" class="btn-confirm" onclick="window.location.href='/two-factor-challenge'">হ্যাঁ, এটি আমি</button>
            <a href="/forgot-password" class="btn-deny">না, পাসওয়ার্ড পরিবর্তন করুন</a>
        </div>
    </div>
</body>
</html>
