<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatrimonioItem extends Model
{
    protected $fillable = [
        'area',
        'piso',
        'codigo_patrimonial',
        'descripcion',
        'marca',
        'modelo',
        'serie',
        'estado',
        'valor_adquisicion',
        'fecha_adquisicion',
        'responsable',
        'observaciones',
        'user_id'
    ];

    protected $casts = [
        'valor_adquisicion' => 'decimal:2',
        'fecha_adquisicion' => 'date',
        'piso' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
