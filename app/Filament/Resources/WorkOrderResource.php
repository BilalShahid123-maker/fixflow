<?php

namespace App\Filament\Resources;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource\Pages;
use App\Models\WorkOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Work orders';

    protected static ?string $navigationGroup = 'Dispatch';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Work order';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request details')
                    ->schema([
                        Forms\Components\TextInput::make('maintenance_request_id')
                            ->label('Request')
                            ->disabled(),
                        Forms\Components\TextInput::make('contractor_id')
                            ->label('Contractor')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options(WorkOrderStatus::class)
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Scheduling')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_for')
                            ->label('Scheduled from'),
                        Forms\Components\DateTimePicker::make('scheduled_until')
                            ->label('Scheduled until'),
                    ])->columns(2),

                Forms\Components\Section::make('Cost')
                    ->schema([
                        Forms\Components\TextInput::make('estimated_cost_cents')
                            ->label('Estimated cost (cents)')
                            ->numeric(),
                        Forms\Components\TextInput::make('final_cost_cents')
                            ->label('Final cost (cents)')
                            ->numeric(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('WO#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('request.title')
                    ->label('Request')
                    ->limit(40),
                Tables\Columns\TextColumn::make('contractor.name')
                    ->label('Contractor'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        WorkOrderStatus::Draft => 'gray',
                        WorkOrderStatus::Scheduled => 'info',
                        WorkOrderStatus::InProgress => 'warning',
                        WorkOrderStatus::Completed => 'success',
                        WorkOrderStatus::Cancelled => 'danger',
                    }),
                Tables\Columns\TextColumn::make('estimated_cost_cents')
                    ->label('Est. cost')
                    ->formatStateUsing(fn ($state) => $state !== null ? '$'.number_format($state / 100, 2) : '—'),
                Tables\Columns\TextColumn::make('scheduled_for')
                    ->label('Scheduled')
                    ->dateTime('M j, g:ia')
                    ->placeholder('Not scheduled'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(WorkOrderStatus::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Maintenance request')
                    ->schema([
                        Infolists\Components\TextEntry::make('request.title'),
                        Infolists\Components\TextEntry::make('request.category')
                            ->badge(),
                        Infolists\Components\TextEntry::make('request.severity')
                            ->badge(),
                        Infolists\Components\TextEntry::make('request.confidence')
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state * 100, 1).'%' : '—'),
                    ])->columns(4),

                Infolists\Components\Section::make('Work order')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('contractor.name'),
                        Infolists\Components\TextEntry::make('contractor.phone'),
                        Infolists\Components\TextEntry::make('scheduled_for')
                            ->dateTime('M j, g:ia')
                            ->placeholder('Not scheduled'),
                    ])->columns(4),

                Infolists\Components\Section::make('Cost')
                    ->schema([
                        Infolists\Components\TextEntry::make('estimated_cost_cents')
                            ->label('Estimated')
                            ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2)),
                        Infolists\Components\TextEntry::make('final_cost_cents')
                            ->label('Final')
                            ->formatStateUsing(fn ($state) => $state !== null ? '$'.number_format($state / 100, 2) : '—'),
                        Infolists\Components\TextEntry::make('approvedBy.name')
                            ->label('Approved by')
                            ->placeholder('Not yet approved'),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'view' => Pages\ViewWorkOrder::route('/{record}'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }
}
