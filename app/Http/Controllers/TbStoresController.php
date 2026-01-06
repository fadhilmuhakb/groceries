<?php

namespace App\Http\Controllers;

use App\Models\tb_stores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
class TbStoresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $stores = tb_stores::all();
        if($request->ajax()) {
            return DataTables::of($stores)
                ->addColumn('status', function ($store) {
                    return $store->is_online
                        ? '<span class="badge bg-success">Online</span>'
                        : '<span class="badge bg-secondary">Offline</span>';
                })
                ->addColumn('action', function ($stores) {
                    $toggleLabel = $stores->is_online ? 'Offline' : 'Online';
                    return '
                    <div class="btn-group">
                        <a href="/store/edit/'.$stores->id.'" class="btn btn-sm btn-success"><i class="bx bx-pencil me-0"></i></a>
                        <button type="button" class="btn btn-sm btn-warning" onclick="toggleOnline('.$stores->id.', '.($stores->is_online ? 'false' : 'true').')">'.$toggleLabel.'</button>
                        <a href="javascript:void(0)" onClick="confirmDelete('.$stores->id.')" class="btn btn-sm btn-danger"><i class="bx bx-trash me-0"></i></a>
                    </div>
                    ';
                })
                ->rawColumns(['action','status'])
                ->make(true);
        }
        return view('pages.admin.manage_store.index');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.admin.manage_store.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_address' => 'required',
            'store_name' => 'required'
        ]);

        DB::beginTransaction();
        try {
            tb_stores::create($validated);
            DB::commit();
            return redirect('/store')->with('success', 'Data berhasil dikirim!');
        } catch(\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(tb_stores $tb_stores)
    {
        //
    }

  
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $stores = tb_stores::findOrFail($id);
        return view('pages.admin.manage_store.create', ['stores' => $stores]); // ← ini harusnya edit.blade.php
    }
    

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'store_address' => 'required',
            'store_name' => 'required'
        ]);

        DB::beginTransaction();
        try {
            tb_stores::where('id', $id)->update($data);
            DB::commit();
            return redirect('/store')->with('success', 'Data berhasil diperbaharui!');
        } catch(\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            tb_stores::where('id', $id)->delete();
            DB::commit();
            return response()->json([
                'success'=>true,
                'message'=>'Toko berhasil dihapus',
            ]);
        }catch(\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success'=>false,
                'message'=>'Toko berhasil dihapus',
            ]);
        }
    
    }

    /**
     * Toggle online/offline status.
     */
    public function toggleOnline(Request $request, $id)
    {
        try {
            $user = $request->user();
            $role = strtolower((string)($user?->roles));

            // superadmin/admin boleh pilih toko; selain itu pakai toko user dan abaikan ID yang dikirim
            $storeId = in_array($role, ['superadmin','admin'])
                ? (int)$id
                : (int)($user?->store_id);
            if ($storeId <= 0 || (!in_array($role, ['superadmin','admin']) && !$user?->store_id)) {
                return response()->json(['message' => 'Store tidak valid untuk akun ini'], 422);
            }

            $store = tb_stores::find($storeId);
            if (!$store) {
                return response()->json(['message' => 'Store tidak ditemukan'], 404);
            }

            if (empty($store->uuid)) {
                $store->uuid = (string) Str::uuid();
            }

            if (!$request->has('is_online')) {
                return response()->json(['message' => 'Status is_online wajib dikirim'], 422);
            }

            $online = (bool)$request->boolean('is_online');
            $note   = $request->input('offline_note');

            DB::transaction(function () use ($store, $online, $note, $storeId) {
                $store->is_online     = $online;
                $store->offline_note  = $online ? null : $note;
                $store->offline_since = $online ? null : now();
                $store->save();

                // jika online kembali, lepas pending stock toko ini
                if ($online) {
                    $this->syncPendingStockForStore($storeId);
                }
            });

            return response()->json([
                'message' => $online ? 'Store online. Pending stok diproses.' : 'Store diset offline.',
                'is_online' => $online,
            ]);
        } catch (\Throwable $e) {
            \Log::error('toggleOnline error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function syncPendingStockForStore(int $storeId): void
    {
        $now = now();
        $incomingIds = DB::table('tb_incoming_goods as ig')
            ->join('tb_purchases as p', 'p.id', '=', 'ig.purchase_id')
            ->where('p.store_id', $storeId)
            ->where('ig.is_pending_stock', true)
            ->when(
                Schema::hasColumn('tb_incoming_goods', 'deleted_at'),
                fn ($q) => $q->whereNull('ig.deleted_at')
            )
            ->pluck('ig.id');

        if ($incomingIds->isNotEmpty()) {
            DB::table('tb_incoming_goods')
                ->whereIn('id', $incomingIds)
                ->update([
                    'is_pending_stock' => false,
                    'synced_at'        => $now,
                    'updated_at'       => $now,
                ]);
        }

        $outgoingIds = DB::table('tb_outgoing_goods as og')
            ->join('tb_sells as s', 's.id', '=', 'og.sell_id')
            ->where('s.store_id', $storeId)
            ->where('og.is_pending_stock', true)
            ->when(
                Schema::hasColumn('tb_outgoing_goods', 'deleted_at'),
                fn ($q) => $q->whereNull('og.deleted_at')
            )
            ->pluck('og.id');

        if ($outgoingIds->isNotEmpty()) {
            DB::table('tb_outgoing_goods')
                ->whereIn('id', $outgoingIds)
                ->update([
                    'is_pending_stock' => false,
                    'synced_at'        => $now,
                    'updated_at'       => $now,
                ]);
        }
    }
}
