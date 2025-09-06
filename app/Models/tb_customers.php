<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\SoftDeletes;
class tb_customers extends Model
{
    use HasFactory,Syncable,SoftDeletes;

    protected $fillable = [
        'customer_name',
        'store_id',
        'phone_number',
        'uuid'
    ];

    public function store()
    {
        return $this->belongsTo(tb_stores::class, 'store_id');
    }
}
