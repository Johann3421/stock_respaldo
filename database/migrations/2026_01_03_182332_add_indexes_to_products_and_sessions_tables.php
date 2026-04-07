<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear índices solo si no existen
        try {
            Schema::table('products', function (Blueprint $table) {
                // Usar DB raw para verificar y crear índices
                DB::statement('CREATE INDEX IF NOT EXISTS idx_products_codigo ON products(codigo)');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_products_producto ON products(producto(100))');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_products_marca ON products(marca(100))');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_products_stock_verificado ON products(stock_verificado)');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_products_stock_verificado_2 ON products(stock_verificado_2)');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_products_stock_verificado_3 ON products(stock_verificado_3)');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_products_created_at ON products(created_at)');
                DB::statement('CREATE INDEX IF NOT EXISTS idx_products_updated_at ON products(updated_at)');
            });
        } catch (\Exception $e) {
            // Ignorar si los índices ya existen
            Log::info('Algunos índices ya existen en products: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('DROP INDEX IF EXISTS idx_products_codigo ON products');
            DB::statement('DROP INDEX IF EXISTS idx_products_producto ON products');
            DB::statement('DROP INDEX IF EXISTS idx_products_marca ON products');
            DB::statement('DROP INDEX IF EXISTS idx_products_stock_verificado ON products');
            DB::statement('DROP INDEX IF EXISTS idx_products_stock_verificado_2 ON products');
            DB::statement('DROP INDEX IF EXISTS idx_products_stock_verificado_3 ON products');
            DB::statement('DROP INDEX IF EXISTS idx_products_created_at ON products');
            DB::statement('DROP INDEX IF EXISTS idx_products_updated_at ON products');
        } catch (\Exception $e) {
            Log::info('Error al eliminar índices: ' . $e->getMessage());
        }
    }
};
