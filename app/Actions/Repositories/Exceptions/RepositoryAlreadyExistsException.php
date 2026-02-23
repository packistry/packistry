<?php

declare(strict_types=1);

namespace App\Actions\Repositories\Exceptions;

use App\HasValidationMessage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RepositoryAlreadyExistsException extends RuntimeException implements HasValidationMessage
{
    public function asValidationMessage(string $attribute = 'path'): ValidationException
    {
        return ValidationException::withMessages([
            $attribute => 'Repository path has already been taken.',
        ]);
    }
}
