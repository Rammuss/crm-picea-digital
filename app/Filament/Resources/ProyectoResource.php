<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProyectoResource\Pages;
use App\Models\Proyecto;
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

class ProyectoResource extends Resource
{
    protected static ?string $model = Proyecto::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Proyectos';

    private static function isAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Comercial')
                ->schema([
                    Forms\Components\Select::make('cliente_id')
                        ->relationship('cliente', 'nombre')
                        ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->codigo_cliente ?? '-') . ' | ' . $record->nombre . ' | ' . ($record->telefono ?? '-')))
                        ->searchable(['nombre', 'telefono', 'email', 'codigo_cliente'])
                        ->required(),
                    Forms\Components\TextInput::make('nombre')->required()->maxLength(255),
                    Forms\Components\TextInput::make('servicio')->maxLength(255),
                    Forms\Components\TextInput::make('responsable_comercial')->maxLength(255),
                    Forms\Components\TextInput::make('responsable_tecnico')->maxLength(255),
                    Forms\Components\Select::make('estado')->options([
                        'lead' => 'Lead',
                        'contactado' => 'Contactado',
                        'propuesta' => 'Propuesta',
                        'anticipo' => 'Anticipo',
                        'produccion' => 'Produccion',
                        'entregado' => 'Entregado',
                        'cerrado_ganado' => 'Cerrado ganado',
                        'cerrado_perdido' => 'Cerrado perdido',
                    ])->default('lead')->required(),
                    Forms\Components\TextInput::make('precio_venta')->numeric()->label('Precio de venta (Gs.)'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Pagos')
                ->schema([
                    Forms\Components\Toggle::make('anticipo_pagado')
                        ->label('Anticipo pagado')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\DatePicker::make('fecha_anticipo')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('monto_anticipo')->numeric()->disabled()->dehydrated(false)->label('Monto anticipo (Gs.)'),
                    Forms\Components\Placeholder::make('total_ingresos_info')
                        ->label('Total ingresos cargados')
                        ->content(fn (?Proyecto $record): string => $record ? number_format((float) $record->total_ingresos, 0, ',', '.') . ' Gs.' : '0 Gs.'),
                    Forms\Components\Placeholder::make('saldo_pendiente_info')
                        ->label('Saldo pendiente')
                        ->content(fn (?Proyecto $record): string => $record ? number_format((float) $record->saldo_pendiente, 0, ',', '.') . ' Gs.' : '0 Gs.'),
                ])
                ->columns(3),

            Forms\Components\Section::make('Plan')
                ->schema([
                    Forms\Components\DatePicker::make('fecha_inicio'),
                    Forms\Components\DatePicker::make('fecha_entrega_estimada'),
                    Forms\Components\TextInput::make('proximo_paso')->maxLength(255),
                    Forms\Components\DatePicker::make('fecha_proximo_paso'),
                    Forms\Components\DatePicker::make('fecha_publicacion'),
                    Forms\Components\DatePicker::make('fecha_entrega_cliente'),
                ])
                ->columns(3),

            Forms\Components\Section::make('Dominio y DNS')
                ->schema([
                    Forms\Components\Select::make('gestiona_dominio')->options([
                        'nosotros' => 'Nosotros',
                        'cliente' => 'Cliente',
                    ]),
                    Forms\Components\TextInput::make('dominio_principal')->maxLength(255),
                    Forms\Components\TextInput::make('proveedor_dominio')->maxLength(255),
                    Forms\Components\TextInput::make('usuario_acceso_dominio')->maxLength(255),
                    Forms\Components\DatePicker::make('fecha_vencimiento_dominio'),
                    Forms\Components\TextInput::make('dns_proveedor')->maxLength(255),
                    Forms\Components\Select::make('estado_dns')->options([
                        'pendiente' => 'Pendiente',
                        'configurado' => 'Configurado',
                        'verificado' => 'Verificado',
                    ]),
                ])
                ->columns(3),

            Forms\Components\Section::make('Hosting y Deploy')
                ->schema([
                    Forms\Components\TextInput::make('hosting_proveedor')->maxLength(255),
                    Forms\Components\Select::make('estado_hosting')->options([
                        'pendiente' => 'Pendiente',
                        'activo' => 'Activo',
                        'vencido' => 'Vencido',
                    ]),
                    Forms\Components\DatePicker::make('fecha_activacion_hosting'),
                    Forms\Components\DatePicker::make('fecha_vencimiento_hosting'),
                    Forms\Components\TextInput::make('repo_url')->url()->maxLength(255)->label('Repo URL'),
                    Forms\Components\TextInput::make('rama_principal')->maxLength(255),
                    Forms\Components\TextInput::make('metodo_deploy')->maxLength(255),
                    Forms\Components\TextInput::make('url_produccion')->url()->maxLength(255),
                ])
                ->columns(3),

            Forms\Components\Section::make('Postventa')
                ->schema([
                    Forms\Components\Toggle::make('requiere_mantenimiento'),
                    Forms\Components\DatePicker::make('proxima_revision_post_entrega'),
                    Forms\Components\Textarea::make('observaciones_postventa')->rows(3),
                    Forms\Components\Textarea::make('notas')->rows(4),
                ])
                ->columns(2),

            Forms\Components\Section::make('Renovaciones')
                ->schema([
                    Forms\Components\DatePicker::make('incluye_dominio_hasta'),
                    Forms\Components\DatePicker::make('incluye_hosting_hasta'),
                    Forms\Components\DatePicker::make('proximo_vencimiento_dominio'),
                    Forms\Components\DatePicker::make('proximo_vencimiento_hosting'),
                    Forms\Components\Toggle::make('auto_renovado'),
                    Forms\Components\DatePicker::make('fecha_renovacion_real'),
                    Forms\Components\Select::make('estado_renovacion')
                        ->options([
                            'al_dia' => 'Al dia',
                            'vence_pronto' => 'Vence pronto',
                            'vencido' => 'Vencido',
                        ])
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cliente.codigo_cliente')->label('Codigo cliente')->searchable(),
                Tables\Columns\TextColumn::make('cliente.nombre')->label('Cliente')->searchable(),
                Tables\Columns\BadgeColumn::make('estado')->colors([
                    'secondary' => 'lead',
                    'primary' => 'contactado',
                    'warning' => ['propuesta', 'anticipo'],
                    'success' => ['produccion', 'entregado', 'cerrado_ganado'],
                    'danger' => 'cerrado_perdido',
                ]),
                Tables\Columns\IconColumn::make('anticipo_pagado')->label('Anticipo')->boolean(),
                Tables\Columns\TextColumn::make('monto_anticipo')->money('PYG')->toggleable(),
                Tables\Columns\TextColumn::make('total_ingresos')->label('Ingresos')->money('PYG')->toggleable(),
                Tables\Columns\TextColumn::make('saldo_pendiente')->label('Saldo')->money('PYG')->toggleable(),
                Tables\Columns\TextColumn::make('proximo_paso')->limit(30)->toggleable(),
                Tables\Columns\TextColumn::make('fecha_proximo_paso')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('precio_venta')->money('PYG')->sortable(),
                Tables\Columns\TextColumn::make('proximo_vencimiento_dominio')->label('Vence dominio')->date('d/m/Y')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('proximo_vencimiento_hosting')->label('Vence hosting')->date('d/m/Y')->sortable()->toggleable(),
                Tables\Columns\BadgeColumn::make('estado_renovacion')->label('Renovacion')->colors([
                    'success' => 'al_dia',
                    'warning' => 'vence_pronto',
                    'danger' => 'vencido',
                ]),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('estado')->options([
                    'lead' => 'Lead',
                    'contactado' => 'Contactado',
                    'propuesta' => 'Propuesta',
                    'anticipo' => 'Anticipo',
                    'produccion' => 'Produccion',
                    'entregado' => 'Entregado',
                    'cerrado_ganado' => 'Cerrado ganado',
                    'cerrado_perdido' => 'Cerrado perdido',
                ]),
                Filter::make('con_paso_pendiente_hoy')
                    ->label('Paso de hoy o atrasado')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('fecha_proximo_paso')
                        ->whereDate('fecha_proximo_paso', '<=', now()->toDateString())),
                Filter::make('vence_30_dias')
                    ->label('Vence en 30 dias')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $q): void {
                        $q->whereBetween('proximo_vencimiento_dominio', [now()->toDateString(), now()->addDays(30)->toDateString()])
                            ->orWhereBetween('proximo_vencimiento_hosting', [now()->toDateString(), now()->addDays(30)->toDateString()]);
                    })),
                Filter::make('vence_60_dias')
                    ->label('Vence en 60 dias')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $q): void {
                        $q->whereBetween('proximo_vencimiento_dominio', [now()->toDateString(), now()->addDays(60)->toDateString()])
                            ->orWhereBetween('proximo_vencimiento_hosting', [now()->toDateString(), now()->addDays(60)->toDateString()]);
                    })),
                Filter::make('vencidos')
                    ->label('Vencidos')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $q): void {
                        $q->whereDate('proximo_vencimiento_dominio', '<', now()->toDateString())
                            ->orWhereDate('proximo_vencimiento_hosting', '<', now()->toDateString());
                    })),
                Filter::make('renovados_mes')
                    ->label('Renovados este mes')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('fecha_renovacion_real')
                        ->whereMonth('fecha_renovacion_real', now()->month)
                        ->whereYear('fecha_renovacion_real', now()->year)),
                Filter::make('renovados_2do_anio')
                    ->label('Renovados 2do anio+')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('auto_renovado', true)
                        ->where(function (Builder $q): void {
                            $q->whereColumn('proximo_vencimiento_dominio', '>', 'incluye_dominio_hasta')
                                ->orWhereColumn('proximo_vencimiento_hosting', '>', 'incluye_hosting_hasta');
                        })),
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
            'index' => Pages\ListProyectos::route('/'),
            'create' => Pages\CreateProyecto::route('/create'),
            'edit' => Pages\EditProyecto::route('/{record}/edit'),
        ];
    }
}
