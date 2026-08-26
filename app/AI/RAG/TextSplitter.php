<?php

namespace App\AI\RAG;

readonly class TextSplitter
{
    public function __construct(
        public int $maxWords = 500,
        public int $overlapWords = 50,
    ) {}

    /**
     * @return list<array{content: string, word_count: int}>
     */
    public function split(string $text): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if ($text === '' || str_word_count($text) <= $this->maxWords) {
            return [
                [
                    'content' => $text,
                    'word_count' => str_word_count($text),
                ],
            ];
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if ($sentences === false || $sentences === []) {
            return [
                [
                    'content' => $text,
                    'word_count' => str_word_count($text),
                ],
            ];
        }

        $chunks = [];
        $buffer = '';

        foreach ($sentences as $sentence) {
            $candidate = trim($buffer.' '.$sentence);

            if (str_word_count($candidate) >= $this->maxWords && $buffer !== '') {
                $chunks[] = [
                    'content' => trim($buffer),
                    'word_count' => str_word_count(trim($buffer)),
                ];

                $buffer = $this->takeOverlapWords($buffer).' '.$sentence;
            } else {
                $buffer = $candidate;
            }
        }

        if (trim($buffer) !== '') {
            $chunks[] = [
                'content' => trim($buffer),
                'word_count' => str_word_count(trim($buffer)),
            ];
        }

        return $chunks;
    }

    private function takeOverlapWords(string $text): string
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || count($words) <= $this->overlapWords) {
            return trim($text);
        }

        return implode(' ', array_slice($words, -$this->overlapWords));
    }
}
