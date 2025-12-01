<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_product_store_price extends Model
{
    use HasFactory;

    protected $table = 'tb_product_store_prices';

    protected $fillable = [
        'product_id',
        'store_id',
        'purchase_price',
        'selling_price',
        'product_discount',
        'tier_prices',
    ];

    protected $casts = [
        'tier_prices' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(tb_products::class, 'product_id');
    }

    public function store()
    {
        return $this->belongsTo(tb_stores::class, 'store_id');
    }
}
