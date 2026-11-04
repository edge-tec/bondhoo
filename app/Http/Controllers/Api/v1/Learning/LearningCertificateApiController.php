<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Learning;

use App\Http\Controllers\Controller;
use App\Models\LearningCertificate;
use App\Models\LearningCourse;
use App\Services\Learning\CertificateService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningCertificateApiController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService
    ) {}

    /**
     * POST /api/v1/learning/courses/{id}/certificate
     *
     * Claim certificate upon meeting course completion.
     */
    public function claim(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $course = LearningCourse::findOrFail($id);

        try {
            $certificate = $this->certificateService->issueCertificate($user, $course);

            return $this->successResponse($certificate, 'Certificate issued successfully.', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * GET /api/v1/learning/my-certificates
     */
    public function myCertificates(Request $request): JsonResponse
    {
        $user = $request->user();
        $certificates = LearningCertificate::with('course')
            ->where('user_id', $user->id)
            ->latest('issued_at')
            ->get();

        return $this->successResponse($certificates, 'Certificates retrieved successfully.');
    }

    /**
     * GET /api/v1/learning/certificates/{code}
     *
     * Public verification endpoint.
     */
    public function verify(string $code): JsonResponse
    {
        $result = $this->certificateService->verifyCertificate($code);

        if (! $result) {
            return $this->errorResponse('Certificate not found or invalid.', 404);
        }

        return $this->successResponse($result, 'Certificate verified successfully.');
    }

    /**
     * GET /api/v1/learning/certificates/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $certificate = LearningCertificate::with('course')->findOrFail($id);

        if ($certificate->user_id !== $user->id && ! $user->hasRole(['ADMIN', 'SUPER_ADMIN'])) {
            return $this->errorResponse('Unauthorized to view this certificate.', 403);
        }

        return $this->successResponse($certificate, 'Certificate retrieved successfully.');
    }

    /**
     * POST /api/v1/learning/instructor/certificates/{id}/revoke
     */
    public function revoke(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $certificate = LearningCertificate::with('course')->findOrFail($id);

        try {
            $revoked = $this->certificateService->revokeCertificate($user, $certificate, $request->input('reason'));

            return $this->successResponse($revoked, 'Certificate revoked successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }
}
