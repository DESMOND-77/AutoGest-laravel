<?php

namespace Database\Seeders;

use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Models\Lead;
use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Enums\PaymentMethod;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\TrainingPackage;
use App\Domain\Fleet\Enums\VehicleStatus;
use App\Domain\Fleet\Models\FuelLog;
use App\Domain\Fleet\Models\MaintenanceLog;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Instructors\Enums\InstructorStatus;
use App\Domain\Instructors\Models\Instructor;
use App\Domain\Instructors\Models\InstructorAvailability;
use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Settings\Models\Setting;
use App\Domain\Store\Enums\OrderStatus;
use App\Domain\Store\Enums\PurchaseOrderStatus;
use App\Domain\Store\Enums\StockMovementType;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\OrderItem;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\PurchaseOrderItem;
use App\Domain\Store\Models\StockMovement;
use App\Domain\Store\Models\Supplier;
use App\Domain\Students\Enums\CourseType;
use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Enums\LicenseCategory;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentRegistrationLink;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\ExamResult;
use App\Domain\Training\Enums\ExamType;
use App\Domain\Training\Enums\SkillLevel;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\QuizAttempt;
use App\Domain\Training\Models\QuizAttemptAnswer;
use App\Domain\Training\Models\QuizOption;
use App\Domain\Training\Models\QuizQuestion;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Models\SkillProgress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Complete demo dataset: one active driving school with staff, a fleet, students
 * at every lifecycle stage, invoices/payments/ledger, a schedule, exams, a quiz
 * bank, a shop and CRM leads. Everything is created with an explicit
 * structure_id (no tenant context is active when seeding).
 *
 * Logins (password for all: "password"): superadmin@demo.test (no tenant),
 * admin@demo.test, moniteur1@demo.test .. moniteur4@demo.test,
 * eleve1@demo.test .. eleve8@demo.test.
 */
class DemoSeeder extends Seeder
{
    private Structure $structure;

    /** @var Collection<int, User> */
    private Collection $instructorUsers;

    /** @var Collection<int, Vehicle> */
    private Collection $vehicles;

    /** @var Collection<int, Student> */
    private Collection $students;

    private User $admin;

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        mt_srand(42);
        fake()->seed(42);

