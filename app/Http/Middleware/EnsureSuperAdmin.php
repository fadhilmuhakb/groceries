<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSuperadmin
{
    public function handle(Request $request, Closure $next)
    {
        $role = strtolower((string)($request->user()->roles ?? ''));
        abort_if($role !== 'superadmin', 403, 'Hanya superadmin yang boleh mengakses halaman ini.');
        return $next($request);
    }
}
