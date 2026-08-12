<?php

namespace App\Providers;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Audit\Policies\AuditLogPolicy;
use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Policies\LeadPolicy;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Policies\DocumentPolicy;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\TrainingPackage;
use App\Domain\Finance\Policies\InvoicePolicy;
use App\Domain\Finance\Policies\PaymentPolicy;
use App\Domain\Finance\Policies\TrainingPackagePolicy;
use App\Domain\Finance\Repositories\EloquentInvoiceRepository;
use App\Domain\Finance\Repositories\InvoiceRepositoryInterface;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fleet\Policies\VehiclePolicy;
use App\Domain\Instructors\Models\Instructor;
use App\Domain\Instructors\Policies\InstructorPolicy;
use App\Domain\Instructors\Repositories\EloquentInstructorRepository;
use App\Domain\Instructors\Repositories\InstructorRepositoryInterface;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Scheduling\Policies\LessonSessionPolicy;
use App\Domain\Scheduling\Repositories\EloquentLessonSessionRepository;
use App\Domain\Scheduling\Repositories\LessonSessionRepositoryInterface;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\Policies\SettingPolicy;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Policies\OrderPolicy;
use App\Domain\Store\Policies\ProductPolicy;
use App\Domain\Students\Events\StudentStageChanged;
use App\Domain\Students\Listeners\LogStageChange;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Policies\StudentPolicy;
use App\Domain\Students\Repositories\EloquentStudentRepository;
use App\Domain\Students\Repositories\StudentRepositoryInterface;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\QuizAttempt;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Policies\ExamPolicy;
use App\Domain\Training\Policies\QuizAttemptPolicy;
use App\Domain\Training\Policies\SkillPolicy;
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
        $this->app->bind(LessonSessionRepositoryInterface::class, EloquentLessonSessionRepository::class);
        $this->app->bind(InstructorRepositoryInterface::class, EloquentInstructorRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(TrainingPackage::class, TrainingPackagePolicy::class);
        Gate::policy(LessonSession::class, LessonSessionPolicy::class);
        Gate::policy(Skill::class, SkillPolicy::class);
        Gate::policy(Exam::class, ExamPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Instructor::class, InstructorPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(QuizAttempt::class, QuizAttemptPolicy::class);

        // RecordVehicleExpenseInLedger lives in app/Listeners, where Laravel's
        // event auto-discovery already finds it by its handle(VehicleExpenseRecorded)
        // type-hint — registering it again here would fire it twice.
        Event::listen(StudentStageChanged::class, LogStageChange::class);
    }
}
