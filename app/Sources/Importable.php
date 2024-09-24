<?php

declare(strict_types=1);

namespace App\Sources;

interface Importable
{
    public function zipUrl(): string;

    public function version(): string;

    public function name(): string;
}
