<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>পাসওয়ার্ড পুনরুদ্ধার — Bondhoo</title>
    <meta name="description" content="Bondhoo অ্যাকাউন্টের পাসওয়ার্ড রিসেট করুন।">
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
            padding: 32px;
        }
        .card-header {
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--bh-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .icon-wrap-header {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #eef2ff;
            color: var(--bh-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .title { font-size: 20px; font-weight: 700; color: #0f172a; }
        .desc { font-size: 14px; color: var(--bh-text-secondary); line-height: 1.5; margin-bottom: 20px; }
        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
            margin-bottom: 18px;
        }
        .input-icon {
            position: absolute;
            left: 14px;
            width: 18px;
            height: 18px;
            color: #94a3b8;
            pointer-events: none;
        }
        .form-input {
            width: 100%;
            height: 48px;
            border: 1.5px solid var(--bh-border);
            border-radius: 10px;
            padding: 0 14px 0 42px;
            font-size: 14px;
            outline: none;
            background: #f8fafc;
            color: var(--bh-text-primary);
            transition: all 0.2s ease;
        }
        .form-input:focus {
            border-color: var(--bh-border-focus);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 14px;
            border-top: 1px solid var(--bh-border);
            padding-top: 18px;
        }
        .btn-cancel {
            height: 44px;
            padding: 0 18px;
            border: 1.5px solid var(--bh-border);
            border-radius: 10px;
            background: white;
            color: #475569;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            font-size: 14px;
        }
        .btn-cancel:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        .btn-submit {
            height: 44px;
            padding: 0 22px;
            border: none;
            border-radius: 10px;
            background: var(--bh-gradient);
            color: white;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
        }
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .alert-box {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
            line-height: 1.4;
        }
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
        <div class="card-header">
            <div class="icon-wrap-header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div>
                <h1 class="title">আপনার অ্যাকাউন্ট খুঁজুন</h1>
            </div>
        </div>

        <p class="desc">আপনার অ্যাকাউন্টের সাথে যুক্ত ইমেইল ঠিকানা, মোবাইল নম্বর বা ইউজারনেম লিখুন। আমরা আপনাকে একটি পাসওয়ার্ড রিসেট ওটিপি কোড পাঠাব।</p>

        <div id="alertBox" class="alert-box"></div>

        <form onsubmit="handleForgotPassword(event)">
            <div class="input-wrap">
                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <input type="text" id="recoverIdentifier" class="form-input" placeholder="ইমেইল, মোবাইল নম্বর বা ইউজারনেম" required autofocus>
            </div>

            <div class="btn-group">
                <a href="/login" class="btn-cancel">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>বাতিল</span>
                </a>
                <button type="submit" id="submitBtn" class="btn-submit">
                    <span>অনুসন্ধান ও কোড পাঠান</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <script>
        async function handleForgotPassword(e) {
            e.preventDefault();
            const id = document.getElementById('recoverIdentifier').value.trim();
            const alertBox = document.getElementById('alertBox');
            const submitBtn = document.getElementById('submitBtn');

            alertBox.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.innerText = 'যাচাই হচ্ছে...';

            try {
                const res = await fetch('/api/v2/auth/forgot-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ identifier: id })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    sessionStorage.setItem('reset_identifier', id);
                    alertBox.className = 'alert-box alert-success';
                    alertBox.innerText = data.data.message || 'ওটিপি কোড পাঠানো হয়েছে! পাসওয়ার্ড রিসেট পাতায় নিয়ে যাওয়া হচ্ছে...';
                    alertBox.style.display = 'block';

                    setTimeout(() => {
                        window.location.href = '/reset-password?identifier=' + encodeURIComponent(id);
                    }, 1000);
                } else {
                    alertBox.className = 'alert-box alert-error';
                    alertBox.innerText = data.message || 'অনুরোধ ব্যর্থ হয়েছে।';
                    alertBox.style.display = 'block';
                }
            } catch (err) {
                alertBox.className = 'alert-box alert-error';
                alertBox.innerText = 'সার্ভার এরর। আবার চেষ্টা করুন।';
                alertBox.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = 'অনুসন্ধান ও কোড পাঠান';
            }
        }
    </script>
</body>
</html>
