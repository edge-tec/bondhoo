<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mediable_type' => null,
            'mediable_id' => null,
            'collection' => 'posts',
            'disk' => 'public',
            'original_path' => 'media/sample.jpg',
            'mime_type' => 'image/jpeg',
            'width' => 1200,
            'height' => 800,
            'size' => 102400,
            'processing_status' => 'completed',
        ];
    }
}
