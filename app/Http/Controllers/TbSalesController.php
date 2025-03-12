<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TbSalesController extends Controller
{
    public function index()
    {
        return view('pages.admin.sales.index');
    }
}
