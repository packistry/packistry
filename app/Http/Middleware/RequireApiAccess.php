<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TokenAbility;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiAccess
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // sessions get a transient token, which has every ability
        if (! $user instanceof User || ! $user->tokenCan(TokenAbility::API->value)) {
            abort(403, 'This token has no API access.');
        }

        return $next($request);
    }
}
