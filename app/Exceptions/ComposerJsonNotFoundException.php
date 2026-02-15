<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\HasValidationMessage;
use Exception;
use Illuminate\Validation\ValidationException;

class ComposerJsonNotFoundException extends Exception implements HasValidationMessage
{
    public function asValidationMessage(string $attribute = 'file'): ValidationException
    {
        return ValidationException::withMessages([
            $attribute => ['composer.json not found in archive'],
        ]);
    }
}
