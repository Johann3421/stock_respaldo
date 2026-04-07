<?php

namespace App\Http\Controllers;

use App\Models\AreaVentas;
use App\Models\AreaContaduria;
use App\Models\AreaGerencia;
use App\Models\AreaDiseno;
use App\Models\AreaSistemas;
use App\Models\AreaAdministracion;
use App\Models\AreaSalaReuniones;
use App\Models\AreaEnsamblado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatrimonioController extends Controller
{
    private $areaModels = [];

    // Prefijos QR por área: GA01260 + dos letras identificadores
    private $areaPrefixes = [
        'Ventas' => 'VT',
        'Contaduría' => 'CT',
        'Gerencia' => 'GR',
        'Diseño' => 'DG',
        'Sistemas' => 'ST',
        'Administración' => 'AD',
        'Sala de Reuniones' => 'SR',
        'Ensamblado' => 'EN'
    ];

    public function __construct()
    {
        $this->middleware('auth');

        // Mapping de áreas a modelos
        $this->areaModels = [
            'Ventas' => AreaVentas::class,
            'Contaduría' => AreaContaduria::class,
            'Gerencia' => AreaGerencia::class,
            'Diseño' => AreaDiseno::class,
            'Sistemas' => AreaSistemas::class,
            'Administración' => AreaAdministracion::class,
            'Sala de Reuniones' => AreaSalaReuniones::class,
            'Ensamblado' => AreaEnsamblado::class
        ];
    }

    public function index()
    {
        $areas = [
            'piso_1' => ['Ventas'],
            'piso_2' => ['Contaduría', 'Gerencia', 'Diseño', 'Sistemas', 'Administración', 'Sala de Reuniones', 'Ensamblado']
        ];

        $items = [];

        return view('patrimonio.index', compact('areas', 'items'));
    }

    /**
     * Obtiene la clase modelo según el área
     */
    private function getModelClass($area)
    {
        return $this->areaModels[$area] ?? null;
    }

    /**
     * Obtiene instancia del modelo según el área
     */
    private function getModel($area)
    {
        $modelClass = $this->getModelClass($area);
        return $modelClass ? new $modelClass : null;
    }

    /**
     * Genera código QR automático para el área
     * Formato: GA01260 + Prefijo del área (2 letras) + Número secuencial (3 dígitos)
     * Ejemplo: GA01260ST001, GA01260CT002
     */
    private function generateQRCode($area)
    {
        $prefix = $this->areaPrefixes[$area] ?? 'XX';
        $modelClass = $this->getModelClass($area);

        // Obtener el próximo número secuencial para esta área
        $lastItem = $modelClass::orderByRaw('
            CAST(SUBSTR(codigo_patrimonial, -3) AS UNSIGNED) DESC
        ')->first();

        $nextNumber = 1;
        if ($lastItem && $lastItem->codigo_patrimonial) {
            $lastNumber = (int) substr($lastItem->codigo_patrimonial, -3);
            $nextNumber = $lastNumber + 1;
        }

        // Formato: GA01260 + Prefijo + Número con 3 dígitos
        return 'GA01260' . $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $area = $request->input('area');
        $model = $this->getModel($area);

        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Área no válida'], 400);
        }

        // Generar código QR automático
        $codigoQR = $this->generateQRCode($area);

        $validated = $request->validate([
            'area' => 'required|string',
            'piso' => 'required|integer|in:1,2',
            'descripcion' => 'required|string',
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'serie' => 'nullable|string',
            'estado' => 'required|string|in:Operativo,Inoperativo,En reparación,De baja',
            'valor_adquisicion' => 'nullable|numeric',
            'fecha_adquisicion' => 'nullable|date_format:Y-m-d',
            'responsable' => 'nullable|string',
            'observaciones' => 'nullable|string'
        ]);

        // Agregar el código QR generado automáticamente
        $validated['codigo_patrimonial'] = $codigoQR;
        $validated['user_id'] = Auth::id();

        // Remover piso e área antes de guardar (son solo para contexto)
        unset($validated['piso']);
        unset($validated['area']);

        $modelClass = $this->getModelClass($area);
        $modelClass::create($validated);

        return response()->json(['success' => true, 'message' => 'Artículo agregado correctamente', 'codigo' => $codigoQR]);
    }

    public function update(Request $request, $id)
    {
        $area = $request->input('area');
        $modelClass = $this->getModelClass($area);

        if (!$modelClass) {
            return response()->json(['success' => false, 'message' => 'Área no válida'], 400);
        }

        $item = $modelClass::findOrFail($id);

        $validated = $request->validate([
            'area' => 'required|string',
            'piso' => 'required|integer|in:1,2',
            'descripcion' => 'required|string',
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'serie' => 'nullable|string',
            'estado' => 'required|string|in:Operativo,Inoperativo,En reparación,De baja',
            'valor_adquisicion' => 'nullable|numeric',
            'fecha_adquisicion' => 'nullable|date_format:Y-m-d',
            'responsable' => 'nullable|string',
            'observaciones' => 'nullable|string'
        ]);

        // El código NO se actualiza, se mantiene el original
        unset($validated['codigo_patrimonial']);
        unset($validated['piso']);
        unset($validated['area']);

        $item->update($validated);

        return response()->json(['success' => true, 'message' => 'Artículo actualizado correctamente']);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $area = $request->input('area');

            if (!$area) {
                return response()->json(['success' => false, 'message' => 'Área no especificada'], 400);
            }

            $modelClass = $this->getModelClass($area);

            if (!$modelClass) {
                return response()->json(['success' => false, 'message' => 'Área no válida'], 400);
            }

            $item = $modelClass::findOrFail($id);
            $item->delete();

            return response()->json(['success' => true, 'message' => 'Artículo eliminado correctamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Artículo no encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }

    public function getByArea($piso, $area)
    {
        $modelClass = $this->getModelClass(urldecode($area));

        if (!$modelClass) {
            return response()->json([], 200);
        }

        try {
            $items = $modelClass::orderBy('codigo_patrimonial')->get();
            return response()->json($items, 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    /**
     * Obtiene el total de valores y estado de cierre de un área
     */
    public function getAreaSummary($piso, $area)
    {
        $modelClass = $this->getModelClass(urldecode($area));

        if (!$modelClass) {
            return response()->json(['total_value' => 0, 'closed_at' => null, 'closed_by_user' => null, 'item_count' => 0], 200);
        }

        try {
            $items = $modelClass::all();

            $totalValue = $items->sum(function($item) {
                return (float) $item->valor_adquisicion;
            });

            // Obtener el closed_at y closed_by_user_id del primer registro (todos tienen el mismo)
            $closedAt = $items->first()?->closed_at;
            $closedByUserId = $items->first()?->closed_by_user_id;

            $closedByUser = null;
            if ($closedByUserId) {
                $user = User::find($closedByUserId);
                if ($user) {
                    $closedByUser = $user->name;
                }
            }

            return response()->json([
                'total_value' => number_format($totalValue, 2),
                'closed_at' => $closedAt,
                'closed_by_user' => $closedByUser,
                'item_count' => $items->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['total_value' => 0, 'closed_at' => null, 'closed_by_user' => null, 'item_count' => 0], 200);
        }
    }

    /**
     * Cierra el inventariado de un área
     */
    public function closeInventory(Request $request, $piso, $area)
    {
        try {
            $decodedArea = urldecode($area);
            $modelClass = $this->getModelClass($decodedArea);

            if (!$modelClass) {
                return response()->json(['success' => false, 'message' => 'Área no válida'], 400);
            }

            $items = $modelClass::all();

            if ($items->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No hay artículos en esta área'], 400);
            }

            // Marcar todos los items como cerrados con usuario y timestamp
            $closedAt = now();
            $userId = Auth::id();
            foreach ($items as $item) {
                $item->update([
                    'closed_at' => $closedAt,
                    'closed_by_user_id' => $userId
                ]);
            }

            // Calcular el total
            $totalValue = $items->sum(function($item) {
                return (float) $item->valor_adquisicion;
            });

            $closedByUser = Auth::user()->name;

            return response()->json([
                'success' => true,
                'message' => 'Inventario cerrado correctamente',
                'closed_at' => $closedAt->format('d/m/Y H:i:s'),
                'closed_by_user' => $closedByUser,
                'total_value' => number_format($totalValue, 2),
                'item_count' => $items->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al cerrar inventario: ' . $e->getMessage()], 500);
        }
    }
}
