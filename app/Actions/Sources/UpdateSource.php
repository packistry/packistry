<?php

declare(strict_types=1);

namespace App\Actions\Sources;

use App\Actions\Sources\Inputs\UpdateSourceInput;
use App\Exceptions\FailedToParseUrlException;
use App\Exceptions\InvalidTokenException;
use App\Models\Source;
use App\Normalizer;

class UpdateSource
{
    /**
     * @throws InvalidTokenException|FailedToParseUrlException
     */
    public function handle(Source $source, UpdateSourceInput $input): Source
    {
        $source->name = $input->name;
        $source->url = Normalizer::url($input->url);

        if ($input->metadata !== null) {
            $source->metadata = [
                ...$source->metadata,
                ...$input->metadata,
            ];
        }

        if (is_string($input->token)) {
            $source->token = encrypt($input->token);
        }

        if ($source->isDirty(['token', 'metadata'])) {
            $source->client()->validateToken();
        }

        $source->save();

        return $source;
    }
}
