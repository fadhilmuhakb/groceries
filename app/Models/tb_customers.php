<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_customers extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'store_id',
        'phone_number'
    ];

    public function store()
    {
        return $this->belongsTo(tb_stores::class, 'store_id');
    }
}
