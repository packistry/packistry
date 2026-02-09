<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\HasValidationMessage;
use Exception;
use Illuminate\Validation\ValidationException;

class FailedToFetchArchiveException extends Exception implements HasValidationMessage
{
    public function asValidationMessage(string $attribute = 'archive'): ValidationException
    {
        return ValidationException::withMessages([
            $attribute => "Failed to fetch archive: {$this->getMessage()}",
        ]);
    }
}
