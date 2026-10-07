<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->successResponse(
            data: [
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            message: 'Registration successful.',
            statusCode: 201
        );
    }
}
