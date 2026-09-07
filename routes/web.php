<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Guest routes — redirect to dashboard if already authenticated
Route::middleware('guest')->group(function () {
    Route::livewire('/', 'pages::auth.login')->name('login');
});

// Authenticated routes — protected by auth middleware
Route::middleware('auth')->group(function () {
    // 1. Dashboard (Accessible by all authenticated roles: Admin, Manager, Staff)
    Route::livewire('/dashboard', 'pages::dashboard.dashboard')->name('dashboard');

    // 2. Admin Only Routes (Role: admin)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // User Management, System Settings
    });

    // 3. Admin & Warehouse Manager Routes (Role: admin, manager)
    Route::middleware('role:admin,manager')->group(function () {
        // Master Data CRUD (Products, Categories, Suppliers)
        Route::livewire('/categories', 'pages::categories.index')->name('categories.index');
        Route::livewire('/suppliers', 'pages::suppliers.index')->name('suppliers.index');
        Route::livewire('/products', 'pages::products.index')->name('products.index');

        // Reports & Export Analytics
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::livewire('/stock', 'pages::reports.stock')->name('stock');
            Route::livewire('/mutations', 'pages::reports.mutations')->name('mutations');

            // Export endpoints (CSV for Excel and Printable PDF)
            Route::get('/stock/csv', [\App\Http\Controllers\ReportController::class, 'exportStockCsv'])->name('stock.csv');
            Route::get('/stock/print', [\App\Http\Controllers\ReportController::class, 'printStock'])->name('stock.print');
            Route::get('/mutations/csv', [\App\Http\Controllers\ReportController::class, 'exportMutationsCsv'])->name('mutations.csv');
            Route::get('/mutations/print', [\App\Http\Controllers\ReportController::class, 'printMutations'])->name('mutations.print');
        });
    });

    // 4. Warehouse Operations & Stock Management (Role: admin, manager, staff)
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::livewire('/in', 'pages::stock.in')->name('in');
        Route::livewire('/out', 'pages::stock.out')->name('out');
        Route::livewire('/opname', 'pages::stock.opname')->name('opname');
        Route::livewire('/tasks', 'pages::stock.tasks')->name('tasks');
    });
});
