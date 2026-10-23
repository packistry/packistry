<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\HasValidationMessage;
use Exception;
use Illuminate\Validation\ValidationException;

class InvalidConstraintException extends Exception implements HasValidationMessage
{
    /**
     * @param  list<string>  $links
     */
    public function __construct(public readonly array $links)
    {
        parent::__construct('invalid version constraint in '.implode(', ', $links));
    }

    public function asValidationMessage(string $attribute = 'file'): ValidationException
    {
        return ValidationException::withMessages([
            $attribute => [$this->getMessage()],
        ]);
    }
}
