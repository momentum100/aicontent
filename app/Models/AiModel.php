<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModel extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'provider_id',
        'type',
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
        return $this->hasMany(Generation::class, 'model_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeImage($query)
    {
        return $query->where('type', 'image');
    }

    public function scopeText($query)
    {
        return $query->where('type', 'text');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
