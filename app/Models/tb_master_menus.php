<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class tb_master_menus extends Model
{
    protected $table = 'tb_master_menuses';
    protected $fillable = ['menu_name','menu_path','menu_icon','parent_id','is_active'];

    public function parent() { return $this->belongsTo(self::class,'parent_id'); }
    public function children(){ return $this->hasMany(self::class,'parent_id')->orderBy('id'); }

    /**
     * Ambil tree menu aktif yang diizinkan utk role tertentu (berdasar users.roles string)
     */
 public static function treeAllowedForRoleName(string $roleName)
{
    $roleName = strtolower(trim($roleName));

    $allowedIds = \DB::table('tb_master_menu_roles')
        ->whereRaw('LOWER(TRIM(role_name)) = ?', [$roleName])
        ->pluck('menu_id')
        ->map(fn($v) => (int) $v)
        ->all();

    $allowSet = array_flip($allowedIds);

    $all = static::where('is_active', 1)
        // ->orderBy('sort')
        ->orderBy('id')
        ->get()
        ->groupBy('parent_id');

    $build = function ($parentId) use (&$build, $all, $allowSet) {
        $items = $all[$parentId] ?? collect();

        return $items->map(function ($m) use ($build, $allowSet) {
            $children = $build($m->id);

            $isSelfAllowed = isset($allowSet[(int) $m->id]);
            $isParent      = is_null($m->menu_path);
            $hasAllowedKid = $children->isNotEmpty();

            if ($isSelfAllowed || ($isParent && $hasAllowedKid)) {
                $m->setRelation('children', $children->values());
                return $m;
            }
            return null;
        })->filter()->values();
    };

    return $build(null);
}


}
