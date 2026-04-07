<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    protected $fillable = [
        'codigo',
        'producto',
        'categoria',
        'subcategoria',
        'marca',
        'modelo',
        'caracteristicas',
        'numero_parte',
        'nombre_anterior',
        'costo',
        'precio_cliente',
        'stock',
        'stock_verificado',
        'verificado_por',
        'ultima_verificacion',
        'stock_verificado_2',
        'verificado_por_2',
        'ultima_verificacion_2',
        'stock_verificado_3',
        'verificado_por_3',
        'ultima_verificacion_3'
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'precio_cliente' => 'decimal:2',
        'stock' => 'integer',
        'stock_verificado' => 'integer',
        'stock_verificado_2' => 'integer',
        'stock_verificado_3' => 'integer',
        'ultima_verificacion' => 'datetime',
        'ultima_verificacion_2' => 'datetime',
        'ultima_verificacion_3' => 'datetime'
    ];

    /**
     * Boot del modelo - protege el campo 'producto' para que siempre se regenere automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        // Evento saving: se ejecuta antes de crear o actualizar
        static::saving(function ($product) {
            // Si hay cambios en los componentes del nombre, guardar backup
            if ($product->isDirty(['categoria', 'subcategoria', 'marca', 'modelo', 'caracteristicas', 'numero_parte'])) {
                if ($product->exists && !$product->nombre_anterior) {
                    $product->nombre_anterior = $product->getOriginal('producto');
                }
            }

            // Regenerar el nombre AUTOMÁTICAMENTE solo cuando corresponda:
            // - Si el modelo ya existe: regenerar solamente si los componentes cambiaron y
            //   el nombre anterior era el nombre generado automáticamente (no un nombre manual).
            // - Si es creación (no existe): regenerar solo si no se proporcionó un nombre manual.
            try {
                if ($product->exists) {
                    // Construir el nombre generado a partir de los valores ORIGINALES
                    $origParts = array_filter([
                        $product->getOriginal('categoria'),
                        $product->getOriginal('subcategoria'),
                        $product->getOriginal('marca'),
                        $product->getOriginal('modelo'),
                        $product->getOriginal('caracteristicas'),
                        $product->getOriginal('numero_parte')
                    ], function ($v) { return !is_null($v) && trim($v) !== ''; });

                    $origGenerated = 'PRODUCTO GENÉRICO';
                    if (!empty($origParts)) {
                        $mapped = array_map(function ($part) {
                            $part = trim($part);
                            $part = strtoupper($part);
                            $part = preg_replace('/\s+/', ' ', $part);
                            return $part;
                        }, $origParts);
                        $origGenerated = implode(' ', $mapped);
                    }

                    $originalProducto = $product->getOriginal('producto');

                    // Si los componentes cambiaron y el nombre original era el generado automáticamente,
                    // entonces regeneramos; si el usuario tenía un nombre personalizado, lo respetamos.
                    if ($product->isDirty(['categoria', 'subcategoria', 'marca', 'modelo', 'caracteristicas', 'numero_parte'])
                        && ($originalProducto === null || strtoupper($originalProducto) === strtoupper($origGenerated))) {
                        $product->producto = $product->generateName();
                    }
                    // en cualquier otro caso dejamos `producto` tal como lo envió el usuario
                } else {
                    // Nuevo registro: solo generar si no se proporcionó un nombre manual
                    if (!isset($product->producto) || trim($product->producto) === '') {
                        $product->producto = $product->generateName();
                    }
                }
            } catch (\Throwable $ex) {
                // En caso de cualquier error, no bloquear la operación: intentar generar nombre por seguridad
                $product->producto = $product->generateName();
            }
        });
    }

    /**
     * Genera el nombre del producto basado en su estructura
     * Formato: CATEGORÍA + SUBCATEGORÍA + MARCA + MODELO + CARACTERÍSTICAS + NÚMERO_PARTE
     *
     * @return string
     */
    public function generateName(): string
    {
        $parts = array_filter([
            $this->categoria,
            $this->subcategoria,
            $this->marca,
            $this->modelo,
            $this->caracteristicas,
            $this->numero_parte
        ], function ($value) {
            return !is_null($value) && trim($value) !== '';
        });

        // Si no hay componentes, usar genérico
        if (empty($parts)) {
            return 'PRODUCTO GENÉRICO';
        }

        // Limpiar y normalizar cada parte
        $parts = array_map(function ($part) {
            $part = trim($part);
            $part = strtoupper($part);
            // Eliminar espacios múltiples
            $part = preg_replace('/\s+/', ' ', $part);
            return $part;
        }, $parts);

        return implode(' ', $parts);
    }

    /**
     * Obtiene el nombre generado sin guardarlo
     * Útil para previsualización
     *
     * @return string
     */
    public function previewName(): string
    {
        return $this->generateName();
    }


    // Relaciones
    public function stockVerifications()
    {
        return $this->hasMany(StockVerification::class);
    }

    // Scopes
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        $searchClean = $this->removeAccents($search);

        return $query->where(function($q) use ($search, $searchClean) {
            $q->where('codigo', 'like', "%{$search}%")
              ->orWhere('producto', 'like', "%{$search}%")
              ->orWhere('marca', 'like', "%{$search}%")
              ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(producto, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE ?", ["%{$searchClean}%"])
              ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(marca, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE ?", ["%{$searchClean}%"]);
        });
    }

    public function scopeByStockColor(Builder $query, ?string $color): Builder
    {
        if (empty($color)) {
            return $query;
        }

        return match($color) {
            // Productos con discrepancia: V1 ≠ V2
            'discrepancy' => $query->where(function($q) {
                $q->whereRaw('(COALESCE(stock_verificado, 0) + COALESCE(stock_verificado_2, 0)) > 0')
                  ->whereRaw('COALESCE(stock_verificado, 0) != COALESCE(stock_verificado_2, 0)');
            }),
            // Productos inferiores: V1 = V2, y (V2 + V3) < Stock
            'danger' => $query->where(function($q) {
                $q->where(function($subQ) {
                    // V1 = V2 y (V2 + V3) < Stock (stock > 0)
                    $subQ->where('stock', '>', 0)
                         ->whereRaw('COALESCE(stock_verificado, 0) = COALESCE(stock_verificado_2, 0)')
                         ->whereRaw('(COALESCE(stock_verificado_2, 0) + COALESCE(stock_verificado_3, 0)) < stock')
                         ->whereRaw('NOT (COALESCE(stock_verificado, 0) = 0 AND COALESCE(stock_verificado_2, 0) = 0 AND COALESCE(stock_verificado_3, 0) = 0)');
                })->orWhere(function($subQ) {
                    // Stock = 0 pero con verificaciones (V1 = V2)
                    $subQ->where('stock', '=', 0)
                         ->whereRaw('COALESCE(stock_verificado, 0) = COALESCE(stock_verificado_2, 0)')
                         ->whereRaw('(COALESCE(stock_verificado, 0) + COALESCE(stock_verificado_2, 0) + COALESCE(stock_verificado_3, 0)) > 0');
                });
            }),
            // Productos completos: V1 = V2, y (V2 + V3) = Stock
            'success' => $query->where('stock', '>', 0)
                               ->whereRaw('COALESCE(stock_verificado, 0) = COALESCE(stock_verificado_2, 0)')
                               ->whereRaw('(COALESCE(stock_verificado_2, 0) + COALESCE(stock_verificado_3, 0)) = stock'),
            // Productos superiores: V1 = V2, y (V2 + V3) > Stock
            'warning' => $query->where('stock', '>', 0)
                               ->whereRaw('COALESCE(stock_verificado, 0) = COALESCE(stock_verificado_2, 0)')
                               ->whereRaw('(COALESCE(stock_verificado_2, 0) + COALESCE(stock_verificado_3, 0)) > stock'),
            // Productos pendientes: sin ninguna verificación
            'unverified' => $query->whereRaw('COALESCE(stock_verificado, 0) = 0')
                                  ->whereRaw('COALESCE(stock_verificado_2, 0) = 0')
                                  ->whereRaw('COALESCE(stock_verificado_3, 0) = 0'),
            default => $query
        };
    }

    // Accessors
    public function getStockStatusAttribute()
    {
        if ($this->stock_verificado === null) {
            return 'pending';
        }

        return match(true) {
            $this->stock_verificado < $this->stock => 'inferior',
            $this->stock_verificado == $this->stock => 'igual',
            default => 'superior'
        };
    }

    public function getStockColorAttribute()
    {
        return match($this->stock_status) {
            'inferior' => 'danger',
            'igual' => 'success',
            'superior' => 'warning',
            default => 'secondary'
        };
    }

    public function getStockStatus2Attribute()
    {
        if ($this->stock_verificado_2 === null) {
            return 'pending';
        }

        return match(true) {
            $this->stock_verificado_2 < $this->stock => 'inferior',
            $this->stock_verificado_2 == $this->stock => 'igual',
            default => 'superior'
        };
    }

    public function getStockColor2Attribute()
    {
        return match($this->stock_status_2) {
            'inferior' => 'danger',
            'igual' => 'success',
            'superior' => 'warning',
            default => 'secondary'
        };
    }

    public function getStockStatus3Attribute()
    {
        if ($this->stock_verificado_3 === null) {
            return 'pending';
        }

        return match(true) {
            $this->stock_verificado_3 < $this->stock => 'inferior',
            $this->stock_verificado_3 == $this->stock => 'igual',
            default => 'superior'
        };
    }

    public function getStockColor3Attribute()
    {
        return match($this->stock_status_3) {
            'inferior' => 'danger',
            'igual' => 'success',
            'superior' => 'warning',
            default => 'secondary'
        };
    }

    // Accessor para suma total de verificaciones V2 + V3 (nuevo algoritmo)
    public function getTotalVerificadoAttribute()
    {
        return ($this->stock_verificado_2 ?? 0) +
               ($this->stock_verificado_3 ?? 0);
    }

    // Verifica si hay discrepancia entre V1 y V2
    public function getHasDiscrepancyAttribute()
    {
        $v1 = $this->stock_verificado ?? 0;
        $v2 = $this->stock_verificado_2 ?? 0;

        // Solo hay discrepancia si al menos uno tiene valor y son diferentes
        if ($v1 > 0 || $v2 > 0) {
            return $v1 !== $v2;
        }

        return false;
    }

    // Accessor para estado general basado en el nuevo algoritmo
    public function getStockStatusGeneralAttribute()
    {
        $v1 = $this->stock_verificado ?? 0;
        $v2 = $this->stock_verificado_2 ?? 0;
        $v3 = $this->stock_verificado_3 ?? 0;

        // Si no hay verificaciones, es pendiente
        if ($v1 === 0 && $v2 === 0 && $v3 === 0) {
            return 'pending';
        }

        // Si V1 ≠ V2, hay discrepancia
        if (($v1 > 0 || $v2 > 0) && $v1 !== $v2) {
            return 'discrepancy';
        }

        // Si V1 = V2, calcular V2 + V3
        $total = $v2 + $v3;

        // Si tiene verificaciones pero stock es 0, es inferior (danger)
        if ($this->stock == 0 && $total > 0) {
            return 'inferior';
        }

        // Comparar (V2 + V3) vs stock
        return match(true) {
            $total < $this->stock => 'inferior',
            $total == $this->stock => 'igual',
            default => 'superior'
        };
    }

    // Accessor para color general basado en el nuevo algoritmo
    public function getStockColorGeneralAttribute()
    {
        return match($this->stock_status_general) {
            'discrepancy' => 'info',
            'inferior' => 'danger',
            'igual' => 'success',
            'superior' => 'warning',
            default => 'secondary'
        };
    }

    // Helpers privados
    private function removeAccents($string)
    {
        $string = strtolower($string);
        $replacements = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'ñ' => 'n', 'Ñ' => 'n'
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $string);
    }
}
