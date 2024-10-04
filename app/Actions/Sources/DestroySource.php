<?php

declare(strict_types=1);

namespace App\Actions\Sources;

use App\Models\Source;

class DestroySource
{
    public function handle(Source $source): Source
    {
        $source->delete();

        return $source;
    }
}
