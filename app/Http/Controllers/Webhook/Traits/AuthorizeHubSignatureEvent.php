<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook\Traits;

use Illuminate\Http\Request;

trait AuthorizeHubSignatureEvent
{
    public function authorizeWebhook(Request $request): void
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (is_null($signature)) {
            abort(401, 'signature missing');
        }

        $calculatedHash = 'sha256='.hash_hmac('sha256', $request->getContent(), decrypt($this->source()->secret));

        if (! hash_equals($calculatedHash, $signature)) {
            abort(401, 'signature validation failed');
        }
    }
}
