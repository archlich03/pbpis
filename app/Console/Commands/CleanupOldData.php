<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Meeting;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:old-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete soft-deleted users and old meetings based on retention period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $retentionDays = config('app.data_retention_days', 455);
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $this->info("Starting cleanup of data older than {$retentionDays} days (before {$cutoffDate->toDateString()})...");

        // Permanently delete soft-deleted users older than retention period
        $deletedUsersCount = User::onlyTrashed()
            ->where('deleted_at', '<=', $cutoffDate)
            ->forceDelete();

        $this->info("Permanently deleted {$deletedUsersCount} soft-deleted user(s).");

        // Delete meetings that finished voting more than retention period ago
        $oldMeetingsCount = Meeting::where('vote_end', '<=', $cutoffDate)
            ->where('status', 'Baigtas')
            ->count();

        if ($oldMeetingsCount > 0) {
            Meeting::where('vote_end', '<=', $cutoffDate)
                ->where('status', 'Baigtas')
                ->delete();

            $this->info("Deleted {$oldMeetingsCount} old meeting(s) and all related data.");
        } else {
            $this->info("No old meetings to delete.");
        }

        $this->info('Cleanup completed successfully.');

        return Command::SUCCESS;
    }
}
