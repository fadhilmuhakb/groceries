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
        ->addIndexColumn()
        ->addColumn('name', fn ($row) => optional($row->user)->name ?? '-')
        ->editColumn('amount', fn ($row) => (int) $row->amount)
        ->editColumn('date', function ($row) {
            return $row->date instanceof \Carbon\Carbon
                ? $row->date->toDateString()
                : $row->date;
        })
        ->addColumn('status', function ($row) {
            $filterDate  = \Carbon\Carbon::parse($row->date)->toDateString();
            $cashierName = trim(optional($row->user)->name ?? '');

            // Hitung total penjualan (Σ subtotal) untuk kasir & tanggal ini
            $soldTotal = \App\Models\tb_outgoing_goods::query()
                ->leftJoin('tb_products as p', 'p.id', '=', 'tb_outgoing_goods.product_id') // SESUAIKAN nama tabel produk
                ->where(function ($q) use ($filterDate) {
                    $q->whereDate('tb_outgoing_goods.date', $filterDate)
                      ->orWhereDate('tb_outgoing_goods.created_at', $filterDate);
                })
                ->when($cashierName !== '', function ($q) use ($cashierName) {
                    // normalisasi recorded_by vs $cashierName
                    $normalized = strtolower(trim($cashierName));
                    $sql = "
                        REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(COALESCE(tb_outgoing_goods.recorded_by,''))), ' ', ''), '.', ''), '-', ''), '_', '')
                        = REPLACE(REPLACE(REPLACE(REPLACE(LOWER(?), ' ', ''), '.', ''), '-', ''), '_', '')
                    ";
                    $q->whereRaw($sql, [$normalized]);
                })
                ->selectRaw("
                    COALESCE(SUM(GREATEST(0,
                        (COALESCE(p.selling_price,0) * COALESCE(tb_outgoing_goods.quantity_out,0))
                        - COALESCE(tb_outgoing_goods.discount,0)
                    )), 0) as total
                ")
                ->value('total');

            $delta = (int) $row->amount - (int) $soldTotal; // +/−
            return $delta; // biar dirender di view (dibuat Rp + warna)
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
