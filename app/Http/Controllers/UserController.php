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
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $showDeleted = request('show_deleted', false);

        $query = User::query();

        // Include soft-deleted users if checkbox is checked
        if ($showDeleted) {
            $query->withTrashed();
        }

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

        return view('users.index', compact('users', 'showDeleted'));
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
        
        // Check if user is a chairman of any body
        $chairmanBodies = Body::where('chairman_id', $user->user_id)->get();
        if ($chairmanBodies->count() > 0) {
            return redirect()->route('users.edit', $user)
                ->withErrors([
                    'delete' => __('Cannot delete this user because they are the chairman of :count body/bodies: :bodies', [
                        'count' => $chairmanBodies->count(),
                        'bodies' => $chairmanBodies->pluck('title')->join(', ')
                    ])
                ], 'userDeletion');
        }
        
        // Check if user is a secretary of any meeting
        $secretaryMeetings = \App\Models\Meeting::where('secretary_id', $user->user_id)->get();
        if ($secretaryMeetings->count() > 0) {
            return redirect()->route('users.edit', $user)
                ->withErrors([
                    'delete' => __('Cannot delete this user because they are the secretary of :count meeting(s). Please reassign or delete those meetings first.', [
                        'count' => $secretaryMeetings->count()
                    ])
                ], 'userDeletion');
        }
        
        // Store user info in audit logs before deletion (for historical records)
        \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('user_id', $user->user_id)
            ->update([
                'deleted_user_name' => $user->name,
                'deleted_user_email' => $user->email,
            ]);
        
        // Remove user from body members JSON arrays
        $bodies = Body::all();
        foreach ($bodies as $body) {
            // Get raw member IDs from database
            $memberIds = json_decode($body->getRawOriginal('members') ?? '[]', true);
            
            if (in_array($user->user_id, $memberIds)) {
                $memberIds = array_values(array_filter($memberIds, fn($id) => $id != $user->user_id));
                // Update with array - the cast will handle JSON encoding
                $body->update(['members' => $memberIds]);
            }
        }
        
        // Log the soft deletion
        AuditLog::log(
            $user->user_id,
            'user_soft_deleted',
            $request->ip(),
            $request->userAgent(),
            [
                'deleted_by' => $authenticatedUser->user_id,
                'deleted_by_name' => $authenticatedUser->name,
                'deleted_by_role' => $authenticatedUser->role,
                'deleted_user_name' => $user->name,
                'deleted_user_email' => $user->email,
                'deleted_user_role' => $user->role,
                'retention_days' => config('app.data_retention_days', 455),
            ]
        );
        
        // Soft delete the user (marks deleted_at timestamp)
        $user->delete();

        return redirect()->route('users.index')
            ->with('status', 'user-deleted');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore($id): RedirectResponse
    {
        $authenticatedUser = Auth::user();
        
        // Only IT admins can restore users
        if ($authenticatedUser->role !== 'IT administratorius') {
            abort(403);
        }
        
        $user = User::withTrashed()->findOrFail($id);
        
        if (!$user->trashed()) {
            return redirect()->route('users.index')
                ->withErrors(['restore' => 'User is not deleted.']);
        }
        
        // Restore the user
        $user->restore();
        
        // Log the restoration
        AuditLog::log(
            $user->user_id,
            'user_restored',
            request()->ip(),
            request()->userAgent(),
            [
                'restored_by' => $authenticatedUser->user_id,
                'restored_by_name' => $authenticatedUser->name,
                'restored_by_role' => $authenticatedUser->role,
                'restored_user_name' => $user->name,
                'restored_user_email' => $user->email,
                'restored_user_role' => $user->role,
            ]
        );
        
        return redirect()->route('users.index', ['show_deleted' => true])
            ->with('status', 'user-restored');
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

        // Log the profile update
        $action = ($authenticatedUser->user_id === $user->user_id) ? 'profile_updated' : 'user_updated';
        AuditLog::log(
            $user->user_id,
            $action,
            $request->ip(),
            $request->userAgent(),
            [
                'updated_by' => $authenticatedUser->user_id,
                'updated_by_name' => $authenticatedUser->name,
                'updated_by_role' => $authenticatedUser->role,
            ]
        );

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
        
        // Only privileged users (IT admins and secretaries) can change passwords
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
        
        $validated = $request->validateWithBag('updatePassword', [
            'password' => [
                'required', 
                'confirmed', 
                \Illuminate\Validation\Rules\Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
        ], [
            'password.min' => __('The password must be at least :min characters.', ['min' => 12]),
            'password.mixed' => __('The password must contain both uppercase and lowercase letters.'),
            'password.numbers' => __('The password must contain at least one number.'),
            'password.symbols' => __('The password must contain at least one special character (including Lithuanian characters: ąčęėįšųūž).'),
            'password.uncompromised' => __('The password has appeared in a data leak. Please choose a different password.'),
            'password.confirmed' => __('The password confirmation does not match.'),
        ]);

        $user->update([
            'password' => bcrypt($validated['password']),
            'password_change_required' => false, // Clear forced password change flag
        ]);
        
        // Log the password change
        AuditLog::log(
            $user->user_id,
            'password_changed',
            $request->ip(),
            $request->userAgent(),
            [
                'changed_by' => $authenticatedUser->user_id,
                'changed_by_name' => $authenticatedUser->name,
                'changed_by_role' => $authenticatedUser->role,
            ]
        );

        return redirect()->route('users.edit', $user)
            ->with('status', 'password-updated');
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
        
        // Log the removal (use 2fa_disabled when admin removes it)
        AuditLog::log(
            $user->user_id,
            '2fa_disabled',
            $request->ip(),
            $request->userAgent(),
            [
                'disabled_by' => $authenticatedUser->user_id,
                'disabled_by_name' => $authenticatedUser->name,
                'disabled_by_role' => $authenticatedUser->role,
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
                  ->orWhere('user_agent', 'LIKE', "%{$search}%")
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
        
        // Build query using helper method
        $query = $this->buildAuditLogsQuery($request);
        
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

    /**
     * Export audit logs as JSON with applied filters and sorting.
     */
    public function exportAuditLogsJson(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user has permission
        if (!in_array($user->role, ['IT administratorius', 'Sekretorius'])) {
            abort(403);
        }
        
        // Build the same query as auditLogs() method
        $query = $this->buildAuditLogsQuery($request);
        
        // Get all results (no pagination for export)
        $auditLogs = $query->get();
        
        // Transform data for export
        $exportData = $auditLogs->map(function ($log) {
            return [
                'id' => $log->id,
                'user' => [
                    'id' => $log->user_id,
                    'name' => $log->user?->name ?? __('Deleted User'),
                    'email' => $log->user?->email ?? __('N/A'),
                ],
                'action' => $log->action,
                'action_label' => AuditLogService::getActionName($log->action),
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'details' => $log->details,
                'created_at' => $log->created_at->toIso8601String(),
            ];
        });
        
        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.json';
        
        return response()->json($exportData, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Export audit logs as PDF with applied filters and sorting.
     */
    public function exportAuditLogsPdf(Request $request)
    {
        $user = Auth::user();
        
        // Check if user has permission
        if (!in_array($user->role, ['IT administratorius', 'Sekretorius'])) {
            abort(403);
        }
        
        // Build the same query as auditLogs() method
        $query = $this->buildAuditLogsQuery($request);
        
        // Get all results (no pagination for export)
        $auditLogs = $query->get();
        
        // Get filter information for PDF header
        $filters = [
            'search' => $request->get('search'),
            'user_id' => $request->get('user_id'),
            'action' => $request->get('action'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'sort' => $request->get('sort', 'created_at'),
            'direction' => $request->get('direction', 'desc'),
        ];
        
        $pdf = Pdf::loadView('audit.export-pdf', compact('auditLogs', 'filters'));
        
        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Build audit logs query with filters and sorting (DRY helper method).
     */
    private function buildAuditLogsQuery(Request $request)
    {
        $query = AuditLog::with('user');
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('action', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%")
                  ->orWhere('user_agent', 'LIKE', "%{$search}%")
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
        
        return $query;
    }
}