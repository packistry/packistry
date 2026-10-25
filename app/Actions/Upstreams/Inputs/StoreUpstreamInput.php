<?php

declare(strict_types=1);

namespace App\Actions\Upstreams\Inputs;

use App\Actions\Input;
use Spatie\LaravelData\Attributes\DataCollectionOf;

class StoreUpstreamInput extends Input
{
    /**
     * @param  array<int, string|null>|null  $only
     * @param  array<int, string|null>|null  $exclude
     * @param  UpstreamCredentialInput[]  $credentials
     */
    public function __construct(
        public string $name,
        public string $url,
        public ?array $only = null,
        public ?array $exclude = null,
        #[DataCollectionOf(UpstreamCredentialInput::class)]
        public array $credentials = [],
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'url' => ['url:http,https'],
            'only.*' => ['nullable', 'string', 'max:255'],
            'exclude.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
