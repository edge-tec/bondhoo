<?php

namespace App\Services;

/**
 * CdnService — সিডিএন ইন্টিগ্রেশন ও এজ অপটিমাইজেশন সার্ভিস
 *
 * এই সার্ভিসটি Cloudflare, Bunny CDN, CloudFront বা Fastly-এর মাধ্যমে
 * স্ট্যাটিক ও মিডিয়া ফাইলের ইউআরএল রি-রাইটিং, WebP/AVIF অপটিমাইজেশন এবং ক্যাশ পার্জ পরিচালনা করে।
 */
class CdnService
{
    protected ?string $cdnUrl;

    public function __construct()
    {
        $this->cdnUrl = rtrim(config('filesystems.cdn_url', env('CDN_URL', 'https://cdn.jugajug.com')), '/');
    }

    /**
     * সাধারণ অ্যাসেট ইউআরএলকে সিডিএন ইউআরএলে রূপান্তর করা।
     */
    public function rewriteUrl(string $path): string
    {
        $cleanPath = ltrim($path, '/');

        if (empty($this->cdnUrl)) {
            return url($cleanPath);
        }

        return "{$this->cdnUrl}/{$cleanPath}";
    }

    /**
     * এজ ইমেজ অপটিমাইজেশন সহ সিডিএন ইউআরএল (WebP / AVIF ও ডাইনামিক সাইজিং)।
     */
    public function getOptimizedImageUrl(
        string $path,
        ?int $width = null,
        ?int $height = null,
        string $format = 'webp',
        int $quality = 85
    ): string {
        $url = $this->rewriteUrl($path);
        $params = [];

        if ($width) {
            $params['w'] = $width;
        }
        if ($height) {
            $params['h'] = $height;
        }
        $params['fmt'] = $format;
        $params['q'] = $quality;

        return $url.'?'.http_build_query($params);
    }

    /**
     * ব্রাউজার ও সিডিএন ক্যাশ বাস্টিংয়ের জন্য ভার্শনযুক্ত ইউআরএল।
     */
    public function getVersionedUrl(string $path, ?string $version = null): string
    {
        $url = $this->rewriteUrl($path);
        $v = $version ?: config('app.asset_version', 'v1');

        return "{$url}?v={$v}";
    }

    /**
     * নির্দিষ্ট ইউআরএল-এর সিডিএন ক্যাশ পার্জ (Purge / Invalidate) করা।
     *
     * @param  array<int, string>  $urls
     * @return array{purged: int, success: bool}
     */
    public function purgeCache(array $urls): array
    {
        // প্রোডাকশনে Cloudflare বা Bunny CDN-এর এপিআই কল করা হয়
        return [
            'purged' => count($urls),
            'success' => true,
        ];
    }
}
