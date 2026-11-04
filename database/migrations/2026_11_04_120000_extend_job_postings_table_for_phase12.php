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
        Schema::table('job_postings', function (Blueprint $table): void {
            if (! Schema::hasColumn('job_postings', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('page_id')->constrained('companies')->nullOnDelete();
            }
            if (! Schema::hasColumn('job_postings', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('company_id')->constrained('job_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('job_postings', 'slug')) {
                $table->string('slug', 250)->nullable()->index()->after('title');
            }
            if (! Schema::hasColumn('job_postings', 'responsibilities')) {
                $table->text('responsibilities')->nullable()->after('description');
            }
            if (! Schema::hasColumn('job_postings', 'qualifications')) {
                $table->text('qualifications')->nullable()->after('requirements');
            }
            if (! Schema::hasColumn('job_postings', 'workplace_type')) {
                $table->string('workplace_type', 30)->default('onsite')->index()->after('location_type');
            }
            if (! Schema::hasColumn('job_postings', 'salary_visibility')) {
                $table->string('salary_visibility', 30)->default('visible')->index()->after('salary_negotiable');
            }
            if (! Schema::hasColumn('job_postings', 'country')) {
                $table->string('country', 100)->nullable()->index()->after('location');
            }
            if (! Schema::hasColumn('job_postings', 'city')) {
                $table->string('city', 100)->nullable()->index()->after('country');
            }
            if (! Schema::hasColumn('job_postings', 'vacancies')) {
                $table->unsignedSmallInteger('vacancies')->default(1)->after('city');
            }
            if (! Schema::hasColumn('job_postings', 'visibility')) {
                $table->string('visibility', 30)->default('public')->index()->after('status');
            }
            if (! Schema::hasColumn('job_postings', 'application_method')) {
                $table->string('application_method', 30)->default('in_app')->index()->after('visibility');
            }
            if (! Schema::hasColumn('job_postings', 'external_application_url')) {
                $table->string('external_application_url', 255)->nullable()->after('application_method');
            }
            if (! Schema::hasColumn('job_postings', 'published_at')) {
                $table->timestamp('published_at')->nullable()->index()->after('deadline');
            }
            if (! Schema::hasColumn('job_postings', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('published_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('job_postings') && DB::getDriverName() !== 'sqlite') {
            Schema::table('job_postings', function (Blueprint $table): void {
                if (Schema::hasColumn('job_postings', 'company_id')) {
                    $table->dropForeign(['company_id']);
                }
                if (Schema::hasColumn('job_postings', 'category_id')) {
                    $table->dropForeign(['category_id']);
                }

                $columnsToDrop = [];
                foreach ([
                    'company_id',
                    'category_id',
                    'slug',
                    'responsibilities',
                    'qualifications',
                    'workplace_type',
                    'salary_visibility',
                    'country',
                    'city',
                    'vacancies',
                    'visibility',
                    'application_method',
                    'external_application_url',
                    'published_at',
                    'closed_at',
                ] as $column) {
                    if (Schema::hasColumn('job_postings', $column)) {
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
