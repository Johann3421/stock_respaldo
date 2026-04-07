<?php

namespace App\Console\Commands;

use App\Models\ProductoDescuento;
use Illuminate\Console\Command;

class CheckProductos extends Command
{
    protected $signature = 'check:productos';
    protected $description = 'Check first 5 productos to see stock values';

    public function handle()
    {
        $productos = ProductoDescuento::limit(5)->get();
        foreach ($productos as $p) {
            $this->line("Código: {$p->codigo}, Producto: {$p->producto}, Stock: {$p->stock}");
        }

        // Also check if there are any products with stock > 0
        $count = ProductoDescuento::where('stock', '>', 0)->count();
        $this->line("\nTotal products with stock > 0: $count");
    }
}
