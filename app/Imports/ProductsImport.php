<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductsImport implements
    ToModel,
    WithHeadingRow,
    SkipsEmptyRows,
    WithBatchInserts,
    WithChunkReading,
    SkipsOnError,
    SkipsOnFailure
{
    private $errors = [];
    private $skipped = 0;
    private $imported = 0;

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        try {
            // Mapeo correcto según el Excel
            $codigo = $row[1] ?? null;
            $producto = $row[2] ?? null;
            $marca = $row[4] ?? null;
            $costoRaw = $row[7] ?? 0;
            $precioClienteRaw = $row['general_s'] ?? $row[10] ?? 0;
            $stock = $row[14] ?? 0;

            // Validaciones básicas
            if (!$codigo || !$producto || $codigo == 'CODIGO' || $codigo == 'NUM') {
                $this->skipped++;
                return null;
            }

            // Limpiar código
            $codigo = preg_replace('/\s+/', '', trim($codigo));

            // Validar longitud del código
            if (strlen($codigo) > 100) {
                Log::warning("Código demasiado largo, omitido: " . substr($codigo, 0, 20));
                $this->skipped++;
                return null;
            }

            // Corregir encoding UTF-8
            $producto = $this->fixEncoding($producto);
            $marca = $this->fixEncoding($marca);

            // Limpiar producto
            $producto = preg_replace('/\s+/', ' ', trim($producto));
            $producto = str_replace(["\r", "\n", "\t"], ' ', $producto);

            // Validar longitud del producto
            if (strlen($producto) > 500) {
                $producto = substr($producto, 0, 500);
            }

            // Limpiar marca
            if ($marca) {
                $marca = preg_replace('/\s+/', ' ', trim($marca));
                if (strlen($marca) > 200) {
                    $marca = substr($marca, 0, 200);
                }
            }

            // Limpiar y validar valores numéricos
            $costo = $this->cleanNumeric($costoRaw);
            $precioCliente = $this->cleanNumeric($precioClienteRaw);
            $stock = intval($this->cleanNumeric($stock));

            // Validar rangos
            if ($costo > 999999.99) $costo = 999999.99;
            if ($costo < 0) $costo = 0;

            if ($precioCliente > 999999.99) $precioCliente = 999999.99;
            if ($precioCliente < 0) $precioCliente = 0;

            if ($stock > 999999) $stock = 999999;
            if ($stock < 0) $stock = 0;

            // Verificar si el producto ya existe (con manejo de errores)
            try {
                $exists = Product::where('codigo', $codigo)->exists();
                if ($exists) {
                    $this->skipped++;
                    return null;
                }
            } catch (\Exception $e) {
                Log::error("Error verificando duplicado: " . $e->getMessage());
                $this->skipped++;
                return null;
            }

            $this->imported++;

            return new Product([
                'codigo' => $codigo,
                'producto' => $producto,
                'marca' => $marca,
                'costo' => round($costo, 2),
                'precio_cliente' => round($precioCliente, 2),
                'stock' => $stock,
            ]);
        } catch (\Exception $e) {
            Log::error("Error procesando fila en import: " . $e->getMessage(), [
                'row' => $row,
                'trace' => $e->getTraceAsString()
            ]);
            $this->errors[] = $e->getMessage();
            $this->skipped++;
            return null;
        }
    }

    private function cleanNumeric($value)
    {
        try {
            if (is_numeric($value)) {
                return floatval($value);
            }

            // Limpiar el valor si es string
            $cleaned = preg_replace('/[^0-9.]/', '', (string)$value);

            // Validar que solo haya un punto decimal
            $parts = explode('.', $cleaned);
            if (count($parts) > 2) {
                $cleaned = $parts[0] . '.' . implode('', array_slice($parts, 1));
            }

            return is_numeric($cleaned) ? floatval($cleaned) : 0;
        } catch (\Exception $e) {
            Log::warning("Error limpiando valor numérico: " . $e->getMessage());
            return 0;
        }
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
            ];

            // Aplicar reemplazos directos
            $text = str_replace(array_keys($directReplacements), array_values($directReplacements), $text);

            // PASO 2: Intentar decodificar si viene con doble encoding
            if (preg_match('/[\x80-\xFF]{2,}/', $text)) {
                // Parece tener encoding múltiple, intentar decodificar
                $attempts = [
                    // Intentar UTF-8 -> ISO-8859-1 -> UTF-8
                    function($t) {
                        $decoded = @mb_convert_encoding($t, 'ISO-8859-1', 'UTF-8');
                        return @mb_convert_encoding($decoded, 'UTF-8', 'ISO-8859-1');
                    },
                    // Intentar Windows-1252 -> UTF-8
                    function($t) { return @mb_convert_encoding($t, 'UTF-8', 'Windows-1252'); },
                    // Intentar ISO-8859-1 -> UTF-8
                    function($t) { return @mb_convert_encoding($t, 'UTF-8', 'ISO-8859-1'); },
                    // Intentar CP1252 -> UTF-8
                    function($t) { return @iconv('CP1252', 'UTF-8//IGNORE', $t); },
                ];

                foreach ($attempts as $attempt) {
                    $converted = $attempt($text);
                    if ($converted && mb_check_encoding($converted, 'UTF-8')) {
                        // Verificar si mejoró (menos caracteres raros)
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
                // Si es un carácter UTF-8 válido, dejarlo
                if (mb_check_encoding($char, 'UTF-8')) {
                    return $char;
                }
                // Si no, intentar convertir desde ISO-8859-1
                return @mb_convert_encoding($char, 'UTF-8', 'ISO-8859-1') ?: $char;
            }, $text);

            // PASO 4: Limpieza final
            $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

            // Log si hubo cambios significativos para debug
            if ($text !== $original && strlen($text) > 20) {
                Log::debug('Encoding corregido', [
                    'original' => substr($original, 0, 100),
                    'corregido' => substr($text, 0, 100)
                ]);
            }

            return $text;
        } catch (\Exception $e) {
            Log::warning("Error en fixEncoding: " . $e->getMessage(), ['text' => substr($text ?? '', 0, 50)]);
            // Fallback: reemplazar solo los más comunes
            $text = str_replace(['Í"', 'í"', 'Í‰', 'í‰'], ['Ó', 'ó', 'É', 'é'], $text ?? '');
            return $text;
        }
    }

    public function headingRow(): int
    {
        return 2; // La fila 2 tiene los encabezados reales
    }

    // Procesar en lotes para mejor rendimiento
    public function batchSize(): int
    {
        return 500;
    }

    // Procesar en chunks para evitar problemas de memoria
    public function chunkSize(): int
    {
        return 500;
    }

    // Manejar errores sin detener la importación
    public function onError(\Throwable $e)
    {
        Log::error("Error en importación: " . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        $this->errors[] = $e->getMessage();
    }

    // Manejar fallos de validación
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            Log::warning("Fallo de validación en fila {$failure->row()}: " . implode(', ', $failure->errors()));
            $this->errors[] = "Fila {$failure->row()}: " . implode(', ', $failure->errors());
        }
    }

    // Obtener estadísticas de la importación
    public function getStats()
    {
        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'errors' => count($this->errors),
            'error_messages' => $this->errors
        ];
    }
}
