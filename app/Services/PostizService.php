<?php

namespace App\Services;

use App\Models\Generation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PostizService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.postiz.api_key');
        $this->baseUrl = config('services.postiz.base_url');
    }

    /**
     * Get all connected integrations (channels)
     * Cached for 5 minutes to save API requests (30/hour limit)
     */
    public function getIntegrations(): array
    {
        return cache()->remember('postiz_integrations', 300, function () {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->get($this->baseUrl . '/integrations');

            if (!$response->successful()) {
                throw new RuntimeException('Failed to fetch integrations: ' . $response->body());
            }

            return $response->json();
        });
    }

    /**
     * Check if API key is valid
     */
    public function isConnected(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->get($this->baseUrl . '/is-connected');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Upload an image to Postiz
     * Returns array with id and path, or null if failed
     */
    public function uploadImage(string $imagePath): ?array
    {
        $fullPath = Storage::disk('public')->path($imagePath);

        if (!file_exists($fullPath)) {
            return null;
        }

        $response = Http::withHeaders([
            'Authorization' => $this->apiKey,
        ])->attach(
            'file',
            file_get_contents($fullPath),
            basename($imagePath)
        )->post($this->baseUrl . '/upload');

        if (!$response->successful()) {
            throw new RuntimeException('Failed to upload image: ' . $response->body());
        }

        $data = $response->json();
        // Return both id and path as required by Postiz API
        return [
            'id' => $data['id'] ?? null,
            'path' => $data['path'] ?? null,
        ];
    }

    /**
     * Schedule a post to Postiz
     * Note: API limit is 30 requests/hour. Each image upload = 1 request.
     */
    public function schedulePost(
        Generation $generation,
        string $integrationId,
        string $channel,
        ?Carbon $scheduledAt = null,
        int $maxImages = 4
    ): array {
        // Upload images first - Postiz requires id and path for each image
        // Limit images to save API requests (30/hour limit)
        $uploadedImages = [];
        $imagesToUpload = array_slice($generation->images ?? [], 0, $maxImages);

        foreach ($imagesToUpload as $imagePath) {
            $uploadedImage = $this->uploadImage($imagePath);
            if ($uploadedImage && $uploadedImage['id'] && $uploadedImage['path']) {
                $uploadedImages[] = $uploadedImage;
            }
        }

        // Build content
        $content = $generation->title ?? $generation->recipe_name;
        if ($generation->ingredients) {
            $content .= "\n\n" . $generation->ingredients;
        }

        // Date is always required - use now + 1 minute if posting immediately
        $postDate = $scheduledAt ?? Carbon::now()->addMinute();

        // Build channel-specific settings
        $settings = ['__type' => $channel];

        if ($channel === 'tiktok') {
            $settings = array_merge($settings, [
                'privacy_level' => 'PUBLIC_TO_EVERYONE',
                'duet' => false,
                'stitch' => false,
                'comment' => true,
                'autoAddMusic' => 'no',
                'brand_content_toggle' => false,
                'brand_organic_toggle' => false,
                'content_posting_method' => 'UPLOAD',
            ]);
        }

        // Build post payload
        $postData = [
            'type' => $scheduledAt ? 'schedule' : 'now',
            'date' => $postDate->toIso8601String(),
            'shortLink' => false,
            'tags' => [],
            'posts' => [
                [
                    'integration' => [
                        'id' => $integrationId,
                    ],
                    'value' => [
                        [
                            'content' => $content,
                            'image' => $uploadedImages,
                        ],
                    ],
                    'settings' => $settings,
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/posts', $postData);

        if (!$response->successful()) {
            throw new RuntimeException('Failed to schedule post: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get posts within a date range
     */
    public function getPosts(Carbon $startDate, Carbon $endDate): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->apiKey,
        ])->get($this->baseUrl . '/posts', [
            'startDate' => $startDate->toIso8601String(),
            'endDate' => $endDate->toIso8601String(),
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Failed to fetch posts: ' . $response->body());
        }

        return $response->json()['posts'] ?? [];
    }

    /**
     * Delete a post
     */
    public function deletePost(string $postId): bool
    {
        $response = Http::withHeaders([
            'Authorization' => $this->apiKey,
        ])->delete($this->baseUrl . '/posts/' . $postId);

        return $response->successful();
    }
}
