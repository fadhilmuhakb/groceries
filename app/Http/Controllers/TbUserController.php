<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TbUserController extends Controller
{
    
    public function index(Request $request)
    {
        $users = User::all();
        if($request->ajax()) {
            return DataTables::of($users)
                    // ->addColumn('action', function ($user) {
                    //     return '<a href="/user/edit/'.$user->id.'" class="btn btn-sm btn-success"><i class="bx bx-pencil me-0"></i>
                    //     </a>
                    //     <a href="javascript:void(0)" onClick="confirmDelete('.$user->id.')" class="btn btn-sm btn-danger"><i class="bx bx-trash me-0"></i>
                    //     </a>
                    //     ';
                    // })
                    ->rawColumns(['action'])
                    ->make(true);
        }

        return view('pages.admin.manage_user.index');
    }

    public function create()
    {
        return view('pages.admin.manage_user.create');
    }

    /**
     * Store the newly created resource in storage.
     */
    public function store(Request $request)
    {
        
    }

    public function edit($id)
    {
        $user = User::where('id', $id)->first();
        return view('pages.admin.manage_user.create', ['user'=>$user]);
    }

    /**
     * Update the resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the resource from storage.
     */
    public function destroy($id)
    {
        
    }
}
