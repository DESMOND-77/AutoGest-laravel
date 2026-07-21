<?php

namespace App\Domain\Finance\Repositories;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Students\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InvoiceRepositoryInterface
{
    public function paginate(int $perPage = 20): LengthAwarePaginator;

    public function forStudent(Student $student): Collection;

    public function create(array $data): Invoice;
}
