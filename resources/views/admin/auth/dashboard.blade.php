<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অথেন্টিকেশন ও সিকিউরিটি অ্যাডমিন ড্যাশবোর্ড — Bondhoo</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/bondhoo-favicon.png">
    <link rel="apple-touch-icon" href="/images/bondhoo-icon-192.png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        body { background: var(--fb-bg); color: var(--fb-text-primary); min-height: 100vh; display: flex; flex-direction: column; }

        header { background: white; border-bottom: 1px solid var(--fb-border); height: 64px; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .logo-box { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .logo-circle { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #1877f2, #0052cc); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 22px; box-shadow: 0 2px 6px rgba(24,119,242,0.4); }
        .logo-title { font-weight: 700; font-size: 20px; color: var(--fb-primary); }
        .badge-gov { background: #e7f3ff; color: var(--fb-primary); border: 1px solid #1877f2; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; letter-spacing: 0.3px; }

        .header-actions { display: flex; align-items: center; gap: 12px; }
        .btn-back { color: var(--fb-text-secondary); text-decoration: none; font-weight: 600; font-size: 13px; padding: 8px 14px; border-radius: 6px; border: 1px solid var(--fb-border); background: white; transition: all 0.2s; }
        .btn-back:hover { background: #f0f2f5; color: var(--fb-primary); }

        .container { max-width: 1440px; width: 100%; margin: 0 auto; padding: 24px 16px; flex: 1; }

        /* Section Header */
        .section-header { margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .section-title { font-size: 22px; font-weight: 800; color: #1c1e21; }
        .section-subtitle { font-size: 13px; color: var(--fb-text-secondary); }

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
        .tabs-header { display: flex; gap: 6px; border-bottom: 1px solid var(--fb-border); margin-bottom: 20px; overflow-x: auto; padding-bottom: 2px; }
        .tab-btn { padding: 10px 16px; font-size: 14px; font-weight: 600; border: none; background: none; cursor: pointer; color: var(--fb-text-secondary); border-bottom: 3px solid transparent; white-space: nowrap; transition: all 0.2s; }
        .tab-btn:hover { color: var(--fb-primary); }
        .tab-btn.active { color: var(--fb-primary); border-bottom-color: var(--fb-primary); }

        .controls-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; justify-content: space-between; align-items: center; }
        .search-input { height: 38px; padding: 0 12px; border: 1px solid var(--fb-border); border-radius: 6px; font-size: 13px; width: 280px; }
        .filter-select { height: 38px; padding: 0 10px; border: 1px solid var(--fb-border); border-radius: 6px; font-size: 13px; background: white; }

        /* Export Button Group */
        .export-group { display: flex; gap: 8px; }
        .btn-export { height: 38px; padding: 0 14px; background: var(--fb-primary); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: background 0.2s; }
        .btn-export:hover { background: var(--fb-primary-hover); }
        .btn-export.secondary { background: white; color: var(--fb-text-primary); border: 1px solid var(--fb-border); }
        .btn-export.secondary:hover { background: #f0f2f5; }

        /* Data Tables */
        .table-card { background: white; border: 1px solid var(--fb-border); border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        th { background: #f7f8fa; padding: 12px 14px; font-weight: 700; color: #4b4f56; border-bottom: 1px solid var(--fb-border); font-size: 12px; text-transform: uppercase; }
        td { padding: 12px 14px; border-bottom: 1px solid var(--fb-border); vertical-align: middle; }
        tr:hover { background: #fafbfc; }

        .user-cell { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: #e4e6eb; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--fb-primary); font-size: 14px; }
        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .status-active { background: #eafbe7; color: var(--fb-green); }
        .status-pending { background: #fffbe6; color: #d48806; }
        .status-suspended { background: #fff1f0; color: var(--fb-red); }
        .status-banned { background: #333; color: white; }

        .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-act { padding: 4px 10px; font-size: 11px; font-weight: 600; border-radius: 4px; border: 1px solid var(--fb-border); background: white; cursor: pointer; transition: all 0.15s; }
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
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; padding: 20px; }
        .modal-content { background: white; width: 100%; max-width: 500px; border-radius: 8px; padding: 24px; box-shadow: 0 8px 30px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .modal-title { font-size: 18px; font-weight: 700; }
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; }
    </style>
</head>
<body>
    <header>
        <div class="logo-box" style="display: flex; align-items: center; gap: 10px;">
            <img src="/images/bondhoo-icon.png" alt="Bondhoo" style="width: 32px; height: 32px; object-fit: contain;">
            <span class="logo-title">Bondhoo Admin</span>
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
        </div>

        <!-- Navigation Tabs -->
        <div class="tabs-header">
            <button class="tab-btn active" onclick="switchTab('users')">👥 ব্যবহারকারী তালিকা</button>
            <button class="tab-btn" onclick="switchTab('sessions')">💻 সক্রিয় সেশন (Revoke)</button>
            <button class="tab-btn" onclick="switchTab('failed')">🛡️ ব্যর্থ লগইন মনিটর</button>
            <button class="tab-btn" onclick="switchTab('otps')">📱 ওটিপি অডিট লগ</button>
            <button class="tab-btn" onclick="switchTab('emails')">✉️ ইমেইল ভেরিফিকেশন লগ</button>
            <button class="tab-btn" onclick="switchTab('passwords')">🔑 পাসওয়ার্ড রিসেট লগ</button>
            <button class="tab-btn" onclick="switchTab('analytics')">📊 লগইন অ্যানালিটিক্স</button>
            <button class="tab-btn" onclick="switchTab('audits')">📜 অডিট ট্রেইল</button>
            <button class="tab-btn" onclick="switchTab('media')">📹 মিডিয়া ও রিসোর্স কন্ট্রোল</button>
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
    </main>

    <script>
        const token = localStorage.getItem('jugajug_token') || localStorage.getItem('admin_token') || '';

        function getAuthHeaders() {
            const h = { 'Accept': 'application/json' };
            if (token) {
                h['Authorization'] = 'Bearer ' + token;
            }
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrf) {
                h['X-CSRF-TOKEN'] = csrf;
            }
            return h;
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadAll();
        });

        function loadAll() {
            loadStats();
            loadUsers();
        }

        let globalStatsData = null;

        async function loadStats() {
            try {
                const res = await fetch('/api/v2/admin/auth/stats', { headers: getAuthHeaders() });
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
                }
            } catch (err) {
                console.error('Stats loading error:', err);
            }
        }

        // --- TAB SWITCHING ---
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');

            ['users', 'sessions', 'failed', 'otps', 'emails', 'passwords', 'analytics', 'audits', 'media'].forEach(t => {
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
                let url = `/api/v2/admin/auth/users?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
                if (locked) url += '&locked_only=true';

                const res = await fetch(url, { headers: getAuthHeaders() });
                const json = await res.json();
                const users = json.data?.data || [];

                if (users.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 20px;">কোনো ব্যবহারকারী পাওয়া যায়নি।</td></tr>`;
                    return;
                }

                tbody.innerHTML = users.map(u => `
                    <tr>
                        <td>#${u.id}</td>
                        <td>
                            <div class="user-cell">
                                <div class="avatar">${(u.name || u.username).charAt(0).toUpperCase()}</div>
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
                tbody.innerHTML = `<tr><td colspan="7" style="color: red; text-align: center; padding: 20px;">ডাটা লোড ত্রুটি: ${err.message}</td></tr>`;
            }
        }

        async function performAction(action, id) {
            if (!confirm(`আপনি কি এই অপারেশনটি (${action}) নিশ্চিত করতে চান?`)) return;
            try {
                const res = await fetch(`/api/v2/admin/auth/users/${id}/${action}`, {
                    method: 'POST',
                    headers: getAuthHeaders()
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
                const res = await fetch(`/api/v2/admin/auth/sessions?search=${encodeURIComponent(search)}&os=${encodeURIComponent(os)}`, {
                    headers: getAuthHeaders()
                });
                const json = await res.json();
                const list = json.data?.data || [];

                if (list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 20px;">কোনো সক্রিয় সেশন নেই</td></tr>`;
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
                tbody.innerHTML = `<tr><td colspan="8" style="color: red; text-align: center;">ত্রুটি: ${err.message}</td></tr>`;
            }
        }

        async function revokeSession(sessionId) {
            if (!confirm(`আপনি কি এই সেশনটি (#${sessionId}) বাতিল করতে চান? ব্যবহারকারী উক্ত ডিভাইস থেকে তাৎক্ষণিকভাবে লগআউট হয়ে যাবেন।`)) return;

            try {
                const res = await fetch(`/api/v2/admin/auth/sessions/${sessionId}`, {
                    method: 'DELETE',
                    headers: getAuthHeaders()
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
                const res = await fetch('/api/v2/admin/auth/failed-logins', { headers: getAuthHeaders() });
                const json = await res.json();
                const list = json.data || [];
                if (list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px;">কোনো ব্যর্থ লগইন রেকর্ড নেই</td></tr>`;
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
                tbody.innerHTML = `<tr><td colspan="6" style="color: red; text-align: center;">ত্রুটি</td></tr>`;
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
                const res = await fetch(`/api/v2/admin/auth/otp-logs?search=${encodeURIComponent(search)}&purpose=${encodeURIComponent(purpose)}`, {
                    headers: getAuthHeaders()
                });
                const json = await res.json();
                const list = json.data?.data || [];
                if (list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px;">কোনো ওটিপি রেকর্ড পাওয়া যায়নি</td></tr>`;
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
                tbody.innerHTML = `<tr><td colspan="6" style="color: red; text-align: center;">ত্রুটি</td></tr>`;
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
                const res = await fetch(`/api/v2/admin/auth/email-verifications?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`, {
                    headers: getAuthHeaders()
                });
                const json = await res.json();
                const list = json.data?.data || [];
                if (list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px;">কোনো ইমেইল ভেরিফিকেশন রেকর্ড পাওয়া যায়নি</td></tr>`;
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
                tbody.innerHTML = `<tr><td colspan="6" style="color: red; text-align: center;">ত্রুটি</td></tr>`;
            }
        }

        // --- PASSWORD RESET LOGS ---
        async function loadPasswords() {
            const tbody = document.getElementById('passwordsTableBody');
            try {
                const res = await fetch('/api/v2/admin/auth/password-resets', { headers: getAuthHeaders() });
                const json = await res.json();
                const list = json.data?.data || [];
                if (list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 20px;">কোনো পাসওয়ার্ড পরিবর্তন রেকর্ড নেই</td></tr>`;
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
                tbody.innerHTML = `<tr><td colspan="4" style="color: red; text-align: center;">ত্রুটি</td></tr>`;
            }
        }

        // --- LOGIN ANALYTICS RENDERING ---
        function renderAnalytics() {
            if (!globalStatsData || !globalStatsData.login_analytics) return;
            const a = globalStatsData.login_analytics;

            // 1. Status
            const sBox = document.getElementById('analyticsStatusBox');
            const total = a.total || 1;
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

            // 2. Browsers
            const bBox = document.getElementById('analyticsBrowserBox');
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

            // 3. OS
            const oBox = document.getElementById('analyticsOsBox');
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

            // 4. Device
            const dBox = document.getElementById('analyticsDeviceBox');
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

            // 5. 7-Day Trend Table
            const dTable = document.getElementById('analyticsDaysTableBody');
            dTable.innerHTML = Object.entries(a.last_7_days || {}).map(([date, count]) => `
                <tr>
                    <td style="font-weight:700;">${date}</td>
                    <td><span class="status-badge" style="background:#eafbe7; color:var(--fb-green);">${count} টি লগইন</span></td>
                </tr>
            `).join('') || '<tr><td colspan="2" style="text-align:center;">কোনো ট্রেন্ড ডাটা নেই</td></tr>';
        }

        // --- AUDIT TRAIL ---
        async function loadAudits() {
            const tbody = document.getElementById('auditsTableBody');
            try {
                const res = await fetch('/api/v2/admin/auth/audit-logs', { headers: getAuthHeaders() });
                const json = await res.json();
                const list = json.data?.data || [];
                if (list.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px;">কোনো অডিট রেকর্ড নেই</td></tr>`;
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
                tbody.innerHTML = `<tr><td colspan="6" style="color: red; text-align: center;">ত্রুটি</td></tr>`;
            }
        }

        // --- MEDIA & RESOURCE CONTROLS ---
        async function loadMediaMetrics() {
            try {
                const res = await fetch('/api/v2/admin/media/metrics', { headers: getAuthHeaders() });
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

                const res = await fetch('/api/v2/admin/media/settings', {
                    method: 'PUT',
                    headers: {
                        ...getAuthHeaders(),
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
    </script>
</body>
</html>
