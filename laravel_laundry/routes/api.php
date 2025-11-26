
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OutletController;

Route::middleware('api')->group(function () {
    Route::get('/status', function () {
        return response()->json(['status' => 'API is running']);
    })->name('api.status');
});


Route::prefix('auth')->middleware('api')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh')->middleware('auth:api');

    Route::middleware('auth:api')->group(function () {
        Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');
    });

    Route::middleware('auth:api')->group(function () {
        Route::post('/generate-api-key', [AuthController::class, 'generateApiKey'])->name('auth.generate-api-key');
    });

    Route::middleware('api.key')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('/get-outlets', [OutletController::class, 'getOutlets'])->name('get.outlets');
    });
});


Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => "API tidak ditemukan",
        "data" => null,
        "errors" => null,
        "meta" => null
    ], 404);
});
