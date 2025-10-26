<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateMeetingStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meetings:update-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update meeting statuses based on current time and voting periods';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $updated = 0;

        // Get all meetings that might need status updates
        $meetings = Meeting::whereIn('status', ['Suplanuotas', 'Vyksta'])
            ->orWhere(function ($query) use ($now) {
                $query->where('status', 'Baigtas')
                    ->where('vote_end', '>', $now);
            })
            ->get();

        foreach ($meetings as $meeting) {
            $oldStatus = $meeting->status;
            $newStatus = $this->calculateMeetingStatus($meeting, $now);

            if ($oldStatus !== $newStatus) {
                $meeting->status = $newStatus;
                $meeting->save();
                $updated++;

                Log::info('Meeting status updated', [
                    'meeting_id' => $meeting->meeting_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'vote_start' => $meeting->vote_start,
                    'vote_end' => $meeting->vote_end,
                ]);
            }
        }

        $this->info("Updated {$updated} meeting status(es).");

        return Command::SUCCESS;
    }

    /**
     * Calculate the correct status for a meeting based on current time.
     */
    private function calculateMeetingStatus(Meeting $meeting, $now): string
    {
        if ($now->lt($meeting->vote_start)) {
            return 'Suplanuotas';  // Planned
        } elseif ($now->between($meeting->vote_start, $meeting->vote_end)) {
            return 'Vyksta';       // In Progress
        } else {
            return 'Baigtas';      // Finished
        }
    }
}
