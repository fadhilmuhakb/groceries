<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SalesReportController extends Controller
{
    /**
     * Placeholder page for legacy "Laporan Penjualan" menu.
     */
    public function index()
    {
        return view('pages.admin.sales_report.index');
    }

    /**
     * Temporary JSON endpoint so the menu can hit DataTables without errors.
     */
    public function data(Request $request)
    {
        return DataTables::of(collect())
            ->with(['message' => 'Endpoint akan diisi setelah fitur siap.'])
            ->toJson();
    }
}
