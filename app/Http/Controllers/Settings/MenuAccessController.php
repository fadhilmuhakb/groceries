<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\tb_master_menus;

class MenuAccessController extends Controller
{
    // Daftar role yang bisa diatur
    private array $roles = ['superadmin','admin','staff'];

    public function index(Request $request)
    {
        $roles = collect($this->roles);

        // role aktif dari query ?role=..., default superadmin
        $currentRole = strtolower((string) $request->query('role', 'superadmin'));
        if (!in_array($currentRole, $this->roles, true)) $currentRole = 'superadmin';

        // id menu yang diizinkan utk role sekarang
        $allowedIds = DB::table('tb_master_menu_roles')
            ->whereRaw('LOWER(role_name) = ?', [$currentRole])
            ->pluck('menu_id')
            ->toArray();

        // build tree menu aktif
        $all = tb_master_menus::where('is_active', 1)->orderBy('id')->get()->groupBy('parent_id');
        $build = function($pid) use (&$build, $all, $allowedIds) {
            $items = $all[$pid] ?? collect();
            return $items->map(function ($m) use ($build, $allowedIds) {
                return (object)[
                    'id'       => $m->id,
                    'name'     => $m->menu_name,
                    'path'     => $m->menu_path,
                    'icon'     => $m->menu_icon,
                    'checked'  => in_array($m->id, $allowedIds, true),
                    'children' => $build($m->id),
                ];
            });
        };

        return view('settings.access.index', [
            'roles'       => $roles,
            'currentRole' => $currentRole,
            'nodes'       => $build(null),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'role_name' => ['required','string'],
            'menu'      => ['array'],
        ]);

        $role = strtolower(trim($data['role_name']));
        if (!in_array($role, $this->roles, true)) {
            return back()->withErrors('Role tidak dikenal.');
        }

        // Kebijakan: superadmin selalu full access (abaikan input)
        if ($role === 'superadmin') {
            $allMenuIds = DB::table('tb_master_menuses')->where('is_active',1)->pluck('id')->toArray();
            DB::transaction(function () use ($allMenuIds, $role) {
                DB::table('tb_master_menu_roles')->whereRaw('LOWER(role_name)=?',[$role])->delete();
                $now = now();
                $rows = array_map(fn($id)=>[
                    'menu_id'=>$id,'role_name'=>$role,'created_at'=>$now,'updated_at'=>$now
                ], $allMenuIds);
                if ($rows) DB::table('tb_master_menu_roles')->insert($rows);
            });

            return redirect()->route('settings.access.index', ['role' => $role])
                ->with('ok', 'Superadmin selalu memiliki akses penuh.');
        }

        // admin / staff: simpan sesuai centang
        $menuIds = array_map('intval', array_keys($data['menu'] ?? []));

        DB::transaction(function () use ($role, $menuIds) {
            DB::table('tb_master_menu_roles')->whereRaw('LOWER(role_name)=?',[$role])->delete();
            if (!empty($menuIds)) {
                $now = now();
                $rows = array_map(fn($id)=>[
                    'menu_id'=>$id,'role_name'=>$role,'created_at'=>$now,'updated_at'=>$now
                ], $menuIds);
                DB::table('tb_master_menu_roles')->insert($rows);
            }
        });

        return redirect()->route('settings.access.index', ['role' => $role])
            ->with('ok', 'Akses berhasil disimpan untuk role: '.$role);
    }
}