        $this->seedTenancyAndUsers();
        $this->seedSettings();
        $this->seedInstructors();
        $this->seedFleet();
        $packages = $this->seedTrainingPackages();
        $this->seedStudents();
        $this->seedRegistrationAndDocuments();
        $this->seedFinance($packages);
        $this->seedScheduling();
        $this->seedTraining();
        $this->seedStore();
        $this->seedCrmAndRecyclage();
    }

    private function seedTenancyAndUsers(): void
    {
        $this->structure = Structure::create([
            'name' => 'Auto-École Démo Libreville',
            'email' => 'contact@demo.test',
            'phone' => '+241 01 23 45 67',
            'address' => 'Boulevard Triomphal, Libreville',
            'status' => StructureStatus::Active,
        ]);

        User::factory()->create([
            'structure_id' => null,
            'name' => 'Super Admin',
            'email' => 'superadmin@demo.test',
        ])->assignRole('superadmin');

        $this->admin = User::factory()->create([
            'structure_id' => $this->structure->id,
            'name' => 'Directeur Démo',
            'email' => 'admin@demo.test',
        ]);
        $this->admin->assignRole('admin');
    }

    private function seedSettings(): void
    {
        Setting::create([
            'structure_id' => $this->structure->id,
            'display_name' => $this->structure->name,
            'address' => $this->structure->address,
            'phone' => $this->structure->phone,
            'support_email' => $this->structure->email,
            'timezone' => 'Africa/Libreville',
            'currency' => 'FCFA',
            'default_theme' => 'light',
        ]);
    }

    private function seedInstructors(): void
    {
        $names = ['Jean-Claude Ndong', 'Marie Obame', 'Patrick Mba', 'Sandrine Nzé'];

        $this->instructorUsers = collect($names)->map(function (string $name, int $i) {
            $user = User::factory()->create([
                'structure_id' => $this->structure->id,
                'name' => $name,
                'email' => 'moniteur'.($i + 1).'@demo.test',
            ]);
            $user->assignRole('moniteur');

            $instructor = Instructor::create([
                'structure_id' => $this->structure->id,
                'user_id' => $user->id,
                'license_number' => 'MON-'.(1001 + $i),
                'specialties' => $i === 3 ? ['B', 'A'] : ['B'],
                'hire_date' => now()->subYears(5 - $i)->toDateString(),
                'status' => $i === 3 ? InstructorStatus::Inactive : InstructorStatus::Active,
            ]);

            foreach ([1, 2, 3, 4, 5] as $day) {
                InstructorAvailability::create([
                    'structure_id' => $this->structure->id,
                    'instructor_id' => $instructor->id,
                    'day_of_week' => $day,
                    'starts_at' => $day === 5 ? '08:00' : '07:30',
                    'ends_at' => $day === 5 ? '12:00' : '17:00',
                ]);
            }

            return $user;
        });
    }

    private function seedFleet(): void
    {
        $fleet = [
            ['Toyota', 'Yaris', 'AB-123-GA', VehicleStatus::Active, 'Blanc', now()->addMonths(5), now()->addMonths(8)],
            ['Renault', 'Clio', 'CD-456-GA', VehicleStatus::Active, 'Rouge', now()->addDays(12), now()->addMonths(3)],
            ['Peugeot', '208', 'EF-789-GA', VehicleStatus::Maintenance, 'Gris', now()->addMonths(2), now()->subDays(5)],
            ['Toyota', 'Corolla', 'GH-012-GA', VehicleStatus::Active, 'Bleu', now()->addMonths(9), now()->addMonths(11)],
            ['Yamaha', 'YBR 125', 'IJ-345-GA', VehicleStatus::OutOfService, 'Noir', now()->subDays(20), now()->addMonths(1)],
        ];

        $this->vehicles = collect($fleet)->map(function (array $v, int $i) {
            $vehicle = Vehicle::create([
                'structure_id' => $this->structure->id,
                'plate' => $v[2],
                'brand' => $v[0],
                'model' => $v[1],
                'year' => 2018 + $i,
                'category' => $v[0] === 'Yamaha' ? 'A' : 'B',
                'color' => $v[4],
                'mileage' => 15000 + $i * 12000,
                'status' => $v[3],
                'technical_inspection_expires_at' => $v[5]->toDateString(),
                'insurance_expires_at' => $v[6]->toDateString(),
            ]);

            foreach (range(1, 4) as $n) {
                FuelLog::create([
                    'structure_id' => $this->structure->id,
                    'vehicle_id' => $vehicle->id,
                    'liters' => fake()->numberBetween(25, 45),
                    'cost' => fake()->numberBetween(18, 33) * 1000,
                    'mileage' => $vehicle->mileage - $n * 600,
                    'filled_on' => now()->subWeeks($n * 2)->toDateString(),
                ]);
            }

            MaintenanceLog::create([
                'structure_id' => $this->structure->id,
                'vehicle_id' => $vehicle->id,
                'type' => 'vidange',
                'description' => 'Vidange et filtres',
                'cost' => 45000,
                'mileage' => $vehicle->mileage - 2000,
                'performed_on' => now()->subMonths(2)->toDateString(),
                'next_due_on' => now()->addMonths(4)->toDateString(),
            ]);

            return $vehicle;
        });
    }

    /**
     * @return Collection<int, TrainingPackage>
     */
    private function seedTrainingPackages(): Collection
    {
        return collect([
            ['Forfait Standard B', 20, 'B', 220000],
            ['Forfait Accéléré B', 15, 'B', 280000],
            ['Forfait Premium B', 30, 'B', 350000],
            ['Forfait Moto A', 12, 'A', 180000],
        ])->map(fn (array $p) => TrainingPackage::create([
            'structure_id' => $this->structure->id,
            'name' => $p[0],
            'hours' => $p[1],
            'license_category' => $p[2],
            'price' => $p[3],
            'active' => true,
        ]));
    }

    private function seedStudents(): void
    {
        $stages = [
            LifecycleStage::Prospect, LifecycleStage::PreEnrollment, LifecycleStage::DossierSetup,
            LifecycleStage::Validation, LifecycleStage::Enrollment, LifecycleStage::Payment,
            LifecycleStage::TheoryCourse, LifecycleStage::TheoryCourse, LifecycleStage::MockExams,
            LifecycleStage::CodeObtained, LifecycleStage::PracticalCourse, LifecycleStage::PracticalCourse,
            LifecycleStage::PracticalCourse, LifecycleStage::ContinuousEvaluation, LifecycleStage::ReadyForExam,
            LifecycleStage::PracticalExam, LifecycleStage::LicenseObtained, LifecycleStage::LicenseObtained,
            LifecycleStage::FormerStudent, LifecycleStage::PracticalCourse,
        ];
        $activeInstructors = $this->instructorUsers->take(3)->values();

        $this->students = collect($stages)->map(function (LifecycleStage $stage, int $i) use ($activeInstructors) {
            $userId = null;
            if ($i < 8) {
                $user = User::factory()->create([
                    'structure_id' => $this->structure->id,
                    'name' => 'Élève '.($i + 1),
                    'email' => 'eleve'.($i + 1).'@demo.test',
                ]);
                $user->assignRole('eleve');
                $userId = $user->id;
            }

            $student = Student::create([
                'structure_id' => $this->structure->id,
                'user_id' => $userId,
                'instructor_id' => $stage->value === 'prospect' ? null : $activeInstructors[$i % 3]->id,
                'last_name' => fake()->lastName(),
                'first_name' => fake()->firstName(),
                'birth_date' => now()->subYears(fake()->numberBetween(18, 40))->toDateString(),
                'birth_place' => fake()->randomElement(['Libreville', 'Port-Gentil', 'Franceville', 'Oyem']),
                'phone' => '+241 06 '.fake()->numerify('## ## ##'),
                'email' => $userId ? 'eleve'.($i + 1).'@demo.test' : fake()->unique()->safeEmail(),
                'address' => 'Quartier '.fake()->randomElement(['Louis', 'Nzeng-Ayong', 'Akébé', 'Owendo']),
                'neph' => $i > 3 ? fake()->numerify('############') : null,
                'license_category' => $i % 10 === 9 ? LicenseCategory::A : LicenseCategory::B,
                'course_type' => fake()->randomElement(CourseType::cases()),
                'registered_at' => now()->subDays(200 - $i * 9)->toDateString(),
            ]);

            $student->setLifecycleStage($stage);
            $student->setDossierStatus(match (true) {
                $i < 2 => DossierStatus::Incomplete,
                $i < 3 => DossierStatus::Complete,
                $i < 4 => DossierStatus::Submitted,
                default => DossierStatus::Validated,
            });
            $student->save();

            return $student;
        });
    }

    private function seedRegistrationAndDocuments(): void
    {
        StudentRegistrationLink::create([
            'structure_id' => $this->structure->id,
            'created_by' => $this->admin->id,
            'label' => 'Lien principal',
            'token_hash' => hash('sha256', 'demo-registration-token'),
            'usage_count' => 3,
            'max_uses' => null,
            'expires_at' => now()->addDays(90),
        ]);

        $required = collect(["Carte d'identité", 'Justificatif de domicile', "Photo d'identité", 'Certificat médical'])
            ->map(fn (string $label, int $i) => RequiredDocumentType::create([
                'structure_id' => $this->structure->id,
                'label' => $label,
                'position' => $i,
                'is_active' => true,
            ]));

        $types = [DocumentType::IdCard, DocumentType::ProofOfAddress, DocumentType::Photo, DocumentType::Other];

        foreach ($this->students->skip(2)->take(14) as $student) {
            foreach ($required as $i => $requiredType) {
                $rejected = $student->id === $this->students[3]->id && $i === 1;
                Document::create([
                    'structure_id' => $this->structure->id,
                    'uploaded_by' => $student->user_id ?? $this->admin->id,
                    'documentable_type' => Student::class,
                    'documentable_id' => $student->id,
                    'type' => $types[$i],
                    'disk' => 'local',
                    'path' => 'documents/demo/'.Str::uuid().'.pdf',
                    'original_name' => Str::slug($requiredType->label).'.pdf',
                    'version' => 1,
                    'is_current' => true,
                    'required_document_type_id' => $requiredType->id,
                    'review_status' => $rejected ? DocumentReviewStatus::Rejected : DocumentReviewStatus::Approved,
                    'rejection_reason' => $rejected ? 'Document illisible, merci de le renvoyer.' : null,
                    'reviewed_by_id' => $this->admin->id,
                    'reviewed_at' => now()->subDays(10),
                ]);
            }
        }

        foreach ($this->vehicles->take(3) as $vehicle) {
            Document::create([
                'structure_id' => $this->structure->id,
                'uploaded_by' => $this->admin->id,
                'documentable_type' => Vehicle::class,
                'documentable_id' => $vehicle->id,
                'type' => DocumentType::InsuranceCertificate,
                'disk' => 'local',
                'path' => 'documents/demo/'.Str::uuid().'.pdf',
                'original_name' => 'assurance-'.$vehicle->plate.'.pdf',
                'version' => 1,
                'is_current' => true,
                'expires_at' => $vehicle->insurance_expires_at,
            ]);
        }
    }

    /**
     * @param  Collection<int, TrainingPackage>  $packages
     */
    private function seedFinance(Collection $packages): void
    {
        $methods = PaymentMethod::cases();

        foreach ($this->students->skip(4) as $i => $student) {
            $package = $student->license_category === LicenseCategory::A ? $packages[3] : $packages[$i % 3];
            $price = (int) $package->price;
            $paidShare = match ($i % 4) {
                0 => 1.0,
                1 => 0.5,
                2 => 0.75,
                default => 0.0,
            };
            $paid = (int) round($price * $paidShare);

            $invoice = Invoice::create([
                'structure_id' => $this->structure->id,
                'student_id' => $student->id,
                'training_package_id' => $package->id,
                'label' => $package->name,
                'amount_due' => $price,
                'amount_paid' => $paid,
                'status' => $paid >= $price ? InvoiceStatus::Paid : ($paid > 0 ? InvoiceStatus::Partial : InvoiceStatus::Unpaid),
                'issued_at' => $student->registered_at->toDateString(),
            ]);

            if ($paid === 0) {
                continue;
            }

            $installments = $paid === $price ? [0.5, 0.5] : [1.0];
            foreach ($installments as $share) {
                $payment = Payment::create([
                    'structure_id' => $this->structure->id,
                    'invoice_id' => $invoice->id,
                    'recorded_by' => $this->admin->id,
                    'amount' => (int) round($paid * $share),
                    'method' => $methods[($i + count($installments)) % count($methods)],
                    'paid_at' => $student->registered_at->copy()->addDays(3)->toDateString(),
                ]);

                LedgerEntry::create([
                    'structure_id' => $this->structure->id,
                    'payment_id' => $payment->id,
                    'created_by' => $this->admin->id,
                    'type' => LedgerEntryType::Income,
                    'amount' => $payment->amount,
                    'memo' => 'Paiement '.$student->fullName(),
                    'occurred_on' => $payment->paid_at->toDateString(),
                ]);
            }
        }

        $expenses = [
            ['Loyer du local', 350000], ['Salaires moniteurs', 900000], ['Carburant flotte', 120000],
            ['Assurance véhicules', 210000], ['Électricité et eau', 65000],
        ];
        foreach ($expenses as $i => [$memo, $amount]) {
            LedgerEntry::create([
                'structure_id' => $this->structure->id,
                'created_by' => $this->admin->id,
                'type' => LedgerEntryType::Expense,
                'amount' => $amount,
                'memo' => $memo,
                'occurred_on' => now()->subDays(5 + $i * 4)->toDateString(),
            ]);
        }

        LedgerEntry::create([
            'structure_id' => $this->structure->id,
            'created_by' => $this->admin->id,
            'type' => LedgerEntryType::BankDeposit,
            'amount' => 500000,
            'memo' => 'Dépôt espèces banque',
            'occurred_on' => now()->subDays(7)->toDateString(),
        ]);
        LedgerEntry::create([
            'structure_id' => $this->structure->id,
            'created_by' => $this->admin->id,
            'type' => LedgerEntryType::BankWithdrawal,
            'amount' => 150000,
            'memo' => 'Retrait caisse petites dépenses',
            'occurred_on' => now()->subDays(3)->toDateString(),
        ]);
    }

    private function seedScheduling(): void
    {
        $trainees = $this->students
            ->filter(fn (Student $s) => $s->instructor_id !== null && $s->lifecycle_stage->value !== 'former_student')
            ->values();
        $drivable = $this->vehicles->where('status', VehicleStatus::Active)->values();
        $slots = [['08:00', '09:00'], ['09:30', '10:30'], ['14:00', '15:00'], ['15:30', '16:30']];

        foreach (range(-14, 14) as $offset) {
            $date = Carbon::today()->addDays($offset);
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($slots as $s => [$start, $end]) {
                $student = $trainees[($offset + 14 + $s * 3) % $trainees->count()];
                $past = $offset < 0;
                $type = match ($s) {
                    0 => SessionType::Theoretical,
                    1 => SessionType::Code,
                    default => SessionType::Practical,
                };

                LessonSession::create([
                    'structure_id' => $this->structure->id,
                    'student_id' => $student->id,
                    'instructor_id' => $student->instructor_id,
                    'vehicle_id' => $type === SessionType::Practical ? $drivable[$s % $drivable->count()]->id : null,
                    'type' => $type,
                    'scheduled_date' => $date->toDateString(),
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'location' => $type === SessionType::Practical ? 'Circuit Louis' : 'Salle de cours',
                    'presence' => $past
                        ? fake()->randomElement([PresenceStatus::Present, PresenceStatus::Present, PresenceStatus::Present, PresenceStatus::Absent, PresenceStatus::Cancelled])
                        : PresenceStatus::Planned,
                    'notes' => null,
                ]);
            }
        }
    }

    private function seedTraining(): void
    {
        $skills = collect([
            ['Maîtrise du véhicule', ['Réglage du poste de conduite', 'Démarrage et arrêt', 'Passage des vitesses']],
            ['Circulation', ['Respect des priorités', 'Changement de direction', 'Insertion dans la circulation']],
            ['Manœuvres', ['Créneau', 'Marche arrière', 'Demi-tour']],
        ])->flatMap(fn (array $group, int $g) => collect($group[1])->map(fn (string $label, int $i) => Skill::create([
            'structure_id' => $this->structure->id,
            'code' => 'SK-'.($g + 1).($i + 1),
            'label' => $label,
            'category' => $group[0],
            'position' => $g * 3 + $i,
        ])));

        $practical = $this->students->filter(fn (Student $s) => in_array($s->lifecycle_stage, [
            LifecycleStage::PracticalCourse, LifecycleStage::ContinuousEvaluation, LifecycleStage::ReadyForExam,
            LifecycleStage::PracticalExam, LifecycleStage::LicenseObtained, LifecycleStage::FormerStudent,
        ], true));

        foreach ($practical as $i => $student) {
            $acquired = in_array($student->lifecycle_stage, [LifecycleStage::LicenseObtained, LifecycleStage::FormerStudent, LifecycleStage::ReadyForExam, LifecycleStage::PracticalExam], true);
            foreach ($skills as $n => $skill) {
                $level = $acquired || $n < $i % 5 ? SkillLevel::Acquired : ($n === $i % 5 ? SkillLevel::InProgress : SkillLevel::NotStarted);
                SkillProgress::create([
                    'structure_id' => $this->structure->id,
                    'student_id' => $student->id,
                    'skill_id' => $skill->id,
                    'instructor_id' => $student->instructor_id,
                    'level' => $level,
                    'validated_at' => $level === SkillLevel::Acquired ? now()->subDays(30 - $n)->toDateString() : null,
                    'comment' => null,
                ]);
            }
        }

        $examStudents = $this->students->filter(fn (Student $s) => $s->lifecycle_stage->value !== 'prospect' && $s->id > $this->students[5]->id)->values();
        foreach ($examStudents as $i => $student) {
            $passedCode = $student->lifecycle_stage !== LifecycleStage::TheoryCourse && $student->lifecycle_stage !== LifecycleStage::MockExams;
            Exam::create([
                'structure_id' => $this->structure->id,
                'student_id' => $student->id,
                'type' => ExamType::Code,
                'exam_date' => now()->subDays(90 - $i * 3)->toDateString(),
                'location' => 'Centre d\'examen de Libreville',
                'inspector' => 'M. Oyane',
                'result' => $passedCode ? ExamResult::Passed : ExamResult::Failed,
                'fault_count' => $passedCode ? fake()->numberBetween(0, 5) : fake()->numberBetween(6, 12),
                'comment' => null,
            ]);
        }

        foreach ($this->students->filter(fn (Student $s) => in_array($s->lifecycle_stage, [LifecycleStage::PracticalExam, LifecycleStage::LicenseObtained, LifecycleStage::FormerStudent, LifecycleStage::ReadyForExam], true)) as $i => $student) {
            $done = $student->lifecycle_stage !== LifecycleStage::ReadyForExam;
            Exam::create([
                'structure_id' => $this->structure->id,
                'student_id' => $student->id,
                'type' => ExamType::Driving,
                'exam_date' => $done ? now()->subDays(20 + $i * 5)->toDateString() : now()->addDays(10)->toDateString(),
                'location' => 'Centre d\'examen de Libreville',
                'inspector' => 'Mme Nguema',
                'result' => $done ? ($i === 0 ? ExamResult::Failed : ExamResult::Passed) : ExamResult::Pending,
                'fault_count' => $done ? ($i === 0 ? 14 : 3) : null,
                'comment' => null,
            ]);
        }

        $this->seedQuizBank();
    }

    private function seedQuizBank(): void
    {
        $bank = [
            ['priorites', 'Quelle est la règle de priorité à un carrefour sans signalisation ?', ['Priorité à droite', 'Priorité à gauche', 'Priorité au plus rapide'], 0],
            ['signalisation', 'Que signifie un panneau triangulaire à bordure rouge ?', ['Une interdiction', 'Un danger', 'Une obligation'], 1],
            ['vitesse', 'Quelle est la vitesse maximale en agglomération ?', ['30 km/h', '50 km/h', '90 km/h'], 1],
            ['securite', 'Quelle distance de sécurité faut-il garder sur route sèche ?', ['Deux secondes', 'Une seconde', 'Dix secondes'], 0],
            ['priorites', 'Un véhicule d\'urgence avec gyrophare approche, que faites-vous ?', ['Je accélère', 'Je me range et je le laisse passer', 'Je m\'arrête au milieu'], 1],
            ['securite', 'La ceinture de sécurité est obligatoire :', ['Uniquement à l\'avant', 'Pour tous les passagers', 'Hors agglomération seulement'], 1],
        ];

        $questions = collect($bank)->map(function (array $q) {
            $question = QuizQuestion::create([
                'structure_id' => $this->structure->id,
                'prompt' => $q[1],
                'category' => $q[0],
            ]);
            $options = collect($q[2])->map(fn (string $text, int $i) => QuizOption::create([
                'question_id' => $question->id,
                'text' => $text,
                'is_correct' => $i === $q[3],
            ]));

            return ['question' => $question, 'options' => $options];
        });

        $quizStudents = $this->students->filter(fn (Student $s) => in_array($s->lifecycle_stage, [
            LifecycleStage::TheoryCourse, LifecycleStage::MockExams, LifecycleStage::CodeObtained, LifecycleStage::PracticalCourse,
        ], true))->take(6);

        foreach ($quizStudents as $i => $student) {
            $score = 0;
            $attempt = QuizAttempt::create([
                'structure_id' => $this->structure->id,
                'student_id' => $student->id,
                'score' => 0,
                'total_questions' => $questions->count(),
                'completed_at' => now()->subDays($i + 1),
            ]);

            foreach ($questions as $q => $entry) {
                $chosen = ($q + $i) % 4 === 0
                    ? $entry['options']->firstWhere('is_correct', false)
                    : $entry['options']->firstWhere('is_correct', true);
                $score += $chosen->is_correct ? 1 : 0;
                QuizAttemptAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $entry['question']->id,
                    'option_id' => $chosen->id,
                ]);
            }

            $attempt->update(['score' => $score]);
        }
    }

    private function seedStore(): void
    {
        $suppliers = collect([
            ['Librairie Gabonaise', '+241 01 70 11 22', 'contact@librairie.test'],
            ['Auto Équipement Gabon', '+241 01 72 33 44', 'ventes@autoequip.test'],
        ])->map(fn (array $s) => Supplier::create([
            'structure_id' => $this->structure->id,
            'name' => $s[0],
            'phone' => $s[1],
            'email' => $s[2],
        ]));

        $catalog = [
            ['Code de la route 2026', 'livre', 8000, 5000, 40, 10, 0],
            ['Livret d\'apprentissage', 'livre', 3000, 1800, 60, 15, 0],
            ['Test blanc code (série 10)', 'livre', 5000, 3000, 8, 10, 0],
            ['Gilet de sécurité', 'equipement', 4500, 2500, 25, 10, 1],
            ['Triangle de signalisation', 'equipement', 6000, 3500, 4, 8, 1],
            ['Casque moto', 'equipement', 25000, 16000, 12, 5, 1],
        ];

        $products = collect($catalog)->map(fn (array $p, int $i) => Product::create([
            'structure_id' => $this->structure->id,
            'supplier_id' => $suppliers[$p[6]]->id,
            'name' => $p[0],
            'sku' => 'PRD-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
            'barcode' => fake()->unique()->numerify('############'),
            'category' => $p[1],
            'price' => $p[2],
            'cost_price' => $p[3],
            'stock_quantity' => $p[4],
            'reorder_threshold' => $p[5],
            'active' => true,
        ]));

        foreach ($products as $product) {
            StockMovement::create([
                'structure_id' => $this->structure->id,
                'product_id' => $product->id,
                'type' => StockMovementType::Reception,
                'quantity' => $product->stock_quantity + 5,
                'reference' => 'Stock initial',
                'occurred_at' => now()->subMonths(2),
            ]);
        }

        $purchaseOrders = [
            [$suppliers[0], PurchaseOrderStatus::Received, [[0, 30, 30], [1, 40, 40]]],
            [$suppliers[1], PurchaseOrderStatus::PartiallyReceived, [[3, 20, 10], [4, 10, 0]]],
            [$suppliers[1], PurchaseOrderStatus::Pending, [[5, 10, 0]]],
        ];
        foreach ($purchaseOrders as $i => [$supplier, $status, $lines]) {
            $po = PurchaseOrder::create([
                'structure_id' => $this->structure->id,
                'supplier_id' => $supplier->id,
                'status' => $status,
                'ordered_at' => now()->subDays(40 - $i * 12)->toDateString(),
            ]);
            foreach ($lines as [$productIndex, $qty, $received]) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $products[$productIndex]->id,
                    'quantity' => $qty,
                    'quantity_received' => $received,
                ]);
            }
        }

        $statuses = [OrderStatus::Fulfilled, OrderStatus::Fulfilled, OrderStatus::Confirmed, OrderStatus::Pending, OrderStatus::Cancelled];
        foreach ($statuses as $i => $status) {
            $student = $this->students[6 + $i];
            $lines = [[$products[$i % 3], 1 + $i % 2], [$products[3 + $i % 3], 1]];
            $total = collect($lines)->sum(fn (array $l) => (float) $l[0]->price * $l[1]);

            $order = Order::create([
                'structure_id' => $this->structure->id,
                'student_id' => $student->id,
                'customer_name' => $student->fullName(),
                'status' => $status,
                'total' => $total,
                'ordered_at' => now()->subDays(15 - $i * 3)->toDateString(),
            ]);

            foreach ($lines as [$product, $qty]) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                ]);

                if ($status === OrderStatus::Fulfilled) {
                    StockMovement::create([
                        'structure_id' => $this->structure->id,
                        'product_id' => $product->id,
                        'type' => StockMovementType::Sale,
                        'quantity' => -$qty,
                        'reference' => 'Commande #'.$order->id,
                        'occurred_at' => $order->ordered_at,
                    ]);
                }
            }
        }
    }

    private function seedCrmAndRecyclage(): void
    {
        $sources = ['Facebook', 'Bouche-à-oreille', 'Affiche', 'Site web', 'WhatsApp'];

        foreach (LeadStatus::cases() as $i => $status) {
            foreach (range(0, 1) as $n) {
                Lead::create([
                    'structure_id' => $this->structure->id,
                    'converted_student_id' => $status === LeadStatus::Converted ? $this->students[$n]->id : null,
                    'name' => fake()->name(),
                    'phone' => '+241 07 '.fake()->numerify('## ## ##'),
                    'email' => fake()->safeEmail(),
                    'source' => $sources[($i + $n) % count($sources)],
                    'status' => $status,
                    'notes' => $status === LeadStatus::Lost ? 'Parti dans une autre auto-école.' : null,
                ]);
            }
        }

        foreach (range(1, 6) as $i) {
            RecyclageEntry::create([
                'structure_id' => $this->structure->id,
                'full_name' => fake()->name(),
                'motif' => $i % 2 === 0 ? RecyclageMotif::Recyclage : RecyclageMotif::Test,
                'phone' => '+241 06 '.fake()->numerify('## ## ##'),
                'instructor_id' => $this->instructorUsers[$i % 3]->id,
                'session_date' => now()->subDays($i * 4)->toDateString(),
                'amount' => $i % 2 === 0 ? 25000 : 15000,
            ]);
        }
    }
}
