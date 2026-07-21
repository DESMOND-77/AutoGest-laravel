<?php

namespace App\Domain\Finance\Repositories;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Students\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Invoice::query()->with('student')->latest('issued_at')->paginate($perPage);
    }

    public function forStudent(Student $student): Collection
    {
        return Invoice::query()->where('student_id', $student->id)->latest('issued_at')->get();
    }

    public function create(array $data): Invoice
    {
        return Invoice::query()->create($data);
    }
}
