<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? 'Bondhoo বিজ্ঞপ্তি' }}</title>
    <style>
        /* Base Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; min-width: 100%; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1e293b; }
        
        /* Container */
        .email-wrapper { width: 100%; background-color: #f1f5f9; padding: 32px 12px; }
        .email-container { max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0; }
        
        /* Header */
        .email-header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #6366f1 100%); padding: 28px 24px; text-align: center; }
        .brand-title { color: #ffffff; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; margin: 0; text-decoration: none; }
        .brand-subtitle { color: #e0e7ff; font-size: 13px; font-weight: 500; margin-top: 4px; }
        
        /* Body */
        .email-body { padding: 32px 28px; }
        .email-greeting { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 16px; }
        .email-text { font-size: 15px; color: #334155; line-height: 1.65; margin-bottom: 20px; }
        
        /* Action Button */
        .btn-container { text-align: center; margin: 28px 0; }
        .btn-primary { display: inline-block; background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); color: #ffffff !important; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 10px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.28); }
        
        /* Highlight / OTP Box */
        .otp-box { background: #f8fafc; border: 2px dashed #94a3b8; border-radius: 12px; padding: 18px; text-align: center; margin: 24px 0; }
        .otp-code { font-family: monospace, Courier; font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #1e40af; }
        .otp-hint { font-size: 12px; color: #64748b; margin-top: 6px; }
        
        /* Security Notice */
        .security-box { background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 6px; padding: 14px 16px; margin: 24px 0; font-size: 13px; color: #1e3a8a; }
        .warning-box { background: #fff1f2; border-left: 4px solid #f43f5e; border-radius: 6px; padding: 14px 16px; margin: 24px 0; font-size: 13px; color: #9f1239; }
        
        /* Fallback URL */
        .fallback-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 12px; color: #64748b; word-break: break-all; margin-top: 20px; }
        
        /* Footer */
        .email-footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 24px; text-align: center; font-size: 12px; color: #64748b; line-height: 1.5; }
        .footer-links a { color: #2563eb; text-decoration: none; margin: 0 8px; }
        .footer-links a:hover { text-decoration: underline; }
        
        /* Mobile */
        @media only screen and (max-width: 600px) {
            .email-wrapper { padding: 12px 6px !important; }
            .email-body { padding: 24px 18px !important; }
            .btn-primary { width: 100% !important; box-sizing: border-box; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td align="center">
                    <div class="email-container">
                        <!-- Header -->
                        <div class="email-header">
                            <h1 class="brand-title">bondhoo</h1>
                            <div class="brand-subtitle">বন্ধু সোশ্যাল নেটওয়ার্ক — আধুনিক ও নিরাপদ যোগাযোগ</div>
                        </div>

                        <!-- Body Content Slot -->
                        <div class="email-body">
                            @yield('content')
                        </div>

                        <!-- Footer -->
                        <div class="email-footer">
                            <p style="margin: 0 0 10px 0;">
                                এই ইমেইলটি <strong>Bondhoo সোশ্যাল নেটওয়ার্ক</strong> থেকে আপনার অ্যাকাউন্টের সুরক্ষার জন্য স্বয়ংক্রিয়ভাবে পাঠানো হয়েছে।
                            </p>
                            <div class="footer-links" style="margin-bottom: 12px;">
                                <a href="{{ config('app.url') }}/settings/notifications">নোটিফিকেশন সেটিংস</a> &bull;
                                <a href="{{ config('app.url') }}/privacy">প্রাইভেসি পলিসি</a> &bull;
                                <a href="{{ config('app.url') }}/help">সাহায্য কেন্দ্র</a>
                            </div>
                            <p style="margin: 0; color: #94a3b8; font-size: 11px;">
                                &copy; {{ date('Y') }} Bondhoo Inc. সর্বস্বত্ব সংরক্ষিত। ঢাকা, বাংলাদেশ।
                            </p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
