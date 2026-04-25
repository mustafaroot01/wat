<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Admin;
use Illuminate\Http\Request;

class ActivityLogService
{
    /**
     * Log an activity
     */
    public static function log(
        Admin $admin,
        string $action,
        string $modelType,
        $modelId = null,
        array $oldValues = [],
        array $newValues = [],
        array $context = [],
        ?Request $request = null
    ): void {
        if ($admin->is_super_admin) {
            return;
        }

        try {
            $request = $request ?? request();

            $description = ActivityLog::generateDescription($action, $modelType, $modelId, $context);

            ActivityLog::create([
                'admin_id'     => $admin->id,
                'action'       => $action,
                'model_type'   => $modelType,
                'model_id'     => $modelId,
                'description'  => $description,
                'old_values'   => $oldValues ?: null,
                'new_values'   => $newValues ?: null,
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'device_type'  => self::detectDeviceType($request->userAgent()),
            ]);
        } catch (\Exception $e) {
            // Silently fail if activity logging fails (e.g., table doesn't exist)
            // This prevents logging errors from breaking the application
            \Log::warning('Activity logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Detect device type from user agent
     */
    private static function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) return 'unknown';

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }
}
