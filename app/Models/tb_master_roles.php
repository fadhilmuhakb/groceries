<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TbMasterRole extends Model
{
    protected $table = 'tb_master_roles';
    protected $fillable = ['role_name', 'is_active'];

    public function menus()
    {
        return $this->belongsToMany(
            TbMasterMenus::class,
            'tb_master_menu_roles',
            'role_id',
            'menu_id'
        );
    }
}
