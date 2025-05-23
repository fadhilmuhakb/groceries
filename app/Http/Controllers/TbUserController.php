<?php

namespace App\Http\Controllers;

use App\Models\tb_stores;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class TbUserController extends Controller
{

    public function index(Request $request)
    {
        $user = auth()->user();
        $getRoles = $user->roles;

        $users = \App\Models\User::with('store')
            ->when($getRoles !== 'superadmin' && $user->store_id, function ($query) use ($user) {
                $query->where('store_id', $user->store_id);
            })
            ->get();


        if ($request->ajax()) {
            return DataTables::of($users)
                ->addColumn('action', function ($user) {
                    return '<a href="/user/edit/' . $user->id . '" class="btn btn-sm btn-success">
                                <i class="bx bx-pencil me-0"></i>
                            </a>
                            <a href="javascript:void(0)" onClick="confirmDelete(\'' . $user->id . '\')" class="btn btn-sm btn-danger">
                                <i class="bx bx-trash me-0"></i>
                            </a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('pages.admin.manage_user.index');
    }


    public function create()
    {
        $user = auth()->user();
        $getRoles = $user->roles;

        if ($getRoles === 'superadmin') {
            $stores = tb_stores::all();
        } else {
            $stores = tb_stores::where('id', $user->store_id)->get();
        }
        return view('pages.admin.manage_user.create', ['stores' => $stores]);
    }

    /**
     * Store the newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|min:8|confirmed',
            'roles' => 'required',
            'store_id' => 'nullable',
        ]);
        DB::beginTransaction();
        try {
            $data['password'] = Hash::make($data['password']);
            User::create($data);
            DB::commit();
            return redirect()->route('user.index')->with('success', 'User berahasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $stores = tb_stores::all();
        $user = User::where('id', $id)->first();
        return view('pages.admin.manage_user.create', ['user' => $user, 'stores' => $stores]);
    }

    /**
     * Update the resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'roles' => 'required',
            'store_id' => 'nullable',
        ]);

        DB::beginTransaction();
        try {
            $data = User::where('id', $id)->update($data);
            DB::commit();
            return redirect()->route('user.index')->with('success', 'User berahasil diperbaharui');
        } catch (\Exception $e) {
            dd($e->getMessage());
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function updatePassword(Request $request, $id)
    {
        $data = $request->validate([
            'new_password' => 'required|min:8|confirmed'
        ]);
        // dd(auth()->user()->password);
        DB::beginTransaction();
        try {
            User::where('id', $id)->update([
                'password' => Hash::make($request->new_password)
            ]);
            DB::commit();
            return redirect()->route('user.index')->with('success', 'Password berhasil diperbaharui');
        } catch (\Exception $e) {
            DB::rollBack();
            dd('error');

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            User::where('id', $id)->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'User berhasil dihapus']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
