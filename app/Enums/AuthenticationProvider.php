<?php

declare(strict_types=1);

namespace App\Enums;

use App\OIDCProvider;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\BitbucketProvider;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\GitlabProvider;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\ProviderInterface;

enum AuthenticationProvider: string
{
    case OIDC = 'oidc';
    case GITHUB = 'github';
    case GITLAB = 'gitlab';
    case BITBUCKET = 'bitbucket';
    case GOOGLE = 'google';

    /**
     * @return class-string<AbstractProvider&ProviderInterface>
     */
    public function socialiteProvider(): string
    {
        return match ($this) {
            AuthenticationProvider::OIDC => OIDCProvider::class,
            AuthenticationProvider::GITHUB => GithubProvider::class,
            AuthenticationProvider::GITLAB => GitlabProvider::class,
            AuthenticationProvider::BITBUCKET => BitbucketProvider::class,
            AuthenticationProvider::GOOGLE => GoogleProvider::class,
        };
    }
}
