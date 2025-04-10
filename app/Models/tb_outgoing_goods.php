<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_outgoing_goods extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sell_id',
        'date',
        'quantity_out',
        'discount',
        'recorded_by',
        'description'
    ];


    public function product()
    {
        return $this->belongsTo(tb_products::class, 'product_id');
    }

    public function sell()
    {
        return $this->belongsTo(tb_sell::class, 'sell_id');
    }
}
