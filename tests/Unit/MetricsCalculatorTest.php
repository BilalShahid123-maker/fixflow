<?php

namespace Tests\Unit;

use App\AI\Evaluation\MetricsCalculator;
use App\Models\EvalCase;
use App\Models\EvalRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function makeRun(bool $cat, bool $sev, bool $critExpected = false, bool $critCorrect = false, ?float $conf = 0.8): EvalRun
    {
        $case = EvalCase::create([
            'title' => 'x', 'description' => 'y', 'category' => 'plumbing',
            'severity' => 'medium', 'emergency' => $critExpected, 'split' => 'eval',
        ]);

        return EvalRun::create([
            'eval_case_id' => $case->getKey(),
            'category_expected' => 'plumbing', 'category_predicted' => 'plumbing',
            'severity_expected' => 'medium', 'severity_predicted' => 'medium',
            'category_correct' => $cat, 'severity_correct' => $sev,
            'critical_expected' => $critExpected, 'critical_correct' => $critCorrect,
            'confidence' => $conf,
        ]);
    }

    public function test_empty_set_returns_zeroes(): void
    {
        $summary = (new MetricsCalculator)->summarize(collect());

        $this->assertSame(0, $summary->total);
        $this->assertSame(0.0, $summary->categoryAccuracy);
        $this->assertNull($summary->criticalRecall);
    }

    public function test_accuracy_calculated_correctly(): void
    {
        $this->makeRun(true, true);
        $this->makeRun(true, false);
        $this->makeRun(false, true);
        $this->makeRun(false, false);

        $summary = (new MetricsCalculator)->summarize(EvalRun::all());

        $this->assertSame(4, $summary->total);
        $this->assertSame(0.5, $summary->categoryAccuracy);
        $this->assertSame(0.5, $summary->severityAccuracy);
    }

    public function test_critical_recall_counts_detected_over_expected(): void
    {
        $expected = 3;
        $this->makeRun(true, true, true, true);
        $this->makeRun(true, true, true, false);
        $this->makeRun(true, true, true, true);

        $this->makeRun(true, true, false, true);

        $summary = (new MetricsCalculator)->summarize(EvalRun::all());

        $this->assertSame($expected, 3);
        $this->assertEqualsWithDelta(2 / 3, $summary->criticalRecall, 0.001);
    }
}
