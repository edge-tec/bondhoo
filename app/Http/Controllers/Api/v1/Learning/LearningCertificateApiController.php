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
use Illuminate\Http\Response;

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
     * GET /api/v1/learning/certificates/{code}/download
     *
     * Printable/downloadable verified certificate document.
     */
    public function download(string $code): Response|JsonResponse
    {
        $result = $this->certificateService->verifyCertificate($code);

        if (! $result || ! $result['is_valid']) {
            return $this->errorResponse('Certificate not found or has been revoked.', 404);
        }

        $student = htmlspecialchars((string) $result['student_name'], ENT_QUOTES, 'UTF-8');
        $courseTitle = htmlspecialchars((string) $result['course_title'], ENT_QUOTES, 'UTF-8');
        $instructor = htmlspecialchars((string) $result['instructor_name'], ENT_QUOTES, 'UTF-8');
        $certCode = htmlspecialchars((string) $result['certificate_code'], ENT_QUOTES, 'UTF-8');
        $issuedDate = htmlspecialchars(substr((string) $result['issued_at'], 0, 10), ENT_QUOTES, 'UTF-8');
        $verifyUrl = htmlspecialchars((string) $result['verification_url'], ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bondoo Certificate - {$certCode}</title>
    <style>
        body { font-family: Georgia, serif; background: #f8fafc; padding: 40px; color: #0f172a; }
        .cert { max-width: 820px; margin: 0 auto; background: #fff; border: 8px double #1e3a8a; padding: 56px; text-align: center; box-shadow: 0 20px 50px rgba(15,23,42,0.1); }
        .brand { font-family: system-ui, sans-serif; letter-spacing: 0.2em; color: #2563eb; font-weight: 800; font-size: 14px; text-transform: uppercase; }
        h1 { font-size: 38px; margin: 16px 0 8px; color: #1e293b; }
        .sub { color: #64748b; font-size: 16px; }
        .student { font-size: 34px; font-weight: bold; color: #1d4ed8; margin: 28px 0 12px; border-bottom: 2px solid #e2e8f0; display: inline-block; padding: 0 24px 8px; }
        .course { font-size: 24px; font-weight: bold; margin: 16px 0 36px; color: #0f172a; }
        .meta { display: flex; justify-content: space-between; margin-top: 40px; font-family: system-ui, sans-serif; font-size: 13px; color: #475569; border-top: 1px solid #e2e8f0; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="cert">
        <div class="brand">Bondoo Learning &amp; Development Academy</div>
        <h1>Certificate of Completion</h1>
        <p class="sub">This certifies that</p>
        <div class="student">{$student}</div>
        <p class="sub">has successfully completed all curriculum requirements for</p>
        <div class="course">{$courseTitle}</div>
        <div class="meta">
            <div><strong>Instructor:</strong> {$instructor}</div>
            <div><strong>Issued:</strong> {$issuedDate}</div>
            <div><strong>Credential ID:</strong> {$certCode}</div>
        </div>
        <p style="margin-top:16px;font-family:system-ui,sans-serif;font-size:12px;color:#64748b;">Verify authenticity at: {$verifyUrl}</p>
    </div>
</body>
</html>
HTML;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$certCode.'.html"',
        ]);
    }

    /**
     * GET /api/v1/learning/certificates/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $certificate = LearningCertificate::with('course')->findOrFail($id);

        if ((int) $certificate->user_id !== (int) $user->id && ! $user->isAdmin()) {
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
