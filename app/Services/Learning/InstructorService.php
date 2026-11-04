<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\LearningCourse;
use App\Models\LearningInstructor;
use App\Models\LearningInstructorApplication;
use App\Models\LearningTransaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class InstructorService
{
    /**
     * Submit an instructor application.
     */
    public function applyForInstructor(User $user, array $data): LearningInstructor
    {
        $existingPending = LearningInstructorApplication::where('user_id', $user->id)
            ->where('status', LearningInstructorApplication::STATUS_PENDING)
            ->first();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'application' => ['আপনার একটি আবেদন ইতিমধ্যে পর্যালোচনার জন্য অপেক্ষমাণ রয়েছে।'],
            ]);
        }

        LearningInstructorApplication::create([
            'user_id' => $user->id,
            'expertise' => is_array($data['expertise'] ?? null) ? implode(', ', $data['expertise']) : (string) ($data['expertise'] ?? $data['headline']),
            'experience_years' => (int) ($data['experience_years'] ?? 1),
            'portfolio_url' => $data['portfolio_url'] ?? ($data['website_url'] ?? null),
            'sample_video_url' => $data['sample_video_url'] ?? null,
            'proposal' => $data['proposal'] ?? ($data['biography'] ?? 'Professional instructor application.'),
            'status' => LearningInstructorApplication::STATUS_PENDING,
        ]);

        return LearningInstructor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'headline' => $data['headline'],
                'biography' => $data['biography'] ?? null,
                'expertise' => $data['expertise'] ?? [],
                'website_url' => $data['website_url'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'github_url' => $data['github_url'] ?? null,
                'status' => LearningInstructor::STATUS_APPROVED, // Auto-approve or admin review
                'is_verified' => false,
                'approved_at' => now(),
            ]
        );
    }

    /**
     * Get instructor profile by ID or User ID.
     */
    public function getInstructorProfile(int $id): ?LearningInstructor
    {
        return LearningInstructor::with([
            'user',
            'courses' => function ($q) {
                $q->where('status', LearningCourse::STATUS_PUBLISHED)->orderByDesc('enrollments_count');
            },
        ])->where('id', $id)->orWhere('user_id', $id)->first();
    }

    /**
     * Verify or approve an instructor.
     */
    public function verifyInstructor(LearningInstructor $instructor, bool $verified = true, ?User $admin = null): LearningInstructor
    {
        $instructor->update([
            'is_verified' => $verified,
            'status' => LearningInstructor::STATUS_APPROVED,
            'approved_at' => $instructor->approved_at ?? now(),
        ]);

        LearningInstructorApplication::where('user_id', $instructor->user_id)
            ->where('status', LearningInstructorApplication::STATUS_PENDING)
            ->update([
                'status' => LearningInstructorApplication::STATUS_APPROVED,
                'reviewer_id' => $admin?->id,
                'reviewed_at' => now(),
            ]);

        return $instructor;
    }

    /**
     * Get instructor dashboard analytics.
     */
    public function getInstructorDashboard(User $instructor): array
    {
        $courses = LearningCourse::where('instructor_id', $instructor->id)->get();
        $totalCourses = $courses->count();
        $totalStudents = (int) $courses->sum('enrollments_count');
        $averageRating = $courses->avg('rating_avg') ?: 0.00;

        $totalEarnings = (float) LearningTransaction::where('instructor_id', $instructor->id)
            ->where('status', LearningTransaction::STATUS_COMPLETED)
            ->sum('instructor_earning');

        return [
            'total_courses' => $totalCourses,
            'total_students' => $totalStudents,
            'rating_avg' => round((float) $averageRating, 2),
            'total_earnings' => $totalEarnings,
            'courses' => $courses->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'slug' => $c->slug,
                    'status' => $c->status,
                    'price' => (float) $c->price,
                    'enrollments_count' => $c->enrollments_count,
                    'rating_avg' => (float) $c->rating_avg,
                    'created_at' => $c->created_at->toIso8601String(),
                ];
            })->values(),
        ];
    }
}
