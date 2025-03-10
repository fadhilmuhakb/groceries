<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_products extends Model
{
    use HasFactory;

    protected $table = 'tb_products';

    protected $fillable = [
        'product_code', 'product_name', 'type_id', 
        'brand_id', 'unit_id', 'purchase_price', 
        'selling_price', 'description'
    ];

    
    public function type()
    {
        return $this->belongsTo(tb_types::class, 'type_id');
    }

    
    public function brand()
    {
        return $this->belongsTo(tb_brands::class, 'brand_id');
    }

    
    public function unit()
    {
        return $this->belongsTo(tb_units::class, 'unit_id');
    }
}
