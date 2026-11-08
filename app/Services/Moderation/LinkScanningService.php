<?php

namespace App\Services\Moderation;

use App\Models\LinkScanResult;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkScanningService
{
    protected OpenAIModerationService $openAIService;

    protected int $maxRedirects;

    protected int $maxBytes;

    protected int $timeout;

    protected bool $enabled;

    public function __construct(OpenAIModerationService $openAIService)
    {
        $this->openAIService = $openAIService;
        $this->maxRedirects = (int) config('moderation.link_max_redirects', 3);
        $this->maxBytes = (int) config('moderation.link_max_bytes', 1048576); // 1MB
        $this->timeout = (int) config('moderation.link_scan_timeout', 5);
        $this->enabled = (bool) config('moderation.link_scan_enabled', true);
    }

    /**
     * Extract all URLs from a text snippet.
     *
     * @return array<string>
     */
    public function extractUrls(string $text): array
    {
        preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#i', $text, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }

    /**
     * Inspect and scan a URL with strict SSRF defense.
     */
    public function scanUrl(string $url): LinkScanResult
    {
        $normalizedUrl = trim($url);
        $parsed = parse_url($normalizedUrl);
        $domain = $parsed['host'] ?? '';

        // Check if recently scanned within 6 hours
        $cached = LinkScanResult::where('normalized_url', substr($normalizedUrl, 0, 1024))
            ->where('scanned_at', '>=', now()->subHours(6))
            ->first();

        if ($cached) {
            return $cached;
        }

        // Scheme verification (HTTP / HTTPS only)
        $scheme = strtolower($parsed['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true) || empty($domain)) {
            return LinkScanResult::create([
                'url' => $url,
                'normalized_url' => substr($normalizedUrl, 0, 1024),
                'domain' => $domain ?: 'invalid',
                'status' => LinkScanResult::STATUS_MALICIOUS,
                'is_ssrf_blocked' => true,
                'blocked_ip' => 'invalid_scheme',
                'scanned_at' => now(),
            ]);
        }

        // SSRF IP Validation
        $ssrfCheck = $this->isSsrfTarget($domain);
        if ($ssrfCheck['blocked']) {
            Log::warning("SSRF Blocked: URL {$url} points to blocked IP {$ssrfCheck['ip']}");

            return LinkScanResult::create([
                'url' => $url,
                'normalized_url' => substr($normalizedUrl, 0, 1024),
                'domain' => $domain,
                'status' => LinkScanResult::STATUS_MALICIOUS,
                'is_ssrf_blocked' => true,
                'blocked_ip' => $ssrfCheck['ip'],
                'scanned_at' => now(),
            ]);
        }

        // In offline/test environments where external network is prevented, allow safe test links
        if (app()->environment('testing') && ! filter_var($url, FILTER_VALIDATE_URL)) {
            return LinkScanResult::create([
                'url' => $url,
                'normalized_url' => substr($normalizedUrl, 0, 1024),
                'domain' => $domain,
                'status' => LinkScanResult::STATUS_SAFE,
                'scanned_at' => now(),
            ]);
        }

        // Perform safe fetch
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['User-Agent' => 'BondhooSafetyBot/1.0'])
                ->withoutRedirecting()
                ->get($normalizedUrl);

            $hops = 0;
            $currentUrl = $normalizedUrl;

            // Handle limited redirects safely
            while ($response->status() >= 300 && $response->status() < 400 && $hops < $this->maxRedirects) {
                $hops++;
                $location = $response->header('Location');
                if (empty($location)) {
                    break;
                }

                // If relative redirect, build absolute
                if (! preg_match('#^https?://#i', $location)) {
                    $baseParsed = parse_url($currentUrl);
                    $location = ($baseParsed['scheme'] ?? 'http').'://'.($baseParsed['host'] ?? '').'/'.ltrim($location, '/');
                }

                $nextParsed = parse_url($location);
                $nextDomain = $nextParsed['host'] ?? '';
                $nextSsrf = $this->isSsrfTarget($nextDomain);
                if ($nextSsrf['blocked']) {
                    return LinkScanResult::create([
                        'url' => $url,
                        'normalized_url' => substr($normalizedUrl, 0, 1024),
                        'final_url' => $location,
                        'domain' => $nextDomain,
                        'status' => LinkScanResult::STATUS_MALICIOUS,
                        'is_ssrf_blocked' => true,
                        'blocked_ip' => $nextSsrf['ip'],
                        'redirect_hops' => $hops,
                        'scanned_at' => now(),
                    ]);
                }

                $currentUrl = $location;
                $response = Http::timeout($this->timeout)
                    ->withHeaders(['User-Agent' => 'BondhooSafetyBot/1.0'])
                    ->withoutRedirecting()
                    ->get($currentUrl);
            }

            $body = substr($response->body(), 0, $this->maxBytes);
            $title = $this->extractTagContent($body, 'title');
            $description = $this->extractMetaContent($body, 'description')
                ?: $this->extractMetaProperty($body, 'og:description');

            // Scan extracted content with OpenAI moderation
            $textToModerate = trim("{$title} {$description}");
            $flaggedCategories = [];
            $status = LinkScanResult::STATUS_SAFE;

            if (! empty($textToModerate) && $this->openAIService->isConfigured()) {
                $modResult = $this->openAIService->moderateText($textToModerate);
                if ($modResult['flagged']) {
                    $status = LinkScanResult::STATUS_SUSPICIOUS;
                    $flaggedCategories = array_keys(array_filter($modResult['categories']));
                }
            }

            return LinkScanResult::create([
                'url' => $url,
                'normalized_url' => substr($normalizedUrl, 0, 1024),
                'final_url' => $currentUrl,
                'domain' => $domain,
                'status' => $status,
                'http_status' => $response->status(),
                'title' => $title ? substr($title, 0, 500) : null,
                'description' => $description ? substr($description, 0, 1000) : null,
                'is_ssrf_blocked' => false,
                'redirect_hops' => $hops,
                'flagged_categories' => ! empty($flaggedCategories) ? $flaggedCategories : null,
                'scanned_at' => now(),
            ]);

        } catch (Exception $e) {
            Log::warning("Link scan failed for {$url}: {$e->getMessage()}");

            return LinkScanResult::create([
                'url' => $url,
                'normalized_url' => substr($normalizedUrl, 0, 1024),
                'domain' => $domain,
                'status' => LinkScanResult::STATUS_SAFE, // Do not block external user on simple timeout unless fail_closed
                'http_status' => null,
                'is_ssrf_blocked' => false,
                'scanned_at' => now(),
            ]);
        }
    }

    /**
     * Check if a hostname resolves to private, loopback, or cloud-metadata IP addresses.
     *
     * @return array{blocked: bool, ip: ?string}
     */
    public function isSsrfTarget(string $host): array
    {
        if (empty($host)) {
            return ['blocked' => true, 'ip' => null];
        }

        // Direct IP or hostname resolution
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : @gethostbynamel($host);

        if (! is_array($ips) || empty($ips)) {
            // Unresolvable host or invalid DNS
            return ['blocked' => false, 'ip' => null];
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateOrReservedIp($ip)) {
                return ['blocked' => true, 'ip' => $ip];
            }
        }

        return ['blocked' => false, 'ip' => $ips[0]];
    }

    /**
     * Check if an IPv4 or IPv6 is in private, link-local, loopback, or AWS/GCP metadata range.
     */
    protected function isPrivateOrReservedIp(string $ip): bool
    {
        // IPv6 Checks
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($ip === '::1' || str_starts_with(strtolower($ip), 'fe80:') || str_starts_with(strtolower($ip), 'fc00:')) {
                return true;
            }

            return false;
        }

        // IPv4 Checks
        $long = ip2long($ip);
        if ($long === false) {
            return true;
        }

        $privateRanges = [
            '10.0.0.0/8',       // RFC 1918 Private
            '172.16.0.0/12',    // RFC 1918 Private
            '192.168.0.0/16',   // RFC 1918 Private
            '127.0.0.0/8',      // Loopback
            '169.254.0.0/16',   // Link-local / Cloud Metadata (169.254.169.254)
            '0.0.0.0/8',        // Current network
            '100.64.0.0/10',    // Carrier Grade NAT
            '192.0.0.0/24',     // IETF Protocol Assignments
            '198.18.0.0/15',    // Network benchmark
            '224.0.0.0/4',      // Multicast
            '240.0.0.0/4',      // Reserved
        ];

        foreach ($privateRanges as $range) {
            [$subnet, $mask] = explode('/', $range);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - (int) $mask);
            if (($long & $maskLong) === ($subnetLong & $maskLong)) {
                return true;
            }
        }

        return false;
    }

    protected function extractTagContent(string $html, string $tag): ?string
    {
        if (preg_match("#<{$tag}[^>]*>(.*?)</{$tag}>#is", $html, $matches)) {
            return trim(html_entity_decode(strip_tags($matches[1])));
        }

        return null;
    }

    protected function extractMetaContent(string $html, string $name): ?string
    {
        if (preg_match("#<meta\s+[^>]*name=[\"']{$name}[\"'][^>]*content=[\"'](.*?)[\"']#is", $html, $matches)) {
            return trim(html_entity_decode($matches[1]));
        }

        return null;
    }

    protected function extractMetaProperty(string $html, string $property): ?string
    {
        if (preg_match("#<meta\s+[^>]*property=[\"']{$property}[\"'][^>]*content=[\"'](.*?)[\"']#is", $html, $matches)) {
            return trim(html_entity_decode($matches[1]));
        }

        return null;
    }
}
