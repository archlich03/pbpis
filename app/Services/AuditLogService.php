<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    /**
     * Centralized audit action definitions.
     * Add new actions here to maintain consistency across the application.
     */
    public const ACTIONS = [
        // Authentication actions
        'login' => [
            'name' => 'Login',
            'description' => 'User logged in with password',
            'color' => 'green',
        ],
        'microsoft_login' => [
            'name' => 'Microsoft Login',
            'description' => 'User logged in with Microsoft OAuth',
            'color' => 'purple',
        ],
        'microsoft_account_created' => [
            'name' => 'Microsoft Account Created',
            'description' => 'New account created via Microsoft OAuth',
            'color' => 'blue',
        ],
        'logout' => [
            'name' => 'Logout',
            'description' => 'User logged out',
            'color' => 'gray',
        ],
        
        // Two-Factor Authentication actions
        '2fa_setup' => [
            'name' => '2FA Setup',
            'description' => 'Two-factor authentication enabled',
            'color' => 'blue',
        ],
        '2fa_disabled' => [
            'name' => '2FA Disabled',
            'description' => 'Two-factor authentication disabled',
            'color' => 'red',
        ],
        '2fa_removed' => [
            'name' => '2FA Removed',
            'description' => 'Two-factor authentication removed by admin',
            'color' => 'red',
        ],
        
        // Password management actions
        'password_changed' => [
            'name' => 'Password Changed',
            'description' => 'User changed their password',
            'color' => 'yellow',
        ],
        'password_change_forced' => [
            'name' => 'Password Change Forced',
            'description' => 'Admin forced user to change password',
            'color' => 'orange',
        ],
        'password_change_cancelled' => [
            'name' => 'Password Change Cancelled',
            'description' => 'Admin cancelled forced password change',
            'color' => 'green',
        ],
        
        // Account management actions
        'profile_updated' => [
            'name' => 'Profile Updated',
            'description' => 'User profile information updated',
            'color' => 'blue',
        ],
        'account_deleted' => [
            'name' => 'Account Deleted',
            'description' => 'User account deleted',
            'color' => 'red',
        ],
        
        // Administrative actions
        'user_created' => [
            'name' => 'User Created',
            'description' => 'New user account created by admin',
            'color' => 'green',
        ],
        'user_updated' => [
            'name' => 'User Updated',
            'description' => 'User account updated by admin',
            'color' => 'blue',
        ],
        'user_deleted' => [
            'name' => 'User Deleted',
            'description' => 'User account deleted by admin',
            'color' => 'red',
        ],
    ];

    /**
     * Log an audit event.
     */
    public static function log(
        int $userId,
        string $action,
        string $ipAddress,
        string $userAgent,
        ?array $details = null
    ): void {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'details' => $details,
        ]);
    }

    /**
     * Get action definition by key.
     */
    public static function getActionDefinition(string $action): ?array
    {
        return self::ACTIONS[$action] ?? null;
    }

    /**
     * Get all available actions.
     */
    public static function getAllActions(): array
    {
        return self::ACTIONS;
    }

    /**
     * Get formatted action name.
     */
    public static function getActionName(string $action): string
    {
        $definition = self::getActionDefinition($action);
        return $definition['name'] ?? ucfirst(str_replace('_', ' ', $action));
    }

    /**
     * Get action color for UI display.
     */
    public static function getActionColor(string $action): string
    {
        $definition = self::getActionDefinition($action);
        return $definition['color'] ?? 'gray';
    }

    /**
     * Get CSS classes for action badge.
     */
    public static function getActionBadgeClasses(string $action): string
    {
        $color = self::getActionColor($action);
        
        $colorClasses = [
            'green' => 'bg-green-200 text-green-900 dark:bg-green-600 dark:text-green-50',
            'blue' => 'bg-blue-200 text-blue-900 dark:bg-blue-600 dark:text-blue-50',
            'red' => 'bg-red-200 text-red-900 dark:bg-red-600 dark:text-red-50',
            'purple' => 'bg-purple-200 text-purple-900 dark:bg-purple-600 dark:text-purple-50',
            'yellow' => 'bg-yellow-200 text-yellow-900 dark:bg-yellow-600 dark:text-yellow-50',
            'orange' => 'bg-orange-200 text-orange-900 dark:bg-orange-600 dark:text-orange-50',
            'gray' => 'bg-gray-200 text-gray-900 dark:bg-gray-600 dark:text-gray-50',
        ];

        return $colorClasses[$color] ?? $colorClasses['gray'];
    }
}
