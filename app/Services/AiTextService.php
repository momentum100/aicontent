<?php

namespace App\Services;

use App\Contracts\TextGeneratorInterface;
use App\Models\AiModel;
use App\Models\Prompt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiTextService implements TextGeneratorInterface
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
        $this->baseUrl = config('services.openrouter.base_url');
    }

    public function generate(string $recipeName, AiModel $model, Prompt $prompt): array
    {
        return $this->generateWithContent($recipeName, $model, $prompt->content);
    }

    public function generateWithContent(string $recipeName, AiModel $model, string $promptContent): array
    {
        $promptContent = str_replace('{{recipe_name}}', $recipeName, $promptContent);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])->timeout(60)->post($this->baseUrl . '/chat/completions', [
            'model' => $model->provider_id,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $promptContent,
                ],
            ],
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Text generation failed: ' . $response->body());
        }

        $data = $response->json();

        return $this->processResponse($data);
    }

    private function processResponse(array $data): array
    {
        $text = '';
        $tokensUsed = $data['usage']['total_tokens'] ?? 0;
        $cost = $this->calculateCost($data);

        if (isset($data['choices'][0]['message']['content'])) {
            $text = $data['choices'][0]['message']['content'];
        }

        return [
            'text' => $text,
            'tokens_used' => $tokensUsed,
            'cost' => $cost,
            'raw_response' => $data,
        ];
    }

    private function calculateCost(array $data): float
    {
        $usage = $data['usage'] ?? [];
        $promptTokens = $usage['prompt_tokens'] ?? 0;
        $completionTokens = $usage['completion_tokens'] ?? 0;

        $promptCost = ($promptTokens / 1000) * 0.01;
        $completionCost = ($completionTokens / 1000) * 0.03;

        return $promptCost + $completionCost;
    }
}
