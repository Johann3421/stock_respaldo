<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Product::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'CODIGO',
            'PRODUCTO',
            'MARCA',
            'COSTO',
            'P. CLIENTE',
            'STOCK',
            'STOCK VERIFICADO 1',
            'VERIFICADO POR 1',
            'ULTIMA VERIFICACION 1',
            'STOCK VERIFICADO 2',
            'VERIFICADO POR 2',
            'ULTIMA VERIFICACION 2',
            'STOCK VERIFICADO TIENDA',
            'VERIFICADO POR TIENDA',
            'ULTIMA VERIFICACION TIENDA'
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->codigo,
            $product->producto,
            $product->marca,
            $product->costo,
            $product->precio_cliente,
            $product->stock,
            $product->stock_verificado,
            $product->verificado_por,
            $product->ultima_verificacion ? $product->ultima_verificacion->format('d/m/Y H:i') : '',
            $product->stock_verificado_2,
            $product->verificado_por_2,
            $product->ultima_verificacion_2 ? $product->ultima_verificacion_2->format('d/m/Y H:i') : '',
            $product->stock_verificado_3,
            $product->verificado_por_3,
            $product->ultima_verificacion_3 ? $product->ultima_verificacion_3->format('d/m/Y H:i') : '',
        ];
    }
}
