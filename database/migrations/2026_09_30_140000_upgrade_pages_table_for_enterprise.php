<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Upgrade pages and posts for enterprise social page operations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            if (! Schema::hasColumn('pages', 'username')) {
                $table->string('username', 60)->nullable()->unique()->after('slug');
            }
            if (! Schema::hasColumn('pages', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->index()->after('owner_id');
            }
            if (! Schema::hasColumn('pages', 'sub_category')) {
                $table->string('sub_category', 50)->nullable()->after('category');
            }
            if (! Schema::hasColumn('pages', 'description')) {
                $table->text('description')->nullable()->after('bio');
            }
            if (! Schema::hasColumn('pages', 'website')) {
                $table->string('website')->nullable()->after('description');
            }
            if (! Schema::hasColumn('pages', 'email')) {
                $table->string('email')->nullable()->after('website');
            }
            if (! Schema::hasColumn('pages', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }
            if (! Schema::hasColumn('pages', 'address')) {
                $table->string('address')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('pages', 'city')) {
                $table->string('city', 60)->nullable()->after('address');
            }
            if (! Schema::hasColumn('pages', 'country')) {
                $table->string('country', 60)->nullable()->after('city');
            }
            if (! Schema::hasColumn('pages', 'zip_code')) {
                $table->string('zip_code', 20)->nullable()->after('country');
            }
            if (! Schema::hasColumn('pages', 'business_hours')) {
                $table->json('business_hours')->nullable()->after('zip_code');
            }
            if (! Schema::hasColumn('pages', 'cta_type')) {
                $table->string('cta_type', 30)->default('contact_us')->after('business_hours');
            }
            if (! Schema::hasColumn('pages', 'cta_url')) {
                $table->string('cta_url')->nullable()->after('cta_type');
            }
            if (! Schema::hasColumn('pages', 'verification_status')) {
                $table->string('verification_status', 20)->default('unverified')->after('is_verified');
            }
            if (! Schema::hasColumn('pages', 'visibility')) {
                $table->string('visibility', 20)->default('public')->index()->after('verification_status');
            }
            if (! Schema::hasColumn('pages', 'status')) {
                $table->string('status', 20)->default('active')->index()->after('visibility');
            }
            if (! Schema::hasColumn('pages', 'settings')) {
                $table->json('settings')->nullable()->after('status');
            }
            if (! Schema::hasColumn('pages', 'metadata')) {
                $table->json('metadata')->nullable()->after('settings');
            }
            if (! Schema::hasColumn('pages', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'status')) {
                $table->string('status', 20)->default('published')->index()->after('type');
            }
            if (! Schema::hasColumn('posts', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->index()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'scheduled_at')) {
                $table->dropIndex(['scheduled_at']);
                $table->dropColumn('scheduled_at');
            }
            if (Schema::hasColumn('posts', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });

        Schema::table('pages', function (Blueprint $table) {
            $cols = [
                'username', 'organization_id', 'sub_category', 'description',
                'website', 'email', 'phone', 'address', 'city', 'country', 'zip_code',
                'business_hours', 'cta_type', 'cta_url', 'verification_status',
                'visibility', 'status', 'settings', 'metadata',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('pages', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('pages', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
