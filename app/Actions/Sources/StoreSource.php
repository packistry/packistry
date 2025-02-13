<?php

declare(strict_types=1);

namespace App\Actions\Sources;

use App\Actions\Sources\Inputs\StoreSourceInput;
use App\Exceptions\FailedToParseUrlException;
use App\Exceptions\InvalidTokenException;
use App\Models\Source;
use App\Normalizer;
use Illuminate\Support\Str;

class StoreSource
{
    /**
     * @throws InvalidTokenException|FailedToParseUrlException
     */
    public function handle(StoreSourceInput $input): Source
    {
        $input->provider->clientWith(
            token: $input->token,
            url: $input->url,
            metadata: $input->metadata,
        )->validateToken();

        $source = new Source;

        $source->name = $input->name;
        $source->provider = $input->provider;
        $source->url = Normalizer::url($input->url);
        $source->token = encrypt($input->token);
        $source->secret = encrypt(Str::random());

        if ($input->metadata !== null) {
            $source->metadata = $input->metadata;
        }

        $source->save();

        return $source;
    }
}
