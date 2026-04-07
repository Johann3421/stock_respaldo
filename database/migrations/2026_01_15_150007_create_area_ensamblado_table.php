<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_ensamblado', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_patrimonial')->unique();
            $table->string('descripcion');
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('serie')->nullable();
            $table->enum('estado', ['Operativo', 'Inoperativo', 'En reparación', 'De baja'])->default('Operativo');
            $table->decimal('valor_adquisicion', 10, 2)->nullable();
            $table->date('fecha_adquisicion')->nullable();
            $table->string('responsable')->nullable();
            $table->text('observaciones')->nullable();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->timestamps();

            $table->index('codigo_patrimonial');
            $table->index('estado');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_ensamblado');
    }
};
