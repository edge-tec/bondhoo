<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\CandidateCertification;
use App\Models\LearningCertificate;
use App\Models\LearningCourse;
use App\Models\LearningEnrollment;
use App\Models\LearningLessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Issue a course completion certificate for a student.
     */
    public function issueCertificate(User $user, LearningCourse $course): LearningCertificate
    {
        return DB::transaction(function () use ($user, $course) {
            $existing = LearningCertificate::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            // Server-authoritative eligibility verification
            $enrollment = LearningEnrollment::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $enrollment) {
                throw new \Exception('Student is not enrolled in this course.');
            }

            $totalPublished = $course->lessons()->where('is_published', true)->count();
            if ($totalPublished > 0) {
                $completedCount = LearningLessonProgress::where('course_id', $course->id)
                    ->where('user_id', $user->id)
                    ->where('is_completed', true)
                    ->count();

                if ($completedCount < $totalPublished) {
                    throw new \Exception('Course requirements not satisfied: All lessons must be completed.');
                }
            }

            $year = date('Y');
            $code = 'CERT-'.$year.'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
            $token = Str::random(64);

            $instructorName = $course->instructor?->name ?? 'Connectly Academy';
            $studentName = $user->name ?: ($user->first_name.' '.$user->last_name);

            $certificate = LearningCertificate::create([
                'course_id' => $course->id,
                'user_id' => $user->id,
                'certificate_code' => $code,
                'verification_token' => $token,
                'student_name' => $studentName,
                'course_title' => $course->title,
                'instructor_name' => $instructorName,
                'pdf_url' => url("/api/v1/learning/certificates/{$code}/download"),
                'qr_code_url' => url("/verify/cert/{$code}"),
                'issued_at' => now(),
                'is_revoked' => false,
            ]);

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

            return $certificate;
        });
    }

    /**
     * Revoke an issued certificate and sync removal with CandidateCertification.
     */
    public function revokeCertificate(User $instructor, LearningCertificate $certificate, string $reason): LearningCertificate
    {
        $course = $certificate->course;
        if ($course->instructor_id !== $instructor->id && ! $instructor->hasRole(['ADMIN', 'SUPER_ADMIN'])) {
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
