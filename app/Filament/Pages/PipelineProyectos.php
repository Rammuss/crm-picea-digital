<?php

namespace App\Filament\Pages;

use App\Models\Proyecto;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class PipelineProyectos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-boards';
    protected static ?string $navigationLabel = 'Pipeline';
    protected static ?string $title = 'Pipeline de Proyectos';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.pipeline-proyectos';

    public bool $verCerrados = false;
    public string $buscar = '';

    /** @var array<string,string> */
    public array $estados = [
        'lead' => 'Lead',
        'contactado' => 'Contactado',
        'propuesta' => 'Propuesta',
        'anticipo' => 'Anticipo',
        'produccion' => 'Produccion',
        'entregado' => 'Entregado',
        'cerrado_ganado' => 'Cerrado ganado',
        'cerrado_perdido' => 'Cerrado perdido',
    ];

    public function getColumns(): array
    {
        $query = Proyecto::query()->with('cliente')->orderBy('updated_at', 'desc');

        if (! $this->verCerrados) {
            $query->whereNotIn('estado', ['cerrado_ganado', 'cerrado_perdido']);
        }

        if ($this->buscar !== '') {
            $term = '%' . $this->buscar . '%';
            $query->where(function ($q) use ($term): void {
                $q->where('nombre', 'like', $term)
                    ->orWhereHas('cliente', function ($cq) use ($term): void {
                        $cq->where('nombre', 'like', $term)
                            ->orWhere('codigo_cliente', 'like', $term);
                    });
            });
        }

        $records = $query->get();
        $columns = [];
        foreach ($this->estados as $estado => $label) {
            if (! $this->verCerrados && in_array($estado, ['cerrado_ganado', 'cerrado_perdido'], true)) {
                continue;
            }
            $columns[$estado] = [
                'label' => $label,
                'items' => $records->where('estado', $estado)->values(),
            ];
        }

        return $columns;
    }

    public function moverEstado(int $proyectoId, string $nuevoEstado): void
    {
        $proyecto = Proyecto::find($proyectoId);
        if (! $proyecto || ! isset($this->estados[$nuevoEstado])) {
            return;
        }

        try {
            $proyecto->estado = $nuevoEstado;
            $proyecto->save();
            Notification::make()->title('Estado actualizado')->success()->send();
        } catch (ValidationException $e) {
            Notification::make()->title('No se pudo mover')->body(collect($e->errors())->flatten()->first())->danger()->send();
        }
    }

    public function warningText(Proyecto $proyecto): ?string
    {
        if (! $proyecto->fecha_proximo_paso) {
            return 'Sin proximo paso';
        }
        if ($proyecto->fecha_proximo_paso->isPast()) {
            return 'Proximo paso atrasado';
        }
        if ($proyecto->estado === 'propuesta' && ! $proyecto->anticipo_pagado) {
            return 'Sin anticipo';
        }

        return null;
    }
}

