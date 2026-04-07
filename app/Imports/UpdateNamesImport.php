<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class UpdateNamesImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    private $updated = 0;
    private $skipped = 0;
    private $notFound = 0;

    public function model(array $row)
    {
        try {
            // Normalizar las claves del array (convertir a minúsculas y quitar espacios)
            $row = array_change_key_case($row, CASE_LOWER);
            $row = array_map('trim', $row);
            
            // Buscar por código (el archivo tiene CODIGO y NOMBRE/PRODUCTO)
            $codigo = $row['codigo'] ?? ($row['code'] ?? null);
            $nombre = $row['nombre'] ?? ($row['producto'] ?? ($row['name'] ?? ($row['product'] ?? null)));

            // Validar que existan ambos campos
            if (empty($codigo) || empty($nombre)) {
                $this->skipped++;
                Log::warning("Fila omitida por datos incompletos", [
                    'codigo' => $codigo ?? 'vacío',
                    'nombre' => $nombre ?? 'vacío'
                ]);
                return null;
            }

            // Limpiar el código (quitar espacios y caracteres no deseados)
            $codigo = trim(preg_replace('/\s+/', '', $codigo));
            
            // Limpiar el nombre (quitar espacios múltiples)
            $nombre = trim(preg_replace('/\s+/', ' ', $nombre));

            // Validar longitud
            if (strlen($nombre) > 500) {
                $this->skipped++;
                Log::warning("Nombre demasiado largo", ['codigo' => $codigo, 'length' => strlen($nombre)]);
                return null;
            }

            // Buscar el producto
            $product = Product::where('codigo', $codigo)->first();

            if (!$product) {
                $this->notFound++;
                Log::info("Producto no encontrado", ['codigo' => $codigo]);
                return null;
            }

            // Solo actualizar si el nombre es diferente
            if ($product->producto !== $nombre) {
                $oldName = $product->producto;
                $product->update([
                    'producto' => $nombre,
                    'updated_at' => now()
                ]);
                $this->updated++;
                Log::info("Nombre actualizado exitosamente", [
                    'id' => $product->id,
                    'codigo' => $codigo,
                    'nombre_anterior' => $oldName,
                    'nombre_nuevo' => $nombre
                ]);
            } else {
                $this->skipped++;
            }

            return null; // No crear modelo nuevo, solo actualizar
        } catch (\Exception $e) {
            Log::error("Error actualizando nombre de producto", [
                'codigo' => $codigo ?? 'desconocido',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->skipped++;
            return null;
        }
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
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
}
