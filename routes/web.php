<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ProductController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::middleware('permission:products.view')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::middleware('permission:products.create')->group(function () {
            Route::post('/products/categories', [ProductController::class, 'storeCategory'])->name('products.categories.store');
            Route::post('/products/brands', [ProductController::class, 'storeBrand'])->name('products.brands.store');
            Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
            Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        });
        Route::middleware('permission:products.edit')->group(function () {
            Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->whereNumber('product')->name('products.edit');
            Route::put('/products/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
            Route::patch('/products/{product}/status', [ProductController::class, 'toggleStatus'])->whereNumber('product')->name('products.status');
        });
    });

    Route::middleware('owner')->group(function () {
        Route::get('/branches', [BranchController::class, 'index'])
            ->name('branches.index');

        Route::get('/branches/create', [BranchController::class, 'create'])
            ->name('branches.create');

        Route::post('/branches', [BranchController::class, 'store'])
            ->name('branches.store');

        Route::get('/branches/{branch}', [BranchController::class, 'show'])
            ->whereNumber('branch')
            ->name('branches.show');

        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])
            ->whereNumber('branch')
            ->name('branches.edit');

        Route::put('/branches/{branch}', [BranchController::class, 'update'])
            ->whereNumber('branch')
            ->name('branches.update');

        Route::patch('/branches/{branch}/status', [BranchController::class, 'toggleStatus'])
            ->whereNumber('branch')
            ->name('branches.toggle-status');
    });
});

Route::redirect('/', '/login');
