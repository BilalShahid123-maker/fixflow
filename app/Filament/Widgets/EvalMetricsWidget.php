<?php

namespace App\Filament\Widgets;

use App\AI\Evaluation\MetricsCalculator;
use App\Models\AiRun;
use App\Models\EvalRun;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EvalMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $summary = (new MetricsCalculator)->summarize(
            EvalRun::query()->with('evalCase')->get(),
        );

        $latestModel = AiRun::query()
            ->whereNotNull('model')
            ->latest()
            ->value('model');

        return [
            Stat::make('Eval cases', $summary->total)
                ->description('Labeled requests in the set')
                ->color('gray'),
            Stat::make('Category accuracy', $this->pct($summary->categoryAccuracy))
                ->description($this->modelLabel($latestModel))
                ->color('info'),
            Stat::make('Severity accuracy', $this->pct($summary->severityAccuracy))
                ->description('Exact severity match')
                ->color('primary'),
            Stat::make('Critical recall', $this->pct($summary->criticalRecall))
                ->description('Emergencies correctly flagged')
                ->color('danger'),
        ];
    }

    private function pct(?float $value): string
    {
        return $value !== null ? number_format($value * 100, 1).'%' : '—';
    }

    private function modelLabel(?string $model): string
    {
        return $model !== null ? "Last model: {$model}" : 'No runs yet';
    }
}
