<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsVerifiedExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
     * Retorna TODOS los productos que tengan al menos una verificación
     * Excluye solo productos pendientes (sin ninguna verificación)
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Product::query()
            // Incluir productos que tengan al menos una verificación (V1, V2 o V3)
            ->whereRaw('(COALESCE(stock_verificado, 0) > 0 OR COALESCE(stock_verificado_2, 0) > 0 OR COALESCE(stock_verificado_3, 0) > 0)')
            ->orderBy('codigo')
            ->get();
    }

    /**
     * Encabezados del Excel
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'CODIGO',
            'PRODUCTO',
            'MARCA',
            'COSTO',
            'P. CLIENTE',
            'STOCK VERIFICADO'
        ];
    }

    /**
     * Mapea cada producto a una fila del Excel
     *
     * @param  \App\Models\Product  $product
     * @return array
     */
    public function map($product): array
    {
        // Calcular stock verificado basado en la lógica del sistema:
        // Si V1 ≠ V2, usar MAX(V1, V2) + V3
        // Si V1 = V2, usar V2 + V3
        $v1 = $product->stock_verificado ?? 0;
        $v2 = $product->stock_verificado_2 ?? 0;
        $v3 = $product->stock_verificado_3 ?? 0;

        if ($v1 !== $v2) {
            // Hay discrepancia: usar el mayor entre V1 y V2, luego sumar V3
            $stockVerificado = max($v1, $v2) + $v3;
        } else {
            // No hay discrepancia: V2 + V3
            $stockVerificado = $v2 + $v3;
        }

        return [
            $product->codigo,
            $product->producto,
            $product->marca ?? '',
            number_format($product->costo, 2, '.', ''),
            number_format($product->precio_cliente, 2, '.', ''),
            $stockVerificado
        ];
    }

    /**
     * Aplicar estilos al Excel
     *
     * @param  Worksheet  $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Encabezados en negrita y con fondo
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ]
            ],
        ];
    }
}
