<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\LearningCourse;
use App\Models\LearningCourseCoupon;
use App\Models\LearningCourseProgress;
use App\Models\LearningEnrollment;
use App\Models\LearningInstructor;
use App\Models\LearningTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Contracts\NotificationServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EnrollmentService
{
    /**
     * Enroll user in course (free or paid).
     */
    public function enrollUser(User $user, LearningCourse $course, ?string $couponCode = null): LearningEnrollment
    {
        if ($course->trashed() || $course->status !== LearningCourse::STATUS_PUBLISHED) {
            throw new InvalidArgumentException('This course is not currently available for enrollment.');
        }

        if (in_array($course->type, [LearningCourse::TYPE_PRIVATE, LearningCourse::TYPE_ORGANIZATION], true) && ! $user->isAdmin()) {
            throw new InvalidArgumentException('This course requires private or organization invitation.');
        }

        if ((int) $course->instructor_id === (int) $user->id) {
            throw new InvalidArgumentException('Instructors cannot enroll as students in their own course.');
        }

        return DB::transaction(function () use ($user, $course, $couponCode) {
            // Serialize concurrent enrollment requests for the same user & course
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedCourse = LearningCourse::whereKey($course->id)->lockForUpdate()->firstOrFail();

            if ($lockedCourse->status !== LearningCourse::STATUS_PUBLISHED) {
                throw new InvalidArgumentException('This course is not currently available for enrollment.');
            }

            $existing = LearningEnrollment::where('course_id', $lockedCourse->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            // Active or completed enrollments are idempotent: preserve status & progress without re-charging
            if ($existing && in_array($existing->status, [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED], true)) {
                $existing->update([
                    'last_accessed_at' => now(),
                ]);

                return $existing;
            }

            $priceToPay = $lockedCourse->type === LearningCourse::TYPE_FREE
                ? 0.00
                : max(0.00, (float) ($lockedCourse->discounted_price ?? $lockedCourse->price));

            // Validate and apply coupon if provided
            $coupon = null;
            $normalizedCoupon = $couponCode !== null ? trim($couponCode) : '';
            if ($normalizedCoupon !== '') {
                $coupon = LearningCourseCoupon::whereRaw('UPPER(code) = ?', [strtoupper($normalizedCoupon)])
                    ->lockForUpdate()
                    ->first();
                if (! $coupon || ! $coupon->isValid((int) $lockedCourse->id)) {
                    throw new InvalidArgumentException('The provided coupon code is invalid, expired, or not valid for this course.');
                }

                if ($priceToPay > 0) {
                    if ($coupon->discount_type === LearningCourseCoupon::TYPE_PERCENTAGE) {
                        $discount = ($priceToPay * (float) $coupon->discount_amount) / 100.0;
                    } else {
                        $discount = (float) $coupon->discount_amount;
                    }
                    $priceToPay = round(max(0.00, $priceToPay - $discount), 2);
                }
            }

            $learningTransaction = null;

            // If paid course, verify active wallet and deduct atomically BEFORE activating/reactivating enrollment
            if ($priceToPay > 0) {
                Wallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0.00, 'currency' => $lockedCourse->currency ?? 'USD', 'status' => Wallet::STATUS_ACTIVE]
                );

                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

                if ($wallet->status !== Wallet::STATUS_ACTIVE) {
                    throw new InvalidArgumentException('Your wallet is not active and cannot be used for enrollment.');
                }

                if ((float) $wallet->balance < $priceToPay) {
                    throw new Exception("Insufficient wallet balance of \${$wallet->balance}. Course cost is \${$priceToPay}.");
                }

                $wallet->decrement('balance', $priceToPay);
                $wallet->refresh();

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $user->id,
                    'type' => 'course_enrollment',
                    'amount' => -$priceToPay,
                    'currency' => $lockedCourse->currency ?? 'USD',
                    'reference_id' => (string) $lockedCourse->id,
                    'description' => "Enrolled in course: {$lockedCourse->title}",
                    'balance_after' => (float) $wallet->balance,
                ]);

                // Instructor earnings based on instructor commission rate (default 70%)
                $instructorProfile = LearningInstructor::where('user_id', $lockedCourse->instructor_id)->first();
                $commissionRate = ((float) ($instructorProfile?->commission_rate ?? 70.00)) / 100.0;
                $instructorEarning = round($priceToPay * $commissionRate, 2);
                $platformFee = round($priceToPay - $instructorEarning, 2);

                if ($instructorEarning > 0 && User::whereKey($lockedCourse->instructor_id)->exists()) {
                    Wallet::firstOrCreate(
                        ['user_id' => $lockedCourse->instructor_id],
                        ['balance' => 0.00, 'currency' => $lockedCourse->currency ?? 'USD', 'status' => Wallet::STATUS_ACTIVE]
                    );
                    $instructorWallet = Wallet::where('user_id', $lockedCourse->instructor_id)->lockForUpdate()->first();
                    if ($instructorWallet && $instructorWallet->status === Wallet::STATUS_ACTIVE) {
                        $instructorWallet->increment('balance', $instructorEarning);
                        $instructorWallet->refresh();

                        WalletTransaction::create([
                            'wallet_id' => $instructorWallet->id,
                            'user_id' => $lockedCourse->instructor_id,
                            'type' => 'instructor_earning',
                            'amount' => $instructorEarning,
                            'currency' => $lockedCourse->currency ?? 'USD',
                            'reference_id' => (string) $lockedCourse->id,
                            'description' => "Student enrollment earning: {$lockedCourse->title}",
                            'balance_after' => (float) $instructorWallet->balance,
                        ]);
                    }
                }

                $learningTransaction = LearningTransaction::create([
                    'course_id' => $lockedCourse->id,
                    'user_id' => $user->id,
                    'instructor_id' => $lockedCourse->instructor_id,
                    'amount' => $priceToPay,
                    'instructor_earning' => $instructorEarning,
                    'platform_fee' => $platformFee,
                    'currency' => $lockedCourse->currency ?? 'USD',
                    'payment_method' => 'wallet',
                    'transaction_reference' => 'TXN-'.strtoupper(Str::random(12)),
                    'status' => LearningTransaction::STATUS_COMPLETED,
                ]);
            }

            if ($coupon) {
                $coupon->increment('times_used');
            }

            if ($existing) {
                $existing->update([
                    'status' => LearningEnrollment::STATUS_ACTIVE,
                    'price_paid' => $priceToPay,
                    'currency' => $lockedCourse->currency ?? 'USD',
                    'enrolled_at' => $existing->enrolled_at ?? now(),
                    'last_accessed_at' => now(),
                ]);
                $enrollment = $existing->fresh();
            } else {
                $enrollment = LearningEnrollment::create([
                    'course_id' => $lockedCourse->id,
                    'user_id' => $user->id,
                    'status' => LearningEnrollment::STATUS_ACTIVE,
                    'price_paid' => $priceToPay,
                    'currency' => $lockedCourse->currency ?? 'USD',
                    'enrolled_at' => now(),
                    'last_accessed_at' => now(),
                ]);
            }

            if ($learningTransaction) {
                $learningTransaction->update(['enrollment_id' => $enrollment->id]);
            }

            // Initialize or preserve course progress
            $totalLessons = (int) $lockedCourse->lessons()->where('is_published', true)->count();
            LearningCourseProgress::firstOrCreate(
                ['enrollment_id' => $enrollment->id],
                [
                    'course_id' => $lockedCourse->id,
                    'user_id' => $user->id,
                    'completed_lessons_count' => 0,
                    'total_lessons_count' => $totalLessons,
                    'percentage' => 0.00,
                    'is_completed' => false,
                ]
            );

            $lockedCourse->increment('enrollments_count');

            DB::afterCommit(function () use ($user, $lockedCourse, $enrollment, $priceToPay): void {
                try {
                    $notificationService = app(NotificationServiceInterface::class);
                    $notificationService->send(
                        recipientId: (int) $user->id,
                        type: 'learning.course_enrolled',
                        data: [
                            'title' => 'Course Enrollment Confirmed',
                            'message' => "You are now enrolled in {$lockedCourse->title}.",
                            'course_id' => (int) $lockedCourse->id,
                            'course_slug' => $lockedCourse->slug,
                            'enrollment_id' => (int) $enrollment->id,
                            'price_paid' => $priceToPay,
                        ]
                    );

                    if ((int) $lockedCourse->instructor_id !== (int) $user->id && User::whereKey($lockedCourse->instructor_id)->exists()) {
                        $notificationService->send(
                            recipientId: (int) $lockedCourse->instructor_id,
                            type: 'learning.new_student',
                            data: [
                                'title' => 'New Student Enrolled',
                                'message' => "{$user->name} enrolled in {$lockedCourse->title}.",
                                'course_id' => (int) $lockedCourse->id,
                                'student_id' => (int) $user->id,
                            ]
                        );
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to dispatch course enrollment notification', ['error' => $e->getMessage()]);
                }
            });

            return $enrollment;
        });
    }

    /**
     * Check if user is enrolled in course (active or completed).
     */
    public function isEnrolled(User $user, LearningCourse $course): bool
    {
        return LearningEnrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->whereIn('status', [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED])
            ->exists();
    }

    /**
     * Cancel an active enrollment.
     */
    public function cancelEnrollment(User $user, LearningEnrollment $enrollment): LearningEnrollment
    {
        if ((int) $enrollment->user_id !== (int) $user->id && ! $user->isAdmin()) {
            throw new AuthorizationException('Unauthorized to cancel this enrollment.');
        }

        return DB::transaction(function () use ($enrollment) {
            $locked = LearningEnrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === LearningEnrollment::STATUS_CANCELLED) {
                return $locked;
            }

            if ($locked->status === LearningEnrollment::STATUS_COMPLETED) {
                throw new InvalidArgumentException('Completed course enrollments cannot be cancelled.');
            }

            $wasActive = $locked->status === LearningEnrollment::STATUS_ACTIVE;

            $locked->update([
                'status' => LearningEnrollment::STATUS_CANCELLED,
            ]);

            if ($wasActive) {
                $course = LearningCourse::whereKey($locked->course_id)->lockForUpdate()->first();
                if ($course && (int) $course->enrollments_count > 0) {
                    $course->decrement('enrollments_count');
                }
            }

            return $locked->fresh();
        });
    }

    /**
     * Get single enrollment details with relationships.
     */
    public function getEnrollmentDetails(User $user, LearningEnrollment $enrollment): LearningEnrollment
    {
        if ((int) $enrollment->user_id !== (int) $user->id && ! $user->isAdmin()) {
            throw new AuthorizationException('Unauthorized to view this enrollment.');
        }

        return $enrollment->load(['course.instructor', 'courseProgress']);
    }
}
