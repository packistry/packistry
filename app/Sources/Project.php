<?php

declare(strict_types=1);

namespace App\Sources;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class Project extends Data
{
    public function __construct(
        public int|string $id,
        public string $fullName,
        public string $name,
        public string $url,
        public string $webUrl,
    ) {
        //
    }
}
