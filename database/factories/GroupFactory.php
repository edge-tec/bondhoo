<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'creator_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'username' => 'grp_'.fake()->unique()->lexify('??????'),
            'description' => fake()->sentence(),
            'category' => 'Technology',
            'privacy' => Group::PRIVACY_PUBLIC,
            'community_type' => Group::COMMUNITY_PUBLIC,
            'members_count' => 1,
            'active_members_count' => 1,
            'posts_count' => 0,
            'status' => Group::STATUS_ACTIVE,
            'health_score' => 60,
        ];
    }
}
