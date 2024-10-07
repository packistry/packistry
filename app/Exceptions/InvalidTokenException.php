<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvalidTokenException extends Exception
{
    /**
     * @param  string[]  $missingScopes
     */
    public function __construct(public array $missingScopes = [])
    {
        parent::__construct('Token invalid');
    }
}
