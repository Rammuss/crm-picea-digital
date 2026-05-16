<?php

namespace App\Filament\Widgets;

use App\Models\MovimientoFinanciero;
use App\Models\Proyecto;
use App\Models\Tarea;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class ResumenFinanciero extends BaseWidget
{
    protected function getCards(): array
    {
        $ingresos = (float) MovimientoFinanciero::query()->where('tipo', 'ingreso')->sum('monto');
        $costos = (float) MovimientoFinanciero::query()->where('tipo', 'costo')->sum('monto');
        $margen = $ingresos - $costos;

        $proyectosActivos = Proyecto::query()
            ->whereIn('estado', ['contactado', 'propuesta', 'anticipo', 'produccion'])
            ->count();

        $tareasHoy = Tarea::query()
            ->whereDate('fecha_compromiso', now()->toDateString())
            ->whereIn('estado', ['pendiente', 'en_progreso'])
            ->count();

        $tareasAtrasadas = Tarea::query()
            ->whereDate('fecha_compromiso', '<', now()->toDateString())
            ->whereIn('estado', ['pendiente', 'en_progreso'])
            ->count();

        $venceDominio30 = Proyecto::query()
            ->whereBetween('proximo_vencimiento_dominio', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->count();

        $venceHosting30 = Proyecto::query()
            ->whereBetween('proximo_vencimiento_hosting', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->count();

        $vencidosSinPago = Proyecto::query()
            ->where(function ($q): void {
                $q->whereDate('proximo_vencimiento_dominio', '<', now()->toDateString())
                    ->orWhereDate('proximo_vencimiento_hosting', '<', now()->toDateString());
            })
            ->get()
            ->filter(fn (Proyecto $proyecto): bool => $proyecto->saldo_pendiente > 0)
            ->count();

        $renovacionesMes = MovimientoFinanciero::query()
            ->where('tipo', 'ingreso')
            ->whereRaw('LOWER(concepto) LIKE ?', ['%renov%'])
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->sum('monto');

        return [
            Card::make('Ingresos', $this->money($ingresos))->description('Total registrado')->color('success'),
            Card::make('Costos', $this->money($costos))->description('Total registrado')->color('danger'),
            Card::make('Margen', $this->money($margen))->description('Ingresos - costos')->color($margen >= 0 ? 'primary' : 'danger'),
            Card::make('Proyectos activos', (string) $proyectosActivos)->description('En curso')->color('warning'),
            Card::make('Tareas de hoy', (string) $tareasHoy)->description('Pendiente/en progreso')->color('primary'),
            Card::make('Tareas atrasadas', (string) $tareasAtrasadas)->description('Requieren seguimiento')->color($tareasAtrasadas > 0 ? 'danger' : 'success'),
            Card::make('Dominios vencen 30d', (string) $venceDominio30)->description('Seguimiento renovacion')->color($venceDominio30 > 0 ? 'warning' : 'success'),
            Card::make('Hosting vence 30d', (string) $venceHosting30)->description('Seguimiento renovacion')->color($venceHosting30 > 0 ? 'warning' : 'success'),
            Card::make('Vencidos sin pago', (string) $vencidosSinPago)->description('Riesgo operativo')->color($vencidosSinPago > 0 ? 'danger' : 'success'),
            Card::make('Renovaciones del mes', $this->money((float) $renovacionesMes))->description('Ingresos por renovacion')->color('primary'),
        ];
    }

    private function money(float $value): string
    {
        return 'Gs. ' . number_format($value, 0, ',', '.');
    }
}
