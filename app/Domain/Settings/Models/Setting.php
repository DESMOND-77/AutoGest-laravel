<?php

namespace App\Domain\Settings\Models;

use App\Domain\Settings\Database\Factories\SettingFactory;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per tenant - a singleton config record, not a list. Resolve it via
 * SettingController::forTenant() (find-or-create), never Setting::create()
 * directly, so a school never ends up with two rows.
 */
class Setting extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return SettingFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'display_name',
        'address',
        'phone',
        'support_email',
        'timezone',
        'currency',
        'default_theme',
        'notification_preferences',
    ];

    protected $casts = [
        'notification_preferences' => 'array',
    ];
}
