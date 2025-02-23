<?php

declare(strict_types=1);

namespace App;

use Illuminate\Validation\ValidationException;

/**
 * Implement this interface and the thrown exception will be rethrown as a validation exception
 * see bootstrap/app.php for details
 */
interface HasValidationMessage
{
    public static function asValidationMessage(string $attribute = ''): ValidationException;
}
