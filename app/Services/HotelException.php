<?php

namespace App\Services;

use RuntimeException;

/** A business-rule violation whose message is a translatable key shown to the user. */
class HotelException extends RuntimeException
{
    /** @param array<string, mixed> $replace */
    public function __construct(string $message, public array $replace = [])
    {
        parent::__construct($message);
    }

    public function userMessage(): string
    {
        return __($this->getMessage(), $this->replace);
    }
}
