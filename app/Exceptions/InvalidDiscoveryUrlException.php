<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\HasValidationMessage;
use Exception;
use Illuminate\Validation\ValidationException;

class InvalidDiscoveryUrlException extends Exception implements HasValidationMessage
{
    public static function asValidationMessage(string $attribute = 'discovery_url'): ValidationException
    {
        return ValidationException::withMessages([
            $attribute => 'Discovery URL is not valid.',
        ]);
    }
}
