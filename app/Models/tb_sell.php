<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_sell extends Model
{
    use HasFactory;
    protected $fillable = [
        'no_invoice',
        'date',
        'total_price',
        'store_id',
        'payment_amount',
        'customer_id'
    ];


    public function outgoing_goods()
    {
        return $this->hasMany(tb_outgoing_goods::class, 'sell_id');
    }

    public function store()
    {
        return $this->belongsTo(tb_stores::class, 'store_id');
    }

    public function customer()
    {
        return $this->belongsTo(tb_customers::class, 'customer_id');
    }
}
