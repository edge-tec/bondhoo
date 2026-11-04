<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Parental\Family;
use App\Models\User;
use App\Services\Parental\FamilyService;
use App\Services\Parental\UsageTelemetryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class ParentalWebController
 *
 * Serves the Connectly Parent Web Dashboard via Inertia.js.
 */
class ParentalWebController extends Controller
{
    public function __construct(
        protected FamilyService $familyService,
        protected UsageTelemetryService $telemetryService
    ) {}

    /**
     * Render the comprehensive Parental Control & Family Safety Dashboard.
     */
    public function index(Request $request): Response
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user() ?? $request->user();

        // In development fallback to first user if unauthenticated
        if (! $user && app()->environment('local')) {
            $user = User::first();
            if ($user) {
                Auth::guard('web')->login($user);
            }
        }

        abort_if(! $user, 401, 'Please log in to access the Parental Control Dashboard.');

        // Find primary family owned by this user or where they are an active guardian
        /** @var Family|null $family */
        $family = Family::where('owner_user_id', $user->id)
            ->orWhereHas('members', function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereIn('role', ['parent', 'guardian']);
            })
            ->with([
                'members.devices.screenTimeRecords',
                'restrictions',
                'consentRequests' => fn ($q) => $q->orderByDesc('id')->limit(10),
                'securityAlerts' => fn ($q) => $q->orderByDesc('id')->limit(10),
            ])
            ->first();

        $stats = [
            'total_children' => $family ? $family->members->where('role', 'child')->count() : 0,
            'connected_devices' => $family ? $family->childDevices->where('device_status', 'active')->count() : 0,
            'offline_devices' => $family ? $family->childDevices->where('device_status', 'inactive')->count() : 0,
            'today_screen_time_minutes' => 0,
            'active_policies_count' => $family ? $family->restrictions->where('enabled', true)->count() : 0,
            'pending_consents_count' => $family ? $family->consentRequests->where('request_status', 'pending')->count() : 0,
            'active_alerts_count' => $family ? $family->securityAlerts->where('alert_status', 'active')->count() : 0,
        ];

        return Inertia::render('Parental/Dashboard', [
            'user' => $user,
            'family' => $family,
            'dashboardStats' => $stats,
        ]);
    }
}

