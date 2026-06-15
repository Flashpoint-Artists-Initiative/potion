<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\RefreshToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ConditionalJwtRefresh extends RefreshToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next): mixed
    {
        if (config('jwt.refresh_token', true)) {
            try {
                return parent::handle($request, $next);
            } catch (UnauthorizedHttpException $e) {
            }
        }

        return $next($request);
    }
}
