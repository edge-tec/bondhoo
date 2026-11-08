<?php

namespace App\Services\Moderation;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIModerationService
{
    protected string $apiKey;

    protected string $baseUrl;

    protected string $model;

    protected int $timeout;

    protected int $retryCount;

    protected int $retrySleepMs;

    protected bool $enabled;

    public function __construct()
    {
        $this->apiKey = (string) config('moderation.api_key', env('OPENAI_API_KEY', ''));
        $this->baseUrl = rtrim((string) config('moderation.base_url', env('OPENAI_BASE_URL', 'https://api.openai.com/v1')), '/');
        $this->model = (string) config('moderation.model', env('OPENAI_MODERATION_MODEL', 'omni-moderation-latest'));
        $this->timeout = (int) config('moderation.timeout', env('OPENAI_MODERATION_TIMEOUT', 10));
        $this->retryCount = (int) config('moderation.retry_count', env('OPENAI_MODERATION_RETRY_COUNT', 3));
        $this->retrySleepMs = (int) config('moderation.retry_sleep_ms', env('OPENAI_MODERATION_RETRY_SLEEP_MS', 500));
        $this->enabled = (bool) config('moderation.enabled', env('OPENAI_MODERATION_ENABLED', true));
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && $this->enabled;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Moderate text content using OpenAI Moderation API.
     *
     * @return array{flagged: bool, categories: array<string, bool>, category_scores: array<string, float>, raw: ?array, model: string, error: ?string}
     */
    public function moderateText(string $text): array
    {
        return $this->moderateInput($text);
    }

    /**
     * Moderate image content (URL or base64 data URI).
     *
     * @return array{flagged: bool, categories: array<string, bool>, category_scores: array<string, float>, raw: ?array, model: string, error: ?string}
     */
    public function moderateImage(string $imageUrlOrDataUri): array
    {
        $input = [
            [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageUrlOrDataUri,
                ],
            ],
        ];

        return $this->moderateInput($input);
    }

