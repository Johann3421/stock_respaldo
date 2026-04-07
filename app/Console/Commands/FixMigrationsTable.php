<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMigrationsTable extends Command
{
    protected $signature = 'fix:migrations-table';
    protected $description = 'Fix migrations table structure for strict mode';

    public function handle()
    {
        try {
            $driver = DB::getDriverName();

            DB::statement('DROP TABLE IF EXISTS migrations');

            if ($driver === 'pgsql') {
                DB::statement("
                    CREATE TABLE migrations (
                        id SERIAL PRIMARY KEY,
                        migration VARCHAR(255) NOT NULL,
                        batch INTEGER NOT NULL
                    )
                ");
            } else {
                DB::statement("
                    CREATE TABLE migrations (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        migration VARCHAR(255) NOT NULL,
                        batch INT NOT NULL
                    )
                ");
            }

            $this->info('Migrations table fixed successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
