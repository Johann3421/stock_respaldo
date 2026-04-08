<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Exception;

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

        $sql = file_get_contents($sqlFile);

        // For PostgreSQL bulk import: temporarily disable FK checks by setting
        // session_replication_role to 'replica'. This allows inserting rows
        // without foreign-key order constraints during bulk load.
        $driver = null;
        try {
            try {
                $pdo = DB::getPdo();
                $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            } catch (Exception $e) {
                // ignore, we'll attempt import anyway
            }

            if ($driver === 'pgsql') {
                DB::statement("SET session_replication_role = 'replica';");
            }

            DB::unprepared($sql);

        } finally {
            try {
                if ($driver === 'pgsql') {
                    DB::statement("SET session_replication_role = 'origin';");
                }
            } catch (Exception $e) {
                // log but don't fail the seeder cleanup
                $this->command->error('Warning: failed to reset session_replication_role: ' . $e->getMessage());
            }
        }

        $this->command->info('Production data imported successfully.');
    }
}
