<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Clientes';

    private static function isAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return self::isAdmin();
    }

    public static function canForceDeleteAny(): bool
    {
        return self::isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('codigo_cliente')->label('Codigo cliente')->disabled()->dehydrated(false),
            Forms\Components\Toggle::make('activo')->default(true),
            Forms\Components\TextInput::make('nombre')->required()->maxLength(255),
            Forms\Components\TextInput::make('telefono')->maxLength(255),
            Forms\Components\TextInput::make('email')->email()->maxLength(255),
            Forms\Components\Select::make('origen')->options([
                'whatsapp' => 'WhatsApp',
                'instagram' => 'Instagram',
                'facebook' => 'Facebook',
                'referido' => 'Referido',
                'otro' => 'Otro',
            ])->default('whatsapp')->required(),
            Forms\Components\Textarea::make('notas')->rows(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo_cliente')->label('Codigo')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('telefono')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\BadgeColumn::make('activo')
                    ->label('Estado')
                    ->enum([true => 'Activo', false => 'Inactivo'])
                    ->colors([ 'success' => true, 'secondary' => false ]),
                Tables\Columns\BadgeColumn::make('origen')->colors(['primary']),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('activo')->options([
                    '1' => 'Activos',
                    '0' => 'Inactivos',
                ]),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('archivar')
                    ->label('Archivar')
                    ->icon('heroicon-o-archive')
                    ->color('warning')
                    ->visible(fn (Cliente $record): bool => $record->activo)
                    ->action(function (Cliente $record): void {
                        $record->update(['activo' => false]);
                        Notification::make()
                            ->title('Cliente archivado')
                            ->body('El cliente quedo inactivo. Sus proyectos siguen disponibles para historial/gestion.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reactivar')
                    ->label('Reactivar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Cliente $record): bool => ! $record->activo)
                    ->action(fn (Cliente $record) => $record->update(['activo' => true])),
                Tables\Actions\Action::make('eliminar')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => self::isAdmin())
                    ->requiresConfirmation()
                    ->action(function (Cliente $record): void {
                        $hasMovimientos = $record->proyectos()->whereHas('movimientosFinancieros')->exists();
                        if ($hasMovimientos) {
                            Notification::make()
                                ->title('No se puede eliminar')
                                ->body('Este cliente tiene movimientos financieros. Usar Archivar (inactivo).')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->delete();
                        Notification::make()
                            ->title('Cliente eliminado')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make()->visible(fn (): bool => self::isAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->visible(fn (): bool => self::isAdmin()),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make()->visible(fn (): bool => self::isAdmin()),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
