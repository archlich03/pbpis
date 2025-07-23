<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Body;
use App\Services\AuditLogService;
use App\Services\RoleAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(): View
    {
        if (!RoleAuthorizationService::canAccessUserManagement(Auth::user())) {
            abort(403);
        }
        $perPage = in_array((int) request('perPage'), [10, 20, 50, 100]) ? (int) request('perPage') : 20;
        $sort = request('sort', 'name');
        $direction = request('direction', 'asc');
        $search = request('search');

        $query = User::query();

        // Apply search if present
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Validate and apply sorting
        if (!in_array($sort, ['name', 'email'])) $sort = 'name';
        if (!in_array($direction, ['asc', 'desc'])) $direction = 'asc';

        $users = $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    
    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $authenticatedUser = Auth::user();
        
        if (!RoleAuthorizationService::canEditUserProfile($authenticatedUser, $user)) {
            abort(403);
        }

        return view('users.edit', ['user' => $user]);
    }

    
    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Check if user is authenticated
        $authenticatedUser = auth()->user();
        if (!$authenticatedUser) {
            return redirect()->route('login');
        }
     
        if (!RoleAuthorizationService::canDeleteUser($authenticatedUser, $user)) {
            abort(403);
        }
        
        // Only require password confirmation for non-Microsoft-linked admin accounts
        if (empty($authenticatedUser->ms_id)) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        }
        
        $user->delete();

        return redirect()->route('users.index');
    }

    
    /**
     * Update the specified user's profile in storage.
     */
    public function updateProfile(Request $request, User $user): RedirectResponse
    {
        $authenticatedUser = auth()->user();

        if (!$authenticatedUser) {
            return redirect()->route('login');
        }

        if (!RoleAuthorizationService::canEditUserProfile($authenticatedUser, $user)) {
            abort(403);
        }

        // Validation rules depend on authenticated user's role
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->user_id . ',user_id'],
            'pedagogical_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['integer', 'in:0,1'],
            'role' => RoleAuthorizationService::getRoleValidationRules($authenticatedUser),
        ]);

        $user->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'pedagogical_name' => $request->filled('pedagogical_name') 
                ? strtolower($request->input('pedagogical_name')) 
                : null,
            'gender' => $request->input('gender'),
            'role' => $request->input('role'),
        ]);

        return redirect()->route('users.index');
    }



    /**
     * Update the specified user's password in storage.
     */
    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $authenticatedUser = auth()->user();
        if (!$authenticatedUser) {
            return redirect()->route('login');
        }
        
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        // Prevent password updates for Microsoft-linked accounts
        if (!empty($user->ms_id)) {
            return redirect()->route('users.edit', $user)
                ->withErrors([
                    'password' => __('Password changes are not allowed for Microsoft-linked accounts.')
                ], 'updatePassword');
        }

        if ($authenticatedUser->id !== $user->id) {
            abort(403);
        }
        
        $request->validate([
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => bcrypt($request->input('password')),
                'password_change_required' => false, // Clear forced password change flag
            ]);
        }

        return redirect()->route('users.index');
    }

    public function dashboard(): View
    {
        $temp_bodies = Body::orderBy('title', 'asc')->get();
        $bodies = collect();
        foreach ($temp_bodies as $body) {
            if ($body->members->contains(Auth::user())) {
                $bodies->add($body);
            }
        }

        $meetings = collect();
        foreach ($bodies as $body) {
            foreach ($body->meetings as $meeting) {
                if ($meeting->status !== 'Baigtas') {
                    $meetings->add($meeting);
                }
            }
        }
        $meetings = $meetings->sortByDesc('meeting_date');
        
        return view('dashboard', ['bodies' => $bodies, 'meetings' => $meetings]);
    }
    
    /**
     * Remove 2FA from a user (IT admins and secretaries only).
     */
    public function removeTwoFactor(Request $request, User $user): RedirectResponse
    {
        $authenticatedUser = Auth::user();
        
        if (!$authenticatedUser->isPrivileged()) {
            abort(403);
        }
        
        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('users.edit', $user)
                ->with('error', '2FA is not enabled for this user');
        }
        
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
        
        // Log the removal
        AuditLog::log(
            $user->user_id,
            '2fa_removed',
            $request->ip(),
            $request->userAgent(),
            [
                'removed_by' => $authenticatedUser->user_id,
                'removed_by_name' => $authenticatedUser->name,
                'removed_by_role' => $authenticatedUser->role,
            ]
        );
        
        return redirect()->route('users.edit', $user)
            ->with('status', '2fa-removed');
    }
    
    /**
     * Force password change for a user (IT admins and secretaries only).
     */
    public function forcePasswordChange(Request $request, User $user): RedirectResponse
    {
        $authenticatedUser = Auth::user();
        
        if (!$authenticatedUser->isPrivileged()) {
            abort(403);
        }
        
        // Can't force password change for Microsoft accounts
        if (!empty($user->ms_id)) {
            return redirect()->route('users.edit', $user)
                ->with('error', 'Cannot force password change for Microsoft-linked accounts');
        }
        
        $user->update(['password_change_required' => true]);
        
        // Log the action
        AuditLog::log(
            $user->user_id,
            'password_change_forced',
            $request->ip(),
            $request->userAgent(),
            [
                'forced_by' => $authenticatedUser->user_id,
                'forced_by_name' => $authenticatedUser->name,
                'forced_by_role' => $authenticatedUser->role,
            ]
        );
        
        return redirect()->route('users.edit', $user)
            ->with('status', 'password-change-forced');
    }
    
    /**
     * Cancel forced password change for a user.
     */
    public function cancelPasswordChange(User $user): RedirectResponse
    {
        $authenticatedUser = Auth::user();
        
        // Check if user has permission
        if (!in_array($authenticatedUser->role, ['IT administratorius', 'Sekretorius'])) {
            abort(403);
        }
        
        // Cancel the forced password change
        $user->password_change_required = false;
        $user->save();
        
        // Log the action
        AuditLog::log(
            $user->user_id,
            'password_change_cancelled',
            request()->ip(),
            request()->userAgent(),
            [
                'cancelled_by' => $authenticatedUser->user_id,
                'cancelled_by_name' => $authenticatedUser->name,
                'cancelled_by_role' => $authenticatedUser->role,
            ]
        );
        
        return redirect()->route('users.edit', $user)
            ->with('status', 'password-change-cancelled');
    }
    
    /**
     * Show user's own audit history.
     */
    public function history(Request $request): View
    {
        $user = Auth::user();
        
        $query = AuditLog::where('user_id', $user->user_id)
            ->orderBy('created_at', 'desc');
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('action', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%")
                  ->orWhere('details', 'LIKE', "%{$search}%");
            });
        }
        
        // Filter by action type
        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }
        
        // Sort functionality
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortBy, ['created_at', 'action', 'ip_address'])) {
            $query->orderBy($sortBy, $sortDirection);
        }
        
        // Paginate results
        $perPage = $request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;
        $auditLogs = $query->paginate($perPage)->withQueryString();
        
        // Get available actions for filter dropdown
        $availableActions = array_keys(AuditLogService::getAllActions());
        
        return view('user.history', compact('auditLogs', 'availableActions'));
    }
    
    /**
     * Show audit logs for all users (IT administrators and secretaries only).
     */
    public function auditLogs(Request $request): View
    {
        $user = Auth::user();
        
        // Check if user has permission to view audit logs
        if (!in_array($user->role, ['IT administratorius', 'Sekretorius'])) {
            abort(403);
        }
        
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('action', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%")
                  ->orWhere('details', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }
        
        // Filter by action type
        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }
        
        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }
        
        // Sort functionality
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortBy, ['created_at', 'action', 'ip_address', 'user_id'])) {
            $query->orderBy($sortBy, $sortDirection);
        }
        
        // Paginate results
        $perPage = $request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;
        $auditLogs = $query->paginate($perPage)->withQueryString();
        
        // Get available actions for filter dropdown
        $availableActions = array_keys(AuditLogService::getAllActions());
        
        // Get all users for filter dropdown
        $availableUsers = User::select('user_id', 'name', 'email')
            ->orderBy('name')
            ->get();
        
        return view('audit.logs', compact('auditLogs', 'availableActions', 'availableUsers'));
    }
}