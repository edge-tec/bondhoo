<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * SeoWebController — সার্চ ইঞ্জিন অপ্টিমাইজেশন (SEO), ডাইনামিক সাইটম্যাপ ও ক্যানোনিক্যাল মেটা কন্ট্রোলার
 */
class SeoWebController extends Controller
{
    /**
     * Generate dynamic XML Sitemap for public pages, profiles and posts.
     */
    public function sitemap(): Response
    {
        $xml = Cache::remember('jugajug_seo_sitemap_xml', 3600, function () {
            $baseUrl = rtrim((string) config('app.url', 'https://jugajug.com'), '/');

            $urls = [
                ['loc' => $baseUrl.'/', 'priority' => '1.0', 'changefreq' => 'hourly'],
                ['loc' => $baseUrl.'/terms', 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => $baseUrl.'/privacy', 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => $baseUrl.'/cookies', 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => $baseUrl.'/community-guidelines', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ];

            // Recent active public users
            try {
                $publicUsers = User::where('status', 'active')
                    ->whereNotNull('username')
                    ->select('username', 'updated_at')
                    ->latest('updated_at')
                    ->limit(200)
                    ->get();

                foreach ($publicUsers as $user) {
                    $urls[] = [
                        'loc' => $baseUrl.'/u/'.$user->username,
                        'priority' => '0.8',
                        'changefreq' => 'daily',
                        'lastmod' => $user->updated_at?->toAtomString(),
                    ];
                }
            } catch (\Throwable) {
                // Graceful fallback if database unavailable during pre-boot
            }

            // Recent public posts
            try {
                $publicPosts = Post::where(function ($query) {
                    $query->where('audience', 'public');
                })
                    ->select('id', 'updated_at')
                    ->latest('updated_at')
                    ->limit(500)
                    ->get();

                foreach ($publicPosts as $post) {
                    $urls[] = [
                        'loc' => $baseUrl.'/p/'.$post->id,
                        'priority' => '0.7',
                        'changefreq' => 'weekly',
                        'lastmod' => $post->updated_at?->toAtomString(),
                    ];
                }
            } catch (\Throwable) {
                // Graceful fallback
            }

            $xmlOutput = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
            $xmlOutput .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;

            foreach ($urls as $item) {
                $xmlOutput .= '  <url>'.PHP_EOL;
                $xmlOutput .= '    <loc>'.htmlspecialchars($item['loc'], ENT_XML1).'</loc>'.PHP_EOL;
                if (! empty($item['lastmod'])) {
                    $xmlOutput .= '    <lastmod>'.$item['lastmod'].'</lastmod>'.PHP_EOL;
                }
                $xmlOutput .= '    <changefreq>'.$item['changefreq'].'</changefreq>'.PHP_EOL;
                $xmlOutput .= '    <priority>'.$item['priority'].'</priority>'.PHP_EOL;
                $xmlOutput .= '  </url>'.PHP_EOL;
            }

            $xmlOutput .= '</urlset>';

            return $xmlOutput;
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex, follow',
        ]);
    }
}
