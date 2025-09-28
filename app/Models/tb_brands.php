<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\SoftDeletes;
class tb_brands extends Model
{
    use HasFactory,Syncable,SoftDeletes;
    protected $fillable = [
        'brand_name',
        'description',
        'uuid'
    ];
}
