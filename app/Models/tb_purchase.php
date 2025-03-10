<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_purchase extends Model
{
    use HasFactory;
    protected $fillable = [
        'supplier_id',
        'total_price',
        'paid_date'
    ];
}
