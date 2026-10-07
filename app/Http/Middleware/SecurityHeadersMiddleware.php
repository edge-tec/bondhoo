<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeadersMiddleware — নিরাপত্তা হেডার মিডলওয়্যার
 *
 * এই মিডলওয়্যারটি প্রতিটি HTTP রেসপন্সে গুরুত্বপূর্ণ সিকিউরিটি হেডারগুলো যুক্ত করে,
 * যা XSS, Clickjacking, MIME-sniffing ইত্যাদি সাধারণ ওয়েব আক্রমণ প্রতিহত করে।
 */
class SecurityHeadersMiddleware
{
    /**
     * ইনকামিং রিকোয়েস্ট প্রসেস করে রেসপন্সে সিকিউরিটি হেডার যুক্ত করা।
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ১. MIME-type sniffing বন্ধ করা (ব্রাউজার যেন ফাইলের কন্টেন্ট টাইপ পরিবর্তন করতে না পারে)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // ২. Clickjacking আক্রমণ প্রতিরোধ (অন্য কোনো সাইটের আইফ্রেমে আমাদের প্ল্যাটফর্ম লোড হতে দেবে না)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // ৩. পুরনো ব্রাউজারের XSS প্রোটেকশন সক্রিয় করা
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // ৪. রেফারার পলিসি নিয়ন্ত্রণ (অন্য লিংকে গেলে সংবেদনশীল তথ্য যাতে ইউআরএলে ফাঁস না হয়)
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // ৫. ডিভাইস পারমিশন নিয়ন্ত্রণ (সেলফ অরিজিনে কল এবং ভয়েস রেকর্ডিং এর জন্য ক্যামেরা ও মাইক্রোফোন অনুমোদিত)
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=()');

        // ৬. Content Security Policy (কন্টেন্ট সিকিউরিটি পলিসি)
        // অনিরাপদ বাহ্যিক স্ক্রিপ্ট লোড বন্ধ করে ও অডিও/ভিডিয়ো মিডিয়া সোর্স অনুমোদন করে
        $csp = "default-src 'self'; img-src 'self' data: https: blob:; media-src 'self' data: https: blob:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; connect-src 'self' ws: wss:;";
        $response->headers->set('Content-Security-Policy', $csp);

        // ৭. প্রোডাকশন অথবা HTTPS কানেকশনে HSTS এনফোর্স করা
        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // ৮. ক্যাশিং প্রতিরোধ (লগআউটের পর ব্যাক বা রিফ্রেশে যাতে পূর্ববর্তী প্রাইভেট ডাটা না থাকে)
        if (! $request->is('build/*', 'css/*', 'js/*', 'images/*', 'favicon.*', 'storage/*')) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }
}
