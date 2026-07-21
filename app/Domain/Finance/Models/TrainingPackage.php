<?php

namespace App\Domain\Finance\Models;

use App\Domain\Finance\Database\Factories\TrainingPackageFactory;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingPackage extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return TrainingPackageFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'name',
        'description',
        'hours',
        'license_category',
        'price',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
    ];
}
