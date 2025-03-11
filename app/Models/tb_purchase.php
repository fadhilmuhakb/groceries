<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_purchase extends Model
{
    use HasFactory;

    protected $table = 'tb_purchases';

    protected $fillable = ['supplier_id', 'store_id', 'total_price'];

    /**
     * Relasi dengan tb_supplier
     */
    public function supplier()
    {
        return $this->belongsTo(tb_suppliers::class, 'supplier_id', 'id');
    }
    public function store()
    {
        return $this->belongsTo(tb_stores::class, 'store_id', 'id');
    }
    public function incomingGoods()
{
    return $this->hasMany(tb_incoming_goods::class, 'purchase_id', 'id');
}

}
