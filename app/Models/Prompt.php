<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prompt extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'type',
        'content',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class, 'prompt_id');
    }

    public function titleGenerations(): HasMany
    {
        return $this->hasMany(Generation::class, 'title_prompt_id');
    }

    public function ingredientsGenerations(): HasMany
    {
        return $this->hasMany(Generation::class, 'ingredients_prompt_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRecipe($query)
    {
        return $query->where('type', 'recipe');
    }

    public function scopeTitle($query)
    {
        return $query->where('type', 'title');
    }

    public function scopeIngredients($query)
    {
        return $query->where('type', 'ingredients');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
