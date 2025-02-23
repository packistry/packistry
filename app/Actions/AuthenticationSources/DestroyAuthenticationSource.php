<?php

declare(strict_types=1);

namespace App\Actions\AuthenticationSources;

use App\Models\AuthenticationSource;

class DestroyAuthenticationSource
{
    public function handle(AuthenticationSource $source): AuthenticationSource
    {
        $source->delete();

        return $source;
    }
}
