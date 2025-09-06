<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\SoftDeletes;
class tb_incoming_goods extends Model
{
    use HasFactory,Syncable,SoftDeletes;
    protected $fillable = ['purchase_id', 'product_id', 'stock', 'description','uuid'];



    public function product()
    {
        return $this->belongsTo(tb_products::class, 'product_id');
    }

    public function purchase()
    {
        return $this->belongsTo(tb_purchase::class, 'purchase_id');
    }


}
