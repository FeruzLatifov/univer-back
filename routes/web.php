<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'HEMIS Univer Backend API',
        'version' => '1.0.0',
        'docs' => url('/api/documentation'),
    ]);
});
