<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bondhoo — Next-Generation Social Network</title>
        <meta name="description" content="Bondhoo — বন্ধু সোশ্যাল নেটওয়ার্ক। বন্ধু ও পরিজনদের সাথে যুক্ত থাকুন, মনের ভাব প্রকাশ করুন নিরাপদে।">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
        <link rel="apple-touch-icon" href="/images/bondhoo-icon-192.png">
        <link rel="manifest" href="/manifest.json">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #6366f1;
                --primary-glow: rgba(99, 102, 241, 0.35);
                --bg: #090d16;
                --surface: #111827;
                --border: #1f293d;
                --text: #f3f4f6;
                --text-muted: #9ca3af;
                --accent-emerald: #10b981;
            }
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            body {
                font-family: 'Hind Siliguri', 'Inter', sans-serif;
                background-color: var(--bg);
                color: var(--text);
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow-x: hidden;
            }
            .glow-bg {
                position: fixed;
                top: -20%;
                left: 50%;
                transform: translateX(-50%);
                width: 900px;
                height: 500px;
                background: radial-gradient(ellipse at center, var(--primary-glow) 0%, rgba(9, 13, 22, 0) 70%);
                z-index: 0;
                pointer-events: none;
            }
            header {
                position: relative;
                z-index: 10;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1.25rem 2.5rem;
                border-bottom: 1px solid var(--border);
                backdrop-filter: blur(12px);
                background: rgba(9, 13, 22, 0.7);
            }
            .logo {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                text-decoration: none;
                color: inherit;
            }
            .logo-img {
                height: 38px;
                width: auto;
                object-fit: contain;
                display: block;
            }
            .header-actions {
                display: flex;
                align-items: center;
                gap: 1rem;
            }
            .btn-nav-login {
                color: #e2e8f0;
                text-decoration: none;
                font-weight: 600;
                font-size: 0.95rem;
                padding: 0.5rem 1rem;
                border-radius: 8px;
                transition: color 0.2s;
            }
            .btn-nav-login:hover {
                color: #ffffff;
            }
            .btn-nav-register {
                background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
                color: #ffffff;
                text-decoration: none;
                font-weight: 600;
                font-size: 0.95rem;
                padding: 0.55rem 1.25rem;
                border-radius: 8px;
                box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .btn-nav-register:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 18px rgba(99, 102, 241, 0.45);
            }
            .badge-live {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                background: rgba(16, 185, 129, 0.12);
                border: 1px solid rgba(16, 185, 129, 0.3);
                color: #34d399;
                font-size: 0.75rem;
                padding: 0.25rem 0.65rem;
                border-radius: 9999px;
                font-weight: 500;
            }
            .pulse-dot {
                width: 7px;
                height: 7px;
                background-color: var(--accent-emerald);
                border-radius: 50%;
                box-shadow: 0 0 8px var(--accent-emerald);
                animation: pulse 2s infinite;
            }
            @keyframes pulse {
                0% { opacity: 0.4; transform: scale(0.9); }
                50% { opacity: 1; transform: scale(1.15); }
                100% { opacity: 0.4; transform: scale(0.9); }
            }
            main {
                position: relative;
                z-index: 10;
                max-width: 1100px;
                margin: 0 auto;
                padding: 3.5rem 1.5rem;
                display: flex;
                flex-direction: column;
                gap: 3rem;
            }
            .hero {
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 1.25rem;
            }
            .hero h1 {
                font-size: clamp(2.5rem, 5vw, 4rem);
                font-weight: 800;
                letter-spacing: -0.03em;
                line-height: 1.15;
                background: linear-gradient(180deg, #ffffff 40%, #9ca3af 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .hero p {
                max-width: 650px;
                font-size: 1.15rem;
                color: var(--text-muted);
                line-height: 1.6;
            }
            .hero-cta {
                display: flex;
                gap: 1rem;
                margin-top: 0.5rem;
                flex-wrap: wrap;
                justify-content: center;
            }
            .grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                gap: 1.5rem;
            }
            .card {
                background: rgba(17, 24, 39, 0.75);
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 1.75rem;
                backdrop-filter: blur(8px);
                transition: transform 0.2s ease, border-color 0.2s ease;
            }
            .card:hover {
                border-color: rgba(99, 102, 241, 0.4);
                transform: translateY(-2px);
            }
            .card-title {
                display: flex;
                align-items: center;
                gap: 0.6rem;
                font-size: 1.1rem;
                font-weight: 600;
                margin-bottom: 0.75rem;
            }
            .endpoint-badge {
                display: inline-block;
                padding: 0.2rem 0.5rem;
                border-radius: 4px;
                font-size: 0.7rem;
                font-weight: 700;
                font-family: monospace;
            }
            .post-badge { background: #1e3a5f; color: #60a5fa; }
            .get-badge { background: #143e33; color: #34d399; }
            .put-badge { background: #3b2a1a; color: #fbbf24; }
            .endpoint-list {
                list-style: none;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                margin-top: 1rem;
            }
            .endpoint-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-family: monospace;
                font-size: 0.85rem;
                padding: 0.6rem 0.8rem;
                background: rgba(15, 23, 42, 0.6);
                border-radius: 8px;
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            footer {
                position: relative;
                z-index: 10;
                padding: 2rem;
                text-align: center;
                font-size: 0.85rem;
                color: var(--text-muted);
                border-top: 1px solid var(--border);
            }
        </style>
    </head>
    <body>
        <div class="glow-bg"></div>

        <header>
            <a href="/" class="logo" title="Bondhoo">
                <img src="/images/bondhoo-logo-white.png" alt="Bondhoo" class="logo-img">
            </a>
            <div class="header-actions">
                <div class="badge-live">
                    <div class="pulse-dot"></div>
                    Bondhoo Core Online
                </div>
                <a href="/login" class="btn-nav-login">লগইন</a>
                <a href="/register" class="btn-nav-register">নতুন অ্যাকাউন্ট</a>
            </div>
        </header>

        <main>
            <section class="hero">
                <div class="badge-live">
                    Enterprise Social Platform • Clean Service Layer
                </div>
                <h1>Bondhoo — আধুনিক সামাজিক যোগাযোগ</h1>
                <p>
                    বন্ধু ও শুভাকাঙ্ক্ষীদের সাথে সহজে যোগাযোগ, ছবি ও ভিডিও প্রকাশ, রিয়েলটাইম মেসেজিং ও অডিও-ভিডিও কলের নিরাপদ উন্মুক্ত ঠিকানা।
                </p>
                <div class="hero-cta">
                    <a href="/register" class="btn-nav-register" style="padding: 0.85rem 2rem; font-size: 1.05rem;">এখনই শুরু করুন &rarr;</a>
                    <a href="/login" class="btn-nav-login" style="border: 1px solid var(--border); padding: 0.85rem 1.75rem; font-size: 1.05rem;">অ্যাকাউন্টে প্রবেশ করুন</a>
                </div>
            </section>

            <div class="grid">
                <div class="card">
                    <div class="card-title">
                        🛡️ Core Authentication & RBAC
                    </div>
                    <p style="font-size: 0.95rem; color: var(--text-muted); line-height: 1.5;">
                        Multi-identifier login (Email/Phone/Username), Sanctum Bearer tokens,
                        brute-force rate limiting, and 6-tier RBAC system (Super Admin, Admin, Moderator, Support, Analyst, User).
                    </p>
                    <ul class="endpoint-list">
                        <li class="endpoint-item">
                            <span>/api/v2/auth/register</span>
                            <span class="endpoint-badge post-badge">POST</span>
                        </li>
                        <li class="endpoint-item">
                            <span>/api/v2/auth/login</span>
                            <span class="endpoint-badge post-badge">POST</span>
                        </li>
                        <li class="endpoint-item">
                            <span>/api/v2/auth/me</span>
                            <span class="endpoint-badge get-badge">GET</span>
                        </li>
                    </ul>
                </div>

                <div class="card">
                    <div class="card-title">
                        👤 User Profile & Social Experience
                    </div>
                    <p style="font-size: 0.95rem; color: var(--text-muted); line-height: 1.5;">
                        Comprehensive feed, stories, reels, comments, reactions, media suite, and real-time messaging with end-to-end security.
                    </p>
                    <ul class="endpoint-list">
                        <li class="endpoint-item">
                            <span>/dashboard</span>
                            <span class="endpoint-badge get-badge">GET</span>
                        </li>
                        <li class="endpoint-item">
                            <span>/api/v1/profile</span>
                            <span class="endpoint-badge put-badge">PUT</span>
                        </li>
                        <li class="endpoint-item">
                            <span>/api/v1/profile/settings</span>
                            <span class="endpoint-badge put-badge">PUT</span>
                        </li>
                    </ul>
                </div>

                <div class="card">
                    <div class="card-title">
                        ⚡ Infrastructure & Specifications
                    </div>
                    <p style="font-size: 0.95rem; color: var(--text-muted); line-height: 1.5;">
                        Laravel 13.x • PHP 8.5 • Sanctum API Engine • Soft-delete enabled • Rate limiting active • Audit trail recording.
                    </p>
                    <ul class="endpoint-list">
                        <li class="endpoint-item">
                            <span>Super Admin Account</span>
                            <span style="color: #a78bfa;">@admin</span>
                        </li>
                        <li class="endpoint-item">
                            <span>Standard API Version</span>
                            <span style="color: #34d399;">v2 / v1</span>
                        </li>
                        <li class="endpoint-item">
                            <span>Brand Identity</span>
                            <span style="color: #60a5fa;">Bondhoo</span>
                        </li>
                    </ul>
                </div>
            </div>
        </main>

        <footer>
            &copy; {{ date('Y') }} <strong>Bondhoo</strong> Social Network. Developed with Enterprise Clean Architecture.
        </footer>
    </body>
</html>
