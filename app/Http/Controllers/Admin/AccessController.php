<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TbMasterMenus;
use App\Models\TbMasterRole;
use Illuminate\Http\Request;

class AccessController extends Controller
{
    public function index(Request $r)
    {
        $roles = TbMasterRole::where('is_active',1)->orderBy('role_name')->get();
        $role  = $roles->firstWhere('role_name', $r->query('role')) ?? $roles->first();

        $menus = TbMasterMenus::orderBy('id')->get();
        $allowed = $role ? $role->menus()->pluck('tb_master_menuses.id')->toArray() : [];

        // build tree sederhana
        $tree = $menus->groupBy('parent_id');
        $build = function($pid) use (&$build,$tree,$allowed){
            return ($tree[$pid] ?? collect())->map(function($m) use ($build,$tree,$allowed){
                return (object)[
                    'id'=>$m->id,'name'=>$m->menu_name,'path'=>$m->menu_path,'icon'=>$m->menu_icon,
                    'checked'=>in_array($m->id,$allowed,true),
                    'children'=>$build($m->id),
                ];
            });
        };

        return view('admin.access.index', [
            'roles'=>$roles, 'currentRole'=>$role, 'nodes'=>$build(null)
        ]);
    }

    public function save(Request $r)
    {
        $data = $r->validate([
            'role_id' => ['required','integer','exists:tb_master_roles,id'],
            'menu'    => ['array']
        ]);

        $role = TbMasterRole::findOrFail($data['role_id']);
        $role->menus()->sync(array_map('intval', array_keys($data['menu'] ?? [])));

        return back()->with('ok','Akses disimpan.');
    }
}
