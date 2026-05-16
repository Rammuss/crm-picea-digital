<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tarea extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'proyecto_id',
        'titulo',
        'descripcion',
        'responsable',
        'fecha_compromiso',
        'estado',
        'prioridad',
    ];

    protected $casts = [
        'fecha_compromiso' => 'date',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
