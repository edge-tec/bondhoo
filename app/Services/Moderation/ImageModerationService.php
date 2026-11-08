<?php

namespace App\Services\Moderation;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImageModerationService
{
    protected OpenAIModerationService $openAIService;

    public function __construct(OpenAIModerationService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Inspect and moderate an uploaded image or disk path.
     *
     * @return array{flagged: bool, categories: array<string, bool>, category_scores: array<string, float>, error: ?string}
     */
    public function moderateImageFile(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! file_exists($path) || ! is_readable($path)) {
            return [
                'flagged' => false,
                'categories' => [],
                'category_scores' => [],
                'error' => 'Image file not found or not readable',
            ];
        }

        // Validate MIME type and Magic Bytes
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (! in_array($mime, $allowedMimes, true)) {
            return [
                'flagged' => true,
                'categories' => ['illicit' => true],
                'category_scores' => ['illicit' => 1.0],
                'error' => 'Invalid image MIME type: '.$mime,
            ];
        }

        // Read and Base64 encode for API input
        try {
            $imageData = file_get_contents($path);
            if ($imageData === false || strlen($imageData) === 0) {
                return [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                    'error' => 'Empty image file payload',
                ];
            }

            $base64 = base64_encode($imageData);
            $dataUri = "data:{$mime};base64,{$base64}";

            return $this->openAIService->moderateImage($dataUri);
        } catch (Exception $e) {
            Log::error("Failed to moderate image: {$e->getMessage()}");

            return [
                'flagged' => false,
                'categories' => [],
                'category_scores' => [],
                'error' => $e->getMessage(),
            ];
        }
    }
}
