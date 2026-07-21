<?php

namespace App\Domain\Documents\Database\Factories;

use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'documentable_type' => Student::class,
            'documentable_id' => Student::factory(),
            'type' => DocumentType::Other,
            'disk' => 'local',
            'path' => 'documents/'.$this->faker->uuid().'.pdf',
            'original_name' => 'document.pdf',
            'version' => 1,
            'is_current' => true,
        ];
    }
}
