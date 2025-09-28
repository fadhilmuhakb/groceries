<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncChange extends Model
{
    protected $table = 'sync_changes';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'changed_at' => 'datetime',
        'payload'    => 'array',   // ⬅️ saat dibaca jadi array (jika kolom ada & JSON)
    ];
}
