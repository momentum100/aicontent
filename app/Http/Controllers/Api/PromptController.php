<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Prompt::orderBy('name');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->get());
    }

    public function toggleActive(Prompt $prompt): JsonResponse
    {
        $prompt->update(['is_active' => !$prompt->is_active]);

        return response()->json($prompt);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:recipe,title,ingredients',
            'content' => 'required|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            Prompt::where('type', $validated['type'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $prompt = Prompt::create($validated);

        return response()->json($prompt, 201);
    }

    public function show(Prompt $prompt): JsonResponse
    {
        return response()->json($prompt);
    }

    public function update(Request $request, Prompt $prompt): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'type' => 'in:recipe,title,ingredients',
            'content' => 'string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            Prompt::where('type', $prompt->type)
                ->where('id', '!=', $prompt->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $prompt->update($validated);

        return response()->json($prompt);
    }

    public function destroy(Prompt $prompt): JsonResponse
    {
        $hasOtherDefaultOfType = Prompt::where('type', $prompt->type)
            ->where('id', '!=', $prompt->id)
            ->where('is_default', true)
            ->exists();

        if ($prompt->is_default && !$hasOtherDefaultOfType) {
            return response()->json([
                'message' => 'Cannot delete the only default prompt of this type',
            ], 422);
        }

        $prompt->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
