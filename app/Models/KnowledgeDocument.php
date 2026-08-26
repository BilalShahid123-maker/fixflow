<?php

namespace App\Models;

use App\AI\RAG\KnowledgeIngestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeDocument extends Model
{
    protected $fillable = [
        'title',
        'source_ref',
        'content',
        'version',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'knowledge_document_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function ingest(): int
    {
        return app(KnowledgeIngestion::class)->ingestDocument($this);
    }

    public function reIngest(): int
    {
        return app(KnowledgeIngestion::class)->reIngestDocument($this);
    }
}
