<?php

declare(strict_types=1);

namespace App\Sources\Bitbucket;

use App\Sources\GitHub\Input;
use Spatie\LaravelData\Attributes\MapInputName;

class Repository extends Input
{
    public function __construct(
        #[MapInputName('name')]
        public string $id,
        public string $name,
        public string $fullName,
        #[MapInputName('links.html.href')]
        public string $htmlUrl,
        #[MapInputName('links.self.href')]
        public string $url,
    ) {}
}
