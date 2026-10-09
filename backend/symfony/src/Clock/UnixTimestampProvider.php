<?php

declare(strict_types=1);

namespace App\Clock;

final class UnixTimestampProvider
{
    public function now(): int
    {
        return time();
    }
}
