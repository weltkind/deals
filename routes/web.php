<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DealController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('deals.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/deals', [DealController::class, 'index'])->name('deals.index');
    Route::post('/deals', [DealController::class, 'store'])->name('deals.store');
    Route::get('/deals/{deal}', [DealController::class, 'show'])->name('deals.show');
    Route::post('/deals/{deal}/close', [DealController::class, 'close'])->name('deals.close');
    Route::post('/deals/{deal}/items', [DealController::class, 'storeItem'])->name('deals.items.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
