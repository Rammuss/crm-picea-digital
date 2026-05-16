<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('proyectos', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('tareas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('movimiento_financieros', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tareas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('movimiento_financieros', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
