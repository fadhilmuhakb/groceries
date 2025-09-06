<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SyncChange extends Model
{
  protected $fillable = ['table','row_uuid','op','payload','changed_at','device_id'];
  protected $casts = ['payload' => 'array','changed_at' => 'datetime'];
}
