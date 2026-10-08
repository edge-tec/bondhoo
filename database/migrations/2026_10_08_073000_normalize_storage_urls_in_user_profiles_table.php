<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalize any full URL with /storage/ to root-relative /storage/ path
        // Handles http://localhost:8000/storage/..., https://hostvra.com/storage/..., etc.
        $profiles = DB::table('user_profiles')
            ->where(function ($query) {
                $query->where('avatar_url', 'like', '%/storage/%')
                    ->orWhere('cover_url', 'like', '%/storage/%');
            })
            ->get(['id', 'avatar_url', 'cover_url']);

        foreach ($profiles as $profile) {
            $updates = [];

            if ($profile->avatar_url && preg_match('#^(?:https?://[^/]+)?(/storage/.*)$#i', $profile->avatar_url, $m)) {
                if ($m[1] !== $profile->avatar_url) {
                    $updates['avatar_url'] = $m[1];
                }
            }

            if ($profile->cover_url && preg_match('#^(?:https?://[^/]+)?(/storage/.*)$#i', $profile->cover_url, $m)) {
                if ($m[1] !== $profile->cover_url) {
                    $updates['cover_url'] = $m[1];
                }
            }

            if (! empty($updates)) {
                DB::table('user_profiles')->where('id', $profile->id)->update($updates);
            }
        }

        // Also normalize profile_photos table if present
        if (Schema::hasTable('profile_photos')) {
            $photos = DB::table('profile_photos')
                ->where('photo_url', 'like', '%/storage/%')
                ->get(['id', 'photo_url']);

            foreach ($photos as $photo) {
                if ($photo->photo_url && preg_match('#^(?:https?://[^/]+)?(/storage/.*)$#i', $photo->photo_url, $m)) {
                    if ($m[1] !== $photo->photo_url) {
                        DB::table('profile_photos')->where('id', $photo->id)->update(['photo_url' => $m[1]]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Relative storage URLs are valid across all environments.
    }
};
