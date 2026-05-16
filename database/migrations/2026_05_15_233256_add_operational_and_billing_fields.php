<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->string('responsable_comercial')->nullable()->after('servicio');
            $table->string('responsable_tecnico')->nullable()->after('responsable_comercial');

            $table->string('gestiona_dominio')->nullable()->after('monto_anticipo');
            $table->string('dominio_principal')->nullable()->after('gestiona_dominio');
            $table->string('proveedor_dominio')->nullable()->after('dominio_principal');
            $table->string('usuario_acceso_dominio')->nullable()->after('proveedor_dominio');
            $table->date('fecha_vencimiento_dominio')->nullable()->after('usuario_acceso_dominio');

            $table->string('dns_proveedor')->nullable()->after('fecha_vencimiento_dominio');
            $table->string('estado_dns')->nullable()->after('dns_proveedor');

            $table->string('hosting_proveedor')->nullable()->after('estado_dns');
            $table->string('estado_hosting')->nullable()->after('hosting_proveedor');
            $table->date('fecha_activacion_hosting')->nullable()->after('estado_hosting');
            $table->date('fecha_vencimiento_hosting')->nullable()->after('fecha_activacion_hosting');

            $table->string('repo_url')->nullable()->after('fecha_vencimiento_hosting');
            $table->string('rama_principal')->nullable()->after('repo_url');
            $table->string('metodo_deploy')->nullable()->after('rama_principal');
            $table->string('url_produccion')->nullable()->after('metodo_deploy');
            $table->date('fecha_publicacion')->nullable()->after('url_produccion');
            $table->date('fecha_entrega_cliente')->nullable()->after('fecha_publicacion');

            $table->boolean('requiere_mantenimiento')->default(false)->after('fecha_entrega_cliente');
            $table->date('proxima_revision_post_entrega')->nullable()->after('requiere_mantenimiento');
            $table->text('observaciones_postventa')->nullable()->after('proxima_revision_post_entrega');
        });

        Schema::table('movimiento_financieros', function (Blueprint $table) {
            $table->string('numero_factura')->nullable()->after('concepto');
            $table->string('metodo_pago')->nullable()->after('numero_factura');
        });
    }

    public function down()
    {
        Schema::table('movimiento_financieros', function (Blueprint $table) {
            $table->dropColumn(['numero_factura', 'metodo_pago']);
        });

        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn([
                'responsable_comercial',
                'responsable_tecnico',
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
            ]);
        });
    }
};
