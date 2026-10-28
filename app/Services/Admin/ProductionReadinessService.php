<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\ProductionReadinessRun;
use App\Models\User;
use App\Services\Audit\TamperEvidentAuditService;
use App\Services\BackupService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * ProductionReadinessService — প্রোডাকশন কনফিগারেশন, নিরাপত্তা ও ডেপ্লয়মেন্ট প্রস্তুতি অডিট সার্ভিস (Phase 5)
 *
 * এই সার্ভিসটি কোনো হার্ডকোডেড সবুজ স্ট্যাটাস প্রদান করে না; প্রতিটি কনফিগারেশন বাস্তবে পরীক্ষা করে
 * pass, warning, fail অথবা not_applicable স্ট্যাটাস ও প্রতিকার নির্দেশনা প্রদান করে।
 */
class ProductionReadinessService
{
    public const STATUS_PASS = 'pass';

    public const STATUS_WARNING = 'warning';

    public const STATUS_FAIL = 'fail';

    public const STATUS_NOT_APPLICABLE = 'not_applicable';

    public function __construct(
        protected BackupService $backupService,
        protected TamperEvidentAuditService $auditService
    ) {}

    /**
     * Execute all production-readiness checks and optionally persist the audit run.
     */
    public function runReadinessAudit(?User $actor = null, bool $persist = true): array
    {
        $nowIso = now()->toIso8601String();
        $checks = [];

        // 1. APP_ENV === production
        $appEnv = (string) config('app.env');
        $checks[] = [
            'id' => 'app_env_production',
            'category' => 'environment',
            'title' => 'Application Environment (APP_ENV)',
            'status' => $appEnv === 'production' ? self::STATUS_PASS : self::STATUS_FAIL,
            'severity' => 'critical',
            'description' => $appEnv === 'production'
                ? 'অ্যাপ্লিকেশনটি production পরিবেশে কনফিগার করা আছে।'
                : "অ্যাপ্লিকেশনটি বর্তমানে '{$appEnv}' পরিবেশে চলছে (production নয়)।",
            'remediation' => 'প্রোডাকশন সার্ভারে .env ফাইলে APP_ENV=production সেট করুন এবং php artisan config:cache চালান।',
            'checked_at' => $nowIso,
        ];

        // 2. APP_DEBUG === false
        $appDebug = (bool) config('app.debug');
        $checks[] = [
            'id' => 'app_debug_disabled',
            'category' => 'security',
            'title' => 'Debug Mode Disabled (APP_DEBUG)',
            'status' => ! $appDebug ? self::STATUS_PASS : self::STATUS_FAIL,
            'severity' => 'critical',
            'description' => ! $appDebug
                ? 'APP_DEBUG=false সক্রিয় রয়েছে; স্ট্যাক ট্রেস এবং সংবেদনশীল ভেরিয়েবল গোপন আছে।'
                : 'সতর্কতা: APP_DEBUG=true সক্রিয় রয়েছে, যা ত্রুটির সময় স্ট্যাক ট্রেস ও সংবেদনশীল কনফিগারেশন ফাঁস করতে পারে।',
            'remediation' => '.env ফাইলে APP_DEBUG=false নিশ্চিত করুন।',
            'checked_at' => $nowIso,
        ];

        // 3. APP_KEY strength & presence (NEVER expose the key itself)
        $rawKey = (string) config('app.key');
        $cipher = (string) config('app.cipher', 'AES-256-CBC');
        $keyValid = false;
        if ($rawKey !== '') {
            $decodedKey = str_starts_with($rawKey, 'base64:')
                ? base64_decode(substr($rawKey, 7), true)
                : $rawKey;
            $keyValid = is_string($decodedKey) && strlen($decodedKey) === 32 && ! str_contains($rawKey, 'dummykey');
        }
        $checks[] = [
            'id' => 'app_key_valid',
            'category' => 'cryptography',
            'title' => 'Application Encryption Key (APP_KEY)',
            'status' => $keyValid ? self::STATUS_PASS : self::STATUS_FAIL,
            'severity' => 'critical',
            'description' => $keyValid
                ? "২৫৬-বিট এনক্রিপশন কী ({$cipher}) সঠিকভাবে সংজ্ঞায়িত রয়েছে (কী-এর মান গোপন রাখা হয়েছে)।"
                : 'APP_KEY অনুপস্থিত, দুর্বল অথবা ৩২-বাইট দৈর্ঘ্যের নয়।',
            'remediation' => 'php artisan key:generate চালিয়ে শক্তিশালী ২৫৬-বিট কী তৈরি করুন।',
            'checked_at' => $nowIso,
        ];

        // 4. HTTPS enforcement & APP_URL
        $appUrl = (string) config('app.url', '');
        $isHttpsUrl = str_starts_with(strtolower($appUrl), 'https://');
        $checks[] = [
            'id' => 'https_enforcement',
            'category' => 'network',
            'title' => 'HTTPS Enforcement & Canonical URL',
            'status' => $isHttpsUrl ? self::STATUS_PASS : ($appEnv === 'production' ? self::STATUS_FAIL : self::STATUS_WARNING),
            'severity' => 'high',
            'description' => $isHttpsUrl
                ? 'APP_URL-এ HTTPS প্রোটোকল সক্রিয় রয়েছে।'
                : 'APP_URL বর্তমানে HTTPS প্রোটোকল ব্যবহার করছে না।',
            'remediation' => 'APP_URL=https://... এবং ট্রাস্টেড প্রক্সি TLS টার্মিনেশন কনফিগার করুন।',
            'checked_at' => $nowIso,
        ];

        // 5. Secure Cookie Attributes (Secure, HttpOnly, SameSite)
        $httpOnly = (bool) config('session.http_only', true);
        $sameSite = strtolower((string) config('session.same_site', 'lax'));
        $secureCookie = (bool) config('session.secure', false);
        $cookieStatus = self::STATUS_PASS;
        if (! $httpOnly || ! in_array($sameSite, ['lax', 'strict'], true)) {
            $cookieStatus = self::STATUS_FAIL;
        } elseif (! $secureCookie) {
            $cookieStatus = $appEnv === 'production' ? self::STATUS_FAIL : self::STATUS_WARNING;
        }
        $checks[] = [
            'id' => 'cookie_security_attributes',
            'category' => 'session',
            'title' => 'Session Cookie Security (Secure, HttpOnly, SameSite)',
            'status' => $cookieStatus,
            'severity' => 'high',
            'description' => sprintf(
                'HttpOnly=%s, SameSite=%s, Secure=%s কনফিগার করা আছে।',
                $httpOnly ? 'true' : 'false',
                $sameSite ?: 'none',
                $secureCookie ? 'true' : 'false'
            ),
            'remediation' => 'প্রোডাকশনে SESSION_SECURE_COOKIE=true, SESSION_HTTP_ONLY=true এবং SESSION_SAME_SITE=lax বা strict সেট করুন।',
            'checked_at' => $nowIso,
        ];

        // 6. Session Driver, Expiration & Storage
        $sessionDriver = (string) config('session.driver');
        $sessionLifetime = (int) config('session.lifetime', 120);
        $sessionEncrypted = (bool) config('session.encrypt', false);
        $prodSessionDrivers = ['redis', 'database'];
        $sessionStatus = in_array($sessionDriver, $prodSessionDrivers, true) && $sessionLifetime <= 240
            ? ($sessionEncrypted ? self::STATUS_PASS : self::STATUS_WARNING)
            : ($appEnv === 'production' ? self::STATUS_FAIL : self::STATUS_WARNING);

        $checks[] = [
            'id' => 'session_storage_and_rotation',
            'category' => 'session',
            'title' => 'Session Storage, Lifetime & Encryption',
            'status' => $sessionStatus,
            'severity' => 'medium',
            'description' => "Session driver '{$sessionDriver}', lifetime {$sessionLifetime} মিনিট, encrypt=".($sessionEncrypted ? 'true' : 'false').'।',
            'remediation' => 'প্রোডাকশনে SESSION_DRIVER=redis বা database এবং SESSION_ENCRYPT=true ব্যবহার করুন।',
            'checked_at' => $nowIso,
        ];

        // 7. Database Connection Health & Least-Privilege Account
        $dbDriver = (string) config('database.default');
        $dbUsername = (string) config("database.connections.{$dbDriver}.username", '');
        $dbHealthy = false;
        try {
            DB::select('SELECT 1');
            $dbHealthy = true;
        } catch (Throwable) {
            $dbHealthy = false;
        }

        $dbStatus = self::STATUS_PASS;
        $dbDesc = "ডাটাবেজ সংযোগ ({$dbDriver}) সচল রয়েছে।";
        if (! $dbHealthy) {
            $dbStatus = self::STATUS_FAIL;
            $dbDesc = 'ডাটাবেজ সংযোগ ব্যর্থ হয়েছে।';
        } elseif ($dbDriver === 'sqlite' && $appEnv === 'production') {
            $dbStatus = self::STATUS_WARNING;
            $dbDesc = 'প্রোডাকশন পরিবেশে SQLite ব্যবহৃত হচ্ছে; উচ্চ কনকারেন্সির জন্য MySQL 8+ সুপারিশকৃত।';
        } elseif (in_array(strtolower($dbUsername), ['root', 'admin', 'sa'], true)) {
            $dbStatus = $appEnv === 'production' ? self::STATUS_FAIL : self::STATUS_WARNING;
            $dbDesc = 'ডাটাবেজ সংযোগে উচ্চ-প্রিভিলেজড (root/admin) অ্যাকাউন্ট ব্যবহৃত হচ্ছে।';
        }

        $checks[] = [
            'id' => 'database_least_privilege_and_health',
            'category' => 'database',
            'title' => 'Database Connectivity & Least-Privilege Credentials',
            'status' => $dbStatus,
            'severity' => 'critical',
            'description' => $dbDesc,
            'remediation' => 'প্রোডাকশনে শুধুমাত্র প্রয়োজনীয় DML/DDL পারমিশনসহ ডেডিকেটেড ডাটাবেজ ইউজার ব্যবহার করুন (root ব্যবহার করবেন না)।',
            'checked_at' => $nowIso,
        ];

        // 8. Queue & Cache Configuration
        $queueDriver = (string) config('queue.default');
        $cacheStore = (string) config('cache.default');
        $queueCacheOk = ! in_array($queueDriver, ['sync', 'null'], true) && ! in_array($cacheStore, ['array', 'null'], true);
        $checks[] = [
            'id' => 'queue_and_cache_drivers',
            'category' => 'infrastructure',
            'title' => 'Queue & Cache Production Drivers',
            'status' => $queueCacheOk ? self::STATUS_PASS : ($appEnv === 'production' ? self::STATUS_FAIL : self::STATUS_WARNING),
            'severity' => 'high',
            'description' => "Queue driver='{$queueDriver}', Cache store='{$cacheStore}'।",
            'remediation' => 'প্রোডাকশনে QUEUE_CONNECTION=redis (Horizon) অথবা database এবং CACHE_STORE=redis সেট করুন।',
            'checked_at' => $nowIso,
        ];

        // 9. Mail Configuration
        $mailMailer = (string) config('mail.default');
        $mailOk = ! in_array($mailMailer, ['log', 'array'], true);
        $checks[] = [
            'id' => 'mail_transport_security',
            'category' => 'infrastructure',
            'title' => 'Production Mail Transport',
            'status' => $mailOk ? self::STATUS_PASS : ($appEnv === 'production' ? self::STATUS_FAIL : self::STATUS_WARNING),
            'severity' => 'medium',
            'description' => "Mail driver বর্তমানে '{$mailMailer}' হিসেবে সেট করা আছে।",
            'remediation' => 'প্রোডাকশন অ্যালার্ট ও ওটিপি ডেলিভারির জন্য ভেরিফাইড SMTP/SES/Postmark মেইলার কনফিগার করুন।',
            'checked_at' => $nowIso,
        ];

        // 10. Rate Limiting Registration
        $hasAuthLimiter = RateLimiter::limiter('auth') !== null || RateLimiter::limiter('api') !== null;
        $checks[] = [
            'id' => 'rate_limiting_active',
            'category' => 'security',
            'title' => 'Brute-Force & API Rate Limiting',
            'status' => $hasAuthLimiter ? self::STATUS_PASS : self::STATUS_WARNING,
            'severity' => 'high',
            'description' => $hasAuthLimiter
                ? 'লগইন, টু-ফ্যাক্টর এবং এপিআই রেট লিমিটার সক্রিয় রয়েছে।'
                : 'নেমড রেট লিমিটার নিবন্ধিত পাওয়া যায়নি।',
            'remediation' => 'AppServiceProvider-এ auth, admin-auth এবং api রেট লিমিটার সচল রাখুন।',
            'checked_at' => $nowIso,
        ];

        // 11. Dependency Lockfile Consistency & Runtime Version
        $lockExists = File::exists(base_path('composer.lock'));
        $phpSatisfied = version_compare(PHP_VERSION, '8.3.0', '>=');
        $checks[] = [
            'id' => 'dependency_lockfile_integrity',
            'category' => 'dependencies',
            'title' => 'Dependency Lockfile & PHP Runtime Compatibility',
            'status' => ($lockExists && $phpSatisfied) ? self::STATUS_PASS : self::STATUS_FAIL,
            'severity' => 'high',
            'description' => sprintf(
                'composer.lock %s এবং PHP %s রানটাইম সচল।',
                $lockExists ? 'উপস্থিত' : 'অনুপস্থিত',
                PHP_VERSION
            ),
            'remediation' => 'composer install --no-dev --prefer-dist --classmap-authoritative ব্যবহার করে ডিপেন্ডেন্সি লকফাইল থেকে ইন্সটল করুন।',
            'checked_at' => $nowIso,
        ];

        // 12. Filesystem Permissions & Public .env Exposure
        $storageWritable = is_writable(storage_path()) && is_writable(storage_path('logs'));
        $cacheWritable = is_writable(base_path('bootstrap/cache'));
        $publicEnvExposed = File::exists(public_path('.env')) || File::exists(public_path('phpinfo.php'));
        $fsStatus = ($storageWritable && $cacheWritable && ! $publicEnvExposed) ? self::STATUS_PASS : self::STATUS_FAIL;
        $checks[] = [
            'id' => 'filesystem_permissions_and_exposure',
            'category' => 'security',
            'title' => 'Storage Permissions & Absence of Exposed .env Files',
            'status' => $fsStatus,
            'severity' => 'critical',
            'description' => $fsStatus === self::STATUS_PASS
                ? 'storage/ এবং bootstrap/cache/ ডিরেক্টরি লিখনযোগ্য এবং public/ ডিরেক্টরিতে কোনো .env ফাইল উন্মুক্ত নেই।'
                : 'ফাইল সিস্টেম পারমিশন ত্রুটি অথবা public/ ডিরেক্টরিতে সংবেদনশীল ফাইল পাওয়া গেছে।',
            'remediation' => 'storage ও bootstrap/cache ডিরেক্টরিতে সঠিক ওয়েব সার্ভার পারমিশন দিন এবং public/ থেকে সংবেদনশীল ফাইল মুছে ফেলুন।',
            'checked_at' => $nowIso,
        ];

        // 13. Scheduler Heartbeat & Disk Capacity
        $lastHeartbeat = Cache::get('scheduler_last_heartbeat_at');
        $heartbeatFresh = false;
        if ($lastHeartbeat) {
            try {
                $heartbeatFresh = abs(Carbon::parse($lastHeartbeat)->diffInMinutes(now())) <= 10;
            } catch (Throwable) {
                $heartbeatFresh = false;
            }
        }
        $diskFree = @disk_free_space(storage_path());
        $diskTotal = @disk_total_space(storage_path());
        $diskUsedPct = ($diskFree && $diskTotal && $diskTotal > 0)
            ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1)
            : null;

