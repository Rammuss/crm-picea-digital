<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Proyecto extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'cliente_id',
        'nombre',
        'servicio',
        'responsable_comercial',
        'responsable_tecnico',
        'estado',
        'anticipo_pagado',
        'fecha_anticipo',
        'monto_anticipo',
        'precio_venta',
        'fecha_inicio',
        'fecha_entrega_estimada',
        'proximo_paso',
        'fecha_proximo_paso',
        'notas',
        'gestiona_dominio',
        'dominio_principal',
        'proveedor_dominio',
        'usuario_acceso_dominio',
        'fecha_vencimiento_dominio',
        'dns_proveedor',
        'estado_dns',
        'hosting_proveedor',
        'estado_hosting',
        'fecha_activacion_hosting',
        'fecha_vencimiento_hosting',
        'repo_url',
        'rama_principal',
        'metodo_deploy',
        'url_produccion',
        'fecha_publicacion',
        'fecha_entrega_cliente',
        'requiere_mantenimiento',
        'proxima_revision_post_entrega',
        'observaciones_postventa',
        'incluye_dominio_hasta',
        'incluye_hosting_hasta',
        'auto_renovado',
        'fecha_renovacion_real',
        'proximo_vencimiento_dominio',
        'proximo_vencimiento_hosting',
        'estado_renovacion',
    ];

    protected $casts = [
        'anticipo_pagado' => 'boolean',
        'monto_anticipo' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'fecha_anticipo' => 'date',
        'fecha_inicio' => 'date',
        'fecha_entrega_estimada' => 'date',
        'fecha_proximo_paso' => 'date',
        'fecha_vencimiento_dominio' => 'date',
        'fecha_activacion_hosting' => 'date',
        'fecha_vencimiento_hosting' => 'date',
        'fecha_publicacion' => 'date',
        'fecha_entrega_cliente' => 'date',
        'requiere_mantenimiento' => 'boolean',
        'proxima_revision_post_entrega' => 'date',
        'incluye_dominio_hasta' => 'date',
        'incluye_hosting_hasta' => 'date',
        'auto_renovado' => 'boolean',
        'fecha_renovacion_real' => 'date',
        'proximo_vencimiento_dominio' => 'date',
        'proximo_vencimiento_hosting' => 'date',
    ];

    protected $appends = [
        'total_ingresos',
        'saldo_pendiente',
        'pago_final_pagado',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $proyecto): void {
            if ($proyecto->estado === 'cerrado_ganado' && $proyecto->saldo_pendiente > 0) {
                throw ValidationException::withMessages([
                    'estado' => 'No se puede marcar como cerrado_ganado con saldo pendiente.',
                ]);
            }

            $hoy = now()->startOfDay();
            $vencePronto = $hoy->copy()->addDays(30);
            $dominio = $proyecto->proximo_vencimiento_dominio;
            $hosting = $proyecto->proximo_vencimiento_hosting;

            $fechas = collect([$dominio, $hosting])->filter();
            if ($fechas->isEmpty()) {
                $proyecto->estado_renovacion = 'al_dia';
            } else {
                $minFecha = $fechas->sort()->first();
                if ($minFecha < $hoy) {
                    $proyecto->estado_renovacion = 'vencido';
                } elseif ($minFecha <= $vencePronto) {
                    $proyecto->estado_renovacion = 'vence_pronto';
                } else {
                    $proyecto->estado_renovacion = 'al_dia';
                }
            }
        });

        static::deleting(function (self $proyecto): void {
            if ($proyecto->isForceDeleting()) {
                $proyecto->tareas()->withTrashed()->get()->each->forceDelete();
                $proyecto->movimientosFinancieros()->withTrashed()->get()->each->forceDelete();
                return;
            }

            $proyecto->tareas()->get()->each->delete();
            $proyecto->movimientosFinancieros()->get()->each->delete();
        });

        static::restoring(function (self $proyecto): void {
            $proyecto->tareas()->onlyTrashed()->get()->each->restore();
            $proyecto->movimientosFinancieros()->onlyTrashed()->get()->each->restore();
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class);
    }

    public function movimientosFinancieros(): HasMany
    {
        return $this->hasMany(MovimientoFinanciero::class);
    }

    public function getTotalIngresosAttribute(): float
    {
        return (float) $this->movimientosFinancieros()
            ->where('tipo', 'ingreso')
            ->sum('monto');
    }

    public function getSaldoPendienteAttribute(): float
    {
        $precio = (float) ($this->precio_venta ?? 0);
        return max(0, $precio - $this->total_ingresos);
    }

    public function getPagoFinalPagadoAttribute(): bool
    {
        return $this->saldo_pendiente <= 0.0;
    }
}
