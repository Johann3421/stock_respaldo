<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class InspectExcel extends Command
{
    protected $signature = 'inspect:excel {file}';
    protected $description = 'Inspect Excel file structure';

    public function handle()
    {
        $file = $this->argument('file');

        try {
            $data = Excel::toArray(null, $file);

            if (!empty($data[0])) {
                $this->info("First 5 rows from: $file\n");
                $rows = array_slice($data[0], 0, 5);
                foreach ($rows as $i => $row) {
                    $this->line("Row " . ($i + 1) . ":");
                    foreach ($row as $j => $val) {
                        $this->line("  Col $j: " . var_export($val, true));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
