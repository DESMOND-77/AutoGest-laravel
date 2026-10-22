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
use App\Domain\Notifications\Contracts\SmsGateway;
use App\Domain\Notifications\Services\LogSmsGateway;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Domain\Recyclage\Policies\RecyclageEntryPolicy;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Scheduling\Policies\LessonSessionPolicy;
use App\Domain\Scheduling\Repositories\EloquentLessonSessionRepository;
use App\Domain\Scheduling\Repositories\LessonSessionRepositoryInterface;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\Policies\SettingPolicy;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\Supplier;
use App\Domain\Store\Policies\OrderPolicy;
use App\Domain\Store\Policies\ProductPolicy;
use App\Domain\Store\Policies\PurchaseOrderPolicy;
use App\Domain\Store\Policies\SupplierPolicy;
use App\Domain\Students\Events\StudentEmailVerified;
use App\Domain\Students\Events\StudentStageChanged;
use App\Domain\Students\Listeners\ActivateStudentAfterEmailVerification;
use App\Domain\Students\Listeners\LogStageChange;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentRegistrationLink;
use App\Domain\Students\Policies\RequiredDocumentTypePolicy;
use App\Domain\Students\Policies\StudentPolicy;
use App\Domain\Students\Policies\StudentRegistrationLinkPolicy;
use App\Domain\Students\Repositories\EloquentStudentRepository;
use App\Domain\Students\Repositories\StudentRepositoryInterface;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Tenancy\Policies\StructurePolicy;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\QuizAttempt;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Policies\ExamPolicy;
use App\Domain\Training\Policies\QuizAttemptPolicy;
use App\Domain\Training\Policies\SkillPolicy;
use App\Domain\Users\Policies\UserPolicy;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        // 'log' is the only driver until a real Gabonese SMS provider is
        // chosen (see SmsGateway's docblock) - swapping it is a match()
        // arm here, not a rewrite of any call site.
        $this->app->bind(SmsGateway::class, fn () => match (config('services.sms.driver', 'log')) {
            default => new LogSmsGateway,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(StudentRegistrationLink::class, StudentRegistrationLinkPolicy::class);
        Gate::policy(RequiredDocumentType::class, RequiredDocumentTypePolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(TrainingPackage::class, TrainingPackagePolicy::class);
        Gate::policy(LessonSession::class, LessonSessionPolicy::class);
        Gate::policy(Skill::class, SkillPolicy::class);
        Gate::policy(Exam::class, ExamPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Instructor::class, InstructorPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(QuizAttempt::class, QuizAttemptPolicy::class);
        Gate::policy(Structure::class, StructurePolicy::class);
        Gate::policy(RecyclageEntry::class, RecyclageEntryPolicy::class);

        // The official brand lockup lives at components/brand/logo.blade.php
        // (its own path so the design-system grep guard can allow-list the
        // dark:hidden/dark:block theme swap). Alias it so the rest of the
        // app can call the flat <x-brand-logo /> tag.
        Blade::component('components.brand.logo', 'brand-logo');

        // RecordVehicleExpenseInLedger lives in app/Listeners, where Laravel's
        // event auto-discovery already finds it by its handle(VehicleExpenseRecorded)
        // type-hint - registering it again here would fire it twice.
        Event::listen(StudentStageChanged::class, LogStageChange::class);
        Event::listen(StudentEmailVerified::class, ActivateStudentAfterEmailVerification::class);

        // The numeric 'throttle:N,1' syntax keys by the authenticated user
        // once one exists on the request, not by IP. The public
        // registration endpoint logs the visitor in as part of a
        // successful submission, so a naive 'throttle:6,1' would silently
        // stop limiting by IP after the first success (each new account is
        // a fresh key) - defeating the anti-spam/brute-force intent these
        // routes document (§33-34, §51 of the spec). Force IP-based keying
        // explicitly instead.
        RateLimiter::for('public-registration-lookup', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('public-registration-submit', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
    }
}
