<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Models\ScheduledPost;
use App\Services\PostizService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostizController extends Controller
{
    public function __construct(
        private PostizService $postizService
    ) {}

    /**
     * Get available integrations from Postiz
     */
    public function integrations(): JsonResponse
    {
        try {
            $integrations = $this->postizService->getIntegrations();
            return response()->json($integrations);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch integrations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule a post - saves locally, processed by queue command every 30 min
     */
    public function schedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'generation_id' => 'required|exists:generations,id',
            'integration_id' => 'required|string',
            'channel' => 'required|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $generation = Generation::findOrFail($validated['generation_id']);

        if ($generation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $scheduledAt = $validated['scheduled_at']
            ? Carbon::parse($validated['scheduled_at'])
            : now();

        // Build content for local record
        $content = $generation->title ?? $generation->recipe_name;
        if ($generation->ingredients) {
            $content .= "\n\n" . $generation->ingredients;
        }

        // Save locally as pending - will be processed by ProcessPostizQueue command
        $scheduledPost = ScheduledPost::create([
            'user_id' => $request->user()->id,
            'generation_id' => $generation->id,
            'postiz_post_id' => null,
            'channel' => $validated['channel'],
            'integration_id' => $validated['integration_id'],
            'scheduled_at' => $scheduledAt,
            'status' => 'pending',
            'content' => $content,
            'images' => $generation->images,
        ]);

        $scheduledPost->load('generation');

        return response()->json($scheduledPost);
    }

    /**
     * Get scheduled posts
     */
    public function posts(Request $request): JsonResponse
    {
        $posts = $request->user()
            ->scheduledPosts()
            ->with('generation')
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        return response()->json($posts);
    }

    /**
     * Retry a failed scheduled post
     */
    public function retry(Request $request, ScheduledPost $scheduledPost): JsonResponse
    {
        if ($scheduledPost->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($scheduledPost->status !== 'failed') {
            return response()->json(['message' => 'Only failed posts can be retried'], 400);
        }

        $scheduledPost->update([
            'status' => 'pending',
            'error_message' => null,
            'scheduled_at' => now(),
        ]);

        return response()->json(['message' => 'Post queued for retry']);
    }

    /**
     * Cancel/delete a scheduled post
     */
    public function destroy(Request $request, ScheduledPost $scheduledPost): JsonResponse
    {
        if ($scheduledPost->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Try to delete from Postiz if we have the post ID
        if ($scheduledPost->postiz_post_id) {
            try {
                $this->postizService->deletePost($scheduledPost->postiz_post_id);
            } catch (\Exception $e) {
                // Continue with local deletion even if Postiz fails
            }
        }

        $scheduledPost->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
