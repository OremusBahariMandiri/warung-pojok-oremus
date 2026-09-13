<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UsersAccess;
use App\Services\ActivityLogService;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserAccessController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Show permission matrix for a user.
     */
    public function show(Request $request, User $user)
    {
        $user->load('accesses');
        $modules = UserService::MODULES;

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user,
                    'modules' => $modules,
                ],
            ]);
        }

        return view('pages.users.access', compact('user', 'modules'));
    }

    /**
     * Update access permissions for a specific user.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'accesses' => 'required|array',
        ]);

        $this->userService->syncUserAccesses($user, $request->input('accesses', []));

        ActivityLogService::log(
            action: 'UPDATE_ACCESS',
            module: 'USER_ACCESS',
            entityType: UsersAccess::class,
            entityId: $user->id,
            description: "Updated access permissions for user: {$user->employee_name}",
            oldValues: null,
            newValues: $user->fresh()->load('accesses')->toArray()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Hak akses user berhasil diperbarui.',
                'data' => $user->fresh()->load('accesses'),
            ]);
        }

        return redirect()->route('users.show', $user->id)->with('success', 'Hak akses user berhasil diperbarui.');
    }
}
