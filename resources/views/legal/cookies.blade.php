<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>কুকিজ ও ডেটা পলিসি (Cookie Policy) — SocialHub</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --fb-bg: #f0f2f5;
            --fb-primary: #1877f2;
            --fb-border: #ccd0d5;
            --fb-card-bg: #ffffff;
            --fb-text-primary: #050505;
            --fb-text-secondary: #65676b;
            --fb-hover: #e4e6eb;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Hind Siliguri', 'Inter', sans-serif; }
        body {
            background: var(--fb-bg);
            color: var(--fb-text-primary);
            min-height: 100vh;
            line-height: 1.65;
        }

        .header-bar {
            background: #ffffff;
            border-bottom: 1px solid var(--fb-border);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .brand-logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--fb-primary);
            text-decoration: none;
        }
        .nav-link {
            font-size: 14px;
            font-weight: 600;
            color: var(--fb-text-secondary);
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 6px;
            background: var(--fb-hover);
            transition: all 0.2s;
        }
        .nav-link:hover { color: var(--fb-text-primary); background: #d8dadf; }

        .legal-container {
            max-width: 920px;
            margin: 32px auto;
            padding: 0 16px;
        }
        .legal-card {
            background: var(--fb-card-bg);
            border-radius: 12px;
            border: 1px solid var(--fb-border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 36px;
        }
        @media (max-width: 640px) {
            .legal-card { padding: 20px; }
        }

        .legal-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--fb-text-primary);
            margin-bottom: 6px;
        }
        .legal-subtitle {
            font-size: 15px;
            color: var(--fb-text-secondary);
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--fb-border);
        }

        .section-box {
            margin-bottom: 28px;
        }
        .section-heading {
            font-size: 19px;
            font-weight: 700;
            color: var(--fb-text-primary);
            margin-bottom: 10px;
        }
        .section-subtext {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 12px;
            line-height: 1.7;
        }
        .bullet-list {
            padding-left: 20px;
            margin-bottom: 12px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.7;
        }
        .bullet-list li { margin-bottom: 6px; }

        .cookie-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 13.5px;
        }
        .cookie-table th, .cookie-table td {
            padding: 10px 14px;
            border: 1px solid var(--fb-border);
            text-align: left;
        }
        .cookie-table th {
            background: #f8fafc;
            font-weight: 700;
            color: var(--fb-text-primary);
        }

        .footer-note {
            margin-top: 36px;
            padding-top: 20px;
            border-top: 1px solid var(--fb-border);
            font-size: 13px;
            color: var(--fb-text-secondary);
            text-align: center;
        }
    </style>
