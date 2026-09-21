<?php

namespace App\Services;

use App\Models\User;
use App\Models\UsersAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Default list of menu modules for access rights.
     */
    public const MODULES = [
        'dashboard',
        'products',
        'units',
        'hpp',
        'restock',
        'reports',
        'users',
        'activity_logs',
    ];

    /**
     * Get all users with accesses.
     */
    public function getAllUsers()
    {
        return User::with('accesses')->orderBy('employee_name', 'asc')->get();
    }

    /**
     * Get user by ID.
     */
    public function getUserById(int $id): User
    {
        return User::with('accesses')->findOrFail($id);
    }

    /**
     * Create a new user with access permissions.
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'nrk' => $data['nrk'],
                'email' => $data['email'],
                'employee_name' => $data['employee_name'],
                'password' => Hash::make($data['password']),
                'is_admin' => (bool)($data['is_admin'] ?? false),
            ]);

            // Assign access rights
            $accesses = $data['accesses'] ?? [];
            $this->syncUserAccesses($user, $accesses);

            ActivityLogService::log(
                action: 'CREATE',
                module: 'USER',
                entityType: User::class,
                entityId: $user->id,
                description: "Created user: {$user->employee_name} ({$user->email})",
                oldValues: null,
                newValues: $user->load('accesses')->toArray()
            );

            return $user->load('accesses');
        });
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $oldValues = $user->load('accesses')->toArray();

            $updateData = [
                'nrk' => $data['nrk'] ?? $user->nrk,
                'email' => $data['email'] ?? $user->email,
                'employee_name' => $data['employee_name'] ?? $user->employee_name,
                'is_admin' => isset($data['is_admin']) ? (bool)$data['is_admin'] : $user->is_admin,
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            $user->update($updateData);

            if (isset($data['accesses']) && is_array($data['accesses'])) {
                $this->syncUserAccesses($user, $data['accesses']);
            }

            ActivityLogService::log(
                action: 'UPDATE',
                module: 'USER',
                entityType: User::class,
                entityId: $user->id,
                description: "Updated user: {$user->employee_name}",
                oldValues: $oldValues,
                newValues: $user->fresh()->load('accesses')->toArray()
            );

            return $user->fresh()->load('accesses');
        });
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $oldValues = $user->load('accesses')->toArray();
            $id = $user->id;
            $name = $user->employee_name;

            $deleted = $user->delete();

            ActivityLogService::log(
                action: 'DELETE',
                module: 'USER',
                entityType: User::class,
                entityId: $id,
                description: "Deleted user: {$name}",
                oldValues: $oldValues,
                newValues: null
            );

            return $deleted;
        });
    }

    /**
     * Sync user access matrix.
     */
    public function syncUserAccesses(User $user, array $accessList): void
    {
        UsersAccess::where('user_id', $user->id)->delete();

        foreach ($accessList as $menu => $permissions) {
            UsersAccess::create([
                'user_id' => $user->id,
                'menu_access' => $menu,
                'index_acs' => !empty($permissions['index']) ? '1' : '0',
                'show_acs' => !empty($permissions['show']) ? '1' : '0',
                'create_acs' => !empty($permissions['create']) ? '1' : '0',
                'edit_acs' => !empty($permissions['edit']) ? '1' : '0',
                'delete_acs' => !empty($permissions['delete']) ? '1' : '0',
            ]);
        }
    }
}