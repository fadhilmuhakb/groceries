<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;


class tb_master_menus extends Model

{
    use HasFactory, HasRecursiveRelationships;
    protected $fillable = [
        'menu_name',
        'menu_path',
        'menu_icon',
        'parent_id'
    ];


    public function getParentKeyName()
    {
        return 'parent_id';
    }

    public function getLocalKeyName()
    {
        return 'id';
    }

    public function getDepthName()
    {
        return 'depth';
    }

    public function getPathName()
    {
        return 'path';
    }
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function menu_roles()
    {
        return $this->hasMany(tb_master_menu_roles::class, 'menu_id');
    }
}
