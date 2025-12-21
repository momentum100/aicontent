<?php

namespace App\Services;

use App\Contracts\ImageGeneratorInterface;
use App\Models\AiModel;
use App\Models\Prompt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AiImageService implements ImageGeneratorInterface
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
        $promptContent = str_replace('{{recipe_name}}', $recipeName, $prompt->content);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])->timeout(300)->post($this->baseUrl . '/chat/completions', [
            'model' => $model->provider_id,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $promptContent,
                ],
            ],
            'modalities' => ['image', 'text'],
            'image_config' => [
                'aspect_ratio' => '9:16',
            ],
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Image generation failed: ' . $response->body());
        }

        $data = $response->json();

        return $this->processResponse($data);
    }

    private function processResponse(array $data): array
    {
        $images = [];
        $textContent = '';
        $tokensUsed = $data['usage']['total_tokens'] ?? 0;
        $cost = $this->calculateCost($data);

        if (isset($data['choices'])) {
            foreach ($data['choices'] as $choice) {
                $message = $choice['message'] ?? [];

                // Check for images in message.images array (OpenRouter format)
                if (isset($message['images']) && is_array($message['images'])) {
                    foreach ($message['images'] as $item) {
                        if (isset($item['type']) && $item['type'] === 'image_url') {
                            $imageData = $item['image_url']['url'] ?? null;
                            if ($imageData) {
                                $savedPath = $this->saveImage($imageData);
                                if ($savedPath) {
                                    $images[] = $savedPath;
                                }
                            }
                        }
                    }
                }

                // Extract text content (instructions/recipe)
                $content = $message['content'] ?? '';
                if (is_string($content) && !empty($content)) {
                    $textContent = $content;
                } elseif (is_array($content)) {
                    foreach ($content as $item) {
                        if (isset($item['type']) && $item['type'] === 'image_url') {
                            $imageData = $item['image_url']['url'] ?? null;
                            if ($imageData) {
                                $savedPath = $this->saveImage($imageData);
                                if ($savedPath) {
                                    $images[] = $savedPath;
                                }
                            }
                        } elseif (isset($item['type']) && $item['type'] === 'text') {
                            $textContent .= $item['text'] ?? '';
                        }
                    }
                }
            }
        }

        // Save raw response to JSON log file
        $logPath = $this->saveRawResponseLog($data);

        return [
            'images' => array_values(array_unique($images)),
            'instructions' => $textContent,
            'tokens_used' => $tokensUsed,
            'cost' => $cost,
            'raw_response_log' => $logPath,
        ];
    }

    private function saveRawResponseLog(array $data): string
    {
        $filename = date('Y-m-d_H-i-s') . '_' . Str::uuid() . '.json';
        $path = 'logs/generations/' . date('Y/m/d') . '/' . $filename;

        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT));

        return $path;
    }

    private function saveImage(string $imageData): ?string
    {
        if (str_starts_with($imageData, 'data:image')) {
            $parts = explode(',', $imageData, 2);
            if (count($parts) === 2) {
                $imageData = base64_decode($parts[1]);
            }
        } elseif (filter_var($imageData, FILTER_VALIDATE_URL)) {
            $imageData = Http::get($imageData)->body();
        } else {
            $imageData = base64_decode($imageData);
        }

        if (!$imageData) {
            return null;
        }

        $filename = Str::uuid() . '.png';
        $path = 'generations/' . date('Y/m/d') . '/' . $filename;

        Storage::disk('public')->put($path, $imageData);

        return $path;
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
