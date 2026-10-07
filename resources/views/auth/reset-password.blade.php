<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>নতুন পাসওয়ার্ড নির্ধারণ — Bondhoo</title>
    <meta name="description" content="Bondhoo অ্যাকাউন্টের জন্য নতুন শক্তিশালী পাসওয়ার্ড নির্ধারণ করুন।">
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
        .title { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .desc { font-size: 14px; color: var(--bh-text-secondary); line-height: 1.5; margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
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
        }
        .form-input {
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
        .form-input:focus {
            border-color: var(--bh-border-focus);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
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
        }
        .meter {
            height: 6px;
            background: #e2e8f0;
            border-radius: 9999px;
            margin: 6px 0;
            overflow: hidden;
        }
        .meter-fill {
            height: 100%;
            width: 0%;
            border-radius: 9999px;
            transition: width 0.3s ease, background 0.3s ease;
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
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
            transition: all 0.2s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.4);
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
        <h1 class="title">নতুন পাসওয়ার্ড নির্ধারণ করুন</h1>
        <p class="desc">আপনার প্রাপ্ত ৬ সংখ্যার ওটিপি কোড এবং একটি সম্পূর্ণ নতুন শক্তিশালী পাসওয়ার্ড প্রবেশ করান।</p>

        <div id="alertBox" class="alert-box"></div>

        <form onsubmit="handleResetPassword(event)">
            <input type="hidden" id="resetToken">

            <div class="form-group">
                <label class="form-label" for="resetIdentifier">ইমেইল বা মোবাইল নম্বর</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <input type="text" id="resetIdentifier" class="form-input" required>
                </div>
            </div>

            <div class="form-group" id="otpGroup">
                <label class="form-label" for="resetOtp">৬ সংখ্যার ওটিপি (OTP) কোড</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                    </svg>
                    <input type="text" id="resetOtp" class="form-input" placeholder="123456" maxlength="6" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="newPassword">নতুন পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="password" id="newPassword" class="form-input" style="padding-right: 42px;" required oninput="checkStrength(this.value)">
                    <button type="button" class="pw-toggle-btn" onclick="togglePw('newPassword', 'eye1')" aria-label="পাসওয়ার্ড টগল">
                        <svg id="eye1" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <div class="meter"><div id="meterFill" class="meter-fill"></div></div>
            </div>

            <div class="form-group">
                <label class="form-label" for="newPasswordConfirm">নতুন পাসওয়ার্ড নিশ্চিত করুন</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    <input type="password" id="newPasswordConfirm" class="form-input" style="padding-right: 42px;" required>
                    <button type="button" class="pw-toggle-btn" onclick="togglePw('newPasswordConfirm', 'eye2')" aria-label="পাসওয়ার্ড টগল">
                        <svg id="eye2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" id="submitBtn" class="btn-submit">
                <span>পাসওয়ার্ড পরিবর্তন ও লগইন</span>
            </button>
        </form>
    </div>

    <script>
        function togglePw(fieldId, iconId) {
            const f = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (!f || !icon) return;
            if (f.type === 'password') {
                f.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                f.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }

        const urlParams = new URLSearchParams(window.location.search);
        const identifierParam = urlParams.get('identifier') || urlParams.get('email') || sessionStorage.getItem('reset_identifier') || '';
        const tokenParam = urlParams.get('token') || '';

        document.getElementById('resetIdentifier').value = identifierParam;
        if (tokenParam) {
            document.getElementById('resetToken').value = tokenParam;
            const otpGroup = document.getElementById('otpGroup');
            if (otpGroup) otpGroup.style.display = 'none';
            const resetOtp = document.getElementById('resetOtp');
            if (resetOtp) resetOtp.required = false;
            const desc = document.querySelector('.desc');
            if (desc) desc.innerText = 'নিরাপদ রিসেট লিঙ্ক যাচাই করা হয়েছে। আপনার নতুন শক্তিশালী পাসওয়ার্ড প্রবেশ করান।';
        }

        function checkStrength(pass) {
            let score = 0;
            if (pass.length >= 8) score += 30;
            if (/[a-z]/.test(pass) && /[A-Z]/.test(pass)) score += 30;
            if (/[0-9]/.test(pass)) score += 20;
            if (/[@$!%*#?&^_-]/.test(pass)) score += 20;

            const meter = document.getElementById('meterFill');
            meter.style.width = score + '%';
            meter.style.background = score < 60 ? '#ef4444' : (score < 90 ? '#f59e0b' : '#10b981');
        }

        async function handleResetPassword(e) {
            e.preventDefault();
            const alertBox = document.getElementById('alertBox');
            const submitBtn = document.getElementById('submitBtn');

            const id = document.getElementById('resetIdentifier').value.trim();
            const otp = document.getElementById('resetOtp').value.trim();
            const token = document.getElementById('resetToken').value.trim();
            const pass = document.getElementById('newPassword').value;
            const passConfirm = document.getElementById('newPasswordConfirm').value;

            if (pass !== passConfirm) {
                alertBox.className = 'alert-box alert-error';
                alertBox.innerText = 'নতুন পাসওয়ার্ড নিশ্চিতকরণ মেলেনি।';
                alertBox.style.display = 'block';
                return;
            }

            if (pass.length < 8) {
                alertBox.className = 'alert-box alert-error';
                alertBox.innerText = 'পাসওয়ার্ড অবশ্যই কমপক্ষে ৮ অক্ষরের হতে হবে।';
                alertBox.style.display = 'block';
                return;
            }

            alertBox.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.innerText = 'পরিবর্তন করা হচ্ছে...';

            try {
                const res = await fetch('/api/v2/auth/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        identifier: id,
                        otp: otp || undefined,
                        token: token || undefined,
                        password: pass,
                        password_confirmation: passConfirm
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    alertBox.className = 'alert-box alert-success';
                    alertBox.innerText = 'পাসওয়ার্ড সফলভাবে পরিবর্তিত হয়েছে! লগইন পাতায় নিয়ে যাওয়া হচ্ছে...';
                    alertBox.style.display = 'block';

                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 1200);
                } else {
                    let msg = data.errors?.password?.[0] || data.errors?.otp?.[0] || data.errors?.token?.[0] || data.message || 'পাসওয়ার্ড রিসেট ব্যর্থ হয়েছে।';
                    alertBox.className = 'alert-box alert-error';
                    alertBox.innerText = msg;
                    alertBox.style.display = 'block';
                }
            } catch (err) {
                alertBox.className = 'alert-box alert-error';
                alertBox.innerText = 'সার্ভার এরর। আবার চেষ্টা করুন।';
                alertBox.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = 'পাসওয়ার্ড পরিবর্তন ও লগইন';
            }
        }
    </script>
</body>
</html>