        $schedulerStatus = self::STATUS_PASS;
        if ($diskUsedPct !== null && $diskUsedPct > 95) {
            $schedulerStatus = self::STATUS_FAIL;
        } elseif (! $heartbeatFresh || ($diskUsedPct !== null && $diskUsedPct > 85)) {
            $schedulerStatus = self::STATUS_WARNING;
        }

        $checks[] = [
            'id' => 'scheduler_heartbeat_and_disk',
            'category' => 'operations',
            'title' => 'Scheduler Heartbeat & Storage Disk Capacity',
            'status' => $schedulerStatus,
            'severity' => 'medium',
            'description' => sprintf(
                'Scheduler heartbeat: %s; Disk usage: %s।',
                $heartbeatFresh ? 'সচল ('.$lastHeartbeat.')' : 'সাম্প্রতিক হার্টবিট পাওয়া যায়নি',
                $diskUsedPct !== null ? "{$diskUsedPct}%" : 'পরিমাপযোগ্য নয়'
            ),
            'remediation' => 'সার্ভার ক্রনে * * * * * php artisan schedule:run সক্রিয় করুন এবং ডিস্ক ব্যবহার ৮৫%-এর নিচে রাখুন।',
            'checked_at' => $nowIso,
        ];

        // 14. Disaster Recovery & Verified Restore Drill Status
        $drStatus = $this->backupService->getDisasterRecoveryStatus();
        $drCheckStatus = $drStatus['dr_ready']
            ? self::STATUS_PASS
            : ($drStatus['compliance']['has_passed_restore_drill'] ? self::STATUS_WARNING : self::STATUS_FAIL);

