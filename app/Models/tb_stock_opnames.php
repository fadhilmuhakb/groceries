<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_stock_opnames extends Model
{
    use HasFactory;

    protected $table = 'tb_stock_opnames';

    protected $fillable = [
        'product_id',
        'store_id',
        'physical_quantity',
    ];
}
