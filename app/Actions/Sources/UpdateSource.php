<?php

declare(strict_types=1);

namespace App\Actions\Sources;

use App\Actions\Sources\Inputs\UpdateSourceInput;
use App\Models\Source;
use App\Normalizer;

class UpdateSource
{
    public function handle(Source $source, UpdateSourceInput $input): Source
    {
        $source->name = $input->name;
        $source->url = Normalizer::url($input->url);
        $source->use_name_as_workspace = $input->use_name_as_workspace;

        if (is_string($input->token)) {
            $source->token = encrypt($input->token);
        }

        $source->save();

        return $source;
    }
}
