<?php

declare(strict_types=1);

namespace App\Actions\Sources;

use App\Actions\Sources\Inputs\StoreSourceInput;
use App\Models\Source;
use App\Normalizer;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Support\Str;
use Throwable;

class StoreSource
{
    /**
     * @throws Throwable
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
        $source->metadata = new ArrayObject($input->metadata);

        $source->save();

        return $source;
    }
}
