<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockVerification extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'stock_anterior',
        'stock_verificado',
        'verificado_por'
    ];

    protected $casts = [
        'stock_anterior' => 'integer',
        'stock_verificado' => 'integer'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
