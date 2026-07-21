<?php

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
