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

        // Get soft-deleted users older than retention period
        $eligibleUsers = User::onlyTrashed()
            ->where('deleted_at', '<=', $cutoffDate)
            ->get();

        $deletedUsersCount = 0;
        $skippedUsersCount = 0;

        foreach ($eligibleUsers as $user) {
            // Check if user has any remaining data in other tables
            $hasVotes = \DB::table('votes')->where('user_id', $user->user_id)->exists();
            $hasDiscussions = \DB::table('discussions')->where('user_id', $user->user_id)->exists();
            $hasQuestions = \DB::table('questions')->where('presenter_id', $user->user_id)->exists();
            $hasBodyMemberships = \DB::table('body_members')->where('user_id', $user->user_id)->exists();
            $hasBodyChairmanship = \DB::table('bodies')->where('chairman_id', $user->user_id)->exists();
            $hasMeetingSecretaryship = \DB::table('meetings')->where('secretary_id', $user->user_id)->exists();
            $hasMeetingAttendance = \DB::table('meeting_attendance')->where('user_id', $user->user_id)->exists();
            $hasAuditLogs = \DB::table('audit_logs')->where('user_id', $user->user_id)->exists();

            // If user has any remaining data, skip permanent deletion
            if ($hasVotes || $hasDiscussions || $hasQuestions || $hasBodyMemberships || 
                $hasBodyChairmanship || $hasMeetingSecretaryship || $hasMeetingAttendance || $hasAuditLogs) {
                $this->warn("Skipped user ID {$user->user_id} ({$user->email}): Still has related data in other tables.");
                $skippedUsersCount++;
                continue;
            }

            // Safe to permanently delete
            $user->forceDelete();
            $deletedUsersCount++;
        }

        $this->info("Permanently deleted {$deletedUsersCount} soft-deleted user(s).");
        if ($skippedUsersCount > 0) {
            $this->info("Skipped {$skippedUsersCount} user(s) due to existing related data.");
        }

        // Delete meetings that finished voting more than retention period ago
        // This will cascade delete: questions, votes, attendance records, and discussions
        $oldMeetingsCount = Meeting::where('vote_end', '<=', $cutoffDate)
            ->where('status', 'Baigtas')
            ->count();

        if ($oldMeetingsCount > 0) {
            Meeting::where('vote_end', '<=', $cutoffDate)
                ->where('status', 'Baigtas')
                ->delete();

            $this->info("Deleted {$oldMeetingsCount} old meeting(s) and all related data (questions, votes, attendance, discussions).");
        } else {
            $this->info("No old meetings to delete.");
        }

        $this->info('Cleanup completed successfully.');

        return Command::SUCCESS;
    }
}
