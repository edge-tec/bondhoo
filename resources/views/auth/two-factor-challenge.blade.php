<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>টু-ফ্যাক্টর অথেন্টিকেশন — Bondhoo</title>
    <meta name="description" content="Bondhoo টু-ফ্যাক্টর নিরাপত্তা যাচাই">
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
            max-width: 440px;
            background: var(--bh-card);
            border-radius: 16px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--bh-border);
            padding: 32px 28px;
            text-align: center;
        }
        .icon-shield {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: #eef2ff;
            color: var(--bh-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        .title { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .desc { font-size: 14px; color: var(--bh-text-secondary); line-height: 1.5; margin-bottom: 24px; }
        .code-input {
            width: 100%;
            height: 54px;
            border: 2px solid var(--bh-border);
            border-radius: 12px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 6px;
            font-weight: 700;
            outline: none;
            margin-bottom: 20px;
            background: #f8fafc;
            color: #0f172a;
            transition: all 0.2s ease;
        }
        .code-input:focus {
            border-color: var(--bh-border-focus);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .btn-verify {
            width: 100%;
            height: 48px;
            background: var(--bh-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
            transition: all 0.2s;
        }
        .btn-verify:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.4);
        }
        .btn-verify:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .backup-link { margin-top: 20px; font-size: 13px; }
        .backup-link a { color: var(--bh-primary); text-decoration: none; cursor: pointer; font-weight: 600; }
        .backup-link a:hover { text-decoration: underline; }
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
        <div class="icon-shield">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <circle cx="12" cy="11" r="2"></circle>
                <path d="M12 13v4"></path>
            </svg>
        </div>
        <h1 class="title">টু-ফ্যাক্টর নিরাপত্তা যাচাই</h1>
        <p class="desc" id="challengeDesc">আপনার গুগল অথেনটিকেটর অ্যাপের ৬ সংখ্যার কোড অথবা আপনার ঠিকানায় পাঠানো ওটিপি কোডটি লিখুন।</p>

        <div id="alertBox" class="alert-box"></div>

        <form onsubmit="handleChallenge(event)">
            <input type="text" id="challengeCode" class="code-input" placeholder="••••••" required autocomplete="one-time-code" autofocus>
            <button type="submit" id="submitBtn" class="btn-verify">অনুমোদন করুন</button>
        </form>

        <div class="backup-link">
            <a onclick="toggleBackupMode()">অথবা ব্যাকআপ রিকভারি কোড ব্যবহার করুন (XXXX-XXXX)</a>
        </div>
    </div>

    <script>
        const challengeToken = sessionStorage.getItem('2fa_challenge_token');
        const dest = sessionStorage.getItem('2fa_destination');
        const method = sessionStorage.getItem('2fa_method');

        if (!challengeToken) {
            window.location.href = '/login';
        }

        if (dest) {
            document.getElementById('challengeDesc').innerText = `আমরা আপনার ${dest} ঠিকানায় একটি নিরাপত্তা কোড পাঠিয়েছি। কোডটি নিচে লিখুন:`;
        }

        function toggleBackupMode() {
            const input = document.getElementById('challengeCode');
            input.placeholder = 'ABCD-EFGH';
            input.focus();
        }

        async function handleChallenge(e) {
            e.preventDefault();
            const code = document.getElementById('challengeCode').value.trim();
            const alertBox = document.getElementById('alertBox');
            const submitBtn = document.getElementById('submitBtn');

            alertBox.style.display = 'none';
            submitBtn.disabled = true;

            try {
                const res = await fetch('/api/v2/auth/2fa/challenge', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        challenge_token: challengeToken,
                        code: code
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    localStorage.setItem('bondhoo_token', data.data.token);
                    localStorage.setItem('jugajug_token', data.data.token);
                    localStorage.setItem('bondhoo_user', JSON.stringify(data.data.user));
                    localStorage.setItem('jugajug_user', JSON.stringify(data.data.user));
                    document.cookie = `bondhoo_token=${data.data.token}; path=/; max-age=2592000; SameSite=Lax`;
                    document.cookie = `jugajug_token=${data.data.token}; path=/; max-age=2592000; SameSite=Lax`;
                    sessionStorage.removeItem('2fa_challenge_token');

                    alertBox.className = 'alert-box alert-success';
                    alertBox.innerText = 'যাচাই সফল! ড্যাশবোর্ডে নিয়ে যাওয়া হচ্ছে...';
                    alertBox.style.display = 'block';

                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 600);
                } else {
                    alertBox.className = 'alert-box alert-error';
                    alertBox.innerText = data.message || 'ভুল টু-ফ্যাক্টর কোড।';
                    alertBox.style.display = 'block';
                }
            } catch (err) {
                alertBox.className = 'alert-box alert-error';
                alertBox.innerText = 'সার্ভার এরর। আবার চেষ্টা করুন।';
                alertBox.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
            }
        }
    </script>
</body>
</html>
