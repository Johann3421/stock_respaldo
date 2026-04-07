<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class DiagnoseEncoding extends Command
{
    protected $signature = 'products:diagnose-encoding {--limit=10}';
    protected $description = 'Diagnostica problemas de encoding mostrando los bytes exactos';

    public function handle()
    {
        $this->info('Buscando productos con caracteres sospechosos...');

        // Buscar productos que contengan caracteres con alto bit
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        $regexp = $driver === 'pgsql' ? '~' : 'REGEXP';

        $products = Product::whereRaw("producto {$regexp} '[\\x80-\\xFF]'")
            ->orWhereRaw("marca {$regexp} '[\\x80-\\xFF]'")
            ->limit($this->option('limit'))
            ->get();

        if ($products->isEmpty()) {
            $this->info('No se encontraron productos con caracteres especiales.');
            return 0;
        }

        $this->info("Encontrados {$products->count()} productos con caracteres especiales:\n");

        foreach ($products as $product) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line("ID: {$product->id}");
            $this->line("Producto: {$product->producto}");
            $this->line("Marca: {$product->marca}");

            // Analizar bytes del producto
            $this->analyzeString('Producto', $product->producto);

            if ($product->marca) {
                $this->analyzeString('Marca', $product->marca);
            }

            $this->line("");
        }

        return 0;
    }

    private function analyzeString($label, $string)
    {
        $this->line("\n{$label} - Análisis de bytes:");

        // Buscar secuencias sospechosas
        preg_match_all('/[\x80-\xFF]+/', $string, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            $this->info('  Sin caracteres especiales detectados');
            return;
        }

        foreach ($matches[0] as $match) {
            $chars = $match[0];
            $position = $match[1];

            // Contexto antes y después
            $contextBefore = mb_substr($string, max(0, $position - 5), 5);
            $contextAfter = mb_substr($string, $position + strlen($chars), 5);

            // Mostrar bytes en hexadecimal
            $hex = '';
            $readable = '';
            for ($i = 0; $i < strlen($chars); $i++) {
                $byte = ord($chars[$i]);
                $hex .= sprintf('%02X ', $byte);
                $readable .= $chars[$i];
            }

            $this->comment("  Posición {$position}:");
            $this->line("    Contexto: ...{$contextBefore}[{$readable}]{$contextAfter}...");
            $this->line("    Bytes hex: {$hex}");
            $this->line("    Caracteres: " . $this->getCharacterInfo($chars));

            // Sugerir corrección
            $suggested = $this->suggestCorrection($chars);
            if ($suggested !== $chars) {
                $this->info("    Sugerencia: '{$chars}' → '{$suggested}'");
            }
        }
    }

    private function getCharacterInfo($chars)
    {
        $info = [];
        for ($i = 0; $i < strlen($chars); $i++) {
            $byte = ord($chars[$i]);
            $char = $chars[$i];
            $info[] = "'{$char}' (0x" . sprintf('%02X', $byte) . ")";
        }
        return implode(' ', $info);
    }

    private function suggestCorrection($chars)
    {
        $corrections = [
            "\xCD\x22" => 'Ó',  // Í" -> Ó
            "\xED\x22" => 'ó',  // í" -> ó
            "\xCD\x89" => 'É',  // Í‰ -> É
            "\xED\x89" => 'é',  // í‰ -> é
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
        ];

        return $corrections[$chars] ?? $chars;
    }
}
