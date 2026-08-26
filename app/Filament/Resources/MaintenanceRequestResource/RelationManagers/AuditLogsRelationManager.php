<?php

namespace App\Filament\Resources\MaintenanceRequestResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $title = 'Audit history';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->color(fn ($state) => str_contains($state, 'failed') || str_contains($state, 'rejected')
                        ? Color::Rose
                        : Color::Indigo),
                Tables\Columns\TextColumn::make('actor_type')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('properties')
                    ->formatStateUsing(fn ($state) => $state !== null ? json_encode($state) : '—')
                    ->limit(80)
                    ->wrap(),
            ])
            ->filters([])
            ->headerActions([])
            ->bulkActions([]);
    }
}
