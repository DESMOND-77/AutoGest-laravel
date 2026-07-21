<?php

namespace App\Domain\Training\Http\Requests;

use App\Domain\Training\Models\Skill;
use Illuminate\Foundation\Http\FormRequest;

class StoreSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Skill::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20'],
            'label' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
        ];
    }
}
