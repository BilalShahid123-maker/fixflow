<?php

namespace App\AI\Evaluation;

readonly class EvaluationSummary
{
    public function __construct(
        public int $total,
        public float $categoryAccuracy,
        public float $severityAccuracy,
        public ?float $criticalRecall,
        public ?float $averageConfidence,
    ) {}
}
