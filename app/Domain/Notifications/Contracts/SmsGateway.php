<?php

namespace App\Domain\Notifications\Contracts;

/**
 * No SMS provider is configured for the Gabonese market yet (Airtel/Moov
 * and any local aggregator all have their own signup, sandbox and pricing
 * - see docs/audit/roadmap.md, same caveat as mobile money). Sending goes
 * through this interface so a real gateway is a single new implementation
 * plus a config change, never a rewrite of the call sites.
 */
interface SmsGateway
{
    public function send(string $to, string $message): void;
}
