<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Group;
use App\Models\LiveStream;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\Page;
use App\Models\Post;
use App\Models\Role;
use App\Models\Story;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'SUPER_ADMIN')->first();

        // 1. Super Admin
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Bondhoo System Admin',
                'email' => 'admin@jugajug.com',
                'phone' => '+8801700000000',
                'password' => 'Admin@123456',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        if ($superAdminRole) {
            $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        // Enterprise Admin Guard Account
        Admin::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Bondhoo System Admin',
                'email' => 'admin@jugajug.com',
                'phone' => '+8801700000000',
                'password' => 'Admin@123456',
                'role' => 'super_admin',
                'status' => 'active',
                'two_factor_enabled' => false,
            ]
        );

        UserProfile::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'display_name' => 'Bondhoo Admin',
                'bio' => 'Lead System Administrator at Bondhoo Platform.',
                'location' => 'Dhaka, Bangladesh',
                'joined_date' => now(),
            ]
        );

        // 2. Demo Regular User: Abdur Rahim
        $user = User::firstOrCreate(
            ['username' => 'rahim'],
            [
                'name' => 'Abdur Rahim',
                'email' => 'user@jugajug.com',
                'phone' => '+8801711111111',
                'password' => 'User@123456',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => 'Abdur Rahim',
                'bio' => 'Software Engineer & Tech Enthusiast based in Dhaka. Loving the new Bondhoo platform!',
                'location' => 'Dhaka, Bangladesh',
                'joined_date' => now(),
            ]
        );

        UserSetting::firstOrCreate(['user_id' => $user->id]);

        // 3. Demo Regular User: Nusrat Jahan
        $user2 = User::firstOrCreate(
            ['username' => 'nusrat'],
            [
                'name' => 'Nusrat Jahan',
                'email' => 'nusrat@jugajug.com',
                'phone' => '+8801722222222',
                'password' => 'User@123456',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        UserProfile::firstOrCreate(
            ['user_id' => $user2->id],
            [
                'display_name' => 'Nusrat Jahan',
                'bio' => 'Photographer & Designer exploring beautiful Bangladesh.',
                'location' => 'Chittagong, Bangladesh',
                'joined_date' => now(),
            ]
        );

        UserSetting::firstOrCreate(['user_id' => $user2->id]);

        // 4. Sample Posts
        Post::firstOrCreate(
            ['user_id' => $user->id, 'content' => '🎉 Just joined Bondhoo! The real-time messaging and fast feed architecture is unbelievable. Amazing work team!'],
            ['audience' => 'public', 'type' => 'text']
        );

        Post::firstOrCreate(
            ['user_id' => $user2->id, 'content' => '📸 Beautiful sunny morning here in Cox\'s Bazar! Hope everyone is having a productive week.'],
            ['audience' => 'public', 'type' => 'text']
        );

        // 5. Sample 24h Story
        Story::firstOrCreate(
            ['user_id' => $user->id, 'content' => 'Coding on Bondhoo Platform 🚀'],
            ['type' => 'text', 'privacy' => 'public', 'expires_at' => now()->addHours(20)]
        );

        // 6. Marketplace Categories & Products
        $catElectronics = MarketplaceCategory::firstOrCreate(
            ['slug' => 'electronics'],
            ['name' => 'ইলেকট্রনিক্স ও গ্যাজেটস', 'icon' => '📱', 'display_order' => 1]
        );
        $catFashion = MarketplaceCategory::firstOrCreate(
            ['slug' => 'fashion'],
            ['name' => 'পোশাক ও ফ্যাশন', 'icon' => '👕', 'display_order' => 2]
        );
        $catVehicles = MarketplaceCategory::firstOrCreate(
            ['slug' => 'vehicles'],
            ['name' => 'গাড়ি ও মোটরসাইকেল', 'icon' => '🚗', 'display_order' => 3]
        );
        $catProperty = MarketplaceCategory::firstOrCreate(
            ['slug' => 'property'],
            ['name' => 'বাড়ি ও জমি', 'icon' => '🏠', 'display_order' => 4]
        );

        MarketplaceProduct::firstOrCreate(
            ['title' => 'iPhone 15 Pro Max 256GB'],
            [
                'seller_id' => $user->id,
                'category_id' => $catElectronics->id,
                'description' => 'অরিজিনাল বক্স ও ক্যাবলসহ একদম নতুনের মতো ফ্রেশ কন্ডিশন। ব্যাটারি হেলথ ৯৮%।',
                'price' => 125000.00,
                'currency' => 'BDT',
                'condition' => 'used_like_new',
                'location' => 'গুলশান-২, ঢাকা',
                'status' => 'active',
                'ai_fraud_score' => 0.05,
            ]
        );

        MarketplaceProduct::firstOrCreate(
            ['title' => 'প্রিমিয়াম সুতি পাঞ্জাবি'],
            [
                'seller_id' => $user2->id,
                'category_id' => $catFashion->id,
                'description' => '১০০% খাঁটি কটন ফেব্রিক। আরামদায়ক ও ট্রেন্ডি ডিজাইন। সব সাইজ এভেইলেবল।',
                'price' => 2200.00,
                'currency' => 'BDT',
                'condition' => 'new',
                'location' => 'জিইসি মোড়, চট্টগ্রাম',
                'status' => 'active',
                'ai_fraud_score' => 0.02,
            ]
        );

        // 7. Community Groups
        $groupDev = Group::firstOrCreate(
            ['slug' => 'bangladesh-developers'],
            [
                'name' => 'বাংলাদেশ ডেভেলপার্স কমিউনিটি',
                'description' => 'বাংলাদেশের সফটওয়্যার ইঞ্জিনিয়ার, ডিজাইনার ও ফ্রিল্যান্সারদের জাতীয় মিলনমেলা।',
                'privacy' => 'public',
                'creator_id' => $admin->id,
                'members_count' => 1520,
                'posts_count' => 84,
            ]
        );
        $groupDev->users()->syncWithoutDetaching([$user->id => ['role' => 'admin', 'status' => 'approved']]);
        $groupDev->users()->syncWithoutDetaching([$user2->id => ['role' => 'member', 'status' => 'approved']]);

        $groupTech = Group::firstOrCreate(
            ['slug' => 'tech-startups-bd'],
            [
                'name' => 'টেক স্টার্টআপ বাংলাদেশ',
                'description' => 'উদ্যোক্তা, ইনভেস্টর ও স্টার্টআপ নির্মাতাদের নেটওয়ার্কিং গ্রুপ।',
                'privacy' => 'public',
                'creator_id' => $user->id,
                'members_count' => 830,
                'posts_count' => 42,
            ]
        );
        $groupTech->users()->syncWithoutDetaching([$user->id => ['role' => 'admin', 'status' => 'approved']]);

        // 8. Pages
        Page::firstOrCreate(
            ['slug' => 'bondhoo-official'],
            [
                'name' => 'Bondhoo Official',
                'category' => 'Technology & Social Network',
                'bio' => 'Bondhoo বাংলাদেশের নিজস্ব স্বাবলম্বী সোশ্যাল নেটওয়ার্ক। নতুন আপডেট ও ফিচারের জন্য সাথে থাকুন।',
                'owner_id' => $admin->id,
                'followers_count' => 12400,
                'posts_count' => 15,
                'is_verified' => true,
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'prothom-alo-updates'],
            [
                'name' => 'দৈনিক খবর বিডি',
                'category' => 'News & Media',
                'bio' => 'সারাদেশের সর্বশেষ নির্ভরযোগ্য সংবাদ সবার আগে।',
                'owner_id' => $user2->id,
                'followers_count' => 8900,
                'posts_count' => 32,
                'is_verified' => false,
            ]
        );

        // 9. Live Stream
        LiveStream::firstOrCreate(
            ['title' => 'লাইভ সেশন: Bondhoo আর্কিটেকচার ও ফিচার ডেমো'],
            [
                'channel_id' => (string) Str::uuid(),
                'user_id' => $admin->id,
                'description' => 'হাই-পারফরম্যান্স লারাভেল ও রিয়েল-টাইম আর্কিটেকচার নিয়ে বিশেষ লাইভ প্রশ্নোত্তর।',
                'status' => 'live',
                'stream_key' => 'live_jugajug_demo_key_8842',
                'ingest_url' => 'rtmp://live.jugajug.com/live',
                'playback_url' => 'https://live.jugajug.com/hls/demo.m3u8',
                'viewers_count' => 142,
                'started_at' => now()->subMinutes(15),
            ]
        );
    }
}
