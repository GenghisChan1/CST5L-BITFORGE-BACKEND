<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-cors', function() {
    return response()->json([
        'cors_config' => config('cors'),
        'message' => 'CORS test from web route'
    ]);
});