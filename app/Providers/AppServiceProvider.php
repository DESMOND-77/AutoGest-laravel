<?php

namespace App\Providers;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\TrainingPackage;
use App\Domain\Finance\Policies\InvoicePolicy;
use App\Domain\Finance\Policies\TrainingPackagePolicy;
use App\Domain\Finance\Repositories\EloquentInvoiceRepository;
use App\Domain\Finance\Repositories\InvoiceRepositoryInterface;
use App\Domain\Students\Events\StudentStageChanged;
use App\Domain\Students\Listeners\LogStageChange;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Policies\StudentPolicy;
use App\Domain\Students\Repositories\EloquentStudentRepository;
use App\Domain\Students\Repositories\StudentRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(StudentRepositoryInterface::class, EloquentStudentRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(TrainingPackage::class, TrainingPackagePolicy::class);

        Event::listen(StudentStageChanged::class, LogStageChange::class);
    }
}
