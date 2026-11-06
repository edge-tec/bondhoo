<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>অ্যাকাউন্ট ও ডেটা ডিলিট পলিসি (Account Deletion) — BONDHOO (বন্ধু)</title>
    <link rel="icon" type="image/png" href="/images/bondhoo-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8fafc;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --danger: #dc2626;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Hind Siliguri', 'Inter', system-ui, sans-serif; }
        body { background: var(--bg-color); color: var(--text-primary); line-height: 1.65; }

        .header-bar {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .brand-link {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .brand-logo-img { width: 36px; height: 36px; object-fit: contain; }
        .brand-title { font-size: 20px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; }

        .container { max-width: 860px; margin: 32px auto; padding: 0 20px 60px; }
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        h1 { font-size: 26px; font-weight: 800; margin-bottom: 12px; color: var(--text-primary); }
        h2 { font-size: 20px; font-weight: 700; margin: 24px 0 12px; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 8px; }
        p { margin-bottom: 14px; color: var(--text-secondary); font-size: 15px; }
        ul, ol { margin-left: 24px; margin-bottom: 16px; color: var(--text-secondary); font-size: 15px; }
        li { margin-bottom: 8px; }

        .badge-danger {
            display: inline-block;
            background: #fee2e2;
            color: var(--danger);
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 9999px;
            margin-bottom: 16px;
        }

        .step-box {
            background: #f1f5f9;
            border-left: 4px solid var(--primary);
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
        }

        .form-group { margin-bottom: 18px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        input[type="email"], textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        input[type="email"]:focus, textarea:focus { border-color: var(--primary); }

        .btn-submit {
            background: var(--danger);
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-submit:hover { opacity: 0.9; }

        .footer { text-align: center; color: #94a3b8; font-size: 13px; margin-top: 40px; }
    </style>
</head>
<body>

    <header class="header-bar">
        <a href="/" class="brand-link">
            <img src="/images/bondhoo-icon.png" alt="BONDHOO" class="brand-logo-img">
            <span class="brand-title">BONDHOO (বন্ধু)</span>
        </a>
        <a href="/login" style="text-decoration: none; font-size: 14px; font-weight: 600; color: var(--primary);">লগইন</a>
    </header>

    <main class="container">
        <div class="card">
            <span class="badge-danger">Google Play & User Privacy Compliance</span>
            <h1>অ্যাকাউন্ট ও সম্পর্কিত ডেটা ডিলিট করার নির্দেশিকা</h1>
            <p><strong>BONDHOO (বন্ধু)</strong> প্ল্যাটফর্মে আমরা ব্যবহারকারীদের ব্যক্তিগত গোপনীয়তা ও ডেটা সুরক্ষাকে সর্বোচ্চ অগ্রাধিকার দিই। আপনি চাইলে যেকোনো সময় আপনার অ্যাকাউন্ট ও সম্পর্কিত সমস্ত ব্যক্তিগত তথ্য স্থায়ীভাবে মুছে ফেলতে পারেন।</p>

            <h2>১. মোবাইল অ্যাপের মাধ্যমে অ্যাকাউন্ট ডিলিট করার পদ্ধতি</h2>
            <div class="step-box">
                <ol>
                    <li>BONDHOO অ্যান্ড্রয়েড অ্যাপে প্রবেশ করুন।</li>
                    <li>নিচের নেভিগেশন বার থেকে <strong>প্রোফাইল (Profile)</strong> আইকনে ট্যাপ করুন।</li>
                    <li>উপরে ডানপাশের <strong>সেটিংস (Settings)</strong> আইকনে যান।</li>
                    <li><strong>অ্যাকাউন্ট ও নিরাপত্তা (Account & Security)</strong> অপশনে ট্যাপ করুন।</li>
                    <li>পৃষ্ঠার একদম নিচে <strong>Delete Account (অ্যাকাউন্ট মুছুন)</strong> বাটনে ট্যাপ করুন।</li>
                    <li>নিরাপত্তা যাচাইয়ের জন্য আপনার অ্যাকাউন্টের পাসওয়ার্ড লিখুন এবং নিশ্চিতকরণ বক্সে <code>DELETE</code> শব্দটি টাইপ করে সাবমিট করুন।</li>
                </ol>
            </div>

            <h2>২. ওয়েবসাইটের মাধ্যমে অ্যাকাউন্ট ডিলিট করার পদ্ধতি</h2>
            <p>আপনার ব্রাউজারে লগইন থাকা অবস্থায় সরাসরি <a href="/settings/account" style="color: var(--primary); font-weight: 600;">সেটিংস &gt; অ্যাকাউন্ট ম্যানেজমেন্ট</a> পেজে যান এবং "Delete Account" বাটনে ক্লিক করে পাসওয়ার্ড ভেরিফিকেশন সম্পন্ন করুন।</p>

            <h2>৩. কোন ধরনের ডেটা মুছে ফেলা হয়?</h2>
            <ul>
                <li><strong>প্রোফাইল তথ্য:</strong> আপনার নাম, ইউজারনেম, ইমেইল, ফোন নম্বর, জন্মতারিখ, বায়ো এবং ঠিকানা।</li>
                <li><strong>মিডিয়া ও কনটেন্ট:</strong> আপনার আপলোডকৃত ছবি, কভার ফটো, ভিডিও, পোস্ট ও স্টোরিজ।</li>
                <li><strong>যোগাযোগ ও মেসেজ:</strong> মেসেঞ্জারের ব্যক্তিগত মেসেজ এবং চ্যাট হিস্ট্রি।</li>
                <li><strong>সামাজিক সংযোগ:</strong> ফলোয়ার, ফ্রেন্ডলিস্ট, গ্রুপ মেম্বারশিপ ও বিজনেস পেজ রোলস।</li>
            </ul>

            <h2>৪. গ্রেস পিরিয়ড ও স্থায়ী ডেটা পার্জিং (Grace Period & Data Retention)</h2>
            <p>অ্যাকাউন্ট ডিলিট রিকোয়েস্ট সাবমিট করার সাথে সাথে আপনার সেশন ও অথেনটিকেশন টোকেন বাতিল হয়ে যায় এবং পাবলিক প্রোফাইল অবিলম্বে হাইড হয়ে যায়। আইনি বাধ্যবাধকতা ও ভুলবশত ডিলিট প্রতিরোধে ৩০ (ত্রিশ) দিনের একটি গ্রেস পিরিয়ড কার্যকর থাকে। ৩০ দিন অতিক্রান্ত হওয়ার পর ব্যাকএন্ড শিডিউলার স্থায়ীভাবে আপনার ডেটা সম্পূর্ণ মুছে দেয়।</p>

            <h2>৫. অ্যাপ আনইন্সটল করা থাকলে অনলাইন রিকোয়েস্ট ফর্ম</h2>
            <p>আপনি যদি ইতিমধ্যে মোবাইল অ্যাপ আনইন্সটল করে থাকেন বা লগইন অ্যাক্সেস হারিয়ে ফেলেন, তবে নিচের ফর্মটিতে আপনার নিবন্ধিত ইমেইল ঠিকানা দিয়ে ডিলিট রিকোয়েস্ট পাঠাতে পারেন। আমাদের সিস্টেম আপনার ইমেইলে ভেরিফিকেশন লিংক পাঠাবে:</p>

            @if(session('success'))
                <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 14px; border-radius: 8px; margin-bottom: 16px; font-weight: 600;">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('account.delete.request') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email">আপনার নিবন্ধিত ইমেইল ঠিকানা (Registered Email) *</label>
                    <input type="email" id="email" name="email" required placeholder="name@example.com" value="{{ old('email') }}">
                    @error('email')
                        <p style="color: var(--danger); font-size: 13px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="reason">ডিলিট করার কারণ (ঐচ্ছিক)</label>
                    <textarea id="reason" name="reason" rows="3" placeholder="আপনার মতামত আমাদের সেবা উন্নত করতে সহায়তা করে..."></textarea>
                </div>
                <button type="submit" class="btn-submit">অ্যাকাউন্ট ডিলিট রিকোয়েস্ট পাঠান</button>
            </form>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} BONDHOO (বন্ধু) / JUGAJUG. সর্বস্বত্ব সংরক্ষিত।</p>
            <p><a href="/privacy" style="color: inherit;">Privacy Policy</a> • <a href="/terms" style="color: inherit;">Terms of Service</a> • <a href="/help" style="color: inherit;">Support</a></p>
        </div>
    </main>

</body>
</html>

