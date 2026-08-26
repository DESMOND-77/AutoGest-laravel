<?php

namespace App\Domain\Students\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A student's dossier piece is the one file-upload surface this whole app
 * exposes to an unprivileged, self-service actor (an eleve, not staff), so
 * it's validated strictly on both ends:
 * - `mimes` checks the extension against Symfony's extension→MIME map;
 * - `mimetypes` independently checks the file's actual detected content
 *   type (via finfo), so a renamed .exe or .php can't pass just because
 *   someone gave it a .pdf extension — the two rules catch different
 *   spoofing attempts and are both needed.
 * - `min:1` rejects an empty (0 KB) upload with its own message, rather
 *   than letting it fall through as a generic file-type failure.
 *
 * No lang/fr/validation.php exists anywhere in this app (APP_LOCALE=fr,
 * but Laravel's validator falls back to its bundled English strings for
 * anything not explicitly overridden) — messages() below are written out
 * in full so a student always sees a specific, French, actionable reason.
 */
class UploadDossierDocumentRequest extends FormRequest
{
    private const MAX_KILOBYTES = 5120;

    public function authorize(): bool
    {
        return $this->user()?->hasRole('eleve') ?? false;
    }

    public function rules(): array
    {
        return [
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
            'file.required' => 'Veuillez sélectionner un fichier à déposer.',
            'file.file' => 'Le fichier envoyé est invalide.',
            'file.min' => 'Ce fichier semble vide ou corrompu.',
            'file.max' => 'Ce fichier est trop volumineux (5 Mo maximum).',
            'file.mimes' => 'Format de fichier non autorisé. Formats acceptés : PDF, JPG, PNG ou WEBP.',
            'file.mimetypes' => 'Le contenu du fichier ne correspond à aucun format autorisé (PDF, JPG, PNG ou WEBP).',
            // Laravel's own implicit rule, added automatically whenever a
            // file's PHP upload error code isn't UPLOAD_ERR_OK — most often
            // because the server's own upload_max_filesize/post_max_size
            // rejected it before this request's rules ever ran. Without
            // this override the raw, untranslated "validation.uploaded" key
            // is what a user sees (there's no lang/fr/validation.php in
            // this app to supply a default).
            'file.uploaded' => 'Le fichier n\'a pas pu être envoyé — il est peut-être trop volumineux pour le serveur. Réessayez avec un fichier de 5 Mo maximum.',
        ];
    }
}
