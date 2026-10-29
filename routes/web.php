<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DealController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('deals.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/deals', [DealController::class, 'index'])->name('deals.index');
    Route::get('/deals/history', [DealController::class, 'history'])->name('deals.history');
    Route::post('/deals', [DealController::class, 'store'])->name('deals.store');
    Route::get('/deals/{deal}', [DealController::class, 'show'])->name('deals.show');
    Route::delete('/deals/{deal}', [DealController::class, 'destroy'])->name('deals.destroy');
    Route::post('/deals/{deal}/close', [DealController::class, 'close'])->name('deals.close');
    Route::post('/deals/{deal}/items', [DealController::class, 'storeItem'])->name('deals.items.store');
    Route::get('/deal-items/{item}/edit', [DealController::class, 'editItem'])->name('deals.items.edit');
    Route::patch('/deal-items/{item}', [DealController::class, 'updateItem'])->name('deals.items.update');
    Route::delete('/deal-items/{item}', [DealController::class, 'destroyItem'])->name('deals.items.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
