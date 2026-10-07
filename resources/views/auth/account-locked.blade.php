<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাকাউন্ট সাময়িক লক — Bondhoo</title>
    <meta name="description" content="নিরাপত্তার স্বার্থে আপনার Bondhoo অ্যাকাউন্ট সাময়িক লক করা হয়েছে।">
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
        .icon-lock {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            background: #fef2f2;
            color: var(--bh-red);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        .title { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .desc { font-size: 14px; color: var(--bh-text-secondary); line-height: 1.5; margin-bottom: 24px; }
        .security-badge {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 20px;
        }
        .btn-group { display: flex; gap: 12px; }
        .btn-primary {
            flex: 1;
            height: 46px;
            background: var(--bh-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
        }
        .btn-secondary {
            flex: 1;
            height: 46px;
            background: white;
            border: 1.5px solid var(--bh-border);
            color: #334155;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-secondary:hover {
            background: #f1f5f9;
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
        <div class="icon-lock">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        <h1 class="title">অ্যাকাউন্ট সাময়িক লক করা হয়েছে</h1>
        <div id="lockedMsg" class="security-badge">
            একাধিকবার ভুল পাসওয়ার্ড দেওয়ার কারণে নিরাপত্তার স্বার্থে অ্যাকাউন্টটি আগামী ১৫ মিনিটের জন্য লক করা হয়েছে।
        </div>
        <p class="desc">
            Bondhoo প্ল্যাটফর্মের আধুনিক নিরাপত্তা ব্যবস্থার অংশ হিসেবে ব্রুট-ফোর্স বা অননুমোদিত প্রবেশ প্রতিহত করতে এই ব্যবস্থা নেওয়া হয়েছে। সময় পার হওয়ার পর আবার চেষ্টা করতে পারবেন, অথবা পাসওয়ার্ড রিসেট করে অ্যাকাউন্ট পুনরুদ্ধার করতে পারবেন।
        </p>

        <div class="btn-group">
            <a href="/forgot-password" class="btn-primary">পাসওয়ার্ড পুনরুদ্ধার</a>
            <a href="/login" class="btn-secondary">লগইন পাতায় ফিরে যান</a>
        </div>
    </div>

    <script>
        const customMsg = sessionStorage.getItem('locked_message');
        if (customMsg) {
            document.getElementById('lockedMsg').innerText = customMsg;
        }
    </script>
</body>
</html>
