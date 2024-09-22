<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class WebhookSecret
{
    public function __construct(
        protected readonly string $header = 'X-Hub-Signature-256',
        protected readonly string $secret = ''
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header($this->header);

        if (is_null($signature)) {
            return response()->json(['message' => 'signature missing'], 401);
        }

        $calculatedHash = 'sha256='.hash_hmac('sha256', $request->getContent(), $this->secret);

        if (! hash_equals($calculatedHash, $signature)) {
            return response()->json(['message' => 'signature validation failed'], 401);
        }

        return $next($request);
    }
}
