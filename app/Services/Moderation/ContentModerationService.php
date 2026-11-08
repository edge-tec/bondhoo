<?php

namespace App\Services\Moderation;

use App\Models\Comment;
use App\Models\Media;
use App\Models\ModerationAuditLog;
use App\Models\ModerationRequest;
use App\Models\ModerationResult;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ContentModerationService
{
    public function __construct(
        protected OpenAIModerationService $openAIService,
        protected LinkScanningService $linkScanner,
        protected ImageModerationService $imageScanner,
        protected VideoFrameModerationService $videoScanner,
        protected ModerationPolicyEngine $policyEngine
    ) {}

    /**
     * Moderate a feed post (text, embedded links, and attached media).
     */
    public function moderatePost(User $user, array $data, ?Post $post = null): ModerationRequest
    {
        $text = trim((string) ($data['content'] ?? ''));
        $mediaIds = (array) ($data['media_ids'] ?? []);
        $links = $this->linkScanner->extractUrls($text);

        $highestFlagged = false;
        $combinedCategories = [];
        $combinedScores = [];
        $error = null;

        // 1. Text Moderation
        if (! empty($text)) {
            $textResult = $this->openAIService->moderateText($text);
            $highestFlagged = $highestFlagged || $textResult['flagged'];
            $combinedCategories = array_merge($combinedCategories, $textResult['categories']);
            $combinedScores = array_merge($combinedScores, $textResult['category_scores']);
            if ($textResult['error']) {
                $error = $textResult['error'];
            }
        }

        // 2. Link Inspection (SSRF and Safe Content)
        foreach ($links as $link) {
            $linkResult = $this->linkScanner->scanUrl($link);
            if ($linkResult->is_ssrf_blocked || $linkResult->status === 'malicious') {
                $highestFlagged = true;
                $combinedCategories['illicit'] = true;
                $combinedScores['illicit'] = max($combinedScores['illicit'] ?? 0.0, 0.99);
            }
        }

        // 3. Media Moderation (if images attached)
        if (! empty($mediaIds)) {
            $mediaItems = Media::whereIn('id', $mediaIds)->get();
            foreach ($mediaItems as $media) {
                $filePath = storage_path('app/public/'.($media->file_path ?? ''));
                if (file_exists($filePath)) {
                    $mediaResult = $this->imageScanner->moderateImageFile($filePath);
                    $highestFlagged = $highestFlagged || $mediaResult['flagged'];
                    $combinedCategories = array_merge($combinedCategories, $mediaResult['categories']);
                    $combinedScores = array_merge($combinedScores, $mediaResult['category_scores']);
                }
            }
        }

        // 4. Policy Evaluation
        $eval = $this->policyEngine->evaluate([
            'flagged' => $highestFlagged,
            'categories' => $combinedCategories,
            'category_scores' => $combinedScores,
            'error' => $error,
        ], $user);

        // 5. Persist Request and Result
        $modRequest = ModerationRequest::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'moderatable_type' => Post::class,
            'moderatable_id' => $post?->id,
            'content_type' => (! empty($mediaIds) ? 'composite' : (! empty($links) ? 'link' : 'text')),
            'status' => $eval['decision'] === 'approve' ? ModerationRequest::STATUS_APPROVED :
                       ($eval['decision'] === 'quarantine' ? ModerationRequest::STATUS_QUARANTINED :
                       ($eval['decision'] === 'reject' ? ModerationRequest::STATUS_REJECTED : ModerationRequest::STATUS_MANUAL_REVIEW)),
            'model' => $this->openAIService->getModel(),
            'flagged' => $highestFlagged,
            'categories' => $combinedCategories,
            'category_scores' => $combinedScores,
            'action_taken' => $eval['action_taken'],
            'rejection_reason' => $eval['reason'],
            'error_message' => $error,
        ]);

        ModerationResult::create([
            'moderation_request_id' => $modRequest->id,
            'target_type' => Post::class,
            'target_id' => $post?->id,
            'flagged' => $highestFlagged,
            'categories' => $combinedCategories,
            'category_scores' => $combinedScores,
            'policy_decision' => $eval['decision'],
        ]);

        ModerationAuditLog::log(
            userId: $user->id,
            action: $eval['action_taken'],
            targetType: Post::class,
            targetId: $post?->id,
            reason: $eval['reason']
        );

        return $modRequest;
    }

    /**
     * Moderate a comment or reply before publication.
     */
    public function moderateComment(User $user, string $body, int $postId): ModerationRequest
    {
        $textResult = $this->openAIService->moderateText($body);
        $eval = $this->policyEngine->evaluate($textResult, $user);

        $modRequest = ModerationRequest::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'moderatable_type' => Comment::class,
            'moderatable_id' => null,
            'content_type' => 'text',
            'status' => $eval['decision'] === 'approve' ? ModerationRequest::STATUS_APPROVED :
                       ($eval['decision'] === 'quarantine' ? ModerationRequest::STATUS_QUARANTINED :
                       ($eval['decision'] === 'reject' ? ModerationRequest::STATUS_REJECTED : ModerationRequest::STATUS_MANUAL_REVIEW)),
            'model' => $this->openAIService->getModel(),
            'flagged' => $textResult['flagged'],
            'categories' => $textResult['categories'],
            'category_scores' => $textResult['category_scores'],
            'action_taken' => $eval['action_taken'],
            'rejection_reason' => $eval['reason'],
            'error_message' => $textResult['error'],
        ]);

        ModerationAuditLog::log(
            userId: $user->id,
            action: $eval['action_taken'],
            targetType: Comment::class,
            reason: $eval['reason']
        );

        return $modRequest;
    }

    /**
     * Moderate a story (text content or image attachment).
     */
    public function moderateStory(User $user, array $storyData, UploadedFile|string|null $mediaFile = null): ModerationRequest
    {
        $caption = (string) ($storyData['content'] ?? '');
        $highestFlagged = false;
        $combinedCategories = [];
        $combinedScores = [];
        $error = null;

        if (! empty($caption)) {
            $textResult = $this->openAIService->moderateText($caption);
            $highestFlagged = $highestFlagged || $textResult['flagged'];
            $combinedCategories = array_merge($combinedCategories, $textResult['categories']);
            $combinedScores = array_merge($combinedScores, $textResult['category_scores']);
            if ($textResult['error']) {
                $error = $textResult['error'];
            }
        }

        if ($mediaFile) {
            $imageResult = $this->imageScanner->moderateImageFile($mediaFile);
            $highestFlagged = $highestFlagged || $imageResult['flagged'];
            $combinedCategories = array_merge($combinedCategories, $imageResult['categories']);
            $combinedScores = array_merge($combinedScores, $imageResult['category_scores']);
        }

        $eval = $this->policyEngine->evaluate([
            'flagged' => $highestFlagged,
            'categories' => $combinedCategories,
            'category_scores' => $combinedScores,
            'error' => $error,
        ], $user);

        return ModerationRequest::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'moderatable_type' => Story::class,
            'content_type' => $mediaFile ? 'image' : 'text',
            'status' => $eval['decision'] === 'approve' ? ModerationRequest::STATUS_APPROVED :
                       ($eval['decision'] === 'quarantine' ? ModerationRequest::STATUS_QUARANTINED :
                       ($eval['decision'] === 'reject' ? ModerationRequest::STATUS_REJECTED : ModerationRequest::STATUS_MANUAL_REVIEW)),
            'model' => $this->openAIService->getModel(),
            'flagged' => $highestFlagged,
            'categories' => $combinedCategories,
            'category_scores' => $combinedScores,
            'action_taken' => $eval['action_taken'],
            'rejection_reason' => $eval['reason'],
            'error_message' => $error,
        ]);
    }

    /**
     * Moderate a Reel (caption, audio/title, video frames, thumbnail).
     */
    public function moderateReel(User $user, array $reelData, UploadedFile|string|null $videoFile = null): ModerationRequest
    {
        $caption = (string) ($reelData['caption'] ?? '');
        $highestFlagged = false;
        $combinedCategories = [];
        $combinedScores = [];
        $error = null;

        if (! empty($caption)) {
            $textResult = $this->openAIService->moderateText($caption);
            $highestFlagged = $highestFlagged || $textResult['flagged'];
            $combinedCategories = array_merge($combinedCategories, $textResult['categories']);
            $combinedScores = array_merge($combinedScores, $textResult['category_scores']);
            if ($textResult['error']) {
                $error = $textResult['error'];
            }
        }

        if ($videoFile) {
            $cover = $reelData['cover_image_path'] ?? null;
            $videoResult = $this->videoScanner->moderateVideo($videoFile, $cover);
            $highestFlagged = $highestFlagged || $videoResult['flagged'];
            $combinedCategories = array_merge($combinedCategories, $videoResult['categories']);
            $combinedScores = array_merge($combinedScores, $videoResult['category_scores']);
        }

        $eval = $this->policyEngine->evaluate([
            'flagged' => $highestFlagged,
            'categories' => $combinedCategories,
            'category_scores' => $combinedScores,
            'error' => $error,
        ], $user);

        return ModerationRequest::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'moderatable_type' => 'App\Models\Reel',
            'content_type' => 'video',
            'status' => $eval['decision'] === 'approve' ? ModerationRequest::STATUS_APPROVED :
                       ($eval['decision'] === 'quarantine' ? ModerationRequest::STATUS_QUARANTINED :
                       ($eval['decision'] === 'reject' ? ModerationRequest::STATUS_REJECTED : ModerationRequest::STATUS_MANUAL_REVIEW)),
            'model' => $this->openAIService->getModel(),
            'flagged' => $highestFlagged,
            'categories' => $combinedCategories,
            'category_scores' => $combinedScores,
            'action_taken' => $eval['action_taken'],
            'rejection_reason' => $eval['reason'],
            'error_message' => $error,
        ]);
    }

    /**
     * Moderate user profile avatar or cover photo before setting.
     */
    public function moderateProfileMedia(User $user, string $type, UploadedFile|string $file): ModerationRequest
    {
        $imageResult = $this->imageScanner->moderateImageFile($file);
        $eval = $this->policyEngine->evaluate($imageResult, $user);

        return ModerationRequest::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'moderatable_type' => User::class,
            'moderatable_id' => $user->id,
            'content_type' => 'image',
            'status' => $eval['decision'] === 'approve' ? ModerationRequest::STATUS_APPROVED :
                       ($eval['decision'] === 'quarantine' ? ModerationRequest::STATUS_QUARANTINED :
                       ($eval['decision'] === 'reject' ? ModerationRequest::STATUS_REJECTED : ModerationRequest::STATUS_MANUAL_REVIEW)),
            'model' => $this->openAIService->getModel(),
            'flagged' => $imageResult['flagged'],
            'categories' => $imageResult['categories'],
            'category_scores' => $imageResult['category_scores'],
            'action_taken' => $eval['action_taken'],
            'rejection_reason' => $eval['reason'],
            'error_message' => $imageResult['error'],
        ]);
    }
}
