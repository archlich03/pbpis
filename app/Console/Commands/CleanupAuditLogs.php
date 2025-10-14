<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up audit logs older than configured retention period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = config('app.audit_log_retention_days', 90);
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning up audit logs older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");
        
        $deletedCount = AuditLog::where('created_at', '<', $cutoffDate)->delete();
        
        $this->info("Deleted {$deletedCount} audit log entries.");
        
        return Command::SUCCESS;
    }
}
