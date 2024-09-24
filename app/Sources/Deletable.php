<?php

declare(strict_types=1);

namespace App\Sources;

interface Deletable
{
    public function version(): string;

    public function name(): string;
}
