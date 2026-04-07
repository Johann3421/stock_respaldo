<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaDiseno extends Model
{
    protected $table = 'area_diseno';

    protected $fillable = [
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
        'user_id',
        'closed_at',
        'closed_by_user_id',
    ];

    protected $casts = [
        'fecha_adquisicion' => 'date',
        'valor_adquisicion' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }
}
