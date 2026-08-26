<?php

namespace Tests\Feature;

use App\AI\RAG\KnowledgeIngestion;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingest_creates_chunks_with_embeddings_and_metadata(): void
    {
        $doc = KnowledgeDocument::create([
            'title' => 'Plumbing basics',
            'source_ref' => 'guide-001',
            'content' => 'The most common plumbing issue is a leaking faucet. A running toilet wastes hundreds of gallons per year. Always check the shutoff valve before beginning repairs. Emergency leaks require immediate isolation at the main shutoff.',
            'is_published' => true,
        ]);

        $count = app(KnowledgeIngestion::class)->ingestDocument($doc);

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertSame($count, KnowledgeChunk::where('knowledge_document_id', $doc->getKey())->count());

        $firstChunk = KnowledgeChunk::where('knowledge_document_id', $doc->getKey())->first();
        $this->assertNotNull($firstChunk->embedding);
        $this->assertIsArray($firstChunk->embedding);
        $this->assertCount(384, $firstChunk->embedding);
        $this->assertNotNull($firstChunk->metadata);
    }

    public function test_reingest_replaces_chunks(): void
    {
        $doc = KnowledgeDocument::create([
            'title' => 'HVAC Guide',
            'source_ref' => 'guide-002',
            'content' => 'Replace HVAC filters every 90 days. Dirty filters cause poor air quality and system strain. Annual inspections prevent costly breakdowns.',
            'is_published' => true,
        ]);

        app(KnowledgeIngestion::class)->ingestDocument($doc);
        $before = KnowledgeChunk::where('knowledge_document_id', $doc->getKey())->count();

        app(KnowledgeIngestion::class)->reIngestDocument($doc);
        $after = KnowledgeChunk::where('knowledge_document_id', $doc->getKey())->count();

        $this->assertSame($before, $after);
    }
}
