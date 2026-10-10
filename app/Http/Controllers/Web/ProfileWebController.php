<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use App\Models\Story;
use App\Models\User;
use App\Models\UserFollower;
use App\Models\UsernameHistory;
use App\Models\UserProfile;
use App\Services\ProfileAnalyticsService;
use App\Services\ProfileCompletionService;
use App\Services\ProfileEnterpriseService;
use App\Services\ProfileService;
use App\Services\ProfileVerificationService;
use App\Services\ReelEnterpriseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileWebController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Show the user profile page.
     */
    public function show(Request $request, string $username): View|RedirectResponse
    {
        $cleanUsername = ltrim(trim($username), '@');
        if (str_starts_with($username, '@')) {
            return redirect()->route('profile.u', ['username' => $cleanUsername], 301);
        }

        $viewer = $this->resolveViewer($request);

        $targetUser = User::where('username', strtolower($cleanUsername))->first();

        if (! $targetUser && is_numeric($cleanUsername)) {
            $targetUser = User::find((int) $cleanUsername);
            if ($targetUser) {
                return redirect()->route('profile.u', ['username' => $targetUser->username], 301);
            }
        }

        if (! $targetUser) {
            $history = UsernameHistory::where('username', strtolower($cleanUsername))->latest('id')->first();
            if ($history && $history->user) {
                return redirect()->route('profile.u', ['username' => $history->user->username], 301);
            }
            abort(404, 'প্রোফাইলটি খুঁজে পাওয়া যায়নি।');
        }

        // STRICT IDENTITY ASSERTION: Never auto-login or swap viewer identity to target user!
        $isOwner = $viewer && (int) $viewer->id === (int) $targetUser->id;
        $isViewAsPublic = false;
        $effectiveViewer = $viewer;

        if ($request->query('view_as') === 'public' && $isOwner) {
            $isViewAsPublic = true;
            $effectiveViewer = null;
        }

        $rawProfile = $this->profileService->getProfileByUsername($username, $effectiveViewer);
        $timeline = $this->profileService->getUserTimeline($targetUser, $effectiveViewer, 10);
        $photos = $this->profileService->getUserPhotos($targetUser, $effectiveViewer, 9);
        $videos = $this->profileService->getUserVideos($targetUser, $effectiveViewer, 6);
        $friendsData = $this->profileService->getUserFriends($targetUser, $effectiveViewer, 100);

        // Record profile view analytics (if not own profile, with anti-inflation & queue)
        if (! $isViewAsPublic && (! $viewer || $viewer->id !== $targetUser->id)) {
            app(ProfileAnalyticsService::class)->recordView($targetUser, $viewer, $request);
        }

        $defaultProfile = [
            'bio' => null,
            'about' => null,
            'work' => null,
            'education' => null,
            'city' => null,
            'hometown' => null,
            'relationship_status' => null,
            'website' => null,
            'avatar_url' => null,
            'cover_url' => null,
            'social_links' => [],
        ];

        $isOwnerForView = $isViewAsPublic ? false : $isOwner;

        $completionData = app(ProfileCompletionService::class)->getCompletion($targetUser);
        $verificationEligibility = $isOwnerForView ? app(ProfileVerificationService::class)->checkEligibility($targetUser) : null;
        $analyticsData = $isOwnerForView ? app(ProfileAnalyticsService::class)->getDashboardAnalytics($targetUser, '30d') : null;
        $privacySettings = $isOwnerForView ? $targetUser->privacySettings()->firstOrCreate(['user_id' => $targetUser->id]) : null;
        $notificationSettings = $isOwnerForView ? $targetUser->notificationSettings()->firstOrCreate(['user_id' => $targetUser->id]) : null;

        $avatarUrl = UserProfile::normalizeStorageUrl($rawProfile['profile']['avatar_url'] ?? null);
        if ($avatarUrl) {
            $parsedPath = parse_url($avatarUrl, PHP_URL_PATH) ?: $avatarUrl;
            if (str_starts_with($parsedPath, '/storage/')) {
                $relative = substr($parsedPath, strlen('/storage/'));
                $diskPath = storage_path('app/public/'.$relative);
                if (! file_exists($diskPath)) {
                    $privatePath = storage_path('app/private/'.$relative);
                    if (file_exists($privatePath)) {
                        @mkdir(dirname($diskPath), 0755, true);
                        @copy($privatePath, $diskPath);
                    } else {
                        $avatarUrl = null;
                    }
                }
            }
        }

        $coverUrl = UserProfile::normalizeStorageUrl($rawProfile['profile']['cover_url'] ?? null);
        if ($coverUrl) {
            $parsedPath = parse_url($coverUrl, PHP_URL_PATH) ?: $coverUrl;
            if (str_starts_with($parsedPath, '/storage/')) {
                $relative = substr($parsedPath, strlen('/storage/'));
                $diskPath = storage_path('app/public/'.$relative);
                if (! file_exists($diskPath)) {
                    $privatePath = storage_path('app/private/'.$relative);
                    if (file_exists($privatePath)) {
                        @mkdir(dirname($diskPath), 0755, true);
                        @copy($privatePath, $diskPath);
                    } else {
                        $coverUrl = null;
                    }
                }
            }
        }

        $viewProfile = array_merge($defaultProfile, $rawProfile['profile'] ?? [], [
            'id' => $targetUser->id,
            'name' => $rawProfile['user']['name'] ?? $targetUser->name,
            'username' => $rawProfile['user']['username'] ?? $targetUser->username,
            'avatar' => $avatarUrl ?: '/images/default-avatar.svg',
            'cover_photo' => $coverUrl ?: '/images/default-cover.svg',
            'is_verified' => $rawProfile['user']['is_verified'] ?? false,
            'verification_status' => $targetUser->verification_status ?? 'UNVERIFIED',
            'is_profile_locked' => $rawProfile['is_profile_locked'] ?? false,
            'has_avatar_guard' => (bool) ($targetUser->settings?->has_avatar_guard ?? false),
            'is_owner' => $isOwnerForView,
            'is_friend' => $rawProfile['is_friend'] ?? false,
            'friendship_status' => $rawProfile['friendship_status'] ?? null,
            'is_following' => $viewer ? UserFollower::where('user_id', $targetUser->id)->where('follower_id', $viewer->id)->exists() : false,
            'is_followed_by_target' => $viewer ? UserFollower::where('user_id', $viewer->id)->where('follower_id', $targetUser->id)->exists() : false,
            'mutual_friends_count' => $rawProfile['stats']['mutual_friends_count'] ?? 0,
            'friends_count' => $rawProfile['stats']['friends_count'] ?? 0,
            'followers_count' => $rawProfile['stats']['followers_count'] ?? 0,
            'following_count' => $rawProfile['stats']['following_count'] ?? 0,
            'posts_count' => $rawProfile['stats']['posts_count'] ?? 0,
            'photos_count' => count($photos),
            'videos_count' => count($videos),
            'member_since' => $targetUser->created_at?->format('F Y'),
            'locked_view' => $rawProfile['is_locked_view'] ?? false,
            'sections' => $rawProfile['sections'] ?? [],
            'pronouns' => $targetUser->profile->pronouns ?? null,
            'category' => $targetUser->profile->category ?? null,
            'middle_name' => $targetUser->profile->middle_name ?? null,
            'religion' => $targetUser->profile->religion ?? null,
            'blood_group' => $targetUser->profile->blood_group ?? null,
            'division' => $targetUser->profile->division ?? null,
            'district' => $targetUser->profile->district ?? null,
            'upazila' => $targetUser->profile->upazila ?? null,
            'portfolio' => $targetUser->profile->portfolio ?? null,
            'whatsapp' => $targetUser->profile->whatsapp ?? null,
            'telegram' => $targetUser->profile->telegram ?? null,
            'signal' => $targetUser->profile->signal ?? null,
            'messenger' => $targetUser->profile->messenger ?? null,
            'hobbies' => $targetUser->profile->hobbies ?? [],
            'favorite_music' => $targetUser->profile->favorite_music ?? [],
            'favorite_books' => $targetUser->profile->favorite_books ?? [],
            'favorite_movies' => $targetUser->profile->favorite_movies ?? [],
            'is_professional_mode' => (bool) ($targetUser->profile->is_professional_mode ?? false),
        ]);

        $enterpriseService = app(ProfileEnterpriseService::class);
        $albums = $enterpriseService->getAlbums($targetUser);
        $highlights = $enterpriseService->getHighlights($targetUser);
        $professionalAnalytics = $enterpriseService->getProfessionalAnalytics($targetUser);

        $savedItems = $isOwnerForView ? $enterpriseService->getSavedItems($targetUser) : collect();
        $activityLogs = $isOwnerForView ? $enterpriseService->getActivityLogs($targetUser, null, 20) : null;
        $avatarHistory = $isOwnerForView ? $enterpriseService->getAvatarHistory($targetUser) : collect();
        $coverHistory = $isOwnerForView ? $enterpriseService->getCoverHistory($targetUser) : collect();
        $friendSuggestions = $enterpriseService->getFriendSuggestions($viewer ?? $targetUser, 8);
        $followers = $targetUser->followers()->with('profile')->take(50)->get();
        $following = $targetUser->following()->with('profile')->take(50)->get();

        $authToken = '';
        if ($viewer) {
            $existingToken = (string) $request->cookie('jugajug_token');
            if ($existingToken) {
                $pat = PersonalAccessToken::findToken($existingToken);
                if ($pat && (int) $pat->tokenable_id === (int) $viewer->id) {
                    $authToken = $existingToken;
                }
            }

            if (! $authToken) {
                $authToken = $viewer->createToken('web_profile')->plainTextToken;
                cookie()->queue(cookie('jugajug_token', $authToken, 60 * 24 * 30, '/', null, false, false));
            }
        }

        $activeLiveStream = LiveStream::where('user_id', $targetUser->id)
            ->where('status', LiveStream::STATUS_LIVE)
            ->latest('id')
            ->first();
        if ($activeLiveStream && ! $activeLiveStream->canView($viewer)) {
            $activeLiveStream = null;
        }

        $activeStoriesQuery = Story::where('user_id', $targetUser->id)
            ->active();

        if (! $viewer || $viewer->id !== $targetUser->id) {
            $isFriend = $viewer && $targetUser->isFriendWith($viewer);
            if ($isFriend) {
                $activeStoriesQuery->whereIn('privacy', ['public', 'friends']);
            } else {
                $activeStoriesQuery->where('privacy', 'public');
            }
        }

        $activeStories = $activeStoriesQuery
            ->with(['user.profile', 'media', 'storyMedia.media', 'views', 'reactions', 'musicTrack'])
            ->latest('id')
            ->get();
        $activeStoriesData = $activeStories->map(fn (Story $s) => $s->toResponseArray($viewer))->values()->toArray();
        $hasActiveStory = ! empty($activeStoriesData);

        $activeReels = app(ReelEnterpriseService::class)->getUserReels($targetUser, $effectiveViewer, 24)->items();

        return view('profile.show', [
            'user' => $targetUser,
            'activeLiveStream' => $activeLiveStream,
            'activeStories' => $activeStoriesData,
            'hasActiveStory' => $hasActiveStory,
            'activeReels' => $activeReels,
            'profile' => $viewProfile,
            'timeline' => $timeline,
            'photos' => $photos,
            'videos' => $videos,
            'friends' => $friendsData,
            'viewer' => $viewer,
            'authToken' => $authToken,
            'isOwner' => $isOwnerForView,
            'actualOwner' => $isOwner,
            'isViewAsPublic' => $isViewAsPublic,
            'completion' => $completionData,
            'verificationEligibility' => $verificationEligibility,
            'analytics' => $analyticsData,
            'privacySettings' => $privacySettings,
            'notificationSettings' => $notificationSettings,
            'albums' => $albums,
            'highlights' => $highlights,
            'professionalAnalytics' => $professionalAnalytics,
            'savedItems' => $savedItems,
            'activityLogs' => $activityLogs,
            'avatarHistory' => $avatarHistory,
            'coverHistory' => $coverHistory,
            'friendSuggestions' => $friendSuggestions,
            'followers' => $followers,
            'following' => $following,
        ]);
    }

    /**
     * Redirect to the authenticated user's own profile.
     */
    public function me(Request $request): RedirectResponse
    {
        $user = $this->resolveViewer($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return redirect()->route('profile.u', ['username' => $user->username]);
    }

    /**
     * Resolve the authenticated viewer deterministically.
     *
     * The `jugajug_token` cookie mirrors the token the client is actually logged in with,
     * so it is authoritative. If the web session belongs to a different user (e.g. a stale
     * session), the session is regenerated for the token owner instead of being trusted.
     */
    private function resolveViewer(Request $request): ?User
    {
        $sessionUser = $request->user('web');
        $tokenUser = null;

        if ($request->hasCookie('jugajug_token')) {
            $pat = PersonalAccessToken::findToken((string) $request->cookie('jugajug_token'));
            if ($pat && $pat->tokenable instanceof User && (! $pat->expires_at || $pat->expires_at->isFuture())) {
                $tokenUser = $pat->tokenable;
            }
        }

        if ($tokenUser) {
            if (! $sessionUser || (int) $sessionUser->id !== (int) $tokenUser->id) {
                if ($sessionUser) {
                    auth('web')->logout();
                    $request->session()->invalidate();
                }
                auth('web')->login($tokenUser);
                $request->session()->regenerate();
            }

            return $tokenUser;
        }

        return $sessionUser;
    }
}
