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
        // 1. Platform Settings Table
        if (! Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('type', 30)->default('string'); // string, boolean, integer, json, array
                $table->string('group', 50)->default('general'); // general, security, media, registration, monetization
                $table->string('label', 150)->nullable();
                $table->string('description', 255)->nullable();
                $table->boolean('is_public')->default(false);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['group', 'key']);
            });

            // Seed essential enterprise default settings
            $now = now();
            DB::table('platform_settings')->insert([
                [
                    'key' => 'site_name',
                    'value' => 'Bondhoo - যোগাযোগ',
                    'type' => 'string',
                    'group' => 'general',
                    'label' => 'প্ল্যাটফর্মের নাম',
                    'description' => 'সোশ্যাল নেটওয়ার্কের অফিসিয়াল নাম',
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'site_tagline',
                    'value' => 'সংযোগ, সম্পর্ক ও সম্ভাবনার ডিজিটাল আঙিনা',
                    'type' => 'string',
                    'group' => 'general',
                    'label' => 'ট্যাগলাইন',
                    'description' => 'ব্র্যান্ডের মূল বার্তা বা স্লোগান',
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'registration_enabled',
                    'value' => '1',
                    'type' => 'boolean',
                    'group' => 'registration',
                    'label' => 'নতুন নিবন্ধন উন্মুক্ত রাখুন',
                    'description' => 'ব্যবহারকারীকে সাইন-আপ করতে দিন',
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'email_verification_required',
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'registration',
                    'label' => 'বাধ্যতামূলক ইমেইল যাচাইকরণ',
                    'description' => 'পোস্ট করার আগে ইমেইল নিশ্চিত করতে হবে',
                    'is_public' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'maintenance_mode',
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'general',
                    'label' => 'রক্ষণাবেক্ষণ মোড',
                    'description' => 'অ্যাডমিন ব্যতীত সাধারণ ভিজিটরদের সাময়িক বিরতি পেজ প্রদর্শন',
                    'is_public' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'max_upload_size_mb',
                    'value' => '100',
                    'type' => 'integer',
                    'group' => 'media',
                    'label' => 'সর্বোচ্চ আপলোড সাইজ (MB)',
                    'description' => 'ভিডিও বা ফাইলের সর্বোচ্চ আকার মেগাবাইটে',
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'allowed_video_formats',
                    'value' => json_encode(['mp4', 'mov', 'webm']),
                    'type' => 'json',
                    'group' => 'media',
                    'label' => 'অনুমোদিত ভিডিও ফরম্যাট',
                    'description' => 'যেসব ফরম্যাটের ভিডিও সিস্টেমে আপলোড করা যাবে',
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'contact_email',
                    'value' => 'support@jugajug.com',
                    'type' => 'string',
                    'group' => 'general',
                    'label' => 'সহায়তা ইমেইল',
                    'description' => 'ব্যবহারকারীদের যোগাযোগের জন্য অফিসিয়াল ইমেইল ঠিকানা',
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'terms_url',
                    'value' => '/terms',
                    'type' => 'string',
                    'group' => 'general',
                    'label' => 'শর্তাবলী লিংক',
                    'description' => 'ব্যবহারের নীতিমালা ও শর্তাবলীর ঠিকানা',
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'privacy_url',
                    'value' => '/privacy',
                    'type' => 'string',
                    'group' => 'general',
                    'label' => 'গোপনীয়তা নীতি লিংক',
                    'description' => 'প্রাইভেসি পলিসি ডকুমেন্টের ঠিকানা',
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        // 2. Support Tickets Table
        if (! Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_number', 40)->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('guest_name', 100)->nullable();
                $table->string('guest_email', 150)->nullable();
                $table->string('subject', 200);
                $table->string('category', 50)->default('general'); // general, account, billing, harassment, technical, bug
                $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
                $table->string('status', 30)->default('open'); // open, in_progress, pending_user, resolved, closed
                $table->text('message');
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->text('internal_notes')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'priority']);
                $table->index('category');
            });
        }

        // 3. Platform Announcements Table
        if (! Schema::hasTable('platform_announcements')) {
            Schema::create('platform_announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title', 200);
                $table->text('message');
                $table->string('type', 20)->default('info'); // info, success, warning, urgent
                $table->string('target_audience', 30)->default('all'); // all, users, creators, moderators
                $table->boolean('is_active')->default(true);
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_active', 'scheduled_at', 'expires_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('platform_settings');
    }
};
