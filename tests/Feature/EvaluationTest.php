<?php

namespace Tests\Feature;

use App\AI\Evaluation\EvalRunner;
use App\AI\Evaluation\MetricsCalculator;
use App\Models\EvalCase;
use App\Models\EvalRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_runner_records_eval_runs_for_each_case(): void
    {
        EvalCase::create([
            'title' => 'Leaking faucet',
            'description' => 'Water dripping from the kitchen faucet continuously.',
            'category' => 'plumbing',
            'severity' => 'high',
            'emergency' => false,
            'split' => 'eval',
        ]);
        EvalCase::create([
            'title' => 'Smoke from outlet',
            'description' => 'Smoke coming from the electrical outlet. Smells burnt.',
            'category' => 'electrical',
            'severity' => 'critical',
            'emergency' => true,
            'split' => 'eval',
        ]);

        $runs = app(EvalRunner::class)->runAll('eval');

        $this->assertCount(2, $runs);
        $this->assertSame(2, EvalRun::count());

        $summary = (new MetricsCalculator)->summarize($runs);
        $this->assertSame(2, $summary->total);
        $this->assertGreaterThanOrEqual(0.0, $summary->categoryAccuracy);
        $this->assertNotNull($summary->criticalRecall);
    }

    public function test_run_all_respects_split_filter(): void
    {
        EvalCase::create([
            'title' => 'Train case', 'description' => 'x', 'category' => 'plumbing',
            'severity' => 'medium', 'emergency' => false, 'split' => 'train',
        ]);

        $runs = app(EvalRunner::class)->runAll('eval');

        $this->assertCount(0, $runs);
    }
}
