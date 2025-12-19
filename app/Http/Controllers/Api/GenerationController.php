<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateRecipeJob;
use App\Models\AiModel;
use App\Models\Generation;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GenerationController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $generations = $request->user()
            ->generations()
            ->with(['model', 'textModel', 'prompt'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($generations);
    }

    public function show(Request $request, Generation $generation): JsonResponse
    {
        if ($generation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $generation->load(['model', 'textModel', 'prompt', 'titlePrompt', 'ingredientsPrompt']);

        return response()->json($generation);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipe_name' => 'required|string|max:255',
            'model_id' => 'required|exists:ai_models,id',
            'prompt_id' => 'required|exists:prompts,id',
            'title_prompt_id' => 'nullable|exists:prompts,id',
            'ingredients_prompt_id' => 'nullable|exists:prompts,id',
        ]);

        $model = AiModel::findOrFail($validated['model_id']);
        $prompt = Prompt::findOrFail($validated['prompt_id']);

        $generation = Generation::create([
            'user_id' => $request->user()->id,
            'recipe_name' => $validated['recipe_name'],
            'model_id' => $model->id,
            'prompt_id' => $prompt->id,
            'title_prompt_id' => $validated['title_prompt_id'] ?? null,
            'ingredients_prompt_id' => $validated['ingredients_prompt_id'] ?? null,
            'status' => 'processing',
        ]);

        GenerateRecipeJob::dispatch(
            $generation->id,
            $validated['title_prompt_id'] ?? null,
            $validated['ingredients_prompt_id'] ?? null
        );

        return response()->json($generation->load(['model', 'textModel', 'prompt']));
    }

    public function status(Request $request, Generation $generation): JsonResponse
    {
        if ($generation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'id' => $generation->id,
            'status' => $generation->status,
        ]);
    }

    public function queueStats(): JsonResponse
    {
        $pending = DB::table('jobs')->count();
        $processing = Generation::where('status', 'processing')->count();

        return response()->json([
            'pending' => $pending,
            'processing' => $processing,
        ]);
    }

    public function toggleShare(Request $request, Generation $generation): JsonResponse
    {
        if ($generation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($generation->is_public) {
            $generation->revokeShareToken();
            return response()->json([
                'is_public' => false,
                'share_url' => null,
            ]);
        }

        $generation->generateShareToken();

        return response()->json([
            'is_public' => true,
            'share_url' => $generation->getShareUrl(),
            'share_token' => $generation->share_token,
        ]);
    }

    public function destroy(Request $request, Generation $generation): JsonResponse
    {
        if ($generation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $generation->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
