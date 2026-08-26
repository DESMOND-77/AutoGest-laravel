<?php

namespace App\Domain\Documents\Http\Requests;

use App\Domain\Documents\Models\Document;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Unlike StoreDocumentRequest (still used for Vehicle uploads, categorized
 * by the generic DocumentType enum), a document an admin deposits for a
 * student is always a dossier piece - the "type" the admin picks is one of
 * the tenant's configured RequiredDocumentType rows, not a DocumentType
 * case. Tenant ownership of the id is enforced in the controller by
 * resolving it through RequiredDocumentType's own tenant-scoped query
 * (BelongsToTenant), not here.
 */
class StoreStudentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
    }

    public function rules(): array
    {
        return [
            'required_document_type_id' => ['required', 'integer'],
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,webp'],
        ];
    }
}
