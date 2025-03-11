<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_incoming_goods extends Model
{
    use HasFactory;
    protected $fillable = ['purchase_id', 'product_id', 'stock', 'description'];



    public function product()
    {
        return $this->belongsTo(tb_products::class, 'product_id');
    }

  
}
