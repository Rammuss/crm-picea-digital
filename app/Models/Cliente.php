<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Cliente extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'codigo_cliente',
        'activo',
        'nombre',
        'telefono',
        'email',
        'origen',
        'notas',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $cliente): void {
            if (! $cliente->codigo_cliente) {
                $cliente->codigo_cliente = self::generarCodigo();
            }
        });

        static::deleting(function (self $cliente): void {
            if ($cliente->isForceDeleting()) {
                $cliente->proyectos()->withTrashed()->get()->each->forceDelete();
                return;
            }

            $hasMovimientos = $cliente->proyectos()->whereHas('movimientosFinancieros')->exists();

            if ($hasMovimientos) {
                throw ValidationException::withMessages([
                    'delete' => 'Este cliente tiene movimientos financieros. En lugar de eliminarlo, archivelo (inactivo).',
                ]);
            }

            $cliente->proyectos()->get()->each->delete();
        });

        static::restoring(function (self $cliente): void {
            $cliente->proyectos()->onlyTrashed()->get()->each->restore();
        });
    }

    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class);
    }

    public static function generarCodigo(): string
    {
        $prefijo = 'CLI-' . now()->format('Y');
        $codigos = self::withTrashed()
            ->where('codigo_cliente', 'like', $prefijo . '-%')
            ->pluck('codigo_cliente');

        $max = 0;
        foreach ($codigos as $codigo) {
            if ($codigo && preg_match('/^CLI-\d{4}-(\d{4})$/', $codigo, $m)) {
                $num = (int) $m[1];
                if ($num > $max) {
                    $max = $num;
                }
            }
        }

        $n = $max + 1;
        do {
            $candidate = sprintf('%s-%04d', $prefijo, $n);
            $exists = self::withTrashed()->where('codigo_cliente', $candidate)->exists();
            $n++;
        } while ($exists);

        return $candidate;
    }
}
