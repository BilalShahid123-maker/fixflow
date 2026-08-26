<?php

namespace Tests\Unit;

use App\AI\RAG\VectorSearch;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VectorSearchTest extends TestCase
{
    private VectorSearch $search;

    protected function setUp(): void
    {
        parent::setUp();
        $this->search = new VectorSearch;
    }

    #[Test]
    public function returns_top_k_results_ordered_by_score(): void
    {
        $query = [1.0, 0.0, 0.0];

        $chunks = [
            ['chunk_id' => 1, 'document_id' => 1, 'title' => 'Doc A', 'section' => null, 'content' => 'a', 'embedding' => [0.0, 1.0, 0.0]],
            ['chunk_id' => 2, 'document_id' => 1, 'title' => 'Doc B', 'section' => null, 'content' => 'b', 'embedding' => [1.0, 0.0, 0.0]],
            ['chunk_id' => 3, 'document_id' => 1, 'title' => 'Doc C', 'section' => null, 'content' => 'c', 'embedding' => [0.7071, 0.7071, 0.0]],
        ];

        $results = $this->search->search($query, $chunks, topK: 2);

        $this->assertCount(2, $results);
        $this->assertSame(2, $results[0]['chunk_id']);
        $this->assertSame(3, $results[1]['chunk_id']);
        $this->assertGreaterThan($results[1]['score'], $results[0]['score']);
    }

    #[Test]
    public function filters_out_results_below_min_score(): void
    {
        $query = [1.0, 0.0];
        $chunks = [
            ['chunk_id' => 1, 'document_id' => 1, 'title' => 'D', 'section' => null, 'content' => 'a', 'embedding' => [1.0, 0.0]],
            ['chunk_id' => 2, 'document_id' => 1, 'title' => 'D', 'section' => null, 'content' => 'b', 'embedding' => [0.0, 1.0]],
        ];

        $results = $this->search->search($query, $chunks, topK: 5, minScore: 0.5);

        $this->assertCount(1, $results);
        $this->assertSame(1, $results[0]['chunk_id']);
        $this->assertEqualsWithDelta(1.0, $results[0]['score'], 0.001);
    }

    #[Test]
    public function cosine_similarity_of_identical_vectors_is_one(): void
    {
        $query = [1.0, 2.0, 3.0];
        $chunks = [
            ['chunk_id' => 1, 'document_id' => 1, 'title' => 'D', 'section' => null, 'content' => 'x', 'embedding' => [1.0, 2.0, 3.0]],
        ];

        $results = $this->search->search($query, $chunks);

        $this->assertCount(1, $results);
        $this->assertEqualsWithDelta(1.0, $results[0]['score'], 0.001);
    }
}
