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
                $table->unsignedBigInteger('closed_by_user_id')->nullable()->after('closed_at')->comment('Usuario que cerró el inventario');
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
                $table->dropColumn('closed_by_user_id');
            });
        }
    }
};
