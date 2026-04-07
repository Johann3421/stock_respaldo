<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'area_ventas',
            'area_contaduria',
            'area_gerencia',
            'area_diseno',
            'area_sistemas',
            'area_administracion',
            'area_sala_reuniones',
            'area_ensamblado'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->timestamp('closed_at')->nullable()->comment('Fecha y hora de cierre del inventario');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'area_ventas',
            'area_contaduria',
            'area_gerencia',
            'area_diseno',
            'area_sistemas',
            'area_administracion',
            'area_sala_reuniones',
            'area_ensamblado'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('closed_at');
            });
        }
    }
};
