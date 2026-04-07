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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('producto');
            $table->string('marca')->nullable();
            $table->decimal('costo', 10, 2)->default(0);
            $table->decimal('precio_cliente', 10, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('stock_verificado')->nullable();
            $table->string('verificado_por')->nullable();
            $table->timestamp('ultima_verificacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