    /**
     * Moderate multi-modal input (text & images).
     *
     * @return array{flagged: bool, categories: array<string, bool>, category_scores: array<string, float>, raw: ?array, model: string, error: ?string}
     */
    public function moderateInput(string|array $input): array
    {
        if (! $this->enabled) {
            return $this->createApprovedResponse('Moderation disabled by configuration');
        }

        // In test or local environment when no API key is set, handle gracefully
        if (empty($this->apiKey)) {
            if (app()->environment('testing', 'local')) {
                return $this->simulateLocalModeration($input);
            }

            Log::warning('OpenAI Content Moderation requested but OPENAI_API_KEY is not configured.');

            return $this->createErrorResponse('OPENAI_API_KEY is not configured');
        }

        $endpoint = "{$this->baseUrl}/moderations";
        $payload = [
            'model' => $this->model,
            'input' => $input,
        ];

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryCount) {
            $attempt++;
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout($this->timeout)
                    ->acceptJson()
                    ->post($endpoint, $payload);

                if ($response->successful()) {
                    $json = $response->json();
                    $result = $json['results'][0] ?? null;

                    if ($result && isset($result['categories'], $result['category_scores'])) {
                        return [
                            'flagged' => (bool) ($result['flagged'] ?? false),
                            'categories' => (array) ($result['categories'] ?? []),
                            'category_scores' => (array) ($result['category_scores'] ?? []),
                            'raw' => $json,
                            'model' => $json['model'] ?? $this->model,
                            'error' => null,
                        ];
                    }
                }

                // If rate limited (429), respect Retry-After header
                if ($response->status() === 429) {
                    $retryAfter = (int) $response->header('Retry-After', 1);
                    Log::warning("OpenAI Moderation rate limit encountered (attempt {$attempt}/{$this->retryCount}). Waiting {$retryAfter}s.");
                    if ($attempt < $this->retryCount) {
                        usleep(max($retryAfter * 1000000, $this->retrySleepMs * 1000 * $attempt));

                        continue;
                    }
                }

                // If 5xx server error, retry with exponential backoff
                if ($response->serverError() && $attempt < $this->retryCount) {
                    usleep($this->retrySleepMs * 1000 * (2 ** ($attempt - 1)));

                    continue;
                }

                Log::error("OpenAI Moderation API error response ({$response->status()}): ".substr($response->body(), 0, 300));

                return $this->createErrorResponse("OpenAI API error ({$response->status()}): ".substr($response->body(), 0, 200));

            } catch (ConnectionException|RequestException $e) {
                $lastException = $e;
                Log::warning("OpenAI Moderation connection error (attempt {$attempt}/{$this->retryCount}): {$e->getMessage()}");
                if ($attempt < $this->retryCount) {
                    usleep($this->retrySleepMs * 1000 * (2 ** ($attempt - 1)));
                }
            } catch (Exception $e) {
                Log::error("Unexpected error during OpenAI Moderation request: {$e->getMessage()}");

                return $this->createErrorResponse($e->getMessage());
            }
        }

        $errorMsg = $lastException ? $lastException->getMessage() : 'Exhausted retry attempts without successful response';

        return $this->createErrorResponse($errorMsg);
    }

    /**
     * Simulate local moderation for tests or offline local environments when key is omitted.
     */
    protected function simulateLocalModeration(string|array $input): array
    {
        $text = is_string($input) ? $input : json_encode($input);
        $lower = strtolower($text);

        $flagged = false;
        $categories = [
            'sexual' => false,
            'sexual/minors' => false,
            'hate' => false,
            'hate/threatening' => false,
            'harassment' => false,
            'harassment/threatening' => false,
            'self-harm' => false,
            'self-harm/intent' => false,
            'self-harm/instructions' => false,
            'violence' => false,
            'violence/graphic' => false,
            'illicit' => false,
            'illicit/violent' => false,
        ];
        $scores = array_fill_keys(array_keys($categories), 0.0001);

        if (str_contains($lower, 'explicit adult') || str_contains($lower, 'nsfw') || str_contains($lower, 'pornography')) {
            $flagged = true;
            $categories['sexual'] = true;
            $scores['sexual'] = 0.98;
        }

        if (str_contains($lower, 'csam') || str_contains($lower, 'child exploitation') || str_contains($lower, 'child abuse')) {
            $flagged = true;
            $categories['sexual/minors'] = true;
            $scores['sexual/minors'] = 0.99;
        }

        if (str_contains($lower, 'hate speech') || str_contains($lower, 'ethnic cleansing') || str_contains($lower, 'kill all')) {
            $flagged = true;
            $categories['hate'] = true;
            $categories['hate/threatening'] = true;
            $scores['hate'] = 0.95;
            $scores['hate/threatening'] = 0.92;
        }

        if (str_contains($lower, 'commit suicide') || str_contains($lower, 'kill yourself') || str_contains($lower, 'cut my wrists')) {
            $flagged = true;
            $categories['self-harm'] = true;
            $categories['self-harm/intent'] = true;
            $scores['self-harm'] = 0.96;
            $scores['self-harm/intent'] = 0.94;
        }

        if (str_contains($lower, 'bomb explosion') || str_contains($lower, 'massacre') || str_contains($lower, 'behead')) {
            $flagged = true;
            $categories['violence'] = true;
            $categories['violence/graphic'] = true;
            $scores['violence'] = 0.95;
            $scores['violence/graphic'] = 0.93;
        }

        return [
            'flagged' => $flagged,
            'categories' => $categories,
            'category_scores' => $scores,
            'raw' => ['simulated' => true],
            'model' => $this->model,
            'error' => null,
        ];
    }

    protected function createApprovedResponse(string $reason = 'Approved'): array
    {
        $categories = [
            'sexual' => false,
            'sexual/minors' => false,
            'hate' => false,
            'hate/threatening' => false,
            'harassment' => false,
            'harassment/threatening' => false,
            'self-harm' => false,
            'self-harm/intent' => false,
            'self-harm/instructions' => false,
            'violence' => false,
            'violence/graphic' => false,
            'illicit' => false,
            'illicit/violent' => false,
        ];

        return [
            'flagged' => false,
            'categories' => $categories,
            'category_scores' => array_fill_keys(array_keys($categories), 0.0),
            'raw' => ['note' => $reason],
            'model' => $this->model,
            'error' => null,
        ];
    }

    protected function createErrorResponse(string $error): array
    {
        return [
            'flagged' => false,
            'categories' => [],
            'category_scores' => [],
            'raw' => null,
            'model' => $this->model,
            'error' => $error,
        ];
    }
}
