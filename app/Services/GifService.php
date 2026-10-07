<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GifService
{
    /**
     * Curated catalog of high-quality animated GIFs/WebP across popular social reaction categories.
     */
    protected array $catalog = [
        [
            'id' => 'gif-happy-dance',
            'title' => 'Happy Dance',
            'category' => 'happy',
            'url' => 'https://media.giphy.com/media/artj92V8o75VPL7AeQ/giphy.gif',
            'preview' => 'https://media.giphy.com/media/artj92V8o75VPL7AeQ/200w.gif',
            'width' => 480,
            'height' => 480,
        ],
        [
            'id' => 'gif-celebrate-confetti',
            'title' => 'Celebration Confetti',
            'category' => 'celebrate',
            'url' => 'https://media.giphy.com/media/26tOZ42Mg6pbTUPHW/giphy.gif',
            'preview' => 'https://media.giphy.com/media/26tOZ42Mg6pbTUPHW/200w.gif',
            'width' => 480,
            'height' => 360,
        ],
        [
            'id' => 'gif-thumbs-up',
            'title' => 'Awesome Thumbs Up',
            'category' => 'reactions',
            'url' => 'https://media.giphy.com/media/111ebonMs90YLu/giphy.gif',
            'preview' => 'https://media.giphy.com/media/111ebonMs90YLu/200w.gif',
            'width' => 480,
            'height' => 270,
        ],
        [
            'id' => 'gif-love-hearts',
            'title' => 'Sending Love & Hearts',
            'category' => 'love',
            'url' => 'https://media.giphy.com/media/M90mJvfWfd5mbUuULX/giphy.gif',
            'preview' => 'https://media.giphy.com/media/M90mJvfWfd5mbUuULX/200w.gif',
            'width' => 480,
            'height' => 360,
        ],
        [
            'id' => 'gif-coding-fast',
            'title' => 'Coding Hacker Mode',
            'category' => 'working',
            'url' => 'https://media.giphy.com/media/LmN8OYiY4m0X85K0Zz/giphy.gif',
            'preview' => 'https://media.giphy.com/media/LmN8OYiY4m0X85K0Zz/200w.gif',
            'width' => 480,
            'height' => 270,
        ],
        [
            'id' => 'gif-applause-clapping',
            'title' => 'Standing Ovation & Applause',
            'category' => 'reactions',
            'url' => 'https://media.giphy.com/media/nbvFVPiEiJH6JOGIok/giphy.gif',
            'preview' => 'https://media.giphy.com/media/nbvFVPiEiJH6JOGIok/200w.gif',
            'width' => 480,
            'height' => 360,
        ],
        [
            'id' => 'gif-coffee-vibes',
            'title' => 'Morning Coffee Energy',
            'category' => 'working',
            'url' => 'https://media.giphy.com/media/3oKIPnAiaMCws8nOsE/giphy.gif',
            'preview' => 'https://media.giphy.com/media/3oKIPnAiaMCws8nOsE/200w.gif',
            'width' => 480,
            'height' => 360,
        ],
        [
            'id' => 'gif-funny-cat',
            'title' => 'Laughing Cat',
            'category' => 'funny',
            'url' => 'https://media.giphy.com/media/JIX9t2j0ZTN9S/giphy.gif',
            'preview' => 'https://media.giphy.com/media/JIX9t2j0ZTN9S/200w.gif',
            'width' => 480,
            'height' => 360,
        ],
        [
            'id' => 'gif-fire-lit',
            'title' => 'This is Fire / Lit',
            'category' => 'reactions',
            'url' => 'https://media.giphy.com/media/Lopx9eUi34rbq/giphy.gif',
            'preview' => 'https://media.giphy.com/media/Lopx9eUi34rbq/200w.gif',
            'width' => 480,
            'height' => 270,
        ],
        [
            'id' => 'gif-mind-blown',
            'title' => 'Mind Blown Reaction',
            'category' => 'reactions',
            'url' => 'https://media.giphy.com/media/26ufdipQqU2lhNA4g/giphy.gif',
            'preview' => 'https://media.giphy.com/media/26ufdipQqU2lhNA4g/200w.gif',
            'width' => 480,
            'height' => 270,
        ],
    ];

    /**
     * Search GIFs by query and category.
     */
    public function search(?string $query = null, ?string $category = null, int $limit = 20): array
    {
        $giphyKey = config('services.giphy.key') ?: env('GIPHY_API_KEY');
        if (! empty($giphyKey)) {
            try {
                $endpoint = ! empty($query) ? 'https://api.giphy.com/v1/gifs/search' : 'https://api.giphy.com/v1/gifs/trending';
                $params = [
                    'api_key' => $giphyKey,
                    'limit' => $limit,
                    'rating' => 'g',
                ];
                if (! empty($query)) {
                    $params['q'] = $query;
                }

                $res = Http::timeout(4)->get($endpoint, $params);
                if ($res->successful()) {
                    $data = $res->json('data', []);
                    $results = [];
                    foreach ($data as $item) {
                        $results[] = [
                            'id' => $item['id'],
                            'title' => $item['title'] ?? 'GIF',
                            'category' => $category ?? 'general',
                            'url' => $item['images']['original']['url'] ?? '',
                            'preview' => $item['images']['fixed_width']['url'] ?? ($item['images']['original']['url'] ?? ''),
                            'width' => (int) ($item['images']['original']['width'] ?? 400),
                            'height' => (int) ($item['images']['original']['height'] ?? 300),
                        ];
                    }
                    if (! empty($results)) {
                        return $results;
                    }
                }
            } catch (\Throwable $e) {
                Log::info("Giphy API query failed, falling back to curated library: {$e->getMessage()}");
            }
        }

        // Search catalog
        $results = $this->catalog;

        if (! empty($category) && $category !== 'all' && $category !== 'trending') {
            $results = array_filter($results, fn ($g) => $g['category'] === $category);
        }

        if (! empty($query)) {
            $q = mb_strtolower(trim($query));
            $results = array_filter($results, function ($g) use ($q) {
                return str_contains(mb_strtolower($g['title']), $q) ||
                       str_contains(mb_strtolower($g['category']), $q);
            });
        }

        return array_values(array_slice($results, 0, $limit));
    }

    /**
     * Get available GIF categories.
     */
    public function getCategories(): array
    {
        return [
            ['id' => 'trending', 'name' => '🔥 ট্রেন্ডিং', 'icon' => '🔥'],
            ['id' => 'reactions', 'name' => '😮 প্রতিক্রিয়া', 'icon' => '😮'],
            ['id' => 'happy', 'name' => '😊 আনন্দ', 'icon' => '😊'],
            ['id' => 'celebrate', 'name' => '🎉 উদযাপন', 'icon' => '🎉'],
            ['id' => 'love', 'name' => '❤️ ভালোবাসা', 'icon' => '❤️'],
            ['id' => 'working', 'name' => '💻 কাজ ও কোডিং', 'icon' => '💻'],
            ['id' => 'funny', 'name' => '😂 হাস্যকর', 'icon' => '😂'],
        ];
    }
}
