<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->string('proximo_paso')->nullable()->after('notas');
            $table->date('fecha_proximo_paso')->nullable()->after('proximo_paso');
        });
    }

    public function down()
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn(['proximo_paso', 'fecha_proximo_paso']);
        });
    }
};
