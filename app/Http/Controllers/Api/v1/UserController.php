<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\EnterpriseAccessControlService;
use App\Services\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UserController extends Controller
{
    public function __construct(
        protected RbacService $rbacService,
        protected EnterpriseAccessControlService $accessControl
    ) {}

    /**
     * List users with pagination and search filter.
     */
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user();
        if (! $viewer->hasPermission('users.view')) {
            return $this->errorResponse('Unauthorized to view user directory.', 403);
        }

        $query = User::with(['profile', 'roles']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15);

        return $this->successResponse(
            data: $users->items(),
            message: 'Users retrieved successfully.',
            meta: [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ]
        );
    }

    /**
     * Assign role to user (admin only, with privilege escalation & SUPER_ADMIN protection).
     */
    public function assignRole(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor->hasPermission('users.edit')) {
            return $this->errorResponse('Unauthorized to assign roles.', 403);
        }

        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $roleName = strtoupper((string) $request->input('role'));
        $targetUser = User::findOrFail($id);

        // Only SUPER_ADMIN or roles.manage can assign SUPER_ADMIN or ADMIN, or modify a SUPER_ADMIN
        if (in_array($roleName, ['SUPER_ADMIN', 'ADMIN'], true) || $this->accessControl->isSuperAdmin($targetUser)) {
            if (! $this->accessControl->isSuperAdmin($actor) && ! $this->accessControl->hasPermission($actor, 'roles.manage')) {
                return $this->errorResponse('শুধুমাত্র সুপার অ্যাডমিন উচ্চ-প্রিভিলেজড রোল (SUPER_ADMIN / ADMIN) প্রদান করতে পারেন।', 403);
            }
        }

        $this->rbacService->assignRole($targetUser, $roleName, $actor);

        return $this->successResponse(
            data: $targetUser->fresh(['roles']),
            message: "Role {$roleName} assigned successfully."
        );
    }

    /**
     * Remove role from user (admin only, with last SUPER_ADMIN protection).
     */
    public function removeRole(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        if (! $actor->hasPermission('users.edit')) {
            return $this->errorResponse('Unauthorized to remove roles.', 403);
        }

        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $roleName = strtoupper((string) $request->input('role'));
        $targetUser = User::findOrFail($id);

        if (in_array($roleName, ['SUPER_ADMIN', 'ADMIN'], true) || $this->accessControl->isSuperAdmin($targetUser)) {
            if (! $this->accessControl->isSuperAdmin($actor) && ! $this->accessControl->hasPermission($actor, 'roles.manage')) {
                return $this->errorResponse('শুধুমাত্র সুপার অ্যাডমিন উচ্চ-প্রিভিলেজড রোল প্রত্যাহার করতে পারেন।', 403);
            }
        }

        try {
            DB::transaction(function () use ($targetUser, $roleName, $actor) {
                $lockedTarget = User::where('id', $targetUser->id)->lockForUpdate()->firstOrFail();
                if ($roleName === 'SUPER_ADMIN') {
                    $this->accessControl->assertSuperAdminProtection($lockedTarget, 'demote');
                }
                $this->rbacService->removeRole($lockedTarget, $roleName, $actor);
            });
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse(
            data: $targetUser->fresh(['roles']),
            message: "Role {$roleName} removed successfully."
        );
    }
}
