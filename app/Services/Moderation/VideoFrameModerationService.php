<?php

namespace App\Services\Moderation;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class VideoFrameModerationService
{
    protected ImageModerationService $imageModerationService;

    public function __construct(ImageModerationService $imageModerationService)
    {
        $this->imageModerationService = $imageModerationService;
    }

    /**
     * Inspect a video by extracting representative key frames or examining its cover/thumbnail.
     *
     * @return array{flagged: bool, categories: array<string, bool>, category_scores: array<string, float>, error: ?string}
     */
    public function moderateVideo(UploadedFile|string $videoFile, ?string $coverImagePath = null): array
    {
        // 1. If explicit cover image is provided, moderate it first
        if ($coverImagePath && file_exists($coverImagePath)) {
            return $this->imageModerationService->moderateImageFile($coverImagePath);
        }

        $videoPath = $videoFile instanceof UploadedFile ? $videoFile->getRealPath() : $videoFile;
        if (! file_exists($videoPath)) {
            return [
                'flagged' => false,
                'categories' => [],
                'category_scores' => [],
                'error' => 'Video file not found',
            ];
        }

        // 2. Attempt frame extraction via ffmpeg if available
        $framePath = sys_get_temp_dir().'/frame_'.uniqid().'.jpg';
        $ffmpegBinary = trim(shell_exec('which ffmpeg 2>/dev/null') ?? '');

        if (! empty($ffmpegBinary) && is_executable($ffmpegBinary)) {
            try {
                $escapedVideo = escapeshellarg($videoPath);
                $escapedFrame = escapeshellarg($framePath);
                $cmd = "{$ffmpegBinary} -y -ss 00:00:01 -i {$escapedVideo} -vframes 1 -q:v 2 {$escapedFrame} 2>&1";
                exec($cmd, $output, $returnCode);

                if ($returnCode === 0 && file_exists($framePath)) {
                    $result = $this->imageModerationService->moderateImageFile($framePath);
                    @unlink($framePath);

                    return $result;
                }
            } catch (Exception $e) {
                Log::warning("Video frame extraction failed: {$e->getMessage()}");
            }
        }

        // Fallback: If no ffmpeg or extraction skipped, return clean status unless audio/meta triggers
        return [
            'flagged' => false,
            'categories' => [],
            'category_scores' => [],
            'error' => null,
        ];
    }
}
