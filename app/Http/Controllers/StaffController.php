<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\tb_daily_revenues;


class StaffController extends Controller
{

    public function submitRevenueAndLogout(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);
    
        tb_daily_revenues::create([
            'user_id' => auth()->id(), // tipe UUID
            'date' => now()->toDateString(),
            'amount' => $request->amount
        ]);
    
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    
        return redirect('/login')->with('status', 'Berhasil logout dan mencatat pendapatan hari ini.');
    }
    
    
}
