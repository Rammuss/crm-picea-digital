<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Auditoria';
    protected static ?int $navigationSort = 90;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s')->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Usuario')
                    ->searchable()
                    ->formatStateUsing(fn ($state): string => $state ?: 'sistema'),
                Tables\Columns\BadgeColumn::make('action')->label('Accion')->colors([
                    'success' => ['create', 'restore'],
                    'warning' => 'update',
                    'danger' => ['delete', 'force_delete'],
                ]),
                Tables\Columns\TextColumn::make('entity_type')->label('Modulo')->searchable(),
                Tables\Columns\TextColumn::make('entity_id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('old_values')
                    ->label('Antes')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE) : '-')
                    ->limit(80)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('new_values')
                    ->label('Despues')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE) : '-')
                    ->limit(80)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('action')->options([
                    'create' => 'Create',
                    'update' => 'Update',
                    'delete' => 'Delete',
                    'restore' => 'Restore',
                    'force_delete' => 'Force delete',
                ]),
                SelectFilter::make('entity_type')
                    ->label('Modulo')
                    ->options(fn () => ActivityLog::query()
                        ->select('entity_type')
                        ->distinct()
                        ->orderBy('entity_type')
                        ->pluck('entity_type', 'entity_type')
                        ->toArray()),
                SelectFilter::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'email'),
                Filter::make('hoy')
                    ->label('Solo hoy')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', now()->toDateString())),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
