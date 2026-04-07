<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

class FixEncodingCommand extends Command
{
    protected $signature = 'products:fix-encoding';
    protected $description = 'Corrige el encoding de caracteres especiales en productos';

    public function handle()
    {
        $this->info('Corrigiendo encoding de productos...');

        // Procesar en chunks para evitar problemas de memoria
        $totalProducts = Product::count();
        $fixed = 0;
        $processed = 0;

        $bar = $this->output->createProgressBar($totalProducts);
        $bar->start();

        Product::chunk(100, function ($products) use (&$fixed, &$processed, $bar) {
            foreach ($products as $product) {
                $updated = false;

                // Corregir producto
                if ($product->producto) {
                    $fixedProducto = $this->fixEncoding($product->producto);
                    if ($fixedProducto !== $product->producto) {
                        $this->line("\n✓ Producto #{$product->id}: '{$product->producto}' -> '{$fixedProducto}'");
                        $product->producto = $fixedProducto;
                        $updated = true;
                    }
                }

                // Corregir marca
                if ($product->marca) {
                    $fixedMarca = $this->fixEncoding($product->marca);
                    if ($fixedMarca !== $product->marca) {
                        $this->line("\n✓ Marca #{$product->id}: '{$product->marca}' -> '{$fixedMarca}'");
                        $product->marca = $fixedMarca;
                        $updated = true;
                    }
                }

                if ($updated) {
                    $product->save();
                    $fixed++;
                }

                $processed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("✓ Procesados: {$processed} productos");
        $this->info("✓ Corregidos: {$fixed} productos");

        return 0;
    }

    private function fixEncoding($text)
    {
        try {
            if (!$text) {
                return $text;
            }

            $text = (string)$text;
            $original = $text;

            // PASO 1: Reemplazos directos de patrones específicos conocidos (usando bytes exactos)
            $directReplacements = [
                // Bytes C3 8D C2 81 (CÍMARA mal codificado)
                "\xC3\x8D\xC2\x81" => 'Á',
                'CÍMARA' => 'CÁMARA',
                'Címara' => 'Cámara',
                'címara' => 'cámara',

                // Doble encoding de Á É Í Ó Ú
                'TRÃÂPODE' => 'TRÍPODE',
                'Trãâpode' => 'Trípode',
                'trãâpode' => 'trípode',
                'CÃÂMARA' => 'CÁMARA',
                'Cãâmara' => 'Cámara',
                'cãâmara' => 'cámara',
                'BATERÃA' => 'BATERÍA',
                'Baterãa' => 'Batería',
                'baterãa' => 'batería',
                'GENÍ?' => 'GENÉ',
                'Gení?' => 'Gené',
                'gení?' => 'gené',
                'VÃDEO' => 'VÍDEO',
                'Vãdeo' => 'Vídeo',
                'vãdeo' => 'vídeo',

                // Símbolo de grado
                'Ã‚Â°' => '°',

                // Patrones individuales
                'ÃÂMBRICO' => 'ÁMBRICO',
                'Ãâmbrico' => 'Ámbrico',
                'ãâmbrico' => 'ámbrico',
                'LÃÂMPARA' => 'LÁMPARA',
                'Lãâmpara' => 'Lámpara',
                'lãâmpara' => 'lámpara',
                'LÃÂQUIDA' => 'LÍQUIDA',
                'LÃÂQUIDO' => 'LÍQUIDO',
                'Lãâquido' => 'Líquido',
                'lãâquido' => 'líquido',
                'PORTÃÂTIL' => 'PORTÁTIL',
                'Portãâtil' => 'Portátil',
                'portãâtil' => 'portátil',
                'ESCÃÂNER' => 'ESCÁNER',
                'Escãâner' => 'Escáner',
                'escãâner' => 'escáner',
                'RÃÂGIDO' => 'RÍGIDO',
                'Rãâgido' => 'Rígido',
                'rãâgido' => 'rígido',
                'PÃÂGINA' => 'PÁGINA',
                'Pãâgina' => 'Página',
                'pãâgina' => 'página',
                'HÃÂBRIDA' => 'HÍBRIDA',
                'Hãâbrida' => 'Híbrida',
                'hãâbrida' => 'híbrida',
                'BOLÃÂGRAFO' => 'BOLÍGRAFO',
                'Bolãâgrafo' => 'Bolígrafo',
                'bolãâgrafo' => 'bolígrafo',
                'AUDÃFONO' => 'AUDÍFONO',
                'Audãfono' => 'Audífono',
                'audãfono' => 'audífono',
                'FOTOGRAFÃÂA' => 'FOTOGRAFÍA',
                'Fotografãâa' => 'Fotografía',
                'fotografãâa' => 'fotografía',

                // Bytes exactos UTF-8 del problema Í" (C3 8D E2 80 9C)
                "\xC3\x8D\xE2\x80\x9C" => 'Ó',
                "\xC3\xAD\xE2\x80\x9C" => 'ó',
                "\xC3\x8D\xE2\x80\x9D" => 'Ó',
                "\xC3\xAD\xE2\x80\x9D" => 'ó',

                // Bytes exactos É mal codificado
                "\xC3\x8D\x89" => 'É',
                "\xC3\xAD\x89" => 'é',

                // Encoding UTF-8 correcto pero mal interpretado
                "\xC3\x81" => 'Á',
                "\xC3\xA1" => 'á',
                "\xC3\x89" => 'É',
                "\xC3\xA9" => 'é',
                "\xC3\x8D" => 'Í',
                "\xC3\xAD" => 'í',
                "\xC3\x93" => 'Ó',
                "\xC3\xB3" => 'ó',
                "\xC3\x9A" => 'Ú',
                "\xC3\xBA" => 'ú',
                "\xC3\x91" => 'Ñ',
                "\xC3\xB1" => 'ñ',

                // Otros caracteres especiales
                "\xC2\xB0" => '°',
                "\xE2\x80\x99" => "'",
                "\xE2\x80\x9C" => '"',
                "\xE2\x80\x9D" => '"',
                "\xE2\x80\x93" => '-',
                "\xE2\x80\x94" => '—',
            ];

            // Aplicar reemplazos directos
            $text = str_replace(array_keys($directReplacements), array_values($directReplacements), $text);

            // PASO 2: Patrones con regex para variaciones
            $patterns = [
                '/Í\?([AEIOU])/ui' => 'Ñ$1',
                '/í\?([aeiou])/ui' => 'ñ$1',
                '/AÍ\?O/ui' => 'AÑO',
                '/aí\?o/ui' => 'año',
                '/Í\?/ui' => 'Ñ',
                '/í\?/ui' => 'ñ',

                // Patrones genéricos de palabras comunes
                '/AUD[ÃÍ][^\s]*FONO/ui' => 'AUDÍFONO',
                '/V[ÃÍ][^\s]*DEO/ui' => 'VÍDEO',
                '/C[ÃÁ][^\s]*MARA/ui' => 'CÁMARA',
                '/BATER[ÃÍ][^\s]*A/ui' => 'BATERÍA',
                '/INAL[ÃÁ][^\s]*MBRICO/ui' => 'INALÁMBRICO',
                '/M[ÃÓ][^\s]*VIL/ui' => 'MÓVIL',
                '/TEL[ÉÃ][^\s]*FONO/ui' => 'TELÉFONO',
                '/ELECTR[ÓÃ][^\s]*NICO/ui' => 'ELECTRÓNICO',
                '/[ÓÃ]PTICO/ui' => 'ÓPTICO',
                '/M[ÚÃ][^\s]*SICA/ui' => 'MÚSICA',
                '/EST[ÉÃ][^\s]*REO/ui' => 'ESTÉREO',
                '/MICR[ÓÃ][^\s]*FONO/ui' => 'MICRÓFONO',
                '/RAT[ÓÃ]N/ui' => 'RATÓN',
                '/M[ÓÃ]DULO/ui' => 'MÓDULO',
                '/RESOLUCI[ÓÃ]N/ui' => 'RESOLUCIÓN',
                '/VISI[ÓÃ]N/ui' => 'VISIÓN',
            ];

            foreach ($patterns as $pattern => $replacement) {
                $text = preg_replace($pattern, $replacement, $text);
            }

            // PASO 3: Limpieza final
            $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

            return $text;
        } catch (\Exception $e) {
            // Fallback: reemplazar solo los más comunes
            $text = str_replace(['Í"', 'í"', 'Í‰', 'í‰', 'Í?'], ['Ó', 'ó', 'É', 'é', 'Ñ'], $text ?? '');
            return $text;
        }
    }
}
