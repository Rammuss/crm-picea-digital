<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class MovimientoFinanciero extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'proyecto_id',
        'tipo',
        'concepto',
        'numero_factura',
        'metodo_pago',
        'monto',
        'fecha',
        'notas',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    protected static function booted(): void
    {
        static::created(fn (self $mov) => self::syncProjectFinancialSummaries($mov));
        static::updated(fn (self $mov) => self::syncProjectFinancialSummaries($mov));
        static::deleted(fn (self $mov) => self::syncProjectFinancialSummaries($mov));
        static::restored(fn (self $mov) => self::syncProjectFinancialSummaries($mov));
        static::forceDeleted(fn (self $mov) => self::syncProjectFinancialSummaries($mov));
    }

    private static function syncProjectFinancialSummaries(self $mov): void
    {
        self::syncAnticipoResumen($mov->proyecto_id);
        self::applyRenewalIfNeeded($mov);
    }

    private static function syncAnticipoResumen(?int $proyectoId): void
    {
        if (! $proyectoId) {
            return;
        }

        $proyecto = Proyecto::find($proyectoId);
        if (! $proyecto) {
            return;
        }

        $anticipos = self::query()
            ->where('proyecto_id', $proyectoId)
            ->where('tipo', 'ingreso')
            ->whereRaw('LOWER(concepto) LIKE ?', ['%anticipo%'])
            ->orderBy('fecha')
            ->get(['monto', 'fecha']);

        $montoAnticipo = (float) $anticipos->sum('monto');
        $fechaAnticipo = $anticipos->first()?->fecha;

        $proyecto->anticipo_pagado = $montoAnticipo > 0;
        $proyecto->monto_anticipo = $montoAnticipo > 0 ? $montoAnticipo : null;
        $proyecto->fecha_anticipo = $fechaAnticipo ? Carbon::parse($fechaAnticipo) : null;
        $proyecto->saveQuietly();
    }

    private static function applyRenewalIfNeeded(self $mov): void
    {
        if ($mov->tipo !== 'ingreso') {
            return;
        }

        if (! $mov->proyecto_id || ! $mov->concepto) {
            return;
        }

        $concepto = mb_strtolower($mov->concepto);
        $renuevaDominio = str_contains($concepto, 'renov') && str_contains($concepto, 'dominio');
        $renuevaHosting = str_contains($concepto, 'renov') && str_contains($concepto, 'hosting');

        if (! $renuevaDominio && ! $renuevaHosting) {
            return;
        }

        $proyecto = Proyecto::find($mov->proyecto_id);
        if (! $proyecto) {
            return;
        }

        $baseDate = $mov->fecha ? Carbon::parse($mov->fecha) : now();
        $proyecto->fecha_renovacion_real = $baseDate->copy();

        if ($renuevaDominio) {
            $current = $proyecto->proximo_vencimiento_dominio;
            $start = $current && $current->isFuture() ? $current : $baseDate;
            $proyecto->proximo_vencimiento_dominio = $start->copy()->addYear();
            if (! $proyecto->incluye_dominio_hasta) {
                $proyecto->incluye_dominio_hasta = $proyecto->proximo_vencimiento_dominio;
            }
        }

        if ($renuevaHosting) {
            $current = $proyecto->proximo_vencimiento_hosting;
            $start = $current && $current->isFuture() ? $current : $baseDate;
            $proyecto->proximo_vencimiento_hosting = $start->copy()->addYear();
            if (! $proyecto->incluye_hosting_hasta) {
                $proyecto->incluye_hosting_hasta = $proyecto->proximo_vencimiento_hosting;
            }
        }

        $proyecto->auto_renovado = true;
        $proyecto->saveQuietly();
    }
}
