<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Models\GroupRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GroupEcosystemSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first() ?? User::factory()->create(['name' => 'System Admin', 'status' => 'active']);
        $user1 = User::find(1) ?? $admin;
        $user2 = User::find(2) ?? User::factory()->create(['name' => 'Jane Developer', 'status' => 'active']);

        // Update existing groups with proper categories and high quality assets
        $groupDev = Group::where('slug', 'bangladesh-developers')->first();
        if ($groupDev) {
            $groupDev->update([
                'category' => 'Software Development',
                'subcategory' => 'Web & Mobile Engineering',
                'cover_image_url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1200&q=80',
                'avatar_url' => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=400&q=80',
                'health_score' => 98.50,
                'status' => 'active',
            ]);
            // Ensure User 1 is active member
            GroupMember::updateOrCreate(
                ['group_id' => $groupDev->id, 'user_id' => $user1->id],
                ['role' => 'admin', 'status' => 'active', 'joined_at' => now()]
            );
        }

        $groupTech = Group::where('slug', 'tech-startups-bd')->first();
        if ($groupTech) {
            $groupTech->update([
                'category' => 'Startups',
                'subcategory' => 'Venture & Innovation',
                'cover_image_url' => 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=1200&q=80',
                'avatar_url' => 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?auto=format&fit=crop&w=400&q=80',
                'health_score' => 95.00,
                'status' => 'active',
            ]);
            // User 1 is owner
            GroupMember::updateOrCreate(
                ['group_id' => $groupTech->id, 'user_id' => $user1->id],
                ['role' => 'owner', 'status' => 'active', 'joined_at' => now()]
            );
        }

        $seedGroups = [
            [
                'name' => 'AI & Machine Learning Bangladesh',
                'slug' => 'ai-ml-bangladesh',
                'username' => 'ai_ml_bd',
                'description' => 'A community for researchers, ML engineers, data scientists, and AI builders in Bangladesh.',
                'category' => 'Technology',
                'subcategory' => 'Artificial Intelligence & Data',
                'cover_image_url' => 'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=1200&q=80',
                'avatar_url' => 'https://images.unsplash.com/photo-1677442136019-21780efad99a?auto=format&fit=crop&w=400&q=80',
                'members_count' => 3450,
                'posts_count' => 128,
                'creator_id' => $admin->id,
                'privacy' => 'public',
                'location' => 'Dhaka, Bangladesh',
                'health_score' => 99.0,
            ],
            [
                'name' => 'UI/UX & Product Designers Bangladesh',
                'slug' => 'ui-ux-designers-bd',
                'username' => 'uiux_bd',
                'description' => 'The premier community for digital product designers, design systems leads, and UX researchers.',
                'category' => 'Design & Creative',
                'subcategory' => 'Product & Interaction Design',
                'cover_image_url' => 'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=1200&q=80',
                'avatar_url' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=400&q=80',
                'members_count' => 2890,
                'posts_count' => 95,
                'creator_id' => $admin->id,
                'privacy' => 'public',
                'location' => 'Dhaka, Bangladesh',
                'health_score' => 96.5,
            ],
            [
                'name' => 'Top-Rated Freelancers & Remote Workers BD',
                'slug' => 'top-freelancers-bd',
                'username' => 'freelancers_bd',
                'description' => 'Sharing international payment insights, Upwork/Fiverr client acquisition, and high-ticket freelancing tips.',
                'category' => 'Freelancing',
                'subcategory' => 'Remote Careers & Contracting',
                'cover_image_url' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80',
                'avatar_url' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=400&q=80',
                'members_count' => 6120,
                'posts_count' => 310,
                'creator_id' => $user2->id,
                'privacy' => 'public',
                'location' => 'Bangladesh',
                'health_score' => 97.2,
            ],
            [
                'name' => 'Cyber Security & Ethical Hacking BD',
                'slug' => 'cyber-security-bd',
                'username' => 'cybersec_bd',
                'description' => 'Discussion on DevSecOps, bug bounty, penetration testing, security auditing, and compliance in Bangladesh.',
                'category' => 'Technology',
                'subcategory' => 'Information Security',
                'cover_image_url' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=1200&q=80',
                'avatar_url' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=400&q=80',
                'members_count' => 1980,
                'posts_count' => 64,
                'creator_id' => $admin->id,
                'privacy' => 'public',
                'location' => 'Dhaka, Bangladesh',
                'health_score' => 94.0,
            ],
            [
                'name' => 'Business Leaders & Executive Network BD',
                'slug' => 'business-leaders-bd',
                'username' => 'business_leaders_bd',
                'description' => 'C-suite executives, corporate directors, and SME business owners networking and business opportunities.',
                'category' => 'Business',
                'subcategory' => 'Corporate Strategy & Leadership',
                'cover_image_url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80',
                'avatar_url' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=400&q=80',
                'members_count' => 4210,
                'posts_count' => 180,
                'creator_id' => $admin->id,
                'privacy' => 'public',
                'location' => 'Gulshan, Dhaka',
                'health_score' => 96.0,
            ],
            [
                'name' => 'Digital Marketers & Growth Hackers BD',
                'slug' => 'digital-marketing-bd',
                'username' => 'marketing_bd',
                'description' => 'Performance marketing, SEO, Meta Ads, Google Analytics, and growth loops for Bangladeshi businesses.',
                'category' => 'Digital Marketing',
                'subcategory' => 'Growth & Performance Marketing',
                'cover_image_url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&q=80',
                'avatar_url' => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=400&q=80',
                'members_count' => 5400,
                'posts_count' => 220,
                'creator_id' => $user2->id,
                'privacy' => 'public',
                'location' => 'Dhaka, Bangladesh',
                'health_score' => 95.8,
            ],
        ];

        foreach ($seedGroups as $gData) {
            $createdGroup = Group::firstOrCreate(
                ['slug' => $gData['slug']],
                array_merge($gData, [
                    'status' => 'active',
                    'membership_approval_mode' => 'anyone',
                    'post_approval_mode' => 'auto',
                ])
            );

            // Add rules
            GroupRule::firstOrCreate(
                ['group_id' => $createdGroup->id, 'title' => 'Be Professional & Respectful'],
                ['description' => 'Treat fellow members with dignity and professional courtesy. Zero tolerance for harassment.']
            );
            GroupRule::firstOrCreate(
                ['group_id' => $createdGroup->id, 'title' => 'No Spam or Pyramid Schemes'],
                ['description' => 'Unsolicited promotion, MLM, or scam links will lead to immediate ban.']
            );
        }

        // Add User 1 as a member of AI & ML group so it appears in Your Groups
        $aiGroup = Group::where('slug', 'ai-ml-bangladesh')->first();
        if ($aiGroup) {
            GroupMember::firstOrCreate(
                ['group_id' => $aiGroup->id, 'user_id' => $user1->id],
                ['role' => 'member', 'status' => 'active', 'joined_at' => now()]
            );
        }

        // Add a pending invitation for User 1 to Business Leaders BD
        $bizGroup = Group::where('slug', 'business-leaders-bd')->first();
        if ($bizGroup) {
            GroupInvitation::updateOrCreate(
                ['group_id' => $bizGroup->id, 'invitee_id' => $user1->id],
                [
                    'inviter_id' => $admin->id,
                    'token' => Str::random(32),
                    'role' => 'member',
                    'status' => GroupInvitation::STATUS_PENDING,
                    'expires_at' => now()->addDays(7),
                ]
            );
        }
    }
}
