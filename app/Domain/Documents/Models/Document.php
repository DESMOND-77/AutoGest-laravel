<?php

namespace App\Domain\Documents\Models;

use App\Domain\Documents\Database\Factories\DocumentFactory;
use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Models\User;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return DocumentFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'uploaded_by',
        'documentable_type',
        'documentable_id',
        'type',
        'disk',
        'path',
        'original_name',
        'version',
        'is_current',
        'expires_at',
        'required_document_type_id',
        'review_status',
        'rejection_reason',
        'reviewed_by_id',
        'reviewed_at',
    ];

    protected $casts = [
        'type' => DocumentType::class,
        'is_current' => 'boolean',
        'expires_at' => 'date',
        'review_status' => DocumentReviewStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function requiredDocumentType(): BelongsTo
    {
        return $this->belongsTo(RequiredDocumentType::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
