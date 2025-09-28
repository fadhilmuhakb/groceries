<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\SoftDeletes;
class tb_types extends Model
{
    use HasFactory, SoftDeletes, Syncable;
    protected $fillable = [
        'type_name',
        'description',
        'uuid'
    ];
}
