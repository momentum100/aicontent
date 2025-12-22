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
     * Schedule a post
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
            : null;

        try {
            $result = $this->postizService->schedulePost(
                $generation,
                $validated['integration_id'],
                $validated['channel'],
                $scheduledAt
            );

            // Get the post ID from result
            $postizPostId = $result[0]['postId'] ?? null;

            // Build content for local record
            $content = $generation->title ?? $generation->recipe_name;
            if ($generation->ingredients) {
                $content .= "\n\n" . $generation->ingredients;
            }

            $scheduledPost = ScheduledPost::create([
                'user_id' => $request->user()->id,
                'generation_id' => $generation->id,
                'postiz_post_id' => $postizPostId,
                'channel' => $validated['channel'],
                'integration_id' => $validated['integration_id'],
                'scheduled_at' => $scheduledAt ?? now(),
                'status' => 'scheduled',
                'content' => $content,
                'images' => $generation->images,
            ]);

            $scheduledPost->load('generation');

            return response()->json($scheduledPost);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to schedule post: ' . $e->getMessage()
            ], 500);
        }
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