        $checks[] = [
            'id' => 'disaster_recovery_readiness',
            'category' => 'disaster_recovery',
            'title' => 'Verified Backups & Isolated Restore Drill Compliance',
            'status' => $drCheckStatus,
            'severity' => 'critical',
            'description' => $drStatus['dr_ready']
                ? 'সর্বশেষ এনক্রিপ্টেড ব্যাকআপ এবং আইসোলেটেড রিস্টোর ড্রিল উভয়ই সফলভাবে যাচাইকৃত (RPO/RTO Compliant)।'
                : 'ডিজাস্টার রিকভারি সম্পূর্ণ প্রস্তুত নয়: ভেরিফাইড ব্যাকআপ অথবা সাম্প্রতিক আইসোলেটেড রিস্টোর ড্রিল প্রয়োজন।',
            'remediation' => 'php artisan admin:backup-verify --drill চালিয়ে বিচ্ছিন্ন পরিবেশে রিস্টোর ড্রিল সম্পন্ন করুন।',
            'checked_at' => $nowIso,
        ];

        // 15. Tamper-Evident Audit Chain Integrity
        $auditIntegrity = $this->auditService->verifyIntegrity(150);
        $checks[] = [
            'id' => 'audit_chain_integrity',
            'category' => 'compliance',
            'title' => 'Tamper-Evident SHA-256 Audit Chain Verification',
            'status' => $auditIntegrity['verified'] ? self::STATUS_PASS : self::STATUS_FAIL,
            'severity' => 'critical',
            'description' => $auditIntegrity['message'],
            'remediation' => 'অডিট চেইন অখণ্ডতা যাচাই করুন এবং নিয়মিত স্বাক্ষরিত অডিট চেকপয়েন্ট সিল করুন।',
            'checked_at' => $nowIso,
        ];

