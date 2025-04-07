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
        'payment_amount'  
    ];


    public function outgoing_goods()
    {
        return $this->hasMany(tb_outgoing_goods::class, 'sell_id');
    }
}
