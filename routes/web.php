<?php

use App\Domain\Audit\Http\Controllers\AuditLogController;
use App\Domain\CRM\Http\Controllers\LeadController;
use App\Domain\Documents\Http\Controllers\DocumentController;
use App\Domain\Finance\Http\Controllers\InvoiceController;
use App\Domain\Finance\Http\Controllers\LedgerController;
use App\Domain\Finance\Http\Controllers\PaymentController;
use App\Domain\Finance\Http\Controllers\TrainingPackageController;
use App\Domain\Fleet\Http\Controllers\VehicleController;
use App\Domain\Instructors\Http\Controllers\InstructorAvailabilityController;
use App\Domain\Instructors\Http\Controllers\InstructorController;
use App\Domain\Notifications\Http\Controllers\NotificationController;
use App\Domain\Reports\Http\Controllers\ReportsController;
use App\Domain\Scheduling\Http\Controllers\AgendaController;
use App\Domain\Scheduling\Http\Controllers\LessonSessionController;
use App\Domain\Scheduling\Http\Controllers\StudentPlanningController;
use App\Domain\Settings\Http\Controllers\SettingController;
use App\Domain\Store\Http\Controllers\OrderController;
use App\Domain\Store\Http\Controllers\ProductController;
use App\Domain\Store\Http\Controllers\SupplierController;
use App\Domain\Students\Http\Controllers\StudentController;
use App\Domain\Tenancy\Http\Controllers\StructureManagementController;
use App\Domain\Training\Http\Controllers\EvaluationController;
use App\Domain\Training\Http\Controllers\ExamController;
use App\Domain\Training\Http\Controllers\QuizController;
use App\Domain\Training\Http\Controllers\SkillController;
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
        Route::get('dashboard', [ReportsController::class, 'dashboard'])->name('dashboard');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('reports')
    ->name('reports.')
    ->group(function () {
        Route::get('revenue.csv', [ReportsController::class, 'exportRevenueCsv'])->name('revenue.csv');
        Route::get('exams.csv', [ReportsController::class, 'exportExamResultsCsv'])->name('exams.csv');
        Route::get('students-by-stage.csv', [ReportsController::class, 'exportStudentsByStageCsv'])->name('students-by-stage.csv');
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

Route::middleware(['auth', 'role:admin'])
    ->prefix('planning')
    ->name('scheduling.')
    ->group(function () {
        Route::get('/', [LessonSessionController::class, 'index'])->name('index');
        Route::post('/', [LessonSessionController::class, 'store'])->name('store');
        Route::delete('{session}', [LessonSessionController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'role:admin|moniteur'])
    ->patch('sessions/{session}/presence', [LessonSessionController::class, 'markPresence'])
    ->name('scheduling.presence');

Route::middleware(['auth', 'role:admin'])
    ->prefix('training')
    ->name('training.')
    ->group(function () {
        Route::get('skills', [SkillController::class, 'index'])->name('skills.index');
        Route::post('skills', [SkillController::class, 'store'])->name('skills.store');
        Route::delete('skills/{skill}', [SkillController::class, 'destroy'])->name('skills.destroy');

        Route::get('exams', [ExamController::class, 'index'])->name('exams.index');
        Route::post('exams', [ExamController::class, 'store'])->name('exams.store');
        Route::patch('exams/{exam}', [ExamController::class, 'update'])->name('exams.update');
    });

Route::middleware(['auth', 'role:admin|moniteur'])
    ->prefix('training')
    ->name('training.')
    ->group(function () {
        Route::get('students/{student}/evaluation', [EvaluationController::class, 'show'])->name('evaluation.show');
        Route::post('students/{student}/evaluation', [EvaluationController::class, 'store'])->name('evaluation.store');
    });

Route::middleware(['auth', 'role:eleve'])
    ->prefix('quiz')
    ->name('quiz.')
    ->group(function () {
        Route::get('/', [QuizController::class, 'index'])->name('index');
        Route::post('/', [QuizController::class, 'store'])->name('store');
        Route::get('results', [QuizController::class, 'results'])->name('results');
    });

Route::middleware(['auth', 'role:admin|moniteur'])
    ->get('quiz/students/{student}/results', [QuizController::class, 'studentResults'])
    ->name('quiz.students.results');

Route::middleware(['auth', 'role:admin|moniteur'])
    ->prefix('fleet')
    ->name('fleet.')
    ->group(function () {
        Route::get('/', [VehicleController::class, 'index'])->name('index');
        Route::get('{vehicle}', [VehicleController::class, 'show'])->name('show');
        Route::post('{vehicle}/maintenance', [VehicleController::class, 'storeMaintenance'])->name('maintenance.store');
        Route::post('{vehicle}/fuel', [VehicleController::class, 'storeFuel'])->name('fuel.store');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('fleet')
    ->name('fleet.')
    ->group(function () {
        Route::post('/', [VehicleController::class, 'store'])->name('store');
        Route::delete('{vehicle}', [VehicleController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'role:admin|moniteur'])
    ->prefix('instructors')
    ->name('instructors.')
    ->group(function () {
        Route::get('/', [InstructorController::class, 'index'])->name('index');
        Route::get('{instructor}', [InstructorController::class, 'show'])->name('show');
        Route::post('{instructor}/availabilities', [InstructorAvailabilityController::class, 'store'])->name('availabilities.store');
        Route::delete('{instructor}/availabilities/{availability}', [InstructorAvailabilityController::class, 'destroy'])->name('availabilities.destroy');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('instructors')
    ->name('instructors.')
    ->group(function () {
        Route::post('/', [InstructorController::class, 'store'])->name('store');
        Route::patch('{instructor}', [InstructorController::class, 'update'])->name('update');
        Route::delete('{instructor}', [InstructorController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('settings')
    ->name('settings.')
    ->group(function () {
        Route::get('/', [SettingController::class, 'show'])->name('show');
        Route::patch('/', [SettingController::class, 'update'])->name('update');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('store')
    ->name('store.')
    ->group(function () {
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('crm')
    ->name('crm.')
    ->group(function () {
        Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
        Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
        Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
        Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    });

Route::middleware('auth')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('students/{student}/documents', [DocumentController::class, 'storeForStudent'])->name('students.documents.store');
    Route::post('fleet/{vehicle}/documents', [DocumentController::class, 'storeForVehicle'])->name('fleet.documents.store');
});

Route::middleware('auth')
    ->get('documents/{document}/download', [DocumentController::class, 'download'])
    ->name('documents.download');

Route::middleware(['auth', 'role:admin|superadmin'])
    ->get('audit', [AuditLogController::class, 'index'])
    ->name('audit.index');

Route::middleware(['auth', 'role:moniteur'])
    ->name('moniteur.')
    ->group(function () {
        Route::view('moniteur/dashboard', 'moniteur.dashboard')->name('dashboard');
        Route::get('moniteur/agenda', AgendaController::class)->name('agenda');
    });

Route::middleware(['auth', 'role:eleve'])
    ->name('eleve.')
    ->group(function () {
        Route::view('eleve/dashboard', 'eleve.dashboard')->name('dashboard');
        Route::get('eleve/planning', StudentPlanningController::class)->name('planning');
    });

require __DIR__.'/auth.php';
