<?php

namespace Tests\Unit;

use App\Services\RedisCacheService;
use Tests\TestCase;

class RedisCacheServiceTest extends TestCase
{
    protected RedisCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new RedisCacheService;
    }

    public function test_cache_set_and_get_with_graceful_fallback(): void
    {
        $this->cacheService->set('test_key', 'jugajug_val', 60);

        $this->assertEquals('jugajug_val', $this->cacheService->get('test_key'));
        $this->assertTrue($this->cacheService->has('test_key'));

        $this->cacheService->forget('test_key');
        $this->assertFalse($this->cacheService->has('test_key'));
    }

    public function test_user_presence_lifecycle(): void
    {
        $userId = 101;

        $this->cacheService->setUserOnline($userId, 60);
        $this->assertTrue($this->cacheService->isUserOnline($userId));
        $this->assertNotNull($this->cacheService->getUserLastSeen($userId));

        $this->cacheService->setUserOffline($userId);
        $this->assertFalse($this->cacheService->isUserOnline($userId));
    }

    public function test_conversation_typing_indicator(): void
    {
        $convId = 5;
        $userId = 101;

        $this->cacheService->setTyping($convId, $userId, 5);
        $this->assertTrue($this->cacheService->isTyping($convId, $userId));
    }
}
