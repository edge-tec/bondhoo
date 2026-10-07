<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>আমার ডিভাইসসমূহ — Bondhoo</title>
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
        .logo { font-size: 26px; font-weight: 800; color: var(--fb-primary); text-decoration: none; letter-spacing: -0.5px; }
        .back-link { font-size: 14px; font-weight: 600; color: var(--fb-text-secondary); text-decoration: none; padding: 6px 12px; border-radius: 6px; background: #e4e6eb; transition: background 0.2s; }
        .back-link:hover { background: #d8dadf; }
        
        .page-title { font-size: 24px; font-weight: 700; margin-bottom: 6px; }
        .page-subtitle { font-size: 14px; color: var(--fb-text-secondary); margin-bottom: 20px; line-height: 1.4; }

        .alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; }
        .alert-success { background: #e7f3ff; color: #1877f2; border: 1px solid #1877f2; }
        .alert-error { background: #ffebe8; color: #e41e3f; border: 1px solid #e41e3f; }

        .card { background: var(--fb-card-bg); border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); border: 1px solid #dddfe2; padding: 20px; margin-bottom: 20px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .card-title { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        
        .pulse-badge { display: inline-flex; align-items: center; gap: 6px; background: #e7f8ec; color: #1e8e3e; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 12px; }
        .pulse-dot { width: 8px; height: 8px; background: #1e8e3e; border-radius: 50%; box-shadow: 0 0 0 0 rgba(30, 142, 62, 0.7); animation: pulse 1.6s infinite; }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(30, 142, 62, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(30, 142, 62, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(30, 142, 62, 0); }
        }

        .device-item { display: flex; align-items: center; justify-content: space-between; padding: 14px; border-radius: 8px; background: #f7f8fa; margin-bottom: 12px; border: 1px solid #ebedf0; }
        .device-item.current { background: #f0f7ff; border-color: #b2d7ff; }
        .device-icon { font-size: 28px; width: 44px; height: 44px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08); flex-shrink: 0; margin-right: 14px; }
        .device-info { flex-grow: 1; }
        .device-name { font-size: 15px; font-weight: 700; color: #050505; margin-bottom: 2px; }
        .device-meta { font-size: 13px; color: var(--fb-text-secondary); display: flex; flex-wrap: wrap; gap: 10px; }
        .device-meta span { display: inline-flex; align-items: center; gap: 4px; }

        .btn-action { padding: 6px 14px; font-size: 13px; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-danger { background: #ffebe8; color: var(--fb-red); }
        .btn-danger:hover { background: #fcdbd8; }
        .btn-primary { background: var(--fb-primary); color: white; }
        .btn-primary:hover { background: #166fe5; }
        .btn-secondary { background: #e4e6eb; color: #050505; }
        .btn-secondary:hover { background: #d8dadf; }
        .btn-rotate { background: #e7f3ff; color: var(--fb-primary); font-size: 13px; }
        .btn-rotate:hover { background: #dbeafe; }

        .logout-all-box { display: flex; align-items: center; justify-content: space-between; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 8px; padding: 18px 20px; margin-top: 24px; }
        .logout-all-text h4 { font-size: 16px; font-weight: 700; color: #9b2c2c; margin-bottom: 4px; }
        .logout-all-text p { font-size: 13px; color: #742a2a; }
        .btn-logout-all { background: var(--fb-red); color: white; font-weight: 700; padding: 10px 18px; border-radius: 6px; border: none; cursor: pointer; transition: background 0.2s; }
        .btn-logout-all:hover { background: #cc1836; }

        .empty-state { text-align: center; padding: 24px; color: var(--fb-text-secondary); font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation Bar -->
        <header class="header-bar">
            <a href="/" class="logo" style="display: inline-flex; align-items: center;">
                <img src="/images/bondhoo-logo.png" alt="Bondhoo" style="height: 36px; max-width: 160px; object-fit: contain;">
            </a>
            <a href="/dashboard" class="back-link">← ড্যাশবোর্ডে ফিরুন</a>
        </header>

        <h1 class="page-title">আমার ডিভাইসসমূহ ও সক্রিয় সেশন</h1>
        <p class="page-subtitle">আপনার অ্যাকাউন্টে বর্তমানে কোন কোন ডিভাইস থেকে লগইন রয়েছে তা দেখুন এবং অনাকাঙ্ক্ষিত সেশন অবিলম্বে লগআউট করুন।</p>

        @if(session('success'))
            <div class="alert alert-success">
                <span>✓ {{ session('success') }}</span>
            </div>
        @endif

        @if(session('errors') && session('errors')->has('error'))
            <div class="alert alert-error">
                <span>✕ {{ session('errors')->first('error') }}</span>
            </div>
        @endif

        <!-- 1. Current Active Device Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <span style="display:inline-flex;align-items:center;gap:8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                        <span>বর্তমান ডিভাইস</span>
                    </span>
                    <span class="pulse-badge"><span class="pulse-dot"></span> সক্রিয় (Active)</span>
                </h2>
                <form action="{{ route('session.rotate') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn-action btn-rotate" title="সেশন ফিক্সেশন থেকে নিরাপদ থাকতে নতুন আইডি জেনারেট করুন" style="display:inline-flex;align-items:center;gap:6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                        <span>সেশন আইডি রোটেট করুন</span>
                    </button>
                </form>
            </div>

            <div class="device-item current">
                <div class="device-icon">
                    @if(preg_match('/mobile|android|iphone/i', $currentMeta['os'] ?? ''))
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    @else
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                    @endif
                </div>
                <div class="device-info">
                    <div class="device-name">
                        {{ $currentSession->device_name ?? ($currentMeta['browser'] . ' on ' . $currentMeta['os']) }}
                        <strong style="color: var(--fb-primary); font-size: 12px; margin-left: 6px;">(এই ব্রাউজার)</strong>
                    </div>
                    <div class="device-meta">
                        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg> IP: {{ $currentSession->ip_address ?? $currentMeta['ip'] }}</span>
                        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> দেশ: {{ $currentSession->location ?? $currentMeta['country'] }}</span>
                        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> সর্বশেষ সক্রিয়: এইমাত্র</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Other Active Sessions Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <span style="display:inline-flex;align-items:center;gap:8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        <span>অন্যান্য সক্রিয় সেশন ({{ $sessions->where('is_current', false)->count() }})</span>
                    </span>
                </h2>
            </div>

            @php
                $otherSessions = $sessions->filter(function($s) use ($currentSession) {
                    return $s->id !== ($currentSession->id ?? null) && ! $s->is_current;
                });
            @endphp

            @if($otherSessions->isEmpty())
                <div class="empty-state">
                    অন্য কোনো ডিভাইসে আপনার অ্যাকাউন্ট সক্রিয় নেই। আপনার অ্যাকাউন্ট সম্পূর্ণ সুরক্ষিত।
                </div>
            @else
                @foreach($otherSessions as $session)
                    <div class="device-item">
                        <div class="device-icon">
                            @if(preg_match('/mobile|android|iphone/i', $session->os ?? ''))
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                            @else
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                            @endif
                        </div>
                        <div class="device-info">
                            <div class="device-name">{{ $session->device_name ?: ($session->browser . ' on ' . $session->os) }}</div>
                            <div class="device-meta">
                                <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg> IP: {{ $session->ip_address }}</span>
                                <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> দেশ: {{ $session->location ?: 'BD' }}</span>
                                <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> সক্রিয়: {{ $session->last_active_at ? $session->last_active_at->diffForHumans() : 'কিছুক্ষণ আগে' }}</span>
                            </div>
                        </div>
                        <form action="{{ route('devices.revoke', $session->id) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত এই ডিভাইসটি লগআউট করতে চান?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-action btn-danger">লগআউট</button>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- 3. Trusted Devices Card (Remember Me) -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <span style="display:inline-flex;align-items:center;gap:8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>বিশ্বস্ত ডিভাইসসমূহ (Remember Me) ({{ $trustedDevices->count() }})</span>
                    </span>
                </h2>
            </div>

            @if($trustedDevices->isEmpty())
                <div class="empty-state">
                    কোনো সংরক্ষিত বিশ্বস্ত ডিভাইস নেই। লগইন করার সময় "মনে রাখুন" নির্বাচন করলে এখানে প্রদর্শিত হবে।
                </div>
            @else
                @foreach($trustedDevices as $trusted)
                    <div class="device-item">
                        <div class="device-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </div>
                        <div class="device-info">
                            <div class="device-name">{{ $trusted->device_name ?: ($trusted->browser . ' on ' . $trusted->os) }}</div>
                            <div class="device-meta">
                                <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg> IP: {{ $trusted->ip_address }}</span>
                                <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> মেয়াদ শেষ: {{ $trusted->trusted_until ? $trusted->trusted_until->format('d M, Y') : 'অসীম' }}</span>
                            </div>
                        </div>
                        <form action="{{ route('devices.trusted.revoke', $trusted->id) }}" method="POST" onsubmit="return confirm('এই বিশ্বস্ত ডিভাইসটি মুছে ফেলতে চান?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-action btn-secondary">মুছুন</button>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- 4. Device Login History Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <span style="display:inline-flex;align-items:center;gap:8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>ডিভাইস লগইন হিস্টোরি (Login History)</span>
                    </span>
                </h2>
            </div>

            @if(!isset($loginHistories) || $loginHistories->isEmpty())
                <div class="empty-state">
                    লগইন সংক্রান্ত কোনো পূর্ববর্তী রেকর্ড পাওয়া যায়নি।
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e4e6eb; color: var(--fb-text-secondary);">
                                <th style="padding: 10px 8px;">ডিভাইস ও ব্রাউজার</th>
                                <th style="padding: 10px 8px;">আইপি ও অবস্থান</th>
                                <th style="padding: 10px 8px;">লগইন সময়</th>
                                <th style="padding: 10px 8px;">লগআউট সময় / স্ট্যাটাস</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loginHistories as $history)
                                <tr style="border-bottom: 1px solid #ebedf0;">
                                    <td style="padding: 12px 8px;">
                                        <strong>{{ $history->device ?: ($history->browser . ' on ' . $history->os) }}</strong>
                                        <div style="color: var(--fb-text-secondary); font-size: 11px;">
                                            {{ $history->browser }} • {{ $history->os }}
                                        </div>
                                    </td>
                                    <td style="padding: 12px 8px;">
                                        <code>{{ $history->ip_address }}</code>
                                        <div style="color: var(--fb-text-secondary); font-size: 11px; display:inline-flex; align-items:center; gap:3px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                            <span>{{ $history->city ?: 'Dhaka' }}, {{ $history->country ?: 'BD' }}</span>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 8px; color: var(--fb-text-secondary);">
                                        {{ $history->login_at ? $history->login_at->format('d M, Y h:i A') : ($history->created_at ? $history->created_at->format('d M, Y h:i A') : '—') }}
                                    </td>
                                    <td style="padding: 12px 8px;">
                                        @if($history->logout_at)
                                            <span style="color: var(--fb-text-secondary);">
                                                {{ $history->logout_at->format('d M, Y h:i A') }}
                                            </span>
                                        @elseif($history->status === 'success')
                                            <span style="background: #e7f8ec; color: #1e8e3e; padding: 2px 8px; border-radius: 10px; font-weight: 600; font-size: 11px;">
                                                সক্রিয় (Active)
                                            </span>
                                        @else
                                            <span style="background: #ffebe8; color: var(--fb-red); padding: 2px 8px; border-radius: 10px; font-weight: 600; font-size: 11px;">
                                                {{ $history->status }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- 4. Global Logout All Devices Box -->
        <div class="logout-all-box">
            <div class="logout-all-text">
                <h4>নিরাপত্তা শঙ্কা রয়েছে?</h4>
                <p>সব ডিভাইস ও ব্রাউজার থেকে তাৎক্ষণিকভাবে লগআউট করতে নিচের বাটনে ক্লিক করুন।</p>
            </div>
            <form action="{{ route('devices.logout-all') }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে সকল ডিভাইস থেকে লগআউট করতে চান? এটি আপনার বর্তমান সেশনটিও বন্ধ করে দেবে।');">
                @csrf
                <button type="submit" class="btn-logout-all">সকল ডিভাইস থেকে লগআউট</button>
            </form>
        </div>
    </div>
</body>
</html>
