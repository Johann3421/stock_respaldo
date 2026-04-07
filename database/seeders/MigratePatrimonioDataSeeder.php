<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigratePatrimonioDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los datos de la tabla antigua
        $oldItems = DB::table('patrimonio_items')->get();

        $areaTableMapping = [
            'Ventas' => 'area_ventas',
            'Contaduría' => 'area_contaduria',
            'Gerencia' => 'area_gerencia',
            'Diseño' => 'area_diseno',
            'Sistemas' => 'area_sistemas',
            'Administración' => 'area_administracion',
            'Sala de Reuniones' => 'area_sala_reuniones',
            'Ensamblado' => 'area_ensamblado'
        ];

        foreach ($oldItems as $item) {
            $table = $areaTableMapping[$item->area] ?? null;

            if ($table) {
                DB::table($table)->insert([
                    'codigo_patrimonial' => $item->codigo_patrimonial,
                    'descripcion' => $item->descripcion,
                    'marca' => $item->marca,
                    'modelo' => $item->modelo,
                    'serie' => $item->serie,
                    'estado' => $item->estado,
                    'valor_adquisicion' => $item->valor_adquisicion,
                    'fecha_adquisicion' => $item->fecha_adquisicion,
                    'responsable' => $item->responsable,
                    'observaciones' => $item->observaciones,
                    'user_id' => $item->user_id,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ]);
            }
        }

        $this->command->info('✓ Datos migrados exitosamente a las nuevas tablas de área');
    }
}
