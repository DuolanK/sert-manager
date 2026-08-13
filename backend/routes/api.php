<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificateController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function () {
    Route::post('/certificates/{id}/restore', [CertificateController::class, 'restore']);
    Route::delete('/certificates/{id}/force', [CertificateController::class, 'forceDestroy']);
    Route::apiResource('certificates', CertificateController::class);
});
