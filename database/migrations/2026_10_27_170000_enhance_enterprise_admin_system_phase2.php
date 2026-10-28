<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enhance Reports Table for AI-Assisted Moderation & Appeals
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'ai_flagged')) {
                $table->boolean('ai_flagged')->default(false)->after('status');
            }
            if (! Schema::hasColumn('reports', 'ai_confidence')) {
                $table->decimal('ai_confidence', 5, 2)->nullable()->after('ai_flagged');
            }
            if (! Schema::hasColumn('reports', 'ai_reason')) {
                $table->string('ai_reason', 150)->nullable()->after('ai_confidence');
            }
            if (! Schema::hasColumn('reports', 'ai_evidence')) {
                $table->json('ai_evidence')->nullable()->after('ai_reason');
            }
            if (! Schema::hasColumn('reports', 'priority')) {
                $table->string('priority', 20)->default('medium')->after('ai_evidence');
            }
            if (! Schema::hasColumn('reports', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('priority');
            }
            if (! Schema::hasColumn('reports', 'appeal_status')) {
                $table->string('appeal_status', 30)->default('none')->after('assigned_to'); // none, requested, under_review, upheld, overturned
            }
            if (! Schema::hasColumn('reports', 'appeal_notes')) {
                $table->text('appeal_notes')->nullable()->after('appeal_status');
            }
        });

        // 2. Enhance Security Events Table for SOC Incident Management
        Schema::table('security_events', function (Blueprint $table) {
            if (! Schema::hasColumn('security_events', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('resolved');
            }
            if (! Schema::hasColumn('security_events', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('assigned_to');
            }
            if (! Schema::hasColumn('security_events', 'resolved_by')) {
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete()->after('internal_notes');
            }
            if (! Schema::hasColumn('security_events', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            }
        });

        // 3. Enhance Audit Logs for Tamper-Evident Hash Chaining
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'checksum')) {
                $table->string('checksum', 64)->nullable()->after('user_agent');
            }
            if (! Schema::hasColumn('audit_logs', 'previous_hash')) {
                $table->string('previous_hash', 64)->nullable()->after('checksum');
            }
        });

        // 4. Create Platform Setting Histories Table for Instant Rollback
        if (! Schema::hasTable('platform_setting_histories')) {
            Schema::create('platform_setting_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('platform_setting_id')->constrained('platform_settings')->cascadeOnDelete();
                $table->string('key', 100);
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reason', 255)->nullable();
                $table->timestamps();

                $table->index(['key', 'created_at']);
            });
        }

        // 5. Create Admin Alerts Table for Real-Time Notification Center
        if (! Schema::hasTable('admin_alerts')) {
            Schema::create('admin_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('type', 50)->default('system'); // moderation, security, system, financial, registration
                $table->string('severity', 20)->default('info'); // info, warning, danger, critical
                $table->string('title', 200);
                $table->text('message');
                $table->json('data')->nullable();
                $table->string('link', 255)->nullable();
                $table->timestamp('read_at')->nullable();
                $table->foreignId('read_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['severity', 'read_at']);
                $table->index('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_alerts');
        Schema::dropIfExists('platform_setting_histories');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['checksum', 'previous_hash']);
        });

        Schema::table('security_events', function (Blueprint $table) {
            $table->dropColumn(['assigned_to', 'internal_notes', 'resolved_by', 'resolved_at']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn([
                'ai_flagged',
                'ai_confidence',
                'ai_reason',
                'ai_evidence',
                'priority',
                'assigned_to',
                'appeal_status',
                'appeal_notes',
            ]);
        });
    }
};
