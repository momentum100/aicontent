<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPost extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'generation_id',
        'postiz_post_id',
        'channel',
        'integration_id',
        'scheduled_at',
        'status',
        'content',
        'images',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'scheduled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }
}
