<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Comment;
use App\Models\Group;
use App\Models\LiveStream;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageEvent;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\Post;
use App\Models\Reaction;
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
        $pageBondhoo = Page::firstOrCreate(
            ['slug' => 'bondhoo-official'],
            [
                'name' => 'Bondhoo Official',
                'category' => 'Technology & Social Network',
                'bio' => 'Bondhoo বাংলাদেশের নিজস্ব স্বাবলম্বী সোশ্যাল নেটওয়ার্ক। নতুন আপডেট ও ফিচারের জন্য সাথে থাকুন।',
                'avatar_url' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=400&q=80',
                'cover_image_url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1600&q=80',
                'owner_id' => $admin->id,
                'followers_count' => 12402,
                'posts_count' => 6,
                'is_verified' => true,
            ]
        );

        // Real Followers for Bondhoo Official
        PageFollower::firstOrCreate(['page_id' => $pageBondhoo->id, 'user_id' => $user->id]);
        PageFollower::firstOrCreate(['page_id' => $pageBondhoo->id, 'user_id' => $user2->id]);

        // Real Team Members for Bondhoo Official
        PageMember::firstOrCreate(
            ['page_id' => $pageBondhoo->id, 'user_id' => $user->id],
            ['role' => 'editor', 'status' => 'active', 'invited_by' => $admin->id]
        );
        PageMember::firstOrCreate(
            ['page_id' => $pageBondhoo->id, 'user_id' => $user2->id],
            ['role' => 'moderator', 'status' => 'active', 'invited_by' => $admin->id]
        );

        // Real Event for Bondhoo Official
        PageEvent::firstOrCreate(
            ['page_id' => $pageBondhoo->id, 'slug' => 'bondhoo-developer-summit-2026'],
            [
                'title' => 'Bondhoo ডেভেলপার ও ক্রিয়েটর সামিট ২০২৬',
                'description' => 'বাংলাদেশের সফটওয়্যার ইঞ্জিনিয়ার ও ডিজিটাল কন্টেন্ট ক্রিয়েটরদের বার্ষিক মিলনমেলা। প্ল্যাটফর্মের নতুন আর্কিটেকচার ও এপিআই উন্মোচন।',
                'location' => 'বঙ্গবন্ধু আন্তর্জাতিক সম্মেলন কেন্দ্র (BICC), ঢাকা',
                'start_time' => now()->addDays(7)->setTime(16, 0),
                'end_time' => now()->addDays(7)->setTime(20, 0),
                'is_online' => false,
                'rsvp_count' => 142,
                'status' => 'published',
            ]
        );

        // Real Posts for Bondhoo Official
        // 1) Pinned Text Post
        $p1 = Post::firstOrCreate(
            [
                'page_id' => $pageBondhoo->id,
                'content' => "🎉 স্বাগতম Bondhoo Official পেইজে! আমাদের নতুন আপডেট, সিকিউরিটি ফিচার ও কমিউনিটি গাইডলাইন সবার আগে পেতে আমাদের সাথেই থাকুন।\n\nকোন ফিচারে আপনি সবচেয়ে বেশি আগ্রহী? আমাদের জানান!",
            ],
            [
                'user_id' => $admin->id,
                'type' => 'text',
                'audience' => 'public',
                'status' => 'published',
                'is_pinned' => true,
                'likes_count' => 45,
                'comments_count' => 12,
                'shares_count' => 8,
            ]
        );

        // 2) Photo Post 1
        $p2 = Post::firstOrCreate(
            [
                'page_id' => $pageBondhoo->id,
                'content' => '🚀 নতুন ড্যাশবোর্ড ও ক্রিয়েটর স্টুডিও উন্মোচন! এখন থেকে পেইজ অ্যাডমিনরা পাবেন রিয়েল-টাইম এনালিটিক্স ও সহজ পোস্ট ম্যানেজমেন্ট সুবিধা।',
            ],
            [
                'user_id' => $admin->id,
                'type' => 'media',
                'audience' => 'public',
                'status' => 'published',
                'likes_count' => 88,
                'comments_count' => 19,
                'shares_count' => 14,
            ]
        );
        Media::firstOrCreate(
            ['mediable_type' => Post::class, 'mediable_id' => $p2->id, 'collection' => 'posts'],
            [
                'user_id' => $admin->id,
                'disk' => 'public',
                'original_path' => 'messages/msg_1_1791226377_d15cf765e917.png',
                'thumbnail_path' => 'messages/msg_1_1791226377_d15cf765e917_thumbnail.webp',
                'medium_path' => 'messages/msg_1_1791226377_d15cf765e917_medium.webp',
                'large_path' => 'messages/msg_1_1791226377_d15cf765e917_large.webp',
                'mime_type' => 'image/png',
                'size' => 102400,
                'processing_status' => 'completed',
            ]
        );

        // 3) Photo Post 2
        $p3 = Post::firstOrCreate(
            [
                'page_id' => $pageBondhoo->id,
                'content' => '📱 মোবাইল-ফার্স্ট সোশ্যাল এক্সপেরিয়েন্স: অ্যান্ড্রোইড এবং আইওএস সব ডিভাইসেই এখন লাইটনিং-ফাস্ট ব্রাউজিং ও ডাটা-সাশ্রয়ী ফিচার।',
            ],
            [
                'user_id' => $admin->id,
                'type' => 'media',
                'audience' => 'public',
                'status' => 'published',
                'likes_count' => 64,
                'comments_count' => 9,
                'shares_count' => 7,
            ]
        );
        Media::firstOrCreate(
            ['mediable_type' => Post::class, 'mediable_id' => $p3->id, 'collection' => 'posts'],
            [
                'user_id' => $admin->id,
                'disk' => 'public',
                'original_path' => 'messages/msg_1_1791371155_c9f40e3d330b.png',
                'thumbnail_path' => 'messages/msg_1_1791371155_c9f40e3d330b_thumbnail.webp',
                'medium_path' => 'messages/msg_1_1791371155_c9f40e3d330b_medium.webp',
                'large_path' => 'messages/msg_1_1791371155_c9f40e3d330b_large.webp',
                'mime_type' => 'image/png',
                'size' => 98400,
                'processing_status' => 'completed',
            ]
        );

        // 4) Video Post
        $p4 = Post::firstOrCreate(
            [
                'page_id' => $pageBondhoo->id,
                'content' => '🎥 বিশেষ ভিডিও টিউটোরিয়াল: সহজে কীভাবে আপনার পেইজ ও গ্রুপ দ্রুত বৃদ্ধি করবেন এবং ফলোয়ারদের সাথে সার্বক্ষণিক যুক্ত থাকবেন।',
            ],
            [
                'user_id' => $admin->id,
                'type' => 'video',
                'audience' => 'public',
                'status' => 'published',
                'likes_count' => 112,
                'comments_count' => 27,
                'shares_count' => 31,
            ]
        );
        Media::firstOrCreate(
            ['mediable_type' => Post::class, 'mediable_id' => $p4->id, 'collection' => 'posts'],
            [
                'user_id' => $admin->id,
                'disk' => 'public',
                'original_path' => 'uploads/reel/2026/10/user1-yajGJ1AsSv.mp4',
                'mime_type' => 'video/mp4',
                'size' => 1048576,
                'processing_status' => 'completed',
            ]
        );

        // 5) Link Post
        Post::firstOrCreate(
            [
                'page_id' => $pageBondhoo->id,
                'content' => "আমাদের টেকনিক্যাল ব্লগের নতুন পর্ব: 'হাই-পারফরম্যান্স রিয়েলটাইম ডেটাবেজ ও মেসেজিং ইনফ্রাস্ট্রাকচার'। বিস্তারিত পড়ুন এবং আপনার মতামত দিন।",
            ],
            [
                'user_id' => $admin->id,
                'type' => 'link',
                'audience' => 'public',
                'status' => 'published',
                'link_preview' => [
                    'url' => 'https://jugajug.com/blog/security-architecture',
                    'title' => 'Jugajug Engineering: Real-time Social Networking Architecture',
                    'domain' => 'jugajug.com',
                    'image' => '/images/bondhoo-logo.png',
                ],
                'likes_count' => 52,
                'comments_count' => 6,
                'shares_count' => 11,
            ]
        );

        // 6) Text Discussion Post
        Post::firstOrCreate(
            [
                'page_id' => $pageBondhoo->id,
                'content' => 'সপ্তাহের মতামত পর্ব: আপনার সবচেয়ে প্রিয় Bondhoo ফিচার কোনটি? কমেন্টে আমাদের জানান! ১) মেসেজিং ২) পেজ ৩) গ্রুপ ৪) মার্কেটপ্লেস।',
            ],
            [
                'user_id' => $admin->id,
                'type' => 'text',
                'audience' => 'public',
                'status' => 'published',
                'likes_count' => 34,
                'comments_count' => 22,
                'shares_count' => 5,
            ]
        );

        // 7) Scheduled Post
        Post::firstOrCreate(
            [
                'page_id' => $pageBondhoo->id,
                'content' => 'আগামী শুক্রবার রাত ৮টায় লাইভ প্রশ্নোত্তর পর্বে অংশ নিন আমাদের প্রতিষ্ঠাতা ও টেকনিক্যাল টিমের সাথে!',
            ],
            [
                'user_id' => $admin->id,
                'type' => 'text',
                'audience' => 'public',
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(2),
            ]
        );

        // 8) Draft Post
        Post::firstOrCreate(
            [
                'page_id' => $pageBondhoo->id,
                'content' => 'খসড়া: প্ল্যাটফর্মের ক্রিয়েটর মনিটাইজেশন পলিসি ২০২৬-এর বিস্তারিত রূপরেখা।',
            ],
            [
                'user_id' => $admin->id,
                'type' => 'text',
                'audience' => 'only_me',
                'status' => 'draft',
            ]
        );

        // Real Comments and Reactions
        Comment::firstOrCreate([
            'post_id' => $p1->id,
            'user_id' => $user->id,
            'body' => 'Bondhoo Official পেইজ চালুর জন্য অনেক শুভকামনা! প্ল্যাটফর্মটি চমৎকার কাজ করছে।',
        ]);
        Comment::firstOrCreate([
            'post_id' => $p1->id,
            'user_id' => $user2->id,
            'body' => 'দারুণ উদ্যোগ! পেজ ম্যানেজমেন্ট ড্যাশবোর্ড খুবই ইউজার ফ্রেন্ডলি।',
        ]);
        Reaction::firstOrCreate([
            'user_id' => $user->id,
            'reactable_type' => Post::class,
            'reactable_id' => $p1->id,
            'type' => 'like',
        ]);
        Reaction::firstOrCreate([
            'user_id' => $user2->id,
            'reactable_type' => Post::class,
            'reactable_id' => $p1->id,
            'type' => 'love',
        ]);

        // Sync real counts on page
        $pageBondhoo->update([
            'posts_count' => Post::where('page_id', $pageBondhoo->id)->where('status', 'published')->count(),
            'followers_count' => 12400 + PageFollower::where('page_id', $pageBondhoo->id)->count(),
        ]);

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
