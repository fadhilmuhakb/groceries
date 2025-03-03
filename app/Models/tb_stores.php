<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_stores extends Model
{
    use HasFactory;
    protected $fillable = [
        'store_address',
        'store_name'
    ];
}
