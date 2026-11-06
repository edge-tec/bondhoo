<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningCourse;
use App\Models\LearningEnrollment;
use App\Models\LearningTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13Stage05WalletAndEnrollmentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $student;

    protected LearningCourse $paidCourse;

    protected LearningCourse $freeCourse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['name' => 'Prof. Alan Turing']);
        $this->student = User::factory()->create(['name' => 'Student Alice']);

        $this->paidCourse = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Advanced Cryptography & Zero-Knowledge Proofs',
            'slug' => 'advanced-cryptography-zkp',
            'description' => 'Comprehensive masterclass on modern cryptographic protocols.',
            'level' => 'expert',
            'language' => 'en',
            'type' => LearningCourse::TYPE_PAID,
            'price' => 75.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
            'duration_minutes' => 600,
            'is_published' => true,
        ]);

        $this->freeCourse = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Intro to Modular Arithmetic',
            'slug' => 'intro-modular-arithmetic',
            'description' => 'Foundational mathematics course.',
            'level' => 'beginner',
            'language' => 'en',
            'type' => LearningCourse::TYPE_FREE,
            'price' => 0.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
            'duration_minutes' => 120,
            'is_published' => true,
        ]);
    }

    /**
     * 1. Insufficient balance fails gracefully without deducting money or enrolling
     */
    public function test_insufficient_wallet_balance_blocks_enrollment(): void
    {
        $wallet = Wallet::create([
            'user_id' => $this->student->id,
            'balance' => 20.00, // Course is $75
            'currency' => 'USD',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $res = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->paidCourse->id}/enroll");

        $res->assertStatus(422);

        // Wallet balance remains completely untouched
        $this->assertEquals(20.00, (float) $wallet->fresh()->balance);

        // No enrollment or transaction created
        $this->assertEquals(0, LearningEnrollment::where('course_id', $this->paidCourse->id)->where('user_id', $this->student->id)->count());
        $this->assertEquals(0, WalletTransaction::where('user_id', $this->student->id)->count());
    }

    /**
     * 2. Atomic paid course enrollment deducts exact amount and creates ledger entries
     */
    public function test_atomic_paid_course_enrollment_and_ledger_integrity(): void
    {
        $wallet = Wallet::create([
            'user_id' => $this->student->id,
            'balance' => 100.00,
            'currency' => 'USD',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $res = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->paidCourse->id}/enroll");

        $res->assertOk()
            ->assertJsonPath('success', true);

        // 1. Wallet balance decremented by exactly $75
        $this->assertEquals(25.00, (float) $wallet->fresh()->balance);

        // 2. Financial ledger entry created
        $walletTxn = WalletTransaction::where('user_id', $this->student->id)->first();
        $this->assertNotNull($walletTxn);
        $this->assertEquals(-75.00, (float) $walletTxn->amount);

        // 3. Learning platform transaction with fee split (70% instructor, 30% platform)
        $lmsTxn = LearningTransaction::where('course_id', $this->paidCourse->id)->where('user_id', $this->student->id)->first();
        $this->assertNotNull($lmsTxn);
        $this->assertEquals(75.00, (float) $lmsTxn->amount);
        $this->assertEquals(52.50, (float) $lmsTxn->instructor_earning);
        $this->assertEquals(22.50, (float) $lmsTxn->platform_fee);

        // 4. Enrollment record created
        $this->assertEquals(1, LearningEnrollment::where('course_id', $this->paidCourse->id)->where('user_id', $this->student->id)->count());
    }

    /**
     * 3. Idempotency: Repeated enrollment requests do not double charge or corrupt records
     */
    public function test_repeated_enrollment_is_idempotent_and_does_not_double_charge(): void
    {
        $wallet = Wallet::create([
            'user_id' => $this->student->id,
            'balance' => 100.00,
            'currency' => 'USD',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        // First enrollment
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->paidCourse->id}/enroll")
            ->assertOk();

        $this->assertEquals(25.00, (float) $wallet->fresh()->balance);

        // Second enrollment attempt
        $repeated = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$this->paidCourse->id}/enroll");

        $repeated->assertOk();

        // Still $25, exactly 1 enrollment, exactly 1 transaction
        $this->assertEquals(25.00, (float) $wallet->fresh()->balance);
        $this->assertEquals(1, LearningEnrollment::where('course_id', $this->paidCourse->id)->where('user_id', $this->student->id)->count());
        $this->assertEquals(1, WalletTransaction::where('user_id', $this->student->id)->count());
    }
}
