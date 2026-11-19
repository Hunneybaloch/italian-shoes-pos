<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CatalogController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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


    Route::middleware('permission:products.view')->group(function () {
        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    });
    Route::middleware('permission:products.edit')->group(function () {
        Route::put('/catalog/categories/{category}', [CatalogController::class, 'updateCategory'])->name('catalog.categories.update');
        Route::patch('/catalog/categories/{category}/status', [CatalogController::class, 'toggleCategory'])->name('catalog.categories.toggle');
        Route::put('/catalog/brands/{brand}', [CatalogController::class, 'updateBrand'])->name('catalog.brands.update');
        Route::patch('/catalog/brands/{brand}/status', [CatalogController::class, 'toggleBrand'])->name('catalog.brands.toggle');
    });

    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/history', [InventoryController::class, 'history'])->name('inventory.history');
    });
    Route::middleware('permission:inventory.manage')->group(function () {
        Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::patch('/suppliers/{supplier}/status', [SupplierController::class, 'toggle'])->name('suppliers.toggle');
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    });
    Route::middleware('permission:inventory.transfer')->group(function () {
        Route::get('/transfers', [StockTransferController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/create', [StockTransferController::class, 'create'])->name('transfers.create');
        Route::post('/transfers', [StockTransferController::class, 'store'])->name('transfers.store');
        Route::patch('/transfers/{transfer}/approve', [StockTransferController::class, 'approve'])->name('transfers.approve');
        Route::patch('/transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');
    });

    Route::middleware('permission:sales.view')->group(function () {
        Route::get('/sales', [PosController::class, 'sales'])->name('sales.index');
        Route::get('/sales/{sale}/receipt', [PosController::class, 'receipt'])->name('sales.receipt');
    });
    Route::middleware('permission:sales.create')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('/pos/search', [PosController::class, 'search'])->name('pos.search');
        Route::post('/sales', [PosController::class, 'store'])->name('sales.store');
    });

    Route::middleware('permission:customers.view')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    });
    Route::middleware('permission:customers.manage')->group(function () {
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::patch('/customers/{customer}/status', [CustomerController::class, 'toggle'])->name('customers.toggle');
    });
    Route::middleware('permission:sales.returns')->group(function () {
        Route::get('/returns', [SaleReturnController::class, 'index'])->name('returns.index');
        Route::get('/sales/{sale}/returns/create', [SaleReturnController::class, 'create'])->name('returns.create');
        Route::post('/sales/{sale}/returns', [SaleReturnController::class, 'store'])->name('returns.store');
    });


    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    });
    Route::middleware('permission:expenses.manage')->group(function () {
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    });
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });
    Route::middleware('permission:inventory.manage')->group(function () {
        Route::get('/supplier-payments', [SupplierPaymentController::class, 'index'])->name('supplier-payments.index');
        Route::post('/purchases/{purchase}/payments', [SupplierPaymentController::class, 'store'])->name('purchases.payments.store');
    });


    Route::middleware('permission:employees.view')->group(function () {
        Route::get('/employees', [UserController::class, 'index'])->name('employees.index');
    });
    Route::middleware('permission:employees.create')->group(function () {
        Route::get('/employees/create', [UserController::class, 'create'])->name('employees.create');
        Route::post('/employees', [UserController::class, 'store'])->name('employees.store');
    });
    Route::middleware('permission:employees.edit')->group(function () {
        Route::get('/employees/{employee}/edit', [UserController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [UserController::class, 'update'])->name('employees.update');
    });
    Route::middleware('permission:employees.deactivate')->group(function () {
        Route::patch('/employees/{employee}/status', [UserController::class, 'toggle'])->name('employees.toggle');
    });
    Route::middleware('permission:activity-logs.view')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
    Route::middleware('owner')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::middleware('owner')->group(function () {
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('/branches/{branch}', [BranchController::class, 'show'])->whereNumber('branch')->name('branches.show');
        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->whereNumber('branch')->name('branches.edit');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])->whereNumber('branch')->name('branches.update');
        Route::patch('/branches/{branch}/status', [BranchController::class, 'toggleStatus'])->whereNumber('branch')->name('branches.toggle-status');
    });
});

Route::redirect('/', '/login');
