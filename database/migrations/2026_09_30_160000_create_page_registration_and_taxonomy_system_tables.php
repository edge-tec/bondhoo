<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * এন্টারপ্রাইজ পেজ রেজিস্ট্রেশন, ট্যাক্সোনমি, ডায়নামিক ফিল্ডস, ব্রাঞ্চেস ও ড্রাফট সিস্টেম।
     */
    public function up(): void
    {
        // ১. পেজ টাইপস টেবিল (Business, Creator, Brand, NGO, etc.)
        Schema::create('page_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 60)->default('briefcase');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        // ২. পেজ প্রাইমারি ক্যাটাগরিজ টেবিল
        Schema::create('page_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_type_id')->constrained('page_types')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->string('icon', 60)->default('folder');
            $table->boolean('is_popular')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['page_type_id', 'slug']);
        });

        // ৩. পেজ সাবক্যাটাগরিজ টেবিল
        Schema::create('page_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_category_id')->constrained('page_categories')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->string('icon', 60)->default('tag');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['page_category_id', 'slug']);
        });

        // ৪. ডায়নামিক কাস্টম ও ইন্ডাস্ট্রি স্পেসিফিক ফিল্ডস ইঞ্জিন টেবিল
        Schema::create('page_category_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_type_id')->nullable()->constrained('page_types')->cascadeOnDelete();
            $table->foreignId('page_category_id')->nullable()->constrained('page_categories')->cascadeOnDelete();
            $table->foreignId('page_subcategory_id')->nullable()->constrained('page_subcategories')->cascadeOnDelete();
            $table->string('field_key', 60);
            $table->string('label', 120);
            $table->string('field_type', 30)->default('text'); // text, textarea, number, select, multi-select, boolean, date, time, url, email, phone
            $table->string('placeholder', 150)->nullable();
            $table->text('description')->nullable();
            $table->json('options')->nullable(); // dropdown or radio options
            $table->boolean('is_required')->default(false);
            $table->string('default_value')->nullable();
            $table->string('validation_rules')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['page_type_id', 'page_category_id']);
        });

        // ৫. মাল্টিপল লোকেশন / এন্টারপ্রাইজ ব্রাঞ্চেস টেবিল
        Schema::create('page_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('name', 120); // Head Office, Gulshan Branch, etc.
            $table->string('street_address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('country', 100)->default('Bangladesh');
            $table->string('zip_code', 30)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('website', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('business_hours')->nullable();
            $table->boolean('is_headquarters')->default(false);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['page_id', 'is_headquarters']);
        });

        // ৬. পেজ রেজিস্ট্রেশন ড্রাফট ও অটোসেভ টেবিল
        Schema::create('page_creation_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->unsignedSmallInteger('current_step')->default(1);
            $table->json('form_data');
            $table->timestamp('autosaved_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        // ৭. পেজ ভেরিফিকেশন রিকোয়েস্ট টেবিল
        Schema::create('page_verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('verification_type', 40); // business, brand, organization, creator, public_figure, media
            $table->string('legal_name', 150)->nullable();
            $table->string('registration_number', 80)->nullable();
            $table->string('tax_id', 80)->nullable();
            $table->string('document_url')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('status', 30)->default('pending')->index(); // pending, under_review, approved, rejected
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['page_id', 'status']);
        });

        // ৮. পেজেস টেবিলের এক্সটেনশন (ট্যাক্সোনমি রিলেশন, বিজনেস ইনফো, সোশ্যাল লিংকস, প্রাইভেসি)
        Schema::table('pages', function (Blueprint $table) {
            if (! Schema::hasColumn('pages', 'page_type_id')) {
                $table->foreignId('page_type_id')->nullable()->after('owner_id')->constrained('page_types')->nullOnDelete();
            }
            if (! Schema::hasColumn('pages', 'page_category_id')) {
                $table->foreignId('page_category_id')->nullable()->after('page_type_id')->constrained('page_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('pages', 'page_subcategory_id')) {
                $table->foreignId('page_subcategory_id')->nullable()->after('page_category_id')->constrained('page_subcategories')->nullOnDelete();
            }
            if (! Schema::hasColumn('pages', 'logo_url')) {
                $table->string('logo_url')->nullable()->after('avatar_url');
            }
            if (! Schema::hasColumn('pages', 'short_description')) {
                $table->string('short_description', 300)->nullable()->after('bio');
            }
            if (! Schema::hasColumn('pages', 'business_details')) {
                $table->json('business_details')->nullable()->after('business_hours');
            }
            if (! Schema::hasColumn('pages', 'custom_fields_data')) {
                $table->json('custom_fields_data')->nullable()->after('business_details');
            }
            if (! Schema::hasColumn('pages', 'social_links')) {
                $table->json('social_links')->nullable()->after('custom_fields_data');
            }
            if (! Schema::hasColumn('pages', 'gallery_urls')) {
                $table->json('gallery_urls')->nullable()->after('social_links');
            }
            if (! Schema::hasColumn('pages', 'privacy_settings')) {
                $table->json('privacy_settings')->nullable()->after('settings');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $cols = [
                'privacy_settings', 'gallery_urls', 'social_links', 'custom_fields_data',
                'business_details', 'short_description', 'logo_url',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('pages', $col)) {
                    $table->dropColumn($col);
                }
            }

            if (Schema::hasColumn('pages', 'page_subcategory_id')) {
                $table->dropForeign(['page_subcategory_id']);
                $table->dropColumn('page_subcategory_id');
            }
            if (Schema::hasColumn('pages', 'page_category_id')) {
                $table->dropForeign(['page_category_id']);
                $table->dropColumn('page_category_id');
            }
            if (Schema::hasColumn('pages', 'page_type_id')) {
                $table->dropForeign(['page_type_id']);
                $table->dropColumn('page_type_id');
            }
        });

        Schema::dropIfExists('page_verification_requests');
        Schema::dropIfExists('page_creation_drafts');
        Schema::dropIfExists('page_locations');
        Schema::dropIfExists('page_category_fields');
        Schema::dropIfExists('page_subcategories');
        Schema::dropIfExists('page_categories');
        Schema::dropIfExists('page_types');
    }
};
