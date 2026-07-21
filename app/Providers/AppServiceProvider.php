<?php

namespace App\Providers;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);

        Event::listen(StudentStageChanged::class, LogStageChange::class);
    }
}
