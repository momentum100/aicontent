<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    public function index(): JsonResponse
    {
        $models = AiModel::orderBy('name')->get();

        return response()->json($models);
    }

    public function toggleActive(AiModel $model): JsonResponse
    {
        $model->update(['is_active' => !$model->is_active]);

        return response()->json($model);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider_id' => 'required|string|max:255',
            'type' => 'required|in:image,text',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            AiModel::where('type', $validated['type'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $model = AiModel::create($validated);

        return response()->json($model, 201);
    }

    public function show(AiModel $model): JsonResponse
    {
        return response()->json($model);
    }

    public function update(Request $request, AiModel $model): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'provider_id' => 'string|max:255',
            'type' => 'in:image,text',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            AiModel::where('type', $model->type)
                ->where('id', '!=', $model->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $model->update($validated);

        return response()->json($model);
    }

    public function destroy(AiModel $model): JsonResponse
    {
        $model->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
