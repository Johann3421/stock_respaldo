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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'stock_verificado_2')) {
                $table->integer('stock_verificado_2')->nullable()->after('ultima_verificacion');
            }
            if (!Schema::hasColumn('products', 'verificado_por_2')) {
                $table->string('verificado_por_2')->nullable()->after('stock_verificado_2');
            }
            if (!Schema::hasColumn('products', 'ultima_verificacion_2')) {
                $table->timestamp('ultima_verificacion_2')->nullable()->after('verificado_por_2');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_verificado_2', 'verificado_por_2', 'ultima_verificacion_2']);
        });
    }
};
