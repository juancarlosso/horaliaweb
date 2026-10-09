<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class SifeiStampException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $requiresReview = false, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
