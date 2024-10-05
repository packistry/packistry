<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AcceptsJsonOrRedirectApp
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accept = $request->header('Accept', '');

        if (! Str::contains($accept, ['application/json'], true)) {
            return response()->file(public_path('index.html'));
        }

        return $next($request);
    }
}
