<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enhance Users table for Abuse scoring and Legal Hold protection
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_legal_hold')) {
                $table->boolean('is_legal_hold')->default(false)->after('status');
            }
            if (! Schema::hasColumn('users', 'risk_score')) {
                $table->unsignedTinyInteger('risk_score')->default(0)->after('is_legal_hold'); // 0 - 100
            }
            if (! Schema::hasColumn('users', 'risk_factors')) {
                $table->json('risk_factors')->nullable()->after('risk_score');
            }
        });

        // 2. Enhance Reports table for Category, Duplicate Grouping & False Positive tracking
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'category')) {
                $table->string('category', 50)->default('general')->after('reason');
            }
            if (! Schema::hasColumn('reports', 'duplicate_of_id')) {
                $table->foreignId('duplicate_of_id')->nullable()->constrained('reports')->nullOnDelete()->after('appeal_notes');
            }
            if (! Schema::hasColumn('reports', 'is_false_positive')) {
                $table->boolean('is_false_positive')->default(false)->after('duplicate_of_id');
            }
        });

        // 3. Enhance Admin Alerts table for Deduplication, Hit Counts & Lifecycle Status
        Schema::table('admin_alerts', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_alerts', 'signature')) {
                $table->string('signature', 64)->nullable()->after('type');
                $table->index('signature');
            }
            if (! Schema::hasColumn('admin_alerts', 'hit_count')) {
                $table->unsignedInteger('hit_count')->default(1)->after('signature');
            }
            if (! Schema::hasColumn('admin_alerts', 'status')) {
                $table->string('status', 30)->default('unread')->after('hit_count'); // unread, acknowledged, resolved, suppressed
            }
            if (! Schema::hasColumn('admin_alerts', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('read_by');
            }
            if (! Schema::hasColumn('admin_alerts', 'acknowledged_by')) {
                $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete()->after('acknowledged_at');
            }
            if (! Schema::hasColumn('admin_alerts', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('acknowledged_by');
            }
        });

        // 4. Analytics Daily Aggregates (Module A)
        if (! Schema::hasTable('analytics_daily_aggregates')) {
            Schema::create('analytics_daily_aggregates', function (Blueprint $table) {
                $table->id();
                $table->date('date')->unique();
                $table->unsignedInteger('dau')->default(0);
                $table->unsignedInteger('wau')->default(0);
                $table->unsignedInteger('mau')->default(0);
                $table->unsignedInteger('registrations_count')->default(0);
                $table->unsignedInteger('posts_count')->default(0);
                $table->unsignedInteger('comments_count')->default(0);
                $table->unsignedInteger('reactions_count')->default(0);
                $table->unsignedInteger('shares_count')->default(0);
                $table->unsignedInteger('messages_count')->default(0);
                $table->unsignedInteger('reports_count')->default(0);
                $table->unsignedInteger('security_events_count')->default(0);
                $table->json('metrics')->nullable();
                $table->timestamps();

                $table->index('date');
            });
        }

        // 5. User Restrictions (Module C — Fraud, Abuse & Platform Integrity)
        if (! Schema::hasTable('user_restrictions')) {
            Schema::create('user_restrictions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('restriction_type', 40); // read_only, post_block, comment_block, message_block, full_ban, rate_limited
                $table->string('reason', 255);
                $table->unsignedTinyInteger('risk_score')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->string('override_reason', 255)->nullable();
                $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('overridden_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'is_active', 'expires_at']);
                $table->index('restriction_type');
            });
        }

        // 6. Admin Alert Deliveries (Module H — Real-Time Operations & Alert Center)
        if (! Schema::hasTable('admin_alert_deliveries')) {
            Schema::create('admin_alert_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_alert_id')->constrained('admin_alerts')->cascadeOnDelete();
                $table->string('channel', 30); // in_app, email, webhook, slack
                $table->string('recipient', 255)->nullable();
                $table->string('status', 30)->default('queued'); // queued, sent, failed
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'channel']);
            });
        }

        // 7. Platform Feature Flags (Module G — Configuration, Feature Flags & Safe Rollback)
        if (! Schema::hasTable('platform_feature_flags')) {
            Schema::create('platform_feature_flags', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->boolean('is_enabled')->default(false);
                $table->unsignedTinyInteger('rollout_percentage')->default(0); // 0 to 100%
                $table->json('target_roles')->nullable();
                $table->json('target_users')->nullable();
                $table->json('rules')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('is_enabled');
            });

            // Seed initial core feature flags
            $now = now();
            DB::table('platform_feature_flags')->insert([
                [
                    'key' => 'ai_moderation_auto_hide',
                    'name' => 'AI অটো-মডারেশন হাইড',
                    'description' => 'উচ্চ আত্মবিশ্বাসের (>=95%) ক্ষেত্রে সহিংস কন্টেন্ট স্বয়ংক্রিয়ভাবে গোপন করা',
                    'is_enabled' => true,
                    'rollout_percentage' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'advanced_abuse_throttling',
                    'name' => 'অ্যাডভান্সড অপব্যবহার থ্রটলিং',
                    'description' => 'স্প্যাম বা বট প্যাটার্ন সনাক্ত হলে সাময়িক রিকোয়েস্ট লিমিট প্রয়োগ',
                    'is_enabled' => true,
                    'rollout_percentage' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'creator_instant_payouts',
                    'name' => 'ক্রিয়েটর ইনস্ট্যান্ট পেআউট',
                    'description' => 'ভেরিফায়েড ক্রিয়েটরদের জন্য স্বয়ংক্রিয় ছোট অঙ্কের কয়েন রূপান্তর',
                    'is_enabled' => false,
                    'rollout_percentage' => 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        // 8. Audit Signed Checkpoints (Module F — Tamper-Evident Audit & Compliance)
        if (! Schema::hasTable('audit_checkpoints')) {
            Schema::create('audit_checkpoints', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('checkpoint_number')->unique();
                $table->unsignedBigInteger('record_start_id');
                $table->unsignedBigInteger('record_end_id');
                $table->unsignedInteger('record_count');
                $table->string('root_hash', 64);
                $table->text('signature');
                $table->string('key_version', 20)->default('v1');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('checkpoint_at');
                $table->timestamps();

                $table->index('checkpoint_number');
            });
        }

        // 9. Data Retention Policies (Module L — Data Governance, Privacy & Retention)
        if (! Schema::hasTable('data_retention_policies')) {
            Schema::create('data_retention_policies', function (Blueprint $table) {
                $table->id();
                $table->string('data_category', 50)->unique(); // audit_logs, security_events, failed_logins, notifications, chat_messages, soft_deleted_users
                $table->string('name', 120);
                $table->text('description')->nullable();
                $table->unsignedInteger('retention_days')->default(365);
                $table->string('action', 30)->default('hard_delete'); // soft_delete, hard_delete, anonymize
                $table->boolean('is_enabled')->default(true);
                $table->timestamp('last_run_at')->nullable();
                $table->unsignedInteger('last_records_affected')->default(0);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });

            // Seed default compliant enterprise retention policies
            $now = now();
            DB::table('data_retention_policies')->insert([
                [
                    'data_category' => 'audit_logs',
                    'name' => 'অডিট লগ ধারণ নীতি',
                    'description' => 'নিয়ন্ত্রক প্রয়োজনীয়তার জন্য অডিট লগ ন্যূনতম ৩৬৫ দিন সংরক্ষিত থাকবে।',
                    'retention_days' => 365,
                    'action' => 'hard_delete',
                    'is_enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'data_category' => 'security_events',
                    'name' => 'নিরাপত্তা ইভেন্ট লগ',
                    'description' => 'সমাধানকৃত সাধারণ সিকিউরিটি ইভেন্ট ৯০ দিন সংরক্ষণ করা হবে।',
                    'retention_days' => 90,
                    'action' => 'hard_delete',
                    'is_enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'data_category' => 'failed_logins',
                    'name' => 'ব্যর্থ লগইন প্রচেষ্টা',
                    'description' => 'ব্রুট-ফোর্স বিশ্লেষণের জন্য ব্যর্থ লগইন ৩০ দিন সংরক্ষণ।',
                    'retention_days' => 30,
                    'action' => 'hard_delete',
                    'is_enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'data_category' => 'notifications',
                    'name' => 'পুরনো নোটিফিকেশন ক্লিনআপ',
                    'description' => 'পঠিত নোটিফিকেশন ৬০ দিন পর অপসারিত হবে।',
                    'retention_days' => 60,
                    'action' => 'hard_delete',
                    'is_enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'data_category' => 'soft_deleted_users',
                    'name' => 'বাতিল অ্যাকাউন্ট পার্মানেন্ট রিমুভাল',
                    'description' => 'আইনি স্থগিতাদেশ (Legal Hold) ব্যতিরেকে স্থগিত অ্যাকাউন্ট ৩০ দিন পর পার্মানেন্ট মুছে ফেলা।',
                    'retention_days' => 30,
                    'action' => 'anonymize',
                    'is_enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        // 10. Privacy & GDPR Requests (Module L — Data Governance, Privacy & Retention)
        if (! Schema::hasTable('privacy_requests')) {
            Schema::create('privacy_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_number', 40)->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('request_type', 30); // export, erasure, rectification, objection
                $table->string('status', 35)->default('pending'); // pending, processing, completed, rejected, blocked_by_legal_hold
                $table->text('reason')->nullable();
                $table->string('export_file_path', 255)->nullable();
                $table->timestamp('export_expires_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'request_type']);
            });
        }

        // 11. Admin Elevations (Module E — Enterprise RBAC & ABAC Time-Limited Privileges)
        if (! Schema::hasTable('admin_elevations')) {
            Schema::create('admin_elevations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('elevated_role', 50); // e.g. SUPER_ADMIN, SOC_LEAD, AUDITOR
                $table->json('permissions')->nullable();
                $table->string('reason', 255);
                $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('expires_at');
                $table->timestamp('revoked_at')->nullable();
                $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['user_id', 'expires_at', 'revoked_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('admin_elevations');
        Schema::dropIfExists('privacy_requests');
        Schema::dropIfExists('data_retention_policies');
        Schema::dropIfExists('audit_checkpoints');
        Schema::dropIfExists('platform_feature_flags');
        Schema::dropIfExists('admin_alert_deliveries');
        Schema::dropIfExists('user_restrictions');
        Schema::dropIfExists('analytics_daily_aggregates');

        if (Schema::hasTable('admin_alerts')) {
            Schema::table('admin_alerts', function (Blueprint $table) {
                if (Schema::hasColumn('admin_alerts', 'assigned_to')) {
                    $table->dropForeign(['assigned_to']);
                }
                if (Schema::hasColumn('admin_alerts', 'acknowledged_by')) {
                    $table->dropForeign(['acknowledged_by']);
                }
                if (Schema::hasColumn('admin_alerts', 'signature')) {
                    $table->dropIndex(['signature']);
                }
                $colsToDrop = array_filter(
                    ['assigned_to', 'acknowledged_by', 'acknowledged_at', 'status', 'hit_count', 'signature'],
                    fn ($col) => Schema::hasColumn('admin_alerts', $col)
                );
                if (! empty($colsToDrop)) {
                    $table->dropColumn($colsToDrop);
                }
            });
        }

        if (Schema::hasTable('reports')) {
            Schema::table('reports', function (Blueprint $table) {
                if (Schema::hasColumn('reports', 'duplicate_of_id')) {
                    $table->dropForeign(['duplicate_of_id']);
                }
                $colsToDrop = array_filter(
                    ['is_false_positive', 'duplicate_of_id', 'category'],
                    fn ($col) => Schema::hasColumn('reports', $col)
                );
                if (! empty($colsToDrop)) {
                    $table->dropColumn($colsToDrop);
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $colsToDrop = array_filter(
                    ['risk_factors', 'risk_score', 'is_legal_hold'],
                    fn ($col) => Schema::hasColumn('users', $col)
                );
                if (! empty($colsToDrop)) {
                    $table->dropColumn($colsToDrop);
                }
            });
        }

        Schema::enableForeignKeyConstraints();
    }
};
