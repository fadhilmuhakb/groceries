<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_daily_revenues extends Model
{
    use HasFactory;

    protected $table = 'tb_daily_revenues';

    protected $fillable = [
        'user_id',
        'date',
        'amount',
    ];
    
    // Relasi ke user jika dibutuhkan
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
