<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\HasValidationMessage;
use Exception;
use Illuminate\Validation\ValidationException;

class NameNotFoundException extends Exception implements HasValidationMessage
{
    public function asValidationMessage(string $attribute = 'name'): ValidationException
    {
        return ValidationException::withMessages([
            $attribute => ['no name provided'],
        ]);
    }
}