        // Summarize counts
        $passCount = count(array_filter($checks, fn ($c) => $c['status'] === self::STATUS_PASS));
        $warningCount = count(array_filter($checks, fn ($c) => $c['status'] === self::STATUS_WARNING));
        $failCount = count(array_filter($checks, fn ($c) => $c['status'] === self::STATUS_FAIL));
        $naCount = count(array_filter($checks, fn ($c) => $c['status'] === self::STATUS_NOT_APPLICABLE));
        $criticalBlockersCount = count(array_filter(
            $checks,
            fn ($c) => $c['status'] === self::STATUS_FAIL && $c['severity'] === 'critical'
        ));

        $applicableTotal = max(1, count($checks) - $naCount);
        $readinessScore = (int) round((($passCount + ($warningCount * 0.5)) / $applicableTotal) * 100);

        $overallStatus = self::STATUS_PASS;
        if ($failCount > 0) {
            $overallStatus = self::STATUS_FAIL;
        } elseif ($warningCount > 0) {
            $overallStatus = self::STATUS_WARNING;
        }

        $runUid = 'RDY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        $runRecord = null;

        if ($persist && Schema::hasTable('production_readiness_runs')) {
            $runRecord = ProductionReadinessRun::create([
                'run_uid' => $runUid,
                'overall_status' => $overallStatus,
                'readiness_score' => $readinessScore,
                'pass_count' => $passCount,
                'warning_count' => $warningCount,
                'fail_count' => $failCount,
                'not_applicable_count' => $naCount,
                'critical_blockers_count' => $criticalBlockersCount,
                'environment' => $appEnv,
                'checks' => $checks,
                'triggered_by' => $actor?->id,
            ]);

            if ($actor) {
                AuditLog::create([
                    'user_id' => $actor->id,
                    'action' => 'admin_production_readiness_audit_executed',
                    'entity_type' => ProductionReadinessRun::class,
                    'entity_id' => $runRecord->id,
                    'new_values' => [
                        'run_uid' => $runUid,
                        'overall_status' => $overallStatus,
                        'readiness_score' => $readinessScore,
                        'fail_count' => $failCount,
                        'warning_count' => $warningCount,
                        'critical_blockers_count' => $criticalBlockersCount,
                    ],
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                ]);
            }
        }

