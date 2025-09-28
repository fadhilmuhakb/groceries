<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_products extends Model
{
    use HasFactory;

    protected $table = 'tb_products';

    protected $fillable = [
        'product_code',
        'product_name',
        'type_id',
        'brand_id',
        'unit_id',
        'purchase_price',
        'selling_price',
        'product_discount',
        'description',
        'uuid',
        'tier_prices',
    ];

    protected $casts = [
        'tier_prices' => 'array',
    ];

    // ===== RELATIONSHIPS (tambahkan ini) =====
    public function type()
    {
        // ganti class & foreign key sesuai model/kolom kamu
        return $this->belongsTo(tb_types::class, 'type_id');
    }

    public function brand()
    {
        return $this->belongsTo(tb_brands::class, 'brand_id');
    }

    public function unit()
    {
        return $this->belongsTo(tb_units::class, 'unit_id');
    }

    // ===== Helper harga tier (opsional kalau belum ada) =====
    public function unitPriceForQty(int $qty): float
    {
        $base = (float) $this->selling_price;

        $tiers = collect($this->tier_prices ?? [])
            ->mapWithKeys(fn($price, $q) => [(int)$q => (float)$price])
            ->sortKeys();

        $eligible = $tiers->filter(fn($price, $q) => $qty >= $q);

        return $eligible->isNotEmpty() ? (float)$eligible->last() : $base;
    }
}
