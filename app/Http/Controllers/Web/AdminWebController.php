<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWebController extends Controller
{
    public function __construct(
        protected AdminDashboardService $dashboardService
    ) {}

    /**
     * Render the unified Enterprise Social Network Admin Dashboard.
     */
    public function index(Request $request): View
    {
        /** @var User|null $admin */
        $admin = Auth::guard('admin')->user() ?? Auth::guard('web')->user();

        $adminToken = null;
        if ($admin && method_exists($admin, 'createToken')) {
            $adminToken = $admin->createToken('admin_enterprise_console', ['admin', 'manage.users', 'auth.admin'])->plainTextToken;
            session(['admin_api_token' => $adminToken]);
        }

        $kpis = $this->dashboardService->getExecutiveKpis();
        $analytics = $this->dashboardService->getAnalyticsTrends(14);
        $settings = PlatformSetting::getAllGrouped();

        return view('admin.dashboard', compact('admin', 'adminToken', 'kpis', 'analytics', 'settings'));
    }
}
