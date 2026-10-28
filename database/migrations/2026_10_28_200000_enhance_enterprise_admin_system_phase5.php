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
        // 1. Enhance Backups table with full Disaster Recovery inventory metadata
        if (Schema::hasTable('backups')) {
            Schema::table('backups', function (Blueprint $table) {
                if (! Schema::hasColumn('backups', 'backup_uid')) {
                    $table->string('backup_uid', 64)->nullable()->unique()->after('id');
                }
                if (! Schema::hasColumn('backups', 'started_at')) {
                    $table->timestamp('started_at')->nullable()->after('encrypted');
                }
                if (! Schema::hasColumn('backups', 'db_engine')) {
                    $table->string('db_engine', 32)->nullable()->after('completed_at');
                }
                if (! Schema::hasColumn('backups', 'db_version')) {
                    $table->string('db_version', 64)->nullable()->after('db_engine');
                }
                if (! Schema::hasColumn('backups', 'app_version')) {
                    $table->string('app_version', 64)->nullable()->after('db_version');
                }
                if (! Schema::hasColumn('backups', 'key_version')) {
                    $table->string('key_version', 32)->default('v1')->after('app_version');
                }
                if (! Schema::hasColumn('backups', 'cipher')) {
                    $table->string('cipher', 40)->default('AES-256-CBC+HMAC-SHA256')->after('key_version');
                }
                if (! Schema::hasColumn('backups', 'manifest')) {
                    $table->json('manifest')->nullable()->after('cipher');
                }
                if (! Schema::hasColumn('backups', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->index()->after('manifest');
                }
                if (! Schema::hasColumn('backups', 'verification_status')) {
                    $table->string('verification_status', 32)->default('unverified')->index()->after('expires_at');
                }
                if (! Schema::hasColumn('backups', 'last_verified_at')) {
                    $table->timestamp('last_verified_at')->nullable()->after('verification_status');
                }
                if (! Schema::hasColumn('backups', 'restore_drill_status')) {
                    $table->string('restore_drill_status', 32)->default('not_tested')->index()->after('last_verified_at');
                }
                if (! Schema::hasColumn('backups', 'last_restore_drill_at')) {
                    $table->timestamp('last_restore_drill_at')->nullable()->after('restore_drill_status');
                }
                if (! Schema::hasColumn('backups', 'error_category')) {
                    $table->string('error_category', 64)->nullable()->after('error_message');
                }
                if (! Schema::hasColumn('backups', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('error_category');
                }
            });
        }

        // 2. Create Backup Restore Drills table for isolated recovery verification
        if (! Schema::hasTable('backup_restore_drills')) {
            Schema::create('backup_restore_drills', function (Blueprint $table) {
                $table->id();
                $table->string('drill_uid', 64)->unique();
                $table->foreignId('backup_id')->constrained('backups')->cascadeOnDelete();
                $table->string('status', 32)->index(); // running, passed, failed
                $table->string('isolated_env_path', 255)->nullable();
                $table->boolean('isolated_resources_cleaned')->default(false);
                $table->boolean('checksum_verified')->default(false);
                $table->boolean('decryption_verified')->default(false);
                $table->boolean('schema_verified')->default(false);
                $table->boolean('relationships_verified')->default(false);
                $table->unsignedInteger('restored_tables_count')->default(0);
                $table->unsignedBigInteger('restored_records_count')->default(0);
                $table->unsignedInteger('duration_ms')->default(0);
                $table->json('verification_report')->nullable();
                $table->text('error_message')->nullable();
                $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. Create Production Readiness Runs table for historical configuration audits
        if (! Schema::hasTable('production_readiness_runs')) {
            Schema::create('production_readiness_runs', function (Blueprint $table) {
                $table->id();
                $table->string('run_uid', 64)->unique();
                $table->string('overall_status', 32)->index(); // pass, warning, fail
                $table->unsignedTinyInteger('readiness_score')->default(0);
                $table->unsignedSmallInteger('pass_count')->default(0);
                $table->unsignedSmallInteger('warning_count')->default(0);
                $table->unsignedSmallInteger('fail_count')->default(0);
                $table->unsignedSmallInteger('not_applicable_count')->default(0);
                $table->unsignedSmallInteger('critical_blockers_count')->default(0);
                $table->string('environment', 32);
                $table->json('checks');
                $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 4. Enhance Security Events table for full Incident Response lifecycle
        if (Schema::hasTable('security_events')) {
            Schema::table('security_events', function (Blueprint $table) {
                if (! Schema::hasColumn('security_events', 'status')) {
                    $table->string('status', 32)->default('open')->index()->after('resolved'); // open, acknowledged, investigating, escalated, resolved, reopened
                }
                if (! Schema::hasColumn('security_events', 'acknowledged_at')) {
                    $table->timestamp('acknowledged_at')->nullable()->after('resolved_at');
                }
                if (! Schema::hasColumn('security_events', 'acknowledged_by')) {
                    $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete()->after('acknowledged_at');
                }
                if (! Schema::hasColumn('security_events', 'escalated_at')) {
                    $table->timestamp('escalated_at')->nullable()->after('acknowledged_by');
                }
                if (! Schema::hasColumn('security_events', 'reopened_at')) {
                    $table->timestamp('reopened_at')->nullable()->after('escalated_at');
                }
                if (! Schema::hasColumn('security_events', 'reopened_by')) {
                    $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete()->after('reopened_at');
                }
                if (! Schema::hasColumn('security_events', 'evidence_refs')) {
                    $table->json('evidence_refs')->nullable()->after('details');
                }
                if (! Schema::hasColumn('security_events', 'timeline')) {
                    $table->json('timeline')->nullable()->after('evidence_refs');
                }
            });
        }

        // 5. Seed configurable Disaster Recovery RPO / RTO objectives in platform_settings if table exists
        if (Schema::hasTable('platform_settings')) {
            $now = now();
            $drSettings = [
                ['key' => 'dr_rpo_minutes', 'value' => '60', 'type' => 'integer', 'group' => 'disaster_recovery', 'description' => 'Recovery Point Objective (RPO) in minutes'],
                ['key' => 'dr_rto_minutes', 'value' => '30', 'type' => 'integer', 'group' => 'disaster_recovery', 'description' => 'Recovery Time Objective (RTO) in minutes'],
                ['key' => 'dr_backup_frequency_hours', 'value' => '1', 'type' => 'integer', 'group' => 'disaster_recovery', 'description' => 'Automated backup frequency in hours'],
                ['key' => 'dr_retention_days', 'value' => '30', 'type' => 'integer', 'group' => 'disaster_recovery', 'description' => 'Encrypted backup retention duration in days'],
                ['key' => 'dr_restore_drill_frequency_days', 'value' => '7', 'type' => 'integer', 'group' => 'disaster_recovery', 'description' => 'Isolated restore verification drill frequency in days'],
                ['key' => 'security_require_admin_mfa', 'value' => 'false', 'type' => 'boolean', 'group' => 'security', 'description' => 'Enforce multi-factor authentication for privileged operations'],
            ];

            foreach ($drSettings as $setting) {
                $exists = DB::table('platform_settings')->where('key', $setting['key'])->exists();
                if (! $exists) {
                    DB::table('platform_settings')->insert(array_merge($setting, [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]));
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('production_readiness_runs');
        Schema::dropIfExists('backup_restore_drills');

        if (Schema::hasTable('security_events')) {
            Schema::table('security_events', function (Blueprint $table) {
                if (Schema::hasColumn('security_events', 'acknowledged_by')) {
                    $table->dropForeign(['acknowledged_by']);
                }
                if (Schema::hasColumn('security_events', 'reopened_by')) {
                    $table->dropForeign(['reopened_by']);
                }
                if (Schema::hasColumn('security_events', 'status')) {
                    $table->dropIndex(['status']);
                }
                $colsToDrop = array_filter(
                    ['timeline', 'evidence_refs', 'reopened_by', 'reopened_at', 'escalated_at', 'acknowledged_by', 'acknowledged_at', 'status'],
                    fn ($col) => Schema::hasColumn('security_events', $col)
                );
                if (! empty($colsToDrop)) {
                    $table->dropColumn($colsToDrop);
                }
            });
        }

        if (Schema::hasTable('backups')) {
            Schema::table('backups', function (Blueprint $table) {
                if (Schema::hasColumn('backups', 'created_by')) {
                    $table->dropForeign(['created_by']);
                }
                if (Schema::hasColumn('backups', 'backup_uid')) {
                    $table->dropUnique(['backup_uid']);
                }
                if (Schema::hasColumn('backups', 'expires_at')) {
                    $table->dropIndex(['expires_at']);
                }
                if (Schema::hasColumn('backups', 'verification_status')) {
                    $table->dropIndex(['verification_status']);
                }
                if (Schema::hasColumn('backups', 'restore_drill_status')) {
                    $table->dropIndex(['restore_drill_status']);
                }
                $colsToDrop = array_filter(
                    [
                        'created_by',
                        'error_category',
                        'last_restore_drill_at',
                        'restore_drill_status',
                        'last_verified_at',
                        'verification_status',
                        'expires_at',
                        'manifest',
                        'cipher',
                        'key_version',
                        'app_version',
                        'db_version',
                        'db_engine',
                        'started_at',
                        'backup_uid',
                    ],
                    fn ($col) => Schema::hasColumn('backups', $col)
                );
                if (! empty($colsToDrop)) {
                    $table->dropColumn($colsToDrop);
                }
            });
        }

        Schema::enableForeignKeyConstraints();
    }
};
