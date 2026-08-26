<?php

namespace App\AI\RAG;

interface EmbeddingService
{
    /**
     * @param  list<string>  $texts
     * @return list<array<int, float>>
     */
    public function embed(array $texts): array;

    public function dimensions(): int;
}
