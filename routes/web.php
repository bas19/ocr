<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Receipt UI routes (use session/cookies for Inertia)
Route::get('/receipts', [ReceiptController::class, 'indexPage'])->name('receipts.page.index');
Route::get('/receipts/upload', [ReceiptController::class, 'uploadPage'])->name('receipts.page.upload');
