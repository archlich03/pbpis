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
            'description' => 'Two-factor authentication disabled (by self or admin)',
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
        
        // Voting actions
        'vote_cast' => [
            'name' => 'Vote Cast',
            'description' => 'User cast a vote on a question',
            'color' => 'blue',
        ],
        'vote_removed' => [
            'name' => 'Vote Removed',
            'description' => 'User removed their vote',
            'color' => 'yellow',
        ],
        'proxy_vote_cast' => [
            'name' => 'Proxy Vote Cast',
            'description' => 'Admin/Secretary cast a vote on behalf of another user',
            'color' => 'purple',
        ],
        'proxy_vote_removed' => [
            'name' => 'Proxy Vote Removed',
            'description' => 'Admin/Secretary removed a vote on behalf of another user',
            'color' => 'orange',
        ],
        
        // Email actions
        'email_sent' => [
            'name' => 'Email Sent',
            'description' => 'Email sent to body members',
            'color' => 'blue',
        ],
        
        // AI actions
        'ai_summary_generated' => [
            'name' => 'AI Summary Generated',
            'description' => 'AI-generated summary created for question discussions',
            'color' => 'purple',
        ],
        'ai_summary_failed' => [
            'name' => 'AI Summary Failed',
            'description' => 'AI summary generation failed with error',
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
            'green' => 'bg-green-100 text-green-700 dark:bg-green-700 dark:text-green-100',
            'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-700 dark:text-blue-100',
            'red' => 'bg-red-100 text-red-700 dark:bg-red-700 dark:text-red-100',
            'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-700 dark:text-purple-100',
            'yellow' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-700 dark:text-yellow-100',
            'orange' => 'bg-orange-100 text-orange-700 dark:bg-orange-700 dark:text-orange-100',
            'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-100',
        ];

        return $colorClasses[$color] ?? $colorClasses['gray'];
    }
}
