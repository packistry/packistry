<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\Response;

class GitlabWebhookSecret
{
    public function __construct(
        protected string $header = 'X-Gitlab-Token',
        #[SensitiveParameter] protected string $secret = ''
    ) {
        //
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->header($this->header);

        if (is_null($secret)) {
            return response()->json(['message' => 'secret missing'], 401);
        }

        if ($secret !== $this->secret) {
            return response()->json(['message' => 'invalid secret'], 401);
        }

        return $next($request);
    }
}
