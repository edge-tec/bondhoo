<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkPreviewService
{
    /**
     * Safely fetch metadata for a URL with SSRF protection.
     */
    public function fetchMetadata(string $url): ?array
    {
        $url = trim($url);
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');
        $host = strtolower($parsed['host'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || empty($host)) {
            return null;
        }

        // SSRF protection: Resolve host IP and check against private/reserved ranges
        if ($this->isPrivateOrReservedHost($host)) {
            Log::warning("SSRF blocked attempt to fetch: {$url}");

            return null;
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'User-Agent' => 'JugajugBot/1.0 (+https://jugajug.com/bot)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $html = substr($response->body(), 0, 500000); // 500KB limit
            if (empty($html)) {
                return null;
            }

            return $this->parseHtmlMetadata($html, $url, $host);
        } catch (Exception $e) {
            Log::info("Failed to fetch link preview for {$url}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Check if a hostname resolves to a loopback, private, or cloud metadata IP.
     */
    protected function isPrivateOrReservedHost(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0', '169.254.169.254', 'metadata.google.internal'], true)) {
            return true;
        }

        $ips = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (empty($ips)) {
            $singleIp = @gethostbyname($host);
            if ($singleIp && $singleIp !== $host) {
                $ips = [['ip' => $singleIp]];
            } else {
                return false;
            }
        }

        foreach ($ips as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (! $ip) {
                continue;
            }

            // Reject private and reserved IP addresses
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }

            // Explicitly block AWS/GCP/Azure link-local metadata address 169.254.x.x
            if (str_starts_with($ip, '169.254.') || str_starts_with($ip, '127.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract title, description, image, and domain from HTML.
     */
    protected function parseHtmlMetadata(string $html, string $originalUrl, string $host): array
    {
        $title = null;
        $description = null;
        $image = null;

        // OpenGraph Title
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $title = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5);
        } elseif (preg_match('/<meta[^>]+name=["\']twitter:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $title = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5);
        } elseif (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            $title = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5);
        }

        // OpenGraph Description
        if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $description = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5);
        } elseif (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $description = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5);
        }

        // OpenGraph Image
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $image = trim($matches[1]);
        } elseif (preg_match('/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $image = trim($matches[1]);
        }

        // Resolve relative image URL
        if ($image && ! preg_match('/^https?:\/\//i', $image)) {
            $base = rtrim(parse_url($originalUrl, PHP_URL_SCHEME).'://'.$host, '/');
            $image = $base.'/'.ltrim($image, '/');
        }

        return [
            'url' => $originalUrl,
            'domain' => $host,
            'title' => $title ? mb_substr($title, 0, 200) : $host,
            'description' => $description ? mb_substr($description, 0, 350) : null,
            'image' => $image,
        ];
    }
}