</head>
<body>

    <header class="header-bar">
        <a href="/dashboard" class="brand-logo" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:9px;background:#1877f2;color:#fff;box-shadow:0 2px 6px rgba(24,119,242,0.35);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </span>
            <span style="font-size:1.35rem;font-weight:800;letter-spacing:-0.03em;color:#1877f2;font-family:'Inter',sans-serif;">SocialHub</span>
        </a>
        <div style="display: flex; gap: 8px;">
            <a href="/settings" class="nav-link">সেটিংস সেন্টার</a>
            <a href="/terms" class="nav-link">ব্যবহারের শর্তাবলী</a>
            <a href="/dashboard" class="nav-link">ড্যাশবোর্ড</a>
        </div>
    </header>

    <main class="legal-container">
        <article class="legal-card">
            <h1 class="legal-title">কুকিজ ও ডেটা পলিসি (Cookie & Data Policy)</h1>
            <p class="legal-subtitle">সর্বশেষ হালনাগাদ: অক্টোবর ২০২৬ · আমরা কিভাবে কুকিজ এবং সমজাতীয় প্রযুক্তি ব্যবহার করি</p>

            <div class="section-box">
                <h2 class="section-heading">১. কুকিজ কী? (What are Cookies?)</h2>
                <p class="section-subtext">
                    কুকিজ হলো ছোট টেক্সট ফাইল যা আপনার কম্পিউটার বা মোবাইল ডিভাইসে আপনার ব্রাউজার দ্বারা সংরক্ষিত হয় যখন আপনি কোনো ওয়েবসাইট ভিজিট করেন। কুকিজ ব্যবহারের ফলে আপনার সেশন মনে রাখা যায় এবং আপনি মসৃণ ও দ্রুত প্ল্যাটফর্ম ব্যবহার করতে পারেন।
                </p>
            </div>

            <div class="section-box">
                <h2 class="section-heading">২. আমরা কেন কুকিজ ব্যবহার করি? (Why We Use Cookies)</h2>
                <p class="section-subtext">
                    SocialHub প্ল্যাটফর্মে প্রধানত নিম্নোক্ত উদ্দেশ্যে কুকিজ ও লোকাল স্টোরেজ ব্যবহার করা হয়ে থাকে:
                </p>
                <ul class="bullet-list">
                    <li><strong>অথেনটিকেশন ও নিরাপত্তা (Authentication & Security):</strong> আপনি লগইন অবস্থায় থাকলে আপনার অ্যাকাউন্ট সুরক্ষিত রাখা এবং অননুমোদিত অ্যাক্সেস রোধ করা।</li>
                    <li><strong>ব্যবহারকারীর পছন্দ (User Preferences):</strong> ভাষা, ডার্ক মোড এবং ইন্টারফেস সেটিংস মনে রাখা।</li>
                    <li><strong>সেশন ও সিঙ্ক (Session & Realtime Sync):</strong> রিয়েল-টাইম মেসেজিং ও নোটিফিকেশন সকেট কানেকশন সচল রাখা।</li>
                </ul>
            </div>

            <div class="section-box">
                <h2 class="section-heading">৩. আমাদের ব্যবহৃত কুকিজের প্রকারভেদ</h2>
                <table class="cookie-table">
                    <thead>
                        <tr>
                            <th>কুকিজের ক্যাটাগরি</th>
                            <th>উদ্দেশ্য</th>
                            <th>স্থায়িত্বকাল</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>bondhoo_session</strong></td>
                            <td>লগইন সেশন ও ইউজার অথেনটিকেশন স্টেট নিশ্চিত করা।</td>
                            <td>সেশন বা ২ ঘণ্টা নিষ্ক্রিয়তা</td>
                        </tr>
                        <tr>
                            <td><strong>XSRF-TOKEN</strong></td>
                            <td>ক্রস-সাইট রিকোয়েস্ট ফোরজারি (CSRF) আক্রমণ থেকে সুরক্ষা প্রদান।</td>
                            <td>সেশন</td>
                        </tr>
                        <tr>
                            <td><strong>remember_web_*</strong></td>
                            <td>'মনে রাখুন (Remember Me)' নির্বাচন করা থাকলে স্বয়ংক্রিয় লগইন সক্ষম রাখা।</td>
                            <td>৫ বছর বা লগআউট পর্যন্ত</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-box">
                <h2 class="section-heading">৪. কুকিজ কিভাবে নিয়ন্ত্রণ করবেন? (How to Control Cookies)</h2>
                <p class="section-subtext">
                    আপনি আপনার ব্রাউজার সেটিংসে গিয়ে যেকোনো সময় কুকিজ মুছে ফেলতে বা ব্লক করতে পারেন। তবে অনুগ্রহ করে মনে রাখবেন যে, প্রয়োজনীয় অথেনটিকেশন কুকিজ নিষ্ক্রিয় করলে আপনি প্ল্যাটফর্মে লগইন করতে বা সেবা পেতে সমস্যার সম্মুখীন হতে পারেন।
                </p>
            </div>

            <div class="section-box">
                <h2 class="section-heading">৫. আমাদের সাথে যোগাযোগ (Contact Us)</h2>
                <p class="section-subtext">
                    কুকিজ নীতিমালা সম্পর্কে কোনো প্রশ্ন থাকলে আমাদের গোপনীয়তা সহায়তায় ইমেইল করুন: <a href="mailto:privacy@jugajug.com" style="color: var(--fb-primary);">privacy@jugajug.com</a>
                </p>
            </div>

            <div class="footer-note">
                SocialHub Social Platform © {{ date('Y') }} · সমস্ত স্বত্ব সংরক্ষিত।
            </div>
        </article>
    </main>

</body>
</html>
