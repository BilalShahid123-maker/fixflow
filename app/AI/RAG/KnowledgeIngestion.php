<?php

namespace App\AI\RAG;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;

class KnowledgeIngestion
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private TextSplitter $splitter,
        private EmbeddingService $embedding,
    ) {}

    public function ingestDocument(KnowledgeDocument $doc): int
    {
        $chunks = $this->splitter->split($doc->content);

        $knowledgeChunks = [];

        foreach ($chunks as $index => $chunk) {
            $knowledgeChunks[] = new KnowledgeChunk([
                'knowledge_document_id' => $doc->getKey(),
                'content' => $chunk['content'],
                'chunk_index' => $index,
                'token_count' => $chunk['word_count'],
                'metadata' => ['section' => $chunk['section'] ?? null],
            ]);
        }

        $this->storeChunks($knowledgeChunks);

        $ids = array_map(fn (KnowledgeChunk $c) => $c->getKey(), $knowledgeChunks);

        $this->embedChunks($ids);

        return count($ids);
    }

    public function reIngestDocument(KnowledgeDocument $doc): int
    {
        KnowledgeChunk::where('knowledge_document_id', $doc->getKey())->delete();

        return $this->ingestDocument($doc);
    }

    /**
     * @param  array<int, int>  $chunkIds
     */
    private function embedChunks(array $chunkIds): void
    {
        foreach (array_chunk($chunkIds, self::BATCH_SIZE) as $batch) {
            $chunks = KnowledgeChunk::whereIn('id', $batch)->get();

            $texts = $chunks->pluck('content')->all();

            $embeddings = $this->embedding->embed($texts);

            foreach ($chunks as $index => $chunk) {
                $chunk->update([
                    'embedding' => $embeddings[$index] ?? [],
                ]);
            }
        }
    }

    /**
     * @param  array<KnowledgeChunk>  $chunks
     */
    private function storeChunks(array $chunks): void
    {
        foreach (array_chunk($chunks, self::BATCH_SIZE) as $batch) {
            foreach ($batch as $chunk) {
                $chunk->save();
            }
        }
    }
}
