<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductoDescuento;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DiscountsController extends Controller
{
    /**
     * Mostrar listado de descuentos de productos.
     */
    public function index(Request $request)
    {
        $query = ProductoDescuento::select('id','codigo','producto','marca','costo','precio_cliente','stock','descuento_percent','fecha_ingreso');

        // Búsqueda simple
        $search = $request->get('q');
        if (!empty($search)) {
            $normalized = str_replace(' ', '', mb_strtolower($search));

            $query->where(function ($q) use ($normalized) {
                $q->whereRaw("REPLACE(LOWER(codigo), ' ', '') LIKE ?", ["%{$normalized}%"])
                  ->orWhereRaw("REPLACE(LOWER(producto), ' ', '') LIKE ?", ["%{$normalized}%"])
                  ->orWhereRaw("REPLACE(LOWER(marca), ' ', '') LIKE ?", ["%{$normalized}%"]);
            });
        }

        $products = $query->orderBy('producto')->paginate(25)->appends($request->only('q'));

        // Calcular campo derivado `precio_con_descuento`
        $products->getCollection()->transform(function ($p) {
            if (!is_null($p->descuento_percent) && $p->descuento_percent > 0) {
                $p->precio_con_descuento = $p->precio_cliente * (1 - $p->descuento_percent / 100);
            } else {
                $p->precio_con_descuento = null;
            }
            return $p;
        });

        return view('discounts.index', compact('products'));
    }

    /**
     * Importar productos (crear/actualizar en tabla productos_descuento).
     */
    public function importProducts(Request $request)
    {
        Log::info('DiscountsController@importProducts called', ['user_id' => Auth::id()]);
        $request->validate([
            'file' => 'required|file|max:10240'
        ]);

        Log::info('Uploaded file info', [
            'original_name' => $request->file('file')->getClientOriginalName(),
            'client_mime' => $request->file('file')->getClientMimeType(),
            'client_ext' => $request->file('file')->getClientOriginalExtension(),
            'size' => $request->file('file')->getSize(),
        ]);

        $file = $request->file('file');

        // Leer contenido del primer sheet como array.
        $rows = [];
        try {
            $sheets = Excel::toArray([], $file);
            if (!empty($sheets) && isset($sheets[0]) && count($sheets[0]) > 0) {
                $rows = $sheets[0];
            }
        } catch (\Exception $e) {
            Log::warning('Excel reader failed, attempting HTML fallback', ['msg' => $e->getMessage()]);

            $contents = @file_get_contents($file->getRealPath());
            if ($contents !== false && (stripos($contents, '<table') !== false || stripos($contents, '<html') !== false)) {
                libxml_use_internal_errors(true);
                $doc = new \DOMDocument();
                $doc->loadHTML($contents);
                libxml_clear_errors();

                $tables = $doc->getElementsByTagName('table');
                if ($tables->length > 0) {
                    foreach ($tables as $t) {
                        foreach ($t->getElementsByTagName('tr') as $tr) {
                            $cells = [];
                            foreach ($tr->getElementsByTagName('th') as $th) {
                                $cells[] = trim($th->textContent);
                            }
                            if (count($cells) === 0) {
                                foreach ($tr->getElementsByTagName('td') as $td) {
                                    $cells[] = trim($td->textContent);
                                }
                            }
                            if (count($cells) > 0) {
                                $rows[] = $cells;
                            }
                        }
                        if (count($rows) > 0) break;
                    }
                }
            } else {
                return back()->with('error', 'No se pudo leer el archivo: ' . $e->getMessage());
            }
        }

        if (empty($rows) || count($rows) === 0) {
            return back()->with('error', 'El archivo no contiene datos legibles (hoja vacía o formato no soportado).');
        }

        // Normalizador robusto + limpiador de caracteres corrompidos
        $normalize = function ($s) {
            $s = (string) $s;
            $s = trim($s);
            $s = preg_replace('/[\x{FEFF}\x{00A0}\p{Zs}\s]+/u', '', $s);
            if (!mb_check_encoding($s, 'UTF-8')) {
                $s = iconv('ISO-8859-1', 'UTF-8//IGNORE', $s);
            }
            $s = mb_strtolower($s);
            $trans = ["á"=>"a","é"=>"e","í"=>"i","ó"=>"o","ú"=>"u","ã"=>"a","ñ"=>"n"];
            return strtr($s, $trans);
        };

        // Limpiador simple y robusto de caracteres acentuados
        $cleanData = function ($s) {
            $s = (string) $s;
            $s = trim($s);

            // PASO 1: Limpiar mojibake UTF-8/ISO-8859-1 PRIMERO (antes de tratar otras cosas)
            // Las secuencias mojibake como Ã" deben mapearse primero
            // También incluir variantes con comillas Unicode (""'' etc)
            $mojibake_replacements = array(
                'Ã"' => 'O',  'ã"' => 'o',    // Ó/ó (ASCII quote)
                "Ã\u{201C}" => 'O',  "ã\u{201C}" => 'o',    // Ó/ó (Unicode LEFT double quote U+201C)
                "Ã\u{201D}" => 'O',  "ã\u{201D}" => 'o',    // Ó/ó (Unicode RIGHT double quote U+201D)
                'Ã©' => 'E',  'Ã¡' => 'A',    // é/á
                'Ã­' => 'I',  'Ã³' => 'O',    // í/ó
                'Ãº' => 'U',  'ã©' => 'e',    // ú
                'Ã€' => 'A',  'ÃŠ' => 'E',    // À/È
                'ÃŒ' => 'I',  'Ã' => 'O',     // Ì/Ò
                'Ã™' => 'U',  'Ã§' => 'C',    // Ù/ç
                'Ã±' => 'N',  'ã§' => 'c',    // ñ
                'Ã ' => 'A',  'ã ' => 'a',    // à
                'Ã' => 'A',   'ã' => 'a',     // Catch-all Ã/ã
                'â€' => '',   'Â' => '',      // Limpiar basura
            );
            $s = strtr($s, $mojibake_replacements);

            // PASO 2: Eliminar comillas incrustadas en palabras (después de mojibake limpio)
            $s = preg_replace('/([A-Za-z])["\']([A-Za-z])/u', '$1$2', $s);
            $s = preg_replace('/([A-Za-z])["\'`]/u', '$1', $s);

            // PASO 3: Convertir acentos UTF-8 correctos
            $s = strtr($s, array(
                'Á'=>'A', 'À'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'AE',
                'á'=>'a', 'à'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'ae',
                'É'=>'E', 'È'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e',
                'Í'=>'I', 'Ì'=>'I', 'Î'=>'I', 'Ï'=>'I', 'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i',
                'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'ó'=>'o', 'ò'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o',
                'Ú'=>'U', 'Ù'=>'U', 'Û'=>'U', 'Ü'=>'U', 'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u',
                'Ñ'=>'N', 'ñ'=>'n',
                'Ç'=>'C', 'ç'=>'c',
                'Ý'=>'Y', 'ý'=>'y',
                'Þ'=>'TH', 'þ'=>'th',
                'ß'=>'ss'
            ));

            // PASO 4: Remover comillas ASCII y caracteres de control
            $s = preg_replace('/[\x00-\x1f\x7f"\'`]/u', '', $s);

            // PASO 5: Remover Unicode especial (per mille, daggers, etc)
            $s = preg_replace('/[\xe2\x80\x80-\xe2\x80\xbf]/u', '', $s);

            // PASO 6: Remover cualquier carácter no-alfanumérico restante
            $s = preg_replace('/[^A-Za-z0-9\s\-\.,]/u', '', $s);

            // PASO 7: Normalizar espacios
            $s = preg_replace('/\s+/', ' ', $s);

            return strtoupper(trim($s));
        };

        // Detectar encabezado
        $first = $rows[0];
        $hasHeader = false;
        $colMap = ['codigo'=>0, 'producto'=>1, 'marca'=>2, 'costo'=>3, 'precio'=>4, 'stock'=>5];

        if (is_array($first)) {
            $flat = array_map($normalize, $first);
            Log::info('Header row normalized', ['headers' => $flat]);

            foreach ($flat as $i => $val) {
                if (strpos($val, 'codigo') !== false) $colMap['codigo'] = $i;
                if (strpos($val, 'producto') !== false) $colMap['producto'] = $i;
                if (strpos($val, 'marca') !== false) $colMap['marca'] = $i;
                if (strpos($val, 'costo') !== false || strpos($val, 'cost') !== false) $colMap['costo'] = $i;
                if (strpos($val, 'precio') !== false || strpos($val, 'preciocliente') !== false) $colMap['precio'] = $i;
                if (strpos($val, 'stock') !== false) $colMap['stock'] = $i;
            }

            Log::info('Column mapping', $colMap);

            if (preg_grep('/^codigo|^producto|^marca|^costo|^precio|^stock/', $flat)) {
                $hasHeader = true;
                array_shift($rows);
            }
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $preview = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $rIndex => $row) {
                if (!is_array($row)) continue;

                $codigo = trim((string)($row[$colMap['codigo']] ?? ''));
                if ($codigo === '') continue;

                if (count($preview) < 10) {
                    $preview[] = ['row' => $row, 'codigo_raw' => $codigo];
                }

                $data = [
                    'codigo' => $codigo,
                    'producto' => $cleanData($row[$colMap['producto']] ?? ''),
                    'marca' => $cleanData($row[$colMap['marca']] ?? ''),
                ];

                Log::info('Cleaned data for row', ['row' => $rIndex, 'producto_raw' => ($row[$colMap['producto']] ?? ''), 'producto_clean' => $data['producto'], 'marca_raw' => ($row[$colMap['marca']] ?? ''), 'marca_clean' => $data['marca']]);

                // Costo
                $costoRaw = $row[$colMap['costo']] ?? null;
                if (!is_null($costoRaw) && $costoRaw !== '') {
                    // Remover prefijo "S/." y espacios
                    $costoStr = (string) $costoRaw;
                    $costoStr = str_replace(['S/.', 's/.', ' '], '', $costoStr);
                    $costoStr = str_replace(',', '.', $costoStr); // Convertir , a .
                    $costo = filter_var($costoStr, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $costo = $costo === '' ? null : (float) $costo;
                    if (!is_null($costo) && $costo > 0) {
                        $data['costo'] = $costo;
                        Log::info('Parsed costo', ['raw' => $costoRaw, 'cleaned' => $costoStr, 'parsed' => $costo, 'row' => $rIndex]);
                    }
                }

                // Precio
                $priceRaw = $row[$colMap['precio']] ?? null;
                if (!is_null($priceRaw) && $priceRaw !== '') {
                    // Remover prefijo "S/." y espacios
                    $priceStr = (string) $priceRaw;
                    $priceStr = str_replace(['S/.', 's/.', ' '], '', $priceStr);
                    $priceStr = str_replace(',', '.', $priceStr); // Convertir , a .
                    $precio = filter_var($priceStr, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $precio = $precio === '' ? null : (float) $precio;
                    if (!is_null($precio) && $precio > 0) {
                        $data['precio_cliente'] = $precio;
                        Log::info('Parsed precio', ['raw' => $priceRaw, 'cleaned' => $priceStr, 'parsed' => $precio, 'row' => $rIndex]);
                    }
                }

                // Stock (SIEMPRE intentar guardar, incluso si es 0)
                $stockRaw = $row[$colMap['stock']] ?? null;
                Log::info('Stock raw value', ['raw' => $stockRaw, 'row' => $rIndex, 'col' => $colMap['stock']]);
                if (!is_null($stockRaw) && $stockRaw !== '') {
                    $stock = (int) filter_var($stockRaw, FILTER_SANITIZE_NUMBER_INT);
                    $data['stock'] = $stock;
                    Log::info('Stock assigned to data', ['raw' => $stockRaw, 'parsed' => $stock, 'row' => $rIndex]);
                }

                Log::info('Data to be saved for row', ['row' => $rIndex, 'data' => $data]);

                // Si el producto es nuevo, asignar fecha de ingreso por defecto a 01-01-2026
                $existing = ProductoDescuento::where('codigo', $codigo)->exists();
                if (!$existing) {
                    $data['fecha_ingreso'] = '2026-01-01 00:00:00';
                }

                $prod = ProductoDescuento::updateOrCreate(['codigo' => $codigo], $data);
                if ($prod->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing products', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }

        $summary = [
            'type' => 'import_productos',
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'preview' => $preview,
        ];

        return back()->with('import_summary', $summary);
    }

    /**
     * Importar Excel para actualizar precios.
     */
    public function updatePrices(Request $request)
    {
        Log::info('DiscountsController@updatePrices called', ['user_id' => Auth::id()]);
        $request->validate([
            'file' => 'required|file|max:10240'
        ]);

        Log::info('Uploaded file info', [
            'original_name' => $request->file('file')->getClientOriginalName(),
            'client_mime' => $request->file('file')->getClientMimeType(),
            'client_ext' => $request->file('file')->getClientOriginalExtension(),
            'size' => $request->file('file')->getSize(),
        ]);

        $file = $request->file('file');

        // Leer contenido del primer sheet como array
        $rows = [];
        try {
            $sheets = Excel::toArray([], $file);
            if (!empty($sheets) && isset($sheets[0]) && count($sheets[0]) > 0) {
                $rows = $sheets[0];
            }
        } catch (\Exception $e) {
            Log::warning('Excel reader failed, attempting HTML fallback', ['msg' => $e->getMessage()]);

            $contents = @file_get_contents($file->getRealPath());
            if ($contents !== false && (stripos($contents, '<table') !== false || stripos($contents, '<html') !== false)) {
                libxml_use_internal_errors(true);
                $doc = new \DOMDocument();
                $doc->loadHTML($contents);
                libxml_clear_errors();

                $tables = $doc->getElementsByTagName('table');
                if ($tables->length > 0) {
                    foreach ($tables as $t) {
                        foreach ($t->getElementsByTagName('tr') as $tr) {
                            $cells = [];
                            foreach ($tr->getElementsByTagName('th') as $th) {
                                $cells[] = trim($th->textContent);
                            }
                            if (count($cells) === 0) {
                                foreach ($tr->getElementsByTagName('td') as $td) {
                                    $cells[] = trim($td->textContent);
                                }
                            }
                            if (count($cells) > 0) {
                                $rows[] = $cells;
                            }
                        }
                        if (count($rows) > 0) break;
                    }
                }
            } else {
                return back()->with('error', 'No se pudo leer el archivo: ' . $e->getMessage());
            }
        }

        if (empty($rows) || count($rows) === 0) {
            return back()->with('error', 'El archivo no contiene datos legibles (hoja vacía o formato no soportado).');
        }

        // Normalizador robusto
        $normalize = function ($s) {
            $s = (string) $s;
            $s = trim($s);
            $s = preg_replace('/[\x{FEFF}\x{00A0}\p{Zs}\s]+/u', '', $s);
            $s = mb_strtolower($s);
            $trans = ["á"=>"a","é"=>"e","í"=>"i","ó"=>"o","ú"=>"u","ñ"=>"n"];
            return strtr($s, $trans);
        };

        // Detectar encabezado
        $first = $rows[0];
        $hasHeader = false;
        $colMap = ['codigo'=>0, 'costo'=>1, 'precio'=>2];

        if (is_array($first)) {
            $flat = array_map($normalize, $first);
            foreach ($flat as $i => $val) {
                if (strpos($val, 'codigo') !== false) $colMap['codigo'] = $i;
                if (strpos($val, 'costo') !== false || strpos($val, 'cost') !== false) $colMap['costo'] = $i;
                if (strpos($val, 'precio') !== false || strpos($val, 'preciocliente') !== false || strpos($val, 'precio_venta') !== false) $colMap['precio'] = $i;
            }

            if (preg_grep('/^codigo/', $flat) || preg_grep('/^costo/', $flat) || preg_grep('/^precio/', $flat)) {
                $hasHeader = true;
                array_shift($rows);
            }
        }

        $updated = 0;
        $updatedCosto = 0;
        $updatedPrecio = 0;
        $matched = 0;
        $notFound = 0;
        $errors = [];
        $preview = [];

        // Construir índice en memoria
        $allProducts = ProductoDescuento::select('id','codigo','costo','precio_cliente')->get();
        $productIndex = [];
        $productDigitsIndex = [];
        foreach ($allProducts as $p) {
            $pnorm = $normalize($p->codigo);
            if (!isset($productIndex[$pnorm])) {
                $productIndex[$pnorm] = $p;
            }
            $pdigits = preg_replace('/\D+/', '', $pnorm);
            if ($pdigits !== '' && !isset($productDigitsIndex[$pdigits])) {
                $productDigitsIndex[$pdigits] = $p;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($rows as $rIndex => $row) {
                if (!is_array($row)) continue;

                $codigo = trim((string)($row[$colMap['codigo']] ?? ''));
                if ($codigo === '') continue;

                $searchCodigo = $normalize($codigo);

                if (count($preview) < 10) {
                    $preview[] = ['row' => $row, 'codigo_raw' => $codigo, 'search' => $searchCodigo];
                }

                // 0) Buscar en índice en memoria
                $product = $productIndex[$searchCodigo] ?? null;
                $foundBy = $product ? 'map_exact' : null;

                // si no encontrado, intentar por dígitos
                if (!$product) {
                    $digits = preg_replace('/\D+/', '', $searchCodigo);
                    if ($digits !== '' && isset($productDigitsIndex[$digits])) {
                        $product = $productDigitsIndex[$digits];
                        $foundBy = 'map_digits';
                    }
                }

                if (!$product) {
                    $notFound++;
                    continue;
                }

                $matched++;
                $didUpdate = false;

                // Costo
                $costoRaw = $row[$colMap['costo']] ?? null;
                if (!is_null($costoRaw) && $costoRaw !== '') {
                    // Remover prefijo "S/." y espacios
                    $costoStr = (string) $costoRaw;
                    $costoStr = str_replace(['S/.', 's/.', ' '], '', $costoStr);
                    $costoStr = str_replace(',', '.', $costoStr);
                    $costo = filter_var($costoStr, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $costo = $costo === '' ? null : (float) $costo;
                    if (is_null($costo) || $costo <= 0) {
                        $errors[] = "Fila " . ($rIndex+1) . ": costo inválido";
                    } else {
                        $product->costo = $costo;
                        $updatedCosto++;
                        $didUpdate = true;
                    }
                }

                // Precio
                $priceRaw = $row[$colMap['precio']] ?? null;
                if (!is_null($priceRaw) && $priceRaw !== '') {
                    // Remover prefijo "S/." y espacios
                    $priceStr = (string) $priceRaw;
                    $priceStr = str_replace(['S/.', 's/.', ' '], '', $priceStr);
                    $priceStr = str_replace(',', '.', $priceStr);
                    $precio = filter_var($priceStr, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $precio = $precio === '' ? null : (float) $precio;
                    if (is_null($precio)) {
                        $errors[] = "Fila " . ($rIndex+1) . ": precio inválido";
                    } else {
                        $product->precio_cliente = $precio;
                        $updatedPrecio++;
                        $didUpdate = true;
                    }
                }

                if ($didUpdate) {
                    $product->save();
                    $updated++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }

        $summary = [
            'type' => 'update_precios',
            'matched' => $matched,
            'updated' => $updated,
            'updated_costo' => $updatedCosto,
            'updated_precio' => $updatedPrecio,
            'not_found' => $notFound,
            'errors' => $errors,
            'preview' => $preview,
        ];

        return back()->with('import_summary', $summary);
    }

    /**
     * Eliminar todos los productos de la tabla.
     */
    public function deleteAll()
    {
        ProductoDescuento::truncate();
        Log::info('All products deleted from productos_descuento table', ['user_id' => Auth::id()]);

        return back()->with('success', '✓ Todos los productos han sido eliminados. Ahora puedes importar los productos actualizados.');
    }

    /**
     * Actualizar fecha de ingreso de un producto
     */
    public function updateFecha(Request $request, $id)
    {
        $request->validate([
            'fecha_ingreso' => 'required|date_format:Y-m-d'
        ]);

        try {
            $producto = ProductoDescuento::findOrFail($id);
            $producto->update([
                'fecha_ingreso' => $request->fecha_ingreso . ' 00:00:00'
            ]);

            Log::info('Fecha de ingreso actualizada', [
                'producto_id' => $id,
                'nueva_fecha' => $request->fecha_ingreso,
                'user_id' => Auth::id()
            ]);

            return back()->with('success', '✓ Fecha de ingreso actualizada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar fecha', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al actualizar la fecha: ' . $e->getMessage());
        }
    }
}
