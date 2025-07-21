<?php

namespace App\Services;

use App\Models\User;

class RoleAuthorizationService
{
    /**
     * Available roles in the system
     */
    public const ROLES = [
        'IT administratorius',
        'Sekretorius', 
        'Balsuojantysis'
    ];

    /**
     * Check if a user can edit another user's profile
     *
     * @param User $authenticatedUser The user performing the action
     * @param User $targetUser The user being edited
     * @return bool
     */
    public static function canEditUserProfile(User $authenticatedUser, User $targetUser): bool
    {
        // Users can always edit their own profile
        if ($authenticatedUser->user_id === $targetUser->user_id) {
            return true;
        }

        $authRole = $authenticatedUser->role;
        $targetRole = $targetUser->role;

        // IT administrators can edit anyone
        if ($authRole === 'IT administratorius') {
            return true;
        }

        // Non-IT admins cannot edit IT administrators
        if ($targetRole === 'IT administratorius') {
            return false;
        }

        // Secretaries can only edit voters (Balsuojantysis)
        if ($authRole === 'Sekretorius' && $targetRole === 'Balsuojantysis') {
            return true;
        }

        // All other cases are forbidden
        return false;
    }

    /**
     * Get allowed roles that a user can assign to others
     *
     * @param User $authenticatedUser
     * @return array
     */
    public static function getAllowedRolesForUser(User $authenticatedUser): array
    {
        $authRole = $authenticatedUser->role;

        switch ($authRole) {
            case 'IT administratorius':
                return ['IT administratorius', 'Sekretorius', 'Balsuojantysis'];
            case 'Sekretorius':
                return ['Sekretorius', 'Balsuojantysis'];
            default:
                return []; // Voters cannot assign roles
        }
    }

    /**
     * Get validation rules for role field based on authenticated user
     *
     * @param User $authenticatedUser
     * @return array
     */
    public static function getRoleValidationRules(User $authenticatedUser): array
    {
        $allowedRoles = self::getAllowedRolesForUser($authenticatedUser);
        
        if (empty($allowedRoles)) {
            return ['prohibited'];
        }

        return ['string', 'in:' . implode(',', $allowedRoles)];
    }

    /**
     * Check if a user can delete another user
     *
     * @param User $authenticatedUser
     * @param User $targetUser
     * @return bool
     */
    public static function canDeleteUser(User $authenticatedUser, User $targetUser): bool
    {
        // Only IT administrators can delete users
        if ($authenticatedUser->role !== 'IT administratorius') {
            return false;
        }

        // Users cannot delete themselves
        if ($authenticatedUser->user_id === $targetUser->user_id) {
            return false;
        }

        return true;
    }

    /**
     * Check if a user can access user management pages
     *
     * @param User $user
     * @return bool
     */
    public static function canAccessUserManagement(User $user): bool
    {
        return $user->isPrivileged(); // IT admin or Secretary
    }
}
