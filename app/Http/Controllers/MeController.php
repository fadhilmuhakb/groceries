<?php
class MeController extends Controller
{
    public function menu(\App\Services\MenuService $svc)
    {
        return response()->json($svc->buildForUser(auth()->user()));
    }
}
