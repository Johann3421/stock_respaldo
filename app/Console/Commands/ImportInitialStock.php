<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportInitialStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:import {file?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar stock inicial desde archivo Excel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file') ?? public_path('REPORTE_DE_STOCK_INICIAL (2).xls.xlsx');

        if (!file_exists($file)) {
            $this->error('El archivo no existe: ' . $file);
            return 1;
        }

        $this->info('Importando productos desde: ' . $file);

        try {
            Excel::import(new ProductsImport, $file);
            $this->info('✓ Productos importados exitosamente');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error al importar: ' . $e->getMessage());
            return 1;
        }
    }
}
