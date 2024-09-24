<?php

declare(strict_types=1);

namespace App\Sources\Traits;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait BearerToken
{
    public function http(): PendingRequest
    {
        return Http::baseUrl($this->url)
            ->withHeader('Authorization', "Bearer $this->token");
    }
}
