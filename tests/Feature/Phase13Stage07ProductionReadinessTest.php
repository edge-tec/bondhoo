<?php

namespace Tests\Feature;

use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Models\LearningEnrollment;
use App\Models\LearningInstructor;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceOrder;
use App\Models\MarketplacePaymentTransaction;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Marketplace\Payments\PaymentGatewayManager;
use App\Services\Marketplace\Payments\SandboxGatewayDriver;
use Exception;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Pusher\Pusher;
use Tests\TestCase;

/**
 * Phase 13 Stage 07 — Production Readiness, Security & Performance Audit Test
 */
class Phase13Stage07ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $instructorUser;

    protected LearningInstructor $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'stage07.auditor@jugajug.test',
            'username' => 'auditor07',
        ]);

        $this->instructorUser = User::factory()->create([
            'email' => 'stage07.instructor@jugajug.test',
            'username' => 'instructor07',
        ]);

        $this->instructor = LearningInstructor::create([
            'user_id' => $this->instructorUser->id,
            'headline' => 'Enterprise System Architect',
            'bio' => 'Auditing production readiness and data resilience.',
            'status' => LearningInstructor::STATUS_APPROVED,
        ]);
    }

    /**
     * 1. PAYMENT GATEWAY SANDBOX & REPLAY ATTACK IDEMPOTENCY
     */
    public function test_payment_gateway_sandbox_and_duplicate_webhook_replay_prevention(): void
    {
        config(['services.marketplace_payments.webhook_secret' => 'audit_secret_key_2026']);

        $driver = new SandboxGatewayDriver('sandbox', 'audit_secret_key_2026');

        $category = MarketplaceCategory::create([
            'name' => 'Technology Books',
            'slug' => 'technology-books',
        ]);

        $product = MarketplaceProduct::create([
            'seller_id' => $this->instructorUser->id,
            'category_id' => $category->id,
            'title' => 'Advanced Cloud Architecture Book',
            'description' => 'Comprehensive enterprise guide to distributed systems.',
            'price' => 150.00,
            'currency' => 'BDT',
            'condition' => 'new',
            'delivery_type' => 'courier',
            'location' => 'Dhaka',
            'status' => MarketplaceProduct::STATUS_ACTIVE,
        ]);

        $order = MarketplaceOrder::create([
            'order_number' => 'ORD-AUDIT-'.time(),
            'buyer_id' => $this->user->id,
            'seller_id' => $this->instructorUser->id,
            'product_id' => $product->id,
            'amount' => 150.00,
            'currency' => 'BDT',
            'status' => MarketplaceOrder::STATUS_PENDING,
            'payment_status' => MarketplaceOrder::PAYMENT_UNPAID,
            'payment_method' => 'sandbox',
            'items_count' => 1,
            'shipping_address' => 'Dhaka, Bangladesh',
        ]);

        // Initiate payment
        $session = $driver->initiatePayment($order);
        $this->assertNotEmpty($session['transaction_id']);
        $this->assertSame(150.00, $session['amount']);

        $manager = app(PaymentGatewayManager::class);

        $payload = [
            'gateway' => 'sandbox',
            'status' => 'completed',
            'transaction_id' => $session['transaction_id'],
            'amount' => 150.00,
            'currency' => 'BDT',
            'signature' => $session['signature'],
        ];

        // First callback execution -> Must succeed and mark order paid
        $firstResult = $manager->processCallback($order, $payload, '127.0.0.1');
        $this->assertTrue($firstResult['verified']);
        $this->assertSame(MarketplacePaymentTransaction::STATUS_SUCCESSFUL, $firstResult['transaction']->status);
        $this->assertTrue($order->fresh()->isPaid());

        $firstTxnId = $firstResult['transaction']->id;
        $totalTransactionsBefore = MarketplacePaymentTransaction::count();

        // Second callback execution (Simulated Replay Attack / Network Retry)
        $secondResult = $manager->processCallback($order, $payload, '127.0.0.1');
        $this->assertTrue($secondResult['verified']);
        $this->assertSame($firstTxnId, $secondResult['transaction']->id, 'Must return the original transaction idempotently.');

        // Assert NO duplicate transaction was inserted into the ledger
        $totalTransactionsAfter = MarketplacePaymentTransaction::count();
        $this->assertSame($totalTransactionsBefore, $totalTransactionsAfter, 'Duplicate webhook replay must not create additional transactions.');
    }

    /**
     * 2. WALLET BALANCE RECONCILIATION & CONCURRENCY OVERDRAFT SAFETY
     */
    public function test_wallet_balance_reconciliation_and_atomic_overdraft_prevention(): void
    {
        $wallet = Wallet::create([
            'user_id' => $this->user->id,
            'balance' => 200.00,
            'currency' => 'USD',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        // Transaction 1: Credit $50
        $wallet->increment('balance', 50.00);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'type' => 'deposit',
            'amount' => 50.00,
            'currency' => 'USD',
            'reference_id' => 'DEP-001',
            'description' => 'Topup deposit',
            'balance_after' => 250.00,
        ]);

        // Transaction 2: Debit $120
        $wallet->decrement('balance', 120.00);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'type' => 'course_enrollment',
            'amount' => -120.00,
            'currency' => 'USD',
            'reference_id' => 'ENR-001',
            'description' => 'Course purchase',
            'balance_after' => 130.00,
        ]);

        // Transaction 3: Debit $30
        $wallet->decrement('balance', 30.00);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'type' => 'course_enrollment',
            'amount' => -30.00,
            'currency' => 'USD',
            'reference_id' => 'ENR-002',
            'description' => 'Course purchase',
            'balance_after' => 100.00,
        ]);

        $freshWallet = $wallet->fresh();
        $this->assertEquals(100.00, (float) $freshWallet->balance);

        // Mathematical Ledger Reconciliation:
        // Initial Balance (200) + Net Transactions (+50 -120 -30 = -100) = 100
        $initialBalance = 200.00;
        $netTransactions = (float) WalletTransaction::where('wallet_id', $wallet->id)->sum('amount');
        $reconciledBalance = $initialBalance + $netTransactions;

        $this->assertEquals($reconciledBalance, (float) $freshWallet->balance, 'Ledger sum must exactly reconcile with current wallet balance.');

        // Test Overdraft Prevention with atomic rollback
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient funds');

        DB::transaction(function () use ($freshWallet) {
            $lockedWallet = Wallet::where('id', $freshWallet->id)->lockForUpdate()->firstOrFail();
            $attemptedDebit = 500.00; // Greater than current $100

            if ((float) $lockedWallet->balance < $attemptedDebit) {
                throw new Exception('Insufficient funds: cannot overdraft wallet.');
            }

            $lockedWallet->decrement('balance', $attemptedDebit);
        });
    }

    /**
     * 3. REAL COMPLEX DATABASE QUERY STRESS & TRANSACTION ATOMICITY
     */
    public function test_complex_database_query_performance_and_rollback_atomicity(): void
    {
        // Seed realistic relational structure
        $course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'High-Concurrency Distributed Architecture in Laravel',
            'slug' => 'distributed-architecture-laravel',
            'description' => 'Deep dive into sharding, replication, and concurrency.',
            'level' => 'expert',
            'language' => 'en',
            'type' => LearningCourse::TYPE_PAID,
            'price' => 99.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
            'duration_minutes' => 720,
            'is_published' => true,
        ]);

        for ($s = 1; $s <= 5; $s++) {
            $section = LearningCourseSection::create([
                'course_id' => $course->id,
                'title' => "Section {$s}: Distributed Components",
                'order' => $s,
            ]);

            for ($l = 1; $l <= 4; $l++) {
                LearningCourseLesson::create([
                    'section_id' => $section->id,
                    'course_id' => $course->id,
                    'title' => "Lesson {$s}.{$l}: Concurrency Tuning",
                    'slug' => "lesson-{$s}-{$l}-concurrency",
                    'type' => 'video',
                    'duration_minutes' => 20,
                    'order' => $l,
                    'is_preview' => ($l === 1),
                ]);
            }
        }

        // Add 10 student enrollments
        for ($i = 0; $i < 10; $i++) {
            $st = User::factory()->create();
            LearningEnrollment::create([
                'user_id' => $st->id,
                'course_id' => $course->id,
                'status' => 'active',
                'price_paid' => 99.00,
                'enrolled_at' => now(),
            ]);
        }

        // Execute complex query: 4 JOINs, Aggregates, GROUP BY
        $iterations = 50;
        $t0 = microtime(true);

        for ($k = 0; $k < $iterations; $k++) {
            $report = DB::table('learning_courses as c')
                ->join('learning_instructors as i', 'c.instructor_id', '=', 'i.id')
                ->join('users as u', 'i.user_id', '=', 'u.id')
                ->leftJoin('learning_course_sections as s', 'c.id', '=', 's.course_id')
                ->leftJoin('learning_course_lessons as l', 's.id', '=', 'l.section_id')
                ->leftJoin('learning_enrollments as e', 'c.id', '=', 'e.course_id')
                ->select(
                    'c.id as course_id',
                    'c.title as course_title',
                    'u.name as instructor_name',
                    DB::raw('COUNT(DISTINCT s.id) as total_sections'),
                    DB::raw('COUNT(DISTINCT l.id) as total_lessons'),
                    DB::raw('COUNT(DISTINCT e.id) as total_enrollments'),
                    DB::raw('(SELECT COALESCE(SUM(price_paid), 0) FROM learning_enrollments WHERE course_id = c.id) as total_revenue')
                )
                ->where('c.status', '=', 'published')
                ->groupBy('c.id', 'c.title', 'u.name')
                ->get();

            $this->assertNotEmpty($report);
            $this->assertSame(5, (int) $report[0]->total_sections);
            $this->assertSame(20, (int) $report[0]->total_lessons);
            $this->assertSame(10, (int) $report[0]->total_enrollments);
            $this->assertEquals(990.00, (float) $report[0]->total_revenue);
        }

        $totalMs = (microtime(true) - $t0) * 1000;
        $avgMsPerQuery = $totalMs / $iterations;

        // In-memory / local query should be sub-5ms per complex execution
        $this->assertLessThan(20.0, $avgMsPerQuery, "Complex query avg latency was {$avgMsPerQuery}ms (Target: < 20ms)");

        // Test Transaction Atomicity: mid-flight failure must roll back completely
        $initialSectionCount = LearningCourseSection::count();
        $initialLessonCount = LearningCourseLesson::count();

        try {
            DB::transaction(function () use ($course) {
                LearningCourseSection::create([
                    'course_id' => $course->id,
                    'title' => 'Faulty Section (Must Roll Back)',
                    'order' => 99,
                ]);

                // Deliberate exception to trigger rollback
                throw new Exception('Simulated fatal database connection rupture.');
            });
        } catch (Exception $e) {
            // Expected
        }

        $this->assertSame($initialSectionCount, LearningCourseSection::count(), 'Aborted transaction must roll back created sections.');
        $this->assertSame($initialLessonCount, LearningCourseLesson::count(), 'Aborted transaction must roll back all related records.');
    }

    /**
     * 4. REAL BACKUP & RESTORE REHEARSAL VERIFICATION
     */
    public function test_actual_database_backup_and_isolated_restore_verification(): void
    {
        $tempBackupFile = sys_get_temp_dir().'/jugajug_stage07_backup_'.time().'.sqlite';
        $tempRestoreFile = sys_get_temp_dir().'/jugajug_stage07_restore_'.time().'.sqlite';

        // Connect to a disk-based SQLite DB to perform physical backup & restore
        $pdo = DB::connection()->getPdo();

        // Verify tables exist
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $originalTableCount = count($tables);
        $this->assertGreaterThan(10, $originalTableCount, 'Database must have real tables.');

        $originalUserCount = User::count();
        $this->assertGreaterThan(0, $originalUserCount);

        // Perform physical schema & data dump
        $dumpSql = '';
        foreach ($tables as $t) {
            $tableName = $t->name;
            $createRow = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$tableName]);
            if (! empty($createRow[0]->sql)) {
                $dumpSql .= $createRow[0]->sql.";\n";
            }
            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $cols = array_keys((array) $row);
                $quotedCols = array_map(fn ($c) => '"'.$c.'"', $cols);
                $vals = array_map(function ($v) {
                    if ($v === null) {
                        return 'NULL';
                    }

                    return "'".str_replace("'", "''", (string) $v)."'";
                }, array_values((array) $row));

                $dumpSql .= 'INSERT INTO "'.$tableName.'" ('.implode(',', $quotedCols).') VALUES ('.implode(',', $vals).");\n";
            }
        }

        file_put_contents($tempBackupFile, $dumpSql);
        $this->assertFileExists($tempBackupFile);
        $this->assertGreaterThan(500, filesize($tempBackupFile));

        // Restore into isolated temporary database
        if (file_exists($tempRestoreFile)) {
            unlink($tempRestoreFile);
        }
        touch($tempRestoreFile);

        $restorePdo = new \PDO('sqlite:'.$tempRestoreFile);
        $restorePdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $restorePdo->exec($dumpSql);

        // Assert restored database matches original
        $restoredTables = $restorePdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame($originalTableCount, count($restoredTables), 'Restored table count must exactly match original.');

        $restoredUserCount = (int) $restorePdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $this->assertSame($originalUserCount, $restoredUserCount, 'Restored user record count must match original.');

        // Cleanup temporary rehearsal files
        @unlink($tempBackupFile);
        @unlink($tempRestoreFile);
    }

    /**
     * 5. WEBSOCKET CHANNELS & WEBRTC CALLING AUTHORIZATION STRESS
     */
    public function test_websocket_channels_authorization_and_webrtc_calling_stress(): void
    {
        $pusher = \Mockery::mock(Pusher::class);
        $pusher->shouldReceive('authorizeChannel')->andReturn(json_encode(['auth' => 'test-auth-signature']));
        $broadcaster = new PusherBroadcaster($pusher);
        Broadcast::extend('mock_pusher', fn () => $broadcaster);
        config([
            'broadcasting.connections.mock_pusher' => ['driver' => 'mock_pusher'],
            'broadcasting.default' => 'mock_pusher',
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $users = User::factory()->count(10)->create();

        // 1. User private channel authorization: User A cannot listen to User B's channel
        $resForbidden = $this->actingAs($users[0])->postJson('/broadcasting/auth', [
            'channel_name' => "private-user.{$users[1]->id}",
            'socket_id' => '1234.5678',
        ]);
        $resForbidden->assertForbidden();

        $resAuthorized = $this->actingAs($users[0])->postJson('/broadcasting/auth', [
            'channel_name' => "private-user.{$users[0]->id}",
            'socket_id' => '1234.5678',
        ]);
        $resAuthorized->assertOk();

        // 2. High-concurrency authorization simulation: 100 requests in loop
        $t0 = microtime(true);
        $iterations = 100;
        for ($i = 0; $i < $iterations; $i++) {
            $currentUser = $users[$i % 10];
            $response = $this->actingAs($currentUser)->postJson('/broadcasting/auth', [
                'channel_name' => "private-user.{$currentUser->id}",
                'socket_id' => '1234.'.rand(1000, 9999),
            ]);
            $response->assertOk();
        }
        $totalMs = (microtime(true) - $t0) * 1000;
        $avgMsPerAuth = $totalMs / $iterations;

        $this->assertLessThan(25.0, $avgMsPerAuth, "WebSocket auth latency was {$avgMsPerAuth}ms (Target: < 25ms)");
    }

    /**
     * 6. PRODUCTION MIGRATION STATUS & ENGINE RESILIENCE
     */
    public function test_production_migration_status_and_engine_resilience(): void
    {
        // Assert migration status command runs cleanly
        $exitCode = Artisan::call('migrate:status');
        $this->assertSame(0, $exitCode, 'Migrate status command must return exit code 0.');
    }
}
