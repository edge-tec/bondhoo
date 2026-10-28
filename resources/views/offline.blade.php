<!DOCTYPE html>
<html lang="bn" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অফলাইন মোড — বন্ধু সোশ্যাল নেটওয়ার্ক</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Hind Siliguri', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .offline-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 48px 32px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .icon-container {
            width: 96px;
            height: 96px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(249, 115, 22, 0.2));
            border: 2px solid rgba(239, 68, 68, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .icon-container svg {
            width: 48px;
            height: 48px;
            color: #f87171;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }
        h1 {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 12px;
        }
        p {
            font-size: 16px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .btn-retry {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            padding: 14px 24px;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
        }
        .btn-retry:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(79, 70, 229, 0.6);
        }
        .btn-retry:active {
            transform: translateY(0);
        }
        .cached-links {
            margin-top: 24px;
            font-size: 14px;
            color: #64748b;
        }
        .cached-links a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 500;
        }
        .cached-links a:hover {
            text-decoration: underline;
        }
        .status-badge {
            display: inline-block;
            margin-top: 16px;
            padding: 6px 14px;
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
    </style>
</head>
<body>
    <div class="offline-card">
        <div class="icon-container">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-1.414-1.414m-1.414-1.414a9 9 0 010-12.728m0 0l2.829 2.829m-2.829-2.829L3 3m5.636 5.636a5 5 0 011.414-1.414m1.414 1.414L12 12"></path>
            </svg>
        </div>

        <h1>ইন্টারনেট সংযোগ বিচ্ছিন্ন</h1>
        <p>আপনি বর্তমানে অফলাইনে আছেন। আপনার নেটওয়ার্ক সংযোগ ঠিক হলে পূর্বের মতো সবকিছু স্বয়ংক্রিয়ভাবে সিঙ্ক হবে।</p>

        <button class="btn-retry" onclick="window.location.reload();">
            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            পুনরায় চেষ্টা করুন
        </button>

        <div class="status-badge" id="net-status">অফলাইন সংরক্ষিত মোড</div>

        <div class="cached-links">
            ক্যাশ করা ফিড দেখতে <a href="/dashboard">ড্যাশবোর্ডে যান</a>
        </div>
    </div>

    <script>
        window.addEventListener('online', () => {
            const status = document.getElementById('net-status');
            status.textContent = 'সংযোগ ফিরে এসেছে! পেজ রিফ্রেশ হচ্ছে...';
            status.style.color = '#86efac';
            status.style.background = 'rgba(34, 197, 94, 0.2)';
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });
    </script>
</body>
</html>
