<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\HasValidationMessage;
use Exception;
use Illuminate\Validation\ValidationException;

class VersionNotFoundException extends Exception implements HasValidationMessage
{
    public function asValidationMessage(string $attribute = 'version'): ValidationException
    {
        return ValidationException::withMessages([
            $attribute => ['no version provided'],
        ]);
    }
}
