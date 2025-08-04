<?php

declare(strict_types=1);

namespace App\Exceptions;

class InvalidDomainException extends AuthenticationSourceException
{
    public function __construct()
    {
        parent::__construct('Email is not permitted');
    }
}
