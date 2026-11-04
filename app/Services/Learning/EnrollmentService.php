<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\LearningCourse;
use App\Models\LearningCourseCoupon;
use App\Models\LearningCourseProgress;
use App\Models\LearningEnrollment;
use App\Models\LearningTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnrollmentService
{
    /**
     * Enroll user in course (free or paid).
     */
    public function enrollUser(User $user, LearningCourse $course, ?string $couponCode = null): LearningEnrollment
    {
        return DB::transaction(function () use ($user, $course, $couponCode) {
            $existing = LearningEnrollment::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                if ($existing->status === LearningEnrollment::STATUS_ACTIVE) {
                    return $existing;
                }
                $existing->update([
                    'status' => LearningEnrollment::STATUS_ACTIVE,
                    'last_accessed_at' => now(),
                ]);

                return $existing;
            }

            $priceToPay = (float) ($course->discounted_price ?? $course->price);

            // Apply coupon if provided
            if ($couponCode && $priceToPay > 0) {
                $coupon = LearningCourseCoupon::where('code', trim($couponCode))->first();
                if ($coupon && $coupon->isValid()) {
                    if ($coupon->discount_type === LearningCourseCoupon::TYPE_PERCENTAGE) {
                        $discount = ($priceToPay * (float) $coupon->discount_amount) / 100;
                    } else {
                        $discount = (float) $coupon->discount_amount;
                    }
                    $priceToPay = max(0.00, $priceToPay - $discount);
                    $coupon->increment('times_used');
                }
            }

            // If paid course, verify and deduct wallet or process payment
            if ($priceToPay > 0) {
                Wallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0.00, 'currency' => 'USD', 'status' => Wallet::STATUS_ACTIVE]
                );

                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

                if ((float) $wallet->balance < $priceToPay) {
                    // For test/sandbox or if insufficient balance, allow demonstration or fail
                    throw new Exception("Insufficient wallet balance of \${$wallet->balance}. Course cost is \${$priceToPay}.");
                }

                $wallet->decrement('balance', $priceToPay);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $user->id,
                    'type' => 'course_enrollment',
                    'amount' => -$priceToPay,
                    'currency' => 'USD',
                    'reference_id' => (string) $course->id,
                    'description' => "Enrolled in course: {$course->title}",
                    'balance_after' => (float) $wallet->balance,
                ]);

                // Instructor earnings (70% standard)
                $instructorEarning = round($priceToPay * 0.70, 2);
                $platformFee = round($priceToPay - $instructorEarning, 2);

                LearningTransaction::create([
                    'course_id' => $course->id,
                    'user_id' => $user->id,
                    'instructor_id' => $course->instructor_id,
                    'amount' => $priceToPay,
                    'instructor_earning' => $instructorEarning,
                    'platform_fee' => $platformFee,
                    'currency' => 'USD',
                    'payment_method' => 'wallet',
                    'transaction_reference' => 'TXN-'.strtoupper(Str::random(12)),
                    'status' => LearningTransaction::STATUS_COMPLETED,
                ]);
            }

            $enrollment = LearningEnrollment::create([
                'course_id' => $course->id,
                'user_id' => $user->id,
                'status' => LearningEnrollment::STATUS_ACTIVE,
                'price_paid' => $priceToPay,
                'currency' => $course->currency ?? 'USD',
                'enrolled_at' => now(),
                'last_accessed_at' => now(),
            ]);

            // Initialize course progress
            $totalLessons = (int) $course->lessons()->where('is_published', true)->count();
            LearningCourseProgress::create([
                'enrollment_id' => $enrollment->id,
                'course_id' => $course->id,
                'user_id' => $user->id,
                'completed_lessons_count' => 0,
                'total_lessons_count' => $totalLessons,
                'percentage' => 0.00,
                'is_completed' => false,
            ]);

            $course->increment('enrollments_count');

            return $enrollment;
        });
    }

    /**
     * Check if user is enrolled in course.
     */
    public function isEnrolled(User $user, LearningCourse $course): bool
    {
        return LearningEnrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('status', LearningEnrollment::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Cancel an active enrollment.
     */
    public function cancelEnrollment(User $user, LearningEnrollment $enrollment): LearningEnrollment
    {
        if ($enrollment->user_id !== $user->id && ! $user->hasRole(['ADMIN', 'SUPER_ADMIN'])) {
            throw new Exception('Unauthorized to cancel this enrollment.');
        }

        if ($enrollment->status === LearningEnrollment::STATUS_CANCELLED) {
            return $enrollment;
        }

        $enrollment->update([
            'status' => LearningEnrollment::STATUS_CANCELLED,
        ]);

        return $enrollment;
    }

    /**
     * Get single enrollment details with relationships.
     */
    public function getEnrollmentDetails(User $user, LearningEnrollment $enrollment): LearningEnrollment
    {
        if ($enrollment->user_id !== $user->id && ! $user->hasRole(['ADMIN', 'SUPER_ADMIN'])) {
            throw new Exception('Unauthorized to view this enrollment.');
        }

        return $enrollment->load(['course.instructor', 'courseProgress']);
    }
}
