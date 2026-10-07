<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;

class JugajugAIAssistant
{
    protected AIGateway $gateway;

    protected PromptManager $promptManager;

    public function __construct(AIGateway $gateway, PromptManager $promptManager)
    {
        $this->gateway = $gateway;
        $this->promptManager = $promptManager;
    }

    /**
     * Chat with conversational memory per user/session.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function chat(string $message, ?int $userId = null, ?string $conversationId = null, array $options = []): array
    {
        $convKey = 'ai:memory:'.($conversationId ?? ($userId ? 'user_'.$userId : 'anon_'.session()->getId()));

        $history = Cache::get($convKey, []);
        $history[] = ['role' => 'user', 'content' => $message];

        // Format conversational prompt with last 5 turns
        $contextLines = [];
        $recentTurns = array_slice($history, -6);
        foreach ($recentTurns as $turn) {
            $contextLines[] = ($turn['role'] === 'user' ? 'User' : 'Assistant').': '.$turn['content'];
        }

        $systemPrompt = 'You are Bondhoo AI (বন্ধু এআই), an empathetic, smart, multilingual social assistant for Bangladesh. Support Bengali and English gracefully.';
        $fullPrompt = implode("\n", $contextLines);

        $options['system_prompt'] = $systemPrompt;
        $options['operation'] = 'chat';

        $result = $this->gateway->complete($fullPrompt, $options, $userId);

        $history[] = ['role' => 'assistant', 'content' => $result['content']];
        // Retain 1-day conversation memory
        Cache::put($convKey, array_slice($history, -10), 86400);

        return [
            'reply' => $result['content'],
            'model' => $result['model'],
            'provider' => $result['provider'],
            'tokens' => $result['total_tokens'],
            'latency_ms' => $result['latency_ms'],
            'conversation_id' => $conversationId ?? $convKey,
        ];
    }

    /**
     * AI smart comment suggestion for social posts.
     */
    public function suggestComment(string $postText, ?int $userId = null): array
    {
        $prompt = $this->promptManager->render('smart_comment', ['post_text' => $postText]);

        return $this->gateway->complete($prompt, ['operation' => 'comment_writer'], $userId);
    }

    /**
     * AI social media caption writer with tone adjustments.
     */
    public function writeCaption(string $topic, string $tone = 'friendly', ?int $userId = null): array
    {
        $prompt = $this->promptManager->render('caption_writer', ['topic' => $topic, 'tone' => $tone]);

        return $this->gateway->complete($prompt, ['operation' => 'caption_writer'], $userId);
    }

    /**
     * AI Translation between Bengali and English.
     */
    public function translate(string $text, string $targetLanguage = 'bn', ?int $userId = null): array
    {
        $targetName = ($targetLanguage === 'bn' || $targetLanguage === 'bengali') ? 'Bengali (বাংলা)' : 'English';
        $prompt = "Translate the following social content accurately into natural {$targetName} preserving tone and emojis:\n\n{$text}";

        return $this->gateway->complete($prompt, ['operation' => 'translation'], $userId);
    }

    /**
     * AI Summarizer for long posts or group discussions.
     */
    public function summarize(string $content, ?int $userId = null): array
    {
        $prompt = $this->promptManager->render('post_summary', ['content' => $content]);

        return $this->gateway->complete($prompt, ['operation' => 'summarize'], $userId);
    }

    /**
     * AI Content moderation for hate speech, harassment, and misinformation.
     *
     * @return array{is_safe: bool, flags: string[], reason: string}
     */
    public function moderate(string $content, ?int $userId = null): array
    {
        $prompt = $this->promptManager->render('feed_moderation', ['content' => $content]);
        $result = $this->gateway->complete($prompt, ['operation' => 'moderation'], $userId);

        // Simple heuristic fallback if model does not output JSON
        $text = strtolower($result['content']);
        $badWords = ['kill', 'hate', 'attack', 'scam', 'বোমা', 'জঙ্গি'];
        $flags = [];

        foreach ($badWords as $bw) {
            if (str_contains(strtolower($content), $bw)) {
                $flags[] = 'restricted_keyword: '.$bw;
            }
        }

        $isSafe = empty($flags) && ! str_contains($text, '"is_safe": false');

        return [
            'is_safe' => $isSafe,
            'flags' => $flags,
            'reason' => $isSafe ? 'Content complies with community standards' : 'Flagged by safety filter',
            'raw' => $result['content'],
        ];
    }

    /**
     * Bangladesh Government Citizen Services e-Assistant.
     */
    public function citizenServicesAssistant(string $inquiry, ?int $userId = null): array
    {
        $prompt = $this->promptManager->render('citizen_assistant', ['inquiry' => $inquiry]);

        return $this->gateway->complete($prompt, ['operation' => 'citizen_assistant'], $userId);
    }
}
