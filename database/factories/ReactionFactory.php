<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReactionFactory extends Factory
{
    protected $model = Reaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reactable_type' => Post::class,
            'reactable_id' => Post::factory(),
            'type' => 'like',
        ];
    }
}
