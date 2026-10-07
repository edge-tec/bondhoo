<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>টু-ফ্যাক্টর অথেনটিকেশন — Bondhoo</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/bondhoo-favicon.png">
    <link rel="apple-touch-icon" href="/images/bondhoo-icon-192.png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --fb-bg: #f0f2f5;
            --fb-primary: #1877f2;
            --fb-green: #42b72a;
            --fb-red: #e41e3f;
            --fb-border: #ccd0d5;
            --fb-card-bg: #ffffff;
            --fb-text-primary: #050505;
            --fb-text-secondary: #65676b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Hind Siliguri', 'Inter', sans-serif; }
        body { background: var(--fb-bg); color: var(--fb-text-primary); min-height: 100vh; padding: 24px 16px; }
        .container { max-width: 720px; margin: 0 auto; }
        .header-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--fb-border); }
        .logo { font-size: 26px; font-weight: 800; color: var(--fb-primary); text-decoration: none; }
        .nav-links { display: flex; gap: 10px; }
        .nav-link { font-size: 14px; font-weight: 600; color: var(--fb-text-secondary); text-decoration: none; padding: 6px 12px; border-radius: 6px; background: #e4e6eb; transition: background 0.2s; }
        .nav-link:hover { background: #d8dadf; }

        .page-title { font-size: 24px; font-weight: 700; margin-bottom: 6px; }
        .page-subtitle { font-size: 14px; color: var(--fb-text-secondary); margin-bottom: 20px; line-height: 1.4; }

        .alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 18px; }
        .alert-success { background: #e7f3ff; color: #1877f2; border: 1px solid #1877f2; }
        .alert-error { background: #ffebe8; color: #e41e3f; border: 1px solid #e41e3f; }

        .card { background: var(--fb-card-bg); border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); border: 1px solid #dddfe2; padding: 22px; margin-bottom: 20px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .card-title { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        .status-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 12px; }
        .badge-active { background: #e7f8ec; color: #1e8e3e; }
        .badge-disabled { background: #f0f2f5; color: var(--fb-text-secondary); }

        .btn { padding: 10px 18px; font-size: 14px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer; transition: background 0.2s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-primary { background: var(--fb-primary); color: white; }
        .btn-primary:hover { background: #166fe5; }
        .btn-danger { background: var(--fb-red); color: white; }
        .btn-danger:hover { background: #cc1836; }
        .btn-secondary { background: #e4e6eb; color: #050505; }
        .btn-secondary:hover { background: #d8dadf; }

        .qr-section { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px dashed var(--fb-border); margin-bottom: 20px; }
        .qr-img { width: 180px; height: 180px; border-radius: 8px; border: 1px solid #ccd0d5; background: white; padding: 8px; margin-bottom: 14px; }
        .secret-box { background: white; border: 1px solid #ccd0d5; border-radius: 6px; padding: 8px 14px; font-family: monospace; font-size: 16px; font-weight: 700; color: #1c1e21; letter-spacing: 2px; margin-bottom: 8px; word-break: break-all; }

        .code-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; margin: 16px 0; }
        .code-pill { background: #f0f2f5; border: 1px solid #ccd0d5; border-radius: 6px; padding: 8px; text-align: center; font-family: monospace; font-weight: 700; font-size: 14px; color: #050505; }

        .input-field { width: 100%; height: 44px; border: 1px solid var(--fb-border); border-radius: 6px; padding: 0 12px; font-size: 15px; outline: none; margin-bottom: 14px; }
        .input-field:focus { border-color: var(--fb-primary); }

        .apps-list { display: flex; gap: 14px; flex-wrap: wrap; margin: 12px 0 18px; }
        .app-badge { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: #e7f3ff; border-radius: 6px; font-size: 13px; font-weight: 600; color: var(--fb-primary); }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation Header -->
        <header class="header-bar">
            <a href="/" class="logo" style="display: inline-flex; align-items: center;">
                <img src="/images/bondhoo-logo.png" alt="Bondhoo" style="height: 36px; max-width: 160px; object-fit: contain;">
            </a>
            <div class="nav-links">
                <a href="/devices" class="nav-link" style="display:inline-flex;align-items:center;gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    <span>আমার ডিভাইসসমূহ</span>
                </a>
                <a href="/dashboard" class="nav-link">← ড্যাশবোর্ড</a>
            </div>
        </header>

        <h1 class="page-title" style="display:flex;align-items:center;gap:10px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--fb-primary);"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            <span>টু-ফ্যাক্টর অথেনটিকেশন (2FA)</span>
        </h1>
        <p class="page-subtitle">আপনার অ্যাকাউন্টে সর্বোচ্চ স্তরের সুরক্ষা নিশ্চিত করুন। লগইন করার সময় পাসওয়ার্ডের পাশাপাশি আপনার অথেনটিকেটর অ্যাপের ওটিপি কোড প্রয়োজন হবে।</p>

        @if(session('success'))
            <div class="alert alert-success">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                ✕ {{ $errors->first() }}
            </div>
        @endif

        <!-- Status Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <span>স্ট্যাটাস ও কনফিগারেশন</span>
                    @if($isEnabled)
                        <span class="status-badge badge-active">● সক্রিয় (Enabled)</span>
                    @else
                        <span class="status-badge badge-disabled">○ নিষ্ক্রিয় (Disabled)</span>
                    @endif
                </h2>
            </div>

            @if($isEnabled)
                <p style="font-size: 14px; line-height: 1.5; margin-bottom: 16px;">
                    আপনার অ্যাকাউন্টে টু-ফ্যাক্টর অথেনটিকেশন চালু রয়েছে। অবশিষ্ট ব্যাকআপ রিকভারি কোড: <strong>{{ $recoveryCodesCount }}</strong> টি।
                </p>

                <!-- Disable Form -->
                <form action="{{ route('settings.two-factor.disable') }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে টু-ফ্যাক্টর অথেনটিকেশন বন্ধ করতে চান?');" style="margin-top: 14px;">
                    @csrf
                    <div style="max-width: 320px;">
                        <input type="password" name="password" class="input-field" placeholder="নিষ্ক্রিয় করতে অ্যাকাউন্ট পাসওয়ার্ড দিন" required>
                        <button type="submit" class="btn btn-danger">নিষ্ক্রিয় করুন (Disable 2FA)</button>
                    </div>
                </form>
            @else
                <p style="font-size: 14px; line-height: 1.5; color: var(--fb-text-secondary); margin-bottom: 18px;">
                    নিরাপত্তা বৃদ্ধির জন্য নিচের বাটনে ক্লিক করে Google Authenticator বা Microsoft Authenticator দিয়ে সেটআপ করুন।
                </p>

                <div class="apps-list">
                    <div class="app-badge" style="display:inline-flex;align-items:center;gap:6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                        <span>Google Authenticator রেডি</span>
                    </div>
                    <div class="app-badge" style="display:inline-flex;align-items:center;gap:6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>Microsoft Authenticator রেডি</span>
                    </div>
                    <div class="app-badge" style="display:inline-flex;align-items:center;gap:6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"></circle><path d="m21 2-9.6 9.6"></path><path d="m15.5 7.5 3 3L22 7l-3-3"></path></svg>
                        <span>RFC 6238 TOTP স্ট্যান্ডার্ড</span>
                    </div>
                </div>

                @if(!session('setupData'))
                    <form action="{{ route('settings.two-factor.setup') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">সেটআপ শুরু করুন (Start 2FA Setup)</button>
                    </form>
                @endif
            @endif
        </div>

        <!-- Setup Details Box (When Setup Initiated) -->
        @if(session('setupData'))
            @php $setup = session('setupData'); @endphp
            <div class="card">
                <h2 class="card-title" style="margin-bottom: 16px; display:flex; align-items:center; gap:8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    <span>অথেনটিকেটর অ্যাপে কিউআর কোড স্ক্যান করুন</span>
                </h2>

                <div class="qr-section">
                    <img src="{{ $setup['qr_code_url'] }}" alt="2FA QR Code" class="qr-img">
                    <p style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 6px;">কিউআর স্ক্যান করতে না পারলে নিচের সিক্রেট কি ম্যানুয়ালি যুক্ত করুন:</p>
                    <div class="secret-box">{{ $setup['secret'] }}</div>
                </div>

                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 6px; display:flex; align-items:center; gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>ব্যাকআপ রিকভারি কোডসমূহ (Backup Codes)</span>
                    </h3>
                    <p style="font-size: 13px; color: var(--fb-text-secondary);">মোবাইল হারিয়ে গেলে লগইন করতে এই কোডগুলো নিরাপদ স্থানে সংরক্ষণ করুন। প্রতিটি কোড একবারই ব্যবহার করা যাবে।</p>
                    <div class="code-grid">
                        @foreach($setup['recovery_codes'] as $code)
                            <div class="code-pill">{{ $code }}</div>
                        @endforeach
                    </div>
                </div>

                <!-- Enable Form -->
                <form action="{{ route('settings.two-factor.enable') }}" method="POST">
                    @csrf
                    <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 8px;">৬ সংখ্যার ওটিপি কোড দিয়ে নিশ্চিত করুন:</h3>
                    <div style="display: flex; gap: 10px; max-width: 360px;">
                        <input type="text" name="code" class="input-field" placeholder="যেমন: 123456" maxlength="8" required autocomplete="one-time-code" style="margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary">চালু করুন</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</body>
</html>
