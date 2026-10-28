<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations (Phase 6: Zero-Trust Security, High Availability, Compliance & Autonomous Operations).
     */
    public function up(): void
    {
        if (! Schema::hasTable('zero_trust_policies')) {
            Schema::create('zero_trust_policies', function (Blueprint $table) {
                $table->id();
                $table->string('policy_code', 64)->unique();
                $table->string('policy_key', 64)->nullable()->index();
                $table->string('name', 160);
                $table->unsignedInteger('version')->default(1);
                $table->string('operation_key', 120)->index();
                $table->string('effect', 20)->default('allow'); // allow, deny
                $table->string('risk_level', 20)->default('medium'); // low, medium, high, critical
                $table->json('required_permissions')->nullable();
                $table->boolean('require_mfa')->default(false);
                $table->boolean('require_jit')->default(false);
                $table->unsignedInteger('require_fresh_auth_minutes')->nullable();
                $table->boolean('require_dual_approval')->default(false);
                $table->boolean('prevent_self_approval')->default(true);
                $table->unsignedInteger('max_risk_score')->default(75);
                $table->boolean('require_trusted_device')->default(false);
                $table->json('conditions')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->json('change_history')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('zero_trust_access_logs')) {
            Schema::create('zero_trust_access_logs', function (Blueprint $table) {
                $table->id();
                $table->string('evaluation_uid', 64)->unique();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('policy_code', 64)->index();
                $table->unsignedInteger('policy_version')->default(1);
                $table->string('operation', 120)->index();
                $table->string('resource_type', 120)->nullable();
                $table->string('resource_id', 64)->nullable();
                $table->string('decision', 32)->index(); // allow, deny, step_up_required, approval_required
                $table->unsignedInteger('risk_score')->default(0);
                $table->json('signals')->nullable();
                $table->string('reason_code', 64)->index();
                $table->string('reason_message', 500)->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->timestamp('evaluated_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('threat_detection_rules')) {
            Schema::create('threat_detection_rules', function (Blueprint $table) {
                $table->id();
                $table->string('rule_code', 64)->unique();
                $table->string('rule_id', 64)->nullable()->index();
                $table->string('name', 160);
                $table->unsignedInteger('version')->default(1);
                $table->string('data_source', 80);
                $table->string('event_pattern', 120);
                $table->unsignedInteger('threshold_count')->default(5);
                $table->unsignedInteger('window_minutes')->default(15);
                $table->string('severity', 24)->default('high')->index();
                $table->unsignedInteger('suppression_minutes')->default(30);
                $table->string('recommended_response', 64)->default('create_incident');
                $table->boolean('requires_human_approval')->default(false);
                $table->boolean('is_enabled')->default(true)->index();
                $table->unsignedInteger('false_positive_count')->default(0);
                $table->unsignedInteger('true_positive_count')->default(0);
                $table->json('version_history')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('threat_detections')) {
            Schema::create('threat_detections', function (Blueprint $table) {
                $table->id();
                $table->string('detection_uid', 64)->unique();
                $table->string('correlation_id', 64)->index();
                $table->string('dedup_key', 120)->index();
                $table->string('rule_code', 64)->index();
                $table->unsignedInteger('rule_version')->default(1);
                $table->string('data_source', 80);
                $table->string('severity', 24)->index();
                $table->unsignedBigInteger('target_user_id')->nullable()->index();
                $table->string('ip_address', 64)->nullable()->index();
                $table->unsignedInteger('observed_count')->default(1);
                $table->unsignedInteger('occurrence_count')->default(1);
                $table->unsignedInteger('threshold_count')->default(1);
                $table->unsignedInteger('window_minutes')->default(15);
                $table->string('confidence_level', 40)->default('measured_exact');
                $table->json('evidence_refs')->nullable();
                $table->string('limitations', 500)->nullable();
                $table->string('status', 32)->default('open')->index(); // open, investigating, suppressed, false_positive, mitigated
                $table->unsignedBigInteger('security_event_id')->nullable()->index();
                $table->timestamp('evaluated_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('automated_response_actions')) {
            Schema::create('automated_response_actions', function (Blueprint $table) {
                $table->id();
                $table->string('action_uid', 64)->unique();
                $table->string('idempotency_key', 120)->unique();
                $table->unsignedBigInteger('detection_id')->nullable()->index();
                $table->unsignedBigInteger('security_event_id')->nullable()->index();
                $table->string('action_type', 64)->index();
                $table->string('risk_level', 24)->default('medium');
                $table->string('target_type', 80);
                $table->unsignedBigInteger('target_id')->nullable()->index();
                $table->string('target_identifier', 120)->nullable()->index();
                $table->json('payload')->nullable();
                $table->boolean('requires_approval')->default(false)->index();
                $table->boolean('requires_human_approval')->default(false)->index();
                $table->string('status', 32)->default('pending_approval')->index(); // pending_approval, approved, executed, rejected, rolled_back, failed
                $table->unsignedBigInteger('requested_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->unsignedBigInteger('rejected_by')->nullable()->index();
                $table->text('justification')->nullable();
                $table->text('approval_notes')->nullable();
                $table->json('rollback_payload')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->timestamp('rolled_back_at')->nullable();
                $table->unsignedInteger('retry_count')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('compliance_controls')) {
            Schema::create('compliance_controls', function (Blueprint $table) {
                $table->id();
                $table->string('control_id', 64)->unique();
                $table->string('title', 180);
                $table->json('framework_mappings')->nullable();
                $table->string('owner_team', 120);
                $table->string('scope', 160);
                $table->string('evidence_status', 32)->default('auto_collected')->index(); // verified, auto_collected, manual_supplied, expired, missing, unavailable
                $table->string('assessment_outcome', 32)->default('not_assessed')->index(); // effective, needs_improvement, ineffective, not_assessed
                $table->json('evidence_refs')->nullable();
                $table->string('evidence_hash', 128)->nullable();
                $table->unsignedInteger('freshness_ttl_hours')->default(168);
                $table->timestamp('last_collected_at')->nullable();
                $table->timestamp('last_assessed_at')->nullable();
                $table->text('remediation_task')->nullable();
                $table->timestamp('remediation_due_at')->nullable();
                $table->text('risk_acceptance_reason')->nullable();
                $table->timestamp('risk_acceptance_expires_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->unsignedBigInteger('last_reviewed_by')->nullable()->index();
                $table->json('review_history')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('config_drift_baselines')) {
            Schema::create('config_drift_baselines', function (Blueprint $table) {
                $table->id();
                $table->string('baseline_uid', 64)->unique();
                $table->unsignedInteger('version')->default(1);
                $table->string('name', 160);
                $table->json('approved_baseline');
                $table->json('last_drift_report')->nullable();
                $table->unsignedInteger('drift_count')->default(0);
                $table->string('status', 32)->default('compliant')->index(); // compliant, drift_detected, exception_granted
                $table->json('exceptions')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('slo_definitions')) {
            Schema::create('slo_definitions', function (Blueprint $table) {
                $table->id();
                $table->string('slo_code', 64)->unique();
                $table->string('name', 160);
                $table->string('metric_key', 80)->index();
                $table->decimal('target_value', 10, 4);
                $table->string('comparison_operator', 8)->default('>=');
                $table->string('unit', 24)->default('percent');
                $table->unsignedInteger('window_days')->default(30);
                $table->unsignedInteger('error_budget_minutes')->default(43);
                $table->decimal('alert_threshold', 10, 4)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('release_verifications')) {
            Schema::create('release_verifications', function (Blueprint $table) {
                $table->id();
                $table->string('release_uid', 64)->unique();
                $table->string('version_tag', 64);
                $table->string('commit_sha', 64);
                $table->string('build_id', 64);
                $table->string('environment', 32)->default('staging')->index();
                $table->json('gates');
                $table->json('sbom_summary')->nullable();
                $table->string('overall_status', 32)->default('blocked')->index(); // approved_ready, blocked, failed, rolled_back
                $table->json('blockers')->nullable();
                $table->unsignedBigInteger('authorized_by')->nullable();
                $table->text('authorization_notes')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_integrity_runs')) {
            Schema::create('audit_integrity_runs', function (Blueprint $table) {
                $table->id();
                $table->string('run_uid', 64)->unique();
                $table->unsignedInteger('records_checked')->default(0);
                $table->unsignedBigInteger('start_id')->nullable();
                $table->unsignedBigInteger('end_id')->nullable();
                $table->boolean('verified')->default(true)->index();
                $table->string('error_code', 40)->default('NONE');
                $table->unsignedBigInteger('broken_at_id')->nullable();
                $table->json('gap_ids')->nullable();
                $table->string('root_hash', 128)->nullable();
                $table->unsignedInteger('duration_ms')->default(0);
                $table->unsignedBigInteger('triggered_by')->nullable();
                $table->timestamp('executed_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations safely.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_integrity_runs');
        Schema::dropIfExists('release_verifications');
        Schema::dropIfExists('slo_definitions');
        Schema::dropIfExists('config_drift_baselines');
        Schema::dropIfExists('compliance_controls');
        Schema::dropIfExists('automated_response_actions');
        Schema::dropIfExists('threat_detections');
        Schema::dropIfExists('threat_detection_rules');
        Schema::dropIfExists('zero_trust_access_logs');
        Schema::dropIfExists('zero_trust_policies');
    }
};
