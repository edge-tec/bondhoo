<?php

namespace App\Services\AI;

use App\Models\AiPrompt;
use Illuminate\Support\Facades\Cache;

class PromptManager
{
    /**
     * Get hydrated prompt template by name and version.
     *
     * @param  array<string, mixed>  $variables
     */
    public function render(string $name, array $variables = [], ?string $version = null): string
    {
        $cacheKey = "ai:prompt:{$name}:".($version ?? 'latest');

        $prompt = Cache::remember($cacheKey, 3600, function () use ($name, $version) {
            $query = AiPrompt::where('name', $name)->where('is_active', true);
            if ($version) {
                $query->where('version', $version);
            } else {
                $query->latest('id');
            }

            return $query->first();
        });

        if (! $prompt) {
            // Default built-in fallback prompt templates
            $templates = [
                'feed_moderation' => 'Analyze the following social post text for hate speech, violence, harassment, or misinformation in Bengali or English: "{{content}}". Output JSON: {"is_safe": bool, "flags": [], "reason": string}',
                'smart_comment' => 'Suggest a thoughtful, polite Bengali or English comment response to: "{{post_text}}". Keep it under 2 sentences.',
                'caption_writer' => 'Generate 3 engaging social media captions with relevant hashtags for a post about: "{{topic}}". Tone: {{tone}}.',
                'post_summary' => 'Summarize the following discussion or article in 3 bullet points: "{{content}}".',
                'citizen_assistant' => 'You are the Bondhoo Citizen AI Assistant for Bangladesh e-Government services (NID, Passport, Driving License, Birth Registration). Provide accurate, step-by-step guidance for inquiry: "{{inquiry}}".',
            ];

            $template = $templates[$name] ?? '{{content}}';
        } else {
            $template = $prompt->template;
        }

        foreach ($variables as $key => $value) {
            $template = str_replace("{{{$key}}}", (string) $value, $template);
        }

        return $template;
    }

    /**
     * Create or update a versioned prompt.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function storePrompt(string $name, string $template, string $category = 'general', string $version = '1.0.0', array $parameters = []): AiPrompt
    {
        $prompt = AiPrompt::create([
            'name' => $name,
            'template' => $template,
            'category' => $category,
            'version' => $version,
            'parameters' => $parameters,
            'is_active' => true,
        ]);

        Cache::forget("ai:prompt:{$name}:latest");
        Cache::forget("ai:prompt:{$name}:{$version}");

        return $prompt;
    }
}
