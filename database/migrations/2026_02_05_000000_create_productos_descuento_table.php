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
        Schema::create('productos_descuento', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('producto');
            $table->string('marca')->nullable();
            $table->decimal('costo', 10, 2)->default(0);
            $table->decimal('precio_cliente', 10, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->decimal('descuento_percent', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos_descuento');
    }
};
