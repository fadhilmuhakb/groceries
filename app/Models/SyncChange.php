<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncChange extends Model
{
    protected $table = 'sync_changes';
    public $timestamps = false;

    // pakai guarded agar fleksibel (kolom action/op bisa berbeda)
    protected $guarded = [];

    protected $casts = [
        'changed_at' => 'datetime',
    ];
}
