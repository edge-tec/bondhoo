<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI Content Moderation Configuration - Phase 16.9
    |--------------------------------------------------------------------------
    */
    'enabled' => env('OPENAI_MODERATION_ENABLED', true),
    'api_key' => env('OPENAI_API_KEY', ''),
    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    'model' => env('OPENAI_MODERATION_MODEL', 'omni-moderation-latest'),
    'timeout' => (int) env('OPENAI_MODERATION_TIMEOUT', 10),
    'retry_count' => (int) env('OPENAI_MODERATION_RETRY_COUNT', 3),
    'retry_sleep_ms' => (int) env('OPENAI_MODERATION_RETRY_SLEEP_MS', 500),

    /*
    | Adult Content Policy:
    | 'manual_review' => hold for moderator/admin review
    | 'reject'        => automatically reject content
    | 'approve'       => allow content with warning tag
    */
    'adult_content_policy' => env('OPENAI_ADULT_CONTENT_POLICY', 'manual_review'),

    /*
    | Fail Closed Policy:
    | When true, if OpenAI API fails or is unreachable, the content is held for
    | manual review rather than silently published.
    */
    'fail_closed' => env('OPENAI_FAIL_CLOSED', true),

    /*
    | Server-side Link Scanning with SSRF Protection
    */
    'link_scan_enabled' => env('OPENAI_LINK_SCAN_ENABLED', true),
    'link_scan_timeout' => (int) env('OPENAI_LINK_SCAN_TIMEOUT', 5),
    'link_max_redirects' => 3,
    'link_max_bytes' => 1048576, // 1MB

    /*
    | Default Category Confidence Thresholds (0.0 to 1.0)
    */
    'thresholds' => [
        'sexual' => 0.70,
        'hate' => 0.60,
        'harassment' => 0.60,
        'self_harm' => 0.50,
        'sexual_minors' => 0.05, // Ultra-sensitive zero-tolerance threshold
        'violence' => 0.70,
        'illicit' => 0.70,
    ],
];

