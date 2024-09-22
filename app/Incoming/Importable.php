<?php

declare(strict_types=1);

namespace App\Incoming;

interface Importable
{
    public function zipUrl(): string;

    public function version(): string;

    public function name(): string;

    public function subDirectory(): string;
}
