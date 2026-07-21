<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Database\Factories\StructureFactory;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Structure extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return StructureFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'logo',
        'status',
    ];

    protected $casts = [
        'status' => StructureStatus::class,
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
