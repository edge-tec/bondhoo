<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('group_categories')) {
            Schema::create('group_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->string('icon', 60)->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_system')->default(false);
                $table->unsignedInteger('groups_count')->default(0);
                $table->timestamps();
            });

            // Seed initial 19 professional categories
            $categories = [
                ['name' => 'Technology', 'icon' => 'laptop-outline', 'description' => 'Discussions on modern technology, AI, hardware, and gadgets.'],
                ['name' => 'Software Development', 'icon' => 'code-slash-outline', 'description' => 'Web, mobile, cloud architecture, and open-source software engineering.'],
                ['name' => 'Business', 'icon' => 'briefcase-outline', 'description' => 'Corporate strategy, management, operations, and business leadership.'],
                ['name' => 'Entrepreneurship', 'icon' => 'rocket-outline', 'description' => 'Founders, venture building, startup scaling, and innovation.'],
                ['name' => 'Digital Marketing', 'icon' => 'megaphone-outline', 'description' => 'SEO, SEM, social media growth, and content marketing.'],
                ['name' => 'Affiliate Marketing', 'icon' => 'link-outline', 'description' => 'Performance marketing, affiliate partnerships, and monetization.'],
                ['name' => 'Freelancing', 'icon' => 'globe-outline', 'description' => 'Independent consulting, gig economy, client management, and remote work.'],
                ['name' => 'Finance', 'icon' => 'cash-outline', 'description' => 'Investing, capital markets, personal finance, and fintech.'],
                ['name' => 'Education', 'icon' => 'school-outline', 'description' => 'Academic learning, e-learning, skill development, and certifications.'],
                ['name' => 'Design & Creative', 'icon' => 'color-palette-outline', 'description' => 'UI/UX design, visual identity, motion graphics, and creative arts.'],
                ['name' => 'Engineering', 'icon' => 'construct-outline', 'description' => 'Mechanical, electrical, civil, and industrial engineering communities.'],
                ['name' => 'Healthcare', 'icon' => 'medkit-outline', 'description' => 'Medical research, clinical practice, biotech, and digital health.'],
                ['name' => 'Science', 'icon' => 'flask-outline', 'description' => 'Scientific inquiry, research papers, data science, and discoveries.'],
                ['name' => 'Career Development', 'icon' => 'trending-up-outline', 'description' => 'Resume building, mentorship, interview prep, and career advancement.'],
                ['name' => 'Job & Recruitment', 'icon' => 'search-outline', 'description' => 'Job postings, talent sourcing, hiring trends, and recruitment.'],
                ['name' => 'Startups', 'icon' => 'flame-outline', 'description' => 'Early-stage ventures, pitch decks, investor networking, and accelerators.'],
                ['name' => 'E-commerce', 'icon' => 'cart-outline', 'description' => 'Online retail, dropshipping, marketplace strategy, and logistics.'],
                ['name' => 'Networking', 'icon' => 'people-outline', 'description' => 'Professional connections, industry meetups, and collaboration.'],
                ['name' => 'General Community', 'icon' => 'chatbubbles-outline', 'description' => 'Open community forums, informal networking, and hobbyists.'],
            ];

            $now = now();
            foreach ($categories as $index => $cat) {
                DB::table('group_categories')->insert([
                    'name' => $cat['name'],
                    'slug' => Str::slug($cat['name']),
                    'icon' => $cat['icon'],
                    'description' => $cat['description'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'is_system' => true,
                    'groups_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_categories');
    }
};
