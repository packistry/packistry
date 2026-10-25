<?php

declare(strict_types=1);

namespace App\Actions\Upstreams\Inputs;

use App\Actions\Input;
use Spatie\LaravelData\Attributes\DataCollectionOf;

class UpdateUpstreamInput extends Input
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
            // credentials in the url would bypass the rules for credentials, e.g. on public repositories
            'url' => ['url:http,https', 'not_regex:{^[a-z][a-z0-9+.-]*://[^/?#]*@}i'],
            'only.*' => ['nullable', 'string', 'max:255'],
            'exclude.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'url.not_regex' => 'Add credentials as a credential instead of in the url.',
        ];
    }
}
