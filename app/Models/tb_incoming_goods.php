<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_incoming_goods extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'supplier_id',
        'store_id',
        'stock',
        'type',
        'description',
        'paid_of_date'
    ];


    public function product()
    {
        return $this->belongsTo(tb_products::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(tb_suppliers::class, 'supplier_id');
    }

    public function store()
    {
        return $this->belongsTo(tb_stores::class, 'store_id');
    }
}
