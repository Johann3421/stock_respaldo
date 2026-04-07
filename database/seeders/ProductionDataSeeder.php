<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionDataSeeder extends Seeder
{
    public function run(): void
    {
        $sqlFile = database_path('sql/seed_data.sql');

        if (!file_exists($sqlFile)) {
            $this->command->error('Seed SQL file not found: ' . $sqlFile);
            return;
        }

        $this->command->info('Importing production data from SQL file...');
        DB::unprepared(file_get_contents($sqlFile));
        $this->command->info('Production data imported successfully.');
    }
}
