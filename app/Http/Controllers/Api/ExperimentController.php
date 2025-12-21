<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Prompt;
use App\Models\PromptExperiment;
use App\Services\AiTextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExperimentController extends Controller
{
    public function __construct(
        private AiTextService $textService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $experiments = $request->user()
            ->promptExperiments()
            ->with(['prompt', 'model'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($experiments);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipe_name' => 'required|string|max:255',
            'prompt_id' => 'nullable|exists:prompts,id',
            'model_id' => 'required|exists:ai_models,id',
            'prompt_content' => 'required|string',
        ]);

        $model = AiModel::findOrFail($validated['model_id']);

        try {
            $result = $this->textService->generateWithContent(
                $validated['recipe_name'],
                $model,
                $validated['prompt_content']
            );

            $experiment = PromptExperiment::create([
                'user_id' => $request->user()->id,
                'prompt_id' => $validated['prompt_id'] ?? null,
                'model_id' => $model->id,
                'recipe_name' => $validated['recipe_name'],
                'prompt_content' => $validated['prompt_content'],
                'output' => $result['text'],
                'tokens_used' => $result['tokens_used'],
                'cost' => $result['cost'],
                'raw_response' => $result['raw_response'],
            ]);

            $experiment->load(['prompt', 'model']);

            return response()->json($experiment);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, PromptExperiment $experiment): JsonResponse
    {
        if ($experiment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $experiment->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
