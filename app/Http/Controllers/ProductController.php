<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockVerification;
use App\Imports\ProductsImport;
use App\Exports\ProductsExport;
use App\Exports\ProductsVerifiedExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        try {
            // Verificar que el usuario esté autenticado correctamente
            if (!Auth::check() || !Auth::user()) {
                return redirect()->route('login')->with('error', 'Sesión expirada. Por favor, inicia sesión nuevamente.');
            }

            // Usar scopes para simplificar las queries
            // Ordenar por ID para mantener consistencia en paginación
            $products = Product::query()
                ->search($request->search)
                ->byStockColor($request->color)
                ->orderBy('id')
                ->paginate(50)
                ->appends($request->query());

            // Calcular conteos basados en el nuevo algoritmo:
            // 1. Primero comparar V1 vs V2
            // 2. Si V1 = V2, entonces comparar (V2 + V3) vs Stock
            $totalProducts = Product::count();
            $colorCounts = DB::table('products')
                ->selectRaw(<<<'SQL'
SUM(CASE
    WHEN COALESCE(stock_verificado, 0) = 0 AND COALESCE(stock_verificado_2, 0) = 0 AND COALESCE(stock_verificado_3, 0) = 0
    THEN 1 ELSE 0
END) as unverified,
SUM(CASE
    WHEN (COALESCE(stock_verificado, 0) + COALESCE(stock_verificado_2, 0)) > 0
     AND COALESCE(stock_verificado, 0) != COALESCE(stock_verificado_2, 0)
    THEN 1 ELSE 0
END) as discrepancy,
SUM(CASE
    WHEN COALESCE(stock_verificado, 0) = COALESCE(stock_verificado_2, 0)
     AND (COALESCE(stock_verificado_2, 0) + COALESCE(stock_verificado_3, 0)) > 0
     AND (
         (stock > 0 AND (COALESCE(stock_verificado_2, 0) + COALESCE(stock_verificado_3, 0)) < stock)
         OR (stock = 0)
     )
    THEN 1 ELSE 0
END) as danger,
SUM(CASE
    WHEN stock > 0
     AND COALESCE(stock_verificado, 0) = COALESCE(stock_verificado_2, 0)
     AND (COALESCE(stock_verificado_2, 0) + COALESCE(stock_verificado_3, 0)) = stock
    THEN 1 ELSE 0
END) as success,
SUM(CASE
    WHEN stock > 0
     AND COALESCE(stock_verificado, 0) = COALESCE(stock_verificado_2, 0)
     AND (COALESCE(stock_verificado_2, 0) + COALESCE(stock_verificado_3, 0)) > stock
    THEN 1 ELSE 0
END) as warning
SQL
                )
                ->first();

            return view('products.index', compact('products', 'colorCounts', 'totalProducts'));
        } catch (\Exception $e) {
            Log::error('Error en products.index: ' . $e->getMessage() . ' | User: ' . (Auth::check() ? Auth::id() : 'guest'));

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al cargar los productos.'], 500);
            }
            return response()->view('errors.custom', ['message' => 'Error al cargar los productos. Por favor, intenta nuevamente más tarde.'], 500);
        }
    }

    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // Max 10MB
            ], [
                'file.required' => 'Debe seleccionar un archivo',
                'file.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV',
                'file.max' => 'El archivo no puede superar los 10MB'
            ]);

            // Verificar que el archivo existe
            if (!$request->hasFile('file')) {
                throw new \Exception('No se recibió ningún archivo');
            }

            $file = $request->file('file');

            // Verificar que el archivo es válido
            if (!$file->isValid()) {
                throw new \Exception('El archivo está corrupto o no es válido');
            }

            // Aumentar límites temporalmente para importaciones grandes
            set_time_limit(300); // 5 minutos
            ini_set('memory_limit', '512M');

            DB::beginTransaction();

            try {
                $import = new ProductsImport;
                Excel::import($import, $file);

                // Obtener estadísticas de la importación
                $stats = $import->getStats();

                DB::commit();

                Log::info('Importación exitosa', [
                    'user_id' => Auth::id(),
                    'filename' => $file->getClientOriginalName(),
                    'imported' => $stats['imported'],
                    'skipped' => $stats['skipped'],
                    'errors' => $stats['errors']
                ]);

                // Construir mensaje con estadísticas
                $message = "Importación completada: {$stats['imported']} productos importados";
                if ($stats['skipped'] > 0) {
                    $message .= ", {$stats['skipped']} omitidos (duplicados o inválidos)";
                }
                if ($stats['errors'] > 0) {
                    $message .= ". Revise los logs para más detalles.";
                }

                return redirect()->back()->with('success', $message);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator);
        } catch (\Exception $e) {
            Log::error('Error en importación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new ProductsExport, 'inventario_' . date('Y-m-d_H-i-s') . '.xlsx');
    }

    public function exportVerified()
    {
        return Excel::download(new ProductsVerifiedExport, 'inventario_verificado_' . date('Y-m-d_H-i-s') . '.xlsx');
    }

    public function updateNames(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240'
            ], [
                'file.required' => 'Debe seleccionar un archivo',
                'file.mimes' => 'El archivo debe ser Excel (.xlsx, .xls), CSV o TXT',
                'file.max' => 'El archivo no puede superar los 10MB'
            ]);

            if (!$request->hasFile('file')) {
                throw new \Exception('No se recibió ningún archivo');
            }

            $file = $request->file('file');

            if (!$file->isValid()) {
                throw new \Exception('El archivo está corrupto o no es válido');
            }

            set_time_limit(300);
            ini_set('memory_limit', '512M');

            DB::beginTransaction();

            try {
                $extension = strtolower($file->getClientOriginalExtension());

                // Procesar según el tipo de archivo
                if ($extension === 'txt') {
                    // Usar importador especializado para archivos TXT
                    $import = new \App\Imports\UpdateNamesFromTextImport();
                    $import->import($file->getRealPath());

                    $updated = $import->getUpdated();
                    $skipped = $import->getSkipped();
                    $notFound = $import->getNotFound();
                    $errors = $import->getErrors();
                } else {
                    // Usar importador Excel para xlsx, xls, csv
                    $import = new \App\Imports\UpdateNamesImport;
                    Excel::import($import, $file);

                    $updated = $import->getUpdated();
                    $skipped = $import->getSkipped();
                    $notFound = $import->getNotFound();
                    $errors = [];
                }

                DB::commit();

                Log::info('Actualización de nombres exitosa', [
                    'user_id' => Auth::id(),
                    'filename' => $file->getClientOriginalName(),
                    'type' => $extension,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'not_found' => $notFound
                ]);

                $message = "✅ Actualización completada: {$updated} productos actualizados";
                if ($skipped > 0) {
                    $message .= ", {$skipped} omitidos (sin cambios)";
                }
                if ($notFound > 0) {
                    $message .= ", {$notFound} códigos no encontrados";
                }
                if (!empty($errors)) {
                    $message .= ". Se encontraron " . count($errors) . " errores (ver logs)";
                }

                return redirect()->back()->with('success', $message);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator);
        } catch (\Exception $e) {
            Log::error('Error en actualización de nombres', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            $errorMessage = '❌ Error al actualizar nombres: ';

            if (strpos($e->getMessage(), 'does not exist') !== false) {
                $errorMessage .= 'El archivo seleccionado no es válido o está corrupto.';
            } elseif (strpos($e->getMessage(), 'header') !== false || strpos($e->getMessage(), 'column') !== false) {
                $errorMessage .= 'El archivo debe contener las columnas CODIGO y NOMBRE (o PRODUCTO).';
            } else {
                $errorMessage .= $e->getMessage();
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    public function updateStock(Request $request, Product $product)
    {
        return $this->updateStockField($request, $product, 1);
    }

    public function updateStock2(Request $request, Product $product)
    {
        return $this->updateStockField($request, $product, 2);
    }

    public function updateStock3(Request $request, Product $product)
    {
        return $this->updateStockField($request, $product, 3);
    }

    /**
     * Método unificado para actualizar stock verificado
     */
    private function updateStockField(Request $request, Product $product, int $fieldNumber)
    {
        try {
            if (!Auth::check() || !Auth::user()) {
                return response()->json(['success' => false, 'message' => 'Sesión expirada'], 401);
            }

            $fieldName = 'stock_verificado' . ($fieldNumber > 1 ? "_{$fieldNumber}" : '');
            $verifiedByField = 'verificado_por' . ($fieldNumber > 1 ? "_{$fieldNumber}" : '');
            $lastVerificationField = 'ultima_verificacion' . ($fieldNumber > 1 ? "_{$fieldNumber}" : '');

            // Validación más estricta
            $validated = $request->validate([
                $fieldName => 'required|integer|min:0|max:999999'
            ]);

            $stockValue = intval($validated[$fieldName]);
            $userName = Auth::user()->display_name;

            // Usar transacción y lock para evitar race conditions
            DB::beginTransaction();

            try {
                // Lock pessimista para evitar actualizaciones simultáneas
                $product = Product::lockForUpdate()->findOrFail($product->id);

                $stockAnterior = $product->{$fieldName};

                $product->update([
                    $fieldName => $stockValue,
                    $verifiedByField => $userName,
                    $lastVerificationField => now()
                ]);

                // Crear registro en historial
                StockVerification::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'stock_anterior' => $stockAnterior ?? $product->stock,
                    'stock_verificado' => $stockValue,
                    'verificado_por' => $userName,
                    'tipo_verificacion' => $fieldNumber
                ]);

                DB::commit();

                // Recargar el producto para obtener los valores actualizados
                $product->refresh();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            $colorAttr = 'stock_color' . ($fieldNumber > 1 ? "_{$fieldNumber}" : '');
            $mensaje = $fieldNumber === 3 ? 'Stock Tienda actualizado correctamente' : "Stock {$fieldNumber} actualizado correctamente";

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                $colorAttr => $product->{$colorAttr},
                $verifiedByField => $product->{$verifiedByField},
                $lastVerificationField => $product->{$lastVerificationField}->format('d/m/Y H:i')
            ]);
        } catch (\Exception $e) {
            Log::error("Error en updateStock{$fieldNumber}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validación con mensajes personalizados
            $validated = $request->validate([
                'codigo' => 'required|string|max:100|unique:products,codigo',
                'producto' => 'required|string|max:500',
                'marca' => 'nullable|string|max:200',
                'costo' => 'required|numeric|min:0|max:999999.99',
                'precio_cliente' => 'required|numeric|min:0|max:999999.99',
                'stock' => 'required|integer|min:0|max:999999',
            ], [
                'codigo.unique' => 'Ya existe un producto con este código',
                'codigo.required' => 'El código es obligatorio',
                'producto.required' => 'El nombre del producto es obligatorio',
                'costo.required' => 'El costo es obligatorio',
                'precio_cliente.required' => 'El precio de cliente es obligatorio',
                'stock.required' => 'El stock es obligatorio',
            ]);

            // Limpiar y sanitizar datos antes de insertar
            $cleanData = [
                'codigo' => trim(preg_replace('/\s+/', '', $validated['codigo'])),
                'producto' => trim(preg_replace('/\s+/', ' ', $validated['producto'])),
                'marca' => isset($validated['marca']) ? trim(preg_replace('/\s+/', ' ', $validated['marca'])) : null,
                'costo' => round(floatval($validated['costo']), 2),
                'precio_cliente' => round(floatval($validated['precio_cliente']), 2),
                'stock' => intval($validated['stock']),
            ];

            // Usar transacción para garantizar integridad
            DB::beginTransaction();

            try {
                // Verificación adicional de duplicados
                $exists = Product::where('codigo', $cleanData['codigo'])->exists();
                if ($exists) {
                    throw new \Exception('El código ya existe en el sistema');
                }

                $product = Product::create($cleanData);

                DB::commit();

                Log::info('Producto creado exitosamente', ['product_id' => $product->id, 'codigo' => $product->codigo, 'user_id' => Auth::id()]);

                return redirect()->route('products.index')
                    ->with('success', 'Producto creado exitosamente');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación - mostrar al usuario
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error al crear producto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'data' => $request->except(['_token'])
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el producto: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Product $product)
    {
        try {
            $validated = $request->validate([
                'codigo' => 'required|string|max:100|unique:products,codigo,' . $product->id,
                'producto' => 'required|string|max:500',
                'marca' => 'nullable|string|max:200',
                'costo' => 'required|numeric|min:0|max:999999.99',
                'precio_cliente' => 'required|numeric|min:0|max:999999.99',
                'stock' => 'required|integer|min:0|max:999999',
            ], [
                'codigo.unique' => 'Ya existe otro producto con este código',
                'codigo.required' => 'El código es obligatorio',
                'producto.required' => 'El nombre del producto es obligatorio',
            ]);

            // Limpiar datos
            $cleanData = [
                'codigo' => trim(preg_replace('/\s+/', '', $validated['codigo'])),
                'producto' => trim(preg_replace('/\s+/', ' ', $validated['producto'])),
                'marca' => isset($validated['marca']) ? trim(preg_replace('/\s+/', ' ', $validated['marca'])) : null,
                'costo' => round(floatval($validated['costo']), 2),
                'precio_cliente' => round(floatval($validated['precio_cliente']), 2),
                'stock' => intval($validated['stock']),
            ];

            DB::beginTransaction();

            try {
                // Deshabilitar temporalmente los eventos del modelo
                // para evitar que se regenere automáticamente el nombre del producto
                Product::withoutEvents(function() use ($product, $cleanData) {
                    $product->update($cleanData);
                });

                DB::commit();

                Log::info('Producto actualizado', ['product_id' => $product->id, 'user_id' => Auth::id()]);

                return redirect()->route('products.index')
                    ->with('success', 'Producto actualizado exitosamente');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error al actualizar producto', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        try {
            DB::transaction(function () use ($product) {
                // Eliminar historial de verificaciones primero
                $product->stockVerifications()->delete();
                $product->delete();
            });

            return redirect()->route('products.index')
                ->with('success', 'Producto eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar producto: ' . $e->getMessage());
            return redirect()->route('products.index')
                ->with('error', 'Error al eliminar el producto');
        }
    }

    public function historyData(Product $product)
    {
        $verifications = $product->stockVerifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($verification) {
                return [
                    'stock_verificado' => $verification->stock_verificado,
                    'stock_anterior' => $verification->stock_anterior,
                    'verificado_por' => $verification->verificado_por,
                    'created_at' => $verification->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'product' => [
                'codigo' => $product->codigo,
                'producto' => $product->producto,
                'stock' => $product->stock,
            ],
            'verifications' => $verifications
        ]);
    }

    public function history(Product $product)
    {
        $history = $product->stockVerifications()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('products.history', compact('product', 'history'));
    }

    /**
     * Exportar solo los productos de la página actual (Codigo y Nombre en 2 columnas)
     */
    public function exportCurrentPage(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json(['error' => 'No hay productos para exportar'], 400);
            }

            // Obtener productos manteniendo el orden de los IDs recibidos
            $products = Product::whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                ->get(['codigo', 'producto']);

            // Crear CSV con BOM UTF-8 para mejor compatibilidad con Excel
            // BOM ayuda a Excel a reconocer el encoding UTF-8
            // Usar punto y coma (;) como separador para Excel en español
            $csv = "\xEF\xBB\xBF"; // BOM UTF-8

            // Agregar encabezados
            $csv .= "CODIGO;NOMBRE\n";

            // Agregar datos
            foreach ($products as $product) {
                // Escapar comillas dobles duplicándolas según estándar CSV
                $nombre = str_replace('"', '""', $product->producto);
                $codigo = str_replace('"', '""', $product->codigo);
                $csv .= '"' . $codigo . '";"' . $nombre . '"' . "\n";
            }

            // Retornar como descarga
            return response($csv)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="productos_pagina_' . date('Y-m-d_H-i-s') . '.csv"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('Error al exportar página actual: ' . $e->getMessage());
            return response()->json(['error' => 'Error al exportar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Importar nombres desde CSV (formato 2 columnas: CODIGO y NOMBRE) y detectar cambios
     */
    public function importCurrentPage(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:2048'
            ]);

            $file = $request->file('file');

            // Leer el contenido del archivo
            $content = file_get_contents($file->getRealPath());

            // Remover BOM UTF-8 si existe
            $content = str_replace("\xEF\xBB\xBF", '', $content);

            // Crear archivo temporal sin BOM
            $tempFile = tmpfile();
            $tempPath = stream_get_meta_data($tempFile)['uri'];
            file_put_contents($tempPath, $content);

            $handle = fopen($tempPath, 'r');

            // Leer y validar encabezado usando punto y coma como delimitador
            $header = fgetcsv($handle, 0, ';');
            if (!$header || count($header) < 2) {
                fclose($handle);
                throw new \Exception('El archivo debe tener 2 columnas: CODIGO y NOMBRE');
            }

            $updates = [];
            $updatedIds = [];
            $errors = [];
            $changes = [];

            // Leer cada fila del CSV usando punto y coma como delimitador
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if (count($row) < 2) {
                    continue;
                }

                $codigo = trim($row[0]);
                $newName = trim($row[1]);

                // Validar que el codigo no esté vacío
                if (empty($codigo)) {
                    continue;
                }

                // Buscar producto por codigo
                $product = Product::where('codigo', $codigo)->first();

                if (!$product) {
                    $errors[] = "CODIGO {$codigo} no encontrado";
                    continue;
                }

                // Verificar si hay cambio
                if ($product->producto !== $newName) {
                    $changes[] = [
                        'id' => $product->id,
                        'codigo' => $codigo,
                        'old_name' => $product->producto,
                        'new_name' => $newName
                    ];

                    // Actualizar nombre sin disparar eventos del modelo
                    // para evitar que se regenere automáticamente
                    Product::withoutEvents(function() use ($product, $newName) {
                        $product->producto = $newName;
                        $product->save();
                    });

                    // Registrar tanto el codigo como el id para destacar en la UI
                    $updates[] = $codigo;
                    $updatedIds[] = $product->id;
                }
            }

            fclose($handle);

            // (Removed) previously flashed updated IDs for UI highlighting

            return response()->json([
                'success' => true,
                'updated' => count($updates),
                'errors' => $errors,
                'changes' => $changes,
                'message' => count($updates) . ' productos actualizados correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al importar página actual: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al importar: ' . $e->getMessage()
            ], 500);
        }
    }
}
