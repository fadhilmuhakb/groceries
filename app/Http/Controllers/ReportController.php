<?php

namespace App\Http\Controllers;

use App\Models\tb_daily_revenues;
use App\Models\tb_outcoming_goods;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\tb_outgoing_goods;
class ReportController extends Controller
{
    // =========================
    // LIST / INDEX (VIEW)
    // =========================
    public function index()
    {
        return view('pages.admin.report.index');
    }

    // =========================
    // LIST / INDEX (JSON for DataTables)
    // =========================
    public function indexData(Request $request)
    {
        $query = tb_daily_revenues::with('user:id,name')
            ->select('id', 'user_id', 'amount', 'date');

        return DataTables::eloquent($query)
            ->addIndexColumn() // DT_RowIndex
            ->addColumn('name', fn ($row) => optional($row->user)->name ?? '-')
            ->editColumn('amount', fn ($row) => (int) $row->amount)
            ->editColumn('date', function ($row) {
                return $row->date instanceof \Carbon\Carbon
                    ? $row->date->toDateString() // "YYYY-MM-DD"
                    : $row->date;
            })
            ->addColumn('action', function ($row) {
                return '
                    <div class="d-flex justify-content-center">
                        <a href="' . route('report.detail', $row->id) . '" class="btn btn-sm btn-success me-1">
                            Detail Penjualan <i class="bx bx-right-arrow-alt"></i>
                        </a>
                    </div>';
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    // =========================
    // DETAIL (VIEW)
    // =========================
    public function detail(Request $request, $id)
    {
        $revenue = tb_daily_revenues::with('user:id,name')
            ->select('id','user_id','amount','date')
            ->findOrFail($id);

        return view('pages.admin.report.detail', [
            'revenue' => $revenue,
            'cashier' => optional($revenue->user)->name ?? '-',
        ]);
    }

    // =========================
    // DETAIL (JSON for DataTables)
    // =========================
  // =========================
// DETAIL (JSON for DataTables)
// =========================
public function detailData(Request $request, $id)
{
    $revenue = tb_daily_revenues::with('user:id,name')
        ->select('id','user_id','amount','date')
        ->findOrFail($id);

    $filterDate  = \Carbon\Carbon::parse($revenue->date)->toDateString();
    $cashierName = trim(optional($revenue->user)->name ?? '');

    // NOTE: gunakan model & tabel yang benar: tb_outgoing_goods (bukan outcoming)
    $query = tb_outgoing_goods::query()
        ->select(
            'id',
            'uuid',
            'product_id',
            'sell_id',
            'date',
            'quantity_out',
            'discount',
            'recorded_by',
            'description',
            'created_at'
        )
        // Samakan tanggalnya (pakai kolom `date`)
        ->whereDate('date', $filterDate)
        // Samakan nama kasir: recorded_by === daily_revenues.user.name (case/space-insensitive)
        ->when($cashierName !== '', function ($q) use ($cashierName) {
            $q->whereRaw('LOWER(TRIM(recorded_by)) = ?', [strtolower($cashierName)]);
        });

    // Ambil relasi product agar bisa keluarkan nama product
    if (method_exists(\App\Models\tb_outgoing_goods::class, 'product')) {
        $query->with('product:id,product_name as name,selling_price as price');
    }

    return DataTables::eloquent($query)
        ->addIndexColumn()
        // Keluarkan nama product di outgoing goods
        ->addColumn('product_name', fn($row) => optional($row->product)->name ?? $row->product_id)
        ->addColumn('price', fn($row) => (float) (optional($row->product)->price ?? 0))
        ->addColumn('subtotal', function ($row) {
            $price = (float) (optional($row->product)->price ?? 0);
            $qty   = (int)   ($row->quantity_out ?? 0);
            $disc  = (float) ($row->discount ?? 0);
            return max(0, ($price * $qty) - $disc);
        })
        ->toJson();
}

}