        $history = Schema::hasTable('production_readiness_runs')
            ? ProductionReadinessRun::with('operator:id,name,email')
                ->latest('id')
                ->take(10)
                ->get()
                ->map(fn (ProductionReadinessRun $r) => [
                    'id' => $r->id,
                    'run_uid' => $r->run_uid,
                    'overall_status' => $r->overall_status,
                    'readiness_score' => $r->readiness_score,
                    'pass_count' => $r->pass_count,
                    'warning_count' => $r->warning_count,
                    'fail_count' => $r->fail_count,
                    'critical_blockers_count' => $r->critical_blockers_count,
                    'environment' => $r->environment,
                    'operator' => $r->operator?->name ?? 'System CLI',
                    'created_at' => $r->created_at?->toIso8601String(),
                ])
                ->all()
            : [];

        return [
            'run_uid' => $runUid,
            'overall_status' => $overallStatus,
            'readiness_score' => $readinessScore,
            'environment' => $appEnv,
            'summary' => [
                'total_checks' => count($checks),
                'pass_count' => $passCount,
                'warning_count' => $warningCount,
                'fail_count' => $failCount,
                'not_applicable_count' => $naCount,
                'critical_blockers_count' => $criticalBlockersCount,
            ],
            'checks' => $checks,
            'deployment_checklist' => $this->getDeploymentChecklist(),
            'recent_runs' => $history,
            'disaster_recovery' => $drStatus,
            'checked_at' => $nowIso,
        ];
    }

    /**
     * Record scheduler heartbeat so missed runs can be accurately detected.
     */
    public function recordSchedulerHeartbeat(): string
    {
        $ts = now()->toIso8601String();
        Cache::put('scheduler_last_heartbeat_at', $ts, now()->addHours(24));

        return $ts;
    }

    /**
     * Structured Pre-Deployment, Migration, Smoke-Test & Post-Deployment Checklist.
     */
    public function getDeploymentChecklist(): array
    {
        return [
            'pre_deployment' => [
                ['step' => 'Verify composer.lock consistency and run vendor/bin/pint --test', 'command' => './vendor/bin/pint --test'],
                ['step' => 'Create and verify an encrypted pre-deployment database backup', 'command' => 'php artisan jugajug:backup --type=db && php artisan admin:backup-verify --latest --drill'],
                ['step' => 'Run production readiness audit check', 'command' => 'php artisan admin:production-readiness'],
            ],
            'migration_execution' => [
                ['step' => 'Inspect pending migrations before execution', 'command' => 'php artisan migrate:status'],
                ['step' => 'Run additive migrations atomically', 'command' => 'php artisan migrate --force'],
            ],
            'smoke_tests' => [
                ['step' => 'Verify health probe endpoint (/up and /api/v1/health)', 'command' => 'curl -fsS http://127.0.0.1:8000/api/v1/health'],
                ['step' => 'Verify tamper-evident audit chain integrity', 'command' => 'php artisan admin:audit-verify'],
            ],
            'post_deployment_monitoring' => [
                ['step' => 'Gracefully restart queue workers and Horizon supervisors', 'command' => 'php artisan queue:restart'],
                ['step' => 'Monitor failed_jobs, SOC alerts, and scheduler heartbeat for 15 minutes', 'command' => 'php artisan schedule:list'],
            ],
        ];
    }
}
