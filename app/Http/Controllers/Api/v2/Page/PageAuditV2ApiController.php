<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * এন্টারপ্রাইজ পেজ অডিট লগ কন্ট্রোলার:
 * মিউটেশন অডিট ট্রেইল ও সিকিউরিটি ট্র্যাকিং।
 */
class PageAuditV2ApiController extends Controller
{
    /**
     * পেজের অডিট লগ তালিকা প্রদর্শন
     */
    public function index(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageSettings', $page)) {
            abort(403, 'You do not have permission to view audit logs for this page.');
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));

        $logs = PageAuditLog::where('page_id', $page->id)
            ->with(['actor:id,name,username,email'])
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
