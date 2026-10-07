<?php

namespace Tests\Unit;

use App\Traits\ApiResponse;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    use ApiResponse;

    public function test_success_response_structure(): void
    {
        $response = $this->successResponse(['id' => 1, 'name' => 'Test'], 'Data loaded', 200, ['page' => 1]);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('Data loaded', $data['message']);
        $this->assertEquals(['id' => 1, 'name' => 'Test'], $data['data']);
        $this->assertEquals(['page' => 1], $data['meta']);
    }

    public function test_error_response_structure(): void
    {
        $response = $this->errorResponse('Validation failed', 422, ['field' => ['Invalid value']]);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertEquals(['field' => ['Invalid value']], $data['errors']);
    }
}
