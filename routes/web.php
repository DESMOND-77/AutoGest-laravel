<?php

use App\Domain\Finance\Http\Controllers\InvoiceController;
use App\Domain\Finance\Http\Controllers\LedgerController;
use App\Domain\Finance\Http\Controllers\PaymentController;
use App\Domain\Finance\Http\Controllers\TrainingPackageController;
use App\Domain\Students\Http\Controllers\AdminDashboardController;
use App\Domain\Students\Http\Controllers\StudentController;
use App\Domain\Tenancy\Http\Controllers\StructureManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('structures', [StructureManagementController::class, 'index'])->name('structures.index');
        Route::patch('structures/{structure}/status', [StructureManagementController::class, 'updateStatus'])->name('structures.status');
        Route::delete('structures/{structure}', [StructureManagementController::class, 'destroy'])->name('structures.destroy');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', AdminDashboardController::class)->name('dashboard');
    });

Route::middleware(['auth', 'role:admin|moniteur'])->group(function () {
    Route::resource('students', StudentController::class);
    Route::patch('students/{student}/stage', [StudentController::class, 'advanceStage'])->name('students.stage');
});

Route::middleware(['auth', 'role:admin'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('students/{student}/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('students/{student}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');

        Route::get('packages', [TrainingPackageController::class, 'index'])->name('packages.index');
        Route::post('packages', [TrainingPackageController::class, 'store'])->name('packages.store');
        Route::patch('packages/{package}', [TrainingPackageController::class, 'update'])->name('packages.update');
        Route::delete('packages/{package}', [TrainingPackageController::class, 'destroy'])->name('packages.destroy');

        Route::get('ledger', [LedgerController::class, 'index'])->name('ledger.index');
        Route::post('ledger', [LedgerController::class, 'store'])->name('ledger.store');
    });

Route::middleware(['auth', 'role:moniteur'])
    ->name('moniteur.')
    ->group(function () {
        Route::view('moniteur/dashboard', 'moniteur.dashboard')->name('dashboard');
    });

Route::middleware(['auth', 'role:eleve'])
    ->name('eleve.')
    ->group(function () {
        Route::view('eleve/dashboard', 'eleve.dashboard')->name('dashboard');
    });

require __DIR__.'/auth.php';
