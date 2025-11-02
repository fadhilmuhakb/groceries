<?php
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

if (!function_exists('resp_success')) {
    function resp_success(string $message = "", mixed $data = null, $cookie = null): JsonResponse
    {
        $response = response()->json([
            "status" => "OK",
            "messages" => $message,
            "data" => $data ?? [],
            "errors" => []
        ], 200);

        if($cookie){
            $response->cookie($cookie);
        }

        return $response;
    }
}

if (!function_exists('resp_error')) {
    function resp_error(string $message = "", mixed $data = null, int $code = 400): JsonResponse
    {
        return response()->json([
            "status" => "ERROR",
            "messages" => $message,
            "data" => [],
            "errors" => $data ?? [],
        ], $code);
    }
}

if (!function_exists('handle_exception')) {
    function handle_exception($e): JsonResponse
    {
        $message = "Error";
        $code = 500;

        if ($e instanceof Exception) {
            $message = $e->getMessage();
            $code = 404;
        }

        dd($e);

        // if($e !instanceof Exception){
        //     $message = $e->getMess
        // }

        return response()->json([
            "status" => "ERROR",
            "messages" => $message,
            "data" => [],
            "errors" => [],
        ], $code);
    }
}