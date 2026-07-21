<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\TrainingPackage;
use App\Domain\Finance\Repositories\InvoiceRepositoryInterface;
use App\Domain\Students\Models\Student;

class InvoicingService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices,
    ) {}

    public function createForStudent(Student $student, array $data): Invoice
    {
        $package = isset($data['training_package_id'])
            ? TrainingPackage::query()->find($data['training_package_id'])
            : null;

        return $this->invoices->create([
            'student_id' => $student->id,
            'training_package_id' => $package?->id,
            'label' => $data['label'] ?? $package?->name ?? 'Facture',
            'amount_due' => $data['amount_due'] ?? $package?->price ?? 0,
            'amount_paid' => 0,
            'status' => InvoiceStatus::Unpaid,
            'issued_at' => $data['issued_at'] ?? now()->toDateString(),
        ]);
    }
}
