<?php

declare(strict_types=1);

namespace App\Sources;

interface Importable
{
    public function zipUrl(): string;

    public function url(): string;

    public function sourceUrl(): string;

    public function version(): string;

    public function reference(): string;

    public function id(): string;
}
