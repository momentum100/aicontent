<?php

namespace App\Console\Commands;

use App\Models\ScheduledPost;
use App\Services\PostizService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPostizQueue extends Command
{
    protected $signature = 'postiz:process';
    protected $description = 'Process pending Postiz scheduled posts';

    public function handle(PostizService $postizService): int
    {
        // Get pending posts that are due
        $pendingPosts = ScheduledPost::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->with('generation')
            ->orderBy('scheduled_at')
            ->limit(5) // Process max 5 per run to stay within rate limits
            ->get();

        if ($pendingPosts->isEmpty()) {
            $this->info('No pending posts to process.');
            return 0;
        }

        $this->info("Processing {$pendingPosts->count()} pending posts...");

        foreach ($pendingPosts as $post) {
            $this->processPost($post, $postizService);
        }

        return 0;
    }

    private function processPost(ScheduledPost $post, PostizService $postizService): void
    {
        $this->info("Processing post #{$post->id} for generation #{$post->generation_id}");

        try {
            $result = $postizService->schedulePost(
                $post->generation,
                $post->integration_id,
                $post->channel,
                $post->scheduled_at
            );

            // Get the post ID from result
            $postizPostId = $result[0]['postId'] ?? null;

            $post->update([
                'postiz_post_id' => $postizPostId,
                'status' => 'scheduled',
                'error_message' => null,
            ]);

            $this->info("  ✓ Post #{$post->id} scheduled successfully (Postiz ID: {$postizPostId})");

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Check if rate limited
            if (str_contains($errorMessage, 'Too Many Requests') || str_contains($errorMessage, '429')) {
                $this->warn("  ⏳ Rate limited. Post #{$post->id} will retry next run.");
                Log::warning("Postiz rate limited for post #{$post->id}");
                // Leave as pending, will retry next run
                return;
            }

            // Other error - mark as failed
            $post->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ]);

            $this->error("  ✗ Post #{$post->id} failed: {$errorMessage}");
            Log::error("Postiz post #{$post->id} failed: {$errorMessage}");
        }
    }
}
