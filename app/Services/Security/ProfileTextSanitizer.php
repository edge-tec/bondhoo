<?php

namespace App\Services\Security;

class ProfileTextSanitizer
{
    /**
     * Allowed HTML tags for rich About text.
     *
     * @var array<int, string>
     */
    protected const ALLOWED_TAGS = [
        'b', 'strong', 'i', 'em', 'u', 'p', 'br',
        'ul', 'ol', 'li', 'blockquote', 'a', 'span',
    ];

    /**
     * Dangerous tags whose entire contents must be stripped.
     *
     * @var array<int, string>
     */
    protected const STRIP_TAGS_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed',
        'svg', 'math', 'applet', 'form', 'textarea',
    ];

    /**
     * Sanitize plain text (Bio and Headline/Introduction).
     * Strictly strips all HTML, scripts, and dangerous characters.
     */
    public static function sanitizePlainText(?string $text, bool $allowNewlines = true): ?string
    {
        if ($text === null) {
            return null;
        }

        $clean = trim($text);
        if ($clean === '') {
            return null;
        }

        // 1. Remove dangerous blocks and their contents completely
        foreach (self::STRIP_TAGS_WITH_CONTENT as $tag) {
            $clean = preg_replace('/<'.$tag.'\b[^>]*>.*?<\/'.$tag.'>/is', '', (string) $clean);
            $clean = preg_replace('/<'.$tag.'\b[^>]*\/?>(?!<\/'.$tag.'>)/is', '', (string) $clean);
        }

        // 2. Strip all remaining HTML tags
        $clean = strip_tags($clean);

        // 3. Remove dangerous javascript: / data: patterns if left plain
        $clean = preg_replace('/javascript\s*:/i', '', $clean);
        $clean = preg_replace('/data\s*:\s*text\/html/i', '', $clean);

        // 4. Handle whitespace & newlines
        if (! $allowNewlines) {
            $clean = preg_replace('/[\r\n\t]+/', ' ', $clean);
            $clean = preg_replace('/\s{2,}/', ' ', $clean);
        } else {
            // Normalize CRLF to LF and limit consecutive blank lines to at most 2
            $clean = str_replace(["\r\n", "\r"], "\n", $clean);
            $clean = preg_replace("/\n{3,}/", "\n\n", $clean);
        }

        $clean = trim($clean);

        return $clean === '' ? null : $clean;
    }

    /**
     * Sanitize rich About text supporting safe text formatting.
     * Allows safe formatting tags while preventing XSS, JS injection, and malicious URLs.
     */
    public static function sanitizeRichAbout(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $clean = trim($html);
        if ($clean === '') {
            return null;
        }

        // 1. Strip dangerous tags and their contents completely
        foreach (self::STRIP_TAGS_WITH_CONTENT as $tag) {
            $clean = preg_replace('/<'.$tag.'\b[^>]*>.*?<\/'.$tag.'>/is', '', (string) $clean);
            $clean = preg_replace('/<'.$tag.'\b[^>]*\/?>(?!<\/'.$tag.'>)/is', '', (string) $clean);
        }

        // 2. Strip disallowed HTML tags
        $clean = strip_tags($clean, self::ALLOWED_TAGS);

        // 3. Remove inline event handlers (onload, onerror, onclick, onmouseover, etc.)
        $clean = preg_replace('/\s+on[a-z]+\s*=\s*(["\'][^"\']*["\']|[^\s>]+)/is', '', (string) $clean);

        // 4. Remove style attributes to prevent CSS-based expressions/exfiltration
        $clean = preg_replace('/\s+style\s*=\s*(["\'][^"\']*["\']|[^\s>]+)/is', '', (string) $clean);

        // 5. Sanitize <a> links: validate href protocol and add secure attributes
        $clean = preg_replace_callback('/<a\b([^>]*)>/is', function (array $matches) {
            $attributes = $matches[1];

            // Extract href
            $href = '';
            if (preg_match('/href\s*=\s*(["\'])(.*?)\1/is', $attributes, $hrefMatches)) {
                $href = trim($hrefMatches[2]);
            } elseif (preg_match('/href\s*=\s*([^\s>]+)/is', $attributes, $hrefMatches)) {
                $href = trim($hrefMatches[1]);
            }

            // Decode entities to catch disguised protocols: java&#115;cript:
            $decodedHref = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $decodedHref = preg_replace('/\s+/', '', strtolower($decodedHref));

            $isSafe = false;
            if (
                str_starts_with($decodedHref, 'http://') ||
                str_starts_with($decodedHref, 'https://') ||
                str_starts_with($decodedHref, 'mailto:') ||
                str_starts_with($decodedHref, '/') ||
                str_starts_with($decodedHref, '#')
            ) {
                // Ensure no hidden javascript: within query or fragments
                if (! str_contains($decodedHref, 'javascript:') && ! str_contains($decodedHref, 'data:')) {
                    $isSafe = true;
                }
            }

            if (! $isSafe) {
                $safeHref = '#';
            } else {
                $safeHref = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
            }

            return '<a href="'.$safeHref.'" target="_blank" rel="noopener noreferrer nofollow">';
        }, (string) $clean);

        // 6. Clean dangling closing tags or corrupted tokens
        $clean = preg_replace('/<!--.*?-->/s', '', (string) $clean);
        $clean = trim((string) $clean);

        return $clean === '' ? null : $clean;
    }
}
