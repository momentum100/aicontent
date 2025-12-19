<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;

class DefaultsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'models' => [
                'image' => AiModel::active()->image()->get(),
                'text' => AiModel::active()->text()->get(),
            ],
            'prompts' => [
                'recipe' => Prompt::active()->recipe()->get(),
                'title' => Prompt::active()->title()->get(),
                'ingredients' => Prompt::active()->ingredients()->get(),
            ],
            'defaults' => [
                'image_model' => AiModel::active()->image()->default()->first(),
                'text_model' => AiModel::active()->text()->default()->first(),
                'recipe_prompt' => Prompt::active()->recipe()->default()->first(),
                'title_prompt' => Prompt::active()->title()->default()->first(),
                'ingredients_prompt' => Prompt::active()->ingredients()->default()->first(),
            ],
        ]);
    }
}
