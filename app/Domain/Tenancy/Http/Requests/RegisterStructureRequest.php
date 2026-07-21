<?php

namespace App\Domain\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:200'],
            'school_phone' => ['nullable', 'string', 'max:30'],
            'admin_name' => ['required', 'string', 'max:150'],
            // No global uniqueness check: emails are unique per tenant
            // (unique(structure_id, email)), and this signup always creates
            // a brand-new tenant, so colliding with another school's admin
            // email is expected and allowed.
            'admin_email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
