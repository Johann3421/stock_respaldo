<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ProductoDescuento extends Model
{
    protected $table = 'productos_descuento';

    protected $fillable = [
        'codigo',
        'producto',
        'marca',
        'costo',
        'precio_cliente',
        'stock',
        'descuento_percent',
        'fecha_ingreso',
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'precio_cliente' => 'decimal:2',
        'descuento_percent' => 'decimal:2',
        'fecha_ingreso' => 'datetime',
    ];

    /**
     * Calcular descuento automático basado en días desde ingreso
     * < 60 DIAS: Usar precio_cliente (precio fijo)
     * >= 60 DIAS: Calcular basado en costo con margen/descuento
     * >= 60 Y < 90: +5% ganancia sobre costo
     * >= 90 Y < 120: Precio costo (0%)
     * >= 120 Y < 180: -3% descuento sobre costo
     * >= 180 Y < 210: -5% descuento sobre costo
     * >= 210: -15% descuento sobre costo
     */
    public function getDescuentoAutomaticoAttribute()
    {
        if (!$this->fecha_ingreso) {
            return 0;
        }

        $dias = $this->fecha_ingreso->diffInDays(Carbon::now());

        if ($dias < 60) {
            return null; // Especial: usar precio_cliente (no mostrar %)
        } elseif ($dias < 90) {
            return 5;   // +5% ganancia sobre costo
        } elseif ($dias < 120) {
            return 0;   // Precio costo
        } elseif ($dias < 180) {
            return -3;  // -3% descuento sobre costo
        } elseif ($dias < 210) {
            return -5;  // -5% descuento sobre costo
        }

        return -15; // -15% descuento para productos muy antiguos
    }

    /**
     * Obtener el precio final con descuento/margen automático aplicado
     * < 60 días: precio_cliente (precio fijo registrado)
     * >= 60 días: precio basado en costo + margen/descuento
     */
    public function getPrecioConDescuentoAttribute()
    {
        if (!$this->fecha_ingreso) {
            return null;
        }

        $dias = $this->fecha_ingreso->diffInDays(Carbon::now());

        // Productos nuevos: usar precio_cliente
        if ($dias < 60) {
            return (float) $this->precio_cliente;
        }

        // Productos antiguos: calcular basado en costo
        $descuento = $this->getDescuentoAutomaticoAttribute();
        $precio_base = (float) $this->costo;

        return round($precio_base * (1 + ($descuento / 100)), 2);
    }

    /**
     * Obtener días desde ingreso
     */
    public function getDiasDesdeIngresoAttribute()
    {
        if (!$this->fecha_ingreso) {
            return 0;
        }
        // Asegurar siempre número entero (sin decimales)
        return (int) $this->fecha_ingreso->diffInDays(Carbon::now());
    }
}
