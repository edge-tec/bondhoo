<?php

namespace App\Services;

use App\Models\MusicTrack;
use App\Models\MusicUsage;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * MusicService
 *
 * Professional Background Music Management & Licensing Engine.
 * Handles copyright verification, music search, preview, and usage tracking.
 */
class MusicService
{
    /**
     * Search available and approved tracks for Reels and Stories.
     *
     * @param  array<string, mixed>  $filters
     */
    public function searchTracks(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MusicTrack::query()->available();

        if (! empty($filters['query'])) {
            $searchTerm = trim((string) $filters['query']);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('artist', 'like', "%{$searchTerm}%")
                    ->orWhere('album', 'like', "%{$searchTerm}%");
            });
        }

        if (! empty($filters['genre'])) {
            $query->where('genre', $filters['genre']);
        }

        if (! empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        $sort = $filters['sort'] ?? 'trending';
        if ($sort === 'trending') {
            $query->orderByDesc('usages_count')->latest('id');
        } elseif ($sort === 'latest') {
            $query->latest('id');
        } else {
            $query->orderBy('title');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get distinct available genres with track counts.
     *
     * @return Collection<int, array{genre: string, count: int}>
     */
    public function getGenres(): Collection
    {
        return MusicTrack::query()
            ->available()
            ->select('genre', DB::raw('count(*) as count'))
            ->groupBy('genre')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get track details by ID.
     *
     * @throws Exception
     */
    public function getTrack(int $id): MusicTrack
    {
        $track = MusicTrack::query()->available()->find($id);
        if (! $track) {
            throw new Exception('Music track not found or license expired.');
        }

        return $track;
    }

    /**
     * Record music usage when attached to a Reel or Story.
     */
    public function recordUsage(
        MusicTrack $track,
        Model $usable,
        User $user,
        float $startTime = 0.0,
        float $duration = 15.0,
        int $volume = 100
    ): MusicUsage {
        return DB::transaction(function () use ($track, $usable, $user, $startTime, $duration, $volume) {
            $usage = MusicUsage::create([
                'music_track_id' => $track->id,
                'usable_type' => get_class($usable),
                'usable_id' => $usable->getKey(),
                'user_id' => $user->id,
                'start_time_seconds' => $startTime,
                'duration_seconds' => $duration,
                'volume_percent' => $volume,
            ]);

            $track->increment('usages_count');

            return $usage;
        });
    }

    /**
     * Admin: Create and ingest a new music track.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTrack(array $data): MusicTrack
    {
        return MusicTrack::create([
            'title' => $data['title'],
            'artist' => $data['artist'],
            'album' => $data['album'] ?? null,
            'audio_url' => $data['audio_url'],
            'cover_url' => $data['cover_url'] ?? null,
            'duration' => (float) ($data['duration'] ?? 30.0),
            'genre' => $data['genre'] ?? 'pop',
            'language' => $data['language'] ?? 'bn',
            'tags' => $data['tags'] ?? [],
            'bpm' => $data['bpm'] ?? null,
            'license_type' => $data['license_type'] ?? 'royalty_free',
            'license_holder' => $data['license_holder'] ?? 'Public Domain / Free for Commercial Use',
            'is_admin_approved' => (bool) ($data['is_admin_approved'] ?? true),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * Admin: Delete track.
     */
    public function deleteTrack(MusicTrack $track): void
    {
        $track->delete();
    }
}
