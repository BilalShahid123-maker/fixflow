<?php

namespace App\Filament\Resources;

use App\Actions\ApproveReview;
use App\Actions\RejectReview;
use App\Enums\RequestStatus;
use App\Enums\Severity;
use App\Filament\Resources\MaintenanceRequestResource\Pages;
use App\Filament\Resources\MaintenanceRequestResource\RelationManagers\AuditLogsRelationManager;
use App\Models\MaintenanceRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceRequestResource extends Resource
{
    protected static ?string $model = MaintenanceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Requests';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Textarea::make('description')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Select::make('unit_id')
                ->relationship('unit', 'label')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width(40),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('semibold')
                    ->limit(45),
                Tables\Columns\TextColumn::make('unit.label')
                    ->label('Unit')
                    ->badge()
                    ->color(Color::Slate),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->value ?? '—')
                    ->placeholder('untriaged'),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->value ?? '—')
                    ->color(fn ($state) => match ($state) {
                        Severity::Critical => Color::Rose,
                        Severity::High => Color::Orange,
                        Severity::Medium => Color::Amber,
                        Severity::Low => Color::Gray,
                        default => Color::Slate,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('confidence')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state * 100, 0).'%' : '—')
                    ->color(fn ($state) => match (true) {
                        $state === null => Color::Gray,
                        $state >= 0.9 => Color::Emerald,
                        $state >= 0.7 => Color::Amber,
                        default => Color::Rose,
                    }),
                Tables\Columns\IconColumn::make('emergency')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor(Color::Rose)
                    ->falseColor(Color::Gray),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => str($state->value)->replace('_', ' ')->title())
                    ->color(fn ($state) => match ($state) {
                        RequestStatus::Triaged => Color::Emerald,
                        RequestStatus::AwaitingApproval => Color::Amber,
                        RequestStatus::Dispatched => Color::Sky,
                        RequestStatus::InProgress => Color::Indigo,
                        RequestStatus::Completed => Color::Gray,
                        RequestStatus::Rejected => Color::Rose,
                        RequestStatus::PendingTriage => Color::Slate,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(RequestStatus::cases())->mapWithKeys(
                        fn ($case) => [$case->value => str($case->value)->replace('_', ' ')->title()],
                    )),
                Tables\Filters\SelectFilter::make('severity')
                    ->options(collect(Severity::cases())->mapWithKeys(
                        fn ($case) => [$case->value => ucfirst($case->value)],
                    ))
                    ->query(fn (Builder $query, array $data) => $data['value'] === null
                        ? $query
                        : $query->where('severity', $data['value'])),
                Tables\Filters\TernaryFilter::make('emergency'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Emerald)
                    ->requiresConfirmation()
                    ->modalDescription('Approve this request and move it to dispatch preparation?')
                    ->visible(fn (MaintenanceRequest $record) => $record->status === RequestStatus::AwaitingApproval)
                    ->action(fn (MaintenanceRequest $record) => app(ApproveReview::class)->execute($record, auth()->id())),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color(Color::Rose)
                    ->requiresConfirmation()
                    ->visible(fn (MaintenanceRequest $record) => $record->status === RequestStatus::AwaitingApproval)
                    ->action(fn (MaintenanceRequest $record) => app(RejectReview::class)->execute($record, auth()->id())),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            AuditLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaintenanceRequests::route('/'),
            'view' => Pages\ViewMaintenanceRequest::route('/{record}'),
            'edit' => Pages\EditMaintenanceRequest::route('/{record}/edit'),
        ];
    }
}
