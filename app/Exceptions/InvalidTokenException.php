<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\HasValidationMessage;
use Exception;
use Illuminate\Validation\ValidationException;

class InvalidTokenException extends Exception implements HasValidationMessage
{
    /**
     * @param  string[]  $missingScopes
     */
    public function __construct(public array $missingScopes = [])
    {
        parent::__construct('Token invalid');
    }

    public function asValidationMessage(string $attribute = 'token'): ValidationException
    {
        $message = 'Token does not appear to be valid';

        if (count($this->missingScopes) > 0) {
            $message .= ', or missing scopes: '.implode(', ', $this->missingScopes);
        }

        return ValidationException::withMessages([
            $attribute => [$message],
        ]);
    }
}
