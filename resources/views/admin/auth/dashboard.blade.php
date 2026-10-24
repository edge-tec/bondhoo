<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>অথেন্টিকেশন ও সিকিউরিটি অ্যাডমিন ড্যাশবোর্ড — SocialHub</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="admin-token" content="{{ $adminToken ?? session('admin_api_token') ?? '' }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --fb-bg: #f0f2f5;
            --fb-card: #ffffff;
            --fb-primary: #1877f2;
            --fb-primary-hover: #166fe5;
            --fb-green: #42b72a;
            --fb-red: #e41e3f;
            --fb-yellow: #f7b125;
            --fb-purple: #8a2be2;
            --fb-text-primary: #050505;
            --fb-text-secondary: #65676b;
            --fb-border: #ccd0d5;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Hind Siliguri', 'Inter', sans-serif; }
        body {
            background: var(--fb-bg);
            color: var(--fb-text-primary);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            padding-top: env(safe-area-inset-top, 0px);
            padding-bottom: env(safe-area-inset-bottom, 0px);
            padding-left: env(safe-area-inset-left, 0px);
            padding-right: env(safe-area-inset-right, 0px);
        }

        header { background: white; border-bottom: 1px solid var(--fb-border); min-height: 64px; padding: 10px 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 3px rgba(0,0,0,0.06); gap: 12px; }
        .logo-box { display: flex; align-items: center; gap: 12px; text-decoration: none; flex-wrap: wrap; }
        .logo-circle { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #1877f2, #0052cc); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 22px; box-shadow: 0 2px 6px rgba(24,119,242,0.4); }
        .logo-title { font-weight: 700; font-size: 20px; color: var(--fb-primary); }
        .badge-gov { background: #e7f3ff; color: var(--fb-primary); border: 1px solid #1877f2; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; letter-spacing: 0.3px; }

        .header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .btn-back { color: var(--fb-text-secondary); text-decoration: none; font-weight: 600; font-size: 13px; padding: 8px 14px; border-radius: 6px; border: 1px solid var(--fb-border); background: white; transition: all 0.2s; display: inline-flex; align-items: center; }
        .btn-back:hover { background: #f0f2f5; color: var(--fb-primary); }

        .container { max-width: 1440px; width: 100%; margin: 0 auto; padding: 24px 16px; flex: 1; }

        /* Section Header */
        .section-header { margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .section-title { font-size: 22px; font-weight: 800; color: #1c1e21; word-break: break-word; }
        .section-subtitle { font-size: 13px; color: var(--fb-text-secondary); line-height: 1.5; }

        /* 8 Required Widgets Grid */
        .widgets-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 24px; }
        .widget-card { background: white; border: 1px solid var(--fb-border); border-radius: 10px; padding: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); position: relative; overflow: hidden; }
        .widget-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--fb-primary); }
        .widget-card.green::before { background: var(--fb-green); }
        .widget-card.red::before { background: var(--fb-red); }
        .widget-card.yellow::before { background: var(--fb-yellow); }
        .widget-card.purple::before { background: var(--fb-purple); }

        .widget-title { font-size: 12px; text-transform: uppercase; color: var(--fb-text-secondary); font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .widget-val { font-size: 28px; font-weight: 800; color: #1c1e21; }
        .widget-val.green { color: var(--fb-green); }
        .widget-val.red { color: var(--fb-red); }
        .widget-val.blue { color: var(--fb-primary); }
        .widget-val.purple { color: var(--fb-purple); }
        .widget-sub { font-size: 11px; color: var(--fb-text-secondary); margin-top: 4px; }

        /* Tabs & Controls */
        .tabs-header { display: flex; gap: 6px; border-bottom: 1px solid var(--fb-border); margin-bottom: 20px; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 2px; }
        .tab-btn { padding: 10px 16px; font-size: 14px; font-weight: 600; border: none; background: none; cursor: pointer; color: var(--fb-text-secondary); border-bottom: 3px solid transparent; white-space: nowrap; transition: all 0.2s; flex-shrink: 0; }
        .tab-btn:hover { color: var(--fb-primary); }
        .tab-btn.active { color: var(--fb-primary); border-bottom-color: var(--fb-primary); }

        .controls-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; justify-content: space-between; align-items: center; }
        .search-input { height: 38px; padding: 0 12px; border: 1px solid var(--fb-border); border-radius: 6px; font-size: 13px; width: 280px; max-width: 100%; }
        .filter-select { height: 38px; padding: 0 10px; border: 1px solid var(--fb-border); border-radius: 6px; font-size: 13px; background: white; }

        /* Export Button Group */
        .export-group { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-export { height: 38px; padding: 0 14px; background: var(--fb-primary); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: background 0.2s; white-space: nowrap; }
        .btn-export:hover { background: var(--fb-primary-hover); }
        .btn-export.secondary { background: white; color: var(--fb-text-primary); border: 1px solid var(--fb-border); }
        .btn-export.secondary:hover { background: #f0f2f5; }

        /* Data Tables */
        .table-card { background: white; border: 1px solid var(--fb-border); border-radius: 10px; overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; min-width: 650px; }
        th { background: #f7f8fa; padding: 12px 14px; font-weight: 700; color: #4b4f56; border-bottom: 1px solid var(--fb-border); font-size: 12px; text-transform: uppercase; white-space: nowrap; }
        td { padding: 12px 14px; border-bottom: 1px solid var(--fb-border); vertical-align: middle; word-break: break-word; }
        tr:hover { background: #fafbfc; }

        .user-cell { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: #e4e6eb; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--fb-primary); font-size: 14px; flex-shrink: 0; }
        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; white-space: nowrap; }
        .status-active { background: #eafbe7; color: var(--fb-green); }
        .status-pending { background: #fffbe6; color: #d48806; }
        .status-suspended { background: #fff1f0; color: var(--fb-red); }
        .status-banned { background: #333; color: white; }

        .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-act { padding: 4px 10px; font-size: 11px; font-weight: 600; border-radius: 4px; border: 1px solid var(--fb-border); background: white; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
        .btn-act:hover { background: #f0f2f5; }
        .btn-act.red { color: var(--fb-red); border-color: #ffa39e; }
        .btn-act.red:hover { background: #fff1f0; }
        .btn-act.green { color: var(--fb-green); border-color: #b7eb8f; }
        .btn-act.blue { color: var(--fb-primary); border-color: #91d5ff; }

        /* Analytics Grid */
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .analytics-box { background: white; border: 1px solid var(--fb-border); border-radius: 10px; padding: 18px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
        .analytics-box-title { font-size: 14px; font-weight: 700; color: #1c1e21; margin-bottom: 14px; border-bottom: 1px solid #f0f2f5; padding-bottom: 8px; display: flex; justify-content: space-between; }
        .stat-bar-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 12px; }
        .stat-bar-bg { width: 100%; height: 6px; background: #e4e6eb; border-radius: 3px; overflow: hidden; margin-top: 3px; margin-bottom: 10px; }
        .stat-bar-fill { height: 100%; background: var(--fb-primary); border-radius: 3px; }

        /* Modal */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; padding: 16px; }
        .modal-content { background: white; width: 100%; max-width: min(94vw, 500px); max-height: calc(100dvh - 32px); overflow-y: auto; border-radius: 12px; padding: 22px; box-shadow: 0 8px 30px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .modal-title { font-size: 18px; font-weight: 700; }
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; padding: 4px; }

        @media (max-width: 768px) {
            header { padding: 10px 14px; }
            .header-actions { width: 100%; justify-content: flex-start; }
            .widgets-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .controls-bar { flex-direction: column; align-items: stretch; }
            .search-input { width: 100%; }
            .export-group { width: 100%; justify-content: flex-start; }
            .container { padding: 16px 10px; }
        }

        @media (max-width: 480px) {
            .widgets-grid { grid-template-columns: 1fr; }
            .widget-val { font-size: 24px; }
            .section-title { font-size: 18px; }
            .logo-title { font-size: 17px; }
            .badge-gov { font-size: 10px; padding: 2px 6px; }
            .btn-back { font-size: 12px; padding: 6px 10px; }
        }

        @media (max-width: 374px) {
            .widget-card { padding: 12px; }
            .modal-content { padding: 16px 12px; }
            .section-title { font-size: 16px; }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-box" style="display: flex; align-items: center; gap: 10px;">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:9px;background:#1877f2;color:#fff;box-shadow:0 2px 6px rgba(24,119,242,0.35);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </span>
            <span class="logo-title">SocialHub Admin</span>
            <span class="badge-gov">Enterprise Authentication Console</span>
        </div>
        <div class="header-actions">
            <button onclick="loadAll()" class="btn-back">🔄 রিফ্রেশ ডাটা</button>
            <a href="/dashboard" class="btn-back">← সোশ্যাল প্ল্যাটফর্মে ফিরুন</a>
            <form action="{{ route('admin.logout') }}" method="POST" style="display: inline;" onsubmit="return confirm('আপনি কি নিশ্চিত যে অ্যাডমিন সেশন শেষ করতে চান?');">
                @csrf
                <button type="submit" class="btn-back" style="color: #e41e3f; border-color: #fecaca; background: #fee2e2; cursor: pointer; font-weight: 700;">
                    🚪 অ্যাডমিন লগআউট
                </button>
            </form>
        </div>
    </header>

    <main class="container">
        <!-- Section Header -->
        <div class="section-header">
            <div>
                <h1 class="section-title">অথেন্টিকেশন ও সিকিউরিটি ম্যানেজমেন্ট</h1>
                <p class="section-subtitle">রিয়েল-টাইম সেশন মনিটরিং, ব্রুট-ফোর্স প্রটেকশন, ওটিপি অডিট এবং লগইন অ্যানালিটিক্স</p>
            </div>
            <div class="export-group">
                <a href="/api/v2/admin/auth/export?type=users" class="btn-export">⬇️ Users CSV</a>
                <a href="/api/v2/admin/auth/export?type=sessions" class="btn-export secondary">⬇️ Sessions CSV</a>
                <a href="/api/v2/admin/auth/export?type=logins" class="btn-export secondary">⬇️ Logins CSV</a>
            </div>
        </div>

        <!-- 8 Required Dashboard Widgets -->
        <div class="widgets-grid">
            <!-- 1. Online Users -->
            <div class="widget-card green">
                <div class="widget-title">🟢 Online Users</div>
                <div id="statOnlineUsers" class="widget-val green">--</div>
                <div class="widget-sub">বিগত ১৫ মিনিটে সক্রিয়</div>
            </div>

            <!-- 2. Failed Logins -->
            <div class="widget-card red">
                <div class="widget-title">🛡️ Failed Logins</div>
                <div id="statFailedLogins" class="widget-val red">--</div>
                <div id="statFailedLoginsSub" class="widget-sub">২৪ ঘণ্টায় ব্যর্থ প্রচেষ্টা</div>
            </div>

            <!-- 3. Locked Accounts -->
            <div class="widget-card red">
                <div class="widget-title">🔒 Locked Accounts</div>
                <div id="statLockedAccounts" class="widget-val red">--</div>
                <div class="widget-sub">ব্রুট-ফোর্স প্রতিরোধে লকড</div>
            </div>

            <!-- 4. Active Sessions -->
            <div class="widget-card blue">
                <div class="widget-title">💻 Active Sessions</div>
                <div id="statActiveSessions" class="widget-val blue">--</div>
                <div class="widget-sub">লাইভ ডিভাইস সংযোগ</div>
            </div>

            <!-- 5. OTP Logs -->
            <div class="widget-card yellow">
                <div class="widget-title">📱 OTP Logs</div>
                <div id="statOtpLogs" class="widget-val">--</div>
                <div id="statOtpLogsSub" class="widget-sub">তৈরিকৃত ওটিপি কোড</div>
            </div>

            <!-- 6. Email Verification Logs -->
            <div class="widget-card purple">
                <div class="widget-title">✉️ Email Verification Logs</div>
                <div id="statEmailLogs" class="widget-val purple">--</div>
                <div id="statEmailLogsSub" class="widget-sub">ভেরিফিকেশন টোকেন</div>
            </div>

            <!-- 7. Password Reset Logs -->
            <div class="widget-card">
                <div class="widget-title">🔑 Password Reset Logs</div>
                <div id="statPasswordLogs" class="widget-val">--</div>
                <div id="statPasswordLogsSub" class="widget-sub">পাসওয়ার্ড পরিবর্তন ও রিসেট</div>
            </div>

            <!-- 8. Login Analytics -->
            <div class="widget-card blue">
                <div class="widget-title">📊 Login Analytics</div>
                <div id="statTotalLogins" class="widget-val blue">--</div>
                <div class="widget-sub">সর্বমোট লগইন প্রচেষ্টা</div>
            </div>

            <!-- 9. Citizen Identity Verification Queue -->
            <div class="widget-card purple" style="cursor: pointer;" onclick="switchTab('verifications')">
                <div class="widget-title">🛡️ Identity Verification</div>
                <div id="statVerificationQueue" class="widget-val purple">--</div>
                <div id="statVerificationQueueSub" class="widget-sub">অপেক্ষমান আবেদনসমূহ</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="tabs-header">
            <button class="tab-btn active" data-tab="users" onclick="switchTab('users')">👥 ব্যবহারকারী তালিকা</button>
            <button class="tab-btn" data-tab="sessions" onclick="switchTab('sessions')">💻 সক্রিয় সেশন (Revoke)</button>
            <button class="tab-btn" data-tab="failed" onclick="switchTab('failed')">🛡️ ব্যর্থ লগইন মনিটর</button>
            <button class="tab-btn" data-tab="otps" onclick="switchTab('otps')">📱 ওটিপি অডিট লগ</button>
            <button class="tab-btn" data-tab="emails" onclick="switchTab('emails')">✉️ ইমেইল ভেরিফিকেশন লগ</button>
            <button class="tab-btn" data-tab="passwords" onclick="switchTab('passwords')">🔑 পাসওয়ার্ড রিসেট লগ</button>
            <button class="tab-btn" data-tab="analytics" onclick="switchTab('analytics')">📊 লগইন অ্যানালিটিক্স</button>
            <button class="tab-btn" data-tab="audits" onclick="switchTab('audits')">📜 অডিট ট্রেইল</button>
            <button class="tab-btn" data-tab="media" onclick="switchTab('media')">📹 মিডিয়া ও রিসোর্স কন্ট্রোল</button>
            <button class="tab-btn" data-tab="smtp" onclick="switchTab('smtp')">📧 SMTP ও ইমেইল ম্যানেজমেন্ট</button>
            <button class="tab-btn" data-tab="verifications" onclick="switchTab('verifications')">🛡️ পরিচয় ভেরিফিকেশন কিউ</button>
        </div>

        <!-- TAB 1: USERS -->
        <div id="tabUsers">
            <div class="controls-bar">
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <input type="text" id="userSearch" class="search-input" placeholder="নাম, ইউজারনেম, ইমেইল বা ফোন..." oninput="debounceSearchUsers()">
                    <select id="userStatusFilter" class="filter-select" onchange="loadUsers()">
                        <option value="">সকল স্ট্যাটাস</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="suspended">Suspended</option>
                        <option value="banned">Banned</option>
                    </select>
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" id="userLockedOnly" onchange="loadUsers()"> শুধু লকড অ্যাকাউন্ট
                    </label>
                </div>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>আইডি</th>
                            <th>ব্যবহারকারী</th>
                            <th>যোগাযোগ</th>
                            <th>দেশ</th>
                            <th>স্ট্যাটাস</th>
                            <th>ভেরিফিকেশন</th>
                            <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr><td colspan="7" style="text-align: center; padding: 20px;">ডাটা লোড হচ্ছে...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: SESSIONS (WITH REVOKE) -->
        <div id="tabSessions" style="display: none;">
            <div class="controls-bar">
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <input type="text" id="sessionSearch" class="search-input" placeholder="ডিভাইস নাম, আইপি বা ইউজার দিয়ে খুঁজুন..." oninput="debounceSearchSessions()">
                    <select id="sessionOsFilter" class="filter-select" onchange="loadSessions()">
                        <option value="">সকল ওএস (OS)</option>
                        <option value="iOS">iOS</option>
                        <option value="Android">Android</option>
                        <option value="macOS">macOS</option>
                        <option value="Windows">Windows</option>
                        <option value="Linux">Linux</option>
                    </select>
                </div>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>সেশন আইডি</th>
                            <th>ব্যবহারকারী</th>
                            <th>ডিভাইস নাম</th>
                            <th>ব্রাউজার ও ওএস</th>
                            <th>আইপি ও লোকেশন</th>
                            <th>অবস্থা</th>
                            <th>সর্বশেষ সক্রিয়</th>
                            <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody id="sessionsTableBody">
                        <tr><td colspan="8" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 3: FAILED LOGINS -->
        <div id="tabFailed" style="display: none;">
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>আইডি</th>
                            <th>টার্গেট আইডেন্টিফায়ার</th>
                            <th>আইপি অ্যাড্রেস</th>
                            <th>ডিভাইস / এজেন্ট</th>
                            <th>ব্যর্থতার কারণ</th>
                            <th>চেষ্টার সময়</th>
                        </tr>
                    </thead>
                    <tbody id="failedTableBody">
                        <tr><td colspan="6" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 4: OTP HISTORY -->
        <div id="tabOtps" style="display: none;">
            <div class="controls-bar">
                <input type="text" id="otpSearch" class="search-input" placeholder="মোবাইল বা ইমেইল দিয়ে খুঁজুন..." oninput="debounceSearchOtps()">
                <select id="otpPurposeFilter" class="filter-select" onchange="loadOtps()">
                    <option value="">সকল উদ্দেশ্য (Purpose)</option>
                    <option value="login_2fa">Login 2FA</option>
                    <option value="verify_phone">Verify Phone</option>
                    <option value="verify_email">Verify Email</option>
                    <option value="password_reset">Password Reset</option>
                </select>
            </div>
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>আইডি</th>
                            <th>প্রাপক</th>
                            <th>উদ্দেশ্য</th>
                            <th>স্ট্যাটাস</th>
                            <th>আইপি</th>
                            <th>মেয়াদ / সৃষ্টির সময়</th>
                        </tr>
                    </thead>
                    <tbody id="otpsTableBody">
                        <tr><td colspan="6" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 5: EMAIL VERIFICATIONS -->
        <div id="tabEmails" style="display: none;">
            <div class="controls-bar">
                <input type="text" id="emailSearch" class="search-input" placeholder="ইমেইল দিয়ে খুঁজুন..." oninput="debounceSearchEmails()">
                <select id="emailStatusFilter" class="filter-select" onchange="loadEmails()">
                    <option value="">সকল স্ট্যাটাস</option>
                    <option value="verified">Verified</option>
                    <option value="pending">Pending</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>আইডি</th>
                            <th>ইমেইল এড্রেস</th>
                            <th>ব্যবহারকারী</th>
                            <th>আইপি</th>
                            <th>ভেরিফিকেশন স্ট্যাটাস</th>
                            <th>সৃষ্টির তারিখ</th>
                        </tr>
                    </thead>
                    <tbody id="emailsTableBody">
                        <tr><td colspan="6" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 6: PASSWORD RESETS -->
        <div id="tabPasswords" style="display: none;">
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>আইডি</th>
                            <th>ব্যবহারকারী</th>
                            <th>পাসওয়ার্ড হ্যাশ</th>
                            <th>তারিখ ও সময়</th>
                        </tr>
                    </thead>
                    <tbody id="passwordsTableBody">
                        <tr><td colspan="4" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 7: LOGIN ANALYTICS -->
        <div id="tabAnalytics" style="display: none;">
            <div class="analytics-grid">
                <div class="analytics-box">
                    <div class="analytics-box-title">লগইন ফলাফল (Status Breakdown)</div>
                    <div id="analyticsStatusBox">লোড হচ্ছে...</div>
                </div>
                <div class="analytics-box">
                    <div class="analytics-box-title">শীর্ষ ব্রাউজার (Top Browsers)</div>
                    <div id="analyticsBrowserBox">লোড হচ্ছে...</div>
                </div>
                <div class="analytics-box">
                    <div class="analytics-box-title">অপারেটিং সিস্টেম (Operating Systems)</div>
                    <div id="analyticsOsBox">লোড হচ্ছে...</div>
                </div>
                <div class="analytics-box">
                    <div class="analytics-box-title">ডিভাইস টাইপ (Device Types)</div>
                    <div id="analyticsDeviceBox">লোড হচ্ছে...</div>
                </div>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>তারিখ</th>
                            <th>দৈনিক সফল ও ব্যর্থ লগইন প্রচেষ্টা</th>
                        </tr>
                    </thead>
                    <tbody id="analyticsDaysTableBody">
                        <tr><td colspan="2" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 8: AUDIT LOGS -->
        <div id="tabAudits" style="display: none;">
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>আইডি</th>
                            <th>অ্যাকশন</th>
                            <th>অ্যাডমিন ইউজার</th>
                            <th>টার্গেট এনটিটি</th>
                            <th>আইপি এড্রেস</th>
                            <th>তারিখ ও সময়</th>
                        </tr>
                    </thead>
                    <tbody id="auditsTableBody">
                        <tr><td colspan="6" style="text-align: center; padding: 20px;">লোড হচ্ছে...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 9: MEDIA & RESOURCE PROTECTION -->
        <div id="tabMedia" style="display: none;">
            <!-- Live Media & Resource Widgets -->
            <div class="widgets-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                <div class="widget-card blue">
                    <div class="widget-title">📁 মোট মিডিয়া ফাইল</div>
                    <div id="statTotalMedia" class="widget-val blue">--</div>
                    <div id="statTotalStorage" class="widget-sub">স্টোরেজ সাইজ: -- MB</div>
                </div>
                <div class="widget-card yellow">
                    <div class="widget-title">⚡ সক্রিয় আপলোড সেশন</div>
                    <div id="statActiveUploads" class="widget-val yellow">--</div>
                    <div class="widget-sub">রিসোর্স ও চাঙ্ক প্রটেক্টেড</div>
                </div>
                <div class="widget-card green">
                    <div class="widget-title">⚙️ প্রসেসিং কিউ (জবস)</div>
                    <div id="statQueuedJobs" class="widget-val green">--</div>
                    <div id="statJobsDetail" class="widget-sub">চলমান: -- | ব্যর্থ: --</div>
                </div>
                <div class="widget-card purple">
                    <div class="widget-title">💾 ফ্রি ডিস্ক ও মেমরি</div>
                    <div id="statFreeDisk" class="widget-val purple">-- GB</div>
                    <div id="statMemoryUsage" class="widget-sub">ব্যবহৃত RAM: -- MB</div>
                </div>
            </div>

            <!-- Resource Controls & Limits Form -->
            <div class="table-card" style="padding: 24px; max-width: 800px; margin-top: 16px;">
                <h3 style="font-size: 17px; font-weight: 800; margin-bottom: 14px; color: #1e293b;">🛠️ এন্টারপ্রাইজ মিডিয়া ও সার্ভার প্রটেকশন সেটিংস</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 700; display: block; margin-bottom: 6px;">সর্বোচ্চ ভিডিও সাইজ (MB):</label>
                        <input type="number" id="settingMaxVideoSize" class="search-input" style="width: 100%;" min="1" max="2048">
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 700; display: block; margin-bottom: 6px;">সর্বোচ্চ ছবি সাইজ (MB):</label>
                        <input type="number" id="settingMaxImageSize" class="search-input" style="width: 100%;" min="1" max="100">
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 700; display: block; margin-bottom: 6px;">কনকারেন্ট ব্যাকগ্রাউন্ড ওয়ার্কার্স:</label>
                        <input type="number" id="settingMaxWorkers" class="search-input" style="width: 100%;" min="1" max="16">
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 700; display: block; margin-bottom: 6px;">ন্যূনতম ফ্রি ডিস্ক থ্রেশহোল্ড (MB):</label>
                        <input type="number" id="settingMinFreeDisk" class="search-input" style="width: 100%;" min="100">
                    </div>
                </div>

                <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                    <button type="button" class="btn-export" onclick="saveMediaSettings()">সেটিংস সংরক্ষণ করুন 💾</button>
                </div>
            </div>
        </div>

        <!-- TAB 10: SMTP & EMAIL SYSTEM MANAGEMENT -->
        <div id="tabSmtp" style="display: none;">
            <!-- Live SMTP & Delivery Statistics -->
            <div class="widgets-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
                <div class="widget-card blue">
                    <div class="widget-title">✉️ মোট প্রেরিত ইমেইল</div>
                    <div id="smtpTotalSent" class="widget-val blue">--</div>
                    <div id="smtpTotalSentSub" class="widget-sub">সর্বমোট সফল ডেলিভারি</div>
                </div>
                <div class="widget-card green">
                    <div class="widget-title">📈 ডেলিভারি সফলতার হার</div>
                    <div id="smtpDeliveryRate" class="widget-val green">--%</div>
                    <div class="widget-sub">সফল বনাম ব্যর্থ অনুপাত</div>
                </div>
                <div class="widget-card red">
                    <div class="widget-title">⚠️ ব্যর্থ ইমেইল (Failed)</div>
                    <div id="smtpFailedCount" class="widget-val red">--</div>
                    <div id="smtpFailedSub" class="widget-sub">গত ২৪ ঘণ্টায় ব্যর্থ: --</div>
                </div>
                <div class="widget-card yellow">
                    <div class="widget-title">⏳ অপেক্ষমাণ কিউ (Queued)</div>
                    <div id="smtpQueuedCount" class="widget-val yellow">--</div>
                    <div id="smtpQueuedSub" class="widget-sub">সিস্টেম স্ট্যাটাস: চেক হচ্ছে...</div>
                </div>
            </div>

            <!-- Configuration & Test Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 20px; margin-bottom: 24px;">
                <!-- Card 1: SMTP Server Settings -->
                <div class="table-card" style="padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h3 style="font-size: 16px; font-weight: 800; color: #1e293b;">⚙️ SMTP সার্ভার কনফিগারেশন</h3>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; cursor: pointer;">
                            <input type="checkbox" id="smtpIsEnabled" style="width: 18px; height: 18px;" {{ !empty($smtpSettings['is_enabled']) ? 'checked' : '' }}> ইমেইল সিস্টেম সক্রিয়
                        </label>
                    </div>

                    <form id="smtpSettingsForm" onsubmit="event.preventDefault(); saveSmtpSettings();">
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 12px;">
                            <div>
                                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">SMTP Host *</label>
                                <input type="text" id="smtpHost" class="search-input" style="width: 100%;" placeholder="e.g. mail.socialhub.com" value="{{ $smtpSettings['mail_host'] ?? '' }}" required>
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">Port *</label>
                                <input type="number" id="smtpPort" class="search-input" style="width: 100%;" placeholder="587" value="{{ $smtpSettings['mail_port'] ?? 587 }}" required>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                            <div>
                                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">Username</label>
                                <input type="text" id="smtpUsername" class="search-input" style="width: 100%;" placeholder="SMTP Username" value="{{ $smtpSettings['mail_username'] ?? '' }}">
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">Password</label>
                                <input type="password" id="smtpPassword" class="search-input" style="width: 100%;" placeholder="{{ !empty($smtpSettings['has_password']) ? '•••••••• (সংরক্ষিত - পরিবর্তন করতে নতুন দিন)' : 'নতুন পাসওয়ার্ড দিন...' }}">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                            <div>
                                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">Encryption</label>
                                <select id="smtpEncryption" class="filter-select" style="width: 100%;">
                                    <option value="tls" {{ ($smtpSettings['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS (STARTTLS / 587)</option>
                                    <option value="ssl" {{ ($smtpSettings['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL (Port 465)</option>
                                    <option value="none" {{ ($smtpSettings['mail_encryption'] ?? '') === 'none' ? 'selected' : '' }}>None (Unencrypted)</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">Timeout (সেকেন্ড)</label>
                                <input type="number" id="smtpTimeout" class="search-input" style="width: 100%;" value="{{ $smtpSettings['timeout'] ?? 30 }}" min="5" max="120">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                            <div>
                                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">From Email *</label>
                                <input type="email" id="smtpFromAddress" class="search-input" style="width: 100%;" placeholder="noreply@socialhub.com" value="{{ $smtpSettings['mail_from_address'] ?? '' }}" required>
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">From Name *</label>
                                <input type="text" id="smtpFromName" class="search-input" style="width: 100%;" placeholder="SocialHub" value="{{ $smtpSettings['mail_from_name'] ?? 'SocialHub' }}" required>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                            <div>
                                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">Reply-To Email</label>
                                <input type="email" id="smtpReplyTo" class="search-input" style="width: 100%;" placeholder="support@socialhub.com" value="{{ $smtpSettings['mail_reply_to'] ?? '' }}">
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px;">Rate Limit (প্রতি মিনিটে)</label>
                                <input type="number" id="smtpRateLimit" class="search-input" style="width: 100%;" value="{{ $smtpSettings['rate_limit_per_minute'] ?? 60 }}" min="1" max="1000">
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                                <input type="checkbox" id="smtpAuth" {{ ($smtpSettings['smtp_auth'] ?? true) ? 'checked' : '' }}> SMTP Authentication
                            </label>
                            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                <button type="button" id="btnVerifyConnCard1" class="btn-export secondary" onclick="verifySmtpQuickConnection()" title="সার্ভারের সাথে সরাসরি সকেট, TLS ও লগইন হ্যান্ডশেক পরীক্ষা করুন">কানেকশন টেস্ট 🔌</button>
                                <button type="submit" id="btnSaveSmtp" class="btn-export">সংরক্ষণ ও কার্যকর করুন 💾</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Card 2: Live SMTP Connection & Test Email -->
                <div class="table-card" style="padding: 24px; display: flex; flex-direction: column;">
                    <h3 style="font-size: 16px; font-weight: 800; color: #1e293b; margin-bottom: 8px;">🚀 SMTP কানেকশন ভেরিফিকেশন ও টেস্ট</h3>
                    <p style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 16px;">
                        প্রোডাকশনে ইমেইল চালুর পূর্বে সরাসরি সকেট হ্যান্ডশেক অথবা রিয়েল টেস্ট ইমেইল প্রেরণ করে কনফিগারেশন নিশ্চিত করুন।
                    </p>

                    <!-- Section 1: Quick Connection & Handshake Test -->
                    <div style="margin-bottom: 18px; padding: 14px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <strong style="font-size: 13px; color: #1e293b; display: block;">১. দ্রুত কানেকশন ও হ্যান্ডশেক টেস্ট 🔌</strong>
                                <span style="font-size: 12px; color: #64748b;">কোনো ইমেইল না পাঠিয়ে সরাসরি সকেট, TLS ও প্রমাণীকরণ (Auth) পরীক্ষা করুন।</span>
                            </div>
                            <button type="button" id="btnVerifyConnCard2" class="btn-export secondary" onclick="verifySmtpQuickConnection()">কানেকশন টেস্ট 🔌</button>
                        </div>
                        <div id="smtpConnResultBox" style="display: none; padding: 12px; border-radius: 8px; font-size: 12px; border: 1px solid transparent; margin-top: 8px;"></div>
                    </div>

                    <!-- Section 2: Real Test Email Dispatch -->
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 6px;">২. টেস্ট ইমেইল প্রাপক (Recipient):</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="email" id="smtpTestRecipient" class="search-input" style="flex: 1;" placeholder="admin@socialhub.com">
                            <button type="button" id="btnSendTestEmail" class="btn-export green" onclick="sendTestSmtpEmail()">টেস্ট পাঠান ✉️</button>
                        </div>
                    </div>

                    <!-- Live Test Diagnostic Result Box -->
                    <div id="smtpTestResultBox" style="display: none; padding: 14px; border-radius: 8px; font-size: 13px; margin-top: 12px; border: 1px solid transparent;"></div>

                    <!-- Section 3: Domain DNS Deliverability (SPF, DMARC, MX) -->
                    <div style="margin-top: 16px; padding: 14px; background: #fffbeb; border-radius: 8px; border: 1px solid #fef3c7;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <strong style="font-size: 13px; color: #92400e; display: block;">৩. ডোমেইন ডেলিভারিবিলিটি (SPF, DMARC, MX) 🛡️</strong>
                                <span style="font-size: 12px; color: #b45309;">গুগল জিমেইল (Gmail) বা ইয়াহু ইনবক্সে ইমেইল পৌঁছানোর জন্য প্রয়োজনীয় DNS রেকর্ড চেক করুন।</span>
                            </div>
                            <button type="button" id="btnCheckDns" class="btn-export secondary" onclick="checkDomainDnsDeliverability()" style="background: #f59e0b; color: #ffffff; border: none;">DNS স্বাস্থ্য চেক 🔍</button>
                        </div>
                        <div id="smtpDnsResultBox" style="display: none; padding: 12px; border-radius: 8px; font-size: 12px; background: #ffffff; border: 1px solid #fde68a; margin-top: 8px;"></div>
                    </div>

                    <div style="margin-top: 16px; padding: 14px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 12px; color: #475569;">
                        🔒 <strong>নিরাপত্তা নীতি:</strong> সংবেদনশীল SMTP পাসওয়ার্ড ডেটাবেজে সম্পূর্ণ এনক্রিপ্ট করে রাখা হয় এবং কখনো API রেসপন্স বা লগ ফাইলে প্লেইনটেক্সট আকারে রাখা হয় না।
                    </div>
                </div>
            </div>

            <!-- Email Templates Section -->
            <div class="table-card" style="padding: 24px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 800; color: #1e293b;">🎨 সাপোর্টেড ইমেইল টেমপ্লেট ও প্রিভিউ</h3>
                        <p style="font-size: 12px; color: var(--fb-text-secondary);">রেসপন্সিভ HTML ও প্লেইনটেক্সট সমৃদ্ধ সেন্ট্রালাইজড টেমপ্লেট গ্যালারি</p>
                    </div>
                </div>
                <div id="smtpTemplatesGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px;">
                    <!-- Filled dynamically via JS -->
                </div>
            </div>

            <!-- Live Email Delivery Logs Table -->
            <div class="table-card">
                <div style="padding: 16px 20px; border-bottom: 1px solid var(--fb-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h3 style="font-size: 15px; font-weight: 800; color: #1e293b;">📜 রিয়েলটাইম ইমেইল ডেলিভারি লগ</h3>
                        <span id="smtpLogsTotalBadge" class="badge-gov">০ টি রেকর্ড</span>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <input type="text" id="smtpLogSearch" class="search-input" style="width: 220px;" placeholder="ইমেইল বা বিষয় খুঁজুন..." oninput="debounceSearchSmtpLogs()">
                        <select id="smtpLogStatusFilter" class="filter-select" onchange="loadSmtpLogs()">
                            <option value="">সকল স্ট্যাটাস</option>
                            <option value="sent">Sent (সফল)</option>
                            <option value="queued">Queued (কিউড)</option>
                            <option value="failed">Failed (ব্যর্থ)</option>
                        </select>
                        <button type="button" class="btn-export secondary" onclick="loadSmtpLogs()">রিফ্রেশ 🔄</button>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>প্রাপক (Recipient)</th>
                            <th>টাইপ</th>
                            <th>বিষয় (Subject)</th>
                            <th>স্ট্যাটাস</th>
                            <th>প্রচেষ্টা</th>
                            <th>সময়</th>
                            <th style="text-align: right;">একশন</th>
                        </tr>
                    </thead>
                    <tbody id="smtpLogsTableBody">
                        <tr><td colspan="8" style="text-align: center; padding: 24px;">লগ লোড হচ্ছে...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 11: CITIZEN IDENTITY VERIFICATION QUEUE -->
        <div id="tabVerifications" style="display: none;">
            <div class="controls-bar">
                <div style="display: flex; gap: 8px; flex-wrap: wrap; flex: 1;">
                    <input type="text" id="verificationSearch" class="search-input" placeholder="নাগরিক নাম, ইউজারনেম, এনআইডি বা পাসপোর্ট..." oninput="debounceSearchVerifications()">
                    <select id="verificationStatusFilter" class="filter-select" onchange="loadVerifications(1)">
                        <option value="">সকল স্ট্যাটাস</option>
                        <option value="pending" selected>অপেক্ষমান (Pending)</option>
                        <option value="verified">অনুমোদিত (Verified)</option>
                        <option value="needs_info">অতিরিক্ত তথ্য প্রয়োজন (Needs Info)</option>
                        <option value="rejected">বাতিলকৃত (Rejected)</option>
                    </select>
                    <select id="verificationTypeFilter" class="filter-select" onchange="loadVerifications(1)">
                        <option value="">সকল নথির ধরন</option>
                        <option value="nid">জাতীয় পরিচয়পত্র (NID)</option>
                        <option value="passport">পাসপোর্ট (Passport)</option>
                        <option value="driving_license">ড্রাইভিং লাইসেন্স</option>
                        <option value="organizational">প্রাতিষ্ঠানিক ডকুমেন্ট</option>
                    </select>
                </div>
                <div class="export-group">
                    <button type="button" class="btn-export secondary" onclick="loadVerifications(1)">
                        🔄 রিফ্রেশ তালিকা
                    </button>
                </div>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 70px;">আইডি</th>
                            <th>নাগরিক / আবেদনকারী</th>
                            <th>নথির ধরন</th>
                            <th>ডকুমেন্ট নম্বর</th>
                            <th>আবেদনের সময়</th>
                            <th>বর্তমান অবস্থা</th>
                            <th style="text-align: right;">পর্যালোচনা ও একশন</th>
                        </tr>
                    </thead>
                    <tbody id="verificationsTableBody">
                        <tr><td colspan="7" style="text-align: center; padding: 24px;">ভেরিফিকেশন আবেদন লোড হচ্ছে...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div id="verificationsPagination" style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; font-size: 13px; color: var(--fb-text-secondary);">
                <div id="verificationsPageInfo">০ টি আবেদনের মধ্যে ০ টি প্রদর্শিত</div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" id="btnPrevVerifications" class="btn-act" onclick="changeVerificationsPage(-1)" disabled>&laquo; পূর্ববর্তী</button>
                    <button type="button" id="btnNextVerifications" class="btn-act" onclick="changeVerificationsPage(1)" disabled>পরবর্তী &raquo;</button>
                </div>
            </div>
        </div>

        <!-- CITIZEN VERIFICATION REVIEW MODAL -->
        <div id="verificationReviewModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
            <div style="background: white; border-radius: 12px; width: 100%; max-width: 840px; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 35px rgba(0,0,0,0.35);">
                <!-- Header -->
                <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h4 style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0;">🛡️ নাগরিক পরিচয় ভেরিফিকেশন পর্যালোচনা</h4>
                        <span id="reviewModalStatusBadge" class="badge-gov">অপেক্ষমান</span>
                    </div>
                    <button type="button" onclick="closeVerificationReviewModal()" style="border: none; background: none; font-size: 22px; cursor: pointer; color: #64748b; padding: 0 4px;">&times;</button>
                </div>

                <!-- Scrollable Content -->
                <div style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 18px;">
                    <!-- User & Request Info Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 600;">নাগরিক / ব্যবহারকারী</div>
                            <div id="reviewUserName" style="font-size: 14px; font-weight: 700; color: #0f172a;">--</div>
                            <div id="reviewUserEmailPhone" style="font-size: 12px; color: #64748b;">--</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 600;">নথির ধরন ও নম্বর</div>
                            <div id="reviewDocType" style="font-size: 14px; font-weight: 700; color: #0f172a;">--</div>
                            <div id="reviewDocNumber" style="font-size: 12px; font-family: monospace; color: #0284c7;">--</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 600;">আবেদনের সময়</div>
                            <div id="reviewSubmittedAt" style="font-size: 13px; font-weight: 600; color: #334155;">--</div>
                            <div id="reviewReviewedBy" style="font-size: 11px; color: #64748b;">পর্যালোচক: এখনও হয়নি</div>
                        </div>
                    </div>

                    <!-- Documents Preview Section -->
                    <div>
                        <h5 style="font-size: 13px; font-weight: 700; color: #334155; margin: 0 0 10px 0;">দাখিলকৃত আইডেন্টিটি ডকুমেন্টস ও সেলফি</h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                            <!-- Front Document -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; background: #fafafa; text-align: center;">
                                <div style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">📄 ডকুমেন্টের সম্মুখভাগ (Front)</div>
                                <div id="reviewFrontPreview" style="min-height: 140px; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 6px; border: 1px dashed #cbd5e1; overflow: hidden; padding: 4px;">
                                    <span style="color: #94a3b8; font-size: 12px;">কোনো ছবি নেই</span>
                                </div>
                            </div>

                            <!-- Back Document -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; background: #fafafa; text-align: center;">
                                <div style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">📄 ডকুমেন্টের পেছনের ভাগ (Back)</div>
                                <div id="reviewBackPreview" style="min-height: 140px; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 6px; border: 1px dashed #cbd5e1; overflow: hidden; padding: 4px;">
                                    <span style="color: #94a3b8; font-size: 12px;">কোনো ছবি নেই</span>
                                </div>
                            </div>

                            <!-- Selfie Document -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; background: #fafafa; text-align: center;">
                                <div style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">🤳 লাইভ সেলফি / ভেরিফিকেশন ফটো</div>
                                <div id="reviewSelfiePreview" style="min-height: 140px; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 6px; border: 1px dashed #cbd5e1; overflow: hidden; padding: 4px;">
                                    <span style="color: #94a3b8; font-size: 12px;">কোনো ছবি নেই</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Notes / Rejection Reason Input -->
                    <div>
                        <label for="reviewAdminNotes" style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                            প্রশাসনিক মন্তব্য বা পর্যালোচনার নোট (বাতিল বা রিকোয়েস্ট ইনফোর ক্ষেত্রে আবশ্যক, নতুবা ঐচ্ছিক):
                        </label>
                        <textarea id="reviewAdminNotes" rows="3" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; font-size: 13px; font-family: inherit; box-sizing: border-box;" placeholder="প্রয়োজনে কারণ বা দিকনির্দেশনা লিখুন..."></textarea>
                    </div>

                    <div id="reviewActionFeedback" style="display: none; padding: 10px; border-radius: 6px; font-size: 13px;"></div>
                </div>

                <!-- Footer with Action Buttons -->
                <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <button type="button" class="btn-act" onclick="closeVerificationReviewModal()">বন্ধ করুন</button>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button type="button" id="btnReviewReject" class="btn-act red" onclick="submitVerificationDecision('reject')">❌ বাতিল করুন</button>
                        <button type="button" id="btnReviewNeedInfo" class="btn-act" style="color: #0284c7; border-color: #7dd3fc;" onclick="submitVerificationDecision('request-info')">❓ তথ্য চান</button>
                        <button type="button" id="btnReviewApprove" class="btn-act green" style="font-weight: 700; padding: 6px 14px;" onclick="submitVerificationDecision('approve')">✅ অনুমোদন ও ব্লু ব্যাজ প্রদান</button>
                        <button type="button" id="btnReviewRevoke" class="btn-act red" style="display: none;" onclick="submitVerificationDecision('revoke')">⚠️ ব্যাজ প্রত্যাহার</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TEMPLATE PREVIEW MODAL -->
        <div id="templatePreviewModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
            <div style="background: white; border-radius: 12px; width: 100%; max-width: 780px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h4 id="previewModalTitle" style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">ইমেইল প্রিভিউ</h4>
                        <span id="previewModalBadge" class="badge-gov">Responsive HTML</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="setPreviewWidth('100%')" class="btn-act" title="Desktop View">🖥️ ডেস্কটপ</button>
                        <button type="button" onclick="setPreviewWidth('380px')" class="btn-act" title="Mobile View">📱 মোবাইল</button>
                        <button type="button" onclick="closeTemplateModal()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b; padding: 0 4px;">&times;</button>
                    </div>
                </div>
                <div style="flex: 1; background: #f1f5f9; display: flex; justify-content: center; overflow: auto; padding: 20px;">
                    <iframe id="templatePreviewIframe" style="width: 100%; height: 580px; border: 1px solid #cbd5e1; border-radius: 8px; background: white; transition: width 0.3s ease;"></iframe>
                </div>
            </div>
        </div>
    </main>

    <script>
        const serverAdminToken = document.querySelector('meta[name="admin-token"]')?.getAttribute('content') || @json($adminToken ?? session('admin_api_token') ?? '');
        if (serverAdminToken) {
            try {
                localStorage.setItem('admin_token', serverAdminToken);
                localStorage.setItem('jugajug_token', serverAdminToken);
            } catch (e) {}
        }
        let token = serverAdminToken || localStorage.getItem('admin_token') || localStorage.getItem('jugajug_token') || '';

        function getAuthHeaders() {
            const h = { 'Accept': 'application/json' };
            const curToken = token || localStorage.getItem('admin_token') || localStorage.getItem('jugajug_token') || '';
            if (curToken) {
                h['Authorization'] = 'Bearer ' + curToken;
            }
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrf) {
                h['X-CSRF-TOKEN'] = csrf;
            }
            return h;
        }

        async function adminFetch(url, options = {}) {
            const headers = Object.assign({}, getAuthHeaders(), options.headers || {});
            const opts = Object.assign({
                credentials: 'same-origin',
            }, options, { headers });
            return fetch(url, opts);
        }

        async function loadAll() {
            const refreshBtn = document.querySelector('button[onclick="loadAll()"]');
            if (refreshBtn) {
                refreshBtn.innerText = '⌛ রিফ্রেশ হচ্ছে...';
                refreshBtn.disabled = true;
            }
            try {
                await loadStats();
                const activeTab = localStorage.getItem('admin_active_tab') || 'users';
                switchTab(activeTab);
            } finally {
                if (refreshBtn) {
                    refreshBtn.innerText = '🔄 রিফ্রেশ ডাটা';
                    refreshBtn.disabled = false;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadStats();

            let initialTab = 'users';
            const hash = window.location.hash ? window.location.hash.replace('#', '').toLowerCase() : '';
            const validTabs = ['users', 'sessions', 'failed', 'otps', 'emails', 'passwords', 'analytics', 'audits', 'media', 'smtp'];

            if (hash && validTabs.includes(hash)) {
                initialTab = hash;
            } else {
                try {
                    const saved = localStorage.getItem('admin_active_tab');
                    if (saved && validTabs.includes(saved)) {
                        initialTab = saved;
                    }
                } catch (e) {}
            }

            switchTab(initialTab);
        });

        let globalStatsData = null;

        async function loadStats() {
            try {
                const res = await adminFetch('/api/v2/admin/auth/stats');
                const json = await res.json();
                if (json.success && json.data) {
                    const d = json.data;
                    globalStatsData = d;

                    // 1. Online Users
                    document.getElementById('statOnlineUsers').innerText = d.online_users ?? 0;

                    // 2. Failed Logins
                    const fl24 = d.failed_logins?.last_24h ?? d.failed_logins_24h ?? 0;
                    const flTotal = d.failed_logins?.total ?? fl24;
                    document.getElementById('statFailedLogins').innerText = fl24;
                    document.getElementById('statFailedLoginsSub').innerText = `মোট ব্যর্থ: ${flTotal} বার`;

                    // 3. Locked Accounts
                    document.getElementById('statLockedAccounts').innerText = d.locked_accounts ?? 0;

                    // 4. Active Sessions
                    document.getElementById('statActiveSessions').innerText = d.active_sessions ?? 0;

                    // 5. OTP Logs
                    const otpTotal = d.otp_logs?.total ?? 0;
                    document.getElementById('statOtpLogs').innerText = otpTotal;
                    document.getElementById('statOtpLogsSub').innerText = `ব্যবহৃত: ${d.otp_logs?.used ?? 0}`;

                    // 6. Email Verification Logs
                    const emailTotal = d.email_verification_logs?.total ?? 0;
                    document.getElementById('statEmailLogs').innerText = emailTotal;
                    document.getElementById('statEmailLogsSub').innerText = `ভেরিফাইড: ${d.email_verification_logs?.verified ?? 0}`;

                    // 7. Password Reset Logs
                    const pwTotal = d.password_reset_logs?.total ?? 0;
                    document.getElementById('statPasswordLogs').innerText = pwTotal;
                    document.getElementById('statPasswordLogsSub').innerText = `পরিবর্তন: ${d.password_reset_logs?.password_changes ?? 0}`;

                    // 8. Login Analytics
                    document.getElementById('statTotalLogins').innerText = d.login_analytics?.total ?? 0;

                    // 9. Pending Verifications Queue Stat
                    try {
                        const verRes = await adminFetch('/api/v2/admin/verifications?status=pending&per_page=1');
                        const verJson = await verRes.json();
                        if (verJson.success && verJson.data) {
                            const pendingCount = verJson.data.total ?? 0;
                            const el = document.getElementById('statVerificationQueue');
                            if (el) el.innerText = pendingCount;
                            const elSub = document.getElementById('statVerificationQueueSub');
                            if (elSub) elSub.innerText = `${pendingCount} টি নাগরিক আবেদন অপেক্ষমান`;
                        }
                    } catch (e) {
                        console.error('Verification stat load error:', e);
                    }

                    const curTab = localStorage.getItem('admin_active_tab');
                    if (curTab === 'analytics') {
                        renderAnalytics();
                    }
                }
            } catch (err) {
                console.error('Stats loading error:', err);
            }
        }

        // --- TAB SWITCHING ---
        function switchTab(tab) {
            const validTabs = ['users', 'sessions', 'failed', 'otps', 'emails', 'passwords', 'analytics', 'audits', 'media', 'smtp', 'verifications'];
            if (!validTabs.includes(tab)) {
                tab = 'users';
            }

            try {
                localStorage.setItem('admin_active_tab', tab);
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, null, '#' + tab);
                }
            } catch (e) {}

            document.querySelectorAll('.tab-btn').forEach(b => {
                const matches = b.dataset.tab === tab || b.getAttribute('onclick')?.includes(`'${tab}'`);
                b.classList.toggle('active', !!matches);
            });

            validTabs.forEach(t => {
                const el = document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1));
                if (el) el.style.display = (t === tab) ? 'block' : 'none';
            });

            if (tab === 'users') loadUsers();
            if (tab === 'sessions') loadSessions();
            if (tab === 'failed') loadFailedLogins();
            if (tab === 'otps') loadOtps();
            if (tab === 'emails') loadEmails();
            if (tab === 'passwords') loadPasswords();
            if (tab === 'analytics') renderAnalytics();
            if (tab === 'audits') loadAudits();
            if (tab === 'media') loadMediaMetrics();
            if (tab === 'smtp') loadSmtpTab();
            if (tab === 'verifications') loadVerifications();
        }

        // --- USERS MANAGEMENT ---
        let userSearchTimeout;
        function debounceSearchUsers() {
            clearTimeout(userSearchTimeout);
            userSearchTimeout = setTimeout(loadUsers, 350);
        }

        async function loadUsers() {
            const search = document.getElementById('userSearch')?.value || '';
            const status = document.getElementById('userStatusFilter')?.value || '';
            const locked = document.getElementById('userLockedOnly')?.checked ? '1' : '';
            const tbody = document.getElementById('usersTableBody');

            try {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--fb-text-secondary);">ব্যবহারকারী তালিকা লোড হচ্ছে...</td></tr>`;

                let url = `/api/v2/admin/auth/users?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
                if (locked) url += '&locked_only=true';

                const res = await adminFetch(url);
                const json = await res.json();

                if (!res.ok || json.success === false) {
                    tbody.innerHTML = `<tr><td colspan="7" style="color: red; text-align: center; padding: 24px; font-weight: 600;">ডাটা লোড ত্রুটি: ${json.message || res.statusText} (${res.status})</td></tr>`;
                    return;
                }

                const users = json.data?.data || (Array.isArray(json.data) ? json.data : []);

                if (!Array.isArray(users) || users.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: #64748b;">কোনো ব্যবহারকারী পাওয়া যায়নি।</td></tr>`;
                    return;
                }

                tbody.innerHTML = users.map(u => `
                    <tr>
                        <td>#${u.id}</td>
                        <td>
                            <div class="user-cell">
                                <div class="avatar">${(u.name || u.username || 'U').charAt(0).toUpperCase()}</div>
                                <div>
                                    <div style="font-weight: 700;">${u.name || u.username}</div>
                                    <div style="color: var(--fb-text-secondary); font-size: 11px;">@${u.username}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>${u.email || '—'}</div>
                            <div style="color: var(--fb-text-secondary); font-size: 11px;">${u.phone || '—'}</div>
                        </td>
                        <td>${u.country || 'BD'}</td>
                        <td><span class="status-badge status-${u.status}">${u.status}</span></td>
                        <td>
                            ${u.email_verified_at ? '<span style="color: var(--fb-green);">✓ ইমেইল</span>' : '<span style="color: #999;">✗ ইমেইল</span>'} /
                            ${u.phone_verified_at ? '<span style="color: var(--fb-green);">✓ মোবাইল</span>' : '<span style="color: #999;">✗ মোবাইল</span>'}
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-act green" onclick="performAction('verify-email', ${u.id})">ভেরিফাই ইমেইল</button>
                                <button class="btn-act green" onclick="performAction('verify-mobile', ${u.id})">ভেরিফাই মোবাইল</button>
                                <button class="btn-act blue" onclick="performAction('activate', ${u.id})">সক্রিয়</button>
                                <button class="btn-act red" onclick="performAction('suspend', ${u.id})">স্থগিত</button>
                                <button class="btn-act red" onclick="performAction('ban', ${u.id})">ব্যান</button>
                                <button class="btn-act" onclick="performAction('unlock', ${u.id})">আনলক</button>
                                <button class="btn-act blue" onclick="performAction('force-logout', ${u.id})">ফোর্স লগআউট</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="7" style="color: red; text-align: center; padding: 24px;">ডাটা লোড ত্রুটি: ${err.message}</td></tr>`;
            }
        }

        async function performAction(action, id) {
            if (!confirm(`আপনি কি এই операцияটি (${action}) নিশ্চিত করতে চান?`)) return;
            try {
                const res = await adminFetch(`/api/v2/admin/auth/users/${id}/${action}`, {
                    method: 'POST'
                });
                const data = await res.json();
                alert(data.message || 'অপারেশন সফল হয়েছে!');
                loadStats();
                loadUsers();
            } catch (err) {
                alert('ত্রুটি: ' + err.message);
            }
        }

        // --- ACTIVE SESSIONS MANAGEMENT & REVOCATION ---
        let sessionSearchTimeout;
        function debounceSearchSessions() {
            clearTimeout(sessionSearchTimeout);
            sessionSearchTimeout = setTimeout(loadSessions, 350);
        }

        async function loadSessions() {
            const search = document.getElementById('sessionSearch')?.value || '';
            const os = document.getElementById('sessionOsFilter')?.value || '';
            const tbody = document.getElementById('sessionsTableBody');

            try {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 20px; color: var(--fb-text-secondary);">সেশন তালিকা লোড হচ্ছে...</td></tr>`;

                const res = await adminFetch(`/api/v2/admin/auth/sessions?search=${encodeURIComponent(search)}&os=${encodeURIComponent(os)}`);
                const json = await res.json();
                const list = json.data?.data || (Array.isArray(json.data) ? json.data : []);

                if (!Array.isArray(list) || list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 20px; color: #64748b;">কোনো সক্রিয় সেশন নেই</td></tr>`;
                    return;
                }

                tbody.innerHTML = list.map(s => `
                    <tr>
                        <td>#${s.id}</td>
                        <td>
                            <div style="font-weight: 700;">${s.user?.name || s.user?.username || 'User #' + s.user_id}</div>
                            <div style="color: var(--fb-text-secondary); font-size: 11px;">${s.user?.email || ''}</div>
                        </td>
                        <td><span style="font-weight: 600;">${s.device_name || 'Generic Device'}</span></td>
                        <td>${s.browser || '—'} / ${s.os || '—'}</td>
                        <td>${s.ip_address || '—'} ${s.location ? `(${s.location})` : ''}</td>
                        <td>${s.is_current ? '<span class="status-badge status-active">বর্তমান সেশন</span>' : '<span class="status-badge" style="background:#eee;">সংযুক্ত</span>'}</td>
                        <td>${s.last_active_at ? new Date(s.last_active_at).toLocaleString() : '—'}</td>
                        <td>
                            <button class="btn-act red" onclick="revokeSession(${s.id})">❌ সেশন বাতিল (Revoke)</button>
                        </td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="8" style="color: red; text-align: center; padding: 20px;">ত্রুটি: ${err.message}</td></tr>`;
            }
        }

        async function revokeSession(sessionId) {
            if (!confirm(`আপনি কি এই সেশনটি (#${sessionId}) বাতিল করতে চান? ব্যবহারকারী উক্ত ডিভাইস থেকে তাৎক্ষণিকভাবে লগআউট হয়ে যাবেন।`)) return;

            try {
                const res = await adminFetch(`/api/v2/admin/auth/sessions/${sessionId}`, {
                    method: 'DELETE'
                });
                const data = await res.json();
                alert(data.message || 'সেশন সফলভাবে বাতিল করা হয়েছে!');
                loadStats();
                loadSessions();
            } catch (err) {
                alert('ত্রুটি: ' + err.message);
            }
        }

        // --- FAILED LOGINS MONITOR ---
        async function loadFailedLogins() {
            const tbody = document.getElementById('failedTableBody');
            try {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--fb-text-secondary);">লোড হচ্ছে...</td></tr>`;
                const res = await adminFetch('/api/v2/admin/auth/failed-logins');
                const json = await res.json();
                const list = json.data?.data || (Array.isArray(json.data) ? json.data : []);
                if (!Array.isArray(list) || list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: #64748b;">কোনো ব্যর্থ লগইন রেকর্ড নেই</td></tr>`;
                    return;
                }
                tbody.innerHTML = list.map(f => `
                    <tr>
                        <td>#${f.id}</td>
                        <td style="font-weight: 700;">${f.identifier || f.email || 'Unknown'}</td>
                        <td>${f.ip_address || '—'}</td>
                        <td style="font-size: 11px; max-width: 300px; word-break: break-all;">${f.user_agent || '—'}</td>
                        <td><span style="color: var(--fb-red); font-weight: 600;">${f.failure_reason || f.reason || 'Invalid Password'}</span></td>
                        <td>${f.attempted_at ? new Date(f.attempted_at).toLocaleString() : '—'}</td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="6" style="color: red; text-align: center; padding: 20px;">ত্রুটি: ${err.message}</td></tr>`;
            }
        }

        // --- OTP LOGS ---
        let otpSearchTimeout;
        function debounceSearchOtps() {
            clearTimeout(otpSearchTimeout);
            otpSearchTimeout = setTimeout(loadOtps, 350);
        }

        async function loadOtps() {
            const tbody = document.getElementById('otpsTableBody');
            const search = document.getElementById('otpSearch')?.value || '';
            const purpose = document.getElementById('otpPurposeFilter')?.value || '';

            try {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--fb-text-secondary);">লোড হচ্ছে...</td></tr>`;
                const res = await adminFetch(`/api/v2/admin/auth/otp-logs?search=${encodeURIComponent(search)}&purpose=${encodeURIComponent(purpose)}`);
                const json = await res.json();
                const list = json.data?.data || (Array.isArray(json.data) ? json.data : []);
                if (!Array.isArray(list) || list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: #64748b;">কোনো ওটিপি রেকর্ড পাওয়া যায়নি</td></tr>`;
                    return;
                }
                tbody.innerHTML = list.map(o => `
                    <tr>
                        <td>#${o.id}</td>
                        <td style="font-weight: 600;">${o.identifier}</td>
                        <td><span class="status-badge" style="background:#e7f3ff; color:var(--fb-primary);">${o.purpose}</span></td>
                        <td>${o.is_used ? '<span style="color: var(--fb-green); font-weight:700;">✓ ব্যবহৃত</span>' : '<span style="color: #888;">অব্যবহৃত</span>'}</td>
                        <td>${o.ip_address || '—'}</td>
                        <td>${o.created_at ? new Date(o.created_at).toLocaleString() : '—'}</td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="6" style="color: red; text-align: center; padding: 20px;">ত্রুটি: ${err.message}</td></tr>`;
            }
        }

        // --- EMAIL VERIFICATION LOGS ---
        let emailSearchTimeout;
        function debounceSearchEmails() {
            clearTimeout(emailSearchTimeout);
            emailSearchTimeout = setTimeout(loadEmails, 350);
        }

        async function loadEmails() {
            const tbody = document.getElementById('emailsTableBody');
            const search = document.getElementById('emailSearch')?.value || '';
            const status = document.getElementById('emailStatusFilter')?.value || '';

            try {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--fb-text-secondary);">লোড হচ্ছে...</td></tr>`;
                const res = await adminFetch(`/api/v2/admin/auth/email-verifications?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`);
                const json = await res.json();
                const list = json.data?.data || (Array.isArray(json.data) ? json.data : []);
                if (!Array.isArray(list) || list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: #64748b;">কোনো ইমেইল ভেরিফিকেশন রেকর্ড পাওয়া যায়নি</td></tr>`;
                    return;
                }
                tbody.innerHTML = list.map(e => `
                    <tr>
                        <td>#${e.id}</td>
                        <td style="font-weight: 700;">${e.email}</td>
                        <td>${e.user ? (e.user.name || e.user.username) : 'User #' + e.user_id}</td>
                        <td>${e.ip_address || '—'}</td>
                        <td>${e.verified_at ? '<span style="color: var(--fb-green); font-weight:700;">✓ ভেরিফাইড (' + new Date(e.verified_at).toLocaleDateString() + ')</span>' : '<span style="color: var(--fb-yellow); font-weight:700;">⏳ অপেক্ষমাণ</span>'}</td>
                        <td>${e.created_at ? new Date(e.created_at).toLocaleString() : '—'}</td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="6" style="color: red; text-align: center; padding: 20px;">ত্রুটি: ${err.message}</td></tr>`;
            }
        }

        // --- PASSWORD RESET LOGS ---
        async function loadPasswords() {
            const tbody = document.getElementById('passwordsTableBody');
            try {
                tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 20px; color: var(--fb-text-secondary);">লোড হচ্ছে...</td></tr>`;
                const res = await adminFetch('/api/v2/admin/auth/password-resets');
                const json = await res.json();
                const list = json.data?.data || (Array.isArray(json.data) ? json.data : []);
                if (!Array.isArray(list) || list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">কোনো পাসওয়ার্ড পরিবর্তন রেকর্ড নেই</td></tr>`;
                    return;
                }
                tbody.innerHTML = list.map(p => `
                    <tr>
                        <td>#${p.id}</td>
                        <td style="font-weight: 700;">${p.user ? (p.user.name || p.user.username) : 'User #' + p.user_id}</td>
                        <td style="font-family: monospace; color:#666; font-size: 11px;">${(p.password_hash || '').substring(0, 30)}...</td>
                        <td>${p.created_at ? new Date(p.created_at).toLocaleString() : '—'}</td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="4" style="color: red; text-align: center; padding: 20px;">ত্রুটি: ${err.message}</td></tr>`;
            }
        }

        // --- LOGIN ANALYTICS RENDERING ---
        async function renderAnalytics() {
            if (!globalStatsData || !globalStatsData.login_analytics) {
                await loadStats();
            }
            if (!globalStatsData || !globalStatsData.login_analytics) {
                const sBox = document.getElementById('analyticsStatusBox');
                if (sBox) sBox.innerHTML = '<span style="color:#64748b;">অ্যানালিটিক্স ডাটা লোড করা যায়নি</span>';
                return;
            }
            const a = globalStatsData.login_analytics;

            // 1. Status
            const sBox = document.getElementById('analyticsStatusBox');
            const total = a.total || 1;
            if (sBox) {
                sBox.innerHTML = Object.entries(a.by_status || {}).map(([k, v]) => {
                    const pct = Math.round((v / total) * 100);
                    return `
                        <div class="stat-bar-row">
                            <span style="font-weight:600; text-transform:capitalize;">${k}</span>
                            <span>${v} (${pct}%)</span>
                        </div>
                        <div class="stat-bar-bg"><div class="stat-bar-fill" style="width:${pct}%; background:${k === 'failed' ? 'var(--fb-red)' : 'var(--fb-green)'}"></div></div>
                    `;
                }).join('') || 'কোনো ডাটা নেই';
            }

            // 2. Browsers
            const bBox = document.getElementById('analyticsBrowserBox');
            if (bBox) {
                bBox.innerHTML = Object.entries(a.by_browser || {}).map(([k, v]) => {
                    const pct = Math.round((v / total) * 100);
                    return `
                        <div class="stat-bar-row">
                            <span style="font-weight:600;">${k}</span>
                            <span>${v} (${pct}%)</span>
                        </div>
                        <div class="stat-bar-bg"><div class="stat-bar-fill" style="width:${pct}%;"></div></div>
                    `;
                }).join('') || 'কোনো ব্রাউজার ডাটা নেই';
            }

            // 3. OS
            const oBox = document.getElementById('analyticsOsBox');
            if (oBox) {
                oBox.innerHTML = Object.entries(a.by_os || {}).map(([k, v]) => {
                    const pct = Math.round((v / total) * 100);
                    return `
                        <div class="stat-bar-row">
                            <span style="font-weight:600;">${k}</span>
                            <span>${v} (${pct}%)</span>
                        </div>
                        <div class="stat-bar-bg"><div class="stat-bar-fill" style="width:${pct}%; background:var(--fb-purple);"></div></div>
                    `;
                }).join('') || 'কোনো ওএস ডাটা নেই';
            }

            // 4. Device
            const dBox = document.getElementById('analyticsDeviceBox');
            if (dBox) {
                dBox.innerHTML = Object.entries(a.by_device || {}).map(([k, v]) => {
                    const pct = Math.round((v / total) * 100);
                    return `
                        <div class="stat-bar-row">
                            <span style="font-weight:600; text-transform:capitalize;">${k}</span>
                            <span>${v} (${pct}%)</span>
                        </div>
                        <div class="stat-bar-bg"><div class="stat-bar-fill" style="width:${pct}%; background:var(--fb-yellow);"></div></div>
                    `;
                }).join('') || 'কোনো ডিভাইস ডাটা নেই';
            }

            // 5. 7-Day Trend Table
            const dTable = document.getElementById('analyticsDaysTableBody');
            if (dTable) {
                dTable.innerHTML = Object.entries(a.last_7_days || {}).map(([date, count]) => `
                    <tr>
                        <td style="font-weight:700;">${date}</td>
                        <td><span class="status-badge" style="background:#eafbe7; color:var(--fb-green);">${count} টি লগইন</span></td>
                    </tr>
                `).join('') || '<tr><td colspan="2" style="text-align:center;">কোনো ট্রেন্ড ডাটা নেই</td></tr>';
            }
        }

        // --- AUDIT TRAIL ---
        async function loadAudits() {
            const tbody = document.getElementById('auditsTableBody');
            try {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--fb-text-secondary);">লোড হচ্ছে...</td></tr>`;
                const res = await adminFetch('/api/v2/admin/auth/audit-logs');
                const json = await res.json();
                const list = json.data?.data || (Array.isArray(json.data) ? json.data : []);
                if (!Array.isArray(list) || list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: #64748b;">কোনো অডিট রেকর্ড নেই</td></tr>`;
                    return;
                }
                tbody.innerHTML = list.map(a => `
                    <tr>
                        <td>#${a.id}</td>
                        <td style="font-weight: 700; color: var(--fb-primary);">${a.action}</td>
                        <td>${a.user ? (a.user.name || a.user.username) : 'Admin #' + (a.user_id || 'System')}</td>
                        <td>${a.entity_type ? a.entity_type.split('\\').pop() + ' #' + a.entity_id : '—'}</td>
                        <td>${a.ip_address || '—'}</td>
                        <td>${a.created_at ? new Date(a.created_at).toLocaleString() : '—'}</td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="6" style="color: red; text-align: center; padding: 20px;">ত্রুটি: ${err.message}</td></tr>`;
            }
        }

        // --- MEDIA & RESOURCE CONTROLS ---
        async function loadMediaMetrics() {
            try {
                const res = await adminFetch('/api/v2/admin/media/metrics');
                const json = await res.json();
                if (json.success && json.data) {
                    const d = json.data;
                    document.getElementById('statTotalMedia').innerText = d.total_media_count ?? 0;
                    document.getElementById('statTotalStorage').innerText = `স্টোরেজ সাইজ: ${d.total_storage_mb ?? 0} MB`;
                    document.getElementById('statActiveUploads').innerText = d.active_upload_sessions ?? 0;
                    document.getElementById('statQueuedJobs').innerText = d.processing_jobs?.queued ?? 0;
                    document.getElementById('statJobsDetail').innerText = `চলমান: ${d.processing_jobs?.processing ?? 0} | ব্যর্থ: ${d.processing_jobs?.failed ?? 0}`;
                    document.getElementById('statFreeDisk').innerText = `${d.server_resources?.disk_free_gb ?? 0} GB`;
                    document.getElementById('statMemoryUsage').innerText = `ব্যবহৃত RAM: ${d.server_resources?.memory_usage_mb ?? 0} MB`;

                    if (d.settings) {
                        document.getElementById('settingMaxVideoSize').value = d.settings.max_video_size_mb || 100;
                        document.getElementById('settingMaxImageSize').value = d.settings.max_image_size_mb || 20;
                        document.getElementById('settingMaxWorkers').value = d.settings.max_concurrent_workers || 3;
                        document.getElementById('settingMinFreeDisk').value = d.settings.min_free_disk_mb || 1024;
                    }
                }
            } catch (err) {
                console.error('Failed to load media metrics:', err);
            }
        }

        async function saveMediaSettings() {
            try {
                const payload = {
                    max_video_size_mb: parseInt(document.getElementById('settingMaxVideoSize').value),
                    max_image_size_mb: parseInt(document.getElementById('settingMaxImageSize').value),
                    max_concurrent_workers: parseInt(document.getElementById('settingMaxWorkers').value),
                    min_free_disk_mb: parseInt(document.getElementById('settingMinFreeDisk').value)
                };

                const res = await adminFetch('/api/v2/admin/media/settings', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const json = await res.json();
                if (json.success) {
                    alert('মিডিয়া ও রিসোর্স সেটিংস সফলভাবে আপডেট করা হয়েছে! ✅');
                    loadMediaMetrics();
                } else {
                    alert('ত্রুটি: ' + (json.message || 'সেটিংস আপডেট ব্যর্থ হয়েছে।'));
                }
            } catch (err) {
                alert('অনুরোধ ব্যর্থ হয়েছে: ' + err.message);
            }
        }

        // --- SMTP & EMAIL MANAGEMENT SUBSYSTEM ---
        let smtpLogSearchTimeout;
        function debounceSearchSmtpLogs() {
            clearTimeout(smtpLogSearchTimeout);
            smtpLogSearchTimeout = setTimeout(loadSmtpLogs, 350);
        }

        async function fetchAdminSmtp(path, options = {}) {
            const headers = {
                ...getAuthHeaders(),
                ...(options.headers || {})
            };
            const fetchOpts = { credentials: 'same-origin', ...options, headers };
            let res = await fetch('/admin' + path, fetchOpts);
            if ((res.status === 401 || res.status === 404) && !path.startsWith('/api/')) {
                res = await fetch('/api/v2/admin' + path, fetchOpts);
            }
            return res;
        }

        async function loadSmtpTab() {
            await Promise.all([
                loadSmtpStats(),
                loadSmtpSettings(),
                loadSmtpTemplates(),
                loadSmtpLogs()
            ]);
        }

        async function loadSmtpStats() {
            try {
                const res = await fetchAdminSmtp('/smtp/stats');
                const json = await res.json();
                if (json.success && json.data) {
                    const d = json.data;
                    document.getElementById('smtpTotalSent').innerText = d.sent_count ?? 0;
                    document.getElementById('smtpTotalSentSub').innerText = `মোট প্রচেষ্টা: ${d.total_attempts ?? 0}`;
                    document.getElementById('smtpDeliveryRate').innerText = `${d.delivery_rate_percent ?? 100}%`;
                    document.getElementById('smtpFailedCount').innerText = d.failed_count ?? 0;
                    document.getElementById('smtpFailedSub').innerText = `২৪ ঘণ্টায় ব্যর্থ: ${d.last_24h?.failed ?? 0}`;
                    document.getElementById('smtpQueuedCount').innerText = d.queued_count ?? 0;
                    document.getElementById('smtpQueuedSub').innerText = d.is_system_enabled ? 'ইমেইল সিস্টেম সক্রিয় 🟢' : 'ইমেইল সিস্টেম নিষ্ক্রিয় 🔴';
                }
            } catch (err) {
                console.error('Failed to load SMTP stats:', err);
            }
        }

        async function loadSmtpSettings() {
            try {
                const res = await fetchAdminSmtp('/smtp/settings');
                const json = await res.json();
                if (json.success && json.data) {
                    const s = json.data;
                    if (s.mail_host !== undefined && s.mail_host !== null) document.getElementById('smtpHost').value = s.mail_host;
                    if (s.mail_port !== undefined && s.mail_port !== null) document.getElementById('smtpPort').value = s.mail_port;
                    if (s.mail_username !== undefined) document.getElementById('smtpUsername').value = s.mail_username || '';
                    if (s.has_password) {
                        document.getElementById('smtpPassword').placeholder = '•••••••• (সংরক্ষিত - পরিবর্তন করতে নতুন দিন)';
                    } else {
                        document.getElementById('smtpPassword').placeholder = 'নতুন পাসওয়ার্ড দিন...';
                    }
                    document.getElementById('smtpPassword').value = '';
                    if (s.mail_encryption) document.getElementById('smtpEncryption').value = s.mail_encryption;
                    if (s.mail_from_address !== undefined && s.mail_from_address !== null) document.getElementById('smtpFromAddress').value = s.mail_from_address;
                    if (s.mail_from_name !== undefined && s.mail_from_name !== null) document.getElementById('smtpFromName').value = s.mail_from_name;
                    if (s.mail_reply_to !== undefined) document.getElementById('smtpReplyTo').value = s.mail_reply_to || '';
                    if (s.timeout) document.getElementById('smtpTimeout').value = s.timeout;
                    if (s.rate_limit_per_minute) document.getElementById('smtpRateLimit').value = s.rate_limit_per_minute;
                    if (s.is_enabled !== undefined) document.getElementById('smtpIsEnabled').checked = !!s.is_enabled;
                    if (s.smtp_auth !== undefined) document.getElementById('smtpAuth').checked = s.smtp_auth !== false;
                }
            } catch (err) {
                console.error('Failed to load SMTP settings:', err);
            }
        }

        async function saveSmtpSettings() {
            const btn = document.getElementById('btnSaveSmtp');
            const originalText = btn.innerText;
            btn.innerText = 'সংরক্ষণ হচ্ছে... ⏳';
            btn.disabled = true;

            const host = document.getElementById('smtpHost').value.trim();
            const port = parseInt(document.getElementById('smtpPort').value);
            const fromAddr = document.getElementById('smtpFromAddress').value.trim();
            const fromName = document.getElementById('smtpFromName').value.trim();
            const username = document.getElementById('smtpUsername').value.trim();
            const replyTo = document.getElementById('smtpReplyTo').value.trim();

            if (!host) {
                alert('SMTP Host আবশ্যক।');
                btn.innerText = originalText;
                btn.disabled = false;
                return;
            }
            if (!port) {
                alert('Port আবশ্যক।');
                btn.innerText = originalText;
                btn.disabled = false;
                return;
            }
            if (!fromAddr) {
                alert('From Email আবশ্যক।');
                btn.innerText = originalText;
                btn.disabled = false;
                return;
            }

            const payload = {
                mail_host: host,
                mail_port: port,
                mail_username: username ? username : null,
                mail_encryption: document.getElementById('smtpEncryption').value,
                mail_from_address: fromAddr,
                mail_from_name: fromName || 'SocialHub',
                mail_reply_to: replyTo ? replyTo : null,
                timeout: parseInt(document.getElementById('smtpTimeout').value) || 30,
                rate_limit_per_minute: parseInt(document.getElementById('smtpRateLimit').value) || 60,
                is_enabled: document.getElementById('smtpIsEnabled').checked,
                smtp_auth: document.getElementById('smtpAuth').checked
            };

            const pwd = document.getElementById('smtpPassword').value;
            if (pwd.length > 0) {
                payload.mail_password = pwd;
            }

            try {
                const res = await fetchAdminSmtp('/smtp/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (res.ok && json.success) {
                    alert('SMTP সেটিংস সফলভাবে সংরক্ষিত ও কার্যকর করা হয়েছে! ✅');
                    await loadSmtpSettings();
                    await loadSmtpStats();
                } else {
                    let errMsg = json.message || 'সংরক্ষণ ব্যর্থ হয়েছে।';
                    if (json.errors) {
                        errMsg += '\n' + Object.values(json.errors).flat().join('\n');
                    }
                    alert('ত্রুটি: ' + errMsg);
                }
            } catch (err) {
                alert('অনুরোধ ব্যর্থ হয়েছে: ' + err.message);
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }

        async function verifySmtpQuickConnection() {
            const btn1 = document.getElementById('btnVerifyConnCard1');
            const btn2 = document.getElementById('btnVerifyConnCard2');
            const resultBox = document.getElementById('smtpConnResultBox');

            const host = document.getElementById('smtpHost').value.trim();
            const port = parseInt(document.getElementById('smtpPort').value);
            const username = document.getElementById('smtpUsername').value.trim();
            const encryption = document.getElementById('smtpEncryption').value;
            const timeout = parseInt(document.getElementById('smtpTimeout').value) || 15;

            if (!host) {
                alert('অনুগ্রহ করে SMTP Host উল্লেখ করুন।');
                document.getElementById('smtpHost').focus();
                return;
            }
            if (!port) {
                alert('অনুগ্রহ করে Port উল্লেখ করুন।');
                document.getElementById('smtpPort').focus();
                return;
            }

            const btns = [btn1, btn2].filter(Boolean);
            btns.forEach(b => {
                b.dataset.prevText = b.innerText;
                b.innerText = 'কানেক্ট হচ্ছে... ⏳';
                b.disabled = true;
            });

            if (resultBox) {
                resultBox.style.display = 'block';
                resultBox.style.background = '#eff6ff';
                resultBox.style.borderColor = '#93c5fd';
                resultBox.style.color = '#1e40af';
                resultBox.innerHTML = `<strong>সার্ভারের সাথে সরাসরি যোগাযোগ পরীক্ষা চলছে...</strong><br>সকেট সংযোগ, TLS হ্যান্ডশেক ও প্রমাণীকরণ যাচাই হচ্ছে (${host}:${port})...`;
            }

            const payload = {
                mail_host: host,
                mail_port: port,
                mail_username: username ? username : null,
                mail_encryption: encryption,
                timeout: timeout
            };
            const pwd = document.getElementById('smtpPassword').value;
            if (pwd.length > 0) {
                payload.mail_password = pwd;
            }

            try {
                const res = await fetchAdminSmtp('/smtp/verify-connection', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();

                if (resultBox) {
                    if (res.ok && json.success) {
                        resultBox.style.background = '#f0fdf4';
                        resultBox.style.borderColor = '#86efac';
                        resultBox.style.color = '#166534';
                        resultBox.innerHTML = `
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                <strong style="font-size: 13px;">✅ কানেকশন সম্পূর্ণ সফল!</strong>
                                <span style="background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 999px; font-weight: 700; font-size: 11px;">${json.details?.latency_ms || 0} ms</span>
                            </div>
                            <div style="margin-top: 4px; font-size: 12px;">${json.message}</div>
                            <div style="margin-top: 6px; font-size: 11px; opacity: 0.85;">
                                হোস্ট: <code>${json.details?.host}:${json.details?.port}</code> | এনক্রিপশন: <code>${json.details?.encryption}</code> | প্রমাণীকরণ: <strong>${json.details?.auth_verified ? 'সফল (Authenticated)' : 'সফল (Anonymous)'}</strong>
                            </div>
                        `;
                    } else {
                        resultBox.style.background = '#fef2f2';
                        resultBox.style.borderColor = '#fca5a5';
                        resultBox.style.color = '#991b1b';

                        const errText = json.details?.error || json.message || 'অজানা ত্রুটি';
                        let hint = 'হোস্ট এবং পোর্ট সঠিক কিনা পরীক্ষা করুন।';
                        if (errText.includes('authentication failed') || errText.includes('535') || errText.includes('Username and Password not accepted')) {
                            hint = '<strong>প্রমাণীকরণ ত্রুটি (535):</strong> ইউজারনেম এবং পাসওয়ার্ড সঠিক নয়। Password ফিল্ডে নতুন পাসওয়ার্ড প্রদান করে টেস্ট করুন।';
                        } else if (errText.includes('Connection refused') || errText.includes('timed out')) {
                            hint = '<strong>সার্ভার অনুপুস্থিত:</strong> পোর্ট খোলা নেই বা সার্ভার ফায়ারওয়াল দ্বারা আউটবাউন্ড সংযোগ ব্লক রয়েছে।';
                        } else if (errText.includes('SSL') || errText.includes('TLS') || errText.includes('certificate')) {
                            hint = '<strong>SSL/TLS হ্যান্ডশেক ত্রুটি:</strong> Port 587 হলে TLS, Port 465 হলে SSL সিলেক্ট করুন।';
                        }

                        resultBox.innerHTML = `
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                <strong style="font-size: 13px;">❌ কানেকশন ব্যর্থ হয়েছে</strong>
                                ${json.details?.latency_ms ? `<span style="background: #fee2e2; color: #b91c1c; padding: 2px 8px; border-radius: 999px; font-weight: 700; font-size: 11px;">${json.details.latency_ms} ms</span>` : ''}
                            </div>
                                                                                                                                                             <div style="margin-top: 6px; padding: 6px 8px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 6px; font-family: monospace; font-size: 11px; word-break: break-all;">${errText}</div>
                            <div style="margin-top: 6px; font-size: 11px; opacity: 0.9;">${hint}</div>
                        `;
                    }
                }
            } catch (err) {
                if (resultBox) {
                    resultBox.style.background = '#fef2f2';
                    resultBox.style.borderColor = '#fca5a5';
                    resultBox.style.color = '#991b1b';
                    resultBox.innerHTML = `
                        <strong style="font-size: 13px;">❌ নেটওয়ার্ক বা সার্ভার ত্রুটি</strong>
                        <div style="margin-top: 4px; font-size: 12px;">${err.message}</div>
                    `;
                }
            } finally {
                btns.forEach(b => {
                    b.innerText = b.dataset.prevText || 'কানেকশন টেস্ট 🔌';
                    b.disabled = false;
                });
            }
        }

        async function sendTestSmtpEmail() {
            const recipientInput = document.getElementById('smtpTestRecipient');
            const recipient = recipientInput.value.trim();
            const resultBox = document.getElementById('smtpTestResultBox');
            const btn = document.getElementById('btnSendTestEmail');

            if (!recipient) {
                alert('অনুগ্রহ করে প্রাপকের ইমেইল ঠিকানা প্রদান করুন।');
                recipientInput.focus();
                return;
            }

            btn.innerText = 'পাঠানো হচ্ছে... ⏳';
            btn.disabled = true;
            resultBox.style.display = 'block';
            resultBox.style.background = '#eff6ff';
            resultBox.style.borderColor = '#93c5fd';
            resultBox.style.color = '#1e40af';
            resultBox.innerHTML = `<strong>সংযোগ পরীক্ষা চলছে...</strong><br>সার্ভার ${document.getElementById('smtpHost').value}:${document.getElementById('smtpPort').value}-এ টেস্ট ইমেইল প্রেরণ করা হচ্ছে...`;

            const username = document.getElementById('smtpUsername').value.trim();
            const payload = {
                to_email: recipient,
                mail_host: document.getElementById('smtpHost').value.trim(),
                mail_port: parseInt(document.getElementById('smtpPort').value),
                mail_username: username ? username : null,
                mail_encryption: document.getElementById('smtpEncryption').value,
                mail_from_address: document.getElementById('smtpFromAddress').value.trim(),
                mail_from_name: document.getElementById('smtpFromName').value.trim()
            };
            const pwd = document.getElementById('smtpPassword').value;
            if (pwd.length > 0) {
                payload.mail_password = pwd;
            }

            try {
                const res = await fetchAdminSmtp('/smtp/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();

                if (res.ok && json.success) {
                    resultBox.style.background = '#f0fdf4';
                    resultBox.style.borderColor = '#86efac';
                    resultBox.style.color = '#166534';
                    resultBox.innerHTML = `
                        <strong>✅ সফল! SMTP টেস্ট উত্তীর্ণ:</strong><br>
                        ${json.message}<br>
                        <small style="opacity: 0.85;">হোস্ট: ${json.details?.host}:${json.details?.port} | এনক্রিপশন: ${json.details?.encryption}</small>
                        ${json.details?.dns_warning ? `
                            <div style="margin-top: 10px; padding: 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; color: #92400e; font-size: 12px; line-height: 1.4;">
                                ⚠️ <strong>ইনবক্স ডেলিভারি সতর্কতা:</strong><br>
                                ${json.details.dns_warning}
                                <div style="margin-top: 6px;">
                                    <button type="button" class="btn-export secondary" style="font-size: 11px; padding: 3px 8px; background: #f59e0b; color: #fff; border:none;" onclick="checkDomainDnsDeliverability()">DNS স্বাস্থ্য রিপোর্ট দেখুন 🔍</button>
                                </div>
                            </div>
                        ` : ''}
                    `;
                } else {
                    resultBox.style.background = '#fef2f2';
                    resultBox.style.borderColor = '#fca5a5';
                    resultBox.style.color = '#991b1b';

                    const errorDetail = json.details?.error || (json.errors ? Object.values(json.errors).flat().join(', ') : '');
                    let hint = 'অনুগ্রহ করে হোস্ট, পোর্ট, পাসওয়ার্ড বা এনক্রিপশন মোড যাচাই করুন।';
                    if (errorDetail.includes('authentication failed') || errorDetail.includes('535') || errorDetail.includes('Username and Password not accepted')) {
                        hint = '<strong>প্রমাণীকরণ ত্রুটি (Authentication Failed):</strong> ইউজারনেম এবং পাসওয়ার্ড সঠিক কিনা যাচাই করুন। পাসওয়ার্ড পরিবর্তন করলে আগে "সংরক্ষণ ও কার্যকর করুন 💾" বাটনে ক্লিক করে সংরক্ষণ করুন।';
                    } else if (errorDetail.includes('Connection refused') || errorDetail.includes('Connection timed out') || errorDetail.includes('timed out')) {
                        hint = '<strong>সার্ভার সংযোগ ত্রুটি:</strong> হোস্ট ও পোর্ট রিচ করা যাচ্ছে না বা ফায়ারওয়াল দ্বারা ব্লক রয়েছে।';
                    } else if (errorDetail.includes('certificate') || errorDetail.includes('SSL')) {
                        hint = '<strong>SSL/TLS হ্যান্ডশেক ত্রুটি:</strong> এনক্রিপশন মোড (TLS / SSL / None) ও পোর্ট কনফিগারেশন মিলিয়ে দেখুন।';
                    }

                    resultBox.innerHTML = `
                        <div style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">❌ ব্যর্থ! SMTP সংযোগে সমস্যা:</div>
                        <div style="font-size: 13px;">${json.message || 'সংযোগ স্থাপন করা সম্ভব হয়নি।'}</div>
                        ${errorDetail ? `<div style="margin-top: 8px; padding: 8px 10px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 6px; font-family: monospace; font-size: 11px; word-break: break-all; color: #9f1239; max-height: 120px; overflow-y: auto;">${errorDetail}</div>` : ''}
                        <div style="margin-top: 8px; font-size: 12px; line-height: 1.4; opacity: 0.95;">${hint}</div>
                    `;
                }
                loadSmtpStats();
                loadSmtpLogs();
            } catch (err) {
                resultBox.style.background = '#fef2f2';
                resultBox.style.borderColor = '#fca5a5';
                resultBox.style.color = '#991b1b';
                resultBox.innerHTML = `
                    <div style="font-weight: 700;">❌ নেটওয়ার্ক বা সার্ভার ত্রুটি:</div>
                    <div style="font-size: 12px; margin-top: 4px;">${err.message}</div>
                `;
            } finally {
                btn.innerText = 'টেস্ট পাঠান ✉️';
                btn.disabled = false;
            }
        }

        async function checkDomainDnsDeliverability() {
            const btn = document.getElementById('btnCheckDns');
            const resultBox = document.getElementById('smtpDnsResultBox');
            const fromAddr = document.getElementById('smtpFromAddress')?.value || '';

            if (btn) {
                btn.disabled = true;
                btn.innerText = 'চেক হচ্ছে... ⏳';
            }
            if (resultBox) {
                resultBox.style.display = 'block';
                resultBox.innerHTML = 'DNS রেকর্ড (SPF, DMARC, MX) বিশ্লেষণ করা হচ্ছে...';
            }

            try {
                const res = await fetchAdminSmtp('/smtp/dns-check' + (fromAddr ? '?from_address=' + encodeURIComponent(fromAddr) : ''));
                const json = await res.json();
                if (res.ok && json.success && json.data) {
                    const d = json.data;
                    const spfOk = d.spf?.status === 'ok';
                    const dmarcOk = d.dmarc?.status === 'ok';
                    const mxOk = d.mx?.status === 'ok';

                    resultBox.innerHTML = `
                        <div style="font-weight: 700; color: #1e293b; margin-bottom: 8px; font-size: 13px;">ডোমেইন: <code>${d.domain}</code> এর লাইভ DNS স্ট্যাটাস:</div>
                        <div style="display: grid; gap: 8px;">
                            <div style="padding: 8px; background: ${spfOk ? '#f0fdf4' : '#fef2f2'}; border: 1px solid ${spfOk ? '#bbf7d0' : '#fecdd3'}; border-radius: 6px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong>SPF রেকর্ড:</strong>
                                    <span style="font-size: 11px; font-weight: 700; color: ${spfOk ? '#15803d' : '#b91c1c'};">${spfOk ? '✅ সক্রিয়' : '❌ অনুপস্থিত (Missing)'}</span>
                                </div>
                                <div style="font-size: 11px; margin-top: 4px; color: #475569;">
                                    ${spfOk ? `বর্তমান: <code>${d.spf.record}</code>` : `প্রস্তাবিত TXT রেকর্ড (@): <code style="background:#fff; padding:2px 4px; border:1px solid #ccc; border-radius:3px;">${d.spf.recommended}</code>`}
                                </div>
                            </div>

                            <div style="padding: 8px; background: ${dmarcOk ? '#f0fdf4' : '#fffbeb'}; border: 1px solid ${dmarcOk ? '#bbf7d0' : '#fef3c7'}; border-radius: 6px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong>DMARC রেকর্ড:</strong>
                                    <span style="font-size: 11px; font-weight: 700; color: ${dmarcOk ? '#15803d' : '#b45309'};">${dmarcOk ? '✅ সক্রিয়' : (d.dmarc.status === 'invalid' ? '⚠️ ভুল সিনট্যাক্স' : '❌ অনুপস্থিত')}</span>
                                </div>
                                <div style="font-size: 11px; margin-top: 4px; color: #475569;">
                                    ${dmarcOk ? `বর্তমান: <code>${d.dmarc.record}</code>` : `প্রস্তাবিত TXT রেকর্ড (_dmarc): <code style="background:#fff; padding:2px 4px; border:1px solid #ccc; border-radius:3px;">${d.dmarc.recommended}</code>`}
                                </div>
                            </div>

                            <div style="padding: 8px; background: ${mxOk ? '#f0fdf4' : '#fef2f2'}; border: 1px solid ${mxOk ? '#bbf7d0' : '#fecdd3'}; border-radius: 6px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong>MX রেকর্ড:</strong>
                                    <span style="font-size: 11px; font-weight: 700; color: ${mxOk ? '#15803d' : '#b91c1c'};">${mxOk ? '✅ সক্রিয় (' + d.mx.count + ' টি)' : '❌ অনুপস্থিত'}</span>
                                </div>
                                <div style="font-size: 11px; margin-top: 4px; color: #475569;">
                                    ${mxOk ? `বর্তমান: ${d.mx.hosts?.join(', ')}` : `প্রস্তাবিত MX রেকর্ড: <code style="background:#fff; padding:2px 4px; border:1px solid #ccc; border-radius:3px;">${d.mx.recommended}</code>`}
                                </div>
                            </div>
                        </div>
                        ${!d.is_healthy ? `
                            <div style="margin-top: 10px; font-size: 11px; color: #b45309; line-height: 1.4;">
                                💡 <strong>করণীয়:</strong> আপনার ডোমেইন কন্ট্রোল প্যানেলে (cPanel / Cloudflare / DNS Provider) গিয়ে উপরের প্রস্তাবিত SPF ও DMARC TXT রেকর্ডগুলো যোগ করুন। তাহলে জিমেইল আর মেইল ব্লক করবে না এবং স্প্যামে যাবে না।
                            </div>
                        ` : ''}
                    `;
                } else {
                    resultBox.innerHTML = '<span style="color:#b91c1c;">DNS তথ্য রিট্রিভ করতে ব্যর্থ হয়েছে।</span>';
                }
            } catch (err) {
                resultBox.innerHTML = '<span style="color:#b91c1c;">ত্রুটি: ' + err.message + '</span>';
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'DNS স্বাস্থ্য চেক 🔍';
                }
            }
        }

        async function loadSmtpTemplates() {
            const container = document.getElementById('smtpTemplatesGrid');
            try {
                const res = await fetchAdminSmtp('/smtp/templates');
                const json = await res.json();
                const list = json.data || [];

                if (list.length === 0) {
                    container.innerHTML = '<div style="color: #64748b;">কোনো টেমপ্লেট পাওয়া যায়নি।</div>';
                    return;
                }

                container.innerHTML = list.map(t => `
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                                <strong style="font-size: 13px; color: #0f172a;">${t.name}</strong>
                                <span class="status-badge ${t.is_mandatory ? 'status-suspended' : 'status-active'}" style="font-size: 10px;">
                                    ${t.is_mandatory ? 'Mandatory' : 'Social'}
                                </span>
                            </div>
                            <p style="font-size: 11px; color: #64748b; line-height: 1.5; margin-bottom: 12px;">${t.description}</p>
                        </div>
                        <button type="button" class="btn-act" onclick="openTemplateModal('${t.key}', '${t.name}')" style="width: 100%; text-align: center; background: white;">
                            লাইভ প্রিভিউ দেখুন 👁️
                        </button>
                    </div>
                `).join('');
            } catch (err) {
                console.error('Failed to load templates:', err);
            }
        }

        async function loadSmtpLogs() {
            const tbody = document.getElementById('smtpLogsTableBody');
            const search = document.getElementById('smtpLogSearch')?.value || '';
            const status = document.getElementById('smtpLogStatusFilter')?.value || '';

            try {
                let url = `/smtp/logs?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
                const res = await fetchAdminSmtp(url);
                const json = await res.json();
                const paginated = json.data || {};
                const logs = paginated.data || [];

                document.getElementById('smtpLogsTotalBadge').innerText = `${paginated.total || logs.length} টি রেকর্ড`;

                if (logs.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 24px; color: #64748b;">কোনো ইমেইল ডেলিভারি লগ পাওয়া যায়নি।</td></tr>`;
                    return;
                }

                tbody.innerHTML = logs.map(l => {
                    const statusClass = l.status === 'sent' ? 'status-active' : (l.status === 'failed' ? 'status-suspended' : 'status-pending');
                    const time = l.sent_at || l.created_at || '--';
                    const safeErr = l.error_message ? `<div style="font-size: 11px; color: #dc2626; margin-top: 4px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${l.error_message}">⚠️ ${l.error_message}</div>` : '';

                    return `
                        <tr>
                            <td><strong>#${l.id}</strong></td>
                            <td>
                                <div style="font-weight: 600; color: #0f172a;">${l.recipient}</div>
                                <div style="font-size: 11px; color: #64748b;">IP: ${l.ip_address || 'System'}</div>
                            </td>
                            <td><span style="font-size: 11px; font-weight: 700; color: #2563eb; background: #eff6ff; padding: 2px 6px; border-radius: 4px;">${l.email_type || 'system'}</span></td>
                            <td>
                                <div style="font-size: 13px; color: #1e293b; font-weight: 500;">${l.subject || '--'}</div>
                                ${safeErr}
                            </td>
                            <td><span class="status-badge ${statusClass}">${l.status}</span></td>
                            <td>${l.attempts || 1} বার</td>
                            <td style="font-size: 12px; color: #64748b;">${new Date(time).toLocaleString('bn-BD')}</td>
                            <td style="text-align: right;">
                                ${l.status === 'failed' ? `<button type="button" class="btn-act" onclick="retryEmailLog(${l.id})" style="color: #dc2626;">রিট্রাই 🔄</button>` : `<span style="font-size: 11px; color: #16a34a;">সফল ✓</span>`}
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                console.error('Failed to load SMTP logs:', err);
                tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 20px; color: #dc2626;">লগ লোড করতে সমস্যা হয়েছে: ${err.message}</td></tr>`;
            }
        }

        async function retryEmailLog(id) {
            if (!confirm('আপনি কি নিশ্চিত যে এই ইমেইলটি পুনরায় পাঠাতে চান?')) return;
            try {
                const res = await fetchAdminSmtp(`/smtp/logs/${id}/retry`, {
                    method: 'POST'
                });
                const json = await res.json();
                if (res.ok && json.success) {
                    alert('ইমেইল সফলভাবে পুনরায় পাঠানো হয়েছে! ✅');
                    loadSmtpStats();
                    loadSmtpLogs();
                } else {
                    alert('রিট্রাই ব্যর্থ: ' + (json.message || 'অজানা ত্রুটি'));
                }
            } catch (err) {
                alert('অনুরোধ ব্যর্থ হয়েছে: ' + err.message);
            }
        }

        function openTemplateModal(key, title) {
            document.getElementById('previewModalTitle').innerText = title + ' — লাইভ প্রিভিউ';
            const iframe = document.getElementById('templatePreviewIframe');
            iframe.src = `/admin/smtp/templates/${key}/preview`;
            document.getElementById('templatePreviewModal').style.display = 'flex';
        }

        function closeTemplateModal() {
            document.getElementById('templatePreviewModal').style.display = 'none';
            document.getElementById('templatePreviewIframe').src = 'about:blank';
        }

        function setPreviewWidth(w) {
            document.getElementById('templatePreviewIframe').style.width = w;
        }

        // ==========================================
        // CITIZEN IDENTITY VERIFICATION QUEUE LOGIC
        // ==========================================
        let verificationSearchTimeout;
        let currentVerificationsPage = 1;
        let totalVerificationsPages = 1;
        let activeVerificationDetail = null;

        function debounceSearchVerifications() {
            clearTimeout(verificationSearchTimeout);
            verificationSearchTimeout = setTimeout(() => {
                loadVerifications(1);
            }, 350);
        }

        async function loadVerifications(page = 1) {
            currentVerificationsPage = page;
            const search = document.getElementById('verificationSearch')?.value?.trim() || '';
            const status = document.getElementById('verificationStatusFilter')?.value || '';
            const type = document.getElementById('verificationTypeFilter')?.value || '';
            const tbody = document.getElementById('verificationsTableBody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 24px;">তথ্য লোড হচ্ছে...</td></tr>';
            }

            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: 15
                });
                if (search) params.append('search', search);
                if (status) params.append('status', status);
                if (type) params.append('verification_type', type);

                const res = await adminFetch(`/api/v2/admin/verifications?${params.toString()}`);
                const json = await res.json();

                if (!res.ok || !json.success) {
                    throw new Error(json.message || 'ভেরিফিকেশন তালিকা লোড করতে সমস্যা হয়েছে');
                }

                const paginator = json.data;
                const items = paginator.data || [];
                totalVerificationsPages = paginator.last_page || 1;

                // Update page info
                const pageInfoEl = document.getElementById('verificationsPageInfo');
                if (pageInfoEl) {
                    pageInfoEl.innerText = `${paginator.total || 0} টি আবেদনের মধ্যে পৃষ্ঠা ${paginator.current_page || 1} / ${totalVerificationsPages} (মোট ${items.length} টি প্রদর্শিত)`;
                }
                const btnPrev = document.getElementById('btnPrevVerifications');
                if (btnPrev) btnPrev.disabled = currentVerificationsPage <= 1;
                const btnNext = document.getElementById('btnNextVerifications');
                if (btnNext) btnNext.disabled = currentVerificationsPage >= totalVerificationsPages;

                if (items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 32px; color: #64748b;">কোনো ভেরিফিকেশন আবেদন পাওয়া যায়নি।</td></tr>';
                    return;
                }

                tbody.innerHTML = items.map(item => {
                    const user = item.user || {};
                    const avatarLetter = (user.name || user.username || 'U').charAt(0).toUpperCase();
                    const avatarHtml = user.avatar
                        ? `<img src="${user.avatar}" alt="${user.name}" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover;">`
                        : `<div class="avatar">${avatarLetter}</div>`;

                    const docTypeMap = {
                        'nid': 'জাতীয় পরিচয়পত্র (NID)',
                        'passport': 'পাসপোর্ট',
                        'driving_license': 'ড্রাইভিং লাইসেন্স',
                        'organizational': 'প্রাতিষ্ঠানিক'
                    };
                    const docTypeLabel = docTypeMap[item.verification_type] || item.verification_type || '--';

                    let statusClass = 'status-pending';
                    let statusLabel = 'অপেক্ষমান';
                    if (item.status === 'verified' || item.status === 'approved') {
                        statusClass = 'status-active';
                        statusLabel = 'অনুমোদিত ও ভেরিফাইড';
                    } else if (item.status === 'rejected') {
                        statusClass = 'status-suspended';
                        statusLabel = 'বাতিলকৃত';
                    } else if (item.status === 'needs_info') {
                        statusClass = 'status-pending';
                        statusLabel = 'তথ্য প্রয়োজন';
                    }

                    const submitted = item.submitted_at ? new Date(item.submitted_at).toLocaleDateString('bn-BD', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : (item.created_at ? new Date(item.created_at).toLocaleDateString('bn-BD') : '--');

                    return `
                        <tr>
                            <td>#${item.id}</td>
                            <td>
                                <div class="user-cell">
                                    ${avatarHtml}
                                    <div>
                                        <div style="font-weight: 700; color: #0f172a;">${user.name || 'অজ্ঞাত'}</div>
                                        <div style="font-size: 11px; color: #64748b;">@${user.username || 'user'} • ID: ${user.id}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #334155;">${docTypeLabel}</span>
                            </td>
                            <td>
                                <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #0369a1;">${item.document_number || 'অনুল্লেখিত'}</code>
                            </td>
                            <td style="font-size: 12px; color: #64748b;">${submitted}</td>
                            <td>
                                <span class="status-badge ${statusClass}">${statusLabel}</span>
                            </td>
                            <td style="text-align: right;">
                                <div class="action-btns" style="justify-content: flex-end;">
                                    <button type="button" class="btn-act blue" onclick="openVerificationReviewModal(${item.id})">
                                        🔍 যাচাই ও পর্যালোচনা
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                console.error('Load verifications error:', err);
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 20px; color: #dc2626;">ত্রুটি: ${err.message}</td></tr>`;
                }
            }
        }

        function changeVerificationsPage(delta) {
            const next = currentVerificationsPage + delta;
            if (next >= 1 && next <= totalVerificationsPages) {
                loadVerifications(next);
            }
        }

        async function openVerificationReviewModal(id) {
            const modal = document.getElementById('verificationReviewModal');
            const feedback = document.getElementById('reviewActionFeedback');
            if (feedback) feedback.style.display = 'none';
            document.getElementById('reviewAdminNotes').value = '';

            try {
                const res = await adminFetch(`/api/v2/admin/verifications/${id}`);
                const json = await res.json();
                if (!res.ok || !json.success) {
                    alert('ভেরিফিকেশন বিস্তারিত লোড করা সম্ভব হয়নি: ' + (json.message || 'ত্রুটি'));
                    return;
                }

                activeVerificationDetail = json.data;
                const v = activeVerificationDetail;
                const user = v.user || {};

                document.getElementById('reviewUserName').innerText = `${user.name || 'অজ্ঞাত'} (@${user.username || 'user'})`;
                document.getElementById('reviewUserEmailPhone').innerText = `${user.email || 'ইমেইল নেই'} • ${user.phone || 'ফোন নেই'}`;

                const docTypeMap = {
                    'nid': 'জাতীয় পরিচয়পত্র (NID)',
                    'passport': 'আন্তর্জাতিক পাসপোর্ট',
                    'driving_license': 'ড্রাইভিং লাইসেন্স',
                    'organizational': 'প্রাতিষ্ঠানিক ডকুমেন্ট'
                };
                document.getElementById('reviewDocType').innerText = docTypeMap[v.verification_type] || v.verification_type;
                document.getElementById('reviewDocNumber').innerText = `ডকুমেন্ট নং: ${v.document_number || 'নেই'}`;

                document.getElementById('reviewSubmittedAt').innerText = v.submitted_at
                    ? new Date(v.submitted_at).toLocaleString('bn-BD')
                    : (v.created_at ? new Date(v.created_at).toLocaleString('bn-BD') : '--');

                const reviewer = v.admin ? (v.admin.name || v.admin.username) : 'এখনও হয়নি';
                document.getElementById('reviewReviewedBy').innerText = `পর্যালোচক: ${reviewer}`;

                const badge = document.getElementById('reviewModalStatusBadge');
                if (badge) {
                    badge.innerText = v.status.toUpperCase();
                }

                // Render document images/links
                renderDocPreview('reviewFrontPreview', v.document_front_url || v.document_front_key);
                renderDocPreview('reviewBackPreview', v.document_back_url || v.document_back_key);
                renderDocPreview('reviewSelfiePreview', v.selfie_url || v.selfie_key);

                // Configure buttons based on current status
                const isVerified = (v.status === 'verified' || v.status === 'approved');
                const btnApprove = document.getElementById('btnReviewApprove');
                const btnReject = document.getElementById('btnReviewReject');
                const btnNeedInfo = document.getElementById('btnReviewNeedInfo');
                const btnRevoke = document.getElementById('btnReviewRevoke');

                if (isVerified) {
                    if (btnApprove) btnApprove.style.display = 'none';
                    if (btnReject) btnReject.style.display = 'none';
                    if (btnNeedInfo) btnNeedInfo.style.display = 'none';
                    if (btnRevoke) btnRevoke.style.display = 'inline-block';
                } else {
                    if (btnApprove) btnApprove.style.display = 'inline-block';
                    if (btnReject) btnReject.style.display = 'inline-block';
                    if (btnNeedInfo) btnNeedInfo.style.display = 'inline-block';
                    if (btnRevoke) btnRevoke.style.display = 'none';
                }

                if (v.admin_notes) {
                    document.getElementById('reviewAdminNotes').value = v.admin_notes;
                } else if (v.rejection_reason) {
                    document.getElementById('reviewAdminNotes').value = v.rejection_reason;
                }

                if (modal) modal.style.display = 'flex';
            } catch (err) {
                console.error('Open verification error:', err);
                alert('অনুরোধ প্রক্রিয়াকরণে সমস্যা: ' + err.message);
            }
        }

        function renderDocPreview(containerId, urlOrKey) {
            const container = document.getElementById(containerId);
            if (!container) return;
            if (!urlOrKey) {
                container.innerHTML = '<span style="color: #94a3b8; font-size: 12px;">কোনো ছবি দাখিল করা হয়নি</span>';
                return;
            }

            const src = urlOrKey.startsWith('http') || urlOrKey.startsWith('/') ? urlOrKey : `/storage/${urlOrKey}`;
            container.innerHTML = `
                <div style="position: relative; width: 100%;">
                    <a href="${src}" target="_blank" rel="noopener noreferrer" title="পূর্ণ আকারে দেখুন">
                        <img src="${src}" alt="Document" style="max-height: 160px; max-width: 100%; object-fit: contain; border-radius: 4px; display: block; margin: 0 auto;" onerror="this.parentElement.innerHTML='<span style=\\'color:#64748b; font-size:11px;\\'>নথি ফাইল: <a href=\\'${src}\\' target=\\'_blank\\'>ডাউনলোড করুন</a></span>'">
                    </a>
                </div>
            `;
        }

        function closeVerificationReviewModal() {
            const modal = document.getElementById('verificationReviewModal');
            if (modal) modal.style.display = 'none';
            activeVerificationDetail = null;
        }

        async function submitVerificationDecision(action) {
            if (!activeVerificationDetail) return;
            const id = activeVerificationDetail.id;
            const notes = document.getElementById('reviewAdminNotes')?.value?.trim() || '';
            const feedback = document.getElementById('reviewActionFeedback');

            let url = '';
            let payload = {};

            if (action === 'approve') {
                if (!confirm('আপনি কি এই নাগরিকের পরিচয় ভেরিফিকেশন অনুমোদন এবং আইডিতে ব্লু ভেরিফাইড ব্যাজ প্রদান করতে নিশ্চিত?')) {
                    return;
                }
                url = `/api/v2/admin/verifications/${id}/approve`;
                payload = { admin_notes: notes };
            } else if (action === 'reject') {
                if (notes.length < 5) {
                    alert('বাতিল করার জন্য প্রশাসনিক মন্তব্য ঘরে কমপক্ষে ৫ অক্ষরের কারণ উল্লেখ করুন।');
                    return;
                }
                if (!confirm('আপনি কি এই ভেরিফিকেশন আবেদন বাতিল করতে নিশ্চিত?')) {
                    return;
                }
                url = `/api/v2/admin/verifications/${id}/reject`;
                payload = { rejection_reason: notes, admin_notes: notes };
            } else if (action === 'request-info') {
                if (notes.length < 5) {
                    alert('ব্যবহারকারীর কাছ থেকে তথ্য চাইতে মন্তব্য ঘরে কমপক্ষে ৫ অক্ষরের দিকনির্দেশনা লিখুন।');
                    return;
                }
                url = `/api/v2/admin/verifications/${id}/request-info`;
                payload = { instructions: notes };
            } else if (action === 'revoke') {
                if (notes.length < 5) {
                    alert('ভেরিফিকেশন স্ট্যাটাস প্রত্যাহারের জন্য কমপক্ষে ৫ অক্ষরের কারণ উল্লেখ করুন।');
                    return;
                }
                if (!confirm('সতর্কতা: এটি ব্যবহারকারীর ব্লু ব্যাজ ও ভেরিফাইড স্ট্যাটাস প্রত্যাহার করবে। নিশ্চিত?')) {
                    return;
                }
                url = `/api/v2/admin/verifications/${id}/revoke`;
                payload = { revocation_reason: notes };
            }

            try {
                if (feedback) {
                    feedback.style.display = 'block';
                    feedback.style.background = '#eff6ff';
                    feedback.style.color = '#1e40af';
                    feedback.innerText = 'অনুরোধ প্রক্রিয়াকরণ হচ্ছে...';
                }

                const res = await adminFetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();

                if (res.ok && json.success) {
                    alert(json.message || 'কার্যক্রম সফলভাবে সম্পন্ন হয়েছে!');
                    closeVerificationReviewModal();
                    loadVerifications(currentVerificationsPage);
                    loadStats();
                } else {
                    const errMsg = json.message || (json.errors ? Object.values(json.errors).flat().join(', ') : 'ত্রুটি ঘটেছে');
                    if (feedback) {
                        feedback.style.display = 'block';
                        feedback.style.background = '#fef2f2';
                        feedback.style.color = '#dc2626';
                        feedback.innerText = 'ব্যর্থ: ' + errMsg;
                    }
                    alert('ব্যর্থ: ' + errMsg);
                }
            } catch (err) {
                console.error('Submit decision error:', err);
                alert('অনুরোধ ব্যর্থ: ' + err.message);
            }
        }
    </script>
</body>
</html>
