<?php

declare(strict_types=1);

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
        Schema::table('job_applications', function (Blueprint $table): void {
            if (! Schema::hasColumn('job_applications', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('job_posting_id')->constrained('companies')->nullOnDelete();
            }
            if (! Schema::hasColumn('job_applications', 'resume_id')) {
                $table->foreignId('resume_id')->nullable()->after('user_id')->constrained('candidate_resumes')->nullOnDelete();
            }
            if (! Schema::hasColumn('job_applications', 'answers')) {
                $table->json('answers')->nullable()->after('cover_letter');
            }
            if (! Schema::hasColumn('job_applications', 'source')) {
                $table->string('source', 50)->default('direct')->index()->after('expected_salary');
            }
            if (! Schema::hasColumn('job_applications', 'applied_at')) {
                $table->timestamp('applied_at')->nullable()->index()->after('source');
            }
            if (! Schema::hasColumn('job_applications', 'withdrawn_at')) {
                $table->timestamp('withdrawn_at')->nullable()->after('applied_at');
            }
        });

        if (! Schema::hasTable('recruitment_stages')) {
            Schema::create('recruitment_stages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('slug', 100)->index();
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->boolean('is_system')->default(true);
                $table->string('color', 20)->default('#0284c7');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('application_stage_history')) {
            Schema::create('application_stage_history', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->cascadeOnDelete();
                $table->string('stage', 50)->index();
                $table->string('previous_stage', 50)->nullable();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('application_notes')) {
            Schema::create('application_notes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->cascadeOnDelete();
                $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
                $table->text('note');
                $table->string('visibility', 30)->default('internal'); // internal
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('application_assignments')) {
            Schema::create('application_assignments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->cascadeOnDelete();
                $table->foreignId('assigned_to_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_assignments');
        Schema::dropIfExists('application_notes');
        Schema::dropIfExists('application_stage_history');
        Schema::dropIfExists('recruitment_stages');

        if (Schema::hasTable('job_applications') && DB::getDriverName() !== 'sqlite') {
            Schema::table('job_applications', function (Blueprint $table): void {
                if (Schema::hasColumn('job_applications', 'company_id')) {
                    $table->dropForeign(['company_id']);
                }
                if (Schema::hasColumn('job_applications', 'resume_id')) {
                    $table->dropForeign(['resume_id']);
                }

                $columnsToDrop = [];
                foreach ([
                    'company_id',
                    'resume_id',
                    'answers',
                    'source',
                    'applied_at',
                    'withdrawn_at',
                ] as $column) {
                    if (Schema::hasColumn('job_applications', $column)) {
                        $columnsToDrop[] = $column;
                    }
                }
                if (! empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
