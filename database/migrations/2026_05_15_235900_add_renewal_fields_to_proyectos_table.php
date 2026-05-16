<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->date('incluye_dominio_hasta')->nullable()->after('observaciones_postventa');
            $table->date('incluye_hosting_hasta')->nullable()->after('incluye_dominio_hasta');
            $table->boolean('auto_renovado')->default(false)->after('incluye_hosting_hasta');
            $table->date('fecha_renovacion_real')->nullable()->after('auto_renovado');
            $table->date('proximo_vencimiento_dominio')->nullable()->after('fecha_renovacion_real');
            $table->date('proximo_vencimiento_hosting')->nullable()->after('proximo_vencimiento_dominio');
            $table->string('estado_renovacion')->default('al_dia')->after('proximo_vencimiento_hosting');
        });
    }

    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn([
                'incluye_dominio_hasta',
                'incluye_hosting_hasta',
                'auto_renovado',
                'fecha_renovacion_real',
                'proximo_vencimiento_dominio',
                'proximo_vencimiento_hosting',
                'estado_renovacion',
            ]);
        });
    }
};

