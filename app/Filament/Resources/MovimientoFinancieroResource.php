<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovimientoFinancieroResource\Pages;
use App\Models\MovimientoFinanciero;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MovimientoFinancieroResource extends Resource
{
    protected static ?string $model = MovimientoFinanciero::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Movimientos';

    private static function isAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('proyecto_id')
                ->relationship('proyecto', 'nombre')
                ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->cliente->codigo_cliente ?? '-') . ' | ' . ($record->cliente->nombre ?? '-') . ' | ' . $record->nombre))
                ->searchable(['nombre'])
                ->required(),
            Forms\Components\Select::make('tipo')->options([
                'ingreso' => 'Ingreso',
                'costo' => 'Costo',
            ])->required(),
            Forms\Components\TextInput::make('concepto')->required()->maxLength(255),
            Forms\Components\TextInput::make('numero_factura')->maxLength(255),
            Forms\Components\Select::make('metodo_pago')->options([
                'efectivo' => 'Efectivo',
                'transferencia' => 'Transferencia',
                'tarjeta' => 'Tarjeta',
                'billetera' => 'Billetera',
                'otro' => 'Otro',
            ]),
            Forms\Components\TextInput::make('monto')->numeric()->required(),
            Forms\Components\DatePicker::make('fecha')->required(),
            Forms\Components\Textarea::make('notas')->rows(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proyecto.cliente.codigo_cliente')->label('Codigo cliente')->searchable(),
                Tables\Columns\TextColumn::make('proyecto.cliente.nombre')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('proyecto.nombre')->label('Proyecto')->searchable(),
                Tables\Columns\BadgeColumn::make('tipo')->colors([
                    'success' => 'ingreso',
                    'danger' => 'costo',
                ]),
                Tables\Columns\TextColumn::make('concepto')->searchable(),
                Tables\Columns\TextColumn::make('numero_factura')->label('Factura')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('metodo_pago')->label('Metodo')->toggleable(),
                Tables\Columns\TextColumn::make('monto')->money('PYG')->sortable(),
                Tables\Columns\TextColumn::make('fecha')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('tipo')->options([
                    'ingreso' => 'Ingreso',
                    'costo' => 'Costo',
                ]),
                Tables\Filters\SelectFilter::make('metodo_pago')->options([
                    'efectivo' => 'Efectivo',
                    'transferencia' => 'Transferencia',
                    'tarjeta' => 'Tarjeta',
                    'billetera' => 'Billetera',
                    'otro' => 'Otro',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->visible(fn (): bool => self::isAdmin()),
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
            'index' => Pages\ListMovimientoFinancieros::route('/'),
            'create' => Pages\CreateMovimientoFinanciero::route('/create'),
            'edit' => Pages\EditMovimientoFinanciero::route('/{record}/edit'),
        ];
    }
}
