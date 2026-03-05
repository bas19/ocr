<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Receipt UI routes
Route::get('/receipts', [ReceiptController::class, 'indexPage'])->name('receipts.page.index');
Route::get('/receipts/upload', [ReceiptController::class, 'uploadPage'])->name('receipts.page.upload');
Route::post('/receipts', [ReceiptController::class, 'store'])->name('receipts.page.store');

// Receipt API routes
Route::prefix('api/receipts')->group(function () {
    Route::get('/', [ReceiptController::class, 'index'])->name('receipts.index');
    Route::post('/', [ReceiptController::class, 'store'])->name('receipts.store');
    Route::get('/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::delete('/{receipt}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');
});


