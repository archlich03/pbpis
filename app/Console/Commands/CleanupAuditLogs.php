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
    protected $signature = 'audit:cleanup {--days=30 : Number of days to keep audit logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up audit logs older than specified days (default: 30 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning up audit logs older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");
        
        $deletedCount = AuditLog::where('created_at', '<', $cutoffDate)->delete();
        
        $this->info("Deleted {$deletedCount} audit log entries.");
        
        return Command::SUCCESS;
    }
}
