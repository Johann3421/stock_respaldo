<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixProductEncoding extends Command
{
    protected $signature = 'products:fix-encoding';
    protected $description = 'Corrige el encoding de productos y marcas con caracteres especiales mal codificados';

    public function handle()
    {
        $this->info('Iniciando corrección de encoding...');

        $fixed = 0;
        $total = Product::count();

        $this->output->progressStart($total);

        Product::chunk(100, function ($products) use (&$fixed) {
            foreach ($products as $product) {
                $updated = false;
                $originalProducto = $product->producto;
                $originalMarca = $product->marca;

                // Corregir producto
                $fixedProducto = $this->fixEncoding($product->producto);
                if ($fixedProducto !== $originalProducto) {
                    $product->producto = $fixedProducto;
                    $updated = true;
                }

                // Corregir marca
                if ($product->marca) {
                    $fixedMarca = $this->fixEncoding($product->marca);
                    if ($fixedMarca !== $originalMarca) {
                        $product->marca = $fixedMarca;
                        $updated = true;
                    }
                }

                if ($updated) {
                    $product->save();
                    $fixed++;
                    $this->line("\n✓ Corregido: {$originalProducto} → {$product->producto}");
                }

                $this->output->progressAdvance();
            }
        });

        $this->output->progressFinish();
        $this->info("\n✓ Proceso completado: {$fixed} productos corregidos de {$total} totales");

        return 0;
    }

    private function fixEncoding($text)
    {
        if (!$text) {
            return $text;
        }

        $text = (string)$text;

        // PASO 1: Reemplazos directos de patrones específicos conocidos (usando bytes exactos)
        $directReplacements = [
            // Bytes C3 8D C2 81 (CÍMARA mal codificado)
            "\xC3\x8D\xC2\x81" => 'Á',  // ÍÁ -> Á
            'CÍMARA' => 'CÁMARA',
            'Címara' => 'Cámara',
            'címara' => 'cámara',

            // Doble encoding de Á É Í Ó Ú (Ã seguido de caracteres)
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

            // Símbolo de grado con doble encoding
            'Ã‚Â°' => '°',
            '°' => '°',

            // Patrones individuales de doble encoding
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
            "\xC3\x8D\xE2\x80\x9C" => 'Ó',  // Í" -> Ó
            "\xC3\xAD\xE2\x80\x9C" => 'ó',  // í" -> ó
            "\xC3\x8D\xE2\x80\x9D" => 'Ó',  // Í" -> Ó (comilla derecha)
            "\xC3\xAD\xE2\x80\x9D" => 'ó',  // í" -> ó

            // Bytes exactos É mal codificado
            "\xC3\x8D\x89" => 'É',  // Í‰ -> É
            "\xC3\xAD\x89" => 'é',  // í‰ -> é

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
            "\xE2\x80\x99" => "'",  // comilla simple curva
            "\xE2\x80\x9C" => '"',  // comilla doble izquierda
            "\xE2\x80\x9D" => '"',  // comilla doble derecha
            "\xE2\x80\x93" => '-',  // guión en dash
            "\xE2\x80\x94" => '—',  // guión em dash
            "\xE2\x80\x94" => '—',  // guión em dash
        ];


        // Aplicar reemplazos directos
        $text = str_replace(array_keys($directReplacements), array_values($directReplacements), $text);

        // PASO 2: Intentar decodificar si viene con doble encoding
        if (preg_match('/[\x80-\xFF]{2,}/', $text)) {
            $attempts = [
                function($t) {
                    $decoded = @mb_convert_encoding($t, 'ISO-8859-1', 'UTF-8');
                    return @mb_convert_encoding($decoded, 'UTF-8', 'ISO-8859-1');
                },
                function($t) { return @mb_convert_encoding($t, 'UTF-8', 'Windows-1252'); },
                function($t) { return @mb_convert_encoding($t, 'UTF-8', 'ISO-8859-1'); },
                function($t) { return @iconv('CP1252', 'UTF-8//IGNORE', $t); },
            ];

            foreach ($attempts as $attempt) {
                $converted = $attempt($text);
                if ($converted && mb_check_encoding($converted, 'UTF-8')) {
                    $rareChars = preg_match_all('/[^\x20-\x7E\xC0-\xFF]/u', $converted ?? '');
                    $originalRareChars = preg_match_all('/[^\x20-\x7E\xC0-\xFF]/u', $text);
                    if ($rareChars < $originalRareChars) {
                        $text = $converted;
                        break;
                    }
                }
            }
        }

        // PASO 3: Usar expresiones regulares para patrones genéricos
        $text = preg_replace_callback('/[\xC0-\xFF][\x80-\xBF]*/', function($matches) {
            $char = $matches[0];
            if (mb_check_encoding($char, 'UTF-8')) {
                return $char;
            }
            return @mb_convert_encoding($char, 'UTF-8', 'ISO-8859-1') ?: $char;
        }, $text);

        // PASO 4: Limpieza final
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        return $text;
    }
}
