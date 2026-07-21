<?php

namespace App\Domain\Store\Exceptions;

use RuntimeException;

class InsufficientStock extends RuntimeException
{
    public static function forProduct(string $name): self
    {
        return new self("Stock insuffisant pour « {$name} ».");
    }
}
