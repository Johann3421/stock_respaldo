<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old monolithic table after data migration
        Schema::dropIfExists('patrimonio_items');
    }

    public function down(): void
    {
        // This is a destructive migration, no rollback available
        // The old table structure cannot be reliably reconstructed
    }
};
