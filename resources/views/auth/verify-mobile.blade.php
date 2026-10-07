<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>মোবাইল নম্বর যাচাই — Bondhoo</title>
    <meta name="description" content="Bondhoo অ্যাকাউন্টের সুরক্ষার জন্য আপনার মোবাইল নম্বর যাচাই করুন।">
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
            --bh-border-focus: #6366f1;
            --bh-red: #ef4444;
            --bh-green: #10b981;
            --bh-green-hover: #059669;
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
            max-width: 460px;
            background: var(--bh-card);
            border-radius: 16px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--bh-border);
            padding: 32px 28px;
            text-align: center;
        }
        .icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            background: #ecfdf5;
            color: var(--bh-green);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        .title { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .desc { font-size: 14px; color: var(--bh-text-secondary); line-height: 1.5; margin-bottom: 16px; }
        .target-box {
            background: #f1f5f9;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 700;
            color: #0f172a;
            display: inline-block;
            margin-bottom: 12px;
            font-size: 16px;
            border: 1px solid var(--bh-border);
        }
        .gateway-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            background: #eef2ff;
            color: var(--bh-primary);
            padding: 4px 10px;
            border-radius: 9999px;
            margin-bottom: 18px;
            font-weight: 600;
        }
        .otp-input {
            width: 100%;
            height: 54px;
            border: 2px solid var(--bh-border);
            border-radius: 12px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: 700;
            outline: none;
            margin-bottom: 18px;
            background: #f8fafc;
            color: #0f172a;
            transition: all 0.2s ease;
        }
        .otp-input:focus {
            border-color: var(--bh-green);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }
        .btn-verify {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            transition: all 0.2s;
        }
        .btn-verify:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.4);
        }
        .btn-verify:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .resend-section { margin-top: 20px; font-size: 13px; color: var(--bh-text-secondary); }
        .resend-btn { background: none; border: none; color: var(--bh-primary); font-weight: 600; cursor: pointer; text-decoration: underline; padding: 0; }
        .resend-btn:disabled { color: #94a3b8; text-decoration: none; cursor: not-allowed; }
        .alert-box { padding: 12px 14px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; display: none; line-height: 1.4; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
    </style>
</head>
<body>
    <div class="brand-header">
        <a href="/" title="Bondhoo">
            <img src="/images/bondhoo-logo.png" alt="Bondhoo Logo" class="logo-img">
        </a>
    </div>

    <div class="card">
        <div class="icon-circle">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                <line x1="12" y1="18" x2="12.01" y2="18"></line>
            </svg>
        </div>
        <h1 class="title">মোবাইল এসএমএস ওটিপি যাচাই</h1>
        <p class="desc">আপনার প্রদত্ত মোবাইল নম্বরে ৬ সংখ্যার ভেরিফিকেশন কোড পাঠানো হয়েছে:</p>
        <div id="targetDisplay" class="target-box">+8801700000000</div>
        <div>
            <span class="gateway-tag">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                এসএমএস গেটওয়ে রেডি (SSL Wireless / BulkSMSBD / Twilio)
            </span>
        </div>

        <div id="alertBox" class="alert-box"></div>

        <form onsubmit="handleVerify(event)">
            <input type="text" id="otpCode" class="otp-input" placeholder="••••••" maxlength="6" required autocomplete="one-time-code" autofocus>
            <button type="submit" id="verifyBtn" class="btn-verify">মোবাইল নম্বর নিশ্চিত করুন</button>
        </form>

        <div class="resend-section">
            এসএমএস পাননি?
            <button type="button" id="resendBtn" class="resend-btn" onclick="handleResend()" disabled>পুনরায় এসএমএস পাঠান (<span id="timer">60</span>s)</button>
        </div>
    </div>

    <script>
        let phone = sessionStorage.getItem('verify_phone_target') || '+8801711111111';
        document.getElementById('targetDisplay').innerText = phone;

        let countdown = 60;
        const timerSpan = document.getElementById('timer');
        const resendBtn = document.getElementById('resendBtn');

        const interval = setInterval(() => {
            countdown--;
            timerSpan.innerText = countdown;
            if (countdown <= 0) {
                clearInterval(interval);
                resendBtn.disabled = false;
                resendBtn.innerText = 'এখনই পুনরায় এসএমএস পাঠান';
            }
        }, 1000);

        async function handleVerify(e) {
            e.preventDefault();
            const otp = document.getElementById('otpCode').value.trim();
            const alertBox = document.getElementById('alertBox');
            const verifyBtn = document.getElementById('verifyBtn');

            alertBox.style.display = 'none';
            verifyBtn.disabled = true;

            try {
                const res = await fetch('/api/v2/auth/verify-mobile', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ identifier: phone, otp: otp })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    alertBox.className = 'alert-box alert-success';
                    alertBox.innerText = 'মোবাইল নম্বর সফলভাবে যাচাই হয়েছে! রিডাইরেক্ট করা হচ্ছে...';
                    alertBox.style.display = 'block';

                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 1200);
                } else {
                    alertBox.className = 'alert-box alert-error';
                    alertBox.innerText = data.errors?.otp?.[0] || data.message || 'ভুল ওটিপি কোড।';
                    alertBox.style.display = 'block';
                }
            } catch (err) {
                alertBox.className = 'alert-box alert-error';
                alertBox.innerText = 'সার্ভার এরর। আবার চেষ্টা করুন।';
                alertBox.style.display = 'block';
            } finally {
                verifyBtn.disabled = false;
            }
        }

        async function handleResend() {
            const alertBox = document.getElementById('alertBox');
            try {
                const res = await fetch('/api/v2/auth/resend-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ phone: phone })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alertBox.className = 'alert-box alert-success';
                    alertBox.innerText = 'নতুন এসএমএস পাঠানো হয়েছে।';
                    alertBox.style.display = 'block';
                    resendBtn.disabled = true;
                    resendBtn.innerText = 'এসএমএস পাঠানো হয়েছে';
                }
            } catch (err) {
                alertBox.className = 'alert-box alert-error';
                alertBox.innerText = 'এসএমএস পাঠাতে সমস্যা হয়েছে।';
                alertBox.style.display = 'block';
            }
        }
    </script>
</body>
</html>
