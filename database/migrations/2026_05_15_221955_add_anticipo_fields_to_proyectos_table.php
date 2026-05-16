<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->boolean('anticipo_pagado')->default(false)->after('estado');
            $table->date('fecha_anticipo')->nullable()->after('anticipo_pagado');
            $table->decimal('monto_anticipo', 12, 2)->nullable()->after('fecha_anticipo');
        });
    }

    public function down()
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn(['anticipo_pagado', 'fecha_anticipo', 'monto_anticipo']);
        });
    }
};
