<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_type',
        'actor_id',
        'event',
        'subject_type',
        'subject_id',
        'properties',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(
        string $event,
        ?string $actorType = null,
        ?int $actorId = null,
        ?Model $subject = null,
        array $properties = [],
    ): self {
        return static::create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'created_at' => now(),
        ]);
    }
}
