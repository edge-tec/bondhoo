<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RateLimitingTest — রেট লিমিটার কার্যকারিতা টেস্ট
 *
 * এই টেস্টটি প্রমাণ করে যে অথেনটিকেশন ও পোস্ট তৈরিতে নির্দিষ্ট সীমার বেশি
 * রিকোয়েস্ট পাঠালে সিস্টেম ৪২৯ (Too Many Requests) কোড প্রদান করে।
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * লগইন এন্ডপয়েন্টে ব্রুট-ফোর্স রেট লিমিটিং যাচাই (সর্বোচ্চ ৫ টি চেষ্টা)।
     */
    public function test_auth_rate_limiting_blocks_after_threshold_exceeded(): void
    {
        $payload = [
            'email' => 'attacker@jugajug.com',
            'password' => 'wrongpassword',
        ];

        // প্রথম ৫ টি রিকোয়েস্ট গ্রহণযোগ্য (যদিও ক্রিডেনশিয়াল ভুল থাকায় ৪২২ বা ৪০১ আসবে)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', $payload);
            $this->assertNotEquals(429, $response->status());
        }

        // ৬ষ্ঠ রিকোয়েস্ট ৪২৯ রেট লিমিটেড হবে
        $response = $this->postJson('/api/v1/auth/login', $payload);
        $response->assertStatus(429);
        $response->assertJson([
            'success' => false,
            'message' => 'খুব বেশি লগইন চেষ্টা করা হয়েছে। অনুগ্রহ করে কিছুক্ষণ পর চেষ্টা করুন।',
        ]);
    }

    /**
     * পোস্ট তৈরির রেট লিমিটিং যাচাই (প্রতি মিনিটে সর্বোচ্চ ১৫ টি পোস্ট)।
     */
    public function test_posts_rate_limiting_blocks_after_threshold_exceeded(): void
    {
        $user = User::factory()->create();

        // ১৫ টি পোস্টের রিকোয়েস্ট পাস হবে
        for ($i = 0; $i < 15; $i++) {
            $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/posts', [
                'content' => "Post number {$i}",
                'visibility' => 'public',
            ]);
            $this->assertNotEquals(429, $response->status());
        }

        // ১৬তম রিকোয়েস্ট রেট লিমিট খাবে
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Excess post',
            'visibility' => 'public',
        ]);

        $response->assertStatus(429);
    }
}
