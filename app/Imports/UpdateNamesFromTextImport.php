<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class UpdateNamesFromTextImport
{
    private $updated = 0;
    private $skipped = 0;
    private $notFound = 0;
    private $errors = [];

    /**
     * Procesar archivo de texto con formato pipe-delimited
     */
    public function import(string $filePath)
    {
        try {
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                throw new \Exception('No se pudo leer el archivo');
            }

            // Dividir en líneas
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // Saltar líneas vacías o de encabezado
                if (empty(trim($line)) || 
                    strpos($line, '===') === 0 || 
                    strpos($line, '==') === 0 || 
                    strpos($line, '|------') === 0 ||
                    strpos($line, '|Columna|') === 0 ||
                    strpos($line, '|id|') === 0) {
                    continue;
                }

                // Procesar solo líneas que comienzan con pipe
                if (strpos($line, '|') === 0) {
                    $this->processLine($line, $lineNumber + 1);
                }
            }

            Log::info('Importación de nombres desde archivo TXT completada', [
                'updated' => $this->updated,
                'skipped' => $this->skipped,
                'not_found' => $this->notFound,
                'errors' => count($this->errors)
            ]);

        } catch (\Exception $e) {
            Log::error('Error procesando archivo TXT', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function processLine(string $line, int $lineNumber)
    {
        try {
            // Dividir por pipes
            $parts = explode('|', $line);
            
            // Formato esperado: |ID|CODIGO|NOMBRE|FECHA|
            // Índice 0: vacío (antes del primer pipe)
            // Índice 1: ID (no lo usamos)
            // Índice 2: CODIGO
            // Índice 3: NOMBRE
            // Índice 4: FECHA (no la usamos)
            
            if (count($parts) < 4) {
                $this->skipped++;
                return;
            }

            $codigo = trim($parts[2]);
            $nombre = trim($parts[3]);

            // Validar que existan ambos campos
            if (empty($codigo) || empty($nombre)) {
                $this->skipped++;
                Log::warning("Línea {$lineNumber}: Datos incompletos", [
                    'codigo' => $codigo,
                    'nombre' => substr($nombre, 0, 50)
                ]);
                return;
            }

            // Decodificar entidades HTML (&quot; a ")
            $nombre = html_entity_decode($nombre, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            // Limpiar el nombre (quitar espacios múltiples, comas al final, etc)
            $nombre = trim(preg_replace('/\s+/', ' ', $nombre));
            $nombre = rtrim($nombre, ',');

            // Validar longitud
            if (strlen($nombre) > 500) {
                $nombre = substr($nombre, 0, 500);
                Log::warning("Nombre truncado en línea {$lineNumber}", [
                    'codigo' => $codigo,
                    'length_original' => strlen($nombre)
                ]);
            }

            // Buscar el producto
            $product = Product::where('codigo', $codigo)->first();

            if (!$product) {
                $this->notFound++;
                Log::debug("Producto no encontrado en línea {$lineNumber}", ['codigo' => $codigo]);
                return;
            }

            // Solo actualizar si el nombre es diferente
            $oldName = $product->producto;
            if ($oldName !== $nombre) {
                $product->update([
                    'producto' => $nombre,
                    'updated_at' => now()
                ]);
                
                $this->updated++;
                
                Log::info("Nombre actualizado exitosamente", [
                    'id' => $product->id,
                    'codigo' => $codigo,
                    'nombre_anterior' => substr($oldName, 0, 100),
                    'nombre_nuevo' => substr($nombre, 0, 100),
                    'linea' => $lineNumber
                ]);
            } else {
                $this->skipped++;
            }

        } catch (\Exception $e) {
            $this->errors[] = [
                'line' => $lineNumber,
                'error' => $e->getMessage()
            ];
            
            Log::error("Error procesando línea {$lineNumber}", [
                'error' => $e->getMessage(),
                'line_content' => substr($line, 0, 200)
            ]);
        }
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function getNotFound(): int
    {
        return $this->notFound;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
