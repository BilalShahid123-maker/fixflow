<?php

namespace App\AI\RAG;

readonly class VectorSearch
{
    /**
     * @param  array<array{chunk_id: int, document_id: int, title: string, section: string, content: string, embedding: list<float>}>  $chunks
     * @return list<array{chunk_id: int, document_id: int, title: string, section: string, content: string, score: float}>
     */
    public function search(array $queryEmbedding, array $chunks, int $topK = 3, float $minScore = 0.0): array
    {
        $scored = [];

        foreach ($chunks as $chunk) {
            $score = $this->cosineSimilarity($queryEmbedding, $chunk['embedding']);

            if ($score >= $minScore) {
                $scored[] = [...$chunk, 'score' => round($score, 4)];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $topK);
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = $normA = $normB = 0.0;
        $len = min(count($a), count($b));

        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $denom = sqrt($normA) * sqrt($normB);

        return $denom > 0 ? $dot / $denom : 0.0;
    }
}
