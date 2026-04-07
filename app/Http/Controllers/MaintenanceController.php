<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MaintenanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Solo administradores pueden acceder
        if (!Auth::check()) {
            abort(403, 'No autorizado');
        }

        return view('maintenance.index');
    }

    public function fixEncoding(Request $request)
    {
        try {
            // Verificar que el usuario esté autenticado
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            Log::info('Ejecutando fix-encoding desde web', [
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            // Ejecutar el comando
            Artisan::call('products:fix-encoding');
            $output = Artisan::output();

            Log::info('Comando fix-encoding completado', [
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comando ejecutado exitosamente',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Error ejecutando fix-encoding', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearCache(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'Caché limpiado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
