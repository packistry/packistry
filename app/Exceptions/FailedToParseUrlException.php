<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\HasValidationMessage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class FailedToParseUrlException extends RuntimeException implements HasValidationMessage
{
    public function asValidationMessage(string $attribute = 'url'): ValidationException
    {
        return ValidationException::withMessages([
            $attribute => ['URL must be a valid URL'],
        ]);
    }
}
