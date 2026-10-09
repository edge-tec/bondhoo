<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ইমেইল ও নোটিফিকেশন সেটিংস — Bondhoo</title>
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
        .container { max-width: 760px; margin: 0 auto; }
        .header-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--fb-border); }
        .logo { font-size: 26px; font-weight: 800; color: var(--fb-primary); text-decoration: none; }
        .nav-links { display: flex; gap: 10px; }
        .nav-link { font-size: 14px; font-weight: 600; color: var(--fb-text-secondary); text-decoration: none; padding: 6px 12px; border-radius: 6px; background: #e4e6eb; transition: background 0.2s; }
        .nav-link:hover { background: #d8dadf; }

        .page-title { font-size: 24px; font-weight: 800; margin-bottom: 6px; }
        .page-subtitle { font-size: 14px; color: var(--fb-text-secondary); margin-bottom: 24px; line-height: 1.5; }

        .card { background: var(--fb-card-bg); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #dddfe2; padding: 24px; margin-bottom: 20px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; border-bottom: 1px solid #f0f2f5; padding-bottom: 12px; }
        .card-title { font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 8px; color: #1e293b; }

        .switch-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid #f1f5f9; }
        .switch-row:last-child { border-bottom: none; }
        .switch-info { flex: 1; padding-right: 20px; }
        .switch-title { font-size: 14px; font-weight: 600; color: #0f172a; margin-bottom: 2px; }
        .switch-desc { font-size: 12px; color: #64748b; line-height: 1.4; }

        /* Custom Toggle Switch */
        .switch { position: relative; display: inline-block; width: 46px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        input:checked + .slider { background-color: var(--fb-primary); }
        input:checked + .slider:before { transform: translateX(20px); }
        input:disabled + .slider { opacity: 0.6; cursor: not-allowed; background-color: #94a3b8; }

        .btn-save { background: var(--fb-primary); color: white; border: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; transition: background 0.2s; box-shadow: 0 2px 6px rgba(24,119,242,0.3); }
        .btn-save:hover { background: #166fe5; }
        .alert-box { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: none; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-bar">
            <a href="/dashboard" class="logo">bondhoo</a>
            <div class="nav-links">
                <a href="/dashboard" class="nav-link">ড্যাশবোর্ড</a>
                <a href="/settings/two-factor" class="nav-link">2FA সিকিউরিটি</a>
                <a href="/devices" class="nav-link">সেশন</a>
            </div>
        </div>

        <h1 class="page-title">ইমেইল ও নোটিফিকেশন পছন্দসমূহ</h1>
        <p class="page-subtitle">আপনি Bondhoo থেকে কোন কোন কার্যকলাপের জন্য ইমেইল নোটিফিকেশন পেতে চান তা নির্ধারণ করুন।</p>

        <div id="saveAlert" class="alert-box" style="background: #e7f3ff; color: #1877f2; border: 1px solid #1877f2;">
            নোটিফিকেশন সেটিংস সফলভাবে সংরক্ষিত হয়েছে! ✅
        </div>

        <form id="preferencesForm" onsubmit="event.preventDefault(); savePreferences();">
            <!-- 1. Master Toggle -->
            <div class="card">
                <div class="switch-row" style="padding: 0;">
                    <div class="switch-info">
                        <div class="switch-title" style="font-size: 16px; font-weight: 700;">ইমেইল নোটিফিকেশন সিস্টেম</div>
                        <div class="switch-desc">সকল ধরনের সোশ্যাল ও অ্যাকাউন্টের নোটিফিকেশন ইমেইল চালু বা বন্ধ রাখুন।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="email_notifications" name="email_notifications" {{ ($settings->email_notifications ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <!-- 2. Account & Security (Mandatory) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">🔒 অ্যাকাউন্ট ও নিরাপত্তা নোটিফিকেশন (বাধ্যতামূলক)</div>
                    <span style="font-size: 11px; background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 12px; font-weight: 700;">সর্বদা সক্রিয়</span>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">পাসওয়ার্ড পরিবর্তন ও রিসেট সতর্কতা</div>
                        <div class="switch-desc">পাসওয়ার্ড পরিবর্তন, রিসেট লিঙ্ক বা ওটিপি সংক্রান্ত ইমেইল।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" checked disabled>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">নতুন ডিভাইস লগইন ও সন্দেহজনক তৎপরতা</div>
                        <div class="switch-desc">অপরিচিত স্থান বা ডিভাইস থেকে অ্যাকাউন্টে প্রবেশের অ্যালার্ট।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" checked disabled>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">ইমেইল ভেরিফিকেশন ও অ্যাকাউন্ট সক্রিয়করণ</div>
                        <div class="switch-desc">অ্যাকাউন্টের বৈধতা যাচাইয়ের নোটিফিকেশন।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" checked disabled>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <!-- 3. Social Activity -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">👥 ফ্রেন্ডশিপ ও সোশ্যাল অ্যাক্টিভিটি</div>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">নতুন ফ্রেন্ড রিকোয়েস্ট (Friend Requests)</div>
                        <div class="switch-desc">কেউ ফ্রেন্ড রিকোয়েস্ট পাঠালে প্রেরকের তথ্যসহ ইমেইল পাবেন।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="friend_request_alerts" name="friend_request_alerts" {{ ($settings->friend_request_alerts ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">ফ্রেন্ড রিকোয়েস্ট গ্রহণ (Friend Request Accepted)</div>
                        <div class="switch-desc">আপনার পাঠানো ফ্রেন্ড রিকোয়েস্ট গৃহীত হলে নোটিফিকেশন পাবেন।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="friend_accepted_alerts" name="friend_accepted_alerts" {{ ($settings->friend_accepted_alerts ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">ব্যক্তিগত বার্তা (Direct Messages)</div>
                        <div class="switch-desc">ইনবক্সে নতুন মেসেজ আসলে ইমেইলে সতর্কবার্তা পাঠানো হবে।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="message_alerts" name="message_alerts" {{ ($settings->message_alerts ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">কমেন্ট ও রিপ্লাই (Comments & Replies)</div>
                        <div class="switch-desc">আপনার পোস্টে কেউ মন্তব্য বা উত্তরের উত্তর দিলে নোটিফিকেশন পাবেন।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="comment_alerts" name="comment_alerts" {{ ($settings->comment_alerts ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">মেনশন ও ট্যাগ (Mentions)</div>
                        <div class="switch-desc">কোনো পোস্টে বা কমেন্টে আপনার নাম মেনশন করা হলে ইমেইল পাবেন।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="mention_alerts" name="mention_alerts" {{ ($settings->mention_alerts ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">লাইক ও রিঅ্যাকশন (Likes & Reactions)</div>
                        <div class="switch-desc">পোস্টে লাইক বা রিঅ্যাকশনের নোটিফিকেশন।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="like_alerts" name="like_alerts" {{ ($settings->like_alerts ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">শেয়ার (Post Shares)</div>
                        <div class="switch-desc">কেউ আপনার পোস্ট নিজের টাইমলাইনে শেয়ার করলে ইমেইল পাবেন।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="share_alerts" name="share_alerts" {{ ($settings->share_alerts ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">নতুন ফলোয়ার (Followers)</div>
                        <div class="switch-desc">কেউ আপনাকে ফলো করতে শুরু করলে নোটিফিকেশন পাবেন।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="follower_alerts" name="follower_alerts" {{ ($settings->follower_alerts ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <!-- 4. Pages & Groups -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📄 পেজ ও গ্রুপ অ্যাক্টিভিটি</div>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">পেজের গুরুত্বপূর্ণ নোটিফিকেশন (Pages)</div>
                        <div class="switch-desc">আপনার পরিচালনাধীন পেজের গুরুত্বপূর্ণ কার্যক্রম ও আপডেট।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="page_alerts" name="page_alerts" {{ ($settings->page_alerts ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-row">
                    <div class="switch-info">
                        <div class="switch-title">গ্রুপ নোটিফিকেশন (Groups)</div>
                        <div class="switch-desc">যুক্ত থাকা গ্রুপের পোস্ট ও মেম্বার রিকোয়েস্টের আপডেট।</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="group_alerts" name="group_alerts" {{ ($settings->group_alerts ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <!-- 5. Bondhoo Messenger Sounds & Ringtone System -->
            <div class="card" style="border-left: 4px solid #1877f2;">
                <div class="card-header">
                    <div class="card-title">🔔 মেসেঞ্জার সাউন্ড ও রিংটোন সেটিংস</div>
                    <span style="font-size: 11px; background: #e7f3ff; color: #1877f2; padding: 3px 8px; border-radius: 12px; font-weight: 700;">৩টি স্বতন্ত্র অডিও সাউন্ড</span>
                </div>
                <div style="padding: 10px 0;">
                    <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 14px;">
                        ইনকামিং কল রিংটোন, ইনকামিং মেসেজ টোন এবং আউটগোয়িং মেসেজ সেন্ট টোন সম্পূর্ণ আলাদাভাবে কনফিগার ও প্রিভিউ করুন। প্রতিটি সাউন্ডের ভলিউম ও সক্রিয়তা স্বাধীনভাবে নিয়ন্ত্রণ করা যায়।
                    </p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" onclick="openMessengerSoundSettings()" style="background: #f0f2f5; color: #0f172a; border: 1px solid #cbd5e1; padding: 10px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
                            <span>🎧</span>
                            <span>মেসেঞ্জার সাউন্ড সেটিংস কনফিগার করুন</span>
                        </button>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-bottom: 40px;">
                <button type="submit" id="btnSubmit" class="btn-save">সেটিংস সংরক্ষণ করুন 💾</button>
            </div>
        </form>
    </div>

    @include('partials.messenger-sound-settings-modal')
    <script src="/js/bondhoo-sound-manager.js"></script>

    <script>
        async function savePreferences() {
            const btn = document.getElementById('btnSubmit');
            const alertBox = document.getElementById('saveAlert');
            const originalText = btn.innerText;

            btn.innerText = 'সংরক্ষণ হচ্ছে... ⏳';
            btn.disabled = true;

            const payload = {
                email_notifications: document.getElementById('email_notifications').checked,
                friend_request_alerts: document.getElementById('friend_request_alerts').checked,
                friend_accepted_alerts: document.getElementById('friend_accepted_alerts').checked,
                message_alerts: document.getElementById('message_alerts').checked,
                comment_alerts: document.getElementById('comment_alerts').checked,
                mention_alerts: document.getElementById('mention_alerts').checked,
                like_alerts: document.getElementById('like_alerts').checked,
                share_alerts: document.getElementById('share_alerts').checked,
                follower_alerts: document.getElementById('follower_alerts').checked,
                page_alerts: document.getElementById('page_alerts').checked,
                group_alerts: document.getElementById('group_alerts').checked,
                security_alerts: true
            };

            const token = localStorage.getItem('jugajug_token') || localStorage.getItem('bondhoo_token') || '';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const res = await fetch('/api/v2/notifications/preferences', {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        ...(token ? { 'Authorization': 'Bearer ' + token } : {})
                    },
                    body: JSON.stringify(payload)
                });

                alertBox.style.display = 'block';
                alertBox.style.background = '#e7f3ff';
                alertBox.style.color = '#1877f2';
                alertBox.innerText = 'নোটিফিকেশন পছন্দসমূহ সফলভাবে সংরক্ষিত হয়েছে! ✅';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (err) {
                alertBox.style.display = 'block';
                alertBox.style.background = '#ffebe8';
                alertBox.style.color = '#e41e3f';
                alertBox.innerText = 'সংরক্ষণ করতে সমস্যা হয়েছে: ' + err.message;
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
