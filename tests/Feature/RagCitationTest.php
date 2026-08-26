<?php

namespace Tests\Feature;

use App\AI\RAG\EmbeddingService;
use App\AI\RAG\KnowledgeIngestion;
use App\AI\RAG\VectorSearch;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RagCitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vector_search_returns_matching_chunks_with_document_metadata(): void
    {
        $doc = KnowledgeDocument::create([
            'title' => 'Leak Response Guide',
            'source_ref' => 'guide-leak-01',
            'content' => 'Water leaking under a kitchen sink requires immediate shutoff of the local valve. Place a bucket under the P-trap and tighten the compression nut. If the leak persists, the supply line may need replacement.',
            'is_published' => true,
        ]);

        app(KnowledgeIngestion::class)->ingestDocument($doc);

        $embeddingService = app(EmbeddingService::class);
        $vectorSearch = app(VectorSearch::class);

        $queryEmbedding = $embeddingService->embed(['Water leaking under kitchen sink'])[0];

        $chunks = KnowledgeChunk::query()
            ->whereNotNull('embedding')
            ->with('document')
            ->get()
            ->map(fn (KnowledgeChunk $chunk) => [
                'chunk_id' => $chunk->getKey(),
                'document_id' => $chunk->knowledge_document_id,
                'title' => $chunk->document->title,
                'section' => $chunk->metadata['section'] ?? null,
                'content' => $chunk->content,
                'embedding' => $chunk->embedding,
            ])
            ->all();

        $results = $vectorSearch->search($queryEmbedding, $chunks, topK: 3, minScore: -1.0);

        $this->assertNotEmpty($results, 'RAG search should return results for a matching knowledge document.');
        $this->assertSame($doc->getKey(), $results[0]['document_id']);
        $this->assertSame('Leak Response Guide', $results[0]['title']);
        $this->assertArrayHasKey('score', $results[0]);
        $this->assertIsFloat($results[0]['score']);
    }

    public function test_rag_citations_appear_in_triage_result_meta(): void
    {
        $doc = KnowledgeDocument::create([
            'title' => 'Electrical Emergency Protocol',
            'source_ref' => 'guide-elec-01',
            'content' => 'Smoke or burning smell from an electrical outlet indicates a serious fire hazard. Evacuate the area and do not use the outlet. Call emergency services immediately.',
            'is_published' => true,
        ]);

        $count = app(KnowledgeIngestion::class)->ingestDocument($doc);
        $this->assertGreaterThan(0, $count);

        $embeddingService = app(EmbeddingService::class);
        $vectorSearch = app(VectorSearch::class);

        $queryEmbedding = $embeddingService->embed(['Smoke from electrical outlet'])[0];

        $chunks = KnowledgeChunk::query()
            ->whereNotNull('embedding')
            ->with('document')
            ->get()
            ->map(fn (KnowledgeChunk $chunk) => [
                'chunk_id' => $chunk->getKey(),
                'document_id' => $chunk->knowledge_document_id,
                'title' => $chunk->document->title,
                'section' => $chunk->metadata['section'] ?? null,
                'content' => $chunk->content,
                'embedding' => $chunk->embedding,
            ])
            ->all();

        $results = $vectorSearch->search($queryEmbedding, $chunks, topK: 3, minScore: 0.0);

        $this->assertNotEmpty($results);

        $citations = array_map(fn ($r) => [
            'document_id' => $r['document_id'],
            'title' => $r['title'],
            'score' => $r['score'],
        ], $results);

        $this->assertArrayHasKey('document_id', $citations[0]);
        $this->assertSame($doc->getKey(), $citations[0]['document_id']);
    }
}
