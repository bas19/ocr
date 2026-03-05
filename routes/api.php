<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. These routes are stateless and
| do not use session/cookie storage, making them suitable for high-volume
| operations and cloud deployments with strict cookie size limits.
|
*/

// Receipt API routes (stateless, no session middleware or CSRF for same-origin requests)
Route::prefix('receipts')->group(function () {
    Route::get('/', [ReceiptController::class, 'index'])->name('api.receipts.index');
    Route::post('/', [ReceiptController::class, 'store'])->name('api.receipts.store');
    Route::get('/{receipt}', [ReceiptController::class, 'show'])->name('api.receipts.show');
    Route::delete('/{receipt}', [ReceiptController::class, 'destroy'])->name('api.receipts.destroy');
});
