<?php

namespace App\AI\RAG;

class FakeEmbeddingService implements EmbeddingService
{
    private const DIMENSIONS = 384;

    public function embed(array $texts): array
    {
        return array_map([$this, 'fakeEmbed'], $texts);
    }

    public function dimensions(): int
    {
        return self::DIMENSIONS;
    }

    private function fakeEmbed(string $text): array
    {
        $hash = md5($text);
        $vector = [];

        for ($i = 0; $i < self::DIMENSIONS; $i++) {
            $hex = substr($hash, ($i % 16) * 2, 2);
            $vector[] = (hexdec($hex) / 127.5) - 1.0;
        }

        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vector)));

        return $norm > 0
            ? array_map(fn ($v) => $v / $norm, $vector)
            : array_fill(0, self::DIMENSIONS, 0.0);
    }
}
