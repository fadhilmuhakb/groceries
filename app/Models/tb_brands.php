<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_brands extends Model
{
    use HasFactory;
    protected $fillable = [
        'brand_name',
        'description'
    ];
}
