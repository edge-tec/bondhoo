<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * SecurityHeadersTest — সিকিউরিটি হেডার মিডলওয়্যার টেস্ট
 *
 * এই টেস্টটি নিশ্চিত করে যে প্ল্যাটফর্মের প্রতিটি রেসপন্সে OWASP নির্দেশিত
 * সিকিউরিটি হেডারসমূহ সঠিকভাবে যুক্ত হচ্ছে।
 */
class SecurityHeadersTest extends TestCase
{
    /**
     * সকল রেসপন্সে প্রয়োজনীয় সিকিউরিটি হেডার উপস্থিতি যাচাই।
     */
    public function test_api_responses_include_mandated_security_headers(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);

        // হেডারসমূহ যাচাই করা
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=()');
        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("media-src 'self'", $response->headers->get('Content-Security-Policy'));
    }
}
