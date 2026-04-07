<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la tabla existe
        if (Schema::hasTable('users') && DB::getDriverName() === 'mysql') {
            Schema::table('users', function (Blueprint $table) {
                DB::statement('ALTER TABLE users MODIFY id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
