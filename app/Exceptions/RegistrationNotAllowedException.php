<?php

declare(strict_types=1);

namespace App\Exceptions;

class RegistrationNotAllowedException extends AuthenticationSourceException
{
    public function __construct()
    {
        parent::__construct('Registration on this authentication source is not allowed');
    }
}
