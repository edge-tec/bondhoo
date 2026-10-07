<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected RbacService $rbacService
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
     * Assign role to user (admin only).
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

        $targetUser = User::findOrFail($id);
        $this->rbacService->assignRole($targetUser, $request->input('role'), $actor);

        return $this->successResponse(
            data: $targetUser->fresh(['roles']),
            message: "Role {$request->input('role')} assigned successfully."
        );
    }

    /**
     * Remove role from user (admin only).
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

        $targetUser = User::findOrFail($id);
        $this->rbacService->removeRole($targetUser, $request->input('role'), $actor);

        return $this->successResponse(
            data: $targetUser->fresh(['roles']),
            message: "Role {$request->input('role')} removed successfully."
        );
    }
}
