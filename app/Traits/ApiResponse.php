<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Send a standardized success JSON response.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => (object) $meta,
        ];

        return new JsonResponse($payload, $statusCode);
    }

    /**
     * Send a standardized error JSON response.
     */
    protected function errorResponse(
        string $message = 'An error occurred',
        int $statusCode = 400,
        mixed $errors = null,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'meta' => (object) $meta,
        ];

        return new JsonResponse($payload, $statusCode);
    }
}
