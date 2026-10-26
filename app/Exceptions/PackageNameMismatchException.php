<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\HasValidationMessage;
use Exception;
use Illuminate\Validation\ValidationException;

class PackageNameMismatchException extends Exception implements HasValidationMessage
{
    public function __construct(string $name, string $packageName)
    {
        parent::__construct("composer.json name $name does not match package $packageName");
    }

    public function asValidationMessage(string $attribute = 'name'): ValidationException
    {
        return ValidationException::withMessages([
            $attribute => [$this->getMessage()],
        ]);
    }
}
