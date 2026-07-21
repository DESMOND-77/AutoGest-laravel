<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Database\Factories\SupplierFactory;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return SupplierFactory::new();
    }

    protected $fillable = ['structure_id', 'name', 'phone', 'email'];
}
