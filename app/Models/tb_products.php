<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_products extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_code',
        'product_name',
        'type_id',
        'brand_id',
        'unit_id',
        'description'
    ];
}
