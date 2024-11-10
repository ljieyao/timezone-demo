<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/mock-api/batch', function (Request $request) {
    // Validate request format
    $request->validate([
        'batches.*.subscribers' => 'required|array',
        'batches.*.subscribers.*.email' => 'required|email',
        'batches.*.subscribers.*.time_zone' => 'required|string',
    ]);

    // Simulate API processing time
    usleep(500000);

    return response()->json([
        'status' => 'success',
        'processed' => count($request->input('batches.0.subscribers')),
    ]);
})->middleware('api');
