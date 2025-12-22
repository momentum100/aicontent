<?php

namespace App\Jobs;

use App\Models\AiModel;
use App\Models\Generation;
use App\Models\Prompt;
use App\Services\AiImageService;
use App\Services\AiTextService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class GenerateRecipeJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(
        public int $generationId,
        public ?int $titlePromptId = null,
        public ?int $ingredientsPromptId = null
    ) {}

    public function handle(AiImageService $imageService, AiTextService $textService): void
    {
        $generation = Generation::find($this->generationId);

        if (!$generation) {
            return;
        }

        try {
            $model = AiModel::findOrFail($generation->model_id);
            $prompt = Prompt::findOrFail($generation->prompt_id);

            $imageResult = $imageService->generate(
                $generation->recipe_name,
                $model,
                $prompt
            );

            $totalCost = $imageResult['cost'];
            $totalTokens = $imageResult['tokens_used'];

            $title = null;
            $ingredients = null;
            $textModelId = null;

            if ($this->titlePromptId) {
                $titlePrompt = Prompt::findOrFail($this->titlePromptId);
                $textModel = AiModel::active()->text()->default()->first();

                if ($textModel) {
                    $textModelId = $textModel->id;
                    $titleResult = $textService->generate(
                        $generation->recipe_name,
                        $textModel,
                        $titlePrompt
                    );
                    $title = $titleResult['text'];
                    $totalCost += $titleResult['cost'];
                    $totalTokens += $titleResult['tokens_used'];
                }
            }

            if ($this->ingredientsPromptId) {
                $ingredientsPrompt = Prompt::findOrFail($this->ingredientsPromptId);
                $textModel = AiModel::active()->text()->default()->first();

                if ($textModel) {
                    $textModelId = $textModel->id;
                    $ingredientsResult = $textService->generate(
                        $generation->recipe_name,
                        $textModel,
                        $ingredientsPrompt
                    );
                    $ingredients = $ingredientsResult['text'];
                    $totalCost += $ingredientsResult['cost'];
                    $totalTokens += $ingredientsResult['tokens_used'];
                }
            }

            DB::reconnect();

            $generation->update([
                'images' => $imageResult['images'],
                'title' => $title,
                'ingredients' => $ingredients,
                'instructions' => $imageResult['instructions'] ?? null,
                'tokens_used' => $totalTokens,
                'cost' => $totalCost,
                'text_model_id' => $textModelId,
                'status' => 'completed',
            ]);

        } catch (\Throwable $e) {
            DB::reconnect();

            $generation->update([
                'status' => 'failed',
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        DB::reconnect();

        $generation = Generation::find($this->generationId);

        if ($generation && $generation->status !== 'completed') {
            $generation->update(['status' => 'failed']);
        }

        \Log::error('GenerateRecipeJob failed', [
            'generation_id' => $this->generationId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
