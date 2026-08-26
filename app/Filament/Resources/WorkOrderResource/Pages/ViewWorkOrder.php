<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Actions\ApproveWorkOrder;
use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkOrder extends ViewRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('approve')
                ->label('Approve & dispatch')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->status === WorkOrderStatus::Draft)
                ->requiresConfirmation()
                ->modalHeading('Approve work order'
                )
                ->modalSubmitActionLabel('Approve & dispatch')
                ->action(function ($record) {
                    $record = app(ApproveWorkOrder::class)->execute($record, auth()->id());

                    Notification::make()
                        ->title("Work order #{$record->getKey()} dispatched to {$record->contractor->name}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
