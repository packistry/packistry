<?php

declare(strict_types=1);

namespace App\Actions\Packages\Exceptions;

use App\HasValidationMessage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RepositoryDoesNotSupportSourceSync extends RuntimeException implements HasValidationMessage
{
    public function asValidationMessage(string $attribute = 'repository'): ValidationException
    {
        return ValidationException::withMessages([
            $attribute => 'Source import is disabled for this repository. Use manual ZIP upload instead.',
        ]);
    }
}
