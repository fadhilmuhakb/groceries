<?php

namespace App\Models;

use App\Http\Controllers\TbMasterMenusController;
use App\Http\Controllers\TbMasterRolesController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_master_menu_roles extends Model
{
    use HasFactory;
    protected $fillable = [
        'menu_id',
        'role_id'
    ];

    public function menus()
    {
        return $this->hasMany(TbMasterMenusController::class, 'menu_id');
    }

    public function roles()
    {
        return $this->hasMany(TbMasterRolesController::class, 'role_id');
    }
}
