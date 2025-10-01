<?php

namespace App\Models;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'details',
        'deleted_user_name',
        'deleted_user_email',
    ];
    
    protected $casts = [
        'details' => 'array',
    ];
    
    protected $appends = [
        'action_name',
        'action_badge_classes',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
    
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
        AuditLogService::log($userId, $action, $ipAddress, $userAgent, $details);
    }
    
    /**
     * Get formatted action name.
     */
    public function getActionNameAttribute(): string
    {
        return AuditLogService::getActionName($this->action);
    }
    
    /**
     * Get action badge CSS classes.
     */
    public function getActionBadgeClassesAttribute(): string
    {
        return AuditLogService::getActionBadgeClasses($this->action);
    }
}
