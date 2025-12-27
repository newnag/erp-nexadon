<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('ingredients', \App\Http\Controllers\IngredientController::class);
    Route::post('ingredients/{ingredient}/receive-stock', [\App\Http\Controllers\IngredientController::class, 'receiveStock'])->name('ingredients.receive-stock');

    // ระบบจัดการสต็อค
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [\App\Http\Controllers\StockController::class, 'index'])->name('index');
        Route::get('/in', [\App\Http\Controllers\StockController::class, 'stockInCreate'])->name('in.create');
        Route::post('/in', [\App\Http\Controllers\StockController::class, 'stockInStore'])->name('in.store');
        Route::get('/out', [\App\Http\Controllers\StockController::class, 'stockOutCreate'])->name('out.create');
        Route::post('/out', [\App\Http\Controllers\StockController::class, 'stockOutStore'])->name('out.store');
        Route::get('/adjustment', [\App\Http\Controllers\StockController::class, 'adjustmentCreate'])->name('adjustment.create');
        Route::post('/adjustment', [\App\Http\Controllers\StockController::class, 'adjustmentStore'])->name('adjustment.store');
        Route::get('/history', [\App\Http\Controllers\StockController::class, 'history'])->name('history');
        Route::get('/report', [\App\Http\Controllers\StockController::class, 'report'])->name('report');
    });
    
    Route::resource('recipes', \App\Http\Controllers\RecipeController::class);
    Route::get('recipes/{recipe}/print', [\App\Http\Controllers\RecipeController::class, 'print'])->name('recipes.print');

    // ระบบการเงิน - รายรับรายจ่าย
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\FinancialTransactionController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\FinancialTransactionController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\FinancialTransactionController::class, 'store'])->name('store');
        Route::get('/recent-descriptions', [\App\Http\Controllers\FinancialTransactionController::class, 'recentDescriptions'])->name('recent-descriptions');
        Route::get('/{financialTransaction}/edit', [\App\Http\Controllers\FinancialTransactionController::class, 'edit'])->name('edit');
        Route::put('/{financialTransaction}', [\App\Http\Controllers\FinancialTransactionController::class, 'update'])->name('update');
        Route::delete('/{financialTransaction}', [\App\Http\Controllers\FinancialTransactionController::class, 'destroy'])->name('destroy');
        Route::get('/report', [\App\Http\Controllers\FinancialTransactionController::class, 'report'])->name('report');

        // หมวดหมู่การเงิน
        Route::get('/categories', [\App\Http\Controllers\FinancialCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [\App\Http\Controllers\FinancialCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{financialCategory}', [\App\Http\Controllers\FinancialCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{financialCategory}', [\App\Http\Controllers\FinancialCategoryController::class, 'destroy'])->name('categories.destroy');
    });
});

require __DIR__.'/settings.php';
