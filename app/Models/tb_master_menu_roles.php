<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tb_master_menu_roles extends Model
{
    protected $table = 'tb_master_menu_roles';
    protected $fillable = ['menu_id', 'role_id'];

    public function menu()
    {
        return $this->belongsTo(tb_master_menus::class, 'menu_id');
    }

    public function role()
    {
        return $this->belongsTo(tb_master_roles::class, 'role_id');
    }
}
