<?php

namespace App\AI\RAG;

use Prism\Prism\Facades\Prism;

class LlmEmbeddingService implements EmbeddingService
{
    private string $provider;

    private string $model;

    private int $dims;

    public function __construct()
    {
        $this->provider = config('fixflow.rag.embedding.provider', 'openai');
        $this->model = config('fixflow.rag.embedding.model', 'text-embedding-3-small');
        $this->dims = (int) config('fixflow.rag.embedding.dimensions', 1536);
    }

    public function embed(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $response = Prism::embeddings()
            ->using($this->provider, $this->model)
            ->fromArray($texts)
            ->asEmbeddings();

        return array_map(
            fn ($embedding) => array_map('floatval', $embedding->embedding),
            $response->embeddings,
        );
    }

    public function dimensions(): int
    {
        return $this->dims;
    }
}
