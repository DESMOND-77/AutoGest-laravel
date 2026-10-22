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
 *
 * The file rules mirror UploadDossierDocumentRequest exactly (same kind of
 * document, just deposited by an admin instead of the student) — `mimes`
 * checks the extension, `mimetypes` independently checks the file's actual
 * detected content type so a renamed file can't pass on extension alone,
 * and `min:1` rejects an empty upload with its own message. messages() is
 * spelled out because no lang/fr/validation.php exists in this app
 * (APP_LOCALE=fr, but Laravel falls back to its bundled English strings
 * for anything not explicitly overridden).
 */
class StoreStudentDocumentRequest extends FormRequest
{
    private const MAX_KILOBYTES = 5120;

    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
    }

    public function rules(): array
    {
        return [
            'required_document_type_id' => ['required', 'integer'],
            'file' => [
                'required',
                'file',
                'min:1',
                'max:'.self::MAX_KILOBYTES,
                'mimes:pdf,jpg,jpeg,png,webp',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'required_document_type_id.required' => 'Veuillez choisir la pièce à déposer.',
            'file.required' => 'Veuillez sélectionner un fichier à déposer.',
            'file.file' => 'Le fichier envoyé est invalide.',
            'file.min' => 'Ce fichier semble vide ou corrompu.',
            'file.max' => 'Ce fichier est trop volumineux (5 Mo maximum).',
            'file.mimes' => 'Format de fichier non autorisé. Formats acceptés : PDF, JPG, PNG ou WEBP.',
            'file.mimetypes' => 'Le contenu du fichier ne correspond à aucun format autorisé (PDF, JPG, PNG ou WEBP).',
            // Laravel's own implicit rule — see UploadDossierDocumentRequest's
            // docblock for why this needs an explicit override.
            'file.uploaded' => 'Le fichier n\'a pas pu être envoyé — il est peut-être trop volumineux pour le serveur. Réessayez avec un fichier de 5 Mo maximum.',
        ];
    }
}
