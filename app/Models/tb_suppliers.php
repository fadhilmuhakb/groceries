<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_suppliers extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'address',
        'city',
        'province',
        'phone_number'
    ];
}
