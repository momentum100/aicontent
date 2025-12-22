<?php

namespace App\Console\Commands;

use App\Models\Generation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupStaleJobs extends Command
{
    protected $signature = 'queue:cleanup-stale
                          {--age=3600 : Age in seconds (default: 60 minutes)}
                          {--dry-run : Show what would be cleaned}';

    protected $description = 'Clean up stale jobs and reset orphaned generations';

    public function handle(): int
    {
        $age = (int) $this->option('age');
        $dryRun = $this->option('dry-run');
        $staleTime = now()->subSeconds($age);

        // Find stale generations (status=processing, updated >60min ago)
        $staleGenerations = Generation::where('status', 'processing')
            ->where('updated_at', '<', $staleTime)
            ->get();

        if ($staleGenerations->count() > 0) {
            $this->table(
                ['ID', 'Recipe Name', 'Status', 'Age (minutes)'],
                $staleGenerations->map(fn($g) => [
                    $g->id,
                    $g->recipe_name,
                    $g->status,
                    $g->updated_at->diffInMinutes(now()),
                ])
            );

            if (!$dryRun) {
                Generation::where('status', 'processing')
                    ->where('updated_at', '<', $staleTime)
                    ->update(['status' => 'failed']);

                $this->info("Marked {$staleGenerations->count()} stale generations as failed");
            }
        } else {
            $this->info('No stale generations found');
        }

        // Find zombie jobs in queue
        $zombieJobs = DB::table('jobs')
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '<', $staleTime->timestamp)
            ->count();

        if ($zombieJobs > 0) {
            $this->info("Found {$zombieJobs} zombie jobs in queue");

            if (!$dryRun) {
                DB::table('jobs')
                    ->whereNotNull('reserved_at')
                    ->where('reserved_at', '<', $staleTime->timestamp)
                    ->delete();

                $this->info("Deleted {$zombieJobs} zombie jobs");
            }
        }

        if ($dryRun) {
            $this->warn('DRY RUN - no changes made');
        } else {
            $this->info('Cleanup complete');
        }

        return Command::SUCCESS;
    }
}
