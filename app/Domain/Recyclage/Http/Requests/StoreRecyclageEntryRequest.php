<?php

namespace App\Domain\Recyclage\Http\Requests;

use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Domain\Recyclage\Models\RecyclageEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreRecyclageEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', RecyclageEntry::class);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'motif' => ['required', new Enum(RecyclageMotif::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'instructor_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('structure_id', $this->user()->structure_id),
            ],
            'session_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
