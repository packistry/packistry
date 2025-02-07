<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Source;
use App\Sources\Client;
use RuntimeException;

enum SourceProvider: string
{
    case GITEA = 'gitea';
    case GITHUB = 'github';
    case GITLAB = 'gitlab';
    case BITBUCKET = 'bitbucket';

    public function clientClassString(): string
    {
        $class = config()->string("services.$this->value.client");

        if (! is_subclass_of($class, Client::class)) {
            throw new RuntimeException($class.' has to implement '.Client::class);
        }

        return $class;
    }

    public function clientWith(string $token, string $url, ?Source $source = null): Client
    {
        /** @var Client $client */
        $client = app($this->clientClassString());
        return $client->withOptions(
            token: $token,
            url: $url,
            source: $source,
        );
    }
}
