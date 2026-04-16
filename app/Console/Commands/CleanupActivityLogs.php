<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class CleanupActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup-activity {--days=7 : Number of days to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete activity logs older than specified days (default: 7 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        
        $cutoffDate = now()->subDays($days);
        
        $deletedCount = ActivityLog::where('created_at', '<', $cutoffDate)->delete();
        
        $this->info("✓ Deleted {$deletedCount} activity log(s) older than {$days} days.");
        
        return Command::SUCCESS;
    }
}
