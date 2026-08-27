<?php

namespace App\Filament\Widgets;

use App\Models\AiRun;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CostStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalCost = (float) AiRun::query()->sum('cost_usd');
        $totalTokens = (int) AiRun::query()->sum('input_tokens') + (int) AiRun::query()->sum('output_tokens');
        $avgLatency = AiRun::query()->whereNotNull('latency_ms')->avg('latency_ms');
        $runsThisWeek = AiRun::query()->where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Total AI cost', $this->usd($totalCost))
                ->description('All triage runs')
                ->color('primary'),
            Stat::make('Tokens consumed', number_format($totalTokens))
                ->description('Input + output')
                ->color('info'),
            Stat::make('Avg latency', $avgLatency !== null ? round($avgLatency).' ms' : '—')
                ->description('Across runs')
                ->color('warning'),
            Stat::make('Runs (7 days)', number_format($runsThisWeek))
                ->description('Last 7 days')
                ->color('success'),
        ];
    }

    private function usd(float $value): string
    {
        return '$'.number_format($value, 4);
    }
}
