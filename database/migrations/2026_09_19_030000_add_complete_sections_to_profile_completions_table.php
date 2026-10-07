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
        Schema::table('profile_completions', function (Blueprint $table) {
            if (! Schema::hasColumn('profile_completions', 'has_name')) {
                $table->boolean('has_name')->default(false)->after('completion_percentage');
            }
            if (! Schema::hasColumn('profile_completions', 'has_username')) {
                $table->boolean('has_username')->default(false)->after('has_name');
            }
            if (! Schema::hasColumn('profile_completions', 'has_interests')) {
                $table->boolean('has_interests')->default(false)->after('has_skills');
            }
            if (! Schema::hasColumn('profile_completions', 'has_languages')) {
                $table->boolean('has_languages')->default(false)->after('has_interests');
            }
            if (! Schema::hasColumn('profile_completions', 'completed_sections')) {
                $table->json('completed_sections')->nullable()->after('missing_sections');
            }
            if (! Schema::hasColumn('profile_completions', 'total_sections')) {
                $table->unsignedTinyInteger('total_sections')->default(13)->after('completed_sections');
            }
            if (! Schema::hasColumn('profile_completions', 'completed_count')) {
                $table->unsignedTinyInteger('completed_count')->default(0)->after('total_sections');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_completions', function (Blueprint $table) {
            $columns = [
                'has_name',
                'has_username',
                'has_interests',
                'has_languages',
                'completed_sections',
                'total_sections',
                'completed_count',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('profile_completions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
