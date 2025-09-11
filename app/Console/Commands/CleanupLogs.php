<?php

namespace App\Console\Commands;

use App\Services\RecordCleanupService;
use Illuminate\Console\Command;

class CleanupLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:cleanup-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old logs from database';

    /**
     * Execute the console command.
     */
    protected $recordCleanupService;

    public function __construct(RecordCleanupService $recordCleanupService)
    {
        parent::__construct();
        $this->recordCleanupService = $recordCleanupService;
    }

    // public function handle()
    // {
    //     $oneMonthAgo = Carbon::now()->subDays(30);

    //     DB::table('activity_logs')
    //         ->where('created_at', '<', $oneMonthAgo)
    //         ->delete();

    //     $this->info('Old logs cleaned up successfully!');
    // }

    public function handle()
    {
        $this->recordCleanupService->deleteOldRecords('activity_logs', 'created_at');
        $this->info('Old records deleted successfully.');
    }
}
