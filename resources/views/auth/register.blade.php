<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>নতুন অ্যাকাউন্ট তৈরি করুন — Bondhoo সোশ্যাল নেটওয়ার্ক</title>
    <meta name="description" content="Bondhoo — বন্ধু সোশ্যাল নেটওয়ার্কে যুক্ত হোন। সহজে ও নিরাপদে আপনার নতুন অ্যাকাউন্ট তৈরি করুন।">
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

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--bh-border);
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .logo-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo-img {
            height: 42px;
            width: auto;
            max-width: 190px;
            object-fit: contain;
            display: block;
        }

        .login-btn-top {
            background: transparent;
            color: var(--bh-primary);
            border: 1.5px solid var(--bh-primary);
            border-radius: 8px;
            padding: 8px 18px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .login-btn-top:hover {
            background: var(--bh-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .main-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 36px 16px;
        }

        .card {
            width: 100%;
            max-width: 580px;
            background: var(--bh-card);
            border-radius: 16px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--bh-border);
            padding: 32px 36px;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header {
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 22px;
            border-bottom: 1px solid var(--bh-border);
        }

        .card-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ede9fe;
            color: #6d28d9;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .card-title {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .card-subtitle {
            font-size: 14px;
            color: var(--bh-text-secondary);
            margin-top: 6px;
        }

        .form-row {
            display: flex;
            gap: 14px;
            margin-bottom: 14px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 14px;
            position: relative;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #334155;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .input-wrap {
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
            transition: color 0.2s;
        }

        .form-input, .form-select {
            width: 100%;
            height: 46px;
            border: 1.5px solid var(--bh-border);
            border-radius: 10px;
            padding: 0 14px 0 42px;
            font-size: 14px;
            outline: none;
            background: #f8fafc;
            color: var(--bh-text-primary);
            transition: all 0.2s ease;
        }

        .form-input:focus, .form-select:focus {
            border-color: var(--bh-border-focus);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .input-wrap:focus-within .input-icon {
            color: var(--bh-primary);
        }

        .pw-toggle-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .pw-toggle-btn:hover {
            color: var(--bh-text-primary);
        }

        .radio-group {
            display: flex;
            gap: 10px;
        }

        .radio-card {
            flex: 1;
            height: 44px;
            border: 1.5px solid var(--bh-border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 14px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            background: #f8fafc;
            transition: all 0.2s ease;
        }

        .radio-card:hover {
            background: #ffffff;
            border-color: #cbd5e1;
        }

        .radio-card input[type="radio"]:checked + span,
        .radio-card:has(input[type="radio"]:checked) {
            border-color: var(--bh-primary);
            background: #eef2ff;
            color: var(--bh-primary);
            font-weight: 600;
        }

        .meter-container {
            height: 6px;
            width: 100%;
            background: #e2e8f0;
            border-radius: 9999px;
            margin: 8px 0 4px 0;
            overflow: hidden;
        }

        .meter-fill {
            height: 100%;
            width: 0%;
            border-radius: 9999px;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .strength-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            margin-top: 4px;
        }

        .strength-text {
            color: var(--bh-text-secondary);
            font-weight: 500;
        }

        .match-badge {
            font-size: 12px;
            font-weight: 600;
            margin-top: 4px;
            display: none;
        }

        .match-badge.matched {
            color: var(--bh-green);
            display: block;
        }

        .match-badge.unmatched {
            color: var(--bh-red);
            display: block;
        }

        .terms-box {
            background: #f8fafc;
            border: 1px solid var(--bh-border);
            border-radius: 10px;
            padding: 12px 14px;
            margin: 16px 0;
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
        }

        .terms-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
        }

        .terms-checkbox {
            margin-top: 3px;
            width: 16px;
            height: 16px;
            accent-color: var(--bh-primary);
            cursor: pointer;
        }

        .terms-label a {
            color: var(--bh-primary);
            font-weight: 600;
            text-decoration: none;
        }

        .terms-label a:hover {
            text-decoration: underline;
        }

        .btn-submit {
            width: 100%;
            height: 48px;
            background: var(--bh-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .bottom-login-cta {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--bh-text-secondary);
        }

        .bottom-login-cta a {
            color: var(--bh-primary);
            font-weight: 700;
            text-decoration: none;
            margin-left: 4px;
        }

        .bottom-login-cta a:hover {
            text-decoration: underline;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 18px;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
        }

        .hp-field {
            display: none !important;
            visibility: hidden !important;
        }

        footer {
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: var(--bh-text-secondary);
            border-top: 1px solid rgba(226, 232, 240, 0.8);
            background: rgba(255, 255, 255, 0.6);
        }

        @media (max-width: 640px) {
            .card {
                padding: 24px 18px;
                border-radius: 12px;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            .card-title {
                font-size: 22px;
            }
            .header {
                padding: 10px 16px;
            }
            .logo-img {
                height: 36px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="/" class="logo-link" title="Bondhoo — হোম পেজে যান">
            <img src="/images/bondhoo-logo.png" alt="Bondhoo Logo" class="logo-img">
        </a>
        <a href="/login" class="login-btn-top">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                <polyline points="10 17 15 12 10 7"></polyline>
                <line x1="15" y1="12" x2="3" y2="12"></line>
            </svg>
            <span>ইতিমধ্যে অ্যাকাউন্ট আছে? লগইন</span>
        </a>
    </header>

    <main class="main-container">
        <div class="card">
            <div class="card-header">
                <div class="card-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <line x1="19" y1="8" x2="19" y2="14"></line>
                        <line x1="22" y1="11" x2="16" y2="11"></line>
                    </svg>
                    <span>নতুন সদস্য নিবন্ধন</span>
                </div>
                <h1 class="card-title">Bondhoo-তে স্বাগতম</h1>
                <p class="card-subtitle">বন্ধু ও শুভানুধ্যায়ীদের সাথে যুক্ত হতে মাত্র ১ মিনিটে অ্যাকাউন্ট খুলুন।</p>
            </div>

            <div id="alertBox" class="alert-box">
                <span id="alertIcon"></span>
                <span id="alertText"></span>
            </div>

            <form id="registerForm" onsubmit="handleRegister(event)" novalidate>
                <!-- Anti-bot Honeypots -->
                <input type="text" name="website_hp" class="hp-field" tabindex="-1" autocomplete="off">
                <input type="text" name="hp_company" class="hp-field" tabindex="-1" autocomplete="off">

                <!-- First Name & Last Name -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="regFirstName">
                            <span>নামের প্রথম অংশ</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <input type="text" id="regFirstName" class="form-input" placeholder="উদাঃ আবরার" required autocomplete="given-name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="regLastName">
                            <span>নামের শেষ অংশ</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <input type="text" id="regLastName" class="form-input" placeholder="উদাঃ রাহিদ" required autocomplete="family-name">
                        </div>
                    </div>
                </div>

                <!-- Username -->
                <div class="form-group">
                    <label class="form-label" for="regUsername">
                        <span>ইউজারনেম (Username)</span>
                    </label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="4"></circle>
                            <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"></path>
                        </svg>
                        <input type="text" id="regUsername" class="form-input" placeholder="উদাঃ abrar_rahid" required autocomplete="username">
                    </div>
                </div>

                <!-- Email Address -->
                <div class="form-group">
                    <label class="form-label" for="regEmail">
                        <span>ইমেইল ঠিকানা</span>
                    </label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <input type="email" id="regEmail" class="form-input" placeholder="name@domain.com" required autocomplete="email">
                    </div>
                </div>

                <!-- Mobile & Country -->
                <div class="form-row">
                    <div class="form-group" style="flex: 1.4;">
                        <label class="form-label" for="regPhone">
                            <span>মোবাইল নম্বর</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <input type="tel" id="regPhone" class="form-input" placeholder="+8801700000000" required autocomplete="tel">
                        </div>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label" for="regCountry">
                            <span>দেশ</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                            </svg>
                            <select id="regCountry" class="form-select">
                                <option value="BD" selected>বাংলাদেশ (BD)</option>
                                <option value="US">যুক্তরাষ্ট্র (US)</option>
                                <option value="GB">যুক্তরাজ্য (UK)</option>
                                <option value="SA">সৌদি আরব (SA)</option>
                                <option value="AE">সংযুক্ত আরব আমিরাত (UAE)</option>
                                <option value="MY">মালয়েশিয়া (MY)</option>
                                <option value="SG">সিঙ্গাপুর (SG)</option>
                                <option value="IN">ভারত (IN)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Date of Birth -->
                <div class="form-group">
                    <label class="form-label" for="regDob">
                        <span>জন্ম তারিখ</span>
                    </label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <input type="date" id="regDob" class="form-input" required autocomplete="bday">
                    </div>
                </div>

                <!-- Gender -->
                <div class="form-group">
                    <label class="form-label">
                        <span>লিঙ্গ</span>
                    </label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <span>মহিলা</span>
                            <input type="radio" name="gender" value="female">
                        </label>
                        <label class="radio-card">
                            <span>পুরুষ</span>
                            <input type="radio" name="gender" value="male" checked>
                        </label>
                        <label class="radio-card">
                            <span>অন্যান্য</span>
                            <input type="radio" name="gender" value="other">
                        </label>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label" for="regPassword">
                        <span>নতুন পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)</span>
                    </label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input type="password" id="regPassword" class="form-input" style="padding-right: 44px;" placeholder="ছোট-বড় হাত, সংখ্যা ও স্পেশাল ক্যারেক্টার সহ" required autocomplete="new-password" oninput="checkStrength(this.value); checkPasswordMatch();">
                        <button type="button" class="pw-toggle-btn" onclick="togglePasswordVisibility('regPassword', 'eyeIcon1')" aria-label="পাসওয়ার্ড দেখুন বা লুকান">
                            <svg id="eyeIcon1" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <div class="meter-container">
                        <div id="meterFill" class="meter-fill"></div>
                    </div>
                    <div class="strength-row">
                        <span id="strengthText" class="strength-text">পাসওয়ার্ড নিরাপত্তা স্কোর: অপেক্ষমান</span>
                        <span id="lengthHint" style="color: #94a3b8; font-size: 11px;">ন্যূনতম ৮ অক্ষর</span>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label class="form-label" for="regPasswordConfirm">
                        <span>পাসওয়ার্ড নিশ্চিত করুন</span>
                    </label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <input type="password" id="regPasswordConfirm" class="form-input" style="padding-right: 44px;" placeholder="পুনরায় একই পাসওয়ার্ড লিখুন" required autocomplete="new-password" oninput="checkPasswordMatch();">
                        <button type="button" class="pw-toggle-btn" onclick="togglePasswordVisibility('regPasswordConfirm', 'eyeIcon2')" aria-label="পাসওয়ার্ড নিশ্চিতকরণ দেখুন বা লুকান">
                            <svg id="eyeIcon2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <div id="matchBadge" class="match-badge"></div>
                </div>

                <!-- Referral Username (Optional) -->
                <div class="form-group">
                    <label class="form-label" for="regReferral">
                        <span>রেফারেল ইউজারনেম (ঐচ্ছিক)</span>
                    </label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 12 20 22 4 22 4 12"></polyline>
                            <rect x="2" y="7" width="20" height="5"></rect>
                            <line x1="12" y1="22" x2="12" y2="7"></line>
                        </svg>
                        <input type="text" id="regReferral" class="form-input" placeholder="রেফারার থাকলে লিখুন">
                    </div>
                </div>

                <!-- Terms & Policy -->
                <div class="terms-box">
                    <label class="terms-label">
                        <input type="checkbox" id="acceptTerms" class="terms-checkbox" required>
                        <span>
                            আমি Bondhoo প্ল্যাটফর্মের <a href="#" onclick="event.preventDefault(); alert('Bondhoo ব্যবহারের শর্তাবলী: পারস্পরিক শ্রদ্ধা বজায় রাখুন এবং নিরাপদ কমিউনিটি গড়ে তুলুন।');">ব্যবহারের শর্তাবলী</a> এবং <a href="#" onclick="event.preventDefault(); alert('Bondhoo প্রাইভেসি পলিসি: আপনার ব্যক্তিগত তথ্য ও গোপনীয়তা সর্বদা সুরক্ষিত।');">প্রাইভেসি পলিসি</a> পড়েছি ও এতে পূর্ণ সম্মতি প্রদান করছি।
                        </span>
                    </label>
                </div>

                <button type="submit" id="submitBtn" class="btn-submit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <line x1="19" y1="8" x2="19" y2="14"></line>
                        <line x1="22" y1="11" x2="16" y2="11"></line>
                    </svg>
                    <span>নিবন্ধন সম্পন্ন করুন</span>
                </button>
            </form>

            <div class="bottom-login-cta">
                ইতিমধ্যে একটি অ্যাকাউন্ট আছে?
                <a href="/login">লগইন করুন &rarr;</a>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} <strong>Bondhoo</strong>. সর্বস্বত্ব সংরক্ষিত। আধুনিক সামাজিক যোগাযোগের নিরাপদ ঠিকানা।</p>
    </footer>

    <script>
        function togglePasswordVisibility(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (!field || !icon) return;

            if (field.type === 'password') {
                field.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                field.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }

        function checkStrength(pass) {
            let score = 0;
            if (pass.length >= 12) score += 25;
            if (/[a-z]/.test(pass)) score += 25;
            if (/[A-Z]/.test(pass)) score += 20;
            if (/[0-9]/.test(pass)) score += 15;
            if (/[@$!%*#?&^_-]/.test(pass)) score += 15;

            const meter = document.getElementById('meterFill');
            const label = document.getElementById('strengthText');
            if (!meter || !label) return;

            meter.style.width = Math.min(score, 100) + '%';

            if (!pass) {
                meter.style.background = '#e2e8f0';
                label.innerText = 'পাসওয়ার্ড নিরাপত্তা স্কোর: অপেক্ষমান';
                label.style.color = 'var(--bh-text-secondary)';
            } else if (score < 40) {
                meter.style.background = '#ef4444';
                label.innerText = 'পাসওয়ার্ড নিরাপত্তা স্কোর: দুর্বল (ন্যূনতম ১২ অক্ষর ও মিশ্র ক্যারেক্টার দিন)';
                label.style.color = '#ef4444';
            } else if (score < 75) {
                meter.style.background = '#f59e0b';
                label.innerText = 'পাসওয়ার্ড নিরাপত্তা স্কোর: মাঝারি';
                label.style.color = '#d97706';
            } else {
                meter.style.background = '#10b981';
                label.innerText = 'পাসওয়ার্ড নিরাপত্তা স্কোর: শক্তিশালী ও নিরাপদ ✓';
                label.style.color = '#10b981';
            }
        }

        function checkPasswordMatch() {
            const pass = document.getElementById('regPassword').value;
            const passConfirm = document.getElementById('regPasswordConfirm').value;
            const badge = document.getElementById('matchBadge');
            if (!badge) return;

            if (!passConfirm) {
                badge.className = 'match-badge';
                badge.innerText = '';
                return;
            }

            if (pass === passConfirm) {
                badge.className = 'match-badge matched';
                badge.innerText = '✓ পাসওয়ার্ড মিলেছে';
            } else {
                badge.className = 'match-badge unmatched';
                badge.innerText = '✕ পাসওয়ার্ড মিলছে না';
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const alertBox = document.getElementById('alertBox');
            const alertText = document.getElementById('alertText');
            const submitBtn = document.getElementById('submitBtn');

            function showError(msg) {
                alertBox.className = 'alert-box alert-error';
                alertText.innerText = msg;
                alertBox.style.display = 'flex';
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function showSuccess(msg) {
                alertBox.className = 'alert-box alert-success';
                alertText.innerText = msg;
                alertBox.style.display = 'flex';
            }

            const pass = document.getElementById('regPassword').value;
            const passConfirm = document.getElementById('regPasswordConfirm').value;

            if (pass !== passConfirm) {
                showError('পাসওয়ার্ড এবং কনফার্ম পাসওয়ার্ড মেলেনি।');
                return;
            }

            if (pass.length < 8) {
                showError('পাসওয়ার্ড অবশ্যই কমপক্ষে ৮ অক্ষরের হতে হবে।');
                return;
            }

            if (!document.getElementById('acceptTerms').checked) {
                showError('অনুগ্রহ করে শর্তাবলী এবং প্রাইভেসি পলিসিতে সম্মতি দিন।');
                return;
            }

            alertBox.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin-anim" style="animation: spin 1s linear infinite;">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                </svg>
                <span>অ্যাকাউন্ট তৈরি হচ্ছে...</span>
            `;

            const firstName = document.getElementById('regFirstName').value.trim();
            const lastName = document.getElementById('regLastName').value.trim();

            const payload = {
                first_name: firstName,
                last_name: lastName,
                name: `${firstName} ${lastName}`.trim(),
                username: document.getElementById('regUsername').value.trim(),
                email: document.getElementById('regEmail').value.trim(),
                phone: document.getElementById('regPhone').value.trim(),
                country: document.getElementById('regCountry').value,
                birth_date: document.getElementById('regDob').value,
                gender: document.querySelector('input[name="gender"]:checked').value,
                password: pass,
                password_confirmation: passConfirm,
                referred_by: document.getElementById('regReferral').value.trim() || null,
                terms: document.getElementById('acceptTerms').checked,
            };

            try {
                const res = await fetch('/api/v2/auth/register', {
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
                    sessionStorage.setItem('verify_email_target', payload.email);
                    sessionStorage.setItem('verify_phone_target', payload.phone);

                    showSuccess('নিবন্ধন সফল হয়েছে! ভেরিফিকেশন পাতায় নিয়ে যাওয়া হচ্ছে...');

                    setTimeout(() => {
                        window.location.href = '/verify-email';
                    }, 800);
                } else {
                    let errMsg = data.message || 'নিবন্ধনে সমস্যা হয়েছে।';
                    if (data.errors) {
                        const firstError = Object.values(data.errors)[0];
                        if (firstError) errMsg = firstError[0];
                    }
                    showError(errMsg);
                }
            } catch (err) {
                showError('সার্ভার কানেকশন ত্রুটি। অনুগ্রহ করে আবার চেষ্টা করুন।');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <line x1="19" y1="8" x2="19" y2="14"></line>
                        <line x1="22" y1="11" x2="16" y2="11"></line>
                    </svg>
                    <span>নিবন্ধন সম্পন্ন করুন</span>
                `;
            }
        }
    </script>
</body>
</html>
