<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Upgrade groups and group_members tables for Enterprise Community Operations.
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (! Schema::hasColumn('groups', 'username')) {
                $table->string('username', 60)->nullable()->unique()->after('slug');
            }
            if (! Schema::hasColumn('groups', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('cover_image_url');
            }
            if (! Schema::hasColumn('groups', 'category')) {
                $table->string('category', 60)->nullable()->index()->after('description');
            }
            if (! Schema::hasColumn('groups', 'subcategory')) {
                $table->string('subcategory', 60)->nullable()->after('category');
            }
            if (! Schema::hasColumn('groups', 'group_type')) {
                $table->string('group_type', 40)->default('general')->after('subcategory');
            }
            if (! Schema::hasColumn('groups', 'language')) {
                $table->string('language', 10)->default('en')->after('group_type');
            }
            if (! Schema::hasColumn('groups', 'location')) {
                $table->string('location')->nullable()->after('language');
            }
            if (! Schema::hasColumn('groups', 'country')) {
                $table->string('country', 60)->nullable()->after('location');
            }
            if (! Schema::hasColumn('groups', 'verification_status')) {
                $table->string('verification_status', 20)->default('unverified')->after('country');
            }
            if (! Schema::hasColumn('groups', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('verification_status');
            }
            if (! Schema::hasColumn('groups', 'status')) {
                $table->string('status', 20)->default('active')->index()->after('is_verified');
            }
            if (! Schema::hasColumn('groups', 'membership_approval_mode')) {
                $table->string('membership_approval_mode', 30)->default('anyone')->after('privacy');
            }
            if (! Schema::hasColumn('groups', 'post_approval_mode')) {
                $table->string('post_approval_mode', 30)->default('auto')->after('membership_approval_mode');
            }
            if (! Schema::hasColumn('groups', 'features')) {
                $table->json('features')->nullable()->after('post_approval_mode');
            }
            if (! Schema::hasColumn('groups', 'settings')) {
                $table->json('settings')->nullable()->after('features');
            }
            if (! Schema::hasColumn('groups', 'conversation_id')) {
                $table->unsignedBigInteger('conversation_id')->nullable()->index()->after('creator_id');
            }
            if (! Schema::hasColumn('groups', 'active_members_count')) {
                $table->unsignedInteger('active_members_count')->default(1)->after('members_count');
            }
            if (! Schema::hasColumn('groups', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        Schema::table('group_members', function (Blueprint $table) {
            if (! Schema::hasColumn('group_members', 'permissions')) {
                $table->json('permissions')->nullable()->after('role');
            }
            if (! Schema::hasColumn('group_members', 'invited_by')) {
                $table->unsignedBigInteger('invited_by')->nullable()->after('status');
            }
            if (! Schema::hasColumn('group_members', 'answers')) {
                $table->json('answers')->nullable()->after('invited_by');
            }
            if (! Schema::hasColumn('group_members', 'strikes_count')) {
                $table->unsignedInteger('strikes_count')->default(0)->after('answers');
            }
            if (! Schema::hasColumn('group_members', 'muted_until')) {
                $table->timestamp('muted_until')->nullable()->after('strikes_count');
            }
            if (! Schema::hasColumn('group_members', 'restricted_until')) {
                $table->timestamp('restricted_until')->nullable()->after('muted_until');
            }
            if (! Schema::hasColumn('group_members', 'banned_at')) {
                $table->timestamp('banned_at')->nullable()->after('restricted_until');
            }
            if (! Schema::hasColumn('group_members', 'ban_reason')) {
                $table->text('ban_reason')->nullable()->after('banned_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->dropColumn([
                'permissions',
                'invited_by',
                'answers',
                'strikes_count',
                'muted_until',
                'restricted_until',
                'banned_at',
                'ban_reason',
            ]);
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'avatar_url',
                'category',
                'subcategory',
                'group_type',
                'language',
                'location',
                'country',
                'verification_status',
                'is_verified',
                'status',
                'membership_approval_mode',
                'post_approval_mode',
                'features',
                'settings',
                'conversation_id',
                'active_members_count',
                'deleted_at',
            ]);
        });
    }
};
