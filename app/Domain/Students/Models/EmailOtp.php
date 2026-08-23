<?php

namespace App\Domain\Students\Models;

use App\Domain\Students\Database\Factories\EmailOtpFactory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailOtp extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return EmailOtpFactory::new();
    }

    protected $fillable = [
        'user_id',
        'code_hash',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
