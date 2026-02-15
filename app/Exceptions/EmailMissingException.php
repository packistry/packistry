<?php

declare(strict_types=1);

namespace App\Exceptions;

class EmailMissingException extends AuthenticationSourceException
{
    public function __construct()
    {
        parent::__construct('Email not provided');
    }
}
