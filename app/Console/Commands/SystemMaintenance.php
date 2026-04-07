<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:maintenance {--full : Ejecutar mantenimiento completo incluyendo optimizaciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia caches, optimiza y ejecuta tareas de mantenimiento del sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Iniciando mantenimiento del sistema...');
        $this->newLine();

        $results = [];

        // 1. Limpiar cache de aplicación
        $this->info('⏳ Limpiando cache de aplicación...');
        try {
            Artisan::call('cache:clear');
            $results['cache'] = '✅ OK';
            $this->line($results['cache']);
        } catch (\Exception $e) {
            $results['cache'] = '❌ ERROR: ' . $e->getMessage();
            $this->error($results['cache']);
        }

        // 2. Limpiar cache de configuración
        $this->info('⏳ Limpiando cache de configuración...');
        try {
            Artisan::call('config:clear');
            $results['config'] = '✅ OK';
            $this->line($results['config']);
        } catch (\Exception $e) {
            $results['config'] = '❌ ERROR: ' . $e->getMessage();
            $this->error($results['config']);
        }

        // 3. Limpiar vistas compiladas
        $this->info('⏳ Limpiando vistas compiladas...');
        try {
            Artisan::call('view:clear');
            $results['view'] = '✅ OK';
            $this->line($results['view']);
        } catch (\Exception $e) {
            $results['view'] = '❌ ERROR: ' . $e->getMessage();
            $this->error($results['view']);
        }

        // 4. Limpiar cache de rutas
        $this->info('⏳ Limpiando cache de rutas...');
        try {
            Artisan::call('route:clear');
            $results['route'] = '✅ OK';
            $this->line($results['route']);
        } catch (\Exception $e) {
            $results['route'] = '❌ ERROR: ' . $e->getMessage();
            $this->error($results['route']);
        }

        // 5. Limpiar sesiones expiradas
        $this->info('⏳ Limpiando sesiones expiradas...');
        try {
            if (config('session.driver') === 'database') {
                $expired = DB::table(config('session.table', 'sessions'))
                    ->where('last_activity', '<', time() - config('session.lifetime') * 60)
                    ->delete();
                $results['sessions'] = "✅ OK (eliminadas: {$expired})";
                $this->line($results['sessions']);
            } else {
                $results['sessions'] = '⚠️  SKIP (no usa database)';
                $this->warn($results['sessions']);
            }
        } catch (\Exception $e) {
            $results['sessions'] = '❌ ERROR: ' . $e->getMessage();
            $this->error($results['sessions']);
        }

        // Si se especifica --full, ejecutar optimizaciones adicionales
        if ($this->option('full')) {
            $this->newLine();
            $this->info('🚀 Ejecutando optimizaciones adicionales...');
            $this->newLine();

            // 6. Cachear configuración
            $this->info('⏳ Cacheando configuración...');
            try {
                if (config('app.env') === 'production') {
                    Artisan::call('config:cache');
                    $results['config_cache'] = '✅ OK';
                    $this->line($results['config_cache']);
                } else {
                    $results['config_cache'] = '⚠️  SKIP (no production)';
                    $this->warn($results['config_cache']);
                }
            } catch (\Exception $e) {
                $results['config_cache'] = '❌ ERROR: ' . $e->getMessage();
                $this->error($results['config_cache']);
            }

            // 7. Cachear rutas
            $this->info('⏳ Cacheando rutas...');
            try {
                if (config('app.env') === 'production') {
                    Artisan::call('route:cache');
                    $results['route_cache'] = '✅ OK';
                    $this->line($results['route_cache']);
                } else {
                    $results['route_cache'] = '⚠️  SKIP (no production)';
                    $this->warn($results['route_cache']);
                }
            } catch (\Exception $e) {
                $results['route_cache'] = '❌ ERROR: ' . $e->getMessage();
                $this->error($results['route_cache']);
            }

            // 8. Optimizar autoloader
            $this->info('⏳ Optimizando autoloader...');
            try {
                Artisan::call('optimize');
                $results['optimize'] = '✅ OK';
                $this->line($results['optimize']);
            } catch (\Exception $e) {
                $results['optimize'] = '❌ ERROR: ' . $e->getMessage();
                $this->error($results['optimize']);
            }
        }

        $this->newLine();
        $this->info('✅ Mantenimiento completado!');
        $this->newLine();

        // Mostrar resumen
        $this->table(
            ['Tarea', 'Estado'],
            collect($results)->map(fn($status, $task) => [$task, $status])->toArray()
        );

        return Command::SUCCESS;
    }
}
