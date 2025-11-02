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
    $roleName   = trim(strtolower($roleName));
    $allowedIds = \DB::table('tb_master_menu_roles')
        ->whereRaw('LOWER(role_name) = ?', [$roleName])
        ->pluck('menu_id')
        ->toArray();

    // Ambil semua menu aktif, dikelompokkan per parent
    $all = static::where('is_active', 1)->orderBy('id')->get()->groupBy('parent_id');

    $build = function ($parentId) use (&$build, $all, $allowedIds) {
        $items = $all[$parentId] ?? collect();

        return $items->map(function ($m) use ($build, $allowedIds) {
            // Bangun dulu anak-anaknya
            $children = $build($m->id);

            // Aturan tampil:
            // - Jika menu ini explicit diizinkan (id ada di pivot) => tampil
            // - Jika menu ini parent (menu_path NULL) => hanya tampil bila punya anak yang diizinkan
            $isSelfAllowed = in_array($m->id, $allowedIds, true);
            $isParent      = is_null($m->menu_path);
            $hasAllowedKid = $children->isNotEmpty();

            if ($isSelfAllowed || ($isParent && $hasAllowedKid)) {
                $m->setRelation('children', $children->values());
                return $m;
            }

            // Selain itu sembunyikan
            return null;
        })->filter()->values();
    };

    return $build(null);
}

}
