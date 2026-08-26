<?php

namespace Tests\Unit;

use App\AI\RAG\TextSplitter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TextSplitterTest extends TestCase
{
    #[Test]
    public function short_text_stays_as_one_chunk(): void
    {
        $splitter = new TextSplitter(maxWords: 10);
        $chunks = $splitter->split('This is a short sentence.');

        $this->assertCount(1, $chunks);
        $this->assertSame(5, $chunks[0]['word_count']);
    }

    #[Test]
    public function long_text_splits_across_sentence_boundaries(): void
    {
        $splitter = new TextSplitter(maxWords: 8, overlapWords: 2);
        $text = 'The faucet is dripping. Water is pooling on the floor. The tenant reports constant noise. There is a bad smell coming from the drain.';

        $chunks = $splitter->split($text);

        $this->assertGreaterThanOrEqual(2, count($chunks));

        $allContent = implode(' ', array_column($chunks, 'content'));
        $this->assertStringContainsString('faucet', $allContent);
        $this->assertStringContainsString('drain', $allContent);
    }

    #[Test]
    public function overlap_words_appear_at_start_of_subsequent_chunk(): void
    {
        $splitter = new TextSplitter(maxWords: 8, overlapWords: 2);
        $text = 'Alpha bravo charlie delta. Echo foxtrot golf hotel. India juliet kilo lima. Mike november oscar papa.';

        $chunks = $splitter->split($text);

        $this->assertGreaterThanOrEqual(2, count($chunks));
    }
}
