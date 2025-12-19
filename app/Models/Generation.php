<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Generation extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'recipe_name',
        'model_id',
        'prompt_id',
        'title_prompt_id',
        'ingredients_prompt_id',
        'title',
        'ingredients',
        'instructions',
        'images',
        'tokens_used',
        'cost',
        'raw_response',
        'status',
        'share_token',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'raw_response' => 'array',
            'cost' => 'decimal:6',
            'is_public' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($generation) {
            if ($generation->is_public && !$generation->share_token) {
                $generation->share_token = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }

    public function titlePrompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class, 'title_prompt_id');
    }

    public function ingredientsPrompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class, 'ingredients_prompt_id');
    }

    public function generateShareToken(): string
    {
        $this->share_token = Str::random(32);
        $this->is_public = true;
        $this->save();

        return $this->share_token;
    }

    public function revokeShareToken(): void
    {
        $this->share_token = null;
        $this->is_public = false;
        $this->save();
    }

    public function getShareUrl(): ?string
    {
        if (!$this->share_token) {
            return null;
        }

        return route('share.show', $this->share_token);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true)->whereNotNull('share_token');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
