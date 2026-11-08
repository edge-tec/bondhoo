<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\CandidateCertification;
use App\Models\LearningCertificate;
use App\Models\LearningCourse;
use App\Models\LearningEnrollment;
use App\Models\LearningLessonProgress;
use App\Models\User;
use App\Services\Contracts\NotificationServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Issue a course completion certificate for a student.
     */
    public function issueCertificate(User $user, LearningCourse $course): LearningCertificate
    {
        return DB::transaction(function () use ($user, $course) {
            // Server-authoritative eligibility verification under row lock
            $enrollment = LearningEnrollment::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->whereIn('status', [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED])
                ->lockForUpdate()
                ->first();

            if (! $enrollment) {
                throw new \Exception('Student is not enrolled in this course.');
            }

            $existing = LearningCertificate::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $publishedLessonIds = $course->lessons()
                ->where('is_published', true)
                ->pluck('id')
                ->all();

            $totalPublished = count($publishedLessonIds);
            if ($totalPublished === 0) {
                throw new \Exception('Course requirements not satisfied: Course has no published lessons.');
            }

            $completedCount = LearningLessonProgress::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->whereIn('lesson_id', $publishedLessonIds)
                ->where('is_completed', true)
                ->count();

            if ($completedCount < $totalPublished) {
                throw new \Exception('Course requirements not satisfied: All lessons must be completed.');
            }

            $year = date('Y');
            $code = 'CERT-'.$year.'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
            $token = Str::random(64);

            $instructorName = $course->instructor?->name ?? 'Bondoo LMS Academy';
            $studentName = $user->name ?: trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

            $certificate = LearningCertificate::create([
                'course_id' => $course->id,
                'user_id' => $user->id,
                'certificate_code' => $code,
                'verification_token' => $token,
                'student_name' => $studentName ?: 'Bondoo Learner',
                'course_title' => $course->title,
                'instructor_name' => $instructorName,
                'pdf_url' => url("/api/v1/learning/certificates/{$code}/download"),
                'qr_code_url' => url("/verify/cert/{$code}"),
                'issued_at' => now(),
                'is_revoked' => false,
            ]);

            if ($enrollment->status !== LearningEnrollment::STATUS_COMPLETED) {
                $enrollment->update([
                    'status' => LearningEnrollment::STATUS_COMPLETED,
                    'completed_at' => $enrollment->completed_at ?? now(),
                ]);
            }

            // Sync with Phase 12 CandidateCertification for professional resume display
            CandidateCertification::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'credential_id' => $code,
                ],
                [
                    'title' => $course->title.' (Professional Certification)',
                    'issuing_organization' => 'Connectly LMS Academy',
                    'issue_date' => now()->toDateString(),
                    'credential_url' => url("/api/v1/learning/certificates/{$code}"),
                ]
            );

            DB::afterCommit(function () use ($user, $course, $certificate): void {
                try {
                    app(NotificationServiceInterface::class)->send(
                        recipientId: (int) $user->id,
                        type: 'learning.certificate_issued',
                        data: [
                            'title' => 'Certificate Issued',
                            'message' => "Your verified certificate ({$certificate->certificate_code}) for {$course->title} is ready.",
                            'course_id' => (int) $course->id,
                            'certificate_id' => (int) $certificate->id,
                            'certificate_code' => $certificate->certificate_code,
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning('Failed to dispatch certificate notification', ['error' => $e->getMessage()]);
                }
            });

            return $certificate;
        });
    }

    /**
     * Revoke an issued certificate and sync removal with CandidateCertification.
     */
    public function revokeCertificate(User $instructor, LearningCertificate $certificate, string $reason): LearningCertificate
    {
        $course = $certificate->course;
        if (! $course || ((int) $course->instructor_id !== (int) $instructor->id && ! $instructor->isAdmin())) {
            throw new \Exception('Unauthorized to revoke this certificate.');
        }

        $certificate->update([
            'is_revoked' => true,
            'revoked_reason' => $reason,
            'revoked_at' => now(),
        ]);

        // Sync revocation with CandidateCertification
        CandidateCertification::where('user_id', $certificate->user_id)
            ->where('credential_id', $certificate->certificate_code)
            ->delete();

        return $certificate;
    }

    /**
     * Verify a certificate by its public code.
     */
    public function verifyCertificate(string $code): ?array
    {
        $cert = LearningCertificate::with(['course.instructor', 'user'])
            ->where('certificate_code', trim($code))
            ->first();

        if (! $cert) {
            return null;
        }

        return [
            'certificate_code' => $cert->certificate_code,
            'is_valid' => ! $cert->is_revoked,
            'is_revoked' => $cert->is_revoked,
            'revoked_reason' => $cert->revoked_reason,
            'student_name' => $cert->student_name,
            'course_title' => $cert->course_title,
            'instructor_name' => $cert->instructor_name,
            'issued_at' => $cert->issued_at->toIso8601String(),
            'verification_url' => url("/verify/cert/{$cert->certificate_code}"),
        ];
    }
}
