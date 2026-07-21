<?php

namespace App\Domain\Training\Models;

use App\Domain\Training\Database\Factories\SkillFactory;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return SkillFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'code',
        'label',
        'category',
        'position',
    ];
}
