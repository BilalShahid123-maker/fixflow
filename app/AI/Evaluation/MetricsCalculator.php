<?php

namespace App\AI\Evaluation;

use App\Models\EvalRun;
use Illuminate\Support\Collection;

readonly class MetricsCalculator
{
    /**
     * @param  Collection<int, EvalRun>  $runs
     */
    public function summarize($runs): EvaluationSummary
    {
        $total = $runs->count();

        if ($total === 0) {
            return new EvaluationSummary(
                total: 0,
                categoryAccuracy: 0.0,
                severityAccuracy: 0.0,
                criticalRecall: null,
                averageConfidence: null,
            );
        }

        $categoryCorrect = $runs->where('category_correct', true)->count();
        $severityCorrect = $runs->where('severity_correct', true)->count();

        $criticalExpected = $runs->where('critical_expected', true);
        $criticalDetected = $criticalExpected->where('critical_correct', true)->count();

        $avgConfidence = $runs->whereNotNull('confidence')->avg('confidence');

        return new EvaluationSummary(
            total: $total,
            categoryAccuracy: round($categoryCorrect / $total, 4),
            severityAccuracy: round($severityCorrect / $total, 4),
            criticalRecall: $criticalExpected->isNotEmpty()
                ? round($criticalDetected / $criticalExpected->count(), 4)
                : null,
            averageConfidence: $avgConfidence !== null ? round($avgConfidence, 4) : null,
        );
    }
}
