<?php

namespace App\Console\Commands;

use App\Services\EmailService;
use Illuminate\Console\Command;

class ProcessEmailQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:process-queue {--limit=5 : Number of emails to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending emails from the queue';

    /**
     * Execute the console command.
     */
    public function handle(EmailService $emailService): int
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Processing up to {$limit} emails from the queue...");
        
        $processed = $emailService->processPendingEmails($limit);
        
        $this->info("Processed {$processed} email(s).");
        
        return Command::SUCCESS;
    }
}
