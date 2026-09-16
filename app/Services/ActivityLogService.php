<?php

namespace App\Services;

use App\Models\ActivityLogs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Record an activity log entry.
     *
     * @param string $action (e.g., 'CREATE', 'UPDATE', 'DELETE', 'RESTOCK', 'LOGIN')
     * @param string $module (e.g., 'HPP', 'PRODUCT', 'USER', 'RESTOCK', 'REPORT')
     * @param string $entityType (e.g., 'App\Models\Products')
     * @param int $entityId
     * @param string|null $description
     * @param array|null $oldValues
     * @param array|null $newValues
     * @param int|null $userId
     * @param string|null $ipAddress
     * @return ActivityLogs
     */
    public static function log(
        string $action,
        string $module,
        string $entityType,
        int $entityId,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?string $ipAddress = null
    ): ActivityLogs {
        $resolvedUserId = $userId ?? Auth::id() ?? null; // Fallback to 1 if unauthenticated (e.g. seeder/cron)
        $resolvedIpAddress = $ipAddress ?? Request::ip() ?? '127.0.0.1';

        return ActivityLogs::create([
            'user_id' => $resolvedUserId,
            'action' => strtoupper($action),
            'module' => strtoupper($module),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description ?? "{$action} operation on {$module} ID {$entityId}",
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $resolvedIpAddress,
        ]);
    }
}