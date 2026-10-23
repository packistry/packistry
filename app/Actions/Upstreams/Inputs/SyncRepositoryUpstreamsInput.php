<?php

declare(strict_types=1);

namespace App\Actions\Upstreams\Inputs;

use App\Actions\Input;
use Spatie\LaravelData\Attributes\DataCollectionOf;

class SyncRepositoryUpstreamsInput extends Input
{
    /**
     * @param  RepositoryUpstreamInput[]  $upstreams  in priority order
     */
    public function __construct(
        #[DataCollectionOf(RepositoryUpstreamInput::class)]
        public array $upstreams = [],
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'upstreams' => ['present', 'array'],
            'upstreams.*.id' => ['required', 'integer', 'distinct', 'exists:upstreams,id'],
            'upstreams.*.enabled' => ['boolean'],
        ];
    }
}
