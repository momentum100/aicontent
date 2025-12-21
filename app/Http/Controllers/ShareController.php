<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ShareController extends Controller
{
    public function show(int $id, string $token): View|JsonResponse
    {
        $generation = Generation::where('id', $id)
            ->where('share_token', $token)
            ->where('is_public', true)
            ->with(['model', 'prompt'])
            ->firstOrFail();

        if (request()->expectsJson()) {
            return response()->json([
                'recipe_name' => $generation->recipe_name,
                'title' => $generation->title,
                'ingredients' => $generation->ingredients,
                'images' => $generation->images,
                'created_at' => $generation->created_at,
            ]);
        }

        return view('share.show', compact('generation'));
    }
}
