<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\SoftDeletes;
class tb_suppliers extends Model
{
    use HasFactory, SoftDeletes, Syncable;
    protected $fillable = [
        'code',
        'name',
        'address',
        'city',
        'province',
        'phone_number',
        'uuid'
    ];
}
