<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Models\MaintenanceRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TriageStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $base = MaintenanceRequest::query();

        return [
            Stat::make('Open requests', (clone $base)->whereNotIn('status', [RequestStatus::Completed, RequestStatus::Rejected])->count())
                ->description('Not yet completed or rejected')
                ->color('info'),
            Stat::make('Awaiting approval', (clone $base)->where('status', RequestStatus::AwaitingApproval)->count())
                ->description('Low-confidence, needs a human')
                ->color('warning'),
            Stat::make('Critical / emergency', (clone $base)->where('emergency', true)->count())
                ->description('Safety hazards flagged by triage')
                ->color('danger'),
            Stat::make('Avg confidence', $this->averageConfidence())
                ->description('Across all triaged requests')
                ->color('success'),
        ];
    }

    private function averageConfidence(): string
    {
        $avg = MaintenanceRequest::query()
            ->whereNotNull('confidence')
            ->avg('confidence');

        return $avg !== null ? number_format($avg * 100, 1).'%' : '—';
    }
}
