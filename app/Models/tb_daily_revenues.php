<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\SoftDeletes;
class tb_daily_revenues extends Model
{
    use HasFactory,Syncable,SoftDeletes;

    protected $table = 'daily_revenues';

    protected $fillable = [
        'user_id',
        'date',
        'amount',
        'uuid'
    ];
    
    // Relasi ke user jika dibutuhkan
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
