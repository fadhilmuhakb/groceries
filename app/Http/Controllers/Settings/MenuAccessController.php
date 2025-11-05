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
    $roles = collect(['superadmin','admin','staff']);
    $currentRole = strtolower(trim((string) $request->query('role', 'superadmin')));
    if (!in_array($currentRole, $roles->all(), true)) $currentRole = 'superadmin';

    // Ambil id menu yang diizinkan untuk role (CAST ke int + trim/lower)
    $allowedIds = \DB::table('tb_master_menu_roles')
        ->whereRaw('LOWER(TRIM(role_name)) = ?', [$currentRole])
        ->pluck('menu_id')
        ->map(fn($v) => (int) $v)
        ->all();

    // Buat SET supaya lookup O(1) & kebal tipe
    $allowSet = array_flip($allowedIds);

    // Build tree seperti biasa
    $all   = \App\Models\tb_master_menus::where('is_active', 1)->orderBy('id')->get()->groupBy('parent_id');
    $build = function($pid) use (&$build, $all) {
        $items = $all[$pid] ?? collect();
        return $items->map(function($m) use ($build) {
            return (object)[
                'id'       => (int) $m->id,
                'name'     => $m->menu_name,
                'path'     => $m->menu_path,
                'icon'     => $m->menu_icon,
                'children' => $build($m->id),
            ];
        });
    };

    return view('settings.access.index', [
        'roles'       => $roles,
        'currentRole' => $currentRole,
        'nodes'       => $build(null),
        'allowSet'    => $allowSet,   // ⬅️ kirim ke view
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
