<?php

namespace Database\Seeders;

use App\Models\MusicTrack;
use Illuminate\Database\Seeder;

class MusicTrackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Ingests curated royalty-free tracks for Reels and Stories.
     */
    public function run(): void
    {
        $tracks = [
            [
                'title' => 'Bangla Folk Acoustic Journey',
                'artist' => 'Bondhoo Studio Artists',
                'album' => 'Bengal Heritage Sounds',
                'audio_url' => 'https://actions.google.com/sounds/v1/ambiences/outdoor_ambience_rural.ogg',
                'cover_url' => 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=300&auto=format&fit=crop&q=80',
                'duration' => 45.0,
                'genre' => 'folk',
                'language' => 'bn',
                'tags' => ['acoustic', 'folk', 'bangla', 'flute', 'soothing'],
                'bpm' => 95,
                'license_type' => 'royalty_free',
                'license_holder' => 'Creative Commons Zero / Open Audio',
                'is_admin_approved' => true,
                'is_active' => true,
                'usages_count' => 142,
            ],
            [
                'title' => 'Dhaka City Upbeat Beat',
                'artist' => 'Rahim & Beats Collective',
                'album' => 'Urban Vibes Vol. 1',
                'audio_url' => 'https://actions.google.com/sounds/v1/transportation/car_horn_traffic_short.ogg',
                'cover_url' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=300&auto=format&fit=crop&q=80',
                'duration' => 30.0,
                'genre' => 'electronic',
                'language' => 'bn',
                'tags' => ['upbeat', 'dance', 'modern', 'bass'],
                'bpm' => 124,
                'license_type' => 'royalty_free',
                'license_holder' => 'Creative Commons Attribution',
                'is_admin_approved' => true,
                'is_active' => true,
                'usages_count' => 89,
            ],
            [
                'title' => 'Morning Breeze Piano',
                'artist' => 'Nusrat Jahan Piano',
                'album' => 'Serenity Morning',
                'audio_url' => 'https://actions.google.com/sounds/v1/water/rain_heavy.ogg',
                'cover_url' => 'https://images.unsplash.com/photo-1520523839898-507121c25dbb?w=300&auto=format&fit=crop&q=80',
                'duration' => 60.0,
                'genre' => 'classical',
                'language' => 'en',
                'tags' => ['piano', 'calm', 'morning', 'peaceful'],
                'bpm' => 80,
                'license_type' => 'royalty_free',
                'license_holder' => 'Public Domain',
                'is_admin_approved' => true,
                'is_active' => true,
                'usages_count' => 65,
            ],
            [
                'title' => 'Sunset Lo-Fi Chill Hop',
                'artist' => 'Chillhop Beats BD',
                'album' => 'Coffee & Rain',
                'audio_url' => 'https://actions.google.com/sounds/v1/weather/thunderstorm_heavy.ogg',
                'cover_url' => 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=300&auto=format&fit=crop&q=80',
                'duration' => 40.0,
                'genre' => 'lofi',
                'language' => 'instrumental',
                'tags' => ['lofi', 'chill', 'study', 'relax'],
                'bpm' => 85,
                'license_type' => 'royalty_free',
                'license_holder' => 'Free Music Archive',
                'is_admin_approved' => true,
                'is_active' => true,
                'usages_count' => 210,
            ],
            [
                'title' => 'Cinematic Epic Motivation',
                'artist' => 'Studio Orchestral Pro',
                'album' => 'Inspirational Horizons',
                'audio_url' => 'https://actions.google.com/sounds/v1/weather/wind_strong_gusts.ogg',
                'cover_url' => 'https://images.unsplash.com/photo-1507838153414-b4b713384a76?w=300&auto=format&fit=crop&q=80',
                'duration' => 50.0,
                'genre' => 'cinematic',
                'language' => 'instrumental',
                'tags' => ['cinematic', 'epic', 'trailer', 'drama'],
                'bpm' => 110,
                'license_type' => 'royalty_free',
                'license_holder' => 'Platform Licensed',
                'is_admin_approved' => true,
                'is_active' => true,
                'usages_count' => 77,
            ],
            [
                'title' => 'Festive Celebration Drums',
                'artist' => 'Dhol & Percussion Ensemble',
                'album' => 'Boishakhi Rhythms',
                'audio_url' => 'https://actions.google.com/sounds/v1/household/clock_ticking_fast.ogg',
                'cover_url' => 'https://images.unsplash.com/photo-1465847899084-d164df4dedc6?w=300&auto=format&fit=crop&q=80',
                'duration' => 35.0,
                'genre' => 'festive',
                'language' => 'bn',
                'tags' => ['dhol', 'celebration', 'boishakh', 'energy'],
                'bpm' => 135,
                'license_type' => 'royalty_free',
                'license_holder' => 'CC-BY-4.0',
                'is_admin_approved' => true,
                'is_active' => true,
                'usages_count' => 180,
            ],
        ];

        foreach ($tracks as $track) {
            MusicTrack::updateOrCreate(
                ['title' => $track['title'], 'artist' => $track['artist']],
                $track
            );
        }
    }
}
