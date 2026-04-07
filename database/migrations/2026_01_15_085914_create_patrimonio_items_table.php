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
        Schema::create('patrimonio_items', function (Blueprint $table) {
            $table->id();
            $table->string('area'); // Piso 1: Ventas | Piso 2: Contaduría, Gerencia, Diseño, Sistemas, Administración, Sala de Reuniones, Ensamblado
            $table->integer('piso'); // 1 o 2
            $table->string('codigo_patrimonial')->unique();
            $table->string('descripcion');
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('serie')->nullable();
            $table->string('estado'); // Operativo, Inoperativo, En reparación, etc.
            $table->decimal('valor_adquisicion', 10, 2)->nullable();
            $table->date('fecha_adquisicion')->nullable();
            $table->string('responsable')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrimonio_items');
    }
};
