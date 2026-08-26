<?php

namespace App\Filament\Resources\MaintenanceRequestResource\Pages;

use App\Actions\ApproveReview;
use App\Actions\RejectReview;
use App\Enums\RequestStatus;
use App\Filament\Resources\MaintenanceRequestResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewMaintenanceRequest extends ViewRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Tenant request')
                ->schema([
                    TextEntry::make('title')->weight('bold'),
                    TextEntry::make('description')->prose()->columnSpanFull(),
                    Grid::make(3)->schema([
                        TextEntry::make('unit.label')->label('Unit'),
                        TextEntry::make('unit.property.name')->label('Property'),
                        TextEntry::make('tenant.name')->label('Tenant')
                            ->placeholder('—'),
                        TextEntry::make('created_at')->dateTime('M j, Y H:i'),
                        TextEntry::make('status')->badge(),
                        IconEntry::make('emergency')->boolean()
                            ->trueIcon('heroicon-o-exclamation-triangle')
                            ->falseIcon('heroicon-o-minus'),
                    ]),
                ])
                ->columnSpan(['lg' => 2]),

            Section::make('AI triage')
                ->description('Latest run of the triage agent for this request.')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('ai.category')
                            ->state(fn ($record) => $record->category?->value ?? 'untriaged')
                            ->badge(),
                        TextEntry::make('ai.severity')
                            ->state(fn ($record) => $record->severity?->value ?? '—')
                            ->badge(),
                        TextEntry::make('ai.confidence')
                            ->label('Confidence')
                            ->state(fn ($record) => $record->confidence !== null
                                ? number_format($record->confidence * 100, 0).'%'
                                : '—'),
                    ]),
                    TextEntry::make('ai.reasoning')
                        ->label('Reasoning')
                        ->state(fn ($record) => $record->latestAiRun?->output['reasoning'] ?? 'No AI run yet.')
                        ->prose()
                        ->columnSpanFull(),
                    Grid::make(4)->schema([
                        TextEntry::make('ai.model')
                            ->label('Model')
                            ->state(fn ($record) => $record->latestAiRun?->model ?? '—'),
                        TextEntry::make('ai.latency')
                            ->label('Latency')
                            ->state(function ($record) {
                                $latency = $record->latestAiRun?->latency_ms;

                                return $latency !== null ? $latency.' ms' : '—';
                            }),
                        TextEntry::make('ai.tokens')
                            ->label('Tokens (in/out)')
                            ->state(function ($record) {
                                $run = $record->latestAiRun;

                                return $run !== null ? "{$run->input_tokens} / {$run->output_tokens}" : '—';
                            }),
                        TextEntry::make('ai.cost')
                            ->label('Est. cost')
                            ->state(function ($record) {
                                $cost = $record->latestAiRun?->cost_usd;

                                return $cost !== null ? '$'.number_format($cost, 5) : '—';
                            }),
                    ]),
                ])
                ->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->icon('heroicon-o-check-circle')
                ->color(Color::Emerald)
                ->requiresConfirmation()
                ->visible(fn () => $this->getRecord()->status === RequestStatus::AwaitingApproval)
                ->action(fn () => app(ApproveReview::class)->execute($this->getRecord(), auth()->id())),
            Action::make('reject')
                ->icon('heroicon-o-x-circle')
                ->color(Color::Rose)
                ->requiresConfirmation()
                ->visible(fn () => $this->getRecord()->status === RequestStatus::AwaitingApproval)
                ->action(fn () => app(RejectReview::class)->execute($this->getRecord(), auth()->id())),
        ];
    }
}
