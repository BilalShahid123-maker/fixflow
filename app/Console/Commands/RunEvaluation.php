<?php

namespace App\Console\Commands;

use App\AI\Evaluation\EvalRunner;
use App\AI\Evaluation\MetricsCalculator;
use Illuminate\Console\Command;

class RunEvaluation extends Command
{
    protected $signature = 'eval:run {--split=eval : Comma-separated splits, or "all"}';

    protected $description = 'Run the triage agent over the labeled eval set and report metrics';

    public function handle(EvalRunner $runner, MetricsCalculator $metrics): int
    {
        $split = $this->option('split');

        $splits = $split === 'all' ? ['train', 'eval', 'holdout'] : explode(',', $split);

        foreach ($splits as $s) {
            $runs = $runner->runAll(trim($s));
            $summary = $metrics->summarize($runs);

            $this->line('');
            $this->info("Split [{$s}]: {$summary->total} cases");

            if ($summary->total === 0) {
                continue;
            }

            $this->line('  Category accuracy : '.number_format($summary->categoryAccuracy * 100, 1).'%');
            $this->line('  Severity accuracy : '.number_format($summary->severityAccuracy * 100, 1).'%');
            $this->line('  Critical recall   : '.($summary->criticalRecall !== null
                ? number_format($summary->criticalRecall * 100, 1).'%'
                : 'n/a'));
            $this->line('  Avg confidence    : '.($summary->averageConfidence !== null
                ? number_format($summary->averageConfidence * 100, 1).'%'
                : 'n/a'));
        }

        return self::SUCCESS;
    }
}
