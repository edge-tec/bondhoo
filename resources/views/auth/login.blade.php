<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>লগইন করুন — Bondhoo সোশ্যাল নেটওয়ার্ক</title>
    <meta name="description" content="Bondhoo — বন্ধু সোশ্যাল নেটওয়ার্কে লগইন করুন। বন্ধু ও পরিজনদের সাথে যুক্ত থাকুন, মনের কথা শেয়ার করুন।">
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
            --bh-primary-hover: #4338ca;
            --bh-gradient: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            --bh-text-primary: #0f172a;
            --bh-text-secondary: #64748b;
            --bh-border: #e2e8f0;
            --bh-border-focus: #6366f1;
            --bh-red: #ef4444;
            --bh-green: #10b981;
            --bh-green-hover: #059669;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-card: 0 20px 40px -15px rgba(99, 102, 241, 0.12), 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Hind Siliguri', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        body {
            background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
            color: var(--bh-text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .main-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 20px;
        }

        .auth-wrapper {
            max-width: 1020px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 56px;
            flex-wrap: wrap;
        }

        .brand-section {
            flex: 1;
            min-width: 320px;
        }

        .brand-logo-wrap {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            text-decoration: none;
        }

        .brand-logo-img {
            height: 64px;
            width: auto;
            max-width: 280px;
            object-fit: contain;
            display: block;
        }

        .brand-headline {
            font-size: 30px;
            line-height: 1.35;
            color: #1e293b;
            font-weight: 700;
            max-width: 500px;
            margin-bottom: 16px;
        }

        .brand-subtext {
            font-size: 16px;
            line-height: 1.6;
            color: var(--bh-text-secondary);
            max-width: 480px;
        }

        .brand-features {
            margin-top: 28px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .brand-feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #334155;
            font-weight: 500;
        }

        .brand-feature-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e0e7ff;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        .form-card {
            width: 420px;
            background: var(--bh-card);
            border-radius: 18px;
            box-shadow: var(--shadow-card);
            padding: 32px 28px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            position: relative;
        }

        .form-header {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--bh-text-primary);
        }

        .form-subtitle {
            font-size: 13px;
            color: var(--bh-text-secondary);
            margin-top: 4px;
        }

        .input-group {
            margin-bottom: 16px;
            position: relative;
        }

        .input-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            width: 18px;
            height: 18px;
            color: #94a3b8;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-input {
            width: 100%;
            height: 48px;
            padding: 0 14px 0 42px;
            border: 1.5px solid var(--bh-border);
            border-radius: 10px;
            font-size: 15px;
            color: var(--bh-text-primary);
            background: #f8fafc;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: var(--bh-border-focus);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .form-input.error {
            border-color: var(--bh-red);
            background: #fff5f5;
        }

        .error-text {
            color: var(--bh-red);
            font-size: 12px;
            font-weight: 500;
            margin-top: 5px;
            display: none;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--bh-text-secondary);
            transition: color 0.2s;
        }

        .password-toggle-btn:hover {
            color: var(--bh-text-primary);
            background: rgba(0,0,0,0.04);
        }

        .identifier-type-badge {
            position: absolute;
            right: 12px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 20px;
            display: none;
            pointer-events: none;
        }

        .capslock-alert {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
        }

        .btn-primary {
            width: 100%;
            height: 48px;
            background: var(--bh-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.2s, opacity 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.45);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .login-spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .divider {
            position: relative;
            text-align: center;
            margin: 22px 0;
        }

        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: var(--bh-border);
        }

        .divider span {
            position: relative;
            background: var(--bh-card);
            padding: 0 12px;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn-register-cta {
            width: 100%;
            height: 46px;
            background: #ffffff;
            color: #0f172a;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-register-cta:hover {
            border-color: #6366f1;
            color: #4f46e5;
            background: #f8fafc;
        }

        .alert-box {
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 16px;
            display: none;
            line-height: 1.4;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }

        .hp-field { display: none !important; }

        footer {
            padding: 24px 20px;
            background: transparent;
            text-align: center;
            color: var(--bh-text-secondary);
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .auth-wrapper {
                flex-direction: column;
                align-items: center;
                gap: 32px;
            }
            .brand-section {
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .brand-headline {
                font-size: 24px;
            }
            .brand-features {
                display: none;
            }
            .form-card {
                width: 100%;
                max-width: 420px;
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>
    <main class="main-container">
        <div class="auth-wrapper">
            <!-- Left Branding -->
            <div class="brand-section">
                <a href="/" class="brand-logo-wrap" title="Bondhoo — বন্ধু সোশ্যাল নেটওয়ার্ক">
                    <img src="/images/bondhoo-logo.png" alt="Bondhoo" class="brand-logo-img">
                </a>
                <h1 class="brand-headline">বন্ধু সোশ্যাল নেটওয়ার্কে আপনাকে স্বাগতম</h1>
                <p class="brand-subtext">Bondhoo আপনাকে আপনার জীবনের প্রিয় মানুষদের সাথে যুক্ত হতে, আনন্দময় মুহূর্তগুলো শেয়ার করতে এবং নতুন বন্ধু তৈরিতে সাহায্য করে।</p>

                <div class="brand-features">
                    <div class="brand-feature-item">
                        <span class="brand-feature-icon">✓</span>
                        <span>রিয়েল-টাইম মেসেজিং, ভয়েস ও এইচডি ভিডিও কল</span>
                    </div>
                    <div class="brand-feature-item">
                        <span class="brand-feature-icon">✓</span>
                        <span>স্টোরিজ, রিলস ও সরাসরি লাইভ ভিডিও সম্প্রচার</span>
                    </div>
                    <div class="brand-feature-item">
                        <span class="brand-feature-icon">✓</span>
                        <span>সম্পূর্ণ নিরাপদ, দ্রুত ও আধুনিক সোশ্যাল অভিজ্ঞতা</span>
                    </div>
                </div>
            </div>

            <!-- Right Login Form Card -->
            <div class="form-card">
                <div class="form-header">
                    <h2 class="form-title">অ্যাকাউন্টে লগইন করুন</h2>
                    <p class="form-subtitle">আপনার শংসাপত্র দিয়ে সাইন-ইন করে যুক্ত হোন</p>
                </div>

                <div id="alertBox" class="alert-box"></div>

                <form id="loginForm" onsubmit="handleLogin(event)" novalidate>
                    <!-- Bot Honeypot -->
                    <input type="text" name="website_hp" class="hp-field" tabindex="-1" autocomplete="off">
                    <input type="text" name="hp_company" class="hp-field" tabindex="-1" autocomplete="off">

                    <!-- Identifier: Username / Email / Mobile -->
                    <div class="input-group">
                        <label class="input-label" for="loginIdentifier">ইমেইল, ইউজারনেম বা মোবাইল নম্বর</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                            </span>
                            <input type="text" id="loginIdentifier" class="form-input" placeholder="ইমেইল বা ইউজারনেম লিখুন" required autocomplete="username" oninput="onIdentifierInput(this.value)">
                            <span id="identifierTypeBadge" class="identifier-type-badge"></span>
                        </div>
                        <div id="identifierError" class="error-text"></div>
                    </div>

                    <!-- Password with Toggle & Caps Lock Detection -->
                    <div class="input-group">
                        <label class="input-label" for="loginPassword">পাসওয়ার্ড</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </span>
                            <input type="password" id="loginPassword" class="form-input" placeholder="আপনার গোপন পাসওয়ার্ড" required autocomplete="current-password" oninput="onPasswordInput(this.value)" style="padding-right: 48px;">
                            <button type="button" id="pwdToggleBtn" class="password-toggle-btn" onclick="togglePasswordVisibility()" title="পাসওয়ার্ড দেখুন" aria-label="পাসওয়ার্ড দেখুন">
                                <svg id="eyeIconOpen" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg id="eyeIconClosed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="m15 18-.722-3.25"></path><path d="M2 8a10.645 10.645 0 0 0 20 0"></path><path d="m20 15-1.726-2.05"></path><path d="m4 15 1.726-2.05"></path><path d="m9 18 .722-3.25"></path></svg>
                            </button>
                        </div>
                        <div id="capsLockAlert" class="capslock-alert" style="display:none;">⚠️ ক্যাপস লক (Caps Lock) চালু রয়েছে!</div>
                        <div id="passwordError" class="error-text"></div>
                    </div>

                    <!-- CAPTCHA Section (appears after failed attempts) -->
                    <div id="captchaContainer" style="display: none; margin-bottom: 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 12px;">
                        <div style="font-size: 13px; font-weight: 600; color: #1e40af; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            <span>নিরাপত্তা যাচাইকরণ (ক্যাপচা সমাধান করুন)</span>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span id="captchaQuestion" style="font-size: 16px; font-weight: 700; background: #e2e8f0; padding: 6px 12px; border-radius: 6px; letter-spacing: 1px; color: #1e293b;">-</span>
                            <input type="text" id="captchaAnswer" class="form-input" placeholder="উত্তর লিখুন" style="height: 38px; flex: 1; padding-left: 12px;">
                            <input type="hidden" id="captchaKey">
                            <button type="button" onclick="loadCaptcha()" style="background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; cursor: pointer; display: flex; align-items: center;" title="নতুন ক্যাপচা">🔄</button>
                        </div>
                        <div id="captchaError" class="error-text" style="display: none;"></div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; font-size: 14px;">
                        <label style="display: flex; align-items: center; gap: 7px; cursor: pointer; color: var(--bh-text-secondary); user-select: none;">
                            <input type="checkbox" id="rememberMe" checked style="accent-color: var(--bh-primary); width: 16px; height: 16px;">
                            <span>লগইন মনে রাখুন</span>
                        </label>
                        <a href="/forgot-password" style="color: var(--bh-primary); text-decoration: none; font-weight: 600; font-size: 13px;">পাসওয়ার্ড ভুলে গেছেন?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn" class="btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                        <span>লগইন করুন</span>
                    </button>
                </form>

                <div class="divider">
                    <span>অথবা</span>
                </div>

                <!-- Create Account CTA Button -->
                <a href="/register" class="btn-register-cta">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
                    <span>নতুন অ্যাকাউন্ট তৈরি করুন</span>
                </a>
            </div>
        </div>
    </main>

    <footer>
        <p>Bondhoo সোশ্যাল নেটওয়ার্ক &copy; {{ date('Y') }} — বন্ধু ও পরিজনদের সাথে বন্ধন আরও সুদৃঢ় করুন।</p>
    </footer>

    <script>
        let isSubmitting = false;

        function detectIdentifierType(val) {
            val = (val || '').trim();
            const badge = document.getElementById('identifierTypeBadge');
            if (!badge) return 'empty';

            if (!val) {
                badge.style.display = 'none';
                return 'empty';
            }

            if (val.includes('@')) {
                badge.innerText = '📧 ইমেইল';
                badge.style.display = 'inline-block';
                badge.style.background = '#e0e7ff';
                badge.style.color = '#4338ca';
                return 'email';
            } else if (/^(\+?88)?01[3-9]\d{8}$/.test(val) || /^(\+?\d{8,15})$/.test(val)) {
                badge.innerText = '📱 মোবাইল';
                badge.style.display = 'inline-block';
                badge.style.background = '#dcfce7';
                badge.style.color = '#15803d';
                return 'phone';
            } else {
                badge.innerText = '👤 ইউজারনেম';
                badge.style.display = 'inline-block';
                badge.style.background = '#f1f5f9';
                badge.style.color = '#475569';
                return 'username';
            }
        }

        function onIdentifierInput(val) {
            const idInput = document.getElementById('loginIdentifier');
            const idError = document.getElementById('identifierError');
            if (idInput) idInput.classList.remove('error');
            if (idError) idError.style.display = 'none';
            detectIdentifierType(val);
        }

        function onPasswordInput(val) {
            const pwdInput = document.getElementById('loginPassword');
            const pwdError = document.getElementById('passwordError');
            if (pwdInput) pwdInput.classList.remove('error');
            if (pwdError) pwdError.style.display = 'none';
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('loginPassword');
            const eyeOpen = document.getElementById('eyeIconOpen');
            const eyeClosed = document.getElementById('eyeIconClosed');
            const toggleBtn = document.getElementById('pwdToggleBtn');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
                toggleBtn.setAttribute('title', 'পাসওয়ার্ড লুকান');
                toggleBtn.setAttribute('aria-label', 'পাসওয়ার্ড লুকান');
            } else {
                passwordInput.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
                toggleBtn.setAttribute('title', 'পাসওয়ার্ড দেখুন');
                toggleBtn.setAttribute('aria-label', 'পাসওয়ার্ড দেখুন');
            }
        }

        async function loadCaptcha() {
            try {
                const res = await fetch('/api/v2/auth/captcha');
                const data = await res.json();
                if (data.success && data.data) {
                    document.getElementById('captchaQuestion').innerText = data.data.captcha_question;
                    document.getElementById('captchaKey').value = data.data.captcha_key;
                    document.getElementById('captchaAnswer').value = '';
                    document.getElementById('captchaContainer').style.display = 'block';
                }
            } catch (err) {
                console.error('Failed to load captcha', err);
            }
        }

        // Caps Lock detection
        const passwordField = document.getElementById('loginPassword');
        const capsAlert = document.getElementById('capsLockAlert');

        ['keydown', 'keyup'].forEach(eventType => {
            passwordField.addEventListener(eventType, function (e) {
                if (e.getModifierState && e.getModifierState('CapsLock')) {
                    capsAlert.style.display = 'block';
                } else {
                    capsAlert.style.display = 'none';
                }
            });
        });

        passwordField.addEventListener('blur', function () {
            capsAlert.style.display = 'none';
        });

        document.addEventListener('DOMContentLoaded', () => {
            detectIdentifierType(document.getElementById('loginIdentifier').value);
        });

        async function handleLogin(e) {
            e.preventDefault();

            if (isSubmitting) return;

            const alertBox = document.getElementById('alertBox');
            const submitBtn = document.getElementById('submitBtn');
            const idInput = document.getElementById('loginIdentifier');
            const pwdInput = document.getElementById('loginPassword');
            const idError = document.getElementById('identifierError');
            const pwdError = document.getElementById('passwordError');

            const identifier = idInput.value.trim();
            const password = pwdInput.value;
            const remember = document.getElementById('rememberMe').checked;

            alertBox.style.display = 'none';
            idError.style.display = 'none';
            pwdError.style.display = 'none';
            idInput.classList.remove('error');
            pwdInput.classList.remove('error');

            if (!identifier) {
                idInput.classList.add('error');
                idError.innerText = 'ইমেইল, ইউজারনেম বা মোবাইল নম্বর প্রদান করা আবশ্যক।';
                idError.style.display = 'block';
                idInput.focus();
                return;
            }

            if (!password) {
                pwdInput.classList.add('error');
                pwdError.innerText = 'পাসওয়ার্ড প্রদান করা আবশ্যক।';
                pwdError.style.display = 'block';
                pwdInput.focus();
                return;
            }

            if (password.length < 6) {
                pwdInput.classList.add('error');
                pwdError.innerText = 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।';
                pwdError.style.display = 'block';
                pwdInput.focus();
                return;
            }

            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="login-spinner"></span> <span>যাচাই করা হচ্ছে...</span>';

            const endpoint = '/api/v2/auth/login';

            const payload = {
                identifier: identifier,
                password: password,
                remember: remember,
                device_name: 'Web Browser (' + (navigator.platform || 'Workstation') + ')'
            };

            const capKey = document.getElementById('captchaKey')?.value;
            const capAns = document.getElementById('captchaAnswer')?.value;
            if (capKey && capAns) {
                payload.captcha_key = capKey;
                payload.captcha_answer = capAns;
            }

            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    if (data.data?.requires_2fa) {
                        sessionStorage.setItem('2fa_challenge_token', data.data.challenge_token);
                        sessionStorage.setItem('2fa_destination', data.data.destination || '');
                        sessionStorage.setItem('2fa_method', data.data.method || 'app');
                        sessionStorage.setItem('2fa_guard', 'web');
                        window.location.href = '/two-factor-challenge';
                        return;
                    }

                    // Store both bondhoo and legacy keys for 100% interoperability
                    localStorage.setItem('bondhoo_token', data.data.token);
                    localStorage.setItem('jugajug_token', data.data.token);
                    localStorage.setItem('bondhoo_user', JSON.stringify(data.data.user));
                    localStorage.setItem('jugajug_user', JSON.stringify(data.data.user));
                    document.cookie = `bondhoo_token=${data.data.token}; path=/; max-age=2592000; SameSite=Lax`;
                    document.cookie = `jugajug_token=${data.data.token}; path=/; max-age=2592000; SameSite=Lax`;

                    alertBox.className = 'alert-box alert-info';
                    alertBox.innerText = 'লগইন সফল হয়েছে! ড্যাশবোর্ডে নিয়ে যাওয়া হচ্ছে...';
                    alertBox.style.display = 'block';
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 400);
                } else {
                    let hasFocused = false;

                    if (data.errors) {
                        if (data.errors.identifier) {
                            idInput.classList.add('error');
                            idError.innerText = data.errors.identifier[0];
                            idError.style.display = 'block';
                            idInput.focus();
                            hasFocused = true;
                        }
                        if (data.errors.password) {
                            pwdInput.classList.add('error');
                            pwdError.innerText = data.errors.password[0];
                            pwdError.style.display = 'block';
                            if (!hasFocused) {
                                pwdInput.focus();
                                hasFocused = true;
                            }
                        }
                        if (data.errors.captcha || data.errors.requires_captcha) {
                            loadCaptcha();
                            const capError = document.getElementById('captchaError');
                            if (capError) {
                                capError.innerText = data.errors.captcha ? data.errors.captcha[0] : 'ক্যাপচা পূরণ আবশ্যক।';
                                capError.style.display = 'block';
                            }
                        }
                    }

                    if (data.requires_captcha) {
                        loadCaptcha();
                    }

                    const msg = data.errors?.identifier?.[0] || data.errors?.password?.[0] || data.message || 'লগইন ব্যর্থ হয়েছে।';

                    if (msg.includes('লক')) {
                        sessionStorage.setItem('locked_message', msg);
                        window.location.href = '/account-locked';
                        return;
                    }

                    alertBox.className = 'alert-box alert-error';
                    alertBox.innerText = msg;
                    alertBox.style.display = 'block';

                    if (!hasFocused) {
                        idInput.focus();
                    }
                }
            } catch (err) {
                console.error(err);
                alertBox.className = 'alert-box alert-error';
                alertBox.innerText = 'সার্ভার কানেকশনে সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।';
                alertBox.style.display = 'block';
            } finally {
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg><span>লগইন করুন</span>';
            }
        }
    </script>
</body>
</html>
