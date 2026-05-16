<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TareaResource\Pages;
use App\Models\Proyecto;
use App\Models\Tarea;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TareaResource extends Resource
{
    protected static ?string $model = Tarea::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationLabel = 'Tareas';

    private static function isAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('proyecto_id')
                ->relationship('proyecto', 'nombre')
                ->searchable()
                ->reactive()
                ->required(),
            Forms\Components\Placeholder::make('cliente_contexto')
                ->label('Cliente')
                ->content(function (callable $get): string {
                    $proyectoId = $get('proyecto_id');
                    if (! $proyectoId) {
                        return '-';
                    }

                    $proyecto = Proyecto::with('cliente')->find($proyectoId);
                    if (! $proyecto || ! $proyecto->cliente) {
                        return '-';
                    }

                    return (string) $proyecto->cliente->nombre;
                }),
            Forms\Components\Placeholder::make('telefono_contexto')
                ->label('Telefono cliente')
                ->content(function (callable $get): string {
                    $proyectoId = $get('proyecto_id');
                    if (! $proyectoId) {
                        return '-';
                    }

                    $proyecto = Proyecto::with('cliente')->find($proyectoId);
                    if (! $proyecto || ! $proyecto->cliente || ! $proyecto->cliente->telefono) {
                        return '-';
                    }

                    return (string) $proyecto->cliente->telefono;
                }),
            Forms\Components\TextInput::make('titulo')->required()->maxLength(255),
            Forms\Components\Textarea::make('descripcion')->rows(4),
            Forms\Components\TextInput::make('responsable')->maxLength(255),
            Forms\Components\DatePicker::make('fecha_compromiso'),
            Forms\Components\Select::make('estado')->options([
                'pendiente' => 'Pendiente',
                'en_progreso' => 'En progreso',
                'hecho' => 'Hecho',
            ])->default('pendiente')->required(),
            Forms\Components\Select::make('prioridad')->options([
                'alta' => 'Alta',
                'media' => 'Media',
                'baja' => 'Baja',
            ])->default('media')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_compromiso', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('titulo')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('proyecto.nombre')->label('Proyecto')->searchable(),
                Tables\Columns\TextColumn::make('proyecto.cliente.nombre')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('proyecto.cliente.telefono')->label('Telefono')->searchable(),
                Tables\Columns\TextColumn::make('responsable')->searchable(),
                Tables\Columns\BadgeColumn::make('estado')->colors([
                    'secondary' => 'pendiente',
                    'warning' => 'en_progreso',
                    'success' => 'hecho',
                ]),
                Tables\Columns\BadgeColumn::make('prioridad')->colors([
                    'danger' => 'alta',
                    'warning' => 'media',
                    'success' => 'baja',
                ]),
                Tables\Columns\TextColumn::make('fecha_compromiso')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('estado')->options([
                    'pendiente' => 'Pendiente',
                    'en_progreso' => 'En progreso',
                    'hecho' => 'Hecho',
                ]),
                Filter::make('hoy')
                    ->label('Compromiso hoy')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereDate('fecha_compromiso', now()->toDateString())),
                Filter::make('atrasadas')
                    ->label('Atrasadas')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('estado', ['pendiente', 'en_progreso'])
                        ->whereDate('fecha_compromiso', '<', now()->toDateString())),
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
            'index' => Pages\ListTareas::route('/'),
            'create' => Pages\CreateTarea::route('/create'),
            'edit' => Pages\EditTarea::route('/{record}/edit'),
        ];
    }
}
