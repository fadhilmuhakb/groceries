<?php

namespace App\Http\Controllers;

use App\Models\tb_master_menus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;


class TbMasterMenusController extends Controller
{
    public function index(Request $request)
    {
        try {
            $menus = tb_master_menus::with('parent','ancestors')->get();
            // dd($menus);
            if($request->ajax()) {
            return DataTables::of($menus)
                        ->addColumn('action', function ($menu) {
                        return '<a href="/settings/menus/edit/'.$menu->id.'" class="btn btn-sm btn-success"><i class="bx bx-pencil me-0"></i>
                        </a>
                        <a href="javascript:void(0)" onClick="confirmDelete('.$menu->id.')" class="btn btn-sm btn-danger"><i class="bx bx-trash me-0"></i>
                        </a>
                        ';
                    
                        })
                        ->addColumn('ancestors', function($menu) {
                            if($menu->parent_id) {
                                $ordered = $menu->ancestors;
                                return $ordered
                                    ->pluck('menu_name')
                                    ->implode(' › ');
                            } else {
                                return '-';
                            }
                             
                        })
                    ->rawColumns(['action','ancestors'])
                    ->make(true);
            };
            return view('pages.admin.settings.menus.index');
        } catch(\Exception $e){

        }
    }

    public function create(Request $request) 
    {
        $menus = tb_master_menus::all();
        return view('pages.admin.settings.menus.create', compact('menus'));
    }

    public function edit(Request $request, $id)
    {
        $menu = tb_master_menus::find($id);
        $menus = tb_master_menus::all();
        return view('pages.admin.settings.menus.create', compact('menu','menus'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'menu_name' => 'required',
            'menu_path' => 'nullable',
            'menu_icon' => 'nullable',
            'parent_id' => 'nullable',
        ]);
        DB::beginTransaction();
        try {
            tb_master_menus::create($data);
            DB::commit();
            return redirect('/settings/menus')->with('success', 'Data berhasil dikirim!');
        } catch(\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());

        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'menu_name' => 'required',
            'menu_path' => 'nullable',
            'menu_icon' => 'nullable',
            'parent_id' => 'nullable',
            'is_active' => 'required',
        ]);
        DB::beginTransaction();
        try {
            tb_master_menus::where('id', $id)->update($data);
            DB::commit();
            return redirect('/settings/menus')->with('success', 'Data berhasil diperbaharui!');

        } catch(\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            tb_master_menus::where('id', $id)->delete();
            return resp_success('Data berhasil dihapus');
        } catch(\Exception $e) {
            return resp_error($e->getMessage());
        }
    }
}
