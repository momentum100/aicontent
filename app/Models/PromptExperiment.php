<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptExperiment extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'prompt_id',
        'model_id',
        'recipe_name',
        'output',
        'tokens_used',
        'cost',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
            'cost' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }
}
