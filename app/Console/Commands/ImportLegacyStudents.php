<?php

namespace App\Console\Commands;

use App\Domain\Finance\Enums\PaymentMethod;
use App\Domain\Finance\Services\InvoicingService;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Students\Enums\CourseType;
use App\Domain\Students\Enums\LicenseCategory;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;
use Illuminate\Console\Command;

/**
 * Ports the legacy setup/import.php script's `inscription.csv` import onto
 * the new relational schema. Where the legacy script wrote one flat
 * `paiements` row per student, this creates an Invoice (what's owed) and,
 * if anything was already received, a Payment against it - the Phase 2
 * invoice/payment split the design doc calls for. Deliberately does not
 * import the weekly schedule CSVs (etp*, ett*, Recyclage*) or the Code
 * Rousseau book-sales CSV, matching the legacy script's own documented
 * scope.
 */
class ImportLegacyStudents extends Command
{
    protected $signature = 'import:legacy-students {structure : Structure (tenant) id to import into} {path : Directory containing inscription.csv}';

    protected $description = 'Import the legacy inscription.csv export into Students/Invoices/Payments for one tenant';

    private array $stats = [
        'students_added' => 0,
        'students_skipped' => 0,
        'payments_added' => 0,
    ];

    public function handle(InvoicingService $invoicing, PaymentService $payments): int
    {
        $structure = Structure::query()->find((int) $this->argument('structure'));

        if (! $structure) {
            $this->error('Structure not found.');

            return self::FAILURE;
        }

        $file = rtrim((string) $this->argument('path'), '/').'/inscription.csv';

        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        TenantContext::set($structure);

        try {
            $this->importInscriptions($file, $invoicing, $payments);
        } finally {
            TenantContext::clear();
        }

        $this->table(
            ['Élèves créés', 'Élèves ignorés (doublon)', 'Paiements enregistrés'],
            [[$this->stats['students_added'], $this->stats['students_skipped'], $this->stats['payments_added']]],
        );

        return self::SUCCESS;
    }

    private function importInscriptions(string $file, InvoicingService $invoicing, PaymentService $payments): void
    {
        $rows = $this->readCsv($file);

        $headerRow = null;
        foreach ($rows as $i => $row) {
            if (isset($row[0]) && stripos($row[0], 'NOMS ET PRENOMS') !== false) {
                $headerRow = $i;
                break;
            }
        }

        if ($headerRow === null) {
            $this->error('inscription.csv: header row not found.');

            return;
        }

        for ($i = $headerRow + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $fullName = trim($row[0] ?? '');

            if ($fullName === '' || stripos($fullName, 'TOTAL') === 0) {
                continue;
            }

            $cours = $row[1] ?? 'Cours normal';
            $categorie = $row[2] ?? 'Permis B';
            $registeredAt = $this->parseDate($row[3] ?? '');
            $received = $this->parseAmount($row[4] ?? '0');
            $remaining = $this->parseAmount($row[5] ?? '0');
            $phone = trim($row[7] ?? '');

            [$lastName, $firstName] = $this->splitName($fullName);

            $exists = Student::query()
                ->where('last_name', $lastName)
                ->where('first_name', $firstName)
                ->where('registered_at', $registeredAt)
                ->exists();

            if ($exists) {
                $this->stats['students_skipped']++;

                continue;
            }

            $student = Student::query()->create([
                'structure_id' => TenantContext::id(),
                'last_name' => $lastName,
                'first_name' => $firstName,
                'phone' => $phone ?: null,
                'course_type' => $this->mapCourseType($cours),
                'license_category' => $this->mapLicenseCategory($categorie),
                'registered_at' => $registeredAt,
            ]);

            // lifecycle_stage is a guarded column (see
            // Student::setLifecycleStage) - imported students start
            // further along than a fresh registration, so the database
            // default ('prospect') doesn't apply here and must be set
            // explicitly, bypassing the transition guard since this is an
            // initial import value, not a transition. dossier_status is
            // left at the model's default (Incomplete, set in
            // Student::booted()'s creating() hook) since imported students
            // have no uploaded documents yet - it is purely computed by
            // DossierStatusService from document state, not from payment
            // state.
            $student->setLifecycleStage(LifecycleStage::Enrollment);
            $student->save();

            $this->stats['students_added']++;

            $total = $received + $remaining;

            if ($total <= 0) {
                continue;
            }

            $invoice = $invoicing->createForStudent($student, [
                'label' => 'Import legacy',
                'amount_due' => $total,
                'issued_at' => $registeredAt,
            ]);

            if ($received > 0) {
                $payments->record($invoice, [
                    'amount' => $received,
                    'method' => PaymentMethod::Cash,
                    'paid_at' => $registeredAt,
                ]);
                $this->stats['payments_added']++;
            }
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $rows[] = array_map(fn ($value) => trim((string) $value), $row);
        }

        fclose($handle);

        return $rows;
    }

    private function parseAmount(string $value): float
    {
        $value = preg_replace('/[^0-9,.]/u', '', $value) ?? '';

        if ($value === '' || $value === '.') {
            return 0.0;
        }

        // Legacy export uses "." as a thousands separator: "40.000" = 40000.
        $value = str_replace(['.', ','], ['', '.'], $value);

        return (float) $value;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $value)) {
            return $value;
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string} [last_name, first_name]
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim($fullName)) ?: [];
        $lastName = [];
        $firstName = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (mb_strtoupper($part, 'UTF-8') === $part) {
                $lastName[] = $part;
            } else {
                $firstName[] = $part;
            }
        }

        if (! $lastName) {
            $lastName = $parts;
            $firstName = [];
        }

        if (! $firstName) {
            $firstName = [array_pop($lastName)];
        }

        return [implode(' ', $lastName), implode(' ', $firstName)];
    }

    private function mapCourseType(string $value): CourseType
    {
        $value = strtolower($value);

        return match (true) {
            str_contains($value, '10 jours') => CourseType::Accelerated10Days,
            str_contains($value, 'accel') => CourseType::Accelerated,
            default => CourseType::Normal,
        };
    }

    private function mapLicenseCategory(string $value): LicenseCategory
    {
        $value = strtoupper($value);

        foreach (LicenseCategory::cases() as $category) {
            if (str_contains($value, $category->value)) {
                return $category;
            }
        }

        return LicenseCategory::B;
    }
}
