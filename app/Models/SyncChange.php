<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncChange extends Model
{
    protected $table = 'sync_changes';
    public $timestamps = false;

    protected $fillable = [
        'table',        // ganti ke 'table_name' jika skema kamu
        'row_uuid',
        'action',       // 'upsert' | 'delete'
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];
}
