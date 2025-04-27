<?php

namespace App\Http\Controllers;

use App\Models\tb_customers;
use App\Models\tb_stores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TbCustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $customers = tb_customers::all();
        if($request->ajax()) {
            return DataTables::of($customers)
                    ->addColumn('action', function ($customers) {
                        return '<a href="/customer/edit/'.$customers->id.'" class="btn btn-sm btn-success"><i class="bx bx-pencil me-0"></i>
                        </a>
                        <a href="javascript:void(0)" onClick="confirmDelete('.$customers->id.')" class="btn btn-sm btn-danger"><i class="bx bx-trash me-0"></i>
                        </a>
                        ';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('pages.admin.manage_customer.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = tb_stores::all();
        return view('pages.admin.manage_customer.create', compact('stores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $getRoles = auth()->user()->roles;
        if($getRoles == 'superadmin') {
            $store_id = $request->store_id;
        } else {
            $store_id = auth()->user()->store_id;
        }

        $validated = $request->validate([
            'customer_name' => 'required',
            'phone_number' => 'nullable|min:8|numeric'
        ]);

        DB::beginTransaction();
        try {
            tb_customers::create([
                'customer_name' => $request->customer_name,
                'phone_number' => $request->phone_number,
                'store_id' => $store_id
            ]);

            DB::commit();
            return redirect('/customer')->with('success', 'Data berhasil dikirim!');

        } catch(\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());

        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $customer = tb_customers::findOrFail($id);
        $stores = tb_stores::all();
        return view('pages.admin.manage_customer.create', compact('customer', 'stores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $getRoles = auth()->user()->roles;

        if($getRoles == 'superadmin') {
            $store_id = $request->store_id;
        } else {
            $store_id = auth()->user()->store_id;
        }

        $validated = $request->validate([
            'customer_name' => 'required',
            'phone_number' => 'nullable|min:8|numeric'
        ]);
        
        DB::beginTransaction();
        try {

            tb_customers::where('id', $id)->update([
                'customer_name' => $request->customer_name,
                'phone_number' => $request->phone_number,
                'store_id' => $store_id
            ]);

            DB::commit();
            return redirect('/customer')->with('success', 'Data berhasil diperbaharui!');
        } catch(\Exception $e){
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }


    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            tb_customers::where('id', $id)->delete();
            DB::commit();
            return response()->json([
                'success'=>true,
                'message'=>'Customer berhasil dihapus',
            ]);
        }catch(\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success'=>false,
                'message'=>'Toko berhasil dihapus',
            ]);
        }
    }
}
