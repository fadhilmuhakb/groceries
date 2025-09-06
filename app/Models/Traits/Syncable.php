<?php
namespace App\Models\Traits;

use Illuminate\Support\Str;
use App\Models\SyncChange;

trait Syncable
{
  public static function bootSyncable()
  {
    static::creating(function ($model) {
      if (empty($model->uuid)) $model->uuid = (string) Str::uuid();
    });

    $log = function ($model, string $op) {
      $payload = $op === 'upsert' ? $model->toArray() : null;
      SyncChange::create([
        'table'      => $model->getTable(),
        'row_uuid'   => $model->uuid,
        'op'         => $op,
        'payload'    => $payload,
        'changed_at' => $model->updated_at ?? now(),
        'device_id'  => request()->header('X-Device-Id') // opsional
      ]);
    };

    static::created(fn ($m) => $log($m, 'upsert'));
    static::updated(fn ($m) => $log($m, 'upsert'));
    static::deleted(fn ($m) => $log($m, 'delete'));
    static::restored(fn ($m) => $log($m, 'upsert')); // soft delete cancelled
  }
}
