<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাডমিন সিকিউরিটি লগইন — Bondhoo Enterprise Console</title>
    <meta name="description" content="Bondhoo সোশ্যাল নেটওয়ার্ক সেন্ট্রাল অ্যাডমিনিস্ট্রেটিভ ও সিকিউরিটি কনসোল প্রবেশদ্বার।">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/bondhoo-favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --adm-bg: #0b1329;
            --adm-card: #131f37;
            --adm-border: #1e2e4f;
            --adm-primary: #3b82f6;
            --adm-primary-hover: #2563eb;
            --adm-accent: #06b6d4;
            --adm-danger: #ef4444;
            --adm-success: #10b981;
            --adm-text-main: #f8fafc;
            --adm-text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Hind Siliguri', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: radial-gradient(circle at 50% 20%, #172554 0%, #090d16 100%);
            color: var(--adm-text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .header-strip {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(11, 19, 41, 0.7);
            backdrop-filter: blur(12px);
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--adm-text-main);
        }

        .brand-badge img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        .brand-title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .sec-level-tag {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.35);
            color: #93c5fd;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #3b82f6;
            box-shadow: 0 0 8px #3b82f6;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.85); }
        }

        .login-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .login-box {
            background: var(--adm-card);
            border: 1px solid var(--adm-border);
            border-radius: 14px;
            max-width: 440px;
            width: 100%;
            padding: 36px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
        }

        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .shield-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(6, 182, 212, 0.2));
            border: 1px solid rgba(59, 130, 246, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            color: #60a5fa;
        }

        .login-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--adm-text-main);
            margin-bottom: 6px;
        }

        .login-subtitle {
            font-size: 13px;
            color: var(--adm-text-muted);
            line-height: 1.5;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            font-size: 13px;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            font-size: 13px;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 6px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            display: flex;
            align-items: center;
            pointer-events: none;
        }

        .form-ctrl {
            width: 100%;
            height: 46px;
            background: #0b132b;
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 0 14px 0 42px;
            color: #f8fafc;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }

        .form-ctrl:focus {
            border-color: var(--adm-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            background: #0f172a;
        }

        .form-ctrl::placeholder {
            color: #475569;
        }

        .pwd-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
        }

        .pwd-toggle:hover {
            color: #94a3b8;
        }

        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 13px;
            color: #94a3b8;
            user-select: none;
        }

        .checkbox-label input {
            accent-color: var(--adm-primary);
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .btn-admin-submit {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }

        .btn-admin-submit:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.45);
        }

        .btn-admin-submit:active {
            transform: translateY(0);
        }

        .security-footer-note {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid var(--adm-border);
            font-size: 11px;
            color: #64748b;
            text-align: center;
            line-height: 1.5;
        }

        footer {
            padding: 16px;
            text-align: center;
            font-size: 12px;
            color: #475569;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body>
    <div class="header-strip">
        <a href="/" class="brand-badge">
            <img src="/images/bondhoo-icon.png" alt="Bondhoo">
            <span class="brand-title">Bondhoo Admin</span>
        </a>
        <div class="sec-level-tag">
            <span class="pulse-dot"></span>
            <span>RESTRICTED ACCESS · LEVEL 4</span>
        </div>
    </div>

    <div class="login-wrap">
        <div class="login-box">
            <div class="login-header">
                <div class="shield-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <h1 class="login-title">অ্যাডমিন সিকিউরিটি কনসোল</h1>
                <p class="login-subtitle">শুধুমাত্র অনুমোদিত সিস্টেম ও প্ল্যাটফর্ম অ্যাডমিনিস্ট্রেটরদের প্রবেশের অনুমতি রয়েছে</p>
            </div>

            @if (session('success'))
                <div class="alert-success">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('admin.login') }}" method="POST" id="adminLoginForm">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="identifier">অ্যাডমিন আইডেন্টিফায়ার (ইমেইল / ইউজারনেম)</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <input type="text" id="identifier" name="identifier" class="form-ctrl" placeholder="আপনার অনুমোদিত অ্যাডমিন আইডি" value="{{ old('identifier') }}" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">সিকিউরিটি পাসওয়ার্ড</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </span>
                        <input type="password" id="password" name="password" class="form-ctrl" placeholder="••••••••••••" required autocomplete="current-password" style="padding-right: 42px;">
                        <button type="button" class="pwd-toggle" onclick="togglePassword()" title="পাসওয়ার্ড দেখুন">
                            <svg id="eyeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>

                <div class="form-row">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span>এই ডিভাইসে সেশন মনে রাখুন</span>
                    </label>
                </div>

                <button type="submit" class="btn-admin-submit" id="btnSubmit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    <span>অ্যাডমিন প্যানেলে প্রবেশ করুন</span>
                </button>
            </form>

            <div class="security-footer-note">
                🔒 সমস্ত লগইন প্রচেষ্টা, আইপি অ্যাড্রেস ও ডিভাইস ফিঙ্গারপ্রিন্ট নিরীক্ষণ ও অডিট লগে সংরক্ষণ করা হয়। অননুমোদিত প্রবেশ আইনত দণ্ডনীয়।
            </div>
        </div>
    </div>

    <footer>
        Bondhoo Enterprise Infrastructure &bull; All Security Operations Monitored &bull; &copy; {{ date('Y') }}
    </footer>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            if (pwd.type === 'password') {
                pwd.type = 'text';
            } else {
                pwd.type = 'password';
            }
        }

        document.getElementById('adminLoginForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.innerHTML = '<span>যাচাই করা হচ্ছে...</span>';
        });
    </script>
</body>
</html>
