<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    /**
     * Check application version compatibility for native clients (Android, iOS, macOS, Windows, Linux).
     */
    public function check(Request $request): JsonResponse
    {
        $platform = strtolower((string) $request->query('platform', 'unknown'));
        $clientVersion = (string) $request->query('version', '1.0.0');

        $minVersion = config('app.client_min_version', '1.0.0');
        $recommendedVersion = config('app.client_recommended_version', '1.2.0');
        $latestVersion = config('app.client_latest_version', '1.2.0');

        $isSupported = version_compare($clientVersion, $minVersion, '>=');
        $isDeprecated = version_compare($clientVersion, $recommendedVersion, '<');
        $isUpdateAvailable = version_compare($clientVersion, $latestVersion, '<');
        $forceUpdate = ! $isSupported;

        return $this->successResponse(
            data: [
                'platform' => $platform,
                'client_version' => $clientVersion,
                'min_supported_version' => $minVersion,
                'recommended_version' => $recommendedVersion,
                'latest_version' => $latestVersion,
                'is_supported' => $isSupported,
                'is_deprecated' => $isDeprecated,
                'update_available' => $isUpdateAvailable,
                'force_update' => $forceUpdate,
                'downloads' => [
                    'android' => 'https://play.google.com/store/apps/details?id=com.jugajug.messenger',
                    'ios' => 'https://apps.apple.com/app/jugajug-messenger/id640000000',
                    'macos' => 'https://jugajug.com/download/macos/JugajugMessenger.dmg',
                    'windows' => 'https://jugajug.com/download/windows/JugajugMessengerSetup.exe',
                    'linux' => 'https://jugajug.com/download/linux/JugajugMessenger.AppImage',
                ],
                'release_notes_url' => 'https://jugajug.com/releases',
            ],
            message: 'Client version compatibility checked successfully.'
        );
    }
}
